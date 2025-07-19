<?php
/**
 * CF7 Artist Submissions Recovery Script
 * 
 * If your WordPress site crashes, upload this file to your 
 * /wp-content/plugins/cf7-artist-submissions/ directory
 * and access it directly via browser to deactivate the plugin.
 */

// Basic security check
if (!isset($_GET['recovery_key']) || $_GET['recovery_key'] !== 'xapher19_2025') {
    die('Unauthorized access');
}

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Check user permissions
if (!function_exists('current_user_can') || !current_user_can('activate_plugins')) {
    die('You do not have permission to perform this action');
}

// Deactivate the plugin
if (!function_exists('deactivate_plugins')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

deactivate_plugins('cf7-artist-submissions/cf7-artist-submissions.php');

echo '<h1>CF7 Artist Submissions has been deactivated</h1>';
echo '<p>The plugin has been safely deactivated. You can now access your WordPress admin again.</p>';
echo '<p><a href="' . admin_url() . '">Go to WordPress Admin</a></p>';