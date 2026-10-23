<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\RequirePasskey;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * admin.openrevise.com: analytics, content, API keys, logs and settings.
 * One admin account; sign-in is a password, then a passkey if one is set up.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(config('openrevise.domains.admin'))
            ->path('')
            ->login()
            ->brandName('OpenRevise')
            ->brandLogo(fn () => view('filament.brand'))
            ->favicon('/favicon.svg')
            ->colors([
                // The deep green of the public sites (#0f6b56 at 600).
                'primary' => [
                    50 => 'oklch(0.977 0.012 172)', 100 => 'oklch(0.95 0.025 172)', 200 => 'oklch(0.905 0.045 172)',
                    300 => 'oklch(0.84 0.07 172)', 400 => 'oklch(0.72 0.09 172)', 500 => 'oklch(0.6 0.1 172)',
                    600 => 'oklch(0.48 0.09 172)', 700 => 'oklch(0.42 0.08 172)', 800 => 'oklch(0.36 0.07 172)',
                    900 => 'oklch(0.3 0.055 172)', 950 => 'oklch(0.22 0.04 172)',
                ],
                'danger' => Color::Red,
                'gray' => Color::Stone,
            ])
            ->font(
                'Inter Variable',
                url: fn () => rescue(fn () => Vite::asset('resources/css/admin.css'), null, false),
                provider: LocalFontProvider::class,
            )
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Content'),
                NavigationGroup::make('API'),
                NavigationGroup::make('Logs'),
                NavigationGroup::make('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<meta name="robots" content="noindex, nofollow">'))
            ->middleware([
                NoIndex::class,
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
            ->authMiddleware([
                Authenticate::class,
                RequirePasskey::class,
            ]);
    }
}
