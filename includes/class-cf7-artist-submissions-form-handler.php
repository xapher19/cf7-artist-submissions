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
 * @version 1.1.0
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
        error_log('CF7AS Form Handler Debug - capture_submission triggered');
        error_log('CF7AS Form Handler Debug - Form ID received: ' . $contact_form->id());
        
        $options = get_option('cf7_artist_submissions_options', array());
        $form_id = !empty($options['form_id']) ? $options['form_id'] : '';
        
        error_log('CF7AS Form Handler Debug - Configured form ID: ' . $form_id);
        
        // Only process the selected form
        if ($contact_form->id() != $form_id) {
            error_log('CF7AS Form Handler Debug - Form ID mismatch, skipping processing');
            return;
        }
        
        error_log('CF7AS Form Handler Debug - Form ID matches, proceeding with processing');
        
        if (!class_exists('WPCF7_Submission')) {
            error_log('CF7AS Form Handler Debug - WPCF7_Submission class not found');
            return;
        }
        
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            error_log('CF7AS Form Handler Debug - No submission instance available');
            return;
        }
        
        error_log('CF7AS Form Handler Debug - Submission instance obtained successfully');
        
        $posted_data = $submission->get_posted_data();
        if (empty($posted_data)) {
            error_log('CF7AS Form Handler Debug - No posted data available');
            return;
        }
        
        error_log('CF7AS Form Handler Debug - Posted data retrieved successfully');
        
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
        
        error_log('CF7AS Form Handler Debug - Creating post with title: ' . $title);
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title'   => sanitize_text_field($title),
            'post_status'  => 'publish',
            'post_type'    => 'cf7_submission',
        ));
        
        if (is_wp_error($post_id)) {
            error_log('CF7AS Form Handler Debug - Failed to create post: ' . $post_id->get_error_message());
            return;
        }
        
        error_log('CF7AS Form Handler Debug - Post created successfully with ID: ' . $post_id);
        
        // Set initial status
        wp_set_object_terms($post_id, 'New', 'submission_status');
        error_log('CF7AS Form Handler Debug - Set initial status to New');
        
        // Save form data as post meta
        $meta_count = 0;
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
            $meta_count++;
        }
        
        error_log('CF7AS Form Handler Debug - Saved ' . $meta_count . ' meta fields for post ' . $post_id);
        
        // Handle artistic medium tags
        error_log('CF7AS Form Handler Debug - Processing medium tags');
        $this->process_medium_tags($post_id, $posted_data);
        
        // Process S3 uploaded files data (from custom uploader)
        error_log('CF7AS Form Handler Debug - About to process S3 uploaded files');
        $this->process_s3_uploaded_files($posted_data, $post_id);
        
        // Save submission date
        update_post_meta($post_id, 'cf7_submission_date', current_time('mysql'));
        error_log('CF7AS Form Handler Debug - Saved submission date');
        
        // Fire submission created action for logging and email triggers
        error_log('CF7AS Form Handler Debug - Firing submission created action');
        do_action('cf7_artist_submission_created', $post_id);
        
        error_log('CF7AS Form Handler Debug - Submission processing completed for post ID: ' . $post_id);
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
    
    /**
     * Process uploaded files from S3 and store metadata in database
     */
    private function process_s3_uploaded_files($posted_data, $post_id) {
        if (empty($posted_data)) {
            error_log('CF7AS Form Handler Debug - No posted data received');
            return;
        }

        // Log the full posted data for debugging
        error_log('CF7AS Form Handler Debug - Full posted data: ' . print_r($posted_data, true));
        error_log('CF7AS Form Handler Debug - Processing files for post ID: ' . $post_id);

        $found_file_fields = 0;
        $processed_files = 0;

        // Look for the custom uploader file data fields ending with '_data'
        foreach ($posted_data as $field_name => $field_value) {
            // Check if this is a file data field (ends with '_data') and has content
            if (substr($field_name, -5) === '_data' && !empty($field_value)) {
                $found_file_fields++;
                error_log('CF7AS Form Handler Debug - Found uploader data field: ' . $field_name . ' with value: ' . $field_value);
                
                // Parse the JSON data
                $file_data = json_decode($field_value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($file_data)) {
                    error_log('CF7AS Form Handler Debug - Successfully parsed JSON data: ' . print_r($file_data, true));
                    $files_stored = $this->store_s3_files_in_database($file_data, $post_id, $field_name);
                    $processed_files += $files_stored;
                } else {
                    error_log('CF7AS Form Handler Debug - Failed to parse JSON for field ' . $field_name . '. JSON Error: ' . json_last_error_msg());
                }
            }
        }

        // Also check for traditional file upload fields (fallback)
        $file_data_fields = array();
        foreach ($posted_data as $field_name => $field_value) {
            if (strpos($field_name, 'file') !== false || strpos($field_name, 'upload') !== false || strpos($field_name, 'artwork') !== false) {
                if (!in_array($field_name, $file_data_fields)) {
                    $file_data_fields[] = $field_name;
                }
            }
        }

        error_log('CF7AS Form Handler Debug - Found traditional file fields: ' . print_r($file_data_fields, true));

        foreach ($file_data_fields as $field) {
            if (!empty($posted_data[$field]) && is_string($posted_data[$field])) {
                $found_file_fields++;
                error_log('CF7AS Form Handler Debug - Processing traditional field: ' . $field);
                $file_data = json_decode($posted_data[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($file_data)) {
                    $files_stored = $this->store_s3_files_in_database($file_data, $post_id, $field);
                    $processed_files += $files_stored;
                } else {
                    error_log('CF7AS Form Handler Debug - Traditional field ' . $field . ' is not valid JSON: ' . $posted_data[$field]);
                }
            }
        }

        error_log('CF7AS Form Handler Debug - Summary: Found ' . $found_file_fields . ' file fields, processed ' . $processed_files . ' files');
    }
    
    /**
     * Store S3 file metadata in the database.
     */
    private function store_s3_files_in_database($file_data, $post_id, $field_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_files';
        $stored_count = 0;
        
        error_log('CF7AS File Storage Debug - Attempting to store files for post ID: ' . $post_id . ' from field: ' . $field_name);
        error_log('CF7AS File Storage Debug - Table name: ' . $table_name);
        error_log('CF7AS File Storage Debug - Files to process: ' . print_r($file_data, true));
        
        if (!is_array($file_data)) {
            error_log('CF7AS File Storage Debug - File data is not an array for field: ' . $field_name);
            return $stored_count;
        }
        
        foreach ($file_data as $file) {
            // Handle both old and new field formats
            $s3_key = isset($file['s3_key']) ? $file['s3_key'] : (isset($file['s3Key']) ? $file['s3Key'] : null);
            $original_name = isset($file['filename']) ? $file['filename'] :
                            (isset($file['original_filename']) ? $file['original_filename'] : 
                            (isset($file['original_name']) ? $file['original_name'] : 
                            (isset($file['name']) ? $file['name'] : null)));
            $mime_type = isset($file['type']) ? $file['type'] :
                        (isset($file['file_type']) ? $file['file_type'] :
                        (isset($file['mime_type']) ? $file['mime_type'] : 'application/octet-stream'));
            
            error_log('CF7AS File Storage Debug - Processing file: ' . print_r($file, true));
            error_log('CF7AS File Storage Debug - Extracted values - s3_key: ' . $s3_key . ', name: ' . $original_name . ', type: ' . $mime_type);
            
            if (!$s3_key || !$original_name) {
                error_log('CF7AS File Storage Debug - Skipping file due to missing s3_key or original_name');
                continue; // Skip invalid file data
            }
            
            $insert_data = array(
                'submission_id' => (string) $post_id,
                'original_name' => sanitize_text_field($original_name),
                's3_key' => sanitize_text_field($s3_key),
                'mime_type' => sanitize_text_field($mime_type),
                'file_size' => isset($file['size']) ? intval($file['size']) : 0,
                'thumbnail_url' => isset($file['thumbnail_url']) ? esc_url_raw($file['thumbnail_url']) : '',
                'created_at' => current_time('mysql')
            );
            
            // Store work metadata as post meta linked to the file
            if (!empty($file['work_title'])) {
                update_post_meta($post_id, 'cf7_work_title_' . sanitize_key($original_name), sanitize_text_field($file['work_title']));
            }
            if (!empty($file['work_statement'])) {
                update_post_meta($post_id, 'cf7_work_statement_' . sanitize_key($original_name), sanitize_textarea_field($file['work_statement']));
            }
            
            error_log('CF7AS File Storage Debug - Insert data: ' . print_r($insert_data, true));
            
            $result = $wpdb->insert($table_name, $insert_data);
            
            if ($result === false) {
                error_log('CF7AS File Storage Debug - Failed to insert file record: ' . $wpdb->last_error);
                error_log('CF7AS File Storage Debug - Last query: ' . $wpdb->last_query);
            } else {
                $stored_count++;
                error_log('CF7AS File Storage Debug - Successfully inserted file record with ID: ' . $wpdb->insert_id);
            }
        }
        
        // Verify files were stored
        $verification_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE submission_id = %s",
            (string) $post_id
        ));
        error_log('CF7AS File Storage Debug - Total files now stored for submission ' . $post_id . ': ' . $verification_count);
        
        return $stored_count;
    }
}