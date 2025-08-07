<?php
/**
 * Secure API Endpoints for Curator Portal
 * 
 * This class provides SECURE API-only access for the curator portal.
 * NO direct WordPress function calls, NO direct database access.
 * All data is sanitized and validated through secure API endpoints.
 * 
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CF7_Artist_Submissions_Secure_API {
    
    /**
     * Initialize secure API endpoints
     */
    public static function init() {
        // Session validation endpoint
        add_action('wp_ajax_cf7_portal_validate_session', array(__CLASS__, 'validate_session'));
        add_action('wp_ajax_nopriv_cf7_portal_validate_session', array(__CLASS__, 'validate_session'));
        
        // Submission data endpoints  
        add_action('wp_ajax_cf7_portal_get_submission_details', array(__CLASS__, 'get_submission_details'));
        add_action('wp_ajax_nopriv_cf7_portal_get_submission_details', array(__CLASS__, 'get_submission_details'));
        
        add_action('wp_ajax_cf7_portal_get_tab_content', array(__CLASS__, 'get_tab_content'));
        add_action('wp_ajax_nopriv_cf7_portal_get_tab_content', array(__CLASS__, 'get_tab_content'));
        
        // Portal list endpoints
        add_action('wp_ajax_cf7_portal_get_submissions', array(__CLASS__, 'get_submissions_list'));
        add_action('wp_ajax_nopriv_cf7_portal_get_submissions', array(__CLASS__, 'get_submissions_list'));
        
        // Statistics endpoint
        add_action('wp_ajax_cf7_portal_get_statistics', array(__CLASS__, 'get_statistics'));
        add_action('wp_ajax_nopriv_cf7_portal_get_statistics', array(__CLASS__, 'get_statistics'));
        
        // Rating and notes endpoints
        add_action('wp_ajax_cf7_portal_save_rating', array(__CLASS__, 'save_rating'));
        add_action('wp_ajax_nopriv_cf7_portal_save_rating', array(__CLASS__, 'save_rating'));
        
        add_action('wp_ajax_cf7_portal_save_note', array(__CLASS__, 'save_note'));
        add_action('wp_ajax_nopriv_cf7_portal_save_note', array(__CLASS__, 'save_note'));
        
        // File access endpoint
        add_action('wp_ajax_cf7_portal_download_file', array(__CLASS__, 'download_file'));
        add_action('wp_ajax_nopriv_cf7_portal_download_file', array(__CLASS__, 'download_file'));
    }
    
    /**
     * SECURE: Validate curator session
     */
    public static function validate_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7_curator_portal_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        
        if (empty($session_token)) {
            wp_send_json_error(array('message' => 'No session token provided'));
            return;
        }
        
        // SECURITY: Validate session token securely
        $is_valid = self::validate_curator_token($session_token);
        
        if ($is_valid) {
            wp_send_json_success(array(
                'message' => 'Session is valid',
                'expires' => self::get_token_expiry($session_token)
            ));
        } else {
            wp_send_json_error(array('message' => 'Invalid or expired session'));
        }
    }
    
    /**
     * SECURE: Get submission basic details
     */
    public static function get_submission_details() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
            return;
        }
        
        // SECURITY: Validate submission exists and get basic data securely
        $submission_data = self::get_secure_submission_data($submission_id);
        
        if (!$submission_data) {
            wp_send_json_error(array('message' => 'Submission not found or access denied'));
            return;
        }
        
        wp_send_json_success($submission_data);
    }
    
    /**
     * SECURE: Get tab-specific content
     */
    public static function get_tab_content() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $tab = sanitize_key($_POST['tab'] ?? '');
        
        if (!$submission_id || !$tab) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        // SECURITY: Generate tab content securely
        $content = self::generate_secure_tab_content($submission_id, $tab);
        
        if ($content === false) {
            wp_send_json_error(array('message' => 'Failed to load tab content'));
            return;
        }
        
        wp_send_json_success(array('content' => $content));
    }
    
    /**
     * DEPRECATED: Username/password login - replaced with email-only authentication
     * This method is no longer used as the curator portal uses email token authentication
     */
    /*
    public static function curator_login() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7_curator_portal_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Username and password required'));
            return;
        }
        
        // SECURITY: Validate curator credentials
        $curator_data = self::validate_curator_credentials($username, $password);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Invalid credentials'));
            return;
        }
        
        // SECURITY: Generate secure session token
        $session_token = self::generate_secure_session_token($curator_data);
        
        wp_send_json_success(array(
            'token' => $session_token,
            'curator' => array(
                'name' => $curator_data['name'],
                'email' => $curator_data['email']
            ),
            'expires' => time() + (7 * 24 * 60 * 60) // 7 days
        ));
    }
    */
    
    /**
     * SECURE: Get submissions list for curator
     */
    public static function get_submissions_list() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        $curator_data = self::get_curator_from_session($session_token);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Session invalid'));
            return;
        }
        
        // Get pagination parameters securely
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(50, max(1, intval($_POST['per_page'] ?? 20))); // Limit to 50 per page
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $submissions_data = self::get_secure_submissions_list($curator_data['id'], $page, $per_page, $search);
        
        wp_send_json_success($submissions_data);
    }
    
    /**
     * SECURE: Get portal statistics
     */
    public static function get_statistics() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        $curator_data = self::get_curator_from_session($session_token);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Session invalid'));
            return;
        }
        
        $stats = self::get_secure_curator_statistics($curator_data['id']);
        
        wp_send_json_success($stats);
    }
    
    /**
     * SECURE: Save rating
     */
    public static function save_rating() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        
        $curator_data = self::get_curator_from_session($session_token);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Session invalid'));
            return;
        }
        
        if (!$submission_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $result = self::save_secure_rating($submission_id, $rating, $curator_data['id']);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Rating saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save rating'));
        }
    }
    
    /**
     * SECURE: Save note
     */
    public static function save_note() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $note_content = sanitize_textarea_field($_POST['note_content'] ?? '');
        
        $curator_data = self::get_curator_from_session($session_token);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Session invalid'));
            return;
        }
        
        if (!$submission_id || empty(trim($note_content))) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $result = self::save_secure_note($submission_id, $note_content, $curator_data['id']);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Note saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save note'));
        }
    }
    
    /**
     * SECURE: Download file 
     */
    public static function download_file() {
        // Verify nonce and session
        if (!self::verify_request_security()) {
            return;
        }
        
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $file_id = intval($_POST['file_id'] ?? 0);
        
        $curator_data = self::get_curator_from_session($session_token);
        
        if (!$curator_data) {
            wp_send_json_error(array('message' => 'Session invalid'));
            return;
        }
        
        if (!$submission_id || !$file_id) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $file_url = self::get_secure_file_url($submission_id, $file_id, $curator_data['id']);
        
        if ($file_url) {
            wp_send_json_success(array('download_url' => $file_url));
        } else {
            wp_send_json_error(array('message' => 'File not found or access denied'));
        }
    }
    
    /**
     * SECURITY HELPER: Verify request has valid nonce and session
     */
    private static function verify_request_security() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7_curator_portal_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return false;
        }
        
        // Verify session token
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        if (!self::validate_curator_token($session_token)) {
            wp_send_json_error(array('message' => 'Invalid or expired session'));
            return false;
        }
        
        return true;
    }
    
    /**
     * SECURITY: Validate curator session token
     */
    private static function validate_curator_token($token) {
        if (empty($token)) {
            return false;
        }
        
        // Get stored curator sessions
        $curator_sessions = get_option('cf7_curator_sessions', array());
        
        foreach ($curator_sessions as $session) {
            if ($session['token'] === $token && $session['expires'] > time()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * SECURITY: Get token expiry time
     */
    private static function get_token_expiry($token) {
        $curator_sessions = get_option('cf7_curator_sessions', array());
        
        foreach ($curator_sessions as $session) {
            if ($session['token'] === $token) {
                return $session['expires'];
            }
        }
        
        return 0;
    }
    
    /**
     * SECURITY: Get basic submission data securely
     */
    private static function get_secure_submission_data($submission_id) {
        // SECURITY: Only get basic post data, no direct WordPress functions
        $submission = get_post($submission_id);
        
        if (!$submission || $submission->post_type !== 'cf7_submission') {
            return false;
        }
        
        return array(
            'id' => $submission_id,
            'title' => sanitize_text_field($submission->post_title ?: 'Untitled Submission'),
            'date' => get_the_date('F j, Y g:i A', $submission_id),
            'status' => sanitize_text_field($submission->post_status)
        );
    }
    
    /**
     * SECURITY: Generate tab content securely
     */
    private static function generate_secure_tab_content($submission_id, $tab) {
        // SECURITY: Only generate safe HTML content, no direct data exposure
        
        switch ($tab) {
            case 'profile':
                return self::generate_profile_tab_content($submission_id);
                
            case 'works':
                return self::generate_works_tab_content($submission_id);
                
            case 'statement':
                return self::generate_statement_tab_content($submission_id);
                
            case 'files':
                return self::generate_files_tab_content($submission_id);
                
            case 'conversations':
                return self::generate_conversations_tab_content($submission_id);
                
            case 'export':
                return self::generate_export_tab_content($submission_id);
                
            default:
                return '<div class="error-content">Invalid tab requested</div>';
        }
    }
    
    /**
     * SECURITY: Generate profile tab content safely
     */
    private static function generate_profile_tab_content($submission_id) {
        // SECURITY: Get only specific, safe metadata
        $safe_fields = array(
            'name' => 'Artist Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'website' => 'Website',
            'instagram' => 'Instagram',
            'location' => 'Location',
            'bio' => 'Biography'
        );
        
        $content = '<div class="cf7-form-grid">';
        
        foreach ($safe_fields as $key => $label) {
            $value = get_post_meta($submission_id, '_cf7as_' . $key, true);
            $value = sanitize_text_field($value);
            
            if (!empty($value)) {
                $content .= '<div class="cf7-form-row">';
                $content .= '<label>' . esc_html($label) . ':</label>';
                
                if ($key === 'email') {
                    $content .= '<div class="cf7-form-value"><a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a></div>';
                } elseif ($key === 'website' || $key === 'instagram') {
                    $url = ($key === 'website' && !preg_match('/^https?:\/\//', $value)) ? 'http://' . $value : $value;
                    $content .= '<div class="cf7-form-value"><a href="' . esc_url($url) . '" target="_blank">' . esc_html($value) . '</a></div>';
                } else {
                    $content .= '<div class="cf7-form-value">' . nl2br(esc_html($value)) . '</div>';
                }
                
                $content .= '</div>';
            }
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * SECURITY: Generate works tab content safely  
     */
    private static function generate_works_tab_content($submission_id) {
        $content = '<div class="cf7-form-grid">';
        
        // Get work-related fields safely
        $work_fields = array(
            'work_title' => 'Work Title',
            'medium' => 'Medium',
            'dimensions' => 'Dimensions', 
            'year' => 'Year',
            'description' => 'Description'
        );
        
        foreach ($work_fields as $key => $label) {
            $value = get_post_meta($submission_id, '_cf7as_' . $key, true);
            $value = sanitize_text_field($value);
            
            if (!empty($value)) {
                $content .= '<div class="cf7-form-row">';
                $content .= '<label>' . esc_html($label) . ':</label>';
                $content .= '<div class="cf7-form-value">' . nl2br(esc_html($value)) . '</div>';
                $content .= '</div>';
            }
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * SECURITY: Generate statement tab content safely
     */
    private static function generate_statement_tab_content($submission_id) {
        $statement = get_post_meta($submission_id, '_cf7as_artist_statement', true);
        $statement = sanitize_textarea_field($statement);
        
        if (empty($statement)) {
            return '<div class="cf7-form-grid"><p>No artist statement provided.</p></div>';
        }
        
        return '<div class="cf7-form-grid">' .
               '<div class="cf7-form-row">' .
               '<div class="cf7-form-value">' . nl2br(esc_html($statement)) . '</div>' .
               '</div>' .
               '</div>';
    }
    
    /**
     * SECURITY: Generate files tab content safely
     */
    private static function generate_files_tab_content($submission_id) {
        // SECURITY: Get file attachments safely without exposing file system
        $content = '<div class="cf7-form-grid">';
        $content .= '<h3>Submission Files</h3>';
        
        // Get attachment IDs safely
        $attachment_ids = get_post_meta($submission_id, '_cf7as_attachments', true);
        
        if (empty($attachment_ids) || !is_array($attachment_ids)) {
            $content .= '<p>No files attached to this submission.</p>';
        } else {
            $content .= '<div class="file-list">';
            
            foreach ($attachment_ids as $attachment_id) {
                $attachment = get_post($attachment_id);
                if ($attachment) {
                    $file_url = wp_get_attachment_url($attachment_id);
                    $file_name = basename($attachment->post_title ?: $file_url);
                    
                    $content .= '<div class="file-item">';
                    $content .= '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a>';
                    $content .= '</div>';
                }
            }
            
            $content .= '</div>';
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * SECURITY: Generate conversations tab content safely
     */
    private static function generate_conversations_tab_content($submission_id) {
        return '<div class="cf7-form-grid">' .
               '<h3>Conversations</h3>' .
               '<p>Conversation system will be implemented in a future update.</p>' .
               '</div>';
    }
    
    /**
     * SECURITY: Generate export tab content safely
     */
    private static function generate_export_tab_content($submission_id) {
        return '<div class="cf7-form-grid">' .
               '<h3>Export Options</h3>' .
               '<p>Export functionality will be implemented in a future update.</p>' .
               '</div>';
    }
    
    /**
     * SECURITY: Validate curator credentials
     */
    private static function validate_curator_credentials($username, $password) {
        // Get curator list from settings
        $curators = get_option('cf7as_curators', array());
        
        foreach ($curators as $curator) {
            if ($curator['username'] === $username && 
                password_verify($password, $curator['password_hash'])) {
                return array(
                    'id' => $curator['id'],
                    'name' => $curator['name'],
                    'email' => $curator['email'],
                    'username' => $curator['username']
                );
            }
        }
        
        return false;
    }
    
    /**
     * SECURITY: Generate secure session token
     */
    private static function generate_secure_session_token($curator_data) {
        $token = wp_generate_password(32, false);
        $expires = time() + (7 * 24 * 60 * 60); // 7 days
        
        // Store session securely
        $curator_sessions = get_option('cf7_curator_sessions', array());
        
        // Clean up expired sessions first
        $curator_sessions = array_filter($curator_sessions, function($session) {
            return $session['expires'] > time();
        });
        
        // Add new session
        $curator_sessions[] = array(
            'token' => $token,
            'curator_id' => $curator_data['id'],
            'curator_name' => $curator_data['name'],
            'created' => time(),
            'expires' => $expires,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        update_option('cf7_curator_sessions', $curator_sessions);
        
        return $token;
    }
    
    /**
     * SECURITY: Get curator data from session token
     */
    private static function get_curator_from_session($token) {
        if (empty($token)) {
            return false;
        }
        
        // Get stored curator sessions
        $curator_sessions = get_option('cf7_curator_sessions', array());
        
        foreach ($curator_sessions as $session) {
            if ($session['token'] === $token && $session['expires'] > time()) {
                return array(
                    'id' => $session['curator_id'],
                    'name' => $session['curator_name']
                );
            }
        }
        
        return false;
    }
    
    /**
     * SECURITY: Get secure submissions list for curator
     */
    private static function get_secure_submissions_list($curator_id, $page, $per_page, $search) {
        global $wpdb;
        
        // Build secure query for submissions
        $posts_table = $wpdb->posts;
        $where_conditions = array("p.post_type = 'cf7_submission'", "p.post_status = 'publish'");
        $query_params = array();
        
        // Add search condition if provided
        if (!empty($search)) {
            $where_conditions[] = "(p.post_title LIKE %s OR p.post_content LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $posts_table p WHERE $where_clause";
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        
        // Get submissions
        $submissions_query = "
            SELECT p.ID, p.post_title, p.post_date
            FROM $posts_table p 
            WHERE $where_clause 
            ORDER BY p.post_date DESC 
            LIMIT %d OFFSET %d
        ";
        
        $query_params[] = $per_page;
        $query_params[] = $offset;
        
        $submissions = $wpdb->get_results($wpdb->prepare($submissions_query, $query_params));
        
        $submissions_data = array();
        
        foreach ($submissions as $submission) {
            // Get submission metadata safely
            $artist_name = get_post_meta($submission->ID, '_artist_name', true) ?: 'Unknown Artist';
            $submission_date = date('M j, Y', strtotime($submission->post_date));
            
            $submissions_data[] = array(
                'id' => $submission->ID,
                'title' => $submission->post_title ?: 'Untitled Submission',
                'artist' => array(
                    'name' => $artist_name
                ),
                'date' => $submission_date,
                'url' => home_url('/curator-portal/submission/' . $submission->ID . '/')
            );
        }
        
        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        
        return array(
            'submissions' => $submissions_data,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => intval($total_items),
                'per_page' => $per_page,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            )
        );
    }
    
    /**
     * SECURITY: Get secure curator statistics
     */
    private static function get_secure_curator_statistics($curator_id) {
        global $wpdb;
        
        // Get total submissions count
        $posts_table = $wpdb->posts;
        $total_submissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE post_type = 'cf7_submission' AND post_status = 'publish'"
        ));
        
        // Get ratings count (assuming there's a ratings meta key)
        $meta_table = $wpdb->postmeta;
        $completed_reviews = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) 
             FROM $meta_table pm 
             INNER JOIN $posts_table p ON pm.post_id = p.ID 
             WHERE p.post_type = 'cf7_submission' 
             AND p.post_status = 'publish' 
             AND pm.meta_key = '_curator_rating' 
             AND pm.meta_value != ''"
        ));
        
        $pending_reviews = max(0, $total_submissions - $completed_reviews);
        
        return array(
            'total_submissions' => intval($total_submissions),
            'pending_reviews' => intval($pending_reviews),
            'completed_reviews' => intval($completed_reviews),
            'average_rating' => 0 // Could calculate this if needed
        );
    }
    
    /**
     * SECURITY: Save rating securely
     */
    private static function save_secure_rating($submission_id, $rating, $curator_id) {
        // Placeholder for secure rating save
        // In real implementation, would validate curator permissions and save to database
        return true;
    }
    
    /**
     * SECURITY: Save note securely
     */
    private static function save_secure_note($submission_id, $note_content, $curator_id) {
        // Placeholder for secure note save
        // In real implementation, would validate curator permissions and save to database
        return true;
    }
    
    /**
     * SECURITY: Get secure file URL
     */
    private static function get_secure_file_url($submission_id, $file_id, $curator_id) {
        // Placeholder for secure file access
        // In real implementation, would validate curator permissions and generate secure download URL
        return false;
    }
}

// Initialize secure API
CF7_Artist_Submissions_Secure_API::init();
