<?php

/**
 * Wishlist overview information
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/overview.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;
?>

<div id="nmgr-overview" class="<?php echo esc_attr($class); ?>"
  data-wishlist-id="<?php echo $wishlist ? absint($wishlist->get_id()) : 0; ?>"
  data-nonce="<?php echo esc_attr($nonce); ?>">
  <?php
  if (!$wishlist) :

    nmgr_get_no_wishlist_placeholder('overview', true);

  else :
    if ($title) {
      printf('<div class="nmgr-template-title overview">%s</div>', nmgr_kses_post($title));
    }

    do_action('nmgr_before_overview', $wishlist);
  ?>

  <h2 class="nmgr-title nmgr-text-center"><?php echo esc_html($wishlist->get_title()); ?></h2>

  <?php if ($wishlist->get_display_name()) : ?>
  <h3 class="nmgr-display-name nmgr-text-center"><?php echo esc_html($wishlist->get_display_name()); ?></h3>
  <?php endif; ?>

  <div class="nmgr-statistics">
    <?php if (isset($days, $days_notice)) : ?>
    <div class="stat time-remaining nmgr-background-color">
      <?php
          if (is_int($days)) {
            printf('<span class="highlight">%s</span>', esc_html($days));
          } else {
            printf('<span><strong>%s</strong></span>', esc_html($days));
          }

          echo wp_kses_post($days_notice);
          ?>
    </div>
    <?php endif; ?>

    <?php if (nmgr_get_option('display_item_quantity', 1)) : ?>
    <a href="#nmgr-tab-items"
      class="nmgr-go-to-tab stat items-total nmgr-background-color <?php echo $wishlist->is_fulfilled() ? esc_attr('highlight') : ''; ?>">
      <?php
          if ($wishlist->is_fulfilled()) {
            echo nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              'icon' => 'heart',
              'class' => 'nmgr-tip',
              'fill' => '#fff',
              'size' => 1.2,
              'title' => sprintf(
                /* translators: %s: wishlist type title */
                esc_html__('Your %s is fulfilled.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
              ),
            ));
          }
          ?>
      <span class="highlight"><?php echo absint($wishlist->get_item_count()); ?></span>
      <?php
          /* translators: %s: wishlist type title */
          printf(_n( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'item is in your %s',
            'items are in your %s',
            absint($wishlist->get_item_count()),
            'nm-gift-registry-lite'
          ), esc_html(nmgr_get_type_title()));
          ?>
    </a>
    <?php endif; ?>

    <?php if (nmgr_get_option('display_item_purchased_quantity', 1)) : ?>
    <a href="#nmgr-tab-items"
      class="nmgr-go-to-tab stat items-purchased nmgr-background-color <?php echo $wishlist->is_fulfilled() ? esc_attr('highlight') : ''; ?>">
      <?php
          if ($wishlist->is_fulfilled()) {
            echo nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              'icon' => 'heart',
              'class' => 'nmgr-tip',
              'fill' => '#fff',
              'size' => 1.2,
              'title' => sprintf(
                /* translators: %s: wishlist type title */
                esc_html__('Your %s is fulfilled.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
              ),
            ));
          }
          ?>
      <span class="highlight"><?php echo absint($wishlist->get_item_purchased_count()); ?></span>
      <?php
          echo _n( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'item has been bought',
            'items have been bought',
            absint($wishlist->get_item_purchased_count()),
            'nm-gift-registry-lite'
          );
          ?>
    </a>
    <?php endif; ?>

  </div>

  <?php
    if ($wishlist->is_fulfilled()) {
      // translators: %s: wishlist type titles
      wc_print_notice(sprintf(__('Congratulations, your %s is fulfilled.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title())), 'notice');
    }
    ?>

  <?php
    if ((!nmgr_get_option('shipping_address_required') ||
      (nmgr_get_option('shipping_address_required') && $wishlist->has_shipping_address()))) :
    ?>
  <p class="nmgr-shop-for-items nmgr-text-center">
    <a class="button nmgr-tip" title="<?php
                                          /* translators: %s: wishlist type title */
                                          printf(esc_attr__('Go shopping for items to add to your %s.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
                                          ?>" href="<?php echo esc_url(nmgr_get_add_items_url()); ?>">
      <?php esc_html_e('Add item(s)', 'nm-gift-registry-lite'); ?>
    </a>
  </p>
  <?php endif; ?>


  <?php nmgr_maybe_show_required_shipping_address_notice($wishlist); ?>


  <?php if ('publish' === $wishlist->get_status()) : ?>
  <div class="nmgr-share-wishlist nmgr-text-center nmgr-background-color nmgr-status-box">
    <h3>
      <?php
          /* translators: %s: wishlist type title */
          printf(esc_html__('Share your %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
          ?>
    </h3>

    <?php nmgr_get_share_template($wishlist, true); ?>

    <div>
      <?php
          printf(
            /* translators: %s: wishlist type title */
            esc_html__('You can copy your %s url below to share directly with others.', 'nm-gift-registry-lite'),
            esc_html(nmgr_get_type_title())
          );
          ?>
    </div>
    <div class="permalink-wrapper">
      <div class="link"><?php echo esc_html($wishlist->get_permalink()); ?></div>
      <div class="link-actions">
        <a href="#" class="nmgr-action button nmgr-copy nmgr-tip" role="button" title="<?php
                                                                                            /* translators: %s: wishlist type title */
                                                                                            printf(esc_attr__('Copy your %s url', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
                                                                                            ?>">
          <?php esc_html_e('Copy', 'nm-gift-registry-lite'); ?>
        </a>

        <a class="nmgr-action button nmgr-tip" href="<?php echo esc_url($wishlist->get_permalink()); ?>"
          title="<?php
                                                                                                                    /* translators: %s: wishlist type title */
                                                                                                                    printf(esc_attr__('View your %s page.', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
                                                                                                                    ?>">
          <?php esc_html_e('View', 'nm-gift-registry-lite'); ?>
        </a>
      </div>
    </div>
  </div>
  <?php
    endif;

    do_action('nmgr_after_overview');

  endif;
  ?>
</div>