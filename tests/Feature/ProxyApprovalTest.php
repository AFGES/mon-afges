<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Organization;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use App\Notifications\ProxyApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('new proxy is pending by default', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
    ]);

    $seat1 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $seat2 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    $session = DemocraticTimeSession::factory()->create();

    $proxy = Proxy::factory()->create([
        'delegator_seat_id' => $seat1->id,
        'proxy_holder_seat_id' => $seat2->id,
        'democratic_time_session_id' => $session->id,
    ]);

    expect($proxy->approval_status)->toBe(ApprovalStatus::Pending);
    expect($proxy->isPending())->toBeTrue();
    expect($proxy->isApproved())->toBeFalse();
    expect($proxy->isRejected())->toBeFalse();
});

test('proxy can be approved', function () {
    $user = User::factory()->create();
    $proxy = Proxy::factory()->create();

    $proxy->approve($user->id);

    expect($proxy->fresh()->approval_status)->toBe(ApprovalStatus::Approved);
    expect($proxy->isApproved())->toBeTrue();
    expect($proxy->approved_by)->toBe($user->id);
    expect($proxy->approved_at)->not->toBeNull();
});

test('proxy can be rejected', function () {
    $user = User::factory()->create();
    $proxy = Proxy::factory()->create();

    $proxy->reject('Invalid proxy request', $user->id);

    expect($proxy->fresh()->approval_status)->toBe(ApprovalStatus::Rejected);
    expect($proxy->isRejected())->toBeTrue();
    expect($proxy->approved_by)->toBe($user->id);
    expect($proxy->rejection_reason)->toBe('Invalid proxy request');
});

test('approved proxy factory state creates approved proxy', function () {
    $proxy = Proxy::factory()->approved()->create();

    expect($proxy->approval_status)->toBe(ApprovalStatus::Approved);
    expect($proxy->isApproved())->toBeTrue();
    expect($proxy->approved_by)->not->toBeNull();
    expect($proxy->approved_at)->not->toBeNull();
});

test('rejected proxy factory state creates rejected proxy', function () {
    $proxy = Proxy::factory()->rejected('Test rejection')->create();

    expect($proxy->approval_status)->toBe(ApprovalStatus::Rejected);
    expect($proxy->isRejected())->toBeTrue();
    expect($proxy->approved_by)->not->toBeNull();
    expect($proxy->rejection_reason)->toBe('Test rejection');
});

test('approved scope filters only approved proxies', function () {
    Proxy::factory()->approved()->create();
    Proxy::factory()->create(); // pending
    Proxy::factory()->rejected()->create();

    $approvedProxies = Proxy::all();

    expect($approvedProxies)->toHaveCount(1);
    expect($approvedProxies->first()->isApproved())->toBeTrue();
});

test('can query pending proxies without global scope', function () {
    Proxy::factory()->approved()->create();
    Proxy::factory()->create(); // pending
    Proxy::factory()->rejected()->create();

    $pendingProxies = Proxy::withoutGlobalScopes()
        ->where('approval_status', ApprovalStatus::Pending)
        ->get();

    expect($pendingProxies)->toHaveCount(1);
    expect($pendingProxies->first()->isPending())->toBeTrue();
});

test('cannot approve an already approved proxy', function () {
    $user = User::factory()->create();
    $proxy = Proxy::factory()->approved()->create();

    $result = $proxy->approve($user->id);

    expect($result)->toBeFalse();
});

test('cannot reject an already rejected proxy', function () {
    $user = User::factory()->create();
    $proxy = Proxy::factory()->rejected()->create();

    $result = $proxy->reject('Another reason', $user->id);

    expect($result)->toBeFalse();
});

test('notification is sent to approvers when proxy is created', function () {
    Notification::fake();

    $permission = \Spatie\Permission\Models\Permission::create(['name' => 'Update:Proxy']);
    $approver = User::factory()->create();
    $approver->givePermissionTo($permission);

    $proxy = Proxy::factory()->create();

    Notification::assertSentTo(
        [$approver],
        ProxyApprovalNotification::class,
        function ($notification) use ($proxy) {
            return $notification->proxy->id === $proxy->id;
        }
    );
});

test('notification is not sent when no approvers exist', function () {
    Notification::fake();

    Proxy::factory()->create();

    Notification::assertNothingSent();
});

test('notification is sent to delegator when proxy is approved', function () {
    Notification::fake();

    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
    ]);

    $delegatorSeat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $proxyHolderSeat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'seat_id' => $delegatorSeat->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    $session = DemocraticTimeSession::factory()->create();
    $proxy = Proxy::factory()->create([
        'delegator_seat_id' => $delegatorSeat->id,
        'proxy_holder_seat_id' => $proxyHolderSeat->id,
        'democratic_time_session_id' => $session->id,
    ]);

    $approver = User::factory()->create();
    $proxy->approve($approver->id);

    Notification::assertSentTo(
        [$user],
        ProxyApprovedNotification::class,
        function ($notification) use ($proxy) {
            return $notification->proxy->id === $proxy->id;
        }
    );
});

test('proxy navigation badge shows pending count', function () {
    Proxy::factory()->count(3)->create(); // pending
    Proxy::factory()->approved()->count(2)->create();

    $badge = Proxy::getNavigationBadge();

    expect($badge)->toBe('3');
});
