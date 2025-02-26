/**
 * Added to Cart Popup Alpine.js Component
 */
export default () => ({
    isOpen: false,
    product: null,
    cartData: null,

    init() {
        // Listen for the product added to cart event
        document.addEventListener('product_added_to_cart', (e) => {
            this.product = e.detail.product;
            this.cartData = e.detail.cartData;
            this.open();
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    },

    open() {
        this.isOpen = true;
        document.body.classList.add('popup-open');
    },

    close() {
        this.isOpen = false;
        document.body.classList.remove('popup-open');
    }
});
