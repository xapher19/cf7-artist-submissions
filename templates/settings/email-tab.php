<?php
/**
 * Email Settings Tab Template
 * 
 * @package CF7_Artist_Submissions
 * @since 2.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$email_options = get_option('cf7_artist_submissions_email_options', array());
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-email-alt"></span>
            <?php _e('Email Configuration', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Configure SMTP settings and outbound email preferences for your artist submissions system.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <form method="post" action="options.php" class="cf7-settings-form">
        <?php settings_fields('cf7_artist_submissions_email_options'); ?>
        
        <div class="cf7-card-body">
            <div class="cf7-field-grid two-cols">
                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('From Email Address', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="email" 
                           name="cf7_artist_submissions_email_options[from_email]" 
                           value="<?php echo esc_attr($email_options['from_email'] ?? get_option('admin_email')); ?>" 
                           class="cf7-field-input"
                           placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                    <p class="cf7-field-help">
                        <?php _e('The email address that emails will be sent from. Make sure this email is authorized in your SMTP provider settings.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('From Name', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="text" 
                           name="cf7_artist_submissions_email_options[from_name]" 
                           value="<?php echo esc_attr($email_options['from_name'] ?? get_bloginfo('name')); ?>" 
                           class="cf7-field-input"
                           placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <p class="cf7-field-help">
                        <?php _e('The name that emails will be sent from (e.g. "Your Gallery Name").', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
            </div>

            <div class="cf7-field-group">
                <label class="cf7-field-label">
                    <span class="dashicons dashicons-cart"></span>
                    <?php _e('WooCommerce Email Template', 'cf7-artist-submissions'); ?>
                </label>
                <div class="cf7-toggle">
                    <input type="hidden" name="cf7_artist_submissions_email_options[use_wc_template]" value="0">
                    <input type="checkbox" 
                           id="use_wc_template"
                           name="cf7_artist_submissions_email_options[use_wc_template]" 
                           value="1" 
                           <?php checked($email_options['use_wc_template'] ?? false, 1); ?>>
                    <span class="cf7-toggle-slider"></span>
                </div>
                <p class="cf7-field-help">
                    <?php _e('When enabled, emails will be styled using the WooCommerce email template for consistent branding. Requires WooCommerce plugin.', 'cf7-artist-submissions'); ?>
                </p>
                
                <?php if (class_exists('WooCommerce')): ?>
                    <div class="cf7-field-actions">
                        <button type="button" class="cf7-btn cf7-btn-secondary" id="preview-wc-template">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Preview WooCommerce Template', 'cf7-artist-submissions'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="cf7-notice cf7-notice-warning">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php _e('WooCommerce Not Detected', 'cf7-artist-submissions'); ?></strong>
                            <p><?php _e('Install and activate WooCommerce to use this feature.', 'cf7-artist-submissions'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cf7-form-actions">
            <div class="cf7-form-actions-left">
                <button type="button" class="cf7-test-btn" data-action="validate-email-config">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Validate Configuration', 'cf7-artist-submissions'); ?>
                </button>
                <button type="button" class="cf7-test-btn" data-action="test-smtp">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Send Test Email', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            <div class="cf7-form-actions-right">
                <button type="submit" class="cf7-btn cf7-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Settings', 'cf7-artist-submissions'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- WooCommerce Template Preview Modal -->
<div id="wc-template-preview-modal" class="cf7-modal" style="display: none;">
    <div class="cf7-modal-content">
        <div class="cf7-modal-header">
            <h3><?php _e('WooCommerce Email Template Preview', 'cf7-artist-submissions'); ?></h3>
            <button type="button" class="cf7-modal-close" id="close-wc-preview">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="cf7-modal-body">
            <div id="wc-template-preview-content">
                <p><?php _e('Loading preview...', 'cf7-artist-submissions'); ?></p>
            </div>
        </div>
        <div class="cf7-modal-footer">
            <button type="button" class="cf7-btn cf7-btn-secondary" id="close-wc-preview-footer">
                <?php _e('Close Preview', 'cf7-artist-submissions'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Test Results Container -->
<div id="cf7-email-test-results" class="cf7-test-results" style="display: none;">
    <div class="cf7-test-results-content">
        <div class="cf7-test-results-header">
            <h4><?php _e('Test Results', 'cf7-artist-submissions'); ?></h4>
            <button type="button" class="cf7-test-results-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="cf7-test-results-body">
            <!-- Results will be populated via AJAX -->
        </div>
    </div>
</div>
