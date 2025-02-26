/**
 * Real-Time Search Alpine.js Component
 */
export default () => ({
    query: '',
    results: [],
    isLoading: false,
    showResults: false,
    hasMore: false,
    highlightedIndex: -1,
    settings: {},

    /**
     * Initialize the component
     * @param {Object} settings
     */
    init(settings) {
        this.settings = settings;

        // Close results on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeResults();
            }
        });
    },

    /**
     * Handle input changes
     */
    async handleInput() {
        if (!this.query) {
            this.results = [];
            this.showResults = false;
            return;
        }

        this.isLoading = true;
        this.showResults = true;
        this.highlightedIndex = -1;

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'real_time_search',
                    nonce: this.settings.nonce,
                    query: this.query,
                }),
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data.message || 'Search failed');
            }

            this.results = data.data.results;
            this.hasMore = data.data.hasMore;
        } catch (error) {
            console.error('Search error:', error);
            this.results = [];
            this.hasMore = false;
        } finally {
            this.isLoading = false;
        }
    },

    /**
     * Handle form submission
     */
    handleSubmit() {
        if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
            window.location.href = this.results[this.highlightedIndex].url;
        } else {
            window.location.href = this.getSearchUrl();
        }
    },

    /**
     * Select a search result
     * @param {Object} result
     */
    selectResult(result) {
        window.location.href = result.url;
    },

    /**
     * Select the highlighted result
     */
    selectHighlighted() {
        if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
            this.selectResult(this.results[this.highlightedIndex]);
        }
    },

    /**
     * Highlight the next result
     */
    highlightNext() {
        if (!this.showResults || !this.results.length) {
            return;
        }

        this.highlightedIndex = this.highlightedIndex < this.results.length - 1
            ? this.highlightedIndex + 1
            : 0;

        this.scrollHighlightedIntoView();
    },

    /**
     * Highlight the previous result
     */
    highlightPrev() {
        if (!this.showResults || !this.results.length) {
            return;
        }

        this.highlightedIndex = this.highlightedIndex > 0
            ? this.highlightedIndex - 1
            : this.results.length - 1;

        this.scrollHighlightedIntoView();
    },

    /**
     * Scroll the highlighted result into view
     */
    scrollHighlightedIntoView() {
        this.$nextTick(() => {
            const highlighted = this.$el.querySelector('.menu li:nth-child(' + (this.highlightedIndex + 1) + ')');
            if (highlighted) {
                highlighted.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth',
                });
            }
        });
    },

    /**
     * Close the results dropdown
     */
    closeResults() {
        this.showResults = false;
        this.highlightedIndex = -1;
    },

    /**
     * Get the URL for the full search results page
     * @returns {string}
     */
    getSearchUrl() {
        const shopUrl = document.querySelector('[name="s"]').form.action;
        return `${shopUrl}?s=${encodeURIComponent(this.query)}&post_type=product`;
    },
});
