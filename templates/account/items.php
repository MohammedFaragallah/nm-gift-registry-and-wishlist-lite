<?php
/**
 * Template for displaying items in a wishlist
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

global $post;
?>

<div id="nmgr-items" class="<?php echo esc_attr($class); ?>"
  data-wishlist-id="<?php echo $wishlist ? absint($wishlist->get_id()) : 0; ?>"
  data-nonce="<?php echo esc_attr($nonce); ?>" data-editable="<?php echo esc_attr($items_args[ 'editable' ]); ?>">

  <?php
    if (!$wishlist):

        nmgr_get_no_wishlist_placeholder('items', true);

    else :

        if ($title) {
            printf('<div class="nmgr-template-title items">%s</div>', nmgr_kses_post($title));
        }

        do_action('nmgr_before_items', $items, $wishlist, $items_args);
        ?>

  <table class="nmgr-items-table nmgr-table">
    <thead>
      <tr><?php do_action('nmgr_items_table_header', $items, $wishlist, $items_args); ?></tr>
    </thead>
    <tbody>
      <?php
                foreach ($items as $item_id => $item) :
                    $the_product = $item->get_product();
                    $tr_title = '';

                    if (!$the_product) {
                        if (!is_nmgr_wishlist()) {
                            echo '<tr class="item-deleted-notice"><td colspan="20">The item ' . $item->get_name() . ' has been deleted from your wishlist as it no longer exists</td></tr>';
                            $item->delete();
                        }
                        continue;
                    }

                    // Don't show the item if we are on the frontend single wishlist page and it is fulfilled and should be hidden
                    if (is_nmgr_wishlist() && $item->is_fulfilled() && nmgr_get_option('hide_fulfilled_items', 1)) {
                        continue;
                    }

                    $item_class = array();

                    if ($item->is_purchased()) {
                        $item_class[] = 'item-purchased';
                    }

                    if ($item->is_fulfilled()) {
                        $item_class[] = 'item-fulfilled';
                    }

                    $row_class = apply_filters('nmgr_items_table_row_class', $item_class, $item, $items_args);

                    if ($item->is_fulfilled()) {
                        $tr_title = apply_filters('nmgr_item_row_fulfilled_text', sprintf(
                                /* translators: %s : wishlist type title */
                                __('This item has been bought for the %s owner.', 'nm-gift-registry-lite'),
                            nmgr_get_type_title()
                        ));
                    }
                    ?>
      <tr class="item <?php echo esc_attr(implode(' ', array_filter($row_class))); ?>"
        data-product_title="<?php echo sanitize_text_field($the_product->get_title()); ?>"
        data-wishlist_item_id="<?php echo absint($item->get_id()); ?>"
        data-wishlist_id="<?php echo absint($wishlist->get_id()); ?>" title="<?php echo esc_attr($tr_title); ?>">

        <?php
                        wc_setup_product_data($the_product->get_id());

                        do_action('nmgr_items_table_body', $item, $wishlist, $items_args);

                        wc_setup_product_data($post);
                        ?>

      </tr>
      <?php endforeach; ?>
    </tbody>
    <?php do_action('nmgr_after_items_table_body', $items, $wishlist, $items_args); ?>
  </table>

  <?php
        do_action('nmgr_after_items', $items, $wishlist, $items_args);

    endif;
    ?>

</div>