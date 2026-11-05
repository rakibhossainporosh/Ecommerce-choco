import { ref, computed } from 'vue';
import { trackMeta } from './useMetaPixel';

const isCartOpen = ref(false);
const isWishlistOpen = ref(false);
const toast = ref({
    visible: false,
    title: '',
    body: '',
});
let toastTimer = null;

const showToast = (title, body = '') => {
    toast.value = { visible: true, title, body };
    if (typeof window !== 'undefined') {
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.value.visible = false;
        }, 3200);
    }
};

const dismissToast = () => {
    toast.value.visible = false;
    if (typeof window !== 'undefined') {
        window.clearTimeout(toastTimer);
    }
};

// Load from localStorage or start empty
const savedCart = typeof window !== 'undefined' ? localStorage.getItem('nobo_cart') : null;
const cartItems = ref(savedCart ? JSON.parse(savedCart) : []);

// Wishlist
const savedWishlist = typeof window !== 'undefined' ? localStorage.getItem('nobo_wishlist') : null;
let parsedWishlist = savedWishlist ? JSON.parse(savedWishlist) : [];
if (parsedWishlist.length > 0 && typeof parsedWishlist[0] === 'number') {
    parsedWishlist = []; // Clear old format
}
const wishlistItems = ref(parsedWishlist);

const saveCart = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('nobo_cart', JSON.stringify(cartItems.value));
        scheduleRemember();
    }
};

let rememberTimer = null;
let cartContact = { name: '', phone: '', email: '' };

const scheduleRemember = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.clearTimeout(rememberTimer);
    rememberTimer = window.setTimeout(postRemember, 1500);
};

const postRemember = () => {
    const customer = window.__noboCustomer || null;
    const phone = String(cartContact.phone || customer?.phone || '').replace(/\D/g, '');
    const hasIdentity = Boolean(customer?.id) || phone.length >= 10;

    if (!hasIdentity) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/cart/remember', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
            name: cartContact.name || customer?.name || '',
            phone: cartContact.phone || customer?.phone || '',
            email: cartContact.email || customer?.email || '',
            items: cartItems.value.map((item) => ({
                id: item.id,
                variant_id: item.variant_id || item.id,
                quantity: item.quantity,
            })),
        }),
    }).catch(() => {});
};

export function setCartContact(next) {
    cartContact = { ...cartContact, ...next };
    scheduleRemember();
}

let wishlistSyncInFlight = false;

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const wishlistHeaders = () => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': csrfToken(),
});

const saveWishlist = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('nobo_wishlist', JSON.stringify(wishlistItems.value));
    }
};

const replaceWishlist = (items) => {
    wishlistItems.value = Array.isArray(items) ? items : [];
    saveWishlist();
};

const syncWishlistWithAccount = async () => {
    if (typeof window === 'undefined' || !window.__noboCustomer?.id || wishlistSyncInFlight) {
        return;
    }

    wishlistSyncInFlight = true;

    try {
        const localIds = wishlistItems.value.map((item) => item.id).filter(Boolean);
        const response = await fetch('/account/wishlist/sync', {
            method: 'POST',
            headers: wishlistHeaders(),
            body: JSON.stringify({ product_ids: localIds }),
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        replaceWishlist(payload.items || []);
    } catch {
        // Keep local wishlist if sync fails.
    } finally {
        wishlistSyncInFlight = false;
    }
};

export function setCartCustomer(customer) {
    const previousId = window.__noboCustomer?.id || null;
    window.__noboCustomer = customer || null;

    if (customer?.id && customer.id !== previousId) {
        if (cartItems.value.length > 0) {
            scheduleRemember();
        }

        syncWishlistWithAccount();
    }
}

export function useCart() {
    const addToCart = (product, quantity = 1, variant = null) => {
        if (product.is_in_stock === false || (variant && variant.is_in_stock === false)) {
            return;
        }

        // Find default variant id if none is provided
        const defaultVariantId = product.default_variant_id || product.id;
        const variantId = variant ? variant.id : defaultVariantId;
        
        const price = variant ? variant.price : product.price;
        const baseName = product.display_name || product.name;
        let name = baseName;
        if (variant && variant.options && Object.keys(variant.options).length > 0) {
            name = `${baseName} - ${Object.values(variant.options).join(' ')}`;
        }
        
        const cartItemId = `${product.id}-${variantId}`;

        const existing = cartItems.value.find((item) => item.cartItemId === cartItemId);
        if (existing) {
            existing.quantity += quantity;
        } else {
            cartItems.value.push({
                cartItemId: cartItemId,
                id: product.id,
                variant_id: variantId,
                name: name,
                price: price,
                quantity: quantity,
                image: product.image,
            });
        }
        saveCart();
        showToast('', name);
        isCartOpen.value = true;
        trackMeta('AddToCart', {
            content_ids: [String(product.id)],
            content_name: name,
            content_type: 'product',
            contents: [{
                id: String(product.id),
                quantity: Number(quantity),
                item_price: Number(price),
            }],
            num_items: Number(quantity),
            value: Number(price) * Number(quantity),
        });
    };

    const updateQuantity = (cartItemId, delta) => {
        const item = cartItems.value.find((i) => (i.cartItemId || i.id) === cartItemId);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                removeFromCart(cartItemId);
                return;
            }
            saveCart();
        }
    };

    const removeFromCart = (cartItemId) => {
        cartItems.value = cartItems.value.filter((i) => (i.cartItemId || i.id) !== cartItemId);
        saveCart();
    };

    const clearCart = () => {
        cartItems.value = [];
        saveCart();
    };

    const toggleWishlist = (product) => {
        const index = wishlistItems.value.findIndex((item) => item.id === product.id);
        const removing = index > -1;

        if (removing) {
            wishlistItems.value.splice(index, 1);
        } else {
            wishlistItems.value.push({
                id: product.id,
                name: product.display_name || product.name,
                price: product.price,
                image: product.image,
                category_name: product.category_display_name || product.category_name,
                compare_at_price: product.compare_at_price,
                display_name: product.display_name,
                category_display_name: product.category_display_name,
                is_in_stock: product.is_in_stock,
                slug: product.slug,
            });
            trackMeta('AddToWishlist', {
                content_ids: [String(product.id)],
                content_name: product.display_name || product.name,
                content_type: 'product',
                value: Number(product.price || 0),
            });
        }
        saveWishlist();

        if (typeof window !== 'undefined' && window.__noboCustomer?.id) {
            fetch('/account/wishlist/toggle', {
                method: 'POST',
                headers: wishlistHeaders(),
                body: JSON.stringify({ product_id: product.id }),
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((payload) => {
                    if (payload?.items) {
                        replaceWishlist(payload.items);
                    }
                })
                .catch(() => {});
        }
    };

    const isInWishlist = (productId) => {
        return wishlistItems.value.some((item) => item.id === productId);
    };

    const totalAmount = computed(() => {
        return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0);
    });

    const totalCount = computed(() => {
        return cartItems.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    const wishlistCount = computed(() => wishlistItems.value.length);

    return {
        isCartOpen,
        isWishlistOpen,
        cartItems,
        wishlistItems,
        toast,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        toggleWishlist,
        isInWishlist,
        totalAmount,
        totalCount,
        wishlistCount,
        showToast,
        dismissToast,
        openCart: () => (isCartOpen.value = true),
        closeCart: () => (isCartOpen.value = false),
        openWishlist: () => (isWishlistOpen.value = true),
        closeWishlist: () => (isWishlistOpen.value = false),
    };
}
