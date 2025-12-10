<?php

use App\Http\Controllers\Auth\MagicLinkController;
use Illuminate\Support\Facades\Route;

// Magic Link Authentication Routes
Route::middleware('guest')->group(function () {
    Route::post('/magic-link/request', [MagicLinkController::class, 'request'])
        ->name('magic-link.request');
});

Route::get('/magic-link/verify', [MagicLinkController::class, 'verify'])
    ->middleware('signed')
    ->name('magic-link.verify');
