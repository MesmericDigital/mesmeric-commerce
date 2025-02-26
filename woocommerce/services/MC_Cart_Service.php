<?php

namespace MesmericCommerce\WooCommerce\Services;

/**
 * Cart Service class
 *
 * Handles cart-related functionality for the Mesmeric Commerce plugin.
 *
 * @since 1.0.0
 */
class MC_Cart_Service {
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks
        add_action('woocommerce_add_to_cart', [$this, 'handle_add_to_cart'], 10, 6);
        add_action('woocommerce_cart_item_removed', [$this, 'handle_cart_item_removed'], 10, 2);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_cart_item_data'], 10, 3);
    }

    /**
     * Initialize the service
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Additional initialization if needed
        add_filter('woocommerce_cart_contents_count', [$this, 'filter_cart_contents_count'], 10, 1);
        add_filter('woocommerce_cart_contents_changed', [$this, 'handle_cart_contents_changed'], 10, 1);
    }

    /**
     * Filter cart contents count
     *
     * @param int $count Cart contents count
     * @return int Modified count
     */
    public function filter_cart_contents_count(int $count): int {
        // Custom logic for modifying cart count if needed
        return $count;
    }

    /**
     * Handle cart contents changed
     *
     * @param array $cart_contents Cart contents
     * @return array Modified cart contents
     */
    public function handle_cart_contents_changed(array $cart_contents): array {
        // Custom logic for handling cart contents changes
        return $cart_contents;
    }

    /**
     * Handle add to cart action
     *
     * @param string $cart_item_key Cart item key
     * @param int    $product_id    Product ID
     * @param int    $quantity      Quantity
     * @param int    $variation_id  Variation ID
     * @param array  $variation     Variation data
     * @param array  $cart_item_data Cart item data
     */
    public function handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data): void {
        // Custom logic for handling add to cart
    }

    /**
     * Handle cart item removed action
     *
     * @param string $cart_item_key Cart item key
     * @param object $cart          WC_Cart instance
     */
    public function handle_cart_item_removed($cart_item_key, $cart): void {
        // Custom logic for handling cart item removal
    }

    /**
     * Add custom cart item data
     *
     * @param array $cart_item_data Cart item data
     * @param int   $product_id     Product ID
     * @param int   $variation_id   Variation ID
     * @return array Modified cart item data
     */
    public function add_custom_cart_item_data($cart_item_data, $product_id, $variation_id): array {
        // Add custom data to cart items if needed
        return $cart_item_data;
    }

    /**
     * Get cart contents
     *
     * @return array Cart contents
     */
    public function get_cart_contents(): array {
        if (function_exists('WC') && WC()->cart) {
            return WC()->cart->get_cart();
        }

        return [];
    }

    /**
     * Get cart total
     *
     * @return float Cart total
     */
    public function get_cart_total(): float {
        if (function_exists('WC') && WC()->cart) {
            return (float) WC()->cart->get_cart_contents_total();
        }

        return 0.0;
    }
}
