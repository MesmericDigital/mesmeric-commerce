<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\LoginPopup;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\Interfaces\MC_ModuleInterface;
use MesmericCommerce\Includes\MC_Plugin;
use MesmericCommerce\Modules\LoginPopup\Options\ModuleOptions;

/**
 * Login Popup Module
 *
 * Provides a modern, user-friendly login popup for WordPress sites.
 *
 * @since 1.0.0
 */
class LoginPopupModule extends MC_AbstractModule implements MC_ModuleInterface
{
    /**
     * Module identifier
     */
    private const MODULE_ID = 'login-popup';

    /**
     * Nonce action for AJAX requests
     */
    private const NONCE_ACTION = 'mesmeric_commerce_login_popup';

    /**
     * Initialize the module
     *
     * @return void
     */
    public function init(): void
    {
        if (!is_user_logged_in() || $this->get_setting('show_when_logged_in', false)) {
            // Register assets
            add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

            // Add login popup trigger based on position setting
            $this->addLoginPopupTrigger();

            // Add modal template to footer
            add_action('wp_footer', [$this, 'renderModal']);

            // Register AJAX handlers
            add_action('wp_ajax_nopriv_mesmeric_commerce_login_popup', [$this, 'handleLoginAjax']);
            add_action('wp_ajax_mesmeric_commerce_login_popup', [$this, 'handleLoginAjax']);
        }
    }

