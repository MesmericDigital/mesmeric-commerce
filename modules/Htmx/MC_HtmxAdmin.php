<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Htmx;

use MesmericCommerce\Includes\MC_TwigService;

/**
 * HTMX Admin Class
 *
 * Handles HTMX admin functionality
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/Htmx
 * @since      1.0.0
 */
class MC_HtmxAdmin {
    /**
     * Module instance
     *
     * @var MC_HtmxModule
     */
    private MC_HtmxModule $module;

    /**
     * Twig service
     *
     * @var MC_TwigService
     */
    private MC_TwigService $twig;

    /**
     * Initialize the class
     *
     * @param MC_HtmxModule  $module Module instance
     * @param MC_TwigService $twig   Twig service
     */
    public function __construct(MC_HtmxModule $module, MC_TwigService $twig) {
        $this->module = $module;
        $this->twig = $twig;

        // Register hooks
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_mc_update_htmx_settings', [$this, 'ajax_update_settings']);
    }

    /**
     * Add menu page
     *
     * @return void
     */
    public function add_menu_page(): void {
        add_submenu_page(
            'mesmeric-commerce',
            __('HTMX Settings', 'mesmeric-commerce'),
            __('HTMX', 'mesmeric-commerce'),
            'manage_options',
            'mc-htmx-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function register_settings(): void {
        register_setting(
            'mc_htmx_settings',
            'mc_htmx_settings',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->module->get_default_settings(),
            ]
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $settings Settings to sanitize
     * @return array
     */
    public function sanitize_settings(array $settings): array {
        $defaults = $this->module->get_default_settings();
        $sanitized = [];

        // Sanitize version
        $sanitized['version'] = isset($settings['version']) ? sanitize_text_field($settings['version']) : $defaults['version'];

        // Sanitize boolean settings
        $sanitized['use_cdn'] = isset($settings['use_cdn']) ? (bool) $settings['use_cdn'] : $defaults['use_cdn'];
        $sanitized['enable_frontend'] = isset($settings['enable_frontend']) ? (bool) $settings['enable_frontend'] : $defaults['enable_frontend'];
        $sanitized['enable_admin'] = isset($settings['enable_admin']) ? (bool) $settings['enable_admin'] : $defaults['enable_admin'];

        // Sanitize admin pages
        $sanitized['admin_pages'] = isset($settings['admin_pages']) && is_array($settings['admin_pages'])
            ? array_map('sanitize_text_field', $settings['admin_pages'])
            : $defaults['admin_pages'];

        // Sanitize extensions
        $sanitized['extensions'] = [];

        foreach ($defaults['extensions'] as $extension => $enabled) {
            $sanitized['extensions'][$extension] = isset($settings['extensions'][$extension])
                ? (bool) $settings['extensions'][$extension]
                : $enabled;
        }

        return $sanitized;
    }

    /**
     * AJAX update settings
     *
     * @return void
     */
    public function ajax_update_settings(): void {
        // Check nonce
        check_ajax_referer('mc_htmx_settings', 'mc_htmx_nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to update settings.', 'mesmeric-commerce'),
            ]);
            return;
        }

        // Get settings
        $settings_json = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';

        if (empty($settings_json)) {
            wp_send_json_error([
                'message' => __('No settings provided.', 'mesmeric-commerce'),
            ]);
            return;
        }

        // Decode settings
        $settings = json_decode($settings_json, true);

        if (!is_array($settings)) {
            wp_send_json_error([
                'message' => __('Invalid settings format.', 'mesmeric-commerce'),
            ]);
            return;
        }

        // Sanitize settings
        $sanitized = $this->sanitize_settings($settings);

        // Update settings
        $this->module->update_settings($sanitized);

        // Send response
        wp_send_json_success([
            'message' => __('Settings saved successfully.', 'mesmeric-commerce'),
            'settings' => $sanitized,
        ]);
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'mesmeric-commerce'));
        }

        // Get settings
        $settings = $this->module->get_settings();

        // Render template
        echo $this->twig->render('admin-settings.twig', [
            'settings' => $settings,
        ]);
    }
}
