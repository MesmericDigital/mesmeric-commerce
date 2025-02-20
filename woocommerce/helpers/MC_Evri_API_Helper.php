<?php
declare(strict_types=1);

namespace MesmericCommerce\WooCommerce\Helpers;

/**
 * Evri API Helper
 *
 * Handles API interactions with Evri shipping service.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/woocommerce/helpers
 * @since      1.0.0
 */
class MC_Evri_API_Helper {
    /**
     * API key
     *
     * @since 1.0.0
     * @var string
     */
    private string $api_key;

    /**
     * Test mode
     *
     * @since 1.0.0
     * @var bool
     */
    private bool $test_mode;

    /**
     * API base URL
     *
     * @since 1.0.0
     * @var string
     */
    private string $api_url;

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     * @param string $api_key   API key
     * @param bool   $test_mode Whether to use test mode
     */
    public function __construct(string $api_key, bool $test_mode = false) {
        $this->api_key = $api_key;
        $this->test_mode = $test_mode;
        $this->api_url = $test_mode
            ? 'https://api-sandbox.evri.com/v1'
            : 'https://api.evri.com/v1';
    }

    /**
     * Get shipping rates.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package Package information
     * @return array<string, mixed>|WP_Error
     */
    public function get_shipping_rates(array $package) {
        $endpoint = '/shipping/rates';

        $body = [
            'weight' => $this->calculate_package_weight($package),
            'dimensions' => $this->calculate_package_dimensions($package),
            'destination' => [
                'postcode' => $package['destination']['postcode'],
                'country' => $package['destination']['country'],
            ],
        ];

        return $this->make_request('POST', $endpoint, $body);
    }

    /**
     * Create shipping label.
     *
     * @since 1.0.0
     * @param int                  $order_id Order ID
     * @param array<string, mixed> $package  Package information
     * @return array<string, mixed>|WP_Error
     */
    public function create_shipping_label(int $order_id, array $package) {
        $endpoint = '/shipping/labels';

        $order = wc_get_order($order_id);
        if (!$order) {
            return new \WP_Error('invalid_order', __('Invalid order ID', 'mesmeric-commerce'));
        }

        $body = [
            'order_reference' => $order->get_order_number(),
            'weight' => $this->calculate_package_weight($package),
            'dimensions' => $this->calculate_package_dimensions($package),
            'destination' => [
                'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address1' => $order->get_shipping_address_1(),
                'address2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email(),
            ],
        ];

        return $this->make_request('POST', $endpoint, $body);
    }

    /**
     * Track shipment.
     *
     * @since 1.0.0
     * @param string $tracking_number Tracking number
     * @return array<string, mixed>|WP_Error
     */
    public function track_shipment(string $tracking_number) {
        $endpoint = '/tracking/' . urlencode($tracking_number);
        return $this->make_request('GET', $endpoint);
    }

    /**
     * Make API request.
     *
     * @since 1.0.0
     * @param string               $method   HTTP method
     * @param string               $endpoint API endpoint
     * @param array<string, mixed> $body     Request body
     * @return array<string, mixed>|WP_Error
     */
    private function make_request(string $method, string $endpoint, array $body = []) {
        $url = $this->api_url . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'invalid_response',
                __('Invalid response from Evri API', 'mesmeric-commerce')
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            return new \WP_Error(
                'api_error',
                $data['message'] ?? __('Error from Evri API', 'mesmeric-commerce'),
                ['status' => $status_code]
            );
        }

        return $data;
    }

    /**
     * Calculate package weight.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package Package information
     * @return float
     */
    private function calculate_package_weight(array $package): float {
        $weight = 0;
        foreach ($package['contents'] as $item) {
            if ($item['data']->needs_shipping()) {
                $weight += (float) $item['data']->get_weight() * $item['quantity'];
            }
        }
        return $weight;
    }

    /**
     * Calculate package dimensions.
     *
     * @since 1.0.0
     * @param array<string, mixed> $package Package information
     * @return array<string, float>
     */
    private function calculate_package_dimensions(array $package): array {
        $dimensions = [
            'length' => 0,
            'width' => 0,
            'height' => 0,
        ];

        foreach ($package['contents'] as $item) {
            if (!$item['data']->needs_shipping()) {
                continue;
            }

            $length = (float) $item['data']->get_length();
            $width = (float) $item['data']->get_width();
            $height = (float) $item['data']->get_height();

            // Update maximum dimensions
            $dimensions['length'] = max($dimensions['length'], $length);
            $dimensions['width'] = max($dimensions['width'], $width);
            $dimensions['height'] = max($dimensions['height'], $height);
        }

        return $dimensions;
    }
}
