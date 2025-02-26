/**
 * Stock Scarcity Alpine.js Component
 */
export default () => ({
    isVisible: false,
    progressValue: 0,

    init() {
        // Show with a slight delay for smooth animation
        setTimeout(() => {
            this.isVisible = true;
        }, 150);
    },

    initProgressBar(percentage) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            // Animate progress bar when visible
                            this.$el.style.transition = 'width 1s ease-out';
                            this.$el.style.width = `${percentage}%`;
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
        } else {
            // Fallback for browsers without IntersectionObserver
            this.$el.style.width = `${percentage}%`;
        }
    }
});
