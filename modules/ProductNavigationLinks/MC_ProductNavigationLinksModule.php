<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\ProductNavigationLinks;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Plugin;
use MesmericCommerce\Includes\MC_TwigService;

/**
 * Product Navigation Links Module
 *
 * Adds previous/next navigation links to product pages
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/ProductNavigationLinks
 * @since      1.0.0
 */
class MC_ProductNavigationLinksModule extends MC_AbstractModule {
    /**
     * Module settings
     *
     * @var array
     */
    private array $settings;

    /**
     * Twig service
     *
     * @var MC_TwigService
     */
    private MC_TwigService $twig;

    /**
     * Initialize the class and set its properties.
     *
     * @param MC_Plugin $plugin Main plugin instance.
     */
    public function __construct(MC_Plugin $plugin) {
        parent::__construct($plugin);

        $this->settings = $this->get_settings();
        $this->twig = new MC_TwigService();
        $this->twig->add_path(dirname(__FILE__) . '/views');
    }

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_module_id(): string {
        return 'product-navigation-links';
    }

    /**
     * Get default module settings
     *
     * @return array
     */
    protected function get_default_settings(): array {
        return [
            'placement' => 'bottom',
            'text' => 'titles',
            'text_color' => '#212121',
            'text_hover_color' => '#757575',
            'text_decoration' => 'none',
            'justify_content' => 'space-between',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'product_navigation_mode' => 'default',
            'priority' => 30,
            'use_shortcode' => false,
        ];
    }

    /**
     * Initialize the module
     *
     * @return void
     */
    public function init(): void {
        // Only load on single product pages or if using shortcode
        if (!is_product() && !$this->get_option('use_shortcode', false)) {
            return;
        }

        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Add navigation links based on placement setting
        $placement = $this->get_option('placement', 'bottom');
        $priority = (int) $this->get_option('priority', 30);

        switch ($placement) {
            case 'top':
                add_action('woocommerce_before_single_product', [$this, 'render_navigation_links'], $priority);
                break;
            case 'bottom':
                add_action('woocommerce_after_single_product', [$this, 'render_navigation_links'], $priority);
                break;
            case 'bottom-product-summary':
                add_action('woocommerce_single_product_summary', [$this, 'render_navigation_links'], 50);
                break;
        }

        // Register shortcode if enabled
        if ($this->get_option('use_shortcode', false)) {
            add_shortcode('mc_product_navigation_links', [$this, 'shortcode_callback']);
        }

        // Add custom CSS
        add_action('wp_head', [$this, 'add_custom_css']);
    }

    /**
     * Register module assets
     *
     * @return void
     */
    public function register_assets(): void {
        wp_enqueue_style(
            'mc-product-navigation-links',
            plugin_dir_url(__FILE__) . 'assets/css/product-navigation-links.css',
            [],
            MC_VERSION
        );
    }

    /**
     * Get module settings with defaults
     *
     * @return array
     */
    public function get_settings(): array {
        $defaults = $this->get_default_settings();
        $settings = [];

        foreach ($defaults as $key => $default) {
            $settings[$key] = $this->get_option($key, $default);
        }

        return $settings;
    }

    /**
     * Render navigation links
     *
     * @return void
     */
    public function render_navigation_links(): void {
        // Get previous and next product links
        $links = $this->get_product_links();

        if (empty($links['prev']) && empty($links['next'])) {
            return;
        }

        // Prepare template data
        $template_data = [
            'text_type' => $this->settings['text'],
            'prev_product' => !empty($links['prev']) ? $links['prev'] : null,
            'next_product' => !empty($links['next']) ? $links['next'] : null,
        ];

        // Render template
        echo $this->twig->render('navigation-links.twig', $template_data);
    }

