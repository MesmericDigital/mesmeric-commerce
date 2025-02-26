<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

use wpdb;

/**
 * Class Database
 *
 * Handles database operations for Mesmeric Commerce plugin
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/includes
 */
class MC_Database {
	/**
	 * Get instance of wpdb
	 *
	 * @return wpdb WordPress database object
	 */
	private static function get_db(): wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Safely get product sales data
	 *
	 * @param int $product_id Product ID
	 * @param string $start_date Start date in Y-m-d format
	 * @return array Sales data
	 */
	public static function get_product_sales_data( int $product_id, string $start_date ): array {
		// Validate inputs
		$product_id = absint($product_id);
		if ($product_id <= 0) {
			return [];
		}

		// Validate date format
		$date_obj = \DateTime::createFromFormat('Y-m-d', $start_date);
		if (!$date_obj || $date_obj->format('Y-m-d') !== $start_date) {
			return [];
		}

		// Use WooCommerce's data store API to get orders
		$args = [
			'status' => ['completed', 'processing'],
			'date_created' => '>=' . strtotime($start_date),
			'return' => 'ids',
			'limit' => -1,
		];

		// Get order IDs
		$order_ids = wc_get_orders($args);

		if (empty($order_ids)) {
			return [];
		}

		// Prepare results array
		$sales_data = [];

		// Process each order
		foreach ($order_ids as $order_id) {
			$order = wc_get_order($order_id);
			if (!$order) {
				continue;
			}

			// Check if this order contains the product we're looking for
			$found = false;
			$quantity = 0;

			foreach ($order->get_items() as $item) {
				if ($item->get_product_id() === $product_id) {
					$found = true;
					$quantity += $item->get_quantity();
				}
			}

			if ($found) {
				$sale_date = $order->get_date_created()->date('Y-m-d');

				// Add to or update the sales data array
				if (isset($sales_data[$sale_date])) {
					$sales_data[$sale_date] += $quantity;
				} else {
					$sales_data[$sale_date] = $quantity;
				}
			}
		}

		// Format the results to match the expected output
		$results = [];
		foreach ($sales_data as $date => $qty) {
			$results[] = (object) [
				'sale_date' => $date,
				'quantity' => $qty
			];
		}

		// Sort by date descending
		usort($results, function($a, $b) {
			return strcmp($b->sale_date, $a->sale_date);
		});

		return $results;
	}

	/**
	 * Safely get product meta value
	 *
	 * @param int    $product_id Product ID
	 * @param string $meta_key   Meta key
	 * @param bool   $single     Whether to return a single value
	 * @return mixed Meta value
	 */
	public static function get_product_meta( int $product_id, string $meta_key, bool $single = true ): mixed {
		return get_post_meta( $product_id, wp_unslash( $meta_key ), $single );
	}

	/**
	 * Safely update product meta value
	 *
	 * @param int    $product_id Product ID
	 * @param string $meta_key   Meta key
	 * @param mixed  $value      Meta value
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure
	 */
	public static function update_product_meta( int $product_id, string $meta_key, mixed $value ): int|bool {
		return update_post_meta( $product_id, wp_unslash( $meta_key ), wp_unslash( $value ) );
	}

	/**
	 * Get notification logs
	 *
	 * @param int $limit Maximum number of logs to return
	 * @return array Array of notification logs
	 */
	public static function get_notification_logs( int $limit = 100 ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mc_notification_logs';

		// Check if table exists, if not create it
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			self::create_notification_logs_table();
		}

		// Get logs from the database
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array($logs) ? $logs : [];
	}

	/**
	 * Safely add notification log
	 *
	 * @param array $log_data Log data to add
	 * @return bool True on success, false on failure
	 */
	public static function add_notification_log( array $log_data ): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mc_notification_logs';

		// Check if table exists, if not create it
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			self::create_notification_logs_table();
		}

		// Validate log data
		$valid_keys = ['type', 'message', 'product_id', 'user_id', 'details'];
		$sanitized_data = [];

		foreach ($valid_keys as $key) {
			if (isset($log_data[$key])) {
				// Sanitize based on the type of data
				if ($key === 'product_id' || $key === 'user_id') {
					$sanitized_data[$key] = absint($log_data[$key]);
				} elseif ($key === 'details' && is_array($log_data[$key])) {
					$sanitized_data[$key] = wp_json_encode($log_data[$key]);
				} else {
					$sanitized_data[$key] = sanitize_text_field($log_data[$key]);
				}
			}
		}

		// Ensure required fields are present
		if (!isset($sanitized_data['type']) || !isset($sanitized_data['message'])) {
			return false;
		}

		// Add timestamp
		$sanitized_data['created_at'] = current_time('mysql', true);

		// Insert into database
		$result = $wpdb->insert($table_name, $sanitized_data);

		return $result !== false;
	}

	/**
	 * Create notification logs table
	 *
	 * @return bool True on success, false on failure
	 */
	private static function create_notification_logs_table(): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mc_notification_logs';
		$charset_collate = $wpdb->get_charset_collate();

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

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	}

	/**
	 * Begin a database transaction
	 *
	 * @return bool True on success, false on failure
	 */
	public static function begin_transaction(): bool {
		return self::get_db()->query( 'START TRANSACTION' );
	}

	/**
	 * Commit a database transaction
	 *
	 * @return bool True on success, false on failure
	 */
	public static function commit(): bool {
		return self::get_db()->query( 'COMMIT' );
	}

	/**
	 * Rollback a database transaction
	 *
	 * @return bool True on success, false on failure
	 */
	public static function rollback(): bool {
		return self::get_db()->query( 'ROLLBACK' );
	}
}
