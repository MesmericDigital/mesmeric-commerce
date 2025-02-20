<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce;

use MesmericCommerce\Includes\MC_Plugin;

/**
 * WooCommerce Integration Class
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce
 */

/**
 * Class MC_WooCommerce
 *
 * Handles WooCommerce integration and customizations
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce
 */
class MC_WooCommerce {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Constructor
    }

    /**
     * Initialize WooCommerce integration.
     */
    public function init(): void {
        // Template overrides
        add_filter('woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3);

        // Product hooks
        add_action('woocommerce_before_shop_loop_item', array( $this, 'quick_view_button' ), 15);
        add_action('woocommerce_after_add_to_cart_button', array( $this, 'wishlist_button' ), 15);

        // AJAX handlers
        add_action('wp_ajax_mc_quick_view', array( $this, 'handle_quick_view' ));
        add_action('wp_ajax_nopriv_mc_quick_view', array( $this, 'handle_quick_view' ));
        add_action('wp_ajax_mc_add_to_wishlist', array( $this, 'handle_add_to_wishlist' ));
        add_action('wp_ajax_mc_remove_from_wishlist', array( $this, 'handle_remove_from_wishlist' ));

        // Product data tabs
        add_filter('woocommerce_product_tabs', array( $this, 'custom_product_tabs' ));

        // Cart fragments
        add_filter('woocommerce_add_to_cart_fragments', array( $this, 'cart_fragments' ));
    }

    /**
     * Locate template files.
     *
     * @param string $template      Template file.
     * @param string $template_name Template name.
     * @param string $template_path Template path.
     * @return string
     */
    public function locate_template( string $template, string $template_name, string $template_path ): string {
        $plugin_template = MC_PLUGIN_DIR . 'woocommerce/templates/' . $template_name;

        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    /**
     * Add quick view button.
     */
    public function quick_view_button(): void {
        if (get_option('mc_enable_quickview', 'yes') !== 'yes') {
            return;
        }

        global $product;
        if (! $product) {
            return;
        }

        printf(
            '<button
                class="mc-quick-view-button"
                data-product-id="%d"
                hx-get="%s"
                hx-trigger="click"
                hx-target="#mc-quick-view-modal"
                hx-swap="innerHTML"
                x-data="{}"
                @click="$dispatch(\'show-quick-view\')"
            >%s</button>',
            $product->get_id(),
            admin_url('admin-ajax.php?action=mc_quick_view&product_id=' . $product->get_id()),
            esc_html__('Quick View', 'mesmeric-commerce')
        );
    }

    /**
     * Add wishlist button.
     */
    public function wishlist_button(): void {
        if (get_option('mc_enable_wishlist', 'yes') !== 'yes') {
            return;
        }

        global $product;
        if (! $product) {
            return;
        }

        $in_wishlist = false;
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id    = get_current_user_id();
            $product_id = $product->get_id();

            $in_wishlist = $wpdb->get_var(
                $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mc_wishlist_items wi
                JOIN {$wpdb->prefix}mc_wishlists w ON wi.wishlist_id = w.id
                WHERE w.user_id = %d AND wi.product_id = %d",
                $user_id,
                $product_id
            )
                ) > 0;
        }

        printf(
            '<button
                class="mc-wishlist-button %s"
                data-product-id="%d"
                hx-post="%s"
                hx-trigger="click"
                hx-swap="outerHTML"
                x-data="{}"
                @click="$dispatch(\'wishlist-update\')"
            >%s</button>',
            $in_wishlist ? 'in-wishlist' : '',
            $product->get_id(),
            admin_url('admin-ajax.php?action=' . ( $in_wishlist ? 'mc_remove_from_wishlist' : 'mc_add_to_wishlist' ) . '&product_id=' . $product->get_id()),
            esc_html__($in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist', 'mesmeric-commerce')
        );
    }

    /**
     * Handle quick view AJAX request.
     */
    public function handle_quick_view(): void {
        check_ajax_referer('mesmeric_commerce_nonce', 'nonce');

        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        if (! $product_id) {
            wp_send_json_error(__('Invalid product ID', 'mesmeric-commerce'));
        }

        $product = wc_get_product($product_id);
        if (! $product) {
            wp_send_json_error(__('Product not found', 'mesmeric-commerce'));
        }

        ob_start();
        require MC_PLUGIN_DIR . 'modules/quick-view/views/quickview-template.php';
        wp_send_json_success(array( 'html' => ob_get_clean() ));
    }

    /**
     * Handle add to wishlist AJAX request.
     */
    public function handle_add_to_wishlist(): void {
        check_ajax_referer('mesmeric_commerce_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(__('Please login to add items to wishlist', 'mesmeric-commerce'));
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if (! $product_id) {
            wp_send_json_error(__('Invalid product ID', 'mesmeric-commerce'));
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Get or create default wishlist
        $wishlist_id = $wpdb->get_var(
            $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mc_wishlists WHERE user_id = %d LIMIT 1",
            $user_id
        )
            );

        if (! $wishlist_id) {
            $wpdb->insert(
                $wpdb->prefix . 'mc_wishlists',
                array(
                    'user_id' => $user_id,
                    'name'    => __('Default Wishlist', 'mesmeric-commerce'),
                )
            );
            $wishlist_id = $wpdb->insert_id;
        }

        // Add product to wishlist
        $wpdb->insert(
            $wpdb->prefix . 'mc_wishlist_items',
            array(
                'wishlist_id' => $wishlist_id,
                'product_id'  => $product_id,
            )
        );

        ob_start();
        $this->wishlist_button();
        wp_send_json_success(array( 'html' => ob_get_clean() ));
    }

    /**
     * Handle remove from wishlist AJAX request.
     */
    public function handle_remove_from_wishlist(): void {
        check_ajax_referer('mesmeric_commerce_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(__('Please login to remove items from wishlist', 'mesmeric-commerce'));
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if (! $product_id) {
            wp_send_json_error(__('Invalid product ID', 'mesmeric-commerce'));
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $wpdb->delete(
            $wpdb->prefix . 'mc_wishlist_items',
            array(
                'product_id'  => $product_id,
                'wishlist_id' => $wpdb->get_var(
                    $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}mc_wishlists WHERE user_id = %d",
                    $user_id
                )
                    ),
            )
        );

        ob_start();
        $this->wishlist_button();
        wp_send_json_success(array( 'html' => ob_get_clean() ));
    }

    /**
     * Add custom product tabs.
     *
     * @param array $tabs Product tabs.
     * @return array
     */
    public function custom_product_tabs( array $tabs ): array {
        // Add custom tabs here if needed
        return $tabs;
    }

    /**
     * Add custom cart fragments.
     *
     * @param array $fragments Cart fragments.
     * @return array
     */
    public function cart_fragments( array $fragments ): array {
        ob_start();
        ?>
        <span class="mc-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        <?php
        $fragments['.mc-cart-count'] = ob_get_clean();
        return $fragments;
    }
}
