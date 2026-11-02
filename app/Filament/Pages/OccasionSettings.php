<?php

namespace App\Filament\Pages;

use App\Enums\SettingType;
use App\Filament\Concerns\TranslatesPageNavigation;
use App\Models\Product;
use App\Models\Setting;
use App\Support\StorefrontOccasion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OccasionSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Festivals';

    protected static ?string $title = 'Festivals';

    protected static ?string $slug = 'settings/festivals';

    protected static ?int $navigationSort = 24;

    protected string $view = 'filament.pages.occasion-settings';

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
        $sections = StorefrontOccasion::sections();

        $this->form->fill([
            'occasion_enabled' => filter_var(Setting::get('occasion_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'occasion_key' => Setting::get('occasion_key', 'eid_fitr') ?: 'eid_fitr',
            'occasion_general' => $sections['general'],
            'occasion_hero' => $sections['hero'],
            'occasion_categories' => $sections['categories'],
            'occasion_products' => $sections['products'],
            'occasion_starts_at' => Setting::get('occasion_starts_at', '') ?: null,
            'occasion_ends_at' => Setting::get('occasion_ends_at', '') ?: null,
            'occasion_hero_image' => Setting::get('occasion_hero_image', '') ?: null,
            'occasion_sale_ids' => StorefrontOccasion::saleIds(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Storefront festival')
                    ->compact()
                    ->description('Turn on one festival. The greeting and a light decoration then appear on every shop page. The shop colours stay as they are.')
                    ->schema([
                        Toggle::make('occasion_enabled')
                            ->label('Show the festival on the shop')
                            ->helperText('Off leaves every page exactly as it is today.'),
                        Select::make('occasion_key')
                            ->label('Festival')
                            ->options(StorefrontOccasion::options())
                            ->native(false)
                            ->required(),
                        Grid::make(2)->schema([
                            DateTimePicker::make('occasion_starts_at')
                                ->label('Starts')
                                ->seconds(false)
                                ->native(false)
                                ->nullable()
                                ->helperText('Leave blank to start as soon as you turn it on.'),
                            DateTimePicker::make('occasion_ends_at')
                                ->label('Ends')
                                ->seconds(false)
                                ->native(false)
                                ->nullable()
                                ->after('occasion_starts_at')
                                ->helperText('Leave blank to keep it on until you turn it off. After this time the shop goes back to normal.'),
                        ]),
                        FileUpload::make('occasion_hero_image')
                            ->label('Hero campaign image')
                            ->image()
                            ->disk('public')
                            ->directory('occasions')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->nullable()
                            ->helperText('Optional. This photo replaces the first homepage banner while the festival is on. Leave it blank to keep the current photos, with a festival frame around them.'),
                        Select::make('occasion_sale_ids')
                            ->label('Sale products')
                            ->options(fn (): array => Product::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->maxItems(4)
                            ->nullable()
                            ->helperText('Pick up to four products for the festival sale. They stay out of the list below. Leave blank to use products that already have a lower price.'),
                    ]),
                Section::make('Where it shows')
                    ->compact()
                    ->description('The top bar is the general effect on every shop page. The other switches dress one homepage section, the way a sale campaign dresses each block.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('occasion_general')
                                ->label('Top bar on every page'),
                            Toggle::make('occasion_hero')
                                ->label('Hero banner'),
                            Toggle::make('occasion_categories')
                                ->label('Category row'),
                            Toggle::make('occasion_products')
                                ->label('Product section'),
                        ]),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save festival')
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
        $key = is_string($state['occasion_key'] ?? null) ? $state['occasion_key'] : 'eid_fitr';

        if (! isset(StorefrontOccasion::catalog()[$key])) {
            $key = 'eid_fitr';
        }

        $start = $this->dateValue($state['occasion_starts_at'] ?? null);
        $end = $this->dateValue($state['occasion_ends_at'] ?? null);

        if ($start !== '' && $end !== '' && Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
            Notification::make()
                ->title('The end time has to be after the start time.')
                ->danger()
                ->send();

            return;
        }

        $this->store('occasion_enabled', filter_var($state['occasion_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false', SettingType::Boolean);
        $this->store('occasion_key', $key, SettingType::String);
        $this->store('occasion_starts_at', $start, SettingType::String);
        $this->store('occasion_ends_at', $end, SettingType::String);
        $this->store('occasion_hero_image', $this->imageValue($state['occasion_hero_image'] ?? null), SettingType::String);
        $this->store('occasion_sale_ids', json_encode($this->saleIds($state['occasion_sale_ids'] ?? [])), SettingType::Json);
        $this->store('occasion_sections', json_encode([
            'general' => filter_var($state['occasion_general'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'hero' => filter_var($state['occasion_hero'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'categories' => filter_var($state['occasion_categories'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'products' => filter_var($state['occasion_products'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]), SettingType::Json);

        Notification::make()
            ->title('Festival saved.')
            ->success()
            ->send();
    }

    private function store(string $key, string $value, SettingType $type): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->fill([
            'label' => str_replace('_', ' ', $key),
            'value' => $value,
            'type' => $type,
            'group' => 'occasion',
            'is_public' => false,
            'is_system' => false,
        ]);
        $setting->save();
        Cache::forget("setting.{$key}");
    }

    private function dateValue(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function imageValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value) ?: '';
        }

        if (! is_string($value) || ! str_starts_with($value, 'occasions/') || str_contains($value, '..')) {
            return '';
        }

        return $value;
    }

    /**
     * @return list<int>
     */
    private function saleIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 4);
    }
}
