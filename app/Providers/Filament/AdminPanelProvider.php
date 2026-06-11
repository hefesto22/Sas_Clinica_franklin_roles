<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Support\Enums\Alignment;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $logo = config('app.logo');
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(config('app.name'))
            // si hay logo, úsalo
            // ?v=2: fuerza al navegador a recargar el PNG recortado (cache-busting)
            ->brandLogo($logo ? asset($logo) . '?v=2' : null)
            ->brandLogoHeight('4.0rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->login()
            ->colors([
                'primary' => Color::generateV3Palette('#39C928'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->plugins(['dayGrid', 'timeGrid', 'interaction'])
                    ->selectable(),

            ])
            // FullCalendar no conoce el modo oscuro de Filament: estas variables
            // adaptan popovers ("+N más"), bordes y fondos a ambos temas.
            ->renderHook('panels::styles.after', fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(<<<'HTML'
                <style>
                    .dark .fc {
                        --fc-page-bg-color: rgb(24 24 27);        /* zinc-900 */
                        --fc-neutral-bg-color: rgb(39 39 42);     /* zinc-800 */
                        --fc-border-color: rgb(63 63 70);         /* zinc-700 */
                        --fc-neutral-text-color: rgb(212 212 216);
                        --fc-list-event-hover-bg-color: rgb(39 39 42);
                        --fc-today-bg-color: rgb(57 201 40 / 0.10);
                    }
                    .dark .fc .fc-popover {
                        background: rgb(24 24 27);
                        border-color: rgb(63 63 70);
                        box-shadow: 0 10px 25px rgb(0 0 0 / 0.5);
                    }
                    .dark .fc .fc-popover-header {
                        background: rgb(39 39 42);
                        color: rgb(244 244 245);
                    }
                    .dark .fc .fc-popover-body .fc-event-title,
                    .dark .fc .fc-popover-body .fc-event-time {
                        color: rgb(228 228 231);
                    }
                    .fc .fc-popover { z-index: 40; border-radius: 0.5rem; overflow: hidden; }

                    /* ── Toolbar del calendario: espaciado y botones estilo Filament ── */
                    .fc .fc-header-toolbar {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 0.75rem;
                        margin-top: 0.75rem;
                        margin-bottom: 1.25rem !important;
                    }
                    .fc .fc-toolbar-chunk { display: flex; align-items: center; gap: 0.5rem; }
                    .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 700; text-transform: capitalize; }
                    .fc .fc-button {
                        padding: 0.45rem 0.9rem;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                        font-weight: 600;
                        text-transform: capitalize;
                        border: 1px solid rgb(63 63 70 / 0.4);
                        background: transparent;
                        color: inherit;
                        box-shadow: none;
                    }
                    .fc .fc-button:hover { background: rgb(113 113 122 / 0.15); }
                    .fc .fc-button-primary:not(:disabled).fc-button-active,
                    .fc .fc-button-primary:not(:disabled):active {
                        background: #39C928;
                        border-color: #39C928;
                        color: #fff;
                    }
                    .fc .fc-button:focus, .fc .fc-button:focus-visible { box-shadow: 0 0 0 2px rgb(57 201 40 / 0.4); }
                    .fc .fc-button-group { gap: 0; border-radius: 0.5rem; overflow: hidden; }
                    .fc .fc-button-group .fc-button { border-radius: 0; }
                    .fc .fc-button-group .fc-button:first-child { border-radius: 0.5rem 0 0 0.5rem; }
                    .fc .fc-button-group .fc-button:last-child { border-radius: 0 0.5rem 0.5rem 0; }
                </style>
                HTML))
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
