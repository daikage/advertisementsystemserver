<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates a default moderator account for dashboard access.
     */
    public function up(): void
    {
        if (User::where('email', 'admin@adsystem.local')->doesntExist()) {
            User::create([
                'name' => 'Ad Moderator',
                'email' => 'admin@adsystem.local',
                'password' => Hash::make(env('MODERATOR_DEFAULT_PASSWORD', 'admin123')),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('email', 'admin@adsystem.local')->delete();
    }
};
