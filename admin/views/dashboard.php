<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap rlm-dashboard">
    <h1><?php esc_html_e('Referral Link Manager', 'referral-link-manager'); ?></h1>
    
    <?php if (!Referral_Link_Manager::check_ai_engine()): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('AI Engine Required', 'referral-link-manager'); ?></strong><br>
            <?php esc_html_e('Install and configure Meow Apps AI Engine to enable AI-powered link insertion.', 'referral-link-manager'); ?>
            <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai+engine&tab=search&type=term')); ?>" class="button button-secondary" style="margin-left: 10px;">
                <?php esc_html_e('Install AI Engine', 'referral-link-manager'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <div class="rlm-stats-grid">
        <div class="rlm-stat-card">
            <span class="rlm-stat-icon dashicons dashicons-admin-links"></span>
            <div class="rlm-stat-content">
                <span class="rlm-stat-value"><?php echo esc_html($links_count); ?></span>
                <span class="rlm-stat-label"><?php esc_html_e('Referral Links', 'referral-link-manager'); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=referral_link')); ?>" class="rlm-stat-link">
                <?php esc_html_e('View All', 'referral-link-manager'); ?> &rarr;
            </a>
        </div>

        <div class="rlm-stat-card">
            <span class="rlm-stat-icon dashicons dashicons-admin-settings"></span>
            <div class="rlm-stat-content">
                <span class="rlm-stat-value"><?php echo count($makers); ?></span>
                <span class="rlm-stat-label"><?php esc_html_e('Link Makers', 'referral-link-manager'); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=link_maker')); ?>" class="rlm-stat-link">
                <?php esc_html_e('View All', 'referral-link-manager'); ?> &rarr;
            </a>
        </div>

        <div class="rlm-stat-card <?php echo $pending_count > 0 ? 'rlm-stat-alert' : ''; ?>">
            <span class="rlm-stat-icon dashicons dashicons-clipboard"></span>
            <div class="rlm-stat-content">
                <span class="rlm-stat-value"><?php echo esc_html($pending_count); ?></span>
                <span class="rlm-stat-label"><?php esc_html_e('Pending Approval', 'referral-link-manager'); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rlm-pending')); ?>" class="rlm-stat-link">
                <?php esc_html_e('Review Now', 'referral-link-manager'); ?> &rarr;
            </a>
        </div>
    </div>

    <div class="rlm-dashboard-grid">
        <div class="rlm-card">
            <h2><?php esc_html_e('Recent Link Makers', 'referral-link-manager'); ?></h2>
            <?php if (empty($makers)): ?>
            <p><?php esc_html_e('No link makers configured yet.', 'referral-link-manager'); ?></p>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=link_maker')); ?>" class="button button-primary">
                <?php esc_html_e('Create First Maker', 'referral-link-manager'); ?>
            </a>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'referral-link-manager'); ?></th>
                        <th><?php esc_html_e('Status', 'referral-link-manager'); ?></th>
                        <th><?php esc_html_e('Last Run', 'referral-link-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($makers as $maker): 
                        $status = get_post_meta($maker->ID, '_rlm_status', true) ?: 'draft';
                        $last_run = get_post_meta($maker->ID, '_rlm_last_run', true);
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($maker->ID)); ?>">
                                <?php echo esc_html($maker->post_title); ?>
                            </a>
                        </td>
                        <td>
                            <span class="rlm-status rlm-status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $last_run ? esc_html(human_time_diff(strtotime($last_run))) . ' ago' : esc_html__('Never', 'referral-link-manager'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="rlm-card">
            <h2><?php esc_html_e('Quick Actions', 'referral-link-manager'); ?></h2>
            <div class="rlm-quick-actions">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=referral_link')); ?>" class="button">
                    <?php esc_html_e('Add Referral Link', 'referral-link-manager'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=link_maker')); ?>" class="button">
                    <?php esc_html_e('New Link Maker', 'referral-link-manager'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=referral_link_group&post_type=referral_link')); ?>" class="button">
                    <?php esc_html_e('Manage Groups', 'referral-link-manager'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rlm-settings')); ?>" class="button">
                    <?php esc_html_e('Settings', 'referral-link-manager'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
