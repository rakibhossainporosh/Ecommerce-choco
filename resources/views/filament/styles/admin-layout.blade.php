{{-- Custom Admin Layout Styling (Matching ONETECH / GitHub style collapsible rail and full-width layout) --}}
<style>
    /* Full-width container across all admin pages with professional spacing */
    .fi-main {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }

    @media (min-width: 640px) {
        .fi-main {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }
    }

    @media (min-width: 1024px) {
        .fi-main {
            padding-left: 2rem !important;
            padding-right: 2rem !important;
        }
    }

    .store-locale-choices.fi-btn-group {
        width: max-content;
        max-width: 100%;
    }

    .store-locale-choices.fi-btn-group > .fi-btn {
        min-width: 7.5rem;
        justify-content: center;
    }

    .fi-topbar-end .admin-locale {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .admin-locale-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        height: 2.25rem;
        padding: 0 0.7rem;
        border: 1px solid #e7e5e4;
        border-radius: 999px;
        background: #fff;
        color: #1e293b;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
    }
    .admin-locale-trigger:hover,
    .admin-locale-trigger[aria-expanded="true"] {
        background: #faf7f2;
        border-color: #e2d9cd;
    }
    .admin-locale-trigger:focus,
    .admin-locale-trigger:focus-visible {
        outline: none;
        border-color: #e86a28;
        box-shadow: 0 0 0 3px rgba(232, 106, 40, 0.18);
    }
    .admin-locale-trigger svg {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
        color: #64748b;
    }
    .admin-locale-layer {
        position: fixed;
        inset: 0;
        z-index: 80;
    }
    .admin-locale-menu {
        position: fixed;
        z-index: 81;
        max-width: calc(100vw - 1rem);
        padding: 0.35rem;
        border: 1px solid #e7e5e4;
        border-radius: 0.85rem;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
    }
    .admin-locale-menu-label {
        margin: 0;
        padding: 0.4rem 0.65rem 0.2rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
    }
    .admin-locale-option {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.65rem;
        border: 0;
        border-radius: 0.6rem;
        background: transparent;
        color: #1e293b;
        font-size: 0.875rem;
        font-weight: 600;
        text-align: start;
        cursor: pointer;
    }
    .admin-locale-option:hover {
        background: #faf7f2;
    }
    .admin-locale-option.is-active {
        background: #fff7ed;
        color: #c2410c;
    }
    .admin-locale-option svg {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
    }
    .dark .admin-locale-trigger,
    .dark .admin-locale-menu {
        background: #0f172a;
        border-color: #334155;
        color: #f8fafc;
    }
    .dark .admin-locale-trigger:hover,
    .dark .admin-locale-trigger[aria-expanded="true"],
    .dark .admin-locale-option:hover {
        background: #1e293b;
    }
    .dark .admin-locale-option {
        color: #f8fafc;
    }
    .dark .admin-locale-option.is-active {
        background: rgba(232, 106, 40, 0.16);
        color: #fdba74;
    }
    .admin-locale-profile {
        display: none;
    }
    @media (max-width: 1023px) {
        .fi-topbar-end .admin-locale {
            display: none !important;
        }
        .admin-locale-profile {
            display: contents;
        }
    }

    /* Topbar clean full-bleed styling */
    .fi-topbar {
        border-bottom: 1px solid var(--gray-200, rgba(229, 231, 235, 1)) !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }
    .dark .fi-topbar {
        border-bottom: 1px solid var(--gray-800, rgba(31, 41, 55, 1)) !important;
        background: #0f172a !important;
    }

    /* Sidebar Clean Structure - Spans full height from top: 0 so its header aligns seamlessly with topbar */
    .fi-sidebar {
        border-right: 1px solid var(--gray-200, rgba(229, 231, 235, 1)) !important;
        background: #ffffff !important;
        box-shadow: none !important;
        transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .dark .fi-sidebar {
        border-right: 1px solid var(--gray-800, rgba(31, 41, 55, 1)) !important;
        background: #0f172a !important;
    }

    @media (min-width: 1024px) {
        /* Force sidebar to top: 0 with higher z-index than topbar */
        .fi-body-has-topbar .fi-sidebar,
        .fi-sidebar {
            top: 0 !important;
            height: 100dvh !important;
            z-index: 50 !important;
            position: fixed !important;
        }

        /* Topbar container must start to the right of the sidebar, not stretch across the left void */
        .fi-body-has-sidebar-collapsible-on-desktop.fi-body-has-topbar .fi-topbar-ctn,
        .fi-topbar-ctn,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-main-ctn,
        .fi-main-ctn {
            margin-inline-start: 14.5rem !important;
            transition: margin-inline-start 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .fi-topbar-ctn {
            z-index: 40 !important;
        }

        /* When sidebar is collapsed */
        html:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-ctn,
        body:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-ctn,
        .fi-body-has-sidebar-collapsible-on-desktop:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-ctn,
        html:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-main-ctn,
        body:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-main-ctn,
        .fi-body-has-sidebar-collapsible-on-desktop:has(.fi-sidebar:not(.fi-sidebar-open)) .fi-main-ctn {
            margin-inline-start: 4.5rem !important;
        }
    }

    /* Hide default desktop toggle and duplicate logo in topbar */
    .fi-topbar-collapse-sidebar-btn-ctn {
        display: none !important;
    }
    
    .fi-topbar-start {
        display: none !important;
    }

    /* Mobile Header Layout (Phone Version) */
    @media (max-width: 1023px) {
        .fi-topbar {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.5rem !important;
            height: 3.5rem !important;
        }

        /* 1. Mobile Menu Toggle Buttons on Left - Respect Alpine x-show so both icons NEVER show at once */
        .fi-topbar-open-sidebar-btn {
            order: 1 !important;
            margin: 0 !important;
            flex-shrink: 0 !important;
        }

        .fi-topbar-close-sidebar-btn {
            order: 1 !important;
            margin: 0 !important;
            flex-shrink: 0 !important;
        }

        /* Prevent both hamburger and cross from displaying simultaneously */
        [style*="display: none"] {
            display: none !important;
        }

        /* 2. Page Title immediately next to Toggle with flexible expansion */
        .custom-topbar-page-heading-wrapper {
            order: 2 !important;
            padding-left: 0.25rem !important;
            padding-right: 0.5rem !important;
            margin-right: auto !important;
            flex: 1 1 auto !important;
            overflow: hidden !important;
            min-width: 0 !important;
        }

        .custom-topbar-title-text {
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* 3. Hide Search button on mobile - phone doesn't need search taking up valuable navbar space */
        .fi-topbar-end .fi-global-search-ctn,
        .fi-topbar-end [data-global-search],
        .fi-topbar-end button[aria-label*="Search"],
        .fi-topbar-end button[title*="Search"],
        .fi-topbar-end .fi-input-wrp:has(input[type="search"]),
        .fi-topbar-end .fi-global-search-btn {
            display: none !important;
        }

        /* 4. Notification and user menu on the far right */
        .fi-topbar-end {
            order: 3 !important;
            margin-left: auto !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            flex-shrink: 0 !important;
        }

        /* Clean user menu trigger - remove harsh focus outline box around circular avatar */
        .fi-topbar-end .fi-user-menu-btn,
        .fi-topbar-end button:has(.fi-avatar) {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }
    }

    /* Desktop Topbar Page Heading */
    @media (min-width: 1024px) {
        .custom-topbar-page-heading-wrapper {
            display: flex;
            align-items: center;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
            height: 100%;
            margin-right: auto;
        }
    }

    .custom-topbar-title-text {
        font-weight: 700 !important;
        letter-spacing: -0.02em !important;
        color: #0f172a !important;
        margin: 0 !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }
    .dark .custom-topbar-title-text {
        color: #f8fafc !important;
    }

    /* In page body: Hide duplicate large heading so title only displays in topbar, while keeping breadcrumbs and actions */
    .fi-header-heading {
        display: none !important;
    }

    /* Page container spacing - Remove default py-8 (2rem gap) so breadcrumbs sit closely and neatly below topbar */
    .fi-page-header-main-ctn {
        padding-top: 1rem !important;
        padding-bottom: 2rem !important;
        gap: 1.25rem !important;
    }

    /* Page body breadcrumbs & actions bar styling */
    .fi-header {
        margin-bottom: 0.5rem !important;
        padding-top: 0 !important;
    }

    .fi-header .fi-breadcrumbs {
        display: block !important;
        margin-bottom: 0 !important;
    }

    /* Hide default sidebar header since we render custom GitHub / ONETECH header */
    .fi-sidebar-header-ctn {
        display: none !important;
    }

    /* Custom Sidebar Brand & Toggle Header - Exact same border color as the topbar */
    .custom-sidebar-brand-wrapper {
        border-bottom: 1px solid var(--gray-200, rgba(229, 231, 235, 1)) !important;
        height: 4rem;
        min-height: 4rem;
        display: flex;
        align-items: center;
        background-color: #ffffff;
        box-sizing: border-box;
    }
    .dark .custom-sidebar-brand-wrapper {
        border-bottom: 1px solid var(--gray-800, rgba(31, 41, 55, 1)) !important;
        background-color: #0f172a;
    }

    /* Expanded State Header: Brand on left, Toggle on right */
    .custom-sidebar-header-expanded {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-left: 1rem;
        padding-right: 0.75rem;
    }

    .custom-brand-logo-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        overflow: hidden;
        max-width: 9.5rem;
    }

    .custom-brand-logo-img {
        max-height: 2.25rem;
        width: auto;
        object-fit: contain;
    }

    .custom-brand-name {
        font-weight: 700;
        font-size: 1.15rem;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark .custom-brand-name {
        color: #f8fafc;
    }

    /* Collapsed State Header: No logo, ONLY centered toggle button */
    .custom-sidebar-header-collapsed {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        margin: 0 auto;
    }

    /* Toggle Button (Matching ONETECH / GitHub exact UI) */
    .custom-sidebar-toggle-btn {
        width: 2.25rem;
        height: 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid rgba(203, 213, 225, 0.8);
        background-color: #ffffff;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        padding: 0;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .custom-sidebar-header-collapsed .custom-sidebar-toggle-btn {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
    }
    .dark .custom-sidebar-toggle-btn {
        background-color: rgba(30, 41, 59, 0.8);
        border-color: rgba(255, 255, 255, 0.15);
        color: #94a3b8;
        box-shadow: none;
    }

    .custom-sidebar-toggle-btn:hover {
        background-color: rgba(241, 245, 249, 1);
        color: #0f172a;
        border-color: #94a3b8;
    }
    .dark .custom-sidebar-toggle-btn:hover {
        background-color: rgba(51, 65, 85, 1);
        color: #f8fafc;
        border-color: rgba(255, 255, 255, 0.3);
    }

    .custom-toggle-icon {
        width: 1.25rem;
        height: 1.25rem;
        stroke-width: 1.85;
    }

    /* Topbar search & right-side alignment */
    .fi-topbar-ctn {
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
    }

    /* Sidebar Navigation Container - Compact, perfectly aligned with zero gap */
    .fi-sidebar-nav {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        padding-top: 0.75rem !important;
        padding-bottom: 1.5rem !important;
        gap: 0.75rem !important;
    }

    .fi-sidebar-nav-groups {
        margin-left: 0 !important;
        margin-right: 0 !important;
        gap: 0.75rem !important;
    }

    .fi-sidebar-group {
        gap: 0.25rem !important;
    }

    /* Group Labels in Expanded State - Straight alignment with menu icons */
    .fi-sidebar-group-btn {
        padding: 0.35rem 0.5rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    .fi-sidebar-group-label {
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        color: #64748b !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .dark .fi-sidebar-group-label {
        color: #94a3b8 !important;
    }

    .fi-sidebar-group-items {
        gap: 0.15rem !important;
    }

    /* Sidebar Item Buttons - Flush and aligned straight */
    .fi-sidebar-item-btn {
        border-radius: 0.5rem !important;
        padding: 0.45rem 0.5rem !important;
        margin: 0 !important;
        gap: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        font-weight: 500 !important;
        font-size: 0.875rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    /* Keep icon fixed size and left-aligned */
    .fi-sidebar-item-btn .fi-sidebar-item-icon {
        width: 1.35rem !important;
        height: 1.35rem !important;
        min-width: 1.35rem !important;
        flex-shrink: 0 !important;
        color: #64748b !important;
    }
    .dark .fi-sidebar-item-btn .fi-sidebar-item-icon {
        color: #94a3b8 !important;
    }

    /* Ensure label starts immediately after the icon without weird right displacement */
    .fi-sidebar-item-btn .fi-sidebar-item-label {
        flex: 1 1 auto !important;
        text-align: left !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .fi-sidebar-item-btn:hover {
        background-color: rgba(243, 244, 246, 0.9) !important;
    }
    .dark .fi-sidebar-item-btn:hover {
        background-color: rgba(255, 255, 255, 0.06) !important;
    }

    /* Active Sidebar Item - Pill Highlight */
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: rgba(245, 158, 11, 0.1) !important;
        color: #d97706 !important;
        font-weight: 600 !important;
    }
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: rgba(245, 158, 11, 0.18) !important;
        color: #fbbf24 !important;
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon {
        color: #d97706 !important;
    }
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon {
        color: #fbbf24 !important;
    }

    /* ==========================================================================
       COLLAPSED SIDEBAR (GitHub / ONETECH Rail Style)
       ========================================================================== */
    @media (min-width: 1024px) {
        /* When sidebar is collapsed on desktop */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) {
            width: 4.5rem !important;
        }

        /* 1. In collapsed state: HIDE group header buttons, labels, and collapse chevron so group titles disappear */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-btn,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-label,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-collapse-btn {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
        }

        /* 2. In collapsed state: ENSURE group items container and item buttons remain visible and centered */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-items {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            width: 100% !important;
            gap: 0.5rem !important;
            height: auto !important;
            visibility: visible !important;
            overflow: visible !important;
        }

        /* In collapsed state: hide the item label text, keep the icon visible inside centered pill button */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-label,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-badge {
            display: none !important;
        }

        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item {
            width: 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav {
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-top: 1rem !important;
            padding-bottom: 2rem !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-gutter: stable;
            scrollbar-width: thin !important;
            scrollbar-color: #858c97 transparent !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav {
            scrollbar-color: #64748b transparent !important;
        }

        /* Sleek Modern Scrollbar for Collapsed Rail */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar {
            width: 5px !important;
            display: block !important;
        }
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar-track {
            background: transparent !important;
        }
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar-thumb {
            background: #858c97 !important;
            border-radius: 9999px !important;
        }
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: #64748b !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar-thumb {
            background: #64748b !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: #94a3b8 !important;
        }

        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav-groups {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0.75rem !important;
        }

        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            margin-bottom: 0 !important;
            padding: 0 !important;
        }

        /* Group Separator / Divider in Collapsed State */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group:not(:first-child)::before {
            content: "" !important;
            display: block !important;
            width: 2rem !important;
            height: 1px !important;
            margin: 0.5rem auto 0.625rem auto !important;
            background-color: rgba(203, 213, 225, 0.6) !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group:not(:first-child)::before {
            background-color: rgba(255, 255, 255, 0.12) !important;
        }

        /* Collapsed Icon Circular/Pill Button - Centered exactly on the vertical line */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-btn {
            width: 2.75rem !important;
            height: 2.75rem !important;
            min-width: 2.75rem !important;
            border-radius: 0.75rem !important;
            padding: 0 !important;
            margin: 0 auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: rgba(241, 245, 249, 0.7) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid transparent !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-btn {
            background-color: rgba(30, 41, 59, 0.7) !important;
        }

        /* Hover on Collapsed Item */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-btn:hover {
            background-color: #ffffff !important;
            border-color: rgba(203, 213, 225, 0.8) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
            transform: scale(1.05);
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-btn:hover {
            background-color: rgba(51, 65, 85, 0.9) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
        }

        /* Active State on Collapsed Item (Selected Pill Highlight with Amber Brand Theme) */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
            background-color: #ffffff !important;
            border: 2px solid #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15), 0 4px 6px -1px rgba(245, 158, 11, 0.1) !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
            background-color: rgba(245, 158, 11, 0.15) !important;
            border: 2px solid #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
        }

        /* Icon size inside collapsed pill */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-icon {
            width: 1.35rem !important;
            height: 1.35rem !important;
            stroke-width: 1.75 !important;
            color: #475569 !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-icon {
            color: #cbd5e1 !important;
        }

        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
            color: #d97706 !important;
        }
        .dark .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
            color: #fbbf24 !important;
        }
    }

    /* Smooth page animations & zero layout jumps */
    .fi-main-ctn {
        transition: margin 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* ==========================================================================
       ENTERPRISE TABLE HEADERS & ACTIONS COLUMN (Shopify / Stripe Standard)
       ========================================================================== */
    .fi-ta-actions-header-cell {
        text-align: right !important;
        padding-right: 1.5rem !important;
        padding-left: 1rem !important;
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
        white-space: nowrap !important;
    }

    .fi-ta-actions-header-cell::after {
        content: "{{ __('Actions') }}" !important;
        display: inline-block !important;
        font-size: 0.775rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        color: #0f172a !important;
        text-transform: none !important;
    }
    .dark .fi-ta-actions-header-cell::after {
        color: #f8fafc !important;
    }

    /* Middle Column Alignment & Equalized Gaps across ALL admin tables */
    .fi-ta-table {
        table-layout: auto !important;
        width: 100% !important;
    }

    /* Prevent accidental horizontal table scrollbar on desktop, but allow smooth horizontal scroll on mobile/tablet */
    .fi-ta-content {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    @media (min-width: 1024px) {
        .fi-ta-content {
            overflow-x: visible !important;
        }
    }

    /* 1. Selection Checkbox Column (Only present when selection is enabled) */
    .fi-ta-table th.fi-ta-selection-cell,
    .fi-ta-table td.fi-ta-selection-cell {
        text-align: left !important;
        padding-left: 1.25rem !important;
        padding-right: 0.75rem !important;
        width: 1% !important;
        white-space: nowrap !important;
    }

    /* 2. Primary Identifier Column (Order #, Name, Log name, etc.) */
    .fi-ta-table th.fi-ta-header-cell:not(.fi-ta-selection-cell):not(.fi-ta-actions-header-cell):first-of-type,
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):not(:last-child):first-of-type,
    .fi-ta-table tr:has(.fi-ta-selection-cell) th.fi-ta-header-cell:nth-child(2),
    .fi-ta-table tr:has(.fi-ta-selection-cell) td.fi-ta-cell:nth-child(2),
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) th.fi-ta-header-cell:first-child,
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) td.fi-ta-cell:first-child {
        text-align: left !important;
        padding-left: 1.25rem !important;
        padding-right: 1rem !important;
    }

    /* Reset internal padding inside first data cell so it flushes exactly with the header text */
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):first-of-type .fi-ta-text,
    .fi-ta-table tr:has(.fi-ta-selection-cell) td.fi-ta-cell:nth-child(2) .fi-ta-text,
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) td.fi-ta-cell:first-child .fi-ta-text {
        padding-left: 0 !important;
        padding-inline-start: 0 !important;
        margin-left: 0 !important;
    }

    .fi-ta-table th.fi-ta-header-cell:not(.fi-ta-selection-cell):not(.fi-ta-actions-header-cell):first-of-type .fi-ta-header-cell-sort-btn,
    .fi-ta-table th.fi-ta-header-cell:not(.fi-ta-selection-cell):not(.fi-ta-actions-header-cell):first-of-type > button,
    .fi-ta-table th.fi-ta-header-cell:not(.fi-ta-selection-cell):not(.fi-ta-actions-header-cell):first-of-type > span,
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):not(:last-child):first-of-type .fi-ta-col,
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):not(:last-child):first-of-type .fi-ta-text-item,
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):not(:last-child):first-of-type a,
    .fi-ta-table td.fi-ta-cell:not(.fi-ta-selection-cell):not(:last-child):first-of-type div,
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) th.fi-ta-header-cell:first-child .fi-ta-header-cell-sort-btn,
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) td.fi-ta-cell:first-child .fi-ta-text-item,
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) td.fi-ta-cell:first-child a {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        text-align: left !important;
        margin-left: 0 !important;
        margin-right: auto !important;
        padding-left: 0 !important;
    }

    /* 3. Middle Columns: Data and Headers CENTER-ALIGNED with Equal Gaps across every field */
    .fi-ta-table tr:has(.fi-ta-selection-cell) th.fi-ta-header-cell:not(:first-child):not(:nth-child(2)):not(:last-child),
    .fi-ta-table tr:has(.fi-ta-selection-cell) td.fi-ta-cell:not(:first-child):not(:nth-child(2)):not(:last-child),
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) th.fi-ta-header-cell:not(:first-child):not(:last-child),
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) td.fi-ta-cell:not(:first-child):not(:last-child),
    .fi-ta-table th.fi-ta-header-cell.fi-align-center,
    .fi-ta-table td.fi-ta-cell.fi-align-center,
    .fi-ta-header-cell-customer-name,
    .fi-ta-header-cell-items-count,
    .fi-ta-header-cell-status,
    .fi-ta-header-cell-payment-status,
    .fi-ta-header-cell-payment-method,
    .fi-ta-header-cell-grand-total,
    .fi-ta-header-cell-placed-at,
    .fi-ta-cell-customer-name,
    .fi-ta-cell-items-count,
    .fi-ta-cell-status,
    .fi-ta-cell-payment-status,
    .fi-ta-cell-payment-method,
    .fi-ta-cell-grand-total,
    .fi-ta-cell-placed-at {
        text-align: center !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    /* Equalized internal text container padding */
    .fi-ta-table td.fi-ta-cell .fi-ta-text:not(.fi-inline) {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Force table header cell text-center */
    .fi-ta-table th.fi-ta-header-cell.fi-align-center,
    .fi-ta-table tr:has(.fi-ta-selection-cell) th.fi-ta-header-cell:not(:first-child):not(:nth-child(2)):not(:last-child),
    .fi-ta-table tr:not(:has(.fi-ta-selection-cell)) th.fi-ta-header-cell:not(:first-child):not(:last-child) {
        text-align: center !important;
    }

    /* Middle Columns: Header Sort Button dead-centering with zero offset */
    .fi-ta-table th.fi-ta-header-cell.fi-align-center .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-customer-name .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-customer-phone .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-items-count .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-status .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-payment-status .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-payment-method .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-grand-total .fi-ta-header-cell-sort-btn,
    .fi-ta-header-cell-placed-at .fi-ta-header-cell-sort-btn {
        display: inline-flex !important;
        position: relative !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: auto !important;
        padding-left: 1.25rem !important;
        padding-right: 1.25rem !important; /* Symmetrical padding so text stays 100% dead center */
    }

    .fi-ta-table th.fi-ta-header-cell.fi-align-center .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-customer-name .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-customer-phone .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-items-count .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-status .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-payment-status .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-payment-method .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-grand-total .fi-ta-header-cell-sort-btn svg,
    .fi-ta-header-cell-placed-at .fi-ta-header-cell-sort-btn svg {
        position: absolute !important;
        right: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
    }

    /* Target SVG and Images inside middle cells */
    .fi-ta-table td.fi-align-center svg,
    .fi-ta-table td.fi-align-center img {
        margin-left: auto !important;
        margin-right: auto !important;
        display: block !important;
    }

    /* All centered table data cells (.fi-align-center and targeted columns) */
    .fi-ta-table td.fi-align-center,
    .fi-ta-table th.fi-align-center,
    .fi-ta-header-cell-customer-name,
    .fi-ta-header-cell-customer-phone,
    .fi-ta-header-cell-items-count,
    .fi-ta-header-cell-status,
    .fi-ta-header-cell-payment-status,
    .fi-ta-header-cell-payment-method,
    .fi-ta-header-cell-grand-total,
    .fi-ta-header-cell-placed-at,
    .fi-ta-cell-customer-name,
    .fi-ta-cell-customer-phone,
    .fi-ta-cell-items-count,
    .fi-ta-cell-status,
    .fi-ta-cell-payment-status,
    .fi-ta-cell-payment-method,
    .fi-ta-cell-grand-total,
    .fi-ta-cell-placed-at {
        text-align: center !important;
    }

    /* Ensure .fi-ta-col stretches full width and centers its content */
    .fi-ta-table td.fi-align-center .fi-ta-col,
    .fi-ta-cell-customer-name .fi-ta-col,
    .fi-ta-cell-customer-phone .fi-ta-col,
    .fi-ta-cell-items-count .fi-ta-col,
    .fi-ta-cell-status .fi-ta-col,
    .fi-ta-cell-payment-status .fi-ta-col,
    .fi-ta-cell-payment-method .fi-ta-col,
    .fi-ta-cell-grand-total .fi-ta-col,
    .fi-ta-cell-placed-at .fi-ta-col {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        width: 100% !important;
        margin: 0 auto !important;
    }

    /* Inner containers and text (.fi-ta-text) */
    .fi-ta-table td.fi-align-center .fi-ta-text,
    .fi-ta-cell-customer-name .fi-ta-text,
    .fi-ta-cell-customer-phone .fi-ta-text,
    .fi-ta-cell-items-count .fi-ta-text,
    .fi-ta-cell-status .fi-ta-text,
    .fi-ta-cell-payment-status .fi-ta-text,
    .fi-ta-cell-payment-method .fi-ta-text,
    .fi-ta-cell-grand-total .fi-ta-text,
    .fi-ta-cell-placed-at .fi-ta-text {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        width: 100% !important;
        margin: 0 auto !important;
    }

    /* Single-item divs inside .fi-ta-text (e.g. optimized render or default div) */
    .fi-ta-table td.fi-align-center .fi-ta-text > div,
    .fi-ta-table td.fi-align-center .fi-ta-text > p,
    .fi-ta-table td.fi-align-center .fi-ta-text > span,
    .fi-ta-cell-customer-name .fi-ta-text-item,
    .fi-ta-cell-customer-phone .fi-ta-text-item,
    .fi-ta-cell-items-count .fi-ta-text-item,
    .fi-ta-cell-status .fi-ta-text-item,
    .fi-ta-cell-payment-status .fi-ta-text-item,
    .fi-ta-cell-payment-method .fi-ta-text-item,
    .fi-ta-cell-grand-total .fi-ta-text-item,
    .fi-ta-cell-placed-at .fi-ta-text-item {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        margin: 0 auto !important;
        width: auto !important;
    }

    /* Badges & placeholders inside centered cells: strictly inline-flex center */
    .fi-ta-table td.fi-align-center .fi-badge,
    .fi-ta-cell-customer-name .fi-badge,
    .fi-ta-cell-customer-phone .fi-badge,
    .fi-ta-cell-items-count .fi-badge,
    .fi-ta-cell-status .fi-badge,
    .fi-ta-cell-payment-status .fi-badge,
    .fi-ta-cell-customer-name .fi-ta-placeholder,
    .fi-ta-cell-customer-phone .fi-ta-placeholder,
    .fi-ta-cell-items-count .fi-ta-placeholder,
    .fi-ta-cell-status .fi-ta-placeholder,
    .fi-ta-cell-payment-status .fi-ta-placeholder,
    .fi-ta-cell-payment-method .fi-ta-placeholder,
    .fi-ta-cell-grand-total .fi-ta-placeholder,
    .fi-ta-cell-placed-at .fi-ta-placeholder {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        margin: 0 auto !important;
    }

    /* Highlight interactive clickable table rows with distinct background, border indicator, and pointer cursor */
    .fi-ta-table tbody tr.fi-clickable,
    .fi-ta-row.fi-clickable {
        cursor: pointer !important;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-ta-table tbody tr.fi-clickable:hover,
    .fi-ta-row.fi-clickable:hover {
        background-color: #f1f5f9 !important; /* Clearly visible soft slate/gray */
    }
    .dark .fi-ta-table tbody tr.fi-clickable:hover,
    .dark .fi-ta-row.fi-clickable:hover {
        background-color: rgba(51, 65, 85, 0.6) !important;
    }

    /* Primary identifier (Order #) gets subtle brand color on hover to indicate clickability */
    .fi-ta-table tbody tr.fi-clickable:hover td.fi-ta-cell:first-of-type .fi-ta-text-item,
    .fi-ta-table tbody tr.fi-clickable:hover td.fi-ta-cell:nth-child(2) .fi-ta-text-item {
        color: #d97706 !important; /* Amber brand accent */
        transition: color 0.15s ease-in-out !important;
    }
    .dark .fi-ta-table tbody tr.fi-clickable:hover td.fi-ta-cell:first-of-type .fi-ta-text-item,
    .dark .fi-ta-table tbody tr.fi-clickable:hover td.fi-ta-cell:nth-child(2) .fi-ta-text-item {
        color: #fbbf24 !important;
    }

    /* Remove all hover underlines across tables, action buttons, and links */
    .fi-ta-table a,
    .fi-ta-table a:hover,
    .fi-ta-table a:focus,
    .fi-ta-table a:active,
    .fi-ta-table button,
    .fi-ta-table button:hover,
    .fi-ta-table button:focus,
    .fi-ta-table button:active,
    .fi-ta-table .fi-link,
    .fi-ta-table .fi-link:hover,
    .fi-ta-table .fi-link:focus,
    .fi-ta-table .fi-link:active,
    .fi-ta-table .fi-link-label,
    .fi-ta-table .fi-link-label:hover,
    .fi-ta-table .fi-link-label:focus,
    .fi-ta-table .fi-ta-text-item a,
    .fi-ta-table .fi-ta-text-item a:hover,
    .fi-ta-table .fi-ta-text-item:hover,
    .fi-ta-table .fi-ta-text a,
    .fi-ta-table .fi-ta-col-has-column-url .fi-ta-text-item,
    .fi-ta-table .fi-ta-col-has-column-url .fi-ta-text-item:hover,
    .fi-link,
    .fi-link:hover,
    .fi-link:focus,
    .fi-link-label,
    .fi-link-label:hover {
        text-decoration: none !important;
        text-decoration-line: none !important;
        outline: none !important;
    }

    /* Modern, industry-standard micro-interaction for Table Action buttons (View, Invoice, Confirm, Cancel, etc.) */
    .fi-ta-actions .fi-link {
        padding: 0.35rem 0.65rem !important;
        border-radius: 0.5rem !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background-color: transparent !important;
    }

    .fi-ta-actions .fi-link:hover {
        background-color: rgba(241, 245, 249, 1) !important; /* Soft subtle pill background on hover */
        transform: translateY(-1px) !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }

    .dark .fi-ta-actions .fi-link:hover {
        background-color: rgba(51, 65, 85, 0.6) !important;
    }

    /* Specific danger/cancel action modern hover styling */
    .fi-ta-actions .fi-link.fi-color-danger:hover,
    .fi-ta-actions .fi-link[class*="danger"]:hover {
        background-color: rgba(254, 242, 242, 1) !important;
    }
    .dark .fi-ta-actions .fi-link.fi-color-danger:hover,
    .dark .fi-ta-actions .fi-link[class*="danger"]:hover {
        background-color: rgba(239, 68, 68, 0.15) !important;
    }

    /* Specific primary/info action modern hover styling */
    .fi-ta-actions .fi-link.fi-color-primary:hover,
    .fi-ta-actions .fi-link.fi-color-info:hover {
        background-color: rgba(239, 246, 255, 1) !important;
    }
    .dark .fi-ta-actions .fi-link.fi-color-primary:hover,
    .dark .fi-ta-actions .fi-link.fi-color-info:hover {
        background-color: rgba(59, 130, 246, 0.15) !important;
    }

    /* 4. Last Column (Actions) keeps its precise right-aligned position */
    .fi-ta-table th:last-child,
    .fi-ta-table td:last-child {
        text-align: right !important;
        padding-right: 1.5rem !important;
        padding-left: 1rem !important;
        white-space: nowrap !important;
        width: 1% !important;
    }
    .fi-ta-table td:last-child .fi-ta-actions {
        justify-content: flex-end !important;
        margin-left: auto !important;
    }



    /* Transform fragmented 3-column pagination into a cohesive 2-part enterprise layout */
    .fi-pagination {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 1rem !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
        grid-template-columns: none !important;
    }

    /* 1. Left Group: Overview Count + Per-page Dropdown cleanly paired */
    .fi-pagination-overview {
        display: inline-flex !important;
        align-items: center !important;
        font-size: 0.84rem !important;
        font-weight: 500 !important;
        color: #64748b !important;
        order: 1 !important;
    }
    .dark .fi-pagination-overview {
        color: #94a3b8 !important;
    }

    .fi-pagination-records-per-page-select-ctn {
        order: 2 !important;
        display: inline-flex !important;
        align-items: center !important;
        margin-right: auto !important;
        margin-left: 0.75rem !important;
    }

    /* Subtle divider line between "Showing 1 to 10 of 28" and "Per page [10 ▾]" on desktop */
    @media (min-width: 640px) {
        .fi-pagination-records-per-page-select-ctn::before {
            content: "" !important;
            display: inline-block !important;
            width: 1px !important;
            height: 1.15rem !important;
            background-color: #e2e8f0 !important;
            margin-right: 0.75rem !important;
        }
        .dark .fi-pagination-records-per-page-select-ctn::before {
            background-color: rgba(255, 255, 255, 0.12) !important;
        }
    }

    /* Compact, clean Per page selector wrapper - HIDE DUPLICATE compact select */
    .fi-pagination-records-per-page-select.fi-compact {
        display: none !important;
    }

    .fi-pagination-records-per-page-select:not(.fi-compact) {
        display: inline-flex !important;
        align-items: center !important;
    }

    .fi-pagination-records-per-page-select .fi-input-wrp {
        border-radius: 0.5rem !important;
        border: 1px solid rgba(203, 213, 225, 0.9) !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
        transition: all 0.15s ease-in-out !important;
    }
    .dark .fi-pagination-records-per-page-select .fi-input-wrp {
        background-color: rgba(30, 41, 59, 0.7) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    .fi-pagination-records-per-page-select .fi-input-wrp:hover {
        border-color: #94a3b8 !important;
    }

    .fi-pagination-records-per-page-select .fi-input-wrp:focus-within {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
    }

    /* 2. Right Group: Page numbers & Next/Prev navigation */
    .fi-pagination-items {
        order: 3 !important;
        display: flex !important;
        align-items: center !important;
        margin-left: auto !important;
        border-radius: 0.5rem !important;
        border: 1px solid rgba(203, 213, 225, 0.8) !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
        overflow: hidden !important;
    }
    .dark .fi-pagination-items {
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    .fi-pagination-item-btn {
        transition: all 0.15s ease-in-out !important;
    }

    .fi-pagination-item.fi-active .fi-pagination-item-btn {
        background-color: rgba(245, 158, 11, 0.12) !important;
        color: #d97706 !important;
        font-weight: 700 !important;
    }
    .dark .fi-pagination-item.fi-active .fi-pagination-item-btn {
        background-color: rgba(245, 158, 11, 0.22) !important;
        color: #fbbf24 !important;
    }

    .fi-pagination-previous-btn,
    .fi-pagination-next-btn {
        order: 4 !important;
    }

    /* ==========================================================================
       LOGIN & SIMPLE AUTH PAGE (Ultra-Luxury Enterprise Ecommerce Style)
       ========================================================================== */
    .fi-simple-layout {
        min-height: 100dvh !important;
        background-color: #f8fafc !important;
        background-image: 
            radial-gradient(at 15% 15%, rgba(245, 158, 11, 0.12) 0px, transparent 40%),
            radial-gradient(at 85% 15%, rgba(217, 119, 6, 0.08) 0px, transparent 40%),
            radial-gradient(at 50% 90%, rgba(245, 158, 11, 0.07) 0px, transparent 45%),
            radial-gradient(#e2e8f0 1.25px, transparent 1.25px) !important;
        background-size: 100% 100%, 100% 100%, 100% 100%, 28px 28px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
        overflow: hidden !important;
        padding: 2rem 1rem !important;
    }
    .dark .fi-simple-layout {
        background-color: #0b0f19 !important;
        background-image: 
            radial-gradient(at 15% 15%, rgba(245, 158, 11, 0.14) 0px, transparent 40%),
            radial-gradient(at 85% 15%, rgba(217, 119, 6, 0.1) 0px, transparent 40%),
            radial-gradient(at 50% 90%, rgba(245, 158, 11, 0.08) 0px, transparent 45%),
            radial-gradient(rgba(255, 255, 255, 0.06) 1.25px, transparent 1.25px) !important;
    }

    /* Ambient decorative soft blurred light beams */
    .fi-simple-layout::before {
        content: "";
        position: absolute;
        top: -160px;
        left: 50%;
        transform: translateX(-50%);
        width: 800px;
        height: 420px;
        background: radial-gradient(50% 50% at 50% 50%, rgba(245, 158, 11, 0.22) 0%, rgba(245, 158, 11, 0) 100%);
        filter: blur(60px);
        pointer-events: none;
        z-index: 0;
    }

    .fi-simple-main-ctn {
        width: 100% !important;
        max-width: 29.5rem !important;
        padding: 0 !important;
        position: relative !important;
        z-index: 1 !important;
    }

    /* Elegant Floating Glassmorphic Card */
    .fi-simple-main {
        position: relative !important;
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(226, 232, 240, 0.95) !important;
        border-radius: 1.5rem !important;
        padding: 2.75rem 2.5rem !important;
        box-shadow: 
            0 25px 50px -12px rgba(15, 23, 42, 0.08),
            0 12px 24px -8px rgba(15, 23, 42, 0.04),
            0 0 0 1px rgba(255, 255, 255, 0.9) inset !important;
        margin: 0 !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease !important;
        overflow: hidden !important;
    }
    
    /* Top subtle amber gradient border accent */
    .fi-simple-main::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 50%, #d97706 100%);
        opacity: 0.95;
    }

    .dark .fi-simple-main {
        background: rgba(17, 24, 39, 0.9) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow: 
            0 30px 60px -15px rgba(0, 0, 0, 0.7),
            0 0 0 1px rgba(255, 255, 255, 0.08) inset !important;
    }

    /* Login Header & Logo Alignment - Compact & Natural */
    .fi-simple-header {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
        margin-bottom: 1.75rem !important;
    }

    .fi-simple-header-logo-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        margin-bottom: 1rem !important;
        max-width: 100% !important;
    }
    .dark .fi-simple-header-logo-wrapper {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .fi-simple-header-logo-wrapper .fi-logo {
        max-height: 3.25rem !important;
        max-width: 100% !important;
        height: auto !important;
        width: auto !important;
        object-fit: contain !important;
        filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.04));
    }

    .custom-login-brand-text {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.03em !important;
        color: #0f172a !important;
        background: linear-gradient(135deg, #0f172a 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        padding-inline: 0.5rem;
    }
    .dark .custom-login-brand-text {
        background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .fi-simple-header-heading {
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif !important;
        font-size: 1.5rem !important;
        font-weight: 600 !important;
        letter-spacing: -0.015em !important;
        color: #0f172a !important;
        margin: 0 !important;
        line-height: 1.3 !important;
    }
    .dark .fi-simple-header-heading {
        color: #f8fafc !important;
    }

    .fi-simple-header-subheading {
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif !important;
        font-size: 0.825rem !important;
        color: #64748b !important;
        margin-top: 0.35rem !important;
        line-height: 1.55 !important;
        font-weight: 400 !important;
        max-width: 22rem;
        margin-inline: auto;
    }
    .dark .fi-simple-header-subheading {
        color: #94a3b8 !important;
    }

    /* Input Labels */
    .fi-simple-main .fi-fo-field-wrp-label span {
        font-size: 0.825rem !important;
        font-weight: 600 !important;
        color: #334155 !important;
        letter-spacing: 0.01em !important;
    }
    .dark .fi-simple-main .fi-fo-field-wrp-label span {
        color: #cbd5e1 !important;
    }

    /* Input Fields Styling */
    .fi-simple-main .fi-input-wrp {
        border-radius: 0.75rem !important;
        border: 1px solid rgba(203, 213, 225, 0.85) !important;
        background-color: #f8fafc !important;
        transition: all 0.2s ease-in-out !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
    }
    .dark .fi-simple-main .fi-input-wrp {
        background-color: rgba(15, 23, 42, 0.5) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    .fi-simple-main .fi-input-wrp:hover {
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
    }
    .dark .fi-simple-main .fi-input-wrp:hover {
        border-color: rgba(255, 255, 255, 0.25) !important;
    }

    .fi-simple-main .fi-input-wrp:focus-within {
        border-color: #f59e0b !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3.5px rgba(245, 158, 11, 0.18), 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }
    .dark .fi-simple-main .fi-input-wrp:focus-within {
        background-color: rgba(15, 23, 42, 0.8) !important;
        box-shadow: 0 0 0 3.5px rgba(245, 158, 11, 0.28), 0 1px 2px 0 rgba(0, 0, 0, 0.2) !important;
    }

    /* Input text style */
    .fi-simple-main .fi-input-wrp input {
        font-size: 0.925rem !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
        font-weight: 500 !important;
        color: #0f172a !important;
    }
    .dark .fi-simple-main .fi-input-wrp input {
        color: #f8fafc !important;
    }

    /* Remember me checkbox */
    .fi-simple-main .fi-checkbox-input {
        border-radius: 0.375rem !important;
        border-color: #cbd5e1 !important;
        cursor: pointer !important;
        transition: all 0.15s ease-in-out !important;
    }
    .fi-simple-main .fi-checkbox-input:checked {
        background-color: #f59e0b !important;
        border-color: #f59e0b !important;
    }

    /* Login Submit Button (Amber Theme matching Brand with 3D Depth) */
    .fi-simple-main .fi-btn {
        width: 100% !important;
        border-radius: 0.75rem !important;
        padding-top: 0.875rem !important;
        padding-bottom: 0.875rem !important;
        font-weight: 650 !important;
        font-size: 0.975rem !important;
        letter-spacing: 0.015em !important;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.35), 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        border: none !important;
        cursor: pointer !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-simple-main .fi-btn:hover {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        box-shadow: 0 6px 22px rgba(245, 158, 11, 0.48) !important;
        transform: translateY(-1.5px);
    }

    .fi-simple-main .fi-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25) !important;
    }

    /* Subtle security badge at footer */
    .custom-login-footer-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 2rem;
        font-size: 0.775rem;
        color: #94a3b8;
        font-weight: 500;
        text-align: center;
    }
    .custom-login-footer-badge svg {
        width: 0.95rem;
        height: 0.95rem;
        stroke: currentColor;
    }
    .dark .custom-login-footer-badge {
        color: #64748b;
    }

    /* ==========================================================================
       ENTERPRISE LUXURY TOASTER NOTIFICATIONS (Shopify / Stripe / Vercel Standard)
       ========================================================================== */
    .fi-no-notification {
        border-radius: 1rem !important;
        padding: 1rem 1.25rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
        backdrop-filter: blur(12px) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
    }
    .dark .fi-no-notification {
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 25px 30px -5px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
    }

    .fi-no-notification-title {
        font-size: 0.95rem !important;
        font-weight: 650 !important;
        letter-spacing: -0.015em !important;
        line-height: 1.35 !important;
    }

    .fi-no-notification-body {
        font-size: 0.85rem !important;
        color: #64748b !important;
        line-height: 1.45 !important;
        margin-top: 0.25rem !important;
    }
    .dark .fi-no-notification-body {
        color: #94a3b8 !important;
    }

    /* ==========================================================================
       ENTERPRISE MODAL CENTERING (Shopify / Stripe Standard - Exactly Centered)
       ========================================================================== */
    .fi-modal:not(.fi-modal-slide-over) > .fi-modal-window-ctn {
        grid-template-rows: 1fr auto 1fr !important;
        align-items: center !important;
        justify-items: center !important;
    }

    @media (min-width: 640px) {
        .fi-modal:not(.fi-modal-slide-over) > .fi-modal-window-ctn {
            grid-template-rows: 1fr auto 1fr !important;
        }
    }

    .fi-modal:not(.fi-modal-slide-over) > .fi-modal-window-ctn > .fi-modal-window {
        margin: auto !important;
        align-self: center !important;
        justify-self: center !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
        border-radius: 1.25rem !important;
    }
    .dark .fi-modal:not(.fi-modal-slide-over) > .fi-modal-window-ctn > .fi-modal-window {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
    }

    /* ==========================================================================
       CHART WIDGET RESPONSIVENESS & PREMIUM SELECT STYLING
       ========================================================================== */
    .fi-wi-chart .fi-section-header {
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
        align-items: center !important;
    }

    .fi-wi-chart .fi-section-header-text-ctn {
        min-width: 10rem !important;
        flex: 1 1 auto !important;
    }

    .fi-wi-chart .fi-section-header-after-ctn {
        margin-left: auto !important;
        flex-shrink: 0 !important;
        max-width: 100% !important;
    }

    /* Enterprise Clean Filter Pill Select */
    .fi-wi-chart .fi-wi-chart-filter.fi-input-wrp {
        border-radius: 0.625rem !important;
        background-color: #ffffff !important;
        border: 1px solid rgba(203, 213, 225, 0.8) !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 0.15s ease-in-out !important;
    }
    .dark .fi-wi-chart .fi-wi-chart-filter.fi-input-wrp {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        box-shadow: none !important;
    }

    .fi-wi-chart .fi-wi-chart-filter.fi-input-wrp:hover {
        border-color: #d97706 !important;
    }

    .fi-wi-chart .fi-wi-chart-filter select.fi-select-input {
        font-size: 0.825rem !important;
        font-weight: 600 !important;
        color: #334155 !important;
        padding-top: 0.4rem !important;
        padding-bottom: 0.4rem !important;
        padding-left: 0.85rem !important;
        padding-right: 2.25rem !important;
        cursor: pointer !important;
        border-radius: 0.625rem !important;
        outline: none !important;
    }
    .dark .fi-wi-chart .fi-wi-chart-filter select.fi-select-input {
        color: #f1f5f9 !important;
    }

    /* Modern Dropdown Option styling inside native popup */
    .fi-wi-chart .fi-wi-chart-filter select.fi-select-input option {
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        padding: 0.6rem 0.85rem !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
    }
    .dark .fi-wi-chart .fi-wi-chart-filter select.fi-select-input option {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }

    @media (max-width: 639px) {
        .fi-wi-chart .fi-section-header {
            flex-direction: column !important;
            align-items: flex-start !important;
        }

        .fi-wi-chart .fi-section-header-after-ctn {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .fi-wi-chart .fi-wi-chart-filter.fi-input-wrp {
            width: 100% !important;
        }

        .fi-wi-chart .fi-wi-chart-filter select.fi-select-input {
            width: 100% !important;
        }
    }

    /* ==========================================================================
       LATEST ORDERS WIDGET: SAME ROW SEARCHBOX & COMPACT ENTERPRISE HEADER
       ========================================================================== */
    @media (min-width: 640px) {
        .fi-ta-ctn > .fi-ta-main > .fi-ta-header-ctn {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 0.875rem 1.5rem !important;
            gap: 1rem !important;
        }
        .dark .fi-ta-ctn > .fi-ta-main > .fi-ta-header-ctn {
            border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        }

        .fi-ta-ctn .fi-ta-header {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            border-bottom: none !important;
            padding: 0 !important;
            gap: 1.25rem !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        .fi-ta-ctn .fi-ta-header > div:first-child {
            flex-shrink: 0 !important;
        }

        .fi-ta-ctn .fi-ta-header .fi-ta-actions {
            margin-inline-start: 0 !important;
            margin-left: 0 !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            border-bottom: none !important;
            border-top: none !important;
            padding: 0 !important;
            margin-left: auto !important;
            gap: 0.75rem !important;
            flex-shrink: 0 !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar > .fi-ta-actions:empty,
        .fi-ta-ctn .fi-ta-header-toolbar > div:first-child:empty,
        .fi-ta-ctn .fi-ta-header-toolbar > div:first-child:not(:has(*:not(:empty))) {
            display: none !important;
            min-height: 0 !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar > div:has(.fi-ta-search-field) {
            margin-left: 0 !important;
            min-height: auto !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar .fi-ta-search-field {
            min-width: 16rem !important;
            max-width: 22rem !important;
            width: 18rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar .fi-ta-search-field:focus-within {
            max-width: 24rem !important;
            width: 22rem !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar .fi-ta-search-field .fi-input-wrp {
            border-radius: 0.625rem !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        }
    }

    /* Phone Version: Compact Spacing & Zero Extra Gap (Below 640px) */
    @media (max-width: 639px) {
        .fi-ta-ctn .fi-ta-header-ctn {
            padding: 0.75rem 0.85rem !important;
            border-bottom: 1px solid rgba(229, 231, 235, 0.8) !important;
        }
        .dark .fi-ta-ctn .fi-ta-header-ctn {
            border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        }

        .fi-ta-ctn .fi-ta-header {
            padding-bottom: 0.5rem !important;
            border-bottom: none !important;
        }

        .fi-ta-ctn .fi-ta-header-toolbar {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: nowrap !important;
            padding: 0.75rem 0.85rem !important; /* Perfect top and bottom spacing so search never touches top border */
            border-top: none !important;
            border-bottom: 1px solid rgba(229, 231, 235, 0.8) !important;
            gap: 0.5rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .dark .fi-ta-ctn .fi-ta-header-toolbar {
            border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        }

        .fi-ta-header-toolbar > .fi-ta-actions:empty,
        .fi-ta-header-toolbar > div:first-child:empty,
        .fi-ta-header-toolbar > div:first-child:not(:has(*:not(:empty))) {
            display: none !important;
            min-height: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .fi-ta-header-toolbar > div:has(.fi-ta-search-field),
        .fi-ta-header-toolbar > div:last-child {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            gap: 0.65rem !important;
            min-height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .fi-ta-header-toolbar .fi-ta-search-field {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            width: auto !important;
            margin: 0 !important;
        }

        .fi-ta-header-toolbar .fi-ta-search-field .fi-input-wrp {
            width: 100% !important;
            margin: 0 !important;
        }

        /* Filter trigger and column manager buttons on mobile */
        .fi-ta-header-toolbar .fi-ta-filters-dropdown,
        .fi-ta-header-toolbar .fi-ta-col-manager-dropdown,
        .fi-ta-header-toolbar .fi-icon-btn {
            flex-shrink: 0 !important;
        }

        /* Order status tabs: smooth horizontal swipe on small devices */
        .fi-tabs {
            max-width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            white-space: nowrap !important;
            scrollbar-width: none !important;
        }
        .fi-tabs::-webkit-scrollbar {
            display: none !important;
        }

        /* Pagination on mobile: compact and wraps cleanly */
        .fi-pagination {
            flex-direction: column !important;
            align-items: center !important;
            gap: 0.75rem !important;
            padding: 0.75rem 1rem !important;
        }

        .fi-pagination > div {
            width: 100% !important;
            justify-content: center !important;
            text-align: center !important;
        }

        /* Table container on mobile: prevent overflow blowout */
        .fi-ta-ctn {
            max-width: 100% !important;
            overflow: hidden !important;
            border-radius: 0.75rem !important;
        }
    }
</style>
