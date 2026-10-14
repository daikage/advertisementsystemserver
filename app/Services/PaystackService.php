<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Paystack payment gateway integration service.
 *
 * Acts as the single source of truth for duration-based pricing tiers and
 * wraps the Paystack REST API (transaction initialize / verify) plus webhook
 * signature validation. The secret key never leaves the server.
 */
class PaystackService
{
    /** Base NGN per-day rate for short-term runs (used to derive discounts). */
    public const BASE_DAILY_RATE = 500;

    /**
     * Raw tier definitions: [duration_days, label, price_naira].
     * Ordered from shortest to longest commitment.
     *
     * @var array<int, array{0:int,1:string,2:int}>
     */
    private static array $definitions = [
        [2,   'Micro (2 Days)',         1000],
        [5,   'Standard (5 Days)',      2500],
        [10,  'Standard (10 Days)',     5000],
        [14,  'Fortnight (2 Weeks)',    6500],
        [30,  'Monthly (1 Month)',      12500],
        [60,  'Bi-Monthly (2 Months)',  22000],
        [90,  'Quarterly (3 Months)',   30000],
        [180, 'Semi-Annual (6 Months)', 54000],
        [365, 'Annual (1 Year)',        90000],
    ];

    protected function secretKey(): string
    {
        return (string) config('services.paystack.secret_key', '');
    }

    protected function baseUrl(): string
    {
        $url = rtrim((string) config('services.paystack.payment_url', 'https://api.paystack.co'), '/');
        // Paystack REST API is on api.paystack.co; api.paystack.com is Cloudflare browser-protected
        if (str_contains($url, 'api.paystack.com')) {
            $url = str_replace('api.paystack.com', 'api.paystack.co', $url);
        }
        return $url;
    }

    protected function shouldVerifySsl(): bool
    {
        $configured = config('services.paystack.verify_ssl');
        if ($configured !== null) {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }

    /**
     * Central source of truth for the duration -> price mapping.
     * Returns tiers keyed by duration_days with derived pricing metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pricingTiers(): array
    {
        $tiers = [];

        foreach (self::$definitions as [$days, $label, $price]) {
            $dailyEquivalent = (int) floor($price / $days);
            $fullRate        = $days * self::BASE_DAILY_RATE;
            $discountPercent = $fullRate > 0 ? (int) round((1 - ($price / $fullRate)) * 100) : 0;

            $tiers[$days] = [
                'durationDays'    => $days,
                'tierLabel'       => $label,
                'priceNaira'      => $price,
                'priceKobo'       => $price * 100,
                'dailyEquivalent' => $dailyEquivalent,
                'discountPercent' => $discountPercent,
                'formattedPrice'  => '₦' . number_format($price) . ' NGN',
                'discountText'    => $discountPercent > 0 ? "Save {$discountPercent}%" : 'Baseline',
            ];
        }

        return $tiers;
    }

    /**
     * Look up a single tier by its duration in days.
     *
     * @return array<string, mixed>|null
     */
    public static function getTier(int $days): ?array
    {
        return self::pricingTiers()[$days] ?? null;
    }

    /**
     * Get the price (in kobo) for a given duration, or null if invalid.
     */
    public static function priceKoboForDays(int $days): ?int
    {
        $tier = self::getTier($days);

        return $tier['priceKobo'] ?? null;
    }

    /**
     * Initialize a Paystack transaction.
     *
     * @param array<string, mixed> $metadata
     * @return array<string, mixed> Decoded Paystack response.
     */
    public function initializeTransaction(string $email, int $amountKobo, string $reference, array $metadata = [], ?string $callbackUrl = null): array
    {
        $payload = [
            'email'     => $email,
            'amount'    => $amountKobo,
            'reference' => $reference,
            'currency'  => 'NGN',
            'metadata'  => $metadata,
        ];

        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        try {
            $request = Http::withToken($this->secretKey())
                ->acceptJson()
                ->timeout(20);

            if (!$this->shouldVerifySsl()) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl() . '/transaction/initialize', $payload);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            \Log::error('Paystack initializeTransaction error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Paystack connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * @return array<string, mixed> Decoded Paystack response.
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $request = Http::withToken($this->secretKey())
                ->acceptJson()
                ->timeout(20);

            if (!$this->shouldVerifySsl()) {
                $request = $request->withoutVerifying();
            }

            $response = $request->get($this->baseUrl() . '/transaction/verify/' . rawurlencode($reference));

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            \Log::error('Paystack verifyTransaction error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Paystack verify error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Charge a card directly using Paystack Charge API.
     *
     * @param array<string, mixed> $cardData ['number', 'cvv', 'expiry_month', 'expiry_year', 'pin']
     * @param array<string, mixed> $metadata
     * @return array<string, mixed> Decoded Paystack response.
     */
    public function chargeCard(string $email, int $amountKobo, string $reference, array $cardData, array $metadata = []): array
    {
        $payload = [
            'email'     => $email,
            'amount'    => $amountKobo,
            'reference' => $reference,
            'currency'  => 'NGN',
            'card'      => $cardData,
            'metadata'  => $metadata,
        ];

        try {
            $request = Http::withToken($this->secretKey())
                ->acceptJson()
                ->timeout(25);

            if (!$this->shouldVerifySsl()) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl() . '/charge', $payload);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            \Log::error('Paystack chargeCard error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Paystack connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Submit OTP for a pending Paystack charge.
     */
    public function submitOtp(string $reference, string $otp): array
    {
        try {
            $request = Http::withToken($this->secretKey())
                ->acceptJson()
                ->timeout(20);

            if (!$this->shouldVerifySsl()) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl() . '/charge/submit_otp', [
                'reference' => $reference,
                'otp'       => $otp,
            ]);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            \Log::error('Paystack submitOtp error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => 'Paystack OTP error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate the X-Paystack-Signature header (HMAC SHA-512 of the raw body).
     */
    public function validateWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = $this->secretKey();

        if (empty($signature) || $secret === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}
