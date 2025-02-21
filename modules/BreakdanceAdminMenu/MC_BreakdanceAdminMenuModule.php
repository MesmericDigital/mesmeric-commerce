<?php
/**
 * Breakdance Admin Menu Module
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/breakdance-admin-menu
 */

declare(strict_types=1);

namespace MesmericCommerce\Modules\BreakdanceAdminMenu;

use MesmericCommerce\Includes\MC_Logger;
use MesmericCommerce\Includes\MC_Plugin;

/**
 * Class MC_BreakdanceAdminMenuModule
 *
 * Handles Breakdance admin menu integration and customization
 */
class MC_BreakdanceAdminMenuModule {
    /**
     * The plugin's instance.
     *
     * @var MC_Plugin
     */
    private MC_Plugin $plugin;

    /**
     * The logger instance.
     *
     * @var MC_Logger
     */
    private MC_Logger $logger;

    /**
     * Initialize the module.
     */
    public function __construct() {
        global $mesmeric_commerce;
        $this->plugin = $mesmeric_commerce;
        $this->logger = $this->plugin->get_logger();

        // Add debugging
        error_log('Breakdance Admin Menu Module initialized');

        $this->register_hooks();
    }

    /**
     * Register module hooks.
     *
     * @return void
     */
    private function register_hooks(): void {
        // Add debugging
        error_log('Registering Breakdance Admin Menu hooks');

        // Use a later priority to ensure our menu appears after other items
        add_action('admin_bar_menu', array($this, 'add_breakdance_nav'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'), 100);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_admin_assets'), 100); // For front-end

        error_log('Breakdance Admin Menu hooks registered');
    }

