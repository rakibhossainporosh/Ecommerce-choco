<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->emptyStateIcon(Heroicon::OutlinedInbox)
                ->emptyStateHeading(fn (Table $table): string => 'No '.$table->getPluralModelLabel().' Found')
                ->emptyStateDescription(fn (Table $table): string => 'There are currently no '.strtolower($table->getPluralModelLabel()).' recorded in the system. New entries will show up here.');
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action->successNotificationTitle(fn (CreateAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' created successfully' : 'Created successfully');
        });

        EditAction::configureUsing(function (EditAction $action): void {
            $action->successNotificationTitle(fn (EditAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' updated successfully' : 'Changes saved successfully');
        });

        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action->successNotificationTitle(fn (DeleteAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' deleted successfully' : 'Deleted successfully');
        });

        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
            $action->successNotificationTitle(fn (DeleteBulkAction $a): string => filled($m = $a->getPluralModelLabel()) ? 'Selected '.strtolower($m).' deleted successfully' : 'Selected records deleted successfully');
        });

        RestoreAction::configureUsing(function (RestoreAction $action): void {
            $action->successNotificationTitle(fn (RestoreAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' restored successfully' : 'Restored successfully');
        });

        ForceDeleteAction::configureUsing(function (ForceDeleteAction $action): void {
            $action->successNotificationTitle(fn (ForceDeleteAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' permanently deleted' : 'Permanently deleted');
        });

        ReplicateAction::configureUsing(function (ReplicateAction $action): void {
            $action->successNotificationTitle(fn (ReplicateAction $a): string => filled($m = $a->getModelLabel()) ? ucfirst($m).' duplicated successfully' : 'Duplicated successfully');
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => Setting::get('site_name', config('app.name', 'Nobo Shop')))
            ->brandLogo(function () {
                $logo = Setting::get('site_logo');

                return $logo ? asset('storage/'.ltrim($logo, '/')) : null;
            })
            ->brandLogoHeight('2.5rem')
            ->favicon(function () {
                $favicon = Setting::get('site_favicon');

                return $favicon ? asset('storage/'.ltrim($favicon, '/')) : null;
            })
            ->font('Poppins')
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'purple' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('14.5rem')
            ->collapsedSidebarWidth('4.5rem')
            ->maxContentWidth(Width::Full)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render("@include('filament.styles.admin-layout')"),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_START,
                fn (): string => Blade::render("@include('filament.components.sidebar-header')"),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render("@include('filament.components.topbar-heading')"),
            )
            ->navigationGroups([
                NavigationGroup::make('Sales'),
                NavigationGroup::make('Catalog'),
                NavigationGroup::make('Customers & Marketing'),
                NavigationGroup::make('Shipping'),
                NavigationGroup::make('System'),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
