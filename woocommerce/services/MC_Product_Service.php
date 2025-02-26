<?php

namespace MesmericCommerce\WooCommerce\Services;

/**
 * Product Service class
 *
 * Handles product-related functionality for the Mesmeric Commerce plugin.
 *
 * @since 1.0.0
 */
class MC_Product_Service {
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks
        add_filter('woocommerce_product_get_price', [$this, 'modify_product_price'], 10, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'modify_product_regular_price'], 10, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'modify_product_sale_price'], 10, 2);
    }

    /**
     * Initialize the service
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Additional initialization if needed
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tabs'], 10, 1);
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panels']);
    }

    /**
     * Add custom product data tabs
     *
     * @param array $tabs Product data tabs
     * @return array Modified tabs
     */
    public function add_product_data_tabs(array $tabs): array {
        // Add custom product data tabs if needed
        return $tabs;
    }

    /**
     * Add custom product data panels
     */
    public function add_product_data_panels(): void {
        // Add custom product data panels if needed
    }

    /**
     * Modify product price
     *
     * @param string     $price   Product price
     * @param \WC_Product $product Product object
     * @return string Modified price
     */
    public function modify_product_price($price, $product) {
        // Custom logic for modifying product price
        return $price;
    }

    /**
     * Modify product regular price
     *
     * @param string     $price   Product regular price
     * @param \WC_Product $product Product object
     * @return string Modified regular price
     */
    public function modify_product_regular_price($price, $product) {
        // Custom logic for modifying product regular price
        return $price;
    }

    /**
     * Modify product sale price
     *
     * @param string     $price   Product sale price
     * @param \WC_Product $product Product object
     * @return string Modified sale price
     */
    public function modify_product_sale_price($price, $product) {
        // Custom logic for modifying product sale price
        return $price;
    }

    /**
     * Get product by ID
     *
     * @param int $product_id Product ID
     * @return \WC_Product|null Product object or null if not found
     */
    public function get_product($product_id) {
        return wc_get_product($product_id);
    }

    /**
     * Get product stock quantity
     *
     * @param int $product_id Product ID
     * @return int|null Stock quantity or null if not managed
     */
    public function get_stock_quantity($product_id): ?int {
        $product = $this->get_product($product_id);

        if ($product && $product->managing_stock()) {
            return $product->get_stock_quantity();
        }

        return null;
    }
}
