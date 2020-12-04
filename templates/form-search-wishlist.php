<?php
/**
 * Form for search for wishlists on the frontend
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/form-search-wishlist.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.4
 */
defined('ABSPATH') || exit;
?>

<form role="search" method="get" class="nmgr-search-form" action="<?php echo esc_url($form_action); ?>">
  <label class="screen-reader-text" for="<?php echo esc_attr($input_name); ?>">
    <?php esc_html_e('Search for:', 'nm-gift-registry-lite'); ?>
  </label>
  <input type="search" class="search-field" placeholder="<?php
                 /* translators: %s: wishlist type title */
                 printf(esc_attr__('Search %s&hellip;', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title('', true)));
                 ?>" value="<?php echo esc_attr($input_value); ?>" name="<?php echo esc_attr($input_name); ?>" />
  <button type="submit" value="<?php echo esc_attr_x('Search', 'submit button', 'nm-gift-registry-lite'); ?>">
    <?php echo esc_html_x('Search', 'submit button', 'nm-gift-registry-lite'); ?>
  </button>
  <?php if ($using_wp_search) : ?>
  <input type="hidden" name="post_type" value="nm_gift_registry" />
  <?php endif; ?>
</form>