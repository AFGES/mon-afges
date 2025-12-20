<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequested;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Notification::fake();

    // Create the permission if it doesn't exist
    Permission::firstOrCreate(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
});

test('notification is sent to approvers when volunteer is created', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');

    $volunteer = Volunteer::factory()->create();

    Notification::assertSentTo(
        [$approver],
        VolunteerApprovalRequested::class,
        function ($notification, $channels) use ($volunteer) {
            return $notification->volunteer->id === $volunteer->id;
        }
    );
});

test('notification is not sent when no approvers exist', function () {
    $volunteer = Volunteer::factory()->create();

    Notification::assertNothingSent();
});

test('notification is sent to multiple approvers', function () {
    $approver1 = User::factory()->create();
    $approver1->givePermissionTo('Update:Volunteer');

    $approver2 = User::factory()->create();
    $approver2->givePermissionTo('Update:Volunteer');

    $volunteer = Volunteer::factory()->create();

    Notification::assertSentTo([$approver1, $approver2], VolunteerApprovalRequested::class);
});

test('notification email contains correct volunteer information', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');

    $volunteer = Volunteer::factory()->create();

    Notification::assertSentTo(
        [$approver],
        VolunteerApprovalRequested::class,
        function ($notification) use ($volunteer) {
            $mailMessage = $notification->toMail($volunteer->user);

            return str_contains($mailMessage->introLines[1], $volunteer->user->name)
                && str_contains($mailMessage->introLines[2], $volunteer->organization->short_name)
                && str_contains($mailMessage->introLines[3], $volunteer->position_title);
        }
    );
});

test('notification is queued for background processing', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');

    $volunteer = Volunteer::factory()->create();

    Notification::assertSentTo($approver, VolunteerApprovalRequested::class);
});

test('notification includes action link to approve volunteer', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');

    $volunteer = Volunteer::factory()->create();

    Notification::assertSentTo(
        [$approver],
        VolunteerApprovalRequested::class,
        function ($notification) use ($volunteer) {
            $mailMessage = $notification->toMail($volunteer->user);

            return str_contains(
                $mailMessage->actionUrl,
                route('volunteers.approve', ['volunteer' => $volunteer->id], false)
            );
        }
    );
});
