<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Pending_Approvals {
    private static function table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    }

    public static function create($data) {
        if (!self::table_exists()) {
            return new WP_Error('table_missing', __('Pending approvals table does not exist. Please deactivate and reactivate the plugin.', 'referral-link-manager'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        $result = $wpdb->insert($table, array(
            'maker_id'         => $data['maker_id'],
            'post_id'          => $data['post_id'],
            'original_content' => $data['original_content'],
            'modified_content' => $data['modified_content'],
            'inserted_links'   => wp_json_encode($data['inserted_links']),
            'status'           => 'pending',
            'created_at'       => current_time('mysql'),
        ), array('%d', '%d', '%s', '%s', '%s', '%s', '%s'));

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create pending approval.', 'referral-link-manager'));
        }

        self::update_maker_pending_count($data['maker_id']);

        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!self::table_exists()) {
            return null;
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);

        if ($row) {
            $row['inserted_links'] = json_decode($row['inserted_links'], true);
        }

        return $row;
    }

    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!self::table_exists()) {
            return array();
        }

        $defaults = array(
            'maker_id' => null,
            'status'   => null,
            'limit'    => 50,
            'offset'   => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['maker_id']) {
            $where[] = 'maker_id = %d';
            $values[] = $args['maker_id'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        $results = $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);

        foreach ($results as &$row) {
            $row['inserted_links'] = json_decode($row['inserted_links'], true);
        }

        return $results;
    }

    public static function approve($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!current_user_can('edit_posts')) {
            return new WP_Error('permission_denied', __('You do not have permission to approve changes.', 'referral-link-manager'));
        }

        $approval = self::get($id);
        
        if (!$approval) {
            return new WP_Error('not_found', __('Approval not found.', 'referral-link-manager'));
        }

        if ($approval['status'] !== 'pending') {
            return new WP_Error('already_processed', __('This approval has already been processed.', 'referral-link-manager'));
        }

        $post = get_post($approval['post_id']);
        if (!$post) {
            return new WP_Error('post_not_found', __('The original post no longer exists.', 'referral-link-manager'));
        }

        if (!current_user_can('edit_post', $approval['post_id'])) {
            return new WP_Error('permission_denied', __('You do not have permission to edit this post.', 'referral-link-manager'));
        }

        $result = wp_update_post(array(
            'ID'           => $approval['post_id'],
            'post_content' => $approval['modified_content'],
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        foreach ($approval['inserted_links'] as $link) {
            $usage_count = (int) get_post_meta($link['link_id'], '_rlm_usage_count', true);
            update_post_meta($link['link_id'], '_rlm_usage_count', $usage_count + 1);
        }

        $wpdb->update($table, 
            array('status' => 'approved'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        self::update_maker_pending_count($approval['maker_id']);

        return true;
    }

    public static function reject($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!current_user_can('edit_posts')) {
            return new WP_Error('permission_denied', __('You do not have permission to reject changes.', 'referral-link-manager'));
        }

        $approval = self::get($id);
        
        if (!$approval) {
            return new WP_Error('not_found', __('Approval not found.', 'referral-link-manager'));
        }

        if ($approval['status'] !== 'pending') {
            return new WP_Error('already_processed', __('This approval has already been processed.', 'referral-link-manager'));
        }

        $result = $wpdb->update($table, 
            array('status' => 'rejected'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update approval status.', 'referral-link-manager'));
        }

        self::update_maker_pending_count($approval['maker_id']);

        return true;
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!self::table_exists()) {
            return false;
        }

        $approval = self::get($id);
        
        if ($approval) {
            $wpdb->delete($table, array('id' => $id), array('%d'));
            self::update_maker_pending_count($approval['maker_id']);
            return true;
        }

        return false;
    }

    public static function get_pending_count($maker_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'rlm_pending_approvals';

        if (!self::table_exists()) {
            return 0;
        }

        if ($maker_id) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE maker_id = %d AND status = 'pending'",
                $maker_id
            ));
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status = 'pending'"
        );
    }

    private static function update_maker_pending_count($maker_id) {
        $count = self::get_pending_count($maker_id);
        update_post_meta($maker_id, '_rlm_pending_count', $count);
    }
}
