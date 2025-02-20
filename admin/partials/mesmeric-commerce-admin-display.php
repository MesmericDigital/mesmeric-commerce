<?php
/**
 * Main admin interface template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/admin/partials
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="wrap">
    <div class="mc-admin-header">
        <h1 class="text-2xl font-bold mb-4">
            <?php esc_html_e('Mesmeric Commerce', 'mesmeric-commerce'); ?>
            <span class="text-sm font-normal ml-2">v<?php echo esc_html(MC_VERSION); ?></span>
        </h1>
    </div>

    <div class="mc-admin-tabs tabs tabs-boxed mb-6">
        <a href="#dashboard" class="tab tab-active" data-tab="dashboard">
            <?php esc_html_e('Dashboard', 'mesmeric-commerce'); ?>
        </a>
        <a href="#modules" class="tab" data-tab="modules">
            <?php esc_html_e('Modules', 'mesmeric-commerce'); ?>
        </a>
        <a href="#settings" class="tab" data-tab="settings">
            <?php esc_html_e('Settings', 'mesmeric-commerce'); ?>
        </a>
        <a href="#logs" class="tab" data-tab="logs">
            <?php esc_html_e('Logs', 'mesmeric-commerce'); ?>
        </a>
    </div>

    <div id="dashboard" class="tab-content active">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Quick View Stats -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title"><?php esc_html_e('Quick Views', 'mesmeric-commerce'); ?></div>
                    <div class="stat-value"><?php echo esc_html(get_option('mc_quick_view_count', '0')); ?></div>
                    <div class="stat-desc"><?php esc_html_e('Last 30 days', 'mesmeric-commerce'); ?></div>
                </div>
            </div>

            <!-- Wishlist Stats -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title"><?php esc_html_e('Wishlists', 'mesmeric-commerce'); ?></div>
                    <div class="stat-value"><?php echo esc_html(get_option('mc_wishlist_count', '0')); ?></div>
                    <div class="stat-desc"><?php esc_html_e('Active wishlists', 'mesmeric-commerce'); ?></div>
                </div>
            </div>

            <!-- Inventory Stats -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title"><?php esc_html_e('Low Stock', 'mesmeric-commerce'); ?></div>
                    <div class="stat-value text-warning">
                        <?php
                        $args = array(
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => '_stock_status',
                                    'value' => 'instock',
                                    'compare' => '='
                                ),
                                array(
                                    'key' => '_stock',
                                    'value' => array(0, get_option('woocommerce_notify_low_stock_amount', 2)),
                                    'type' => 'NUMERIC',
                                    'compare' => 'BETWEEN'
                                )
                            )
                        );
                        $low_stock_products = new WP_Query($args);
                        echo esc_html($low_stock_products->found_posts);
                        ?>
                    </div>
                    <div class="stat-desc"><?php esc_html_e('Products below threshold', 'mesmeric-commerce'); ?></div>
                </div>
            </div>

            <!-- FAQ Stats -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title"><?php esc_html_e('FAQs', 'mesmeric-commerce'); ?></div>
                    <div class="stat-value">
                        <?php
                        $faq_count = wp_count_posts('mc_faq');
                        echo esc_html($faq_count->publish);
                        ?>
                    </div>
                    <div class="stat-desc"><?php esc_html_e('Published FAQs', 'mesmeric-commerce'); ?></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Activity -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title"><?php esc_html_e('Recent Activity', 'mesmeric-commerce'); ?></h2>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Event', 'mesmeric-commerce'); ?></th>
                                    <th><?php esc_html_e('Date', 'mesmeric-commerce'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_activity = get_option('mc_recent_activity', array());
                                foreach ($recent_activity as $activity):
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($activity['message']); ?></td>
                                        <td><?php echo esc_html(human_time_diff($activity['timestamp'])) . ' ' . esc_html__('ago', 'mesmeric-commerce'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title"><?php esc_html_e('System Status', 'mesmeric-commerce'); ?></h2>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <tbody>
                                <tr>
                                    <td><?php esc_html_e('WordPress Version', 'mesmeric-commerce'); ?></td>
                                    <td>
                                        <div class="badge <?php echo version_compare(get_bloginfo('version'), '6.0', '>=') ? 'badge-success' : 'badge-error'; ?>">
                                            <?php echo esc_html(get_bloginfo('version')); ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('WooCommerce Version', 'mesmeric-commerce'); ?></td>
                                    <td>
                                        <div class="badge <?php echo version_compare(WC()->version, '8.0.0', '>=') ? 'badge-success' : 'badge-error'; ?>">
                                            <?php echo esc_html(WC()->version); ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('PHP Version', 'mesmeric-commerce'); ?></td>
                                    <td>
                                        <div class="badge <?php echo version_compare(PHP_VERSION, '8.3', '>=') ? 'badge-success' : 'badge-error'; ?>">
                                            <?php echo esc_html(PHP_VERSION); ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Memory Limit', 'mesmeric-commerce'); ?></td>
                                    <td>
                                        <div class="badge badge-info">
                                            <?php echo esc_html(WP_MEMORY_LIMIT); ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modules" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick View Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('Quick View', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_quickview', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('HTMX-powered product preview modal with instant add to cart functionality.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-quickview')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Wishlist Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('Wishlist', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_wishlist', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('Multiple wishlists per user with sharing functionality.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-wishlist')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Shipping Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('Shipping', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_shipping', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('Enhanced shipping calculations and custom shipping methods.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-shipping')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Inventory Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('Inventory', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_inventory', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('Advanced stock management with notifications and reorder suggestions.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-inventory')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- FAQ Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('FAQ', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_faq', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('Product-specific and category-level FAQs with AJAX-powered frontend.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-faq')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Breakdance Admin Menu Module -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php esc_html_e('Breakdance Admin Menu', 'mesmeric-commerce'); ?>
                        <div class="badge badge-primary">
                            <?php echo get_option('mc_enable_breakdance_admin_menu', 'yes') === 'yes' ? esc_html__('Active', 'mesmeric-commerce') : esc_html__('Inactive', 'mesmeric-commerce'); ?>
                        </div>
                    </h2>
                    <p><?php esc_html_e('Custom admin menu integration for Breakdance with visual enhancements.', 'mesmeric-commerce'); ?></p>
                    <div class="card-actions justify-end">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mesmeric-commerce-breakdance')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Manage', 'mesmeric-commerce'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="settings" class="tab-content hidden">
        <form method="post" action="options.php" class="card bg-base-100 shadow-xl">
            <?php
            settings_fields('mesmeric_commerce_settings');
            do_settings_sections('mesmeric_commerce_settings');
            ?>

            <div class="card-body">
                <h2 class="card-title mb-6"><?php esc_html_e('General Settings', 'mesmeric-commerce'); ?></h2>

                <div class="form-control w-full max-w-xs mb-4">
                    <label class="label">
                        <span class="label-text"><?php esc_html_e('Debug Mode', 'mesmeric-commerce'); ?></span>
                    </label>
                    <select name="mc_debug_mode" class="select select-bordered">
                        <option value="no" <?php selected(get_option('mc_debug_mode'), 'no'); ?>>
                            <?php esc_html_e('Disabled', 'mesmeric-commerce'); ?>
                        </option>
                        <option value="yes" <?php selected(get_option('mc_debug_mode'), 'yes'); ?>>
                            <?php esc_html_e('Enabled', 'mesmeric-commerce'); ?>
                        </option>
                    </select>
                </div>

                <div class="form-control w-full max-w-xs mb-4">
                    <label class="label">
                        <span class="label-text"><?php esc_html_e('Cache Duration (hours)', 'mesmeric-commerce'); ?></span>
                    </label>
                    <input type="number"
                           name="mc_cache_duration"
                           value="<?php echo esc_attr(get_option('mc_cache_duration', '24')); ?>"
                           class="input input-bordered w-full max-w-xs"
                           min="1"
                           max="168">
                </div>

                <div class="form-control w-full max-w-xs mb-4">
                    <label class="label">
                        <span class="label-text"><?php esc_html_e('Error Logging', 'mesmeric-commerce'); ?></span>
                    </label>
                    <select name="mc_error_logging" class="select select-bordered">
                        <option value="none" <?php selected(get_option('mc_error_logging'), 'none'); ?>>
                            <?php esc_html_e('None', 'mesmeric-commerce'); ?>
                        </option>
                        <option value="errors" <?php selected(get_option('mc_error_logging'), 'errors'); ?>>
                            <?php esc_html_e('Errors Only', 'mesmeric-commerce'); ?>
                        </option>
                        <option value="all" <?php selected(get_option('mc_error_logging'), 'all'); ?>>
                            <?php esc_html_e('All', 'mesmeric-commerce'); ?>
                        </option>
                    </select>
                </div>

                <div class="form-control w-full max-w-xs mb-4">
                    <label class="label">
                        <span class="label-text"><?php esc_html_e('AJAX Timeout (seconds)', 'mesmeric-commerce'); ?></span>
                    </label>
                    <input type="number"
                           name="mc_ajax_timeout"
                           value="<?php echo esc_attr(get_option('mc_ajax_timeout', '30')); ?>"
                           class="input input-bordered w-full max-w-xs"
                           min="5"
                           max="120">
                </div>

                <div class="card-actions justify-end mt-6">
                    <?php submit_button(null, 'btn btn-primary', 'submit', false); ?>
                </div>
            </div>
        </form>
    </div>

    <div id="logs" class="tab-content hidden">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="card-title"><?php esc_html_e('Error Logs', 'mesmeric-commerce'); ?></h2>
                    <button type="button" class="btn btn-error btn-sm clear-logs">
                        <?php esc_html_e('Clear Logs', 'mesmeric-commerce'); ?>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Timestamp', 'mesmeric-commerce'); ?></th>
                                <th><?php esc_html_e('Level', 'mesmeric-commerce'); ?></th>
                                <th><?php esc_html_e('Message', 'mesmeric-commerce'); ?></th>
                                <th><?php esc_html_e('Context', 'mesmeric-commerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $mesmeric_commerce;
                            $log_content = $mesmeric_commerce->get_logger()->get_log_content(date('Y-m-d'));
                            $log_entries = explode("\n", $log_content);

                            foreach ($log_entries as $entry):
                                if (empty($entry)) continue;
                                $parts = explode('] ', $entry);
                                $timestamp = trim($parts[0], '[');
                                $level = trim($parts[1], '[]');
                                $message = $parts[2] ?? '';
                                $context = $parts[3] ?? '';
                                ?>
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <?php echo esc_html($timestamp); ?>
                                    </td>
                                    <td>
                                        <div class="badge <?php
                                        echo esc_attr(match ($level) {
                                            'ERROR' => 'badge-error',
                                            'WARNING' => 'badge-warning',
                                            'INFO' => 'badge-info',
                                            default => 'badge-ghost'
                                        });
                                        ?>">
                                            <?php echo esc_html($level); ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($message); ?></td>
                                    <td>
                                        <div class="tooltip" data-tip="<?php echo esc_attr($context); ?>">
                                            <button class="btn btn-ghost btn-xs">
                                                <?php esc_html_e('View', 'mesmeric-commerce'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
