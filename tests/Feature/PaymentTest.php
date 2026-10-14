<?php

namespace Tests\Feature;

use App\Models\AdSubmission;
use App\Models\AdTransaction;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'unity_game_ad_secret_key_2026';

    public function test_pricing_tiers_endpoint_returns_valid_catalog(): void
    {
        $response = $this->withHeader('X-Ad-Api-Key', $this->apiKey)
            ->getJson('/api/ads/pricing-tiers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('currency', 'NGN')
            ->assertJsonStructure([
                'success',
                'currency',
                'tiers' => [
                    '*' => [
                        'durationDays',
                        'tierLabel',
                        'priceNaira',
                        'priceKobo',
                        'dailyEquivalent',
                        'discountPercent',
                        'formattedPrice',
                        'discountText',
                    ],
                ],
            ]);
    }

    public function test_submit_with_payment_creates_submission_and_transaction(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status'  => true,
                'message' => 'Authorization URL created',
                'data'    => [
                    'authorization_url' => 'https://checkout.paystack.com/test1234',
                    'access_code'       => 'test_access_code_99',
                    'reference'         => 'ADSUB_test_ref',
                ],
            ], 200),
        ]);

        $image = UploadedFile::fake()->create('test_ad.png', 50, 'image/png');

        $response = $this->withHeader('X-Ad-Api-Key', $this->apiKey)
            ->post('/api/ads/submit-with-payment', [
                'business_name'     => 'Test Cyber Cafe',
                'description'       => 'Best gaming in town',
                'submitter_contact' => '+234800000000',
                'submitter_email'   => 'advertiser@test.com',
                'category'          => 'tech',
                'duration_days'     => 5,
                'image'             => $image,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('amountNaira', 2500)
            ->assertJsonPath('durationDays', 5);

        $reference = $response->json('reference');
        $this->assertNotEmpty($reference);

        // Assert submission created
        $submission = AdSubmission::where('payment_reference', $reference)->first();
        $this->assertNotNull($submission);
        $this->assertEquals('pending', $submission->status);
        $this->assertEquals('pending', $submission->payment_status);
        $this->assertNull($submission->starts_at);
        $this->assertNull($submission->expires_at);

        // Assert transaction created
        $transaction = AdTransaction::where('reference', $reference)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('initial', $transaction->type);
        $this->assertEquals(2500, (float) $transaction->amount);
        $this->assertEquals('pending', $transaction->status);
    }

    public function test_webhook_confirms_payment_and_does_not_prematurely_expire(): void
    {
        $submission = AdSubmission::create([
            'image_path'        => 'ads/test.png',
            'business_name'     => 'Test Bakery',
            'submitter_contact' => '+234811111111',
            'submitter_email'   => 'baker@test.com',
            'category'          => 'food',
            'media_type'        => 'image',
            'duration_days'     => 2,
            'status'            => 'pending',
            'payment_status'    => 'pending',
            'payment_amount'    => 1000,
            'payment_currency'  => 'NGN',
        ]);

        $reference = 'ADSUB' . $submission->id . 'TEST123';
        $submission->update(['payment_reference' => $reference]);

        $transaction = AdTransaction::create([
            'ad_submission_id' => $submission->id,
            'reference'        => $reference,
            'type'             => 'initial',
            'amount'           => 1000,
            'currency'         => 'NGN',
            'duration_days'    => 2,
            'status'           => 'pending',
        ]);

        $secret = (string) config('services.paystack.secret_key');
        $payload = json_encode([
            'event' => 'charge.success',
            'data'  => [
                'reference' => $reference,
                'amount'    => 100000, // 1000 NGN in kobo
                'currency'  => 'NGN',
                'status'    => 'success',
            ],
        ]);
        $signature = hash_hmac('sha512', $payload, $secret);

        $response = $this->call(
            'POST',
            '/api/paystack/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-Paystack-Signature' => $signature,
                'CONTENT_TYPE'              => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(200);

        $submission->refresh();
        $transaction->refresh();

        $this->assertTrue($transaction->isPaid());
        $this->assertTrue($submission->isPaid());
        // Verify campaign duration is NOT started while status is still pending review
        $this->assertEquals('pending', $submission->status);
        $this->assertNull($submission->starts_at);
        $this->assertNull($submission->expires_at);
    }

    public function test_moderator_approval_starts_full_purchased_duration(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $submission = AdSubmission::create([
            'image_path'        => 'ads/test.png',
            'business_name'     => 'Pizza Express',
            'submitter_contact' => '+234822222222',
            'submitter_email'   => 'pizza@test.com',
            'category'          => 'food',
            'duration_days'     => 14,
            'status'            => 'pending',
            'payment_status'    => 'paid',
            'payment_amount'    => 6500,
            'paid_at'           => now()->subDays(3), // Paid 3 days ago
        ]);

        $response = $this->actingAs($moderator)
            ->post("/submissions/{$submission->id}/approve", []);

        $submission->refresh();

        $this->assertEquals('approved', $submission->status);
        $this->assertNotNull($submission->starts_at);
        $this->assertNotNull($submission->expires_at);

        // Expiry must be 14 days from NOW (approval time), NOT from 3 days ago when paid
        $expectedExpiry = now()->addDays(14);
        $this->assertEquals($expectedExpiry->format('Y-m-d'), $submission->expires_at->format('Y-m-d'));
    }

    public function test_advertiser_portal_rejects_numeric_id_idor(): void
    {
        $submission = AdSubmission::create([
            'image_path'        => 'ads/test.png',
            'business_name'     => 'Secret Tech Inc',
            'submitter_contact' => '+234833333333',
            'submitter_email'   => 'ceo@secret.com',
            'duration_days'     => 30,
            'status'            => 'approved',
            'payment_status'    => 'paid',
            'payment_reference' => 'ADSUB_SECRET_TOKEN_99999',
        ]);

        // Attempting to access via integer ID directly must fail with 404
        $response = $this->get("/advertiser/status/{$submission->id}");
        $response->assertStatus(404);

        // Accessing via genuine secret token reference succeeds
        $responseValid = $this->get("/advertiser/status/{$submission->payment_reference}");
        $responseValid->assertStatus(200);
    }
}
