<?php
defined('ABSPATH') || exit;

/*
 * Handles Creating and storing Plugin Settings in Admin
 */

class NMGR_Admin_Settings
{

    /**
     * Slug of settings page
     */
    public static $settings_slug = 'nmgr-settings';

    /**
     * The current tab we are on
     */
    private static $current_tab;

    /**
     * The current section we are on
     */
    private static $current_section;

    /**
     * Name of options in the database table
     * (also used as the options_group value in the 'register_setting' function)
     */
    public static $option_name = 'nmgr_settings';

    public static function run()
    {
        add_filter('woocommerce_screen_ids', array(__CLASS__, 'add_screen_id'));
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'add_settings_fields'));
        add_filter('pre_update_option_' . nmgr()->option_name, array(__CLASS__, 'pre_update_option_actions'), 10, 2);
        add_action('update_option_' . nmgr()->option_name, array(__CLASS__, 'update_option_actions'), 10, 2);
        add_action('nmgr_settings_sections_tab_full_version', array(__CLASS__, 'do_settings_sections_full_version'));

        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_GET['page']) && self::$settings_slug === $_GET['page']) {
            self::$current_tab = self::get_current_tab($_GET);
            self::$current_section = self::get_current_section($_GET);
        }
        // phpcs:enable
    }

    /**
     * Set the nm_gift_registry settings page as a woocommerce admin page
     * so that woocommerce can enqueue its admin styles for us
     */
    public static function add_screen_id($screen_ids)
    {
        $screen_ids[] = nmgr()->post_type . '_page_' . self::$settings_slug;
        return $screen_ids;
    }

    /**
     * Get the current settings tab being viewed
     *
     * @param array $request The associative array used to determine the tab, typically $_GET or HTTP_REFERER
     * @return string
     */
    public static function get_current_tab($request)
    {
        return empty($request['tab']) ? 'general' : sanitize_title(wp_unslash($request['tab']));
    }

    /**
     * Get the current settings section being viewed
     *
     * @param array $request The associative array used to determine the section, typically $_GET or HTTP_REFERER
     * @return string
     */
    public static function get_current_section($request)
    {
        $section = isset($request['section']) ? sanitize_title(wp_unslash($request['section'])) : '';
        $tab = self::get_current_tab($request);

        if ($tab && !$section) {
            $tab_args = isset(self::get_tabs()[$tab]) ? self::get_tabs()[$tab] : '';
            if (!empty($tab_args) && isset($tab_args['sections'])) {
                $sec = array_flip($tab_args['sections']);
                $section = reset($sec);
            }
        }
        return $section;
    }

    public static function pre_update_option_actions($new_value, $old_value)
    {
        $default_values = self::get_default_values();

        if ($old_value['enable_shipping'] !== $new_value['enable_shipping']) {
            if (!$new_value['enable_shipping']) {
                $new_value['shipping_address_required'] = '';
            }
        }

        if ($old_value['type'] !== $new_value['type']) {
            if ('wishlist' == $new_value['type']) {
                $new_value['type_title'] = 'wishlist';
                $new_value['display_form_event_date'] = 'no';
                $new_value['display_form_partner_first_name'] = 'no';
                $new_value['display_form_partner_last_name'] = 'no';
                $new_value['display_item_purchased_quantity'] = '';
                $new_value['enable_shipping'] = '';
                $new_value['display_tab_shipping'] = '';
                $new_value['allow_guest_wishlists'] = 1;
            } elseif ('gift_registry' == $new_value['type']) {
                $new_value['type_title'] = 'gift_registry';
                $new_value['enable_shipping'] = 1;

                foreach (array_keys($new_value) as $key) {
                    if (strpos($key, 'display_form_') !== false) {
                        $new_value[$key] = 'optional';
                    }

                    if (strpos($key, 'display_item_') !== false) {
                        $new_value[$key] = 1;
                    }

                    if (strpos($key, 'display_tab_') !== false) {
                        $new_value[$key] = 1;
                    }
                }
            }
        }

        if ($old_value['display_item_quantity'] !== $new_value['display_item_quantity']) {
            if (!$new_value['display_item_quantity']) {
                $new_value['display_item_total_cost'] = '';
            } else {
                $new_value['display_item_total_cost'] = 1;
            }
        }

        if (!$new_value['display_item_quantity'] || !$new_value['display_item_purchased_quantity']) {
            $new_value['hide_fulfilled_items'] = '';
        }

        if (!$new_value['add_to_wishlist_button_text']) {
            $new_value['add_to_wishlist_button_text'] = $default_values['add_to_wishlist_button_text'];
        }

        return $new_value;
    }

    public static function update_option_actions($old_value, $new_value)
    {
        global $wpdb;

        if ($old_value['my_account_name'] != $new_value['my_account_name']) {
            update_option('nmgr_flush_rewrite_rules', 'yes');
        }

        if ($old_value['permalink_base'] != $new_value['permalink_base']) {
            update_option('nmgr_flush_rewrite_rules', 'yes');
        }

        if ($old_value['wishlist_account_page_id'] != $new_value['wishlist_account_page_id']) {
            update_option('nmgr_flush_rewrite_rules', 'yes');
        }

        if ($old_value['user_enable_wishlist'] != $new_value['user_enable_wishlist']) {
            if (!$new_value['user_enable_wishlist']) {
                // delete all the enable_wishlist options for all users in the user meta table
                delete_metadata('user', 0, 'nmgr_enable_wishlist', '', true);
            } else {
                // Get all the users that have wishlists
                $user_ids = nmgr_get_users();

                // set the enable_wishlist option to true for each user since they already have active wishlists
                if ($user_ids) {
                    foreach ($user_ids as $user_id) {
                        update_user_meta($user_id, 'nmgr_enable_wishlist', 1);
                    }
                }
            }
        }

        if ($old_value['display_item_purchased_quantity'] != $new_value['display_item_purchased_quantity']) {
            if (!$new_value['display_item_purchased_quantity']) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}nmgr_wishlist_itemmeta SET meta_value = %d WHERE meta_key = %s",
                    0,
                    '_purchased_quantity'
                ));
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}nmgr_wishlist_itemmeta SET meta_value = %s WHERE meta_key = %s",
                    maybe_serialize(array()),
                    '_quantity_reference'
                ));
            }
        }

        if (
            isset($old_value['allow_multiple_wishlists']) &&
            $old_value['allow_multiple_wishlists'] != $new_value['allow_multiple_wishlists']
        ) {
            // if the setting has changed from users having  a single wishlist to having multiple wishlists
            if (!$new_value['allow_multiple_wishlists']) {
                // Get all the users that have wishlists (registered users and guests)
                $meta_rows = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_nmgr_user_id' ORDER BY meta_id DESC ");

                $user_ids_as_keys = wp_list_pluck($meta_rows, 'meta_value', 'meta_value');

                $user_ids_to_post_ids = array_map(function () {
                    return array();
                }, $user_ids_as_keys);

                foreach ($user_ids_to_post_ids as $value => $array) {
                    foreach ($meta_rows as $row) {
                        if ($value == $row->meta_value) {
                            // Add post ids as indexed array
                            $user_ids_to_post_ids[$value][] = $row->post_id;
                        }
                    }
                }

                /**
                 *  @todo check if it is necessary to remove this filter as this code has been refactored
                 * not to use get_posts which was used originally
                 */
                remove_filter('wp_insert_post_data', array('NMGR_Admin_Post', 'insert_post_data'), 10);

                // Delete all wishlists of all users except the most recent
                foreach ($user_ids_to_post_ids as $user_id => $post_ids) {
                    foreach ($post_ids as $index => $post_id) {
                        /**
                         * Post ids are added as indexed array above so we expect the index 0 to exist at least.4
                         * This should also be the lastest post as the posts were retrieved by descending meta_id values above.
                         */
                        if ($index == 0) {
                            if (is_numeric($user_id)) {
                                // Set the latest wishlist as the user's current wishlist if the user is not a guest
                                update_user_meta($user_id, 'nmgr_wishlist_id', $post_id);
                            }
                            continue;
                        }
                        // Trash all the user's other wishlists
                        wp_trash_post($post_id);
                    }
                }

                /**
                 *  @todo check if it is necessary to add this filter as this code has been refactored
                 * not to use get_posts which was used originally
                 */
                add_filter('wp_insert_post_data', array('NMGR_Admin_Post', 'insert_post_data'), 10, 2);
            }
        }
    }

    /**
     * The url of the settings page
     */
    public static function url()
    {
        return admin_url('edit.php?post_type=' . nmgr()->post_type . '&page=' . self::$settings_slug);
    }

    public static function add_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=' . nmgr()->post_type,
            __('Settings', 'nm-gift-registry-lite') . ' - ' . nmgr()->name,
            __('Settings', 'nm-gift-registry-lite'),
            'manage_' . nmgr()->post_type . '_settings',
            self::$settings_slug,
            array(__CLASS__, 'settings_page_content')
        );
    }

    /**
     * Get the default values for all plugin options
     *
     * @return array
     */
    public static function get_default_values()
    {
        $fields = array();

        foreach (array_keys(self::get_tabs()) as $tab) {
            $get_tab_sections = $tab . '_tab_sections';
            if (method_exists(__CLASS__, $get_tab_sections)) {
                $this_section = call_user_func(array(__CLASS__, $get_tab_sections));
                if ($this_section) {
                    foreach ($this_section as $args) {
                        foreach ($args as $args_key => $args_value) {
                            if ('fields' == $args_key) {
                                foreach ($args_value as $value) {
                                    // Key to use to save the value
                                    $option_key = isset($value['option_name']) ? $value['option_name'] : (isset($value['id']) ? $value['id'] : '');
                                    if ($option_key) {
                                        if (isset($value['option_group']) && $value['option_group']) {
                                            $fields[$option_key][] = isset($value['default']) ? $value['default'] : '';
                                        } else {
                                            $fields[$option_key] = isset($value['default']) ? $value['default'] : '';
                                        }

                                        if (is_array($fields[$option_key])) {
                                            $fields[$option_key] = array_filter($fields[$option_key]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Get all the settings tabs registered for the plugin
     *
     * This returns an array of arrays where each array key represents the slug for the
     * settings tab and the array value is an array containing keys:
     * - title - string,  the title of the tab
     * - show_sections, boolean, whether to show the sections all at once (using do_settings_sections)
     *
     * @return array
     */
    public static function get_tabs()
    {
        return apply_filters(nmgr()->option_name . '_tabs', array(
            'general' => array(
                'tab_title' => __('General', 'nm-gift-registry-lite'),
                'sections_title' => '',
                'show_sections' => true,
                'sections' => array(
                    'general' => __('General', 'nm-gift-registry-lite'),
                    'add_to_wishlist' => __('Add to wishlist', 'nm-gift-registry-lite'),
                    'add_to_cart' => __('Add to cart', 'nm-gift-registry-lite'),
                ),
            ),
            'modules' => array(
                'tab_title' => __('Modules', 'nm-gift-registry-lite'),
                'sections_title' => '',
                'show_sections' => true,
                'sections' => array(
                    'profile_form' => __('Profile form', 'nm-gift-registry-lite'),
                    'items_table' => __('Items table', 'nm-gift-registry-lite'),
                    'shipping' => __('Shipping', 'nm-gift-registry-lite'),
                ),
            ),
            'full_version' => array(
                'tab_title' => __('Full Version', 'nm-gift-registry-lite'),
                'sections_title' => '',
                'show_sections' => false,
                'submit_button' => false
            )
        ));
    }

    public static function settings_page_content()
    {
?>
<div class="wrap <?php echo esc_attr(nmgr()->post_type) . " " . esc_attr(self::$current_tab); ?>">
  <form method="post" action="options.php" enctype="multipart/form-data">
    <nav class="nav-tab-wrapper">
      <?php
                    foreach (self::get_tabs() as $slug => $args) {
                        $tab_title = isset($args['tab_title']) ? $args['tab_title'] : $slug;
                        $tab_url = add_query_arg(array(
                            'page' => self::$settings_slug,
                            'tab' => esc_attr($slug)
                        ), nmgr_get_admin_url());

                        echo '<a href="' . esc_html($tab_url) . '" class="nav-tab ' . (self::$current_tab === $slug ? 'nav-tab-active' : '') . '">' . esc_html($tab_title) . '</a>';
                    } ?>
    </nav>

    <?php
                $current_tab_sections = isset(self::get_tabs()[self::$current_tab]['sections']) ? self::get_tabs()[self::$current_tab]['sections'] : null;

                if ($current_tab_sections) :
                ?>
    <ul class="subsubsub">

      <?php
                        $section_keys = array_keys($current_tab_sections);

                        foreach ($current_tab_sections as $key => $label) {
                            $section_url = add_query_arg(array(
                                'page' => self::$settings_slug,
                                'tab' => esc_attr(self::$current_tab),
                                'section' => sanitize_title($key)
                            ), nmgr_get_admin_url());
                            echo '<li><a href="' . esc_html($section_url) . '" class="' . (self::$current_section == $key ? 'current' : '') . '">' . esc_html($label) . '</a> ' . (end($section_keys) == $key ? '' : '|') . ' </li>';
                        } ?>

    </ul><br class="clear" />
    <?php
                endif;

                $args = isset(self::get_tabs()[self::$current_tab]) ? self::get_tabs()[self::$current_tab] : '';

                settings_errors();
                settings_fields(nmgr()->option_name);

                $sections_title = isset($args['sections_title']) ? $args['sections_title'] : null;

                // hack to keep settings_errors() above section titles
                printf(
                    '<h1 style=%s>%s</h1>',
                    empty($sections_title) ? 'display:none;' : '',
                    $sections_title
                );

                if (isset($args['show_sections']) && $args['show_sections']) {
                    $key = !empty(self::$current_section) ? self::$current_section : self::$current_tab;
                    do_settings_sections($key);
                }

                do_action('nmgr_settings_sections_tab_' . self::$current_tab);

                if (
                    !isset($args['submit_button']) ||
                    (isset($args['submit_button']) && $args['submit_button'])
                ) {
                    submit_button();
                } ?>
  </form>
</div>
<?php
    }

    /**
     * Get all the sections that are in a settings tab
     *
     * @param string $tab The tab (Default is current tab)
     * @return array
     */
    public static function get_tab_sections($tab = '')
    {
        $tab = $tab ? $tab : self::$current_tab;
        $get_tab_sections = $tab . '_tab_sections';

        if (method_exists(__CLASS__, $get_tab_sections)) {
            return call_user_func(array(__CLASS__, $get_tab_sections));
        }
    }

    public static function add_settings_fields()
    {
        register_setting(
            nmgr()->option_name,
            nmgr()->option_name,
            array(__CLASS__, 'validate')
        );

        $sections = self::get_tab_sections();
        if (!$sections) {
            return;
        }

        foreach ($sections as $key => $section) {
            $page = isset($section['section']) ? $section['section'] : self::$current_tab;

            add_settings_section(
                $key,
                isset($section['title']) ? $section['title'] : "",
                array(__CLASS__, 'settings_section_callback'),
                $page
            );

            if (!isset($section['fields'])) {
                continue;
            }

            foreach ($section['fields'] as $field) {
                if ('heading' === $field['type']) {
                    $field['id'] = uniqid();
                }

                if (!isset($field['id']) || (isset($field['show_in_group']) && $field['show_in_group'])) {
                    continue;
                }

                add_settings_field(
                    $field['id'],
                    self::get_formatted_settings_field_label($field),
                    array(__CLASS__, 'output_field'),
                    $page,
                    $key,
                    array(
                        'class' => isset($field['type']) && 'heading' === $field['type'] ? 'hidden' : '',
                        'field' => $field,
                        'fields' => $section['fields']
                    )
                );
            }
        }
    }

    /**
     * Format the label of a settings field before display
     * This function is used to add tooltips to the field labels as well as title attributes and error notification colors
     * in situations where the field involved has an error
     *
     * @since 2.0.0
     * @param type $field
     */
    private static function get_formatted_settings_field_label($field)
    {
        if (!isset($field['label'])) {
            return '';
        }

        $label = $field['label'];

        if (isset($field['error_codes']) && self::has_settings_error_code($field['error_codes'])) {
            $title = self::get_settings_error_message_by_code($field['error_codes']);
            $label = '<span class="nmgr-settings-error" title="' . $title . '">' . $label . '</span>';
        }

        if (isset($field['desc_tip'])) {
            $label .= ' ' . wc_help_tip($field['desc_tip']);
        }

        return $label;
    }

    /**
     * Check if particular settings error codes exists if we have errors after saving settings
     *
     * @since 2.0.0
     * @param string|array $code Error code or array of error codes
     * @return boolean
     */
    private static function has_settings_error_code($code)
    {
        foreach (get_settings_errors('nmgr-settings') as $error) {
            if (in_array($error['code'], (array) $code, true)) {
                return true;
            }
        }
        return false;
    }

    private static function get_settings_error_message_by_code($code)
    {
        $message = '';
        foreach (get_settings_errors('nmgr-settings') as $error) {
            if (in_array($error['code'], (array) $code, true)) {
                $message .= $error['message'] . '&#10;';
            }
        }
        return trim($message);
    }

    public static function settings_section_callback($section)
    {
        $tab_sections = self::get_tab_sections();

        if (isset($tab_sections[$section['id']])) {
            if (isset($tab_sections[$section['id']]['description'])) {
                echo "<p class='section-description'>" . esc_html($tab_sections[$section['id']]['description']) . "</p>";
            }
        }
    }

    /**
     * Get the name attribute of a form field based on the arguments supplied to the field
     *
     * @param array $field Arguments supplied to the field
     */
    public static function get_field_name($field)
    {
        if (isset($field['name'])) {
            $name = $field['name'];
        } else {
            $key = isset($field['option_name']) ? $field['option_name'] : (isset($field['id']) ? $field['id'] : '');
            $name = "nmgr_settings[$key]";
        }

        $name = isset($field['option_group']) && $field['option_group'] ? $name . '[]' : $name;
        return $name;
    }

    /**
     * Get the value saved for a field in the database
     *
     * @param array $field Arguments supplied to the field
     */
    public static function get_field_value($field)
    {
        $field_default = isset($field['default']) ? $field['default'] : '';
        $field_id = isset($field['id']) ? $field['id'] : '';

        if (isset($field['option_name'])) {
            $value = nmgr_get_option($field['option_name'], $field_default);
        } elseif (isset($field['name'])) {
            $value = get_option($field['name'], $field_default);
        } else {
            $value = nmgr_get_option($field_id, $field_default);
        }
        return $value;
    }

    /**
     * Adds html checked attribute to a field if it should be checked
     * Should be used for checkboxes, returns empty string otherwise.
     *
     * @param array $field Arguments supplied to the field
     */
    private static function checked($field, $echo = false)
    {
        $stored_value = (array) self::get_field_value($field);
        $field_value = isset($field['value']) ? $field['value'] : 1;

        if (in_array($field_value, $stored_value)) {
            $result = " checked='checked'";
        } else {
            $result = "";
        }

        if ($echo) {
            echo $result;
        }
        return $result;
    }

    /**
     * Adds html selected attribute to a select option if it should be selected
     * Should be used for select option inputs, returns empty string otherwise.
     *
     * @param array $option_value The registered value for the option element
     * @param array $field Arguments supplied to the field
     */
    private static function selected($option_value, $field, $echo = false)
    {
        $stored_value = (array) self::get_field_value($field);

        if (in_array($option_value, $stored_value)) {
            $result = " selected='selected'";
        } else {
            $result = "";
        }

        if ($echo) {
            echo $result;
        }
        return $result;
    }

    public static function output_field($setting)
    {
        $field = $setting['field'];
        $fields = $setting['fields'];

        // Ensure necessary fields are set
        $field_id = isset($field['id']) ? esc_attr($field['id']) : '';
        $field_type = isset($field['type']) ? esc_attr($field['type']) : '';
        $field_desc = isset($field['desc']) ? nmgr_kses_post($field['desc']) : '';
        $field_placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
        $field_class = isset($field['class']) ? esc_attr($field['class']) : '';
        $field_css = isset($field['css']) ? esc_attr($field['css']) : '';
        $field_name = esc_attr(self::get_field_name($field));
        $raw_field_value = self::get_field_value($field);
        $field_value = !is_array($raw_field_value) ? esc_attr($raw_field_value) : $raw_field_value;
        $inline_class = isset($field['inline']) && true === $field['inline'] ? 'nmgr-inline' : '';
        $field_options = isset($field['options']) ? $field['options'] : array();
        $custom_attributes = array();

        if (isset($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $attribute_value) {
                if (false === $attribute_value) {
                    unset($field['custom_attributes'][$attribute]);
                    break;
                }
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }
        $field_custom_attributes = implode(' ', $custom_attributes);

        if (isset($field['show_in_group']) && $field['show_in_group']) {
            return;
        }

        switch ($field_type) {
            case 'heading':
                echo '</td></tr></tbody></table>';
                echo isset($field['label']) && !empty($field['label']) ? "<h2 class='heading'>{$field['label']}</h2>" : '';
                echo !empty($field_desc) ? "<p>{$field_desc}</p>" : '';
                echo '<table class="form-table" role="presentation"><tbody><tr class="hidden"><th></th><td>';
                break;

            case 'text':
            case 'password':
            case 'number':
                printf(
                    "<input type='%s' id='%s' name='%s' size='40' value='%s' placeholder='%s' %s />",
                    $field_type,
                    $field_id,
                    $field_name,
                    $field_value,
                    $field_placeholder,
                    $field_custom_attributes // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                );
                break;

            case 'textarea':
                printf(
                    "<textarea name='%s' cols='45' rows='4' placeholder='%s'>%s</textarea>",
                    $field_name,
                    $field_placeholder,
                    esc_html($field_value)
                );
                break;

            case 'checkbox':
                if (isset($field['checkboxgroup'])) {
                    $group_fields = array_filter($fields, function ($f) use ($field) {
                        return isset($f['checkboxgroup']) && $f['checkboxgroup'] == $field['checkboxgroup'];
                    });

                    if ($group_fields) {
                        foreach ($group_fields as $group_field) {
                            printf(
                                "<label><input %s value='%s' name='%s' type='checkbox' /> %s</label><br />", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                self::checked($group_field),
                                isset($group_field['value']) ? esc_attr($group_field['value']) : 1,
                                esc_attr(self::get_field_name($group_field)),
                                isset($group_field['desc']) ? nmgr_kses_post($group_field['desc']) : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            );
                        }
                    }
                } else {
                    printf(
                        "<label><input %s value='1' name='%s' type='checkbox' %s /> %s</label>", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        self::checked($field),
                        $field_name,
                        $field_custom_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        $field_desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    );
                }
                break;

            case 'radio':
        ?>
<div class="nmgr-input-group <?php echo $inline_class; ?>">
  <?php
                    foreach ($field['options'] as $key => $val) :
                        $checked = checked($key, $field_value, false);
                    ?>
  <div><label><input <?php echo $checked . ' ' . $field_custom_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ?> value="<?php echo $key; ?>" name="<?php echo $field_name; ?>"
        type="radio" /><?php echo $val; ?></label></div>
  <?php endforeach; ?>
</div>
<?php
                break;

            case 'radio_with_image':
            ?>
<div class="nmgr-btn-group nmgr-input-group <?php echo $inline_class; ?>">
  <?php
                    foreach ($field['options'] as $key => $args) :
                        $checked = checked($key, $field_value, false);
                        $option_id = "{$field_id}-{$key}";
                    ?>
  <div class="nmgr-btn">
    <input <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?> id="<?php echo esc_attr($option_id); ?>" type="radio"
      value="<?php echo esc_attr($key); ?>" name="<?php echo $field_name; ?>">
    <label for="<?php echo esc_attr($option_id); ?>"
      title="<?php echo isset($args['label_title']) ? esc_attr($args['label_title']) : ''; ?>" class="nmgr-tip">
      <?php
                                echo isset($args['image']) ? nmgr_kses_post($args['image']) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo isset($args['label']) ? esc_html($args['label']) : '';
                                ?>
    </label>
  </div>
  <?php endforeach; ?>
</div>
<?php
                break;

            case 'select':
                printf(
                    "<select class='%s' name='%s' id='%s' %s>",
                    $field_class,
                    $field_name,
                    $field_id,
                    $field_custom_attributes // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                );
                foreach ($field_options as $key => $val) {
                    printf(
                        "<option value='%s' %s>%s</option>",
                        esc_attr($key),
                        self::selected($key, $field),
                        esc_html($val)
                    );
                }
                echo "</select>";
                break;

            case 'select_page':
                $args = array(
                    'name' => $field_name,
                    'id' => $field_id,
                    'sort_column' => 'menu_order',
                    'sort_order' => 'ASC',
                    'show_option_none' => ' ',
                    'class' => $field_class,
                    'echo' => false,
                    'selected' => absint($field_value),
                );

                if (isset($field['args'])) {
                    $args = wp_parse_args($field['args'], $args);
                }

                echo str_replace(' id=', " data-placeholder='" . $field_placeholder . "' style='" . $field_css . "' class='" . $field_class . "' id=", wp_dropdown_pages($args));
                break;


            default:
                break;
        }

        // These fields should not have description
        $exclude_fields = array('checkbox', 'heading');
        if ($field_desc && !in_array($field_type, $exclude_fields)) {
            echo '<p>' . $field_desc . '</p>';
        }
    }

    // Validate  fields before save
    public static function validate($input)
    {
        $referer = array();
        parse_str(wp_get_referer(), $referer);

        // We're only dealing with fields posted from a particular tab or section
        $tab = self::get_current_tab($referer);
        $section = self::get_current_section($referer);

        $tab_sections = self::get_tab_sections($tab);

        if (!$tab_sections) {
            return $input;
        }

        $fields = array();

        foreach ($tab_sections as $content) {
            foreach ($content as $prop => $value) {
                if ('fields' == $prop) {
                    if ($section) {
                        if (isset($content['section']) && $content['section'] === $section) {
                            $fields = array_merge($fields, $value);
                            break 2;
                        }
                    } else {
                        $fields = array_merge($fields, $value);
                    }
                }
            }
        }

        foreach ($fields as $field) {
            // Key for the field
            $key = isset($field['option_name']) ? $field['option_name'] : (isset($field['id']) ? $field['id'] : '');

            // Get posted value
            $posted_value = isset($input[$key]) ? $input[$key] : null;

            // Format/Sanitize value to save in database
            if (isset($field['type'])) {
                switch ($field['type']) {
                    case 'textarea':
                        $value = nmgr_kses_post(trim($posted_value));
                        break;

                    default:
                        $value = wc_clean($posted_value);
                        break;
                }
            }

            $input[$key] = $value;
        }

        $options = array_merge(nmgr_get_option(), $input);

        if (
            array_key_exists('allow_guest_wishlists', $input) &&
            $input['allow_guest_wishlists'] &&
            isset($options['wishlist_account_page_id']) &&
            !$options['wishlist_account_page_id']
        ) {
            add_settings_error('nmgr-settings', 'no-wishlist-account-page-id', __('You have allowed guests to create wishlists. You need to set a page for managing wishlists.', 'nm-gift-registry-lite'), 'warning');
        }

        if (
            array_key_exists('my_account_name', $input) && empty($input['my_account_name']) &&
            isset($options['wishlist_account_page_id']) && !$options['wishlist_account_page_id']
        ) {
            add_settings_error('nmgr-settings', 'no-my-account-name', __('You have not set the title of the page for managing wishlists in the my-account page or a page for managing wishlists. You need to set one or both of these to allow your users manage their wishlists, except if you know what you are doing.', 'nm-gift-registry-lite'), 'warning');
        }

        if (get_settings_errors('nmgr-settings')) {
            add_settings_error('nmgr-settings', 'settings-saved', __('Settings saved.', 'nm-gift-registry-lite'), 'success');
        }

        return $options;
    }

    public static function get_profile_form_fields()
    {
        $settings_fields = array();
        $form = new NMGR_Form();
        $fields = $form->get_fields('profile', '', false, false);

        foreach ($fields as $key => $args) {
            if ('title' === $key) {
                continue;
            }

            $composed_field = array(
                'id' => "display_form_{$key}",
                'label' => isset($args['label']) ? $args['label'] : '',
                'type' => 'select',
                'options' => array(
                    'optional' => __('Optional', 'nm-gift-registry-lite'),
                    'required' => __('Required', 'nm-gift-registry-lite'),
                    'no' => __('Hidden', 'nm-gift-registry-lite'),
                ),
                'default' => 'optional',
            );

            $settings_fields[] = $composed_field;
        }

        return $settings_fields;
    }

    /**
     * Default columns on wishlist items table
     *
     * (We're hardcoding this for now)
     */
    public static function get_items_table_columns()
    {
        $settings = array();

        foreach (nmgr_items_table_columns() as $key => $value) {
            $args = array(
                'id' => "display_item_{$key}",
                'label' => $value,
                'type' => 'checkbox',
                'default' => 1,
            );

            switch ($key) {
                case 'quantity':
                    $args['desc_tip'] = __('Unchecking this column would disable all functionality related to the quantities of wishlist items. For example it would prevent the regulation of the quantities of items added to the wishlist and cart. Please see the documentation for full explanation.', 'nm-gift-registry-lite');
                    break;

                case 'purchased_quantity':
                    $args['desc_tip'] = __('Unchecking this column would disable all functionality related to the purchasing of wishlist items. It would also reset the purchased quantities of all items in all existing wishlists to zero. Please see the documentation for full explanation.', 'nm-gift-registry-lite');
                    break;

                case 'total_cost':
                    $args['desc_tip'] = __('The visibility of this column depends on the "quantity" column being visible.', 'nm-gift-registry-lite');
                    $args['custom_attributes']['disabled'] = nmgr_get_option('display_item_quantity', 1) ? false : 'disabled';
                    break;

                case 'actions':
                    $args['desc_tip'] = __('This column allows action such as add to cart, edit, and delete to be performed on the wishlist items from the frontend. Unchecking it would prevent these actions from being able to be performed from the frontend.', 'nm-gift-registry-lite');
                    break;
            }

            $settings[] = $args;
        }
        return apply_filters('nmgr_settings_items_table_columns', $settings);
    }

    public static function general_tab_sections()
    {
        $sections = array();

        $sections['general'] = array(
            'title' => '',
            'description' => '',
            'section' => 'general',
            'fields' => array(
                array(
                    'id' => 'type',
                    'label' => __('Wishlist type', 'nm-gift-registry-lite'),
                    'type' => 'select',
                    'options' => array(
                        'gift_registry' => __('Gift Registry', 'nm-gift-registry-lite'),
                        'wishlist' => __('Wishlist', 'nm-gift-registry-lite'),
                    ),
                    'default' => 'gift_registry',
                    'desc_tip' => __('This setting is simply a quick one-click way to configure the plugin to be used either as a gift registry or wishlist. It is not necessary to use it as you can easily configure the plugin on a setting-by-setting basis. Note that changing this setting would change some of your existing plugin settings where applicable.', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'type_title',
                    'label' => __('Wishlist type title', 'nm-gift-registry-lite'),
                    'type' => 'select',
                    'options' => array_map(function ($v) {
                        return esc_html(ucwords($v));
                    }, wp_list_pluck(nmgr_get_type_titles(), 'singular')),
                    'default' => 'gift_registry',
                    'desc_tip' => __('The text used to describe the wishlist type on the frontend.', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'permalink_base',
                    'label' => __('Frontend permalink base for viewing wishlists', 'nm-gift-registry-lite'),
                    'type' => 'text',
                    'default' => 'nm-gift-registries',
                    'desc_tip' => sprintf(
                        /* translators: 1: site url, 2: plugin permalink_base option value */
                        __('This would allow users view a wishlist at a url like %1$s/%2$s/sample-wishlist/. It is best to set this only once to keep the name consistent for users.', 'nm-gift-registry-lite'),
                        get_bloginfo('url'),
                        nmgr_get_option('permalink_base', 'nm-gift-registries')
                    ),
                ),
                array(
                    'id' => 'single_wishlist_template',
                    'label' => __('Page to use as template for viewing single wishlists', 'nm-gift-registry-lite'),
                    'type' => 'select_page',
                    'default' => '',
                    'class' => 'wc-enhanced-select-nostd',
                    'css' => 'min-width:300px;',
                    'placeholder' => esc_attr__('None', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Use this if the default template provided by the plugin conflicts with your theme layout or if you just want to use the layout of a page as the template.', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'search_results_template',
                    'label' => __('Page to use as template for viewing wishlist search results', 'nm-gift-registry-lite'),
                    'type' => 'select_page',
                    'default' => '',
                    'class' => 'wc-enhanced-select-nostd',
                    'css' => 'min-width:300px;',
                    'placeholder' => esc_attr__('None', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Use this if the default template provided by the plugin conflicts with your theme layout or if you just want to use the layout of a page as the template.', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'wishlist_account_page_id',
                    'label' => __('Page for managing wishlists', 'nm-gift-registry-lite'),
                    'type' => 'select_page',
                    'default' => '',
                    'class' => 'wc-enhanced-select-nostd',
                    'css' => 'min-width:300px;',
                    'placeholder' => esc_attr__('None', 'nm-gift-registry-lite'),
                    'desc' => __('must contain the <code>[nmgr_account]</code> shortcode', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Set this page if you are using a custom page for managing wishlists instead of the default woocommerce my-account page for logged-in users. This page must be set if guests are allowed to create wishlists as it would be the location where they manage their wishlists. If set, note that logged in users can also manage their wishlists here. This page must contain the <code>[nmgr_account]</code> shortcode.', 'nm-gift-registry-lite'),
                    'error_codes' => array(
                        'no-wishlist-account-page-id',
                        'no-my-account-name'
                    ),
                ),
                array(
                    'id' => 'my_account_name',
                    'label' => __('Title of page for managing wishlists in "my-account" page', 'nm-gift-registry-lite'),
                    'type' => 'text',
                    'default' => __('Gift Registry', 'nm-gift-registry-lite'),
                    'desc_tip' => sprintf(
                        /* translators:
                         * 1: woocommerce account page url,
                         * 2: plugin wishlist account page slug,
                         * 3: plugin wishlist account page name
                         */
                        __('This is for logged-in users only. It covers the menu title, page title and slug for the wishlist management page on the my-account page. With this you can manage your wishlist at %1$s%2$s/, and the title of the page would be "%3$s". It is best to set this only once to keep the name consistent for users. Leave empty if you do not want to manage wishlists in the my-account page, for example when you want to manage wishlists only through a custom page.', 'nm-gift-registry-lite'),
                        get_permalink(get_option('woocommerce_myaccount_page_id')),
                        nmgr_get_account_details('slug'),
                        nmgr_get_account_details('name')
                    ),
                    'error_codes' => array(
                        'no-my-account-name'
                    ),
                ),
                array(
                    'label' => __('Allow users to enable the wishlist module for use on an individual basis', 'nm-gift-registry-lite'),
                    'id' => 'user_enable_wishlist',
                    'default' => '',
                    'type' => 'checkbox',
                    'desc' => __('If not checked, the wishlist module would be enabled for all users by default', 'nm-gift-registry-lite'),
                    'desc_tip' => __('If checked the wishlist module can be enabled by the user from his my-account dashboard page by default.', 'nm-gift-registry-lite'),
                ),
                array(
                    'label' => __('Sharing on social networks', 'nm-gift-registry-lite'),
                    'id' => 'share_on_facebook',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Allow sharing on Facebook', 'nm-gift-registry-lite'),
                    'checkboxgroup' => 'social_share',
                ),
                array(
                    'label' => '',
                    'id' => 'share_on_twitter',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Allow sharing on Twitter', 'nm-gift-registry-lite'),
                    'checkboxgroup' => 'social_share',
                    'show_in_group' => true,
                ),
                array(
                    'label' => '',
                    'id' => 'share_on_pinterest',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Allow sharing on Pinterest', 'nm-gift-registry-lite'),
                    'checkboxgroup' => 'social_share',
                    'show_in_group' => true,
                ),
                array(
                    'label' => '',
                    'id' => 'share_on_email',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Allow sharing on Email', 'nm-gift-registry-lite'),
                    'checkboxgroup' => 'social_share',
                    'show_in_group' => true,
                ),
                array(
                    'label' => '',
                    'id' => 'enable_single_sharing',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Show sharing buttons on single wishlist page', 'nm-gift-registry-lite'),
                    'checkboxgroup' => 'social_share',
                    'show_in_group' => true,
                ),
                array(
                    'label' => __('Allow guests to create wishlists', 'nm-gift-registry-lite'),
                    'id' => 'allow_guest_wishlists',
                    'default' => '',
                    'type' => 'checkbox',
                    'desc' => __('Guests can create and manage wishlists just like logged in users', 'nm-gift-registry-lite'),
                    'desc_tip' => __('If checked you must set the page for managing wishlists where guests can create and manage their wishlists.', 'nm-gift-registry-lite'),
                ),
                array(
                    'label' => __('Number of days to keep guest wishlists', 'nm-gift-registry-lite'),
                    'id' => 'guest_wishlist_expiry_days',
                    'default' => 365,
                    'type' => 'number',
                    'desc_tip' => __('The maximum number of days allowed is 365. If set to 0 or left empty, guest wishlists would expire when the browser closes.', 'nm-gift-registry-lite'),
                    'custom_attributes' => array(
                        'max' => 365,
                        'min' => 0
                    )
                ),
            )
        );

        $sections['add_to_wishlist'] = array(
            'title' => '',
            'description' => '',
            'section' => 'add_to_wishlist',
            'fields' => array(
                array(
                    'type' => 'heading',
                    'label' => __('Button display options', 'nm-gift-registry-lite'),
                    'desc' => __('Set the display options for the add to wishlist button.', 'nm-gift-registry-lite')
                ),
                array(
                    'id' => 'add_to_wishlist_button_type',
                    'label' => __('Display type', 'nm-gift-registry-lite'),
                    'type' => 'radio_with_image',
                    'inline' => true,
                    'default' => 'button',
                    'options' => array(
                        'button' => array(
                            'label' => __('Button', 'nm-gift-registry-lite'),
                            'image' => sprintf(
                                '<div class="button" disabled>%s</div>',
                                __('Add to wishlist', 'nm-gift-registry-lite')
                            ),
                            'label_title' => __('Use a standard button.', 'nm-gift-registry-lite'),
                        ),
                        'icon-heart' => array(
                            'label' => __('Icon', 'nm-gift-registry-lite'),
                            'label_title' => __('Use the heart icon.', 'nm-gift-registry-lite'),
                            'image' => nmgr_get_svg(array(
                                'icon' => 'heart-empty',
                                'size' => 2,
                                'fill' => '#ccc',
                            )),
                        ),
                    ),
                ),
                array(
                    'id' => 'add_to_wishlist_button_position_archive',
                    'label' => __('Display position on archive pages', 'nm-gift-registry-lite'),
                    'type' => 'select',
                    'default' => 'woocommerce_after_shop_loop_item',
                    'options' => array(
                        'woocommerce_before_shop_loop_item' => __('Before thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_top_left' => __('Top left of thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_top_right' => __('Top right of thumbnail', 'nm-gift-registry-lite'),
                        'woocommerce_before_shop_loop_item_title' => __('Before title', 'nm-gift-registry-lite'),
                        'woocommerce_shop_loop_item_title' => __('After title', 'nm-gift-registry-lite'),
                        'woocommerce_after_shop_loop_item_title' => __('After price', 'nm-gift-registry-lite'),
                        'woocommerce_after_shop_loop_item' => __('After add to cart button', 'nm-gift-registry-lite'),
                    ),
                ),
                array(
                    'id' => 'add_to_wishlist_button_position_single',
                    'label' => __('Display position on single pages', 'nm-gift-registry-lite'),
                    'type' => 'select',
                    'default' => 35,
                    'options' => array(
                        'woocommerce_before_single_product_summary' => __('Before thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_top_left' => __('Top left of thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_top_right' => __('Top right of thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_bottom_left' => __('Bottom left of thumbnail', 'nm-gift-registry-lite'),
                        'thumbnail_bottom_right' => __('Bottom right of thumbnail', 'nm-gift-registry-lite'),
                        1 => __('Before title', 'nm-gift-registry-lite'),
                        6 => __('After title', 'nm-gift-registry-lite'),
                        15 => __('After price', 'nm-gift-registry-lite'),
                        25 => __('After excerpt', 'nm-gift-registry-lite'),
                        35 => __('After add to cart button', 'nm-gift-registry-lite'),
                        45 => __('After meta information', 'nm-gift-registry-lite'),
                    ),
                ),
                array(
                    'type' => 'heading',
                    'label' => __('Additional options', 'nm-gift-registry-lite'),
                    'desc' => __('Set up additional add to wishlist options', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'default_wishlist_title',
                    'label' => __('Create a default wishlist automatically for users without any wishlist. Enter title here', 'nm-gift-registry-lite'),
                    'type' => 'text',
                    'default' => '{wishlist_type_title} #{wishlist_id}',
                    'placeholder' => 'e.g. {wishlist_type_title} #{wishlist_id}',
                    'desc_tip' => __('Available placeholders: {wishlist_type_title}, {wishlist_id}, {site_title}. Leave empty if you want users without a wishlist to create one themselves.', 'nm-gift-registry-lite'),
                ),
                array(
                    'id' => 'add_to_wishlist_button_text',
                    'label' => __('Add to wishlist button text', 'nm-gift-registry-lite'),
                    'type' => 'text',
                    'default' => sprintf(
                        /* translators: %s: wishlist type title */
                        __('Add to %s', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ),
                    'placeholder' => sprintf(
                        /* translators: %s: wishlist type title */
                        __('Add to %s', 'nm-gift-registry-lite'),
                        esc_html(nmgr_get_type_title())
                    ),
                ),
                array(
                    'id' => 'add_to_wishlist_guests',
                    'default' => 1,
                    'type' => 'checkbox',
                    'label' => __('Keep actions visible for guests', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Allow the add to wishlist button, wishlist cart, and other wishlist-related actions to be kept visible for guests if they are not allowed to create wishlists. In such case they would have to login to actually add products to their wishlist. ', 'nm-gift-registry-lite'),
                ),
            ),
        );

        $sections['add_to_cart'] = array(
            'title' => '',
            'description' => '',
            'section' => 'add_to_cart',
            'fields' => array(
                array(
                    'label' => __('Identify wishlist items in cart and checkout page', 'nm-gift-registry-lite'),
                    'id' => 'show_cart_item',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Show notification in the cart and checkout page identifying the wishlist item', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Even if the notification is hidden, the plugin is still aware that there are wishlist items in the cart.', 'nm-gift-registry-lite'),
                ),
                array(
                    'label' => __('Identify wishlist items in order after checkout', 'nm-gift-registry-lite'),
                    'id' => 'show_order_item',
                    'default' => 1,
                    'type' => 'checkbox',
                    'desc' => __('Show notification in the order details page after checkout identifying the wishlist item', 'nm-gift-registry-lite'),
                    'desc_tip' => __('Even if the notification is hidden, the plugin is still aware that there are wishlist items in the order.', 'nm-gift-registry-lite'),
                ),
            ),
        );

        return $sections;
    }

    public static function modules_tab_sections()
    {
        $modules_tab_sections = array();

        $modules_tab_sections['profile_form'] = array(
            'title' => __('Wishlist profile form fields', 'nm-gift-registry-lite'),
            'description' => __('Set the display type for the wishlist profile form fields.', 'nm-gift-registry-lite'),
            'section' => 'profile_form',
            'fields' => self::get_profile_form_fields(),
        );

        $modules_tab_sections['items_table'] = array(
            'title' => __('Wishlist items table', 'nm-gift-registry-lite'),
            'description' => __('Set the visibility of default columns on the items table. For some of these columns their visibility controls related plugin functionality so changing it may adjust how the plugin works. Please see the documentation for full explanation.', 'nm-gift-registry-lite'),
            'section' => 'items_table',
            'fields' => array_merge(
                self::get_items_table_columns(),
                array(
                    array(
                        'id' => 'hide_fulfilled_items',
                        'label' => __('Hide fulfilled items from the table on the single wishlist page', 'nm-gift-registry-lite'),
                        'type' => 'checkbox',
                        'default' => '',
                        'desc_tip' => __('Fulfilled items are items which have all their desired quantities purchased. This setting only works if the "quantity" and "purchased quantity" columns are visible on the items table.', 'nm-gift-registry-lite'),
                        'custom_attributes' => array(
                            'disabled' => !nmgr_get_option('display_item_quantity') || !nmgr_get_option('display_item_purchased_quantity') ? true : false,
                        ),
                    ),
                )
            ),
        );

        $modules_tab_sections['shipping_section'] = array(
            'title' => __('Wishlist shipping', 'nm-gift-registry-lite'),
            'description' => __('Configure the wishlist shipping module', 'nm-gift-registry-lite'),
            'section' => 'shipping',
            'fields' => array(
                array(
                    'id' => 'enable_shipping',
                    'label' => __('Enable', 'nm-gift-registry-lite'),
                    'type' => 'checkbox',
                    'default' => 1,
                ),
                array(
                    'id' => 'shipping_address_required',
                    'label' => __('Make shipping address required', 'nm-gift-registry-lite'),
                    'type' => 'checkbox',
                    'default' => '',
                    'desc' => __('If checked the user would have to fill in his shipping address before he can add items to his wishlist.', 'nm-gift-registry-lite'),
                    'custom_attributes' => array(
                        'disabled' => nmgr_get_option('enable_shipping', 1) ? false : 'disabled',
                    ),
                ),
            ),
        );

        return $modules_tab_sections;
    }

    public static function do_settings_sections_full_version()
    {
        $features = array(
            array(
                'title' => __('Multiple wishlists', 'nm-gift-registry-lite'),
                'desc' => __('Allow users to have as many gift registries or wishlists as they want, each with it\'s own custom management page and settings on the frontend.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'database',
                    'size' => 6,
                    'fill' => '#da5027',
                    'sprite' => false
                )),
            ),
            array(
                'title' => __('Wishlist lifecycle emails', 'nm-gift-registry-lite'),
                'desc' => __('Allow the plugin to send emails to custom recipients and the list owner when the list is created, fulfilled or deleted, when items have been ordered, purchased or refunded from the list, and when messages are sent to the list owner at checkout.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'email',
                    'size' => 6,
                    'fill' => 'blue'
                )),
            ),
            array(
                'title' => __('Featured and background images', 'nm-gift-registry-lite'),
                'desc' => __('Allow list owners to upload and display featured and background images on their list pages and on social media to add a personalised touch and improve the impressions made on guests.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'camera',
                    'size' => 6,
                    'fill' => 'lawngreen'
                )),
            ),
            array(
                'title' => __('Checkout messages', 'nm-gift-registry-lite'),
                'desc' => __('Allow guests to send messages to the list owner from the checkout page as items are ordered for him. These message appear in the list\'s management page and can be configured to appear in the owner\'s inbox as well as in other emails sent to him.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'bubble',
                    'size' => 6,
                    'fill' => 'aqua'
                )),
            ),
            array(
                'title' => __('Wishlist visibility settings', 'nm-gift-registry-lite'),
                'desc' => __('Allow list owners to set the visibility of their lists to private, password protected or public from the list management page on the frontend. These visibilities correspond to WordPress\' visibility settings.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'gear',
                    'size' => 6,
                    'fill' => 'purple'
                )),
            ),
            array(
                'title' => __('Exclude wishlists from search', 'nm-gift-registry-lite'),
                'desc' => __('Allow list owners to exclude individual lists from search results regardless of their visibility. This gives them improved control over how their lists appear on the website.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'search',
                    'size' => 6,
                    'sprite' => false,
                    'fill' => 'yellow'
                )),
            ),
            array(
                'title' => __('Mark wishlist items as favourite', 'nm-gift-registry-lite'),
                'desc' => __('Allow list owners to mark an item as favourite when adding it to their list. The favourite status of items can be edited in the list management page and items can be sorted in the items table by their favourite status.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'star-full',
                    'size' => 6,
                    'fill' => 'red'
                )),
            ),
            array(
                'title' => __('Extra add to wishlist customizations', 'nm-gift-registry-lite'),
                'desc' => __('Customize the add to wishlist button to your liking. Select new display types, toggle the use of ajax for the action, the animation and display of notifications, the ability to choose the quantity and favourite status when adding a product to the list, whether variable and grouped products can be added to the list. Include or exclude products and product categories from being added to the wishlist, and much more.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'wrench',
                    'size' => 6,
                    'sprite' => false,
                    'fill' => 'orange'
                )),
            ),
            array(
                'title' => __('One-click wishlist templates customization', 'nm-gift-registry-lite'),
                'desc' => __('Customize the list templates from the admin area with the click of a button without writing any code. Toggle the visibility of default account tabs, the visibility and required status of the settings fields, the visibility and display type of the featured and background images, the messages module, and much more.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'control-panel',
                    'size' => 6,
                    'sprite' => false,
                    'fill' => '#1d78c3'
                )),
            ),
            array(
                'title' => __('Delete wishlist', 'nm-gift-registry-lite'),
                'desc' => __('Give list owners the ability to delete their lists from the frontend without having to contact the admin or leave it dormant to clog up your database. This gives them more control over their lists and leads to a cleaner site for the administrator.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'trash-can',
                    'size' => 6,
                    'fill' => 'brown'
                )),
            ),
            array(
                'title' => __('Customize wishlist shipping', 'nm-gift-registry-lite'),
                'desc' => __('Control the shipping methods available for wishlist items in the cart. Calculate shipping rates for the wishlist items separately from normal items. Ship wishlist items in cart to the wishlist\'s owner\'s address. And much more.', 'nm-gift-registry-lite'),
                'image' => nmgr_get_svg(array(
                    'icon' => 'box-open',
                    'size' => 6,
                    'fill' => 'orangered'
                )),
            ),
        ); ?>
<div class="nmgr-full-version">
  <div class="nmgr-text-center">
    <a class="nmgr-buy-btn" href="https://nmgiftregistry.com/product/nm-gift-registry" rel="noopener noreferrer"
      target="_blank"><?php esc_html_e('Upgrade Now', 'nm-gift-registry-lite'); ?></a>
  </div>

  <h1 class="nmgr-text-center"><?php esc_html_e('NM Gift Registry and Wishlist Features', 'nm-gift-registry-lite'); ?>
  </h1>
  <p class="nmgr-desc nmgr-text-center">
    <?php esc_html_e('Check out these fantastic extra features that can provide your store with the perfect gift registry and wishlist experience.', 'nm-gift-registry-lite'); ?>
  </p>
  <div class="nmgr-features">
    <?php foreach ($features as $feature) : ?>
    <div class="nmgr-feature">
      <div class="nmgr-image">
        <?php echo $feature['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?></div>
      <div class="nmgr-info">
        <h2><?php echo esc_html($feature['title']); ?></h2>
        <p><?php echo wp_kses_post($feature['desc']); ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="nmgr-text-center">
    <a class="nmgr-buy-btn" href="https://nmgiftregistry.com/product/nm-gift-registry" rel="noopener noreferrer"
      target="_blank"><?php esc_html_e('Upgrade Now', 'nm-gift-registry-lite'); ?></a>
  </div>
</div>
<?php
    }
}