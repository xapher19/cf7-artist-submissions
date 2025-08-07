<?php
/**
 * CF7 Artist Submissions - Enhanced Curator Notes System
 *
 * Enhanced curator notes system supporting multiple curators with threaded
 * comments, individual attribution, and comprehensive note management.
 * Replaces the single curator notes field with a comment-like system
 * supporting both internal WordPress users and guest curators.
 *
 * Features:
 * • Multi-curator note system with individual attribution
 * • Threaded comment display with chronological ordering
 * • Support for both WordPress users and guest curators
 * • AJAX-powered note adding and management
 * • Migration from legacy curator notes field
 * • Professional comment interface with timestamps
 *
 * @package CF7_Artist_Submissions
 * @subpackage EnhancedCuratorNotes
 * @since 1.3.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Enhanced Curator Notes Class
 * 
 * Manages the enhanced curator notes system with multi-curator support.
 */
class CF7_Artist_Submissions_Enhanced_Curator_Notes {
    
    /**
     * Initialize the enhanced curator notes system
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_hooks'));
        add_action('admin_init', array(__CLASS__, 'maybe_migrate_legacy_notes'));
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // AJAX handlers for note management
        add_action('wp_ajax_cf7_add_curator_note', array(__CLASS__, 'ajax_add_curator_note'));
        add_action('wp_ajax_cf7_get_curator_notes', array(__CLASS__, 'ajax_get_curator_notes'));
        add_action('wp_ajax_cf7_delete_curator_note', array(__CLASS__, 'ajax_delete_curator_note'));
        add_action('wp_ajax_cf7_update_curator_note', array(__CLASS__, 'ajax_update_curator_note'));
        
        // Portal AJAX handlers (for guest curators)
        add_action('wp_ajax_nopriv_cf7_portal_add_curator_note', array(__CLASS__, 'ajax_portal_add_curator_note'));
        add_action('wp_ajax_nopriv_cf7_portal_get_curator_notes', array(__CLASS__, 'ajax_portal_get_curator_notes'));
        
        // Enqueue scripts for enhanced notes interface
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Migrate legacy curator notes to new system
     */
    public static function maybe_migrate_legacy_notes() {
        $migrated = get_option('cf7as_curator_notes_migrated', false);
        
        if (!$migrated) {
            self::migrate_legacy_curator_notes();
            update_option('cf7as_curator_notes_migrated', true);
        }
    }
    
