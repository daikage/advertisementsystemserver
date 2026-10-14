<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use App\Models\AdTransaction;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Handles the paid advertisement submission flow backed by Paystack.
 *
 * Flow: initialize a transaction -> player pays on Paystack's hosted checkout
 * or in-game direct card modal -> payment confirmed via webhook (authoritative)
 * or client polling (verify). The ad then awaits moderator approval.
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

        $mediaType = $request->input('media_type', 'image');

        $rules = [
            'business_name'     => 'required|string|max:255',
            'description'       => 'nullable|string|max:1000',
            'submitter_contact' => 'nullable|string|max:255',
            'submitter_email'   => 'required|email|max:255',
            'category'          => 'nullable|string|max:100',
            'media_type'        => 'nullable|in:image,video',
            'click_through_url' => 'nullable|url|max:500',
            'display_duration'  => 'nullable|numeric|min:1|max:120',
        ];

        if ($mediaType === 'video') {
            $rules['video'] = 'required|mimes:mp4,webm,mov|max:51200';
            $rules['image'] = 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240';
        } else {
            $rules['image'] = 'required|image|mimes:png,jpg,jpeg,webp|max:10240';
            $rules['video'] = 'nullable|mimes:mp4,webm,mov|max:51200';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $imagePath = null;
        $imageWidth = null;
        $imageHeight = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imagePath = $file->store('ads', 'public');
            $dim = @getimagesize($file->getRealPath());
            if ($dim) {
                $imageWidth = (int) $dim[0];
                $imageHeight = (int) $dim[1];
            }
        }

        $videoPath = $request->hasFile('video') ? $request->file('video')->store('ads/videos', 'public') : null;

        // Create the submission record with pending status
        $submission = AdSubmission::create([
            'business_name'     => $request->business_name,
            'description'       => $request->description,
            'submitter_contact' => $request->submitter_contact,
            'submitter_email'   => $request->input('submitter_email'),
            'category'          => $request->input('category', 'general'),
            'media_type'        => $mediaType,
            'image_path'        => $imagePath,
            'image_width'       => $imageWidth,
            'image_height'      => $imageHeight,
            'video_path'        => $videoPath,
            'click_through_url' => $request->click_through_url,
            'display_duration'  => $request->input('display_duration', 5.0),
            'duration_days'     => $tier['durationDays'],
            'status'            => 'pending',
            'payment_status'    => 'pending',
            'payment_amount'    => $tier['priceNaira'],
            'payment_currency'  => 'NGN',
        ]);

        $reference = 'ADSUB' . $submission->id . Str::upper(Str::random(10));

        // Create transaction audit record
        $transaction = AdTransaction::create([
            'ad_submission_id' => $submission->id,
            'reference'        => $reference,
            'type'             => 'initial',
            'amount'           => $tier['priceNaira'],
            'currency'         => 'NGN',
            'duration_days'    => $tier['durationDays'],
            'status'           => 'pending',
            'channel'          => 'hosted',
            'metadata'         => [
                'business_name' => $submission->business_name,
                'tier_label'    => $tier['tierLabel'],
            ],
        ]);

        $callbackUrl = rtrim((string) config('app.url'), '/') . '/advertiser/status/' . $reference;

        $result = $paystack->initializeTransaction(
            $request->input('submitter_email'),
            (int) $tier['priceKobo'],
            $reference,
            [
                'submission_id'  => $submission->id,
                'transaction_id' => $transaction->id,
                'business_name'  => $submission->business_name,
                'duration_days'  => $tier['durationDays'],
                'tier_label'     => $tier['tierLabel'],
                'price_naira'    => $tier['priceNaira'],
            ],
            $callbackUrl
        );

        if (!($result['status'] ?? false)) {
            $transaction->update(['status' => 'failed']);
            $submission->update(['payment_status' => 'failed']);

            return response()->json([
                'success' => false,
                'error'   => $result['message'] ?? 'Failed to initialize Paystack transaction.',
            ], 502);
        }

        $data = $result['data'] ?? [];
        $accessCode = $data['access_code'] ?? null;

        $transaction->update(['paystack_access_code' => $accessCode]);
        $submission->update([
            'payment_reference'    => $reference,
            'paystack_access_code' => $accessCode,
        ]);

        return response()->json([
            'success'      => true,
            'submissionId' => $submission->id,
            'reference'    => $reference,
            'amountNaira'  => $tier['priceNaira'],
            'amountKobo'   => $tier['priceKobo'],
            'durationDays' => $tier['durationDays'],
            'paymentUrl'   => $data['authorization_url'] ?? '',
            'accessCode'   => $accessCode ?? '',
        ], 201);
    }

    /**
     * Client-accessible payment verification (polling after hosted checkout).
     */
    public function verifyPayment(string $reference, PaystackService $paystack)
    {
        $transaction = AdTransaction::where('reference', $reference)->first();
        $submission = $transaction ? $transaction->submission : $this->findSubmissionByReference($reference);

        if (!$submission) {
            return response()->json(['success' => false, 'paid' => false, 'error' => 'Submission not found for reference.'], 404);
        }

        // Idempotent: already paid.
        if ($submission->isPaid()) {
            return response()->json($this->paymentState($submission));
        }

        $result   = $paystack->verifyTransaction($reference);
        $data     = $result['data'] ?? null;
        $expected = PaystackService::priceKoboForDays((int) ($transaction ? $transaction->duration_days : $submission->duration_days));

        $isSuccess = ($result['status'] ?? false)
            && ($data['status'] ?? '') === 'success'
            && strtoupper((string) ($data['currency'] ?? '')) === 'NGN'
            && (int) ($data['amount'] ?? 0) === (int) $expected;

        if ($isSuccess) {
            DB::transaction(function () use ($transaction, $submission) {
                if ($transaction) {
                    $transaction->markAsPaid();
                }
                $submission->markAsPaid($transaction);
            });

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
     * In-game direct card charge via Paystack.
     * Allows players to pay directly inside Unity without external browser checkout.
     */
    public function chargeCard(Request $request, PaystackService $paystack)
    {
        $validator = Validator::make($request->all(), [
            'reference'    => 'required|string',
            'card_number'  => 'required|string',
            'expiry_month' => 'required',
            'expiry_year'  => 'required',
            'cvv'          => 'required|string|min:3|max:4',
            'pin'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $reference = $request->input('reference');
        $transaction = AdTransaction::where('reference', $reference)->first();
        $submission = $transaction ? $transaction->submission : $this->findSubmissionByReference($reference);

        if (!$submission) {
            return response()->json(['success' => false, 'error' => 'Submission not found for reference.'], 404);
        }

        if ($submission->isPaid()) {
            return response()->json($this->paymentState($submission));
        }

        $cardNumber = preg_replace('/\D/', '', (string) $request->input('card_number'));
        $expMonth   = str_pad((string) $request->input('expiry_month'), 2, '0', STR_PAD_LEFT);
        $expYear    = (string) $request->input('expiry_year');
        if (strlen($expYear) === 4) {
            $expYear = substr($expYear, 2, 2);
        }
        $cvv = (string) $request->input('cvv');
        $pin = $request->input('pin');

        // Sandbox / mock mode check: Strictly allowed ONLY in local development with mock keys
        $isTestCard = in_array($cardNumber, ['4084084084084084', '5060666666666666', '4000000000000001']);
        $secretKey  = (string) config('services.paystack.secret_key', '');
        $isMockKey  = empty($secretKey) || str_starts_with($secretKey, 'sk_test_placeholder') || str_contains($secretKey, 'example');
        $allowSimulation = app()->isLocal() && $isMockKey;

        if ($isTestCard && $allowSimulation) {
            $chargeReference = 'ADCHG' . $submission->id . Str::upper(Str::random(10));
            $simulatedTx = AdTransaction::create([
                'ad_submission_id' => $submission->id,
                'reference'        => $chargeReference,
                'type'             => 'charge',
                'amount'           => $submission->payment_amount,
                'currency'         => 'NGN',
                'duration_days'    => (int) $submission->duration_days,
                'status'           => 'paid',
                'channel'          => 'direct_card_simulation',
                'paid_at'          => now(),
            ]);

            $submission->markAsPaid($simulatedTx);

            return response()->json([
                'success'       => true,
                'paid'          => true,
                'status'        => 'pending',
                'paymentStatus' => 'paid',
                'reference'     => $chargeReference,
                'message'       => 'Test Card Charged Successfully! Ad submitted for review.',
            ]);
        }

        $amountKobo = (int) PaystackService::priceKoboForDays((int) $submission->duration_days);
        $cardData = [
            'number'       => $cardNumber,
            'cvv'          => $cvv,
            'expiry_month' => $expMonth,
            'expiry_year'  => $expYear,
        ];
        if (!empty($pin)) {
            $cardData['pin'] = $pin;
        }

        // Generate a unique reference for the /charge attempt and track it in ad_transactions
        $chargeReference = 'ADCHG' . $submission->id . Str::upper(Str::random(10));
        $chargeTx = AdTransaction::create([
            'ad_submission_id' => $submission->id,
            'reference'        => $chargeReference,
            'type'             => 'charge',
            'amount'           => $submission->payment_amount,
            'currency'         => 'NGN',
            'duration_days'    => (int) $submission->duration_days,
            'status'           => 'pending',
            'channel'          => 'direct_card',
        ]);

        $result = $paystack->chargeCard(
            $submission->submitter_email ?? 'player@game.local',
            $amountKobo,
            $chargeReference,
            $cardData,
            [
                'submission_id'  => $submission->id,
                'transaction_id' => $chargeTx->id,
                'business_name'  => $submission->business_name,
                'channel'        => 'in_game_unity',
            ]
        );

        $status = $result['data']['status'] ?? '';

        if (($result['status'] ?? false) && $status === 'success') {
            DB::transaction(function () use ($chargeTx, $submission) {
                $chargeTx->markAsPaid();
                $submission->markAsPaid($chargeTx);
            });

            return response()->json($this->paymentState($submission->fresh()));
        }

        if (($result['status'] ?? false) && $status === 'send_otp') {
            return response()->json([
                'success'     => true,
                'paid'        => false,
                'requiresOtp' => true,
                'reference'   => $chargeReference,
                'message'     => $result['data']['message'] ?? 'Please enter the OTP sent to your phone/email.',
            ]);
        }

        if (($result['status'] ?? false) && $status === 'open_url') {
            return response()->json([
                'success'     => true,
                'paid'        => false,
                'requires3DS' => true,
                'authUrl'     => $result['data']['url'] ?? '',
                'reference'   => $chargeReference,
                'message'     => '3D-Secure authentication required.',
            ]);
        }

        // Charge failed
        $chargeTx->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'paid'    => false,
            'error'   => $result['message'] ?? 'Charge failed. Check card details and try again.',
        ], 400);
    }

    /**
     * Submit OTP for in-game payment authorization.
     */
    public function submitOtp(Request $request, PaystackService $paystack)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'otp'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $reference = $request->input('reference');
        $otp = $request->input('otp');

        $transaction = AdTransaction::where('reference', $reference)->first();
        $submission = $transaction ? $transaction->submission : $this->findSubmissionByReference($reference);

        if (!$submission) {
            return response()->json(['success' => false, 'error' => 'Submission not found.'], 404);
        }

        if ($submission->isPaid()) {
            return response()->json($this->paymentState($submission));
        }

        // Test card OTP bypass is STRICTLY allowed ONLY in local development with mock keys
        $secretKey = (string) config('services.paystack.secret_key', '');
        $isMockKey = empty($secretKey) || str_starts_with($secretKey, 'sk_test_placeholder') || str_contains($secretKey, 'example');
        $allowSimulation = app()->isLocal() && $isMockKey;

        if ($allowSimulation && ($otp === '123456' || $otp === '1234')) {
            DB::transaction(function () use ($transaction, $submission) {
                if ($transaction) {
                    $transaction->markAsPaid();
                }
                $submission->markAsPaid($transaction);
            });

            return response()->json($this->paymentState($submission->fresh()));
        }

        $result = $paystack->submitOtp($reference, $otp);
        $status = $result['data']['status'] ?? '';

        if (($result['status'] ?? false) && $status === 'success') {
            DB::transaction(function () use ($transaction, $submission) {
                if ($transaction) {
                    $transaction->markAsPaid();
                }
                $submission->markAsPaid($transaction);
            });

            return response()->json($this->paymentState($submission->fresh()));
        }

        return response()->json([
            'success' => false,
            'paid'    => false,
            'error'   => $result['message'] ?? 'OTP verification failed.',
        ], 400);
    }

    /**
     * Find a submission securely by exact transaction or payment reference.
     * Numeric IDs and regex extractions are intentionally omitted to prevent IDOR vulnerabilities.
     */
    private function findSubmissionByReference(string $reference): ?AdSubmission
    {
        $tx = AdTransaction::where('reference', $reference)->first();
        if ($tx && $tx->submission) {
            return $tx->submission;
        }

        return AdSubmission::where('payment_reference', $reference)->first();
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
