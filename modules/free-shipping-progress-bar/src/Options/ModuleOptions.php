<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\FreeShippingProgressBar\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Free Shipping Progress Bar Module Options
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
                    'message_initial' => [
                        'type' => 'text',
                        'title' => __('Initial message', 'mesmeric-commerce'),
                        'default' => __('Add {amount} to your cart to get free shipping!', 'mesmeric-commerce'),
                        'description' => __('Message shown when cart is empty. Use {amount} for the minimum amount.', 'mesmeric-commerce'),
                    ],
                    'message_progress' => [
                        'type' => 'text',
                        'title' => __('Progress message', 'mesmeric-commerce'),
                        'default' => __('Add {remaining} more to get free shipping!', 'mesmeric-commerce'),
                        'description' => __('Message shown during progress. Use {remaining} for the remaining amount and {amount} for the minimum amount.', 'mesmeric-commerce'),
                    ],
                    'message_achieved' => [
                        'type' => 'text',
                        'title' => __('Achieved message', 'mesmeric-commerce'),
                        'default' => __('🎉 Congratulations! You get free shipping!', 'mesmeric-commerce'),
                        'description' => __('Message shown when free shipping is achieved.', 'mesmeric-commerce'),
                    ],
                    'theme_preset' => [
                        'type' => 'select',
                        'title' => __('Theme preset', 'mesmeric-commerce'),
                        'default' => 'clean_slate',
                        'options' => [
                            'clean_slate' => __('Clean Slate', 'mesmeric-commerce'),
                            'solar_night' => __('Solar Night', 'mesmeric-commerce'),
                            'lively_breeze' => __('Lively Breeze', 'mesmeric-commerce'),
                            'midnight_tide' => __('Midnight Tide', 'mesmeric-commerce'),
                            'fresh_frost' => __('Fresh Frost', 'mesmeric-commerce'),
                            'sky_harmony' => __('Sky Harmony', 'mesmeric-commerce'),
                            'custom' => __('Custom', 'mesmeric-commerce'),
                        ],
                    ],
                    'card_background_color' => [
                        'type' => 'color',
                        'title' => __('Card background color', 'mesmeric-commerce'),
                        'default' => '#FFFFFF',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-card-bg',
                    ],
                    'card_text_color' => [
                        'type' => 'color',
                        'title' => __('Card text color', 'mesmeric-commerce'),
                        'default' => '#202223',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-text',
                    ],
                    'variable_text_color' => [
                        'type' => 'color',
                        'title' => __('Variable text color', 'mesmeric-commerce'),
                        'default' => '#202223',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-variable-text',
                    ],
                    'bar_background_color' => [
                        'type' => 'color',
                        'title' => __('Progress bar background', 'mesmeric-commerce'),
                        'default' => '#E4E5E7',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-bar-bg',
                    ],
                    'bar_foreground_color' => [
                        'type' => 'color',
                        'title' => __('Progress bar color', 'mesmeric-commerce'),
                        'default' => '#000000',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-bar-fg',
                    ],
                    'card_border_radius' => [
                        'type' => 'range',
                        'title' => __('Card border radius', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                        'default' => 8,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-card-radius',
                    ],
                    'card_border_width' => [
                        'type' => 'range',
                        'title' => __('Card border width', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 10,
                        'step' => 1,
                        'default' => 0,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-card-border-width',
                    ],
                    'card_border_color' => [
                        'type' => 'color',
                        'title' => __('Card border color', 'mesmeric-commerce'),
                        'default' => '#c5c8d1',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-card-border-color',
                    ],
                    'bar_border_radius' => [
                        'type' => 'range',
                        'title' => __('Progress bar radius', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                        'default' => 4,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-bar-radius',
                    ],
                    'card_font_size' => [
                        'type' => 'range',
                        'title' => __('Font size', 'mesmeric-commerce'),
                        'min' => 12,
                        'max' => 24,
                        'step' => 1,
                        'default' => 18,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-font-size',
                    ],
                    'card_padding_y' => [
                        'type' => 'range',
                        'title' => __('Vertical padding', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                        'default' => 16,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-padding-y',
                    ],
                    'card_padding_x' => [
                        'type' => 'range',
                        'title' => __('Horizontal padding', 'mesmeric-commerce'),
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'default' => 24,
                        'unit' => 'px',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-padding-x',
                    ],
                    'bar_width' => [
                        'type' => 'range',
                        'title' => __('Progress bar width', 'mesmeric-commerce'),
                        'min' => 50,
                        'max' => 100,
                        'step' => 1,
                        'default' => 90,
                        'unit' => '%',
                        'condition' => ['theme_preset', '==', 'custom'],
                        'css_var' => '--mesmeric-fspb-bar-width',
                    ],
                ],
            ],
            'display_settings' => [
                'title' => __('Display Settings', 'mesmeric-commerce'),
                'fields' => [
                    'show_on_cart' => [
                        'type' => 'toggle',
                        'title' => __('Show on cart page', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_on_checkout' => [
                        'type' => 'toggle',
                        'title' => __('Show on checkout page', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_on_mini_cart' => [
                        'type' => 'toggle',
                        'title' => __('Show in mini cart', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                ],
            ],
        ];
    }
}
