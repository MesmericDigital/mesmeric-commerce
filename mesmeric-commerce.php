<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://beanbagplanet.co.uk
 * @since             1.0.0
 * @package           MesmericCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Mesmeric Commerce
 * Plugin URI:        https://beanbagplanet.co.uk
 * Description:       A modern, feature-rich enhancement suite for WooCommerce with HTMX, Alpine.js, and Vue.js integration.
 * Version:           1.0.0
 * Author:            Bean Bag Planet
 * Author URI:        https://beanbagplanet.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mesmeric-commerce
 * Domain Path:       /languages
 * Requires PHP:      8.3
 * Requires at least: 6.0
 * WC requires at least: 8.0.0
 * WC tested up to:   8.5.2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MC_VERSION', '1.0.0');
define('MC_PLUGIN_FILE', __FILE__);
define('MC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Check if WooCommerce is active
 */
function mc_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', []);

    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
    }

    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Check system requirements
 *
 * @return bool
 */
function mc_check_system_requirements() {
    $requirements = array(
        'php' => '8.3',
        'wordpress' => '6.0',
        'woocommerce' => '8.0.0',
        'extensions' => array(
            'curl',
            'json',
            'mbstring',
            'openssl'
        )
    );

    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            ?>
            <div class="error">
                <p><?php printf(
                    __('Mesmeric Commerce requires PHP %s or higher. Your current PHP version is %s.', 'mesmeric-commerce'),
                    $requirements['php'],
                    PHP_VERSION
                ); ?></p>
            </div>
            <?php
        });
        return false;
    }

    // Check WordPress version
    if (version_compare(get_bloginfo('version'), $requirements['wordpress'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            ?>
            <div class="error">
                <p><?php printf(
                    __('Mesmeric Commerce requires WordPress %s or higher.', 'mesmeric-commerce'),
                    $requirements['wordpress']
                ); ?></p>
            </div>
            <?php
        });
        return false;
    }

    // Check required PHP extensions
    $missing_extensions = array();
    foreach ($requirements['extensions'] as $ext) {
        if (!extension_loaded($ext)) {
            $missing_extensions[] = $ext;
        }
    }

    if (!empty($missing_extensions)) {
        add_action('admin_notices', function() use ($missing_extensions) {
            ?>
            <div class="error">
                <p><?php printf(
                    __('Mesmeric Commerce requires the following PHP extensions: %s', 'mesmeric-commerce'),
                    implode(', ', $missing_extensions)
                ); ?></p>
            </div>
            <?php
        });
        return false;
    }

    return true;
}

/**
 * Check WooCommerce dependency
 */
function mc_check_woocommerce_dependency() {
    if (!mc_is_woocommerce_active()) {
        add_action('admin_notices', 'mc_woocommerce_missing_notice');
        return false;
    }

    // Check WooCommerce version
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '8.0.0', '<')) {
        add_action('admin_notices', 'mc_woocommerce_version_notice');
        return false;
    }

    return true;
}

/**
 * WooCommerce missing notice
 */
function mc_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('Mesmeric Commerce requires WooCommerce to be installed and active.', 'mesmeric-commerce'); ?></p>
    </div>
    <?php
}

/**
 * WooCommerce version notice
 */
function mc_woocommerce_version_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('Mesmeric Commerce requires WooCommerce version 8.0.0 or higher.', 'mesmeric-commerce'); ?></p>
    </div>
    <?php
}

/**
 * Custom error handler for serious errors
 *
 * @param int    $errno   Error level
 * @param string $errstr  Error message
 * @param string $errfile File where the error occurred
 * @param int    $errline Line number where the error occurred
 * @return bool
 */
function mc_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle serious errors
    if (!($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        return false;
    }

    $error_message = sprintf(
        "[%s] Error: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errstr,
        $errfile,
        $errline
    );

    // Log to plugin's error log
    $log_file = MC_PLUGIN_DIR . 'logs/serious-errors.log';
    error_log($error_message, 3, $log_file);

    // Also log to WordPress debug.log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Mesmeric Commerce ' . $error_message);
    }

    return false;
}

/**
 * Custom exception handler
 *
 * @param Throwable $e The exception
 * @return void
 */
function mc_exception_handler($e) {
    $error_message = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    // Log to plugin's error log
    $log_file = MC_PLUGIN_DIR . 'logs/serious-errors.log';
    error_log($error_message, 3, $log_file);

    // Also log to WordPress debug.log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Mesmeric Commerce ' . $error_message);
    }

    // Show an admin notice if in admin area
    if (is_admin()) {
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="error">
                <p><?php _e('Mesmeric Commerce encountered a serious error. Please check the error logs.', 'mesmeric-commerce'); ?></p>
                <?php if (WP_DEBUG): ?>
                    <pre><?php echo esc_html($e->getMessage()); ?></pre>
                <?php endif; ?>
            </div>
            <?php
        });
    }
}

/**
 * Register a custom exception handler for autoloader errors
 */
function mc_autoloader_exception_handler($exception) {
    error_log('Mesmeric Commerce Autoloader Error: ' . $exception->getMessage());
    if (MC_DEBUG) {
        error_log('Stack trace: ' . $exception->getTraceAsString());
    }
}

/**
 * The autoloader that loads classes from the MesmericCommerce namespace.
 */
