<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\User;
use App\Notifications\ProxyApprovedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

it('is active only within its period', function (?string $startedAt, ?string $endedAt, string $checkedAt, bool $expected): void {
    $proxy = Proxy::factory()->create(['started_at' => $startedAt, 'ended_at' => $endedAt]);

    expect($proxy->isActiveAt(Carbon::parse($checkedAt)))->toBe($expected);
})->with([
    'full session' => [null, null, '2026-03-10 12:00:00', true],
    'no start, before end' => [null, '2026-03-10 14:00:00', '2026-03-10 12:00:00', true],
    'no start, at end' => [null, '2026-03-10 14:00:00', '2026-03-10 14:00:00', true],
    'no start, after end' => [null, '2026-03-10 14:00:00', '2026-03-10 14:01:00', false],
    'no end, before start' => ['2026-03-10 13:00:00', null, '2026-03-10 12:59:00', false],
    'no end, at start' => ['2026-03-10 13:00:00', null, '2026-03-10 13:00:00', true],
    'no end, after start' => ['2026-03-10 13:00:00', null, '2026-03-10 13:01:00', true],
    'between, before start' => ['2026-03-10 13:00:00', '2026-03-10 15:00:00', '2026-03-10 12:30:00', false],
    'between, inside' => ['2026-03-10 13:00:00', '2026-03-10 15:00:00', '2026-03-10 14:00:00', true],
    'between, after end' => ['2026-03-10 13:00:00', '2026-03-10 15:00:00', '2026-03-10 15:01:00', false],
]);

it('checks activity against the current time by default', function (): void {
    $proxy = Proxy::factory()->create(['started_at' => '2026-03-10 13:00:00']);

    expect($proxy->isActiveAt())->toBeFalse();

    $this->travelTo('2026-03-10 13:00:00');

    expect($proxy->isActiveAt())->toBeTrue();
});

it('detects seats from the same college', function (): void {
    $collegeOrganization = CollegeOrganization::factory()->create();
    $proxy = Proxy::factory()
        ->for(Seat::factory()->for($collegeOrganization), 'delegatorSeat')
        ->for(Seat::factory()->for($collegeOrganization), 'proxyHolderSeat')
        ->create();

    expect($proxy->areSeatsFromSameCollege())->toBeTrue();
});

it('detects seats from different colleges', function (): void {
    $proxy = Proxy::factory()->create();

    expect($proxy->areSeatsFromSameCollege())->toBeFalse();
});

it('is approved with the approver and the approval time', function (): void {
    $approver = User::factory()->create();
    $proxy = Proxy::factory()->create();

    $result = $proxy->approve($approver->id);

    $proxy->refresh();
    expect($result)->toBeTrue()
        ->and($proxy->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($proxy->approved_by)->toBe($approver->id)
        ->and($proxy->approved_at->toDateTimeString())->toBe('2026-03-10 12:00:00');
});

it('is rejected with the reason, the rejecter and the rejection time', function (): void {
    $rejecter = User::factory()->create();
    $proxy = Proxy::factory()->create();

    $result = $proxy->reject('Invalid proxy request', $rejecter->id);

    $proxy->refresh();
    expect($result)->toBeTrue()
        ->and($proxy->approval_status)->toBe(ApprovalStatus::Rejected)
        ->and($proxy->approved_by)->toBe($rejecter->id)
        ->and($proxy->approved_at->toDateTimeString())->toBe('2026-03-10 12:00:00')
        ->and($proxy->rejection_reason)->toBe('Invalid proxy request');
});

it('returns false when approving an already approved proxy', function (): void {
    $proxy = Proxy::factory()->approved()->create();

    expect($proxy->approve(User::factory()->create()->id))->toBeFalse();
});

it('returns false when rejecting an already rejected proxy', function (): void {
    $proxy = Proxy::factory()->rejected()->create();

    expect($proxy->reject('Another reason', User::factory()->create()->id))->toBeFalse();
});

it('only returns approved proxies by default', function (): void {
    $approvedProxy = Proxy::factory()->approved()->create();
    Proxy::factory()->create();
    Proxy::factory()->rejected()->create();

    expect(Proxy::pluck('id')->all())->toBe([$approvedProxy->id]);
});

it('counts pending proxies in the navigation badge', function (): void {
    Proxy::factory()->count(2)->create();
    Proxy::factory()->approved()->create();

    expect(Proxy::pendingCount())->toBe(2);
});

describe('approval notification', function (): void {
    it('is sent to the delegates currently seated on the delegator seat', function (): void {
        Notification::fake([ProxyApprovedNotification::class]);
        $proxy = Proxy::factory()->create();
        $currentUser = User::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->for($currentUser)->create([
            'started_at' => '2026-03-09 12:00:00',
            'ended_at' => null,
        ]);

        $proxy->approve();

        Notification::assertSentTo($currentUser, ProxyApprovedNotification::class, fn (ProxyApprovedNotification $notification): bool => $notification->proxy->is($proxy));
    });

    it('is not sent to a delegate who left the seat', function (): void {
        Notification::fake([ProxyApprovedNotification::class]);
        $proxy = Proxy::factory()->create();
        $formerUser = User::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->for($formerUser)->create([
            'started_at' => '2026-03-01 12:00:00',
            'ended_at' => '2026-03-09 12:00:00',
        ]);

        $proxy->approve();

        Notification::assertNotSentTo($formerUser, ProxyApprovedNotification::class);
    });

    it('is not sent to a delegate who has not started yet', function (): void {
        Notification::fake([ProxyApprovedNotification::class]);
        $proxy = Proxy::factory()->create();
        $futureUser = User::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->for($futureUser)->create([
            'started_at' => '2026-03-11 12:00:00',
            'ended_at' => null,
        ]);

        $proxy->approve();

        Notification::assertNotSentTo($futureUser, ProxyApprovedNotification::class);
    });
});

it('is deleted with its session', function (): void {
    $session = DemocraticTimeSession::factory()->create();
    $proxy = Proxy::factory()->for($session)->create();

    $session->delete();

    $this->assertModelMissing($proxy);
});

it('is deleted with its delegator seat', function (): void {
    $proxy = Proxy::factory()->create();

    $proxy->delegatorSeat->forceDelete();

    $this->assertModelMissing($proxy);
});

it('is deleted with its proxy holder seat', function (): void {
    $proxy = Proxy::factory()->create();

    $proxy->proxyHolderSeat->forceDelete();

    $this->assertModelMissing($proxy);
});
