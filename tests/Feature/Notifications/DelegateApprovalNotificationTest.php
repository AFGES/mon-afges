<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequested;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Notification::fake();

    // Create the permission if it doesn't exist
    Permission::firstOrCreate(['name' => 'Update:Delegate', 'guard_name' => 'web']);
});

test('notification is sent to approvers when delegate is created', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');

    $delegate = Delegate::factory()->create();

    Notification::assertSentTo(
        [$approver],
        DelegateApprovalRequested::class,
        function ($notification, $channels) use ($delegate) {
            return $notification->delegate->id === $delegate->id;
        }
    );
});

test('notification is not sent when no approvers exist', function () {
    $delegate = Delegate::factory()->create();

    Notification::assertNothingSent();
});

test('notification is sent to multiple approvers', function () {
    $approver1 = User::factory()->create();
    $approver1->givePermissionTo('Update:Delegate');

    $approver2 = User::factory()->create();
    $approver2->givePermissionTo('Update:Delegate');

    $delegate = Delegate::factory()->create();

    Notification::assertSentTo([$approver1, $approver2], DelegateApprovalRequested::class);
});

test('notification email contains correct delegate information', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');

    $delegate = Delegate::factory()->create();

    Notification::assertSentTo(
        [$approver],
        DelegateApprovalRequested::class,
        function ($notification) use ($delegate) {
            $mailMessage = $notification->toMail($delegate->user);

            return str_contains($mailMessage->introLines[1], $delegate->user->name)
                && str_contains($mailMessage->introLines[2], $delegate->organization->short_name);
        }
    );
});

test('notification is queued for background processing', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');

    $delegate = Delegate::factory()->create();

    Notification::assertSentTo($approver, DelegateApprovalRequested::class);
});

test('notification includes action link to approve delegate', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');

    $delegate = Delegate::factory()->create();

    Notification::assertSentTo(
        [$approver],
        DelegateApprovalRequested::class,
        function ($notification) use ($delegate) {
            $mailMessage = $notification->toMail($delegate->user);

            return str_contains(
                $mailMessage->actionUrl,
                route('delegates.approve', ['delegate' => $delegate->id], false)
            );
        }
    );
});

test('notification details link points to the delegate edit page', function () {
    $delegate = Delegate::factory()->create();

    $mailMessage = (new DelegateApprovalRequested($delegate))->toMail($delegate->user);

    $expectedUrl = DelegateResource::getUrl('edit', ['record' => $delegate]);

    expect(collect($mailMessage->outroLines)->implode(' '))->toContain($expectedUrl);
});
