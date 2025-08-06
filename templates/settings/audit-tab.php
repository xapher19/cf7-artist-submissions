<?php
/**
 * CF7 Artist Submissions - Audit Log Tab Template
 *
 * Administrative interface template for audit trail management providing
 * comprehensive system activity logging with advanced filtering, pagination,
 * and detailed action tracking for submission workflow oversight.
 *
 * Features:
 * • Advanced filtering by action type, submission ID, and date range
 * • Comprehensive audit trail with user attribution and timestamps
 * • Interactive table display with submission and artist information
 * • Pagination support for large audit log datasets
 * • Missing artist information update functionality
 * • Real-time AJAX operations for data management
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.0.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$action_type = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

// Build query
$table_name = $wpdb->prefix . 'cf7_action_log';
$where_conditions = array('1=1');
$where_values = array();

if (!empty($action_type)) {
    $where_conditions[] = 'action_type = %s';
    $where_values[] = $action_type;
}

if ($submission_id > 0) {
    $where_conditions[] = 'submission_id = %d';
    $where_values[] = $submission_id;
}

if (!empty($date_from)) {
    $where_conditions[] = 'DATE(date_created) >= %s';
    $where_values[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = 'DATE(date_created) <= %s';
    $where_values[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
if (!empty($where_values)) {
    $count_query = $wpdb->prepare($count_query, $where_values);
}
$total_items = $wpdb->get_var($count_query);

// Get logs with pagination
$query = "SELECT al.*, p.post_title, u.display_name 
          FROM {$table_name} al 
          LEFT JOIN {$wpdb->posts} p ON al.submission_id = p.ID 
          LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
          WHERE {$where_clause} 
          ORDER BY al.date_created DESC 
          LIMIT %d OFFSET %d";

$query_values = array_merge($where_values, array($per_page, $offset));
$logs = $wpdb->get_results($wpdb->prepare($query, $query_values));

// Calculate pagination
$total_pages = ceil($total_items / $per_page);
?>

<div class="cf7-settings-card">
    <div class="cf7-card-header">
        <h2 class="cf7-card-title">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Audit Trail', 'cf7-artist-submissions'); ?>
        </h2>
        <p class="cf7-card-description">
            <?php _e('View and manage the audit log of all system activities and changes.', 'cf7-artist-submissions'); ?>
        </p>
    </div>

    <div class="cf7-card-body">
        <div class="cf7-audit-log-container">
            <!-- Filters -->
            <div class="cf7-audit-filters">
                <form method="get" class="cf7-filters-form">
                    <input type="hidden" name="post_type" value="cf7_submission">
                    <input type="hidden" name="page" value="cf7-artist-submissions-settings">
                    <input type="hidden" name="tab" value="audit">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="action_type"><?php _e('Action Type:', 'cf7-artist-submissions'); ?></label>
                            <select name="action_type" id="action_type">
                                <option value=""><?php _e('All Actions', 'cf7-artist-submissions'); ?></option>
                                <option value="email_sent" <?php selected($action_type, 'email_sent'); ?>><?php _e('Email Sent', 'cf7-artist-submissions'); ?></option>
                                <option value="status_change" <?php selected($action_type, 'status_change'); ?>><?php _e('Status Change', 'cf7-artist-submissions'); ?></option>
                                <option value="form_submission" <?php selected($action_type, 'form_submission'); ?>><?php _e('Form Submission', 'cf7-artist-submissions'); ?></option>
                                <option value="file_upload" <?php selected($action_type, 'file_upload'); ?>><?php _e('File Upload', 'cf7-artist-submissions'); ?></option>
                                <option value="action_created" <?php selected($action_type, 'action_created'); ?>><?php _e('Action Created', 'cf7-artist-submissions'); ?></option>
                                <option value="action_completed" <?php selected($action_type, 'action_completed'); ?>><?php _e('Action Completed', 'cf7-artist-submissions'); ?></option>
                                <option value="conversation_cleared" <?php selected($action_type, 'conversation_cleared'); ?>><?php _e('Conversation Cleared', 'cf7-artist-submissions'); ?></option>
                                <option value="setting_changed" <?php selected($action_type, 'setting_changed'); ?>><?php _e('Setting Changed', 'cf7-artist-submissions'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="submission_id"><?php _e('Submission ID:', 'cf7-artist-submissions'); ?></label>
                            <input type="number" name="submission_id" id="submission_id" value="<?php echo esc_attr($submission_id); ?>" min="0">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from"><?php _e('From Date:', 'cf7-artist-submissions'); ?></label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to"><?php _e('To Date:', 'cf7-artist-submissions'); ?></label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="submit" class="filter-btn filter-btn-primary">
                                <span class="dashicons dashicons-filter"></span>
                                <?php _e('Filter', 'cf7-artist-submissions'); ?>
                            </button>
                            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=audit" class="filter-btn filter-btn-secondary">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php _e('Clear', 'cf7-artist-submissions'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Results Summary -->
            <div class="cf7-audit-summary">
                <p><?php printf(__('Showing %d of %d audit log entries', 'cf7-artist-submissions'), count($logs), $total_items); ?></p>
                
                <!-- Update Missing Artist Info Button -->
                <div class="cf7-audit-tools">
                    <button type="button" class="cf7-btn cf7-btn-secondary" id="update-missing-artist-info" data-action="update_missing_artist_info">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Update Missing Artist Info', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Audit Log Table -->
            <div class="cf7-audit-table-container">
                <?php if (empty($logs)): ?>
                    <div class="cf7-notice cf7-notice-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <p><?php _e('No audit log entries found matching your criteria.', 'cf7-artist-submissions'); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 140px;"><?php _e('Date & Time', 'cf7-artist-submissions'); ?></th>
                                <th style="width: 100px;"><?php _e('Action Type', 'cf7-artist-submissions'); ?></th>
                                <th style="width: 200px;"><?php _e('Submission & Artist', 'cf7-artist-submissions'); ?></th>
                                <th style="width: 120px;"><?php _e('User', 'cf7-artist-submissions'); ?></th>
                                <th><?php _e('Details', 'cf7-artist-submissions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log->date_created))); ?></strong><br>
                                        <small><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log->date_created))); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $type_labels = array(
                                            'email_sent' => __('Email Sent', 'cf7-artist-submissions'),
                                            'status_change' => __('Status Change', 'cf7-artist-submissions'),
                                            'form_submission' => __('Form Submission', 'cf7-artist-submissions'),
                                            'file_upload' => __('File Upload', 'cf7-artist-submissions'),
                                            'action_created' => __('Action Created', 'cf7-artist-submissions'),
                                            'action_completed' => __('Action Completed', 'cf7-artist-submissions'),
                                            'conversation_cleared' => __('Conversation Cleared', 'cf7-artist-submissions'),
                                            'setting_changed' => __('Setting Changed', 'cf7-artist-submissions')
                                        );
                                        $type_class = sanitize_html_class($log->action_type);
                                        echo '<span class="audit-type audit-type-' . $type_class . '">';
                                        echo esc_html($type_labels[$log->action_type] ?? ucwords(str_replace('_', ' ', $log->action_type)));
                                        echo '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <!-- Combined Submission & Artist Info -->
                                        <div class="submission-artist-info">
                                            <?php if ($log->submission_id > 0): ?>
                                                <!-- Submission Info -->
                                                <div class="submission-info">
                                                    <?php if ($log->post_title): ?>
                                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $log->submission_id . '&action=edit')); ?>" class="submission-link">
                                                            <strong>#<?php echo esc_html($log->submission_id); ?></strong>
                                                        </a>
                                                    <?php else: ?>
                                                        <strong>#<?php echo esc_html($log->submission_id); ?></strong>
                                                        <span class="submission-deleted"><?php _e('(Deleted)', 'cf7-artist-submissions'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Artist Info -->
                                                <?php if (!empty($log->artist_name) || !empty($log->artist_email)): ?>
                                                    <div class="artist-info">
                                                        <?php if (!empty($log->artist_name)): ?>
                                                            <span class="artist-name"><?php echo esc_html($log->artist_name); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($log->artist_email)): ?>
                                                            <span class="artist-email"><?php echo esc_html($log->artist_email); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($log->post_title): ?>
                                                    <!-- Fallback: Show post title if no artist info available -->
                                                    <div class="artist-info">
                                                        <span class="artist-name"><?php echo esc_html(wp_trim_words($log->post_title, 4)); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- System Action -->
                                                <div class="system-action-info">
                                                    <span class="system-action"><?php _e('System Action', 'cf7-artist-submissions'); ?></span>
                                                    <?php if (!empty($log->artist_name) || !empty($log->artist_email)): ?>
                                                        <div class="artist-info">
                                                            <?php if (!empty($log->artist_name)): ?>
                                                                <span class="artist-name"><?php echo esc_html($log->artist_name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($log->artist_email)): ?>
                                                                <span class="artist-email"><?php echo esc_html($log->artist_email); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log->display_name): ?>
                                            <?php echo esc_html($log->display_name); ?>
                                        <?php else: ?>
                                            <?php _e('System', 'cf7-artist-submissions'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $data = json_decode($log->data, true);
                                        if (is_array($data)) {
                                            echo '<div class="audit-details">';
                                            foreach ($data as $key => $value) {
                                                if (is_string($value) || is_numeric($value)) {
                                                    echo '<div><strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ':</strong> ';
                                                    echo esc_html(wp_trim_words($value, 10));
                                                    echo '</div>';
                                                }
                                            }
                                            echo '</div>';
                                        } else {
                                            echo esc_html($log->data);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="cf7-audit-pagination">
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(__('%d items', 'cf7-artist-submissions'), $total_items); ?></span>
                            
                            <?php if ($page_num > 1): ?>
                                <a class="cf7-btn cf7-btn-secondary" href="<?php echo esc_url(add_query_arg('log_page', $page_num - 1)); ?>"><?php _e('Previous', 'cf7-artist-submissions'); ?></a>
                            <?php endif; ?>
                            
                            <span class="current-page"><?php printf(__('Page %d of %d', 'cf7-artist-submissions'), $page_num, $total_pages); ?></span>
                            
                            <?php if ($page_num < $total_pages): ?>
                                <a class="cf7-btn cf7-btn-secondary" href="<?php echo esc_url(add_query_arg('log_page', $page_num + 1)); ?>"><?php _e('Next', 'cf7-artist-submissions'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update Missing Artist Info functionality
    $('#update-missing-artist-info').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const originalText = $btn.html();
        
        if (!confirm('<?php _e('This will update all audit log entries with missing artist information. Continue?', 'cf7-artist-submissions'); ?>')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e('Updating...', 'cf7-artist-submissions'); ?>');
        
        $.ajax({
            url: cf7_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_missing_artist_info',
                nonce: cf7_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="cf7-notice cf7-notice-success"><span class="dashicons dashicons-yes"></span><div><p>' + response.data.message + '</p></div></div>')
                        .insertBefore('.cf7-audit-log-container')
                        .delay(5000)
                        .fadeOut();
                    
                    // Optionally reload the page to show updated data
                    if (response.data.updated_count > 0) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    // Show error message
                    $('<div class="cf7-notice cf7-notice-error"><span class="dashicons dashicons-warning"></span><div><p>' + response.data.message + '</p></div></div>')
                        .insertBefore('.cf7-audit-log-container')
                        .delay(5000)
                        .fadeOut();
                }
            },
            error: function() {
                // Show error message
                $('<div class="cf7-notice cf7-notice-error"><span class="dashicons dashicons-warning"></span><div><p><?php _e('An error occurred while updating artist information.', 'cf7-artist-submissions'); ?></p></div></div>')
                    .insertBefore('.cf7-audit-log-container')
                    .delay(5000)
                    .fadeOut();
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
