<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\LoginPopup\Options;

use MesmericCommerce\Includes\Options\MC_ModuleOptions;

/**
 * Login Popup Module Options
 *
 * Handles the settings for the login popup module.
 *
 * @since 1.0.0
 */
class LoginPopupOptions extends MC_ModuleOptions
{
    /**
     * Get the default settings for the module
     *
     * @return array The default settings
     */
    public function getDefaults(): array
    {
        return [
            'general_section' => [
                'enabled' => true,
                'recaptcha_enabled' => false,
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
            ],
            'content_section' => [
                'show_login_form' => true,
                'show_register_form' => true,
                'show_reset_form' => true,
                'show_when_logged_in' => false,
                'redirect_after_login' => false,
                'redirect_url' => '',
                'auto_trigger' => false,
                'auto_trigger_delay' => 5,
                'auto_trigger_once' => true,
            ],
            'trigger_section' => [
                'trigger_type' => 'button',
                'trigger_text' => 'Log In',
                'trigger_icon' => 'user',
                'trigger_position' => 'none',
            ],
            'style_section' => [
                'modal_size' => 'medium',
                'primary_color' => '#4a90e2',
                'custom_css' => '',
            ],
            'social_login_section' => [
                'enable_social_login' => false,
                'facebook_enabled' => false,
                'facebook_app_id' => '',
                'facebook_app_secret' => '',
                'google_enabled' => false,
                'google_client_id' => '',
                'google_client_secret' => '',
            ],
        ];
    }

