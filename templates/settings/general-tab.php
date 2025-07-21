<?php
/**
 * General Settings Tab Template
 * 
 * @package CF7_Artist_Submissions
 * @since 2.1.0
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
            <?php _e('Configure the core settings for your artist submission system.', 'cf7-artist-submissions'); ?>
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
                            <?php _e('Contact Form 7 Form', 'cf7-artist-submissions'); ?>
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
                            <?php _e('Select the Contact Form 7 form that will be used for artist submissions.', 'cf7-artist-submissions'); ?>
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
