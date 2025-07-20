<?php
/**
 * CF7 Artist Submissions - Actions/To-Do System
 */
class CF7_Artist_Submissions_Actions {
    
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
     * Enqueue scripts and styles for actions
     * Note: Actions scripts are now managed by the Tabs system for consistency
     */
    public static function enqueue_scripts($hook) {
        // Actions scripts are now globally enqueued by the Tabs system
        // This ensures consistency and prevents conflicts
        // No additional enqueuing needed here
    }
    
    /**
     * Create actions table
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
     * Check if actions table exists
     */
    public static function table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        return $result === $table_name;
    }
    
    /**
     * Update table schema if needed
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
     * Check and update database schema on admin init
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
    
    /**
     * Get WordPress users for assignment dropdown
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
     * AJAX: Get assignable users
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
    
    /**
     * Add an action
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
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get actions for a submission
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
     * Get outstanding actions across all submissions
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
     * Get imminent actions (due soon or overdue)
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
    
    /**
     * Complete an action
     */
    public static function complete_action($action_id, $notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_actions';
        
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
        
        return $result !== false;
    }
    
    /**
     * Update an action
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
     * Delete an action
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
    
    /**
     * Get actions count by submission
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
    
    /**
     * AJAX: Get actions for a submission
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
     * AJAX: Add a new action
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
     * AJAX: Update an action
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
     * AJAX: Save action (combined add/update)
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
        
        // Debug logging
        error_log('CF7 Actions: Save action called');
        error_log('CF7 Actions: submission_id=' . $submission_id);
        error_log('CF7 Actions: action_id=' . $action_id);
        error_log('CF7 Actions: title=' . $title);
        error_log('CF7 Actions: POST data=' . print_r($_POST, true));

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
     * AJAX: Complete an action
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
     * AJAX: Delete an action
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
     * AJAX: Get outstanding actions for dashboard
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
    
    /**
     * Render actions tab content
     */
    public static function render_actions_tab($post) {
        $submission_id = $post->ID;
        $actions = self::get_actions($submission_id);
        
        wp_nonce_field('cf7_actions_nonce', 'cf7_actions_nonce');
        ?>
        <div class="cf7-actions-container">
            <div class="cf7-actions-header">
                <h3><?php _e('Actions & To-Do Items', 'cf7-artist-submissions'); ?></h3>
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
    
    /**
     * Get actions summary for daily emails
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
    
    /**
     * Get SMTP configuration info for debugging
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
     * Validate email configuration to prevent SMTP errors
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
     * Check if WP Mail SMTP is active and configured
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

    /**
     * Send daily summary email to a user
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
    
    /**
     * Test SMTP configuration by sending a test email
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
     * Manual test method for debugging daily summary emails
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
        
        error_log('CF7 Actions: Debug daily summary email starting for user ' . $user_id . ' to ' . $test_email);
        
        // Validate configuration
        $email_validation = self::validate_email_config();
        error_log('CF7 Actions: Email validation result: ' . print_r($email_validation, true));
        
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
     * Log email errors for debugging
     */
    public static function log_mail_error($wp_error) {
        error_log('CF7 Actions: wp_mail failed - ' . $wp_error->get_error_message());
    }

    /**
     * Send daily summary email to all users with pending actions
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
    
    /**
     * Generate HTML content for summary email
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
    
    /**
     * Format daily summary email content using WooCommerce email template
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
     * Setup WordPress cron for daily summary emails
     */
    public static function setup_daily_summary_cron() {
        if (!wp_next_scheduled('cf7_daily_summary_cron')) {
            wp_schedule_event(strtotime('09:00:00'), 'daily', 'cf7_daily_summary_cron');
        }
    }
    
    /**
     * Clear WordPress cron for daily summary emails
     */
    public static function clear_daily_summary_cron() {
        wp_clear_scheduled_hook('cf7_daily_summary_cron');
    }
    
    /**
     * Render individual action item
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
    
    /**
     * Add context menu script for conversation messages
     */
}
