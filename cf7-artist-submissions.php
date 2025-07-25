<?php
/**
 * Plugin Name: CF7 Artist Submissions
 * Description: Professional artist submission management system with modern dashboard, advanced field editing, task management, and conversation system for Contact Form 7.
 * Version: 1.1.0
 * Author: Pup and Tiger
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 6.8.2
 * Text Domain: cf7-artist-submissions
 * License: GPL v2 or later
 * Network: false
 *
 * @package CF7_Artist_Submissions
 * @version 1.1.0
 * @author Pup and Tiger
 * @copyright 2025 Pup and Tiger
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CF7_ARTIST_SUBMISSIONS_VERSION', '1.1.0');
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_FILE', __FILE__);
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7_ARTIST_SUBMISSIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include class files
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
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-updater.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-cf7-artist-submissions-add-submission.php';

// S3 Integration Classes
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-s3-handler.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-rest-endpoints.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-metadata-manager.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-thumbnail-generator.php';
require_once CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'includes/class-zip-downloader.php';

/**
 * Main plugin initialization function.
 * 
 * Loads and initializes all plugin components including dashboard interface,
 * tabbed navigation system, field editing capabilities, task management,
 * conversation tracking, and PDF export functionality with Contact Form 7
 * dependency validation and comprehensive system integration.
 * 
 * @since 1.0.0
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
    
    // Initialize automatic updater
    if (is_admin()) {
        global $cf7_artist_submissions_updater;
        $cf7_artist_submissions_updater = new CF7_Artist_Submissions_Updater(__FILE__, CF7_ARTIST_SUBMISSIONS_VERSION);
    }
    
    // Initialize Email System
    $emails = new CF7_Artist_Submissions_Emails();
    $emails->init();
    
    // Initialize Conversation System
    CF7_Artist_Submissions_Conversations::init();
    
    // Initialize Tabbed Interface
    CF7_Artist_Submissions_Tabs::init();
    
    // Initialize Actions System
    CF7_Artist_Submissions_Actions::init();
    
    // Initialize S3 Handler
    if (class_exists('CF7_Artist_Submissions_S3_Handler')) {
        $s3_handler = new CF7_Artist_Submissions_S3_Handler();
        $s3_handler->init();
    }
    
    // Initialize REST Endpoints
    if (class_exists('CF7_Artist_Submissions_REST_Endpoints')) {
        $rest_endpoints = new CF7_Artist_Submissions_REST_Endpoints();
        $rest_endpoints->init();
    }
    
    // Initialize Metadata Manager
    if (class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
        CF7_Artist_Submissions_Metadata_Manager::init();
    }
    
    // Initialize Dashboard
    $dashboard = new CF7_Artist_Submissions_Dashboard();
    $dashboard->init();
    
    // Enqueue Uppy assets on frontend for CF7 forms
    add_action('wp_enqueue_scripts', 'cf7_artist_submissions_enqueue_uploader_assets');
    
    // Initialize PDF Export
    if (is_admin()) {
        $pdf_export = new CF7_Artist_Submissions_PDF_Export();
        $pdf_export->init();
    }
    
    // Initialize Add Submission Interface
    if (is_admin()) {
        CF7_Artist_Submissions_Add_Submission::init();
    }
}

add_action('plugins_loaded', 'cf7_artist_submissions_init');

/**
 * Add custom cron schedule for email processing and action reminders.
 * Adds 5-minute interval schedule for conversation email processing and action notifications.
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
 * Creates necessary database tables, registers custom post types and taxonomies,
 * and sets up rewrite rules during plugin activation with comprehensive
 * system initialization for all plugin components.
 * 
 * @since 1.0.0
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
    
    // Create S3 files table
    CF7_Artist_Submissions_Metadata_Manager::create_files_table();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 * Cleans up rewrite rules during plugin deactivation without removing user data.
 */
register_deactivation_hook(__FILE__, 'cf7_artist_submissions_deactivate');
function cf7_artist_submissions_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Enqueue Custom S3 Uploader assets for Contact Form 7 forms
 */
function cf7_artist_submissions_enqueue_uploader_assets() {
    // Only enqueue on pages that might have CF7 forms
    if (is_admin()) {
        return;
    }
    
    // Enqueue Custom Uploader CSS
    wp_enqueue_style(
        'cf7as-custom-uploader',
        CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/custom-uploader.css',
        array(),
        CF7_ARTIST_SUBMISSIONS_VERSION
    );
    
    // Enqueue Custom Uploader JavaScript
    wp_enqueue_script(
        'cf7as-custom-uploader',
        CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/custom-uploader.js',
        array('jquery'),
        CF7_ARTIST_SUBMISSIONS_VERSION,
        true
    );
    
    // Localize script with REST API data and plugin configuration
    wp_localize_script('cf7as-custom-uploader', 'cf7as_uploader_config', array(
        'plugin_url' => CF7_ARTIST_SUBMISSIONS_PLUGIN_URL,
        'rest_url' => rest_url(),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
    
    // Keep lightbox CSS for other features
    wp_enqueue_style(
        'cf7as-lightbox',
        CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/lightbox.css',
        array(),
        CF7_ARTIST_SUBMISSIONS_VERSION
    );
}