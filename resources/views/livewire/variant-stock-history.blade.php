<div class="space-y-4">
    {{-- Header Summary --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ $variant->name ? $variant->name . ' (' . $variant->sku . ')' : $variant->sku }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Inventory Movement Ledger &bull; Immutable audit history
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                Current Stock: <strong class="ml-1">{{ $variant->inventory?->quantity ?? 0 }}</strong>
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                Threshold: <strong class="ml-1">{{ $variant->inventory?->low_stock_threshold ?? 0 }}</strong>
            </span>
        </div>
    </div>

    {{-- Movement Ledger Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-400 font-semibold uppercase tracking-wider text-[11px]">
                <tr>
                    <th scope="col" class="px-3.5 py-3">Date</th>
                    <th scope="col" class="px-3.5 py-3">Type</th>
                    <th scope="col" class="px-3.5 py-3 text-right">Before</th>
                    <th scope="col" class="px-3.5 py-3 text-right">Change</th>
                    <th scope="col" class="px-3.5 py-3 text-right">After</th>
                    <th scope="col" class="px-3.5 py-3">Reason</th>
                    <th scope="col" class="px-3.5 py-3">Note</th>
                    <th scope="col" class="px-3.5 py-3">User</th>
                    <th scope="col" class="px-3.5 py-3">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                @forelse ($movements as $movement)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                        {{-- 1. Date --}}
                        <td class="px-3.5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400 font-mono">
                            {{ $movement->created_at?->format('Y-m-d H:i:s') ?? '—' }}
                        </td>

                        {{-- 2. Type --}}
                        <td class="px-3.5 py-3 whitespace-nowrap">
                            @php
                                $typeVal = is_object($movement->type) ? $movement->type->value : (string) $movement->type;
                                $isOpening = $typeVal === \App\Enums\InventoryMovementType::Opening->value;
                                $isAdjustIn = $typeVal === \App\Enums\InventoryMovementType::AdjustmentIn->value;
                                $isAdjustOut = $typeVal === \App\Enums\InventoryMovementType::AdjustmentOut->value;
                            @endphp

                            @if ($isOpening)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                    Opening Stock
                                </span>
                            @elseif ($isAdjustIn)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    Stock In
                                </span>
                            @elseif ($isAdjustOut)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                    Stock Out
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                    {{ $typeVal }}
                                </span>
                            @endif
                        </td>

                        {{-- 3. Before --}}
                        <td class="px-3.5 py-3 whitespace-nowrap text-right font-mono">
                            {{ $movement->quantity_before }}
                        </td>

                        {{-- 4. Change --}}
                        <td class="px-3.5 py-3 whitespace-nowrap text-right font-mono font-semibold">
                            @if ($isAdjustOut)
                                <span class="text-rose-600 dark:text-rose-400">-{{ $movement->quantity }}</span>
                            @else
                                <span class="text-emerald-600 dark:text-emerald-400">+{{ $movement->quantity }}</span>
                            @endif
                        </td>

                        {{-- 5. After --}}
                        <td class="px-3.5 py-3 whitespace-nowrap text-right font-mono font-bold text-gray-900 dark:text-white">
                            {{ $movement->quantity_after }}
                        </td>

                        {{-- 6. Reason --}}
                        <td class="px-3.5 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate" title="{{ $movement->reason }}">
                            {{ $movement->reason ?: '—' }}
                        </td>

                        {{-- 7. Note --}}
                        <td class="px-3.5 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $movement->note }}">
                            {{ $movement->note ?: '—' }}
                        </td>

                        {{-- 8. User --}}
                        <td class="px-3.5 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            @if ($movement->creator)
                                <span class="font-medium">{{ $movement->creator->name }}</span>
                            @elseif ($movement->created_by)
                                <span class="text-gray-400 dark:text-gray-500">User #{{ $movement->created_by }}</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">System</span>
                            @endif
                        </td>

                        {{-- 9. Reference --}}
                        <td class="px-3.5 py-3 whitespace-nowrap">
                            @if ($movement->reference_type && $movement->reference_id)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded font-mono text-[11px] bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center space-y-1">
                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium">No inventory movements recorded yet for this variant.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if ($movements->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
