<?php

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist item class
 */
class NMGR_Wishlist_Item extends NMGR_Data {

	/**
	 * Wishlist item data stored in nmgr_wishlist_items table
	 *
	 * @var array
	 */
	protected $core_data = array(
		// General wishlist id containing many items
		'wishlist_id' => 0,
		// product name
		'name' => '',
		// Date the item was added to the wishlist
		'date_created' => '',
		// Date the item was last updated
		'date_modified' => '',
	);

	/**
	 * Wishlist item meta data stored in nmgr_wishlist_itemmeta table
	 *
	 * Internal meta keys for wishlist item
	 *
	 * @var array
	 */
	protected $meta_data = array(
		'product_id' => 0,
		'variation_id' => 0,
		'variation' => array(),
		/**
		 * Quantity of the item in the wishlist
		 */
		'quantity' => 1,
		/**
		 * Quantity of the item purchased from the wishlist
		 */
		'purchased_quantity' => 0,
		/**
		 * The unique id of the item
		 * This id is generated based on the item properties such as product id, variation id and wishlist id
		 * as such it provides unique identification for the item in a wishlist and among all wishlists
		 */
		'unique_id' => '',
		/**
		 * Quantity reference of item for all orders
		 *
		 * This is an array of the ordered, refunded and original purchased quantity (if set) for the item,
		 * stored in the database with the order_id as array key
		 *
		 * This reference is used to calculate/update the purchased quantity of the item
		 *
		 * An item would have quantity references for all the orders which have certain quantities of the item.
		 * Each of these orders is accessed in the quantity reference array using the order id as array key.
		 */
		'quantity_reference' => array(),
	);

	/**
	 * Meta type.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_metadata/.
	 * @var string
	 */
	public $meta_type = 'wishlist_item';

	/**
	 * Name of the object type
	 *
	 * @var string
	 */
	protected $object_type = 'wishlist_item';

	/**
	 * Constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or NMGR_Wishlist_Item object.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( $item instanceof self ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} elseif ( is_object( $item ) && !empty( $item->wishlist_item_id ) ) {
			// $item would be an object with 'wishlist_item_id' if read from database
			$this->set_id( $item->wishlist_item_id );
		} else {
			// if we don't have an id, prepare to read object from database
			$this->set_object_read( true );
		}

		$this->db = new NMGR_Database_Wishlist_Item();

		if ( $this->get_id() > 0 ) {
			$this->db->read( $this );
		}
	}

	/*
	  |--------------------------------------------------------------------------
	  | Getters
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Get the name of the wishlist item
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->get_prop( 'name' );
	}

	/**
	 * Get the id of the wishlist the item belongs to
	 *
	 * @return int
	 */
	public function get_wishlist_id() {
		return absint( $this->get_prop( 'wishlist_id' ) );
	}

	/**
	 * Get the date the item was added to the wishlist
	 *
	 * @return Timestamp
	 */
	public function get_date_created() {
		return $this->get_prop( 'date_created' );
	}

	/**
	 * Get the date the item was last updated in the wishlist
	 *
	 * @return Timestamp
	 */
	public function get_date_modified() {
		return $this->get_prop( 'date_modified' );
	}

	/**
	 * Get the id of the product this item represents
	 *
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->get_prop( 'product_id' ) );
	}

	/**
	 * Get the id of the product variation this item represents
	 *
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->get_prop( 'variation_id' ) );
	}

	/**
	 * Get the variation of the product this item represents
	 *
	 * @return array
	 */
	public function get_variation() {
		return array_filter( ( array ) $this->get_prop( 'variation' ) );
	}

	/**
	 * Get the quantity of this item in the wishlist
	 *
	 * @return int
	 */
	public function get_quantity() {
		return wc_stock_amount( $this->get_prop( 'quantity' ) );
	}

	/**
	 * Get the purchased quantity of this item in the wishlist
	 *
	 * @return int
	 */
	public function get_purchased_quantity() {
		return absint( $this->get_prop( 'purchased_quantity' ) );
	}

	/**
	 * Get the unpurchased quantity of the item
	 * This only works if the quantity and purchased quantity columns are visible on the items table
	 *
	 * @return int
	 */
	public function get_unpurchased_quantity() {
		$quantity = nmgr_get_option( 'display_item_quantity', 1 ) ? $this->get_quantity() : 0;
		return max( $quantity - $this->get_purchased_quantity(), 0 );
	}

	/**
	 * Get the unique id of this item
	 *
	 * @return string
	 */
	public function get_unique_id() {
		return $this->get_prop( 'unique_id' );
	}

	/**
	 * Get the quantity reference of this item
	 *
	 * @return array
	 */
	public function get_quantity_reference() {
		return ( array ) $this->get_prop( 'quantity_reference' );
	}

