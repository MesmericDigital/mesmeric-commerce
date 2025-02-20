<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Services;

use MesmericCommerce\WooCommerce\Abstracts\MC_WC_Abstract_Service;
use MesmericCommerce\WooCommerce\Includes\MC_Evri_Shipping_Method;

/**
 * Shipping Service
 *
 * Handles all shipping method registrations and functionality.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/services
 * @since      1.0.0
 */
class MC_Shipping_Service extends MC_WC_Abstract_Service {
    /**
     * Default settings
     *
     * @since 1.0.0
     * @var array<string, mixed>
     */
    protected array $default_settings = [
        'enable_evri' => true,
        'enable_custom_rates' => false,
        'enable_local_pickup' => true,
        'enable_shipping_zones' => true,
    ];

    /**
     * Initialize the service.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        if (!$this->is_enabled()) {
            return;
        }

        // Register shipping methods
        add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_methods']);

        // Admin hooks
        if (is_admin()) {
            $this->init_admin();
        }

        // Frontend hooks
        if (!is_admin()) {
            $this->init_frontend();
        }

        // AJAX handlers
        $this->init_ajax_handlers();

        // Order status hooks
        add_action('mc_tracking_number_added', [$this, 'update_order_status_on_tracking'], 10, 2);
    }

    /**
     * Initialize admin functionality.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_admin(): void {
        // Add admin menu items
        add_action('admin_menu', [$this, 'add_admin_menu_items']);

        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);

        // Save meta box data
        add_action('save_post', [$this, 'save_meta_box_data']);
    }

    /**
     * Initialize frontend functionality.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_frontend(): void {
        // Add frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Add shipping calculator hooks
        add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
        add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_true');
    }

    /**
     * Initialize AJAX handlers.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_ajax_handlers(): void {
        // Handle shipping rate updates
        add_action('wp_ajax_mc_update_shipping_rates', [$this, 'handle_update_shipping_rates']);
        add_action('wp_ajax_nopriv_mc_update_shipping_rates', [$this, 'handle_update_shipping_rates']);

        // Handle shipping label generation
        add_action('wp_ajax_mc_generate_shipping_label', [$this, 'handle_generate_shipping_label']);
    }

    /**
     * Register shipping methods.
     *
     * @since 1.0.0
     * @param array<string, string> $methods Shipping methods
     * @return array<string, string>
     */
    public function register_shipping_methods(array $methods): array {
        // Register Evri shipping if enabled
        if ($this->get_setting('enable_evri', true)) {
            $methods['evri'] = MC_Evri_Shipping_Method::class;
        }

        // Add more shipping methods here

        return $methods;
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
            __('Shipping Settings', 'mesmeric-commerce'),
            __('Shipping Settings', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mc-shipping-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['woocommerce_page_mc-shipping-settings', 'shop_order'], true)) {
            return;
        }

        wp_enqueue_style(
            'mc-shipping-admin',
            MC_PLUGIN_URL . 'woocommerce/assets/css/shipping-admin.css',
            [],
            MC_VERSION
        );

        wp_enqueue_script(
            'mc-shipping-admin',
            MC_PLUGIN_URL . 'woocommerce/assets/js/shipping-admin.js',
            ['jquery', 'wp-api-fetch'],
            MC_VERSION,
            true
        );

