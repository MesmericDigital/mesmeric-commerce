<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\PreOrders\Service;

use MesmericCommerce\Core\Analytics\AnalyticsLogger;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Modules\PreOrders\Service\PreOrderService;
use PHPUnit\Framework\TestCase;
use WC_Product;
use WC_Order;
use WC_Order_Item_Product;

class PreOrderServiceTest extends TestCase
{
    private PreOrderService $service;
    private OptionsManager $optionsManager;
    private AnalyticsLogger $analyticsLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->analyticsLogger = $this->createMock(AnalyticsLogger::class);

        $this->service = new PreOrderService(
            $this->optionsManager,
            $this->analyticsLogger
        );
    }

    public function testIsPreOrderableWithNoRules(): void
    {
        $product = $this->createMock(WC_Product::class);
        
        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn(['rules' => []]);

        $this->assertFalse($this->service->isPreOrderable($product));
    }

    public function testIsPreOrderableWithMatchingRule(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(123);
        
        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn([
                'rules' => [
                    [
                        'condition' => 'specific_products',
                        'products' => [123],
                    ],
                ],
            ]);

        $this->assertTrue($this->service->isPreOrderable($product));
    }

    public function testGetApplicableRuleWithCategoryMatch(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_category_ids')->willReturn([456]);
        
        $rule = [
            'condition' => 'specific_categories',
            'categories' => [456],
            'shipping_date' => time() + 86400,
        ];

        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn(['rules' => [$rule]]);

        $this->assertEquals($rule, $this->service->getApplicableRule($product));
    }

    public function testGetShippingDateWithNoRule(): void
    {
        $product = $this->createMock(WC_Product::class);
        
        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn(['rules' => []]);

        $this->assertEquals(0, $this->service->getShippingDate($product));
    }

    public function testGetShippingDateWithRule(): void
    {
        $product = $this->createMock(WC_Product::class);
        $shippingDate = time() + 86400;
        
        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn([
                'rules' => [
                    [
                        'condition' => 'all',
                        'shipping_date' => $shippingDate,
                    ],
                ],
            ]);

        $this->assertEquals($shippingDate, $this->service->getShippingDate($product));
    }

    public function testApplyPreOrderDiscountPercentage(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_price')->willReturn('100.00');
        $product->expects($this->once())
            ->method('set_price')
            ->with(90.0);

        $cartItem = ['data' => $product];

        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn([
                'rules' => [
                    [
                        'condition' => 'all',
                        'discount' => 10,
                        'discount_type' => 'percentage',
                    ],
                ],
            ]);

        $this->service->applyPreOrderDiscount($cartItem);
    }

    public function testApplyPreOrderDiscountFixed(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_price')->willReturn('100.00');
        $product->expects($this->once())
            ->method('set_price')
            ->with(90.0);

        $cartItem = ['data' => $product];

        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn([
                'rules' => [
                    [
                        'condition' => 'all',
                        'discount' => 10,
                        'discount_type' => 'fixed',
                    ],
                ],
            ]);

        $this->service->applyPreOrderDiscount($cartItem);
    }

    public function testAddOrderItemMeta(): void
    {
        $product = $this->createMock(WC_Product::class);
        $orderItem = $this->createMock(WC_Order_Item_Product::class);
        $shippingDate = time() + 86400;

        $this->optionsManager->method('getModuleSettings')
            ->with('pre-orders')
            ->willReturn([
                'rules' => [
                    [
                        'condition' => 'all',
                        'shipping_date' => $shippingDate,
                        'discount' => 10,
                        'discount_type' => 'percentage',
                    ],
                ],
            ]);

        $orderItem->expects($this->exactly(3))
            ->method('add_meta_data')
            ->withConsecutive(
                ['_pre_order', true],
                ['_pre_order_shipping_date', $shippingDate],
                ['_pre_order_discount', [
                    'amount' => 10,
                    'type' => 'percentage',
                ]]
            );

        $this->service->addOrderItemMeta($orderItem, $product);
    }

    public function testLogAnalyticsEvent(): void
    {
        $productId = 123;
        $eventType = 'pre_order_placed';
        $metadata = ['key' => 'value'];

        $this->analyticsLogger->expects($this->once())
            ->method('logEvent')
            ->with([
                'event_type' => $eventType,
                'source_product_id' => $productId,
                'customer_id' => 0,
                'module_id' => 'pre-orders',
                'meta_data' => $metadata,
            ]);

        $this->service->logAnalyticsEvent($eventType, $productId, $metadata);
    }
}
