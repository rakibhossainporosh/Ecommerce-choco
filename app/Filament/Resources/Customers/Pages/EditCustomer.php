<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Customer $record): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete customer with placed orders.')
                            ->body('To preserve business history and financial consistency, this customer cannot be deleted.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
