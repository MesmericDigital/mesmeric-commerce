<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\ProductBundles;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Analytics\AnalyticsInterface;
use Twig\Environment;

/**
 * Product Bundles Module
 * 
 * Allows creation and management of product bundles in WooCommerce.
 * Bundles can include multiple products with customizable quantities and pricing.
 */
class ProductBundlesModule extends AbstractModule implements ModuleInterface, AnalyticsInterface {
    public const MODULE_ID = 'product-bundles';
    
    private Environment $twig;
    
    public function __construct(Environment $twig) {
        $this->twig = $twig;
        parent::__construct();
    }

    public function initialize(): void {
        if (!$this->isActive()) {
            return;
        }

        // Register product type
        add_filter('product_type_selector', [$this, 'addBundleProductType']);
        add_filter('woocommerce_product_class', [$this, 'getBundleProductClass'], 10, 2);

        // Add bundle data tab
        add_filter('woocommerce_product_data_tabs', [$this, 'addBundleDataTab']);
        add_action('woocommerce_product_data_panels', [$this, 'renderBundleDataPanel']);

        // Save bundle data
        add_action('woocommerce_process_product_meta_bundle', [$this, 'saveBundleData']);

        // Display bundle items
        $settings = $this->getSettings();
        $placement = $settings['placement'] ?? 'woocommerce_before_add_to_cart_form';
        add_action($placement, [$this, 'renderBundleItems']);

        // Handle bundle add to cart
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validateBundleAddToCart'], 10, 6);
        add_action('woocommerce_add_to_cart', [$this, 'addBundleToCart'], 10, 6);

        // Handle bundle pricing
        add_filter('woocommerce_product_get_price', [$this, 'getBundlePrice'], 10, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'getBundleRegularPrice'], 10, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'getBundleSalePrice'], 10, 2);

        // Admin
        if (is_admin()) {
            $this->initAdmin();
        }
    }

    public function getDefaultSettings(): array {
        return [
            'price_range' => true,
            'bundled_thumb' => true,
            'bundled_description' => false,
            'bundled_qty' => true,
            'bundled_link_single' => true,
            'bundled_price' => 'price',
            'bundled_price_from' => 'sale_price',
            'placement' => 'woocommerce_before_add_to_cart_form',
        ];
    }

    public function getAnalyticsMetrics(): array {
        $metrics = [
            'total_bundles' => 0,
            'bundle_sales' => 0,
            'bundle_revenue' => 0.0,
            'most_popular_bundles' => [],
            'average_bundle_size' => 0,
        ];

        return apply_filters('mesmeric_analytics_bundle_metrics', $metrics);
    }

    private function initAdmin(): void {
        add_action('admin_init', function() {
            new Admin\BundleProductType($this->twig);
        });

        // Help banner on module page
        add_action('mesmeric_admin_before_include_modules_options', function($moduleId) {
            if ($moduleId === self::MODULE_ID) {
                echo $this->twig->render('admin/help-banner.twig', [
                    'new_bundle_url' => admin_url('post-new.php?post_type=product'),
                ]);
            }
        });

        // Admin assets
        add_action('admin_enqueue_scripts', function() {
            if ($this->isModuleSettingsPage()) {
                wp_enqueue_style(
                    'mesmeric-admin-bundles',
                    $this->getAssetUrl('css/admin.css'),
                    [],
                    MESMERIC_COMMERCE_VERSION
                );

                wp_enqueue_script(
                    'mesmeric-admin-bundles',
                    $this->getAssetUrl('js/admin.js'),
                    ['jquery', 'alpine'],
                    MESMERIC_COMMERCE_VERSION,
                    true
                );
            }
        });
    }

    public function getModuleId(): string {
        return self::MODULE_ID;
    }

    private function getAssetUrl(string $path): string {
        return plugin_dir_url(__FILE__) . '../assets/' . $path;
    }
}
