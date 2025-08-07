<?php
/**
 * CF7 Artist Submissions - Enhanced Multi-Curator Rating System
 *
 * Enhanced rating system supporting multiple curators with individual ratings
 * and calculated averages. Extends the existing single-curator system to
 * support multiple ratings per submission from different curators.
 *
 * Features:
 * • Multi-curator rating support with individual attribution
 * • Automatic average rating calculation and display
 * • Support for both WordPress users and guest curators
 * • Enhanced rating interface with curator identification
 * • Rating history and management
 * • Migration from legacy single-curator ratings
 *
 * @package CF7_Artist_Submissions
 * @subpackage EnhancedRatings
 * @since 1.3.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Enhanced Multi-Curator Rating System Class
 * 
 * Manages the enhanced multi-curator rating system.
 */
class CF7_Artist_Submissions_Enhanced_Ratings {
    
    /**
     * Initialize the enhanced rating system
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_hooks'));
        add_action('admin_init', array(__CLASS__, 'maybe_migrate_legacy_ratings'));
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // AJAX handlers for rating management
        add_action('wp_ajax_cf7_save_curator_rating', array(__CLASS__, 'ajax_save_curator_rating'));
        add_action('wp_ajax_cf7_get_curator_ratings', array(__CLASS__, 'ajax_get_curator_ratings'));
        add_action('wp_ajax_cf7_delete_curator_rating', array(__CLASS__, 'ajax_delete_curator_rating'));
        
        // Portal AJAX handlers (for guest curators)
        add_action('wp_ajax_nopriv_cf7_portal_save_curator_rating', array(__CLASS__, 'ajax_portal_save_curator_rating'));
        add_action('wp_ajax_nopriv_cf7_portal_get_curator_ratings', array(__CLASS__, 'ajax_portal_get_curator_ratings'));
        
        // Enqueue scripts for enhanced ratings interface
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        
        // Override legacy rating functions
        add_filter('cf7_submission_rating_display', array(__CLASS__, 'filter_rating_display'), 10, 2);
    }
    
    /**
     * Migrate legacy ratings to new multi-curator system
     */
    public static function maybe_migrate_legacy_ratings() {
        $migrated = get_option('cf7as_ratings_migrated', false);
        
        if (!$migrated) {
            self::migrate_legacy_ratings();
            update_option('cf7as_ratings_migrated', true);
        }
    }
    
    /**
     * Migrate existing ratings from single-curator to multi-curator system
     */
    public static function migrate_legacy_ratings() {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Check if table structure needs updating
        $table_structure = $wpdb->get_results("DESCRIBE $ratings_table");
        $has_curator_columns = false;
        
        foreach ($table_structure as $column) {
            if ($column->Field === 'curator_name' || $column->Field === 'guest_curator_id') {
                $has_curator_columns = true;
                break;
            }
        }
        
        // Add new columns if they don't exist
        if (!$has_curator_columns) {
            $wpdb->query("ALTER TABLE $ratings_table 
                ADD COLUMN curator_name VARCHAR(100) NULL AFTER curator_id,
                ADD COLUMN guest_curator_id INT(11) NULL AFTER curator_name,
                ADD COLUMN rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER rating
            ");
        }
        
        // Update existing ratings without curator names
        $wpdb->query("
            UPDATE $ratings_table r
            LEFT JOIN {$wpdb->users} u ON r.curator_id = u.ID
            SET r.curator_name = COALESCE(u.display_name, 'Administrator')
            WHERE r.curator_name IS NULL OR r.curator_name = ''
        ");
        
        $migrated_count = $wpdb->get_var("SELECT COUNT(*) FROM $ratings_table WHERE curator_name IS NOT NULL");
        
        // Log migration
        if (class_exists('CF7_Artist_Submissions_Action_Log') && $migrated_count > 0) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'ratings_migrated',
                array(
                    'migrated_count' => $migrated_count,
                    'migration_date' => current_time('mysql')
                )
            );
        }
    }
    
    // ============================================================================
    // RATING MANAGEMENT SECTION
    // ============================================================================
    
