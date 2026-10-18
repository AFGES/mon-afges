<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\ListProxies;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ListSessions;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\ListSessionSignIns;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\ListColleges;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\ListDemocraticTimeTypes;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Models\College;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\Organization;
use App\Models\Proxy;
use App\Models\SessionSignIn;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('forbids a user without permissions from listing records', function (string $listPage) {
    actingAs(User::factory()->create());

    livewire($listPage)
        ->assertForbidden();
})->with([
    'delegates' => ListDelegates::class,
    'session sign-ins' => ListSessionSignIns::class,
    'sessions' => ListSessions::class,
    'colleges' => ListColleges::class,
    'democratic time types' => ListDemocraticTimeTypes::class,
    'organizations' => ListOrganizations::class,
    'proxies' => ListProxies::class,
    'users' => ListUsers::class,
    'volunteers' => ListVolunteers::class,
]);

dataset('bulk deletable resources', [
    'delegates' => [ListDelegates::class, Delegate::class, 'Delegate'],
    'session sign-ins' => [ListSessionSignIns::class, SessionSignIn::class, 'SessionSignIn'],
    'sessions' => [ListSessions::class, DemocraticTimeSession::class, 'DemocraticTimeSession'],
    'colleges' => [ListColleges::class, College::class, 'College'],
    'democratic time types' => [ListDemocraticTimeTypes::class, DemocraticTimeType::class, 'DemocraticTimeType'],
    'organizations' => [ListOrganizations::class, Organization::class, 'Organization'],
    'proxies' => [ListProxies::class, Proxy::class, 'Proxy'],
    'users' => [ListUsers::class, User::class, 'User'],
    'volunteers' => [ListVolunteers::class, Volunteer::class, 'Volunteer'],
]);

function actingAsUserWithPermissions(string ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    actingAs($user);

    return $user;
}

it('does not bulk delete records for a user with only view permission', function (string $listPage, string $model, string $permissionSuffix) {
    actingAsUserWithPermissions("ViewAny:{$permissionSuffix}");

    $records = $model::factory()->count(2)->create();
    $remaining = $model::query()->count();

    livewire($listPage)
        ->selectTableRecords($records)
        ->assertActionHidden(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect($model::query()->count())->toBe($remaining);
})->with('bulk deletable resources');

it('bulk deletes records for a user with delete permission', function (string $listPage, string $model, string $permissionSuffix) {
    actingAsUserWithPermissions("ViewAny:{$permissionSuffix}", "Delete:{$permissionSuffix}");

    $records = $model::factory()->count(2)->create();

    livewire($listPage)
        ->selectTableRecords($records)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    foreach ($records as $record) {
        expect($model::query()->whereKey($record->getKey())->exists())->toBeFalse();
    }
})->with('bulk deletable resources');

it('hides the sign-in actions from a user without update permission', function () {
    actingAsUserWithPermissions('ViewAny:DemocraticTimeSession');

    $closed = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => null]);
    $open = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => now(), 'sign_in_closes_at' => null]);

    livewire(ListSessions::class)
        ->assertActionHidden(TestAction::make('open_sign_in')->table($closed))
        ->assertActionHidden(TestAction::make('close_sign_in')->table($open));
});

it('lets a user with update permission open and close sign-in', function () {
    actingAsUserWithPermissions('ViewAny:DemocraticTimeSession', 'Update:DemocraticTimeSession');

    $closed = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => null]);

    livewire(ListSessions::class)
        ->callAction(TestAction::make('open_sign_in')->table($closed));

    expect($closed->refresh()->sign_in_opens_at)->not->toBeNull();

    livewire(ListSessions::class)
        ->callAction(TestAction::make('close_sign_in')->table($closed));

    expect($closed->refresh()->sign_in_closes_at)->not->toBeNull();
});
