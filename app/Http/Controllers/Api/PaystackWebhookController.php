<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use App\Services\PaystackService;
use Illuminate\Http\Request;

/**
 * Secure Paystack webhook listener.
 *
 * This is the authoritative confirmation path (independent of the client).
 * It lives in the stateless `api` routes, which are excluded from CSRF, and
 * is authenticated by validating Paystack's HMAC SHA-512 signature.
 */
class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Paystack-Signature');

        if (!$paystack->validateWebhookSignature($payload, is_string($signature) ? $signature : null)) {
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        // Only successful charges advance the payment state.
        if ($request->input('event') !== 'charge.success') {
            return response()->json(['status' => true]);
        }

        $reference = (string) $request->input('data.reference');
        $amount    = (int) $request->input('data.amount', 0);

        $submission = AdSubmission::where('payment_reference', $reference)->first();

        // Acknowledge unknown references so Paystack stops retrying.
        if (!$submission) {
            return response()->json(['status' => true]);
        }

        // Strict amount validation against the tier catalog (anti-tampering).
        $expectedKobo = PaystackService::priceKoboForDays((int) $submission->duration_days);
        if ($amount !== (int) $expectedKobo) {
            return response()->json(['status' => false, 'message' => 'Amount mismatch'], 400);
        }

        // markAsPaid() is idempotent, so replays are safe.
        $submission->markAsPaid();

        return response()->json(['status' => true]);
    }
}
