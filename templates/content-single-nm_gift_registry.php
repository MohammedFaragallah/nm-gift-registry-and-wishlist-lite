<?php

/**
 * The template for displaying a wishlist's content in the single-nm_gift_registry.php template
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/content-single-nm_gift_registry.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;

$wishlist = nmgr_get_wishlist(get_the_ID(), true);

do_action('nmgr_before_single', $wishlist);

if (post_password_required()) {
  echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  return;
}
?>
<div id="nmgr-<?php the_ID(); ?>" <?php post_class(); ?>>

  <?php
  do_action('nmgr_wishlist', $wishlist);
  ?>
</div>

<?php do_action('nmgr_after_single', $wishlist); ?>