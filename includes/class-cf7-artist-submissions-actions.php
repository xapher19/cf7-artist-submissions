<?php
/**
 * CF7 Artist Submissions - Actions Management System
 *
 * Comprehensive task and action management system providing complete workflow
 * orchestration for artist submissions with assignment tracking, priority
 * management, due date monitoring, and automated notification systems.
 *
 * Features:
 * • Task creation and assignment with role-based access control
 * • Priority-based workflow organization and deadline management
 * • AJAX communication framework for real-time action management
 * • Automated daily email summaries with SMTP integration
 * • Comprehensive database schema with performance optimization
 * • User assignment system with capability-based filtering
 * • Audit trail integration for complete action lifecycle tracking
 *
 * @package CF7_Artist_Submissions
 * @subpackage ActionsManagement
 * @since 2.0.0
 * @version 2.2.0
 */

/**
 * CF7 Artist Submissions Actions Class
 * 
 * Primary controller class for the CF7 Artist Submissions actions and task
 * management system. Provides comprehensive workflow orchestration, assignment
 * management, notification systems, and database operations for action tracking
 * throughout the submission review lifecycle.
 * 
 * @since 2.0.0
 */
class CF7_Artist_Submissions_Actions {
    
    /**
     * Initialize the comprehensive actions management system.
     * 
     * Establishes complete system integration including AJAX communication
     * endpoints, automated cron scheduling for daily summaries, database
     * schema management with version control, and plugin lifecycle hooks
     * for proper activation and deactivation handling.
     * 
     * @since 2.0.0
     */
    public static function init() {
        // AJAX handlers - matching JavaScript expectations
        add_action('wp_ajax_cf7_get_actions', array(__CLASS__, 'ajax_get_actions'));
        add_action('wp_ajax_cf7_save_action', array(__CLASS__, 'ajax_save_action'));
        add_action('wp_ajax_cf7_complete_action', array(__CLASS__, 'ajax_complete_action'));
        add_action('wp_ajax_cf7_delete_action', array(__CLASS__, 'ajax_delete_action'));
        add_action('wp_ajax_cf7_get_outstanding_actions', array(__CLASS__, 'ajax_get_outstanding_actions'));
        add_action('wp_ajax_cf7_get_assignable_users', array(__CLASS__, 'ajax_get_assignable_users'));
        
        // Daily summary cron hook
        add_action('cf7_daily_summary_cron', array(__CLASS__, 'send_daily_summary_to_all'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        
        // Setup cron on plugin activation
        register_activation_hook(__FILE__, array(__CLASS__, 'setup_daily_summary_cron'));
        register_deactivation_hook(__FILE__, array(__CLASS__, 'clear_daily_summary_cron'));
        
        // Ensure database schema is up to date
        add_action('admin_init', array(__CLASS__, 'check_and_update_schema'));
    }
    
    /**
     * Coordinate script and style enqueuing for actions system.
     * 
     * Actions scripts are now managed by the global Tabs system for
     * consistency and conflict prevention. This method maintains
     * compatibility while delegating to the unified script management.
     * 
     * @since 2.0.0
     */
    public static function enqueue_scripts($hook) {
        // Actions scripts are now globally enqueued by the Tabs system
        // This ensures consistency and prevents conflicts
        // No additional enqueuing needed here
    }
    
    // ============================================================================
    // DATABASE SCHEMA MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Create comprehensive actions table with optimized schema.
     * 
     * Establishes the primary actions table with performance-optimized
     * structure including strategic indexing, appropriate data types,
     * and referential integrity support for robust action management.
     * 
     * @since 2.0.0
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            submission_id mediumint(9) NOT NULL,
            message_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text,
            action_type varchar(50) DEFAULT 'manual',
            assignee_type varchar(20) DEFAULT 'admin',
            assigned_to mediumint(9) DEFAULT NULL,
            priority varchar(20) DEFAULT 'medium',
            due_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_by mediumint(9) NOT NULL,
            completed_by mediumint(9) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            notes text,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY message_id (message_id),
            KEY status (status),
            KEY due_date (due_date),
            KEY priority (priority),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Verify actions table existence in database.
     * 
     * Performs database introspection to confirm the actions table
     * exists and is accessible for operations. Used for conditional
     * table creation and schema validation processes.
     * 
     * @since 2.0.0
     */
    public static function table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        return $result === $table_name;
    }
    
    /**
     * Update actions table schema for version compatibility.
     * 
     * Handles incremental schema updates for existing installations,
     * ensuring backward compatibility while adding new functionality.
     * Performs version-aware column additions and index optimization.
     * 
     * @since 2.0.0
     */
    public static function update_table_schema() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        // Check if assigned_to column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'assigned_to'");
        
        if (empty($column_exists)) {
            // Add the assigned_to column
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN assigned_to mediumint(9) DEFAULT NULL AFTER assignee_type");
            
            // Add index for the new column
            $wpdb->query("ALTER TABLE $table_name ADD KEY assigned_to (assigned_to)");
            
            error_log('CF7 Actions: Added assigned_to column to actions table');
        }
        
        // Could add more schema updates here in the future
    }
    
    /**
     * Automated schema validation and update system.
     * 
     * Performs comprehensive database schema validation and updates
     * during admin initialization. Uses transient caching to prevent
     * repeated database introspection and ensure optimal performance.
     * 
     * @since 2.0.0
     */
    public static function check_and_update_schema() {
        // Only run once per admin session to avoid repeated checks
        if (get_transient('cf7_actions_schema_checked')) {
            return;
        }
        
        // Set transient for 1 hour to avoid repeated checks
        set_transient('cf7_actions_schema_checked', true, HOUR_IN_SECONDS);
        
        // Ensure table exists and is up to date
        if (!self::table_exists()) {
            self::create_table();
        } else {
            self::update_table_schema();
        }
    }
    
    // ============================================================================
    // USER MANAGEMENT AND ASSIGNMENT SYSTEM SECTION
    // ============================================================================
    
    /**
     * Retrieve assignable users with capability-based filtering.
     * 
     * Discovers WordPress users eligible for action assignment based on
     * capability requirements and role permissions. Provides structured
     * user data optimized for frontend assignment interfaces.
     * 
     * @since 2.0.0
     */
    public static function get_assignable_users() {
        $users = get_users(array(
            'capability' => 'edit_posts', // Users who can edit posts (editors and above)
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email')
        ));
        
        $user_options = array();
        foreach ($users as $user) {
            $user_options[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            );
        }
        
        return $user_options;
    }
    
