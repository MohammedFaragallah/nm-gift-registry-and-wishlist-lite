<?php
/**
 * Template for displaying the add to wishlist view in a dialog
 *
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry/add-to-wishlist/dialog-content.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

if (!isset($product)) {
    $product = $GLOBALS[ 'product' ];
}
$product_id = absint($product->get_id());
?>

<form class="nmgr-add-to-wishlist-content product-type-<?php echo esc_attr($product->get_type()); ?>">
  <?php
    do_action('nmgr_before_add_to_wishlist_dialog_content');

    $thumbnail = $product->get_image('nmgr_thumbnail', array(
        'title' => $product->get_name(),
        'alt' => $product->get_name() ));
    ?>
  <div class="product-thumbnail"><?php echo nmgr_kses_post($thumbnail); ?></div>
  <div class="product-title nmgr-row"> <?php echo esc_html($product->get_name()); ?> </div>

  <select name="nmgr_wid" class="list-of-wishlists nmgr-row">
    <?php
        foreach ($wishlists as $wishlist) {
            $wishlist_id = absint($wishlist->get_id());
            $shipping_address_required = nmgr_get_option('shipping_address_required') && !$wishlist->has_shipping_address() ? 1 : 0;
            $title = !$wishlist->has_product($product) ? '' : sprintf(
                    /* translators: %s: wishlist type title */
                    __('This product is in this %s', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            ); ?>
    <option value="<?php echo $wishlist_id; ?>"
      data-shipping-address-required="<?php echo $shipping_address_required; ?>" <?php
                            if (isset($selected_wishlist_id) && absint($selected_wishlist_id) === $wishlist_id) {
                                echo 'selected="selected"';
                            } ?> title="<?php echo esc_attr($title); ?>">
      <?php
                                echo esc_html($wishlist->get_title());

            echo $wishlist->has_product($product) ? '  &hearts;' : ''; ?>
    </option>
    <?php
        } ?>
  </select>

  <?php
    if ($product->is_type('grouped')) {
        nmgr_template('add-to-wishlist/options-grouped.php', $args);
    }
    ?>

  <?php do_action('nmgr_after_add_to_wishlist_dialog_content'); ?>

  <?php
    foreach ($formdata as $key => $value) {
        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
    }
    ?>
</form><!-- .add-to-wishlist-content -->