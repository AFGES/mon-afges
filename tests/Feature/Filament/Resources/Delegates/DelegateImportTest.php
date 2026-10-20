<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Imports\DelegateImporter;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function actingAsDelegateManager(string ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    actingAs($user);

    return $user;
}

it('shows the import action to a user allowed to create delegates', function () {
    actingAsDelegateManager('ViewAny:Delegate', 'Create:Delegate');

    livewire(ListDelegates::class)->assertActionVisible('import');
});

it('hides the import action from a user who cannot create delegates', function () {
    actingAsDelegateManager('ViewAny:Delegate');

    livewire(ListDelegates::class)->assertActionHidden('import');
});

it('imports a legacy row with French dates as an approved delegate', function () {
    $user = actingAsDelegateManager('ViewAny:Delegate', 'Create:Delegate');
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create(['delegates_per_seat' => 1]);
    $import = new Import;
    $import->user()->associate($user);

    DelegateImporter::test(import: $import)->import([
        'email' => 'camille@example.org',
        'first_name' => 'Camille',
        'last_name' => 'Durand',
        'organization' => $collegeOrganization->organization->short_name,
        'college' => $collegeOrganization->college->name,
        'seat_number' => '1',
        'started_at' => '01/09/2025',
        'ended_at' => '31/08/2026',
    ])->assertImported()->assertHasNoErrors();

    $delegate = Delegate::query()->withoutGlobalScopes([CurrentlyScope::class, ApprovedScope::class])->sole();

    expect($delegate)
        ->started_at->toDateString()->toBe('2025-09-01')
        ->ended_at->toDateTimeString()->toBe('2026-09-01 00:00:00')
        ->approved_by->toBe($user->id);
});

it('reports a row failure for an unknown organization', function () {
    $user = actingAsDelegateManager('Create:Delegate');
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $import = new Import;
    $import->user()->associate($user);

    DelegateImporter::test(import: $import)->import([
        'email' => 'camille@example.org',
        'first_name' => 'Camille',
        'last_name' => 'Durand',
        'organization' => 'Nope',
        'college' => $collegeOrganization->college->name,
        'seat_number' => '1',
        'started_at' => '2025-09-01',
    ])->assertHasErrors(['organization']);
});
