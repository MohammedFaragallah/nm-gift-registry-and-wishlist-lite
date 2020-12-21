<?php

/**
 * Wishlist shipping form
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/shipping.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;
?>

<div id="nmgr-shipping" class="<?php echo esc_attr($class); ?>"
  data-wishlist-id="<?php echo $wishlist ? absint($wishlist->get_id()) : 0; ?>"
  data-nonce="<?php echo esc_attr($nonce); ?>">

  <?php
  if (!$wishlist) :

    nmgr_get_no_wishlist_placeholder('shipping', true);

  else :

    if ($title) {
      printf('<div class="nmgr-template-title shipping">%s</div>', nmgr_kses_post($title));
    }

    do_action('nmgr_before_shipping', $wishlist);
  ?>

  <form class="nmgr-shipping-form" method="POST">

    <?php if ($customer->get_id()) : ?>

    <?php echo $form->get_fields_html(array('ship_to_account_address')); ?>

    <div class="account-shipping-address">
      <?php
          if ($customer->get_shipping_address()) {
            echo WC()->countries->get_formatted_address($customer->get_shipping());
          } else {
            $message = __('You have not set up a shipping address yet for your account.', 'nm-gift-registry-lite');
            $message .= sprintf(
              '<a class="button nmgr-call-to-action-btn" href="%s">%s</a>',
              wc_get_account_endpoint_url('edit-address'),
              __("Set now", 'nm-gift-registry-lite')
            );
            wc_print_notice($message, 'notice');
          }
          ?>
    </div>

    <?php endif; ?>

    <div class="wishlist-shipping-address">
      <div class="woocommerce-address-fields">
        <div class="woocommerce-address-fields__field-wrapper">
          <?php
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $form->get_fields_html('shipping');
            echo $form->get_hidden_fields();
            echo $form->get_submit_button();
            // phpcs:enable
            ?>
        </div>
      </div>
    </div>

  </form>

  <?php
    do_action('nmgr_after_shipping', $wishlist);

  endif;
  ?>

</div>