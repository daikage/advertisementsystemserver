<?php

namespace App\Http\Controllers;

use App\Models\AdSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModeratorController extends Controller
{
    /**
     * Display the moderator dashboard with status, category, payment, and expiry filtering.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'pending');
        if (!in_array($filter, ['pending', 'approved', 'declined', 'expired', 'all', 'unpaid', 'paid'])) {
            $filter = 'pending';
        }

        $category = $request->query('category');
        $now = now();

        $counts = [
            'total' => AdSubmission::count(),
            'pending' => AdSubmission::where('status', 'pending')->count(),
            'approved' => AdSubmission::where('status', 'approved')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                })->count(),
            'expired' => AdSubmission::where('status', 'approved')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)->count(),
            'declined' => AdSubmission::where('status', 'declined')->count(),
            'unpaid' => AdSubmission::where('payment_status', '!=', 'paid')->count(),
            'paid' => AdSubmission::where('payment_status', 'paid')->count(),
        ];

        // Financial metrics derived from confirmed payments.
        $financials = [
            'total_revenue' => (float) AdSubmission::where('payment_status', 'paid')->sum('payment_amount'),
            'active_paid_campaigns' => AdSubmission::where('payment_status', 'paid')
                ->where('status', 'approved')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                })->count(),
            'awaiting_payment' => AdSubmission::where('payment_status', 'pending')->count(),
        ];

        $categories = AdSubmission::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        $query = AdSubmission::orderBy('id', 'desc');

        if ($filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($filter === 'approved') {
            // Active approved ads
            $query->where('status', 'approved')
                  ->where(function ($q) use ($now) {
                      $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                  });
        } elseif ($filter === 'expired') {
            // Expired approved ads
            $query->where('status', 'approved')
                  ->whereNotNull('expires_at')
                  ->where('expires_at', '<', $now);
        } elseif ($filter === 'declined') {
            $query->where('status', 'declined');
        } elseif ($filter === 'unpaid') {
            $query->where('payment_status', '!=', 'paid');
        } elseif ($filter === 'paid') {
            $query->where('payment_status', 'paid');
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $submissions = $query->paginate(15);

        return view('moderator.index', compact('submissions', 'counts', 'financials', 'filter', 'categories', 'category'));
    }

    /**
     * Approve an ad submission with optional start date and expiration duration.
     *
     * Safety guard: paid submissions require a confirmed payment before they can
     * go live. A moderator may explicitly bypass this (e.g. house/test ads) via
     * the "force_approve" flag.
     */
    public function approve(Request $request, $id)
    {
        $ad = AdSubmission::findOrFail($id);

        // Block unpaid ads unless the moderator explicitly forces approval.
        $forceApprove = $request->boolean('force_approve');
        if (!$ad->isPaid() && !$forceApprove) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'This ad has not been paid for. Confirm payment or use the force-approve bypass.',
                ], 403);
            }

            return back()->withErrors(['approve' => "Ad #{$id} is unpaid. Confirm payment or tick 'Force approve (unpaid)' to bypass."]);
        }

        $startsAt = $request->input('starts_at') 
            ? Carbon::parse($request->input('starts_at')) 
            : ($ad->starts_at ?? now());

        $durationDays = $request->input('duration_days'); // '3', '7', '14', '30', 'unlimited', 'custom'
        $expiresAt = null;

        if ($durationDays === 'custom' && $request->input('expires_at')) {
            $expiresAt = Carbon::parse($request->input('expires_at'));
        } elseif (is_numeric($durationDays) && (int)$durationDays > 0) {
            $expiresAt = (clone $startsAt)->addDays((int)$durationDays);
        } elseif ($durationDays === 'unlimited') {
            $expiresAt = null;
        } elseif ($ad->expires_at) {
            // Retain existing schedule if not specified
            $expiresAt = $ad->expires_at;
        }

        $ad->update([
            'status' => 'approved',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'decline_reason' => null,
            'approved_by' => auth()->id(),
        ]);

        // Send approval email notification if advertiser email is provided
        if (!empty($ad->submitter_email)) {
            try {
                \Illuminate\Support\Facades\Mail::to($ad->submitter_email)->send(new \App\Mail\AdApprovedMail($ad));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send ad approval email: " . $e->getMessage());
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Ad #{$id} approved! Active from {$startsAt->format('M d')} to " . ($expiresAt ? $expiresAt->format('M d, Y') : 'indefinite'),
            ]);
        }

        $expiryMsg = $expiresAt ? " (runs until {$expiresAt->format('M d, Y')})" : " (no expiry)";
        return back()->with('success', "Ad '{$ad->business_name}' approved successfully{$expiryMsg}!");
    }

    /**
     * Decline an ad submission.
     */
    public function decline(Request $request, $id)
    {
        $ad = AdSubmission::findOrFail($id);
        $reason = $request->input('decline_reason', 'Not specified');

        $ad->update([
            'status' => 'declined',
            'decline_reason' => $reason,
            'declined_by' => auth()->id(),
        ]);

        // Send decline email notification if advertiser email is provided
        if (!empty($ad->submitter_email)) {
            try {
                \Illuminate\Support\Facades\Mail::to($ad->submitter_email)->send(new \App\Mail\AdDeclinedMail($ad, $reason));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send ad decline email: " . $e->getMessage());
            }
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Ad #{$id} declined."]);
        }

        return back()->with('success', "Ad '{$ad->business_name}' declined.");
    }

    /**
     * Renew / extend an ad campaign duration.
     */
    public function renew(Request $request, $id)
    {
        $ad = AdSubmission::findOrFail($id);
        $days = (int) $request->input('days', 30);
        if ($days <= 0) $days = 30;

        $baseDate = ($ad->expires_at && $ad->expires_at->isFuture()) ? $ad->expires_at : now();
        $newExpiry = (clone $baseDate)->addDays($days);

        $ad->update([
            'status' => 'approved',
            'expires_at' => $newExpiry,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Campaign extended by {$days} days until {$newExpiry->format('M d, Y')}.",
                'expires_at' => $newExpiry->toIso8601String(),
            ]);
        }

        return back()->with('success', "Ad '{$ad->business_name}' extended by {$days} days (valid until {$newExpiry->format('M d, Y')})!");
    }

    /**
     * Delete an ad submission and remove its image and video files.
     */
    public function destroy($id)
    {
        $ad = AdSubmission::findOrFail($id);
        
        if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
            Storage::disk('public')->delete($ad->image_path);
        }

        if ($ad->video_path && Storage::disk('public')->exists($ad->video_path)) {
            Storage::disk('public')->delete($ad->video_path);
        }

        $ad->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Ad #{$id} deleted."]);
        }

        return back()->with('success', 'Submission deleted successfully.');
    }
}
