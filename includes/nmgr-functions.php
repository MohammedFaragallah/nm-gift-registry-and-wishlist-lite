<?php

defined('ABSPATH') || exit;

use JWTAuth\AUTH;

/**
 * Returns NM Gift Registry Lite main properties
 *
 * @return object
 */
function nmgr()
{
    return NMGR_Lite_Install::get_plugin_data();
}

/**
 * Checks if the post is for an nm gift registry post type
 *
 * @param int | WP_Post | null $post The post id or object
 * @return boolean True | False
 */
function is_nmgr_post($post = null)
{
    return nmgr()->post_type === get_post_type($post);
}

/**
 * Check if we are viewing a single wishlist page on the frontend
 *
 * @return boolean
 */
function is_nmgr_wishlist()
{
    if (isset($GLOBALS[ 'nmgr' ]->is_wishlist)) {
        return filter_var($GLOBALS[ 'nmgr' ]->is_wishlist, FILTER_VALIDATE_BOOLEAN);
    }
    return is_singular(nmgr()->post_type) ||
        defined('NMGR_WISHLIST') ||
        nmgr_post_content_has_shortcodes('nmgr_wishlist') ||
        apply_filters('is_nmgr_wishlist', false);
}

/**
 * Check if we are on a single wishlist edit page or the all wishlists page in the admin area
 *
 * @return boolean
 */
function is_nmgr_admin()
{
    global $current_screen;

    if (wp_doing_ajax() && isset($GLOBALS[ 'nmgr' ]->is_admin)) {
        return filter_var($GLOBALS[ 'nmgr' ]->is_admin, FILTER_VALIDATE_BOOLEAN);
    }

    if (is_admin() && !wp_doing_ajax()) {
        if (!isset($current_screen)) {
            return false;
        }

        return isset($current_screen->post_type) && nmgr()->post_type === $current_screen->post_type;
    }
    return false;
}

/**
 * Check if we are on the woocommerce account page endpoint for NM Gift Registry
 *
 * @return boolean
 */
function is_nmgr_account()
{
    if (isset($GLOBALS[ 'nmgr' ]->is_account)) {
        return filter_var($GLOBALS[ 'nmgr' ]->is_account, FILTER_VALIDATE_BOOLEAN);
    }

    return is_wc_endpoint_url(nmgr_get_account_details('slug')) ||
        defined('NMGR_ACCOUNT') ||
        apply_filters('is_nmgr_account', false);
}

/**
 * Check if we are on the wishlists search page
 *
 * @return boolean
 */
function is_nmgr_search()
{
    global $wp_query;
    return (is_search() &&
        isset($wp_query->query_vars[ 'post_type' ]) &&
        nmgr()->post_type === $wp_query->query_vars[ 'post_type' ]) ||
        isset($wp_query->query_vars[ 'nmgr_s' ]) ||
        nmgr_post_content_has_shortcodes(array( 'nmgr_search', 'nmgr_search_results' ));
}

/**
 * Check if we are in an NM Gift Registry account tab
 *
 * @return boolean
 */
function is_nmgr_account_tab()
{
    if (isset($GLOBALS[ 'nmgr' ]->is_account_tab)) {
        return filter_var($GLOBALS[ 'nmgr' ]->is_account_tab, FILTER_VALIDATE_BOOLEAN);
    }
    return false;
}

/**
 * Check if we are in an NM Gift Registry modal window
 *
 * @return boolean
 */
function is_nmgr_modal()
{
    if (isset($GLOBALS[ 'nmgr' ]->is_modal)) {
        return filter_var($GLOBALS[ 'nmgr' ]->is_modal, FILTER_VALIDATE_BOOLEAN);
    }
    return false;
}

/**
 * Check if we are currently in an NM Gift Registry account section
 *
 * Registered account sections are:
 * - overview
 * - profile
 * - items
 * - shipping
 *
 * @return boolean
 */
function is_nmgr_account_section()
{
    return defined('NMGR_ACCOUNT_SECTION') || nmgr_post_content_has_shortcodes(NMGR_Wordpress::get_account_shortcodes());
}

/**
 * Check if we are on a page which uses nmgr templates
 *
 * @return boolean
 */
function is_nmgr()
{
    return apply_filters(
        'is_nmgr',
        is_nmgr_wishlist() ||
        is_nmgr_admin() ||
        is_nmgr_account() ||
        is_nmgr_search() ||
        is_nmgr_account_section() ||
        is_nmgr_account_tab()
    );
}

/**
 * Check whether the user has enabled the gift registry module for his use
 *
 * This function should only be used for logged-in users.
 * It will always return true for guests and
 * if user-specific enabling of the gift registry is disabled in the admin settings.
 *
 * If the user has not enabled the gift registry module, redirection to 404 page occurs
 * when trying to access his woocommerce account area and single wishlist page.
 *
 * @param int $user_id User id. Optional. Defaults to current logged in user.
 * @return boolean
 */
function is_nmgr_enabled($user_id = '')
{
    if (is_user_logged_in() && nmgr_get_option('user_enable_wishlist')) {
        $id = $user_id ? $user_id : get_current_user_id();
        if (!get_user_meta(absint($id), 'nmgr_enable_wishlist', true)) {
            return false;
        }
    }
    return true;
}

/**
 * Determine whether we are in the shop loop
 *
 * This is based on whether any of the registered action hooks for displaying product
 * content within loops in content-product.php is being fired.
 *
 * This function is typically preferred to checking for woocommerce archive
 * pages with is_shop() or is_product_taxonomy() because it covers the shop loop
 * in every location including places which may not be typical archive locations
 * such as the 'Related Products' section on single product pages.
 *
 * @return boolean
 */
function is_nmgr_shop_loop()
{
    $actions = apply_filters('nmgr_shop_loop_actions', array(
        'woocommerce_before_shop_loop_item',
        'woocommerce_before_shop_loop_item_title',
        'woocommerce_shop_loop_item_title',
        'woocommerce_after_shop_loop_item_title',
        'woocommerce_after_shop_loop_item'
        ));

    foreach ($actions as $action) {
        if (doing_action($action)) {
            return true;
        }
    }
    return false;
}

/**
 * Get the search form for finding a wishlist on the frontend
 */
function nmgr_get_search_form()
{
    return nmgr_get_search_template(array(
        'show_results' => false,
        'form_action' => home_url(),
        ));
}

/**
 * Paged navigation for search results
 *
 * @since 1.0.3 'nmgr_paging_nav_args' and 'nmgr_paging_nav' filters added
 */
function nmgr_paging_nav()
{
    $args = apply_filters('nmgr_paging_nav_args', array(
        'next_text' => _x('Next', 'Next wishlist', 'nm-gift-registry-lite'),
        'prev_text' => _x('Previous', 'Previous wishlist', 'nm-gift-registry-lite'),
        ));
    $pagination = apply_filters('nmgr_paging_nav', get_the_posts_pagination($args));
    echo $pagination;
}

/**
 * Get the permalink for the wishlist account page
 * or for managing a specific wishlist on the account page
 *
 * @return string
 */
function nmgr_get_account_url()
{
    $custom_page_id = absint(nmgr_get_option('wishlist_account_page_id'));
    $custom_url = 0 < $custom_page_id ? get_permalink($custom_page_id) : '';

    if ($custom_url && (!nmgr_get_account_details('slug') || (nmgr_get_account_details('slug') && is_nmgr_guest()))) {
        $account_url = $custom_url;
    } else {
        $account_url = trailingslashit(wc_get_page_permalink('myaccount')) . trailingslashit(nmgr_get_account_details('slug'));
    }

    return apply_filters('nmgr_account_url', $account_url);
}

/**
 * Get an option value for the plugin
 *
 * If an option key is not provided, values for all plugin keys are returned
 *
 * @param string $option_key The name of the option to get the value for. Optional
 * @param mixed $default_value The value to return if the option doesn't exist
 * @return mixed
 */
function nmgr_get_option($option_key = '', $default_value = null)
{
    $options = get_option(nmgr()->option_name, array());
    if ($option_key) {
        return array_key_exists($option_key, $options) ? $options[ $option_key ] : $default_value;
    }
    return $options;
}

/**
 * Verify the standard form nonce supplied in NMGR_Form
 *
 * @param array $request Array to check in for existing nonce key or $_REQUEST if not supplied
 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
 */
function nmgr_verify_form_nonce($request = '')
{
    return NMGR_Form::verify_nonce($request);
}

/**
 * Verify the wishlist id posted during a request and make sure that it is the
 * same wishlist id sent to the page
 *
 * @deprecated 2.0.0
 * @param array $posted_data The posted data containing the 'wishlist id' and 'nonce' keys. Optional. Default is $_POST.
 * @return int|null Verified wishlist id or null if the wishlist id supplied is invalid.
 */
function nmgr_get_verified_wishlist_id($posted_data = '')
{
    _deprecated_function(__FUNCTION__, '2.0.0', 'nmgr_verify_request');

    $wishlist_id = isset($_REQUEST[ 'wishlist_id' ]) ? ( int ) $_REQUEST[ 'wishlist_id' ] : 0;
    if (0 === $wishlist_id && nmgr_user_has_wishlist($wishlist_id)) {
        return $wishlist_id;
    }
    return null;
}

/**
 * Get a template file from the templates path
 *
 * This function searches the templates path in the theme folder before defaulting to
 * the templates path in the plugin folder if it doesn't find the file.
 * This way, It allows plugin templates to be overridden by copying them to the theme folder
 * similar to the way woocommerce works.
 *
 * The default expected theme template path where overridden templates reside is: yourtheme/plugin-slug'
 * where 'yourtheme' is the name of your theme and 'plugin-slug' is nm-gift-registry-lite for the lite version or
 * nm-gift-registry for the full version of the plugin.
 *
 * @param string $name Name of template file to get (prefixed with subfolder if it exists in a subfolder of the template path).
 * @param array $args Variables to send to the template file.
 *
 * @return string Template html
 */
function nmgr_get_template($name, $args = array())
{
    return wc_get_template_html($name, $args, nmgr()->theme_path, nmgr()->template_path);
}

/**
 * Output a template file
 *
 * @param type $name Name of template file to get (prefixed with subfolder if it exists in a subfolder of the template path).
 * @param type $args Variables to send to the template file.
 */
