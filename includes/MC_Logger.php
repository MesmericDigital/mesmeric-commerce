<?php
/**
 * Mesmeric Commerce Logger
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes
 */

declare(strict_types=1);

namespace MesmericCommerce\Includes;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use WC_Order;
use WC_Product;

/**
 * Class MC_Logger
 *
 * Handles error logging for Mesmeric Commerce plugin
 */
class MC_Logger {
    private static ?self $instance = null;
    /**
     * Log directory path for Mesmeric Commerce
     *
     * @var string
     */
    private string $mc_log_dir;

    /**
     * Log directory path for general plugins
     *
     * @var string
     */
    private string $plugins_log_dir;

    /**
     * Monolog instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Get logger instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $this->mc_log_dir      = plugin_dir_path(__DIR__) . 'logs';
        $this->plugins_log_dir = WP_CONTENT_DIR . '/plugins/logs';

        $this->init_log_directories();
        $this->setup_monolog();
    }

    /**
     * Initialize log directories
     *
     * @return void
     */
    private function init_log_directories(): void {
        // Create Mesmeric Commerce logs directory if it doesn't exist
        if (! file_exists($this->mc_log_dir)) {
            wp_mkdir_p($this->mc_log_dir);
            $this->protect_directory($this->mc_log_dir);
        }

        // Create plugins logs directory if it doesn't exist
        if (! file_exists($this->plugins_log_dir)) {
            wp_mkdir_p($this->plugins_log_dir);
            $this->protect_directory($this->plugins_log_dir);
        }
    }

