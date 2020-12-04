<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$nmgr_installed = get_option('nmgr_version');

if (!$nmgr_installed) {
    // remove capabilities
    include_once dirname(__FILE__) . '/includes/class-nmgr-lite-install.php';
    NMGR_Lite_Install::remove_capabilities();

    // delete tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nmgr_wishlist_items");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nmgr_wishlist_itemmeta");


    // delete posts and postmeta
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'nm_gift_registry');");
    $wpdb->query("DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;");

    // delete user meta
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'nmgr\_%';");

    // delete options
    delete_option('nmgr_settings');
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'nmgr\_%';");
}

$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'nmgrlite\_%';");