<?php
namespace MesmericCommerce\Includes;

/**
 * Security handler for Mesmeric Commerce
 */
class MC_Security {
	private static $instance = null;
	private $logger;

	private function __construct() {
		$this->logger = MC_Logger::get_instance();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function setup_hooks() {
		add_action( 'template_redirect', [ $this, 'enforce_frontend_ssl' ] );
		add_action( 'admin_init', [ $this, 'enforce_admin_ssl' ] );
		register_activation_hook( MC_PLUGIN_FILE, [ $this, 'update_site_urls_to_https' ] );
	}

	public function enforce_frontend_ssl() {
		if ( ! is_ssl() ) {
			$this->logger->log_security_event( 'Non-SSL frontend access attempted' );
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit;
		}
	}

	public function enforce_admin_ssl() {
		if ( $this->is_admin_request() && ! is_ssl() ) {
			$this->logger->log_security_event( 'Non-SSL admin access attempted' );
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
			exit;
		}
	}

	public function update_site_urls_to_https() {
		$this->migrate_option_to_https( 'home' );
		$this->migrate_option_to_https( 'siteurl' );
		$this->logger->log_security_event( 'Updated site URLs to HTTPS' );
	}

	private function migrate_option_to_https( $option_name ) {
		$url = get_option( $option_name );
		if ( strpos( $url, 'http://' ) === 0 ) {
			update_option( $option_name, str_replace( 'http://', 'https://', $url ) );
		}
	}

	private function is_admin_request() {
		return ( is_admin() || strpos( $_SERVER['REQUEST_URI'], '/wp-admin' ) !== false );
	}
}
