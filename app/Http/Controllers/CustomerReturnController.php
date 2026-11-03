<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Support\ReturnItemValidator;
use App\Support\ReturnPolicySettings;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerReturnController extends Controller
{
    /**
     * Submit a self-serve return request for a delivered order.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $ownsOrder = (int) $order->customer_id === (int) $customer->id
            || ($customer->phone && $order->customer_phone === $customer->phone);

        if (! $ownsOrder) {
            abort(403);
        }

        $order->load('items');

        if ($order->status !== OrderStatus::Delivered) {
            throw ValidationException::withMessages([
                'order' => __('storefront.account.return_only_delivered'),
            ]);
        }

        if (! $order->isWithinReturnWindow()) {
            throw ValidationException::withMessages([
                'order' => __('storefront.account.return_window_expired', [
                    'days' => ReturnPolicySettings::windowDays(),
                ]),
            ]);
        }

        if (ReturnRequest::hasOpenReturnForOrder((int) $order->id)) {
            throw ValidationException::withMessages([
                'order' => __('storefront.account.return_already_open'),
            ]);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $normalized = ReturnItemValidator::normalize($order, $validated['items']);

            if ($normalized === []) {
                throw ValidationException::withMessages([
                    'items' => __('storefront.account.return_select_items'),
                ]);
            }

            ReturnRequest::createPendingForOrder($order, [
                'customer_id' => $customer->id,
                'user_id' => $customer->user_id,
                'reason' => $validated['reason'],
            ], $validated['items']);
        } catch (DomainException $e) {
            $message = $e->getMessage();

            throw ValidationException::withMessages([
                str_contains(strtolower($message), 'open return') ? 'order' : 'items' => $message,
            ]);
        }

        return redirect()
            ->route('customer.account', ['tab' => 'returns'])
            ->with('success', __('storefront.account.return_submitted'));
    }

    /**
     * Cancel a pending self-serve return request.
     */
    public function destroy(ReturnRequest $returnRequest): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $ownsReturn = (int) $returnRequest->customer_id === (int) $customer->id;

        if (! $ownsReturn) {
            $order = $returnRequest->order;

            $ownsReturn = $order && (
                (int) $order->customer_id === (int) $customer->id
                || ($customer->phone && $order->customer_phone === $customer->phone)
            );
        }

        if (! $ownsReturn) {
            abort(403);
        }

        try {
            $returnRequest->cancelByCustomer();
        } catch (DomainException $e) {
            return redirect()
                ->route('customer.account', ['tab' => 'returns'])
                ->with('error', __('storefront.account.return_cancel_failed'));
        }

        return redirect()
            ->route('customer.account', ['tab' => 'returns'])
            ->with('success', __('storefront.account.return_cancelled'));
    }
}
