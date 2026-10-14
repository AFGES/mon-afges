<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\EditDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForApprovalActions(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

function actingAsViewerWithoutUpdatePermission(string $model): User
{
    $permission = 'ViewAny:'.class_basename($model);
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

    $user = User::factory()->create()->givePermissionTo($permission);

    actingAs($user);

    return $user;
}

dataset('approvable tables', [
    'volunteers' => [ListVolunteers::class, Volunteer::class],
    'delegates' => [ListDelegates::class, Delegate::class],
]);

describe('ApproveAction', function () {
    it('approves a pending record and notifies its user', function (string $listPage, string $model) {
        $this->freezeSecond();
        $admin = actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $record = $model::factory()->pending()->create();

        livewire($listPage)
            ->callTableAction('approve', $record)
            ->assertNotified(__('approval.notifications.approved.title'));

        expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Approved)
            ->approved_by->toBe($admin->id)
            ->approved_at->toEqual(now());
        Notification::assertSentTo($record->user, ApprovalApprovedNotification::class);
    })->with('approvable tables');

    it('hides the approve action for records that are not pending', function (string $listPage, string $model, string $state) {
        actingAsSuperAdminForApprovalActions();
        $record = $model::factory()->{$state}()->create();

        livewire($listPage)
            ->filterTable('approval_status', [$state])
            ->assertTableActionHidden('approve', $record);
    })->with('approvable tables')->with(['approved', 'rejected']);
});

describe('RejectAction', function () {
    it('rejects a pending record with the given reason', function (string $listPage, string $model) {
        $this->freezeSecond();
        $admin = actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $record = $model::factory()->pending()->create();

        livewire($listPage)
            ->callTableAction('reject', $record, data: ['rejection_reason' => 'Missing mandate'])
            ->assertNotified(__('approval.notifications.rejected.title'));

        expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Rejected)
            ->rejection_reason->toBe('Missing mandate')
            ->approved_by->toBe($admin->id)
            ->approved_at->toEqual(now());
        Notification::assertNothingSent();
    })->with('approvable tables');

    it('requires a rejection reason', function (string $listPage, string $model) {
        actingAsSuperAdminForApprovalActions();
        $record = $model::factory()->pending()->create();

        livewire($listPage)
            ->callTableAction('reject', $record, data: ['rejection_reason' => null])
            ->assertHasTableActionErrors(['rejection_reason' => 'required'])
            ->assertNotNotified();

        expect($record->refresh()->isPending())->toBeTrue();
    })->with('approvable tables');

    it('hides the reject action for records that are not pending', function (string $listPage, string $model, string $state) {
        actingAsSuperAdminForApprovalActions();
        $record = $model::factory()->{$state}()->create();

        livewire($listPage)
            ->filterTable('approval_status', [$state])
            ->assertTableActionHidden('reject', $record);
    })->with('approvable tables')->with(['approved', 'rejected']);
});

describe('BulkApproveAction', function () {
    it('approves only the pending selected records and reports the count', function (string $listPage, string $model) {
        $admin = actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $pending = $model::factory()->count(2)->pending()->create();
        $alreadyApproved = $model::factory()->approved()->create();

        livewire($listPage)
            ->filterTable('approval_status', ['pending', 'approved'])
            ->callTableBulkAction('bulkApprove', $pending->concat([$alreadyApproved]))
            ->assertNotified(__('approval.notifications.bulk_approved.title'));

        $pending->each(fn ($record) => expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Approved)
            ->approved_by->toBe($admin->id));
        Notification::assertSentToTimes($pending->first()->user, ApprovalApprovedNotification::class, 1);
        Notification::assertNotSentTo($alreadyApproved->user, ApprovalApprovedNotification::class);
    })->with('approvable tables');
});

describe('BulkRejectAction', function () {
    it('rejects only the pending selected records with the given reason', function (string $listPage, string $model) {
        $admin = actingAsSuperAdminForApprovalActions();
        $pending = $model::factory()->count(2)->pending()->create();
        $alreadyApproved = $model::factory()->approved()->create();

        livewire($listPage)
            ->filterTable('approval_status', ['pending', 'approved'])
            ->callTableBulkAction('bulkReject', $pending->concat([$alreadyApproved]), data: ['rejection_reason' => 'Out of scope'])
            ->assertNotified(__('approval.notifications.bulk_rejected.title'));

        $pending->each(fn ($record) => expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Rejected)
            ->rejection_reason->toBe('Out of scope')
            ->approved_by->toBe($admin->id));
        expect($alreadyApproved->refresh()->approval_status)->toBe(ApprovalStatus::Approved);
    })->with('approvable tables');

    it('requires a rejection reason for the bulk rejection', function (string $listPage, string $model) {
        actingAsSuperAdminForApprovalActions();
        $pending = $model::factory()->count(2)->pending()->create();

        livewire($listPage)
            ->callTableBulkAction('bulkReject', $pending, data: ['rejection_reason' => null])
            ->assertHasTableBulkActionErrors(['rejection_reason' => 'required']);

        $pending->each(fn ($record) => expect($record->refresh()->isPending())->toBeTrue());
    })->with('approvable tables');
});

