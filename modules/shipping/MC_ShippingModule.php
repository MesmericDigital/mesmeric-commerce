<?php
/**
 * Shipping Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/shipping
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Shipping;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_ShippingModule
 *
 * Handles enhanced shipping functionality
 */
class MC_ShippingModule {
    /**
     * The plugin's instance.
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * The logger instance.
     *
     * @var MC_Logger
     */
    private MC_Logger $logger;

    /**
     * Initialize the module.
     */
    public function __construct() {
        global $mesmeric_commerce;
        $this->plugin = $mesmeric_commerce;
        $this->logger = $this->plugin->get_logger();

        $this->register_hooks();
    }

    /**
     * Register module hooks.
     *
     * @return void
     */
    private function register_hooks(): void {
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_methods'));
        add_action('woocommerce_shipping_init', array($this, 'init_shipping_methods'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_shipping_fees'));
        add_filter('woocommerce_package_rates', array($this, 'modify_shipping_rates'), 10, 2);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_shipping_menu'));
            add_action('admin_init', array($this, 'register_shipping_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('wp_ajax_mc_save_shipping_zone', array($this, 'handle_save_shipping_zone'));
            add_action('wp_ajax_mc_delete_shipping_zone', array($this, 'handle_delete_shipping_zone'));
            add_action('wp_ajax_mc_save_shipping_rule', array($this, 'handle_save_shipping_rule'));
            add_action('wp_ajax_mc_delete_shipping_rule', array($this, 'handle_delete_shipping_rule'));
        }
    }

    /**
     * Add custom shipping methods.
     *
     * @param array $methods Shipping methods.
     * @return array
     */
    public function add_shipping_methods(array $methods): array {
        try {
            $methods['mc_shipping'] = MC_ShippingMethod::class;
            $this->logger->log_error('Custom shipping methods added', 'info');
            return $methods;
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error adding shipping methods: %s', $e->getMessage()),
                'error',
                true
            );
            return $methods;
        }
    }

    /**
     * Initialize shipping methods.
     *
     * @return void
     */
    public function init_shipping_methods(): void {
        try {
            require_once __DIR__ . '/MC_ShippingMethod.php';
            $this->logger->log_error('Shipping methods initialized', 'info');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error initializing shipping methods: %s', $e->getMessage()),
                'error',
                true
            );
        }
    }

    /**
     * Calculate shipping fees.
     *
     * @param \WC_Cart $cart Cart object.
     * @return void
     */
    public function calculate_shipping_fees(\WC_Cart $cart): void {
        try {
            $handling_fee = (float) get_option('mc_shipping_handling_fee', 0);
            if ($handling_fee > 0) {
                $cart->add_fee(__('Handling Fee', 'mesmeric-commerce'), $handling_fee);
            }

            $this->logger->log_error('Shipping fees calculated', 'info');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error calculating shipping fees: %s', $e->getMessage()),
                'error',
                true
            );
        }
    }

