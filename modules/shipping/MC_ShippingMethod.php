<?php
/**
 * Custom Shipping Method
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/shipping
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Shipping;

use MesmericCommerce\Includes\MC_Logger;

/**
 * Class MC_ShippingMethod
 *
 * Custom shipping method implementation
 */
class MC_ShippingMethod extends \WC_Shipping_Method {
    /**
     * The logger instance.
     *
     * @var MC_Logger
     */
    private MC_Logger $logger;

    /**
     * Base shipping cost
     *
     * @var string
     */
    protected string $cost;

    /**
     * Calculation type
     *
     * @var string
     */
    protected string $type;

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct(int $instance_id = 0) {
        parent::__construct($instance_id);

        $this->id = 'mc_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Mesmeric Shipping', 'mesmeric-commerce');
        $this->method_description = __('Custom shipping method with advanced rules and calculations.', 'mesmeric-commerce');
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
        $this->logger = new MC_Logger();
    }

    /**
     * Initialize shipping method settings
     */
    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->method_title);
        $this->tax_status = $this->get_option('tax_status');
        $this->cost = $this->get_option('cost');
        $this->type = $this->get_option('type', 'class');

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields(): void {
        $this->instance_form_fields = array(
            'title' => array(
                'title' => __('Method Title', 'mesmeric-commerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mesmeric-commerce'),
                'default' => $this->method_title,
                'desc_tip' => true,
            ),
            'tax_status' => array(
                'title' => __('Tax Status', 'mesmeric-commerce'),
                'type' => 'select',
                'description' => __('Choose whether or not to apply taxes to shipping.', 'mesmeric-commerce'),
                'default' => 'taxable',
                'options' => array(
                    'taxable' => __('Taxable', 'mesmeric-commerce'),
                    'none' => __('None', 'mesmeric-commerce'),
                ),
                'desc_tip' => true,
            ),
            'cost' => array(
                'title' => __('Base Cost', 'mesmeric-commerce'),
                'type' => 'price',
                'description' => __('Base shipping cost before rules are applied.', 'mesmeric-commerce'),
                'default' => '0',
                'desc_tip' => true,
            ),
            'type' => array(
                'title' => __('Calculation Type', 'mesmeric-commerce'),
                'type' => 'select',
                'description' => __('Choose how to calculate shipping costs.', 'mesmeric-commerce'),
                'default' => 'class',
                'options' => array(
                    'class' => __('Based on shipping class', 'mesmeric-commerce'),
                    'weight' => __('Based on weight', 'mesmeric-commerce'),
                    'item' => __('Based on item count', 'mesmeric-commerce'),
                    'price' => __('Based on price', 'mesmeric-commerce'),
                ),
                'desc_tip' => true,
            ),
            'rules' => array(
                'title' => __('Shipping Rules', 'mesmeric-commerce'),
                'type' => 'rules_table',
                'description' => __('Define custom shipping rules.', 'mesmeric-commerce'),
                'default' => array(),
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Calculate shipping cost
     *
     * @param array $package Package data.
     */
    public function calculate_shipping($package = array()): void {
        try {
            $cost = (float) $this->cost;
            $rules = $this->get_option('rules', array());

            // Apply rules based on calculation type
            switch ($this->type) {
                case 'weight':
                    $cost += $this->calculate_weight_based_cost($package, $rules);
                    break;
                case 'item':
                    $cost += $this->calculate_item_based_cost($package, $rules);
                    break;
                case 'price':
                    $cost += $this->calculate_price_based_cost($package, $rules);
                    break;
                case 'class':
                default:
                    $cost += $this->calculate_class_based_cost($package, $rules);
                    break;
            }

            $rate = array(
                'id' => $this->get_rate_id(),
                'label' => $this->title,
                'cost' => $cost,
                'package' => $package,
            );

            $this->add_rate($rate);

            $this->logger->log_error(
                sprintf(
                    'Shipping calculated for package. Type: %s, Cost: %f',
                    $this->type,
                    $cost
                ),
                'info'
            );
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error calculating shipping: %s', $e->getMessage()),
                'error',
                true
            );
        }
    }

    /**
     * Calculate weight-based cost
     *
     * @param array $package Package data.
     * @param array $rules   Shipping rules.
     * @return float
     */
    private function calculate_weight_based_cost(array $package, array $rules): float {
        $weight = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $weight += (float) $item['data']->get_weight() * $item['quantity'];
            }
        }

        return $this->apply_rules($weight, $rules);
    }

    /**
     * Calculate item-based cost
     *
     * @param array $package Package data.
     * @param array $rules   Shipping rules.
     * @return float
     */
    private function calculate_item_based_cost(array $package, array $rules): float {
        $items = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $items += $item['quantity'];
            }
        }

        return $this->apply_rules($items, $rules);
    }

    /**
     * Calculate price-based cost
     *
     * @param array $package Package data.
     * @param array $rules   Shipping rules.
     * @return float
     */
    private function calculate_price_based_cost(array $package, array $rules): float {
        $price = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $price += (float) $item['data']->get_price() * $item['quantity'];
            }
        }

        return $this->apply_rules($price, $rules);
    }

    /**
     * Calculate class-based cost
     *
     * @param array $package Package data.
     * @param array $rules   Shipping rules.
     * @return float
     */
    private function calculate_class_based_cost(array $package, array $rules): float {
        $cost = 0;
        $found_classes = array();

        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $class_id = $item['data']->get_shipping_class_id();
                if (!isset($found_classes[$class_id])) {
                    $found_classes[$class_id] = 0;
                }
                $found_classes[$class_id] += $item['quantity'];
            }
        }

        foreach ($found_classes as $class_id => $quantity) {
            $class_rules = array_filter($rules, function($rule) use ($class_id) {
                return isset($rule['class_id']) && $rule['class_id'] === $class_id;
            });
            $cost += $this->apply_rules($quantity, $class_rules);
        }

        return $cost;
    }

    /**
     * Apply shipping rules
     *
     * @param float $value Value to check against rules.
     * @param array $rules Shipping rules.
     * @return float
     */
    private function apply_rules(float $value, array $rules): float {
        $cost = 0;

        foreach ($rules as $rule) {
            if (!isset($rule['min'], $rule['max'], $rule['cost'])) {
                continue;
            }

            if ($value >= $rule['min'] && $value <= $rule['max']) {
                $cost += (float) $rule['cost'];
            }
        }

        return $cost;
    }
}
