<?php

declare(strict_types=1);

namespace MesmericCommerce\Modules\RealTimeSearch;

use MesmericCommerce\Core\Module\AbstractModule;
use MesmericCommerce\Core\Module\ModuleInterface;
use MesmericCommerce\Core\Options\OptionsManager;
use MesmericCommerce\Core\Template\TemplateRenderer;
use MesmericCommerce\Core\Translation\Translator;
use WP_Query;

/**
 * Real-Time Search Module
 *
 * Provides instant search functionality for WooCommerce products.
 */
class RealTimeSearchModule extends AbstractModule implements ModuleInterface
{
    public const MODULE_ID = 'real-time-search';
    private const MODULE_SECTION = 'improve-experience';
    private bool $isModulePreview = false;
    private TemplateRenderer $templateRenderer;
    private Translator $translator;

    /**
     * Constructor.
     */
    public function __construct(
        OptionsManager $optionsManager,
        TemplateRenderer $templateRenderer,
        Translator $translator
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->translator = $translator;

        parent::__construct($optionsManager);

        if ($this->isModulePreview()) {
            $this->isModulePreview = true;
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_filter('mesmeric_module_preview', [$this, 'renderAdminPreview'], 10, 2);
        }

        if (!$this->isActive()) {
            return;
        }

        // Frontend hooks
        if (!is_admin() || wp_doing_ajax()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
            add_filter('mesmeric_localize_script', [$this, 'localizeScript']);
            add_action('wp_ajax_real_time_search', [$this, 'handleSearchRequest']);
            add_action('wp_ajax_nopriv_real_time_search', [$this, 'handleSearchRequest']);
            add_shortcode('mesmeric_search', [$this, 'renderSearchForm']);
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-real-time-search-admin',
            $this->getModuleUrl() . '/assets/css/admin.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-real-time-search-admin',
            $this->getModuleUrl() . '/assets/js/admin.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueFrontendAssets(): void
    {
        wp_enqueue_style(
            'mesmeric-real-time-search',
            $this->getModuleUrl() . '/assets/css/real-time-search.css',
            [],
            MESMERIC_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'mesmeric-real-time-search',
            $this->getModuleUrl() . '/assets/js/real-time-search.js',
            ['alpine'],
            MESMERIC_COMMERCE_VERSION,
            true
        );
    }

    /**
     * Localize script with module settings.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function localizeScript(array $settings): array
    {
        $moduleSettings = $this->getSettings();

        $settings['realTimeSearch'] = [
            'enabled' => true,
            'resultsPerSearch' => $moduleSettings['results_per_search'] ?? 15,
            'orderBy' => $moduleSettings['results_order_by'] ?? 'title',
            'order' => $moduleSettings['results_order'] ?? 'asc',
            'displayCategories' => (bool)($moduleSettings['display_categories'] ?? false),
            'enableSearchBySku' => (bool)($moduleSettings['enable_search_by_sku'] ?? false),
            'nonce' => wp_create_nonce('real_time_search'),
            'i18n' => [
                'noResults' => __('No products found', 'mesmeric-commerce'),
                'viewAll' => __('View all results', 'mesmeric-commerce'),
            ],
        ];

        return $settings;
    }

    /**
     * Handle AJAX search request.
     */
    public function handleSearchRequest(): void
    {
        check_ajax_referer('real_time_search', 'nonce');

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (empty($query)) {
            wp_send_json_error(['message' => 'Empty search query']);
            return;
        }

        $settings = $this->getSettings();
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $settings['results_per_search'] ?? 15,
            'orderby' => $settings['results_order_by'] ?? 'title',
            'order' => $settings['results_order'] ?? 'asc',
            's' => $query,
        ];

        // Add SKU search if enabled
        if (!empty($settings['enable_search_by_sku'])) {
            add_filter('posts_search', [$this, 'searchBySku'], 10, 2);
        }

        $searchQuery = new WP_Query($args);

        if (!empty($settings['enable_search_by_sku'])) {
            remove_filter('posts_search', [$this, 'searchBySku']);
        }

        $results = [];
        foreach ($searchQuery->posts as $post) {
            $product = wc_get_product($post);
            if (!$product) {
                continue;
            }

            $results[] = [
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'url' => get_permalink($product->get_id()),
                'image' => $this->getProductImage($product),
                'price' => $product->get_price_html(),
                'sku' => $product->get_sku(),
                'categories' => $settings['display_categories'] ? $this->getProductCategories($product) : [],
            ];
        }

        wp_send_json_success([
            'results' => $results,
            'total' => $searchQuery->found_posts,
            'hasMore' => $searchQuery->found_posts > count($results),
        ]);
    }

    /**
     * Modify search query to include SKU.
     *
     * @param string $search
     * @param WP_Query $query
     * @return string
     */
    public function searchBySku(string $search, WP_Query $query): string
    {
        global $wpdb;

        if (empty($search) || !is_search() || !isset($query->query_vars['s'])) {
            return $search;
        }

        $terms = explode(' ', $query->query_vars['s']);
        $skuQuery = [];

        foreach ($terms as $term) {
            $skuQuery[] = $wpdb->prepare(
                "mpm.meta_key = '_sku' AND mpm.meta_value LIKE %s",
                '%' . $wpdb->esc_like($term) . '%'
            );
        }

        $search = preg_replace(
            "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "({$wpdb->posts}.post_title LIKE $1) OR EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} mpm
                WHERE mpm.post_id = {$wpdb->posts}.ID AND (" . implode(' OR ', $skuQuery) . ")
            )",
            $search
        );

