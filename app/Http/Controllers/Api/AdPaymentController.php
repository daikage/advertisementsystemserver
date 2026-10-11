<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Handles the paid advertisement submission flow backed by Paystack.
 *
 * Flow: initialize a transaction -> player pays on Paystack's hosted checkout
 * -> payment confirmed via webhook (authoritative) or client polling (verify).
 * The ad then awaits moderator approval like any other submission.
 */
class AdPaymentController extends Controller
{
    /**
     * Public pricing tier catalog for the in-game store.
     */
    public function pricingTiers()
    {
        return response()->json([
            'success'  => true,
            'currency' => 'NGN',
            'tiers'    => array_values(PaystackService::pricingTiers()),
        ]);
    }

    /**
     * Register a paid ad submission and initialize a Paystack transaction.
     */
    public function submitWithPayment(Request $request, PaystackService $paystack)
    {
        // Resolve the pricing tier server-side (never trust a client-sent amount).
        $tier = PaystackService::getTier((int) $request->input('duration_days'));
        if (!$tier) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid or missing duration tier. Fetch /api/ads/pricing-tiers for valid options.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'business_name'     => 'required|string|max:255',
            'description'       => 'nullable|string|max:1000',
            'submitter_contact' => 'nullable|string|max:255',
            'submitter_email'   => 'required|email|max:255',
            'category'          => 'nullable|string|max:100',
            'media_type'        => 'nullable|in:image,video',
            'image'             => 'required|image|mimes:png,jpg,jpeg,webp|max:10240',
            'video'             => 'nullable|mimes:mp4,webm,mov|max:51200',
            'click_through_url' => 'nullable|url|max:500',
            'display_duration'  => 'nullable|numeric|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('ads', 'public') : null;
        $videoPath = $request->hasFile('video') ? $request->file('video')->store('ads/videos', 'public') : null;

        // Create the submission first so we can derive a unique reference from its id.
        $submission = AdSubmission::create([
            'business_name'     => $request->business_name,
            'description'       => $request->description,
            'submitter_contact' => $request->submitter_contact,
            'category'          => $request->input('category', 'general'),
            'media_type'        => $request->input('media_type', 'image'),
            'image_path'        => $imagePath,
            'video_path'        => $videoPath,
            'click_through_url' => $request->click_through_url,
            'display_duration'  => $request->input('display_duration', 5.0),
            'duration_days'     => $tier['durationDays'],
            'status'            => 'pending',
            'payment_status'    => 'pending',
            'payment_amount'    => $tier['priceNaira'],
            'payment_currency'  => 'NGN',
        ]);

        $reference = 'ADSUB' . $submission->id . Str::upper(Str::random(6));

        $callbackUrl = rtrim((string) config('app.url'), '/');

        $result = $paystack->initializeTransaction(
            $request->input('submitter_email'),
            (int) $tier['priceKobo'],
            $reference,
            [
                'submission_id' => $submission->id,
                'business_name' => $submission->business_name,
                'duration_days' => $tier['durationDays'],
                'tier_label'    => $tier['tierLabel'],
                'price_naira'   => $tier['priceNaira'],
            ],
            $callbackUrl
        );

        if (!($result['status'] ?? false)) {
            $submission->update(['payment_status' => 'failed']);

            return response()->json([
                'success' => false,
                'error'   => $result['message'] ?? 'Failed to initialize Paystack transaction.',
            ], 502);
        }

        $data = $result['data'] ?? [];
        $submission->update([
            'payment_reference'    => $reference,
            'paystack_access_code' => $data['access_code'] ?? null,
        ]);

        return response()->json([
            'success'      => true,
            'submissionId' => $submission->id,
            'reference'    => $reference,
            'amountNaira'  => $tier['priceNaira'],
            'amountKobo'   => $tier['priceKobo'],
            'durationDays' => $tier['durationDays'],
            'paymentUrl'   => $data['authorization_url'] ?? '',
            'accessCode'   => $data['access_code'] ?? '',
        ], 201);
    }

    /**
     * Client-accessible payment verification (polling after hosted checkout).
     */
    public function verifyPayment(string $reference, PaystackService $paystack)
    {
        $submission = AdSubmission::where('payment_reference', $reference)->first();
        if (!$submission) {
            return response()->json(['success' => false, 'paid' => false, 'error' => 'Submission not found for reference.'], 404);
        }

        // Idempotent: already paid.
        if ($submission->isPaid()) {
            return response()->json($this->paymentState($submission));
        }

        $result   = $paystack->verifyTransaction($reference);
        $data     = $result['data'] ?? null;
        $expected = PaystackService::priceKoboForDays((int) $submission->duration_days);

        $isSuccess = ($result['status'] ?? false)
            && ($data['status'] ?? '') === 'success'
            && (int) ($data['amount'] ?? 0) === (int) $expected;

        if ($isSuccess) {
            $submission->markAsPaid();

            return response()->json($this->paymentState($submission->fresh()));
        }

        return response()->json([
            'success'       => true,
            'paid'          => false,
            'status'        => $submission->status,
            'paymentStatus' => $submission->payment_status,
            'message'       => 'Payment not confirmed yet.',
        ]);
    }

    /**
     * Build the canonical payment-state payload returned to clients.
     *
     * @return array<string, mixed>
     */
    private function paymentState(AdSubmission $submission): array
    {
        return [
            'success'       => true,
            'paid'          => $submission->isPaid(),
            'status'        => $submission->status,
            'paymentStatus' => $submission->payment_status,
            'reference'     => $submission->payment_reference,
            'amountNaira'   => (float) $submission->payment_amount,
            'durationDays'  => (int) $submission->duration_days,
            'startsAt'      => optional($submission->starts_at)->toIso8601String(),
            'expiresAt'     => optional($submission->expires_at)->toIso8601String(),
            'message'       => $submission->isPaid()
                ? 'Payment confirmed! Ad submitted for moderator review.'
                : 'Payment not confirmed yet.',
        ];
    }
}
