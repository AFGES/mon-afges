<?php

declare(strict_types=1);

use App\Filament\Pages\RequestDelegateSeat;
use App\Filament\Widgets\DelegateRequestWidget;
use App\Models\CollegeOrganization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Pages\Dashboard;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function eligibleWidgetUser(): User
{
    $user = User::factory()->create(['email' => 'member@afges.org']);
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        'ended_at' => null,
    ]);

    return $user;
}

it('is visible to a user who can request a delegate seat', function () {
    actingAs(eligibleWidgetUser());

    expect(DelegateRequestWidget::canView())->toBeTrue();
});

it('is hidden from a user who cannot request a delegate seat', function () {
    actingAs(User::factory()->create());

    expect(DelegateRequestWidget::canView())->toBeFalse();
});

it('links to the request page', function () {
    actingAs(eligibleWidgetUser());

    livewire(DelegateRequestWidget::class)
        ->assertSee(__('delegate_form.widget.heading'))
        ->assertSee(RequestDelegateSeat::getUrl());
});

it('is rendered on the dashboard for an eligible user', function () {
    actingAs(eligibleWidgetUser());

    livewire(Dashboard::class)->assertSeeLivewire(DelegateRequestWidget::class);
});
