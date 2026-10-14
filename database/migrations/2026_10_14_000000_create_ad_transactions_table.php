<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the ad_transactions table to support multiple payments, renewals,
     * direct card charges, and full audit ledger per ad campaign.
     */
    public function up(): void
    {
        Schema::create('ad_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_submission_id')->constrained('ad_submissions')->cascadeOnDelete();
            $table->string('reference', 100)->unique()->index();
            $table->string('type', 30)->default('initial'); // 'initial', 'renewal', 'charge'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->integer('duration_days')->default(0);
            $table->string('status', 30)->default('pending')->index(); // 'pending', 'paid', 'failed', 'refunded'
            $table->string('channel', 50)->nullable(); // 'hosted', 'direct_card', 'portal_renewal', 'api_renewal'
            $table->timestamp('paid_at')->nullable();
            $table->string('paystack_access_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_transactions');
    }
};
