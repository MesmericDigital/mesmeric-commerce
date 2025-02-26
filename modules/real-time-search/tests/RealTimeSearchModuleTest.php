<?php

declare(strict_types=1);

namespace MesmericCommerce\Tests\Modules\RealTimeSearch;

use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use MesmericCommerce\Modules\RealTimeSearch\RealTimeSearchModule;
use PHPUnit\Framework\TestCase;
use WC_Product;
use WP_Post;
use WP_Query;

class RealTimeSearchModuleTest extends TestCase
{
    private RealTimeSearchModule $module;
    private OptionsManager $optionsManager;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsManager = $this->createMock(OptionsManager::class);
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->translator = $this->createMock(Translator::class);

        $this->module = new RealTimeSearchModule(
            $this->optionsManager,
            $this->templateRenderer,
            $this->translator
        );
    }

    public function testGetModuleId(): void
    {
        $this->assertEquals('real-time-search', $this->module->getModuleId());
    }

    public function testRequiresWooCommerce(): void
    {
        $this->assertTrue($this->module->requiresWooCommerce());
    }

    public function testGetDefaultSettings(): void
    {
        $expected = [
            'results_per_search' => 15,
            'results_description' => 'product-short-description',
            'results_description_length' => 10,
            'results_order_by' => 'title',
            'results_order' => 'asc',
            'results_box_width' => 500,
            'display_categories' => false,
            'enable_search_by_sku' => false,
        ];

        $this->assertEquals($expected, $this->module->getDefaultSettings());
    }

    public function testLocalizeScript(): void
    {
        $settings = [
            'results_per_search' => 10,
            'results_order_by' => 'date',
            'results_order' => 'desc',
            'display_categories' => true,
            'enable_search_by_sku' => true,
        ];

        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $result = $this->module->localizeScript([]);

        $this->assertArrayHasKey('realTimeSearch', $result);
        $this->assertEquals(true, $result['realTimeSearch']['enabled']);
        $this->assertEquals(10, $result['realTimeSearch']['resultsPerSearch']);
        $this->assertEquals('date', $result['realTimeSearch']['orderBy']);
        $this->assertEquals('desc', $result['realTimeSearch']['order']);
        $this->assertEquals(true, $result['realTimeSearch']['displayCategories']);
        $this->assertEquals(true, $result['realTimeSearch']['enableSearchBySku']);
    }

    public function testSearchBySku(): void
    {
        global $wpdb;
        $wpdb = (object)['posts' => 'wp_posts', 'postmeta' => 'wp_postmeta', 'esc_like' => function($input) {
            return addcslashes($input, '%_[]');
        }];

        $query = $this->createMock(WP_Query::class);
        $query->query_vars['s'] = 'test123';

        $result = $this->module->searchBySku(
            "({$wpdb->posts}.post_title LIKE '%test123%')",
            $query
        );

        $expected = "({$wpdb->posts}.post_title LIKE '%test123%') OR EXISTS (
                SELECT 1 FROM wp_postmeta mpm
                WHERE mpm.post_id = wp_posts.ID AND (mpm.meta_key = '_sku' AND mpm.meta_value LIKE '%test123%')
            )";

        $this->assertEquals(
            preg_replace('/\s+/', ' ', trim($expected)),
            preg_replace('/\s+/', ' ', trim($result))
        );
    }

    public function testRenderSearchForm(): void
    {
        $settings = ['results_box_width' => 500];
        $this->optionsManager->method('getModuleSettings')
            ->willReturn($settings);

        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with(
                '@real-time-search/search-form.twig',
                [
                    'settings' => $settings,
                    'attributes' => [
                        'placeholder' => 'Search products...',
                        'submit_text' => 'Search',
                    ],
                ]
            )
            ->willReturn('<div>Search Form</div>');

        $result = $this->module->renderSearchForm();
        $this->assertEquals('<div>Search Form</div>', $result);
    }

    public function testGetProductImage(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_image_id')->willReturn(123);

        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('getProductImage');
        $method->setAccessible(true);

        $result = $method->invoke($this->module, $product);

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('alt', $result);
    }

    public function testGetProductCategories(): void
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(1);

        $term = (object)[
            'term_id' => 1,
            'name' => 'Test Category',
            'slug' => 'test-category',
        ];

        $reflection = new \ReflectionClass($this->module);
        $method = $reflection->getMethod('getProductCategories');
        $method->setAccessible(true);

        // Mock get_the_terms function
        global $wp_filter;
        $wp_filter = [];
        add_filter('get_the_terms', function() use ($term) {
            return [$term];
        });

        $result = $method->invoke($this->module, $product);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($term->term_id, $result[0]['id']);
        $this->assertEquals($term->name, $result[0]['name']);
    }
}
