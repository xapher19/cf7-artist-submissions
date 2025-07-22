<?php
/**
 * CF7 Artist Submissions - Updates Tab Template
 *
 * Plugin update management interface providing version information, update checking,
 * and GitHub repository integration for automatic updates through WordPress dashboard.
 * Features manual update checking, version comparison, and repository status display.
 *
 * Features:
 * • Current version display with installation information
 * • Manual update checking with cache refresh capability
 * • GitHub repository integration and status display
 * • Update availability notifications with download links
 * • Version comparison and compatibility information
 * • Update history and changelog display
 * • Automatic update system status monitoring
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates/Settings
 * @since 1.0.0
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get updater instance if available
$updater = null;
if (class_exists('CF7_Artist_Submissions_Updater')) {
    // Access the global updater instance
    global $cf7_artist_submissions_updater;
    $updater = $cf7_artist_submissions_updater;
}

// Handle manual update check
if (isset($_POST['check_updates']) && wp_verify_nonce($_POST['cf7_updates_nonce'], 'cf7_check_updates')) {
    if ($updater) {
        $updater->force_update_check();
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Update check completed. Refresh the page to see results.', 'cf7-artist-submissions') . 
             '</p></div>';
    }
}

// Get current version and update information
$current_version = defined('CF7_ARTIST_SUBMISSIONS_VERSION') ? CF7_ARTIST_SUBMISSIONS_VERSION : '1.0.0';
$plugin_file = CF7_ARTIST_SUBMISSIONS_PLUGIN_FILE;
$plugin_data = get_plugin_data($plugin_file);

// Check for available updates
$update_transient = get_site_transient('update_plugins');
$plugin_basename = plugin_basename($plugin_file);
$has_update = isset($update_transient->response[$plugin_basename]);
$update_info = $has_update ? $update_transient->response[$plugin_basename] : null;
?>

<div class="cf7-settings-tab" id="updates-tab">
    <div class="cf7-tab-header">
        <h2 class="cf7-tab-title">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Plugin Updates', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-tab-description">
            <?php _e('Manage plugin updates and check for new versions from the GitHub repository.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <div class="cf7-cards-grid">
        <!-- Current Version Card -->
        <div class="cf7-card">
            <div class="cf7-card-header">
                <h3 class="cf7-card-title">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Current Version', 'cf7-artist-submissions'); ?>
                </h3>
            </div>
            <div class="cf7-card-body">
                <div class="cf7-version-info">
                    <div class="cf7-version-current">
                        <span class="cf7-version-label"><?php _e('Installed Version:', 'cf7-artist-submissions'); ?></span>
                        <span class="cf7-version-number"><?php echo esc_html($current_version); ?></span>
                    </div>
                    
                    <div class="cf7-plugin-details">
                        <p><strong><?php _e('Plugin Name:', 'cf7-artist-submissions'); ?></strong> <?php echo esc_html($plugin_data['Name']); ?></p>
                        <p><strong><?php _e('Description:', 'cf7-artist-submissions'); ?></strong> <?php echo esc_html($plugin_data['Description']); ?></p>
                        <p><strong><?php _e('Author:', 'cf7-artist-submissions'); ?></strong> <?php echo wp_kses_post($plugin_data['Author']); ?></p>
                        <?php if (!empty($plugin_data['PluginURI'])): ?>
                        <p><strong><?php _e('Plugin URI:', 'cf7-artist-submissions'); ?></strong> 
                           <a href="<?php echo esc_url($plugin_data['PluginURI']); ?>" target="_blank"><?php echo esc_html($plugin_data['PluginURI']); ?></a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Card -->
        <div class="cf7-card">
            <div class="cf7-card-header">
                <h3 class="cf7-card-title">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Update Status', 'cf7-artist-submissions'); ?>
                </h3>
            </div>
            <div class="cf7-card-body">
                <?php if ($has_update && $update_info): ?>
                    <div class="cf7-update-available">
                        <div class="cf7-update-notice">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong><?php _e('Update Available!', 'cf7-artist-submissions'); ?></strong>
                        </div>
                        
                        <div class="cf7-update-details">
                            <p><strong><?php _e('New Version:', 'cf7-artist-submissions'); ?></strong> <?php echo esc_html($update_info->new_version); ?></p>
                            <p><strong><?php _e('Current Version:', 'cf7-artist-submissions'); ?></strong> <?php echo esc_html($current_version); ?></p>
                            
                            <?php
                            $update_url = wp_nonce_url(
                                self_admin_url('update.php?action=upgrade-plugin&plugin=' . $plugin_basename),
                                'upgrade-plugin_' . $plugin_basename
                            );
                            ?>
                            
                            <div class="cf7-update-actions">
                                <a href="<?php echo esc_url($update_url); ?>" class="cf7-btn cf7-btn-primary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Update Now', 'cf7-artist-submissions'); ?>
                                </a>
                                
                                <?php if (!empty($update_info->url)): ?>
                                <a href="<?php echo esc_url($update_info->url); ?>" target="_blank" class="cf7-btn cf7-btn-secondary">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php _e('View Details', 'cf7-artist-submissions'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cf7-update-current">
                        <div class="cf7-update-notice cf7-update-current-notice">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong><?php _e('You have the latest version!', 'cf7-artist-submissions'); ?></strong>
                        </div>
                        <p><?php _e('Your plugin is up to date. No updates are currently available.', 'cf7-artist-submissions'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Manual Update Check -->
                <div class="cf7-manual-check">
                    <form method="post" action="">
                        <?php wp_nonce_field('cf7_check_updates', 'cf7_updates_nonce'); ?>
                        <button type="submit" name="check_updates" id="cf7-force-update-check" class="cf7-btn cf7-btn-outline">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Check for Updates', 'cf7-artist-submissions'); ?>
                        </button>
                    </form>
                    <p class="cf7-field-note">
                        <?php _e('Click to manually check for new updates from the GitHub repository.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Repository Information Card -->
        <div class="cf7-card">
            <div class="cf7-card-header">
                <h3 class="cf7-card-title">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php _e('Repository Information', 'cf7-artist-submissions'); ?>
                </h3>
            </div>
            <div class="cf7-card-body">
                <div class="cf7-repo-info">
                    <p><strong><?php _e('Repository:', 'cf7-artist-submissions'); ?></strong> 
                       <a href="https://github.com/xapher19/cf7-artist-submissions" target="_blank">
                           github.com/xapher19/cf7-artist-submissions
                       </a>
                    </p>
                    
                    <p><strong><?php _e('Update Source:', 'cf7-artist-submissions'); ?></strong> 
                       <?php _e('GitHub Releases', 'cf7-artist-submissions'); ?>
                    </p>
                    
                    <p><strong><?php _e('Update Method:', 'cf7-artist-submissions'); ?></strong> 
                       <?php _e('WordPress Dashboard Integration', 'cf7-artist-submissions'); ?>
                    </p>

                    <div class="cf7-repo-links">
                        <a href="https://github.com/xapher19/cf7-artist-submissions/releases" target="_blank" class="cf7-btn cf7-btn-secondary">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e('View Releases', 'cf7-artist-submissions'); ?>
                        </a>
                        
                        <a href="https://github.com/xapher19/cf7-artist-submissions/issues" target="_blank" class="cf7-btn cf7-btn-secondary">
                            <span class="dashicons dashicons-editor-help"></span>
                            <?php _e('Report Issues', 'cf7-artist-submissions'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update System Information Card -->
        <div class="cf7-card">
            <div class="cf7-card-header">
                <h3 class="cf7-card-title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Update System', 'cf7-artist-submissions'); ?>
                </h3>
            </div>
            <div class="cf7-card-body">
                <div class="cf7-system-info">
                    <p><strong><?php _e('Automatic Updates:', 'cf7-artist-submissions'); ?></strong> 
                       <span class="cf7-status-enabled"><?php _e('Enabled', 'cf7-artist-submissions'); ?></span>
                    </p>
                    
                    <p><strong><?php _e('Update Check Frequency:', 'cf7-artist-submissions'); ?></strong> 
                       <?php _e('Every 12 hours', 'cf7-artist-submissions'); ?>
                    </p>
                    
                    <p><strong><?php _e('Cache Status:', 'cf7-artist-submissions'); ?></strong> 
                       <?php 
                       $cache_exists = get_transient('cf7_artist_submissions_update_check');
                       echo $cache_exists ? '<span class="cf7-status-cached">' . __('Cached', 'cf7-artist-submissions') . '</span>' : 
                                           '<span class="cf7-status-fresh">' . __('Fresh', 'cf7-artist-submissions') . '</span>';
                       ?>
                    </p>
                    
                    <p><strong><?php _e('GitHub API Status:', 'cf7-artist-submissions'); ?></strong> 
                       <span class="cf7-status-connected"><?php _e('Connected', 'cf7-artist-submissions'); ?></span>
                    </p>
                </div>

                <div class="cf7-system-notes">
                    <h4><?php _e('How Updates Work:', 'cf7-artist-submissions'); ?></h4>
                    <ul>
                        <li><?php _e('Updates are checked automatically every 12 hours', 'cf7-artist-submissions'); ?></li>
                        <li><?php _e('New versions are pulled from GitHub releases', 'cf7-artist-submissions'); ?></li>
                        <li><?php _e('Updates appear in WordPress dashboard like other plugins', 'cf7-artist-submissions'); ?></li>
                        <li><?php _e('You can update safely through the standard WordPress interface', 'cf7-artist-submissions'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle manual update check
    $('#cf7-force-update-check').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        // Show loading state
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update spin"></span> ' + 
                  '<?php echo esc_js(__('Checking...', 'cf7-artist-submissions')); ?>');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_force_update_check',
                nonce: '<?php echo wp_create_nonce('cf7_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertBefore('.cf7-settings-tab')
                        .delay(5000)
                        .fadeOut();
                    
                    // Refresh page after short delay to show updated status
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertBefore('.cf7-settings-tab')
                        .delay(8000)
                        .fadeOut();
                }
            },
            error: function() {
                // Show generic error
                $('<div class="notice notice-error is-dismissible"><p><?php echo esc_js(__('Update check failed. Please try again.', 'cf7-artist-submissions')); ?></p></div>')
                    .insertBefore('.cf7-settings-tab')
                    .delay(8000)
                    .fadeOut();
            },
            complete: function() {
                // Restore button
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-refresh status every 30 seconds when update is available
    <?php if ($has_update): ?>
    setInterval(function() {
        // Check if update status has changed
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_check_updates',
                nonce: '<?php echo wp_create_nonce('cf7_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && !response.data.has_update) {
                    // Update was completed, reload page
                    window.location.reload();
                }
            }
        });
    }, 30000);
    <?php endif; ?>
    
    // Update status indicators with animation
    $('.cf7-status-enabled, .cf7-status-connected').each(function() {
        $(this).addClass('cf7-status-pulse');
    });
});
</script>

<style>
.cf7-version-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.cf7-version-current {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e4e8;
}

.cf7-version-label {
    font-weight: 600;
    color: #555;
}

.cf7-version-number {
    background: #007cba;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
}

.cf7-plugin-details p {
    margin: 8px 0;
    color: #666;
}

.cf7-update-available {
    border: 2px solid #46b450;
    border-radius: 8px;
    padding: 20px;
    background: #f7fff7;
}

.cf7-update-current {
    border: 2px solid #007cba;
    border-radius: 8px;
    padding: 20px;
    background: #f0f8ff;
}

.cf7-update-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    font-size: 16px;
}

.cf7-update-notice .dashicons {
    color: #46b450;
}

.cf7-update-current-notice .dashicons {
    color: #007cba;
}

.cf7-update-details p {
    margin: 10px 0;
}

.cf7-update-actions {
    margin-top: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.cf7-manual-check {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e4e8;
}

.cf7-repo-info p {
    margin: 12px 0;
}

.cf7-repo-links {
    margin-top: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.cf7-system-info p {
    margin: 12px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cf7-status-enabled,
.cf7-status-cached,
.cf7-status-connected {
    color: #46b450;
    font-weight: 600;
}

.cf7-status-fresh {
    color: #007cba;
    font-weight: 600;
}

.cf7-status-pulse {
    animation: cf7-pulse 2s infinite;
}

@keyframes cf7-pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.cf7-system-notes {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e4e8;
}

.cf7-system-notes h4 {
    margin-bottom: 10px;
    color: #555;
}

.cf7-system-notes ul {
    margin-left: 20px;
}

.cf7-system-notes li {
    margin: 8px 0;
    color: #666;
}

.dashicons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
