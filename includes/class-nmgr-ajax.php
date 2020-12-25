<?php

defined('ABSPATH') || exit;

/**
 * Handles all plugin ajax events
 */
class NMGR_Ajax
{
    public static function run()
    {
        self::ajax_events();
        add_action('admin_init', array(__CLASS__, 'set_global_variable'), -1);
        add_filter('nmgr_get_wishlist_data', array(__CLASS__, 'get_wishlist_data'), 10, 2);
    }

    /**
     * Make the plugin global variable available for all ajax requests
     *
     * json_decode is used here for situations where JSON.stringify is used in
     * the ajax script to post the global nmgr variable  (like when using the formData object)
     */
    public static function set_global_variable()
    {
        if (wp_doing_ajax() && isset($_REQUEST['nmgr_global'])) { // phpcs:ignore WordPress.Security.NonceVerification
            $nmgr = $_REQUEST['nmgr_global']; // phpcs:ignore

            if (is_string($nmgr)) {
                $GLOBALS['nmgr'] = json_decode(stripslashes($nmgr)); // phpcs:ignore
            } else {
                $GLOBALS['nmgr'] = (object) wc_clean($nmgr);
            }
        }
    }

    /**
     * Add extra properties to wishlist data for ajax requests
     *
     * @param array $data Wishlist data
     * @param NMGR_Wishlist $wishlist
     * @return array
     */
    public static function get_wishlist_data($data, $wishlist)
    {
        if (!wp_doing_ajax()) {
            return $data;
        }

        $data = array_merge($data, array(
            'has_shipping_address' => $wishlist->has_shipping_address(),
        ));

        return $data;
    }

    public static function ajax_events()
    {
        $ajax_events = array(
            'save_profile',
            'save_items',
            'save_shipping',
            'add_item',
            'add_to_wishlist',
            'delete_items',
            'json_search_products',
            'json_search_users',
            'load_overview',
            'load_profile',
            'load_shipping',
            'load_messages',
            'load_settings',
            'dialog_create_new_wishlist',
            'dialog_set_shipping_address',
            'dialog_add_to_wishlist',
            'auto_create_wishlist',
        );

        foreach ($ajax_events as $event) {
            if (method_exists(__CLASS__, $event)) {
                add_action('wp_ajax_nmgr_' . $event, array(__CLASS__, $event));

                add_action('wp_ajax_nopriv_nmgr_' . $event, array(__CLASS__, $event));
            }
        }

        $ajax_nopriv_events = array(
            'load_items',
            'add_to_cart',
            'get_cart_fragments',
            'load_wishlist_cart',
        );

        foreach ($ajax_nopriv_events as $event) {
            if (method_exists(__CLASS__, $event)) {
                add_action('wp_ajax_nmgr_' . $event, array(__CLASS__, $event));
                add_action('wp_ajax_nopriv_nmgr_' . $event, array(__CLASS__, $event));
            }
        }

        /**
         * Remove woocommerce's add to cart action when running our own so that the add to cart action
         * runs only once for us
         */
        if (wp_doing_ajax() && isset($_REQUEST['action']) && ('nmgr_add_to_cart' === $_REQUEST['action'])) { // phpcs:ignore WordPress.Security.NonceVerification
            remove_action('wp_loaded', array('WC_Form_Handler', 'add_to_cart_action'), 20);
        }
    }

    public static function add_to_wishlist()
    {
        // check_ajax_referer('nmgr-frontend');

        $result = NMGR_Wordpress::add_to_wishlist_action();

        // We are expecting notices from the add to wishlist action, so get them
        $error = wc_notice_count('error') ? true : false;
        $success = wc_notice_count('success') ? true : false;

        // This clears the notices after printing so we get it last
        $notice = wc_print_notices(true);

        wp_send_json(array(
            'notice' => $notice,
            'error' => $error,
            'success' => $success,
            'result' => $result,
        ));
    }

    public static function add_to_cart()
    {
        if (!isset($_POST['nmgr_items']) || empty($_POST['nmgr_items'])) {
            wp_die(-1);
        }

        $items_data = array();
        $item = reset($_POST['nmgr_items']);

        $product_id = (int) $item['add-to-cart'];
        $quantity = empty($item['quantity']) ? 1 : wc_stock_amount(wp_unslash($item['quantity']));
        $variation_id = isset($item['variation_id']) ? absint($item['variation_id']) : 0;
        $variation = nmgr_get_posted_variations($variation_id, $item);
        $wishlist_id = (int) $item['nmgr-add-to-cart-wishlist'];
        $wishlist_item_id = (int) $item['nmgr-add-to-cart-wishlist-item'];
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);

