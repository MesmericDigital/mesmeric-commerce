<?php
/**
 * Template view file
 *
 * This is a template for creating module views. Customize this file for your module's frontend output.
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/template/views
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract variables from the shortcode attributes
$id = !empty($atts['id']) ? ' id="' . esc_attr($atts['id']) . '"' : '';
$class = !empty($atts['class']) ? ' ' . esc_attr($atts['class']) : '';
?>

<div<?php echo $id; ?> class="mc-template<?php echo $class; ?>">
    <div class="mc-template__content">
        <?php if (!empty($content)) : ?>
            <div class="mc-template__custom-content">
                <?php echo wp_kses_post($content); ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('Template module content. Replace this with your custom content.', 'mesmeric-commerce'); ?></p>
        <?php endif; ?>
    </div>
</div>
