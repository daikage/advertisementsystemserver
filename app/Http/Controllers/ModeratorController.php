<?php

namespace App\Http\Controllers;

use App\Models\AdSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModeratorController extends Controller
{
    /**
     * Display the moderator dashboard.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'pending');
        if (!in_array($filter, ['pending', 'approved', 'declined', 'all'])) {
            $filter = 'pending';
        }

        $counts = [
            'total' => AdSubmission::count(),
            'pending' => AdSubmission::where('status', 'pending')->count(),
            'approved' => AdSubmission::where('status', 'approved')->count(),
            'declined' => AdSubmission::where('status', 'declined')->count(),
        ];

        $query = AdSubmission::orderBy('id', 'desc');
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $submissions = $query->paginate(15);

        return view('moderator.index', compact('submissions', 'counts', 'filter'));
    }

    /**
     * Approve an ad submission.
     */
    public function approve($id)
    {
        $ad = AdSubmission::findOrFail($id);
        $ad->update([
            'status' => 'approved',
            'decline_reason' => null,
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Ad #{$id} approved!"]);
        }

        return back()->with('success', "Ad '{$ad->business_name}' approved successfully!");
    }

    /**
     * Decline an ad submission.
     */
    public function decline(Request $request, $id)
    {
        $ad = AdSubmission::findOrFail($id);
        $ad->update([
            'status' => 'declined',
            'decline_reason' => $request->input('decline_reason', 'Not specified'),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Ad #{$id} declined."]);
        }

        return back()->with('success', "Ad '{$ad->business_name}' declined.");
    }

    /**
     * Delete an ad submission and remove its image file.
     */
    public function destroy($id)
    {
        $ad = AdSubmission::findOrFail($id);
        
        if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
            Storage::disk('public')->delete($ad->image_path);
        }

        $ad->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Ad #{$id} deleted."]);
        }

        return back()->with('success', 'Submission deleted successfully.');
    }
}
