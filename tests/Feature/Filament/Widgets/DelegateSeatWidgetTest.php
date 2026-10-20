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
use function Pest\Laravel\travelTo;
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
        ->assertActionVisible('changeSeat')
        ->assertDontSee(__('delegate_seat_widget.heading'));
});

it('warns a seat holder about the replacement before opening the request page', function () {
    [$user] = seatHolderWidgetUser();
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->mountAction('changeSeat')
        ->assertMountedActionModalSee(__('delegate_seat_widget.seat_holder.replacement_notice'))
        ->callMountedAction()
        ->assertRedirect(RequestDelegateSeat::getUrl());
});

it('hides the request link from a seat holder with a pending request', function () {
    [$user] = seatHolderWidgetUser();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(trans_choice('delegate_seat_widget.pending', 1, ['count' => 1]))
        ->assertActionHidden('changeSeat');
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

it('turns an upcoming seat into a zero-length seat that is no longer listed when terminated', function () {
    travelTo('2026-03-15 12:00:00');
    [$user, $delegate] = seatHolderWidgetUser();
    $delegate->update(['started_at' => '2026-04-01 09:00:00']);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.seat_holder.starts_on', ['date' => '01/04/2026']))
        ->callAction(TestAction::make('terminateSeat')->arguments(['delegate' => $delegate->id]))
        ->assertDontSee(__('delegate_seat_widget.seat_holder.starts_on', ['date' => '01/04/2026']));

    expect($delegate->refresh())
        ->ended_at->toDateTimeString()->toBe('2026-04-01 09:00:00')
        ->and($user->hasCurrentOrUpcomingDelegateSeat())->toBeFalse();

    travelTo('2026-04-01 09:00:00');

    expect($delegate->refresh()->isCurrentlyActive())->toBeFalse();
});

it('shows the end date of a seat that already has one, without a terminate action', function () {
    travelTo('2026-03-15 12:00:00');
    [$user, $delegate] = seatHolderWidgetUser();
    $delegate->update(['ended_at' => '2026-06-30']);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertSee(__('delegate_seat_widget.seat_holder.ends_on', ['date' => '30/06/2026']))
        ->assertActionHidden(TestAction::make('terminateSeat')->arguments(['delegate' => $delegate->id]));
});

it('no longer shows the default state to a former volunteer who ended their last seat', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create(['user_id' => $user->id, 'started_at' => now()->subMonth(), 'ended_at' => null]);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->callAction(TestAction::make('terminateSeat')->arguments(['delegate' => $delegate->id]))
        ->assertDontSee(__('delegate_seat_widget.heading'))
        ->assertDontSee(__('delegate_seat_widget.seat_holder.heading'));

    expect(DelegateSeatWidget::canView())->toBeFalse();
});

it('hides the terminate action without throwing when the delegate argument is missing or unknown', function (array $arguments) {
    actingAs(seatHolderWidgetUser()[0]);

    livewire(DelegateSeatWidget::class)
        ->assertActionHidden(TestAction::make('terminateSeat')->arguments($arguments));
})->with([
    'missing' => [[]],
    'unknown' => [['delegate' => '00000000-0000-0000-0000-000000000000']],
]);

it('does not end the mandate of another delegate when the action is forced', function () {
    [, $delegate] = seatHolderWidgetUser();
    actingAs(eligibleSeatWidgetUser('other@afges.org'));

    livewire(DelegateSeatWidget::class)
        ->call('mountAction', 'terminateSeat', ['delegate' => $delegate->id])
        ->call('callMountedAction');

    expect($delegate->refresh()->ended_at)->toBeNull();
});

it('renders a seat holder whose seat was removed', function () {
    [$user, $delegate] = seatHolderWidgetUser();
    $delegate->update(['seat_id' => null]);
    actingAs($user);

    livewire(DelegateSeatWidget::class)
        ->assertOk()
        ->assertSee(__('delegate_seat_widget.seat_holder.heading'));
});
