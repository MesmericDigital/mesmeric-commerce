<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Htmx;

/**
 * HTMX Demo REST Controller
 *
 * Handles HTMX demo endpoints
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/Htmx
 * @since      1.0.0
 */
class MC_HtmxDemoController {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register REST routes
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Basic click example
        register_rest_route('mesmeric-commerce/v1', '/htmx/demo/click', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_click'],
            'permission_callback' => '__return_true',
        ]);

        // Form submission example
        register_rest_route('mesmeric-commerce/v1', '/htmx/demo/form', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_form'],
            'permission_callback' => '__return_true',
        ]);

        // Search example
        register_rest_route('mesmeric-commerce/v1', '/htmx/demo/search', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_search'],
            'permission_callback' => '__return_true',
        ]);

        // Infinite scroll example
        register_rest_route('mesmeric-commerce/v1', '/htmx/demo/items', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_items'],
            'permission_callback' => '__return_true',
        ]);

        // Tabs example
        register_rest_route('mesmeric-commerce/v1', '/htmx/demo/tab/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_tab'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);
    }

    /**
     * Handle click example
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function handle_click(\WP_REST_Request $request): \WP_REST_Response {
        // Simulate a delay
        usleep(500000); // 500ms

        $html = '<div class="mc-htmx-success">';
        $html .= '<h4>' . esc_html__('Success!', 'mesmeric-commerce') . '</h4>';
        $html .= '<p>' . esc_html__('This content was loaded via HTMX without a page refresh.', 'mesmeric-commerce') . '</p>';
        $html .= '<p>' . esc_html__('Current time:', 'mesmeric-commerce') . ' ' . esc_html(current_time('mysql')) . '</p>';
        $html .= '</div>';

        return new \WP_REST_Response($html);
    }

    /**
     * Handle form submission example
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function handle_form(\WP_REST_Request $request): \WP_REST_Response {
        // Simulate a delay
        usleep(1000000); // 1s

        // Get form data
        $params = $request->get_params();
        $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';

        // Validate
        if (empty($name) || empty($email)) {
            $html = '<div id="form-result" class="mc-htmx-result mc-htmx-error">';
            $html .= '<h4>' . esc_html__('Error', 'mesmeric-commerce') . '</h4>';
            $html .= '<p>' . esc_html__('Please fill in all required fields.', 'mesmeric-commerce') . '</p>';
            $html .= '</div>';

            return new \WP_REST_Response($html);
        }

        // Success response
        $html = '<div id="form-result" class="mc-htmx-result mc-htmx-success">';
        $html .= '<h4>' . esc_html__('Form Submitted Successfully!', 'mesmeric-commerce') . '</h4>';
        $html .= '<p>' . sprintf(
            esc_html__('Thank you, %s! We\'ve received your submission.', 'mesmeric-commerce'),
            esc_html($name)
        ) . '</p>';
        $html .= '<p>' . esc_html__('Email:', 'mesmeric-commerce') . ' ' . esc_html($email) . '</p>';
        $html .= '</div>';

        return new \WP_REST_Response($html);
    }

    /**
     * Handle search example
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function handle_search(\WP_REST_Request $request): \WP_REST_Response {
        // Simulate a delay
        usleep(300000); // 300ms

        // Get search query
        $params = $request->get_params();
        $query = isset($params['search']) ? sanitize_text_field($params['search']) : '';

        if (empty($query)) {
            $html = '<p>' . esc_html__('Please enter a search term.', 'mesmeric-commerce') . '</p>';
            return new \WP_REST_Response($html);
        }

        // Get products
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 5,
            's' => $query,
        ];

        $products = get_posts($args);

        if (empty($products)) {
            $html = '<p>' . esc_html__('No products found matching your search.', 'mesmeric-commerce') . '</p>';
            return new \WP_REST_Response($html);
        }

        // Build response
        $html = '<div class="mc-search-results">';
        $html .= '<h4>' . esc_html__('Search Results', 'mesmeric-commerce') . '</h4>';
        $html .= '<ul>';

        foreach ($products as $product) {
            $html .= '<li>';
            $html .= '<a href="' . esc_url(get_permalink($product->ID)) . '">';
            $html .= esc_html($product->post_title);
            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return new \WP_REST_Response($html);
    }

    /**
     * Handle infinite scroll example
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function handle_items(\WP_REST_Request $request): \WP_REST_Response {
        // Simulate a delay
        usleep(800000); // 800ms

        // Get page
        $params = $request->get_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;

        // Generate items
        $html = '';
        $start = ($page * 3) + 1;
        $end = $start + 2;

        for ($i = $start; $i <= $end; $i++) {
            $html .= '<div class="mc-item">';
            $html .= '<h4>' . sprintf(esc_html__('Item %d', 'mesmeric-commerce'), $i) . '</h4>';
            $html .= '<p>' . sprintf(esc_html__('This is item number %d in the list.', 'mesmeric-commerce'), $i) . '</p>';
            $html .= '</div>';
        }

        // Add sentinel for next page if not the last page
        if ($page < 3) { // Limit to 3 pages for demo
            $next_page = $page + 1;
            $html .= '<div class="mc-scroll-sentinel" ';
            $html .= 'hx-get="' . esc_url(rest_url('mesmeric-commerce/v1/htmx/demo/items')) . '" ';
            $html .= 'hx-trigger="revealed" ';
            $html .= 'hx-swap="beforeend" ';
            $html .= 'hx-vals=\'{"page": ' . $next_page . '}\'>';
            $html .= '</div>';
        } else {
            $html .= '<div class="mc-end-message">';
            $html .= '<p>' . esc_html__('No more items to load.', 'mesmeric-commerce') . '</p>';
            $html .= '</div>';
        }

        return new \WP_REST_Response($html);
    }

    /**
     * Handle tab example
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public function handle_tab(\WP_REST_Request $request): \WP_REST_Response {
        // Simulate a delay
        usleep(400000); // 400ms

        // Get tab ID
        $tab_id = (int) $request->get_param('id');

        // Generate content based on tab ID
        switch ($tab_id) {
            case 1:
                $html = '<h4>' . esc_html__('Tab 1 Content', 'mesmeric-commerce') . '</h4>';
                $html .= '<p>' . esc_html__('This is the content for Tab 1.', 'mesmeric-commerce') . '</p>';
                $html .= '<p>' . esc_html__('HTMX makes it easy to load content dynamically without writing JavaScript.', 'mesmeric-commerce') . '</p>';
                break;

            case 2:
                $html = '<h4>' . esc_html__('Tab 2 Content', 'mesmeric-commerce') . '</h4>';
                $html .= '<p>' . esc_html__('This is the content for Tab 2.', 'mesmeric-commerce') . '</p>';
                $html .= '<p>' . esc_html__('With HTMX, you can build modern user interfaces with simple HTML attributes.', 'mesmeric-commerce') . '</p>';
                break;

            case 3:
                $html = '<h4>' . esc_html__('Tab 3 Content', 'mesmeric-commerce') . '</h4>';
                $html .= '<p>' . esc_html__('This is the content for Tab 3.', 'mesmeric-commerce') . '</p>';
                $html .= '<p>' . esc_html__('HTMX is perfect for WordPress developers who want to add interactivity without complex JavaScript.', 'mesmeric-commerce') . '</p>';
                break;

            default:
                $html = '<h4>' . esc_html__('Unknown Tab', 'mesmeric-commerce') . '</h4>';
                $html .= '<p>' . esc_html__('The requested tab does not exist.', 'mesmeric-commerce') . '</p>';
        }

        return new \WP_REST_Response($html);
    }
}
