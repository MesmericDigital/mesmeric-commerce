<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class MC_TwigService
 *
 * Handles Twig template rendering for the plugin
 */
class MC_TwigService {
	private Environment $twig;
	private string $template_directory;

	/**
	 * Initialize Twig environment
	 *
	 * @param string $template_directory Base directory for templates
	 */
	public function __construct( string $template_directory ) {
		$this->template_directory = $template_directory;
		$this->initialize();
	}

	/**
	 * Initialize Twig environment
	 */
	private function initialize(): void {
		$loader = new FilesystemLoader( $this->template_directory );
		$this->twig = new Environment( $loader, [ 
			'cache' => WP_CONTENT_DIR . '/cache/mesmeric-commerce/twig',
			'auto_reload' => true,
			'debug' => WP_DEBUG,
		] );

		// Add WordPress functions as Twig functions
		$this->add_wordpress_functions();
	}

	/**
	 * Add WordPress functions to Twig
	 */
	private function add_wordpress_functions(): void {
		$functions = [ 
			'wp_nonce_field',
			'get_option',
			'esc_html',
			'esc_attr',
			'esc_url',
			'wp_create_nonce',
			'admin_url',
			'plugin_dir_url',
			'is_woocommerce',
		];

		foreach ( $functions as $function ) {
			$this->twig->addFunction(
				new \Twig\TwigFunction( $function, $function )
			);
		}
	}

	/**
	 * Render a template with given context
	 *
	 * @param string $template_name Template name
	 * @param array  $context      Template context
	 * @return string
	 */
	public function render( string $template_name, array $context = [] ): string {
		try {
			return $this->twig->render( $template_name, $context );
		} catch (\Exception $e) {
			error_log( 'Twig rendering error: ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Get the Twig environment
	 *
	 * @return Environment
	 */
	public function get_environment(): Environment {
		return $this->twig;
	}
}