        wp_localize_script('mc-shipping-admin', 'mcShippingAdmin', [
            'nonce' => wp_create_nonce('mc-shipping-nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        if (!is_cart() && !is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'mc-shipping-frontend',
            MC_PLUGIN_URL . 'woocommerce/assets/css/shipping-frontend.css',
            [],
            MC_VERSION
        );

        wp_enqueue_script(
            'mc-shipping-frontend',
            MC_PLUGIN_URL . 'woocommerce/assets/js/shipping-frontend.js',
            ['jquery', 'wp-api-fetch'],
            MC_VERSION,
            true
        );
    }

    /**
     * Add meta boxes.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'mc-shipping-info',
            __('Shipping Information', 'mesmeric-commerce'),
            [$this, 'render_shipping_meta_box'],
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Save meta box data.
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return void
     */
    public function save_meta_box_data(int $post_id): void {
        if (!isset($_POST['mc_shipping_nonce']) || !wp_verify_nonce($_POST['mc_shipping_nonce'], 'mc_shipping_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save shipping meta data
        if (isset($_POST['mc_tracking_number'])) {
            $tracking_number = sanitize_text_field($_POST['mc_tracking_number']);
            $old_tracking_number = get_post_meta($post_id, '_mc_tracking_number', true);

            update_post_meta($post_id, '_mc_tracking_number', $tracking_number);

            // If tracking number is new or changed, trigger the status update
            if ($tracking_number && $tracking_number !== $old_tracking_number) {
                do_action('mc_tracking_number_added', $post_id, $tracking_number);
            }
        }
    }

    /**
     * Handle shipping rate updates.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_update_shipping_rates(): void {
        check_ajax_referer('mc-shipping-nonce', 'nonce');

        // Implementation here

        wp_send_json_success();
    }

    /**
     * Handle shipping label generation.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_generate_shipping_label(): void {
        check_ajax_referer('mc-shipping-nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'mesmeric-commerce'));
        }

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if (!$order_id) {
            wp_send_json_error(__('Invalid order ID', 'mesmeric-commerce'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Order not found', 'mesmeric-commerce'));
        }

        try {
            // Get the API helper
            $api_helper = new MC_Evri_API_Helper(
                $this->get_setting('evri_api_key', ''),
                (bool) $this->get_setting('evri_test_mode', false)
            );

            // Generate label
            $response = $api_helper->create_shipping_label($order_id, [
                'contents' => $order->get_items(),
                'destination' => [
                    'address' => $order->get_shipping_address_1(),
                    'city' => $order->get_shipping_city(),
                    'postcode' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country(),
                ],
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            // Save tracking number
            if (!empty($response['tracking_number'])) {
                update_post_meta($order_id, '_mc_tracking_number', $response['tracking_number']);
                do_action('mc_tracking_number_added', $order_id, $response['tracking_number']);
            }

            wp_send_json_success([
                'message' => __('Shipping label generated successfully', 'mesmeric-commerce'),
                'tracking_number' => $response['tracking_number'] ?? '',
                'label_url' => $response['label_url'] ?? '',
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Render settings page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page(): void {
        // Implementation will be in a separate template file
        include MC_PLUGIN_DIR . 'woocommerce/admin/views/shipping-settings.php';
    }

    /**
     * Render shipping meta box.
     *
     * @since 1.0.0
     * @param \WP_Post $post Post object
     * @return void
     */
    public function render_shipping_meta_box(\WP_Post $post): void {
        // Implementation will be in a separate template file
        include MC_PLUGIN_DIR . 'woocommerce/admin/views/shipping-meta-box.php';
    }

    /**
     * Update order status when tracking number is added.
     *
     * @since 1.0.0
     * @param int    $order_id       Order ID
     * @param string $tracking_number Tracking number
     * @return void
     */
    public function update_order_status_on_tracking(int $order_id, string $tracking_number): void {
        $order = wc_get_order($order_id);
        if (!$order || $order->get_status() === 'completed') {
            return;
        }

        // Update order status and add note
        $order->update_status(
            'completed',
            sprintf(
                /* translators: %s: tracking number */
                __('Order shipped. Tracking number: %s', 'mesmeric-commerce'),
                $tracking_number
            )
        );

        // Log the status change
        $this->log(
            sprintf(
                /* translators: 1: order ID, 2: tracking number */
                __('Order #%1$s status updated to completed. Tracking number: %2$s', 'mesmeric-commerce'),
                $order_id,
                $tracking_number
            )
        );
    }
}
