<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\QuickView;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsInterface;
use MesmericCommerce\Core\Template\TemplateRendererInterface;
use MesmericCommerce\Core\Analytics\AnalyticsInterface;
use MesmericCommerce\Core\Assets\AssetsInterface;
use MesmericCommerce\Modules\QuickView\Options\ModuleOptions;

/**
 * Quick View Module
 * 
 * Provides a quick view modal for WooCommerce products.
 * 
 * @since 1.0.0
 */
class QuickViewModule extends AbstractModule implements ModuleInterface
{
    private const MODULE_ID = 'quick-view';
    private const NONCE_ACTION = 'mesmeric_commerce_quick_view';

    public function __construct(
        private readonly OptionsInterface $options,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly AnalyticsInterface $analytics,
        private readonly AssetsInterface $assets
    ) {
        parent::__construct();
    }

    public function initialize(): void
    {
        if (!$this->isWooCommerceActive()) {
            return;
        }

        // Register options
        $this->options->register(new ModuleOptions());

        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // Register AJAX handlers
        add_action('wp_ajax_mesmeric_commerce_quick_view', [$this, 'handleQuickViewAjax']);
        add_action('wp_ajax_nopriv_mesmeric_commerce_quick_view', [$this, 'handleQuickViewAjax']);

        // Add quick view button based on position setting
        $this->addQuickViewButton();

        // Add modal template to footer
        add_action('wp_footer', [$this, 'renderModal']);
    }

    private function addQuickViewButton(): void
    {
        if ($this->options->get('shortcode_enabled', false)) {
            return;
        }

        $position = $this->options->get('button_position', 'overlay');
        $hook = match ($position) {
            'before' => ['woocommerce_after_shop_loop_item', 5],
            'after' => ['woocommerce_after_shop_loop_item', 15],
            'overlay' => $this->getThemeOverlayHook(),
            default => ['woocommerce_after_shop_loop_item', 10],
        };

        add_action($hook[0], [$this, 'renderQuickViewButton'], $hook[1]);
    }

    private function getThemeOverlayHook(): array
    {
        return match (true) {
            $this->isThemeActive('kadence') => ['woocommerce_before_shop_loop_item_title', 35],
            $this->isThemeActive('blocksy') => ['blocksy:woocommerce:product-card:thumbnail:end', 10],
            $this->isThemeActive('botiga') => ['woocommerce_before_shop_loop_item_title', 10],
            $this->isThemeActive('oceanwp') => ['ocean_after_archive_product_image', 10],
            $this->isThemeActive('flatsome') => ['flatsome_woocommerce_shop_loop_images', 10],
            $this->isThemeActive('astra') => ['woocommerce_after_shop_loop_item', 7],
            $this->isThemeActive('storefront') => $this->addStorefrontSupport(),
            default => ['woocommerce_after_shop_loop_item', 10],
        };
    }

    private function addStorefrontSupport(): array
    {
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
        add_action('woocommerce_before_shop_loop_item_title', function() {
            echo '<div class="mesmeric-commerce-storefront-thumbnail-wrapper">';
            woocommerce_template_loop_product_thumbnail();
            $this->renderQuickViewButton();
            echo '</div>';
        });
        return ['woocommerce_before_shop_loop_item_title', 10];
    }

    public function enqueueAssets(): void
    {
        // WooCommerce scripts
        wp_enqueue_script('zoom');
        wp_enqueue_script('flexslider');
        wp_enqueue_script('wc-single-product');
        wp_enqueue_script('wc-add-to-cart-variation');

        // Module assets
        $this->assets->enqueueStyle('quick-view', 'modules/quick-view/css/quick-view.css');
        $this->assets->enqueueScript('quick-view', 'modules/quick-view/js/quick-view.js', [
            'dependencies' => ['alpine', 'wc-single-product'],
            'inFooter' => true,
        ]);

        // Localize script
        wp_localize_script('mesmeric-commerce-quick-view', 'mesQuickView', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'i18n' => [
                'loading' => __('Loading...', 'mesmeric-commerce'),
                'error' => __('Error loading product', 'mesmeric-commerce'),
            ],
            'settings' => [
                'zoomEnabled' => $this->options->get('zoom_effect', true),
                'showQuantity' => $this->options->get('show_quantity', true),
                'showBuyNow' => $this->options->get('show_buy_now_button', false),
                'showSuggested' => $this->options->get('show_suggested_products', false),
            ],
        ]);
    }

    public function renderQuickViewButton(): void
    {
        global $product;

        if (!$product) {
            return;
        }

        $this->templateRenderer->render('modules/quick-view/button.twig', [
            'product_id' => $product->get_id(),
            'button_type' => $this->options->get('button_type', 'text'),
            'button_text' => $this->options->get('button_text', __('Quick View', 'mesmeric-commerce')),
            'button_icon' => $this->options->get('button_icon', 'eye'),
            'button_position' => $this->options->get('button_position', 'overlay'),
        ]);

        $this->analytics->logEvent('quick_view_button_shown', [
            'product_id' => $product->get_id(),
            'button_type' => $this->options->get('button_type', 'text'),
            'button_position' => $this->options->get('button_position', 'overlay'),
        ]);
    }

    public function renderModal(): void
    {
        $this->templateRenderer->render('modules/quick-view/modal.twig', [
            'image_placement' => $this->options->get('place_product_image', 'thumbs-at-left'),
            'description_placement' => $this->options->get('place_product_description', 'top'),
            'description_style' => $this->options->get('description_style', 'short'),
            'show_quantity' => $this->options->get('show_quantity', true),
            'show_buy_now' => $this->options->get('show_buy_now_button', false),
            'show_suggested' => $this->options->get('show_suggested_products', false),
            'suggested_module' => $this->options->get('suggested_products_module', 'bulk_discounts'),
        ]);
    }

    public function handleQuickViewAjax(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if (!$productId) {
            wp_send_json_error(['message' => __('Invalid product ID', 'mesmeric-commerce')]);
        }

        $product = wc_get_product($productId);
        if (!$product) {
            wp_send_json_error(['message' => __('Product not found', 'mesmeric-commerce')]);
        }

        try {
            $html = $this->templateRenderer->render('modules/quick-view/content.twig', [
                'product' => $product,
                'description_placement' => $this->options->get('place_product_description', 'top'),
                'description_style' => $this->options->get('description_style', 'short'),
                'show_quantity' => $this->options->get('show_quantity', true),
                'show_buy_now' => $this->options->get('show_buy_now_button', false),
                'show_suggested' => $this->options->get('show_suggested_products', false),
                'suggested_module' => $this->options->get('suggested_products_module', 'bulk_discounts'),
            ], true);

            $this->analytics->logEvent('quick_view_opened', [
                'product_id' => $productId,
                'product_name' => $product->get_name(),
                'product_type' => $product->get_type(),
            ]);

            wp_send_json_success(['html' => $html]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function isThemeActive(string $theme): bool
    {
        return function_exists("mesmeric_is_{$theme}_active") && call_user_func("mesmeric_is_{$theme}_active");
    }

    private function isWooCommerceActive(): bool
    {
        return class_exists('WooCommerce');
    }
}
