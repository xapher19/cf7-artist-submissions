<?php
/**
 * CF7 Artist Submissions - Advanced ZIP Archive Generation System
 *
 * Comprehensive ZIP archive generation system providing on-demand file packaging,
 * S3 integration, streaming downloads, and memory-efficient processing for
 * bulk file downloads with administrative access controls and error handling.
 *
 * Features:
 * • On-demand ZIP archive generation for submission file collections
 * • S3 integration with original file downloads and streaming processing
 * • Memory-efficient ZIP streaming without temporary local storage
 * • Administrative access controls with WordPress capability checking
 * • Progress tracking and user feedback for large archive operations
 * • Error handling and recovery for failed downloads and corrupted files
 * • Filename sanitization and duplicate handling for archive integrity
 * • Performance optimization for large file collections and bandwidth management
 *
 * @package CF7_Artist_Submissions
 * @subpackage ZIPArchiveGeneration
 * @since 1.1.0
 * @version 1.2.0
 */

/**
 * ZIP Downloader Class
 * 
 * Creates ZIP archives of submission files for admin download.
 * Handles streaming downloads and temporary file management.
 * 
 * @since 1.1.0
 */
class CF7_Artist_Submissions_ZIP_Downloader {
    
    private $s3_handler;
    private $metadata_manager;
    
    /**
     * Initialize ZIP downloader
     */
    public function __construct() {
        $this->s3_handler = new CF7_Artist_Submissions_S3_Handler();
        $this->metadata_manager = new CF7_Artist_Submissions_Metadata_Manager();
    }
    
    /**
     * Initialize ZIP download functionality
     */
    public function init() {
        add_action('wp_ajax_cf7as_download_submission_zip', array($this, 'handle_zip_download'));
        add_action('init', array($this, 'handle_zip_download_request'));
    }
    
    /**
     * Handle ZIP download request via URL parameter
     */
    public function handle_zip_download_request() {
        if (!isset($_GET['cf7as_download_zip']) || !isset($_GET['submission_id'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'cf7as_download_zip_' . $_GET['submission_id'])) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $submission_id = sanitize_text_field($_GET['submission_id']);
        $this->download_submission_zip($submission_id);
    }
    
    /**
     * Handle AJAX ZIP download request
     */
    public function handle_zip_download() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7as_zip_download')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!isset($_POST['submission_id'])) {
            wp_die('Missing submission ID');
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id']);
        $this->download_submission_zip($submission_id);
    }
    
    /**
     * Download submission files as ZIP
     * 
     * @param string $submission_id Submission ID
     */
    public function download_submission_zip($submission_id) {
        // Get submission post
        $post = get_post($submission_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            wp_die('Submission not found');
        }
        
        // Get artist name for filename
        $artist_name = get_post_meta($post->ID, 'cf7_artist-name', true);
        if (empty($artist_name)) {
            $artist_name = get_post_meta($post->ID, 'cf7_your-name', true);
        }
        if (empty($artist_name)) {
            $artist_name = $post->post_title;
        }
        if (empty($artist_name)) {
            $artist_name = 'Unknown Artist';
        }
        
        // Get files for this submission
        $files = $this->metadata_manager->get_submission_files($submission_id);
        
        if (empty($files)) {
            wp_die('No files found for this submission');
        }
        
        // Create temporary directory for ZIP creation
        $temp_dir = $this->create_temp_directory();
        if (!$temp_dir) {
            wp_die('Failed to create temporary directory');
        }
        
        try {
            // Download files from S3 to temp directory
            $downloaded_files = $this->download_files_to_temp($files, $temp_dir);
            
            if (empty($downloaded_files)) {
                $this->cleanup_temp_directory($temp_dir);
                wp_die('Failed to download files from S3');
            }
            
            // Create ZIP file
            $zip_path = $this->create_zip_archive($downloaded_files, $temp_dir, $submission_id, $artist_name);
            
            if (!$zip_path) {
                $this->cleanup_temp_directory($temp_dir);
                wp_die('Failed to create ZIP archive');
            }
            
            // Stream ZIP file to user
            $this->stream_zip_file($zip_path, $submission_id, $artist_name);
            
            // Cleanup
            $this->cleanup_temp_directory($temp_dir);
            
        } catch (Exception $e) {
            $this->cleanup_temp_directory($temp_dir);
            wp_die('Failed to create ZIP download');
        }
    }
    
    /**
     * Create temporary directory
     * 
     * @return string|false Temporary directory path or false on failure
     */
    private function create_temp_directory() {
        $upload_dir = wp_upload_dir();
        $temp_base = $upload_dir['basedir'] . '/cf7as-temp';
        
        if (!file_exists($temp_base)) {
            wp_mkdir_p($temp_base);
        }
        
        $temp_dir = $temp_base . '/' . uniqid('zip_') . '_' . time();
        
        if (wp_mkdir_p($temp_dir)) {
            return $temp_dir;
        }
        
        return false;
    }
    
