<?php

declare(strict_types=1);

namespace MesmericCommerce\Compatibility;

use MesmericCommerce\Core\Module\ModuleRegistry;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Modules\QuickView\QuickViewModule;
use MesmericCommerce\Modules\ProductSwatches\ProductSwatchesModule;
use MesmericCommerce\Modules\Wishlist\WishlistModule;
use MesmericCommerce\Modules\SideCart\SideCartModule;
use MesmericCommerce\Modules\ProductAudio\ProductAudioModule;
use MesmericCommerce\Modules\ProductVideo\ProductVideoModule;
use MesmericCommerce\Modules\StickyAddToCart\StickyAddToCartModule;
use MesmericCommerce\Modules\RecentlyViewedProducts\RecentlyViewedProductsModule;

/**
 * Breakdance Builder Compatibility Layer
 * 
 * Provides compatibility between Mesmeric Commerce modules and the Breakdance page builder.
 * Handles module-specific integrations and CSS customizations.
 *
 * @since 1.0.0
 */
class BreakdanceBuilder {
    private ModuleRegistry $moduleRegistry;
    private OptionsManager $optionsManager;

    /**
     * Initialize the Breakdance Builder compatibility layer.
     *
     * @param ModuleRegistry $moduleRegistry The module registry instance.
     * @param OptionsManager $optionsManager The options manager instance.
     */
    public function __construct(ModuleRegistry $moduleRegistry, OptionsManager $optionsManager) {
        $this->moduleRegistry = $moduleRegistry;
        $this->optionsManager = $optionsManager;

        if ((is_admin() && !wp_doing_ajax()) || !$this->isBreakdanceActive()) {
            return;
        }

        $this->initializeCompatibility();
    }

    /**
     * Check if Breakdance is active.
     *
     * @return bool True if Breakdance is active, false otherwise.
     */
    private function isBreakdanceActive(): bool {
        return defined('BREAKDANCE_VERSION') && BREAKDANCE_VERSION;
    }

    /**
     * Initialize compatibility features.
     */
    private function initializeCompatibility(): void {
        $this->initQuickView();
        $this->initProFeatures();
        
        // Custom CSS
        add_filter('mesmeric_custom_css', [$this, 'getFrontendCustomCss']);
    }

    /**
     * Initialize QuickView module compatibility.
     */
    private function initQuickView(): void {
        if (!$this->moduleRegistry->isModuleActive(QuickViewModule::MODULE_ID)) {
            return;
        }

        $buttonPosition = $this->optionsManager->get(QuickViewModule::MODULE_ID, 'button_position', 'overlay');

        if ($buttonPosition === 'overlay') {
            $quickView = $this->moduleRegistry->getModule(QuickViewModule::MODULE_ID);
            add_filter('breakdance_before_shop_loop_after_image', [$quickView, 'renderQuickViewButton']);
            remove_action('woocommerce_after_shop_loop_item', [$quickView, 'renderQuickViewButton']);
        }
    }

    /**
     * Initialize pro features if available.
     */
    private function initProFeatures(): void {
        if (!$this->isProActive()) {
            return;
        }

        $this->initProductSwatches();
        $this->initWishlist();
        $this->initSideCart();
        $this->initMediaFeatures();
        $this->initStickyAddToCart();
        $this->initRecentlyViewedProducts();
    }

    /**
     * Check if pro version is active.
     *
     * @return bool True if pro version is active, false otherwise.
     */
    private function isProActive(): bool {
        return apply_filters('mesmeric_commerce_is_pro_active', false);
    }

    /**
     * Initialize product swatches compatibility.
     */
    private function initProductSwatches(): void {
        if ($this->moduleRegistry->isModuleActive(ProductSwatchesModule::MODULE_ID)) {
            remove_action('breakdance_shop_loop_footer', 'woocommerce_template_loop_add_to_cart');
        }
    }

