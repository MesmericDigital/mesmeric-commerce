<?php
/**
 * Error Handler class for Mesmeric Commerce
 *
 * @package MesmericCommerce
 */

namespace MesmericCommerce;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Class Error_Handler
 */
class Error_Handler {
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Run
     */
    private $whoops;

    /**
     * @var bool
     */
    private $is_debug;

    /**
     * Error_Handler constructor.
     */
    public function __construct() {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->setup_logger();
        $this->setup_whoops();
    }

    /**
     * Setup the logger
     */
    private function setup_logger() {
        $this->logger = new Logger('mesmeric-commerce');
        
        // Create logs directory if it doesn't exist
        $log_dir = WP_CONTENT_DIR . '/mesmeric-commerce-logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Add rotating file handler (keeps last 30 days of logs)
        $rotating_handler = new RotatingFileHandler(
            $log_dir . '/error.log',
            30,
            $this->is_debug ? Logger::DEBUG : Logger::ERROR
        );

        // Set the format
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        );
        $rotating_handler->setFormatter($formatter);

        $this->logger->pushHandler($rotating_handler);
    }

    /**
     * Setup Whoops error handler
     */
    private function setup_whoops() {
        $this->whoops = new Run();

        if ($this->is_debug) {
            // In debug mode, show the pretty page handler
            $handler = new PrettyPageHandler();
            
            // Add some custom data to the debug page
            $handler->addDataTable('Mesmeric Commerce Info', [
                'Version' => defined('MESMERIC_COMMERCE_VERSION') ? MESMERIC_COMMERCE_VERSION : 'Unknown',
                'WordPress Version' => get_bloginfo('version'),
                'PHP Version' => PHP_VERSION,
            ]);

            $this->whoops->pushHandler($handler);
        } else {
            // In production, only log the error and show a generic message
            $this->whoops->pushHandler(function($exception, $inspector, $run) {
                $this->logger->error($exception->getMessage(), [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]);

                // Show generic error message
                wp_die(
                    'An error occurred. Please try again or contact support if the problem persists.',
                    'Error',
                    ['response' => 500]
                );
            });
        }

        // For AJAX requests, return JSON
        if (wp_is_json_request()) {
            $this->whoops->pushHandler(new JsonResponseHandler());
        }

        $this->whoops->register();
    }

    /**
     * Log an error message
     *
     * @param string $message The error message
     * @param array  $context Additional context
     */
    public function log_error($message, array $context = []) {
        $this->logger->error($message, $context);
    }

    /**
     * Log a debug message (only in debug mode)
     *
     * @param string $message The debug message
     * @param array  $context Additional context
     */
    public function log_debug($message, array $context = []) {
        if ($this->is_debug) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * Get the logger instance
     *
     * @return Logger
     */
    public function get_logger() {
        return $this->logger;
    }
}
