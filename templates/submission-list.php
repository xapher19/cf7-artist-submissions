<?php
/**
 * Submission List Template for CF7 Artist Submissions
 * 
 * This template renders the submissions list page in WordPress admin,
 * providing an overview of all artist submissions with:
 * - Status filtering and categorization
 * - Bulk actions for submission management
 * - Search functionality across submission data
 * - Quick status updates and navigation
 * - Integration with WordPress list table structure
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with improved filtering and status management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="cf7-submissions-filter">
        <form method="get">
            <input type="hidden" name="post_type" value="cf7_submission">
            <?php
            $taxonomy_obj = get_taxonomy('submission_status');
            $taxonomy_args = array(
                'show_option_all' => __('All Statuses', 'cf7-artist-submissions'),
                'taxonomy' => 'submission_status',
                'name' => 'submission_status',
                'orderby' => 'name',
                'selected' => isset($_GET['submission_status']) ? sanitize_text_field($_GET['submission_status']) : '',
                'hierarchical' => true,
                'show_count' => true,
                'hide_empty' => false,
            );
            wp_dropdown_categories($taxonomy_args);
            
            submit_button(__('Filter', 'cf7-artist-submissions'), 'secondary', 'filter_action', false);
            ?>
        </form>
    </div>
    
    <div class="cf7-submissions-list">
        <?php
        // The list will be rendered by WordPress core
        ?>
    </div>
</div>