    /**
     * Modify shipping rates.
     *
     * @param array $rates   Shipping rates.
     * @param array $package Shipping package.
     * @return array
     */
    public function modify_shipping_rates(array $rates, array $package): array {
        try {
            $rules = $this->get_shipping_rules();
            foreach ($rates as $rate_id => $rate) {
                foreach ($rules as $rule) {
                    if ($this->should_apply_rule($rule, $package)) {
                        $rates[$rate_id]->cost = $this->apply_rule($rule, $rates[$rate_id]->cost);
                    }
                }
            }

            $this->logger->log_error('Shipping rates modified', 'info');
            return $rates;
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error modifying shipping rates: %s', $e->getMessage()),
                'error',
                true
            );
            return $rates;
        }
    }

    /**
     * Get shipping rules.
     *
     * @return array
     */
    private function get_shipping_rules(): array {
        $rules = get_option('mc_shipping_rules', array());
        return is_array($rules) ? $rules : array();
    }

    /**
     * Check if a rule should be applied.
     *
     * @param array $rule    Shipping rule.
     * @param array $package Shipping package.
     * @return bool
     */
    private function should_apply_rule(array $rule, array $package): bool {
        if (!isset($rule['conditions'])) {
            return true;
        }

        foreach ($rule['conditions'] as $condition) {
            if (!$this->check_condition($condition, $package)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check a shipping condition.
     *
     * @param array $condition Condition data.
     * @param array $package   Package data.
     * @return bool
     */
    private function check_condition(array $condition, array $package): bool {
        if (!isset($condition['type'], $condition['operator'], $condition['value'])) {
            return false;
        }

        $actual_value = $this->get_condition_value($condition['type'], $package);
        $test_value = (float) $condition['value'];

        return match ($condition['operator']) {
            '==' => $actual_value === $test_value,
            '!=' => $actual_value !== $test_value,
            '>' => $actual_value > $test_value,
            '>=' => $actual_value >= $test_value,
            '<' => $actual_value < $test_value,
            '<=' => $actual_value <= $test_value,
            default => false,
        };
    }

    /**
     * Get value for condition checking.
     *
     * @param string $type    Condition type.
     * @param array  $package Package data.
     * @return float
     */
    private function get_condition_value(string $type, array $package): float {
        return match ($type) {
            'weight' => $this->get_package_weight($package),
            'items' => $this->get_package_items($package),
            'subtotal' => $this->get_package_subtotal($package),
            default => 0,
        };
    }

    /**
     * Get package weight.
     *
     * @param array $package Package data.
     * @return float
     */
    private function get_package_weight(array $package): float {
        $weight = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $weight += (float) $item['data']->get_weight() * $item['quantity'];
            }
        }
        return $weight;
    }

    /**
     * Get package item count.
     *
     * @param array $package Package data.
     * @return float
     */
    private function get_package_items(array $package): float {
        $items = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $items += $item['quantity'];
            }
        }
        return (float) $items;
    }

    /**
     * Get package subtotal.
     *
     * @param array $package Package data.
     * @return float
     */
    private function get_package_subtotal(array $package): float {
        $subtotal = 0;
        foreach ($package['contents'] as $item) {
            $subtotal += (float) $item['data']->get_price() * $item['quantity'];
        }
        return $subtotal;
    }

    /**
     * Apply shipping rule.
     *
     * @param array $rule Shipping rule.
     * @param float $cost Current cost.
     * @return float
     */
    private function apply_rule(array $rule, float $cost): float {
        if (!isset($rule['action'], $rule['amount'])) {
            return $cost;
        }

        return match ($rule['action']) {
            'add' => $cost + (float) $rule['amount'],
            'subtract' => max(0, $cost - (float) $rule['amount']),
            'multiply' => $cost * (float) $rule['amount'],
            'set' => (float) $rule['amount'],
            default => $cost,
        };
    }

    /**
     * Add shipping management menu.
     *
     * @return void
     */
    public function add_shipping_menu(): void {
        add_submenu_page(
            'mesmeric-commerce',
            'Shipping',
            'Shipping',
            'manage_woocommerce',
            'mesmeric-commerce-shipping',
            array($this, 'render_shipping_page')
        );
    }

    /**
     * Register shipping settings.
     *
     * @return void
     */
    public function register_shipping_settings(): void {
        register_setting(
            'mc_shipping_settings',
            'mc_shipping_handling_fee',
            array(
                'type' => 'number',
                'default' => 0,
                'sanitize_callback' => 'floatval',
            )
        );

        register_setting(
            'mc_shipping_settings',
            'mc_shipping_rules',
            array(
                'type' => 'array',
                'default' => array(),
                'sanitize_callback' => array($this, 'sanitize_shipping_rules'),
            )
        );
    }

    /**
     * Sanitize shipping rules.
     *
     * @param array $rules Shipping rules.
     * @return array
     */
    public function sanitize_shipping_rules(array $rules): array {
        return array_map(function($rule) {
            return array(
                'name' => sanitize_text_field($rule['name'] ?? ''),
                'conditions' => array_map(function($condition) {
                    return array(
                        'type' => sanitize_key($condition['type'] ?? ''),
                        'operator' => sanitize_key($condition['operator'] ?? ''),
                        'value' => floatval($condition['value'] ?? 0),
                    );
                }, $rule['conditions'] ?? array()),
                'action' => sanitize_key($rule['action'] ?? ''),
                'amount' => floatval($rule['amount'] ?? 0),
            );
        }, $rules);
    }

    /**
     * Enqueue admin assets.
     *
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'mesmeric-commerce_page_mesmeric-commerce-shipping') {
            return;
        }

        wp_enqueue_style(
            'mc-shipping-admin',
            plugin_dir_url(__FILE__) . 'assets/css/shipping-admin.css',
            array(),
            MC_VERSION
        );

        wp_enqueue_script(
            'mc-shipping-admin',
            plugin_dir_url(__FILE__) . 'assets/js/shipping-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            MC_VERSION,
            true
        );

        wp_localize_script(
            'mc-shipping-admin',
            'mcShippingData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mc-shipping-admin'),
                'rules' => $this->get_shipping_rules(),
            )
        );
    }

    /**
     * Handle saving shipping zone.
     *
     * @return void
     */
    public function handle_save_shipping_zone(): void {
        try {
            if (!check_ajax_referer('mc-shipping-admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $zone_data = isset($_POST['zone']) ? json_decode(wp_unslash($_POST['zone']), true) : null;
            if (!is_array($zone_data)) {
                wp_send_json_error('Invalid zone data');
                return;
            }

            $zone_id = absint($zone_data['id'] ?? 0);
            $zone = new \WC_Shipping_Zone($zone_id);

            if (isset($zone_data['name'])) {
                $zone->set_zone_name(sanitize_text_field($zone_data['name']));
            }

            if (isset($zone_data['locations'])) {
                $zone->set_locations($zone_data['locations']);
            }

            $zone->save();

            wp_send_json_success('Zone saved successfully');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error saving shipping zone: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error('Error saving zone');
        }
    }

    /**
     * Handle deleting shipping zone.
     *
     * @return void
     */
    public function handle_delete_shipping_zone(): void {
        try {
            if (!check_ajax_referer('mc-shipping-admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $zone_id = isset($_POST['zone_id']) ? absint($_POST['zone_id']) : 0;
            if ($zone_id === 0) {
                wp_send_json_error('Invalid zone ID');
                return;
            }

            \WC_Shipping_Zones::delete_zone($zone_id);
            wp_send_json_success('Zone deleted successfully');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error deleting shipping zone: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error('Error deleting zone');
        }
    }

    /**
     * Handle saving shipping rule.
     *
     * @return void
     */
    public function handle_save_shipping_rule(): void {
        try {
            if (!check_ajax_referer('mc-shipping-admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $rule_data = isset($_POST['rule']) ? json_decode(wp_unslash($_POST['rule']), true) : null;
            if (!is_array($rule_data)) {
                wp_send_json_error('Invalid rule data');
                return;
            }

            $rules = $this->get_shipping_rules();
            $rule_id = sanitize_key($rule_data['id'] ?? '');

            if (empty($rule_id)) {
                $rule_id = uniqid('rule_', true);
                $rule_data['id'] = $rule_id;
                $rules[] = $rule_data;
            } else {
                $rules = array_map(function($rule) use ($rule_id, $rule_data) {
                    return $rule['id'] === $rule_id ? $rule_data : $rule;
                }, $rules);
            }

            update_option('mc_shipping_rules', $this->sanitize_shipping_rules($rules));
            wp_send_json_success('Rule saved successfully');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error saving shipping rule: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error('Error saving rule');
        }
    }

    /**
     * Handle deleting shipping rule.
     *
     * @return void
     */
    public function handle_delete_shipping_rule(): void {
        try {
            if (!check_ajax_referer('mc-shipping-admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $rule_id = isset($_POST['rule_id']) ? sanitize_key($_POST['rule_id']) : '';
            if (empty($rule_id)) {
                wp_send_json_error('Invalid rule ID');
                return;
            }

            $rules = $this->get_shipping_rules();
            $rules = array_filter($rules, function($rule) use ($rule_id) {
                return $rule['id'] !== $rule_id;
            });

            update_option('mc_shipping_rules', $rules);
            wp_send_json_success('Rule deleted successfully');
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error deleting shipping rule: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error('Error deleting rule');
        }
    }

    /**
     * Render shipping management page.
     *
     * @return void
     */
    public function render_shipping_page(): void {
        require_once plugin_dir_path(__FILE__) . 'views/shipping-page.php';
    }
}
