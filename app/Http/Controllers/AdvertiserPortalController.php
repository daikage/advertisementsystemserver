<?php

namespace App\Http\Controllers;

use App\Models\AdSubmission;
use App\Services\PaystackService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdvertiserPortalController extends Controller
{
    /**
     * Show real-time ad performance and status to the advertiser via their payment reference.
     * No login required — secured by reference knowledge.
     */
    public function show(string $reference)
    {
        $query = AdSubmission::where('payment_reference', $reference);
        if (is_numeric($reference)) {
            $query->orWhere('id', (int) $reference);
        }
        $ad = $query->firstOrFail();

        // Calculate analytics metrics
        $impressions = $ad->events()->where('event_type', 'impression')->count();
        $clicks = $ad->events()->where('event_type', 'click')->count();
        $dismisses = $ad->events()->where('event_type', 'dismiss')->count();
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $avgViewDuration = $ad->events()->where('event_type', 'impression')->avg('duration_viewed') ?? 0;

        // Pricing tiers for self-service renewal
        $tiers = PaystackService::pricingTiers();

        // Time remaining
        $now = now();
        $isLive = $ad->status === 'approved' && (!$ad->expires_at || $ad->expires_at->isFuture()) && (!$ad->starts_at || $ad->starts_at->isPast());
        $isExpired = $ad->status === 'approved' && $ad->expires_at && $ad->expires_at->isPast();

        $timeRemaining = null;
        if ($ad->expires_at && $ad->expires_at->isFuture()) {
            $timeRemaining = $now->diffForHumans($ad->expires_at, ['parts' => 2, 'syntax' => Carbon::DIFF_ABSOLUTE]);
        }

        return view('advertiser.status', compact(
            'ad',
            'impressions',
            'clicks',
            'dismisses',
            'ctr',
            'avgViewDuration',
            'tiers',
            'isLive',
            'isExpired',
            'timeRemaining'
        ));
    }

    /**
     * Initiate a self-service campaign renewal payment for this ad.
     */
    public function initiateRenewal(Request $request, string $reference, PaystackService $paystack)
    {
        $query = AdSubmission::where('payment_reference', $reference);
        if (is_numeric($reference)) {
            $query->orWhere('id', (int) $reference);
        }
        $ad = $query->firstOrFail();

        $tier = PaystackService::getTier((int) $request->input('duration_days', 30));
        if (!$tier) {
            return back()->withErrors(['renewal' => 'Invalid duration tier selected.']);
        }

        $email = $ad->submitter_email ?? $request->input('email');
        if (empty($email)) {
            return back()->withErrors(['renewal' => 'Email address is required to process renewal.']);
        }

        $newReference = 'ADREN' . $ad->id . Str::upper(Str::random(6));
        $callbackUrl = route('advertiser.renewal_callback', ['reference' => $newReference, 'ad_id' => $ad->id]);

        $result = $paystack->initializeTransaction(
            $email,
            (int) $tier['priceKobo'],
            $newReference,
            [
                'submission_id' => $ad->id,
                'business_name' => $ad->business_name,
                'duration_days' => $tier['durationDays'],
                'is_renewal'    => true,
            ],
            $callbackUrl
        );

        if (!($result['status'] ?? false)) {
            return back()->withErrors(['renewal' => $result['message'] ?? 'Failed to initiate Paystack checkout.']);
        }

        return redirect()->away($result['data']['authorization_url']);
    }

    /**
     * Callback handler when returning from Paystack renewal checkout.
     */
    public function renewalCallback(Request $request, PaystackService $paystack)
    {
        $reference = $request->query('reference');
        $adId = $request->query('ad_id');

        $ad = AdSubmission::findOrFail($adId);
        $verify = $paystack->verifyTransaction($reference);

        if (($verify['status'] ?? false) && ($verify['data']['status'] ?? '') === 'success') {
            $durationDays = (int) ($verify['data']['metadata']['duration_days'] ?? 30);
            $base = ($ad->expires_at && $ad->expires_at->isFuture()) ? $ad->expires_at : now();
            $newExpiry = (clone $base)->addDays($durationDays);

            $ad->update([
                'status'         => 'approved',
                'expires_at'     => $newExpiry,
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            return redirect()->route('advertiser.status', ['reference' => $ad->payment_reference ?: $ad->id])
                ->with('success', "Campaign successfully renewed! Extended by {$durationDays} days until {$newExpiry->format('M d, Y')}.");
        }

        return redirect()->route('advertiser.status', ['reference' => $ad->payment_reference ?: $ad->id])
            ->withErrors(['renewal' => 'Payment could not be confirmed. Please contact support.']);
    }
}
