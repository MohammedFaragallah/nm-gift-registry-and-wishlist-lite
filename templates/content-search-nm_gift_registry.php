<?php
/**
 * The template for displaying wishlist search results content in the search-nm_gift_registry.php template
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/content-search-nm_gift_registry.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

$wishlist = nmgr_get_wishlist();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('search-nm_gift_registry nmgr-background-color'); ?>>

  <div class="entry-content nmgr-col">
    <h2 class="entry-title nmgr-title">
      <a href="<?php echo esc_url($wishlist->get_permalink()); ?>" rel="bookmark">
        <?php echo esc_html($wishlist->get_title()); ?>
      </a>
    </h2>
    <?php
        if ($wishlist->get_event_date() || $wishlist->get_full_name()) {
            echo "<p class='nmgr-details'>";
            $wishlist->get_full_name() ? printf('<span class="nmgr-full-name">%s</span>', esc_html($wishlist->get_full_name())) : '';
            $wishlist->get_event_date() ? printf(
                '<span class="nmgr-event-date">%1$s <span class="nmgr-date">%2$s</span></span>',
                __('Event date:', 'nm-gift-registry-lite'),
                esc_html(nmgr_format_date($wishlist->get_event_date()))
            ) : '';
            echo "</p>";
        }
        ?>
  </div>

  <div class="entry-action nmgr-col">
    <a href="<?php echo esc_url($wishlist->get_permalink()); ?>" class="button" rel="bookmark">
      <?php
            /* translators: %s: wishlist type title */
            printf(esc_html__('View %s', 'nm-gift-registry-lite'), esc_html(nmgr_get_type_title()));
            ?>
    </a>
  </div>

</article>