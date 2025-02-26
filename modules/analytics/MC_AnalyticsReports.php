<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Analytics;

use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_AnalyticsReports
 * 
 * Handles generation of analytics reports
 */
class MC_AnalyticsReports {
    protected MC_Plugin $plugin;
    protected MC_AnalyticsDataProvider $data_provider;

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->data_provider = new MC_AnalyticsDataProvider($plugin);
    }

    /**
     * Get overview data for dashboard
     */
    public function get_overview_data(): array {
        return [
            'sales' => $this->get_sales_overview(),
            'products' => $this->get_product_overview(),
            'customers' => $this->get_customer_overview(),
        ];
    }

    /**
     * Get sales overview data
     */
    protected function get_sales_overview(): array {
        $sales_data = $this->data_provider->get_report_data('sales');
        
        $total_sales = array_sum(array_column($sales_data, 'total_sales'));
        $total_orders = array_sum(array_column($sales_data, 'order_count'));
        
        $prev_period_sales = $this->get_previous_period_sales();
        $sales_growth = $this->calculate_growth($total_sales, $prev_period_sales);

        return [
            'total_sales' => $total_sales,
            'total_orders' => $total_orders,
            'average_order_value' => $total_orders ? ($total_sales / $total_orders) : 0,
            'sales_growth' => $sales_growth,
            'daily_data' => $this->format_daily_data($sales_data),
        ];
    }

    /**
     * Get product overview data
     */
    protected function get_product_overview(): array {
        $product_data = $this->data_provider->get_report_data('products');
        
        return [
            'top_products' => array_slice($product_data, 0, 5),
            'total_products_sold' => array_sum(array_column($product_data, 'quantity_sold')),
        ];
    }

    /**
     * Get customer overview data
     */
    protected function get_customer_overview(): array {
        $customer_data = $this->data_provider->get_report_data('customers');
        
        return [
            'top_customers' => array_slice($customer_data, 0, 5),
            'total_customers' => count($customer_data),
            'average_customer_value' => $this->calculate_average_customer_value($customer_data),
        ];
    }

    /**
     * Get sales from previous period for comparison
     */
    protected function get_previous_period_sales(): float {
        global $wpdb;

        $date_range = $this->data_provider->get_date_range();
        $days = 30; // Matches the date range in data provider

        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days", strtotime($date_range['start'])));
        $end_date = $date_range['start'];

        $query = $wpdb->prepare(
            "SELECT SUM(meta_value) as total_sales
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_order_total'
            AND p.post_date >= %s
            AND p.post_date < %s",
            $start_date,
            $end_date
        );

        return (float) $wpdb->get_var($query) ?: 0;
    }

    /**
     * Calculate growth percentage
     */
    protected function calculate_growth(float $current, float $previous): float {
        if (!$previous) {
            return 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Format daily data for charts
     */
    protected function format_daily_data(array $data): array {
        $formatted = [];
        foreach ($data as $day) {
            $formatted[] = [
                'date' => $day['date'],
                'sales' => (float) $day['total_sales'],
                'orders' => (int) $day['order_count'],
            ];
        }

        return $formatted;
    }

    /**
     * Calculate average customer value
     */
    protected function calculate_average_customer_value(array $customer_data): float {
        if (empty($customer_data)) {
            return 0;
        }

        $total_spent = array_sum(array_column($customer_data, 'total_spent'));
        return $total_spent / count($customer_data);
    }
}
