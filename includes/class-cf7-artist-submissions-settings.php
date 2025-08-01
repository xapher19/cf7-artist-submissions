<?php
/**
 * CF7 Artist Submissions - Comprehensive Settings Management System
 *
 * Complete administrative interface for plugin configuration with Contact Form 7
 * integration, email system setup, conversation management, action logging, and
 * real-time testing tools for streamlined administrative workflow.
 *
 * Features:
 * • Contact Form 7 form selection and field validation
 * • Comprehensive email system configuration (SMTP, IMAP, plus addressing)
 * • Conversation system setup with token migration tools
 * • Action logging system with cron management and daily summaries
 * • File storage configuration with security validation
 * • Real-time diagnostic testing for all system components
 * • Template email testing and preview functionality
 * • Database schema management and migration tools
 *
 * @package CF7_Artist_Submissions
 * @subpackage SettingsManagement
 * @since 1.0.0
 * @version 1.2.0
 */

/**
 * CF7 Artist Submissions Settings Class
 * 
 * Comprehensive settings management system providing complete administrative
 * interface for plugin configuration, testing, and validation. Handles Contact
 * Form 7 integration, email system setup, conversation management, action logging,
 * and real-time diagnostic tools for optimal system administration and workflow.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Settings {
    
    // ============================================================================
    // INITIALIZATION SECTION
    // ============================================================================
    
    /**
     * Initialize comprehensive settings management system with complete functionality.
     * 
     * Establishes administrative interface, registers AJAX handlers for real-time
     * testing, configuration validation, email diagnostics, and system management.
     * Sets up complete settings workflow with validation, testing tools, and
     * database management for optimal administrative experience and system control.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'settings_notice'));
        
        // AJAX handlers for daily summary testing
        add_action('wp_ajax_test_daily_summary', array($this, 'ajax_test_daily_summary'));
        add_action('wp_ajax_setup_daily_cron', array($this, 'ajax_setup_daily_cron'));
        add_action('wp_ajax_clear_daily_cron', array($this, 'ajax_clear_daily_cron'));
        add_action('wp_ajax_update_actions_schema', array($this, 'ajax_update_actions_schema'));
        add_action('wp_ajax_migrate_conversation_tokens', array($this, 'ajax_migrate_conversation_tokens'));
        add_action('wp_ajax_test_form_config', array($this, 'ajax_test_form_config'));
        
        // AJAX handlers for email debugging
        add_action('wp_ajax_validate_email_config', array($this, 'ajax_validate_email_config'));
        add_action('wp_ajax_test_smtp_config', array($this, 'ajax_test_smtp_config'));
        
        // AJAX handlers for IMAP and other tests
        add_action('wp_ajax_cf7_test_imap', array($this, 'ajax_test_imap_connection'));
        add_action('wp_ajax_cf7_cleanup_inbox', array($this, 'ajax_cleanup_inbox'));
        
        // AJAX handlers for update management
        add_action('wp_ajax_cf7_check_updates', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_cf7_force_update_check', array($this, 'ajax_force_update_check'));
        add_action('wp_ajax_cf7_test_template', array($this, 'ajax_test_template_email'));
        add_action('wp_ajax_cf7_preview_template', array($this, 'ajax_preview_template_email'));
        
        // AJAX handlers for audit log functionality
        add_action('wp_ajax_update_missing_artist_info', array($this, 'ajax_update_missing_artist_info'));
        
        // AJAX handlers for S3 configuration
        add_action('wp_ajax_test_s3_connection', array($this, 'ajax_test_s3_connection'));
        
        // AJAX handler for PDF Lambda testing
        add_action('wp_ajax_cf7as_test_pdf_lambda', array($this, 'ajax_test_pdf_lambda'));
        
        // AJAX handlers for settings management
        add_action('wp_ajax_cf7_save_artist_settings', array($this, 'ajax_save_settings'));
        // SECURITY FIX: Removed wp_ajax_nopriv_ to prevent unauthenticated access
        
        // Bypass handler for settings save (working solution)
        add_action('wp_ajax_cf7_bypass_save', array($this, 'ajax_bypass_save_settings'));
        
        // Bypass handler for S3 test (working solution)
        add_action('wp_ajax_cf7_bypass_s3_test', array($this, 'ajax_bypass_test_s3_connection'));
        
        // Test handler to verify AJAX routing
        add_action('wp_ajax_cf7_test_ajax', array($this, 'ajax_test_handler'));
    }
    
    /**
     * Working bypass AJAX save handler
     */
    public function ajax_bypass_save_settings() {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Basic nonce check
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get existing options
        $existing_options = get_option('cf7_artist_submissions_options', array());
        
        // Update with new values
        $updated = false;
        if (isset($_POST['form_id'])) {
            $existing_options['form_id'] = sanitize_text_field($_POST['form_id']);
            $updated = true;
        }
        if (isset($_POST['menu_label'])) {
            $existing_options['menu_label'] = sanitize_text_field($_POST['menu_label']);
            $updated = true;
        }
        if (isset($_POST['store_files'])) {
            $existing_options['store_files'] = sanitize_text_field($_POST['store_files']);
            $updated = true;
        }
        if (isset($_POST['aws_access_key'])) {
            $existing_options['aws_access_key'] = sanitize_text_field($_POST['aws_access_key']);
            $updated = true;
        }
        if (isset($_POST['aws_secret_key'])) {
            $secret_raw = $_POST['aws_secret_key'];
            
            // Fix common URL encoding issues with + character
            // AWS secret keys can start with + which gets corrupted during URL encoding
            if (strlen($secret_raw) === 41) {
                // Check if it starts with "=+" (equals plus)
                if (substr($secret_raw, 0, 2) === '=+') {
                    $secret_raw = substr($secret_raw, 1); // Remove the leading =
                }
                // Check if it starts with space (space decoded from +)
                elseif (substr($secret_raw, 0, 1) === ' ') {
                    $secret_raw = '+' . substr($secret_raw, 1);
                }
            }
            
            $existing_options['aws_secret_key'] = sanitize_text_field($secret_raw);
            $updated = true;
        }
        if (isset($_POST['aws_region'])) {
            $existing_options['aws_region'] = sanitize_text_field($_POST['aws_region']);
            $updated = true;
        }
        if (isset($_POST['s3_bucket'])) {
            $existing_options['s3_bucket'] = sanitize_text_field($_POST['s3_bucket']);
            $updated = true;
        }
        
        if ($updated) {
            $result = update_option('cf7_artist_submissions_options', $existing_options);
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Settings saved successfully!'));
            } else {
                // Check if values are already the same
                $current = get_option('cf7_artist_submissions_options', array());
                if ($current === $existing_options) {
                    wp_send_json_success(array('message' => 'Settings are already up to date.'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to save settings'));
                }
            }
        } else {
            wp_send_json_error(array('message' => 'No settings to update'));
        }
    }
    
    /**
     * Working bypass AJAX S3 test handler
     */
    public function ajax_bypass_test_s3_connection() {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Basic nonce check
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $aws_key = sanitize_text_field($_POST['aws_access_key'] ?? '');
        $aws_secret_raw = $_POST['aws_secret_key'] ?? '';
        
        // Fix common URL encoding issues with + character
        // AWS secret keys can start with + which gets corrupted during URL encoding
        if (strlen($aws_secret_raw) === 41) {
            // Check if it starts with "=+" (equals plus)
            if (substr($aws_secret_raw, 0, 2) === '=+') {
                $aws_secret_raw = substr($aws_secret_raw, 1); // Remove the leading =
            }
            // Check if it starts with space (space decoded from +)
            elseif (substr($aws_secret_raw, 0, 1) === ' ') {
                $aws_secret_raw = '+' . substr($aws_secret_raw, 1);
            }
        }
        
        $aws_secret = sanitize_text_field($aws_secret_raw);
        $aws_region = sanitize_text_field($_POST['aws_region'] ?? '');
        $s3_bucket = sanitize_text_field($_POST['s3_bucket'] ?? '');
        
        if (empty($aws_key) || empty($aws_secret) || empty($aws_region) || empty($s3_bucket)) {
            wp_send_json_error(array('message' => 'All S3 fields are required'));
            return;
        }
        
        try {
            // Initialize our S3 handler with the provided credentials
            $s3_handler = new CF7_Artist_Submissions_S3_Handler();
            
            // Get existing options to backup
            $existing_options = get_option('cf7_artist_submissions_options', array());
            $backup_options = $existing_options; // Full backup
            
            // Update options with test credentials (the format S3 handler expects)
            $existing_options['aws_access_key'] = $aws_key;
            $existing_options['aws_secret_key'] = $aws_secret;
            $existing_options['aws_region'] = $aws_region;
            $existing_options['s3_bucket'] = $s3_bucket;
            
            // Set test credentials in the format the S3 handler reads
            update_option('cf7_artist_submissions_options', $existing_options);
            
            // Test the connection by trying to list bucket contents (limited)
            $test_result = $s3_handler->test_connection();
            
            // Always restore original credentials after test
            update_option('cf7_artist_submissions_options', $backup_options);
            
            if (!$test_result['success']) {
                // Enhanced error response for 403 debugging
                $error_message = $test_result['message'];
                if (isset($test_result['details'])) {
                    $error_message .= '<br><br><div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #f59e0b; margin-top: 10px; text-align: left; white-space: pre-line; font-family: monospace; font-size: 12px;">';
                    $error_message .= '<strong>Detailed Error Information:</strong><br><br>' . esc_html($test_result['details']);
                    $error_message .= '</div>';
                }
                
                wp_send_json_error(array(
                    'message' => $error_message,
                    'details' => $test_result['details'] ?? 'Connection test failed'
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'S3 connection successful! Bucket is accessible.',
                'details' => array(
                    'region' => $aws_region,
                    'bucket' => $s3_bucket,
                    'status' => 'Connected',
                    'test_details' => $test_result['details'] ?? 'Connection verified'
                )
            ));
            
        } catch (Exception $e) {
            // Restore original credentials on error
            if (isset($backup_options)) {
                update_option('cf7_artist_submissions_options', $backup_options);
            }
            
            wp_send_json_error(array(
                'message' => 'S3 connection test failed: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Test AJAX handler to verify routing
     */
    public function ajax_test_handler() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        wp_send_json_success(array('message' => 'AJAX routing works!'));
    }
    
    // ============================================================================
    // ADMIN INTERFACE SECTION
    // ============================================================================
    
    /**
     * Register settings page in WordPress admin with proper menu positioning.
     * Creates submenu under CF7 Submissions with asset loading and integration.
     */
    public function add_settings_page() {
        // Add as submenu under Submissions instead of under Settings
        $page_hook = add_submenu_page(
            'edit.php?post_type=cf7_submission',  // Parent slug
            __('CF7 Submissions Settings', 'cf7-artist-submissions'),
            __('Settings', 'cf7-artist-submissions'),
            'manage_options',
            'cf7-artist-submissions-settings',
            array($this, 'render_settings_page')
        );
        
        // Enqueue styles and scripts only on this settings page
        add_action('admin_print_styles-' . $page_hook, array($this, 'enqueue_settings_assets'));
    }
    
    /**
     * Enqueue specialized assets for settings page interface.
     * Loads CSS dependencies and JavaScript with localization for AJAX functionality.
     */
    public function enqueue_settings_assets() {
        // Enqueue common styles first (foundation for all other styles)
        wp_enqueue_style('cf7-common-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/common.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
        
        // Enqueue settings-specific styles (depends on common.css)
        wp_enqueue_style('cf7-settings-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/settings.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
        
        // Enqueue admin styles for additional functionality (depends on common.css)
        wp_enqueue_style('cf7-admin-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/admin.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
        
        wp_enqueue_script('cf7-admin-js', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
        
        // Enqueue open calls JavaScript for settings page
        wp_enqueue_script('cf7-open-calls-js', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/open-calls.js', array('jquery', 'cf7-admin-js'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('cf7-admin-js', 'cf7_admin_ajax', array(
            'nonce' => wp_create_nonce('cf7_admin_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_email' => get_option('admin_email'),
            'strings' => array(
                'testing' => __('Testing...', 'cf7-artist-submissions'),
                'saving' => __('Saving...', 'cf7-artist-submissions'),
                'success' => __('Success!', 'cf7-artist-submissions'),
                'error' => __('Error occurred', 'cf7-artist-submissions'),
            )
        ));
        
        // Add Updates Tab AJAX data (for admin.js updates functionality)
        $update_transient = get_site_transient('update_plugins');
        $plugin_basename = plugin_basename(CF7_ARTIST_SUBMISSIONS_PLUGIN_FILE);
        $has_update = isset($update_transient->response[$plugin_basename]);
        
        wp_localize_script('cf7-admin-js', 'cf7_admin_vars', array(
            'nonce' => wp_create_nonce('cf7_admin_nonce'),
            'has_update' => $has_update,
            'checking_text' => __('Checking...', 'cf7-artist-submissions'),
            'update_check_failed_text' => __('Update check failed. Please try again.', 'cf7-artist-submissions')
        ));
        
        // Also provide backward compatibility
        wp_localize_script('cf7-admin-js', 'cf7ArtistSubmissions', array(
            'nonce' => wp_create_nonce('cf7_admin_nonce'),
            'conversationNonce' => wp_create_nonce('cf7_conversation_nonce'),
            'adminEmail' => get_option('admin_email'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ));
    }
    
    /**
     * Display admin notices for unconfigured plugin settings.
     * Shows warning when no Contact Form 7 form has been selected.
     */
    public function settings_notice() {
        $screen = get_current_screen();
        
        // Only show on plugins page or submissions post type screen
        if (!$screen || (!in_array($screen->id, array('plugins')) && $screen->post_type !== 'cf7_submission')) {
            return;
        }
        
        $options = get_option('cf7_artist_submissions_options', array());
        if (empty($options['form_id'])) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php _e('CF7 Artist Submissions is active but no form has been selected yet.', 'cf7-artist-submissions'); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=cf7_submission&page=cf7-artist-submissions-settings'); ?>">
                        <?php _e('Configure settings now', 'cf7-artist-submissions'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Register WordPress settings groups with validation callbacks.
     * Establishes options, email, and IMAP configuration groups.
     */
    public function register_settings() {
        register_setting('cf7_artist_submissions_options', 'cf7_artist_submissions_options', array($this, 'validate_options'));
        register_setting('cf7_artist_submissions_open_calls', 'cf7_artist_submissions_open_calls', array($this, 'validate_open_calls_options'));
        register_setting('cf7_artist_submissions_email_options', 'cf7_artist_submissions_email_options', array($this, 'validate_email_options'));
        register_setting('cf7_artist_submissions_imap_options', 'cf7_artist_submissions_imap_options', array($this, 'validate_imap_options'));
    }
    
    /**
     * Render comprehensive settings page interface with template integration.
     * Handles form submissions, permission checks, and loads modern template.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions (legacy support)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'update_missing_artist_info' && wp_verify_nonce($_POST['cf7_artist_info_nonce'], 'cf7_update_artist_info')) {
                if (class_exists('CF7_Artist_Submissions_Action_Log')) {
                    $updated_count = CF7_Artist_Submissions_Action_Log::update_missing_artist_info();
                    if ($updated_count > 0) {
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                             sprintf(__('Successfully updated %d audit log entries with artist information.', 'cf7-artist-submissions'), $updated_count) . 
                             '</p></div>';
                    } else {
                        echo '<div class="notice notice-info is-dismissible"><p>' . 
                             __('No audit log entries needed updating.', 'cf7-artist-submissions') . 
                             '</p></div>';
                    }
                }
            }
        }
        
        // Load the modern template
        include CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    // ============================================================================
    // SETTINGS VALIDATION SECTION
    // ============================================================================
    
    /**
     * Validate general plugin options with comprehensive field validation.
     * Processes form ID, menu label, file storage settings with change logging.
     */
    public function validate_options($input) {
        $valid = array();
        $old_options = get_option('cf7_artist_submissions_options', array());
        
        $valid['form_id'] = isset($input['form_id']) ? sanitize_text_field($input['form_id']) : '';
        $valid['menu_label'] = isset($input['menu_label']) ? sanitize_text_field($input['menu_label']) : 'Submissions';
        $valid['store_files'] = isset($input['store_files']) ? 'yes' : 'no';
        
        // Add AWS S3 configuration fields
        $valid['aws_access_key'] = isset($input['aws_access_key']) ? sanitize_text_field($input['aws_access_key']) : '';
        
        // Debug secret key processing in validation
        if (isset($input['aws_secret_key'])) {
            error_log('CF7 Validation - RAW input aws_secret_key: "' . $input['aws_secret_key'] . '"');
            error_log('CF7 Validation - RAW input length: ' . strlen($input['aws_secret_key']));
            
            $secret_raw = $input['aws_secret_key'];
            
            // Fix common URL encoding issues with + character
            // AWS secret keys can start with + which gets corrupted during URL encoding
            if (strlen($secret_raw) === 41) {
                // Check if it starts with "=+" (equals plus)
                if (substr($secret_raw, 0, 2) === '=+') {
                    $secret_raw = substr($secret_raw, 1); // Remove the leading =
                }
                // Check if it starts with space (space decoded from +)
                elseif (substr($secret_raw, 0, 1) === ' ') {
                    $secret_raw = '+' . substr($secret_raw, 1);
                }
            }
            
            $valid['aws_secret_key'] = sanitize_text_field($secret_raw);
        } else {
            $valid['aws_secret_key'] = '';
        }
        
        $valid['aws_region'] = isset($input['aws_region']) ? sanitize_text_field($input['aws_region']) : 'us-east-1';
        $valid['s3_bucket'] = isset($input['s3_bucket']) ? sanitize_text_field($input['s3_bucket']) : '';
        
        // Add media conversion configuration fields
        $valid['enable_media_conversion'] = isset($input['enable_media_conversion']) ? 'on' : 'off';
        $valid['lambda_function_name'] = isset($input['lambda_function_name']) ? sanitize_text_field($input['lambda_function_name']) : 'cf7as-image-converter';
        $valid['mediaconvert_endpoint'] = isset($input['mediaconvert_endpoint']) ? esc_url_raw($input['mediaconvert_endpoint']) : '';
        
        // PDF Lambda settings
        $valid['enable_pdf_lambda'] = isset($input['enable_pdf_lambda']) ? 'on' : 'off';
        $valid['pdf_lambda_function_arn'] = isset($input['pdf_lambda_function_arn']) ? sanitize_text_field($input['pdf_lambda_function_arn']) : '';
        
        // Add conversion settings checkboxes
        $valid['convert_images'] = isset($input['convert_images']) ? 1 : 0;
        $valid['convert_videos'] = isset($input['convert_videos']) ? 1 : 0;
        $valid['generate_thumbnails'] = isset($input['generate_thumbnails']) ? 1 : 0;
        $valid['create_multiple_sizes'] = isset($input['create_multiple_sizes']) ? 1 : 0;
        
        // Log setting changes
        $this->log_settings_changes($old_options, $valid, 'general');
        
        return $valid;
    }
    

    
    /**
     * Validate comprehensive email configuration options.
     * Processes from email, name, and WooCommerce template settings with logging.
     */
    public function validate_email_options($input) {
        $valid = array();
        $old_options = get_option('cf7_artist_submissions_email_options', array());
        
        // Validate from email
        if (isset($input['from_email'])) {
            $email = sanitize_email($input['from_email']);
            if (is_email($email)) {
                $valid['from_email'] = $email;
            } else {
                add_settings_error('cf7_artist_submissions_email_options', 'invalid_email', 'Please enter a valid email address.');
                $valid['from_email'] = get_option('admin_email');
            }
        }
        
        // Validate from name
        if (isset($input['from_name'])) {
            $valid['from_name'] = sanitize_text_field($input['from_name']);
        }
        
        // Validate WooCommerce template option
        $valid['use_wc_template'] = isset($input['use_wc_template']) ? true : false;
        
        // Log setting changes
        $this->log_settings_changes($old_options, $valid, 'email');
        
        return $valid;
    }

    /**
     * Validate IMAP connection settings with secure credential handling.
     * Processes server, port, encryption, and authentication with audit logging.
     */
    public function validate_imap_options($input) {
        $valid = array();
        $old_options = get_option('cf7_artist_submissions_imap_options', array());
        
        $valid['server'] = isset($input['server']) ? sanitize_text_field($input['server']) : '';
        $valid['port'] = isset($input['port']) ? intval($input['port']) : 993;
        $valid['username'] = isset($input['username']) ? sanitize_text_field($input['username']) : '';
        $valid['password'] = isset($input['password']) ? $input['password'] : ''; // Don't sanitize password
        $valid['encryption'] = isset($input['encryption']) ? sanitize_text_field($input['encryption']) : 'ssl';
        $valid['delete_processed'] = isset($input['delete_processed']) ? true : false;
        
        // Validate port range
        if ($valid['port'] < 1 || $valid['port'] > 65535) {
            $valid['port'] = 993;
        }
        
        // Validate encryption method
        if (!in_array($valid['encryption'], array('ssl', 'tls', 'none'))) {
            $valid['encryption'] = 'ssl';
        }
        
        // Log setting changes (but sanitize password for audit log)
        $audit_old = $old_options;
        $audit_new = $valid;
        if (!empty($audit_old['password'])) {
            $audit_old['password'] = '[REDACTED]';
        }
        if (!empty($audit_new['password'])) {
            $audit_new['password'] = '[REDACTED]';
        }
        $this->log_settings_changes($audit_old, $audit_new, 'imap');
        
        return $valid;
    }

    /**
     * Validate open calls settings with comprehensive data processing.
     * 
     * Validates open call configurations, Contact Form 7 assignments, and
     * automatically creates/updates taxonomy terms based on configuration.
     * Processes titles, descriptions, dates, and form associations with
     * comprehensive error handling and data sanitization.
     * 
     * @since 1.2.0
     */
    public function validate_open_calls_options($input) {
        $valid = array();
        $old_options = get_option('cf7_artist_submissions_open_calls', array());
        
        // Validate auto-create terms option
        $valid['auto_create_terms'] = isset($input['auto_create_terms']) ? true : false;
        
        // Validate calls array
        $valid['calls'] = array();
        
        if (isset($input['calls']) && is_array($input['calls'])) {
            foreach ($input['calls'] as $index => $call) {
                $valid_call = array();
                
                // Validate title (required)
                $valid_call['title'] = isset($call['title']) ? sanitize_text_field($call['title']) : '';
                
                // Skip empty calls
                if (empty($valid_call['title'])) {
                    continue;
                }
                
                // Preserve term_id if it exists (for tracking existing terms)
                $valid_call['term_id'] = isset($call['term_id']) ? intval($call['term_id']) : 0;
                
                // Validate call type
                $allowed_call_types = array('visual_arts', 'text_based');
                $valid_call['call_type'] = isset($call['call_type']) && in_array($call['call_type'], $allowed_call_types) 
                    ? sanitize_text_field($call['call_type']) 
                    : '';
                
                // Validate form ID
                $valid_call['form_id'] = isset($call['form_id']) ? intval($call['form_id']) : 0;
                
                // Validate description
                $valid_call['description'] = isset($call['description']) ? sanitize_textarea_field($call['description']) : '';
                
                // Validate dates
                $valid_call['start_date'] = isset($call['start_date']) ? sanitize_text_field($call['start_date']) : '';
                $valid_call['end_date'] = isset($call['end_date']) ? sanitize_text_field($call['end_date']) : '';
                
                // Validate status
                $allowed_statuses = array('active', 'inactive', 'draft');
                $valid_call['status'] = isset($call['status']) && in_array($call['status'], $allowed_statuses) 
                    ? sanitize_text_field($call['status']) 
                    : 'active';
                
                // Validate dashboard tag
                $valid_call['dashboard_tag'] = isset($call['dashboard_tag']) ? sanitize_text_field($call['dashboard_tag']) : '';
                // Trim and limit to 20 characters
                if (!empty($valid_call['dashboard_tag'])) {
                    $valid_call['dashboard_tag'] = trim(substr($valid_call['dashboard_tag'], 0, 20));
                }
                
                // Validate form exists if specified
                if ($valid_call['form_id'] > 0 && class_exists('WPCF7_ContactForm')) {
                    $form = WPCF7_ContactForm::get_instance($valid_call['form_id']);
                    if (!$form || !$form->id()) {
                        add_settings_error(
                            'cf7_artist_submissions_open_calls',
                            'invalid_form',
                            sprintf(__('Contact Form 7 form #%d not found for open call "%s".', 'cf7-artist-submissions'), 
                                $valid_call['form_id'], $valid_call['title'])
                        );
                        $valid_call['form_id'] = 0;
                    }
                }
                
                $valid['calls'][] = $valid_call;
            }
        }
        
        // Auto-create taxonomy terms if enabled
        if ($valid['auto_create_terms'] && !empty($valid['calls'])) {
            $valid['calls'] = $this->sync_open_call_taxonomy_terms($valid['calls']);
        }
        
        // Log settings changes for audit trail
        $audit_old = array();
        $audit_new = array();
        
        if (!empty($old_options['calls'])) {
            $audit_old['calls_count'] = count($old_options['calls']);
        }
        if (!empty($valid['calls'])) {
            $audit_new['calls_count'] = count($valid['calls']);
            $audit_new['call_titles'] = array_column($valid['calls'], 'title');
        }
        
        $this->log_settings_changes($audit_old, $audit_new, 'open_calls');
        
        return $valid;
    }

    /**
     * Synchronize open call taxonomy terms with settings configuration.
     * 
     * Creates or updates open call taxonomy terms based on configured calls.
     * Maintains consistency between settings and taxonomy structure while
     * preserving existing term relationships and submission associations.
     * Uses term_id tracking to properly update existing terms when titles change.
     * 
     * @since 1.2.0
     * @param array $calls Array of call configurations
     * @return array Updated calls array with term_id populated
     */
    private function sync_open_call_taxonomy_terms($calls) {
        if (!taxonomy_exists('open_call')) {
            return $calls;
        }
        
        foreach ($calls as $index => $call) {
            if (empty($call['title'])) {
                continue;
            }
            
            $term_id = null;
            $existing_term = null;
            
            // First, try to find existing term by term_id if provided
            if (!empty($call['term_id'])) {
                $existing_term = get_term($call['term_id'], 'open_call');
                if (!is_wp_error($existing_term) && $existing_term) {
                    $term_id = $existing_term->term_id;
                }
            }
            
            // If no term found by ID, try to find by name (for backward compatibility)
            if (!$term_id) {
                $existing_term = get_term_by('name', $call['title'], 'open_call');
                if ($existing_term) {
                    $term_id = $existing_term->term_id;
                }
            }
            
            if ($term_id) {
                // Update existing term
                $update_args = array(
                    'name' => $call['title'],
                    'slug' => sanitize_title($call['title']),
                    'description' => $call['description'] ?? ''
                );
                
                $result = wp_update_term($term_id, 'open_call', $update_args);
                
                if (!is_wp_error($result)) {
                    // Update term meta
                    update_term_meta($term_id, 'form_id', $call['form_id']);
                    update_term_meta($term_id, 'start_date', $call['start_date']);
                    update_term_meta($term_id, 'end_date', $call['end_date']);  
                    update_term_meta($term_id, 'status', $call['status']);
                    update_term_meta($term_id, 'call_type', $call['call_type']);
                    
                    // Update the calls array with the term_id for future reference
                    $calls[$index]['term_id'] = $term_id;
                }
            } else {
                // Create new term
                $term_args = array(
                    'slug' => sanitize_title($call['title']),
                    'description' => $call['description'] ?? ''
                );
                
                $result = wp_insert_term($call['title'], 'open_call', $term_args);
                
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                    
                    // Add term meta for additional data
                    add_term_meta($term_id, 'form_id', $call['form_id'], true);
                    add_term_meta($term_id, 'start_date', $call['start_date'], true);
                    add_term_meta($term_id, 'end_date', $call['end_date'], true);
                    add_term_meta($term_id, 'status', $call['status'], true);
                    add_term_meta($term_id, 'call_type', $call['call_type'], true);
                    
                    // Update the calls array with the new term_id
                    $calls[$index]['term_id'] = $term_id;
                }
            }
        }
        
        return $calls;
    }
    
    
    // ============================================================================
    // AJAX TESTING HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler for comprehensive daily summary email testing.
     * 
     * Validates permissions, processes test email requests, and generates sample
     * summary reports with actions data. Provides complete testing workflow for
     * daily notification system with error handling and detailed feedback for
     * administrative configuration validation and system verification.
     * 
     * @since 1.0.0
     */
    public function ajax_test_daily_summary() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        // Get test email from request
        $test_email = sanitize_email($_POST['test_email'] ?? '');
        if (empty($test_email)) {
            wp_send_json_error(array('message' => 'Test email address is required'));
            return;
        }
        
        // Validate email format
        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => 'Invalid email address format'));
            return;
        }
        
        try {
            // Use the new test method that generates sample data
            $result = CF7_Artist_Submissions_Actions::send_test_daily_summary_email($test_email);
            
            if (!empty($result['error'])) {
                wp_send_json_error(array('message' => $result['error']));
                return;
            }
            
            if ($result['success']) {
                $message = '✓ Test daily summary email sent successfully to ' . esc_html($test_email);
                $message .= '<br>Check your email inbox to confirm receipt.';
                $message .= '<br><em>Note: This test email contains sample data, not real actions.</em>';
                
                wp_send_json_success(array(
                    'message' => $message,
                    'details' => $result
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to send test email. Check your email configuration.'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for daily cron schedule management.
     * Sets up automated daily summary email delivery scheduling.
     */
    public function ajax_setup_daily_cron() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            CF7_Artist_Submissions_Actions::setup_daily_summary_cron();
            
            $next_scheduled = wp_next_scheduled('cf7_daily_summary_cron');
            if ($next_scheduled) {
                $next_time = date(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled);
                $message = 'Daily summary cron scheduled successfully. Next run: ' . $next_time;
            } else {
                $message = 'Daily summary cron setup attempted, but schedule not found.';
            }
            
            wp_send_json_success(array('message' => $message));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for clearing daily cron schedules.
     * Removes automated email delivery scheduling from WordPress cron.
     */
    public function ajax_clear_daily_cron() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            CF7_Artist_Submissions_Actions::clear_daily_summary_cron();
            wp_send_json_success(array('message' => 'Daily summary cron cleared successfully.'));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for database schema updates and management.
     * Forces actions table schema updates with assigned_to column addition.
     */
    public function ajax_update_actions_schema() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            // Force schema update
            delete_transient('cf7_actions_schema_checked');
            CF7_Artist_Submissions_Actions::check_and_update_schema();
            
            wp_send_json_success(array('message' => 'Database schema updated successfully. The assigned_to column has been added to the actions table.'));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error updating schema: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for conversation token migration and standardization.
     * Migrates legacy conversation tokens to ensure threading consistency.
     */
    public function ajax_migrate_conversation_tokens() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        global $wpdb;
        
        try {
            // Get submissions without conversation tokens
            $submissions_table = $wpdb->prefix . 'cf7_artist_submissions';
            $conversations_table = $wpdb->prefix . 'cf7_conversations';
            
            $query = "SELECT id, form_id, entry_id 
                     FROM {$submissions_table} 
                     WHERE conversation_token IS NULL OR conversation_token = ''";
            
            $submissions = $wpdb->get_results($query);
            $migrated_count = 0;
            
            foreach ($submissions as $submission) {
                // Generate unique conversation token
                $token = wp_generate_password(32, false);
                
                // Update submission with token
                $updated = $wpdb->update(
                    $submissions_table,
                    array('conversation_token' => $token),
                    array('id' => $submission->id),
                    array('%s'),
                    array('%d')
                );
                
                if ($updated) {
                    // Create conversation record if it doesn't exist
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$conversations_table} WHERE submission_id = %d",
                        $submission->id
                    ));
                    
                    if (!$existing) {
                        $wpdb->insert(
                            $conversations_table,
                            array(
                                'submission_id' => $submission->id,
                                'token' => $token,
                                'status' => 'active',
                                'created_at' => current_time('mysql')
                            ),
                            array('%d', '%s', '%s', '%s')
                        );
                    }
                    
                    $migrated_count++;
                }
            }
            
            wp_send_json_success(array('message' => "Successfully migrated {$migrated_count} conversation tokens."));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error migrating tokens: ' . $e->getMessage()));
        }
    }
    
    // ============================================================================
    // CONFIGURATION TESTING SECTION
    // ============================================================================
    
    /**
     * AJAX handler for comprehensive Contact Form 7 configuration testing.
     * 
     * Validates complete plugin setup including form selection, field validation,
     * database schema verification, and file storage configuration. Provides
     * detailed diagnostic information with field analysis, database status,
     * and comprehensive troubleshooting feedback for optimal system configuration.
     * 
     * @since 1.0.0
     */
    public function ajax_test_form_config() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        try {
            $options = get_option('cf7_artist_submissions_options', array());
            $issues = array();
            
            // Check if Contact Form 7 is active
            if (!class_exists('WPCF7_ContactForm')) {
                $issues[] = 'Contact Form 7 plugin is not active';
            } else {
                // Check if form is selected
                if (empty($options['form_id'])) {
                    $issues[] = 'No Contact Form 7 form has been selected';
                } else {
                    // Verify the selected form exists
                    $form = \WPCF7_ContactForm::get_instance($options['form_id']);
                    if (!$form) {
                        $issues[] = 'Selected form (ID: ' . $options['form_id'] . ') no longer exists';
                    } else {
                        // Check form fields
                        $form_fields = $form->scan_form_tags();
                        $has_file_field = false;
                        $has_email_field = false;
                        $has_name_field = false;
                        $field_details = array();
                        
                        foreach ($form_fields as $field) {
                            // Store field details for debugging
                            $field_details[] = array(
                                'type' => $field->type,
                                'name' => $field->name,
                                'required' => $field->is_required()
                            );
                            
                            // Check for file upload fields (both single and multiple file uploads)
                            if ($field->type === 'file' || $field->type === 'mfile') {
                                $has_file_field = true;
                            }
                            
                            // Check for email fields - be more flexible
                            if ($field->type === 'email' || strpos(strtolower($field->name), 'email') !== false) {
                                $has_email_field = true;
                            }
                            
                            // Check for name fields - be more flexible
                            if (in_array($field->type, array('text', 'textarea')) && 
                                (strpos(strtolower($field->name), 'name') !== false || 
                                 strpos(strtolower($field->name), 'artist') !== false ||
                                 $field->name === 'your-name' || // Common CF7 default
                                 strpos(strtolower($field->name), 'author') !== false)) {
                                $has_name_field = true;
                            }
                        }
                        
                        // Additional fallback checks by examining form content directly
                        $form_content = $form->prop('form');
                        if (!$has_email_field && (strpos($form_content, 'type="email"') !== false || strpos($form_content, '[email') !== false)) {
                            $has_email_field = true;
                        }
                        if (!$has_file_field && (strpos($form_content, 'type="file"') !== false || strpos($form_content, '[file') !== false || strpos($form_content, '[mfile') !== false)) {
                            $has_file_field = true;
                        }
                        if (!$has_name_field && (strpos($form_content, 'name') !== false || strpos($form_content, 'artist') !== false)) {
                            $has_name_field = true;
                        }
                        
                        // Only flag as issues if fields are truly missing
                        if (!$has_email_field) {
                            $issues[] = 'Form should have an email field for artist contact';
                        }
                        if (!$has_name_field) {
                            $issues[] = 'Form should have a name or artist field for identification';
                        }
                        if (!$has_file_field) {
                            $issues[] = 'Form should have a file upload field for artwork submissions';
                        }
                    }
                }
            }
            
            // Check database tables
            global $wpdb;
            $submissions_table = $wpdb->prefix . 'cf7_artist_submissions';
            $conversations_table = $wpdb->prefix . 'cf7_conversations';
            
            // First check if we're using the custom post type instead of a custom table
            $post_type_exists = post_type_exists('cf7_submission');
            
            // Check if submissions table exists
            $submissions_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $submissions_table));
            if (!$submissions_exists && !$post_type_exists) {
                $issues[] = 'Neither submissions database table nor custom post type found';
            } elseif (!$submissions_exists && $post_type_exists) {
                // This is actually fine - we're using the custom post type
                $submissions_exists = true; // Mark as exists for status display
            }
            
            // Check if conversations table exists  
            $conversations_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $conversations_table));
            if (!$conversations_exists) {
                $issues[] = 'Conversations database table is missing: ' . $conversations_table;
            }
            
            // Check file storage directory
            if (($options['store_files'] ?? false) === 'yes') {
                $upload_dir = wp_upload_dir();
                $cf7_dir = $upload_dir['basedir'] . '/cf7-submissions';
                
                if (!file_exists($cf7_dir)) {
                    $issues[] = 'File storage directory does not exist: ' . $cf7_dir;
                } elseif (!is_writable($cf7_dir)) {
                    $issues[] = 'File storage directory is not writable: ' . $cf7_dir;
                }
            }
            
            if (empty($issues)) {
                $message = '✓ Configuration test passed!';
                $message .= '<br><br><strong>Configuration Details:</strong>';
                $message .= '<br>Form ID: #' . esc_html($options['form_id']);
                $message .= '<br>Menu Label: ' . esc_html($options['menu_label'] ?? 'Artist Submissions');
                $message .= '<br>File Storage: ' . (($options['store_files'] ?? false) === 'yes' ? 'Enabled' : 'Disabled');
                $message .= '<br>Database Tables: ✓ Present';
                
                if (isset($form) && isset($form_fields)) {
                    $message .= '<br><br><strong>Form Analysis:</strong>';
                    $message .= '<br>Form Title: ' . esc_html($form->title());
                    $message .= '<br>Total Fields: ' . count($form_fields);
                    $message .= '<br>Email Field: ' . ($has_email_field ? '✓ Found' : '✗ Missing');
                    $message .= '<br>Name/Artist Field: ' . ($has_name_field ? '✓ Found' : '✗ Missing');
                    $message .= '<br>File Upload Field: ' . ($has_file_field ? '✓ Found' : '✗ Missing');
                    
                    // Show field details for debugging
                    if (isset($field_details) && !empty($field_details)) {
                        $message .= '<br><br><strong>Field Details:</strong>';
                        foreach ($field_details as $field_detail) {
                            $message .= '<br>• ' . esc_html($field_detail['type']) . ' field: "' . esc_html($field_detail['name']) . '"';
                            if ($field_detail['required']) {
                                $message .= ' (required)';
                            }
                        }
                    }
                    
                    // Show database table status
                    if (isset($submissions_exists) && isset($conversations_exists)) {
                        $message .= '<br><br><strong>Database Status:</strong>';
                        if ($post_type_exists) {
                            $message .= '<br>Submissions Storage: ✓ Custom Post Type (cf7_submission)';
                        } else {
                            $message .= '<br>Submissions Table: ' . ($submissions_exists ? '✓ Present' : '✗ Missing');
                        }
                        $message .= '<br>Conversations Table: ' . ($conversations_exists ? '✓ Present' : '✗ Missing');
                    }
                }
                
                wp_send_json_success(array('message' => $message));
            } else {
                $message = '✗ Configuration issues found:';
                foreach ($issues as $issue) {
                    $message .= '<br>• ' . esc_html($issue);
                }
                
                // Add debugging information even when there are issues
                if (isset($form) && isset($form_fields)) {
                    $message .= '<br><br><strong>Debug Information:</strong>';
                    $message .= '<br>Form ID: #' . esc_html($options['form_id']);
                    $message .= '<br>Form Title: ' . esc_html($form->title());
                    $message .= '<br>Total Fields Found: ' . count($form_fields);
                    
                    if (isset($field_details) && !empty($field_details)) {
                        $message .= '<br><br><strong>Detected Fields:</strong>';
                        foreach ($field_details as $field_detail) {
                            $message .= '<br>• ' . esc_html($field_detail['type']) . ' field: "' . esc_html($field_detail['name']) . '"';
                            if ($field_detail['required']) {
                                $message .= ' (required)';
                            }
                        }
                    }
                    
                    // Show field detection results
                    $message .= '<br><br><strong>Field Detection Results:</strong>';
                    $message .= '<br>Email Field Detected: ' . ($has_email_field ? 'Yes' : 'No');
                    $message .= '<br>Name Field Detected: ' . ($has_name_field ? 'Yes' : 'No');  
                    $message .= '<br>File Field Detected: ' . ($has_file_field ? 'Yes' : 'No');
                    
                    // Show database table status
                    if (isset($submissions_exists) || isset($conversations_exists)) {
                        $message .= '<br><br><strong>Database Status:</strong>';
                        if (isset($post_type_exists) && $post_type_exists) {
                            $message .= '<br>Submissions Storage: Custom Post Type (cf7_submission) - Present';
                        } elseif (isset($submissions_exists)) {
                            $message .= '<br>Submissions Table: ' . ($submissions_exists ? 'Present' : 'Missing');
                        }
                        if (isset($conversations_exists)) {
                            $message .= '<br>Conversations Table: ' . ($conversations_exists ? 'Present' : 'Missing');
                        }
                    }
                }
                
                wp_send_json_error(array('message' => $message));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error testing configuration: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for email configuration validation and diagnostics.
     * Validates email setup with SMTP detection and configuration analysis.
     */
    public function ajax_validate_email_config() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            $validation = CF7_Artist_Submissions_Actions::validate_email_config();
            $smtp_info = CF7_Artist_Submissions_Actions::get_smtp_config_info();
            
            $response = array(
                'validation' => $validation,
                'smtp_info' => $smtp_info
            );
            
            if ($validation['valid']) {
                $message = '✓ Email configuration is valid';
                $message .= '<br>From: ' . esc_html($validation['from_name']) . ' &lt;' . esc_html($validation['from_email']) . '&gt;';
                $message .= '<br>Site: ' . esc_html($validation['site_host']);
                $message .= '<br><br><strong>SMTP Configuration:</strong>';
                $message .= '<br>SMTP Configured: ' . ($smtp_info['smtp_configured'] ? 'Yes' : 'No');
                $message .= '<br>Mailer Type: ' . esc_html($smtp_info['mailer_type']);
                if (!empty($smtp_info['plugins_detected'])) {
                    $message .= '<br>Plugins Detected: ' . esc_html(implode(', ', $smtp_info['plugins_detected']));
                }
                wp_send_json_success(array('message' => $message, 'details' => $response));
            } else {
                $message = '✗ Email configuration has issues:';
                foreach ($validation['issues'] as $issue) {
                    $message .= '<br>• ' . esc_html($issue);
                }
                wp_send_json_error(array('message' => $message, 'details' => $response));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error validating email config: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for SMTP configuration testing with live email delivery.
     * Tests email delivery system with diagnostic information and feedback.
     */
    public function ajax_test_smtp_config() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            // Get test email from request or use admin email
            $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : get_option('admin_email');
            
            if (empty($test_email) || !is_email($test_email)) {
                wp_send_json_error(array('message' => 'Invalid test email address'));
                return;
            }
            
            $result = CF7_Artist_Submissions_Actions::test_smtp_configuration($test_email);
            
            if ($result['success']) {
                $message = '✓ SMTP test email sent successfully to ' . esc_html($test_email);
                $message .= '<br><br><strong>Configuration Details:</strong>';
                $message .= '<br>SMTP Configured: ' . ($result['smtp_info']['smtp_configured'] ? 'Yes' : 'No');
                $message .= '<br>Mailer Type: ' . esc_html($result['smtp_info']['mailer_type']);
                if (!empty($result['smtp_info']['plugins_detected'])) {
                    $message .= '<br>Plugins Detected: ' . esc_html(implode(', ', $result['smtp_info']['plugins_detected']));
                }
                $message .= '<br><br>Check your email inbox to confirm receipt.';
                wp_send_json_success(array('message' => $message, 'details' => $result));
            } else {
                $message = '✗ SMTP test failed';
                if (isset($result['error'])) {
                    $message .= '<br>Error: ' . esc_html($result['error']);
                }
                wp_send_json_error(array('message' => $message, 'details' => $result));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error testing SMTP: ' . $e->getMessage()));
        }
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS SECTION
    // ============================================================================
    
    /**
     * Log configuration changes to audit trail system.
     * Compares old and new settings with comprehensive change tracking.
     */
    private function log_settings_changes($old_values, $new_values, $tab = '') {
        if (!class_exists('CF7_Artist_Submissions_Action_Log')) {
            return;
        }
        
        // Compare old and new values
        foreach ($new_values as $key => $new_value) {
            $old_value = isset($old_values[$key]) ? $old_values[$key] : '';
            
            // Only log if the value actually changed
            if ($old_value != $new_value) {
                CF7_Artist_Submissions_Action_Log::log_setting_changed(
                    $key,
                    $old_value,
                    $new_value,
                    $tab
                );
            }
        }
        
        // Check for removed settings
        foreach ($old_values as $key => $old_value) {
            if (!isset($new_values[$key])) {
                CF7_Artist_Submissions_Action_Log::log_setting_changed(
                    $key,
                    $old_value,
                    '',
                    $tab
                );
            }
        }
    }
    
    /**
     * AJAX handler for IMAP connection testing and validation.
     * Tests server connectivity with detailed diagnostic information.
     */
    public function ajax_test_imap_connection() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce') && !wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if IMAP extension is available first
        if (!extension_loaded('imap')) {
            wp_send_json_error(array('message' => 'PHP IMAP extension is not installed on this server. Please install php-imap to use IMAP functionality.'));
            return;
        }
        
        // Get IMAP settings
        $imap_options = get_option('cf7_artist_submissions_imap_options', array());
        
        // Debug: Show what settings we have
        if (empty($imap_options)) {
            wp_send_json_error(array('message' => 'No IMAP settings found. Please save your IMAP configuration first.'));
            return;
        }
        
        // Validate required settings with specific error messages
        $missing_fields = array();
        if (empty($imap_options['server'])) {
            $missing_fields[] = 'IMAP Server';
        }
        if (empty($imap_options['username'])) {
            $missing_fields[] = 'Username';
        }
        if (empty($imap_options['password'])) {
            $missing_fields[] = 'Password';
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error(array('message' => 'IMAP settings are incomplete. Missing: ' . implode(', ', $missing_fields) . '. Please configure all required fields and save.'));
            return;
        }
        
        try {
            // Build connection string
            $server = $imap_options['server'];
            $port = isset($imap_options['port']) ? intval($imap_options['port']) : 993;
            $encryption = isset($imap_options['encryption']) ? $imap_options['encryption'] : 'ssl';
            
            $connection_string = '{' . $server . ':' . $port;
            if ($encryption === 'ssl') {
                $connection_string .= '/ssl';
            } elseif ($encryption === 'tls') {
                $connection_string .= '/tls';
            }
            $connection_string .= '}INBOX';
            
            // Clear any previous IMAP errors
            imap_errors();
            imap_alerts();
            
            // Attempt connection with timeout
            $connection = @imap_open(
                $connection_string,
                $imap_options['username'],
                $imap_options['password'],
                OP_READONLY
            );
            
            if ($connection === false) {
                // Get all IMAP errors for better debugging
                $errors = imap_errors();
                $alerts = imap_alerts();
                
                $error_message = 'IMAP connection failed';
                if (!empty($errors)) {
                    $error_message .= ': ' . implode(', ', $errors);
                } elseif (!empty($alerts)) {
                    $error_message .= ': ' . implode(', ', $alerts);
                } else {
                    $error_message .= ': Unknown error';
                }
                
                $error_message .= '<br><br><strong>Connection Details:</strong>';
                $error_message .= '<br>Server: ' . esc_html($connection_string);
                $error_message .= '<br>Username: ' . esc_html($imap_options['username']);
                
                wp_send_json_error(array('message' => $error_message));
                return;
            }
            
            // Get mailbox info
            $status = imap_status($connection, $connection_string, SA_ALL);
            $message_count = $status ? $status->messages : 0;
            
            // Close connection
            imap_close($connection);
            
            $message = '✓ IMAP connection successful';
            $message .= '<br>Server: ' . esc_html($server . ':' . $port);
            $message .= '<br>Encryption: ' . esc_html(strtoupper($encryption));
            $message .= '<br>Messages in inbox: ' . $message_count;
            
            wp_send_json_success(array(
                'message' => $message,
                'details' => array(
                    'server' => $server,
                    'port' => $port,
                    'encryption' => $encryption,
                    'message_count' => $message_count
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error testing IMAP: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for IMAP inbox cleanup and maintenance.
     * Removes old and deleted messages from email server.
     */
    public function ajax_cleanup_inbox() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce') && !wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get IMAP settings
        $imap_options = get_option('cf7_artist_submissions_imap_options', array());
        
        // Validate required settings
        if (empty($imap_options['server']) || empty($imap_options['username']) || empty($imap_options['password'])) {
            wp_send_json_error(array('message' => 'IMAP settings are incomplete. Please configure server, username, and password.'));
            return;
        }
        
        // Check if IMAP extension is available
        if (!extension_loaded('imap')) {
            wp_send_json_error(array('message' => 'PHP IMAP extension is not installed on this server.'));
            return;
        }
        
        try {
            // Build connection string
            $server = $imap_options['server'];
            $port = isset($imap_options['port']) ? intval($imap_options['port']) : 993;
            $encryption = isset($imap_options['encryption']) ? $imap_options['encryption'] : 'ssl';
            
            $connection_string = '{' . $server . ':' . $port;
            if ($encryption === 'ssl') {
                $connection_string .= '/ssl';
            } elseif ($encryption === 'tls') {
                $connection_string .= '/tls';
            }
            $connection_string .= '}INBOX';
            
            // Attempt connection
            $connection = @imap_open(
                $connection_string,
                $imap_options['username'],
                $imap_options['password']
            );
            
            if ($connection === false) {
                $error = imap_last_error();
                wp_send_json_error(array(
                    'message' => 'IMAP connection failed: ' . ($error ? $error : 'Unknown error')
                ));
                return;
            }
            
            // Get messages marked for deletion or older messages
            $messages = imap_search($connection, 'DELETED', SE_UID) ?: array();
            $old_messages = imap_search($connection, 'BEFORE "' . date('d-M-Y', strtotime('-30 days')) . '"', SE_UID) ?: array();
            
            $all_cleanup_messages = array_unique(array_merge($messages, $old_messages));
            $deleted_count = 0;
            
            if (!empty($all_cleanup_messages)) {
                foreach ($all_cleanup_messages as $uid) {
                    if (imap_delete($connection, $uid, FT_UID)) {
                        $deleted_count++;
                    }
                }
                
                // Expunge to permanently delete
                imap_expunge($connection);
            }
            
            // Close connection
            imap_close($connection);
            
            $message = '✓ Inbox cleanup completed';
            $message .= '<br>Processed ' . count($all_cleanup_messages) . ' messages';
            $message .= '<br>Successfully deleted ' . $deleted_count . ' messages';
            
            wp_send_json_success(array(
                'message' => $message,
                'details' => array(
                    'total_processed' => count($all_cleanup_messages),
                    'deleted_count' => $deleted_count
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error during cleanup: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for email template testing with sample data.
     * Sends test emails using configured templates and placeholders.
     */
    public function ajax_test_template_email() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get template ID and test email
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : get_option('admin_email');
        
        if (empty($template_id)) {
            wp_send_json_error(array('message' => 'Template ID is required'));
            return;
        }
        
        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => 'Valid test email is required'));
            return;
        }
        
        try {
            // Get email templates
            $templates = get_option('cf7_artist_submissions_email_templates', array());
            
            if (!isset($templates[$template_id]) || !$templates[$template_id]['enabled']) {
                wp_send_json_error(array('message' => 'Template not found or not enabled'));
                return;
            }
            
            $template = $templates[$template_id];
            
            // Get email options for from address
            $email_options = get_option('cf7_artist_submissions_email_options', array());
            $from_email = isset($email_options['from_email']) ? $email_options['from_email'] : get_option('admin_email');
            $from_name = isset($email_options['from_name']) ? $email_options['from_name'] : get_bloginfo('name');
            
            // Replace placeholders with test data
            $subject = str_replace(
                array('{artist_name}', '{site_name}', '{submission_id}'),
                array('Test Artist', get_bloginfo('name'), '123'),
                $template['subject']
            );
            
            $body = str_replace(
                array('{artist_name}', '{site_name}', '{submission_id}', '{artist_email}'),
                array('Test Artist', get_bloginfo('name'), '123', $test_email),
                $template['body']
            );
            
            // Send test email
            $headers = array();
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            
            $sent = wp_mail($test_email, $subject, $body, $headers);
            
            if ($sent) {
                $message = '✓ Test email sent successfully to ' . esc_html($test_email);
                $message .= '<br>Template: ' . esc_html($template_id);
                $message .= '<br>Subject: ' . esc_html($subject);
                wp_send_json_success(array(
                    'message' => $message, 
                    'details' => array(
                        'template_id' => $template_id,
                        'subject' => $subject,
                        'to' => $test_email
                    )
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to send test email. Check your email configuration.'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error sending test email: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for email template preview generation.
     * Creates formatted preview with sample data and WooCommerce styling.
     */
    public function ajax_preview_template_email() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get template ID
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        
        if (empty($template_id)) {
            wp_send_json_error(array('message' => 'Template ID is required'));
            return;
        }
        
        try {
            // Get email templates
            $templates = get_option('cf7_artist_submissions_email_templates', array());
            
            if (!isset($templates[$template_id])) {
                wp_send_json_error(array('message' => 'Template not found'));
                return;
            }

            $template = $templates[$template_id];
            
            // Use sample data for preview
            $sample_data = array(
                'artist_name' => 'Jane Smith',
                'artist_email' => 'jane.smith@example.com',
                'submission_title' => 'Sample Artwork Submission',
                'submission_id' => '123',
                'site_name' => get_bloginfo('name'),
                'site_url' => get_site_url(),
                'status' => 'Selected'
            );
            
            // Process merge tags with sample data
            $subject = $this->process_merge_tags_with_data($template['subject'], $sample_data);
            $body = $this->process_merge_tags_with_data($template['body'], $sample_data);
            
            // Check if WooCommerce template should be used
            $email_options = get_option('cf7_artist_submissions_email_options', array());
            $use_wc_template = isset($email_options['use_wc_template']) ? $email_options['use_wc_template'] : false;
            
            if ($use_wc_template && class_exists('WooCommerce')) {
                // Apply WooCommerce email template styling
                $body = $this->apply_woocommerce_template($body, $subject);
            } else {
                // Convert line breaks to HTML for regular templates
                $body = wpautop($body);
            }
            
            wp_send_json_success(array(
                'subject' => $subject,
                'body' => $body,
                'template_name' => $this->get_template_name($template_id),
                'uses_woocommerce' => $use_wc_template && class_exists('WooCommerce')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error generating preview: ' . $e->getMessage()));
        }
    }
    
    /**
     * Apply WooCommerce email template styling to content.
     * Wraps content with WooCommerce email header and footer templates.
     */
    private function apply_woocommerce_template($content, $subject) {
        if (!class_exists('WooCommerce')) {
            return wpautop($content);
        }
        
        try {
            // Make sure WooCommerce is properly initialized
            require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
            require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
            
            $wc_emails = new WC_Emails();
            $wc_emails->init();
            
            // Create a generic WC_Email object to access template methods
            $email = new WC_Email();
            
            // Get the email template content
            ob_start();
            
            // Include the WooCommerce email header
            wc_get_template('emails/email-header.php', array('email_heading' => $subject));
            
            // Add our content with proper formatting
            echo wpautop($content);
            
            // Include the WooCommerce email footer
            wc_get_template('emails/email-footer.php');
            
            // Get the complete email template
            $formatted_email = ob_get_clean();
            
            // Apply WooCommerce inline styles
            $formatted_email = $email->style_inline($formatted_email);
            
            return $formatted_email;
        } catch (Exception $e) {
            // If WooCommerce template fails, fall back to plain text
            error_log('CF7 Template Preview: WooCommerce template error: ' . $e->getMessage());
            return wpautop($content);
        }
    }
    
    /**
     * Process merge tags with provided sample data for previews.
     * Replaces placeholders with supplied data values for testing.
     */
    private function process_merge_tags_with_data($content, $data) {
        $merge_tags = array(
            '{artist_name}' => $data['artist_name'],
            '{artist_email}' => $data['artist_email'],
            '{submission_title}' => $data['submission_title'],
            '{submission_id}' => $data['submission_id'],
            '{site_name}' => $data['site_name'],
            '{site_url}' => $data['site_url'],
            '{status}' => $data['status']
        );
        
        return str_replace(array_keys($merge_tags), array_values($merge_tags), $content);
    }
    
    /**
     * Get human-readable template names for display purposes.
     * Provides friendly names for template identification in interfaces.
     */
    private function get_template_name($template_id) {
        $names = array(
            'submission_received' => __('Submission Received', 'cf7-artist-submissions'),
            'status_changed_to_selected' => __('Status Changed to Selected', 'cf7-artist-submissions'),
            'status_changed_to_reviewed' => __('Status Changed to Reviewed', 'cf7-artist-submissions'),
            'status_changed_to_shortlisted' => __('Status Changed to Shortlisted', 'cf7-artist-submissions'),
            'custom_notification' => __('Custom Notification', 'cf7-artist-submissions')
        );
        
        return isset($names[$template_id]) ? $names[$template_id] : ucwords(str_replace('_', ' ', $template_id));
    }
    
    /**
     * AJAX handler for missing artist information updates in audit logs.
     * Updates audit log entries with artist data from submissions.
     */
    public function ajax_update_missing_artist_info() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'cf7-artist-submissions')));
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'cf7-artist-submissions')));
            return;
        }
        
        // Update missing artist info
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            $updated_count = CF7_Artist_Submissions_Action_Log::update_missing_artist_info();
            
            if ($updated_count > 0) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully updated %d audit log entries with artist information.', 'cf7-artist-submissions'), $updated_count),
                    'updated_count' => $updated_count
                ));
            } else {
                wp_send_json_success(array(
                    'message' => __('No audit log entries needed updating.', 'cf7-artist-submissions'),
                    'updated_count' => 0
                ));
            }
        } else {
            wp_send_json_error(array('message' => __('Action Log class not available.', 'cf7-artist-submissions')));
        }
    }
    
    // ============================================================================
    // UPDATE MANAGEMENT AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler for checking plugin updates
     * 
     * Triggers a manual update check and returns current status information
     * including available updates and version comparison data.
     * 
     * @since 1.0.0
     */
    public function ajax_check_updates() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'cf7-artist-submissions')));
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'cf7-artist-submissions')));
            return;
        }
        
        global $cf7_artist_submissions_updater;
        
        if (!$cf7_artist_submissions_updater) {
            wp_send_json_error(array('message' => __('Update system not available.', 'cf7-artist-submissions')));
            return;
        }
        
        try {
            // Force update check
            $cf7_artist_submissions_updater->force_update_check();
            
            // Get current and remote versions
            $current_version = $cf7_artist_submissions_updater->get_version();
            $remote_version = $this->get_remote_version_info();
            
            $has_update = version_compare($current_version, $remote_version['version'], '<');
            
            wp_send_json_success(array(
                'message' => __('Update check completed successfully.', 'cf7-artist-submissions'),
                'current_version' => $current_version,
                'remote_version' => $remote_version['version'],
                'has_update' => $has_update,
                'update_url' => $has_update ? $this->get_update_url() : null,
                'repository_info' => $cf7_artist_submissions_updater->get_repository_info()
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Update check failed: %s', 'cf7-artist-submissions'), $e->getMessage())
            ));
        }
    }
    
    /**
     * AJAX handler for forcing an update check
     * 
     * Clears update cache and forces an immediate check for new versions
     * from the GitHub repository.
     * 
     * @since 1.0.0
     */
    public function ajax_force_update_check() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'cf7-artist-submissions')));
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'cf7-artist-submissions')));
            return;
        }
        
        global $cf7_artist_submissions_updater;
        
        if (!$cf7_artist_submissions_updater) {
            wp_send_json_error(array('message' => __('Update system not available.', 'cf7-artist-submissions')));
            return;
        }
        
        try {
            // Clear cache and force immediate check
            delete_transient('cf7_artist_submissions_update_check');
            delete_site_transient('update_plugins');
            
            $cf7_artist_submissions_updater->force_update_check();
            
            wp_send_json_success(array(
                'message' => __('Update cache cleared and fresh check completed.', 'cf7-artist-submissions'),
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Force update check failed: %s', 'cf7-artist-submissions'), $e->getMessage())
            ));
        }
    }
    
    /**
     * Get remote version information from GitHub
     * 
     * Retrieves the latest version information from the GitHub repository
     * for comparison with the current installed version.
     * 
     * @since 1.0.0
     * 
     * @return array Version information
     */
    private function get_remote_version_info() {
        $response = wp_remote_get('https://api.github.com/repos/xapher19/cf7-artist-submissions/releases/latest', array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'CF7-Artist-Submissions-Settings'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array(
                'version' => CF7_ARTIST_SUBMISSIONS_VERSION,
                'error' => 'Unable to fetch remote version information'
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['tag_name'])) {
            return array(
                'version' => CF7_ARTIST_SUBMISSIONS_VERSION,
                'error' => 'Invalid response from GitHub API'
            );
        }
        
        return array(
            'version' => ltrim($data['tag_name'], 'v'),
            'release_notes' => isset($data['body']) ? $data['body'] : '',
            'release_date' => isset($data['published_at']) ? $data['published_at'] : '',
            'download_url' => isset($data['zipball_url']) ? $data['zipball_url'] : ''
        );
    }
    
    /**
     * Get WordPress update URL for the plugin
     * 
     * Generates the proper WordPress update URL with nonce for secure updates.
     * 
     * @since 1.0.0
     * 
     * @return string Update URL
     */
    private function get_update_url() {
        $plugin_basename = plugin_basename(CF7_ARTIST_SUBMISSIONS_PLUGIN_FILE);
        
        return wp_nonce_url(
            self_admin_url('update.php?action=upgrade-plugin&plugin=' . $plugin_basename),
            'upgrade-plugin_' . $plugin_basename
        );
    }
    
    /**
     * AJAX handler for saving plugin settings
     * Saves all plugin configuration options
     */
    public function ajax_save_settings() {
        // Debug: Log that the function was called
        error_log('CF7 Settings Save - AJAX handler called - METHOD EXISTS AND REACHED');
        error_log('CF7 Settings Save - REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
        error_log('CF7 Settings Save - DOING_AJAX defined: ' . (defined('DOING_AJAX') ? 'YES' : 'NO'));
        error_log('CF7 Settings Save - DOING_AJAX value: ' . (defined('DOING_AJAX') && DOING_AJAX ? 'TRUE' : 'FALSE'));
        
        // Basic error checking with debugging
        if (!isset($_POST) || empty($_POST)) {
            error_log('CF7 Settings Save - No POST data received');
            wp_send_json_error(array('message' => 'No POST data received'));
            return;
        }
        
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array('message' => 'No nonce provided'));
            return;
        }
        
        // Verify nonce - prioritize cf7_admin_nonce since that's what we're sending
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'cf7_artist_submissions_settings')) {
            error_log('CF7 Settings Save - Nonce verification failed. Expected: cf7_admin_nonce or cf7_artist_submissions_settings, Got: ' . $_POST['nonce']);
            wp_send_json_error(array('message' => 'Security check failed - nonce mismatch'));
            return;
        }
        
        // Debug: Log received data
        error_log('CF7 Settings Save - POST data keys: ' . implode(', ', array_keys($_POST)));
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        try {
            // Process each settings section
            $settings = array();
            
            // General settings
            if (isset($_POST['form_id'])) {
                $settings['form_id'] = intval($_POST['form_id']);
            }
            if (isset($_POST['menu_label'])) {
                $settings['menu_label'] = sanitize_text_field($_POST['menu_label']);
            }
            if (isset($_POST['submissions_per_page'])) {
                $settings['submissions_per_page'] = intval($_POST['submissions_per_page']);
            }
            if (isset($_POST['enable_file_uploads'])) {
                $settings['enable_file_uploads'] = (bool) $_POST['enable_file_uploads'];
            }
            if (isset($_POST['max_file_size'])) {
                $settings['max_file_size'] = intval($_POST['max_file_size']);
            }
            if (isset($_POST['allowed_file_types'])) {
                $settings['allowed_file_types'] = sanitize_text_field($_POST['allowed_file_types']);
            }
            if (isset($_POST['store_files'])) {
                $settings['store_files'] = sanitize_text_field($_POST['store_files']);
            }
            
            // Email settings
            if (isset($_POST['enable_email_notifications'])) {
                $settings['enable_email_notifications'] = (bool) $_POST['enable_email_notifications'];
            }
            if (isset($_POST['admin_email'])) {
                $settings['admin_email'] = sanitize_email($_POST['admin_email']);
            }
            if (isset($_POST['from_email'])) {
                $settings['from_email'] = sanitize_email($_POST['from_email']);
            }
            if (isset($_POST['from_name'])) {
                $settings['from_name'] = sanitize_text_field($_POST['from_name']);
            }
            
            // SMTP settings
            if (isset($_POST['smtp_host'])) {
                $settings['smtp_host'] = sanitize_text_field($_POST['smtp_host']);
            }
            if (isset($_POST['smtp_port'])) {
                $settings['smtp_port'] = intval($_POST['smtp_port']);
            }
            if (isset($_POST['smtp_username'])) {
                $settings['smtp_username'] = sanitize_text_field($_POST['smtp_username']);
            }
            if (isset($_POST['smtp_password'])) {
                $settings['smtp_password'] = sanitize_text_field($_POST['smtp_password']);
            }
            if (isset($_POST['smtp_encryption'])) {
                $settings['smtp_encryption'] = sanitize_text_field($_POST['smtp_encryption']);
            }
            
            // S3 settings
            if (isset($_POST['aws_access_key'])) {
                $settings['aws_access_key'] = sanitize_text_field($_POST['aws_access_key']);
            }
            if (isset($_POST['aws_secret_key'])) {
                $secret_raw = $_POST['aws_secret_key'];
                
                // Fix common URL encoding issues with + character
                if (strlen($secret_raw) === 41) {
                    // Check if it starts with "=+" (equals plus)
                    if (substr($secret_raw, 0, 2) === '=+') {
                        $secret_raw = substr($secret_raw, 1); // Remove the leading =
                    }
                    // Check if it starts with space (space decoded from +)
                    elseif (substr($secret_raw, 0, 1) === ' ') {
                        $secret_raw = '+' . substr($secret_raw, 1);
                    }
                }
                
                $settings['aws_secret_key'] = sanitize_text_field($secret_raw);
            }
            if (isset($_POST['aws_region'])) {
                $settings['aws_region'] = sanitize_text_field($_POST['aws_region']);
            }
            if (isset($_POST['s3_bucket'])) {
                $settings['s3_bucket'] = sanitize_text_field($_POST['s3_bucket']);
            }
            
            if (empty($settings)) {
                wp_send_json_error(array(
                    'message' => 'No valid settings found to save. Please check the form data.'
                ));
                return;
            }
            
            // Get existing options array
            $existing_options = get_option('cf7_artist_submissions_options', array());
            
            // Update the existing options array with new values
            foreach ($settings as $key => $value) {
                $existing_options[$key] = $value;
            }
            
            // Debug: Log what we're trying to save
            error_log('CF7 Settings Save - Attempting to save: ' . json_encode($settings));
            error_log('CF7 Settings Save - Final options array: ' . json_encode($existing_options));
            
            // Save the entire options array
            $result = update_option('cf7_artist_submissions_options', $existing_options);
            
            // Debug: Log the result
            error_log('CF7 Settings Save - update_option result: ' . ($result ? 'true' : 'false'));
            
            if ($result === false) {
                // Check if the option already exists with the same values
                $current_options = get_option('cf7_artist_submissions_options', array());
                if ($current_options === $existing_options) {
                    // Values are the same, so no update was needed
                    wp_send_json_success(array(
                        'message' => 'Settings are already up to date (no changes detected).',
                        'saved_count' => count($settings)
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => 'Failed to save settings to database'
                    ));
                }
                return;
            }

            wp_send_json_success(array(
                'message' => 'Settings saved successfully! Updated ' . count($settings) . ' settings.',
                'saved_count' => count($settings)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for testing S3 connection
     * Tests AWS credentials and S3 bucket access using WordPress HTTP API
     */
    public function ajax_test_s3_connection() {
        
        // Verify nonce - accept both cf7_admin_nonce and cf7_artist_submissions_settings
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce') && !wp_verify_nonce($_POST['nonce'], 'cf7_artist_submissions_settings')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $aws_key = sanitize_text_field($_POST['aws_access_key']);
        
        $aws_secret_raw = $_POST['aws_secret_key'];
        // Fix common URL encoding issues with + character being converted to space
        if (strlen($aws_secret_raw) === 40 && substr($aws_secret_raw, 0, 1) === ' ') {
            $aws_secret_raw = '+' . substr($aws_secret_raw, 1);
        }
        $aws_secret = sanitize_text_field($aws_secret_raw);
        
        $aws_region = sanitize_text_field($_POST['aws_region']);
        $s3_bucket = sanitize_text_field($_POST['s3_bucket']);
        
        if (empty($aws_key) || empty($aws_secret) || empty($aws_region) || empty($s3_bucket)) {
            wp_send_json_error(array('message' => 'All S3 fields are required'));
            return;
        }
        
        try {
            // Initialize our S3 handler with the provided credentials
            $s3_handler = new CF7_Artist_Submissions_S3_Handler();
            
            // Get existing options to backup
            $existing_options = get_option('cf7_artist_submissions_options', array());
            $backup_options = $existing_options; // Full backup
            
            // Update options with test credentials (the format S3 handler expects)
            $existing_options['aws_access_key'] = $aws_key;
            $existing_options['aws_secret_key'] = $aws_secret;
            $existing_options['aws_region'] = $aws_region;
            $existing_options['s3_bucket'] = $s3_bucket;
            
            // Set test credentials in the format the S3 handler reads
            update_option('cf7_artist_submissions_options', $existing_options);
            
            // Test the connection by trying to list bucket contents (limited)
            $test_result = $s3_handler->test_connection();
            
            // Always restore original credentials after test
            update_option('cf7_artist_submissions_options', $backup_options);
            
            if (!$test_result['success']) {
                
                // Enhanced error response for 403 debugging
                $error_message = $test_result['message'];
                if (isset($test_result['details'])) {
                    $error_message .= '<br><br><div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #f59e0b; margin-top: 10px; text-align: left; white-space: pre-line; font-family: monospace; font-size: 12px;">';
                    $error_message .= '<strong>Detailed Error Information:</strong><br><br>' . esc_html($test_result['details']);
                    $error_message .= '</div>';
                }
                
                wp_send_json_error(array(
                    'message' => $error_message,
                    'details' => $test_result['details'] ?? 'Connection test failed',
                    'raw_response' => $test_result['raw_response'] ?? null
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'S3 connection successful! Bucket is accessible.',
                'details' => array(
                    'region' => $aws_region,
                    'bucket' => $s3_bucket,
                    'status' => 'Connected',
                    'test_details' => $test_result['details'] ?? 'Connection verified'
                )
            ));
            
        } catch (Exception $e) {
            // Restore original credentials on error
            if (isset($backup_options)) {
                update_option('cf7_artist_submissions_options', $backup_options);
            }
            
            wp_send_json_error(array(
                'message' => 'S3 connection test failed',
                'details' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for testing PDF Lambda function
     */
    public function ajax_test_pdf_lambda() {
        // Log that the function was called
        error_log('=== CF7AS: ajax_test_pdf_lambda function called ===');
        error_log('CF7AS: $_POST data: ' . print_r($_POST, true));
        error_log('CF7AS: Current user can manage options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7as_test_pdf_lambda')) {
            error_log('CF7AS: Nonce verification failed');
            error_log('CF7AS: Expected nonce action: cf7as_test_pdf_lambda');
            error_log('CF7AS: Received nonce: ' . $_POST['nonce']);
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        error_log('CF7AS: Nonce verification passed');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        try {
            // Get submitted values
            $lambda_arn = sanitize_text_field($_POST['pdf_lambda_function_arn']);
            $aws_access_key = sanitize_text_field($_POST['aws_access_key']);
            $aws_secret_key = sanitize_text_field($_POST['aws_secret_key']);
            $aws_region = sanitize_text_field($_POST['aws_region']);
            $s3_bucket = sanitize_text_field($_POST['s3_bucket']);
            
            error_log('CF7AS: Test values - Lambda ARN: ' . $lambda_arn . ', Region: ' . $aws_region);
            
            // Validate inputs
            $validation_errors = array();
            
            if (empty($lambda_arn)) {
                $validation_errors[] = 'Lambda function ARN is required';
            } elseif (!preg_match('/^arn:aws:lambda:[a-z0-9\-]+:\d+:function:[a-zA-Z0-9\-_]+$/', $lambda_arn)) {
                $validation_errors[] = 'Invalid Lambda function ARN format';
            }
            
            if (empty($aws_access_key)) {
                $validation_errors[] = 'AWS Access Key is required';
            }
            
            if (empty($aws_secret_key)) {
                $validation_errors[] = 'AWS Secret Key is required';
            }
            
            if (empty($aws_region)) {
                $validation_errors[] = 'AWS Region is required';
            }
            
            if (empty($s3_bucket)) {
                $validation_errors[] = 'S3 Bucket is required';
            }
            
            if (!empty($validation_errors)) {
                wp_send_json_error(array(
                    'message' => 'Configuration incomplete',
                    'errors' => $validation_errors
                ));
                return;
            }
            
            // Create test payload
            $test_payload = array(
                'test' => true,
                'timestamp' => current_time('mysql')
            );
            
            // Generate AWS Lambda invocation endpoint
            // Extract function name from ARN for endpoint
            $function_name = basename($lambda_arn);
            $endpoint = "https://lambda.{$aws_region}.amazonaws.com/2015-03-31/functions/{$function_name}/invocations";
            $json_payload = json_encode($test_payload);
            
            // Generate AWS signature
            $headers = $this->generate_lambda_headers($json_payload, $endpoint, $aws_access_key, $aws_secret_key, $aws_region);
            
            // Make HTTP request to Lambda
            $args = array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => $json_payload,
                'timeout' => 30
            );
            
            $response = wp_remote_request($endpoint, $args);
            
            if (is_wp_error($response)) {
                wp_send_json_error(array(
                    'message' => 'HTTP request failed: ' . $response->get_error_message(),
                    'lambda_arn_valid' => true,
                    'credentials_valid' => false,
                    's3_bucket_configured' => !empty($s3_bucket),
                    'connection_successful' => false
                ));
                return;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($status_code !== 200) {
                wp_send_json_error(array(
                    'message' => "Lambda invocation failed with status $status_code: $body",
                    'lambda_arn_valid' => true,
                    'credentials_valid' => false,
                    's3_bucket_configured' => !empty($s3_bucket),
                    'connection_successful' => false
                ));
                return;
            }
            
            // Parse Lambda response
            $lambda_response = json_decode($body, true);
            
            // Extract the actual response from Lambda's body field if it exists
            $actual_response = $lambda_response;
            if (isset($lambda_response['body'])) {
                $nested_response = json_decode($lambda_response['body'], true);
                if ($nested_response) {
                    $actual_response = $nested_response;
                }
            }
            
            wp_send_json_success(array(
                'lambda_arn_valid' => true,
                'credentials_valid' => true,
                's3_bucket_configured' => !empty($s3_bucket),
                'connection_successful' => true,
                'lambda_response' => $actual_response,
                'message' => 'PDF Lambda function test successful'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'PDF Lambda test failed: ' . $e->getMessage(),
                'lambda_arn_valid' => false,
                'credentials_valid' => false,
                's3_bucket_configured' => false,
                'connection_successful' => false
            ));
        }
    }
    
    /**
     * Generate AWS Signature V4 headers for Lambda invocation
     */
    private function generate_lambda_headers($payload, $endpoint, $access_key, $secret_key, $region) {
        $url_parts = parse_url($endpoint);
        $host = $url_parts['host'];
        $path = $url_parts['path'];
        
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $service = 'lambda';
        
        // Create canonical request
        $canonical_headers = "host:$host\nx-amz-date:$timestamp\n";
        $signed_headers = 'host;x-amz-date';
        $payload_hash = hash('sha256', $payload);
        
        $canonical_request = "POST\n$path\n\n$canonical_headers\n$signed_headers\n$payload_hash";
        
        // Create string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = "$date/$region/$service/aws4_request";
        $string_to_sign = "$algorithm\n$timestamp\n$credential_scope\n" . hash('sha256', $canonical_request);
        
        // Calculate signature
        $signing_key = $this->get_lambda_signature_key($secret_key, $date, $region, $service);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        // Create authorization header
        $authorization = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        return array(
            'Authorization' => $authorization,
            'X-Amz-Date' => $timestamp,
            'Content-Type' => 'application/json'
        );
    }
    
    /**
     * Generate signing key for AWS Signature V4
     */
    private function get_lambda_signature_key($key, $date, $region, $service) {
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $key, true);
        $k_region = hash_hmac('sha256', $region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        return hash_hmac('sha256', 'aws4_request', $k_service, true);
    }
}