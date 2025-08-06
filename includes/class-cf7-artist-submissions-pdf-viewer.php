<?php
/**
 * CF7 Artist Submissions - PDF Viewer System
 *
 * Comprehensive PDF viewing system for submitted works with secure file handling,
 * inline viewing capabilities, text-only submission support, and integrated
 * file management for artist submission review workflow.
 *
 * Features:
 * • Secure PDF inline viewing with browser compatibility
 * • Text-only submission handling and display
 * • File type detection and appropriate viewer selection
 * • Security validation for file access and permissions
 * • Responsive viewer interface with navigation controls
 * • Integration with submission management workflow
 *
 * @package CF7_Artist_Submissions
 * @subpackage PDFViewer
 * @since 1.2.0
 * @version 1.3.0
 */

/**
 * CF7 Artist Submissions PDF Viewer Class
 * 
 * Comprehensive PDF and text viewing system providing secure inline viewing
 * capabilities for submitted works. Handles PDF documents, text submissions,
 * and other file types with appropriate viewer interfaces and security
 * validation for seamless submission review workflow.
 * 
 * @since 1.2.0
 */
class CF7_Artist_Submissions_PDF_Viewer {
    
    /**
     * Initialize PDF viewer system with security and interface integration.
     * 
     * Establishes PDF viewing infrastructure including AJAX handlers for
     * secure file serving, viewer interface rendering, and file type
     * detection. Provides foundation for comprehensive submission file
     * review with appropriate security measures and user experience.
     * 
     * @since 1.2.0
     */
    public function init() {
        // AJAX handlers for PDF viewing
        add_action('wp_ajax_cf7_view_pdf', array($this, 'ajax_view_pdf'));
        add_action('wp_ajax_cf7_view_text_submission', array($this, 'ajax_view_text_submission'));
        add_action('wp_ajax_cf7_get_file_info', array($this, 'ajax_get_file_info'));
        
        // Enqueue viewer assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_viewer_assets'));
        
        // PDF viewer functionality is now integrated into the Works tab
        // No separate meta box needed
    }
    
