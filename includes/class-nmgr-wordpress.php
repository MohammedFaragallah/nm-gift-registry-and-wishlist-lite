<?php
defined('ABSPATH') || exit;

class NMGR_Wordpress
{
    public static function run()
    {
        add_action('init', array(__CLASS__, 'load_plugin_textdomain'), 1);
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_action('init', array(__CLASS__, 'maybe_flush_rewrite_rules'), 999);
        add_action('init', array(__CLASS__, 'add_image_sizes'));
        add_action('wp', array(__CLASS__, 'add_shortcodes'));
        add_filter('query_vars', array(__CLASS__, 'query_vars'));

        add_action('before_delete_post', array(__CLASS__, 'setup_before_delete_post_action'), -1);
        add_action('delete_post', array(__CLASS__, 'setup_delete_action'), -1);
        add_action('trashed_post', array(__CLASS__, 'setup_trashed_action'), -1);
        add_action('untrashed_post', array(__CLASS__, 'setup_untrashed_action'), -1);

        add_action('nmgr_before_delete_wishlist', array(__CLASS__, 'before_delete_wishlist'));

        // Add to template_redirect hook so that it occurs only on frontend (non-ajax) requests
        add_action('template_redirect', array(__CLASS__, 'add_to_wishlist_action'), 20);

        add_filter('nmgr_quantity_input_min', array(__CLASS__, 'set_minimum_input_quantity'), 10);
        add_filter('nmgr_quantity_input_max', array(__CLASS__, 'set_maximum_input_quantity'), 10);
        add_action('wp_loaded', array(__CLASS__, 'show_notice'));
        add_action('wp', array(__CLASS__, 'enable_wishlist'));
        add_action('deleted_user_meta', array(__CLASS__, 'delete_user_wishlists_if_user_disabled'), 10, 3);
        add_filter('posts_search', array(__CLASS__, 'enhance_wishlist_search'));
        add_action('wp', array(__CLASS__, 'maybe_set_user_id_cookie'));
        add_action('nmgr_delete_guest_wishlists', array(__CLASS__, 'delete_guest_wishlists'));
        add_filter('woocommerce_login_redirect', array(__CLASS__, 'login_redirect'), 10, 2);
        add_action('nmgr_data_before_save', array(__CLASS__, 'before_save_wishlist'));
        add_action('nmgr_delete_wishlist_item', array(__CLASS__, 'delete_item_from_cart'), 10, 2);
        add_action('wp_footer', array(__CLASS__, 'add_modal_template'));
        add_action('admin_footer', array(__CLASS__, 'add_modal_template'));
        add_filter('nmgr_account_tabs', array(__CLASS__, 'filter_account_sections'));
        add_action('nmgr_before_shipping', array(__CLASS__, 'show_shipping_address_required_notice'));
        add_filter('wp_insert_post_data', array(__CLASS__, 'insert_post_data'), 10, 2);

        function get_wishlist_schema()
        {
            $product_controller = new WC_REST_Products_Controller();

            $wishlist_schema = array(
                '$schema'    => 'http://json-schema.org/draft-04/schema#',
                'title'      => 'gift registry',
                'type'       => 'object',
                'properties' => array(
                    "id" => array(
                        'type' => 'integer',
                        'description' => ''
                    ),
                    "title" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "status" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "description" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "slug" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "date_created" => array(
                        'type' => 'date',
                        'description' => ''
                    ),
                    "first_name" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "last_name" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "partner_first_name" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "partner_last_name" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "email" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "event_date" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "ship_to_account_address" => array(
                        'type' => 'boolean',
                        'description' => ''
                    ),
                    "shipping" => array(
                        'type' => 'object',
                        'description' => '',
                        'properties' => array(
                            "first_name" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "last_name" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "company" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "address_1" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "address_2" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "city" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "postcode" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "country" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                            "state" => array(
                                'type' => 'string',
                                'description' => '',
                            ),
                        )
                    ),
                    "fulfilled" => array(
                        'type' => 'boolean',
                        'description' => ''
                    ),
                    "date_fulfilled" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "nmgr_user_id" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "nmgr_guest" => array(
                        'type' => 'string',
                        'description' => ''
                    ),
                    "items" => array(
                        'type' => 'array',
                        'items' => array(
                            'type' => 'object',
                            'properties' => array(
                                "id" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "wishlist_id" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "name" => array(
                                    'type' => 'string',
                                    'description' => ''
                                ),
                                "date_created" => array(
                                    'type' => 'date',
                                    'description' => ''
                                ),
                                "date_modified" => array(
                                    'type' => 'date',
                                    'description' => ''
                                ),
                                "product_id" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "variation_id" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "variation" => array(
                                    "type" => "array",
                                    "items" => array(
                                        'type' => 'integer',
                                        'description' => ''
                                    )
                                ),
                                "quantity" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "purchased_quantity" => array(
                                    'type' => 'integer',
                                    'description' => ''
                                ),
                                "purchased_quantity" => array(
                                    'type' => 'string',
                                    'description' => ''
                                ),
                                "variation" => array(
                                    "type" => "array",
                                    "items" => array(
                                        'type' => 'mixed',
                                        'description' => ''
                                    )
                                ),
                            )
                        )
                    ),
                    "products" => array(
                        'type' => 'array',
                        "items" => array(
                            "type" => "object",
                            "properties" => $product_controller->get_public_item_schema()
                        )
                    ),
                    "fulfilled" => array(
                        'type' => 'boolean',
                        'description' => ''
                    ),
                    "item_count" => array(
                        'type' => 'integer',
                        'description' => ''
                    ),
                    "item_purchased_count" => array(
                        'type' => 'integer',
                        'description' => ''
                    ),
                    "total" => array(
                        'type' => 'integer',
                        'description' => ''
                    ),
                    "permalink" => array(
                        'type' => 'string',
                        'format' => 'url',
                        'description' => ''
                    ),
                ),
            );

            return $wishlist_schema;
        }

        add_action(
            'rest_api_init',
            function () {
                $namespace = 'frego-mobile-builder/v1';

                $base = 'gift-registry';

                $product_controller = new WC_REST_Products_Controller();
                $product_controller->get_collection_params();

                register_rest_route(
                    $namespace,
                    '/' . $base,
                    [
                        [
                            'methods'             => WP_REST_Server::READABLE,
                            'callback'            => 'get_wishlist',
                            'args'                => array_merge([
                                'items' => [
                                    'required'    => false,
                                    'type'        => 'boolean',
                                    'description' => 'Whether to get the wishlist items with the data.',
                                    'default'     => false,
                                ],
                            ], $product_controller->get_collection_params()),
                            'permission_callback' => '__return_true',
                        ],
                        'schema' => 'get_wishlist_schema'
                    ]
                );

                register_rest_route(
                    $namespace,
                    '/' . $base . '/add-item',
                    [
                        [
                            'methods'             => WP_REST_Server::CREATABLE,
                            'callback'            => 'add_item',
                            'args'                => [
                                'product_id' => [
                                    'required'    => true,
                                    'type'        => 'integer',
                                    'description' => 'product id.',
                                ],
                                'quantity' => [
                                    'required'    => true,
                                    'type'        => 'integer',
                                    'description' => 'ID.',
                                    'default' => 1
                                ],
                                'favourite' => [
                                    'required'    => false,
                                    'type'        => 'mixed'
                                ],
                                'variation' => [
                                    'required'    => false,
                                    'type'        => 'mixed'
                                ],
                            ],
                            'permission_callback' => '__return_true',
                        ],
                    ]
                );

                register_rest_route(
                    $namespace,
                    '/' . $base . '/remove-item',
                    [
                        [
                            'methods'             => WP_REST_Server::CREATABLE,
                            'callback'            => 'remove_item',
                            'args'                => [
                                'item_id' => [
                                    'required'    => false,
                                    'type'        => 'integer',
                                    'description' => 'Item ID to delete.',
                                ],
                                'clear' => [
                                    'required'    => false,
                                    'type'        => 'boolean',
                                    'description' => 'delete all items',
                                ],
                            ],
                            'permission_callback' => '__return_true',
                        ],
                    ]
                );

                register_rest_route(
                    $namespace,
                    '/' . $base . '/update-item',
                    [
                        [
                            'methods'             => WP_REST_Server::CREATABLE,
                            'callback'            => 'update_item',
                            'args' => [
                                'item_id' => [
                                    'required'    => true,
                                    'type'        => 'integer',
                                    'description' => 'Item ID to update.',
                                ],
                                'quantity' => [
                                    'required'    => true,
                                    'type'        => 'integer',
                                    'description' => 'new updated quantity',
                                    'default' => 1
                                ],
                                'purchased_quantity' => [
                                    'required'    => true,
                                    'type'        => 'integer',
                                    'description' => 'new updated purchased quantity',
                                    'default' => 1
                                ]
                            ],
                            'permission_callback' => '__return_true',
                        ],
                    ]
                );
            }
        );

        function get_wishlist(WP_REST_Request $request)
        {
            $params      = $request->get_params();
            $wishlist_id = (int) nmgr_get_user_default_wishlist_id(get_current_user_id());

            if ($wishlist_id > 0) {
                try {
                    $wishlist_class = new NMGR_Wishlist($wishlist_id);

                    if (!$wishlist_class->is_active()) {
                        return false;
                    }

                    $wishlist_obj = $wishlist_class->get_data($params['items']);

                    if ($params['items']) {
                        $wishlist_obj['products'] = array();
                        $ids = array();

                        foreach ($wishlist_class->get_items() as $key => $item) {
                            $d = new NMGR_Wishlist_Item($key);
                            array_push($ids, $d->get_product_id());
                        }

                        if (count($ids)) {
                            $product_controller = new WC_REST_Products_Controller();

                            $request->set_param('include', $ids);

                            $products =  $product_controller->get_items($request);

                            $wishlist_obj['products'] = $products->get_data();
                        }

                        $items = array();

                        foreach ($wishlist_obj['items'] as $key => $item) {
                            $items[] = array(
                                'id' => (int) $item['id'],
                                "wishlist_id" => (int) $item['wishlist_id'],
                                "name" => $item['name'],
                                "date_created" =>  $item['date_created'],
                                "date_modified" =>  $item['date_modified'],
                                "product_id" => (int) $item['product_id'],
                                "variation_id" => (int) $item['variation_id'] > 0 ? (int) $item['variation_id'] : null,
                                "variation" =>  $item['variation'],
                                "quantity" => (int) $item['quantity'],
                                "purchased_quantity" => (int) $item['purchased_quantity'],
                                "unique_id" =>  $item['unique_id'],
                                "quantity_reference" =>  $item['quantity_reference'],
                            );
                        }

                        $wishlist_obj['items'] = $items;
                    }

                    $wishlist_obj['fulfilled'] = $wishlist_class->is_fulfilled();
                    $wishlist_obj['item_count'] = $wishlist_class->get_item_count();
                    $wishlist_obj['item_purchased_count'] = $wishlist_class->get_item_purchased_count();
                    $wishlist_obj['total'] = $wishlist_class->get_total();
                    $wishlist_obj['permalink'] = $wishlist_class->get_permalink();

                    return $wishlist_obj;
                } catch (Exception $e) {
                    return rest_ensure_response($e);
                }
            }

            return rest_ensure_response($wishlist_id);
        }

        function add_item(WP_REST_Request $request)
        {
            $params      = $request->get_params();
            $wishlist_id = (int) nmgr_get_user_default_wishlist_id(get_current_user_id());

            if ($wishlist_id > 0) {
                try {
                    $wishlist_class = new NMGR_Wishlist($wishlist_id);

                    if (!$wishlist_class->is_active()) {
                        return false;
                    }

                    $product = wc_get_product($params['product_id']);

                    if (!$product) throw new Exception($product);

                    return $wishlist_class->add_item(
                        $product,
                        $params['quantity'],
                        $params['favourite'],
                        $params['variation'],
                    );
                } catch (Exception $e) {
                    return rest_ensure_response($e);
                }
            }

            return rest_ensure_response($wishlist_id);
        }


        function remove_item(WP_REST_Request $request)
        {
            $params      = $request->get_params();
            $wishlist_id = (int) nmgr_get_user_default_wishlist_id(get_current_user_id());

            if ($wishlist_id > 0) {
                try {
                    $wishlist_class = new NMGR_Wishlist($wishlist_id);

                    if (!$wishlist_class->is_active()) {
                        return false;
                    }

                    if ($params['clear'])  return $wishlist_class->delete_items();

                    return $wishlist_class->delete_item(
                        $params['item_id'],
                    );
                } catch (Exception $e) {
                    return rest_ensure_response($e);
                }
            }

            return rest_ensure_response($wishlist_id);
        }

        function update_item(WP_REST_Request $request)
        {
            $params      = $request->get_params();
            $wishlist_id = (int) nmgr_get_user_default_wishlist_id(get_current_user_id());

            if ($wishlist_id > 0) {
                try {
                    $wishlist_class = new NMGR_Wishlist($wishlist_id);

                    if (!$wishlist_class->is_active()) {
                        return false;
                    }

                    $item = $wishlist_class->get_Item($params['item_id']);

                    if ($item) {
                        $item->set_props(
                            array(
                                'quantity' => $params['quantity'],
                                'purchased_quantity' => $params['purchased_quantity'],
                            )
                        );

                        return $item->save();
                    }
                } catch (Exception $e) {
                    return rest_ensure_response($e);
                }
            }

            return rest_ensure_response($wishlist_id);
        }

        self::set_add_to_wishlist_button_position();
    }

