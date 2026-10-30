<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class FooterSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Footer Settings';

    protected static ?string $title = 'Footer Settings';

    protected static ?string $slug = 'settings/footer';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.footer-settings';

    /**
     * @var array<string, mixed> | null
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

    protected function fillForm(): void
    {
        $defaultCol2Links = [
            ['label' => 'আমাদের সম্পর্কে', 'url' => '/about-us'],
            ['label' => 'যোগাযোগ', 'url' => '/contact'],
            ['label' => 'অর্ডার ট্র্যাকিং', 'url' => '/track-order'],
            ['label' => 'অফার সমূহ', 'url' => '/?offer=1#featured'],
        ];

        $defaultCol3Links = [
            ['label' => 'রিটার্ন পলিসি', 'url' => '/return-policy'],
            ['label' => 'প্রাইভেসি পলিসি', 'url' => '/privacy-policy'],
            ['label' => 'শর্তাবলী', 'url' => '/terms-conditions'],
            ['label' => 'সাধারণ জিজ্ঞাসা (FAQ)', 'url' => '/faq'],
        ];

        $col2Links = Setting::get('footer_col2_links');
        $col3Links = Setting::get('footer_col3_links');
        $paymentMethods = Setting::get('footer_payment_methods');

        $this->data = [
            'site_name' => Setting::get('site_name', 'Nobo Shop'),
            'site_tagline' => Setting::get('site_tagline', 'আপনার প্রতিদিনের পছন্দ'),
            'footer_about' => Setting::get('footer_about', 'আপনার প্রতিদিনের পছন্দ — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।'),
            'facebook_url' => Setting::get('facebook_url', 'https://facebook.com'),
            'twitter_url' => Setting::get('twitter_url', 'https://twitter.com'),
            'instagram_url' => Setting::get('instagram_url', 'https://instagram.com'),
            'youtube_url' => Setting::get('youtube_url', 'https://youtube.com'),
            'footer_col2_title' => Setting::get('footer_col2_title', 'প্রয়োজনীয় লিংক'),
            'footer_col2_links' => is_array($col2Links) ? $col2Links : $defaultCol2Links,
            'footer_col3_title' => Setting::get('footer_col3_title', 'গ্রাহক সেবা'),
            'footer_col3_links' => is_array($col3Links) ? $col3Links : $defaultCol3Links,
            'footer_col4_title' => Setting::get('footer_col4_title', 'যোগাযোগ'),
            'contact_address' => Setting::get('contact_address', '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ'),
            'contact_phone' => Setting::get('contact_phone', '+৮৮০ ৫২৩৪ ৫৬৭৮৯০'),
            'contact_email' => Setting::get('contact_email', 'support@noboshop.com'),
            'footer_copyright' => Setting::get('footer_copyright', '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।'),
            'footer_payment_methods' => is_array($paymentMethods) ? $paymentMethods : ['bKash', 'Nagad', 'VISA'],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Brand & Description')
                    ->description('Store name, logo tagline, and bio in the first column of the footer')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('site_name')
                                ->label('Store Name')
                                ->required(),
                            TextInput::make('site_tagline')
                                ->label('Tagline')
                                ->nullable(),
                        ]),
                        Textarea::make('footer_about')
                            ->label('Footer About Text')
                            ->rows(3)
                            ->helperText('This text appears below the store brand in the first column of the footer')
                            ->required(),
                    ]),

                Section::make('Social Media Links')
                    ->description('Facebook, Twitter / X, Instagram, and YouTube links (leave empty to hide the icon)')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('facebook_url')
                                ->label('Facebook URL')
                                ->nullable(),
                            TextInput::make('twitter_url')
                                ->label('Twitter / X URL')
                                ->nullable(),
                            TextInput::make('instagram_url')
                                ->label('Instagram URL')
                                ->nullable(),
                            TextInput::make('youtube_url')
                                ->label('YouTube URL')
                                ->nullable(),
                        ]),
                    ]),

                Section::make('Column 2: Quick Links')
                    ->description('Second column title and navigation links (add, edit, or reorder)')
                    ->collapsible()
                    ->schema([
                        TextInput::make('footer_col2_title')
                            ->label('Column Title')
                            ->required(),
                        Repeater::make('footer_col2_links')
                            ->label('Links')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Link Label')
                                    ->required(),
                                TextInput::make('url')
                                    ->label('URL / Path')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Link')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Section::make('Column 3: Customer Service')
                    ->description('Third column title and policy/service links')
                    ->collapsible()
                    ->schema([
                        TextInput::make('footer_col3_title')
                            ->label('Column Title')
                            ->required(),
                        Repeater::make('footer_col3_links')
                            ->label('Links')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Link Label')
                                    ->required(),
                                TextInput::make('url')
                                    ->label('URL / Path')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Link')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Section::make('Column 4: Contact Info')
                    ->description('Fourth column title, address, phone number, and email')
                    ->collapsible()
                    ->schema([
                        TextInput::make('footer_col4_title')
                            ->label('Column Title')
                            ->required(),
                        Textarea::make('contact_address')
                            ->label('Address')
                            ->rows(2)
                            ->required(),
                        Grid::make(2)->schema([
                            TextInput::make('contact_phone')
                                ->label('Phone Number')
                                ->required(),
                            TextInput::make('contact_email')
                                ->label('Email Address')
                                ->email()
                                ->required(),
                        ]),
                    ]),

                Section::make('Copyright & Payment Badges')
                    ->description('Footer bottom copyright message and payment method badges')
                    ->collapsible()
                    ->schema([
                        TextInput::make('footer_copyright')
                            ->label('Copyright Text')
                            ->helperText('{year} is automatically replaced with the current year')
                            ->required(),
                        TagsInput::make('footer_payment_methods')
                            ->label('Payment Badges')
                            ->helperText('Type a payment method name and press Enter (e.g. bKash, Nagad, Rocket, Upay, VISA, Mastercard)'),
                    ]),

                Actions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('primary')
                        ->action('save'),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon(Heroicon::OutlinedCheck)
                ->action('save'),
        ];
    }

    public function save(): void
    {
        if (! auth()->user()?->can('settings.update')) {
            Notification::make()
                ->title('You do not have permission to update settings.')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();

        foreach ($state as $key => $val) {
            $type = SettingType::String;
            $group = 'footer';

            if (in_array($key, ['site_name', 'site_tagline'])) {
                $group = 'general';
            } elseif (in_array($key, ['facebook_url', 'twitter_url', 'instagram_url', 'youtube_url'])) {
                $group = 'social';
            } elseif (in_array($key, ['contact_address', 'contact_phone', 'contact_email'])) {
                $group = 'contact';
            }

            if (in_array($key, ['footer_col2_links', 'footer_col3_links', 'footer_payment_methods'])) {
                $type = SettingType::Json;
                $val = is_array($val) ? json_encode(array_values($val), JSON_UNESCAPED_UNICODE) : $val;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'label' => static::getSettingLabel($key),
                    'value' => $val,
                    'type' => $type,
                    'group' => $group,
                    'is_public' => true,
                ]
            );

            Cache::forget("setting.{$key}");
        }

        Notification::make()
            ->title('Footer settings saved successfully!')
            ->success()
            ->send();
    }

    protected static function getSettingLabel(string $key): string
    {
        return match ($key) {
            'site_name' => 'Site Name',
            'site_tagline' => 'Site Tagline',
            'footer_about' => 'Footer About Description',
            'facebook_url' => 'Facebook URL',
            'twitter_url' => 'Twitter / X URL',
            'instagram_url' => 'Instagram URL',
            'youtube_url' => 'YouTube URL',
            'footer_col2_title' => 'Footer Column 2 Title',
            'footer_col2_links' => 'Footer Column 2 Links',
            'footer_col3_title' => 'Footer Column 3 Title',
            'footer_col3_links' => 'Footer Column 3 Links',
            'footer_col4_title' => 'Footer Column 4 Title',
            'contact_address' => 'Contact Address',
            'contact_phone' => 'Contact Phone',
            'contact_email' => 'Contact Email',
            'footer_copyright' => 'Footer Copyright Text',
            'footer_payment_methods' => 'Footer Payment Methods',
            default => str_replace('_', ' ', ucwords($key, '_')),
        };
    }
}
