<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\AddToCartText\Admin;

use MesmericCommerce\Core\Admin\AbstractMetabox;
use MesmericCommerce\Modules\AddToCartText\AddToCartTextModule;

/**
 * Add To Cart Text Metabox
 * 
 * Adds custom fields to product pages for customizing add to cart button text.
 */
class AddToCartTextMetabox extends AbstractMetabox {
    public function initialize(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveMetaBox']);
    }

    public function addMetaBox(): void {
        add_meta_box(
            'mesmeric_add_to_cart_text',
            __('Add To Cart Text', 'mesmeric-commerce'),
            [$this, 'renderMetaBox'],
            'product',
            'side',
            'default'
        );
    }

    public function renderMetaBox(\WP_Post $post): void {
        wp_nonce_field('mesmeric_add_to_cart_text_nonce', 'mesmeric_add_to_cart_text_nonce');

        $singleLabel = get_post_meta($post->ID, '_mesmeric_add_to_cart_text_single_label', true);
        $shopLabel = get_post_meta($post->ID, '_mesmeric_add_to_cart_text_shop_label', true);

        echo $this->twig->render('admin/metabox.twig', [
            'single_label' => $singleLabel,
            'shop_label' => $shopLabel,
            'labels' => [
                'single' => __('Label on single product page', 'mesmeric-commerce'),
                'shop' => __('Label on shop pages', 'mesmeric-commerce'),
                'desc' => __('Leave empty to use global settings.', 'mesmeric-commerce'),
            ],
        ]);
    }

    public function saveMetaBox(int $postId): void {
        if (!$this->canSaveMetaBox($postId, 'mesmeric_add_to_cart_text_nonce')) {
            return;
        }

        $singleLabel = sanitize_text_field($_POST['_mesmeric_add_to_cart_text_single_label'] ?? '');
        $shopLabel = sanitize_text_field($_POST['_mesmeric_add_to_cart_text_shop_label'] ?? '');

        update_post_meta($postId, '_mesmeric_add_to_cart_text_single_label', $singleLabel);
        update_post_meta($postId, '_mesmeric_add_to_cart_text_shop_label', $shopLabel);
    }
}
