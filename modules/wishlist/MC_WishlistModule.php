<?php
/**
 * Wishlist Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/wishlist
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Wishlist;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_WishlistModule
 *
 * Handles wishlist functionality
 */
class MC_WishlistModule {
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
     * Initialize the module.
     */
    public function __construct() {
        global $mesmeric_commerce;
        $this->plugin = $mesmeric_commerce;
        $this->logger = $this->plugin->get_logger();

        $this->register_hooks();
    }

    /**
     * Register module hooks.
     *
     * @return void
     */
    private function register_hooks(): void {
        // Activation/deactivation
        register_activation_hook(MC_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(MC_PLUGIN_FILE, array($this, 'deactivate'));

        // User actions
        add_action('user_register', array($this, 'create_default_wishlist'));

        // AJAX handlers
        add_action('wp_ajax_mc_add_to_wishlist', array($this, 'handle_add_to_wishlist'));
        add_action('wp_ajax_mc_remove_from_wishlist', array($this, 'handle_remove_from_wishlist'));
        add_action('wp_ajax_mc_create_wishlist', array($this, 'handle_create_wishlist'));
        add_action('wp_ajax_mc_update_wishlist', array($this, 'handle_update_wishlist'));
        add_action('wp_ajax_mc_delete_wishlist', array($this, 'handle_delete_wishlist'));
        add_action('wp_ajax_mc_share_wishlist', array($this, 'handle_share_wishlist'));

        // Frontend display
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_wishlist_button'));
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_wishlist_button'));
        add_shortcode('mc_wishlist', array($this, 'wishlist_shortcode'));

        // Assets
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        }
    }

    /**
     * Activate the module.
     *
     * @return void
     */
    public function activate(): void {
        MC_WishlistTable::create_tables();
    }

    /**
     * Deactivate the module.
     *
     * @return void
     */
    public function deactivate(): void {
        // Optionally drop tables if uninstalling
        if (defined('WP_UNINSTALL_PLUGIN')) {
            MC_WishlistTable::drop_tables();
        }
    }

