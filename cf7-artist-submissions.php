<?php
/**
 * Plugin Name: CF7 Artist Submissions
 * Description: Store and manage Contact Form 7 submissions from artists.
 * Version: 1.0.0
 * Author: Pup and Tiger
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 6.8.2
 * Text Domain: cf7-artist-submissions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF7_ARTIST_SUBMISSIONS_VERSION', '1.0.0');
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_FILE', __FILE__);

// Include required files
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-post-type.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-form-handler.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-admin.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-settings.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-action-log.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-emails.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-conversations.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-tabs.php';

// Initialize the plugin
function cf7_artist_submissions_init() {
    // Check if Contact Form 7 is active
    if (!class_exists('WPCF7')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>CF7 Artist Submissions requires Contact Form 7 to be installed and active.</p></div>';
        });
        return;
    }
    
    $post_type = new CF7_Artist_Submissions_Post_Type();
    $post_type->init();
    
    $form_handler = new CF7_Artist_Submissions_Form_Handler();
    $form_handler->init();
    
    $admin = new CF7_Artist_Submissions_Admin();
    $admin->init();
    
    $settings = new CF7_Artist_Submissions_Settings();
    $settings->init();
    
    // Initialize Action Log
    CF7_Artist_Submissions_Action_Log::init();
    
    // Initialize Email System
    $emails = new CF7_Artist_Submissions_Emails();
    $emails->init();
    
    // Initialize Conversation System
    CF7_Artist_Submissions_Conversations::init();
    
    // Initialize Tabbed Interface
    CF7_Artist_Submissions_Tabs::init();
}

add_action('plugins_loaded', 'cf7_artist_submissions_init');

// Add custom cron schedule for every 5 minutes
add_filter('cron_schedules', 'cf7_artist_submissions_cron_schedules');
function cf7_artist_submissions_cron_schedules($schedules) {
    $schedules['every_5_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display' => __('Every 5 Minutes', 'cf7-artist-submissions')
    );
    return $schedules;
}

// Plugin activation
register_activation_hook(__FILE__, 'cf7_artist_submissions_activate');
function cf7_artist_submissions_activate() {
    // Create custom post type
    $post_type = new CF7_Artist_Submissions_Post_Type();
    $post_type->register_post_type();
    $post_type->register_taxonomy();
    
    // Create action log table
    CF7_Artist_Submissions_Action_Log::create_log_table();
    
    // Create conversations table
    CF7_Artist_Submissions_Conversations::create_conversations_table();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'cf7_artist_submissions_deactivate');
function cf7_artist_submissions_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}