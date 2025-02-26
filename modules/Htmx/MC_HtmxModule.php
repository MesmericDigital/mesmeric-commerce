<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Htmx;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_HtmxService;
use MesmericCommerce\Includes\MC_TwigService;

/**
 * HTMX Module Class
 *
 * Handles HTMX integration for the Mesmeric Commerce plugin
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/Htmx
 * @since      1.0.0
 */
class MC_HtmxModule extends MC_AbstractModule {
    /**
     * HTMX Service instance
     *
     * @var MC_HtmxService
     */
    private MC_HtmxService $htmx_service;

    /**
     * Admin instance
     *
     * @var MC_HtmxAdmin|null
     */
    private ?MC_HtmxAdmin $admin = null;

    /**
     * Demo controller instance
     *
     * @var MC_HtmxDemoController|null
     */
    private ?MC_HtmxDemoController $demo_controller = null;

    /**
     * Initialize the class
     */
    public function __construct($plugin, $logger) {
        parent::__construct($plugin, $logger);

        // Create HTMX service
        $this->htmx_service = new MC_HtmxService();

        // Register default extensions
        $this->htmx_service->register_extension('json-enc');
        $this->htmx_service->register_extension('loading-states');
        $this->htmx_service->register_extension('class-tools');
        $this->htmx_service->register_extension('ajax-header');
        $this->htmx_service->register_extension('response-targets');
        $this->htmx_service->register_extension('path-deps');
        $this->htmx_service->register_extension('morphdom-swap');
        $this->htmx_service->register_extension('alpine-morph');
        $this->htmx_service->register_extension('debug');

        // Initialize admin
        $this->init_admin();

        // Initialize demo controller
        $this->init_demo_controller();

        // Initialize WP-CLI commands
        $this->init_cli_commands();

        // Register hooks
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);

        // Add REST API support
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Add HTMX headers support
        add_filter('wp_headers', [$this, 'add_htmx_headers']);

