<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCustomer extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = CustomerResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Customer $record): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot delete customer with placed orders.'))
                            ->body(__('To preserve business history and financial consistency, this customer cannot be deleted.'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
