<?php
/**
 * CF7 Artist Submissions - Amazon S3 Handler
 *
 * Handles all Amazon S3 operations including file uploads, presigned URLs,
 * thumbnail management, and secure file downloads for the CF7 Artist Submissions plugin.
 *
 * @package CF7_Artist_Submissions
 * @subpackage S3Handler
 * @since 1.1.0
 */

/**
 * S3 Handler Class
 * 
 * Manages all Amazon S3 operations for file uploads, downloads, and metadata handling.
 * Provides secure presigned URLs, thumbnail management, and ZIP archive functionality.
 * 
 * @since 1.1.0
 */
class CF7_Artist_Submissions_S3_Handler {
    
    /**
     * AWS configuration properties
     */
    private $aws_access_key;
    private $aws_secret_key;
    private $aws_region;
    private $s3_bucket;
    
    /**
     * Initialize the S3 handler
     */
    public function init() {
        // Register CF7 form tag for custom uploader
        add_action('wpcf7_init', array($this, 'register_cf7_uploader_form_tag'));
    }
    
    /**
     * Register the custom uploader form tag with Contact Form 7
     */
    public function register_cf7_uploader_form_tag() {
        if (function_exists('wpcf7_add_form_tag')) {
            wpcf7_add_form_tag(array('uploader', 'uploader*'), array($this, 'render_uploader_form_tag'), array('name-attr' => true));
        }
    }
    
    /**
     * Render the custom uploader form tag in Contact Form 7 forms
     */
    public function render_uploader_form_tag($tag) {
        if (empty($tag->name)) {
            return '';
        }
        
        $validation_error = function_exists('wpcf7_get_validation_error') ? wpcf7_get_validation_error($tag->name) : '';
        $class = function_exists('wpcf7_form_controls_class') ? wpcf7_form_controls_class($tag->type) : 'wpcf7-form-control';
        
        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }
        
        $atts = array();
        $atts['id'] = method_exists($tag, 'get_id_option') ? $tag->get_id_option() : 'cf7as-uppy-' . uniqid();
        $atts['name'] = $tag->name;
        $atts['required'] = (method_exists($tag, 'has_option') && $tag->has_option('required')) ? 'required' : '';
        
        // Fix max_files parameter parsing - try multiple CF7 parsing methods
        $max_files_from_tag = null;
        
        // Method 1: Try get_option
        if (method_exists($tag, 'get_option')) {
            $max_files_from_tag = $tag->get_option('max_files', 'int', true);
        }
        
        // Method 2: Try parsing from options array if get_option failed
        if (empty($max_files_from_tag) && isset($tag->options)) {
            foreach ($tag->options as $option) {
                if (preg_match('/^max_files:(\d+)$/', $option, $matches)) {
                    $max_files_from_tag = intval($matches[1]);
                    break;
                }
            }
        }
        
        $atts['max_files'] = (!empty($max_files_from_tag) && $max_files_from_tag > 0) ? $max_files_from_tag : 20;
        error_log("CF7 max_files debug - from tag: " . print_r($max_files_from_tag, true) . ", final: " . $atts['max_files']);
        
        // Fix max_size parameter parsing - try multiple methods too
        $max_size_from_tag = null;
        
        // Method 1: Try get_option
        if (method_exists($tag, 'get_option')) {
            $max_size_from_tag = $tag->get_option('max_size', 'int', true);
        }
        
        // Method 2: Try parsing from options array if get_option failed
        if (empty($max_size_from_tag) && isset($tag->options)) {
            foreach ($tag->options as $option) {
                if (preg_match('/^max_size:(\d+)$/', $option, $matches)) {
                    $max_size_from_tag = intval($matches[1]);
                    break;
                }
            }
        }
        
        $atts['max_size'] = (!empty($max_size_from_tag) && $max_size_from_tag > 0) ? $max_size_from_tag : 5120; // 5GB in MB
        
        $args = $atts;
        
        ob_start();
        include CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . '/templates/uploader-field-template.php';
        $output = ob_get_clean();
        
