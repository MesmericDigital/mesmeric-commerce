<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\StockScarcity;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use WC_Product;

/**
 * Stock Scarcity Module
 *
 * Displays a visual indicator of product stock levels to create urgency.
 */
class StockScarcityModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'stock-scarcity';
    private const MODULE_SECTION = 'convert-more';
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

        if ($this->isModulePreview()) {
            $this->isModulePreview = true;
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
        }

        if ($this->isActive() && is_admin()) {
            $this->initTranslations();
        }

        // Frontend hooks
        if ($this->isActive() && !is_admin()) {
            add_action('woocommerce_single_product_summary', [$this, 'renderStockScarcity'], 25);
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        }
    }

    /**
     * Initialize translations.
     */
    private function initTranslations(): void
    {
        $settings = $this->getSettings();

        if (!empty($settings['low_inventory_text'])) {
            $this->translator->registerString(
                $settings['low_inventory_text'],
                __('Stock Scarcity: Text when inventory is low (single item)', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['low_inventory_text_plural'])) {
            $this->translator->registerString(
                $settings['low_inventory_text_plural'],
                __('Stock Scarcity: Text when inventory is low (multiple items)', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['low_inventory_text_simple'])) {
            $this->translator->registerString(
                $settings['low_inventory_text_simple'],
                __('Stock Scarcity: Text when inventory is low (variable product)', 'mesmeric-commerce')
            );
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-stock-scarcity-admin',
            $this->getModuleUrl() . '/assets/css/admin.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-stock-scarcity-admin',
            $this->getModuleUrl() . '/assets/js/admin.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
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
            'mesmeric-stock-scarcity',
            $this->getModuleUrl() . '/assets/css/stock-scarcity.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-stock-scarcity',
            $this->getModuleUrl() . '/assets/js/stock-scarcity.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
    }

    /**
     * Render stock scarcity indicator on product page.
     */
    public function renderStockScarcity(): void
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $settings = $this->getSettings();
        $stockData = $this->getProductStockData($product);

        if (!$stockData) {
            return;
        }

        echo $this->templateRenderer->render('@stock-scarcity/stock-indicator.twig', [
            'settings' => $settings,
            'stock' => $stockData['stock'],
            'percentage' => $stockData['percentage'],
            'isSimple' => $product->is_type('simple'),
        ]);
    }

    /**
     * Get stock data for a product.
     *
     * @return array{stock: int, percentage: float}|null
     */
    private function getProductStockData(WC_Product $product): ?array
    {
        if (!$product->managing_stock()) {
            return null;
        }

        $stock = $product->get_stock_quantity();
        if (!$stock) {
            return null;
        }

        $settings = $this->getSettings();
        $minInventory = $settings['min_inventory'] ?? 50;

        if ($stock >= $minInventory) {
            return null;
        }

        $percentage = min(100, ($stock / $minInventory) * 100);

        return [
            'stock' => $stock,
            'percentage' => $percentage,
        ];
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
        return __('Stock Scarcity', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Display a visual indicator of product stock levels to create urgency.', 'mesmeric-commerce');
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
            'min_inventory' => 50,
            'display_pages' => ['product'],
            'low_inventory_text' => __('Hurry! Only {stock} unit left in stock!', 'mesmeric-commerce'),
            'low_inventory_text_plural' => __('Hurry! Only {stock} units left in stock!', 'mesmeric-commerce'),
            'low_inventory_text_simple' => __('Hurry, low stock.', 'mesmeric-commerce'),
            'gradient_start' => '#ffc108',
            'gradient_end' => '#d61313',
            'progress_bar_bg' => '#e1e1e1',
            'text_font_size' => 16,
            'text_font_weight' => 'normal',
            'text_color' => '#212121',
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
