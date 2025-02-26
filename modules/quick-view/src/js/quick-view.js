/**
 * Quick View Module JavaScript
 * 
 * @since 1.0.0
 */

document.addEventListener('alpine:init', () => {
    // Quick View Button Component
    Alpine.data('quickViewButton', () => ({
        loading: false,

        /**
         * Open quick view modal
         * 
         * @param {number} productId Product ID
         */
        async openQuickView(productId) {
            this.loading = true;
            this.$dispatch('quick-view-open', { productId });
        }
    }));

    // Quick View Modal Component
    Alpine.data('quickViewModel', () => ({
        isOpen: false,
        loading: false,
        error: false,
        errorMessage: '',
        content: '',

        init() {
            // Listen for quick view open event
            this.$watch('isOpen', (value) => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                    this.content = '';
                    this.error = false;
                    this.errorMessage = '';
                }
            });

            this.$root.addEventListener('quick-view-open', async (event) => {
                this.isOpen = true;
                this.loading = true;
                this.error = false;
                this.errorMessage = '';

                try {
                    const response = await fetch(mesQuickView.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'mesmeric_commerce_quick_view',
                            nonce: mesQuickView.nonce,
                            product_id: event.detail.productId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.content = data.data.html;
                    } else {
                        throw new Error(data.data.message);
                    }
                } catch (error) {
                    this.error = true;
                    this.errorMessage = error.message || mesQuickView.i18n.error;
                } finally {
                    this.loading = false;
                }
            });
        },

        close() {
            this.isOpen = false;
        }
    }));

    // Quick View Gallery Component
    Alpine.data('quickViewGallery', () => ({
        zoomLevel: 1,
        panX: 0,
        panY: 0,
        isDragging: false,
        startX: 0,
        startY: 0,

        initGallery() {
            if (!mesQuickView.settings.zoomEnabled) {
                return;
            }

            this.$refs.mainImage.addEventListener('mousedown', (e) => {
                this.isDragging = true;
                this.startX = e.clientX - this.panX;
                this.startY = e.clientY - this.panY;
            });

            window.addEventListener('mousemove', (e) => {
                if (!this.isDragging) return;

                this.panX = e.clientX - this.startX;
                this.panY = e.clientY - this.startY;
                this.updateTransform();
            });

            window.addEventListener('mouseup', () => {
                this.isDragging = false;
            });

            this.$refs.mainImage.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.1 : 0.1;
                this.zoomLevel = Math.min(Math.max(1, this.zoomLevel + delta), 3);
                this.updateTransform();
            });
        },

        updateTransform() {
            this.$refs.mainImage.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.zoomLevel})`;
        },

        switchImage(event) {
            const imageUrl = event.currentTarget.dataset.imageUrl;
            this.$refs.mainImage.src = imageUrl;
            this.resetZoom();

            // Update active state
            this.$el.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        },

        zoomIn() {
            this.zoomLevel = Math.min(this.zoomLevel + 0.1, 3);
            this.updateTransform();
        },

        zoomOut() {
            this.zoomLevel = Math.max(this.zoomLevel - 0.1, 1);
            this.updateTransform();
        },

        resetZoom() {
            this.zoomLevel = 1;
            this.panX = 0;
            this.panY = 0;
            this.updateTransform();
        }
    }));

    // Quick View Add to Cart Component
    Alpine.data('quickViewAddToCart', () => ({
        loading: false,
        message: '',
        status: '',
        variations: {},
        isValid: true,

        async addToCart(event) {
            this.loading = true;
            this.message = '';
            this.status = '';

            try {
                const form = event.target;
                const formData = new FormData(form);
                formData.append('add-to-cart', form.querySelector('[name="add-to-cart"]').value);

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.message = data.data.message;
                    this.status = 'success';
                    this.$dispatch('wc-cart-updated');
                } else {
                    throw new Error(data.data.message);
                }
            } catch (error) {
                this.message = error.message;
                this.status = 'error';
            } finally {
                this.loading = false;
                setTimeout(() => {
                    this.message = '';
                    this.status = '';
                }, 5000);
            }
        },

        async buyNow(event) {
            await this.addToCart(event);
            if (this.status === 'success') {
                window.location.href = wc_add_to_cart_params.cart_url;
            }
        },

        updateVariation() {
            // Trigger WooCommerce variation form update
            jQuery('.variations_form').trigger('check_variations');
            
            // Check if all variations are selected
            this.isValid = Object.values(this.variations).every(value => value !== '');
        }
    }));

    // Quick View Quantity Component
    Alpine.data('quickViewQuantity', () => ({
        quantity: 1,
        min: 1,
        max: 9999,
        step: 1,

        initQuantity() {
            const input = this.$el.querySelector('input[type="number"]');
            this.min = parseInt(input.min) || 1;
            this.max = parseInt(input.max) || 9999;
            this.step = parseInt(input.step) || 1;
            this.quantity = parseInt(input.value) || 1;
        },

        increase() {
            if (this.quantity < this.max) {
                this.quantity += this.step;
                this.validateQuantity();
            }
        },

        decrease() {
            if (this.quantity > this.min) {
                this.quantity -= this.step;
                this.validateQuantity();
            }
        },

        validateQuantity() {
            this.quantity = Math.min(Math.max(this.min, this.quantity), this.max);
        }
    }));

    // Quick View Suggested Products Component
    Alpine.data('quickViewSuggestedProducts', () => ({
        loading: false,
        error: false,
        errorMessage: '',
        products: [],
        module: '',

        async loadSuggestedProducts() {
            this.loading = true;
            this.error = false;
            this.errorMessage = '';
            this.module = this.$el.dataset.module;

            try {
                const response = await fetch(mesQuickView.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'mesmeric_commerce_get_suggested_products',
                        nonce: mesQuickView.nonce,
                        product_id: this.$el.dataset.productId,
                        module: this.module
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.products = data.data.products;
                } else {
                    throw new Error(data.data.message);
                }
            } catch (error) {
                this.error = true;
                this.errorMessage = error.message;
            } finally {
                this.loading = false;
            }
        },

        async addToCart(productId) {
            try {
                const response = await fetch(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();

                if (data.error) {
                    throw new Error(data.message);
                }

                this.$dispatch('wc-cart-updated');
            } catch (error) {
                console.error('Error adding product to cart:', error);
            }
        }
    }));
});
