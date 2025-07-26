<?php
/**
 * CF7 Artist Submissions - Open Calls Settings Tab Template
 *
 * Configuration interface for managing multiple open calls with individual
 * Contact Form 7 form assignments, titles, and submission management settings.
 * Enables call set functionality where each call maintains unique forms and
 * requirements while providing unified submission management interface.
 *
 * Features:
 * • Multiple open call configuration with unique titles and descriptions
 * • Individual Contact Form 7 form assignment per open call
 * • Dynamic add/remove functionality for call management
 * • Call status management (active/inactive) with visual indicators
 * • Bulk operations for efficient call administration
 * • Real-time validation and feedback for configuration changes
 * • Responsive interface with professional card-based design
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

// Get available Contact Form 7 forms
$forms = array();
if (class_exists('WPCF7_ContactForm')) {
    $cf7_forms = WPCF7_ContactForm::find();
    foreach ($cf7_forms as $form) {
        $forms[$form->id()] = $form->title();
    }
}

// Get current open calls configuration
$open_calls_options = get_option('cf7_artist_submissions_open_calls', array());

// Populate term_id for existing calls if missing
if (!empty($open_calls_options['calls'])) {
    foreach ($open_calls_options['calls'] as $index => $call) {
        if (empty($call['term_id']) && !empty($call['title'])) {
            // Try to find existing term by name
            $existing_term = get_term_by('name', $call['title'], 'open_call');
            if ($existing_term) {
                $open_calls_options['calls'][$index]['term_id'] = $existing_term->term_id;
            }
        }
    }
    // Update the option with populated term_ids
    update_option('cf7_artist_submissions_open_calls', $open_calls_options);
}

// Get existing open call terms for reference
$existing_calls = get_terms(array(
    'taxonomy' => 'open_call',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Initialize default structure if empty
if (empty($open_calls_options)) {
    $open_calls_options = array(
        'calls' => array()
    );
}
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-megaphone"></span>
            <?php _e('Open Calls Configuration', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Configure multiple open calls with individual Contact Form 7 forms. Each open call can have its own form, title, and requirements while maintaining unified submission management.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <?php if (empty($forms)): ?>
        <div class="cf7-notice cf7-notice-error">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php _e('No Contact Form 7 forms found', 'cf7-artist-submissions'); ?></strong>
                <p><?php _e('Please create at least one Contact Form 7 form before configuring open calls.', 'cf7-artist-submissions'); ?></p>
            </div>
        </div>
    <?php else: ?>
        <form method="post" action="options.php" class="cf7-settings-form" id="cf7-open-calls-form">
            <?php settings_fields('cf7_artist_submissions_open_calls'); ?>
            
            <div class="cf7-card-body">
                <!-- Existing Terms Notice -->
                <?php if (!empty($existing_calls)): ?>
                    <div class="cf7-notice cf7-notice-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php _e('Existing Open Call Terms', 'cf7-artist-submissions'); ?></strong>
                            <p><?php _e('The following open call terms already exist in your system:', 'cf7-artist-submissions'); ?></p>
                            <ul style="margin: 10px 0;">
                                <?php foreach ($existing_calls as $call): ?>
                                    <li><strong><?php echo esc_html($call->name); ?></strong> (<?php echo $call->count; ?> submissions)</li>
                                <?php endforeach; ?>
                            </ul>
                            <p><?php _e('Configure these calls below to assign Contact Form 7 forms and manage their settings.', 'cf7-artist-submissions'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Open Calls Configuration -->
                <div class="cf7-open-calls-container">
                    <div class="cf7-section-header">
                        <h3><?php _e('Open Calls Setup', 'cf7-artist-submissions'); ?></h3>
                        <button type="button" class="cf7-btn cf7-btn-primary" id="cf7-add-open-call">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add New Open Call', 'cf7-artist-submissions'); ?>
                        </button>
                    </div>

                    <div id="cf7-open-calls-list">
                        <?php 
                        $calls = $open_calls_options['calls'] ?? array();
                        
                        // If no calls configured yet, show one empty call
                        if (empty($calls)) {
                            $calls = array(array());
                        }
                        
                        foreach ($calls as $index => $call): 
                        ?>
                            <div class="cf7-open-call-item" data-index="<?php echo $index; ?>">
                                <div class="cf7-call-header">
                                    <h4 class="cf7-call-title">
                                        <span class="dashicons dashicons-megaphone"></span>
                                        <?php echo !empty($call['title']) ? esc_html($call['title']) : __('New Open Call', 'cf7-artist-submissions'); ?>
                                    </h4>
                                    <div class="cf7-call-actions">
                                        <button type="button" class="cf7-btn cf7-btn-ghost cf7-toggle-call" title="<?php _e('Expand/Collapse', 'cf7-artist-submissions'); ?>">
                                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                                        </button>
                                        <button type="button" class="cf7-btn cf7-btn-danger cf7-remove-call" title="<?php _e('Remove Call', 'cf7-artist-submissions'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="cf7-call-content expanded">
                                    <!-- Hidden field to track term_id -->
                                    <input type="hidden" 
                                           name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][term_id]" 
                                           value="<?php echo esc_attr($call['term_id'] ?? ''); ?>">
                                    
                                    <div class="cf7-field-grid two-cols">
                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-edit"></span>
                                                <?php _e('Call Title', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <input type="text" 
                                                   name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][title]" 
                                                   value="<?php echo esc_attr($call['title'] ?? ''); ?>" 
                                                   class="cf7-field-input cf7-call-title-input"
                                                   placeholder="<?php _e('e.g., Spring Exhibition 2025', 'cf7-artist-submissions'); ?>">
                                            <p class="cf7-field-help">
                                                <?php _e('The display title for this open call.', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>

                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-tag"></span>
                                                <?php _e('Dashboard Tag', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <input type="text" 
                                                   name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][dashboard_tag]" 
                                                   value="<?php echo esc_attr($call['dashboard_tag'] ?? ''); ?>" 
                                                   class="cf7-field-input cf7-call-dashboard-tag-input"
                                                   placeholder="<?php _e('e.g., Spring, Exhibitions', 'cf7-artist-submissions'); ?>"
                                                   maxlength="20">
                                            <p class="cf7-field-help">
                                                <?php _e('Short tag (1-2 words) to display in dashboard submissions list instead of full title.', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="cf7-field-grid two-cols">
                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-category"></span>
                                                <?php _e('Call Type', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <select name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][call_type]" 
                                                    class="cf7-field-input">
                                                <option value=""><?php _e('Select call type...', 'cf7-artist-submissions'); ?></option>
                                                <option value="visual_arts" <?php selected($call['call_type'] ?? '', 'visual_arts'); ?>>
                                                    <?php _e('Visual Arts', 'cf7-artist-submissions'); ?>
                                                </option>
                                                <option value="text_based" <?php selected($call['call_type'] ?? '', 'text_based'); ?>>
                                                    <?php _e('Text-based', 'cf7-artist-submissions'); ?>
                                                </option>
                                            </select>
                                            <p class="cf7-field-help">
                                                <?php _e('Determines available mediums and accepted file types for submissions.', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>

                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-forms"></span>
                                                <?php _e('Contact Form 7 Form', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <select name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][form_id]" 
                                                    class="cf7-field-input">
                                                <option value=""><?php _e('Select a form...', 'cf7-artist-submissions'); ?></option>
                                                <?php foreach ($forms as $form_id => $form_title): ?>
                                                    <option value="<?php echo esc_attr($form_id); ?>" 
                                                            <?php selected($call['form_id'] ?? '', $form_id); ?>>
                                                        #<?php echo esc_html($form_id); ?> - <?php echo esc_html($form_title); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="cf7-field-help">
                                                <?php _e('The Contact Form 7 form to use for this open call.', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="cf7-field-group">
                                        <label class="cf7-field-label">
                                            <span class="dashicons dashicons-text-page"></span>
                                            <?php _e('Description', 'cf7-artist-submissions'); ?>
                                        </label>
                                        <textarea name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][description]" 
                                                  class="cf7-field-input" 
                                                  rows="3"
                                                  placeholder="<?php _e('Brief description of this open call...', 'cf7-artist-submissions'); ?>"><?php echo esc_textarea($call['description'] ?? ''); ?></textarea>
                                        <p class="cf7-field-help">
                                            <?php _e('Optional description for internal reference.', 'cf7-artist-submissions'); ?>
                                        </p>
                                    </div>

                                    <div class="cf7-field-grid three-cols">
                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-calendar-alt"></span>
                                                <?php _e('Start Date', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <input type="date" 
                                                   name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][start_date]" 
                                                   value="<?php echo esc_attr($call['start_date'] ?? ''); ?>" 
                                                   class="cf7-field-input">
                                            <p class="cf7-field-help">
                                                <?php _e('When this call opens (optional).', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>

                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-calendar-alt"></span>
                                                <?php _e('End Date', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <input type="date" 
                                                   name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][end_date]" 
                                                   value="<?php echo esc_attr($call['end_date'] ?? ''); ?>" 
                                                   class="cf7-field-input">
                                            <p class="cf7-field-help">
                                                <?php _e('Submission deadline (optional).', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>

                                        <div class="cf7-field-group">
                                            <label class="cf7-field-label">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php _e('Status', 'cf7-artist-submissions'); ?>
                                            </label>
                                            <select name="cf7_artist_submissions_open_calls[calls][<?php echo $index; ?>][status]" 
                                                    class="cf7-field-input">
                                                <option value="active" <?php selected($call['status'] ?? 'active', 'active'); ?>>
                                                    <?php _e('Active', 'cf7-artist-submissions'); ?>
                                                </option>
                                                <option value="inactive" <?php selected($call['status'] ?? 'active', 'inactive'); ?>>
                                                    <?php _e('Inactive', 'cf7-artist-submissions'); ?>
                                                </option>
                                                <option value="draft" <?php selected($call['status'] ?? 'active', 'draft'); ?>>
                                                    <?php _e('Draft', 'cf7-artist-submissions'); ?>
                                                </option>
                                            </select>
                                            <p class="cf7-field-help">
                                                <?php _e('Current status of this call.', 'cf7-artist-submissions'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="cf7-section-divider"></div>
                
                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Default Open Call Behavior', 'cf7-artist-submissions'); ?>
                    </label>
                    <div class="cf7-checkbox-group">
                        <label class="cf7-checkbox-label">
                            <input type="checkbox" 
                                   name="cf7_artist_submissions_open_calls[auto_create_terms]" 
                                   value="1" 
                                   <?php checked($open_calls_options['auto_create_terms'] ?? true, true); ?>>
                            <span class="cf7-checkbox-custom"></span>
                            <span><?php _e('Automatically create taxonomy terms for new open calls', 'cf7-artist-submissions'); ?></span>
                        </label>
                        <p class="cf7-field-help">
                            <?php _e('When enabled, new open call terms will be automatically created in the taxonomy when you save settings.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="cf7-card-footer">
                <button type="submit" class="cf7-btn cf7-btn-primary cf7-btn-large">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Save Open Calls Configuration', 'cf7-artist-submissions'); ?>
                </button>
                <p class="cf7-save-notice">
                    <?php _e('Changes will take effect immediately and update your open call taxonomy terms.', 'cf7-artist-submissions'); ?>
                </p>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Open Call Template (Hidden) -->
<div id="cf7-open-call-template" style="display: none;">
    <div class="cf7-open-call-item" data-index="{{INDEX}}">
        <div class="cf7-call-header">
            <h4 class="cf7-call-title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('New Open Call', 'cf7-artist-submissions'); ?>
            </h4>
            <div class="cf7-call-actions">
                <button type="button" class="cf7-btn cf7-btn-ghost cf7-toggle-call" title="<?php _e('Expand/Collapse', 'cf7-artist-submissions'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <button type="button" class="cf7-btn cf7-btn-danger cf7-remove-call" title="<?php _e('Remove Call', 'cf7-artist-submissions'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>

        <div class="cf7-call-content expanded">
            <!-- Hidden field to track term_id -->
            <input type="hidden" 
                   name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][term_id]" 
                   value="">
            
            <div class="cf7-field-grid two-cols">
                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Call Title', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="text" 
                           name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][title]" 
                           value="" 
                           class="cf7-field-input cf7-call-title-input"
                           placeholder="<?php _e('e.g., Spring Exhibition 2025', 'cf7-artist-submissions'); ?>">
                    <p class="cf7-field-help">
                        <?php _e('The display title for this open call.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-category"></span>
                        <?php _e('Call Type', 'cf7-artist-submissions'); ?>
                    </label>
                    <select name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][call_type]" 
                            class="cf7-field-input">
                        <option value=""><?php _e('Select call type...', 'cf7-artist-submissions'); ?></option>
                        <option value="visual_arts"><?php _e('Visual Arts', 'cf7-artist-submissions'); ?></option>
                        <option value="text_based"><?php _e('Text-based', 'cf7-artist-submissions'); ?></option>
                    </select>
                    <p class="cf7-field-help">
                        <?php _e('Determines available mediums and accepted file types for submissions.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
            </div>

            <div class="cf7-field-group">
                <label class="cf7-field-label">
                    <span class="dashicons dashicons-forms"></span>
                    <?php _e('Contact Form 7 Form', 'cf7-artist-submissions'); ?>
                </label>
                <select name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][form_id]" 
                        class="cf7-field-input">
                    <option value=""><?php _e('Select a form...', 'cf7-artist-submissions'); ?></option>
                    <?php foreach ($forms as $form_id => $form_title): ?>
                        <option value="<?php echo esc_attr($form_id); ?>">
                            #<?php echo esc_html($form_id); ?> - <?php echo esc_html($form_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="cf7-field-help">
                    <?php _e('The Contact Form 7 form to use for this open call.', 'cf7-artist-submissions'); ?>
                </p>
            </div>

            <div class="cf7-field-group">
                <label class="cf7-field-label">
                    <span class="dashicons dashicons-text-page"></span>
                    <?php _e('Description', 'cf7-artist-submissions'); ?>
                </label>
                <textarea name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][description]" 
                          class="cf7-field-input" 
                          rows="3"
                          placeholder="<?php _e('Brief description of this open call...', 'cf7-artist-submissions'); ?>"></textarea>
                <p class="cf7-field-help">
                    <?php _e('Optional description for internal reference.', 'cf7-artist-submissions'); ?>
                </p>
            </div>

            <div class="cf7-field-grid three-cols">
                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e('Start Date', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="date" 
                           name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][start_date]" 
                           value="" 
                           class="cf7-field-input">
                    <p class="cf7-field-help">
                        <?php _e('When this call opens (optional).', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e('End Date', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="date" 
                           name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][end_date]" 
                           value="" 
                           class="cf7-field-input">
                    <p class="cf7-field-help">
                        <?php _e('Submission deadline (optional).', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Status', 'cf7-artist-submissions'); ?>
                    </label>
                    <select name="cf7_artist_submissions_open_calls[calls][{{INDEX}}][status]" 
                            class="cf7-field-input">
                        <option value="active"><?php _e('Active', 'cf7-artist-submissions'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'cf7-artist-submissions'); ?></option>
                        <option value="draft"><?php _e('Draft', 'cf7-artist-submissions'); ?></option>
                    </select>
                    <p class="cf7-field-help">
                        <?php _e('Current status of this call.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript functionality is loaded via open-calls.js -->

<!-- CSS styles are loaded via settings.css -->
