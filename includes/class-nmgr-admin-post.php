<?php
defined( 'ABSPATH' ) || exit;

/**
 * Actions related to NM Gift Registry admin post edit screen
 */
class NMGR_Admin_Post {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Meta box notices
	 *
	 * @var array
	 */
	public static $notices = array();

	public static function run() {
		if ( !is_nmgr_admin_request() ) {
			return;
		}

		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_screen_id' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'enter_title_here' ), 1, 2 );
		add_action( 'edit_form_after_title', array( __CLASS__, 'edit_form_after_title' ) );
		add_action( 'admin_print_scripts', array( __CLASS__, 'disable_autosave' ) );
		add_filter( 'post_updated_messages', array( __CLASS__, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( __CLASS__, 'bulk_post_updated_messages' ), 10, 2 );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'change_priority' ) );
		add_filter( 'nmgr_fields', array( __CLASS__, 'modify_fields_args' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 20 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'insert_post_data' ), 10, 2 );
		add_action( 'save_post_' . nmgr()->post_type, array( __CLASS__, 'save_meta_boxes' ), 1, 2 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_output_notices' ) );
		add_action( 'shutdown', array( __CLASS__, 'save_notices' ) );
		add_action( 'nmgr_untrashed_wishlist', array( __CLASS__, 'enable_wishlist_for_user' ) );
		add_action( 'nmgr_created_wishlist', array( __CLASS__, 'enable_wishlist_for_user' ) );
		add_action( 'nmgr_updated_wishlist', array( __CLASS__, 'enable_wishlist_for_user' ) );
		add_action( 'admin_footer', array( __CLASS__, 'add_products_template' ) );
	}

	/**
	 * Set the nm_gift_registry post type admin page as a woocommerce admin page
	 * (lazily just so that woocommerce can enqueue its admin styles for our form fields)
	 */
	public static function add_screen_id( $screen_ids ) {
		$screen_ids[] = nmgr()->post_type;
		return $screen_ids;
	}

	public static function enter_title_here( $text, $post ) {
		if ( is_nmgr_post( $post ) ) {
			/* translators: %s: wishlist type title */
			$text = sprintf( __( '%s title', 'nm-gift-registry-lite' ), nmgr_get_type_title( 'cf' ) );
		}
		return $text;
	}