        if ($passed_validation && false !== WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation)) {
            // wc filter (check this filter on updates)
            do_action('woocommerce_ajax_added_to_cart', $product_id);
            wc_add_to_cart_message(array($product_id => $quantity), true);
        }

        $items_data[] = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'wishlist_id' => $wishlist_id,
            'wishlist_item_id' => $wishlist_item_id,
        );

        $success = wc_notice_count('success') ? true : false;
        $redirect_url = false;
        if ($success && 'yes' === get_option('woocommerce_cart_redirect_after_add')) {
            // wc filter (check this filter on updates)
            $redirect_url = apply_filters('woocommerce_add_to_cart_redirect', wc_get_cart_url(), null);
        }

        // We are expecting notices from the add to cart action, so get them
        wp_send_json(array(
            'success' => $success,
            'notice' => $success && $redirect_url ? '' : wc_print_notices(true),
            'items_data' => $items_data,
            'redirect_url' => esc_url(apply_filters('nmgr_ajax_add_to_cart_redirect_url', $redirect_url, $success, $items_data)),
        ));
    }

    public static function get_cart_fragments()
    {
        if (class_exists('wc_ajax') && method_exists('wc_ajax', 'get_refreshed_fragments')) {
            WC_AJAX::get_refreshed_fragments();
        }
    }

    public static function send_ajax_response($data = array())
    {
        if (!wp_doing_ajax()) {
            return;
        }

        $data = (array) $data;

        $error = isset($data['error']) ? $data['error'] : (wc_notice_count('error') ? true : false);
        $success = isset($data['success']) ? $data['success'] : (wc_notice_count('success') ? true : false);

        unset($data['error']);
        unset($data['success']);

        // This clears the notices after printing so we get it last
        $data['notice'] = isset($data['notice']) ? $data['notice'] : wc_print_notices(true);

        wp_send_json(array(
            'error' => $error,
            'success' => $success,
            'data' => $data
        ));
    }

    /**
     * Save a wishlist profile
     *
     * This function is used for both admin and frontend (ajax requests)
     */
    public static function save_profile()
    {
        global $nmgr;

        // check_ajax_referer('nmgr_manage_wishlist');

        $response_data = array();

        try {
            $posted_form_data = filter_input(INPUT_POST, 'data');
            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            $form_data = array();

            if ($posted_form_data) {
                parse_str($posted_form_data, $form_data);
            }

            if (is_null($wishlist_id) || (0 !== $wishlist_id && !nmgr_user_can_manage_wishlist($wishlist_id))) {
                throw new Exception(sprintf(
                    /* translators: %s: wishlist type title */
                    __('We could not save your %s details, please try again.', 'nm-gift-registry-lite'),
                    nmgr_get_type_title()
                ));
            }

            // Let's flag if this is a new wishlist.
            if (0 === $wishlist_id) {
                $response_data['created'] = true;
            }

            $form = new NMGR_Form($wishlist_id);
            $form->sanitize($form_data)->validate();

            if ($form->has_errors()) {
                foreach ($form->get_error_messages() as $message) {
                    wc_add_notice($message, 'error');
                }
            } else {
                $id = $form->save();

                if (!$id) {
                    throw new Exception(sprintf(
                        /* translators: %s: wishlist type title */
                        __('Sorry the %s details could not be saved.', 'nm-gift-registry-lite'),
                        nmgr_get_type_title()
                    ));
                }

                wc_add_notice(
                    sprintf(
                        /* translators: %s: wishlist type title */
                        __('Your %s details have been saved', 'nm-gift-registry-lite'),
                        nmgr_get_type_title()
                    ),
                    'success'
                );

                $wishlist = nmgr_get_wishlist($id);
                $response_data['wishlist'] = $wishlist->get_data();
                $response_data['html'] = nmgr_get_profile_template($id);
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
        }

        self::send_ajax_response($response_data);
    }

    public static function add_item()
    {
        try {
            if (!isset($_POST['wishlist_id'])) {
                /* translators: %s: wishlist type title */
                throw new Exception(sprintf(__('Invalid %s', 'nm-gift-registry-lite'), nmgr_get_type_title()));
            }

            $wishlist_id = absint(wp_unslash($_POST['wishlist_id']));
            $wishlist = nmgr_get_wishlist($wishlist_id, true);

            if (!$wishlist) {
                /* translators: %s: wishlist type title */
                throw new Exception(sprintf(__('Invalid %s', 'nm-gift-registry-lite'), nmgr_get_type_title()));
            }

            // If we passed through items it means we need to save first before adding a new one.
            if (!empty($_POST['items'])) {
                $save_items = array();
                parse_str(wp_unslash($_POST['items']), $save_items);
                $wishlist->update_items(wc_clean($save_items));
            }

            $items_to_add = isset($_POST['data']) ? array_filter(wc_clean(wp_unslash((array) $_POST['data']))) : array();

            // Add items to wishlist.
            foreach ($items_to_add as $item) {
                if (!isset($item['id'], $item['qty']) || empty($item['id'])) {
                    continue;
                }
                $product_id = absint($item['id']);
                $qty = wc_stock_amount($item['qty']);
                $product = wc_get_product($product_id);
                $favourite = isset($item['fav']) ? absint($item['fav']) : 0;

                if (!$product) {
                    throw new Exception(__('Invalid product ID', 'nm-gift-registry-lite') . ' ' . $product_id);
                }

                $wishlist->add_item($product, $qty, $favourite);
            }

            $items_html = nmgr_get_items_template($wishlist);
            wp_send_json_success(array('html' => $items_html));
        } catch (Exception $e) {
            wp_send_json_error(array('error' => $e->getMessage()));
        }
    }

    public static function delete_items()
    {
        // check_ajax_referer('nmgr');

        try {
            $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
            $error_msg = __('The item(s) could not be deleted', 'nm-gift-registry-lite');

            if (!$wishlist_id || !nmgr_user_can_manage_wishlist($wishlist_id) || !isset($_POST['wishlist_item_ids'])) {
                throw new Exception($error_msg);
            }

            $wishlist = nmgr_get_wishlist($wishlist_id);

            if (!$wishlist) {
                throw new Exception($error_msg);
            }

            // If we passed through items it means we need to save first before deleting.
            if (!empty($_POST['items'])) {
                $save_items = array();
                parse_str(wp_unslash($_POST['items']), $save_items);
                $wishlist->update_items(wc_clean($save_items));
            }

            $items_data = array();
            $wishlist_item_ids = array_map('sanitize_text_field', wp_unslash((array) $_POST['wishlist_item_ids']));

            if (empty($wishlist_item_ids)) {
                throw new Exception($error_msg);
            }

            foreach ($wishlist_item_ids as $item_id) {
                $item = $wishlist->get_item($item_id);

                if (!$item) {
                    continue;
                }

                $product = wc_get_product($item->get_product_id());

                $item_data = array(
                    'wishlist_item_id' => $item->get_id(),
                    'wishlist_id' => $wishlist->get_id(),
                    'product_id' => $product ? $item->get_product_id() : '',
                    'variation_id' => $product ? $item->get_variation_id() : '',
                );

                $wishlist->delete_item($item_id);

                $item_data['product_in_wishlist'] = $product ? (int) nmgr_user_has_product_in_wishlist($product) : '';

                $items_data[] = $item_data;
            }

            wp_send_json(array(
                'success' => true,
                'template' => nmgr_get_items_template($wishlist_id),
                'wishlist' => $wishlist->get_data(),
                'items_data' => $items_data
            ));
        } catch (Exception $e) {
            wp_send_json(array(
                'error' => true,
                'notice' => $e->getMessage()
            ));
        }
    }

    public static function save_items()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);

        if ($wishlist_id && nmgr_user_can_manage_wishlist($wishlist_id) && isset($_POST['items'])) {
            $wishlist = nmgr_get_wishlist($wishlist_id);

            if ($wishlist) {
                // Parse the jQuery serialized items.
                $items = array();
                parse_str(wp_unslash($_POST['items']), $items); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                // Update wishlist items
                $wishlist->update_items(wc_clean($items));

                $response_data = array(
                    'wishlist' => $wishlist->get_data(),
                    'items' => $items,
                    'html' => nmgr_get_items_template($wishlist_id),
                    'success' => true,
                );

                self::send_ajax_response($response_data);
            }
        }
        wp_die();
    }

    /**
     * Search for products and echo json.
     * @since 1.0.5
     */
    public static function json_search_products()
    {
        // check_ajax_referer('nmgr-search-products', 'security');

        $term = '';
        $include_variations = true;

        if (empty($term) && isset($_GET['term'])) {
            $term = (string) wc_clean(wp_unslash($_GET['term'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        if (empty($term)) {
            wp_die();
        }

        if (!empty($_GET['limit'])) {
            $limit = absint($_GET['limit']);
        } else {
            $limit = absint(apply_filters('nmgr_json_search_limit', 30));
        }

        $include_ids = !empty($_GET['include']) ? array_map('absint', (array) wp_unslash($_GET['include'])) : array();
        $exclude_ids = !empty($_GET['exclude']) ? array_map('absint', (array) wp_unslash($_GET['exclude'])) : array();

        $exclude_types = array();
        if (!empty($_GET['exclude_type'])) {
            // Support both comma-delimited and array format inputs.
            $exclude_types = wp_unslash($_GET['exclude_type']);
            if (!is_array($exclude_types)) {
                $exclude_types = explode(',', $exclude_types);
            }

            // Sanitize the excluded types against valid product types.
            foreach ($exclude_types as &$exclude_type) {
                $exclude_type = strtolower(trim($exclude_type));
            }
            $exclude_types = array_intersect(
                array_merge(array('variation'), array_keys(wc_get_product_types())),
                $exclude_types
            );
        }

        $data_store = WC_Data_Store::load('product');
        $ids = $data_store->search_products($term, '', (bool) $include_variations, false, $limit, $include_ids, $exclude_ids);

        $product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
        $products = array();

        foreach ($product_objects as $product_object) {
            if (in_array($product_object->get_type(), $exclude_types, true)) {
                continue;
            }

            $formatted_name = $product_object->get_formatted_name();
            $managing_stock = $product_object->managing_stock();

            if ($managing_stock && !empty($_GET['display_stock'])) {
                $stock_amount = $product_object->get_stock_quantity();
                $formatted_name .= ' &ndash; ' . sprintf(
                    /* translators: %d: stock quantity */
                    __('Stock: %d', 'nm-gift-registry-lite'),
                    wc_format_stock_quantity_for_display($stock_amount, $product_object)
                );
            }

            $products[$product_object->get_id()] = rawurldecode($formatted_name);
        }

        wp_send_json($products);
    }

    public static function load_overview()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        nmgr_get_overview_template($wishlist_id, true);
        wp_die();
    }

    public static function load_profile()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        nmgr_get_profile_template($wishlist_id, true);
        wp_die();
    }

    public static function load_items()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        nmgr_get_items_template($wishlist_id, true);
        wp_die();
    }

    public static function load_shipping()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        nmgr_get_shipping_template($wishlist_id, true);
        wp_die();
    }

    /**
     * Save a wishlist's shipping address
     */
    public static function save_shipping()
    {
        // check_ajax_referer('nmgr_manage_wishlist');
        $wishlist_id = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        $posted_form_data = filter_input(INPUT_POST, 'data');
        $response_data = array();
        $form_data = array();

        if ($posted_form_data) {
            parse_str($posted_form_data, $form_data);
        }

        if (!$wishlist_id || !nmgr_user_can_manage_wishlist($wishlist_id)) {
            wc_add_notice(sprintf(
                /* translators: %s: wishlist type title */
                __('We could not save your %s shipping details, please try again.', 'nm-gift-registry-lite'),
                nmgr_get_type_title()
            ), 'error');
            self::send_ajax_response();
        }

        $form = new NMGR_Form($wishlist_id);
        $wishlist = $form->get_wishlist();

        // Always save the ship_to_account_address field
        $use_account_shipping = isset($form_data['nmgr_ship_to_account_address']) ? $form_data['nmgr_ship_to_account_address'] : null;
        $wishlist->set_ship_to_account_address(sanitize_title($use_account_shipping));
        $wishlist->save();

        if ($use_account_shipping) {
            // Reset wishlist shipping values if users wants to ship to account address
            $default_shipping = $wishlist->get_default_data()['shipping'];

            $shipping = array();
            foreach ($default_shipping as $key => $value) {
                $shipping["shipping_$key"] = $value;
            }

            $wishlist->set_props($shipping);
            $wishlist->save();
            wc_add_notice(__('Your shipping details have been saved', 'nm-gift-registry-lite'), 'success');
        } else {
            $form->sanitize($form_data)->validate();

            if ($form->has_errors()) {
                foreach ($form->get_error_messages() as $message) {
                    wc_add_notice($message, 'error');
                }
            } else {
                $form->save();
                wc_add_notice(__('Your shipping details have been saved.', 'nm-gift-registry-lite'), 'success');
            }
        }

        $wishlist_data = array_merge($wishlist->get_data(), array('has_shipping_address' => $wishlist->has_shipping_address()));
        $response_data['wishlist'] = $wishlist_data;
        $response_data['html'] = nmgr_get_shipping_template($wishlist_id);

        self::send_ajax_response($response_data);
    }

    /**
     * Search for users
     */
    public static function json_search_users()
    {
        ob_start();

        // check_ajax_referer('nmgr-search-users', 'security');

        if (!current_user_can('edit_' . nmgr()->post_type_plural)) {
            wp_die(-1);
        }

        $term = isset($_GET['term']) ? (string) wc_clean(wp_unslash($_GET['term'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $limit = 0;

        if (empty($term)) {
            wp_die();
        }

        $ids = array();
        // Search by ID.
        if (is_numeric($term)) {
            $customer = new WC_Customer((int) $term);

            // Customer exists.
            if (0 !== $customer->get_id()) {
                $ids = array($customer->get_id());
            }
        }

        /**
         * If the customer doesn't exist by searching by id, search for numeric username,
         * this prevents performance issues with ID lookups.
         */
        if (empty($ids)) {
            $data_store = WC_Data_Store::load('customer');

            /**
             * If search is smaller than 3 characters, limit result set to avoid
             * too many rows being returned.
             */
            if (3 > strlen($term)) {
                $limit = 20;
            }
            $ids = $data_store->search_customers($term, $limit);
        }

        $found_customers = array();

        if (!empty($_GET['exclude'])) {
            $ids = array_diff($ids, array_map('absint', (array) wp_unslash($_GET['exclude'])));
        }

        foreach ($ids as $id) {
            $customer = new WC_Customer($id);
            $found_customers[$id] = sprintf(
                /* translators: $1: customer name, $2: customer email */
                esc_html__('%1$s (%2$s)', 'nm-gift-registry-lite'),
                $customer->get_first_name() . ' ' . $customer->get_last_name(),
                $customer->get_email()
            );
        }

        wp_send_json($found_customers);
    }

    public static function load_wishlist_cart($args = array())
    {
        // check_ajax_referer('nmgr-frontend');

        $data = isset($_POST['data']) ? $_POST['data'] : $args;

        if ('dialog' === filter_input(INPUT_POST, 'context')) {
            $data['show_cart_contents_only'] = true;
            $args = array(
                'title' => isset($data['title']) ? $data['title'] : '',
                'content' => nmgr_get_cart_template($data),
            );
            $template = nmgr_get_dialog_template($args);
        } else {
            $template = nmgr_get_cart_template($data);
        }

        self::send_ajax_response(array('template' => $template));
    }

    public static function dialog_create_new_wishlist()
    {
        // check_ajax_referer('nmgr-frontend');

        $context = filter_input(INPUT_POST, 'context');

        $customer = new WC_Customer(get_current_user_id());
        $submit_btn_args = array('attributes' => array());

        if ('add_to_wishlist' === $context) {
            $submit_btn_args['attributes']['data-action'] = 'add_to_wishlist';
            $submit_btn_args['attributes']['disabled'] = true;
        }

        if (
            nmgr_get_option('shipping_address_required') &&
            (is_nmgr_guest() || ($customer->get_id() && !$customer->get_shipping_address()))
        ) {
            $submit_btn_args['attributes']['data-message'] = sprintf(__('The shipping address for this %s is required before you can add items to it.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
        }

        $args = array(
            'title' => sprintf(
                /* translators: %s: wishlist type title */
                __('Create new %s', 'nm-gift-registry-lite'),
                nmgr_get_type_title()
            ),
            'content' => nmgr_get_account_template('new'),
            'footer' => nmgr_get_dialog_submit_button($submit_btn_args)
        );

        self::send_ajax_response(array('template' => nmgr_get_dialog_template($args)));
    }

    public static function dialog_set_shipping_address()
    {
        // check_ajax_referer('nmgr-frontend');

        $wishlist_id = filter_input(INPUT_POST, 'nmgr_wid', FILTER_VALIDATE_INT);
        $context = filter_input(INPUT_POST, 'context');
        $submit_btn_args = array('attributes' => array());

        if ('add_to_wishlist' === $context) {
            $submit_btn_args['attributes']['disabled'] = true;
            $submit_btn_args['attributes']['data-action'] = 'add_to_wishlist';
            $submit_btn_args['attributes']['data-message'] = sprintf(__('The shipping address for this %s is required before you can add items to it.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
        }

        $args = array(
            'title' => __('Set shipping address', 'nm-gift-registry-lite'),
            'content' => nmgr_get_shipping_template($wishlist_id),
            'footer' => nmgr_get_dialog_submit_button($submit_btn_args)
        );

        self::send_ajax_response(array('template' => nmgr_get_dialog_template($args)));
    }

    // Automatically create a wishlist for a user without any wishlist when adding to a wishlist
    public static function auto_create_wishlist()
    {
        // check_ajax_referer('nmgr-frontend');

        $wishlist_id = wp_insert_post(
            array(
                'post_title' => 'Auto Draft',
                'post_type' => 'nm_gift_registry',
                'post_status' => 'auto-draft',
            )
        );

        $title = str_replace(
            array('{wishlist_type_title}', '{site_title}', '{wishlist_id}'),
            array(nmgr_get_type_title('c'), get_bloginfo('name'), $wishlist_id),
            nmgr_get_option('default_wishlist_title')
        );

        $wishlist = nmgr_get_wishlist($wishlist_id);
        $wishlist->set_title($title);
        $wishlist->set_status('publish');
        $wishlist->set_user_id(esc_html(nmgr_get_current_user_id()));

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $wishlist->set_email($user->user_email);
            $wishlist->set_first_name($user->first_name);
            $wishlist->set_last_name($user->last_name);
        }

        $id = $wishlist->save();

        if (!$id) {
            self::send_ajax_response(array('error' => true));
        }

        self::send_ajax_response(array('wishlist' => $wishlist->get_data()));
    }

    public static function dialog_add_to_wishlist()
    {
        // check_ajax_referer('nmgr-frontend');
        $template = self::get_add_to_wishlist_dialog();
        self::send_ajax_response(array('template' => $template));
    }

    public static function get_add_to_wishlist_dialog()
    {
        $product_id = filter_input(INPUT_POST, 'nmgr_pid');
        $wishlist_id = filter_input(INPUT_POST, 'nmgr_wid');
        $variation_id = filter_input(INPUT_POST, 'nmgr_vid');
        $product = wc_get_product($variation_id ? $variation_id : $product_id);

        if ($product) {
            $submit_btn_args = array(
                'text' => nmgr_get_option('add_to_wishlist_button_text'),
                'attributes' => array(
                    'data-action' => 'add_to_wishlist'
                ),
            );

            $args = array(
                'product' => $product,
                'wishlists' => nmgr_get_user_wishlists(),
                'selected_wishlist_id' => $wishlist_id,
                'formdata' => array(
                    'nmgr_pid' => $product_id,
                    'nmgr_vid' => $variation_id,
                    'nmgr_wc_form_values' => filter_input(INPUT_POST, 'nmgr_wc_form_values')
                ),
            );

            if ($product->is_type('grouped')) {
                $args['grouped_products'] = array_filter(
                    array_map('wc_get_product', $product->get_children()),
                    'wc_products_array_filter_visible_grouped'
                );
            }

            $template = nmgr_get_template(
                'add-to-wishlist/dialog-content.php',
                apply_filters('nmgr_add_to_wishlist_dialog_content_args', $args)
            );

            $dialog_args = array(
                'show_header' => false,
                'show_body_close_button' => true,
                'content' => $template,
                'footer' => nmgr_get_dialog_submit_button($submit_btn_args)
            );
            return nmgr_get_dialog_template($dialog_args);
        }
    }
}
