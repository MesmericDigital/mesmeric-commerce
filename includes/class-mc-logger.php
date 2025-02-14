<?php
/**
 * Mesmeric Commerce Logger
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes
 */

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class MC_Logger
 *
 * Handles error logging for Mesmeric Commerce plugin
 */
class MC_Logger {
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
     * Initialize the logger
     */
    public function __construct() {
        $this->mc_log_dir = plugin_dir_path(dirname(__FILE__)) . 'logs';
        $this->plugins_log_dir = WP_CONTENT_DIR . '/plugins/logs';

        $this->init_log_directories();
    }

    /**
     * Initialize log directories
     *
     * @return void
     */
    private function init_log_directories(): void {
        // Create Mesmeric Commerce logs directory if it doesn't exist
        if (!file_exists($this->mc_log_dir)) {
            wp_mkdir_p($this->mc_log_dir);
            $this->protect_directory($this->mc_log_dir);
        }

        // Create plugins logs directory if it doesn't exist
        if (!file_exists($this->plugins_log_dir)) {
            wp_mkdir_p($this->plugins_log_dir);
            $this->protect_directory($this->plugins_log_dir);
        }
    }

    /**
     * Protect log directory with .htaccess
     *
     * @param string $directory Directory path.
     * @return void
     */
    private function protect_directory(string $directory): void {
        $htaccess_file = $directory . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order Deny,Allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        $index_file = $directory . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Log an error message
     *
     * @param string  $message Error message.
     * @param string  $type    Error type (error, warning, info).
     * @param boolean $global  Whether to log in plugins directory as well.
     * @return void
     */
    public function log_error(string $message, string $type = 'error', bool $global = false): void {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($type), $message);

        // Log to Mesmeric Commerce log file
        $mc_log_file = $this->mc_log_dir . '/mesmeric-commerce-' . current_time('Y-m-d') . '.log';
        error_log($log_message, 3, $mc_log_file);

        // Log to plugins directory if global is true
        if ($global) {
            $plugins_log_file = $this->plugins_log_dir . '/plugins-' . current_time('Y-m-d') . '.log';
            error_log($log_message, 3, $plugins_log_file);
        }
    }

    /**
     * Get log file content
     *
     * @param string  $date   Date in Y-m-d format.
     * @param boolean $global Whether to get plugins log instead of Mesmeric Commerce log.
     * @return string
     */
    public function get_log_content(string $date, bool $global = false): string {
        $log_file = $global
            ? $this->plugins_log_dir . '/plugins-' . $date . '.log'
            : $this->mc_log_dir . '/mesmeric-commerce-' . $date . '.log';

        if (file_exists($log_file)) {
            return file_get_contents($log_file) ?: '';
        }

        return '';
    }
}
