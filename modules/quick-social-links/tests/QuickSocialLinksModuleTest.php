<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\QuickSocialLinks;

use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Modules\QuickSocialLinks\QuickSocialLinksModule;
use PHPUnit\Framework\TestCase;

class QuickSocialLinksModuleTest extends TestCase
{
    private QuickSocialLinksModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);

        $this->module = new QuickSocialLinksModule(
            $this->optionsManager,
            $this->templateRenderer
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('quick-social-links', $this->module->getModuleId());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testGetDefaultSettings(): void
    {
        $expected = [
            'layout' => 'pos-bottom',
            'visibility' => 'visibility-all',
            'icon_color' => '#212121',
            'bg_color' => '#ffffff',
            'border_radius' => 15,
            'condition_rules' => [
                [
                    'layout' => 'display',
                    'condition' => 'all',
                    'type' => 'include',
                ],
            ],
            'links' => [
                [
                    'layout' => 'social',
                    'icon' => 'facebook',
                    'url' => 'https://www.facebook.com',
                ],
                [
                    'layout' => 'social',
                    'icon' => 'instagram',
                    'url' => 'https://www.instagram.com',
                ],
                [
                    'layout' => 'social',
                    'icon' => 'twitter',
                    'url' => 'https://www.twitter.com',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->module->getDefaultSettings());
    }

    public function testGetFilteredLinks(): void
    {
        $links = [
            [
                'layout' => 'social',
                'icon' => 'facebook',
                'url' => 'https://facebook.com',
            ],
            [
                'layout' => 'custom',
                'icon' => 'path/to/icon.png',
                'url' => 'https://example.com',
            ],
        ];

        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('getFilteredLinks');
        $method->setAccessible(true);

        $result = $method->invoke($this->module, $links);

        $this->assertCount(2, $result);
        $this->assertEquals('facebook', $result[0]['icon']);
        $this->assertEquals('https://facebook.com', $result[0]['url']);
        $this->assertEquals('social', $result[0]['type']);
        $this->assertEquals('path/to/icon.png', $result[1]['icon']);
        $this->assertEquals('https://example.com', $result[1]['url']);
        $this->assertEquals('custom', $result[1]['type']);
    }

    public function testGetContainerClasses(): void
    {
        $settings = [
            'layout' => 'pos-left',
            'visibility' => 'visibility-desktop',
        ];

        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('getContainerClasses');
        $method->setAccessible(true);

        $result = $method->invoke($this->module, $settings);

        $this->assertStringContainsString('mesmeric-quick-social-links', $result);
        $this->assertStringContainsString('pos-left', $result);
        $this->assertStringContainsString('visibility-desktop', $result);
    }

    public function testShouldDisplayLinks(): void
    {
        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('shouldDisplayLinks');
        $method->setAccessible(true);

        // Test with no rules
        $this->optionsManager->method('getModuleSettings')
            ->willReturn(['condition_rules' => []]);
        $this->assertTrue($method->invoke($this->module));

        // Test with include rule
        $this->optionsManager->method('getModuleSettings')
            ->willReturn([
                'condition_rules' => [
                    [
                        'type' => 'include',
                        'condition' => 'all',
                    ],
                ],
            ]);
        $this->assertTrue($method->invoke($this->module));

        // Test with exclude rule
        $this->optionsManager->method('getModuleSettings')
            ->willReturn([
                'condition_rules' => [
                    [
                        'type' => 'exclude',
                        'condition' => 'all',
                    ],
                ],
            ]);
        $this->assertFalse($method->invoke($this->module));
    }

    public function testInjectCustomCss(): void
    {
        $settings = [
            'border_radius' => 20,
            'icon_color' => '#000000',
            'bg_color' => '#ffffff',
        ];

        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $result = $this->module->injectCustomCss('');

        $this->assertStringContainsString('--mesmeric-border-radius: 20px', $result);
        $this->assertStringContainsString('--mesmeric-icon-color: #000000', $result);
        $this->assertStringContainsString('--mesmeric-bg-color: #ffffff', $result);
    }
}
