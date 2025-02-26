<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class Activator
 *
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/includes
 */
class MC_Activator {
    /**
	 * Activate the plugin.
	 *
	 * Long Description.
	 */
	public static function activate(): void
	{
		self::check_woocommerce();
		self::create_tables();
		self::set_default_options();
		self::create_pages();
		self::schedule_cron_jobs();
		self::ensure_log_directory();
		flush_rewrite_rules();

		// Enable Breakdance Admin Menu module by default
		if (false === get_option('mc_enable_breakdance_admin_menu')) {
			add_option('mc_enable_breakdance_admin_menu', 'yes');
		}

		// Create a nonce for secure activation actions
		$activation_nonce = wp_create_nonce('mesmeric_commerce_activation');

		// Trigger activation action with plugin basename and nonce
		do_action('mesmeric_commerce_activated', MC_PLUGIN_BASENAME, $activation_nonce);
	}

	/**
	 * Check if WooCommerce is active.
	 */
	private static function check_woocommerce(): void
	{
		if (  ! class_exists('WooCommerce')) {
			wp_die(
				esc_html__('Mesmeric Commerce requires WooCommerce to be installed and activated.', 'mesmeric-commerce'),
				esc_html__('Plugin Activation Error', 'mesmeric-commerce'),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create necessary database tables.
	 */
	private static function create_tables(): void
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$tables = array(
			'mc_wishlists'               => "
				CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mc_wishlists (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					user_id bigint(20) NOT NULL,
					name varchar(255) NOT NULL,
					created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					KEY user_id (user_id)
				) $charset_collate;
			",
			'mc_wishlist_items'          => "
				CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mc_wishlist_items (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					wishlist_id bigint(20) NOT NULL,
					product_id bigint(20) NOT NULL,
					variation_id bigint(20) DEFAULT NULL,
					created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					KEY wishlist_id (wishlist_id),
					KEY product_id (product_id)
				) $charset_collate;
			",
			'mc_inventory_notifications' => "
				CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mc_inventory_notifications (
					id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					product_id bigint(20) UNSIGNED NOT NULL,
					user_id bigint(20) UNSIGNED NOT NULL,
					email varchar(100) NOT NULL,
					created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					notification_sent tinyint(1) NOT NULL DEFAULT 0,
					notification_sent_at datetime NULL,
					PRIMARY KEY (id),
					KEY product_id (product_id),
					KEY user_id (user_id),
					KEY email (email),
					KEY notification_sent (notification_sent)
				) $charset_collate;
			",
		);

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ($tables as $sql) {
			dbDelta($sql);
		}

		// Create inventory_alerts table
		$table_name = $wpdb->prefix . 'mc_inventory_alerts';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			threshold int(11) NOT NULL DEFAULT 5,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			last_notification datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY product_id (product_id)
		) $charset_collate;";

		dbDelta($sql);

		// Create notification_logs table
		$table_name = $wpdb->prefix . 'mc_notification_logs';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(50) NOT NULL,
			message text NOT NULL,
			product_id bigint(20) unsigned DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			details longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY type (type),
			KEY product_id (product_id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta($sql);

		// Migrate existing notification logs from options to the new table
		self::migrate_notification_logs();
	}

	/**
	 * Migrate existing notification logs from options to the new table
	 */
	private static function migrate_notification_logs(): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'mc_notification_logs';

		// Check if we have any logs in the options table
		$old_logs = get_option('mc_inventory_notification_logs', []);
		if (empty($old_logs)) {
			return;
		}

		// Migrate each log to the new table
		foreach ($old_logs as $log) {
			if (!is_array($log)) {
				continue;
			}

			// Prepare data for insertion
			$data = [
				'type' => isset($log['type']) ? sanitize_text_field($log['type']) : 'unknown',
				'message' => isset($log['message']) ? sanitize_text_field($log['message']) : '',
				'created_at' => isset($log['timestamp']) ? date('Y-m-d H:i:s', $log['timestamp']) : current_time('mysql', true)
			];

			// Add optional fields if they exist
			if (isset($log['product_id'])) {
				$data['product_id'] = absint($log['product_id']);
			}

			if (isset($log['user_id'])) {
				$data['user_id'] = absint($log['user_id']);
			}

			if (isset($log['details']) && is_array($log['details'])) {
				$data['details'] = wp_json_encode($log['details']);
			}

			// Insert into the new table
			$wpdb->insert($table_name, $data);
		}

		// Delete the old option
		delete_option('mc_inventory_notification_logs');
	}

	/**
	 * Set default options.
	 */
	private static function set_default_options(): void
	{
		$default_options = array(
			'mc_enable_quickview'             => 'yes',
			'mc_enable_wishlist'              => 'yes',
			'mc_enable_shipping'              => 'yes',
			'mc_enable_inventory'             => 'yes',
			'mc_quickview_button_text'        => __('Quick View', 'mesmeric-commerce'),
			'mc_wishlist_page_title'          => __('My Wishlist', 'mesmeric-commerce'),
			'mc_inventory_low_threshold'      => '5',
			'mc_inventory_notification_email' => get_option('admin_email'),
		);

		foreach ($default_options as $option => $value) {
			if (get_option($option) === false) {
				add_option($option, $value);
			}
		}
	}

	/**
	 * Create required pages.
	 */
	private static function create_pages(): void
	{
		$pages = array(
			'wishlist' => array(
				'title'   => __('My Wishlist', 'mesmeric-commerce'),
				'content' => '<!-- wp:shortcode -->[mesmeric_wishlist]<!-- /wp:shortcode -->',
			),
		);

		foreach ($pages as $slug => $page) {
			$page_id = get_option('mc_' . $slug . '_page_id');

			if (  ! $page_id) {
				$page_data = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => get_current_user_id(),
					'post_title'     => $page['title'],
					'post_content'   => $page['content'],
					'post_name'      => $slug,
					'comment_status' => 'closed',
				);

				$page_id = wp_insert_post($page_data);
				add_option('mc_' . $slug . '_page_id', $page_id);
			}
		}
	}

	/**
	 * Schedule cron jobs.
	 */
	private static function schedule_cron_jobs(): void
	{
		$cron_jobs = array(
			'mesmeric_commerce_daily_cron'      => 'daily',
			'mesmeric_commerce_inventory_check' => 'hourly',
		);

		foreach ($cron_jobs as $hook => $recurrence) {
			if (  ! wp_next_scheduled($hook)) {
				wp_schedule_event(time(), $recurrence, $hook);
			}
		}
	}

	/**
	 * Ensure the log directory exists and is protected
	 */
	private static function ensure_log_directory(): void
	{
		$logs_dir = MC_PLUGIN_DIR . 'logs';
		if (!file_exists($logs_dir)) {
			wp_mkdir_p($logs_dir);

			// Create .htaccess file to protect logs
			file_put_contents(
				$logs_dir . '/.htaccess',
				"Order Deny,Allow\nDeny from all"
			);

			// Create web.config file for IIS servers
			file_put_contents(
				$logs_dir . '/web.config',
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
			file_put_contents($logs_dir . '/index.php', '<?php // Silence is golden');
		}
	}
}
