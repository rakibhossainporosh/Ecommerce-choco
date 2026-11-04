<div class="variant-stock-history">
    {{-- Summary strip — variant name lives in the Filament modal heading --}}
    <div class="vsh-header">
        <p class="vsh-subtitle">
            {{ __('Stock change history. These rows are kept and are not deleted.') }}
        </p>
        <div class="vsh-stats" role="group" aria-label="{{ __('Stock summary') }}">
            <span class="vsh-stat">
                {{ __('Current Stock') }}
                <strong>{{ $variant->inventory?->quantity ?? 0 }}</strong>
            </span>
            <span class="vsh-stat">
                {{ __('Threshold') }}
                <strong>{{ $variant->inventory?->low_stock_threshold ?? 0 }}</strong>
            </span>
        </div>
    </div>

    <div class="vsh-ledger">
        <div class="vsh-ledger-bar">
            <h3 class="vsh-ledger-title">{{ __('Inventory Movement Ledger') }}</h3>
            @if ($movements->total() > 0)
                <span class="vsh-count">
                    {{ trans_choice(':count record|:count records', $movements->total(), ['count' => $movements->total()]) }}
                </span>
            @endif
        </div>

        @if ($movements->isEmpty())
            <div class="vsh-empty-card" role="status">
                <svg class="vsh-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="vsh-empty-title">{{ __('No movements yet') }}</p>
                <p class="vsh-empty-text">{{ __('No inventory movements recorded yet for this variant.') }}</p>
            </div>
        @else
            <div class="vsh-table-wrap">
                <table class="vsh-table">
                    <colgroup>
                        <col class="vsh-col-date" />
                        <col class="vsh-col-type" />
                        <col class="vsh-col-num" />
                        <col class="vsh-col-num" />
                        <col class="vsh-col-num" />
                        <col class="vsh-col-text" />
                        <col class="vsh-col-text" />
                        <col class="vsh-col-user" />
                        <col class="vsh-col-ref" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col">{{ __('Type') }}</th>
                            <th scope="col" class="vsh-num">{{ __('Before') }}</th>
                            <th scope="col" class="vsh-num">{{ __('Change') }}</th>
                            <th scope="col" class="vsh-num">{{ __('After') }}</th>
                            <th scope="col">{{ __('Reason') }}</th>
                            <th scope="col">{{ __('Note') }}</th>
                            <th scope="col">{{ __('User') }}</th>
                            <th scope="col">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php
                                $typeVal = is_object($movement->type) ? $movement->type->value : (string) $movement->type;
                                $isOpening = $typeVal === \App\Enums\InventoryMovementType::Opening->value;
                                $isAdjustIn = $typeVal === \App\Enums\InventoryMovementType::AdjustmentIn->value;
                                $isAdjustOut = $typeVal === \App\Enums\InventoryMovementType::AdjustmentOut->value;
                            @endphp
                            <tr>
                                <td class="vsh-mono vsh-muted">
                                    {{ $movement->created_at?->format('Y-m-d H:i:s') ?? '—' }}
                                </td>

                                <td>
                                    @if ($isOpening)
                                        <span class="vsh-badge vsh-badge-info">
                                            {{ \App\Enums\InventoryMovementType::Opening->label() }}
                                        </span>
                                    @elseif ($isAdjustIn)
                                        <span class="vsh-badge vsh-badge-success">
                                            {{ \App\Enums\InventoryMovementType::AdjustmentIn->label() }}
                                        </span>
                                    @elseif ($isAdjustOut)
                                        <span class="vsh-badge vsh-badge-danger">
                                            {{ \App\Enums\InventoryMovementType::AdjustmentOut->label() }}
                                        </span>
                                    @else
                                        <span class="vsh-badge vsh-badge-muted">
                                            {{ $typeVal }}
                                        </span>
                                    @endif
                                </td>

                                <td class="vsh-num vsh-mono">
                                    {{ $movement->quantity_before }}
                                </td>

                                <td class="vsh-num vsh-mono vsh-change">
                                    @if ($isAdjustOut)
                                        <span class="vsh-delta-out">-{{ $movement->quantity }}</span>
                                    @else
                                        <span class="vsh-delta-in">+{{ $movement->quantity }}</span>
                                    @endif
                                </td>

                                <td class="vsh-num vsh-mono vsh-after">
                                    {{ $movement->quantity_after }}
                                </td>

                                <td class="vsh-truncate" title="{{ $movement->reason }}">
                                    {{ $movement->reason ?: '—' }}
                                </td>

                                <td class="vsh-truncate vsh-muted" title="{{ $movement->note }}">
                                    {{ $movement->note ?: '—' }}
                                </td>

                                <td>
                                    @if ($movement->creator)
                                        <span class="vsh-user">{{ $movement->creator->name }}</span>
                                    @elseif ($movement->created_by)
                                        <span class="vsh-muted">{{ __('User #:id', ['id' => $movement->created_by]) }}</span>
                                    @else
                                        <span class="vsh-muted">{{ __('System') }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($movement->reference_type && $movement->reference_id)
                                        <span class="vsh-ref">
                                            {{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}
                                        </span>
                                    @else
                                        <span class="vsh-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($movements->hasPages())
                    <div class="vsh-pagination">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
