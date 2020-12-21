<?php
defined('ABSPATH') || exit;

class NMGR_Widget_Cart extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'nmgr_cart',
            __('NM Gift Registry Cart', 'nm-gift-registry-lite'),
            array(
                'description' => __('Display a user\'s wishlists like a cart.', 'nm-gift-registry-lite'),
            )
        );

        add_action('widgets_init', function () {
            register_widget('NMGR_Widget_Cart');
        });
    }

    protected function get_form_fields()
    {
        return array(
            'title' => array(
                'type' => 'text',
                'std' => sprintf(
                    /* translators: %s: wishlist type title */
                    __('%s Cart', 'nm-gift-registry-lite'),
                    nmgr_get_type_title('c')
                ),
                'label' => __('Title', 'nm-gift-registry-lite'),
            ),
            'number_of_items' => array(
                'type' => 'number',
                'label' => __('Number of items to show', 'nm-gift-registry-lite'),
            ),
            'show_cart_contents_only' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Show cart contents only', 'nm-gift-registry-lite'),
            ),
            'hide_item_image' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide item image', 'nm-gift-registry-lite'),
            ),
            'hide_item_qty_cost' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide item quantity and cost', 'nm-gift-registry-lite'),
            ),
            'hide_item_add_to_cart_button' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide item add to cart button', 'nm-gift-registry-lite'),
            ),
            'hide_item_availability' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide item availability', 'nm-gift-registry-lite'),
            ),
            'show_item_rating' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Show item rating', 'nm-gift-registry-lite'),
            ),
            'hide_total_quantity' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide total quantity of items', 'nm-gift-registry-lite'),
            ),
            'hide_total_cost' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide total cost of items', 'nm-gift-registry-lite'),
            ),
            'hide_manage_button' => array(
                'type' => 'checkbox',
                'std' => 0,
                'label' => __('Hide button for managing wishlists', 'nm-gift-registry-lite'),
            ),
        );
    }

    public function widget($args, $instance)
    {
        if (!is_nmgr_enabled() || (!is_nmgr_user() && !nmgr_get_option('add_to_wishlist_guests'))) {
            return;
        }

        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $template_args = array(
            'show_item_image' => empty($instance['hide_item_image']) ? 1 : 0,
            'show_item_add_to_cart_button' => empty($instance['hide_item_add_to_cart_button']) ? 1 : 0,
            'show_item_qty_cost' => empty($instance['hide_item_qty_cost']) ? 1 : 0,
            'show_item_availability' => empty($instance['hide_item_availability']) ? 1 : 0,
            'show_item_rating' => empty($instance['show_item_rating']) ? 0 : 1,
            'show_total_quantity' => empty($instance['hide_total_quantity']) ? 1 : 0,
            'show_total_cost' => empty($instance['hide_total_cost']) ? 1 : 0,
            'show_manage_button' => empty($instance['hide_manage_button']) ? 1 : 0,
            'number_of_items' => empty($instance['number_of_items']) ? '' : $instance['number_of_items'],
            'show_cart_contents_only' => empty($instance['show_cart_contents_only']) ? 0 : 1,
        );

        echo '<div class="widget_nmgr_cart_content">';

        nmgr_get_cart_template($template_args, true);

        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $fields = $this->get_form_fields();

        if (empty($fields)) {
            return;
        }

        foreach ($fields as $key => $setting) {
            $class = isset($setting['class']) ? $setting['class'] : '';
            $value = isset($instance[$key]) ? $instance[$key] : (isset($setting['std']) ? $setting['std'] : '');

            switch ($setting['type']) {
                case 'text':
                case 'number':
?>
<p>
  <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo wp_kses_post($setting['label']); ?></label>
  <input class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>" type="<?php echo $setting['type']; ?>"
    value="<?php echo esc_attr($value); ?>" />
</p>
<?php
                    break;

                case 'checkbox':
                ?>
<p>
  <input class="checkbox <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>" type="checkbox" value="1"
    <?php checked($value, 1); ?> />
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting['label']; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
</p>
<?php
                    break;

                case 'textarea':
                ?>
<p>
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting['label']; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
  <textarea class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>" cols="20"
    rows="3"><?php echo esc_textarea($value); ?></textarea>
  <?php if (isset($setting['desc'])) : ?>
  <small><?php echo esc_html($setting['desc']); ?></small>
  <?php endif; ?>
</p>
<?php
                    break;

                case 'select':
                ?>
<p>
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting['label']; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
  <select class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>">
    <?php foreach ($setting['options'] as $option_key => $option_value) : ?>
    <option value="<?php echo esc_attr($option_key); ?>" <?php selected($option_key, $value); ?>>
      <?php echo esc_html($option_value); ?></option>
    <?php endforeach; ?>
  </select>
</p>
<?php
                    break;
            }
        }
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        if (empty($this->get_form_fields())) {
            return $instance;
        }

        foreach ($this->get_form_fields() as $key => $setting) {
            if (!isset($setting['type'])) {
                continue;
            }

            switch ($setting['type']) {
                case 'textarea':
                    $instance[$key] = wp_kses(trim(wp_unslash($new_instance[$key])), wp_kses_allowed_html('post'));
                    break;
                case 'checkbox':
                    $instance[$key] = empty($new_instance[$key]) ? 0 : 1;
                    break;
                default:
                    $instance[$key] = isset($new_instance[$key]) ? sanitize_text_field($new_instance[$key]) : $setting['std'];
                    break;
            }
        }

        return $instance;
    }
}