    /**
     * Create default wishlist for new user.
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function create_default_wishlist(int $user_id): void {
        MC_WishlistTable::create_default_wishlist($user_id);
    }

    /**
     * Handle adding item to wishlist.
     *
     * @return void
     */
    public function handle_add_to_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to add items to your wishlist', 'mesmeric-commerce'));
                return;
            }

            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            $variation_id = filter_input(INPUT_POST, 'variation_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

            if (!$product_id || !$wishlist_id) {
                wp_send_json_error(__('Invalid request', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $wpdb->insert(
                MC_WishlistTable::get_items_table_name(),
                array(
                    'wishlist_id' => $wishlist_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $quantity
                ),
                array('%d', '%d', '%d', '%d')
            );

            wp_send_json_success(__('Item added to wishlist', 'mesmeric-commerce'));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error adding item to wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error adding item to wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Handle removing item from wishlist.
     *
     * @return void
     */
    public function handle_remove_from_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to manage your wishlist', 'mesmeric-commerce'));
                return;
            }

            $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            if (!$item_id) {
                wp_send_json_error(__('Invalid request', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $wpdb->delete(
                MC_WishlistTable::get_items_table_name(),
                array('id' => $item_id),
                array('%d')
            );

            wp_send_json_success(__('Item removed from wishlist', 'mesmeric-commerce'));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error removing item from wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error removing item from wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Handle creating new wishlist.
     *
     * @return void
     */
    public function handle_create_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to create a wishlist', 'mesmeric-commerce'));
                return;
            }

            $name = sanitize_text_field($_POST['name'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            $visibility = sanitize_key($_POST['visibility'] ?? 'private');

            if (empty($name)) {
                wp_send_json_error(__('Please provide a name for your wishlist', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $wpdb->insert(
                MC_WishlistTable::get_table_name(),
                array(
                    'user_id' => get_current_user_id(),
                    'name' => $name,
                    'description' => $description,
                    'visibility' => $visibility,
                    'share_key' => wp_generate_password(12, false)
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );

            wp_send_json_success(__('Wishlist created successfully', 'mesmeric-commerce'));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error creating wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error creating wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Handle updating wishlist.
     *
     * @return void
     */
    public function handle_update_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to update your wishlist', 'mesmeric-commerce'));
                return;
            }

            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            if (!$wishlist_id) {
                wp_send_json_error(__('Invalid request', 'mesmeric-commerce'));
                return;
            }

            $name = sanitize_text_field($_POST['name'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            $visibility = sanitize_key($_POST['visibility'] ?? 'private');

            if (empty($name)) {
                wp_send_json_error(__('Please provide a name for your wishlist', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $wpdb->update(
                MC_WishlistTable::get_table_name(),
                array(
                    'name' => $name,
                    'description' => $description,
                    'visibility' => $visibility
                ),
                array('id' => $wishlist_id, 'user_id' => get_current_user_id()),
                array('%s', '%s', '%s'),
                array('%d', '%d')
            );

            wp_send_json_success(__('Wishlist updated successfully', 'mesmeric-commerce'));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error updating wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error updating wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Handle deleting wishlist.
     *
     * @return void
     */
    public function handle_delete_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to delete your wishlist', 'mesmeric-commerce'));
                return;
            }

            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            if (!$wishlist_id) {
                wp_send_json_error(__('Invalid request', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $wpdb->delete(
                MC_WishlistTable::get_table_name(),
                array('id' => $wishlist_id, 'user_id' => get_current_user_id()),
                array('%d', '%d')
            );

            wp_send_json_success(__('Wishlist deleted successfully', 'mesmeric-commerce'));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error deleting wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error deleting wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Handle sharing wishlist.
     *
     * @return void
     */
    public function handle_share_wishlist(): void {
        try {
            check_ajax_referer('mc_wishlist_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_send_json_error(__('Please log in to share your wishlist', 'mesmeric-commerce'));
                return;
            }

            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            if (!$wishlist_id) {
                wp_send_json_error(__('Invalid request', 'mesmeric-commerce'));
                return;
            }

            global $wpdb;
            $share_key = wp_generate_password(12, false);

            $wpdb->update(
                MC_WishlistTable::get_table_name(),
                array(
                    'visibility' => 'public',
                    'share_key' => $share_key
                ),
                array('id' => $wishlist_id, 'user_id' => get_current_user_id()),
                array('%s', '%s'),
                array('%d', '%d')
            );

            $share_url = add_query_arg('wishlist', $share_key, home_url('/wishlist/'));
            wp_send_json_success(array(
                'message' => __('Wishlist shared successfully', 'mesmeric-commerce'),
                'share_url' => $share_url
            ));
        } catch (\Throwable $e) {
            $this->logger->log_error(
                sprintf('Error sharing wishlist: %s', $e->getMessage()),
                'error',
                true
            );
            wp_send_json_error(__('Error sharing wishlist', 'mesmeric-commerce'));
        }
    }

    /**
     * Add wishlist button to product.
     *
     * @return void
     */
    public function add_wishlist_button(): void {
        global $product;
        if (!$product) {
            return;
        }

        $wishlists = is_user_logged_in() ? MC_WishlistTable::get_user_wishlists(get_current_user_id()) : [];

        wc_get_template(
            'wishlist/button.php',
            array(
                'product' => $product,
                'wishlists' => $wishlists
            ),
            '',
            plugin_dir_path(__FILE__) . 'views/'
        );
    }

    /**
     * Wishlist shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function wishlist_shortcode(array $atts): string {
        $atts = shortcode_atts(array(
            'id' => 0,
            'share_key' => ''
        ), $atts);

        if ($atts['share_key']) {
            $wishlist = MC_WishlistTable::get_wishlist_by_share_key($atts['share_key']);
        } elseif ($atts['id']) {
            $wishlist = MC_WishlistTable::get_user_wishlists(get_current_user_id());
            $wishlist = array_filter($wishlist, fn($w) => $w['id'] == $atts['id']);
            $wishlist = reset($wishlist);
        } else {
            $wishlists = is_user_logged_in() ? MC_WishlistTable::get_user_wishlists(get_current_user_id()) : [];
            ob_start();
            wc_get_template(
                'wishlist/lists.php',
                array('wishlists' => $wishlists),
                '',
                plugin_dir_path(__FILE__) . 'views/'
            );
            return ob_get_clean();
        }

        if (!$wishlist) {
            return '<p>' . esc_html__('Wishlist not found', 'mesmeric-commerce') . '</p>';
        }

        $items = MC_WishlistTable::get_wishlist_items($wishlist['id']);

        ob_start();
        wc_get_template(
            'wishlist/view.php',
            array(
                'wishlist' => $wishlist,
                'items' => $items
            ),
            '',
            plugin_dir_path(__FILE__) . 'views/'
        );
        return ob_get_clean();
    }

    /**
     * Enqueue module assets.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'mc-wishlist',
            plugin_dir_url(__FILE__) . 'assets/css/wishlist.css',
            array(),
            MC_VERSION
        );

        wp_enqueue_script(
            'mc-wishlist',
            plugin_dir_url(__FILE__) . 'assets/js/wishlist.js',
            array('alpine'),
            MC_VERSION,
            true
        );

        wp_localize_script(
            'mc-wishlist',
            'mcWishlistData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mc_wishlist_nonce'),
                'i18n' => array(
                    'addToWishlist' => __('Add to Wishlist', 'mesmeric-commerce'),
                    'removeFromWishlist' => __('Remove from Wishlist', 'mesmeric-commerce'),
                    'adding' => __('Adding...', 'mesmeric-commerce'),
                    'removing' => __('Removing...', 'mesmeric-commerce'),
                    'selectWishlist' => __('Select a Wishlist', 'mesmeric-commerce'),
                    'createNew' => __('Create New Wishlist', 'mesmeric-commerce'),
                    'pleaseLogin' => __('Please log in to use wishlists', 'mesmeric-commerce'),
                )
            )
        );
    }
}
