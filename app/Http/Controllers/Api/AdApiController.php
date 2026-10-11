<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\AdSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdApiController extends Controller
{
    /**
     * Handle in-game advertisement submission.
     * Supports image ads, video ads, category tagging, and optional scheduling.
     */
    public function submit(Request $request)
    {
        $mediaType = $request->input('media_type', 'image');

        // Build validation rules based on media type
        $rules = [
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'submitter_contact' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'media_type' => 'nullable|in:image,video,animated',
            'click_through_url' => 'nullable|url|max:500',
            'display_duration' => 'nullable|numeric|min:1|max:120',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ];

        if ($mediaType === 'video') {
            $rules['video'] = 'required|mimes:mp4,webm,mov|max:51200'; // Max 50MB
            $rules['image'] = 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240'; // Optional thumbnail
        } else {
            $rules['image'] = 'required|image|mimes:png,jpg,jpeg,webp|max:10240'; // Max 10MB
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        // Store image on the public disk inside 'ads' folder
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ads', 'public');
        }

        // Store video if present
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('ads/videos', 'public');
        }

        $durationDays = $request->input('duration_days');
        $startsAt = $request->input('starts_at') ? \Carbon\Carbon::parse($request->input('starts_at')) : now();
        $expiresAt = $request->input('expires_at') ? \Carbon\Carbon::parse($request->input('expires_at')) : null;

        if (!$expiresAt && is_numeric($durationDays) && (int)$durationDays > 0) {
            $expiresAt = (clone $startsAt)->addDays((int)$durationDays);
        }

        $submission = AdSubmission::create([
            'business_name' => $request->business_name,
            'description' => $request->description,
            'submitter_contact' => $request->submitter_contact,
            'category' => $request->input('category', 'general'),
            'media_type' => $mediaType,
            'image_path' => $imagePath,
            'video_path' => $videoPath,
            'click_through_url' => $request->click_through_url,
            'display_duration' => $request->input('display_duration', 5.0),
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ad submitted successfully! Awaiting review.',
            'submission_id' => $submission->id,
        ], 201);
    }

    /**
     * Return all approved and currently active advertisements for game billboards.
     * Respects starts_at / expires_at schedule and optional category filtering.
     */
    public function getApprovedAds(Request $request)
    {
        // Use currentlyActive scope to automatically filter by approved status + active schedule
        $query = AdSubmission::currentlyActive()->orderBy('id', 'desc');

        // Optional category filter
        $category = $request->query('category');
        if (!empty($category) && strtolower($category) !== 'all') {
            $query->where('category', $category);
        }

        $approved = $query->get();

        $ads = $approved->map(function ($ad) {
            $result = [
                'id' => $ad->id,
                'businessName' => $ad->business_name,
                'description' => $ad->description,
                'imageUrl' => $ad->image_url,
                'displayDuration' => (float) ($ad->display_duration ?? 5.0),
                'category' => $ad->category ?? 'general',
                'mediaType' => $ad->media_type ?? 'image',
                'clickThroughUrl' => $ad->click_through_url ?? '',
                'startsAt' => $ad->starts_at ? $ad->starts_at->toIso8601String() : null,
                'expiresAt' => $ad->expires_at ? $ad->expires_at->toIso8601String() : null,
            ];

            // Include video URL if the ad is a video type
            if ($ad->media_type === 'video' && !empty($ad->video_path)) {
                $result['videoUrl'] = $ad->video_url;
            } else {
                $result['videoUrl'] = '';
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'count' => $ads->count(),
            'advertisements' => $ads,
        ]);
    }

    /**
     * Record an ad impression event (when an ad becomes visible to a player).
     */
    public function recordImpression(Request $request, $id)
    {
        $ad = AdSubmission::find($id);
        if (!$ad) {
            return response()->json(['success' => false, 'error' => 'Ad not found'], 404);
        }

        AdEvent::create([
            'ad_submission_id' => $ad->id,
            'event_type' => 'impression',
            'slot_index' => $request->input('slot_index'),
            'device_id' => $request->input('device_id'),
            'duration_viewed' => $request->input('duration_viewed', 0),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Impression logged']);
    }

    /**
     * Record an ad click or interaction event.
     */
    public function recordClick(Request $request, $id)
    {
        $ad = AdSubmission::find($id);
        if (!$ad) {
            return response()->json(['success' => false, 'error' => 'Ad not found'], 404);
        }

        AdEvent::create([
            'ad_submission_id' => $ad->id,
            'event_type' => 'click',
            'slot_index' => $request->input('slot_index'),
            'device_id' => $request->input('device_id'),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Click logged']);
    }

    /**
     * Record an ad dismiss event (when player chooses "Not interested").
     */
    public function recordDismiss(Request $request, $id)
    {
        $ad = AdSubmission::find($id);
        if (!$ad) {
            return response()->json(['success' => false, 'error' => 'Ad not found'], 404);
        }

        AdEvent::create([
            'ad_submission_id' => $ad->id,
            'event_type' => 'dismiss',
            'slot_index' => $request->input('slot_index'),
            'device_id' => $request->input('device_id'),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Dismiss logged']);
    }

    /**
     * Record a batch of telemetry events from Unity client for high efficiency.
     */
    public function recordBatchEvents(Request $request)
    {
        $events = $request->input('events', []);
        if (!is_array($events) || empty($events)) {
            return response()->json(['success' => false, 'error' => 'No events provided'], 400);
        }

        $now = now();
        $ip = $request->ip();
        $insertData = [];

        foreach ($events as $evt) {
            if (empty($evt['ad_id']) || empty($evt['event_type'])) continue;

            $insertData[] = [
                'ad_submission_id' => (int) $evt['ad_id'],
                'event_type' => (string) $evt['event_type'],
                'slot_index' => isset($evt['slot_index']) ? (int) $evt['slot_index'] : null,
                'device_id' => $evt['device_id'] ?? null,
                'duration_viewed' => isset($evt['duration_viewed']) ? (float) $evt['duration_viewed'] : null,
                'ip_address' => $ip,
                'created_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            AdEvent::insert($insertData);
        }

        return response()->json([
            'success' => true,
            'logged_count' => count($insertData),
        ]);
    }
}
