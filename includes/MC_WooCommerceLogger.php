<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * WooCommerce Event Logger
 */
class MC_WooCommerceLogger {
    /**
     * Logger instance
     */
    private MC_Logger $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = MC_Logger::get_instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Order status changes
        add_action('woocommerce_order_status_changed', [$this, 'log_order_status_change'], 10, 4);

        // New order
        add_action('woocommerce_new_order', [$this, 'log_new_order']);

        // Order updates
        add_action('woocommerce_update_order', [$this, 'log_order_update']);

        // Product stock changes
        add_action('woocommerce_product_set_stock', [$this, 'log_stock_change']);
        add_action('woocommerce_variation_set_stock', [$this, 'log_stock_change']);

        // Shipping updates
        add_action('woocommerce_shipping_zone_method_added', [$this, 'log_shipping_method_change'], 10, 3);
        add_action('woocommerce_shipping_zone_method_deleted', [$this, 'log_shipping_method_change'], 10, 3);
        add_action('woocommerce_shipping_zone_method_status_toggled', [$this, 'log_shipping_method_status_change'], 10, 4);

        // User activity
        add_action('woocommerce_created_customer', [$this, 'log_customer_creation'], 10, 3);
        add_action('woocommerce_updated_customer', [$this, 'log_customer_update']);
    }

    /**
     * Log order status changes
     */
    public function log_order_status_change(int $order_id, string $old_status, string $new_status, \WC_Order $order): void {
        $this->logger->log_order_event($order, 'status_change', [
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]);
    }

    /**
     * Log new orders
     */
    public function log_new_order(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->logger->log_order_event($order, 'created');
    }

    /**
     * Log order updates
     */
    public function log_order_update(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->logger->log_order_event($order, 'updated');
    }

    /**
     * Log stock changes
     */
    public function log_stock_change(\WC_Product $product): void {
        $old_stock = $product->get_stock_quantity();
        $new_stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

        if ($new_stock === false || $new_stock === null || $old_stock === $new_stock) {
            return;
        }

        $this->logger->log_inventory_change(
            $product,
            (int) $old_stock,
            $new_stock,
            'manual_update'
        );
    }

    /**
     * Log shipping method changes
     */
    public function log_shipping_method_change(int $instance_id, string $type, int $zone_id): void {
        $zone = \WC_Shipping_Zones::get_zone($zone_id);
        if (!$zone) {
            return;
        }

        $this->logger->log_system_event(
            'shipping_method_change',
            sprintf(
                'Shipping method %s (ID: %d) modified in zone %s',
                $type,
                $instance_id,
                $zone->get_zone_name()
            ),
            'info',
            [
                'instance_id' => $instance_id,
                'type' => $type,
                'zone_id' => $zone_id,
            ]
        );
    }

    /**
     * Log shipping method status changes
     */
    public function log_shipping_method_status_change(int $instance_id, bool $enabled, string $type, int $zone_id): void {
        $zone = \WC_Shipping_Zones::get_zone($zone_id);
        if (!$zone) {
            return;
        }

        $this->logger->log_system_event(
            'shipping_method_status_change',
            sprintf(
                'Shipping method %s %s in zone %s %s',
                $type,
                $instance_id,
                $zone->get_zone_name(),
                $enabled ? 'enabled' : 'disabled'
            ),
            'info',
            [
                'instance_id' => $instance_id,
                'type' => $type,
                'zone_id' => $zone_id,
                'enabled' => $enabled,
            ]
        );
    }

    /**
     * Log customer creation
     */
    public function log_customer_creation(int $customer_id, array $new_customer_data, bool $password_generated): void {
        $this->logger->log_user_activity($customer_id, 'customer_created', [
            'email' => $new_customer_data['user_email'] ?? '',
            'password_generated' => $password_generated,
        ]);
    }

    /**
     * Log customer updates
     */
    public function log_customer_update(int $customer_id): void {
        $this->logger->log_user_activity($customer_id, 'customer_updated');
    }
}
