<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Analytics;

use MesmericCommerce\Includes\MC_Plugin;
use WC_Order;

/**
 * Class MC_AnalyticsLogger
 * 
 * Handles logging of analytics events
 */
class MC_AnalyticsLogger {
    protected MC_Plugin $plugin;
    protected const LOG_TABLE = 'mc_analytics_events';

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Log order status change
     */
    public function log_order_status_change(int $order_id, string $old_status, string $new_status, WC_Order $order): void {
        $this->log_event('order_status_change', [
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'total' => $order->get_total(),
        ]);
    }

    /**
     * Log new order
     */
    public function log_new_order(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->log_event('new_order', [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'items' => count($order->get_items()),
            'customer_id' => $order->get_customer_id(),
        ]);
    }

    /**
     * Log analytics event
     */
    protected function log_event(string $event_type, array $data): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::LOG_TABLE;
        
        $wpdb->insert(
            $table_name,
            [
                'event_type' => $event_type,
                'event_data' => json_encode($data),
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($wpdb->last_error) {
            $this->plugin->get_logger()->log_error(
                sprintf('Failed to log analytics event: %s', $wpdb->last_error),
                'error'
            );
        }
    }

    /**
     * Create analytics events table if it doesn't exist
     */
    public static function create_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::LOG_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
