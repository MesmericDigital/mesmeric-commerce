<?php
/**
 * Wishlist Table Handler
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/wishlist
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Wishlist;

/**
 * Class MC_WishlistTable
 *
 * Handles wishlist database table creation and management
 */
class MC_WishlistTable {
	/**
	 * Get the table name
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'mc_wishlists';
	}

	/**
	 * Get the items table name
	 *
	 * @return string
	 */
	public static function get_items_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'mc_wishlist_items';
	}

	/**
	 * Create the database tables
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Wishlists table
		$sql = 'CREATE TABLE IF NOT EXISTS ' . self::get_table_name() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            visibility VARCHAR(20) NOT NULL DEFAULT 'private',
            share_key VARCHAR(32),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY share_key (share_key)
        ) $charset_collate;";

		// Wishlist items table
		$sql .= 'CREATE TABLE IF NOT EXISTS ' . self::get_items_table_name() . ' (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wishlist_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY wishlist_product (wishlist_id, product_id, variation_id),
            KEY product_id (product_id),
            KEY variation_id (variation_id),
            FOREIGN KEY (wishlist_id) REFERENCES ' . self::get_table_name() . "(id) ON DELETE CASCADE
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the database tables
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::get_items_table_name() );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::get_table_name() );
	}

	/**
	 * Create default wishlist for user
	 *
	 * @param int $user_id User ID
	 * @return int Wishlist ID
	 */
	public static function create_default_wishlist( int $user_id ): int {
		global $wpdb;

		$wpdb->insert(
			self::get_table_name(),
			array(
				'user_id'    => $user_id,
				'name'       => __( 'Default Wishlist', 'mesmeric-commerce' ),
				'visibility' => 'private',
			),
			array( '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get user's wishlists
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public static function get_user_wishlists( int $user_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . ' WHERE user_id = %d ORDER BY created_at DESC',
				$user_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get wishlist by share key
	 *
	 * @param string $share_key Share key
	 * @return array|null
	 */
	public static function get_wishlist_by_share_key( string $share_key ): ?array {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_table_name() . " WHERE share_key = %s AND visibility = 'public'",
				$share_key
			),
			ARRAY_A
		);

		return $result ?: null;
	}

	/**
	 * Get wishlist items
	 *
	 * @param int $wishlist_id Wishlist ID
	 * @return array
	 */
	public static function get_wishlist_items( int $wishlist_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::get_items_table_name() . ' WHERE wishlist_id = %d ORDER BY added_at DESC',
				$wishlist_id
			),
			ARRAY_A
		);
	}
}