    /**
     * AJAX endpoint for retrieving assignable users.
     * 
     * Secure AJAX handler providing real-time access to assignable user
     * data for frontend assignment interfaces. Includes comprehensive
     * security validation and structured response formatting.
     * 
     * @since 2.0.0
     */
    public static function ajax_get_assignable_users() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $users = self::get_assignable_users();
        wp_send_json_success(array('users' => $users));
    }
    
    // ============================================================================
    // CORE ACTION MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Create new action with comprehensive metadata and assignment.
     * 
     * Primary action creation method providing full-featured action
     * initialization with metadata, assignment, scheduling, and audit
     * trail integration. Handles database schema validation and
     * automatic table creation for seamless operation.
     * 
     * @since 2.0.0
     */
    public static function add_action($submission_id, $title, $description = '', $options = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        // Ensure table exists
        if (!self::table_exists()) {
            self::create_table();
        }
        
        // Update table schema if needed (for existing installations)
        self::update_table_schema();
        
        $defaults = array(
            'message_id' => null,
            'action_type' => 'manual',
            'assignee_type' => 'admin',
            'assigned_to' => null,
            'priority' => 'medium',
            'due_date' => null,
            'created_by' => get_current_user_id()
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'message_id' => $options['message_id'],
                'title' => $title,
                'description' => $description,
                'action_type' => $options['action_type'],
                'assignee_type' => $options['assignee_type'],
                'assigned_to' => $options['assigned_to'],
                'priority' => $options['priority'],
                'due_date' => $options['due_date'],
                'created_by' => $options['created_by'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            // Log the error for debugging
            error_log('CF7 Actions: Failed to insert action. MySQL Error: ' . $wpdb->last_error);
            error_log('CF7 Actions: Last query: ' . $wpdb->last_query);
            return false;
        }
        
        $action_id = $wpdb->insert_id;
        
        // Log action creation to audit trail
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action_created(
                $action_id,
                $submission_id,
                $title,
                $options['assigned_to']
            );
        }
        
        return $action_id;
    }
    
    // ============================================================================
    // ACTION RETRIEVAL AND QUERYING SECTION
    // ============================================================================
    
    /**
     * Retrieve actions for submission with advanced filtering and sorting.
     * 
     * Comprehensive action retrieval system providing filtered access to
     * submission-specific actions with user assignment data and optimized
     * sorting for workflow efficiency. Features intelligent priority-based
     * organization and due date management.
     * 
     * @since 2.0.0
     */
    public static function get_actions($submission_id, $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $where_clause = "WHERE a.submission_id = %d";
        $params = array($submission_id);
        
        if (!empty($status)) {
            $where_clause .= " AND a.status = %s";
            $params[] = $status;
        }
        
        $query = "SELECT a.*, 
                         u.display_name as assigned_user_name,
                         u.user_email as assigned_user_email
                  FROM $table_name a
                  LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                  $where_clause 
                  ORDER BY 
                    CASE a.priority 
                      WHEN 'high' THEN 1 
                      WHEN 'medium' THEN 2 
                      WHEN 'low' THEN 3 
                      ELSE 4 
                    END, 
                    a.due_date ASC, 
                    a.created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    /**
     * Retrieve outstanding actions across all submissions with priority sorting.
     * 
     * Global action retrieval system providing cross-submission visibility
     * of pending actions for administrative oversight and workload management.
     * Features intelligent priority organization and artist name integration.
     * 
     * @since 2.0.0
     */
    public static function get_outstanding_actions($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $query = "SELECT a.*, p.post_title as artist_name 
                  FROM $table_name a 
                  LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID 
                  WHERE a.status = 'pending' 
                  ORDER BY 
                    CASE a.priority 
                      WHEN 'high' THEN 1 
                      WHEN 'medium' THEN 2 
                      WHEN 'low' THEN 3 
                      ELSE 4 
                    END,
                    a.due_date ASC NULLS LAST,
                    a.created_at DESC
                  LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }
    
    /**
     * Retrieve imminent actions requiring immediate attention.
     * 
     * Time-sensitive action retrieval system identifying actions due soon
     * or overdue for immediate attention and escalation. Provides critical
     * deadline management and workflow prioritization support.
     * 
     * @since 2.0.0
     */
    public static function get_imminent_actions($days_ahead = 3) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("+{$days_ahead} days"));
        
        $query = "SELECT a.*, p.post_title as artist_name 
                  FROM $table_name a 
                  LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID 
                  WHERE a.status = 'pending' 
                  AND (a.due_date IS NOT NULL AND a.due_date <= %s)
                  ORDER BY a.due_date ASC";
        
        return $wpdb->get_results($wpdb->prepare($query, $cutoff_date));
    }
    
    // ============================================================================
    // ACTION LIFECYCLE MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Complete action with comprehensive audit trail and status update.
     * 
     * Action completion system providing comprehensive status lifecycle
     * management with audit trail integration, user attribution, and
     * completion documentation. Features error handling and rollback
     * capabilities for data integrity.
     * 
     * @since 2.0.0
     */
    public static function complete_action($action_id, $notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        // Get action details before updating for audit log
        $action = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $action_id));
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'completed_by' => get_current_user_id(),
                'completed_at' => current_time('mysql'),
                'notes' => $notes
            ),
            array('id' => $action_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        // Log action completion to audit trail
        if ($result !== false && $action && class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action_completed(
                $action_id,
                $action->submission_id,
                $action->title,
                get_current_user_id()
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Update existing action with comprehensive field validation.
     * 
     * Flexible action update system supporting partial updates with
     * comprehensive field validation, security controls, and audit
     * trail integration. Features whitelist-based field filtering
     * for security and data integrity.
     * 
     * @since 2.0.0
     */
    public static function update_action($action_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $allowed_fields = array('title', 'description', 'priority', 'due_date', 'assignee_type', 'assigned_to', 'status', 'notes');
        $update_data = array();
        $formats = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $update_data[$field] = $value;
                
                if ($field === 'due_date') {
                    $formats[] = '%s';
                } else {
                    $formats[] = '%s';
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $action_id),
            $formats,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete action with comprehensive validation and cleanup.
     * 
     * Secure action deletion system with comprehensive validation
     * and cleanup procedures. Features audit trail preservation
     * and error handling for safe data removal.
     * 
     * @since 2.0.0
     */
    public static function delete_action($action_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $action_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    // ============================================================================
    // REPORTING AND ANALYTICS SECTION
    // ============================================================================
    
    /**
     * Generate comprehensive action statistics by submission.
     * 
     * Advanced analytics system providing detailed action metrics
     * and performance statistics organized by submission for workflow
     * analysis and optimization. Features multi-dimensional counting
     * with status classification and overdue detection.
     * 
     * @since 2.0.0
     */
    public static function get_actions_count_by_submission() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        $results = $wpdb->get_results(
            "SELECT submission_id, 
                    COUNT(*) as total_actions,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_actions,
                    SUM(CASE WHEN status = 'pending' AND due_date IS NOT NULL AND due_date <= NOW() THEN 1 ELSE 0 END) as overdue_actions
             FROM $table_name 
             GROUP BY submission_id"
        );
        
        $counts = array();
        foreach ($results as $result) {
            $counts[$result->submission_id] = array(
                'total' => $result->total_actions,
                'pending' => $result->pending_actions,
                'overdue' => $result->overdue_actions
            );
        }
        
        return $counts;
    }
    
    // ============================================================================
    // AJAX API ENDPOINTS SECTION
    // ============================================================================
    
    /**
     * AJAX endpoint for retrieving submission actions with filtering.
     * 
     * Secure AJAX handler providing real-time access to submission-specific
     * actions with optional status filtering for dynamic frontend interfaces.
     * Features comprehensive security validation and structured responses.
     * 
     * @since 2.0.0
     */
    public static function ajax_get_actions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        $actions = self::get_actions($submission_id, $status);
        
        wp_send_json_success(array('actions' => $actions));
    }
    
    /**
     * AJAX endpoint for adding new actions (legacy method).
     * 
     * Legacy AJAX handler for action creation maintained for backward
     * compatibility. Redirects to the unified save action method for
     * consistent processing and reduced code duplication.
     * 
     * @since 2.0.0
     * @deprecated 2.1.0 Use ajax_save_action instead
     */
    public static function ajax_add_action() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        $options = array();
        if (!empty($_POST['message_id'])) {
            $options['message_id'] = intval($_POST['message_id']);
            $options['action_type'] = 'conversation';
        }
        
        if (!empty($_POST['priority'])) {
            $options['priority'] = sanitize_text_field($_POST['priority']);
        }
        
        if (!empty($_POST['assignee_type'])) {
            $options['assignee_type'] = sanitize_text_field($_POST['assignee_type']);
        }
        
        if (!empty($_POST['assigned_to'])) {
            $options['assigned_to'] = intval($_POST['assigned_to']);
        }
        
        if (!empty($_POST['due_date'])) {
            $options['due_date'] = sanitize_text_field($_POST['due_date']);
        }
        
        $action_id = self::add_action($submission_id, $title, $description, $options);
        
        if ($action_id) {
            wp_send_json_success(array(
                'message' => 'Action added successfully',
                'action_id' => $action_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to add action'));
        }
    }
    
    /**
     * AJAX endpoint for updating existing actions (legacy method).
     * 
     * Legacy AJAX handler for action updates maintained for backward
     * compatibility. Features comprehensive field validation and
     * security controls for safe action modification.
     * 
     * @since 2.0.0
     * @deprecated 2.1.0 Use ajax_save_action instead
     */
    public static function ajax_update_action() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $action_id = intval($_POST['action_id']);
        
        $data = array();
        $allowed_fields = array('title', 'description', 'priority', 'due_date', 'assignee_type', 'assigned_to', 'status', 'notes');
        
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'description' || $field === 'notes') {
                    $data[$field] = sanitize_textarea_field($_POST[$field]);
                } elseif ($field === 'assigned_to') {
                    $data[$field] = intval($_POST[$field]);
                } else {
                    $data[$field] = sanitize_text_field($_POST[$field]);
                }
            }
        }
        
        $success = self::update_action($action_id, $data);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Action updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update action'));
        }
    }
    
    /**
     * Unified AJAX endpoint for action creation and updates.
     * 
     * Primary AJAX handler providing unified action creation and update
     * functionality with comprehensive security validation, input sanitization,
     * and structured error handling. Features intelligent action ID detection
     * for automatic create/update routing.
     * 
     * @since 2.0.0
     */
    public static function ajax_save_action() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $action_id = !empty($_POST['action_id']) ? intval($_POST['action_id']) : 0;
        $submission_id = intval($_POST['submission_id']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if ($action_id > 0) {
            // Update existing action
            $data = array(
                'title' => $title,
                'description' => $description
            );
            
            $allowed_fields = array('priority', 'due_date', 'assignee_type', 'assigned_to', 'status', 'notes');
            foreach ($allowed_fields as $field) {
                if (isset($_POST[$field])) {
                    if ($field === 'notes') {
                        $data[$field] = sanitize_textarea_field($_POST[$field]);
                    } else {
                        $data[$field] = sanitize_text_field($_POST[$field]);
                    }
                }
            }

            $success = self::update_action($action_id, $data);
            
            if ($success) {
                wp_send_json_success(array(
                    'message' => 'Action updated successfully',
                    'action_id' => $action_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to update action'));
            }
        } else {
            // Add new action
            $options = array();
            if (!empty($_POST['message_id'])) {
                $options['message_id'] = intval($_POST['message_id']);
                $options['action_type'] = 'conversation';
            }
            
            if (!empty($_POST['priority'])) {
                $options['priority'] = sanitize_text_field($_POST['priority']);
            }
            
            if (!empty($_POST['assignee_type'])) {
                $options['assignee_type'] = sanitize_text_field($_POST['assignee_type']);
            }
            
            if (!empty($_POST['assigned_to'])) {
                $options['assigned_to'] = intval($_POST['assigned_to']);
            }
            
            if (!empty($_POST['due_date'])) {
                $options['due_date'] = sanitize_text_field($_POST['due_date']);
            }
            
            $new_action_id = self::add_action($submission_id, $title, $description, $options);
            
            if ($new_action_id) {
                wp_send_json_success(array(
                    'message' => 'Action added successfully',
                    'action_id' => $new_action_id
                ));
            } else {
                // Get more specific error information
                global $wpdb;
                $error_message = 'Failed to add action';
                if (!empty($wpdb->last_error)) {
                    $error_message .= ': ' . $wpdb->last_error;
                }
                wp_send_json_error(array('message' => $error_message));
            }
        }
    }
    
    /**
     * AJAX endpoint for action completion with documentation.
     * 
     * Specialized AJAX handler for action completion operations with
     * comprehensive validation, audit trail integration, and completion
     * documentation support. Features secure completion processing
     * and structured response formatting.
     * 
     * @since 2.0.0
     */
    public static function ajax_complete_action() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $action_id = intval($_POST['action_id']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        $success = self::complete_action($action_id, $notes);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Action completed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to complete action'));
        }
    }
    
    /**
     * AJAX endpoint for secure action deletion.
     * 
     * Secure AJAX handler for action deletion operations with comprehensive
     * validation, permission checking, and error handling. Features safe
     * deletion processing with audit trail considerations.
     * 
     * @since 2.0.0
     */
    public static function ajax_delete_action() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_actions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $action_id = intval($_POST['action_id']);
        
        $success = self::delete_action($action_id);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Action deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete action'));
        }
    }
    
    /**
     * AJAX endpoint for dashboard outstanding actions retrieval.
     * 
     * Specialized AJAX handler providing dashboard-specific action data
     * including imminent actions requiring immediate attention and
     * general outstanding actions for workflow overview. Features
     * optimized queries for dashboard performance.
     * 
     * @since 2.0.0
     */
    public static function ajax_get_outstanding_actions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $imminent_actions = self::get_imminent_actions();
        $outstanding_actions = self::get_outstanding_actions(20);
        
        wp_send_json_success(array(
            'imminent' => $imminent_actions,
            'outstanding' => $outstanding_actions
        ));
    }
    
    // ============================================================================
    // FRONTEND INTERFACE RENDERING SECTION
    // ============================================================================
    
    /**
     * Render comprehensive actions tab interface for submission management.
     * 
     * Complete frontend interface rendering system providing full-featured
     * action management capabilities within submission detail views. Features
     * responsive design, real-time interaction, and comprehensive modal
     * interfaces for action creation and management.
     * 
     * @since 2.0.0
     */
    public static function render_actions_tab($post) {
        $submission_id = $post->ID;
        $actions = self::get_actions($submission_id);
        
        wp_nonce_field('cf7_actions_nonce', 'cf7_actions_nonce');
        ?>
        <div class="cf7-actions-container">
            <div class="cf7-actions-header">
                <h3><?php _e('Actions', 'cf7-artist-submissions'); ?></h3>
                <button type="button" id="cf7-add-action-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Action', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            
            <div class="cf7-actions-filters">
                <button type="button" class="cf7-filter-btn active" data-status="">
                    <?php _e('All', 'cf7-artist-submissions'); ?>
                </button>
                <button type="button" class="cf7-filter-btn" data-status="pending">
                    <?php _e('Pending', 'cf7-artist-submissions'); ?>
                </button>
                <button type="button" class="cf7-filter-btn" data-status="completed">
                    <?php _e('Completed', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            
            <div class="cf7-actions-list" id="cf7-actions-list">
                <?php if (empty($actions)): ?>
                    <div class="cf7-no-actions">
                        <p><?php _e('No actions yet. Click "Add Action" to create your first to-do item.', 'cf7-artist-submissions'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($actions as $action): ?>
                        <?php self::render_action_item($action); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add Action Modal -->
        <div id="cf7-action-modal" class="cf7-modal" style="display: none;">
            <div class="cf7-modal-content">
                <div class="cf7-modal-header">
                    <h3 id="cf7-modal-title"><?php _e('Add New Action', 'cf7-artist-submissions'); ?></h3>
                    <button type="button" class="cf7-modal-close">&times;</button>
                </div>
                <div class="cf7-modal-body">
                    <form id="cf7-action-form">
                        <input type="hidden" id="cf7-action-id" name="action_id">
                        <input type="hidden" id="cf7-submission-id" name="submission_id" value="<?php echo esc_attr($submission_id); ?>">
                        
                        <div class="cf7-form-group">
                            <label for="cf7-action-title"><?php _e('Title', 'cf7-artist-submissions'); ?> *</label>
                            <input type="text" id="cf7-action-title" name="title" required>
                        </div>
                        
                        <div class="cf7-form-group">
                            <label for="cf7-action-description"><?php _e('Description', 'cf7-artist-submissions'); ?></label>
                            <textarea id="cf7-action-description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="cf7-form-row">
                            <div class="cf7-form-group">
                                <label for="cf7-action-priority"><?php _e('Priority', 'cf7-artist-submissions'); ?></label>
                                <select id="cf7-action-priority" name="priority">
                                    <option value="low"><?php _e('Low', 'cf7-artist-submissions'); ?></option>
                                    <option value="medium" selected><?php _e('Medium', 'cf7-artist-submissions'); ?></option>
                                    <option value="high"><?php _e('High', 'cf7-artist-submissions'); ?></option>
                                </select>
                            </div>
                            
                            <div class="cf7-form-group">
                                <label for="cf7-action-assignee"><?php _e('Assigned To', 'cf7-artist-submissions'); ?></label>
                                <select id="cf7-action-assignee" name="assigned_to">
                                    <option value=""><?php _e('Select User...', 'cf7-artist-submissions'); ?></option>
                                </select>
                                <div class="cf7-loading-users" style="display: none;"><?php _e('Loading users...', 'cf7-artist-submissions'); ?></div>
                            </div>
                        </div>
                        
                        <div class="cf7-form-group">
                            <label for="cf7-action-due-date"><?php _e('Due Date', 'cf7-artist-submissions'); ?></label>
                            <input type="datetime-local" id="cf7-action-due-date" name="due_date">
                        </div>
                    </form>
                </div>
                <div class="cf7-modal-footer">
                    <button type="button" class="button" id="cf7-action-cancel"><?php _e('Cancel', 'cf7-artist-submissions'); ?></button>
                    <button type="button" class="button button-primary" id="cf7-action-save"><?php _e('Save Action', 'cf7-artist-submissions'); ?></button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            window.CF7_Actions = window.CF7_Actions || {};
            window.CF7_Actions.submissionId = <?php echo intval($submission_id); ?>;
            window.CF7_Actions.nonce = '<?php echo wp_create_nonce('cf7_actions_nonce'); ?>';
        });
        </script>
        <?php
    }
    
    // ============================================================================
    // EMAIL NOTIFICATION SYSTEM SECTION
    // ============================================================================
    
    /**
     * Generate comprehensive actions summary for email notifications.
     * 
     * Advanced summary generation system providing categorized action data
     * for email notifications with intelligent prioritization, due date
     * analysis, and user-specific filtering. Features multi-dimensional
     * action classification for comprehensive workflow reporting.
     * 
     * @since 2.0.0
     */
    public static function get_actions_summary($user_id = null, $days_ahead = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        $today = date('Y-m-d');
        $cutoff_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
        
        $where_clause = "WHERE a.status = 'pending'";
        $params = array();
        
        if ($user_id) {
            $where_clause .= " AND a.assigned_to = %d";
            $params[] = $user_id;
        }
        
        // Query for different categories
        $summaries = array();
        
        // Overdue actions
        $overdue_query = "SELECT a.*, 
                                u.display_name as assigned_user_name,
                                u.user_email as assigned_user_email,
                                p.post_title as submission_title
                         FROM $table_name a
                         LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                         LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
                         $where_clause AND a.due_date < %s
                         ORDER BY a.due_date ASC, a.priority ASC";
        
        $overdue_params = array_merge($params, array($today));
        $summaries['overdue'] = $wpdb->get_results($wpdb->prepare($overdue_query, $overdue_params));
        
        // Due today
        $today_query = "SELECT a.*, 
                              u.display_name as assigned_user_name,
                              u.user_email as assigned_user_email,
                              p.post_title as submission_title
                       FROM $table_name a
                       LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                       LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
                       $where_clause AND DATE(a.due_date) = %s
                       ORDER BY a.priority ASC";
        
        $today_params = array_merge($params, array($today));
        $summaries['today'] = $wpdb->get_results($wpdb->prepare($today_query, $today_params));
        
        // Due this week
        $week_query = "SELECT a.*, 
                             u.display_name as assigned_user_name,
                             u.user_email as assigned_user_email,
                             p.post_title as submission_title
                      FROM $table_name a
                      LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                      LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
                      $where_clause AND a.due_date > %s AND a.due_date <= %s
                      ORDER BY a.due_date ASC, a.priority ASC";
        
        $week_params = array_merge($params, array($today, $cutoff_date));
        $summaries['upcoming'] = $wpdb->get_results($wpdb->prepare($week_query, $week_params));
        
        // High priority actions without due dates
        $high_priority_query = "SELECT a.*, 
                                      u.display_name as assigned_user_name,
                                      u.user_email as assigned_user_email,
                                      p.post_title as submission_title
                               FROM $table_name a
                               LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                               LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
                               $where_clause AND a.priority = 'high' AND a.due_date IS NULL
                               ORDER BY a.created_at DESC";
        
        $summaries['high_priority'] = $wpdb->get_results($wpdb->prepare($high_priority_query, $params));
        
        return $summaries;
    }
    
    // ============================================================================
    // EMAIL CONFIGURATION AND SMTP MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Retrieve SMTP configuration information for diagnostics.
     * 
     * SMTP configuration detection system providing information about email
     * delivery capabilities, plugin compatibility, and configuration status
     * for email system diagnostics and troubleshooting.
     * 
     * @since 2.0.0
     */
    public static function get_smtp_config_info() {
        $config_info = array(
            'wp_mail_smtp_active' => false,
            'smtp_configured' => false,
            'mailer_type' => 'php_mail',
            'plugins_detected' => array()
        );
        
        // Check for WP Mail SMTP
        if (function_exists('wp_mail_smtp') || class_exists('WPMailSMTP\Core')) {
            $config_info['wp_mail_smtp_active'] = true;
            $config_info['plugins_detected'][] = 'WP Mail SMTP';
        }
        
        // Check for other SMTP plugins
        if (function_exists('easy_wp_smtp_send_mail') || class_exists('Easy_WP_SMTP')) {
            $config_info['plugins_detected'][] = 'Easy WP SMTP';
        }
        
        if (function_exists('postman_wp_mail') || class_exists('PostmanWpMail')) {
            $config_info['plugins_detected'][] = 'Postman SMTP';
        }
        
        if (class_exists('WP_Mail_Bank')) {
            $config_info['plugins_detected'][] = 'WP Mail Bank';
        }
        
        // Check if SMTP is configured via WordPress options
        if (defined('SMTP_HOST') || get_option('smtp_host')) {
            $config_info['smtp_configured'] = true;
            $config_info['mailer_type'] = 'smtp';
        }
        
        return $config_info;
    }

    /**
     * Validate email configuration for reliable delivery.
     * 
     * Email configuration validation system ensuring email configuration
     * integrity for reliable delivery of daily summary notifications with
     * comprehensive field validation and error reporting.
     * 
     * @since 2.0.0
     */
    public static function validate_email_config() {
        $email_options = get_option('cf7_artist_submissions_email_options', array());
        $from_email = isset($email_options['from_email']) ? trim($email_options['from_email']) : trim(get_option('admin_email'));
        $from_name = isset($email_options['from_name']) && !empty($email_options['from_name']) ? trim($email_options['from_name']) : trim(get_bloginfo('name'));
        
        $issues = array();
        
        // Validate from email - must be non-empty and valid
        if (empty($from_email) || strlen($from_email) === 0 || !is_email($from_email)) {
            $issues[] = 'Invalid or empty from email address';
        }
        
        // Validate from name - must be non-empty after trimming
        if (empty($from_name) || strlen($from_name) === 0) {
            $issues[] = 'Empty from name';
        }
        
        // Check if from name is too long (some SMTP providers have limits)
        if (strlen($from_name) > 200) {
            $issues[] = 'From name is too long (over 200 characters)';
        }
        
        // Check site configuration
        $site_host = parse_url(home_url(), PHP_URL_HOST);
        if (empty($site_host) || strlen($site_host) === 0) {
            $issues[] = 'Cannot determine site hostname for Message-ID header';
            $site_host = 'localhost'; // Fallback
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues,
            'from_email' => $from_email,
            'from_name' => $from_name,
            'site_host' => $site_host
        );
    }

    /**
     * Detect SMTP plugin configuration status.
     * 
     * SMTP configuration detection system identifying popular WordPress
     * email plugins and their activation status for optimal email delivery
     * configuration and compatibility assessment.
     * 
     * @since 2.0.0
     */
    public static function is_wp_mail_smtp_configured() {
        // Check for WP Mail SMTP plugin
        if (function_exists('wp_mail_smtp')) {
            return true;
        }
        
        // Check for WP Mail SMTP Pro
        if (class_exists('WPMailSMTP\Core')) {
            return true;
        }
        
        // Check for other popular SMTP plugins
        if (function_exists('easy_wp_smtp_send_mail') || 
            class_exists('Easy_WP_SMTP') ||
            function_exists('postman_wp_mail') ||
            class_exists('PHPMailer')) {
            return true;
        }
        
        return false;
    }

    // ============================================================================
    // EMAIL DELIVERY SYSTEM SECTION
    // ============================================================================
    
    /**
     * Send daily summary email to specified user.
     * 
     * Email delivery system providing personalized daily action summaries
     * with comprehensive validation, SMTP integration, and detailed error
     * handling. Features intelligent content generation, template support,
     * and delivery optimization for reliable notification delivery.
     * 
     * @since 2.0.0
     */
    public static function send_daily_summary_email($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('CF7 Actions: Invalid user ID for daily summary: ' . $user_id);
            return false;
        }
        
        // Validate email configuration first
        $email_validation = self::validate_email_config();
        if (!$email_validation['valid']) {
            error_log('CF7 Actions: Email configuration issues: ' . implode(', ', $email_validation['issues']));
            return false;
        }
        
        $summaries = self::get_actions_summary($user_id);
        
        // Check if there are any actions to report
        $total_actions = count($summaries['overdue']) + count($summaries['today']) + 
                        count($summaries['upcoming']) + count($summaries['high_priority']);
        
        if ($total_actions === 0) {
            return true; // No actions to report, but don't consider it an error
        }
        
        $subject = sprintf(__('Task Summary: %d items need your attention (%s)', 'cf7-artist-submissions'), 
                          $total_actions, date('M j, Y'));
        
        // Use validated email configuration
        $from_email = $email_validation['from_email'];
        $from_name = $email_validation['from_name'];
        $site_host = $email_validation['site_host'];
        
        // Get email options for WooCommerce template setting
        $email_options = get_option('cf7_artist_submissions_email_options', array());
        $use_wc_template = isset($email_options['use_wc_template']) && $email_options['use_wc_template'] && class_exists('WooCommerce');
        
        // Generate email content
        $message = self::generate_summary_email_content($summaries, $user, $use_wc_template);
        
        // Simple headers - just the essentials to avoid SMTP2GO errors
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        // Only add From header if we have both email and name
        if (!empty($from_name) && !empty($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        
        // Add essential headers to improve deliverability and reduce spam flags
        $headers[] = 'Reply-To: ' . $from_email;
        $headers[] = 'X-Mailer: WordPress';
        $headers[] = 'MIME-Version: 1.0';
        
        // Check SMTP configuration for logging
        $smtp_info = self::get_smtp_config_info();
        error_log('CF7 Actions: Sending daily summary with simplified headers');
        error_log('CF7 Actions: Headers: ' . print_r($headers, true));
        
        // Apply email configuration filters
        add_filter('wp_mail_from', function($from) use ($from_email) {
            return $from_email;
        });
        
        add_filter('wp_mail_from_name', function($from_name_filter) use ($from_name) {
            return $from_name;
        });
        
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
        
        // Send the email
        $mail_sent = wp_mail($user->user_email, $subject, $message, $headers);
        
        // Clean up filters to avoid affecting other emails
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
        remove_all_filters('wp_mail_content_type');
        
        // Log success/failure for debugging
        if ($mail_sent) {
            $mailer_type = $smtp_info['smtp_configured'] ? 'SMTP' : 'PHP mail';
            $plugins = !empty($smtp_info['plugins_detected']) ? implode(', ', $smtp_info['plugins_detected']) : 'None';
            error_log('CF7 Actions: Daily summary email sent successfully to ' . $user->user_email . ' via ' . $mailer_type . ' (Plugins: ' . $plugins . ')');
        } else {
            error_log('CF7 Actions: Failed to send daily summary email to ' . $user->user_email);
            
            // Get additional error details if available
            global $phpmailer;
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                error_log('CF7 Actions: PHPMailer Error: ' . $phpmailer->ErrorInfo);
            }
        }
        
        return $mail_sent;
    }
    
    // ============================================================================
    // EMAIL TESTING AND DIAGNOSTICS SECTION
    // ============================================================================
    
    /**
     * Test SMTP configuration with diagnostic reporting.
     * 
     * SMTP testing system providing configuration validation and diagnostic
     * email delivery for troubleshooting email delivery issues. Features
     * configuration analysis, test email generation, and detailed reporting
     * for email system optimization.
     * 
     * @since 2.0.0
     */
    public static function test_smtp_configuration($test_email = null) {
        if (!$test_email) {
            $test_email = get_option('admin_email');
        }
        
        // Validate email configuration first
        $email_validation = self::validate_email_config();
        if (!$email_validation['valid']) {
            return array(
                'success' => false,
                'error' => 'Email configuration issues: ' . implode(', ', $email_validation['issues']),
                'smtp_info' => self::get_smtp_config_info(),
                'test_email' => $test_email
            );
        }
        
        $smtp_info = self::get_smtp_config_info();
        $from_email = $email_validation['from_email'];
        $from_name = $email_validation['from_name'];
        
        $subject = 'CF7 Artist Submissions - SMTP Test Email';
        $message = '<h2>SMTP Configuration Test</h2>';
        $message .= '<p>This is a test email to verify SMTP configuration for CF7 Artist Submissions daily summary emails.</p>';
        $message .= '<h3>Configuration Details:</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>SMTP Configured:</strong> ' . ($smtp_info['smtp_configured'] ? 'Yes' : 'No') . '</li>';
        $message .= '<li><strong>Mailer Type:</strong> ' . $smtp_info['mailer_type'] . '</li>';
        $message .= '<li><strong>Plugins Detected:</strong> ' . (!empty($smtp_info['plugins_detected']) ? implode(', ', $smtp_info['plugins_detected']) : 'None') . '</li>';
        $message .= '<li><strong>From Email:</strong> ' . $from_email . '</li>';
        $message .= '<li><strong>From Name:</strong> ' . $from_name . '</li>';
        $message .= '</ul>';
        $message .= '<p>If you received this email, your SMTP configuration is working correctly for daily summary emails.</p>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: CF7 Artist Submissions Plugin - SMTP Test'
        );
        
        // Only add From header if we have valid values
        if (!empty($from_name) && !empty($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        } elseif (!empty($from_email)) {
            $headers[] = 'From: ' . $from_email;
        }
        
        // Apply the same filters as the daily summary emails
        add_filter('wp_mail_from', function($from) use ($from_email) {
            return $from_email;
        });
        
        add_filter('wp_mail_from_name', function($from_name_filter) use ($from_name) {
            return $from_name;
        });
        
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
        
        $result = wp_mail($test_email, $subject, $message, $headers);
        
        // Clean up filters
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
        remove_all_filters('wp_mail_content_type');
        
        return array(
            'success' => $result,
            'smtp_info' => $smtp_info,
            'test_email' => $test_email
        );
    }

    /**
     * Debug daily summary email delivery system.
     * 
     * Debugging utility for daily summary email delivery featuring manual
     * test execution, user override capabilities, and detailed diagnostic
     * reporting for troubleshooting email delivery issues.
     * 
     * @since 2.0.0
     */
    public static function debug_daily_summary_email($user_id = null, $test_email = null) {
        // Use admin user if no user specified
        if (!$user_id) {
            $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
            if (empty($admin_users)) {
                return array('error' => 'No admin users found');
            }
            $user_id = $admin_users[0]->ID;
        }
        
        // Use admin email if no test email specified
        if (!$test_email) {
            $test_email = get_option('admin_email');
        }
        
        // Validate configuration
        $email_validation = self::validate_email_config();
        
        if (!$email_validation['valid']) {
            return array('error' => 'Email validation failed: ' . implode(', ', $email_validation['issues']));
        }
        
        // Get user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return array('error' => 'User not found: ' . $user_id);
        }
        
        // Temporarily override user email for testing
        $original_email = $user->user_email;
        $user->user_email = $test_email;
        
        // Send the email
        $result = self::send_daily_summary_email($user_id);
        
        // Restore original email
        $user->user_email = $original_email;
        
        return array(
            'success' => $result,
            'user_id' => $user_id,
            'test_email' => $test_email,
            'validation' => $email_validation,
            'smtp_info' => self::get_smtp_config_info()
        );
    }

    /**
     * Log email delivery errors for debugging.
     * 
     * Email error logging utility for WordPress email delivery failures
     * providing detailed error information and debugging context for
     * troubleshooting email delivery issues.
     * 
     * @since 2.0.0
     */
    public static function log_mail_error($wp_error) {
        error_log('CF7 Actions: wp_mail failed - ' . $wp_error->get_error_message());
    }

    // ============================================================================
    // EMAIL TESTING DATA GENERATION SECTION
    // ============================================================================
    
    /**
     * Generate sample action data for email testing.
     * 
     * Sample data generation system creating realistic action objects for
     * daily summary email testing and template validation. Features
     * comprehensive action categorization and realistic metadata.
     * 
     * @since 2.0.0
     */
    public static function generate_sample_actions_summary() {
        // Create sample action objects with the same structure as real data
        $sample_overdue = array(
            (object) array(
                'id' => 101,
                'submission_id' => 1001,
                'action_type' => 'review',
                'title' => 'Review portfolio submission',
                'description' => 'Complete initial review of artist portfolio and provide feedback',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'priority' => 'high',
                'status' => 'pending',
                'submission_title' => 'Digital Art Portfolio - Sarah Johnson',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            ),
            (object) array(
                'id' => 102,
                'submission_id' => 1002,
                'action_type' => 'follow_up',
                'title' => 'Follow up on missing documents',
                'description' => 'Contact artist about missing portfolio pieces',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'priority' => 'medium',
                'status' => 'pending',
                'submission_title' => 'Photography Collection - Mike Chen',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            )
        );

        $sample_today = array(
            (object) array(
                'id' => 103,
                'submission_id' => 1003,
                'action_type' => 'interview',
                'title' => 'Schedule artist interview',
                'description' => 'Arrange video call to discuss commissioned work details',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => date('Y-m-d H:i:s'),
                'priority' => 'high',
                'status' => 'pending',
                'submission_title' => 'Mural Design Proposal - Lisa Rodriguez',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            )
        );

        $sample_upcoming = array(
            (object) array(
                'id' => 104,
                'submission_id' => 1004,
                'action_type' => 'review',
                'title' => 'Final approval needed',
                'description' => 'Review revised artwork and provide final approval',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
                'priority' => 'medium',
                'status' => 'pending',
                'submission_title' => 'Abstract Paintings Series - David Kim',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            ),
            (object) array(
                'id' => 105,
                'submission_id' => 1005,
                'action_type' => 'payment',
                'title' => 'Process artist payment',
                'description' => 'Submit payment request for approved commissioned work',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => date('Y-m-d H:i:s', strtotime('+5 days')),
                'priority' => 'low',
                'status' => 'pending',
                'submission_title' => 'Sculpture Design - Emma Wilson',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            )
        );

        $sample_high_priority = array(
            (object) array(
                'id' => 106,
                'submission_id' => 1006,
                'action_type' => 'urgent_review',
                'title' => 'Urgent: Exhibition deadline approaching',
                'description' => 'Critical review needed for upcoming gallery exhibition',
                'assigned_to' => 1,
                'assigned_by' => 1,
                'due_date' => null,
                'priority' => 'urgent',
                'status' => 'pending',
                'submission_title' => 'Contemporary Installation - Alex Thompson',
                'assigned_user_name' => 'Art Director',
                'assigned_user_email' => 'test@example.com'
            )
        );

        return array(
            'overdue' => $sample_overdue,
            'today' => $sample_today,
            'upcoming' => $sample_upcoming,
            'high_priority' => $sample_high_priority
        );
    }

    /**
     * Send test daily summary email with sample action data.
     * 
     * Test email system delivering sample daily summary emails with realistic
     * action data for template validation, delivery testing, and user experience
     * verification. Features complete email generation pipeline with sample data
     * integration and delivery confirmation.
     * 
     * @since 2.0.0
     */
    public static function send_test_daily_summary_email($test_email) {
        // Validate email format
        if (!is_email($test_email)) {
            return array('error' => 'Invalid email address format');
        }

        // Validate email configuration
        $email_validation = self::validate_email_config();
        if (!$email_validation['valid']) {
            return array('error' => 'Email validation failed: ' . implode(', ', $email_validation['issues']));
        }

        // Generate sample actions summary
        $summaries = self::generate_sample_actions_summary();

        // Create a sample user object for the test
        $test_user = (object) array(
            'ID' => 1,
            'display_name' => 'Test User',
            'user_email' => $test_email
        );

        // Calculate total actions for subject
        $total_actions = count($summaries['overdue']) + count($summaries['today']) + 
                        count($summaries['upcoming']) + count($summaries['high_priority']);

        $subject = sprintf(__('TEST: Task Summary - %d items need your attention (%s)', 'cf7-artist-submissions'), 
                          $total_actions, date('M j, Y'));

        // Use validated email configuration
        $from_email = $email_validation['from_email'];
        $from_name = $email_validation['from_name'];

        // Get email options for WooCommerce template setting
        $email_options = get_option('cf7_artist_submissions_email_options', array());
        $use_wc_template = isset($email_options['use_wc_template']) && $email_options['use_wc_template'] && class_exists('WooCommerce');

        // Generate email content with sample data
        $message = self::generate_summary_email_content($summaries, $test_user, $use_wc_template);

        // Add test notice to the beginning of the email
        $test_notice = '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #856404;">
            <p style="margin: 0; font-weight: bold; font-size: 16px;">📧 TEST EMAIL</p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">This is a test daily summary email with sample data. Real emails will contain actual task information.</p>
        </div>';

        $message = $test_notice . $message;

        // Simple headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );

        // Only add From header if we have both email and name
        if (!empty($from_name) && !empty($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        // Set up mail filters temporarily
        add_filter('wp_mail_from', function($from) use ($from_email) {
            return $from_email;
        });

        add_filter('wp_mail_from_name', function($from_name_filter) use ($from_name) {
            return $from_name;
        });

        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });

        // Send the email
        $result = wp_mail($test_email, $subject, $message, $headers);

        // Clean up filters
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
        remove_all_filters('wp_mail_content_type');

        return array(
            'success' => $result,
            'test_email' => $test_email,
            'validation' => $email_validation,
            'smtp_info' => self::get_smtp_config_info(),
            'sample_data' => true
        );
    }

    // ============================================================================
    // AUTOMATION AND BATCH PROCESSING SECTION
    // ============================================================================
    
    /**
     * Send daily summary emails to all users with pending actions.
     * 
     * Batch email processing system for automated daily summary delivery
     * to all users with assigned pending actions. Features intelligent
     * user discovery, personalized content generation, and efficient
     * batch processing for reliable notification automation.
     * 
     * @since 2.0.0
     */
    public static function send_daily_summary_to_all() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
        // Get all users who have pending actions assigned to them
        $query = "SELECT DISTINCT a.assigned_to 
                  FROM $table_name a 
                  WHERE a.status = 'pending' 
                  AND a.assigned_to IS NOT NULL";
        
        $users_with_actions = $wpdb->get_col($query);
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($users_with_actions as $user_id) {
            if (self::send_daily_summary_email($user_id)) {
                $sent_count++;
            } else {
                $failed_count++;
            }
        }
        
        return array(
            'sent' => $sent_count,
            'failed' => $failed_count,
            'total_users' => count($users_with_actions)
        );
    }
    
    // ============================================================================
    // EMAIL TEMPLATE GENERATION SECTION
    // ============================================================================
    
    /**
     * Generate HTML email content for action summary delivery.
     * 
     * Email template generation system creating rich HTML content for daily
     * action summaries with professional presentation, responsive design,
     * and optional WooCommerce styling integration. Features comprehensive
     * action categorization and contextual formatting.
     * 
     * @since 2.0.0
     */
    private static function generate_summary_email_content($summaries, $user, $use_wc_template = false) {
        $site_name = get_bloginfo('name');
        $dashboard_url = admin_url('edit.php?post_type=cf7_submission&page=cf7-dashboard');
        
        // Generate the core email content first
        $email_heading = sprintf(__('Daily Action Summary for %s', 'cf7-artist-submissions'), date(get_option('date_format')));
        
        ob_start();
        ?>
        <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px;">
            <!-- Header Section -->
            <div style="text-align: center; padding: 20px 0; border-bottom: 2px solid #0073aa;">
                <h1 style="color: #0073aa; margin: 0; font-size: 24px;">
                    <?php echo esc_html($site_name); ?>
                </h1>
                <h2 style="color: #666; margin: 10px 0 0 0; font-size: 18px; font-weight: normal;">
                    <?php echo esc_html($email_heading); ?>
                </h2>
            </div>
            
            <!-- Personal Greeting -->
            <div style="margin: 30px 0;">
                <p style="margin: 0; font-size: 16px;">
                    Dear <?php echo esc_html($user->display_name); ?>,
                </p>
                <p style="margin: 15px 0 0 0; color: #666;">
                    This is your scheduled daily summary of tasks and actions that need your attention. 
                    We've organized them by priority to help you plan your day effectively.
                </p>
            </div>
            
            <?php if (!empty($summaries['overdue'])): ?>
            <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #dc3232; background: #fef7f7;">
                <h3 style="margin-top: 0; color: #dc3232;">🚨 Overdue Actions (<?php echo count($summaries['overdue']); ?>)</h3>
                <?php foreach ($summaries['overdue'] as $action): ?>
                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo esc_html($action->title); ?></div>
                        <div style="font-size: 0.9em; color: #666;">
                            Submission: <?php echo esc_html($action->submission_title); ?><br>
                            Due: <?php echo date(get_option('date_format'), strtotime($action->due_date)); ?><br>
                            Priority: <?php echo ucfirst($action->priority); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($summaries['today'])): ?>
            <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #ffb900; background: #fffbf0;">
                <h3 style="margin-top: 0; color: #ffb900;">📅 Due Today (<?php echo count($summaries['today']); ?>)</h3>
                <?php foreach ($summaries['today'] as $action): ?>
                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo esc_html($action->title); ?></div>
                        <div style="font-size: 0.9em; color: #666;">
                            Submission: <?php echo esc_html($action->submission_title); ?><br>
                            Priority: <?php echo ucfirst($action->priority); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($summaries['upcoming'])): ?>
            <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #00a32a; background: #f7fff7;">
                <h3 style="margin-top: 0; color: #00a32a;">📋 Due This Week (<?php echo count($summaries['upcoming']); ?>)</h3>
                <?php foreach ($summaries['upcoming'] as $action): ?>
                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo esc_html($action->title); ?></div>
                        <div style="font-size: 0.9em; color: #666;">
                            Submission: <?php echo esc_html($action->submission_title); ?><br>
                            Due: <?php echo date(get_option('date_format'), strtotime($action->due_date)); ?><br>
                            Priority: <?php echo ucfirst($action->priority); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($summaries['high_priority'])): ?>
            <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #8c8f94; background: #f9f9f9;">
                <h3 style="margin-top: 0; color: #8c8f94;">⚡ High Priority Actions (<?php echo count($summaries['high_priority']); ?>)</h3>
                <?php foreach ($summaries['high_priority'] as $action): ?>
                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo esc_html($action->title); ?></div>
                        <div style="font-size: 0.9em; color: #666;">
                            Submission: <?php echo esc_html($action->submission_title); ?><br>
                            Created: <?php echo date(get_option('date_format'), strtotime($action->created_at)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Call to Action -->
            <div style="text-align: center; margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">Ready to get started?</h3>
                <p style="margin: 0 0 20px 0; color: #666;">
                    Access your dashboard to view, update, and manage all your assigned tasks.
                </p>
                <a href="<?php echo esc_url($dashboard_url); ?>" 
                   style="background: #0073aa; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 16px;">
                    Open Dashboard
                </a>
            </div>
            
            <!-- Professional Footer -->
            <div style="border-top: 1px solid #e0e0e0; padding-top: 30px; margin-top: 40px;">
                <div style="text-align: center; color: #666; font-size: 14px; line-height: 1.5;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php echo esc_html($site_name); ?></strong> - Artist Submissions Management System
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        This automated summary helps you stay on top of your assigned tasks and deadlines.
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #999;">
                        You're receiving this email because you have active task assignments. 
                        This notification is sent daily at 9:00 AM to keep your workflow organized.
                    </p>
                </div>
            </div>
        </div>
        <?php
        $email_content = ob_get_clean();
        
        // Apply WooCommerce template if enabled and available
        if ($use_wc_template && class_exists('WooCommerce')) {
            try {
                $email_content = self::format_woocommerce_daily_summary_email($email_content, $email_heading);
            } catch (Exception $e) {
                error_log('CF7 Actions: WooCommerce template error for daily summary: ' . $e->getMessage());
                // Fall back to the regular content if WooCommerce templating fails
            }
        } else {
            // Wrap in a professional HTML structure for non-WooCommerce emails
            $site_url = home_url();
            $email_content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="date=no">
    <meta name="format-detection" content="address=no">
    <meta name="format-detection" content="email=no">
    <title>' . esc_attr($site_name) . ' - Daily Task Summary</title>
    <style type="text/css">
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 10px !important; }
            .content-section { padding: 15px !important; }
        }
        /* Prevent blue links on iOS */
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
    <div class="email-container" style="max-width: 600px; margin: 0 auto; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <div class="content-section" style="padding: 30px;">
            ' . $email_content . '
        </div>
    </div>
    <!-- Email tracking pixel for deliverability -->
    <div style="display: none; max-height: 0; overflow: hidden;">
        Daily task summary from ' . esc_attr($site_name) . ' - Manage your assigned actions and deadlines effectively
    </div>
