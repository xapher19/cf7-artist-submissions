<?php
/**
 * PDF Export functionality for CF7 Artist Submissions
 */

class CF7_Artist_Submissions_PDF_Export {
    
    public function init() {
        add_action('wp_ajax_cf7_export_submission_pdf', array($this, 'handle_pdf_export'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        // Meta box is now handled by the tabs interface
        // add_action('add_meta_boxes', array($this, 'add_pdf_export_meta_box'), 20);
    }
    
    /**
     * Enqueue scripts for PDF export
     */
    public function enqueue_scripts($hook) {
        global $post;
        
        if ($hook === 'post.php' && isset($post) && $post->post_type === 'cf7_submission') {
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
                'export_text' => __('Exporting PDF...', 'cf7-artist-submissions'),
                'success_text' => __('PDF exported successfully!', 'cf7-artist-submissions'),
                'error_text' => __('Error exporting PDF', 'cf7-artist-submissions')
            ));
        }
    }
    
    /**
     * Add PDF export meta box
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
     * Render PDF export meta box
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
                        <input type="checkbox" name="include_notes"> 
                        <?php _e('Include Curator Notes', 'cf7-artist-submissions'); ?>
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
        </style>
        <?php
    }
    
    /**
     * Handle AJAX PDF export request
     */
    public function handle_pdf_export() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_pdf_export_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $options = array(
            'include_personal_info' => isset($_POST['include_personal_info']) && $_POST['include_personal_info'] == '1',
            'include_works' => isset($_POST['include_works']) && $_POST['include_works'] == '1',
            'include_notes' => isset($_POST['include_notes']) && $_POST['include_notes'] == '1',
            'confidential_watermark' => isset($_POST['confidential_watermark']) && $_POST['confidential_watermark'] == '1'
        );
        
        $result = $this->generate_pdf($post_id, $options);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Generate PDF for submission
     */
    private function generate_pdf($post_id, $options = array()) {
        // Check if post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            return array('success' => false, 'message' => 'Submission not found');
        }
        
        try {
            // Generate HTML content
            $html = $this->generate_pdf_content($post_id, $options);
            
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
    
    /**
     * Wrap HTML content in complete document
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
     * Generate HTML content for PDF
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
     * Get file fields from submission
     */
    private function get_file_fields($post_id) {
        // Start with an empty array of file URLs to display
        $file_urls = array();
        
        // APPROACH 1: Check for standard file format (cf7_file_your-work)
        $standard_files = get_post_meta($post_id, 'cf7_file_your-work', true);
        if (!empty($standard_files)) {
            if (is_array($standard_files)) {
                $file_urls = $standard_files;
            } else {
                $file_urls = array($standard_files);
            }
        }
        
        // APPROACH 2: If no files found, check for comma-separated URLs in cf7_your-work
        if (empty($file_urls)) {
            $comma_separated_urls = get_post_meta($post_id, 'cf7_your-work', true);
            if (!empty($comma_separated_urls)) {
                // Split by commas
                $file_urls = array_map('trim', explode(',', $comma_separated_urls));
            }
        }
        
        // APPROACH 3: Check for any other file fields
        if (empty($file_urls)) {
            $meta_keys = get_post_custom_keys($post_id);
            if (!empty($meta_keys)) {
                foreach ($meta_keys as $key) {
                    if (substr($key, 0, 8) === 'cf7_file_') {
                        $file_data = get_post_meta($post_id, $key, true);
                        
                        // Handle both string and array values
                        if (is_array($file_data)) {
                            $file_urls = array_merge($file_urls, $file_data);
                        } else {
                            $file_urls[] = $file_data;
                        }
                    }
                }
            }
        }
        
        // Return files with field names for proper organization
        $file_fields = array();
        if (!empty($file_urls)) {
            foreach ($file_urls as $index => $url) {
                if (!empty($url)) {
                    $file_fields['work_' . ($index + 1)] = $url;
                }
            }
        }
        
        return $file_fields;
    }
    
    /**
     * Format work item for PDF
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
     * Check if file is an image
     */
    private function is_image_file($filename) {
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $image_extensions);
    }
    
    /**
     * Get artist name from submission
     */
    private function get_artist_name($post_id) {
        // Try common field names for artist name
        $name_fields = array('cf7_artist_name', 'cf7_name', 'cf7_your_name', 'cf7_artist-name');
        
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
     * Get site logo URL for PDF header
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
     * Get site logo path for PDF header
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
