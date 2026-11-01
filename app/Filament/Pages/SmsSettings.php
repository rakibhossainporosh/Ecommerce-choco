<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesPageNavigation;
use App\Services\OrderSmsNotifier;
use App\Services\SmsService;
use App\Support\OrderSmsSettings;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class SmsSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'SMS notifications';

    protected static ?string $title = 'SMS notifications';

    protected static ?string $slug = 'settings/sms';

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.sms-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $balanceFigure = '…';

    public string $balanceNote = '';

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
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Sending')
                    ->description(__('Turn order SMS on or off, and choose which gateway sends them. Only the selected gateway is used.'))
                    ->schema([
                        Toggle::make('sms_enabled')
                            ->label('Send order SMS')
                            ->helperText(__('Turn this off to stop every SMS. The steps and messages you wrote stay saved.')),
                        ToggleButtons::make('sms_driver')
                            ->label('Send through')
                            ->options([
                                'greenweb' => 'Greenweb',
                                'bulksmsbd' => 'BulkSMSBD',
                                'custom' => 'Other company',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group settings-choice-group-wide'], merge: true),
                    ]),
                Section::make('Greenweb')
                    ->description(__('Paste the Greenweb API token. Leave this blank to keep the saved token.'))
                    ->schema([
                        TextInput::make('greenweb_token')
                            ->label('API token')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->maxWidth(Width::Large)
                            ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'greenweb' && ! OrderSmsSettings::hasCredential('greenweb_token'))
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ]),
                Section::make('BulkSMSBD')
                    ->description(__('Paste the BulkSMSBD API key and the sender ID they approved. Leave the key blank to keep the saved key.'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('bulksmsbd_api_key')
                                ->label('API key')
                                ->password()
                                ->revealable()
                                ->maxLength(255)
                                ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'bulksmsbd' && ! OrderSmsSettings::hasCredential('bulksmsbd_api_key'))
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                            TextInput::make('bulksmsbd_sender_id')
                                ->label('Sender ID')
                                ->helperText(__('The sender name BulkSMSBD approved for your shop.'))
                                ->maxLength(20)
                                ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'bulksmsbd'),
                        ]),
                    ]),
                Section::make('Other company')
                    ->description(__('Use this only if your SMS company is not Greenweb or BulkSMSBD. Copy the names from their document into the boxes. You do not write the SMS here.'))
                    ->schema([
                        TextInput::make('sms_custom_url')
                            ->label('API link')
                            ->placeholder('https://sms.example.com/send')
                            ->maxLength(500)
                            ->maxWidth(Width::ThreeExtraLarge)
                            ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'custom')
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || trim($value) === '') {
                                    return;
                                }

                                if (OrderSmsSettings::publicHttpUrl($value) === null) {
                                    $fail(__('Enter the https link from your SMS company.'));
                                }
                            }),
                        TextInput::make('sms_custom_balance_url')
                            ->label('Balance link')
                            ->placeholder('https://sms.example.com/balance')
                            ->helperText(__('Paste the balance link from their document. Leave this empty if they did not give one. Do not paste the API key into this link.'))
                            ->maxLength(500)
                            ->maxWidth(Width::ThreeExtraLarge)
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || trim($value) === '') {
                                    return;
                                }

                                if (OrderSmsSettings::publicHttpUrl($value) === null) {
                                    $fail(__('Enter the https link from your SMS company.'));
                                }
                            }),
                        ToggleButtons::make('sms_custom_method')
                            ->label('How the request is sent')
                            ->options([
                                'post' => 'POST',
                                'json' => 'JSON',
                                'get' => 'GET',
                            ])
                            ->helperText(__('If their document says POST, leave POST. Change it only when the document says JSON or GET.'))
                            ->grouped()
                            ->required(fn (Get $get): bool => $get('sms_driver') === 'custom')
                            ->default('post')
                            ->extraAttributes(['class' => 'settings-choice-group settings-choice-group-wide'], merge: true),
                        Grid::make(2)->schema([
                            TextInput::make('sms_custom_api_key')
                                ->label('API key')
                                ->password()
                                ->revealable()
                                ->helperText(__('Leave this blank to keep the saved key.'))
                                ->maxLength(255)
                                ->required(function (Get $get): bool {
                                    if (! $get('sms_enabled') || $get('sms_driver') !== 'custom' || OrderSmsSettings::hasCredential('sms_custom_api_key')) {
                                        return false;
                                    }

                                    return filled($get('sms_field_key')) || $this->headerRowsUseKey($get('sms_custom_header_rows'));
                                })
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                            TextInput::make('sms_custom_sender_id')
                                ->label('Sender name')
                                ->helperText(__('Leave this blank if they did not give you a sender name. This is not an email address.'))
                                ->maxLength(32),
                        ]),
                        Grid::make(2)
                            ->extraAttributes(['class' => 'sms-doc-names'], merge: true)
                            ->schema([
                                TextInput::make('sms_field_key')
                                    ->label('Name they use for the API key')
                                    ->placeholder('api_key')
                                    ->helperText(__('Copy this name from their document. The key you typed above is sent under this name.'))
                                    ->maxLength(40)
                                    ->rule($this->gatewayNameRule()),
                                TextInput::make('sms_field_phone')
                                    ->label('Name they use for the mobile number')
                                    ->placeholder('to')
                                    ->helperText(__('The customer mobile number is sent under this name.'))
                                    ->maxLength(40)
                                    ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'custom')
                                    ->rule($this->gatewayNameRule()),
                                TextInput::make('sms_field_message')
                                    ->label('Name they use for the message')
                                    ->placeholder('message')
                                    ->helperText(__('The SMS text is sent under this name.'))
                                    ->maxLength(40)
                                    ->required(fn (Get $get): bool => (bool) $get('sms_enabled') && $get('sms_driver') === 'custom')
                                    ->rule($this->gatewayNameRule()),
                                TextInput::make('sms_field_sender')
                                    ->label('Name they use for the sender')
                                    ->placeholder('senderid')
                                    ->helperText(__('Leave this empty if the document has no sender name.'))
                                    ->maxLength(40)
                                    ->rule($this->gatewayNameRule()),
                            ]),
                        Repeater::make('sms_custom_extra')
                            ->label('Any other box in their document')
                            ->helperText(__('Skip this unless the document has an extra box, such as a name type and the word text.'))
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Name in the document')
                                        ->maxLength(40)
                                        ->required()
                                        ->rule($this->gatewayNameRule()),
                                    TextInput::make('value')
                                        ->label('Word to send')
                                        ->maxLength(100)
                                        ->required(),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->maxItems(10)
                            ->addActionLabel('Add another box')
                            ->addActionAlignment(Alignment::Start)
                            ->reorderable(false)
                            ->extraAttributes(['class' => 'sms-gateway-fields'], merge: true),
                        Repeater::make('sms_custom_header_rows')
                            ->label('Header, only if they ask')
                            ->helperText(__('Most companies do not need this. Add one only if the document says Header or Authorization.'))
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Name in the document')
                                        ->placeholder('Authorization')
                                        ->maxLength(40)
                                        ->required()
                                        ->rule($this->gatewayNameRule()),
                                    TextInput::make('words')
                                        ->label('Words to send')
                                        ->placeholder('Bearer')
                                        ->maxLength(100)
                                        ->required(fn (Get $get): bool => ! (bool) $get('use_key')),
                                ]),
                                Toggle::make('use_key')
                                    ->label('Also send my API key here')
                                    ->default(false),
                            ])
                            ->defaultItems(0)
                            ->maxItems(5)
                            ->addActionLabel('Add a header')
                            ->addActionAlignment(Alignment::Start)
                            ->reorderable(false)
                            ->extraAttributes(['class' => 'sms-gateway-fields'], merge: true),
                    ]),
                Grid::make(2)
                    ->extraAttributes(['class' => 'sms-audience-grid'], merge: true)
                    ->schema([
                        Section::make('Customer SMS')
                            ->description(__('Choose which updates are sent to the customer. The message goes to the mobile number on the order.').' '.__('You can use {name}, {phone}, {number}, {total}, {status}, {address}, {url}, and {shop}. Each one is replaced for that order.').' '.__('One Bangla SMS is about 70 characters. A longer message is split and costs more.'))
                            ->schema($this->audienceFields('customer')),
                        Section::make('Store SMS')
                            ->description(__('Choose which updates are sent to the shop. These go to every number you add here.').' '.__('You can use {name}, {phone}, {number}, {total}, {status}, {address}, {url}, and {shop}. Each one is replaced for that order.'))
                            ->schema([
                                Repeater::make('phones')
                                    ->label('Store numbers')
                                    ->helperText(__('Shop numbers only. Press Add a number for each one.'))
                                    ->simple(
                                        TextInput::make('number')
                                            ->hiddenLabel()
                                            ->tel()
                                            ->placeholder('01XXXXXXXXX')
                                            ->maxLength(20)
                                            ->required()
                                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                                if (! is_string($value) || OrderSmsSettings::canonicalPhone($value) === null) {
                                                    $fail(__('Enter a Bangladeshi mobile number.'));
                                                }
                                            }),
                                    )
                                    ->defaultItems(0)
                                    ->maxItems(10)
                                    ->addActionLabel('Add a number')
                                    ->addActionAlignment(Alignment::Start)
                                    ->reorderable(false)
                                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $phones = is_array($value) ? $value : [];

                                        if (OrderSmsSettings::adminStepsEnabled($get('rules')) && $phones === []) {
                                            $fail(__('Add at least one store number for the store SMS you turned on.'));
                                        }
                                    })
                                    ->extraAttributes(['class' => 'sms-phone-list'], merge: true),
                                ...$this->audienceFields('admin'),
                            ]),
                    ]),
                Section::make('Account SMS')
                    ->description(__('Sent when a customer creates an account. You can use {name}, {shop}, and {url}.'))
                    ->schema([
                        Toggle::make('registered_enabled')
                            ->label('New account'),
                        Grid::make(2)
                            ->extraAttributes(['class' => 'sms-account-grid'], merge: true)
                            ->schema([
                                Textarea::make('registered_message')
                                    ->label('SMS text')
                                    ->rows(3)
                                    ->maxLength(600)
                                    ->required(fn (Get $get): bool => (bool) $get('registered_enabled')),
                            ]),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save SMS settings')
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

        OrderSmsSettings::save($this->form->getState());

        $this->fillForm();

        Notification::make()
            ->title(__('SMS settings saved.'))
            ->success()
            ->send();
    }

    public function loadBalance(): void
    {
        $this->showBalance(false);
    }

    public function refreshBalance(): void
    {
        $this->showBalance(true);
    }

    private function showBalance(bool $fresh): void
    {
        if (! auth()->user()?->can('settings.view') && ! auth()->user()?->can('settings.update')) {
            return;
        }

        $balance = app(SmsService::class)->balance($fresh);
        $amount = $balance['amount'] ?? null;

        $this->balanceFigure = is_string($amount) && $amount !== ''
            ? $this->formatBalance($amount)
            : '—';

        $this->balanceNote = match ($balance['state'] ?? 'failed') {
            'ready' => (string) __('This number comes from your SMS company.'),
            'missing' => (string) __('Save an API key first. Then this shows how many SMS are left.'),
            'unsupported' => (string) __('Add a balance link for this company to see how many SMS are left.'),
            default => (string) __('Could not read the balance. Check the key, then press Refresh.'),
        };
    }

    private function formatBalance(string $amount): string
    {
        $normalized = str_replace(',', '', $amount);

        if (! is_numeric($normalized)) {
            return $amount;
        }

        $decimals = str_contains($normalized, '.') ? 2 : 0;

        return number_format((float) $normalized, $decimals);
    }

    public function sendTest(string $phone): void
    {
        if (! auth()->user()?->can('settings.update')) {
            Notification::make()
                ->title(__('You do not have permission to update settings.'))
                ->danger()
                ->send();

            return;
        }

        if (! OrderSmsSettings::enabled()) {
            Notification::make()
                ->title(__('Turn on order SMS, save, then send a test.'))
                ->warning()
                ->send();

            return;
        }

        if (OrderSmsSettings::canonicalPhone($phone) === null) {
            Notification::make()
                ->title(__('Enter a Bangladeshi mobile number.'))
                ->danger()
                ->send();

            return;
        }

        if (! OrderSmsSettings::gatewayReady()) {
            Notification::make()
                ->title(__('Save a gateway key first. The test did not go to a phone.'))
                ->warning()
                ->send();

            return;
        }

        $sent = app(OrderSmsNotifier::class)->sendTest($phone);

        Notification::make()
            ->title($sent
                ? __('Test SMS sent.')
                : __('Could not send the test SMS. Check the gateway, then save and try again.'))
            ->color($sent ? 'success' : 'danger')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Send a test SMS')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->form([
                    TextInput::make('phone')
                        ->label('Test mobile number')
                        ->tel()
                        ->placeholder('01XXXXXXXXX')
                        ->required()
                        ->maxLength(20),
                ])
                ->action(function (array $data): void {
                    $this->sendTest((string) ($data['phone'] ?? ''));
                }),
        ];
    }

    protected function fillForm(): void
    {
        $driver = OrderSmsSettings::driver();
        $messages = [];

        foreach (array_keys(OrderSmsSettings::steps()) as $step) {
            $messages[$step] = [
                'customer' => OrderSmsSettings::message($step, 'customer'),
                'admin' => OrderSmsSettings::message($step, 'admin'),
            ];
        }

        $this->form->fill([
            'sms_enabled' => OrderSmsSettings::enabled(),
            'sms_driver' => in_array($driver, ['greenweb', 'bulksmsbd', 'custom'], true) ? $driver : 'greenweb',
            'greenweb_token' => null,
            'bulksmsbd_api_key' => null,
            'bulksmsbd_sender_id' => OrderSmsSettings::credential('bulksmsbd_sender_id'),
            'sms_custom_url' => OrderSmsSettings::customUrl(),
            'sms_custom_balance_url' => OrderSmsSettings::customBalanceUrl(),
            'sms_custom_method' => OrderSmsSettings::customMethod(),
            'sms_custom_api_key' => null,
            'sms_custom_sender_id' => OrderSmsSettings::credential('sms_custom_sender_id'),
            ...OrderSmsSettings::formFields(),
            'phones' => OrderSmsSettings::phones() === []
                ? []
                : array_map(
                    fn (string $phone): array => ['number' => $phone],
                    OrderSmsSettings::phones(),
                ),
            'rules' => OrderSmsSettings::rules(),
            'messages' => $messages,
            'registered_enabled' => OrderSmsSettings::rule('registered')['customer'],
            'registered_message' => OrderSmsSettings::message('registered', 'customer'),
        ]);
    }

    /**
     * @return array<int, Toggle|Textarea>
     */
    private function audienceFields(string $audience): array
    {
        $fields = [];

        foreach (OrderSmsSettings::steps() as $step => $label) {
            $fields[] = Toggle::make("rules.{$step}.{$audience}")
                ->label($label);
            $fields[] = Textarea::make("messages.{$step}.{$audience}")
                ->label('SMS text')
                ->rows(3)
                ->maxLength(600)
                ->required(fn (Get $get): bool => (bool) $get("rules.{$step}.{$audience}"));
        }

        return $fields;
    }

    private function gatewayNameRule(): Closure
    {
        return fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/', trim($value)) !== 1) {
                $fail(__('Use the field name from your SMS company. Letters, numbers, and dashes only.'));
            }
        };
    }

    private function headerRowsUseKey(mixed $rows): bool
    {
        if (! is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (is_array($row) && ! empty($row['use_key'])) {
                return true;
            }
        }

        return false;
    }
}
