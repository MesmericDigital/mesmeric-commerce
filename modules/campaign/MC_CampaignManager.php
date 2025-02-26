<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Campaign;

use MesmericCommerce\Includes\MC_Plugin;
use WC_Order;

/**
 * Class MC_CampaignManager
 * 
 * Handles campaign creation, management, and tracking
 */
class MC_CampaignManager {
    protected MC_Plugin $plugin;
    protected MC_EmailService $email_service;
    protected const CAMPAIGNS_TABLE = 'mc_campaigns';
    protected const CAMPAIGN_STATS_TABLE = 'mc_campaign_stats';

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
        $this->email_service = new MC_EmailService($plugin);
    }

    /**
     * Handle campaign save request
     */
    public function handle_save_campaign(): void {
        check_ajax_referer('mc_campaign_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $campaign_data = $this->sanitize_campaign_data($_POST);
        $campaign_id = $this->save_campaign($campaign_data);

        if ($campaign_id) {
            wp_send_json_success(['campaign_id' => $campaign_id]);
        } else {
            wp_send_json_error('Failed to save campaign');
        }
    }

    /**
     * Handle campaign send request
     */
    public function handle_send_campaign(): void {
        check_ajax_referer('mc_campaign_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $campaign_id = (int) $_POST['campaign_id'];
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            wp_send_json_error('Campaign not found');
        }

        $result = $this->send_campaign($campaign);
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to send campaign');
        }
    }

    /**
     * Handle stats request
     */
    public function handle_get_stats(): void {
        check_ajax_referer('mc_campaign_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $campaign_id = (int) $_POST['campaign_id'];
        $stats = $this->get_campaign_stats($campaign_id);

        wp_send_json_success($stats);
    }

    /**
     * Get all campaigns
     */
    public function get_campaigns(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $query = "SELECT * FROM $table_name ORDER BY created_at DESC";

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get a specific campaign
     */
    public function get_campaign(int $campaign_id): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $campaign_id
        );

        return $wpdb->get_row($query, ARRAY_A) ?: null;
    }

    /**
     * Save campaign data
     */
    protected function save_campaign(array $data): int {
        global $wpdb;

        $table_name = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $now = current_time('mysql');

        $campaign_data = [
            'name' => $data['name'],
            'subject' => $data['subject'],
            'template_id' => $data['template_id'],
            'content' => $data['content'],
            'list_id' => $data['list_id'],
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (isset($data['id'])) {
            $wpdb->update(
                $table_name,
                array_merge($campaign_data, ['updated_at' => $now]),
                ['id' => $data['id']],
                ['%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s'],
                ['%d']
            );
            return (int) $data['id'];
        } else {
            $wpdb->insert(
                $table_name,
                $campaign_data,
                ['%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s']
            );
            return (int) $wpdb->insert_id;
        }
    }

    /**
     * Send campaign to subscribers
     */
    protected function send_campaign(array $campaign): bool {
        try {
            $subscribers = $this->get_campaign_subscribers($campaign['list_id']);
            foreach ($subscribers as $subscriber) {
                $this->email_service->send_campaign_email($campaign, $subscriber);
                $this->log_email_sent($campaign['id'], $subscriber['id']);
            }

            $this->update_campaign_status($campaign['id'], 'sent');
            return true;
        } catch (\Throwable $e) {
            $this->plugin->get_logger()->log_error(
                sprintf('Failed to send campaign: %s', $e->getMessage()),
                'error'
            );
            return false;
        }
    }

    /**
     * Get campaign statistics
     */
    public function get_campaign_stats(int $campaign_id): array {
        global $wpdb;

        $stats_table = $wpdb->prefix . self::CAMPAIGN_STATS_TABLE;
        
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as total_clicked
            FROM $stats_table 
            WHERE campaign_id = %d",
            $campaign_id
        );

        return $wpdb->get_row($query, ARRAY_A) ?: [
            'total_sent' => 0,
            'total_opened' => 0,
            'total_clicked' => 0,
        ];
    }

    /**
     * Get overall campaign statistics
     */
    public function get_overall_stats(): array {
        global $wpdb;

        $campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $stats_table = $wpdb->prefix . self::CAMPAIGN_STATS_TABLE;

        $query = "SELECT 
            COUNT(DISTINCT c.id) as total_campaigns,
            COUNT(DISTINCT s.id) as total_emails_sent,
            AVG(CASE WHEN s.opened_at IS NOT NULL THEN 1 ELSE 0 END) * 100 as open_rate,
            AVG(CASE WHEN s.clicked_at IS NOT NULL THEN 1 ELSE 0 END) * 100 as click_rate
        FROM $campaigns_table c
        LEFT JOIN $stats_table s ON c.id = s.campaign_id";

        return $wpdb->get_row($query, ARRAY_A) ?: [
            'total_campaigns' => 0,
            'total_emails_sent' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
        ];
    }

    /**
     * Trigger post-purchase campaign
     */
    public function trigger_post_purchase_campaign(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $campaign = $this->get_post_purchase_campaign();
        if (!$campaign) {
            return;
        }

        $subscriber_data = [
            'email' => $order->get_billing_email(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
        ];

        $this->email_service->send_campaign_email($campaign, $subscriber_data);
    }

    /**
     * Get subscribers for a campaign
     */
    protected function get_campaign_subscribers(int $list_id): array {
        global $wpdb;

        $subscribers_table = $wpdb->prefix . 'mc_subscribers';
        $query = $wpdb->prepare(
            "SELECT * FROM $subscribers_table WHERE list_id = %d AND status = 'subscribed'",
            $list_id
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Log email sent
     */
    protected function log_email_sent(int $campaign_id, int $subscriber_id): void {
        global $wpdb;

        $stats_table = $wpdb->prefix . self::CAMPAIGN_STATS_TABLE;
        $wpdb->insert(
            $stats_table,
            [
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id,
                'sent_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );
    }

    /**
     * Update campaign status
     */
    protected function update_campaign_status(int $campaign_id, string $status): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $wpdb->update(
            $table_name,
            [
                'status' => $status,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $campaign_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Get post-purchase campaign template
     */
    protected function get_post_purchase_campaign(): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE type = %s AND status = %s LIMIT 1",
            'post_purchase',
            'active'
        );

        return $wpdb->get_row($query, ARRAY_A) ?: null;
    }

    /**
     * Sanitize campaign data
     */
    protected function sanitize_campaign_data(array $data): array {
        return [
            'id' => isset($data['id']) ? (int) $data['id'] : null,
            'name' => sanitize_text_field($data['name']),
            'subject' => sanitize_text_field($data['subject']),
            'template_id' => (int) $data['template_id'],
            'content' => wp_kses_post($data['content']),
            'list_id' => (int) $data['list_id'],
        ];
    }

    /**
     * Create required database tables
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Campaigns table
        $campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
        $campaigns_sql = "CREATE TABLE IF NOT EXISTS $campaigns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            template_id bigint(20) NOT NULL,
            content longtext NOT NULL,
            list_id bigint(20) NOT NULL,
            type varchar(50) DEFAULT 'regular',
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY type (type)
        ) $charset_collate;";

        // Campaign stats table
        $stats_table = $wpdb->prefix . self::CAMPAIGN_STATS_TABLE;
        $stats_sql = "CREATE TABLE IF NOT EXISTS $stats_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            subscriber_id bigint(20) NOT NULL,
            sent_at datetime NOT NULL,
            opened_at datetime DEFAULT NULL,
            clicked_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY subscriber_id (subscriber_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($campaigns_sql);
        dbDelta($stats_sql);
    }
}
