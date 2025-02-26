<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\AddToCartText;

use MesmericCommerce\Modules\AddToCartText\AddToCartTextModule;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddToCartTextModuleTest extends TestCase {
    private AddToCartTextModule $module;
    private Environment $twig;

    protected function setUp(): void {
        parent::setUp();
        
        $this->twig = $this->createMock(Environment::class);
        $this->module = new AddToCartTextModule($this->twig);
    }

    public function testGetDefaultSettings(): void {
        $settings = $this->module->getDefaultSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('simple_product_label', $settings);
        $this->assertArrayHasKey('simple_product_shop_label', $settings);
        $this->assertArrayHasKey('variable_product_label', $settings);
        $this->assertArrayHasKey('variable_product_shop_label', $settings);
        $this->assertArrayHasKey('out_of_stock_shop_label', $settings);
    }

    public function testGetModuleId(): void {
        $this->assertEquals('add-to-cart-text', $this->module->getModuleId());
    }
}
