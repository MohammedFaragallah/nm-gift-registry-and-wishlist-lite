<?php

/**
 * Plugin Name: NM Gift Registry and Wishlist Lite
 * Plugin URI: nmgiftregistry.com
 * Description: Advanced and highly customizable gift registry and wishlist plugin for your woocommerce store.
 * Author: Nmeri Ibeawuchi
 * Version: 2.1.0
 * Text Domain: nm-gift-registry-lite
 * Domain Path: /languages/
 * NMGR documentation uri: nmgiftregistry.com/category/documentation
 * NMGR requires WP: 4.7
 * NMGR requires PHP: 5.6
 * NMGR requires WC: 3.6.5
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * WC requires at least: 3.6.5
 * WC tested up to: 4.8
 */
defined( 'ABSPATH' ) || exit;

define( 'NMGRLITE_FILE', __FILE__ );

if ( !class_exists( 'NMGR_Lite_Install' ) ) {
	include_once 'includes/class-nmgr-lite-install.php';

	NMGR_Lite_Install::load();
}

