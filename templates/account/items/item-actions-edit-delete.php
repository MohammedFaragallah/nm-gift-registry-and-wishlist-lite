<?php
/**
 * Wishlist item edit and delete actions
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry-lite/account/items/item-actions-edit-delete.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 1.0.0
 */
defined('ABSPATH') || exit;
?>

<td class="actions edit-delete">
  <div class="edit-delete-wrapper">

    <?php if ($show_edit_button) : ?>
    <a class="edit-wishlist-item nmgr-tip" href="#" title="<?php esc_attr_e('Edit item', 'nm-gift-registry-lite'); ?>">
      <?php
                echo nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'icon' => 'pencil',
                    'fill' => 'currentColor'
                ));
                ?>
    </a>
    <?php endif; ?>

    <a class="delete-wishlist-item nmgr-tip" href="#"
      title="<?php esc_attr_e('Delete item', 'nm-gift-registry-lite'); ?>">
      <?php
            echo nmgr_get_svg(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'icon' => 'trash-can',
                'fill' => 'currentColor'
            ));
            ?>
    </a>
  </div>
</td>