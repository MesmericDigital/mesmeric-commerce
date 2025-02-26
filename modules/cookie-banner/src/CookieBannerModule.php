<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\CookieBanner;

use MesmericCommerce\Core\Assets\AssetsManager;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use MesmericCommerce\Modules\CookieBanner\Options\ModuleOptions;

/**
 * Cookie Banner Module
 * 
 * Displays a GDPR-compliant cookie consent banner with customizable appearance and behavior.
 */
class CookieBannerModule
{
    private const MODULE_ID = 'cookie-banner';
    private const MODULE_SECTION = 'protect-your-store';

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

        // Render cookie banner
        add_action('wp_footer', [$this, 'renderCookieBanner']);
    }

    /**
     * Initialize translations
     */
    public function initTranslations(): void
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);
        
        if (!empty($settings['bar_text'])) {
            $this->translator->registerString(
                $settings['bar_text'],
                'Cookie banner: Bar text'
            );
        }

        if (!empty($settings['privacy_policy_text'])) {
            $this->translator->registerString(
                $settings['privacy_policy_text'],
                'Cookie banner: Privacy policy text'
            );
        }

        if (!empty($settings['button_text'])) {
            $this->translator->registerString(
                $settings['button_text'],
                'Cookie banner: Button text'
            );
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        $this->assetsManager->enqueueStyle(
            'mesmeric-cookie-banner',
            'modules/cookie-banner/css/cookie-banner.css'
        );

        $this->assetsManager->enqueueScript(
            'mesmeric-cookie-banner',
            'modules/cookie-banner/js/cookie-banner.js',
            ['alpine'],
            [
                'cookieDuration' => (int) $settings['cookie_duration'],
                'translations' => [
                    'accept' => $this->translator->translate($settings['button_text']),
                    'learnMore' => $this->translator->translate($settings['privacy_policy_text']),
                ],
            ]
        );
    }

    /**
     * Render cookie banner
     */
    public function renderCookieBanner(): void
    {
        $settings = $this->optionsManager->getModuleSettings(self::MODULE_ID);

        $this->templateRenderer->render('@cookie-banner/banner.twig', [
            'settings' => $settings,
            'bar_text' => $this->translator->translate($settings['bar_text']),
            'privacy_policy_text' => $this->translator->translate($settings['privacy_policy_text']),
            'privacy_policy_url' => $this->translator->translate($settings['privacy_policy_url']),
            'button_text' => $this->translator->translate($settings['button_text']),
        ]);

        // Log analytics event when banner is shown
        $this->analyticsLogger->logEvent('cookie_banner_shown', [
            'theme' => $settings['theme'],
            'has_privacy_policy' => !empty($settings['privacy_policy_url']),
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
}
