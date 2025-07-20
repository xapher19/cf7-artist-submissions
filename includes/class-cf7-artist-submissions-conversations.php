<?php
/**
 * CF7 Artist Submissions - Conversation System
 * Handles two-way email conversations with artists
 */
class CF7_Artist_Submissions_Conversations {
    
    /**
     * Initialize the conversation system
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_conversation_meta_box'));
        add_action('wp_ajax_cf7_send_message', array(__CLASS__, 'ajax_send_message'));
        add_action('wp_ajax_cf7_fetch_messages', array(__CLASS__, 'ajax_fetch_messages'));
        add_action('wp_ajax_cf7_mark_messages_viewed', array(__CLASS__, 'ajax_mark_messages_viewed'));
        add_action('wp_ajax_cf7_get_unviewed_count', array(__CLASS__, 'ajax_get_unviewed_count'));
        add_action('wp_ajax_cf7_check_new_messages', array(__CLASS__, 'ajax_check_new_messages'));
        add_action('wp_ajax_cf7_test_imap', array(__CLASS__, 'ajax_test_imap'));
        add_action('wp_ajax_cf7_check_replies_manual', array(__CLASS__, 'ajax_check_replies_manual'));
        add_action('wp_ajax_cf7_debug_inbox', array(__CLASS__, 'ajax_debug_inbox'));
        add_action('wp_ajax_cf7_clear_debug_messages', array(__CLASS__, 'ajax_clear_debug_messages'));
        add_action('wp_ajax_cf7_migrate_tokens', array(__CLASS__, 'ajax_migrate_tokens'));
        add_action('wp_ajax_cf7_toggle_message_read', array(__CLASS__, 'ajax_toggle_message_read'));
        add_action('wp_ajax_cf7_clear_messages', array(__CLASS__, 'ajax_clear_messages'));
        add_action('wp_ajax_cf7_cleanup_imap_inbox', array(__CLASS__, 'ajax_cleanup_imap_inbox'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_footer', array(__CLASS__, 'add_context_menu_script'));
        
        // Ensure table is up to date
        add_action('admin_init', array(__CLASS__, 'maybe_update_table'));
        
        // Add custom cron schedule
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_schedules'));
        
        // Schedule IMAP checking
        add_action('cf7_check_email_replies', array(__CLASS__, 'check_email_replies'));
        if (!wp_next_scheduled('cf7_check_email_replies')) {
            wp_schedule_event(time(), 'every_5_minutes', 'cf7_check_email_replies');
        }
    }
    
    /**
     * Maybe update the conversations table
     */
    public static function maybe_update_table() {
        $version_option = 'cf7_conversations_table_version';
        $current_version = get_option($version_option, '1.0');
        $target_version = '1.2'; // Version with notification support
        
        if (version_compare($current_version, $target_version, '<')) {
            self::update_conversations_table();
            update_option($version_option, $target_version);
        }
    }
    
    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        $schedules['every_5_minutes'] = array(
            'interval' => 300, // 5 minutes in seconds
            'display' => __('Every 5 Minutes', 'cf7-artist-submissions')
        );
        return $schedules;
    }
    
    /**
     * Create conversations table
     */
    public static function create_conversations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Check if table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            // Table exists, check if we need to add new columns
            self::update_conversations_table();
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) unsigned NOT NULL,
            message_id varchar(255) NOT NULL,
            direction enum('outbound','inbound') NOT NULL,
            from_email varchar(255) NOT NULL,
            from_name varchar(255) NOT NULL,
            to_email varchar(255) NOT NULL,
            to_name varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            message_body longtext NOT NULL,
            reply_token varchar(32) NOT NULL,
            sent_at datetime NOT NULL,
            received_at datetime DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            read_status tinyint(1) DEFAULT 0,
            email_status enum('pending','sent','delivered','failed') DEFAULT 'pending',
            is_template tinyint(1) DEFAULT 0,
            template_id varchar(100) DEFAULT NULL,
            admin_viewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY reply_token (reply_token),
            KEY message_id (message_id),
            KEY direction (direction),
            KEY sent_at (sent_at),
            KEY is_template (is_template)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Update conversations table to add template columns and notification tracking
     */
    public static function update_conversations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Check if is_template column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'is_template'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_template tinyint(1) DEFAULT 0");
        }
        
        // Check if template_id column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'template_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN template_id varchar(100) DEFAULT NULL");
        }
        
        // Check if admin_viewed_at column exists (for notification system)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'admin_viewed_at'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN admin_viewed_at datetime DEFAULT NULL");
        }
        
        // Add index for is_template if it doesn't exist
        $index_exists = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'is_template'");
        if (empty($index_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX is_template (is_template)");
        }
        
        // Add index for admin_viewed_at if it doesn't exist
        $index_exists = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'admin_viewed_at'");
        if (empty($index_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX admin_viewed_at (admin_viewed_at)");
        }
    }
    
    /**
     * Generate or retrieve consistent reply token for a submission
     */
    public static function generate_reply_token($submission_id) {
        // Check if this submission already has a token
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Store debug info
        $debug_info = get_option('cf7_debug_messages', array());
        
        // Try to get existing token from any message for this submission
        $existing_token = $wpdb->get_var($wpdb->prepare(
            "SELECT reply_token FROM $table_name WHERE submission_id = %d LIMIT 1",
            $submission_id
        ));
        
        if ($existing_token) {
            // Use the existing token to maintain consistency
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'generate_reply_token',
                'submission_id' => $submission_id,
                'result' => 'Using existing token: ' . $existing_token
            );
            update_option('cf7_debug_messages', array_slice($debug_info, -50));
            return $existing_token;
        }
        
        // If no token exists, generate a new permanent one based on submission ID
        // This ensures the same submission always gets the same token
        $new_token = wp_hash('submission_reply_' . $submission_id, 'nonce');
        
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'generate_reply_token',
            'submission_id' => $submission_id,
            'result' => 'Generated new permanent token: ' . $new_token
        );
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        
        return $new_token;
    }
    
    /**
     * Migrate existing submissions to use consistent tokens
     */
    public static function migrate_to_consistent_tokens() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Store debug info
        $debug_info = get_option('cf7_debug_messages', array());
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'migrate_to_consistent_tokens',
            'message' => 'Starting token migration'
        );
        
        // Get all submissions that have multiple tokens
        $submissions_with_messages = $wpdb->get_results(
            "SELECT submission_id, COUNT(DISTINCT reply_token) as token_count 
             FROM $table_name 
             GROUP BY submission_id 
             HAVING token_count > 1"
        );
        
        foreach ($submissions_with_messages as $submission) {
            $submission_id = $submission->submission_id;
            
            // Generate the new consistent token for this submission
            $new_token = wp_hash('submission_reply_' . $submission_id, 'nonce');
            
            // Update all messages for this submission to use the new token
            $updated = $wpdb->update(
                $table_name,
                array('reply_token' => $new_token),
                array('submission_id' => $submission_id),
                array('%s'),
                array('%d')
            );
            
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'migrate_to_consistent_tokens',
                'submission_id' => $submission_id,
                'updated_records' => $updated,
                'new_token' => $new_token
            );
        }
        
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'migrate_to_consistent_tokens',
            'message' => 'Migration completed',
            'submissions_updated' => count($submissions_with_messages)
        );
        
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        
        return count($submissions_with_messages);
    }
    
    /**
     * Add conversation meta box to submission edit page
     */
    public static function add_conversation_meta_box() {
        add_meta_box(
            'cf7-conversation',
            __('Artist Conversation', 'cf7-artist-submissions'),
            array(__CLASS__, 'render_conversation_meta_box'),
            'cf7_submission',
            'normal',
            'high'
        );
    }
    
    /**
     * Render conversation meta box
     */
    public static function render_conversation_meta_box($post) {
        $submission_id = $post->ID;
        $messages = self::get_conversation_messages($submission_id);
        $artist_email = self::get_artist_email($submission_id);
        $unviewed_count = self::get_unviewed_message_count($submission_id);
        
        // Mark messages as viewed when the meta box is rendered (user is actively viewing)
        if ($unviewed_count > 0) {
            self::mark_messages_as_viewed($submission_id);
        }
        
        // Store debug info for admin interface instead of error_log
        $debug_info = get_option('cf7_debug_messages', array());
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'render_conversation_meta_box',
            'submission_id' => $submission_id,
            'message_count' => count($messages),
            'unviewed_count' => $unviewed_count,
            'artist_email' => $artist_email
        );
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        
        wp_nonce_field('cf7_conversation_nonce', 'cf7_conversation_nonce');
        
        ?>
        <div class="conversation-meta-box">
            <div class="thread-controls">
                <div class="last-checked">
                    <?php 
                    $last_checked = get_option('cf7_last_imap_check', '');
                    if ($last_checked) {
                        echo 'Last checked: ' . date('Y-m-d H:i:s', strtotime($last_checked));
                    } else {
                        echo 'Never checked for replies';
                    }
                    ?>
                </div>
                <button type="button" id="check-replies-manual" class="button button-manual-check" data-submission-id="<?php echo esc_attr($submission_id); ?>">
                    <?php _e('Check for New Replies', 'cf7-artist-submissions'); ?>
                </button>
            </div>
            
            <?php if (!empty($messages)): ?>
                <div class="conversation-status-summary">
                    <?php 
                    $total_messages = count($messages);
                    $inbound_messages = array_filter($messages, function($msg) { return $msg->direction === 'inbound'; });
                    $inbound_count = count($inbound_messages);
                    $read_count = count(array_filter($inbound_messages, function($msg) { return !empty($msg->admin_viewed_at); }));
                    $unread_count = $inbound_count - $read_count;
                    ?>
                    <div class="message-summary">
                        <div class="summary-stats">
                            <span class="summary-item">
                                <span class="dashicons dashicons-email"></span>
                                <?php printf(_n('%d message', '%d messages', $total_messages, 'cf7-artist-submissions'), $total_messages); ?>
                            </span>
                            <?php if ($inbound_count > 0): ?>
                                <span class="summary-item">
                                    <span class="dashicons dashicons-arrow-down-alt"></span>
                                    <?php printf(_n('%d received', '%d received', $inbound_count, 'cf7-artist-submissions'), $inbound_count); ?>
                                </span>
                                <?php if ($unread_count > 0): ?>
                                    <span class="summary-item unread-summary">
                                        <span class="dashicons dashicons-marker"></span>
                                        <?php printf(_n('%d unread', '%d unread', $unread_count, 'cf7-artist-submissions'), $unread_count); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="summary-item read-summary">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('All read', 'cf7-artist-submissions'); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($total_messages > 0): ?>
                            <div class="conversation-actions">
                                <button type="button" id="cf7-clear-messages-btn" class="button cf7-danger-button" data-submission-id="<?php echo esc_attr($submission_id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Clear Messages', 'cf7-artist-submissions'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="conversation-messages">
                <?php if (empty($messages)): ?>
                    <p class="no-messages"><?php _e('No messages yet. Start a conversation with the artist below.', 'cf7-artist-submissions'); ?></p>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="conversation-message <?php echo esc_attr($message->direction === 'outbound' ? 'outgoing' : 'incoming'); ?> <?php echo $message->is_template ? 'template-message' : ''; ?> <?php echo ($message->direction === 'inbound' && empty($message->admin_viewed_at)) ? 'unviewed' : ''; ?>" data-message-id="<?php echo esc_attr($message->id); ?>">
                            <div class="message-meta">
                                <span class="message-type"><?php echo $message->direction === 'outbound' ? 'Sent' : 'Received'; ?></span>
                                <?php if ($message->is_template): ?>
                                    <span class="template-badge"><?php _e('Template', 'cf7-artist-submissions'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($message->direction === 'inbound'): ?>
                                    <?php if (empty($message->admin_viewed_at)): ?>
                                        <span class="message-status-badge unread">
                                            <span class="dashicons dashicons-marker"></span>
                                            <?php _e('Unread', 'cf7-artist-submissions'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="message-status-badge read">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php _e('Read', 'cf7-artist-submissions'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <span class="message-date"><?php echo esc_html(human_time_diff(strtotime($message->sent_at), current_time('timestamp')) . ' ago'); ?></span>
                            </div>
                            <div class="message-bubble">
                                <div class="message-content"><?php echo esc_html(wp_strip_all_tags($message->message_body)); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="send-message-form">
                <h4><?php _e('Send Message to Artist', 'cf7-artist-submissions'); ?></h4>
                <?php if (empty($artist_email)): ?>
                    <p class="error"><?php _e('No artist email found for this submission.', 'cf7-artist-submissions'); ?></p>
                <?php else: ?>
                    <div class="compose-field">
                        <label for="message-to"><?php _e('To:', 'cf7-artist-submissions'); ?></label>
                        <input type="email" id="message-to" value="<?php echo esc_attr($artist_email); ?>" readonly>
                    </div>
                    
                    <div class="compose-field">
                        <label for="message-type"><?php _e('Message Type:', 'cf7-artist-submissions'); ?></label>
                        <select id="message-type">
                            <option value="custom"><?php _e('Custom Message', 'cf7-artist-submissions'); ?></option>
                            <?php 
                            // Get available email templates
                            $templates = get_option('cf7_artist_submissions_email_templates', array());
                            
                            // Define triggers (same as in emails class)
                            $triggers = array(
                                'submission_received' => array(
                                    'name' => __('Submission Received', 'cf7-artist-submissions'),
                                    'auto' => true,
                                ),
                                'status_changed_to_selected' => array(
                                    'name' => __('Status Changed to Selected', 'cf7-artist-submissions'),
                                    'auto' => false,
                                ),
                                'status_changed_to_reviewed' => array(
                                    'name' => __('Status Changed to Reviewed', 'cf7-artist-submissions'),
                                    'auto' => false,
                                ),
                                'status_changed_to_shortlisted' => array(
                                    'name' => __('Status Changed to Shortlisted', 'cf7-artist-submissions'),
                                    'auto' => false,
                                ),
                                'custom_notification' => array(
                                    'name' => __('Custom Notification', 'cf7-artist-submissions'),
                                    'auto' => false,
                                ),
                            );
                            
                            foreach ($triggers as $trigger_id => $trigger) {
                                $template = isset($templates[$trigger_id]) ? $templates[$trigger_id] : array('enabled' => false);
                                // Only show enabled templates
                                if (isset($template['enabled']) && $template['enabled']) {
                                    echo '<option value="' . esc_attr($trigger_id) . '">' . esc_html($trigger['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="compose-field" id="custom-message-field">
                        <label for="message-body"><?php _e('Message:', 'cf7-artist-submissions'); ?></label>
                        <textarea id="message-body" rows="6" placeholder="Type your message here..." class="widefat"></textarea>
                    </div>
                    
                    <div class="compose-field" id="template-preview-field" style="display: none;">
                        <label><?php _e('Template Preview:', 'cf7-artist-submissions'); ?></label>
                        <div id="template-preview-content" class="template-preview">
                            <div class="preview-subject"></div>
                            <div class="preview-body"></div>
                        </div>
                    </div>
                    
                    <div class="compose-actions">
                        <button type="button" id="send-message-btn" class="button button-primary">
                            <?php _e('Send Message', 'cf7-artist-submissions'); ?>
                        </button>
                        <span id="send-status" class="send-status"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Clear Messages Confirmation Modal -->
        <div id="cf7-clear-messages-modal" class="cf7-modal" style="display: none;">
            <div class="cf7-modal-content">
                <div class="cf7-modal-header">
                    <h3><?php _e('Clear All Messages', 'cf7-artist-submissions'); ?></h3>
                    <button type="button" class="cf7-modal-close">&times;</button>
                </div>
                <div class="cf7-modal-body">
                    <div class="cf7-warning-content">
                        <div class="cf7-warning-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <div class="cf7-warning-text">
                            <p><strong><?php _e('WARNING: This action is permanent and irreversible!', 'cf7-artist-submissions'); ?></strong></p>
                            <p><?php _e('This will permanently delete all conversation messages from the database for this artist. All sent and received messages will be completely removed for privacy compliance.', 'cf7-artist-submissions'); ?></p>
                            <p><em><?php _e('This action cannot be undone and messages cannot be recovered.', 'cf7-artist-submissions'); ?></em></p>
                            <p><?php _e('To confirm permanent deletion, please type "CLEAR" in the field below:', 'cf7-artist-submissions'); ?></p>
                            
                            <div class="cf7-form-group">
                                <input type="text" id="cf7-clear-confirmation" placeholder="<?php _e('Type CLEAR to confirm', 'cf7-artist-submissions'); ?>" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cf7-modal-footer">
                    <button type="button" id="cf7-clear-cancel" class="button"><?php _e('Cancel', 'cf7-artist-submissions'); ?></button>
                    <button type="button" id="cf7-clear-confirm" class="button cf7-danger-button" disabled><?php _e('Permanently Delete All Messages', 'cf7-artist-submissions'); ?></button>
                </div>
            </div>
        </div>
        
        <input type="hidden" id="submission-id" value="<?php echo esc_attr($submission_id); ?>">
        <input type="hidden" id="cf7-email-nonce" value="<?php echo wp_create_nonce('cf7_send_email_nonce'); ?>">
        <?php
    }
    
    /**
     * Get conversation messages for a submission
     */
    public static function get_conversation_messages($submission_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Store debug info for admin interface
        $debug_info = get_option('cf7_debug_messages', array());
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'get_conversation_messages',
            'submission_id' => $submission_id
        );
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'get_conversation_messages',
                'error' => 'Conversations table does not exist'
            );
            update_option('cf7_debug_messages', array_slice($debug_info, -50));
            return array();
        }
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE submission_id = %d ORDER BY sent_at ASC",
            $submission_id
        ));
        
        if ($wpdb->last_error) {
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'get_conversation_messages',
                'error' => 'Database error getting messages: ' . $wpdb->last_error
            );
            update_option('cf7_debug_messages', array_slice($debug_info, -50));
            return array();
        }
        
        $message_count = count($messages);
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'get_conversation_messages',
            'result' => 'Found ' . $message_count . ' messages for submission ' . $submission_id
        );
        
        if ($message_count > 0) {
            foreach ($messages as $message) {
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'get_conversation_messages',
                    'message_detail' => 'Message ID: ' . $message->id . ', Direction: ' . $message->direction . ', Date: ' . $message->sent_at
                );
            }
        }
        
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        return $messages;
    }
    
    /**
     * Get artist email from submission
     */
    public static function get_artist_email($submission_id) {
        // Try common email field names
        $email_fields = array('email', 'your-email', 'artist-email', 'contact-email');
        
        foreach ($email_fields as $field) {
            $email = get_post_meta($submission_id, 'cf7_' . $field, true);
            if (!empty($email) && is_email($email)) {
                return $email;
            }
        }
        
        // Fallback: check all meta fields for email-like values
        $meta_keys = get_post_custom_keys($submission_id);
        if ($meta_keys) {
            foreach ($meta_keys as $key) {
                if (strpos($key, 'cf7_') === 0) {
                    $email = get_post_meta($submission_id, $key, true);
                    if (!empty($email) && is_email($email)) {
                        return $email;
                    }
                }
            }
        }
        
        return '';
    }
    
    /**
     * Enqueue scripts for conversation interface
     * Note: Assets are now centrally managed by the Tabs system to prevent conflicts.
     * The Tabs system loads conversation.js, conversations.css, and provides
     * cf7Conversations localization data for all single submission edit pages.
     */
    public static function enqueue_scripts($hook) {
        // Assets are handled by the Tabs system for single submission pages
        // This prevents script/style conflicts and ensures consistent loading
    }
    
    /**
     * AJAX handler for sending messages
     */
    public static function ajax_send_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $to_email = sanitize_email($_POST['to_email']);
        $message_type = sanitize_text_field($_POST['message_type']);
        $message_body = isset($_POST['message_body']) ? wp_kses_post($_POST['message_body']) : '';
        
        // Handle template-based messages
        if ($message_type !== 'custom') {
            // Send using email template system
            $emails = new CF7_Artist_Submissions_Emails();
            $result = $emails->send_email($message_type, $submission_id, $to_email);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            
            // Don't store the message here - the email system already stores it in conversations
            // This prevents duplicate messages with different formatting
            wp_send_json_success(array('message' => 'Template email sent successfully'));
            return;
        }
        
        // Handle custom messages
        if (empty($message_body)) {
            wp_send_json_error(array('message' => 'Message body is required for custom messages'));
            return;
        }
        
        // Auto-generate subject line for custom messages
        $submission = get_post($submission_id);
        $submission_date = get_post_meta($submission_id, 'cf7_submission_date', true);
        if (empty($submission_date)) {
            $submission_date = $submission ? $submission->post_date : date('Y-m-d');
        }
        
        // Format date nicely
        $formatted_date = date('F j, Y', strtotime($submission_date));
        $subject = 'Re: Your Submission to Pup and Tiger on ' . $formatted_date;
        
        if (empty($submission_id) || empty($to_email) || empty($subject) || empty($message_body)) {
            wp_send_json_error(array('message' => 'Missing required fields'));
            return;
        }
        
        // Send the message
        $result = self::send_message($submission_id, $to_email, $subject, $message_body);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => 'Error: ' . $result->get_error_message(),
                'error_code' => $result->get_error_code(),
                'debug_data' => array(
                    'submission_id' => $submission_id,
                    'to_email' => $to_email,
                    'subject' => $subject,
                    'message_length' => strlen($message_body)
                )
            ));
        } else {
            wp_send_json_success(array('message' => 'Message sent successfully', 'message_id' => $result));
        }
    }
    
    /**
     * AJAX handler for fetching messages
     */
    public static function ajax_fetch_messages() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        if (empty($submission_id)) {
            wp_send_json_error(array('message' => 'Missing submission ID'));
            return;
        }
        
        $messages = self::get_conversation_messages($submission_id);
        
        ob_start();
        if (empty($messages)): ?>
            <p class="no-messages"><?php _e('No messages yet. Start a conversation with the artist below.', 'cf7-artist-submissions'); ?></p>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <div class="message-item <?php echo esc_attr($message->direction); ?>" data-message-id="<?php echo esc_attr($message->id); ?>">
                    <div class="message-header">
                        <span class="message-from"><?php echo esc_html($message->from_name); ?> &lt;<?php echo esc_html($message->from_email); ?>&gt;</span>
                        <span class="message-date"><?php echo esc_html(human_time_diff(strtotime($message->sent_at), current_time('timestamp'))); ?> ago</span>
                        <span class="message-direction <?php echo esc_attr($message->direction); ?>">
                            <?php echo $message->direction === 'outbound' ? '→' : '←'; ?>
                        </span>
                    </div>
                    <div class="message-subject">
                        <strong><?php echo esc_html($message->subject); ?></strong>
                    </div>
                    <div class="message-body">
                        <?php echo esc_html(wp_strip_all_tags($message->message_body)); ?>
                    </div>
                    <div class="message-status">
                        Status: <span class="status-<?php echo esc_attr($message->email_status); ?>"><?php echo esc_html(ucfirst($message->email_status)); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif;
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Send a message to the artist
     */
    public static function send_message($submission_id, $to_email, $subject, $message_body) {
        global $wpdb;
        
        // Ensure conversations table exists
        self::create_conversations_table();
        
        // Get current user info
        $current_user = wp_get_current_user();
        
        // Get email settings - prioritize configured settings over user display name
        $options = get_option('cf7_artist_submissions_email_options', array());
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        $from_name = isset($options['from_name']) && !empty($options['from_name']) ? $options['from_name'] : 
                    (!empty($current_user->display_name) ? $current_user->display_name : 'CF7 Artist Submissions');
        
        // Validate required fields
        if (empty($from_email) || empty($to_email) || empty($subject)) {
            return new WP_Error('validation_error', 'Missing required email fields: from=' . $from_email . ', to=' . $to_email . ', subject=' . $subject);
        }
        
        // Use WordPress default admin email as fallback if needed
        if (empty($from_email)) {
            $from_email = get_option('admin_email', 'noreply@' . $_SERVER['HTTP_HOST']);
        }
        
        // Generate reply token for conversation threading
        $reply_token = self::generate_reply_token($submission_id);
        
        // Get IMAP settings for reply-to address (this is the email we'll monitor for replies)
        $imap_options = get_option('cf7_artist_submissions_imap_options', array());
        $imap_email = isset($imap_options['username']) ? $imap_options['username'] : $from_email;
        
        // Create reply-to address using plus addressing with IMAP email for conversation threading
        // This uses your IMAP email address like: imap-email+SUB123_token@yourdomain.com
        $email_parts = explode('@', $imap_email);
        $local_part = $email_parts[0];
        $domain_part = isset($email_parts[1]) ? $email_parts[1] : 'example.com';
        $reply_to_email = $local_part . '+SUB' . $submission_id . '_' . $reply_token . '@' . $domain_part;
        
        // Get email settings and check if WooCommerce template should be used
        $email_options = get_option('cf7_artist_submissions_email_options', array());
        $use_wc_template = isset($email_options['use_wc_template']) && $email_options['use_wc_template'] && class_exists('WooCommerce');
        
        // Prepare message body
        $email_body = $message_body;
        $content_type = 'text/plain';
        
        if ($use_wc_template) {
            // Apply WooCommerce template to the message
            $email_body = self::format_woocommerce_conversation_email($message_body, $subject);
            $content_type = 'text/html';
        }
        
        // Use headers with reply-to for conversation threading
        $headers = array(
            'Content-Type: ' . $content_type . '; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: <' . $reply_to_email . '>'
        );
        
        // Log what we're trying to send for debugging
        error_log('CF7 Conversations: Attempting to send email');
        error_log('From: ' . $from_name . ' <' . $from_email . '>');
        error_log('To: ' . $to_email);
        error_log('Subject: ' . $subject);
        error_log('WooCommerce template: ' . ($use_wc_template ? 'Yes' : 'No'));
        error_log('Headers: ' . print_r($headers, true));
        
        $mail_sent = wp_mail($to_email, $subject, $email_body, $headers);
        
        // Enhanced error reporting
        if (!$mail_sent) {
            global $phpmailer;
            $error_details = array();
            $error_details[] = 'wp_mail returned false';
            
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_details[] = 'PHPMailer Error: ' . $phpmailer->ErrorInfo;
            }
            
            if (isset($phpmailer) && !empty($phpmailer->getLastMessageID())) {
                $error_details[] = 'Last Message ID: ' . $phpmailer->getLastMessageID();
            }
            
            $error_message = implode(' | ', $error_details);
            error_log('CF7 Conversations wp_mail failed: ' . $error_message);
            
            return new WP_Error('email_error', $error_message);
        }
        
        error_log('CF7 Conversations: Email sent successfully');
        
        // Message ID was already generated above with reply_token
        $message_id = '<cf7_' . $submission_id . '_' . uniqid() . '@wordpress.local>';
        
        // Store in database
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'message_id' => $message_id,
                'direction' => 'outbound',
                'from_email' => $from_email,
                'from_name' => $from_name,
                'to_email' => $to_email,
                'to_name' => '',
                'subject' => $subject,
                'message_body' => $message_body,
                'reply_token' => $reply_token,
                'sent_at' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'email_status' => $mail_sent ? 'sent' : 'failed'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            // Add debug information about the database error
            $error_message = 'Failed to save message to database';
            if ($wpdb->last_error) {
                $error_message .= ': ' . $wpdb->last_error;
            }
            return new WP_Error('db_error', $error_message);
        }
        
        if (!$mail_sent) {
            return new WP_Error('email_error', 'Failed to send email');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Check email replies with timeout protection
     */
    public static function check_email_replies_with_timeout($timeout_seconds = 30) {
        // Set a timeout for the operation
        $original_time_limit = ini_get('max_execution_time');
        set_time_limit($timeout_seconds + 10); // Give a little buffer
        
        $start_time = time();
        
        try {
            $result = self::check_email_replies();
            
            // Check if we're approaching timeout
            if (time() - $start_time > $timeout_seconds) {
                throw new Exception('Operation timed out after ' . $timeout_seconds . ' seconds');
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Reset time limit
            set_time_limit($original_time_limit);
            throw $e;
        } finally {
            // Always reset time limit
            set_time_limit($original_time_limit);
        }
    }
    
    /**
     * Check for email replies via IMAP
     */
    public static function check_email_replies() {
        $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
        
        // Enhanced logging for debugging
        error_log('CF7 Artist Submissions: Starting IMAP check');
        error_log('CF7 Artist Submissions: IMAP settings - ' . print_r($imap_settings, true));
        
        if (empty($imap_settings['server']) || empty($imap_settings['username']) || empty($imap_settings['password'])) {
            error_log('CF7 Artist Submissions: IMAP not configured - missing required settings');
            return false; // IMAP not configured
        }
        
        try {
            // Build connection string for folder listing
            $encryption = !empty($imap_settings['encryption']) ? '/' . $imap_settings['encryption'] : '/ssl';
            $port = !empty($imap_settings['port']) ? $imap_settings['port'] : 993;
            
            // Connect to server root to list folders (for Migadu plus addressing)
            $connection_string = '{' . $imap_settings['server'] . ':' . $port . '/imap' . $encryption . '}';
            
            error_log('CF7 Artist Submissions: Attempting IMAP connection to: ' . $connection_string);
            
            // Connect to IMAP server 
            $connection = imap_open(
                $connection_string,
                $imap_settings['username'],
                $imap_settings['password']
            );
            
            if (!$connection) {
                $error = imap_last_error();
                error_log('CF7 Artist Submissions: Failed to connect to IMAP server: ' . $error);
                return false;
            }
            
            error_log('CF7 Artist Submissions: IMAP connection successful');
            
            // Get list of all folders to find plus-tagged ones (Migadu stores them separately)
            $folders = @imap_list($connection, $connection_string, '*');
            $folders_to_check = array('INBOX'); // Always check INBOX first
            
            if ($folders) {
                error_log('CF7 Artist Submissions: Found ' . count($folders) . ' folders, looking for plus-tagged folders');
                foreach ($folders as $folder) {
                    $folder_name = str_replace($connection_string, '', $folder);
                    
                    // Skip INBOX since we already have it
                    if ($folder_name === 'INBOX') {
                        continue;
                    }
                    
                    // Look for folders that match our submission patterns (sub2803_token format)
                    if (preg_match('/^sub\d+_[a-f0-9]{32}$/i', $folder_name)) {
                        $folders_to_check[] = $folder_name;
                        error_log('CF7 Artist Submissions: Found submission folder: ' . $folder_name);
                    }
                }
            } else {
                error_log('CF7 Artist Submissions: Could not list folders, checking INBOX only');
            }
            
            $total_processed = 0;
            
            // Check each folder
            foreach ($folders_to_check as $folder_name) {
                error_log('CF7 Artist Submissions: Checking folder: ' . $folder_name);
                
                // Switch to the folder
                if ($folder_name !== 'INBOX') {
                    $reopen_result = imap_reopen($connection, $connection_string . $folder_name);
                    if (!$reopen_result) {
                        error_log('CF7 Artist Submissions: Failed to switch to folder: ' . $folder_name);
                        continue;
                    }
                }
                
                $processed = self::process_folder_emails($connection, $folder_name);
                $total_processed += $processed;
                
                if ($processed > 0) {
                    error_log('CF7 Artist Submissions: Processed ' . $processed . ' emails in folder: ' . $folder_name);
                }
            }
            
            error_log('CF7 Artist Submissions: Processed ' . $total_processed . ' emails total');
            
            // Expunge deleted emails to permanently remove them from the server (only if deletion is enabled)
            $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
            $delete_processed = isset($imap_settings['delete_processed']) ? $imap_settings['delete_processed'] : '1'; // Default to enabled
            
            if ($delete_processed === '1') {
                imap_expunge($connection);
                error_log('CF7 Artist Submissions: Expunged deleted emails from server');
            } else {
                error_log('CF7 Artist Submissions: Expunge skipped (delete_processed disabled)');
            }
            
            imap_close($connection);
            
            // Update last check time
            update_option('cf7_last_imap_check', current_time('mysql'));
            
            error_log('CF7 Artist Submissions: Processed ' . $total_processed . ' emails total');
            
            return $total_processed;
            
        } catch (Exception $e) {
            error_log('CF7 Artist Submissions: IMAP error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process emails in a specific folder
     */
    public static function process_folder_emails($connection, $folder_name) {
        // Get total message count
        $total_messages = imap_num_msg($connection);
        error_log('CF7 Artist Submissions: Folder "' . $folder_name . '" has ' . $total_messages . ' messages');
        
        if ($total_messages == 0) {
            return 0;
        }
        
        // Try to get unread emails first
        $emails = imap_search($connection, 'UNSEEN');
        
        if ($emails === false || empty($emails)) {
            error_log('CF7 Artist Submissions: No UNSEEN emails found in ' . $folder_name . ', checking recent emails');
            
            // Check last 50 emails as fallback
            $recent_count = min(50, $total_messages);
            if ($recent_count > 0) {
                $emails = range($total_messages - $recent_count + 1, $total_messages);
                error_log('CF7 Artist Submissions: Checking last ' . $recent_count . ' messages in ' . $folder_name);
            } else {
                return 0;
            }
        } else {
            error_log('CF7 Artist Submissions: Found ' . count($emails) . ' UNSEEN emails in ' . $folder_name);
        }
        
        $processed_count = 0;
        
        if ($emails && count($emails) > 0) {
            // Limit the number of emails to process per folder - reduced for speed
            $max_emails_per_folder = 20;
            $emails_to_process = array_slice($emails, 0, $max_emails_per_folder);
            
            foreach ($emails_to_process as $email_number) {
                if (self::process_incoming_email($connection, $email_number)) {
                    $processed_count++;
                }
            }
        } else {
            // No unread emails found
        }
        
        return $processed_count;
    }
    
    /**
     * Process an incoming email
     */
    public static function process_incoming_email($connection, $email_number) {
        $header = imap_headerinfo($connection, $email_number);
        
        // Get email body - try different parts
        $body = '';
        $structure = imap_fetchstructure($connection, $email_number);
        
        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart email
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                $part_number = $i + 1;
                
                // Look for text/plain first, then text/html
                if ($part->subtype == 'PLAIN' || ($part->subtype == 'HTML' && empty($body))) {
                    $body = imap_fetchbody($connection, $email_number, $part_number);
                    
                    // Decode if needed
                    if ($part->encoding == 3) { // base64
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) { // quoted-printable
                        $body = quoted_printable_decode($body);
                    }
                    
                    if ($part->subtype == 'PLAIN') {
                        break; // Prefer plain text
                    }
                }
            }
        } else {
            // Single part email
            $body = imap_fetchbody($connection, $email_number, 1);
            
            // Decode if needed
            if ($structure->encoding == 3) { // base64
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) { // quoted-printable
                $body = quoted_printable_decode($body);
            }
        }
        
        // Convert to UTF-8 if needed
        if (function_exists('imap_utf8')) {
            $body = imap_utf8($body);
        }
        
        // Parse the To address to extract submission ID and token
        $to_address = '';
        if (isset($header->to) && is_array($header->to) && count($header->to) > 0) {
            $to_address = $header->to[0]->mailbox . '@' . $header->to[0]->host;
        }
        
        error_log('CF7 Artist Submissions: Processing email TO: ' . $to_address);
        
        // Simple pattern to match plus addressing: your-email+SUB123_token@domain.com
        if (preg_match('/\+SUB(\d+)_([a-f0-9]{32})@/', $to_address, $matches)) {
            $submission_id = intval($matches[1]);
            $reply_token = $matches[2];
            
            error_log('CF7 Artist Submissions: Pattern matched - Submission ID: ' . $submission_id . ', Token: ' . $reply_token);
            
            // Check if this email has already been processed using message ID
            $message_id = isset($header->message_id) ? $header->message_id : '';
            if (!empty($message_id) && self::message_already_processed($message_id)) {
                error_log('CF7 Artist Submissions: Message already processed: ' . $message_id);
                return false;
            }
            
            // Verify token is valid for this submission
            error_log('CF7 Artist Submissions: Verifying token for submission ' . $submission_id);
            if (self::verify_reply_token($submission_id, $reply_token)) {
                // Store the incoming message
                $stored = self::store_incoming_message($submission_id, $header, $body, $reply_token);
                
                if ($stored) {
                    // Mark email as seen first
                    imap_setflag_full($connection, $email_number, "\\Seen");
                    
                    // Check if we should delete processed emails from server
                    $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
                    $delete_processed = isset($imap_settings['delete_processed']) ? $imap_settings['delete_processed'] : '1'; // Default to enabled
                    
                    if ($delete_processed === '1') {
                        // Delete email from server to prevent reprocessing after database clearing
                        imap_delete($connection, $email_number);
                        error_log('CF7 Artist Submissions: Email deleted from server to prevent reprocessing');
                    } else {
                        error_log('CF7 Artist Submissions: Email kept on server (delete_processed disabled)');
                    }
                    
                    error_log('CF7 Artist Submissions: Successfully processed and stored message for submission: ' . $submission_id);
                    return true;
                } else {
                    error_log('CF7 Artist Submissions: Failed to store message for submission: ' . $submission_id);
                    return false;
                }
            } else {
                error_log('CF7 Artist Submissions: Invalid reply token for submission: ' . $submission_id);
            }
        } else {
            error_log('CF7 Artist Submissions: Email address pattern did not match: ' . $to_address);
        }
        
        return false;
    }
    
    /**
     * Debug method to check database status
     */
    public static function debug_database_status() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        $debug_info = array();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $debug_info['table_exists'] = $table_exists;
        $debug_info['table_name'] = $table_name;
        
        if (!$table_exists) {
            $debug_info['error'] = 'Conversations table does not exist';
            return $debug_info;
        }
        
        // Count total messages
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $debug_info['total_messages'] = $total_count;
        
        // Get recent messages
        $recent_messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 5");
        $debug_info['recent_messages'] = $recent_messages;
        
        // Check for any database errors
        if ($wpdb->last_error) {
            $debug_info['db_error'] = $wpdb->last_error;
        }
        
        return $debug_info;
    }

    /**
     * Verify reply token is valid
     */
    public static function verify_reply_token($submission_id, $token) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Store debug info for admin interface
        $debug_info = get_option('cf7_debug_messages', array());
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'verify_reply_token',
            'submission_id' => $submission_id,
            'token' => $token
        );
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'verify_reply_token',
                'error' => 'Conversations table does not exist'
            );
            update_option('cf7_debug_messages', array_slice($debug_info, -50));
            return false;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE submission_id = %d AND reply_token = %s",
            $submission_id,
            $token
        ));
        
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'verify_reply_token',
            'result' => 'Token verification - found ' . $count . ' matching records'
        );
        
        if ($count == 0) {
            // Debug: Show all tokens for this submission
            $existing_tokens = $wpdb->get_results($wpdb->prepare(
                "SELECT id, reply_token, direction, sent_at FROM $table_name WHERE submission_id = %d ORDER BY sent_at DESC LIMIT 5",
                $submission_id
            ));
            
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'verify_reply_token',
                'debug' => 'Existing tokens for submission ' . $submission_id . ':'
            );
            
            if ($existing_tokens) {
                foreach ($existing_tokens as $token_record) {
                    $debug_info[] = array(
                        'timestamp' => current_time('mysql'),
                        'action' => 'verify_reply_token',
                        'token_detail' => 'ID: ' . $token_record->id . ', Token: ' . $token_record->reply_token . ', Direction: ' . $token_record->direction . ', Date: ' . $token_record->sent_at
                    );
                }
            } else {
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'verify_reply_token',
                    'debug' => 'No existing records found for this submission'
                );
            }
        }
        
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        return $count > 0;
    }
    
    /**
     * Store conversation message
     */
    public static function store_conversation_message($submission_id, $direction, $from_email, $from_name, $to_email, $to_name, $subject, $message_body, $is_template = false, $template_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        $reply_token = self::generate_reply_token($submission_id);
        
        // Generate a unique message ID
        $message_id = wp_generate_uuid4();
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'message_id' => $message_id,
                'direction' => $direction,
                'from_email' => $from_email,
                'from_name' => $from_name,
                'to_email' => $to_email,
                'to_name' => $to_name,
                'subject' => $subject,
                'message_body' => $message_body,
                'reply_token' => $reply_token,
                'sent_at' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'email_status' => 'sent',
                'is_template' => $is_template ? 1 : 0,
                'template_id' => $template_id
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s'
            )
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to store conversation message');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Store incoming message
     */
    public static function store_incoming_message($submission_id, $header, $body, $reply_token) {
        global $wpdb;
        
        $from_email = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $from_name = isset($header->from[0]->personal) ? $header->from[0]->personal : $from_email;
        $subject = isset($header->subject) ? $header->subject : '';
        $message_id = isset($header->message_id) ? $header->message_id : '';
        
        // Decode body if needed
        if (function_exists('imap_utf8')) {
            $body = imap_utf8($body);
        }
        
        // Clean up the body - remove excessive whitespace and line breaks
        $body = trim($body);
        
        // Strip previous message content (email reply threading)
        $body = self::strip_previous_message_content($body);
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Check for duplicate message_id to prevent storing the same email twice
        if (!empty($message_id)) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE message_id = %s",
                $message_id
            ));
            
            if ($existing) {
                // Store debug info for admin interface
                $debug_info = get_option('cf7_debug_messages', array());
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'result' => 'DUPLICATE - Message ID already exists: ' . $message_id,
                    'existing_id' => $existing
                );
                update_option('cf7_debug_messages', array_slice($debug_info, -50));
                return false; // Don't store duplicate
            }
        } else {
            // Fallback duplicate check for emails without message_id
            // Check for duplicate based on submission_id, from_email, subject, and message content hash
            $content_hash = md5($body);
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE submission_id = %d AND from_email = %s AND subject = %s AND MD5(message_body) = %s AND direction = 'inbound' AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $submission_id,
                $from_email,
                $subject,
                $content_hash
            ));
            
            if ($existing) {
                // Store debug info for admin interface
                $debug_info = get_option('cf7_debug_messages', array());
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'result' => 'DUPLICATE - Content hash match found (no message_id): ' . $content_hash,
                    'existing_id' => $existing
                );
                update_option('cf7_debug_messages', array_slice($debug_info, -50));
                return false; // Don't store duplicate
            }
        }
        
        // Store debug info in option instead of error_log for live environments
        $debug_info = get_option('cf7_debug_messages', array());
        $debug_info[] = array(
            'timestamp' => current_time('mysql'),
            'action' => 'store_incoming_message',
            'submission_id' => $submission_id,
            'from_email' => $from_email,
            'from_name' => $from_name,
            'subject' => substr($subject, 0, 50),
            'body_length' => strlen($body),
            'reply_token' => $reply_token,
            'message_id' => $message_id
        );
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'store_incoming_message',
                'message' => 'Conversations table does not exist, creating it'
            );
            self::create_conversations_table();
        }
        
        // Normalize email date to server timezone
        $sent_at = current_time('mysql'); // Default to now
        if (isset($header->date)) {
            try {
                // Parse email date and convert to server timezone
                $email_date = new DateTime($header->date);
                $server_timezone = new DateTimeZone(wp_timezone_string());
                $email_date->setTimezone($server_timezone);
                $sent_at = $email_date->format('Y-m-d H:i:s');
                
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'timezone_conversion' => 'Original: ' . $header->date . ' -> Server time: ' . $sent_at
                );
            } catch (Exception $e) {
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'timezone_error' => 'Failed to parse date: ' . $e->getMessage() . ', using current time'
                );
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'submission_id' => $submission_id,
                'message_id' => $message_id,
                'direction' => 'inbound',
                'from_email' => $from_email,
                'from_name' => $from_name,
                'to_email' => '',
                'to_name' => '',
                'subject' => $subject,
                'message_body' => $body,
                'reply_token' => $reply_token,
                'sent_at' => $sent_at,
                'received_at' => current_time('mysql'),
                'read_status' => 0,
                'email_status' => 'delivered'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'store_incoming_message',
                'error' => 'Database insert failed: ' . $wpdb->last_error,
                'query' => $wpdb->last_query
            );
        } else {
            $inserted_id = $wpdb->insert_id;
            $debug_info[] = array(
                'timestamp' => current_time('mysql'),
                'action' => 'store_incoming_message',
                'success' => 'Message stored successfully with ID: ' . $inserted_id
            );
            
            // Verify the message was stored by checking if we can retrieve it
            $check_query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $inserted_id
            );
            $stored_message = $wpdb->get_row($check_query);
            
            if ($stored_message) {
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'verification' => 'Verification successful - message is in database'
                );
            } else {
                $debug_info[] = array(
                    'timestamp' => current_time('mysql'),
                    'action' => 'store_incoming_message',
                    'error' => 'Verification failed - message not found in database'
                );
            }
        }
        
        // Keep only last 50 debug messages
        update_option('cf7_debug_messages', array_slice($debug_info, -50));
        
        return $result;
    }
    
    /**
     * Strip previous message content from email replies
     * This removes quoted text, forwarded content, and email signatures
     */
    public static function strip_previous_message_content($body) {
        // Remove carriage returns first
        $body = str_replace("\r", "", $body);
        
        // Split content by lines for more precise processing
        $lines = explode("\n", $body);
        $cleaned_lines = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines at the start
            if (empty($line) && empty($cleaned_lines)) {
                continue;
            }
            
            // Check for quoted lines (Gmail, Outlook, etc.) - these start with > or &gt;
            if (preg_match('/^(>|&gt;)/', $line)) {
                // This is a quoted line, stop processing
                break;
            }
            
            // Check for unquoted reply headers that appear at the beginning of quoted sections
            // Only break if we haven't collected any content yet (meaning this is at the start)
            if (empty($cleaned_lines) && preg_match('/^(On .* wrote:|From:|Sent:|To:|Subject:|Date:|CC:|BCC:)/i', $line)) {
                break;
            }
            
            // Check for Outlook-style separators (usually appear before quoted content)
            if (preg_match('/^(-----Original Message-----|________________________________|_________|========)/', $line)) {
                break;
            }
            
            // Check for forwarded message indicators
            if (preg_match('/^(Begin forwarded message:|Forwarded message|---------- Forwarded message)/i', $line)) {
                break;
            }
            
            // Check for Gmail/Apple Mail reply headers (usually appear before quoted content)
            if (preg_match('/^(Le .* a écrit|Am .* schrieb|Il .* ha scritto)/i', $line)) {
                break;
            }
            
            // Check for mobile signatures and common footers
            if (preg_match('/^(Sent from my|Get Outlook for|Sent via|Sent using)/i', $line)) {
                break;
            }
            
            // Check for signature patterns but be more selective
            if (preg_match('/^(Best regards|Kind regards|Regards|Thanks|Thank you|Cheers|Sincerely|Best|Yours),?\s*$/i', $line)) {
                // Include the signature line but stop after it
                $cleaned_lines[] = $line;
                break;
            }
            
            // Check for email footer patterns
            if (preg_match('/^(This email|This message|Please consider|Confidentiality|CONFIDENTIAL)/i', $line)) {
                break;
            }
            
            // Check for unsubscribe/footer links
            if (preg_match('/^(To unsubscribe|Click here|Visit us at|Follow us)/i', $line)) {
                break;
            }
            
            $cleaned_lines[] = $line;
        }
        
        // Join the cleaned lines
        $cleaned_body = implode("\n", $cleaned_lines);
        
        // Remove excessive whitespace
        $cleaned_body = preg_replace('/\n{3,}/', "\n\n", $cleaned_body);
        $cleaned_body = trim($cleaned_body);
        
        // Final trim
        $cleaned_body = trim($cleaned_body);
        
        // If we removed too much content (less than 5 characters), return original
        if (strlen($cleaned_body) < 5 && strlen($body) > 20) {
            return $body;
        }
        
        return $cleaned_body;
    }
    
    /**
     * AJAX handler for clearing debug messages
     */
    public static function ajax_clear_debug_messages() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        delete_option('cf7_debug_messages');
        wp_send_json_success(array('message' => 'Debug messages cleared'));
    }
    
    /**
     * AJAX handler for migrating tokens
     */
    public static function ajax_migrate_tokens() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $updated_count = self::migrate_to_consistent_tokens();
        wp_send_json_success(array(
            'message' => 'Token migration completed successfully',
            'submissions_updated' => $updated_count
        ));
    }
    
    /**
     * AJAX handler for testing IMAP connection
     */
    public static function ajax_test_imap() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $options = get_option('cf7_artist_submissions_imap_options', array());
        
        if (empty($options['server']) || empty($options['username']) || empty($options['password'])) {
            wp_send_json_error(array('message' => 'IMAP settings incomplete. Please fill in all required fields.'));
            return;
        }
        
        // Test IMAP connection
        $server = $options['server'];
        $port = $options['port'];
        $username = $options['username'];
        $password = $options['password'];
        $encryption = $options['encryption'];
        
        // Build connection string
        $connection_string = '{' . $server . ':' . $port;
        if ($encryption === 'ssl') {
            $connection_string .= '/ssl';
        } elseif ($encryption === 'tls') {
            $connection_string .= '/tls';
        }
        $connection_string .= '}INBOX';
        
        try {
            if (!function_exists('imap_open')) {
                wp_send_json_error(array('message' => 'IMAP extension not available. Please enable PHP IMAP extension.'));
                return;
            }
            
            // Try to connect
            $connection = @imap_open($connection_string, $username, $password);
            
            if ($connection === false) {
                $error = imap_last_error();
                wp_send_json_error(array('message' => 'IMAP connection failed: ' . $error));
                return;
            }
            
            // Test successful - get mailbox info
            $mailbox_info = imap_status($connection, $connection_string, SA_ALL);
            imap_close($connection);
            
            wp_send_json_success(array(
                'message' => 'IMAP connection successful!',
                'details' => array(
                    'messages' => $mailbox_info->messages,
                    'recent' => $mailbox_info->recent,
                    'unseen' => $mailbox_info->unseen
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'IMAP test failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for manual email reply checking
     */
    public static function ajax_check_replies_manual() {
        error_log('CF7 Artist Submissions: ajax_check_replies_manual called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            error_log('CF7 Artist Submissions: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get submission ID if provided (for specific submission checking)
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : null;
        
        // Set a reasonable timeout for the AJAX request
        set_time_limit(30); // 30 seconds max (reduced from 60)
        
        try {
            // Store start time for debugging
            $start_time = microtime(true);
            
            // Run the email check with timeout protection
            $result = self::check_email_replies_with_timeout(20); // 20 second timeout (reduced from 30)
            
            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);
            
            if ($result === false) {
                wp_send_json_error(array(
                    'message' => 'IMAP not configured or connection failed',
                    'duration' => $duration . ' seconds'
                ));
                return;
            }
            
            // If checking for a specific submission, get updated messages
            $response_data = array(
                'message' => 'Successfully checked for new replies (' . $result . ' processed)',
                'checked_at' => current_time('mysql'),
                'duration' => $duration . ' seconds',
                'processed_count' => $result
            );
            
            if ($submission_id) {
                $messages = self::get_conversation_messages($submission_id);
                $response_data['messages'] = self::format_messages_for_ajax($messages);
                $response_data['submission_id'] = $submission_id;
            }
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error checking replies: ' . $e->getMessage(),
                'duration' => isset($duration) ? $duration . ' seconds' : 'unknown'
            ));
        }
    }
    
    /**
     * AJAX handler for debugging inbox contents
     */
    public static function ajax_debug_inbox() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
        
        if (empty($imap_settings['server']) || empty($imap_settings['username']) || empty($imap_settings['password'])) {
            wp_send_json_error(array('message' => 'IMAP not configured'));
            return;
        }
        
        try {
            // Build connection string
            $encryption = !empty($imap_settings['encryption']) ? '/' . $imap_settings['encryption'] : '/ssl';
            $port = !empty($imap_settings['port']) ? $imap_settings['port'] : 993;
            $connection_string = '{' . $imap_settings['server'] . ':' . $port . '/imap' . $encryption . '}';
            
            // Connect to IMAP server
            $connection = imap_open(
                $connection_string,
                $imap_settings['username'],
                $imap_settings['password']
            );
            
            if (!$connection) {
                wp_send_json_error(array('message' => 'IMAP connection failed'));
                return;
            }
            
            $debug_info = array();
            
            // Get list of all folders/mailboxes
            $folders = imap_list($connection, $connection_string, '*');
            $debug_info[] = 'Available folders:';
            
            if ($folders) {
                foreach ($folders as $folder) {
                    $folder_name = str_replace($connection_string, '', $folder);
                    $debug_info[] = '  - ' . $folder_name;
                }
            } else {
                $debug_info[] = '  No folders found';
            }
            
            $debug_info[] = '';
            
            // Check INBOX and any plus-addressing folders
            $folders_to_check = array('INBOX');
            if ($folders) {
                foreach ($folders as $folder) {
                    $folder_name = str_replace($connection_string, '', $folder);
                    if (preg_match('/(plus|tag|label|filter|sub|SUB)/i', $folder_name) || 
                        strpos($folder_name, '+') !== false) {
                        $folders_to_check[] = $folder_name;
                    }
                }
            }
            
            foreach ($folders_to_check as $folder_name) {
                $debug_info[] = 'Checking folder: ' . $folder_name;
                
                // Open specific folder
                $folder_connection = imap_open(
                    $connection_string . $folder_name,
                    $imap_settings['username'],
                    $imap_settings['password']
                );
                
                if (!$folder_connection) {
                    $debug_info[] = '  Failed to open folder';
                    continue;
                }
                
                $total_messages = imap_num_msg($folder_connection);
                $debug_info[] = '  Total messages: ' . $total_messages;
                
                if ($total_messages > 0) {
                    $recent_count = min(5, $total_messages);
                    $emails_info = array();
                    
                    for ($i = $total_messages; $i > ($total_messages - $recent_count); $i--) {
                        $header = imap_headerinfo($folder_connection, $i);
                        $flags = imap_fetch_overview($folder_connection, $i);
                        
                        $email_info = array();
                        $email_info['number'] = $i;
                        $email_info['subject'] = isset($header->subject) ? $header->subject : 'No subject';
                        $email_info['from'] = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : 'Unknown';
                        $email_info['date'] = isset($header->date) ? $header->date : 'Unknown date';
                        $email_info['seen'] = (isset($flags[0]) && isset($flags[0]->seen)) ? ($flags[0]->seen ? 'Yes' : 'No') : 'Unknown';
                        
                        // Get all To addresses
                        $to_addresses = array();
                        if (isset($header->to) && is_array($header->to)) {
                            foreach ($header->to as $to) {
                                $to_addresses[] = $to->mailbox . '@' . $to->host;
                            }
                        }
                        $email_info['to'] = implode(', ', $to_addresses);
                        
                        // Check if any To address matches our pattern
                        $matches_pattern = false;
                        foreach ($to_addresses as $to_addr) {
                            if (preg_match('/([^+]+)\+SUB(\d+)_([a-f0-9]{32})@/', $to_addr)) {
                                $matches_pattern = true;
                                break;
                            }
                        }
                        $email_info['matches_pattern'] = $matches_pattern ? 'Yes' : 'No';
                        
                        $emails_info[] = $email_info;
                    }
                    
                    $debug_info[] = '  Recent emails:';
                    foreach ($emails_info as $email) {
                        $debug_info[] = sprintf(
                            '    #%d | %s | From: %s | To: %s | Seen: %s | Matches: %s | Subject: %s',
                            $email['number'],
                            $email['date'],
                            $email['from'],
                            $email['to'],
                            $email['seen'],
                            $email['matches_pattern'],
                            $email['subject']
                        );
                    }
                } else {
                    $debug_info[] = '  No messages in this folder';
                }
                
                $debug_info[] = '';
                imap_close($folder_connection);
            }
            
            imap_close($connection);
            
            wp_send_json_success(array(
                'debug_info' => $debug_info,
                'folders_checked' => count($folders_to_check)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Debug failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * Format messages for AJAX response
     */
    private static function format_messages_for_ajax($messages) {
        $formatted = array();
        
        foreach ($messages as $message) {
            $formatted[] = array(
                'id' => $message->id,
                'direction' => $message->direction,
                'type' => $message->direction === 'outbound' ? 'outgoing' : 'incoming',
                'message' => $message->message_body,
                'message_body' => $message->message_body,
                'sent_at' => $message->sent_at,
                'human_time_diff' => human_time_diff(strtotime($message->sent_at), current_time('timestamp')) . ' ago',
                'from_name' => $message->from_name,
                'from_email' => $message->from_email,
                'subject' => $message->subject,
                'is_template' => (bool) $message->is_template,
                'admin_viewed_at' => $message->admin_viewed_at,
                'template_id' => $message->template_id
            );
        }
        
        return $formatted;
    }
    
    /**
     * Check if a message with the given message ID has already been processed
     */
    private static function message_already_processed($message_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE message_id = %s",
            $message_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Format message content using WooCommerce email template
     */
    public static function format_woocommerce_conversation_email($content, $heading) {
        if (!class_exists('WooCommerce')) {
            return $content;
        }
        
        try {
            // Make sure WooCommerce is properly initialized
            require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
            require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
            
            $wc_emails = new WC_Emails();
            $wc_emails->init();
            
            // Create a generic WC_Email object to access template methods
            $email = new WC_Email();
            
            // Get the email template content
            ob_start();
            
            // Include the WooCommerce email header
            wc_get_template('emails/email-header.php', array('email_heading' => $heading));
            
            // Add our content with proper formatting
            echo wpautop($content);
            
            // Include the WooCommerce email footer
            wc_get_template('emails/email-footer.php');
            
            // Get the complete email template
            $formatted_email = ob_get_clean();
            
            // Apply WooCommerce inline styles
            $formatted_email = $email->style_inline($formatted_email);
            
            return $formatted_email;
        } catch (Exception $e) {
            // If WooCommerce template fails, fall back to plain text
            error_log('CF7 Conversations: WooCommerce template error: ' . $e->getMessage());
            return $content;
        }
    }
    
    /**
     * Mark messages as viewed by admin
     */
    public static function mark_messages_as_viewed($submission_id, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Mark all unviewed inbound messages for this submission as viewed
        $result = $wpdb->update(
            $table_name,
            array('admin_viewed_at' => current_time('mysql')),
            array(
                'submission_id' => $submission_id,
                'direction' => 'inbound',
                'admin_viewed_at' => null
            ),
            array('%s'),
            array('%d', '%s', '%s')
        );
        
        return $result;
    }
    
    /**
     * Get count of unviewed messages for a submission
     */
    public static function get_unviewed_message_count($submission_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE submission_id = %d 
             AND direction = 'inbound' 
             AND admin_viewed_at IS NULL",
            $submission_id
        ));
        
        return intval($count);
    }
    
    /**
     * Get total unviewed messages across all submissions
     */
    public static function get_total_unviewed_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE direction = 'inbound' 
             AND admin_viewed_at IS NULL"
        );
        
        return intval($count);
    }
    
    /**
     * Get submissions with unviewed messages
     */
    public static function get_submissions_with_unviewed_messages() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        $results = $wpdb->get_results(
            "SELECT submission_id, COUNT(*) as unviewed_count,
                    MAX(COALESCE(received_at, sent_at)) as latest_message_date
             FROM $table_name 
             WHERE direction = 'inbound' 
             AND admin_viewed_at IS NULL
             GROUP BY submission_id
             ORDER BY latest_message_date DESC"
        );
        
        return $results;
    }
    
    /**
     * AJAX handler for marking messages as viewed
     */
    public static function ajax_mark_messages_viewed() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        if (empty($submission_id)) {
            wp_send_json_error(array('message' => 'Missing submission ID'));
            return;
        }
        
        $result = self::mark_messages_as_viewed($submission_id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Messages marked as viewed',
                'marked_count' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to mark messages as viewed'));
        }
    }
    
    /**
     * AJAX handler for getting unviewed message count
     */
    public static function ajax_get_unviewed_count() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        if ($submission_id) {
            $count = self::get_unviewed_message_count($submission_id);
        } else {
            $count = self::get_total_unviewed_count();
        }
        
        wp_send_json_success(array(
            'unviewed_count' => $count
        ));
    }
    
    /**
     * AJAX handler to check for new messages
     */
    public static function ajax_check_new_messages() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
            return;
        }
        
        // For now, just return that there are no new messages
        // This can be enhanced later to actually check for new messages
        // by comparing last check time with message timestamps
        wp_send_json_success(array(
            'hasNewMessages' => false,
            'message' => 'No new messages'
        ));
    }
    
    /**
     * AJAX handler to toggle message read/unread status
     */
    public static function ajax_toggle_message_read() {
        check_ajax_referer('cf7_conversation_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $message_id = intval($_POST['message_id']);
        $current_status = sanitize_text_field($_POST['current_status']);
        
        if (!$message_id) {
            wp_send_json_error('Invalid message ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Toggle the viewed status
        $new_status = ($current_status === 'read') ? 0 : 1;
        
        $result = $wpdb->update(
            $table_name,
            array('viewed' => $new_status),
            array('id' => $message_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'new_status' => $new_status ? 'read' : 'unread',
                'message' => $new_status ? __('Message marked as read', 'cf7-artist-submissions') : __('Message marked as unread', 'cf7-artist-submissions')
            ));
        } else {
            wp_send_json_error('Failed to update message status');
        }
    }
    
    /**
     * AJAX handler to clear all messages for a submission
     */
    public static function ajax_clear_messages() {
        check_ajax_referer('cf7_conversation_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_conversations';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error(array('message' => 'Conversations table does not exist'));
        }
        
        // Begin transaction for data integrity
        $wpdb->query('START TRANSACTION');
        
        try {
            // First, get the count of messages that will be deleted for logging
            $message_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE submission_id = %d",
                $submission_id
            ));
            
            if ($message_count == 0) {
                $wpdb->query('ROLLBACK');
                wp_send_json_success(array(
                    'message' => 'No messages found to delete',
                    'deleted_count' => 0
                ));
                return;
            }
            
            // Delete all messages for this submission - PERMANENT DELETION
            $result = $wpdb->delete(
                $table_name,
                array('submission_id' => $submission_id),
                array('%d')
            );
            
            if ($result === false) {
                $wpdb->query('ROLLBACK');
                wp_send_json_error(array('message' => 'Database error: Failed to delete messages'));
                return;
            }
            
            // Also clear any cached conversation data
            wp_cache_delete("cf7_conversation_messages_{$submission_id}", 'cf7_artist_submissions');
            wp_cache_delete("cf7_conversation_count_{$submission_id}", 'cf7_artist_submissions');
            
            // Commit the transaction
            $wpdb->query('COMMIT');
            
            // Log the action for audit purposes with more detail
            error_log("CF7 Artist Submissions: PRIVACY DELETION - {$message_count} messages permanently deleted for submission ID {$submission_id} by user " . get_current_user_id() . " at " . current_time('mysql'));
            
            wp_send_json_success(array(
                'message' => sprintf(__('%d messages permanently deleted from database', 'cf7-artist-submissions'), $message_count),
                'deleted_count' => $message_count,
                'action' => 'permanent_deletion'
            ));
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log("CF7 Artist Submissions: Error during message deletion for submission {$submission_id}: " . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to clear messages: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for cleaning up IMAP inbox - removes processed emails from server
     */
    public static function ajax_cleanup_imap_inbox() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions - only admins can do cleanup
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $start_time = microtime(true);
        
        try {
            $result = self::cleanup_processed_emails_from_imap();
            
            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2) . ' seconds';
            
            if ($result !== false) {
                $response_data = array(
                    'deleted_count' => $result['deleted_count'],
                    'scanned_count' => $result['scanned_count'],
                    'orphaned_count' => isset($result['orphaned_count']) ? $result['orphaned_count'] : 0,
                    'folders_deleted' => isset($result['folders_deleted']) ? $result['folders_deleted'] : 0,
                    'duration' => $duration
                );
                
                if (isset($result['skipped']) && $result['skipped']) {
                    $response_data['message'] = $result['message'];
                } else {
                    $response_data['message'] = 'IMAP cleanup completed successfully';
                }
                
                wp_send_json_success($response_data);
            } else {
                wp_send_json_error(array(
                    'message' => 'IMAP cleanup failed - check server configuration',
                    'duration' => $duration
                ));
            }
            
        } catch (Exception $e) {
            error_log('CF7 Artist Submissions: IMAP cleanup error - ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'IMAP cleanup failed: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Clean up processed emails from IMAP server
     * Scans all emails and deletes those that have been successfully processed and stored in database
     */
    public static function cleanup_processed_emails_from_imap() {
        $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
        
        if (empty($imap_settings['server']) || empty($imap_settings['username']) || empty($imap_settings['password'])) {
            error_log('CF7 Artist Submissions: IMAP cleanup failed - IMAP not configured');
            return false;
        }
        
        // Check if deletion is enabled
        $delete_processed = isset($imap_settings['delete_processed']) ? $imap_settings['delete_processed'] : '1';
        if ($delete_processed !== '1') {
            error_log('CF7 Artist Submissions: IMAP cleanup skipped - delete_processed is disabled');
            return array(
                'scanned_count' => 0, 
                'deleted_count' => 0, 
                'folders_deleted' => 0,
                'skipped' => true,
                'message' => 'Cleanup skipped - email deletion is disabled in settings'
            );
        }
        
        try {
            // Build connection string
            $encryption = !empty($imap_settings['encryption']) ? '/' . $imap_settings['encryption'] : '/ssl';
            $port = !empty($imap_settings['port']) ? $imap_settings['port'] : 993;
            $connection_string = '{' . $imap_settings['server'] . ':' . $port . '/imap' . $encryption . '}';
            
            error_log('CF7 Artist Submissions: Starting IMAP cleanup - connecting to: ' . $connection_string);
            
            // Connect to IMAP server
            $connection = imap_open(
                $connection_string,
                $imap_settings['username'],
                $imap_settings['password']
            );
            
            if (!$connection) {
                $error = imap_last_error();
                error_log('CF7 Artist Submissions: IMAP cleanup connection failed: ' . $error);
                return false;
            }
            
            error_log('CF7 Artist Submissions: IMAP cleanup connection successful');
            
            // Get list of ALL folders to check (not just INBOX and submission folders)
            $folders = @imap_list($connection, $connection_string, '*');
            $folders_to_check = array();
            
            if ($folders) {
                foreach ($folders as $folder) {
                    $folder_name = str_replace($connection_string, '', $folder);
                    $folders_to_check[] = $folder_name;
                }
                error_log('CF7 Artist Submissions: Found ' . count($folders_to_check) . ' folders to check: ' . implode(', ', $folders_to_check));
            } else {
                // Fallback to just INBOX if folder listing fails
                $folders_to_check = array('INBOX');
                error_log('CF7 Artist Submissions: Could not list folders, checking INBOX only');
            }
            
            $total_scanned = 0;
            $total_deleted = 0;
            $total_orphaned = 0;
            $folders_deleted = 0;
            $empty_folders = array();
            
            // Check each folder
            foreach ($folders_to_check as $folder_name) {
                error_log('CF7 Artist Submissions: Cleanup checking folder: ' . $folder_name);
                
                // Switch to the folder
                if ($folder_name !== 'INBOX') {
                    $reopen_result = imap_reopen($connection, $connection_string . $folder_name);
                    if (!$reopen_result) {
                        error_log('CF7 Artist Submissions: Cleanup failed to switch to folder: ' . $folder_name);
                        continue;
                    }
                } else {
                    // Make sure we're in INBOX
                    $reopen_result = imap_reopen($connection, $connection_string . 'INBOX');
                    if (!$reopen_result) {
                        error_log('CF7 Artist Submissions: Cleanup failed to switch to INBOX');
                        continue;
                    }
                }
                
                $folder_result = self::cleanup_folder_emails($connection, $folder_name);
                $total_scanned += $folder_result['scanned'];
                $total_deleted += $folder_result['deleted'];
                $total_orphaned += isset($folder_result['orphaned']) ? $folder_result['orphaned'] : 0;
                
                // Check if folder is now empty (and not INBOX)
                if ($folder_name !== 'INBOX') {
                    $remaining_messages = imap_num_msg($connection);
                    if ($remaining_messages == 0) {
                        $empty_folders[] = $folder_name;
                        error_log('CF7 Artist Submissions: Folder ' . $folder_name . ' is now empty and will be deleted');
                    }
                }
                
                error_log('CF7 Artist Submissions: Cleanup folder ' . $folder_name . ' - scanned: ' . $folder_result['scanned'] . ', deleted: ' . $folder_result['deleted'] . ', orphaned: ' . (isset($folder_result['orphaned']) ? $folder_result['orphaned'] : 0));
            }
            
            // Expunge deleted emails
            if ($total_deleted > 0) {
                imap_expunge($connection);
                error_log('CF7 Artist Submissions: Cleanup expunged ' . $total_deleted . ' emails from server');
            }
            
            // Delete empty folders (except INBOX and system folders)
            foreach ($empty_folders as $empty_folder) {
                // Skip system folders
                $system_folders = array('INBOX', 'Sent', 'Drafts', 'Trash', 'Junk', 'Spam', 'Outbox', 'Archive', 'Deleted Items', 'Sent Items');
                $is_system_folder = false;
                
                foreach ($system_folders as $system_folder) {
                    if (stripos($empty_folder, $system_folder) !== false) {
                        $is_system_folder = true;
                        break;
                    }
                }
                
                if ($is_system_folder) {
                    error_log('CF7 Artist Submissions: Skipping system folder: ' . $empty_folder);
                    continue;
                }
                
                try {
                    // Switch back to a safe folder first (INBOX)
                    imap_reopen($connection, $connection_string . 'INBOX');
                    
                    // Delete the empty folder
                    $delete_result = imap_deletemailbox($connection, $connection_string . $empty_folder);
                    if ($delete_result) {
                        $folders_deleted++;
                        error_log('CF7 Artist Submissions: Successfully deleted empty folder: ' . $empty_folder);
                    } else {
                        $last_error = imap_last_error();
                        error_log('CF7 Artist Submissions: Failed to delete empty folder: ' . $empty_folder . ' - ' . $last_error);
                    }
                } catch (Exception $e) {
                    error_log('CF7 Artist Submissions: Error deleting empty folder ' . $empty_folder . ': ' . $e->getMessage());
                }
            }
            
            imap_close($connection);
            
            error_log('CF7 Artist Submissions: IMAP cleanup completed - scanned: ' . $total_scanned . ', deleted: ' . $total_deleted . ', orphaned: ' . $total_orphaned . ', folders deleted: ' . $folders_deleted);
            
            return array(
                'scanned_count' => $total_scanned,
                'deleted_count' => $total_deleted,
                'orphaned_count' => $total_orphaned,
                'folders_deleted' => $folders_deleted
            );
            
        } catch (Exception $e) {
            error_log('CF7 Artist Submissions: IMAP cleanup error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up emails in a specific IMAP folder
     */
    public static function cleanup_folder_emails($connection, $folder_name) {
        global $wpdb;
        
        $scanned_count = 0;
        $deleted_count = 0;
        $orphaned_count = 0;
        
        // Get total message count
        $total_messages = imap_num_msg($connection);
        
        error_log('CF7 Artist Submissions: Cleanup folder ' . $folder_name . ' reports ' . $total_messages . ' total messages');
        
        if ($total_messages == 0) {
            error_log('CF7 Artist Submissions: Cleanup folder ' . $folder_name . ' is empty, skipping');
            return array('scanned' => $scanned_count, 'deleted' => $deleted_count, 'orphaned' => $orphaned_count);
        }
        
        // Try to get a list of message UIDs to see what's actually available
        $message_list = imap_search($connection, 'ALL');
        
        if ($message_list === false || empty($message_list)) {
            error_log('CF7 Artist Submissions: Cleanup folder ' . $folder_name . ' - no messages found via search, despite count of ' . $total_messages);
            return array('scanned' => $scanned_count, 'deleted' => $deleted_count, 'orphaned' => $orphaned_count);
        }
        
        $actual_message_count = count($message_list);
        error_log('CF7 Artist Submissions: Cleanup scanning ' . $actual_message_count . ' actual emails in folder: ' . $folder_name);
        
        // Process each message found by search
        foreach ($message_list as $email_number) {
            $scanned_count++;
            
            try {
                $header = imap_headerinfo($connection, $email_number);
                
                if (!$header) {
                    error_log('CF7 Artist Submissions: Cleanup could not get header for message #' . $email_number);
                    continue;
                }
                
                // Check if this email matches our pattern and has been processed
                $to_address = '';
                if (isset($header->to) && is_array($header->to) && count($header->to) > 0) {
                    $to_address = $header->to[0]->mailbox . '@' . $header->to[0]->host;
                }
                
                $from_email = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';
                $subject = isset($header->subject) ? $header->subject : '';
                $message_id = isset($header->message_id) ? $header->message_id : '';
                
                $should_delete = false;
                
                // Check if it matches our plus addressing pattern
                if (preg_match('/\+SUB(\d+)_([a-f0-9]{32})@/', $to_address, $matches)) {
                    $submission_id = intval($matches[1]);
                    $reply_token = $matches[2];
                    
                    // First check if the submission still exists in the database
                    $submission_exists = get_post($submission_id) && get_post_type($submission_id) === 'cf7_submission';
                    
                    if (!$submission_exists) {
                        // Submission has been deleted - mark email for deletion regardless of processing status
                        $should_delete = true;
                        $orphaned_count++;
                        error_log('CF7 Artist Submissions: Cleanup found orphaned email for deleted submission #' . $submission_id . ' - marking for deletion');
                    } else {
                        // Submission exists - check if this email has been processed (stored in database)
                        $is_processed = false;
                        
                        // First check by message_id if available
                        if (!empty($message_id)) {
                            $table_name = $wpdb->prefix . 'cf7_conversations';
                            $existing = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM $table_name WHERE message_id = %s",
                                $message_id
                            ));
                            $is_processed = !empty($existing);
                        }
                        
                        // Fallback check by content characteristics
                        if (!$is_processed && !empty($from_email) && !empty($subject)) {
                            $table_name = $wpdb->prefix . 'cf7_conversations';
                            $existing = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM $table_name 
                                 WHERE submission_id = %d 
                                 AND from_email = %s 
                                 AND subject = %s 
                                 AND direction = 'inbound'
                                 ORDER BY sent_at DESC 
                                 LIMIT 1",
                                $submission_id,
                                $from_email,
                                $subject
                            ));
                            $is_processed = !empty($existing);
                        }
                        
                        // If email has been processed, mark for deletion
                        if ($is_processed) {
                            $should_delete = true;
                        }
                    }
                } else {
                    // Also check emails that might not match the pattern but are in the database
                    // This catches emails that were processed before the pattern was enforced
                    if (!empty($message_id)) {
                        $table_name = $wpdb->prefix . 'cf7_conversations';
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $table_name WHERE message_id = %s AND direction = 'inbound'",
                            $message_id
                        ));
                        if (!empty($existing)) {
                            $should_delete = true;
                        }
                    }
                    
                    // Additional check for emails from known submission email addresses
                    if (!$should_delete && !empty($from_email)) {
                        $table_name = $wpdb->prefix . 'cf7_conversations';
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $table_name 
                             WHERE from_email = %s 
                             AND direction = 'inbound'
                             AND subject = %s
                             ORDER BY sent_at DESC 
                             LIMIT 1",
                            $from_email,
                            $subject
                        ));
                        if (!empty($existing)) {
                            $should_delete = true;
                        }
                    }
                }
                
                // Delete the email if it should be deleted
                if ($should_delete) {
                    imap_delete($connection, $email_number);
                    $deleted_count++;
                    
                    // Log the deletion with reason
                    if (isset($submission_id) && !get_post($submission_id)) {
                        error_log('CF7 Artist Submissions: Cleanup deleted orphaned email for non-existent submission #' . $submission_id . ' (Message-ID: ' . $message_id . ', From: ' . $from_email . ', To: ' . $to_address . ')');
                    } else {
                        error_log('CF7 Artist Submissions: Cleanup deleted processed email (Message-ID: ' . $message_id . ', From: ' . $from_email . ', To: ' . $to_address . ')');
                    }
                }
                
            } catch (Exception $e) {
                error_log('CF7 Artist Submissions: Cleanup error processing email #' . $email_number . ': ' . $e->getMessage());
                continue;
            }
        }
        
        return array('scanned' => $scanned_count, 'deleted' => $deleted_count, 'orphaned' => $orphaned_count);
    }
    
    /**
     * Add context menu script for conversation messages
     */
    public static function add_context_menu_script() {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add context menu to conversation messages
            $(document).on('contextmenu', '.conversation-message', function(e) {
                e.preventDefault();
                
                var $message = $(this);
                var messageId = $message.data('message-id');
                
                if (!messageId) {
                    return;
                }
                
                // Determine message type and read status
                var isIncoming = $message.hasClass('incoming');
                var isUnread = $message.hasClass('unviewed');
                var readStatus = isUnread ? 'unread' : 'read';
                
                // Remove any existing context menus
                $('.cf7-context-menu').remove();
                
                // Build menu items
                var menuItems = '';
                
                // Add to Actions (for all messages)
                menuItems += '<div class="cf7-context-item" data-action="add-to-actions" data-message-id="' + messageId + '">' +
                    '<span class="dashicons dashicons-list-view"></span>' +
                    '<?php _e("Add to Actions", "cf7-artist-submissions"); ?>' +
                '</div>';
                
                // Mark as read/unread (only for incoming messages)
                if (isIncoming) {
                    if (isUnread) {
                        menuItems += '<div class="cf7-context-item" data-action="mark-read" data-message-id="' + messageId + '" data-current-status="unread">' +
                            '<span class="dashicons dashicons-yes"></span>' +
                            '<?php _e("Mark as Read", "cf7-artist-submissions"); ?>' +
                        '</div>';
                    } else {
                        menuItems += '<div class="cf7-context-item" data-action="mark-unread" data-message-id="' + messageId + '" data-current-status="read">' +
                            '<span class="dashicons dashicons-hidden"></span>' +
                            '<?php _e("Mark as Unread", "cf7-artist-submissions"); ?>' +
                        '</div>';
                    }
                }
                
                // Create context menu
                var $menu = $('<div class="cf7-context-menu">' + menuItems + '</div>');
                
                // Position menu (account for scroll position)
                $menu.css({
                    position: 'fixed',
                    top: (e.clientY) + 'px',
                    left: (e.clientX) + 'px',
                    zIndex: 999999
                });
                
                // Ensure menu stays within viewport
                setTimeout(function() {
                    var menuRect = $menu[0].getBoundingClientRect();
                    var viewportWidth = $(window).width();
                    var viewportHeight = $(window).height();
                    
                    if (menuRect.right > viewportWidth) {
                        $menu.css('left', (e.clientX - menuRect.width) + 'px');
                    }
                    if (menuRect.bottom > viewportHeight) {
                        $menu.css('top', (e.clientY - menuRect.height) + 'px');
                    }
                }, 10);
                
                // Add to page
                $('body').append($menu);
                
                // Handle menu clicks
                $menu.on('click', '.cf7-context-item', function(e) {
                    e.stopPropagation();
                    var action = $(this).data('action');
                    var contextMessageId = $(this).data('message-id');
                    var currentStatus = $(this).data('current-status');
                    
                    if (action === 'add-to-actions') {
                        // Use the global CF7_Actions interface
                        if (window.CF7_Actions && typeof window.CF7_Actions.openModal === 'function') {
                            var messageText = $message.find('.message-content').first().text().trim();
                            var truncatedText = messageText.length > 100 ? messageText.substring(0, 100) + '...' : messageText;
                            
                            window.CF7_Actions.openModal({
                                messageId: contextMessageId,
                                title: '<?php _e("Follow up on message", "cf7-artist-submissions"); ?>',
                                description: '<?php _e("Regarding: ", "cf7-artist-submissions"); ?>' + truncatedText
                            });
                        } else {
                            console.error('CF7_Actions.openModal not available');
                            alert('Actions system not available. Please make sure you are on a submission page.');
                        }
                    } else if (action === 'mark-read' || action === 'mark-unread') {
                        // Toggle read status
                        $.ajax({
                            url: cf7Conversations.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'cf7_toggle_message_read',
                                message_id: contextMessageId,
                                current_status: currentStatus,
                                nonce: cf7Conversations.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Update the message UI
                                    if (response.data.new_status === 'read') {
                                        $message.removeClass('unviewed');
                                    } else {
                                        $message.addClass('unviewed');
                                    }
                                    
                                    // Show success message
                                    if (typeof showNotification === 'function') {
                                        showNotification(response.data.message, 'success');
                                    }
                                } else {
                                    if (typeof showNotification === 'function') {
                                        showNotification('Failed to update message status', 'error');
                                    }
                                }
                            },
                            error: function() {
                                if (typeof showNotification === 'function') {
                                    showNotification('Error updating message status', 'error');
                                }
                            }
                        });
                    }
                    
                    $menu.remove();
                });
                
                // Close menu on outside click
                $(document).one('click', function() {
                    $menu.remove();
                });
                
                // Close menu on escape key
                $(document).one('keydown', function(e) {
                    if (e.keyCode === 27) {
                        $menu.remove();
                    }
                });
            });
        });
        </script>
        <?php
    }
}
