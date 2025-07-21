<?php
/**
 * Admin Interface for CF7 Artist Submissions
 * 
 * This class handles the admin interface components including meta boxes,
 * AJAX status updates, field saving, and asset management coordination
 * with other system components.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with tabbed interface coordination
 */

/**
 * CF7 Artist Submissions Admin Class
 * 
 * Manages the WordPress admin interface for artist submissions:
 * - Meta boxes for submission details, files, and notes
 * - AJAX handlers for status updates
 * - Field saving and validation
 * - Asset management coordination with tabs system
 * - Status change logging and notifications
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Admin {
    
    /**
     * Initialize the admin interface.
     * 
     * Sets up hooks for asset management, meta boxes, AJAX handlers,
     * and post saving functionality.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced asset coordination with tabs system
     * 
     * @return void
     */
    public function init() {
        // Asset management is handled by the tabs system for submission pages
        // Only register AJAX handlers and post saving functionality
        add_action('wp_ajax_update_submission_status', array($this, 'ajax_update_status'));
        
        // Register the save_fields hook for editable fields
        add_action('save_post_cf7_submission', array($this, 'save_fields'), 10, 2);
    }
    
    public function ajax_update_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_artist_submissions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check required data
        if (!isset($_POST['post_id']) || !isset($_POST['status_id'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }
        
        $post_id = intval($_POST['post_id']);
        $status_id = intval($_POST['status_id']);
        
        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Get the old status
        $old_status_terms = wp_get_object_terms($post_id, 'submission_status');
        $old_status = !empty($old_status_terms) ? $old_status_terms[0]->name : 'None';
        
        // Update the status
        $result = wp_set_object_terms($post_id, $status_id, 'submission_status');
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Get the status term for response
        $term = get_term($status_id, 'submission_status');
        
        // Log the status change
        do_action('cf7_artist_submission_status_changed', $post_id, $term->name, $old_status);
        
        wp_send_json_success(array(
            'status_name' => $term->name,
            'status_slug' => sanitize_title($term->name)
        ));
    }
    
    /**
     * Save the edited fields when the post is updated
     */
    public function save_fields($post_id, $post) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is not a CF7 submission post type
        if (get_post_type($post_id) !== 'cf7_submission') {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['cf7_artist_submissions_fields_nonce']) || !wp_verify_nonce($_POST['cf7_artist_submissions_fields_nonce'], 'cf7_artist_submissions_fields_nonce')) {
            return;
        }
        
        // Check if user has permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if we have fields to save
        if (!isset($_POST['cf7_editable_fields']) || !is_array($_POST['cf7_editable_fields'])) {
            return;
        }
        
        // Save each field
        foreach ($_POST['cf7_editable_fields'] as $meta_key => $value) {
            // Skip empty values or non-cf7 meta keys for safety
            if (empty($value) || strpos($meta_key, 'cf7_') !== 0) {
                continue;
            }
            
            // Get the old value
            $old_value = get_post_meta($post_id, $meta_key, true);
            
            // Only proceed if the value has changed
            if ($old_value !== $value) {
                // Sanitize based on field type
                if (strpos($meta_key, 'email') !== false) {
                    $value = sanitize_email($value);
                } 
                elseif (strpos($meta_key, 'website') !== false || 
                       strpos($meta_key, 'portfolio') !== false || 
                       strpos($meta_key, 'url') !== false || 
                       strpos($meta_key, 'link') !== false) {
                    $value = esc_url_raw($value);
                }
                elseif (strpos($meta_key, 'statement') !== false) {
                    $value = sanitize_textarea_field($value);
                }
                else {
                    $value = sanitize_text_field($value);
                }
                
                // Update the post meta
                update_post_meta($post_id, $meta_key, $value);
                
                // If the field is artist-name, also update the post title
                if ($meta_key === 'cf7_artist-name') {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_title' => $value
                    ));
                }
                
                // Log the field update
                do_action('cf7_artist_submission_field_updated', $post_id, $meta_key, $old_value, $value);
            }
        }
    }
}