<?php

declare(strict_types=1);

use App\Http\Controllers\ApproveDelegateController;
use App\Http\Controllers\ApproveVolunteerController;
use App\Http\Controllers\Auth\MagicLinkLoginsController;
use App\Http\Controllers\Auth\MagicLinksController;
use App\Http\Controllers\RejectDelegateController;
use App\Http\Controllers\RejectVolunteerController;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Route;

// Magic Link Authentication Routes
Route::middleware('guest')->group(function () {
    Route::post('/magic-link/request', [MagicLinksController::class, 'store'])
        ->middleware('throttle:magic-link')
        ->name('magicLink.request');
});

Route::get('/magic-link/verify', [MagicLinkLoginsController::class, 'show'])
    ->middleware('signed')
    ->name('magicLink.verify');

// Pending or not-yet-current records are hidden by global scopes, so bind without them (soft deletes still apply).
Route::bind('volunteer', fn (string $value): Volunteer => Volunteer::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])->findOrFail($value));
Route::bind('delegate', fn (string $value): Delegate => Delegate::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])->findOrFail($value));

// Volunteer Approval Routes (signed URLs)
Route::middleware(['auth', 'signed'])->group(function () {
    Route::prefix('/volunteers/{volunteer}')->can('update', 'volunteer')->group(function () {
        Route::get('/approve', ApproveVolunteerController::class)
            ->name('volunteers.approve');
        Route::get('/reject', RejectVolunteerController::class)
            ->name('volunteers.reject');
    });

    Route::prefix('/delegates/{delegate}')->can('update', 'delegate')->group(function () {
        Route::get('/approve', ApproveDelegateController::class)
            ->name('delegates.approve');
        Route::get('/reject', RejectDelegateController::class)
            ->name('delegates.reject');
    });
});