    /**
     * Get the admin settings fields for the module
     *
     * @return array The admin settings fields
     */
    public function getAdminFields(): array
    {
        return [
            'general_section' => [
                'title' => __('General Settings', 'mesmeric-commerce'),
                'description' => __('Configure general settings for the login popup module.', 'mesmeric-commerce'),
                'fields' => [
                    'enabled' => [
                        'type' => 'toggle',
                        'label' => __('Enable Login Popup', 'mesmeric-commerce'),
                        'description' => __('Enable or disable the login popup module.', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'recaptcha_enabled' => [
                        'type' => 'toggle',
                        'label' => __('Enable reCAPTCHA', 'mesmeric-commerce'),
                        'description' => __('Enable Google reCAPTCHA to protect forms from spam and abuse.', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'recaptcha_site_key' => [
                        'type' => 'text',
                        'label' => __('reCAPTCHA Site Key', 'mesmeric-commerce'),
                        'description' => __('Enter your Google reCAPTCHA v2 site key.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'recaptcha_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'recaptcha_secret_key' => [
                        'type' => 'text',
                        'label' => __('reCAPTCHA Secret Key', 'mesmeric-commerce'),
                        'description' => __('Enter your Google reCAPTCHA v2 secret key.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'recaptcha_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                ],
            ],
            'content_section' => [
                'title' => __('Content Settings', 'mesmeric-commerce'),
                'description' => __('Configure the content and behavior of the login popup.', 'mesmeric-commerce'),
                'fields' => [
                    'show_login_form' => [
                        'type' => 'toggle',
                        'label' => __('Show Login Form', 'mesmeric-commerce'),
                        'description' => __('Show the login form in the popup.', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_register_form' => [
                        'type' => 'toggle',
                        'label' => __('Show Registration Form', 'mesmeric-commerce'),
                        'description' => __('Show the registration form in the popup.', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_reset_form' => [
                        'type' => 'toggle',
                        'label' => __('Show Password Reset Form', 'mesmeric-commerce'),
                        'description' => __('Show the password reset form in the popup.', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_when_logged_in' => [
                        'type' => 'toggle',
                        'label' => __('Show When Logged In', 'mesmeric-commerce'),
                        'description' => __('Show the login popup trigger even when the user is already logged in.', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'redirect_after_login' => [
                        'type' => 'toggle',
                        'label' => __('Redirect After Login', 'mesmeric-commerce'),
                        'description' => __('Redirect the user to a specific URL after successful login.', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'redirect_url' => [
                        'type' => 'text',
                        'label' => __('Redirect URL', 'mesmeric-commerce'),
                        'description' => __('The URL to redirect to after successful login. Leave empty to use the default behavior.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'redirect_after_login',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'auto_trigger' => [
                        'type' => 'toggle',
                        'label' => __('Auto Trigger', 'mesmeric-commerce'),
                        'description' => __('Automatically open the login popup when the page loads.', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'auto_trigger_delay' => [
                        'type' => 'number',
                        'label' => __('Auto Trigger Delay', 'mesmeric-commerce'),
                        'description' => __('The delay in seconds before automatically opening the popup.', 'mesmeric-commerce'),
                        'default' => 5,
                        'min' => 0,
                        'max' => 60,
                        'step' => 1,
                        'dependency' => [
                            'id' => 'auto_trigger',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'auto_trigger_once' => [
                        'type' => 'toggle',
                        'label' => __('Auto Trigger Once', 'mesmeric-commerce'),
                        'description' => __('Only automatically trigger the popup once per visitor.', 'mesmeric-commerce'),
                        'default' => true,
                        'dependency' => [
                            'id' => 'auto_trigger',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                ],
            ],
            'trigger_section' => [
                'title' => __('Trigger Settings', 'mesmeric-commerce'),
                'description' => __('Configure the appearance and behavior of the login popup trigger.', 'mesmeric-commerce'),
                'fields' => [
                    'trigger_type' => [
                        'type' => 'select',
                        'label' => __('Trigger Type', 'mesmeric-commerce'),
                        'description' => __('The type of trigger to use for opening the login popup.', 'mesmeric-commerce'),
                        'default' => 'button',
                        'options' => [
                            'button' => __('Button', 'mesmeric-commerce'),
                            'link' => __('Text Link', 'mesmeric-commerce'),
                            'icon' => __('Icon Only', 'mesmeric-commerce'),
                        ],
                    ],
                    'trigger_text' => [
                        'type' => 'text',
                        'label' => __('Trigger Text', 'mesmeric-commerce'),
                        'description' => __('The text to display on the trigger.', 'mesmeric-commerce'),
                        'default' => __('Log In', 'mesmeric-commerce'),
                        'dependency' => [
                            'id' => 'trigger_type',
                            'value' => 'icon',
                            'compare' => '!=',
                        ],
                    ],
                    'trigger_icon' => [
                        'type' => 'select',
                        'label' => __('Trigger Icon', 'mesmeric-commerce'),
                        'description' => __('The icon to display on the trigger.', 'mesmeric-commerce'),
                        'default' => 'user',
                        'options' => [
                            'none' => __('No Icon', 'mesmeric-commerce'),
                            'user' => __('User', 'mesmeric-commerce'),
                            'lock' => __('Lock', 'mesmeric-commerce'),
                            'key' => __('Key', 'mesmeric-commerce'),
                            'sign-in' => __('Sign In', 'mesmeric-commerce'),
                        ],
                    ],
                    'trigger_position' => [
                        'type' => 'select',
                        'label' => __('Trigger Position', 'mesmeric-commerce'),
                        'description' => __('The position of the trigger on the page. Select "None" to use shortcodes or hooks only.', 'mesmeric-commerce'),
                        'default' => 'none',
                        'options' => [
                            'none' => __('None (Manual Placement)', 'mesmeric-commerce'),
                            'header' => __('Header', 'mesmeric-commerce'),
                            'menu' => __('Primary Menu', 'mesmeric-commerce'),
                            'footer' => __('Footer', 'mesmeric-commerce'),
                        ],
                    ],
                ],
            ],
            'style_section' => [
                'title' => __('Style Settings', 'mesmeric-commerce'),
                'description' => __('Configure the appearance of the login popup.', 'mesmeric-commerce'),
                'fields' => [
                    'modal_size' => [
                        'type' => 'select',
                        'label' => __('Modal Size', 'mesmeric-commerce'),
                        'description' => __('The size of the login popup modal.', 'mesmeric-commerce'),
                        'default' => 'medium',
                        'options' => [
                            'small' => __('Small', 'mesmeric-commerce'),
                            'medium' => __('Medium', 'mesmeric-commerce'),
                            'large' => __('Large', 'mesmeric-commerce'),
                        ],
                    ],
                    'primary_color' => [
                        'type' => 'color',
                        'label' => __('Primary Color', 'mesmeric-commerce'),
                        'description' => __('The primary color for buttons and accents.', 'mesmeric-commerce'),
                        'default' => '#4a90e2',
                    ],
                    'custom_css' => [
                        'type' => 'textarea',
                        'label' => __('Custom CSS', 'mesmeric-commerce'),
                        'description' => __('Add custom CSS to customize the appearance of the login popup.', 'mesmeric-commerce'),
                        'default' => '',
                    ],
                ],
            ],
            'social_login_section' => [
                'title' => __('Social Login', 'mesmeric-commerce'),
                'description' => __('Configure social login options for the login popup.', 'mesmeric-commerce'),
                'fields' => [
                    'enable_social_login' => [
                        'type' => 'toggle',
                        'label' => __('Enable Social Login', 'mesmeric-commerce'),
                        'description' => __('Allow users to log in using their social media accounts.', 'mesmeric-commerce'),
                        'default' => false,
                    ],
                    'facebook_enabled' => [
                        'type' => 'toggle',
                        'label' => __('Enable Facebook Login', 'mesmeric-commerce'),
                        'description' => __('Allow users to log in with their Facebook account.', 'mesmeric-commerce'),
                        'default' => false,
                        'dependency' => [
                            'id' => 'enable_social_login',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'facebook_app_id' => [
                        'type' => 'text',
                        'label' => __('Facebook App ID', 'mesmeric-commerce'),
                        'description' => __('Enter your Facebook App ID.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'facebook_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'facebook_app_secret' => [
                        'type' => 'text',
                        'label' => __('Facebook App Secret', 'mesmeric-commerce'),
                        'description' => __('Enter your Facebook App Secret.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'facebook_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'google_enabled' => [
                        'type' => 'toggle',
                        'label' => __('Enable Google Login', 'mesmeric-commerce'),
                        'description' => __('Allow users to log in with their Google account.', 'mesmeric-commerce'),
                        'default' => false,
                        'dependency' => [
                            'id' => 'enable_social_login',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'google_client_id' => [
                        'type' => 'text',
                        'label' => __('Google Client ID', 'mesmeric-commerce'),
                        'description' => __('Enter your Google Client ID.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'google_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                    'google_client_secret' => [
                        'type' => 'text',
                        'label' => __('Google Client Secret', 'mesmeric-commerce'),
                        'description' => __('Enter your Google Client Secret.', 'mesmeric-commerce'),
                        'default' => '',
                        'dependency' => [
                            'id' => 'google_enabled',
                            'value' => true,
                            'compare' => '==',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Sanitize the settings before saving
     *
     * @param array $settings The settings to sanitize
     * @return array The sanitized settings
     */
    public function sanitizeSettings(array $settings): array
    {
        // Sanitize text fields
        if (isset($settings['general_section']['recaptcha_site_key'])) {
            $settings['general_section']['recaptcha_site_key'] = sanitize_text_field($settings['general_section']['recaptcha_site_key']);
        }

        if (isset($settings['general_section']['recaptcha_secret_key'])) {
            $settings['general_section']['recaptcha_secret_key'] = sanitize_text_field($settings['general_section']['recaptcha_secret_key']);
        }

        if (isset($settings['content_section']['redirect_url'])) {
            $settings['content_section']['redirect_url'] = esc_url_raw($settings['content_section']['redirect_url']);
        }

        if (isset($settings['trigger_section']['trigger_text'])) {
            $settings['trigger_section']['trigger_text'] = sanitize_text_field($settings['trigger_section']['trigger_text']);
        }

        if (isset($settings['style_section']['primary_color'])) {
            $settings['style_section']['primary_color'] = sanitize_hex_color($settings['style_section']['primary_color']);
        }

        if (isset($settings['style_section']['custom_css'])) {
            $settings['style_section']['custom_css'] = wp_strip_all_tags($settings['style_section']['custom_css']);
        }

        if (isset($settings['social_login_section']['facebook_app_id'])) {
            $settings['social_login_section']['facebook_app_id'] = sanitize_text_field($settings['social_login_section']['facebook_app_id']);
        }

        if (isset($settings['social_login_section']['facebook_app_secret'])) {
            $settings['social_login_section']['facebook_app_secret'] = sanitize_text_field($settings['social_login_section']['facebook_app_secret']);
        }

        if (isset($settings['social_login_section']['google_client_id'])) {
            $settings['social_login_section']['google_client_id'] = sanitize_text_field($settings['social_login_section']['google_client_id']);
        }

        if (isset($settings['social_login_section']['google_client_secret'])) {
            $settings['social_login_section']['google_client_secret'] = sanitize_text_field($settings['social_login_section']['google_client_secret']);
        }

        return $settings;
    }
}
