<?php
/**
 * Wishlist item thumbnail
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-thumbnail.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
?>

<td class="thumb">
  <?php echo '<div class="thumbnail">' . $thumbnail . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
</td>