/**
 * Reasons to Buy Alpine.js Component
 */
export default () => ({
    isVisible: false,

    init() {
        // Show with a slight delay for smooth animation
        setTimeout(() => {
            this.isVisible = true;
        }, 150);

        // Optional: Add intersection observer for animation on scroll
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.isVisible = true;
                            observer.unobserve(entry.target);
                        }
                    });
                },
                {
                    threshold: 0.1,
                    rootMargin: '50px',
                }
            );

            observer.observe(this.$el);
        }
    }
});
