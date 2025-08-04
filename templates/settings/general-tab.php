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
 * @version 1.2.0
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

                <!-- AWS Configuration Notice -->
                <div class="cf7-notice cf7-notice-info">
                    <span class="dashicons dashicons-cloud"></span>
                    <div>
                        <strong><?php _e('Advanced: AWS Integration Available', 'cf7-artist-submissions'); ?></strong>
                        <p><?php _e('For advanced file processing, automatic image optimization, and video conversion, configure AWS services in the', 'cf7-artist-submissions'); ?> 
                           <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=aws"><?php _e('AWS settings tab', 'cf7-artist-submissions'); ?></a>.</p>
                        <ul style="margin: 10px 0 0 20px; color: #666;">
                            <li><?php _e('Amazon S3 for secure cloud storage', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Lambda functions for automatic image processing', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('MediaConvert for video file optimization', 'cf7-artist-submissions'); ?></li>
                            <li><?php _e('Thumbnail generation and multiple size variants', 'cf7-artist-submissions'); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Orphaned Data Cleanup Section -->
                <div class="cf7-form-section">
                    <div class="cf7-section-header">
                        <h3 class="cf7-section-title">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('System Maintenance', 'cf7-artist-submissions'); ?>
                        </h3>
                        <p class="cf7-section-description">
                            <?php _e('Clean up orphaned data that may remain after deleting submissions or from system issues.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>

                    <div class="cf7-notice cf7-notice-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php _e('Orphaned Data Cleanup', 'cf7-artist-submissions'); ?></strong>
                            <p><?php _e('When submissions are deleted, all associated data should be automatically cleaned up. However, database issues or interrupted deletions may leave orphaned records. Use these tools to scan for and remove any orphaned data.', 'cf7-artist-submissions'); ?></p>
                            <ul style="margin: 10px 0 0 20px; color: #666;">
                                <li><?php _e('Actions records not linked to active submissions', 'cf7-artist-submissions'); ?></li>
                                <li><?php _e('Conversation records without valid submissions', 'cf7-artist-submissions'); ?></li>
                                <li><?php _e('File records for missing submission entries', 'cf7-artist-submissions'); ?></li>
                                <li><?php _e('PDF generation records for deleted submissions', 'cf7-artist-submissions'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="cf7-form-row">
                        <div class="cf7-form-group">
                            <button type="button" id="scan-orphaned-data" class="cf7-test-btn">
                                <span class="dashicons dashicons-search"></span>
                                <?php _e('Scan for Orphaned Data', 'cf7-artist-submissions'); ?>
                            </button>
                            <button type="button" id="cleanup-orphaned-data" class="cf7-test-btn" style="margin-left: 10px; background-color: #d63638;">
                                <span class="dashicons dashicons-trash"></span>
                                <?php _e('Clean Up Orphaned Data', 'cf7-artist-submissions'); ?>
                            </button>
                            <div id="orphaned-data-result" class="cf7-test-result" style="display: none;"></div>
                        </div>
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
        
        // Get form data manually to ensure fields are captured correctly
        const ajaxData = {
            action: 'cf7_bypass_save', // Use bypass method instead of cf7_save_artist_settings
            nonce: cf7_admin_ajax.nonce,
        };
        
        // Manually get each field value to avoid issues with styled fields
        ajaxData.form_id = $('#cf7_form_id').val() || '';
        ajaxData.menu_label = $('#cf7_menu_label').val() || 'Artist Submissions';
        ajaxData.store_files = $('#cf7_store_files').is(':checked') ? 'yes' : '';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            processData: true,  // Keep jQuery's default processing
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',  // Explicit content type
            success: function(response, textStatus, xhr) {
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
    
    // Handle Orphaned Data Scan
    $('#scan-orphaned-data').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $result = $('#orphaned-data-result');
        const originalText = $button.html();
        
        // Update button state
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update spin"></span> Scanning...');
        
        // Show loading state
        $result.show().removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update spin"></span> Scanning for orphaned data...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_cleanup_orphaned_data',
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
                        statsHtml += 'Active submissions: ' + stats.active_submissions + '<br>';
                        statsHtml += 'Orphaned actions: ' + stats.orphaned_actions + '<br>';
                        statsHtml += 'Orphaned conversations: ' + stats.orphaned_conversations + '<br>';
                        statsHtml += 'Orphaned files: ' + stats.orphaned_files + '<br>';
                        statsHtml += 'Orphaned PDFs: ' + stats.orphaned_pdfs + '<br>';
                        statsHtml += '<strong>Total orphaned records: ' + stats.total_orphaned + '</strong><br>';
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
                    errorMessage = 'Scan timed out - your database may have large amounts of data';
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
    
    // Handle Orphaned Data Cleanup
    $('#cleanup-orphaned-data').on('click', function(e) {
        e.preventDefault();
        
        // Confirmation dialog
        if (!confirm('⚠️ CLEAN UP ORPHANED DATA\n\nThis will:\n• Permanently delete orphaned actions, conversations, files, and PDF records\n• Remove database records that are not linked to active submissions\n• Cannot be undone\n\n💡 Recommendation: Run "Scan for Orphaned Data" first to see what will be deleted.\n\nContinue with cleanup?')) {
            return;
        }
        
        const $button = $(this);
        const $result = $('#orphaned-data-result');
        const originalText = $button.html();
        
        // Update button state
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update spin"></span> Cleaning...');
        
        // Show loading state
        $result.show().removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update spin"></span> Cleaning up orphaned data...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7as_cleanup_orphaned_data',
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
                        statsHtml += 'Actions cleaned: ' + stats.cleaned_actions + '<br>';
                        statsHtml += 'Conversations cleaned: ' + stats.cleaned_conversations + '<br>';
                        statsHtml += 'Files cleaned: ' + stats.cleaned_files + '<br>';
                        statsHtml += 'PDFs cleaned: ' + stats.cleaned_pdfs + '<br>';
                        statsHtml += '<strong>Total cleaned: ' + stats.total_cleaned + '</strong><br>';
                        
                        if (stats.total_cleaned > 0) {
                            statsHtml += '<br><strong style="color: #00a32a;">✅ Cleanup successful!</strong><br>';
                            statsHtml += 'Removed ' + stats.total_cleaned + ' orphaned records from the database.';
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
                    errorMessage = 'Cleanup timed out - you may have many orphaned records. Try again or contact support.';
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
});
</script>

<style>
/* Orphaned Data Cleanup Styles */
#scan-orphaned-data {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    transition: background-color 0.2s;
}

#scan-orphaned-data:hover {
    background: #005a87;
}

#scan-orphaned-data:disabled {
    background: #ccc;
    cursor: not-allowed;
}

#cleanup-orphaned-data {
    background: #d63638;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    transition: background-color 0.2s;
}

#cleanup-orphaned-data:hover {
    background: #b32d2e;
}

#cleanup-orphaned-data:disabled {
    background: #ccc;
    cursor: not-allowed;
}

#orphaned-data-result {
    margin-top: 15px;
    padding: 10px 15px;
    border-radius: 4px;
    background: #f9f9f9;
    border-left: 4px solid #ddd;
}

#orphaned-data-result.success {
    background: #f0fff0;
    border-left-color: #00a32a;
    color: #155724;
}

#orphaned-data-result.error {
    background: #fff5f5;
    border-left-color: #dc3232;
    color: #721c24;
}

#orphaned-data-result.loading {
    background: #f8f9fa;
    border-left-color: #007cba;
    color: #0c5460;
}

.cf7-test-details {
    margin-top: 10px;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.4;
    background: rgba(0,0,0,0.05);
    padding: 8px;
    border-radius: 3px;
}
</style>