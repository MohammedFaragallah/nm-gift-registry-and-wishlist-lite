<?php
/**
 * Wishlist item cost per quantity
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-cost.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;
?>

<td class="cost" data-title="<?php esc_attr_e( 'Cost', 'nm-gift-registry-lite' ); ?>" data-sort-value="<?php echo esc_attr( $product->get_price() ); ?>">
	<div class="view nmgr-tip" title="<?php esc_attr_e( 'Cost per item', 'nm-gift-registry-lite' ); ?>">
		<?php
		echo wc_price( $product->get_price(), array( 'currency' => get_woocommerce_currency() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</td>