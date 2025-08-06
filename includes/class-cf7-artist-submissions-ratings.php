<?php
/**
 * CF7 Artist Submissions - Works Rating System
 *
 * Comprehensive rating and commenting system for individual submitted works.
 * Provides 5-star rating interface with curator comments, integrated with
 * the existing tabbed interface system and database architecture.
 *
 * Features:
 * • 5-star rating system for individual works
 * • Curator comments for each rated work
 * • List view layout for better organization
 * • AJAX-powered rating and comment saving
 * • Integration with existing file management system
 * • Audit trail logging for rating changes
 * • Responsive design with professional styling
 *
 * @package CF7_Artist_Submissions
 * @subpackage Ratings
 * @since 1.2.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Ratings Class
 * 
 * Manages the rating and commenting system for submitted works,
 * integrating with the existing file management and tabbed interface.
 */
class CF7_Artist_Submissions_Ratings {
    
    /**
     * Initialize the ratings system
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_hooks'));
        add_action('admin_init', array(__CLASS__, 'maybe_create_table'));
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // AJAX handlers for rating and comment operations
        add_action('wp_ajax_cf7_save_work_rating', array(__CLASS__, 'ajax_save_work_rating'));
        add_action('wp_ajax_cf7_get_work_rating', array(__CLASS__, 'ajax_get_work_rating'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Check if ratings table exists and create if needed
     */
    public static function maybe_create_table() {
        if (!self::table_exists()) {
            self::create_ratings_table();
        }
    }
    
