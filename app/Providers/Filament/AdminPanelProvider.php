<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetApplicationLocale;
use App\Models\Setting;
use App\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\BasePage;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Create / Edit record pages: actions sit under the whole form (not sticky float).
        BasePage::alignFormActionsEnd();

        Table::configureUsing(function (Table $table): void {
            $table
                // Defer heavy table queries in the real admin UI; keep eager in tests/assertions.
                ->deferLoading(fn (): bool => ! app()->runningUnitTests())
                ->defaultPaginationPageOption(10)
                ->paginationPageOptions([10, 25, 50])
                ->emptyStateIcon(Heroicon::OutlinedInbox)
                ->emptyStateHeading(fn (Table $table): string => __('No :label found', ['label' => $table->getPluralModelLabel()]))
                ->emptyStateDescription(fn (Table $table): string => __('There are currently no :label recorded in the system. New entries will show up here.', ['label' => $table->getPluralModelLabel()]));
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action
                ->modalFooterActionsAlignment(Alignment::End)
                ->successNotificationTitle(fn (CreateAction $a): string => filled($m = $a->getModelLabel()) ? __(':label created successfully', ['label' => $m]) : __('Created successfully'));
        });

        EditAction::configureUsing(function (EditAction $action): void {
            $action
                ->modalFooterActionsAlignment(Alignment::End)
                ->successNotificationTitle(fn (EditAction $a): string => filled($m = $a->getModelLabel()) ? __(':label updated successfully', ['label' => $m]) : __('Changes saved successfully'));
        });

        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action->successNotificationTitle(fn (DeleteAction $a): string => filled($m = $a->getModelLabel()) ? __(':label deleted successfully', ['label' => $m]) : __('Deleted successfully'));
        });

        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
            $action->successNotificationTitle(fn (DeleteBulkAction $a): string => filled($m = $a->getPluralModelLabel()) ? __('Selected :label deleted successfully', ['label' => $m]) : __('Selected records deleted successfully'));
        });

        RestoreAction::configureUsing(function (RestoreAction $action): void {
            $action->successNotificationTitle(fn (RestoreAction $a): string => filled($m = $a->getModelLabel()) ? __(':label restored successfully', ['label' => $m]) : __('Restored successfully'));
        });

        ForceDeleteAction::configureUsing(function (ForceDeleteAction $action): void {
            $action->successNotificationTitle(fn (ForceDeleteAction $a): string => filled($m = $a->getModelLabel()) ? __(':label permanently deleted', ['label' => $m]) : __('Permanently deleted'));
        });

        ReplicateAction::configureUsing(function (ReplicateAction $action): void {
            $action->successNotificationTitle(fn (ReplicateAction $a): string => filled($m = $a->getModelLabel()) ? __(':label duplicated successfully', ['label' => $m]) : __('Duplicated successfully'));
        });

        FileUpload::configureUsing(function (FileUpload $upload): void {
            if (app()->getLocale() !== 'bn' || filled($upload->getPlaceholder())) {
                return;
            }

            $upload->placeholder('ফাইল এখানে টেনে আনুন অথবা <span class="filepond--label-action">বেছে নিন</span>');
        });

        DateTimePicker::configureUsing(function (DateTimePicker $picker): void {
            $picker
                ->native(false)
                ->displayFormat(fn (DateTimePicker $component): string => $component->hasTime() ? 'd/m/Y H:i' : 'd/m/Y')
                ->placeholder(fn (DateTimePicker $component): string => app()->getLocale() === 'bn'
                    ? ($component->hasTime() ? 'দিন/মাস/বছর, সময়' : 'দিন/মাস/বছর')
                    : ($component->hasTime() ? 'dd/mm/yyyy, time' : 'dd/mm/yyyy'));
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
            ->spa(hasPrefetching: true)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('14.5rem')
            ->collapsedSidebarWidth('4.5rem')
            ->maxContentWidth(Width::Full)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.styles.admin-layout')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_START,
                fn (): string => view('filament.components.sidebar-header')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.components.topbar-heading')->render(),
            )
            ->navigationGroups([
                NavigationGroup::make(fn (): string => UiText::line('Sales')),
                NavigationGroup::make(fn (): string => UiText::line('Catalog')),
                NavigationGroup::make(fn (): string => UiText::line('Customers & Marketing')),
                NavigationGroup::make(fn (): string => UiText::line('Shipping')),
                NavigationGroup::make(fn (): string => UiText::line('System')),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetApplicationLocale::class,
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
