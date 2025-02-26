<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\AddedToCartPopup\Options;

use MesmericCommerce\Core\Options\AbstractOptions;

/**
 * Added to Cart Popup Module Options
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
        return [
            'popup_settings' => [
                'title' => __('Popup Settings', 'mesmeric-commerce'),
                'fields' => [
                    'layout' => [
                        'type' => 'image_picker',
                        'title' => __('Select layout', 'mesmeric-commerce'),
                        'options' => [
                            'layout-1' => [
                                'image' => $this->getModuleUrl() . '/assets/images/admin/layout-1.png',
                                'title' => __('Layout 1', 'mesmeric-commerce'),
                            ],
                            'layout-2' => [
                                'image' => $this->getModuleUrl() . '/assets/images/admin/layout-2.png',
                                'title' => __('Layout 2', 'mesmeric-commerce'),
                            ],
                            'layout-3' => [
                                'image' => $this->getModuleUrl() . '/assets/images/admin/layout-3.png',
                                'title' => __('Layout 3', 'mesmeric-commerce'),
                            ],
                        ],
                        'default' => 'layout-1',
                    ],
                    'popup_size' => [
                        'type' => 'range',
                        'title' => __('Popup size', 'mesmeric-commerce'),
                        'min' => 700,
                        'max' => 1300,
                        'step' => 1,
                        'unit' => 'px',
                        'default' => 1000,
                    ],
                    'popup_message' => [
                        'type' => 'text',
                        'title' => __('Popup message', 'mesmeric-commerce'),
                        'description' => __('This message will be shown at the top of the popup', 'mesmeric-commerce'),
                        'default' => __('Added to cart', 'mesmeric-commerce'),
                    ],
                    'show_product_info' => [
                        'type' => 'checkbox_multiple',
                        'title' => __('Show product info', 'mesmeric-commerce'),
                        'options' => [
                            'thumbnail' => __('Product thumbnail', 'mesmeric-commerce'),
                            'title_and_price' => __('Product title and price', 'mesmeric-commerce'),
                            'description' => __('Product description', 'mesmeric-commerce'),
                        ],
                        'default' => ['title_and_price', 'description', 'thumbnail'],
                    ],
                    'description_type' => [
                        'type' => 'radio',
                        'title' => __('Description type', 'mesmeric-commerce'),
                        'options' => [
                            'full' => __('Full description', 'mesmeric-commerce'),
                            'short' => __('Short description', 'mesmeric-commerce'),
                        ],
                        'default' => 'short',
                        'conditions' => [
                            'show_product_info' => ['contains' => 'description'],
                        ],
                    ],
                    'description_length' => [
                        'type' => 'range',
                        'title' => __('Maximum product description length', 'mesmeric-commerce'),
                        'min' => 5,
                        'max' => 60,
                        'step' => 1,
                        'unit' => __('Words', 'mesmeric-commerce'),
                        'default' => 15,
                        'conditions' => [
                            'show_product_info' => ['contains' => 'description'],
                        ],
                    ],
                    'show_cart_totals' => [
                        'type' => 'switcher',
                        'title' => __('Show cart totals', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'show_view_cart_button' => [
                        'type' => 'switcher',
                        'title' => __('Show view cart button', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'view_cart_button_label' => [
                        'type' => 'text',
                        'title' => __('View cart button label', 'mesmeric-commerce'),
                        'default' => __('View Cart', 'mesmeric-commerce'),
                        'conditions' => [
                            'show_view_cart_button' => true,
                        ],
                    ],
                    'show_continue_shopping_button' => [
                        'type' => 'switcher',
                        'title' => __('Show continue shopping button', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'view_continue_shopping_button_label' => [
                        'type' => 'text',
                        'title' => __('Continue shopping button label', 'mesmeric-commerce'),
                        'default' => __('Continue Shopping', 'mesmeric-commerce'),
                        'conditions' => [
                            'show_continue_shopping_button' => true,
                        ],
                    ],
                    'show_suggested_products' => [
                        'type' => 'switcher',
                        'title' => __('Show suggested products', 'mesmeric-commerce'),
                        'default' => true,
                    ],
                    'suggested_products_source' => [
                        'type' => 'select',
                        'title' => __('Suggested products source', 'mesmeric-commerce'),
                        'options' => [
                            'related' => __('Related Products', 'mesmeric-commerce'),
                            'recently_viewed' => __('Recently Viewed', 'mesmeric-commerce'),
                            'frequently_bought' => __('Frequently Bought Together', 'mesmeric-commerce'),
                            'buy_x_get_y' => __('Buy X Get Y', 'mesmeric-commerce'),
                        ],
                        'default' => 'related',
                        'conditions' => [
                            'show_suggested_products' => true,
                        ],
                    ],
                    'show_devices' => [
                        'type' => 'checkbox_multiple',
                        'title' => __('Show on devices', 'mesmeric-commerce'),
                        'options' => [
                            'desktop' => __('Desktop', 'mesmeric-commerce'),
                            'tablet' => __('Tablet', 'mesmeric-commerce'),
                            'mobile' => __('Mobile', 'mesmeric-commerce'),
                        ],
                        'default' => ['desktop', 'tablet', 'mobile'],
                    ],
                ],
            ],
            'look_and_feel' => [
                'title' => __('Look and Feel', 'mesmeric-commerce'),
                'fields' => [
                    'popup_animation' => [
                        'type' => 'select',
                        'title' => __('Popup animation', 'mesmeric-commerce'),
                        'options' => [
                            'fade' => __('Fade', 'mesmeric-commerce'),
                            'slide' => __('Slide', 'mesmeric-commerce'),
                            'zoom' => __('Zoom', 'mesmeric-commerce'),
                        ],
                        'default' => 'fade',
                    ],
                    'popup_overlay_color' => [
                        'type' => 'color',
                        'title' => __('Popup overlay color', 'mesmeric-commerce'),
                        'default' => 'rgba(0, 0, 0, 0.8)',
                    ],
                    'popup_background_color' => [
                        'type' => 'color',
                        'title' => __('Popup background color', 'mesmeric-commerce'),
                        'default' => '#ffffff',
                    ],
                    'popup_text_color' => [
                        'type' => 'color',
                        'title' => __('Popup text color', 'mesmeric-commerce'),
                        'default' => '#333333',
                    ],
                ],
            ],
        ];
    }
}
