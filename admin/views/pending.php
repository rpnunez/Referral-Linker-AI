<?php
if (!defined('ABSPATH')) {
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize_text_field($_GET['action']);
    $id = absint($_GET['id']);

    if ($action === 'approve' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'rlm_approve_' . $id)) {
        $result = RLM_Pending_Approvals::approve($id);
        if (!is_wp_error($result)) {
            add_settings_error('rlm_pending', 'approved', __('Post approved and updated.', 'referral-link-manager'), 'success');
        } else {
            add_settings_error('rlm_pending', 'error', $result->get_error_message(), 'error');
        }
    }

    if ($action === 'reject' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'rlm_reject_' . $id)) {
        $result = RLM_Pending_Approvals::reject($id);
        if (!is_wp_error($result)) {
            add_settings_error('rlm_pending', 'rejected', __('Changes rejected.', 'referral-link-manager'), 'success');
        } else {
            add_settings_error('rlm_pending', 'error', $result->get_error_message(), 'error');
        }
    }

    if ($action === 'view') {
        $approval = RLM_Pending_Approvals::get($id);
        if ($approval):
            $post = get_post($approval['post_id']);
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rlm-pending')); ?>">&larr;</a>
                <?php esc_html_e('Review Changes', 'referral-link-manager'); ?>: <?php echo esc_html($post ? $post->post_title : '(Deleted)'); ?>
            </h1>
            
            <div class="rlm-review-panel">
                <div class="rlm-review-header">
                    <div class="rlm-review-meta">
                        <strong><?php esc_html_e('Inserted Links:', 'referral-link-manager'); ?></strong>
                        <?php foreach ($approval['inserted_links'] as $link): ?>
                        <span class="rlm-link-badge">
                            <?php echo esc_html($link['link_name']); ?>
                            <small>(<?php echo esc_html($link['anchor_text']); ?>)</small>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="rlm-review-actions">
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=rlm-pending&action=approve&id=' . $id), 'rlm_approve_' . $id)); ?>" class="button button-primary">
                            <?php esc_html_e('Approve & Publish', 'referral-link-manager'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=rlm-pending&action=reject&id=' . $id), 'rlm_reject_' . $id)); ?>" class="button">
                            <?php esc_html_e('Reject Changes', 'referral-link-manager'); ?>
                        </a>
                    </div>
                </div>

                <div class="rlm-diff-panel">
                    <div class="rlm-diff-column">
                        <h3><?php esc_html_e('Modified Content', 'referral-link-manager'); ?></h3>
                        <div class="rlm-content-preview rlm-modified">
                            <?php echo wp_kses_post($approval['modified_content']); ?>
                        </div>
                    </div>
                    <div class="rlm-diff-column">
                        <h3><?php esc_html_e('Original Content', 'referral-link-manager'); ?></h3>
                        <div class="rlm-content-preview rlm-original">
                            <?php echo wp_kses_post($approval['original_content']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return;
        endif;
    }
}

if (isset($_POST['action']) && isset($_POST['approval_ids'])) {
    check_admin_referer('bulk-pending_approvals');
    $action = sanitize_text_field($_POST['action']);
    $ids = array_map('absint', $_POST['approval_ids']);
    
    foreach ($ids as $id) {
        if ($action === 'approve') {
            RLM_Pending_Approvals::approve($id);
        } elseif ($action === 'reject') {
            RLM_Pending_Approvals::reject($id);
        } elseif ($action === 'delete') {
            RLM_Pending_Approvals::delete($id);
        }
    }
    
    add_settings_error('rlm_pending', 'bulk_action', sprintf(__('%d items processed.', 'referral-link-manager'), count($ids)), 'success');
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Pending Approval', 'referral-link-manager'); ?></h1>
    
    <?php settings_errors('rlm_pending'); ?>
    
    <form method="post">
        <?php
        $pending_list->prepare_items();
        $pending_list->display();
        ?>
    </form>
</div>
