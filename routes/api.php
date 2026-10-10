<?php

use App\Http\Controllers\Api\AdApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Unity Advertisement System
|--------------------------------------------------------------------------
*/

Route::post('/ads/submit', [AdApiController::class, 'submit']);
Route::get('/ads', [AdApiController::class, 'getApprovedAds']);
