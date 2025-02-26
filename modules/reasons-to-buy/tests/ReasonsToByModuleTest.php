<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\ReasonsToBuy;

use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Modules\ReasonsToBuy\ReasonsToByModule;
use PHPUnit\Framework\TestCase;
use WC_Product;

class ReasonsToByModuleTest extends TestCase
{
    private ReasonsToByModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->translator = $this->createMock(Translator::class);

        $this->module = new ReasonsToByModule(
            $this->optionsManager,
            $this->templateRenderer,
            $this->translator
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('reasons-to-buy', $this->module->getModuleId());
    }

    public function testGetModuleName(): void
    {
        $this->assertEquals('Reasons to Buy', $this->module->getModuleName());
    }

    public function testGetModuleSection(): void
    {
        $this->assertEquals('build-trust', $this->module->getModuleSection());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testGetDefaultSettings(): void
    {
        $settings = $this->module->getDefaultSettings();
        
        $this->assertArrayHasKey('reasons_to_buy', $settings);
        $this->assertIsArray($settings['reasons_to_buy']);
        $this->assertCount(1, $settings['reasons_to_buy']);
        
        $defaultGroup = $settings['reasons_to_buy'][0];
        $this->assertEquals('active', $defaultGroup['campaign_status']);
        $this->assertEquals('all', $defaultGroup['display_rules']);
        $this->assertCount(3, $defaultGroup['items']);
    }

    public function testGetWrapperClasses(): void
    {
        $this->optionsManager
            ->method('get')
            ->willReturn(['display_icon' => true]);

        $classes = $this->module->getWrapperClasses([]);
        $this->assertContains('show-icon', $classes);
    }

    public function testRenderReasonsList(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(123);

        $settings = [
            'reasons_to_buy' => [
                [
                    'campaign_status' => 'active',
                    'display_rules' => 'all',
                    'title' => 'Test Reasons',
                    'items' => ['Reason 1', 'Reason 2'],
                ],
            ],
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->once())
            ->method('render')
            ->with(
                '@reasons-to-buy/reasons-list.twig',
                $this->callback(function ($args) use ($settings) {
                    return isset($args['reasons'])
                        && isset($args['settings'])
                        && isset($args['product'])
                        && count($args['reasons']) === 1
                        && $args['reasons'][0]['title'] === 'Test Reasons';
                })
            );

        $GLOBALS['product'] = $product;
        $this->module->renderReasonsList();
        unset($GLOBALS['product']);
    }

    public function testProductSpecificRules(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(123);

        $settings = [
            'reasons_to_buy' => [
                [
                    'campaign_status' => 'active',
                    'display_rules' => 'products',
                    'product_ids' => [123],
                    'title' => 'Test Reasons',
                    'items' => ['Reason 1'],
                ],
            ],
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->once())
            ->method('render');

        $GLOBALS['product'] = $product;
        $this->module->renderReasonsList();
        unset($GLOBALS['product']);
    }

    public function testExcludedProducts(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(123);

        $settings = [
            'reasons_to_buy' => [
                [
                    'campaign_status' => 'active',
                    'display_rules' => 'all',
                    'exclusion_enabled' => true,
                    'excluded_products' => [123],
                    'title' => 'Test Reasons',
                    'items' => ['Reason 1'],
                ],
            ],
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->never())
            ->method('render');

        $GLOBALS['product'] = $product;
        $this->module->renderReasonsList();
        unset($GLOBALS['product']);
    }
}
