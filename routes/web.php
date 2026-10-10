<?php

use App\Http\Controllers\ModeratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Moderator Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', [ModeratorController::class, 'index'])->name('moderator.index');
Route::post('/submissions/{id}/approve', [ModeratorController::class, 'approve'])->name('moderator.approve');
Route::post('/submissions/{id}/decline', [ModeratorController::class, 'decline'])->name('moderator.decline');
Route::delete('/submissions/{id}', [ModeratorController::class, 'destroy'])->name('moderator.destroy');
