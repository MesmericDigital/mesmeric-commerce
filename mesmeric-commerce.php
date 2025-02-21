<?php
/**
 * Plugin Name: Mesmeric Commerce
 * Plugin URI: https://beanbagplanet.co.uk/mesmeric-commerce
 * Description: A modern, feature-rich enhancement suite for WooCommerce with HTMX, Alpine.js, and Vue.js integration
 * Version: 1.0.0
 * Author: Bean Bag Planet
 * Author URI: https://beanbagplanet.co.uk
 * Text Domain: mesmeric-commerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * WC requires at least: 8.0.0
 * WC tested up to: 8.5.1
 *
 * @package MesmericCommerce
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define( 'MC_VERSION', '1.0.0' );
define( 'MC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'MC_PLUGIN_FILE', __FILE__ );
define( 'MC_VENDOR_PATH', __DIR__ . '/vendor/autoload.php' );

// Load Composer autoloader
if ( file_exists( MC_VENDOR_PATH ) ) {
    try {
        require_once MC_VENDOR_PATH;
    } catch ( Throwable $e ) {
        error_log( sprintf( '[Mesmeric Commerce] Error loading autoloader: %s', $e->getMessage() ) );
        return;
    }
} else {
    // If no autoloader, manually require core files
    $core_files = array(
        'includes/MC_Loader.php',
        'includes/MC_Logger.php',
        'includes/MC_I18n.php',
        'includes/MC_Database.php',
        'includes/MC_Media.php',
        'includes/MC_Security.php',
        'includes/MC_Plugin.php',
        'includes/MC_TwigService.php',
        'includes/MC_WooCommerceLogger.php',
        'includes/MC_LogsRestController.php',
    );

    foreach ( $core_files as $file ) {
        $file_path = MC_PLUGIN_DIR . $file;
        if ( ! file_exists( $file_path ) ) {
            throw new \RuntimeException( sprintf( 'Required file not found: %s', $file ) );
        }
        require_once $file_path;
    }
}

use MesmericCommerce\Includes\MC_Activator;
use MesmericCommerce\Includes\MC_Deactivator;
use MesmericCommerce\Includes\MC_Security;
use MesmericCommerce\Includes\MC_ErrorHandler;
use MesmericCommerce\Includes\MC_Plugin;
use MesmericCommerce\Admin\MC_Admin;
use MesmericCommerce\Frontend\MC_Public;

// Initialize security features
$security = MC_Security::get_instance();
$security->setup_hooks();

/**
 * Check if WooCommerce is active and meets version requirements
 */
function mc_check_dependencies(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="error"><p>Mesmeric Commerce requires WooCommerce to be installed and activated.</p></div>';
		} );
		return false;
	}

	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '8.0.0', '<' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="error"><p>Mesmeric Commerce requires WooCommerce 8.0.0 or higher.</p></div>';
		} );
		return false;
	}

	return true;
}

// Initialize error handler
if ( ! defined( 'MC_ERROR_HANDLER' ) ) {
	define( 'MC_ERROR_HANDLER', true );
	$error_handler = new MC_ErrorHandler();
}

// Register activation hook
register_activation_hook( __FILE__, function (): void {
	MC_Activator::activate();
} );

// Register deactivation hook
register_deactivation_hook( __FILE__, function (): void {
	MC_Deactivator::deactivate();
} );

// Initialize plugin if dependencies are met
if ( mc_check_dependencies() ) {
	$plugin = new MC_Plugin();
	$plugin->run();

	// Instantiate admin class
	$admin = new MC_Admin( 'mesmeric-commerce', MC_VERSION, $plugin );

	// Instantiate public class
	$public = new MC_Public( 'mesmeric-commerce', MC_VERSION );

	add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_styles' ) );
	add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_scripts' ) );
}
