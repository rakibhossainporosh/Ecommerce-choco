import { ref, computed } from 'vue';

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

export function setCartCustomer(customer) {
    const previousId = window.__noboCustomer?.id || null;
    window.__noboCustomer = customer || null;

    if (customer?.id && customer.id !== previousId && cartItems.value.length > 0) {
        scheduleRemember();
    }
}

const saveWishlist = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('nobo_wishlist', JSON.stringify(wishlistItems.value));
    }
};

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
        if (index > -1) {
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
        }
        saveWishlist();
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
