<?php
if (!defined('ABSPATH')) {
    exit;
}

class RLM_AI_Processor {
    private $maker_id;
    private $maker_meta;
    private $available_links;

    public function __construct($maker_id) {
        $this->maker_id = $maker_id;
        $this->load_maker_meta();
        $this->load_available_links();
    }

    private function load_maker_meta() {
        $this->maker_meta = array(
            'categories'      => get_post_meta($this->maker_id, '_rlm_categories', true) ?: array(),
            'tags'            => get_post_meta($this->maker_id, '_rlm_tags', true) ?: array(),
            'authors'         => get_post_meta($this->maker_id, '_rlm_authors', true) ?: array(),
            'date_from'       => get_post_meta($this->maker_id, '_rlm_date_from', true),
            'date_to'         => get_post_meta($this->maker_id, '_rlm_date_to', true),
            'post_statuses'   => get_post_meta($this->maker_id, '_rlm_post_statuses', true) ?: array('publish'),
            'link_group_ids'  => get_post_meta($this->maker_id, '_rlm_link_group_ids', true) ?: array(),
            'links_per_post'  => (int) get_post_meta($this->maker_id, '_rlm_links_per_post', true) ?: 3,
            'ai_instructions' => get_post_meta($this->maker_id, '_rlm_ai_instructions', true),
            'schedule'        => get_post_meta($this->maker_id, '_rlm_schedule', true) ?: 'daily',
            'status'          => get_post_meta($this->maker_id, '_rlm_status', true) ?: 'draft',
        );
    }

