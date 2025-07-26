<?php
/**
 * CF7 Artist Submissions - Add Submission Interface
 *
 * Custom "Add New Submission" interface to replace the default WordPress post editor
 * with a purpose-built form that works independently without requiring existing data.
 * Provides comprehensive submission creation capabilities with all necessary fields.
 *
 * Features:
 * • Custom add submission form with all required fields
 * • Artistic medium selection with visual interface
 * • File upload handling for artwork submissions
 * • Validation and sanitization of all input data
 * • Automatic status setting and taxonomy assignment
 * • Seamless redirect to tabbed interface after creation
 * • Integration with existing submission workflow
 *
 * @package CF7_Artist_Submissions
 * @subpackage AddSubmission
 * @since 1.0.1
 * @version 1.0.1
 */

/**
 * CF7 Artist Submissions Add Submission Class
 * 
 * Manages the custom "Add New Submission" interface, replacing the default
 * WordPress post editor with a specialized form designed for creating new
 * submissions without requiring existing data.
 * 
 * @since 1.0.1
 */
class CF7_Artist_Submissions_Add_Submission {
    
    /**
     * Initialize the add submission interface system.
     * 
     * Sets up custom page replacement, form handling, and asset loading
     * for the specialized add submission interface.
     * 
     * @since 1.0.1
     */
    public static function init() {
        add_action('current_screen', array(__CLASS__, 'check_add_submission_page'));
        add_action('wp_ajax_cf7_create_submission', array(__CLASS__, 'ajax_create_submission'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    /**
     * Check if we're on the add submission page and replace content.
     * 
     * Detects the WordPress "Add New" page for cf7_submission post type
     * and replaces it with our custom interface.
     * 
     * @since 1.0.1
     */
    public static function check_add_submission_page() {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission' || $screen->action !== 'add') {
            return;
        }
        
        // Replace the default add new post page
        add_action('edit_form_after_title', array(__CLASS__, 'render_custom_add_form'));
        add_action('admin_head', array(__CLASS__, 'hide_default_editor_elements'));
    }
    
    /**
     * Hide default WordPress editor elements for clean add submission interface.
     * 
     * Removes standard WordPress editor components to provide a clean,
     * purpose-built interface for submission creation.
     * 
     * @since 1.0.1
     */
    public static function hide_default_editor_elements() {
        ?>
        <style>
            /* Hide default WordPress editor elements */
            #postdiv,
            #postbox-container-1,
            #normal-sortables,
            #advanced-sortables,
            #side-sortables .postbox,
            #submitdiv,
            #categorydiv,
            #tagsdiv-post_tag,
            #postimagediv,
            .page-title-action,
            #screen-options-link-wrap,
            #screen-meta,
            .screen-meta-toggle {
                display: none !important;
            }
            
            /* Hide title field */
            #titlediv {
                display: none !important;
            }
            
            /* Make our form full width */
            #poststuff {
                width: 100% !important;
            }
            
            #post-body.columns-2 {
                margin-right: 0 !important;
            }
            
