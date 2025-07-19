<?php
/**
 * Uninstall CF7 Artist Submissions
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('cf7_artist_submissions_options');

// Get all submission posts
$submissions = get_posts(array(
    'post_type' => 'cf7_submission',
    'numberposts' => -1,
    'post_status' => 'any',
    'fields' => 'ids',
));

// Delete all submission posts
foreach ($submissions as $post_id) {
    wp_delete_post($post_id, true);
}

// Delete submission status taxonomy
$terms = get_terms(array(
    'taxonomy' => 'submission_status',
    'hide_empty' => false,
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'submission_status');
}

// Remove uploaded files
$upload_dir = wp_upload_dir();
$submissions_dir = $upload_dir['basedir'] . '/cf7-submissions';

if (file_exists($submissions_dir)) {
    // Recursive function to delete directory and all contents
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