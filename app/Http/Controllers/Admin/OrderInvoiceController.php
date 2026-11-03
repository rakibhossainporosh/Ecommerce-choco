<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    /**
     * Display a clean, printable invoice and packing slip for an order.
     */
    public function show(Request $request, Order $order): View
    {
        $user = $request->user();

        if (! $user || ! $user->can_access_admin_panel || ! $user->can('view', $order)) {
            abort(403, 'Unauthorized access to order invoice.');
        }

        $order->loadMissing([
            'items',
            'payments',
            'shipments.shippingMethod',
            'customer',
        ]);

        return view('admin.orders.invoice', [
            'order' => $order,
        ]);
    }

    /**
     * Display a warehouse packing slip (quantities / SKUs, no prices).
     */
    public function packingSlip(Request $request, Order $order): View
    {
        $user = $request->user();

        if (! $user || ! $user->can_access_admin_panel || ! $user->can('view', $order)) {
            abort(403, 'Unauthorized access to packing slip.');
        }

        $order->loadMissing([
            'items',
            'shipments.shippingMethod',
            'latestShipment',
            'customer',
        ]);

        return view('admin.orders.packing-slip', [
            'order' => $order,
        ]);
    }
}
