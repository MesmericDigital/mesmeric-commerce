<?php

namespace MesmericCommerce\WooCommerce\Services;

use MesmericCommerce\Includes\MC_Plugin;

/**
 * Shipping Handler class
 *
 * Handles shipping-related functionality for the Mesmeric Commerce plugin.
 *
 * @since 1.0.0
 */
class MC_ShippingHandler {
    /**
     * Plugin instance
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * Constructor
     *
     * @param MC_Plugin $plugin Plugin instance
     */
    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;

        // Initialize hooks
        add_filter('woocommerce_package_rates', [$this, 'modify_shipping_rates'], 10, 2);
        add_action('woocommerce_shipping_init', [$this, 'register_shipping_methods']);
        add_filter('woocommerce_shipping_methods', [$this, 'add_shipping_methods']);
    }

    /**
     * Initialize the service
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Additional initialization if needed
        add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
        add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_true');
    }

    /**
     * Modify shipping rates
     *
     * @param array $rates Shipping rates
     * @param array $package Shipping package
     * @return array Modified shipping rates
     */
    public function modify_shipping_rates($rates, $package) {
        // Custom logic for modifying shipping rates
        return $rates;
    }

    /**
     * Register shipping methods
     */
    public function register_shipping_methods(): void {
        // Register custom shipping methods
    }

    /**
     * Add shipping methods
     *
     * @param array $methods Shipping methods
     * @return array Modified shipping methods
     */
    public function add_shipping_methods($methods) {
        // Add custom shipping methods
        return $methods;
    }

    /**
     * Get available shipping methods
     *
     * @return array Available shipping methods
     */
    public function get_available_shipping_methods(): array {
        if (function_exists('WC') && WC()->shipping) {
            return WC()->shipping->get_shipping_methods();
        }

        return [];
    }

    /**
     * Get shipping zones
     *
     * @return array Shipping zones
     */
    public function get_shipping_zones(): array {
        $zones = [];
        $shipping_zones = \WC_Shipping_Zones::get_zones();

        foreach ($shipping_zones as $zone_id => $zone) {
            $zones[$zone_id] = [
                'id' => $zone_id,
                'name' => $zone['zone_name'],
                'locations' => $zone['zone_locations'],
                'methods' => $zone['shipping_methods'],
            ];
        }

        return $zones;
    }
}
