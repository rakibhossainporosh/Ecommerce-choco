<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerInvoiceController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            abort(403);
        }

        $ownsOrder = (int) $order->customer_id === (int) $customer->id
            || (filled($customer->phone) && $order->customer_phone === $customer->phone);

        if (! $ownsOrder) {
            abort(403);
        }

        $order->loadMissing([
            'items',
            'payments',
            'shipments.shippingMethod',
            'customer',
        ]);

        return view('admin.orders.invoice', [
            'order' => $order,
            'customer_view' => true,
        ]);
    }
}