    /**
     * Initialize wishlist compatibility.
     */
    private function initWishlist(): void {
        if (!$this->moduleRegistry->isModuleActive(WishlistModule::MODULE_ID)) {
            return;
        }

        $displayOnSingleProduct = $this->optionsManager->get(WishlistModule::MODULE_ID, 'display_on_single_product', true);
        
        if ($displayOnSingleProduct) {
            $wishlist = $this->moduleRegistry->getModule(WishlistModule::MODULE_ID);
            add_action('woocommerce_after_add_to_cart_form', [$wishlist, 'renderWishlistLink']);
        }
    }

    /**
     * Initialize side cart compatibility.
     */
    private function initSideCart(): void {
        if ($this->moduleRegistry->isModuleActive(SideCartModule::MODULE_ID)) {
            remove_action('woocommerce_widget_cart_item_quantity', '\Breakdance\WooCommerce\addQuantityInputToMiniCart');
        }
    }

    /**
     * Initialize media features compatibility.
     */
    private function initMediaFeatures(): void {
        if ($this->moduleRegistry->isModuleActive(ProductAudioModule::MODULE_ID) || 
            $this->moduleRegistry->isModuleActive(ProductVideoModule::MODULE_ID)) {
            add_filter('breakdance_woocommerce_product_gallery_options', [$this, 'modifyProductGalleryOptions']);
        }
    }

    /**
     * Initialize sticky add to cart compatibility.
     */
    private function initStickyAddToCart(): void {
        if (!$this->moduleRegistry->isModuleActive(StickyAddToCartModule::MODULE_ID)) {
            return;
        }

        $stickyCart = $this->moduleRegistry->getModule(StickyAddToCartModule::MODULE_ID);
        add_action('breakdance_after_single_product', [$stickyCart, 'renderStickyBar']);
    }

    /**
     * Initialize recently viewed products compatibility.
     */
    private function initRecentlyViewedProducts(): void {
        if (!$this->moduleRegistry->isModuleActive(RecentlyViewedProductsModule::MODULE_ID)) {
            return;
        }

        $recentlyViewed = $this->moduleRegistry->getModule(RecentlyViewedProductsModule::MODULE_ID);
        add_action('breakdance_after_single_product', [$recentlyViewed, 'renderProductsList']);
    }

    /**
     * Modify product gallery options for media features.
     *
     * @param array $options The gallery options.
     * @return array Modified gallery options.
     */
    public function modifyProductGalleryOptions(array $options): array {
        $options['zoom'] = false;
        $options['lightbox'] = false;
        return $options;
    }

    /**
     * Get custom CSS for frontend compatibility.
     *
     * @param string $css Existing custom CSS.
     * @return string Modified custom CSS.
     */
    public function getFrontendCustomCss(string $css): string {
        $customCss = '';

        if ($this->moduleRegistry->isModuleActive(QuickViewModule::MODULE_ID)) {
            $customCss .= $this->getQuickViewCss();
        }

        if ($this->isProActive()) {
            if ($this->moduleRegistry->isModuleActive(ProductSwatchesModule::MODULE_ID)) {
                $customCss .= $this->getProductSwatchesCss();
            }

            if ($this->moduleRegistry->isModuleActive(WishlistModule::MODULE_ID)) {
                $customCss .= $this->getWishlistCss();
            }

            if ($this->moduleRegistry->isModuleActive(SideCartModule::MODULE_ID)) {
                $customCss .= $this->getSideCartCss();
            }

            if ($this->moduleRegistry->isModuleActive(StickyAddToCartModule::MODULE_ID)) {
                $customCss .= $this->getStickyAddToCartCss();
            }
        }

        return $css . $customCss;
    }

