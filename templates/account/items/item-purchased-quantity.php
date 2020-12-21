<?php

/**
 * Wishlist item purchased quantity
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-purchased-quantity.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
?>

<td class="purchased-quantity" data-title="<?php esc_attr_e('Purchased quantity', 'nm-gift-registry-lite'); ?>"
  data-sort-value="<?php echo esc_attr($purchased_quantity); ?>">
  <div class="view nmgr-tip" title="<?php esc_attr_e('Purchased quantity', 'nm-gift-registry-lite'); ?>">
    <?php echo esc_html($purchased_quantity); ?>
  </div>
  <div class="edit" style="display: none;">
    <input type="number" step="1" placeholder="0" autocomplete="off" size="4" class="quantity"
      value="<?php echo esc_attr($purchased_quantity); ?>" data-qty="<?php echo esc_attr($purchased_quantity); ?>"
      name="wishlist_item_purchased_qty[<?php echo absint($item->get_id()); ?>]" min="0"
      max="<?php echo esc_attr(apply_filters('nmgr_quantity_input_max', $product->get_stock_quantity(), $product)); ?>" />
  </div>
</td>