<?php
/**
 * CF7 Artist Submissions - PHP-Based Media Conversion System
 *
 * Comprehensive media conversion system using PHP image processing libraries and 
 * JavaScript client-side optimizations to automatically convert uploaded files into 
 * web-efficient formats while preserving originals. Designed for shared hosting.
 *
 * Features:
 * • PHP-based image processing using GD/Imagick libraries
 * • Client-side image compression and resizing with JavaScript
 * • Automatic format conversion (images to WebP when supported)
 * • Multiple size variants for responsive delivery
 * • Original file preservation for downloads
 * • Background processing with WordPress cron
 * • Batch processing for existing files
 * • Progress tracking and status monitoring
 * • Fallback support for different hosting environments
 *
 * @package CF7_Artist_Submissions
 * @subpackage MediaConversion
 * @since 1.2.0
 * @version 1.2.0
 */

/**
 * Media Converter Class
 * 
 * Manages PHP-based media conversion for automated file optimization
 * into web-efficient formats while preserving original files for downloads.
 * 
 * @since 1.2.0
 */
class CF7_Artist_Submissions_Media_Converter {
    
    private static $instance = null;
    
    private $s3_handler;
    private $aws_region;
    private $mediaconvert_endpoint;
    private $lambda_function_name;
    private $conversion_presets;
    private $max_execution_time;
    private $memory_limit;
    private $image_library;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize media converter
     */
    public function __construct() {
        error_log('CF7AS Media Converter: Constructor started');
        
        $this->s3_handler = new CF7_Artist_Submissions_S3_Handler();
        $init_result = $this->s3_handler->init(); // Initialize S3 handler with settings
        
        error_log('CF7AS Media Converter: S3 Handler initialized. Result: ' . ($init_result ? 'success' : 'failed'));
        
        // Only initialize MediaConvert if conversion is enabled
        if ($this->is_conversion_enabled()) {
            error_log('CF7AS Media Converter: Media conversion is enabled, setting up AWS configuration');
            // Set up AWS MediaConvert and Lambda configuration
            $this->setup_aws_configuration();
            
            // Set up conversion presets
            $this->setup_conversion_presets();
            
            // Initialize system limits and detect image library
            $this->init_system_limits();
        } else {
            error_log('CF7AS Media Converter: Media conversion is DISABLED in settings');
        }
        
        // Hook into file upload process
        add_action('cf7as_file_uploaded', array($this, 'trigger_conversion'), 10, 2);
        
        // Log when we receive the file upload trigger
        add_action('cf7as_file_uploaded', function($s3_key, $file_metadata) {
        }, 5, 2);
        
        
        // Create database tables if needed
        add_action('admin_init', array($this, 'create_tables'));
        
        // Add AJAX handlers for settings
        add_action('wp_ajax_cf7as_test_media_conversion', array($this, 'test_media_conversion'));
        add_action('wp_ajax_cf7as_test_lambda_connection', array($this, 'ajax_test_lambda_connection'));
        add_action('wp_ajax_cf7as_process_existing_files', array($this, 'ajax_process_existing_files'));
        add_action('wp_ajax_cf7as_get_conversion_status', array($this, 'ajax_get_conversion_status'));
        add_action('wp_ajax_cf7as_reset_file_status', array($this, 'ajax_reset_file_status'));
        add_action('wp_ajax_cf7as_check_conversion_jobs', array($this, 'ajax_check_conversion_jobs'));
        
        // Set up cron job for checking conversion status
        add_action('cf7as_check_conversion_jobs', array($this, 'process_conversion_queue'));
        
        // Schedule cron job if not already scheduled
        if (!wp_next_scheduled('cf7as_check_conversion_jobs')) {
            $scheduled = wp_schedule_event(time(), 'cf7as_every_minute', 'cf7as_check_conversion_jobs');
            error_log('CF7AS Media Converter: Scheduled new cron job (every minute). Result: ' . ($scheduled ? 'success' : 'failed'));
        } else {
            $next_run = wp_next_scheduled('cf7as_check_conversion_jobs');
            error_log('CF7AS Media Converter: Cron job already scheduled. Next run: ' . date('Y-m-d H:i:s', $next_run));
        }
        
        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Add a test to verify the constructor is running
        error_log('CF7AS Media Converter: Constructor completed - conversion enabled: ' . ($this->is_conversion_enabled() ? 'yes' : 'no'));
    }
    
    /**
     * Create necessary database tables
     */
    public function create_tables() {
        $this->create_conversion_jobs_table();
        $this->create_converted_files_table();
    }
    
    /**
     * Check if conversion is enabled in settings
     */
    private function is_conversion_enabled() {
        $options = get_option('cf7_artist_submissions_options', array());
        $enabled = isset($options['enable_media_conversion']) && $options['enable_media_conversion'] === 'on';
        return $enabled;
    }
    
    /**
     * Set up AWS service configuration
     */
    private function setup_aws_configuration() {
        $options = get_option('cf7_artist_submissions_options', array());
        
        $this->aws_region = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
        $this->mediaconvert_endpoint = isset($options['mediaconvert_endpoint']) ? $options['mediaconvert_endpoint'] : '';
        $this->lambda_function_name = isset($options['lambda_function_name']) ? $options['lambda_function_name'] : 'cf7as-image-converter';
        
        // If MediaConvert endpoint is empty, construct the regional endpoint (no longer needs account-specific endpoints)
        if (empty($this->mediaconvert_endpoint)) {
            $this->mediaconvert_endpoint = "https://mediaconvert.{$this->aws_region}.amazonaws.com";
        }
        
        // Ensure endpoint doesn't have trailing slash
        $this->mediaconvert_endpoint = rtrim($this->mediaconvert_endpoint, '/');
        
        // Only log if in admin area or during file uploads to reduce log spam
        if (is_admin() || wp_doing_ajax()) {
            error_log('CF7AS Media Converter: AWS Region = ' . $this->aws_region);
            error_log('CF7AS Media Converter: MediaConvert Endpoint = ' . $this->mediaconvert_endpoint);
            error_log('CF7AS Media Converter: Lambda Function = ' . $this->lambda_function_name);
        }
    }
    
    /**
     * Set up conversion presets for AWS services
     */
    private function setup_conversion_presets() {
        $this->conversion_presets = array(
            // Image presets (handled by Lambda + Sharp/ImageMagick)
            'thumbnail' => array(
                'type' => 'image',
                'service' => 'lambda',
                'width' => 300,
                'height' => 300,
                'format' => 'webp',
                'quality' => 80,
                'suffix' => '_thumb'
            ),
            'medium' => array(
                'type' => 'image',
                'service' => 'lambda',
                'width' => 800,
                'height' => 800,
                'format' => 'webp',
                'quality' => 85,
                'suffix' => '_medium'
            ),
            'large' => array(
                'type' => 'image',
                'service' => 'lambda',
                'width' => 1200,
                'height' => 1200,
                'format' => 'webp',
                'quality' => 90,
                'suffix' => '_large'
            ),
            
            // Video presets (handled by MediaConvert)
            'video_web' => array(
                'type' => 'video',
                'service' => 'mediaconvert',
                'width' => 1280,
                'height' => 720,
                'format' => 'mp4',
                'codec' => 'H_264',
                'bitrate' => 2000,
                'suffix' => '_web'
            ),
            'video_thumbnail' => array(
                'type' => 'video',
                'service' => 'mediaconvert',
                'width' => 300,
                'height' => 300,
                'format' => 'jpg',
                'time_offset' => 5, // Extract frame at 5 seconds
                'suffix' => '_thumb'
            )
        );
    }
    
    /**
     * Initialize system resource limits and detect available image processing library
     */
    private function init_system_limits() {
        $this->max_execution_time = ini_get('max_execution_time') ?: 30;
        $this->memory_limit = $this->parse_memory_limit(ini_get('memory_limit'));
        
        // Set reasonable limits for media processing
        @set_time_limit(120); // 2 minutes for processing
        @ini_set('memory_limit', '256M');
        
        // Detect available image processing library
        $this->detect_image_library();
    }
    
    /**
     * Detect available image processing library
     */
    private function detect_image_library() {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $this->image_library = 'imagick';
        } elseif (extension_loaded('gd') && function_exists('gd_info')) {
            $this->image_library = 'gd';
        } else {
            $this->image_library = 'none';
        }
        
