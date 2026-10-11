<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds scheduling (starts_at, expires_at) and analytics tracking (ad_events table).
     */
    public function up(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('display_duration');
            $table->timestamp('expires_at')->nullable()->after('starts_at');
        });

        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_submission_id')->index();
            $table->string('event_type', 32)->index(); // 'impression', 'click', 'expand', 'dismiss'
            $table->integer('slot_index')->nullable();
            $table->string('device_id', 128)->nullable();
            $table->float('duration_viewed')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('ad_submission_id')
                  ->references('id')
                  ->on('ad_submissions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_events');

        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'expires_at']);
        });
    }
};
