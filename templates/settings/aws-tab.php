<?php
/**
 * CF7 Artist Submissions - AWS Settings Tab Template
 *
 * AWS configuration interface template providing Amazon Web Services integration
 * settings including S3 storage configuration, Lambda function setup, MediaConvert
 * video processing, and comprehensive AWS service testing tools.
 *
 * Features:
 * • Amazon S3 bucket configuration with region selection
 * • AWS Lambda function integration for image processing
 * • MediaConvert setup for video file conversion
 * • Real-time connection testing and diagnostics
 * • Conversion statistics and processing monitoring
 * • Professional security with field masking for sensitive data
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.2.0
 * @version 1.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('cf7_artist_submissions_options', array());
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-cloud-upload"></span>
            <?php _e('Amazon Web Services Configuration', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Configure AWS services for advanced file processing, storage, and media conversion. These settings enable cloud-based file handling with automatic image optimization and video processing.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <div class="cf7-settings-form-container" id="aws-settings-form-container">
        <div class="cf7-card-body">
            <!-- S3 Configuration Section -->
            <div class="cf7-form-section">
                <div class="cf7-section-header">
                    <h3 class="cf7-section-title">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('Amazon S3 Storage', 'cf7-artist-submissions'); ?>
                    </h3>
                    <p class="cf7-section-description">
                        <?php _e('Configure Amazon S3 for secure file uploads and storage. S3 provides scalable, secure storage for all uploaded artwork and documents.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
                
                <div class="cf7-field-grid two-cols">
                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="aws_access_key">
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php _e('AWS Access Key ID', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <input type="text" 
                               id="aws_access_key" 
                               name="cf7_artist_submissions_options[aws_access_key]" 
                               value="<?php echo esc_attr(isset($options['aws_access_key']) ? $options['aws_access_key'] : ''); ?>" 
                               class="cf7-field-input"
                               autocomplete="off"
                               data-lpignore="true"
                               placeholder="<?php _e('Enter your AWS Access Key ID', 'cf7-artist-submissions'); ?>">
                        <p class="cf7-field-help">
                            <?php _e('Your AWS access key ID for S3 authentication. Create this in AWS IAM console.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="aws_secret_key">
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('AWS Secret Access Key', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <div class="cf7-secret-field-wrapper">
                            <input type="text" 
                                   id="aws_secret_key" 
                                   name="cf7_artist_submissions_options[aws_secret_key]" 
                                   value="<?php echo esc_attr(isset($options['aws_secret_key']) ? $options['aws_secret_key'] : ''); ?>" 
                                   class="cf7-field-input cf7-secret-field"
                                   autocomplete="off"
                                   data-lpignore="true"
                                   placeholder="<?php _e('Enter your AWS Secret Access Key', 'cf7-artist-submissions'); ?>">
                            <button type="button" class="cf7-secret-toggle" onclick="toggleSecretVisibility('aws_secret_key')" title="<?php _e('Show/Hide Secret Key', 'cf7-artist-submissions'); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        <p class="cf7-field-help">
                            <?php _e('Your AWS secret access key for S3 authentication. Keep this secure and private.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="cf7-field-grid two-cols">
                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="aws_region">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php _e('AWS Region', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <select id="aws_region" 
                                name="cf7_artist_submissions_options[aws_region]" 
                                class="cf7-field-input">
                            <?php 
                            $regions = array(
                                'us-east-1' => 'US East (N. Virginia)',
                                'us-east-2' => 'US East (Ohio)',
                                'us-west-1' => 'US West (N. California)',
                                'us-west-2' => 'US West (Oregon)',
                                'eu-west-1' => 'Europe (Ireland)',
                                'eu-west-2' => 'Europe (London)',
                                'eu-central-1' => 'Europe (Frankfurt)',
                                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                            );
                            $current_region = isset($options['aws_region']) ? $options['aws_region'] : 'eu-west-1';
                            foreach ($regions as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($current_region, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="cf7-field-help">
                            <?php _e('Select the AWS region where your S3 bucket is located. All AWS services should be in the same region for optimal performance.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="s3_bucket">
                            <span class="dashicons dashicons-portfolio"></span>
                            <?php _e('S3 Bucket Name', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <input type="text" 
                               id="s3_bucket" 
                               name="cf7_artist_submissions_options[s3_bucket]" 
                               value="<?php echo esc_attr(isset($options['s3_bucket']) ? $options['s3_bucket'] : ''); ?>" 
                               class="cf7-field-input"
                               placeholder="<?php _e('my-artist-submissions-bucket', 'cf7-artist-submissions'); ?>">
                        <p class="cf7-field-help">
                            <?php _e('The name of your S3 bucket where files will be stored. Must be unique globally and in the same region as selected above.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <button type="button" id="test-s3-connection" class="cf7-test-btn">
                            <span class="dashicons dashicons-cloud-upload"></span>
                            <?php _e('Test S3 Connection', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="diagnose-s3-setup" class="cf7-test-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('Diagnose S3 Setup', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="scan-s3-files" class="cf7-test-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Scan for Orphaned Files', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="cleanup-s3-files" class="cf7-test-btn" style="margin-left: 10px; background-color: #d63638; color: white;">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Clean Up Orphaned Files', 'cf7-artist-submissions'); ?>
                        </button>
                        <div id="s3-test-result" class="cf7-test-result" style="display: none;"></div>
                        <div id="s3-cleanup-result" class="cf7-test-result" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- S3 Cleanup Information -->
                <div class="cf7-notice cf7-notice-info">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('S3 File Cleanup', 'cf7-artist-submissions'); ?></strong>
                        <p><?php _e('The cleanup tools help manage S3 storage costs by identifying and removing files that are no longer linked to active submissions:', 'cf7-artist-submissions'); ?></p>
                        <ul style="margin: 10px 0 0 20px; color: #666;">
                            <li><strong><?php _e('Scan for Orphaned Files:', 'cf7-artist-submissions'); ?></strong> <?php _e('Identifies files in S3 that are no longer linked to active submissions', 'cf7-artist-submissions'); ?></li>
                            <li><strong><?php _e('Clean Up Orphaned Files:', 'cf7-artist-submissions'); ?></strong> <?php _e('Permanently deletes orphaned files from S3 storage and database records', 'cf7-artist-submissions'); ?></li>
                        </ul>
                        <p style="margin-top: 10px;">
                            <strong><?php _e('When files become orphaned:', 'cf7-artist-submissions'); ?></strong>
                            <?php _e('When submissions are deleted manually from the WordPress admin, associated files in S3 storage may remain. The cleanup tools help identify and remove these unused files.', 'cf7-artist-submissions'); ?>
                        </p>
                        <p style="margin-top: 10px; color: #d63638;">
                            <strong><?php _e('⚠️ Important:', 'cf7-artist-submissions'); ?></strong>
                            <?php _e('Always run "Scan" first to review what will be deleted. File deletion cannot be undone.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Media Conversion Settings -->
            <div class="cf7-form-section">
                <div class="cf7-section-header">
                    <h3 class="cf7-section-title">
                        <span class="dashicons dashicons-format-video"></span>
                        <?php _e('Media Conversion (AWS Lambda & MediaConvert)', 'cf7-artist-submissions'); ?>
                    </h3>
                    <p class="cf7-section-description">
                        <?php _e('Configure AWS Lambda and MediaConvert to automatically convert uploaded files into web-efficient formats. Original files are preserved for downloads while converted versions are served for viewing and previews.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <!-- Enable Media Conversion Toggle -->
                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <label class="cf7-field-label">
                            <span class="dashicons dashicons-format-video"></span>
                            <strong><?php _e('Enable Media Conversion', 'cf7-artist-submissions'); ?></strong>
                        </label>
                        <div class="cf7-toggle">
                            <input type="checkbox" 
                                   id="enable_media_conversion"
                                   name="cf7_artist_submissions_options[enable_media_conversion]" 
                                   value="on"
                                   <?php checked(isset($options['enable_media_conversion']) ? $options['enable_media_conversion'] : '', 'on'); ?>>
                            <span class="cf7-toggle-slider"></span>
                        </div>
                        <p class="cf7-field-description">
                            <?php _e('Turn on automatic media conversion for uploaded files. When enabled, images will be converted to WebP format and videos will be optimized using AWS Lambda and MediaConvert services.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>

                <div class="cf7-field-grid two-cols">
                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="lambda_function_name">
                            <span class="dashicons dashicons-cloud"></span>
                            <?php _e('Lambda Function Name', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <input type="text" 
                               class="cf7-field-input" 
                               id="lambda_function_name" 
                               name="cf7_artist_submissions_options[lambda_function_name]" 
                               value="<?php echo esc_attr(isset($options['lambda_function_name']) ? $options['lambda_function_name'] : 'cf7as-image-converter'); ?>" 
                               placeholder="<?php _e('cf7as-image-converter', 'cf7-artist-submissions'); ?>" />
                        <p class="cf7-field-help">
                            <?php _e('The name of your AWS Lambda function that handles media conversion. This function should process files and trigger MediaConvert jobs.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="mediaconvert_endpoint">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <?php _e('MediaConvert Endpoint', 'cf7-artist-submissions'); ?>
                        </label>
                        <input type="url" 
                               class="cf7-field-input" 
                               id="mediaconvert_endpoint" 
                               name="cf7_artist_submissions_options[mediaconvert_endpoint]" 
                               value="<?php echo esc_attr(isset($options['mediaconvert_endpoint']) ? $options['mediaconvert_endpoint'] : ''); ?>" 
                               placeholder="<?php _e('https://mediaconvert.eu-west-1.amazonaws.com', 'cf7-artist-submissions'); ?>" />
                        <p class="cf7-field-help">
                            <?php _e('Your MediaConvert regional endpoint URL. For modern setup, use: https://mediaconvert.eu-west-1.amazonaws.com (replace eu-west-1 with your region).', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label" for="mediaconvert_role_arn">
                        <span class="dashicons dashicons-admin-network"></span>
                        <?php _e('MediaConvert Service Role ARN', 'cf7-artist-submissions'); ?>
                        <span class="cf7-required">*</span>
                    </label>
                    <input type="text" 
                           class="cf7-field-input" 
                           id="mediaconvert_role_arn" 
                           name="cf7_artist_submissions_options[mediaconvert_role_arn]" 
                           value="<?php echo esc_attr(isset($options['mediaconvert_role_arn']) ? $options['mediaconvert_role_arn'] : ''); ?>" 
                           placeholder="<?php _e('arn:aws:iam::659942169281:role/CF7AS-MediaConvert-Role', 'cf7-artist-submissions'); ?>" />
                    <p class="cf7-field-help">
                        <?php _e('The ARN of your MediaConvert service role that allows MediaConvert to access your S3 bucket. Format: arn:aws:iam::YOUR-ACCOUNT-ID:role/CF7AS-MediaConvert-Role', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Conversion Settings', 'cf7-artist-submissions'); ?>
                    </label>
                    <div class="cf7-checkbox-group">
                        <label class="cf7-checkbox-label">
                            <input type="checkbox" 
                                   name="cf7_artist_submissions_options[convert_images]" 
                                   value="1" 
                                   <?php checked(isset($options['convert_images']) ? $options['convert_images'] : 1, 1); ?>>
                            <span class="cf7-checkbox-mark"></span>
                            <span><?php _e('Convert images to WebP format for faster loading', 'cf7-artist-submissions'); ?></span>
                        </label>
                        <label class="cf7-checkbox-label">
                            <input type="checkbox" 
                                   name="cf7_artist_submissions_options[convert_videos]" 
                                   value="1" 
                                   <?php checked(isset($options['convert_videos']) ? $options['convert_videos'] : 1, 1); ?>>
                            <span class="cf7-checkbox-mark"></span>
                            <span><?php _e('Convert videos to MP4/WebM formats for web compatibility', 'cf7-artist-submissions'); ?></span>
                        </label>
                        <label class="cf7-checkbox-label">
                            <input type="checkbox" 
                                   name="cf7_artist_submissions_options[generate_thumbnails]" 
                                   value="1" 
                                   <?php checked(isset($options['generate_thumbnails']) ? $options['generate_thumbnails'] : 1, 1); ?>>
                            <span class="cf7-checkbox-mark"></span>
                            <span><?php _e('Generate thumbnails for videos and large images', 'cf7-artist-submissions'); ?></span>
                        </label>
                        <label class="cf7-checkbox-label">
                            <input type="checkbox" 
                                   name="cf7_artist_submissions_options[create_multiple_sizes]" 
                                   value="1" 
                                   <?php checked(isset($options['create_multiple_sizes']) ? $options['create_multiple_sizes'] : 1, 1); ?>>
                            <span class="cf7-checkbox-mark"></span>
                            <span><?php _e('Create multiple size variants (thumbnail, medium, large)', 'cf7-artist-submissions'); ?></span>
                        </label>
                    </div>
                    <p class="cf7-field-help">
                        <?php _e('Choose which types of conversions to perform automatically when files are uploaded.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <button type="button" id="test-lambda-connection" class="cf7-test-btn">
                            <span class="dashicons dashicons-cloud"></span>
                            <?php _e('Test Lambda Connection', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="test-mediaconvert-connection" class="cf7-test-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <?php _e('Test MediaConvert Connection', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="process-existing-files" class="cf7-test-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Process Existing Files', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="conversion-status" class="cf7-test-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('View Conversion Status', 'cf7-artist-submissions'); ?>
                        </button>
                        <button type="button" id="reset-file-status" class="cf7-test-btn" style="margin-left: 10px; background-color: #d63638;">
                            <span class="dashicons dashicons-undo"></span>
                            <?php _e('Reset File Status', 'cf7-artist-submissions'); ?>
                        </button>
                        <div id="lambda-test-result" class="cf7-test-result" style="display: none;"></div>
                        <div id="mediaconvert-test-result" class="cf7-test-result" style="display: none;"></div>
                        <div id="conversion-progress" class="cf7-conversion-progress" style="display: none;">
                            <div class="cf7-progress-bar">
                                <div class="cf7-progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="cf7-progress-text">Processing files...</div>
                        </div>
                    </div>
                </div>

                <?php
                // Show conversion statistics if available
                if (class_exists('CF7_Artist_Submissions_Media_Converter')) {
                    $converter = new CF7_Artist_Submissions_Media_Converter();
                    $stats = $converter->get_conversion_statistics();
                    
                    if ($stats['total_files'] > 0) :
                ?>
                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <label class="cf7-field-label">
                            <span class="dashicons dashicons-chart-pie"></span>
                            <?php _e('Conversion Statistics', 'cf7-artist-submissions'); ?>
                        </label>
                        <div class="cf7-stats-grid">
                            <div class="cf7-stat-item">
                                <div class="cf7-stat-number"><?php echo $stats['total_files']; ?></div>
                                <div class="cf7-stat-label"><?php _e('Total Files', 'cf7-artist-submissions'); ?></div>
                            </div>
                            <div class="cf7-stat-item">
                                <div class="cf7-stat-number"><?php echo $stats['converted_files']; ?></div>
                                <div class="cf7-stat-label"><?php _e('Converted', 'cf7-artist-submissions'); ?></div>
                            </div>
                            <div class="cf7-stat-item">
                                <div class="cf7-stat-number"><?php echo $stats['pending_conversions']; ?></div>
                                <div class="cf7-stat-label"><?php _e('Pending', 'cf7-artist-submissions'); ?></div>
                            </div>
                            <div class="cf7-stat-item">
                                <div class="cf7-stat-number"><?php echo $stats['failed_conversions']; ?></div>
                                <div class="cf7-stat-label"><?php _e('Failed', 'cf7-artist-submissions'); ?></div>
                            </div>
                            <div class="cf7-stat-item cf7-stat-highlight">
                                <div class="cf7-stat-number"><?php echo $stats['conversion_rate']; ?>%</div>
                                <div class="cf7-stat-label"><?php _e('Success Rate', 'cf7-artist-submissions'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; } ?>
            </div>

            <!-- PDF Generation Settings -->
            <div class="cf7-form-section">
                <div class="cf7-section-header">
                    <h3 class="cf7-section-title">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php _e('PDF Generation (AWS Lambda)', 'cf7-artist-submissions'); ?>
                    </h3>
                    <p class="cf7-section-description">
                        <?php _e('Configure AWS Lambda for professional PDF generation using Puppeteer and Chrome. This provides high-quality PDF exports with pixel-perfect rendering instead of browser print-to-PDF.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <!-- Enable PDF Lambda Toggle -->
                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <label class="cf7-field-label">
                            <span class="dashicons dashicons-pdf"></span>
                            <strong><?php _e('Enable AWS Lambda PDF Generation', 'cf7-artist-submissions'); ?></strong>
                        </label>
                        <div class="cf7-toggle">
                            <input type="checkbox" 
                                   id="enable_pdf_lambda"
                                   name="cf7_artist_submissions_options[enable_pdf_lambda]" 
                                   value="on"
                                   <?php checked(isset($options['enable_pdf_lambda']) ? $options['enable_pdf_lambda'] : '', 'on'); ?>>
                            <span class="cf7-toggle-slider"></span>
                        </div>
                        <p class="cf7-field-description">
                            <?php _e('Enable cloud-based PDF generation using AWS Lambda. When disabled, the system will fall back to browser-based HTML print-to-PDF.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label" for="pdf_lambda_function_arn">
                        <span class="dashicons dashicons-cloud"></span>
                        <?php _e('PDF Lambda Function ARN', 'cf7-artist-submissions'); ?>
                        <span class="cf7-required">*</span>
                    </label>
                    <input type="text" 
                           class="cf7-field-input" 
                           id="pdf_lambda_function_arn" 
                           name="cf7_artist_submissions_options[pdf_lambda_function_arn]" 
                           value="<?php echo esc_attr(isset($options['pdf_lambda_function_arn']) ? $options['pdf_lambda_function_arn'] : ''); ?>" 
                           placeholder="<?php _e('arn:aws:lambda:us-east-1:123456789012:function:cf7as-pdf-generator', 'cf7-artist-submissions'); ?>" />
                    <p class="cf7-field-help">
                        <?php _e('The full ARN of your AWS Lambda function for PDF generation. Deploy the cf7as-pdf-generator function and enter its ARN here.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <button type="button" id="test-pdf-lambda" class="cf7-test-btn">
                            <span class="dashicons dashicons-pdf"></span>
                            <?php _e('Test PDF Lambda Function', 'cf7-artist-submissions'); ?>
                        </button>
                        <div id="pdf-lambda-test-result" class="cf7-test-result" style="display: none;"></div>
                    </div>
                </div>

                <!-- PDF Lambda Setup Help -->
                <div class="cf7-notice cf7-notice-info">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('PDF Lambda Setup', 'cf7-artist-submissions'); ?></strong>
                        <p><?php _e('To enable professional PDF generation, deploy the cf7as-pdf-generator Lambda function:', 'cf7-artist-submissions'); ?></p>
                        <ol style="margin: 10px 0 0 20px; color: #666;">
                            <li><?php _e('Navigate to lambda-functions/cf7as-pdf-generator/', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Run ./deploy.sh to deploy the function to AWS', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Copy the function ARN from the deployment output', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Paste the ARN in the field above and test the connection', 'cf7-artist-submissions'); ?></li>
                        </ol>
                        <p style="margin-top: 10px;">
                            <strong><?php _e('Benefits:', 'cf7-artist-submissions'); ?></strong>
                            <?php _e('Professional rendering, consistent formatting, better image handling, and automatic S3 storage with download URLs.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>

                <!-- AWS Setup Help -->
                <div class="cf7-notice cf7-notice-info">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('AWS Setup Required', 'cf7-artist-submissions'); ?></strong>
                        <p><?php _e('Media conversion requires AWS Lambda and MediaConvert services to be set up. Check the', 'cf7-artist-submissions'); ?> 
                           <strong>AWS_SETUP_GUIDE.md</strong> 
                           <?php _e('file in your plugin directory for complete setup instructions.', 'cf7-artist-submissions'); ?></p>
                        <ul style="margin: 10px 0 0 20px; color: #666;">
                            <li><?php _e('Deploy Lambda function for image processing', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Activate MediaConvert service in your AWS region', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Configure IAM permissions for Lambda and MediaConvert', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Set up S3 bucket policies for service access', 'cf7-artist-submissions'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="cf7-form-actions">
            <div class="cf7-form-actions-left">
                <?php if (!empty($options['s3_bucket'])): ?>
                    <div class="cf7-status-indicator cf7-status-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php printf(__('S3 Bucket: %s', 'cf7-artist-submissions'), $options['s3_bucket']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="cf7-form-actions-right">
                <button type="button" class="cf7-test-btn" data-action="test-aws-config">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Test AWS Configuration', 'cf7-artist-submissions'); ?>
                </button>
                <button type="button" class="cf7-btn cf7-btn-primary" id="save-aws-settings">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save AWS Settings', 'cf7-artist-submissions'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading indicator for save operations -->
    <div id="aws-save-status" class="cf7-save-status" style="display: none;">
        <div class="cf7-save-message"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Define ajaxurl for WordPress AJAX calls
    var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
    
    // Generate nonces for AJAX calls
    const processFilesNonce = '<?php echo wp_create_nonce("cf7as_process_files"); ?>';
    const conversionStatusNonce = '<?php echo wp_create_nonce("cf7as_conversion_status"); ?>';
    
    // Handle S3 connection test
    $('#test-s3-connection').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $result = $('#s3-test-result');
        
        // Get S3 configuration values
        const s3Config = {
            aws_access_key: $('#aws_access_key').val(),
            aws_secret_key: $('#aws_secret_key').val(),
            aws_region: $('#aws_region').val(),
            s3_bucket: $('#s3_bucket').val(),
            nonce: '<?php echo wp_create_nonce("cf7_artist_submissions_settings"); ?>'
        };
        
        // Validate required fields
        if (!s3Config.aws_access_key || !s3Config.aws_secret_key || !s3Config.aws_region || !s3Config.s3_bucket) {
            $result.show().removeClass('success error').addClass('error')
                   .html('<span class="dashicons dashicons-warning"></span> Please fill in all S3 configuration fields before testing.');
            return;
        }
        
        // Update button state
        const originalText = $button.html();
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update spin"></span> Testing Connection...');
        
        // Show loading state
        $result.show().removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update spin"></span> Testing S3 connection...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_s3_connection',
                ...s3Config
            },
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('loading error').addClass('success')
                           .html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    
                    if (response.data.details) {
                        const details = response.data.details;
                        let detailsHtml = '<div class="cf7-test-details">';
                        detailsHtml += '<strong>Connection Details:</strong><br>';
                        detailsHtml += 'Region: ' + details.region + '<br>';
                        detailsHtml += 'Bucket: ' + details.bucket + '<br>';
                        detailsHtml += 'Status: ' + details.status + '<br>';
                        if (details.test_details) {
                            detailsHtml += 'Test: ' + details.test_details + '<br>';
                        }
                        detailsHtml += '</div>';
                        $result.append(detailsHtml);
                    }
                } else {
                    $result.removeClass('loading success').addClass('error')
                           .html('<span class="dashicons dashicons-warning"></span> ' + response.data.message);
                    
                    if (response.data.details) {
                        $result.append('<div class="cf7-test-details"><strong>Details:</strong> ' + response.data.details + '</div>');
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Connection test failed';
                if (status === 'timeout') {
                    errorMessage = 'Test timed out - check your network connection and AWS credentials';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (error) {
                    errorMessage = 'Error: ' + error;
                }
                
                $result.removeClass('loading success').addClass('error')
                       .html('<span class="dashicons dashicons-warning"></span> ' + errorMessage);
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle S3 Setup Diagnosis - same as in general tab
    $('#diagnose-s3-setup').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $result = $('#s3-test-result');
        
        // Get S3 configuration values  
        const s3Config = {
            aws_access_key: $('#aws_access_key').val(),
            aws_secret_key: $('#aws_secret_key').val(),
            aws_region: $('#aws_region').val(),
            s3_bucket: $('#s3_bucket').val()
        };
        
        // Show diagnostic information
        let diagnosticHtml = '<div class="cf7-test-details" style="text-align: left; padding: 15px; background: #f8f9fa; border-left: 4px solid #007cba; margin-top: 10px;">';
        diagnosticHtml += '<h4 style="color: #007cba; margin-top: 0;">🔍 S3 Setup Diagnostic Information</h4>';
        
        diagnosticHtml += '<h5>📋 Current Configuration:</h5>';
        diagnosticHtml += '<ul>';
        diagnosticHtml += '<li><strong>AWS Region:</strong> ' + (s3Config.aws_region || 'Not set') + '</li>';
        diagnosticHtml += '<li><strong>S3 Bucket:</strong> ' + (s3Config.s3_bucket || 'Not set') + '</li>';
        diagnosticHtml += '<li><strong>AWS Access Key:</strong> ' + (s3Config.aws_access_key ? s3Config.aws_access_key.substring(0, 4) + '***' : 'Not set') + '</li>';
        diagnosticHtml += '<li><strong>AWS Secret Key:</strong> ' + (s3Config.aws_secret_key ? '***Set***' : 'Not set') + '</li>';
        diagnosticHtml += '</ul>';
        
        diagnosticHtml += '<h5>🛠️ For 403 Forbidden Error, Check These Items:</h5>';
        diagnosticHtml += '<ol>';
        diagnosticHtml += '<li><strong style="color: #d63638;">Bucket Policy Missing IAM User</strong><br>';
        diagnosticHtml += 'Most common cause! Your bucket policy must include your IAM user ARN.<br>';
        diagnosticHtml += 'Expected format: <code>"arn:aws:iam::YOUR-ACCOUNT-ID:user/cf7-artist-submissions"</code></li>';
        
        diagnosticHtml += '<li><strong>IAM User Policy Insufficient</strong><br>';
        diagnosticHtml += 'IAM user needs: s3:GetObject, s3:PutObject, s3:DeleteObject, s3:ListBucket permissions</li>';
        
        diagnosticHtml += '<li><strong>Wrong AWS Account ID</strong><br>';
        diagnosticHtml += 'Bucket policy must use your actual 12-digit AWS Account ID</li>';
        
        diagnosticHtml += '<li><strong>Incorrect Bucket Name/Region</strong><br>';
        diagnosticHtml += 'Verify bucket "' + s3Config.s3_bucket + '" exists in region "' + s3Config.aws_region + '"</li>';
        
        diagnosticHtml += '<li><strong>Bucket Policy Syntax Error</strong><br>';
        diagnosticHtml += 'JSON syntax must be valid in your S3 bucket policy</li>';
        diagnosticHtml += '</ol>';
        
        diagnosticHtml += '<h5>✅ Quick Fix Steps:</h5>';
        diagnosticHtml += '<ol>';
        diagnosticHtml += '<li>Go to your S3 bucket → <strong>Permissions</strong> tab</li>';
        diagnosticHtml += '<li>Scroll to <strong>"Bucket policy"</strong> section</li>';
        diagnosticHtml += '<li>Verify your policy includes: <code>"arn:aws:iam::YOUR-ACCOUNT-ID:user/cf7-artist-submissions"</code></li>';
        diagnosticHtml += '<li>Replace YOUR-ACCOUNT-ID with your actual 12-digit account number</li>';
        diagnosticHtml += '<li>Save the bucket policy and test connection again</li>';
        diagnosticHtml += '</ol>';
        
        diagnosticHtml += '<h5>🆔 Find Your AWS Account ID:</h5>';
        diagnosticHtml += '<ul>';
        diagnosticHtml += '<li>AWS Console top-right → Click your account name → "Account"</li>';
        diagnosticHtml += '<li>Or check IAM → Users → cf7-artist-submissions → ARN shows your account ID</li>';
        diagnosticHtml += '</ul>';
        
        diagnosticHtml += '<div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;">';
        diagnosticHtml += '<strong>💡 Pro Tip:</strong> The "public access is blocked" warning in S3 is normal and expected!<br>';
        diagnosticHtml += 'Your bucket policy still works even with that warning - it only blocks public access, not IAM user access.';
        diagnosticHtml += '</div>';
        
        diagnosticHtml += '</div>';
        
        $result.show().removeClass('success error loading').html(diagnosticHtml);
    });

    // Lambda and MediaConvert functionality - same as in general tab
    
    // Test Lambda connection
    $('#test-lambda-connection').on('click', function() {
        const $button = $(this);
        const $result = $('#lambda-test-result');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
        $result.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_test_lambda_connection',
                nonce: '<?php echo wp_create_nonce("cf7as_test_lambda"); ?>',
                lambda_function_name: $('#lambda_function_name').val(),
                mediaconvert_endpoint: $('#mediaconvert_endpoint').val(),
                aws_access_key: $('#aws_access_key').val(),
                aws_secret_key: $('#aws_secret_key').val(),
                aws_region: $('#aws_region').val()
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '<div style="color: #008000; padding: 10px; background: #f0fff0; border: 1px solid #90EE90; border-radius: 4px;">';
                    html += '<h4 style="color: #008000; margin-top: 0;">✅ Lambda Connection Test Results</h4>';
                    
                    if (data.connection_successful) {
                        html += '<p><strong>🎉 Connection Successful!</strong> Your Lambda function is responding correctly.</p>';
                    }
                    
                    html += '<ul>';
                    html += '<li><strong>Lambda Configuration:</strong> ' + (data.lambda_configured ? '✅ Configured' : '❌ Missing') + '</li>';
                    html += '<li><strong>MediaConvert Endpoint:</strong> ' + (data.mediaconvert_configured ? '✅ Configured' : '❌ Missing') + '</li>';
                    html += '<li><strong>AWS Credentials:</strong> ' + (data.credentials_valid ? '✅ Valid' : '❌ Invalid') + '</li>';
                    html += '<li><strong>Lambda Response:</strong> ' + (data.connection_successful ? '✅ Success' : '❌ Failed') + '</li>';
                    html += '</ul>';
                    html += '</div>';
                    
                    $result.html(html).show();
                } else {
                    const data = response.data;
                    let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                    html += '<h4 style="color: #d63638; margin-top: 0;">❌ Lambda Connection Failed</h4>';
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<p><strong>Issues found:</strong></p>';
                        html += '<ul>';
                        data.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    html += '<h5>🛠️ Setup Requirements:</h5>';
                    html += '<ol>';
                    html += '<li>Deploy AWS Lambda function for media conversion</li>';
                    html += '<li>Configure MediaConvert endpoint in AWS console</li>';
                    html += '<li>Set IAM permissions for Lambda and MediaConvert</li>';
                    html += '<li>Test the setup with a sample file</li>';
                    html += '</ol>';
                    html += '</div>';
                    
                    $result.html(html).show();
                }
            },
            error: function(xhr, status, error) {
                let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                html += '<h4 style="color: #d63638; margin-top: 0;">❌ Connection Test Error</h4>';
                html += '<p>Failed to test Lambda connection: ' + error + '</p>';
                if (xhr.responseText) {
                    html += '<p><strong>Server Response:</strong> ' + xhr.responseText.substring(0, 200) + '...</p>';
                }
                html += '</div>';
                
                $result.html(html).show();
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Test MediaConvert connection
    $('#test-mediaconvert-connection').on('click', function() {
        const $button = $(this);
        const $result = $('#mediaconvert-test-result');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
        $result.hide();
        
        // Get MediaConvert configuration values
        const mediaconvertConfig = {
            aws_access_key: $('#aws_access_key').val(),
            aws_secret_key: $('#aws_secret_key').val(),
            aws_region: $('#aws_region').val(),
            mediaconvert_endpoint: $('#mediaconvert_endpoint').val(),
            mediaconvert_role_arn: $('#mediaconvert_role_arn').val(),
            nonce: '<?php echo wp_create_nonce("cf7as_test_mediaconvert"); ?>'
        };
        
        // Validate required fields
        if (!mediaconvertConfig.aws_access_key || !mediaconvertConfig.aws_secret_key || !mediaconvertConfig.aws_region) {
            $result.show().removeClass('success error').addClass('error')
                   .html('<span class="dashicons dashicons-warning"></span> Please fill in AWS credentials and region before testing MediaConvert.');
            $button.prop('disabled', false).html(originalText);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_test_mediaconvert_connection',
                ...mediaconvertConfig
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '<div style="color: #008000; padding: 10px; background: #f0fff0; border: 1px solid #90EE90; border-radius: 4px;">';
                    html += '<h4 style="color: #008000; margin-top: 0;">✅ MediaConvert Connection Test Results</h4>';
                    
                    if (data.connection_test) {
                        html += '<p><strong>🎉 Connection Successful!</strong> MediaConvert is responding correctly.</p>';
                    }
                    
                    html += '<ul>';
                    html += '<li><strong>Endpoint Configured:</strong> ' + (data.endpoint_configured ? '✅ Yes' : '❌ No') + '</li>';
                    html += '<li><strong>Credentials Configured:</strong> ' + (data.credentials_configured ? '✅ Yes' : '❌ No') + '</li>';
                    html += '<li><strong>Service Role Configured:</strong> ' + (data.role_configured ? '✅ Yes' : '❌ No') + '</li>';
                    html += '<li><strong>Connection Test:</strong> ' + (data.connection_test ? '✅ Success' : '❌ Failed') + '</li>';
                    html += '</ul>';
                    
                    if (data.endpoint_url) {
                        html += '<p><strong>Endpoint:</strong> ' + data.endpoint_url + '</p>';
                    }
                    if (data.role_arn) {
                        html += '<p><strong>Service Role:</strong> ' + data.role_arn + '</p>';
                    }
                    
                    html += '</div>';
                    
                    $result.html(html).show();
                } else {
                    const data = response.data;
                    let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                    html += '<h4 style="color: #d63638; margin-top: 0;">❌ MediaConvert Connection Failed</h4>';
                    
                    if (data.error) {
                        html += '<p><strong>Error:</strong> ' + data.error + '</p>';
                    }
                    
                    if (data.suggestion) {
                        html += '<p><strong>Suggestion:</strong> ' + data.suggestion + '</p>';
                    }
                    
                    html += '<h5>🛠️ Setup Requirements:</h5>';
                    html += '<ol>';
                    html += '<li>Set MediaConvert endpoint: <code>https://mediaconvert.eu-west-1.amazonaws.com</code></li>';
                    html += '<li>Configure MediaConvert service role ARN: <code>arn:aws:iam::659942169281:role/CF7AS-MediaConvert-Role</code></li>';
                    html += '<li>Ensure service role has S3 permissions</li>';
                    html += '<li>Verify IAM user has MediaConvert permissions</li>';
                    html += '</ol>';
                    html += '</div>';
                    
                    $result.html(html).show();
                }
            },
            error: function(xhr, status, error) {
                let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                html += '<h4 style="color: #d63638; margin-top: 0;">❌ Test Request Failed</h4>';
                html += '<p><strong>Error:</strong> ' + error + '</p>';
                html += '</div>';
                
                $result.html(html).show();
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Initialize secret field as hidden on page load
    const secretField = document.getElementById('aws_secret_key');
    if (secretField && secretField.value) {
        // Use setTimeout to ensure field is fully loaded before applying styles
        setTimeout(function() {
            secretField.style.webkitTextSecurity = 'disc';
            secretField.style.textSecurity = 'disc';
            secretField.style.letterSpacing = '0.1em';
        }, 100);
    }
    
    // Handle Process Existing Files button
    $('#process-existing-files').on('click', function() {
        const $button = $(this);
        const originalText = $button.html();
        const $progress = $('#conversion-progress');
        const $progressBar = $('.cf7-progress-fill');
        const $progressText = $('.cf7-progress-text');
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update cf7-spin"></span> Processing...');
        $progress.show();
        $progressBar.css('width', '0%');
        $progressText.text('Starting file processing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_process_existing_files',
                nonce: processFilesNonce,
                limit: 10
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $progressBar.css('width', '100%');
                    $progressText.html('✅ Processing complete! ' + data.processed + ' files processed, ' + data.failed + ' failed.');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $progressText.html('❌ Processing failed: ' + (response.data || 'Unknown error'));
                    $progressBar.css('width', '100%').css('background-color', '#dc3232');
                }
            },
            error: function(xhr, status, error) {
                $progressText.html('❌ Error: ' + error);
                $progressBar.css('width', '100%').css('background-color', '#dc3232');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
                setTimeout(function() {
                    $progress.hide();
                    $progressBar.css('background-color', '#00a32a');
                }, 3000);
            }
        });
    });
    
    // Handle View Conversion Status button
    $('#conversion-status').on('click', function() {
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update cf7-spin"></span> Loading...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_get_conversion_status',
                nonce: conversionStatusNonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Create modal dialog for status display
                    let html = '<div id="conversion-status-modal" onclick="closeConversionModal(event)" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;">';
                    html += '<div onclick="event.stopPropagation()" style="background: white; padding: 30px; border-radius: 8px; max-width: 800px; max-height: 80vh; overflow-y: auto; position: relative;">';
                    html += '<button onclick="closeConversionModal()" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>';
                    html += '<h2 style="margin-top: 0; color: #23282d;">📊 Conversion Status Report</h2>';
                    
                    // Summary stats
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">';
                    html += '<div style="text-align: center; padding: 15px; background: #f0f8ff; border-radius: 6px;">';
                    html += '<div style="font-size: 24px; font-weight: bold; color: #0073aa;">' + data.summary.total + '</div>';
                    html += '<div style="color: #666;">Total Files</div>';
                    html += '</div>';
                    html += '<div style="text-align: center; padding: 15px; background: #f0fff0; border-radius: 6px;">';
                    html += '<div style="font-size: 24px; font-weight: bold; color: #00a32a;">' + data.summary.completed + '</div>';
                    html += '<div style="color: #666;">Completed</div>';
                    html += '</div>';
                    html += '<div style="text-align: center; padding: 15px; background: #fff8dc; border-radius: 6px;">';
                    html += '<div style="font-size: 24px; font-weight: bold; color: #dba617;">' + data.summary.pending + '</div>';
                    html += '<div style="color: #666;">Pending</div>';
                    html += '</div>';
                    html += '<div style="text-align: center; padding: 15px; background: #fff5f5; border-radius: 6px;">';
                    html += '<div style="font-size: 24px; font-weight: bold; color: #dc3232;">' + data.summary.failed + '</div>';
                    html += '<div style="color: #666;">Failed</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Recent jobs table
                    if (data.recent_jobs && data.recent_jobs.length > 0) {
                        html += '<h3>Recent Conversion Jobs</h3>';
                        html += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                        html += '<thead style="background: #f1f1f1;"><tr>';
                        html += '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">File</th>';
                        html += '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Status</th>';
                        html += '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Created</th>';
                        html += '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Progress</th>';
                        html += '</tr></thead><tbody>';
                        
                        data.recent_jobs.forEach(function(job) {
                            const statusColor = job.status === 'completed' ? '#00a32a' : 
                                              job.status === 'failed' ? '#dc3232' : '#dba617';
                            html += '<tr>';
                            html += '<td style="padding: 8px; border: 1px solid #ddd;">' + job.original_filename + '</td>';
                            html += '<td style="padding: 8px; border: 1px solid #ddd; color: ' + statusColor + ';"><strong>' + job.status.toUpperCase() + '</strong></td>';
                            html += '<td style="padding: 8px; border: 1px solid #ddd;">' + job.created_at + '</td>';
                            html += '<td style="padding: 8px; border: 1px solid #ddd;">' + (job.progress || '0') + '%</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    }
                    
                    html += '<div style="margin-top: 20px; text-align: center;">';
                    html += '<button onclick="closeConversionModal()" style="background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Close</button>';
                    
                    // Add job management buttons if there are pending or failed jobs
                    if (data.summary.pending > 0) {
                        html += '<button onclick="clearPendingJobs()" style="background: #dba617; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Clear ' + data.summary.pending + ' Pending Jobs</button>';
                    }
                    if (data.summary.failed > 0) {
                        html += '<button onclick="clearFailedJobs()" style="background: #dc3232; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Clear ' + data.summary.failed + ' Failed Jobs</button>';
                    }
                    
                    html += '</div>';
                    html += '</div></div>';
                    
                    $('body').append(html);
                    
                    // Add keyboard support for closing modal
                    $(document).on('keydown.conversionModal', function(e) {
                        if (e.key === 'Escape') {
                            closeConversionModal();
                        }
                    });
                } else {
                    alert('Failed to load conversion status: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading conversion status: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle Reset File Status button
    $('#reset-file-status').on('click', function() {
        if (!confirm('⚠️ RESET FILE STATUS\n\nThis will:\n• Reset all files to "not converted" status\n• Clear all pending/failed conversion jobs\n• Clear all converted file records\n• Allow all files to be processed again from scratch\n\n⚠️ This action cannot be undone!\n\nContinue?')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update cf7-spin"></span> Resetting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_reset_file_status',
                nonce: '<?php echo wp_create_nonce("cf7as_reset_files"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let message = '✅ Success! Reset ' + data.reset_count + ' files to allow reprocessing.';
                    
                    if (data.cleared_jobs && data.cleared_jobs > 0) {
                        message += '\n📋 Cleared ' + data.cleared_jobs + ' pending/failed conversion jobs.';
                    }
                    
                    if (data.cleared_converted && data.cleared_converted > 0) {
                        message += '\n🗑️ Cleared ' + data.cleared_converted + ' converted file records.';
                    }
                    
                    message += '\n\n🔄 The page will now reload to refresh the statistics.';
                    alert(message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + (response.data || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Network error: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Test PDF Lambda function
    $('#test-pdf-lambda').on('click', function() {
        const $button = $(this);
        const $result = $('#pdf-lambda-test-result');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
        $result.hide();
        
        const lambdaArn = $('#pdf_lambda_function_arn').val();
        
        if (!lambdaArn) {
            $result.html('<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">Please enter a Lambda function ARN first.</div>').show();
            $button.prop('disabled', false).html(originalText);
            return;
        }
        
        const testData = {
            action: 'cf7as_test_pdf_lambda',
            nonce: '<?php echo wp_create_nonce("cf7as_test_pdf_lambda"); ?>',
            pdf_lambda_function_arn: lambdaArn,
            aws_access_key: $('#aws_access_key').val(),
            aws_secret_key: $('#aws_secret_key').val(),
            aws_region: $('#aws_region').val(),
            s3_bucket: $('#s3_bucket').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: testData,
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '<div style="color: #008000; padding: 10px; background: #f0fff0; border: 1px solid #90EE90; border-radius: 4px;">';
                    html += '<h4 style="color: #008000; margin-top: 0;">✅ PDF Lambda Function Test Results</h4>';
                    
                    if (data.connection_successful) {
                        html += '<p><strong>🎉 Connection Successful!</strong> Your PDF Lambda function is responding correctly.</p>';
                    }
                    
                    html += '<ul>';
                    html += '<li><strong>Lambda ARN:</strong> ' + (data.lambda_arn_valid ? '✅ Valid Format' : '❌ Invalid Format') + '</li>';
                    html += '<li><strong>AWS Credentials:</strong> ' + (data.credentials_valid ? '✅ Valid' : '❌ Invalid') + '</li>';
                    html += '<li><strong>S3 Bucket:</strong> ' + (data.s3_bucket_configured ? '✅ Configured' : '❌ Missing') + '</li>';
                    html += '<li><strong>Lambda Response:</strong> ' + (data.connection_successful ? '✅ Success' : '❌ Failed') + '</li>';
                    html += '</ul>';
                    
                    if (data.lambda_response) {
                        html += '<div style="margin-top: 10px; padding: 8px; background: rgba(0,0,0,0.05); border-radius: 3px;">';
                        html += '<strong>Lambda Response:</strong><br>';
                        html += '<code>' + JSON.stringify(data.lambda_response, null, 2) + '</code>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    
                    $result.html(html).show();
                } else {
                    const data = response.data;
                    let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                    html += '<h4 style="color: #d63638; margin-top: 0;">❌ PDF Lambda Connection Failed</h4>';
                    
                    if (data && data.message) {
                        html += '<p><strong>Error:</strong> ' + data.message + '</p>';
                    }
                    
                    html += '<h5>🛠️ Setup Requirements:</h5>';
                    html += '<ol>';
                    html += '<li>Deploy the cf7as-pdf-generator Lambda function to AWS</li>';
                    html += '<li>Ensure the function has the correct IAM permissions</li>';
                    html += '<li>Configure S3 bucket access for PDF storage</li>';
                    html += '<li>Verify the Lambda function ARN is correct</li>';
                    html += '</ol>';
                    
                    html += '<h5>📋 Deployment Steps:</h5>';
                    html += '<ol>';
                    html += '<li>Navigate to <code>lambda-functions/cf7as-pdf-generator/</code></li>';
                    html += '<li>Run <code>./deploy.sh</code> to deploy the function</li>';
                    html += '<li>Copy the function ARN from the deployment output</li>';
                    html += '<li>Paste the ARN in the field above</li>';
                    html += '</ol>';
                    
                    html += '</div>';
                    
                    $result.html(html).show();
                }
            },
            error: function(xhr, status, error) {
                let html = '<div style="color: #d63638; padding: 10px; background: #fff5f5; border: 1px solid #ff9999; border-radius: 4px;">';
                html += '<h4 style="color: #d63638; margin-top: 0;">❌ Test Request Failed</h4>';
                html += '<p><strong>Error:</strong> ' + error + '</p>';
                html += '<p>Please check your network connection and try again.</p>';
                html += '</div>';
                
                $result.html(html).show();
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle S3 Files Scan
    $('#scan-s3-files').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $result = $('#s3-cleanup-result');
        const originalText = $button.html();
        
        // Update button state
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update spin"></span> Scanning...');
        
        // Show loading state
        $result.show().removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update spin"></span> Scanning S3 for orphaned files...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_cleanup_s3_files',
                cleanup_action: 'scan',
                nonce: '<?php echo wp_create_nonce("cf7_admin_nonce"); ?>'
            },
            timeout: 60000,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('loading error').addClass('success')
                           .html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    
                    // Show stats if available
                    if (response.data.stats) {
                        const stats = response.data.stats;
                        let statsHtml = '<div class="cf7-test-details" style="margin-top: 10px;">';
                        statsHtml += '<strong>📊 Detailed Statistics:</strong><br>';
                        statsHtml += 'Total files tracked: ' + stats.tracked_files + '<br>';
                        statsHtml += 'Active submissions: ' + stats.active_submissions + '<br>';
                        statsHtml += 'Files linked to active submissions: ' + stats.active_files + '<br>';
                        statsHtml += '<strong>Orphaned files: ' + stats.orphaned_files + '</strong><br>';
                        statsHtml += '</div>';
                        $result.append(statsHtml);
                    }
                } else {
                    $result.removeClass('loading success').addClass('error')
                           .html('<span class="dashicons dashicons-warning"></span> ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Scan failed';
                if (status === 'timeout') {
                    errorMessage = 'Scan timed out - your S3 bucket may have many files';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (error) {
                    errorMessage = 'Error: ' + error;
                }
                
                $result.removeClass('loading success').addClass('error')
                       .html('<span class="dashicons dashicons-warning"></span> ' + errorMessage);
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle S3 Files Cleanup
    $('#cleanup-s3-files').on('click', function(e) {
        e.preventDefault();
        
        // Confirmation dialog
        if (!confirm('⚠️ CLEAN UP ORPHANED S3 FILES\n\nThis will:\n• Permanently delete orphaned files from S3 storage\n• Remove database records for deleted files\n• Cannot be undone\n\n💡 Recommendation: Run "Scan for Orphaned Files" first to see what will be deleted.\n\nContinue with cleanup?')) {
            return;
        }
        
        const $button = $(this);
        const $result = $('#s3-cleanup-result');
        const originalText = $button.html();
        
        // Update button state
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update spin"></span> Cleaning...');
        
        // Show loading state
        $result.show().removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update spin"></span> Cleaning up orphaned S3 files...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_cleanup_s3_files',
                cleanup_action: 'cleanup',
                nonce: '<?php echo wp_create_nonce("cf7_admin_nonce"); ?>'
            },
            timeout: 120000, // 2 minutes for cleanup
            success: function(response) {
                if (response.success) {
                    $result.removeClass('loading error').addClass('success')
                           .html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    
                    // Show stats if available
                    if (response.data.stats) {
                        const stats = response.data.stats;
                        let statsHtml = '<div class="cf7-test-details" style="margin-top: 10px;">';
                        statsHtml += '<strong>📊 Cleanup Results:</strong><br>';
                        statsHtml += 'Files successfully cleaned: ' + stats.cleaned_files + '<br>';
                        statsHtml += 'Orphaned files found: ' + stats.orphaned_files + '<br>';
                        
                        if (stats.cleaned_files > 0) {
                            statsHtml += '<br><strong style="color: #00a32a;">✅ Cleanup successful!</strong><br>';
                            statsHtml += 'Removed ' + stats.cleaned_files + ' orphaned files from S3.';
                        }
                        
                        statsHtml += '</div>';
                        $result.append(statsHtml);
                    }
                } else {
                    $result.removeClass('loading success').addClass('error')
                           .html('<span class="dashicons dashicons-warning"></span> ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Cleanup failed';
                if (status === 'timeout') {
                    errorMessage = 'Cleanup timed out - you may have many orphaned files. Try again or contact support.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (error) {
                    errorMessage = 'Error: ' + error;
                }
                
                $result.removeClass('loading success').addClass('error')
                       .html('<span class="dashicons dashicons-warning"></span> ' + errorMessage);
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

}); // End of jQuery document ready

// Handle AWS Settings Save
jQuery(document).ready(function($) {
    $('#save-aws-settings').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $container = $('#aws-settings-form-container');
        const $status = $('#aws-save-status');
        const $message = $status.find('.cf7-save-message');
        
        // Show loading state
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update cf7-spin"></span> Saving...');
        $status.removeClass('success error').hide();
        
        // Collect AWS form data
        const formData = {
            action: 'cf7_save_aws_settings',
            nonce: '<?php echo wp_create_nonce("cf7_admin_nonce"); ?>',
            // S3 Settings
            aws_access_key: $container.find('input[name="cf7_artist_submissions_options[aws_access_key]"]').val(),
            aws_secret_key: $container.find('input[name="cf7_artist_submissions_options[aws_secret_key]"]').val(),
            aws_region: $container.find('select[name="cf7_artist_submissions_options[aws_region]"]').val(),
            s3_bucket: $container.find('input[name="cf7_artist_submissions_options[s3_bucket]"]').val(),
            // MediaConvert Settings
            mediaconvert_endpoint: $container.find('input[name="cf7_artist_submissions_options[mediaconvert_endpoint]"]').val(),
            mediaconvert_role_arn: $container.find('input[name="cf7_artist_submissions_options[mediaconvert_role_arn]"]').val(),
            // Lambda Settings  
            pdf_lambda_function_arn: $container.find('input[name="cf7_artist_submissions_options[pdf_lambda_function_arn]"]').val()
        };
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $status.addClass('success');
                    $message.html('<strong>Success:</strong> ' + response.data.message);
                } else {
                    $status.addClass('error');
                    $message.html('<strong>Error:</strong> ' + response.data.message);
                }
                $status.show();
                
                // Auto-hide success message after 3 seconds
                if (response.success) {
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                $status.addClass('error');
                $message.html('<strong>Error:</strong> Failed to save settings. Please try again.');
                $status.show();
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save AWS Settings');
            }
        });
    });
});

// Toggle secret field visibility
function toggleSecretVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('.dashicons');
    
    if (field.style.webkitTextSecurity === 'disc' || field.style.textSecurity === 'disc') {
        // Show the value
        field.style.webkitTextSecurity = 'none';
        field.style.textSecurity = 'none';
        field.style.letterSpacing = 'normal';
        icon.classList.remove('dashicons-visibility');
        icon.classList.add('dashicons-hidden');
        button.title = 'Hide Secret Key';
    } else {
        // Hide the value
        field.style.webkitTextSecurity = 'disc';
        field.style.textSecurity = 'disc';
        field.style.letterSpacing = '0.1em';
        icon.classList.remove('dashicons-hidden');
        icon.classList.add('dashicons-visibility');
        button.title = 'Show Secret Key';
    }
}

// Close conversion status modal
function closeConversionModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('conversion-status-modal');
    if (modal) {
        modal.remove();
        // Remove keyboard handler
        jQuery(document).off('keydown.conversionModal');
    }
}

// Clear pending conversion jobs
function clearPendingJobs() {
    if (!confirm('⚠️ CLEAR PENDING JOBS\n\nThis will permanently delete all pending conversion jobs.\nThese jobs will not be processed.\n\n⚠️ This action cannot be undone!\n\nContinue?')) {
        return;
    }
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'cf7as_clear_pending_jobs',
            nonce: '<?php echo wp_create_nonce("cf7_admin_nonce"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ Success: ' + response.data.message);
                closeConversionModal();
                // Refresh the status if the button still exists
                if (jQuery('#conversion-status').length) {
                    jQuery('#conversion-status').click();
                }
            } else {
                alert('❌ Error: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('❌ Error: Failed to clear pending jobs. Please try again.');
        }
    });
}

// Clear failed conversion jobs
function clearFailedJobs() {
    if (!confirm('⚠️ CLEAR FAILED JOBS\n\nThis will permanently delete all failed conversion jobs.\nYou can retry these files by reprocessing them.\n\n⚠️ This action cannot be undone!\n\nContinue?')) {
        return;
    }
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'cf7as_clear_failed_jobs',
            nonce: '<?php echo wp_create_nonce("cf7_admin_nonce"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ Success: ' + response.data.message);
                closeConversionModal();
                // Refresh the status if the button still exists
                if (jQuery('#conversion-status').length) {
                    jQuery('#conversion-status').click();
                }
            } else {
                alert('❌ Error: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('❌ Error: Failed to clear failed jobs. Please try again.');
        }
    });
}
</script>

<style>
.cf7-save-status {
    margin-top: 15px;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid;
}

.cf7-save-status.success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.cf7-save-status.error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.cf7-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* S3 Cleanup Button Styles */
#scan-s3-files {
    background-color: #0073aa;
    color: white;
    border: none;
}

#scan-s3-files:hover {
    background-color: #005a87;
}

#cleanup-s3-files {
    background-color: #d63638;
    color: white;
    border: none;
}

#cleanup-s3-files:hover {
    background-color: #a72324;
}

#cleanup-s3-files:disabled {
    background-color: #cccccc;
    color: #666666;
    cursor: not-allowed;
}

/* Cleanup Results Styling */
#s3-cleanup-result .cf7-test-details {
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    padding: 15px;
    margin-top: 10px;
    border-radius: 0 4px 4px 0;
}

#s3-cleanup-result.success .cf7-test-details {
    background: #f0fff0;
    border-left-color: #00a32a;
}

#s3-cleanup-result.error .cf7-test-details {
    background: #fff5f5;
    border-left-color: #d63638;
}
</style>