    /**
     * Save or update a curator rating
     */
    public static function save_curator_rating($submission_id, $rating, $curator_id = null, $guest_curator_id = null, $curator_name = null) {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Validate submission exists
        if (!get_post($submission_id) || get_post_type($submission_id) !== 'cf7_submission') {
            return new WP_Error('invalid_submission', 'Invalid submission ID');
        }
        
        // Validate rating
        $rating = intval($rating);
        if ($rating < 1 || $rating > 5) {
            return new WP_Error('invalid_rating', 'Rating must be between 1 and 5');
        }
        
        // Determine curator information
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
        
        // Check if rating already exists for this curator
        $where_conditions = array('submission_id = %d');
        $where_params = array($submission_id);
        
        if ($curator_id) {
            $where_conditions[] = 'curator_id = %d';
            $where_params[] = $curator_id;
        } elseif ($guest_curator_id) {
            $where_conditions[] = 'guest_curator_id = %d';
            $where_params[] = $guest_curator_id;
        } else {
            return new WP_Error('no_curator', 'No curator specified');
        }
        
        $existing_rating = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $ratings_table WHERE " . implode(' AND ', $where_conditions),
            $where_params
        ));
        
        if ($existing_rating) {
            // Update existing rating
            $result = $wpdb->update(
                $ratings_table,
                array(
                    'rating' => $rating,
                    'rated_at' => current_time('mysql')
                ),
                array('id' => $existing_rating->id),
                array('%d', '%s'),
                array('%d')
            );
            
            $rating_id = $existing_rating->id;
            $action = 'rating_updated';
        } else {
            // Insert new rating
            $result = $wpdb->insert(
                $ratings_table,
                array(
                    'submission_id' => $submission_id,
                    'curator_id' => $curator_id,
                    'curator_name' => sanitize_text_field($curator_name),
                    'guest_curator_id' => $guest_curator_id,
                    'rating' => $rating,
                    'rated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%d', '%d', '%s')
            );
            
            $rating_id = $wpdb->insert_id;
            $action = 'rating_added';
        }
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to save rating');
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $submission_id,
                $action,
                array(
                    'rating_id' => $rating_id,
                    'curator_name' => $curator_name,
                    'rating' => $rating,
                    'average_rating' => self::calculate_average_rating($submission_id)
                )
            );
        }
        
        return $rating_id;
    }
    
    /**
     * Get all curator ratings for a submission
     */
    public static function get_curator_ratings($submission_id) {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $ratings_table WHERE submission_id = %d ORDER BY rated_at DESC",
            $submission_id
        ));
        
        $formatted_ratings = array();
        foreach ($ratings as $rating) {
            $formatted_ratings[] = array(
                'id' => $rating->id,
                'submission_id' => $rating->submission_id,
                'curator_id' => $rating->curator_id,
                'curator_name' => $rating->curator_name ?: 'Administrator',
                'guest_curator_id' => $rating->guest_curator_id,
                'rating' => intval($rating->rating),
                'rated_at' => $rating->rated_at,
                'formatted_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($rating->rated_at)),
                'relative_time' => human_time_diff(strtotime($rating->rated_at), current_time('timestamp')) . ' ago',
                'is_guest_curator' => !empty($rating->guest_curator_id)
            );
        }
        
        return $formatted_ratings;
    }
    
    /**
     * Calculate average rating for a submission
     */
    public static function calculate_average_rating($submission_id) {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM $ratings_table WHERE submission_id = %d",
            $submission_id
        ));
        
        return array(
            'average' => $result->average ? round(floatval($result->average), 2) : 0,
            'count' => intval($result->count),
            'stars' => $result->average ? round(floatval($result->average)) : 0
        );
    }
    
    /**
     * Delete a curator rating
     */
    public static function delete_curator_rating($rating_id, $curator_id = null, $guest_curator_id = null) {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Get existing rating to verify ownership
        $existing_rating = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $ratings_table WHERE id = %d",
            $rating_id
        ));
        
        if (!$existing_rating) {
            return new WP_Error('rating_not_found', 'Rating not found');
        }
        
        // Check if user can delete this rating
        $can_delete = false;
        
        if (current_user_can('manage_options')) {
            $can_delete = true; // Admins can delete any rating
        } elseif ($curator_id && $existing_rating->curator_id == $curator_id) {
            $can_delete = true; // WordPress user can delete their own rating
        } elseif ($guest_curator_id && $existing_rating->guest_curator_id == $guest_curator_id) {
            $can_delete = true; // Guest curator can delete their own rating
        }
        
        if (!$can_delete) {
            return new WP_Error('permission_denied', 'You cannot delete this rating');
        }
        
        // Delete the rating
        $result = $wpdb->delete(
            $ratings_table,
            array('id' => $rating_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to delete rating');
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $existing_rating->submission_id,
                'rating_deleted',
                array(
                    'rating_id' => $rating_id,
                    'curator_name' => $existing_rating->curator_name,
                    'rating' => $existing_rating->rating
                )
            );
        }
        
        return true;
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler to save curator rating
     */
    public static function ajax_save_curator_rating() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $rating = intval($_POST['rating']);
        
        $rating_id = self::save_curator_rating($submission_id, $rating, get_current_user_id());
        
        if (is_wp_error($rating_id)) {
            wp_send_json_error($rating_id->get_error_message());
            return;
        }
        
        $average_data = self::calculate_average_rating($submission_id);
        
        wp_send_json_success(array(
            'message' => 'Rating saved successfully',
            'rating_id' => $rating_id,
            'average_rating' => $average_data['average'],
            'rating_count' => $average_data['count'],
            'stars' => $average_data['stars']
        ));
    }
    
    /**
     * AJAX handler to get curator ratings
     */
    public static function ajax_get_curator_ratings() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $ratings = self::get_curator_ratings($submission_id);
        $average_data = self::calculate_average_rating($submission_id);
        
        wp_send_json_success(array(
            'ratings' => $ratings,
            'average_rating' => $average_data['average'],
            'rating_count' => $average_data['count'],
            'stars' => $average_data['stars']
        ));
    }
    
    /**
     * AJAX handler to delete curator rating
     */
    public static function ajax_delete_curator_rating() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $rating_id = intval($_POST['rating_id']);
        $current_user_id = get_current_user_id();
        
        $result = self::delete_curator_rating($rating_id, $current_user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Rating deleted successfully'
        ));
    }
    
    // ============================================================================
    // INTERFACE RENDERING SECTION
    // ============================================================================
    
    /**
     * Render enhanced multi-curator rating interface
     */
    public static function render_enhanced_rating_interface($submission_id) {
        $ratings = self::get_curator_ratings($submission_id);
        $average_data = self::calculate_average_rating($submission_id);
        $current_user = wp_get_current_user();
        
        // Check if current user has already rated
        $user_rating = null;
        foreach ($ratings as $rating) {
            if ($rating['curator_id'] == $current_user->ID) {
                $user_rating = $rating;
                break;
            }
        }
        
        ?>
        <div class="cf7-enhanced-rating-system" data-submission-id="<?php echo esc_attr($submission_id); ?>">
            <div class="cf7-rating-header">
                <h3><?php _e('Curator Ratings', 'cf7-artist-submissions'); ?></h3>
                <div class="cf7-average-rating-display">
                    <div class="cf7-stars-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="cf7-star <?php echo $i <= $average_data['stars'] ? 'cf7-star-filled' : 'cf7-star-empty'; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="cf7-rating-meta">
                        <span class="cf7-average-number"><?php echo $average_data['average']; ?></span>
                        <span class="cf7-rating-count">(<?php echo $average_data['count']; ?> <?php echo $average_data['count'] === 1 ? 'rating' : 'ratings'; ?>)</span>
                    </div>
                </div>
            </div>
            
            <!-- Current User Rating Section -->
            <div class="cf7-user-rating-section">
                <h4><?php _e('Your Rating', 'cf7-artist-submissions'); ?></h4>
                <div class="cf7-user-rating-interface">
                    <div class="cf7-rating-stars" data-current-rating="<?php echo $user_rating ? $user_rating['rating'] : 0; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="cf7-rating-star" data-rating="<?php echo $i; ?>">
                                <span class="cf7-star <?php echo ($user_rating && $i <= $user_rating['rating']) ? 'cf7-star-filled' : 'cf7-star-empty'; ?>">★</span>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <div class="cf7-rating-actions">
                        <button type="button" id="cf7-save-rating-btn" class="button button-primary" disabled>
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Save Rating', 'cf7-artist-submissions'); ?>
                        </button>
                        <?php if ($user_rating): ?>
                            <button type="button" id="cf7-clear-rating-btn" class="button" data-rating-id="<?php echo $user_rating['id']; ?>">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php _e('Clear Rating', 'cf7-artist-submissions'); ?>
                            </button>
                        <?php endif; ?>
                        <span class="cf7-rating-status"></span>
                    </div>
                </div>
            </div>
            
            <!-- All Ratings List -->
            <?php if (!empty($ratings)): ?>
                <div class="cf7-all-ratings-section">
                    <h4><?php _e('All Curator Ratings', 'cf7-artist-submissions'); ?></h4>
                    <div id="cf7-ratings-list" class="cf7-ratings-list">
                        <?php foreach ($ratings as $rating): ?>
                            <div class="cf7-rating-item" data-rating-id="<?php echo esc_attr($rating['id']); ?>">
                                <div class="cf7-rating-curator">
                                    <span class="cf7-curator-name"><?php echo esc_html($rating['curator_name']); ?></span>
                                    <?php if ($rating['is_guest_curator']): ?>
                                        <span class="cf7-curator-type"><?php _e('(Guest Curator)', 'cf7-artist-submissions'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cf7-rating-value">
                                    <div class="cf7-rating-stars-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="cf7-star <?php echo $i <= $rating['rating'] ? 'cf7-star-filled' : 'cf7-star-empty'; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="cf7-rating-number"><?php echo $rating['rating']; ?>/5</span>
                                </div>
                                <div class="cf7-rating-meta">
                                    <span class="cf7-rating-date" title="<?php echo esc_attr($rating['formatted_date']); ?>">
                                        <?php echo esc_html($rating['relative_time']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <input type="hidden" id="cf7-rating-nonce" value="<?php echo wp_create_nonce('cf7_tabs_nonce'); ?>">
        <?php
    }
    
    /**
     * Filter the legacy rating display to show enhanced multi-curator data
     */
    public static function filter_rating_display($original_display, $submission_id) {
        $average_data = self::calculate_average_rating($submission_id);
        
        if ($average_data['count'] === 0) {
            return '<span class="cf7-no-rating">No ratings yet</span>';
        }
        
        $stars_html = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars_html .= '<span class="cf7-star ' . ($i <= $average_data['stars'] ? 'cf7-star-filled' : 'cf7-star-empty') . '">★</span>';
        }
        
        return '<div class="cf7-rating-summary">' .
            '<div class="cf7-stars-inline">' . $stars_html . '</div>' .
            '<span class="cf7-average-inline">' . $average_data['average'] . '</span>' .
            '<span class="cf7-count-inline">(' . $average_data['count'] . ')</span>' .
        '</div>';
    }
    
    /**
     * Enqueue scripts for enhanced rating interface
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
            'cf7-enhanced-ratings',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/enhanced-ratings.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cf7-enhanced-ratings',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/enhanced-ratings.css',
            array(),
            CF7_ARTIST_SUBMISSIONS_VERSION
        );
        
        wp_localize_script('cf7-enhanced-ratings', 'cf7EnhancedRatings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_tabs_nonce'),
            'strings' => array(
                'confirm_clear' => __('Are you sure you want to clear your rating?', 'cf7-artist-submissions'),
                'error_general' => __('An error occurred. Please try again.', 'cf7-artist-submissions'),
                'saving' => __('Saving...', 'cf7-artist-submissions'),
                'rating_saved' => __('Rating saved successfully.', 'cf7-artist-submissions'),
                'rating_cleared' => __('Rating cleared successfully.', 'cf7-artist-submissions'),
                'select_rating' => __('Please select a rating.', 'cf7-artist-submissions')
            )
        ));
    }
}
