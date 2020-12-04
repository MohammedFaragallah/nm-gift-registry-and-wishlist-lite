<?php

/**
 * The template for displaying wishlist search results
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/search-nm_gift_registry.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.3
 */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

nmgr_get_search_results_template( '', true );

do_action( 'woocommerce_after_main_content' );

do_action( 'nmgr_sidebar' );

get_footer( 'shop' );


