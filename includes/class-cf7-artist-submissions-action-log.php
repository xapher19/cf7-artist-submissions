<?php
/**
 * Action Logging for CF7 Artist Submissions
 */
class CF7_Artist_Submissions_Action_Log {
    
    /**
     * Initialize the action log
     */
    public static function init() {
        // No specific hooks needed for initialization
    }
    
    /**
     * Create action log table
     */
    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            data text NOT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY submission_id (submission_id),
            KEY action_type (action_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created successfully
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('CF7 Artist Submissions: Failed to create action log table');
        }
    }
    
    /**
     * Log an action
     */
    public static function log_action($submission_id, $action_type, $data = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Try to create the table if it doesn't exist
            self::create_log_table();
            
            // Check again to make sure it was created
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                error_log('CF7 Artist Submissions: Action log table does not exist');
                return false;
            }
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Insert log entry
        $result = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'action_type' => $action_type,
                'data' => $data,
                'date_created' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            // Log the database error
            error_log('CF7 Artist Submissions: Failed to insert log entry. DB Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs for a specific submission
     */
    public static function get_logs_for_submission($submission_id, $action_type = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        // Base query
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE submission_id = %d", $submission_id);
        
        // Add action type filter if specified
        if (!empty($action_type)) {
            $query .= $wpdb->prepare(" AND action_type = %s", $action_type);
        }
        
        // Add order by
        $query .= " ORDER BY date_created DESC";
        
        // Get results
        $logs = $wpdb->get_results($query);
        
        return $logs;
    }
    
    /**
     * Delete logs for a specific submission
     */
    public static function delete_logs_for_submission($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        return $wpdb->delete($table_name, array('submission_id' => $submission_id), array('%d'));
    }
    
    /**
     * Get action type label
     */
    public static function get_action_type_label($action_type) {
        $labels = array(
            'submission_created' => __('Submission Created', 'cf7-artist-submissions'),
            'status_changed' => __('Status Changed', 'cf7-artist-submissions'),
            'field_updated' => __('Field Updated', 'cf7-artist-submissions'),
            'email_sent' => __('Email Sent', 'cf7-artist-submissions')
        );
        
        return isset($labels[$action_type]) ? $labels[$action_type] : $action_type;
    }
    
    /**
     * Test if logging is working properly
     */
    public static function test_logging() {
        $test_id = 999999; // Use a dummy submission ID
        $result = self::log_action($test_id, 'test_logging', 'This is a test entry');
        
        if ($result === false) {
            return new WP_Error('logging_failed', 'Failed to create test log entry');
        }
        
        // Try to retrieve the log entry
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $result);
        $log = $wpdb->get_row($query);
        
        if (!$log) {
            return new WP_Error('logging_retrieval_failed', 'Failed to retrieve test log entry');
        }
        
        // Clean up the test entry
        $wpdb->delete($table_name, array('id' => $result), array('%d'));
        
        return true;
    }
}