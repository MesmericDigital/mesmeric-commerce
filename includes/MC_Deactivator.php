<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class Deactivator
 *
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    MesmericCommerce
 * @subpackage MesmericCommerce/includes
 */
class MC_Deactivator
{
    /**
     * Deactivate the plugin.
     */
    public static function deactivate(): void
    {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('mesmeric_commerce_daily_cron');
        wp_clear_scheduled_hook('mesmeric_commerce_inventory_check');

        // Clear plugin-specific transients
        self::clear_transients();

        // Flush rewrite rules
        flush_rewrite_rules();

        self::cleanup_old_data();
    }

    /**
     * Clear plugin-specific transients.
     */
    private static function clear_transients(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_mc_') . '%',
                $wpdb->esc_like('_transient_timeout_mc_') . '%'
            )
        );
    }

    /**
     * Clean up old data when deactivating
     */
    private static function cleanup_old_data(): void
    {
        // Clean up old shipping module options
        delete_option('mc_enable_shipping');
        delete_option('mc_shipping_rules');
        delete_option('mc_shipping_handling_fee');
        delete_option('mc_shipping_settings');

        // Clean up other old data as needed
        // ... existing code ...
    }
}
