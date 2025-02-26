<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\AddToCartText;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use Twig\Environment;

/**
 * Add To Cart Text Module
 * 
 * Allows customization of WooCommerce add to cart button text for different product types
 * and contexts (shop/single product pages).
 */
class AddToCartTextModule extends AbstractModule implements ModuleInterface {
    public const MODULE_ID = 'add-to-cart-text';
    
    private Environment $twig;
    
    public function __construct(Environment $twig) {
        $this->twig = $twig;
        parent::__construct();
    }

    public function initialize(): void {
        if (!$this->isActive()) {
            return;
        }

        // Customize add to cart text on the single product page
        add_filter('woocommerce_product_single_add_to_cart_text', 
            [$this, 'customizeSingleAddToCartText'], 
            99
        );

        // Customize add to cart text on shop pages
        add_filter('woocommerce_product_add_to_cart_text', 
            [$this, 'customizeShopAddToCartText'], 
            10, 
            2
        );

        // Add admin metabox if in admin
        if (is_admin()) {
            $this->initAdmin();
        }
    }

    public function getDefaultSettings(): array {
        return [
            'simple_product_label' => __('Add to cart', 'mesmeric-commerce'),
            'simple_product_shop_label' => __('Add to cart', 'mesmeric-commerce'),
            'simple_product_custom_single_label' => false,
            'variable_product_label' => __('Add to cart', 'mesmeric-commerce'),
            'variable_product_shop_label' => __('Select options', 'mesmeric-commerce'),
            'variable_product_custom_single_label' => false,
            'out_of_stock_shop_label' => __('Out of stock', 'mesmeric-commerce'),
            'out_of_stock_custom_label' => false,
        ];
    }

    public function customizeSingleAddToCartText(string $text): string {
        $product = wc_get_product();
        if (!$product) {
            return $text;
        }

        $settings = $this->getSettings();
        $productId = $product->get_id();
        $customText = get_post_meta($productId, '_mesmeric_add_to_cart_text_single_label', true);

        if (!empty($customText)) {
            return esc_html($customText);
        }

        if ($product->is_type('variable')) {
            return esc_html($settings['variable_product_label']);
        }

        return esc_html($settings['simple_product_label']);
    }

    public function customizeShopAddToCartText(string $text, \WC_Product $product): string {
        if (!$product) {
            return $text;
        }

        $settings = $this->getSettings();
        $productId = $product->get_id();
        $customText = get_post_meta($productId, '_mesmeric_add_to_cart_text_shop_label', true);

        if (!empty($customText)) {
            return esc_html($customText);
        }

        if (!$product->is_in_stock()) {
            return esc_html($settings['out_of_stock_shop_label']);
        }

        if ($product->is_type('variable')) {
            return esc_html($settings['variable_product_shop_label']);
        }

        return esc_html($settings['simple_product_shop_label']);
    }

    private function initAdmin(): void {
        add_action('admin_init', function() {
            new Admin\AddToCartTextMetabox();
        });
    }

    public function getModuleId(): string {
        return self::MODULE_ID;
    }
}
