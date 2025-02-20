<?php
/**
 * Reorder Calculator
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/inventory
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Inventory;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_ReorderCalculator
 *
 * Calculates reorder points and optimal stock levels
 */
class MC_ReorderCalculator {
    /**
     * The plugin's instance.
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * The logger instance.
     *
     * @var MC_Logger
     */
    private MC_Logger $logger;

    /**
     * Initialize the calculator.
     *
     * @param MC_Plugin $plugin The plugin instance.
     */
    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->logger = $plugin->get_logger();
    }

    /**
     * Check if a product should be reordered.
     *
     * @param \WC_Product $product The product.
     * @return bool
     */
    public function should_reorder(\WC_Product $product): bool {
        try {
            if (!$product->managing_stock()) {
                return false;
            }

            $stock_quantity = $product->get_stock_quantity();
            if ($stock_quantity === null) {
                return false;
            }

            $reorder_point = $this->calculate_reorder_point($product);
            return $stock_quantity <= $reorder_point;
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf(
                    'Error checking reorder status for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                ),
                'error',
                true
            );
            return false;
        }
    }

    /**
     * Calculate reorder point for a product.
     *
     * @param \WC_Product $product The product.
     * @return int
     */
    public function calculate_reorder_point(\WC_Product $product): int {
        try {
            $product_id = $product->get_id();
            $sales_data = $this->get_sales_data($product_id);

            // Calculate average daily demand
            $daily_demand = $this->calculate_daily_demand($sales_data);

            // Get lead time in days
            $lead_time = $this->get_lead_time($product);

            // Calculate safety stock
            $safety_stock = $this->calculate_safety_stock($sales_data, $daily_demand);

            // Calculate reorder point
            $reorder_point = (int) ceil(($daily_demand * $lead_time) + $safety_stock);

            $this->logger->log_error(
                sprintf(
                    'Calculated reorder point for product #%d: %d (Daily Demand: %.2f, Lead Time: %d, Safety Stock: %d)',
                    $product_id,
                    $reorder_point,
                    $daily_demand,
                    $lead_time,
                    $safety_stock
                ),
                'info'
            );

            return $reorder_point;
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf(
                    'Error calculating reorder point for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                ),
                'error',
                true
            );
            return 0;
        }
    }

    /**
     * Get sales data for a product.
     *
     * @param int $product_id The product ID.
     * @return array<array{date: string, quantity: int}>
     */
    private function get_sales_data(int $product_id): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT
                p.post_date AS date,
                oim.meta_value AS quantity
            FROM {$wpdb->prefix}wc_order_product_lookup opl
            JOIN {$wpdb->posts} p ON p.ID = opl.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = opl.order_item_id
            WHERE opl.product_id = %d
            AND p.post_status = 'wc-completed'
            AND oim.meta_key = '_qty'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY p.post_date DESC",
            $product_id
        );

        $results = $wpdb->get_results($query, ARRAY_A);
        if (!is_array($results)) {
            return array();
        }

        return array_map(function ($row) {
            return array(
                'date' => $row['date'],
                'quantity' => (int) $row['quantity']
            );
        }, $results);
    }

    /**
     * Calculate average daily demand.
     *
     * @param array<array{date: string, quantity: int}> $sales_data Sales data.
     * @return float
     */
    private function calculate_daily_demand(array $sales_data): float {
        if (empty($sales_data)) {
            return 0.0;
        }

        $total_quantity = array_sum(array_column($sales_data, 'quantity'));
        $date_range = (strtotime($sales_data[0]['date']) - strtotime(end($sales_data)['date'])) / DAY_IN_SECONDS;
        $days = max(1, $date_range);

        return $total_quantity / $days;
    }

    /**
     * Get lead time for a product.
     *
     * @param \WC_Product $product The product.
     * @return int
     */
    private function get_lead_time(\WC_Product $product): int {
        $lead_time = (int) $product->get_meta('_mc_lead_time_days');
        if ($lead_time < 1) {
            $lead_time = (int) get_option('mc_default_lead_time', 7);
        }

        return max(1, $lead_time);
    }

    /**
     * Calculate safety stock.
     *
     * @param array<array{date: string, quantity: int}> $sales_data Sales data.
     * @param float                                     $avg_demand Average daily demand.
     * @return int
     */
    private function calculate_safety_stock(array $sales_data, float $avg_demand): int {
        if (empty($sales_data) || $avg_demand <= 0) {
            return 0;
        }

        // Calculate standard deviation of daily demand
        $daily_quantities = array();
        $current_date = null;
        $current_total = 0;

        foreach ($sales_data as $sale) {
            $sale_date = date('Y-m-d', strtotime($sale['date']));
            if ($current_date === null) {
                $current_date = $sale_date;
            }

            if ($sale_date === $current_date) {
                $current_total += $sale['quantity'];
            } else {
                $daily_quantities[] = $current_total;
                $current_total = $sale['quantity'];
                $current_date = $sale_date;
            }
        }
        $daily_quantities[] = $current_total;

        $variance = 0;
        foreach ($daily_quantities as $quantity) {
            $variance += pow($quantity - $avg_demand, 2);
        }
        $std_dev = sqrt($variance / count($daily_quantities));

        // Use service level factor (z-score) of 1.96 for 95% service level
        $service_level_factor = 1.96;

        return (int) ceil($service_level_factor * $std_dev);
    }
}
