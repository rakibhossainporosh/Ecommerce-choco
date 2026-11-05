<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Home, LayoutGrid, Heart, ShoppingBag, User, X, ChevronRight } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { useCart } from '../Composables/useCart';
import { useI18n } from '../Composables/useI18n';
import { useStoreTheme } from '../Composables/useStoreTheme';

const page = usePage();
const { t } = useI18n();
const { theme } = useStoreTheme();
const { totalCount, wishlistCount, openCart, openWishlist, isCartOpen, isWishlistOpen } = useCart();

const categoriesOpen = ref(false);
const categories = computed(() => page.props.global_categories || []);

const hideOnCheckout = computed(() => (page.url || '').startsWith('/checkout'));

const syncCheckoutNavClass = () => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('store-mobile-nav-hidden', hideOnCheckout.value);
};

watch(hideOnCheckout, syncCheckoutNavClass, { immediate: true });
onMounted(syncCheckoutNavClass);
onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.remove('store-mobile-nav-hidden');
    }
});

const isHomeActive = computed(() => {
    const url = page.url || '';

    return url === '/' || url.startsWith('/?');
});

const isAccountActive = computed(() => {
    const url = page.url || '';

    return url.startsWith('/account') || url.startsWith('/login') || url.startsWith('/register');
});

const accountHref = computed(() => (page.props.customer ? '/account' : '/login'));

const shell = computed(() => {
    if (theme.value === 'grove') {
        return {
            bar: 'store-mobile-app-nav--grove',
            active: 'is-active',
            badge: 'store-mobile-app-nav__badge',
            sheet: 'store-mobile-app-nav-sheet--grove',
            sheetItem: 'store-mobile-app-nav-sheet__item',
        };
    }

    if (theme.value === 'harbor') {
        return {
            bar: 'store-mobile-app-nav--harbor',
            active: 'is-active',
            badge: 'store-mobile-app-nav__badge',
            sheet: 'store-mobile-app-nav-sheet--harbor',
            sheetItem: 'store-mobile-app-nav-sheet__item',
        };
    }

    return {
        bar: 'store-mobile-app-nav--warm',
        active: 'is-active',
        badge: 'store-mobile-app-nav__badge',
        sheet: 'store-mobile-app-nav-sheet--warm',
        sheetItem: 'store-mobile-app-nav-sheet__item',
    };
});

watch(() => page.url, () => {
    categoriesOpen.value = false;
});

const openCategories = () => {
    categoriesOpen.value = true;
};

const closeCategories = () => {
    categoriesOpen.value = false;
};

const onCart = () => {
    closeCategories();
    openCart();
};

const onWishlist = () => {
    closeCategories();
    openWishlist();
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="categoriesOpen && !hideOnCheckout"
            class="store-mobile-app-nav-overlay lg:hidden"
            @click="closeCategories"
        />

        <div
            v-if="categoriesOpen && !hideOnCheckout"
            class="store-mobile-app-nav-sheet lg:hidden"
            :class="shell.sheet"
            role="dialog"
            aria-modal="true"
            :aria-label="t('header.categories')"
        >
            <div class="store-mobile-app-nav-sheet__head">
                <h2>{{ t('header.categories') }}</h2>
                <button type="button" :aria-label="t('header.clear')" @click="closeCategories">
                    <X class="w-4 h-4" />
                </button>
            </div>
            <div class="store-mobile-app-nav-sheet__body">
                <p v-if="!categories.length" class="store-mobile-app-nav-sheet__empty">
                    {{ t('header.no_categories') }}
                </p>
                <template v-else>
                    <div v-for="cat in categories" :key="cat.id">
                        <Link
                            :href="'/category/' + cat.slug"
                            class="store-mobile-app-nav-sheet__item"
                            :class="shell.sheetItem"
                            @click="closeCategories"
                        >
                            <span>{{ cat.display_name || cat.name }}</span>
                            <ChevronRight class="w-4 h-4 opacity-40 shrink-0" />
                        </Link>
                        <Link
                            v-for="child in (cat.children || [])"
                            :key="child.id"
                            :href="'/category/' + child.slug"
                            class="store-mobile-app-nav-sheet__item store-mobile-app-nav-sheet__item--child"
                            :class="shell.sheetItem"
                            @click="closeCategories"
                        >
                            <span>{{ child.display_name || child.name }}</span>
                        </Link>
                    </div>
                </template>
            </div>
        </div>

        <!-- Edge-to-edge fixed tab bar (app chrome — not a floating pill) -->
        <nav
            v-show="!hideOnCheckout"
            class="store-mobile-app-nav lg:hidden"
            :class="shell.bar"
            aria-label="Mobile navigation"
        >
            <div class="store-mobile-app-nav__row">
                <Link
                    href="/"
                    class="store-mobile-app-nav__tab"
                    :class="{ [shell.active]: isHomeActive && !categoriesOpen }"
                >
                    <Home class="store-mobile-app-nav__icon" :stroke-width="isHomeActive && !categoriesOpen ? 2.35 : 1.9" />
                    <span>{{ t('header.nav_home') }}</span>
                </Link>

                <button
                    type="button"
                    class="store-mobile-app-nav__tab"
                    :class="{ [shell.active]: categoriesOpen }"
                    @click="openCategories"
                >
                    <LayoutGrid class="store-mobile-app-nav__icon" :stroke-width="categoriesOpen ? 2.35 : 1.9" />
                    <span>{{ t('header.nav_categories') }}</span>
                </button>

                <button
                    type="button"
                    class="store-mobile-app-nav__tab"
                    :class="{ [shell.active]: isCartOpen }"
                    @click="onCart"
                >
                    <span class="store-mobile-app-nav__icon-wrap">
                        <ShoppingBag class="store-mobile-app-nav__icon" :stroke-width="isCartOpen ? 2.35 : 1.9" />
                        <span v-if="totalCount > 0" :class="shell.badge">{{ totalCount > 99 ? '99+' : totalCount }}</span>
                    </span>
                    <span>{{ t('header.nav_cart') }}</span>
                </button>

                <button
                    type="button"
                    class="store-mobile-app-nav__tab"
                    :class="{ [shell.active]: isWishlistOpen }"
                    @click="onWishlist"
                >
                    <span class="store-mobile-app-nav__icon-wrap">
                        <Heart class="store-mobile-app-nav__icon" :stroke-width="isWishlistOpen ? 2.35 : 1.9" />
                        <span v-if="wishlistCount > 0" :class="shell.badge">{{ wishlistCount > 99 ? '99+' : wishlistCount }}</span>
                    </span>
                    <span>{{ t('header.nav_wishlist') }}</span>
                </button>

                <Link
                    :href="accountHref"
                    class="store-mobile-app-nav__tab"
                    :class="{ [shell.active]: isAccountActive }"
                    @click="closeCategories"
                >
                    <User class="store-mobile-app-nav__icon" :stroke-width="isAccountActive ? 2.35 : 1.9" />
                    <span>{{ page.props.customer ? t('header.nav_account') : t('header.nav_login') }}</span>
                </Link>
            </div>
        </nav>
    </Teleport>
</template>
