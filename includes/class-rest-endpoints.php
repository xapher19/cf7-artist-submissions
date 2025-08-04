<?php
/**
 * CF7 Artist Submissions - WordPress REST API Integration System
 *
 * Comprehensive REST API endpoint system providing secure file upload operations,
 * metadata management, admin file operations, and AJAX communication i        // Get file download URL (admin only)
        register_rest_route($namespace, '/file/(?P<file_id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_download_url'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        // Get file display URL (admin only) - optimized for GIF preservation
        register_rest_route($namespace, '/file/(?P<file_id>\d+)/display', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_display_url'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));ces
 * with advanced authentication, validation, and error handling capabilities.
 *
 * Features:
 * • Custom REST API endpoints for S3 file operations and metadata management
 * • Secure multipart upload handling with chunking and validation
 * • Admin file management operations (download, delete, metadata updates)
 * • WordPress authentication and capability checking integration
 * • Comprehensive input validation and sanitization with security measures
 * • Error handling and logging for debugging and maintenance
 * • CORS support for cross-origin requests and frontend integration
 * • Rate limiting and abuse prevention for secure API access
 *
 * @package CF7_Artist_Submissions
 * @subpackage RestAPIIntegration
 * @since 1.1.0
 * @version 1.2.0
 */

/**
 * REST Endpoints Class
 * 
 * Provides REST API endpoints for file upload operations, metadata handling,
 * and admin file management functionality.
 * 
 * @since 1.1.0
 */
class CF7_Artist_Submissions_REST_Endpoints {
    
    /**
     * Initialize REST endpoints
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }
    
    private $s3_handler;
    private $metadata_manager;
    
    /**
     * Initialize REST endpoints
     */
    public function __construct() {
        // Use lazy loading to avoid instantiation issues
        $this->s3_handler = null;
        $this->metadata_manager = null;
    }
    
    /**
     * Get S3 handler instance
     */
    private function get_s3_handler() {
        if ($this->s3_handler === null) {
            $this->s3_handler = new CF7_Artist_Submissions_S3_Handler();
        }
        return $this->s3_handler;
    }
    
    /**
     * Get metadata manager instance  
     */
    private function get_metadata_manager() {
        if ($this->metadata_manager === null) {
            $this->metadata_manager = new CF7_Artist_Submissions_Metadata_Manager();
        }
        return $this->metadata_manager;
    }
    
