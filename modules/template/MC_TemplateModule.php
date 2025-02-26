<?php
/**
 * Template Module
 *
 * This is a template for creating new modules. Copy this directory and rename it to your module name.
 * Then rename this file to MC_YourModuleNameModule.php and update the class name and namespace.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/template
 */

namespace MesmericCommerce\Modules\Template;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Template Module Class
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/template
 */
class MC_TemplateModule extends MC_AbstractModule {
    /**
     * Initialize the module.
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        try {
            // Register hooks, filters, and actions here
            $loader = $this->get_plugin()->get_loader();

            // Example: Register admin scripts
            // $loader->add_action('admin_enqueue_scripts', $this, 'admin_enqueue_scripts');

            // Example: Register public scripts
            // $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts');

            // Example: Register shortcode
            // add_shortcode('mc_template', [$this, 'render_shortcode']);

            $this->get_logger()->info('Template module initialized successfully');
        } catch (\Throwable $e) {
            $this->get_logger()->error('Failed to initialize template module: ' . $e->getMessage());
        }
    }

    /**
     * Get the module ID.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_module_id(): string {
        return 'template';
    }

    /**
     * Get default settings.
     *
     * @since  1.0.0
     * @return array
     */
    protected function get_default_settings(): array {
        return [
            'enabled' => true,
            // Add your default settings here
        ];
    }

    /**
     * Enqueue scripts for the public-facing side of the site.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_scripts(): void {
        try {
            // Enqueue CSS
            wp_enqueue_style(
                'mc-template',
                plugin_dir_url(__FILE__) . 'css/template.css',
                [],
                $this->get_plugin()->get_version(),
                'all'
            );

            // Enqueue JS
            wp_enqueue_script(
                'mc-template',
                plugin_dir_url(__FILE__) . 'js/template.js',
                ['jquery'],
                $this->get_plugin()->get_version(),
                true
            );

            // Localize script
            wp_localize_script(
                'mc-template',
                'mcTemplateData',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mc-template-nonce'),
                    // Add your data here
                ]
            );
        } catch (\Throwable $e) {
            $this->get_logger()->error('Failed to enqueue template scripts: ' . $e->getMessage());
        }
    }

    /**
     * Enqueue scripts for the admin area.
     *
     * @since  1.0.0
     * @param  string $hook_suffix The current admin page.
     * @return void
     */
    public function admin_enqueue_scripts(string $hook_suffix): void {
        try {
            // Only load on specific admin pages if needed
            // if (strpos($hook_suffix, 'mesmeric-commerce') === false) {
            //     return;
            // }

            // Enqueue admin CSS
            wp_enqueue_style(
                'mc-template-admin',
                plugin_dir_url(__FILE__) . 'css/admin.css',
                [],
                $this->get_plugin()->get_version(),
                'all'
            );

            // Enqueue admin JS
            wp_enqueue_script(
                'mc-template-admin',
                plugin_dir_url(__FILE__) . 'js/admin.js',
                ['jquery'],
                $this->get_plugin()->get_version(),
                true
            );

            // Localize script
            wp_localize_script(
                'mc-template-admin',
                'mcTemplateAdminData',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mc-template-admin-nonce'),
                    // Add your data here
                ]
            );
        } catch (\Throwable $e) {
            $this->get_logger()->error('Failed to enqueue template admin scripts: ' . $e->getMessage());
        }
    }

    /**
     * Render shortcode.
     *
     * @since  1.0.0
     * @param  array  $atts    Shortcode attributes.
     * @param  string $content Shortcode content.
     * @return string
     */
    public function render_shortcode(array $atts = [], string $content = ''): string {
        try {
            // Parse attributes
            $atts = shortcode_atts(
                [
                    'id' => '',
                    'class' => '',
                    // Add your attributes here
                ],
                $atts,
                'mc_template'
            );

            // Start output buffering
            ob_start();

            // Include template
            include plugin_dir_path(__FILE__) . 'views/template.php';

            // Return the buffered content
            return ob_get_clean();
        } catch (\Throwable $e) {
            $this->get_logger()->error('Failed to render template shortcode: ' . $e->getMessage());
            return '<!-- Template shortcode error: ' . esc_html($e->getMessage()) . ' -->';
        }
    }
}
