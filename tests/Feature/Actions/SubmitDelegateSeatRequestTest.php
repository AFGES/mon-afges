<?php

declare(strict_types=1);

use App\Actions\SubmitDelegateSeatRequest;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\User;
use Illuminate\Database\QueryException;

it('updates the user details and creates the delegate request', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords()->firstOrFail();

    $delegate = app(SubmitDelegateSeatRequest::class)->handle($user, [
        'organization_id' => $collegeOrganization->organization_id,
        'seat_id' => $seat->id,
        'email' => 'camille@afges.org',
        'started_at' => '2026-01-05',
        'last_name' => 'Durand',
        'first_name' => 'Camille',
        'phone' => '0601020304',
        'ignored' => 'value',
    ]);

    expect($user->fresh())
        ->last_name->toBe('Durand')
        ->phone->toBe('0601020304')
        ->and($delegate->user_id)->toBe($user->id)
        ->and($delegate->seat_id)->toBe($seat->id)
        ->and($delegate->email)->toBe('camille@afges.org');
});

it('rolls back the user update when the delegate cannot be created', function () {
    $user = User::factory()->create(['last_name' => 'Original']);

    expect(fn () => app(SubmitDelegateSeatRequest::class)->handle($user, [
        'organization_id' => 'missing',
        'seat_id' => 'missing',
        'email' => 'x@afges.org',
        'started_at' => '2026-01-05',
        'last_name' => 'Changed',
    ]))->toThrow(QueryException::class);

    expect($user->fresh()->last_name)->toBe('Original')
        ->and(Delegate::query()->count())->toBe(0);
});
