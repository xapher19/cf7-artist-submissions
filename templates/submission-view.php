<?php
/**
 * Single Submission View Template for CF7 Artist Submissions
 * 
 * This template renders the detailed view of a single artist submission,
 * providing comprehensive information display and management tools:
 * - Submission header with title and current status
 * - Status management with real-time updates
 * - All submission metadata and field data
 * - File attachments and media display
 * - Administrative tools and actions
 * - Integration with tabbed dashboard interface
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with tabbed interface and improved status management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
?>
<div class="cf7-submission-container">
    <div class="cf7-submission-header">
        <h2><?php echo esc_html(get_the_title()); ?></h2>
        
        <div class="cf7-submission-status">
            <?php
            $terms = get_the_terms($post_id, 'submission_status');
            if (!empty($terms)) {
                $status = $terms[0]->name;
                echo '<span class="submission-status status-' . sanitize_html_class(strtolower($status)) . '">' . esc_html($status) . '</span>';
            } else {
                echo '<span class="submission-status status-new">New</span>';
            }
            ?>
            
            <div class="submission-actions">
                <label for="submission_status_change"><?php _e('Change Status:', 'cf7-artist-submissions'); ?></label>
                <select name="submission_status_change" id="submission_status_change" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <?php
                    $statuses = get_terms(array(
                        'taxonomy' => 'submission_status',
                        'hide_empty' => false,
                    ));
                    
                    foreach ($statuses as $status) {
                        $selected = (!empty($terms) && $terms[0]->term_id === $status->term_id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($status->term_id) . '" ' . $selected . '>' . esc_html($status->name) . '</option>';
                    }
                    ?>
                </select>
                <span class="spinner"></span>
            </div>
        </div>
    </div>
    
    <div class="cf7-submission-content">
        <!-- Content will be rendered by meta boxes -->
    </div>
</div>