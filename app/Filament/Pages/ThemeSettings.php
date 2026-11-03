<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Filament\Concerns\TranslatesPageNavigation;
use App\Models\Setting;
use App\Support\StorefrontTheme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
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
        $this->form->fill([
            'storefront_theme' => StorefrontTheme::key(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Shop theme')
                    ->compact()
                    ->description('Warm keeps today’s shop. Grove and Harbor swap the whole storefront design — header, hero, catalog, product page, cart drawer, and footer. Order logic and admin stay the same.')
                    ->schema([
                        Radio::make('storefront_theme')
                            ->label('Theme')
                            ->options(StorefrontTheme::catalog())
                            ->descriptions([
                                'warm' => 'Soft cream boutique. Current default layout.',
                                'grove' => 'Editorial boutique — centered brand, overlay hero, soft product page, green.',
                                'harbor' => 'Marketplace — utility bar, dense catalog, sharp product page, navy.',
                            ])
                            ->required(),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save theme')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('primary')
                        ->action('save'),
                ])->alignment(Alignment::End),
            ]);
    }

    public function save(): void
    {
        if (! auth()->user()?->can('settings.update')) {
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

        Notification::make()
            ->title('Theme saved.')
            ->success()
            ->send();
    }
}
