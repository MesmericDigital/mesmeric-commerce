<?php
/**
 * FAQ management page template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/faq/views
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="wrap">
    <h1><?php esc_html_e('FAQ Management', 'mesmeric-commerce'); ?></h1>

    <h2 class="nav-tab-wrapper mc-faq-tabs">
        <a href="#all" class="nav-tab nav-tab-active"><?php esc_html_e('All FAQs', 'mesmeric-commerce'); ?></a>
        <a href="#categories" class="nav-tab"><?php esc_html_e('Categories', 'mesmeric-commerce'); ?></a>
        <a href="#settings" class="nav-tab"><?php esc_html_e('Settings', 'mesmeric-commerce'); ?></a>
    </h2>

    <div id="all" class="tab-content active">
        <div class="mc-faq-header">
            <button type="button" class="button button-primary add-faq">
                <?php esc_html_e('Add FAQ', 'mesmeric-commerce'); ?>
            </button>
        </div>

        <table class="widefat mc-faq-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Question', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Category', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Products', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Actions', 'mesmeric-commerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $faqs = get_posts(array(
                    'post_type' => 'mc_faq',
                    'posts_per_page' => -1,
                ));

                foreach ($faqs as $faq):
                    $categories = wp_get_post_terms($faq->ID, 'mc_faq_category');
                    $products = get_post_meta($faq->ID, '_mc_faq_products', true);
                    ?>
                    <tr data-faq-id="<?php echo esc_attr($faq->ID); ?>">
                        <td>
                            <strong><?php echo esc_html($faq->post_title); ?></strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="#" class="edit-faq">
                                        <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="#" class="delete-faq">
                                        <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo implode(', ', array_map(function($term) {
                                return esc_html($term->name);
                            }, $categories));
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($products)) {
                                $product_names = array();
                                foreach ($products as $product_id) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        $product_names[] = $product->get_name();
                                    }
                                }
                                echo esc_html(implode(', ', $product_names));
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button edit-faq">
                                <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                            </button>
                            <button type="button" class="button delete-faq">
                                <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="categories" class="tab-content">
        <div class="mc-faq-header">
            <button type="button" class="button button-primary add-category">
                <?php esc_html_e('Add Category', 'mesmeric-commerce'); ?>
            </button>
        </div>

        <table class="widefat mc-category-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Description', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('FAQ Count', 'mesmeric-commerce'); ?></th>
                    <th><?php esc_html_e('Actions', 'mesmeric-commerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'mc_faq_category',
                    'hide_empty' => false,
                ));

                foreach ($categories as $category):
                    ?>
                    <tr data-category-id="<?php echo esc_attr($category->term_id); ?>">
                        <td>
                            <strong><?php echo esc_html($category->name); ?></strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="#" class="edit-category">
                                        <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="#" class="delete-category">
                                        <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html($category->description); ?></td>
                        <td><?php echo esc_html($category->count); ?></td>
                        <td>
                            <button type="button" class="button edit-category">
                                <?php esc_html_e('Edit', 'mesmeric-commerce'); ?>
                            </button>
                            <button type="button" class="button delete-category">
                                <?php esc_html_e('Delete', 'mesmeric-commerce'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="settings" class="tab-content">
        <form method="post" action="options.php" class="mc-faq-settings">
            <?php
            settings_fields('mc_faq_settings');
            do_settings_sections('mc_faq_settings');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mc_faq_per_page">
                            <?php esc_html_e('FAQs per Page', 'mesmeric-commerce'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               min="1"
                               id="mc_faq_per_page"
                               name="mc_faq_per_page"
                               value="<?php echo esc_attr(get_option('mc_faq_per_page', '10')); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Number of FAQs to display per page on the frontend.', 'mesmeric-commerce'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script type="text/template" id="tmpl-faq-modal">
    <div class="mc-modal">
        <div class="mc-modal-content">
            <h3><?php esc_html_e('{{id ? "Edit" : "Add"}} FAQ', 'mesmeric-commerce'); ?></h3>
            <form class="faq-form">
                <div class="form-field">
                    <label for="faq-question"><?php esc_html_e('Question', 'mesmeric-commerce'); ?></label>
                    <input type="text"
                           id="faq-question"
                           name="question"
                           value="{{question}}"
                           required>
                </div>

                <div class="form-field">
                    <label for="faq-answer"><?php esc_html_e('Answer', 'mesmeric-commerce'); ?></label>
                    <textarea id="faq-answer"
                              name="answer"
                              rows="5"
                              required>{{answer}}</textarea>
                </div>

                <div class="form-field">
                    <label for="faq-category"><?php esc_html_e('Category', 'mesmeric-commerce'); ?></label>
                    <select id="faq-category"
                            name="category[]"
                            multiple
                            class="regular-text">
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'mc_faq_category',
                            'hide_empty' => false,
                        ));
                        foreach ($categories as $category):
                            ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="faq-products"><?php esc_html_e('Products', 'mesmeric-commerce'); ?></label>
                    <select id="faq-products"
                            name="products[]"
                            multiple
                            class="regular-text">
                        <?php
                        $products = wc_get_products(array(
                            'status' => 'publish',
                            'limit' => -1,
                        ));
                        foreach ($products as $product):
                            ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </option>
                        <?php endforeach; ?>
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

<script type="text/template" id="tmpl-category-modal">
    <div class="mc-modal">
        <div class="mc-modal-content">
            <h3><?php esc_html_e('{{id ? "Edit" : "Add"}} Category', 'mesmeric-commerce'); ?></h3>
            <form class="category-form">
                <div class="form-field">
                    <label for="category-name"><?php esc_html_e('Name', 'mesmeric-commerce'); ?></label>
                    <input type="text"
                           id="category-name"
                           name="name"
                           value="{{name}}"
                           required>
                </div>

                <div class="form-field">
                    <label for="category-description"><?php esc_html_e('Description', 'mesmeric-commerce'); ?></label>
                    <textarea id="category-description"
                              name="description"
                              rows="3">{{description}}</textarea>
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
