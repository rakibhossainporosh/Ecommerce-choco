<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Exceptions\PaymentException;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Record Payment')
                ->modalHeading(__('Record Payment Transaction'))
                ->modalDescription(__('Enter transaction details to record and reconcile payments against an order.'))
                ->modalIcon(Heroicon::OutlinedBanknotes)
                ->modalWidth(Width::TwoExtraLarge)
                ->using(function (array $data): Payment {
                    $order = Order::query()->findOrFail($data['order_id']);

                    try {
                        /** @var User|null $user */
                        $user = auth()->user();

                        return $order->recordPayment(
                            amount: (float) $data['amount'],
                            method: $data['payment_method'] ?? PaymentMethod::Cod,
                            status: $data['status'] ?? PaymentTransactionStatus::Completed,
                            transactionId: $data['transaction_id'] ?? null,
                            accountNumber: $data['account_number'] ?? null,
                            notes: $data['notes'] ?? null,
                            recordedBy: $user,
                            paidAt: isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : null,
                        );
                    } catch (PaymentException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('Payment Failed'))
                            ->body($e->getMessage())
                            ->send();

                        throw new Halt;
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $counts = Payment::query()
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = fn (PaymentTransactionStatus $status): int => (int) ($counts[$status->value] ?? 0);
        $total = (int) $counts->sum();

        return [
            'all' => Tab::make('All Payments')
                ->badge($total),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Pending))
                ->badge($countFor(PaymentTransactionStatus::Pending))
                ->badgeColor('warning'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Completed))
                ->badge($countFor(PaymentTransactionStatus::Completed))
                ->badgeColor('success'),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Failed))
                ->badge($countFor(PaymentTransactionStatus::Failed))
                ->badgeColor('danger'),
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentTransactionStatus::Refunded))
                ->badge($countFor(PaymentTransactionStatus::Refunded))
                ->badgeColor('gray'),
        ];
    }
}
