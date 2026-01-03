<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_Cron_Handler {
    const CRON_HOOK = 'rlm_process_makers';

    public static function init() {
        add_action(self::CRON_HOOK, array(__CLASS__, 'process_active_makers'));
    }

    public static function schedule_events() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }
    }

    public static function clear_scheduled_events() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    public static function process_active_makers() {
        $makers = get_posts(array(
            'post_type'      => 'link_maker',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_rlm_status',
                    'value' => 'active',
                ),
            ),
        ));

        foreach ($makers as $maker) {
            $schedule = get_post_meta($maker->ID, '_rlm_schedule', true) ?: 'daily';
            $last_run = get_post_meta($maker->ID, '_rlm_last_run', true);
            
            if (self::should_run($schedule, $last_run)) {
                self::run_maker($maker->ID);
            }
        }
    }

    private static function should_run($schedule, $last_run) {
        if (empty($last_run)) {
            return true;
        }

        $last_run_time = strtotime($last_run);
        $current_time = current_time('timestamp');
        $diff = $current_time - $last_run_time;

        switch ($schedule) {
            case 'hourly':
                return $diff >= HOUR_IN_SECONDS;
            case 'twicedaily':
                return $diff >= (12 * HOUR_IN_SECONDS);
            case 'daily':
                return $diff >= DAY_IN_SECONDS;
            case 'weekly':
                return $diff >= WEEK_IN_SECONDS;
            default:
                return $diff >= DAY_IN_SECONDS;
        }
    }

    public static function run_maker($maker_id) {
        $processor = new RLM_AI_Processor($maker_id);
        return $processor->run();
    }

    public static function get_next_run_time($maker_id) {
        $schedule = get_post_meta($maker_id, '_rlm_schedule', true) ?: 'daily';
        $last_run = get_post_meta($maker_id, '_rlm_last_run', true);

        if (empty($last_run)) {
            return current_time('mysql');
        }

        $last_run_time = strtotime($last_run);

        switch ($schedule) {
            case 'hourly':
                $next = $last_run_time + HOUR_IN_SECONDS;
                break;
            case 'twicedaily':
                $next = $last_run_time + (12 * HOUR_IN_SECONDS);
                break;
            case 'daily':
                $next = $last_run_time + DAY_IN_SECONDS;
                break;
            case 'weekly':
                $next = $last_run_time + WEEK_IN_SECONDS;
                break;
            default:
                $next = $last_run_time + DAY_IN_SECONDS;
        }

        return date('Y-m-d H:i:s', $next);
    }
}
