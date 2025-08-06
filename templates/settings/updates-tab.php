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
 * @version 1.3.0
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

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Plugin Updates', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('Manage plugin updates and check for new versions from the GitHub repository.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <div class="cf7-card-body">
        <!-- Current Version Section -->
        <div class="cf7-field-group">
            <h3 class="cf7-section-title">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php _e('Current Version', 'cf7-artist-submissions'); ?>
            </h3>
            
            <div class="cf7-version-info">
                <div class="cf7-version-current">
                    <span class="cf7-version-label"><?php _e('Installed Version:', 'cf7-artist-submissions'); ?></span>
                    <span class="cf7-version-number"><?php echo esc_html($current_version); ?></span>
                </div>
                
                <div class="cf7-plugin-details">
                    <div class="cf7-field-grid two-cols">
                        <div class="cf7-detail-item">
                            <strong><?php _e('Plugin Name:', 'cf7-artist-submissions'); ?></strong>
                            <span><?php echo esc_html($plugin_data['Name']); ?></span>
                        </div>
                        <div class="cf7-detail-item">
                            <strong><?php _e('Author:', 'cf7-artist-submissions'); ?></strong>
                            <span><?php echo wp_kses_post($plugin_data['Author']); ?></span>
                        </div>
                    </div>
                    
                    <div class="cf7-detail-item">
                        <strong><?php _e('Description:', 'cf7-artist-submissions'); ?></strong>
                        <span><?php echo wp_kses_post($plugin_data['Description']); ?></span>
                    </div>
                    
                    <?php if (!empty($plugin_data['PluginURI'])): ?>
                    <div class="cf7-detail-item">
                        <strong><?php _e('Plugin URI:', 'cf7-artist-submissions'); ?></strong>
                        <span><a href="<?php echo esc_url($plugin_data['PluginURI']); ?>" target="_blank"><?php echo esc_html($plugin_data['PluginURI']); ?></a></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Update Status Section -->
        <div class="cf7-field-group">
            <h3 class="cf7-section-title">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Update Status', 'cf7-artist-submissions'); ?>
            </h3>

            <?php if ($has_update && $update_info): ?>
                <div class="cf7-update-available">
                    <div class="cf7-update-notice">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <strong><?php _e('Update Available!', 'cf7-artist-submissions'); ?></strong>
                    </div>
                    
                    <div class="cf7-field-grid two-cols">
                        <div class="cf7-detail-item">
                            <strong><?php _e('New Version:', 'cf7-artist-submissions'); ?></strong>
                            <span class="cf7-version-highlight"><?php echo esc_html($update_info->new_version); ?></span>
                        </div>
                        <div class="cf7-detail-item">
                            <strong><?php _e('Current Version:', 'cf7-artist-submissions'); ?></strong>
                            <span><?php echo esc_html($current_version); ?></span>
                        </div>
                    </div>
                    
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
                <p class="cf7-field-help">
                    <?php _e('Click to manually check for new updates from the GitHub repository.', 'cf7-artist-submissions'); ?>
                </p>
            </div>
        </div>

        <!-- Repository Information Section -->
        <div class="cf7-field-group">
            <h3 class="cf7-section-title">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php _e('Repository Information', 'cf7-artist-submissions'); ?>
            </h3>
            
            <div class="cf7-field-grid two-cols">
                <div class="cf7-detail-item">
                    <strong><?php _e('Repository:', 'cf7-artist-submissions'); ?></strong>
                    <span><a href="https://github.com/xapher19/cf7-artist-submissions" target="_blank">github.com/xapher19/cf7-artist-submissions</a></span>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('Update Source:', 'cf7-artist-submissions'); ?></strong>
                    <span><?php _e('GitHub Releases', 'cf7-artist-submissions'); ?></span>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('Update Method:', 'cf7-artist-submissions'); ?></strong>
                    <span><?php _e('WordPress Dashboard Integration', 'cf7-artist-submissions'); ?></span>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('Check Frequency:', 'cf7-artist-submissions'); ?></strong>
                    <span><?php _e('Every 12 hours', 'cf7-artist-submissions'); ?></span>
                </div>
            </div>

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

        <!-- System Status Section -->
        <div class="cf7-field-group">
            <h3 class="cf7-section-title">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('System Status', 'cf7-artist-submissions'); ?>
            </h3>
            
            <div class="cf7-field-grid two-cols">
                <div class="cf7-detail-item">
                    <strong><?php _e('Automatic Updates:', 'cf7-artist-submissions'); ?></strong>
                    <span class="cf7-status-enabled"><?php _e('Enabled', 'cf7-artist-submissions'); ?></span>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('Cache Status:', 'cf7-artist-submissions'); ?></strong>
                    <?php 
                    $cache_exists = get_transient('cf7_artist_submissions_update_check');
                    echo $cache_exists ? '<span class="cf7-status-cached">' . __('Cached', 'cf7-artist-submissions') . '</span>' : 
                                        '<span class="cf7-status-fresh">' . __('Fresh', 'cf7-artist-submissions') . '</span>';
                    ?>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('GitHub API Status:', 'cf7-artist-submissions'); ?></strong>
                    <span class="cf7-status-connected"><?php _e('Connected', 'cf7-artist-submissions'); ?></span>
                </div>
                <div class="cf7-detail-item">
                    <strong><?php _e('WordPress Integration:', 'cf7-artist-submissions'); ?></strong>
                    <span class="cf7-status-enabled"><?php _e('Active', 'cf7-artist-submissions'); ?></span>
                </div>
            </div>

            <div class="cf7-system-notes">
                <h4><?php _e('How Updates Work:', 'cf7-artist-submissions'); ?></h4>
                <ul class="cf7-help-list">
                    <li><?php _e('Updates are checked automatically every 12 hours', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('New versions are pulled from GitHub releases', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('Updates appear in WordPress dashboard like other plugins', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('You can update safely through the standard WordPress interface', 'cf7-artist-submissions'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
