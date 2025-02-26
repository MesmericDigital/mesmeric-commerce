<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\QuickView\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Quick View Module Options
 * 
 * @since 1.0.0
 */
class ModuleOptions extends AbstractOptions
{
    protected function getDefaults(): array
    {
        return [
            // Button settings
            'button_type' => 'text',
            'button_text' => __('Quick View', 'mesmeric-commerce'),
            'button_icon' => 'eye',
            'button_position' => 'overlay',
            'button_position_top' => 50,
            'button_position_left' => 50,
            'mobile_position' => false,
            'button_position_top_mobile' => 50,
            'button_position_left_mobile' => 50,

            // Colors
            'icon_color' => '#ffffff',
            'icon_hover_color' => '#ffffff',
            'text_color' => '#ffffff',
            'text_hover_color' => '#ffffff',
            'border_color' => '#212121',
            'border_hover_color' => '#414141',
            'background_color' => '#212121',
            'background_hover_color' => '#414141',

            // Modal settings
            'modal_width' => 1000,
            'modal_height' => 500,
            'place_product_image' => 'thumbs-at-left',
            'zoom_effect' => true,
            'place_product_description' => 'top',
            'description_style' => 'short',
            'show_quantity' => true,

            // Integration settings
            'show_buy_now_button' => false,
            'show_suggested_products' => false,
            'suggested_products_module' => 'bulk_discounts',
            'suggested_products_placement' => 'after_add_to_cart',

            // Advanced settings
            'animation_duration' => 300,
            'delay_before_show' => 0,
            'shortcode_enabled' => false,
            'custom_css' => '',
        ];
    }

    protected function getSchema(): array
    {
        return [
            'button_type' => [
                'type' => 'string',
                'enum' => ['text', 'icon', 'icon-text'],
                'required' => true,
            ],
            'button_text' => [
                'type' => 'string',
                'required' => true,
                'sanitize' => 'sanitize_text_field',
            ],
            'button_icon' => [
                'type' => 'string',
                'enum' => ['eye', 'cart'],
                'required' => true,
            ],
            'button_position' => [
                'type' => 'string',
                'enum' => ['before', 'after', 'overlay'],
                'required' => true,
            ],
            'button_position_top' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
                'required' => true,
            ],
            'button_position_left' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
                'required' => true,
            ],
            'mobile_position' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'button_position_top_mobile' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
                'required' => true,
            ],
            'button_position_left_mobile' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
                'required' => true,
            ],
            'icon_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'icon_hover_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'text_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'text_hover_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'border_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'border_hover_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'background_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'background_hover_color' => [
                'type' => 'string',
                'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
                'required' => true,
            ],
            'modal_width' => [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 2000,
                'required' => true,
            ],
            'modal_height' => [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 2000,
                'required' => true,
            ],
            'place_product_image' => [
                'type' => 'string',
                'enum' => ['thumbs-at-left', 'thumbs-at-right', 'thumbs-at-bottom'],
                'required' => true,
            ],
            'zoom_effect' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'place_product_description' => [
                'type' => 'string',
                'enum' => ['top', 'bottom'],
                'required' => true,
            ],
            'description_style' => [
                'type' => 'string',
                'enum' => ['full', 'short'],
                'required' => true,
            ],
            'show_quantity' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'show_buy_now_button' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'show_suggested_products' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'suggested_products_module' => [
                'type' => 'string',
                'enum' => ['bulk_discounts', 'buy_x_get_y', 'frequently_bought_together'],
                'required' => true,
            ],
            'suggested_products_placement' => [
                'type' => 'string',
                'enum' => ['before_add_to_cart', 'after_add_to_cart', 'after_description'],
                'required' => true,
            ],
            'animation_duration' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 2000,
                'required' => true,
            ],
            'delay_before_show' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10000,
                'required' => true,
            ],
            'shortcode_enabled' => [
                'type' => 'boolean',
                'required' => true,
            ],
            'custom_css' => [
                'type' => 'string',
                'sanitize' => 'wp_strip_all_tags',
            ],
        ];
    }
}
