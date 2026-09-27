<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($this->getKpiCards() as $card)
            <x-filament::section compact>
                <div class="flex items-center gap-x-3">
                    <div @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                        'bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400' => $card['color'] === 'primary',
                        'bg-success-50 text-success-600 dark:bg-success-950/50 dark:text-success-400' => $card['color'] === 'success',
                        'bg-warning-50 text-warning-600 dark:bg-warning-950/50 dark:text-warning-400' => $card['color'] === 'warning',
                        'bg-danger-50 text-danger-600 dark:bg-danger-950/50 dark:text-danger-400' => $card['color'] === 'danger',
                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $card['color'] === 'gray',
                    ])>
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">
                            {{ $card['label'] }}
                        </div>
                        <div class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($card['value']) }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
