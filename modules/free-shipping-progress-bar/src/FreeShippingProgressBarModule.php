<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\FreeShippingProgressBar;

use MesmericCommerce\Core\Assets\AssetsManager;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use MesmericCommerce\Modules\FreeShippingProgressBar\Options\ModuleOptions;

/**
 * Free Shipping Progress Bar Module
 * 
 * Displays a progress bar showing how close the customer is to qualifying for free shipping.
 */
class FreeShippingProgressBarModule
{
    private const MODULE_ID = 'free-shipping-progress-bar';
    private const MODULE_SECTION = 'boost-revenue';

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

        // Add progress bar to cart and checkout pages
        add_action('woocommerce_before_cart', [$this, 'renderProgressBar']);
        add_action('woocommerce_before_checkout_form', [$this, 'renderProgressBar']);

        // Add progress bar to mini cart
        add_action('woocommerce_before_mini_cart', [$this, 'renderProgressBar']);

        // Update progress bar on cart update
        add_action('woocommerce_cart_updated', [$this, 'updateProgressBar']);
        add_filter('woocommerce_update_cart_action_cart_updated', [$this, 'updateProgressBar']);
    }

    /**
     * Initialize translations
     */
    public function initTranslations(): void
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);
        
        if (!empty($settings['message_initial'])) {
            $this->translator->registerString(
                $settings['message_initial'],
                'Free shipping progress bar: Initial message'
            );
        }

        if (!empty($settings['message_progress'])) {
            $this->translator->registerString(
                $settings['message_progress'],
                'Free shipping progress bar: Progress message'
            );
        }

        if (!empty($settings['message_achieved'])) {
            $this->translator->registerString(
                $settings['message_achieved'],
                'Free shipping progress bar: Achieved message'
            );
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void
    {
        if (!is_cart() && !is_checkout()) {
            return;
        }

        $this->assetsManager->enqueueStyle(
            'mesmeric-free-shipping-progress-bar',
            'modules/free-shipping-progress-bar/css/free-shipping-progress-bar.css'
        );

        $this->assetsManager->enqueueScript(
            'mesmeric-free-shipping-progress-bar',
            'modules/free-shipping-progress-bar/js/free-shipping-progress-bar.js',
            ['alpine'],
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('free_shipping_progress_bar_nonce'),
            ]
        );
    }

    /**
     * Get free shipping minimum amount
     */
    private function getFreeShippingMinAmount(): float
    {
        $freeShippingMethods = array_filter(WC()->shipping()->get_shipping_methods(), function($method) {
            return $method instanceof \WC_Shipping_Free_Shipping;
        });

        if (empty($freeShippingMethods)) {
            return 0;
        }

        $minAmount = PHP_FLOAT_MAX;
        foreach ($freeShippingMethods as $method) {
            if ($method->requires === 'min_amount' && $method->min_amount < $minAmount) {
                $minAmount = (float) $method->min_amount;
            }
        }

        return $minAmount === PHP_FLOAT_MAX ? 0 : $minAmount;
    }

    /**
     * Get cart subtotal
     */
    private function getCartSubtotal(): float
    {
        return (float) WC()->cart->get_displayed_subtotal();
    }

    /**
     * Calculate progress percentage
     */
    private function calculateProgress(float $subtotal, float $minAmount): float
    {
        if ($minAmount <= 0) {
            return 100;
        }

        $progress = ($subtotal / $minAmount) * 100;
        return min(100, max(0, $progress));
    }

    /**
     * Get progress bar message
     */
    private function getProgressMessage(float $subtotal, float $minAmount): string
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);
        $remaining = $minAmount - $subtotal;

        if ($remaining <= 0) {
            return $this->translator->translate($settings['message_achieved']);
        }

        if ($subtotal <= 0) {
            $message = $settings['message_initial'];
        } else {
            $message = $settings['message_progress'];
        }

        return str_replace(
            ['{remaining}', '{amount}'],
            [wc_price($remaining), wc_price($minAmount)],
            $this->translator->translate($message)
        );
    }

    /**
     * Render progress bar
     */
    public function renderProgressBar(): void
    {
        $minAmount = $this->getFreeShippingMinAmount();
        if ($minAmount <= 0) {
            return;
        }

        $subtotal = $this->getCartSubtotal();
        $progress = $this->calculateProgress($subtotal, $minAmount);
        $message = $this->getProgressMessage($subtotal, $minAmount);

        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        $this->templateRenderer->render('@free-shipping-progress-bar/progress-bar.twig', [
            'settings' => $settings,
            'progress' => $progress,
            'message' => $message,
            'subtotal' => $subtotal,
            'min_amount' => $minAmount,
            'remaining' => max(0, $minAmount - $subtotal),
        ]);

        if ($progress >= 100) {
            $this->analyticsLogger->logEvent('free_shipping_achieved', [
                'cart_total' => $subtotal,
                'threshold' => $minAmount,
            ]);
        }
    }

    /**
     * Update progress bar via AJAX
     */
    public function updateProgressBar(): void
    {
        $this->renderProgressBar();
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
