<?php

declare(strict_types=1);

use App\Actions\ImportLegacyDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Exports\DelegateExporter;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\User;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Testing\TestAction;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function actingAsDelegateExporter(string ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    actingAs($user);

    return $user;
}

it('exports rows that the importer accepts unchanged', function () {
    $approver = actingAsDelegateExporter('Create:Delegate');
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create(['delegates_per_seat' => 1]);
    $headers = ['email', 'first_name', 'last_name', 'phone', 'birth_date', 'study_program', 'organization', 'college', 'seat_number', 'started_at', 'ended_at'];

    $original = app(ImportLegacyDelegate::class)->handle([
        'email' => 'camille@example.org',
        'first_name' => 'Camille',
        'last_name' => 'Durand',
        'phone' => '0601020304',
        'birth_date' => '2000-12-25',
        'study_program' => 'Master 1 Droit',
        'organization' => $collegeOrganization->organization->short_name,
        'college' => $collegeOrganization->college->name,
        'seat_number' => 1,
        'started_at' => '2025-09-01',
        'ended_at' => '2026-08-31',
    ], $approver);

    $row = DelegateExporter::test(columnMap: array_combine($headers, $headers))
        ->export($original->fresh());

    expect(array_combine($headers, $row))->toMatchArray([
        'email' => 'camille@example.org',
        'birth_date' => '2000-12-25',
        'organization' => $collegeOrganization->organization->short_name,
        'college' => $collegeOrganization->college->name,
        'seat_number' => 1,
        'started_at' => '2025-09-01',
        'ended_at' => '2026-08-31',
    ]);

    $original->forceDelete();

    $reimported = app(ImportLegacyDelegate::class)->handle(array_combine($headers, $row), $approver);

    expect($reimported)
        ->user_id->toBe($original->user_id)
        ->seat_id->toBe($original->seat_id)
        ->started_at->toDateString()->toBe('2025-09-01')
        ->ended_at->toDateString()->toBe('2026-08-31')
        ->and(Delegate::query()->withoutGlobalScopes([CurrentlyScope::class, ApprovedScope::class])->count())->toBe(1);
});

it('does not select the approval columns by default', function () {
    $row = DelegateExporter::test()->export(Delegate::factory()->approved()->create());

    expect($row)->toHaveCount(11);
});

it('exports the approval status label and date when selected', function () {
    $delegate = Delegate::factory()->approved()->create();

    $row = DelegateExporter::test(columnMap: ['approval_status' => 'approval_status', 'approved_at' => 'approved_at'])
        ->export($delegate);

    expect($row)->toBe([__('approval.status.approved'), $delegate->approved_at->format('Y-m-d H:i:s')]);
});

it('shows the export actions to a user allowed to export delegates', function () {
    actingAsDelegateExporter('ViewAny:Delegate', 'Export:Delegate');
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    livewire(ListDelegates::class)
        ->assertActionVisible('export')
        ->selectTableRecords([$delegate])
        ->assertActionVisible(TestAction::make(ExportBulkAction::class)->table()->bulk());
});

it('hides the export actions from a user who can only create delegates', function (array $permissions) {
    actingAsDelegateExporter('ViewAny:Delegate', ...$permissions);
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    livewire(ListDelegates::class)
        ->assertActionHidden('export')
        ->selectTableRecords([$delegate])
        ->assertActionHidden(TestAction::make(ExportBulkAction::class)->table()->bulk());
})->with([
    'create only' => [['Create:Delegate']],
    'no other permission' => [[]],
]);
