<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce;

use MesmericCommerce\Includes\MC_Plugin;
use MesmericCommerce\WooCommerce\Includes\MC_WC_Performance;
use MesmericCommerce\WooCommerce\Services\MC_Cart_Service;
use MesmericCommerce\WooCommerce\Services\MC_Product_Service;
use MesmericCommerce\WooCommerce\Services\MC_Order_Service;
use MesmericCommerce\WooCommerce\Shipping\MC_ShippingHandler;

/**
 * WooCommerce Integration Class
 *
 * Handles core WooCommerce integration, performance optimizations, and service management.
 * This class acts as the main entry point for all WooCommerce-related functionality.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce
 * @since      1.0.0
 */
class MC_WooCommerce {
    /**
     * The performance optimization instance.
     *
     * @since 1.0.0
     * @var MC_WC_Performance
     */
    private MC_WC_Performance $performance;

    /**
     * The cart service instance.
     *
     * @since 1.0.0
     * @var MC_Cart_Service
     */
    private MC_Cart_Service $cart_service;

    /**
     * The product service instance.
     *
     * @since 1.0.0
     * @var MC_Product_Service
     */
    private MC_Product_Service $product_service;

    /**
     * The order service instance.
     *
     * @since 1.0.0
     * @var MC_Order_Service
     */
    private MC_Order_Service $order_service;

    /**
     * The shipping handler instance.
     *
     * @since 1.0.0
     * @var MC_ShippingHandler
     */
    private MC_ShippingHandler $shipping_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     */
    public function __construct(MC_Plugin $plugin) {
        $this->performance = new MC_WC_Performance();
        $this->cart_service = new MC_Cart_Service();
        $this->product_service = new MC_Product_Service();
        $this->order_service = new MC_Order_Service();
        $this->shipping_handler = new MC_ShippingHandler($plugin);
    }

    /**
     * Initialize WooCommerce integration.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Initialize performance optimizations
        $this->performance->init();

        // Initialize services
        $this->cart_service->init();
        $this->product_service->init();
        $this->order_service->init();

        // Template overrides
        add_filter('woocommerce_locate_template', [$this, 'locate_template'], 10, 3);

        // Register custom endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);

        // Admin optimizations
        if (is_admin()) {
            $this->init_admin();
        }
    }

    /**
     * Initialize admin-specific functionality.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_admin(): void {
        // Add admin menu items
        add_action('admin_menu', [$this, 'add_admin_menu_items']);

        // Add admin notices
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Locate template files with performance optimization.
     *
     * @since 1.0.0
     * @param string $template      Template file
     * @param string $template_name Template name
     * @param string $template_path Template path
     * @return string
     */
    public function locate_template(string $template, string $template_name, string $template_path): string {
        static $template_cache = [];

        $cache_key = md5($template . $template_name . $template_path);

        if (isset($template_cache[$cache_key])) {
            return $template_cache[$cache_key];
        }

        $plugin_template = MC_PLUGIN_DIR . 'woocommerce/templates/overrides/' . $template_name;

        $template_cache[$cache_key] = file_exists($plugin_template) ? $plugin_template : $template;

        return $template_cache[$cache_key];
    }

    /**
     * Register custom REST API endpoints.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_rest_endpoints(): void {
        register_rest_route(
            'mesmeric-commerce/v1',
            '/cart',
            [
                'methods' => 'GET',
                'callback' => [$this->cart_service, 'get_cart_data'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'mesmeric-commerce/v1',
            '/products/performance-data',
            [
                'methods' => 'GET',
                'callback' => [$this->product_service, 'get_performance_data'],
                'permission_callback' => [$this, 'admin_api_permission_check'],
            ]
        );
    }

    /**
     * Check if user has permission to access admin API endpoints.
     *
     * @since 1.0.0
     * @return bool
     */
    public function admin_api_permission_check(): bool {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Add admin menu items.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu_items(): void {
        add_submenu_page(
            'woocommerce',
            __('Performance', 'mesmeric-commerce'),
            __('Performance', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mc-wc-performance',
            [$this->performance, 'render_admin_page']
        );
    }

    /**
     * Display admin notices.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_admin_notices(): void {
        $this->performance->display_notices();
    }
}
