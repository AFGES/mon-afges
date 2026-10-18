<?php

declare(strict_types=1);

use App\Filament\Resources\Volunteers\Widgets\PendingVolunteerStats;
use App\Models\User;
use App\Models\Volunteer;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function actingAsUserAllowedToViewPendingVolunteerStats(): User
{
    Permission::firstOrCreate(['name' => 'View:PendingVolunteerStats', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('View:PendingVolunteerStats');

    actingAs($user);

    return $user;
}

function pendingVolunteerStatValue(PendingVolunteerStats $widget): int
{
    return (fn (): array => $this->getStats())->call($widget)[0]->getValue();
}

it('counts only pending volunteers', function () {
    actingAsUserAllowedToViewPendingVolunteerStats();
    Volunteer::factory()->count(2)->pending()->create();
    Volunteer::factory()->count(3)->approved()->create();
    Volunteer::factory()->rejected()->create();

    $component = livewire(PendingVolunteerStats::class)
        ->assertSee(__('volunteer.stats.pending_approvals'));

    /** @var PendingVolunteerStats $widget */
    $widget = $component->instance();

    expect(pendingVolunteerStatValue($widget))->toBe(2);
});

it('shows zero when no volunteer is pending', function () {
    actingAsUserAllowedToViewPendingVolunteerStats();
    Volunteer::factory()->count(2)->approved()->create();

    $component = livewire(PendingVolunteerStats::class);

    /** @var PendingVolunteerStats $widget */
    $widget = $component->instance();

    expect(pendingVolunteerStatValue($widget))->toBe(0);
});

it('is visible to users with the View:PendingVolunteerStats permission', function () {
    actingAsUserAllowedToViewPendingVolunteerStats();

    expect(PendingVolunteerStats::canView())->toBeTrue();
});

it('is hidden from users without the View:PendingVolunteerStats permission', function () {
    actingAs(User::factory()->create());

    expect(PendingVolunteerStats::canView())->toBeFalse();
});

it('does not count soft-deleted pending volunteers', function () {
    actingAsUserAllowedToViewPendingVolunteerStats();
    Volunteer::factory()->pending()->create();
    Volunteer::factory()->pending()->create()->delete();

    $component = livewire(PendingVolunteerStats::class);

    /** @var PendingVolunteerStats $widget */
    $widget = $component->instance();

    expect(pendingVolunteerStatValue($widget))->toBe(1);
});
