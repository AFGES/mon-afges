<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Seat;
use App\Models\User;
use App\Services\DelegateApprovalService;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    mockGoogleWorkspaceService()->shouldIgnoreMissing();
});

function seatWithCapacityForApprovalService(int $delegatesPerSeat): Seat
{
    return Seat::factory()->create([
        'college_organization_id' => CollegeOrganization::factory()->create(['delegates_per_seat' => $delegatesPerSeat])->id,
    ]);
}

it('ends the selected delegates on the start date of the approved delegate', function () {
    $seat = seatWithCapacityForApprovalService(2);
    $kept = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $terminated = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->subDay()->startOfDay()]);

    (new DelegateApprovalService)->approve($pending, [$terminated->id]);

    expect($pending->refresh()->isApproved())->toBeTrue()
        ->and(Delegate::withoutGlobalScopes()->find($terminated->id)->ended_at->toDateString())->toBe(now()->subDay()->toDateString())
        ->and(Delegate::withoutGlobalScopes()->find($kept->id)->ended_at)->toBeNull();
});

it('ends nobody when no delegate is selected and the seat has room', function () {
    $seat = seatWithCapacityForApprovalService(2);
    $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    (new DelegateApprovalService)->approve($pending, []);

    expect($pending->refresh()->isApproved())->toBeTrue()
        ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
});

it('ignores delegates of another seat, already ended delegates and the delegate itself', function () {
    $seat = seatWithCapacityForApprovalService(1);
    $otherSeatDelegate = Delegate::factory()->approved()->create(['started_at' => now()->subYear(), 'ended_at' => null]);
    $alreadyEnded = Delegate::factory()->approved()->create([
        'seat_id' => $seat->id,
        'started_at' => now()->subYears(2),
        'ended_at' => now()->subYear()->startOfDay(),
    ]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->subDay()->startOfDay(), 'ended_at' => null]);

    (new DelegateApprovalService)->approve($pending, [$otherSeatDelegate->id, $alreadyEnded->id, $pending->id]);

    expect($pending->refresh())->isApproved()->toBeTrue()
        ->and($pending->ended_at)->toBeNull()
        ->and(Delegate::withoutGlobalScopes()->find($otherSeatDelegate->id)->ended_at)->toBeNull()
        ->and(Delegate::withoutGlobalScopes()->find($alreadyEnded->id)->ended_at->toDateString())->toBe(now()->subYear()->toDateString());
});

it('lists the current approved delegates of the seat excluding the given delegate', function () {
    $seat = Seat::factory()->create();
    $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYears(3), 'ended_at' => now()->subYear()]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    $result = (new DelegateApprovalService)->currentDelegatesOfSeat($pending);

    expect($result->modelKeys())->toBe([$current->id]);
});

it('refuses to keep current delegates when the seat capacity of the college does not permit it', function () {
    $seat = seatWithCapacityForApprovalService(1);
    $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    expect(fn () => (new DelegateApprovalService)->approve($pending, []))
        ->toThrow(ValidationException::class);

    expect($pending->refresh()->isPending())->toBeTrue()
        ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
});

it('computes how many current delegates must end to fit the seat capacity', function (int $capacity, int $currentCount, int $expected) {
    $seat = seatWithCapacityForApprovalService($capacity);
    Delegate::factory()->approved()->count($currentCount)->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    expect((new DelegateApprovalService)->minimumTerminations($pending))->toBe($expected);
})->with([
    'empty seat' => [1, 0, 0],
    'room left' => [3, 2, 0],
    'full seat' => [2, 2, 1],
    'over capacity' => [1, 3, 3],
]);

