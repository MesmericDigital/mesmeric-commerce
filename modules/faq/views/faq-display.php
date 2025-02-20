<?php
/**
 * FAQ display template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/faq/views
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$category_id = isset($args['category_id']) ? (int) $args['category_id'] : 0;
$product_id  = isset($args['product_id']) ? (int) $args['product_id'] : 0;

// Get FAQs based on category or product
$query_args = array(
    'post_type'      => 'mc_faq',
    'posts_per_page' => get_option('mc_faq_per_page', 10),
    'orderby'        => 'menu_order',
	'order'          => 'ASC',
);

if ($category_id) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'mc_faq_category',
			'field'    => 'term_id',
			'terms'    => $category_id,
		),
	);
} elseif ($product_id) {
	$query_args['meta_query'] = array(
		array(
			'key'     => '_mc_faq_products',
			'value'   => sprintf('"%s"', $product_id),
			'compare' => 'LIKE',
		),
	);
}

$faqs = get_posts($query_args);
?>

<div class="mc-faq-wrapper" x-data="{ activeItem: null }">
	<?php if ( ! $category_id && ! $product_id) : ?>
		<div class="mc-faq-categories">
			<h3><?php esc_html_e('FAQ Categories', 'mesmeric-commerce'); ?></h3>
			<ul>
				<?php
				$categories = get_terms(
					array(
					'taxonomy'   => 'mc_faq_category',
					'hide_empty' => true,
				)
					);

				foreach ($categories as $category) :
					?>
					<li>
						<a href="<?php echo esc_url(add_query_arg('faq_category', $category->term_id )); ?>">
							<?php echo esc_html( $category->name ); ?>
							<span class="count">(<?php echo esc_html( $category->count ); ?>)</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $category_id ) : ?>
		<?php
		$category = get_term( $category_id, 'mc_faq_category' );
		if ( $category ) :
			?>
			<div class="mc-faq-category-header">
				<h2><?php echo esc_html( $category->name ); ?></h2>
				<?php if ( $category->description ) : ?>
					<p class="description"><?php echo esc_html( $category->description ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $product_id ) : ?>
		<div class="mc-faq-product-header">
			<h2><?php esc_html_e( 'Product FAQs', 'mesmeric-commerce' ); ?></h2>
			<p class="description">
				<?php
				$product = wc_get_product( $product_id );
				if ( $product ) {
					printf(
						/* translators: %s: product name */
						esc_html__( 'Frequently asked questions about %s', 'mesmeric-commerce' ),
						esc_html( $product->get_name() )
					);
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $faqs ) : ?>
		<div class="mc-faq-items">
			<?php foreach ( $faqs as $index => $faq ) : ?>
				<div class="mc-faq-item" x-data="{ open: false }">
					<div class="mc-faq-question"
						@click="open = !open"
						:class="{ 'active': open }">
						<h3><?php echo esc_html( $faq->post_title ); ?></h3>
						<span class="mc-faq-toggle" :class="{ 'active': open }">
							<svg xmlns="http://www.w3.org/2000/svg"
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round">
								<line x1="12" y1="5" x2="12" y2="19"></line>
								<line x1="5" y1="12" x2="19" y2="12"></line>
							</svg>
						</span>
					</div>
					<div class="mc-faq-answer"
						x-show="open"
						x-transition:enter="transition ease-out duration-200"
						x-transition:enter-start="opacity-0 transform -translate-y-2"
						x-transition:enter-end="opacity-100 transform translate-y-0"
						x-transition:leave="transition ease-in duration-200"
						x-transition:leave-start="opacity-100 transform translate-y-0"
						x-transition:leave-end="opacity-0 transform -translate-y-2">
						<?php echo wp_kses_post( $faq->post_content ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="mc-faq-empty">
			<?php esc_html_e( 'No FAQs found.', 'mesmeric-commerce' ); ?>
		</p>
	<?php endif; ?>
</div>
