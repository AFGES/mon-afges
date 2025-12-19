<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\User;
use App\Observers\DelegateObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        // Register Livewire components explicitly
        Livewire::component('profile.manage-volunteers', \App\Livewire\Profile\ManageVolunteers::class);
        Livewire::component('profile.manage-delegates', \App\Livewire\Profile\ManageDelegates::class);
        Livewire::component('manage_volunteers', \App\Livewire\Profile\ManageVolunteers::class);
        Livewire::component('manage_delegates', \App\Livewire\Profile\ManageDelegates::class);

        if (app()->runningInConsole()) {
            \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
                'user' => User::class,
            ]);
        }

        if (app()->isLocale('fr')) {
            Pluralizer::useLanguage('french');
        }

        // Register Blueprint macro for approval status columns
        \Illuminate\Database\Schema\Blueprint::macro('approvalStatus', function (?string $after = null) {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->enum('status', array_column(ApprovalStatus::cases(), 'value'))->default(ApprovalStatus::Pending->value)->after($after);
            $this->timestamp('approved_at')->nullable()->after('status');
            $this->foreignId('approved_by')->nullable()->constrained('users')->after('approved_at');
            $this->text('rejection_reason')->nullable()->after('approved_by');
        });

        \Illuminate\Database\Schema\Blueprint::macro('dropApprovalStatus', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->dropForeign(['approved_by']);
            $this->dropColumn(['status', 'approved_at', 'approved_by', 'rejection_reason']);
        });

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
