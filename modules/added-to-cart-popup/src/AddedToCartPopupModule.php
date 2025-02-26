<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\AddedToCartPopup;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;

/**
 * Added to Cart Popup Module
 *
 * Provides functionality for displaying a popup when products are added to cart.
 *
 * @since 1.0.0
 */
class AddedToCartPopupModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'added-to-cart-popup';
    private const MODULE_TEMPLATES_PATH = 'modules/' . self::MODULE_ID;
    private bool $isModulePreview = false;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    /**
     * Constructor.
     *
     * @param OptionsManager $optionsManager The options manager instance.
     * @param TemplateRenderer $templateRenderer The template renderer instance.
     * @param Translator $translator The translator instance.
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
    }

    /**
     * Initialize translations.
     */
    private function initTranslations(): void
    {
        $settings = $this->getSettings();
        
        if (!empty($settings['popup_message'])) {
            $this->translator->registerString(
                $settings['popup_message'],
                __('Add to cart popup: header message text', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['view_cart_button_label'])) {
            $this->translator->registerString(
                $settings['view_cart_button_label'],
                __('Add to cart popup: view cart button label', 'mesmeric-commerce')
            );
        }

        if (!empty($settings['view_continue_shopping_button_label'])) {
            $this->translator->registerString(
                $settings['view_continue_shopping_button_label'],
                __('Add to cart popup: continue shopping button label', 'mesmeric-commerce')
            );
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-added-to-cart-popup-admin',
            $this->getModuleUrl() . '/assets/css/admin.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-added-to-cart-popup-admin',
            $this->getModuleUrl() . '/assets/js/admin.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
    }

    /**
     * Render the popup.
     *
     * @param array $args The template arguments.
     */
    public function renderPopup(array $args = []): void
    {
        $settings = $this->getSettings();
        $layout = $settings['layout'] ?? 'layout-1';

        echo $this->templateRenderer->render(
            "@added-to-cart-popup/layouts/{$layout}.twig",
            array_merge($args, ['settings' => $settings])
        );
    }

    /**
     * Render admin preview.
     *
     * @param string $preview The preview HTML.
     * @param string $moduleId The module ID.
     * @return string The modified preview HTML.
     */
    public function renderAdminPreview(string $preview, string $moduleId): string
    {
        if ($moduleId !== self::MODULE_ID) {
            return $preview;
        }

        ob_start();
        $this->renderPopup(['is_preview' => true]);
        return ob_get_clean();
    }

    /**
     * Get module ID.
     *
     * @return string The module ID.
     */
    public function getModuleId(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Get module name.
     *
     * @return string The module name.
     */
    public function getModuleName(): string
    {
        return __('Added to Cart Popup', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     *
     * @return string The module description.
     */
    public function getModuleDescription(): string
    {
        return __('Display a popup when products are added to cart.', 'mesmeric-commerce');
    }

    /**
     * Get module settings.
     *
     * @return array The module settings.
     */
    public function getDefaultSettings(): array
    {
        return [
            'layout' => 'layout-1',
            'popup_message' => __('Item has been added to your cart', 'mesmeric-commerce'),
            'view_cart_button_label' => __('View Cart', 'mesmeric-commerce'),
            'view_continue_shopping_button_label' => __('Continue Shopping', 'mesmeric-commerce'),
        ];
    }

    /**
     * Check if WooCommerce is required.
     *
     * @return bool True if WooCommerce is required.
     */
    public function requiresWooCommerce(): bool
    {
        return true;
    }
}
