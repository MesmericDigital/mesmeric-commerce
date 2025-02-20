<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Abstracts;

use MesmericCommerce\WooCommerce\Interfaces\MC_WC_Service_Interface;
use MesmericCommerce\WooCommerce\Traits\MC_WC_Caching;

/**
 * Abstract WooCommerce Service Class
 *
 * Base class for all WooCommerce services.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/includes/abstracts
 * @since      1.0.0
 */
abstract class MC_WC_Abstract_Service implements MC_WC_Service_Interface {
    use MC_WC_Caching;

    /**
     * Service name
     *
     * @since 1.0.0
     * @var string
     */
    protected string $service_name;

    /**
     * Service settings
     *
     * @since 1.0.0
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * Default settings
     *
     * @since 1.0.0
     * @var array<string, mixed>
     */
    protected array $default_settings = [];

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->service_name = static::class;
        $this->init_settings();
    }

    /**
     * Initialize settings.
     *
     * @since 1.0.0
     * @return void
     */
    protected function init_settings(): void {
        $saved_settings = get_option('mc_wc_' . $this->get_service_name() . '_settings', []);
        $this->settings = wp_parse_args($saved_settings, $this->default_settings);
    }

    /**
     * {@inheritDoc}
     */
    public function get_service_name(): string {
        return sanitize_title($this->service_name);
    }

    /**
     * {@inheritDoc}
     */
    public function is_enabled(): bool {
        return (bool) ($this->settings['enabled'] ?? true);
    }

    /**
     * {@inheritDoc}
     */
    public function get_settings(): array {
        return $this->settings;
    }

    /**
     * {@inheritDoc}
     */
    public function update_settings(array $settings): bool {
        $this->settings = wp_parse_args($settings, $this->default_settings);
        return update_option('mc_wc_' . $this->get_service_name() . '_settings', $this->settings);
    }

    /**
     * Get setting value.
     *
     * @since 1.0.0
     * @param string $key     Setting key
     * @param mixed  $default Default value
     * @return mixed
     */
    protected function get_setting(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Update single setting.
     *
     * @since 1.0.0
     * @param string $key   Setting key
     * @param mixed  $value Setting value
     * @return bool
     */
    protected function update_setting(string $key, $value): bool {
        $this->settings[$key] = $value;
        return $this->update_settings($this->settings);
    }

    /**
     * Log message to WooCommerce logger.
     *
     * @since 1.0.0
     * @param string $message Message to log
     * @param string $level   Log level (emergency|alert|critical|error|warning|notice|info|debug)
     * @return void
     */
    protected function log(string $message, string $level = 'info'): void {
        if (!$this->get_setting('enable_logging', false)) {
            return;
        }

        $logger = wc_get_logger();
        $context = [
            'source' => 'mesmeric-commerce-' . $this->get_service_name(),
        ];

        $logger->log($level, $message, $context);
    }

    /**
     * Check if debug mode is enabled.
     *
     * @since 1.0.0
     * @return bool
     */
    protected function is_debug(): bool {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
}
