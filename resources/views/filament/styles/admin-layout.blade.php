{{-- Custom Admin Layout Styling (Matching ONETECH / GitHub style collapsible rail and full-width layout) --}}
<style>
    /* Full-width container across all admin pages with professional spacing */
    .fi-main {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }

    @media (min-width: 1024px) {
        .fi-main {
            padding-left: 2rem !important;
            padding-right: 2rem !important;
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

    /* Hide default out-of-place toggle and duplicate logo in topbar */
    .fi-topbar-collapse-sidebar-btn-ctn {
        display: none !important;
    }
    
    .fi-topbar-start {
        display: none !important;
    }

    /* Topbar Page Heading (Title in Topbar) */
    .custom-topbar-page-heading-wrapper {
        display: flex;
        align-items: center;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
        height: 100%;
        margin-right: auto;
    }

    .custom-topbar-title-text {
        font-size: 1.25rem !important;
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
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            overflow-x: hidden !important;
            scrollbar-gutter: auto !important;
            scrollbar-width: none !important; /* Firefox */
            -ms-overflow-style: none !important; /* IE and Edge */
        }
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav::-webkit-scrollbar {
            display: none !important; /* Chrome, Safari, Opera */
            width: 0 !important;
            height: 0 !important;
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
</style>
