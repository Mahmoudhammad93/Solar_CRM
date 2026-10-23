<?php

use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([AuthenticateApiToken::class, 'throttle:api'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
});
