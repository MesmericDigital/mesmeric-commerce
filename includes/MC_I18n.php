<?php
declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class I18n
 *
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/includes
 */
class MC_I18n {
	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'mesmeric-commerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
