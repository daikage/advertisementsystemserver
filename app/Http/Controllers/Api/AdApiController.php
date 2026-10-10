<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdApiController extends Controller
{
    /**
     * Handle in-game advertisement submission.
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'submitter_contact' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        // Store image on the public disk inside 'ads' folder
        $imagePath = $request->file('image')->store('ads', 'public');

        $submission = AdSubmission::create([
            'business_name' => $request->business_name,
            'description' => $request->description,
            'submitter_contact' => $request->submitter_contact,
            'image_path' => $imagePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ad submitted successfully! Awaiting review.',
            'submission_id' => $submission->id,
        ], 201);
    }

    /**
     * Return all approved advertisements for game billboards.
     */
    public function getApprovedAds()
    {
        $approved = AdSubmission::where('status', 'approved')
            ->orderBy('id', 'desc')
            ->get();

        $ads = $approved->map(function ($ad) {
            return [
                'id' => $ad->id,
                'businessName' => $ad->business_name,
                'description' => $ad->description,
                'imageUrl' => asset('storage/' . $ad->image_path),
                'displayDuration' => 5.0,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $ads->count(),
            'advertisements' => $ads,
        ]);
    }
}
