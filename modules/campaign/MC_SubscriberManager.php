<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Campaign;

use MesmericCommerce\Includes\MC_Plugin;
use WC_Order;

/**
 * Class MC_SubscriberManager
 * 
 * Handles subscriber management and list operations
 */
class MC_SubscriberManager {
    protected MC_Plugin $plugin;
    protected const SUBSCRIBERS_TABLE = 'mc_subscribers';
    protected const LISTS_TABLE = 'mc_subscriber_lists';

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Handle subscribe request
     */
    public function handle_subscribe(): void {
        check_ajax_referer('mc_subscribe_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        $list_id = (int) $_POST['list_id'];
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');

        $subscriber_id = $this->add_subscriber([
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'list_id' => $list_id,
        ]);

        if ($subscriber_id) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to add subscriber');
        }
    }

    /**
     * Handle checkout subscription
     */
    public function handle_checkout_subscription(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $subscribe = filter_var(
            $order->get_meta('_mc_subscribe_to_newsletter'),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$subscribe) {
            return;
        }

        $list_id = (int) get_option('mc_default_list_id', 0);
        if (!$list_id) {
            return;
        }

        $this->add_subscriber([
            'email' => $order->get_billing_email(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'list_id' => $list_id,
        ]);
    }

    /**
     * Get all subscribers
     */
    public function get_subscribers(array $args = []): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::SUBSCRIBERS_TABLE;
        $where = ['1=1'];
        $values = [];

        if (isset($args['list_id'])) {
            $where[] = 'list_id = %d';
            $values[] = $args['list_id'];
        }

        if (isset($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC",
            $values
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get subscriber lists
     */
    public function get_lists(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::LISTS_TABLE;
        $query = "SELECT * FROM $table_name ORDER BY name ASC";

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get subscriber statistics
     */
    public function get_stats(): array {
        global $wpdb;

        $subscribers_table = $wpdb->prefix . self::SUBSCRIBERS_TABLE;
        
        $query = "SELECT 
            COUNT(*) as total_subscribers,
            SUM(CASE WHEN status = 'subscribed' THEN 1 ELSE 0 END) as active_subscribers,
            COUNT(DISTINCT list_id) as total_lists,
            DATE_FORMAT(MAX(created_at), '%Y-%m-%d') as latest_signup
        FROM $subscribers_table";

        return $wpdb->get_row($query, ARRAY_A) ?: [
            'total_subscribers' => 0,
            'active_subscribers' => 0,
            'total_lists' => 0,
            'latest_signup' => null,
        ];
    }

    /**
     * Add or update subscriber
     */
    protected function add_subscriber(array $data): int {
        global $wpdb;

        $subscribers_table = $wpdb->prefix . self::SUBSCRIBERS_TABLE;
        $now = current_time('mysql');

        // Check if subscriber already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $subscribers_table WHERE email = %s AND list_id = %d",
                $data['email'],
                $data['list_id']
            ),
            ARRAY_A
        );

        if ($existing) {
            // Update existing subscriber
            $wpdb->update(
                $subscribers_table,
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'status' => 'subscribed',
                    'updated_at' => $now,
                ],
                [
                    'id' => $existing['id'],
                ],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            return (int) $existing['id'];
        }

        // Add new subscriber
        $wpdb->insert(
            $subscribers_table,
            [
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'list_id' => $data['list_id'],
                'status' => 'subscribed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Create required database tables
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Subscribers table
        $subscribers_table = $wpdb->prefix . self::SUBSCRIBERS_TABLE;
        $subscribers_sql = "CREATE TABLE IF NOT EXISTS $subscribers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            first_name varchar(100) DEFAULT '',
            last_name varchar(100) DEFAULT '',
            list_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'subscribed',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_list (email, list_id),
            KEY status (status)
        ) $charset_collate;";

        // Lists table
        $lists_table = $wpdb->prefix . self::LISTS_TABLE;
        $lists_sql = "CREATE TABLE IF NOT EXISTS $lists_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($subscribers_sql);
        dbDelta($lists_sql);

        // Add default list if none exists
        if (!$wpdb->get_var("SELECT COUNT(*) FROM $lists_table")) {
            self::create_default_list();
        }
    }

    /**
     * Create default subscriber list
     */
    protected static function create_default_list(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::LISTS_TABLE;
        $now = current_time('mysql');

        $default_list = [
            'name' => 'Newsletter Subscribers',
            'description' => 'Default list for newsletter subscribers',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $wpdb->insert(
            $table_name,
            $default_list,
            ['%s', '%s', '%s', '%s']
        );

        update_option('mc_default_list_id', $wpdb->insert_id);
    }
}
