<?php
/**
 * Handle WP Cron scheduling for YouTube status checks
 */
class YLBI_Cron_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_cron_schedules'));
        add_action('ylbi_check_youtube_status', array($this, 'run_status_check'));
        add_action('ylbi_schedule_status_check', array($this, 'schedule_status_check'));
        add_action('update_option_ylbi_check_frequency', array($this, 'reschedule_on_frequency_change'), 10, 2);
    }

    /**
     * Register custom cron intervals
     *
     * @param array $schedules
     * @return array
     */
    public function register_cron_schedules($schedules) {
        $schedules['ylbi_5min'] = array(
            'interval' => 300,
            'display' => __('Every 5 minutes', 'youtube-live-bricks-integration')
        );
        
        $schedules['ylbi_10min'] = array(
            'interval' => 600,
            'display' => __('Every 10 minutes', 'youtube-live-bricks-integration')
        );
        
        $schedules['ylbi_30min'] = array(
            'interval' => 1800,
            'display' => __('Every 30 minutes', 'youtube-live-bricks-integration')
        );
        
        return $schedules;
    }

    /**
     * Run the YouTube status check
     */
    public function run_status_check() {
        $youtube_api = new YLBI_YouTube_API();
        $result = $youtube_api->check_live_status();
        
        if (!$result['success']) {
            YouTube_Live_Bricks_Integration::log_error('Scheduled check failed: ' . $result['message']);
        }
    }

    /**
     * Schedule the status check based on settings
     */
    public function schedule_status_check() {
        $frequency = get_option('ylbi_check_frequency', '300');
        $schedule = $this->get_schedule_name($frequency);
        
        // Clear any existing schedule
        wp_clear_scheduled_hook('ylbi_check_youtube_status');
        
        // Schedule new check
        if (!wp_next_scheduled('ylbi_check_youtube_status')) {
            wp_schedule_event(time(), $schedule, 'ylbi_check_youtube_status');
        }
    }

    /**
     * Reschedule when frequency is changed
     *
     * @param mixed $old_value
     * @param mixed $new_value
     */
    public function reschedule_on_frequency_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            $this->schedule_status_check();
        }
    }

    /**
     * Get schedule name based on frequency
     *
     * @param string $frequency Frequency in seconds
     * @return string
     */
    private function get_schedule_name($frequency) {
        switch ($frequency) {
            case '300':
                return 'ylbi_5min';
            case '600':
                return 'ylbi_10min';
            case '1800':
                return 'ylbi_30min';
            default:
                return 'ylbi_5min';
        }
    }
}