    /**
     * Get QuickView custom CSS.
     *
     * @return string QuickView custom CSS.
     */
    private function getQuickViewCss(): string {
        return <<<CSS
            .breakdance-woocommerce .products .product .mesmeric-quick-view-button {
                padding: var(--bde-button-padding-base);
                font-size: var(--bde-button-font-size);
                line-height: var(--bde-button-line-height);
                font-weight: var(--bde-button-font-weight);
                border-radius: var(--bde-button-border-radius);
            }
            .breakdance-woocommerce .products .product .mesmeric-quick-view-position-before {
                margin-bottom: 10px;
            }
            .breakdance-woocommerce .products .product .mesmeric-quick-view-position-after {
                margin-top: 10px;
            }
            .breakdance-woocommerce .products .product .mesmeric-quick-view-position-overlay {
                width: auto;
                position: absolute;
            }
            .mesmeric-quick-view-modal .bde-quantity-button {
                display: none !important;
            }
            .mesmeric-quick-view-modal .single_add_to_cart_button,
            .mesmeric-quick-view-modal .mesmeric-bogo-add-to-cart,
            .mesmeric-quick-view-modal .mesmeric-add-bundle-to-cart {
                padding: var(--bde-button-padding-base);
                font-size: var(--bde-button-font-size);
                line-height: var(--bde-button-line-height);
                font-weight: var(--bde-button-font-weight);
                border-radius: var(--bde-button-border-radius);
            }
            .mesmeric-quick-view-modal input[type=number] {
                width: 80px;
                text-align: center;
                padding: 10px;
            }
            .mesmeric-quick-view-modal .variations td.value,
            .mesmeric-quick-view-modal .variations th.label {
                display: block;
                text-align: left;
                margin: 7px 0;
            }
            .mesmeric-quick-view-modal .mesmeric-frequently-bought-together-bundle-product img {
                max-width: none;
            }
        CSS;
    }

    /**
     * Get Product Swatches custom CSS.
     *
     * @return string Product Swatches custom CSS.
     */
    private function getProductSwatchesCss(): string {
        return <<<CSS
            .breakdance-woocommerce .products .product a.mesmeric-variation-item {
                width: auto;
            }
            .breakdance-woocommerce .products .product table.variations {
                margin-bottom: 10px;
            }
        CSS;
    }

    /**
     * Get Wishlist custom CSS.
     *
     * @return string Wishlist custom CSS.
     */
    private function getWishlistCss(): string {
        return <<<CSS
            .breakdance-woocommerce .products .product .mesmeric-wishlist-button {
                width: auto;
            }
            .single-product .mesmeric-wishlist-button {
                position: static;
            }
            .single-product li .mesmeric-wishlist-button {
                position: absolute;
            }
            .mesmeric-wishlist-button ~ .mesmeric-product-swatches .mesmeric-wishlist-button {
                display: none !important;
            }
        CSS;
    }

    /**
     * Get Side Cart custom CSS.
     *
     * @return string Side Cart custom CSS.
     */
    private function getSideCartCss(): string {
        return <<<CSS
            .bde-mini-cart-offcanvas-footer .woocommerce-mini-cart__buttons a {
                padding: var(--bde-button-padding-base);
                font-size: var(--bde-button-font-size);
                line-height: var(--bde-button-line-height);
                font-weight: var(--bde-button-font-weight);
                border-radius: var(--bde-button-border-radius);
            }
            .mesmeric-floating-side-mini-cart-widget .mesmeric-quantity-inner .bde-quantity-button {
                display: none !important;
            }
        CSS;
    }

    /**
     * Get Sticky Add To Cart custom CSS.
     *
     * @return string Sticky Add To Cart custom CSS.
     */
    private function getStickyAddToCartCss(): string {
        return <<<CSS
            .mesmeric-sticky-add-to-cart-item .button {
                padding: var(--bde-button-padding-base);
                font-size: var(--bde-button-font-size);
                line-height: var(--bde-button-line-height);
                font-weight: var(--bde-button-font-weight);
                border-radius: var(--bde-button-border-radius);
            }
            .mesmeric-sticky-add-to-cart-item .quantity {
                position: relative;
            }
            .mesmeric-sticky-add-to-cart-item .quantity input {
                width: 80px;
                padding: 10px 5px;
                text-align: center;
            }
            .mesmeric-sticky-add-to-cart-item .quantity input::-webkit-outer-spin-button,
            .mesmeric-sticky-add-to-cart-item .quantity input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .mesmeric-sticky-add-to-cart-item .quantity input[type=number] {
                -moz-appearance: textfield;
            }
        CSS;
    }
}

// Initialize the compatibility layer
add_action('init', function() {
    $moduleRegistry = new ModuleRegistry();
    $optionsManager = new OptionsManager();
    new BreakdanceBuilder($moduleRegistry, $optionsManager);
}, 20);
