<?php
/**
 * Plugin Name: CF7 Artist Submissions
 * Description: Professional artist submission management system with modern dashboard, advanced field editing, task management, and conversation system for Contact Form 7.
 * Version: 2.0.0
 * Author: Pup and Tiger
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 6.8.2
 * Text Domain: cf7-artist-submissions
 * License: GPL v2 or later
 * Network: false
 *
 * @package CF7_Artist_Submissions
 * @version 2.0.0
 * @author Pup and Tiger
 * @copyright 2025 Pup and Tiger
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF7_ARTIST_SUBMISSIONS_VERSION', '2.0.0');
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
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-dashboard.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-actions.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-pdf-export.php';

/**
 * Main plugin initialization function.
 * 
 * This function is responsible for loading and initializing all plugin components
 * including the dashboard, tabbed interface, field editing system, task management,
 * conversation system, and PDF export functionality.
 *
 * @since 1.0.0
 * @since 2.0.0 Added dashboard, tabs, actions, and PDF export systems
 * 
 * @return void
 */
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
    
    // Update action log table schema if needed (for existing installations)
    CF7_Artist_Submissions_Action_Log::update_table_schema();
    
    // Initialize Email System
    $emails = new CF7_Artist_Submissions_Emails();
    $emails->init();
    
    // Initialize Conversation System
    CF7_Artist_Submissions_Conversations::init();
    
    // Initialize Tabbed Interface
    CF7_Artist_Submissions_Tabs::init();
    
    // Initialize Actions System
    CF7_Artist_Submissions_Actions::init();
    
    // Initialize Dashboard
    $dashboard = new CF7_Artist_Submissions_Dashboard();
    $dashboard->init();
    
    // Initialize PDF Export
    if (is_admin()) {
        $pdf_export = new CF7_Artist_Submissions_PDF_Export();
        $pdf_export->init();
    }
}

add_action('plugins_loaded', 'cf7_artist_submissions_init');

/**
 * Add custom cron schedule for email processing and action reminders.
 * 
 * Adds a 5-minute interval schedule for processing conversation emails
 * and sending action reminder notifications.
 *
 * @since 2.0.0
 * 
 * @param array $schedules Existing cron schedules
 * @return array Modified schedules array with 5-minute interval
 */
add_filter('cron_schedules', 'cf7_artist_submissions_cron_schedules');
function cf7_artist_submissions_cron_schedules($schedules) {
    $schedules['every_5_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display' => __('Every 5 Minutes', 'cf7-artist-submissions')
    );
    return $schedules;
}

/**
 * Plugin activation hook.
 * 
 * Creates all necessary database tables, registers custom post types,
 * and sets up rewrite rules when the plugin is activated.
 *
 * @since 1.0.0
 * @since 2.0.0 Added conversations and actions table creation
 * 
 * @return void
 */
register_activation_hook(__FILE__, 'cf7_artist_submissions_activate');
function cf7_artist_submissions_activate() {
    // Create custom post type
    $post_type = new CF7_Artist_Submissions_Post_Type();
    $post_type->register_post_type();
    $post_type->register_taxonomy();
    
    // Create action log table
    CF7_Artist_Submissions_Action_Log::create_log_table();
    
    // Update action log table schema if needed
    CF7_Artist_Submissions_Action_Log::update_table_schema();
    
    // Create conversations table
    CF7_Artist_Submissions_Conversations::create_conversations_table();
    
    // Create actions table
    CF7_Artist_Submissions_Actions::create_table();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 * 
 * Cleans up rewrite rules when the plugin is deactivated.
 * Does not remove data - that is handled by uninstall.php.
 *
 * @since 1.0.0
 * 
 * @return void
 */
register_deactivation_hook(__FILE__, 'cf7_artist_submissions_deactivate');
function cf7_artist_submissions_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}