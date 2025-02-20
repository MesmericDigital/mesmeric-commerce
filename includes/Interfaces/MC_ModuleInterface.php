<?php
/**
 * Module Interface
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes/interfaces
 */

namespace MesmericCommerce\Includes\Interfaces;

/**
 * Interface MC_ModuleInterface
 *
 * Defines the contract that all Mesmeric Commerce modules must follow.
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes/interfaces
 */
interface MC_ModuleInterface {
	/**
	 * Initialize the module
	 *
	 * This method is called when the module is loaded and should register all hooks,
	 * filters, and other functionality needed by the module.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init();

	/**
	 * Get the module identifier
	 *
	 * This should return a unique string identifier for the module.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_module_id();

	/**
	 * Get module settings
	 *
	 * Returns the current settings for the module.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings();

	/**
	 * Update module settings
	 *
	 * Updates the module settings with new values.
	 *
	 * @since 1.0.0
	 * @param array $settings New settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function update_settings( array $settings );

	/**
	 * Check if module is active
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active();

	/**
	 * Activate the module
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function activate();

	/**
	 * Deactivate the module
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function deactivate();

	/**
	 * Get module dependencies
	 *
	 * Returns an array of module IDs that this module depends on.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_dependencies();
}