    /**
     * Add Breakdance navigator to admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    public function add_breakdance_nav(\WP_Admin_Bar $admin_bar): void {
        // Add debugging
        error_log('Adding Breakdance nav to admin bar');

        if (!current_user_can('edit_posts')) {
            error_log('User cannot edit posts, not adding Breakdance nav');
            return;
        }

        // Add main menu item with icon
        $icon_url = plugin_dir_url(__FILE__) . 'assets/images/breakdance-icon.png';
        $title_html = sprintf(
            '<img src="%s" style="width:16px;height:16px;padding-right:6px;vertical-align:middle;" alt="">%s',
            esc_url($icon_url),
            esc_html__('Breakdance Nav', 'mesmeric-commerce')
        );

        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-nav',
            'title' => $title_html,
            'href' => '#',
        ));

        // Add submenus
        $this->add_pages_submenu($admin_bar);
        $this->add_templates_submenu($admin_bar);
        $this->add_headers_submenu($admin_bar);
        $this->add_footers_submenu($admin_bar);
        $this->add_global_blocks_submenu($admin_bar);
        $this->add_popups_submenu($admin_bar);
        $this->add_form_submissions_submenu($admin_bar);
        $this->add_design_library_submenu($admin_bar);
        $this->add_settings_submenu($admin_bar);
        $this->add_headspin_submenu($admin_bar);
        $this->add_links_submenu($admin_bar);
        $this->add_about_submenu($admin_bar);
    }

    /**
     * Add pages submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_pages_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-pages',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Pages', 'mesmeric-commerce'),
            'href' => admin_url('edit.php?post_type=page'),
        ));

        $pages = $this->get_breakdance_pages();
        foreach ($pages as $page) {
            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-page-' . $page->ID,
                'parent' => 'mc-breakdance-pages',
                'title' => $page->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $page->ID),
            ));
        }
    }

    /**
     * Get Breakdance pages.
     *
     * @return array
     */
    private function get_breakdance_pages(): array {
        return get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_breakdance_data',
                    'compare' => 'EXISTS',
                ),
            ),
        ));
    }

    /**
     * Add templates submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_templates_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-templates',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Templates', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_template'),
        ));

        $templates = $this->get_breakdance_templates();
        foreach ($templates as $template) {
            if (strpos($template->post_title, 'Fallback: ') === 0) {
                continue;
            }

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-template-' . $template->ID,
                'parent' => 'mc-breakdance-templates',
                'title' => $template->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $template->ID),
            ));
        }
    }

    /**
     * Add headers submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_headers_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-headers',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Headers', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_header'),
        ));

        $headers = $this->get_breakdance_headers();
        foreach ($headers as $header) {
            if (strpos($header->post_title, 'Fallback: ') === 0) {
                continue;
            }

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-header-' . $header->ID,
                'parent' => 'mc-breakdance-headers',
                'title' => $header->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $header->ID),
            ));
        }
    }

    /**
     * Add footers submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_footers_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-footers',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Footers', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_footer'),
        ));

        $footers = $this->get_breakdance_footers();
        foreach ($footers as $footer) {
            if (strpos($footer->post_title, 'Fallback: ') === 0) {
                continue;
            }

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-footer-' . $footer->ID,
                'parent' => 'mc-breakdance-footers',
                'title' => $footer->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $footer->ID),
            ));
        }
    }

    /**
     * Add global blocks submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_global_blocks_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-blocks',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Global Blocks', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_block'),
        ));

        $blocks = $this->get_breakdance_global_blocks();
        foreach ($blocks as $block) {
            if (strpos($block->post_title, 'Fallback: ') === 0) {
                continue;
            }

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-block-' . $block->ID,
                'parent' => 'mc-breakdance-blocks',
                'title' => $block->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $block->ID),
            ));
        }
    }

    /**
     * Add popups submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_popups_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-popups',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Popups', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_popup'),
        ));

        $popups = $this->get_breakdance_popups();
        foreach ($popups as $popup) {
            if (strpos($popup->post_title, 'Fallback: ') === 0) {
                continue;
            }

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-popup-' . $popup->ID,
                'parent' => 'mc-breakdance-popups',
                'title' => $popup->post_title,
                'href' => site_url('/?breakdance=builder&id=' . $popup->ID),
            ));
        }
    }

    /**
     * Add form submissions submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_form_submissions_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-form-submissions',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Form Submissions', 'mesmeric-commerce'),
            'href' => admin_url('edit.php?post_type=breakdance_form_res'),
        ));
    }

    /**
     * Add design library submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_design_library_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-design-library',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Design Library', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_design_library'),
        ));
    }

    /**
     * Add settings submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_settings_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-settings',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Settings', 'mesmeric-commerce'),
            'href' => admin_url('admin.php?page=breakdance_settings'),
            'meta' => array('class' => 'mc-settings-separator'),
        ));

        $settings_submenus = array(
            'license' => __('License', 'mesmeric-commerce'),
            'global_styles' => __('Global Styles', 'mesmeric-commerce'),
            'theme_disabler' => __('Theme', 'mesmeric-commerce'),
            'woocommerce' => __('WooCommerce', 'mesmeric-commerce'),
            'permissions' => __('User Access', 'mesmeric-commerce'),
            'maintenance-mode' => __('Maintenance', 'mesmeric-commerce'),
            'bloat_eliminator' => __('Performance', 'mesmeric-commerce'),
            'api_keys' => __('API Keys', 'mesmeric-commerce'),
            'post_types' => __('Post Types', 'mesmeric-commerce'),
            'advanced' => __('Advanced', 'mesmeric-commerce'),
            'privacy' => __('Privacy', 'mesmeric-commerce'),
            'design_library' => __('Design Library', 'mesmeric-commerce'),
            'header_footer' => __('Custom Code', 'mesmeric-commerce'),
            'tools' => __('Tools', 'mesmeric-commerce'),
            'ai' => __('AI Assistant', 'mesmeric-commerce'),
        );

        foreach ($settings_submenus as $tab => $title) {
            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-settings-' . sanitize_key($tab),
                'parent' => 'mc-breakdance-settings',
                'title' => $title,
                'href' => admin_url('admin.php?page=breakdance_settings&tab=' . urlencode($tab)),
            ));
        }
    }

    /**
     * Add Headspin submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_headspin_submenu(\WP_Admin_Bar $admin_bar): void {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        if (is_plugin_active('headspinui/headspinui.php')) {
            $icon_url = plugin_dir_url(__FILE__) . 'assets/images/headspin-icon.png';
            $title_html = sprintf(
                '<img src="%s" style="width:16px;height:16px;padding-right:6px;vertical-align:middle;" alt="">%s',
                esc_url($icon_url),
                esc_html__('Headspin', 'mesmeric-commerce')
            );

            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-headspin',
                'parent' => 'mc-breakdance-nav',
                'title' => $title_html,
                'href' => admin_url('admin.php?page=headspin'),
            ));
        }
    }

    /**
     * Add links submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_links_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-links',
            'parent' => 'mc-breakdance-nav',
            'title' => __('Links', 'mesmeric-commerce'),
            'href' => '#',
        ));

        $links = array(
            'breakdance' => array(
                'title' => __('Breakdance', 'mesmeric-commerce'),
                'url' => 'https://breakdance.com/ref/325/',
            ),
            'breakdance-fb-group' => array(
                'title' => __('Breakdance FB Group', 'mesmeric-commerce'),
                'url' => 'https://www.facebook.com/groups/5118076864894234',
            ),
            'headspin' => array(
                'title' => __('Headspin', 'mesmeric-commerce'),
                'url' => 'https://headspinui.com/',
            ),
            'moreblocks' => array(
                'title' => __('Moreblocks', 'mesmeric-commerce'),
                'url' => 'https://moreblocks.com/',
            ),
            'breakerblocks' => array(
                'title' => __('Breakerblocks', 'mesmeric-commerce'),
                'url' => 'https://breakerblocks.com/',
            ),
            'bdlibraryawesome' => array(
                'title' => __('BD Library Awesome', 'mesmeric-commerce'),
                'url' => 'https://bdlibraryawesome.com/',
            ),
            'flowmattic' => array(
                'title' => __('Flowmattic', 'mesmeric-commerce'),
                'url' => 'https://flowmattic.com/integrations/?aff=97',
            ),
        );

        foreach ($links as $id => $info) {
            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-links-' . sanitize_key($id),
                'parent' => 'mc-breakdance-links',
                'title' => $info['title'],
                'href' => $info['url'],
                'meta' => array('target' => '_blank'),
            ));
        }
    }

    /**
     * Add about submenu.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    private function add_about_submenu(\WP_Admin_Bar $admin_bar): void {
        $admin_bar->add_node(array(
            'id' => 'mc-breakdance-about',
            'parent' => 'mc-breakdance-nav',
            'title' => __('About', 'mesmeric-commerce'),
            'href' => '#',
        ));

        $about_links = array(
            'author' => array(
                'title' => __('Author: Bean Bag Planet', 'mesmeric-commerce'),
                'url' => 'https://beanbagplanet.co.uk/',
            ),
            'github' => array(
                'title' => __('Plugin on GitHub', 'mesmeric-commerce'),
                'url' => 'https://github.com/beanbagplanet/mesmeric-commerce',
            ),
        );

        foreach ($about_links as $id => $info) {
            $admin_bar->add_node(array(
                'id' => 'mc-breakdance-about-' . sanitize_key($id),
                'parent' => 'mc-breakdance-about',
                'title' => $info['title'],
                'href' => $info['url'],
                'meta' => array('target' => '_blank'),
            ));
        }
    }

    /**
     * Get Breakdance templates.
     *
     * @return array
     */
    private function get_breakdance_templates(): array {
        return get_posts(array(
            'post_type' => 'breakdance_template',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
    }

    /**
     * Get Breakdance headers.
     *
     * @return array
     */
    private function get_breakdance_headers(): array {
        return get_posts(array(
            'post_type' => 'breakdance_header',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
    }

    /**
     * Get Breakdance footers.
     *
     * @return array
     */
    private function get_breakdance_footers(): array {
        return get_posts(array(
            'post_type' => 'breakdance_footer',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
    }

    /**
     * Get Breakdance global blocks.
     *
     * @return array
     */
    private function get_breakdance_global_blocks(): array {
        return get_posts(array(
            'post_type' => 'breakdance_block',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
    }

    /**
     * Get Breakdance popups.
     *
     * @return array
     */
    private function get_breakdance_popups(): array {
        return get_posts(array(
            'post_type' => 'breakdance_popup',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
    }

    /**
     * Enqueue admin assets.
     *
     * @return void
     */
    public function enqueue_admin_assets(): void {
        if (is_admin_bar_showing()) {
            wp_enqueue_style(
                'mc-breakdance-nav',
                plugin_dir_url(__FILE__) . 'assets/css/breakdance-nav.css',
                array(),
                MC_VERSION
            );

            wp_add_inline_style('admin-bar', '
                #wp-admin-bar-mc-breakdance-nav > .ab-sub-wrapper #wp-admin-bar-mc-breakdance-settings {
                    border-bottom: 1px solid #ccc;
                    margin: 0 0 5px 0;
                }
            ');
        }
    }
}
