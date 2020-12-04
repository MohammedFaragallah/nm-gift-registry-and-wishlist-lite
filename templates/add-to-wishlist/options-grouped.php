<?php
/**
 * Template for displaying the options for adding grouped products to the wishlist
 * in the add-to-wishlist dialog
 *
 * Options displayed:
 * - product quantity
 *
 * Used on single product pages
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/add-to-wishlist/options-grouped.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined( 'ABSPATH' ) || exit;


if ( isset( $grouped_products ) && !empty( $grouped_products ) ) :
	foreach ( $grouped_products as $grouped_product ) :
		if ( $grouped_product->is_purchasable() &&
			!$grouped_product->has_options() &&
			$grouped_product->is_in_stock() ) :
			?>
			<div class="option-row">
				<?php
				$quantity = nmgr_query_key( 'quantity' );
				$field_id = uniqid( "{$quantity}_{$grouped_product->get_id()}_" );
				?>
				<div class="nmgr-btn-group">
					<div class="nmgr-btn">
						<input type="checkbox"
									 value="1"
									 id="<?php echo esc_attr( $field_id ); ?>"
									 name="nmgr_qty[<?php echo esc_attr( $grouped_product->get_id() ); ?>]">
						<label for="<?php echo esc_attr( $field_id ); ?>"
									 class="title"
									 title="<?php
									 printf(
										 /* translators: %s: wishlist type title */
										 esc_html__( 'Add this item to my %s', 'nm-gift-registry-lite' ), esc_html( nmgr_get_type_title() ) );
									 ?>">
										 <?php echo esc_html( $grouped_product->get_name() ); ?>
						</label>
					</div>
				</div>
			</div>
			<?php
		endif;
	endforeach;
endif;