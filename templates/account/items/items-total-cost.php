<?php
/**
 * Wishlist items total cost
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/items-total-cost.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
?>

<div class="after-table-row">
  <table class="total">
    <tr>
      <td class="label"><?php esc_html_e('Total', 'nm-gift-registry-lite'); ?>:</td>
      <td width="1%"></td>
      <td class="total">
        <?php echo $wishlist->get_total('true'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
      </td>
    </tr>
  </table>
</div>