	/**
	 * Get the product this item represents
	 *
	 * @return WC_Product
	 */
	public function get_product() {
		if ( $this->get_variation_id() ) {
			$product = wc_get_product( $this->get_variation_id() );
		} else {
			$product = wc_get_product( $this->get_product_id() );
		}
		return $product;
	}

	/**
	 * Get the wishlist this item belongs to
	 *
	 * @return NMGR_Wishlist
	 */
	public function get_wishlist() {
		return nmgr_get_wishlist( $this->get_wishlist_id() );
	}

	/**
	 * Get the total cost of the wishlist item (cost of product x qty)
	 *
	 * @param bool $currency_symbol Whether to return the value with the base currency symbol
	 * @return string
	 */
	public function get_total( $currency_symbol = false ) {
		$product = $this->get_product();
		if ( $product ) {
			$total = ( float ) wc_get_price_excluding_tax( $product, array( 'qty' => $this->get_quantity() ) );
			return $currency_symbol ? wc_price( $total, array( 'currency' => get_woocommerce_currency() ) ) : $total;
		}
	}

	/**
	 * Get variations that are not shown in the item title
	 * This is because variation titles display the attributes
	 *
	 * @return array Array of variation name, value pairs
	 */
	public function get_variations_for_display() {
		if ( empty( $this->get_variation() ) ) {
			return;
		}

		$variations = array();

		foreach ( $this->get_variation() as $name => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( !is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				// If this is a custom option slug, get the options name.
				$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $this->get_product() );
			}

			// Check the nicename against the title.
			if ( '' === $value || wc_is_attribute_in_product_name( $value, $this->get_product()->get_name() ) ) {
				continue;
			}

			$variations[] = array(
				'key' => $label,
				'value' => $value,
			);
		}

		return $variations;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Setters
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Set wishlist ID.
	 *
	 * @param int $value Wishlist ID.
	 */
	public function set_wishlist_id( $value ) {
		$this->set_prop( 'wishlist_id', absint( $value ) );
	}

	/**
	 * Set item name.
	 *
	 * @param string $value Item name.
	 */
	public function set_name( $value ) {
		$this->set_prop( 'name', wp_check_invalid_utf8( $value ) );
	}

	/**
	 * Set item desired quantity
	 *
	 * @param int $value Desired quantity
	 */
	public function set_quantity( $value ) {
		$this->set_prop( 'quantity', wc_stock_amount( $value ) );
	}

	/**
	 * Set item purchased quantity
	 *
	 * @param int $value purchased quantity
	 */
	public function set_purchased_quantity( $value ) {
		$this->set_prop( 'purchased_quantity', absint( $value ) );
	}

	/**
	 * Set item product id
	 *
	 * @param int $value Product id.
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Set item variation id
	 * @param int $value Product Id/Variation id
	 */
	public function set_variation_id( $value ) {
		$this->set_prop( 'variation_id', absint( $value ) );
	}

	/**
	 * Set the item variation
	 * @param array Product variation
	 */
	public function set_variation( $value ) {
		$this->set_prop( 'variation', $value );
	}

	/**
	 * Set all product details for item at once based on the product the item represents
	 *
	 * This sets the product id, variation id and variation
	 *
	 * @param WC_Product $product Product the item represents
	 */
	public function set_product( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$this->set_product_id( $product->get_parent_id() );
			$this->set_variation_id( $product->get_id() );
			$this->set_variation( is_callable( array( $product, 'get_variation_attributes' ) ) ? $product->get_variation_attributes() : array()  );
		} else {
			$this->set_product_id( $product->get_id() );
		}
		$this->set_name( $product->get_name() );
	}

	/**
	 * Set the unique id for the item
	 * @param string $value unique id
	 */
	public function set_unique_id( $value ) {
		$this->set_prop( 'unique_id', $value );
	}

	/**
	 * Set the quantity reference for the item
	 * @param array $value Quantity reference
	 */
	public function set_quantity_reference( $value ) {
		$this->set_prop( 'quantity_reference', $value );
	}

	/*
	  |--------------------------------------------------------------------------
	  | Conditionals
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Get whether any quantity of this item has been purchased
	 *
	 * This is only possible if the 'purchased quantity' column is visible on the items table
	 * as it is the column used to determine that item purchased would be accounted for
	 *
	 * @return boolean True or false
	 */
	public function is_purchased() {
		return nmgr_get_option( 'display_item_purchased_quantity', 1 ) ? ( bool ) $this->get_purchased_quantity() : false;
	}

	/**
	 * Get whether the desired quantity of this item has been completely purchased
	 *
	 * This is only possible if the 'quantity' and 'purchased_quantity' columns are visible on the items table
	 *
	 * @return boolean
	 */
	public function is_fulfilled() {
		if ( nmgr_get_option( 'display_item_quantity', 1 ) && nmgr_get_option( 'display_item_purchased_quantity' ) ) {
			return ( bool ) 0 >= $this->get_unpurchased_quantity();
		}
		return false;
	}

}
