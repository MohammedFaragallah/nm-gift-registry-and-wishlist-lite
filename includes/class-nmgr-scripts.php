<?php

/**
 * Scripts and styles used by plugin
 *
 * @version 2.0.0
 */
defined('ABSPATH') || exit;

class NMGR_Scripts
{

    /**
     * Suffix to append to file name before file extension e.g. 'min' for minified
     *
     * @var string
     */
    private static $suffix;

    /**
     * Localized script handles
     *
     * @var array
     */
    private static $inline_scripts = array();

    public static function run()
    {
        self::$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        // 999 priority ensures we hook our scripts after woocommerce's and any other conflicting plugin scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_scripts'), 999);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_scripts'), 999);

        add_action('wp_print_scripts', array(__CLASS__, 'add_inline_scripts'), 5);
        add_action('wp_print_footer_scripts', array(__CLASS__, 'add_inline_scripts'), 5);

        add_action('wp_footer', 'nmgr_include_sprite_file');
        add_action('admin_footer', 'nmgr_include_sprite_file');
    }

    public static function frontend_scripts()
    {
        self::register_common_scripts();
        self::register_frontend_scripts();

        wp_enqueue_style('nmgr-frontend');
        wp_enqueue_script('nmgr-frontend');
    }

    public static function admin_scripts()
    {
        self::register_common_scripts();
        self::register_admin_scripts();

        if (is_nmgr_admin()) {
            wp_enqueue_style('nmgr-admin');
            wp_enqueue_script('nmgr-admin');
        }
    }

    /**
     * Register scripts used on both admin and frontend
     *
     * Because woocommerce also registers some of these scripts, we have to check
     * if the script it registered so that we can use woocommerce's own and not override it
     * with our own or add our script to the page if it already exists
     */
    private static function register_common_scripts()
    {
        // Styles
        wp_register_style('nmgr-jquery-tiptip', nmgr()->url . 'assets/css/vendor/jquery-tiptip' . self::$suffix . '.css', array());

        if (!wp_style_is('select2', 'registered')) {
            wp_register_style('select2', nmgr()->url . 'assets/css/vendor/select2.css', array());
        }

        // Scripts
        if (!wp_script_is('stupidtable', 'registered')) {
            wp_register_script('stupidtable', nmgr()->url . 'assets/js/vendor/stupidtable' . self::$suffix . '.js', array('jquery'), '', true);
        }

        if (!wp_script_is('jquery-blockui', 'registered')) {
            wp_register_script('jquery-blockui', nmgr()->url . 'assets/js/vendor/jquery.blockUI' . self::$suffix . '.js', array('jquery'), '2.70', true);
        }

        if (!wp_script_is('jquery-tiptip', 'registered')) {
            wp_register_script('jquery-tiptip', nmgr()->url . 'assets/js/vendor/jquery.tipTip' . self::$suffix . '.js', array('jquery'), nmgr()->version, true);
        }

        // We're forced to use selectWoo instead of select2 because woocommerce uses it for shipping fields
        if (!wp_script_is('selectWoo', 'registered')) {
            wp_register_script('selectWoo', nmgr()->url . 'assets/js/vendor/selectWoo.full' . self::$suffix . '.js', array('jquery'), '1.0.6', true);
        }

        wp_register_script('nmgr-bootstrap', nmgr()->url . 'assets/js/vendor/bootstrap-native' . self::$suffix . '.js', array(), nmgr()->version, true);
    }

    private static function register_admin_scripts()
    {
        wp_register_style('nmgr-admin', nmgr()->url . 'assets/css/admin' . self::$suffix . '.css', array('nmgr-jquery-tiptip'), nmgr()->version);
        wp_register_script('nmgr-admin', nmgr()->url . 'assets/js/admin' . self::$suffix . '.js', array('jquery', 'selectWoo', 'stupidtable', 'jquery-blockui', 'nmgr-bootstrap', 'jquery-ui-datepicker', 'jquery-tiptip'), nmgr()->version, true);
    }

    private static function register_frontend_scripts()
    {
        wp_register_style('nmgr-frontend', nmgr()->url . 'assets/css/frontend' . self::$suffix . '.css', array('nmgr-jquery-tiptip', 'select2'), nmgr()->version);
        wp_register_script('nmgr-frontend', nmgr()->url . 'assets/js/frontend' . self::$suffix . '.js', array('jquery', 'jquery-tiptip', 'wc-add-to-cart-variation', 'stupidtable', 'jquery-blockui', 'selectWoo', 'wc-country-select', 'wc-address-i18n', 'nmgr-bootstrap', 'jquery-ui-datepicker'), nmgr()->version, true);
    }

