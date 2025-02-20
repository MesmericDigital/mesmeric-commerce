<?php
/**
 * Quick view content template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/quickview/views
 *
 * @var WC_Product $product The product object
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="mc-quick-view-content" x-data="{
    quantity: 1,
    variations: {},
    variationId: 0,
    isAddingToCart: false,
    addToCart() {
        if (this.isAddingToCart) return;
        this.isAddingToCart = true;

        fetch(mcQuickViewData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mc_add_to_cart_quick_view',
                nonce: mcQuickViewData.nonce,
                product_id: <?php echo esc_js($product->get_id()); ?>,
                variation_id: this.variationId,
                quantity: this.quantity,
                variations: JSON.stringify(this.variations)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart fragments
                document.body.dispatchEvent(new Event('wc_fragments_refreshed'));
                // Close modal
                this.$dispatch('close-modal');
            } else {
                alert(data.data || 'Error adding to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding to cart');
        })
        .finally(() => {
            this.isAddingToCart = false;
        });
    }
}">
    <div class="mc-quick-view-images">
        <?php if ($product->get_image_id()): ?>
            <img src="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_single')); ?>"
                 alt="<?php echo esc_attr($product->get_name()); ?>"
                 class="mc-quick-view-main-image">
        <?php endif; ?>

        <?php
        $attachment_ids = $product->get_gallery_image_ids();
        if ($attachment_ids): ?>
            <div class="mc-quick-view-gallery">
                <?php foreach ($attachment_ids as $attachment_id): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($attachment_id, 'woocommerce_thumbnail')); ?>"
                         alt=""
                         class="mc-quick-view-gallery-image">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mc-quick-view-details">
        <h2 class="mc-quick-view-title"><?php echo esc_html($product->get_name()); ?></h2>

        <div class="mc-quick-view-price">
            <?php echo $product->get_price_html(); ?>
        </div>

        <div class="mc-quick-view-description">
            <?php echo wp_kses_post($product->get_short_description()); ?>
        </div>

        <?php if ($product->is_type('variable')): ?>
            <div class="mc-quick-view-variations">
                <?php
                $attributes = $product->get_variation_attributes();
                foreach ($attributes as $attribute_name => $options): ?>
                    <div class="mc-quick-view-variation-select">
                        <label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>">
                            <?php echo wc_attribute_label($attribute_name); ?>
                        </label>
                        <select id="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"
                                x-model="variations['<?php echo esc_attr($attribute_name); ?>']">
                            <option value=""><?php echo esc_html__('Choose an option', 'mesmeric-commerce'); ?></option>
                            <?php foreach ($options as $option): ?>
                                <option value="<?php echo esc_attr($option); ?>">
                                    <?php echo esc_html($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mc-quick-view-quantity">
            <label for="quantity"><?php esc_html_e('Quantity', 'mesmeric-commerce'); ?></label>
            <input type="number"
                   id="quantity"
                   x-model="quantity"
                   min="1"
                   max="<?php echo esc_attr($product->get_max_purchase_quantity()); ?>"
                   step="1"
                   value="1">
        </div>

        <button class="mc-quick-view-add-to-cart"
                x-on:click="addToCart"
                x-bind:disabled="isAddingToCart || !product.is_purchasable() || !product.is_in_stock()">
            <span x-show="!isAddingToCart">
                <?php echo esc_html($product->is_purchasable() && $product->is_in_stock() ?
                    __('Add to cart', 'mesmeric-commerce') :
                    __('Read more', 'mesmeric-commerce')); ?>
            </span>
            <span x-show="isAddingToCart">
                <?php esc_html_e('Adding...', 'mesmeric-commerce'); ?>
            </span>
        </button>

        <div class="mc-quick-view-meta">
            <?php if ($product->get_sku()): ?>
                <p class="mc-quick-view-sku">
                    <?php esc_html_e('SKU:', 'mesmeric-commerce'); ?>
                    <span><?php echo esc_html($product->get_sku()); ?></span>
                </p>
            <?php endif; ?>

            <?php if ($product->get_category_ids()): ?>
                <p class="mc-quick-view-categories">
                    <?php esc_html_e('Categories:', 'mesmeric-commerce'); ?>
                    <?php echo wc_get_product_category_list($product->get_id()); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
