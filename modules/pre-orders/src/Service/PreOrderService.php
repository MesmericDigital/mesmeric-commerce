<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\PreOrders\Service;

use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use MesmericCommerce\Core\Options\OptionsManager;
use WC_Order;
use WC_Product;
use WC_Order_Item_Product;

/**
 * Pre Order Service
 * 
 * Handles core pre-order functionality like validation, cart management,
 * and order processing.
 */
class PreOrderService
{
    private OptionsManager $optionsManager;
    private AnalyticsLogger $analyticsLogger;

    public function __construct(
        OptionsManager $optionsManager,
        AnalyticsLogger $analyticsLogger
    ) {
        $this->optionsManager = $optionsManager;
        $this->analyticsLogger = $analyticsLogger;
    }

    /**
     * Check if a product can be pre-ordered.
     */
    public function isPreOrderable(WC_Product $product): bool
    {
        $settings = $this->optionsManager->getModuleSettings('pre-orders');
        $rules = $settings['rules'] ?? [];

        foreach ($rules as $rule) {
            if ($this->productMatchesRule($product, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the pre-order rule that applies to a product.
     *
     * @return array<string, mixed>|null
     */
    public function getApplicableRule(WC_Product $product): ?array
    {
        $settings = $this->optionsManager->getModuleSettings('pre-orders');
        $rules = $settings['rules'] ?? [];

        foreach ($rules as $rule) {
            if ($this->productMatchesRule($product, $rule)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Check if a product matches a pre-order rule.
     *
     * @param array<string, mixed> $rule
     */
    private function productMatchesRule(WC_Product $product, array $rule): bool
    {
        if (empty($rule['condition']) || $rule['condition'] === 'all') {
            return true;
        }

        $productId = $product->get_id();
        $categoryIds = $product->get_category_ids();

        return match ($rule['condition']) {
            'specific_products' => in_array($productId, $rule['products'] ?? [], true),
            'specific_categories' => array_intersect($categoryIds, $rule['categories'] ?? []) !== [],
            'out_of_stock' => !$product->is_in_stock(),
            'low_stock' => $product->get_stock_quantity() <= ($rule['low_stock_threshold'] ?? 5),
            default => false,
        };
    }

    /**
     * Get the shipping date for a pre-order.
     */
    public function getShippingDate(WC_Product $product): int
    {
        $rule = $this->getApplicableRule($product);
        
        if (!$rule) {
            return 0;
        }

        return (int) ($rule['shipping_date'] ?? time() + (30 * DAY_IN_SECONDS));
    }

    /**
     * Check if cart contains pre-order items.
     */
    public function cartHasPreOrders(): bool
    {
        if (!WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {
            if (!empty($cartItem['pre_order'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cart contains regular (non-pre-order) items.
     */
    public function cartHasRegularItems(): bool
    {
        if (!WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {
            if (empty($cartItem['pre_order'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split cart into pre-order and regular items.
     *
     * @return array{
     *     pre_order: array<string, array<string, mixed>>,
     *     regular: array<string, array<string, mixed>>
     * }
     */
    public function splitCart(): array
    {
        $cart = WC()->cart->get_cart();
        $split = [
            'pre_order' => [],
            'regular' => [],
        ];

        foreach ($cart as $cartKey => $cartItem) {
            if (!empty($cartItem['pre_order'])) {
                $split['pre_order'][$cartKey] = $cartItem;
            } else {
                $split['regular'][$cartKey] = $cartItem;
            }
        }

        return $split;
    }

    /**
     * Apply pre-order discount to cart item.
     *
     * @param array<string, mixed> $cartItem
     */
    public function applyPreOrderDiscount(array &$cartItem): void
    {
        $product = $cartItem['data'];
        if (!($product instanceof WC_Product)) {
            return;
        }

        $rule = $this->getApplicableRule($product);
        if (!$rule || empty($rule['discount'])) {
            return;
        }

        $discount = (float) $rule['discount'];
        $discountType = $rule['discount_type'] ?? 'percentage';
        $originalPrice = (float) $product->get_price();

        $discountedPrice = match ($discountType) {
            'percentage' => $originalPrice * (1 - ($discount / 100)),
            'fixed' => max(0, $originalPrice - $discount),
            default => $originalPrice,
        };

        $product->set_price($discountedPrice);
    }

    /**
     * Add pre-order meta to order item.
     */
    public function addOrderItemMeta(WC_Order_Item_Product $item, WC_Product $product): void
    {
        if (!$this->isPreOrderable($product)) {
            return;
        }

        $shippingDate = $this->getShippingDate($product);
        $rule = $this->getApplicableRule($product);

        $item->add_meta_data('_pre_order', true);
        $item->add_meta_data('_pre_order_shipping_date', $shippingDate);
        
        if ($rule && !empty($rule['discount'])) {
            $item->add_meta_data('_pre_order_discount', [
                'amount' => $rule['discount'],
                'type' => $rule['discount_type'] ?? 'percentage',
            ]);
        }
    }

    /**
     * Log pre-order analytics event.
     */
    public function logAnalyticsEvent(string $eventType, int $productId, array $metadata = []): void
    {
        $this->analyticsLogger->logEvent([
            'event_type' => $eventType,
            'source_product_id' => $productId,
            'customer_id' => WC()->session ? WC()->session->get_customer_id() : 0,
            'module_id' => 'pre-orders',
            'meta_data' => $metadata,
        ]);
    }
}
