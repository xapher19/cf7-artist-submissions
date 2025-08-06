<?php
/**
 * CF7 Artist Submissions - AWS Lambda PDF Export System
 *
 * Comprehensive PDF export functionality for artist submissions using AWS Lambda
 * and Puppeteer for professional PDF generation with configurable content sections,
 * ratings integration, curator comments, and secure cloud-based processing.
 *
 * Features:
 * • AWS Lambda-powered PDF generation using Puppeteer and Chrome
 * • Professional PDF output with pixel-perfect rendering
 * • Configurable content sections (personal info, works, ratings, curator notes/comments)
 * • Real-time progress tracking and status updates
 * • Secure S3 storage with presigned download URLs
 * • Advanced error handling and retry mechanisms
 * • Rating system integration with star displays
 * • Curator comments and notes with timestamps
 * • Confidential watermarks for sensitive documents
 * • Mobile-responsive admin interface
 *
 * @package CF7_Artist_Submissions
 * @subpackage PDFExport
 * @since 1.2.0
 * @version 1.3.0
 */

/**
 * CF7 Artist Submissions PDF Export Class
 * 
 * Professional PDF export system for artist submissions using AWS Lambda
 * for server-side PDF generation. Provides comprehensive document generation
 * with professional styling, ratings integration, curator functionality,
 * and seamless cloud-based processing workflow.
 * 
 * @since 1.2.0
 */

class CF7_Artist_Submissions_PDF_Export {
    
    // ============================================================================
    // PROPERTIES SECTION
    // ============================================================================
    
    /**
     * AWS Lambda configuration
     */
    private $lambda_function_arn;
    private $s3_handler;
    private $aws_region;
    
    /**
     * Export job tracking
     */
    private $active_jobs = array();
    
    // ============================================================================
    // INITIALIZATION SECTION
    // ============================================================================
    
    /**
     * Initialize AWS Lambda-powered PDF export system with comprehensive functionality.
     * 
     * Establishes AJAX handlers for PDF generation, script enqueuing for export
     * interface, AWS Lambda integration, and callback processing. Provides complete
     * PDF export infrastructure including cloud-based generation, progress tracking,
     * configurable content sections, and professional document formatting.
     * 
     * @since 1.2.0
     */
    public function init() {
        // Initialize S3 handler for AWS operations
        $this->s3_handler = new CF7_Artist_Submissions_S3_Handler();
        $this->s3_handler->init();
        
        // Load AWS configuration
        $this->load_aws_config();
        
        // AJAX handlers
        add_action('wp_ajax_cf7_export_submission_pdf', array($this, 'handle_pdf_export'));
        add_action('wp_ajax_cf7_pdf_export_callback', array($this, 'handle_lambda_callback'));
        add_action('wp_ajax_cf7_check_pdf_status', array($this, 'handle_pdf_status_check'));
        
        // Script enqueuing
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add meta box for PDF export options
        add_action('add_meta_boxes', array($this, 'add_pdf_export_meta_box'));
        
        // Cleanup old PDFs periodically
        add_action('cf7_cleanup_old_pdfs', array($this, 'cleanup_old_pdfs'));
        if (!wp_next_scheduled('cf7_cleanup_old_pdfs')) {
            wp_schedule_event(time(), 'daily', 'cf7_cleanup_old_pdfs');
        }
    }
    
    /**
     * Load AWS configuration from plugin settings
     */
    private function load_aws_config() {
        $options = get_option('cf7_artist_submissions_options', array());
        
        $this->aws_region = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
        $this->lambda_function_arn = isset($options['pdf_lambda_function_arn']) ? $options['pdf_lambda_function_arn'] : '';
        
        // Extract region from Lambda ARN if available and region not explicitly set
        if (!empty($this->lambda_function_arn)) {
            $arn_parts = explode(':', $this->lambda_function_arn);
            if (count($arn_parts) >= 4 && !empty($arn_parts[3])) {
                $arn_region = $arn_parts[3];
                // Use ARN region if no region explicitly configured or if using default
                if (!isset($options['aws_region']) || $this->aws_region === 'us-east-1') {
                    $this->aws_region = $arn_region;
                }
            }
        }
        
        // Validate Lambda function ARN format
        if (!empty($this->lambda_function_arn) && !$this->validate_lambda_arn($this->lambda_function_arn)) {
            error_log('CF7AS PDF Export: Invalid Lambda function ARN format');
            $this->lambda_function_arn = '';
        }
    }
    
    /**
     * Validate Lambda function ARN format
     */
    private function validate_lambda_arn($arn) {
        return preg_match('/^arn:aws:lambda:[a-z0-9\-]+:\d+:function:[a-zA-Z0-9\-_]+$/', $arn);
    }
    
