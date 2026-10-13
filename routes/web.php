<?php

use App\Http\Controllers\AdvertiserPortalController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Moderator Dashboard, Analytics & Authentication
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Advertiser Self-Service Portal (Secured by payment reference)
Route::get('/advertiser/status/{reference}', [AdvertiserPortalController::class, 'show'])->name('advertiser.status');
Route::post('/advertiser/renew/{reference}', [AdvertiserPortalController::class, 'initiateRenewal'])->name('advertiser.renew');
Route::get('/advertiser/renewal-callback', [AdvertiserPortalController::class, 'renewalCallback'])->name('advertiser.renewal_callback');

// Authenticated Routes (Viewer, Moderator, Admin)
Route::middleware('auth')->group(function () {
    // Read-only accessible by all team roles (viewer, moderator, admin)
    Route::get('/', [ModeratorController::class, 'index'])->name('moderator.index');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Moderation Actions (Requires moderator or admin role)
    Route::middleware('role:moderator,admin')->group(function () {
        Route::post('/submissions/{id}/approve', [ModeratorController::class, 'approve'])->name('moderator.approve');
        Route::post('/submissions/{id}/decline', [ModeratorController::class, 'decline'])->name('moderator.decline');
        Route::post('/submissions/{id}/renew', [ModeratorController::class, 'renew'])->name('moderator.renew');
        Route::delete('/submissions/{id}', [ModeratorController::class, 'destroy'])->name('moderator.destroy');
    });

    // Team & RBAC Management (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{id}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
