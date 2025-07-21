<?php
/**
 * Action Logging for CF7 Artist Submissions
 * 
 * This class provides comprehensive audit logging functionality for the plugin,
 * tracking all significant user actions and system events including:
 * - Email sending activities with template and recipient details
 * - Status changes on submissions with before/after values
 * - Form submissions and file uploads
 * - Administrative actions and system events
 * - User authentication and permission changes
 * 
 * The audit log provides a complete trail for compliance, debugging,
 * and administrative oversight purposes.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with audit trail interface and advanced filtering
 */

/**
 * CF7 Artist Submissions Action Log Class
 * 
 * Manages the audit logging system including:
 * - Database table creation and maintenance
 * - Action logging with structured data storage
 * - Log retrieval with filtering and pagination
 * - Test functionality for system validation
 * - Cleanup and maintenance operations
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Action_Log {
    
    /**
     * Initialize the action log system.
     * 
     * Sets up any necessary hooks and initialization procedures.
     * Currently used for future extensibility.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function init() {
        // No specific hooks needed for initialization
    }
    
    /**
     * Create action log database table.
     * 
     * Creates the cf7_action_log table with the following structure:
     * - id: Auto-incrementing primary key
     * - submission_id: Links to submission posts (0 for system-wide actions)
     * - user_id: WordPress user who performed the action
     * - action_type: Type of action (email_sent, status_change, etc.)
     * - artist_name: Name of the artist for easy identification
     * - artist_email: Email of the artist for easy identification
     * - data: JSON data containing action details
     * - date_created: Timestamp of when action occurred
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced table structure for better audit capabilities
     * @since 2.1.0 Added artist columns and enhanced tracking
     * 
     * @return bool True on success, false on failure
     */
    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            artist_name varchar(255) DEFAULT '' NOT NULL,
            artist_email varchar(255) DEFAULT '' NOT NULL,
            data text NOT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY submission_id (submission_id),
            KEY action_type (action_type),
            KEY artist_email (artist_email),
            KEY user_id (user_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created successfully
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Table creation failed
            return false;
        }
        
        return true;
    }
    
    /**
     * Update existing table to add new columns.
     * 
     * Adds artist_name and artist_email columns to existing tables
     * for better audit trail functionality.
     * 
     * @since 2.1.0
     * 
     * @return bool True on success, false on failure
     */
    public static function update_table_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return false;
        }
        
        // Check if new columns already exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = wp_list_pluck($columns, 'Field');
        
        $needs_artist_name = !in_array('artist_name', $column_names);
        $needs_artist_email = !in_array('artist_email', $column_names);
        
        if ($needs_artist_name) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN artist_name varchar(255) DEFAULT '' NOT NULL AFTER action_type");
        }
        
        if ($needs_artist_email) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN artist_email varchar(255) DEFAULT '' NOT NULL AFTER artist_name");
        }
        
        // Update submission_id to allow 0 for system-wide actions
        $wpdb->query("ALTER TABLE $table_name MODIFY submission_id bigint(20) NOT NULL DEFAULT 0");
        
        // Add additional indexes for better performance
        $existing_indexes = $wpdb->get_results("SHOW INDEXES FROM $table_name");
        $index_names = wp_list_pluck($existing_indexes, 'Key_name');
        
        if (!in_array('artist_email', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX (artist_email)");
        }
        if (!in_array('user_id', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX (user_id)");
        }
        if (!in_array('date_created', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX (date_created)");
        }
        
        return true;
    }
    
    /**
     * Log an action to the audit trail.
     * 
     * Records an action in the audit log with structured data storage.
     * Automatically captures the current user and timestamp.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced with better error handling and data validation
     * @since 2.1.0 Added artist information and enhanced tracking
     * 
     * @param int    $submission_id ID of the submission the action relates to (0 for system-wide actions)
     * @param string $action_type   Type of action being logged (e.g., 'email_sent', 'status_change')
     * @param mixed  $data         Action data - string or array (will be JSON encoded if array)
     * @param string $artist_name  Optional artist name (will be auto-detected if not provided)
     * @param string $artist_email Optional artist email (will be auto-detected if not provided)
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_action($submission_id, $action_type, $data = '', $artist_name = '', $artist_email = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Try to create the table if it doesn't exist
            self::create_log_table();
            
            // Check again to make sure it was created
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                return false;
            }
        }
        
        // Auto-detect artist information if not provided and submission_id is valid
        if ($submission_id > 0 && (empty($artist_name) || empty($artist_email))) {
            $artist_info = self::get_artist_info($submission_id);
            if (!empty($artist_info)) {
                if (empty($artist_name)) {
                    $artist_name = $artist_info['name'];
                }
                if (empty($artist_email)) {
                    $artist_email = $artist_info['email'];
                }
            }
        }
        
        // Ensure data is properly formatted
        if (is_array($data)) {
            $data = wp_json_encode($data);
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
                'artist_name' => $artist_name,
                'artist_email' => $artist_email,
                'data' => $data,
                'date_created' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs for a specific submission.
     * 
     * Retrieves all audit log entries for a given submission,
     * optionally filtered by action type.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced with better error handling
     * 
     * @param int    $submission_id ID of the submission to get logs for
     * @param string $action_type   Optional action type filter
     * @return array               Array of log objects, empty array if none found
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
     * Delete logs for a specific submission.
     * 
     * Removes all audit log entries for a given submission.
     * Used during submission deletion to maintain data consistency.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced with better error handling
     * 
     * @param int $submission_id ID of the submission to delete logs for
     * @return bool              True on success, false on failure
     */
    public static function delete_logs_for_submission($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        return $wpdb->delete($table_name, array('submission_id' => $submission_id), array('%d'));
    }
    
    /**
     * Get action type label for display.
     * 
     * Converts internal action type codes to human-readable labels
     * for display in the audit log interface.
     * 
     * @since 2.0.0
     * 
     * @param string $action_type The action type code
     * @return string            Human-readable label
     */
    public static function get_action_type_label($action_type) {
        $labels = array(
            'submission_created' => __('Submission Created', 'cf7-artist-submissions'),
            'status_changed' => __('Status Changed', 'cf7-artist-submissions'),
            'field_updated' => __('Field Updated', 'cf7-artist-submissions'),
            'email_sent' => __('Email Sent', 'cf7-artist-submissions'),
            'file_upload' => __('File Upload', 'cf7-artist-submissions'),
            'form_submission' => __('Form Submission', 'cf7-artist-submissions'),
            'test_logging' => __('Test Entry', 'cf7-artist-submissions')
        );
        
        return isset($labels[$action_type]) ? $labels[$action_type] : $action_type;
    }
    
    /**
     * Test if logging is working properly.
     * 
     * Creates a test log entry, verifies it can be retrieved,
     * then cleans it up. Used for system diagnostics.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced with better error reporting
     * 
     * @return bool|WP_Error True on success, WP_Error on failure
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
    
    /**
     * Log an email sent action.
     * 
     * Convenience method for logging email activities with structured data.
     * 
     * @since 2.0.0
     * 
     * @param int    $submission_id ID of the submission
     * @param string $template_name Name of the email template used
     * @param string $recipient     Email recipient
     * @param string $subject       Email subject
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_email_sent($submission_id, $template_name, $recipient, $subject) {
        $data = array(
            'template_name' => $template_name,
            'recipient' => $recipient,
            'subject' => $subject,
            'sent_by' => get_current_user_id()
        );
        
        return self::log_action($submission_id, 'email_sent', $data);
    }
    
    /**
     * Log a file upload action.
     * 
     * Convenience method for logging file uploads with structured data.
     * 
     * @since 2.0.0
     * 
     * @param int    $submission_id ID of the submission
     * @param string $filename      Name of the uploaded file
     * @param string $file_type     MIME type of the file
     * @param int    $file_size     Size of the file in bytes
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_file_upload($submission_id, $filename, $file_type, $file_size) {
        $data = array(
            'filename' => $filename,
            'file_type' => $file_type,
            'file_size' => $file_size,
            'uploaded_by' => get_current_user_id()
        );
        
        return self::log_action($submission_id, 'file_upload', $data);
    }
    
    /**
     * Get artist information from a submission.
     * 
     * Retrieves the artist name and email from a submission post.
     * 
     * @since 2.1.0
     * 
     * @param int $submission_id ID of the submission
     * @return array|null Array with 'name' and 'email' keys, or null if not found
     */
    public static function get_artist_info($submission_id) {
        if (!$submission_id) {
            return null;
        }
        
        // Get submission post
        $submission = get_post($submission_id);
        if (!$submission || $submission->post_type !== 'cf7_submission') {
            return null;
        }
        
        // Extract artist information from individual post meta fields
        $name = '';
        $email = '';
        
        // Try to get name from various possible meta fields (with cf7_ prefix)
        $name_fields = array('cf7_artist-name', 'cf7_your-name', 'cf7_name', 'cf7_full-name', 'cf7_artist_name');
        foreach ($name_fields as $field) {
            $value = get_post_meta($submission_id, $field, true);
            if (!empty($value)) {
                $name = $value;
                break;
            }
        }
        
        // Try to get email from various possible meta fields (with cf7_ prefix)
        $email_fields = array('cf7_artist-email', 'cf7_your-email', 'cf7_email', 'cf7_contact-email', 'cf7_artist_email');
        foreach ($email_fields as $field) {
            $value = get_post_meta($submission_id, $field, true);
            if (!empty($value)) {
                $email = $value;
                break;
            }
        }
        
        return array(
            'name' => $name,
            'email' => $email
        );
    }
    
    /**
     * Log an action creation.
     * 
     * Logs when a new action is created in the actions system.
     * 
     * @since 2.1.0
     * 
     * @param int    $action_id     ID of the action
     * @param int    $submission_id ID of the submission
     * @param string $action_title  Title of the action
     * @param int    $assigned_to   User ID assigned to
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_action_created($action_id, $submission_id, $action_title, $assigned_to = null) {
        $assigned_user = $assigned_to ? get_userdata($assigned_to) : null;
        
        $data = array(
            'action_id' => $action_id,
            'action_title' => $action_title,
            'assigned_to' => $assigned_to,
            'assigned_to_name' => $assigned_user ? $assigned_user->display_name : '',
            'created_by' => get_current_user_id()
        );
        
        return self::log_action($submission_id, 'action_created', $data);
    }
    
    /**
     * Log an action completion.
     * 
     * Logs when an action is marked as completed.
     * 
     * @since 2.1.0
     * 
     * @param int    $action_id     ID of the action
     * @param int    $submission_id ID of the submission
     * @param string $action_title  Title of the action
     * @param int    $completed_by  User ID who completed it
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_action_completed($action_id, $submission_id, $action_title, $completed_by = null) {
        $completed_user = $completed_by ? get_userdata($completed_by) : get_userdata(get_current_user_id());
        
        $data = array(
            'action_id' => $action_id,
            'action_title' => $action_title,
            'completed_by' => $completed_by ?: get_current_user_id(),
            'completed_by_name' => $completed_user ? $completed_user->display_name : ''
        );
        
        return self::log_action($submission_id, 'action_completed', $data);
    }
    
    /**
     * Log conversation clearing.
     * 
     * Logs when conversations are cleared for an artist.
     * 
     * @since 2.1.0
     * 
     * @param int    $submission_id   ID of the submission
     * @param int    $messages_count  Number of messages cleared
     * @param string $reason         Reason for clearing (optional)
     * @return int|false             The log entry ID on success, false on failure
     */
    public static function log_conversation_cleared($submission_id, $messages_count, $reason = '') {
        $data = array(
            'messages_count' => $messages_count,
            'reason' => $reason,
            'cleared_by' => get_current_user_id()
        );
        
        return self::log_action($submission_id, 'conversation_cleared', $data);
    }
    
    /**
     * Log settings changes.
     * 
     * Logs when plugin settings are modified.
     * 
     * @since 2.1.0
     * 
     * @param string $setting_name  Name of the setting changed
     * @param mixed  $old_value     Previous value
     * @param mixed  $new_value     New value
     * @param string $tab          Settings tab (optional)
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_setting_changed($setting_name, $old_value, $new_value, $tab = '') {
        $data = array(
            'setting_name' => $setting_name,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'tab' => $tab,
            'changed_by' => get_current_user_id()
        );
        
        return self::log_action(0, 'setting_changed', $data, '', ''); // submission_id = 0 for system-wide actions
    }
    
    /**
     * Enhanced log status change with user tracking.
     * 
     * Updates the original method to include better user tracking.
     * 
     * @since 2.1.0
     * 
     * @param int    $submission_id ID of the submission
     * @param string $old_status    Previous status
     * @param string $new_status    New status
     * @param int    $user_id       User who made the change (optional, defaults to current user)
     * @return int|false           The log entry ID on success, false on failure
     */
    public static function log_status_change($submission_id, $old_status, $new_status, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $user = get_userdata($user_id);
        
        $data = array(
            'old_status' => $old_status,
            'new_status' => $new_status,
            'changed_by' => $user_id,
            'changed_by_name' => $user ? $user->display_name : 'Unknown User'
        );
        
        return self::log_action($submission_id, 'status_change', $data);
    }
    
    /**
     * Update existing audit log entries with missing artist information.
     * 
     * This method can be called to retroactively populate artist names
     * for existing audit log entries that have empty artist_name fields.
     * 
     * @since 2.1.0
     * 
     * @return int Number of entries updated
     */
    public static function update_missing_artist_info() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Find entries with missing artist information
        $entries = $wpdb->get_results(
            "SELECT id, submission_id FROM {$table_name} 
             WHERE submission_id > 0 AND (artist_name = '' OR artist_name IS NULL)"
        );
        
        $updated_count = 0;
        
        foreach ($entries as $entry) {
            $artist_info = self::get_artist_info($entry->submission_id);
            
            if ($artist_info && (!empty($artist_info['name']) || !empty($artist_info['email']))) {
                $wpdb->update(
                    $table_name,
                    array(
                        'artist_name' => $artist_info['name'],
                        'artist_email' => $artist_info['email']
                    ),
                    array('id' => $entry->id),
                    array('%s', '%s'),
                    array('%d')
                );
                $updated_count++;
            }
        }
        
        return $updated_count;
    }
}