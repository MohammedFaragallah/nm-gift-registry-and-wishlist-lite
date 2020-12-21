<?php

/**
 * Wishlist item cost of all quantities
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-total_cost.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
?>

<td class="total-cost" data-title="<?php esc_attr_e('Total cost', 'nm-gift-registry-lite'); ?>"
  data-sort-value="<?php echo esc_attr($item->get_total()); ?>">
  <div class="view nmgr-tip" title="<?php esc_attr_e('Total cost', 'nm-gift-registry-lite'); ?>">
    <?php echo $item->get_total(true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
  </div>
  <div class="edit" style="display: none;">
    <div class="split-input">
      <div class="input">
        <label><?php esc_attr_e('Total', 'nm-gift-registry-lite'); ?></label>
        <input disabled type="text" name="item_total[<?php echo absint($item->get_id()); ?>]"
          placeholder="<?php echo esc_attr(wc_format_localized_price(0)); ?>"
          value="<?php echo esc_attr($item->get_total()); ?>" class="item_total"
          data-total="<?php echo esc_attr($item->get_total()); ?>" />
      </div>
    </div>
  </div>
</td>