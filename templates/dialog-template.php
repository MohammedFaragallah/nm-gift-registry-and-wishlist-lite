<?php

/**
 * Template for displaying content in a dialog window
 *
 * This template can be overridden by copying it to:
 * yourtheme/nm-gift-registry/dialog-template.php
 *
 * The template may also be updated in future versions of the plugin.
 * In such case you would need to copy the new template to your theme to maintain compatibility
 *
 * @package NM Gift Registry Lite/Templates
 * @version 2.0.0
 */
defined('ABSPATH') || exit;
global $nmgr;

$close_button = '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>';
?>

<?php if ($show_header) : ?>
<div class="modal-header">

  <?php if ($title) : ?>
  <h4 class="modal-title"><?php echo esc_html($title); ?></h4>
  <?php endif; ?>

  <?php
    if ($show_header_close_button) {
      echo $close_button;
    }
    ?>

</div>
<?php endif; ?>

<div class="modal-body">
  <?php
  if ($show_body_close_button) {
    echo $close_button;
  }

  echo $content;
  ?>
</div>

<?php if ($footer) : ?>
<div class="modal-footer">
  <?php echo $footer; ?>
</div>
<?php
endif;