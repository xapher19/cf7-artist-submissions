<?php
/**
 * Contact Form 7 Submission Handler
 * 
 * This class intercepts Contact Form 7 submissions for the configured
 * form and stores them as custom post type entries with file uploads,
 * metadata, and initial status assignment.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 */

/**
 * CF7 Artist Submissions Form Handler Class
 * 
 * Handles the capture and processing of Contact Form 7 submissions:
 * - Intercepts submissions for the configured form ID
 * - Creates custom post type entries
 * - Processes and securely stores file uploads
 * - Saves form data as post metadata
 * - Sets initial submission status
 * - Triggers submission created actions
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Form_Handler {
    
    /**
     * Initialize the form handler hooks.
     * 
     * Sets up the Contact Form 7 submission hook to capture
     * submissions for processing.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function init() {
        add_action('wpcf7_before_send_mail', array($this, 'capture_submission'));
    }
    
    /**
     * Capture and process Contact Form 7 submissions.
     * 
     * Processes submissions from the configured form ID, creates
     * custom post type entries, handles file uploads with security
     * validation, and saves all form data as metadata.
     * 
     * @since 1.0.0
     * 
     * @param WPCF7_ContactForm $contact_form The Contact Form 7 instance
     * @return void
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
}