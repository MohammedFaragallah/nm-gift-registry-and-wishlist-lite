<?php

/**
 * Template for displaying all single wishlists
 *
 * As NM Gift Registry is a custom post type, this template can be overridden by
 * creating a single-nm_gift_registry.php file in your theme root
 * (following wordpress' template hierarchy for custom post types)
 *
 * This template can also be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/single-nm_gift_registry.php if you prefer
 *
 * The template may also be updated in future versions of the plugin.
 * In such case if it has been overriden by copying to your theme's nm-gift-registry folder,
 * you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

get_header('shop');

do_action('woocommerce_before_main_content');

while (have_posts()) :
    the_post();

    nmgr_template('content-single-nm_gift_registry.php');

endwhile;

do_action('woocommerce_after_main_content');


do_action('nmgr_sidebar');

get_footer('shop');