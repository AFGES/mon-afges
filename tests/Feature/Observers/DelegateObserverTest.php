<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequestedNotification;
use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use App\Services\GoogleWorkspaceServiceInterface;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;

function ignoreGoogleWorkspaceSync(): MockInterface
{
    return mock(DelegateGoogleWorkspaceSyncServiceInterface::class)->shouldIgnoreMissing();
}

/**
 * Binds a Google Workspace mock behind a fresh sync service, so the real observer logic runs against it.
 * Set the mock expectations only after the delegate under test is created.
 */
function googleWorkspaceForObserver(): GoogleWorkspaceServiceInterface&MockInterface
{
    $google = mockGoogleWorkspaceService();
    $google->shouldIgnoreMissing(true);
    app()->forgetInstance(DelegateGoogleWorkspaceSyncServiceInterface::class);

    return $google;
}

function eligibleDelegate(array $attributes = []): Delegate
{
    return Delegate::factory()->approved()->verified()->create([
        'email' => 'delegate@afges.org',
        'started_at' => now()->subMonth(),
        'ended_at' => null,
        ...$attributes,
    ]);
}

describe('approver notification', function () {
    beforeEach(function () {
        ignoreGoogleWorkspaceSync();
    });

    it('notifies every user allowed to update delegates when a delegate is created', function () {
        Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
        $firstApprover = User::factory()->create()->givePermissionTo('Update:Delegate');
        $secondApprover = User::factory()->create()->givePermissionTo('Update:Delegate');
        Notification::fake([DelegateApprovalRequestedNotification::class]);

        $delegate = Delegate::factory()->create();

        Notification::assertSentTo(
            [$firstApprover, $secondApprover],
            DelegateApprovalRequestedNotification::class,
            fn (DelegateApprovalRequestedNotification $notification): bool => $notification->delegate->is($delegate),
        );
    });

    it('does not notify approvers when the delegate is created already approved', function () {
        Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
        User::factory()->create()->givePermissionTo('Update:Delegate');
        Notification::fake([DelegateApprovalRequestedNotification::class]);

        Delegate::factory()->approved()->create();

        Notification::assertNothingSent();
    });

    it('does not notify a user without the update permission', function () {
        Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
        $userWithoutPermission = User::factory()->create();
        Notification::fake([DelegateApprovalRequestedNotification::class]);

        Delegate::factory()->create();

        Notification::assertNotSentTo($userWithoutPermission, DelegateApprovalRequestedNotification::class);
    });

    it('sends nothing when nobody holds the update permission', function () {
        Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
        Notification::fake([DelegateApprovalRequestedNotification::class]);

        Delegate::factory()->create();

        Notification::assertNothingSent();
    });

    it('sends nothing when the update permission does not exist', function () {
        Notification::fake([DelegateApprovalRequestedNotification::class]);

        $delegate = Delegate::factory()->create();

        $this->assertModelExists($delegate);
        Notification::assertNothingSent();
    });
});

describe('Google Workspace group sync', function () {
    it('adds an approved, verified, current delegate when created', function () {
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        eligibleDelegate();
    });

    it('does not add a pending delegate when created', function () {
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('addToGroup');

        Delegate::factory()->pending()->verified()->create(['email' => 'delegate@afges.org', 'started_at' => now()->subMonth(), 'ended_at' => null]);
    });

    it('does not add an approved delegate whose mandate has not started', function () {
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('addToGroup');

        eligibleDelegate(['started_at' => now()->addWeek()]);
    });

    it('adds a pending delegate once approved', function () {
        $delegate = Delegate::factory()->pending()->verified()->create(['email' => 'delegate@afges.org', 'started_at' => now()->subMonth(), 'ended_at' => null]);
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        $delegate->approve();
    });

    it('does not add a delegate approved before its mandate starts', function () {
        $delegate = Delegate::factory()->pending()->verified()->create(['email' => 'delegate@afges.org', 'started_at' => now()->addWeek(), 'ended_at' => null]);
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('addToGroup');
        $google->shouldNotReceive('removeFromGroup');

        $delegate->approve();
    });

    it('removes the delegate when the mandate ends in the past', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();
        $google->shouldNotReceive('addToGroup');

        $delegate->update(['ended_at' => now()->subDay()]);
    });

    it('keeps the delegate when the end date is set in the future', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('removeFromGroup');

        $delegate->update(['ended_at' => now()->addMonth()]);
    });

    it('keeps the delegate when the mandate ends today', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('removeFromGroup');

        $delegate->update(['ended_at' => now()]);
    });

    it('keeps the delegate when an unrelated attribute is updated', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('removeFromGroup');
        $google->shouldNotReceive('addToGroup');

        $delegate->update(['rejection_reason' => null, 'seat_id' => $delegate->seat_id]);
    });

    it('removes a previously approved delegate when rejected', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        $delegate->reject('No longer valid');
    });

    it('removes nothing when a pending delegate is rejected', function () {
        $delegate = Delegate::factory()->pending()->verified()->create(['email' => 'delegate@afges.org', 'started_at' => now()->subMonth(), 'ended_at' => null]);
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('removeFromGroup');

        $delegate->reject('Not eligible');
    });

    it('removes the old email and adds the new one when the email changes', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();
        $google->shouldReceive('addToGroup')->once()->with('new@afges.org')->andReturnTrue();

        $delegate->update(['email' => 'new@afges.org']);
    });

    it('keeps the old email while another eligible delegate still uses it', function () {
        eligibleDelegate();
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('removeFromGroup');
        $google->shouldReceive('addToGroup')->once()->with('new@afges.org')->andReturnTrue();

        $delegate->update(['email' => 'new@afges.org']);
    });

    it('removes the delegate from the group when deleted', function () {
        $delegate = eligibleDelegate();
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        $delegate->delete();
    });

    it('adds the delegate back to the group when restored', function () {
        $delegate = eligibleDelegate();
        $delegate->delete();
        $google = googleWorkspaceForObserver();
        $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        $delegate->restore();
    });

    it('does not add a restored delegate that is still pending', function () {
        $delegate = Delegate::factory()->pending()->verified()->create(['email' => 'delegate@afges.org', 'started_at' => now()->subMonth(), 'ended_at' => null]);
        $delegate->delete();
        $google = googleWorkspaceForObserver();
        $google->shouldNotReceive('addToGroup');

        $delegate->restore();
    });
});
