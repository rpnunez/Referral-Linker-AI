<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Taxonomies {
    public static function register() {
        self::register_referral_link_group();
    }

    private static function register_referral_link_group() {
        $labels = array(
            'name'              => __('Link Groups', 'referral-link-manager'),
            'singular_name'     => __('Link Group', 'referral-link-manager'),
            'search_items'      => __('Search Link Groups', 'referral-link-manager'),
            'all_items'         => __('All Link Groups', 'referral-link-manager'),
            'parent_item'       => __('Parent Link Group', 'referral-link-manager'),
            'parent_item_colon' => __('Parent Link Group:', 'referral-link-manager'),
            'edit_item'         => __('Edit Link Group', 'referral-link-manager'),
            'update_item'       => __('Update Link Group', 'referral-link-manager'),
            'add_new_item'      => __('Add New Link Group', 'referral-link-manager'),
            'new_item_name'     => __('New Link Group Name', 'referral-link-manager'),
            'menu_name'         => __('Link Groups', 'referral-link-manager'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rewrite'           => false,
        );

        register_taxonomy('referral_link_group', array('referral_link'), $args);
    }
}
