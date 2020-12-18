<?php

/*
 * Handles cart, checkout and order actions related to NM Gift Registry
 */

defined('ABSPATH') || exit;

class NMGR_Order
{
    public static function run()
    {
        add_filter('woocommerce_continue_shopping_redirect', array( __CLASS__, 'continue_shopping_redirect' ));
        add_filter('woocommerce_add_cart_item_data', array( __CLASS__, 'add_wishlist_item_cart_item_data' ), 10, 3);
        add_filter('woocommerce_add_to_cart_validation', array( __CLASS__, 'maybe_add_wishlist_item_to_cart' ), 10, 3);
        add_filter('woocommerce_update_cart_validation', array( __CLASS__, 'maybe_update_cart_item_quantity' ), 10, 4);
        add_action('woocommerce_before_cart', array( __CLASS__, 'notify_if_cart_has_wishlist' ));
        add_filter('woocommerce_get_item_data', array( __CLASS__, 'show_wishlist_item_cart_item_data' ), 50, 2);
        add_action('woocommerce_before_checkout_shipping_form', array( __CLASS__, 'notify_wishlist_shipping' ));
        add_action('woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_wishlist_data_as_order_item_meta_data' ), 10, 4);
        add_action('woocommerce_checkout_update_order_meta', array( __CLASS__, 'add_wishlist_data_as_order_meta_data' ));
        add_action('woocommerce_order_item_meta_start', array( __CLASS__, 'display_wishlist_data_in_order_itemmeta_table' ), 10, 2);
        add_action('woocommerce_before_order_itemmeta', array( __CLASS__, 'display_wishlist_data_in_order_itemmeta_table' ), 10, 2);
        add_action('woocommerce_payment_complete', array( __CLASS__, 'process_wishlist_item_payment' ), 10);
        add_action('woocommerce_order_status_changed', array( __CLASS__, 'process_wishlist_item_payment' ), 10);
        add_action('woocommerce_order_refunded', array( __CLASS__, 'process_wishlist_item_payment' ), 10);
        add_action('woocommerce_refund_deleted', array( __CLASS__, 'process_wishlist_item_payment_for_deleted_refunds' ), 10, 2);
        add_action('nmgr_order_payment_complete', array( __CLASS__, 'update_wishlist_item_purchased_quantity' ), 10, 3);
        add_action('nmgr_order_payment_complete', array( __CLASS__, 'maybe_set_wishlist_as_fulfilled' ), 99, 2);
    }

    /**
     * If the product was added to the cart from the wishlist page, return to the wishlist page
     */
    public static function continue_shopping_redirect($url)
    {
        $item_data = self::get_add_to_cart_item_data();

        if (empty($item_data)) {
            return $url;
        }

        $wishlist = nmgr_get_wishlist($item_data[ 'wishlist_id' ]);
        return $wishlist ? $wishlist->get_permalink() : $url;
    }

    /**
     * Get the data of the wishlist items we are adding to the cart from the request array
     *
     * This is necessary as the wishlist items can be added to the cart individually or in bulk.
     * In each of these cases, the request array is different. But we need to make it constistent
     * in order to apply the same functions to them. Hence this function.
     * @return array
     */
    public static function get_add_to_cart_item_data($product_id = 0)
    {
        $item_data = array();

        if (isset($_REQUEST[ 'nmgr-add-to-cart-wishlist' ])) {
            $item_data = $_REQUEST;
        } elseif (isset($_REQUEST[ 'nmgr_items' ])) {
            foreach ($_REQUEST[ 'nmgr_items' ] as $item) {
                if ($product_id && $product_id == $item[ 'add-to-cart' ]) {
                    $item_data = $item;
                    break;
                }
            }

            if (empty($item_data)) {
                $item_data = reset($_REQUEST[ 'items' ]);
            }
        }
        return $item_data;
    }

    /**
     * Add information about the wishlist to the cart item when it is added to the cart
     *
     * Wishlist information added:
     * 'wishlist_id' - The post id of the wishlist the item belongs to. This corresponds to the
     * 'wishlist_id' in the nmgr_wishlist_items table
     * 'wishlist_item_id - The id of the item in the wishlist. This corresponds to the 'wishlist_item_id'
     * in the nmgr_wishlist_items table
     *
     * @param array $cart_item_data Extra data associated with the cart item
     * @return array $cart_item_data
     */
    public static function add_wishlist_item_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        $item_data = self::get_add_to_cart_item_data($product_id);

        if (empty($item_data)) {
            return $cart_item_data;
        }

        $cart_item_data[ 'nm_gift_registry' ] = array(
            'wishlist_id' => ( int ) $item_data[ 'nmgr-add-to-cart-wishlist' ],
            'wishlist_item_id' => ( int ) $item_data[ 'nmgr-add-to-cart-wishlist-item' ],
            'product_id' => $product_id,
            'variation_id' => $variation_id
        );

        return $cart_item_data;
    }

    /**
     * Add the wishlist item to the cart if the quantity being added is not more than the quantity requested in the wishlist
     * This function is used typically on the wishlist page where a wishlist item is being added to the cart
     *
     * @param bool $passed Whether the validation passes or fails
     * @param int $product_id Item product id
     * @param int $quantity Item quantity being added to cart
     * @return boolean Validation passed or failed
     */
    public static function maybe_add_wishlist_item_to_cart($passed, $product_id, $quantity)
    {
        $item_data = self::get_add_to_cart_item_data($product_id);

        if (empty($item_data)) {
            return $passed;
        }

        $wishlist_id = ( int ) $item_data[ 'nmgr-add-to-cart-wishlist' ];
        $wishlist_item_id = ( int ) $item_data[ 'nmgr-add-to-cart-wishlist-item' ];

        // check if the wishlist item is already in the cart
        $item_in_cart = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item[ nmgr()->cart_key ])) {
                if ($cart_item[ nmgr()->cart_key ][ 'wishlist_id' ] === $wishlist_id && $cart_item[ nmgr()->cart_key ][ 'wishlist_item_id' ] === $wishlist_item_id) {
                    $item_in_cart = $cart_item;
                    break;
                }
            }
        }

        return self::validate_wishlist_item_cart_quantity($passed, $wishlist_id, $wishlist_item_id, $quantity, $item_in_cart);
    }

    /**
     * Update the wishlist item in the cart if the quantity being added doesn't exceed the quantity requested in the wishlist
     * This function is used mainly on the cart page where only the item quantity is updated
     *
     * @param bool $passed Whether the validation passes or fails
     * @param string $cart_item_key Unique id of the cart item
     * @param array $values Cart item properties
     * @param int $quantity Item quantity being added to cart
     * @return boolean Validation passed or failed
     */
    public static function maybe_update_cart_item_quantity($passed, $cart_item_key, $values, $quantity)
    {
        $passed = self::validate_product_is_wishlist_item_cart_quantity($passed, $values, $quantity);

        if ($passed && isset($values[ nmgr()->cart_key ])) {
            $passed = self::validate_wishlist_item_cart_quantity($passed, $values[ nmgr()->cart_key ][ 'wishlist_id' ], $values[ nmgr()->cart_key ][ 'wishlist_item_id' ], $quantity);
        }

        return $passed;
    }

    /**
     * Validate the quantity of a wishlist item added to the cart based on its existing cart quantity if present and desired quantity
     *
     * @param bool $passed Whether the validation passes or fails
     * @param int $wishlist_id The wishlist id
     * @param int $wishlist_item_id The item id in the wishlist
     * @param int $quantity The quantity of the item being added
     * @param array $item_in_cart The item contents if the item is already in the cart
     * @return boolean Validation passed or failed
     */
    private static function validate_wishlist_item_cart_quantity($passed, $wishlist_id, $wishlist_item_id, $quantity, $item_in_cart = false)
    {
        $wishlist = nmgr_get_wishlist($wishlist_id, true);
        if (!$wishlist || !nmgr_get_option('display_item_quantity', 1)) {
            return $passed;
        }

        $item = $wishlist->get_item($wishlist_item_id);
        if ($item) {
            /* translators: %s: item name */
            $item_title = sprintf(_x('&ldquo;%s&rdquo;', 'Item name in quotes', 'nm-gift-registry-lite'), $item->get_name());

            $desired_item_qty = nmgr_get_option('display_item_purchased_quantity', 1) ?
                $item->get_unpurchased_quantity() : $item->get_quantity();

            if ($item_in_cart) {
                $item_cart_quantity = $item_in_cart[ 'quantity' ]; // the current quantity of the item in the cart

                if (($quantity + $item_cart_quantity) > $desired_item_qty) {
                    $passed = false;
                    $message = apply_filters('nmgr_validate_wishlist_item_in_cart_quantity_message', sprintf(
                            /* translators:
                             * 1: item quantity to add to cart,
                             * 2: item title,
                             * 3: item quantity in cart,
                             * 4: item quantity requested in wishlist,
                             * 5: wishlist type title
                             */
                            __('You cannot add %1$d of %2$s to the cart as you have %3$d already in the cart with %4$d requested in the %5$s.', 'nm-gift-registry-lite'),
                        $quantity,
                        $item_title,
                        $item_cart_quantity,
                        $desired_item_qty,
                        nmgr_get_type_title()
                    ), $quantity, $item, $wishlist, $item_in_cart);
                    wc_add_notice($message, 'error');
                }
            } else {
                if ($quantity > $desired_item_qty) {
                    $passed = false;
                    $message = apply_filters('nmgr_validate_wishlist_item_cart_quantity_message', sprintf(
                            /* translators: 1: item title, 2: wishlist type title */
                            __('Please choose a quantity of %1$s that is not greater than the quantity requested in the %2$s.', 'nm-gift-registry-lite'),
                        $item_title,
                        nmgr_get_type_title()
                    ), $quantity, $item, $wishlist);
                    wc_add_notice($message, 'error');
                }
            }
        }
        return $passed;
    }

    /**
     * Validate the quantity of items added to the cart if these items have already been
     * added to the cart as wishlist items
     *
     * @param bool $passed Whether the validation passes or fails
     * @param int $values Cart item data
     * @param int $quantity The quantity of the item being added
     * @return boolean Validation passed or failed
     */
    public static function validate_product_is_wishlist_item_cart_quantity($passed, $values, $quantity)
    {
        $product = $values[ 'data' ];

        if (!$product->managing_stock() || $product->backorders_allowed()) {
            return $passed;
        }

        // check if product is also added as a wishlist item
        $product_is_wishlist_item_in_cart = false;

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item[ nmgr()->cart_key ])) {
                $variation_id = $cart_item[ nmgr()->cart_key ][ 'variation_id' ];
                if (($variation_id && $variation_id == $values[ 'variation_id' ]) || $cart_item[ nmgr()->cart_key ][ 'product_id' ] == $values[ 'product_id' ]) {
                    $product_is_wishlist_item_in_cart = true;
                    break;
                }
            }
        }

        if (!$product_is_wishlist_item_in_cart) {
            return $passed;
        }

        // Check that the overall cart stock for the product is not greater than the product stock
        $products_cart_quantities = wc()->cart->get_cart_item_quantities();
        $product_cart_quantity = $products_cart_quantities[ $product->get_stock_managed_by_id() ];

        if ($product->get_stock_quantity() < ($product_cart_quantity + $quantity)) {
            /* translators: %1$s: product stock quantity, %2$s: product quantity in cart */
            $message = sprintf(
                __('You cannot add that amount to the cart &mdash; we have %1$s in stock and you already have %2$s in your cart.', 'nm-gift-registry-lite'),
                $product->get_stock_quantity(),
                $product_cart_quantity
            );
            wc_add_notice($message, 'error');
            return false;
        }
        return $passed;
    }

    /**
     * Add a simple notice on the cart page to notify that the cart contains at least one wishlist
     */
    public static function notify_if_cart_has_wishlist()
    {
        if (!nmgr_get_option('show_cart_item', 1)) {
            return;
        }

        if (nmgr_get_wishlist_in_cart()) {
            $item_message = sprintf(
                /* translators: %s: wishlist type title */
                __('There are some items in your cart for a specific %s.', 'nm-gift-registry-lite'),
                nmgr_get_type_title()
            );

            $notice = apply_filters('nmgr_cart_has_wishlist_notice', $item_message);

            if ($notice) {
                wc_print_notice($notice, 'notice');
            }
        }
    }

    /**
     * Display information about the associated wishlist for an item in the cart
     *
     * @param array $item_data key value pair of Information to display
     * @param array $cart_item_data Cart item data
     * @return array $item_data
     */
    public static function show_wishlist_item_cart_item_data($item_data, $cart_item_data)
    {
        if (!nmgr_get_option('show_cart_item', 1) ||
            !isset($cart_item_data[ nmgr()->cart_key ]) ||
            empty($cart_item_data[ nmgr()->cart_key ]) ||
            empty(array_filter($cart_item_data[ nmgr()->cart_key ]))) {
            return $item_data;
        }

        $wishlist = nmgr_get_wishlist($cart_item_data[ nmgr()->cart_key ][ 'wishlist_id' ], true);

        if (!$wishlist) {
            return $item_data;
        }

        $item = $wishlist->get_item($cart_item_data[ nmgr()->cart_key ][ 'wishlist_item_id' ]);

        if (!$item) {
            return $item_data;
        }

        /* translators: %s: wishlist type title */
        $title = sprintf(__('You are buying this item for this %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
        $data = array(
            /* translators: %s: wishlist type title */
            'key' => sprintf(__('For %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title())),
            'value' => nmgr_get_wishlist_link($wishlist, array( 'title' => $title )),
            'display' => '',
        );
        $item_data[] = apply_filters('nmgr_get_item_data_content', $data, $item, $wishlist);

        return $item_data;
    }

    /**
     * Displays simple notice on checkout page shipping section concerning the wishlist's shipping method
     */
    public static function notify_wishlist_shipping()
    {
        if (nmgr_get_wishlist_in_cart()) {
            wc_print_notice(
                apply_filters(
                    'nmgr_checkout_shipping_message',
                    /* translators: %1$s: wishlist type title, %2$s: wishlist type title */
                    sprintf(
                        __('Items in your order which are for a %1$s would be shipped separately to the owner of the %2$s.', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title()),
                        esc_html(nmgr_get_type_title())
                    )
                ),
                'notice'
            );
        }
    }

    /**
     * Add a wishlist's data as order itemmeta
     */
    public static function add_wishlist_data_as_order_item_meta_data($item, $cart_item_key, $values, $order)
    {
        if (isset($values[ nmgr()->cart_key ]) && !empty(array_filter($values[ nmgr()->cart_key ]))) {
            $cart_value = $values[ nmgr()->cart_key ];
            $wishlist = nmgr_get_wishlist($cart_value[ 'wishlist_id' ], true);
            if (!$wishlist) {
                return;
            }
            $item->add_meta_data(nmgr()->cart_key, $cart_value);
        }
    }

    /**
     * Add data concerning the wishlists in the order as order meta data
     * after the order has been saved at checkout
     *
     * This information would serve as the main data store from which we can
     * perform gift registry related wishlist actions on the order later
     *
     * @param int $order_id The order id
     */
    public static function add_wishlist_data_as_order_meta_data($order_id)
    {
        $wishlist_items_data = array();
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $order_item) {
            $meta = $order_item->get_meta(nmgr()->cart_key);

            if ($meta) {
                $wishlist = nmgr_get_wishlist($meta[ 'wishlist_id' ], true);
                if (!$wishlist) {
                    continue;
                }

                $wishlist_item = $wishlist->get_item($meta[ 'wishlist_item_id' ]);
                if (!$wishlist_item) {
                    continue;
                }

                $arr = array(
                    'wishlist_item_id' => $meta[ 'wishlist_item_id' ], // wishlist_item_id
                    'order_item_quantity' => $order_item->get_quantity(), // quantity of item ordered
                    'order_item_id' => $order_item->get_id(), // order item id
                    'wishlist_id' => $meta[ 'wishlist_id' ], // id of wishlist the item belongs to
                );
                $wishlist_items_data[] = $arr;
            }
        }

        if (!empty($wishlist_items_data)) {
            $order_meta = array();
            foreach ($wishlist_items_data as $data) {
                $order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_id' ] = $data[ 'wishlist_id' ];
                $order_meta[ $data[ 'wishlist_id' ] ][ 'wishlist_item_ids' ][] = $data[ 'wishlist_item_id' ];
                $order_meta[ $data[ 'wishlist_id' ] ][ 'order_item_ids' ][ $data[ 'wishlist_item_id' ] ] = $data[ 'order_item_id' ];
                $order_meta[ $data[ 'wishlist_id' ] ][ 'sent_customer_purchased_items_email' ] = 'no';
            }
            $order->add_meta_data(nmgr()->cart_key, $order_meta);
            $order->save();
        }
    }

    /**
     * Displays wishlist data as order item meta
     * used in order details table at checkout and in the order screen in admin
     */
    public static function display_wishlist_data_in_order_itemmeta_table($item_id, $item)
    {
        if (!is_admin() && !nmgr_get_option('show_order_item', 1)) {
            return;
        }

        $meta = $item->get_meta(nmgr()->cart_key);
        if ($meta) {
            $wishlist = nmgr_get_wishlist($meta[ 'wishlist_id' ], true);

            if (!$wishlist) {
                return;
            }

            /* translators: %s: wishlist type title */
            $title = sprintf(__('This item is bought for this %s', 'nm-gift-registry-lite'), nmgr_get_type_title());
            $link = nmgr_get_wishlist_link($wishlist, array( 'title' => $title ));

            echo nmgr_kses_post('<div class="nmgr-order-item-wishlist">' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                /* translators: %s: wishlist type title */
                . sprintf(__('For %s: ', 'nm-gift-registry-lite'), nmgr_get_type_title())
                . $link
                . '</div>');
        }
    }

    /**
     * When a refund has been deleted, process payments for the wishlist items in the order
     */
    public static function process_wishlist_item_payment_for_deleted_refunds($refund_id, $order_id)
    {
        self::process_wishlist_item_payment($order_id);
    }

    /**
     * Process payments for the wishlist items in an order
     *
     * This is the main entry point for actions relating to nm gift registry for WooCommerce orders.
     *
     * This function determines if we have any wishlist items in the order, if so it gets their data in the order
     * and sets up some actions, based on whether the order is paid or refunded,
     * that we can use to simplify the subsequent processing of these items in the order
     *
     * @param int $order_id The order id
     */
    public static function process_wishlist_item_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $data = $order->get_meta(nmgr()->cart_key);
        $order_wishlist_data = $data ? $data : array();

        if (!$order_wishlist_data && false === apply_filters('nmgr_do_order_payment_actions', false, $order)) {
            return;
        }

        /**
         * At this point we confirm that there are wishlist items in the order
         * so we can set up our actions
         */
        $payment_cancelled_statuses = nmgr_get_payment_cancelled_order_statuses();

        if ($order->get_date_paid() &&
            ($order->is_paid() ||
            $order->has_status($payment_cancelled_statuses) ||
            doing_action('woocommerce_order_refunded') ||
            doing_action('woocommerce_refund_deleted'))
        ) {
            do_action('nmgr_order_payment_complete', $order_id, $order_wishlist_data, $order);
        } else {
            do_action('nmgr_order_payment_incomplete', $order_id, $order_wishlist_data, $order);
        }
    }

    /**
     * Update the purchased quantity of a wishlist item based on order status or action
     *
     * This function only runs if the 'purchased quantity' column is visible on the items table
     * and if the order is paid for or refunded, that is typically on these statuses:
     * - processing, completed, refunded, cancelled.
     *
     * Any other statuses of the order would not trigger an update of the wishlist item's purchased quantity.
     * This is because the purchased quantity of a wishlist item is meant to be updated, as in the real shopping sense,
     * only when an order has been paid for or refunded.
     */
    public static function update_wishlist_item_purchased_quantity($order_id, $order_wishlist_data, $order)
    {
        if (!nmgr_get_option('display_item_purchased_quantity', 1)) {
            return;
        }

        $order_item_ids = array();
        foreach ($order_wishlist_data as $wishlist_data) {
            $ids = isset($wishlist_data[ 'order_item_ids' ]) ? $wishlist_data[ 'order_item_ids' ] : array();
            $order_item_ids = $order_item_ids + $ids;
        }

        if (empty($order_item_ids)) {
            return;
        }

        $refunded_items = array();

        /**
         * At this point we have an array of wishlist item ids to their order_item_id counterparts
         * so we update the purchased quantity of each item individually
         */
        foreach ($order_item_ids as $wishlist_item_id => $order_item_id) {
            // Get the wishlist item object
            $wishlist_item_object = new NMGR_Wishlist_Item($wishlist_item_id);

            if (!$wishlist_item_object) {
                continue;
            }

            // Get the item's quantity reference from the database
            $qr_meta = $wishlist_item_object->get_quantity_reference();
            /**
             * array_filter was added in 2.1.0 to fix bug present from previous version when there is an empty array.
             * Should probably remove this function in a later version
             */
            $quantity_references = $qr_meta ? array_filter($qr_meta) : array();

            // If the quantity reference doesn't exist, flag this as a new order item
            $item_is_new = isset($quantity_references[ $order_id ]) ? false : true;

            // Get the order item object
            $order_item_object = $order->get_item($order_item_id);

            /**
             * If we don't have the order item object, we assume the item has been removed from the order
             * (This might be due to a refund or something).
             * So we simply delete the quantity reference of this item for the order if it exists,
             * update its purchased quantity, and return
             */
            if (!$order_item_object) {
                if (isset($quantity_references[ $order_id ])) {
                    unset($quantity_references[ $order_id ]);

                    $original_purchased_qty = $wishlist_item_object->get_purchased_quantity();

                    $wishlist_item_object->set_quantity_reference($quantity_references);
                    $new_purchased_qty = array_sum(wp_list_pluck($quantity_references, 'purchased_quantity'));
                    // Item purchased quantity should always be updated after changing the quantity reference
                    $wishlist_item_object->set_purchased_quantity($new_purchased_qty);
                    $wishlist_item_object->save();

                    /**
                     * The item was previously in the order because it has a quantity reference
                     * for the order id, but since it no longer has it, let's assume that the refunded
                     * quantity of the item has changed. (That is, all of the item is refunded).
                     * So we add the item to the refunded_items array.
                     */
                    if ($new_purchased_qty < $original_purchased_qty) {
                        $refunded_items[ $wishlist_item_object->get_id() ] = $original_purchased_qty - $new_purchased_qty;
                    }
                }
                continue;
            }

            // Set a default quantity reference for new items
            $default_qr = array(
                'ordered_quantity' => 0,
                'refunded_quantity' => 0,
                'purchased_quantity' => 0,
            );

            /**
             * If this item's quantity reference has not be set for this order, set the new one
             * else get the one set for this order
             */
            $qr = $item_is_new ? $default_qr : $quantity_references[ $order_id ];

            // Set a new quantity reference for the item for this order based on current item quantity properties
            $ordered_qty = absint($order_item_object->get_quantity());
            $refunded_qty = absint($order->get_qty_refunded_for_item($order_item_id));
            $new_qr = array(
                'ordered_quantity' => $ordered_qty,
                'refunded_quantity' => $refunded_qty,
                'purchased_quantity' => absint($ordered_qty - $refunded_qty),
            );

            // If the order payment is cancelled, update the quantity reference and purchased quantity for the wishlist item
            if ($order->has_status(nmgr_get_payment_cancelled_order_statuses())) {
                $new_qr[ 'purchased_quantity' ] = 0;
            }

            /**
             * If the item is not new and the new purchased quantity is less than the old purchased quantity,
             * we assume the item is refunded  (or the order payment is cancelled)
             * so we add it to the refunded_items array
             */
            if (!$item_is_new && ($new_qr[ 'purchased_quantity' ] < $qr[ 'purchased_quantity' ])) {
                $refunded_items[ $wishlist_item_object->get_id() ] = $qr[ 'purchased_quantity' ] - $new_qr[ 'purchased_quantity' ];
            }

            /**
             * if the stored quantity reference is not equal to the new quantity reference
             * the item purchased quantity might have changed, so update it
             */
            if ($qr !== $new_qr) {
                $quantity_references[ $order_id ] = $new_qr;

                $wishlist_item_object->set_quantity_reference($quantity_references);

                $new_purchased_qty = array_sum(wp_list_pluck($quantity_references, 'purchased_quantity'));

                // Item purchased quantity should always be updated after changing the quantity reference
                $wishlist_item_object->set_purchased_quantity($new_purchased_qty);

                $wishlist_item_object->save();
            }
        }

        /**
         * If we have items in the refunded_items array, set up the refund action.
         */
        if (!empty($refunded_items)) {
            do_action('nmgr_order_items_refunded', $refunded_items, $order_id, $order_wishlist_data, $order);
        }
    }

    /**
     * After order payment, check if we should set the wishlist as fulfilled
     *
     * @param WC_Order $order_id
     * @param array $order_wishlist_meta Wishlist data in order
     */
    public static function maybe_set_wishlist_as_fulfilled($order_id, $order_wishlist_meta)
    {
        // Loop through each wishlist in the order
        foreach (array_keys($order_wishlist_meta) as $wishlist_id) {
            $wishlist = nmgr_get_wishlist($wishlist_id, true);

            if (!$wishlist) {
                continue;
            }

            /**
             * If the wishlist is fulfilled we set a fulfilled date for the wishlist if it isn't already set
             */
            if ($wishlist->is_fulfilled()) {
                if (!$wishlist->get_date_fulfilled()) {
                    $wishlist->set_date_fulfilled(time());
                    $wishlist->save();
                    /**
                     * Functions hooked into this action should typically only be run once (except in the case of refunds),
                     * as it is set when the wishlist has been detected as fulfilled based on the current order items
                     */
                    do_action('nmgr_fulfilled_wishlist', $wishlist_id, $wishlist);
                }
            } else {
                // If the wishlist is not fulfilled (perhaps because of refunds) but it already has a fulfilled date set, remove the date
                if ($wishlist->get_date_fulfilled()) {
                    $wishlist->set_date_fulfilled(null);
                    $wishlist->save();
                }
            }
        }
    }
}