	public static function edit_form_after_title( $post ) {
		if ( is_nmgr_post( $post ) && 'no' !== nmgr_get_option( 'display_form_description' ) ) {
			$form = new NMGR_Form( $post->ID );
			echo $form->get_fields_html( array( 'description' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Disable the auto-save functionality for Wishlists.
	 */
	public static function disable_autosave() {
		if ( is_nmgr_post() ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	public static function post_updated_messages( $messages ) {
		global $post;

		$messages[ nmgr()->post_type ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Wishlist updated.', 'nm-gift-registry-lite' ),
			2 => __( 'Custom field updated.', 'nm-gift-registry-lite' ),
			3 => __( 'Custom field deleted.', 'nm-gift-registry-lite' ),
			4 => __( 'Wishlist updated.', 'nm-gift-registry-lite' ),
			5 => __( 'Revision restored.', 'nm-gift-registry-lite' ),
			6 => __( 'Wishlist updated.', 'nm-gift-registry-lite' ),
			7 => __( 'Wishlist saved.', 'nm-gift-registry-lite' ),
			8 => __( 'Wishlist submitted.', 'nm-gift-registry-lite' ),
			9 => sprintf(
				/* translators: %s: date */
				__( 'Wishlist scheduled for: %s.', 'nm-gift-registry-lite' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'nm-gift-registry-lite' ), strtotime( $post->post_date ) ) . '</strong>'
			),
			10 => __( 'Wishlist draft updated.', 'nm-gift-registry-lite' ),
			11 => __( 'Wishlist updated and sent.', 'nm-gift-registry-lite' ),
		);

		return $messages;
	}

	public static function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages[ nmgr()->post_type ] = array(
			/* translators: %s: wishlist count */
			'updated' => _n( '%s wishlist updated.', '%s wishlists updated.', $bulk_counts[ 'updated' ], 'nm-gift-registry-lite' ),
			/* translators: %s: wishlist count */
			'locked' => _n( '%s wishlist not updated, somebody is editing it.', '%s wishlists not updated, somebody is editing them.', $bulk_counts[ 'locked' ], 'nm-gift-registry-lite' ),
			/* translators: %s: wishlist count */
			'deleted' => _n( '%s wishlist permanently deleted.', '%s wishlists permanently deleted.', $bulk_counts[ 'deleted' ], 'nm-gift-registry-lite' ),
			/* translators: %s: wishlist count */
			'trashed' => _n( '%s wishlist moved to the Trash.', '%s wishlists moved to the Trash.', $bulk_counts[ 'trashed' ], 'nm-gift-registry-lite' ),
			/* translators: %s: wishlist count */
			'untrashed' => _n( '%s wishlist restored from the Trash.', '%s wishlists restored from the Trash.', $bulk_counts[ 'untrashed' ], 'nm-gift-registry-lite' ),
		);

		return $bulk_messages;
	}

	/**
	 * Change priority of woocommerce shipping fields specially for admin page
	 *
	 * @param array $fields Woocommerce default address fields.
	 * @return array Woocommerce default address fields
	 */
	public static function change_priority( $fields ) {
		if ( !is_nmgr_admin() ) {
			return $fields;
		}

		// Set state field to be after country field.
		$fields[ 'state' ][ 'priority' ] = 45;

		// Remove required attribute from all fields.
		foreach ( $fields as $key => $args ) {
			$fields[ $key ][ 'required' ] = false;
		}

		return $fields;
	}

	/**
	 * Modify field arguments for plugin fields specially for admin page
	 *
	 * @param type $fields Plugin form fields.
	 * @return array Modified fields
	 */
	public static function modify_fields_args( $fields ) {
		if ( !is_nmgr_admin() ) {
			return $fields;
		}

		foreach ( $fields as $key => $args ) {

			// Remove required attribute from all fields
			$fields[ $key ][ 'required' ] = false;

			switch ( $key ) {
				case 'nmgr_description':
				case 'nmgr_event_date':
					unset( $fields[ $key ][ 'label' ] );
					break;
				case 'nmgr_ship_to_account_address':
					$fields[ $key ][ 'cbvalue' ] = 1; // allow field to be checked with woocommerce_wp_checkbox()
					$fields[ $key ][ 'label' ] = sprintf(
						/* translators: %s: wishlist type title */
						__( 'Ship %s items to user\'s account shipping address', 'nm-gift-registry-lite' ), nmgr_get_type_title()
					);
					break;
				case 'shipping_country':
					$fields[ $key ][ 'class' ] = array( 'form-row-first' );
					break;
				case 'shipping_state':
					$fields[ $key ][ 'class' ] = array( 'form-row-last' );
					break;
				case 'shipping_address_1':
					$fields[ $key ][ 'class' ] = array( 'form-row-first' );
					$fields[ $key ][ 'label' ] = __( 'Street address 1', 'nm-gift-registry-lite' );
					break;
				case 'shipping_address_2':
					$fields[ $key ][ 'class' ] = array( 'form-row-last' );
					$fields[ $key ][ 'label' ] = __( 'Street address 2', 'nm-gift-registry-lite' );
					break;
				case 'shipping_city':
					$fields[ $key ][ 'class' ] = array( 'form-row-first' );
					break;
				case 'shipping_postcode':
					$fields[ $key ][ 'class' ] = array( 'form-row-last' );
					break;
			}
		}
		return $fields;
	}

	/**
	 * Add a notice
	 *
	 * @param string $text The notice
	 * @param string $notice_type The type of notice. Should be success, error, or notice. Default is notice.
	 */
	private static function add_notice( $text, $notice_type = 'notice' ) {
		global $pagenow;

		if ( 'post.php' === $pagenow ) {
			self::$notices[] = array(
				'message' => $text,
				'type' => $notice_type
			);
		}
	}

	/**
	 * Save notices to an option.
	 */
	public static function save_notices() {
		if ( !empty( self::$notices ) ) {
			update_option( 'nmgr_metabox_notices', self::$notices );
		}
	}

	public static function maybe_output_notices() {
		$notices = array_filter( ( array ) get_option( 'nmgr_metabox_notices' ) );
		if ( !empty( $notices ) ) {
			self::$notices = $notices;

			delete_option( 'nmgr_metabox_notices' );

			add_action( 'admin_notices', array( __CLASS__, 'output_notices' ) );
		}
	}

	/**
	 * Show any stored messages.
	 */
	public static function output_notices() {
		if ( empty( self::$notices ) ) {
			return;
		}

		foreach ( self::$notices as $notice ) {
			if ( !is_array( $notice ) || !isset( $notice[ 'message' ], $notice[ 'type' ] ) || !in_array( $notice[ 'type' ], array( 'success', 'error', 'notice' ) ) ) {
				continue;
			}

			switch ( $notice[ 'type' ] ) {
				case 'error':
					echo '<div class="error notice is-dismissible"><p>' . wp_kses_post( $notice[ 'message' ] ) . '</p></div>';
					break;
				case 'notice':
					echo '<div class="notice-info notice is-dismissible"><p>' . wp_kses_post( $notice[ 'message' ] ) . '</p></div>';
					break;
				case 'success':
					echo '<div class="updated notice is-dismissible"><p>' . wp_kses_post( $notice[ 'message' ] ) . '</p></div>';
					break;
			}
		}

		self::$notices = array();
	}

	public static function add_meta_boxes() {
		add_meta_box( 'nm_gift_registry-profile', __( 'Profile', 'nm-gift-registry-lite' ), array( __CLASS__, 'profile_metabox' ), nmgr()->post_type, 'normal', 'high' );
		add_meta_box( 'nm_gift_registry-items', __( 'Items', 'nm-gift-registry-lite' ), array( __CLASS__, 'items_metabox' ), nmgr()->post_type, 'normal', 'high' );

		if ( nmgr_get_option( 'display_form_event_date' ) !== 'no' ) {
			add_meta_box( 'nm_gift_registry-event-date', __( 'Event Date', 'nm-gift-registry-lite' ), array( __CLASS__, 'event_date_metabox' ), nmgr()->post_type, 'side', 'default' );
		}
	}

	public static function remove_meta_boxes() {
		if ( is_nmgr_post() || post_type_supports( nmgr()->post_type, 'comments' ) ) {
			remove_meta_box( 'commentsdiv', nmgr()->post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', nmgr()->post_type, 'side' );
			remove_meta_box( 'commentstatusdiv', nmgr()->post_type, 'normal' );
		}
	}

	public static function profile_metabox( $post ) {
		$form = new NMGR_Form( $post->ID );
		$user = '';
		$user_id = '';
		$user_string = '';
		$account_shipping = '';
		$enable_shipping = nmgr_get_option( 'enable_shipping' );

		// If the post is not a new post and we have a post author, get his user details and account shipping details
		if ( ('auto-draft' !== $post->post_status) && get_post_meta( $post->ID, '_nmgr_user_id', true ) ) {
			$user_id = get_post_meta( $post->ID, '_nmgr_user_id', true );
			$user_string = __( 'Guest', 'nm-gift-registry-lite' );

			if ( is_numeric( $user_id ) ) {
				$user = new WC_Customer( $form->get_wishlist()->get_user_id() );
				$user_string = sprintf(
					esc_html( '%1$s (%2$s)' ),
					$user->get_display_name(),
					$user->get_email()
				);

				$account_shipping = WC()->countries->get_formatted_address( $user->get_shipping() );
			}
		}

		$profile_fields = $form->get_fields_html(
			array(
				'first_name',
				'last_name',
				'partner_first_name',
				'partner_last_name',
				'email',
			), '', __( 'User Details', 'nm-gift-registry-lite' ) );
		$has_profile_fields = $form->has_fields();

		$ship_to_account = $form->get_fields_html( array( 'ship_to_account_address' ), '', __( 'Shipping Details', 'nm-gift-registry-lite' ), false );
		$has_ship_to_account = $form->has_fields();

		$wishlist_shipping = $form->get_fields_html( 'shipping' );
		$has_wishlist_shipping = $form->has_fields();

		$class = $has_profile_fields &&
			$enable_shipping &&
			(true === $has_ship_to_account || true === $has_wishlist_shipping) ? 'two-col' : '';

		// output the wishlist id and our own nonce
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $form->get_fields_html( array( 'wishlist_id' ) );
		echo $form->get_nonce();
		// phpcs:enable
		?>

		<p class="nmgr-user">
			<label for="nmgr_user_id"> <?php esc_html_e( 'User:', 'nm-gift-registry-lite' ); ?> </label>
			<select class="nmgr-user-search"
							id="nmgr_user_id"
							name="nmgr_user_id"
							data-placeholder="<?php
							/* translators: %s: wishlist type title */
							printf( esc_attr__( 'Enter name of %s owner', 'nm-gift-registry-lite' ), esc_html( nmgr_get_type_title() ) );
							?>"
							data-allow_clear="true">
				<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $user_string ) ); ?></option>
			</select>
		</p>

		<div class="wishlist-columns <?php echo esc_attr( $class ); ?>">

			<?php if ( $has_profile_fields ) : ?>
				<div class='column'><?php echo $profile_fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped                               ?></div>
			<?php endif; ?>

			<?php if ( $enable_shipping && ($has_ship_to_account || $has_wishlist_shipping) ) : ?>
				<div class='column'>
					<?php
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $has_ship_to_account ? $ship_to_account : '';
					echo $account_shipping ? "<p class='account-shipping-address'>$account_shipping</p>" : '';
					echo $has_wishlist_shipping ? "<div class='wishlist-shipping-address'>$wishlist_shipping</div>" : '';
					// phpcs:enable
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function event_date_metabox( $post ) {
		$form = new NMGR_Form( $post->ID );
		echo $form->get_fields_html( array( 'event_date' ), '', '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function items_metabox( $post ) {
		nmgr_get_items_template( $post->ID, true );
	}

	public static function add_products_template() {
		global $pagenow;

		if ( is_nmgr_admin() && 'edit.php' !== $pagenow ) :
			$prod_text = esc_html__( 'Product', 'nm-gift-registry-lite' );
			$qty_text = esc_html__( 'Quantity', 'nm-gift-registry-lite' );
			?>
			<div id="nmgr-add-items-modal" class="modal fade" role="dialog" aria-labelledby="nmgr-aim-label" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered nmgr-add-items">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title"><?php esc_html_e( 'Add item(s)', 'nm-gift-registry-lite' ); ?></h4>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
						</div>
						<div class="modal-body">
							<form>
								<table class="widefat">
									<thead>
										<tr>
											<th><?php echo $prod_text; ?></th>
											<th><?php echo $qty_text; ?></th>
										</tr>
									</thead>
									<?php
									$row = '
									<td data-title="' . $prod_text . '"><select class="nmgr-product-search" name="item_id" data-allow_clear="true" data-display_stock="true" data-placeholder="' . esc_attr__( 'Search for a product or variation&hellip;', 'nm-gift-registry-lite' ) . '"></select></td>
									<td data-title="' . $qty_text . '"><input type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" class="quantity" /></td>';
									?>
									<tbody data-row="<?php echo esc_attr( $row ); ?>">
										<tr>
											<?php echo $row; ?>
										</tr>
									</tbody>
								</table>
							</form>
						</div>
						<div class="modal-footer">
							<button class="nmgr-add button button-primary button-large">
								<?php esc_html_e( 'Add', 'nm-gift-registry-lite' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<?php
		endif;
	}

	public static function insert_post_data( $data, $postarr ) {
		global $post;

		if ( nmgr()->post_type !== $data[ 'post_type' ] ) {
			return $data;
		}

		if ( !$data[ 'post_title' ] ) {
			$data[ 'post_title' ] = sprintf( '%1$s #%2$s', nmgr_get_type_title( 'c' ), $post->ID );
		}

		/**
		 * The post author should be the admin submitted user id (if the user id belongs to a registered user).
		 * If this is not available, we default to zero
		 */
		$post_author_username = '';
		if ( isset( $_REQUEST[ 'nmgr_user_id' ] ) ) {
			if ( is_numeric( $_REQUEST[ 'nmgr_user_id' ] ) ) {
				$data[ 'post_author' ] = absint( wp_unslash( $_REQUEST[ 'nmgr_user_id' ] ) );
				$post_author_username = get_the_author_meta( 'user_login', $data[ 'post_author' ] );
			} else {
				$data[ 'post_author' ] = 0;
			}
		} elseif ( 0 < $data[ 'post_author' ] && !is_numeric( get_post_meta( $postarr[ 'ID' ], '_nmgr_user_id', true ) ) ) {
			/**
			 * Make sure we don't set a post author for guest wishlists
			 * (This particular code snippet is necessary for when the post is updated via 'quick edit' in the list table.
			 */
			$data[ 'post_author' ] = 0;
		}

		/**
		 * Users are allowed to have one wishlist. If this user already has,
		 * update the user_id, set the post status to pending, and add error message
		 */
		if ( is_int( $data[ 'post_author' ] ) && 0 < $data[ 'post_author' ] ) {
			$wishlist_id = get_user_meta( absint( $data[ 'post_author' ] ), 'nmgr_wishlist_id', true );

			/**
			 * If the submitted user already has a wishlist and his wishlist is not
			 * the same as this wishlist being saved, set this wishlist author as 0 and
			 * status as pending.
			 */
			if ( $wishlist_id && absint( $postarr[ 'ID' ] ) !== absint( $wishlist_id ) ) {
				$data[ 'post_author' ] = 0;
				$data[ 'post_status' ] = 'pending';

				// inform the admin that the submitted user can only have one wishlist
				self::add_notice( sprintf(
						/* translators: %1$s: username, %2$s: %3$s: %5$s: wishlist type title, %4$s: line break */
						__( 'The user %1$s already has one %2$s. Users are allowed to have only one %3$s.%4$sThe status of this %5$s has been set to pending.', 'nm-gift-registry-lite' ),
						'<strong>' . $post_author_username . '</strong>',
						esc_html( nmgr_get_type_title() ),
						esc_html( nmgr_get_type_title() ),
						'<br>',
						esc_html( nmgr_get_type_title() )
					), 'error' );
			}
		}

		return $data;
	}

	public static function save_meta_boxes( $post_id, $post ) {
		if ( self::$saved_meta_boxes ||
			!current_user_can( 'edit_' . nmgr()->post_type_plural, $post_id ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		// Flag the save evet to run once to avoid endless loops.
		self::$saved_meta_boxes = true;

		// Save wishlist post meta fields
		try {
			$posted_data = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification

			if ( !NMGR_Form::verify_nonce( $posted_data ) || self::$notices ) {
				return;
			}

			$form = new NMGR_Form( $post_id );
			$wishlist = $form->get_wishlist();

			/**
			 * Always save ship to account address
			 * and save the value for registered users only
			 */
			$use_account_shipping = '';
			if ( isset( $posted_data[ 'nmgr_user_id' ] ) &&
				is_numeric( $posted_data[ 'nmgr_user_id' ] ) &&
				isset( $posted_data[ 'nmgr_ship_to_account_address' ] ) ) {
				$use_account_shipping = $posted_data[ 'nmgr_ship_to_account_address' ];
			}

			$wishlist->set_ship_to_account_address( sanitize_title( $use_account_shipping ) );
			$wishlist->save();
			unset( $posted_data[ 'nmgr_ship_to_account_address' ] );

			// Reset wishlist shipping values if users wants to ship to account address
			if ( $use_account_shipping ) {
				$default_shipping = $wishlist->get_default_data()[ 'shipping' ];

				$shipping = array();
				foreach ( $default_shipping as $key => $value ) {
					$shipping[ "shipping_$key" ] = $value;
				}

				$wishlist->set_props( $shipping );
				$wishlist->save();

				// Remove shipping fields from posted data
				foreach ( $posted_data as $key => $value ) {
					if ( false !== strpos( $key, 'shipping_' ) ) {
						unset( $posted_data[ $key ] );
					}
				}
			}

			// Save wishlist items
			$wishlist->update_items( $posted_data );

			$form->sanitize( $posted_data )->validate();

			if ( $form->has_errors() ) {
				foreach ( $form->get_error_messages() as $message ) {
					self::add_notice( $message, 'error' );
				}
			} else {
				if ( !$form->save() ) {
					throw new Exception( __( 'Sorry the wishlist details could not be saved', 'nm-gift-registry-lite' ) );
				}
			}
		} catch ( Exception $e ) {
			self::add_notice( $e->getMessage(), 'error' );
		}
	}

	/**
	 * If the user's wishlist is not enabled, let's do this for the admin here for convenience, and notify him.
	 */
	public static function enable_wishlist_for_user( $post_id ) {
		$post = get_post( $post_id );

		if ( 0 < absint( $post->post_author ) && !is_nmgr_enabled( $post->post_author ) ) {
			update_user_meta( $post->post_author, 'nmgr_enable_wishlist', 1 );

			$post_author_username = get_the_author_meta( 'user_login', $post->post_author );

			self::add_notice( sprintf(
					/* translators: %1$s: wishlist type title, %2$s Post author username */
					__( 'The %1$s module has been enabled for the user %2$s. <a href="%3$s">Manage this setting in the user\'s profile page.</a>', 'nm-gift-registry-lite' ),
					esc_html( nmgr_get_type_title() ),
					'<strong>' . $post_author_username . '</strong>',
					admin_url( 'user-edit.php?user_id=' . $post->post_author . '#user-nm-gift-registry' )
			) );
		}
	}

}
