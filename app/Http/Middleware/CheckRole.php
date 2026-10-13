<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request and check if user has the required role(s).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Admin always has access to all roles
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user's role is in the list of allowed roles
        if (!in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized for this action.'], 403);
            }
            abort(403, "You do not have permission to perform this action. Required role: " . implode(' or ', $roles));
        }

        return $next($request);
    }
}
