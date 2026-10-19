<?php

declare(strict_types=1);

use App\Actions\SubmitVolunteerRequests;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Validation\ValidationException;

it('creates pending requests', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    app(SubmitVolunteerRequests::class)->handle($user, [
        ['organization_id' => $organization->id, 'position_title' => 'Trésorier', 'started_at' => '2026-01-05'],
    ]);

    $volunteer = Volunteer::query()->withoutGlobalScopes()->where('user_id', $user->id)->sole();
    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->isPending())->toBeTrue();
});

it('rejects a new organization while another request is pending, even outside the form', function () {
    $user = User::factory()->create();
    Volunteer::factory()->pending()->for($user)->create();
    $other = Organization::factory()->create();

    expect(fn () => app(SubmitVolunteerRequests::class)->handle($user, [
        ['organization_id' => $other->id, 'position_title' => 'Trésorier'],
    ]))->toThrow(ValidationException::class);

    expect(Volunteer::query()->withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects two new organizations in one submission', function () {
    $user = User::factory()->create();
    [$first, $second] = Organization::factory()->count(2)->create();

    expect(fn () => app(SubmitVolunteerRequests::class)->handle($user, [
        ['organization_id' => $first->id, 'position_title' => 'Trésorier'],
        ['organization_id' => $second->id, 'position_title' => 'Trésorier'],
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('volunteers', 0);
});
