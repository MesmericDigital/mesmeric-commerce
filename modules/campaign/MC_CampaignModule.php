<?php
declare(strict_types=1);

namespace MesmericCommerce\Modules\Campaign;

use MesmericCommerce\Includes\Abstract\MC_AbstractModule;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_CampaignModule
 * 
 * Handles email campaigns and marketing functionality
 */
class MC_CampaignModule extends MC_AbstractModule {
    protected MC_CampaignManager $campaign_manager;
    protected MC_EmailService $email_service;
    protected MC_SubscriberManager $subscriber_manager;

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
        $this->campaign_manager = new MC_CampaignManager($this->plugin);
        $this->email_service = new MC_EmailService($this->plugin);
        $this->subscriber_manager = new MC_SubscriberManager($this->plugin);
    }

    /**
     * Register module hooks
     */
    public function register_hooks(): void {
        // Admin hooks
        add_action('admin_menu', [$this, 'add_campaign_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_mc_save_campaign', [$this->campaign_manager, 'handle_save_campaign']);
        add_action('wp_ajax_mc_send_campaign', [$this->campaign_manager, 'handle_send_campaign']);
        add_action('wp_ajax_mc_get_campaign_stats', [$this->campaign_manager, 'handle_get_stats']);
        
        // Subscriber hooks
        add_action('wp_ajax_nopriv_mc_subscribe', [$this->subscriber_manager, 'handle_subscribe']);
        add_action('wp_ajax_mc_subscribe', [$this->subscriber_manager, 'handle_subscribe']);
        
        // WooCommerce hooks
        add_action('woocommerce_checkout_update_order_meta', [$this->subscriber_manager, 'handle_checkout_subscription']);
        add_action('woocommerce_order_status_completed', [$this->campaign_manager, 'trigger_post_purchase_campaign']);
    }

    /**
     * Add campaign menu items
     */
    public function add_campaign_menu(): void {
        add_submenu_page(
            'mesmeric-commerce',
            __('Campaigns', 'mesmeric-commerce'),
            __('Campaigns', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mc-campaigns',
            [$this, 'render_campaigns_page']
        );

        add_submenu_page(
            'mesmeric-commerce',
            __('Subscribers', 'mesmeric-commerce'),
            __('Subscribers', 'mesmeric-commerce'),
            'manage_woocommerce',
            'mc-subscribers',
            [$this, 'render_subscribers_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if ($screen && in_array($screen->id, ['mesmeric-commerce_page_mc-campaigns', 'mesmeric-commerce_page_mc-subscribers'])) {
            wp_enqueue_style(
                'mc-campaign-admin',
                $this->plugin->get_url() . 'assets/css/campaign-admin.css',
                [],
                MC_VERSION
            );

            wp_enqueue_script(
                'mc-campaign-admin',
                $this->plugin->get_url() . 'assets/js/campaign-admin.js',
                ['alpine', 'htmx'],
                MC_VERSION,
                true
            );

            wp_localize_script('mc-campaign-admin', 'mcCampaign', [
                'nonce' => wp_create_nonce('mc_campaign_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]);
        }
    }

    /**
     * Render campaigns page
     */
    public function render_campaigns_page(): void {
        $context = [
            'campaigns' => $this->campaign_manager->get_campaigns(),
            'stats' => $this->campaign_manager->get_overall_stats(),
            'templates' => $this->email_service->get_templates(),
        ];

        $this->plugin->get_twig()->display('campaign/campaigns.twig', $context);
    }

    /**
     * Render subscribers page
     */
    public function render_subscribers_page(): void {
        $context = [
            'subscribers' => $this->subscriber_manager->get_subscribers(),
            'stats' => $this->subscriber_manager->get_stats(),
            'lists' => $this->subscriber_manager->get_lists(),
        ];

        $this->plugin->get_twig()->display('campaign/subscribers.twig', $context);
    }
}
