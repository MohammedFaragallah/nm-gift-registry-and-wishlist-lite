<?php
/**
 * Form for enabling the wishlist module on a per user basis
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/form-enable-wishlist.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$checked = get_user_meta( get_current_user_id(), 'nmgr_enable_wishlist', true ) ? 'checked="checked"' : '';
?>

<div id="nmgr-enable-wishlist">
	<form method="post" id="nmgr-enable-wishlist-form">
		<label class="checkbox">
			<input type="checkbox" value="1" name="nmgr_enable_wishlist" id="nmgr-enable-wishlist" <?php echo esc_attr($checked); ?>>
			<?php
			/* translators: %s: wishlist type title */
			printf( esc_html__( 'Enable %s', 'nm-gift-registry-lite' ), esc_html(nmgr_get_type_title()) );
			?>
		</label>
		<?php wp_nonce_field( 'nmgr_enable_wishlist', 'nmgr-enable-wishlist-nonce' ); ?>
	</form>
</div>