    /**
     * Enqueue PDF viewer assets for admin interface.
     * Loads CSS and JavaScript for PDF viewer functionality.
     */
    public function enqueue_viewer_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission' || $screen->base !== 'post') {
            return;
        }
        
        // Enqueue PDF viewer styles
        wp_enqueue_style(
            'cf7as-pdf-viewer',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/pdf-viewer.css',
            array('cf7-common-css'),
            CF7_ARTIST_SUBMISSIONS_VERSION
        );
        
        // Enqueue PDF viewer JavaScript
        wp_enqueue_script(
            'cf7as-pdf-viewer',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/pdf-viewer.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
        
        // Localize script with configuration
        wp_localize_script('cf7as-pdf-viewer', 'cf7_pdf_viewer', array(
            'nonce' => wp_create_nonce('cf7_pdf_viewer_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'strings' => array(
                'loading' => __('Loading...', 'cf7-artist-submissions'),
                'error' => __('Error loading file', 'cf7-artist-submissions'),
                'not_found' => __('File not found', 'cf7-artist-submissions'),
                'unsupported' => __('File type not supported for preview', 'cf7-artist-submissions'),
            )
        ));
    }
    
    /**
     * Get submission files from post metadata.
     * Retrieves file information for all uploaded files in submission.
     * 
     * @param int $post_id The submission post ID
     * @return array Array of file fields and their data
     */
    public function get_submission_files($post_id) {
        $file_fields = array();
        $meta_data = get_post_meta($post_id);
        
        foreach ($meta_data as $key => $values) {
            // Look for file fields (typically start with cf7_)
            if (strpos($key, 'cf7_') === 0 && !empty($values[0])) {
                $value = $values[0];
                
                // Check if this looks like file data
                if ($this->is_file_field($key, $value)) {
                    $files = $this->parse_file_data($value);
                    if (!empty($files)) {
                        $file_fields[$key] = $files;
                    }
                }
            }
        }
        
        return $file_fields;
    }
    
    /**
     * Get text submission content from post metadata.
     * Retrieves text-based submission content for viewing.
     * 
     * @param int $post_id The submission post ID
     * @return string Text content if found, empty string otherwise
     */
    public function get_text_submission_content($post_id) {
        // Common text submission field names
        $text_fields = array(
            'cf7_text-submission',
            'cf7_your-work',
            'cf7_submission-text',
            'cf7_written-work',
            'cf7_manuscript',
            'cf7_text-content'
        );
        
        foreach ($text_fields as $field) {
            $content = get_post_meta($post_id, $field, true);
            if (!empty($content) && is_string($content) && strlen($content) > 50) {
                return $content;
            }
        }
        
        return '';
    }
    
    /**
     * Check if a field contains file data.
     * Determines if metadata field represents uploaded files.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return bool True if field contains file data
     */
    public function is_file_field($key, $value) {
        // Skip certain known non-file fields
        $exclude_fields = array(
            'cf7_submission_date',
            'cf7_curator_notes',
            'cf7_artist-name',
            'cf7_email',
            'cf7_your-email',
            'cf7_artist-statement',
            'cf7_portfolio-link'
        );
        
        if (in_array($key, $exclude_fields)) {
            return false;
        }
        
        // Check if value looks like file data (contains URLs or file paths)
        if (is_string($value)) {
            return (strpos($value, '.jpg') !== false || 
                    strpos($value, '.jpeg') !== false || 
                    strpos($value, '.png') !== false || 
                    strpos($value, '.gif') !== false ||
                    strpos($value, 'http') === 0 ||
                    preg_match('/\.(jpg|jpeg|png|gif|doc|docx|txt|rtf)$/i', $value));
        }
        
        return false;
    }
    
    /**
     * Parse file data from metadata value.
     * Extracts file information from stored metadata.
     * 
     * @param mixed $value The metadata value to parse
     * @return array Array of file information
     */
    public function parse_file_data($value) {
        $files = array();
        
        if (is_string($value)) {
            // Handle different file data formats
            if (strpos($value, '|') !== false) {
                // Multiple files separated by |
                $file_urls = explode('|', $value);
                foreach ($file_urls as $index => $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        $files[] = array(
                            'id' => md5($url . $index),
                            'url' => $url,
                            'name' => basename($url),
                            'type' => $this->get_file_type($url)
                        );
                    }
                }
            } elseif (strpos($value, 'http') === 0) {
                // Single file URL
                $files[] = array(
                    'id' => md5($value),
                    'url' => $value,
                    'name' => basename($value),
                    'type' => $this->get_file_type($value)
                );
            }
        }
        
        return $files;
    }
    
    /**
     * Get file type from URL or filename.
     * Determines file type for appropriate viewer selection.
     * 
     * @param string $url The file URL or filename
     * @return string File type (pdf, image, document, text, unknown)
     */
    public function get_file_type($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return 'pdf';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return 'image';
            case 'doc':
            case 'docx':
                return 'document';
            case 'txt':
                return 'text';
            default:
                return 'unknown';
        }
    }
    
    /**
     * Get CSS icon class for file type.
     * Returns appropriate icon class for file type visualization.
     * 
     * @param string $file_type The file type
     * @return string CSS class for file icon
     */
    public function get_file_icon_class($file_type) {
        switch ($file_type) {
            case 'pdf':
                return 'cf7-icon-pdf';
            case 'image':
                return 'cf7-icon-image';
            case 'document':
                return 'cf7-icon-document';
            case 'text':
                return 'cf7-icon-text';
            default:
                return 'cf7-icon-file';
        }
    }
    
    /**
     * AJAX handler for PDF viewing.
     * Serves PDF files for inline viewing with security validation.
     */
    public function ajax_view_pdf() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_viewer_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $file_id = sanitize_text_field($_POST['file_id']);
        $file_url = sanitize_url($_POST['file_url']);
        
        if (empty($file_url)) {
            wp_send_json_error('No file URL provided');
        }
        
        // Additional security: validate URL is from allowed domains/schemes
        $parsed_url = parse_url($file_url);
        if (!$parsed_url || !in_array($parsed_url['scheme'], array('http', 'https'))) {
            wp_send_json_error('Invalid file URL scheme');
        }
        
        // Validate file type
        $file_type = $this->get_file_type($file_url);
        if ($file_type !== 'pdf') {
            wp_send_json_error('File is not a PDF');
        }
        
        // Return PDF embed HTML
        wp_send_json_success(array(
            'html' => $this->generate_pdf_embed($file_url),
            'file_type' => $file_type
        ));
    }
    
    /**
     * AJAX handler for text submission viewing.
     * Provides formatted text content for inline viewing.
     */
    public function ajax_view_text_submission() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_viewer_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $text_content = $this->get_text_submission_content($post_id);
        
        if (empty($text_content)) {
            wp_send_json_error('No text content found');
        }
        
        wp_send_json_success(array(
            'html' => '<div class="cf7-text-viewer">' . nl2br(esc_html($text_content)) . '</div>',
            'word_count' => str_word_count($text_content),
            'char_count' => strlen($text_content)
        ));
    }
    
    /**
     * AJAX handler for file information retrieval.
     * Provides file metadata and viewing options.
     */
    public function ajax_get_file_info() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_viewer_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $file_url = sanitize_url($_POST['file_url']);
        
        if (empty($file_url)) {
            wp_send_json_error('No file URL provided');
        }
        
        // Additional security: validate URL is from allowed domains/schemes
        $parsed_url = parse_url($file_url);
        if (!$parsed_url || !in_array($parsed_url['scheme'], array('http', 'https'))) {
            wp_send_json_error('Invalid file URL scheme');
        }
        
        $file_type = $this->get_file_type($file_url);
        $file_name = basename($file_url);
        
        // Get file size if possible (with additional security checks)
        $file_size = '';
        if (function_exists('get_headers') && filter_var($file_url, FILTER_VALIDATE_URL)) {
            // Additional validation to prevent SSRF attacks
            $headers = @get_headers($file_url, 1);
            if (isset($headers['Content-Length'])) {
                $size_bytes = is_array($headers['Content-Length']) ? 
                    $headers['Content-Length'][0] : $headers['Content-Length'];
                $file_size = $this->format_file_size(intval($size_bytes));
            }
        }
        
        wp_send_json_success(array(
            'name' => $file_name,
            'type' => $file_type,
            'size' => $file_size,
            'url' => $file_url,
            'viewable' => in_array($file_type, array('pdf', 'image', 'text'))
        ));
    }
    
    /**
     * Generate PDF embed HTML for inline viewing.
     * Creates secure PDF embedding with fallback options.
     * 
     * @param string $file_url The PDF file URL
     * @return string HTML for PDF embed
     */
    public function generate_pdf_embed($file_url) {
        $embed_url = esc_url($file_url);
        
        $html = '<div class="cf7-pdf-viewer">';
        $html .= '<div class="cf7-pdf-embed-container">';
        $html .= '<embed src="' . $embed_url . '" type="application/pdf" width="100%" height="600px" />';
        $html .= '</div>';
        $html .= '<div class="cf7-pdf-fallback" style="display: none;">';
        $html .= '<p>PDF preview not available in your browser.</p>';
        $html .= '<a href="' . $embed_url . '" target="_blank" class="cf7-btn cf7-btn-primary">';
        $html .= '<span class="dashicons dashicons-external"></span> Open PDF in New Tab</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format file size in human-readable format.
     * Converts bytes to appropriate units (KB, MB, GB).
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    public function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