    /**
     * Setup Monolog instance
     *
     * @return void
     */
    private function setup_monolog(): void {
        // Create a Monolog instance
        $this->logger = new Logger( 'mesmeric-commerce' );

        // Create a formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );

        // Add handlers for Mesmeric Commerce logs
        $mc_handler = new RotatingFileHandler(
            $this->mc_log_dir . '/mesmeric-commerce.log',
            7,
            Logger::DEBUG
        );
        $mc_handler->setFormatter( $formatter );
        $this->logger->pushHandler( $mc_handler );

        // Add handlers for global plugin logs if WP_DEBUG is enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $plugins_handler = new StreamHandler(
                $this->plugins_log_dir . '/plugins-' . date( 'Y-m-d' ) . '.log',
                Logger::DEBUG
            );
            $plugins_handler->setFormatter( $formatter );
            $this->logger->pushHandler( $plugins_handler );
        }
    }

    /**
     * Protect log directory with .htaccess
     *
     * @param string $directory Directory path.
     * @return void
     */
    private function protect_directory( string $directory ): void {
        $htaccess_file = $directory . '/.htaccess';
        if (! file_exists($htaccess_file)) {
            $htaccess_content = "Order Deny,Allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        $index_file = $directory . '/index.php';
        if (! file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Log a system event
     *
     * @param string $message Event message.
     * @return void
     */
    public function log_system_event(string $message): void {
        $this->logger->info($message, ['context' => 'system_event']);
    }

    /**
     * Log an error message
     *
     * @param string  $message Error message.
     * @param string  $type    Error type (error, warning, info).
     * @param boolean $global  Whether to log in plugins directory as well.
     * @return void
     */
    public function log_error( string $message, string $type = 'error', bool $global = false ): void {
        $level = match ( strtolower( $type ) ) {
            'error' => Logger::ERROR,
            'warning' => Logger::WARNING,
            'notice' => Logger::NOTICE,
            'info' => Logger::INFO,
            default => Logger::DEBUG,
        };

        $this->logger->log( $level, $message );
    }

    /**
     * Log an error message
     *
     * @param string $message Error message.
     * @return void
     */
    public function error(string $message): void {
        $this->log_error($message, 'error');
    }

    /**
     * Log an info message
     *
     * @param string $message Info message.
     * @return void
     */
    public function info(string $message): void {
        $this->log_error($message, 'info');
    }

    /**
     * Log a warning message
     *
     * @param string $message Warning message.
     * @return void
     */
    public function warning(string $message): void {
        $this->log_error($message, 'warning');
    }

    /**
     * Log a notice message
     *
     * @param string $message Notice message.
     * @return void
     */
    public function notice(string $message): void {
        $this->log_error($message, 'notice');
    }

    /**
     * Log a debug message
     *
     * @param string $message Debug message.
     * @return void
     */
    public function debug(string $message): void {
        $this->log_error($message, 'debug');
    }

    /**
     * Get log file content
     *
     * @param string  $date   Date in Y-m-d format.
     * @param boolean $global Whether to get plugins log instead of Mesmeric Commerce log.
     * @return string
     */
    public function get_log_content( string $date, bool $global = false ): string {
        $log_file = $global
            ? $this->plugins_log_dir . '/plugins-' . $date . '.log'
            : $this->mc_log_dir . '/mesmeric-commerce-' . $date . '.log';

        if (file_exists($log_file)) {
            return file_get_contents($log_file) ?: '';
        }

        return '';
    }

    /**
     * Log WooCommerce order events with detailed information
     *
     * @param WC_Order $order          Order object
     * @param string   $event_type     Event type (created, updated, status_change, etc.)
     * @param array    $additional_data Additional event data
     */
    public function log_order_event(WC_Order $order, string $event_type, array $additional_data = []): void {
        $log_data = [
            'event_type' => $event_type,
            'order_id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method(),
            'shipping_method' => $order->get_shipping_method(),
            'customer_id' => $order->get_customer_id(),
            'timestamp' => current_time('mysql'),
            'additional_data' => $additional_data,
        ];

        $this->logger->info(
            'Order Event: ' . $event_type,
            ['context' => 'woocommerce_order'] + $log_data
        );
    }

    /**
     * Log WooCommerce inventory changes
     *
     * @param WC_Product $product    Product object
     * @param int        $old_stock  Old stock quantity
     * @param int        $new_stock  New stock quantity
     * @param string     $reason     Reason for stock change
     */
    public function log_inventory_change(WC_Product $product, int $old_stock, int $new_stock, string $reason): void {
        $log_data = [
            'event_type' => 'inventory_change',
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'old_stock' => $old_stock,
            'new_stock' => $new_stock,
            'change' => $new_stock - $old_stock,
            'reason' => $reason,
            'timestamp' => current_time('mysql'),
        ];

        $this->logger->info(
            sprintf(
                'Inventory Change: %s (ID: %d) - Stock changed from %d to %d (%s)',
                $product->get_name(),
                $product->get_id(),
                $old_stock,
                $new_stock,
                $reason
            ),
            ['context' => 'woocommerce_inventory'] + $log_data
        );
    }

    /**
     * Log WooCommerce shipping events
     *
     * @param WC_Order $order           Order object
     * @param string   $tracking_number Tracking number
     * @param string   $carrier        Shipping carrier
     * @param string   $status         Shipping status
     */
    public function log_shipping_event(WC_Order $order, string $tracking_number, string $carrier, string $status): void {
        $log_data = [
            'event_type' => 'shipping',
            'order_id' => $order->get_id(),
            'tracking_number' => $tracking_number,
            'carrier' => $carrier,
            'status' => $status,
            'timestamp' => current_time('mysql'),
        ];

        $this->logger->info(
            sprintf(
                'Shipping Update: Order #%d - %s (%s) - Status: %s',
                $order->get_id(),
                $tracking_number,
                $carrier,
                $status
            ),
            ['context' => 'woocommerce_shipping'] + $log_data
        );
    }

    /**
     * Log user activity in WooCommerce context
     *
     * @param int    $user_id       User ID
     * @param string $activity_type Activity type
     * @param array  $details      Additional details
     */
    public function log_user_activity(int $user_id, string $activity_type, array $details = []): void {
        $log_data = [
            'event_type' => 'user_activity',
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'details' => $details,
            'timestamp' => current_time('mysql'),
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        $this->logger->info(
            sprintf(
                'User Activity: User #%d - %s',
                $user_id,
                $activity_type
            ),
            ['context' => 'woocommerce_user'] + $log_data
        );
    }
}
