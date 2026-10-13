<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequestedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

describe('approver notification', function () {
    it('notifies every user allowed to update volunteers when a volunteer is created', function () {
        Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
        $firstApprover = User::factory()->create()->givePermissionTo('Update:Volunteer');
        $secondApprover = User::factory()->create()->givePermissionTo('Update:Volunteer');
        Notification::fake([VolunteerApprovalRequestedNotification::class]);

        $volunteer = Volunteer::factory()->create();

        Notification::assertSentTo(
            [$firstApprover, $secondApprover],
            VolunteerApprovalRequestedNotification::class,
            fn (VolunteerApprovalRequestedNotification $notification): bool => $notification->volunteer->is($volunteer),
        );
    });

    it('does not notify a user without the update permission', function () {
        Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
        $userWithoutPermission = User::factory()->create();
        Notification::fake([VolunteerApprovalRequestedNotification::class]);

        Volunteer::factory()->create();

        Notification::assertNotSentTo($userWithoutPermission, VolunteerApprovalRequestedNotification::class);
    });

    it('sends nothing when nobody holds the update permission', function () {
        Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
        Notification::fake([VolunteerApprovalRequestedNotification::class]);

        Volunteer::factory()->create();

        Notification::assertNothingSent();
    });

    it('sends nothing when the update permission does not exist', function () {
        Notification::fake([VolunteerApprovalRequestedNotification::class]);

        $volunteer = Volunteer::factory()->create();

        $this->assertModelExists($volunteer);
        Notification::assertNothingSent();
    });
});
