<?php
/**
 * CF7 Artist Submissions - Administrative Interface Management System
 *
 * Comprehensive WordPress admin interface system for artist submission management
 * with real-time status updates, dynamic field editing, AJAX communication, and
 * seamless integration with the CF7 Artist Submissions workflow ecosystem.
 *
 * Features:
 * • Real-time submission status management with instant feedback
 * • Dynamic field editing with type-specific validation and sanitization
 * • AJAX communication framework for responsive administrative workflows
 * • Security validation with WordPress nonce and capability systems
 * • Field saving and validation with comprehensive audit trail integration
 * • Asset coordination delegation to tabs system for unified management
 * • Administrative workflow optimization and user experience enhancement
 *
 * @package CF7_Artist_Submissions
 * @subpackage AdminInterface
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * CF7 Artist Submissions Admin Class
 * 
 * Primary administrative interface management system for CF7 Artist Submissions
 * providing comprehensive submission administration capabilities within the
 * WordPress admin environment. Features real-time status updates, dynamic
 * field management, AJAX communication, and seamless asset coordination.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Admin {
    
    /**
     * Initialize comprehensive admin interface management system.
     * 
     * Establishes complete administrative interface coordination including
     * AJAX communication endpoints, field management systems, asset
     * coordination with tabs system, and WordPress hook integration
     * for seamless submission administration workflows.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Asset management is handled by the tabs system for submission pages
        // Only register AJAX handlers and post saving functionality
        add_action('wp_ajax_update_submission_status', array($this, 'ajax_update_status'));
        
        // Register the save_fields hook for editable fields
        add_action('save_post_cf7_submission', array($this, 'save_fields'), 10, 2);
    }
    
    // ============================================================================
    // AJAX COMMUNICATION HANDLERS SECTION
    // ============================================================================
    
    /**
     * Real-time submission status update handler with comprehensive validation.
     * 
     * Advanced AJAX endpoint providing secure, real-time submission status
     * updates with comprehensive security validation, permission checking,
     * audit trail integration, and structured response formatting for
     * optimal administrative workflow efficiency.
     * 
     * @since 1.0.0
     */
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
    
    // ============================================================================
    // FIELD MANAGEMENT SYSTEM SECTION
    // ============================================================================
    
    /**
     * Comprehensive field saving and validation system for submission management.
     * 
     * Advanced field management system providing secure field saving, type-specific
     * validation, change detection, audit trail integration, and post synchronization
     * for comprehensive submission field management within the WordPress admin
     * environment.
     * 
     * @since 1.0.0
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