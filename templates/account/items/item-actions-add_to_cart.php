<?php
/**
 * Wishlist item add to cart action
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-actions-add_to_cart.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

if ( $item->is_fulfilled() ) :
	?>
	<td class="actions add_to_cart">
		<?php echo nmgr_kses_post( apply_filters( 'nmgr_item_fulfilled_column_html', __( 'Fulfilled', 'nm-gift-registry-lite' ) ) ); ?>
	</td>
	<?php
	return;
endif;
?>

<td class="actions add_to_cart">
	<form class="cart nmgr-add-to-cart-form"
				action="<?php the_permalink(); ?>"
				method="post"
				enctype='multipart/form-data'>

		<?php
		woocommerce_quantity_input( array(
			'min_value' => $product->get_min_purchase_quantity(),
			'max_value' => $max_qty,
			), $product );
		?>

		<input type="hidden" name="add-to-cart" value="<?php echo absint( $item->get_product_id() ); ?>" />
		<input type="hidden" name="nmgr-add-to-cart-wishlist-item" value="<?php echo absint( $item->get_id() ); ?>" />
		<input type="hidden" name="nmgr-add-to-cart-wishlist" value="<?php echo absint( $wishlist->get_id() ); ?>" />

		<?php if ( 'variation' === $product->get_type() ) : ?>
			<input type="hidden" name="variation_id" value="<?php echo absint( $item->get_variation_id() ); ?>" />
			<?php
			if ( !empty( $item->get_variation() ) ) :
				foreach ( $item->get_variation() as $attribute_key => $value ) :
					?>
					<input type="hidden" name="<?php echo esc_attr( $attribute_key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
					<?php
				endforeach;
			endif;
		endif;
		?>

		<button type="submit" class="nmgr_add_to_cart_button nmgr_ajax_add_to_cart button alt">
			<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
		</button>
	</form>
</td>
