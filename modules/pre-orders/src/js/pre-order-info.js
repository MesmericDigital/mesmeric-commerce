/**
 * Pre-order information Alpine.js component
 */
export default () => ({
    shippingDate: 0,
    timeRemaining: 0,
    showCountdown: false,
    countdownInterval: null,

    /**
     * Initialize the component
     * @param {number} shippingDate Shipping date timestamp
     */
    init(shippingDate) {
        this.shippingDate = shippingDate;
        this.updateTimeRemaining();

        // Update countdown every second
        this.countdownInterval = setInterval(() => {
            this.updateTimeRemaining();
        }, 1000);

        // Cleanup on component destroy
        this.$cleanup(() => {
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
            }
        });
    },

    /**
     * Update time remaining until shipping
     */
    updateTimeRemaining() {
        const now = Math.floor(Date.now() / 1000);
        this.timeRemaining = Math.max(0, this.shippingDate - now);
        this.showCountdown = this.timeRemaining > 0;

        // Stop countdown if time is up
        if (this.timeRemaining === 0 && this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    },

    /**
     * Format time remaining in days, hours, minutes, seconds
     */
    get formatTimeRemaining() {
        const days = Math.floor(this.timeRemaining / 86400);
        const hours = Math.floor((this.timeRemaining % 86400) / 3600);
        const minutes = Math.floor((this.timeRemaining % 3600) / 60);
        const seconds = this.timeRemaining % 60;

        const parts = [];

        if (days > 0) {
            parts.push(`${days}d`);
        }
        if (hours > 0 || days > 0) {
            parts.push(`${hours}h`);
        }
        if (minutes > 0 || hours > 0 || days > 0) {
            parts.push(`${minutes}m`);
        }
        parts.push(`${seconds}s`);

        return parts.join(' ');
    }
});
