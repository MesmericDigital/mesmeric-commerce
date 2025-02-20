<?php
/**
 * Inventory management page template
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/inventory/views
 */

if (  ! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly
}

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Inventory Management', 'mesmeric-commerce' ); ?></h1>

    <h2 class="nav-tab-wrapper mc-inventory-tabs">
        <a href="#overview" class="nav-tab nav-tab-active"><?php esc_html_e( 'Overview', 'mesmeric-commerce' ); ?></a>
        <a href="#low-stock" class="nav-tab"><?php esc_html_e( 'Low Stock', 'mesmeric-commerce' ); ?></a>
        <a href="#reorder" class="nav-tab"><?php esc_html_e( 'Reorder Points', 'mesmeric-commerce' ); ?></a>
        <a href="#settings" class="nav-tab"><?php esc_html_e( 'Settings', 'mesmeric-commerce' ); ?></a>
    </h2>

    <div id="overview" class="tab-content active">
        <div class="mc-inventory-stats">
            <div class="mc-inventory-stat">
                <h3><?php esc_html_e( 'Total Products', 'mesmeric-commerce' ); ?></h3>
                <?php
                $total_products = wp_count_posts( 'product' );
                echo esc_html( $total_products->publish );
                ?>
            </div>
            <div class="mc-inventory-stat">
                <h3><?php esc_html_e( 'Out of Stock', 'mesmeric-commerce' ); ?></h3>
				<?php
				$out_of_stock = wc_get_products(
					array(
					'status'       => 'publish',
					'stock_status' => 'outofstock',
					'return'       => 'ids',
				)
					);
				echo esc_html( count( $out_of_stock ) );
				?>
			</div>
			<div class="mc-inventory-stat">
				<h3><?php esc_html_e( 'Low Stock', 'mesmeric-commerce' ); ?></h3>
				<?php
				$low_stock = wc_get_products(
					array(
					'status'       => 'publish',
					'low_in_stock' => true,
					'return'       => 'ids',
				)
					);
				echo esc_html( count( $low_stock ) );
				?>
			</div>
		</div>

		<div class="mc-inventory-recent">
			<h3><?php esc_html_e( 'Recent Stock Changes', 'mesmeric-commerce' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Old Stock', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'New Stock', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Date', 'mesmeric-commerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$recent_changes = get_option( 'mc_recent_stock_changes', array() );
					foreach ( $recent_changes as $change ) :
						$product = wc_get_product( $change['product_id'] );
						if (  ! $product) {
							continue;
						}
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $change['product_id'] ) ); ?>">
									<?php echo esc_html( $product->get_name() ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $change['old_stock'] ); ?></td>
							<td><?php echo esc_html( $change['new_stock'] ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), $change['date'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div id="low-stock" class="tab-content">
		<div class="mc-inventory-low-stock">
			<h3><?php esc_html_e( 'Low Stock Products', 'mesmeric-commerce' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'SKU', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Current Stock', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Threshold', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'mesmeric-commerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$low_stock_products = wc_get_products(
						array(
						'status'       => 'publish',
						'low_in_stock' => true,
					)
						);
					foreach ( $low_stock_products as $product ) :
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>">
									<?php echo esc_html( $product->get_name() ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $product->get_sku() ); ?></td>
							<td><?php echo esc_html( $product->get_stock_quantity() ); ?></td>
							<td><?php echo esc_html( $product->get_low_stock_amount() ); ?></td>
							<td>
								<button type="button"
										class="button update-stock"
										data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
									<?php esc_html_e( 'Update Stock', 'mesmeric-commerce' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div id="reorder" class="tab-content">
		<div class="mc-inventory-reorder">
			<h3><?php esc_html_e( 'Products to Reorder', 'mesmeric-commerce' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'SKU', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Current Stock', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Reorder Point', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Suggested Order', 'mesmeric-commerce' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'mesmeric-commerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$products = wc_get_products(
						array(
						'status'       => 'publish',
						'manage_stock' => true,
					)
						);
					foreach ( $products as $product ) :
						$reorder_point  = (int) $product->get_meta( '_mc_reorder_point' );
						$stock_quantity = (int) $product->get_stock_quantity();
						if ($stock_quantity > $reorder_point) {
							continue;
						}

						$optimal_stock   = (int) get_option( 'mc_inventory_optimal_stock', 10 );
						$suggested_order = max( 0, $optimal_stock - $stock_quantity );
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>">
									<?php echo esc_html( $product->get_name() ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $product->get_sku() ); ?></td>
							<td><?php echo esc_html( $stock_quantity ); ?></td>
							<td><?php echo esc_html( $reorder_point ); ?></td>
							<td><?php echo esc_html( $suggested_order ); ?></td>
							<td>
								<button type="button"
										class="button update-stock"
										data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
									<?php esc_html_e( 'Update Stock', 'mesmeric-commerce' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div id="settings" class="tab-content">
		<form method="post" action="options.php" class="mc-inventory-settings">
			<?php
			settings_fields( 'mc_inventory_settings' );
			do_settings_sections( 'mc_inventory_settings' );
			?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="mc_inventory_low_threshold">
							<?php esc_html_e( 'Low Stock Threshold', 'mesmeric-commerce' ); ?>
						</label>
					</th>
					<td>
						<input type="number"
								min="0"
								id="mc_inventory_low_threshold"
								name="mc_inventory_low_threshold"
								value="<?php echo esc_attr( get_option( 'mc_inventory_low_threshold', '5' ) ); ?>"
								class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Default low stock threshold for all products.', 'mesmeric-commerce' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="mc_inventory_notification_email">
							<?php esc_html_e( 'Notification Email', 'mesmeric-commerce' ); ?>
						</label>
					</th>
					<td>
						<input type="email"
								id="mc_inventory_notification_email"
								name="mc_inventory_notification_email"
								value="<?php echo esc_attr( get_option( 'mc_inventory_notification_email', get_option( 'admin_email' ) ) ); ?>"
								class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Email address for inventory notifications.', 'mesmeric-commerce' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="mc_inventory_optimal_stock">
							<?php esc_html_e( 'Optimal Stock Level', 'mesmeric-commerce' ); ?>
						</label>
					</th>
					<td>
						<input type="number"
								min="0"
								id="mc_inventory_optimal_stock"
								name="mc_inventory_optimal_stock"
								value="<?php echo esc_attr( get_option( 'mc_inventory_optimal_stock', '10' ) ); ?>"
								class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Target stock level for reorder calculations.', 'mesmeric-commerce' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="mc_default_lead_time">
							<?php esc_html_e( 'Default Lead Time (Days)', 'mesmeric-commerce' ); ?>
						</label>
					</th>
					<td>
						<input type="number"
								min="1"
								id="mc_default_lead_time"
								name="mc_default_lead_time"
								value="<?php echo esc_attr( get_option( 'mc_default_lead_time', '7' ) ); ?>"
								class="regular-text">
						<p class="description">
							<?php esc_html_e( 'Default lead time in days for reorder calculations.', 'mesmeric-commerce' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>

<script type="text/template" id="tmpl-stock-modal">
	<div class="mc-modal">
		<div class="mc-modal-content">
			<h3><?php esc_html_e( 'Update Stock', 'mesmeric-commerce' ); ?></h3>
			<form class="stock-form">
				<div class="form-field">
					<label for="stock-quantity"><?php esc_html_e( 'New Stock Quantity', 'mesmeric-commerce' ); ?></label>
					<input type="number"
							min="0"
							id="stock-quantity"
							name="quantity"
							value="{{stock_quantity}}"
							required>
				</div>

				<div class="form-field">
					<label for="stock-note"><?php esc_html_e( 'Note', 'mesmeric-commerce' ); ?></label>
					<textarea id="stock-note"
								name="note"
								rows="3"></textarea>
				</div>

				<div class="form-actions">
					<button type="button" class="button cancel">
						<?php esc_html_e( 'Cancel', 'mesmeric-commerce' ); ?>
					</button>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Update', 'mesmeric-commerce' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</script>
