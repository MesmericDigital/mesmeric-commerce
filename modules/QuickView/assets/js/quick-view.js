/**
 * Quick View Module JavaScript
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/quickview/assets/js
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('mcQuickView', () => ({
        open: false,
        loading: false,
        content: '',
        productId: null,

        async init() {
            // Listen for quick view button clicks
            document.addEventListener('click', (e) => {
                const quickViewBtn = e.target.closest('.mc-quick-view-button');
                if (quickViewBtn) {
                    e.preventDefault();
                    this.showQuickView(quickViewBtn.dataset.productId);
                }
            });

            // Listen for notification events
            window.addEventListener('show-notification', (e) => {
                this.showNotification(e.detail.message, e.detail.type);
            });
        },

        async showQuickView(productId) {
            if (this.loading || !productId) return;

            this.loading = true;
            this.productId = productId;

            try {
                const response = await fetch(mcQuickView.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mc_load_quick_view',
                        nonce: mcQuickView.nonce,
                        product_id: productId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.content = data.html;
                    this.open = true;

                    // Trigger a custom event for other scripts
                    window.dispatchEvent(new CustomEvent('mc-quick-view-opened', {
                        detail: { productId }
                    }));
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showNotification(error.message || mcQuickView.i18n.error, 'error');
            } finally {
                this.loading = false;
            }
        },

        showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `mc-notification mc-notification-${type} fixed bottom-4 right-4 p-4 rounded-lg shadow-lg text-white transform translate-y-full transition-transform duration-300 z-50`;
            notification.style.backgroundColor = type === 'success' ? '#10B981' : '#EF4444';
            notification.textContent = message;

            // Add to document
            document.body.appendChild(notification);

            // Animate in
            requestAnimationFrame(() => {
                notification.style.transform = 'translateY(0)';
            });

            // Remove after delay
            setTimeout(() => {
                notification.style.transform = 'translateY(full)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        },

        close() {
            this.open = false;
            this.content = '';
            this.productId = null;

            // Trigger a custom event for other scripts
            window.dispatchEvent(new CustomEvent('mc-quick-view-closed'));
        }
    }));
});

// Add quick view buttons to products
document.addEventListener('DOMContentLoaded', () => {
    const products = document.querySelectorAll('.product');
    products.forEach(product => {
        if (!product.querySelector('.mc-quick-view-button')) {
            const productId = product.className.match(/post-(\d+)/)?.[1];
            if (productId) {
                const quickViewBtn = document.createElement('button');
                quickViewBtn.className = 'mc-quick-view-button btn btn-sm btn-ghost absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200';
                quickViewBtn.dataset.productId = productId;
                quickViewBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                `;
                product.appendChild(quickViewBtn);
            }
        }
    });
});
