/**
 * Quick Social Links Alpine.js Component
 */
export default () => ({
    isOpen: false,
    isMobile: false,
    settings: {},

    /**
     * Initialize the component
     * @param {Object} settings
     */
    init(settings) {
        this.settings = settings;
        this.checkMobile();

        // Update mobile check on resize
        window.addEventListener('resize', () => {
            this.checkMobile();
        });

        // Close links when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.$el.contains(e.target)) {
                this.isOpen = false;
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.isOpen = false;
            }
        });
    },

    /**
     * Check if we're on mobile
     */
    checkMobile() {
        this.isMobile = window.innerWidth < 768;
        if (!this.isMobile) {
            this.isOpen = false;
        }
    },

    /**
     * Toggle links visibility on mobile
     */
    toggleLinks() {
        this.isOpen = !this.isOpen;
    },
});
