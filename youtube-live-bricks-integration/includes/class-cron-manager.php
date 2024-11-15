<?php
/**
 * Handle WP Cron scheduling for YouTube status checks
 */
class YLBI_Cron_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register custom intervals
        add_filter('cron_schedules', array($this, 'register_cron_schedules'));
        
        // Hook for running the check
        add_action('ylbi_check_youtube_status', array($this, 'run_status_check'));
        
        // Hook for scheduling/rescheduling
        add_action('ylbi_schedule_status_check', array($this, 'schedule_status_check'));
        
        // Hook into frequency option changes
        add_action('update_option_ylbi_check_frequency', array($this, 'reschedule_on_frequency_change'), 10, 2);
    }

    /**
     * Register custom cron intervals
     */
    public function register_cron_schedules($schedules) {
        // Add new shorter intervals
        $schedules['ylbi_1min'] = array(
            'interval' => 60,
            'display' => __('Every minute', 'youtube-live-bricks-integration')
        );
        
        $schedules['ylbi_2min'] = array(
            'interval' => 120,
            'display' => __('Every 2 minutes', 'youtube-live-bricks-integration')
        );
        
        // Existing intervals
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
            ylbi_log_error('Scheduled check failed: ' . $result['message']);
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
        
        // Schedule new recurring check
        if (!wp_next_scheduled('ylbi_check_youtube_status')) {
            wp_schedule_event(time(), $schedule, 'ylbi_check_youtube_status');
        }
    }

    /**
     * Reschedule when frequency is changed
     */
    public function reschedule_on_frequency_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            $this->schedule_status_check();
        }
    }

    /**
     * Get schedule name based on frequency
     */
    private function get_schedule_name($frequency) {
        switch ($frequency) {
            case '60':
                return 'ylbi_1min';
            case '120':
                return 'ylbi_2min';
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