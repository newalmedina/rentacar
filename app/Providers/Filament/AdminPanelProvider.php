<?php

namespace App\Providers\Filament;

use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;
use App\Filament\AvatarProviders\AutenticatedUserAvatar;
use App\Filament\CustomWidgets\GananciasMensualesChart;
use App\Filament\CustomWidgets\GananciasStats;
use App\Filament\CustomWidgets\GastosPercentPieChart;
use App\Filament\CustomWidgets\GastosPieChart;
use App\Filament\CustomWidgets\OtherExpensesChart;
use App\Filament\CustomWidgets\OtherExpensesStats;
use App\Filament\CustomWidgets\ProductosMasVendido;
use App\Filament\CustomWidgets\ProductosMasVendidoPorcentaje;
use App\Filament\CustomWidgets\ProductosMenosVendido;
use App\Filament\CustomWidgets\ProductosMenosVendidoPorcentaje;
use App\Filament\CustomWidgets\VentasStats;
use App\Filament\Pages\Settings\Settings;
use App\Filament\CustomWidgets\VentasMensualesChart;
use App\Filament\CustomWidgets\VentasPorVendedorPercentPieChart;
use App\Filament\CustomWidgets\VentasPorVendedorPieChart;
use App\Filament\Pages\Backups;
use App\Filament\Pages\CalendarPage;
use App\Filament\Pages\Profile;
use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CityResource;
use App\Filament\Resources\CmsContentResource;
use App\Filament\Resources\CountryResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\OtherExpenseItemResource;
use App\Filament\Resources\OtherExpenseResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\StateResource;
use App\Filament\Resources\SupplierResource;
use App\Filament\Resources\UnitOfMeasureResource;
use App\Filament\Resources\UserResource;
use App\Http\Middleware\AuthenticateAndCheckActive;
use App\Models\OtherExpenseItem;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
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
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Outerweb\FilamentSettings\Filament\Plugins\FilamentSettingsPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{

    public function panel(Panel $panel): Panel
    {
        $panel
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->default()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::Full)
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset(RequestPasswordReset::class)
            ->colors([

                //'primary' => Color::Amber,
                'primary' => Color::Blue,      // Azul similar a Bootstrap primary (#0d6efd)
                'secondary' => Color::Zinc,
                'success' => Color::Emerald,   // Verde success (#198754)
                'danger' => Color::Red,        // Rojo danger (#dc3545)
                'warning' => Color::Yellow,    // Amarillo warning (#ffc107)
                'info' => Color::Sky,          // Azul info (#0dcaf0)
                'light' => Color::Gray,         // gris claro aproximado
                'dark' => Color::Slate,
            ])
            ->defaultAvatarProvider(AutenticatedUserAvatar::class)
            //   ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                // CmsContentResource::class,
                UserResource::class,
                OtherExpenseResource::class,
                CustomerResource::class,
                ItemResource::class,
                CategoryResource::class,
                BrandResource::class,
                // SupplierResource::class,
                // UnitOfMeasureResource::class,
                CountryResource::class,
                StateResource::class,
                CityResource::class,
                SaleResource::class,
                OtherExpenseItemResource::class,
            ])
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->plugins([
                FilamentSettingsPlugin::make()
                    ->pages([
                        Settings::class,
                    ]),
                FilamentAuthenticationLogPlugin::make(),
                // FilamentSpatieLaravelBackupPlugin::make()
                FilamentSpatieLaravelBackupPlugin::make()
                    ->usingPage(Backups::class)->authorize(fn(): bool => auth()->user()->email === 'el.solitions@gmail.com'),
                FilamentFullCalendarPlugin::make()->config(
                    []
                ),
            ])
            ->pages([
                Pages\Dashboard::class,
                Profile::class
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
                VentasStats::class,
                OtherExpensesStats::class,
                VentasMensualesChart::class,
                OtherExpensesChart::class,
                GananciasMensualesChart::class,
                VentasPorVendedorPieChart::class,
                VentasPorVendedorPercentPieChart::class,
                GastosPieChart::class,
                GastosPercentPieChart::class,
                ProductosMasVendido::class,
                ProductosMasVendidoPorcentaje::class,
                    // ProductosMenosVendido::class,
                    // ProductosMenosVendidoPorcentaje::class,
                // VentasVsGastosPorDiaChart::class
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
            ->authMiddleware([
                Authenticate::class,
                AuthenticateAndCheckActive::class,
            ]);


        if (Schema::hasTable('settings')) {
            $settings = Setting::first();

            if ($settings && $settings->general) {
                $generalSettings = $settings->general;
                if (!empty($generalSettings->image) && $generalSettings->image != "[]") {
                    $panel->brandLogo(Storage::url(str_replace('"', '', $generalSettings->image)))
                        ->brandLogoHeight('3rem');
                } elseif (!empty($generalSettings->brand_name)) {
                    return $panel->brandName(str_replace('"', '', $generalSettings->brand_name));
                }
            }
        }



        return $panel;
    }

    // Método para registrar el menú del usuario
    public function boot()
    {
        Filament::serving(function () {
            Filament::registerUserMenuItems([
                'profile' => MenuItem::make()
                    ->label('Perfil')
                    ->url(route('filament.admin.pages.profile')) // Aquí también agregamos la URL
                    ->icon('heroicon-o-user'),
                'personal' => MenuItem::make()
                    ->label('Ir a al panel trabajador')
                    ->url(url('/personal')) // tu landing page
                    ->icon('heroicon-o-briefcase'), // ícono opcional
                // ->openUrlInNewTab(), // opcional: abre en nueva pestaña
                'home' => MenuItem::make()
                    ->label('Ir a la Home')
                    ->url(url('/')) // tu landing page
                    ->icon('heroicon-o-globe-alt') // ícono globo terrestre
                    ->openUrlInNewTab(), // opcional: abre en nueva pestaña

            ]);
        });
    }
}
