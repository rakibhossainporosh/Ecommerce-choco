import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useCart } from './useCart';
import { useI18n } from './useI18n';

export function useProductDetailPage(props) {
    const page = usePage();
    const occasion = computed(() => page.props.site_settings?.occasion || null);
    const { t, tm } = useI18n();
    const { addToCart, toggleWishlist, isInWishlist } = useCart();

    const productTitle = computed(() => props.product.display_name || props.product.name);
    const productCategory = computed(() => props.product.category_display_name || props.product.category_name);
    const productShortDesc = computed(() => props.product.display_short_description || props.product.short_description || t('product.fallback_short'));
    const productDescriptionHtml = computed(() => props.product.display_description || props.product.description);
    const quantity = ref(1);
    const activeImage = ref(props.product.image);
    const showReviewForm = ref(false);
    const hoverRating = ref(0);

    const images = computed(() => {
        if (props.product.images && props.product.images.length > 0) {
            return props.product.images;
        }

        return [props.product.image];
    });

    const selectedOptions = ref({});
    if (props.product.available_options) {
        for (const [attrName, values] of Object.entries(props.product.available_options)) {
            if (values.length > 0) {
                selectedOptions.value[attrName] = values[0];
            }
        }
    }

    const currentVariant = computed(() => {
        if (!props.product.variants || props.product.variants.length === 0) {
            return null;
        }

        return props.product.variants.find((variant) => {
            return Object.entries(selectedOptions.value).every(([attrName, value]) => {
                return variant.options && variant.options[attrName] === value;
            });
        }) || props.product.variants[0];
    });

    const currentPrice = computed(() => (currentVariant.value ? currentVariant.value.price : props.product.price));
    const currentComparePrice = computed(() => (currentVariant.value ? currentVariant.value.compare_at_price : props.product.compare_at_price));

    const isCurrentInStock = computed(() => {
        if (currentVariant.value && currentVariant.value.is_in_stock !== undefined) {
            return currentVariant.value.is_in_stock;
        }

        return props.product.is_in_stock !== false;
    });

    const currentStockQty = computed(() => {
        if (currentVariant.value && currentVariant.value.stock_quantity !== undefined) {
            return currentVariant.value.stock_quantity;
        }

        return props.product.stock_quantity;
    });

    const formatPrice = (val) => {
        if (!val) {
            return '';
        }

        return '৳ ' + Number(val).toLocaleString('en-US');
    };

    const handleAddToCart = () => {
        if (props.product && isCurrentInStock.value) {
            addToCart(props.product, quantity.value, currentVariant.value);
        }
    };

    const incrementQuantity = () => {
        if (currentStockQty.value !== null && currentStockQty.value !== undefined && quantity.value >= currentStockQty.value) {
            return;
        }
        quantity.value++;
    };

    const decrementQuantity = () => {
        if (quantity.value > 1) {
            quantity.value--;
        }
    };

    const reviewForm = useForm({
        rating: 5,
        reviewer_name: page.props.customer?.name || '',
        reviewer_email: page.props.customer?.email || '',
        title: '',
        body: '',
    });

    const reviewSuccess = ref(false);

    const ratingLabels = computed(() => {
        const labels = tm('product.rating_labels') || [];

        return {
            0: labels[0] || t('product.your_rating'),
            1: labels[1] || '',
            2: labels[2] || '',
            3: labels[3] || '',
            4: labels[4] || '',
            5: labels[5] || '',
        };
    });

    const setRating = (star) => {
        if (reviewForm.rating === star) {
            reviewForm.rating = star - 1;
        } else {
            reviewForm.rating = star;
        }
        hoverRating.value = 0;
    };

    const setHover = (star) => {
        hoverRating.value = star;
    };

    const clearHover = () => {
        hoverRating.value = 0;
    };

    const submitReview = () => {
        reviewForm.post(`/product/${props.product.slug}/reviews`, {
            preserveScroll: true,
            onSuccess: () => {
                reviewForm.reset('title', 'body');
                showReviewForm.value = false;
                reviewSuccess.value = true;
                setTimeout(() => {
                    reviewSuccess.value = false;
                }, 6000);
            },
        });
    };

    return {
        page,
        occasion,
        t,
        productTitle,
        productCategory,
        productShortDesc,
        productDescriptionHtml,
        quantity,
        activeImage,
        showReviewForm,
        hoverRating,
        images,
        selectedOptions,
        currentVariant,
        currentPrice,
        currentComparePrice,
        isCurrentInStock,
        currentStockQty,
        formatPrice,
        handleAddToCart,
        incrementQuantity,
        decrementQuantity,
        reviewForm,
        reviewSuccess,
        ratingLabels,
        setRating,
        setHover,
        clearHover,
        submitReview,
        addToCart,
        toggleWishlist,
        isInWishlist,
    };
}