        return $search;
    }

    /**
     * Get product image data.
     *
     * @param \WC_Product $product
     * @return array{url: string, alt: string}
     */
    private function getProductImage(\WC_Product $product): array
    {
        $imageId = $product->get_image_id();
        if (!$imageId) {
            return [
                'url' => wc_placeholder_img_src(),
                'alt' => __('Product image placeholder', 'mesmeric-commerce'),
            ];
        }

        return [
            'url' => wp_get_attachment_image_url($imageId, 'woocommerce_thumbnail'),
            'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * Get product categories.
     *
     * @param \WC_Product $product
     * @return array<array{id: int, name: string, url: string}>
     */
    private function getProductCategories(\WC_Product $product): array
    {
        $categories = [];
        $terms = get_the_terms($product->get_id(), 'product_cat');

        if (!is_array($terms)) {
            return $categories;
        }

        foreach ($terms as $term) {
            $categories[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'url' => get_term_link($term),
            ];
        }

        return $categories;
    }

    /**
     * Render search form shortcode.
     *
     * @param array<string, mixed> $atts
     * @return string
     */
    public function renderSearchForm(array $atts = []): string
    {
        $settings = $this->getSettings();
        $atts = shortcode_atts([
            'placeholder' => __('Search products...', 'mesmeric-commerce'),
            'submit_text' => __('Search', 'mesmeric-commerce'),
        ], $atts);

        return $this->templateRenderer->render('@real-time-search/search-form.twig', [
            'settings' => $settings,
            'attributes' => $atts,
        ]);
    }

    /**
     * Get module ID.
     */
    public function getModuleId(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Get module name.
     */
    public function getModuleName(): string
    {
        return __('Real-Time Search', 'mesmeric-commerce');
    }

    /**
     * Get module description.
     */
    public function getModuleDescription(): string
    {
        return __('Provides instant search functionality for WooCommerce products.', 'mesmeric-commerce');
    }

    /**
     * Get module section.
     */
    public function getModuleSection(): string
    {
        return self::MODULE_SECTION;
    }

    /**
     * Get default settings.
     */
    public function getDefaultSettings(): array
    {
        return [
            'results_per_search' => 15,
            'results_description' => 'product-short-description',
            'results_description_length' => 10,
            'results_order_by' => 'title',
            'results_order' => 'asc',
            'results_box_width' => 500,
            'display_categories' => false,
            'enable_search_by_sku' => false,
        ];
    }

    /**
     * Check if WooCommerce is required.
     */
    public function requiresWooCommerce(): bool
    {
        return true;
    }
}
