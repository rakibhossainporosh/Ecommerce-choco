import { ref, computed } from 'vue';

const isCartOpen = ref(false);

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
const wishlistItems = ref(savedWishlist ? JSON.parse(savedWishlist) : [1, 2]);

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
    const addToCart = (product, quantity = 1) => {
        const existing = cartItems.value.find((item) => item.id === product.id);
        if (existing) {
            existing.quantity += quantity;
        } else {
            cartItems.value.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: quantity,
                image: product.image,
            });
        }
        saveCart();
        isCartOpen.value = true;
    };

    const updateQuantity = (id, delta) => {
        const item = cartItems.value.find((i) => i.id === id);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                removeFromCart(id);
                return;
            }
            saveCart();
        }
    };

    const removeFromCart = (id) => {
        cartItems.value = cartItems.value.filter((i) => i.id !== id);
        saveCart();
    };

    const toggleWishlist = (productId) => {
        if (wishlistItems.value.includes(productId)) {
            wishlistItems.value = wishlistItems.value.filter((id) => id !== productId);
        } else {
            wishlistItems.value.push(productId);
        }
        saveWishlist();
    };

    const isInWishlist = (productId) => {
        return wishlistItems.value.includes(productId);
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
        cartItems,
        wishlistItems,
        addToCart,
        updateQuantity,
        removeFromCart,
        toggleWishlist,
        isInWishlist,
        totalAmount,
        totalCount,
        wishlistCount,
        openCart: () => (isCartOpen.value = true),
        closeCart: () => (isCartOpen.value = false),
    };
}
