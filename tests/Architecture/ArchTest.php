<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

// Models should extend Eloquent Model
arch('models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\Scopes')
    ->ignoring('App\Models\User'); // User extends Authenticatable

// Service providers should have Provider suffix
arch('service providers')
    ->expect('App\Providers')
    ->toHaveSuffix('Provider')
    ->ignoring('App\Providers\Filament'); // Filament uses PanelProvider suffix

// Enums should be in the Enums namespace
arch('enums')
    ->expect('App\Enums')
    ->toBeEnums();

// $expectations[] = expect($userNamespaces)->classes()->not->toHaveProtectedMethods();
// arch()->expect()->classes()->not->toBeAbstract()->ignoring('App\Http\Controllers\Controller');
// arch()->expect()->toUseStrictTypes();
// arch()->expect()->toUseStrictEquality();
// arch()->expect()->classes()->toBeFinal();

// arch()->expect([
//     'sleep',
//     'usleep',
// ])->not->toBeUsed();
