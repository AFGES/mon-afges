<?php

declare(strict_types=1);

use App\Actions\ImportLegacyDelegate;
use App\Enums\ApprovalStatus;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->importer = User::factory()->create();
    $this->organization = Organization::factory()->create(['short_name' => 'AFGES', 'legal_name' => 'Fédération Générale des Étudiants']);
    $this->college = College::factory()->general()->create();
    $this->collegeOrganization = CollegeOrganization::factory()->withSeats(2)->create([
        'organization_id' => $this->organization->id,
        'college_id' => $this->college->id,
        'delegates_per_seat' => 1,
    ]);

    $this->row = fn (array $overrides = []): array => [
        'email' => 'Camille.Durand@Example.org ',
        'first_name' => 'Camille',
        'last_name' => 'Durand',
        'organization' => 'AFGES',
        'college' => 'Général',
        'seat_number' => 1,
        'started_at' => '2025-09-01',
        ...$overrides,
    ];

    $this->import = fn (array $overrides = []): Delegate => app(ImportLegacyDelegate::class)->handle(($this->row)($overrides), $this->importer);

    $this->allDelegates = fn () => Delegate::query()->withoutGlobalScopes([CurrentlyScope::class, ApprovedScope::class]);
});

it('creates the missing user without password and unverified', function () {
    $delegate = ($this->import)(['phone' => '0601020304']);

    expect($delegate->user)
        ->email->toBe('camille.durand@example.org')
        ->first_name->toBe('Camille')
        ->phone->toBe('0601020304')
        ->password->toBeNull()
        ->email_verified_at->toBeNull();
});

it('reuses an existing user by case-insensitive email and only fills empty fields', function () {
    $user = User::factory()->create(['email' => 'camille.durand@example.org', 'first_name' => 'Cam', 'phone' => null]);

    $delegate = ($this->import)(['phone' => '0601020304', 'first_name' => 'Camille']);

    expect($delegate->user_id)->toBe($user->id)
        ->and(User::query()->where('email', 'camille.durand@example.org')->count())->toBe(1)
        ->and($user->fresh())
        ->first_name->toBe('Cam')
        ->phone->toBe('0601020304');
});

it('fails when a new user has no name and creates nothing', function () {
    expect(fn () => ($this->import)(['first_name' => null, 'last_name' => null]))
        ->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'camille.durand@example.org')->exists())->toBeFalse();
});

it('matches the organization by short name', function () {
    expect(($this->import)(['organization' => '  afges ']))->organization_id->toBe($this->organization->id);
});

it('matches the organization by legal name ignoring accents and case', function () {
    expect(($this->import)(['organization' => 'federation  generale des etudiants']))
        ->organization_id->toBe($this->organization->id);
});

it('matches the college ignoring accents and case', function () {
    expect(($this->import)(['college' => 'GENERAL']))->seat->collegeOrganization->college_id->toBe($this->college->id);
});

it('fails on a resolution miss and rolls back the created user', function (array $overrides, string $field) {
    try {
        ($this->import)($overrides);
        $this->fail('The row should have failed.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }

    expect(User::query()->where('email', 'camille.durand@example.org')->exists())->toBeFalse()
        ->and(($this->allDelegates)()->count())->toBe(0);
})->with([
    'unknown organization' => [['organization' => 'Unknown'], 'organization'],
    'unknown college' => [['college' => 'Unknown'], 'college'],
    'unknown seat number' => [['seat_number' => 99], 'seat_number'],
]);

it('fails when the organization has no seats in the college', function () {
    College::factory()->federal()->create();

    expect(fn () => ($this->import)(['college' => 'Fédéral']))->toThrow(ValidationException::class);
});

it('fails on an ambiguous organization name', function () {
    Organization::factory()->create(['short_name' => 'afges']);

    expect(fn () => ($this->import)())->toThrow(ValidationException::class);
});

it('creates an approved delegate approved by the importing user', function () {
    $delegate = ($this->import)(['started_at' => '2024-09-01', 'ended_at' => '2025-08-31']);

    expect($delegate->fresh())
        ->approval_status->toBe(ApprovalStatus::Approved)
        ->approved_by->toBe($this->importer->id)
        ->email->toBe('camille.durand@example.org')
        ->seat_id->toBe($this->collegeOrganization->seatRecords()->where('seat_number', 1)->value('id'))
        ->started_at->toDateString()->toBe('2024-09-01')
        ->ended_at->toDateTimeString()->toBe('2025-09-01 00:00:00');
});

it('fails the row when the seat capacity is exceeded', function () {
    ($this->import)();

    expect(fn () => ($this->import)(['email' => 'other@example.org', 'first_name' => 'Other', 'last_name' => 'Person']))
        ->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'other@example.org')->exists())->toBeFalse();
});

it('is idempotent when the same row is imported again', function () {
    $first = ($this->import)();
    $second = ($this->import)();

    expect($second->id)->toBe($first->id)
        ->and(($this->allDelegates)()->count())->toBe(1);
});

it('ends the other seat of the user when they take a new one', function () {
    $first = ($this->import)(['started_at' => '2024-09-01']);

    ($this->import)(['seat_number' => 2, 'started_at' => '2025-09-01']);

    expect($first->fresh()->ended_at->toDateString())->toBe('2025-09-01');
});
