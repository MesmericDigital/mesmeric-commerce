<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Shipping\Includes;

use WC_Shipping_Method;

/**
 * Evri Shipping Method
 *
 * Implements Evri shipping calculations and settings.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/shipping/includes
 * @since      1.0.0
 */
class MC_Evri_Shipping_Method extends WC_Shipping_Method {
    /**
     * Constructor for shipping method.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->id                 = 'evri';
        $this->method_title       = __('Evri Shipping', 'mesmeric-commerce');
        $this->method_description = __('Provides Evri shipping rates and options', 'mesmeric-commerce');

        // Load the form fields
        $this->init_form_fields();

        // Load the settings
        $this->init_settings();

        // Define user set variables
        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title   = $this->get_option('title', __('Evri Shipping', 'mesmeric-commerce'));

        // Actions
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Initialize form fields.
     *
     * @since 1.0.0
     * @return void
     */
    public function init_form_fields(): void {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'mesmeric-commerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Evri Shipping', 'mesmeric-commerce'),
                'default' => 'yes'
            ],
            'title' => [
                'title'       => __('Method Title', 'mesmeric-commerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mesmeric-commerce'),
                'default'     => __('Evri Shipping', 'mesmeric-commerce'),
                'desc_tip'    => true,
            ],
            'base_cost' => [
                'title'       => __('Base Cost', 'mesmeric-commerce'),
                'type'        => 'price',
                'description' => __('Base cost for Evri shipping.', 'mesmeric-commerce'),
                'default'     => '5.00',
                'desc_tip'    => true,
            ],
            'handling_fee' => [
                'title'       => __('Handling Fee', 'mesmeric-commerce'),
                'type'        => 'price',
                'description' => __('Additional handling fee for Evri shipping.', 'mesmeric-commerce'),
                'default'     => '0.00',
                'desc_tip'    => true,
            ],
            'api_key' => [
                'title'       => __('API Key', 'mesmeric-commerce'),
                'type'        => 'password',
                'description' => __('Your Evri API key.', 'mesmeric-commerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'test_mode' => [
                'title'       => __('Test Mode', 'mesmeric-commerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable test mode', 'mesmeric-commerce'),
                'description' => __('Use test API endpoints instead of production.', 'mesmeric-commerce'),
                'default'     => 'no',
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Calculate shipping costs.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package Package information
     * @return void
     */
    public function calculate_shipping($package = []): void {
        $base_cost = (float) $this->get_option('base_cost', 5.00);
        $handling_fee = (float) $this->get_option('handling_fee', 0.00);

        // Calculate total cost
        $total_cost = $base_cost + $handling_fee;

        // Apply any package-specific calculations here
        $total_cost = $this->calculate_package_cost($package, $total_cost);

        $rate = [
            'id'       => $this->id,
            'label'    => $this->title,
            'cost'     => $total_cost,
            'calc_tax' => 'per_item'
        ];

        // Register the rate
        $this->add_rate($rate);
    }

    /**
     * Calculate package-specific costs.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package    Package information
     * @param float               $total_cost Base shipping cost
     * @return float
     */
    protected function calculate_package_cost(array $package, float $total_cost): float {
        // Get package weight
        $weight = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $weight += (float) $item['data']->get_weight() * $item['quantity'];
            }
        }

        // Add weight-based cost
        if ($weight > 2) {
            $total_cost += ($weight - 2) * 1.50; // £1.50 per kg over 2kg
        }

        return $total_cost;
    }

    /**
     * Check if this method is available.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package Package information
     * @return bool
     */
    public function is_available($package = []): bool {
        // Check if shipping is enabled
        if ($this->enabled === 'no') {
            return false;
        }

        // Check if we have all required settings
        if (empty($this->get_option('api_key'))) {
            return false;
        }

        // Check package restrictions
        $weight = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $weight += (float) $item['data']->get_weight() * $item['quantity'];
            }
        }

        // Maximum weight for Evri is 15kg
        if ($weight > 15) {
            return false;
        }

        return true;
    }
}
