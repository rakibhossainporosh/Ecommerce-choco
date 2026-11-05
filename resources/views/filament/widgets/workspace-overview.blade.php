<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $greeting }}, {{ $name }}
        </x-slot>

        <x-slot name="description">
            @if ($hasWorkspace)
                @if ($role)
                    {{ __('Signed in as :role. Your dashboard shows only the modules you can access.', ['role' => $role]) }}
                @else
                    {{ __('Your dashboard shows only the modules you can access.') }}
                @endif
            @else
                {{ __('No modules are assigned to your account yet. Ask an administrator to grant access.') }}
            @endif
        </x-slot>
    </x-filament::section>
</x-filament-widgets::widget>
