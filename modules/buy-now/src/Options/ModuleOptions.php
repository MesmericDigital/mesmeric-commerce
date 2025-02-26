<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\BuyNow\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Buy Now Module Options
 */
class ModuleOptions extends AbstractOptions
{
    /**
     * Get module settings fields
     */
    public function getFields(): array
    {
        return [
            'settings' => [
                'title' => __('Settings', 'mesmeric-commerce'),
                'fields' => [
                    'button_text' => [
                        'type' => 'text',
                        'title' => __('Button text', 'mesmeric-commerce'),
                        'default' => __('Buy Now', 'mesmeric-commerce'),
                        'description' => __('Text to display on the buy now button', 'mesmeric-commerce'),
                    ],
                    'customize_button' => [
                        'type' => 'toggle',
                        'title' => __('Customize Button', 'mesmeric-commerce'),
                        'default' => true,
                        'description' => __('Enable to customize button styles or disable to inherit theme styles', 'mesmeric-commerce'),
                    ],
                    'text_color' => [
                        'type' => 'color',
                        'title' => __('Button text color', 'mesmeric-commerce'),
                        'default' => '#ffffff',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-text-color',
                    ],
                    'text_hover_color' => [
                        'type' => 'color',
                        'title' => __('Button text color hover', 'mesmeric-commerce'),
                        'default' => '#ffffff',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-text-hover-color',
                    ],
                    'border_color' => [
                        'type' => 'color',
                        'title' => __('Button border color', 'mesmeric-commerce'),
                        'default' => '#212121',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-border-color',
                    ],
                    'border_hover_color' => [
                        'type' => 'color',
                        'title' => __('Button border color hover', 'mesmeric-commerce'),
                        'default' => '#414141',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-border-hover-color',
                    ],
                    'background_color' => [
                        'type' => 'color',
                        'title' => __('Button background color', 'mesmeric-commerce'),
                        'default' => '#212121',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-bg-color',
                    ],
                    'background_hover_color' => [
                        'type' => 'color',
                        'title' => __('Button background color hover', 'mesmeric-commerce'),
                        'default' => '#414141',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-bg-hover-color',
                    ],
                    'font_size' => [
                        'type' => 'range',
                        'title' => __('Font size', 'mesmeric-commerce'),
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                        'default' => 16,
                        'unit' => 'px',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-font-size',
                    ],
                    'padding_y' => [
                        'type' => 'range',
                        'title' => __('Padding Top/Bottom', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'default' => 12,
                        'unit' => 'px',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-padding-y',
                    ],
                    'padding_x' => [
                        'type' => 'range',
                        'title' => __('Padding Left/Right', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'default' => 24,
                        'unit' => 'px',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-padding-x',
                    ],
                    'border_radius' => [
                        'type' => 'range',
                        'title' => __('Border radius', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                        'default' => 4,
                        'unit' => 'px',
                        'condition' => ['customize_button', '==', true],
                        'css_var' => '--mesmeric-buy-now-border-radius',
                    ],
                ],
            ],
            'display_settings' => [
                'title' => __('Display Settings', 'mesmeric-commerce'),
                'fields' => [
                    'show_on_archive' => [
                        'type' => 'toggle',
                        'title' => __('Show on shop/archive pages', 'mesmeric-commerce'),
                        'default' => true,
                        'description' => __('Display buy now button on shop and archive pages', 'mesmeric-commerce'),
                    ],
                    'show_on_single' => [
                        'type' => 'toggle',
                        'title' => __('Show on single product pages', 'mesmeric-commerce'),
                        'default' => true,
                        'description' => __('Display buy now button on single product pages', 'mesmeric-commerce'),
                    ],
                    'hook_order_single_product' => [
                        'type' => 'hook_select',
                        'title' => __('Single product button position', 'mesmeric-commerce'),
                        'default' => [
                            'hook_name' => 'woocommerce_after_add_to_cart_button',
                            'hook_priority' => 10,
                        ],
                        'condition' => ['show_on_single', '==', true],
                    ],
                    'hook_order_shop_archive' => [
                        'type' => 'hook_select',
                        'title' => __('Shop/archive button position', 'mesmeric-commerce'),
                        'default' => [
                            'hook_name' => 'woocommerce_after_shop_loop_item',
                            'hook_priority' => 10,
                        ],
                        'condition' => ['show_on_archive', '==', true],
                    ],
                ],
            ],
        ];
    }
}
