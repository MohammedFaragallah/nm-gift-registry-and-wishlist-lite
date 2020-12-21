<?php

defined('ABSPATH') || exit;

/**
 * Wishlist class
 */
class NMGR_Wishlist extends NMGR_Data
{

    /**
     * Wishlist data stored in wp posts table
     *
     * @var array
     */
    protected $core_data = array(
        'title' => '', // post title
        'status' => 'publish', // post status
        'description' => '', // post excerpt
        'slug' => '', // post-name
        'date_created' => '', // post_date
    );

    /**
     * Wishlist meta data stored in post meta table
     *
     * Internal meta keys for wishlist
     *
     * @var array
     */
    protected $meta_data = array(
        'first_name' => '',
        'last_name' => '',
        'partner_first_name' => '',
        'partner_last_name' => '',
        'email' => '',
        'event_date' => '',
        'ship_to_account_address' => 1,
        'shipping' => array(
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'postcode' => '',
            'country' => '',
            'state' => '',
        ),
        'date_fulfilled' => null,
        'nmgr_user_id' => 0,
        'nmgr_guest' => 0,
    );

    /**
     * Properties that have sub properties
     *
     * @var array
     */
    protected $parent_props = array(
        'shipping'
    );

    /**
     * Wishlist items
     *
     * Items are added here before they are saved to the database
     *
     * @var array
     */
    protected $items = array();

    /**
     * Name of the object type
     *
     * @var string
     */
    protected $object_type = 'wishlist';

    /**
     * Get the wishlist if ID is passed, otherwise the wishlist is new and empty.
     *
     * @param  int|object|NMGR_Wishlist $wishlist Wishlist to read.
     */
    public function __construct($wishlist = 0)
    {
        global $post;

        parent::__construct($wishlist);

        if (!$wishlist && is_a($post, 'WP_Post') && nmgr()->post_type === get_post_type($post)) {
            // if we are on the wishlist edit screen in admin, the wishlist id is the post id
            $this->set_id(absint($post->ID));
        } elseif (is_numeric($wishlist) && $wishlist > 0) {
            $this->set_id($wishlist);
        } elseif ($wishlist instanceof self) {
            $this->set_id(absint($wishlist->get_id()));
        } elseif (!empty($wishlist->ID)) {
            $this->set_id(absint($wishlist->ID));
        } else {
            $this->set_object_read(true);
        }

        $this->db = new NMGR_Database_Wishlist();

        if ($this->get_id() > 0) {
            $this->db->read($this);
        }
    }

    /*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

    /**
     * Get all data for this wishlist including wishlist items
     *
     * @param bool $items Whether to get the wishlist items with the data. Default false
     * @return array Wishlist Data
     */
    public function get_data($items = false)
    {
        $data = parent::get_data();

        if ($items) {
            $items_data = array_map(function ($obj) {
                $d = new NMGR_Wishlist_Item($obj);
                return $d->get_data();
            }, $this->get_items());

            $data = array_merge($data, array('items' => $items_data));
        }

        return apply_filters('nmgr_get_wishlist_data', $data, $this);
    }

    /**
     * Get the title of the wishlist
     *
     * @return string
     */
    public function get_title()
    {
        return $this->get_prop('title');
    }

    /**
     * Get the post status of the wishlist (e.g. publish, draft)
     *
     * @return string
     */
    public function get_status()
    {
        return $this->get_prop('status');
    }

    /**
     * Get the first name of the wishlist owner
     *
     * @return string
     */
    public function get_first_name()
    {
        return $this->get_prop('first_name');
    }

    /**
     * Get the last name of the wishlist owner
     *
     * @return string
     */
    public function get_last_name()
    {
        return $this->get_prop('last_name');
    }

    /**
     * Get the first name and last name of the wishlist owner
     *
     * @return string
     */
    public function get_full_name()
    {
        return trim(sprintf('%1$s %2$s', $this->get_first_name(), $this->get_last_name()));
    }

    /**
     * Get the first name of the wishlist owner's partner
     *
     * @return string
     */
    public function get_partner_first_name()
    {
        return $this->get_prop('partner_first_name');
    }

    /**
     * Get the last name of the wishlist owner's partner
     *
     * @return string
     */
    public function get_partner_last_name()
    {
        return $this->get_prop('partner_last_name');
    }

    /**
     * Get the first name and last name of the wishlist owner's partner
     *
     * @return string
     */
    public function get_partner_full_name()
    {
        return trim(sprintf('%1$s %2$s', $this->get_partner_first_name(), $this->get_partner_last_name()));
    }

