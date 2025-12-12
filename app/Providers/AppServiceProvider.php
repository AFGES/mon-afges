<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Delegate;
use App\Models\User;
use App\Observers\DelegateObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pluralizer::useLanguage('french');

        // Register model observers
        Delegate::observe(DelegateObserver::class);

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
        });

        // Mark email as verified when user logs in with Socialite
        Event::listen(
            \DutchCodingCompany\FilamentSocialite\Events\Login::class,
            [\App\Listeners\MarkEmailAsVerifiedOnSocialiteLogin::class, 'handleLogin']
        );

        Event::listen(
            \DutchCodingCompany\FilamentSocialite\Events\Registered::class,
            [\App\Listeners\MarkEmailAsVerifiedOnSocialiteLogin::class, 'handleRegistered']
        );

        Event::listen(
            \DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected::class,
            [\App\Listeners\MarkEmailAsVerifiedOnSocialiteLogin::class, 'handleSocialiteUserConnected']
        );

        Gate::before(function (User $user, $ability) {
            return $user->hasRole('super_admin') ?: null;
        });
    }
}
