/**
 * Buy Now Alpine.js Component
 */
export default () => ({
    loading: false,
    error: false,
    errorMessage: '',
    loadingText: '',
    isVariationSelected: false,

    /**
     * Initialize the component
     */
    init() {
        const productType = this.$el.dataset.productType;

        if (productType === 'variable') {
            // Listen for variation selection changes
            document.addEventListener('show_variation', (e) => {
                this.isVariationSelected = true;
            });

            document.addEventListener('hide_variation', (e) => {
                this.isVariationSelected = false;
            });
        }
    },

    /**
     * Buy now for simple products
     */
    async buyNow(event) {
        event.preventDefault();

        const productId = this.$el.dataset.productId;
        const quantity = event.target.dataset.quantity || 1;

        await this.processBuyNow({
            product_id: productId,
            quantity: quantity,
        });
    },

    /**
     * Buy now for variable products
     */
    async buyNowVariable(event) {
        event.preventDefault();

        if (!this.isVariationSelected) {
            this.showError(window.mesmericBuyNow.i18n.selectOptions);
            return;
        }

        const form = document.querySelector('form.variations_form');
        if (!form) {
            this.showError(window.mesmericBuyNow.i18n.error);
            return;
        }

        const formData = new FormData(form);
        const productId = this.$el.dataset.productId;
        const variationId = formData.get('variation_id');
        const quantity = event.target.dataset.quantity || 1;

        // Get variation attributes
        const variations = {};
        for (const [key, value] of formData.entries()) {
            if (key.startsWith('attribute_')) {
                variations[key] = value;
            }
        }

        await this.processBuyNow({
            product_id: productId,
            variation_id: variationId,
            quantity: quantity,
            variations: variations,
        });
    },

    /**
     * Process buy now request
     */
    async processBuyNow(data) {
        this.loading = true;
        this.error = false;
        this.loadingText = window.mesmericBuyNow.i18n.addingToCart;

        try {
            const params = new URLSearchParams({
                'buy-now': '1',
                'nonce': window.mesmericBuyNow.nonce,
                ...data,
            });

            // Redirect to checkout
            this.loadingText = window.mesmericBuyNow.i18n.redirectingToCheckout;
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        } catch (error) {
            console.error('Buy now error:', error);
            this.showError(window.mesmericBuyNow.i18n.error);
            this.loading = false;
        }
    },

    /**
     * Show error message
     */
    showError(message) {
        this.error = true;
        this.errorMessage = message;

        setTimeout(() => {
            this.error = false;
            this.errorMessage = '';
        }, 5000);
    },
});
