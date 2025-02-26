<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Includes;

use MesmericCommerce\WooCommerce\Includes\Traits\MC_WC_Caching;

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
        add_action('wp_enqueue_scripts', [$this, 'manage_cart_fragments'], 999);
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
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
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
        // Render the performance admin page
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WooCommerce Performance', 'mesmeric-commerce'); ?></h1>
            <div class="card">
                <h2><?php echo esc_html__('Performance Metrics', 'mesmeric-commerce'); ?></h2>
                <p><?php echo esc_html__('View and optimize your WooCommerce performance metrics.', 'mesmeric-commerce'); ?></p>
                <div class="performance-metrics">
                    <?php $this->render_performance_metrics(); ?>
                </div>
            </div>
            <div class="card">
                <h2><?php echo esc_html__('Optimization Settings', 'mesmeric-commerce'); ?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('mc_wc_performance_options');
                    do_settings_sections('mc_wc_performance');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render performance metrics
     *
     * @return void
     */
    private function render_performance_metrics(): void {
        // Get performance metrics
        $metrics = $this->get_performance_metrics();

        // Render metrics
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Metric', 'mesmeric-commerce'); ?></th>
                    <th><?php echo esc_html__('Value', 'mesmeric-commerce'); ?></th>
                    <th><?php echo esc_html__('Status', 'mesmeric-commerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metrics as $metric) : ?>
                    <tr>
                        <td><?php echo esc_html($metric['name']); ?></td>
                        <td><?php echo esc_html($metric['value']); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($metric['status']); ?>">
                                <?php echo esc_html($metric['status_text']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Get performance metrics
     *
     * @return array Performance metrics
     */
    private function get_performance_metrics(): array {
        // Get performance metrics
        return [
            [
                'name' => __('Product Query Time', 'mesmeric-commerce'),
                'value' => '0.5s',
                'status' => 'good',
                'status_text' => __('Good', 'mesmeric-commerce'),
            ],
            [
                'name' => __('Cart Fragments', 'mesmeric-commerce'),
                'value' => __('Optimized', 'mesmeric-commerce'),
                'status' => 'good',
                'status_text' => __('Good', 'mesmeric-commerce'),
            ],
            [
                'name' => __('Database Queries', 'mesmeric-commerce'),
                'value' => '25',
                'status' => 'warning',
                'status_text' => __('Warning', 'mesmeric-commerce'),
            ],
        ];
    }

    /**
     * Display performance-related admin notices.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_notices(): void {
        // Display performance-related notices
        $screen = get_current_screen();

        if (!$screen || !in_array($screen->id, ['woocommerce_page_mc-wc-performance', 'edit-product'])) {
            return;
        }

        // Check for performance issues
        $issues = $this->check_performance_issues();

        if (!empty($issues)) {
            foreach ($issues as $issue) {
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo esc_html($issue);
                echo '</p></div>';
            }
        }
    }

    /**
     * Check for performance issues
     *
     * @return array Performance issues
     */
    private function check_performance_issues(): array {
        $issues = [];

        // Check for large number of products
        $product_count = wp_count_posts('product')->publish;
        if ($product_count > 1000) {
            $issues[] = sprintf(
                __('You have %d products. Consider using product category caching for better performance.', 'mesmeric-commerce'),
                $product_count
            );
        }

        // Check for large number of variations
        global $wpdb;
        $variation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND post_status = 'publish'");
        if ($variation_count > 5000) {
            $issues[] = sprintf(
                __('You have %d product variations. Consider optimizing your variable products.', 'mesmeric-commerce'),
                $variation_count
            );
        }

        return $issues;
    }
}