function nmgr_template($name, $args = array())
{
    echo nmgr_get_template($name, $args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get the full anchor tag for a user's wishlist page on the frontend or the edit page in the admin area if in admin
 *
 * @param obj $wishlist Wishlist object
 * @param array $args Parameters to be used in the anchor tag.
 * Accepted parameters
 *  title - title attriubte of the link element.
 * content - content to display as the link text.
 * @return string anchor html tag
 */
function nmgr_get_wishlist_link($wishlist, $args = array())
{
    if ($wishlist) {
        $url = !is_admin() ? esc_url($wishlist->get_permalink()) : admin_url('post.php?post=' . $wishlist->get_id() . '&action=edit');
        $title = isset($args[ 'title' ]) ? esc_attr($args[ 'title' ]) : '';
        $content = nmgr_kses_post(isset($args[ 'content' ]) ? $args[ 'content' ] : $wishlist->get_title());
        return sprintf('<a href="%s" title="%s">%s</a>', $url, $title, $content);
    }
}

/**
 * Get the current wishlist id based on the global context, current page, or query
 *
 * @return mixed Wishlist id | NULL
 */
function nmgr_get_current_wishlist_id()
{
    global $post, $nmgr, $wpdb;

    $the_post = $post;

    if (isset($nmgr->wishlist_id) && !empty($nmgr->wishlist_id)) {
        if (is_nmgr_post($nmgr->wishlist_id)) {
            return absint($nmgr->wishlist_id);
        } else {
            unset($GLOBALS[ 'nmgr' ]->wishlist_id);
        }
    }

    if (is_int($the_post)) {
        $the_post = get_post($the_post);
    }

    if (isset($the_post, $the_post->ID) && is_nmgr_post($the_post->ID)) {
        return absint($the_post->ID);
    }

    if (is_user_logged_in()) {
        return absint(get_user_meta(get_current_user_id(), 'nmgr_wishlist_id', true));
    } elseif (nmgr_get_current_user_id()) {
        /**
         * This code snippet could be used for logged in users also but we have used it here for now
         * as guest wishlists are always considered to be active for simplicity sake and the code does
         * not allow us to detect the status of the wishlist, whether trash or pending, etc, before returning
         * it as the current wishlist id.
         */
        return ( int ) $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_user_id' AND meta_value = %s ORDER BY meta_id DESC LIMIT 1", nmgr_get_current_user_id()));
    }
}

/**
 * Get all the users that have active wishlists
 *
 * Active wishlists are wishlists that are not in trash.
 * All guest wishlists are assumed to be active at the moment for simplicity sake.
 *
 * @global wpdb $wpdb
 * @param string $type Type of users to get. Default is 'users' for registered users.
 * Other options are : 'guests' for not registered or logged in users.
 * @return array User ids
 */
function nmgr_get_users($type = 'users')
{
    global $wpdb;

    if ('users' === $type) {
        return $wpdb->get_col("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_author != 0 AND post_type = 'nm_gift_registry' AND post_status != 'trash'");
    }

    if ('guests' === $type) {
        return $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_nmgr_guest' ");
    }
}

/**
 * Get all the wishlists for a user
 *
 * This function only retrieves active wishlists (wishlists with valid plugin statuses - @see nmgr_get_post_statuses())
 * as these are the statuses used for wishlists on the frontend.
 *
 * @param int|string $user_id The user id (optional). Defaults to current logged in user id or guest user id cookie value.
 * @return array
 */
function nmgr_get_user_wishlists($user_id = '')
{
    global $wpdb;

    $userid = $user_id ? $user_id : nmgr_get_current_user_id();

    if (!$userid) {
        return array();
    }

    $posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_user_id' AND meta_value = %s ",
        $userid
    ));

    foreach ($posts as $key => $id) {
        if (!in_array(get_post_status($id), nmgr_get_post_statuses())) {
            unset($posts[ $key ]);
            continue;
        }
        $posts[ $id ] = $posts[ $key ];
        unset($posts[ $key ]);
    }

    return !empty($posts) ? array_filter(array_map('nmgr_get_wishlist', $posts)) : array();
}

/**
 * Get the count of all a user's wishlists or the current user if no user id is supplied
 *
 * This function only retrieves wishlists with valid plugin statuses (@see nmgr_get_statuses())
 * as these are the statuses used for wishlists on the frontend
 *
 * @param int $user_id The user id (optional)
 * @global wpdb $wpdb
 * @return mixed int | NULL
 */
function nmgr_get_user_wishlists_count($user_id = '')
{
    global $wpdb;

    $userid = $user_id ? $user_id : nmgr_get_current_user_id();

    if (!$userid) {
        return 0;
    }

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_user_id' AND meta_value = %s ",
        $userid
    ));

    foreach ($post_ids as $key => $id) {
        if (!is_nmgr_post($id) || !in_array(get_post_status($id), nmgr_get_post_statuses())) {
            unset($post_ids[ $key ]);
        }
    }

    return count($post_ids);
}

/**
 * Compose an svg icon using dynamic arguments provided
 *
 * @since 2.1.0 the path argument was added.
 *
 * @param string|array $args The name of the svg icon to get or array of icon parameters.
 *
 * If $args is an array, registered array keys needed to compose the svg are:
 * - icon - [required] string The svg icon name. This should correspond to the name of an svg file in the assets/svg directory or the last part of the id of a symbol element in the sprite file - assets/svg/sprite.svg.
 * - size - [optional] integer|string The svg icon width and height. (uses em unit if no unit is specified). Default 1em (16px).
 * - class - [optional] string The svg icon class to append to default classes.
 * - sprite - [optional] boolean Whether to use the loaded svg sprite file (Default), or to load the single svg icon from the assets/svg directory.
 * - role - [optional] string Svg role attribute. Default img.
 * - id - [optional] string Svg id attribute.
 * - style - [optional] string Svg Inline style.
 * - title - [optional] string Svg title attribute.
 * - fill - [optional] string Svg fill attribute.
 * - path - [optional] path to the single svg icon file. (Usually used if not using the loaded svg sprite file).
 *
 * @return string Svg HTML element
 */
