<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class FooterSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $title = 'Website Settings';

    protected static ?string $slug = 'settings/website';

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

        $this->form->fill([
            'site_name' => Setting::get('site_name', 'Nobo Shop'),
            'site_tagline' => Setting::get('site_tagline', 'আপনার প্রতিদিনের পছন্দ'),
            'site_logo' => Setting::get('site_logo'),
            'site_favicon' => Setting::get('site_favicon'),
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
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('WebsiteSettingsTabs')
                    ->tabs([
                        Tab::make('General & Branding')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
                                Section::make('Brand Identity & Logo')
                                    ->description('Configure your store brand identity, official logo, favicon, and bio shown across the website.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('site_name')
                                                ->label('Store / Brand Name')
                                                ->placeholder('e.g. Nobo Shop')
                                                ->helperText('The primary name of your ecommerce store.')
                                                ->required(),
                                            TextInput::make('site_tagline')
                                                ->label('Tagline / Slogan')
                                                ->placeholder('e.g. আপনার প্রতিদিনের পছন্দ')
                                                ->helperText('Catchy tagline shown beneath the logo or in browser metadata.')
                                                ->nullable(),
                                        ]),
                                        Grid::make(2)->schema([
                                            FileUpload::make('site_logo')
                                                ->label('Website Official Logo')
                                                ->image()
                                                ->disk('public')
                                                ->directory('settings/branding')
                                                ->visibility('public')
                                                ->maxSize(5120)
                                                ->imageEditor()
                                                ->openable()
                                                ->downloadable()
                                                ->deletable()
                                                ->helperText('PNG, JPG, WebP, SVG, AVIF (Max: 5MB). লোগো পরিবর্তন করতে চাইলে নতুন ছবি সিলেক্ট করুন বা ক্রস চিহ্নে ক্লিক করে রিমুভ করুন।'),
                                            FileUpload::make('site_favicon')
                                                ->label('Browser Favicon Icon')
                                                ->image()
                                                ->disk('public')
                                                ->directory('settings/branding')
                                                ->visibility('public')
                                                ->maxSize(2048)
                                                ->imageEditor()
                                                ->openable()
                                                ->downloadable()
                                                ->deletable()
                                                ->helperText('Square 1:1 icon (PNG, ICO, SVG, WebP, Max: 2MB). ফেভিকন পরিবর্তন করতে চাইলে নতুন ফাইল সিলেক্ট করুন বা ক্রস চিহ্নে ক্লিক করে রিমুভ করুন।'),
                                        ]),
                                        Textarea::make('footer_about')
                                            ->label('Brand Bio / About Text')
                                            ->placeholder('Write a brief overview of your store and commitment to customers...')
                                            ->rows(3)
                                            ->helperText('This introduction text appears directly under your brand logo in the footer.')
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('Navigation Menus')
                            ->icon(Heroicon::OutlinedBars3BottomLeft)
                            ->schema([
                                Section::make('Quick Links Menu')
                                    ->description('Manage primary navigation links for quick access to store sections.')
                                    ->schema([
                                        TextInput::make('footer_col2_title')
                                            ->label('Menu Column Title')
                                            ->placeholder('প্রয়োজনীয় লিংক')
                                            ->helperText('Header displayed above this link group in the footer.')
                                            ->required(),
                                        Repeater::make('footer_col2_links')
                                            ->label('Navigation Links')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Menu Title')
                                                    ->placeholder('e.g. আমাদের সম্পর্কে')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Destination URL / Path')
                                                    ->placeholder('/about-us')
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('+ Add Navigation Link')
                                            ->reorderable()
                                            ->collapsible(),
                                    ]),

                                Section::make('Customer Service & Policies Menu')
                                    ->description('Manage customer assistance, policy, and information page links.')
                                    ->schema([
                                        TextInput::make('footer_col3_title')
                                            ->label('Menu Column Title')
                                            ->placeholder('গ্রাহক সেবা')
                                            ->helperText('Header displayed above this policy group in the footer.')
                                            ->required(),
                                        Repeater::make('footer_col3_links')
                                            ->label('Service & Policy Links')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Policy Title')
                                                    ->placeholder('e.g. রিটার্ন পলিসি')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Destination URL / Path')
                                                    ->placeholder('/return-policy')
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('+ Add Policy Link')
                                            ->reorderable()
                                            ->collapsible(),
                                    ]),
                            ]),

                        Tab::make('Contact & Support')
                            ->icon(Heroicon::OutlinedPhone)
                            ->schema([
                                Section::make('Official Store Contact Information')
                                    ->description('Public contact details displayed for customer assistance, orders, and inquiries.')
                                    ->schema([
                                        TextInput::make('footer_col4_title')
                                            ->label('Section Title')
                                            ->placeholder('যোগাযোগ')
                                            ->required(),
                                        Textarea::make('contact_address')
                                            ->label('Store / Office Physical Address')
                                            ->placeholder('e.g. ৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ')
                                            ->rows(2)
                                            ->helperText('Official head office or pickup showroom address.')
                                            ->required(),
                                        Grid::make(2)->schema([
                                            TextInput::make('contact_phone')
                                                ->label('Customer Support Hotline')
                                                ->placeholder('+৮৮০ ৫২৩৪ ৫৬৭৮৯০')
                                                ->helperText('Direct phone or helpline number for customer inquiries.')
                                                ->required(),
                                            TextInput::make('contact_email')
                                                ->label('Official Support Email')
                                                ->placeholder('support@noboshop.com')
                                                ->email()
                                                ->helperText('Inquiries sent through contact forms will be directed here.')
                                                ->required(),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Social Channels')
                            ->icon(Heroicon::OutlinedShare)
                            ->schema([
                                Section::make('Social Media Accounts')
                                    ->description('Connect your official social media pages. Icons without URLs are automatically hidden.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('facebook_url')
                                                ->label('Facebook Page / Group URL')
                                                ->placeholder('https://facebook.com/your-brand')
                                                ->prefix('https://')
                                                ->nullable(),
                                            TextInput::make('twitter_url')
                                                ->label('X (Twitter) Profile URL')
                                                ->placeholder('https://twitter.com/your-brand')
                                                ->prefix('https://')
                                                ->nullable(),
                                            TextInput::make('instagram_url')
                                                ->label('Instagram Profile URL')
                                                ->placeholder('https://instagram.com/your-brand')
                                                ->prefix('https://')
                                                ->nullable(),
                                            TextInput::make('youtube_url')
                                                ->label('YouTube Channel URL')
                                                ->placeholder('https://youtube.com/@your-brand')
                                                ->prefix('https://')
                                                ->nullable(),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Legal & Payments')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                Section::make('Legal Copyright & Payment Trust Badges')
                                    ->description('Configure bottom copyright notices and accepted payment gateway trust badges.')
                                    ->schema([
                                        TextInput::make('footer_copyright')
                                            ->label('Copyright Notice')
                                            ->placeholder('© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।')
                                            ->helperText('Note: {year} is automatically replaced with the current year dynamically.')
                                            ->required(),
                                        TagsInput::make('footer_payment_methods')
                                            ->label('Accepted Payment Method Badges')
                                            ->placeholder('Add payment method and press Enter')
                                            ->helperText('Badges displayed at footer bottom (e.g. bKash, Nagad, Rocket, Upay, VISA, Mastercard, COD).'),
                                    ]),
                            ]),
                    ]),

                Actions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('primary')
                        ->action('save'),
                ])->alignment(Alignment::End),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
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

            if (in_array($key, ['site_name', 'site_tagline', 'site_logo', 'site_favicon'])) {
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
            ->title('Website settings saved successfully!')
            ->success()
            ->send();
    }

    protected static function getSettingLabel(string $key): string
    {
        return match ($key) {
            'site_name' => 'Site Name',
            'site_tagline' => 'Site Tagline',
            'site_logo' => 'Website Logo',
            'site_favicon' => 'Website Favicon',
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
