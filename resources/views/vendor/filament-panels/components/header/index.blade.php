@props([
    'actions' => [],
    'actionsAlignment' => null,
    'breadcrumbs' => [],
    'heading' => null,
    'subheading' => null,
])

@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    use function Filament\Support\is_slot_empty;
@endphp

<header
    {{
        $attributes->class([
            'fi-header',
            'fi-header-has-breadcrumbs' => true,
            'fi-header-has-subheading' => filled($subheading),
        ])
    }}
>
    @php
        $dashboardUrl = filament()->getHomeUrl() ?? url('/admin');
        $cleanBreadcrumbs = [];
        $cleanBreadcrumbs[$dashboardUrl] = \App\Support\UiText::line('Dashboard');
        $skipLabels = ['list', 'তালিকা', 'dashboard', 'ড্যাশবোর্ড'];

        if (! empty($breadcrumbs)) {
            foreach ($breadcrumbs as $url => $label) {
                $label = trim((string) $label);

                if ($label === '' || in_array(mb_strtolower($label), $skipLabels, true)) {
                    continue;
                }

                $cleanBreadcrumbs[$url] = \App\Support\UiText::line($label);
            }
        } elseif (filled($heading)) {
            $headingLabel = trim(strip_tags((string) $heading));

            if ($headingLabel !== '' && ! in_array(mb_strtolower($headingLabel), $skipLabels, true)) {
                $cleanBreadcrumbs[] = \App\Support\UiText::line($headingLabel);
            }
        }
    @endphp

    @capture($headingContent)
        @if (count($cleanBreadcrumbs) > 1 || ! empty($cleanBreadcrumbs))
            <x-filament::breadcrumbs :breadcrumbs="$cleanBreadcrumbs" />
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE, scopes: $this->getRenderHookScopes()) }}

        @if (filled($heading))
            <h1 class="fi-header-heading">
                {{ $heading }}
            </h1>
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::PAGE_HEADER_HEADING_AFTER, scopes: $this->getRenderHookScopes()) }}

        @if (filled($subheading))
            <p class="fi-header-subheading">
                {{ $subheading }}
            </p>
        @endif
    @endcapture

    @php
        $headingContent = $headingContent();
    @endphp

    @if (! is_slot_empty($headingContent))
        <div>{{ $headingContent }}</div>
    @else
        {{ $headingContent }}
    @endif

    @php
        $beforeActions = FilamentView::renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes());
        $afterActions = FilamentView::renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes());
    @endphp

    @capture($actionsContent)
        {{ $beforeActions }}

        @if ($actions)
            <x-filament::actions
                :actions="$actions"
                :alignment="$actionsAlignment"
            />
        @endif

        {{ $afterActions }}
    @endcapture

    @php
        $actionsContent = $actionsContent();
    @endphp

    @if (! is_slot_empty($actionsContent))
        <div class="fi-header-actions-ctn">
            {{ $actionsContent }}
        </div>
    @else
        {{ $actionsContent }}
    @endif
</header>
