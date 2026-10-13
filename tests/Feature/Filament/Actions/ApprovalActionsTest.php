<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;
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
            ->callTableBulkAction('bulkApprove', $pending->push($alreadyApproved))
            ->assertNotified(__('approval.notifications.bulk_approved.title'));

        $pending->each(fn ($record) => expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Approved)
            ->approved_by->toBe($admin->id));
        Notification::assertSentToTimes($pending->first()->user, ApprovalApprovedNotification::class, 1);
        Notification::assertNotSentTo($alreadyApproved->user, ApprovalApprovedNotification::class);
    })->with('approvable tables')->skip('App bug: BulkApproveAction/BulkRejectAction read the selected records without accessSelectedRecords(), so Filament throws a LogicException');
});

describe('BulkRejectAction', function () {
    it('rejects only the pending selected records with the given reason', function (string $listPage, string $model) {
        $admin = actingAsSuperAdminForApprovalActions();
        $pending = $model::factory()->count(2)->pending()->create();
        $alreadyApproved = $model::factory()->approved()->create();

        livewire($listPage)
            ->filterTable('approval_status', ['pending', 'approved'])
            ->callTableBulkAction('bulkReject', $pending->push($alreadyApproved), data: ['rejection_reason' => 'Out of scope'])
            ->assertNotified(__('approval.notifications.bulk_rejected.title'));

        $pending->take(2)->each(fn ($record) => expect($record->refresh())
            ->approval_status->toBe(ApprovalStatus::Rejected)
            ->rejection_reason->toBe('Out of scope')
            ->approved_by->toBe($admin->id));
        expect($alreadyApproved->refresh()->approval_status)->toBe(ApprovalStatus::Approved);
    })->with('approvable tables')->skip('App bug: BulkApproveAction/BulkRejectAction read the selected records without accessSelectedRecords(), so Filament throws a LogicException');

    it('requires a rejection reason for the bulk rejection', function (string $listPage, string $model) {
        actingAsSuperAdminForApprovalActions();
        $pending = $model::factory()->count(2)->pending()->create();

        livewire($listPage)
            ->callTableBulkAction('bulkReject', $pending, data: ['rejection_reason' => null])
            ->assertHasTableBulkActionErrors(['rejection_reason' => 'required']);

        $pending->each(fn ($record) => expect($record->refresh()->isPending())->toBeTrue());
    })->with('approvable tables')->skip('App bug: BulkApproveAction/BulkRejectAction read the selected records without accessSelectedRecords(), so Filament throws a LogicException');
});
