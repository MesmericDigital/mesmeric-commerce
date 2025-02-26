<?php
/**
 * Login Popup Module Registration
 *
 * @package MesmericCommerce
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use MesmericCommerce\Modules\LoginPopup\LoginPopupModule;

/**
 * Register the Login Popup module
 */
function mc_register_login_popup_module(): void
{
    // Register the module with the plugin
    mc_register_module(LoginPopupModule::class);
}
add_action('mc_register_modules', 'mc_register_login_popup_module');
