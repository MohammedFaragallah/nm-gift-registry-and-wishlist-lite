<?php

/**
 * The template for displaying the add to wishlist form on product archive and single pages
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/add-to-wishlist/form.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

global $product;
?>

<div class="nmgr-add-to-wishlist-wrapper <?php echo esc_attr($wrapper_class); ?>">
  <form class="<?php echo esc_attr($form_class); ?>"
    method="<?php echo esc_attr(isset($permalink) ? 'get' : 'post'); ?>"
    action="<?php echo isset($permalink) ? esc_url($permalink) : ''; ?>" aria-haspopup="dialog" aria-expanded="false"
    <?php echo $form_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                                                                                                                                                                                            ?>>

    <?php echo nmgr_kses_post($button); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>

    <?php
    // If we're using the button as an anchor, simply print the anchor
    if (isset($permalink)) :
      if (isset($permalink_args)) :
        foreach ($permalink_args as $key => $value) {
          printf('<input type="hidden" name="%1$s" value="%2$s">', esc_attr($key), esc_attr($value));
        }
      endif;

    // Else print the add to wishlist form
    else :
      $shipping_address_required = nmgr_get_option('shipping_address_required') &&
        $wishlist && !$wishlist->has_shipping_address() ? 1 : 0;
    ?>
    <input type="hidden" name="nmgr_pid" value="<?php echo absint($product->get_id()); ?>" />
    <input type="hidden" name="nmgr_wid" value="<?php echo $wishlist ? absint($wishlist->get_id()) : 0; ?>"
      data-shipping-address-required="<?php echo $shipping_address_required; ?>" />

    <?php if (!is_nmgr_shop_loop() && $product->is_type('variable')) : ?>
    <input type="hidden" name="nmgr_vid" value="0" />
    <input type="hidden" name="nmgr_wc_form_values" value="" />
    <?php endif; ?>

    <?php endif; ?>

  </form>
</div>