    public static function load_plugin_textdomain()
    {
        load_plugin_textdomain('nm-gift-registry-lite', false, nmgr()->path . 'languages');
    }

    public static function register_post_types()
    {
        if (post_type_exists(nmgr()->post_type)) {
            return;
        }

        register_post_type(
            nmgr()->post_type,
            array(
                'labels' => array(
                    'name' => __('NM Gift Registry', 'nm-gift-registry-lite'),
                    'singular_name' => __('NM Gift Registry', 'nm-gift-registry-lite'),
                    'all_items' => __('All Wishlists', 'nm-gift-registry-lite'),
                    'menu_name' => __('NM Gift Registry', 'nm-gift-registry-lite'),
                    'add_new_item' => __('Add new wishlist', 'nm-gift-registry-lite'),
                    'edit' => __('Edit', 'nm-gift-registry-lite'),
                    'edit_item' => __('Edit wishlist', 'nm-gift-registry-lite'),
                    'new_item' => __('New wishlist', 'nm-gift-registry-lite'),
                    'view_item' => __('View wishlist', 'nm-gift-registry-lite'),
                    'view_items' => __('View wishlists', 'nm-gift-registry-lite'),
                    'search_items' => __('Search wishlists', 'nm-gift-registry-lite'),
                    'not_found' => __('No wishlists found', 'nm-gift-registry-lite'),
                    'not_found_in_trash' => __('No wishlists found in trash', 'nm-gift-registry-lite'),
                    'filter_items_list' => __('Filter wishlists', 'nm-gift-registry-lite'),
                    'items_list' => __('Wishlists list', 'nm-gift-registry-lite'),
                    'item_published' => __('Wishlist published', 'nm-gift-registry-lite'),
                    'item_published_privately' => __('Wishlist published privately', 'nm-gift-registry-lite'),
                    'item_updated' => __('Wishlist updated', 'nm-gift-registry-lite'),
                ),
                'description' => __('Add gift registries and wishlists to your store.', 'nm-gift-registry-lite'),
                'public' => true,
                'show_ui' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'show_in_menu' => true,
                'map_meta_cap' => true,
                'hierarchical' => false,
                'show_in_nav_menus' => false,
                'rewrite' => array(
                    'slug' => nmgr_get_option('permalink_base'),
                ),
                'query_var' => true,
                'supports' => array('title'),
                'capability_type' => array(nmgr()->post_type, nmgr()->post_type_plural),
                'has_archive' => false,
                'menu_icon' => 'dashicons-heart',
            )
        );
    }

