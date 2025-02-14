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

// Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('MC_VERSION', '1.0.0');
define('MC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'MesmericCommerce\\';
    
    // Base directory for the namespace prefix
    $base_dir = plugin_dir_path(__FILE__);

    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Convert class name to file name
    $file_name = 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    // Build the path to the file
    $file = $base_dir . 'includes/' . $file_name;

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize error handler
if (!defined('MC_ERROR_HANDLER')) {
    define('MC_ERROR_HANDLER', true);
    $error_handler = new \MesmericCommerce\Error_Handler();
}

// Activation and deactivation hooks
register_activation_hook(__FILE__, function() {
    require_once MC_PLUGIN_DIR . 'includes/class-mc-activator.php';
    MesmericCommerce\Includes\MC_Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once MC_PLUGIN_DIR . 'includes/class-mc-deactivator.php';
    MesmericCommerce\Includes\MC_Deactivator::deactivate();
});

require_once MC_PLUGIN_DIR . 'includes/class-mc-plugin.php';
$plugin = new MesmericCommerce\Includes\MC_Plugin();
$plugin->run();
