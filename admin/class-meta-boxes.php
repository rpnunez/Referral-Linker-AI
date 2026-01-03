<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Meta_Boxes {
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_boxes'), 10, 2);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'rlm_referral_link_details',
            __('Referral Link Details', 'referral-link-manager'),
            array(__CLASS__, 'render_referral_link_meta_box'),
            'referral_link',
            'normal',
            'high'
        );

        add_meta_box(
            'rlm_link_maker_filters',
            __('Post Selection Criteria', 'referral-link-manager'),
            array(__CLASS__, 'render_link_maker_filters_meta_box'),
            'link_maker',
            'normal',
            'high'
        );

        add_meta_box(
            'rlm_link_maker_groups',
            __('Referral Link Groups', 'referral-link-manager'),
            array(__CLASS__, 'render_link_maker_groups_meta_box'),
            'link_maker',
            'normal',
            'high'
        );

        add_meta_box(
            'rlm_link_maker_ai',
            __('AI Configuration', 'referral-link-manager'),
            array(__CLASS__, 'render_link_maker_ai_meta_box'),
            'link_maker',
            'normal',
            'default'
        );

        add_meta_box(
            'rlm_link_maker_schedule',
            __('Schedule Settings', 'referral-link-manager'),
            array(__CLASS__, 'render_link_maker_schedule_meta_box'),
            'link_maker',
            'side',
            'high'
        );

        add_meta_box(
            'rlm_link_maker_stats',
            __('Statistics', 'referral-link-manager'),
            array(__CLASS__, 'render_link_maker_stats_meta_box'),
            'link_maker',
            'side',
            'default'
        );
    }

    public static function render_referral_link_meta_box($post) {
        wp_nonce_field('rlm_save_referral_link', 'rlm_referral_link_nonce');
        
        $referral_url = get_post_meta($post->ID, '_rlm_referral_url', true);
        $usage_count = (int) get_post_meta($post->ID, '_rlm_usage_count', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="rlm_referral_url"><?php esc_html_e('Referral URL', 'referral-link-manager'); ?></label></th>
                <td>
                    <input type="url" id="rlm_referral_url" name="rlm_referral_url" value="<?php echo esc_url($referral_url); ?>" class="large-text" required>
                    <p class="description"><?php esc_html_e('The full URL with your referral/affiliate code', 'referral-link-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Usage Count', 'referral-link-manager'); ?></th>
                <td>
                    <strong><?php echo esc_html($usage_count); ?></strong>
                    <p class="description"><?php esc_html_e('Times this link has been inserted into posts', 'referral-link-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_link_maker_filters_meta_box($post) {
        wp_nonce_field('rlm_save_link_maker', 'rlm_link_maker_nonce');
        
        $categories = get_post_meta($post->ID, '_rlm_categories', true) ?: array();
        $tags = get_post_meta($post->ID, '_rlm_tags', true) ?: array();
        $authors = get_post_meta($post->ID, '_rlm_authors', true) ?: array();
        $date_from = get_post_meta($post->ID, '_rlm_date_from', true);
        $date_to = get_post_meta($post->ID, '_rlm_date_to', true);
        $post_statuses = get_post_meta($post->ID, '_rlm_post_statuses', true) ?: array('publish');
        
        $all_categories = get_categories(array('hide_empty' => false));
        $all_tags = get_tags(array('hide_empty' => false));
        $all_authors = get_users(array('who' => 'authors'));
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e('Categories', 'referral-link-manager'); ?></label></th>
                <td>
                    <div class="rlm-checkbox-list">
                        <?php foreach ($all_categories as $cat): ?>
                        <label>
                            <input type="checkbox" name="rlm_categories[]" value="<?php echo esc_attr($cat->term_id); ?>" <?php checked(in_array($cat->term_id, $categories)); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php esc_html_e('Leave empty to include all categories', 'referral-link-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Tags', 'referral-link-manager'); ?></label></th>
                <td>
                    <div class="rlm-checkbox-list">
                        <?php foreach ($all_tags as $tag): ?>
                        <label>
                            <input type="checkbox" name="rlm_tags[]" value="<?php echo esc_attr($tag->term_id); ?>" <?php checked(in_array($tag->term_id, $tags)); ?>>
                            <?php echo esc_html($tag->name); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Authors', 'referral-link-manager'); ?></label></th>
                <td>
                    <div class="rlm-checkbox-list">
                        <?php foreach ($all_authors as $author): ?>
                        <label>
                            <input type="checkbox" name="rlm_authors[]" value="<?php echo esc_attr($author->ID); ?>" <?php checked(in_array($author->ID, $authors)); ?>>
                            <?php echo esc_html($author->display_name); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Date Range', 'referral-link-manager'); ?></label></th>
                <td>
                    <input type="date" name="rlm_date_from" value="<?php echo esc_attr($date_from); ?>">
                    <?php esc_html_e('to', 'referral-link-manager'); ?>
                    <input type="date" name="rlm_date_to" value="<?php echo esc_attr($date_to); ?>">
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Post Status', 'referral-link-manager'); ?></label></th>
                <td>
                    <label><input type="checkbox" name="rlm_post_statuses[]" value="publish" <?php checked(in_array('publish', $post_statuses)); ?>> <?php esc_html_e('Published', 'referral-link-manager'); ?></label>
                    <label><input type="checkbox" name="rlm_post_statuses[]" value="draft" <?php checked(in_array('draft', $post_statuses)); ?>> <?php esc_html_e('Draft', 'referral-link-manager'); ?></label>
                    <label><input type="checkbox" name="rlm_post_statuses[]" value="pending" <?php checked(in_array('pending', $post_statuses)); ?>> <?php esc_html_e('Pending', 'referral-link-manager'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_link_maker_groups_meta_box($post) {
        $selected_groups = get_post_meta($post->ID, '_rlm_link_group_ids', true) ?: array();
        $all_groups = get_terms(array(
            'taxonomy'   => 'referral_link_group',
            'hide_empty' => false,
        ));
        ?>
        <p><?php esc_html_e('Select which referral link groups to use for link insertion:', 'referral-link-manager'); ?></p>
        <div class="rlm-checkbox-list">
            <?php if (empty($all_groups)): ?>
            <p class="description"><?php esc_html_e('No link groups found. Create some first.', 'referral-link-manager'); ?></p>
            <?php else: ?>
            <?php foreach ($all_groups as $group): ?>
            <label>
                <input type="checkbox" name="rlm_link_group_ids[]" value="<?php echo esc_attr($group->term_id); ?>" <?php checked(in_array($group->term_id, $selected_groups)); ?>>
                <?php echo esc_html($group->name); ?> (<?php echo esc_html($group->count); ?> links)
            </label>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_link_maker_ai_meta_box($post) {
        $links_per_post = get_post_meta($post->ID, '_rlm_links_per_post', true) ?: 3;
        $ai_instructions = get_post_meta($post->ID, '_rlm_ai_instructions', true);
        ?>
        <?php if (!Referral_Link_Manager::check_ai_engine()): ?>
        <div class="notice notice-warning inline">
            <p><?php esc_html_e('Meow Apps AI Engine is not installed. AI processing will not work.', 'referral-link-manager'); ?></p>
        </div>
        <?php endif; ?>
        
        <table class="form-table">
            <tr>
                <th><label for="rlm_links_per_post"><?php esc_html_e('Links per Post', 'referral-link-manager'); ?></label></th>
                <td>
                    <input type="number" id="rlm_links_per_post" name="rlm_links_per_post" value="<?php echo esc_attr($links_per_post); ?>" min="1" max="10" class="small-text">
                    <p class="description"><?php esc_html_e('Maximum number of links to insert per post', 'referral-link-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="rlm_ai_instructions"><?php esc_html_e('Custom Instructions', 'referral-link-manager'); ?></label></th>
                <td>
                    <textarea id="rlm_ai_instructions" name="rlm_ai_instructions" rows="4" class="large-text"><?php echo esc_textarea($ai_instructions); ?></textarea>
                    <p class="description"><?php esc_html_e('Optional context to guide how the AI inserts links', 'referral-link-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_link_maker_schedule_meta_box($post) {
        $schedule = get_post_meta($post->ID, '_rlm_schedule', true) ?: 'daily';
        $status = get_post_meta($post->ID, '_rlm_status', true) ?: 'draft';
        ?>
        <p>
            <label for="rlm_status"><strong><?php esc_html_e('Status', 'referral-link-manager'); ?></strong></label><br>
            <select id="rlm_status" name="rlm_status" class="widefat">
                <option value="draft" <?php selected($status, 'draft'); ?>><?php esc_html_e('Draft', 'referral-link-manager'); ?></option>
                <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'referral-link-manager'); ?></option>
                <option value="paused" <?php selected($status, 'paused'); ?>><?php esc_html_e('Paused', 'referral-link-manager'); ?></option>
            </select>
        </p>
        <p>
            <label for="rlm_schedule"><strong><?php esc_html_e('Schedule', 'referral-link-manager'); ?></strong></label><br>
            <select id="rlm_schedule" name="rlm_schedule" class="widefat">
                <option value="hourly" <?php selected($schedule, 'hourly'); ?>><?php esc_html_e('Every Hour', 'referral-link-manager'); ?></option>
                <option value="twicedaily" <?php selected($schedule, 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'referral-link-manager'); ?></option>
                <option value="daily" <?php selected($schedule, 'daily'); ?>><?php esc_html_e('Daily', 'referral-link-manager'); ?></option>
                <option value="weekly" <?php selected($schedule, 'weekly'); ?>><?php esc_html_e('Weekly', 'referral-link-manager'); ?></option>
            </select>
        </p>
        <?php
        $next_run = RLM_Cron_Handler::get_next_run_time($post->ID);
        if ($next_run && $status === 'active'): ?>
        <p>
            <strong><?php esc_html_e('Next Run:', 'referral-link-manager'); ?></strong><br>
            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($next_run))); ?>
        </p>
        <?php endif;
    }

    public static function render_link_maker_stats_meta_box($post) {
        $last_run = get_post_meta($post->ID, '_rlm_last_run', true);
        $total_processed = (int) get_post_meta($post->ID, '_rlm_total_processed', true);
        $pending_count = (int) get_post_meta($post->ID, '_rlm_pending_count', true);
        ?>
        <p>
            <strong><?php esc_html_e('Last Run:', 'referral-link-manager'); ?></strong><br>
            <?php echo $last_run ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_run))) : esc_html__('Never', 'referral-link-manager'); ?>
        </p>
        <p>
            <strong><?php esc_html_e('Total Processed:', 'referral-link-manager'); ?></strong><br>
            <?php echo esc_html($total_processed); ?> posts
        </p>
        <p>
            <strong><?php esc_html_e('Pending Approval:', 'referral-link-manager'); ?></strong><br>
            <?php if ($pending_count > 0): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rlm-pending&maker_id=' . $post->ID)); ?>">
                <?php echo esc_html($pending_count); ?> posts
            </a>
            <?php else: ?>
            0 posts
            <?php endif; ?>
        </p>
        <?php
    }

    public static function save_meta_boxes($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if ($post->post_type === 'referral_link') {
            if (!isset($_POST['rlm_referral_link_nonce']) || 
                !wp_verify_nonce($_POST['rlm_referral_link_nonce'], 'rlm_save_referral_link')) {
                return;
            }

            if (isset($_POST['rlm_referral_url'])) {
                update_post_meta($post_id, '_rlm_referral_url', esc_url_raw($_POST['rlm_referral_url']));
            }
        }

        if ($post->post_type === 'link_maker') {
            if (!isset($_POST['rlm_link_maker_nonce']) || 
                !wp_verify_nonce($_POST['rlm_link_maker_nonce'], 'rlm_save_link_maker')) {
                return;
            }

            $meta_fields = array(
                'rlm_categories'     => '_rlm_categories',
                'rlm_tags'           => '_rlm_tags',
                'rlm_authors'        => '_rlm_authors',
                'rlm_post_statuses'  => '_rlm_post_statuses',
                'rlm_link_group_ids' => '_rlm_link_group_ids',
            );

            foreach ($meta_fields as $field => $meta_key) {
                $value = isset($_POST[$field]) ? array_map('absint', (array) $_POST[$field]) : array();
                update_post_meta($post_id, $meta_key, $value);
            }

            if (isset($_POST['rlm_date_from'])) {
                update_post_meta($post_id, '_rlm_date_from', sanitize_text_field($_POST['rlm_date_from']));
            }
            if (isset($_POST['rlm_date_to'])) {
                update_post_meta($post_id, '_rlm_date_to', sanitize_text_field($_POST['rlm_date_to']));
            }
            if (isset($_POST['rlm_links_per_post'])) {
                update_post_meta($post_id, '_rlm_links_per_post', absint($_POST['rlm_links_per_post']));
            }
            if (isset($_POST['rlm_ai_instructions'])) {
                update_post_meta($post_id, '_rlm_ai_instructions', sanitize_textarea_field($_POST['rlm_ai_instructions']));
            }
            if (isset($_POST['rlm_schedule'])) {
                update_post_meta($post_id, '_rlm_schedule', sanitize_text_field($_POST['rlm_schedule']));
            }
            if (isset($_POST['rlm_status'])) {
                update_post_meta($post_id, '_rlm_status', sanitize_text_field($_POST['rlm_status']));
            }
        }
    }
}
