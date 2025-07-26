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
        
        // Register custom form tags
        add_action('wpcf7_init', array($this, 'register_custom_form_tags'));
        
        // Enqueue frontend assets for custom form fields
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Enqueue admin assets for CF7 tag generator
        add_action('admin_enqueue_scripts', array($this, 'enqueue_cf7_admin_assets'));
        
        // Register AJAX handlers for open call information
        add_action('wp_ajax_cf7as_get_open_call_info', array($this, 'ajax_get_open_call_info'));
        add_action('wp_ajax_nopriv_cf7as_get_open_call_info', array($this, 'ajax_get_open_call_info'));
    }
    
    /**
     * Register custom Contact Form 7 form tags for artist submissions.
     * 
     * Adds the 'mediums' form tag for multiple checkbox selection of artistic mediums
     * from the artistic_medium taxonomy. Integrates seamlessly with CF7 form builder
     * and provides comprehensive medium selection capabilities.
     * 
     * @since 1.1.0
     */
    public function register_custom_form_tags() {
        if (function_exists('wpcf7_add_form_tag')) {
            wpcf7_add_form_tag(
                array('mediums', 'mediums*'), 
                array($this, 'render_mediums_form_tag'), 
                array('name-attr' => true)
            );
        }
        
        // Register tag generator for form builder
        if (function_exists('wpcf7_add_tag_generator')) {
            add_action('wpcf7_admin_init', array($this, 'add_mediums_tag_generator'), 15);
        }
    }
    
    /**
     * Conditionally enqueue frontend assets only when needed.
     * 
     * Note: Common CSS is now handled by the main conditional loading system
     * in the main plugin file, so this method is kept for compatibility but
     * doesn't need to load assets anymore.
     * 
     * @since 1.1.0
     */
    public function enqueue_frontend_assets() {
        // Assets are now handled by the main conditional loading system
        // This method is kept for backward compatibility
        return;
    }    /**
     * Enqueue CF7 admin assets for tag generator functionality.
     * 
     * Loads JavaScript for the mediums tag generator in the CF7 form builder.
     * 
     * @since 1.1.0
     */
    public function enqueue_cf7_admin_assets() {
        $screen = get_current_screen();
        
        // Only load on CF7 form edit pages
        if (!$screen || $screen->post_type !== 'wpcf7_contact_form') {
            return;
        }
        
        // Enqueue tag generator JavaScript
        wp_enqueue_script(
            'cf7as-tag-generator',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/cf7-tag-generator.js',
            array('jquery', 'wpcf7-admin'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
    }
    
    // ============================================================================
    // FORM PROCESSING SECTION
    // ============================================================================
    
    /**
     * Get open call information by Contact Form 7 ID.
     * 
     * Retrieves open call configuration, timing, and status information
     * for a specific Contact Form 7 form to determine if submissions
     * are currently allowed.
     * 
     * @since 1.2.0
     * @param int $form_id The Contact Form 7 form ID
     * @return array|null Open call configuration or null if not found
     */
    public function get_open_call_by_form_id($form_id) {
        $open_calls_options = get_option('cf7_artist_submissions_open_calls', array());
        
        if (empty($open_calls_options['calls'])) {
            return null;
        }
        
        foreach ($open_calls_options['calls'] as $call_config) {
            if (!empty($call_config['form_id']) && 
                intval($call_config['form_id']) === intval($form_id)) {
                
                // Add computed status based on timing
                $now = current_time('timestamp');
                $start_time = !empty($call_config['start_date']) ? strtotime($call_config['start_date'] . ' 00:00:00') : null;
                $end_time = !empty($call_config['end_date']) ? strtotime($call_config['end_date'] . ' 23:59:59') : null;
                
                $call_config['is_open'] = true; // Default to open
                $call_config['status_message'] = '';
                
                // Ensure call_type is set with default
                if (empty($call_config['call_type'])) {
                    $call_config['call_type'] = 'visual_arts'; // Default to visual arts
                }
                
                // Check if not yet open
                if ($start_time && $now < $start_time) {
                    $call_config['is_open'] = false;
                    $call_config['status_message'] = 'This call opens on ' . date('F j, Y', $start_time);
                }
                // Check if closed
                elseif ($end_time && $now > $end_time) {
                    $call_config['is_open'] = false;
                    $call_config['status_message'] = 'This call closed on ' . date('F j, Y', $end_time);
                }
                // Check if active but has dates
                elseif ($start_time && $end_time) {
                    $call_config['status_message'] = 'Open until ' . date('F j, Y', $end_time);
                }
                elseif ($end_time) {
                    $call_config['status_message'] = 'Open until ' . date('F j, Y', $end_time);
                }
                
                return $call_config;
            }
        }
        
        return null;
    }
    
    /**
     * AJAX handler to get open call timing information for frontend.
     * 
     * Provides open call status and timing information to JavaScript
     * for form takeover functionality and deadline enforcement.
     * 
     * @since 1.2.0
     */
    public function ajax_get_open_call_info() {
        // Verify nonce for security
        if (!check_ajax_referer('cf7as_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error('Form ID is required');
            return;
        }
        
        $open_call = $this->get_open_call_by_form_id($form_id);
        
        if (!$open_call) {
            // Check if it's a legacy form configuration
            $options = get_option('cf7_artist_submissions_options', array());
            $legacy_form_id = !empty($options['form_id']) ? $options['form_id'] : '';
            
            if ($legacy_form_id && intval($legacy_form_id) === intval($form_id)) {
                // Get visual arts mediums for legacy forms
                $mediums = get_terms(array(
                    'taxonomy' => 'artistic_medium',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                $mediums_data = array();
                if (!empty($mediums) && !is_wp_error($mediums)) {
                    foreach ($mediums as $medium) {
                        $bg_color = get_term_meta($medium->term_id, 'medium_color', true);
                        $text_color = get_term_meta($medium->term_id, 'medium_text_color', true);
                        
                        $mediums_data[] = array(
                            'term_id' => $medium->term_id,
                            'name' => $medium->name,
                            'bg_color' => $bg_color,
                            'text_color' => $text_color
                        );
                    }
                }
                
                wp_send_json_success(array(
                    'is_open' => true,
                    'status_message' => '',
                    'title' => 'Submit Your Work',
                    'call_type' => 'visual_arts', // Default for legacy forms
                    'mediums' => $mediums_data
                ));
                return;
            }
            
            wp_send_json_error('Form not configured for submissions');
            return;
        }
        
        // Add mediums data to open call response
        $call_type = isset($open_call['call_type']) ? $open_call['call_type'] : 'visual_arts';
        $taxonomy = ($call_type === 'text_based') ? 'text_medium' : 'artistic_medium';
        
        $mediums = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $mediums_data = array();
        if (!empty($mediums) && !is_wp_error($mediums)) {
            foreach ($mediums as $medium) {
                $bg_color = get_term_meta($medium->term_id, 'medium_color', true);
                $text_color = get_term_meta($medium->term_id, 'medium_text_color', true);
                
                $mediums_data[] = array(
                    'term_id' => $medium->term_id,
                    'name' => $medium->name,
                    'bg_color' => $bg_color,
                    'text_color' => $text_color
                );
            }
        }
        
        $open_call['mediums'] = $mediums_data;
        
        wp_send_json_success($open_call);
    }

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
        
        $current_form_id = $contact_form->id();
        $should_process = false;
        
        // Check if this form is configured in open calls
        $open_calls_options = get_option('cf7_artist_submissions_open_calls', array());
        if (!empty($open_calls_options['calls'])) {
            foreach ($open_calls_options['calls'] as $call_config) {
                if (!empty($call_config['form_id']) && 
                    intval($call_config['form_id']) === intval($current_form_id) &&
                    ($call_config['status'] ?? 'active') === 'active') {
                    $should_process = true;
                    error_log('CF7AS Form Handler Debug - Form matches active open call: ' . ($call_config['title'] ?? 'Unnamed'));
                    break;
                }
            }
        }
        
        // If not found in open calls, check legacy general form configuration
        if (!$should_process) {
            $options = get_option('cf7_artist_submissions_options', array());
            $legacy_form_id = !empty($options['form_id']) ? $options['form_id'] : '';
            
            if ($legacy_form_id && intval($legacy_form_id) === intval($current_form_id)) {
                $should_process = true;
                error_log('CF7AS Form Handler Debug - Form matches legacy configuration');
            }
        }
        
        // Only process if form is configured
        if (!$should_process) {
            error_log('CF7AS Form Handler Debug - Form not configured for processing, skipping');
            return;
        }
        
        error_log('CF7AS Form Handler Debug - Form configured for processing, proceeding');
        
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
        
        // Handle text medium tags
        error_log('CF7AS Form Handler Debug - Processing text medium tags');
        $this->process_text_medium_tags($post_id, $posted_data);
        
        // Handle open call assignment
        error_log('CF7AS Form Handler Debug - Processing open call assignment');
        $this->process_open_call_assignment($post_id, $posted_data);
        
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
                    // For mediums field, values are term IDs, convert to term objects
                    if ($field === 'mediums') {
                        foreach ($field_value as $term_id) {
                            $term = get_term($term_id, 'artistic_medium');
                            if ($term && !is_wp_error($term)) {
                                $medium_terms[] = $term->name;
                            }
                        }
                    } else {
                        $medium_terms = array_merge($medium_terms, $field_value);
                    }
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
            
            error_log('CF7AS Form Handler Debug - Assigned mediums: ' . implode(', ', $medium_terms));
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
        $processed_fields = array(); // Track which fields we've already processed

        // Look for the custom uploader file data fields ending with '_data'
        foreach ($posted_data as $field_name => $field_value) {
            // Check if this is a file data field (ends with '_data') and has content
            if (substr($field_name, -5) === '_data' && !empty($field_value)) {
                $found_file_fields++;
                $processed_fields[] = $field_name; // Mark this field as processed
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

        // Also check for traditional file upload fields (fallback) - but skip already processed fields
        $file_data_fields = array();
        foreach ($posted_data as $field_name => $field_value) {
            // Skip fields we've already processed in the first pass
            if (in_array($field_name, $processed_fields)) {
                error_log('CF7AS Form Handler Debug - Skipping already processed field: ' . $field_name);
                continue;
            }
            
            if (strpos($field_name, 'file') !== false || strpos($field_name, 'upload') !== false || strpos($field_name, 'artwork') !== false) {
                if (!in_array($field_name, $file_data_fields)) {
                    $file_data_fields[] = $field_name;
                }
            }
        }

        error_log('CF7AS Form Handler Debug - Found traditional file fields (after deduplication): ' . print_r($file_data_fields, true));

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
            
            // Check if this S3 key already exists for this submission to prevent duplicates
            $existing_file = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE submission_id = %s AND s3_key = %s",
                (string) $post_id,
                $s3_key
            ));
            
            if ($existing_file) {
                error_log('CF7AS File Storage Debug - Skipping duplicate file with S3 key: ' . $s3_key . ' (existing ID: ' . $existing_file . ')');
                continue; // Skip duplicate file
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
                $file_id = $wpdb->insert_id;
                error_log('CF7AS File Storage Debug - Successfully inserted file record with ID: ' . $file_id);
                
                // Trigger media conversion for the uploaded file
                $file_metadata = array(
                    'submission_id' => (string) $post_id,
                    'original_name' => $original_name,
                    'mime_type' => $mime_type,
                    'file_size' => isset($file['size']) ? intval($file['size']) : 0
                );
                
                // Fire action hook for media conversion
                do_action('cf7as_file_uploaded', $s3_key, $file_metadata);
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
    
    // ============================================================================
    // CUSTOM FORM TAGS SECTION
    // ============================================================================
    
    /**
     * Render the mediums form tag with multiple checkbox selection.
     * 
     * Creates a multiple checkbox input field populated with artistic mediums
     * from the artistic_medium taxonomy. Provides comprehensive medium selection
     * interface with visual styling, validation support, and seamless CF7 integration.
     * 
     * @param object $tag The CF7 form tag object
     * @return string HTML output for the mediums form field
     * @since 1.1.0
     */
    public function render_mediums_form_tag($tag) {
        if (empty($tag->name)) {
            return '';
        }
        
        // Get validation error and CSS classes
        $validation_error = function_exists('wpcf7_get_validation_error') ? wpcf7_get_validation_error($tag->name) : '';
        $class = function_exists('wpcf7_form_controls_class') ? wpcf7_form_controls_class($tag->type) : 'wpcf7-form-control';
        
        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }
        
        // Check if field is required
        $is_required = method_exists($tag, 'has_option') && $tag->has_option('required');
        if ($is_required) {
            $class .= ' wpcf7-validates-as-required';
        }
        
        // Get artistic mediums from taxonomy
        $mediums = get_terms(array(
            'taxonomy' => 'artistic_medium',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($mediums) || is_wp_error($mediums)) {
            return '<div class="cf7as-mediums-error">No artistic mediums available. Please contact the administrator.</div>';
        }
        
        // Build the HTML output
        $html = '<div class="cf7as-mediums-wrapper ' . esc_attr($class) . '">';
        
        // Add field label if specified
        if (method_exists($tag, 'get_option')) {
            $label = $tag->get_option('label', '', true);
            if (!empty($label)) {
                $html .= '<div class="cf7as-mediums-label">' . esc_html($label) . '</div>';
            }
        }
        
        $html .= '<div class="cf7as-mediums-checkboxes">';
        
        foreach ($mediums as $medium) {
            // Get medium colors for styling
            $bg_color = get_term_meta($medium->term_id, 'medium_color', true);
            $text_color = get_term_meta($medium->term_id, 'medium_text_color', true);
            
            $style_vars = '';
            if ($bg_color && $text_color) {
                $style_vars = ' style="--medium-bg: ' . esc_attr($bg_color) . '; --medium-text: ' . esc_attr($text_color) . ';"';
            }
            
            $html .= '<label class="cf7as-medium-checkbox"' . $style_vars . '>';
            $html .= '<input type="checkbox" name="' . esc_attr($tag->name) . '[]" value="' . esc_attr($medium->term_id) . '"';
            
            if ($is_required) {
                $html .= ' required';
            }
            
            $html .= '>';
            $html .= '<span class="cf7as-checkbox-mark"></span>';
            $html .= '<span class="cf7as-medium-name">' . esc_html($medium->name) . '</span>';
            $html .= '</label>';
        }
        
        $html .= '</div>'; // .cf7as-mediums-checkboxes
        
        // Add validation error if present
        if ($validation_error) {
            $html .= '<span class="wpcf7-not-valid-tip">' . esc_html($validation_error) . '</span>';
        }
        
        $html .= '</div>'; // .cf7as-mediums-wrapper
        
        return $html;
    }
    
    /**
     * Add mediums tag generator to CF7 form builder interface.
     * 
     * Registers the mediums form tag generator to appear in the Contact Form 7
     * form builder interface, allowing users to easily add mediums fields to forms.
     * 
     * @since 1.1.0
     */
    public function add_mediums_tag_generator() {
        if (function_exists('wpcf7_add_tag_generator')) {
            wpcf7_add_tag_generator(
                'mediums',
                __('Artistic Mediums', 'cf7-artist-submissions'),
                'cf7as-mediums-tag-generator',
                array($this, 'mediums_tag_generator')
            );
        }
    }
    
    /**
     * Render the mediums tag generator interface in CF7 form builder.
     * 
     * Creates the form builder interface for inserting mediums form tags
     * with options for required fields and custom labels.
     * 
     * @param object $contact_form The CF7 contact form object
     * @param array $args Arguments for the tag generator
     * @since 1.1.0
     */
    public function mediums_tag_generator($contact_form, $args = '') {
        $args = wp_parse_args($args, array());
        $type = 'mediums';
        
        $description = __('Generate a form-tag for artistic mediums selection. Multiple checkboxes populated from the artistic_medium taxonomy.', 'cf7-artist-submissions');
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo esc_html($description); ?></legend>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'cf7-artist-submissions')); ?></label>
                            </th>
                            <td>
                                <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-label'); ?>"><?php echo esc_html(__('Label', 'cf7-artist-submissions')); ?></label>
                            </th>
                            <td>
                                <input type="text" name="label" class="oneline option" id="<?php echo esc_attr($args['content'] . '-label'); ?>" />
                                <p class="description"><?php echo esc_html(__('Optional label to display above the checkboxes.', 'cf7-artist-submissions')); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php echo esc_html(__('Field Settings', 'cf7-artist-submissions')); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html(__('Field Settings', 'cf7-artist-submissions')); ?></legend>
                                    <label>
                                        <input type="checkbox" name="required" class="option" />
                                        <?php echo esc_html(__('Required field', 'cf7-artist-submissions')); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'cf7-artist-submissions')); ?></label>
                            </th>
                            <td>
                                <input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'cf7-artist-submissions')); ?></label>
                            </th>
                            <td>
                                <input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        
        <div class="insert-box">
            <input type="text" name="<?php echo esc_attr($type); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
            
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'cf7-artist-submissions')); ?>" />
            </div>
            
            <br class="clear" />
            
            <p class="description mail-tag">
                <label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>">
                    <?php echo sprintf(esc_html(__('To use the value input through this field in a mail template, you need to insert the corresponding mail-tag (%s) into the template.', 'contact-form-7')), '<strong><span class="mail-tag"></span></strong>'); ?>
                </label>
                <input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" />
            </p>
        </div>
        <?php
    }
    
    /**
     * Process text medium tags for literary and text-based submissions.
     * 
     * Handles assignment of text medium taxonomy terms to submissions
     * based on form field data. Supports various field name patterns
     * for text-based artistic mediums.
     * 
     * @since 1.2.0
     */
    private function process_text_medium_tags($post_id, $posted_data) {
        // Look for fields that might contain text medium information
        $text_medium_fields = array(
            'text-medium',
            'text_medium',
            'text-mediums',
            'text_mediums',
            'literary-medium',
            'literary_medium',
            'writing-type',
            'writing_type',
            'genre',
            'genres'
        );
        
        $text_medium_terms = array();
        
        // Check each possible field name
        foreach ($text_medium_fields as $field) {
            if (isset($posted_data[$field]) && !empty($posted_data[$field])) {
                $field_value = $posted_data[$field];
                
                // Handle array values (checkboxes/multiple selects)
                if (is_array($field_value)) {
                    // For text-mediums field, values are term IDs, convert to term objects
                    if ($field === 'text-mediums' || $field === 'text_mediums') {
                        foreach ($field_value as $term_id) {
                            $term = get_term($term_id, 'text_medium');
                            if ($term && !is_wp_error($term)) {
                                $text_medium_terms[] = $term->name;
                            }
                        }
                    } else {
                        $text_medium_terms = array_merge($text_medium_terms, $field_value);
                    }
                } else {
                    // Handle comma-separated values or single values
                    $values = array_map('trim', explode(',', $field_value));
                    $text_medium_terms = array_merge($text_medium_terms, $values);
                }
            }
        }
        
        // Clean and validate terms
        $text_medium_terms = array_filter(array_map('sanitize_text_field', $text_medium_terms));
        
        if (!empty($text_medium_terms)) {
            // Set the text medium terms for this submission
            wp_set_object_terms($post_id, $text_medium_terms, 'text_medium');
            
            // Also store as post meta for backwards compatibility
            update_post_meta($post_id, 'cf7_text_mediums', implode(', ', $text_medium_terms));
            
            error_log('CF7AS Form Handler Debug - Assigned ' . count($text_medium_terms) . ' text medium terms to post ' . $post_id);
        }
    }
    
    /**
     * Process open call assignment for submissions.
     * 
     * Assigns submissions to appropriate open call categories based on
     * form data or configuration. Supports automatic assignment based
     * on form ID or explicit field selection.
     * 
     * @since 1.2.0
     */
    private function process_open_call_assignment($post_id, $posted_data) {
        $open_call_term = null;
        
        // Check if there's an explicit open call field in the form
        $open_call_fields = array(
            'open-call',
            'open_call',
            'call',
            'submission-call',
            'submission_call'
        );
        
        foreach ($open_call_fields as $field) {
            if (isset($posted_data[$field]) && !empty($posted_data[$field])) {
                $field_value = $posted_data[$field];
                
                if (is_array($field_value)) {
                    $field_value = $field_value[0]; // Take first value if array
                }
                
                // Try to find the term by slug or name
                $term = get_term_by('slug', sanitize_title($field_value), 'open_call');
                if (!$term) {
                    $term = get_term_by('name', $field_value, 'open_call');
                }
                
                if ($term && !is_wp_error($term)) {
                    $open_call_term = $term;
                    break;
                }
            }
        }
        
        // If no explicit open call field, try to determine from form configuration
        if (!$open_call_term) {
            // Get the form ID that processed this submission from CF7
            $current_form_id = null;
            
            // Try to get form ID from CF7 contact form instance
            if (class_exists('WPCF7_ContactForm')) {
                $submission = WPCF7_Submission::get_instance();
                if ($submission) {
                    $contact_form = $submission->get_contact_form();
                    if ($contact_form) {
                        $current_form_id = $contact_form->id();
                    }
                }
            }
            
            // Look up form ID in open calls configuration
            if ($current_form_id) {
                $open_calls_options = get_option('cf7_artist_submissions_open_calls', array());
                
                if (!empty($open_calls_options['calls'])) {
                    foreach ($open_calls_options['calls'] as $call_config) {
                        if (!empty($call_config['form_id']) && 
                            intval($call_config['form_id']) === intval($current_form_id) &&
                            !empty($call_config['title']) &&
                            ($call_config['status'] ?? 'active') === 'active') {
                            
                            // Find the corresponding taxonomy term
                            $term = get_term_by('name', $call_config['title'], 'open_call');
                            if ($term && !is_wp_error($term)) {
                                $open_call_term = $term;
                                break;
                            }
                        }
                    }
                }
            }
            
            // Fallback to legacy form ID configuration
            if (!$open_call_term) {
                $options = get_option('cf7_artist_submissions_options', array());
                $legacy_form_id = !empty($options['form_id']) ? $options['form_id'] : '';
                
                if ($legacy_form_id && intval($legacy_form_id) === intval($current_form_id)) {
                    // Look for general submissions or first available call
                    $all_calls = get_terms(array(
                        'taxonomy' => 'open_call',
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!empty($all_calls) && !is_wp_error($all_calls)) {
                        // Prefer "General Submissions" if it exists
                        foreach ($all_calls as $call) {
                            if (strtolower($call->slug) === 'general-submissions') {
                                $open_call_term = $call;
                                break;
                            }
                        }
                        
                        // Otherwise use first available
                        if (!$open_call_term) {
                            $open_call_term = $all_calls[0];
                        }
                    }
                }
            }
        }
        
        // Fallback to default "General Submissions" if no specific call found
        if (!$open_call_term) {
            $open_call_term = get_term_by('slug', 'general-submissions', 'open_call');
            
            // Create default term if it doesn't exist
            if (!$open_call_term) {
                $result = wp_insert_term('General Submissions', 'open_call', array(
                    'slug' => 'general-submissions',
                    'description' => 'Default category for general artist submissions'
                ));
                
                if (!is_wp_error($result)) {
                    $open_call_term = get_term($result['term_id'], 'open_call');
                }
            }
        }
        
        // Assign the open call term
        if ($open_call_term && !is_wp_error($open_call_term)) {
            wp_set_object_terms($post_id, array($open_call_term->term_id), 'open_call');
            
            // Also store as post meta for easy access
            update_post_meta($post_id, 'cf7_open_call', $open_call_term->name);
            update_post_meta($post_id, 'cf7_open_call_slug', $open_call_term->slug);
            
            error_log('CF7AS Form Handler Debug - Assigned open call "' . $open_call_term->name . '" to post ' . $post_id);
        }
    }
}