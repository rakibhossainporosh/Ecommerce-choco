import { ref, computed } from 'vue';

const isCartOpen = ref(false);
const isWishlistOpen = ref(false);

const initialCart = [
    {
        id: 1,
        name: 'কটন শার্ট',
        price: 1250,
        quantity: 1,
        image: '/images/storefront/product-1.jpg',
    },
    {
        id: 2,
        name: 'ক্লাসিক ঘড়ি',
        price: 2450,
        quantity: 1,
        image: '/images/storefront/product-2.jpg',
    },
    {
        id: 3,
        name: 'সিরামিক মগ',
        price: 450,
        quantity: 1,
        image: '/images/storefront/product-3.jpg',
    },
];

// Load from localStorage or use initialCart
const savedCart = typeof window !== 'undefined' ? localStorage.getItem('nobo_cart') : null;
const cartItems = ref(savedCart ? JSON.parse(savedCart) : initialCart);

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
    }
};

const saveWishlist = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('nobo_wishlist', JSON.stringify(wishlistItems.value));
    }
};

export function useCart() {
    const addToCart = (product, quantity = 1, variant = null) => {
        // Find default variant id if none is provided
        const defaultVariantId = product.default_variant_id || product.id;
        const variantId = variant ? variant.id : defaultVariantId;
        
        const price = variant ? variant.price : product.price;
        let name = product.name;
        if (variant && variant.options && Object.keys(variant.options).length > 0) {
            name = `${product.name} - ${Object.values(variant.options).join(' ')}`;
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
        isCartOpen.value = true;
    };

    const updateQuantity = (cartItemId, delta) => {
        const item = cartItems.value.find((i) => (i.cartItemId || i.id) === cartItemId);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                removeFromCart(id);
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
                name: product.name,
                price: product.price,
                image: product.image,
                category_name: product.category_name,
                compare_at_price: product.compare_at_price
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
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        toggleWishlist,
        isInWishlist,
        totalAmount,
        totalCount,
        wishlistCount,
        openCart: () => (isCartOpen.value = true),
        closeCart: () => (isCartOpen.value = false),
        openWishlist: () => (isWishlistOpen.value = true),
        closeWishlist: () => (isWishlistOpen.value = false),
    };
}
