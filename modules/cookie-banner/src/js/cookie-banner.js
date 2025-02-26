/**
 * Cookie Banner Alpine.js Component
 * 
 * @typedef {Object} CookieBannerConfig
 * @property {number} cookieDuration - Duration in days to remember the user's choice
 * @property {Object} translations - Translated strings
 * @property {string} translations.accept - Accept button text
 * @property {string} translations.learnMore - Learn more link text
 */

/**
 * Cookie Banner component
 * 
 * @returns {Object} Alpine.js component definition
 */
export default () => ({
    isVisible: false,

    /**
     * Initialize the component
     */
    init() {
        // Check if user has already accepted cookies
        if (!this.hasAcceptedCookies()) {
            // Get delay from data attribute
            const delay = parseInt(this.$el.dataset.delay || '1000', 10);

            // Show banner after delay
            setTimeout(() => {
                this.isVisible = true;
                this.logAnalytics('shown');
            }, delay);
        }

        // Handle color scheme changes
        this.handleColorScheme();

        // Handle escape key
        this.$watch('isVisible', (value) => {
            if (value) {
                document.addEventListener('keydown', this.handleEscape);
            } else {
                document.removeEventListener('keydown', this.handleEscape);
            }
        });
    },

    /**
     * Handle escape key press
     * 
     * @param {KeyboardEvent} event - Keyboard event
     */
    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    },

    /**
     * Handle color scheme changes
     */
    handleColorScheme() {
        const colorScheme = this.$el.dataset.colorScheme;
        
        if (colorScheme === 'auto') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Initial check
            this.updateColorScheme(mediaQuery.matches);
            
            // Listen for changes
            mediaQuery.addEventListener('change', (e) => {
                this.updateColorScheme(e.matches);
            });
        }
    },

    /**
     * Update color scheme based on system preference
     * 
     * @param {boolean} isDark - Whether dark mode is preferred
     */
    updateColorScheme(isDark) {
        this.$el.dataset.theme = isDark ? 'dark' : 'light';
    },

    /**
     * Accept cookies
     */
    accept() {
        this.setCookie('mesmeric_cookie_consent', 'accepted', window.mesmericCookieBanner.cookieDuration);
        this.isVisible = false;
        this.logAnalytics('accepted');

        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('mesmericCookieConsent', {
            detail: { accepted: true }
        }));
    },

    /**
     * Close the banner without accepting
     */
    close() {
        this.isVisible = false;
        this.logAnalytics('closed');
    },

    /**
     * Check if user has accepted cookies
     * 
     * @returns {boolean} Whether user has accepted cookies
     */
    hasAcceptedCookies() {
        return this.getCookie('mesmeric_cookie_consent') === 'accepted';
    },

    /**
     * Set a cookie
     * 
     * @param {string} name - Cookie name
     * @param {string} value - Cookie value
     * @param {number} days - Days until expiration
     */
    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    },

    /**
     * Get a cookie value
     * 
     * @param {string} name - Cookie name
     * @returns {string|null} Cookie value or null if not found
     */
    getCookie(name) {
        const match = document.cookie.match(new RegExp(`(^| )${name}=([^;]+)`));
        return match ? match[2] : null;
    },

    /**
     * Log analytics event
     * 
     * @param {string} action - Action name (shown, accepted, closed)
     */
    logAnalytics(action) {
        if (typeof window.mesmericAnalytics?.logEvent === 'function') {
            window.mesmericAnalytics.logEvent('cookie_banner', {
                action,
                theme: this.$el.classList.contains('theme-floating') ? 'floating' : 'fixed',
                colorScheme: this.$el.dataset.colorScheme,
            });
        }
    },
});
