<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\CookieBanner\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Cookie Banner Module Options
 */
class ModuleOptions extends AbstractOptions
{
    /**
     * Get module settings fields
     */
    public function getFields(): array
    {
        return [
            'settings' => [
                'title' => __('Settings', 'mesmeric-commerce'),
                'fields' => [
                    'theme' => [
                        'type' => 'select',
                        'title' => __('Theme', 'mesmeric-commerce'),
                        'default' => 'floating',
                        'options' => [
                            'floating' => __('Floating', 'mesmeric-commerce'),
                            'fixed-bottom' => __('Fixed bottom', 'mesmeric-commerce'),
                            'minimal' => __('Minimal', 'mesmeric-commerce'),
                            'centered' => __('Centered', 'mesmeric-commerce'),
                        ],
                    ],
                    'bar_text' => [
                        'type' => 'text',
                        'title' => __('Bar text', 'mesmeric-commerce'),
                        'default' => __('🍪 We use cookies to improve your experience on our site.', 'mesmeric-commerce'),
                        'description' => __('Main message shown in the cookie banner.', 'mesmeric-commerce'),
                    ],
                    'privacy_policy_text' => [
                        'type' => 'text',
                        'title' => __('Privacy policy text', 'mesmeric-commerce'),
                        'default' => __('Learn More', 'mesmeric-commerce'),
                        'description' => __('Text for the privacy policy link.', 'mesmeric-commerce'),
                    ],
                    'privacy_policy_url' => [
                        'type' => 'url',
                        'title' => __('Privacy policy URL', 'mesmeric-commerce'),
                        'default' => get_privacy_policy_url(),
                        'placeholder' => 'https://example.com/privacy-policy',
                        'description' => __('Link to your privacy policy page.', 'mesmeric-commerce'),
                    ],
                    'button_text' => [
                        'type' => 'text',
                        'title' => __('Accept button text', 'mesmeric-commerce'),
                        'default' => __('I understand', 'mesmeric-commerce'),
                        'description' => __('Text for the accept button.', 'mesmeric-commerce'),
                    ],
                    'cookie_duration' => [
                        'type' => 'number',
                        'title' => __('Cookie duration (days)', 'mesmeric-commerce'),
                        'default' => 365,
                        'min' => 1,
                        'max' => 730,
                        'step' => 1,
                        'description' => __('How long to remember the user\'s choice (in days).', 'mesmeric-commerce'),
                    ],
                    'show_close_button' => [
                        'type' => 'toggle',
                        'title' => __('Show close button', 'mesmeric-commerce'),
                        'default' => true,
                        'description' => __('Show an "X" close button.', 'mesmeric-commerce'),
                    ],
                ],
            ],
            'appearance' => [
                'title' => __('Appearance', 'mesmeric-commerce'),
                'fields' => [
                    'color_scheme' => [
                        'type' => 'select',
                        'title' => __('Color scheme', 'mesmeric-commerce'),
                        'default' => 'auto',
                        'options' => [
                            'auto' => __('Auto (follows system)', 'mesmeric-commerce'),
                            'light' => __('Light', 'mesmeric-commerce'),
                            'dark' => __('Dark', 'mesmeric-commerce'),
                        ],
                    ],
                    'background_color' => [
                        'type' => 'color',
                        'title' => __('Background color', 'mesmeric-commerce'),
                        'default' => '#000000',
                        'css_var' => '--mesmeric-cookie-banner-bg',
                    ],
                    'text_color' => [
                        'type' => 'color',
                        'title' => __('Text color', 'mesmeric-commerce'),
                        'default' => '#FFFFFF',
                        'css_var' => '--mesmeric-cookie-banner-text',
                    ],
                    'button_background' => [
                        'type' => 'color',
                        'title' => __('Button background', 'mesmeric-commerce'),
                        'default' => '#FFFFFF',
                        'css_var' => '--mesmeric-cookie-banner-btn-bg',
                    ],
                    'button_text_color' => [
                        'type' => 'color',
                        'title' => __('Button text color', 'mesmeric-commerce'),
                        'default' => '#000000',
                        'css_var' => '--mesmeric-cookie-banner-btn-text',
                    ],
                    'link_color' => [
                        'type' => 'color',
                        'title' => __('Link color', 'mesmeric-commerce'),
                        'default' => '#AEAEAE',
                        'css_var' => '--mesmeric-cookie-banner-link',
                    ],
                    'border_radius' => [
                        'type' => 'range',
                        'title' => __('Border radius', 'mesmeric-commerce'),
                        'default' => 10,
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                        'unit' => 'px',
                        'css_var' => '--mesmeric-cookie-banner-radius',
                    ],
                    'max_width' => [
                        'type' => 'range',
                        'title' => __('Maximum width', 'mesmeric-commerce'),
                        'default' => 750,
                        'min' => 300,
                        'max' => 2000,
                        'step' => 10,
                        'unit' => 'px',
                        'css_var' => '--mesmeric-cookie-banner-max-width',
                        'condition' => ['theme', 'in', ['floating', 'centered']],
                    ],
                    'z_index' => [
                        'type' => 'range',
                        'title' => __('Z-index', 'mesmeric-commerce'),
                        'default' => 9999,
                        'min' => 1,
                        'max' => 99999,
                        'step' => 1,
                        'css_var' => '--mesmeric-cookie-banner-z-index',
                    ],
                ],
            ],
            'advanced' => [
                'title' => __('Advanced', 'mesmeric-commerce'),
                'fields' => [
                    'animation_duration' => [
                        'type' => 'range',
                        'title' => __('Animation duration', 'mesmeric-commerce'),
                        'default' => 300,
                        'min' => 0,
                        'max' => 1000,
                        'step' => 50,
                        'unit' => 'ms',
                        'css_var' => '--mesmeric-cookie-banner-animation-duration',
                    ],
                    'delay_before_show' => [
                        'type' => 'range',
                        'title' => __('Delay before showing', 'mesmeric-commerce'),
                        'default' => 1000,
                        'min' => 0,
                        'max' => 10000,
                        'step' => 100,
                        'unit' => 'ms',
                        'description' => __('Delay before showing the banner after page load.', 'mesmeric-commerce'),
                    ],
                    'custom_css' => [
                        'type' => 'code',
                        'title' => __('Custom CSS', 'mesmeric-commerce'),
                        'language' => 'css',
                        'description' => __('Add custom CSS styles for the cookie banner.', 'mesmeric-commerce'),
                    ],
                ],
            ],
        ];
    }
}
