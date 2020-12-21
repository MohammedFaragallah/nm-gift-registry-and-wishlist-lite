<?php
defined('ABSPATH') || exit;

class NMGR_Admin
{
    public static function run()
    {
        if (!is_admin()) {
            return;
        }

        add_action("manage_nm_gift_registry_posts_custom_column", array(__CLASS__, 'get_column_contents'), 999, 2);
        add_filter("manage_edit-nm_gift_registry_columns", array(__CLASS__, 'get_column_headers'));
        add_filter("manage_edit-nm_gift_registry_sortable_columns", array(__CLASS__, 'get_column_info'));
        add_action('show_user_profile', array(__CLASS__, 'show_enable_wishlist_form'));
        add_action('edit_user_profile', array(__CLASS__, 'show_enable_wishlist_form'));
        add_action('personal_options_update', array(__CLASS__, 'save_enable_wishlist_form'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_enable_wishlist_form'));
        add_filter('display_post_states', array(__CLASS__, 'post_states'), 10, 2);
        add_filter("manage_edit-shop_order_columns", array(__CLASS__, 'shop_order_column_headers'));
        add_action("manage_shop_order_posts_custom_column", array(__CLASS__, 'shop_order_column_contents'));
        add_action("admin_print_styles", array(__CLASS__, 'shop_order_styles'));
        add_filter("admin_footer_text", array(__CLASS__, 'admin_footer_text'), 10);
    }

    public static function get_column_headers($columns)
    {
        $nmgr = array();

        if (
            'no' !== nmgr_get_option('display_form_first_name') ||
            'no' !== nmgr_get_option('display_form_last_name') ||
            'no' !== nmgr_get_option('display_form_partner_first_name') ||
            'no' !== nmgr_get_option('display_form_partner_last_name')
        ) {
            $nmgr['nmgr_display_name'] = __('Display name', 'nm-gift-registry-lite');
        }

        if ('no' !== nmgr_get_option('display_form_email')) {
            $nmgr['nmgr_email'] = __('Email', 'nm-gift-registry-lite');
        }

        if ('no' !== nmgr_get_option('display_form_event_date')) {
            $nmgr['nmgr_event_date'] = __('Event date', 'nm-gift-registry-lite');
        }

        if (nmgr_get_option('enable_shipping')) {
            $nmgr['nmgr_shipping_address'] = __('Ships to', 'nm-gift-registry-lite');
        }

        if (nmgr_get_option('display_item_quantity')) {
            $nmgr['nmgr_quantity'] = nmgr_get_svg(array(
                'icon' => 'cart-empty',
                'class' => 'nmgr-tip',
                'title' => __('Desired Quantity', 'nm-gift-registry-lite'),
                'size' => 1.25,
            ));
        }

        if (nmgr_get_option('display_item_purchased_quantity')) {
            $nmgr['nmgr_purchased_quantity'] = nmgr_get_svg(array(
                'icon' => 'cart-full',
                'class' => 'nmgr-tip',
                'title' => __('Purchased Quantity', 'nm-gift-registry-lite'),
                'size' => 1.25
            ));
        }

        $nmgr['author'] = __('Author', 'nm-gift-registry-lite');

        $sorted_columns = array_slice($columns, 0, count($columns) - 1, true) +
            $nmgr +
            array_slice($columns, -1, 1, true);

        return $sorted_columns;
    }

    public static function get_column_info($sortable_columns)
    {
        $sortable_columns['nmgr_quantity'] = 'quantity';
        $sortable_columns['nmgr_purchased_quantity'] = 'purchased-quantity';
        return $sortable_columns;
    }

    public static function get_column_contents($column, $post_id)
    {
        $wishlist = nmgr_get_wishlist($post_id);

        switch ($column) {
            case 'nmgr_display_name':
                echo esc_html($wishlist->get_display_name());
                break;

            case 'nmgr_email':
                echo esc_html($wishlist->get_email());
                break;

            case 'nmgr_event_date':
                echo $wishlist->get_event_date() ? esc_html(nmgr_format_date($wishlist->get_event_date())) : '&mdash;';
                break;

            case 'nmgr_shipping_address':
                echo wc()->countries->get_formatted_address($wishlist->get_shipping());
                break;

            case 'nmgr_quantity':
                echo esc_html($wishlist->get_item_count());
                break;

            case 'nmgr_purchased_quantity':
                echo esc_html($wishlist->get_item_purchased_count());
                break;
        }
    }

    public static function show_enable_wishlist_form($user)
    {
        if (!nmgr_get_option('user_enable_wishlist') || !current_user_can('edit_' . nmgr()->post_type_plural)) {
            return;
        } ?>
<h2 id="user-nm-gift-registry"> <?php echo esc_html(nmgr()->name); ?></h2>
<table class="form-table" role="presentation">
  <tbody>
    <tr>
      <th>
        <label for="nmgr_enable_wishlist"><?php esc_html_e('Enable wishlist', 'nm-gift-registry-lite'); ?></label>
      </th>
      <td>
        <label for="nmgr_enable_wishlist">
          <input type="checkbox" name='nmgr_enable_wishlist' id='nmgr_enable_wishlist' value='1'
            <?php checked((int) get_user_meta($user->ID, 'nmgr_enable_wishlist', true), 1, true); ?> />
          <?php _e('Enable the wishlist module', 'nm-gift-registry-lite'); ?>
        </label>
      </td>
    </tr>
  </tbody>
</table>
<?php
    }

    public static function save_enable_wishlist_form($user_id)
    {
        if (!nmgr_get_option('user_enable_wishlist') || !current_user_can('edit_' . nmgr()->post_type_plural)) {
            return;
        }

        if (isset($_POST['nmgr_enable_wishlist'])) {
            update_user_meta($user_id, 'nmgr_enable_wishlist', 1);
        } else {
            delete_user_meta($user_id, 'nmgr_enable_wishlist');
        }
    }

    /**
     * Display wishlist post states
     *
     * @since 1.1.2
     */
    public static function post_states($states, $post)
    {
        if (absint(nmgr_get_option('single_wishlist_template')) === $post->ID) {
            $states['nmgr_single_wishlist_template'] = __('Single wishlist page', 'nm-gift-registry-lite');
        }

        if (absint(nmgr_get_option('search_results_template')) === $post->ID) {
            $states['nmgr_search_results_template'] = __('Wishlist search results page', 'nm-gift-registry-lite');
        }

        return $states;
    }

    public static function shop_order_column_headers($columns)
    {
        $nmgr = array('nm_gift_registry' => __('Wishlists', 'nm-gift-registry-lite'));
        $new_columns = array();

        foreach ($columns as $key => $name) {
            $new_columns[$key] = $name;

            if ('order_number' === $key) {
                $new_columns['nm_gift_registry'] = __('Wishlists', 'nm-gift-registry-lite');
            }
        }

        return $new_columns;
    }

    public static function shop_order_column_contents($column)
    {
        global $post;
        if ('nm_gift_registry' !== $column) {
            return;
        }

        $order = wc_get_order($post->ID);
        $order_wishlist_data = $order->get_meta('nm_gift_registry');

        if (!$order_wishlist_data) {
            return;
        }

        foreach ($order_wishlist_data as $wishlist_data) {
            $wishlist = isset($wishlist_data['wishlist_id']) ? nmgr_get_wishlist($wishlist_data['wishlist_id']) : false;

            if ($wishlist) {
                $item_count_text = sprintf(
                    /* translators: %s: count of items in wishlist */
                    _n('%s item', '%s items', count($wishlist_data['wishlist_item_ids']), 'nm-gift-registry-lite'),
                    count($wishlist_data['wishlist_item_ids'])
                );

                echo '<div class="nm-wishlist">';
                echo nmgr_get_wishlist_link($wishlist);
                echo '<div class="wishlist-details">';
                if ($wishlist->get_display_name()) {
                    echo '<span class="wishlist-display-name">' . $wishlist->get_display_name() . '</span>';
                }
                echo '<span class="wishlist-item-count-in-order nmgr-grey">' . $item_count_text . '</span>';
                echo '</div></div>';
            }
        }
    }

    public static function shop_order_styles()
    {
        global $post_type, $pagenow;

        if ('edit.php' === $pagenow && 'shop_order' === $post_type) {
            $css = 'td.column-nm_gift_registry .nm-wishlist {line-height:1.5em;margin-bottom:5px;}' .
                'td.column-nm_gift_registry .nm-wishlist .wishlist-details > * { display:block; }' .
                'td.column-nm_gift_registry .nm-wishlist .nmgr-grey { color: #999; }';
            wp_add_inline_style('woocommerce_admin_styles', $css);
        }
    }

    public static function admin_footer_text($text)
    {
        if (!is_nmgr_admin()) {
            return $text;
        }

        $five_star = esc_attr__('Five star', 'nm-gift-registry-lite');

        return sprintf(
            /* translators: 1: plugin homepage link, 2: wordpress plugin review link */
            __('Thanks for creating with %1$s. Love it  &hearts;, please leave a %2$s rating.', 'nm-gift-registry-lite'),
            '<a href="https://nmgiftregistry.com" target="_blank">' . nmgr()->name . '</a>',
            '<a href="https://wordpress.org/support/plugin/nm-gift-registry-and-wishlist-lite/reviews?rate=5#new-post" target="_blank" aria-label="' . $five_star . '" title="' . $five_star . '" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
        );
    }
}