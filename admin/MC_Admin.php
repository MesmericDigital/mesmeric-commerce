<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://beanbagplanet.co.uk
 * @since      1.0.0
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/admin
 */

declare(strict_types=1);

namespace MesmericCommerce\Admin;

use Kucrut\Vite\Vite;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_Admin
 *
 * Handles all admin-specific functionality
 */
class MC_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * The plugin instance.
	 *
	 * @var MC_Plugin
	 */
	private MC_Plugin $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string    $plugin_name The name of this plugin.
	 * @param string    $version     The version of this plugin.
	 * @param MC_Plugin $plugin      The plugin instance.
	 */
	public function __construct( string $plugin_name, string $version, MC_Plugin $plugin ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin = $plugin;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		// Enqueue Vite assets
		Vite::enqueue_asset( plugin_dir_path( __FILE__ ) . 'vite.config.js', 'admin' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Enqueue Vite assets
		Vite::enqueue_asset( plugin_dir_path( __FILE__ ) . 'vite.config.js', 'admin' );
	}

	/**
	 * Add plugin admin menu items.
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu(): void {
		add_menu_page(
			'Mesmeric Commerce',
			'Mesmeric Commerce',
			'manage_options',
			'mesmeric-commerce',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-cart',
			56
		);

		add_submenu_page(
			'mesmeric-commerce',
			'Settings',
			'Settings',
			'manage_options',
			'mesmeric-commerce-settings',
			array( $this, 'display_plugin_settings_page' )
		);

		add_submenu_page(
			'mesmeric-commerce',
			'Logs',
			'Logs',
			'manage_options',
			'mesmeric-commerce-logs',
			array( $this, 'display_plugin_logs_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'mesmeric_commerce_settings',
			'mc_enable_quickview',
			array(
				'type' => 'string',
				'default' => 'yes',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'mesmeric_commerce_settings',
			'mc_enable_wishlist',
			array(
				'type' => 'string',
				'default' => 'yes',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'mesmeric_commerce_settings',
			'mc_enable_inventory',
			array(
				'type' => 'string',
				'default' => 'yes',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'mesmeric_commerce_settings',
			'mc_enable_shipping',
			array(
				'type' => 'string',
				'default' => 'yes',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'mesmeric_commerce_settings',
			'mc_enable_breakdance_admin_menu',
			array(
				'type' => 'string',
				'default' => 'yes',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Display the plugin admin page.
	 *
	 * @return void
	 */
	public function display_plugin_admin_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/mesmeric-commerce-admin-display.php';
	}

	/**
	 * Display the plugin settings page.
	 *
	 * @return void
	 */
	public function display_plugin_settings_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/mesmeric-commerce-settings-display.php';
	}

	/**
	 * Display the plugin logs page.
	 *
	 * @return void
	 */
	public function display_plugin_logs_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/mesmeric-commerce-logs-display.php';
	}

	/**
	 * Check if current page is a plugin admin page.
	 *
	 * @return bool
	 */
	private function is_plugin_admin_page(): bool {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return strpos( $screen->id, 'mesmeric-commerce' ) !== false;
	}
}
