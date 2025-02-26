<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\LoginPopup\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Login Popup Module Options
 *
 * @since 1.0.0
 */
class ModuleOptions extends AbstractOptions
{
    /**
     * Get the option group
     *
     * @return string
     */
    public function getGroup(): string
    {
        return 'login_popup';
    }

    /**
     * Get the option fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return [
            'trigger_section' => [
                'type' => 'section',
                'title' => __('Trigger Settings', 'mesmeric-commerce'),
                'description' => __('Configure how the login popup is triggered', 'mesmeric-commerce'),
                'fields' => [
                    'trigger_type' => [
                        'type' => 'select',
                        'label' => __('Trigger Type', 'mesmeric-commerce'),
                        'description' => __('How the login popup should be triggered', 'mesmeric-commerce'),
                        'default' => 'button',
                        'options' => [
                            'button' => __('Button', 'mesmeric-commerce'),
                            'link' => __('Text Link', 'mesmeric-commerce'),
                            'auto' => __('Automatic', 'mesmeric-commerce'),
                        ],
                    ],
                    'trigger_text' => [
                        'type' => 'text',
                        'label' => __('Trigger Text', 'mesmeric-commerce'),
                        'description' => __('Text to display on the trigger button/link', 'mesmeric-commerce'),
                        'default' => __('Login / Register', 'mesmeric-commerce'),
                        'condition' => [
                            'trigger_type' => ['button', 'link'],
                        ],
                    ],
                    'trigger_icon' => [
                        'type' => 'select',
                        'label' => __('Trigger Icon', 'mesmeric-commerce'),
                        'description' => __('Icon to display on the trigger button/link', 'mesmeric-commerce'),
                        'default' => 'user',
                        'options' => [
                            '' => __('No Icon', 'mesmeric-commerce'),
                            'user' => __('User', 'mesmeric-commerce'),
                            'lock' => __('Lock', 'mesmeric-commerce'),
                            'key' => __('Key', 'mesmeric-commerce'),
                            'sign-in' => __('Sign In', 'mesmeric-commerce'),
                        ],
                        'condition' => [
                            'trigger_type' => ['button', 'link'],
                        ],
                    ],
                    'trigger_position' => [
                        'type' => 'select',
                        'label' => __('Trigger Position', 'mesmeric-commerce'),
                        'description' => __('Where to place the trigger button/link', 'mesmeric-commerce'),
                        'default' => 'menu',
                        'options' => [
                            'menu' => __('Primary Menu', 'mesmeric-commerce'),
                            'header' => __('Header', 'mesmeric-commerce'),
                            'footer' => __('Footer', 'mesmeric-commerce'),
                            'custom' => __('Custom Position', 'mesmeric-commerce'),
                        ],
                        'condition' => [
                            'trigger_type' => ['button', 'link'],
                        ],
                    ],
                    'trigger_selector' => [
                        'type' => 'text',
                        'label' => __('Custom Position Selector', 'mesmeric-commerce'),
                        'description' => __('CSS selector for custom position (e.g., ".site-header")', 'mesmeric-commerce'),
                        'default' => '',
                        'condition' => [
                            'trigger_position' => ['custom'],
                            'trigger_type' => ['button', 'link'],
                        ],
                    ],
                    'auto_trigger_delay' => [
                        'type' => 'number',
                        'label' => __('Auto-Trigger Delay (seconds)', 'mesmeric-commerce'),
                        'description' => __('Seconds to wait before automatically showing the popup', 'mesmeric-commerce'),
                        'default' => 5,
                        'min' => 0,
                        'max' => 60,
                        'condition' => [
                            'trigger_type' => ['auto'],
                        ],
                    ],
                    'auto_trigger_pages' => [
                        'type' => 'multiselect',
                        'label' => __('Auto-Trigger Pages', 'mesmeric-commerce'),
                        'description' => __('Pages to automatically show the popup on (leave empty for all pages)', 'mesmeric-commerce'),
                        'default' => [],
                        'options_callback' => [$this, 'getPageOptions'],
                        'condition' => [
                            'trigger_type' => ['auto'],
                        ],
                    ],
                    'auto_trigger_once' => [
                        'type' => 'toggle',
                        'label' => __('Auto-Trigger Once Per Session', 'mesmeric-commerce'),
                        'description' => __('Only show the popup once per browser session', 'mesmeric-commerce'),
                        'default' => true,
                        'condition' => [
                            'trigger_type' => ['auto'],
                        ],
                    ],
                ],
            ],
            'content_section' => [
                'type' => 'section',
                'title' => __('Content Settings', 'mesmeric-commerce'),
                'description' => __('Configure the content of the login popup', 'mesmeric-commerce'),
                'fields' => [
                    'show_registration' => [
                        'type' => 'toggle',
                        'label' => __('Show Registration Form', 'mesmeric-commerce'),
                        'description' => __('Allow new users to register through the popup', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_social_login' => [
                        'type' => 'toggle',
                        'label' => __('Show Social Login', 'mesmeric-commerce'),
                        'description' => __('Show social login options if available', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_password_reset' => [
                        'type' => 'toggle',
                        'label' => __('Show Password Reset', 'mesmeric-commerce'),
                        'description' => __('Allow users to reset their password', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'modal_width' => [
                        'type' => 'select',
                        'label' => __('Modal Width', 'mesmeric-commerce'),
                        'description' => __('Width of the login popup modal', 'mesmeric-commerce'),
                        'default' => 'medium',
                        'options' => [
                            'small' => __('Small', 'mesmeric-commerce'),
                            'medium' => __('Medium', 'mesmeric-commerce'),
                            'large' => __('Large', 'mesmeric-commerce'),
                        ],
                    ],
                    'show_when_logged_in' => [
                        'type' => 'toggle',
                        'label' => __('Show When Logged In', 'mesmeric-commerce'),
                        'description' => __('Show the login popup even when the user is already logged in', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                ],
            ],
            'redirect_section' => [
                'type' => 'section',
                'title' => __('Redirect Settings', 'mesmeric-commerce'),
                'description' => __('Configure where to redirect users after login', 'mesmeric-commerce'),
                'fields' => [
                    'redirect_after_login' => [
                        'type' => 'select',
                        'label' => __('Redirect After Login', 'mesmeric-commerce'),
                        'description' => __('Where to redirect users after successful login', 'mesmeric-commerce'),
                        'default' => 'current',
                        'options' => [
                            'current' => __('Current Page', 'mesmeric-commerce'),
                            'home' => __('Home Page', 'mesmeric-commerce'),
                            'account' => __('My Account Page', 'mesmeric-commerce'),
                            'custom' => __('Custom URL', 'mesmeric-commerce'),
                        ],
                    ],
                    'custom_redirect' => [
                        'type' => 'text',
                        'label' => __('Custom Redirect URL', 'mesmeric-commerce'),
                        'description' => __('Custom URL to redirect to after login', 'mesmeric-commerce'),
                        'default' => '',
                        'condition' => [
                            'redirect_after_login' => ['custom'],
                        ],
                    ],
                ],
            ],
            'security_section' => [
                'type' => 'section',
                'title' => __('Security Settings', 'mesmeric-commerce'),
                'description' => __('Configure security settings for the login popup', 'mesmeric-commerce'),
                'fields' => [
                    'enable_recaptcha' => [
                        'type' => 'toggle',
                        'label' => __('Enable reCAPTCHA', 'mesmeric-commerce'),
                        'description' => __('Enable Google reCAPTCHA to protect against spam and abuse', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'recaptcha_site_key' => [
                        'type' => 'text',
                        'label' => __('reCAPTCHA Site Key', 'mesmeric-commerce'),
                        'description' => __('Google reCAPTCHA site key', 'mesmeric-commerce'),
                        'default' => '',
                        'condition' => [
                            'enable_recaptcha' => [true],
                        ],
                    ],
                    'recaptcha_secret_key' => [
                        'type' => 'text',
                        'label' => __('reCAPTCHA Secret Key', 'mesmeric-commerce'),
                        'description' => __('Google reCAPTCHA secret key', 'mesmeric-commerce'),
                        'default' => '',
                        'condition' => [
                            'enable_recaptcha' => [true],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get page options for multiselect
     *
     * @return array
     */
    public function getPageOptions(): array
    {
        $pages = get_pages([
            'post_status' => 'publish',
            'sort_column' => 'post_title',
            'sort_order' => 'ASC',
        ]);

        $options = [];
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }

        return $options;
    }
}
