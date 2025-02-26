<?php

declare(strict_types=1);
/**
 * Error Handler class for Mesmeric Commerce
 *
 * @package MesmericCommerce
 */

namespace MesmericCommerce\Includes;

use const MC_VERSION;

/**
 * Class ErrorHandler
 *
 * Handles error handling for Mesmeric Commerce plugin
 */
class MC_ErrorHandler
{
    /**
     * @var bool
     */
    private $is_debug;

    /**
     * Maximum log file size in bytes (10MB)
     */
    private const MAX_LOG_SIZE = 10485760;

    /**
     * Maximum number of backup log files
     */
    private const MAX_BACKUP_FILES = 5;

    /**
     * Log file path
     */
    private string $log_file;

    /**
     * Error_Handler constructor.
     */
    public function __construct()
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;

        // Set up basic error handling
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);

        // Log initialization
        $this->log_file = MC_PLUGIN_DIR . 'logs/serious-errors.log';
        $this->ensure_log_permissions();
        $this->rotate_logs_if_needed();
        $this->log_debug('MC_ErrorHandler initialized');
    }

    /**
     * Custom error handler
     *
     * @param int    $errno   Error level
     * @param string $errstr  Error message
     * @param string $errfile File where the error occurred
     * @param int    $errline Line where the error occurred
     * @return bool
     */
    public function handle_error($errno, $errstr, $errfile, $errline)
    {
        // Don't handle errors if they're suppressed with @
        if (error_reporting() === 0) {
            return false;
        }

        $error_message = sprintf(
            '[%s] %s in %s on line %d',
            $this->get_error_type($errno),
            $errstr,
            $errfile,
            $errline
        );

        $this->log_error($error_message);

        // Let PHP handle the error as well
        return false;
    }

    /**
     * Custom exception handler
     *
     * @param \Throwable $exception The exception
     */
    public function handle_exception($exception)
    {
        $error_message = sprintf(
            'Uncaught %s: %s in %s on line %d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->log_error($error_message);

        // In debug mode, show the error
        if ($this->is_debug) {
            echo '<h1>Mesmeric Commerce Error</h1>';
            echo '<p>' . esc_html($error_message) . '</p>';
            echo '<h2>Stack Trace</h2>';
            echo '<pre>' . esc_html($exception->getTraceAsString()) . '</pre>';
        } else {
            // In production, show a generic message
            wp_die(
                'An error occurred. Please try again or contact support if the problem persists.',
                'Error',
                ['response' => 500]
            );
        }
    }

    /**
     * Get the error type as a string
     *
     * @param int $errno Error number
     * @return string
     */
    private function get_error_type($errno)
    {
        switch ($errno) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * Log an error message
     *
     * @param string $message The error message
     * @param array  $context Additional context
     */
    public function log_error($message, array $context = [])
    {
        $this->ensure_log_permissions();

        $log_entry = sprintf(
            "[%s] ERROR: %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        error_log($log_entry, 3, $this->log_file);
    }

    /**
     * Log a debug message (only in debug mode)
     *
     * @param string $message The debug message
     * @param array  $context Additional context
     */
    public function log_debug($message, array $context = [])
    {
        if ($this->is_debug) {
            $this->ensure_log_permissions();

            $log_entry = sprintf(
                "[%s] DEBUG: %s %s\n",
                date('Y-m-d H:i:s'),
                $message,
                !empty($context) ? json_encode($context) : ''
            );

            error_log($log_entry, 3, $this->log_file);
        }
    }

    /**
     * Ensure log directory exists and has proper permissions
     */
    private function ensure_log_directory(): void
    {
        $log_dir = MC_PLUGIN_DIR . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Create .htaccess file to deny access
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order Deny,Allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Create web.config file for IIS servers
        $web_config_file = $log_dir . '/web.config';
        if (!file_exists($web_config_file)) {
            $web_config_content = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
<system.webServer>
<authorization>
<deny users="*" />
</authorization>
</system.webServer>
</configuration>';
            file_put_contents($web_config_file, $web_config_content);
        }

        // Create index.php to prevent directory listing
        $index_file = $log_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Ensure proper log file permissions
     */
    private function ensure_log_permissions(): void
    {
        $logs_dir = dirname($this->log_file);

        // Create logs directory if it doesn't exist
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
            // Set directory permissions
            chmod($logs_dir, 0755);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
        }

        // Set file permissions
        chmod($this->log_file, 0644);
    }

    /**
     * Rotate logs if the file size exceeds the limit
     */
    private function rotate_logs_if_needed(): void
    {
        if (!file_exists($this->log_file)) {
            return;
        }

        $file_size = filesize($this->log_file);
        if ($file_size === false || $file_size < self::MAX_LOG_SIZE) {
            return;
        }

        // Rotate backup files
        for ($i = self::MAX_BACKUP_FILES - 1; $i >= 1; $i--) {
            $old_file = sprintf('%s.%d', $this->log_file, $i);
            $new_file = sprintf('%s.%d', $this->log_file, $i + 1);

            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }

        // Move current log to .1
        rename($this->log_file, $this->log_file . '.1');

        // Create new empty log file
        touch($this->log_file);
        chmod($this->log_file, 0644);

        // Compress old log files
        $this->compress_old_logs();
    }

    /**
     * Compress log files older than the first backup
     */
    private function compress_old_logs(): void
    {
        for ($i = 2; $i <= self::MAX_BACKUP_FILES; $i++) {
            $log_file = sprintf('%s.%d', $this->log_file, $i);
            if (file_exists($log_file) && !str_ends_with($log_file, '.gz')) {
                $gz_file = $log_file . '.gz';
                $fp_in = fopen($log_file, 'rb');
                $fp_out = gzopen($gz_file, 'wb9');

                if ($fp_in && $fp_out) {
                    while (!feof($fp_in)) {
                        gzwrite($fp_out, fread($fp_in, 8192));
                    }
                    fclose($fp_in);
                    gzclose($fp_out);
                    unlink($log_file);
                }
            }
        }
    }

    /**
     * Clean up old log files
     */
    public function cleanup_old_logs(): void
    {
        $logs_dir = dirname($this->log_file);
        $pattern = basename($this->log_file) . '.*';

        // Find all log files
        $files = glob($logs_dir . '/' . $pattern);
        if (!$files) {
            return;
        }

        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Keep only the most recent files
        $files_to_keep = array_slice($files, 0, self::MAX_BACKUP_FILES);
        $files_to_delete = array_diff($files, $files_to_keep);

        // Delete old files
        foreach ($files_to_delete as $file) {
            unlink($file);
        }
    }

    /**
     * Schedule cleanup of old log files
     */
    public function schedule_cleanup(): void
    {
        if (!wp_next_scheduled('mc_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'mc_cleanup_logs');
        }
    }

    /**
     * Unschedule cleanup of old log files
     */
    public function unschedule_cleanup(): void
    {
        wp_clear_scheduled_hook('mc_cleanup_logs');
    }

    public function get_plugin_info(): array
    {
        return [
            'plugin_name' => 'Mesmeric Commerce',
            'plugin_version' => defined('MC_VERSION') ? MC_VERSION : 'Unknown',
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'debug_mode' => WP_DEBUG ? 'enabled' : 'disabled',
        ];
    }
}
