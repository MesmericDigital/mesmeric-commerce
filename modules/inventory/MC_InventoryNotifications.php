<?php
/**
 * Inventory Notifications Handler
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/inventory
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\Inventory;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_InventoryNotifications
 *
 * Handles inventory-related notifications
 */
class MC_InventoryNotifications {
    /**
     * The plugin's instance.
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * The logger instance.
     *
     * @var MC_Logger
     */
    private MC_Logger $logger;

    /**
     * Initialize the notifications handler.
     *
     * @param MC_Plugin $plugin The plugin instance.
     */
    public function __construct( MC_Plugin $plugin ) {
        $this->plugin = $plugin;
        $this->logger = $plugin->get_logger();
    }

    /**
     * Send low stock notification.
     *
     * @param \WC_Product $product The product.
     * @return void
     */
    public function send_low_stock_notification( \WC_Product $product ): void {
        try {
            $product_id     = $product->get_id();
            $stock_quantity = $product->get_stock_quantity();
            $threshold      = $product->get_low_stock_amount();

            $to      = $this->get_notification_email();
            $subject = sprintf(
                '[%s] Low Stock Alert: %s',
                get_bloginfo( 'name' ),
                $product->get_name()
            );

            $message = sprintf(
                'The following product is running low on stock:\n\n' .
                'Product: %s\n' .
                'Current Stock: %d\n' .
                'Low Stock Threshold: %d\n\n' .
                'Please review and restock if necessary.\n\n' .
                'View Product: %s',
                $product->get_name(),
                $stock_quantity,
                $threshold,
                admin_url( 'post.php?post=' . $product_id . '&action=edit' )
            );

            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            wp_mail( $to, $subject, $message, $headers );

            $this->logger->log_error(
                sprintf(
                    'Low stock notification sent for product #%d. Stock: %d, Threshold: %d',
                    $product_id,
                    $stock_quantity,
                    $threshold
                ),
                'info'
            );
        } catch ( \Throwable $e ) {
            $this->logger->log_error(
                sprintf(
                    'Error sending low stock notification for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                ),
                'error',
                true
            );
        }
    }

    /**
     * Send out of stock notification.
     *
     * @param \WC_Product $product The product.
     * @return void
     */
    public function send_out_of_stock_notification( \WC_Product $product ): void {
        try {
            $product_id = $product->get_id();
            $to         = $this->get_notification_email();
            $subject    = sprintf(
                '[%s] Out of Stock Alert: %s',
                get_bloginfo( 'name' ),
                $product->get_name()
            );

            $message = sprintf(
                'The following product is now out of stock:\n\n' .
                'Product: %s\n' .
                'SKU: %s\n\n' .
                'Please review and restock as soon as possible.\n\n' .
                'View Product: %s',
                $product->get_name(),
                $product->get_sku(),
                admin_url( 'post.php?post=' . $product_id . '&action=edit' )
            );

            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            wp_mail( $to, $subject, $message, $headers );

            $this->logger->log_error(
                sprintf(
                    'Out of stock notification sent for product #%d',
                    $product_id
                ),
                'info'
            );
        } catch ( \Throwable $e ) {
            $this->logger->log_error(
                sprintf(
                    'Error sending out of stock notification for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                ),
                'error',
                true
            );
        }
    }

    /**
     * Send reorder notification.
     *
     * @param \WC_Product $product The product.
     * @return void
     */
    public function send_reorder_notification( \WC_Product $product ): void {
        try {
            $product_id     = $product->get_id();
            $stock_quantity = $product->get_stock_quantity();
            $reorder_amount = $this->calculate_reorder_amount( $product );

            $to      = $this->get_notification_email();
            $subject = sprintf(
                '[%s] Reorder Alert: %s',
                get_bloginfo( 'name' ),
                $product->get_name()
            );

            $message = sprintf(
                'The following product needs to be reordered:\n\n' .
                'Product: %s\n' .
                'SKU: %s\n' .
                'Current Stock: %d\n' .
                'Suggested Reorder Amount: %d\n\n' .
                'Please review and place order as needed.\n\n' .
                'View Product: %s',
                $product->get_name(),
                $product->get_sku(),
                $stock_quantity,
                $reorder_amount,
                admin_url( 'post.php?post=' . $product_id . '&action=edit' )
            );

            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            wp_mail( $to, $subject, $message, $headers );

            $this->logger->log_error(
                sprintf(
                    'Reorder notification sent for product #%d. Stock: %d, Reorder Amount: %d',
                    $product_id,
                    $stock_quantity,
                    $reorder_amount
                ),
                'info'
            );
        } catch ( \Throwable $e ) {
            $this->logger->log_error(
                sprintf(
                    'Error sending reorder notification for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                ),
                'error',
                true
            );
        }
    }

    /**
     * Calculate reorder amount for a product.
     *
     * @param \WC_Product $product The product.
     * @return int
     */
    private function calculate_reorder_amount( \WC_Product $product ): int {
        $optimal_stock = (int) get_option( 'mc_inventory_optimal_stock', 10 );
        $current_stock = (int) $product->get_stock_quantity();

        return max( 0, $optimal_stock - $current_stock );
    }

    /**
     * Get notification email address.
     *
     * @return string
     */
    private function get_notification_email(): string {
        $email = get_option( 'mc_inventory_notification_email' );
        if (  ! $email ) {
            $email = get_option( 'admin_email' );
        }

        return sanitize_email( $email );
    }
}
