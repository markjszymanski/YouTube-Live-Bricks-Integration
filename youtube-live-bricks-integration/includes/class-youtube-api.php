<?php

class YLBI_YouTube_API {
    private $api_key;
    private $channel_id;
    private $encryption;

    public function __construct() {
        $this->encryption = new YLBI_Encryption();
        $this->init();
    }

    private function init() {
        $encrypted_key = get_option('ylbi_youtube_api_key');
        $this->api_key = $encrypted_key ? $this->encryption->decrypt($encrypted_key) : '';
        $this->channel_id = get_option('ylbi_channel_id');
    }

    public function check_live_status() {
        if (empty($this->api_key) || empty($this->channel_id)) {
            return $this->handle_error('API key or Channel ID not set');
        }

        $search_endpoint = 'https://www.googleapis.com/youtube/v3/search';
        $query_args = array(
            'key' => $this->api_key,
            'channelId' => $this->channel_id,
            'eventType' => 'live',
            'type' => 'video',
            'part' => 'id,snippet',
            'maxResults' => 1
        );

        $response = wp_remote_get(add_query_arg($query_args, $search_endpoint));

        if (is_wp_error($response)) {
            return $this->handle_error('API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->error)) {
            return $this->handle_error('YouTube API error: ' . $data->error->message);
        }

        // Update last check time
        update_option('ylbi_last_check_time', current_time('timestamp'));

        // Clear previous status
        $this->update_live_status(false, '', '');

        // Check if any live streams found
        if (empty($data->items)) {
            return array(
                'success' => true,
                'is_live' => false,
                'message' => 'Channel is not currently live'
            );
        }

        // Get the live video details
        $video = $data->items[0];
        $video_id = $video->id->videoId;
        $video_url = "https://www.youtube.com/watch?v={$video_id}";

        // Update stored status
        $this->update_live_status(true, $video_url, $video_id);

        return array(
            'success' => true,
            'is_live' => true,
            'video_url' => $video_url,
            'video_id' => $video_id,
            'message' => 'Channel is currently live'
        );
    }

    private function update_live_status($is_live, $video_url = '', $video_id = '') {
        update_option('ylbi_is_live', $is_live);
        update_option('ylbi_live_video_url', $video_url);
        update_option('ylbi_live_video_id', $video_id);
    }

    private function handle_error($message) {
        ylbi_log_error($message);
        return array(
            'success' => false,
            'is_live' => false,
            'video_url' => '',
            'video_id' => '',
            'message' => $message
        );
    }

    public function get_current_status() {
        return array(
            'is_live' => get_option('ylbi_is_live', false),
            'video_url' => get_option('ylbi_live_video_url', ''),
            'video_id' => get_option('ylbi_live_video_id', ''),
            'last_check' => get_option('ylbi_last_check_time', '')
        );
    }
}