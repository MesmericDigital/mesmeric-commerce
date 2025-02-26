/**
 * Cart Reserved Timer Alpine.js Component
 */
export default () => ({
    isVisible: true,
    timeRemaining: 0,
    progress: 100,
    timerInterval: null,
    settings: window.mesmericCartReservedTimer || {},

    /**
     * Initialize the component
     */
    init() {
        this.timeRemaining = this.settings.duration;
        this.startTimer();

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseTimer();
            } else {
                this.resumeTimer();
            }
        });
    },

    /**
     * Start the countdown timer
     */
    startTimer() {
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            this.progress = (this.timeRemaining / this.settings.duration) * 100;

            if (this.timeRemaining <= 0) {
                this.handleExpiration();
            }
        }, 1000);
    },

    /**
     * Pause the timer when page is hidden
     */
    pauseTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    },

    /**
     * Resume the timer when page becomes visible
     */
    resumeTimer() {
        this.startTimer();
    },

    /**
     * Handle timer expiration
     */
    async handleExpiration() {
        this.pauseTimer();

        if (this.settings.timeExpires === 'clear-cart') {
            try {
                const response = await fetch(this.settings.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'clear_cart',
                        nonce: this.settings.nonce,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error clearing cart:', error);
            }
        }

        this.isVisible = false;
    },

    /**
     * Format time remaining into minutes and seconds
     */
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    },

    /**
     * Get the appropriate timer message based on remaining time
     */
    get timerMessage() {
        const minutes = Math.floor(this.timeRemaining / 60);
        const formattedTime = this.formatTime(this.timeRemaining);

        if (minutes > 0) {
            return this.settings.timer_message_minutes.replace(
                '{timer}',
                `<span class="font-mono font-medium">${formattedTime}</span>`
            );
        }

        return this.settings.timer_message_seconds.replace(
            '{timer}',
            `<span class="font-mono font-medium">${formattedTime}</span>`
        );
    }
});