    /**
     * Enqueue JavaScript and localization for PDF export interface.
     * Loads export scripts with AJAX configuration, progress tracking, and user feedback.
     */
    public function enqueue_scripts($hook) {
        global $post;
        
        // Enqueue for individual submission edit pages (main functionality)
        $should_enqueue = false;
        
        if ($hook === 'post.php' && isset($post) && $post->post_type === 'cf7_submission') {
            $should_enqueue = true;
        }
        
        // Also enqueue for settings page but with limited functionality
        // The settings page has its own test button but may need some shared utilities
        if (strpos($hook, 'cf7-artist-submissions-settings') !== false) {
            $should_enqueue = true;
        }
        
        if ($should_enqueue) {
            wp_enqueue_script(
                'cf7-pdf-export',
                CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/pdf-export.js',
                array('jquery'),
                CF7_ARTIST_SUBMISSIONS_VERSION,
                true
            );
            
            wp_localize_script('cf7-pdf-export', 'cf7_pdf_export', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_pdf_export_nonce'),
                'export_text' => __('Generating PDF...', 'cf7-artist-submissions'),
                'processing_text' => __('Processing in cloud...', 'cf7-artist-submissions'),
                'success_text' => __('PDF generated successfully!', 'cf7-artist-submissions'),
                'error_text' => __('Error generating PDF', 'cf7-artist-submissions'),
                'download_text' => __('Download PDF', 'cf7-artist-submissions'),
                'lambda_available' => !empty($this->lambda_function_arn),
                'status_check_interval' => 3000, // 3 seconds
                'max_status_checks' => 60 // 3 minutes maximum
            ));
        }
    }
    
    // ============================================================================
    // ADMIN INTERFACE SECTION
    // ============================================================================
    
    /**
     * Add PDF export meta box to submission editor.
     * Registers meta box for export options and functionality.
     */
    public function add_pdf_export_meta_box() {
        add_meta_box(
            'cf7_pdf_export',
            __('Export Options', 'cf7-artist-submissions'),
            array($this, 'render_pdf_export_meta_box'),
            'cf7_submission',
            'side',
            'high'
        );
    }
    
    /**
     * Render PDF export meta box interface with configuration options.
     * Displays export controls, content selection checkboxes, and styling.
     */
    public function render_pdf_export_meta_box($post) {
        ?>
        <div class="cf7-export-options">
            <div class="cf7-export-section">
                <h4><?php _e('Artist Profile Export', 'cf7-artist-submissions'); ?></h4>
                <p class="description"><?php _e('Generate a beautifully formatted PDF with artist information and submitted works.', 'cf7-artist-submissions'); ?></p>
                
                <div class="cf7-export-options-list">
                    <label>
                        <input type="checkbox" name="include_personal_info" checked> 
                        <?php _e('Include Personal Information', 'cf7-artist-submissions'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_works" checked> 
                        <?php _e('Include Submitted Works', 'cf7-artist-submissions'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_ratings"> 
                        <?php _e('Include Ratings', 'cf7-artist-submissions'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_curator_notes"> 
                        <?php _e('Include Curator Notes', 'cf7-artist-submissions'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_curator_comments"> 
                        <?php _e('Include Curator Comments', 'cf7-artist-submissions'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="confidential_watermark" checked> 
                        <?php _e('Add "Private & Confidential" Watermark', 'cf7-artist-submissions'); ?>
                    </label>
                </div>
                
                <div class="cf7-export-actions">
                    <button type="button" class="button button-primary cf7-export-pdf-btn" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php _e('Export to PDF', 'cf7-artist-submissions'); ?>
                    </button>
                    <div class="cf7-export-status"></div>
                    <div class="cf7-export-progress" style="display: none;">
                        <div class="cf7-export-progress-bar">
                            <div class="cf7-export-progress-fill"></div>
                        </div>
                        <div class="cf7-export-progress-text"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .cf7-export-options {
            padding: 0;
        }
        
        .cf7-export-section h4 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #1d2327;
        }
        
        .cf7-export-options-list {
            margin: 12px 0;
        }
        
        .cf7-export-options-list label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .cf7-export-options-list input[type="checkbox"] {
            margin-right: 6px;
        }
        
        .cf7-export-actions {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #dcdcde;
        }
        
        .cf7-export-pdf-btn {
            width: 100%;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .cf7-export-pdf-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cf7-export-status {
            margin-top: 8px;
            padding: 6px 0;
            font-size: 12px;
            text-align: center;
        }
        
        .cf7-export-status.success {
            color: #00a32a;
        }
        
        .cf7-export-status.error {
            color: #d63638;
        }
        
        .cf7-export-status.loading {
            color: #2271b1;
        }
        
        .cf7-export-progress {
            margin-top: 12px;
        }
        
        .cf7-export-progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .cf7-export-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #135e96);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .cf7-export-progress-text {
            font-size: 12px;
            text-align: center;
            color: #646970;
        }
        </style>
        <?php
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler for PDF export request processing with AWS Lambda integration.
     * 
     * Processes PDF export requests with comprehensive security validation,
     * capability checks, and option parsing. Handles export configuration,
     * AWS Lambda invocation, and response formatting for seamless user
     * interface integration and cloud-based PDF generation.
     * 
     * @since 1.2.0
     */
    public function handle_pdf_export() {
        // Check if this is a proper AJAX request
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_die('Invalid request method');
        }
        
        // Check if required POST data exists
        if (empty($_POST['nonce'])) {
            wp_send_json_error(array('message' => 'Security nonce missing'));
            return;
        }
        
        if (empty($_POST['post_id'])) {
            wp_send_json_error(array('message' => 'Submission ID missing'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_export_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Verify the post exists and is the right type
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => 'Submission not found'));
            return;
        }
        
        if ($post->post_type !== 'cf7_submission') {
            wp_send_json_error(array('message' => 'Invalid submission type'));
            return;
        }
        
        // Parse options
        $options = array(
            'include_personal_info' => isset($_POST['include_personal_info']) && $_POST['include_personal_info'] == '1',
            'include_works' => isset($_POST['include_works']) && $_POST['include_works'] == '1',
            'include_ratings' => isset($_POST['include_ratings']) && $_POST['include_ratings'] == '1',
            'include_curator_notes' => isset($_POST['include_curator_notes']) && $_POST['include_curator_notes'] == '1',
            'include_curator_comments' => isset($_POST['include_curator_comments']) && $_POST['include_curator_comments'] == '1',
            'confidential_watermark' => isset($_POST['confidential_watermark']) && $_POST['confidential_watermark'] == '1'
        );
        
        // Check if Lambda is configured
        if (empty($this->lambda_function_arn)) {
            error_log('CF7AS: PDF Export - No Lambda ARN found, checking plugin options...');
            $plugin_options = get_option('cf7_artist_submissions_options', array());
            error_log('CF7AS: PDF Export - Plugin options keys: ' . implode(', ', array_keys($plugin_options)));
            if (isset($plugin_options['pdf_lambda_function_arn'])) {
                error_log('CF7AS: PDF Export - PDF Lambda ARN in options: ' . $plugin_options['pdf_lambda_function_arn']);
            }
        }
        
        // Check if Lambda is configured
        if (empty($this->lambda_function_arn)) {
            // Fallback to legacy HTML generation
            $result = $this->generate_html_fallback($post_id, $options);
        } else {
            // Use AWS Lambda for PDF generation
            $result = $this->generate_pdf_via_lambda($post_id, $options);
        }
        
        error_log('CF7AS: PDF Export - Generation result: ' . print_r($result, true));
        
        if ($result['success']) {
            error_log('CF7AS: PDF Export - Success, sending response');
            wp_send_json_success($result);
        } else {
            error_log('CF7AS: PDF Export - Failed: ' . $result['message']);
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle Lambda callback from AWS
     */
    public function handle_lambda_callback() {
        // Verify this is a legitimate callback (you might want to add signature verification)
        $job_id = sanitize_text_field($_POST['job_id']);
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($job_id)) {
            wp_die('Invalid callback');
        }
        
        // Update job status in database
        $this->update_job_status($job_id, $_POST);
        
        wp_send_json_success(array('message' => 'Callback processed'));
    }
    
    /**
     * Handle PDF status check requests
     */
    public function handle_pdf_status_check() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_export_nonce')) {
            wp_die('Security check failed');
        }
        
        $job_id = sanitize_text_field($_POST['job_id']);
        $status = $this->get_job_status($job_id);
        
        wp_send_json_success($status);
    }
    
    // ============================================================================
    // PDF GENERATION SECTION
    // ============================================================================
    
    /**
     * Generate PDF using AWS Lambda with Puppeteer for professional output.
     * 
     * Invokes AWS Lambda function to create high-quality PDFs with comprehensive
     * formatting, validation, content preparation, and cloud-based processing.
     * Implements job tracking, progress monitoring, and error handling for
     * reliable PDF generation workflow integration.
     * 
     * @since 1.2.0
     */
    private function generate_pdf_via_lambda($post_id, $options = array()) {
        // Check if post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            return array('success' => false, 'message' => 'Submission not found');
        }
        
        try {
            // Generate unique job ID
            $job_id = 'pdf_' . uniqid() . '_' . $post_id;
            
            // Prepare submission data for Lambda
            $submission_data = $this->prepare_submission_data($post_id);
            
            // Get site information
            $site_info = $this->get_site_info();
            
            // Get S3 bucket configuration
            $plugin_options = get_option('cf7_artist_submissions_options', array());
            $s3_bucket = isset($plugin_options['s3_bucket']) ? $plugin_options['s3_bucket'] : '';
            
            if (empty($s3_bucket)) {
                return array('success' => false, 'message' => 'S3 bucket not configured');
            }
            
            // Prepare Lambda event payload
            $lambda_payload = array(
                'job_id' => $job_id,
                'submission_data' => $submission_data,
                'export_options' => $options,
                'bucket' => $s3_bucket,
                'callback_url' => admin_url('admin-ajax.php?action=cf7_pdf_export_callback'),
                'site_info' => $site_info
            );
            
        // Additional payload cleaning and validation
        $lambda_payload = $this->clean_data_for_lambda($lambda_payload);
        
        // ENHANCED DEBUGGING: Check all data types in payload
        $this->validate_lambda_payload_types($lambda_payload);
        
        // CRITICAL DEBUGGING: Check specific field types that might cause JS errors
        if (isset($lambda_payload['submission_data']['id'])) {
            error_log('CF7AS: PDF Export - submission_data.id type: ' . gettype($lambda_payload['submission_data']['id']) . ', value: ' . var_export($lambda_payload['submission_data']['id'], true));
        }
        
        // Check all numeric-looking fields in submission_data
        if (isset($lambda_payload['submission_data'])) {
            foreach ($lambda_payload['submission_data'] as $key => $value) {
                if (is_numeric($value) || (is_string($value) && ctype_digit($value))) {
                    error_log('CF7AS: PDF Export - Numeric field detected: ' . $key . ' (type: ' . gettype($value) . ', value: ' . var_export($value, true) . ')');
                }
            }
        }
        
        error_log('CF7AS: PDF Export - LAMBDA PAYLOAD: ' . print_r($lambda_payload, true));
        error_log('CF7AS: PDF Export - Export options being sent: ' . print_r($options, true));
        error_log('CF7AS: PDF Export - Payload JSON size: ' . strlen(json_encode($lambda_payload)) . ' bytes');            // Store job in database for tracking
            $this->store_job($job_id, $post_id, 'processing', $options);
            
            // Invoke Lambda function
            $lambda_result = $this->invoke_lambda_function($lambda_payload);
            
            if ($lambda_result['success']) {
                // Check if Lambda returned immediate results (synchronous processing)
                $lambda_response = isset($lambda_result['response']) ? $lambda_result['response'] : array();
                
                // Parse the response body if it's a JSON string
                if (isset($lambda_response['body']) && is_string($lambda_response['body'])) {
                    $response_body = json_decode($lambda_response['body'], true);
                    
                    // Check if Lambda function itself returned an error
                    if (isset($response_body['success']) && !$response_body['success']) {
                        $error_message = isset($response_body['error']) ? $response_body['error'] : 'Unknown Lambda error';
                        error_log('CF7AS: PDF Export - Lambda function error: ' . $error_message);
                        
                        // Update job status to failed
                        $this->update_job_status($job_id, array(
                            'status' => 'failed', 
                            'error_message' => $error_message
                        ));
                        
                        return array(
                            'success' => false,
                            'message' => 'PDF generation failed: ' . $error_message
                        );
                    }
                    
                    // If we got a successful immediate response with download URL, return it
                    if (isset($response_body['success']) && $response_body['success'] && 
                        isset($response_body['download_url'])) {
                        
                        // Update job status to completed
                        $this->update_job_status($job_id, array(
                            'status' => 'completed',
                            'pdf_s3_key' => $response_body['s3_key'] ?? '',
                            'download_url' => $response_body['download_url'],
                            'file_size' => $response_body['file_size'] ?? 0
                        ));
                        
                        return array(
                            'success' => true,
                            'message' => 'PDF generated successfully',
                            'job_id' => $job_id,
                            'type' => 'lambda',
                            'status' => 'completed',
                            'download_url' => $response_body['download_url'],
                            'file_size' => $response_body['file_size'] ?? 0
                        );
                    }
                }
                
                // Fallback to async processing mode
                return array(
                    'success' => true,
                    'message' => 'PDF generation started',
                    'job_id' => $job_id,
                    'type' => 'lambda',
                    'status' => 'processing'
                );
            } else {
                // Update job status to failed
                $this->update_job_status($job_id, array('status' => 'failed', 'error_message' => $lambda_result['message']));
                
                return array(
                    'success' => false,
                    'message' => 'Failed to start PDF generation: ' . $lambda_result['message']
                );
            }
            
        } catch (Exception $e) {
            error_log('CF7AS PDF Export Lambda Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error starting PDF generation: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Generate HTML fallback for when Lambda is not configured
     */
    private function generate_html_fallback($post_id, $options = array()) {
        // Check if post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            return array('success' => false, 'message' => 'Submission not found');
        }
        
        try {
            // Generate HTML content
            $html = $this->generate_legacy_pdf_content($post_id, $options);
            
            // Wrap in complete HTML document
            $complete_html = $this->wrap_html_document($html, $post_id, $options);
            
            // Generate filename
            $artist_name = $this->get_artist_name($post_id);
            $filename = 'artist-submission-' . sanitize_file_name($artist_name) . '-' . date('Y-m-d') . '.html';
            
            // Save to uploads directory
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $filename;
            $file_url = $upload_dir['url'] . '/' . $filename;
            
            // Write HTML file
            file_put_contents($file_path, $complete_html);
            
            return array(
                'success' => true,
                'message' => 'PDF-ready document generated successfully',
                'download_url' => $file_url,
                'filename' => $filename,
                'type' => 'html'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error generating document: ' . $e->getMessage()
            );
        }
    }
    
    // ============================================================================
    // AWS LAMBDA INTEGRATION SECTION
    // ============================================================================
    
    /**
     * Invoke AWS Lambda function for PDF generation
     */
    private function invoke_lambda_function($payload) {
        error_log('CF7AS: PDF Export - Starting Lambda invocation');
        
        try {
            // Get AWS credentials
            $plugin_options = get_option('cf7_artist_submissions_options', array());
            $aws_access_key = isset($plugin_options['aws_access_key']) ? $plugin_options['aws_access_key'] : '';
            $aws_secret_key = isset($plugin_options['aws_secret_key']) ? $plugin_options['aws_secret_key'] : '';
            
            error_log('CF7AS: PDF Export - AWS Access Key configured: ' . (!empty($aws_access_key) ? 'YES' : 'NO'));
            error_log('CF7AS: PDF Export - AWS Secret Key configured: ' . (!empty($aws_secret_key) ? 'YES' : 'NO'));
            error_log('CF7AS: PDF Export - AWS Region: ' . $this->aws_region);
            error_log('CF7AS: PDF Export - Lambda ARN: ' . $this->lambda_function_arn);
            
            if (empty($aws_access_key) || empty($aws_secret_key)) {
                return array('success' => false, 'message' => 'AWS credentials not configured');
            }
            
            // Prepare AWS Lambda invocation
            // Extract function name from ARN for endpoint
            // ARN format: arn:aws:lambda:region:account-id:function:function-name
            $arn_parts = explode(':', $this->lambda_function_arn);
            if (count($arn_parts) >= 6) {
                $function_name = $arn_parts[6]; // Get the function name part
            } else {
                $function_name = basename($this->lambda_function_arn); // Fallback
            }
            
            $endpoint = "https://lambda.{$this->aws_region}.amazonaws.com/2015-03-31/functions/{$function_name}/invocations";
            $json_payload = json_encode($payload);
            
            error_log('CF7AS: PDF Export - Function name extracted: ' . $function_name);
            error_log('CF7AS: PDF Export - Endpoint: ' . $endpoint);
            error_log('CF7AS: PDF Export - Payload size: ' . strlen($json_payload) . ' bytes');
            
            // Generate AWS signature
            $headers = $this->generate_aws_headers($json_payload, $endpoint, $aws_access_key, $aws_secret_key);
            
            error_log('CF7AS: PDF Export - Generated headers: ' . print_r(array_keys($headers), true));
            
            // Make HTTP request to Lambda
            $args = array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => $json_payload,
                'timeout' => 30,
                'sslverify' => true
            );
            
            error_log('CF7AS: PDF Export - Making HTTP request to: ' . $endpoint);
            error_log('CF7AS: PDF Export - Request args (without body): ' . print_r(array_merge($args, array('body' => '[PAYLOAD_' . strlen($json_payload) . '_BYTES]')), true));
            
            $response = wp_remote_request($endpoint, $args);
            
            if (is_wp_error($response)) {
                error_log('CF7AS: PDF Export - WP HTTP Error: ' . $response->get_error_message());
                return array('success' => false, 'message' => 'HTTP request failed: ' . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            
            error_log('CF7AS: PDF Export - Response status: ' . $status_code);
            error_log('CF7AS: PDF Export - Response headers: ' . print_r($response_headers, true));
            error_log('CF7AS: PDF Export - Response body: ' . $body);
            
            if ($status_code !== 200 && $status_code !== 202) {
                error_log('CF7AS: PDF Export - Lambda invocation failed with status ' . $status_code);
                return array('success' => false, 'message' => "Lambda invocation failed with status $status_code: $body");
            }
            
            error_log('CF7AS: PDF Export - Lambda invocation successful');
            return array('success' => true, 'response' => json_decode($body, true));
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Lambda invocation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate AWS Signature V4 headers for Lambda invocation
     */
    private function generate_aws_headers($payload, $endpoint, $access_key, $secret_key) {
        error_log('CF7AS: PDF Export - Generating AWS signature headers');
        
        $url_parts = parse_url($endpoint);
        $host = $url_parts['host'];
        $path = isset($url_parts['path']) ? $url_parts['path'] : '/';
        
        // Use GMT time for AWS signatures
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $service = 'lambda';
        $region = $this->aws_region;
        
        error_log('CF7AS: PDF Export - Host: ' . $host);
        error_log('CF7AS: PDF Export - Path: ' . $path);
        error_log('CF7AS: PDF Export - Timestamp: ' . $timestamp);
        error_log('CF7AS: PDF Export - Date: ' . $date);
        error_log('CF7AS: PDF Export - Region: ' . $region);
        
        // Create canonical request
        $canonical_headers = "host:" . $host . "\n" . "x-amz-date:" . $timestamp . "\n";
        $signed_headers = 'host;x-amz-date';
        $payload_hash = hash('sha256', $payload);
        
        // Canonical request format: METHOD\nURI\nQUERY_STRING\nHEADERS\nSIGNED_HEADERS\nPAYLOAD_HASH
        // Make sure path is properly URL-encoded
        $canonical_uri = rawurlencode($path);
        // But don't double-encode slashes
        $canonical_uri = str_replace('%2F', '/', $canonical_uri);
        
        $canonical_request = "POST\n" . $canonical_uri . "\n\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
        
        error_log('CF7AS: PDF Export - Canonical URI: ' . $canonical_uri);
        error_log('CF7AS: PDF Export - Payload hash: ' . $payload_hash);
        error_log('CF7AS: PDF Export - Canonical request hash: ' . hash('sha256', $canonical_request));
        
        // Create string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = $date . '/' . $region . '/' . $service . '/aws4_request';
        $string_to_sign = $algorithm . "\n" . $timestamp . "\n" . $credential_scope . "\n" . hash('sha256', $canonical_request);
        
        error_log('CF7AS: PDF Export - Credential scope: ' . $credential_scope);
        error_log('CF7AS: PDF Export - String to sign hash: ' . hash('sha256', $string_to_sign));
        
        // Calculate signature
        $signing_key = $this->get_signature_key($secret_key, $date, $region, $service);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        error_log('CF7AS: PDF Export - Signature: ' . $signature);
        
        // Create authorization header
        $authorization = $algorithm . ' Credential=' . $access_key . '/' . $credential_scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;
        
        error_log('CF7AS: PDF Export - Authorization header: ' . substr($authorization, 0, 100) . '...');
        
        return array(
            'Authorization' => $authorization,
            'X-Amz-Date' => $timestamp,
            'Content-Type' => 'application/json',
            'Host' => $host
        );
    }
    
    /**
     * Generate signing key for AWS Signature V4
     */
    private function get_signature_key($key, $date, $region, $service) {
        error_log('CF7AS: PDF Export - Generating signing key for date: ' . $date . ', region: ' . $region . ', service: ' . $service);
        
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $key, true);
        $k_region = hash_hmac('sha256', $region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        $signing_key = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        error_log('CF7AS: PDF Export - Signing key generated (length: ' . strlen($signing_key) . ')');
        
        return $signing_key;
    }
    
    // ============================================================================
    // JOB TRACKING SECTION
    // ============================================================================
    
    /**
     * Store PDF generation job in database
     */
    private function store_job($job_id, $post_id, $status, $options) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_pdf_jobs';
        
        // Create table if it doesn't exist
        $this->create_jobs_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'job_id' => $job_id,
                'post_id' => $post_id,
                'status' => $status,
                'export_options' => json_encode($options),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Update job status in database
     */
    private function update_job_status($job_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_pdf_jobs';
        
        $update_data = array('updated_at' => current_time('mysql'));
        $update_format = array('%s');
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $update_format[] = '%s';
        }
        
        if (isset($data['pdf_s3_key'])) {
            $update_data['pdf_s3_key'] = sanitize_text_field($data['pdf_s3_key']);
            $update_format[] = '%s';
        }
        
        if (isset($data['download_url'])) {
            $update_data['download_url'] = esc_url_raw($data['download_url']);
            $update_format[] = '%s';
        }
        
        if (isset($data['error_message'])) {
            $update_data['error_message'] = sanitize_textarea_field($data['error_message']);
            $update_format[] = '%s';
        }
        
        if (isset($data['file_size'])) {
            $update_data['file_size'] = intval($data['file_size']);
            $update_format[] = '%d';
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('job_id' => $job_id),
            $update_format,
            array('%s')
        );
    }
    
    /**
     * Get job status from database
     */
    private function get_job_status($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_pdf_jobs';
        
        $job = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE job_id = %s", $job_id),
            ARRAY_A
        );
        
        if (!$job) {
            return array('status' => 'not_found');
        }
        
        return $job;
    }
    
    /**
     * Create jobs tracking table
     */
    private function create_jobs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_pdf_jobs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            job_id varchar(100) NOT NULL,
            post_id int(11) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'processing',
            export_options text,
            pdf_s3_key varchar(255),
            download_url text,
            error_message text,
            file_size int(11),
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY job_id (job_id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // ============================================================================
    // DATA PREPARATION SECTION
    // ============================================================================
    
    /**
     * Prepare submission data for Lambda function
     */
    private function prepare_submission_data($post_id) {
        error_log('CF7AS: PDF Export - Starting data preparation for post: ' . $post_id);
        
        $post = get_post($post_id);
        $meta_data = get_post_meta($post_id);
        
        error_log('CF7AS: PDF Export - Post title: ' . $post->post_title);
        error_log('CF7AS: PDF Export - Meta data keys: ' . implode(', ', array_keys($meta_data)));
        
        // DIAGNOSTIC: Check what field names actually exist for this post
        $cf7_fields = array();
        foreach (array_keys($meta_data) as $key) {
            if (strpos($key, 'cf7_') === 0) {
                $cf7_fields[] = $key;
            }
        }
        error_log('CF7AS: PDF Export - DIAGNOSTIC: All CF7 fields found: ' . implode(', ', $cf7_fields));
        
        // Extract artist information
        $submission_data = array(
            'id' => $post_id,
            'title' => $post->post_title,
            'artist_name' => $this->get_artist_name($post_id),
            'submission_date' => get_post_meta($post_id, 'cf7_submission_date', true)
        );
        
        error_log('CF7AS: PDF Export - Basic data prepared, artist name: ' . $submission_data['artist_name']);
        
        // Add personal information fields
        // Note: WordPress forms may save fields with hyphens (admin/manual) or underscores (sanitize_key)
        $personal_fields = array(
            'email' => array('cf7_email', 'cf7_your-email', 'cf7_your_email', 'cf7_artist-email', 'cf7_artist_email'),
            'phone' => array('cf7_phone', 'cf7_your-phone', 'cf7_your_phone', 'cf7_contact-phone', 'cf7_contact_phone'),
            'pronouns' => array('cf7_pronouns', 'cf7_your-pronouns', 'cf7_your_pronouns', 'cf7_artist-pronouns', 'cf7_artist_pronouns'),
            'address' => array('cf7_address', 'cf7_your-address', 'cf7_your_address', 'cf7_contact-address', 'cf7_contact_address'),
            'website' => array('cf7_website', 'cf7_your-website', 'cf7_your_website', 'cf7_artist-website', 'cf7_artist_website'),
            'portfolio_link' => array('cf7_portfolio-link', 'cf7_portfolio_link', 'cf7_portfolio', 'cf7_portfolio-url', 'cf7_portfolio_url'),
            'bio' => array('cf7_bio', 'cf7_artist-bio', 'cf7_artist_bio', 'cf7_biography'),
            'artist_statement' => array('cf7_artist-statement', 'cf7_artist_statement', 'cf7_statement', 'cf7_artistic-statement', 'cf7_artistic_statement'),
            'experience' => array('cf7_experience', 'cf7_art-experience', 'cf7_art_experience', 'cf7_years-experience', 'cf7_years_experience'),
            'medium' => array('cf7_medium', 'cf7_preferred-medium', 'cf7_preferred_medium', 'cf7_art-medium', 'cf7_art_medium'),
            'artistic_mediums' => array('cf7_artistic-mediums', 'cf7_artistic_mediums', 'cf7_art-mediums', 'cf7_art_mediums', 'cf7_mediums'),
            'text_mediums' => array('cf7_text-mediums', 'cf7_text_mediums', 'cf7_text-medium', 'cf7_text_medium', 'cf7_writing-mediums', 'cf7_writing_mediums'),
            'style' => array('cf7_style', 'cf7_art-style', 'cf7_art_style', 'cf7_artistic-style', 'cf7_artistic_style')
        );
        
        foreach ($personal_fields as $field => $possible_keys) {
            foreach ($possible_keys as $key) {
                $value = get_post_meta($post_id, $key, true);
                if (!empty($value)) {
                    $submission_data[$field] = $value;
                    error_log('CF7AS: PDF Export - DIAGNOSTIC: Found field ' . $field . ' using key ' . $key . ' = ' . substr($value, 0, 50));
                    break;
                }
            }
            
            // If field not found, log which keys were tried
            if (!isset($submission_data[$field])) {
                error_log('CF7AS: PDF Export - DIAGNOSTIC: Field ' . $field . ' not found, tried keys: ' . implode(', ', $possible_keys));
            }
        }
        
        error_log('CF7AS: PDF Export - Personal fields processed');
        
        // Add all other meta fields for fallback
        foreach ($meta_data as $key => $value) {
            if (substr($key, 0, 1) !== '_' && substr($key, 0, 8) !== 'cf7_file_' && !isset($submission_data[$key])) {
                $submission_data[$key] = is_array($value) ? $value[0] : $value;
            }
        }
        
        error_log('CF7AS: PDF Export - Meta fields processed, starting works...');
        
        // Get submitted works
        try {
            $submission_data['works'] = $this->get_submitted_works($post_id);
            error_log('CF7AS: PDF Export - Works processed: ' . count($submission_data['works']));
        } catch (Exception $e) {
            error_log('CF7AS: PDF Export - Error getting submitted works: ' . $e->getMessage());
            $submission_data['works'] = array();
        }
        
        // Get ratings if available
        try {
            error_log('CF7AS: PDF Export - Starting ratings processing...');
            $submission_data['ratings'] = $this->get_submission_ratings($post_id);
            error_log('CF7AS: PDF Export - Ratings processed successfully: ' . count($submission_data['ratings']) . ' ratings found');
        } catch (Exception $e) {
            error_log('CF7AS: PDF Export - CRITICAL ERROR getting ratings: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Ratings error stack trace: ' . $e->getTraceAsString());
            $submission_data['ratings'] = array();
        } catch (Error $e) {
            error_log('CF7AS: PDF Export - FATAL ERROR getting ratings: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Ratings fatal error stack trace: ' . $e->getTraceAsString());
            $submission_data['ratings'] = array();
        }
        
        // Get curator notes
        try {
            error_log('CF7AS: PDF Export - Starting curator notes processing...');
            $curator_notes = get_post_meta($post_id, 'cf7_curator_notes', true);
            if (!empty($curator_notes)) {
                $submission_data['curator_notes'] = $curator_notes;
                error_log('CF7AS: PDF Export - Curator notes found: ' . strlen($curator_notes) . ' characters');
            } else {
                error_log('CF7AS: PDF Export - No curator notes found');
            }
        } catch (Exception $e) {
            error_log('CF7AS: PDF Export - CRITICAL ERROR getting curator notes: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Curator notes error stack trace: ' . $e->getTraceAsString());
        }
        
        // Note: Curator comments are now handled per-work in get_submitted_works() method
        // Global curator_comments field has been removed as requested
        
        error_log('CF7AS: PDF Export - Data preparation completed successfully');
        
        // Clean all submission data for Lambda compatibility
        $submission_data = $this->clean_data_for_lambda($submission_data);
        
        error_log('CF7AS: PDF Export - FULL SUBMISSION DATA: ' . print_r($submission_data, true));
        error_log('CF7AS: PDF Export - Works count: ' . count($submission_data['works']));
        error_log('CF7AS: PDF Export - Personal info fields: ' . implode(', ', array_keys(array_filter($submission_data, function($key) {
            return in_array($key, ['email', 'phone', 'pronouns', 'address', 'website', 'portfolio_link', 'bio', 'artist_statement', 'experience', 'medium', 'artistic_mediums', 'text_mediums', 'style']);
        }, ARRAY_FILTER_USE_KEY))));
        
        // Additional debugging - check data types
        foreach ($submission_data as $key => $value) {
            if (!is_string($value) && !is_array($value)) {
                error_log('CF7AS: PDF Export - WARNING: Non-string/array field detected: ' . $key . ' (type: ' . gettype($value) . ')');
            }
            if (is_string($value) && (strpos($value, '{') !== false || strpos($value, '[') !== false)) {
                error_log('CF7AS: PDF Export - WARNING: Potential JSON in string field: ' . $key . ' = ' . substr($value, 0, 100));
            }
        }
        
        return $submission_data;
    }
    
    /**
     * Clean all data to ensure Lambda compatibility
     * Removes complex objects, HTML content, and ensures all values are simple strings or arrays
     */
    private function clean_data_for_lambda($data) {
        error_log('CF7AS: PDF Export - Cleaning data for Lambda compatibility');
        
        if (is_array($data)) {
            $cleaned = array();
            foreach ($data as $key => $value) {
                // Skip any uploader data fields that contain complex JSON
                // Enhanced patterns to catch more uploader variations
                if ((strpos($key, 'uploader') !== false && strpos($key, '_data') !== false) ||
                    strpos($key, 'cf7as-uploader-') !== false ||
                    strpos($key, 'cf7_cf7as-uploader-') !== false ||
                    preg_match('/.*uploader.*_data$/', $key) ||
                    preg_match('/^_uploader/', $key)) {
                    error_log('CF7AS: PDF Export - Skipping complex uploader data field: ' . $key);
                    continue;
                }
                
                // Skip any field key that looks suspicious for Lambda
                if (strpos($key, '_edit_') !== false || 
                    strpos($key, '_lock') !== false ||
                    strpos($key, '_thumbnail') !== false ||
                    strpos($key, '_temp') !== false ||
                    strpos($key, '_cache') !== false ||
                    preg_match('/^_[a-z]/', $key)) {
                    error_log('CF7AS: PDF Export - Skipping internal/meta field: ' . $key);
                    continue;
                }
                
                // Also skip any field that contains JSON-like data
                if (is_string($value) && (strpos($value, '{"') === 0 || strpos($value, '[{') === 0)) {
                    error_log('CF7AS: PDF Export - Skipping JSON data field: ' . $key);
                    continue;
                }
                
                $cleaned[$key] = $this->clean_data_for_lambda($value);
            }
            return $cleaned;
        } elseif (is_object($data)) {
            // Convert objects to arrays
            error_log('CF7AS: PDF Export - Converting object to array');
            return $this->clean_data_for_lambda((array)$data);
        } else {
            // ULTRA-AGGRESSIVE STRING CONVERSION: Convert EVERYTHING to string
            // This ensures no matter what type we have, it becomes a string
            
            if (is_null($data)) {
                return '';
            }
            
            if (is_bool($data)) {
                return $data ? '1' : '0';
            }
            
            if (is_numeric($data) || is_int($data) || is_float($data)) {
                $string_result = strval($data);
                error_log('CF7AS: PDF Export - Converting numeric value: ' . var_export($data, true) . ' -> "' . $string_result . '"');
                return $string_result;
            }
            
            if (is_string($data)) {
                // Check if string looks like JSON and skip it
                if (strpos($data, '{"') === 0 || strpos($data, '[{') === 0) {
                    error_log('CF7AS: PDF Export - Skipping JSON string data');
                    return '';
                }
                
                // Handle empty or null strings
                if ($data === '') {
                    return '';
                }
                
                // Strip HTML tags and ensure it's a clean string
                $cleaned = strip_tags($data);
                // Also decode HTML entities
                $cleaned = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                // Remove any remaining control characters that might cause issues
                $cleaned = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $cleaned);
                // Ensure we return a proper string, never null or undefined
                $result = trim($cleaned);
                return $result !== '' ? $result : '';
            }
            
            // For absolutely any other type, force convert to string
            $result = strval($data);
            error_log('CF7AS: PDF Export - Force converting unknown type ' . gettype($data) . ' to string: "' . $result . '"');
            return $result !== null ? $result : '';
        }
    }
    
    /**
     * Validate Lambda payload data types to prevent JavaScript errors
     */
    private function validate_lambda_payload_types($data, $path = '') {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $current_path = $path ? $path . '.' . $key : $key;
                
                if (!is_string($value) && !is_array($value)) {
                    error_log('CF7AS: PDF Export - CRITICAL: Non-string/array value found in payload at ' . $current_path . ' (type: ' . gettype($value) . ', value: ' . var_export($value, true) . ')');
                }
                
                if (is_null($value)) {
                    error_log('CF7AS: PDF Export - CRITICAL: NULL value found in payload at ' . $current_path);
                }
                
                if (is_string($value)) {
                    if (strpos($value, '{') === 0 || strpos($value, '[') === 0) {
                        error_log('CF7AS: PDF Export - WARNING: Potential JSON string at ' . $current_path . ': ' . substr($value, 0, 50) . '...');
                    }
                    if (strlen($value) > 10000) {
                        error_log('CF7AS: PDF Export - WARNING: Very long string at ' . $current_path . ' (' . strlen($value) . ' chars)');
                    }
                }
                
                // Recursively validate arrays
                if (is_array($value)) {
                    $this->validate_lambda_payload_types($value, $current_path);
                }
            }
        }
    }
    
    /**
     * Get submitted works with file information from cf7as_files table
     */
    private function get_submitted_works($post_id) {
        error_log('CF7AS: PDF Export - Getting submitted works for post: ' . $post_id);
        
        $works = array();
        
        // Use metadata manager to get files from database
        if (class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
            error_log('CF7AS: PDF Export - Using metadata manager to get files');
            $metadata_manager = new CF7_Artist_Submissions_Metadata_Manager();
            $files = $metadata_manager->get_submission_files($post_id);
            error_log('CF7AS: PDF Export - Files from database: ' . count($files));
            
            foreach ($files as $file) {
                $filename = $file['original_name'] ?? basename($file['s3_key']);
                // Use sanitize_key to match the pattern used in tabs view
                $safe_filename = sanitize_key($filename);
                
                $work = array(
                    'filename' => $filename,
                    'file_url' => $this->get_file_download_url($file),
                    'file_type' => $file['mime_type'] ?? 'application/octet-stream',
                    's3_key' => $file['s3_key'],
                    'file_size' => $file['file_size'] ?? 0
                );
                
                // Get work title from post meta (prioritize this over filename)
                // Try both sanitize_key and regex patterns for compatibility
                $work_title = get_post_meta($post_id, 'cf7_work_title_' . $safe_filename, true);
                if (empty($work_title)) {
                    // Fallback to regex pattern for older data
                    $regex_safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
                    $work_title = get_post_meta($post_id, 'cf7_work_title_' . $regex_safe_filename, true);
                }
                $work['title'] = !empty($work_title) ? $work_title : pathinfo($filename, PATHINFO_FILENAME);
                
                error_log('CF7AS: PDF Export - Work title lookup for ' . $filename . ': sanitize_key=' . $safe_filename . ', found=' . (!empty($work_title) ? 'YES' : 'NO'));
                
                // Get work statement from post meta
                // Try both sanitize_key and regex patterns for compatibility
                $work_statement = get_post_meta($post_id, 'cf7_work_statement_' . $safe_filename, true);
                if (empty($work_statement)) {
                    // Fallback to regex pattern for older data
                    $regex_safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
                    $work_statement = get_post_meta($post_id, 'cf7_work_statement_' . $regex_safe_filename, true);
                }
                if (!empty($work_statement)) {
                    $work['description'] = $work_statement;
                }
                
                // Handle different file types
                if ($this->is_image_file($work['filename'])) {
                    $work['type'] = 'image';
                    // Try to get compressed preview image for PDF
                    $compressed_url = $this->get_compressed_image_url($file);
                    $work['image_url'] = !empty($compressed_url) ? $compressed_url : $work['file_url'];
                } elseif ($this->is_video_file($work['filename'])) {
                    $work['type'] = 'video';
                    // Try to get thumbnail if available
                    if (!empty($file['thumbnail_url'])) {
                        $work['thumbnail_url'] = $file['thumbnail_url'];
                    }
                } elseif ($this->is_document_file($work['filename'])) {
                    $work['type'] = 'document';
                } else {
                    $work['type'] = 'other';
                }
                
                // Get work-specific ratings and comments from the ratings system
                $work_ratings = array();
                $work_comments = array();
                
                // Get file ID from the file data if available
                if (isset($file['id'])) {
                    $file_id = intval($file['id']);
                    
                    // Try to get ratings from the ratings system
                    if (class_exists('CF7_Artist_Submissions_Ratings')) {
                        error_log('CF7AS: PDF Export - Getting ratings for file ID: ' . $file_id);
                        
                        // Get all ratings for this work from all curators
                        $all_ratings = CF7_Artist_Submissions_Ratings::get_submission_ratings($post_id);
                        
                        if (isset($all_ratings[$file_id])) {
                            $rating_data = $all_ratings[$file_id];
                            if ($rating_data['rating'] > 0) {
                                $work_ratings[] = array(
                                    'category' => 'Overall Rating',
                                    'score' => $rating_data['rating'],
                                    'comment' => $rating_data['comments']
                                );
                            }
                            
                            if (!empty($rating_data['comments'])) {
                                $work_comments[] = array(
                                    'author' => 'Curator',
                                    'content' => $rating_data['comments'],
                                    'date' => $rating_data['updated_at'] ?: $rating_data['created_at']
                                );
                            }
                        }
                        
                        error_log('CF7AS: PDF Export - Found ' . count($work_ratings) . ' ratings and ' . count($work_comments) . ' comments for file ID: ' . $file_id);
                    }
                }
                
                $work['ratings'] = $work_ratings;
                $work['comments'] = $work_comments; 
                
                error_log('CF7AS: PDF Export - Added work: ' . $work['title'] . ' (type: ' . $work['type'] . ', file: ' . $work['filename'] . ')');
                $works[] = $work;
            }
        } else {
            error_log('CF7AS: PDF Export - Metadata manager not available, using fallback method');
            // Fallback to old method
            $file_fields = $this->get_file_fields($post_id);
            
            foreach ($file_fields as $field_name => $file_url) {
                if (empty($file_url)) continue;
                
                $work = array(
                    'filename' => basename($file_url),
                    'file_url' => $file_url,
                    'title' => basename($file_url)
                );
                
                // Handle different file types
                if ($this->is_image_file($work['filename'])) {
                    $work['image_url'] = $file_url;
                    $work['type'] = 'image';
                } elseif ($this->is_video_file($work['filename'])) {
                    $work['type'] = 'video';
                } elseif ($this->is_document_file($work['filename'])) {
                    $work['type'] = 'document';
                } else {
                    $work['type'] = 'other';
                }
                
                // Try to get additional work metadata
                $work_title_key = str_replace('cf7_file_', 'cf7_title_', $field_name);
                $work_title = get_post_meta($post_id, $work_title_key, true);
                if (!empty($work_title)) {
                    $work['title'] = $work_title;
                }
                
                $work_desc_key = str_replace('cf7_file_', 'cf7_desc_', $field_name);
                $work_desc = get_post_meta($post_id, $work_desc_key, true);
                if (!empty($work_desc)) {
                    $work['description'] = $work_desc;
                }
                
                // Get work-specific ratings and comments
                $work['ratings'] = $this->get_work_ratings($post_id, $field_name);
                $work['comments'] = $this->get_work_comments($post_id, $field_name);
                
                $works[] = $work;
            }
        }
        
        error_log('CF7AS: PDF Export - Total works found: ' . count($works));
        return $works;
    }
    
    /**
     * Get submission ratings
     */
    private function get_submission_ratings($post_id) {
        error_log('CF7AS: PDF Export - get_submission_ratings() called for post: ' . $post_id);
        
        try {
            // Check if CF7 Artist Submissions Ratings class exists
            if (class_exists('CF7_Artist_Submissions_Ratings')) {
                error_log('CF7AS: PDF Export - CF7_Artist_Submissions_Ratings class found, using it');
                $ratings_class = new CF7_Artist_Submissions_Ratings();
                
                // Check if the method exists before calling it
                if (method_exists($ratings_class, 'get_submission_ratings')) {
                    error_log('CF7AS: PDF Export - get_submission_ratings method exists, calling it');
                    $ratings = $ratings_class->get_submission_ratings($post_id);
                    error_log('CF7AS: PDF Export - Ratings class returned: ' . print_r($ratings, true));
                    return $ratings;
                } elseif (method_exists('CF7_Artist_Submissions_Ratings', 'get_submission_ratings')) {
                    error_log('CF7AS: PDF Export - Using static method get_submission_ratings');
                    $ratings = CF7_Artist_Submissions_Ratings::get_submission_ratings($post_id);
                    error_log('CF7AS: PDF Export - Static ratings method returned: ' . print_r($ratings, true));
                    return $ratings;
                } else {
                    error_log('CF7AS: PDF Export - get_submission_ratings method does not exist in ratings class');
                }
            } else {
                error_log('CF7AS: PDF Export - CF7_Artist_Submissions_Ratings class not found, using fallback');
            }
            
            // Fallback: look for rating metadata
            error_log('CF7AS: PDF Export - Using fallback rating method');
            $ratings = array();
            
            // Common rating field patterns
            $rating_fields = array(
                'overall_rating' => 'Overall Rating',
                'technical_rating' => 'Technical Skill',
                'creativity_rating' => 'Creativity',
                'presentation_rating' => 'Presentation'
            );
            
            foreach ($rating_fields as $field => $label) {
                $rating_value = get_post_meta($post_id, 'cf7_' . $field, true);
                $rating_comment = get_post_meta($post_id, 'cf7_' . $field . '_comment', true);
                
                error_log('CF7AS: PDF Export - Checking field cf7_' . $field . ': value=' . var_export($rating_value, true));
                
                if (!empty($rating_value)) {
                    $rating_data = array(
                        'category' => $label,
                        'score' => floatval($rating_value),
                        'comment' => $rating_comment
                    );
                    $ratings[] = $rating_data;
                    error_log('CF7AS: PDF Export - Added rating: ' . print_r($rating_data, true));
                }
            }
            
            error_log('CF7AS: PDF Export - Fallback method returning ' . count($ratings) . ' ratings');
            return $ratings;
            
        } catch (Exception $e) {
            error_log('CF7AS: PDF Export - EXCEPTION in get_submission_ratings: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Exception trace: ' . $e->getTraceAsString());
            throw $e; // Re-throw to be caught by calling code
        } catch (Error $e) {
            error_log('CF7AS: PDF Export - FATAL ERROR in get_submission_ratings: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Error trace: ' . $e->getTraceAsString());
            throw new Exception('Fatal error in ratings processing: ' . $e->getMessage());
        }
    }
    
    /**
     * Get curator comments
     */
    private function get_curator_comments($post_id) {
        error_log('CF7AS: PDF Export - get_curator_comments() called for post: ' . $post_id);
        
        try {
            // Check if Conversations class exists for comments
            if (class_exists('CF7_Artist_Submissions_Conversations')) {
                error_log('CF7AS: PDF Export - CF7_Artist_Submissions_Conversations class found');
                
                // Check if the method exists before calling it
                if (method_exists('CF7_Artist_Submissions_Conversations', 'get_conversation_messages')) {
                    error_log('CF7AS: PDF Export - get_conversation_messages static method exists, calling it');
                    $raw_comments = CF7_Artist_Submissions_Conversations::get_conversation_messages($post_id);
                    error_log('CF7AS: PDF Export - Conversations class returned: ' . print_r($raw_comments, true));
                    
                    // Convert to clean array format for Lambda
                    $processed_comments = array();
                    if (is_array($raw_comments)) {
                        foreach ($raw_comments as $comment) {
                            // Handle both object and array formats
                            if (is_object($comment)) {
                                $clean_comment = array(
                                    'id' => isset($comment->id) ? (string)$comment->id : '',
                                    'from_name' => isset($comment->from_name) ? (string)$comment->from_name : (isset($comment->author_name) ? (string)$comment->author_name : 'Curator'),
                                    'from_email' => isset($comment->from_email) ? (string)$comment->from_email : (isset($comment->author_email) ? (string)$comment->author_email : ''),
                                    'subject' => isset($comment->subject) ? (string)$comment->subject : 'Curator Comment',
                                    'message' => isset($comment->message_body) ? strip_tags((string)$comment->message_body) : (isset($comment->message) ? strip_tags((string)$comment->message) : ''),
                                    'date' => isset($comment->created_at) ? (string)$comment->created_at : (isset($comment->date) ? (string)$comment->date : ''),
                                    'direction' => isset($comment->direction) ? (string)$comment->direction : 'outbound'
                                );
                            } else {
                                // Handle array format
                                $clean_comment = array(
                                    'id' => isset($comment['id']) ? (string)$comment['id'] : '',
                                    'from_name' => isset($comment['from_name']) ? (string)$comment['from_name'] : (isset($comment['author_name']) ? (string)$comment['author_name'] : 'Curator'),
                                    'from_email' => isset($comment['from_email']) ? (string)$comment['from_email'] : (isset($comment['author_email']) ? (string)$comment['author_email'] : ''),
                                    'subject' => isset($comment['subject']) ? (string)$comment['subject'] : 'Curator Comment',
                                    'message' => isset($comment['message_body']) ? strip_tags((string)$comment['message_body']) : (isset($comment['message']) ? strip_tags((string)$comment['message']) : ''),
                                    'date' => isset($comment['created_at']) ? (string)$comment['created_at'] : (isset($comment['date']) ? (string)$comment['date'] : ''),
                                    'direction' => isset($comment['direction']) ? (string)$comment['direction'] : 'outbound'
                                );
                            }
                            $processed_comments[] = $clean_comment;
                        }
                    }
                    
                    error_log('CF7AS: PDF Export - Processed comments count: ' . count($processed_comments));
                    return $processed_comments;
                } else {
                    error_log('CF7AS: PDF Export - get_conversation_messages method does not exist in conversations class');
                }
            } else {
                error_log('CF7AS: PDF Export - CF7_Artist_Submissions_Conversations class not found, using fallback');
            }
            
            // Fallback: look for comment metadata
            error_log('CF7AS: PDF Export - Using fallback comments method');
            $comments = array();
            
            $comment_fields = get_post_meta($post_id, 'cf7_curator_comments', false);
            error_log('CF7AS: PDF Export - Found comment fields: ' . print_r($comment_fields, true));
            
            if (!empty($comment_fields)) {
                foreach ($comment_fields as $comment) {
                    if (is_array($comment)) {
                        // Clean up array comment
                        $clean_comment = array(
                            'id' => isset($comment['id']) ? (string)$comment['id'] : uniqid(),
                            'from_name' => isset($comment['author']) ? (string)$comment['author'] : 'Curator',
                            'from_email' => isset($comment['email']) ? (string)$comment['email'] : '',
                            'subject' => isset($comment['subject']) ? (string)$comment['subject'] : 'Curator Comment',
                            'message' => isset($comment['content']) ? strip_tags((string)$comment['content']) : '',
                            'date' => isset($comment['date']) ? (string)$comment['date'] : current_time('mysql'),
                            'direction' => 'outbound'
                        );
                        $comments[] = $clean_comment;
                        error_log('CF7AS: PDF Export - Added array comment: ' . print_r($clean_comment, true));
                    } else {
                        // Clean up string comment
                        $clean_comment = array(
                            'id' => uniqid(),
                            'from_name' => 'Curator',
                            'from_email' => '',
                            'subject' => 'Curator Comment',
                            'message' => strip_tags((string)$comment),
                            'date' => current_time('mysql'),
                            'direction' => 'outbound'
                        );
                        $comments[] = $clean_comment;
                        error_log('CF7AS: PDF Export - Added string comment: ' . print_r($clean_comment, true));
                    }
                }
            }
            
            error_log('CF7AS: PDF Export - Fallback method returning ' . count($comments) . ' comments');
            return $comments;
            
        } catch (Exception $e) {
            error_log('CF7AS: PDF Export - EXCEPTION in get_curator_comments: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Exception trace: ' . $e->getTraceAsString());
            throw $e; // Re-throw to be caught by calling code
        } catch (Error $e) {
            error_log('CF7AS: PDF Export - FATAL ERROR in get_curator_comments: ' . $e->getMessage());
            error_log('CF7AS: PDF Export - Error trace: ' . $e->getTraceAsString());
            throw new Exception('Fatal error in comments processing: ' . $e->getMessage());
        }
    }
    
    /**
     * Get compressed/preview image URL for PDF export
     */
    private function get_compressed_image_url($file) {
        // For GIF files, always use original to preserve animation
        if (isset($file['mime_type']) && $file['mime_type'] === 'image/gif') {
            // Use original file URL for GIF files to preserve animation
            if ($this->s3_handler && method_exists($this->s3_handler, 'get_presigned_preview_url')) {
                return $this->s3_handler->get_presigned_preview_url($file['s3_key']);
            }
            return '';
        }
        
        // Try to use media converter to get compressed version for non-GIF images
        if (class_exists('CF7_Artist_Submissions_Media_Converter')) {
            $converter = new CF7_Artist_Submissions_Media_Converter();
            
            // Try to get preview or medium version (better for PDF)
            $preview_version = $converter->get_best_version_for_serving($file['s3_key'], 'preview');
            if (!$preview_version) {
                $preview_version = $converter->get_best_version_for_serving($file['s3_key'], 'medium');
            }
            if (!$preview_version) {
                $preview_version = $converter->get_best_version_for_serving($file['s3_key'], 'large');
            }
            
            if ($preview_version && !empty($preview_version->converted_s3_key)) {
                // Get S3 handler to generate presigned URL
                if ($this->s3_handler && method_exists($this->s3_handler, 'get_presigned_preview_url')) {
                    return $this->s3_handler->get_presigned_preview_url($preview_version->converted_s3_key);
                }
            }
        }
        
        // Fallback to original file URL
        return '';
    }
    
    /**
     * Get work-specific ratings using safe filename and modern ratings system
     */
    private function get_work_ratings($post_id, $safe_filename) {
        error_log('CF7AS: PDF Export - get_work_ratings called for post: ' . $post_id . ', filename: ' . $safe_filename);
        
        $ratings = array();
        
        // First try to get ratings from the modern ratings system if we can find the file ID
        if (class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
            $metadata_manager = new CF7_Artist_Submissions_Metadata_Manager();
            $files = $metadata_manager->get_submission_files($post_id);
            
            foreach ($files as $file) {
                $filename = $file['original_name'] ?? basename($file['s3_key']);
                $file_safe_filename = sanitize_key($filename);
                
                if ($file_safe_filename === $safe_filename && isset($file['id'])) {
                    $file_id = intval($file['id']);
                    
                    if (class_exists('CF7_Artist_Submissions_Ratings')) {
                        error_log('CF7AS: PDF Export - Getting ratings for file ID: ' . $file_id);
                        
                        // Get all ratings for this work
                        $all_ratings = CF7_Artist_Submissions_Ratings::get_submission_ratings($post_id);
                        
                        if (isset($all_ratings[$file_id])) {
                            $rating_data = $all_ratings[$file_id];
                            if ($rating_data['rating'] > 0) {
                                $ratings[] = array(
                                    'category' => 'Overall Rating',
                                    'score' => $rating_data['rating'],
                                    'comment' => $rating_data['comments']
                                );
                                error_log('CF7AS: PDF Export - Found rating: ' . $rating_data['rating'] . ' for file: ' . $filename);
                            }
                        }
                    }
                    break;
                }
            }
        }
        
        // Fallback to legacy meta field patterns if no modern ratings found
        if (empty($ratings)) {
            error_log('CF7AS: PDF Export - No modern ratings found, trying legacy meta fields');
            
            // Look for work-specific rating fields using the safe filename
            $rating_categories = array(
                'overall' => 'Overall Rating',
                'technical' => 'Technical Skill', 
                'creativity' => 'Creativity',
                'presentation' => 'Presentation'
            );
            
            foreach ($rating_categories as $category_key => $category_label) {
                // Try multiple patterns for work ratings
                $rating_patterns = array(
                    'cf7_work_' . $safe_filename . '_' . $category_key . '_rating',
                    'cf7_' . $safe_filename . '_' . $category_key . '_rating',
                    'cf7_work_rating_' . $safe_filename . '_' . $category_key,
                    'cf7_rating_' . $safe_filename . '_' . $category_key
                );
                
                $comment_patterns = array(
                    'cf7_work_' . $safe_filename . '_' . $category_key . '_comment',
                    'cf7_' . $safe_filename . '_' . $category_key . '_comment',
                    'cf7_work_comment_' . $safe_filename . '_' . $category_key,
                    'cf7_comment_' . $safe_filename . '_' . $category_key
                );
                
                $rating_value = null;
                $rating_comment = '';
                
                // Try to find rating value
                foreach ($rating_patterns as $pattern) {
                    $value = get_post_meta($post_id, $pattern, true);
                    if (!empty($value)) {
                        $rating_value = floatval($value);
                        error_log('CF7AS: PDF Export - Found legacy rating: ' . $rating_value . ' for pattern: ' . $pattern);
                        break;
                    }
                }
                
                // Try to find rating comment
                foreach ($comment_patterns as $pattern) {
                    $comment = get_post_meta($post_id, $pattern, true);
                    if (!empty($comment)) {
                        $rating_comment = $comment;
                        break;
                    }
                }
                
                if ($rating_value !== null) {
                    $ratings[] = array(
                        'category' => $category_label,
                        'score' => $rating_value,
                        'comment' => $rating_comment
                    );
                }
            }
        }
        
        error_log('CF7AS: PDF Export - Found ' . count($ratings) . ' ratings for work: ' . $safe_filename);
        return $ratings;
    }
    
    /**
     * Get work-specific comments using safe filename and modern ratings system
     */
    private function get_work_comments($post_id, $safe_filename) {
        error_log('CF7AS: PDF Export - get_work_comments called for post: ' . $post_id . ', filename: ' . $safe_filename);
        
        $comments = array();
        
        // First try to get comments from the modern ratings system if we can find the file ID
        if (class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
            $metadata_manager = new CF7_Artist_Submissions_Metadata_Manager();
            $files = $metadata_manager->get_submission_files($post_id);
            
            foreach ($files as $file) {
                $filename = $file['original_name'] ?? basename($file['s3_key']);
                $file_safe_filename = sanitize_key($filename);
                
                if ($file_safe_filename === $safe_filename && isset($file['id'])) {
                    $file_id = intval($file['id']);
                    
                    if (class_exists('CF7_Artist_Submissions_Ratings')) {
                        error_log('CF7AS: PDF Export - Getting comments for file ID: ' . $file_id);
                        
                        // Get all ratings for this work (which includes comments)
                        $all_ratings = CF7_Artist_Submissions_Ratings::get_submission_ratings($post_id);
                        
                        if (isset($all_ratings[$file_id])) {
                            $rating_data = $all_ratings[$file_id];
                            if (!empty($rating_data['comments'])) {
                                $comments[] = array(
                                    'author' => 'Curator',
                                    'content' => $rating_data['comments'],
                                    'date' => $rating_data['updated_at'] ?: $rating_data['created_at']
                                );
                                error_log('CF7AS: PDF Export - Found comment: ' . substr($rating_data['comments'], 0, 50) . '... for file: ' . $filename);
                            }
                        }
                    }
                    break;
                }
            }
        }
        
        // Fallback to legacy meta field patterns if no modern comments found
        if (empty($comments)) {
            error_log('CF7AS: PDF Export - No modern comments found, trying legacy meta fields');
            
            // Look for work-specific comment fields using multiple patterns
            $comment_patterns = array(
                'cf7_work_' . $safe_filename . '_comments',
                'cf7_' . $safe_filename . '_comments',
                'cf7_work_comment_' . $safe_filename,
                'cf7_comment_' . $safe_filename,
                'cf7_curator_comment_' . $safe_filename,
                'cf7_work_curator_comment_' . $safe_filename
            );
            
            foreach ($comment_patterns as $pattern) {
                $work_comments = get_post_meta($post_id, $pattern, false);
                
                if (!empty($work_comments)) {
                    foreach ($work_comments as $comment) {
                        if (is_array($comment)) {
                            $comments[] = array(
                                'author' => isset($comment['author']) ? $comment['author'] : 'Curator',
                                'content' => isset($comment['content']) ? strip_tags($comment['content']) : '',
                                'date' => isset($comment['date']) ? $comment['date'] : current_time('mysql')
                            );
                        } else {
                            $comments[] = array(
                                'author' => 'Curator',
                                'content' => strip_tags((string)$comment),
                                'date' => current_time('mysql')
                            );
                        }
                        error_log('CF7AS: PDF Export - Found legacy comment via pattern: ' . $pattern);
                    }
                    break; // Found comments, stop looking
                }
            }
        }
        
        error_log('CF7AS: PDF Export - Found ' . count($comments) . ' comments for work: ' . $safe_filename);
        return $comments;
    }

    /**
     * Get site information for PDF header
     */
    private function get_site_info() {
        return array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'logo_url' => $this->get_site_logo_url()
        );
    }
    
    /**
     * Cleanup old PDF files from S3 and database
     */
    public function cleanup_old_pdfs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_pdf_jobs';
        
        // Delete PDFs older than 7 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $old_jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE created_at < %s AND status = 'completed'",
                $cutoff_date
            )
        );
        
        foreach ($old_jobs as $job) {
            // Delete from S3 if we have a key
            if (!empty($job->pdf_s3_key) && $this->s3_handler) {
                // Delete from S3 (implement this method in S3 handler if needed)
                // $this->s3_handler->delete_file($job->pdf_s3_key);
            }
            
            // Delete from database
            $wpdb->delete($table_name, array('id' => $job->id), array('%d'));
        }
        
        error_log("CF7AS PDF Export: Cleaned up " . count($old_jobs) . " old PDF jobs");
    }
    
    // ============================================================================
    // LEGACY HTML GENERATION SECTION (FALLBACK)
    // ============================================================================
    
    /**
     * Generate legacy PDF content for fallback HTML generation
     */
    private function generate_legacy_pdf_content($post_id, $options) {
        $post = get_post($post_id);
        $artist_name = $this->get_artist_name($post_id);
        $submission_date = get_post_meta($post_id, 'cf7_submission_date', true);
        
        $html = '';
        
        // Debug: Show options being used (when debug mode is on)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $html .= '<div style="background: #e0f7ff; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; font-family: monospace; font-size: 12px;">';
            $html .= '<strong>🔧 Debug - Export Options:</strong><br>';
            $html .= 'Include Personal Info: ' . ($options['include_personal_info'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Works: ' . ($options['include_works'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Ratings: ' . ($options['include_ratings'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Curator Notes: ' . ($options['include_curator_notes'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Curator Comments: ' . ($options['include_curator_comments'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Confidential Watermark: ' . ($options['confidential_watermark'] ? 'YES' : 'NO') . '<br>';
            $html .= '</div>';
        }
        
        // Header section
        $html .= '<div class="submission-header">';
        $html .= '<h1 class="submission-title">' . esc_html($post->post_title) . '</h1>';
        if ($submission_date) {
            $html .= '<p class="submission-date">Submitted: ' . esc_html(date('F j, Y', strtotime($submission_date))) . '</p>';
        }
        $html .= '</div>';
        
        // Personal Information Section
        if ($options['include_personal_info']) {
            $html .= '<h2>Artist Information</h2>';
            $html .= '<table class="info-table">';
            
            $meta_keys = get_post_custom_keys($post_id);
            if ($meta_keys) {
                foreach ($meta_keys as $key) {
                    // Skip internal meta and file fields
                    if (substr($key, 0, 1) === '_' || 
                        substr($key, 0, 8) === 'cf7_file_' || 
                        $key === 'cf7_submission_date' || 
                        $key === 'cf7_curator_notes' ||
                        strpos($key, 'work') !== false ||
                        strpos($key, 'files') !== false) {
                        continue;
                    }
                    
                    $value = get_post_meta($post_id, $key, true);
                    if (empty($value) || is_array($value)) {
                        continue;
                    }
                    
                    // Format label
                    $label = ucwords(str_replace(array('cf7_', '_', '-'), ' ', $key));
                    
                    // Format value (handle URLs, emails, etc.)
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $formatted_value = '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                    } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                        $formatted_value = '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
                    } else {
                        $formatted_value = esc_html($value);
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($label) . '</td>';
                    $html .= '<td>' . $formatted_value . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</table>';
        }
        
        // Submitted Works Section
        if ($options['include_works']) {
            $html .= '<h2>Submitted Works</h2>';
            $html .= '<div class="works-grid">';
            
            $file_fields = $this->get_file_fields($post_id);
            
            if (!empty($file_fields)) {
                foreach ($file_fields as $field_name => $file_url) {
                    $html .= $this->format_work_item($file_url, $field_name);
                }
            } else {
                $html .= '<p>No submitted works found.</p>';
            }
            
            $html .= '</div>';
        }
        
        // Ratings Section
        if ($options['include_ratings']) {
            $ratings = $this->get_submission_ratings($post_id);
            if (!empty($ratings)) {
                $html .= '<h2>Ratings & Evaluation</h2>';
                foreach ($ratings as $rating) {
                    $html .= '<div class="rating-section">';
                    $html .= '<h3>' . esc_html($rating['category']) . '</h3>';
                    $html .= '<div class="rating-score">Score: ' . esc_html($rating['score']) . '/5</div>';
                    if (!empty($rating['comment'])) {
                        $html .= '<div class="rating-comment">' . esc_html($rating['comment']) . '</div>';
                    }
                    $html .= '</div>';
                }
            }
        }
        
        // Curator Notes Section
        if ($options['include_curator_notes']) {
            $notes = get_post_meta($post_id, 'cf7_curator_notes', true);
            if (!empty($notes)) {
                $html .= '<div class="notes-section">';
                $html .= '<h2>Curator Notes</h2>';
                $html .= '<p>' . nl2br(esc_html($notes)) . '</p>';
                $html .= '</div>';
            }
        }
        
        // Curator Comments Section
        if ($options['include_curator_comments']) {
            $comments = $this->get_curator_comments($post_id);
            if (!empty($comments)) {
                $html .= '<h2>Curator Comments</h2>';
                foreach ($comments as $comment) {
                    $html .= '<div class="comment-section">';
                    if (is_array($comment)) {
                        $html .= '<div><strong>By:</strong> ' . esc_html($comment['author'] ?? 'Curator') . '</div>';
                        if (!empty($comment['date'])) {
                            $html .= '<div><strong>Date:</strong> ' . esc_html(date('F j, Y', strtotime($comment['date']))) . '</div>';
                        }
                        $html .= '<div>' . nl2br(esc_html($comment['content'] ?? $comment)) . '</div>';
                    } else {
                        $html .= '<div>' . nl2br(esc_html($comment)) . '</div>';
                    }
                    $html .= '</div>';
                }
            }
        }
        
        return $html;
    }
    
    /**
     * Wrap generated content in complete HTML document structure.
     * Creates full HTML document with styling, headers, and print optimization.
     */
    private function wrap_html_document($content, $post_id, $options) {
        $artist_name = $this->get_artist_name($post_id);
        $site_name = get_bloginfo('name');
        $logo_url = $this->get_site_logo_url();
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Submission - ' . esc_html($artist_name) . '</title>
    <style>
        @media print {
            body { margin: 0; font-size: 12px; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .header-logo { max-height: 60px; }
            
            /* Force backgrounds to print */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Ensure header backgrounds print */
            .header-text-section {
                background: #667eea !important;
                background-image: none !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Fallback solid background for better print compatibility */
            .header-text-section::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: #667eea;
                z-index: -1;
            }
        }
        
        @media screen {
            body { margin: 20px; background: #f5f5f5; }
            .document-container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: white; 
                padding: 40px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .print-instructions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            text-align: center;
        }
        
        .print-instructions h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .print-instructions ol {
            text-align: left;
            display: inline-block;
            margin: 15px 0;
        }
        
        .print-instructions li {
            margin-bottom: 8px;
        }
        
        .document-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header-logo-section {
            background: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .header-text-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px 20px 20px;
            color: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .header-logo {
            max-height: 80px;
            margin-bottom: 0;
        }
        
        .document-title {
            font-size: 28px;
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .document-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin: 5px 0 0 0;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            font-weight: 400;
        }
        
        h2 {
            color: #34495e;
            font-size: 18px;
            margin: 25px 0 15px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 12px 16px;
            border-left: 4px solid #3498db;
            border-radius: 0 4px 4px 0;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: white;
        }
        
        .info-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        .info-table td:first-child {
            font-weight: 600;
            width: 30%;
            background: #f8f9fa;
            color: #495057;
        }
        
        .info-table td:last-child {
            background: white;
        }
        
        .info-table tr:hover {
            background: #f1f3f4;
        }
        
        .works-grid {
            margin-top: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
        
        .work-item {
            margin-bottom: 0;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            break-inside: avoid;
            page-break-inside: avoid;
            overflow: hidden;
        }
        
        .work-title {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .work-image {
            max-width: 100%;
            max-height: 250px;
            width: 100%;
            height: auto;
            margin: 15px 0;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            object-fit: cover;
        }
        
        .notes-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-left: 4px solid #f39c12;
            margin-top: 25px;
            border-radius: 0 4px 4px 0;
        }
        
        .notes-section h2 {
            background: none;
            margin-top: 0;
            padding: 0;
            border: none;
            color: #d68910;
        }
        
        .confidential-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
            font-weight: 600;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.1);
            z-index: -1;
            pointer-events: none;
        }
        
        a {
            color: #3498db;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Fallback for browsers without CSS Grid support */
        @supports not (display: grid) {
            .works-grid {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            
            .work-item {
                width: calc(50% - 10px);
                margin-bottom: 20px;
            }
        }
        
        /* Print-specific styles */
        @media print {
            .works-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                page-break-inside: avoid;
            }
            
            .work-item {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 0;
            }
            
            .work-image {
                max-height: 200px;
                page-break-inside: avoid;
            }
        }
        
        /* Responsive for smaller screens */
        @media screen and (max-width: 768px) {
            .works-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>';

        if ($options['confidential_watermark']) {
            $html .= '<div class="watermark">PRIVATE & CONFIDENTIAL</div>';
        }

        $html .= '<div class="document-container">
            <div class="print-instructions no-print">
                <h3>📄 Print to PDF Instructions</h3>
                <p>To save this document as a professional PDF:</p>
                <ol>
                    <li>Press <strong>Ctrl+P</strong> (or <strong>⌘+P</strong> on Mac)</li>
                    <li>Select <strong>"Save as PDF"</strong> or <strong>"Microsoft Print to PDF"</strong></li>
                    <li>Choose <strong>"More settings"</strong> → Set margins to <strong>"Minimum"</strong></li>
                    <li>Click <strong>"Save"</strong> and choose your location</li>
                </ol>
                <p><em>💡 This instruction box will not appear in your PDF</em></p>
            </div>
            
            <div class="document-header">
                <div class="header-logo-section">';
            
        if ($logo_url) {
            $html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" class="header-logo">';
        }
        
        $html .= '</div>
                <div class="header-text-section">
                    <h1 class="document-title">Artist Submission Profile</h1>
                    <p class="document-subtitle">Generated from ' . esc_html($site_name) . '</p>
                </div>
            </div>
            
            ' . $content . '';
            
        if ($options['confidential_watermark']) {
            $html .= '<div class="confidential-footer">
                <strong>PRIVATE AND CONFIDENTIAL</strong><br>
                Generated on ' . date('F j, Y \a\t g:i A') . '
            </div>';
        }
        
        $html .= '</div>

<script>
// Auto-open print dialog after a short delay
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        if (window.location.search.indexOf("autoprint=1") !== -1) {
            window.print();
        }
    }, 1000);
});
</script>

</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate structured HTML content for PDF document sections.
     * Creates formatted content sections with debug information and data display.
     */
    private function generate_pdf_content($post_id, $options) {
        $post = get_post($post_id);
        $artist_name = $this->get_artist_name($post_id);
        $submission_date = get_post_meta($post_id, 'cf7_submission_date', true);
        
        $html = '';
        
        // Debug: Show options being used (when debug mode is on)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $html .= '<div style="background: #e0f7ff; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; font-family: monospace; font-size: 12px;">';
            $html .= '<strong>🔧 Debug - Export Options:</strong><br>';
            $html .= 'Include Personal Info: ' . ($options['include_personal_info'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Works: ' . ($options['include_works'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Include Curator Notes: ' . ($options['include_notes'] ? 'YES' : 'NO') . '<br>';
            $html .= 'Confidential Watermark: ' . ($options['confidential_watermark'] ? 'YES' : 'NO') . '<br>';
            $html .= '</div>';
        }
        
        // Header section
        $html .= '<div class="submission-header">';
        $html .= '<h1 class="submission-title">' . esc_html($post->post_title) . '</h1>';
        if ($submission_date) {
            $html .= '<p class="submission-date">Submitted: ' . esc_html(date('F j, Y', strtotime($submission_date))) . '</p>';
        }
        $html .= '</div>';
        
        // Personal Information Section
        if ($options['include_personal_info']) {
            $html .= '<h2>Artist Information</h2>';
            $html .= '<table class="info-table">';
            
            $meta_keys = get_post_custom_keys($post_id);
            if ($meta_keys) {
                foreach ($meta_keys as $key) {
                    // Skip internal meta and file fields
                    if (substr($key, 0, 1) === '_' || 
                        substr($key, 0, 8) === 'cf7_file_' || 
                        $key === 'cf7_submission_date' || 
                        $key === 'cf7_curator_notes' ||
                        strpos($key, 'work') !== false ||
                        strpos($key, 'files') !== false) {
                        continue;
                    }
                    
                    $value = get_post_meta($post_id, $key, true);
                    if (empty($value) || is_array($value)) {
                        continue;
                    }
                    
                    // Format label
                    $label = ucwords(str_replace(array('cf7_', '_', '-'), ' ', $key));
                    
                    // Format value (handle URLs, emails, etc.)
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $formatted_value = '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                    } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                        $formatted_value = '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
                    } else {
                        $formatted_value = esc_html($value);
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($label) . '</td>';
                    $html .= '<td>' . $formatted_value . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</table>';
        }
        
        // Submitted Works Section
        if ($options['include_works']) {
            $html .= '<h2>Submitted Works</h2>';
            $html .= '<div class="works-grid">';
            
            $file_fields = $this->get_file_fields($post_id);
            
            // Debug: Show what we found
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $html .= '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
                $html .= '<strong>Debug - File fields found:</strong><br>';
                $html .= '<pre>' . print_r($file_fields, true) . '</pre>';
                $html .= '</div>';
            }
            
            if (!empty($file_fields)) {
                foreach ($file_fields as $field_name => $file_url) {
                    $html .= $this->format_work_item($file_url, $field_name);
                }
            } else {
                $html .= '<p>No submitted works found.</p>';
                
                // Debug: Show all meta fields if no files found
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $all_meta = get_post_meta($post_id);
                    $html .= '<div style="background: #ffe0e0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
                    $html .= '<strong>Debug - All meta fields:</strong><br>';
                    foreach ($all_meta as $key => $value) {
                        if (strpos($key, 'work') !== false || strpos($key, 'file') !== false || substr($key, 0, 4) === 'cf7_') {
                            $html .= esc_html($key) . ': ' . esc_html(print_r($value, true)) . '<br>';
                        }
                    }
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        // Curator Notes Section
        if ($options['include_notes']) {
            $notes = get_post_meta($post_id, 'cf7_curator_notes', true);
            if (!empty($notes)) {
                $html .= '<div class="notes-section">';
                $html .= '<h2>Curator Notes</h2>';
                $html .= '<p>' . nl2br(esc_html($notes)) . '</p>';
                $html .= '</div>';
            }
        }
        
        return $html;
    }
    
    /**
     * Extract file fields from submission metadata with multiple approach scanning.
     * Searches various metadata patterns to locate uploaded files and URLs.
     */
    private function get_file_fields($post_id) {
        error_log('CF7AS: PDF Export - Getting file fields for post: ' . $post_id);
        
        // Start with an empty array of file URLs to display
        $file_urls = array();
        
        // APPROACH 1: Check for CF7AS uploader data (most common)
        $meta_keys = get_post_custom_keys($post_id);
        if (!empty($meta_keys)) {
            foreach ($meta_keys as $key) {
                // Check for CF7AS uploader data
                if (strpos($key, 'cf7as-uploader-') === 0 && strpos($key, '_data') !== false) {
                    error_log('CF7AS: PDF Export - Found uploader data key: ' . $key);
                    $uploader_data = get_post_meta($post_id, $key, true);
                    error_log('CF7AS: PDF Export - Uploader data: ' . print_r($uploader_data, true));
                    
                    if (!empty($uploader_data) && is_array($uploader_data)) {
                        foreach ($uploader_data as $file_info) {
                            if (isset($file_info['url']) && !empty($file_info['url'])) {
                                $file_key = 'cf7_file_' . sanitize_key($file_info['filename'] ?? 'upload_' . count($file_urls));
                                $file_urls[$file_key] = $file_info['url'];
                                error_log('CF7AS: PDF Export - Added file: ' . $file_key . ' -> ' . $file_info['url']);
                            }
                        }
                    }
                }
            }
        }
        
        // APPROACH 2: Check for standard file format (both hyphen and underscore variants)
        if (empty($file_urls)) {
            error_log('CF7AS: PDF Export - No uploader data found, trying standard file fields');
            
            // Try both hyphen and underscore variants
            $standard_file_keys = array('cf7_file_your-work', 'cf7_file_your_work');
            foreach ($standard_file_keys as $file_key) {
                $standard_files = get_post_meta($post_id, $file_key, true);
                if (!empty($standard_files)) {
                    if (is_array($standard_files)) {
                        foreach ($standard_files as $index => $url) {
                            $file_urls['cf7_file_work_' . ($index + 1)] = $url;
                        }
                    } else {
                        $file_urls['cf7_file_your_work'] = $standard_files;
                    }
                    error_log('CF7AS: PDF Export - Found standard files in ' . $file_key . ': ' . count($file_urls));
                    break; // Found files, stop looking
                }
            }
        }
        
        // APPROACH 3: If no files found, check for comma-separated URLs (both variants)
        if (empty($file_urls)) {
            error_log('CF7AS: PDF Export - Trying comma-separated URLs');
            
            $url_field_keys = array('cf7_your-work', 'cf7_your_work');
            foreach ($url_field_keys as $url_key) {
                $comma_separated_urls = get_post_meta($post_id, $url_key, true);
                if (!empty($comma_separated_urls)) {
                    // Split by commas
                    $urls = array_map('trim', explode(',', $comma_separated_urls));
                    foreach ($urls as $index => $url) {
                        if (!empty($url)) {
                            $file_urls['cf7_file_work_' . ($index + 1)] = $url;
                        }
                    }
                    error_log('CF7AS: PDF Export - Found comma-separated files in ' . $url_key . ': ' . count($file_urls));
                    break; // Found files, stop looking
                }
            }
        }
        
        // APPROACH 4: Check for any other file fields with cf7_file_ prefix
        if (empty($file_urls)) {
            error_log('CF7AS: PDF Export - Trying cf7_file_ prefixed fields');
            if (!empty($meta_keys)) {
                foreach ($meta_keys as $key) {
                    if (substr($key, 0, 8) === 'cf7_file_') {
                        $file_data = get_post_meta($post_id, $key, true);
                        
                        if (!empty($file_data)) {
                            // Handle both string and array values
                            if (is_array($file_data)) {
                                foreach ($file_data as $index => $url) {
                                    if (!empty($url)) {
                                        $file_urls[$key . '_' . $index] = $url;
                                    }
                                }
                            } else {
                                $file_urls[$key] = $file_data;
                            }
                        }
                    }
                }
                error_log('CF7AS: PDF Export - Found cf7_file_ prefixed files: ' . count($file_urls));
            }
        }
        
        error_log('CF7AS: PDF Export - Total files found: ' . count($file_urls));
        error_log('CF7AS: PDF Export - File fields: ' . print_r($file_urls, true));
        
        return $file_urls;
    }
    
    /**
     * Format individual work item HTML for PDF display.
     * Creates formatted work sections with images, titles, and file links.
     */
    private function format_work_item($file_url, $field_name) {
        $html = '<div class="work-item">';
        
        // Format field name as title
        $title = ucwords(str_replace(array('cf7_file_', 'cf7_', '_', '-'), ' ', $field_name));
        $html .= '<div class="work-title">' . esc_html($title) . '</div>';
        
        // Skip if URL is empty or invalid
        if (empty($file_url)) {
            $html .= '<p>No file submitted.</p>';
            $html .= '</div>';
            return $html;
        }
        
        $filename = basename($file_url);
        $html .= '<p><strong>File:</strong> ' . esc_html($filename) . '</p>';
        
        // Add image preview for image files
        if ($this->is_image_file($filename)) {
            $html .= '<img src="' . esc_url($file_url) . '" class="work-image" alt="' . esc_attr($filename) . '" />';
        } else {
            // For non-image files, show a link
            $html .= '<p><a href="' . esc_url($file_url) . '" target="_blank">View File</a></p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Check if filename represents an image file type.
     * Validates file extension against supported image formats.
     */
    private function is_image_file($filename) {
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $image_extensions);
    }
    
    /**
     * Check if filename represents a video file type.
     */
    private function is_video_file($filename) {
        $video_extensions = array('mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $video_extensions);
    }
    
    /**
     * Check if filename represents a document file type.
     */
    private function is_document_file($filename) {
        $document_extensions = array('pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'pages', 'xls', 'xlsx', 'ppt', 'pptx');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $document_extensions);
    }
    
    /**
     * Get download URL for a file from S3 or generate presigned URL
     */
    private function get_file_download_url($file) {
        // If the file already has a URL, use it
        if (!empty($file['url'])) {
            return $file['url'];
        }
        
        // If we have an S3 key, generate a presigned URL
        if (!empty($file['s3_key']) && $this->s3_handler) {
            try {
                return $this->s3_handler->get_presigned_access_url($file['s3_key'], 3600); // 1 hour expiry
            } catch (Exception $e) {
                error_log('CF7AS: PDF Export - Error generating presigned URL: ' . $e->getMessage());
            }
        }
        
        // Fallback: construct a basic S3 URL (may not work without proper auth)
        if (!empty($file['s3_key'])) {
            $plugin_options = get_option('cf7_artist_submissions_options', array());
            $s3_bucket = isset($plugin_options['s3_bucket']) ? $plugin_options['s3_bucket'] : '';
            $aws_region = isset($plugin_options['aws_region']) ? $plugin_options['aws_region'] : 'us-east-1';
            
            if (!empty($s3_bucket)) {
                return "https://{$s3_bucket}.s3.{$aws_region}.amazonaws.com/{$file['s3_key']}";
            }
        }
        
        return '';
    }
    
    /**
     * Extract artist name from submission metadata fields.
     * Searches multiple field patterns with post title fallback.
     */
    private function get_artist_name($post_id) {
        // Try common field names for artist name (both hyphen and underscore variants)
        $name_fields = array('cf7_artist-name', 'cf7_artist_name', 'cf7_your-name', 'cf7_your_name', 'cf7_name');
        
        foreach ($name_fields as $field) {
            $name = get_post_meta($post_id, $field, true);
            if (!empty($name)) {
                return $name;
            }
        }
        
        // Fallback to post title
        return get_the_title($post_id);
    }
    
    /**
     * Retrieve site logo URL for PDF header branding.
     * Extracts custom logo from theme settings with fallback handling.
     */
    private function get_site_logo_url() {
        // Try to get custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'medium');
            if ($logo_url) {
                return $logo_url;
            }
        }
        
        return false;
    }
    
    /**
     * Retrieve site logo file path for PDF header integration.
     * Converts logo URL to local file path for PDF processing compatibility.
     */
    private function get_site_logo_path() {
        // Try to get custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                // Convert URL to local path for TCPDF
                $upload_dir = wp_upload_dir();
                $logo_path = str_replace($upload_dir['url'], $upload_dir['path'], $logo_url);
                if (file_exists($logo_path)) {
                    return $logo_path;
                }
            }
        }
        
        return false;
    }
}
