<?php
/**
 * Wishlist view template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/wishlist/views
 *
 * @var array $wishlist The wishlist data
 * @var array $items    The wishlist items
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="mc-wishlist-view" x-data="{
    isRemoving: false,
    isUpdating: false,
    isSharing: false,
    shareUrl: '',
    async removeItem(itemId) {
        if (!confirm(mcWishlistData.i18n.confirmRemove)) return;
        this.isRemoving = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_remove_from_wishlist',
                    nonce: mcWishlistData.nonce,
                    item_id: itemId
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isRemoving = false;
        }
    },
    async updateQuantity(itemId, quantity) {
        this.isUpdating = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_update_wishlist_item',
                    nonce: mcWishlistData.nonce,
                    item_id: itemId,
                    quantity: quantity
                })
            });

            const data = await response.json();
            if (!data.success) {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isUpdating = false;
        }
    },
    async shareWishlist() {
        this.isSharing = true;

        try {
            const response = await fetch(mcWishlistData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_share_wishlist',
                    nonce: mcWishlistData.nonce,
                    wishlist_id: <?php echo esc_js($wishlist['id']); ?>
                })
            });

            const data = await response.json();
            if (data.success) {
                this.shareUrl = data.data.share_url;
            } else {
                alert(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
            alert(mcWishlistData.i18n.error);
        } finally {
            this.isSharing = false;
        }
    }
}">
    <div class="mc-wishlist-view__header">
        <div class="mc-wishlist-view__title-wrapper">
            <h2 class="mc-wishlist-view__title">
                <?php echo esc_html($wishlist['name']); ?>
            </h2>
            <?php if (!empty($wishlist['description'])): ?>
                <p class="mc-wishlist-view__description">
                    <?php echo esc_html($wishlist['description']); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="mc-wishlist-view__actions">
            <button type="button"
                    @click="shareWishlist"
                    class="mc-wishlist-view__share-button"
                    :disabled="isSharing">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                    <polyline points="16 6 12 2 8 6"></polyline>
                    <line x1="12" y1="2" x2="12" y2="15"></line>
                </svg>
                <span><?php esc_html_e('Share', 'mesmeric-commerce'); ?></span>
            </button>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <p class="mc-wishlist-view__empty">
            <?php esc_html_e('This wishlist is empty.', 'mesmeric-commerce'); ?>
        </p>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="mc-wishlist-view__shop-link">
            <?php esc_html_e('Continue Shopping', 'mesmeric-commerce'); ?>
        </a>
    <?php else: ?>
        <div class="mc-wishlist-view__items">
            <?php foreach ($items as $item):
                $product = wc_get_product($item['product_id']);
                if (!$product) continue;

                if ($item['variation_id']) {
                    $variation = wc_get_product($item['variation_id']);
                    if ($variation) {
                        $product = $variation;
                    }
                }
                ?>
                <div class="mc-wishlist-view__item">
                    <div class="mc-wishlist-view__item-image">
                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                    </div>
                    <div class="mc-wishlist-view__item-details">
                        <h3 class="mc-wishlist-view__item-title">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </a>
                        </h3>
                        <?php if ($product->is_type('variable') && $item['variation_id']): ?>
                            <div class="mc-wishlist-view__item-variations">
                                <?php
                                $attributes = $product->get_variation_attributes();
                                foreach ($attributes as $attribute => $options):
                                    $selected = $variation->get_attribute($attribute);
                                    ?>
                                    <span class="mc-wishlist-view__item-variation">
                                        <?php echo esc_html(wc_attribute_label($attribute) . ': ' . $selected); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mc-wishlist-view__item-price">
                            <?php echo $product->get_price_html(); ?>
                        </div>
                        <?php if ($product->is_in_stock()): ?>
                            <div class="mc-wishlist-view__item-stock mc-wishlist-view__item-stock--in">
                                <?php esc_html_e('In Stock', 'mesmeric-commerce'); ?>
                            </div>
                        <?php else: ?>
                            <div class="mc-wishlist-view__item-stock mc-wishlist-view__item-stock--out">
                                <?php esc_html_e('Out of Stock', 'mesmeric-commerce'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mc-wishlist-view__item-actions">
                        <?php if ($product->is_in_stock()): ?>
                            <div class="mc-wishlist-view__item-quantity">
                                <label for="quantity-<?php echo esc_attr($item['id']); ?>" class="screen-reader-text">
                                    <?php esc_html_e('Quantity', 'mesmeric-commerce'); ?>
                                </label>
                                <input type="number"
                                       id="quantity-<?php echo esc_attr($item['id']); ?>"
                                       class="mc-wishlist-view__quantity-input"
                                       value="<?php echo esc_attr($item['quantity']); ?>"
                                       min="1"
                                       @change="updateQuantity(<?php echo esc_js($item['id']); ?>, $event.target.value)"
                                       :disabled="isUpdating">
                            </div>
                            <form action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" class="mc-wishlist-view__add-to-cart">
                                <?php if ($product->is_type('simple')): ?>
                                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>">
                                    <input type="hidden" name="quantity" value="<?php echo esc_attr($item['quantity']); ?>">
                                    <button type="submit" class="mc-wishlist-view__add-to-cart-button">
                                        <?php esc_html_e('Add to Cart', 'mesmeric-commerce'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="mc-wishlist-view__add-to-cart-button">
                                        <?php esc_html_e('Select Options', 'mesmeric-commerce'); ?>
                                    </a>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                        <button type="button"
                                @click="removeItem(<?php echo esc_js($item['id']); ?>)"
                                class="mc-wishlist-view__remove-button"
                                :disabled="isRemoving">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <span class="screen-reader-text">
                                <?php esc_html_e('Remove', 'mesmeric-commerce'); ?>
                            </span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mc-wishlist-view__share-modal"
         x-show="shareUrl"
         @click.away="shareUrl = ''">
        <div class="mc-wishlist-view__share-content">
            <h4 class="mc-wishlist-view__share-title">
                <?php esc_html_e('Share Wishlist', 'mesmeric-commerce'); ?>
            </h4>
            <div class="mc-wishlist-view__share-url">
                <input type="text"
                       :value="shareUrl"
                       readonly
                       class="mc-wishlist-view__share-input"
                       @click="$event.target.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText(shareUrl)"
                        class="mc-wishlist-view__copy-button">
                    <?php esc_html_e('Copy', 'mesmeric-commerce'); ?>
                </button>
            </div>
            <button type="button"
                    @click="shareUrl = ''"
                    class="mc-wishlist-view__close-button">
                <?php esc_html_e('Close', 'mesmeric-commerce'); ?>
            </button>
        </div>
    </div>
</div>
