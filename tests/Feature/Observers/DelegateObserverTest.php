<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequestedNotification;
use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;

function ignoreGoogleWorkspaceSync(): MockInterface
{
    return mock(DelegateGoogleWorkspaceSyncServiceInterface::class)->shouldIgnoreMissing();
}

beforeEach(function () {
    ignoreGoogleWorkspaceSync();
});

function delegateWithEmail(string $email): Mockery\Matcher\Closure
{
    return Mockery::on(fn (Delegate $delegate): bool => $delegate->email === $email);
}

describe('approver notification', function () {
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
    it('adds the delegate to the group when created', function () {
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldReceive('addDelegate')
            ->once()
            ->with(delegateWithEmail('delegate@afges.org'))
            ->andReturnTrue();

        Delegate::factory()->create(['email' => 'delegate@afges.org']);
    });

    it('removes the delegate from the group when the mandate ends', function () {
        $delegate = Delegate::factory()->create(['email' => 'delegate@afges.org', 'ended_at' => null]);
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldReceive('removeDelegate')
            ->once()
            ->with(delegateWithEmail('delegate@afges.org'))
            ->andReturnTrue();

        $delegate->update(['ended_at' => now()]);
    });

    it('keeps the delegate in the group when another attribute is updated', function () {
        $delegate = Delegate::factory()->create(['ended_at' => null]);
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldNotReceive('removeDelegate');

        $delegate->update(['email' => 'new-address@afges.org']);
    });

    it('keeps the delegate in the group when the end date is cleared', function () {
        $delegate = Delegate::factory()->create(['ended_at' => now()->addDay()]);
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldNotReceive('removeDelegate');

        $delegate->update(['ended_at' => null]);
    });

    it('removes the delegate from the group when deleted', function () {
        $delegate = Delegate::factory()->create(['email' => 'delegate@afges.org']);
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldReceive('removeDelegate')
            ->once()
            ->with(delegateWithEmail('delegate@afges.org'))
            ->andReturnTrue();

        $delegate->delete();
    });

    it('adds the delegate back to the group when restored', function () {
        $delegate = Delegate::factory()->create(['email' => 'delegate@afges.org']);
        $delegate->delete();
        $this->mock(DelegateGoogleWorkspaceSyncServiceInterface::class)
            ->shouldReceive('addDelegate')
            ->once()
            ->with(delegateWithEmail('delegate@afges.org'))
            ->andReturnTrue();

        $delegate->restore();
    });
});
