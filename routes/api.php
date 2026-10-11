<?php

use App\Http\Controllers\Api\AdApiController;
use App\Http\Controllers\Api\AdPaymentController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Middleware\ValidateAdApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Unity Advertisement System
| Protected by:
| 1. API Key Authentication (X-Ad-Api-Key header)
| 2. Rate-Limiting (throttle middleware to prevent spam & DDoS)
|--------------------------------------------------------------------------
*/

/*
| Paystack webhook listener.
| Registered OUTSIDE the API-key group because Paystack cannot send our custom
| header — it is authenticated by validating its HMAC SHA-512 signature instead.
| It lives in the stateless `api` routes, which are excluded from CSRF, and is
| lightly throttled. This is the authoritative payment confirmation path.
*/
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])->middleware('throttle:120,1');

Route::middleware([ValidateAdApiKey::class, 'throttle:120,1'])->group(function () {
    // Ad fetching for game billboards
    Route::get('/ads', [AdApiController::class, 'getApprovedAds']);

    // In-game ad submission (stricter rate limit: 15 per minute per IP)
    Route::post('/ads/submit', [AdApiController::class, 'submit'])->middleware('throttle:15,1');

    // Pricing & paid submission flow (Paystack)
    Route::get('/ads/pricing-tiers', [AdPaymentController::class, 'pricingTiers']);
    Route::post('/ads/submit-with-payment', [AdPaymentController::class, 'submitWithPayment'])->middleware('throttle:15,1');
    Route::get('/ads/verify-payment/{reference}', [AdPaymentController::class, 'verifyPayment']);

    // Telemetry & analytics event endpoints
    Route::post('/ads/{id}/impression', [AdApiController::class, 'recordImpression']);
    Route::post('/ads/{id}/click', [AdApiController::class, 'recordClick']);
    Route::post('/ads/{id}/dismiss', [AdApiController::class, 'recordDismiss']);
    Route::post('/ads/events/batch', [AdApiController::class, 'recordBatchEvents']);
});
