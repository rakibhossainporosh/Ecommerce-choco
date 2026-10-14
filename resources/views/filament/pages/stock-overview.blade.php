<x-filament-panels::page>
    <style>
        .stock-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        @media (min-width: 640px) {
            .stock-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .stock-kpi-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
        .stock-kpi-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.125rem 1.25rem;
            border-radius: 0.875rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            min-height: 110px;
        }
        .dark .stock-kpi-card {
            background: #18181b;
            border-color: #27272a;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }
        .stock-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            border-color: #d1d5db;
        }
        .dark .stock-kpi-card:hover {
            border-color: #3f3f46;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        }
        .stock-kpi-card.is-active {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.35), 0 4px 6px -1px rgba(0, 0, 0, 0.08);
        }
        .dark .stock-kpi-card.is-active {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.45), 0 4px 6px -1px rgba(0, 0, 0, 0.4);
        }
        .stock-kpi-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .accent-primary { background: #3b82f6; }
        .accent-success { background: #10b981; }
        .accent-warning { background: #f59e0b; }
        .accent-danger { background: #ef4444; }
        .accent-gray { background: #6b7280; }

        .kpi-icon-badge {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kpi-icon-badge svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        .badge-primary { background: #eff6ff; color: #2563eb; }
        .dark .badge-primary { background: rgba(37, 99, 235, 0.18); color: #60a5fa; }

        .badge-success { background: #ecfdf5; color: #059669; }
        .dark .badge-success { background: rgba(5, 150, 105, 0.18); color: #34d399; }

        .badge-warning { background: #fffbeb; color: #d97706; }
        .dark .badge-warning { background: rgba(217, 119, 6, 0.18); color: #fbbf24; }

        .badge-danger { background: #fef2f2; color: #dc2626; }
        .dark .badge-danger { background: rgba(220, 38, 38, 0.18); color: #f87171; }

        .badge-gray { background: #f3f4f6; color: #4b5563; }
        .dark .badge-gray { background: rgba(107, 114, 128, 0.18); color: #9ca3af; }
    </style>

    <div class="stock-kpi-grid">
        @foreach ($this->getKpiCards() as $card)
            @php
                $currentFilter = $this->tableFilters['stock_status']['value'] ?? null;
                $isSelected = ($card['filter'] === null && blank($currentFilter)) || ($card['filter'] !== null && $currentFilter === $card['filter']);
            @endphp
            <div 
                wire:click="filterByKpi({{ $card['filter'] ? "'{$card['filter']}'" : 'null' }})"
                role="button"
                tabindex="0"
                class="stock-kpi-card {{ $isSelected ? 'is-active' : '' }}"
                title="{{ $card['filter'] ? 'Click to filter by ' . $card['label'] : 'Click to show all variants' }}"
            >
                <div class="stock-kpi-accent accent-{{ $card['color'] }}"></div>

                <div class="flex items-center justify-between mb-2 pt-1">
                    <span class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">
                        {{ $card['label'] }}
                    </span>
                    <div class="kpi-icon-badge badge-{{ $card['color'] }}">
                        <x-filament::icon :icon="$card['icon']" class="w-5 h-5" />
                    </div>
                </div>

                <div class="flex items-baseline justify-between mt-auto">
                    <div>
                        <div class="text-2xl sm:text-3xl font-extrabold tracking-tight font-mono text-gray-900 dark:text-white">
                            {{ number_format($card['value']) }}
                        </div>
                        @if (! empty($card['subtext']))
                            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $card['subtext'] }}
                            </div>
                        @endif
                    </div>

                    @if ($isSelected)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                            Active
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
