<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesPageNavigation;
use App\Models\Setting;
use App\Support\FraudCheckSettings as Settings;
use App\Support\OrderSmsSettings;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class FraudCheckSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Fraud check';

    protected static ?string $title = 'Fraud check';

    protected static ?string $slug = 'settings/fraud-check';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.fraud-check-settings';

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
            'fraud_check_enabled' => Settings::enabled(),
            'fraud_provider' => Settings::provider(),
            'fraud_steadfast_api_key' => null,
            'fraud_steadfast_secret_key' => null,
            'fraud_company_name' => Setting::get('fraud_company_name'),
            'fraud_custom_url' => Setting::get('fraud_custom_url'),
            'fraud_custom_method' => Settings::customMethod(),
            'fraud_phone_place' => Settings::phoneInField() ? 'field' : 'link',
            'fraud_phone_field' => Settings::phoneField(),
            'fraud_key_name' => Settings::keyName(),
            'fraud_custom_api_key' => null,
            'fraud_extra_key_name' => Settings::extraKeyName(),
            'fraud_custom_secret' => null,
            'fraud_key_on_link' => Settings::keyOnLink(),
            'fraud_word_total' => Settings::word('fraud_word_total', 'total_parcels'),
            'fraud_word_delivered' => Settings::word('fraud_word_delivered', 'total_delivered'),
            'fraud_word_cancelled' => Settings::word('fraud_word_cancelled', 'total_cancelled'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Outside check')
                    ->description(__('Your own shop history always shows on the order. Turn this on to also ask another company about that mobile number.'))
                    ->schema([
                        Toggle::make('fraud_check_enabled')
                            ->label('Check another company')
                            ->helperText(__('Turn this off to stop the outside lookup. The order stays as it is.')),
                        ToggleButtons::make('fraud_provider')
                            ->label('Company')
                            ->options([
                                'steadfast' => 'Steadfast',
                                'custom' => 'Other company',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group'], merge: true),
                    ]),
                Section::make('Steadfast')
                    ->visible(fn (Get $get): bool => $get('fraud_provider') !== 'custom')
                    ->schema([
                        TextInput::make('fraud_steadfast_api_key')
                            ->label('API key')
                            ->password()
                            ->revealable()
                            ->helperText(__('Leave this blank to keep the saved key.'))
                            ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') !== 'custom' && ! Settings::hasSecret('fraud_steadfast_api_key'))
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('fraud_steadfast_secret_key')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->helperText(__('Leave this blank to keep the saved key.'))
                            ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') !== 'custom' && ! Settings::hasSecret('fraud_steadfast_secret_key'))
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ]),
                Section::make('Other company')
                    ->description(__('Paste the link they gave you. Say where the mobile number goes, and which words in their answer mean total, delivered, and cancelled.'))
                    ->visible(fn (Get $get): bool => $get('fraud_provider') === 'custom')
                    ->schema([
                        TextInput::make('fraud_company_name')
                            ->label('Company name')
                            ->maxLength(40),
                        TextInput::make('fraud_custom_url')
                            ->label('API link')
                            ->placeholder('https://')
                            ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom')
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (filled($value) && OrderSmsSettings::publicHttpUrl($value) === null) {
                                    $fail(__('Use a public https link.'));
                                }
                            }),
                        ToggleButtons::make('fraud_custom_method')
                            ->label('How to send')
                            ->options([
                                'get' => 'GET',
                                'post' => 'POST',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group'], merge: true),
                        ToggleButtons::make('fraud_phone_place')
                            ->label('Where the mobile number goes')
                            ->options([
                                'link' => 'End of the link',
                                'field' => 'Named field',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group'], merge: true),
                        TextInput::make('fraud_phone_field')
                            ->label('Mobile number name')
                            ->helperText(__('The word their form uses for the mobile number.'))
                            ->visible(fn (Get $get): bool => $get('fraud_phone_place') === 'field')
                            ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom' && $get('fraud_phone_place') === 'field')
                            ->maxLength(40),
                        Grid::make(2)->schema([
                            TextInput::make('fraud_key_name')
                                ->label('API key name')
                                ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom')
                                ->maxLength(40),
                            TextInput::make('fraud_custom_api_key')
                                ->label('API key')
                                ->password()
                                ->revealable()
                                ->helperText(__('Leave this blank to keep the saved key.'))
                                ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom' && ! Settings::hasSecret('fraud_custom_api_key'))
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                            TextInput::make('fraud_extra_key_name')
                                ->label('Second key name')
                                ->helperText(__('Leave this empty if they gave you only one key.'))
                                ->maxLength(40),
                            TextInput::make('fraud_custom_secret')
                                ->label('Second key')
                                ->password()
                                ->revealable()
                                ->helperText(__('Leave this blank to keep the saved key.'))
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                        ]),
                        Toggle::make('fraud_key_on_link')
                            ->label('Also send my API key on the link'),
                        Grid::make(3)->schema([
                            TextInput::make('fraud_word_total')
                                ->label('Word for total')
                                ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom')
                                ->maxLength(40),
                            TextInput::make('fraud_word_delivered')
                                ->label('Word for delivered')
                                ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom')
                                ->maxLength(40),
                            TextInput::make('fraud_word_cancelled')
                                ->label('Word for cancelled')
                                ->required(fn (Get $get): bool => (bool) $get('fraud_check_enabled') && $get('fraud_provider') === 'custom')
                                ->maxLength(40),
                        ]),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save')
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

        Settings::save($this->form->getState());
        $this->mount();

        Notification::make()
            ->title(__('Fraud check saved.'))
            ->success()
            ->send();
    }
}