function nmgr_get_svg($args)
{
    // Make sure icon name is given
    if (!$args || (is_array($args) && (false === array_key_exists('icon', $args)))) {
        // If no arguments are set or the icon key is not in the array, require icon key
        return __('Please define the name of the svg icon to get.', 'nm-gift-registry-lite');
    } elseif (is_string($args)) {
        //if a string argument is given, assume it is the icon name and set it in the array
        $args = array( 'icon' => $args );
    }

    // Set defaults.
    $defaults = array(
        'icon' => '',
        'size' => 1,
        'class' => 'nmgr-icon ' . $args[ 'icon' ],
        'sprite' => true,
        'role' => 'img',
        'path' => '',
    );

    // Parse args.
    $args = wp_parse_args($args, $defaults);

    // Make sure default classes are present
    if ($args[ 'class' ] !== $defaults[ 'class' ]) {
        $args[ 'class' ] = $defaults[ 'class' ] . ' ' . $args[ 'class' ];
    }

    // Make sure the size has units (default em)
    $size = is_numeric($args[ 'size' ]) ? "{$args[ 'size' ]}em" : $args[ 'size' ];

    // Get extra svg parameters set by user that are not in the default expected arguments
    // e.g 'style', 'title' and 'fill'
    $extra_params = array_diff_key($args, $defaults);
    $extra_params_string = '';

    // extract the title attribute if it exists so that we can add it separately to the svg
    $title = '';
    if (isset($extra_params[ 'title' ]) && !empty($extra_params[ 'title' ])) {
        $string = htmlspecialchars(wp_kses_post($extra_params[ 'title' ]));
        $title = sprintf("<title data-title='%s'>%s</title>", $string, $string);
        unset($extra_params[ 'title' ]);
    }

    // Create new indexed array from extra svg parameters
    if (!empty($extra_params)) {
        $arr = array();
        foreach ($extra_params as $key => $value) {
            $arr[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        // Compose string from array of extra svg parameters
        $extra_params_string = implode(' ', $arr);
    }

    // Compose svg with the given attributes
    $composed_svg = sprintf(
        '<svg role="%s" width="%s" height="%s" class="%s" %s ',
        esc_attr($args[ 'role' ]),
        esc_attr($size),
        esc_attr($size),
        esc_attr($args[ 'class' ]),
        $extra_params_string
    );


    if ($args[ 'sprite' ]) {
        /**
         * we are using a sprite file
         */
        $svg = sprintf('%s>%s<use xlink:href="#nmgr-icon-%s"></use></svg>', $composed_svg, $title, esc_html($args[ 'icon' ]));
    } else {
        /**
         *  we are using a single svg file
         */
        // Get the svg file
        $svg = nmgr_get_svg_file($args[ 'icon' ], $args[ 'path' ]);

        // Remove width and heigh attributes from svg if exists as we are adding our own
        $svg = preg_replace('/(width|height)="\d*"\s/', "", $svg);

        // Merge composed svg with original svg
        $svg = preg_replace('/^<svg /', $composed_svg, trim($svg));

        // Add title attribute if it exists
        $svg = str_replace('</svg>', "$title</svg>", $svg);

        // Remove newlines & tabs.
        $svg = preg_replace("/([\n\t]+)/", ' ', $svg);

        // Remove white space between SVG tags.
        $svg = preg_replace('/>\s*</', '><', $svg);
    }

    return nmgr_kses_svg($svg);
}

/**
 * Get a wishlist by a specified id,
 * or by the current wishlist id in the global object if no id is specified.
 *
 * @param int $wishlist_id The wishlist id used to retrieve the wishlist
 * @param bool $active Whether the wishlist must be active. Default false. (An active wishlist has its
 * post status in the registered post statuses for wishlists. Active wishlists appear on the frontend).
 * @return NMGR_Wishlist | false
 */
function nmgr_get_wishlist($wishlist_id = 0, $active = false)
{
    $wishlist_id = $wishlist_id ? $wishlist_id : nmgr_get_current_wishlist_id();

    if (!$wishlist_id) {
        return false;
    }

    /**
     * try catch statement is used because the wishlist db class throws an exception
     * if the wishlist cannot be read from database
     */
    try {
        $wishlist = new NMGR_Wishlist($wishlist_id);
        return $active ? ($wishlist->is_active() ? $wishlist : false) : $wishlist;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get specific information concerning the main wishlist account page
 *
 * For example, get the name or slug of the account page, as set in the admin settings screen
 *
 * @param string $param The information to get e.g. 'name', 'slug'
 * @return string Wishlist account page information
 */
function nmgr_get_account_details($param)
{
    $options = nmgr_get_option();
    switch ($param) {
        case 'name':
            return isset($options[ 'my_account_name' ]) ? esc_html($options[ 'my_account_name' ]) : false;
        case 'slug':
            $slug = isset($options[ 'my_account_name' ]) ? $options[ 'my_account_name' ] : null;
            return $slug ? strtolower(str_replace(' ', '-', esc_html($slug))) : false;
    }
}

/**
 * Add to wishlist messages.
 *
 * @param NMGR_Wishlist $wishlist Wishlist Object
 * @param int|array $products Array of product id to quantity wishlist or single product ID.
 * @param bool      $show_qty Should qty's be shown?
 * @param bool      $return   Return message rather than add it.
 *
 * @return mixed
 */
function nmgr_add_to_wishlist_notice($wishlist, $products, $show_qty = false, $return = false)
{
    $titles = array();
    $count = 0;

    if (!is_array($products)) {
        $products = array( $products => 1 );
        $show_qty = false;
    }

    if (!$show_qty) {
        $products = array_fill_keys(array_keys($products), 1);
    }

    foreach ($products as $product_id => $qty) {
        $titles[] = apply_filters('nmgr_add_to_wishlist_qty_html', ($qty > 1 ? absint($qty) . ' &times; ' : ''), $product_id) .
            apply_filters('nmgr_add_to_wishlist_item_name_in_quotes', sprintf(
                    /* translators: %s: item name */
                    _x('&ldquo;%s&rdquo;', 'Item name in quotes', 'nm-gift-registry-lite'),
                strip_tags(get_the_title($product_id))
            ), $product_id);
        $count += $qty;
    }

    $titles = array_filter($titles);
    $added_text = sprintf(
        /* translators: 1: item names, 2: wishlist type title */
        _n('%1$s has been added to your %2$s.', '%1$s have been added to your %2$s.', $count, 'nm-gift-registry-lite'),
        wc_format_list_of_items($titles),
        esc_html(nmgr_get_type_title())
    );

    // Get success messages.
    $message = sprintf(
        '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s',
        esc_url($wishlist->get_permalink()),
        /* translators: %s: wishlist type title */
        sprintf(esc_html__('View %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title())),
        esc_html($added_text)
    );

    $message = apply_filters('nmgr_add_to_wishlist_notice', $message, $wishlist, $products, $show_qty);

    if ($return) {
        return $message;
    } else {
        wc_add_notice($message);
    }
}

/**
 * Get all wishlists which are in the cart
 * @return array Array of unique wishlist ids
 */
function nmgr_get_wishlists_in_cart()
{
    $wishlists = array();
    if (!WC()->cart->is_empty()) {
        $cart = WC()->cart->get_cart();

        foreach ($cart as $cart_item) {
            if (isset($cart_item[ nmgr()->cart_key ])) {
                $wishlist_id = $cart_item[ nmgr()->cart_key ][ 'wishlist_id' ];
                if (nmgr_get_wishlist($wishlist_id, true)) {
                    $wishlists[] = $wishlist_id;
                }
            }
        }

        $wishlists = apply_filters('nmgr_get_wishlists_in_cart', $wishlists, $cart);
    }
    return array_unique($wishlists);
}

/**
 * Get the title attribute to show when a wishlist has a product
 *
 * This depends on the kind of product - simple, variable, grouped
 * as well as the number of wishlists the user has
 */
function nmgr_get_product_in_wishlist_title_attribute($product)
{
    if (!$product) {
        return;
    }

    $count = nmgr_get_user_wishlists_count();
    $type = $product->get_type();

    switch ($type) {
        case 'variable':
            return sprintf(
                /* translators: 1: wishlist type title, 2: wishlist type title plural form */
                _n(
                    'This product has variations in your %s',
                    'This product has variations in one or more of your %s',
                    $count,
                    'nm-gift-registry-lite'
                ),
                esc_html(nmgr_get_type_title()),
                esc_html(nmgr_get_type_title('', 1))
            );
        case 'grouped':
            return sprintf(
                /* translators: 1: wishlist type title, 2: wishlist type title plural form */
                _n(
                    'This product has child products in your %s',
                    'This product has child products in one or more of your %s',
                    $count,
                    'nm-gift-registry-lite'
                ),
                esc_html(nmgr_get_type_title()),
                esc_html(nmgr_get_type_title('', 1))
            );
        default:
        return sprintf(
            _n(
                    /* translators: 1: wishlist type title, 2: wishlist type title plural form */
                    'This product is in your %s',
                'This product is in one or more of your %s',
                $count,
                'nm-gift-registry-lite'
            ),
            esc_html(nmgr_get_type_title()),
            esc_html(nmgr_get_type_title('', 1))
        );
    }
}

/**
 * Check whether the current user has a product in any of his wishlists
 *
 * @param WC_Product $product
 * @return boolean True or false
 */
function nmgr_user_has_product_in_wishlist($product)
{
    $wishlists = nmgr_get_user_wishlists();
    if ($wishlists) {
        foreach ($wishlists as $wishlist) {
            if ($wishlist && $wishlist->has_product($product)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Query string keys for adding an item to the wishlist
 *
 * This function is used to prevent hardcoding the query keys in various files.
 * In case they change later, they would only change here
 *
 * @param string $name query key to get for value
 * @return string Query key
 */
function nmgr_query_key($name = '')
{
    $query_keys = array(
        'product_id' => 'nmgr_pid',
        'wishlist_id' => 'nmgr_wid',
        'quantity' => 'nmgr_qty',
        'variation_id' => 'nmgr_vid',
        'wishlist' => 'nmgr_w', // accepts wishlist id or slug.
    );

    return $name ? (isset($query_keys[ $name ]) ? $query_keys[ $name ] : null) : $query_keys;
}

/**
 * Get the post statuses used by the plugin
 *
 * These are the statuses the plugin uses for active wishlists on the frontend.
 * All other wordpress post statuses are currently ignored.
 *
 * Default post statuses:
 * - publish
 * - private
 *
 * @return array
 */
function nmgr_get_post_statuses()
{
    return apply_filters('nmgr_post_statuses', array( 'publish', 'private' ));
}

/**
 * Get the standard date format used by the plugin to display dates sitewide
 *
 * This function simply allows the date format to be filtered so that a different
 * date format can be used to display dates sitewide.
 *
 * @return string
 */
function nmgr_date_format()
{
    return apply_filters('nmgr_date_format', nmgr_php_date_format());
}

/**
 * Get the registered php date format for the plugin.
 *
 * This date format is used by default to format all dates displayed by the plugin sitewide
 * and also to validate dates submitted through jquery-datepicker
 *
 * @since 2.0.2
 *
 * @return string
 */
function nmgr_php_date_format()
{
    /* translators: 'M j, Y': date format */
    return _x('F j, Y', 'nm gift registry date format', 'nm-gift-registry-lite');
}

/**
 * Get a localized date based on date format
 *
 * @param string $date Date to format
 * @param string $format Date format to use. Default is standard plugin date format
 * @return string
 */
function nmgr_format_date($date, $format = '')
{
    $date_format = $format ? $format : nmgr_date_format();
    $datetime = nmgr_get_datetime($date);
    if ($date && $datetime) {
        $function = function_exists('wp_date') ? 'wp_date' : 'date_i18n';
        return call_user_func($function, $date_format, $datetime->getTimestamp());
    }
    return $date;
}

/**
 * Get variation attributes posted by a form
 *
 * @param int $variation_id Id of the variation product
 * @param array $post posted data contaIning variations
 * @return array
 */
function nmgr_get_posted_variations($variation_id, $post = '')
{
    $variations = array();
    $posted_data = array();

    if (!$variation_id) {
        return $variations;
    }

    if (empty($post)) {
        $posted_data = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification
    } elseif (is_string($post)) {
        parse_str($post, $posted_data);
    } elseif (is_array($post)) {
        $posted_data = $post;
    }

    $product = wc_get_product($variation_id);
    $product_parent_id = $product->get_parent_id();
    $product_parent = wc_get_product($product_parent_id);

    if (!$product_parent) {
        return $variations;
    }

    foreach ($posted_data as $key => $value) {
        if (false === strpos($key, 'attribute_')) {
            unset($posted_data[ $key ]);
        }
    }

    foreach ($product_parent->get_attributes() as $attribute) {
        if (!$attribute[ 'is_variation' ]) {
            continue;
        }
        $attribute_key = 'attribute_' . sanitize_title($attribute[ 'name' ]);

        if (isset($posted_data[ $attribute_key ])) {
            if ($attribute[ 'is_taxonomy' ]) {
                $value = sanitize_title(wp_unslash($posted_data[ $attribute_key ]));
            } else {
                $value = html_entity_decode(wc_clean(wp_unslash($posted_data[ $attribute_key ])), ENT_QUOTES, get_bloginfo('charset'));
            }
            $variations[ $attribute_key ] = $value;
        }
    }

    return $variations;
}

/**
 * Add the plugin prefix to the specified fields keys
 * except fields that have $args['prefix'] set to false
 *
 * @param array $fields Form fields to add prefix to
 * @return array Prefixed form fields
 */
function nmgr_add_prefix($fields)
{
    $prefixed = array();
    foreach ($fields as $name => $args) {
        if ((isset($args[ 'prefix' ]) && !$args[ 'prefix' ]) || false !== strpos($name, nmgr()->prefix)) {
            $prefixed[ $name ] = $args;
            continue;
        }
        $prefixed[ nmgr()->prefix . $name ] = $args;
    }
    return $prefixed;
}

/**
 * Remove plugin prefix from supplied fields
 *
 * The function removes the prefix from field keys and values if they are strings or arrays of strings
 *
 * @param string | array $data Fields to remove prefix from
 * @return array Fields with prefix removed from keys and values
 */
function nmgr_remove_prefix($data)
{
    $data_array = ( array ) $data;
    $new_data = array();

    foreach ($data_array as $key => $value) {
        $key = str_replace(nmgr()->prefix, '', $key);
        $value = is_string($value) ? str_replace(nmgr()->prefix, '', $value) : $value;

        if (is_array($value)) {
            $value = array_map(function ($val) {
                return is_string($val) ? str_replace(nmgr()->prefix, '', $val) : $val;
            }, $value);
        }
        $new_data[ $key ] = $value;
    }
    return $new_data;
}

/**
 * Get a single svg icon file unmodified from the icon directory
 *
 * @since 2.1.0 $path parameter was added
 *
 * @param string $icon_name The name of the icon e.g. user.
 * @param string $path The path to the icon file.
 *
 * @return string Icon html
 */
function nmgr_get_svg_file($icon_name, $path = '')
{
    $iconfile = ($path ? trailingslashit($path) : nmgr()->path . 'assets/svg/') . "{$icon_name}.svg";
    if (file_exists($iconfile)) {
        ob_start();
        include $iconfile;
        return ob_get_clean();
    }
    return false;
}

/**
 * Include the svg sprite file in a page
 */
function nmgr_include_sprite_file()
{
    $sprite_file = nmgr()->path . 'assets/svg/sprite.svg';
    if (file_exists($sprite_file)) {
        include_once $sprite_file;
    }
}

/**
 * Checks whether the post content contains any of the specified shortcodes
 *
 * This function is a simply modification of woocommerce's wc_post_content_has_shortcode
 * function to allow checking for multiple shortcodes at once
 *
 * @param string|array $tags Shortcode tag(s) to check for
 * @see wc_post_content_has_shortcode()
 * @return boolean
 */
function nmgr_post_content_has_shortcodes($tags)
{
    foreach (( array ) $tags as $tag) {
        if (wc_post_content_has_shortcode($tag)) {
            return true;
        }
    }
    return false;
}

if (!function_exists('nmgr_get_account_tabs')) {

    /**
     * Tabs used on account page
     *
     * @return array
     */
    function nmgr_get_account_tabs()
    {
        return NMGR_Templates::get_account_tabs();
    }
}

if (!function_exists('nmgr_get_no_wishlist_placeholder')) {

    /**
     * Placeholder content displayed on wishlist account tabs when no wishlist exists
     *
     * @param string $tab The name of the tab.
     * @param boolean $echo Whether to echo the content. Default false.
     */
    function nmgr_get_no_wishlist_placeholder($tab = '', $echo = false)
    {
        switch ($tab) {
            case 'overview':
                $icon = 'heart';
                break;
            case 'items':
                $icon = 'cart-empty';
                break;
            case 'shipping':
                $icon = 'box-open';
                break;

            default:
                $icon = 'heart';
                break;
        }

        $svg = '';
        $svg_args = '';

        $icon = apply_filters('nmgr_no_wishlist_placeholder_svg_icon', $icon, $tab);

        if ($icon) {
            $svg_args = apply_filters('nmgr_no_wishlist_placeholder_svg_args', array(
                'icon' => $icon,
                'size' => nmgr()->post_thumbnail_size / 16, // convert px to em
                'fill' => '#f8f8f8',
                ), $tab);
        }

        if ($svg_args) {
            $svg = sprintf(
                '<div class="nmgr-no-wishlist-placeholder-svg nmgr-text-center">%s</div>',
                nmgr_get_svg($svg_args)
            );
        }

        $call_to_action = apply_filters('nmgr_no_wishlist_call_to_action', nmgr_get_template('account/call-to-action-no-wishlist.php', array( 'tab' => $tab )));

        $content = apply_filters('nmgr_no_wishlist_placeholder', $svg . $call_to_action, $tab);

        if ($echo) {
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $content;
        }
    }
}

if (!function_exists('nmgr_get_account_template')) {

    /**
     * Template for displaying a user's wishlist account information
     *
     * @param int|string|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|string|NMGR_Wishlist] optional. The id, slug or object of the wishlist to display account information for.
     * Default to current wishlist id in global context.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     * @return string Template html
     */
    function nmgr_get_account_template($atts = '', $echo = false)
    {
        if (!is_nmgr_enabled() || !is_nmgr_user()) {
            return;
        }

        $args = shortcode_atts(
            array(
                'id' => !is_array($atts) ? $atts : 0,
            ),
            $atts,
            'nmgr_account'
        );

        $wishlist_id = 0;

        if (is_numeric($args[ 'id' ])) {
            $wishlist_id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $wishlist_id = $args[ 'id' ]->get_id();
        } elseif (is_string($args[ 'id' ]) && !empty($args[ 'id' ])) {
            $wishlist_post = get_page_by_path(sanitize_text_field(wp_unslash($args[ 'id' ])), OBJECT, nmgr()->post_type);
            $wishlist_id = isset($wishlist_post->ID) ? absint($wishlist_post->ID) : $wishlist_id;
        } elseif (empty($args[ 'id' ])) {
            $wishlist_id = nmgr_get_current_wishlist_id();
        }

        if (0 < $wishlist_id && !nmgr_user_has_wishlist($wishlist_id)) {
            return;
        }

        ob_start();

        wc_maybe_define_constant('NMGR_ACCOUNT', true);

        $tabs = apply_filters('nmgr_account_tabs', nmgr_get_account_tabs());

        if (!empty($tabs) && isset($GLOBALS[ 'nmgr' ])) {
            $GLOBALS[ 'nmgr' ]->is_account_tab = true;
        }

        $vars = array(
            'wishlist' => nmgr_get_wishlist($wishlist_id, true),
            'tabs' => $tabs,
        );

        do_action('nmgr_before_account');

        echo apply_filters('nmgr_account_wishlist_template', nmgr_get_template('account/wishlist.php', $vars), $vars);

        do_action('nmgr_after_account');

        $template = apply_filters('nmgr_account_template', ob_get_clean());

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_account_wishlist_template')) {

    /**
     * Template for managing account information for a single wishlist
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_account_wishlist_template($atts = '', $echo = false)
    {
        if (!is_array($atts)) {
            $atts = ( array ) $atts;
        }

        $template = nmgr_get_account_template($atts, false);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_overview_template')) {

    /**
     * Wishlist overview information template
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     * - title [string] The title header to use for the template. Default none.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_overview_template($atts = '', $echo = false)
    {
        if (!is_nmgr_enabled() || !is_nmgr_user()) {
            return;
        }

        $args = shortcode_atts(
            array(
                'id' => is_array($atts) ? 0 : $atts,
                'title' => '',
            ),
            $atts,
            'nmgr_overview'
        );

        $id = 0;
        if (is_numeric($args[ 'id' ])) {
            $id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $id = $args[ 'id' ]->get_id();
        } elseif (empty($args[ 'id' ])) {
            $id = nmgr_get_current_wishlist_id();
        }

        if (0 < $id && !nmgr_user_has_wishlist($id)) {
            return;
        }

        wc_maybe_define_constant('NMGR_ACCOUNT_SECTION', true);

        $wishlist = nmgr_get_wishlist($id, true);

        $vars = array(
            'wishlist' => $wishlist,
            'title' => apply_filters('nmgr_overview_template_title', $args[ 'title' ]),
            'class' => 'woocommerce',
            'nonce' => wp_create_nonce('nmgr_manage_wishlist'),
        );

        if ($wishlist && $wishlist->get_event_date()) {
            $event_date = nmgr_get_datetime($wishlist->get_event_date());
            if ($event_date) {
                $current_time = current_time('Y-m-d');
                $diff = date_diff(new DateTime($current_time), new DateTime($event_date->format('Y-m-d')));
                $days = ( int ) $diff->format("%R%a");
                $abs_days = absint($days);
                $days_notice = '';

                if ($days > 0) {
                    $days_notice = _n('day to your event', 'days to your event', $abs_days, 'nm-gift-registry-lite');
                } elseif ($days < 0) {
                    $days_notice = _n('day after your event', 'days after your event', $abs_days, 'nm-gift-registry-lite');
                } else {
                    $abs_days = __('Your event is today', 'nm-gift-registry-lite');
                }
            } else {
                $abs_days = __('Unknown', 'nm-gift-registry-lite');
                $days_notice = __('days to your event', 'nm-gift-registry-lite');
            }

            $vars[ 'days_notice' ] = $days_notice;
            $vars[ 'days' ] = $abs_days;
        }

        $template = apply_filters('nmgr_overview_template', nmgr_get_template('account/overview.php', $vars), $vars);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_profile_template')) {

    /**
     * Wishlist profile form template
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     * - title [string] The title header to use for the template. Default '{wishlist type title} details'.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_profile_template($atts = '', $echo = false)
    {
        if (!is_nmgr_enabled() || !is_nmgr_user()) {
            return;
        }

        $args = shortcode_atts(
            array(
                'id' => is_array($atts) ? 0 : $atts,
                'title' => '',
            ),
            $atts,
            'nmgr_profile'
        );

        $id = 0;
        if (is_numeric($args[ 'id' ])) {
            $id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $id = $args[ 'id' ]->get_id();
        } elseif (empty($args[ 'id' ])) {
            $id = nmgr_get_current_wishlist_id();
        }

        if (0 < $id && !nmgr_user_has_wishlist($id)) {
            return;
        }

        wc_maybe_define_constant('NMGR_ACCOUNT_SECTION', true);

        $the_wishlist = nmgr_get_wishlist($id, true);
        $wishlist = $the_wishlist ? $the_wishlist : new NMGR_Wishlist();

        $vars = array(
            'wishlist' => $wishlist,
            'form' => new NMGR_Form($wishlist->get_id()),
            'title' => apply_filters('nmgr_profile_template_title', $args[ 'title' ]),
            'class' => 'woocommerce',
            'nonce' => wp_create_nonce('nmgr_manage_wishlist'),
        );

        $template = apply_filters('nmgr_profile_template', nmgr_get_template('account/profile.php', $vars), $vars);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_items_template')) {

    /**
     * Wishlist items template
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     * - title [string] The title header to use for the template. Default none.
     * - editable [bool] Whether the template can be edited. Default true.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_items_template($atts = '', $echo = false)
    {
        $args = shortcode_atts(
            array(
                'id' => is_array($atts) ? 0 : $atts,
                'editable' => true,
                'add_to_cart' => false,
                'title' => ''
            ),
            $atts,
            'nmgr_items'
        );

        $id = 0;
        if (is_numeric($args[ 'id' ])) {
            $id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $id = $args[ 'id' ]->get_id();
        } elseif (empty($args[ 'id' ])) {
            $id = nmgr_get_current_wishlist_id();
        }

        $editable = filter_var($args[ 'editable' ], FILTER_VALIDATE_BOOLEAN);
        $wishlist = nmgr_get_wishlist($id, true);

        if (!is_admin() &&
            ((!$editable && !$wishlist && !is_nmgr_enabled()) ||
            ($editable && (!is_nmgr_enabled() || (0 < $id && !nmgr_user_has_wishlist($id)))))) {
            return;
        }

        wc_maybe_define_constant('NMGR_ACCOUNT_SECTION', true);

        $class = array(
            'woocommerce',
            $editable ? 'editable' : '',
            $wishlist && $wishlist->is_fulfilled() ? 'wishlist-fulfilled' : '',
            is_nmgr_wishlist() ? 'single' : '',
            is_nmgr_admin() ? 'admin' : '',
            is_nmgr_account() ? 'account' : '',
        );

        $vars = array(
            'wishlist' => $wishlist,
            'items' => $wishlist ? $wishlist->get_items() : '',
            'title' => apply_filters('nmgr_items_template_title', $args[ 'title' ]),
            'items_args' => array(
                'editable' => $editable,
                'add_to_cart' => filter_var($args[ 'add_to_cart' ], FILTER_VALIDATE_BOOLEAN),
            ),
            'class' => implode(' ', array_filter($class)),
            'nonce' => wp_create_nonce('nmgr_manage_wishlist'),
        );

        $template = apply_filters('nmgr_items_template', nmgr_get_template('account/items.php', $vars), $vars);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_shipping_template')) {

    /**
     * Wishlist shipping template
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     * - title [string] The title header to use for the template. Default 'Shipping Details'.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_shipping_template($atts = '', $echo = false)
    {
        if (!is_nmgr_enabled() || !is_nmgr_user() || !nmgr_get_option('enable_shipping')) {
            return;
        }

        $args = shortcode_atts(
            array(
                'id' => is_array($atts) ? 0 : $atts,
                'title' => '',
            ),
            $atts,
            'nmgr_shipping'
        );

        $id = 0;
        if (is_numeric($args[ 'id' ])) {
            $id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $id = $args[ 'id' ]->get_id();
        } elseif (empty($args[ 'id' ])) {
            $id = nmgr_get_current_wishlist_id();
        }

        if (0 < $id && !nmgr_user_has_wishlist($id)) {
            return;
        }

        wc_maybe_define_constant('NMGR_ACCOUNT_SECTION', true);

        $wishlist = nmgr_get_wishlist($id, true);

        $vars = array(
            'wishlist' => $wishlist,
            'title' => apply_filters('nmgr_shipping_template_title', $args[ 'title' ]),
            'class' => 'woocommerce',
            'form' => $wishlist ? new NMGR_Form($wishlist->get_id()) : '',
            'customer' => $wishlist ? new WC_Customer($wishlist->get_user_id()) : '',
            'nonce' => wp_create_nonce('nmgr_manage_wishlist'),
        );

        $template = apply_filters('nmgr_shipping_template', nmgr_get_template('account/shipping.php', $vars), $vars);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

/**
 * Template for displaying a single wishlist
 *
 * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
 * Currently accepted $atts attributes if array:
 * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
 *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
 *
 * @param boolean $echo Whether to echo the template. Default false.
 *
 * @return string Template html
 */
function nmgr_get_wishlist_template($atts = '', $echo = false)
{
    $args = shortcode_atts(
        array(
            'id' => is_array($atts) ? 0 : $atts,
        ),
        $atts,
        'nmgr_wishlist'
    );

    $id = 0;
    if (is_numeric($args[ 'id' ])) {
        $id = absint($args[ 'id' ]);
    } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
        $id = $args[ 'id' ]->get_id();
    } elseif (empty($args[ 'id' ])) {
        $id = nmgr_get_current_wishlist_id();
    }

    if (!$id) {
        return;
    }

    $query_args = array(
        'posts_per_page' => 1,
        'post_type' => nmgr()->post_type,
        'post_status' => nmgr_get_post_statuses(),
        'p' => $id,
    );

    $single = new WP_Query($query_args);

    if (!$single->have_posts() ||
        !is_nmgr_enabled($single->post->post_author) ||
        ('private' === $single->post->post_status &&
        absint(get_current_user_id()) !== absint($single->post->post_author))
    ) {
        return;
    }

    ob_start();

    global $wp_query;

    $previous_wp_query = $wp_query;
    $wp_query = $single;

    while ($single->have_posts()) {
        $single->the_post();
        nmgr_template('content-single-nm_gift_registry.php');
    }

    $wp_query = $previous_wp_query;
    wp_reset_postdata();

    $template = '<div class="woocommerce">' . ob_get_clean() . '</div>';

    if ($echo) {
        echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $template;
    }
}

if (!function_exists('nmgr_get_share_template')) {

    /**
     * Wishlist sharing links template
     *
     * @param int|NMGR_Wishlist|array $atts Attributes needed to compose the template.
     * Currently accepted $atts attributes if array:
     * - id [int|NMGR_Wishlist] Wishlist id or instance of NMGR_Wishlist.
     *   Default none - id is taken from the global context if present @see nmgr_get_current_wishlist_id().
     * - title [string] The title header to use for the template. Default none.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_share_template($atts = '', $echo = false)
    {
        $args = shortcode_atts(
            array(
                'id' => is_array($atts) ? 0 : $atts,
                'title' => '',
            ),
            $atts,
            'nmgr_share'
        );

        $id = 0;
        if (is_numeric($args[ 'id' ])) {
            $id = absint($args[ 'id' ]);
        } elseif (is_a($args[ 'id' ], 'NMGR_Wishlist')) {
            $id = $args[ 'id' ]->get_id();
        } elseif (empty($args[ 'id' ])) {
            $id = nmgr_get_current_wishlist_id();
        }

        // We should show the sharing links if at least one share option is enabled
        $options = nmgr_get_option();
        $to_share = false;

        foreach ($options as $key => $value) {
            if (false !== strpos($key, 'share_on') && !empty($value)) {
                $to_share = true;
                break;
            }
        }

        if (!$to_share) {
            return;
        }

        $wishlist = nmgr_get_wishlist($id, true);

        if (!$wishlist || !is_nmgr_enabled($wishlist->get_user_id()) || ('publish' !== $wishlist->get_status())) {
            return;
        }

        $vars = array(
            'wishlist' => $wishlist,
            'title' => apply_filters('nmgr_share_template_title', $args[ 'title' ]),
        );

        $template = apply_filters('nmgr_share_template', nmgr_get_template('account/sharing.php', $vars), $vars);

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

if (!function_exists('nmgr_get_enable_wishlist_form')) {

    /**
     * Allows individual users to enable the wishlist module for their use
     *
     * @param boolean $echo Whether to echo the form. Default false.
     */
    function nmgr_get_enable_wishlist_form($echo = false)
    {
        if (nmgr_get_option('user_enable_wishlist') && is_user_logged_in()) {
            $template = apply_filters('nmgr_enable_wishlist_form', nmgr_get_template('form-enable-wishlist.php'));

            if ($echo) {
                echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } else {
                return $template;
            }
        }
    }
}

/**
 * Add a product to a wishlist
 *
 * @param NMGR_Wishlist $wishlist The wishlist object
 * @param WC_Product $product The product object
 * @param int $quantity Quantity to be added
 * @param int $favourite Product favourite in wishlist
 * @param array $variations Product variations
 * @return array Array of product id to quantity added to wishlist
 * @throws Exception
 */
function nmgr_add_to_wishlist($wishlist, $product, $quantity, $favourite = null, $variations = array())
{
    if ($quantity < 1 || !$product || 'trash' === $product->get_status()) {
        return false;
    }

    // The product is not purchasable
    if (!$product->is_purchasable()) {
        throw new Exception(sprintf(
            /* translators: 1: product name, 2: wishlist type title */
            __('Sorry &quot;%1$s&quot; cannot be added to your %2$s as it is not purchasable.', 'nm-gift-registry-lite'),
            $product->get_name(),
            esc_html(nmgr_get_type_title())
        ));
    }

    // The product is not in stock
    if (!$product->is_in_stock()) {
        throw new Exception(sprintf(
            /* translators: 1: product name, 2: wishlist type title */
            __('You cannot add &quot;%s&quot; to your %2$a because the item is out of stock.', 'nm-gift-registry-lite'),
            $product->get_name(),
            esc_html(nmgr_get_type_title())
        ));
    }

    // The quantity added exceeds the product stock
    if (!$product->has_enough_stock($quantity)) {
        throw new Exception(sprintf(
            /* translators: 1: product name, 2: wishlist type title, 3: product quantity */
            __('You cannot add that amount of &quot;%1$s&quot; to your %2$s because there is not enough stock (%3$s remaining).', 'nm-gift-registry-lite'),
            $product->get_name(),
            esc_html(nmgr_get_type_title()),
            wc_format_stock_quantity_for_display($product->get_stock_quantity(), $product)
        ));
    }

    $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    $variation_id = $product->is_type('variation') ? $product->get_id() : 0;
    $unique_id = $wishlist->generate_unique_id($product_id, $variation_id, $variations);

    /**
     * If the wishlist already has the item with quantity equal to the stock quantity
     * let the user know he cannot add the item again
     */
    if ($wishlist->has_item($unique_id) &&
        $product->get_stock_quantity() === $wishlist->get_item($unique_id)->get_quantity()) {
        throw new Exception(
            sprintf(
                /* translators:
                 * 1: quantity to add, 2: product name, 3: wishlist type title,
                 * 4: product quantity, 5: product quantity in wishlist, 6: wishlist type title
                 */
                __('You cannot add %1$s of &quot;%2$s&quot; to your %3$s &mdash; we have %4$s in stock and you already have %5$s in your %6$s.', 'nm-gift-registry-lite'),
                $quantity,
                $product->get_name(),
                esc_html(nmgr_get_type_title()),
                $product->get_stock_quantity(),
                $wishlist->get_item($unique_id)->get_quantity(),
                esc_html(nmgr_get_type_title())
            )
        );
    }

    if ($wishlist->add_item($product, $quantity, $favourite, $variations)) {
        do_action('nmgr_added_to_wishlist', $product, $quantity, $favourite, $variations, $wishlist);
        return array( $product->get_id() => $quantity );
    } else {
        throw new Exception(sprintf(
            /* translators: 1: product name, 2: wishlist type title */
            __('%1$s could not be added to your %2$s', 'nm-gift-registry-lite'),
            $product->get_name(),
            esc_html(nmgr_get_type_title())
        ));
    }
}

/**
 * Get the button for adding a product to the wishlist
 *
 * @param int|WC_Product $atts Attributes needed to compose the button
 * Currently accepted $atts attributes if array:
 * - id [int|WC_Product] Product id or instance of WC_Product.
 *   Default none - id is taken from the global product variable if present.
 *
 * @param $echo boolean Whether to echo the template. Default false.
 *
 * @since 1.0.1 the $echo parameter was added.
 *
 * @return string Button html
 */
function nmgr_get_add_to_wishlist_button($atts = false, $echo = false)
{
    global $product;

    if (!is_array($atts) && !empty($atts)) {
        $product = wc_get_product($atts);
    } elseif (is_array($atts) && isset($atts[ 'id' ])) {
        $product = wc_get_product($atts[ 'id' ]);
    } else {
        $product = wc_get_product($product);
    }

    if (
        (!is_nmgr_user() && !nmgr_get_option('add_to_wishlist_guests')) ||
        !is_nmgr_enabled() ||
        !$product
    ) {
        return;
    }

    /**
     * Whether to show the button conditionally
     * (This can be modified by a filter - see 'nmgr_show_add_to_wishlist_button')
     */
    $show_button = true;

    // Don't show the button for external products
    if ($product->is_type('external')) {
        $show_button = false;
    }

    // Don't show the button for simple or variable products that are out of stock or not purchasable
    if ($product->is_type(array( 'simple', 'variable' )) && (!$product->is_purchasable() || !$product->is_in_stock())) {
        $show_button = false;
    }

    // Don't show the button for grouped products if all are out of stock or not purchasable
    if ($product->is_type('grouped')) {
        $grouped_products = array_filter(
            array_map('wc_get_product', $product->get_children()),
            'wc_products_array_filter_visible_grouped'
        );

        if (!$grouped_products) {
            return;
        }

        $maybe_show = count($grouped_products);

        foreach ($grouped_products as $grouped_product) {
            if (!$grouped_product->is_purchasable() || $grouped_product->has_options() || !$grouped_product->is_in_stock()) {
                --$maybe_show;
            }
        }

        $show_button = ( bool ) $maybe_show;
    }

    // Should we show the add to wishlist button
    if (!apply_filters('nmgr_show_add_to_wishlist_button', $show_button, $product)) {
        return;
    }

    /**
     * At this point, we are showing the wishlist button, so run through all the use cases
     */
    // Get the user's wishlist
    $wishlist = nmgr_get_wishlist('', true);

    // How should we create a wishlist for users without any wishlist when the add to wishlist button is clicked?
    $create_wishlist = false;
    if (!$wishlist) {
        if (apply_filters('nmgr_redirect_to_create_wishlist', false)) {
            $create_wishlist = 'redirect';
        } elseif (nmgr_get_option('default_wishlist_title')) {
            $create_wishlist = 'auto';
        } else {
            $create_wishlist = 'modal';
        }
    }

    // Set class for the add to wishlist button position on single and archive pages
    $wrapper_class = array();
    $thumbnail_positions = array( 'thumbnail_top_left', 'thumbnail_top_right', 'thumbnail_bottom_left', 'thumbnail_bottom_right' );
    $archive_position = nmgr_get_option('add_to_wishlist_button_position_archive');
    $single_position = nmgr_get_option('add_to_wishlist_button_position_single');
    $button_location = is_product() ? $single_position : (is_nmgr_shop_loop() ? $archive_position : null);

    if (in_array($button_location, $thumbnail_positions)) {
        $wrapper_class[] = 'on-thumbnail';

        switch ($button_location) {
            case 'thumbnail_top_left':
                $wrapper_class[] = 'nmgr-left';
                $wrapper_class[] = 'nmgr-top';
                break;
            case 'thumbnail_top_right':
                $wrapper_class[] = 'nmgr-right';
                $wrapper_class[] = 'nmgr-top';
                break;
            case 'thumbnail_bottom_right':
                $wrapper_class[] = 'nmgr-right';
                $wrapper_class[] = 'nmgr-bottom';
                break;
            case 'thumbnail_bottom_left':
                $wrapper_class[] = 'nmgr-left';
                $wrapper_class[] = 'nmgr-bottom';
                break;
        }
    }

    // Set default arguments for the button
    $defaults = array(
        'wishlist' => $wishlist,
        'wrapper_class' => $wrapper_class,
        'form_class' => array(
            'nmgr-add-to-wishlist-form',
            "nmgr-add-to-wishlist-form-{$product->get_id()}",
            nmgr_user_has_product_in_wishlist($product) ? 'product-in-wishlist' : '',
            'product-type-' . $product->get_type(),
            is_nmgr_shop_loop() ? 'archive' : 'single',
            'nmgr-ajax-add-to-wishlist',
        ),
        'form_attributes' => array(
            'data-nmgr_product_id' => $product->get_id(),
            'data-create_wishlist' => $create_wishlist,
        ),
        'button_class' => array(
            !is_nmgr_shop_loop() ? 'alt' : '',
            'nmgr-add-to-wishlist-button',
        ),
        'button_attributes' => array(
            'aria-label' => sprintf(
                /* translators: 1: product name, 2: wishlist type title */
                __('Add &quot;%1$s&quot; to my %2$s', 'nm-gift-registry-lite'),
                $product->get_name(),
                esc_html(nmgr_get_type_title())
            ),
            'rel' => 'nofollow',
            'role' => 'button',
            'tabindex' => 0,
        ),
        'button_text' => nmgr_get_option('add_to_wishlist_button_text'),
        'button_icon' => nmgr_get_svg(array(
            'icon' => 'heart',
            'size' => .75,
            'fill' => '#ccc',
            'class' => 'nmgr-animate in-wishlist-icon in-wishlist',
            'style' => 'margin-left:0.1875em;',
            'title' => nmgr_get_product_in_wishlist_title_attribute($product),
        )),
    );

    // Should we redirect when the add to wishlist button is clicked?
    $redirect = false;

    // if we don't have a valid user but we are showing the wishlist button, redirect to login page with notice
    if (!is_nmgr_user() && nmgr_get_option('add_to_wishlist_guests')) {
        $redirect = true;
        $defaults[ 'permalink' ] = wc_get_page_permalink('myaccount');
        $defaults[ 'permalink_args' ] = array(
            'nmgr-notice' => 'require-login',
            'nmgr-redirect' => $_SERVER[ 'REQUEST_URI' ],
        );
    } else {
        /**
         * if we have a valid user who has no wishlists to add products to and the admin wants to
         * redirect him to create a wishlist instead of using the modal, setup the redirect
         */
        if ('redirect' === $create_wishlist) {
            $redirect = true;
            $defaults[ 'permalink' ] = nmgr_get_account_url();
            $defaults[ 'permalink_args' ] = array(
                'nmgr-notice' => 'create-wishlist',
            );
        } elseif (is_nmgr_shop_loop() && $product->is_type(array( 'variable' ))) {
            /**
             * The user is logged in and if he has wishlists to add products to
             * For variable and grouped products on archive pages redirect to actual product page with notice
             */
            $redirect = true;
            $defaults[ 'permalink' ] = $product->get_permalink();
            $defaults[ 'permalink_args' ] = array(
                'nmgr-notice' => 'select-product',
                'nmgr-pt' => $product->get_type(),
            );
        }
    }

    if (is_nmgr_shop_loop() && $product->is_type(array( 'variable' ))) {
        $defaults[ 'button_text' ] .= ' *';
    }

    $defaults[ 'form_class' ][] = $redirect ? 'redirect' : '';
    $defaults[ 'button_text' ] = apply_filters('nmgr_add_to_wishlist_button_text', $defaults[ 'button_text' ]);
    $args = wp_parse_args(apply_filters('nmgr_add_to_wishlist_button_args', $defaults, $product), $defaults);

    $args[ 'wrapper_class' ] = isset($args[ 'wrapper_class' ]) ? implode(' ', array_filter(( array ) $args[ 'wrapper_class' ])) : '';
    $args[ 'form_class' ] = isset($args[ 'form_class' ]) ? implode(' ', array_filter(( array ) $args[ 'form_class' ])) : '';
    $args[ 'form_attributes' ] = isset($args[ 'form_attributes' ]) ? wc_implode_html_attributes(( array ) $args[ 'form_attributes' ]) : '';
    $args[ 'button_class' ] = isset($args[ 'button_class' ]) ? implode(' ', array_filter(( array ) $args[ 'button_class' ])) : '';

    if (isset($args[ 'button_attributes' ][ 'aria-label' ])) {
        $args[ 'button_attributes' ][ 'aria-label' ] = wp_strip_all_tags($args[ 'button_attributes' ][ 'aria-label' ]);
    }

    $button_params = array(
        'class' => esc_attr($args[ 'button_class' ]),
        'attributes' => wc_implode_html_attributes(( array ) $args[ 'button_attributes' ]),
        'text' => isset($args[ 'button_text' ]) ? esc_html($args[ 'button_text' ]) : '',
        'icon' => isset($args[ 'button_icon' ]) ? $args[ 'button_icon' ] : null
    );

    // Get the add-to-wishlist button type
    $button_type = nmgr_get_option('add_to_wishlist_button_type', 'button');
    $button_html = '';

    switch ($button_type) {
        case 'icon-heart':
            $not_in_wishlist_svg_args = array_merge(array(
                'icon' => 'heart-empty',
                'size' => 2,
                'class' => 'not-in-wishlist ' . $button_params[ 'class' ],
                'fill' => 'currentColor',
                'title' => nmgr_get_option('add_to_wishlist_button_text'),
                ), $args[ 'button_attributes' ]);

            $in_wishlist_svg_args = array_merge(array(
                'icon' => 'heart',
                'size' => 2,
                'class' => 'nmgr-animate in-wishlist ' . $button_params[ 'class' ],
                'fill' => 'currentColor',
                'title' => nmgr_get_product_in_wishlist_title_attribute($product),
                ), $args[ 'button_attributes' ]);

            $button_html = nmgr_get_svg($not_in_wishlist_svg_args) . nmgr_get_svg($in_wishlist_svg_args);
            break;
    }

    if (!$button_html) {
        if (is_nmgr_shop_loop()) {
            $button_html = sprintf(
                "<a href='#' class='button %s' %s>%s</a>",
                $button_params[ 'class' ],
                $button_params[ 'attributes' ],
                $button_params[ 'text' ] . $button_params[ 'icon' ]
            );
        } else {
            $button_html = sprintf(
                "<button type='submit' class='button %s' %s>%s</button>",
                $button_params[ 'class' ],
                $button_params[ 'attributes' ],
                $button_params[ 'text' ] . $button_params[ 'icon' ]
            );
        }
    }

    /**
     * Filter the add to wishlist button
     *
     * @param string $button_template The button html
     * @param array $button_params Array keys of parameters used to compose the button html
     * 					and their values. Default parameters: id, class, attributes, text, icon
     * @param array $args Arguments used to compose the overall button template
     */
    $args[ 'button' ] = apply_filters('nmgr_add_to_wishlist_button', $button_html, $button_params, $args);

    $template = !empty($args[ 'button' ]) ? nmgr_get_template('add-to-wishlist/form.php', $args) : '';

    if ($echo) {
        echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $template;
    }
}

/**
 * Displays the button for adding a product to the wishlist
 *
 * @param int|WC_Product $atts Attributes needed to compose the button
 * Currently accepted $atts attributes if array:
 * - id [int|WC_Product] Product id or instance of WC_Product. Default none - id is taken from the global product variable context if present.
 * @return string button html
 */
function nmgr_add_to_wishlist_button($atts = false)
{
    nmgr_get_add_to_wishlist_button($atts, true);
}

/**
 * Get the possible titles that can be used for the wishlist type
 *
 * @return array
 */
function nmgr_get_type_titles()
{
    $titles = array(
        'gift_registry' => array(
            'singular' => __('gift registry', 'nm-gift-registry-lite'),
            'plural' => __('gift registries', 'nm-gift-registry-lite')
        ),
        'wishlist' => array(
            'singular' => __('wishlist', 'nm-gift-registry-lite'),
            'plural' => __('wishlists', 'nm-gift-registry-lite')
        ),
        'gift_list' => array(
            'singular' => __('gift list', 'nm-gift-registry-lite'),
            'plural' => __('gift lists', 'nm-gift-registry-lite')
        ),
        'list' => array(
            'singular' => __('list', 'nm-gift-registry-lite'),
            'plural' => __('lists', 'nm-gift-registry-lite')
        )
    );

    return apply_filters('nmgr_type_titles', $titles);
}

/**
 * Get the current title used for the wishlist type
 *
 * @param string $formatting How to format the title (default is lowercase).
 * Possible values are
 * - 'c': Capitalize
 * - 'u': Uppercase
 * - 'cf': Capitalize first word
 *
 * @param boolean $pluralize Whether to get the plural form of the title. Default false
 *
 * @return string
 */
function nmgr_get_type_title($formatting = '', $pluralize = false)
{
    $type_title = '';
    $key = nmgr_get_option('type_title', 'gift_registry');
    $type_titles = nmgr_get_type_titles();

    if (isset($type_titles[ $key ])) {
        $singular = isset($type_titles[ $key ][ 'singular' ]) ? $type_titles[ $key ][ 'singular' ] : '';
        $plural = isset($type_titles[ $key ][ 'plural' ]) ? $type_titles[ $key ][ 'plural' ] : '';
        $type_title = !$pluralize ? $singular : $plural;
    }

    // Default list title
    if (!$type_title) {
        $type_title = !$pluralize ? __('gift registry', 'nm-gift-registry-lite') : __('gift registries', 'nm-gift-registry-lite');
    }

    switch ($formatting) {
        case 'cf': // capitalize first word
            $type_title = ucfirst($type_title);
            break;

        case 'c': // capitalize
            $type_title = ucwords($type_title);
            break;

        case 'u': // uppercase
            $type_title = strtoupper($type_title);
            break;

        default: //lowercase
            $type_title = strtolower($type_title);
            break;
    }

    return apply_filters('nmgr_type_title', $type_title, $formatting, $pluralize, $type_titles);
}

/**
 * Returns the admin url base page for the plugin
 */
function nmgr_get_admin_url()
{
    return admin_url('edit.php?post_type=' . nmgr()->post_type);
}

/**
 * Return a standard plugin tooltip notification
 *
 * @param string $title The notification message to be in the tooltip
 * @return string
 */
function nmgr_get_help_tip($title)
{
    return nmgr_get_svg(array(
        'icon' => 'info',
        'title' => $title,
        'fill' => 'currentColor',
        'style' => 'margin-left:7px;',
        'size' => 0.75,
        'class' => 'nmgr-tip nmgr-help-tip nmgr-cursor-help',
        ));
}

/**
 * Order statuses used by the plugin to determine if a payment is cancelled
 *
 * These are the same statuses used by woocommerce to
 * determine whether to increase stock levels
 *
 * Default:
 * - cancelled
 * - pending
 *
 * @return array
 */
function nmgr_get_payment_cancelled_order_statuses()
{
    return apply_filters('nmgr_payment_cancelled_order_statuses', array( 'cancelled', 'pending' ));
}

/**
 * Get the url where items can be added to the wishlist
 *
 * This should typically be the shop page url
 *
 * @return string
 */
function nmgr_get_add_items_url()
{
    return apply_filters('nmgr_add_items_url', wc_get_page_permalink('shop'));
}

/**
 * Svg tags allowed by the plugin
 *
 * @return array
 */
function nmgr_allowed_svg_tags()
{
    return array(
        'svg' => array(
            'id' => true,
            'role' => true,
            'width' => true,
            'height' => true,
            'class' => true,
            'style' => true,
            'fill' => true,
            'xmlns' => true,
            'viewbox' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'data-notice' => true, // may be deprecated soon. Used temporarily.
        ),
        'use' => array(
            'xlink:href' => true
        ),
        'title' => array(
            'data-title' => true
        ),
        'path' => array(
            'fill' => true,
            'fill-rule' => true,
            'd' => true,
            'transform' => true,
        ),
        'polygon' => array(
            'fill' => true,
            'fill-rule' => true,
            'points' => true,
            'transform' => true,
            'focusable' => true,
        ),
    );
}

/**
 * Sanitize content to allow for svg tags used by the plugin
 *
 * @param string $data Content to sanitize
 * @return string Sanitized content
 */
function nmgr_kses_svg($data)
{
    return wp_kses($data, nmgr_allowed_svg_tags());
}

/**
 * Sanitize content to allow for HTML tags used by WordPress in post content and svg tags used by the plugin
 *
 * This function is simply used to allow the plugin svg tags to be used alongside
 * WordPress allowed HTML tags in post content.
 *
 * @param string $data Content to sanitize
 * @return string Sanitized content
 */
function nmgr_kses_post($data)
{
    return wp_kses($data, array_merge(wp_kses_allowed_html('post'), nmgr_allowed_svg_tags()));
}

/**
 * Check if a wishlist belongs to a particular user
 *
 * @param int|NMGR_Wishlist $wishlist_id The wishlist id or object.
 * @param int|string $user_id The user id. Optional. Defaults to current logged in user or guest.
 * @return boolean True if user has the wishlist. False if not.
 */
function nmgr_user_has_wishlist($wishlist_id, $user_id = '')
{
    $id = 0;
    if (is_numeric($wishlist_id) && 0 < $wishlist_id) {
        $id = $wishlist_id;
    } elseif (is_a($wishlist_id, 'NMGR_Wishlist')) {
        $id = $wishlist_id->get_id();
    }

    if ($id) {
        $wishlists = nmgr_get_user_wishlists($user_id);
        if (!empty($wishlists)) {
            $wishlist_ids = array_map(function ($wishlist) {
                return $wishlist->get_id();
            }, $wishlists);

            return in_array($id, $wishlist_ids);
        }
    }
    return false;
}

if (!function_exists('nmgr_maybe_show_required_shipping_address_notice')) {
    function nmgr_maybe_show_required_shipping_address_notice($wishlist)
    {
        if (!$wishlist || is_nmgr_admin_request()) {
            return;
        }

        if (nmgr_get_option('shipping_address_required') && !$wishlist->has_shipping_address()) {
            /* translators: %s: wishlist type title */
            $message = sprintf(__('The shipping address for this %s is required before you can add items to it.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));


            $default_set_shipping_address_url = is_nmgr_account_tab() ? '#nmgr-tab-shipping' : '';
            $set_shipping_address_url = apply_filters('nmgr_set_shipping_address_url', $default_set_shipping_address_url, $wishlist);

            if ($set_shipping_address_url) {
                $message .= sprintf(
                    '<a class="button nmgr-go-to-tab nmgr-call-to-action-btn" href="%1$s">%2$s</a>',
                    esc_url($set_shipping_address_url),
                    esc_html__('Set now', 'nm-gift-registry-lite')
                );
            }

            wc_print_notice($message, 'notice');
        }
    }
}

/**
 * Check whether the cart has items belonging to a particular wishlist
 *
 * If no wishlist id is supplied, the function checks for the first wishlist that may have an item in the cart.
 *
 * @since 1.0.3
 * @deprecated since 2.1.0. Use 'nmgr_get_wishlist_in_cart' instead.
 * @param int $wishlist_id The wishlist id to check for.
 * @return mixed The wishlist id if the cart has the wishlist or 0 if the cart doesn't.
 */
function nmgr_cart_has_wishlist($wishlist_id = '')
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'nmgr_get_wishlist_in_cart');
    return nmgr_get_wishlist_in_cart($wishlist_id);
}

/**
 * Check whether the cart has items belonging to a particular wishlist
 *
 * If no wishlist id is supplied, the function checks for the first wishlist that may have an item in the cart.
 *
 * @since 2.1.0
 * @param int $wishlist_id The wishlist id to check for.
 * @return mixed The wishlist id if the cart has the wishlist or 0 if the cart doesn't.
 */
function nmgr_get_wishlist_in_cart($wishlist_id = '')
{
    if (is_a(wc()->cart, 'WC_Cart') && !WC()->cart->is_empty()) {
        $id = 0;
        $cart = WC()->cart->get_cart();

        foreach ($cart as $cart_item) {
            if (isset($cart_item[ 'nm_gift_registry' ])) {
                if ($wishlist_id &&
                    (absint($wishlist_id) === absint($cart_item[ 'nm_gift_registry' ][ 'wishlist_id' ])) &&
                    nmgr_get_wishlist($wishlist_id, true)) {
                    $id = $wishlist_id;
                    break;
                } elseif (!$wishlist_id && nmgr_get_wishlist($cart_item[ 'nm_gift_registry' ][ 'wishlist_id' ], true)) {
                    $id = $cart_item[ 'nm_gift_registry' ][ 'wishlist_id' ];
                    break;
                }
            }
        }
        return apply_filters('nmgr_get_wishlist_in_cart', $id, $cart);
    }
    return 0;
}

if (!function_exists('nmgr_get_search_results_template')) {

    /**
     * Get the template for outputting wishlist search results
     *
     * This function or the shortcode attached to it should be used after wp_loaded hook
     * as that is when the wp_query global exists.
     *
     * @since 1.0.3
     * @param array $atts Attributes needed to compose the template. added @since 1.0.4
     * @param bool $echo Whether to echo or return the template. added @since 1.0.4
     */
    function nmgr_get_search_results_template($atts = '', $echo = false)
    {
        global $wp_query;

        if (!is_a($wp_query, 'WP_Query')) {
            return;
        }

        $args = shortcode_atts(
            array(
                'show_title' => true,
                'show_post_count' => true,
                'show_results_if_empty' => false,
            ),
            $atts,
            'nmgr_search_results'
        );

        $template_args = filter_var_array($args, array(
            'show_title' => FILTER_VALIDATE_BOOLEAN,
            'show_post_count' => FILTER_VALIDATE_BOOLEAN,
            'show_results_if_empty' => FILTER_VALIDATE_BOOLEAN,
            ));

        $query_args = is_nmgr_search() ? $wp_query->query_vars : array();

        if (isset($query_args[ 'nmgr_s' ]) || (!isset($query_args[ 'nmgr_s' ]) && $template_args[ 'show_results_if_empty' ])) {
            $query_args = array();
            $query_args[ 's' ] = get_query_var('nmgr_s');
            $query_args[ 'post_type' ] = 'nm_gift_registry';
            $query_args[ 'paged' ] = get_query_var('paged') ? get_query_var('paged') : 1;
        }

        ob_start();

        $wp_query = new WP_Query($query_args);

        do_action('nmgr_search_results_header', $template_args);

        if ($wp_query->have_posts()) :

            while ($wp_query->have_posts()) :

                $wp_query->the_post();

        nmgr_template('content-search-nm_gift_registry.php');

        endwhile;
        nmgr_paging_nav(); else:
            do_action('nmgr_no_search_results', $template_args);
        endif;

        $template = ob_get_clean();

        wp_reset_query();

        if ($echo) {
            echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            return $template;
        }
    }
}

/**
 * Get the wishlist search template
 * This includes the search form and search results, depending on the arguments provided
 *
 * @since 1.0.4
 * @param type $atts Attributes needed to compose the template
 * @param boolean $echo Whether to echo the template. Default false.
 * @return string Template html
 */
function nmgr_get_search_template($atts = '', $echo = false)
{
    $args_unfiltered = shortcode_atts(
        array(
            'show_form' => true,
            'show_results' => true,
            'form_action' => '',
            'show_results_title' => true,
            'show_post_count' => true,
            'show_results_if_empty' => false,
        ),
        $atts,
        'nmgr_search'
    );

    $args = filter_var_array($args_unfiltered, array(
        'show_form' => FILTER_VALIDATE_BOOLEAN,
        'show_results' => FILTER_VALIDATE_BOOLEAN,
        'form_action' => FILTER_SANITIZE_URL,
        'show_results_title' => FILTER_VALIDATE_BOOLEAN,
        'show_post_count' => FILTER_VALIDATE_BOOLEAN,
        'show_results_if_empty' => FILTER_VALIDATE_BOOLEAN,
        ));

    $template = '';

    // Whether we are using the default wordpress search that uses 's' query var on the home_url permalink
    $using_wp_search = untrailingslashit(home_url()) === untrailingslashit(esc_url($args[ 'form_action' ]));

    if ($args[ 'show_form' ]) {
        $vars = array(
            'form_action' => $args[ 'form_action' ],
            'input_name' => $using_wp_search ? 's' : 'nmgr_s',
            'input_value' => is_nmgr_search() ? ($using_wp_search ? get_query_var('s') : get_query_var('nmgr_s')) : '',
            'using_wp_search' => $using_wp_search
        );

        $template .= apply_filters('nmgr_search_form', nmgr_get_template('form-search-wishlist.php', $vars), $vars);
    }

    if ($args[ 'show_results' ]) {
        $args[ 'show_title' ] = $args[ 'show_results_title' ];
        $template .= nmgr_get_search_results_template($args);
    }

    if ($echo) {
        echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $template;
    }
}

/**
 * Check if it is an admin request
 *
 * @since 1.0.4
 * @return boolean
 */
function is_nmgr_admin_request()
{
    $current_url = home_url(add_query_arg(null, null));
    $admin_url = strtolower(admin_url());
    $referrer = strtolower(wp_get_referer());

    /**
     * Check if this is a admin request. If true, it
     * could also be a AJAX request from the frontend.
     */
    if (0 === strpos($current_url, $admin_url)) {
        /**
         * Check if the user comes from a admin page.
         */
        if (0 === strpos($referrer, $admin_url)) {
            return true;
        } else {
            return !wp_doing_ajax();
        }
    } else {
        return false;
    }
}

/**
 * Check if the current user is a logged in user or a guest.
 *
 * Guests exist when non-logged-in users have been permitted to create and manage wishlists.
 *
 * @since 2.0.0
 * @return boolean
 */
function is_nmgr_user()
{
    return is_user_logged_in() || is_nmgr_guest();
}

/**
 * Check if the current user is a guest
 *
 * Guests exist when non-logged-in users have been permitted to create and manage wishlists.
 *
 * @since 2.0.0
 * @return boolean
 */
function is_nmgr_guest()
{
    return ( bool ) !is_user_logged_in() && nmgr_get_option('allow_guest_wishlists');
}

/**
 * Get the user id for the current logged in user or guest
 * (Note that this function returns the string value of the user id)
 *
 * @since 2.0.0
 * @return string
 */
function nmgr_get_current_user_id()
{
    if (AUTH) {
        $auth = new AUTH();
        $jwt_user = $auth->validate_token(false);

        if ($jwt_user->data->user_id) {
            return $jwt_user->data->user_id;
        }
    }

    if (get_current_user_id()) {
        /**
         * Always return logged in user id as a string so that it can be compatible with the guest
         * user id cookie value type and be tested with ===
         */
        return ( string ) get_current_user_id();
    }

    if (is_nmgr_guest()) {
        return nmgr_get_user_id_cookie();
    }
}

/**
 * Get the user id stored in a cookie.
 * (This is typically used for guests)
 *
 * @since 2.0.0
 * @return string
 */
function nmgr_get_user_id_cookie()
{
    return isset($_COOKIE[ 'nmgr_user_id' ]) ? wp_unslash($_COOKIE[ 'nmgr_user_id' ]) : 0;
}

/**
 * Generate a user id for wishlist users.
 * This is typically done for guests who are not logged in and so have no user id
 *
 * @since 2.0.0
 * @return string
 */
function nmgr_generate_user_id()
{
    require_once ABSPATH . 'wp-includes/class-phpass.php';
    $hasher = new PasswordHash(8, false);
    return md5($hasher->get_random_bytes(32));
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @since 2.0.0
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param integer $expire Cookie expiry
 */
function nmgr_setcookie($name, $value, $expire = 0)
{
    if (!headers_sent()) {
        setcookie($name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, false, false);
    }
}

if (!function_exists('nmgr_get_cart_template')) {

    /**
     * Template for displaying wishlists in cart fashion
     *
     * By default wishlists are displayed as a dropdown but this can be changed
     * by supplying relevant arguments
     *
     * @param mixed $atts Attributes needed to compose the template.
     *
     * @param boolean $echo Whether to echo the template. Default false.
     *
     * @return string Template html
     */
    function nmgr_get_cart_template($atts = '', $echo = false)
    {
        if (!is_nmgr_enabled() || (!is_nmgr_user() && !nmgr_get_option('add_to_wishlist_guests'))) {
            return;
        }

        $args = array(
            'title' => sprintf(
                /* translators: %s: wishlist type title */
                esc_html__('%s Items', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title('c'))
            ),
            'show_item_image' => true,
            'show_item_add_to_cart_button' => true,
            'show_item_qty_cost' => true,
            'show_item_availability' => true,
            'show_item_rating' => false,
            'show_total_quantity' => true,
            'show_total_cost' => true,
            'show_manage_button' => true,
            'number_of_items' => '',
            'show_cart_contents_only' => false,
        );

        $vars = shortcode_atts(
            $args,
            $atts,
            'nmgr_cart'
        );

        foreach ($args as $key => $val) {
            if (is_bool($val) && isset($vars[ $key ])) {
                $vars[ $key ] = ( int ) $vars[ $key ];
            }
        }

        $vars[ 'number_of_items' ] = ( int ) $vars[ 'number_of_items' ];

        $template_data_atts = array();
        foreach ($vars as $key => $value) {
            $template_data_atts[] = 'data-' . $key . '="' . $value . '"';
        }

        $vars[ 'template_args' ] = $vars; // pass this on for reference before it is modified below.
        $vars[ 'template_data_atts' ] = implode(' ', $template_data_atts);
        $vars[ 'wishlists' ] = nmgr_get_user_wishlists();
        $vars[ 'add_item_to_cart_text' ] = esc_attr__('Add to cart', 'nm-gift-registry-lite');
        $vars[ 'remove_item_text' ] = esc_attr(
            sprintf(
                /* translators: %s: wishlist type title */
                __('Remove from %s', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            )
        );
        $vars[ 'cart_qty' ] = 0;
        $vars[ 'cart_total' ] = 0;
        $vars[ 'items_and_products' ] = array(); // Array of items with their equivalent products
        $vars[ 'redirect' ] = false;
        $vars[ 'url' ] = nmgr_get_account_url();

        foreach ($vars[ 'wishlists' ] as $wishlist) {
            foreach ($wishlist->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $vars[ 'cart_qty' ] = $vars[ 'cart_qty' ] + $item->get_quantity();
                    $vars[ 'cart_total' ] = $vars[ 'cart_total' ] + $product->get_price();
                    $vars[ 'items_and_products' ][] = array(
                        'item' => $item,
                        'product' => $product
                    );
                }
            }
        }

        if (!is_nmgr_user() && nmgr_get_option('add_to_wishlist_guests')) {
            $vars[ 'redirect' ] = true;
            $vars[ 'url' ] = add_query_arg(array(
                'nmgr-notice' => 'require-login',
                'nmgr-redirect' => $_SERVER[ 'REQUEST_URI' ],
                ), wc_get_page_permalink('myaccount'));
        }

        $template = apply_filters('nmgr_cart_template', nmgr_get_template('cart.php', $vars), $vars);

        if ($echo) {
            echo $template;
        } else {
            return $template;
        }
    }
}

function nmgr_get_dialog_template($args)
{
    $defaults = array(
        'show_header' => true,
        'show_header_close_button' => true,
        'show_body_close_button' => false,
        'title' => '',
        'content' => '',
        'footer' => '',
    );

    $vars = apply_filters('nmgr_dialog_template_args', wp_parse_args($args, $defaults));
    return nmgr_get_template('dialog-template.php', $vars);
}

/**
 * Check if the current user has permissions to manage a wishlist
 *
 * By default the administrator and shop_manager roles have permision to manage all wishlists.
 *
 * @param int $wishlist_id Wishlist id
 * @return boolean
 */
function nmgr_user_can_manage_wishlist($wishlist_id = 0)
{
    return current_user_can('manage_nm_gift_registry_settings') || nmgr_user_has_wishlist($wishlist_id);
}

/**
 * Get the DateTime object representation of a date
 *
 * @since 2.0.0
 * @param string $date
 * @return DateTime
 */
function nmgr_get_datetime($date)
{
    $date_format = apply_filters('nmgr_validate_date_format', nmgr_php_date_format());

    $datetime = DateTime::createFromFormat($date_format, $date);

    if (!$datetime) {
        try {
            $datetime = new DateTime($date);
        } catch (Exception $ex) {
            $datetime = false;
        }
    }
    return $datetime;
}

function nmgr_get_dialog_submit_button($args)
{
    $class = isset($args[ 'class' ]) ? esc_attr(implode(' ', ( array ) $args[ 'class' ])) : '';
    $text = isset($args[ 'text' ]) ? nmgr_kses_post($args[ 'text' ]) : esc_html__('Done', 'nm-gift-registry-lite');
    $attributes = array();

    if (isset($args[ 'attributes' ]) && is_array($args[ 'attributes' ])) {
        foreach ($args[ 'attributes' ] as $attribute => $attribute_value) {
            $attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
        }
    }

    return sprintf(
        '<button class="nmgr-add-to-wishlist-dialog-button nmgr-dialog-submit-button %1$s" %2$s>%3$s</button>',
        $class,
        implode(' ', $attributes),
        $text
    );
}

/**
 * Columns shows on the wishlist items table
 *
 * This function is used to output  the plugin settings for the items table columns
 */
function nmgr_items_table_columns()
{
    return apply_filters('nmgr_items_table_columns', array(
        'thumbnail' => __('Thumbnail', 'nm-gift-registry-lite'),
        'title' => __('Title', 'nm-gift-registry-lite'),
        'cost' => __('Cost', 'nm-gift-registry-lite'),
        'quantity' => __('Quantity', 'nm-gift-registry-lite'),
        'purchased_quantity' => __('Purchased Quantity', 'nm-gift-registry-lite'),
        'total_cost' => __('Total Cost', 'nm-gift-registry-lite'),
        'actions' => __('Actions', 'nm-gift-registry-lite')
        ));
}

/**
 * Get a wishlist item by a specified id,
 *
 * @since 2.1.0
 *
 * @param int $item_id The wishlist item id used to retrieve the wishlist item
 * @return mixed NMGR_Wishlist_Item | false
 */
function nmgr_get_wishlist_item($item_id)
{
    if (!$item_id) {
        return false;
    }

    /**
     * try catch statement is used because the wishlist item db class throws an exception
     * if the wishlist item cannot be read from database
     */
    try {
        $item = new NMGR_Wishlist_Item($item_id);
        return $item;
    } catch (Exception $e) {
        return false;
    }
}

function nmgr_get_delete_item_notice($item)
{
    return apply_filters('nmgr_delete_item_notice', sprintf(
            /* translators: %s: wishlist type title */
            __('Are you sure you want to remove the %s item?', 'nm-gift-registry-lite'),
        esc_html(nmgr_get_type_title())
    ), $item);
}