<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

test('delegate user receives notification when approved', function () {
    Notification::fake();

    $approver = User::factory()->create();
    $delegateUser = User::factory()->create();
    $delegate = Delegate::withoutGlobalScopes()->create(
        Delegate::factory()->make(['user_id' => $delegateUser->id])->toArray()
    );

    actingAs($approver);

    $delegate->approve();

    Notification::assertSentTo(
        $delegateUser,
        ApprovalApprovedNotification::class,
        function (ApprovalApprovedNotification $notification) use ($delegate) {
            return $notification->approvable->is($delegate);
        }
    );
});

test('volunteer user receives notification when approved', function () {
    Notification::fake();

    $approver = User::factory()->create();
    $volunteerUser = User::factory()->create();
    $volunteer = Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make(['user_id' => $volunteerUser->id])->toArray()
    );

    actingAs($approver);

    $volunteer->approve();

    Notification::assertSentTo(
        $volunteerUser,
        ApprovalApprovedNotification::class,
        function (ApprovalApprovedNotification $notification) use ($volunteer) {
            return $notification->approvable->is($volunteer);
        }
    );
});

test('already approved delegate does not send duplicate notification', function () {
    Notification::fake();

    $approver = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create();

    actingAs($approver);

    $result = $delegate->approve();

    expect($result)->toBeFalse();
    Notification::assertNothingSent();
});

test('already approved volunteer does not send duplicate notification', function () {
    Notification::fake();

    $approver = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create();

    actingAs($approver);

    $result = $volunteer->approve();

    expect($result)->toBeFalse();
    Notification::assertNothingSent();
});

test('approval notification uses correct translation key for delegate', function () {
    $delegate = Delegate::factory()->make();
    $notification = new ApprovalApprovedNotification($delegate);

    $mailMessage = $notification->toMail(User::factory()->make(['name' => 'Test User']));

    expect($mailMessage->subject)->toBe(__('delegate.notifications.approval_approved.subject'));
});

test('approval notification uses correct translation key for volunteer', function () {
    $volunteer = Volunteer::factory()->make();
    $notification = new ApprovalApprovedNotification($volunteer);

    $mailMessage = $notification->toMail(User::factory()->make(['name' => 'Test User']));

    expect($mailMessage->subject)->toBe(__('volunteer.notifications.approval_approved.subject'));
});
