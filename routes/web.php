<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\DelegateApprovalController;
use App\Http\Controllers\VolunteerApprovalController;
use Illuminate\Support\Facades\Route;

// Magic Link Authentication Routes
Route::middleware('guest')->group(function () {
    Route::post('/magic-link/request', [MagicLinkController::class, 'request'])
        ->name('magic-link.request');
});

Route::get('/magic-link/verify', [MagicLinkController::class, 'verify'])
    ->middleware('signed')
    ->name('magic-link.verify');

// Volunteer Approval Routes (signed URLs)
Route::middleware(['auth', 'signed'])->group(function () {
    Route::prefix('/volunteers/{volunteer}')->group(function () {
        Route::get('/approve', [VolunteerApprovalController::class, 'approve'])
            ->name('volunteers.approve');
        Route::get('/reject', [VolunteerApprovalController::class, 'reject'])
            ->name('volunteers.reject');
    })->can('update', 'volunteer');

    Route::prefix('/delegates/{delegate}')->group(function () {
        Route::get('/approve', [DelegateApprovalController::class, 'approve'])
            ->name('delegates.approve');
        Route::get('/reject', [DelegateApprovalController::class, 'reject'])
            ->name('delegates.reject');
    })->can('update', 'delegate');
});
