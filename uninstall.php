<?php
/**
 * CF7 Artist Submissions - Plugin Uninstall Handler
 *
 * Complete plugin data removal system handling comprehensive cleanup when
 * plugin is uninstalled through WordPress admin interface with database
 * table removal, file cleanup, and settings deletion.
 *
 * Features:
 * • Custom database tables removal (conversations, action log, actions)
 * • Complete custom post type and metadata deletion
 * • Uploaded files and directories cleanup with recursive removal
 * • Plugin options and settings comprehensive deletion
 * • Custom taxonomies and terms complete removal
 * • Cache clearing and system cleanup finalization
 *
 * @package CF7_Artist_Submissions
 * @subpackage Core
 * @since 1.0.0
 * @version 1.0.1
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options and settings
delete_option('cf7_artist_submissions_options');
delete_option('cf7_debug_messages');
delete_option('cf7_last_imap_check');
delete_option('cf7_conversations_db_version');

global $wpdb;

// Delete custom database tables
$table_name = $wpdb->prefix . 'cf7_conversations';
$wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");

$table_name = $wpdb->prefix . 'cf7_action_log';
$wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");

$table_name = $wpdb->prefix . 'cf7_actions';
$wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");

// Get all submission posts and delete them completely
$submissions = get_posts(array(
    'post_type' => 'cf7_submission',
    'numberposts' => -1,
    'post_status' => 'any',
    'fields' => 'ids',
));

// Delete all submission posts and their metadata
foreach ($submissions as $post_id) {
    wp_delete_post($post_id, true);
}

// Delete submission status taxonomy and all terms
$terms = get_terms(array(
    'taxonomy' => 'submission_status',
    'hide_empty' => false,
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'submission_status');
}

// Remove all uploaded files and directories
$upload_dir = wp_upload_dir();
$submissions_dir = $upload_dir['basedir'] . '/cf7-submissions';

if (file_exists($submissions_dir)) {
    /**
     * Recursively delete directory and all contents.
     * Removes directories and files with comprehensive error handling.
     */
    function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    delete_directory($submissions_dir);
}

// Clear any cached data
wp_cache_flush();