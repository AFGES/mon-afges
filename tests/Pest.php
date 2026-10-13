<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Architecture Testing
|--------------------------------------------------------------------------
|
| Architecture testing enables you to specify expectations that test whether your application
| adheres to a set of architectural rules, helping you maintain a clean and sustainable
| codebase. The expectations are determined by either relative namespaces, fully qualified
| namespaces, or function names.
|
*/
arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel()
    ->ignoring('App\Providers\Filament\RootPanelProvider')
    ->ignoring('App\Providers\AppServiceProvider')
    ->ignoring('App\Listeners\MarkEmailAsVerifiedOnSocialiteLoginListener');
// arch()->preset()->strict();
