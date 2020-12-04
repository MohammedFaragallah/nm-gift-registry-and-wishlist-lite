<?php
defined('ABSPATH') || exit;

class NMGR_Widget_Search extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'nmgr_search',
            __('NM Gift Registry Search', 'nm-gift-registry-lite'),
            array(
                'description' => __('Wishlist search functionality.', 'nm-gift-registry-lite'),
            )
        );

        add_action('widgets_init', function () {
            register_widget('NMGR_Widget_Search');
        });
    }

    protected function get_form_fields()
    {
        return array(
            'title' => array(
                'type' => 'text',
                'default' => '',
                'label' => __('Title', 'nm-gift-registry-lite'),
            ),
            'hide_form' => array(
                'type' => 'checkbox',
                'default' => 0,
                'label' => __('Hide form', 'nm-gift-registry-lite'),
            ),
            'show_results' => array(
                'type' => 'checkbox',
                'default' => 0,
                'label' => __('Show results', 'nm-gift-registry-lite'),
            ),
            'show_results_if_empty' => array(
                'type' => 'checkbox',
                'default' => 0,
                'label' => __('Show all results if no search query', 'nm-gift-registry-lite'),
            ),
            'hide_results_title' => array(
                'type' => 'checkbox',
                'default' => 0,
                'label' => __('Hide results title', 'nm-gift-registry-lite'),
            ),
            'hide_post_count' => array(
                'type' => 'checkbox',
                'default' => 0,
                'label' => __('Hide number of results found', 'nm-gift-registry-lite'),
            ),
            'form_action' => array(
                'type' => 'text',
                'default' => home_url(),
                'placeholder' => sprintf(
                    /* translators: %s: site url */
                    __('e.g. %s', 'nm-gift-registry-lite'),
                    get_bloginfo('url')
                ),
                'label' => __('Form action', 'nm-gift-registry-lite'),
            ),
        );
    }

    public function widget($args, $instance)
    {
        echo $args[ 'before_widget' ];

        if (!empty($instance[ 'title' ])) {
            echo $args[ 'before_title' ] . apply_filters('widget_title', $instance[ 'title' ]) . $args[ 'after_title' ];
        }

        $template_args = array(
            'show_form' => empty($instance[ 'hide_form' ]) ? 1 : 0,
            'show_results_title' => empty($instance[ 'hide_results_title' ]) ? 1 : 0,
            'show_post_count' => empty($instance[ 'hide_post_count' ]) ? 1 : 0,
            'show_results_if_empty' => empty($instance[ 'show_results_if_empty' ]) ? 0 : 1,
            'show_results' => empty($instance[ 'show_results' ]) ? 0 : 1,
            'form_action' => empty($instance[ 'form_action' ]) ? '' : $instance[ 'form_action' ],
        );

        echo '<div class="widget_nmgr_search_content">';

        nmgr_get_search_template($template_args, true);

        echo '</div>';

        echo $args[ 'after_widget' ];
    }

    public function form($instance)
    {
        $fields = $this->get_form_fields();

        if (empty($fields)) {
            return;
        }

        foreach ($fields as $key => $setting) {
            $class = isset($setting[ 'class' ]) ? $setting[ 'class' ] : '';
            $placeholder = isset($setting[ 'placeholder' ]) ? $setting[ 'placeholder' ] : '';
            $value = isset($instance[ $key ]) ? $instance[ $key ] : $setting[ 'default' ];

            switch ($setting[ 'type' ]) {
                case 'text':
                    ?>
<p>
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo wp_kses_post($setting[ 'label' ]); ?></label>
  <input class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>" placeholder="<?php echo esc_attr($placeholder); ?>"
    type="text" value="<?php echo esc_attr($value); ?>" />
  <?php if (isset($setting[ 'desc' ])) : ?>
  <small><?php echo esc_html($setting[ 'desc' ]); ?></small>
  <?php endif; ?>
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
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting[ 'label' ]; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
</p>
<?php
                    break;

                case 'textarea':
                    ?>
<p>
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting[ 'label' ]; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
  <textarea class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>" cols="20"
    rows="3"><?php echo esc_textarea($value); ?></textarea>
  <?php if (isset($setting[ 'desc' ])) : ?>
  <small><?php echo esc_html($setting[ 'desc' ]); ?></small>
  <?php endif; ?>
</p>
<?php
                    break;

                case 'select':
                    ?>
<p>
  <label
    for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo $setting[ 'label' ]; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></label>
  <select class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>"
    name="<?php echo esc_attr($this->get_field_name($key)); ?>">
    <?php foreach ($setting[ 'options' ] as $option_key => $option_value) : ?>
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
            if (!isset($setting[ 'type' ])) {
                continue;
            }

            switch ($setting[ 'type' ]) {
                case 'textarea':
                    $instance[ $key ] = wp_kses(trim(wp_unslash($new_instance[ $key ])), wp_kses_allowed_html('post'));
                    break;
                case 'checkbox':
                    $instance[ $key ] = empty($new_instance[ $key ]) ? 0 : 1;
                    break;
                default:
                    $instance[ $key ] = isset($new_instance[ $key ]) ? sanitize_text_field($new_instance[ $key ]) : $setting[ 'default' ];
                    break;
            }
        }

        return $instance;
    }
}