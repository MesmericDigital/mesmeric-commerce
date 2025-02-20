<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Services;

use MesmericCommerce\WooCommerce\Abstracts\MC_WC_Abstract_Service;

/**
 * Evri Shipping Service
 *
 * Handles Evri shipping method registration and functionality.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/services
 * @since      1.0.0
 */
class MC_Evri_Shipping_Service extends MC_WC_Abstract_Service {
    /**
     * Initialize the service.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        if (!$this->is_enabled()) {
            return;
        }

        // Register shipping method
        add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_method']);
    }

    /**
     * Register Evri shipping method.
     *
     * @since 1.0.0
     * @param array<string, string> $methods Shipping methods
     * @return array<string, string>
     */
    public function register_shipping_method(array $methods): array {
        $methods['evri'] = MC_Evri_Shipping_Method::class;
        return $methods;
    }
}
