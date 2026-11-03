{{-- Dynamic topbar title — SPA/Livewire events only (no body MutationObserver). --}}
<div
    id="custom-topbar-page-heading-container"
    class="custom-topbar-page-heading-wrapper"
    x-data="{
        title: '',
        updateTitle() {
            const headingEl = document.querySelector('.fi-header-heading');
            if (headingEl) {
                this.title = headingEl.innerText.trim();
                return;
            }
            this.title = (document.title.split(' - ')[0] || '').trim();
        }
    }"
    x-init="
        updateTitle();
        document.addEventListener('livewire:navigated', () => updateTitle());
    "
>
    <h1 class="custom-topbar-title-text" x-text="title" x-show="title"></h1>
</div>
