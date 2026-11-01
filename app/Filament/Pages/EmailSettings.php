<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesPageNavigation;
use App\Models\Setting;
use App\Services\OrderMailer;
use App\Support\OrderMailSettings;
use BackedEnum;
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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EmailSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Email notifications';

    protected static ?string $title = 'Email notifications';

    protected static ?string $slug = 'settings/email';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.email-settings';

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
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Sending')
                    ->description(__('Turn order emails on or off, and choose which mailbox sends them. Only the selected mailbox is used.'))
                    ->schema([
                        Toggle::make('mail_enabled')
                            ->label('Send order emails')
                            ->helperText(__('Turn this off to stop every order email. The steps you chose stay saved.')),
                        ToggleButtons::make('mail_provider')
                            ->label('Send from')
                            ->options([
                                'gmail' => 'Gmail',
                                'webmail' => 'Webmail',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group'], merge: true),
                    ]),
                Section::make('Gmail')
                    ->description(__('Use a Gmail address that has 2-step verification turned on. Google needs an app password, not the normal Gmail password.'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('mail_gmail_address')
                                ->label('Gmail address')
                                ->email()
                                ->placeholder('shop@gmail.com')
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'gmail')
                                ->maxLength(255),
                            TextInput::make('mail_gmail_password')
                                ->label('App password')
                                ->password()
                                ->revealable()
                                ->helperText(__('Create an app password in the Google account and paste it here. Leave this blank to keep the saved password.'))
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'gmail' && ! OrderMailSettings::hasSecret('mail_gmail_password'))
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                        ]),
                    ]),
                Section::make('Webmail')
                    ->description(__('Use the mailbox from your hosting webmail. TLS usually uses port 587. SSL usually uses port 465.'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('mail_webmail_address')
                                ->label('Mailbox address')
                                ->email()
                                ->placeholder('orders@noboshop.com')
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'webmail')
                                ->maxLength(255),
                            TextInput::make('mail_webmail_password')
                                ->label('Mailbox password')
                                ->password()
                                ->revealable()
                                ->helperText(__('Leave this blank to keep the saved password.'))
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'webmail' && ! OrderMailSettings::hasSecret('mail_webmail_password'))
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                            TextInput::make('mail_webmail_host')
                                ->label('Mail host')
                                ->placeholder('mail.noboshop.com')
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'webmail')
                                ->maxLength(255),
                            TextInput::make('mail_webmail_port')
                                ->label('Port')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(65535)
                                ->required(fn (Get $get): bool => (bool) $get('mail_enabled') && $get('mail_provider') === 'webmail')
                                ->default(587),
                        ]),
                        ToggleButtons::make('mail_webmail_encryption')
                            ->label('Security')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                            ])
                            ->grouped()
                            ->required()
                            ->extraAttributes(['class' => 'settings-choice-group'], merge: true),
                    ]),
                Grid::make(2)
                    ->extraAttributes(['class' => 'mail-audience-grid'], merge: true)
                    ->schema([
                        Section::make('Customer emails')
                            ->description(__('Choose which updates are emailed to the customer. The message goes to the email on the order.'))
                            ->schema($this->audienceToggles('customer')),
                        Section::make('Store emails')
                            ->description(__('Choose which updates are emailed to the shop. These go only to the store inbox.'))
                            ->schema([
                                TextInput::make('mail_admin_address')
                                    ->label('Store email address')
                                    ->email()
                                    ->placeholder('orders@noboshop.com')
                                    ->maxLength(255)
                                    ->maxWidth(Width::ExtraSmall),
                                ...$this->audienceToggles('admin'),
                            ]),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save email settings')
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

        OrderMailSettings::save($this->form->getState());

        $this->fillForm();

        Notification::make()
            ->title(__('Email settings saved.'))
            ->success()
            ->send();
    }

    public function sendTest(): void
    {
        if (! auth()->user()?->can('settings.update')) {
            Notification::make()
                ->title(__('You do not have permission to update settings.'))
                ->danger()
                ->send();

            return;
        }

        if (! OrderMailSettings::enabled()) {
            Notification::make()
                ->title(__('Turn on order emails, save, then send a test.'))
                ->warning()
                ->send();

            return;
        }

        $sent = app(OrderMailer::class)->sendTest();

        Notification::make()
            ->title($sent
                ? __('Test email sent.')
                : __('Could not send the test email. Check the mailbox, then save and try again.'))
            ->color($sent ? 'success' : 'danger')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Send a test email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->action('sendTest'),
        ];
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'mail_enabled' => OrderMailSettings::enabled(),
            'mail_provider' => OrderMailSettings::provider(),
            'mail_gmail_address' => Setting::get('mail_gmail_address'),
            'mail_gmail_password' => null,
            'mail_webmail_address' => Setting::get('mail_webmail_address'),
            'mail_webmail_password' => null,
            'mail_webmail_host' => Setting::get('mail_webmail_host'),
            'mail_webmail_port' => (int) Setting::get('mail_webmail_port', 587),
            'mail_webmail_encryption' => Setting::get('mail_webmail_encryption', 'tls') === 'ssl' ? 'ssl' : 'tls',
            'mail_admin_address' => Setting::get('mail_admin_address', Setting::get('contact_email')),
            'rules' => OrderMailSettings::rules(),
        ]);
    }

    /**
     * @return array<int, Toggle>
     */
    private function audienceToggles(string $audience): array
    {
        $toggles = [];

        foreach (OrderMailSettings::steps() as $step => $label) {
            $toggles[] = Toggle::make("rules.{$step}.{$audience}")
                ->label($label);
        }

        return $toggles;
    }
}
