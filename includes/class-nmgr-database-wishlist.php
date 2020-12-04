<?php

defined('ABSPATH') || exit;

/*
 * Handles CRUD operations for a wishlist
 */

class NMGR_Database_Wishlist
{

    /**
     * Create a new wishlist in the database.
     *
     * @param NMGR_Wishlist $wishlist Wishlist object.
     */
    public function create($wishlist)
    {
        $id = wp_insert_post(
            array(
                'post_type' => nmgr()->post_type,
                'post_status' => $wishlist->get_status(),
                'post_title' => $wishlist->get_title(),
                'post_excerpt' => $wishlist->get_description(),
                'post_author' => is_numeric($wishlist->get_user_id()) ? absint($wishlist->get_user_id()) : 0,
            )
        );

        if ($id && !is_wp_error($id)) {
            $wishlist->set_id($id);
            $this->update_meta_data($wishlist, true);
            $wishlist->apply_changes();

            do_action('nmgr_created_wishlist', $id);
        }
    }

    /**
     * Read a wishlist from the database
     *
     * @param NMGR_Wishlist $wishlist Wishlist  object.
     * @throws Exception for an invalid wishlist.
     */
    public function read($wishlist)
    {
        $wishlist->set_defaults();
        $post = get_post($wishlist->get_id());

        if (!$wishlist->get_id() || !$post || nmgr()->post_type !== $post->post_type) {
            /* translators: %s: wishlist type title */
            throw new Exception(sprintf(__('Invalid %s.', 'nm-gift-registry-lite'), nmgr_get_type_title()));
        }

        $wishlist->set_props(
            array(
                'title' => $post->post_title,
                'description' => $post->post_excerpt,
                'password' => $post->post_password,
                'status' => $post->post_status,
                'slug' => $post->post_name,
                'date_created' => $post->post_date,
            )
        );

        $this->read_meta_data($wishlist);
        $wishlist->set_object_read(true);
    }

    /**
     * Update a wishlist item in the database.
     *
     * @param NMGR_Wishlist $wishlist Wishlist object.
     */
    public function update($wishlist)
    {
        $changes = $wishlist->get_changes();
        $core_data = $wishlist->get_core_data();

        if (array_intersect(array_keys($core_data), array_keys($changes))) {
            $post_data = array(
                'post_status' => $wishlist->get_status(),
                'post_title' => $wishlist->get_title(),
                'post_excerpt' => $wishlist->get_description(),
                'post_author' => is_numeric($wishlist->get_user_id()) ? absint($wishlist->get_user_id()) : 0,
                'post_type' => nmgr()->post_type,
            );

            /**
             * When updating this object, to prevent infinite loops, use $wpdb
             * to update data, since wp_update_post spawns more calls to the
             * save_post action.
             *
             * This ensures hooks are fired by either WP itself (admin screen save),
             * or an update purely from CRUD.
             */
            if (doing_action('save_post')) {
                $GLOBALS[ 'wpdb' ]->update($GLOBALS[ 'wpdb' ]->posts, $post_data, array( 'ID' => $wishlist->get_id() ));
                clean_post_cache($wishlist->get_id());
            } else {
                wp_update_post(array_merge(array( 'ID' => $wishlist->get_id() ), $post_data));
            }
        }

        $this->update_meta_data($wishlist);
        $wishlist->apply_changes();

        do_action('nmgr_updated_wishlist', $wishlist->get_id());
    }

    /**
     * Remove a wishlist from the database.
     *
     * @paramNMGR_Wishlist $wishlist Wishlist object.
     * @param bool $force_delete Whether to permanently delete the wishlist. Default false
     */
    public function delete($wishlist, $force_delete = false)
    {
        $id = $wishlist->get_id();

        if (!$id) {
            return;
        }

        if ($force_delete) {
            wp_delete_post($id);
            $wishlist->set_id(0);
        } else {
            wp_trash_post($id);
            $wishlist->set_status('trash');
        }
    }

    /**
     * Read a wishlist's meta data
     *
     * @param NMGR_Wishlist $wishlist Wishlist object
     */
    public function read_meta_data($wishlist)
    {
        $data = array();
        $props_to_meta_keys = $wishlist->get_internal_meta_keys($wishlist->get_meta_data());

        foreach ($props_to_meta_keys as $prop => $meta_key) {
            $data[ $prop ] = get_post_meta($wishlist->get_id(), $meta_key, true);
        }

        $wishlist->set_props($data);
    }

    /**
     * Update a wishlist post meta
     *
     * @param NMGR_Wishlist $wishlist Wishlist object
     * @param bool $force	Whether to update all post meta or only changed ones. Used during create
     */
    public function update_meta_data($wishlist, $force = false)
    {
        $meta_data = $wishlist->get_meta_data();
        $props_to_update = $force ? $meta_data : $wishlist->get_props_to_update($meta_data);
        $props_to_meta_keys = $wishlist->get_internal_meta_keys($props_to_update);

        foreach ($props_to_meta_keys as $prop => $meta_key) {
            if (is_callable(array( $wishlist, "get_$prop" ))) {
                $value = $wishlist->{"get_$prop"}();
            } else {
                $value = $wishlist->get_prop($prop);
            }

            $value = is_string($value) ? wp_slash($value) : $value;
            update_post_meta($wishlist->get_id(), $meta_key, $value);
        }
    }

    /**
     * Read wishlist items from the database for this wishlist.
     *
     * @param  NMGR_Wishlist $wishlist Wishlist object.
     * @return array
     */
    public function read_items($wishlist)
    {
        global $wpdb;

        // Get from cache if available.
        $items = 0 < $wishlist->get_id() ? wp_cache_get('nmgr-wishlist-items-' . $wishlist->get_id()) : false;

        if (false === $items) {
            // Items from database are returned as objects
            $items = $wpdb->get_results(
                $wpdb->prepare("SELECT wishlist_item_id, wishlist_id, name FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_id = %d ORDER BY wishlist_item_id DESC;", $wishlist->get_id())
            );

            if (0 < $wishlist->get_id()) {
                wp_cache_set('nmgr-wishlist-items-' . $wishlist->get_id(), $items);
            }
        }

        if (!empty($items)) {
            // get the item ids from the items objects
            $item_ids = wp_list_pluck($items, 'wishlist_item_id');

            // create new array of item ids to the database objects they represent
            $item_ids_to_db_objs = array_combine($item_ids, $items);

            // create new array of the item ids to their NMGR_Wishlist_Item class instances
            $item_ids_to_class_objs = array_map(function ($id) {
                return new NMGR_Wishlist_Item($id);
            }, $item_ids_to_db_objs);

            return $item_ids_to_class_objs;
        } else {
            return array();
        }
    }

    /**
     * Remove all items from the wishlist.
     *
     * @param  NMGR_Wishlist $wishlist Wishlist object.
     */
    public function delete_items($wishlist)
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM itemmeta USING {$wpdb->prefix}nmgr_wishlist_itemmeta itemmeta INNER JOIN {$wpdb->prefix}nmgr_wishlist_items items WHERE itemmeta.wishlist_item_id = items.wishlist_item_id and items.wishlist_id = %d", $wishlist->get_id()));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}nmgr_wishlist_items WHERE wishlist_id = %d", $wishlist->get_id()));

        $this->clear_items_cache($wishlist);
    }

    public function clear_items_cache($wishlist)
    {
        wp_cache_delete('nmgr-wishlist-items-' . $wishlist->get_id());
    }
}