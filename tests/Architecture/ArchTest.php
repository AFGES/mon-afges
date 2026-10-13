<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel()
    ->ignoring('App\Providers\Filament\RootPanelProvider')
    ->ignoring('App\Providers\AppServiceProvider')
    ->ignoring('App\Listeners\MarkEmailAsVerifiedOnSocialiteLoginListener');

arch('models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\Scopes');

arch('service providers')
    ->expect('App\Providers')
    ->toHaveSuffix('Provider')
    ->ignoring('App\Providers\Filament');

arch('enums')
    ->expect('App\Enums')
    ->toBeEnums();
