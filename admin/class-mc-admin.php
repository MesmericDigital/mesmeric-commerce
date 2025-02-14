<?php
declare(strict_types=1);

namespace MesmericCommerce\Admin;

use Mesmeric_Commerce\Includes\MC_Plugin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/admin
 */
class MC_Admin {

    /**
     * The plugin's instance.
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        global $mesmeric_commerce;
        $this->plugin = $mesmeric_commerce;

        // Example of using the logger
        add_action('admin_init', [$this, 'log_admin_access']);
    }

    /**
     * Example method to demonstrate logger usage
     *
     * @return void
     */
    public function log_admin_access(): void {
        $current_user = wp_get_current_user();
        $message = sprintf(
            'Admin area accessed by user: %s (ID: %d)',
            $current_user->user_login,
            $current_user->ID
        );

        // Log to both Mesmeric Commerce and plugins logs
        $this->plugin->get_logger()->log_error($message, 'info', true);
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles(): void {
        wp_enqueue_style(
            'mesmeric-commerce-admin',
            MC_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            MC_VERSION,
            'all'
        );

        // Enqueue Tailwind CSS with DaisyUI
        wp_enqueue_style(
            'mesmeric-commerce-tailwind',
            MC_PLUGIN_URL . 'admin/css/tailwind.min.css',
            [],
            MC_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts(): void {
        // Alpine.js
        wp_enqueue_script(
            'alpine-js',
            MC_PLUGIN_URL . 'admin/js/alpine.min.js',
            [],
            '3.13.3',
            true
        );

        // Vue.js (only on plugin admin pages)
        if ($this->is_plugin_admin_page()) {
            wp_enqueue_script(
                'mesmeric-commerce-vue',
                MC_PLUGIN_URL . 'admin/vue-backend/dist/app.js',
                [],
                MC_VERSION,
                true
            );

            wp_localize_script(
                'mesmeric-commerce-vue',
                'mcAdminData',
                [
                    'apiNonce' => wp_create_nonce('wp_rest'),
                    'apiUrl' => rest_url('mesmeric-commerce/v1'),
                    'adminUrl' => admin_url(),
                    'pluginUrl' => MC_PLUGIN_URL,
                ]
            );
        }

        // Admin scripts
        wp_enqueue_script(
            'mesmeric-commerce-admin',
            MC_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery'],
            MC_VERSION,
            true
        );
    }

    /**
     * Add plugin admin menu.
     */
    public function add_plugin_admin_menu(): void {
        add_menu_page(
            __('Mesmeric Commerce', 'mesmeric-commerce'),
            __('Mesmeric Commerce', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mesmeric-commerce',
            [$this, 'display_plugin_admin_dashboard'],
            'dashicons-cart',
            56
        );

        add_submenu_page(
            'mesmeric-commerce',
            __('Dashboard', 'mesmeric-commerce'),
            __('Dashboard', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mesmeric-commerce',
            [$this, 'display_plugin_admin_dashboard']
        );

        add_submenu_page(
            'mesmeric-commerce',
            __('Settings', 'mesmeric-commerce'),
            __('Settings', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mesmeric-commerce-settings',
            [$this, 'display_plugin_admin_settings']
        );

        add_submenu_page(
            'mesmeric-commerce',
            __('Modules', 'mesmeric-commerce'),
            __('Modules', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mesmeric-commerce-modules',
            [$this, 'display_plugin_admin_modules']
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings(): void {
        register_setting(
            'mesmeric_commerce_settings',
            'mc_enable_quickview',
            [
                'type' => 'string',
                'default' => 'yes',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'mesmeric_commerce_settings',
            'mc_enable_wishlist',
            [
                'type' => 'string',
                'default' => 'yes',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'mesmeric_commerce_settings',
            'mc_enable_shipping',
            [
                'type' => 'string',
                'default' => 'yes',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'mesmeric_commerce_settings',
            'mc_enable_inventory',
            [
                'type' => 'string',
                'default' => 'yes',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'mesmeric_commerce_settings',
            'mc_inventory_low_threshold',
            [
                'type' => 'number',
                'default' => 5,
                'sanitize_callback' => 'absint',
            ]
        );

        register_setting(
            'mesmeric_commerce_settings',
            'mc_inventory_notification_email',
            [
                'type' => 'string',
                'default' => get_option('admin_email'),
                'sanitize_callback' => 'sanitize_email',
            ]
        );
    }

    /**
     * Display the plugin admin dashboard.
     */
    public function display_plugin_admin_dashboard(): void {
        require_once MC_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display the plugin admin settings.
     */
    public function display_plugin_admin_settings(): void {
        require_once MC_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Display the plugin admin modules page.
     */
    public function display_plugin_admin_modules(): void {
        require_once MC_PLUGIN_DIR . 'admin/partials/modules.php';
    }

    /**
     * Check if current page is a plugin admin page.
     *
     * @return bool
     */
    private function is_plugin_admin_page(): bool {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'mesmeric-commerce') !== false;
    }
}
