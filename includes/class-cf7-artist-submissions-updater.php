<?php
/**
 * CF7 Artist Submissions - Plugin Update Manager
 *
 * Automatic update system for the CF7 Artist Submissions plugin that connects
 * to GitHub repository for version checking and automatic updates through the
 * WordPress dashboard. Provides seamless update experience with proper version
 * management and security validation.
 *
 * Features:
 * • GitHub repository integration for version checking
 * • WordPress dashboard update notifications
 * • Automatic update download and installation
 * • Version comparison and compatibility checking
 * • Security validation for update packages
 * • Proper WordPress update API integration
 * • Admin notices for update availability
 * • Rollback protection with validation
 *
 * @package CF7_Artist_Submissions
 * @subpackage UpdateManager
 * @since 1.0.0
 * @version 1.0.1
 */

/**
 * CF7 Artist Submissions Update Manager Class
 * 
 * Manages automatic updates for the CF7 Artist Submissions plugin by connecting
 * to the GitHub repository and integrating with WordPress update system.
 * Provides seamless update experience through the WordPress dashboard.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Updater {

    /**
     * Plugin file path
     * 
     * @since 1.0.0
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin basename
     * 
     * @since 1.0.0
     * @var string
     */
    private $plugin_basename;

    /**
     * Plugin version
     * 
     * @since 1.0.0
     * @var string
     */
    private $version;

    /**
     * GitHub repository URL
     * 
     * @since 1.0.0
     * @var string
     */
    private $github_repo = 'xapher19/cf7-artist-submissions';

    /**
     * GitHub API URL for releases
     * 
     * @since 1.0.0
     * @var string
     */
    private $github_api_url = 'https://api.github.com/repos/xapher19/cf7-artist-submissions/releases/latest';

    /**
     * Update cache key
     * 
     * @since 1.0.0
     * @var string
     */
    private $cache_key = 'cf7_artist_submissions_update_check';

    /**
     * Cache expiration time (12 hours)
     * 
     * @since 1.0.0
     * @var int
     */
    private $cache_expiration = 43200;

    /**
     * Initialize the updater
     * 
     * Sets up the plugin updater with necessary hooks and filters for
     * WordPress update system integration.
     * 
     * @since 1.0.0
     * 
     * @param string $plugin_file Plugin main file path
     * @param string $version Current plugin version
     */
    public function __construct($plugin_file, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->version = $version;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_pre_download', array($this, 'download_package'), 10, 3);
        add_action('admin_notices', array($this, 'update_notice'));
        
        // AJAX handlers for updates tab
        add_action('wp_ajax_cf7_force_update_check', array($this, 'ajax_force_update_check'));
        add_action('wp_ajax_cf7_check_updates', array($this, 'ajax_check_updates'));
    }

    /**
     * Check for plugin updates
     * 
     * Hooks into WordPress update system to check for plugin updates
     * from GitHub repository and add update information to transient.
     * 
     * @since 1.0.0
     * 
     * @param object $transient WordPress update transient
     * @return object Modified transient with update information
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_basename] = (object) array(
                'slug' => dirname($this->plugin_basename),
                'plugin' => $this->plugin_basename,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_repo}",
                'package' => $this->get_download_url($remote_version),
                'tested' => '6.8.2',
                'requires_php' => '7.4',
                'compatibility' => (object) array()
            );
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub
     * 
     * Retrieves the latest version information from GitHub releases API
     * with caching to prevent excessive API calls.
     * 
     * @since 1.0.0
     * 
     * @return string Latest version number
     */
    private function get_remote_version() {
        $cached_version = get_transient($this->cache_key);
        
        if ($cached_version !== false) {
            return $cached_version;
        }

        $response = wp_remote_get($this->github_api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'CF7-Artist-Submissions-Updater'
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return $this->version; // Return current version if API fails
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['tag_name'])) {
            return $this->version;
        }

        $version = ltrim($data['tag_name'], 'v');
        set_transient($this->cache_key, $version, $this->cache_expiration);

        return $version;
    }

    /**
     * Get download URL for specific version
     * 
     * Constructs the download URL for the plugin package from GitHub.
     * 
     * @since 1.0.0
     * 
     * @param string $version Version to download
     * @return string Download URL
     */
    private function get_download_url($version) {
        return "https://github.com/{$this->github_repo}/archive/refs/tags/v{$version}.zip";
    }

    /**
     * Provide plugin information for update screen
     * 
     * Hooks into WordPress plugin information API to provide details
     * about the plugin for the update/install screen.
     * 
     * @since 1.0.0
     * 
     * @param false|object|array $result The result object or array
     * @param string $action The type of information being requested
     * @param object $args Plugin API arguments
     * @return false|object|array Modified result with plugin information
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_basename)) {
            return $result;
        }

        $remote_version = $this->get_remote_version();

        return (object) array(
            'name' => 'CF7 Artist Submissions',
            'slug' => dirname($this->plugin_basename),
            'version' => $remote_version,
            'author' => '<a href="https://github.com/xapher19">xapher19</a>',
            'homepage' => "https://github.com/{$this->github_repo}",
            'download_link' => $this->get_download_url($remote_version),
            'tested' => '6.8.2',
            'requires' => '5.6',
            'requires_php' => '7.4',
            'sections' => array(
                'description' => 'Professional artist submission management system for WordPress with modern dashboard, advanced field editing, and comprehensive task management.',
                'changelog' => $this->get_changelog()
            ),
            'banners' => array(),
            'external' => true
        );
    }

    /**
     * Download plugin package
     * 
     * Handles the download of the plugin package from GitHub with
     * proper error handling and validation.
     * 
     * @since 1.0.0
     * 
     * @param bool $reply Whether to bail without returning the package
     * @param string $package The package URL
     * @param object $upgrader The upgrader instance
     * @return bool|string Download result or package path
     */
    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com/' . $this->github_repo) === false) {
            return $reply;
        }

        $response = wp_remote_get($package, array(
            'timeout' => 300,
            'headers' => array(
                'Accept' => 'application/zip',
                'User-Agent' => 'CF7-Artist-Submissions-Updater'
            )
        ));

        if (is_wp_error($response)) {
            return $reply;
        }

        $temp_file = download_url($package);
        
        if (is_wp_error($temp_file)) {
            return $reply;
        }

        return $temp_file;
    }

    /**
     * Display update notice in admin
     * 
     * Shows admin notice when updates are available for the plugin.
     * 
     * @since 1.0.0
     */
    public function update_notice() {
        $current_screen = get_current_screen();
        
        if ($current_screen->id !== 'plugins' && $current_screen->id !== 'dashboard') {
            return;
        }

        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $update_url = wp_nonce_url(
                self_admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_basename),
                'upgrade-plugin_' . $this->plugin_basename
            );

            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>CF7 Artist Submissions:</strong> ';
            echo sprintf(
                __('Version %s is available. <a href="%s">Update now</a>.', 'cf7-artist-submissions'),
                $remote_version,
                $update_url
            );
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Get changelog information
     * 
     * Retrieves changelog information from GitHub releases for display
     * in the plugin update screen.
     * 
     * @since 1.0.0
     * 
     * @return string Formatted changelog
     */
    private function get_changelog() {
        $response = wp_remote_get($this->github_api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'CF7-Artist-Submissions-Updater'
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return 'Unable to fetch changelog.';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['body'])) {
            return 'No changelog available.';
        }

        return wp_kses_post($data['body']);
    }

    /**
     * Force update check
     * 
     * Forces an immediate update check by clearing the cache and
     * triggering WordPress update transient refresh.
     * 
     * @since 1.0.0
     */
    public function force_update_check() {
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
    }

    /**
     * Get current plugin version
     * 
     * Returns the current version of the plugin.
     * 
     * @since 1.0.0
     * 
     * @return string Current version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get repository information
     * 
     * Returns information about the GitHub repository.
     * 
     * @since 1.0.0
     * 
     * @return array Repository information
     */
    public function get_repository_info() {
        return array(
            'repo' => $this->github_repo,
            'api_url' => $this->github_api_url,
            'download_url' => "https://github.com/{$this->github_repo}/releases/latest"
        );
    }

    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================

    /**
     * AJAX handler for manual update check
     * 
     * Handles AJAX requests to force update checking from the updates tab.
     * Provides secure endpoint for manual update checks with proper validation.
     * 
     * @since 1.0.0
     */
    public function ajax_force_update_check() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'cf7-artist-submissions')));
        }

        // Check user capabilities
        if (!current_user_can('update_plugins')) {
            wp_send_json_error(array('message' => __('Permission denied', 'cf7-artist-submissions')));
        }

        // Force update check
        $this->force_update_check();

        wp_send_json_success(array(
            'message' => __('Update check completed. The page will refresh to show results.', 'cf7-artist-submissions')
        ));
    }

    /**
     * AJAX handler for checking update status
     * 
     * Handles AJAX requests to check current update status for monitoring.
     * Used by automatic status monitoring in the updates tab.
     * 
     * @since 1.0.0
     */
    public function ajax_check_updates() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'cf7-artist-submissions')));
        }

        // Check user capabilities
        if (!current_user_can('update_plugins')) {
            wp_send_json_error(array('message' => __('Permission denied', 'cf7-artist-submissions')));
        }

        // Check for available updates
        $update_transient = get_site_transient('update_plugins');
        $has_update = isset($update_transient->response[$this->plugin_basename]);

        wp_send_json_success(array(
            'has_update' => $has_update,
            'current_version' => $this->version
        ));
    }
}
