<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\QuickSocialLinks;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;

/**
 * Quick Social Links Module
 *
 * Provides floating social media links with customizable position and styling.
 */
class QuickSocialLinksModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'quick-social-links';
    private const MODULE_SECTION = 'improve-experience';
    private bool $isModulePreview = false;
    private TemplateRenderer $templateRenderer;

    /**
     * Constructor.
     */
    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer
    ) {
        $this->templateRenderer = $templateRenderer;
        parent::__construct($optionsManager);

        if ($this->isModulePreview()) {
            $this->isModulePreview = true;
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
        }

        if (!$this->isActive()) {
            return;
        }

        // Frontend hooks
        if (!is_admin() || wp_doing_ajax()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
            add_action('wp_footer', [$this, 'renderSocialLinks']);
            add_filter('mesmeric_custom_css', [$this, 'injectCustomCss']);
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-quick-social-links-admin',
            $this->getModuleUrl() . '/assets/css/admin.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-quick-social-links-admin',
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
        wp_enqueue_style(
            'mesmeric-quick-social-links',
            $this->getModuleUrl() . '/assets/css/quick-social-links.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-quick-social-links',
            $this->getModuleUrl() . '/assets/js/quick-social-links.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
    }

    /**
     * Render social links.
     */
    public function renderSocialLinks(): void
    {
        $settings = $this->getSettings();
        
        if (!$this->shouldDisplayLinks()) {
            return;
        }

        echo $this->templateRenderer->render('@quick-social-links/social-links.twig', [
            'settings' => $settings,
            'links' => $this->getFilteredLinks($settings['links'] ?? []),
            'classes' => $this->getContainerClasses($settings),
        ]);
    }

    /**
     * Check if links should be displayed based on visibility rules.
     */
    private function shouldDisplayLinks(): bool
    {
        $settings = $this->getSettings();
        $rules = $settings['condition_rules'] ?? [];

        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $rule) {
            if ($rule['type'] === 'exclude' && $this->matchesRule($rule)) {
                return false;
            }
            if ($rule['type'] === 'include' && !$this->matchesRule($rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current page matches a rule.
     *
     * @param array<string, mixed> $rule
     */
    private function matchesRule(array $rule): bool
    {
        if ($rule['condition'] === 'all') {
            return true;
        }

        // Add specific page type checks based on WooCommerce/WordPress functions
        return match ($rule['condition']) {
            'product' => is_product(),
            'shop' => is_shop(),
            'cart' => is_cart(),
            'checkout' => is_checkout(),
            'account' => is_account_page(),
            default => false,
        };
    }

    /**
     * Get filtered and sanitized links.
     *
     * @param array<int, array<string, mixed>> $links
     * @return array<int, array{icon: string, url: string, type: string}>
     */
    private function getFilteredLinks(array $links): array
    {
        return array_map(function (array $link) {
            return [
                'icon' => sanitize_text_field($link['icon'] ?? ''),
                'url' => esc_url($link['url'] ?? ''),
                'type' => $link['layout'] ?? 'social',
            ];
        }, $links);
    }

    /**
     * Get container classes based on settings.
     *
     * @param array<string, mixed> $settings
     * @return string
     */
    private function getContainerClasses(array $settings): string
    {
        $classes = [
            'mesmeric-quick-social-links',
            'mesmeric-quick-social-links__regular',
            $settings['layout'] ?? 'pos-bottom',
            $settings['visibility'] ?? 'visibility-all',
        ];

        return implode(' ', array_filter($classes));
    }

    /**
     * Inject custom CSS for social links.
     *
     * @param string $css
     * @return string
     */
    public function injectCustomCss(string $css): string
    {
        $settings = $this->getSettings();
        
        $customCss = "
            .mesmeric-quick-social-links-inner {
                --mesmeric-border-radius: {$settings['border_radius']}px;
                --mesmeric-icon-color: {$settings['icon_color']};
                --mesmeric-bg-color: {$settings['bg_color']};
            }
        ";

        return $css . $customCss;
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
        return __('Quick Social Links', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Add floating social media links with customizable position and styling.', 'mesmeric-commerce');
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
            'layout' => 'pos-bottom',
            'visibility' => 'visibility-all',
            'icon_color' => '#212121',
            'bg_color' => '#ffffff',
            'border_radius' => 15,
            'condition_rules' => [
                [
                    'layout' => 'display',
                    'condition' => 'all',
                    'type' => 'include',
                ],
            ],
            'links' => [
                [
                    'layout' => 'social',
                    'icon' => 'facebook',
                    'url' => 'https://www.facebook.com',
                ],
                [
                    'layout' => 'social',
                    'icon' => 'instagram',
                    'url' => 'https://www.instagram.com',
                ],
                [
                    'layout' => 'social',
                    'icon' => 'twitter',
                    'url' => 'https://www.twitter.com',
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