    /**
     * Add rewrite rules
     *
     * @since 2.0.0
     */
    public static function add_rewrite_rules()
    {
        $page_id = nmgr_get_option('wishlist_account_page_id');
        if ($page_id) {
            $page = get_post($page_id);
            if (is_a($page, 'WP_Post')) {
                $query_key = nmgr_query_key('wishlist');
                $pagename = $page->post_name;

                add_rewrite_tag('%' . $query_key . '%', '([^/]*');
                add_rewrite_rule('^' . $pagename . '/([^/]*)', 'index.php?pagename=' . $pagename . '&' . $query_key . '=$matches[1]', 'top');
            }
        }
    }

    public static function maybe_flush_rewrite_rules()
    {
        if ('yes' == get_option('nmgr_flush_rewrite_rules')) {
            delete_option('nmgr_flush_rewrite_rules');
            flush_rewrite_rules();
        }
    }

    /**
     * Add nmgr images sizes to wordpress
     *
     * nmgr_medium - size for wishlist featured image, used on account page and single wishlist page
     * @since 1.1.5 nmgr_thumbnail - used in items table, wishlist cart and add to wishlist template
     */
    public static function add_image_sizes()
    {
        add_image_size('nmgr_medium', nmgr()->post_thumbnail_size, nmgr()->post_thumbnail_size, true);
        add_image_size('nmgr_thumbnail', apply_filters('nmgr_thumbnail_size', 90));
    }

