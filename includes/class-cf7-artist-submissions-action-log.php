<?php
/**
 * CF7 Artist Submissions - Action Logging System
 *
 * Advanced audit logging and activity tracking system providing complete
 * oversight of all plugin operations, user interactions, and system events.
 * Designed for compliance, debugging, administrative oversight, and security
 * monitoring with comprehensive data retention and retrieval capabilities.
 *
 * Features:
 * • Comprehensive event capture with structured data storage
 * • User attribution and permission-based audit trails
 * • Specialized logging modules for emails, files, and system events
 * • Advanced filtering and search capabilities for audit review
 * • Automated cleanup and data retention management
 * • Security-focused logging with privacy compliance support
 * • Performance optimization with strategic database indexing
 * • WordPress standards compliance with internationalization support
 *
 * @package CF7_Artist_Submissions
 * @subpackage AuditLogging
 * @since 1.0.0
 * @version 1.2.0
 */

/**
 * CF7 Artist Submissions Action Log Class
 * 
 * Comprehensive audit logging and activity tracking system providing complete
 * oversight of all plugin operations, user interactions, and system events.
 * Serves as the central controller for database operations, structured data
 * storage, log retrieval with advanced filtering, and specialized logging
 * modules for different event types.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Action_Log {
    
    /**
     * Initialize the action log system.
     * 
     * Sets up necessary hooks and initialization procedures for the audit
     * logging system. Maintains consistent initialization pattern across
     * plugin architecture and provides entry point for system setup.
     * 
     * @since 1.0.0
     */
    public static function init() {
        // No specific hooks needed for initialization
        // Reserved for future extensibility and system setup
    }
    
    // ============================================================================
    // DATABASE MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Create action log database table with comprehensive schema design.
     * 
     * Creates the cf7_action_log table with performance-optimized structure,
     * strategic indexing for query optimization, and data integrity constraints.
     * Implements auto-incrementing primary keys, foreign key relationships through
     * application logic, and comprehensive audit trail functionality with rich
     * metadata storage capabilities.
     * 
     * @since 1.0.0
     */
    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
                $sql = $wpdb->prepare("CREATE TABLE `%1s` (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL DEFAULT 0,
            action_type varchar(100) NOT NULL,
            artist_name varchar(255) DEFAULT '' NOT NULL,
            artist_email varchar(255) DEFAULT '' NOT NULL,
            data longtext,
            date_created datetime NOT NULL,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY user_id (user_id),
            KEY date_created (date_created),
            KEY artist_email (artist_email)
        ) %s;", $table_name, $charset_collate);
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created successfully
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            // Table creation failed
            return false;
        }
        
        return true;
    }
    
    /**
     * Update existing table schema for version compatibility.
     * Adds artist information columns and enhanced indexing for better performance.
     */
    public static function update_table_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            return false;
        }
        
        // Check if new columns already exist
        $columns = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `%1s`", $table_name));
        $column_names = wp_list_pluck($columns, 'Field');
        
        $needs_artist_name = !in_array('artist_name', $column_names);
        $needs_artist_email = !in_array('artist_email', $column_names);
        
        if ($needs_artist_name) {
            $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` ADD COLUMN artist_name varchar(255) DEFAULT '' NOT NULL AFTER action_type", $table_name));
        }
        
        if ($needs_artist_email) {
            $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` ADD COLUMN artist_email varchar(255) DEFAULT '' NOT NULL AFTER artist_name", $table_name));
        }
        
        // Update submission_id to allow 0 for system-wide actions
        $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` MODIFY submission_id bigint(20) NOT NULL DEFAULT 0", $table_name));
        
        // Add additional indexes for better performance
        $existing_indexes = $wpdb->get_results($wpdb->prepare("SHOW INDEXES FROM `%1s`", $table_name));
        $index_names = wp_list_pluck($existing_indexes, 'Key_name');
        
        if (!in_array('artist_email', $index_names)) {
            $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` ADD INDEX (artist_email)", $table_name));
        }
        if (!in_array('user_id', $index_names)) {
            $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` ADD INDEX (user_id)", $table_name));
        }
        if (!in_array('date_created', $index_names)) {
            $wpdb->query($wpdb->prepare("ALTER TABLE `%1s` ADD INDEX (date_created)", $table_name));
        }
        
        return true;
    }
    
    // ============================================================================
    // AUDIT LOGGING SECTION
    // ============================================================================
    
    /**
     * Log action to comprehensive audit trail with structured data storage.
     * 
     * Records actions in the audit log with automatic user identification, precise
     * timestamp tracking, artist information detection, and flexible metadata
     * storage. Serves as the central entry point for all audit logging activities
     * with comprehensive error handling and data validation for reliable audit
     * trail functionality throughout the plugin ecosystem.
     * 
     * @since 1.0.0
     */
    public static function log_action($submission_id, $action_type, $data = '', $artist_name = '', $artist_email = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            // Try to create the table if it doesn't exist
            self::create_log_table();
            
            // Check again to make sure it was created
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
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
        
        // Sanitize input parameters
        $submission_id = intval($submission_id);
        $action_type = sanitize_text_field($action_type);
        $artist_name = sanitize_text_field($artist_name);
        $artist_email = sanitize_email($artist_email);
        
        // Ensure data is properly formatted
        if (is_array($data)) {
            $data = wp_json_encode($data);
        } else {
            $data = sanitize_textarea_field($data);
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
    
    // ============================================================================
    // LOG RETRIEVAL SECTION
    // ============================================================================
    
    /**
     * Get audit logs for specific submission with filtering capabilities.
     * 
     * Retrieves all audit log entries for a given submission with optional
     * action type filtering. Provides optimized query performance with indexed
     * columns and chronological ordering for effective audit trail review
     * and compliance reporting functionality.
     * 
     * @since 1.0.0
     */
    public static function get_logs_for_submission($submission_id, $action_type = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Sanitize input parameters
        $submission_id = intval($submission_id);
        $action_type = sanitize_text_field($action_type);
        
        // Validate submission ID
        if ($submission_id <= 0) {
            return array();
        }
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            return array();
        }
        
        // Base query
        $query = $wpdb->prepare("SELECT * FROM `%1s` WHERE submission_id = %d", $table_name, $submission_id);
        
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
    
    // ============================================================================
    // DATA MAINTENANCE SECTION
    // ============================================================================
    
    /**
     * Delete audit logs for specific submission.
     * Removes all audit log entries for submission cleanup and data integrity.
     */
    public static function delete_logs_for_submission($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Sanitize and validate input
        $submission_id = intval($submission_id);
        if ($submission_id <= 0) {
            return false;
        }
        
        return $wpdb->delete($table_name, array('submission_id' => $submission_id), array('%d'));
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS SECTION
    // ============================================================================
    
    /**
     * Get action type label for display with internationalization support.
     * Converts internal action codes to human-readable labels for admin interface.
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
     * Test audit logging system functionality for diagnostics.
     * Creates test entry, verifies retrieval, and cleans up for system validation.
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
        $query = $wpdb->prepare("SELECT * FROM `%1s` WHERE id = %d", $table_name, $result);
        $log = $wpdb->get_row($query);
        
        if (!$log) {
            return new WP_Error('logging_retrieval_failed', 'Failed to retrieve test log entry');
        }
        
        // Clean up the test entry
        $wpdb->delete($table_name, array('id' => $result), array('%d'));
        
        return true;
    }
    
    // ============================================================================
    // SPECIALIZED LOGGING SECTION
    // ============================================================================
    
    /**
     * Log email sent action with comprehensive metadata capture.
     * 
     * Specialized logging method for email communication activities with
     * template information, recipient details, and sending context. Provides
     * structured audit trail for all email communications within submission
     * management workflow for compliance and debugging purposes.
     * 
     * @since 1.0.0
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
     * Log file upload action with structured metadata.
     * Records file operations with comprehensive details for audit compliance.
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
     * Get artist information from submission metadata.
     * Extracts artist name and email from submission post meta fields.
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
     * Log action creation in task management system.
     * Records when new actions are created with assignment details.
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
     * Log action completion in task management system.
     * Records when actions are marked as completed with user attribution.
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
     * Log conversation clearing for privacy compliance.
     * Records when conversation messages are cleared with metadata.
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
     * Log settings changes for configuration audit trail.
     * Records when plugin settings are modified with before/after values.
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
     * Log status changes with enhanced user tracking.
     * Records submission status transitions with user attribution and metadata.
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
     * Retroactively populates artist data for existing log entries.
     */
    public static function update_missing_artist_info() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_action_log';
        
        // Find entries with missing artist information
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id, submission_id FROM `%1s` 
             WHERE submission_id > 0 AND (artist_name = '' OR artist_name IS NULL)",
            $table_name
        ));
        
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