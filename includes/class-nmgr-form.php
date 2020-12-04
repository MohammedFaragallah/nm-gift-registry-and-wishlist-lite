<?php

defined('ABSPATH') || exit;

/**
 * Handles generation, sanitization, validation and saving of wishlist form fields
 */
class NMGR_Form
{

    /**
     * Wishlist object passed to the form
     *
     * @var NMGR_Wishlist
     */
    protected $wishlist;

    /**
     * Current form fields being worked with
     *
     * @var array
     */
    protected $data = array();

    /**
     * Instance of WP_Error class
     *
     * @var object WP_Error
     */
    public $error;

    /**
     * The number of fields being retrieved from the form
     *
     * (Typically used when get_fields() is called directly or indirectly to enable
     * the caller know the number of fields returned based on those excluded)
     *
     * @var int
     */
    public $fields_count = 0;

    public static function run()
    {
        add_filter('nmgr_fields', array( __CLASS__, 'modify_form_fields' ), 10);
        add_action('woocommerce_form_field', array( __CLASS__, 'remove_optional_required_html' ), 10, 3);
        add_filter('woocommerce_form_field_nmgr-hidden', array( __CLASS__, 'create_hidden_field' ), 10, 4);
    }

    public function __construct($wishlist_id = 0)
    {
        $wishlist = nmgr_get_wishlist($wishlist_id, true);
        $this->wishlist = $wishlist ? $wishlist : new NMGR_Wishlist();
    }

    /**
     * Add new hidden field to woocommerce_form_field function
     */
    public static function create_hidden_field($field, $key, $args, $value)
    {
        $field = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        return $field;
    }

    /**
     * Remove the 'optional' html from all optional fields
     * and replace the 'required' html with something we can use with tooltips
     */
    public static function remove_optional_required_html($field, $key, $args)
    {
        if (false !== strpos($key, nmgr()->prefix) || isset($args[ 'prefix' ])) {
            $field = str_replace('<span class="optional">(optional)</span>', '', $field);
        }

        if (false !== strpos($key, nmgr()->prefix) || isset($args[ 'prefix' ])) {
            $field = str_replace('<abbr class="required', '<abbr class="nmgr-tip required', $field);
        }

        return $field;
    }

    /**
     * Modify form fields before output
     *
     * @param array $fields Form fields
     * @return array Modified form fields
     */
    public static function modify_form_fields($fields)
    {
        if (!is_ajax() && is_admin()) {
            return $fields;
        }

        foreach ($fields as $field => $args) {
            /**
             * Add html5 required attribute to fields if required
             *
             * Let's not do this for woocommerce's shipping fields to
             * prevent html5 focussable error being generated when field is hidden
             */
            if (false === strpos($field, 'shipping_') && isset($args[ 'required' ]) && $args[ 'required' ]) {
                $fields[ $field ][ 'custom_attributes' ][ 'required' ] = true;
            }

            switch ($field) {
                case 'nmgr_first_name':
                    if (isset($fields[ 'nmgr_last_name' ])) {
                        $fields[ $field ][ 'class' ] = array( 'form-row-first' );
                    }
                    break;
                case 'nmgr_last_name':
                    if (isset($fields[ 'nmgr_first_name' ])) {
                        $fields[ $field ][ 'class' ] = array( 'form-row-last' );
                    }
                    break;
                case 'nmgr_partner_first_name':
                    if (isset($fields[ 'nmgr_partner_last_name' ])) {
                        $fields[ $field ][ 'class' ] = array( 'form-row-first' );
                    }
                    break;
                case 'nmgr_partner_last_name':
                    if (isset($fields[ 'nmgr_partner_first_name' ])) {
                        $fields[ $field ][ 'class' ] = array( 'form-row-last' );
                    }
                    break;
                case 'nmgr_email':
                    if (isset($fields[ $field ])) {
                        $fields[ $field ][ 'class' ] = array( 'form-row-wide' );
                    }
                    break;
            }
        }
        return $fields;
    }

