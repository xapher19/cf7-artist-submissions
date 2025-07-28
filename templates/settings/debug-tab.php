<?php
/**
 * CF7 Artist Submissions - Debug Tab Template
 *
 * System diagnostics and troubleshooting interface template providing
 * comprehensive testing tools, configuration validation, and maintenance
 * utilities for email systems, database operations, and system health monitoring.
 *
 * Features:
 * • Email configuration testing and SMTP validation
 * • IMAP connection testing and inbox cleanup utilities
 * • Database schema updates and conversation token migration
 * • Daily summary system testing and cron management
 * • System information display with compatibility checks
 * • Real-time test result feedback and debugging tools
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
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('System Diagnostics', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Debug information and system health checks for troubleshooting.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <div class="cf7-card-body">
        <div class="cf7-field-grid two-cols">
            <!-- Email Testing -->
            <div class="cf7-debug-section">
                <h3><?php _e('Email Configuration Testing', 'cf7-artist-submissions'); ?></h3>
                <p><?php _e('Test your email configuration and SMTP settings.', 'cf7-artist-submissions'); ?></p>
                
                <div class="cf7-debug-actions">
                    <button type="button" class="cf7-test-btn" data-action="validate-email-config">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Validate Email Config', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" class="cf7-test-btn" data-action="test-smtp">
                        <span class="dashicons dashicons-email"></span>
                        <?php _e('Send Test Email', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </div>

            <!-- IMAP Testing -->
            <div class="cf7-debug-section">
                <h3><?php _e('IMAP Connection Testing', 'cf7-artist-submissions'); ?></h3>
                <p><?php _e('Test your IMAP connection and inbox management.', 'cf7-artist-submissions'); ?></p>
                
                <div class="cf7-debug-actions">
                    <button type="button" class="cf7-test-btn" data-action="test-imap">
                        <span class="dashicons dashicons-networking"></span>
                        <?php _e('Test IMAP Connection', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" class="cf7-test-btn" data-action="cleanup-inbox">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clean Up Inbox', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </div>

            <!-- Database Maintenance -->
            <div class="cf7-debug-section">
                <h3><?php _e('Database Maintenance', 'cf7-artist-submissions'); ?></h3>
                <p><?php _e('Update database schema and fix data integrity issues.', 'cf7-artist-submissions'); ?></p>
                
                <div class="cf7-debug-actions">
                    <button type="button" class="cf7-test-btn" data-action="update-schema">
                        <span class="dashicons dashicons-database"></span>
                        <?php _e('Update Database Schema', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" class="cf7-test-btn" data-action="migrate-tokens">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Migrate Conversation Tokens', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </div>

            <!-- Daily Summary Testing -->
            <div class="cf7-debug-section">
                <h3><?php _e('Daily Summary System', 'cf7-artist-submissions'); ?></h3>
                <p><?php _e('Test and manage the daily summary email system.', 'cf7-artist-submissions'); ?></p>
                
                <div class="cf7-debug-actions">
                    <button type="button" class="cf7-test-btn" data-action="test-daily-summary">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Send Test Summary', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" class="cf7-test-btn" data-action="setup-cron">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Setup Daily Cron', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
                
                <?php
                // Show current cron status
                $next_scheduled = wp_next_scheduled('cf7_daily_summary_cron');
                if ($next_scheduled) {
                    echo '<div class="cf7-notice cf7-notice-info">';
                    echo '<p><strong>' . __('Next scheduled email:', 'cf7-artist-submissions') . '</strong> ';
                    echo date(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled);
                    echo '</p></div>';
                } else {
                    echo '<div class="cf7-notice cf7-notice-warning">';
                    echo '<p>' . __('Daily summary cron is not scheduled.', 'cf7-artist-submissions') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- System Information -->
        <div class="cf7-debug-section" style="margin-top: 2rem;">
            <h3><?php _e('System Information', 'cf7-artist-submissions'); ?></h3>
            
            <div class="cf7-system-info">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Component', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Status', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Details', 'cf7-artist-submissions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('WordPress Version', 'cf7-artist-submissions'); ?></td>
                            <td>
                                <?php 
                                global $wp_version;
                                echo esc_html($wp_version);
                                ?>
                            </td>
                            <td>
                                <?php if (version_compare($wp_version, '5.0', '>=')): ?>
                                    <span class="cf7-status-ok">✓ <?php _e('Compatible', 'cf7-artist-submissions'); ?></span>
                                <?php else: ?>
                                    <span class="cf7-status-warning">⚠ <?php _e('May have issues', 'cf7-artist-submissions'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('Contact Form 7', 'cf7-artist-submissions'); ?></td>
                            <td>
                                <?php 
                                if (class_exists('WPCF7_ContactForm')) {
                                    echo '<span class="cf7-status-ok">✓ ' . __('Active', 'cf7-artist-submissions') . '</span>';
                                } else {
                                    echo '<span class="cf7-status-error">✗ ' . __('Not Found', 'cf7-artist-submissions') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (defined('WPCF7_VERSION')) {
                                    echo __('Version:', 'cf7-artist-submissions') . ' ' . esc_html(WPCF7_VERSION);
                                } else {
                                    echo __('Install Contact Form 7 plugin', 'cf7-artist-submissions');
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('WooCommerce', 'cf7-artist-submissions'); ?></td>
                            <td>
                                <?php 
                                if (class_exists('WooCommerce')) {
                                    echo '<span class="cf7-status-ok">✓ ' . __('Active', 'cf7-artist-submissions') . '</span>';
                                } else {
                                    echo '<span class="cf7-status-info">- ' . __('Optional', 'cf7-artist-submissions') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (class_exists('WooCommerce')) {
                                    global $woocommerce;
                                    echo __('Version:', 'cf7-artist-submissions') . ' ' . esc_html($woocommerce->version);
                                } else {
                                    echo __('For enhanced email templates', 'cf7-artist-submissions');
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('PHP Version', 'cf7-artist-submissions'); ?></td>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                            <td>
                                <?php if (version_compare(PHP_VERSION, '7.4', '>=')): ?>
                                    <span class="cf7-status-ok">✓ <?php _e('Compatible', 'cf7-artist-submissions'); ?></span>
                                <?php else: ?>
                                    <span class="cf7-status-warning">⚠ <?php _e('Upgrade recommended', 'cf7-artist-submissions'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e('IMAP Extension', 'cf7-artist-submissions'); ?></td>
                            <td>
                                <?php 
                                if (extension_loaded('imap')) {
                                    echo '<span class="cf7-status-ok">✓ ' . __('Available', 'cf7-artist-submissions') . '</span>';
                                } else {
                                    echo '<span class="cf7-status-error">✗ ' . __('Missing', 'cf7-artist-submissions') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (extension_loaded('imap')) {
                                    echo __('Required for email conversations', 'cf7-artist-submissions');
                                } else {
                                    echo __('Install PHP IMAP extension', 'cf7-artist-submissions');
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
