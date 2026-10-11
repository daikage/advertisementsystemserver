<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Paystack payment lifecycle data + purchased duration to ad submissions.
     */
    public function up(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed'])
                  ->default('unpaid')
                  ->after('status');
            $table->string('payment_reference')->nullable()->unique()->after('payment_status');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_reference');
            $table->string('payment_currency', 3)->default('NGN')->after('payment_amount');
            $table->timestamp('paid_at')->nullable()->after('payment_currency');
            $table->integer('duration_days')->default(2)->after('paid_at');
            $table->string('paystack_access_code')->nullable()->after('duration_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_reference',
                'payment_amount',
                'payment_currency',
                'paid_at',
                'duration_days',
                'paystack_access_code',
            ]);
        });
    }
};
