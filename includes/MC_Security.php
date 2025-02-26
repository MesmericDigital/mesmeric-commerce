<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class MC_Security
 *
 * Handles security-related functionality for the plugin
 *
 * @package MesmericCommerce\Includes
 */
class MC_Security
{
	/**
	 * @var MC_Security|null The singleton instance
	 */
	private static ?MC_Security $instance = null;

	/**
	 * @var MC_Logger|null The logger instance
	 */
	private ?MC_Logger $logger = null;

	/**
	 * MC_Security constructor.
	 */
	private function __construct()
	{
		// Try to get the logger instance if available
		try {
			if (class_exists('MesmericCommerce\Includes\MC_Logger')) {
				$this->logger = MC_Logger::get_instance();
			}
		} catch (\Exception $e) {
			// If logger isn't available, log to error_log
			error_log('Mesmeric Commerce: Unable to initialize logger in Security class: ' . $e->getMessage());
		}
	}

	/**
	 * Get the singleton instance
	 *
	 * @return MC_Security The singleton instance
	 */
	public static function get_instance(): MC_Security
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup hooks
	 */
	public function setup_hooks(): void
	{
		// Force SSL on frontend
		add_action('template_redirect', [$this, 'enforce_frontend_ssl']);

		// Force SSL on admin
		add_action('admin_init', [$this, 'enforce_admin_ssl']);

		// Update site URLs to HTTPS on activation
		add_action('mesmeric_commerce_activated', [$this, 'update_site_urls_to_https'], 10, 2);
	}

	/**
	 * Enforce SSL on frontend
	 */
	public function enforce_frontend_ssl(): void
	{
		if (!is_ssl() && !$this->is_admin_request() && !wp_doing_ajax() && !wp_doing_cron()) {
			$this->log_security_event('Redirecting frontend request to HTTPS');
			$redirect_url = 'https://' . esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) . esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
			wp_safe_redirect($redirect_url, 301);
			exit;
		}
	}

	/**
	 * Enforce SSL on admin
	 */
	public function enforce_admin_ssl(): void
	{
		if (!is_ssl()) {
			$this->log_security_event('Redirecting admin request to HTTPS');
			$redirect_url = 'https://' . esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) . esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
			wp_safe_redirect($redirect_url, 301);
			exit;
		}
	}

	/**
	 * Update site URLs to HTTPS
	 *
	 * @param string $plugin The plugin basename
	 * @param string $nonce  Nonce for verification
	 */
	public function update_site_urls_to_https(string $plugin = '', string $nonce = ''): void
	{
		// Verify this is our plugin and the nonce is valid
		if (!empty($plugin) && $plugin !== MC_PLUGIN_BASENAME) {
			return;
		}

		if (!empty($nonce) && !wp_verify_nonce($nonce, 'mesmeric_commerce_activation')) {
			$this->log_security_event('Invalid nonce provided for site URL update');
			return;
		}

		$this->log_security_event('Updating site URLs to HTTPS');

		$this->migrate_option_to_https('siteurl');
		$this->migrate_option_to_https('home');

		// Clear any caches
		if (function_exists('wp_cache_flush')) {
			wp_cache_flush();
		}
	}

	/**
	 * Migrate an option from HTTP to HTTPS
	 *
	 * @param string $option_name The option name
	 */
	private function migrate_option_to_https(string $option_name): void
	{
		$value = get_option($option_name);

		if ($value && strpos($value, 'http://') === 0) {
			$new_value = str_replace('http://', 'https://', $value);
			update_option($option_name, $new_value);
			$this->log_security_event(sprintf('Updated %s from %s to %s', $option_name, $value, $new_value));
		}
	}

	/**
	 * Check if the current request is an admin request
	 *
	 * @return bool
	 */
	private function is_admin_request(): bool
	{
		return is_admin() || (defined('DOING_AJAX') && DOING_AJAX && strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false);
	}

	/**
	 * Log security event
	 *
	 * @param string $message Message to log
	 * @param array  $context Additional context data
	 * @return void
	 */
	private function log_security_event( string $message, array $context = [] ): void {
		// Add timestamp and IP address to context
		$context['timestamp'] = current_time( 'timestamp' );
		$context['ip'] = $this->get_client_ip();
		$context['user_id'] = get_current_user_id();

		// Format message with context
		$formatted_message = sprintf(
			'[Security] %s | IP: %s | User ID: %d | %s',
			$message,
			$context['ip'],
			$context['user_id'],
			!empty($context['additional']) ? json_encode($context['additional']) : ''
		);

		// Try to use the custom logger if available
		if ( $this->logger instanceof \WC_Logger ) {
			$this->logger->warning( $formatted_message, $context );
		} else {
			// Fall back to WordPress error logging
			$log_dir = MC_PLUGIN_DIR . 'logs';
			$log_file = $log_dir . '/security.log';

			// Ensure log directory exists
			if (!file_exists($log_dir)) {
				wp_mkdir_p($log_dir);

				// Create .htaccess file to protect logs
				file_put_contents(
					$log_dir . '/.htaccess',
					"Order Deny,Allow\nDeny from all"
				);

				// Create web.config file for IIS servers
				file_put_contents(
					$log_dir . '/web.config',
					'<?xml version="1.0" encoding="UTF-8"?>
<configuration>
<system.webServer>
<authorization>
<deny users="*" />
</authorization>
</system.webServer>
</configuration>'
				);

				// Create index.php to prevent directory listing
				file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
			}

			// Log to file with timestamp
			$timestamp = date('Y-m-d H:i:s');
			$log_entry = "[$timestamp] $formatted_message" . PHP_EOL;
			file_put_contents($log_file, $log_entry, FILE_APPEND);

			// Also log to WordPress error log as a fallback
			error_log($formatted_message);
		}

		// Add to database logs for admin viewing
		if (class_exists('\\MesmericCommerce\\Includes\\MC_Database')) {
			\MesmericCommerce\Includes\MC_Database::add_notification_log([
				'type' => 'security',
				'message' => $message,
				'details' => $context
			]);
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private function get_client_ip(): string {
		$ip = '';

		// Check for proxy headers first
		$headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ($headers as $header) {
			if (!empty($_SERVER[$header])) {
				$ip_array = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
				$ip = trim($ip_array[0]);
				break;
			}
		}

		// Validate IP address
		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			return $ip;
		}

		return 'unknown';
	}
}