            #post-body.columns-2 #postbox-container-2 {
                width: 100% !important;
            }
            
            /* Style our custom form */
            .cf7-add-submission-container {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .cf7-add-form-section {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .cf7-add-form-section:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            
            .cf7-add-form-section h3 {
                margin: 0 0 15px 0;
                color: #23282d;
                font-size: 16px;
                font-weight: 600;
            }
            
            .cf7-add-form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            .cf7-add-form-field {
                margin-bottom: 15px;
            }
            
            .cf7-add-form-field.full-width {
                grid-column: 1 / -1;
            }
            
            .cf7-add-form-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #23282d;
            }
            
            .cf7-add-form-field input[type="text"],
            .cf7-add-form-field input[type="email"],
            .cf7-add-form-field input[type="tel"],
            .cf7-add-form-field input[type="url"],
            .cf7-add-form-field textarea,
            .cf7-add-form-field select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .cf7-add-form-field textarea {
                min-height: 100px;
                resize: vertical;
            }
            
            .cf7-add-mediums-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }
            
            .cf7-add-medium-option {
                display: flex;
                align-items: center;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .cf7-add-medium-option:hover {
                border-color: #0073aa;
                background-color: #f9f9f9;
            }
            
            .cf7-add-medium-option input[type="checkbox"] {
                margin-right: 8px;
            }
            
            .cf7-add-file-upload {
                border: 2px dashed #ddd;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: border-color 0.2s ease;
            }
            
            .cf7-add-file-upload:hover {
                border-color: #0073aa;
            }
            
            .cf7-add-file-upload.dragover {
                border-color: #0073aa;
                background-color: #f9f9f9;
            }
            
            .cf7-add-form-actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
                text-align: right;
            }
            
            .cf7-add-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                display: inline-block;
                margin-left: 10px;
                transition: all 0.2s ease;
            }
            
            .cf7-add-btn-primary {
                background: #0073aa;
                color: white;
            }
            
            .cf7-add-btn-primary:hover {
                background: #005a87;
                color: white;
            }
            
            .cf7-add-btn-secondary {
                background: #f0f0f1;
                color: #50575e;
                border: 1px solid #c3c4c7;
            }
            
            .cf7-add-btn-secondary:hover {
                background: #e5e5e5;
                color: #50575e;
            }
            
            .cf7-add-success {
                background: #d1e7dd;
                border: 1px solid #badbcc;
                color: #0f5132;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            
            .cf7-add-error {
                background: #f8d7da;
                border: 1px solid #f5c2c7;
                color: #842029;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            
            .cf7-add-loading {
                display: none;
                text-align: center;
                padding: 20px;
            }
            
            .cf7-add-loading-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #0073aa;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 10px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @media (max-width: 768px) {
                .cf7-add-form-grid {
                    grid-template-columns: 1fr;
                }
                
                .cf7-add-mediums-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Render the custom add submission form.
     * 
     * Displays a comprehensive form interface for creating new submissions
     * with all necessary fields and validation.
     * 
     * @since 1.0.1
     */
    public static function render_custom_add_form() {
        global $post;
        
        // Get artistic mediums for selection
        $mediums = get_terms(array(
            'taxonomy' => 'artistic_medium',
            'hide_empty' => false,
        ));
        
        ?>
        <div class="cf7-add-submission-container">
            <div id="cf7-add-messages"></div>
            
            <form id="cf7-add-submission-form" enctype="multipart/form-data">
                <?php wp_nonce_field('cf7_add_submission', 'cf7_add_submission_nonce'); ?>
                
                <!-- Artist Information Section -->
                <div class="cf7-add-form-section">
                    <h3><?php _e('Artist Information', 'cf7-artist-submissions'); ?></h3>
                    <div class="cf7-add-form-grid">
                        <div class="cf7-add-form-field">
                            <label for="artist_name"><?php _e('Artist Name', 'cf7-artist-submissions'); ?> *</label>
                            <input type="text" id="artist_name" name="artist_name" required>
                        </div>
                        <div class="cf7-add-form-field">
                            <label for="artist_email"><?php _e('Email Address', 'cf7-artist-submissions'); ?> *</label>
                            <input type="email" id="artist_email" name="artist_email" required>
                        </div>
                        <div class="cf7-add-form-field">
                            <label for="artist_phone"><?php _e('Phone Number', 'cf7-artist-submissions'); ?></label>
                            <input type="tel" id="artist_phone" name="artist_phone">
                        </div>
                        <div class="cf7-add-form-field">
                            <label for="artist_website"><?php _e('Website', 'cf7-artist-submissions'); ?></label>
                            <input type="url" id="artist_website" name="artist_website">
                        </div>
                        <div class="cf7-add-form-field full-width">
                            <label for="artist_address"><?php _e('Address', 'cf7-artist-submissions'); ?></label>
                            <textarea id="artist_address" name="artist_address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Submission Details Section -->
                <div class="cf7-add-form-section">
                    <h3><?php _e('Submission Details', 'cf7-artist-submissions'); ?></h3>
                    <div class="cf7-add-form-grid">
                        <div class="cf7-add-form-field full-width">
                            <label for="project_title"><?php _e('Project/Work Title', 'cf7-artist-submissions'); ?></label>
                            <input type="text" id="project_title" name="project_title">
                        </div>
                        <div class="cf7-add-form-field full-width">
                            <label for="project_description"><?php _e('Project Description', 'cf7-artist-submissions'); ?></label>
                            <textarea id="project_description" name="project_description" rows="4"></textarea>
                        </div>
                        <div class="cf7-add-form-field full-width">
                            <label for="artist_statement"><?php _e('Artist Statement', 'cf7-artist-submissions'); ?></label>
                            <textarea id="artist_statement" name="artist_statement" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Artistic Mediums Section -->
                <div class="cf7-add-form-section">
                    <h3><?php _e('Artistic Mediums', 'cf7-artist-submissions'); ?></h3>
                    <p class="description"><?php _e('Select all mediums that apply to this submission.', 'cf7-artist-submissions'); ?></p>
                    <div class="cf7-add-mediums-grid">
                        <?php if (!empty($mediums) && !is_wp_error($mediums)): ?>
                            <?php foreach ($mediums as $medium): ?>
                                <div class="cf7-add-medium-option">
                                    <input type="checkbox" id="medium_<?php echo esc_attr($medium->term_id); ?>" name="artistic_mediums[]" value="<?php echo esc_attr($medium->term_id); ?>">
                                    <label for="medium_<?php echo esc_attr($medium->term_id); ?>"><?php echo esc_html($medium->name); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- File Upload Section -->
                <div class="cf7-add-form-section">
                    <h3><?php _e('Artwork Files', 'cf7-artist-submissions'); ?></h3>
                    <p class="description"><?php _e('Upload images or editable documents of your artwork. Multiple files are supported.', 'cf7-artist-submissions'); ?></p>
                    <div class="cf7-add-file-upload" id="cf7-file-upload-area">
                        <input type="file" id="artwork_files" name="artwork_files[]" multiple accept="image/*,.doc,.docx" style="display: none;">
                        <div class="cf7-upload-text">
                            <p><strong><?php _e('Click to select files or drag and drop', 'cf7-artist-submissions'); ?></strong></p>
                            <p><?php _e('Supported formats: Images (JPG, PNG, GIF), PDF, Word documents', 'cf7-artist-submissions'); ?></p>
                        </div>
                        <div id="cf7-selected-files" style="margin-top: 15px;"></div>
                    </div>
                </div>
                
                <!-- Additional Information Section -->
                <div class="cf7-add-form-section">
                    <h3><?php _e('Additional Information', 'cf7-artist-submissions'); ?></h3>
                    <div class="cf7-add-form-grid">
                        <div class="cf7-add-form-field">
                            <label for="cv_link"><?php _e('CV/Resume Link', 'cf7-artist-submissions'); ?></label>
                            <input type="url" id="cv_link" name="cv_link">
                        </div>
                        <div class="cf7-add-form-field">
                            <label for="portfolio_link"><?php _e('Portfolio Link', 'cf7-artist-submissions'); ?></label>
                            <input type="url" id="portfolio_link" name="portfolio_link">
                        </div>
                        <div class="cf7-add-form-field full-width">
                            <label for="additional_notes"><?php _e('Additional Notes', 'cf7-artist-submissions'); ?></label>
                            <textarea id="additional_notes" name="additional_notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="cf7-add-form-actions">
                    <div class="cf7-add-loading" id="cf7-add-loading">
                        <span class="cf7-add-loading-spinner"></span>
                        <?php _e('Creating submission...', 'cf7-artist-submissions'); ?>
                    </div>
                    <a href="<?php echo admin_url('edit.php?post_type=cf7_submission'); ?>" class="cf7-add-btn cf7-add-btn-secondary">
                        <?php _e('Cancel', 'cf7-artist-submissions'); ?>
                    </a>
                    <button type="submit" class="cf7-add-btn cf7-add-btn-primary" id="cf7-submit-button">
                        <?php _e('Create Submission', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue assets for the add submission interface.
     * 
     * Loads JavaScript for form handling, file uploads, and AJAX communication.
     * 
     * @since 1.0.1
     */
    public static function enqueue_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission' || $screen->action !== 'add') {
            return;
        }
        
        wp_enqueue_script(
            'cf7-add-submission',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/add-submission.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
        
        wp_localize_script('cf7-add-submission', 'cf7AddSubmission', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_add_submission'),
            'strings' => array(
                'creating' => __('Creating submission...', 'cf7-artist-submissions'),
                'success' => __('Submission created successfully!', 'cf7-artist-submissions'),
                'error' => __('Error creating submission. Please try again.', 'cf7-artist-submissions'),
                'fileSelected' => __('file selected', 'cf7-artist-submissions'),
                'filesSelected' => __('files selected', 'cf7-artist-submissions'),
                'removeFile' => __('Remove', 'cf7-artist-submissions')
            )
        ));
    }
    
    /**
     * AJAX handler for creating new submissions.
     * 
     * Processes the submitted form data, creates the submission post,
     * handles file uploads, and returns the new submission URL.
     * 
     * @since 1.0.1
     */
    public static function ajax_create_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['cf7_add_submission_nonce'], 'cf7_add_submission')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Validate required fields
        if (empty($_POST['artist_name']) || empty($_POST['artist_email'])) {
            wp_send_json_error(array('message' => 'Artist name and email are required'));
        }
        
        // Sanitize form data
        $artist_name = sanitize_text_field($_POST['artist_name']);
        $artist_email = sanitize_email($_POST['artist_email']);
        
        // Create the submission post
        $post_data = array(
            'post_title' => $artist_name,
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'post_content' => '',
        );
        
        $submission_id = wp_insert_post($post_data);
        
        if (is_wp_error($submission_id)) {
            wp_send_json_error(array('message' => 'Failed to create submission'));
        }
        
        // Set initial status
        wp_set_object_terms($submission_id, 'New', 'submission_status');
        
        // Save form fields as post meta
        $fields = array(
            'cf7_artist-name' => $artist_name,
            'cf7_email' => $artist_email,
            'cf7_phone' => sanitize_text_field($_POST['artist_phone'] ?? ''),
            'cf7_website' => esc_url_raw($_POST['artist_website'] ?? ''),
            'cf7_address' => sanitize_textarea_field($_POST['artist_address'] ?? ''),
            'cf7_project-title' => sanitize_text_field($_POST['project_title'] ?? ''),
            'cf7_project-description' => sanitize_textarea_field($_POST['project_description'] ?? ''),
            'cf7_artist-statement' => sanitize_textarea_field($_POST['artist_statement'] ?? ''),
            'cf7_cv-link' => esc_url_raw($_POST['cv_link'] ?? ''),
            'cf7_portfolio-link' => esc_url_raw($_POST['portfolio_link'] ?? ''),
            'cf7_additional-notes' => sanitize_textarea_field($_POST['additional_notes'] ?? ''),
        );
        
        foreach ($fields as $key => $value) {
            if (!empty($value)) {
                update_post_meta($submission_id, $key, $value);
            }
        }
        
        // Handle artistic mediums
        if (!empty($_POST['artistic_mediums']) && is_array($_POST['artistic_mediums'])) {
            $medium_ids = array_map('intval', $_POST['artistic_mediums']);
            wp_set_object_terms($submission_id, $medium_ids, 'artistic_medium');
        }
        
        // Handle file uploads
        if (!empty($_FILES['artwork_files']['name'][0])) {
            $uploaded_files = array();
            $file_count = count($_FILES['artwork_files']['name']);
            
            // WordPress file upload functions
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['artwork_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = array(
                        'name' => $_FILES['artwork_files']['name'][$i],
                        'type' => $_FILES['artwork_files']['type'][$i],
                        'tmp_name' => $_FILES['artwork_files']['tmp_name'][$i],
                        'error' => $_FILES['artwork_files']['error'][$i],
                        'size' => $_FILES['artwork_files']['size'][$i]
                    );
                    
                    $uploaded_file = wp_handle_upload($file, array('test_form' => false));
                    
                    if (!isset($uploaded_file['error'])) {
                        $uploaded_files[] = $uploaded_file['url'];
                    }
                }
            }
            
            if (!empty($uploaded_files)) {
                update_post_meta($submission_id, 'cf7_uploaded_files', $uploaded_files);
            }
        }
        
        // Log the creation
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $submission_id,
                'submission_created',
                'Submission created manually via admin interface',
                array(
                    'artist_name' => $artist_name,
                    'artist_email' => $artist_email
                )
            );
        }
        
        // Return success with redirect URL
        $edit_url = admin_url('post.php?post=' . $submission_id . '&action=edit');
        
        wp_send_json_success(array(
            'message' => 'Submission created successfully',
            'submission_id' => $submission_id,
            'redirect_url' => $edit_url
        ));
    }
}
