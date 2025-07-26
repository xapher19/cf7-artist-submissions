<?php
/**
 * CF7 Artist Submissions - General Settings Tab Template
 *
 * Core configuration interface template providing fundamental system settings
 * including Contact Form 7 integration, menu customization, and file storage
 * preferences for artist submission management workflow configuration.
 *
 * Features:
 * • Contact Form 7 form selection with validation
 * • Admin menu label customization and branding
 * • File storage configuration with space management options
 * • Configuration testing and validation tools
 * • Real-time status indicators and feedback display
 * • Responsive form interface with professional styling
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.0.0
 * @version 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get available Contact Form 7 forms
$forms = array();
if (class_exists('WPCF7_ContactForm')) {
    $cf7_forms = WPCF7_ContactForm::find();
    foreach ($cf7_forms as $form) {
        $forms[$form->id()] = $form->title();
    }
}

$options = get_option('cf7_artist_submissions_options', array());
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('Basic Configuration', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Configure the core settings for your artist submission system, including default forms, menu labels, and file storage options. Use the Open Calls tab to set up multiple submission categories.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <?php if (empty($forms)): ?>
        <div class="cf7-notice cf7-notice-error">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php _e('No Contact Form 7 forms found', 'cf7-artist-submissions'); ?></strong>
                <p><?php _e('Please create at least one Contact Form 7 form before configuring the artist submissions system.', 'cf7-artist-submissions'); ?></p>
            </div>
        </div>
    <?php else: ?>
        <form method="post" action="options.php" class="cf7-settings-form">
            <?php settings_fields('cf7_artist_submissions_options'); ?>
            
            <div class="cf7-card-body">
                <div class="cf7-field-grid two-cols">
                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="cf7_form_id">
                            <span class="dashicons dashicons-forms"></span>
                            <?php _e('Default Contact Form 7 Form', 'cf7-artist-submissions'); ?>
                        </label>
                        <select id="cf7_form_id" name="cf7_artist_submissions_options[form_id]" class="cf7-field-input">
                            <option value=""><?php _e('Select a form...', 'cf7-artist-submissions'); ?></option>
                            <?php foreach ($forms as $form_id => $form_title): ?>
                                <option value="<?php echo esc_attr($form_id); ?>" <?php selected($options['form_id'] ?? '', $form_id); ?>>
                                    #<?php echo esc_html($form_id); ?> - <?php echo esc_html($form_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="cf7-field-help">
                            <?php _e('Select the default Contact Form 7 form for artist submissions. You can configure additional forms for specific open calls in the Open Calls settings tab.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="cf7_menu_label">
                            <span class="dashicons dashicons-menu"></span>
                            <?php _e('Menu Label', 'cf7-artist-submissions'); ?>
                        </label>
                        <input type="text" 
                               id="cf7_menu_label" 
                               name="cf7_artist_submissions_options[menu_label]" 
                               value="<?php echo esc_attr($options['menu_label'] ?? 'Artist Submissions'); ?>" 
                               class="cf7-field-input"
                               placeholder="<?php _e('Artist Submissions', 'cf7-artist-submissions'); ?>">
                        <p class="cf7-field-help">
                            <?php _e('The label that will appear in the WordPress admin menu.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <?php _e('File Storage', 'cf7-artist-submissions'); ?>
                    </label>
                    <div class="cf7-toggle">
                        <input type="checkbox" 
                               id="cf7_store_files" 
                               name="cf7_artist_submissions_options[store_files]" 
                               value="yes" 
                               <?php checked($options['store_files'] ?? false, 'yes'); ?>>
                        <span class="cf7-toggle-slider"></span>
                    </div>
                    <p class="cf7-field-help">
                        <?php _e('Store uploaded files locally. When disabled, files will be deleted after processing to save disk space.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <!-- Open Calls Information Notice -->
                <div class="cf7-notice cf7-notice-info">
                    <span class="dashicons dashicons-megaphone"></span>
                    <div>
                        <strong><?php _e('New: Multiple Open Calls Support', 'cf7-artist-submissions'); ?></strong>
                        <p><?php _e('You can now configure multiple open calls, each with their own Contact Form 7 forms, in the', 'cf7-artist-submissions'); ?> 
                           <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=open-calls"><?php _e('Open Calls settings tab', 'cf7-artist-submissions'); ?></a>. 
                           <?php _e('The form selected above will serve as the default for general submissions.', 'cf7-artist-submissions'); ?></p>
                        <ul style="margin: 10px 0 0 20px; color: #666;">
                            <li><?php _e('Assign different CF7 forms to specific open calls', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Manage call titles, descriptions, and deadlines', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Filter submissions by open call in the dashboard', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Support for both visual and text-based mediums', 'cf7-artist-submissions'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Amazon S3 Configuration Section -->
                <div class="cf7-form-row">
                    <div class="cf7-form-group">
                        <h3 class="cf7-section-title">
                            <span class="dashicons dashicons-cloud-upload"></span>
                            <?php _e('Amazon S3 Configuration', 'cf7-artist-submissions'); ?>
                        </h3>
                        <p class="cf7-field-help">
                            <?php _e('Configure Amazon S3 for secure file uploads and storage.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
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
                            <?php _e('Your AWS access key ID for S3 authentication.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-field-group">
                        <label class="cf7-field-label" for="aws_secret_key">
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('AWS Secret Access Key', 'cf7-artist-submissions'); ?>
                            <span class="cf7-required">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="text" 
                                   id="aws_secret_key" 
                                   name="cf7_artist_submissions_options[aws_secret_key]" 
                                   value="<?php echo esc_attr(isset($options['aws_secret_key']) ? $options['aws_secret_key'] : ''); ?>" 
                                   class="cf7-field-input cf7-secret-field"
                                   autocomplete="off"
                                   data-lpignore="true"
                                   style="padding-right: 45px;"
                                   placeholder="<?php _e('Enter your AWS Secret Access Key', 'cf7-artist-submissions'); ?>">
                            <button type="button" class="cf7-secret-toggle" onclick="toggleSecretVisibility('aws_secret_key')" title="Show/Hide Secret Key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        <p class="cf7-field-help">
                            <?php _e('Your AWS secret access key for S3 authentication.', 'cf7-artist-submissions'); ?>
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
                            $current_region = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
                            foreach ($regions as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($current_region, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="cf7-field-help">
                            <?php _e('Select the AWS region where your S3 bucket is located.', 'cf7-artist-submissions'); ?>
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
                            <?php _e('The name of your S3 bucket where files will be stored. Must be unique globally.', 'cf7-artist-submissions'); ?>
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
                        <div id="s3-test-result" class="cf7-test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <div class="cf7-form-actions">
                <div class="cf7-form-actions-left">
                    <?php if (!empty($options['form_id'])): ?>
                        <div class="cf7-status-indicator cf7-status-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php printf(__('Tracking Form #%s', 'cf7-artist-submissions'), $options['form_id']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cf7-form-actions-right">
                    <button type="button" class="cf7-test-btn" data-action="test-form">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Test Configuration', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="submit" class="cf7-btn cf7-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save Settings', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($options['form_id'])): ?>
            <div class="cf7-settings-card" style="margin-top: 2rem;">
                <div class="cf7-card-header">
                    <h3 class="cf7-card-title">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Current Configuration', 'cf7-artist-submissions'); ?>
                    </h3>
                </div>
                <div class="cf7-card-body">
                    <div class="cf7-field-grid three-cols">
                        <div class="cf7-status-indicator cf7-status-info">
                            <span class="dashicons dashicons-forms"></span>
                            <div>
                                <strong><?php _e('Form ID', 'cf7-artist-submissions'); ?></strong><br>
                                #<?php echo esc_html($options['form_id']); ?>
                            </div>
                        </div>
                        <div class="cf7-status-indicator cf7-status-info">
                            <span class="dashicons dashicons-menu"></span>
                            <div>
                                <strong><?php _e('Menu Label', 'cf7-artist-submissions'); ?></strong><br>
                                <?php echo esc_html($options['menu_label'] ?? 'Artist Submissions'); ?>
                            </div>
                        </div>
                        <div class="cf7-status-indicator cf7-status-info">
                            <span class="dashicons dashicons-cloud-upload"></span>
                            <div>
                                <strong><?php _e('File Storage', 'cf7-artist-submissions'); ?></strong><br>
                                <?php echo ($options['store_files'] ?? false) === 'yes' ? __('Enabled', 'cf7-artist-submissions') : __('Disabled', 'cf7-artist-submissions'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Test Results Container -->
<div id="cf7-email-test-results" class="cf7-test-results" style="display: none;">
    <div class="cf7-test-results-header">
        <h3><?php _e('Test Results', 'cf7-artist-submissions'); ?></h3>
        <button type="button" class="cf7-test-results-close" aria-label="<?php _e('Close', 'cf7-artist-submissions'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="cf7-test-results-body"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
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
            nonce: '<?php echo wp_create_nonce('cf7_artist_submissions_settings'); ?>'
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
    
    // Handle S3 Setup Diagnosis
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
    
    // Handle settings save
    $('.cf7-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"], input[type="submit"]');
        
        // If no submit button found, create one or handle differently
        if ($submitButton.length === 0) {
            // Add a temporary submit button for AJAX handling
            const $tempSubmit = $('<button type="submit" style="display:none;">Save</button>');
            $form.append($tempSubmit);
            handleFormSubmission($form, $tempSubmit);
        } else {
            handleFormSubmission($form, $submitButton);
        }
    });
    
    // Add save button if it doesn't exist
    if ($('.cf7-settings-form').length > 0 && $('.cf7-settings-form').find('button[type="submit"], input[type="submit"]').length === 0) {
        const $saveButton = $('<div class="cf7-form-row"><div class="cf7-form-group"><button type="submit" class="cf7-button cf7-button-primary"><span class="dashicons dashicons-saved"></span> Save Settings</button></div></div>');
        $('.cf7-settings-form .cf7-card-body').append($saveButton);
    }
    
    function handleFormSubmission($form, $submitButton) {
        const originalText = $submitButton.html();
        $submitButton.prop('disabled', true)
                     .html('<span class="dashicons dashicons-update spin"></span> Saving...');
        
        // Get form data manually to ensure secret fields are captured correctly
        const ajaxData = {
            action: 'cf7_bypass_save', // Use bypass method instead of cf7_save_artist_settings
            nonce: cf7_admin_ajax.nonce,
        };
        
        // Add a quick test to verify our AJAX endpoint is working
        const testData = {
            action: 'cf7_test_ajax',
            nonce: cf7_admin_ajax.nonce,
        };
        
        // Test basic AJAX connectivity first
        console.log('Testing AJAX connectivity with nonce:', cf7_admin_ajax.nonce);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: testData,
            success: function(response) {
                console.log('AJAX Test Response:', response);
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log('AJAX Test Failed:', xhr, textStatus, errorThrown);
            }
        });
        
        // Manually get each field value to avoid issues with styled fields
        ajaxData.form_id = $('#cf7_form_id').val() || '';
        ajaxData.menu_label = $('#cf7_menu_label').val() || 'Artist Submissions';
        ajaxData.store_files = $('#cf7_store_files').is(':checked') ? 'yes' : '';
        ajaxData.aws_access_key = $('#aws_access_key').val() || '';
        
        // Special handling for secret key to prevent URL encoding issues with + character
        var secretKeyValue = $('#aws_secret_key').val() || '';
        console.log('Original secret key:', secretKeyValue);
        console.log('Original secret key length:', secretKeyValue.length);
        ajaxData.aws_secret_key = secretKeyValue;  // Direct value access
        
        ajaxData.aws_region = $('#aws_region').val() || 'us-east-1';
        ajaxData.s3_bucket = $('#s3_bucket').val() || '';
        
        // Debug logging
        console.log('AJAX URL:', ajaxurl);
        console.log('AJAX Data:', ajaxData);
        console.log('Secret key being sent:', ajaxData.aws_secret_key);
        console.log('Secret key length being sent:', ajaxData.aws_secret_key.length);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            processData: true,  // Keep jQuery's default processing
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',  // Explicit content type
            success: function(response, textStatus, xhr) {
                console.log('AJAX Response:', response);
                if (response && response.success) {
                    showNotice('success', response.data.message || 'Settings saved successfully!');
                } else {
                    showNotice('error', response && response.data ? response.data.message : 'Failed to save settings');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                let errorMsg = 'AJAX Error: ';
                if (xhr.status) {
                    errorMsg += `HTTP ${xhr.status} - ${xhr.statusText}`;
                }
                if (xhr.responseText) {
                    errorMsg += ` | Response: ${xhr.responseText.substring(0, 200)}...`;
                }
                if (errorThrown) {
                    errorMsg += ` | Thrown: ${errorThrown}`;
                }
                
                showNotice('error', errorMsg);
            },
            complete: function(xhr, textStatus) {
                $submitButton.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function showNotice(type, message) {
        // Remove existing notices
        $('.cf7-notice.auto-generated').remove();
        
        const noticeClass = type === 'success' ? 'cf7-notice-success' : 'cf7-notice-error';
        const icon = type === 'success' ? 'yes-alt' : 'warning';
        
        const $notice = $('<div class="cf7-notice ' + noticeClass + ' auto-generated">' +
                         '<span class="dashicons dashicons-' + icon + '"></span>' +
                         '<div><strong>' + message + '</strong></div>' +
                         '</div>');
        
        // Insert notice at the top of the settings area
        $('.cf7-modern-settings').prepend($notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
        
        // Scroll to top to show notice
        $('html, body').animate({
            scrollTop: $('.cf7-modern-settings').offset().top - 50
        }, 500);
    }
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

// Initialize secret field as hidden on page load
jQuery(document).ready(function($) {
    const secretField = document.getElementById('aws_secret_key');
    if (secretField && secretField.value) {
        // Use setTimeout to ensure field is fully loaded before applying styles
        setTimeout(function() {
            secretField.style.webkitTextSecurity = 'disc';
            secretField.style.textSecurity = 'disc';
            secretField.style.letterSpacing = '0.1em';
        }, 100);
    }
});
</script>