<?php
if (!defined('WPINC')) {
    die;
}

class YLBI_Bricks_Integration {
    public function __construct() {
        // Register condition group and options
        add_filter('bricks/conditions/groups', [$this, 'register_condition_group']);
        add_filter('bricks/conditions/options', [$this, 'register_condition_options']);
        add_filter('bricks/conditions/result', [$this, 'check_condition_result'], 10, 3);

        // Register dynamic tags
        add_filter('bricks/dynamic_tags_list', [$this, 'register_dynamic_tags']);
        
        // Frontend rendering hooks
        add_filter('bricks/dynamic_data/render_tag', [$this, 'render_dynamic_tag'], 10, 3);
        add_filter('bricks/dynamic_data/render_content', [$this, 'render_dynamic_content'], 10, 3);
        add_filter('bricks/frontend/render_data', [$this, 'render_dynamic_content'], 10, 3);
    }

    public function register_condition_group($groups) {
        $groups[] = [
            'name' => 'youtube_live',
            'label' => esc_html__('YouTube Live', 'youtube-live-bricks-integration'),
        ];
        return $groups;
    }

    public function register_condition_options($options) {
        $options[] = [
            'key' => 'youtube_channel_live',
            'label' => esc_html__('Channel Live Status', 'youtube-live-bricks-integration'),
            'group' => 'youtube_live',
            'compare' => [
                'type' => 'select',
                'options' => [
                    'live' => esc_html__('is live', 'youtube-live-bricks-integration'),
                    'not_live' => esc_html__('is not live', 'youtube-live-bricks-integration'),
                ],
                'placeholder' => esc_html__('is live', 'youtube-live-bricks-integration'),
            ],
        ];

        return $options;
    }

    public function check_condition_result($result, $condition_key, $condition) {
        if ($condition_key !== 'youtube_channel_live') {
            return $result;
        }

        $is_live = get_option('ylbi_is_live', false);
        $compare = isset($condition['compare']) ? $condition['compare'] : 'live';

        return $compare === 'live' ? $is_live : !$is_live;
    }

    public function register_dynamic_tags($tags) {
        $tags[] = [
            'name' => '{youtube_live_url}',
            'label' => esc_html__('YouTube Live URL', 'youtube-live-bricks-integration'),
            'group' => 'YouTube Live'
        ];

        $tags[] = [
            'name' => '{youtube_live_id}',
            'label' => esc_html__('YouTube Live Video ID', 'youtube-live-bricks-integration'),
            'group' => 'YouTube Live'
        ];

        return $tags;
    }

    /**
     * Render individual dynamic tag
     */
    public function render_dynamic_tag($tag, $post, $context = 'text') {
        if (!in_array($tag, ['{youtube_live_url}', '{youtube_live_id}'])) {
            return $tag;
        }

        $is_live = get_option('ylbi_is_live', false);
        
        // If not live, return empty string
        if (!$is_live) {
            return '';
        }

        switch ($tag) {
            case '{youtube_live_url}':
                return get_option('ylbi_live_video_url', '');
            case '{youtube_live_id}':
                return get_option('ylbi_live_video_id', '');
            default:
                return $tag;
        }
    }

    /**
     * Render dynamic content (for content containing multiple tags)
     */
    public function render_dynamic_content($content, $post = false, $context = 'text') {
        // Don't process if content doesn't contain our tags
        if (strpos($content, '{youtube_live') === false) {
            return $content;
        }

        $is_live = get_option('ylbi_is_live', false);
        $video_url = get_option('ylbi_live_video_url', '');
        $video_id = get_option('ylbi_live_video_id', '');

        // Replace URL tag
        $content = str_replace(
            '{youtube_live_url}',
            $is_live ? $video_url : '',
            $content
        );

        // Replace ID tag
        $content = str_replace(
            '{youtube_live_id}',
            $is_live ? $video_id : '',
            $content
        );

        return $content;
    }
}