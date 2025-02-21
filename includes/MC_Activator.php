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
		flush_rewrite_rules();

		// Enable Breakdance Admin Menu module by default
		if (false === get_option('mc_enable_breakdance_admin_menu')) {
			add_option('mc_enable_breakdance_admin_menu', 'yes');
		}
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
}
