<?php
/**
 * Plugin Name: Referral Link Manager
 * Plugin URI: https://example.com/referral-link-manager
 * Description: AI-powered plugin for automatically inserting referral and affiliate links into your WordPress posts using Meow Apps AI Engine.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: referral-link-manager
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RLM_VERSION', '1.0.0');
define('RLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RLM_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Referral_Link_Manager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once RLM_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once RLM_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once RLM_PLUGIN_DIR . 'includes/class-ai-processor.php';
        require_once RLM_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once RLM_PLUGIN_DIR . 'includes/class-pending-approvals.php';
        
        if (is_admin()) {
            require_once RLM_PLUGIN_DIR . 'admin/class-admin.php';
            require_once RLM_PLUGIN_DIR . 'admin/class-meta-boxes.php';
            require_once RLM_PLUGIN_DIR . 'admin/class-pending-list.php';
        }
    }

    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        RLM_Post_Types::register();
        RLM_Taxonomies::register();
        RLM_Cron_Handler::init();
        
        if (is_admin()) {
            RLM_Admin::init();
        }
    }

    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen) return;
        
        $allowed_screens = array(
            'referral_link',
            'link_maker',
            'edit-referral_link',
            'edit-link_maker',
            'edit-referral_link_group',
            'referral-link-manager_page_rlm-pending',
            'referral-link-manager_page_rlm-settings',
        );

        if (in_array($screen->id, $allowed_screens) || 
            in_array($screen->post_type, array('referral_link', 'link_maker'))) {
            wp_enqueue_style(
                'rlm-admin',
                RLM_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                RLM_VERSION
            );
            
            wp_enqueue_script(
                'rlm-admin',
                RLM_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                RLM_VERSION,
                true
            );
            
            wp_localize_script('rlm-admin', 'rlmAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rlm_admin_nonce'),
            ));
        }
    }

    public function activate() {
        RLM_Post_Types::register();
        RLM_Taxonomies::register();
        flush_rewrite_rules();
        
        $this->create_tables();
        
        RLM_Cron_Handler::schedule_events();
        
        update_option('rlm_db_version', RLM_VERSION);
    }

    public function deactivate() {
        RLM_Cron_Handler::clear_scheduled_events();
    }

    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rlm_pending_approvals';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            maker_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            original_content longtext NOT NULL,
            modified_content longtext NOT NULL,
            inserted_links longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY maker_id (maker_id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if ($wpdb->last_error) {
            error_log('RLM Table Creation Error: ' . $wpdb->last_error);
        }
    }

    public static function uninstall() {
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
    }

    public static function check_ai_engine() {
        return class_exists('Meow_MWAI_Core') || function_exists('mwai_generate_text');
    }
}

function rlm_init() {
    return Referral_Link_Manager::get_instance();
}

add_action('plugins_loaded', 'rlm_init');
