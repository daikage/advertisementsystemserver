<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubmission;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdRenewalApiController extends Controller
{
    /**
     * Initialize a renewal transaction for an ad campaign.
     * Can be invoked from the game client or an external portal.
     */
    public function initializeRenewal(Request $request, $id, PaystackService $paystack)
    {
        $ad = AdSubmission::where('id', $id)
            ->orWhere('payment_reference', $id)
            ->first();

        if (!$ad) {
            return response()->json(['success' => false, 'error' => 'Ad submission not found.'], 404);
        }

        $tier = PaystackService::getTier((int) $request->input('duration_days', 30));
        if (!$tier) {
            return response()->json(['success' => false, 'error' => 'Invalid or missing duration tier.'], 422);
        }

        $email = $request->input('email', $ad->submitter_email);
        if (empty($email)) {
            return response()->json(['success' => false, 'error' => 'Email is required for renewal receipt.'], 422);
        }

        $renewalRef = 'ADREN' . $ad->id . Str::upper(Str::random(6));
        $callbackUrl = route('advertiser.renewal_callback', ['reference' => $renewalRef, 'ad_id' => $ad->id]);

        $result = $paystack->initializeTransaction(
            $email,
            (int) $tier['priceKobo'],
            $renewalRef,
            [
                'submission_id' => $ad->id,
                'business_name' => $ad->business_name,
                'duration_days' => $tier['durationDays'],
                'is_renewal'    => true,
            ],
            $callbackUrl
        );

        if (!($result['status'] ?? false)) {
            return response()->json([
                'success' => false,
                'error'   => $result['message'] ?? 'Failed to initialize Paystack renewal.',
            ], 502);
        }

        $data = $result['data'] ?? [];

        return response()->json([
            'success'      => true,
            'adId'         => $ad->id,
            'reference'    => $renewalRef,
            'amountNaira'  => $tier['priceNaira'],
            'durationDays' => $tier['durationDays'],
            'paymentUrl'   => $data['authorization_url'] ?? '',
            'accessCode'   => $data['access_code'] ?? '',
        ]);
    }
}