    /**
     * Whether the form retrieved any fields
     *
     * This function is typically used after get_fields() has been called directly, or
     * indirectly using get_fields_html(), to determine whether any fields were actually
     * returned based on those that should be excluded.
     *
     * @return boolean
     */
    public function has_fields()
    {
        return ( bool ) $this->fields_count;
    }

    /**
     * Get the wishlist object the form is working with
     *
     * @return NMGR_Wishlist
     */
    public function get_wishlist()
    {
        return $this->wishlist;
    }

    /**
     * Get the value stored for a form field in the wishlist object
     *
     * @param string $key The name of the form field
     * @return mixed The value or null
     */
    public function get_wishlist_value($key)
    {
        if (is_callable(array( $this->wishlist, "get_$key" ))) {
            $value = $this->wishlist->{"get_$key"}();
        } else {
            $value = $this->wishlist->get_prop($key);
        }

        return $value ? $value : null;
    }

    /**
     * Set the default values of form fields
     *
     * @param array $fields
     */
    public function set_defaults($fields)
    {
        // Don't set defaults in admin area
        if (is_nmgr_admin_request()) {
            return $fields;
        }

        $user = wp_get_current_user();
        $postmeta = is_a($this->wishlist, 'NMGR_Wishlist') ? get_post_meta($this->wishlist->get_id()) : array();

        foreach ($fields as $key => $args) {
            if (isset($args[ 'default' ])) {
                continue;
            }

            switch ($key) {
                case 'first_name':
                    if (!isset($postmeta[ '_first_name' ])) {
                        $fields[ $key ][ 'default' ] = $user->first_name;
                    }
                    break;
                case 'last_name':
                    if (!isset($postmeta[ '_last_name' ])) {
                        $fields[ $key ][ 'default' ] = $user->last_name;
                    }
                    break;
                case 'email':
                    if (!isset($postmeta[ '_email' ])) {
                        $fields[ $key ][ 'default' ] = $user->user_email;
                    }
                    break;
            }
        }
        return $fields;
    }

    /**
     * Set the value of form fields
     *
     * @param array $fields Form fields
     */
    public function set_values($fields)
    {
        foreach ($fields as $key => $args) {
            if (isset($args[ 'value' ])) {
                continue;
            }

            $value = $this->get_wishlist_value($key);

            // Process certain fields differently (typically checkbox fields)
            switch ($key) {
                case 'ship_to_account_address':
                    $value = ( int ) $this->wishlist->is_shipping_to_account_address();
                    break;
            }

            $fields[ $key ][ 'value' ] = $value;
        }

        return $fields;
    }

    /**
     * Add the plugin prefix to fields keys
     *
     * All field keys should have the prefix if not present
     * Prefix is not added to fields that have $args['prefix'] set to false
     *
     * @param array $fields Form fields
     */
    public function add_prefix($fields)
    {
        $prefixed = array();
        foreach ($fields as $name => $args) {
            if ((isset($args[ 'prefix' ]) && !$args[ 'prefix' ]) || false !== strpos($name, nmgr()->prefix)) {
                $prefixed[ $name ] = $args;
                continue;
            }
            $prefixed[ nmgr()->prefix . $name ] = $args;
        }
        return $prefixed;
    }

    /**
     * Remove plugin prefix from supplied fields keys
     * (This is usually necessary to prepare the fields for saving in the database)
     *
     * @param array $fields Fields to remove prefix from
     * @return array Fields with prefix removed from keys
     */
    public function remove_prefix($fields = '')
    {
        $the_fields = $fields ? $fields : $this->data;
        $original_fields = $this->get_fields('', '', false);
        $unprefixed = array();

        foreach ($the_fields as $name => $v) {
            if (!isset($original_fields[ $name ]) ||
                (isset($original_fields[ $name ]) && isset($original_fields[ $name ][ 'prefix' ]) && !$original_fields[ $name ][ 'prefix' ])) {
                $unprefixed[ $name ] = $v;
                continue;
            }

            $unprefixed[ str_replace(nmgr()->prefix, '', $name) ] = $v;
        }

        $this->data = $unprefixed;
        return $this;
    }

