<?php
/**
 * FAQ Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/faq
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\FAQ;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_TwigService;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_FAQModule
 *
 * Handles FAQ functionality for products and categories
 */
class MC_FAQModule extends MC_AbstractModule {
    /**
     * Error handling codes
     */
    private const ERROR_INITIALIZATION = 'initialization_error';
    private const ERROR_REGISTRATION = 'registration_error';
    private const ERROR_RENDER = 'render_error';

    /**
     * The Twig service instance.
     *
     * @var MC_TwigService
     */
    private MC_TwigService $twig;

    /**
     * Initialize the module.
     *
     * @param MC_Plugin $plugin The plugin instance.
     */
    public function __construct(MC_Plugin $plugin) {
        try {
            parent::__construct($plugin);
            $this->twig = new MC_TwigService(plugin_dir_path(MC_PLUGIN_FILE) . 'templates');
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Error initializing FAQ module: %s', $e->getMessage()),
                [
                    'error_code' => self::ERROR_INITIALIZATION,
                    'exception' => $e
                ]
            );
            throw $e; // Re-throw to prevent partial initialization
        }
    }

    /**
     * Initialize the module.
     */
    public function init(): void {
        $this->register_hooks();
    }

    /**
     * Get module identifier
     *
     * @return string
     */
    public function get_module_id(): string {
        return 'faq';
    }

    /**
     * Get default settings
     *
     * @return array
     */
    protected function get_default_settings(): array {
        return [
            'faqs_per_page' => 10,
            'enable_product_faqs' => true,
            'enable_category_faqs' => true,
            'show_faq_tab' => true
        ];
    }

	/**
	 * Register module hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Post type and taxonomy
		add_action( 'init', array( $this, 'register_faq_post_type' ) );
		add_action( 'init', array( $this, 'register_faq_taxonomy' ) );

		// Product integration
		add_action( 'woocommerce_product_tabs', array( $this, 'add_faq_product_tab' ) );
		add_action( 'woocommerce_product_data_tabs', array( $this, 'add_faq_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_faq_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_faq_product_data' ) );

		// Category integration
		add_action( 'product_cat_add_form_fields', array( $this, 'add_category_faq_field' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_faq_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_category_faq' ) );
		add_action( 'edited_product_cat', array( $this, 'save_category_faq' ) );

		// AJAX handlers
		add_action( 'wp_ajax_mc_load_faqs', array( $this, 'handle_load_faqs' ) );
		add_action( 'wp_ajax_nopriv_mc_load_faqs', array( $this, 'handle_load_faqs' ) );
		add_action( 'wp_ajax_mc_save_faq', array( $this, 'handle_save_faq' ) );

		// Admin
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_faq_menu' ) );
			add_action( 'admin_init', array( $this, 'register_faq_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}

		// Frontend
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		}
	}

	/**
	 * Register FAQ post type.
	 *
	 * @return void
	 */
	public function register_faq_post_type(): void {
		try {
			$labels = array(
				'name' => 'FAQs',
				'singular_name' => 'FAQ',
				'menu_name' => 'FAQs',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New FAQ',
				'edit_item' => 'Edit FAQ',
				'new_item' => 'New FAQ',
				'view_item' => 'View FAQ',
				'search_items' => 'Search FAQs',
				'not_found' => 'No FAQs found',
				'not_found_in_trash' => 'No FAQs found in trash',
			);

			$args = array(
				'labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => false,
				'supports' => array( 'title', 'editor' ),
				'has_archive' => false,
				'rewrite' => array( 'slug' => 'faq' ),
			);

			register_post_type( 'mc_faq', $args );
			$this->logger->info(
				'FAQ post type registered successfully',
				[
					'post_type' => 'mc_faq',
					'args' => $args
				]
			);
		} catch (\Throwable $e) {
			$this->logger->error(
				sprintf('Error registering FAQ post type: %s', $e->getMessage()),
				[
					'error_code' => self::ERROR_REGISTRATION,
					'post_type' => 'mc_faq',
					'exception' => $e
				]
			);
			throw $e;
		}
	}

	/**
	 * Register FAQ taxonomy.
	 *
	 * @return void
	 */
	public function register_faq_taxonomy(): void {
		try {
			$labels = array(
				'name' => 'FAQ Categories',
				'singular_name' => 'FAQ Category',
				'menu_name' => 'FAQ Categories',
				'search_items' => 'Search FAQ Categories',
				'all_items' => 'All FAQ Categories',
				'parent_item' => 'Parent FAQ Category',
				'parent_item_colon' => 'Parent FAQ Category:',
				'edit_item' => 'Edit FAQ Category',
				'update_item' => 'Update FAQ Category',
				'add_new_item' => 'Add New FAQ Category',
				'new_item_name' => 'New FAQ Category Name',
			);

			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'faq-category' ),
			);

			register_taxonomy( 'mc_faq_category', array( 'mc_faq' ), $args );
			$this->logger->info(sprintf('FAQ taxonomy registered (taxonomy: %s)', 'mc_faq_category'));
		} catch (\Throwable $e) {
			$this->logger->error(sprintf('Error registering FAQ taxonomy: %s', $e->getMessage()));
		}
	}

	/**
	 * Add FAQ tab to product page.
	 *
	 * @param array $tabs Product tabs.
	 * @return array
	 */
	public function add_faq_product_tab( array $tabs ): array {
		try {
			global $product;
			if ( ! $product ) {
				return $tabs;
			}

			$faqs = $this->get_product_faqs( $product->get_id() );
			if ( ! empty( $faqs ) ) {
				$tabs['faq'] = array(
					'title' => 'FAQ',
					'priority' => 50,
					'callback' => array( $this, 'render_product_faq_tab' ),
				);
			}

			return $tabs;
		} catch (\Throwable $e) {
			$this->logger->error(sprintf('Error adding FAQ product tab: %s', $e->getMessage()));
			return $tabs;
		}
	}

	/**
	 * Render FAQ tab content.
	 *
	 * @return void
	 */
	public function render_product_faq_tab(): void {
		try {
			global $product;
			if ( ! $product ) {
				return;
			}

			$faqs = $this->get_product_faqs( $product->get_id() );
			if ( empty( $faqs ) ) {
				return;
			}

			echo $this->twig->render( 'faq/display.twig', [
				'faqs' => $faqs,
				'product_id' => $product->get_id(),
				'product' => $product
			] );
		} catch (\Throwable $e) {
			$this->logger->error(
				sprintf('Error rendering FAQ tab: %s', $e->getMessage()),
				['exception' => $e]
			);
		}
	}

	/**
	 * Get FAQs for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_product_faqs( int $product_id ): array {
		try {
			$faqs = get_post_meta( $product_id, '_mc_product_faqs', true );
			if ( ! is_array( $faqs ) ) {
				$faqs = array();
			}

			// Get category FAQs
			$terms = get_the_terms( $product_id, 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$category_faqs = get_term_meta( $term->term_id, '_mc_category_faqs', true );
					if ( is_array( $category_faqs ) ) {
						$faqs = array_merge( $faqs, $category_faqs );
					}
				}
			}

			return array_unique( $faqs, SORT_REGULAR );
		} catch (\Throwable $e) {
			$this->logger->error(
				sprintf( 'Error getting product FAQs: %s', $e->getMessage() ),
				[
					'product_id' => $product_id,
					'exception' => $e
				]
			);
			return array();
		}
	}

	/**
	 * Add FAQ management menu.
	 *
	 * @return void
	 */
	public function add_faq_menu(): void {
		add_submenu_page(
			'mesmeric-commerce',
			'FAQs',
			'FAQs',
			'manage_woocommerce',
			'mesmeric-commerce-faqs',
			array( $this, 'render_faq_page' )
		);
	}

	/**
	 * Register FAQ settings.
	 *
	 * @return void
	 */
	public function register_faq_settings(): void {
		register_setting(
			'mc_faq_settings',
			'mc_faq_per_page',
			array(
				'type' => 'number',
				'default' => 10,
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'mesmeric-commerce_page_mesmeric-commerce-faqs' ) {
			return;
		}

		wp_enqueue_style(
			'mc-faq-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/faq-admin.css',
			array(),
			MC_VERSION
		);

		wp_enqueue_script(
			'mc-faq-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/faq-admin.js',
			array( 'jquery' ),
			MC_VERSION,
			true
		);

		wp_localize_script(
			'mc-faq-admin',
			'mcFaqData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'mc-faq-admin' ),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'mc-faq-frontend',
			plugin_dir_url( __FILE__ ) . 'assets/css/faq-frontend.css',
			array(),
			MC_VERSION
		);

		wp_enqueue_script(
			'mc-faq-frontend',
			plugin_dir_url( __FILE__ ) . 'assets/js/faq-frontend.js',
			array( 'jquery' ),
			MC_VERSION,
			true
		);

		wp_localize_script(
			'mc-faq-frontend',
			'mcFaqData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'mc-faq-frontend' ),
			)
		);
	}

	/**
	 * Render FAQ management page.
	 *
	 * @return void
	 */
	public function render_faq_page(): void {
		echo $this->twig->render( 'faq/page.twig', [
			'faqs' => get_posts( [
				'post_type' => 'mc_faq',
				'posts_per_page' => -1,
				'orderby' => 'menu_order',
				'order' => 'ASC'
			] )
		] );
	}

    /**
     * Handle AJAX load FAQs request
     *
     * @return void
     * @throws \Exception
     */
    public function handle_load_faqs(): void {
        try {
            check_ajax_referer('mc-faq', 'nonce');

            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            if (!$product_id) {
                throw new \InvalidArgumentException('Invalid product ID');
            }

            $faqs = $this->get_product_faqs($product_id);

            wp_send_json_success(['faqs' => $faqs]);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Error loading FAQs: %s', $e->getMessage()),
                [
                    'error_code' => self::ERROR_RENDER,
                    'product_id' => $product_id ?? null,
                    'exception' => $e
                ]
            );
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX save FAQ request
     *
     * @return void
     * @throws \Exception
     */
    public function handle_save_faq(): void {
        try {
            check_ajax_referer('mc-faq-admin', 'nonce');

            if (!current_user_can('manage_woocommerce')) {
                throw new \Exception('Permission denied');
            }

            // Validate and sanitize input
            $faq_data = $this->validate_faq_data($_POST);

            // Save FAQ
            $faq_id = wp_insert_post($faq_data);
            if (is_wp_error($faq_id)) {
                throw new \Exception($faq_id->get_error_message());
            }

            wp_send_json_success(['faq_id' => $faq_id]);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Error saving FAQ: %s', $e->getMessage()),
                [
                    'error_code' => self::ERROR_RENDER,
                    'data' => $_POST,
                    'exception' => $e
                ]
            );
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Validate FAQ form data
     *
     * @param array $data Raw form data
     * @return array Sanitized FAQ data
     * @throws \InvalidArgumentException
     */
    private function validate_faq_data(array $data): array {
        $sanitized = [];

        // Required fields
        if (empty($data['question'])) {
            throw new \InvalidArgumentException('Question is required');
        }

        $sanitized['post_title'] = sanitize_text_field($data['question']);
        $sanitized['post_content'] = wp_kses_post($data['answer'] ?? '');
        $sanitized['post_type'] = 'mc_faq';
        $sanitized['post_status'] = 'publish';

        return $sanitized;
    }
}
