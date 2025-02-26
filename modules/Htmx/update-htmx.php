<?php
/**
 * HTMX Update Script
 *
 * This script updates HTMX and its extensions to the latest versions.
 * It can be run manually or scheduled as a cron job.
 *
 * Usage: php update-htmx.php
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/Htmx
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    // Define the plugin path for CLI usage
    if (PHP_SAPI === 'cli') {
        // Get the plugin directory from the script location
        define('MC_PLUGIN_DIR', dirname(dirname(dirname(__FILE__))) . '/');
        require_once MC_PLUGIN_DIR . 'includes/MC_Logger.php';

        // Create a simple logger for CLI
        class CliLogger {
            public function log_info($message) {
                echo "[INFO] $message\n";
            }

            public function log_error($message) {
                echo "[ERROR] $message\n";
            }

            public function log_warning($message) {
                echo "[WARNING] $message\n";
            }

            public function log_debug($message) {
                echo "[DEBUG] $message\n";
            }
        }

        $logger = new CliLogger();
    } else {
        exit;
    }
} else {
    // WordPress environment
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mesmeric-commerce'));
    }

    // Get the plugin logger
    global $mesmeric_commerce;
    $logger = $mesmeric_commerce->get_logger();
}

// Configuration
$config = [
    'htmx_url' => 'https://unpkg.com/htmx.org/dist/htmx.min.js',
    'extensions_base_url' => 'https://unpkg.com/htmx.org/dist/ext/',
    'extensions' => [
        'json-enc.js',
        'loading-states.js',
        'class-tools.js',
        'ajax-header.js',
        'response-targets.js',
        'path-deps.js',
        'morphdom-swap.js',
        'alpine-morph.js',
        'debug.js',
        'preload.js',
        'sse.js',
        'ws.js',
        'event-header.js',
        'include-vals.js',
        'remove-me.js',
        'method-override.js',
        'client-side-templates.js',
        'head-support.js',
        'multi-swap.js',
        'restored.js',
    ],
    'assets_dir' => MC_PLUGIN_DIR . 'assets/js/htmx/',
    'extensions_dir' => MC_PLUGIN_DIR . 'assets/js/htmx/ext/',
];

// Ensure directories exist
if (!file_exists($config['assets_dir'])) {
    mkdir($config['assets_dir'], 0755, true);
}

if (!file_exists($config['extensions_dir'])) {
    mkdir($config['extensions_dir'], 0755, true);
}

/**
 * Download a file from a URL
 *
 * @param string $url The URL to download from
 * @param string $destination The destination file path
 * @return bool True on success, false on failure
 */
function download_file($url, $destination, $logger) {
    $logger->log_info("Downloading $url to $destination");

    // Use cURL if available
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = fopen($destination, 'w');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $success = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (!$success) {
            $logger->log_error("cURL error: $error");
            return false;
        }

        return true;
    }

    // Fall back to file_get_contents
    $content = @file_get_contents($url);
    if ($content === false) {
        $logger->log_error("Failed to download $url");
        return false;
    }

    $result = @file_put_contents($destination, $content);
    if ($result === false) {
        $logger->log_error("Failed to write to $destination");
        return false;
    }

    return true;
}

/**
 * Get the version of a JavaScript file
 *
 * @param string $file_path Path to the JavaScript file
 * @return string|null Version string or null if not found
 */
function get_js_version($file_path, $logger) {
    if (!file_exists($file_path)) {
        return null;
    }

    $content = file_get_contents($file_path);

    // Try to find version in comments
    if (preg_match('/version\s*[:=]\s*[\'"]?([0-9]+\.[0-9]+\.[0-9]+)[\'"]?/i', $content, $matches)) {
        return $matches[1];
    }

    // Try to find version in htmx.version property
    if (preg_match('/htmx\.version\s*=\s*[\'"]([0-9]+\.[0-9]+\.[0-9]+)[\'"]/', $content, $matches)) {
        return $matches[1];
    }

    $logger->log_warning("Could not determine version for $file_path");
    return null;
}

// Update HTMX core
$htmx_file = $config['assets_dir'] . 'htmx.min.js';
$current_version = get_js_version($htmx_file, $logger);

$logger->log_info("Updating HTMX core...");
$logger->log_info("Current version: " . ($current_version ?? 'unknown'));

if (download_file($config['htmx_url'], $htmx_file, $logger)) {
    $new_version = get_js_version($htmx_file, $logger);
    $logger->log_info("HTMX core updated to version: " . ($new_version ?? 'unknown'));
} else {
    $logger->log_error("Failed to update HTMX core");
}

// Update extensions
$logger->log_info("Updating HTMX extensions...");
$updated_count = 0;
$failed_count = 0;

foreach ($config['extensions'] as $extension) {
    $extension_url = $config['extensions_base_url'] . $extension;
    $extension_file = $config['extensions_dir'] . $extension;

    if (download_file($extension_url, $extension_file, $logger)) {
        $logger->log_info("Updated extension: $extension");
        $updated_count++;
    } else {
        $logger->log_error("Failed to update extension: $extension");
        $failed_count++;
    }
}

$logger->log_info("HTMX update completed");
$logger->log_info("Updated $updated_count extensions");
if ($failed_count > 0) {
    $logger->log_warning("Failed to update $failed_count extensions");
}

// Create or update version file
$version_file = $config['assets_dir'] . 'version.json';
$version_data = [
    'htmx_version' => $new_version ?? 'unknown',
    'last_updated' => date('Y-m-d H:i:s'),
    'extensions' => $config['extensions'],
];

file_put_contents($version_file, json_encode($version_data, JSON_PRETTY_PRINT));
$logger->log_info("Version information saved to $version_file");

// Output summary
$logger->log_info("=== Update Summary ===");
$logger->log_info("HTMX version: " . ($new_version ?? 'unknown'));
$logger->log_info("Extensions updated: $updated_count");
$logger->log_info("Extensions failed: $failed_count");
$logger->log_info("Update completed at: " . date('Y-m-d H:i:s'));
