<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\Organization;
use App\Models\Proxy;
use App\Models\SessionSignIn;
use App\Models\User;
use App\Models\Volunteer;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

dataset('deleteAnyModels', [
    'College' => [College::class, 'College'],
    'Delegate' => [Delegate::class, 'Delegate'],
    'DemocraticTimeSession' => [DemocraticTimeSession::class, 'DemocraticTimeSession'],
    'DemocraticTimeType' => [DemocraticTimeType::class, 'DemocraticTimeType'],
    'Organization' => [Organization::class, 'Organization'],
    'Proxy' => [Proxy::class, 'Proxy'],
    'Role' => [Role::class, 'Role'],
    'SessionSignIn' => [SessionSignIn::class, 'SessionSignIn'],
    'User' => [User::class, 'User'],
    'Volunteer' => [Volunteer::class, 'Volunteer'],
]);

test('allows deleteAny only with the matching delete permission', function (string $model, string $name) {
    Permission::firstOrCreate(['name' => "Delete:{$name}", 'guard_name' => 'web']);
    $allowed = User::factory()->create()->givePermissionTo("Delete:{$name}");
    $denied = User::factory()->create();

    expect($allowed->can('deleteAny', $model))->toBeTrue()
        ->and($denied->can('deleteAny', $model))->toBeFalse();
})->with('deleteAnyModels');
