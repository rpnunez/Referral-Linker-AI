<?php
/**
 * Uninstall script for Referral Link Manager
 * 
 * Runs when the plugin is deleted from WordPress admin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'rlm_pending_approvals';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

delete_option('rlm_settings');
delete_option('rlm_db_version');

$posts = get_posts(array(
    'post_type' => array('referral_link', 'link_maker'),
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids',
));

foreach ($posts as $post_id) {
    wp_delete_post($post_id, true);
}

$terms = get_terms(array(
    'taxonomy' => 'referral_link_group',
    'hide_empty' => false,
    'fields' => 'ids',
));

if (!is_wp_error($terms)) {
    foreach ($terms as $term_id) {
        wp_delete_term($term_id, 'referral_link_group');
    }
}

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        '_rlm_%'
    )
);