        // Only log if in admin area or during file uploads to reduce log spam
        if (is_admin() || wp_doing_ajax()) {
            error_log('CF7AS Media Converter: Detected image library = ' . $this->image_library);
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parse_memory_limit($limit) {
        if (is_numeric($limit)) {
            return (int) $limit;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }
    
    /**
     * Initialize conversion presets for different file types and sizes
     */
    private function init_conversion_presets() {
        $this->conversion_presets = array(
            'thumbnail' => array(
                'width' => 300,
                'height' => 300,
                'quality' => 85,
                'format' => 'webp',
                'suffix' => '_thumb'
            ),
            'preview' => array(
                'width' => 800,
                'height' => 800,
                'quality' => 90,
                'format' => 'webp', 
                'suffix' => '_preview'
            ),
            'web_medium' => array(
                'width' => 1200,
                'height' => 1200,
                'quality' => 85,
                'format' => 'webp',
                'suffix' => '_medium'
            ),
            'web_large' => array(
                'width' => 1920,
                'height' => 1080,
                'quality' => 80,
                'format' => 'webp',
                'suffix' => '_large'
            )
        );
        
        // Allow customization via filter
        $this->conversion_presets = apply_filters('cf7as_conversion_presets', $this->conversion_presets);
    }
    
    /**
     * Trigger conversion for uploaded file using AWS services
     * 
     * @param string $s3_key S3 key of uploaded file
     * @param array $file_metadata File metadata
     */
    public function trigger_conversion($s3_key, $file_metadata) {
        try {
            error_log('CF7AS Media Converter: Starting conversion trigger for ' . $s3_key);
            
            if (!$this->is_conversion_enabled()) {
                error_log('CF7AS Media Converter: Conversion not enabled, skipping ' . $s3_key);
                return false;
            }
            
            $file_type = $this->get_file_type($file_metadata['mime_type']);
            
            // Skip conversion for GIF files - preserve original animated GIFs
            if ($file_metadata['mime_type'] === 'image/gif') {
                error_log('CF7AS Media Converter: Skipping conversion for GIF file (preserving animation): ' . $s3_key);
                return false;
            }
            
            // Only convert images and videos
            if (!in_array($file_type, array('image', 'video'))) {
                error_log('CF7AS Media Converter: File type not supported for conversion: ' . $file_type . ' for ' . $s3_key);
                return false;
            }
            
            // Create conversion job record
            $job_id = $this->create_conversion_job($s3_key, $file_metadata, $file_type);
            
            if (!$job_id) {
                error_log('CF7AS Media Converter: Failed to create conversion job for ' . $s3_key);
                return false;
            }
            
            error_log('CF7AS Media Converter: Created conversion job ' . $job_id . ' for ' . $s3_key);
            
            // Trigger AWS service based on file type
            if ($file_type === 'image') {
                return $this->trigger_lambda_conversion($job_id, $s3_key, $file_metadata);
            } elseif ($file_type === 'video') {
                return $this->trigger_mediaconvert_job($job_id, $s3_key, $file_metadata);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('CF7AS Media Converter: Exception in trigger_conversion for ' . $s3_key . ': ' . $e->getMessage());
            error_log('CF7AS Media Converter: Stack trace: ' . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log('CF7AS Media Converter: Fatal error in trigger_conversion for ' . $s3_key . ': ' . $e->getMessage());
            error_log('CF7AS Media Converter: Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Trigger Lambda function for image conversion
     * 
     * @param int $job_id Conversion job ID
     * @param string $s3_key S3 key of original file
     * @param array $file_metadata File metadata
     * @return bool Success status
     */
    private function trigger_lambda_conversion($job_id, $s3_key, $file_metadata) {
        $options = get_option('cf7_artist_submissions_options', array());
        $aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
        $aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
        
        if (empty($aws_access_key) || empty($aws_secret_key)) {
            error_log('CF7AS Media Converter: Missing AWS credentials for Lambda');
            return false;
        }
        
        // Prepare Lambda payload
        // Get bucket name directly from WordPress options as fallback
        $bucket_name = $this->s3_handler->get_bucket_name();
        if (!$bucket_name) {
            $options = get_option('cf7_artist_submissions_options', array());
            $bucket_name = isset($options['s3_bucket']) ? $options['s3_bucket'] : '';
        }
        
        $payload = array(
            'job_id' => $job_id,
            's3_key' => $s3_key,
            'bucket' => $bucket_name,
            'presets' => $this->get_image_presets(),
            'callback_url' => admin_url('admin-ajax.php?action=cf7as_conversion_callback'),
            'file_metadata' => $file_metadata
        );
        
        // Create AWS signature for Lambda invocation
        $lambda_endpoint = "https://lambda.{$this->aws_region}.amazonaws.com/2015-03-31/functions/{$this->lambda_function_name}/invocations";
        
        $response = $this->invoke_lambda_function($lambda_endpoint, $payload, $aws_access_key, $aws_secret_key);
        
        if ($response) {
            error_log("CF7AS Media Converter: Successfully triggered Lambda for job {$job_id}");
            
            // Process the Lambda response directly instead of waiting for callback
            $this->process_lambda_response($job_id, $response);
            
            return true;
        } else {
            error_log("CF7AS Media Converter: Failed to trigger Lambda for job {$job_id}");
            
            // Mark job as failed
            $this->update_job_status($job_id, 'failed', 'Lambda invocation failed');
            
            return false;
        }
    }
    
    /**
     * Process Lambda function response and update job status
     * 
     * @param int $job_id Conversion job ID
     * @param array $response Lambda response data
     */
    private function process_lambda_response($job_id, $response) {
        
        if (!is_array($response)) {
            $this->update_job_status($job_id, 'failed', 'Invalid Lambda response format');
            return;
        }
        
        // Check if there's a body in the response (Lambda returns nested JSON)
        $response_body = isset($response['body']) ? json_decode($response['body'], true) : $response;
        
        if (!$response_body) {
            $this->update_job_status($job_id, 'failed', 'Could not parse Lambda response');
            return;
        }
        
        $status = isset($response_body['status']) ? $response_body['status'] : 'unknown';
        $converted_files = isset($response_body['converted_files']) ? $response_body['converted_files'] : array();
        $error_message = isset($response_body['error']) ? $response_body['error'] : '';
        
        
        // Update job status
        $additional_data = array();
        
        if (!empty($converted_files) && is_array($converted_files)) {
            $additional_data['converted_files'] = json_encode($converted_files);
            
            // Store individual converted files
            $this->store_converted_files($job_id, $converted_files);
        }
        
        $this->update_job_status($job_id, $status, $error_message, $additional_data);
        
        // If completed successfully, update the original file record
        if ($status === 'completed' && !empty($converted_files)) {
            $this->update_original_file_record($job_id, $converted_files);
        }
    }
    
    /**
     * Trigger MediaConvert job for video conversion
     * 
     * @param int $job_id Conversion job ID
     * @param string $s3_key S3 key of original file
     * @param array $file_metadata File metadata
     * @return bool Success status
     */
    private function trigger_mediaconvert_job($job_id, $s3_key, $file_metadata) {
        try {
            error_log('CF7AS Media Converter: Starting MediaConvert job for ' . $s3_key . ' (job ID: ' . $job_id . ')');
            
            $options = get_option('cf7_artist_submissions_options', array());
            $aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
            $aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
            
            if (empty($aws_access_key) || empty($aws_secret_key)) {
                error_log('CF7AS Media Converter: Missing AWS credentials for MediaConvert');
                $this->update_job_status($job_id, 'failed', 'Missing AWS credentials');
                return false;
            }
            
            // Prepare MediaConvert job specification
            $job_spec = $this->create_mediaconvert_job_spec($s3_key, $file_metadata);
            
            if (!$job_spec) {
                error_log('CF7AS Media Converter: Failed to create MediaConvert job spec for ' . $s3_key);
                $this->update_job_status($job_id, 'failed', 'Failed to create job specification');
                return false;
            }
            
            // Create MediaConvert job
            $response = $this->create_mediaconvert_job($job_spec, $aws_access_key, $aws_secret_key);
            
            // Debug: Log the full response structure
            error_log('CF7AS MediaConvert: Full response structure: ' . print_r($response, true));
            
            if ($response && isset($response['job']['id'])) {
                // Update job record with MediaConvert job ID (lowercase 'job' and 'id' from actual response)
                global $wpdb;
                $result = $wpdb->update(
                    $wpdb->prefix . 'cf7as_conversion_jobs',
                    array(
                        'external_job_id' => $response['job']['id'],
                        'status' => 'processing'
                    ),
                    array('id' => $job_id)
                );
                
                if ($result === false) {
                    error_log('CF7AS Media Converter: Failed to update job status in database for job ' . $job_id);
                }
                
                error_log("CF7AS Media Converter: Successfully created MediaConvert job {$response['job']['id']} for job {$job_id}");
                return true;
            } else {
                // Enhanced error logging
                error_log("CF7AS Media Converter: Failed to create MediaConvert job for job {$job_id}");
                error_log("CF7AS Media Converter: Response structure check failed. Response: " . print_r($response, true));
                $this->update_job_status($job_id, 'failed', 'MediaConvert job creation failed - invalid response structure');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CF7AS Media Converter: MediaConvert job creation exception for job {$job_id}: " . $e->getMessage());
            error_log("CF7AS Media Converter: Exception trace: " . $e->getTraceAsString());
            $this->update_job_status($job_id, 'failed', 'MediaConvert error: ' . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("CF7AS Media Converter: MediaConvert job creation fatal error for job {$job_id}: " . $e->getMessage());
            error_log("CF7AS Media Converter: Error trace: " . $e->getTraceAsString());
            $this->update_job_status($job_id, 'failed', 'MediaConvert fatal error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get image conversion presets
     * 
     * @return array Image presets
     */
    private function get_image_presets() {
        $presets = array();
        foreach ($this->conversion_presets as $name => $preset) {
            if ($preset['type'] === 'image') {
                $presets[$name] = $preset;
            }
        }
        return $presets;
    }
    
    /**
     * Invoke Lambda function with AWS signature
     * 
     * @param string $endpoint Lambda endpoint URL
     * @param array $payload Function payload
     * @param string $access_key AWS access key
     * @param string $secret_key AWS secret key
     * @return array|false Response or false on failure
     */
    private function invoke_lambda_function($endpoint, $payload, $access_key, $secret_key) {
        
        $payload_json = json_encode($payload);
        
        // Create AWS signature for Lambda
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        // Create signature
        $canonical_request = "POST\n" .
                           "/2015-03-31/functions/{$this->lambda_function_name}/invocations\n" .
                           "\n" .
                           "host:lambda.{$this->aws_region}.amazonaws.com\n" .
                           "x-amz-date:{$timestamp}\n" .
                           "\n" .
                           "host;x-amz-date\n" .
                           hash('sha256', $payload_json);
        
        $string_to_sign = "AWS4-HMAC-SHA256\n" .
                         $timestamp . "\n" .
                         "{$date}/{$this->aws_region}/lambda/aws4_request\n" .
                         hash('sha256', $canonical_request);
        
        $signing_key = $this->get_aws_signing_key($date, $this->aws_region, 'lambda', $secret_key);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        $authorization = "AWS4-HMAC-SHA256 Credential={$access_key}/{$date}/{$this->aws_region}/lambda/aws4_request, SignedHeaders=host;x-amz-date, Signature={$signature}";
        
        // Make request
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => $authorization,
                'X-Amz-Date' => $timestamp,
                'Content-Type' => 'application/json'
            ),
            'body' => $payload_json,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7AS Lambda Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        
        if ($status_code === 200) {
            return json_decode($response_body, true);
        } else {
            error_log("CF7AS Lambda Error: HTTP $status_code - $response_body");
        }
        
        return false;
    }
    
    /**
     * Create MediaConvert job specification
     * 
     * @param string $s3_key Input S3 key
     * @param array $file_metadata File metadata
     * @return array Job specification
     */
    private function create_mediaconvert_job_spec($s3_key, $file_metadata) {
        $bucket_name = $this->s3_handler->get_bucket_name();
        $input_s3_uri = "s3://{$bucket_name}/{$s3_key}";
        $output_path = dirname($s3_key) . '/converted/';
        $filename = pathinfo($s3_key, PATHINFO_FILENAME);
        
        // Get MediaConvert service role ARN
        $options = get_option('cf7_artist_submissions_options', array());
        $mediaconvert_role = isset($options['mediaconvert_role_arn']) ? $options['mediaconvert_role_arn'] : '';
        
        if (empty($mediaconvert_role)) {
            throw new Exception('MediaConvert service role ARN not configured');
        }
        
        $outputs = array();
        
        // Add video presets with modern settings
        foreach ($this->conversion_presets as $preset_name => $preset) {
            if ($preset['type'] === 'video') {
                if ($preset['format'] === 'mp4') {
                    // Modern MP4 output configuration
                    $outputs[] = array(
                        'NameModifier' => $preset['suffix'],
                        'ContainerSettings' => array(
                            'Container' => 'MP4',
                            'Mp4Settings' => array(
                                'CslgAtom' => 'INCLUDE',
                                'FreeSpaceBox' => 'EXCLUDE',
                                'MoovPlacement' => 'PROGRESSIVE_DOWNLOAD'
                            )
                        ),
                        'VideoDescription' => array(
                            'Width' => $preset['width'],
                            'Height' => $preset['height'],
                            'ScalingBehavior' => 'DEFAULT',
                            'TimecodeInsertion' => 'DISABLED',
                            'AntiAlias' => 'ENABLED',
                            'Sharpness' => 50,
                            'CodecSettings' => array(
                                'Codec' => 'H_264',
                                'H264Settings' => array(
                                    'InterlaceMode' => 'PROGRESSIVE',
                                    'NumberReferenceFrames' => 3,
                                    'Syntax' => 'DEFAULT',
                                    'Softness' => 0,
                                    'GopClosedCadence' => 1,
                                    'GopSize' => 90,
                                    'Slices' => 1,
                                    'GopBReference' => 'DISABLED',
                                    'SlowPal' => 'DISABLED',
                                    'SpatialAdaptiveQuantization' => 'ENABLED',
                                    'TemporalAdaptiveQuantization' => 'ENABLED',
                                    'FlickerAdaptiveQuantization' => 'DISABLED',
                                    'EntropyEncoding' => 'CABAC',
                                    'Bitrate' => $preset['bitrate'] * 1000, // Convert to bps
                                    'FramerateControl' => 'INITIALIZE_FROM_SOURCE',
                                    'RateControlMode' => 'CBR',
                                    'CodecProfile' => 'MAIN',
                                    'Telecine' => 'NONE',
                                    'MinIInterval' => 0,
                                    'AdaptiveQuantization' => 'HIGH',
                                    'CodecLevel' => 'AUTO',
                                    'FieldEncoding' => 'PAFF',
                                    'SceneChangeDetect' => 'ENABLED',
                                    'QualityTuningLevel' => 'SINGLE_PASS',
                                    'FramerateConversionAlgorithm' => 'DUPLICATE_DROP',
                                    'UnregisteredSeiTimecode' => 'DISABLED',
                                    'GopSizeUnits' => 'FRAMES',
                                    'ParControl' => 'INITIALIZE_FROM_SOURCE',
                                    'NumberBFramesBetweenReferenceFrames' => 2,
                                    'RepeatPps' => 'DISABLED'
                                )
                            )
                        ),
                        'AudioDescriptions' => array(
                            array(
                                'AudioTypeControl' => 'FOLLOW_INPUT',
                                'CodecSettings' => array(
                                    'Codec' => 'AAC',
                                    'AacSettings' => array(
                                        'AudioDescriptionBroadcasterMix' => 'NORMAL',
                                        'Bitrate' => 96000,
                                        'RateControlMode' => 'CBR',
                                        'CodecProfile' => 'LC',
                                        'CodingMode' => 'CODING_MODE_2_0',
                                        'RawFormat' => 'NONE',
                                        'SampleRate' => 48000,
                                        'Specification' => 'MPEG4'
                                    )
                                ),
                                'LanguageCodeControl' => 'FOLLOW_INPUT'
                            )
                        )
                    );
                } elseif ($preset['format'] === 'jpg') {
                    // Thumbnail extraction configuration
                    $outputs[] = array(
                        'NameModifier' => $preset['suffix'],
                        'ContainerSettings' => array(
                            'Container' => 'RAW'
                        ),
                        'VideoDescription' => array(
                            'Width' => $preset['width'],
                            'Height' => $preset['height'],
                            'ScalingBehavior' => 'DEFAULT',
                            'TimecodeInsertion' => 'DISABLED',
                            'AntiAlias' => 'ENABLED',
                            'Sharpness' => 50,
                            'CodecSettings' => array(
                                'Codec' => 'FRAME_CAPTURE',
                                'FrameCaptureSettings' => array(
                                    'FramerateNumerator' => 1,
                                    'FramerateDenominator' => $preset['time_offset'] ?? 5,
                                    'MaxCaptures' => 1,
                                    'Quality' => 80
                                )
                            )
                        )
                    );
                }
            }
        }
        
        if (empty($outputs)) {
            throw new Exception('No valid outputs configured for MediaConvert job');
        }
        
        return array(
            'Role' => $mediaconvert_role,
            'Settings' => array(
                'Inputs' => array(array(
                    'FileInput' => $input_s3_uri,
                    'AudioSelectors' => array(
                        'Audio Selector 1' => array(
                            'Offset' => 0,
                            'DefaultSelection' => 'DEFAULT',
                            'ProgramSelection' => 1
                        )
                    ),
                    'VideoSelector' => array(
                        'ColorSpace' => 'FOLLOW'
                    ),
                    'FilterEnable' => 'AUTO',
                    'PsiControl' => 'USE_PSI',
                    'FilterStrength' => 0,
                    'DeblockFilter' => 'DISABLED',
                    'DenoiseFilter' => 'DISABLED',
                    'TimecodeSource' => 'EMBEDDED'
                )),
                'OutputGroups' => array(array(
                    'Name' => 'File Group',
                    'OutputGroupSettings' => array(
                        'Type' => 'FILE_GROUP_SETTINGS',
                        'FileGroupSettings' => array(
                            'Destination' => "s3://{$bucket_name}/{$output_path}"
                        )
                    ),
                    'Outputs' => $outputs
                ))
            ),
            'StatusUpdateInterval' => 'SECONDS_60',
            'Priority' => 0
        );
    }
    
    /**
     * Create MediaConvert job
     * 
     * @param array $job_spec Job specification
     * @param string $access_key AWS access key
     * @param string $secret_key AWS secret key
     * @return array|false Response or false on failure
     */
    private function create_mediaconvert_job($job_spec, $access_key, $secret_key) {
        $payload_json = json_encode($job_spec);
        
        // Create AWS signature for MediaConvert
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        // MediaConvert endpoint
        $host = str_replace('https://', '', $this->mediaconvert_endpoint);
        $path = '/2017-08-29/jobs';
        
        $canonical_request = "POST\n" .
                           $path . "\n" .
                           "\n" .
                           "host:{$host}\n" .
                           "x-amz-date:{$timestamp}\n" .
                           "\n" .
                           "host;x-amz-date\n" .
                           hash('sha256', $payload_json);
        
        $string_to_sign = "AWS4-HMAC-SHA256\n" .
                         $timestamp . "\n" .
                         "{$date}/{$this->aws_region}/mediaconvert/aws4_request\n" .
                         hash('sha256', $canonical_request);
        
        $signing_key = $this->get_aws_signing_key($date, $this->aws_region, 'mediaconvert', $secret_key);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        $authorization = "AWS4-HMAC-SHA256 Credential={$access_key}/{$date}/{$this->aws_region}/mediaconvert/aws4_request, SignedHeaders=host;x-amz-date, Signature={$signature}";
        
        // Log the request for debugging
        error_log('CF7AS MediaConvert: Creating job with endpoint: ' . $this->mediaconvert_endpoint . $path);
        error_log('CF7AS MediaConvert: Job payload: ' . $payload_json);
        
        // Make request
        $response = wp_remote_post($this->mediaconvert_endpoint . $path, array(
            'headers' => array(
                'Authorization' => $authorization,
                'X-Amz-Date' => $timestamp,
                'Content-Type' => 'application/json'
            ),
            'body' => $payload_json,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7AS MediaConvert Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log response for debugging
        error_log("CF7AS MediaConvert Response: HTTP $status_code");
        error_log("CF7AS MediaConvert Response Body: $response_body");
        
        if ($status_code === 201) {
            return json_decode($response_body, true);
        } else {
            // Parse error response
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown MediaConvert error';
            error_log("CF7AS MediaConvert Error: HTTP $status_code - $error_message");
            
            // Check for common error scenarios
            if ($status_code === 400 && strpos($error_message, 'Role') !== false) {
                error_log('CF7AS MediaConvert Error: Check MediaConvert service role ARN configuration');
            } elseif ($status_code === 403) {
                error_log('CF7AS MediaConvert Error: Check AWS credentials and permissions');
            } elseif ($status_code === 404) {
                error_log('CF7AS MediaConvert Error: Check endpoint URL - may need account-specific endpoint');
            }
            
            return false;
        }
    }
    
    /**
     * Get AWS signing key
     * 
     * @param string $date Date string
     * @param string $region AWS region
     * @param string $service AWS service
     * @param string $secret_key AWS secret key
     * @return string Signing key
     */
    private function get_aws_signing_key($date, $region, $service, $secret_key) {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $secret_key, true);
        $regionKey = hash_hmac('sha256', $region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
        return hash_hmac('sha256', 'aws4_request', $serviceKey, true);
    }
    
    /**
     * Process conversion queue (called by WordPress cron)
     */
    public function process_conversion_queue() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        error_log("CF7AS Media Converter: ===== CRON JOB STARTED =====");
        error_log("CF7AS Media Converter: process_conversion_queue() called at " . current_time('mysql'));
        
        // Check processing MediaConvert jobs first
        $this->check_mediaconvert_jobs();
        
        // Get pending jobs
        $jobs = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT 5"
        );
        
        error_log("CF7AS Media Converter: Found " . count($jobs) . " pending conversion jobs");
        
        foreach ($jobs as $job) {
            $this->process_conversion_job($job->id);
            
            // Small delay between jobs to prevent resource exhaustion
            usleep(500000); // 0.5 seconds
        }
        
        error_log("CF7AS Media Converter: ===== CRON JOB COMPLETED =====");
    }
    
    /**
     * Check status of processing MediaConvert jobs
     */
    private function check_mediaconvert_jobs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Get processing MediaConvert jobs
        $jobs = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE status = 'processing' 
             AND file_type = 'video' 
             AND external_job_id IS NOT NULL 
             ORDER BY created_at ASC 
             LIMIT 10"
        );
        
        error_log("CF7AS Media Converter: Checking MediaConvert jobs - found " . count($jobs) . " processing video jobs");
        
        if (empty($jobs)) {
            // Also check if there are any completed jobs that might have been missed
            $completed_jobs = $wpdb->get_results(
                "SELECT * FROM {$table_name} 
                 WHERE status = 'completed' 
                 AND file_type = 'video' 
                 AND external_job_id IS NOT NULL 
                 ORDER BY created_at DESC 
                 LIMIT 5"
            );
            error_log("CF7AS Media Converter: Found " . count($completed_jobs) . " already completed video jobs for reference");
            return;
        }
        
        foreach ($jobs as $job) {
            error_log("CF7AS Media Converter: Checking status of MediaConvert job ID: {$job->external_job_id} (internal ID: {$job->id})");
            $this->check_mediaconvert_job_status($job);
            
            // Small delay between API calls
            usleep(200000); // 0.2 seconds
        }
    }
    
    /**
     * Check individual MediaConvert job status
     * 
     * @param object $job Job record from database
     */
    private function check_mediaconvert_job_status($job) {
        $options = get_option('cf7_artist_submissions_options', array());
        $aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
        $aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
        
        if (empty($aws_access_key) || empty($aws_secret_key) || empty($job->external_job_id)) {
            error_log("CF7AS Media Converter: Missing credentials or job ID for MediaConvert status check. AWS Key: " . (!empty($aws_access_key) ? 'present' : 'missing') . ", Job ID: {$job->external_job_id}");
            return;
        }
        
        try {
            error_log("CF7AS Media Converter: Getting status for MediaConvert job: {$job->external_job_id}");
            $job_status = $this->get_mediaconvert_job_status($job->external_job_id, $aws_access_key, $aws_secret_key);
            
            if ($job_status) {
                error_log("CF7AS Media Converter: Retrieved job status for {$job->external_job_id}: " . ($job_status['job']['status'] ?? 'unknown'));
                $this->process_mediaconvert_job_status($job, $job_status);
            } else {
                error_log("CF7AS Media Converter: Failed to get job status for {$job->external_job_id}");
            }
            
        } catch (Exception $e) {
            error_log("CF7AS Media Converter: Exception checking MediaConvert job {$job->external_job_id}: " . $e->getMessage());
        }
    }
    
    /**
     * Get MediaConvert job status from AWS
     * 
     * @param string $job_id MediaConvert job ID
     * @param string $access_key AWS access key
     * @param string $secret_key AWS secret key
     * @return array|false Job status data or false on failure
     */
    private function get_mediaconvert_job_status($job_id, $access_key, $secret_key) {
        // Create AWS signature for MediaConvert
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        // MediaConvert endpoint
        $host = str_replace('https://', '', $this->mediaconvert_endpoint);
        $path = "/2017-08-29/jobs/{$job_id}";
        
        $canonical_request = "GET\n" .
                           $path . "\n" .
                           "\n" .
                           "host:{$host}\n" .
                           "x-amz-date:{$timestamp}\n" .
                           "\n" .
                           "host;x-amz-date\n" .
                           hash('sha256', '');
        
        $string_to_sign = "AWS4-HMAC-SHA256\n" .
                         $timestamp . "\n" .
                         "{$date}/{$this->aws_region}/mediaconvert/aws4_request\n" .
                         hash('sha256', $canonical_request);
        
        $signing_key = $this->get_aws_signing_key($date, $this->aws_region, 'mediaconvert', $secret_key);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        $authorization = "AWS4-HMAC-SHA256 Credential={$access_key}/{$date}/{$this->aws_region}/mediaconvert/aws4_request, SignedHeaders=host;x-amz-date, Signature={$signature}";
        
        // Make request
        $response = wp_remote_get($this->mediaconvert_endpoint . $path, array(
            'headers' => array(
                'Authorization' => $authorization,
                'X-Amz-Date' => $timestamp,
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7AS MediaConvert Status Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            return json_decode($response_body, true);
        } else {
            error_log("CF7AS MediaConvert Status Error: HTTP $status_code - $response_body");
            return false;
        }
    }
    
    /**
     * Process MediaConvert job status response
     * 
     * @param object $job Job record from database
     * @param array $job_status MediaConvert job status data
     */
    private function process_mediaconvert_job_status($job, $job_status) {
        if (!isset($job_status['job']['status'])) {
            return;
        }
        
        $aws_status = $job_status['job']['status'];
        $progress = isset($job_status['job']['jobPercentComplete']) ? (int)$job_status['job']['jobPercentComplete'] : 0;
        
        error_log("CF7AS MediaConvert: Job {$job->external_job_id} status: {$aws_status} ({$progress}%)");
        
        switch ($aws_status) {
            case 'COMPLETE':
                $this->handle_mediaconvert_completion($job, $job_status);
                break;
                
            case 'ERROR':
            case 'CANCELED':
                $error_message = isset($job_status['job']['errorMessage']) ? $job_status['job']['errorMessage'] : "Job {$aws_status}";
                $this->update_job_status($job->id, 'failed', $error_message);
                break;
                
            case 'PROGRESSING':
            case 'SUBMITTED':
                // Update progress if available
                if ($progress > 0) {
                    $this->update_job_status($job->id, 'processing', null, array('progress' => $progress));
                }
                break;
        }
    }
    
    /**
     * Handle MediaConvert job completion
     * 
     * @param object $job Job record from database
     * @param array $job_status MediaConvert job status data
     */
    private function handle_mediaconvert_completion($job, $job_status) {
        // Extract output file information
        $converted_files = array();
        
        error_log("CF7AS Media Converter: Processing MediaConvert job completion for job {$job->external_job_id}");
        error_log("CF7AS Media Converter: Job status structure: " . json_encode($job_status['job'] ?? 'no job data', JSON_PRETTY_PRINT));
        
        // MediaConvert doesn't always include outputFilePaths in the response
        // We need to construct the file paths from the job settings and output details
        if (isset($job_status['job']['settings']['outputGroups'])) {
            foreach ($job_status['job']['settings']['outputGroups'] as $group_index => $group) {
                if (isset($group['outputs']) && isset($group['outputGroupSettings']['fileGroupSettings']['destination'])) {
                    $destination = $group['outputGroupSettings']['fileGroupSettings']['destination'];
                    
                    // Extract bucket and path from s3://bucket/path/ format
                    if (strpos($destination, 's3://') === 0) {
                        $s3_parts = explode('/', substr($destination, 5), 2);
                        $bucket = $s3_parts[0];
                        $output_path = isset($s3_parts[1]) ? $s3_parts[1] : '';
                        
                        // Get the original filename without extension
                        $original_filename = pathinfo($job->original_s3_key, PATHINFO_FILENAME);
                        
                        error_log("CF7AS Media Converter: Processing output group {$group_index} with destination: {$destination}");
                        error_log("CF7AS Media Converter: Original filename: {$original_filename}");
                        
                        foreach ($group['outputs'] as $output_index => $output) {
                            $name_modifier = $output['nameModifier'] ?? '';
                            
                            // Determine format and file extension based on container
                            $format = '';
                            $file_extension = '';
                            $preset = '';
                            
                            if (isset($output['containerSettings']['container'])) {
                                $container = $output['containerSettings']['container'];
                                
                                if ($container === 'MP4') {
                                    $format = 'mp4';
                                    $file_extension = '.mp4';
                                    $preset = 'video_web';
                                } elseif ($container === 'RAW') {
                                    // For frame capture (thumbnails)
                                    $format = 'jpg';
                                    $file_extension = '.jpg';
                                    $preset = 'video_thumbnail';
                                }
                            }
                            
                            // Construct the output file path
                            $output_filename = $original_filename . $name_modifier . $file_extension;
                            $full_s3_key = rtrim($output_path, '/') . '/' . $output_filename;
                            
                            error_log("CF7AS Media Converter: Constructed output file: {$full_s3_key} (format: {$format}, preset: {$preset})");
                            
                            $converted_files[] = array(
                                'original_s3_key' => $job->original_s3_key,
                                'converted_s3_key' => $full_s3_key,
                                'output_path' => "s3://{$bucket}/{$full_s3_key}",
                                'format' => $format,
                                'preset' => $preset
                            );
                        }
                    }
                }
            }
        }
        
        error_log("CF7AS Media Converter: MediaConvert job {$job->external_job_id} completion processing finished");
        error_log("CF7AS Media Converter: Extracted " . count($converted_files) . " converted files from MediaConvert output");
        
        // Update job status
        $additional_data = array(
            'progress' => 100
        );
        
        if (!empty($converted_files)) {
            $additional_data['converted_files'] = json_encode($converted_files);
            error_log("CF7AS Media Converter: Calling store_converted_files for job {$job->id}");
            $this->store_converted_files($job->id, $converted_files);
        } else {
            error_log("CF7AS Media Converter: WARNING - No converted files found for job {$job->external_job_id}");
        }
        
        $this->update_job_status($job->id, 'completed', null, $additional_data);
        
        // Update original file record
        if (!empty($converted_files)) {
            $this->update_original_file_record($job->id, $converted_files);
        }
        
        error_log("CF7AS Media Converter: MediaConvert job {$job->external_job_id} completed successfully with " . count($converted_files) . " converted files");
        
        // Post-process JPG thumbnails to WebP
        $this->convert_thumbnails_to_webp($job->id, $converted_files);
    }
    
    /**
     * Convert JPG thumbnails to WebP format using Lambda
     * 
     * @param int $job_id Original conversion job ID
     * @param array $converted_files Array of converted files from MediaConvert
     */
    private function convert_thumbnails_to_webp($job_id, $converted_files) {
        foreach ($converted_files as $file) {
            // Only process JPG thumbnails
            if ($file['format'] === 'jpg' && $file['preset'] === 'video_thumbnail') {
                error_log("CF7AS Media Converter: Converting JPG thumbnail to WebP: {$file['converted_s3_key']}");
                
                // Create a pseudo file metadata for the Lambda conversion
                $thumbnail_metadata = array(
                    'submission_id' => '', // Will be filled from original job
                    'original_name' => basename($file['converted_s3_key']),
                    'mime_type' => 'image/jpeg',
                    'file_size' => 0 // Unknown at this point
                );
                
                // Create a new conversion job for WebP conversion
                $webp_job_id = $this->create_thumbnail_webp_job($job_id, $file['converted_s3_key'], $thumbnail_metadata);
                
                if ($webp_job_id) {
                    // Trigger Lambda conversion for WebP
                    $this->trigger_lambda_thumbnail_conversion($webp_job_id, $file['converted_s3_key'], $thumbnail_metadata);
                }
            }
        }
    }
    
    /**
     * Create a conversion job specifically for thumbnail WebP conversion
     * 
     * @param int $parent_job_id Original video conversion job ID
     * @param string $jpg_s3_key S3 key of JPG thumbnail
     * @param array $file_metadata File metadata
     * @return int|false Job ID or false on failure
     */
    private function create_thumbnail_webp_job($parent_job_id, $jpg_s3_key, $file_metadata) {
        global $wpdb;
        
        // Get parent job info
        $parent_job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cf7as_conversion_jobs WHERE id = %d",
            $parent_job_id
        ));
        
        if (!$parent_job) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        $insert_data = array(
            'original_s3_key' => $jpg_s3_key,
            'submission_id' => $parent_job->submission_id,
            'original_filename' => basename($jpg_s3_key),
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'presets' => json_encode(array('webp_thumbnail' => array('format' => 'webp', 'suffix' => '_webp')))
        );
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Trigger Lambda function for thumbnail WebP conversion
     * 
     * @param int $job_id Conversion job ID
     * @param string $jpg_s3_key S3 key of JPG file
     * @param array $file_metadata File metadata
     * @return bool Success status
     */
    private function trigger_lambda_thumbnail_conversion($job_id, $jpg_s3_key, $file_metadata) {
        $options = get_option('cf7_artist_submissions_options', array());
        $aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
        $aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
        
        if (empty($aws_access_key) || empty($aws_secret_key)) {
            error_log('CF7AS Media Converter: Missing AWS credentials for Lambda thumbnail conversion');
            return false;
        }
        
        $bucket_name = $this->s3_handler->get_bucket_name();
        if (!$bucket_name) {
            $options = get_option('cf7_artist_submissions_options', array());
            $bucket_name = isset($options['s3_bucket']) ? $options['s3_bucket'] : '';
        }
        
        // Create WebP conversion preset
        $webp_presets = array(
            'webp_thumbnail' => array(
                'type' => 'image',
                'width' => 300,
                'height' => 300,
                'format' => 'webp',
                'quality' => 85,
                'suffix' => '_webp'
            )
        );
        
        $payload = array(
            'job_id' => $job_id,
            's3_key' => $jpg_s3_key,
            'bucket' => $bucket_name,
            'presets' => $webp_presets,
            'callback_url' => admin_url('admin-ajax.php?action=cf7as_conversion_callback'),
            'file_metadata' => $file_metadata
        );
        
        $lambda_endpoint = "https://lambda.{$this->aws_region}.amazonaws.com/2015-03-31/functions/{$this->lambda_function_name}/invocations";
        
        $response = $this->invoke_lambda_function($lambda_endpoint, $payload, $aws_access_key, $aws_secret_key);
        
        if ($response) {
            error_log("CF7AS Media Converter: Successfully triggered Lambda for thumbnail WebP conversion job {$job_id}");
            $this->process_lambda_response($job_id, $response);
            return true;
        } else {
            error_log("CF7AS Media Converter: Failed to trigger Lambda for thumbnail WebP conversion job {$job_id}");
            $this->update_job_status($job_id, 'failed', 'Lambda thumbnail WebP conversion failed');
            return false;
        }
    }
    
    /**
     * Process a single conversion job
     */
    public function process_conversion_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Get job details
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            error_log("CF7AS Media Converter: Job {$job_id} not found");
            return false;
        }
        
        // Update status to processing
        $wpdb->update(
            $table_name,
            array('status' => 'processing', 'started_at' => current_time('mysql')),
            array('id' => $job_id),
            array('%s', '%s'),
            array('%d')
        );
        
        try {
            // Download original file from S3
            $temp_file = $this->download_file_from_s3($job->original_s3_key);
            
            if (!$temp_file) {
                throw new Exception('Failed to download original file from S3');
            }
            
            // Process conversions based on file type
            $results = array();
            if ($job->file_type === 'image') {
                $results = $this->process_image_conversions($temp_file, $job);
            } elseif ($job->file_type === 'video') {
                error_log("CF7AS Media Converter: Video conversion not implemented yet for job {$job_id}");
                $results = array(); // Video conversion placeholder
            }
            
            // Clean up temp file
            @unlink($temp_file);
            
            // Update job status
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'completed',
                    'completed_at' => current_time('mysql'),
                    'results' => json_encode($results)
                ),
                array('id' => $job_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            error_log("CF7AS Media Converter: Successfully processed job {$job_id}");
            return true;
            
        } catch (Exception $e) {
            error_log("CF7AS Media Converter: Job {$job_id} failed: " . $e->getMessage());
            
            // Update failure count
            $attempts = (int) $job->attempts + 1;
            $status = $attempts >= 3 ? 'failed' : 'pending';
            
            $wpdb->update(
                $table_name,
                array(
                    'status' => $status,
                    'attempts' => $attempts,
                    'error_message' => $e->getMessage()
                ),
                array('id' => $job_id),
                array('%s', '%d', '%s'),
                array('%d')
            );
            
            return false;
        }
    }
    
    /**
     * Download file from S3 to temporary location
     */
    private function download_file_from_s3($s3_key) {
        $temp_file = wp_tempnam();
        
        try {
            $presigned_url = $this->s3_handler->get_presigned_download_url($s3_key, 3600); // 1 hour
            
            $response = wp_remote_get($presigned_url, array(
                'timeout' => 300, // 5 minutes
                'stream' => true,
                'filename' => $temp_file
            ));
            
            if (is_wp_error($response)) {
                @unlink($temp_file);
                throw new Exception('Failed to download file: ' . $response->get_error_message());
            }
            
            if (wp_remote_retrieve_response_code($response) !== 200) {
                @unlink($temp_file);
                throw new Exception('Failed to download file: HTTP ' . wp_remote_retrieve_response_code($response));
            }
            
            return $temp_file;
            
        } catch (Exception $e) {
            @unlink($temp_file);
            throw $e;
        }
    }
    
    /**
     * Process image conversions using available image library
     */
    private function process_image_conversions($temp_file, $job) {
        $results = array();
        
        foreach ($this->conversion_presets as $preset_name => $preset) {
            // Skip PDF presets for regular images
            if (strpos($preset_name, 'pdf_') === 0) {
                continue;
            }
            
            try {
                $converted_file = $this->convert_image($temp_file, $preset, $job->mime_type);
                
                if ($converted_file) {
                    // Upload converted file to S3
                    $converted_s3_key = $this->generate_converted_s3_key($job->original_s3_key, $preset['suffix'], $preset['format']);
                    $upload_success = $this->upload_converted_file_to_s3($converted_file, $converted_s3_key);
                    
                    if ($upload_success) {
                        $results[$preset_name] = array(
                            's3_key' => $converted_s3_key,
                            'format' => $preset['format'],
                            'width' => $preset['width'],
                            'height' => $preset['height'],
                            'file_size' => filesize($converted_file)
                        );
                    }
                    
                    @unlink($converted_file);
                }
                
            } catch (Exception $e) {
                error_log("CF7AS Media Converter: Failed to convert {$preset_name}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Create conversion job record in database
     * 
     * @param string $s3_key Original S3 key
     * @param array $file_metadata File metadata
     * @param string $file_type File type (image/video/audio)
     * @return int|false Job ID or false on failure
     */
    private function create_conversion_job($s3_key, $file_metadata, $file_type) {
        global $wpdb;
        
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Ensure table exists
        $this->create_conversion_jobs_table();
        
        $presets = $this->get_presets_for_type($file_type);
        
        $insert_data = array(
            'original_s3_key' => $s3_key,
            'submission_id' => $file_metadata['submission_id'],
            'original_filename' => $file_metadata['original_name'],
            'file_type' => $file_type,
            'mime_type' => $file_metadata['mime_type'],
            'file_size' => $file_metadata['file_size'],
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'presets' => json_encode($presets)
        );
        
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            return false;
        }
        
        $job_id = $wpdb->insert_id;
        return $job_id;
    }
    
    /**
     * Create conversion jobs table if it doesn't exist
     */
    private function create_conversion_jobs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            original_s3_key text NOT NULL,
            submission_id varchar(255) NOT NULL,
            original_filename text NOT NULL,
            file_type varchar(50) NOT NULL,
            mime_type varchar(255) NOT NULL,
            file_size bigint(20) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            lambda_job_id varchar(255) DEFAULT NULL,
            mediaconvert_job_id varchar(255) DEFAULT NULL,
            external_job_id varchar(255) DEFAULT NULL,
            converted_files longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            progress int(3) DEFAULT 0,
            started_at timestamp NULL DEFAULT NULL,
            completed_at timestamp NULL DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            presets longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY original_s3_key (original_s3_key(191)),
            KEY submission_id (submission_id),
            KEY status (status),
            KEY file_type (file_type),
            KEY created_at (created_at),
            KEY external_job_id (external_job_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add missing column for existing installations
        $this->add_missing_columns();
    }
    
    /**
     * Add missing columns to existing database tables
     */
    private function add_missing_columns() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Check if external_job_id column exists, if not add it
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'external_job_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN external_job_id varchar(255) DEFAULT NULL");
            $wpdb->query("ALTER TABLE $table_name ADD KEY external_job_id (external_job_id)");
        }
    }
    
    /**
     * Create converted files table to store individual converted file versions
     */
    private function create_converted_files_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_converted_files';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            conversion_job_id int(11) NOT NULL,
            original_s3_key text NOT NULL,
            converted_s3_key text NOT NULL,
            format varchar(50) NOT NULL,
            preset varchar(50) NOT NULL,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            file_size bigint(20) DEFAULT NULL,
            quality int(3) DEFAULT NULL,
            bitrate varchar(50) DEFAULT NULL,
            duration decimal(10,2) DEFAULT NULL,
            thumbnail_s3_key text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversion_job_id (conversion_job_id),
            KEY original_s3_key (original_s3_key(191)),
            KEY format (format),
            KEY preset (preset),
            FOREIGN KEY (conversion_job_id) REFERENCES {$wpdb->prefix}cf7as_conversion_jobs(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get conversion presets for specific file type
     * 
     * @param string $file_type File type (image/video/audio)
     * @return array Conversion presets
     */
    private function get_presets_for_type($file_type) {
        $presets = array();
        foreach ($this->conversion_presets as $name => $preset) {
            if (isset($preset['type']) && $preset['type'] === $file_type) {
                $presets[$name] = $preset;
            }
        }
        return $presets;
    }
    
    /**
     * Determine file type from MIME type
     * 
     * @param string $mime_type File MIME type
     * @return string File type (image/video/audio/document/other)
     */
    private function get_file_type($mime_type) {
        if (strpos($mime_type, 'image/') === 0) {
            return 'image';
        }
        
        if (strpos($mime_type, 'video/') === 0) {
            return 'video';
        }
        
        if (strpos($mime_type, 'audio/') === 0) {
            return 'audio';
        }
        
        return 'other';
    }
    
    
    /**
     * Update conversion job status
     * 
     * @param int $job_id Job ID
     * @param string $status New status
     * @param string $error_message Error message (optional)
     * @param array $additional_data Additional data to update
     */
    private function update_job_status($job_id, $status, $error_message = null, $additional_data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        $update_data = array_merge($additional_data, array(
            'status' => $status
        ));
        
        if ($error_message) {
            $update_data['error_message'] = $error_message;
        }
        
        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
            $update_data['progress'] = 100;
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $job_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Handle conversion callback from Lambda function
     * Requires proper authentication for security
     */
    public function handle_conversion_callback() {
        // Require valid user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
            return;
        }
        
        // Require nonce verification for all conversion callbacks
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'cf7as_conversion_callback')) {
            wp_die('Security check failed');
            return;
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        
        if (!$job_id) {
            wp_send_json_error('Invalid job ID');
            return;
        }
        
        // Verify this job_id exists in our database and belongs to a valid job
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        $job_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $jobs_table WHERE id = %d AND status IN ('pending', 'processing')",
            $job_id
        ));
        
        if (!$job_exists) {
            wp_send_json_error('Invalid job ID');
            return;
        }
        
        // Sanitize and validate input data
        $status = sanitize_text_field($_POST['status'] ?? '');
        $converted_files = $_POST['converted_files'] ?? array();
        $error_message = sanitize_text_field($_POST['error_message'] ?? '');
        $progress = intval($_POST['progress'] ?? 0);
        
        
        // Update job status
        $additional_data = array();
        
        if (!empty($converted_files) && is_array($converted_files)) {
            $additional_data['converted_files'] = json_encode($converted_files);
            
            // Store individual converted files
            $this->store_converted_files($job_id, $converted_files);
        }
        
        if ($progress > 0) {
            $additional_data['progress'] = $progress;
        }
        
        $this->update_job_status($job_id, $status, $error_message, $additional_data);
        
        // If completed successfully, update the original file record
        if ($status === 'completed' && !empty($converted_files)) {
            $this->update_original_file_record($job_id, $converted_files);
        }
        
        wp_send_json_success(array(
            'job_id' => $job_id,
            'status' => $status,
            'message' => 'Conversion status updated'
        ));
    }
    
    /**
     * Store individual converted files in database
     * 
     * @param int $job_id Conversion job ID
     * @param array $converted_files Array of converted file data
     */
    private function store_converted_files($job_id, $converted_files) {
        global $wpdb;
        
        error_log("CF7AS Media Converter: store_converted_files called for job {$job_id} with " . count($converted_files) . " files");
        
        $this->create_converted_files_table();
        $table_name = $wpdb->prefix . 'cf7as_converted_files';
        
        error_log("CF7AS Media Converter: Storing " . count($converted_files) . " converted files for job {$job_id}");
        
        foreach ($converted_files as $file) {
            $insert_data = array(
                'conversion_job_id' => $job_id,
                'original_s3_key' => $file['original_s3_key'] ?? '',
                'converted_s3_key' => $file['converted_s3_key'] ?? '',
                'format' => $file['format'] ?? '',
                'preset' => $file['preset'] ?? '',
                'width' => isset($file['width']) ? intval($file['width']) : null,
                'height' => isset($file['height']) ? intval($file['height']) : null,
                'file_size' => isset($file['file_size']) ? intval($file['file_size']) : null,
                'quality' => isset($file['quality']) ? intval($file['quality']) : null,
                'bitrate' => $file['bitrate'] ?? null,
                'duration' => isset($file['duration']) ? floatval($file['duration']) : null,
                'thumbnail_s3_key' => $file['thumbnail_s3_key'] ?? null,
                'created_at' => current_time('mysql')
            );

            $result = $wpdb->insert($table_name, $insert_data);
            
            if ($result === false) {
                error_log("CF7AS Media Converter: Failed to insert converted file record: " . $wpdb->last_error . " Data: " . json_encode($insert_data));
                
                // Check if the conversion job exists
                $job_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}cf7as_conversion_jobs WHERE id = %d",
                    $job_id
                ));
                
                if (!$job_exists) {
                    error_log("CF7AS Media Converter: Conversion job {$job_id} does not exist - foreign key constraint failed");
                }
            } else {
                error_log("CF7AS Media Converter: Stored converted file - Original: {$insert_data['original_s3_key']}, Converted: {$insert_data['converted_s3_key']}, Format: {$insert_data['format']}, Preset: {$insert_data['preset']}");
            }
        }
    }
    
    /**
     * Update original file record with converted file references
     * 
     * @param int $job_id Conversion job ID
     * @param array $converted_files Array of converted file data
     */
    private function update_original_file_record($job_id, $converted_files) {
        global $wpdb;
        
        // Get the original file record
        $job_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        $files_table = $wpdb->prefix . 'cf7as_files';
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $job_table WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            return;
        }
        
        // Find the original file record
        $original_file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $files_table WHERE s3_key = %s AND submission_id = %s",
            $job->original_s3_key,
            $job->submission_id
        ));
        
        if (!$original_file) {
            return;
        }
        
        // Update the file record with conversion status
        $update_data = array(
            'has_converted_versions' => 1,
            'conversion_job_id' => $job_id
        );
        
        // Ensure columns exist before updating
        $column_exists = $wpdb->get_results("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$files_table' 
            AND COLUMN_NAME = 'has_converted_versions'
        ");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $files_table ADD COLUMN has_converted_versions tinyint(1) DEFAULT 0");
        }
        
        $job_column_exists = $wpdb->get_results("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$files_table' 
            AND COLUMN_NAME = 'conversion_job_id'
        ");
        
        if (empty($job_column_exists)) {
            $wpdb->query("ALTER TABLE $files_table ADD COLUMN conversion_job_id int(11) DEFAULT NULL");
        }
        
        $wpdb->update(
            $files_table,
            $update_data,
            array('id' => $original_file->id),
            null,
            array('%d')
        );
    }
    
    /**
     * Get converted file versions for display/serving
     * 
     * @param string $original_s3_key Original S3 key
     * @param string $format Desired format (optional)
     * @param string $preset Desired preset (optional)
     * @return array Array of converted file data
     */
    public function get_converted_versions($original_s3_key, $format = null, $preset = null) {
        global $wpdb;
        
        $converted_files_table = $wpdb->prefix . 'cf7as_converted_files';
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        $where_conditions = array("j.original_s3_key = %s");
        $where_values = array($original_s3_key);
        
        if ($format) {
            $where_conditions[] = "cf.format = %s";
            $where_values[] = $format;
        }
        
        if ($preset) {
            $where_conditions[] = "cf.preset = %s";
            $where_values[] = $preset;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT cf.*, j.status as job_status 
            FROM $converted_files_table cf
            INNER JOIN $jobs_table j ON cf.conversion_job_id = j.id
            WHERE $where_clause
            ORDER BY cf.preset, cf.format
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Add debug logging
        error_log("CF7AS Media Converter: Query for converted versions - Original S3 Key: {$original_s3_key}, Format: " . ($format ?? 'any') . ", Preset: " . ($preset ?? 'any') . ", Results found: " . count($results));
        
        return $results;
    }
    
    /**
     * Get best converted version for serving (prefers WebP for images, MP4 for videos)
     * 
     * @param string $original_s3_key Original S3 key
     * @param string $preset Desired size/quality preset
     * @return object|null Converted file data or null if not available
     */
    public function get_best_version_for_serving($original_s3_key, $preset = 'medium') {
        $converted_versions = $this->get_converted_versions($original_s3_key, null, $preset);
        
        if (empty($converted_versions)) {
            return null;
        }
        
        // Preference order for formats
        $format_preferences = array('webp', 'mp4', 'jpeg', 'webm', 'png');
        
        foreach ($format_preferences as $preferred_format) {
            foreach ($converted_versions as $version) {
                if ($version->format === $preferred_format) {
                    return $version;
                }
            }
        }
        
        // Return first available if no preferred format found
        return $converted_versions[0];
    }
    
    /**
     * Get thumbnail version for file
     * 
     * @param string $original_s3_key Original S3 key
     * @return object|null Thumbnail file data or null if not available
     */
    public function get_thumbnail_version($original_s3_key) {
        // First try to get image thumbnail (for image files)
        $thumbnail = $this->get_best_version_for_serving($original_s3_key, 'thumbnail');
        
        if (!$thumbnail) {
            // Try to get video thumbnail - prefer WebP, then fall back to JPG
            $converted_versions = $this->get_converted_versions($original_s3_key);
            $webp_thumbnail = null;
            $jpg_thumbnail = null;
            
            foreach ($converted_versions as $version) {
                if ($version->preset === 'video_thumbnail') {
                    if ($version->format === 'webp') {
                        $webp_thumbnail = $version;
                    } elseif ($version->format === 'jpg') {
                        $jpg_thumbnail = $version;
                    }
                }
            }
            
            // Return WebP thumbnail if available, otherwise JPG
            return $webp_thumbnail ?: $jpg_thumbnail;
        }
        
        return $thumbnail;
    }
    
    /**
     * Process existing files for conversion (backward compatibility)
     * 
     * @param int $limit Number of files to process in this batch
     * @return array Processing results
     */
    public function process_existing_files($limit = 10) {
        global $wpdb;
        
        
        $files_table = $wpdb->prefix . 'cf7as_files';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$files_table'");
        if (!$table_exists) {
            return array(
                'processed' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => array("Files table '$files_table' does not exist")
            );
        }
        
        // Ensure the has_converted_versions column exists
        $column_exists = $wpdb->get_results("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$files_table' 
            AND COLUMN_NAME = 'has_converted_versions'
        ");
        
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE $files_table 
                ADD COLUMN has_converted_versions tinyint(1) DEFAULT 0
            ");
        }
        
        // Get total count of files in table
        $total_files = $wpdb->get_var("SELECT COUNT(*) FROM $files_table");
        
        // Get files that haven't been processed yet (excluding GIFs to preserve animation)
        $query = $wpdb->prepare("
            SELECT * FROM $files_table 
            WHERE (has_converted_versions IS NULL OR has_converted_versions = 0)
            AND (mime_type LIKE 'image/%' OR mime_type LIKE 'video/%' OR mime_type LIKE 'audio/%')
            AND mime_type != 'image/gif'
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit);
        
        
        $files = $wpdb->get_results($query);
        $file_count = count($files);
        
        if ($file_count === 0) {
            // Let's check what files we do have
            $all_files = $wpdb->get_results("SELECT id, original_name, mime_type, has_converted_versions FROM $files_table ORDER BY created_at DESC LIMIT 5");
            foreach ($all_files as $file) {
            }
        }
        
        $results = array(
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // Check if conversion is enabled
        if (!$this->is_conversion_enabled()) {
            $results['failed'] = $file_count;
            $results['errors'][] = "Media conversion is not enabled in settings";
            return $results;
        }
        
        foreach ($files as $file) {
            
            $file_metadata = array(
                'submission_id' => $file->submission_id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'file_size' => $file->file_size
            );
            
            
            $success = $this->trigger_conversion($file->s3_key, $file_metadata);
            
            if ($success) {
                $results['processed']++;
                
                // Ensure has_converted_versions column exists before updating
                $column_exists = $wpdb->get_results("
                    SELECT COLUMN_NAME 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$files_table' 
                    AND COLUMN_NAME = 'has_converted_versions'
                ");
                
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $files_table ADD COLUMN has_converted_versions tinyint(1) DEFAULT 0");
                }
                
                // Update the file record to mark as processed
                $updated = $wpdb->update(
                    $files_table,
                    array('has_converted_versions' => 1),
                    array('id' => $file->id),
                    array('%d'),
                    array('%d')
                );
                
                if ($updated === false) {
                } else {
                }
            } else {
                $results['failed']++;
                $error_msg = "Failed to process file: {$file->original_name} (ID: {$file->id})";
                $results['errors'][] = $error_msg;
            }
        }
        
        return $results;
    }
    
    /**
     * Test media conversion capabilities
     * 
     * @return array Test results
     */
    public function test_conversion_capabilities() {
        $results = array(
            'image_library' => $this->image_library,
            'gd_available' => extension_loaded('gd'),
            'imagick_available' => extension_loaded('imagick'),
            'webp_support' => function_exists('imagewebp') && function_exists('imagecreatefromwebp'),
            's3_configured' => $this->s3_handler->is_configured(),
            'temp_dir_writable' => is_writable(sys_get_temp_dir()),
            'conversion_enabled' => $this->is_conversion_enabled(),
            'errors' => array()
        );
        
        // Check image processing capability
        if ($this->image_library === 'none') {
            $results['errors'][] = 'No image processing library available (GD or ImageMagick required)';
        }
        
        // Check S3 configuration
        if (!$results['s3_configured']) {
            $results['errors'][] = 'S3 not properly configured';
        }
        
        // Check temp directory
        if (!$results['temp_dir_writable']) {
            $results['errors'][] = 'Temporary directory not writable';
        }
        
        // Test actual image processing
        if ($this->image_library !== 'none') {
            $test_result = $this->test_image_processing();
            $results['image_processing_test'] = $test_result;
            if (!$test_result['success']) {
                $results['errors'][] = 'Image processing test failed: ' . $test_result['error'];
            }
        }
        
        $results['overall_status'] = empty($results['errors']) ? 'success' : 'error';
        
        return $results;
    }
    
    /**
     * Test image processing capabilities
     * 
     * @return array Test result
     */
    private function test_image_processing() {
        try {
            // Create a simple test image
            $test_image = imagecreatetruecolor(100, 100);
            $white = imagecolorallocate($test_image, 255, 255, 255);
            imagefill($test_image, 0, 0, $white);
            
            // Save to temp file
            $temp_file = wp_tempnam();
            $success = imagepng($test_image, $temp_file);
            imagedestroy($test_image);
            
            if (!$success) {
                return array('success' => false, 'error' => 'Failed to create test image');
            }
            
            // Test resizing
            $resized_image = $this->create_image_resource($temp_file, 'image/png');
            if (!$resized_image) {
                @unlink($temp_file);
                return array('success' => false, 'error' => 'Failed to create image resource');
            }
            
            // Create resized version
            $resized = imagecreatetruecolor(50, 50);
            imagecopyresampled($resized, $resized_image, 0, 0, 0, 0, 50, 50, 100, 100);
            
            // Clean up
            imagedestroy($resized_image);
            imagedestroy($resized);
            @unlink($temp_file);
            
            return array('success' => true, 'message' => 'Image processing test passed');
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Test MediaConvert connectivity and configuration
     * 
     * @return array Test results
     */
    public function test_mediaconvert_connection() {
        $options = get_option('cf7_artist_submissions_options', array());
        $aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
        $aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
        $mediaconvert_role = isset($options['mediaconvert_role_arn']) ? $options['mediaconvert_role_arn'] : '';
        
        $results = array(
            'endpoint_configured' => !empty($this->mediaconvert_endpoint),
            'credentials_configured' => !empty($aws_access_key) && !empty($aws_secret_key),
            'role_configured' => !empty($mediaconvert_role),
            'endpoint_url' => $this->mediaconvert_endpoint,
            'region' => $this->aws_region,
            'role_arn' => $mediaconvert_role
        );
        
        if (!$results['credentials_configured']) {
            $results['error'] = 'AWS credentials not configured';
            return $results;
        }
        
        if (!$results['role_configured']) {
            $results['error'] = 'MediaConvert service role ARN not configured';
            return $results;
        }
        
        // Test endpoint connectivity by trying to list jobs
        try {
            $timestamp = gmdate('Ymd\THis\Z');
            $date = gmdate('Ymd');
            $host = str_replace('https://', '', $this->mediaconvert_endpoint);
            $path = '/2017-08-29/jobs';
            
            // Create GET request for listing jobs (this should work even with no jobs)
            $canonical_request = "GET\n" .
                               $path . "\n" .
                               "\n" .
                               "host:{$host}\n" .
                               "x-amz-date:{$timestamp}\n" .
                               "\n" .
                               "host;x-amz-date\n" .
                               hash('sha256', '');
            
            $string_to_sign = "AWS4-HMAC-SHA256\n" .
                             $timestamp . "\n" .
                             "{$date}/{$this->aws_region}/mediaconvert/aws4_request\n" .
                             hash('sha256', $canonical_request);
            
            $signing_key = $this->get_aws_signing_key($date, $this->aws_region, 'mediaconvert', $aws_secret_key);
            $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
            
            $authorization = "AWS4-HMAC-SHA256 Credential={$aws_access_key}/{$date}/{$this->aws_region}/mediaconvert/aws4_request, SignedHeaders=host;x-amz-date, Signature={$signature}";
            
            $response = wp_remote_get($this->mediaconvert_endpoint . $path, array(
                'headers' => array(
                    'Authorization' => $authorization,
                    'X-Amz-Date' => $timestamp
                ),
                'timeout' => 10
            ));
            
            if (is_wp_error($response)) {
                $results['connection_test'] = false;
                $results['error'] = 'Connection failed: ' . $response->get_error_message();
                return $results;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($status_code === 200) {
                $results['connection_test'] = true;
                $results['message'] = 'MediaConvert connection successful';
            } else {
                $results['connection_test'] = false;
                $error_data = json_decode($response_body, true);
                $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown error';
                $results['error'] = "HTTP $status_code: $error_message";
                
                // Provide specific guidance based on error
                if ($status_code === 403) {
                    $results['suggestion'] = 'Check AWS credentials and MediaConvert permissions';
                } elseif ($status_code === 404) {
                    $results['suggestion'] = 'Endpoint not found - you may need to use account-specific endpoint';
                }
            }
            
        } catch (Exception $e) {
            $results['connection_test'] = false;
            $results['error'] = 'Exception during test: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Test Lambda connection and function availability
     * 
     * @return array Test results
     */
    private function test_lambda_connection() {
        $options = get_option('cf7_artist_submissions_options', array());
        
        if (empty($options['aws_access_key']) || empty($options['aws_secret_key'])) {
            return array(
                'connection_successful' => false,
                'message' => 'AWS credentials not configured',
                'details' => 'Please configure AWS access key and secret key in settings'
            );
        }
        
        if (empty($this->lambda_function_name)) {
            return array(
                'connection_successful' => false,
                'message' => 'Lambda function name not specified',
                'details' => 'Please specify the Lambda function name in settings'
            );
        }
        
        // Test Lambda function invocation with a simple test payload
        $test_payload = array(
            'test' => true,
            'action' => 'connection_test',
            'timestamp' => time()
        );
        
        $lambda_endpoint = "https://lambda.{$this->aws_region}.amazonaws.com/2015-03-31/functions/{$this->lambda_function_name}/invocations";
        
        try {
            $response = $this->invoke_lambda_function($lambda_endpoint, $test_payload, $options['aws_access_key'], $options['aws_secret_key']);
            
            if ($response !== false) {
                return array(
                    'connection_successful' => true,
                    'message' => 'Lambda connection successful',
                    'details' => array(
                        'function_name' => $this->lambda_function_name,
                        'region' => $this->aws_region,
                        'endpoint' => $lambda_endpoint,
                        'response' => $response
                    )
                );
            } else {
                return array(
                    'connection_successful' => false,
                    'message' => 'Lambda invocation failed',
                    'details' => 'Check function name, permissions, and AWS credentials'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'connection_successful' => false,
                'message' => 'Lambda connection error: ' . $e->getMessage(),
                'details' => 'Exception occurred during Lambda invocation test'
            );
        }
    }
    
    /**
     * Test Lambda connection with specific parameters (for testing before saving)
     * 
     * @param string $aws_access_key AWS access key
     * @param string $aws_secret_key AWS secret key  
     * @param string $aws_region AWS region
     * @param string $lambda_function_name Lambda function name
     * @param string $mediaconvert_endpoint MediaConvert endpoint (optional)
     * @return array Test results
     */
    private function test_lambda_connection_with_params($aws_access_key, $aws_secret_key, $aws_region, $lambda_function_name, $mediaconvert_endpoint = '') {
        
        // Validate required parameters
        if (empty($aws_access_key) || empty($aws_secret_key)) {
            return array(
                'connection_successful' => false,
                'lambda_configured' => false,
                'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                'credentials_valid' => false,
                'message' => 'AWS credentials not provided',
                'errors' => array('Please provide AWS access key and secret key'),
                'details' => 'Missing required AWS credentials for testing'
            );
        }
        
        if (empty($lambda_function_name)) {
            return array(
                'connection_successful' => false,
                'lambda_configured' => false,
                'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                'credentials_valid' => false,
                'message' => 'Lambda function name not specified',
                'errors' => array('Please specify the Lambda function name'),
                'details' => 'Missing required Lambda function name for testing'
            );
        }
        
        // Test Lambda function invocation with a simple test payload
        $test_payload = array(
            'test' => true,
            'action' => 'connection_test',
            'timestamp' => time()
        );
        
        $lambda_endpoint = "https://lambda.{$aws_region}.amazonaws.com/2015-03-31/functions/{$lambda_function_name}/invocations";
        
        try {
            $response = $this->invoke_lambda_function($lambda_endpoint, $test_payload, $aws_access_key, $aws_secret_key);
            
            if ($response !== false) {
                return array(
                    'connection_successful' => true,
                    'lambda_configured' => true,
                    'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                    'credentials_valid' => true,
                    'message' => 'Lambda connection successful',
                    'details' => array(
                        'function_name' => $lambda_function_name,
                        'region' => $aws_region,
                        'endpoint' => $lambda_endpoint,
                        'response' => $response
                    )
                );
            } else {
                return array(
                    'connection_successful' => false,
                    'lambda_configured' => true,
                    'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                    'credentials_valid' => false,
                    'message' => 'Lambda invocation failed',
                    'errors' => array('Check function name, permissions, and AWS credentials'),
                    'details' => 'Lambda function did not respond as expected'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'connection_successful' => false,
                'lambda_configured' => true,
                'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                'credentials_valid' => false,
                'message' => 'Lambda connection error: ' . $e->getMessage(),
                'errors' => array('Exception occurred during Lambda invocation test: ' . $e->getMessage()),
                'details' => 'Check network connectivity, function name, and AWS credentials'
            );
        }
    }
    
    /**
     * AJAX handler for testing media conversion functionality
     */
    public function test_media_conversion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_test_conversion')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Basic test functionality - can be expanded
        wp_send_json_success(array(
            'message' => 'Media conversion test completed',
            'php_version' => PHP_VERSION,
            'gd_enabled' => extension_loaded('gd'),
            'imagick_enabled' => extension_loaded('imagick')
        ));
    }
    
    /**
     * AJAX handler for testing Lambda connection
     */
    public function ajax_test_lambda_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_test_lambda')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get parameters from the request (for testing unsaved settings)
        $aws_access_key = sanitize_text_field($_POST['aws_access_key'] ?? '');
        $aws_secret_key = sanitize_text_field($_POST['aws_secret_key'] ?? '');
        $aws_region = sanitize_text_field($_POST['aws_region'] ?? '');
        $lambda_function_name = sanitize_text_field($_POST['lambda_function_name'] ?? '');
        $mediaconvert_endpoint = sanitize_text_field($_POST['mediaconvert_endpoint'] ?? '');
        
        // Validate required fields
        $errors = array();
        if (empty($aws_access_key)) {
            $errors[] = 'AWS Access Key is required';
        }
        if (empty($aws_secret_key)) {
            $errors[] = 'AWS Secret Key is required';
        }
        if (empty($aws_region)) {
            $errors[] = 'AWS Region is required';
        }
        if (empty($lambda_function_name)) {
            $errors[] = 'Lambda Function Name is required';
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => 'Missing required fields',
                'errors' => $errors,
                'lambda_configured' => false,
                'mediaconvert_configured' => !empty($mediaconvert_endpoint),
                'credentials_valid' => false,
                'connection_successful' => false
            ));
            return;
        }
        
        // Test Lambda function with provided credentials
        $results = $this->test_lambda_connection_with_params($aws_access_key, $aws_secret_key, $aws_region, $lambda_function_name, $mediaconvert_endpoint);
        
        if ($results['connection_successful']) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error($results);
        }
    }
    
    /**
     * AJAX handler for processing existing files
     */
    public function ajax_process_existing_files() {
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_process_files')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $limit = intval($_POST['limit'] ?? 10);
        $limit = max(1, min(50, $limit)); // Limit between 1 and 50
        
        // Check AWS settings before processing
        $options = get_option('cf7_artist_submissions_options', array());
        
        $results = $this->process_existing_files($limit);
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for getting conversion status
     */
    public function ajax_get_conversion_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_conversion_status')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Get summary statistics
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(progress) as avg_progress
            FROM $jobs_table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        // Get recent jobs
        $recent_jobs = $wpdb->get_results("
            SELECT id, original_filename, status, progress, created_at, completed_at, error_message
            FROM $jobs_table
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        wp_send_json_success(array(
            'summary' => array(
                'total' => intval($stats->total_jobs ?? 0),
                'completed' => intval($stats->completed ?? 0),
                'pending' => intval($stats->pending ?? 0) + intval($stats->processing ?? 0),
                'failed' => intval($stats->failed ?? 0)
            ),
            'recent_jobs' => $recent_jobs
        ));
    }
    
    /**
     * AJAX handler for resetting file conversion status
     */
    public function ajax_reset_file_status() {
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_reset_files')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'cf7as_files';
        
        // First, ensure the has_converted_versions column exists
        $column_exists = $wpdb->get_results("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$files_table' 
            AND COLUMN_NAME = 'has_converted_versions'
        ");
        
        if (empty($column_exists)) {
            $add_column_result = $wpdb->query("
                ALTER TABLE $files_table 
                ADD COLUMN has_converted_versions tinyint(1) DEFAULT 0
            ");
            
            if ($add_column_result === false) {
                wp_send_json_error('Failed to add required database column: ' . $wpdb->last_error);
                return;
            }
        } else {
        }
        
        // Now reset all files to not converted status
        $result = $wpdb->query(
            $wpdb->prepare("
                UPDATE {$wpdb->prefix}cf7as_files 
                SET has_converted_versions = %d 
                WHERE has_converted_versions = %d 
                OR has_converted_versions IS NULL
            ", 0, 1)
        );
        
        if ($result === false) {
            wp_send_json_error('Database update failed: ' . $wpdb->last_error);
            return;
        }
        
        $reset_count = $wpdb->rows_affected;
        
        // Also clear all pending and failed conversion jobs to allow fresh processing
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        $jobs_result = $wpdb->query(
            $wpdb->prepare("
                DELETE FROM {$wpdb->prefix}cf7as_conversion_jobs 
                WHERE status IN (%s, %s, %s)
            ", 'pending', 'failed', 'processing')
        );
        
        $cleared_jobs = 0;
        if ($jobs_result !== false) {
            $cleared_jobs = $wpdb->rows_affected;
        } else {
        }
        
        // Also clear converted files records to allow fresh conversion
        $converted_files_table = $wpdb->prefix . 'cf7as_converted_files';
        $converted_result = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}cf7as_converted_files WHERE id > 0"
        );
        
        $cleared_converted = 0;
        if ($converted_result !== false) {
            $cleared_converted = $wpdb->rows_affected;
        } else {
        }
        
        wp_send_json_success(array(
            'reset_count' => $reset_count,
            'cleared_jobs' => $cleared_jobs,
            'cleared_converted' => $cleared_converted,
            'message' => "Reset $reset_count files, cleared $cleared_jobs pending jobs, and $cleared_converted converted records for fresh processing"
        ));
    }
    
    /**
     * Convert image using available image processing library
     * 
     * @param string $temp_file Path to temporary file
     * @param array $preset Conversion preset
     * @param string $mime_type Original mime type
     * @return string|false Path to converted file or false
     */
    private function convert_image($temp_file, $preset, $mime_type) {
        try {
            // Create image resource from original file
            $image_resource = $this->create_image_resource($temp_file, $mime_type);
            if (!$image_resource) {
                throw new Exception('Failed to create image resource');
            }
            
            // Get original dimensions
            $original_width = imagesx($image_resource);
            $original_height = imagesy($image_resource);
            
            // Calculate new dimensions maintaining aspect ratio
            $new_dimensions = $this->calculate_resize_dimensions(
                $original_width, 
                $original_height, 
                $preset['width'], 
                $preset['height']
            );
            
            // Create resized image
            $resized_image = imagecreatetruecolor($new_dimensions['width'], $new_dimensions['height']);
            
            // Preserve transparency for PNG/GIF
            if (in_array($mime_type, array('image/png', 'image/gif'))) {
                imagealphablending($resized_image, false);
                imagesavealpha($resized_image, true);
                $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
                imagefill($resized_image, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled(
                $resized_image, $image_resource,
                0, 0, 0, 0,
                $new_dimensions['width'], $new_dimensions['height'],
                $original_width, $original_height
            );
            
            // Create temporary file for converted image
            $converted_file = wp_tempnam();
            
            // Save image in specified format
            $success = false;
            switch ($preset['format']) {
                case 'webp':
                    if (function_exists('imagewebp')) {
                        $success = imagewebp($resized_image, $converted_file, $preset['quality']);
                    }
                    break;
                case 'jpeg':
                    $success = imagejpeg($resized_image, $converted_file, $preset['quality']);
                    break;
                case 'png':
                    $success = imagepng($resized_image, $converted_file, round(9 - ($preset['quality'] / 100 * 9)));
                    break;
            }
            
            // Clean up resources
            imagedestroy($image_resource);
            imagedestroy($resized_image);
            
            if (!$success) {
                @unlink($converted_file);
                throw new Exception('Failed to save converted image');
            }
            
            return $converted_file;
            
        } catch (Exception $e) {
            error_log("CF7AS Media Converter: Image conversion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create image resource from file
     * 
     * @param string $file_path File path
     * @param string $mime_type MIME type
     * @return resource|GdImage|false Image resource or false
     */
    private function create_image_resource($file_path, $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($file_path);
            case 'image/png':
                return imagecreatefrompng($file_path);
            case 'image/gif':
                return imagecreatefromgif($file_path);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($file_path);
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Calculate resize dimensions maintaining aspect ratio
     * 
     * @param int $original_width Original width
     * @param int $original_height Original height
     * @param int $max_width Maximum width
     * @param int $max_height Maximum height
     * @return array New dimensions
     */
    private function calculate_resize_dimensions($original_width, $original_height, $max_width, $max_height) {
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        return array(
            'width' => round($original_width * $ratio),
            'height' => round($original_height * $ratio)
        );
    }
    
    /**
     * Generate S3 key for converted file
     * 
     * @param string $original_s3_key Original file S3 key
     * @param string $suffix Conversion suffix
     * @param string $format New format extension
     * @return string Converted file S3 key
     */
    private function generate_converted_s3_key($original_s3_key, $suffix, $format) {
        $path_info = pathinfo($original_s3_key);
        $directory = isset($path_info['dirname']) && $path_info['dirname'] !== '.' ? $path_info['dirname'] : '';
        $filename = $path_info['filename'];
        
        $converted_filename = $filename . $suffix . '.' . $format;
        
        return $directory ? $directory . '/' . $converted_filename : $converted_filename;
    }
    
    /**
     * Upload converted file to S3
     * 
     * @param string $file_path Local file path
     * @param string $s3_key Target S3 key
     * @return bool Success status
     */
    private function upload_converted_file_to_s3($file_path, $s3_key) {
        try {
            // Read file contents
            $file_contents = file_get_contents($file_path);
            if ($file_contents === false) {
                throw new Exception('Failed to read file contents');
            }
            
            // Get MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            
            // Upload using S3Handler method
            return $this->s3_handler->upload_file_content($s3_key, $file_contents, $mime_type);
            
        } catch (Exception $e) {
            error_log("CF7AS Media Converter: Failed to upload converted file to S3: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update file record with converted version info
     * 
     * @param string $original_s3_key Original file S3 key
     * @param string $preset_name Conversion preset name
     * @param array $conversion_result Conversion result data
     */
    private function update_file_with_converted_version($original_s3_key, $preset_name, $conversion_result) {
        global $wpdb;
        
        // Get the file record
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cf7as_files WHERE s3_key = %s",
            $original_s3_key
        ));
        
        if (!$file) {
            error_log("CF7AS Media Converter: Original file not found: {$original_s3_key}");
            return;
        }
        
        // Get existing converted versions
        $converted_versions = array();
        if (!empty($file->converted_versions)) {
            $converted_versions = json_decode($file->converted_versions, true);
            if (!is_array($converted_versions)) {
                $converted_versions = array();
            }
        }
        
        // Add the new converted version
        $converted_versions[$preset_name] = $conversion_result;
        
        // Update the file record
        $wpdb->update(
            $wpdb->prefix . 'cf7as_files',
            array('converted_versions' => json_encode($converted_versions)),
            array('id' => $file->id),
            array('%s'),
            array('%d')
        );
        
        error_log("CF7AS Media Converter: Updated file {$file->id} with converted version: {$preset_name}");
    }
    
    /**
     * Get conversion statistics for dashboard
     * 
     * @return array Conversion statistics
     */
    public function get_conversion_statistics() {
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        $files_table = $wpdb->prefix . 'cf7as_files';
        
        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$jobs_table'") !== $jobs_table) {
            return array(
                'total_files' => 0,
                'converted_files' => 0,
                'pending_conversions' => 0,
                'failed_conversions' => 0,
                'conversion_rate' => 0
            );
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                (SELECT COUNT(*) FROM $files_table WHERE (mime_type LIKE 'image/%' OR mime_type LIKE 'video/%' OR mime_type LIKE 'audio/%') AND mime_type != 'image/gif') as total_convertible,
                (SELECT COUNT(*) FROM $jobs_table WHERE status = 'completed') as completed,
                (SELECT COUNT(*) FROM $jobs_table WHERE status = 'pending' OR status = 'processing') as pending,
                (SELECT COUNT(*) FROM $jobs_table WHERE status = 'failed') as failed
        ");
        
        $conversion_rate = $stats->total_convertible > 0 ? ($stats->completed / $stats->total_convertible) * 100 : 0;
        
        return array(
            'total_files' => intval($stats->total_convertible),
            'converted_files' => intval($stats->completed),
            'pending_conversions' => intval($stats->pending),
            'failed_conversions' => intval($stats->failed),
            'conversion_rate' => round($conversion_rate, 1)
        );
    }
    
    /**
     * Clear pending conversion jobs
     * 
     * @return array Results with count of cleared jobs
     */
    public function clear_pending_jobs() {
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Count pending jobs first
        $pending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s",
            'pending'
        ));
        
        // Delete pending jobs
        $deleted = $wpdb->delete(
            $jobs_table,
            array('status' => 'pending'),
            array('%s')
        );
        
        error_log("CF7AS Media Converter: Cleared {$deleted} pending conversion jobs");
        
        return array(
            'success' => $deleted !== false,
            'count' => $deleted,
            'message' => $deleted !== false ? 
                "Successfully cleared {$deleted} pending jobs" : 
                'Failed to clear pending jobs'
        );
    }
    
    /**
     * Clear failed conversion jobs
     * 
     * @return array Results with count of cleared jobs
     */
    public function clear_failed_jobs() {
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        // Count failed jobs first
        $failed_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s",
            'failed'
        ));
        
        // Delete failed jobs and their converted file records
        $deleted = $wpdb->delete(
            $jobs_table,
            array('status' => 'failed'),
            array('%s')
        );
        
        error_log("CF7AS Media Converter: Cleared {$deleted} failed conversion jobs");
        
        return array(
            'success' => $deleted !== false,
            'count' => $deleted,
            'message' => $deleted !== false ? 
                "Successfully cleared {$deleted} failed jobs" : 
                'Failed to clear failed jobs'
        );
    }
    
    /**
     * Add custom cron intervals
     * 
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public function add_cron_intervals($schedules) {
        $schedules['cf7as_every_5_minutes'] = array(
            'interval' => 300, // 5 minutes in seconds
            'display' => __('Every 5 Minutes (CF7AS)')
        );
        
        $schedules['cf7as_every_minute'] = array(
            'interval' => 60, // 1 minute in seconds
            'display' => __('Every Minute (CF7AS Debug)')
        );
        
        return $schedules;
    }
    
    /**
     * AJAX handler to manually check conversion jobs
     */
    public function ajax_check_conversion_jobs() {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7as_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Run the conversion queue check
        $this->process_conversion_queue();
        
        // Get current status
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_conversion_jobs';
        
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status",
            ARRAY_A
        );
        
        $status_summary = array();
        foreach ($status_counts as $row) {
            $status_summary[$row['status']] = (int)$row['count'];
        }
        
        wp_send_json_success(array(
            'message' => 'Conversion jobs checked successfully',
            'status_counts' => $status_summary
        ));
    }
}

// Add conversion callback handler (keep this for handling external webhooks)
add_action('wp_ajax_cf7as_conversion_callback', function() {
    $converter = new CF7_Artist_Submissions_Media_Converter();
    $converter->handle_conversion_callback();
});

// Removed unauthenticated access for security - all media conversion operations require authentication