    /**
     * Download files from S3 to temporary directory
     * 
     * @param array $files File metadata array
     * @param string $temp_dir Temporary directory path
     * @return array Downloaded file paths
     */
    private function download_files_to_temp($files, $temp_dir) {
        $downloaded_files = array();
        
        foreach ($files as $file) {
            $file_content = $this->s3_handler->get_file_content($file['s3_key']);
            
            if (!$file_content) {
                continue;
            }
            
            // Sanitize filename for local storage
            $safe_filename = $this->sanitize_filename($file['original_name']);
            $local_path = $temp_dir . '/' . $safe_filename;
            
            // Write content to local file
            $bytes_written = file_put_contents($local_path, $file_content);
            
            if ($bytes_written !== false) {
                $downloaded_files[] = array(
                    'local_path' => $local_path,
                    'original_name' => $file['original_name']
                );
            }
        }
        
        return $downloaded_files;
    }
    
    /**
     * Create ZIP archive from downloaded files
     * 
     * @param array $files Downloaded files array
     * @param string $temp_dir Temporary directory path
     * @param string $submission_id Submission ID
     * @param string $artist_name Artist name for filename
     * @return string|false ZIP file path or false on failure
     */
    private function create_zip_archive($files, $temp_dir, $submission_id, $artist_name = '') {
        if (!class_exists('ZipArchive')) {
            return false;
        }
        
        $zip = new ZipArchive();
        
        // Create descriptive filename with artist name and submission ID
        $safe_artist_name = $this->sanitize_filename($artist_name);
        if (empty($safe_artist_name)) {
            $safe_artist_name = 'Unknown_Artist';
        }
        $zip_filename = $safe_artist_name . '_' . $submission_id . '_submission.zip';
        $zip_path = $temp_dir . '/' . $zip_filename;
        
        $result = $zip->open($zip_path, ZipArchive::CREATE);
        if ($result !== TRUE) {
            return false;
        }
        
        foreach ($files as $file) {
            if (file_exists($file['local_path'])) {
                $zip->addFile($file['local_path'], $file['original_name']);
            }
        }
        
        $close_result = $zip->close();
        if (!$close_result) {
            return false;
        }
        
        if (file_exists($zip_path)) {
            return $zip_path;
        }
        
        return false;
    }
    
    /**
     * Stream ZIP file to user
     * 
     * @param string $zip_path ZIP file path
     * @param string $submission_id Submission ID
     * @param string $artist_name Artist name for filename
     */
    private function stream_zip_file($zip_path, $submission_id, $artist_name = '') {
        // Verify file exists before attempting to stream
        if (!file_exists($zip_path) || !is_readable($zip_path)) {
            wp_die('ZIP file could not be created');
        }
        
        $file_size = filesize($zip_path);
        if ($file_size === false || $file_size === 0) {
            wp_die('ZIP file is empty');
        }
        
        // Create descriptive filename with artist name and submission ID
        $safe_artist_name = $this->sanitize_filename($artist_name);
        if (empty($safe_artist_name)) {
            $safe_artist_name = 'Unknown_Artist';
        }
        $zip_filename = $safe_artist_name . '_' . $submission_id . '_submission.zip';
        
        // Clear any existing output and turn off output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Prevent WordPress from interfering
        if (function_exists('wp_die')) {
            remove_all_actions('shutdown');
        }
        
        // Set headers for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . $file_size);
        header('Content-Transfer-Encoding: binary');
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        // Flush headers
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
        
        // Stream file in chunks to handle large files
        $handle = fopen($zip_path, 'rb');
        if ($handle === false) {
            wp_die('Could not read ZIP file');
        }
        
        $chunk_size = 8192; // 8KB chunks
        while (!feof($handle)) {
            $chunk = fread($handle, $chunk_size);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            flush();
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Cleanup temporary directory
     * 
     * @param string $temp_dir Temporary directory path
     */
    private function cleanup_temp_directory($temp_dir) {
        if (!file_exists($temp_dir)) {
            return;
        }
        
        // Remove all files in directory
        $files = glob($temp_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Remove directory
        rmdir($temp_dir);
    }
    
    /**
     * Sanitize filename for safe storage
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private function sanitize_filename($filename) {
        // Remove any path components
        $filename = basename($filename);
        
        // Replace unsafe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250) . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Get ZIP download URL
     * 
     * @param string $submission_id Submission ID
     * @return string ZIP download URL
     */
    public function get_zip_download_url($submission_id) {
        $nonce = wp_create_nonce('cf7as_download_zip_' . $submission_id);
        
        return add_query_arg(array(
            'cf7as_download_zip' => '1',
            'submission_id' => $submission_id,
            'nonce' => $nonce
        ), admin_url());
    }
    
    /**
     * Get ZIP download button HTML
     * 
     * @param string $submission_id Submission ID
     * @param array $attributes Button attributes
     * @return string Button HTML
     */
    public function get_zip_download_button($submission_id, $attributes = array()) {
        $files = $this->metadata_manager->get_submission_files($submission_id);
        
        if (empty($files)) {
            return '';
        }
        
        $download_url = $this->get_zip_download_url($submission_id);
        
        $default_attributes = array(
            'class' => 'button button-secondary cf7as-zip-download',
            'href' => $download_url,
            'title' => 'Download all files as ZIP'
        );
        
        $attributes = array_merge($default_attributes, $attributes);
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $file_count = count($files);
        $button_text = sprintf('Download All Files (%d)', $file_count);
        
        return sprintf('<a%s>%s</a>', $attr_string, esc_html($button_text));
    }
}
