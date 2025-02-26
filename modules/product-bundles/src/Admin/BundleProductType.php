<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\ProductBundles\Admin;

use MesmericCommerce\Core\Admin\AbstractAdminPage;
use Twig\Environment;

/**
 * Bundle Product Type
 * 
 * Handles the bundle product type admin interface and data management.
 */
class BundleProductType extends AbstractAdminPage {
    private Environment $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->initialize();
    }

    public function initialize(): void {
        add_action('woocommerce_product_options_general_product_data', [$this, 'addBundleOptions']);
        add_action('woocommerce_process_product_meta', [$this, 'saveBundleOptions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addBundleOptions(): void {
        global $post;

        $bundledProducts = get_post_meta($post->ID, '_bundled_products', true) ?: [];
        $bundleData = [];

        foreach ($bundledProducts as $productId) {
            $product = wc_get_product($productId);
            if (!$product) {
                continue;
            }

            $bundleData[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'qty' => get_post_meta($post->ID, "_bundled_product_{$productId}_qty", true) ?: 1,
                'discount' => get_post_meta($post->ID, "_bundled_product_{$productId}_discount", true) ?: 0,
                'optional' => get_post_meta($post->ID, "_bundled_product_{$productId}_optional", true) === 'yes',
                'visibility' => get_post_meta($post->ID, "_bundled_product_{$productId}_visibility", true) ?: 'visible',
            ];
        }

        echo $this->twig->render('admin/bundle-options.twig', [
            'bundle_data' => $bundleData,
            'labels' => [
                'add_product' => __('Add Product', 'mesmeric-commerce'),
                'quantity' => __('Quantity', 'mesmeric-commerce'),
                'discount' => __('Discount %', 'mesmeric-commerce'),
                'optional' => __('Optional', 'mesmeric-commerce'),
                'visibility' => __('Visibility', 'mesmeric-commerce'),
            ],
        ]);
    }

    public function saveBundleOptions(int $postId): void {
        if (!current_user_can('edit_product', $postId)) {
            return;
        }

        $bundledProducts = $_POST['_bundled_products'] ?? [];
        $bundledProducts = array_map('absint', $bundledProducts);
        
        update_post_meta($postId, '_bundled_products', $bundledProducts);

        foreach ($bundledProducts as $productId) {
            update_post_meta(
                $postId,
                "_bundled_product_{$productId}_qty",
                absint($_POST["_bundled_product_{$productId}_qty"] ?? 1)
            );

            update_post_meta(
                $postId,
                "_bundled_product_{$productId}_discount",
                floatval($_POST["_bundled_product_{$productId}_discount"] ?? 0)
            );

            update_post_meta(
                $postId,
                "_bundled_product_{$productId}_optional",
                isset($_POST["_bundled_product_{$productId}_optional"]) ? 'yes' : 'no'
            );

            update_post_meta(
                $postId,
                "_bundled_product_{$productId}_visibility",
                sanitize_text_field($_POST["_bundled_product_{$productId}_visibility"] ?? 'visible')
            );
        }
    }

    public function enqueueAssets(): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'product') {
            wp_enqueue_style(
                'mesmeric-bundle-admin',
                plugin_dir_url(__FILE__) . '../../assets/css/bundle-admin.css',
                [],
                MESMERIC_COMMERCE_VERSION
            );

            wp_enqueue_script(
                'mesmeric-bundle-admin',
                plugin_dir_url(__FILE__) . '../../assets/js/bundle-admin.js',
                ['jquery', 'alpine', 'htmx'],
                MESMERIC_COMMERCE_VERSION,
                true
            );

            wp_localize_script('mesmeric-bundle-admin', 'mesmericBundles', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mesmeric_bundle_admin'),
                'i18n' => [
                    'searchPlaceholder' => __('Search for products...', 'mesmeric-commerce'),
                    'noResults' => __('No products found', 'mesmeric-commerce'),
                ],
            ]);
        }
    }
}
