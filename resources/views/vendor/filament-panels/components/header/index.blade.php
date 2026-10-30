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
        // Transform breadcrumbs to clean professional hierarchy: Dashboard > Module Name (> Action)
        $dashboardUrl = filament()->getHomeUrl() ?? url('/admin');
        $cleanBreadcrumbs = [];
        
        // Always start with Dashboard as root
        $cleanBreadcrumbs[$dashboardUrl] = 'Dashboard';

        if (! empty($breadcrumbs)) {
            foreach ($breadcrumbs as $url => $label) {
                // If label is "List", ignore it so we don't have redundant "Attributes > List"
                if (strtolower(trim($label)) === 'list') {
                    continue;
                }
                $cleanBreadcrumbs[$url] = $label;
            }
        } elseif (filled($heading) && strtolower(trim($heading)) !== 'dashboard') {
            // Fallback for pages that didn't provide breadcrumbs
            $cleanBreadcrumbs[] = $heading;
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
