<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Filament\Concerns\TranslatesPageNavigation;
use App\Models\Setting;
use App\Support\StorefrontTheme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class ThemeSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Themes';

    protected static ?string $title = 'Themes';

    protected static ?string $slug = 'settings/themes';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.theme-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $liveTheme = 'warm';

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('settings.view') || auth()->user()?->can('settings.update'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->liveTheme = StorefrontTheme::key();

        $this->form->fill([
            'storefront_theme' => $this->liveTheme,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $canUpdate = $this->canUpdateThemes();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Shop theme')
                    ->compact()
                    ->description($canUpdate
                        ? 'Pick a preview, then save to publish it to the live storefront. Checkout, orders, and admin stay the same.'
                        : 'You can preview themes here. Ask an admin to publish a change.')
                    ->schema([
                        Hidden::make('storefront_theme')
                            ->required()
                            ->rule('in:'.implode(',', array_keys(StorefrontTheme::catalog())))
                            ->disabled(fn (): bool => ! $this->canUpdateThemes()),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save theme')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('primary')
                        ->visible(fn (): bool => $this->canUpdateThemes())
                        ->disabled(fn (): bool => ! $this->hasUnsavedThemeChanges())
                        ->action('save'),
                ])->alignment(Alignment::End),
            ]);
    }

    public function canUpdateThemes(): bool
    {
        return (bool) auth()->user()?->can('settings.update');
    }

    public function hasUnsavedThemeChanges(): bool
    {
        $selected = is_string($this->data['storefront_theme'] ?? null)
            ? $this->data['storefront_theme']
            : $this->liveTheme;

        return $selected !== $this->liveTheme;
    }

    public function selectedThemeKey(): string
    {
        $selected = $this->data['storefront_theme'] ?? $this->liveTheme;

        return is_string($selected) && isset(StorefrontTheme::catalog()[$selected])
            ? $selected
            : $this->liveTheme;
    }

    public function save(): void
    {
        if (! $this->canUpdateThemes()) {
            Notification::make()
                ->title(__('You do not have permission to update settings.'))
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $key = is_string($state['storefront_theme'] ?? null) ? $state['storefront_theme'] : 'warm';

        if (! isset(StorefrontTheme::catalog()[$key])) {
            $key = 'warm';
        }

        $setting = Setting::query()->firstOrNew(['key' => 'storefront_theme']);
        $setting->fill([
            'label' => 'storefront theme',
            'value' => $key,
            'type' => SettingType::String,
            'group' => 'theme',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget('setting.storefront_theme');

        $this->liveTheme = $key;
        $this->form->fill([
            'storefront_theme' => $key,
        ]);

        Notification::make()
            ->title('Theme saved.')
            ->success()
            ->send();
    }
}
