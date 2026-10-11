<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the moderator login page.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('moderator.index');
        }

        return view('auth.login');
    }

    /**
     * Process moderator login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('moderator.index'))
                ->with('success', 'Logged in successfully! Welcome back.');
        }

        return back()->withErrors([
            'email' => 'Invalid email credentials or password.',
        ])->onlyInput('email');
    }

    /**
     * Log out the current moderator.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
