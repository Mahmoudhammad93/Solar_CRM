<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\QuoteVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/quotes/verify/{token}', [QuoteVerificationController::class, 'show'])
    ->middleware('throttle:quote-verify')
    ->name('quotes.verify');

Route::middleware(['auth', 'throttle:document-download'])->group(function () {
    Route::get('/documents/{uuid}/download', DocumentDownloadController::class)
        ->name('documents.download');
});
