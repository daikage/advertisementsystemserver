<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use App\Models\AdTransaction;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Secure Paystack webhook listener.
 *
 * Authoritative payment confirmation path independent of the client.
 * Validates Paystack's HMAC SHA-512 signature, processes initial submissions,
 * direct card charges, and renewals idempotently.
 */
class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Paystack-Signature');

        if (!$paystack->validateWebhookSignature($payload, is_string($signature) ? $signature : null)) {
            Log::warning('Paystack webhook received with invalid HMAC signature.');
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        // Only successful charges advance payment state.
        if ($request->input('event') !== 'charge.success') {
            return response()->json(['status' => true]);
        }

        $reference = (string) $request->input('data.reference');
        $amount    = (int) $request->input('data.amount', 0);
        $currency  = strtoupper((string) $request->input('data.currency', ''));

        // Resolve transaction or direct submission
        $transaction = AdTransaction::where('reference', $reference)->first();
        $submission  = $transaction ? $transaction->submission : AdSubmission::where('payment_reference', $reference)->first();

        // Acknowledge unknown references so Paystack stops retrying.
        if (!$submission) {
            Log::info("Paystack webhook received for unknown reference: {$reference}. Acknowledged.");
            return response()->json(['status' => true]);
        }

        // Currency validation
        if ($currency !== 'NGN') {
            Log::warning("Paystack webhook currency mismatch for {$reference}: expected NGN, received {$currency}.");
            return response()->json(['status' => true, 'warning' => 'Currency mismatch']);
        }

        // Strict amount validation against the tier catalog (anti-tampering).
        $durationDays = $transaction ? (int) $transaction->duration_days : (int) $submission->duration_days;
        $expectedKobo = PaystackService::priceKoboForDays($durationDays);

        if ($expectedKobo !== null && $amount !== (int) $expectedKobo) {
            Log::warning("Paystack webhook amount mismatch for {$reference}: expected {$expectedKobo} kobo, received {$amount} kobo.");
            // Return 200 so Paystack does not flood retry cycles, but do not advance payment state
            return response()->json(['status' => true, 'warning' => 'Amount mismatch']);
        }

        // Process payment state atomically
        DB::transaction(function () use ($transaction, $submission, $durationDays) {
            if ($transaction) {
                $transaction->markAsPaid();

                if ($transaction->type === 'renewal') {
                    // Extend campaign expiry for renewals
                    $base = ($submission->expires_at && $submission->expires_at->isFuture())
                        ? $submission->expires_at
                        : now();
                    $newExpiry = (clone $base)->addDays($durationDays > 0 ? $durationDays : 30);

                    $submission->update([
                        'status'         => 'approved',
                        'expires_at'     => $newExpiry,
                        'payment_status' => 'paid',
                        'paid_at'        => now(),
                    ]);

                    Log::info("Paystack webhook successfully renewed ad #{$submission->id} for {$durationDays} days.");
                    return;
                }

                $submission->markAsPaid($transaction);
            } else {
                $submission->markAsPaid();
            }

            Log::info("Paystack webhook confirmed payment for submission #{$submission->id} (ref: {$submission->payment_reference}).");
        });

        return response()->json(['status' => true]);
    }
}
