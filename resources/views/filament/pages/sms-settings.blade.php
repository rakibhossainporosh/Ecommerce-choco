<x-filament-panels::page>
    <div class="sms-balance-card" wire:init="loadBalance">
        <div class="sms-balance-copy">
            <p class="sms-balance-kicker">{{ __('SMS left') }}</p>
            <p class="sms-balance-figure" wire:loading.remove wire:target="loadBalance,refreshBalance">{{ $balanceFigure }}</p>
            <p class="sms-balance-figure" wire:loading wire:target="loadBalance,refreshBalance">{{ __('Checking…') }}</p>
            @if ($balanceNote !== '')
                <p class="sms-balance-note" wire:loading.remove wire:target="loadBalance,refreshBalance">{{ $balanceNote }}</p>
            @endif
        </div>

        <x-filament::button
            color="gray"
            type="button"
            wire:click="refreshBalance"
            wire:loading.attr="disabled"
            wire:target="loadBalance,refreshBalance"
        >
            {{ __('Refresh') }}
        </x-filament::button>
    </div>

    {{ $this->form }}
</x-filament-panels::page>
