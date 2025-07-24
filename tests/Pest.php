<?php

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
    ->ignoring('App\Providers\Filament\AdminPanelProvider');
// arch()->preset()->strict();


/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
