<?php
/**
 * Bootstrap file for Mesmeric Commerce
 *
 * @package MesmericCommerce
 */

declare(strict_types=1);

namespace MesmericCommerce\Includes;

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Check if Breakdance is active and being used
if (class_exists('\\Breakdance\\Plugin')) {
	// Only initialize Breakdance integration if it's actually being used on the site
	$is_breakdance_used = false;

	// Check if any posts are using Breakdance
	if (function_exists('\\Breakdance\\Data\\get_breakdance_post_count')) {
		$breakdance_post_count = \Breakdance\Data\get_breakdance_post_count();
		$is_breakdance_used = $breakdance_post_count > 0;
	}

	// Check if Breakdance is enabled in the plugin settings
	if (!$is_breakdance_used) {
		$breakdance_enabled = get_option('mc_enable_breakdance_admin_menu', 'no');
		$is_breakdance_used = $breakdance_enabled === 'yes';
	}

	// Only initialize if Breakdance is being used
	if ($is_breakdance_used) {
		$breakdance_integration = new BreakdanceIntegration();
		$breakdance_integration->init();
	}
}