    /**
     * Set standard attributes for wishlist post thumbnail
     */
    public static function set_image_attributes($attr, $attachment, $size)
    {
        if ('nmgr_medium' == $size) {
            $wishlist = nmgr_get_wishlist($attachment->post_parent);
            if ($wishlist && !$attr['alt']) {
                $attr['alt'] = $wishlist->get_title();
            }
            $attr['class'] = $attr['class'] . ' nmgr-post-thumbnail';
        }
        return $attr;
    }

    /**
     * Shortcode tags used on account page
     *
     * @return array
     */
    public static function get_account_shortcodes()
    {
        return array(
            'nmgr_get_account_template' => 'nmgr_account',
            'nmgr_get_account_wishlist_template' => 'nmgr_account_wishlist',
            'nmgr_get_overview_template' => 'nmgr_overview',
            'nmgr_get_profile_template' => 'nmgr_profile',
            'nmgr_get_items_template' => 'nmgr_items',
            'nmgr_get_shipping_template' => 'nmgr_shipping',
            'nmgr_get_share_template' => 'nmgr_share',
        );
    }

    public static function add_shortcodes()
    {
        $shortcodes = array_merge(
            array(
                'nmgr_get_search_form' => 'nmgr_search_form',
                'nmgr_get_search_results_template' => 'nmgr_search_results',
                'nmgr_get_search_template' => 'nmgr_search',
                'nmgr_get_enable_wishlist_form' => 'nmgr_enable_wishlist_form',
                'nmgr_get_add_to_wishlist_button' => 'nmgr_add_to_wishlist',
                'nmgr_get_wishlist_template' => 'nmgr_wishlist',
                'nmgr_get_cart_template' => 'nmgr_cart'
            ),
            self::get_account_shortcodes()
        );

        foreach ($shortcodes as $function => $shortcode) {
            add_shortcode(apply_filters("{$shortcode}_shortcode_tag", $shortcode), $function);
        }
    }

    /**
     * Fires before a post is deleted, at the start of wp_delete_post().
     *
     * @param int $postid Post ID.
     */
    public static function setup_before_delete_post_action($post_id)
    {
        if (is_nmgr_post($post_id)) {
            do_action('nmgr_before_delete_wishlist', $post_id);
        }
    }

    /**
     * Fires immediately before a post is deleted from the database.
     *
     * @param int $postid Post ID.
     */
    public static function setup_delete_action($post_id)
    {
        if (is_nmgr_post($post_id)) {
            do_action('nmgr_delete_wishlist', $post_id);
        }
    }

    /**
     * Fires after a post is sent to the trash.
     *
     * @param int $post_id Post ID.
     */
    public static function setup_trashed_action($post_id)
    {
        if (is_nmgr_post($post_id)) {
            do_action('nmgr_trashed_wishlist', $post_id);
        }
    }

    /**
     * Fires after a post is restored from the trash.
     *
     * @param int $post_id Post ID.
     */
    public static function setup_untrashed_action($post_id)
    {
        if (is_nmgr_post($post_id)) {
            do_action('nmgr_untrashed_wishlist', $post_id);
        }
    }

    /**
     * Delete wishlist items and images before permanently deleting a wishlist
     *
     * @param init $wishlist_id Wishlist id
     */
    public static function before_delete_wishlist($wishlist_id)
    {
        $wishlist = nmgr_get_wishlist($wishlist_id);
        // Delete wishlist items
        $wishlist->delete_items();
    }

    /**
     * Perform cleanup actions on wishlist data on delete or trash
     *
     * @param int $wishlist_id Wishlist id
     */
    public static function clean_wishlist_data_on_delete($wishlist_id)
    {
        $post = get_post($wishlist_id);

        /**
         * If the wishlist is being deleted or trashed, and it is the same wishlist stored
         * in the user meta table, delete the user meta
         */
        if (doing_action('nmgr_delete_wishlist') || doing_action('nmgr_trashed_wishlist')) {
            $stored_wishlist_id = get_user_meta($post->post_author, 'nmgr_wishlist_id', true);

            if ($stored_wishlist_id && ($stored_wishlist_id == $wishlist_id)) {
                delete_user_meta($post->post_author, 'nmgr_wishlist_id');
            }
            return;
        }

        /**
         * If the wishlist is being untrashed (this happens only in admin), restore the user meta
         * and delete any new wishlist the user has created if it exists
         *
         * (Admin managed restoration of trashed wishlists takes precedence over
         * user created wishlists)
         */
        if (doing_action('nmgr_untrashed_wishlist')) {
            $new_wishlist_id = get_user_meta($post->post_author, 'nmgr_wishlist_id', true);
            if ($new_wishlist_id) {
                wp_delete_post($new_wishlist_id, true);
            }
            update_user_meta($post->post_author, 'nmgr_wishlist_id', $wishlist_id);
            return;
        }
    }