    /**
     * Migrate existing curator notes from meta field to new table
     */
    public static function migrate_legacy_curator_notes() {
        global $wpdb;
        
        // Get all submissions with curator notes
        $posts_with_notes = $wpdb->get_results("
            SELECT post_id, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'cf7_curator_notes' 
            AND meta_value != ''
        ");
        
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        $migrated_count = 0;
        
        foreach ($posts_with_notes as $note_data) {
            $submission_id = $note_data->post_id;
            $note_content = $note_data->meta_value;
            
            // Skip if note already migrated
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $notes_table WHERE submission_id = %d AND note_content = %s",
                $submission_id, $note_content
            ));
            
            if ($existing) {
                continue;
            }
            
            // Insert as system migration note
            $result = $wpdb->insert(
                $notes_table,
                array(
                    'submission_id' => $submission_id,
                    'curator_id' => null,
                    'guest_curator_id' => null,
                    'curator_name' => 'System Migration',
                    'note_content' => $note_content,
                    'note_type' => 'note',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                $migrated_count++;
            }
        }
        
        // Log migration
        if (class_exists('CF7_Artist_Submissions_Action_Log') && $migrated_count > 0) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'curator_notes_migrated',
                array(
                    'migrated_count' => $migrated_count,
                    'migration_date' => current_time('mysql')
                )
            );
        }
    }
    
    // ============================================================================
    // NOTE MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Add a new curator note
     */
    public static function add_curator_note($submission_id, $note_content, $curator_name = null, $curator_id = null, $guest_curator_id = null, $note_type = 'note') {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        
        // Validate submission exists
        if (!get_post($submission_id) || get_post_type($submission_id) !== 'cf7_submission') {
            return new WP_Error('invalid_submission', 'Invalid submission ID');
        }
        
        // Determine curator name if not provided
        if (empty($curator_name)) {
            if ($curator_id) {
                $user = get_user_by('id', $curator_id);
                $curator_name = $user ? $user->display_name : 'Administrator';
            } elseif ($guest_curator_id) {
                $guest_curator = $wpdb->get_row($wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}cf7as_guest_curators WHERE id = %d",
                    $guest_curator_id
                ));
                $curator_name = $guest_curator ? $guest_curator->name : 'Guest Curator';
            } else {
                $current_user = wp_get_current_user();
                $curator_name = $current_user->display_name ?: 'Administrator';
                $curator_id = $current_user->ID;
            }
        }
        
        // Insert the note
        $result = $wpdb->insert(
            $notes_table,
            array(
                'submission_id' => $submission_id,
                'curator_id' => $curator_id,
                'guest_curator_id' => $guest_curator_id,
                'curator_name' => sanitize_text_field($curator_name),
                'note_content' => sanitize_textarea_field($note_content),
                'note_type' => sanitize_text_field($note_type),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to save curator note');
        }
        
        $note_id = $wpdb->insert_id;
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $submission_id,
                'curator_note_added',
                array(
                    'note_id' => $note_id,
                    'curator_name' => $curator_name,
                    'note_type' => $note_type,
                    'content_length' => strlen($note_content)
                )
            );
        }
        
        return $note_id;
    }
    
    /**
     * Get curator notes for a submission
     */
    public static function get_curator_notes($submission_id, $note_type = null) {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        
        $where_clause = "WHERE submission_id = %d";
        $query_params = array($submission_id);
        
        if ($note_type) {
            $where_clause .= " AND note_type = %s";
            $query_params[] = $note_type;
        }
        
        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $notes_table $where_clause ORDER BY created_at DESC",
            $query_params
        ));
        
        $formatted_notes = array();
        foreach ($notes as $note) {
            $formatted_notes[] = array(
                'id' => $note->id,
                'submission_id' => $note->submission_id,
                'curator_id' => $note->curator_id,
                'guest_curator_id' => $note->guest_curator_id,
                'curator_name' => $note->curator_name,
                'note_content' => $note->note_content,
                'note_type' => $note->note_type,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
                'formatted_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($note->created_at)),
                'relative_time' => human_time_diff(strtotime($note->created_at), current_time('timestamp')) . ' ago'
            );
        }
        
        return $formatted_notes;
    }
    
    /**
     * Update a curator note
     */
    public static function update_curator_note($note_id, $note_content, $curator_id = null, $guest_curator_id = null) {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        
        // Get existing note to verify ownership
        $existing_note = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $notes_table WHERE id = %d",
            $note_id
        ));
        
        if (!$existing_note) {
            return new WP_Error('note_not_found', 'Curator note not found');
        }
        
        // Check if user can edit this note
        $current_user_id = get_current_user_id();
        $can_edit = false;
        
        if (current_user_can('manage_options')) {
            $can_edit = true; // Admins can edit any note
        } elseif ($curator_id && $existing_note->curator_id == $curator_id) {
            $can_edit = true; // WordPress user can edit their own note
        } elseif ($guest_curator_id && $existing_note->guest_curator_id == $guest_curator_id) {
            $can_edit = true; // Guest curator can edit their own note
        }
        
        if (!$can_edit) {
            return new WP_Error('permission_denied', 'You cannot edit this note');
        }
        
        // Update the note
        $result = $wpdb->update(
            $notes_table,
            array(
                'note_content' => sanitize_textarea_field($note_content),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $note_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to update curator note');
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $existing_note->submission_id,
                'curator_note_updated',
                array(
                    'note_id' => $note_id,
                    'curator_name' => $existing_note->curator_name
                )
            );
        }
        
        return true;
    }
    
    /**
     * Delete a curator note
     */
    public static function delete_curator_note($note_id, $curator_id = null, $guest_curator_id = null) {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        
        // Get existing note to verify ownership
        $existing_note = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $notes_table WHERE id = %d",
            $note_id
        ));
        
        if (!$existing_note) {
            return new WP_Error('note_not_found', 'Curator note not found');
        }
        
        // Check if user can delete this note
        $can_delete = false;
        
        if (current_user_can('manage_options')) {
            $can_delete = true; // Admins can delete any note
        } elseif ($curator_id && $existing_note->curator_id == $curator_id) {
            $can_delete = true; // WordPress user can delete their own note
        } elseif ($guest_curator_id && $existing_note->guest_curator_id == $guest_curator_id) {
            $can_delete = true; // Guest curator can delete their own note
        }
        
        if (!$can_delete) {
            return new WP_Error('permission_denied', 'You cannot delete this note');
        }
        
        // Delete the note
        $result = $wpdb->delete(
            $notes_table,
            array('id' => $note_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to delete curator note');
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $existing_note->submission_id,
                'curator_note_deleted',
                array(
                    'note_id' => $note_id,
                    'curator_name' => $existing_note->curator_name
                )
            );
        }
        
        return true;
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler to add curator note
     */
    public static function ajax_add_curator_note() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $note_content = sanitize_textarea_field($_POST['note_content']);
        $note_type = sanitize_text_field($_POST['note_type'] ?? 'note');
        
        if (empty($note_content)) {
            wp_send_json_error('Note content is required');
            return;
        }
        
        $note_id = self::add_curator_note($submission_id, $note_content, null, get_current_user_id(), null, $note_type);
        
        if (is_wp_error($note_id)) {
            wp_send_json_error($note_id->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Note added successfully',
            'note_id' => $note_id
        ));
    }
    
    /**
     * AJAX handler to get curator notes
     */
    public static function ajax_get_curator_notes() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $note_type = sanitize_text_field($_POST['note_type'] ?? null);
        
        $notes = self::get_curator_notes($submission_id, $note_type);
        
        wp_send_json_success($notes);
    }
    
    /**
     * AJAX handler to delete curator note
     */
    public static function ajax_delete_curator_note() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $note_id = intval($_POST['note_id']);
        $current_user_id = get_current_user_id();
        
        $result = self::delete_curator_note($note_id, $current_user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Note deleted successfully'
        ));
    }
    
    // ============================================================================
    // INTERFACE RENDERING SECTION
    // ============================================================================
    
    /**
     * Render enhanced curator notes interface
     */
    public static function render_enhanced_notes_interface($submission_id) {
        $notes = self::get_curator_notes($submission_id);
        $current_user = wp_get_current_user();
        
        ?>
        <div class="cf7-enhanced-curator-notes" data-submission-id="<?php echo esc_attr($submission_id); ?>">
            <div class="cf7-notes-header">
                <h3><?php _e('Curator Notes', 'cf7-artist-submissions'); ?></h3>
                <p class="cf7-notes-description">
                    <?php _e('Add notes and comments about this submission. All notes are visible to other curators.', 'cf7-artist-submissions'); ?>
                </p>
            </div>
            
            <!-- Add New Note Form -->
            <div class="cf7-add-note-form">
                <div class="cf7-form-group">
                    <label for="cf7-new-note-content"><?php _e('Add Note', 'cf7-artist-submissions'); ?></label>
                    <textarea 
                        id="cf7-new-note-content" 
                        class="cf7-note-textarea" 
                        rows="4" 
                        placeholder="<?php _e('Write your note here...', 'cf7-artist-submissions'); ?>"
                    ></textarea>
                </div>
                <div class="cf7-form-actions">
                    <button type="button" id="cf7-add-note-btn" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Note', 'cf7-artist-submissions'); ?>
                    </button>
                    <span class="cf7-note-status"></span>
                </div>
            </div>
            
            <!-- Notes List -->
            <div id="cf7-notes-list" class="cf7-notes-list">
                <?php if (empty($notes)): ?>
                    <div class="cf7-no-notes">
                        <p><?php _e('No notes have been added yet.', 'cf7-artist-submissions'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="cf7-note-item" data-note-id="<?php echo esc_attr($note['id']); ?>">
                            <div class="cf7-note-header">
                                <div class="cf7-note-author">
                                    <span class="cf7-curator-name"><?php echo esc_html($note['curator_name']); ?></span>
                                    <?php if ($note['guest_curator_id']): ?>
                                        <span class="cf7-curator-type"><?php _e('(Guest Curator)', 'cf7-artist-submissions'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cf7-note-meta">
                                    <span class="cf7-note-date" title="<?php echo esc_attr($note['formatted_date']); ?>">
                                        <?php echo esc_html($note['relative_time']); ?>
                                    </span>
                                    <?php if ($note['updated_at'] !== $note['created_at']): ?>
                                        <span class="cf7-note-edited"><?php _e('(edited)', 'cf7-artist-submissions'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cf7-note-content">
                                <div class="cf7-note-text"><?php echo wp_kses_post(wpautop($note['note_content'])); ?></div>
                            </div>
                            <div class="cf7-note-actions">
                                <?php 
                                $can_edit = current_user_can('manage_options') || 
                                           ($note['curator_id'] && $note['curator_id'] == $current_user->ID);
                                ?>
                                <?php if ($can_edit): ?>
                                    <button type="button" class="cf7-edit-note-btn cf7-note-action-btn" data-note-id="<?php echo esc_attr($note['id']); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Edit', 'cf7-artist-submissions'); ?>
                                    </button>
                                    <button type="button" class="cf7-delete-note-btn cf7-note-action-btn" data-note-id="<?php echo esc_attr($note['id']); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Delete', 'cf7-artist-submissions'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <input type="hidden" id="cf7-curator-notes-nonce" value="<?php echo wp_create_nonce('cf7_tabs_nonce'); ?>">
        <?php
    }
    
    /**
     * Enqueue scripts for enhanced notes interface
     */
    public static function enqueue_scripts($hook) {
        // Only enqueue on submission edit pages
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== 'cf7_submission') {
            return;
        }
        
        wp_enqueue_script(
            'cf7-enhanced-curator-notes',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/enhanced-curator-notes.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cf7-enhanced-curator-notes',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/enhanced-curator-notes.css',
            array(),
            CF7_ARTIST_SUBMISSIONS_VERSION
        );
        
        // Get current user info for permission checking
        $current_user = wp_get_current_user();
        $current_user_data = array(
            'id' => $current_user->ID,
            'can_manage_options' => current_user_can('manage_options'),
            'guest_curator_id' => null // Will be set if this is a guest curator
        );
        
        // Check if this is a guest curator session
        if (isset($_COOKIE['cf7_curator_session']) || isset($_SESSION['cf7_curator_session'])) {
            // Try to get guest curator info from session
            // This would need to be implemented if guest curators use this interface
        }
        
        wp_localize_script('cf7-enhanced-curator-notes', 'cf7EnhancedNotes', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_tabs_nonce'),
            'currentUser' => $current_user_data,
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this note?', 'cf7-artist-submissions'),
                'error_general' => __('An error occurred. Please try again.', 'cf7-artist-submissions'),
                'saving' => __('Saving...', 'cf7-artist-submissions'),
                'loading' => __('Loading...', 'cf7-artist-submissions'),
                'note_required' => __('Please enter a note.', 'cf7-artist-submissions'),
                'note_added' => __('Note added successfully.', 'cf7-artist-submissions'),
                'note_updated' => __('Note updated successfully.', 'cf7-artist-submissions'),
                'note_deleted' => __('Note deleted successfully.', 'cf7-artist-submissions')
            )
        ));
    }
}
