<?php
/**
 * Admin Settings Template for CF7 Artist Submissions
 * 
 * This template renders the main plugin settings page in the WordPress admin.
 * It provides configuration options for:
 * - Email settings and SMTP configuration
 * - IMAP settings for conversation management
 * - Default assignees and workflow settings
 * - Notification preferences
 * - Export and import options
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with comprehensive settings sections
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('cf7_artist_submissions_options');
        do_settings_sections('cf7-artist-submissions');
        submit_button();
        ?>
    </form>
</div>