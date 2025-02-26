<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\CartReservedTimer;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Core\Assets\AssetsManager;

/**
 * Cart Reserved Timer Module
 *
 * Displays a countdown timer for cart reservation to create urgency and reduce cart abandonment.
 *
 * @since 1.0.0
 */
class CartReservedTimerModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'cart-reserved-timer';
    private const MODULE_TEMPLATES_PATH = 'modules/' . self::MODULE_ID;
    private bool $isModulePreview = false;

    private TemplateRenderer $templateRenderer;
    private Translator $translator;
    private AssetsManager $assetsManager;

    /**
     * Constructor.
     */
    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer,
        Translator $translator,
        AssetsManager $assetsManager
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->translator = $translator;
        $this->assetsManager = $assetsManager;

        parent::__construct($optionsManager);

        if ($this->isModulePreview()) {
            $this->isModulePreview = true;
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
        }

        if ($this->isActive()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
            add_action('woocommerce_before_cart', [$this, 'renderTimer']);
            add_action('wp_ajax_clear_cart', [$this, 'handleClearCart']);
            add_action('wp_ajax_nopriv_clear_cart', [$this, 'handleClearCart']);

            if (is_admin()) {
                $this->initTranslations();
            }
        }
    }

    /**
     * Initialize translations.
     */
    private function initTranslations(): void
    {
        $settings = $this->getSettings();
        
        if (!empty($settings['reserved_message'])) {
            $this->translator->registerString(
                $settings['reserved_message'],
                __('Cart Reserved Timer: Cart reserved message', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['timer_message_minutes'])) {
            $this->translator->registerString(
                $settings['timer_message_minutes'],
                __('Cart Reserved Timer: Timer message for > 1 min', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['timer_message_seconds'])) {
            $this->translator->registerString(
                $settings['timer_message_seconds'],
                __('Cart Reserved Timer: Timer message for < 1 min', 'mesmeric-commerce')
            );
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        if (!$this->isModuleSettingsPage()) {
            return;
        }

        $this->assetsManager->enqueueStyle(
            'mesmeric-cart-reserved-timer-admin',
            'modules/cart-reserved-timer/css/admin.css'
        );

        $this->assetsManager->enqueueScript(
            'mesmeric-cart-reserved-timer-admin',
            'modules/cart-reserved-timer/js/admin.js',
            ['alpine']
        );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueFrontendAssets(): void
    {
        if (!is_cart()) {
            return;
        }

        $this->assetsManager->enqueueStyle(
            'mesmeric-cart-reserved-timer',
            'modules/cart-reserved-timer/css/cart-reserved-timer.css'
        );

        $this->assetsManager->enqueueScript(
            'mesmeric-cart-reserved-timer',
            'modules/cart-reserved-timer/js/cart-reserved-timer.js',
            ['alpine'],
            [
                'duration' => $this->getSettings()['duration'] * 60,
                'timeExpires' => $this->getSettings()['time_expires'],
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cart_reserved_timer'),
            ]
        );
    }

    /**
     * Handle cart clearing via AJAX.
     */
    public function handleClearCart(): void
    {
        check_ajax_referer('cart_reserved_timer', 'nonce');

        WC()->cart->empty_cart();
        wp_send_json_success();
    }

    /**
     * Render the timer.
     */
    public function renderTimer(): void
    {
        if (!is_cart() || WC()->cart->is_empty()) {
            return;
        }

        echo $this->templateRenderer->render(
            '@cart-reserved-timer/timer.twig',
            [
                'settings' => $this->getSettings(),
                'icon' => $this->getIcon($this->getSettings()['icon']),
            ]
        );
    }

    /**
     * Get icon URL.
     */
    private function getIcon(string $icon): string
    {
        $icons = [
            'none' => 'cancel.svg',
            'fire' => 'fire.svg',
            'clock' => 'clock.svg',
            'hour-glass' => 'hour-glass.svg',
        ];

        return $this->assetsManager->getAssetUrl(
            "modules/cart-reserved-timer/images/icons/{$icons[$icon]}"
        );
    }

    /**
     * Render admin preview.
     */
    public function renderAdminPreview(string $preview, string $moduleId): string
    {
        if ($moduleId !== self::MODULE_ID) {
            return $preview;
        }

        ob_start();
        $this->renderTimer();
        return ob_get_clean();
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
        return __('Cart Reserved Timer', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Display a countdown timer for cart reservation to create urgency and reduce cart abandonment.', 'mesmeric-commerce');
    }

    /**
     * Get default settings.
     */
    public function getDefaultSettings(): array
    {
        return [
            'duration' => 10,
            'reserved_message' => __('An item in your cart is in high demand.', 'mesmeric-commerce'),
            'timer_message_minutes' => __('Your cart is saved for {timer} minutes!', 'mesmeric-commerce'),
            'timer_message_seconds' => __('Your cart is saved for {timer} seconds!', 'mesmeric-commerce'),
            'time_expires' => 'clear-cart',
            'icon' => 'fire',
            'background_color' => '#f4f6f8',
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
