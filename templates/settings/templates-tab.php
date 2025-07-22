<?php
/**
 * CF7 Artist Submissions - Email Templates Tab Template
 *
 * Email template customization interface providing comprehensive template
 * management for automated notifications, status updates, and custom
 * communications with merge tag support and preview functionality.
 *
 * Features:
 * • Multiple email templates for submission lifecycle events
 * • Merge tag system for dynamic content personalization
 * • Template preview functionality with real-time rendering
 * • Automatic and manual email sending configuration
 * • Template reset and default restoration capabilities
 * • Test email functionality with validation feedback
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.0.0
 * @version 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$template_options = get_option('cf7_artist_submissions_email_templates', array());
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-editor-code"></span>
            <?php _e('Email Templates', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Customize the email templates sent to artists and administrators.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <form method="post" action="options.php" class="cf7-settings-form">
        <?php settings_fields('cf7_artist_submissions_email_templates'); ?>
        
        <div class="cf7-card-body">
            <div class="cf7-notice cf7-notice-info">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Available Merge Tags', 'cf7-artist-submissions'); ?></strong>
                    <p><?php _e('Use these merge tags in your templates:', 'cf7-artist-submissions'); ?>
                    <code>{artist_name}</code>, <code>{artist_email}</code>, <code>{submission_title}</code>, 
                    <code>{submission_id}</code>, <code>{site_name}</code>, <code>{site_url}</code></p>
                </div>
            </div>

            <?php
            // Get email triggers from settings class
            $triggers = array(
                'submission_received' => array(
                    'name' => __('Submission Received', 'cf7-artist-submissions'),
                    'description' => __('Sent when a new submission is received', 'cf7-artist-submissions'),
                    'auto' => true,
                    'default_subject' => __('New submission received', 'cf7-artist-submissions'),
                    'default_body' => "Dear {artist_name},\n\nThank you for your submission.\n\nRegards,\n{site_name} Team"
                ),
                'status_changed_to_selected' => array(
                    'name' => __('Status Changed to Selected', 'cf7-artist-submissions'),
                    'description' => __('Sent when an artist is selected', 'cf7-artist-submissions'),
                    'auto' => true,
                    'default_subject' => __('Congratulations! You have been selected', 'cf7-artist-submissions'),
                    'default_body' => "Dear {artist_name},\n\nCongratulations! Your submission has been selected.\n\nRegards,\n{site_name} Team"
                ),
                'status_changed_to_reviewed' => array(
                    'name' => __('Status Changed to Reviewed', 'cf7-artist-submissions'),
                    'description' => __('Sent when a submission is marked as reviewed', 'cf7-artist-submissions'),
                    'auto' => true,
                    'default_subject' => __('Your submission has been reviewed', 'cf7-artist-submissions'),
                    'default_body' => "Dear {artist_name},\n\nYour submission has been reviewed.\n\nRegards,\n{site_name} Team"
                ),
                'status_changed_to_shortlisted' => array(
                    'name' => __('Status Changed to Shortlisted', 'cf7-artist-submissions'),
                    'description' => __('Sent when a submission is shortlisted for consideration', 'cf7-artist-submissions'),
                    'auto' => true,
                    'default_subject' => __('Your submission has been shortlisted', 'cf7-artist-submissions'),
                    'default_body' => "Dear {artist_name},\n\nGreat news! Your submission has been shortlisted for consideration.\n\nRegards,\n{site_name} Team"
                ),
                'custom_notification' => array(
                    'name' => __('Custom Notification', 'cf7-artist-submissions'),
                    'description' => __('A custom email that can be sent manually at any time', 'cf7-artist-submissions'),
                    'auto' => false,
                    'default_subject' => __('Update regarding your submission', 'cf7-artist-submissions'),
                    'default_body' => "Dear {artist_name},\n\nThis is a custom notification regarding your submission.\n\nRegards,\n{site_name} Team"
                )
            );

            foreach ($triggers as $trigger_id => $trigger):
                // Get current template settings
                $template = isset($template_options[$trigger_id]) ? $template_options[$trigger_id] : array(
                    'enabled' => false,
                    'subject' => $trigger['default_subject'],
                    'body' => $trigger['default_body'],
                    'auto_send' => $trigger['auto']
                );
            ?>
                <div class="cf7-template-section" data-template="<?php echo esc_attr($trigger_id); ?>">
                    <div class="cf7-template-header">
                        <div class="cf7-template-title">
                            <h3><?php echo esc_html($trigger['name']); ?></h3>
                            <p class="cf7-template-description"><?php echo esc_html($trigger['description']); ?></p>
                        </div>
                        <div class="cf7-template-toggle">
                            <div class="cf7-toggle">
                                <input type="hidden" 
                                       name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][enabled]" 
                                       value="0">
                                <input type="checkbox" 
                                       id="template_enabled_<?php echo esc_attr($trigger_id); ?>"
                                       name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][enabled]" 
                                       value="1" 
                                       <?php checked($template['enabled'], 1); ?>>
                                <span class="cf7-toggle-slider"></span>
                            </div>
                            <label for="template_enabled_<?php echo esc_attr($trigger_id); ?>" class="cf7-toggle-label">
                                <?php _e('Enable Template', 'cf7-artist-submissions'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="cf7-template-content <?php echo $template['enabled'] ? 'active' : ''; ?>">
                        <?php if ($trigger['auto'] !== false): ?>
                            <div class="cf7-field-group">
                                <div class="cf7-toggle">
                                    <input type="hidden" 
                                           name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][auto_send]" 
                                           value="0">
                                    <input type="checkbox" 
                                           id="template_auto_<?php echo esc_attr($trigger_id); ?>"
                                           name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][auto_send]" 
                                           value="1" 
                                           <?php checked($template['auto_send'], 1); ?>>
                                    <span class="cf7-toggle-slider"></span>
                                </div>
                                <label for="template_auto_<?php echo esc_attr($trigger_id); ?>" class="cf7-toggle-label">
                                    <?php _e('Automatically send this email when triggered', 'cf7-artist-submissions'); ?>
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="cf7-field-group">
                            <label class="cf7-field-label" for="template_subject_<?php echo esc_attr($trigger_id); ?>">
                                <span class="dashicons dashicons-email-alt"></span>
                                <?php _e('Email Subject', 'cf7-artist-submissions'); ?>
                            </label>
                            <input type="text" 
                                   id="template_subject_<?php echo esc_attr($trigger_id); ?>"
                                   name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][subject]" 
                                   value="<?php echo esc_attr($template['subject']); ?>" 
                                   class="cf7-field-input cf7-template-subject"
                                   placeholder="<?php echo esc_attr($trigger['default_subject']); ?>">
                        </div>

                        <div class="cf7-field-group">
                            <label class="cf7-field-label" for="template_body_<?php echo esc_attr($trigger_id); ?>">
                                <span class="dashicons dashicons-editor-alignleft"></span>
                                <?php _e('Email Body', 'cf7-artist-submissions'); ?>
                            </label>
                            <textarea id="template_body_<?php echo esc_attr($trigger_id); ?>"
                                      name="cf7_artist_submissions_email_templates[<?php echo esc_attr($trigger_id); ?>][body]" 
                                      class="cf7-field-textarea cf7-template-body"
                                      rows="8"
                                      placeholder="<?php echo esc_attr($trigger['default_body']); ?>"><?php echo esc_textarea($template['body']); ?></textarea>
                            <div class="cf7-template-actions">
                                <button type="button" class="cf7-btn cf7-btn-secondary cf7-template-preview" 
                                        data-template="<?php echo esc_attr($trigger_id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('Preview', 'cf7-artist-submissions'); ?>
                                </button>
                                <button type="button" class="cf7-btn cf7-btn-secondary cf7-template-reset" 
                                        data-template="<?php echo esc_attr($trigger_id); ?>">
                                    <span class="dashicons dashicons-undo"></span>
                                    <?php _e('Reset to Default', 'cf7-artist-submissions'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cf7-form-actions">
            <div class="cf7-form-actions-left">
                <button type="button" class="cf7-test-btn" data-action="test-template">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('Send Test Email', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            <div class="cf7-form-actions-right">
                <button type="submit" class="cf7-btn cf7-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Templates', 'cf7-artist-submissions'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template Preview Modal -->
<div id="cf7-template-preview-modal" class="cf7-modal" style="display: none;">
    <div class="cf7-modal-content">
        <div class="cf7-modal-header">
            <h3><?php _e('Template Preview', 'cf7-artist-submissions'); ?></h3>
            <button type="button" class="cf7-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="cf7-modal-body">
            <div id="cf7-template-preview-content">
                <!-- Preview content will be populated here -->
            </div>
        </div>
        <div class="cf7-modal-footer">
            <button type="button" class="cf7-btn cf7-btn-secondary cf7-modal-close">
                <?php _e('Close Preview', 'cf7-artist-submissions'); ?>
            </button>
        </div>
    </div>
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
