<?php

defined('ABSPATH') || exit;

/*
 * Handles CRUD operations for a wishlist item
 */

class NMGR_Database_Wishlist_Item
{

    /**
     * Create a new wishlist item in the database.
     *
     * @param NMGR_Wishlist_Item $item Wishlist item object.
     */
    public function create($item)
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'nmgr_wishlist_items',
            array(
            'name' => $item->get_name(),
            'wishlist_id' => $item->get_wishlist_id(),
            'date_created' => current_time('mysql', 1),
            'date_modified' => current_time('mysql', 1),
            )
        );
        $item->set_id($wpdb->insert_id);
        $this->update_meta_data($item, true);
        $item->apply_changes();

        do_action_deprecated('nmgr_new_wishlist_item', array( $item->get_id(), $item, $item->get_wishlist_id() ), '2.1.0', 'nmgr_wishlist_item_created');
        do_action('nmgr_wishlist_item_created', $item, $item->get_wishlist());
    }

    /**
     * Read/populate data properties specific to this wishlist item.
     *
     * @param NMGR_Wishlist_Item $item Wishlist item object.
     */
    public function read($item)
    {
        global $wpdb;

        $item->set_defaults();

        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_item_id = %d LIMIT 1;", $item->get_id()));

        if (!$data) {
            /* translators: %s: wishlist type title */
            throw new Exception(sprintf(__('Invalid %s item.', 'nm-gift-registry-lite'), nmgr_get_type_title()));
        }

        $item->set_props(
            array(
                'wishlist_id' => $data->wishlist_id,
                'name' => $data->name,
                'date_created' => 0 < $data->date_created ? $data->date_created : null,
                'date_modified' => 0 < $data->date_modified ? $data->date_modified : null,
            )
        );

        $this->read_meta_data($item);
        $item->set_object_read(true);
    }

    /**
     * Update a wishlist item in the database.
     *
     * @param NMGR_Wishlist_Item $item Wishlist item object.
     */
    public function update($item)
    {
        global $wpdb;

        /**
         * We want to update the nmgr_wishlist_items table if any of the item's properties has changed
         * This is mainly so that we can update the 'date_modified' value
         */
        if (array_intersect(array_keys($item->get_data()), array_keys($item->get_changes()))) {
            $wpdb->update(
                $wpdb->prefix . 'nmgr_wishlist_items',
                array(
                'name' => $item->get_name(),
                'wishlist_id' => $item->get_wishlist_id(),
                'date_modified' => current_time('mysql', 1),
                ),
                array( 'wishlist_item_id' => $item->get_id() )
            );
        }

        $this->update_meta_data($item);
        $item->apply_changes();

        do_action_deprecated('nmgr_update_wishlist_item', array( $item->get_id(), $item, $item->get_wishlist_id() ), '2.1.0', 'nmgr_wishlist_item_updated');
        do_action('nmgr_wishlist_item_updated', $item, $item->get_wishlist());
    }

    /**
     * Remove a wishlist item from the database.
     *
     * @param int|object $item NMGR_Wishlist_Item| Item id
     */
    public function delete($item)
    {
        $id = 0;
        if (is_numeric($item)) {
            $id = $item;
        } elseif (is_object($item) && $item->get_id()) {
            $id = $item->get_id();
        }

        if ($id) {
            global $wpdb;

            do_action('nmgr_before_delete_wishlist_item', $item->get_id());

            $wpdb->delete($wpdb->prefix . 'nmgr_wishlist_items', array( 'wishlist_item_id' => $id ));
            $wpdb->delete($wpdb->prefix . 'nmgr_wishlist_itemmeta', array( 'wishlist_item_id' => $id ));

            do_action_deprecated('nmgr_delete_wishlist_item', array( $item->get_id(), $item->get_wishlist_id() ), '2.1.0', 'nmgr_wishlist_item_deleted');
            do_action('nmgr_wishlist_item_deleted', $item, $item->get_wishlist());
        }
    }

    /**
     * Read a wishlist item's meta data
     *
     * @param NMGR_Wishlist_Item $item Wishlist item object
     */
    public function read_meta_data($item)
    {
        $props_to_meta_keys = $item->get_internal_meta_keys($item->get_meta_data());
        foreach ($props_to_meta_keys as $prop => $meta_key) {
            $item->set_prop($prop, get_metadata($item->meta_type, $item->get_id(), $meta_key, true));
        }
    }

    /**
     * Saves an item's meta data in bulk to the database
     *
     * @param NMGR_Wishlist_Item $item Wishlist item object.
     * @param bool $force	Whether to update all meta or only changed ones. Used during create
     */
    public function update_meta_data($item, $force = false)
    {
        $meta_data = $item->get_meta_data();
        $props_to_update = $force ? $meta_data : $item->get_props_to_update($meta_data, $item->meta_type);
        $props_to_meta_keys = $item->get_internal_meta_keys($props_to_update);

        foreach ($props_to_meta_keys as $prop => $meta_key) {
            if (is_callable(array( $item, "get_$prop" ))) {
                $value = $item->{"get_$prop"}();
            } else {
                $value = $item->get_prop($prop);
            }

            $value = is_string($value) ? wp_slash($value) : $value;
            update_metadata($item->meta_type, $item->get_id(), $meta_key, $value);
        }
    }
}