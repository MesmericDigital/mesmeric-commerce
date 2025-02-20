<?php
declare(strict_types=1);

use MesmericCommerce\WooCommerce\Services\MC_Shipping_Service;

defined('ABSPATH') || exit;

/** @var MC_Shipping_Service $this */
$settings = $this->get_settings();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('mc_shipping_settings'); ?>

        <div class="mc-settings-container">
            <div class="mc-settings-section">
                <h2><?php esc_html_e('General Settings', 'mesmeric-commerce'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_shipping_zones">
                                <?php esc_html_e('Enable Shipping Zones', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="enable_shipping_zones"
                                   name="mc_shipping_settings[enable_shipping_zones]"
                                   value="1"
                                   <?php checked($settings['enable_shipping_zones'] ?? false); ?>>
                            <p class="description">
                                <?php esc_html_e('Enable shipping zones for location-based shipping methods', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_local_pickup">
                                <?php esc_html_e('Enable Local Pickup', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="enable_local_pickup"
                                   name="mc_shipping_settings[enable_local_pickup]"
                                   value="1"
                                   <?php checked($settings['enable_local_pickup'] ?? false); ?>>
                            <p class="description">
                                <?php esc_html_e('Allow customers to pick up orders from your store', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="mc-settings-section">
                <h2><?php esc_html_e('Evri Shipping Settings', 'mesmeric-commerce'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_evri">
                                <?php esc_html_e('Enable Evri Shipping', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="enable_evri"
                                   name="mc_shipping_settings[enable_evri]"
                                   value="1"
                                   <?php checked($settings['enable_evri'] ?? false); ?>>
                            <p class="description">
                                <?php esc_html_e('Enable Evri as a shipping method', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="evri_api_key">
                                <?php esc_html_e('API Key', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password"
                                   id="evri_api_key"
                                   name="mc_shipping_settings[evri_api_key]"
                                   value="<?php echo esc_attr($settings['evri_api_key'] ?? ''); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php esc_html_e('Enter your Evri API key', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="evri_test_mode">
                                <?php esc_html_e('Test Mode', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="evri_test_mode"
                                   name="mc_shipping_settings[evri_test_mode]"
                                   value="1"
                                   <?php checked($settings['evri_test_mode'] ?? false); ?>>
                            <p class="description">
                                <?php esc_html_e('Enable test mode for Evri shipping', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="mc-settings-section">
                <h2><?php esc_html_e('Custom Rates', 'mesmeric-commerce'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_custom_rates">
                                <?php esc_html_e('Enable Custom Rates', 'mesmeric-commerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="enable_custom_rates"
                                   name="mc_shipping_settings[enable_custom_rates]"
                                   value="1"
                                   <?php checked($settings['enable_custom_rates'] ?? false); ?>>
                            <p class="description">
                                <?php esc_html_e('Enable custom shipping rates based on weight, dimensions, or price', 'mesmeric-commerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <div id="custom-rates-table" class="<?php echo $settings['enable_custom_rates'] ? '' : 'hidden'; ?>">
                    <table class="widefat" id="mc-custom-rates">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Min Weight (kg)', 'mesmeric-commerce'); ?></th>
                                <th><?php esc_html_e('Max Weight (kg)', 'mesmeric-commerce'); ?></th>
                                <th><?php esc_html_e('Rate (£)', 'mesmeric-commerce'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $custom_rates = $settings['custom_rates'] ?? [];
                            foreach ($custom_rates as $rate) :
                            ?>
                            <tr>
                                <td>
                                    <input type="number"
                                           name="mc_shipping_settings[custom_rates][min_weight][]"
                                           value="<?php echo esc_attr($rate['min_weight']); ?>"
                                           step="0.1"
                                           min="0">
                                </td>
                                <td>
                                    <input type="number"
                                           name="mc_shipping_settings[custom_rates][max_weight][]"
                                           value="<?php echo esc_attr($rate['max_weight']); ?>"
                                           step="0.1"
                                           min="0">
                                </td>
                                <td>
                                    <input type="number"
                                           name="mc_shipping_settings[custom_rates][rate][]"
                                           value="<?php echo esc_attr($rate['rate']); ?>"
                                           step="0.01"
                                           min="0">
                                </td>
                                <td>
                                    <button type="button" class="button remove-rate">
                                        <?php esc_html_e('Remove', 'mesmeric-commerce'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <button type="button" class="button add-rate">
                                        <?php esc_html_e('Add Rate', 'mesmeric-commerce'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script type="text/template" id="rate-row-template">
    <tr>
        <td>
            <input type="number"
                   name="mc_shipping_settings[custom_rates][min_weight][]"
                   value=""
                   step="0.1"
                   min="0">
        </td>
        <td>
            <input type="number"
                   name="mc_shipping_settings[custom_rates][max_weight][]"
                   value=""
                   step="0.1"
                   min="0">
        </td>
        <td>
            <input type="number"
                   name="mc_shipping_settings[custom_rates][rate][]"
                   value=""
                   step="0.01"
                   min="0">
        </td>
        <td>
            <button type="button" class="button remove-rate">
                <?php esc_html_e('Remove', 'mesmeric-commerce'); ?>
            </button>
        </td>
    </tr>
</script>
