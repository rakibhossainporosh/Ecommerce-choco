{{-- Dynamic Topbar Page Title (Synchronized across all admin panel pages) --}}
<div id="custom-topbar-page-heading-container" class="custom-topbar-page-heading-wrapper" x-data="{
    title: '',
    updateTitle() {
        // Find page header heading
        const headingEl = document.querySelector('.fi-header-heading');
        if (headingEl) {
            this.title = headingEl.innerText.trim();
        } else {
            // Fallback from document.title
            const docTitle = document.title.split(' - ')[0] || '';
            this.title = docTitle.trim();
        }
    }
}" x-init="
    updateTitle();
    // Observe DOM mutations to react immediately to Livewire/SPA navigation
    const observer = new MutationObserver(() => updateTitle());
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
">
    <h1 class="custom-topbar-title-text" x-text="title" x-show="title"></h1>
</div>
