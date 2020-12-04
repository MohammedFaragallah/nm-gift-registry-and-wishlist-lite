<?php
/**
 * Wishlist items actions (Save)
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/items-actions.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="after-table-row">
	<p>
		<?php do_action( 'nmgr_after_items_actions', $items, $wishlist, $items_args ); ?>
	</p>
</div>