<?php
declare(strict_types=1);

namespace MesmericCommerce\Frontend;

/**
 * The public-facing functionality of the plugin.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/public
 */
class MC_Public {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Constructor
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles(): void {
        // Main public styles
        wp_enqueue_style(
            'mesmeric-commerce-public',
            MC_PLUGIN_URL . 'public/css/public-style.css',
            [],
            MC_VERSION,
            'all'
        );

        // Tailwind CSS with DaisyUI
        wp_enqueue_style(
            'mesmeric-commerce-tailwind',
            MC_PLUGIN_URL . 'public/css/tailwind.min.css',
            [],
            MC_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts(): void {
        // HTMX
        wp_enqueue_script(
            'htmx',
            MC_PLUGIN_URL . 'public/js/htmx.min.js',
            [],
            '1.9.10',
            true
        );

        // Alpine.js
        wp_enqueue_script(
            'alpine-js',
            MC_PLUGIN_URL . 'public/js/alpine.min.js',
            [],
            '3.13.3',
            true
        );

        // Main public script
        wp_enqueue_script(
            'mesmeric-commerce-public',
            MC_PLUGIN_URL . 'public/js/public-script.js',
            ['jquery', 'htmx', 'alpine-js'],
            MC_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'mesmeric-commerce-public',
            'mcPublicData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mesmeric_commerce_nonce'),
                'isLoggedIn' => is_user_logged_in(),
                'cartUrl' => wc_get_cart_url(),
                'checkoutUrl' => wc_get_checkout_url(),
                'i18n' => [
                    'addToCart' => __('Add to Cart', 'mesmeric-commerce'),
                    'addedToCart' => __('Added to Cart', 'mesmeric-commerce'),
                    'addToWishlist' => __('Add to Wishlist', 'mesmeric-commerce'),
                    'addedToWishlist' => __('Added to Wishlist', 'mesmeric-commerce'),
                    'quickView' => __('Quick View', 'mesmeric-commerce'),
                    'loading' => __('Loading...', 'mesmeric-commerce'),
                    'error' => __('Error occurred', 'mesmeric-commerce'),
                ],
            ]
        );
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes(): void {
        add_shortcode('mesmeric_wishlist', [$this, 'render_wishlist_shortcode']);
    }

    /**
     * Render wishlist shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_wishlist_shortcode(array $atts = []): string {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'mesmeric-commerce'),
                wp_login_url(get_permalink()),
                __('login', 'mesmeric-commerce')
            );
        }

        ob_start();
        require MC_PLUGIN_DIR . 'modules/wishlist/views/wishlist-template.php';
        return ob_get_clean();
    }

    /**
     * Add custom body classes.
     *
     * @param array $classes Array of body classes.
     * @return array
     */
    public function add_body_classes(array $classes): array {
        if (is_product() || is_shop() || is_product_category() || is_product_tag()) {
            $classes[] = 'mesmeric-commerce-active';
        }
        return $classes;
    }
}
