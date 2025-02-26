<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\ReasonsToBuy;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use WC_Product;

/**
 * Reasons to Buy Module
 *
 * Displays a list of reasons to buy on product pages.
 */
class ReasonsToByModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'reasons-to-buy';
    private const MODULE_SECTION = 'build-trust';
    private bool $isModulePreview = false;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    /**
     * Constructor.
     */
    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer,
        Translator $translator
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->translator = $translator;

        parent::__construct($optionsManager);

        add_filter('mesmeric_reasons_to_buy_wrapper_class', [$this, 'getWrapperClasses']);

        if ($this->isModulePreview()) {
            $this->isModulePreview = true;
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
            add_filter('mesmeric_custom_css', [$this, 'getAdminCustomCss']);
        }

        if ($this->isActive() && is_admin()) {
            $this->initTranslations();
        }

        // Frontend hooks
        if ($this->isActive() && !is_admin()) {
            add_action('woocommerce_single_product_summary', [$this, 'renderReasonsList'], 25);
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        }
    }

    /**
     * Initialize translations.
     */
    private function initTranslations(): void
    {
        $settings = $this->getSettings();
        
        foreach ($settings['reasons'] ?? [] as $reason) {
            $this->translator->registerString(
                $reason,
                __('Reasons to buy: reason text', 'mesmeric-commerce')
            );
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-reasons-to-buy-admin',
            $this->getModuleUrl() . '/assets/css/admin.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-reasons-to-buy-admin',
            $this->getModuleUrl() . '/assets/js/admin.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );

        wp_localize_script('mesmeric-reasons-to-buy-admin', 'mesmericeReasonsToBuy', [
            'icons' => [
                'check2' => $this->getSvgIcon('check2'),
                'check3' => $this->getSvgIcon('check3'),
            ],
        ]);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueFrontendAssets(): void
    {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'mesmeric-reasons-to-buy',
            $this->getModuleUrl() . '/assets/css/reasons-to-buy.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );
    }

    /**
     * Get wrapper classes.
     */
    public function getWrapperClasses(array $classes): array
    {
        $settings = $this->getSettings();

        if (!empty($settings['display_icon'])) {
            $classes[] = 'show-icon';
        }

        return $classes;
    }

    /**
     * Render reasons list on product page.
     */
    public function renderReasonsList(): void
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $settings = $this->getSettings();
        $reasons = $this->getProductReasons($product);

        if (empty($reasons)) {
            return;
        }

        echo $this->templateRenderer->render('@reasons-to-buy/reasons-list.twig', [
            'reasons' => $reasons,
            'settings' => $settings,
            'product' => $product,
        ]);
    }

    /**
     * Get reasons for a specific product.
     */
    private function getProductReasons(WC_Product $product): array
    {
        $settings = $this->getSettings();
        $reasons = [];

        foreach ($settings['reasons_to_buy'] ?? [] as $reasonGroup) {
            if ($reasonGroup['campaign_status'] !== 'active') {
                continue;
            }

            if (!$this->shouldDisplayForProduct($product, $reasonGroup)) {
                continue;
            }

            $reasons[] = [
                'title' => $reasonGroup['title'],
                'items' => $reasonGroup['items'] ?? [],
            ];
        }

        return $reasons;
    }

    /**
     * Check if reasons should be displayed for a product.
     */
    private function shouldDisplayForProduct(WC_Product $product, array $reasonGroup): bool
    {
        $displayRules = $reasonGroup['display_rules'] ?? 'all';
        $productId = $product->get_id();

        // Check exclusions first
        if (!empty($reasonGroup['exclusion_enabled'])) {
            if (in_array($productId, $reasonGroup['excluded_products'] ?? [], true)) {
                return false;
            }

            $excludedCategories = $reasonGroup['excluded_categories'] ?? [];
            $excludedTags = $reasonGroup['excluded_tags'] ?? [];

            if (!empty($excludedCategories) && has_term($excludedCategories, 'product_cat', $productId)) {
                return false;
            }

            if (!empty($excludedTags) && has_term($excludedTags, 'product_tag', $productId)) {
                return false;
            }
        }

        // Check inclusion rules
        switch ($displayRules) {
            case 'all':
                return true;

            case 'products':
                return in_array($productId, $reasonGroup['product_ids'] ?? [], true);

            case 'categories':
                return has_term($reasonGroup['category_slugs'] ?? [], 'product_cat', $productId);

            case 'tags':
                return has_term($reasonGroup['tag_slugs'] ?? [], 'product_tag', $productId);

            default:
                return false;
        }
    }

    /**
     * Get module ID.
     */
    public function getModuleId(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Get module name.
     */
    public function getModuleName(): string
    {
        return __('Reasons to Buy', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Display a list of reasons to buy on product pages.', 'mesmeric-commerce');
    }

    /**
     * Get module section.
     */
    public function getModuleSection(): string
    {
        return self::MODULE_SECTION;
    }

    /**
     * Get default settings.
     */
    public function getDefaultSettings(): array
    {
        return [
            'reasons_to_buy' => [
                [
                    'campaign_status' => 'active',
                    'title' => __('Why Choose This Product?', 'mesmeric-commerce'),
                    'display_rules' => 'all',
                    'items' => [
                        __('Premium Quality Materials', 'mesmeric-commerce'),
                        __('30-Day Money Back Guarantee', 'mesmeric-commerce'),
                        __('Free Shipping Worldwide', 'mesmeric-commerce'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if WooCommerce is required.
     */
    public function requiresWooCommerce(): bool
    {
        return true;
    }
}
