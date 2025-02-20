<?php
/**
 * Abstract Module Class
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes/abstract
 */

namespace MesmericCommerce\Includes\Abstract;

use MesmericCommerce\Includes\Interfaces\MC_ModuleInterface;
use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Abstract Module Class
 *
 * Provides common functionality for all Mesmeric Commerce modules.
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes/abstract
 */
abstract class MC_AbstractModule implements MC_ModuleInterface {
    /**
     * The plugin's instance.
     *
     * @since  1.0.0
     * @access protected
     * @var    MC_Plugin
     */
    protected $plugin;

    /**
     * The logger instance.
     *
     * @since  1.0.0
     * @access protected
     * @var    MC_Logger
     */
    protected $logger;

    /**
     * Module settings.
     *
     * @since  1.0.0
     * @access protected
     * @var    array
     */
    protected $settings = [];

    /**
     * Module active state.
     *
     * @since  1.0.0
     * @access protected
     * @var    bool
     */
    protected $active = false;

    /**
     * Initialize the module.
     *
     * @since 1.0.0
     * @param MC_Plugin $plugin The plugin instance.
     */
    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->logger = $plugin->get_logger();
        $this->load_settings();
    }

    /**
     * Get the plugin instance.
     *
     * @since  1.0.0
     * @return MC_Plugin
     */
    protected function get_plugin() {
        return $this->plugin;
    }

    /**
     * Get the logger instance.
     *
     * @since  1.0.0
     * @return MC_Logger
     */
    protected function get_logger() {
        return $this->logger;
    }

    /**
     * Get module settings.
     *
     * @since  1.0.0
     * @return array
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get a specific setting value.
     *
     * @since  1.0.0
     * @param  string $key     Setting key.
     * @param  mixed  $default Default value if setting doesn't exist.
     * @return mixed
     */
    protected function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Update module settings.
     *
     * @since  1.0.0
     * @param  array $settings New settings to save.
     * @return bool True on success, false on failure.
     */
    public function update_settings(array $settings) {
        try {
            $this->settings = wp_parse_args($settings, $this->get_default_settings());
            $this->save_settings();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update module settings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load module settings from database.
     *
     * @since  1.0.0
     * @return void
     */
    protected function load_settings() {
        $saved_settings = get_option('mc_module_' . $this->get_module_id() . '_settings', []);
        $this->settings = wp_parse_args($saved_settings, $this->get_default_settings());
        $this->active = (bool) get_option('mc_module_' . $this->get_module_id() . '_active', false);
    }

    /**
     * Save module settings to database.
     *
     * @since  1.0.0
     * @return bool True on success, false on failure.
     */
    protected function save_settings() {
        return update_option('mc_module_' . $this->get_module_id() . '_settings', $this->settings);
    }

    /**
     * Check if module is active.
     *
     * @since  1.0.0
     * @return bool
     */
    public function is_active() {
        return $this->active;
    }

    /**
     * Activate the module.
     *
     * @since  1.0.0
     * @return bool True on success, false on failure.
     */
    public function activate() {
        try {
            // Check dependencies
            foreach ($this->get_dependencies() as $dependency) {
                $module = $this->plugin->get_module($dependency);
                if (!$module || !$module->is_active()) {
                    throw new \Exception(sprintf(
                        __('Required module "%s" is not active.', 'mesmeric-commerce'),
                        $dependency
                    ));
                }
            }

            // Perform activation
            $this->active = true;
            update_option('mc_module_' . $this->get_module_id() . '_active', true);

            // Log activation
            $this->logger->info(sprintf('Module activated successfully. (module: %s)', $this->get_module_id()));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Failed to activate module: %s (module: %s, error: %s)', $e->getMessage(), $this->get_module_id(), $e));
            return false;
        }
    }

    /**
     * Deactivate the module.
     *
     * @since  1.0.0
     * @return bool True on success, false on failure.
     */
    public function deactivate() {
        try {
            // Check if other modules depend on this one
            foreach ($this->plugin->get_modules() as $module) {
                if ($module->is_active() && in_array($this->get_module_id(), $module->get_dependencies())) {
                    throw new \Exception(sprintf(
                        __('Module "%s" depends on this module.', 'mesmeric-commerce'),
                        $module->get_module_id()
                    ));
                }
            }

            // Perform deactivation
            $this->active = false;
            update_option('mc_module_' . $this->get_module_id() . '_active', false);

            // Log deactivation
            $this->logger->info(sprintf('Module deactivated successfully. (module: %s)', $this->get_module_id()));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Failed to deactivate module: %s (module: %s, error: %s)', $e->getMessage(), $this->get_module_id(), $e));
            return false;
        }
    }

    /**
     * Get module dependencies.
     *
     * @since  1.0.0
     * @return array Array of module identifiers that this module depends on.
     */
    public function get_dependencies() {
        return [];
    }

    /**
     * Get default settings.
     *
     * @since  1.0.0
     * @return array
     */
    abstract protected function get_default_settings();
}
