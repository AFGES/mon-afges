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
use App\Models\User;

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