    /**
     * Perform update actions on wishlist data on save
     *
     * @param int $wishlist_id Wishlist id
     */
    public static function update_wishlist_data_on_save($wishlist_id)
    {
        $post = get_post($wishlist_id);

        /**
         * If a wishlist post is being created
         * possibly update the user's user_meta wishlist_id value
         */
        if (!doing_action('nmgr_updated_wishlist')) {
            // Add the wishlist_id as a user meta value if none exists
            if (0 != $post->post_author && !get_user_meta($post->post_author, 'nmgr_wishlist_id', true)) {
                update_user_meta($post->post_author, 'nmgr_wishlist_id', $wishlist_id);
            }
        } else {
            /**
             * During an update, if we are in the admin area
             * possibly update the user's user_meta wishlist_id value
             */
            if (is_nmgr_admin()) {
                global $wpdb;

                // Check if the wishlist being updated is in the user meta table as two users cannot have the same wishlist
                $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'nmgr_wishlist_id' AND meta_value=%s", $wishlist_id));

                /**
                 * if the wishlist is in the user meta table and the author of the wishlist is not the same as
                 * the present author of the wishlist. update the author of the wishlist to the present one
                 */
                if ($user_id && absint($user_id) != absint($post->post_author)) {
                    $wpdb->update(
                        $wpdb->usermeta,
                        array('user_id' => $post->post_author),
                        array(
                            'meta_key' => 'nmgr_wishlist_id',
                            'meta_value' => $wishlist_id
                        ),
                        array('%d'),
                        array('%s', '%d')
                    );
                }

                // If the wishlist is not in the user meta table, add it
                if (!$user_id) {
                    update_user_meta($post->post_author, 'nmgr_wishlist_id', $wishlist_id);
                }
            }
        }
    }

    /**
     * Set the mimimum purchase quantity for a product
     *
     * The 'nmgr_quantity_input_min' filter is a substitute for the woocommerce_quantity_input_min filter
     * which is usually used with the 'woocommerce_quantity_input' function to output quantity input fields
     * for products
     *
     * @param int $quantity The quantity to set for the product
     * @return int The quantity set if greater than 0, or 0
     */
    public static function set_minimum_input_quantity($quantity)
    {
        return max($quantity, 0);
    }

    /**
     * Set the maximum purchase quantity for a product
     *
     * The 'nmgr_quantity_input_max' filter is a substitute for the woocommerce_quantity_input_max filter
     * which is usually used with the 'woocommerce_quantity_input' function to output quantity input fields
     * for products
     *
     * @param int $quantity The quantity to set for the product
     * @return mixed The quantity set if greater than 0, or null (This means unlimited quantity)
     */
    public static function set_maximum_input_quantity($quantity)
    {
        return 0 < $quantity ? $quantity : '';
    }

