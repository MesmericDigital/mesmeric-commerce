<?php
/**
 * Mobile Menu Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/MobileMenu
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\MobileMenu;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Mobile Menu Module Class
 *
 * Implements a customizable mobile bottom menu for WooCommerce stores.
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/MobileMenu
 */
class MC_MobileMenuModule extends MC_AbstractModule {
    /**
     * Module identifier.
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private string $module_id = 'mobile_menu';

    /**
     * Initialize the module.
     *
     * @since  1.0.0
     * @return void
     */
    public function init(): void {
        if (!$this->is_active()) {
            return;
        }

        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Add menu to footer
        add_action('wp_footer', [$this, 'render_mobile_menu']);

        // Add admin settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Get the module identifier.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_module_id(): string {
        return $this->module_id;
    }

    /**
     * Get default settings.
     *
     * @since  1.0.0
     * @return array<string, mixed>
     */
    protected function get_default_settings(): array {
        return [
            'enabled' => true,
            'menu_items' => [
                [
                    'type' => 'home',
                    'icon' => 'home',
                    'label' => __('Home', 'mesmeric-commerce'),
                    'url' => home_url(),
                ],
                [
                    'type' => 'shop',
                    'icon' => 'shopping-bag',
                    'label' => __('Shop', 'mesmeric-commerce'),
                    'url' => wc_get_page_permalink('shop'),
                ],
                [
                    'type' => 'cart',
                    'icon' => 'shopping-cart',
                    'label' => __('Cart', 'mesmeric-commerce'),
                    'url' => wc_get_cart_url(),
                ],
                [
                    'type' => 'account',
                    'icon' => 'user',
                    'label' => __('Account', 'mesmeric-commerce'),
                    'url' => wc_get_page_permalink('myaccount'),
                ],
            ],
            'breakpoint' => 'md',
            'position' => 'bottom',
            'theme' => 'light',
        ];
    }

    /**
     * Enqueue module assets.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_assets(): void {
        if (!wp_is_mobile()) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'mc-mobile-menu',
            plugin_dir_url(__FILE__) . 'assets/css/mobile-menu.css',
            [],
            MC_Plugin::VERSION,
            'all'
        );

        // Enqueue scripts
        wp_enqueue_script(
            'mc-mobile-menu',
            plugin_dir_url(__FILE__) . 'assets/js/mobile-menu.js',
            ['alpinejs'],
            MC_Plugin::VERSION,
            true
        );

        // Localize script
        wp_localize_script('mc-mobile-menu', 'mcMobileMenu', [
            'settings' => $this->get_settings(),
            'cart_count' => is_object(WC()->cart) ? WC()->cart->get_cart_contents_count() : 0,
        ]);
    }

    /**
     * Render the mobile menu.
     *
     * @since  1.0.0
     * @return void
     */
    public function render_mobile_menu(): void {
        if (!wp_is_mobile()) {
            return;
        }

        $settings = $this->get_settings();
        $menu_items = $settings['menu_items'];

        // Get Twig template service
        $twig = $this->get_plugin()->get_twig_service();

        // Render menu using Twig template
        echo $twig->render('mobile-menu.twig', [
            'menu_items' => $menu_items,
            'settings' => $settings,
        ]);
    }

    /**
     * Register module settings.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings(): void {
        register_setting(
            'mc_mobile_menu_settings',
            'mc_module_' . $this->get_module_id() . '_settings',
            [
                'type' => 'array',
                'description' => __('Mobile Menu Module Settings', 'mesmeric-commerce'),
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );
    }

    /**
     * Sanitize settings before saving.
     *
     * @since  1.0.0
     * @param  array $settings Settings to sanitize.
     * @return array
     */
    public function sanitize_settings(array $settings): array {
        $defaults = $this->get_default_settings();
        $sanitized = [];

        // Sanitize enabled setting
        $sanitized['enabled'] = isset($settings['enabled']) ? (bool) $settings['enabled'] : $defaults['enabled'];

        // Sanitize menu items
        $sanitized['menu_items'] = [];
        if (isset($settings['menu_items']) && is_array($settings['menu_items'])) {
            foreach ($settings['menu_items'] as $item) {
                if (!isset($item['type'], $item['icon'], $item['label'], $item['url'])) {
                    continue;
                }

                $sanitized['menu_items'][] = [
                    'type' => sanitize_key($item['type']),
                    'icon' => sanitize_key($item['icon']),
                    'label' => sanitize_text_field($item['label']),
                    'url' => esc_url_raw($item['url']),
                ];
            }
        }

        // Sanitize breakpoint
        $valid_breakpoints = ['sm', 'md', 'lg', 'xl', '2xl'];
        $sanitized['breakpoint'] = in_array($settings['breakpoint'] ?? '', $valid_breakpoints, true)
            ? $settings['breakpoint']
            : $defaults['breakpoint'];

        // Sanitize position
        $valid_positions = ['bottom', 'top'];
        $sanitized['position'] = in_array($settings['position'] ?? '', $valid_positions, true)
            ? $settings['position']
            : $defaults['position'];

        // Sanitize theme
        $valid_themes = ['light', 'dark'];
        $sanitized['theme'] = in_array($settings['theme'] ?? '', $valid_themes, true)
            ? $settings['theme']
            : $defaults['theme'];

        return $sanitized;
    }
}
