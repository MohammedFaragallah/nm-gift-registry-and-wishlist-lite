<?php

/**
 * Wishlist profile form
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/profile.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;
?>

<div id="nmgr-profile" class="<?php echo esc_attr($class); ?>"
  data-wishlist-id="<?php echo $wishlist ? absint($wishlist->get_id()) : 0; ?>"
  data-nonce="<?php echo esc_attr($nonce); ?>">

  <?php
  do_action('nmgr_before_profile', $wishlist);

  if ($title) {
    printf('<div class="nmgr-template-title profile">%s</div>', nmgr_kses_post($title));
  }
  ?>

  <form class="nmgr-profile-form" method="POST">

    <?php
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $form->get_fields_html('profile');

    if ($form->has_fields()) {
      echo $form->get_hidden_fields();
      echo $form->get_submit_button();
    }
    // phpcs:enable
    ?>

  </form>

  <?php do_action('nmgr_after_profile', $wishlist); ?>

</div>