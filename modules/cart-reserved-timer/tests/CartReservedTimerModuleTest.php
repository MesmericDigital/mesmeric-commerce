<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\CartReservedTimer;

use MesmericCommerce\Core\Assets\AssetsManager;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Modules\CartReservedTimer\CartReservedTimerModule;
use PHPUnit\Framework\TestCase;
use WC_Cart;
use WC_Session;

class CartReservedTimerModuleTest extends TestCase
{
    private CartReservedTimerModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;
    private AssetsManager $assetsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->translator = $this->createMock(Translator::class);
        $this->assetsManager = $this->createMock(AssetsManager::class);

        $this->module = new CartReservedTimerModule(
            $this->optionsManager,
            $this->templateRenderer,
            $this->translator,
            $this->assetsManager
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('cart-reserved-timer', $this->module->getModuleId());
    }

    public function testGetModuleName(): void
    {
        $this->assertEquals('Cart Reserved Timer', $this->module->getModuleName());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testGetDefaultSettings(): void
    {
        $settings = $this->module->getDefaultSettings();

        $this->assertArrayHasKey('duration', $settings);
        $this->assertArrayHasKey('reserved_message', $settings);
        $this->assertArrayHasKey('timer_message_minutes', $settings);
        $this->assertArrayHasKey('timer_message_seconds', $settings);
        $this->assertArrayHasKey('time_expires', $settings);
        $this->assertArrayHasKey('icon', $settings);
        $this->assertArrayHasKey('background_color', $settings);

        $this->assertEquals(10, $settings['duration']);
        $this->assertEquals('clear-cart', $settings['time_expires']);
        $this->assertEquals('fire', $settings['icon']);
    }

    public function testInitTranslations(): void
    {
        $settings = [
            'reserved_message' => 'Test message',
            'timer_message_minutes' => 'Test minutes message',
            'timer_message_seconds' => 'Test seconds message',
        ];

        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $this->translator->expects($this->exactly(3))
            ->method('registerString')
            ->withConsecutive(
                [$settings['reserved_message'], 'Cart Reserved Timer: Cart reserved message'],
                [$settings['timer_message_minutes'], 'Cart Reserved Timer: Timer message for > 1 min'],
                [$settings['timer_message_seconds'], 'Cart Reserved Timer: Timer message for < 1 min']
            );

        $this->module->initTranslations();
    }

    public function testEnqueueFrontendAssets(): void
    {
        $settings = [
            'duration' => 10,
            'time_expires' => 'clear-cart',
        ];

        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $this->assetsManager->expects($this->once())
            ->method('enqueueStyle')
            ->with(
                'mesmeric-cart-reserved-timer',
                'modules/cart-reserved-timer/css/cart-reserved-timer.css'
            );

        $this->assetsManager->expects($this->once())
            ->method('enqueueScript')
            ->with(
                'mesmeric-cart-reserved-timer',
                'modules/cart-reserved-timer/js/cart-reserved-timer.js',
                ['alpine'],
                $this->callback(function ($config) use ($settings) {
                    return $config['duration'] === $settings['duration'] * 60
                        && $config['timeExpires'] === $settings['time_expires']
                        && isset($config['ajaxUrl'])
                        && isset($config['nonce']);
                })
            );

        $this->module->enqueueFrontendAssets();
    }

    public function testHandleClearCartAjax(): void
    {
        // Mock WC_Cart
        $cart = $this->createMock(WC_Cart::class);
        $cart->expects($this->once())
            ->method('empty_cart');

        // Mock WC() function
        global $woocommerce;
        $woocommerce = (object) ['cart' => $cart];

        // Mock nonce verification
        $this->assertTrue(
            apply_filters('wp_verify_nonce', true, 'cart_reserved_timer', 'nonce')
        );

        // Capture JSON response
        ob_start();
        $this->module->handleClearCart();
        $response = json_decode(ob_get_clean(), true);

        $this->assertTrue($response['success']);
    }

    public function testRenderTimer(): void
    {
        $settings = [
            'icon' => 'fire',
            'other_setting' => 'value',
        ];

        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with(
                '@cart-reserved-timer/timer.twig',
                [
                    'settings' => $settings,
                    'icon' => $this->stringContains('fire.svg'),
                ]
            );

        $this->module->renderTimer();
    }
}
