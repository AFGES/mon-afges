<?php

declare(strict_types=1);

use App\Actions\SubmitDelegateSeatRequest;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

function makeVolunteerOf(User $user, CollegeOrganization $collegeOrganization, array $attributes = []): Volunteer
{
    return Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        ...$attributes,
    ]);
}

it('updates the user details and creates the delegate request', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords()->firstOrFail();
    makeVolunteerOf($user, $collegeOrganization);

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
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    makeVolunteerOf($user, $collegeOrganization);

    expect(fn () => app(SubmitDelegateSeatRequest::class)->handle($user, [
        'organization_id' => $collegeOrganization->organization_id,
        'seat_id' => 'missing',
        'email' => 'x@afges.org',
        'started_at' => '2026-01-05',
        'last_name' => 'Changed',
    ]))->toThrow(QueryException::class);

    expect($user->fresh()->last_name)->toBe('Original')
        ->and(Delegate::query()->count())->toBe(0);
});

it('rejects a new request while another one is pending', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->withSeats(2)->create();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);

    expect(fn () => app(SubmitDelegateSeatRequest::class)->handle($user, [
        'organization_id' => $collegeOrganization->organization_id,
        'seat_id' => $collegeOrganization->seatRecords()->firstOrFail()->id,
        'email' => 'x@afges.org',
        'started_at' => '2026-01-05',
    ]))->toThrow(ValidationException::class);

    expect(Delegate::query()->withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
});

it('refuses an organization the user is no longer an eligible volunteer of', function () {
    $user = User::factory()->create(['last_name' => 'Original']);
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    makeVolunteerOf($user, $collegeOrganization, ['started_at' => now()->subYear(), 'ended_at' => now()->subDay()]);

    expect(fn () => app(SubmitDelegateSeatRequest::class)->handle($user, [
        'organization_id' => $collegeOrganization->organization_id,
        'seat_id' => $collegeOrganization->seatRecords()->firstOrFail()->id,
        'email' => 'x@afges.org',
        'started_at' => '2026-01-05',
        'last_name' => 'Changed',
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->last_name)->toBe('Original')
        ->and(Delegate::query()->withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(0);
});
