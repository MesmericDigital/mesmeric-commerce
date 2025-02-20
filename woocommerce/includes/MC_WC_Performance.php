<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Includes;

use MesmericCommerce\WooCommerce\Traits\MC_WC_Caching;

/**
 * WooCommerce Performance Optimization Class
 *
 * Handles various performance optimizations for WooCommerce:
 * - Query optimization
 * - Cache management
 * - Asset optimization
 * - Database optimization
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/includes
 * @since      1.0.0
 */
class MC_WC_Performance {
    use MC_WC_Caching;

    /**
     * Performance settings
     *
     * @since 1.0.0
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->settings = [
            'enable_query_optimization' => true,
            'enable_cart_fragments_cache' => true,
            'enable_product_cache' => true,
            'enable_template_cache' => true,
            'minify_assets' => true,
            'lazy_load_images' => true,
            'optimize_db_queries' => true,
        ];
    }

    /**
     * Initialize performance optimizations.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Query optimization
        if ($this->settings['enable_query_optimization']) {
            $this->init_query_optimization();
        }

        // Cart fragments optimization
        if ($this->settings['enable_cart_fragments_cache']) {
            $this->init_cart_fragments_optimization();
        }

        // Product data caching
        if ($this->settings['enable_product_cache']) {
            $this->init_product_caching();
        }

        // Asset optimization
        if ($this->settings['minify_assets']) {
            $this->init_asset_optimization();
        }

        // Database optimization
        if ($this->settings['optimize_db_queries']) {
            $this->init_db_optimization();
        }
    }

    /**
     * Initialize query optimization.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_query_optimization(): void {
        // Optimize product queries
        add_filter('woocommerce_product_query_tax_query', [$this, 'optimize_tax_query'], 10, 1);
        add_filter('woocommerce_product_query_meta_query', [$this, 'optimize_meta_query'], 10, 1);

        // Optimize term queries
        add_filter('woocommerce_get_product_terms_args', [$this, 'optimize_term_query'], 10, 2);
    }

    /**
     * Initialize cart fragments optimization.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_cart_fragments_optimization(): void {
        // Disable cart fragments on non-cart/checkout pages
        add_action('wp_enqueue_scripts', [$this, 'manage_cart_fragments'], 99);
    }

    /**
     * Initialize product data caching.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_product_caching(): void {
        // Cache product data
        add_filter('woocommerce_get_product_from_the_post', [$this, 'cache_product_data'], 10, 1);
        add_action('woocommerce_update_product', [$this, 'clear_product_cache'], 10, 1);
    }

    /**
     * Initialize asset optimization.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_asset_optimization(): void {
        // Optimize WooCommerce scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'optimize_assets'], 99);
    }

    /**
     * Initialize database optimization.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_db_optimization(): void {
        // Add database indexes
        add_action('woocommerce_installed', [$this, 'add_custom_indexes']);

        // Clean expired sessions and transients
        add_action('wp_scheduled_delete', [$this, 'cleanup_database']);
    }

    /**
     * Optimize tax query for better performance.
     *
     * @since 1.0.0
     * @param array<string, mixed> $tax_query The tax query array
     * @return array<string, mixed>
     */
    public function optimize_tax_query(array $tax_query): array {
        // Implement tax query optimization logic
        return $tax_query;
    }

    /**
     * Optimize meta query for better performance.
     *
     * @since 1.0.0
     * @param array<string, mixed> $meta_query The meta query array
     * @return array<string, mixed>
     */
    public function optimize_meta_query(array $meta_query): array {
        // Implement meta query optimization logic
        return $meta_query;
    }

    /**
     * Manage cart fragments loading.
     *
     * @since 1.0.0
     * @return void
     */
    public function manage_cart_fragments(): void {
        if (!is_cart() && !is_checkout()) {
            wp_dequeue_script('wc-cart-fragments');
        }
    }

    /**
     * Optimize WooCommerce assets.
     *
     * @since 1.0.0
     * @return void
     */
    public function optimize_assets(): void {
        // Implement asset optimization logic
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            wp_dequeue_style('woocommerce-general');
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-smallscreen');
        }
    }

    /**
     * Add custom database indexes for better performance.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_custom_indexes(): void {
        global $wpdb;

        // Add custom indexes to improve query performance
        $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX mc_product_meta (meta_key(32),meta_value(32))");
    }

    /**
     * Clean up expired sessions and transients.
     *
     * @since 1.0.0
     * @return void
     */
    public function cleanup_database(): void {
        global $wpdb;

        // Clean expired sessions
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}woocommerce_sessions
            WHERE session_expiry < " . time()
        );

        // Clean expired transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_mc_%'
            AND option_value < " . time()
        );
    }

    /**
     * Render the admin performance page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_admin_page(): void {
        // Implementation will be in a separate template file
        include MC_PLUGIN_DIR . 'woocommerce/admin/views/performance-page.php';
    }

    /**
     * Display performance-related admin notices.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_notices(): void {
        $this->check_performance_issues();
    }

    /**
     * Check for performance issues and display notices.
     *
     * @since 1.0.0
     * @return void
     */
    private function check_performance_issues(): void {
        // Check for common performance issues
        $issues = $this->get_performance_issues();

        foreach ($issues as $issue) {
            add_action('admin_notices', function() use ($issue) {
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    esc_html($issue)
                );
            });
        }
    }

    /**
     * Get list of performance issues.
     *
     * @since 1.0.0
     * @return array<string>
     */
    private function get_performance_issues(): array {
        $issues = [];

        // Check for large postmeta table
        global $wpdb;
        $postmeta_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}");
        if ($postmeta_count > 1000000) {
            $issues[] = __('Your postmeta table is very large. Consider cleaning up old or unnecessary metadata.', 'mesmeric-commerce');
        }

        // Check for missing indexes
        $missing_indexes = $this->check_missing_indexes();
        if (!empty($missing_indexes)) {
            $issues[] = __('Some database indexes are missing which could improve performance.', 'mesmeric-commerce');
        }

        return $issues;
    }

    /**
     * Check for missing database indexes.
     *
     * @since 1.0.0
     * @return array<string>
     */
    private function check_missing_indexes(): array {
        global $wpdb;
        $missing = [];

        // Check for specific indexes
        $result = $wpdb->get_results("SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'mc_product_meta'");
        if (empty($result)) {
            $missing[] = 'mc_product_meta';
        }

        return $missing;
    }
}
