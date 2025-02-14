<?php
declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/includes
 */
class MC_Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      MC_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The logger instance
     *
     * @var MC_Logger
     */
    protected $logger;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->setup_logger();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_modules();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies(): void {
        // Core plugin classes
        require_once MC_PLUGIN_DIR . 'includes/class-mc-loader.php';
        require_once MC_PLUGIN_DIR . 'includes/class-mc-i18n.php';
        require_once MC_PLUGIN_DIR . 'includes/class-mc-logger.php';

        // Admin and public classes
        require_once MC_PLUGIN_DIR . 'admin/class-mc-admin.php';
        require_once MC_PLUGIN_DIR . 'public/class-mc-public.php';

        // WooCommerce integration
        require_once MC_PLUGIN_DIR . 'woocommerce/class-mc-woocommerce.php';

        // Initialize loader
        $this->loader = new MC_Loader();
    }

    /**
     * Setup the logger instance
     */
    private function setup_logger(): void {
        $this->logger = new MC_Logger();

        // Set up error handling
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            $error_message = sprintf(
                'PHP Error [%d]: %s in %s on line %d',
                $errno,
                $errstr,
                $errfile,
                $errline
            );
            $this->logger->log_error($error_message, 'error', true);
            return false; // Let PHP handle the error as well
        });

        // Set up exception handling
        set_exception_handler(function(\Throwable $e) {
            $error_message = sprintf(
                'Uncaught Exception: %s in %s on line %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->logger->log_error($error_message, 'error', true);
        });
    }

    /**
     * Get the logger instance
     *
     * @return MC_Logger
     */
    public function get_logger(): MC_Logger {
        return $this->logger;
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale(): void {
        $plugin_i18n = new MC_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks(): void {
        $plugin_admin = new \MesmericCommerce\Admin\MC_Admin();

        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks(): void {
        $plugin_public = new \MesmericCommerce\Frontend\MC_Public();

        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // WooCommerce integration
        $woocommerce = new \MesmericCommerce\WooCommerce\MC_WooCommerce();
        $this->loader->add_action('init', $woocommerce, 'init');
    }

    /**
     * Initialize plugin modules.
     */
    private function init_modules(): void {
        // Quick View Module
        if (get_option('mc_enable_quickview', 'yes') === 'yes') {
            require_once MC_PLUGIN_DIR . 'modules/quick-view/class-mc-module-quickview.php';
            $quickview = new \MesmericCommerce\Modules\QuickView\MC_Module_QuickView();
            $quickview->init();
        }

        // Wishlist Module
        if (get_option('mc_enable_wishlist', 'yes') === 'yes') {
            require_once MC_PLUGIN_DIR . 'modules/wishlist/class-mc-module-wishlist.php';
            $wishlist = new \MesmericCommerce\Modules\Wishlist\MC_Module_Wishlist();
            $wishlist->init();
        }

        // Shipping Module
        if (get_option('mc_enable_shipping', 'yes') === 'yes') {
            require_once MC_PLUGIN_DIR . 'modules/shipping/class-mc-module-shipping.php';
            $shipping = new \MesmericCommerce\Modules\Shipping\MC_Module_Shipping();
            $shipping->init();
        }

        // Inventory Module
        if (get_option('mc_enable_inventory', 'yes') === 'yes') {
            require_once MC_PLUGIN_DIR . 'modules/inventory/class-mc-module-inventory.php';
            $inventory = new \MesmericCommerce\Modules\Inventory\MC_Module_Inventory();
            $inventory->init();
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run(): void {
        $this->loader->run();
    }
}