    /**
     * Get previous and next product links
     *
     * @return array
     */
    private function get_product_links(): array {
        global $post;

        $links = [
            'prev' => [],
            'next' => [],
        ];

        if (!$post || !is_product()) {
            return $links;
        }

        $navigation_mode = $this->settings['product_navigation_mode'];

        // Get adjacent products based on navigation mode
        switch ($navigation_mode) {
            case 'category':
                $links = $this->get_category_adjacent_products($post);
                break;
            case 'tag':
                $links = $this->get_tag_adjacent_products($post);
                break;
            default:
                $links = $this->get_default_adjacent_products($post);
                break;
        }

        return $links;
    }

    /**
     * Get adjacent products in default mode
     *
     * @param \WP_Post $post Current post
     * @return array
     */
    private function get_default_adjacent_products(\WP_Post $post): array {
        $links = [
            'prev' => [],
            'next' => [],
        ];

        $prev_post = get_adjacent_post(false, '', true, 'product');
        $next_post = get_adjacent_post(false, '', false, 'product');

        if ($prev_post && $prev_post instanceof \WP_Post) {
            $links['prev'] = [
                'title' => get_the_title($prev_post->ID),
                'url' => get_permalink($prev_post->ID),
            ];
        }

        if ($next_post && $next_post instanceof \WP_Post) {
            $links['next'] = [
                'title' => get_the_title($next_post->ID),
                'url' => get_permalink($next_post->ID),
            ];
        }

        return $links;
    }

    /**
     * Get adjacent products in same category
     *
     * @param \WP_Post $post Current post
     * @return array
     */
    private function get_category_adjacent_products(\WP_Post $post): array {
        $links = [
            'prev' => [],
            'next' => [],
        ];

        $prev_post = get_adjacent_post(true, '', true, 'product_cat');
        $next_post = get_adjacent_post(true, '', false, 'product_cat');

        if ($prev_post && $prev_post instanceof \WP_Post) {
            $links['prev'] = [
                'title' => get_the_title($prev_post->ID),
                'url' => get_permalink($prev_post->ID),
            ];
        }

        if ($next_post && $next_post instanceof \WP_Post) {
            $links['next'] = [
                'title' => get_the_title($next_post->ID),
                'url' => get_permalink($next_post->ID),
            ];
        }

        return $links;
    }

    /**
     * Get adjacent products with same tag
     *
     * @param \WP_Post $post Current post
     * @return array
     */
    private function get_tag_adjacent_products(\WP_Post $post): array {
        $links = [
            'prev' => [],
            'next' => [],
        ];

        $prev_post = get_adjacent_post(true, '', true, 'product_tag');
        $next_post = get_adjacent_post(true, '', false, 'product_tag');

        if ($prev_post && $prev_post instanceof \WP_Post) {
            $links['prev'] = [
                'title' => get_the_title($prev_post->ID),
                'url' => get_permalink($prev_post->ID),
            ];
        }

        if ($next_post && $next_post instanceof \WP_Post) {
            $links['next'] = [
                'title' => get_the_title($next_post->ID),
                'url' => get_permalink($next_post->ID),
            ];
        }

        return $links;
    }

    /**
     * Shortcode callback
     *
     * @return string
     */
    public function shortcode_callback(): string {
        ob_start();
        $this->render_navigation_links();
        return ob_get_clean();
    }

    /**
     * Add custom CSS
     *
     * @return void
     */
    public function add_custom_css(): void {
        if (!is_product() && !$this->get_option('use_shortcode', false)) {
            return;
        }

        $css = '
            .mc-product-navigation a {
                color: ' . esc_attr($this->settings['text_color']) . ';
                text-decoration: ' . esc_attr($this->settings['text_decoration']) . ';
            }
            .mc-product-navigation a:hover {
                color: ' . esc_attr($this->settings['text_hover_color']) . ';
            }
            .mc-product-navigation {
                justify-content: ' . esc_attr($this->settings['justify_content']) . ';
                margin-top: ' . esc_attr($this->settings['margin_top']) . 'px;
                margin-bottom: ' . esc_attr($this->settings['margin_bottom']) . 'px;
            }
        ';

        echo '<style type="text/css">' . $css . '</style>';
    }
}
