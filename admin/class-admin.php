<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu_pages'));
        add_action('admin_bar_menu', array(__CLASS__, 'add_admin_bar_menu'), 100);
        add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widget'));
        add_action('admin_notices', array(__CLASS__, 'show_ai_engine_notice'));
        
        RLM_Meta_Boxes::init();
    }

    public static function add_menu_pages() {
        $pending_count = RLM_Pending_Approvals::get_pending_count();
        $menu_title = __('Referral Links', 'referral-link-manager');
        if ($pending_count > 0) {
            $menu_title .= sprintf(' <span class="awaiting-mod">%d</span>', $pending_count);
        }

        add_menu_page(
            __('Referral Link Manager', 'referral-link-manager'),
            $menu_title,
            'manage_options',
            'referral-link-manager',
            array(__CLASS__, 'render_dashboard_page'),
            'dashicons-admin-links',
            30
        );

        add_submenu_page(
            'referral-link-manager',
            __('Dashboard', 'referral-link-manager'),
            __('Dashboard', 'referral-link-manager'),
            'manage_options',
            'referral-link-manager',
            array(__CLASS__, 'render_dashboard_page')
        );

        $pending_menu_title = __('Pending Approval', 'referral-link-manager');
        if ($pending_count > 0) {
            $pending_menu_title .= sprintf(' <span class="awaiting-mod">%d</span>', $pending_count);
        }

        add_submenu_page(
            'referral-link-manager',
            __('Pending Approval', 'referral-link-manager'),
            $pending_menu_title,
            'manage_options',
            'rlm-pending',
            array(__CLASS__, 'render_pending_page')
        );

        add_submenu_page(
            'referral-link-manager',
            __('Settings', 'referral-link-manager'),
            __('Settings', 'referral-link-manager'),
            'manage_options',
            'rlm-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function add_admin_bar_menu($wp_admin_bar) {
        $pending_count = RLM_Pending_Approvals::get_pending_count();
        
        if ($pending_count > 0) {
            $wp_admin_bar->add_node(array(
                'id'    => 'rlm-pending',
                'title' => sprintf(
                    '<span class="ab-icon dashicons dashicons-admin-links"></span><span class="ab-label">%d</span>',
                    $pending_count
                ),
                'href'  => admin_url('admin.php?page=rlm-pending'),
                'meta'  => array(
                    'title' => sprintf(__('%d posts pending approval', 'referral-link-manager'), $pending_count),
                ),
            ));
        }
    }

    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'rlm_dashboard_widget',
            __('Referral Link Manager', 'referral-link-manager'),
            array(__CLASS__, 'render_dashboard_widget')
        );
    }

    public static function render_dashboard_widget() {
        $pending_count = RLM_Pending_Approvals::get_pending_count();
        $links_count = wp_count_posts('referral_link')->publish;
        $makers_count = wp_count_posts('link_maker')->publish;
        
        ?>
        <div class="rlm-dashboard-widget">
            <div class="rlm-stats">
                <div class="rlm-stat">
                    <span class="rlm-stat-value"><?php echo esc_html($links_count); ?></span>
                    <span class="rlm-stat-label"><?php esc_html_e('Referral Links', 'referral-link-manager'); ?></span>
                </div>
                <div class="rlm-stat">
                    <span class="rlm-stat-value"><?php echo esc_html($makers_count); ?></span>
                    <span class="rlm-stat-label"><?php esc_html_e('Link Makers', 'referral-link-manager'); ?></span>
                </div>
                <div class="rlm-stat">
                    <span class="rlm-stat-value"><?php echo esc_html($pending_count); ?></span>
                    <span class="rlm-stat-label"><?php esc_html_e('Pending', 'referral-link-manager'); ?></span>
                </div>
            </div>
            <?php if ($pending_count > 0): ?>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rlm-pending')); ?>" class="button button-primary">
                    <?php esc_html_e('Review Pending Posts', 'referral-link-manager'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        <style>
            .rlm-stats { display: flex; gap: 20px; margin-bottom: 15px; }
            .rlm-stat { text-align: center; flex: 1; }
            .rlm-stat-value { display: block; font-size: 24px; font-weight: bold; color: #2271b1; }
            .rlm-stat-label { font-size: 12px; color: #666; }
        </style>
        <?php
    }

    public static function show_ai_engine_notice() {
        if (!Referral_Link_Manager::check_ai_engine()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Referral Link Manager:', 'referral-link-manager'); ?></strong>
                    <?php esc_html_e('The Meow Apps AI Engine plugin is required for AI-powered link insertion.', 'referral-link-manager'); ?>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai+engine&tab=search&type=term')); ?>">
                        <?php esc_html_e('Install AI Engine', 'referral-link-manager'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    public static function render_dashboard_page() {
        $pending_count = RLM_Pending_Approvals::get_pending_count();
        $links_count = wp_count_posts('referral_link')->publish;
        $makers = get_posts(array(
            'post_type'      => 'link_maker',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
        ));
        
        include RLM_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function render_pending_page() {
        $pending_list = new RLM_Pending_List();
        include RLM_PLUGIN_DIR . 'admin/views/pending.php';
    }

    public static function render_settings_page() {
        if (isset($_POST['rlm_save_settings']) && check_admin_referer('rlm_settings')) {
            $options = array(
                'posts_per_batch'   => absint($_POST['posts_per_batch'] ?? 10),
                'skip_processed'    => isset($_POST['skip_processed']),
                'track_usage'       => isset($_POST['track_usage']),
                'email_pending'     => isset($_POST['email_pending']),
                'admin_bar'         => isset($_POST['admin_bar']),
                'dashboard_widget'  => isset($_POST['dashboard_widget']),
            );
            update_option('rlm_settings', $options);
            add_settings_error('rlm_settings', 'settings_saved', __('Settings saved.', 'referral-link-manager'), 'success');
        }

        $options = get_option('rlm_settings', array(
            'posts_per_batch'   => 10,
            'skip_processed'    => true,
            'track_usage'       => true,
            'email_pending'     => true,
            'admin_bar'         => true,
            'dashboard_widget'  => true,
        ));
        
        include RLM_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
