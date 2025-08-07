<?php
/**
 * CF7 Artist Submissions - Guest Curator Management System
 *
 * Comprehensive guest curator management system providing tokenized authentication,
 * open call specific access control, and enhanced curator notes and rating systems.
 * Designed for external curators who need submission review access without full
 * WordPress user accounts.
 *
 * Features:
 * • Guest curator registration and management
 * • Email-based tokenized authentication
 * • Open call specific access permissions
 * • Enhanced multi-curator notes system
 * • Individual curator rating tracking
 * • Secure portal interface for external access
 *
 * @package CF7_Artist_Submissions
 * @subpackage GuestCurators
 * @since 1.3.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Guest Curators Class
 * 
 * Manages guest curator accounts, authentication, permissions, and portal access.
 */
class CF7_Artist_Submissions_Guest_Curators {
    
    /**
     * Initialize the guest curator system
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_hooks'));
        add_action('admin_init', array(__CLASS__, 'maybe_create_tables'));
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // Admin interface hooks
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // AJAX handlers for guest curator management
        add_action('wp_ajax_cf7_add_guest_curator', array(__CLASS__, 'ajax_add_guest_curator'));
        add_action('wp_ajax_cf7_update_guest_curator', array(__CLASS__, 'ajax_update_guest_curator'));
        add_action('wp_ajax_cf7_delete_guest_curator', array(__CLASS__, 'ajax_delete_guest_curator'));
        add_action('wp_ajax_cf7_get_guest_curators', array(__CLASS__, 'ajax_get_guest_curators'));
        add_action('wp_ajax_cf7_get_guest_curator', array(__CLASS__, 'ajax_get_guest_curator'));
        add_action('wp_ajax_cf7_get_open_calls', array(__CLASS__, 'ajax_get_open_calls'));
        add_action('wp_ajax_cf7_get_curator_permissions', array(__CLASS__, 'ajax_get_curator_permissions'));
        add_action('wp_ajax_cf7_send_curator_login_link', array(__CLASS__, 'ajax_send_curator_login_link'));
        
        // Portal authentication hooks
        add_action('wp_ajax_nopriv_cf7_curator_login', array(__CLASS__, 'ajax_curator_login'));
        add_action('wp_ajax_nopriv_cf7_curator_verify_token', array(__CLASS__, 'ajax_verify_token'));
        
        // Portal API endpoints (for guest curators)
        add_action('wp_ajax_nopriv_cf7_portal_get_submissions', array(__CLASS__, 'ajax_portal_get_submissions'));
        add_action('wp_ajax_nopriv_cf7_portal_get_submission', array(__CLASS__, 'ajax_portal_get_submission'));
        add_action('wp_ajax_nopriv_cf7_portal_add_note', array(__CLASS__, 'ajax_portal_add_note'));
        add_action('wp_ajax_nopriv_cf7_portal_save_rating', array(__CLASS__, 'ajax_portal_save_rating'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    /**
     * Check if tables exist and create if needed
     */
    public static function maybe_create_tables() {
        if (!self::guest_curators_table_exists()) {
            self::create_guest_curators_table();
        }
        if (!self::curator_notes_table_exists()) {
            self::create_curator_notes_table();
        }
        if (!self::curator_permissions_table_exists()) {
            self::create_curator_permissions_table();
        }
    }
    