        return $output;
    }
    
    /**
     * Initialize S3 client configuration
     * Sets up AWS credentials and region from plugin options.
     * 
     * @return bool True if initialization successful, false otherwise
     */
    private function init_s3_client() {
        // Check for test options first (used during AJAX testing)
        $test_options = get_option('cf7_artist_submissions_test_options', array());
        $options = get_option('cf7_artist_submissions_options', array());
        
        // Use test options if they exist and contain credentials
        if (!empty($test_options) && isset($test_options['aws_access_key']) && isset($test_options['aws_secret_key'])) {
            $options = array_merge($options, $test_options);
            error_log('CF7AS S3 Init: Using test options for credentials');
        }
        
        $this->aws_access_key = isset($options['aws_access_key']) ? $options['aws_access_key'] : '';
        $this->aws_secret_key = isset($options['aws_secret_key']) ? $options['aws_secret_key'] : '';
        $this->aws_region = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
        $this->s3_bucket = isset($options['s3_bucket']) ? $options['s3_bucket'] : '';
        
        error_log('CF7AS S3 Init: Access Key = ' . (empty($this->aws_access_key) ? 'EMPTY' : 'SET (length: ' . strlen($this->aws_access_key) . ')'));
        error_log('CF7AS S3 Init: Secret Key = ' . (empty($this->aws_secret_key) ? 'EMPTY' : 'SET (length: ' . strlen($this->aws_secret_key) . ')'));
        error_log('CF7AS S3 Init: Region = ' . $this->aws_region);
        error_log('CF7AS S3 Init: Bucket = ' . (empty($this->s3_bucket) ? 'EMPTY' : $this->s3_bucket));
        
        if (empty($this->aws_access_key) || empty($this->aws_secret_key) || empty($this->s3_bucket)) {
            error_log('CF7AS S3 Init: Missing required credentials or bucket name');
            return false;
        }
        
        return true;
    }    /**
     * Generate presigned URL for file upload using AWS Signature V4
     * 
     * @param string $submission_id The submission ID
     * @param string $filename The filename
     * @param string $content_type The MIME type
     * @return array|false The presigned URL data or false on failure
     */
    public function get_presigned_upload_url($submission_id, $filename, $content_type) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        $key = $this->generate_s3_key($submission_id, $filename);
        $expires = 600; // 10 minutes
        
        $signature_data = $this->create_signature_v4('PUT', $key, $content_type, $expires, array(), array(), true);
        
        $url = "https://{$signature_data['host']}/{$key}?{$signature_data['canonical_query']}";
        
        return array(
            'url' => $url,
            's3_key' => $key,
            'expires' => time() + $expires
        );
    }
    
    /**
     * Generate presigned URL for file download using AWS Signature V4
     * 
     * @param string $s3_key The S3 object key
     * @param int $expires_in Expiration time in seconds (default: 1 hour)
     * @return string|false The presigned URL or false on failure
     */
    public function get_presigned_download_url($s3_key, $expires_in = 3600) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        $signature_data = $this->create_signature_v4('GET', $s3_key, '', $expires_in, array(), array(), true);
        
        $url = "https://{$signature_data['host']}/{$s3_key}?{$signature_data['canonical_query']}";
        
        return $url;
    }
    
    /**
     * Generate S3 key for file storage
     * 
     * @param string $submission_id The submission ID
     * @param string $filename The original filename
     * @return string The S3 key
     */
    public function generate_s3_key($submission_id, $filename) {
        // Sanitize filename
        $filename = sanitize_file_name($filename);
        return 'uploads/' . $submission_id . '/' . $filename;
    }
    
    /**
     * Generate S3 key for thumbnail storage
     * 
     * @param string $submission_id The submission ID
     * @param string $filename The original filename
     * @return string The S3 key for thumbnail
     */
    public function generate_thumbnail_s3_key($submission_id, $filename) {
        $filename = sanitize_file_name($filename);
        $path_info = pathinfo($filename);
        $thumbnail_filename = $path_info['filename'] . '_thumb.' . $path_info['extension'];
        return 'thumbnails/' . $submission_id . '/' . $thumbnail_filename;
    }
    
    /**
     * Delete file from S3 using WordPress HTTP API
     * 
     * @param string $s3_key The S3 object key
     * @return bool Success status
     */
    public function delete_file($s3_key) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        $url = "https://{$this->s3_bucket}.s3.{$this->aws_region}.amazonaws.com/{$s3_key}";
        $date = gmdate('D, d M Y H:i:s T');
        
        $string_to_sign = "DELETE\n\n\n{$date}\n/{$this->s3_bucket}/{$s3_key}";
        $signature = $this->sign_string($string_to_sign);
        
        $headers = array(
            'Authorization' => "AWS {$this->aws_access_key}:{$signature}",
            'Date' => $date,
            'Host' => "{$this->s3_bucket}.s3.{$this->aws_region}.amazonaws.com"
        );
        
        $response = wp_remote_request($url, array(
            'method' => 'DELETE',
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7 Artist Submissions S3 Delete Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return in_array($status_code, array(200, 204));
    }
    
    /**
     * Get file contents from S3 using WordPress HTTP API
     * 
     * @param string $s3_key The S3 object key
     * @return string|false The file contents or false on failure
     */
    public function get_file_stream($s3_key) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        // Use presigned URL for GET request
        $url = $this->get_presigned_download_url($s3_key, 300); // 5 minutes
        if (!$url) {
            return false;
        }
        
        $response = wp_remote_get($url, array(
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7 Artist Submissions S3 Get File Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('CF7 Artist Submissions S3 Get File Error: HTTP ' . $status_code);
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Check if S3 is properly configured using WordPress HTTP API
     * 
     * @return bool True if S3 is configured and accessible
     */
    public function is_configured() {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        $url = "https://{$this->s3_bucket}.s3.{$this->aws_region}.amazonaws.com/";
        $date = gmdate('D, d M Y H:i:s T');
        
        $string_to_sign = "HEAD\n\n\n{$date}\n/{$this->s3_bucket}/";
        $signature = $this->sign_string($string_to_sign);
        
        $headers = array(
            'Authorization' => "AWS {$this->aws_access_key}:{$signature}",
            'Date' => $date,
            'Host' => "{$this->s3_bucket}.s3.{$this->aws_region}.amazonaws.com"
        );
        
        $response = wp_remote_head($url, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return in_array($status_code, array(200, 404)); // 404 is OK for empty bucket
    }
    
    /**
     * Get S3 bucket name
     * 
     * @return string The bucket name
     */
    public function get_bucket_name() {
        return $this->s3_bucket;
    }
    
    /**
     * Get S3 region
     * 
     * @return string The region
     */
    public function get_region() {
        return $this->aws_region;
    }
    
    /**
     * Create AWS Signature Version 4 for S3 requests
     * Required for all regions except us-east-1
     * 
     * @param string $method HTTP method
     * @param string $s3_key S3 object key
     * @param string $content_type Content type
     * @param int $expires Expiration time in seconds (for presigned URLs)
     * @param array $headers Additional headers
     * @param array $query_params Additional query parameters
     * @param bool $presigned Whether this is for a presigned URL or direct API call
     * @return array Signature components
     */
    private function create_signature_v4($method, $s3_key, $content_type = '', $expires = 86400, $headers = array(), $query_params = array(), $presigned = true) {
        $algorithm = 'AWS4-HMAC-SHA256';
        $service = 's3';
        
        // Get current time
        $time = time();
        $amz_date = gmdate('Ymd\THis\Z', $time);
        $date_stamp = gmdate('Ymd', $time);
        
        // Create credential scope
        $credential_scope = "{$date_stamp}/{$this->aws_region}/{$service}/aws4_request";
        
        // Canonical URI - properly encode path components but preserve forward slashes
        $path_parts = explode('/', $s3_key);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $canonical_uri = '/' . implode('/', $encoded_parts);
        
        // Host
        $host = $this->s3_bucket . '.s3.' . $this->aws_region . '.amazonaws.com';
        
        if ($presigned) {
            // For presigned URLs
            $query_params = array_merge($query_params, array(
                'X-Amz-Algorithm' => $algorithm,
                'X-Amz-Credential' => $this->aws_access_key . '/' . $credential_scope,
                'X-Amz-Date' => $amz_date,
                'X-Amz-Expires' => $expires,
                'X-Amz-SignedHeaders' => 'host'
            ));
            
            // Sort query parameters
            ksort($query_params);
            $canonical_query = '';
            foreach ($query_params as $key => $value) {
                if ($canonical_query) $canonical_query .= '&';
                $canonical_query .= rawurlencode($key) . '=' . rawurlencode($value);
            }
            
            // Canonical headers
            $canonical_headers = "host:{$host}\n";
            $signed_headers = 'host';
            
            // Payload hash (empty for presigned URLs)
            $payload_hash = 'UNSIGNED-PAYLOAD';
        } else {
            // For direct API calls
            $canonical_query = '';
            foreach ($query_params as $key => $value) {
                if ($canonical_query) $canonical_query .= '&';
                if ($value === '') {
                    // For parameters without values (like 'uploads')
                    $canonical_query .= rawurlencode($key) . '=';
                } else {
                    $canonical_query .= rawurlencode($key) . '=' . rawurlencode($value);
                }
            }
            
            // Canonical headers for API calls
            $canonical_headers = "host:{$host}\n";
            $canonical_headers .= "x-amz-content-sha256:UNSIGNED-PAYLOAD\n";
            $canonical_headers .= "x-amz-date:{$amz_date}\n";
            
            $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
            $payload_hash = 'UNSIGNED-PAYLOAD';
        }
        
        // Canonical request
        $canonical_request = $method . "\n" . 
                           $canonical_uri . "\n" . 
                           $canonical_query . "\n" . 
                           $canonical_headers . "\n" . 
                           $signed_headers . "\n" . 
                           $payload_hash;
        
        // String to sign
        $string_to_sign = $algorithm . "\n" . 
                         $amz_date . "\n" . 
                         $credential_scope . "\n" . 
                         hash('sha256', $canonical_request);
        
        // Signing key
        $signing_key = $this->get_signing_key($date_stamp, $this->aws_region, $service);
        
        // Signature
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        if ($presigned) {
            return array(
                'host' => $host,
                'canonical_query' => $canonical_query . '&X-Amz-Signature=' . $signature,
                'signature' => $signature
            );
        } else {
            return array(
                'host' => $host,
                'canonical_query' => $canonical_query,
                'authorization_header' => "{$algorithm} Credential={$this->aws_access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}",
                'amz_date' => $amz_date,
                'payload_hash' => $payload_hash
            );
        }
    }
    
    /**
     * Create AWS Signature Version 4 for S3 requests with content body
     * 
     * @param string $method HTTP method
     * @param string $s3_key S3 object key
     * @param string $content_type Content type
     * @param int $expires Expiration time in seconds
     * @param array $headers Additional headers
     * @param array $query_params Additional query parameters
     * @param string $content_hash SHA256 hash of the request body
     * @return array Signature components
     */
    private function create_signature_v4_with_content($method, $s3_key, $content_type = '', $expires = 86400, $headers = array(), $query_params = array(), $content_hash = '') {
        $algorithm = 'AWS4-HMAC-SHA256';
        $service = 's3';
        
        // Get current time
        $time = time();
        $amz_date = gmdate('Ymd\THis\Z', $time);
        $date_stamp = gmdate('Ymd', $time);
        
        // Create credential scope
        $credential_scope = "{$date_stamp}/{$this->aws_region}/{$service}/aws4_request";
        
        // Canonical URI - properly encode path components but preserve forward slashes
        $path_parts = explode('/', $s3_key);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $canonical_uri = '/' . implode('/', $encoded_parts);
        
        // Host
        $host = $this->s3_bucket . '.s3.' . $this->aws_region . '.amazonaws.com';
        
        // For direct API calls with content
        $canonical_query = '';
        foreach ($query_params as $key => $value) {
            if ($canonical_query) $canonical_query .= '&';
            if ($value === '') {
                // For parameters without values (like 'uploads')
                $canonical_query .= rawurlencode($key) . '=';
            } else {
                $canonical_query .= rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        
        // Canonical headers for API calls with content
        $canonical_headers = "host:{$host}\n";
        $canonical_headers .= "x-amz-content-sha256:{$content_hash}\n";
        $canonical_headers .= "x-amz-date:{$amz_date}\n";
        
        $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
        $payload_hash = $content_hash;
        
        // Canonical request
        $canonical_request = $method . "\n" . 
                           $canonical_uri . "\n" . 
                           $canonical_query . "\n" . 
                           $canonical_headers . "\n" . 
                           $signed_headers . "\n" . 
                           $payload_hash;
        
        // String to sign
        $string_to_sign = $algorithm . "\n" . 
                         $amz_date . "\n" . 
                         $credential_scope . "\n" . 
                         hash('sha256', $canonical_request);
        
        // Signing key
        $signing_key = $this->get_signing_key($date_stamp, $this->aws_region, $service);
        
        // Signature
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        return array(
            'host' => $host,
            'canonical_query' => $canonical_query,
            'authorization_header' => "{$algorithm} Credential={$this->aws_access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}",
            'amz_date' => $amz_date,
            'payload_hash' => $payload_hash
        );
    }
    
    /**
     * Sign string using AWS secret key with HMAC-SHA1
     * 
     * @param string $string_to_sign The string to sign
     * @return string Base64-encoded signature
     */
    private function sign_string($string_to_sign) {
        return base64_encode(hash_hmac('sha1', $string_to_sign, $this->aws_secret_key, true));
    }
    
    /**
     * Test S3 connection using WordPress HTTP API with AWS Signature V4
     * 
     * @return array Connection test result
     */
    public function test_connection() {
        if (!$this->init_s3_client()) {
            return array(
                'success' => false,
                'message' => 'Invalid S3 configuration. Please check your AWS credentials.'
            );
        }
        
        // Use AWS Signature Version 4 for bucket access test
        $url = "https://{$this->s3_bucket}.s3.{$this->aws_region}.amazonaws.com/";
        
        // Create V4 signature for HEAD request
        $algorithm = 'AWS4-HMAC-SHA256';
        $service = 's3';
        $time = time();
        $amz_date = gmdate('Ymd\THis\Z', $time);
        $date_stamp = gmdate('Ymd', $time);
        
        // Create credential scope
        $credential_scope = "{$date_stamp}/{$this->aws_region}/{$service}/aws4_request";
        
        // Canonical request components (following AWS V4 spec exactly)
        $canonical_uri = '/';
        $canonical_query = '';
        $host = $this->s3_bucket . '.s3.' . $this->aws_region . '.amazonaws.com';
        
        // For HEAD request, payload hash is always SHA256 of empty string
        $payload_hash = hash('sha256', '');
        
        // Canonical headers MUST be lowercase, sorted, and in exact format
        // IMPORTANT: x-amz-content-sha256 is REQUIRED for AWS V4 signature
        $canonical_headers = "host:" . $host . "\n" . 
                           "x-amz-content-sha256:" . $payload_hash . "\n" . 
                           "x-amz-date:" . $amz_date . "\n";
        $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
        
        // Build canonical request (exact AWS V4 format)
        $canonical_request = "HEAD" . "\n" . 
                           $canonical_uri . "\n" . 
                           $canonical_query . "\n" . 
                           $canonical_headers . "\n" . 
                           $signed_headers . "\n" . 
                           $payload_hash;
        
        // String to sign (exact AWS V4 format)
        $string_to_sign = $algorithm . "\n" . 
                         $amz_date . "\n" . 
                         $credential_scope . "\n" . 
                         hash('sha256', $canonical_request);
        
        // Create signing key step by step (AWS V4 specification)
        $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $this->aws_secret_key, true);
        $k_region = hash_hmac('sha256', $this->aws_region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        // Generate final signature
        $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
        
        // Build authorization header (exact AWS V4 format - NO SPACES after commas)
        $authorization = $algorithm . ' ' . 
                        'Credential=' . $this->aws_access_key . '/' . $credential_scope . ',' .
                        'SignedHeaders=' . $signed_headers . ',' .
                        'Signature=' . $signature;
        
        // Set up headers for the request (ensure WordPress sends them correctly)
        $headers = array(
            'Authorization' => $authorization,
            'X-Amz-Content-Sha256' => $payload_hash,
            'X-Amz-Date' => $amz_date,
            'Host' => $host
        );
        
        // Try cURL directly instead of WordPress HTTP API
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $authorization,
                'X-Amz-Content-Sha256: ' . $payload_hash,
                'X-Amz-Date: ' . $amz_date,
                'Host: ' . $host
            )
        ));
        
        $response_raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Parse headers and body from raw response
        if ($response_raw !== false && !empty($response_raw)) {
            $parts = explode("\r\n\r\n", $response_raw, 2);
            $headers_raw = isset($parts[0]) ? $parts[0] : '';
            $body_raw = isset($parts[1]) ? $parts[1] : '';
            
            // Convert to WordPress HTTP API format for consistency
            $response = array(
                'response' => array('code' => $http_code),
                'body' => $body_raw,
                'headers' => array()
            );
            
            // Parse headers into array format
            $header_lines = explode("\r\n", $headers_raw);
            foreach ($header_lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $response['headers'][trim($key)] = trim($value);
                }
            }
            
            $status_code = $http_code;
            $response_body = $body_raw;
            $response_headers = $response['headers'];
            
        } else {
            // If cURL completely failed, fall back to WordPress HTTP API
            $response = wp_remote_head($url, array(
                'headers' => $headers,
                'timeout' => 30,
                'redirection' => 0,
                'blocking' => true,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message(),
                    'details' => 'Both cURL and WordPress HTTP API failed: cURL error = ' . $curl_error . ', WP error = ' . $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
        }
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message(),
                'details' => 'WordPress HTTP API error: ' . $response->get_error_message()
            );
        }
        
        // Parse AWS error details for better debugging
        $error_details = "HTTP {$status_code}";
        if (!empty($response_body) && strpos($response_body, '<Error>') !== false) {
            preg_match('/<Code>(.*?)<\/Code>/', $response_body, $code_matches);
            preg_match('/<Message>(.*?)<\/Message>/', $response_body, $message_matches);
            
            if (!empty($code_matches[1])) {
                $error_details = $code_matches[1];
                if (!empty($message_matches[1])) {
                    $error_details .= ': ' . $message_matches[1];
                }
            }
        }
        
        if (in_array($status_code, array(200, 404))) {
            return array(
                'success' => true,
                'message' => 'S3 connection successful! Bucket is accessible.',
                'details' => array(
                    'region' => $this->aws_region,
                    'bucket' => $this->s3_bucket,
                    'status' => 'Connected'
                )
            );
        } else {
            // Provide specific guidance for common errors
            $error_message = 'S3 connection failed with status: ' . $status_code;
            $suggestions = '';
            
            switch ($status_code) {
                case 403:
                    $suggestions = "\n\n🔍 403 Forbidden - Please check:\n";
                    $suggestions .= "• AWS Access Key and Secret Key are correct\n";
                    $suggestions .= "• IAM user has proper S3 permissions\n";
                    $suggestions .= "• Bucket policy includes your IAM user ARN\n";
                    break;
                case 404:
                    $suggestions = "\n\nBucket not found. Please check:\n• Bucket name is correct\n• Bucket exists in the specified region\n• You have access to this bucket";
                    break;
                case 400:
                    $suggestions = "\n\nBad request. Please check:\n• AWS region is correct\n• Credentials format is valid";
                    break;
            }
            
            return array(
                'success' => false,
                'message' => $error_message,
                'details' => $error_details . $suggestions
            );
        }
    }
    
    /**
     * Initiate multipart upload
     * 
     * @param string $s3_key The S3 object key
     * @param string $content_type MIME type
     * @return array|false Upload initiation data or false on failure
     */
    public function initiate_multipart_upload($s3_key, $content_type) {
        if (!$this->init_s3_client()) {
            error_log('CF7AS S3 Multipart Init: Failed to initialize S3 client');
            return false;
        }
        
        // Use the improved signature V4 method for direct API calls
        $signature_data = $this->create_signature_v4('POST', $s3_key, $content_type, 600, array(), array('uploads' => ''), false);
        
        // URL should use the encoded path
        $path_parts = explode('/', $s3_key);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $encoded_path = implode('/', $encoded_parts);
        $url = "https://{$signature_data['host']}/{$encoded_path}?{$signature_data['canonical_query']}";
        
        error_log('CF7AS S3 Multipart Init: S3 key = ' . $s3_key);
        error_log('CF7AS S3 Multipart Init: Encoded path = ' . $encoded_path);
        error_log('CF7AS S3 Multipart Init: Canonical query = ' . $signature_data['canonical_query']);
        
        $request_headers = array(
            'Authorization' => $signature_data['authorization_header'],
            'X-Amz-Date' => $signature_data['amz_date'],
            'X-Amz-Content-Sha256' => $signature_data['payload_hash'],
            'Host' => $signature_data['host']
        );
        
        error_log('CF7AS S3 Multipart Init: Sending request to ' . $url);
        error_log('CF7AS S3 Multipart Init: Headers - ' . print_r($request_headers, true));
        
        $response = wp_remote_post($url, array(
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7AS S3 Multipart Init Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('CF7AS S3 Multipart Init Response: ' . $status_code . ' - ' . $body);
        
        if ($status_code !== 200) {
            error_log('CF7AS S3 Multipart Init HTTP Error: ' . $status_code . ' - ' . $body);
            return false;
        }
        
        // Parse XML response to get UploadId
        if (function_exists('simplexml_load_string')) {
            $xml = simplexml_load_string($body);
            if ($xml && isset($xml->UploadId)) {
                error_log('CF7AS S3 Multipart Init Success: UploadId = ' . (string) $xml->UploadId);
                return array(
                    'uploadId' => (string) $xml->UploadId,
                    'key' => $s3_key
                );
            }
        }
        
        error_log('CF7AS S3 Multipart Init XML Parse Error: ' . $body);
        return false;
    }
    
    /**
     * Get presigned URL for uploading a part in multipart upload using AWS Signature V4
     * 
     * @param string $s3_key The S3 object key
     * @param string $upload_id Multipart upload ID
     * @param int $part_number Part number
     * @param int $expires Expiration time in seconds
     * @return string|false Presigned URL or false on failure
     */
    public function get_upload_part_url($s3_key, $upload_id, $part_number, $expires = 3600) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        // Use the improved signature V4 method for presigned URLs
        $query_params = array(
            'partNumber' => $part_number,
            'uploadId' => $upload_id
        );
        
        $signature_data = $this->create_signature_v4('PUT', $s3_key, '', $expires, array(), $query_params, true);
        
        $url = "https://{$signature_data['host']}/{$s3_key}?{$signature_data['canonical_query']}";
        
        return $url;
    }
    
    /**
     * Complete multipart upload using AWS Signature V4
     * 
     * @param string $s3_key The S3 object key
     * @param string $upload_id Multipart upload ID
     * @param array $parts Array of parts with ETag and PartNumber
     * @return array|false Result data or false on failure
     */
    public function complete_multipart_upload($s3_key, $upload_id, $parts) {
        if (!$this->init_s3_client()) {
            error_log('CF7AS S3 Complete: Failed to initialize S3 client');
            return false;
        }
        
        error_log('CF7AS S3 Complete: Starting completion for ' . $s3_key . ' with uploadId: ' . $upload_id);
        error_log('CF7AS S3 Complete: Parts count: ' . count($parts));
        
        // Build XML body for complete request
        $xml = '<?xml version="1.0" encoding="UTF-8"?><CompleteMultipartUpload>';
        foreach ($parts as $part) {
            $xml .= '<Part>';
            $xml .= '<PartNumber>' . intval($part['PartNumber']) . '</PartNumber>';
            $xml .= '<ETag>' . htmlspecialchars($part['ETag']) . '</ETag>';
            $xml .= '</Part>';
        }
        $xml .= '</CompleteMultipartUpload>';
        
        error_log('CF7AS S3 Complete: XML body: ' . $xml);
        
        // For completion request, we need to hash the actual content
        $content_hash = hash('sha256', $xml);
        
        // Use the improved signature V4 method for direct API calls with content
        $query_params = array('uploadId' => $upload_id);
        $signature_data = $this->create_signature_v4_with_content('POST', $s3_key, 'text/xml', 600, array(), $query_params, $content_hash);
        
        // URL should use the encoded path
        $path_parts = explode('/', $s3_key);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $encoded_path = implode('/', $encoded_parts);
        $url = "https://{$signature_data['host']}/{$encoded_path}?{$signature_data['canonical_query']}";
        
        $request_headers = array(
            'Authorization' => $signature_data['authorization_header'],
            'X-Amz-Date' => $signature_data['amz_date'],
            'X-Amz-Content-Sha256' => $signature_data['payload_hash'],
            'Host' => $signature_data['host'],
            'Content-Type' => 'text/xml'
        );
        
        error_log('CF7AS S3 Complete: Sending request to ' . $url);
        error_log('CF7AS S3 Complete: Headers - ' . print_r($request_headers, true));
        
        $response = wp_remote_post($url, array(
            'headers' => $request_headers,
            'body' => $xml,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('CF7AS S3 Complete Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('CF7AS S3 Complete Response: ' . $status_code . ' - ' . $body);
        
        if ($status_code === 200) {
            error_log('CF7AS S3 Complete Success');
            return array(
                'location' => "https://{$signature_data['host']}/{$s3_key}",
                'key' => $s3_key
            );
        }
        
        error_log('CF7AS S3 Complete HTTP Error: ' . $status_code . ' - ' . $body);
        return false;
    }
    
    /**
     * Abort multipart upload using AWS Signature V4
     * 
     * @param string $s3_key The S3 object key
     * @param string $upload_id Multipart upload ID
     * @return bool Success status
     */
    public function abort_multipart_upload($s3_key, $upload_id) {
        if (!$this->init_s3_client()) {
            return false;
        }
        
        // Use the improved signature V4 method for direct API calls
        $query_params = array('uploadId' => $upload_id);
        $signature_data = $this->create_signature_v4('DELETE', $s3_key, '', 600, array(), $query_params, false);
        
        // URL should use the encoded path
        $path_parts = explode('/', $s3_key);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $encoded_path = implode('/', $encoded_parts);
        $url = "https://{$signature_data['host']}/{$encoded_path}?{$signature_data['canonical_query']}";
        
        $request_headers = array(
            'Authorization' => $signature_data['authorization_header'],
            'X-Amz-Date' => $signature_data['amz_date'],
            'X-Amz-Content-Sha256' => $signature_data['payload_hash'],
            'Host' => $signature_data['host']
        );
        
        $response = wp_remote_request($url, array(
            'method' => 'DELETE',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 204;
    }
    
    /**
     * Get AWS v4 signing key
     * 
     * @param string $date Date in YYYYMMDD format
     * @param string $region AWS region
     * @param string $service AWS service (s3)
     * @return string Binary signing key
     */
    private function get_signing_key($date, $region, $service) {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->aws_secret_key, true);
        $regionKey = hash_hmac('sha256', $region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
        return hash_hmac('sha256', 'aws4_request', $serviceKey, true);
    }
}
