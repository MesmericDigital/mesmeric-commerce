<?php
/**
 * Quick view content template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/quickview/views
 *
 * @var WC_Product $product
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$post_thumbnail_id = $product->get_image_id();
$gallery_image_ids = $product->get_gallery_image_ids();
?>

<div class="mc-quickview-content" x-data="{
    currentSlide: 0,
    totalSlides: <?php echo count($gallery_image_ids) + 1; ?>,
    quantity: 1,
    loading: false,
    saving: false,
    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
    },
    prevSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
    },
    async addToCart() {
        if (this.loading) return;
        this.loading = true;

        try {
            const response = await fetch(mcQuickView.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_add_to_cart',
                    nonce: mcQuickView.nonce,
                    product_id: <?php echo $product->get_id(); ?>,
                    quantity: this.quantity
                })
            });

            const data = await response.json();
            if (data.success) {
                // Update cart fragments
                if (data.fragments) {
                    jQuery.each(data.fragments, function(key, value) {
                        jQuery(key).replaceWith(value);
                    });
                }
                // Show success message
                this.$dispatch('show-notification', {
                    message: data.message,
                    type: 'success'
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.$dispatch('show-notification', {
                message: error.message || mcQuickView.i18n.error,
                type: 'error'
            });
        } finally {
            this.loading = false;
        }
    },
    async saveForLater() {
        if (this.saving) return;
        this.saving = true;

        try {
            const response = await fetch(mcQuickView.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mc_save_for_later',
                    nonce: mcQuickView.nonce,
                    product_id: <?php echo $product->get_id(); ?>
                })
            });

            const data = await response.json();
            if (data.success) {
                this.$dispatch('show-notification', {
                    message: data.message,
                    type: 'success'
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.$dispatch('show-notification', {
                message: error.message || mcQuickView.i18n.error,
                type: 'error'
            });
        } finally {
            this.saving = false;
        }
    }
}">
    <!-- Close button -->
    <button
        @click="$parent.open = false"
        class="mc-quickview-close absolute top-4 right-4 text-gray-500 hover:text-gray-700 focus:outline-none"
        aria-label="<?php esc_attr_e('Close', 'mesmeric-commerce'); ?>"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="mc-quickview-grid grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
        <!-- Product gallery -->
        <div class="mc-quickview-gallery relative">
            <!-- Main image -->
            <div class="relative aspect-square overflow-hidden rounded-lg bg-gray-100">
                <template x-for="(image, index) in [
                    '<?php echo wp_get_attachment_image_url($post_thumbnail_id, 'large'); ?>',
                    <?php
                    foreach ($gallery_image_ids as $image_id) {
                        echo "'" . wp_get_attachment_image_url($image_id, 'large') . "',";
                    }
                    ?>
                ]" :key="index">
                    <img
                        :src="image"
                        :alt="<?php echo json_encode($product->get_name()); ?>"
                        class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
                        :class="currentSlide === index ? 'opacity-100' : 'opacity-0'"
                    >
                </template>

                <!-- Navigation arrows -->
                <button
                    @click="prevSlide"
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2 hover:bg-white focus:outline-none"
                    :class="{ 'opacity-50 cursor-not-allowed': totalSlides <= 1 }"
                    :disabled="totalSlides <= 1"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    @click="nextSlide"
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2 hover:bg-white focus:outline-none"
                    :class="{ 'opacity-50 cursor-not-allowed': totalSlides <= 1 }"
                    :disabled="totalSlides <= 1"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Thumbnails -->
            <?php if (!empty($gallery_image_ids)) : ?>
            <div class="mt-4 grid grid-cols-4 gap-4">
                <button
                    @click="currentSlide = 0"
                    class="aspect-square rounded-lg overflow-hidden focus:outline-none"
                    :class="{ 'ring-2 ring-primary': currentSlide === 0 }"
                >
                    <?php echo wp_get_attachment_image($post_thumbnail_id, 'thumbnail', false, ['class' => 'w-full h-full object-cover']); ?>
                </button>
                <?php foreach ($gallery_image_ids as $index => $image_id) : ?>
                <button
                    @click="currentSlide = <?php echo $index + 1; ?>"
                    class="aspect-square rounded-lg overflow-hidden focus:outline-none"
                    :class="{ 'ring-2 ring-primary': currentSlide === <?php echo $index + 1; ?> }"
                >
                    <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, ['class' => 'w-full h-full object-cover']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product details -->
        <div class="mc-quickview-details">
            <h2 class="text-2xl font-bold mb-4"><?php echo $product->get_name(); ?></h2>

            <!-- Price -->
            <div class="text-xl mb-6">
                <?php echo $product->get_price_html(); ?>
            </div>

            <!-- Rating -->
            <?php if (wc_review_ratings_enabled()) : ?>
            <div class="flex items-center mb-4">
                <?php echo wc_get_rating_html($product->get_average_rating()); ?>
                <?php if ($review_count = $product->get_review_count()) : ?>
                <span class="ml-2 text-sm text-gray-500">
                    <?php echo sprintf(_n('(%d review)', '(%d reviews)', $review_count, 'mesmeric-commerce'), $review_count); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Short description -->
            <?php if ($short_description = $product->get_short_description()) : ?>
            <div class="prose prose-sm mb-6">
                <?php echo wp_kses_post($short_description); ?>
            </div>
            <?php endif; ?>

            <!-- Add to cart form -->
            <div class="space-y-4">
                <!-- Quantity -->
                <div class="flex items-center space-x-4">
                    <label for="quantity" class="font-medium"><?php esc_html_e('Quantity:', 'mesmeric-commerce'); ?></label>
                    <div class="flex items-center">
                        <button
                            @click="quantity = Math.max(1, quantity - 1)"
                            class="btn btn-square btn-sm"
                            :disabled="quantity <= 1"
                        >-</button>
                        <input
                            type="number"
                            id="quantity"
                            x-model.number="quantity"
                            min="1"
                            max="<?php echo $product->get_max_purchase_quantity(); ?>"
                            class="w-16 text-center mx-2"
                        >
                        <button
                            @click="quantity = Math.min(<?php echo $product->get_max_purchase_quantity(); ?>, quantity + 1)"
                            class="btn btn-square btn-sm"
                            :disabled="quantity >= <?php echo $product->get_max_purchase_quantity(); ?>"
                        >+</button>
                    </div>
                </div>

                <!-- Stock status -->
                <div class="text-sm">
                    <?php if ($product->is_in_stock()) : ?>
                        <span class="text-success"><?php esc_html_e('In stock', 'mesmeric-commerce'); ?></span>
                        <?php if ($product->managing_stock()) : ?>
                            <span class="text-gray-500">(<?php echo $product->get_stock_quantity(); ?> <?php esc_html_e('available', 'mesmeric-commerce'); ?>)</span>
                        <?php endif; ?>
                    <?php else : ?>
                        <span class="text-error"><?php esc_html_e('Out of stock', 'mesmeric-commerce'); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="flex space-x-4">
                    <button
                        @click="addToCart"
                        class="btn btn-primary flex-1"
                        :disabled="loading || !<?php echo $product->is_in_stock() ? 'true' : 'false'; ?>"
                    >
                        <span x-show="!loading"><?php esc_html_e('Add to Cart', 'mesmeric-commerce'); ?></span>
                        <span x-show="loading" class="loading loading-spinner"></span>
                    </button>
                    <button
                        @click="saveForLater"
                        class="btn btn-outline"
                        :disabled="saving"
                    >
                        <span x-show="!saving">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </span>
                        <span x-show="saving" class="loading loading-spinner"></span>
                    </button>
                </div>
            </div>

            <!-- Additional information -->
            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-medium mb-4"><?php esc_html_e('Additional Information', 'mesmeric-commerce'); ?></h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <?php if ($sku = $product->get_sku()) : ?>
                    <dt class="font-medium"><?php esc_html_e('SKU:', 'mesmeric-commerce'); ?></dt>
                    <dd><?php echo esc_html($sku); ?></dd>
                    <?php endif; ?>

                    <?php if ($categories = wc_get_product_category_list($product->get_id())) : ?>
                    <dt class="font-medium"><?php esc_html_e('Categories:', 'mesmeric-commerce'); ?></dt>
                    <dd><?php echo $categories; ?></dd>
                    <?php endif; ?>

                    <?php if ($tags = wc_get_product_tag_list($product->get_id())) : ?>
                    <dt class="font-medium"><?php esc_html_e('Tags:', 'mesmeric-commerce'); ?></dt>
                    <dd><?php echo $tags; ?></dd>
                    <?php endif; ?>

                    <?php if ($weight = $product->get_weight()) : ?>
                    <dt class="font-medium"><?php esc_html_e('Weight:', 'mesmeric-commerce'); ?></dt>
                    <dd><?php echo esc_html($weight) . ' ' . get_option('woocommerce_weight_unit'); ?></dd>
                    <?php endif; ?>

                    <?php if ($dimensions = $product->get_dimensions(false)) : ?>
                    <dt class="font-medium"><?php esc_html_e('Dimensions:', 'mesmeric-commerce'); ?></dt>
                    <dd><?php echo esc_html($dimensions); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
