<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\CartReservedTimer\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Cart Reserved Timer Module Options
 */
class ModuleOptions extends AbstractOptions
{
    /**
     * Get module settings fields.
     *
     * @return array The settings fields.
     */
    public function getFields(): array
    {
        $beforeFields = [];
        if (function_exists('mesmeric_is_cart_block_layout') && mesmeric_is_cart_block_layout()) {
            $beforeFields[] = [
                'type' => 'warning',
                'content' => sprintf(
                    /* Translators: 1. docs link */
                    __('Your cart page is being rendered through the new WooCommerce cart block. You must edit the cart page to use the classic cart shortcode instead. Check <a href="%1$s" target="_blank">this documentation</a> to learn more.', 'mesmeric-commerce'),
                    'https://docs.athemes.com/article/how-to-switch-cart-checkout-blocks-to-the-classic-shortcodes/'
                ),
            ];
        }

        return [
            'settings' => [
                'title' => __('Settings', 'mesmeric-commerce'),
                'fields' => array_merge($beforeFields, [
                    'duration' => [
                        'type' => 'number',
                        'title' => __('Count down duration minutes', 'mesmeric-commerce'),
                        'default' => 10,
                        'min' => 1,
                        'max' => 60,
                    ],
                    'reserved_message' => [
                        'type' => 'text',
                        'title' => __('Cart reserved message', 'mesmeric-commerce'),
                        'default' => __('An item in your cart is in high demand.', 'mesmeric-commerce'),
                    ],
                    'timer_message_minutes' => [
                        'type' => 'text',
                        'title' => __('Timer message for > 1 min', 'mesmeric-commerce'),
                        'default' => __('Your cart is saved for {timer} minutes!', 'mesmeric-commerce'),
                        'description' => __('Use {timer} placeholder for the countdown timer.', 'mesmeric-commerce'),
                    ],
                    'timer_message_seconds' => [
                        'type' => 'text',
                        'title' => __('Timer message for < 1 min', 'mesmeric-commerce'),
                        'default' => __('Your cart is saved for {timer} seconds!', 'mesmeric-commerce'),
                        'description' => __('Use {timer} placeholder for the countdown timer.', 'mesmeric-commerce'),
                    ],
                    'time_expires' => [
                        'type' => 'radio',
                        'title' => __('What to do after the timer expires?', 'mesmeric-commerce'),
                        'options' => [
                            'hide-timer' => __('Hide timer', 'mesmeric-commerce'),
                            'clear-cart' => __('Clear cart', 'mesmeric-commerce'),
                        ],
                        'default' => 'clear-cart',
                    ],
                    'icon' => [
                        'type' => 'choices',
                        'title' => __('Choose an icon', 'mesmeric-commerce'),
                        'class' => 'mesmeric-module-page-setting-field-choices-icon',
                        'options' => [
                            'none' => [
                                'image' => $this->getModuleUrl() . '/assets/images/icons/cancel.svg',
                                'title' => __('None', 'mesmeric-commerce'),
                            ],
                            'fire' => [
                                'image' => $this->getModuleUrl() . '/assets/images/icons/fire.svg',
                                'title' => __('Fire', 'mesmeric-commerce'),
                            ],
                            'clock' => [
                                'image' => $this->getModuleUrl() . '/assets/images/icons/clock.svg',
                                'title' => __('Clock', 'mesmeric-commerce'),
                            ],
                            'hour-glass' => [
                                'image' => $this->getModuleUrl() . '/assets/images/icons/hour-glass.svg',
                                'title' => __('Hour Glass', 'mesmeric-commerce'),
                            ],
                        ],
                        'default' => 'fire',
                    ],
                    'background_color' => [
                        'type' => 'color',
                        'title' => __('Background Color', 'mesmeric-commerce'),
                        'default' => '#f4f6f8',
                        'css_var' => '--mesmeric-bg-color',
                    ],
                ]),
            ],
        ];
    }
}
