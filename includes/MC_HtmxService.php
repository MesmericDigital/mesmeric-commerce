<?php
declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * HTMX Service Class
 *
 * Handles HTMX integration for the Mesmeric Commerce plugin
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes
 * @since      1.0.0
 */
class MC_HtmxService {
    /**
     * HTMX version
     *
     * @var string
     */
    private string $version = '2.0.0';

    /**
     * HTMX CDN URL
     *
     * @var string
     */
    private string $cdn_url = 'https://unpkg.com/htmx.org/dist/htmx.min.js';

    /**
     * HTMX extensions
     *
     * @var array
     */
    private array $extensions = [];

    /**
     * Whether to load HTMX from CDN
     *
     * @var bool
     */
    private bool $use_cdn = true;

    /**
     * Whether HTMX has been enqueued
     *
     * @var bool
     */
    private bool $is_enqueued = false;

    /**
     * Initialize the class
     */
    public function __construct() {
        // Check if we should use local files instead of CDN
        $this->use_cdn = (bool) get_option('mc_htmx_use_cdn', true);

        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'register_scripts']);
    }

    /**
     * Register HTMX scripts
     *
     * @return void
     */
    public function register_scripts(): void {
        $handle = 'mc-htmx';

        if ($this->use_cdn) {
            wp_register_script(
                $handle,
                $this->cdn_url,
                [],
                $this->version,
                true
            );
        } else {
            wp_register_script(
                $handle,
                plugin_dir_url(MC_PLUGIN_FILE) . 'assets/js/htmx/htmx.min.js',
                [],
                $this->version,
                true
            );
        }

        // Register extensions
        foreach ($this->extensions as $extension => $data) {
            wp_register_script(
                "mc-htmx-ext-{$extension}",
                $data['url'],
                ['mc-htmx'],
                $data['version'],
                true
            );
        }
    }

    /**
     * Enqueue HTMX scripts
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        if ($this->is_enqueued) {
            return;
        }

        wp_enqueue_script('mc-htmx');

        // Enqueue active extensions
        foreach ($this->extensions as $extension => $data) {
            if ($data['active']) {
                wp_enqueue_script("mc-htmx-ext-{$extension}");
            }
        }

        $this->is_enqueued = true;

        // Add HTMX settings to page
        add_action('wp_footer', [$this, 'print_settings'], 20);
    }

    /**
     * Print HTMX settings
     *
     * @return void
     */
    public function print_settings(): void {
        $settings = [
            'historyEnabled' => true,
            'defaultSwapStyle' => 'innerHTML',
            'defaultSwapDelay' => 0,
            'refreshOnHistoryMiss' => false,
        ];

        // Allow filtering of settings
        $settings = apply_filters('mc_htmx_settings', $settings);

        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof htmx !== "undefined") {
                    htmx.config = ' . json_encode($settings) . ';
                    document.body.classList.add("htmx-enabled");
                }
            });
        </script>';
    }

    /**
     * Register an HTMX extension
     *
     * @param string $name    Extension name
     * @param string $url     Extension URL
     * @param string $version Extension version
     * @param bool   $active  Whether to activate the extension by default
     * @return void
     */
    public function register_extension(string $name, string $url, string $version = '1.0.0', bool $active = true): void {
        $this->extensions[$name] = [
            'url' => $url,
            'version' => $version,
            'active' => $active,
        ];
    }

    /**
     * Activate an HTMX extension
     *
     * @param string $name Extension name
     * @return bool Whether the extension was activated
     */
    public function activate_extension(string $name): bool {
        if (!isset($this->extensions[$name])) {
            return false;
        }

        $this->extensions[$name]['active'] = true;
        return true;
    }

    /**
     * Deactivate an HTMX extension
     *
     * @param string $name Extension name
     * @return bool Whether the extension was deactivated
     */
    public function deactivate_extension(string $name): bool {
        if (!isset($this->extensions[$name])) {
            return false;
        }

        $this->extensions[$name]['active'] = false;
        return true;
    }

    /**
     * Get registered extensions
     *
     * @return array
     */
    public function get_extensions(): array {
        return $this->extensions;
    }

    /**
     * Set HTMX version
     *
     * @param string $version HTMX version
     * @return void
     */
    public function set_version(string $version): void {
        $this->version = $version;

        // Update CDN URL
        $this->cdn_url = "https://unpkg.com/htmx.org@{$version}/dist/htmx.min.js";
    }

    /**
     * Get HTMX version
     *
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }

    /**
     * Set whether to use CDN
     *
     * @param bool $use_cdn Whether to use CDN
     * @return void
     */
    public function set_use_cdn(bool $use_cdn): void {
        $this->use_cdn = $use_cdn;
        update_option('mc_htmx_use_cdn', $use_cdn);
    }

    /**
     * Check if using CDN
     *
     * @return bool
     */
    public function is_using_cdn(): bool {
        return $this->use_cdn;
    }
}
