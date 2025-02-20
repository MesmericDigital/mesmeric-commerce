<?php

declare(strict_types=1);

namespace MesmericCommerce\Includes;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller for Mesmeric Commerce Logs
 */
class MC_LogsRestController extends WP_REST_Controller {
    /**
     * Logger instance
     */
    private MC_Logger $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'mesmeric-commerce/v1';
        $this->rest_base = 'logs';
        $this->logger = MC_Logger::get_instance();
    }

    /**
     * Register routes
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_logs'],
                    'permission_callback' => [$this, 'get_logs_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
            ]
        );
    }

    /**
     * Check if user has permissions to get logs
     *
     * @param WP_REST_Request $request The request object
     * @return bool Whether the user has permission
     */
    public function get_logs_permissions_check(WP_REST_Request $request): bool {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Get logs
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
        try {
            $params = $request->get_params();
            $date = sanitize_text_field($params['date'] ?? date('Y-m-d'));
            $context = sanitize_text_field($params['context'] ?? '');

            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return new WP_Error(
                    'invalid_date',
                    __('Invalid date format. Use YYYY-MM-DD', 'mesmeric-commerce'),
                    ['status' => 400]
                );
            }

            // Get logs for the specified date
            $log_content = $this->logger->get_log_content($date);

            if (empty($log_content)) {
                return new WP_REST_Response([
                    'logs' => [],
                    'total' => 0,
                ], 200);
            }

            // Parse log entries
            $logs = array_values(array_filter(array_map(function($line) use ($context) {
                if (empty(trim($line))) {
                    return null;
                }

                $log_data = json_decode($line, true);
                if (!is_array($log_data)) {
                    return null;
                }

                // Filter by context if specified
                if (!empty($context) && ($log_data['context'] ?? '') !== $context) {
                    return null;
                }

                return [
                    'id' => wp_generate_uuid4(),
                    'timestamp' => sanitize_text_field($log_data['data']['timestamp'] ?? ''),
                    'context' => sanitize_text_field($log_data['context'] ?? ''),
                    'message' => wp_kses_post($log_data['data']['message'] ?? ''),
                    'data' => array_map('sanitize_text_field', $log_data['data'] ?? []),
                ];
            }, explode("\n", $log_content))));

            // Sort logs by timestamp descending
            usort($logs, function($a, $b) {
                return strcmp($b['timestamp'], $a['timestamp']);
            });

            return new WP_REST_Response([
                'logs' => $logs,
                'total' => count($logs),
            ], 200);

        } catch (\Throwable $e) {
            return new WP_Error(
                'mesmeric_commerce_logs_error',
                __('Error retrieving logs', 'mesmeric-commerce'),
                [
                    'status' => 500,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Get collection parameters
     *
     * @return array Collection parameters
     */
    public function get_collection_params(): array {
        return [
            'date' => [
                'description' => __('Date to retrieve logs for (YYYY-MM-DD)', 'mesmeric-commerce'),
                'type' => 'string',
                'format' => 'date',
                'default' => date('Y-m-d'),
                'required' => false,
            ],
            'context' => [
                'description' => __('Log context to filter by', 'mesmeric-commerce'),
                'type' => 'string',
                'enum' => [
                    'woocommerce_order',
                    'woocommerce_inventory',
                    'woocommerce_shipping',
                    'woocommerce_user',
                ],
                'required' => false,
            ],
        ];
    }
}
