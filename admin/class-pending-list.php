<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class RLM_Pending_List extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => 'pending_approval',
            'plural'   => 'pending_approvals',
            'ajax'     => false,
        ));
    }

    public function get_columns() {
        return array(
            'cb'             => '<input type="checkbox" />',
            'post_title'     => __('Post Title', 'referral-link-manager'),
            'links_inserted' => __('Links Inserted', 'referral-link-manager'),
            'maker'          => __('Link Maker', 'referral-link-manager'),
            'created_at'     => __('Date', 'referral-link-manager'),
        );
    }

    public function get_sortable_columns() {
        return array(
            'post_title' => array('post_title', false),
            'created_at' => array('created_at', true),
        );
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="approval_ids[]" value="%d" />',
            $item['id']
        );
    }

    public function column_post_title($item) {
        $post = get_post($item['post_id']);
        $title = $post ? $post->post_title : __('(Post deleted)', 'referral-link-manager');

        $actions = array(
            'view'    => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=rlm-pending&action=view&id=' . $item['id'])),
                __('View Changes', 'referral-link-manager')
            ),
            'approve' => sprintf(
                '<a href="%s" style="color: green;">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=rlm-pending&action=approve&id=' . $item['id']), 'rlm_approve_' . $item['id'])),
                __('Approve', 'referral-link-manager')
            ),
            'reject'  => sprintf(
                '<a href="%s" style="color: red;">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=rlm-pending&action=reject&id=' . $item['id']), 'rlm_reject_' . $item['id'])),
                __('Reject', 'referral-link-manager')
            ),
        );

        return sprintf('%s %s', esc_html($title), $this->row_actions($actions));
    }

    public function column_links_inserted($item) {
        $links = $item['inserted_links'];
        $count = count($links);
        
        $output = sprintf('<strong>%d</strong> link%s', $count, $count !== 1 ? 's' : '');
        
        if ($count > 0) {
            $output .= '<br><small>';
            $link_names = array_map(function($link) {
                return esc_html($link['link_name']);
            }, array_slice($links, 0, 3));
            $output .= implode(', ', $link_names);
            if ($count > 3) {
                $output .= sprintf(' +%d more', $count - 3);
            }
            $output .= '</small>';
        }

        return $output;
    }

    public function column_maker($item) {
        $maker = get_post($item['maker_id']);
        return $maker ? esc_html($maker->post_title) : __('(Unknown)', 'referral-link-manager');
    }

    public function column_created_at($item) {
        return esc_html(date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($item['created_at'])
        ));
    }

    public function get_bulk_actions() {
        return array(
            'approve' => __('Approve', 'referral-link-manager'),
            'reject'  => __('Reject', 'referral-link-manager'),
            'delete'  => __('Delete', 'referral-link-manager'),
        );
    }

    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $args = array(
            'status' => 'pending',
            'limit'  => $per_page,
            'offset' => ($current_page - 1) * $per_page,
        );

        if (isset($_GET['maker_id'])) {
            $args['maker_id'] = absint($_GET['maker_id']);
        }

        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        
        $args['orderby'] = $orderby;
        $args['order'] = $order;

        $this->items = RLM_Pending_Approvals::get_all($args);

        $total_items = RLM_Pending_Approvals::get_pending_count(
            isset($args['maker_id']) ? $args['maker_id'] : null
        );

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }
}
