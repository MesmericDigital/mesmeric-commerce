<?php
/**
 * Inventory Management Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/inventory
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Inventory;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_InventoryModule
 *
 * Handles inventory management functionality
 */
class MC_InventoryModule {
	/**
	 * The plugin's instance.
	 *
	 * @var MC_Plugin
	 */
	private MC_Plugin $plugin;

	/**
	 * The logger instance.
	 *
	 * @var MC_Logger
	 */
	private MC_Logger $logger;

	/**
	 * The notifications handler.
	 *
	 * @var MC_InventoryNotifications
	 */
	private MC_InventoryNotifications $notifications;

	/**
	 * The reorder calculator.
	 *
	 * @var MC_ReorderCalculator
	 */
	private MC_ReorderCalculator $reorder_calculator;

	/**
	 * Initialize the module.
	 */
	public function __construct() {
		global $mesmeric_commerce;
		$this->plugin = $mesmeric_commerce;
		$this->logger = $this->plugin->get_logger();

		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Initialize module components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		try {
			$this->notifications      = new MC_InventoryNotifications( $this->plugin );
			$this->reorder_calculator = new MC_ReorderCalculator( $this->plugin );
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf( 'Failed to initialize inventory components: %s', $e->getMessage() ),
				'error',
				true
			);
			throw $e;
		}
	}

	/**
	 * Register module hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'woocommerce_product_set_stock', array( $this, 'handle_stock_update' ), 10, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'handle_stock_update' ), 10, 1 );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'handle_stock_status_update' ), 10, 2 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'handle_stock_status_update' ), 10, 2 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_complete' ), 10, 1 );
		add_action( 'woocommerce_scheduled_sales', array( $this, 'check_low_stock_products' ), 10 );

		// Admin hooks
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_inventory_menu' ) );
			add_action( 'admin_init', array( $this, 'register_inventory_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_mc_update_inventory_settings', array( $this, 'handle_settings_update' ) );
		}
	}

	/**
	 * Handle product stock updates.
	 *
	 * @param \WC_Product $product The product being updated.
	 * @return void
	 */
	public function handle_stock_update( \WC_Product $product ): void {
		try {
			$stock_quantity = $product->get_stock_quantity();
			if ( $stock_quantity === null ) {
				return;
			}

			$low_stock_threshold = $this->get_low_stock_threshold( $product );
			if ( $stock_quantity <= $low_stock_threshold ) {
				$this->notifications->send_low_stock_notification( $product );
			}

			if ( $this->reorder_calculator->should_reorder( $product ) ) {
				$this->notifications->send_reorder_notification( $product );
			}

			$this->logger->log_error(
				sprintf(
					'Stock updated for product #%d. New quantity: %d',
					$product->get_id(),
					$stock_quantity
				),
				'info'
			);
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf(
					'Error handling stock update for product #%d: %s',
					$product->get_id(),
					$e->getMessage()
				),
				'error',
				true
			);
		}
	}

	/**
	 * Handle product stock status updates.
	 *
	 * @param int    $product_id      The product ID.
	 * @param string $stock_status    The new stock status.
	 * @return void
	 */
	public function handle_stock_status_update( int $product_id, string $stock_status ): void {
		try {
			$product = wc_get_product( $product_id );
			if (  ! $product ) {
				return;
			}

			if ( $stock_status === 'outofstock' ) {
				$this->notifications->send_out_of_stock_notification( $product );
			}

			$this->logger->log_error(
				sprintf(
					'Stock status updated for product #%d. New status: %s',
					$product_id,
					$stock_status
				),
				'info'
			);
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf(
					'Error handling stock status update for product #%d: %s',
					$product_id,
					$e->getMessage()
				),
				'error',
				true
			);
		}
	}

	/**
	 * Handle completed orders.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function handle_order_complete( int $order_id ): void {
		try {
			$order = wc_get_order( $order_id );
			if (  ! $order ) {
				return;
			}

			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if (  ! $product || ! $product->managing_stock() ) {
					continue;
				}

				$this->handle_stock_update( $product );
			}

			$this->logger->log_error(
				sprintf( 'Order #%d completed. Stock levels updated.', $order_id ),
				'info'
			);
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf(
					'Error handling order completion for order #%d: %s',
					$order_id,
					$e->getMessage()
				),
				'error',
				true
			);
		}
	}

	/**
	 * Check for low stock products.
	 *
	 * @return void
	 */
	public function check_low_stock_products(): void {
		try {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
			);

			$products = wc_get_products( $args );
			foreach ( $products as $product ) {
				if (  ! $product->managing_stock() ) {
					continue;
				}

				$this->handle_stock_update( $product );
			}

			$this->logger->log_error( 'Low stock check completed.', 'info' );
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf( 'Error checking low stock products: %s', $e->getMessage() ),
				'error',
				true
			);
		}
	}

	/**
	 * Get low stock threshold for a product.
	 *
	 * @param \WC_Product $product The product.
	 * @return int
	 */
	private function get_low_stock_threshold( \WC_Product $product ): int {
		$product_threshold = $product->get_low_stock_amount();
		if ( $product_threshold !== '' ) {
			return (int) $product_threshold;
		}

		return (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
	}

	/**
	 * Add inventory management menu.
	 *
	 * @return void
	 */
	public function add_inventory_menu(): void {
		add_submenu_page(
			'mesmeric-commerce',
			'Inventory',
			'Inventory',
			'manage_woocommerce',
			'mesmeric-commerce-inventory',
			array( $this, 'render_inventory_page' )
		);
	}

	/**
	 * Register inventory settings.
	 *
	 * @return void
	 */
	public function register_inventory_settings(): void {
		register_setting(
			'mc_inventory_settings',
			'mc_inventory_low_threshold',
			array(
				'type'              => 'number',
				'default'           => 5,
				'sanitize_callback' => 'absint',
			)
		);

		register_setting(
			'mc_inventory_settings',
			'mc_inventory_notification_email',
			array(
				'type'              => 'string',
				'default'           => get_option( 'admin_email' ),
				'sanitize_callback' => 'sanitize_email',
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();
		if (  ! $screen || $screen->id !== 'mesmeric-commerce_page_mesmeric-commerce-inventory' ) {
			return;
		}

		wp_enqueue_style(
			'mc-inventory-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/inventory-admin.css',
			array(),
			MC_VERSION
		);

		wp_enqueue_script(
			'mc-inventory-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/inventory-admin.js',
			array( 'jquery' ),
			MC_VERSION,
			true
		);

		wp_localize_script(
			'mc-inventory-admin',
			'mcInventoryData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mc-inventory-settings' ),
			)
		);
	}

	/**
	 * Render inventory management page.
	 *
	 * @return void
	 */
	public function render_inventory_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'views/inventory-page.php';
	}

	/**
	 * Handle inventory settings update.
	 *
	 * @return void
	 */
	public function handle_settings_update(): void {
		try {
			if (  ! check_ajax_referer( 'mc-inventory-settings', 'nonce', false ) ) {
				wp_send_json_error( 'Invalid nonce' );
				return;
			}

			if (  ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
				return;
			}

			$threshold = isset( $_POST['threshold'] ) ? absint( $_POST['threshold'] ) : 5;
			$email     = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

			update_option( 'mc_inventory_low_threshold', $threshold );
			update_option( 'mc_inventory_notification_email', $email );

			wp_send_json_success( 'Settings updated successfully' );
		} catch ( \Throwable $e ) {
			$this->logger->log_error(
				sprintf( 'Error updating inventory settings: %s', $e->getMessage() ),
				'error',
				true
			);
			wp_send_json_error( 'Failed to update settings' );
		}
	}
}
