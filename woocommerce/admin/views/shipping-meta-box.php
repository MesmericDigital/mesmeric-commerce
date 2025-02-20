<?php
declare(strict_types=1);

use MesmericCommerce\WooCommerce\Services\MC_Shipping_Service;

defined('ABSPATH') || exit;

/** @var MC_Shipping_Service $this */
/** @var WP_Post $post */

$order = wc_get_order($post->ID);
if (!$order) {
    return;
}

$tracking_number = get_post_meta($post->ID, '_mc_tracking_number', true);
$shipping_method = $order->get_shipping_method();
wp_nonce_field('mc_shipping_meta_box', 'mc_shipping_nonce');
?>

<div class="mc-shipping-info">
    <p>
        <strong><?php esc_html_e('Shipping Method:', 'mesmeric-commerce'); ?></strong>
        <?php echo esc_html($shipping_method); ?>
    </p>

    <?php if ($order->get_shipping_method_id() === 'evri') : ?>
        <p>
            <label for="mc_tracking_number">
                <?php esc_html_e('Tracking Number:', 'mesmeric-commerce'); ?>
            </label>
            <input type="text"
                   id="mc_tracking_number"
                   name="mc_tracking_number"
                   value="<?php echo esc_attr($tracking_number); ?>"
                   class="widefat">
        </p>

        <p>
            <button type="button"
                    class="button button-primary generate-label"
                    data-order-id="<?php echo esc_attr($post->ID); ?>">
                <?php esc_html_e('Generate Shipping Label', 'mesmeric-commerce'); ?>
            </button>
        </p>

        <?php if ($tracking_number) : ?>
            <p>
                <a href="#"
                   class="button track-shipment"
                   data-tracking="<?php echo esc_attr($tracking_number); ?>">
                    <?php esc_html_e('Track Shipment', 'mesmeric-commerce'); ?>
                </a>
            </p>
        <?php endif; ?>

        <div class="mc-shipping-status"></div>
    <?php endif; ?>

    <?php
    // Display any additional shipping information
    do_action('mc_after_shipping_info', $order);
    ?>
</div>
