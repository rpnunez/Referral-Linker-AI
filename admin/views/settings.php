<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Referral Link Manager Settings', 'referral-link-manager'); ?></h1>
    
    <?php settings_errors('rlm_settings'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('rlm_settings'); ?>
        
        <h2><?php esc_html_e('Processing Settings', 'referral-link-manager'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="posts_per_batch"><?php esc_html_e('Posts per Batch', 'referral-link-manager'); ?></label></th>
                <td>
                    <input type="number" id="posts_per_batch" name="posts_per_batch" value="<?php echo esc_attr($options['posts_per_batch']); ?>" min="1" max="100" class="small-text">
                    <p class="description"><?php esc_html_e('Number of posts to process in each cron run', 'referral-link-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Skip Processed', 'referral-link-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skip_processed" value="1" <?php checked($options['skip_processed']); ?>>
                        <?php esc_html_e('Do not process posts that already have referral links', 'referral-link-manager'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Track Usage', 'referral-link-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="track_usage" value="1" <?php checked($options['track_usage']); ?>>
                        <?php esc_html_e('Record usage statistics for each referral link', 'referral-link-manager'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Notifications', 'referral-link-manager'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Email Notifications', 'referral-link-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="email_pending" value="1" <?php checked($options['email_pending']); ?>>
                        <?php esc_html_e('Send email when new posts need approval', 'referral-link-manager'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Admin Bar', 'referral-link-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="admin_bar" value="1" <?php checked($options['admin_bar']); ?>>
                        <?php esc_html_e('Show pending count in WordPress admin bar', 'referral-link-manager'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Dashboard Widget', 'referral-link-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="dashboard_widget" value="1" <?php checked($options['dashboard_widget']); ?>>
                        <?php esc_html_e('Show stats widget on WordPress dashboard', 'referral-link-manager'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('AI Engine Status', 'referral-link-manager'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Status', 'referral-link-manager'); ?></th>
                <td>
                    <?php if (Referral_Link_Manager::check_ai_engine()): ?>
                    <span style="color: green;">&#10004; <?php esc_html_e('Connected', 'referral-link-manager'); ?></span>
                    <p class="description"><?php esc_html_e('Meow Apps AI Engine is installed and active.', 'referral-link-manager'); ?></p>
                    <?php else: ?>
                    <span style="color: red;">&#10008; <?php esc_html_e('Not Connected', 'referral-link-manager'); ?></span>
                    <p class="description">
                        <?php esc_html_e('Meow Apps AI Engine is required.', 'referral-link-manager'); ?>
                        <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai+engine&tab=search&type=term')); ?>">
                            <?php esc_html_e('Install Now', 'referral-link-manager'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="rlm_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'referral-link-manager'); ?>">
        </p>
    </form>
</div>
