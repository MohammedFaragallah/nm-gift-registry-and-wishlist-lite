<?php
/**
 * Call to action to create profile for new wishlists
 *
 * A profile needs to be created for a wishlist to exist
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/call-to-action-no-wishlist.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;
?>


<div class="nmgr-call-to-action-no-wishlist nmgr-text-center">
  <h4>
    <?php
        if (is_nmgr_admin() && isset($tab) && 'items' === $tab) {
            esc_html_e('No items yet.', 'nm-gift-registry-lite');
            echo '<br>';
            printf(
                /* translators: %s: wishlist type title */
                esc_html__(' Save this %s before you can start adding items to it.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            );
        } else {
            printf(
                /* translators: %s: wishlist type title */
                esc_html__('Get started with creating your %s profile', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            );
        }
        ?>
  </h4>

  <?php if (is_nmgr_account_tab()) : ?>
  <a class="button nmgr-go-to-tab nmgr-call-to-action-btn" href="#nmgr-tab-profile">
    <?php esc_html_e('Let\'s go', 'nm-gift-registry-lite'); ?>
  </a>
  <?php endif; ?>

</div>