@php
    /** @var \App\Models\Order $order */
    $order = $record ?? $order ?? (isset($getRecord) ? $getRecord() : null);
    $currency = config('currency.symbol', '৳');
@endphp

@if($order)
<div class="space-y-6">
    <!-- Ordered Products Table -->
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200 divide-y divide-gray-200 dark:divide-white/10">
            <thead class="bg-gray-50/80 dark:bg-white/5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <tr>
                    <th scope="col" class="py-3.5 px-4 min-w-[200px]">Product</th>
                    <th scope="col" class="py-3.5 px-3 text-center min-w-[110px]">SKU</th>
                    <th scope="col" class="py-3.5 px-3 text-right min-w-[100px]">Unit Price</th>
                    <th scope="col" class="py-3.5 px-3 text-center min-w-[70px]">Qty</th>
                    <th scope="col" class="py-3.5 px-3 text-right min-w-[100px]">Discount</th>
                    <th scope="col" class="py-3.5 px-4 text-right min-w-[110px]">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 font-normal">
                @forelse($order->items as $item)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $item->product_name }}
                            </div>
                            @if($item->variant_name)
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/50">
                                        {{ $item->variant_name }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="font-mono text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-white/10 px-2 py-1 rounded select-all">
                                {{ $item->sku }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-right whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ $currency }} {{ number_format((float) $item->unit_price, 2) }}
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-200">
                                {{ $item->quantity }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-right whitespace-nowrap text-gray-500 dark:text-gray-400">
                            @if((float) $item->discount_amount > 0)
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                                    - {{ $currency }} {{ number_format((float) $item->discount_amount, 2) }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-bold text-gray-900 dark:text-white">
                            {{ $currency }} {{ number_format((float) $item->line_total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 px-4 text-center text-gray-400 dark:text-gray-500 italic">
                            No items found in this order.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals Breakdown Footer -->
        <div class="border-t border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/[0.02] p-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total {{ $order->items->count() }} line item(s) • Currency: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $order->currency ?? 'BDT' }}</span>
                </div>
                <div class="w-full sm:w-80 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Items Subtotal:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $currency }} {{ number_format((float) $order->subtotal, 2) }}
                        </span>
                    </div>

                    @if((float) $order->discount_amount > 0)
                        <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                            <span>Discount:</span>
                            <span class="font-medium">
                                - {{ $currency }} {{ number_format((float) $order->discount_amount, 2) }}
                            </span>
                        </div>
                    @endif

                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Shipping Fee:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $currency }} {{ number_format((float) $order->shipping_amount, 2) }}
                        </span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-white/10 pt-2 flex justify-between text-base font-bold text-gray-900 dark:text-white">
                        <span>Grand Total:</span>
                        <span class="text-amber-600 dark:text-amber-400">
                            {{ $currency }} {{ number_format((float) $order->grand_total, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-xs pt-1 border-t border-dashed border-gray-200 dark:border-white/10">
                        <span class="text-gray-500 dark:text-gray-400">Paid:</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $currency }} {{ number_format((float) $order->total_paid, 2) }}
                        </span>
                    </div>

                    @if($order->due_amount > 0)
                        <div class="flex justify-between text-xs font-semibold text-rose-600 dark:text-rose-400">
                            <span>Balance Due:</span>
                            <span>{{ $currency }} {{ number_format((float) $order->due_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
