{{-- Cached static admin CSS (was ~106KB inline on every request). --}}
@php
    $adminLayoutCss = public_path('css/filament/admin-layout.css');
    $adminLayoutCssVersion = is_file($adminLayoutCss) ? (string) filemtime($adminLayoutCss) : (string) time();
@endphp
<link
    rel="stylesheet"
    href="{{ asset('css/filament/admin-layout.css') }}?v={{ $adminLayoutCssVersion }}"
>
<style>
    :root {
        --fi-admin-actions-label: "{{ __('Actions') }}";
    }
</style>
{{-- Stable desktop sidebar width across every Livewire SPA navigation (Cancel included). --}}
<script>
    (function () {
        var DESKTOP = 1024;
        var unlockTimer = null;

        function storageSaysOpen() {
            try {
                var desktop = localStorage.getItem('isOpenDesktop');
                if (desktop !== null) {
                    return desktop === 'true' || desktop === '1';
                }

                var open = localStorage.getItem('isOpen');
                if (open !== null) {
                    return open === 'true' || open === '1';
                }
            } catch (e) {}

            return true;
        }

        function setCollapsedDesktop(collapsed) {
            document.documentElement.classList.toggle('fi-admin-sidebar-collapsed-desktop', !!collapsed);
        }

        function syncFromStorage() {
            if (window.innerWidth < DESKTOP) {
                setCollapsedDesktop(false);

                return;
            }

            setCollapsedDesktop(! storageSaysOpen());
        }

        function stampSidebars() {
            if (window.innerWidth < DESKTOP) {
                return;
            }

            var open = storageSaysOpen();
            document.querySelectorAll('#fi-main-sidebar, .fi-main-sidebar').forEach(function (sidebar) {
                if (open) {
                    sidebar.classList.add('fi-sidebar-open');
                } else {
                    sidebar.classList.remove('fi-sidebar-open');
                }
            });
        }

        function lockNav() {
            document.documentElement.classList.add('fi-admin-sidebar-nav-lock');
            syncFromStorage();
            stampSidebars();

            if (unlockTimer) {
                clearTimeout(unlockTimer);
                unlockTimer = null;
            }
        }

        function unlockNav() {
            syncFromStorage();
            stampSidebars();

            if (unlockTimer) {
                clearTimeout(unlockTimer);
            }

            unlockTimer = setTimeout(function () {
                document.documentElement.classList.remove('fi-admin-sidebar-nav-lock');
                stampSidebars();
                unlockTimer = null;
            }, 250);
        }

        // Set expected state before first paint of admin chrome.
        syncFromStorage();

        document.addEventListener('DOMContentLoaded', function () {
            stampSidebars();
        });

        window.addEventListener('resize', function () {
            syncFromStorage();
            stampSidebars();
        });

        document.addEventListener('livewire:navigate', lockNav);
        document.addEventListener('livewire:navigated', unlockNav);
    })();
</script>
