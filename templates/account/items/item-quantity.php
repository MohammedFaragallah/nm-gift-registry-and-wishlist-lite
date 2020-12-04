<?php
/**
 * Wishlist item desired quantity
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-quantity.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;
?>

<td class="quantity" data-title="<?php esc_attr_e( 'Quantity', 'nm-gift-registry-lite' ); ?>" data-sort-value="<?php echo esc_attr( $quantity ); ?>">
	<div class="view nmgr-tip" title="<?php esc_attr_e( 'Desired quantity', 'nm-gift-registry-lite' ); ?>">
		<?php echo esc_html( $quantity ); ?>
	</div>
	<div class="edit" style="display: none;">
		<input type="number"
					 step="1"
					 placeholder="0"
					 autocomplete="off"
					 size="4"
					 class="quantity"
					 value="<?php echo esc_attr( $quantity ); ?>"
					 data-qty="<?php echo esc_attr( $quantity ); ?>"
					 name="wishlist_item_qty[<?php echo absint( $item->get_id() ); ?>]"
					 min="<?php echo esc_attr( apply_filters( 'nmgr_quantity_input_min', $product->get_min_purchase_quantity(), $product ) ); ?>"
					 max="<?php echo esc_attr( apply_filters( 'nmgr_quantity_input_max', $product->get_stock_quantity(), $product ) ); ?>"
					 />
	</div>
</td>