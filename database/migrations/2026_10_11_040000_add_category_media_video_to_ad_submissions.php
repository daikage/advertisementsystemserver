<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds category, media type, video support, and click-through URL columns.
     */
    public function up(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->string('category')->default('general')->after('submitter_contact');
            $table->enum('media_type', ['image', 'video', 'animated'])->default('image')->after('category');
            $table->string('video_path')->nullable()->after('image_path');
            $table->string('click_through_url')->nullable()->after('video_path');
            $table->float('display_duration')->default(5.0)->after('click_through_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_submissions', function (Blueprint $table) {
            $table->dropColumn(['category', 'media_type', 'video_path', 'click_through_url', 'display_duration']);
        });
    }
};