    /**
     * Get the module identifier
     *
     * @return string
     */
    public function get_module_id(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Get default settings
     *
     * @return array
     */
    protected function get_default_settings(): array
    {
        return [
            'trigger_type' => 'button', // button, link, auto
            'trigger_text' => __('Login / Register', 'mesmeric-commerce'),
            'trigger_icon' => 'user',
            'trigger_position' => 'menu', // menu, header, footer, custom
            'trigger_selector' => '', // Custom selector for trigger position
            'show_registration' => true,
            'show_social_login' => true,
            'show_password_reset' => true,
            'auto_trigger_delay' => 5, // Seconds before auto-triggering
            'auto_trigger_pages' => [], // Page IDs to auto-trigger on
            'auto_trigger_once' => true, // Only trigger once per session
            'redirect_after_login' => 'current', // current, home, account, custom
            'custom_redirect' => '', // Custom URL to redirect to after login
            'modal_width' => 'medium', // small, medium, large
            'show_when_logged_in' => false, // Show for logged-in users
            'enable_recaptcha' => false, // Enable reCAPTCHA
            'recaptcha_site_key' => '', // reCAPTCHA site key
            'recaptcha_secret_key' => '', // reCAPTCHA secret key
        ];
    }

    /**
     * Add login popup trigger based on position setting
     *
     * @return void
     */
    private function addLoginPopupTrigger(): void
    {
        $position = $this->get_setting('trigger_position', 'menu');

        switch ($position) {
            case 'menu':
                add_filter('wp_nav_menu_items', [$this, 'addLoginToMenu'], 10, 2);
                break;

            case 'header':
                add_action('wp_head', [$this, 'renderLoginTrigger']);
                break;

            case 'footer':
                add_action('wp_footer', [$this, 'renderLoginTrigger'], 5);
                break;

            case 'custom':
                add_action('wp_footer', [$this, 'addCustomPositionedTrigger']);
                break;
        }

        // Add shortcode for manual placement
        add_shortcode('mesmeric_login_popup', [$this, 'loginPopupShortcode']);
    }

    /**
     * Add login trigger to menu
     *
     * @param string $items Menu items HTML
     * @param object $args Menu arguments
     * @return string Modified menu items HTML
     */
    public function addLoginToMenu(string $items, $args): string
    {
        // Only add to primary menu by default
        if ($args->theme_location !== 'primary') {
            return $items;
        }

        // Create the login menu item
        $login_item = '<li class="menu-item menu-item-login-popup">';
        $login_item .= $this->getLoginTriggerHtml();
        $login_item .= '</li>';

        // Add to the end of the menu
        return $items . $login_item;
    }

    /**
     * Add custom positioned trigger via JavaScript
     *
     * @return void
     */
    public function addCustomPositionedTrigger(): void
    {
        $selector = $this->get_setting('trigger_selector', '');
        if (empty($selector)) {
            return;
        }

        $trigger_html = $this->getLoginTriggerHtml();
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const targetElement = document.querySelector('<?php echo esc_js($selector); ?>');
                if (targetElement) {
                    targetElement.insertAdjacentHTML('beforeend', <?php echo json_encode($trigger_html); ?>);
                }
            });
        </script>
        <?php
    }

    /**
     * Render login trigger
     *
     * @return void
     */
    public function renderLoginTrigger(): void
    {
        echo $this->getLoginTriggerHtml();
    }

    /**
     * Get login trigger HTML
     *
     * @return string
     */
    private function getLoginTriggerHtml(): string
    {
        $type = $this->get_setting('trigger_type', 'button');
        $text = $this->get_setting('trigger_text', __('Login / Register', 'mesmeric-commerce'));
        $icon = $this->get_setting('trigger_icon', 'user');

        $html = '<div class="mesmeric-login-popup-trigger">';

        if ($type === 'button') {
            $html .= '<button type="button" class="mesmeric-login-popup-button" data-mesmeric-login-popup-trigger>';
        } else {
            $html .= '<a href="#" class="mesmeric-login-popup-link" data-mesmeric-login-popup-trigger>';
        }

        if (!empty($icon)) {
            $html .= '<span class="mesmeric-login-popup-icon mesmeric-icon-' . esc_attr($icon) . '"></span>';
        }

        $html .= '<span class="mesmeric-login-popup-text">' . esc_html($text) . '</span>';

        if ($type === 'button') {
            $html .= '</button>';
        } else {
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Login popup shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function loginPopupShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'text' => $this->get_setting('trigger_text', __('Login / Register', 'mesmeric-commerce')),
            'icon' => $this->get_setting('trigger_icon', 'user'),
            'type' => $this->get_setting('trigger_type', 'button'),
        ], $atts, 'mesmeric_login_popup');

        // Override settings with shortcode attributes
        $this->settings['trigger_text'] = $atts['text'];
        $this->settings['trigger_icon'] = $atts['icon'];
        $this->settings['trigger_type'] = $atts['type'];

        return $this->getLoginTriggerHtml();
    }

    /**
     * Enqueue assets
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        // Enqueue styles
        wp_enqueue_style(
            'mesmeric-login-popup',
            MC_PLUGIN_URL . 'modules/login-popup/src/css/login-popup.css',
            [],
            MC_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'mesmeric-login-popup',
            MC_PLUGIN_URL . 'modules/login-popup/src/js/login-popup.js',
            ['jquery', 'alpine'],
            MC_VERSION,
            true
        );

        // Localize script
        wp_localize_script('mesmeric-login-popup', 'mesLoginPopup', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'i18n' => [
                'loading' => __('Loading...', 'mesmeric-commerce'),
                'error' => __('Error processing request', 'mesmeric-commerce'),
                'loginSuccess' => __('Login successful. Redirecting...', 'mesmeric-commerce'),
                'registerSuccess' => __('Registration successful. Please check your email.', 'mesmeric-commerce'),
                'resetSuccess' => __('Password reset email sent. Please check your inbox.', 'mesmeric-commerce'),
            ],
            'settings' => [
                'showRegistration' => $this->get_setting('show_registration', true),
                'showSocialLogin' => $this->get_setting('show_social_login', true),
                'showPasswordReset' => $this->get_setting('show_password_reset', true),
                'autoTrigger' => $this->shouldAutoTrigger(),
                'autoTriggerDelay' => $this->get_setting('auto_trigger_delay', 5) * 1000,
                'redirectAfterLogin' => $this->get_setting('redirect_after_login', 'current'),
                'customRedirect' => $this->get_setting('custom_redirect', ''),
                'enableRecaptcha' => $this->get_setting('enable_recaptcha', false),
                'recaptchaSiteKey' => $this->get_setting('recaptcha_site_key', ''),
            ],
        ]);

        // Enqueue reCAPTCHA if enabled
        if ($this->get_setting('enable_recaptcha', false) && !empty($this->get_setting('recaptcha_site_key', ''))) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js',
                [],
                null,
                true
            );
        }
    }

    /**
     * Determine if login popup should auto-trigger
     *
     * @return bool
     */
    private function shouldAutoTrigger(): bool
    {
        // Check if auto-trigger is enabled via trigger type
        if ($this->get_setting('trigger_type', 'button') !== 'auto') {
            return false;
        }

        // Check if we should only trigger once per session
        if ($this->get_setting('auto_trigger_once', true) && isset($_COOKIE['mesmeric_login_popup_shown'])) {
            return false;
        }

        // Check if current page is in the auto-trigger pages list
        $auto_trigger_pages = $this->get_setting('auto_trigger_pages', []);
        if (!empty($auto_trigger_pages)) {
            $current_page_id = get_the_ID();
            if (!in_array($current_page_id, $auto_trigger_pages)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render modal
     *
     * @return void
     */
    public function renderModal(): void
    {
        // Get template variables
        $template_vars = [
            'show_registration' => $this->get_setting('show_registration', true),
            'show_social_login' => $this->get_setting('show_social_login', true),
            'show_password_reset' => $this->get_setting('show_password_reset', true),
            'modal_width' => $this->get_setting('modal_width', 'medium'),
            'enable_recaptcha' => $this->get_setting('enable_recaptcha', false),
            'recaptcha_site_key' => $this->get_setting('recaptcha_site_key', ''),
        ];

        // Include template
        include MC_PLUGIN_DIR . 'modules/login-popup/templates/modal.php';
    }

    /**
     * Handle login AJAX request
     *
     * @return void
     */
    public function handleLoginAjax(): void
    {
        // Verify nonce
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $action = isset($_POST['login_action']) ? sanitize_text_field($_POST['login_action']) : '';

        switch ($action) {
            case 'login':
                $this->processLogin();
                break;

            case 'register':
                $this->processRegistration();
                break;

            case 'reset_password':
                $this->processPasswordReset();
                break;

            default:
                wp_send_json_error(['message' => __('Invalid action', 'mesmeric-commerce')]);
                break;
        }

        // This should never be reached
        wp_die();
    }

    /**
     * Process login request
     *
     * @return void
     */
    private function processLogin(): void
    {
        // Validate input
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => __('Username and password are required', 'mesmeric-commerce')]);
        }

        // Verify reCAPTCHA if enabled
        if (!$this->verifyRecaptcha()) {
            wp_send_json_error(['message' => __('reCAPTCHA verification failed', 'mesmeric-commerce')]);
        }

        // Attempt login
        $credentials = [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ];

        $user = wp_signon($credentials, is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => $user->get_error_message()]);
        }

        // Determine redirect URL
        $redirect_url = $this->getRedirectUrl();

        wp_send_json_success([
            'message' => __('Login successful', 'mesmeric-commerce'),
            'redirect' => $redirect_url,
        ]);
    }

    /**
     * Process registration request
     *
     * @return void
     */
    private function processRegistration(): void
    {
        // Check if registration is enabled
        if (!get_option('users_can_register')) {
            wp_send_json_error(['message' => __('Registration is disabled', 'mesmeric-commerce')]);
        }

        // Validate input
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => __('All fields are required', 'mesmeric-commerce')]);
        }

        // Verify reCAPTCHA if enabled
        if (!$this->verifyRecaptcha()) {
            wp_send_json_error(['message' => __('reCAPTCHA verification failed', 'mesmeric-commerce')]);
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Set user role
        $user = new \WP_User($user_id);
        $user->set_role('customer');

        // Send notification
        wp_new_user_notification($user_id, null, 'both');

        wp_send_json_success([
            'message' => __('Registration successful. Please check your email.', 'mesmeric-commerce'),
        ]);
    }

    /**
     * Process password reset request
     *
     * @return void
     */
    private function processPasswordReset(): void
    {
        // Validate input
        $user_login = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : '';

        if (empty($user_login)) {
            wp_send_json_error(['message' => __('Username or email is required', 'mesmeric-commerce')]);
        }

        // Verify reCAPTCHA if enabled
        if (!$this->verifyRecaptcha()) {
            wp_send_json_error(['message' => __('reCAPTCHA verification failed', 'mesmeric-commerce')]);
        }

        // Get user by username or email
        if (strpos($user_login, '@') !== false) {
            $user = get_user_by('email', $user_login);
        } else {
            $user = get_user_by('login', $user_login);
        }

        if (!$user) {
            // Don't reveal if user exists or not for security
            wp_send_json_success([
                'message' => __('If your account exists, you will receive a password reset email shortly.', 'mesmeric-commerce'),
            ]);
        }

        // Generate reset key and send email
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_error(['message' => $key->get_error_message()]);
        }

        $result = wp_mail(
            $user->user_email,
            __('Password Reset Request', 'mesmeric-commerce'),
            $this->getPasswordResetEmailContent($user, $key),
            ['Content-Type: text/html; charset=UTF-8']
        );

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to send password reset email', 'mesmeric-commerce')]);
        }

        wp_send_json_success([
            'message' => __('Password reset email sent. Please check your inbox.', 'mesmeric-commerce'),
        ]);
    }

    /**
     * Get password reset email content
     *
     * @param \WP_User $user User object
     * @param string $key Reset key
     * @return string
     */
    private function getPasswordResetEmailContent(\WP_User $user, string $key): string
    {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        $message = '<p>' . sprintf(__('Someone has requested a password reset for the following account: %s', 'mesmeric-commerce'), $site_name) . '</p>';
        $message .= '<p>' . sprintf(__('Username: %s', 'mesmeric-commerce'), $user->user_login) . '</p>';
        $message .= '<p>' . __('If this was a mistake, just ignore this email and nothing will happen.', 'mesmeric-commerce') . '</p>';
        $message .= '<p>' . __('To reset your password, visit the following address:', 'mesmeric-commerce') . '</p>';
        $message .= '<p><a href="' . esc_url($reset_url) . '">' . esc_html($reset_url) . '</a></p>';

        return $message;
    }

    /**
     * Verify reCAPTCHA response
     *
     * @return bool
     */
    private function verifyRecaptcha(): bool
    {
        // Skip verification if reCAPTCHA is not enabled
        if (!$this->get_setting('enable_recaptcha', false)) {
            return true;
        }

        $recaptcha_secret = $this->get_setting('recaptcha_secret_key', '');
        if (empty($recaptcha_secret)) {
            return true;
        }

        $recaptcha_response = isset($_POST['recaptcha_response']) ? sanitize_text_field($_POST['recaptcha_response']) : '';
        if (empty($recaptcha_response)) {
            return false;
        }

        // Verify with Google
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $response = wp_remote_post($verify_url, [
            'body' => [
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        return isset($result['success']) && $result['success'] === true;
    }

    /**
     * Get redirect URL after login
     *
     * @return string
     */
    private function getRedirectUrl(): string
    {
        $redirect_type = $this->get_setting('redirect_after_login', 'current');

        switch ($redirect_type) {
            case 'home':
                return home_url();

            case 'account':
                return wc_get_page_permalink('myaccount');

            case 'custom':
                $custom_url = $this->get_setting('custom_redirect', '');
                return !empty($custom_url) ? $custom_url : home_url();

            case 'current':
            default:
                return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();
        }
    }
}
