<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\AddedToCartPopup;

use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Modules\AddedToCartPopup\AddedToCartPopupModule;
use PHPUnit\Framework\TestCase;

class AddedToCartPopupModuleTest extends TestCase
{
    private AddedToCartPopupModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->translator = $this->createMock(Translator::class);

        $this->module = new AddedToCartPopupModule(
            $this->optionsManager,
            $this->templateRenderer,
            $this->translator
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('added-to-cart-popup', $this->module->getModuleId());
    }

    public function testGetModuleName(): void
    {
        $this->assertEquals('Added to Cart Popup', $this->module->getModuleName());
    }

    public function testGetModuleDescription(): void
    {
        $this->assertEquals(
            'Display a popup when products are added to cart.',
            $this->module->getModuleDescription()
        );
    }

    public function testGetDefaultSettings(): void
    {
        $expected = [
            'layout' => 'layout-1',
            'popup_message' => 'Item has been added to your cart',
            'view_cart_button_label' => 'View Cart',
            'view_continue_shopping_button_label' => 'Continue Shopping',
        ];

        $this->assertEquals($expected, $this->module->getDefaultSettings());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testRenderPopup(): void
    {
        $settings = [
            'layout' => 'layout-1',
            'popup_message' => 'Test Message',
        ];

        $this->optionsManager
            ->method('get')
            ->willReturn($settings);

        $this->templateRenderer
            ->expects($this->once())
            ->method('render')
            ->with(
                '@added-to-cart-popup/layouts/layout-1.twig',
                $this->callback(function ($args) use ($settings) {
                    return isset($args['settings']) && $args['settings'] === $settings;
                })
            );

        $this->module->renderPopup();
    }
}
