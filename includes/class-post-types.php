<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Post_Types {
    public static function register() {
        self::register_referral_link();
        self::register_link_maker();
    }

    private static function register_referral_link() {
        $labels = array(
            'name'               => __('Referral Links', 'referral-link-manager'),
            'singular_name'      => __('Referral Link', 'referral-link-manager'),
            'add_new'            => __('Add New', 'referral-link-manager'),
            'add_new_item'       => __('Add New Referral Link', 'referral-link-manager'),
            'edit_item'          => __('Edit Referral Link', 'referral-link-manager'),
            'new_item'           => __('New Referral Link', 'referral-link-manager'),
            'view_item'          => __('View Referral Link', 'referral-link-manager'),
            'search_items'       => __('Search Referral Links', 'referral-link-manager'),
            'not_found'          => __('No referral links found', 'referral-link-manager'),
            'not_found_in_trash' => __('No referral links found in Trash', 'referral-link-manager'),
            'menu_name'          => __('Referral Links', 'referral-link-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'referral-link-manager',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
            'show_in_rest'       => true,
        );

        register_post_type('referral_link', $args);
    }

    private static function register_link_maker() {
        $labels = array(
            'name'               => __('Link Makers', 'referral-link-manager'),
            'singular_name'      => __('Link Maker', 'referral-link-manager'),
            'add_new'            => __('Add New', 'referral-link-manager'),
            'add_new_item'       => __('Add New Link Maker', 'referral-link-manager'),
            'edit_item'          => __('Edit Link Maker', 'referral-link-manager'),
            'new_item'           => __('New Link Maker', 'referral-link-manager'),
            'view_item'          => __('View Link Maker', 'referral-link-manager'),
            'search_items'       => __('Search Link Makers', 'referral-link-manager'),
            'not_found'          => __('No link makers found', 'referral-link-manager'),
            'not_found_in_trash' => __('No link makers found in Trash', 'referral-link-manager'),
            'menu_name'          => __('Link Makers', 'referral-link-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'referral-link-manager',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
            'show_in_rest'       => true,
        );

        register_post_type('link_maker', $args);
    }
}
