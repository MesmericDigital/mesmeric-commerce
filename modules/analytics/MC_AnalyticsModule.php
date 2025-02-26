<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Analytics;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_AnalyticsModule
 * 
 * Handles analytics functionality migrated from Mesmeric plugin
 */
class MC_AnalyticsModule extends MC_AbstractModule {
    protected MC_AnalyticsDataProvider $data_provider;
    protected MC_AnalyticsLogger $logger;
    protected MC_AnalyticsReports $reports;

    /**
     * Initialize the module
     */
    public function __construct() {
        parent::__construct();
        $this->init_components();
    }

    /**
     * Initialize module components
     */
    protected function init_components(): void {
        $this->data_provider = new MC_AnalyticsDataProvider($this->plugin);
        $this->logger = new MC_AnalyticsLogger($this->plugin);
        $this->reports = new MC_AnalyticsReports($this->plugin);
    }

    /**
     * Register module hooks
     */
    public function register_hooks(): void {
        // Admin hooks
        add_action('admin_menu', [$this, 'add_analytics_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_mc_get_analytics_data', [$this->data_provider, 'handle_ajax_request']);
        
        // WooCommerce hooks
        add_action('woocommerce_order_status_changed', [$this->logger, 'log_order_status_change'], 10, 4);
        add_action('woocommerce_new_order', [$this->logger, 'log_new_order']);
    }

    /**
     * Add analytics menu item
     */
    public function add_analytics_menu(): void {
        add_submenu_page(
            'mesmeric-commerce',
            __('Analytics', 'mesmeric-commerce'),
            __('Analytics', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mc-analytics',
            [$this, 'render_analytics_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'mesmeric-commerce_page_mc-analytics') {
            wp_enqueue_style(
                'mc-analytics-admin',
                $this->plugin->get_url() . 'assets/css/analytics-admin.css',
                [],
                MC_VERSION
            );

            wp_enqueue_script(
                'mc-analytics-admin',
                $this->plugin->get_url() . 'assets/js/analytics-admin.js',
                ['alpine', 'htmx'],
                MC_VERSION,
                true
            );
        }
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page(): void {
        $context = [
            'reports' => $this->reports->get_overview_data(),
            'dateRange' => $this->data_provider->get_date_range(),
        ];

        $this->plugin->get_twig()->display('analytics/overview.twig', $context);
    }
}