    /**
     * Get the display name for the wishlist
     * This is the combination of the names of the wishlist owner and wishlist owner's partner if available
     *
     * @return string
     */
    public function get_display_name()
    {
        $display_name = '';
        if ($this->get_full_name() && $this->get_partner_full_name()) {
            $display_name = "{$this->get_full_name()} &amp; {$this->get_partner_full_name()}";
        } elseif ($this->get_full_name()) {
            $display_name = $this->get_full_name();
        }
        return $display_name;
    }

    /**
     * Get the registered email for the wishlist
     *
     * @return string
     */
    public function get_email()
    {
        return $this->get_prop('email');
    }

    /**
     * Get the date for the wishlist event
     *
     * @return string
     */
    public function get_event_date()
    {
        return $this->get_prop('event_date');
    }

    /**
     * Get the wishlist description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->get_prop('description');
    }

    /**
     * Get all shipping fields
     *
     * @return array
     */
    public function get_shipping()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping();
        }
        return $this->get_prop('shipping');
    }

    /**
     * Get shipping first name.
     *
     * @return string
     */
    public function get_shipping_first_name()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_first_name();
        }
        return $this->get_child_prop('first_name', 'shipping');
    }

    /**
     * Get shipping_last_name.
     *
     * @return string
     */
    public function get_shipping_last_name()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_last_name();
        }
        return $this->get_child_prop('last_name', 'shipping');
    }

    /**
     * Get shipping company.
     *
     * @return string
     */
    public function get_shipping_company()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_company();
        }
        return $this->get_child_prop('company', 'shipping');
    }

    /**
     * Get shipping address line 1
     *
     * @since 2.0.0
     * @return string
     */
    public function get_shipping_address()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_address();
        }
        return $this->get_child_prop('address_1', 'shipping');
    }

    /**
     * Get shipping address line 1.
     *
     * @return string
     */
    public function get_shipping_address_1()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_address_1();
        }
        return $this->get_child_prop('address_1', 'shipping');
    }

    /**
     * Get shipping address line 2.
     *
     * @return string
     */
    public function get_shipping_address_2()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_address_2();
        }
        return $this->get_child_prop('address_2', 'shipping');
    }

    /**
     * Get shipping city.
     *
     * @return string
     */
    public function get_shipping_city()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_city();
        }
        return $this->get_child_prop('city', 'shipping');
    }

    /**
     * Get shipping state.
     *
     * @return string
     */
    public function get_shipping_state()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_state();
        }
        return $this->get_child_prop('state', 'shipping');
    }

    /**
     * Get shipping postcode.
     *
     * @return string
     */
    public function get_shipping_postcode()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_postcode();
        }
        return $this->get_child_prop('postcode', 'shipping');
    }

    /**
     * Get shipping country.
     *
     * @return string
     */
    public function get_shipping_country()
    {
        if ($this->is_shipping_to_account_address()) {
            return $this->get_customer()->get_shipping_country();
        }
        return $this->get_child_prop('country', 'shipping');
    }

    /**
     * Get the date the wishlist was fulfilled
     *
     * This is the date all items in the wishlist were marked as purchased
     *
     * @return DateTime object
     */
    public function get_date_fulfilled()
    {
        return $this->get_prop('date_fulfilled');
    }

    /**
     * Get the total price of all items in the wishlist
     *
     * @return string
     */
    public function get_total($currency_symbol = false)
    {
        $total = 0;
        foreach ($this->get_items() as $item) {
            $total += $item->get_total();
        }
        return $currency_symbol ? wc_price($total, array('currency' => get_woocommerce_currency())) : $total;
    }

    /**
     * Get the permalink for the wishlist
     *
     * @return string
     */
    public function get_permalink()
    {
        return apply_filters('nmgr_wishlist_permalink', get_permalink($this->get_id()), $this);
    }

    /**
     * Get the user id of the user associated with the wishlist
     *
     * @return int
     */
    public function get_user_id()
    {
        return $this->get_prop('nmgr_user_id');
    }

    /**
     * Get the user associated with the wishlist
     *
     * @return WP_User|false
     */
    public function get_user()
    {
        return is_numeric($this->get_user_id()) ? get_user_by('id', $this->get_user_id()) : false;
    }

    /**
     * Get the customer associated with the wishlist
     *
     * This should be the same as the user associated with the wishlist
     * but simply retrieved as a WC_Customer object
     */
    public function get_customer()
    {
        return new WC_Customer($this->get_user_id());
    }

    /**
     * Get the slug of the wishlist
     *
     * @return string
     */
    public function get_slug()
    {
        return $this->get_prop('slug');
    }

    /**
     * Get the date the wishlist was created
     *
     * @return string
     */
    public function get_date_created()
    {
        return $this->get_prop('date_created');
    }

    /*
	  |--------------------------------------------------------------------------
	  | Setters
	  |--------------------------------------------------------------------------
	 */

    /**
     * Set the title of the wishlist
     */
    public function set_title($value)
    {
        $this->set_prop('title', $value);
    }

    /**
     * Set the post status of the wishlist
     */
    public function set_status($value)
    {
        $this->set_prop('status', $value);
    }

    /**
     * Set the first name of the wishlist owner
     */
    public function set_first_name($value)
    {
        $this->set_prop('first_name', $value);
    }

    /**
     * Set the last name of the wishlist owner
     */
    public function set_last_name($value)
    {
        $this->set_prop('last_name', $value);
    }

    /**
     * Set the first name of the wishlist owner's partner
     */
    public function set_partner_first_name($value)
    {
        $this->set_prop('partner_first_name', $value);
    }

    /**
     * Set the last name of the wishlist owner's partner
     */
    public function set_partner_last_name($value)
    {
        $this->set_prop('partner_last_name', $value);
    }

    /**
     * Set the registered email for the wishlist
     */
    public function set_email($value)
    {
        $this->set_prop('email', $value);
    }

    /**
     * Set the date for the wishlist event
     */
    public function set_event_date($value)
    {
        $this->set_prop('event_date', $value);
    }

    /**
     * Set the wishlist description
     */
    public function set_description($value)
    {
        $this->set_prop('description', $value);
    }

    /**
     * Sets whether the wishlist ships to the customer's account address
     */
    public function set_ship_to_account_address($value)
    {
        $this->set_prop('ship_to_account_address', $value);
    }

    /**
     * Set shipping first name.
     *
     * @param string $value Shipping first name.
     */
    public function set_shipping_first_name($value)
    {
        $this->set_child_prop('first_name', 'shipping', $value);
    }

    /**
     * Set shipping last name.
     *
     * @param string $value Shipping last name.
     */
    public function set_shipping_last_name($value)
    {
        $this->set_child_prop('last_name', 'shipping', $value);
    }

    /**
     * Set shipping company.
     *
     * @param string $value Shipping company.
     */
    public function set_shipping_company($value)
    {
        $this->set_child_prop('company', 'shipping', $value);
    }

    /**
     * Set shipping address line 1.
     *
     * @param string $value Shipping address line 1.
     */
    public function set_shipping_address_1($value)
    {
        $this->set_child_prop('address_1', 'shipping', $value);
    }

    /**
     * Set shipping address line 2.
     *
     * @param string $value Shipping address line 2.
     */
    public function set_shipping_address_2($value)
    {
        $this->set_child_prop('address_2', 'shipping', $value);
    }

    /**
     * Set shipping city.
     *
     * @param string $value Shipping city.
     */
    public function set_shipping_city($value)
    {
        $this->set_child_prop('city', 'shipping', $value);
    }

    /**
     * Set shipping state.
     *
     * @param string $value Shipping state.
     */
    public function set_shipping_state($value)
    {
        $this->set_child_prop('state', 'shipping', $value);
    }

    /**
     * Set shipping postcode.
     *
     * @param string $value Shipping postcode.
     */
    public function set_shipping_postcode($value)
    {
        $this->set_child_prop('postcode', 'shipping', $value);
    }

    /**
     * Set shipping country.
     *
     * @param string $value Shipping country.
     */
    public function set_shipping_country($value)
    {
        $this->set_child_prop('country', 'shipping', $value);
    }

    /**
     * Set user id.
     *
     * @param int $value User ID.
     */
    public function set_user_id($value)
    {
        $this->set_prop('nmgr_user_id', $value);
    }

    /**
     * Set the date the wishlist was fulfilled
     *
     * This is the date all items in the wishlist were marked as purchased
     *
     * @param string|integer|null $date UTC timestamp
     */
    public function set_date_fulfilled($date = null)
    {
        $this->set_prop('date_fulfilled', $date);
    }

    /*
	  |--------------------------------------------------------------------------
	  | Wishlist Items
	  |--------------------------------------------------------------------------
	 */

    /**
     * Remove all items  from this wishlist
     *
     * @return void
     */
    public function delete_items()
    {
        $this->db->delete_items($this);
        $this->items = array();
    }

    /**
     * Get all items in this wishlist
     *
     * @return array Array of wishlist item objects
     */
    public function get_items()
    {
        if (!empty($this->items)) {
            return $this->items;
        }
        $this->items = $this->db->read_items($this);
        return $this->items;
    }

    /**
     * Get a single wishlist item from the wishlist
     *
     * @param  int  $id wishlist_item_id or unique_id of item in database
     * @return NMGR_Wishlist_Item|false
     */
    public function get_item($id)
    {
        $items = $this->get_items();

        if (isset($items[$id])) {
            return $items[$id];
        }

        foreach ($items as $item) {
            if ($id === $item->get_unique_id()) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Bulk updates wishlist items from the wishlist items table
     * (used for updating item properties such as quantity, purchased_quantity and favourite)
     *
     * @param array $posted_data Posted data containing wishlist items properties to save.
     */
    public function update_items($posted_data)
    {
        if (isset($posted_data['wishlist_item_id'])) {
            foreach ($posted_data['wishlist_item_id'] as $item_id) {
                $item = $this->get_Item($item_id);

                if (!$item) {
                    continue;
                }

                // This array holds the props we are updating for each wishlist item
                $data_keys = array(
                    'wishlist_item_qty' => $item->get_quantity(),
                    'wishlist_item_purchased_qty' => $item->get_purchased_quantity(),
                );

                $item_data = array();

                foreach ($data_keys as $key => $default) {
                    $item_data[$key] = isset($posted_data[$key][$item_id]) ? wc_check_invalid_utf8(wp_unslash($posted_data[$key][$item_id])) : $default;
                }

                if ('0' === $item_data['wishlist_item_qty']) {
                    $item->delete();
                    continue;
                }

                $item->set_props(
                    array(
                        'quantity' => $item_data['wishlist_item_qty'],
                        'purchased_quantity' => $item_data['wishlist_item_purchased_qty'],
                    )
                );

                $item->save();
            }
            $this->db->clear_items_cache($this);
        }
    }

    /**
     * Remove an item from the wishlist.
     *
     * @param int $item_id Item ID to delete.
     * @return false|void
     */
    public function delete_item($item_id)
    {
        $item = $this->get_item($item_id);
        $item->delete();
        $this->db->clear_items_cache($this);
    }

    /**
     * Gets the count of items in this wishlist
     *
     * @return int
     */
    public function get_item_count()
    {
        $items = $this->get_items();
        $count = 0;

        foreach ($items as $item) {
            $count += absint($item->get_quantity());
        }
        return $count;
    }

    /**
     * Gets the count of purchased wishlist items
     *
     * @return int
     */
    public function get_item_purchased_count()
    {
        $items = $this->get_items();
        $count = 0;

        foreach ($items as $item) {
            $count += absint($item->get_purchased_quantity());
        }
        return $count;
    }

    /**
     * Add a wishlist ltem to this wishlist and save item in the database
     *
     * @param  WC_Product $product Product object.
     * @param  int        $qty Quantity to add.
     * @param array $variation Product variations if the product is a variation
     * @return int
     */
    public function add_item($product, $qty = 1, $favourite = null, $variation = array())
    {
        $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $variation_id = $product->is_type('variation') ? $product->get_id() : 0;

        // Generate a unique id to identify item in wishlist based on product ID, variation ID, and variation data
        $unique_id = $this->generate_unique_id($product_id, $variation_id, $variation);


        $args = array(
            'wishlist_id' => $this->get_id(),
            'name' => $product->get_name(),
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'variation' => $variation,
            'quantity' => $qty,
            'favourite' => $favourite,
            'unique_id' => $unique_id,
        );

        $item = new NMGR_Wishlist_Item();

        if ($this->has_item($unique_id)) {
            $item = $this->get_item($unique_id);

            // if the wishlist already has the item, we can only update the quantity
            $args['quantity'] = $item->get_quantity() + $qty;
        }

        $item->set_props($args);
        $item->save();
        $this->db->clear_items_cache($this);

        return $item->get_id();
    }

    /**
     * Checks if an item is already in the wishlist
     *
     * @param string $unique_id The unique id of the item in the wishlist
     * @return boolean
     */
    public function has_item($unique_id)
    {
        foreach ($this->get_items() as $item) {
            if ($item->get_unique_id() == $unique_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the wishlist has items
     *
     * @return boolean
     */
    public function has_items()
    {
        return count($this->get_items()) > 0;
    }

    /**
     * Generate a unique id for the wishlist item being added
     *
     * @param int $product_id ID of the product the key is being generated for
     * @param int $variation_id Variation id of the product the key is being generated for
     * @param array $variation Variation data for the wishlist item
     */
    public function generate_unique_id($product_id, $variation_id = 0, $variation = array())
    {
        $id_parts = array($product_id);

        if ($variation_id && 0 !== $variation_id) {
            $id_parts[] = $variation_id;
        }

        if (is_array($variation) && !empty($variation)) {
            $variation_key = '';
            foreach ($variation as $key => $value) {
                $variation_key .= trim($key) . trim($value);
            }
            $id_parts[] = $variation_key;
        }

        return md5(implode('_', $id_parts));
    }

    /**
     * Get all items in an order for this wishlist
     *
     * @param type $order_id Order id
     */
    public function get_items_in_order($order_id)
    {
        if (is_numeric($order_id)) {
            $order = wc_get_order($order_id);
        } elseif ($order_id instanceof WC_Order) {
            $order = $order_id;
        }

        if (!$order) {
            return;
        }

        $items = $order->get_items();
        $items_in_order = array();

        foreach ($items as $item_id => $item) {
            $meta = $item->get_meta(nmgr()->cart_key);
            if ($meta) {
                if ($meta['wishlist_id'] === $this->get_id()) {
                    $items_in_order[$item_id] = array(
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'variation_id' => $item->get_variation_id(),
                        'total' => $item->get_total(),
                    );
                }
            }
        }

        return apply_filters('nmgr_wishlist_get_items_in_order', $items_in_order, $order, $this);
    }

    /**
     * Get the wishlist item representing a product, if the product is in the wishlist
     *
     * @param int|WC_Product $product_id The product id or object
     * @return NMGR_Wishlist_Item|false
     */
    public function get_item_by_product($product_id)
    {
        $product = wc_get_product($product_id);

        if ($product) {
            foreach ($this->get_items() as $item) {
                if ($item->get_product_id() === $product->get_id()) {
                    return $item;
                }
            }
        }
        return false;
    }

    /*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

    /**
     * Whether the wishlist has a shipping address
     *
     * @return boolean
     */
    public function has_shipping_address()
    {
        if ($this->is_shipping_to_account_address()) {
            return (bool) $this->get_customer()->get_shipping_address();
        }
        return $this->get_shipping_address_1() || $this->get_shipping_address_2();
    }

    /**
     * Whether the wishlist ships to the customer's account shipping address
     *
     * @return boolean
     */
    public function is_shipping_to_account_address()
    {
        return (bool) $this->get_prop('ship_to_account_address');
    }

    /**
     * Checks if the wishlist has a product
     *
     * Wishlist items can be single products, variable products or items of group products.
     *
     * This function allows us to check if the product in question  (if it is a grouped product)
     * has an item in the wishlist, or whether the product (if it is a simple or variable product) is an item in the wishlist.
     * It basically queries the presence of the product in the wishlist in a general way.
     *
     * In this sense it differs from the 'has_item' function which checks for a particular item's
     * unique id in the wishlist and cannot tell whether the item is part of a variable or grouped product
     *
     * @param WC_Product $product
     * @return boolean true|false
     */
    public function has_product($product)
    {
        if (!$product) {
            return false;
        }

        if ($product->is_type('grouped') && $product->has_child()) {
            $product_item_ids = $product->get_children();
        } else {
            $product_item_ids = (array) $product->get_id();
        }

        $wishlist_item_ids = array_map(function ($item) use ($product) {
            return $product->is_type('variation') ? $item->get_variation_id() : $item->get_product_id();
        }, $this->get_items());

        $item_ids = array_intersect($wishlist_item_ids, $product_item_ids);
        return !empty($item_ids) ? true : false;
    }

    /**
     * Check if all the items in the wishlist have been fully purchased
     *
     * @return boolean
     */
    public function is_fulfilled()
    {
        if (
            $this->has_items() &&
            nmgr_get_option('display_item_quantity', 1) &&
            nmgr_get_option('display_item_purchased_quantity', 1)
        ) {
            $items = $this->get_items();

            foreach ($items as $item) {
                if (!$item->is_fulfilled()) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Check whether the wishlist is active.
     * An active wishist is a wishlist that has any of the registered post statuses and is not trashed.
     *
     * @return boolean
     */
    public function is_active()
    {
        return $this->get_id() && in_array($this->get_status(), nmgr_get_post_statuses());
    }

    /**
     * Check if the wishlist belongs to a guest
     *
     * @since 2.0.0
     * @return boolean
     */
    public function is_guest()
    {
        return $this->get_user_id() === get_post_meta($this->get_id(), '_nmgr_guest', true);
    }
}