    /**
     * Create the ratings database table
     */
    public static function create_ratings_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) unsigned NOT NULL,
            file_id bigint(20) unsigned NOT NULL,
            rating tinyint(1) unsigned NOT NULL DEFAULT 0,
            comments text DEFAULT NULL,
            curator_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_rating (submission_id, file_id, curator_id),
            KEY submission_id (submission_id),
            KEY file_id (file_id),
            KEY curator_id (curator_id),
            KEY rating (rating),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log table creation
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0, // System-wide action
                'table_created',
                array(
                    'table_name' => 'cf7as_work_ratings',
                    'message' => 'Work ratings table created successfully'
                )
            );
        }
    }
    
    /**
     * Check if ratings table exists
     */
    public static function table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        
        return $result === $table_name;
    }
    
    /**
     * Enqueue scripts and styles for the rating system
     */
    public static function enqueue_scripts($hook) {
        // Only load on submission edit pages
        global $post, $pagenow;
        
        // Check if we're on submission edit pages using multiple methods
        $is_submission_page = false;
        
        // Method 1: Check current screen
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->post_type === 'cf7_submission') {
            $is_submission_page = true;
        }
        
        // Method 2: Check post object
        if ($post && $post->post_type === 'cf7_submission') {
            $is_submission_page = true;
        }
        
        // Method 3: Check hook/pagenow for edit screens
        if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && 
            isset($_GET['post']) && get_post_type($_GET['post']) === 'cf7_submission') {
            $is_submission_page = true;
        }
        
        if (!$is_submission_page) {
            return;
        }
        
        wp_enqueue_script(
            'cf7-ratings',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/ratings.js',
            array('jquery'),
            '1.2.0',
            true
        );
        
        wp_enqueue_style(
            'cf7-ratings-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/ratings.css',
            array(),
            '1.2.0'
        );
        
        // Localize script with AJAX data
        wp_localize_script('cf7-ratings', 'cf7RatingsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_ratings_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'cf7-artist-submissions'),
                'saved' => __('Saved', 'cf7-artist-submissions'),
                'error' => __('Error saving rating', 'cf7-artist-submissions'),
                'confirm_remove' => __('Are you sure you want to remove this rating?', 'cf7-artist-submissions'),
                'last_updated' => __('Last updated:', 'cf7-artist-submissions')
            )
        ));
    }
    
    /**
     * AJAX handler for saving work ratings and comments
     */
    public static function ajax_save_work_rating() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_ratings_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $file_id = intval($_POST['file_id']);
        $rating = intval($_POST['rating']);
        $comments = sanitize_textarea_field($_POST['comments']);
        $curator_id = get_current_user_id();
        
        // Validate rating (1-5 stars or 0 to remove)
        if ($rating < 0 || $rating > 5) {
            wp_send_json_error('Invalid rating value');
            return;
        }
        
        // Validate submission exists
        if (!get_post($submission_id) || get_post_type($submission_id) !== 'cf7_submission') {
            wp_send_json_error('Invalid submission');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Check if rating already exists
        $existing_rating = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE submission_id = %d AND file_id = %d AND curator_id = %d",
            $submission_id, $file_id, $curator_id
        ));
        
        if ($existing_rating) {
            // Update existing rating
            if ($rating === 0 && empty($comments)) {
                // Remove rating if both rating and comments are empty
                $result = $wpdb->delete(
                    $table_name,
                    array(
                        'submission_id' => $submission_id,
                        'file_id' => $file_id,
                        'curator_id' => $curator_id
                    ),
                    array('%d', '%d', '%d')
                );
                
                $action_type = 'rating_removed';
                $message = 'Work rating removed';
            } else {
                // Update existing rating
                $result = $wpdb->update(
                    $table_name,
                    array(
                        'rating' => $rating,
                        'comments' => $comments,
                        'updated_at' => current_time('mysql')
                    ),
                    array(
                        'submission_id' => $submission_id,
                        'file_id' => $file_id,
                        'curator_id' => $curator_id
                    ),
                    array('%d', '%s', '%s'),
                    array('%d', '%d', '%d')
                );
                
                $action_type = 'rating_updated';
                $message = 'Work rating updated';
            }
        } else {
            // Create new rating (only if not removing)
            if ($rating > 0 || !empty($comments)) {
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'submission_id' => $submission_id,
                        'file_id' => $file_id,
                        'rating' => $rating,
                        'comments' => $comments,
                        'curator_id' => $curator_id,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%s', '%d', '%s')
                );
                
                $action_type = 'rating_created';
                $message = 'Work rating created';
            } else {
                wp_send_json_error('Cannot create empty rating');
                return;
            }
        }
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        // Log the action
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                $submission_id,
                $action_type,
                array(
                    'file_id' => $file_id,
                    'rating' => $rating,
                    'has_comments' => !empty($comments),
                    'comments_length' => strlen($comments),
                    'message' => $message
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'rating' => $rating,
            'comments' => $comments,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * AJAX handler for getting work rating data
     */
    public static function ajax_get_work_rating() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_ratings_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $submission_id = intval($_POST['submission_id']);
        $file_id = intval($_POST['file_id']);
        $curator_id = get_current_user_id();
        
        $rating_data = self::get_work_rating($submission_id, $file_id, $curator_id);
        
        wp_send_json_success($rating_data);
    }
    
    /**
     * Get rating data for a specific work
     */
    public static function get_work_rating($submission_id, $file_id, $curator_id = null) {
        global $wpdb;
        
        if (!$curator_id) {
            $curator_id = get_current_user_id();
        }
        
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        
        $rating = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE submission_id = %d AND file_id = %d AND curator_id = %d",
            $submission_id, $file_id, $curator_id
        ));
        
        if ($rating) {
            return array(
                'rating' => intval($rating->rating),
                'comments' => $rating->comments,
                'created_at' => $rating->created_at,
                'updated_at' => $rating->updated_at,
                'curator_id' => intval($rating->curator_id)
            );
        }
        
        return array(
            'rating' => 0,
            'comments' => '',
            'created_at' => null,
            'updated_at' => null,
            'curator_id' => $curator_id
        );
    }
    
    /**
     * Get all ratings for a submission
     */
    public static function get_submission_ratings($submission_id, $curator_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        
        if ($curator_id) {
            $ratings = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE submission_id = %d AND curator_id = %d ORDER BY created_at DESC",
                $submission_id, $curator_id
            ));
        } else {
            $ratings = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE submission_id = %d ORDER BY created_at DESC",
                $submission_id
            ));
        }
        
        $formatted_ratings = array();
        foreach ($ratings as $rating) {
            $formatted_ratings[$rating->file_id] = array(
                'rating' => intval($rating->rating),
                'comments' => $rating->comments,
                'created_at' => $rating->created_at,
                'updated_at' => $rating->updated_at,
                'curator_id' => intval($rating->curator_id)
            );
        }
        
        return $formatted_ratings;
    }
    
    /**
     * Get average rating for a work
     */
    public static function get_work_average_rating($submission_id, $file_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cf7as_work_ratings';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM $table_name 
             WHERE submission_id = %d AND file_id = %d AND rating > 0",
            $submission_id, $file_id
        ));
        
        return array(
            'average' => $result->average ? round(floatval($result->average), 1) : 0,
            'count' => intval($result->count)
        );
    }
    
    /**
     * Render rating interface for a specific work
     */
    public static function render_rating_interface($submission_id, $file_id) {
        $current_rating = self::get_work_rating($submission_id, $file_id);
        $average_rating = self::get_work_average_rating($submission_id, $file_id);
        
        ob_start();
        ?>
        <div class="cf7-work-rating" data-submission-id="<?php echo esc_attr($submission_id); ?>" data-file-id="<?php echo esc_attr($file_id); ?>">
            <div class="cf7-rating-section">
                <div class="cf7-rating-header">
                    <h4><?php _e('Your Rating', 'cf7-artist-submissions'); ?></h4>
                    <?php if ($average_rating['count'] > 1): ?>
                        <div class="cf7-average-rating">
                            <span class="cf7-average-label"><?php _e('Average:', 'cf7-artist-submissions'); ?></span>
                            <span class="cf7-average-stars"><?php echo self::render_stars($average_rating['average'], false); ?></span>
                            <span class="cf7-average-text"><?php echo esc_html($average_rating['average']); ?> (<?php echo esc_html($average_rating['count']); ?> <?php _e('ratings', 'cf7-artist-submissions'); ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="cf7-rating-stars" data-current-rating="<?php echo esc_attr($current_rating['rating']); ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="cf7-star <?php echo ($i <= $current_rating['rating']) ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>">★</span>
                    <?php endfor; ?>
                    <button type="button" class="cf7-clear-rating" title="<?php _e('Clear rating', 'cf7-artist-submissions'); ?>">×</button>
                </div>
                
                <div class="cf7-rating-status"></div>
            </div>
            
            <div class="cf7-comments-section">
                <label for="cf7-work-comments-<?php echo esc_attr($file_id); ?>" class="cf7-comments-label">
                    <?php _e('Curator Comments', 'cf7-artist-submissions'); ?>
                </label>
                <textarea 
                    id="cf7-work-comments-<?php echo esc_attr($file_id); ?>" 
                    class="cf7-work-comments" 
                    rows="3" 
                    placeholder="<?php _e('Add your comments about this work...', 'cf7-artist-submissions'); ?>"
                ><?php echo esc_textarea($current_rating['comments']); ?></textarea>
                
                <div class="cf7-comments-actions">
                    <button type="button" class="cf7-save-rating button button-primary">
                        <span class="cf7-save-text"><?php _e('Save Rating & Comments', 'cf7-artist-submissions'); ?></span>
                        <span class="cf7-save-spinner" style="display: none;">
                            <span class="spinner is-active"></span>
                        </span>
                    </button>
                    
                    <?php if ($current_rating['updated_at']): ?>
                        <div class="cf7-last-updated">
                            <?php _e('Last updated:', 'cf7-artist-submissions'); ?> 
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($current_rating['updated_at']))); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render star display (static or interactive)
     */
    public static function render_stars($rating, $interactive = true) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $output = '';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $output .= '<span class="cf7-star active">★</span>';
        }
        
        // Half star
        if ($half_star) {
            $output .= '<span class="cf7-star half">★</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $output .= '<span class="cf7-star">★</span>';
        }
        
        return $output;
    }
}

// Initialize the ratings system
CF7_Artist_Submissions_Ratings::init();
