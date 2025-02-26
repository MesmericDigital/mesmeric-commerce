<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\LoginPopup\Shortcodes;

use MesmericCommerce\Core\Shortcodes\AbstractShortcode;
use MesmericCommerce\Modules\LoginPopup\LoginPopupModule;

/**
 * Login Popup Shortcode
 *
 * Handles the [mc_login_popup] shortcode for displaying the login popup trigger.
 *
 * @since 1.0.0
 */
class LoginPopupShortcode extends AbstractShortcode
{
    /**
     * @var LoginPopupModule
     */
    protected LoginPopupModule $module;

    /**
     * Constructor
     *
     * @param LoginPopupModule $module The login popup module instance
     */
    public function __construct(LoginPopupModule $module)
    {
        $this->module = $module;
        $this->tag = 'mc_login_popup';
    }

    /**
     * Register the shortcode
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode($this->tag, [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts The shortcode attributes
     * @param string|null $content The shortcode content
     * @return string The shortcode output
     */
    public function render(array $atts = [], ?string $content = null): string
    {
        // Don't show the login popup if user is already logged in and show_when_logged_in is false
        if (is_user_logged_in() && !$this->module->getSetting('content_section.show_when_logged_in')) {
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts([
            'type' => $this->module->getSetting('trigger_section.trigger_type'),
            'text' => $this->module->getSetting('trigger_section.trigger_text'),
            'icon' => $this->module->getSetting('trigger_section.trigger_icon'),
            'class' => '',
        ], $atts, $this->tag);

        // Render the trigger
        return $this->module->renderTrigger([
            'trigger_type' => $atts['type'],
            'trigger_text' => $atts['text'],
            'trigger_icon' => $atts['icon'],
            'extra_classes' => $atts['class'],
        ]);
    }
}
