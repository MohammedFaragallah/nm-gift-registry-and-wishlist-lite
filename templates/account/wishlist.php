<?php

/**
 * Template for managing account information for a single wishlist
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/wishlist.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

/**
 * The $wishlist variable may be false if the wishlist doesn't exist
 * Always check if the wishlist exists before using the variable in this particular action
 */
do_action('nmgr_before_account_wishlist', $wishlist);

if (!empty($tabs)) :

  if (1 < count($tabs)) :
?>
<div id="nmgr-account-tabs" class="nmgr-tabs nmgr-account-sections">
  <ul class="nmgr-tab-wrapper">
    <?php foreach ($tabs as $key => $atab) : ?>
    <li id="<?php echo esc_attr($atab['tab_id']); ?>" class="nmgr-tab <?php echo esc_attr($key); ?>" tabindex="0"
      role="tab" aria-controls="tab-<?php echo esc_attr($key); ?>">
      <a href="#<?php echo esc_attr($atab['tab_content_id']); ?>"><?php echo nmgr_kses_post(apply_filters('nmgr_account_tab_title', esc_html($atab['title']), $key, $wishlist)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                        ?></a>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="nmgr-tab-content-wrapper">
    <?php foreach ($tabs as $key => $atab) : ?>
    <div id="<?php echo esc_attr($atab['tab_content_id']); ?>"
      class="nmgr-section-content nmgr-tab-content <?php echo esc_attr($key); ?>" role="tabpanel">
      <?php
            if (isset($atab['callback'])) {
              call_user_func($atab['callback'], $wishlist);
            }
            ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
  else :
    // We have only one account section, so no need to use tabs
    $tab = reset($tabs);
  ?>
<div class="nmgr-account-sections">
  <div class="nmgr-section-content <?php echo esc_attr(key($tabs)); ?>">
    <?php
        if (isset($tab['callback'])) {
          call_user_func($tab['callback'], $wishlist);
        }
        ?>
  </div>
</div>
<?php
  endif;

endif;

/**
 * The $wishlist variable may be false if the wishlist doesn't exist
 * Always check if the wishlist exists before using the variable in this particular action
 */
do_action('nmgr_after_account_wishlist', $wishlist);