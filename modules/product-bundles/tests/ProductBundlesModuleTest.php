<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\ProductBundles;

use MesmericCommerce\Modules\ProductBundles\ProductBundlesModule;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ProductBundlesModuleTest extends TestCase {
    private ProductBundlesModule $module;
    private Environment $twig;

    protected function setUp(): void {
        parent::setUp();
        
        $this->twig = $this->createMock(Environment::class);
        $this->module = new ProductBundlesModule($this->twig);
    }

    public function testGetDefaultSettings(): void {
        $settings = $this->module->getDefaultSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('price_range', $settings);
        $this->assertArrayHasKey('bundled_thumb', $settings);
        $this->assertArrayHasKey('bundled_description', $settings);
        $this->assertArrayHasKey('bundled_qty', $settings);
        $this->assertArrayHasKey('bundled_link_single', $settings);
        $this->assertArrayHasKey('bundled_price', $settings);
        $this->assertArrayHasKey('bundled_price_from', $settings);
        $this->assertArrayHasKey('placement', $settings);
    }

    public function testGetAnalyticsMetrics(): void {
        $metrics = $this->module->getAnalyticsMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_bundles', $metrics);
        $this->assertArrayHasKey('bundle_sales', $metrics);
        $this->assertArrayHasKey('bundle_revenue', $metrics);
        $this->assertArrayHasKey('most_popular_bundles', $metrics);
        $this->assertArrayHasKey('average_bundle_size', $metrics);
    }

    public function testGetModuleId(): void {
        $this->assertEquals('product-bundles', $this->module->getModuleId());
    }
}
