<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Actions\CreateUserFromSocialite;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Volunteers\Widgets\PendingVolunteerStats;
use App\Http\Middleware\EnsureHasVolunteerOrganization;
use App\Livewire\Profile\ManageDelegates;
use App\Livewire\Profile\ManageVolunteers;
use App\Livewire\Profile\PersonalInfo;
use App\Models\SocialiteUser;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

final class RootPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('root')
            ->path('')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PendingVolunteerStats::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->globallySearchable(false),
                BreezyCore::make()
                    ->myProfile()
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                        ManageVolunteers::class,
                        ManageDelegates::class,
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureHasVolunteerOrganization::class,
            ])
            ->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google'),
                    ])
                    ->registration(true)
                    ->socialiteUserModelClass(SocialiteUser::class)
                    ->createUserUsing((new CreateUserFromSocialite)(...))
            )
            ->sidebarCollapsibleOnDesktop()
            ->topbar(false)
            ->viteTheme('resources/css/filament/root/theme.css')
            ->spa();
    }
}
