<?php
/**
 * Template for displaying items in user's wishlists in cart-like fashion
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry/cart.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="nmgr-cart" <?php echo isset( $template_data_atts ) ? $template_data_atts : ''; ?>>

	<?php
	if ( !$show_cart_contents_only ) {
		?>
		<a href="<?php echo esc_url( $url ); ?>"
			 title="<?php
			 printf(
				 /* translators %s: wishlist type title */
				 esc_attr__( 'View your %s items', 'nm-gift-registry-lite' ), nmgr_get_type_title()
			 );
			 ?>"
			 class="nmgr-show-cart-contents <?php echo $redirect ? 'redirect' : ''; ?>">
			<span class="nmgr-icon-toggle <?php echo 0 < ( int ) $cart_qty ? 'active' : ''; ?>">
				<?php
				if ( 1 > ( int ) $cart_qty ) {
					echo nmgr_kses_svg( nmgr_get_svg( array(
						'icon' => 'heart-empty',
						'size' => 2,
						'fill' => 'currentColor',
					) ) );
				}
				echo nmgr_kses_svg( nmgr_get_svg( array(
					'icon' => 'heart',
					'size' => 2,
					'fill' => 'currentColor',
					'class' => 'active',
				) ) );
				?>
			</span>
			<span class="count"><?php echo absint( $cart_qty ); ?></span>
		</a>
	<?php } ?>

	<?php if ( $show_cart_contents_only ) : ?>
		<div class="nmgr-cart-contents">
			<?php
			if ( empty( $wishlists ) || empty( $items_and_products ) ) {
				$pluralize = 1 < count( $wishlists ) ? true : false;
				$empty_cart_text = sprintf(
					/* translators %s: wishlist type title */
					esc_html__( 'There are currently no items in your %s.', 'nm-gift-registry-lite' ), nmgr_get_type_title( '', $pluralize )
				);
				echo nmgr_kses_post( apply_filters( 'nmgr_cart_empty_html', '<p>' . $empty_cart_text . '</p>' ) );
			} else {
				echo '<ul class="nmgr-cart-items">';

				$wishlists_displayed = 0;

				foreach ( $items_and_products as $item_and_product ) {
					$item = $item_and_product[ 'item' ];
					$product = $item_and_product[ 'product' ];

					$wishlists_displayed++;

					if ( 0 < $number_of_items && $number_of_items < $wishlists_displayed ) {
						break;
					}
					?>

					<li class="nmgr-cart-item">

						<?php if ( $show_item_image ) : ?>
							<div class="nmgr-cart-item-img">
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
									<?php
									$img_size = apply_filters( 'nmgr_cart_item_image_size', 'nmgr_thumbnail' );
									echo $product->get_image( $img_size, array(
										'title' => $product->get_name(),
										'alt' => $product->get_name(),
									) );
									?>
								</a>
							</div>
						<?php endif; ?>

						<div class="nmgr-cart-item-info">
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="nmgr-cart-item-title"><?php echo esc_html( $product->get_name() ); ?></a>

							<?php if ( $show_item_qty_cost ) : ?>
								<span class="nmgr-cart-item-qty-cost">
									<?php printf( '%s &times; %s', $item->get_quantity(), $product->get_price_html() ); ?>
								</span>
							<?php endif; ?>

							<?php
							if ( $show_item_availability ) {
								echo str_replace( array( '<p', '</p>' ),
									array( '<div', '</div>' ),
									wc_get_stock_html( $product )
								);
							}

							if ( $show_item_rating && wc_review_ratings_enabled() ) {
								echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() );
							}
							?>

							<?php if ( nmgr_get_option( 'allow_guest_wishlists' ) ) : ?>
								<span class="nmgr-cart-item-in-wishlist-text">
									<?php echo esc_html__( 'In:', 'nm-gift-registry-lite' ) . ' ' . nmgr_get_wishlist_link( $item->get_wishlist() ); ?>
								</span>
							<?php endif; ?>

							<?php do_action( 'nmgr_cart_item_info', $item, $item->get_wishlist(), $template_args, $wishlists ); ?>
						</div>

						<div class="nmgr-cart-item-actions">
							<a href="#"
								 class="nmgr-cart-item-remove"
								 data-wishlist-id="<?php echo $item->get_wishlist()->get_id(); ?>"
								 data-wishlist-item-id="<?php echo $item->get_id(); ?>"
								 aria-label="<?php echo $remove_item_text; ?>"
								 title="<?php echo $remove_item_text; ?>">
									 <?php
									 echo nmgr_get_svg( array(
										 'icon' => 'trash-can',
										 'fill' => '#ddd',
										 'sprite' => false,
										 'size' => 1.3,
									 ) );
									 ?>
							</a>

							<?php if ( $show_item_add_to_cart_button ) : ?>
								<a href="#"
									 class="nmgr-cart-item-add-to-cart"
									 aria-label="<?php echo $add_item_to_cart_text; ?>"
									 title="<?php echo $add_item_to_cart_text; ?>"
									 data-add-to-cart="<?php echo absint( $product->get_id() ); ?>"
									 data-quantity="<?php echo absint( $item->get_quantity() ); ?>"
									 data-variation_id="<?php echo absint( $item->get_variation_id() ); ?>"
									 data-nmgr-add-to-cart-wishlist-item="<?php echo absint( $item->get_id() ); ?>"
									 data-nmgr-add-to-cart-wishlist="<?php echo absint( $item->get_wishlist()->get_id() ); ?>"
									 <?php
									 if ( !empty( $item->get_variation() ) ) {
										 foreach ( $item->get_variation() as $attribute_key => $value ) {
											 echo 'data-' . esc_attr( $attribute_key ) . '="' . esc_attr( $value ) . '"';
										 }
									 }
									 ?>
									 >
										 <?php
										 echo nmgr_get_svg( array(
											 'icon' => 'cart-full',
											 'fill' => '#ddd',
											 'sprite' => false,
											 'size' => 1.48,
										 ) );
										 ?>
								</a>
							<?php endif; ?>
						</div>

					</li>
					<?php
				}
				echo '</ul>';
				?>

				<div class="nmgr-cart-info">

					<?php if ( $show_total_quantity ) : ?>
						<p class="nmgr-cart-quantity">
							<?php
							printf(
								/* translators: %d: quantity of items in wishlist cart */
								_n( '%d item', '%d items in total', $cart_qty, 'nm-gift-registry-lite' ),
								$cart_qty
							);
							?>
						</p>
					<?php endif; ?>

					<?php if ( $show_total_cost ) : ?>
						<p class="nmgr-cart-total"><strong><?php _e( 'Subtotal', 'nm-gift-registry-lite' ); ?>:</strong> <?php echo wc_price( $cart_total ); ?></p>
					<?php endif; ?>

					<?php do_action( 'nmgr_cart_info', $wishlists, $template_args ); ?>

					<?php if ( $show_manage_button ) : ?>
						<p class="nmgr-cart-manage"><a href="<?php echo esc_url( nmgr_get_account_url() ); ?>" class="button"><?php esc_html_e( 'Manage', 'nm-gift-registry-lite' ); ?></a></p>
						<?php endif; ?>

				</div>
				<?php
			}
			?>
		</div><!--- .nmgr-cart-contents -->
	<?php endif; ?>
</div>
