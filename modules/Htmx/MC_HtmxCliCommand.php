<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Htmx;

use WP_CLI;
use WP_CLI\Utils;

/**
 * Manages HTMX library and extensions for Mesmeric Commerce.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/Htmx
 */
class MC_HtmxCliCommand {

    /**
     * Updates HTMX and its extensions to the latest versions.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Force update even if the current version is up to date.
     *
     * [--extensions=<extensions>]
     * : Comma-separated list of extensions to update. If not provided, all extensions will be updated.
     *
     * ## EXAMPLES
     *
     *     # Update HTMX and all extensions
     *     $ wp mesmeric htmx update
     *
     *     # Force update HTMX and all extensions
     *     $ wp mesmeric htmx update --force
     *
     *     # Update specific extensions
     *     $ wp mesmeric htmx update --extensions=json-enc.js,loading-states.js
     *
     * @when after_wp_load
     *
     * @param array $args       Command arguments.
     * @param array $assoc_args Command options.
     */
    public function update($args, $assoc_args) {
        $force = Utils\get_flag_value($assoc_args, 'force', false);
        $extensions_arg = Utils\get_flag_value($assoc_args, 'extensions', '');

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

        // Filter extensions if specified
        if (!empty($extensions_arg)) {
            $requested_extensions = explode(',', $extensions_arg);
            $config['extensions'] = array_filter($config['extensions'], function($ext) use ($requested_extensions) {
                return in_array($ext, $requested_extensions);
            });

            if (empty($config['extensions'])) {
                WP_CLI::error('No valid extensions specified. Please check the extension names.');
                return;
            }
        }

        // Ensure directories exist
        if (!file_exists($config['assets_dir'])) {
            WP_CLI::log("Creating directory: {$config['assets_dir']}");
            if (!mkdir($config['assets_dir'], 0755, true)) {
                WP_CLI::error("Failed to create directory: {$config['assets_dir']}");
                return;
            }
        }

        if (!file_exists($config['extensions_dir'])) {
            WP_CLI::log("Creating directory: {$config['extensions_dir']}");
            if (!mkdir($config['extensions_dir'], 0755, true)) {
                WP_CLI::error("Failed to create directory: {$config['extensions_dir']}");
                return;
            }
        }

        // Update HTMX core
        $htmx_file = $config['assets_dir'] . 'htmx.min.js';
        $current_version = $this->get_js_version($htmx_file);

        WP_CLI::log("Current HTMX version: " . ($current_version ?? 'unknown'));

        if ($force || !$current_version) {
            WP_CLI::log("Downloading HTMX core...");

            $tmp_file = download_url($config['htmx_url']);
            if (is_wp_error($tmp_file)) {
                WP_CLI::error("Failed to download HTMX: " . $tmp_file->get_error_message());
                return;
            }

            if (!copy($tmp_file, $htmx_file)) {
                WP_CLI::error("Failed to copy HTMX to destination: $htmx_file");
                @unlink($tmp_file);
                return;
            }

            @unlink($tmp_file);

            $new_version = $this->get_js_version($htmx_file);
            WP_CLI::success("HTMX core updated to version: " . ($new_version ?? 'unknown'));
        } else {
            WP_CLI::log("HTMX core is already up to date. Use --force to update anyway.");
        }

        // Update extensions
        WP_CLI::log("Updating HTMX extensions...");
        $updated_count = 0;
        $failed_count = 0;
        $skipped_count = 0;

        $progress = \WP_CLI\Utils\make_progress_bar('Updating extensions', count($config['extensions']));

        foreach ($config['extensions'] as $extension) {
            $progress->tick();

            $extension_url = $config['extensions_base_url'] . $extension;
            $extension_file = $config['extensions_dir'] . $extension;

            if (!$force && file_exists($extension_file)) {
                $skipped_count++;
                continue;
            }

            $tmp_file = download_url($extension_url);
            if (is_wp_error($tmp_file)) {
                WP_CLI::warning("Failed to download extension $extension: " . $tmp_file->get_error_message());
                $failed_count++;
                continue;
            }

            if (!copy($tmp_file, $extension_file)) {
                WP_CLI::warning("Failed to copy extension $extension to destination: $extension_file");
                @unlink($tmp_file);
                $failed_count++;
                continue;
            }

            @unlink($tmp_file);
            $updated_count++;
        }

        $progress->finish();

        // Create or update version file
        $version_file = $config['assets_dir'] . 'version.json';
        $version_data = [
            'htmx_version' => $new_version ?? $current_version ?? 'unknown',
            'last_updated' => date('Y-m-d H:i:s'),
            'extensions' => $config['extensions'],
        ];

        file_put_contents($version_file, json_encode($version_data, JSON_PRETTY_PRINT));

        // Output summary
        WP_CLI::log("=== Update Summary ===");
        WP_CLI::log("HTMX version: " . ($new_version ?? $current_version ?? 'unknown'));
        WP_CLI::log("Extensions updated: $updated_count");
        if ($skipped_count > 0) {
            WP_CLI::log("Extensions skipped: $skipped_count (use --force to update)");
        }
        if ($failed_count > 0) {
            WP_CLI::warning("Extensions failed: $failed_count");
        }

        WP_CLI::success("HTMX update completed at: " . date('Y-m-d H:i:s'));
    }

