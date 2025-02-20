<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\Performance;

use MesmericCommerce\Abstract\MC_AbstractModule;

/**
 * Class MC_PerformanceModule
 * Handles performance monitoring and optimization for WooCommerce
 */
class MC_PerformanceModule extends MC_AbstractModule {

    /**
     * @var string The module ID
     */
    protected string $module_id = 'performance';

    /**
     * @var array Performance metrics cache
     */
    private array $metrics_cache = [];

    /**
     * Initialize the module
     */
    public function init(): void {
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Add performance monitoring hooks
        add_action('woocommerce_before_shop_loop', [$this, 'start_page_timing']);
        add_action('wp_footer', [$this, 'end_page_timing']);

        // Monitor cart and checkout performance
        add_action('woocommerce_before_cart', [$this, 'start_cart_timing']);
        add_action('woocommerce_after_cart', [$this, 'end_cart_timing']);

        // Replace direct query filter with proper WordPress hooks
        add_filter('query_monitor_init', [$this, 'setup_query_monitoring']);

        // Schedule cleanup of old metrics
        if (!wp_next_scheduled('mc_cleanup_performance_metrics')) {
            wp_schedule_event(time(), 'daily', 'mc_cleanup_performance_metrics');
        }
        add_action('mc_cleanup_performance_metrics', [$this, 'cleanup_old_metrics']);
    }

    /**
     * Register REST API routes for the performance module
     */
    public function register_rest_routes(): void {
        register_rest_route('mesmeric-commerce/v1', '/performance/metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_performance_metrics'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Start timing page load
     */
    public function start_page_timing(): void {
        $this->metrics_cache['page_start'] = microtime(true);
    }

    /**
     * End timing and log page load time
     */
    public function end_page_timing(): void {
        if (!isset($this->metrics_cache['page_start'])) {
            return;
        }

        $duration = microtime(true) - $this->metrics_cache['page_start'];
        $this->log_metric('page_load_time', $duration);
    }

    /**
     * Start timing cart operations
     */
    public function start_cart_timing(): void {
        $this->metrics_cache['cart_start'] = microtime(true);
    }

    /**
     * End timing cart operations and log duration
     */
    public function end_cart_timing(): void {
        if (!isset($this->metrics_cache['cart_start'])) {
            return;
        }

        $duration = microtime(true) - $this->metrics_cache['cart_start'];
        $this->log_metric('cart_operation_time', $duration);
    }

    /**
     * Setup query monitoring with proper WordPress hooks
     */
    private function setup_query_monitoring(): void {
        add_filter('query_monitor_queries', [$this, 'monitor_queries'], 10, 2);
    }

    /**
     * Monitor database queries for performance issues
     *
     * @param array $queries Array of queries
     * @param array $data Query Monitor data
     * @return array Modified queries array
     */
    public function monitor_queries(array $queries, array $data): array {
        foreach ($queries as $query) {
            if ($query['time'] > 1.0) { // 1 second threshold
                $this->log_metric('slow_query', [
                    'query' => $query['sql'],
                    'duration' => $query['time'],
                    'caller' => $query['caller']
                ]);
            }
        }
        return $queries;
    }

    /**
     * Log a performance metric
     *
     * @param string $metric_name The name of the metric
     * @param mixed $value The metric value
     */
    private function log_metric(string $metric_name, mixed $value): void {
        $metrics = get_option('mc_performance_metrics', []);
        $metrics[] = [
            'timestamp' => time(),
            'metric' => $metric_name,
            'value' => $value
        ];

        // Keep only last 1000 metrics
        if (count($metrics) > 1000) {
            array_shift($metrics);
        }

        update_option('mc_performance_metrics', $metrics);
    }

    /**
     * Get performance metrics via REST API
     *
     * @return \WP_REST_Response
     */
    public function get_performance_metrics(): \WP_REST_Response {
        $metrics = get_option('mc_performance_metrics', []);
        return new \WP_REST_Response($metrics, 200);
    }

    /**
     * Clean up metrics older than 30 days
     */
    public function cleanup_old_metrics(): void {
        $metrics = get_option('mc_performance_metrics', []);
        $thirty_days_ago = time() - (30 * DAY_IN_SECONDS);

        $metrics = array_filter($metrics, function($metric) use ($thirty_days_ago) {
            return $metric['timestamp'] > $thirty_days_ago;
        });

        update_option('mc_performance_metrics', $metrics);
    }

    /**
     * Clean up when module is deactivated
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('mc_cleanup_performance_metrics');
        delete_option('mc_performance_metrics');
    }
}