function mc_autoloader($class) {
    // Only handle classes in our namespace
    if (strpos($class, 'MesmericCommerce\\') !== 0) {
        return;
    }

    try {
        // Remove the namespace prefix
        $relative_class = substr($class, strlen('MesmericCommerce\\'));

        // Convert namespace separators to directory separators
        $file_path = str_replace('\\', '/', $relative_class);

        // Try multiple file naming conventions
        $possible_paths = [];

        // 1. Try with the original class name
        $possible_paths[] = MC_PLUGIN_DIR . $file_path . '.php';

        // 2. Try with lowercase path but original class name
        $path_parts = explode('/', $file_path);
        $class_name = array_pop($path_parts);
        $path_parts = array_map('strtolower', $path_parts);
        $path_parts[] = $class_name;
        $possible_paths[] = MC_PLUGIN_DIR . implode('/', $path_parts) . '.php';

        // 3. Special handling for MC_ prefix classes
        if (strpos($class_name, 'MC_') === 0) {
            // Try with class-mc-classname.php format
            $formatted_class_name = 'class-' . strtolower(str_replace('_', '-', $class_name));
            $path_parts[count($path_parts) - 1] = $formatted_class_name;
            $possible_paths[] = MC_PLUGIN_DIR . implode('/', $path_parts) . '.php';

            // Try with MC_ClassName.php format in lowercase directory
            $path_parts[count($path_parts) - 1] = $class_name;
            $possible_paths[] = MC_PLUGIN_DIR . implode('/', $path_parts) . '.php';
        }

        // Try each possible path
        foreach ($possible_paths as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }

        // Log autoloader attempts for debugging
        if (MC_DEBUG) {
            error_log("Mesmeric Commerce Autoloader: Could not find {$class}. Tried: " . implode(', ', $possible_paths));
        }
    } catch (Throwable $e) {
        mc_autoloader_exception_handler($e);
    }
}

// Register the autoloader
spl_autoload_register('mc_autoloader');

// Load core files that might not be autoloaded
$core_files = [
    'includes/MC_Loader.php',
    'includes/MC_Logger.php',
    'includes/MC_i18n.php',
    'includes/MC_Security.php',
    'includes/MC_Database.php',
    'includes/MC_Media.php',
    'includes/MC_TwigService.php',
    'includes/MC_Plugin.php',
    'includes/MC_Activator.php',
    'includes/MC_Deactivator.php',
    'admin/MC_Admin.php',
    'frontend/MC_Public.php',
    'woocommerce/MC_WooCommerce.php'
];

foreach ($core_files as $file) {
    $full_path = MC_PLUGIN_DIR . $file;
    if (file_exists($full_path)) {
        try {
            require_once $full_path;
        } catch (Throwable $e) {
            error_log("Mesmeric Commerce: Error loading core file {$file}: " . $e->getMessage());
            if (MC_DEBUG) {
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
        }
    } else {
        error_log("Mesmeric Commerce: Core file not found: {$full_path}");
    }
}

/**
 * Run Mesmeric Commerce
 */
function run_mesmeric_commerce() {
    // Check system requirements first
    if (!mc_check_system_requirements()) {
        return;
    }

    // Check WooCommerce dependency
    if (!mc_check_woocommerce_dependency()) {
        return;
    }

    // Register error handlers
    set_error_handler('mc_error_handler');
    set_exception_handler('mc_exception_handler');

    // Initialize error logging
    require_once MC_PLUGIN_DIR . 'includes/MC_ErrorHandler.php';
    try {
        $error_handler = new MesmericCommerce\Includes\MC_ErrorHandler();

        // Register log cleanup hook
        add_action('mc_cleanup_logs', [$error_handler, 'cleanup_old_logs']);
        $error_handler->schedule_cleanup();
    } catch (\Throwable $e) {
        mc_exception_handler($e);
        return;
    }

    try {
        // Initialize plugin
        $plugin = new MesmericCommerce\Includes\MC_Plugin();

        // Register deactivation hook
        register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));

        // Run the plugin
        $plugin->run();
    } catch (\Throwable $e) {
        // Log the error
        if (isset($plugin) && method_exists($plugin, 'get_logger')) {
            $plugin->get_logger()->critical('Plugin initialization failed: ' . $e->getMessage(), array(
                'exception' => $e
            ));
        } else {
            error_log('Mesmeric Commerce initialization failed: ' . $e->getMessage());
        }

        // Show admin notice
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="error">
                <p><?php _e('Mesmeric Commerce encountered an error during initialization. Please check the error logs.', 'mesmeric-commerce'); ?></p>
                <?php if (WP_DEBUG): ?>
                    <pre><?php echo esc_html($e->getMessage()); ?></pre>
                <?php endif; ?>
            </div>
            <?php
        });

        return;
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, function() {
    try {
        require_once MC_PLUGIN_DIR . 'includes/MC_Activator.php';
        MesmericCommerce\Includes\MC_Activator::activate();
    } catch (Throwable $e) {
        error_log('Mesmeric Commerce Activation Error: ' . $e->getMessage());
        if (MC_DEBUG) {
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        wp_die('Error activating Mesmeric Commerce: ' . esc_html($e->getMessage()));
    }
});

register_deactivation_hook(__FILE__, function() {
    try {
        require_once MC_PLUGIN_DIR . 'includes/MC_Deactivator.php';
        MesmericCommerce\Includes\MC_Deactivator::deactivate();
    } catch (Throwable $e) {
        error_log('Mesmeric Commerce Deactivation Error: ' . $e->getMessage());
        if (MC_DEBUG) {
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
});

// Initialize the plugin
run_mesmeric_commerce();
