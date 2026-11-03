<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Models\Order;
use App\Services\OrderMailer;
use App\Support\OrderMailSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class OrderOpsActions
{
    /**
     * Printable warehouse packing slip (no prices).
     */
    public static function makePrintPackingSlipAction(): Action
    {
        return Action::make('printPackingSlip')
            ->label(__('Packing slip'))
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->url(fn (Order $record): string => route('admin.orders.packing-slip', $record), shouldOpenInNewTab: true);
    }

    /**
     * Internal ops notes, gift message, and order tags (Shopify-style).
     */
    public static function makeEditOpsNotesAction(): Action
    {
        return Action::make('editOpsNotes')
            ->label(__('Ops notes'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->modalHeading(fn (Order $record): string => __('Ops notes — #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Internal notes and tags for warehouse and support. Customers do not see admin notes.'))
            ->modalSubmitActionLabel(__('Save'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('update', $record) ?? false)
            )
            ->authorize('update')
            ->fillForm(fn (Order $record): array => [
                'admin_note' => $record->admin_note,
                'gift_message' => $record->gift_message,
                'tags' => $record->tagList(),
            ])
            ->form([
                Textarea::make('admin_note')
                    ->label(__('Admin note'))
                    ->rows(4)
                    ->maxLength(2000)
                    ->helperText(__('Visible only to staff on this order.')),
                Textarea::make('gift_message')
                    ->label(__('Gift message'))
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText(__('Printed on packing slip when present.')),
                TagsInput::make('tags')
                    ->label(__('Tags'))
                    ->placeholder(__('vip, fragile, priority…'))
                    ->helperText(__('Quick filters for ops (e.g. VIP, fragile, COD risk).')),
            ])
            ->action(function (Order $record, array $data): void {
                if (! auth()->user()?->can('update', $record)) {
                    abort(403);
                }

                $tags = collect($data['tags'] ?? [])
                    ->map(static fn (mixed $tag): string => trim((string) $tag))
                    ->filter()
                    ->unique(strict: true)
                    ->values()
                    ->all();

                $record->update([
                    'admin_note' => filled($data['admin_note'] ?? null) ? trim((string) $data['admin_note']) : null,
                    'gift_message' => filled($data['gift_message'] ?? null) ? trim((string) $data['gift_message']) : null,
                    'tags' => $tags === [] ? null : $tags,
                ]);

                activity()
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->event('ops_notes_updated')
                    ->log('Order ops notes / tags updated');

                Notification::make()
                    ->title(__('Ops notes saved'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Correct shipping address before the parcel leaves (Shopify-style edit destination).
     */
    public static function makeEditShippingAddressAction(): Action
    {
        return Action::make('editShippingAddress')
            ->label(__('Edit address'))
            ->icon(Heroicon::OutlinedMapPin)
            ->color('gray')
            ->modalHeading(fn (Order $record): string => __('Edit shipping address — #:number', ['number' => $record->order_number]))
            ->modalDescription(__('Use before dispatch when the customer corrects their delivery address.'))
            ->modalSubmitActionLabel(__('Update address'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('update', $record) ?? false)
                && ! in_array($record->status->value, ['shipped', 'delivered', 'returned', 'refunded', 'cancelled'], true)
            )
            ->authorize('update')
            ->fillForm(fn (Order $record): array => [
                'customer_name' => $record->customer_name,
                'customer_phone' => $record->customer_phone,
                'shipping_address_line' => $record->shipping_address_line,
                'shipping_area' => $record->shipping_area,
                'shipping_city' => $record->shipping_city,
                'shipping_postcode' => $record->shipping_postcode,
                'shipping_country' => $record->shipping_country ?? 'Bangladesh',
            ])
            ->form([
                TextInput::make('customer_name')
                    ->label(__('Recipient name'))
                    ->required()
                    ->maxLength(120),
                TextInput::make('customer_phone')
                    ->label(__('Phone'))
                    ->required()
                    ->tel()
                    ->maxLength(20),
                TextInput::make('shipping_address_line')
                    ->label(__('Address line'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('shipping_area')
                    ->label(__('Area'))
                    ->maxLength(120),
                TextInput::make('shipping_city')
                    ->label(__('City'))
                    ->required()
                    ->maxLength(120),
                TextInput::make('shipping_postcode')
                    ->label(__('Postcode'))
                    ->maxLength(32),
                TextInput::make('shipping_country')
                    ->label(__('Country'))
                    ->required()
                    ->maxLength(120),
            ])
            ->action(function (Order $record, array $data): void {
                if (! auth()->user()?->can('update', $record)) {
                    abort(403);
                }

                if (in_array($record->status->value, ['shipped', 'delivered', 'returned', 'refunded', 'cancelled'], true)) {
                    Notification::make()
                        ->title(__('Address locked'))
                        ->body(__('Shipping address cannot be changed after the order has left processing.'))
                        ->danger()
                        ->send();

                    return;
                }

                $record->update([
                    'customer_name' => trim((string) $data['customer_name']),
                    'customer_phone' => trim((string) $data['customer_phone']),
                    'shipping_address_line' => trim((string) $data['shipping_address_line']),
                    'shipping_area' => filled($data['shipping_area'] ?? null) ? trim((string) $data['shipping_area']) : null,
                    'shipping_city' => trim((string) $data['shipping_city']),
                    'shipping_postcode' => filled($data['shipping_postcode'] ?? null) ? trim((string) $data['shipping_postcode']) : null,
                    'shipping_country' => trim((string) $data['shipping_country']),
                ]);

                activity()
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->event('shipping_address_updated')
                    ->log('Shipping address updated by staff');

                Notification::make()
                    ->title(__('Shipping address updated'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Resend the status email that matches the order's current lifecycle step.
     */
    public static function makeResendStatusEmailAction(): Action
    {
        return Action::make('resendStatusEmail')
            ->label(__('Resend email'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => __('Resend status email — #:number', ['number' => $record->order_number]))
            ->modalDescription(fn (Order $record): string => __('Send the “:step” notification again to the customer (and admin if enabled).', [
                'step' => OrderMailSettings::steps()[$record->mailStepKey()] ?? $record->mailStepKey(),
            ]))
            ->modalSubmitActionLabel(__('Send now'))
            ->visible(fn (?Order $record): bool => $record instanceof Order
                && (auth()->user()?->can('update', $record) ?? false)
                && OrderMailSettings::enabled()
                && filled(OrderMailSettings::customerEmail($record))
            )
            ->authorize('update')
            ->action(function (Order $record, OrderMailer $mailer): void {
                if (! auth()->user()?->can('update', $record)) {
                    abort(403);
                }

                if (! OrderMailSettings::enabled()) {
                    Notification::make()
                        ->title(__('Mail disabled'))
                        ->body(__('Enable store mail in settings before resending.'))
                        ->warning()
                        ->send();

                    return;
                }

                $step = $record->mailStepKey();
                $mailer->send($record, $step);

                activity()
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->event('status_email_resent')
                    ->withProperties(['step' => $step])
                    ->log('Status email resent: '.$step);

                Notification::make()
                    ->title(__('Email queued'))
                    ->body(__('Status notification “:step” was sent again.', [
                        'step' => OrderMailSettings::steps()[$step] ?? $step,
                    ]))
                    ->success()
                    ->send();
            });
    }
}
