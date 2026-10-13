<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\DelegateApprovalController;
use App\Http\Controllers\VolunteerApprovalController;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Route;

// Magic Link Authentication Routes
Route::middleware('guest')->group(function () {
    Route::post('/magic-link/request', [MagicLinkController::class, 'request'])
        ->name('magic-link.request');
});

Route::get('/magic-link/verify', [MagicLinkController::class, 'verify'])
    ->middleware('signed')
    ->name('magic-link.verify');

// Pending or not-yet-current records are hidden by global scopes, so bind without them (soft deletes still apply).
Route::bind('volunteer', fn (string $value): Volunteer => Volunteer::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])->findOrFail($value));
Route::bind('delegate', fn (string $value): Delegate => Delegate::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])->findOrFail($value));

// Volunteer Approval Routes (signed URLs)
Route::middleware(['auth', 'signed'])->group(function () {
    Route::prefix('/volunteers/{volunteer}')->can('update', 'volunteer')->group(function () {
        Route::get('/approve', [VolunteerApprovalController::class, 'approve'])
            ->name('volunteers.approve');
        Route::get('/reject', [VolunteerApprovalController::class, 'reject'])
            ->name('volunteers.reject');
    });

    Route::prefix('/delegates/{delegate}')->can('update', 'delegate')->group(function () {
        Route::get('/approve', [DelegateApprovalController::class, 'approve'])
            ->name('delegates.approve');
        Route::get('/reject', [DelegateApprovalController::class, 'reject'])
            ->name('delegates.reject');
    });
});
