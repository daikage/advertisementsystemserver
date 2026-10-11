<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAdApiKey
{
    /**
     * Handle an incoming request.
     * Validates client API Key for Unity in-game requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('AD_SYSTEM_API_KEY', 'unity_game_ad_secret_key_2026');

        // If explicitly set to empty or false, bypass API key verification
        if (empty($expectedKey)) {
            return $next($request);
        }

        $providedKey = $request->header('X-Ad-Api-Key') ?? $request->query('api_key');

        if (!$providedKey || !hash_equals($expectedKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Invalid or missing X-Ad-Api-Key header.',
            ], 401);
        }

        return $next($request);
    }
}
