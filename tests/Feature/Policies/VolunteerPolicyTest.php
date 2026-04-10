<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Volunteer;

test('volunteer policy allows user to terminate their own approved active mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeTrue();
});

test('volunteer policy denies terminating another users mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating pending mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->create([
        'user_id' => $user->id,
        'approval_status' => App\Enums\ApprovalStatus::Pending,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating already ended mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating rejected mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->rejected()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});
