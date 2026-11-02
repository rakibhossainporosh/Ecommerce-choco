<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesPageNavigation;
use App\Payments\PaymentManager;
use App\Support\PaymentGatewaySettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class PaymentSettings extends Page
{
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Payment methods';

    protected static ?string $title = 'Payment methods';

    protected static ?string $slug = 'settings/payments';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.payment-settings';

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
        $this->form->fill(PaymentGatewaySettings::formState());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->dense()
            ->components([
                ...$this->gatewaySections(),
                Actions::make([
                    Action::make('save')
                        ->label('Save payment methods')
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

        PaymentGatewaySettings::save($this->form->getState());
        $this->form->fill(PaymentGatewaySettings::formState());

        Notification::make()
            ->title(__('Payment methods saved.'))
            ->success()
            ->send();
    }

    /**
     * @return list<Section>
     */
    private function gatewaySections(): array
    {
        $sections = [];

        foreach (app(PaymentManager::class)->all() as $gateway) {
            $schema = [
                Toggle::make('gateways.'.$gateway->key().'.enabled')
                    ->label('Accept '.$gateway->label())
                    ->helperText($gateway->description()),
            ];
            $row = [];

            foreach ($gateway->settingsFields() as $field) {
                $component = $this->settingField($gateway->key(), $field);

                if ($field['type'] === 'toggle') {
                    $schema = [...$schema, ...$this->fieldRow($row), $component];
                    $row = [];

                    continue;
                }

                $row[] = ['field' => $field, 'component' => $component];

                if (count($row) === 2) {
                    $schema = [...$schema, ...$this->fieldRow($row)];
                    $row = [];
                }
            }

            $schema = [...$schema, ...$this->fieldRow($row)];

            $sections[] = Section::make($gateway->label())
                ->compact()
                ->dense()
                ->description($gateway->mode()->value === 'redirect'
                    ? __('Online checkout. The customer pays on the provider page, then returns here.')
                    : ($gateway->mode()->value === 'manual'
                        ? __('The customer sends the money, and you confirm it from the payment list.')
                        : __('The customer pays when the parcel arrives.')))
                ->schema($schema);
        }

        return $sections;
    }

    /**
     * @param  array{key: string, type: string, label: string, helper: string, placeholder?: string, max?: int}  $field
     */
    private function settingField(string $gateway, array $field): TextInput|Textarea|Toggle
    {
        $name = 'gateways.'.$gateway.'.'.$field['key'];

        return match ($field['type']) {
            'secret' => TextInput::make($name)
                ->label($field['label'])
                ->password()
                ->revealable()
                ->helperText($field['helper'])
                ->dehydrated(fn (?string $state): bool => filled($state)),
            'textarea' => Textarea::make($name)
                ->label($field['label'])
                ->helperText($field['helper'])
                ->rows(2)
                ->maxLength($field['max'] ?? 500),
            'toggle' => Toggle::make($name)
                ->label($field['label'])
                ->helperText($field['helper']),
            default => TextInput::make($name)
                ->label($field['label'])
                ->placeholder($field['placeholder'] ?? null)
                ->helperText($field['helper'])
                ->maxLength($field['max'] ?? 255),
        };
    }

    /**
     * @param  list<array{field: array{key: string, type: string, label: string, helper: string, placeholder?: string, max?: int}, component: TextInput|Textarea}>  $row
     * @return list<Grid|TextInput|Textarea>
     */
    private function fieldRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (count($row) === 1) {
            return [$row[0]['component']];
        }

        $spans = ($row[0]['field']['type'] === 'textarea' || $row[1]['field']['type'] === 'textarea')
            ? [3, 9]
            : [6, 6];

        if ($row[0]['field']['type'] === 'textarea') {
            $spans = array_reverse($spans);
        }

        return [
            Grid::make([
                'default' => 1,
                'md' => 12,
            ])->schema([
                $row[0]['component']->columnSpan(['md' => $spans[0]]),
                $row[1]['component']->columnSpan(['md' => $spans[1]]),
            ]),
        ];
    }
}