    /**
     * Create the guest curators table
     */
    public static function create_guest_curators_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login datetime DEFAULT NULL,
            login_token varchar(64) DEFAULT NULL,
            token_expires datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY created_at (created_at),
            KEY login_token (login_token),
            KEY token_expires (token_expires)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create the enhanced curator notes table
     */
    public static function create_curator_notes_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_curator_notes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) unsigned NOT NULL,
            curator_id bigint(20) unsigned DEFAULT NULL,
            guest_curator_id bigint(20) unsigned DEFAULT NULL,
            curator_name varchar(255) NOT NULL,
            note_content text NOT NULL,
            note_type enum('note', 'comment') DEFAULT 'note',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY curator_id (curator_id),
            KEY guest_curator_id (guest_curator_id),
            KEY created_at (created_at),
            KEY note_type (note_type),
            FOREIGN KEY (guest_curator_id) REFERENCES {$wpdb->prefix}cf7as_guest_curators(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create the curator permissions table
     */
    public static function create_curator_permissions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_curator_permissions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            guest_curator_id bigint(20) unsigned NOT NULL,
            open_call_term_id bigint(20) unsigned NOT NULL,
            permission_type enum('view', 'rate', 'comment') DEFAULT 'view',
            granted_at datetime DEFAULT CURRENT_TIMESTAMP,
            granted_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_permission (guest_curator_id, open_call_term_id, permission_type),
            KEY guest_curator_id (guest_curator_id),
            KEY open_call_term_id (open_call_term_id),
            KEY permission_type (permission_type),
            FOREIGN KEY (guest_curator_id) REFERENCES {$wpdb->prefix}cf7as_guest_curators(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if guest curators table exists
     */
    public static function guest_curators_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    /**
     * Check if curator notes table exists
     */
    public static function curator_notes_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_curator_notes';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    /**
     * Check if curator permissions table exists
     */
    public static function curator_permissions_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_curator_permissions';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    // ============================================================================
    // ADMIN INTERFACE SECTION
    // ============================================================================
    
    /**
     * Add guest curator management to admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=cf7_submission',
            __('Guest Curators', 'cf7-artist-submissions'),
            __('Guest Curators', 'cf7-artist-submissions'),
            'manage_options',
            'cf7-guest-curators',
            array(__CLASS__, 'render_admin_page')
        );
    }
    
    /**
     * Render the guest curator admin page
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cf7-artist-submissions'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Guest Curator Management', 'cf7-artist-submissions'); ?></h1>
            
            <div class="cf7-guest-curator-header">
                <p><?php _e('Manage guest curators who can access submissions without WordPress accounts. Guest curators can view, rate, and comment on submissions for specific open calls.', 'cf7-artist-submissions'); ?></p>
                <button type="button" id="cf7-add-guest-curator" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Guest Curator', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            
            <!-- Guest Curators List -->
            <div id="cf7-guest-curators-list" class="cf7-admin-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Email', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Status', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Open Calls', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Last Login', 'cf7-artist-submissions'); ?></th>
                            <th><?php _e('Actions', 'cf7-artist-submissions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="cf7-guest-curators-tbody">
                        <!-- Dynamic content loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <!-- Portal URL Display -->
            <div class="cf7-portal-info">
                <h3><?php _e('Guest Curator Portal', 'cf7-artist-submissions'); ?></h3>
                <p><?php _e('Share this URL with guest curators:', 'cf7-artist-submissions'); ?></p>
                <code id="cf7-portal-url"><?php echo esc_url(home_url('/curator-portal/')); ?></code>
                <button type="button" id="cf7-copy-portal-url" class="button"><?php _e('Copy URL', 'cf7-artist-submissions'); ?></button>
            </div>
        </div>
        
        <!-- Add/Edit Guest Curator Modal -->
        <div id="cf7-guest-curator-modal" class="cf7-modal" style="display: none;">
            <div class="cf7-modal-content">
                <div class="cf7-modal-header">
                    <h3 id="cf7-modal-title"><?php _e('Add Guest Curator', 'cf7-artist-submissions'); ?></h3>
                    <button type="button" class="cf7-modal-close">&times;</button>
                </div>
                <div class="cf7-modal-body">
                    <form id="cf7-guest-curator-form">
                        <input type="hidden" id="curator-id" name="curator_id">
                        
                        <div class="cf7-form-group">
                            <label for="curator-name"><?php _e('Curator Name', 'cf7-artist-submissions'); ?> *</label>
                            <input type="text" id="curator-name" name="name" required>
                        </div>
                        
                        <div class="cf7-form-group">
                            <label for="curator-email"><?php _e('Email Address', 'cf7-artist-submissions'); ?> *</label>
                            <input type="email" id="curator-email" name="email" required>
                        </div>
                        
                        <div class="cf7-form-group">
                            <label for="curator-status"><?php _e('Status', 'cf7-artist-submissions'); ?></label>
                            <select id="curator-status" name="status">
                                <option value="active"><?php _e('Active', 'cf7-artist-submissions'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'cf7-artist-submissions'); ?></option>
                            </select>
                        </div>
                        
                        <div class="cf7-form-group">
                            <label><?php _e('Open Call Permissions', 'cf7-artist-submissions'); ?></label>
                            <div id="cf7-open-call-permissions">
                                <!-- Dynamic content loaded via AJAX -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="cf7-modal-footer">
                    <button type="button" id="cf7-cancel-curator" class="button"><?php _e('Cancel', 'cf7-artist-submissions'); ?></button>
                    <button type="button" id="cf7-save-curator" class="button button-primary"><?php _e('Save Curator', 'cf7-artist-submissions'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cf7-guest-curators') === false) {
            return;
        }
        
        wp_enqueue_script(
            'cf7-guest-curators-admin',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/guest-curators-admin.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cf7-guest-curators-admin',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/guest-curators-admin.css',
            array(),
            CF7_ARTIST_SUBMISSIONS_VERSION
        );
        
        wp_localize_script('cf7-guest-curators-admin', 'cf7GuestCurators', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_guest_curators_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this guest curator?', 'cf7-artist-submissions'),
                'error_general' => __('An error occurred. Please try again.', 'cf7-artist-submissions'),
                'saving' => __('Saving...', 'cf7-artist-submissions'),
                'loading' => __('Loading...', 'cf7-artist-submissions'),
                'copied' => __('URL copied to clipboard!', 'cf7-artist-submissions'),
                'send_login_link' => __('Send Login Link', 'cf7-artist-submissions'),
            )
        ));
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler to add a guest curator
     */
    public static function ajax_add_guest_curator() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $status = sanitize_text_field($_POST['status']);
        $open_calls = isset($_POST['open_calls']) ? $_POST['open_calls'] : array();
        
        if (empty($name) || empty($email)) {
            wp_send_json_error('Name and email are required');
            return;
        }
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        // Check if email already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            wp_send_json_error('A curator with this email already exists');
            return;
        }
        
        // Insert the curator
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'status' => $status,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        $curator_id = $wpdb->insert_id;
        
        // Add open call permissions
        if (!empty($open_calls)) {
            self::update_curator_permissions($curator_id, $open_calls);
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'guest_curator_created',
                array(
                    'curator_id' => $curator_id,
                    'name' => $name,
                    'email' => $email,
                    'open_calls' => count($open_calls)
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Guest curator added successfully',
            'curator_id' => $curator_id
        ));
    }
    
    /**
     * AJAX handler to get guest curators list
     */
    public static function ajax_get_guest_curators() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $curators_table = $wpdb->prefix . 'cf7as_guest_curators';
        
        $curators = $wpdb->get_results("SELECT * FROM $curators_table ORDER BY created_at DESC");
        
        $formatted_curators = array();
        foreach ($curators as $curator) {
            $permissions = self::get_curator_permissions($curator->id);
            $open_calls = array();
            
            foreach ($permissions as $permission) {
                $term = get_term($permission->open_call_term_id);
                if ($term && !is_wp_error($term)) {
                    $open_calls[] = $term->name;
                }
            }
            
            $formatted_curators[] = array(
                'id' => $curator->id,
                'name' => $curator->name,
                'email' => $curator->email,
                'status' => $curator->status,
                'open_calls' => implode(', ', $open_calls),
                'open_calls_count' => count($open_calls),
                'last_login' => $curator->last_login ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($curator->last_login)) : __('Never', 'cf7-artist-submissions'),
                'created_at' => date_i18n(get_option('date_format'), strtotime($curator->created_at))
            );
        }
        
        wp_send_json_success($formatted_curators);
    }
    
    /**
     * AJAX handler to update a guest curator
     */
    public static function ajax_update_guest_curator() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $curator_id = intval($_POST['curator_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $status = sanitize_text_field($_POST['status']);
        $open_calls = isset($_POST['open_calls']) ? $_POST['open_calls'] : array();
        
        if (empty($name) || empty($email)) {
            wp_send_json_error('Name and email are required');
            return;
        }
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        // Check if curator exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $curator_id
        ));
        
        if (!$existing) {
            wp_send_json_error('Curator not found');
            return;
        }
        
        // Check if email is taken by another curator
        $email_taken = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s AND id != %d",
            $email, $curator_id
        ));
        
        if ($email_taken) {
            wp_send_json_error('Email address is already in use by another curator');
            return;
        }
        
        // Update the curator
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $curator_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        // Update open call permissions
        if (!empty($open_calls)) {
            self::update_curator_permissions($curator_id, $open_calls);
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'guest_curator_updated',
                array(
                    'curator_id' => $curator_id,
                    'name' => $name,
                    'email' => $email,
                    'open_calls' => count($open_calls)
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Guest curator updated successfully',
            'curator_id' => $curator_id
        ));
    }
    
    /**
     * AJAX handler to delete a guest curator
     */
    public static function ajax_delete_guest_curator() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $curator_id = intval($_POST['curator_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        // Get curator info for logging
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $curator_id
        ));
        
        if (!$curator) {
            wp_send_json_error('Curator not found');
            return;
        }
        
        // Delete the curator (permissions will be deleted via foreign key constraint)
        $result = $wpdb->delete($table_name, array('id' => $curator_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'guest_curator_deleted',
                array(
                    'curator_id' => $curator_id,
                    'name' => $curator->name,
                    'email' => $curator->email
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Guest curator deleted successfully'
        ));
    }
    
    /**
     * AJAX handler to get a single guest curator
     */
    public static function ajax_get_guest_curator() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $curator_id = intval($_POST['curator_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $curator_id
        ));
        
        if (!$curator) {
            wp_send_json_error('Curator not found');
            return;
        }
        
        wp_send_json_success(array(
            'id' => $curator->id,
            'name' => $curator->name,
            'email' => $curator->email,
            'status' => $curator->status
        ));
    }
    
    /**
     * AJAX handler to get open calls
     */
    public static function ajax_get_open_calls() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get all terms from open call taxonomy (assuming it exists)
        $terms = get_terms(array(
            'taxonomy' => 'open_call',
            'hide_empty' => false,
        ));
        
        $open_calls = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $open_calls[] = array(
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                );
            }
        }
        
        wp_send_json_success($open_calls);
    }
    
    /**
     * AJAX handler to get curator permissions
     */
    public static function ajax_get_curator_permissions() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $curator_id = intval($_POST['curator_id']);
        $permissions = self::get_curator_permissions($curator_id);
        
        $term_ids = array();
        foreach ($permissions as $permission) {
            $term_ids[] = $permission->open_call_term_id;
        }
        
        wp_send_json_success($term_ids);
    }
    
    /**
     * AJAX handler to send login link to curator
     */
    public static function ajax_send_curator_login_link() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_guest_curators_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $curator_id = intval($_POST['curator_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $curator_id
        ));
        
        if (!$curator) {
            wp_send_json_error('Curator not found');
            return;
        }
        
        $result = self::generate_login_token($curator->email);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Login link sent successfully',
            'login_url' => $result['login_url']
        ));
    }
    
    // ============================================================================
    // AUTHENTICATION SECTION
    // ============================================================================
    
    /**
     * Generate and send login token to guest curator
     */
    public static function generate_login_token($email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s AND status = 'active'",
            $email
        ));
        
        if (!$curator) {
            return new WP_Error('curator_not_found', 'Guest curator not found or inactive');
        }
        
        // Generate secure token
        $token = wp_generate_password(32, false);
        $expires = date('Y-m-d H:i:s', strtotime('+2 hours')); // Token valid for 2 hours
        
        // Update curator with token
        $result = $wpdb->update(
            $table_name,
            array(
                'login_token' => $token,
                'token_expires' => $expires
            ),
            array('id' => $curator->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('token_error', 'Failed to generate login token');
        }
        
        // Send email with login link
        $login_url = home_url('/curator-portal/' . $token . '/');
        $subject = sprintf(__('Access Link for %s Curator Portal', 'cf7-artist-submissions'), get_bloginfo('name'));
        // Prepare email content using template
        ob_start();
        
        $curator_name = $curator->name;
        $site_name = get_bloginfo('name');
        $login_link = $login_url; // Make sure template variable matches
        $expires_text = __('This link will expire in 2 hours for security reasons.', 'cf7-artist-submissions');
        
        $template_path = CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'templates/curator-login-email.php';
        if (file_exists($template_path)) {
            include $template_path;
            $message = ob_get_clean();
        } else {
            ob_end_clean();
            // Fallback message if template is missing
            $message = sprintf(
                __('Hello %s,

You have been granted access to review submissions. Click the link below to access the curator portal:

%s

This link will expire in 2 hours for security reasons.

If you did not request this access, please ignore this email.

Best regards,
%s Team', 'cf7-artist-submissions'),
                $curator->name,
                $login_url,
                get_bloginfo('name')
            );
            $message = wpautop($message);
        }
        
        // Create plain text version for better email client support
        $plain_text_message = sprintf(
            __('Hello %s,

You have been granted access to review artist submissions as a guest curator.

CURATOR PORTAL ACCESS:
%s

IMPORTANT:
- This link will expire in 2 hours for security reasons
- This link is unique to you and should not be shared
- Copy and paste the entire link into your web browser if it\'s not clickable

Once you access the portal, you will be able to:
- View assigned submissions
- Rate artwork using our 5-star system
- Add notes and comments
- Review high-resolution images and files

If you have any questions or need assistance, please don\'t hesitate to contact us.

Thank you for your participation as a guest curator.

---
%s Team
%s', 'cf7-artist-submissions'),
            $curator->name,
            $login_url,
            get_bloginfo('name'),
            home_url()
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email'),
            'X-Mailer: WordPress'
        );
        
        // Try sending HTML email first, fallback to plain text if it fails
        $mail_sent = wp_mail($curator->email, $subject, $message, $headers);
        
        // If HTML email failed, try plain text
        if (!$mail_sent) {
            $plain_headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                'Reply-To: ' . get_option('admin_email'),
                'X-Mailer: WordPress'
            );
            $mail_sent = wp_mail($curator->email, $subject, $plain_text_message, $plain_headers);
        }
        
        // Log email sending for debugging
        if (!$mail_sent) {
            error_log('CF7 Curator Portal: Failed to send login email to ' . $curator->email);
        }
        
        return array(
            'token' => $token,
            'expires' => $expires,
            'curator_id' => $curator->id,
            'login_url' => $login_url
        );
    }
    
    /**
     * Verify login token and create session
     */
    public static function verify_token($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE login_token = %s 
             AND token_expires > NOW() 
             AND status = 'active'",
            $token
        ));
        
        if (!$curator) {
            return new WP_Error('invalid_token', 'Invalid or expired token');
        }
        
        // Update last login
        $wpdb->update(
            $table_name,
            array(
                'last_login' => current_time('mysql'),
                'login_token' => null,
                'token_expires' => null
            ),
            array('id' => $curator->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        return $curator;
    }
    
    // ============================================================================
    // UTILITY METHODS
    // ============================================================================
    
    /**
     * Update curator permissions for open calls
     */
    private static function update_curator_permissions($curator_id, $open_calls) {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        
        // Remove existing permissions
        $wpdb->delete($permissions_table, array('guest_curator_id' => $curator_id), array('%d'));
        
        // Add new permissions
        foreach ($open_calls as $open_call_id) {
            $permissions = array('view', 'rate', 'comment'); // Full permissions by default
            
            foreach ($permissions as $permission) {
                $wpdb->insert(
                    $permissions_table,
                    array(
                        'guest_curator_id' => $curator_id,
                        'open_call_term_id' => intval($open_call_id),
                        'permission_type' => $permission,
                        'granted_at' => current_time('mysql'),
                        'granted_by' => get_current_user_id()
                    ),
                    array('%d', '%d', '%s', '%s', '%d')
                );
            }
        }
    }
    
    /**
     * Get curator permissions
     */
    private static function get_curator_permissions($curator_id) {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT open_call_term_id FROM $permissions_table WHERE guest_curator_id = %d",
            $curator_id
        ));
    }
    
    /**
     * Check if guest curator has permission for an open call
     */
    public static function has_permission($curator_id, $open_call_term_id, $permission_type = 'view') {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $permissions_table 
             WHERE guest_curator_id = %d 
             AND open_call_term_id = %d 
             AND permission_type = %s",
            $curator_id, $open_call_term_id, $permission_type
        ));
        
        return $count > 0;
    }
}
