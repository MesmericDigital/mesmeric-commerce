<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Analytics;

use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_AnalyticsDataProvider
 * 
 * Handles data retrieval and processing for analytics
 */
class MC_AnalyticsDataProvider {
    protected MC_Plugin $plugin;
    protected array $date_range;

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->date_range = $this->calculate_date_range();
    }

    /**
     * Handle AJAX requests for analytics data
     */
    public function handle_ajax_request(): void {
        check_ajax_referer('mc_analytics_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $data = $this->get_report_data($report_type);

        wp_send_json_success($data);
    }

    /**
     * Get report data based on type
     */
    protected function get_report_data(string $report_type): array {
        return match($report_type) {
            'sales' => $this->get_sales_data(),
            'products' => $this->get_product_data(),
            'customers' => $this->get_customer_data(),
            default => [],
        };
    }

    /**
     * Get sales data for the current period
     */
    protected function get_sales_data(): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT 
                DATE(post_date) as date,
                COUNT(*) as order_count,
                SUM(meta_value) as total_sales
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_order_total'
            AND p.post_date >= %s
            AND p.post_date <= %s
            GROUP BY DATE(post_date)
            ORDER BY date ASC",
            $this->date_range['start'],
            $this->date_range['end']
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get product performance data
     */
    protected function get_product_data(): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT 
                p.ID as product_id,
                p.post_title as product_name,
                COUNT(DISTINCT o.ID) as order_count,
                SUM(oim.meta_value) as quantity_sold
            FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_name = p.post_title
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->posts} o ON oi.order_id = o.ID
            WHERE p.post_type = 'product'
            AND o.post_type = 'shop_order'
            AND o.post_status IN ('wc-completed', 'wc-processing')
            AND oim.meta_key = '_qty'
            AND o.post_date >= %s
            AND o.post_date <= %s
            GROUP BY p.ID
            ORDER BY quantity_sold DESC
            LIMIT 10",
            $this->date_range['start'],
            $this->date_range['end']
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get customer analytics data
     */
    protected function get_customer_data(): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT 
                c.customer_id,
                c.first_name,
                c.last_name,
                COUNT(DISTINCT o.ID) as order_count,
                SUM(pm.meta_value) as total_spent
            FROM {$wpdb->prefix}wc_customer_lookup c
            JOIN {$wpdb->posts} o ON c.customer_id = o.post_author
            JOIN {$wpdb->postmeta} pm ON o.ID = pm.post_id
            WHERE o.post_type = 'shop_order'
            AND o.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_order_total'
            AND o.post_date >= %s
            AND o.post_date <= %s
            GROUP BY c.customer_id
            ORDER BY total_spent DESC
            LIMIT 10",
            $this->date_range['start'],
            $this->date_range['end']
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Calculate date range for reports
     */
    protected function calculate_date_range(): array {
        $end = current_time('Y-m-d H:i:s');
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get current date range
     */
    public function get_date_range(): array {
        return $this->date_range;
    }
}
