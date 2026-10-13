<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Addon fields for:
     * 1. Email Notifications  → submitter_email on ad_submissions
     * 2. RBAC                 → role on users, approved_by / declined_by on ad_submissions
     * 3. Creative Validation  → image_width / image_height on ad_submissions
     */
    public function up(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            // Email notifications — advertiser's email for approval/decline/expiry alerts
            $table->string('submitter_email')->nullable()->after('submitter_contact');

            // Creative validation — store uploaded image dimensions for aspect ratio checks
            $table->unsignedInteger('image_width')->nullable()->after('image_path');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');

            // RBAC audit trail — who approved or declined this ad
            $table->unsignedBigInteger('approved_by')->nullable()->after('decline_reason');
            $table->unsignedBigInteger('declined_by')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('declined_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            // RBAC — role column: 'admin', 'moderator', 'viewer'
            $table->string('role', 20)->default('moderator')->after('email');
        });

        // Set the existing default moderator to 'admin' role
        \App\Models\User::where('email', 'admin@adsystem.local')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['declined_by']);
            $table->dropColumn([
                'submitter_email',
                'image_width',
                'image_height',
                'approved_by',
                'declined_by',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