describe('createApproved', function () {
    it('creates the delegate already approved by the authenticated user', function () {
        $admin = User::factory()->create();
        actingAs($admin);
        $seat = seatWithCapacityForApprovalService(1);

        $delegate = (new DelegateApprovalService)->createApproved([
            'user_id' => User::factory()->create()->id,
            'organization_id' => $seat->collegeOrganization->organization_id,
            'seat_id' => $seat->id,
            'email' => 'new-delegate@example.org',
            'started_at' => now()->toDateString(),
        ]);

        expect($delegate->exists)->toBeTrue()
            ->and($delegate->refresh()->isApproved())->toBeTrue()
            ->and($delegate->approved_by)->toBe($admin->id)
            ->and($delegate->approved_at)->not->toBeNull();
    });

    it('ends the selected delegates on the start date of the new delegate', function () {
        $seat = seatWithCapacityForApprovalService(1);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);

        $delegate = (new DelegateApprovalService)->createApproved([
            'user_id' => User::factory()->create()->id,
            'organization_id' => $seat->collegeOrganization->organization_id,
            'seat_id' => $seat->id,
            'email' => 'new-delegate@example.org',
            'started_at' => now()->subDay()->toDateString(),
        ], [$current->id]);

        expect($delegate->refresh()->isApproved())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at->toDateString())->toBe(now()->subDay()->toDateString());
    });

    it('refuses to create the delegate when the seat is full and nobody is ended', function () {
        $seat = seatWithCapacityForApprovalService(1);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);

        expect(fn () => (new DelegateApprovalService)->createApproved([
            'user_id' => User::factory()->create()->id,
            'organization_id' => $seat->collegeOrganization->organization_id,
            'seat_id' => $seat->id,
            'email' => 'new-delegate@example.org',
            'started_at' => now()->toDateString(),
        ]))->toThrow(ValidationException::class);

        expect(Delegate::withoutGlobalScopes()->where('email', 'new-delegate@example.org')->exists())->toBeFalse()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
    });
});

describe('capacity over the whole period', function () {
    it('counts approved delegates whose mandate starts in the future', function () {
        $seat = seatWithCapacityForApprovalService(1);
        $future = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonth()->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);
        $service = new DelegateApprovalService;

        expect($service->minimumTerminations($pending))->toBe(1)
            ->and($service->currentDelegatesOfSeat($pending)->modelKeys())->toBe([$future->id])
            ->and(fn () => $service->approve($pending, []))->toThrow(ValidationException::class);
    });

    it('ignores approved delegates whose mandate does not overlap the period', function () {
        $seat = seatWithCapacityForApprovalService(1);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addYear()->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => now()->addMonth()->startOfDay()]);

        expect((new DelegateApprovalService)->minimumTerminations($pending))->toBe(0);
    });

    it('uses the peak of concurrent delegates so sequential mandates do not add up', function () {
        $seat = seatWithCapacityForApprovalService(2);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subMonths(6)->startOfDay(), 'ended_at' => now()->addMonth()->startOfDay()]);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonths(2)->startOfDay(), 'ended_at' => now()->addMonths(4)->startOfDay()]);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonths(5)->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);

        expect((new DelegateApprovalService)->minimumTerminations($pending))->toBe(0);
    });

    it('reports the excess when a mandate overlaps the busiest moment', function () {
        $seat = seatWithCapacityForApprovalService(2);
        Delegate::factory()->approved()->count(2)->create(['seat_id' => $seat->id, 'started_at' => now()->addMonth()->startOfDay(), 'ended_at' => null]);

        $violation = (new DelegateApprovalService)->capacityViolation($seat->id, now()->startOfDay(), null);

        expect($violation)->toBe(1);
    });

    it('never ends a delegate before it started', function () {
        $seat = seatWithCapacityForApprovalService(1);
        $future = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonth()->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);

        (new DelegateApprovalService)->approve($pending, [$future->id]);

        $future = Delegate::withoutGlobalScopes()->find($future->id);

        expect($future->ended_at->toDateString())->toBe($future->started_at->toDateString());
    });

    it('ends every overlapping delegate except the protected ones', function () {
        $seat = seatWithCapacityForApprovalService(2);
        $protected = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $other = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);

        (new DelegateApprovalService)->approveAndTerminateCurrentDelegates($pending, [$protected->id]);

        expect(Delegate::withoutGlobalScopes()->find($protected->id)->ended_at)->toBeNull()
            ->and(Delegate::withoutGlobalScopes()->find($other->id)->ended_at)->not->toBeNull();
    });

    it('refuses when the seat cannot fit the delegate without ending a protected one', function () {
        $seat = seatWithCapacityForApprovalService(1);
        $protected = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);

        expect(fn () => (new DelegateApprovalService)->approveAndTerminateCurrentDelegates($pending, [$protected->id]))
            ->toThrow(ValidationException::class);

        expect($pending->refresh()->isPending())->toBeTrue();
    });
});
