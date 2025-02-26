<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Campaign;

use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_EmailService
 * 
 * Handles email template management and sending
 */
class MC_EmailService {
    protected MC_Plugin $plugin;
    protected const TEMPLATES_TABLE = 'mc_email_templates';

    public function __construct(MC_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Send campaign email to subscriber
     */
    public function send_campaign_email(array $campaign, array $subscriber): bool {
        try {
            $template = $this->get_template($campaign['template_id']);
            if (!$template) {
                throw new \RuntimeException('Email template not found');
            }

            $content = $this->prepare_email_content($campaign, $template, $subscriber);
            $headers = $this->get_email_headers();

            $sent = wp_mail(
                $subscriber['email'],
                $campaign['subject'],
                $content,
                $headers
            );

            if (!$sent) {
                throw new \RuntimeException('Failed to send email');
            }

            return true;
        } catch (\Throwable $e) {
            $this->plugin->get_logger()->log_error(
                sprintf(
                    'Failed to send campaign email to %s: %s',
                    $subscriber['email'],
                    $e->getMessage()
                ),
                'error'
            );
            return false;
        }
    }

    /**
     * Get all email templates
     */
    public function get_templates(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE;
        $query = "SELECT * FROM $table_name ORDER BY name ASC";

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get specific template
     */
    public function get_template(int $template_id): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE;
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $template_id
        );

        return $wpdb->get_row($query, ARRAY_A) ?: null;
    }

    /**
     * Save email template
     */
    public function save_template(array $data): int {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE;
        $now = current_time('mysql');

        $template_data = [
            'name' => $data['name'],
            'content' => $data['content'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (isset($data['id'])) {
            $wpdb->update(
                $table_name,
                array_merge($template_data, ['updated_at' => $now]),
                ['id' => $data['id']],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            return (int) $data['id'];
        } else {
            $wpdb->insert(
                $table_name,
                $template_data,
                ['%s', '%s', '%s', '%s']
            );
            return (int) $wpdb->insert_id;
        }
    }

    /**
     * Prepare email content with template and variables
     */
    protected function prepare_email_content(array $campaign, array $template, array $subscriber): string {
        $content = $template['content'];

        // Replace template variables
        $variables = [
            '{{content}}' => $campaign['content'],
            '{{first_name}}' => $subscriber['first_name'] ?? '',
            '{{last_name}}' => $subscriber['last_name'] ?? '',
            '{{email}}' => $subscriber['email'],
            '{{unsubscribe_link}}' => $this->get_unsubscribe_link($subscriber),
            '{{site_name}}' => get_bloginfo('name'),
            '{{site_url}}' => get_site_url(),
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $content
        );
    }

    /**
     * Get email headers
     */
    protected function get_email_headers(): array {
        $from_name = get_option('mc_email_from_name', get_bloginfo('name'));
        $from_email = get_option('mc_email_from_address', get_option('admin_email'));

        return [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email),
        ];
    }

    /**
     * Get unsubscribe link for subscriber
     */
    protected function get_unsubscribe_link(array $subscriber): string {
        $token = wp_create_nonce('mc_unsubscribe_' . $subscriber['email']);
        
        return add_query_arg([
            'mc_action' => 'unsubscribe',
            'email' => urlencode($subscriber['email']),
            'token' => $token,
        ], home_url());
    }

    /**
     * Create required database tables
     */
    public static function create_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            content longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add default template if none exists
        if (!$wpdb->get_var("SELECT COUNT(*) FROM $table_name")) {
            self::create_default_template();
        }
    }

    /**
     * Create default email template
     */
    protected static function create_default_template(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE;
        $now = current_time('mysql');

        $default_template = [
            'name' => 'Default Template',
            'content' => self::get_default_template_content(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $wpdb->insert(
            $table_name,
            $default_template,
            ['%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get default template content
     */
    protected static function get_default_template_content(): string {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center; background-color: #ffffff;">
                <img src="{{site_url}}/wp-content/uploads/logo.png" alt="{{site_name}}" style="max-width: 200px;">
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 20px; background-color: #ffffff;">
                {{content}}
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; text-align: center; background-color: #f4f4f4; color: #666666; font-size: 12px;">
                <p>You received this email because you are subscribed to {{site_name}} updates.</p>
                <p><a href="{{unsubscribe_link}}" style="color: #666666;">Unsubscribe</a></p>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