    /**
     * Show various notices related to adding a wishlist depending on the situation
     */
    public static function show_notice()
    {
        if (!isset($_REQUEST['nmgr-notice'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $notice = sanitize_text_field(wp_unslash($_REQUEST['nmgr-notice'])); // phpcs:ignore WordPress.Security.NonceVerification

        $redirect = is_nmgr_guest() ? false : true;

        switch ($notice) {
            case 'select-product':
                $product_type = isset($_REQUEST['nmgr-pt']) ? sanitize_text_field(wp_unslash($_REQUEST['nmgr-pt'])) : null; // phpcs:ignore WordPress.Security.NonceVerification

                if ('variable' == $product_type) {
                    wc_add_notice(
                        sprintf(
                            /* translators: %s: wishlist type title */
                            __('Select a variation of this product to add to your %s.', 'nm-gift-registry-lite'),
                            esc_html(nmgr_get_type_title())
                        ),
                        'notice'
                    );
                } elseif ('grouped' == $product_type) {
                    wc_add_notice(
                        sprintf(
                            /* translators: %s: wishlist type title */
                            __('Select option(s) of this product to add to your %s.', 'nm-gift-registry-lite'),
                            esc_html(nmgr_get_type_title())
                        ),
                        'notice'
                    );
                }
                break;

            case 'require-login':
                $redirect = false;
                wc_add_notice(
                    sprintf(
                        /* translators: %s: wishlist type title */
                        __('Login to add products to your %s.', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ),
                    'notice'
                );
                break;

            case 'create-wishlist':
                wc_add_notice(
                    sprintf(
                        /* translators: %s: wishlist type title */
                        __('Create a %s to add products to it.', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ),
                    'notice'
                );
                break;
        }

        if ($redirect) {
            $query_string = '';
            parse_str(wc_clean(wp_unslash($_SERVER['QUERY_STRING'])), $query_string); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            $remove_keys = array_filter(array_keys($query_string), function ($val) {
                return 'nmgr-redirect' !== $val && false !== strpos($val, 'nmgr-');
            });

            if ($remove_keys) {
                wp_redirect(remove_query_arg($remove_keys));
                exit;
            }
        }
    }

    /**
     * Enable or disable the wishlist module for a specific user
     */
    public static function enable_wishlist()
    {
        /* translators: %s: wishlist type title */
        $enabled_text = sprintf(esc_html__('You have successfully enabled the %s module.', 'nm-gift-registry-lite'), nmgr_get_type_title());
        /* translators: %s: wishlist type title */
        $view_text = sprintf(esc_html__('View your %s dashboard', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));

        $enabled_html = sprintf(
            '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s',
            esc_url(nmgr_get_account_url()),
            $view_text,
            $enabled_text
        );
        $enabled_notice = apply_filters('nmgr_enabled_wishlist_notice', $enabled_html);

        /* translators: %s: wishlist type title */
        $disabled_text = sprintf(__('You have successfully disabled the %s module.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
        $disabled_notice = apply_filters('nmgr_disabled_wishlist_notice', $disabled_text);

        if (
            isset($_REQUEST['nmgr-enable-wishlist-nonce']) &&
            wp_verify_nonce(sanitize_key($_REQUEST['nmgr-enable-wishlist-nonce']), 'nmgr_enable_wishlist')
        ) {
            if (isset($_REQUEST['nmgr_enable_wishlist'])) {
                update_user_meta(get_current_user_id(), 'nmgr_enable_wishlist', absint($_REQUEST['nmgr_enable_wishlist']));
                wc_add_notice($enabled_notice, 'success');
            } else {
                delete_user_meta(get_current_user_id(), 'nmgr_enable_wishlist');
                wc_add_notice($disabled_notice, 'success');
            }
        }
    }

    /**
     * Delete a user's wishlists if he has disabled the wishlist module
     */
    public static function delete_user_wishlists_if_user_disabled($meta_ids, $user_id, $meta_key)
    {
        if ('nmgr_enable_wishlist' !== $meta_key || 0 === $user_id) {
            return;
        }

        $wishlists = nmgr_get_user_wishlists($user_id);
        if (!empty($wishlists)) {
            foreach ($wishlists as $wishlist) {
                $wishlist->delete();
            }
        }
    }

    /**
     * Improve the default wishlist post type search to also search in certain postmeta fields
     */
    public static function enhance_wishlist_search($where)
    {
        global $wpdb;

        if (is_admin() || !is_main_query() || !is_nmgr_search() || !get_search_query()) {
            return $where;
        }

        // Locations in postmeta table to search for search term
        $meta_keys_to_search = array(
            '_last_name',
            '_first_name',
            '_partner_first_name',
            '_partner_last_name',
            '_email'
        );

        $meta_args = array('relation' => 'OR');

        foreach ($meta_keys_to_search as $key) {
            $meta_args[] = array(
                'key' => $key,
                'value' => get_search_query(),
                'compare' => 'like',
            );
        }

        // Use WP_Meta_Query to compose sql for future maintenance
        $meta_query = new WP_Meta_Query($meta_args);
        $sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');

        $search_post_ids = array();
        $found_post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} " . $sql['join'] . $sql['where']);

        if (count($found_post_ids) > 0) {
            $search_post_ids = array_filter(array_unique(array_map('absint', $found_post_ids)));
        }

        if (count($search_post_ids) > 0) {
            $where = str_replace(
                'AND (((',
                "AND ( ({$wpdb->posts}.ID IN (" . implode(',', $search_post_ids) . ")) OR ((",
                $where
            );
        }

        return $where;
    }

    /**
     * Adds a product to a wishlist directly from the $_REQUEST global
     *
     * query key, value pairs expected in the $_REQUEST global
     * - nmgr_pid => {int} Product id (required)
     * - nmgr_wid => {int} Wishlist id (required)
     * -  nmgr_qty => {int} Quantity (optional)
     *
     * $_GET example: ?nmgr_pid=99&nmgr_wid=3&nmgr_qty=1
     */
    public static function add_to_wishlist_action()
    {
        $pid = nmgr_query_key('product_id');
        $wid = nmgr_query_key('wishlist_id');
        $request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification
        // if we don't have product id or wishlist id query key in the query, return
        if (
            (!isset($request[$pid]) || !is_numeric(sanitize_key(wp_unslash($request[$pid])))) ||
            (!isset($request[$wid]) || !is_numeric(sanitize_key(wp_unslash($request[$wid]))))
        ) {
            return;
        }

        $product_id = absint(wp_unslash($request[$pid]));
        $wishlist_id = absint(wp_unslash($request[$wid]));
        $variation_id = isset($request[nmgr_query_key('variation_id')]) ? absint(wp_unslash($request[nmgr_query_key('variation_id')])) : 0;
        $wishlist = nmgr_get_wishlist($wishlist_id, true);
        $product = wc_get_product($variation_id ? $variation_id : $product_id);

        // Serialized form values from default wc variaton form on product page
        $variation_form_values = isset($request['nmgr_wc_form_values']) ? $request['nmgr_wc_form_values'] : array();

        // Flag to check if product was added to the wishlist
        $result = false;

        try {
            // if the wishlist doesn't exist we cannot add the product to it
            if (!$wishlist) {
                /* translators: %s: wishlist type title */
                throw new Exception(sprintf(esc_html__('The specified %s doesn\'t exist.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title())));
            }

            // if the product doesn't exist we cannot add it to the wishlist
            if (!$product) {
                /* translators: %s: wishlist type title */
                throw new Exception(sprintf(esc_html__('This item cannot be added to your %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title())));
            }

            // If the product is a variation and we don't have values from the variations form, throw notice
            if ($product->is_type('variation') && empty($variation_form_values)) {
                throw new Exception(esc_html__('Please select options for this product', 'nm-gift-registry-lite'));
            }

            if ($product->is_type('grouped')) {
                $qtys = isset($request[nmgr_query_key('quantity')]) ? (array) $request[nmgr_query_key('quantity')] : array();
                $posted_quantities = array_filter(array_map('absint', $qtys), function ($v) {
                    return $v > 0;
                });

                if (empty($posted_quantities)) {
                    /* translators: %s: wishlist type title */
                    throw new Exception(sprintf(
                        esc_html__('Please choose the quantity of items you wish to add to your %s.', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ));
                }

                $added_to_wishlist = array();
                $error_msgs = array();

                foreach ($posted_quantities as $product_id => $quantity) {
                    $product = wc_get_product($product_id);
                    $favourite = null;
                    try {
                        $this_result = nmgr_add_to_wishlist($wishlist, $product, $quantity, $favourite);
                        if (!empty($this_result)) {
                            $added_to_wishlist[$product_id] = $quantity;
                        }
                    } catch (Exception $e) {
                        $error_msgs[] = $e->getMessage();
                    }
                }

                if (!empty($added_to_wishlist)) {
                    $result = true;
                    nmgr_add_to_wishlist_notice($wishlist, $added_to_wishlist, true);
                }

                if (!empty($error_msgs)) {
                    foreach ($error_msgs as $message) {
                        throw new Exception($message);
                    }
                }
            } else {
                $q = isset($request[nmgr_query_key('quantity')]) ? $request[nmgr_query_key('quantity')] : 1;
                $quantity = wc_stock_amount(wp_unslash($q));
                $favourite = null;
                $variations = $product->is_type('variation') ? nmgr_get_posted_variations($variation_id, $variation_form_values) : array();
                $result = nmgr_add_to_wishlist($wishlist, $product, $quantity, $favourite, $variations);
                if (!empty($result)) {
                    nmgr_add_to_wishlist_notice($wishlist, $result, true);
                }
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
        }

        // Return details of the product added to the wishlist
        if ($result) {
            return array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'wishlist_id' => $wishlist_id,
            );
        }
        return false;
    }

    /**
     * Redirect the user to various pages after login based on whether he has created a wishlist or not
     */
    public static function login_redirect($redirect, $user)
    {
        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_REQUEST['nmgr-redirect']) && !empty($_REQUEST['nmgr-redirect'])) {
            if (nmgr_get_user_wishlists_count($user->ID)) {
                $redirect = sanitize_text_field(wp_unslash($_REQUEST['nmgr-redirect']));
            } else {
                $redirect = add_query_arg('nmgr-notice', 'create-wishlist', nmgr_get_account_url());
            }
        }
        // phpcs:enable
        return $redirect;
    }

    /**
     * Plugin query vars
     *
     * @since 1.0.4
     */
    public static function query_vars($vars)
    {
        $vars[] = 'nmgr_s';
        return $vars;
    }

    /**
     * Set the add-to-wishlist button position on single and archive pages
     *
     * @since 2.0.0
     */
    public static function set_add_to_wishlist_button_position()
    {
        $archive_display_hook = nmgr_get_option('add_to_wishlist_button_position_archive', 'woocommerce_after_shop_loop_item');

        if (0 === strpos($archive_display_hook, 'woocommerce_')) {
            $priority = 'woocommerce_before_shop_loop_item' === $archive_display_hook ? 5 : 20;
            add_action($archive_display_hook, 'nmgr_add_to_wishlist_button', $priority);
        } else {
            // This default must be there so that the button would always be displayed on the archive page
            add_action('woocommerce_before_shop_loop_item_title', 'nmgr_add_to_wishlist_button', 20);
        }

        $single_display_priority = nmgr_get_option('add_to_wishlist_button_position_single', 35);

        if (0 === strpos($single_display_priority, 'woocommerce_')) {
            add_action($single_display_priority, 'nmgr_add_to_wishlist_button');
        } elseif (0 === strpos($single_display_priority, 'thumbnail_')) {
            add_action('woocommerce_product_thumbnails', 'nmgr_add_to_wishlist_button');
        } else {
            // This default must be there so that the button would always be displayed on the single page
            add_action('woocommerce_single_product_summary', 'nmgr_add_to_wishlist_button', (int) $single_display_priority);
        }
    }

    public static function maybe_set_user_id_cookie()
    {
        if (is_nmgr_guest() && !isset($_COOKIE['nmgr_user_id'])) {
            $days = (int) apply_filters('nmgr_guest_wishlist_expiry_days', nmgr_get_option('guest_wishlist_expiry_days'));
            $val = 1 > $days ? 0 : (365 < $days ? 365 : $days);
            $expiration = 0 === $val ? 0 : strtotime($val . ' DAYS', time());
            nmgr_setcookie('nmgr_user_id', nmgr_generate_user_id(), $expiration);
        }
    }

    /**
     * Delete guest wishlists if they have expired based on the registered cookie expiry time
     */
    public static function delete_guest_wishlists()
    {
        global $wpdb;

        $post_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_nmgr_guest'  AND meta_value != '' ");

        foreach ($post_ids as $id) {
            $post = get_post($id);
            $creation_date = strtotime($post->post_date);
            $expiry_days = (int) apply_filters('nmgr_guest_wishlist_expiry_days', nmgr_get_option('guest_wishlist_expiry_days'));
            $expiry_date = strtotime($expiry_days . ' DAYS', $creation_date);
            if (
                $expiry_date < time() &&
                0 == $post->post_author &&
                'nm_gift_registry' === $post->post_type &&
                'trash' !== $post->post_type
            ) {
                $wishlist = nmgr_get_wishlist($id);
                if ($wishlist) {
                    $wishlist->delete();
                }
            }
        }
    }

    /**
     * Actions to perform before saving a wishlist
     * @param Object $object The object being saved e.g. NMGR_Wishlist or NMGR_Wishlist_Item
     */
    public static function before_save_wishlist($object)
    {
        // If the wishlist belongs to a guest, ensure the 'nmgr_guest' meta key is set to the guest's user id
        if (is_a($object, 'NMGR_Wishlist')) {
            $user_id = $object->get_user_id();
            if ($user_id && !is_numeric($user_id)) {
                $object->set_prop('nmgr_guest', $user_id);
            }
        }
    }

    /**
     * Delete a wishlist item from woocommerce cart if it is deleted from the wishlist
     *
     * @since 2.0.0
     * @param int $item_id The item id
     * @param int $wishlist_id The wishlist id
     */
    public static function delete_item_from_cart($item_id, $wishlist_id)
    {
        if (is_a(wc()->cart, 'WC_Cart') && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $key => $cart_item) {
                if (isset($cart_item['nm_gift_registry'])) {
                    if (
                        absint($wishlist_id === absint($cart_item['nm_gift_registry']['wishlist_id'])) &&
                        nmgr_get_wishlist($wishlist_id, true) &&
                        absint($item_id) === absint($cart_item['nm_gift_registry']['wishlist_item_id'])
                    ) {
                        wc()->cart->remove_cart_item($key);
                    }
                }
            }
        }
    }

    public static function add_modal_template()
    {
        $close_button = '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>';
?>
<div id="nmgr-modal" class="nmgr-modal modal fade" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg no-transform">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"></h4>
        <?php echo $close_button; ?>
      </div>
      <div class="modal-body">
        <?php echo $close_button; ?>
      </div>
      <div class="modal-footer">
      </div>
    </div>
  </div>
</div>
<div id="nmgr-mago" class="nmgr-modal modal fade" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg no-transform">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <button type="button" class="close nmgr-hide" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-footer">
        <button class="nmgr-dialog-submit-button"><?php esc_html_e('Ok', 'nm-gift-registry-lite'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php
    }

    public static function filter_account_sections($sections)
    {
        if (
            is_nmgr_modal() &&
            (doing_action('wp_ajax_nmgr_dialog_create_new_wishlist') ||
                doing_action('wp_ajax_nopriv_nmgr_dialog_create_new_wishlist'))
        ) {
            $new_wishlist_sections = array_merge(
                array('profile'),
                (array) apply_filters('nmgr_dialog_create_new_wishlist_optional_sections', array('shipping'))
            );

            if (nmgr_get_option('shipping_address_required')) {
                $new_wishlist_sections[] = 'shipping';
            }

            foreach (array_keys($sections) as $key) {
                if (!in_array($key, $new_wishlist_sections)) {
                    unset($sections[$key]);
                }
            }
        }
        return $sections;
    }

    public static function show_shipping_address_required_notice()
    {
        if (
            is_nmgr_modal() &&
            (doing_action('wp_ajax_nmgr_dialog_set_shipping_address') ||
                doing_action('wp_ajax_nopriv_nmgr_dialog_set_shipping_address'))
        ) {
            wc_print_notice(
                sprintf(
                    /* translators: %s: wishlist type title */
                    __('The shipping address for this %s is required before you can add items to it.', 'nm-gift-registry-lite'),
                    esc_html(nmgr_get_type_title())
                ),
                'notice'
            );
        }
    }

    public static function insert_post_data($data, $postarr)
    {
        global $post;

        if ('nm_gift_registry' !== $data['post_type']) {
            return $data;
        }

        if ('trash' === $data['post_status']) {
            return $data;
        }

        // Make sure the wishlist has a post title
        if (!$data['post_title']) {
            $default_title = nmgr_get_option('default_wishlist_title');

            if ($default_title) {
                $data['post_title'] = str_replace(
                    array('{wishlist_type_title}', '{site_title}', '{wishlist_id}'),
                    array(nmgr_get_type_title('c'), get_bloginfo('name'), $post->ID),
                    $default_title
                );
            } else {
                $data['post_title'] = sprintf('%1$s #%2$s', nmgr_get_type_title('c'), $post->ID);
            }
        }

        $is_admin = is_nmgr_admin_request();

        /**
         * On the wishlist edit post in admin area we are expecting the 'nmgr_user_id request
         * parameter which tells us who owns the wishlist. Set the wishlist post_author based on
         * this parameter.
         */
        if ($is_admin && isset($_REQUEST['nmgr_user_id'])) {
            if (is_numeric($_REQUEST['nmgr_user_id'])) {
                // If the user id belongs to a registered user, set it as the post author
                $data['post_author'] = (int) wp_unslash($_REQUEST['nmgr_user_id']);
            } else {
                // For guests, set post author as 0.
                $data['post_author'] = 0;
            }
        } elseif ($is_admin && !is_numeric(get_post_meta($postarr['ID'], '_nmgr_user_id', true))) {
            /**
             * When updating a post, make sure we don't set a post author for guest wishlists.
             * In the admin area $postarr['ID'] is always set so we don't need to check for it since the code
             * is already running in the admin area with is_nmgr_admin_request.
             * (This particular code snippet is necessary for when the post is updated via 'quick edit' in the list table.
             */
            $data['post_author'] = 0;
        }

        /**
         * Users are allowed to have one wishlist.
         * If this user already has, set the post status to auto-draft, and add error message.
         *
         * This code only runs in the admin area so $postarr['ID'] is always set which allows us to compare the
         * current wishlist being saved with the user's default wishlist if any. It is not designed to run on the frontend as
         * the frontend is already set up in a structured way to prevent users from saving multiple wishlists and that should
         * be enough.
         *
         * This code also runs only for registered users. It does not prevent multiple wishlists from being created for guests in
         * the admin as this is pointless since the guest cookies cannot be generated as there is a logged in user.
         */
        if ($is_admin && $data['post_author']) {
            $wishlist_id = nmgr_get_user_default_wishlist_id($data['post_author']);

            /**
             * If the submitted user already has a wishlist and his wishlist is not the same as this wishlist being saved,
             * do not publish this wishlist but leave it at it's previous post status.
             * Using 'get_post_field' is a clever way to see the previous post status of the wishlist (not the one currently being
             * set) and it allow us to keep the wishlist as an auto-draft (if it is a new wishlist), or trashed (if it is an already trashed
             * wishlist). This allows for a smoother user experience in the admin area rather than explicitly setting the post_status
             * as auto-draft or trashed.
             */
            if ($wishlist_id && isset($postarr['ID']) && (int) $postarr['ID'] !== $wishlist_id) {
                $data['post_status'] = get_post_field('post_status', $postarr['ID']);

                $wishlist_type_title = esc_html(nmgr_get_type_title());

                // inform the admin that the submitted user can only have one wishlist
                NMGR_Admin_Post::add_notice(sprintf(
                    /* translators: %1$s: username, %2$s: %3$s: %4$s: wishlist type title */
                    __('The user %1$s already has one %2$s. Users are allowed to have only one %3$s. This %4$s has not been published.', 'nm-gift-registry'),
                    '<strong>' . esc_html(get_the_author_meta('user_login', $data['post_author'])) . '</strong>',
                    $wishlist_type_title,
                    $wishlist_type_title,
                    $wishlist_type_title
                ), 'error');
            }
        }

        return $data;
    }
}