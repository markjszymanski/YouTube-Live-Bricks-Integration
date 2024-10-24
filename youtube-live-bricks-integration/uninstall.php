<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Options to remove
$options = array(
    'ylbi_youtube_api_key',
    'ylbi_channel_id',
    'ylbi_check_frequency',
    'ylbi_last_check_time',
    'ylbi_is_live',
    'ylbi_live_video_url',
    'ylbi_live_video_id',
    'ylbi_encryption_key'
);

// Remove all plugin options
foreach ($options as $option) {
    delete_option($option);
}

// Remove logs directory and its contents
$logs_dir = plugin_dir_path(__FILE__) . 'logs';
if (is_dir($logs_dir)) {
    $files = glob($logs_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($logs_dir);
}
