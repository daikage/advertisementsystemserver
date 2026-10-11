<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModeratorController;
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

// Protected Moderator Dashboard & Telemetry Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [ModeratorController::class, 'index'])->name('moderator.index');
    Route::post('/submissions/{id}/approve', [ModeratorController::class, 'approve'])->name('moderator.approve');
    Route::post('/submissions/{id}/decline', [ModeratorController::class, 'decline'])->name('moderator.decline');
    Route::delete('/submissions/{id}', [ModeratorController::class, 'destroy'])->name('moderator.destroy');

    // Analytics & Telemetry Dashboard
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});
