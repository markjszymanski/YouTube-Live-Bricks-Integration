<?php
/**
 * YouTube Live Bricks Integration
 *
 * @package           YoutubeliveBricksIntegration
 * @author            Mark Szymanski & Claude
 * @copyright         2024 Mark Szymanski & Claude
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       YouTube Live Bricks Integration
 * Plugin URI:        https://markszymanski.co
 * Description:       Integrates YouTube Live status with Bricks Builder, providing live stream conditions and dynamic tags.
 * Version:          1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:           Mark Szymanski & Claude
 * Author URI:        https://markszymanski.co
 * Text Domain:       youtube-live-bricks-integration
 * License:          GPL v2 or later
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version and paths
define('YLBI_VERSION', '1.0.0');
define('YLBI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YLBI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files
require_once YLBI_PLUGIN_DIR . 'admin/class-admin-menu.php';
require_once YLBI_PLUGIN_DIR . 'includes/class-youtube-api.php';
require_once YLBI_PLUGIN_DIR . 'includes/class-encryption.php';
require_once YLBI_PLUGIN_DIR . 'includes/class-bricks-integration.php';
require_once YLBI_PLUGIN_DIR . 'includes/class-cron-manager.php';

// Initialize Admin Menu
function ylbi_init_admin_menu() {
    global $ylbi_admin;
    $ylbi_admin = new YLBI_Admin_Menu();
}
add_action('plugins_loaded', 'ylbi_init_admin_menu');

// Initialize Bricks Integration
function ylbi_init_bricks_integration() {
    global $ylbi_bricks;
    $ylbi_bricks = new YLBI_Bricks_Integration();
}
add_action('init', 'ylbi_init_bricks_integration', 20);

// Initialize YouTube API and Cron
function ylbi_init_core() {
    new YLBI_YouTube_API();
    new YLBI_Cron_Manager();
}
add_action('init', 'ylbi_init_core');

// Activation Hook
function ylbi_activate() {
    // Create logs directory
    $logs_dir = YLBI_PLUGIN_DIR . 'logs';
    if (!file_exists($logs_dir)) {
        wp_mkdir_p($logs_dir);
    }

    // Set default options
    $default_options = array(
        'check_frequency' => '300',
        'last_check_time' => '',
        'is_live' => false,
        'live_video_url' => '',
        'live_video_id' => '',
    );

    foreach ($default_options as $option => $value) {
        if (get_option('ylbi_' . $option) === false) {
            add_option('ylbi_' . $option, $value);
        }
    }
}
register_activation_hook(__FILE__, 'ylbi_activate');

// Deactivation Hook
function ylbi_deactivate() {
    wp_clear_scheduled_hook('ylbi_check_youtube_status');
}
register_deactivation_hook(__FILE__, 'ylbi_deactivate');

// Error Logging Function
function ylbi_log_error($message, $type = 'ERROR') {
    $log_file = YLBI_PLUGIN_DIR . 'logs/error.log';
    $timestamp = current_time('Y-m-d H:i:s');
    $log_message = sprintf("[%s] %s: %s\n", $timestamp, $type, $message);
    error_log($log_message, 3, $log_file);
}
