<?php
/**
 * Custom S3 File Upload Field Template
 * 
 * Template for rendering the custom drag-and-drop file upload interface in Contact Form 7 forms.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$field_id = !empty($args['id']) ? $args['id'] : 'cf7as-uploader-' . uniqid();
$field_name = !empty($args['name']) ? $args['name'] : 'your-work';
$required = !empty($args['required']) ? ' required' : '';
$max_files = !empty($args['max_files']) ? intval($args['max_files']) : 20;
$max_size = (!empty($args['max_size']) && intval($args['max_size']) > 0) ? intval($args['max_size']) : 5120; // 5GB in MB
$form_takeover = !empty($args['form_takeover']) ? true : false;

// Debug logging for troubleshooting
error_log("Custom uploader template debug - max_files from args: " . print_r($args['max_files'] ?? 'NOT_SET', true));
error_log("Custom uploader template debug - final max_files: " . $max_files);
error_log("Custom uploader template debug - max_size from args: " . print_r($args['max_size'] ?? 'NOT_SET', true));
error_log("Custom uploader template debug - final max_size: " . $max_size);
?>

<div class="cf7as-uploader-container">
    <!-- The custom uploader will initialize in this container -->
    <div id="<?php echo esc_attr($field_id); ?>" class="cf7as-uploader"
         data-max-files="<?php echo esc_attr($max_files); ?>"
         data-max-size="<?php echo esc_attr($max_size * 1024 * 1024); ?>"
         data-debug-max-size-mb="<?php echo esc_attr($max_size); ?>"
         <?php if ($form_takeover): ?>data-form-takeover="true"<?php endif; ?>>
        <!-- Custom uploader will be initialized here by JavaScript -->
        <div class="cf7as-loading-placeholder">
            <p>Loading file uploader...</p>
        </div>
    </div>
    
    <div class="cf7as-upload-info">
        <p class="cf7as-upload-note">
            <?php 
            if ($max_size >= 1024) {
                echo sprintf(
                    esc_html__('Upload up to %1$d files. Maximum size: %2$s GB per file.', 'cf7-artist-submissions'),
                    $max_files,
                    number_format($max_size / 1024, 1)
                );
            } else {
                echo sprintf(
                    esc_html__('Upload up to %1$d files. Maximum size: %2$d MB per file.', 'cf7-artist-submissions'),
                    $max_files,
                    $max_size
                );
            }
            ?>
        </p>
        <p class="cf7as-allowed-types">
            <?php esc_html_e('Allowed types: Images (JPG, PNG, GIF, WebP, SVG), Videos (MP4, MOV, WebM, AVI), Documents (PDF, DOC, DOCX, TXT, RTF)', 'cf7-artist-submissions'); ?>
        </p>
    </div>
</div>

<style>
.cf7as-uploader-container {
    margin: 15px 0;
}

.cf7as-loading-placeholder {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

.cf7as-upload-info {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
}

.cf7as-upload-note {
    margin-bottom: 5px;
    font-weight: 500;
}

.cf7as-allowed-types {
    margin-bottom: 0;
    font-size: 12px;
    line-height: 1.4;
}
</style>
