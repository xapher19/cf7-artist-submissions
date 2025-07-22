<?php
/**
 * CF7 Artist Submissions - Contact Form 7 Submission Handler
 *
 * Comprehensive form processing system for Contact Form 7 submissions with
 * custom post type integration, secure file upload handling, metadata storage,
 * and automated workflow triggers for artist submission management.
 *
 * Features:
 * • Contact Form 7 submission interception and processing
 * • Custom post type creation with metadata storage
 * • Secure file upload handling with validation and storage
 * • Artistic medium taxonomy processing and assignment
 * • Initial status assignment and workflow automation
 * • Action hooks for email triggers and logging integration
 *
 * @package CF7_Artist_Submissions
 * @subpackage FormProcessing
 * @since 1.0.0
 * @version 2.1.0
 */

/**
 * CF7 Artist Submissions Form Handler Class
 * 
 * Comprehensive Contact Form 7 submission processing system with custom post
 * type integration, secure file handling, and automated workflow triggers.
 * Provides complete form-to-database pipeline with validation, security,
 * and integration hooks for seamless artist submission management.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Form_Handler {
    
    // ============================================================================
    // INITIALIZATION SECTION
    // ============================================================================
    
    /**
     * Initialize Contact Form 7 submission processing system with hook registration.
     * 
     * Establishes Contact Form 7 integration by registering submission interception
     * hooks for configured form processing. Provides foundation for automated
     * submission capture, custom post type creation, and workflow trigger activation
     * within the artist submission management system.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('wpcf7_before_send_mail', array($this, 'capture_submission'));
    }
    
    // ============================================================================
    // FORM PROCESSING SECTION
    // ============================================================================
    
    /**
     * Capture and process Contact Form 7 submissions with comprehensive data handling.
     * 
     * Intercepts submissions from configured Contact Form 7 forms and processes them
     * into custom post type entries with complete metadata storage, secure file upload
     * handling, taxonomy assignment, and workflow trigger activation. Implements
     * validation, security measures, and integration hooks for submission management.
     * 
     * @since 1.0.0
     */
    public function capture_submission($contact_form) {
        $options = get_option('cf7_artist_submissions_options', array());
        $form_id = !empty($options['form_id']) ? $options['form_id'] : '';
        
        // Only process the selected form
        if ($contact_form->id() != $form_id) {
            return;
        }
        
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        if (empty($posted_data)) {
            return;
        }
        
        // Prepare title from artist-name field or use a default
        $title = '';
        if (!empty($posted_data['artist-name'])) {
            $title = $posted_data['artist-name'];
        } elseif (!empty($posted_data['your-name'])) {
            $title = $posted_data['your-name'];
        } elseif (!empty($posted_data['name'])) {
            $title = $posted_data['name'];
        } elseif (!empty($posted_data['first-name']) && !empty($posted_data['last-name'])) {
            $title = $posted_data['first-name'] . ' ' . $posted_data['last-name'];
        } else {
            $title = 'Submission ' . date('Y-m-d H:i:s');
        }
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title'   => sanitize_text_field($title),
            'post_status'  => 'publish',
            'post_type'    => 'cf7_submission',
        ));
        
        if (is_wp_error($post_id)) {
            return;
        }
        
        // Set initial status
        wp_set_object_terms($post_id, 'New', 'submission_status');
        
        // Save form data as post meta
        foreach ($posted_data as $key => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Skip storing mfile data directly if it's complex
            if ($key === 'your-work' && is_array($value) && isset($value['path'])) {
                continue;
            }
            
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            update_post_meta($post_id, 'cf7_' . sanitize_key($key), sanitize_text_field($value));
        }
        
        // Handle artistic medium tags
        $this->process_medium_tags($post_id, $posted_data);
        
        // Store uploaded files
        if (!empty($options['store_files']) && $options['store_files'] === 'yes') {
            $files = $submission->uploaded_files();
            if (!empty($files)) {
                $upload_dir = wp_upload_dir();
                $submission_dir = $upload_dir['basedir'] . '/cf7-submissions/' . $post_id;
                
                if (!file_exists($submission_dir)) {
                    wp_mkdir_p($submission_dir);
                }
                
                // Process regular file uploads
                foreach ($files as $field_name => $tmp_paths) {
                    // Skip empty uploads
                    if (empty($tmp_paths)) {
                        continue;
                    }
                    
                    // Handle both single and multiple file uploads
                    $file_urls = array();
                    
                    // Convert to array if it's a single file
                    if (!is_array($tmp_paths)) {
                        $tmp_paths = array($tmp_paths);
                    }
                    
                    foreach ($tmp_paths as $tmp_path) {
                        // Skip invalid paths
                        if (empty($tmp_path) || !file_exists($tmp_path)) {
                            continue;
                        }
                        
                        // Get filename and validate it
                        $filename = basename($tmp_path);
                        
                        // Validate file type for security
                        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip');
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (!in_array($file_extension, $allowed_types)) {
                            continue; // Skip disallowed file types
                        }
                        
                        // Additional security: Check file MIME type
                        $file_info = wp_check_filetype($filename);
                        if (!$file_info['type']) {
                            continue; // Skip files with no valid MIME type
                        }
                        
                        // Create a unique filename
                        $unique_filename = wp_unique_filename($submission_dir, $filename);
                        
                        // Copy file to our directory
                        $new_path = $submission_dir . '/' . $unique_filename;
                        
                        if (@copy($tmp_path, $new_path)) {
                            // Set proper file permissions
                            @chmod($new_path, 0644);
                            
                            // Create and store file URL
                            $file_url = $upload_dir['baseurl'] . '/cf7-submissions/' . $post_id . '/' . $unique_filename;
                            $file_urls[] = esc_url_raw($file_url);
                        }
                    }
                    
                    // Save file URLs as post meta
                    if (!empty($file_urls)) {
                        if (count($file_urls) === 1) {
                            update_post_meta($post_id, 'cf7_file_' . sanitize_key($field_name), $file_urls[0]);
                        } else {
                            update_post_meta($post_id, 'cf7_file_' . sanitize_key($field_name), $file_urls);
                        }
                    }
                }
            }
        }
        
        // Save submission date
        update_post_meta($post_id, 'cf7_submission_date', current_time('mysql'));
        
        // Fire submission created action for logging and email triggers
        do_action('cf7_artist_submission_created', $post_id);
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS SECTION
    // ============================================================================
    
    /**
     * Process artistic medium tags from form submission data.
     * Extracts medium information from multiple field patterns and assigns taxonomies.
     */
    private function process_medium_tags($post_id, $posted_data) {
        // Look for fields that might contain medium information
        $medium_fields = array(
            'medium',
            'mediums', 
            'artistic-medium',
            'artistic_medium',
            'art-medium',
            'art_medium',
            'techniques',
            'materials'
        );
        
        $medium_terms = array();
        
        // Check each possible field name
        foreach ($medium_fields as $field) {
            if (isset($posted_data[$field]) && !empty($posted_data[$field])) {
                $field_value = $posted_data[$field];
                
                // Handle array values (checkboxes/multiple selects)
                if (is_array($field_value)) {
                    $medium_terms = array_merge($medium_terms, $field_value);
                } else {
                    // Handle comma-separated values or single values
                    $values = array_map('trim', explode(',', $field_value));
                    $medium_terms = array_merge($medium_terms, $values);
                }
            }
        }
        
        // Clean and validate terms
        $medium_terms = array_filter(array_map('sanitize_text_field', $medium_terms));
        
        if (!empty($medium_terms)) {
            // Set the artistic medium terms for this submission
            wp_set_object_terms($post_id, $medium_terms, 'artistic_medium');
            
            // Also store as post meta for backwards compatibility
            update_post_meta($post_id, 'cf7_artistic_mediums', implode(', ', $medium_terms));
        }
    }
}