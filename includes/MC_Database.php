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
	 * @param int    $product_id Product ID
	 * @param string $start_date Start date in Y-m-d H:i:s format
	 * @return array Array of daily sales data
	 */
	public static function get_product_sales_data( int $product_id, string $start_date ): array {
		$db = self::get_db();

		$query = $db->prepare(
			"SELECT
                DATE(o.post_date) as sale_date,
                SUM(oim.meta_value) as quantity
            FROM {$db->prefix}wc_order_product_lookup opl
            JOIN {$db->prefix}posts o ON o.ID = opl.order_id
            JOIN {$db->prefix}woocommerce_order_items oi ON oi.order_id = o.ID
            JOIN {$db->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id
            WHERE opl.product_id = %d
            AND o.post_status IN ('wc-completed', 'wc-processing')
            AND o.post_date >= %s
            AND oim.meta_key = '_qty'
            GROUP BY DATE(o.post_date)
            ORDER BY sale_date DESC",
			$product_id,
			$start_date
		);

		$results = $db->get_results( $query );
		if ( ! is_array( $results ) ) {
			return [];
		}

		$sales_data = [];
		foreach ( $results as $row ) {
			if ( isset( $row->sale_date, $row->quantity ) ) {
				$sales_data[ $row->sale_date ] = (int) $row->quantity;
			}
		}

		return $sales_data;
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
	 * Safely get notification logs
	 *
	 * @param int $limit Maximum number of logs to retrieve
	 * @return array Array of notification logs
	 */
	public static function get_notification_logs( int $limit = 100 ): array {
		$logs = get_option( 'mc_inventory_notification_logs', [] );
		return array_slice( (array) $logs, 0, $limit );
	}

	/**
	 * Safely add notification log
	 *
	 * @param array $log_data Log data to add
	 * @return bool True on success, false on failure
	 */
	public static function add_notification_log( array $log_data ): bool {
		$logs = self::get_notification_logs();
		array_unshift( $logs, wp_unslash( $log_data ) );
		$logs = array_slice( $logs, 0, 100 ); // Keep only last 100 logs
		return update_option( 'mc_inventory_notification_logs', $logs );
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
