<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets\SessionQuorumStatusWidget;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\Seat;
use App\Models\SessionSignIn;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForSessionQuorumStatusWidget(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

/**
 * @return array{session: DemocraticTimeSession, seats: Illuminate\Support\Collection<int, Seat>}
 */
function sessionWithFourVotingSeatsAndQuorumOfFiftyPercent(): array
{
    $college = College::factory()->create(['can_vote' => true]);
    $collegeOrganization = CollegeOrganization::factory()->create(['college_id' => $college->id]);
    $seats = Seat::factory()->count(4)->sequence(
        ['seat_number' => 1],
        ['seat_number' => 2],
        ['seat_number' => 3],
        ['seat_number' => 4],
    )->create(['college_organization_id' => $collegeOrganization->id]);
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50,
        'include_proxies_in_quorum' => false,
    ]);
    $session = DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);

    return ['session' => $session, 'seats' => $seats];
}

function signInSeatsToSession(DemocraticTimeSession $session, Illuminate\Support\Collection $seats): void
{
    foreach ($seats as $seat) {
        SessionSignIn::forceCreate([
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => Delegate::factory()->approved()->for($seat)->create()->id,
            'signed_in_at' => now(),
        ]);
    }
}

it('renders the widget', function () {
    actingAsSuperAdminForSessionQuorumStatusWidget();

    ['session' => $session] = sessionWithFourVotingSeatsAndQuorumOfFiftyPercent();

    livewire(SessionQuorumStatusWidget::class, ['record' => $session])
        ->assertOk();
});

it('shows quorum met with the attendance percentage when half of the seats are signed in', function () {
    actingAsSuperAdminForSessionQuorumStatusWidget();
    ['session' => $session, 'seats' => $seats] = sessionWithFourVotingSeatsAndQuorumOfFiftyPercent();
    signInSeatsToSession($session, $seats->take(2));

    $heading = livewire(SessionQuorumStatusWidget::class, ['record' => $session])
        ->instance()
        ->getHeading();

    expect($heading)->toBe(__('emargement.quorum.met').' - 50%');
});

it('shows quorum not met with the attendance percentage when too few seats are signed in', function () {
    actingAsSuperAdminForSessionQuorumStatusWidget();
    ['session' => $session, 'seats' => $seats] = sessionWithFourVotingSeatsAndQuorumOfFiftyPercent();
    signInSeatsToSession($session, $seats->take(1));

    $heading = livewire(SessionQuorumStatusWidget::class, ['record' => $session])
        ->instance()
        ->getHeading();

    expect($heading)->toBe(__('emargement.quorum.not_met').' - 25%');
});

it('has no heading without a record', function () {
    actingAsSuperAdminForSessionQuorumStatusWidget();

    $heading = livewire(SessionQuorumStatusWidget::class)
        ->instance()
        ->getHeading();

    expect($heading)->toBeNull();
});