    private function load_available_links() {
        $this->available_links = array();
        
        if (empty($this->maker_meta['link_group_ids'])) {
            return;
        }

        $args = array(
            'post_type'      => 'referral_link',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'referral_link_group',
                    'field'    => 'term_id',
                    'terms'    => $this->maker_meta['link_group_ids'],
                ),
            ),
        );

        $links = get_posts($args);
        
        foreach ($links as $link) {
            $this->available_links[] = array(
                'id'   => $link->ID,
                'name' => $link->post_title,
                'url'  => get_post_meta($link->ID, '_rlm_referral_url', true),
            );
        }
    }

    public function get_posts_to_process($limit = 10) {
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => $limit,
            'post_status'    => $this->maker_meta['post_statuses'],
            'meta_query'     => array(
                array(
                    'key'     => '_rlm_processed_by_' . $this->maker_id,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        if (!empty($this->maker_meta['categories'])) {
            $args['category__in'] = $this->maker_meta['categories'];
        }

        if (!empty($this->maker_meta['tags'])) {
            $args['tag__in'] = $this->maker_meta['tags'];
        }

        if (!empty($this->maker_meta['authors'])) {
            $args['author__in'] = $this->maker_meta['authors'];
        }

        if (!empty($this->maker_meta['date_from'])) {
            $args['date_query'][] = array(
                'after' => $this->maker_meta['date_from'],
            );
        }

        if (!empty($this->maker_meta['date_to'])) {
            $args['date_query'][] = array(
                'before' => $this->maker_meta['date_to'],
            );
        }

        return get_posts($args);
    }

    public function process_post($post) {
        if (!Referral_Link_Manager::check_ai_engine()) {
            return new WP_Error('ai_engine_missing', __('Meow Apps AI Engine is not installed or activated.', 'referral-link-manager'));
        }

        if (empty($this->available_links)) {
            return new WP_Error('no_links', __('No referral links available for this maker.', 'referral-link-manager'));
        }

        $content = $post->post_content;
        $links_info = $this->format_links_for_ai();
        
        $prompt = $this->build_ai_prompt($content, $links_info);
        
        $result = $this->call_ai_engine($prompt);
        
        if (is_wp_error($result)) {
            return $result;
        }

        $modified_content = $this->parse_ai_response($result, $content);
        $inserted_links = $this->extract_inserted_links($content, $modified_content);

        if (empty($inserted_links)) {
            update_post_meta($post->ID, '_rlm_processed_by_' . $this->maker_id, current_time('mysql'));
            return false;
        }

        $approval_data = array(
            'maker_id'         => $this->maker_id,
            'post_id'          => $post->ID,
            'original_content' => $content,
            'modified_content' => $modified_content,
            'inserted_links'   => $inserted_links,
        );

        $approval_id = RLM_Pending_Approvals::create($approval_data);
        
        update_post_meta($post->ID, '_rlm_processed_by_' . $this->maker_id, current_time('mysql'));
        
        return $approval_id;
    }

    private function format_links_for_ai() {
        $formatted = array();
        foreach ($this->available_links as $link) {
            $formatted[] = sprintf(
                '- %s: %s',
                $link['name'],
                $link['url']
            );
        }
        return implode("\n", $formatted);
    }

    private function build_ai_prompt($content, $links_info) {
        $links_per_post = $this->maker_meta['links_per_post'];
        $custom_instructions = $this->maker_meta['ai_instructions'];

        $prompt = "You are a content editor. Your task is to insert referral links into the following blog post content naturally and contextually.\n\n";
        $prompt .= "RULES:\n";
        $prompt .= "1. Insert up to {$links_per_post} referral links where they fit naturally.\n";
        $prompt .= "2. Only insert links where they make contextual sense.\n";
        $prompt .= "3. Use the product/service name as anchor text, not generic phrases.\n";
        $prompt .= "4. Do not insert links in headings or titles.\n";
        $prompt .= "5. Maintain the original HTML structure.\n";
        $prompt .= "6. Return ONLY the modified content, nothing else.\n\n";

        if (!empty($custom_instructions)) {
            $prompt .= "ADDITIONAL INSTRUCTIONS:\n{$custom_instructions}\n\n";
        }

        $prompt .= "AVAILABLE REFERRAL LINKS:\n{$links_info}\n\n";
        $prompt .= "ORIGINAL CONTENT:\n{$content}\n\n";
        $prompt .= "MODIFIED CONTENT:";

        return $prompt;
    }

    private function call_ai_engine($prompt) {
        if (function_exists('mwai_generate_text')) {
            try {
                $result = mwai_generate_text($prompt, array(
                    'max_tokens' => 2000,
                    'temperature' => 0.3,
                ));
                if (is_wp_error($result)) {
                    return $result;
                }
                return is_string($result) ? $result : '';
            } catch (Exception $e) {
                return new WP_Error('ai_engine_error', $e->getMessage());
            }
        }

        if (class_exists('Meow_MWAI_Core')) {
            try {
                $core = Meow_MWAI_Core::get_instance();
                if ($core) {
                    if (class_exists('Meow_MWAI_Query_Text')) {
                        $query = new Meow_MWAI_Query_Text($prompt);
                        $query->set_max_tokens(2000);
                        $result = $core->run_query($query);
                        if (!is_wp_error($result) && isset($result->result)) {
                            return $result->result;
                        }
                        return is_wp_error($result) ? $result : new WP_Error('ai_engine_error', __('Invalid response from AI Engine.', 'referral-link-manager'));
                    }
                    
                    if (method_exists($core, 'simpleTextQuery')) {
                        $result = $core->simpleTextQuery($prompt);
                        return is_string($result) ? $result : new WP_Error('ai_engine_error', __('Invalid response from AI Engine.', 'referral-link-manager'));
                    }
                }
            } catch (Exception $e) {
                return new WP_Error('ai_engine_error', $e->getMessage());
            }
        }

        return new WP_Error('ai_engine_not_found', __('Meow Apps AI Engine is not installed or not properly configured.', 'referral-link-manager'));
    }

    private function parse_ai_response($response, $original_content) {
        $response = trim($response);
        
        if (empty($response) || strlen($response) < strlen($original_content) * 0.5) {
            return $original_content;
        }

        return $response;
    }

    private function extract_inserted_links($original, $modified) {
        $inserted = array();
        
        foreach ($this->available_links as $link) {
            $pattern = '/<a[^>]*href=["\']' . preg_quote($link['url'], '/') . '["\'][^>]*>([^<]+)<\/a>/i';
            
            if (preg_match($pattern, $modified, $matches) && !preg_match($pattern, $original)) {
                $inserted[] = array(
                    'link_id'     => $link['id'],
                    'link_name'   => $link['name'],
                    'link_url'    => $link['url'],
                    'anchor_text' => $matches[1],
                );
            }
        }

        return $inserted;
    }

    public function run() {
        if ($this->maker_meta['status'] !== 'active') {
            return;
        }

        $posts = $this->get_posts_to_process();
        $processed = 0;
        $pending = 0;

        foreach ($posts as $post) {
            $result = $this->process_post($post);
            
            if (is_wp_error($result)) {
                error_log('RLM Error processing post ' . $post->ID . ': ' . $result->get_error_message());
                continue;
            }

            $processed++;
            if ($result) {
                $pending++;
            }
        }

        update_post_meta($this->maker_id, '_rlm_last_run', current_time('mysql'));
        
        $total_processed = (int) get_post_meta($this->maker_id, '_rlm_total_processed', true);
        update_post_meta($this->maker_id, '_rlm_total_processed', $total_processed + $processed);

        return array(
            'processed' => $processed,
            'pending'   => $pending,
        );
    }
}
