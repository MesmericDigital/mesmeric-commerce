<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\PreOrders;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use DateTimeZone;
use DateTime;
use WC_Order;
use WC_Product;

/**
 * Pre Orders Module
 *
 * Allows customers to pre-order products that are not yet available.
 */
class PreOrdersModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'pre-orders';
    public const DATE_TIME_FORMAT = 'm-d-Y h:i A';
    private const MODULE_SECTION = 'boost-revenue';

    private TemplateRenderer $templateRenderer;
    private AnalyticsLogger $analyticsLogger;
    private bool $isPreOrderFilterOn = false;

    /**
     * Constructor.
     */
    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer,
        AnalyticsLogger $analyticsLogger
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->analyticsLogger = $analyticsLogger;
        parent::__construct($optionsManager);

        if (!$this->isActive()) {
            return;
        }

        $this->registerHooks();
        $this->setupCronJob();
    }

    /**
     * Register module hooks.
     */
    private function registerHooks(): void
    {
        // Cart validation and data
        add_filter('woocommerce_add_to_cart_validation', [$this, 'allowOneTypeOnly'], 99, 2);
        add_filter('woocommerce_add_cart_item_data', [$this, 'addCartItemData'], 10, 4);
        add_filter('woocommerce_add_cart_item_data', [$this, 'recordAddToCartEvent'], 11, 2);
        
        // Order handling
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hiddenOrderItemMeta']);
        add_action('woocommerce_add_order_item_meta', [$this, 'addOrderItemMeta'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'createOrderLineItem'], 10, 3);
        add_action('woocommerce_checkout_order_created', [$this, 'logOrderEvent']);
        add_action('woocommerce_thankyou', [$this, 'splitOrders']);

        // Button and text modifications
        add_filter('woocommerce_product_add_to_cart_text', [$this, 'changeButtonText'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'changeButtonText'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'changeButtonTextForVariableProducts'], 10, 3);
        
        // Additional information display
        add_action('woocommerce_before_add_to_cart_form', [$this, 'renderAdditionalInformationBeforeCart']);
        add_action('woocommerce_after_add_to_cart_form', [$this, 'renderAdditionalInformationAfterCart']);
        add_filter('woocommerce_get_item_data', [$this, 'handleCartMessage'], 10, 2);
        
        // Order status and display
        add_action('woocommerce_order_item_meta_end', [$this, 'renderOrderItemMetaEnd'], 10, 4);
        add_action('woocommerce_shop_loop_item_title', [$this, 'renderShopLoopItemTitle']);
        
        // Admin columns
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'addShopOrderColumn'], 11);
        add_filter('manage_edit-shop_order_columns', [$this, 'addShopOrderColumn'], 11);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'renderShopOrderColumnContent'], 10, 2);
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderShopOrderColumnContent'], 10, 2);

        // Blocks integration
        add_filter('render_block_context', [$this, 'addBlockTitleFilter']);
        add_filter('woocommerce_blocks_product_grid_item_html', [$this, 'overrideProductGridBlock'], PHP_INT_MAX, 3);

        // Price handling
        add_filter('woocommerce_get_price_html', [$this, 'renderDynamicDiscountPriceHtml'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'applyDynamicDiscountCartPrice']);

        // Analytics
        add_action('woocommerce_after_calculate_totals', [$this, 'updateAnalytics']);

        if (is_admin()) {
            $this->registerAdminHooks();
        }
    }

    /**
     * Register admin-specific hooks.
     */
    private function registerAdminHooks(): void
    {
        if (!$this->isModuleSettingsPage()) {
            return;
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
        add_filter('mesmeric_custom_css', [$this, 'injectAdminCustomCss']);
        add_action('mesmeric_admin_before_include_modules_options', [$this, 'renderHelpBanner']);
    }

    /**
     * Setup cron job for checking pre-orders.
     */
    private function setupCronJob(): void
    {
        if (!wp_next_scheduled('check_for_released_preorders')) {
            wp_schedule_event(time(), 'twicedaily', 'check_for_released_preorders');
        }
        add_action('check_for_released_preorders', [$this, 'checkAndUpdatePreOrderStatus']);
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
        return __('Pre Orders', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Allow customers to pre-order products before they are available.', 'mesmeric-commerce');
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
     *
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
    {
        return [
            'button_text' => __('Pre Order Now!', 'mesmeric-commerce'),
            'additional_text' => __('Ships on {date}.', 'mesmeric-commerce'),
            'cart_label_text' => __('Pre-order', 'mesmeric-commerce'),
            'rules' => [
                [
                    'layout' => 'display',
                    'condition' => 'all',
                    'type' => 'include',
                    'button_text' => __('Pre Order Now!', 'mesmeric-commerce'),
                    'additional_text' => __('Ships on {date}.', 'mesmeric-commerce'),
                    'cart_label_text' => __('Pre-order', 'mesmeric-commerce'),
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

    /**
     * Convert timestamp to human readable date.
     */
    private function convertTimestampToHumanReadable(int $timestamp): string
    {
        $timezone = new DateTimeZone(wp_timezone_string());
        $date = new DateTime('now', $timezone);
        $date->setTimestamp($timestamp);

        return $date->format(self::DATE_TIME_FORMAT);
    }
}
