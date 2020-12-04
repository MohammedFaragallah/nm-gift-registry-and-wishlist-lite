<?php

defined( 'ABSPATH' ) || exit;

/**
 * Handles installation and running actions
 */
class NMGR_Lite_Install {

	/**
	 * Notices to be shown
	 *
	 * @var array
	 */
	private static $notices = array();

	public static function load() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );

		register_activation_hook( NMGRLITE_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( NMGRLITE_FILE, array( __CLASS__, 'deactivate' ) );
		add_filter( 'extra_plugin_headers', array( __CLASS__, 'extra_plugin_headers' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_install_and_run' ) );
		add_action( 'nmgrlite_install', array( __CLASS__, 'install' ) );
		add_action( 'nmgrlite_run', array( __CLASS__, 'run' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	public static function extra_plugin_headers( $headers ) {
		$headers[] = 'NMGR documentation uri';
		$headers[] = 'NMGR requires WP';
		$headers[] = 'NMGR requires PHP';
		$headers[] = 'NMGR requires WC';

		return $headers;
	}

	/**
	 * Returns NM Gift Registry Lite main properties
	 *
	 * @return object
	 */
	public static function get_plugin_data() {
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( NMGRLITE_FILE );

		return ( object ) array(
				/**
				 * Full file path to the root plugin file
				 */
				'file' => NMGRLITE_FILE,
				/**
				 * Name of the root plugin file without the php extension
				 * e.g. nm-gift-registry
				 */
				'slug' => pathinfo( NMGRLITE_FILE, PATHINFO_FILENAME ),
				/**
				 * Name of the root plugin file prefixed with the root plugin folder if available
				 * e.g. nm-gift-registry/nm-gift-registry.php
				 */
				'basename' => plugin_basename( NMGRLITE_FILE ),
				/**
				 * Plugin url
				 */
				'url' => plugin_dir_url( NMGRLITE_FILE ),
				/**
				 * The path to the plugin
				 */
				'path' => plugin_dir_path( NMGRLITE_FILE ),
				/**
				 * The path to the templates folder of the plugin
				 */
				'template_path' => plugin_dir_path( NMGRLITE_FILE ) . 'templates/',
				/**
				 * The theme folder from which the plugin's template flies can be overridden
				 */
				'theme_path' => apply_filters( 'nmgr_theme_path', 'nm-gift-registry-lite/' ),
				/**
				 * Plugin name
				 */
				'name' => $plugin_data[ 'Name' ],
				/**
				 * Plugin version
				 */
				'version' => $plugin_data[ 'Version' ],
				/**
				 * Minimum php version required for plugin to work
				 */
				'requires_php' => $plugin_data[ 'NMGR requires PHP' ],
				/**
				 * Minimum wordpress version required for plugin to work
				 */
				'requires_wp' => $plugin_data[ 'NMGR requires WP' ],
				/**
				 * Minimum woocommerce version required for plugin to work
				 */
				'requires_wc' => $plugin_data[ 'NMGR requires WC' ],
				/**
				 * Documentation url
				 */
				'documentation_uri' => $plugin_data[ 'NMGR documentation uri' ],
				/**
				 * Wishlist post type name
				 */
				'post_type' => 'nm_gift_registry',
				/**
				 * Pluralized wishlist post type name
				 */
				'post_type_plural' => 'nm_gift_registries',
				/**
				 * Name for saving plugin options in database
				 */
				'option_name' => 'nmgr_settings',
				/**
				 * Plugin prefix
				 */
				'prefix' => 'nmgr_',
				/**
				 * Key to identify wishlist items in cart and order
				 */
				'cart_key' => 'nm_gift_registry',
				/**
				 * Post thumbnail size for wishlist featured image
				 * The post thumbnail is a square so this key returns one value for the width and height
				 */
				'post_thumbnail_size' => apply_filters( 'nmgr_medium_size', 190 ),
		);
	}

	public static function autoload( $class ) {
		if ( class_exists( $class ) || false === stripos( $class, 'nmgr' ) ) {
			return;
		}

		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$classes_path = realpath( dirname( NMGRLITE_FILE ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
		$filepath = $classes_path . $file;

		if ( file_exists( $filepath ) ) {
			include_once $filepath;
		}
	}

	public static function activate() {
		$settings = get_option( self::get_plugin_data()->option_name );

		if ( !empty( $settings ) &&
			isset( $settings[ 'allow_multiple_wishlists' ] ) &&
			$settings[ 'allow_multiple_wishlists' ] ) {
			add_option( 'nmgr_disable_multiple_wishlists', true );
		}

		if ( !self::maybe_deactivate_plugin() ) {
			// Installation action
			self::install_actions();
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'nmgr_delete_guest_wishlists' );

		flush_rewrite_rules();
	}

	public static function maybe_install_and_run() {
		if ( self::maybe_deactivate_plugin() ) {
			add_action( 'admin_init', array( __CLASS__, 'deactivate_plugin' ) );
			add_action( 'admin_notices', array( __CLASS__, 'show_deactivation_notice' ) );
			return;
		}

		include_once dirname( NMGRLITE_FILE ) . '/includes/nmgr-functions.php';

		// Installation action
		if ( version_compare( get_option( 'nmgrlite_version' ), self::get_plugin_data()->version, '<' ) ) {
			do_action( 'nmgrlite_install' );
		}

		// Run plugin
		do_action( 'nmgrlite_run' );
	}

	public static function install() {
		add_action( 'init', array( __CLASS__, 'install_actions' ) );
	}

	public static function run() {
		NMGR_Scripts::run();
		NMGR_Ajax::run();
		NMGR_Admin_Settings::run();
		NMGR_Admin_Post::run();
		NMGR_Admin::run();
		NMGR_Templates::run();
		NMGR_Form::run();
		NMGR_Order::run();
		NMGR_Wordpress::run();

		new NMGR_Widget_Cart();
		new NMGR_Widget_Search();

		add_action( 'init', array( __CLASS__, 'register_meta_tables' ), 0 );
		add_action( 'switch_blog', array( __CLASS__, 'register_meta_tables' ), 0 );
		add_filter( 'plugin_action_links_' . self::get_plugin_data()->basename, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'maybe_disable_multiple_wishlists' ) );
	}

	public static function maybe_disable_multiple_wishlists() {
		if ( get_option( 'nmgr_disable_multiple_wishlists' ) ) {
			$settings = get_option( self::get_plugin_data()->option_name );
			$settings[ 'allow_multiple_wishlists' ] = '';
			update_option( self::get_plugin_data()->option_name, $settings );
			delete_option( 'nmgr_disable_multiple_wishlists' );
		}
	}

	/**
	 * Cron schedules
	 *
	 * @since 2.0.0
	 */
	public static function cron_schedules( $schedules ) {
		if ( version_compare( get_bloginfo( 'version' ), '5.4.0', '<' ) ) {
			$schedules[ 'weekly' ] = array(
				'interval' => WEEK_IN_SECONDS,
				'display' => __( 'Once Weekly', 'nm-gift-registry-lite' )
			);
		}
		return $schedules;
	}

	/**
	 * Schedule events
	 *
	 * @since 2.0.0
	 */
	private static function schedule_events() {
		if ( !wp_next_scheduled( 'nmgr_delete_guest_wishlists' ) ) {
			wp_schedule_event( time() + (2 * MINUTE_IN_SECONDS), 'weekly', 'nmgr_delete_guest_wishlists' );
		}
	}

	public static function install_actions() {
		include_once dirname( NMGRLITE_FILE ) . '/includes/nmgr-functions.php';

		self::add_default_settings();
		self::create_tables();
		self::add_capabilities();

		// register custom post type (for flushing rewrite rules)
		NMGR_Wordpress::register_post_types();

		/**
		 * Register wishlist account endpoint (for flushing rewrite rules)
		 *
		 * This is only necessary on the register_activation_hook during plugin installation
		 * to flush the rewrite rules so that the wishlist endpoint would not return 404 when viewed in the frontend
		 *
		 * The wishlist endpoint itself is actually added by woocommerce by adding it to
		 * woocommerce's query vars using the 'woocommerce_get_query_vars' filter
		 */
		add_rewrite_endpoint( nmgr_get_account_details( 'slug' ), EP_ROOT | EP_PAGES );

		/**
		 * Update wishlist post meta
		 * @since 2.0.0
		 */
		if ( version_compare( get_option( 'nmgr_version' ), '2.0.0', '<' ) ) {
			self::add_user_id_wishlist_post_meta();
		}

		// Cron jobs
		self::schedule_events();

		// Update version
		update_option( 'nmgrlite_version', self::get_plugin_data()->version );

		// flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Add user_id meta key to wishlist post meta if it is not present
	 *
	 * This is necessary because version 2.0.0 now allows guests to have wishlists
	 * so the user id for guests have to be set in the post meta.
	 *
	 * (This function should be removed is a much later version of the plugin when we are
	 * sure the user_id meta key would have been added to all customer instances of the plugin).
	 *
	 * @since 2.0.0
	 */
	public static function add_user_id_wishlist_post_meta() {
		global $wpdb;

		$res = $wpdb->get_results(
			"SELECT ID, post_author FROM $wpdb->posts WHERE post_type = 'nm_gift_registry' AND post_author > 0"
		);

		foreach ( $res as $r ) {
			if ( !get_post_meta( $r->ID, '_nmgr_user_id', true ) ) {
				add_post_meta( $r->ID, '_nmgr_user_id', $r->post_author );
			}
		}
	}

	public static function add_default_settings() {
		$defaults = NMGR_Admin_Settings::get_default_values();
		$existing_settings = get_option( self::get_plugin_data()->option_name );

		if ( $existing_settings ) {
			$defaults = array_merge( $defaults, $existing_settings );
			delete_option( self::get_plugin_data()->option_name );
		}

		add_option( self::get_plugin_data()->option_name, $defaults );
	}

	public static function create_tables() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		  CREATE TABLE {$wpdb->prefix}nmgr_wishlist_items (
				wishlist_item_id BIGINT UNSIGNED NOT NULL auto_increment,
				name TEXT NOT NULL,
				wishlist_id BIGINT UNSIGNED NOT NULL,
				date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (wishlist_item_id),
				KEY wishlist_id (wishlist_id)
			 ) $collate;
		  CREATE TABLE {$wpdb->prefix}nmgr_wishlist_itemmeta (
			 meta_id BIGINT UNSIGNED NOT NULL auto_increment,
			 wishlist_item_id BIGINT UNSIGNED NOT NULL,
			 meta_key varchar(255) default NULL,
			 meta_value longtext NULL,
			 PRIMARY KEY  (meta_id),
			 KEY wishlist_item_id (wishlist_item_id),
			 KEY meta_key (meta_key(32))
		  ) $collate;
		  ";

		// update schema with dbdelta
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $tables );
	}

	/**
	 * Get the roles that have been assigned a specific plugin capability
	 *
	 * Default roles are admiinstrator and shop_manager
	 *
	 * @param string $capability_type The plugin capability
	 * @return array The roles that have this capability
	 */
	public static function get_roles( $capability_type ) {
		return apply_filters( "nmgr_{$capability_type}_roles", array(
			'administrator',
			'shop_manager'
			) );
	}

	public static function get_capabilities() {
		$capabilities = array();
		$post_type = self::get_plugin_data()->post_type;
		$post_type_plural = self::get_plugin_data()->post_type_plural;

		// Permission for managing plugin settings
		$capabilities[ 'manage_settings' ] = array(
			"manage_{$post_type}_settings"
		);

		// Permission for managing gift registry post type CRUD operations
		$capabilities[ "manage_CRUD" ] = array(
			"edit_{$post_type}",
			"read_{$post_type}",
			"delete_{$post_type}",
			"edit_{$post_type_plural}",
			"edit_others_{$post_type_plural}",
			"publish_{$post_type_plural}",
			"read_private_{$post_type_plural}",
			"delete_{$post_type_plural}",
			"delete_private_{$post_type_plural}",
			"delete_published_{$post_type_plural}",
			"delete_others_{$post_type_plural}",
			"edit_private_{$post_type_plural}",
			"edit_published_{$post_type_plural}",
		);

		return $capabilities;
	}

	public static function add_capabilities() {
		global $wp_roles;

		if ( !class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( !isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // phpcs:ignore
		}

		$capabilities = self::get_capabilities();

		foreach ( $capabilities as $type => $permissions ) {
			$roles = self::get_roles( $type );

			if ( !empty( $roles ) ) {
				foreach ( $permissions as $permission ) {
					foreach ( $roles as $role ) {
						$wp_roles->add_cap( $role, $permission );
					}
				}
			}
		}
	}

	public static function remove_capabilities() {
		global $wp_roles;

		if ( !class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( !isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // phpcs:ignore
		}

		$capabilities = self::get_capabilities();

		foreach ( $capabilities as $type => $permissions ) {
			$roles = self::get_roles( $type );

			if ( !empty( $roles ) ) {
				foreach ( $permissions as $permission ) {
					foreach ( $roles as $role ) {
						$wp_roles->remove_cap( $role, $permission );
					}
				}
			}
		}
	}

	/**
	 * Register our custom meta tables with wordpress so that we can use the meta api
	 * @global object $wpdb
	 */
	public static function register_meta_tables() {
		global $wpdb;

		$wpdb->wishlist_itemmeta = $wpdb->prefix . 'nmgr_wishlist_itemmeta';
		$wpdb->tables[] = 'nmgr_wishlist_itemmeta';
	}

	public static function plugin_action_links( $links ) {
		return array_merge( $links, array(
			'<a href="' . NMGR_Admin_Settings::url() . '">' . __( 'Settings', 'nm-gift-registry-lite' ) . '</a>',
			) );
	}

	public static function plugin_row_meta( $links, $file ) {
		if ( $file == self::get_plugin_data()->basename ) {
			$links[] = '<a href="' . esc_url( self::get_plugin_data()->documentation_uri ) . '">' . __( 'Docs', 'nm-gift-registry-lite' ) . '</a>';
		}
		return $links;
	}

	public static function maybe_deactivate_plugin() {
		if ( class_exists( 'NMGR_Install' ) ) {
			self::$notices[] = __( 'The lite version of the plugin cannot be activated while the full version is active. Please deactivate the full version first.', 'nm-gift-registry-lite' );
		}

		if ( !class_exists( 'Woocommerce' ) ) {
			/* translators: %s: plugin name */
			self::$notices[] = sprintf( __( 'You need the <strong>WooCommerce plugin</strong> to be <strong>installed</strong> and <strong>activated</strong> for %s to work.', 'nm-gift-registry-lite' ), self::get_plugin_data()->name );
		} elseif ( version_compare( WC_VERSION, self::get_plugin_data()->requires_wc, '<' ) ) {
			/* translators: %1$s: plugin name, %2$s: required woocommerce version */
			self::$notices[] = sprintf( __( '%1$s needs <strong>WooCommerce %2$s or higher</strong> to work. Please update WooCommerce.', 'nm-gift-registry-lite' ), self::get_plugin_data()->name, self::get_plugin_data()->requires_wc );
		}

		if ( version_compare( phpversion(), self::get_plugin_data()->requires_php, '<' ) ) {
			/* translators: %1$s: plugin name, %2$s: required php version */
			self::$notices[] = sprintf( __( '%1$s needs <strong>PHP %2$s or higher</strong> to work. Please upgrade your PHP version.', 'nm-gift-registry-lite' ), self::get_plugin_data()->name, self::get_plugin_data()->requires_php );
		}

		if ( version_compare( get_bloginfo( 'version' ), self::get_plugin_data()->requires_wp, '<' ) ) {
			/* translators: %1$s: plugin name, %2$s: required WordPress version, %3$s: WordPress update url */
			self::$notices[] = sprintf( __( '%1$s needs <strong>WordPress %2$s or higher</strong> to work. Please <a href="%3$s">update WordPress</a>.', 'nm-gift-registry-lite' ), self::get_plugin_data()->name, self::get_plugin_data()->requires_wp, admin_url( 'update-core.php' ) );
		}

		return !empty( self::$notices ) ? true : false;
	}

	public static function deactivate_plugin() {
		deactivate_plugins( self::get_plugin_data()->basename );
		if ( isset( $_GET[ 'activate' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET[ 'activate' ] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	public static function show_deactivation_notice() {
		$header = self::get_plugin_data()->name . ' deactivated';
		$message = '';

		foreach ( self::$notices as $notice ) {
			$message .= "<p>- $notice</p>";
		}

		printf( '<div class="notice notice-error"><p><strong>%1$s</strong></p>%2$s</div>', esc_html( $header ), wp_kses_post( $message ) );
	}

}
