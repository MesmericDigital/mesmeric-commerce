<?php
/**
 * Shipping management page template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/shipping/views
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="wrap">
    <h1><?php esc_html_e('Shipping Management', 'mesmeric-commerce'); ?></h1>

    <h2 class="nav-tab-wrapper mc-shipping-tabs">
        <a href="#zones" class="nav-tab nav-tab-active"><?php esc_html_e('Shipping Zones', 'mesmeric-commerce'); ?></a>
        <a href="#rules" class="nav-tab"><?php esc_html_e('Shipping Rules', 'mesmeric-commerce'); ?></a>
        <a href="#settings" class="nav-tab"><?php esc_html_e('Settings', 'mesmeric-commerce'); ?></a>
    </h2>

    <div id="zones" class="tab-content active">
        <div class="mc-shipping-zones-header">
            <h2><?php esc_html_e('Shipping Zones', 'mesmeric-commerce'); ?></h2>
            <button type="button" class="button button-primary add-zone">
                <?php esc_html_e('Add Zone', 'mesmeric-commerce'); ?>
            </button>
        </div>

        <table class="widefat mc-shipping-zones-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Zone Name', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Region(s)', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Shipping Methods', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Actions', 'mesmeric-commerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $zones = \WC_Shipping_Zones::get_zones();
                foreach ($zones as $zone):
                    $zone_obj = new \WC_Shipping_Zone($zone['id']);
                    ?>
                    <tr data-zone-id="<?php echo esc_attr($zone['id']); ?>">
                        <td><?php echo esc_html($zone['zone_name']); ?></td>
                        <td>
                            <?php
                            $locations = $zone_obj->get_formatted_location();
                            echo esc_html($locations ? $locations : __('Everywhere', 'mesmeric-commerce'));
                            ?>
                        </td>
                        <td>
                            <?php
                            $methods = $zone_obj->get_shipping_methods();
                            $method_names = array_map(function($method) {
                                return $method->get_title();
                            }, $methods);
                            echo esc_html(implode(', ', $method_names));
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button edit-zone">
                                <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                            </button>
                            <button type="button" class="button delete-zone">
                                <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="rules" class="tab-content">
        <div class="mc-shipping-rules-header">
            <h2><?php esc_html_e('Shipping Rules', 'mesmeric-commerce'); ?></h2>
            <button type="button" class="button button-primary add-rule">
                <?php esc_html_e('Add Rule', 'mesmeric-commerce'); ?>
            </button>
        </div>

        <table class="widefat mc-shipping-rules-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Rule Name', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Conditions', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Action', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Actions', 'mesmeric-commerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rules = get_option('mc_shipping_rules', array());
                foreach ($rules as $rule):
                    ?>
                    <tr data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                        <td><?php echo esc_html($rule['name']); ?></td>
                        <td>
                            <?php
                            $conditions = array_map(function($condition) {
                                return sprintf(
                                    '%s %s %s',
                                    esc_html($condition['type']),
                                    esc_html($condition['operator']),
                                    esc_html($condition['value'])
                                );
                            }, $rule['conditions'] ?? array());
                            echo implode(', ', $conditions);
                            ?>
                        </td>
                        <td>
                            <?php
                            printf(
                                '%s %s',
                                esc_html($rule['action']),
                                esc_html($rule['amount'])
                            );
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button edit-rule">
                                <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                            </button>
                            <button type="button" class="button delete-rule">
                                <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="settings" class="tab-content">
        <form method="post" action="options.php" class="mc-shipping-settings">
            <?php
            settings_fields('mc_shipping_settings');
            do_settings_sections('mc_shipping_settings');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mc_shipping_handling_fee">
                            <?php esc_html_e('Handling Fee', 'mesmeric-commerce'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="mc_shipping_handling_fee"
                               name="mc_shipping_handling_fee"
                               value="<?php echo esc_attr(get_option('mc_shipping_handling_fee', '0')); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Additional handling fee to be added to all shipments.', 'mesmeric-commerce'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script type="text/template" id="tmpl-zone-modal">
    <div class="mc-modal">
        <div class="mc-modal-content">
            <h3><?php esc_html_e('{{id ? "Edit" : "Add"}} Shipping Zone', 'mesmeric-commerce'); ?></h3>
            <form class="zone-form">
                <div class="form-field">
                    <label for="zone-name"><?php esc_html_e('Zone Name', 'mesmeric-commerce'); ?></label>
                    <input type="text"
                           id="zone-name"
                           name="name"
                           value="{{name}}"
                           required>
                </div>

                <div class="form-field">
                    <label for="zone-regions"><?php esc_html_e('Regions', 'mesmeric-commerce'); ?></label>
                    <select id="zone-regions"
                            name="regions[]"
                            multiple
                            class="wc-enhanced-select">
                        <?php
                        $continents = WC()->countries->get_continents();
                        $countries = WC()->countries->get_countries();
                        $states = WC()->countries->get_states();

                        // Continents
                        foreach ($continents as $code => $continent):
                            ?>
                            <option value="continent:<?php echo esc_attr($code); ?>">
                                <?php echo esc_html($continent['name']); ?>
                            </option>
                            <?php
                        endforeach;

                        // Countries
                        foreach ($countries as $code => $name):
                            ?>
                            <option value="country:<?php echo esc_attr($code); ?>">
                                <?php echo esc_html($name); ?>
                            </option>
                            <?php
                            // States
                            if (isset($states[$code])):
                                foreach ($states[$code] as $state_code => $state_name):
                                    ?>
                                    <option value="state:<?php echo esc_attr($code . ':' . $state_code); ?>">
                                        <?php echo esc_html($name . ' — ' . $state_name); ?>
                                    </option>
                                    <?php
                                endforeach;
                            endif;
                        endforeach;
                        ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="button cancel">
                        <?php esc_html_e('Cancel', 'mesmeric-commerce'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save', 'mesmeric-commerce'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</script>

<script type="text/template" id="tmpl-rule-modal">
    <div class="mc-modal">
        <div class="mc-modal-content">
            <h3><?php esc_html_e('{{id ? "Edit" : "Add"}} Shipping Rule', 'mesmeric-commerce'); ?></h3>
            <form class="rule-form">
                <div class="form-field">
                    <label for="rule-name"><?php esc_html_e('Rule Name', 'mesmeric-commerce'); ?></label>
                    <input type="text"
                           id="rule-name"
                           name="name"
                           value="{{name}}"
                           required>
                </div>

                <div class="form-field">
                    <label><?php esc_html_e('Conditions', 'mesmeric-commerce'); ?></label>
                    <div class="condition-list">
                        {{#conditions}}
                        <div class="condition">
                            <select name="conditions[{{@index}}][type]" required>
                                <option value="weight"><?php esc_html_e('Weight', 'mesmeric-commerce'); ?></option>
                                <option value="items"><?php esc_html_e('Items', 'mesmeric-commerce'); ?></option>
                                <option value="subtotal"><?php esc_html_e('Subtotal', 'mesmeric-commerce'); ?></option>
                            </select>
                            <select name="conditions[{{@index}}][operator]" required>
                                <option value="=="><?php esc_html_e('Equals', 'mesmeric-commerce'); ?></option>
                                <option value="!="><?php esc_html_e('Not equals', 'mesmeric-commerce'); ?></option>
                                <option value=">"><?php esc_html_e('Greater than', 'mesmeric-commerce'); ?></option>
                                <option value=">="><?php esc_html_e('Greater than or equal', 'mesmeric-commerce'); ?></option>
                                <option value="<"><?php esc_html_e('Less than', 'mesmeric-commerce'); ?></option>
                                <option value="<="><?php esc_html_e('Less than or equal', 'mesmeric-commerce'); ?></option>
                            </select>
                            <input type="number"
                                   step="0.01"
                                   name="conditions[{{@index}}][value]"
                                   value="{{value}}"
                                   required>
                            <button type="button" class="button remove-condition">×</button>
                        </div>
                        {{/conditions}}
                    </div>
                    <button type="button" class="button add-condition">
                        <?php esc_html_e('Add Condition', 'mesmeric-commerce'); ?>
                    </button>
                </div>

                <div class="form-field">
                    <label for="rule-action"><?php esc_html_e('Action', 'mesmeric-commerce'); ?></label>
                    <select id="rule-action" name="action" required>
                        <option value="add"><?php esc_html_e('Add amount', 'mesmeric-commerce'); ?></option>
                        <option value="subtract"><?php esc_html_e('Subtract amount', 'mesmeric-commerce'); ?></option>
                        <option value="multiply"><?php esc_html_e('Multiply by amount', 'mesmeric-commerce'); ?></option>
                        <option value="set"><?php esc_html_e('Set to amount', 'mesmeric-commerce'); ?></option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="rule-amount"><?php esc_html_e('Amount', 'mesmeric-commerce'); ?></label>
                    <input type="number"
                           step="0.01"
                           id="rule-amount"
                           name="amount"
                           value="{{amount}}"
                           required>
                </div>

                <div class="form-actions">
                    <button type="button" class="button cancel">
                        <?php esc_html_e('Cancel', 'mesmeric-commerce'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save', 'mesmeric-commerce'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</script>

<script type="text/template" id="tmpl-condition">
    <div class="condition">
        <select name="conditions[{{index}}][type]" required>
            <option value="weight"><?php esc_html_e('Weight', 'mesmeric-commerce'); ?></option>
            <option value="items"><?php esc_html_e('Items', 'mesmeric-commerce'); ?></option>
            <option value="subtotal"><?php esc_html_e('Subtotal', 'mesmeric-commerce'); ?></option>
        </select>
        <select name="conditions[{{index}}][operator]" required>
            <option value="=="><?php esc_html_e('Equals', 'mesmeric-commerce'); ?></option>
            <option value="!="><?php esc_html_e('Not equals', 'mesmeric-commerce'); ?></option>
            <option value=">"><?php esc_html_e('Greater than', 'mesmeric-commerce'); ?></option>
            <option value=">="><?php esc_html_e('Greater than or equal', 'mesmeric-commerce'); ?></option>
            <option value="<"><?php esc_html_e('Less than', 'mesmeric-commerce'); ?></option>
            <option value="<="><?php esc_html_e('Less than or equal', 'mesmeric-commerce'); ?></option>
        </select>
        <input type="number"
               step="0.01"
               name="conditions[{{index}}][value]"
               required>
        <button type="button" class="button remove-condition">×</button>
    </div>
</script>