    /**
     * Lists installed HTMX extensions.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     # List all installed extensions in table format
     *     $ wp mesmeric htmx list
     *
     *     # List all installed extensions in JSON format
     *     $ wp mesmeric htmx list --format=json
     *
     * @when after_wp_load
     *
     * @param array $args       Command arguments.
     * @param array $assoc_args Command options.
     */
    public function list($args, $assoc_args) {
        $format = Utils\get_flag_value($assoc_args, 'format', 'table');
        $extensions_dir = MC_PLUGIN_DIR . 'assets/js/htmx/ext/';
        $htmx_file = MC_PLUGIN_DIR . 'assets/js/htmx/htmx.min.js';

        // Check if HTMX is installed
        if (!file_exists($htmx_file)) {
            WP_CLI::error("HTMX is not installed. Run 'wp mesmeric htmx update' to install it.");
            return;
        }

        $htmx_version = $this->get_js_version($htmx_file) ?? 'unknown';

        // Get installed extensions
        $extensions = [];

        if (file_exists($extensions_dir)) {
            $files = glob($extensions_dir . '*.js');

            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $modified = filemtime($file);

                $extensions[] = [
                    'name' => $filename,
                    'size' => $this->format_size($size),
                    'modified' => date('Y-m-d H:i:s', $modified),
                ];
            }
        }

        // Output HTMX version
        WP_CLI::log("HTMX version: $htmx_version");

        if (empty($extensions)) {
            WP_CLI::log("No extensions installed.");
            return;
        }

        // Format and output the extensions list
        WP_CLI\Utils\format_items($format, $extensions, ['name', 'size', 'modified']);
    }

    /**
     * Gets information about HTMX installation.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: yaml
     * options:
     *   - yaml
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Get HTMX information in YAML format
     *     $ wp mesmeric htmx info
     *
     *     # Get HTMX information in JSON format
     *     $ wp mesmeric htmx info --format=json
     *
     * @when after_wp_load
     *
     * @param array $args       Command arguments.
     * @param array $assoc_args Command options.
     */
    public function info($args, $assoc_args) {
        $format = Utils\get_flag_value($assoc_args, 'format', 'yaml');
        $assets_dir = MC_PLUGIN_DIR . 'assets/js/htmx/';
        $htmx_file = $assets_dir . 'htmx.min.js';
        $version_file = $assets_dir . 'version.json';

        $info = [
            'installed' => file_exists($htmx_file),
            'version' => $this->get_js_version($htmx_file) ?? 'unknown',
            'file_size' => file_exists($htmx_file) ? $this->format_size(filesize($htmx_file)) : 'N/A',
            'last_modified' => file_exists($htmx_file) ? date('Y-m-d H:i:s', filemtime($htmx_file)) : 'N/A',
            'extensions_count' => 0,
            'extensions_dir_exists' => file_exists($assets_dir . 'ext/'),
            'version_file_exists' => file_exists($version_file),
        ];

        // Count extensions
        if ($info['extensions_dir_exists']) {
            $info['extensions_count'] = count(glob($assets_dir . 'ext/*.js'));
        }

        // Get version file info
        if ($info['version_file_exists']) {
            $version_data = json_decode(file_get_contents($version_file), true);
            $info['version_file'] = $version_data;
        }

        // Output the info
        if ($format === 'json') {
            WP_CLI::line(json_encode($info, JSON_PRETTY_PRINT));
        } else {
            WP_CLI\Utils\format_items($format, [$info], array_keys($info));
        }
    }

    /**
     * Get the version of a JavaScript file
     *
     * @param string $file_path Path to the JavaScript file
     * @return string|null Version string or null if not found
     */
    private function get_js_version($file_path) {
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

        return null;
    }

    /**
     * Format file size
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function format_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Register the command if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('mesmeric htmx', MC_HtmxCliCommand::class);
}
