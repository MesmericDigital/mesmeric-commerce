<?php
/**
 * Breakdance Integration Class
 *
 * @package MesmericCommerce
 */

declare(strict_types=1);

namespace MesmericCommerce\Includes;

/**
 * Class BreakdanceIntegration
 *
 * Handles all Breakdance-specific integrations and customizations
 */
class BreakdanceIntegration {

    /**
     * Initialize the integration
     *
     * @return void
     */
    public function init(): void {
        // Register hooks
        add_action('init', [$this, 'register_dynamic_data_fields']);
        add_action('init', [$this, 'register_form_actions']);
        add_action('breakdance_register_template_types_and_conditions', [$this, 'register_conditions']);
        add_filter('breakdance_singular_content', [$this, 'filter_singular_content']);
        add_action('breakdance_form_start', [$this, 'customize_form_header']);
        add_action('breakdance_form_end', [$this, 'customize_form_footer']);
        add_filter('breakdance_element_classnames_for_html_class_attribute', [$this, 'add_custom_classes']);
        add_filter('breakdance_shape_dividers', [$this, 'register_custom_dividers']);
    }

    /**
     * Register dynamic data fields
     *
     * @return void
     */
    public function register_dynamic_data_fields(): void {
        if (!function_exists('\Breakdance\DynamicData\registerField') ||
            !class_exists('\Breakdance\DynamicData\Field')) {
            return;
        }

        // TODO: Register dynamic data fields
        // Example:
        // \Breakdance\DynamicData\registerField(new MyDynamicField());
    }

    /**
     * Register form actions
     *
     * @return void
     */
    public function register_form_actions(): void {
        if (!function_exists('\Breakdance\Forms\Actions\registerAction') ||
            !class_exists('\Breakdance\Forms\Actions\Action')) {
            return;
        }

        // TODO: Register form actions
        // Example:
        // \Breakdance\Forms\Actions\registerAction(new MyFormAction());
    }

    /**
     * Register conditions for element display
     *
     * @return void
     */
    public function register_conditions(): void {
        if (!class_exists('\Breakdance\ConditionsAPI')) {
            return;
        }

        // TODO: Register conditions
        // Example:
        /*
        \Breakdance\ConditionsAPI\register([
            'supports' => ['element_display'],
            'slug' => 'mesmeric-condition',
            'label' => 'Mesmeric Condition',
            'category' => 'Mesmeric Commerce',
            'operands' => ['equals', 'not equals'],
            'callback' => [$this, 'check_condition'],
        ]);
        */
    }

    /**
     * Filter singular content
     *
     * @param string $content The content to filter.
     * @return string
     */
    public function filter_singular_content(string $content): string {
        // Add custom content filtering logic here
        return $content;
    }

    /**
     * Customize form header
     *
     * @param array $settings Form settings.
     * @return void
     */
    public function customize_form_header(array $settings): void {
        if (!empty($settings['form']['form_name'])) {
            echo wp_kses_post(
                sprintf(
                    '<div class="breakdance-form-header"><h4>%s</h4></div>',
                    esc_html($settings['form']['form_name'])
                )
            );
        }
    }

    /**
     * Customize form footer
     *
     * @param array $settings Form settings.
     * @return void
     */
    public function customize_form_footer(array $settings): void {
        echo wp_kses_post(
            '<div class="breakdance-form-footer">' .
            __('By submitting this form you agree to our terms and conditions.', 'mesmeric-commerce') .
            '</div>'
        );
    }

    /**
     * Add custom classes to elements
     *
     * @param array $classNames Array of class names.
     * @return array
     */
    public function add_custom_classes(array $classNames): array {
        $classNames[] = 'mesmeric-element';
        return $classNames;
    }

    /**
     * Register custom shape dividers
     *
     * @param array $dividers Array of dividers.
     * @return array
     */
    public function register_custom_dividers(array $dividers): array {
        // Add custom dividers
        // Example:
        /*
        $dividers[] = [
            'text' => 'Mesmeric Wave',
            'value' => file_get_contents(__DIR__ . '/../assets/dividers/wave.svg')
        ];
        */

        return $dividers;
    }
}
