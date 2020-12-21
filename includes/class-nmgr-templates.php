<?php

/**
 * Functions related to NM Gift Registry templates
 */
defined('ABSPATH') || exit;

class NMGR_Templates
{
    public static function run()
    {
        // Setup templates with woocommerce and wordpress
        add_filter('is_woocommerce', array(__CLASS__, 'is_woocommerce'));
        add_filter('init', array(__CLASS__, 'include_templates'));
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('the_post', array(__CLASS__, 'set_global_variable'), -1);
        add_action('add_meta_boxes', array(__CLASS__, 'set_global_variable'), -1);
        add_action('template_redirect', array(__CLASS__, 'set_global_variable'), -1);

        // Single wishlist page content
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_actions'), 5);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_title'), 20);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_display_name'), 30);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_event_date'), 40);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_description'), 50);
        add_action('nmgr_wishlist', 'woocommerce_output_all_notices', 60);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_items_table'), 70);
        add_action('nmgr_wishlist', array(__CLASS__, 'single_show_share_links'), 80);

        // Wishlist items table header
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_thumbnail'), 10);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_title'), 20);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_cost'), 30);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_quantity'), 40);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_purchased_quantity'), 50);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_total_cost'), 70);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_add_to_cart_button'), 80, 3);
        add_action('nmgr_items_table_header', array(__CLASS__, 'items_table_header_show_edit_delete_buttons'), 90, 3);

        // Wishlist items table body
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_thumbnail'), 10);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_title'), 20);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_cost'), 30);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_quantity'), 40);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_purchased_quantity'), 50);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_total_cost'), 70);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_add_to_cart_button'), 80, 3);
        add_action('nmgr_items_table_body', array(__CLASS__, 'items_table_body_show_edit_delete_buttons'), 90, 3);

        // Before wishlist items table
        add_action('nmgr_before_items', array(__CLASS__, 'before_items_maybe_show_required_shipping_address_notice'), 10, 3);

        // After wishlist items table
        add_action('nmgr_after_items', array(__CLASS__, 'after_items_show_items_total_cost'), 10, 2);
        add_action('nmgr_after_items', array(__CLASS__, 'after_items_show_add_to_cart_notice'), 10, 2);
        add_action('nmgr_after_items', array(__CLASS__, 'after_items_show_items_actions'), 20, 3);
        add_action('nmgr_after_items_actions', array(__CLASS__, 'after_items_actions_show_add_items_button'), 10, 3);
        add_action('nmgr_after_items_actions', array(__CLASS__, 'after_items_actions_show_save_items_button'), 20, 3);

        // Wishlist items table conditionals
        add_filter('nmgr_items_table_show_thumbnail', array(__CLASS__, 'maybe_show_item_thumbnail'), 10);
        add_filter('nmgr_items_table_show_title', array(__CLASS__, 'maybe_show_item_title'), 10);
        add_filter('nmgr_items_table_show_cost', array(__CLASS__, 'maybe_show_item_cost'), 10);
        add_filter('nmgr_items_table_show_quantity', array(__CLASS__, 'maybe_show_item_quantity'), 10);
        add_filter('nmgr_items_table_show_purchased_quantity', array(__CLASS__, 'maybe_show_item_purchased_quantity'), 10);
        add_filter('nmgr_items_table_show_total_cost', array(__CLASS__, 'maybe_show_item_total_cost'), 10);
        add_filter('nmgr_items_table_show_add_to_cart_button', array(__CLASS__, 'maybe_show_item_add_to_cart_button'), 10, 2);
        add_filter('nmgr_items_table_show_edit_delete_buttons', array(__CLASS__, 'maybe_show_item_edit_delete_buttons'), 10, 2);

        // Wishlist sharing
        add_action('wp_head', array(__CLASS__, 'add_open_graph_markup'));

        // Setup account page
        $page = nmgr_get_account_details('slug');

        if ($page) {
            add_filter('woocommerce_get_query_vars', array(__CLASS__, 'add_account_query_var'));
            add_filter('woocommerce_account_menu_items', array(__CLASS__, 'add_account_menu_item'), 10, 1);
            add_filter("woocommerce_endpoint_{$page}_title", array(__CLASS__, 'endpoint_title'), 10, 2);
            add_action("woocommerce_account_{$page}_endpoint", array(__CLASS__, 'account_endpoint'), 10);
        }

        // Account page content
        add_filter('nmgr_account_tabs', array(__CLASS__, 'show_account_tabs'), 10);
        add_filter('nmgr_account_tabs', array(__CLASS__, 'sort_account_tabs'), 20);
        add_action('woocommerce_account_dashboard', array(__CLASS__, 'show_wishlist_dashboard_text'), 10);
        add_action('woocommerce_account_dashboard', array(__CLASS__, 'enable_wishlist'), 20);
        add_action('template_redirect', array(__CLASS__, 'enable_wishlist_redirection'));
        add_filter('nmgr_account_tab_title', array(__CLASS__, 'account_tab_title'), 10, 3);

        // Modify Wishlist data properties
        add_filter('nmgr_get_prop', array(__CLASS__, 'get_wishlist_property'), 10, 4);

        // Sidebar
        add_action('nmgr_sidebar', array(__CLASS__, 'show_theme_sidebar'), 10);

        // Search
        add_action('nmgr_search_results_header', array(__CLASS__, 'search_results_header'), 10);
        add_action('nmgr_no_search_results', array(__CLASS__, 'no_search_results_notification'), 10);

        add_filter('nmgr_delete_item_notice', array(__CLASS__, 'notify_of_item_purchased_status'), 10, 2);
    }

    /**
     * Declare certain NM Gift Registry pages as woocommerce pages
     *
     * @param bool $boolean
     * @return boolean
     */
    public static function is_woocommerce($boolean)
    {
        return is_nmgr_wishlist() || $boolean;
    }

    public static function include_templates()
    {
        add_filter('template_include', array(__CLASS__, 'include_template_page'));

        if (
            !current_theme_supports('woocommerce') ||
            nmgr_get_option('single_wishlist_template') ||
            nmgr_get_option('search_results_template')
        ) {
            add_action('template_redirect', array(__CLASS__, 'include_template_shortcode'));
        }
    }

    /**
     * Handles loading templates for viewing a single wishlist and wishlist search results
     *
     * NM Gift Registry is a custom post type so a single wishlist can be viewed using the file
     * single-nm_gift_registry.php in the theme root folder
     * If this  file is instead in the theme plugin folder it will be loaded from there.
     * Else it would be loaded from the plugin templates folder
     *
     * Search results can be viewed using the file search-nm_gift_registry.php which is loaded
     * first from the theme plugin folder if found. Else it would be loaded from the
     * plugin templates folder.
     *
     * @return string Template file
     */
    public static function include_template_page($template)
    {
        $page_template = array(
            'page.php',
            'singular.php',
            'index.php'
        );

        $post_template = array(
            'single-nm_gift_registry.php',
            'single.php',
            'singular.php',
            'index.php'
        );

        $single_wishlist_template = nmgr_get_option('single_wishlist_template');

        // Theme file to use as the single wishlist template
        if (current_theme_supports('woocommerce') && !$single_wishlist_template) {
            $single_template = array(
                'single-nm_gift_registry.php', // theme root
                nmgr()->theme_path . 'single-nm_gift_registry.php', // theme folder for plugin templates
            );
        } elseif ($single_wishlist_template) {
            $template_slug = get_page_template_slug(absint($single_wishlist_template));
            if ($template_slug && 0 === validate_file($template_slug)) {
                $single_template = $template_slug;
            } else {
                $single_template = $page_template;
            }
        }

        // Theme file to be used as the search results template
        $search_results_template = nmgr_get_option('search_results_template');

        if (current_theme_supports('woocommerce') && !$search_results_template) {
            $search_template = nmgr()->theme_path . 'search-nm_gift_registry.php';
        } elseif ($search_results_template) {
            $template_slug = get_page_template_slug(absint($search_results_template));
            if ($template_slug && 0 === validate_file($template_slug)) {
                $search_template = $template_slug;
            } else {
                $search_template = $page_template;
            }
        }


        /**
         * Set up template file for single wishlists
         * Make sure we use this template only if the shortcode is not being used
         */
        if (is_nmgr_wishlist() && !nmgr_post_content_has_shortcodes('nmgr_wishlist')) {
            if (isset($single_template) && get_query_template('single', $single_template)) {
                return get_query_template('single', $single_template);
            } elseif (current_theme_supports('woocommerce')) {
                $plugin_template = nmgr()->template_path . 'single-nm_gift_registry.php';
            }
        }

        /**
         * Set up template file for searching wishlists
         * Use this template only if we are search for wishlists using wordpress' default search
         *
         * 'is_search' checks to see if we are using wordpress' default search key 's'
         * 'is_nmgr_search' check to see that we are searching for wishlists
         * Using these two functions is necessary to make sure we are searching for wishlists using wordpress' default search
         * and not the custom wishlist search key 'nmgr_s' used in the search shortcode.
         */
        if (is_search() && is_nmgr_search()) {
            if (isset($search_template) && get_query_template('search', $search_template)) {
                return get_query_template('search', $search_template);
            } elseif (current_theme_supports('woocommerce')) {
                $plugin_template = nmgr()->template_path . 'search-nm_gift_registry.php';
            }
        }

        if (isset($plugin_template) && file_exists($plugin_template)) {
            return $plugin_template;
        }

        return $template;
    }

    public static function include_template_shortcode()
    {
        // Use the shortcode only if it is not already being used
        if (is_nmgr_wishlist() && !nmgr_post_content_has_shortcodes('nmgr_wishlist')) {
            add_filter('the_content', array(__CLASS__, 'show_single_wishlist_shortcode'), 10);
            add_filter('comments_open', '__return_false', 20, 2);
        }

        /**
         * 'is_search' checks to see if we are using wordpress' default search key 's'
         * 'is_nmgr_search' check to see that we are searching for wishlists
         * Using these two functions is necessary to make sure we are inserting the content only when
         * searching for wishlists using wordpress' default search and not the custom wishlist search key 'nmgr_s'
         * used in the search shortcode.
         */
        if (is_search() && is_nmgr_search()) {
            add_filter('the_content', array(__CLASS__, 'show_search_results'));
            add_filter('comments_open', '__return_false', 20, 2);
        }
    }

    public static function show_single_wishlist_shortcode($content)
    {
        if (!is_main_query() || !in_the_loop()) {
            return $content;
        }

        remove_filter('the_content', array(__CLASS__, 'show_single_wishlist_shortcode'));
        return do_shortcode('[nmgr_wishlist id="' . get_the_ID() . '"]');
    }

    /**
     * Show the search results when using a custom theme template
     *
     * @since 1.1.2
     */
    public static function show_search_results($content)
    {
        if (!is_main_query() || !in_the_loop()) {
            return $content;
        }

        remove_filter('the_content', array(__CLASS__, 'show_search_results'));
        return nmgr_get_search_results_template();
    }

    /**
     * Add body classes to identify all nmgr pages
     *
     * Page specific class is not added to the single wishlist page  as a custom post type
     * because wordpress already adds the class 'single-nm_gift_registry' to it
     *
     * Classes added here follow this format {page-type}nm_gift_registry
     *
     * @param type $classes
     * @return string
     */
    public static function body_class($classes)
    {
        if (current_theme_supports('woocommerce')) {
            if (is_nmgr()) {
                $classes[] = 'nm_gift_registry';
            }

            if (is_nmgr_search()) {
                $classes[] = 'search-nm_gift_registry';
            }
        }

        if (is_nmgr_account()) {
            $classes[] = 'nm_gift_registry';
            $classes[] = 'account-nm_gift_registry';
        }

        return $classes;
    }

    /**
     * Put relevant wishlist data into the global variable 'nmgr'
     *
     * This function is hooked into 'the_post' to make the global variable available on an individual
     * post basis in loops. This can be helpful, for example, in retrieving the right wishlist id for
     * the current post if the current 'post' query is not the main query
     *
     * The function is also hooked into 'add_meta_boxes' to make the global variable available
     * in the admin edit screen for a wishlist.
     *
     * Finally the function is hooked into 'template_redirect' to make the global variable available
     * generally for the frontend but also for enqueued scripts which use 'wp_enqueue_script' hook,
     * as this hook runs before 'the_post' hook
     *
     * The global variable is only available on registered nmgr pages and
     * it would not be available before these hooks are called by wordpress
     *
     * @return void|\stdClass
     */
    public static function set_global_variable()
    {
        $GLOBALS['nmgr'] = new stdClass();
        $GLOBALS['nmgr']->is_account = is_nmgr_account();
        $GLOBALS['nmgr']->is_wishlist = is_nmgr_wishlist();
        $GLOBALS['nmgr']->is_admin = is_nmgr_admin();
        $GLOBALS['nmgr']->is_search = is_nmgr_search();
        $GLOBALS['nmgr']->is_account_tab = is_nmgr_account_tab();
        $GLOBALS['nmgr']->is_modal = is_nmgr_modal();
        $GLOBALS['nmgr']->wishlist_id = nmgr_get_current_wishlist_id();
        $GLOBALS['nmgr']->wishlist = nmgr_get_current_wishlist_id() ? nmgr_get_wishlist()->get_data() : '';
    }

    /**
     * Show wishlist actions on the single wishlist page
     *
     * Actions:
     * - Add items
     * - Manage
     *
     * @param NMGR_Wishist $wishlist
     */
    public static function single_show_actions($wishlist)
    {
        if (nmgr_get_current_user_id() === $wishlist->get_user_id()) {
            echo '<p class="nmgr-wishlist-page-actions">';

            printf(
                '<a class="button nmgr-wishlist-edit-link" href="%1$s">%2$s</a>',
                esc_url(nmgr_get_account_url($wishlist->get_slug())),
                esc_html__('Manage', 'nm-gift-registry-lite')
            );

            if ((!nmgr_get_option('shipping_address_required') ||
                (nmgr_get_option('shipping_address_required') && $wishlist->has_shipping_address()))) {
                printf(
                    '<a class="button nmgr-tip" title="%1$s" href="%2$s">%3$s</a>',
                    sprintf(
                        /* translators: %s: wishlist type title */
                        esc_attr__('Go shopping for items to add to your %s.', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ),
                    esc_url(nmgr_get_add_items_url()),
                    esc_html__('Add item(s)', 'nm-gift-registry-lite')
                );
            }

            echo '</p>';
        }
    }

    /**
     * Show the wishlist title on the single wishlist page
     *
     * @param NMGR_Wishist $wishlist
     */
    public static function single_show_title($wishlist)
    {
        printf('<h2 class="nmgr-title nmgr-text-center entry-title">%s</h2>', esc_html($wishlist->get_title()));
    }

    /**
     * Show the wishlist display name on the single wishlist page
     *
     * @param NMGR_Wishlist $wishlist
     */
    public static function single_show_display_name($wishlist)
    {
        if ($wishlist->get_display_name()) {
            printf('<h3 class="nmgr-display-name nmgr-text-center">%s</h3>', esc_html($wishlist->get_display_name()));
        }
    }

    /**
     * Show the wishlist event date on the single wishlist page
     *
     * @param NMGR_Wishlist $wishlist
     */
    public static function single_show_event_date($wishlist)
    {
        $date = nmgr_format_date($wishlist->get_event_date());
        if ($date) {
            printf('<p class="nmgr-event-date nmgr-text-center">%s: %s</p>', esc_html__('Event date', 'nm-gift-registry-lite'), esc_html($date));
        }
    }

    /**
     * Show the wishlist description on the single wishlist page
     *
     * @param NMGR_Wishlist $wishlist
     */
    public static function single_show_description($wishlist)
    {
        if ($wishlist->get_description()) {
            printf('<div class="nmgr-description nmgr-text-center">%s</div>', wp_kses_post(wpautop($wishlist->get_description())));
        }
    }

    /**
     * Show the wishlist items table on the single wishlist page
     *
     * @param NMGR_Wishlist $wishlist
     */
    public static function single_show_items_table($wishlist)
    {
        if ($wishlist->is_fulfilled()) {
            /* translators: %s: wishlist type title */
            wc_print_notice(sprintf(__('This %s is fulfilled.'), nmgr_get_type_title()), 'notice');
        }

        if ($wishlist->is_fulfilled() && nmgr_get_option('hide_fulfilled_items')) {
            return;
        }

        if ($wishlist->has_items()) {
            nmgr_get_items_template(array(
                'id' => $wishlist,
                'editable' => false,
            ), true);
        } else {
            $link = '';
            if (nmgr_user_has_wishlist($wishlist)) {
                $link = sprintf(
                    '<a href="%s" tabindex="1" class="button">%s</a>',
                    esc_url(nmgr_get_add_items_url()),
                    esc_html__('Add item(s)', 'nm-gift-registry-lite')
                );
            }

            wc_print_notice(
                $link .
                    /* translators: %s: wishlist type title */
                    sprintf(__('This %s is empty.', 'nm-gift-registry-lite'), nmgr_get_type_title()),
                'notice'
            );
        }
    }

    /**
     * Show share links on the single wishlist page
     */
    public static function single_show_share_links($wishlist)
    {
        if (!nmgr_get_option('enable_single_sharing')) {
            return;
        }
        nmgr_get_share_template(array(
            'id' => $wishlist,
            'title' => __('Share on:', 'nm-gift-registry-lite')
        ), true);
    }

    /**
     * Show item thumbnail cell in the wishlist items table header
     */
    public static function items_table_header_show_thumbnail()
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_thumbnail', true)) {
            echo '<th class="item_thumbnail">&nbsp;</th>';
        }
    }

    /**
     * Show item title in the wishlist items table header
     */
    public static function items_table_header_show_title()
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_title', true)) {
            printf(
                '<th class="item_title sortable" data-sort="string-ins">%s</th>',
                esc_html__('Product', 'nm-gift-registry-lite')
            );
        }
    }

    /**
     * Show item cost  in the wishlist items table header
     */
    public static function items_table_header_show_cost()
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_cost', true)) {
            printf(
                '<th class="item_cost sortable" data-sort="float">%s</th>',
                esc_html__('Cost', 'nm-gift-registry-lite')
            );
        }
    }

    /**
     * Show item desired quantity in the wishlist items table header
     */
    public static function items_table_header_show_quantity()
    {
        if (apply_filters('nmgr_items_table_show_quantity', true)) {
            printf(
                '<th class="item_quantity  sortable" data-sort="int"><span>%s</span></th>',
                nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'icon' => 'cart-empty',
                    'size' => 1,
                    'fill' => 'currentColor',
                    'class' => 'nmgr-tip',
                    'title' => __('Desired Quantity', 'nm-gift-registry-lite')
                ))
            );
        }
    }

    /**
     * Show item purchased quantity in the wishlist items table header
     */
    public static function items_table_header_show_purchased_quantity()
    {
        if (apply_filters('nmgr_items_table_show_purchased_quantity', true)) {
            printf(
                '<th class="item_purchased_quantity sortable" data-sort="int"><span>%s</span></th>',
                nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'icon' => 'cart-full',
                    'size' => 1,
                    'fill' => 'currentColor',
                    'class' => 'nmgr-tip',
                    'title' => __('Purchased Quantity', 'nm-gift-registry-lite')
                ))
            );
        }
    }

    /**
     * Show item total cost in the wishlist items table header
     */
    public static function items_table_header_show_total_cost()
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_total_cost', true)) {
            printf(
                '<th class="item_total_cost sortable" data-sort="float">%s</th>',
                esc_html__('Total', 'nm-gift-registry-lite')
            );
        }
    }

    /**
     * Show the add to cart button header
     */
    public static function items_table_header_show_add_to_cart_button($items, $wishlist, $items_args)
    {
        if (!is_nmgr_admin() && apply_filters('nmgr_items_table_show_add_to_cart_button', false, $items_args)) {
            echo '<th class="item_add_to_cart">&nbsp;</th>';
        }
    }

    /**
     * Show item actions title in the wishlist items table header
     */
    public static function items_table_header_show_edit_delete_buttons($items, $wishlist, $items_args)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_edit_delete_buttons', false, $items_args)) {
            printf(
                '<th class="item_actions "><div>%s</div></th>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                nmgr_get_svg(array(
                    'icon' => 'gear',
                    'size' => 1,
                    'fill' => 'currentColor'
                ))
            );
        }
    }

    /**
     * Show item thumbnail in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_thumbnail($item)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_thumbnail', true)) {
            $product = $item->get_product();
            $thumbnail = $product ? $product->get_image('nmgr_thumbnail', array(
                'title' => $product->get_name(),
                'class' => 'nmgr-tip',
                'alt' => $product->get_name()
            )) : '';

            nmgr_template('account/items/item-thumbnail.php', array('thumbnail' => $thumbnail));
        }
    }

    /**
     * Show item title in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_title($item)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_title', true)) {
            $product = $item->get_product();
            $product_link = is_nmgr_admin() ? admin_url('post.php?post=' . $product->get_id() . '&action=edit') : $product->get_permalink($item->get_data());

            nmgr_template('account/items/item-title.php', array(
                'item' => $item,
                'product' => $product,
                'product_link' => $product_link,
            ));
        }
    }

    /**
     * Show item cost in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_cost($item)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_cost', true)) {
            nmgr_template('account/items/item-cost.php', array(
                'item' => $item,
                'product' => $item->get_product()
            ));
        }
    }

    /**
     * Show item desired quantity in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_quantity($item)
    {
        if (apply_filters('nmgr_items_table_show_quantity', true)) {
            $product = $item->get_product();
            $quantity = (is_nmgr_wishlist() && nmgr_get_option('display_item_purchased_quantity', 1)) ?
                $item->get_unpurchased_quantity() : $item->get_quantity();

            nmgr_template('account/items/item-quantity.php', array(
                'item' => $item,
                'product' => $product,
                'quantity' => $quantity,
            ));
        }
    }

    /**
     * Show item purchased quantity in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_purchased_quantity($item)
    {
        if (apply_filters('nmgr_items_table_show_purchased_quantity', true)) {
            nmgr_template('account/items/item-purchased-quantity.php', array(
                'item' => $item,
                'product' => $item->get_product(),
                'purchased_quantity' => $item->get_purchased_quantity(),
            ));
        }
    }

    /**
     * Show item total cost in the wishlist items table body
     *
     * @param NMGR_Wishlist_Item $item
     */
    public static function items_table_body_show_total_cost($item)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_total_cost', true)) {
            nmgr_template('account/items/item-total_cost.php', array('item' => $item));
        }
    }

    /**
     * Show the add to cart button for the wishlist item
     */
    public static function items_table_body_show_add_to_cart_button($item, $wishlist, $items_args)
    {
        if (!is_nmgr_admin() && apply_filters('nmgr_items_table_show_add_to_cart_button', false, $items_args)) {
            $product = $item->get_product();

            // Get the maximum quantity of the item that can be added to the cart
            $max_purchase_qty = $product->get_max_purchase_quantity();

            if (nmgr_get_option('display_item_quantity', 1) && !nmgr_get_option('display_item_purchased_quantity', 1)) {
                $desired_qty = $item->get_quantity();
            } elseif (nmgr_get_option('display_item_quantity', 1) && nmgr_get_option('display_item_purchased_quantity', 1)) {
                $desired_qty = $item->get_unpurchased_quantity();
            } else {
                $desired_qty = $max_purchase_qty;
            }

            if ($max_purchase_qty > 0) {
                if ($max_purchase_qty > $desired_qty) {
                    $max_qty = $desired_qty;
                } else {
                    $max_qty = $max_purchase_qty;
                }
            } else {
                $max_qty = $desired_qty;
            }

            nmgr_template('account/items/item-actions-add_to_cart.php', array(
                'item' => $item,
                'wishlist' => $wishlist,
                'product' => $product,
                'max_qty' => $max_qty,
            ));
        }
    }

    /**
     * Show actions related to each item row in the items table
     */
    public static function items_table_body_show_edit_delete_buttons($item, $wishlist, $items_args)
    {
        if (is_nmgr_admin() || apply_filters('nmgr_items_table_show_edit_delete_buttons', false, $items_args)) {
            $show_edit_button = false;

            if (
                nmgr_get_option('display_item_quantity', 1) ||
                nmgr_get_option('display_item_favourite', 1) ||
                nmgr_get_option('display_item_purchased_quantity')
            ) {
                $show_edit_button = true;
            }

            nmgr_template('account/items/item-actions-edit-delete.php', array(
                'show_edit_button' => apply_filters('nmgr_items_table_show_edit_button', $show_edit_button, $item, $items_args),
                'show_delete_button' => apply_filters('nmgr_items_table_show_delete_button', true, $item, $items_args),
                'item' => $item,
                'items_args' => $items_args,
            ));
        }
    }

    public static function maybe_show_item_thumbnail($bool)
    {
        return $bool && nmgr_get_option('display_item_thumbnail', 1);
    }

    public static function maybe_show_item_title($bool)
    {
        return $bool && nmgr_get_option('display_item_title', 1);
    }

    public static function maybe_show_item_cost($bool)
    {
        return $bool && nmgr_get_option('display_item_cost', 1);
    }

    public static function maybe_show_item_quantity($bool)
    {
        return $bool && nmgr_get_option('display_item_quantity', 1);
    }

    public static function maybe_show_item_purchased_quantity($bool)
    {
        if (is_nmgr_wishlist()) {
            return false;
        }
        return $bool && nmgr_get_option('display_item_purchased_quantity', 1);
    }

    public static function maybe_show_item_total_cost($bool)
    {
        if (is_nmgr_wishlist()) {
            return false;
        }

        $value = $bool && nmgr_get_option('display_item_total_cost', 1);

        if (!$value && has_action('nmgr_after_items', array(__CLASS__, 'after_items_show_items_total_cost'))) {
            remove_action('nmgr_after_items', array(__CLASS__, 'after_items_show_items_total_cost'), 10, 2);
        }

        return $value;
    }

    public static function maybe_show_item_add_to_cart_button($bool, $items_args)
    {
        $pre_value = is_nmgr_wishlist() || $items_args['add_to_cart'] || $bool;

        $value = $pre_value && nmgr_get_option('display_item_add_to_cart', 1);

        if (!$value) {
            if (has_action('nmgr_after_items', array(__CLASS__, 'after_items_show_add_to_cart_notice'))) {
                remove_action('nmgr_after_items', array(__CLASS__, 'after_items_show_add_to_cart_notice'), 10, 2);
            }
        }

        return $value;
    }

    public static function maybe_show_item_edit_delete_buttons($bool, $items_args)
    {
        $pre_value = $bool || is_nmgr_account() || (!is_nmgr_wishlist() && $items_args['editable']);

        $value = $pre_value && nmgr_get_option('display_item_edit_delete', 1);

        if (!$value) {
            if (has_action('nmgr_after_items_actions', array(__CLASS__, 'after_items_actions_show_save_items_button'))) {
                remove_action('nmgr_after_items_actions', array(__CLASS__, 'after_items_actions_show_save_items_button'), 20, 3);
            }
        }

        return $value;
    }

    public static function before_items_maybe_show_required_shipping_address_notice($items, $wishlist, $items_args)
    {
        if ($items_args['editable']) {
            nmgr_maybe_show_required_shipping_address_notice($wishlist);
        }
    }

    /**
     * Show total cost of all items
     *
     * @param array $items Wishlist items
     * @param NMGR_Wishlist $wishlist
     */
    public static function after_items_show_items_total_cost($items, $wishlist)
    {
        if (!is_nmgr_wishlist()) {
            nmgr_template('account/items/items-total-cost.php', array('wishlist' => $wishlist));
        }
    }

    /**
     * Show notice concerning adding a wishlist product to the cart
     */
    public static function after_items_show_add_to_cart_notice($items, $wishlist)
    {
        if (is_nmgr_wishlist() && !$wishlist->is_fulfilled()) {
            $text = __('* For accurate pricing details, please add the product to your cart.', 'nm-gift-registry-lite');
            printf('<div style="text-align:right;"><small>%s</small></div>', $text);
        }
    }

    /**
     * Show (add/save) actions relating to all items
     *
     * @param array $items Wishlist items
     * @param NMGR_Wishlist $wishlist
     */
    public static function after_items_show_items_actions($items, $wishlist, $items_args)
    {
        nmgr_template(
            'account/items/items-actions.php',
            array(
                'items' => $items,
                'wishlist' => $wishlist,
                'items_args' => $items_args
            )
        );
    }

    /**
     * Show button for adding an item to the wishlist
     *
     * This button simply redirects to the shop page
     */
    public static function after_items_actions_show_add_items_button($items, $wishlist, $items_args)
    {
        if (
            is_nmgr_wishlist() ||
            (nmgr_get_option('shipping_address_required') && !$wishlist->has_shipping_address()) ||
            !$items_args['editable']
        ) {
            return;
        }

        $is_admin = is_nmgr_admin();
        $title = $is_admin ? '' : esc_attr__('Go shopping for items to add to your wishlist', 'nm-gift-registry-lite');
        $data_url = $is_admin ? '' : esc_attr(nmgr_get_add_items_url());
        $data_content = $is_admin ? '#nmgr-add-items-dialog' : '';
?>
<button type="button" title="<?php echo $title; ?>" class="button nmgr-add-items-action nmgr-tip"
  data-url="<?php echo $data_url; ?>" data-dialog-width="small" data-dialog-content="<?php echo $data_content; ?>">
  <?php esc_html_e('Add item(s)', 'nm-gift-registry-lite'); ?>
</button>
<?php
    }

    public static function after_items_actions_show_save_items_button($items, $wishlist, $items_args)
    {
        if (
            (!nmgr_get_option('display_item_quantity', 1) &&
                !nmgr_get_option('display_item_purchased_quantity', 1)) ||
            empty($items) ||
            !$items_args['editable']
        ) {
            return;
        }
    ?>
<button type="button" class="button button-primary save-action" data-reload="false">
  <?php esc_html_e('Save changes', 'nm-gift-registry-lite'); ?>
</button>
<?php
    }

    /**
     * Show link to wishlist endpoint url on woocommerce account dashboard
     */
    public static function show_wishlist_dashboard_text()
    {
        if (is_nmgr_enabled()) {
            printf(
                /* translators: 1: wishlist module account url, 2: wishlist type title */
                wp_kses_post(__('<p>You can also manage your <a href="%1$s">%2$s</a>.</p>', 'nm-gift-registry-lite')),
                esc_url(nmgr_get_account_url()),
                esc_html(nmgr_get_type_title())
            );
        }
    }

    /**
     * Allows individual users to enable the wishlist module in their account page area
     */
    public static function enable_wishlist()
    {
        nmgr_get_enable_wishlist_form(true);
    }

    /**
     * Redirect to 404 if wishlist module for specific user is not enabled
     */
    public static function enable_wishlist_redirection()
    {
        global $post, $wp_query;
        if ((is_nmgr_account() && !is_nmgr_enabled()) ||
            (is_nmgr_wishlist() && !is_nmgr_enabled($post->post_author))
        ) {
            $wp_query->set_404();
            status_header(404);
            include get_query_template('404');
            exit();
        }
    }

    /**
     *
     * Add NM Gift Registry account query variable to woocommerce query variables
     *
     * @param array $query_vars Query vars
     * @return array
     */
    public static function add_account_query_var($query_vars)
    {
        $query_vars[nmgr_get_account_details('slug')] = nmgr_get_account_details('slug');
        return $query_vars;
    }

    /**
     * Add open graph markup to single wishlist pages and account pages for viewing single wishlists
     * to help web bots such as facebook's crawler recognise content when sharing
     */
    public static function add_open_graph_markup()
    {
        if (!is_nmgr_wishlist() && !is_nmgr_account()) {
            return;
        }

        $wishlist = nmgr_get_wishlist();

        if (!$wishlist) {
            return;
        }
    ?>
<meta property="og:url" content="<?php echo esc_html($wishlist->get_permalink()); ?>" />
<meta property="og:type" content="article" />
<meta property="og:title" content="<?php esc_html($wishlist->get_title()); ?>" />
<?php if ($wishlist->get_description()) : ?>
<meta property="og:description" content="<?php esc_html($wishlist->get_description()); ?>" />
<?php
        endif;
    }

    /**
     * Set up NM Gift Registry menu item name, slug and position in woocommerce my account nav menu
     */
    public static function add_account_menu_item($items)
    {
        if (!is_nmgr_enabled()) {
            return $items;
        }

        $logout = array_pop($items);
        $items[nmgr_get_account_details('slug')] = nmgr_get_account_details('name');
        $items['customer-logout'] = $logout;

        return apply_filters('nmgr_account_menu_item', $items);
    }

    /**
     * NM Gift Registry endpoint title on the woocommerce my account page
     */
    public static function endpoint_title($title, $endpoint)
    {
        $the_title = apply_filters('nmgr_endpoint_title', nmgr_get_account_details('name'));
        return nmgr_get_account_details('slug') == $endpoint ? $the_title : $title;
    }

    /**
     * Template for managing wishlists on the woocommerce my account page
     *
     * @param string $wishlist_slug  Slug of the wishlist currently being viewed
     */
    public static function account_endpoint()
    {
        nmgr_get_account_template('', true);
    }

    /**
     * Tabs used on NM Gift Registry account page
     *
     * @param array $tabs
     * @return array
     */
    public static function get_account_tabs()
    {
        $tabs['overview'] = array(
            'title' => __('Overview', 'nm-gift-registry-lite'),
            'priority' => 10,
            'callback' => array(__CLASS__, 'overview_tab'),
        );

        $tabs['profile'] = array(
            'title' => __('Profile', 'nm-gift-registry-lite'),
            'priority' => 20,
            'callback' => array(__CLASS__, 'profile_tab'),
        );
        $tabs['items'] = array(
            'title' => __('Items', 'nm-gift-registry-lite'),
            'priority' => 30,
            'callback' => array(__CLASS__, 'items_tab'),
        );

        $tabs['shipping'] = array(
            'title' => __('Shipping', 'nm-gift-registry-lite'),
            'priority' => 40,
            'callback' => array(__CLASS__, 'shipping_tab'),
        );

        foreach (array_keys($tabs) as $key) {
            $tabs[$key]['tab_id'] = "nmgr-tab-{$key}";
            $tabs[$key]['tab_content_id'] = "tab-{$key}";
        }

        return $tabs;
    }

    /**
     * Whether to show individual account tabs based on plugin settings
     */
    public static function show_account_tabs($tabs)
    {
        if (!nmgr_get_option('enable_shipping')) {
            unset($tabs['shipping']);
        }
        return $tabs;
    }

    public static function sort_account_tabs($tabs = array())
    {
        uasort($tabs, array(__CLASS__, 'sort_by_priority'));
        return $tabs;
    }

    private static function sort_by_priority($a, $b)
    {
        if (!isset($a['priority'], $b['priority']) || $a['priority'] === $b['priority']) {
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }

    /**
     * NM Gift Registry overview tab content
     */
    public static function overview_tab($wishlist)
    {
        nmgr_get_overview_template($wishlist, true);
    }

    /**
     * NM Gift Registry profile tab content
     */
    public static function profile_tab($wishlist)
    {
        nmgr_get_profile_template($wishlist, true);
    }

    /**
     * NM Gift Registry items tab content
     */
    public static function items_tab($wishlist)
    {
        nmgr_get_items_template($wishlist, true);
    }

    /**
     * NM Gift Registry shipping tab content
     */
    public static function shipping_tab($wishlist)
    {
        nmgr_get_shipping_template($wishlist, true);
    }

    public static function account_tab_title($title, $tab, $wishlist)
    {
        $icon_args = array(
            'icon' => 'info',
            'title' => esc_html__('Your attention is required.', 'nm-gift-registry-lite'),
            'fill' => 'currentColor',
            'style' => 'margin-left:7px;',
            'size' => 0.75,
            'class' => 'nmgr-tip nmgr-hide',
        );

        switch ($tab) {
            case 'profile':
                if (!$wishlist) {
                    $icon_args['class'] = str_replace('nmgr-hide', '', $icon_args['class']);
                }
                $icon_args['title'] = sprintf(
                    /* translators: %s: wishlist type title */
                    esc_html__('Fill in your %s profile details.', 'nm-gift-registry-lite'),
                    esc_html(nmgr_get_type_title())
                );
                $icon_args['data-notice'] = 'require-profile';
                $icon = nmgr_get_svg($icon_args);
                $title .= $icon;
                break;

            case 'shipping':
                if ($wishlist && nmgr_get_option('shipping_address_required') && !$wishlist->has_shipping_address()) {
                    $icon_args['class'] = str_replace('nmgr-hide', '', $icon_args['class']);
                }
                $icon_args['title'] = sprintf(
                    /* translators: %s: wishlist type title */
                    esc_html__('Your shipping address is required before you can add items to your %s.', 'nm-gift-registry-lite'),
                    esc_html(nmgr_get_type_title())
                );
                $icon_args['data-notice'] = 'require-shipping-address';
                $icon = nmgr_get_svg($icon_args);
                $title .= $icon;
                break;
        }

        return $title;
    }

    /**
     * Filter the returned values for data property
     *
     * @param mixed $value The value
     * @param string $prop The property
     * @param string $parent The parent of the property
     * @param Object $object The type of data to modify. Valid values are 'wishlist' and 'wishlist_item'
     */
    public static function get_wishlist_property($value, $prop, $parent, $object)
    {
        if ('wishlist' === $object->get_object_type()) {
            $dont_modify = array('status');

            if (!in_array($prop, $dont_modify) && 'no' === nmgr_get_option("display_form_{$prop}")) {
                return null;
            }

            // Shipping
            if (!nmgr_get_option('enable_shipping', 1)) {
                $shipping = array('ship_to_account_address');
                if ('shipping' === $parent || in_array($prop, $shipping)) {
                    return null;
                }

                if ('shipping' === $prop) {
                    return array();
                }
            }
        }
        return $value;
    }

    /**
     * Show the theme sidebar if it exists
     * (checks only for sidebar.php or siderbar-shop.php)
     */
    public static function show_theme_sidebar()
    {
        $templates = array(
            'sidebar-shop.php',
            'sidebar.php'
        );

        $file_exists = false;

        foreach ($templates as $template_name) {
            if (
                file_exists(get_stylesheet_directory() . '/' . $template_name) ||
                file_exists(get_template_directory() . '/' . $template_name)
            ) {
                $file_exists = true;
                break;
            }
        }

        if ($file_exists) {
            get_sidebar('shop');
        }
    }

    /**
     * Show the header for wishlist search results
     *
     * @since 1.0.3
     * @global WP_Query $wp_query
     */
    public static function search_results_header($args)
    {
        global $wp_query;

        if (!isset($args['show_title'], $args['show_post_count']) || (!$args['show_title'] && !$args['show_post_count'])) {
            return;
        }
        ?>
<header class="nmgr-search-header nmgr-text-center">
  <?php
            if ($args['show_title']) {
                echo '<h2 class="nmgr-search-title">';
                /* translators: %s: search query */
                printf(esc_html__('Search results for: &ldquo;%s&rdquo;', 'nm-gift-registry-lite'), get_search_query(true));
                echo '</h2>';
            }

            if ($args['show_post_count']) {
                echo '<p>';
                /* translators: %d: total results */
                printf(_n('%d result found', '%d results found', $wp_query->found_posts, 'nm-gift-registry-lite'), absint($wp_query->found_posts));
                echo '</p>';
            }
            ?>
</header>
<?php
    }

    /**
     * Show notice when there are no wishlist search results
     *
     * @since 1.1.2
     */
    public static function no_search_results_notification()
    {
        echo '<p class="woocommerce-info">' .
            sprintf(
                /* translators: %s: wishlist type title */
                esc_html__('No %s were found matching your selection.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title('', true))
            ) .
            '</p>';
    }

    public static function notify_of_item_purchased_status($notice, $item)
    {
        if ($item->get_purchased_quantity()) {
            $notice .= ' ' . __('This item has purchases that may be lost if deleted.', 'nm-gift-registry-lite');
        }
        return $notice;
    }
}