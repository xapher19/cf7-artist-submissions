<?php
/**
 * CF7 Artist Submissions - IMAP Settings Tab Template
 *
 * Email server configuration interface template providing comprehensive IMAP
 * connection settings for two-way email conversation tracking with plus
 * addressing support and automated inbox management capabilities.
 *
 * Features:
 * • IMAP server configuration with SSL/TLS encryption support
 * • Plus addressing email conversation tracking system
 * • Automated inbox cleanup and message processing
 * • Connection testing and validation tools
 * • Security-focused password management with app-specific support
 * • Professional form interface with real-time feedback
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.0.0
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$imap_options = get_option('cf7_artist_submissions_imap_options', array());
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-networking"></span>
            <?php _e('IMAP Configuration', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Configure IMAP settings to enable email conversation tracking and replies.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <form method="post" action="options.php" class="cf7-settings-form">
        <?php settings_fields('cf7_artist_submissions_imap_options'); ?>
        
        <div class="cf7-card-body">
            <div class="cf7-notice cf7-notice-info">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('IMAP Configuration', 'cf7-artist-submissions'); ?></strong>
                    <p><?php _e('Configure IMAP settings to enable two-way email conversations with artists. This uses plus addressing with your existing email address.', 'cf7-artist-submissions'); ?></p>
                    <p><?php _e('Uses your single email address with plus addressing (e.g., your-email+SUB123@domain.com). No extra email accounts or forwarding needed!', 'cf7-artist-submissions'); ?></p>
                </div>
            </div>
            
            <div class="cf7-field-grid two-cols">
                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-site"></span>
                        <?php _e('IMAP Server', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="text" 
                           name="cf7_artist_submissions_imap_options[server]" 
                           value="<?php echo esc_attr($imap_options['server'] ?? ''); ?>" 
                           class="cf7-field-input"
                           placeholder="imap.gmail.com">
                    <p class="cf7-field-help">
                        <?php _e('IMAP server hostname (e.g., imap.gmail.com, mail.yourdomain.com)', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-network"></span>
                        <?php _e('IMAP Port', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="number" 
                           name="cf7_artist_submissions_imap_options[port]" 
                           value="<?php echo esc_attr($imap_options['port'] ?? '993'); ?>" 
                           class="cf7-field-input"
                           min="1" max="65535"
                           placeholder="993">
                    <p class="cf7-field-help">
                        <?php _e('IMAP port (usually 993 for SSL/TLS, 143 for non-encrypted)', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Username', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="text" 
                           name="cf7_artist_submissions_imap_options[username]" 
                           value="<?php echo esc_attr($imap_options['username'] ?? ''); ?>" 
                           class="cf7-field-input"
                           placeholder="your@email.com">
                    <p class="cf7-field-help">
                        <?php _e('IMAP username (usually your email address)', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-lock"></span>
                        <?php _e('Password', 'cf7-artist-submissions'); ?>
                    </label>
                    <input type="password" 
                           name="cf7_artist_submissions_imap_options[password]" 
                           value="<?php echo esc_attr($imap_options['password'] ?? ''); ?>" 
                           class="cf7-field-input"
                           autocomplete="new-password">
                    <p class="cf7-field-help">
                        <?php _e('IMAP password (consider using app-specific passwords for Gmail)', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-privacy"></span>
                        <?php _e('Encryption', 'cf7-artist-submissions'); ?>
                    </label>
                    <select name="cf7_artist_submissions_imap_options[encryption]" class="cf7-field-input">
                        <option value="ssl" <?php selected($imap_options['encryption'] ?? 'ssl', 'ssl'); ?>>SSL/TLS</option>
                        <option value="tls" <?php selected($imap_options['encryption'] ?? 'ssl', 'tls'); ?>>STARTTLS</option>
                        <option value="none" <?php selected($imap_options['encryption'] ?? 'ssl', 'none'); ?>><?php _e('None (not recommended)', 'cf7-artist-submissions'); ?></option>
                    </select>
                    <p class="cf7-field-help">
                        <?php _e('Encryption method for IMAP connection', 'cf7-artist-submissions'); ?>
                    </p>
                </div>

                <div class="cf7-field-group">
                    <label class="cf7-field-label">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete Processed Emails', 'cf7-artist-submissions'); ?>
                    </label>
                    <div class="cf7-toggle">
                        <input type="hidden" name="cf7_artist_submissions_imap_options[delete_processed]" value="0">
                        <input type="checkbox" 
                               id="delete_processed"
                               name="cf7_artist_submissions_imap_options[delete_processed]" 
                               value="1" 
                               <?php checked($imap_options['delete_processed'] ?? 1, 1); ?>>
                        <span class="cf7-toggle-slider"></span>
                    </div>
                    <p class="cf7-field-help">
                        <?php _e('When enabled, emails are permanently deleted from the IMAP server after being imported into the database. This prevents duplicate processing when messages are cleared. Recommended for privacy and to avoid reprocessing emails.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="cf7-form-actions">
            <div class="cf7-form-actions-left">
                <button type="button" class="cf7-test-btn" data-action="test-imap">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Test IMAP Connection', 'cf7-artist-submissions'); ?>
                </button>
                <button type="button" class="cf7-test-btn" data-action="cleanup-inbox">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Clean Up Inbox', 'cf7-artist-submissions'); ?>
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