        // Add shortcodes
        add_shortcode('mc_htmx', [$this, 'htmx_shortcode']);
        add_shortcode('mc_htmx_demo', [$this, 'htmx_demo_shortcode']);
    }

    /**
     * Initialize admin
     *
     * @return void
     */
    private function init_admin(): void {
        // Get Twig service
        $twig = $this->get_plugin()->get_twig_service();

        // Set Twig template path
        $template_path = plugin_dir_path(MC_PLUGIN_FILE) . 'modules/Htmx/views';
        $twig->get_environment()->getLoader()->addPath($template_path, 'htmx');

        // Create admin instance
        require_once plugin_dir_path(MC_PLUGIN_FILE) . 'modules/Htmx/MC_HtmxAdmin.php';
        $this->admin = new MC_HtmxAdmin($this, $twig);
    }

    /**
     * Initialize demo controller
     *
     * @return void
     */
    private function init_demo_controller(): void {
        require_once plugin_dir_path(MC_PLUGIN_FILE) . 'modules/Htmx/MC_HtmxDemoController.php';
        $this->demo_controller = new MC_HtmxDemoController();
    }

    /**
     * Initialize the module
     */
    public function init() {
        // Register shortcode
        add_shortcode('mc_htmx_demo', [$this, 'htmx_demo_shortcode']);

        // Add HTMX service to plugin
        $this->get_plugin()->set_htmx_service($this->htmx_service);
    }

    /**
     * Initialize WP-CLI commands
     */
    private function init_cli_commands() {
        if (defined('WP_CLI') && WP_CLI) {
            require_once plugin_dir_path(MC_PLUGIN_FILE) . 'modules/Htmx/MC_HtmxCliCommand.php';
        }
    }

    /**
     * Enqueue scripts for frontend
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        $settings = $this->get_settings();

        if (!$settings['enable_frontend']) {
            return;
        }

        $this->htmx_service->enqueue_scripts();

        // Enqueue extensions based on settings
        foreach ($settings['extensions'] as $extension => $enabled) {
            if ($enabled) {
                $this->htmx_service->activate_extension($extension);
            } else {
                $this->htmx_service->deactivate_extension($extension);
            }
        }
    }

    /**
     * Enqueue scripts for admin
     *
     * @param string $hook_suffix The current admin page
     * @return void
     */
    public function admin_enqueue_scripts(string $hook_suffix): void {
        $settings = $this->get_settings();

        if (!$settings['enable_admin']) {
            return;
        }

        // Only load on specific admin pages if configured
        if (!empty($settings['admin_pages']) && !in_array($hook_suffix, $settings['admin_pages'], true)) {
            return;
        }

        $this->htmx_service->enqueue_scripts();

        // Enqueue extensions based on settings
        foreach ($settings['extensions'] as $extension => $enabled) {
            if ($enabled) {
                $this->htmx_service->activate_extension($extension);
            } else {
                $this->htmx_service->deactivate_extension($extension);
            }
        }
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('mesmeric-commerce/v1', '/htmx/version', [
            'methods' => 'GET',
            'callback' => [$this, 'get_htmx_version'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('mesmeric-commerce/v1', '/htmx/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_htmx_settings'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('mesmeric-commerce/v1', '/htmx/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'update_htmx_settings'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Get HTMX version
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function get_htmx_version(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'version' => $this->htmx_service->get_version(),
        ]);
    }

    /**
     * Get HTMX settings
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function get_htmx_settings(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response($this->get_settings());
    }

    /**
     * Update HTMX settings
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function update_htmx_settings(\WP_REST_Request $request): \WP_REST_Response {
        $params = $request->get_params();
        $settings = $this->get_settings();

        // Update settings
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = $value;
            }
        }

        // Save settings
        $this->update_settings($settings);

        // Update HTMX service
        if (isset($params['version'])) {
            $this->htmx_service->set_version($params['version']);
        }

        if (isset($params['use_cdn'])) {
            $this->htmx_service->set_use_cdn((bool) $params['use_cdn']);
        }

        return new \WP_REST_Response($settings);
    }

    /**
     * Add HTMX headers to responses
     *
     * @param array $headers HTTP headers
     * @return array
     */
    public function add_htmx_headers(array $headers): array {
        // Check if request is from HTMX
        if (!isset($_SERVER['HTTP_HX_REQUEST']) || $_SERVER['HTTP_HX_REQUEST'] !== 'true') {
            return $headers;
        }

        // Add HTMX headers
        $headers['Access-Control-Allow-Headers'] = 'HX-Request, HX-Trigger, HX-Target, HX-Current-URL';
        $headers['Access-Control-Expose-Headers'] = 'HX-Push, HX-Redirect, HX-Refresh, HX-Trigger';

        return $headers;
    }

    /**
     * HTMX shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function htmx_shortcode(array $atts = [], string $content = ''): string {
        // Ensure HTMX is loaded
        $this->htmx_service->enqueue_scripts();

        // Parse attributes
        $atts = shortcode_atts([
            'tag' => 'div',
            'get' => '',
            'post' => '',
            'put' => '',
            'delete' => '',
            'patch' => '',
            'target' => '',
            'swap' => '',
            'trigger' => '',
            'indicator' => '',
            'class' => '',
            'id' => '',
        ], $atts);

        // Build HTMX attributes
        $htmx_atts = [];

        if (!empty($atts['get'])) {
            $htmx_atts['hx-get'] = esc_url($atts['get']);
        }

        if (!empty($atts['post'])) {
            $htmx_atts['hx-post'] = esc_url($atts['post']);
        }

        if (!empty($atts['put'])) {
            $htmx_atts['hx-put'] = esc_url($atts['put']);
        }

        if (!empty($atts['delete'])) {
            $htmx_atts['hx-delete'] = esc_url($atts['delete']);
        }

        if (!empty($atts['patch'])) {
            $htmx_atts['hx-patch'] = esc_url($atts['patch']);
        }

        if (!empty($atts['target'])) {
            $htmx_atts['hx-target'] = esc_attr($atts['target']);
        }

        if (!empty($atts['swap'])) {
            $htmx_atts['hx-swap'] = esc_attr($atts['swap']);
        }

        if (!empty($atts['trigger'])) {
            $htmx_atts['hx-trigger'] = esc_attr($atts['trigger']);
        }

        if (!empty($atts['indicator'])) {
            $htmx_atts['hx-indicator'] = esc_attr($atts['indicator']);
        }

        // Build HTML attributes
        $html_atts = [];

        if (!empty($atts['class'])) {
            $html_atts['class'] = esc_attr($atts['class']);
        }

        if (!empty($atts['id'])) {
            $html_atts['id'] = esc_attr($atts['id']);
        }

        // Combine attributes
        $all_atts = array_merge($html_atts, $htmx_atts);
        $attr_string = '';

        foreach ($all_atts as $key => $value) {
            $attr_string .= ' ' . $key . '="' . $value . '"';
        }

        // Build HTML
        $tag = tag_escape($atts['tag']);
        $html = "<{$tag}{$attr_string}>";

        if (!empty($content)) {
            $html .= do_shortcode($content);
        }

        $html .= "</{$tag}>";

        return $html;
    }

    /**
     * HTMX demo shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function htmx_demo_shortcode(array $atts = []): string {
        // Ensure HTMX is loaded
        $this->htmx_service->enqueue_scripts();

        // Get Twig service
        $twig = $this->get_plugin()->get_twig_service();

        // Set Twig template path if not already set
        $template_path = plugin_dir_path(MC_PLUGIN_FILE) . 'modules/Htmx/views';
        $loader = $twig->get_environment()->getLoader();

        if (!$loader->exists('@htmx/htmx-demo.twig')) {
            $loader->addPath($template_path, 'htmx');
        }

        // Render template
        return $twig->render('@htmx/htmx-demo.twig');
    }

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_module_id(): string {
        return 'htmx';
    }

    /**
     * Get default settings
     *
     * @return array
     */
    public function get_default_settings(): array {
        return [
            'version' => '2.0.0',
            'use_cdn' => true,
            'enable_frontend' => true,
            'enable_admin' => false,
            'admin_pages' => [],
            'extensions' => [
                'json-enc' => false,
                'loading-states' => false,
                'client-side-templates' => false,
            ],
        ];
    }
}
