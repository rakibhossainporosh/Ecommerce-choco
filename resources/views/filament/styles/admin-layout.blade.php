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
{{-- Keep main content margin in sync with sidebar open/collapsed state on every toggle + SPA nav. --}}
<script>
    (function () {
        var DESKTOP = 1024;
        var unlockTimer = null;
        var observed = new WeakSet();

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

        function sidebarIsOpen(sidebar) {
            if (! sidebar) {
                return storageSaysOpen();
            }

            return sidebar.classList.contains('fi-sidebar-open');
        }

        function syncFromSidebarDom() {
            if (window.innerWidth < DESKTOP) {
                setCollapsedDesktop(false);

                return;
            }

            var sidebar = document.querySelector('#fi-main-sidebar, .fi-main-sidebar');
            setCollapsedDesktop(! sidebarIsOpen(sidebar));
            stampSidebars();
        }

        function stampSidebars() {
            if (window.innerWidth < DESKTOP) {
                return;
            }

            var open = ! document.documentElement.classList.contains('fi-admin-sidebar-collapsed-desktop');
            document.querySelectorAll('#fi-main-sidebar, .fi-main-sidebar').forEach(function (sidebar) {
                if (open) {
                    sidebar.classList.add('fi-sidebar-open');
                } else {
                    sidebar.classList.remove('fi-sidebar-open');
                }
            });
        }

        function observeSidebars() {
            document.querySelectorAll('#fi-main-sidebar, .fi-main-sidebar').forEach(function (sidebar) {
                if (observed.has(sidebar)) {
                    return;
                }

                observed.add(sidebar);

                var observer = new MutationObserver(function () {
                    if (window.innerWidth < DESKTOP) {
                        return;
                    }

                    if (document.documentElement.classList.contains('fi-admin-sidebar-nav-lock')) {
                        return;
                    }

                    setCollapsedDesktop(! sidebar.classList.contains('fi-sidebar-open'));
                });

                observer.observe(sidebar, {
                    attributes: true,
                    attributeFilter: ['class'],
                });
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
            observeSidebars();

            if (unlockTimer) {
                clearTimeout(unlockTimer);
            }

            unlockTimer = setTimeout(function () {
                document.documentElement.classList.remove('fi-admin-sidebar-nav-lock');
                syncFromSidebarDom();
                unlockTimer = null;
            }, 250);
        }

        window.__fiAdminSyncSidebarLayout = function (open) {
            if (window.innerWidth < DESKTOP) {
                setCollapsedDesktop(false);

                return;
            }

            setCollapsedDesktop(! open);

            try {
                localStorage.setItem('isOpenDesktop', open ? 'true' : 'false');
                localStorage.setItem('isOpen', open ? 'true' : 'false');
            } catch (e) {}

            stampSidebars();
        };

        // Set expected state before first paint of admin chrome.
        syncFromStorage();

        document.addEventListener('DOMContentLoaded', function () {
            stampSidebars();
            observeSidebars();
            syncFromSidebarDom();
        });

        window.addEventListener('resize', function () {
            syncFromStorage();
            stampSidebars();
            observeSidebars();
        });

        document.addEventListener('livewire:navigate', lockNav);
        document.addEventListener('livewire:navigated', unlockNav);

        document.addEventListener('alpine:initialized', function () {
            observeSidebars();
            syncFromSidebarDom();

            try {
                if (! window.Alpine || ! Alpine.store('sidebar')) {
                    return;
                }

                Alpine.effect(function () {
                    var open = Alpine.store('sidebar').isOpen;

                    if (window.innerWidth < DESKTOP) {
                        setCollapsedDesktop(false);

                        return;
                    }

                    if (document.documentElement.classList.contains('fi-admin-sidebar-nav-lock')) {
                        return;
                    }

                    setCollapsedDesktop(! open);
                });
            } catch (e) {}
        });
    })();
</script>
