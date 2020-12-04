<?php
/**
 * Displays links for sharing wishlist on social networks
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/sharing.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$url = $wishlist->get_permalink();
$wishlist_title = $wishlist->get_title();
$desc = $wishlist->get_description() ? $wishlist->get_description() : apply_filters( 'nmgr_default_share_description', sprintf(
			/* translators: %s: wishlist type title */
			__( 'Here is the link to my %s:', 'nm-gift-registry-lite' ), esc_html( nmgr_get_type_title() ) ) );
$image = '';
?>


<div class="nmgr-sharing">

	<?php
	if ( $title ) {
		printf( '<h3 class="nmgr-template-title sharing">%s</h3>', esc_html( $title ) );
	}
	?>

	<div class="nmgr-sharing-options">
		<?php
		if ( nmgr_get_option( 'share_on_facebook' ) ) :
			$share_link = add_query_arg(
				array(
					'u' => rawurlencode( $url ),
					'p[title]' => rawurlencode( $wishlist_title ),
				), 'http://www.facebook.com/sharer/sharer.php' );
			?>
			<div class="share-item nmgr-share-on-facebook">
				<a target="_blank" href="<?php echo esc_url( $share_link ); ?>" title="<?php esc_attr_e( 'Click to share on facebook', 'nm-gift-registry-lite' ); ?>" class="nmgr-tip">
					<?php
					echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'icon' => 'facebook',
						'size' => 2.375,
						'fill' => '#ddd'
					) );
					?>
				</a>
			</div>
		<?php endif; ?>

		<?php
		if ( nmgr_get_option( 'share_on_twitter' ) ) :
			$share_link = add_query_arg(
				array(
					'url' => rawurlencode( $url ),
					'text' => rawurlencode( $desc ),
				), 'https://twitter.com/share' );
			?>
			<div class="share-item nmgr-share-on-twitter">
				<a target="_blank" href="<?php echo esc_url( $share_link ); ?>" title="<?php esc_attr_e( 'Click to share on twitter', 'nm-gift-registry-lite' ); ?>" class="nmgr-tip">
					<?php
					echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'icon' => 'twitter',
						'size' => 2.375,
						'fill' => '#ddd',
					) );
					?>
				</a>
			</div>
		<?php endif; ?>

		<?php
		if ( nmgr_get_option( 'share_on_pinterest' ) ) :
			$share_link = add_query_arg(
				array(
					'url' => rawurlencode( $url ),
					'description' => rawurlencode( $desc ),
				), 'http://pinterest.com/pin/create/button/' );

			if ( $image ) :
				$share_link = add_query_arg( 'media', rawurlencode( $image ), $share_link );
			endif;
			?>
			<div class="share-item nmgr-share-on-pinterest">
				<a target="_blank" href="<?php echo esc_url( $share_link ); ?>" title="<?php esc_attr_e( 'Click to share on pinterest', 'nm-gift-registry-lite' ); ?>" class="nmgr-tip">
					<?php
					echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'icon' => 'pinterest',
						'size' => 2.375,
						'fill' => '#ddd'
					) );
					?>
				</a>
			</div>
		<?php endif; ?>

		<?php
		if ( nmgr_get_option( 'share_on_email' ) ) :
			$share_link = add_query_arg(
				array(
					'subject' => rawurlencode( apply_filters( 'nmgr_share_on_email_subject', $wishlist_title ) ),
					'body' => rawurlencode( apply_filters( 'nmgr_share_on_email_body', $desc . ' ' . $url ) ),
					'title' => rawurlencode( $wishlist_title ),
				), 'mailto:' );
			?>
			<div class="share-item nmgr-share-on-email">
				<a target="_blank" href="<?php echo esc_url( $share_link ); ?>" title="<?php esc_attr_e( 'Click to share via email', 'nm-gift-registry-lite' ); ?>" class="nmgr-tip">
					<?php
					echo nmgr_get_svg( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'icon' => 'email',
						'size' => 2.375,
						'fill' => '#ddd'
					) );
					?>
				</a>
			</div>
		<?php endif; ?>

	</div>
</div>