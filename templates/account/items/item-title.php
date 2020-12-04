<?php
/**
 * Wishlist item title
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-title.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.2
 */
defined( 'ABSPATH' ) || exit;
?>

<td class="name" data-title="<?php esc_attr_e( 'Product', 'nm-gift-registry-lite' ); ?>" data-sort-value="<?php echo esc_attr( $product->get_name() ); ?>">
	<?php
	echo $product_link ? '<a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $product->get_name() ) . '</a>' : '<div>' . wp_kses_post( $product->get_name() ) . '</div>';

	echo str_replace( array( '<p', '</p>' ), array( '<div', '</div>' ), wc_get_stock_html( $product ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( $product->get_sku() ) {
		echo '<div class="sku meta-item"><strong>' . esc_html__( 'SKU:', 'nm-gift-registry-lite' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>';
	}

	if ( !is_nmgr_wishlist() && $item->get_variation_id() ) {
		echo '<div class="variation-id meta-item"><strong>' . esc_html__( 'Variation ID:', 'nm-gift-registry-lite' ) . '</strong> ';
		if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
			echo esc_html( $item->get_variation_id() );
		} else {
			/* translators: %s: variation id */
			printf( esc_html__( '%s (No longer exists)', 'nm-gift-registry-lite' ), esc_html( $item->get_variation_id() ) );
		}
		echo '</div>';
	}

	if ( !empty( $item->get_variations_for_display() ) ) :
		?>
		<ul class="variations meta-item">
			<?php foreach ( $item->get_variations_for_display() as $variation ) :
				?>
				<li class="nmgr-item-variation <?php echo esc_attr( $variation[ 'key' ] ); ?>">
					<strong><?php echo wp_kses_post( $variation[ 'key' ] ); ?>:</strong>
					<?php echo wp_kses_post( force_balance_tags( $variation[ 'value' ] ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif;
	?>
	<input type="hidden" class="wishlist_item_id" name="wishlist_item_id[]" value="<?php echo esc_attr( $item->get_id() ); ?>" />


	<?php do_action( 'nmgr_items_table_title', $item, $item->get_wishlist() ); ?>
</td>