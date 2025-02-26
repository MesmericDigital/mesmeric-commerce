<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\StockScarcity;

use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Modules\StockScarcity\StockScarcityModule;
use PHPUnit\Framework\TestCase;
use WC_Product;

class StockScarcityModuleTest extends TestCase
{
    private StockScarcityModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->translator = $this->createMock(Translator::class);

        $this->module = new StockScarcityModule(
            $this->optionsManager,
            $this->templateRenderer,
            $this->translator
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('stock-scarcity', $this->module->getModuleId());
    }

    public function testGetModuleName(): void
    {
        $this->assertEquals('Stock Scarcity', $this->module->getModuleName());
    }

    public function testGetModuleSection(): void
    {
        $this->assertEquals('convert-more', $this->module->getModuleSection());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testGetDefaultSettings(): void
    {
        $settings = $this->module->getDefaultSettings();
        
        $this->assertArrayHasKey('min_inventory', $settings);
        $this->assertArrayHasKey('display_pages', $settings);
        $this->assertArrayHasKey('low_inventory_text', $settings);
        $this->assertArrayHasKey('low_inventory_text_plural', $settings);
        $this->assertArrayHasKey('low_inventory_text_simple', $settings);
        
        $this->assertEquals(50, $settings['min_inventory']);
        $this->assertEquals(['product'], $settings['display_pages']);
    }

    public function testRenderStockScarcityWithInsufficientStock(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(123);
        $product->method('managing_stock')->willReturn(true);
        $product->method('get_stock_quantity')->willReturn(10);
        $product->method('is_type')->with('simple')->willReturn(true);

        $settings = [
            'min_inventory' => 50,
            'low_inventory_text_simple' => 'Hurry, low stock.',
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->once())
            ->method('render')
            ->with(
                '@stock-scarcity/stock-indicator.twig',
                $this->callback(function ($args) {
                    return isset($args['stock'])
                        && isset($args['percentage'])
                        && isset($args['isSimple'])
                        && $args['stock'] === 10
                        && $args['percentage'] === 20.0
                        && $args['isSimple'] === true;
                })
            );

        $GLOBALS['product'] = $product;
        $this->module->renderStockScarcity();
        unset($GLOBALS['product']);
    }

    public function testRenderStockScarcityWithSufficientStock(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('managing_stock')->willReturn(true);
        $product->method('get_stock_quantity')->willReturn(100);

        $settings = [
            'min_inventory' => 50,
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->never())
            ->method('render');

        $GLOBALS['product'] = $product;
        $this->module->renderStockScarcity();
        unset($GLOBALS['product']);
    }

    public function testRenderStockScarcityWithoutStockManagement(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('managing_stock')->willReturn(false);

        $this->templateRenderer
            ->expects($this->never())
            ->method('render');

        $GLOBALS['product'] = $product;
        $this->module->renderStockScarcity();
        unset($GLOBALS['product']);
    }
}
