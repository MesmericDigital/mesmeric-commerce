<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\BuyNow;

use MesmericCommerce\Core\Assets\AssetsManager;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use MesmericCommerce\Modules\BuyNow\Options\ModuleOptions;

/**
 * Buy Now Module
 * 
 * Adds a "Buy Now" button to product pages that allows customers to skip the cart
 * and go directly to checkout with the selected product.
 */
class BuyNowModule
{
    private const MODULE_ID = 'buy-now';
    private const MODULE_SECTION = 'reduce-abandonment';

    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;
    private AssetsManager $assetsManager;
    private AnalyticsLogger $analyticsLogger;

    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer,
        Translator $translator,
        AssetsManager $assetsManager,
        AnalyticsLogger $analyticsLogger
    ) {
        $this->optionsManager = $optionsManager;
        $this->templateRenderer = $templateRenderer;
        $this->translator = $translator;
        $this->assetsManager = $assetsManager;
        $this->analyticsLogger = $analyticsLogger;

        $this->initHooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void
    {
        // Initialize translations
        add_action('init', [$this, 'initTranslations']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // Buy now listener
        add_action('wp_loaded', [$this, 'buyNowListener']);

        // Add buttons to product pages
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        // Single product button
        $singleProductHook = $settings['hook_order_single_product'] ?? [
            'hook_name' => 'woocommerce_after_add_to_cart_button',
            'hook_priority' => 10,
        ];
        add_action(
            $singleProductHook['hook_name'],
            [$this, 'renderSingleProductButton'],
            (int) $singleProductHook['hook_priority']
        );

        // Shop archive button
        $shopArchiveHook = $settings['hook_order_shop_archive'] ?? [
            'hook_name' => 'woocommerce_after_shop_loop_item',
            'hook_priority' => 10,
        ];
        add_action(
            $shopArchiveHook['hook_name'],
            [$this, 'renderShopArchiveButton'],
            (int) $shopArchiveHook['hook_priority']
        );
    }

    /**
     * Initialize translations
     */
    public function initTranslations(): void
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);
        if (!empty($settings['button_text'])) {
            $this->translator->registerString(
                $settings['button_text'],
                'Buy now button text'
            );
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void
    {
        if (!is_product() && !is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }

        $this->assetsManager->enqueueStyle(
            'mesmeric-buy-now',
            'modules/buy-now/css/buy-now.css'
        );

        $this->assetsManager->enqueueScript(
            'mesmeric-buy-now',
            'modules/buy-now/js/buy-now.js',
            ['alpine'],
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('buy_now_nonce'),
                'checkoutUrl' => wc_get_checkout_url(),
                'i18n' => [
                    'addingToCart' => __('Adding to cart...', 'mesmeric-commerce'),
                    'redirectingToCheckout' => __('Redirecting to checkout...', 'mesmeric-commerce'),
                    'error' => __('An error occurred. Please try again.', 'mesmeric-commerce'),
                ],
            ]
        );
    }

    /**
     * Listen for buy now requests
     */
    public function buyNowListener(): void
    {
        if (!isset($_GET['buy-now']) || !isset($_GET['product_id'])) {
            return;
        }

        check_admin_referer('buy-now', 'nonce');

        $productId = (int) $_GET['product_id'];
        $quantity = isset($_GET['quantity']) ? (int) $_GET['quantity'] : 1;
        $variationId = isset($_GET['variation_id']) ? (int) $_GET['variation_id'] : 0;
        $variations = isset($_GET['variations']) ? (array) $_GET['variations'] : [];

        try {
            WC()->cart->empty_cart();

            if ($variationId > 0) {
                $added = WC()->cart->add_to_cart($productId, $quantity, $variationId, $variations);
            } else {
                $added = WC()->cart->add_to_cart($productId, $quantity);
            }

            if ($added) {
                $this->analyticsLogger->logEvent('buy_now_clicked', [
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity' => $quantity,
                ]);

                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(get_permalink($productId));
            exit;
        }
    }

    /**
     * Render buy now button on single product pages
     */
    public function renderSingleProductButton(): void
    {
        global $product;

        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }

        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        $this->templateRenderer->render('@buy-now/button.twig', [
            'settings' => $settings,
            'product' => $product,
            'context' => 'single',
            'button_text' => $this->translator->translate($settings['button_text']),
        ]);
    }

    /**
     * Render buy now button on shop archive pages
     */
    public function renderShopArchiveButton(): void
    {
        global $product;

        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }

        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        $this->templateRenderer->render('@buy-now/button.twig', [
            'settings' => $settings,
            'product' => $product,
            'context' => 'archive',
            'button_text' => $this->translator->translate($settings['button_text']),
        ]);
    }

    /**
     * Get module ID
     */
    public function getModuleId(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Get module section
     */
    public function getModuleSection(): string
    {
        return self::MODULE_SECTION;
    }

    /**
     * Check if module requires WooCommerce
     */
    public function requiresWooCommerce(): bool
    {
        return true;
    }
}
