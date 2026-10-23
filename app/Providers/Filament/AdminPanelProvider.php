<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplySessionIdleTimeout;
use App\Http\Middleware\EnsureUserIsActive;
use App\Services\BrandingService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => app(BrandingService::class)->appName())
            ->brandLogo(function (): ?string {
                return app(BrandingService::class)->logoUrl();
            })
            ->darkModeBrandLogo(function (): ?string {
                return app(BrandingService::class)->logoUrl();
            })
            ->brandLogoHeight('2rem')
            ->favicon(function (): ?string {
                return app(BrandingService::class)->faviconUrl();
            })
            ->colors(function (): array {
                $hex = app(BrandingService::class)->accentColor();

                try {
                    return [
                        'primary' => Color::hex($hex),
                    ];
                } catch (\Throwable) {
                    return [
                        'primary' => Color::Amber,
                    ];
                }
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->renderHook(
                'panels::footer',
                fn (): string => '<div class="fi-footer text-center text-sm text-gray-500 py-4">'
                    .e(app(BrandingService::class)->footerText())
                    .'</div>',
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ApplySessionIdleTimeout::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureUserIsActive::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