describe('authorization', function () {
    it('hides the approve and reject actions from users who cannot update the record', function (string $listPage, string $model) {
        actingAsViewerWithoutUpdatePermission($model);
        $record = $model::factory()->pending()->create();

        livewire($listPage)
            ->assertTableActionHidden('approve', $record)
            ->assertTableActionHidden('reject', $record);
    })->with('approvable tables');

    it('skips records the user cannot update in bulk actions', function (string $listPage, string $model, string $bulkAction, array $data) {
        actingAsViewerWithoutUpdatePermission($model);
        $pending = $model::factory()->count(2)->pending()->create();

        livewire($listPage)
            ->callTableBulkAction($bulkAction, $pending, data: $data);

        $pending->each(fn ($record) => expect($record->refresh()->isPending())->toBeTrue());
    })->with('approvable tables')->with([
        'bulk approve' => ['bulkApprove', []],
        'bulk reject' => ['bulkReject', ['rejection_reason' => 'Out of scope']],
    ]);
});

function seatWithCapacityForApprovalActions(int $delegatesPerSeat): Seat
{
    return Seat::factory()->create([
        'college_organization_id' => CollegeOrganization::factory()->create(['delegates_per_seat' => $delegatesPerSeat])->id,
    ]);
}

describe('ApproveDelegateAction', function () {
    beforeEach(function () {
        mockGoogleWorkspaceService()->shouldIgnoreMissing();
    });

    it('ends the selected current delegates of the seat when approving', function () {
        actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $seat = Seat::factory()->create();
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->subDay()->startOfDay()]);

        livewire(ListDelegates::class)
            ->callTableAction('approve', $record, ['terminated_delegate_ids' => [$current->id]])
            ->assertNotified(__('approval.notifications.approved.title'));

        expect($record->refresh()->isApproved())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at->toDateString())->toBe(now()->subDay()->toDateString());
    });

    it('keeps current delegates when none is selected and the seat has room', function () {
        actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $seat = seatWithCapacityForApprovalActions(2);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

        livewire(ListDelegates::class)
            ->callTableAction('approve', $record, ['terminated_delegate_ids' => []]);

        expect($record->refresh()->isApproved())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
    });

    it('requires ending current delegates when the seat capacity of the college is reached', function () {
        actingAsSuperAdminForApprovalActions();
        $seat = seatWithCapacityForApprovalActions(1);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

        livewire(ListDelegates::class)
            ->callTableAction('approve', $record, ['terminated_delegate_ids' => []])
            ->assertHasTableActionErrors(['terminated_delegate_ids' => 'min']);

        expect($record->refresh()->isPending())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
    });

    it('checks the current delegates by default only when the seat is full', function (int $perSeat, array $expectedChecked) {
        actingAsSuperAdminForApprovalActions();
        $collegeOrganization = CollegeOrganization::factory()->create(['delegates_per_seat' => $perSeat]);
        $seat = Seat::factory()->create(['college_organization_id' => $collegeOrganization->id]);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

        livewire(ListDelegates::class)
            ->mountTableAction('approve', $record)
            ->assertSchemaStateSet(['terminated_delegate_ids' => $expectedChecked === [] ? [] : [$current->id]]);
    })->with([
        'seat full' => [1, [true]],
        'seat not full' => [2, []],
    ]);
});

describe('BulkApproveDelegatesAction', function () {
    beforeEach(function () {
        mockGoogleWorkspaceService()->shouldIgnoreMissing();
    });

    it('ends the other current delegates of each seat when the toggle is on', function () {
        actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $seat = Seat::factory()->create();
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->subDay()->startOfDay()]);

        livewire(ListDelegates::class)
            ->callTableBulkAction('bulkApprove', [$record], ['terminate_current_delegates' => true]);

        expect($record->refresh()->isApproved())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at->toDateString())->toBe(now()->subDay()->toDateString());
    });

    it('keeps the other current delegates when the toggle is off and the seat has room', function () {
        actingAsSuperAdminForApprovalActions();
        Notification::fake();
        $seat = seatWithCapacityForApprovalActions(2);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

        livewire(ListDelegates::class)
            ->callTableBulkAction('bulkApprove', [$record], ['terminate_current_delegates' => false]);

        expect($record->refresh()->isApproved())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
    });

    it('skips requests whose seat is full when the toggle is off', function () {
        actingAsSuperAdminForApprovalActions();
        $seat = seatWithCapacityForApprovalActions(1);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $record = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

        livewire(ListDelegates::class)
            ->callTableBulkAction('bulkApprove', [$record], ['terminate_current_delegates' => false])
            ->assertNotified(__('delegate.approval.bulk_skipped_title'));

        expect($record->refresh()->isPending())->toBeTrue()
            ->and(Delegate::withoutGlobalScopes()->find($current->id)->ended_at)->toBeNull();
    });
});

describe('EditDelegate approval link', function () {
    it('opens the approve action when approve=1 is present on a pending delegate', function (bool $pending, bool $opened) {
        actingAsSuperAdminForApprovalActions();
        $record = Delegate::factory()->{$pending ? 'pending' : 'approved'}()->create();

        $component = Livewire::withQueryParams(['approve' => 1])->test(EditDelegate::class, ['record' => $record->getKey()]);

        expect($component->get('mountedActions'))->toHaveCount($opened ? 1 : 0);
    })->with(['pending' => [true, true], 'approved' => [false, false]]);
});
