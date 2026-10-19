<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ApprovalStatus;
use App\Livewire\Profile\ManageDelegates;
use App\Livewire\Profile\ManageVolunteers;
use App\Livewire\Profile\PersonalInfo;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\Proxy;
use App\Models\User;
use App\Models\Volunteer;
use App\Observers\DelegateObserver;
use App\Observers\DemocraticTimeSessionObserver;
use App\Observers\DemocraticTimeTypeObserver;
use App\Observers\EmailTemplateObserver;
use App\Observers\ProxyObserver;
use App\Observers\VolunteerObserver;
use App\Policies\Contracts\RestrictsSuperAdmin;
use App\Services\DelegateGoogleWorkspaceSyncService;
use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use App\Services\GoogleWorkspaceService;
use App\Services\GoogleWorkspaceServiceInterface;
use App\Services\UnconfiguredGoogleWorkspaceService;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as LaravelTelescopeServiceProvider;
use Livewire\Livewire;
use Propaganistas\LaravelPhone\Rules\Phone;
use SocialiteProviders\Google\Provider as GoogleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoogleWorkspaceServiceInterface::class, fn (): GoogleWorkspaceServiceInterface => GoogleWorkspaceService::configIsPresent()
            ? GoogleWorkspaceService::fromConfig()
            : new UnconfiguredGoogleWorkspaceService);
        $this->app->singleton(DelegateGoogleWorkspaceSyncServiceInterface::class, DelegateGoogleWorkspaceSyncService::class);

        if ($this->app->environment('local') && class_exists(LaravelTelescopeServiceProvider::class)) {
            $this->app->register(LaravelTelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        // Enable strict mode for Eloquent models in non-production environments
        Model::shouldBeStrict(! app()->isProduction());

        if (app()->isLocale('fr')) {
            Pluralizer::useLanguage('french');
        }

        // Parse phone numbers without an international prefix as French numbers
        Phone::setDefaultCountry('FR');

        $this->blueprintApprovalStatusMacro();
        $this->blueprintPeriodMacro();

        // Register model observers
        Delegate::observe(DelegateObserver::class);
        Volunteer::observe(VolunteerObserver::class);
        Proxy::observe(ProxyObserver::class);
        DemocraticTimeSession::observe(DemocraticTimeSessionObserver::class);
        DemocraticTimeType::observe(DemocraticTimeTypeObserver::class);
        EmailTemplate::observe(EmailTemplateObserver::class);

        // Register custom Filament Breezy profile components
        Livewire::component('manage_volunteers', ManageVolunteers::class);
        Livewire::component('manage_delegates', ManageDelegates::class);
        Livewire::component('personal_info', PersonalInfo::class);

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('google', GoogleProvider::class);
        });

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! $user->isSuperAdmin()) {
                return null;
            }

            $policy = isset($arguments[0]) ? Gate::getPolicyFor($arguments[0]) : null;

            if ($policy instanceof RestrictsSuperAdmin && in_array($ability, $policy->superAdminRestrictedAbilities(), true)) {
                return null;
            }

            return true;
        });
    }

    /**
     * Register Blueprint macro for approval status columns
     */
    private function blueprintApprovalStatusMacro(): void
    {
        Blueprint::macro('approvalStatus', function (?string $after = null): void {
            /** @var Blueprint $this */
            $approvalStatus = $this->enum('approval_status', array_column(ApprovalStatus::cases(), 'value'))->default(ApprovalStatus::Pending->value);
            if ($after !== null) {
                $approvalStatus->after($after);
            }
            $this->timestamp('approved_at')->nullable()->after('approval_status');
            $this->foreignUuid('approved_by')->nullable()->constrained('users')->after('approved_at');
            $this->text('rejection_reason')->nullable()->after('approved_by');
        });

        Blueprint::macro('dropApprovalStatus', function (): void {
            /** @var Blueprint $this */
            $this->dropForeign(['approved_by']);
            $this->dropColumn(['approval_status', 'approved_at', 'approved_by', 'rejection_reason']);
        });
    }

    /**
     * Register Blueprint macro for period (start/end date) columns
     */
    private function blueprintPeriodMacro(): void
    {
        Blueprint::macro('period', function (string $startType = 'datetime', ?string $after = null): void {
            /** @var Blueprint $this */
            $startedAt = $startType === 'datetime'
                ? $this->datetime('started_at')->useCurrent()
                : $this->date('started_at')->useCurrent();
            if ($after !== null) {
                $startedAt->after($after);
            }
            $endedAt = $startType === 'datetime'
                ? $this->datetime('ended_at')
                : $this->date('ended_at');
            $endedAt->nullable()->after('started_at');
        });

        Blueprint::macro('dropPeriod', function (): void {
            /** @var Blueprint $this */
            $this->dropColumn(['started_at', 'ended_at']);
        });
    }
}