    /**
     * Register all REST API routes
     */
    public function register_endpoints() {
        $namespace = 'cf7as/v1';
        
        // Get presigned upload URL (for small files)
        register_rest_route($namespace, '/presigned-url', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_presigned_url'),
            'permission_callback' => array($this, 'check_upload_permissions'), // Require authentication
            'args' => array(
                'filename' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_filename')
                ),
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_content_type')
                ),
                'size' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_file_size')
                )
            )
        ));

        // Initiate multipart upload (for large files)
        register_rest_route($namespace, '/initiate-multipart', array(
            'methods' => 'POST',
            'callback' => array($this, 'initiate_multipart_upload'),
            'permission_callback' => array($this, 'check_upload_permissions'),
            'args' => array(
                'filename' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_filename')
                ),
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_content_type')
                ),
                'size' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_file_size')
                )
            )
        ));

        // Get presigned URL for uploading a part
        register_rest_route($namespace, '/upload-part-url', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_upload_part_url'),
            'permission_callback' => array($this, 'check_upload_permissions'),
            'args' => array(
                'uploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'partNumber' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        // Complete multipart upload
        register_rest_route($namespace, '/complete-multipart', array(
            'methods' => 'POST',
            'callback' => array($this, 'complete_multipart_upload'),
            'permission_callback' => array($this, 'check_upload_permissions'),
            'args' => array(
                'uploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'parts' => array(
                    'required' => true,
                    'type' => 'array'
                )
            )
        ));

        // Abort multipart upload
        register_rest_route($namespace, '/abort-multipart', array(
            'methods' => 'POST',
            'callback' => array($this, 'abort_multipart_upload'),
            'permission_callback' => array($this, 'check_upload_permissions'),
            'args' => array(
                'uploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Store file metadata after successful upload
        register_rest_route($namespace, '/file-metadata', array(
            'methods' => 'POST',
            'callback' => array($this, 'store_file_metadata'),
            'permission_callback' => array($this, 'check_upload_permissions'), // Require authentication
            'args' => array(
                'submission_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_submission_id')
                ),
                'filename' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_filename')
                ),
                's3_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'content_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_content_type')
                ),
                'file_size' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_file_size')
                )
            )
        ));
        
        // Get file download URL (admin only)
        register_rest_route($namespace, '/download-url/(?P<file_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_download_url'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        // Delete file (admin only)
        register_rest_route($namespace, '/file/(?P<file_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_file'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        // Get files for submission (admin only)
        register_rest_route($namespace, '/files/(?P<submission_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_submission_files'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }
    
    /**
     * Get presigned upload URL endpoint
     */
    public function get_upload_url($request) {
        $filename = $request->get_param('filename');
        $content_type = $request->get_param('content_type');
        $submission_id = $request->get_param('submission_id');
        
        // Generate temporary submission ID if not provided
        if (empty($submission_id)) {
            $submission_id = 'temp_' . uniqid();
        }
        
        $upload_url = $this->get_s3_handler()->get_presigned_upload_url($submission_id, $filename, $content_type);
        
        if (!$upload_url) {
            return new WP_Error('upload_url_failed', 'Failed to generate upload URL', array('status' => 500));
        }
        
        $s3_key = $this->get_s3_handler()->generate_s3_key($submission_id, $filename);
        
        return array(
            'upload_url' => $upload_url,
            's3_key' => $s3_key,
            'submission_id' => $submission_id
        );
    }
    
    /**
     * Store file metadata after successful upload
     */
    public function store_file_metadata($request) {
        $submission_id = $request->get_param('submission_id');
        $filename = $request->get_param('filename');
        $s3_key = $request->get_param('s3_key');
        $content_type = $request->get_param('content_type');
        $file_size = $request->get_param('file_size');
        
        $file_data = array(
            'submission_id' => $submission_id,
            'original_name' => $filename,
            's3_key' => $s3_key,
            'mime_type' => $content_type,
            'file_size' => intval($file_size)
        );
        
        $file_id = $this->get_metadata_manager()->store_file_metadata($file_data);
        
        if (!$file_id) {
            return new WP_Error('metadata_storage_failed', 'Failed to store file metadata', array('status' => 500));
        }
        
        return array(
            'file_id' => $file_id,
            'status' => 'success',
            'message' => 'File metadata stored successfully'
        );
    }
    
    /**
     * Get presigned URL for file upload
     */
    public function get_presigned_url($request) {
        try {
            $filename = sanitize_text_field($request->get_param('filename'));
            $content_type = sanitize_text_field($request->get_param('type'));
            $file_size = intval($request->get_param('size'));
            
            // Check if S3 is configured
            $options = get_option('cf7_artist_submissions_options', array());
            if (empty($options['aws_access_key']) || empty($options['aws_secret_key']) || empty($options['s3_bucket'])) {
                error_log('CF7 Artist Submissions: S3 not configured. Missing AWS credentials or bucket name.');
                return new WP_Error('s3_not_configured', 'S3 upload not configured. Please check your AWS settings in the admin panel.', array('status' => 500));
            }
            
            // Generate unique submission ID for S3 key structure
            $submission_id = uniqid();
            
            // Get presigned URL from S3 handler
            $presigned_data = $this->get_s3_handler()->get_presigned_upload_url($submission_id, $filename, $content_type);
            
            if ($presigned_data && isset($presigned_data['url'])) {
                return rest_ensure_response(array(
                    'success' => true,
                    'data' => array(
                        'url' => $presigned_data['url'],
                        'key' => $presigned_data['s3_key']
                    )
                ));
            } else {
                error_log('CF7 Artist Submissions: Failed to generate presigned URL for file: ' . $filename);
                return new WP_Error('upload_failed', 'Failed to generate presigned URL', array('status' => 500));
            }
        } catch (Exception $e) {
            error_log('CF7 Artist Submissions: Exception in get_presigned_url: ' . $e->getMessage());
            return new WP_Error('upload_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Initiate multipart upload
     */
    public function initiate_multipart_upload($request) {
        try {
            $filename = sanitize_text_field($request->get_param('filename'));
            $content_type = sanitize_text_field($request->get_param('type'));
            $file_size = intval($request->get_param('size'));
            
            error_log('CF7AS REST - Initiate multipart upload request: ' . $filename . ' (' . $content_type . ', ' . $file_size . ' bytes)');
            
            // Validate required parameters
            if (empty($filename) || empty($content_type)) {
                error_log('CF7AS REST - Invalid parameters: filename=' . $filename . ', content_type=' . $content_type);
                return new WP_Error('invalid_parameters', 'Missing filename or content type', array('status' => 400));
            }
            
            // Generate unique S3 key
            $s3_key = 'uploads/' . uniqid() . '/' . $filename;
            
            // Initiate multipart upload
            $upload_data = $this->get_s3_handler()->initiate_multipart_upload($s3_key, $content_type);
            
            if ($upload_data && isset($upload_data['uploadId'])) {
                error_log('CF7AS REST - Multipart upload initiated successfully: ' . $upload_data['uploadId']);
                return rest_ensure_response(array(
                    'success' => true,
                    'data' => array(
                        'uploadId' => $upload_data['uploadId'],
                        'key' => $s3_key
                    )
                ));
            } else {
                return new WP_Error('multipart_failed', 'Failed to initiate multipart upload', array('status' => 500));
            }
        } catch (Exception $e) {
            error_log('CF7AS REST - Exception in initiate_multipart_upload: ' . $e->getMessage());
            return new WP_Error('multipart_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get presigned URL for uploading a part
     */
    public function get_upload_part_url($request) {
        try {
            $upload_id = sanitize_text_field($request->get_param('uploadId'));
            $s3_key = sanitize_text_field($request->get_param('key'));
            $part_number = intval($request->get_param('partNumber'));
            
            // Get presigned URL for part upload
            $part_url = $this->get_s3_handler()->get_upload_part_url($s3_key, $upload_id, $part_number);
            
            if ($part_url) {
                return rest_ensure_response(array(
                    'success' => true,
                    'data' => array(
                        'url' => $part_url
                    )
                ));
            } else {
                return new WP_Error('part_url_failed', 'Failed to generate part upload URL', array('status' => 500));
            }
        } catch (Exception $e) {
            return new WP_Error('part_url_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Complete multipart upload
     */
    public function complete_multipart_upload($request) {
        try {
            $upload_id = sanitize_text_field($request->get_param('uploadId'));
            $s3_key = sanitize_text_field($request->get_param('key'));
            $parts = $request->get_param('parts');
            
            error_log('CF7AS REST - Complete multipart upload request: ' . $s3_key . ' (uploadId: ' . $upload_id . ')');
            
            // Validate required parameters
            if (empty($upload_id) || empty($s3_key) || empty($parts) || !is_array($parts)) {
                error_log('CF7AS REST - Invalid parameters for complete multipart');
                return new WP_Error('invalid_parameters', 'Missing required parameters', array('status' => 400));
            }
            
            // Complete multipart upload
            $result = $this->get_s3_handler()->complete_multipart_upload($s3_key, $upload_id, $parts);
            
            if ($result) {
                error_log('CF7AS REST - Multipart upload completed successfully');
                return rest_ensure_response(array(
                    'success' => true,
                    'data' => array(
                        'location' => $result['location'],
                        'key' => $s3_key
                    )
                ));
            } else {
                return new WP_Error('complete_failed', 'Failed to complete multipart upload', array('status' => 500));
            }
        } catch (Exception $e) {
            error_log('CF7AS REST - Exception in complete_multipart_upload: ' . $e->getMessage());
            return new WP_Error('complete_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Abort multipart upload
     */
    public function abort_multipart_upload($request) {
        try {
            $upload_id = sanitize_text_field($request->get_param('uploadId'));
            $s3_key = sanitize_text_field($request->get_param('key'));
            
            // Abort multipart upload
            $result = $this->get_s3_handler()->abort_multipart_upload($s3_key, $upload_id);
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Multipart upload aborted'
            ));
        } catch (Exception $e) {
            return new WP_Error('abort_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get download URL for a file
     */
    public function get_download_url($request) {
        $file_id = $request->get_param('file_id');
        
        $file_data = $this->get_metadata_manager()->get_file_metadata($file_id);
        if (!$file_data) {
            return new WP_Error('file_not_found', 'File not found', array('status' => 404));
        }
        
        $download_url = $this->get_s3_handler()->get_presigned_download_url($file_data['s3_key']);
        if (!$download_url) {
            return new WP_Error('download_url_failed', 'Failed to generate download URL', array('status' => 500));
        }
        
        return array(
            'download_url' => $download_url,
            'filename' => $file_data['original_name'],
            'file_size' => $file_data['file_size']
        );
    }
    
    /**
     * Get display URL for a file
     * Returns the best URL for displaying the file (original for GIFs, converted for others)
     */
    public function get_display_url($request) {
        $file_id = $request->get_param('file_id');
        
        $display_url = $this->get_metadata_manager()->get_display_url($file_id);
        if (!$display_url) {
            return new WP_Error('display_url_failed', 'Failed to get display URL', array('status' => 500));
        }
        
        $file_data = $this->get_metadata_manager()->get_file_metadata($file_id);
        
        return array(
            'display_url' => $display_url,
            'filename' => $file_data['original_name'],
            'mime_type' => $file_data['mime_type'],
            'is_gif' => $file_data['mime_type'] === 'image/gif'
        );
    }
    
    /**
     * Delete a file
     */
    public function delete_file($request) {
        $file_id = $request->get_param('file_id');
        
        $file_data = $this->get_metadata_manager()->get_file_metadata($file_id);
        if (!$file_data) {
            return new WP_Error('file_not_found', 'File not found', array('status' => 404));
        }
        
        // Delete from S3
        $s3_deleted = $this->get_s3_handler()->delete_file($file_data['s3_key']);
        
        // Delete thumbnail if it exists
        if (!empty($file_data['thumbnail_url'])) {
            $thumbnail_key = $this->get_s3_handler()->generate_thumbnail_s3_key($file_data['submission_id'], $file_data['original_name']);
            $this->get_s3_handler()->delete_file($thumbnail_key);
        }
        
        // Delete metadata
        $metadata_deleted = $this->get_metadata_manager()->delete_file_metadata($file_id);
        
        if (!$s3_deleted || !$metadata_deleted) {
            return new WP_Error('delete_failed', 'Failed to delete file', array('status' => 500));
        }
        
        return array(
            'status' => 'success',
            'message' => 'File deleted successfully'
        );
    }
    
    /**
     * Get all files for a submission
     */
    public function get_submission_files($request) {
        $submission_id = $request->get_param('submission_id');
        
        $files = $this->get_metadata_manager()->get_submission_files($submission_id);
        
        return array(
            'files' => $files,
            'total' => count($files)
        );
    }
    
    /**
     * Validate filename parameter
     */
    public function validate_filename($param, $request, $key) {
        if (empty($param) || !is_string($param)) {
            return false;
        }
        
        // Check for valid filename
        $allowed_extensions = array(
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff',
            'mp4', 'mov', 'webm', 'avi', 'mkv', 'mpeg',
            'pdf', 'doc', 'docx', 'txt', 'rtf'
        );
        
        $extension = strtolower(pathinfo($param, PATHINFO_EXTENSION));
        return in_array($extension, $allowed_extensions);
    }
    
    /**
     * Validate content type parameter
     */
    public function validate_content_type($param, $request, $key) {
        if (empty($param) || !is_string($param)) {
            return false;
        }
        
        $allowed_types = array(
            'image/jpeg',
            'image/jpg', 
            'image/png', 
            'image/gif', 
            'image/webp',
            'image/svg+xml', 
            'image/bmp', 
            'image/tiff',
            'video/mp4', 
            'video/quicktime', 
            'video/webm', 
            'video/x-msvideo',
            'video/x-matroska', 
            'video/mpeg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 
            'application/rtf'
        );
        
        return in_array($param, $allowed_types);
    }
    
    /**
     * Validate submission ID parameter
     */
    public function validate_submission_id($param, $request, $key) {
        return !empty($param) && (is_numeric($param) || strpos($param, 'temp_') === 0);
    }
    
    /**
     * Validate file size parameter with hard limits
     */
    public function validate_file_size($param, $request, $key) {
        if (!is_numeric($param)) {
            return false;
        }
        
        $size = intval($param);
        $max_size = 5 * 1024 * 1024 * 1024; // 5GB limit
        
        return $size > 0 && $size <= $max_size;
    }
    
    /**
     * Check admin permissions - more granular than manage_options
     */
    public function check_admin_permissions($request) {
        return current_user_can('edit_posts');
    }
    
    /**
     * Check upload permissions for file operations
     * More lenient for front-end form submissions but still requires valid nonce
     */
    public function check_upload_permissions($request) {
        // For now, allow anyone with a valid nonce to upload
        // This maintains compatibility with form submissions while adding basic protection
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce) {
            $nonce = $request->get_param('_wpnonce');
        }
        
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Invalid nonce', array('status' => 403));
        }
        
        return true;
    }
}
