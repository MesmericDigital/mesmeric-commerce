<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Interfaces;

/**
 * WooCommerce Service Interface
 *
 * Interface for all WooCommerce service classes.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/includes/interfaces
 * @since      1.0.0
 */
interface MC_WC_Service_Interface {
    /**
     * Initialize the service.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void;

    /**
     * Get service name.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_service_name(): string;

    /**
     * Check if service is enabled.
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_enabled(): bool;

    /**
     * Get service settings.
     *
     * @since 1.0.0
     * @return array<string, mixed>
     */
    public function get_settings(): array;

    /**
     * Update service settings.
     *
     * @since 1.0.0
     * @param array<string, mixed> $settings New settings
     * @return bool
     */
    public function update_settings(array $settings): bool;
}
