<?php

declare(strict_types=1);

use App\Filament\Pages\RequestDelegateSeat;
use App\Filament\Widgets\DelegateSeatWidget;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Actions\Testing\TestAction;
use Filament\Pages\Dashboard;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function eligibleSeatWidgetUser(string $email = 'member@afges.org'): User
{
    $user = User::factory()->create(['email' => $email]);
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        'ended_at' => null,
    ]);

    return $user;
}

it('is visible to a user who can request a delegate seat', function () {
    actingAs(eligibleSeatWidgetUser('other@afges.org'));

    expect(DelegateSeatWidget::canView())->toBeTrue();
});

it('is hidden from a user who cannot request a delegate seat', function () {
    actingAs(User::factory()->create());

    expect(DelegateSeatWidget::canView())->toBeFalse();
});

it('stays visible while a request is pending', function () {
    $user = eligibleSeatWidgetUser();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);
    actingAs($user);

    expect(DelegateSeatWidget::canView())->toBeTrue();
});

it('links to the request page', function () {
    actingAs(eligibleSeatWidgetUser('other@afges.org'));

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.heading'))
        ->assertSee(RequestDelegateSeat::getUrl());
});

it('is rendered on the dashboard for an eligible user', function () {
    actingAs(eligibleSeatWidgetUser('other@afges.org'));

    livewire(Dashboard::class)->assertSeeLivewire(DelegateSeatWidget::class);
});

it('does not count soft-deleted pending delegates', function () {
    $user = eligibleSeatWidgetUser();
    Delegate::factory()->pending()->create(['user_id' => $user->id])->delete();
    actingAs($user);

    $component = livewire(DelegateSeatWidget::class);

    /** @var DelegateSeatWidget $widget */
    $widget = $component->instance();

    expect((fn (): array => $this->getViewData())->call($widget)['pendingCount'])->toBe(0);
});

/**
 * @return array{0: User, 1: Delegate}
 */
function seatHolderWidgetUser(): array
{
    $user = eligibleSeatWidgetUser();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'started_at' => now()->subMonth(),
        'ended_at' => null,
    ]);

    return [$user, $delegate];
}

it('shows the seat-holder state to a user holding a seat', function () {
    [$user, $delegate] = seatHolderWidgetUser();
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.seat_holder.heading'))
        ->assertSee(__('profile.manage_delegates.seat_label', ['number' => $delegate->seat->seat_number]))
        ->assertSee(__('delegate_seat_widget.seat_holder.replacement_notice'))
        ->assertSee(__('delegate_seat_widget.seat_holder.action'))
        ->assertSee(RequestDelegateSeat::getUrl())
        ->assertDontSee(__('delegate_seat_widget.heading'));
});

it('hides the request link from a seat holder with a pending request', function () {
    [$user] = seatHolderWidgetUser();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(trans_choice('delegate_seat_widget.pending', 1, ['count' => 1]))
        ->assertDontSee(RequestDelegateSeat::getUrl());
});

it('shows only the pending note to a user without a seat who has a pending request', function () {
    $user = eligibleSeatWidgetUser();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.heading'))
        ->assertSee(trans_choice('delegate_seat_widget.pending', 1, ['count' => 1]))
        ->assertDontSee(RequestDelegateSeat::getUrl());
});

it('is visible to a seat holder who is no longer eligible', function () {
    $user = User::factory()->create();
    Delegate::factory()->approved()->create(['user_id' => $user->id, 'started_at' => now()->subMonth(), 'ended_at' => null]);
    actingAs($user);

    expect(DelegateSeatWidget::canView())->toBeTrue();
});

it('lets a seat holder end their mandate', function () {
    [$user, $delegate] = seatHolderWidgetUser();
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->callAction(TestAction::make('terminateSeat')->arguments(['delegate' => $delegate->id]))
        ->assertNotified(__('profile.manage_delegates.terminate.success_title'));

    expect($delegate->refresh()->ended_at)->not->toBeNull();

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.heading'))
        ->assertDontSee(__('delegate_seat_widget.seat_holder.heading'));
});

it('does not let a user end the mandate of another delegate', function () {
    [, $delegate] = seatHolderWidgetUser();
    actingAs(eligibleSeatWidgetUser('other@afges.org'));

    livewire(DelegateSeatWidget::class)
        ->assertActionHidden(TestAction::make('terminateSeat')->arguments(['delegate' => $delegate->id]));

    expect($delegate->refresh()->ended_at)->toBeNull();
});
