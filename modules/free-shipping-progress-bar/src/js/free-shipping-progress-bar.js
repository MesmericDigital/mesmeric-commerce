/**
 * Free Shipping Progress Bar Alpine.js Component
 */
export default () => ({
    isVisible: true,

    /**
     * Initialize the component
     */
    init() {
        // Check if user has dismissed the bar
        const isDismissed = localStorage.getItem('mesmeric_fspb_dismissed');
        if (isDismissed) {
            const dismissedTime = parseInt(isDismissed, 10);
            const now = Date.now();
            
            // Show again after 24 hours
            if (now - dismissedTime < 24 * 60 * 60 * 1000) {
                this.isVisible = false;
            } else {
                localStorage.removeItem('mesmeric_fspb_dismissed');
            }
        }

        // Listen for cart updates
        document.addEventListener('wc_fragments_refreshed', () => {
            this.refreshProgressBar();
        });

        document.addEventListener('wc_fragments_loaded', () => {
            this.refreshProgressBar();
        });

        // Listen for quantity changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.qty')) {
                this.refreshProgressBar();
            }
        });
    },

    /**
     * Close the progress bar
     */
    close() {
        this.isVisible = false;
        localStorage.setItem('mesmeric_fspb_dismissed', Date.now().toString());
    },

    /**
     * Refresh progress bar via AJAX
     */
    async refreshProgressBar() {
        try {
            const response = await fetch(window.mesmericFreeShippingProgressBar.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'refresh_free_shipping_progress_bar',
                    nonce: window.mesmericFreeShippingProgressBar.nonce,
                }),
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const html = await response.text();
            
            // Replace the current progress bar with the new one
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            const newBar = temp.querySelector('.mesmeric-fspb-card');
            if (newBar) {
                this.$el.innerHTML = newBar.innerHTML;
            }
        } catch (error) {
            console.error('Error refreshing progress bar:', error);
        }
    },
});
