<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://beanbagplanet.co.uk
 * @since      1.0.0
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/public
 */

declare(strict_types=1);

namespace MesmericCommerce\Frontend;

use Kucrut\Vite\Vite;
use MesmericCommerce\Includes\MC_Logger;

/**
 * Class MC_Public
 *
 * Handles all public-facing functionality
 */
class MC_Public {
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
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		// Enqueue Vite assets
		Vite::enqueue_asset( MC_PLUGIN_DIR . 'vite.config.js', 'main' );
	}

	/**
		* Register the JavaScript for the public-facing side of the site.
		*
		* @return void
		*/
	public function enqueue_scripts(): void {
	// Enqueue Vite assets
	Vite::enqueue_asset( MC_PLUGIN_DIR . 'vite.config.js', 'main' );
	}

	/**
	 * Check if current page is a WooCommerce page.
	 *
	 * @return bool
	 */
	private function is_woocommerce_page(): bool {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}

		return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
	}
}