    private static function get_script_data($handle = '')
    {
        $data = array();
        $nmgr_global = isset($GLOBALS['nmgr']) ? $GLOBALS['nmgr'] : '';
        $ajax_url = admin_url('admin-ajax.php');

        // Parameters that can be used by various scripts
        $global_params = array(
            'global' => $nmgr_global,
            'ajax_url' => $ajax_url,
            'nonce' => wp_create_nonce('nmgr'), // Generic nonce for the application,
            'date_format' => apply_filters('nmgr_datepicker_date_format', 'MM d yy'),
            'style_datepicker' => apply_filters('nmgr_style_datepicker', true),
        );

        $data['nmgr-frontend'] = array(
            'global' => $global_params,
            'nonce' => wp_create_nonce('nmgr-frontend'),
            'shipping_address_required' => nmgr_get_option('shipping_address_required'),
            'i18n_use_account_shipping_address_text' => esc_attr__('Use your account shipping address? This will remove any currently entered shipping information.', 'nm-gift-registry-lite'),
            'i18n_copied_text' => esc_attr__('Copied', 'nm-gift-registry-lite'),
            'i18n_select_product_text' => sprintf(
                /* translators: %s: wishlist type title */
                esc_attr__('Please select an item to add to your %s.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            ),
            'i18n_select_quantity_text' => sprintf(
                /* translators: %s: wishlist type title */
                esc_attr__('Please choose the quantity of items you wish to add to your %s.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            ),
            'i18n_make_a_selection_text' => sprintf(
                /* translators: %s wishlist type title */
                esc_attr__('Please select some product options before adding this product to your %s.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title())
            ),
            'i18n_unavailable_text' => esc_attr__('Sorry, this product is unavailable. Please choose a different combination.', 'nm-gift-registry-lite'),
            'disable_notice' => sprintf(
                /* translators: 1,2: wishlist type title */
                esc_attr__('Are you sure you want to disable the %1$s module? This would delete your %2$s and all its data.', 'nm-gift-registry-lite'),
                esc_html(nmgr_get_type_title()),
                esc_html(nmgr_get_type_title())
            ),
        );


        $data['nmgr-admin'] = array(
            'global' => $global_params,
            'i18n_use_account_shipping_address_text' => __("Use the user's account shipping address? This will remove any currently entered shipping information.", 'nm-gift-registry-lite'),
            'i18n_select_user' => esc_attr__('You have not selected a user yet. Please select a user', 'nm-gift-registry-lite'),
            'search_users_nonce' => wp_create_nonce('nmgr-search-users'),
            'search_products_nonce' => wp_create_nonce('nmgr-search-products'),
            'i18n_select_state_text' => esc_attr__('Select an option...', 'nm-gift-registry-lite'),
            'i18n_guest_text' => esc_attr__('Guest', 'nm-gift-registry-lite'),
        );

        if (is_a(wc()->countries, 'WC_Countries')) {
            $data['nmgr-admin']['countries'] = wp_json_encode(array_merge(WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states()));
        }

        if ($handle) {
            return isset($data[$handle]) ? $data[$handle] : false;
        }

        return $data;
    }

    public static function add_inline_scripts()
    {
        $handles = array_keys(self::get_script_data());
        $global_inline_script_added = false;

        foreach ($handles as $handle) {
            /**
             * We have to use this condition because this function is hooked to both wp_print_scripts and
             * wp_print_footer_scripts so it runs twice and we dont want to add the inline scripts twice so we
             * make sure that once it is added, it is not added again.
             */
            if (!in_array($handle, self::$inline_scripts, true) && wp_script_is($handle, 'enqueued')) {
                $data = self::get_script_data($handle);

                if (isset($data['global'])) {
                    if (false === $global_inline_script_added) {
                        wp_add_inline_script($handle, 'var nmgr_global_params = ' . json_encode($data['global']), 'before');
                        $global_inline_script_added = true;
                    }

                    if ($global_inline_script_added) {
                        unset($data['global']);
                    }
                }

                if (!empty($data)) {
                    $name = str_replace('-', '_', $handle) . '_params';
                    wp_add_inline_script($handle, 'var ' . $name . ' = ' . json_encode($data), 'before');
                }

                self::$inline_scripts[] = $handle;
            }
        }
    }
}