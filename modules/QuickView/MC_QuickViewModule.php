<?php
/**
 * Quick View Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/quickview
 */

namespace MesmericCommerce\Modules\QuickView;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Logger;
use WC_Product;
use WC_AJAX;

/**
 * Quick View Module Class
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/quickview
 */
class MC_QuickViewModule extends MC_AbstractModule {
	/**
	 * Cache expiration time in seconds
	 */
	const CACHE_EXPIRATION = 3600; // 1 hour

	/**
	 * Initialize the module
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Register hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_modal_container' ) );

		// AJAX handlers
		add_action( 'wp_ajax_mc_load_quick_view', array( $this, 'ajax_load_quick_view' ) );
		add_action( 'wp_ajax_nopriv_mc_load_quick_view', array( $this, 'ajax_load_quick_view' ) );
		add_action( 'wp_ajax_mc_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_mc_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_mc_save_for_later', array( $this, 'ajax_save_for_later' ) );
		add_action( 'wp_ajax_nopriv_mc_save_for_later', array( $this, 'ajax_save_for_later' ) );

		// Product classes
		add_filter( 'woocommerce_post_class', array( $this, 'add_product_classes' ), 10, 2 );
	}

	/**
	 * Get module identifier
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_module_id() {
		return 'quickview';
	}

	/**
	 * Get default settings
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_default_settings() {
		return array(
			'enable_cache' => true,
			'cache_expiration' => self::CACHE_EXPIRATION,
			'enable_gallery' => true,
			'enable_zoom' => true,
			'enable_share' => true,
			'enable_wishlist' => true,
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Styles
		wp_enqueue_style(
			'mc-quick-view',
			plugin_dir_url( __FILE__ ) . 'assets/css/quick-view.css',
			array( 'woocommerce-general' ),
			$this->get_plugin()->get_version()
		);

		// Scripts
		wp_enqueue_script(
			'mc-quick-view',
			plugin_dir_url( __FILE__ ) . 'assets/js/quick-view.js',
			array( 'jquery', 'wc-add-to-cart', 'alpinejs' ),
			$this->get_plugin()->get_version(),
			true
		);

		// Localize script
		wp_localize_script(
			'mc-quick-view',
			'mcQuickView',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'mc-quick-view' ),
				'i18n' => array(
					'error' => __( 'An error occurred. Please try again.', 'mesmeric-commerce' ),
					'addToCart' => __( 'Add to Cart', 'mesmeric-commerce' ),
					'adding' => __( 'Adding...', 'mesmeric-commerce' ),
					'added' => __( 'Added to cart', 'mesmeric-commerce' ),
					'saveForLater' => __( 'Save for Later', 'mesmeric-commerce' ),
					'saving' => __( 'Saving...', 'mesmeric-commerce' ),
					'saved' => __( 'Saved to Wishlist', 'mesmeric-commerce' ),
				),
			)
		);
	}

	/**
	 * Render modal container in footer
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_modal_container() {
		?>
		<div x-data="mcQuickView" x-show="open" x-cloak class="mc-quickview-modal" role="dialog" aria-modal="true">
			<div x-show="open" x-transition:enter="mc-quickview-backdrop-enter" @click="close" class="mc-quickview-backdrop">
			</div>

			<div class="mc-quickview-dialog">
				<div x-show="open" x-transition:enter="mc-quickview-enter" class="mc-quickview-content" @click.stop
					x-html="content"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for loading quick view content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_load_quick_view() {
		check_ajax_referer( 'mc-quick-view', 'nonce' );

		$product_id = absint( $_POST['product_id'] );
		if ( ! $product_id ) {
			wp_send_json_error( __( 'Invalid product ID.', 'mesmeric-commerce' ) );
		}

		try {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				throw new \Exception( __( 'Product not found.', 'mesmeric-commerce' ) );
			}

			// Check cache if enabled
			$cache_key = 'mc_quick_view_' . $product_id;
			$html = $this->get_setting( 'enable_cache' ) ? get_transient( $cache_key ) : false;

			if ( false === $html ) {
				ob_start();
				include plugin_dir_path( __FILE__ ) . 'views/content-quick-view.php';
				$html = ob_get_clean();

				if ( $this->get_setting( 'enable_cache' ) ) {
					set_transient( $cache_key, $html, $this->get_setting( 'cache_expiration' ) );
				}
			}

			wp_send_json_success(
				array(
					'html' => $html,
				)
			);
		} catch (\Exception $e) {
			$this->get_logger()->error('Quick view load error: ' . $e->getMessage());
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * AJAX handler for adding to cart
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_add_to_cart() {
		check_ajax_referer( 'mc-quick-view', 'nonce' );

		$product_id = absint( $_POST['product_id'] );
		$quantity = absint( $_POST['quantity'] );

		if ( ! $product_id ) {
			wp_send_json_error( __( 'Invalid product ID.', 'mesmeric-commerce' ) );
		}

		try {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				throw new \Exception( __( 'Product not found.', 'mesmeric-commerce' ) );
			}

			if ( ! $product->is_purchasable() ) {
				throw new \Exception( __( 'This product cannot be purchased.', 'mesmeric-commerce' ) );
			}

			if ( ! $product->is_in_stock() ) {
				throw new \Exception( __( 'This product is out of stock.', 'mesmeric-commerce' ) );
			}

			$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );
			if ( ! $cart_item_key ) {
				throw new \Exception( __( 'Error adding product to cart.', 'mesmeric-commerce' ) );
			}

			WC_AJAX::get_refreshed_fragments();
		} catch (\Exception $e) {
		$this->get_logger()->error( sprintf(
		 'Add to cart error: %s (product_id: %d, quantity: %d, error: %s)',
		 $e->getMessage(),
		 $product_id,
		 $quantity,
		 $e
		) );
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * AJAX handler for saving product for later
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_for_later() {
		check_ajax_referer( 'mc-quick-view', 'nonce' );

		$product_id = absint( $_POST['product_id'] );
		if ( ! $product_id ) {
			wp_send_json_error( __( 'Invalid product ID.', 'mesmeric-commerce' ) );
		}

		try {
			if ( ! is_user_logged_in() ) {
				throw new \Exception( __( 'Please log in to save items.', 'mesmeric-commerce' ) );
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				throw new \Exception( __( 'Product not found.', 'mesmeric-commerce' ) );
			}

			// Get wishlist module
			$wishlist_module = $this->get_plugin()->get_module( 'wishlist' );
			if ( ! $wishlist_module ) {
				throw new \Exception( __( 'Wishlist module not available.', 'mesmeric-commerce' ) );
			}

			// Add to default wishlist
			if ( method_exists( $wishlist_module, 'add_to_default_wishlist' ) ) {
			$result = $wishlist_module->add_to_default_wishlist( $product_id );
			if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
			}
			} else {
			$this->get_logger()->error( sprintf( 'Wishlist module does not implement add_to_default_wishlist method.' ) );
			}

			wp_send_json_success(
				array(
					'message' => __( 'Product saved to wishlist.', 'mesmeric-commerce' ),
				)
			);
		} catch (\Exception $e) {
		$this->get_logger()->error( sprintf(
			'Save for later error: %s (product_id: %d, error: %s)',
			$e->getMessage(),
			$product_id,
			$e
		) );
		wp_send_json_error( $e->getMessage() );
		}
		}

	/**
	 * Add product classes
	 *
	 * @since 1.0.0
	 * @param array      $classes Array of CSS classes.
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public function add_product_classes( $classes, $product ) {
		if ( $product ) {
			$classes[] = 'mc-has-quick-view';
			$classes[] = 'group';
		}
		return $classes;
	}

	/**
	 * Clear product cache
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function clear_cache( $product_id ) {
		delete_transient( 'mc_quick_view_' . $product_id );
	}
}