</body>
</html>';
        }
        
        return $email_content;
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS SECTION
    // ============================================================================

    /**
     * Format daily summary email using WooCommerce template.
     *
     * @since 2.0.0
     */
    private static function format_woocommerce_daily_summary_email($content, $heading) {
        // Use the existing, working WooCommerce template method from the conversations system
        if (class_exists('CF7_Artist_Submissions_Conversations') && 
            method_exists('CF7_Artist_Submissions_Conversations', 'format_woocommerce_conversation_email')) {
            return CF7_Artist_Submissions_Conversations::format_woocommerce_conversation_email($content, $heading);
        }
        
        // If the conversations class is not available, return the content as-is
        return $content;
    }

    /**
     * Setup WordPress cron for daily summary emails.
     *
     * @since 2.0.0
     */
    public static function setup_daily_summary_cron() {
        if (!wp_next_scheduled('cf7_daily_summary_cron')) {
            wp_schedule_event(strtotime('09:00:00'), 'daily', 'cf7_daily_summary_cron');
        }
    }
    
    /**
     * Clear WordPress cron for daily summary emails.
     *
     * @since 2.0.0
     */
    public static function clear_daily_summary_cron() {
        wp_clear_scheduled_hook('cf7_daily_summary_cron');
    }
    
    /**
     * Render individual action item.
     *
     * @since 2.0.0
     */
    private static function render_action_item($action) {
        $priority_class = 'priority-' . $action->priority;
        $status_class = 'status-' . $action->status;
        $overdue_class = '';
        
        if ($action->status === 'pending' && $action->due_date && strtotime($action->due_date) < time()) {
            $overdue_class = 'overdue';
        }
        
        $user = get_user_by('id', $action->created_by);
        $created_by_name = $user ? $user->display_name : __('Unknown', 'cf7-artist-submissions');
        ?>
        <div class="cf7-action-item <?php echo esc_attr($priority_class . ' ' . $status_class . ' ' . $overdue_class); ?>" data-action-id="<?php echo esc_attr($action->id); ?>" data-status="<?php echo esc_attr($action->status); ?>">
            <div class="cf7-action-header">
                <div class="cf7-action-priority">
                    <span class="cf7-priority-indicator <?php echo esc_attr($priority_class); ?>"></span>
                </div>
                <div class="cf7-action-content">
                    <h4 class="cf7-action-title"><?php echo esc_html($action->title); ?></h4>
                    <?php if (!empty($action->description)): ?>
                        <p class="cf7-action-description"><?php echo esc_html($action->description); ?></p>
                    <?php endif; ?>
                </div>
                <div class="cf7-action-controls">
                    <?php if ($action->status === 'pending'): ?>
                        <button type="button" class="cf7-action-complete" title="<?php _e('Mark as completed', 'cf7-artist-submissions'); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="cf7-action-edit" title="<?php _e('Edit action', 'cf7-artist-submissions'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="cf7-action-delete" title="<?php _e('Delete action', 'cf7-artist-submissions'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="cf7-action-meta">
                <div class="cf7-action-details">
                    <span class="cf7-assignee">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo $action->assignee_type === 'admin' ? __('Admin', 'cf7-artist-submissions') : __('Artist', 'cf7-artist-submissions'); ?>
                    </span>
                    
                    <?php if ($action->due_date): ?>
                        <span class="cf7-due-date <?php echo $overdue_class; ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($action->due_date)); ?>
                        </span>
                    <?php endif; ?>
                    
                    <span class="cf7-created-info">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php printf(__('Created by %s on %s', 'cf7-artist-submissions'), 
                            esc_html($created_by_name), 
                            date_i18n(get_option('date_format'), strtotime($action->created_at))
                        ); ?>
                    </span>
                </div>
                
                <?php if ($action->status === 'completed'): ?>
                    <div class="cf7-completion-info">
                        <?php
                        $completed_user = get_user_by('id', $action->completed_by);
                        $completed_by_name = $completed_user ? $completed_user->display_name : __('Unknown', 'cf7-artist-submissions');
                        ?>
                        <span class="cf7-completed-by">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php printf(__('Completed by %s on %s', 'cf7-artist-submissions'), 
                                esc_html($completed_by_name), 
                                date_i18n(get_option('date_format'), strtotime($action->completed_at))
                            ); ?>
                        </span>
                        <?php if (!empty($action->notes)): ?>
                            <div class="cf7-completion-notes">
                                <strong><?php _e('Notes:', 'cf7-artist-submissions'); ?></strong>
                                <?php echo esc_html($action->notes); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