    /**
     * Get the error object
     *
     * @deprecated 2.0.0. Use ->error instead
     * @return WP_Error
     */
    public function get_error()
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'NMGR_Form->error');
        return $this->error;
    }

    /**
     * Whether there were errors in form validation
     *
     * @return boolean
     */
    public function has_errors()
    {
        return !empty($this->error->errors) ? true : false;
    }

    /**
     * Get error messages from form validation
     *
     * @return array Array of error messages
     */
    public function get_error_messages()
    {
        return $this->error->get_error_messages();
    }

    /**
     * Set the current fields being worked with
     *
     * @param array $fields The fields to set as the current fields
     * 									(This could be posted data from a form)
     * @param bool $registered_only Set only fields registered in the form
     * 												within the given fields as the current fields
     *
     * @todo Remove $registered_only parameter as it is not necessary
     *
     */
    public function set_data($fields, $registered_only = false)
    {
        $this->data = !$registered_only ? $fields : array_intersect_key($fields, $this->get_fields('', '', false));
        return $this;
    }

    /**
     * Get the current fields being worked with
     *
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }

    /**
     * Get form fields
     *
     * @param string|array $fieldset Name of the fieldset to get fields for
     * or array of field keys to get fields for. If not provided, this defaults to all fields
     *
     * A fieldset comprises fields which have the same $args['fieldset'] value
     * It categories form fields into the same group.
     *
     * @param array $ignore Fields to ignore from the fieldset if the name of
     * the fieldset is provided as a string or empty in $fieldset
     *
     * @param bool $exclude_hidden Whether to exclude fields that should not be displayed,
     * based on the admin defined plugin settings. Default true.
     *
     * @param bool $prefix Whether to add prefix to the fields. Default true. @since 1.2.0.
     *
     * @return array
     */
    public function get_fields($fieldset = '', $ignore = array(), $exclude_hidden = true, $prefix = true)
    {
        $fields = $requested_fields = array();

        $fields[ 'title' ] = array(
            'label' => __('Title', 'nm-gift-registry-lite'),
            'placeholder' => sprintf(
                /* translators: %s: wishlist type title */
                __('%s title', 'nm-gift-registry-lite'),
                nmgr_get_type_title('cf')
            ),
            'required' => true,
            'fieldset' => 'profile'
        );

        $fields[ 'event_date' ] = array(
            'label' => __('Event Date', 'nm-gift-registry-lite'),
            'autocomplete' => 'off',
            'placeholder' => __('Event date', 'nm-gift-registry-lite'),
            'custom_attributes' => array(
                'autocomplete' => 'off',
            ),
            'validate' => array( 'date' ),
            'fieldset' => 'profile'
        );

        $fields[ 'description' ] = array(
            'type' => 'textarea',
            'label' => __('Description', 'nm-gift-registry-lite'),
            'placeholder' => sprintf(
                /* translators: %s: wishlist type title */
                __('Describe your %s here, or write a message to your guests', 'nm-gift-registry-lite'),
                nmgr_get_type_title()
            ),
            'fieldset' => 'profile'
        );

        $fields[ 'first_name' ] = array(
            'label' => __('First Name', 'nm-gift-registry-lite'),
            'placeholder' => __('First name', 'nm-gift-registry-lite'),
            'autocomplete' => 'given-name',
            'fieldset' => 'profile'
        );

        $fields[ 'last_name' ] = array(
            'label' => __('Last Name', 'nm-gift-registry-lite'),
            'placeholder' => __('Last name', 'nm-gift-registry-lite'),
            'autocomplete' => 'family-name',
            'fieldset' => 'profile'
        );

        $fields[ 'partner_first_name' ] = array(
            'label' => __('Partner First Name', 'nm-gift-registry-lite'),
            'placeholder' => __('Partner first name', 'nm-gift-registry-lite'),
            'fieldset' => 'profile',
        );

        $fields[ 'partner_last_name' ] = array(
            'label' => __('Partner Last Name', 'nm-gift-registry-lite'),
            'placeholder' => __('Partner last name', 'nm-gift-registry-lite'),
            'fieldset' => 'profile',
        );

        $fields[ 'email' ] = array(
            'type' => 'email',
            'label' => __('Email', 'nm-gift-registry-lite'),
            'placeholder' => __('Email', 'nm-gift-registry-lite'),
            'autocomplete' => 'email',
            'validate' => array( 'email' ),
            'fieldset' => 'profile'
        );

        $fields[ 'ship_to_account_address' ] = array(
            'type' => 'checkbox',
            'label' => sprintf(
                /* translators: %s: wishlist type title */
                __('Ship my %s items to my acccount shipping address', 'nm-gift-registry-lite'),
                nmgr_get_type_title()
            ),
            'custom_attributes' => array(
                'data-save' => !$this->wishlist->is_shipping_to_account_address(),
            ),
            'default' => ( int ) $this->wishlist->is_shipping_to_account_address(),
        );

        $fields[ 'wishlist_id' ] = array(
            'type' => 'nmgr-hidden',
            'value' => $this->wishlist->get_id(),
        );

        $fields[ 'nmgr_user_id' ] = array(
            'type' => 'nmgr-hidden',
            'prefix' => false,
            'value' => $this->wishlist->get_user_id() ? $this->wishlist->get_user_id() : nmgr_get_current_user_id(),
        );

        // Get shipping fields
        $fields = array_merge($fields, ( array ) $this->get_shipping_fields());

        // Set default field values
        $fields = $this->set_defaults($fields);

        // Set field values from the stored data for the wishlist
        $fields = $this->set_values($fields);

        // Set required attribute for profile fields based on default settings and plugin settings
        foreach ($fields as $key => $args) {
            if (isset($args[ 'fieldset' ]) && 'profile' === $args[ 'fieldset' ]) {
                $required = isset($args[ 'required' ]) && $args[ 'required' ] ? 'required' : false;
                $fields[ $key ][ 'required' ] = ( bool ) ('required' === nmgr_get_option("display_form_{$key}", $required));
            }
        }

        // Get the requested fields, or all fields if no specific group is requested
        if ($fieldset) {
            if (is_string($fieldset)) {
                foreach ($fields as $key => $args) {
                    if (isset($args[ 'fieldset' ]) && ($args[ 'fieldset' ] === $fieldset) && !in_array($key, ( array ) $ignore)) {
                        $requested_fields[ $key ] = $args;
                    }
                }
            } elseif (is_array($fieldset)) {
                foreach ($fields as $key => $args) {
                    if (in_array($key, $fieldset)) {
                        $requested_fields[ $key ] = $args;
                    }
                }
            }
        } else {
            $requested_fields = array_diff_key($fields, array_flip(( array ) $ignore));
        }

        // Exclude hidden fields
        if ($exclude_hidden) {
            foreach (array_keys($requested_fields) as $key) {
                if ('no' === nmgr_get_option("display_form_{$key}", 'yes')) {
                    unset($requested_fields[ $key ]);
                }
            }
        }

        if ($prefix) {
            // Add plugin prefix to all fields except fields with $args['prefix'] set to false
            $requested_fields = $this->add_prefix($requested_fields);
        }

        $prepared_fields = apply_filters('nmgr_fields', $requested_fields, $this->wishlist);

        /**
         * Enforce 'required' attribute for title field
         * Title field is used to save the wishlist title in database as wordpress post title
         */
        if (isset($prepared_fields[ nmgr()->prefix . 'title' ])) {
            $prepared_fields[ nmgr()->prefix . 'title' ][ 'required' ] = true;
        }

        $this->fields_count = count($prepared_fields);

        return $prepared_fields;
    }

    /**
     * Get form html for specified fields
     *
     * @param string|array $fieldset The name of the field group to get form html for
     * or array of field keys to get form html for. If not provided, this defaults to all fields
     *
     * @param string $title The title to use to categorise this form fieldset. Default none
     *
     * @param bool $wrapper Whether to wrap the fieldset with opening and closing div tags
     *
     * @param bool $exclude_hidden Whether to exclude fields that should not be displayed,
     * based on the admin defined plugin settings. Default true.
     */
    public function get_fields_html($fieldset = '', $ignore = array(), $title = '', $wrapper = true, $exclude_hidden = true)
    {
        $fields = $this->get_fields($fieldset, $ignore, $exclude_hidden);
        $class = is_string($fieldset) ? $fieldset . '-' : '';
        $is_admin = is_nmgr_admin_request();

        if (!$this->fields_count) {
            return;
        }

        ob_start();
        echo $wrapper ? wp_kses_post("<div class='form-group fieldset nmgr-{$class}fields'>") : '';

        if (apply_filters('nmgr_fields_title', $title, $fieldset)) {
            printf(wp_kses_post("<h3 class='fieldset-title nmgr-{$class}fields-title'>%s</h3>"), esc_html($title));
        }

        foreach ($fields as $name => $args) {
            if ($is_admin) {
                /**
                 * We're not using woocommerce_form_field to compose the fields in the admin area in order to
                 * prevent conflicts because it is typically used for checkout fields on the frontend. So we want to
                 * modify each field used in the admin area so that it can be used with the woocommerce_wp_text e.t.c.
                 * functions instead (@see wc-meta-box-functions.php), which are the functions used by woocommerce
                 * to compose shipping and billing fields in the order screen.
                 */
                $args[ 'type' ] = isset($args[ 'type' ]) ? $args[ 'type' ] : 'text';
                $args[ 'id' ] = isset($args[ 'id' ]) ? $args[ 'id' ] : $name;
                $args[ 'label' ] = isset($args[ 'label' ]) ? $args[ 'label' ] : '';
                $args[ 'wrapper_class' ] = 'form-row ' . (isset($args[ 'class' ]) ? implode(' ', ( array ) $args[ 'class' ]) : '');
                $args[ 'class' ] = '';

                switch ($name) {
                    case 'shipping_country':
                        $args[ 'type' ] = 'select';
                        $args[ 'class' ] = 'js_field-country select short';
                        $args[ 'options' ] = array( '' => __('Select a country&hellip;', 'nm-gift-registry-lite') ) + WC()->countries->get_shipping_countries();
                        break;

                    case 'shipping_state':
                        $args[ 'class' ] = 'js_field-state select short';
                        $args[ 'label' ] = __('State / County', 'nm-gift-registry-lite');
                        break;

                    default:
                        break;
                }

                switch ($args[ 'type' ]) {
                    case 'select':
                        $args[ 'style' ] = 'width:100%;max-width:100%!important';
                        woocommerce_wp_select($args);
                        break;
                    case 'textarea':
                        woocommerce_wp_textarea_input($args);
                        break;
                    case 'checkbox':
                        woocommerce_wp_checkbox($args);
                        break;
                    case 'radio':
                        woocommerce_wp_radio($args);
                        break;
                    case 'hidden':
                    case 'nmgr-hidden':
                        woocommerce_wp_hidden_input($args);
                        break;
                    default:
                        woocommerce_wp_text_input($args);
                        break;
                }
            } else {
                /**
                 * We are on the frontend here so use woocommerce_form_field to compose the fields.
                 */
                woocommerce_form_field($name, $args, $args[ 'value' ]);
            }
        }

        echo $wrapper ? '</div>' : '';
        return ob_get_clean();
    }

    /**
     * Get shipping fields for wishlist
     *
     * Uses the same shipping fields woocommerce uses
     *
     * Plugin prefix is not added to shipping fields because we want woocommerce
     * 'wc-country-select' script to be able to manipulate the fields. So we have to
     * process these fields specially after posting.
     *
     * @return array
     */
    public function get_shipping_fields()
    {
        if (!function_exists('wc')) {
            return array();
        }

        $shipping_country = $this->wishlist->get_shipping_country();
        $shipping_country = !$shipping_country ? wc()->countries->get_base_country() : $shipping_country;
        $allowed_countries = wc()->countries->get_shipping_countries();

        if (!array_key_exists($shipping_country, $allowed_countries)) {
            $shipping_country = current(array_keys($allowed_countries));
        }

        $fields = wc()->countries->get_address_fields($shipping_country, 'shipping_');

        $modified_fields = array_map(function ($field, $key) {

            // Get the wishlist's value for this field
            $value = $this->get_wishlist_value($key);

            // Add field to the 'shipping' fieldset
            $field[ 'fieldset' ] = 'shipping';

            // Do not prefix field to allow woocommerce js work on it if necessary
            $field[ 'prefix' ] = false;

            // Set the field value as the wishlist's value
            $field[ 'default' ] = $value;

            return $field;
        }, $fields, array_keys($fields));

        return array_combine(array_keys($fields), $modified_fields);
    }

    /**
     * This submit button html group is an exact copy of that on woocommerce's form-edit-account.php template
     * It is used to give consistency with woocommerce's own html.
     */
    public function get_submit_button()
    {
        return sprintf('<button type="submit" class="save-action button" name="save_nmgr_wishlist">%s</button>', esc_html(__('Save changes', 'nm-gift-registry-lite')));
    }

    /**
     * Get a common nonce for the form
     *
     * @return string Nonce hidden input
     */
    public function get_nonce()
    {
        return wp_nonce_field('nmgr_form', 'nmgr_form-nonce', true, false);
    }

    /**
     * Get standard hidden fields to be used by all form instances
     *
     * @return html
     */
    public function get_hidden_fields()
    {
        // Always add the wishlist id and user id fields
        $fields = $this->get_fields_html(array(
            'wishlist_id',
            'nmgr_user_id',
            ), '', '', false);

        return apply_filters('nmgr_hidden_fields', $fields);
    }

    /**
     * Verify the form nonce
     *
     * @param array $request Array to check in for existing nonce key or $_REQUEST if not supplied
     * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
     *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
     */
    public static function verify_nonce($request = '')
    {
        $request = $request ? $request : $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification
        return isset($request[ 'nmgr_form-nonce' ]) ? wp_verify_nonce(sanitize_key($request[ 'nmgr_form-nonce' ]), 'nmgr_form') : false;
    }

    /**
     * Sanitize only registered form fields
     *
     * @param array $posted_data Data posted from form
     * @return array Sanitized plugin prefixed posted data
     */
    public function sanitize($posted_data = '')
    {
        if ($posted_data) {
            $this->set_data($posted_data);
        }

        $data = $this->get_data();
        $fields = $this->get_fields('', '', false);

        foreach (array_keys($fields) as $key) {
            if (isset($data[ $key ])) {

                // get field types to sanitize
                $type = sanitize_title(isset($fields[ $key ][ 'type' ]) ? $fields[ $key ][ 'type' ] : 'text');

                switch ($type) {
                    case 'checkbox':
                        $data[ $key ] = 1;
                        break;
                    case 'textarea':
                        $data[ $key ] = wc_sanitize_textarea(wp_unslash($data[ $key ]));
                        break;
                    case 'password':
                        $data[ $key ] = wp_unslash($data[ $key ]);
                        break;
                    default:
                        $data[ $key ] = wc_clean(wp_unslash($data[ $key ]));
                        break;
                }

                $data[ $key ] = apply_filters('nmgr_sanitize_' . $type . '_field', apply_filters('nmgr_sanitize_field_' . $key, $data[ $key ]));
            }
        }
        $this->set_data($data);
        return $this;
    }

    /**
     * Validate only registered form fields
     *
     * @param array $posted_data Data posted from form
     * @return void
     */
    public function validate($posted_data = '')
    {
        if ($posted_data) {
            $this->set_data($posted_data);
        }

        $data = $this->get_data();
        $this->error = new WP_Error();
        $fields = $this->get_fields('', '', false);
        $shipping_fields = array();

        /**
         * Merge posted shipping fields with all fields if the shipping country is posted
         * so that we can validate shipping fields properly
         */
        if (isset($data[ 'shipping_country' ])) {
            $shipping_fields = wc()->countries->get_address_fields($data[ 'shipping_country' ], 'shipping_');
            $fields = array_merge($fields, $shipping_fields);
        }

        foreach ($fields as $key => $field) {
            if (!isset($data[ $key ])) {
                continue;
            }

            /* translators: %s: shipping field label */
            $field_label = isset($field[ 'label' ]) ? in_array($key, array_keys($shipping_fields)) ? sprintf(__('Shipping %s', 'nm-gift-registry-lite'), $field[ 'label' ]) : $field[ 'label' ] : '';
            $field_value = $data[ $key ];

            // Validate required fields
            if (isset($field[ 'required' ]) && !empty($field[ 'required' ]) && empty($field_value)) {
                /* translators: %s: shipping field label */
                $this->error->add('required-field', sprintf(__('%s is a required field.', 'nm-gift-registry-lite'), '<strong>' . esc_html($field_label) . '</strong>'), $field_label);
            }

            if (!empty($field_value)) {
                if (isset($field[ 'validate' ]) && !empty($field[ 'validate' ])) {
                    foreach (( array ) $field[ 'validate' ] as $rule) {
                        switch ($rule) {
                            case 'email':
                                if (!is_email($field_value)) {
                                    $this->error->add(
                                        'validation',
                                        /* translators: %s: supplied email address */
                                        sprintf(__('%s is not a valid email address.', 'nm-gift-registry-lite'), '<strong>' . esc_html($field_label) . '</strong>')
                                    );
                                }
                                break;
                            case 'postcode':
                                $country = $data[ 'shipping_country' ] ? $data[ 'shipping_country' ] : $this->wishlist->get_shipping_country();
                                $value = wc_format_postcode($field_value, $country);

                                if ('' !== $value && !WC_Validation::is_postcode($value, $country)) {
                                    $this->error->add('validation', __('Please enter a valid postcode / ZIP.', 'nm-gift-registry-lite'));
                                }
                                break;
                            case 'state':
                                $country = $data[ 'shipping_country' ] ? $data[ 'shipping_country' ] : $this->wishlist->get_shipping_country();
                                $valid_states = WC()->countries->get_states($country);

                                if (!empty($valid_states) && is_array($valid_states) && count($valid_states) > 0) {
                                    $valid_state_values = array_map('wc_strtoupper', array_flip(array_map('wc_strtoupper', $valid_states)));
                                    $field_value = wc_strtoupper($field_value);

                                    if (isset($valid_state_values[ $field_value ])) {
                                        $field_value = $valid_state_values[ $field_value ];
                                    }

                                    if (!in_array($field_value, $valid_state_values, true)) {
                                        /* translators: %1$s: supplied state, %2$s: valid states */
                                        $this->error->add('validation', sprintf(__('%1$s is not valid. Please enter one of the following: %2$s', 'nm-gift-registry-lite'), '<strong>' . esc_html($field_label) . '</strong>', implode(', ', $valid_states)));
                                    }
                                }
                                break;
                            case 'date':
                                if (!nmgr_get_datetime($field_value)) {
                                    /* translators: %s: field label */
                                    $this->error->add('invalid-date', sprintf(__('%s - Please enter the date in a valid format.', 'nm-gift-registry-lite'), '<strong>' . esc_html($field_label) . '</strong>'), $field_label);
                                }
                                break;
                        }
                    }
                }
            }
        }

        /**
         * Allow others to validate posted data
         *
         * @param array $data Posted data
         * @param array $fields Form fields
         * @param NMGR_Form $this Form Object
         */
        do_action('nmgr_validate_fields', $data, $fields, $this);

        return $this;
    }

    /**
     * Utility function to save form fields to wishlist
     *
     * @return in Wishlist id on successful save
     */
    public function save()
    {
        // Remove prefix from posted data so that we can save
        $this->remove_prefix();

        $this->wishlist->set_props($this->data);
        return $this->wishlist->save();
    }
}