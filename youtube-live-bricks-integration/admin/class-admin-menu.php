<?php
if (!defined('WPINC')) {
    die;
}

class YLBI_Admin_Menu {
    private $encryption;

    public function __construct() {
        $this->encryption = new YLBI_Encryption();
        add_action('admin_init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_ylbi_check_status', [$this, 'ajax_check_status']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function init() {
        // Any additional initialization
    }

    public function add_admin_menu() {
        add_menu_page(
            'YouTube Live Settings',
            'YouTube Live',
            'manage_options',
            'youtube-live-settings',
            [$this, 'render_settings_page'],
            'dashicons-video-alt3',
            30
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_youtube-live-settings') {
            return;
        }

        wp_enqueue_style(
            'ylbi-admin-styles',
            YLBI_PLUGIN_URL . 'admin/css/admin-styles.css',
            [],
            YLBI_VERSION
        );

        wp_enqueue_script(
            'ylbi-admin-script',
            YLBI_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery'],
            YLBI_VERSION,
            true
        );

        wp_localize_script('ylbi-admin-script', 'ylbiAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ylbi_admin_nonce')
        ]);
    }

    public function render_settings_page() {
        // Check if settings were saved
        if (isset($_POST['ylbi_save_settings']) && check_admin_referer('ylbi_settings_nonce')) {
            $this->save_settings();
        }

        $channel_id = get_option('ylbi_channel_id', '');
        $check_frequency = get_option('ylbi_check_frequency', '300');
        $youtube_api = new YLBI_YouTube_API();
        $current_status = $youtube_api->get_current_status();
        ?>
        <div class="wrap ylbi-settings-wrap">
            <h1><?php _e('YouTube Live Integration Settings', 'youtube-live-bricks-integration'); ?></h1>

            <?php settings_errors('ylbi_messages'); ?>

            <div class="ylbi-status-panel">
                <h2><?php _e('Current Status', 'youtube-live-bricks-integration'); ?></h2>
                <div id="ylbi-status-display">
                    <p class="status-indicator">
                        <span class="status-dot <?php echo $current_status['is_live'] ? 'live' : 'offline'; ?>"></span>
                        <?php
                        echo $current_status['is_live'] 
                            ? __('Channel is LIVE', 'youtube-live-bricks-integration')
                            : __('Channel is OFFLINE', 'youtube-live-bricks-integration');
                        ?>
                    </p>
                    <?php if ($current_status['last_check']): ?>
                        <p class="last-check">
                            <?php 
                            printf(
                                __('Last checked: %s', 'youtube-live-bricks-integration'),
                                date_i18n('Y-m-d H:i:s', $current_status['last_check'])
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <!-- Added Debug Info Section -->
                    <div class="ylbi-debug-info">
                        <h3><?php _e('Current Dynamic Tag Values:', 'youtube-live-bricks-integration'); ?></h3>
                        <table class="widefat" style="margin-top: 10px;">
                            <tbody>
                                <tr>
                                    <td><strong>{youtube_live_url}</strong></td>
                                    <td><?php echo esc_html(get_option('ylbi_live_video_url', 'Not set')); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>{youtube_live_id}</strong></td>
                                    <td><?php echo esc_html(get_option('ylbi_live_video_id', 'Not set')); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="ylbi-check-now" class="button button-secondary">
                        <?php _e('Check Now', 'youtube-live-bricks-integration'); ?>
                    </button>
                </div>
            </div>

            <form method="post" action="" class="ylbi-settings-form">
                <?php wp_nonce_field('ylbi_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="channel_id">
                                <?php _e('YouTube Channel ID', 'youtube-live-bricks-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   id="channel_id"
                                   name="ylbi_channel_id"
                                   value="<?php echo esc_attr($channel_id); ?>"
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php _e('Enter your YouTube channel ID (e.g., UCxxxxxxxxxx)', 'youtube-live-bricks-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="api_key">
                                <?php _e('YouTube API Key', 'youtube-live-bricks-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password"
                                   id="api_key"
                                   name="ylbi_api_key"
                                   class="regular-text"
                                   placeholder="<?php echo get_option('ylbi_youtube_api_key') ? '••••••••' : ''; ?>"
                            >
                            <p class="description">
                                <?php _e('Enter your YouTube Data API v3 key. Leave blank to keep existing key.', 'youtube-live-bricks-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="check_frequency">
                                <?php _e('Check Frequency', 'youtube-live-bricks-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="check_frequency" name="ylbi_check_frequency">
                                <option value="60" <?php selected($check_frequency, '60'); ?>>
                                    <?php _e('Every minute', 'youtube-live-bricks-integration'); ?>
                                </option>
                                <option value="120" <?php selected($check_frequency, '120'); ?>>
                                    <?php _e('Every 2 minutes', 'youtube-live-bricks-integration'); ?>
                                </option>
                                <option value="300" <?php selected($check_frequency, '300'); ?>>
                                    <?php _e('Every 5 minutes', 'youtube-live-bricks-integration'); ?>
                                </option>
                                <option value="600" <?php selected($check_frequency, '600'); ?>>
                                    <?php _e('Every 10 minutes', 'youtube-live-bricks-integration'); ?>
                                </option>
                                <option value="1800" <?php selected($check_frequency, '1800'); ?>>
                                    <?php _e('Every 30 minutes', 'youtube-live-bricks-integration'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('More frequent checks provide faster live status updates but increase server load.', 'youtube-live-bricks-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit"
                           name="ylbi_save_settings"
                           class="button button-primary"
                           value="<?php _e('Save Settings', 'youtube-live-bricks-integration'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    private function save_settings() {
        // Validate and save Channel ID
        $channel_id = sanitize_text_field($_POST['ylbi_channel_id']);
        update_option('ylbi_channel_id', $channel_id);

        // Handle API Key
        if (!empty($_POST['ylbi_api_key'])) {
            $api_key = sanitize_text_field($_POST['ylbi_api_key']);
            $encrypted_key = $this->encryption->encrypt($api_key);
            update_option('ylbi_youtube_api_key', $encrypted_key);
        }

        // Save check frequency
        $frequency = sanitize_text_field($_POST['ylbi_check_frequency']);
        update_option('ylbi_check_frequency', $frequency);

        // Trigger immediate check with new settings
        $youtube_api = new YLBI_YouTube_API();
        $result = $youtube_api->check_live_status();

        if (!$result['success']) {
            add_settings_error(
                'ylbi_messages',
                'ylbi_error',
                $result['message'],
                'error'
            );
        } else {
            add_settings_error(
                'ylbi_messages',
                'ylbi_success',
                __('Settings saved successfully.', 'youtube-live-bricks-integration'),
                'success'
            );
        }

        // Reschedule cron
        do_action('ylbi_schedule_status_check');
    }

    public function ajax_check_status() {
        check_ajax_referer('ylbi_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $youtube_api = new YLBI_YouTube_API();
        $result = $youtube_api->check_live_status();

        if ($result['success']) {
            wp_send_json_success([
                'is_live' => $result['is_live'],
                'message' => $result['message'],
                'last_check' => date_i18n('Y-m-d H:i:s', get_option('ylbi_last_check_time'))
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}