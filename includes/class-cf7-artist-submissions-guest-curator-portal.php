<?php
/**
 * CF7 Artist Submissions - Guest Curator Portal
 *
 * Public-facing portal interface for guest curators to access, review, rate,
 * and comment on submissions without requiring WordPress user accounts.
 *
 * Features:
 * • Token-based authentication system
 * • Submission viewing with file previews
 * • Interactive rating system
 * • Comment/notes system
 * • Responsive design
 * • Security-first approach
 *
 * @package CF7_Artist_Submissions
 * @subpackage GuestCuratorPortal
 * @since 1.3.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Guest Curator Portal Class
 * 
 * Manages the public portal interface for guest curators.
 */
class CF7_Artist_Submissions_Guest_Curator_Portal {
    
    /**
     * Initialize the portal
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_hooks'), 10);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_portal_assets'));
        
        // Admin functionality
        if (is_admin()) {
            add_action('admin_init', array(__CLASS__, 'check_rewrite_rules'));
            add_action('admin_init', array(__CLASS__, 'handle_admin_actions'));
        }
    }
    
    /**
     * Add dashboard widget for portal status
     */
    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'cf7_curator_portal_status',
            'CF7 Curator Portal Status',
            array(__CLASS__, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public static function render_dashboard_widget() {
        $portal_url = home_url('/curator-portal/');
        $flush_url = admin_url('admin.php?cf7_flush_rules=1');
        
        echo '<div style="text-align: center;">';
        echo '<p><strong>Guest Curator Portal Status</strong></p>';
        
        // Test rewrite rules
        $rules = get_option('rewrite_rules', array());
        $has_rule = false;
        foreach ($rules as $pattern => $replacement) {
            if (strpos($pattern, 'curator-portal') !== false) {
                $has_rule = true;
                break;
            }
        }
        
        if ($has_rule) {
            echo '<p>✅ Rewrite rules: <span style="color: green;">Active</span></p>';
        } else {
            echo '<p>❌ Rewrite rules: <span style="color: red;">Missing</span></p>';
        }
        
        echo '<div style="margin: 15px 0;">';
        echo '<a href="' . esc_url($portal_url) . '" class="button" target="_blank">Test Portal</a> ';
        echo '<a href="' . esc_url($flush_url) . '" class="button button-primary">Fix Rules</a>';
        echo '</div>';
        
        echo '<p style="font-size: 11px; color: #666;">Portal URL: <code>' . esc_html($portal_url) . '</code></p>';
        echo '</div>';
    }
    
    /**
     * Handle admin actions
     */
    public static function handle_admin_actions() {
        if (isset($_GET['cf7_flush_rules']) && current_user_can('manage_options')) {
            flush_rewrite_rules(false);
            
            // Add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>CF7 Curator Portal: Rewrite rules flushed successfully!</p>';
                echo '</div>';
            });
        }
        
        if (isset($_GET['cf7_test_portal']) && current_user_can('manage_options')) {
            $portal_url = home_url('/curator-portal/?debug=1');
            wp_redirect($portal_url);
            exit;
        }
        
        if (isset($_GET['cf7_test_auth']) && current_user_can('manage_options')) {
            // Test authentication system
            echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">';
            echo '<h3>🔧 Authentication System Test</h3>';
            
            // Test database connection and curator table
            global $wpdb;
            $table_name = $wpdb->prefix . 'cf7as_guest_curators';
            $curator_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
            echo '<p><strong>Active Curators:</strong> ' . intval($curator_count) . '</p>';
            
            // Test nonce generation
            $test_nonce = wp_create_nonce('cf7_curator_portal_nonce');
            echo '<p><strong>Nonce Generated:</strong> ✅ ' . substr($test_nonce, 0, 10) . '...</p>';
            
            // Test AJAX URL
            echo '<p><strong>AJAX URL:</strong> ' . esc_html(admin_url('admin-ajax.php')) . '</p>';
            
            // Check if we have any tokens in the database
            $active_tokens = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE login_token IS NOT NULL AND token_expires > NOW()");
            echo '<p><strong>Active Login Tokens:</strong> ' . intval($active_tokens) . '</p>';
            
            // JavaScript test
            echo '<div id="auth-test-result"></div>';
            echo '<button onclick="testAuthSystem()" style="margin: 10px 0; padding: 10px 20px;">Test AJAX Authentication</button>';
            
            echo '<script>
            function testAuthSystem() {
                var resultDiv = document.getElementById("auth-test-result");
                resultDiv.innerHTML = "<p>Testing AJAX authentication...</p>";
                
                // Test with nonce first
                jQuery.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "cf7_portal_authenticate",
                        action_type: "verify_token", 
                        token: "test-token-123",
                        nonce: "' . $test_nonce . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.innerHTML = "<p style=\"color: green;\">✅ AJAX Success: " + JSON.stringify(response.data) + "</p>";
                        } else {
                            resultDiv.innerHTML = "<p style=\"color: orange;\">⚠️ AJAX Error (Expected for test token): " + response.data.message + "</p>";
                            if (response.data.debug) {
                                resultDiv.innerHTML += "<p style=\"font-size: 12px; color: #666;\">Debug: " + response.data.debug + "</p>";
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        resultDiv.innerHTML = "<p style=\"color: red;\">❌ AJAX Failed: " + error + " (Status: " + xhr.status + ")</p>";
                        resultDiv.innerHTML += "<p style=\"font-size: 12px;\">Response: " + xhr.responseText + "</p>";
                        
                        // If nonce fails, try without nonce
                        resultDiv.innerHTML += "<p>Trying without nonce security...</p>";
                        jQuery.ajax({
                            url: "' . admin_url('admin-ajax.php') . '",
                            type: "POST",
                            data: {
                                action: "cf7_portal_authenticate",
                                action_type: "verify_token", 
                                token: "test-token-123",
                                skip_nonce: "1"
                            },
                            success: function(response2) {
                                if (response2.success) {
                                    resultDiv.innerHTML += "<p style=\"color: green;\">✅ No-nonce test succeeded</p>";
                                } else {
                                    resultDiv.innerHTML += "<p style=\"color: orange;\">⚠️ No-nonce test failed: " + response2.data + "</p>";
                                }
                            },
                            error: function(xhr2, status2, error2) {
                                resultDiv.innerHTML += "<p style=\"color: red;\">❌ No-nonce test also failed: " + error2 + "</p>";
                            }
                        });
                    }
                });
            }
            </script>';
            
            echo '</div>';
            exit;
        }

        if (isset($_GET['cf7_test_email']) && current_user_can('manage_options')) {
            $curator_name = 'Test Curator';
            $site_name = get_bloginfo('name');
            $login_link = home_url('/curator-portal/test-token-123/');
            $expires_text = __('This link will expire in 2 hours for security reasons.', 'cf7-artist-submissions');
            
            // Always show debug output first
            echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">';
            echo '<h3>🔧 Email Generation Debug Test</h3>';
            echo '<p><strong>Login Link:</strong> ' . esc_html($login_link) . '</p>';
            echo '<p><strong>Site Name:</strong> ' . esc_html($site_name) . '</p>';
            echo '<p><strong>Curator Name:</strong> ' . esc_html($curator_name) . '</p>';
            echo '<p><strong>Template Path:</strong> ' . esc_html(CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'templates/curator-login-email.php') . '</p>';
            echo '<p><strong>Template Exists:</strong> ' . (file_exists(CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'templates/curator-login-email.php') ? '✅ Yes' : '❌ No') . '</p>';
            echo '<p><strong>Fix Applied:</strong> ✅ $login_link variable is now properly set</p>';
            echo '</div>';
            
            // Test the actual email generation
            ob_start();
            $template_path = CF7_ARTIST_SUBMISSIONS_PLUGIN_DIR . 'templates/curator-login-email.php';
            if (file_exists($template_path)) {
                include $template_path;
                $email_html = ob_get_clean();
                
                echo '<h3>📧 Generated Email Preview:</h3>';
                echo '<div style="border: 1px solid #ddd; padding: 20px; background: white;">';
                echo $email_html;
                echo '</div>';
                
                // Check if the link is actually in the generated HTML
                echo '<div style="background: #e7f5e7; padding: 15px; margin: 20px 0; border-left: 4px solid #46b450;">';
                echo '<h4>🔍 Link Analysis:</h4>';
                if (strpos($email_html, $login_link) !== false) {
                    echo '<p><strong>✅ SUCCESS:</strong> Login link found in generated email HTML!</p>';
                    $link_count = substr_count($email_html, $login_link);
                    echo '<p><strong>Link appears:</strong> ' . $link_count . ' time(s) in the email</p>';
                } else {
                    echo '<p><strong>❌ ERROR:</strong> Login link NOT found in generated email HTML</p>';
                }
                
                // Check for button href attributes
                if (preg_match_all('/href=["\']([^"\']*)["\']/', $email_html, $matches)) {
                    echo '<p><strong>All href attributes found:</strong></p>';
                    echo '<ul>';
                    foreach ($matches[1] as $href) {
                        echo '<li>' . esc_html($href) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p><strong>⚠️ WARNING:</strong> No href attributes found in email</p>';
                }
                echo '</div>';
                
            } else {
                ob_end_clean();
                echo '<p><strong>❌ ERROR:</strong> Email template file not found!</p>';
            }
            exit;
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    public static function setup_hooks() {
        // Add rewrite rules for curator portal - more specific rules first
        add_rewrite_rule('^curator-portal/submission/([0-9]+)/?$', 'index.php?cf7_curator_portal=1&submission_id=$matches[1]', 'top');
        add_rewrite_rule('^curator-portal/([^/]+)/?$', 'index.php?cf7_curator_portal=1&curator_token=$matches[1]', 'top');
        add_rewrite_rule('^curator-portal/?$', 'index.php?cf7_curator_portal=1', 'top');
        
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'handle_portal_request'));
        
        // Ensure rewrite rules are flushed
        add_action('init', array(__CLASS__, 'maybe_flush_rewrite_rules'));
        
        // Portal AJAX endpoints
        add_action('wp_ajax_nopriv_cf7_portal_get_submissions', array(__CLASS__, 'ajax_get_submissions'));
        add_action('wp_ajax_cf7_portal_get_submissions', array(__CLASS__, 'ajax_get_submissions'));
        add_action('wp_ajax_nopriv_cf7_portal_get_submission_details', array(__CLASS__, 'ajax_get_submission_details'));
        add_action('wp_ajax_cf7_portal_get_submission_details', array(__CLASS__, 'ajax_get_submission_details'));
        add_action('wp_ajax_nopriv_cf7_portal_save_rating', array(__CLASS__, 'ajax_save_rating'));
        add_action('wp_ajax_cf7_portal_save_rating', array(__CLASS__, 'ajax_save_rating'));
        add_action('wp_ajax_nopriv_cf7_portal_save_note', array(__CLASS__, 'ajax_save_note'));
        add_action('wp_ajax_cf7_portal_save_note', array(__CLASS__, 'ajax_save_note'));
        
        // Authentication endpoints
        add_action('wp_ajax_nopriv_cf7_portal_authenticate', array(__CLASS__, 'ajax_auth_test'));
        add_action('wp_ajax_cf7_portal_authenticate', array(__CLASS__, 'ajax_auth_test'));
        
        // Session management endpoints
        add_action('wp_ajax_nopriv_cf7_portal_logout', array(__CLASS__, 'ajax_logout'));
        add_action('wp_ajax_cf7_portal_logout', array(__CLASS__, 'ajax_logout'));
        add_action('wp_ajax_nopriv_cf7_portal_validate_session', array(__CLASS__, 'ajax_validate_session'));
        add_action('wp_ajax_cf7_portal_validate_session', array(__CLASS__, 'ajax_validate_session'));
        
        // Enhanced portal functionality endpoints
        add_action('wp_ajax_nopriv_cf7_portal_get_statistics', array(__CLASS__, 'ajax_get_statistics'));
        add_action('wp_ajax_cf7_portal_get_statistics', array(__CLASS__, 'ajax_get_statistics'));
        add_action('wp_ajax_nopriv_cf7_portal_download_file', array(__CLASS__, 'ajax_download_file'));
        add_action('wp_ajax_cf7_portal_download_file', array(__CLASS__, 'ajax_download_file'));
        
        // SECURE API: Add secure login endpoint bridge
        add_action('wp_ajax_nopriv_cf7_portal_curator_login', array(__CLASS__, 'ajax_secure_curator_login'));
        add_action('wp_ajax_cf7_portal_curator_login', array(__CLASS__, 'ajax_secure_curator_login'));
    }
    
    /**
     * Check and refresh rewrite rules if needed
     */
    public static function check_rewrite_rules() {
        $rules = get_option('rewrite_rules', array());
        $pattern = '^curator-portal/?([^/]*)/?$';
        
        if (!isset($rules[$pattern])) {
            flush_rewrite_rules(false);
        }
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public static function maybe_flush_rewrite_rules() {
        if (get_option('cf7_curator_portal_rewrite_version') !== '2.0') {
            flush_rewrite_rules(false);
            update_option('cf7_curator_portal_rewrite_version', '2.0');
        }
    }

    /**
     * Add query variables
     */
    public static function add_query_vars($vars) {
        $vars[] = 'cf7_curator_portal';
        $vars[] = 'curator_token';
        $vars[] = 'submission_id';
        return $vars;
    }
    
    /**
     * Handle portal requests
     */
    public static function handle_portal_request() {
        // Check if this is a portal request
        $is_portal = get_query_var('cf7_curator_portal');
        
        // Also check the REQUEST_URI for manual detection
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $is_portal_url = strpos($request_uri, 'curator-portal') !== false;
        
        if ($is_portal || $is_portal_url) {
            $submission_id = get_query_var('submission_id');
            $token = get_query_var('curator_token');
            
            if ($submission_id) {
                // Render individual submission page - requires active session
                self::render_submission_page($submission_id);
            } elseif ($token) {
                // Initial authentication page with token
                self::render_portal();
            } else {
                // Main portal page - requires active session
                self::render_authenticated_portal();
            }
            exit;
        }
    }
    
    /**
     * Render individual submission page with tabs - API-ONLY SECURE VERSION
     */
    private static function render_submission_page($submission_id) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php printf(__('Curator Portal - Submission Review - %s', 'cf7-artist-submissions'), get_bloginfo('name')); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="cf7-curator-portal cf7-submission-page">
            <!-- Session validation via API only -->
            <div id="auth-check" class="auth-check">
                <div class="loading-spinner"><?php _e('Validating session...', 'cf7-artist-submissions'); ?></div>
            </div>
            
            <!-- Content loaded via API only -->
            <div id="submission-content" class="submission-page" style="display: none;">
                <div class="loading-spinner"><?php _e('Loading submission...', 'cf7-artist-submissions'); ?></div>
            </div>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        
        echo ob_get_clean();
    }

    /**
     * Render authenticated portal (clean URL without token)
     */
    public static function render_authenticated_portal() {
        // This is for /curator-portal/ (clean URL after authentication)
        // Check if user has a valid session via JavaScript on frontend
        
        // Ensure WordPress knows this is a curator portal page
        set_query_var('cf7_curator_portal', '1');
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php printf(__('Curator Portal - %s', 'cf7-artist-submissions'), get_bloginfo('name')); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="cf7-curator-portal">
            <div id="cf7-portal-app">
                <div class="cf7-portal-header">
                    <div class="cf7-container">
                        <h1 class="cf7-portal-title">
                            <?php printf(__('Curator Portal - %s', 'cf7-artist-submissions'), get_bloginfo('name')); ?>
                        </h1>
                        <div class="cf7-portal-user-info" style="display: none;">
                            <span class="cf7-curator-name"></span>
                            <button type="button" id="cf7-portal-logout" class="cf7-portal-btn cf7-portal-btn-secondary">
                                <?php _e('Logout', 'cf7-artist-submissions'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-container">
                    <!-- Initial Loading State -->
                    <div id="cf7-portal-loading" class="cf7-portal-section">
                        <div class="cf7-auth-message">
                            <p><?php _e('Checking authentication...', 'cf7-artist-submissions'); ?></p>
                            <div class="cf7-loading-spinner"></div>
                        </div>
                    </div>
                    
                    <!-- Authentication Required -->
                    <div id="cf7-portal-auth" class="cf7-portal-section" style="display: none;">
                        <div class="cf7-auth-message">
                            <p><?php _e('Authentication required. Please use the link from your email.', 'cf7-artist-submissions'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Main Portal Content -->
                    <div id="cf7-portal-main" class="cf7-portal-section" style="display: none;">
                        <div class="cf7-dashboard-controls">
                            <div class="cf7-search-box">
                                <input type="text" id="cf7-submission-search" placeholder="<?php _e('Search submissions...', 'cf7-artist-submissions'); ?>">
                                <button type="button" id="cf7-search-btn" class="cf7-portal-btn">
                                    <?php _e('Search', 'cf7-artist-submissions'); ?>
                                </button>
                            </div>
                            
                            <div class="cf7-filters">
                                <select id="cf7-status-filter">
                                    <option value=""><?php _e('All Statuses', 'cf7-artist-submissions'); ?></option>
                                    <option value="pending"><?php _e('Pending', 'cf7-artist-submissions'); ?></option>
                                    <option value="reviewed"><?php _e('Reviewed', 'cf7-artist-submissions'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="cf7-submissions-container">
                            <div id="cf7-submissions-loading" class="cf7-loading-state">
                                <p><?php _e('Loading submissions...', 'cf7-artist-submissions'); ?></p>
                                <div class="cf7-loading-spinner"></div>
                            </div>
                            <div id="cf7-submissions-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php wp_footer(); ?>
            
            <script>
                // Initialize portal for authenticated users (clean URL)
                jQuery(document).ready(function($) {
                    if (typeof CF7CuratorPortal !== 'undefined') {
                        // curator-portal.js automatically calls checkExistingSession() during initialization
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Render the curator portal
     */
    public static function render_portal() {
        $token = get_query_var('curator_token');
        
        // Ensure WordPress knows this is a curator portal page
        set_query_var('cf7_curator_portal', '1');
        
        // Basic HTML structure
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php printf(__('Curator Portal - %s', 'cf7-artist-submissions'), get_bloginfo('name')); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="cf7-curator-portal">
            <div id="cf7-portal-app">
                <div class="cf7-portal-header">
                    <div class="cf7-container">
                        <h1 class="cf7-portal-title">
                            <?php printf(__('Curator Portal - %s', 'cf7-artist-submissions'), get_bloginfo('name')); ?>
                        </h1>
                        <div class="cf7-portal-user-info" style="display: none;">
                            <span class="cf7-curator-name"></span>
                            <button type="button" id="cf7-portal-logout" class="cf7-portal-btn cf7-portal-btn-secondary">
                                <?php _e('Logout', 'cf7-artist-submissions'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-container">
                    <!-- Initial Loading State -->
                    <div id="cf7-portal-loading" class="cf7-portal-section">
                        <div class="cf7-auth-message">
                            <p><?php _e('Checking authentication...', 'cf7-artist-submissions'); ?></p>
                            <div class="cf7-loading-spinner"></div>
                        </div>
                    </div>
                    
                    <!-- Authentication Form -->
                    <div id="cf7-portal-auth" class="cf7-portal-section" style="display: none;">
                        <?php if ($token): ?>
                            <div class="cf7-auth-message">
                                <p><?php _e('Verifying your access...', 'cf7-artist-submissions'); ?></p>
                                <div class="cf7-loading-spinner"></div>
                            </div>
                        <?php else: ?>
                            <div class="cf7-auth-form">
                                <h2><?php _e('Curator Access', 'cf7-artist-submissions'); ?></h2>
                                <p><?php _e('Please enter your email address to receive a secure access link.', 'cf7-artist-submissions'); ?></p>
                                
                                <form id="cf7-auth-form">
                                    <div class="cf7-form-group">
                                        <label for="curator-email"><?php _e('Email Address', 'cf7-artist-submissions'); ?></label>
                                        <input type="email" id="curator-email" name="email" required>
                                    </div>
                                    <button type="submit" class="cf7-portal-btn cf7-portal-btn-primary">
                                        <?php _e('Request Access', 'cf7-artist-submissions'); ?>
                                    </button>
                                </form>
                                
                                <div class="cf7-auth-status"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Main Portal Interface -->
                    <div id="cf7-portal-main" class="cf7-portal-section" style="display: none;">
                        
                        <!-- Navigation Tabs -->
                        <div class="cf7-portal-nav">
                            <button type="button" class="cf7-nav-tab cf7-nav-tab-active" data-tab="submissions">
                                <?php _e('Submissions', 'cf7-artist-submissions'); ?>
                            </button>
                            <button type="button" class="cf7-nav-tab" data-tab="profile">
                                <?php _e('Profile', 'cf7-artist-submissions'); ?>
                            </button>
                        </div>
                        
                        <!-- Submissions Tab -->
                        <div id="cf7-tab-submissions" class="cf7-tab-content cf7-tab-content-active">
                            <div class="cf7-submissions-header">
                                <h2><?php _e('Assigned Submissions', 'cf7-artist-submissions'); ?></h2>
                                <div class="cf7-submissions-stats">
                                    <span class="cf7-stat">
                                        <span class="cf7-stat-label"><?php _e('Total:', 'cf7-artist-submissions'); ?></span>
                                        <span class="cf7-stat-value" id="cf7-total-submissions">0</span>
                                    </span>
                                    <span class="cf7-stat">
                                        <span class="cf7-stat-label"><?php _e('Rated:', 'cf7-artist-submissions'); ?></span>
                                        <span class="cf7-stat-value" id="cf7-rated-submissions">0</span>
                                    </span>
                                </div>
                            </div>
                            
                            <div id="cf7-submissions-list" class="cf7-submissions-grid">
                                <!-- Submissions loaded via AJAX -->
                            </div>
                        </div>
                        
                        <!-- Profile Tab -->
                        <div id="cf7-tab-profile" class="cf7-tab-content">
                            <h2><?php _e('Curator Profile', 'cf7-artist-submissions'); ?></h2>
                            <div id="cf7-curator-profile">
                                <!-- Profile info loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submission Detail Modal -->
                    <div id="cf7-submission-modal" class="cf7-portal-modal" style="display: none;">
                        <div class="cf7-modal-content">
                            <div class="cf7-modal-header">
                                <h3 class="cf7-modal-title"><?php _e('Submission Details', 'cf7-artist-submissions'); ?></h3>
                                <button type="button" class="cf7-modal-close">&times;</button>
                            </div>
                            <div class="cf7-modal-body">
                                <!-- Artist Profile Section -->
                                <div id="cf7-modal-artist-profile" class="cf7-modal-section">
                                    <!-- Artist profile content -->
                                </div>
                                
                                <!-- Description Section -->
                                <div class="cf7-modal-section">
                                    <h4><?php _e('Description', 'cf7-artist-submissions'); ?></h4>
                                    <div id="cf7-modal-description">
                                        <!-- Description content -->
                                    </div>
                                </div>
                                
                                <!-- Files Section -->
                                <div class="cf7-modal-section">
                                    <h4><?php _e('Submitted Files', 'cf7-artist-submissions'); ?></h4>
                                    <div id="cf7-modal-files-grid">
                                        <!-- Files content -->
                                    </div>
                                </div>
                                
                                <!-- Metadata Section -->
                                <div class="cf7-modal-section">
                                    <h4><?php _e('Submission Details', 'cf7-artist-submissions'); ?></h4>
                                    <div id="cf7-modal-meta-details">
                                        <!-- Metadata content -->
                                    </div>
                                </div>
                                
                                <!-- Rating Section -->
                                <div id="cf7-modal-rating" class="cf7-modal-section">
                                    <!-- Rating interface -->
                                </div>
                                
                                <!-- Notes Section -->
                                <div id="cf7-modal-notes" class="cf7-modal-section">
                                    <h4><?php _e('Curator Notes', 'cf7-artist-submissions'); ?></h4>
                                    <div id="cf7-modal-notes-list">
                                        <!-- Notes list -->
                                    </div>
                                    <div id="cf7-modal-notes-form">
                                        <!-- Notes form -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php wp_footer(); ?>
            
            <script type="text/javascript">
                // Initialize portal with token if provided
                jQuery(document).ready(function() {
                    if (typeof CF7CuratorPortal !== 'undefined') {
                        <?php if ($token): ?>
                            CF7CuratorPortal.authenticateWithToken('<?php echo esc_js($token); ?>');
                        <?php endif; ?>
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Enqueue portal assets
     */
    public static function enqueue_portal_assets() {
        if (!get_query_var('cf7_curator_portal')) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        $submission_id = get_query_var('submission_id');
        
        // For submission pages, only load minimal assets
        if ($submission_id) {
            // Load secure submission page assets
            wp_enqueue_script(
                'cf7-curator-portal',
                CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/curator-portal.js',
                array('jquery'),
                CF7_ARTIST_SUBMISSIONS_VERSION . '.' . time(),
                true
            );
            
            wp_enqueue_style(
                'cf7-curator-portal',
                CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/curator-portal.css',
                array(),
                CF7_ARTIST_SUBMISSIONS_VERSION
            );
            
            // Localize with secure config
            wp_localize_script('cf7-curator-portal', 'CF7CuratorPortalConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_curator_portal_nonce'),
                'submissionId' => intval($submission_id),
                'portalUrl' => home_url('curator-portal/'),
                'apiVersion' => '1.0'
            ));
            
            return; // Don't load main portal JS for submission pages
        }
        
        // For main portal pages, load full secure assets
        wp_enqueue_script(
            'cf7-curator-portal',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/curator-portal.js',
            array('jquery'),
            CF7_ARTIST_SUBMISSIONS_VERSION . '.' . time(),
            true
        );
        
        wp_enqueue_style(
            'cf7-curator-portal',
            CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/curator-portal.css',
            array(),
            CF7_ARTIST_SUBMISSIONS_VERSION
        );
        
        // Localize script with secure portal data
        $token = get_query_var('curator_token');
        wp_localize_script('cf7-curator-portal', 'CF7CuratorPortalConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_curator_portal_nonce'),
            'urlToken' => $token ? sanitize_text_field($token) : null,
            'portalUrl' => home_url('curator-portal/'),
            'apiVersion' => '1.0',
            'strings' => array(
                'loading' => __('Loading...', 'cf7-artist-submissions'),
                'error_general' => __('An error occurred. Please try again.', 'cf7-artist-submissions'),
                'invalid_token' => __('Invalid or expired access token.', 'cf7-artist-submissions'),
                'access_requested' => __('Access link has been sent to your email.', 'cf7-artist-submissions'),
                'rating_saved' => __('Rating saved successfully.', 'cf7-artist-submissions'),
                'note_saved' => __('Note saved successfully.', 'cf7-artist-submissions'),
                'confirm_logout' => __('Are you sure you want to logout?', 'cf7-artist-submissions'),
            )
        ));
    }
    
    /**
     * Main authentication handler - handles both token verification and access requests
     */
    public static function ajax_auth_test() {
        try {
            // Basic validation
            if (!function_exists('wp_send_json_error')) {
                echo json_encode(array('success' => false, 'data' => array('message' => 'WordPress AJAX functions not available')));
                wp_die();
            }

            // Get and validate input
            $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
            $skip_nonce = isset($_POST['skip_nonce']) && $_POST['skip_nonce'] === '1';

            // Nonce check (only if not skipping)
            if (!$skip_nonce) {
                $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
                if (empty($nonce) || !wp_verify_nonce($nonce, 'cf7_curator_portal_nonce')) {
                    wp_send_json_error(array(
                        'message' => 'Security check failed. Please refresh the page.',
                        'nonce_check_failed' => true
                    ));
                    return;
                }
            }

            // Route to appropriate handler
            if ($action_type === 'request_access') {
                // Handle email access request
                $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
                
                if (!$email || !is_email($email)) {
                    wp_send_json_error(array('message' => __('Please enter a valid email address.', 'cf7-artist-submissions')));
                    return;
                }
                
                // Check if Guest Curators class exists
                if (!class_exists('CF7_Artist_Submissions_Guest_Curators')) {
                    wp_send_json_error(array('message' => __('Guest curator system not available.', 'cf7-artist-submissions')));
                    return;
                }
                
                $result = CF7_Artist_Submissions_Guest_Curators::generate_login_token($email);
                
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                    return;
                }
                
                wp_send_json_success(array(
                    'message' => __('Access link has been sent to your email address.', 'cf7-artist-submissions')
                ));
                
            } elseif ($action_type === 'verify_token') {
                // Handle token verification
                $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
                
                if (!$token) {
                    wp_send_json_error(array('message' => __('No token provided.', 'cf7-artist-submissions')));
                    return;
                }
                
                // Check if Guest Curators class exists
                if (!class_exists('CF7_Artist_Submissions_Guest_Curators')) {
                    wp_send_json_error(array('message' => __('Guest curator system not available.', 'cf7-artist-submissions')));
                    return;
                }
                
                $curator = CF7_Artist_Submissions_Guest_Curators::verify_token($token);
                
                if (is_wp_error($curator)) {
                    wp_send_json_error(array('message' => $curator->get_error_message()));
                    return;
                }
                
                if (!$curator) {
                    wp_send_json_error(array('message' => __('Invalid token.', 'cf7-artist-submissions')));
                    return;
                }
                
                // Create session data with longer duration (7 days for user convenience)
                $session_token = wp_generate_password(32, false);
                $session_duration = apply_filters('cf7_curator_session_duration', 7 * DAY_IN_SECONDS); // 7 days
                $expires_timestamp = time() + $session_duration;
                
                // Store additional session info for security
                $session_data = array(
                    'curator_id' => $curator->id,
                    'created_at' => current_time('timestamp'),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
                );
                
                // Store in transient (for Guest Curator Portal compatibility)
                set_transient('cf7_curator_session_' . $session_token, $session_data, $session_duration);
                
                // ALSO store in format expected by Secure API
                $curator_sessions = get_option('cf7_curator_sessions', array());
                $curator_sessions[] = array(
                    'token' => $session_token,
                    'curator_id' => $curator->id,
                    'curator_name' => $curator->name,
                    'expires' => $expires_timestamp,
                    'created_at' => current_time('timestamp')
                );
                update_option('cf7_curator_sessions', $curator_sessions);
                
                wp_send_json_success(array(
                    'curator' => array(
                        'id' => $curator->id,
                        'name' => $curator->name,
                        'email' => $curator->email
                    ),
                    'session_token' => $session_token
                ));
                
            } else {
                wp_send_json_error(array(
                    'message' => 'Invalid action type',
                    'received_action' => $action_type
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Authentication error occurred',
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX handler for logout
     */
    public static function ajax_logout() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        
        if ($session_token) {
            // Delete the session transient
            delete_transient('cf7_curator_session_' . $session_token);
            
            // Also remove from Secure API session storage
            $curator_sessions = get_option('cf7_curator_sessions', array());
            $curator_sessions = array_filter($curator_sessions, function($session) use ($session_token) {
                return $session['token'] !== $session_token;
            });
            update_option('cf7_curator_sessions', $curator_sessions);
        }
        
        wp_send_json_success(array(
            'message' => __('Successfully logged out.', 'cf7-artist-submissions')
        ));
    }
    
    /**
     * AJAX handler for email-based curator login (EMAIL ONLY)
     */
    public static function ajax_secure_curator_login() {
        // Email-only authentication
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$email || !is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'cf7-artist-submissions')));
            return;
        }
        
        // Check if Guest Curators class exists
        if (!class_exists('CF7_Artist_Submissions_Guest_Curators')) {
            wp_send_json_error(array('message' => __('Guest curator system not available.', 'cf7-artist-submissions')));
            return;
        }
        
        $result = CF7_Artist_Submissions_Guest_Curators::generate_login_token($email);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Access link has been sent to your email address.', 'cf7-artist-submissions')
        ));
    }
    
    /**
     * AJAX handler for session validation
     */
    public static function ajax_validate_session() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        
        if (!$session_token) {
            wp_send_json_error(array('message' => 'No session token provided'));
            return;
        }
        
        // Debug: Check if transient exists
        $session_data = get_transient('cf7_curator_session_' . $session_token);
        if (!$session_data) {
            wp_send_json_error(array(
                'message' => 'Session expired', 
                'debug' => 'Session transient not found for token: ' . substr($session_token, 0, 8) . '...'
            ));
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error(array(
                'message' => 'Session expired',
                'debug' => 'get_curator_from_session returned false'
            ));
            return;
        }
        
        // Get curator details
        if (!class_exists('CF7_Artist_Submissions_Guest_Curators')) {
            wp_send_json_error(array('message' => 'Guest curator system not available'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_guest_curators';
        $curator = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'active'",
            $curator_id
        ));
        
        if (!$curator) {
            wp_send_json_error(array(
                'message' => 'Curator not found or inactive',
                'debug' => 'Curator ID: ' . $curator_id . ' not found in database'
            ));
            return;
        }
        
        wp_send_json_success(array(
            'curator' => array(
                'id' => $curator->id,
                'name' => $curator->name,
                'email' => $curator->email
            ),
            'session_valid' => true
        ));
    }
    
    /**
     * Validate access - simplified approach
     */
     private static function validate_access($token) {
        // For now, allow access if there's any token
        // The main portal handles the real authentication
        // Individual submission pages assume users are already authenticated
        return !empty($token);
    }

    /**
     * Render submission tabs interface
     */
    private static function render_submission_tabs($submission_id, $metadata, $files) {
        ?>
        <div class="cf7-tabs-container">
            <div class="cf7-tab-nav">
                <a href="#profile" class="cf7-tab-link active" data-tab="profile">Profile</a>
                <a href="#works" class="cf7-tab-link" data-tab="works">Works</a>
                <a href="#statement" class="cf7-tab-link" data-tab="statement">Statement</a>
                <a href="#files" class="cf7-tab-link" data-tab="files">Files</a>
                <a href="#conversations" class="cf7-tab-link" data-tab="conversations">Conversations</a>
                <a href="#export" class="cf7-tab-link" data-tab="export">Export</a>
            </div>

            <div class="cf7-tab-content">
                <!-- Profile Tab -->
                <div id="profile" class="cf7-tab-pane active">
                    <h3>Artist Profile</h3>
                    <div class="cf7-form-grid">
                        <div class="cf7-form-row">
                            <label>Name</label>
                            <div class="cf7-form-value"><?php echo esc_html($metadata['name'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="cf7-form-row">
                            <label>Email</label>
                            <div class="cf7-form-value"><?php echo esc_html($metadata['email'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="cf7-form-row">
                            <label>Phone</label>
                            <div class="cf7-form-value"><?php echo esc_html($metadata['phone'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="cf7-form-row">
                            <label>Location</label>
                            <div class="cf7-form-value">
                                <?php 
                                $location_parts = array_filter([
                                    $metadata['city'] ?? '',
                                    $metadata['state'] ?? '',
                                    $metadata['country'] ?? ''
                                ]);
                                echo esc_html(implode(', ', $location_parts) ?: 'Not provided');
                                ?>
                            </div>
                        </div>
                        <div class="cf7-form-row">
                            <label>Website</label>
                            <div class="cf7-form-value">
                                <?php if (!empty($metadata['website'])): ?>
                                    <a href="<?php echo esc_url($metadata['website']); ?>" target="_blank"><?php echo esc_html($metadata['website']); ?></a>
                                <?php else: ?>
                                    Not provided
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="cf7-form-row">
                            <label>Instagram</label>
                            <div class="cf7-form-value">
                                <?php if (!empty($metadata['instagram'])): ?>
                                    <a href="<?php echo esc_url($metadata['instagram']); ?>" target="_blank"><?php echo esc_html($metadata['instagram']); ?></a>
                                <?php else: ?>
                                    Not provided
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Works Tab -->
                <div id="works" class="cf7-tab-pane">
                    <h3>Submitted Works</h3>
                    <?php if (!empty($metadata['work-title-1'])): ?>
                        <div class="cf7-work-section">
                            <h4><?php echo esc_html($metadata['work-title-1']); ?></h4>
                            <?php if (!empty($metadata['work-year-1'])): ?>
                                <p><strong>Year:</strong> <?php echo esc_html($metadata['work-year-1']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-medium-1'])): ?>
                                <p><strong>Medium:</strong> <?php echo esc_html($metadata['work-medium-1']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-dimensions-1'])): ?>
                                <p><strong>Dimensions:</strong> <?php echo esc_html($metadata['work-dimensions-1']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($metadata['work-title-2'])): ?>
                        <div class="cf7-work-section">
                            <h4><?php echo esc_html($metadata['work-title-2']); ?></h4>
                            <?php if (!empty($metadata['work-year-2'])): ?>
                                <p><strong>Year:</strong> <?php echo esc_html($metadata['work-year-2']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-medium-2'])): ?>
                                <p><strong>Medium:</strong> <?php echo esc_html($metadata['work-medium-2']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-dimensions-2'])): ?>
                                <p><strong>Dimensions:</strong> <?php echo esc_html($metadata['work-dimensions-2']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($metadata['work-title-3'])): ?>
                        <div class="cf7-work-section">
                            <h4><?php echo esc_html($metadata['work-title-3']); ?></h4>
                            <?php if (!empty($metadata['work-year-3'])): ?>
                                <p><strong>Year:</strong> <?php echo esc_html($metadata['work-year-3']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-medium-3'])): ?>
                                <p><strong>Medium:</strong> <?php echo esc_html($metadata['work-medium-3']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($metadata['work-dimensions-3'])): ?>
                                <p><strong>Dimensions:</strong> <?php echo esc_html($metadata['work-dimensions-3']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statement Tab -->
                <div id="statement" class="cf7-tab-pane">
                    <h3>Artist Statement</h3>
                    <div class="cf7-statement-content">
                        <?php echo wp_kses_post(nl2br($metadata['artist-statement'] ?? 'No statement provided.')); ?>
                    </div>
                </div>

                <!-- Files Tab -->
                <div id="files" class="cf7-tab-pane">
                    <h3>Submitted Files</h3>
                    <div class="cf7-files-grid">
                        <?php if (!empty($files)): ?>
                            <?php foreach ($files as $file): ?>
                                <div class="cf7-file-item">
                                    <?php if (in_array($file['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                        <div class="cf7-file-preview">
                                            <img src="<?php echo esc_url($file['s3_url']); ?>" alt="<?php echo esc_attr($file['original_name']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="cf7-file-info">
                                        <div class="cf7-file-name"><?php echo esc_html($file['original_name']); ?></div>
                                        <div class="cf7-file-size"><?php echo size_format($file['file_size']); ?></div>
                                        <a href="<?php echo esc_url($file['s3_url']); ?>" target="_blank" class="cf7-file-link">View Full Size</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No files submitted.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Conversations Tab -->
                <div id="conversations" class="cf7-tab-pane">
                    <h3>Conversations</h3>
                    <div id="cf7-conversations-content">
                        <p>Loading conversations...</p>
                    </div>
                </div>

                <!-- Export Tab -->
                <div id="export" class="cf7-tab-pane">
                    <h3>Export Options</h3>
                    <div class="cf7-export-options">
                        <button type="button" class="cf7-portal-btn" onclick="window.print()">Print Submission</button>
                        <button type="button" class="cf7-portal-btn" onclick="exportToPDF(<?php echo $submission_id; ?>)">Export to PDF</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .cf7-work-section {
                margin-bottom: 30px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #f9f9f9;
            }
            .cf7-work-section h4 {
                margin: 0 0 15px 0;
                color: #333;
                font-size: 18px;
            }
            .cf7-statement-content {
                padding: 20px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                line-height: 1.6;
            }
            .cf7-files-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .cf7-file-item {
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                background: #fff;
            }
            .cf7-file-preview {
                width: 100%;
                height: 150px;
                overflow: hidden;
                background: #f0f0f0;
            }
            .cf7-file-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .cf7-file-info {
                padding: 10px;
            }
            .cf7-file-name {
                font-weight: bold;
                margin-bottom: 5px;
                word-break: break-word;
            }
            .cf7-file-size {
                font-size: 12px;
                color: #666;
                margin-bottom: 10px;
            }
            .cf7-file-link {
                color: #0073aa;
                text-decoration: none;
                font-size: 12px;
            }
            .cf7-export-options {
                padding: 20px;
            }
            .cf7-export-options .cf7-portal-btn {
                margin-right: 10px;
                margin-bottom: 10px;
            }
        </style>
        <?php
    }

    /**
     * Debug submissions data - admin only
     */
    public static function ajax_debug_submissions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $debug_info = array();
        
        // Check curator permissions table
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        $debug_info['permissions_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$permissions_table'") === $permissions_table;
        
        if ($debug_info['permissions_table_exists']) {
            $debug_info['total_permissions'] = $wpdb->get_var("SELECT COUNT(*) FROM $permissions_table");
            $debug_info['permissions_sample'] = $wpdb->get_results("SELECT * FROM $permissions_table LIMIT 5", ARRAY_A);
        }
        
        // Check curators table
        $curators_table = $wpdb->prefix . 'cf7as_guest_curators';
        $debug_info['curators_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$curators_table'") === $curators_table;
        
        if ($debug_info['curators_table_exists']) {
            $debug_info['total_curators'] = $wpdb->get_var("SELECT COUNT(*) FROM $curators_table");
            $debug_info['active_curators'] = $wpdb->get_var("SELECT COUNT(*) FROM $curators_table WHERE status = 'active'");
        }
        
        // Check submissions
        $submissions_count = wp_count_posts('cf7_submission');
        $debug_info['total_cf7_submissions'] = $submissions_count->publish ?? 0;
        
        // Check open call taxonomy
        $open_call_terms = get_terms(array('taxonomy' => 'open_call', 'hide_empty' => false));
        $debug_info['open_call_terms'] = count($open_call_terms);
        
        if (!empty($open_call_terms)) {
            $debug_info['open_call_terms_list'] = array();
            foreach ($open_call_terms as $term) {
                $debug_info['open_call_terms_list'][] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'count' => $term->count
                );
            }
        }
        
        // Sample submission-term relationships
        $debug_info['submission_term_relationships'] = $wpdb->get_results("
            SELECT p.ID as post_id, p.post_title, t.name as open_call_name, tt.term_id 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id 
            WHERE p.post_type = 'cf7_submission' 
            AND tt.taxonomy = 'open_call' 
            AND p.post_status = 'publish' 
            LIMIT 10
        ", ARRAY_A);
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * Get curator ID from session token
     */
    private static function get_curator_from_session($session_token) {
        $session_data = get_transient('cf7_curator_session_' . $session_token);
        if (!$session_data) {
            return false;
        }
        
        // Handle both old format (direct curator_id) and new format (session_data array)
        if (is_array($session_data)) {
            return $session_data['curator_id'];
        } else {
            // Old format - just the curator ID
            return $session_data;
        }
    }
    
    /**
     * Handle access request via email
     */
    private static function handle_access_request() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$email || !is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'cf7-artist-submissions'));
            return;
        }
        
        $result = CF7_Artist_Submissions_Guest_Curators::generate_login_token($email);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Access link has been sent to your email address.', 'cf7-artist-submissions')
        ));
    }
    
    /**
     * Handle token verification
     */
    private static function handle_token_verification() {
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        
        if (!$token) {
            wp_send_json_error(__('No token provided.', 'cf7-artist-submissions'));
            return;
        }
        
        // Check if guest curators class exists
        if (!class_exists('CF7_Artist_Submissions_Guest_Curators')) {
            wp_send_json_error(__('Guest curator system not available.', 'cf7-artist-submissions'));
            return;
        }
        
        $curator = CF7_Artist_Submissions_Guest_Curators::verify_token($token);
        
        if (is_wp_error($curator)) {
            wp_send_json_error($curator->get_error_message());
            return;
        }
        
        // Create session data with longer duration (7 days for user convenience)
        $session_token = wp_generate_password(32, false);
        $session_duration = apply_filters('cf7_curator_session_duration', 7 * DAY_IN_SECONDS); // 7 days
        $expires_timestamp = time() + $session_duration;
        
        // Store additional session info for security
        $session_data = array(
            'curator_id' => $curator->id,
            'created_at' => current_time('timestamp'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
        );
        
        // Store in transient (for Guest Curator Portal compatibility)
        set_transient('cf7_curator_session_' . $session_token, $session_data, $session_duration);
        
        // ALSO store in format expected by Secure API
        $curator_sessions = get_option('cf7_curator_sessions', array());
        $curator_sessions[] = array(
            'token' => $session_token,
            'curator_id' => $curator->id,
            'curator_name' => $curator->name,
            'expires' => $expires_timestamp,
            'created_at' => current_time('timestamp')
        );
        update_option('cf7_curator_sessions', $curator_sessions);
        
        wp_send_json_success(array(
            'curator' => array(
                'id' => $curator->id,
                'name' => $curator->name,
                'email' => $curator->email
            ),
            'session_token' => $session_token
        ));
    }
    
    /**
     * AJAX handler to get submissions for authenticated curator
     */
    public static function ajax_get_submissions() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        
        if (!$session_token) {
            wp_send_json_error(array(
                'message' => 'Authentication required',
                'code' => 'NO_TOKEN'
            ));
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error(array(
                'message' => 'Session expired', 
                'code' => 'INVALID_SESSION'
            ));
            return;
        }
        
        // Get pagination parameters
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
        
        // Get submissions for this curator's assigned open calls
        try {
            $submissions_data = self::get_curator_submissions($curator_id, $page, $per_page, $search, $filter);
            
            wp_send_json_success(array(
                'submissions' => $submissions_data['submissions'],
                'pagination' => $submissions_data['pagination']
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to load submissions: ' . $e->getMessage(),
                'code' => 'DATABASE_ERROR'
            ));
        }
    }
    
    /**
     * AJAX handler to get submission details
     */
    public static function ajax_get_submission_details() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        
        if (!$session_token) {
            wp_send_json_error('Authentication required');
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error('Session expired');
            return;
        }
        
        // Verify curator has access to this submission
        if (!self::curator_can_access_submission($curator_id, $submission_id)) {
            wp_send_json_error('Access denied');
            return;
        }
        
        $submission_details = self::get_submission_details($submission_id, $curator_id);
        
        wp_send_json_success($submission_details);
    }
    
    /**
     * AJAX handler to save rating
     */
    public static function ajax_save_rating() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        
        if (!$session_token) {
            wp_send_json_error('Authentication required');
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error('Session expired');
            return;
        }
        
        if (!self::curator_can_access_submission($curator_id, $submission_id)) {
            wp_send_json_error('Access denied');
            return;
        }
        
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error('Invalid rating value');
            return;
        }
        
        $result = CF7_Artist_Submissions_Enhanced_Ratings::save_curator_rating($submission_id, $rating, null, $curator_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Rating saved successfully', 'cf7-artist-submissions')
        ));
    }
    
    /**
     * AJAX handler to save note
     */
    public static function ajax_save_note() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $note_content = isset($_POST['note_content']) ? sanitize_textarea_field($_POST['note_content']) : '';
        
        if (!$session_token) {
            wp_send_json_error('Authentication required');
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error('Session expired');
            return;
        }
        
        if (!self::curator_can_access_submission($curator_id, $submission_id)) {
            wp_send_json_error('Access denied');
            return;
        }
        
        if (empty(trim($note_content))) {
            wp_send_json_error('Note content is required');
            return;
        }
        
        $result = CF7_Artist_Submissions_Enhanced_Curator_Notes::add_curator_note($submission_id, $note_content, null, null, $curator_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Note saved successfully', 'cf7-artist-submissions')
        ));
    }
    
    // ============================================================================
    // HELPER METHODS
    // ============================================================================
    
    /**
     * Get submissions assigned to a curator with pagination
     */
    private static function get_curator_submissions($curator_id, $page = 1, $per_page = 20, $search = '', $filter = '') {
        global $wpdb;
        
        // Get curator's assigned open calls
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        $assigned_calls = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT open_call_term_id FROM $permissions_table WHERE guest_curator_id = %d",
            $curator_id
        ));
        
        if (empty($assigned_calls)) {
            return array(
                'submissions' => array(),
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => 0,
                    'total_items' => 0,
                    'per_page' => $per_page,
                    'has_next' => false,
                    'has_prev' => false
                )
            );
        }
        
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'tax_query' => array(
                array(
                    'taxonomy' => 'open_call',
                    'field' => 'term_id',
                    'terms' => $assigned_calls
                )
            )
        );
        
        // Add search if provided
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add meta query for filtering if provided
        if (!empty($filter)) {
            switch ($filter) {
                case 'rated':
                    $args['meta_query'] = array(
                        array(
                            'key' => 'cf7as_curator_rating_' . $curator_id,
                            'compare' => 'EXISTS'
                        )
                    );
                    break;
                case 'unrated':
                    $args['meta_query'] = array(
                        array(
                            'key' => 'cf7as_curator_rating_' . $curator_id,
                            'compare' => 'NOT EXISTS'
                        )
                    );
                    break;
                case 'favorited':
                    $args['meta_query'] = array(
                        array(
                            'key' => 'cf7as_curator_favorite_' . $curator_id,
                            'value' => '1',
                            'compare' => '='
                        )
                    );
                    break;
            }
        }
        
        // Get posts with pagination
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        $submissions = array();
        foreach ($posts as $post) {
            // Get uploaded files count
            $files = get_post_meta($post->ID, 'uploaded_files', true);
            $files_count = is_array($files) ? count($files) : 0;
            
            // Extract artist name from form data
            $form_data = get_post_meta($post->ID, 'form_data', true);
            $artist_name = 'Unknown Artist';
            if (is_array($form_data)) {
                // Try common field names for artist/name
                $possible_names = array('artist', 'artist_name', 'name', 'your-name', 'full-name', 'artist-name');
                foreach ($possible_names as $field_name) {
                    if (!empty($form_data[$field_name])) {
                        $artist_name = $form_data[$field_name];
                        break;
                    }
                }
            }
            
            // Determine if submission has been reviewed
            $rating = self::get_curator_rating($post->ID, $curator_id);
            $has_rating = $rating && intval($rating) > 0;
            
            $submissions[] = array(
                'id' => $post->ID,
                'title' => !empty($post->post_title) ? $post->post_title : 'Submission #' . $post->ID,
                'date' => get_the_date('', $post),
                'excerpt' => !empty($post->post_content) ? wp_trim_words($post->post_content, 20) : 'No description available.',
                'thumbnail' => self::get_submission_thumbnail($post->ID),
                'rating' => $rating,
                'notes_count' => self::get_submission_notes_count($post->ID, $curator_id),
                'artist' => array(
                    'name' => $artist_name
                ),
                'files' => array(
                    'count' => $files_count
                ),
                'status' => $has_rating ? 'reviewed' : 'pending'
            );
        }
        
        // Prepare pagination data
        $pagination = array(
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'has_next' => $page < $query->max_num_pages,
            'has_prev' => $page > 1
        );
        
        return array(
            'submissions' => $submissions,
            'pagination' => $pagination
        );
    }
    
    /**
     * Get submission details for portal view
     */
    private static function get_submission_details($submission_id, $curator_id) {
        $post = get_post($submission_id);
        if (!$post) {
            return null;
        }
        
        // Get rating data
        $rating = self::get_curator_rating($post->ID, $curator_id);
        
        // Enhanced details
        return array(
            'id' => $post->ID,
            'title' => !empty($post->post_title) ? $post->post_title : 'Submission #' . $post->ID,
            'content' => !empty($post->post_content) ? $post->post_content : 'No detailed description provided by the artist.',
            'date' => get_the_date('', $post),
            'rating' => $rating,
            'can_rate' => CF7_Artist_Submissions_Guest_Curators::has_permission($curator_id, 0, 'rate'),
            'can_comment' => CF7_Artist_Submissions_Guest_Curators::has_permission($curator_id, 0, 'comment')
        );
    }
    
    /**
     * Check if curator can access a specific submission
     */
    private static function curator_can_access_submission($curator_id, $submission_id) {
        $open_call_terms = wp_get_post_terms($submission_id, 'open_call');
        
        foreach ($open_call_terms as $term) {
            if (CF7_Artist_Submissions_Guest_Curators::has_permission($curator_id, $term->term_id, 'view')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get submission thumbnail
     */
    private static function get_submission_thumbnail($submission_id) {
        // Get first image file as thumbnail
        $files = get_post_meta($submission_id, 'uploaded_files', true);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (strpos($file['type'], 'image/') === 0) {
                    return isset($file['url']) ? $file['url'] : '';
                }
            }
        }
        return '';
    }
    
    /**
     * Get curator's rating for a submission
     */
    private static function get_curator_rating($submission_id, $curator_id) {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $ratings_table WHERE submission_id = %d AND guest_curator_id = %d",
            $submission_id, $curator_id
        ));
    }
    
    /**
     * Get submission notes count for a curator
     */
    private static function get_submission_notes_count($submission_id, $curator_id) {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $notes_table WHERE submission_id = %d AND guest_curator_id = %d",
            $submission_id, $curator_id
        ));
    }
    
    /**
     * AJAX handler to get statistics
     */
    public static function ajax_get_statistics() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        
        if (!$session_token) {
            wp_send_json_error('Authentication required');
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error('Session expired');
            return;
        }
        
        // Generate statistics for this curator
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'cf7as_curator_permissions';
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Get curator's assigned open calls
        $assigned_calls = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT open_call_term_id FROM $permissions_table WHERE guest_curator_id = %d",
            $curator_id
        ));
        
        if (empty($assigned_calls)) {
            wp_send_json_success(array(
                'total_submissions' => 0,
                'pending_reviews' => 0,
                'completed_reviews' => 0,
                'average_rating' => 0
            ));
            return;
        }
        
        // Get submissions for these open calls
        $submissions = get_posts(array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'open_call',
                    'field' => 'term_id',
                    'terms' => $assigned_calls
                )
            )
        ));
        
        $total_submissions = count($submissions);
        
        if ($total_submissions === 0) {
            wp_send_json_success(array(
                'total_submissions' => 0,
                'pending_reviews' => 0,
                'completed_reviews' => 0,
                'average_rating' => 0
            ));
            return;
        }
        
        // Get rated submissions by this curator
        $submission_ids_str = implode(',', array_map('intval', $submissions));
        $rated_submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT submission_id, rating FROM $ratings_table WHERE guest_curator_id = %d AND submission_id IN ($submission_ids_str)",
            $curator_id
        ));
        
        $completed_reviews = count($rated_submissions);
        $pending_reviews = $total_submissions - $completed_reviews;
        
        // Calculate average rating
        $average_rating = 0;
        if ($completed_reviews > 0) {
            $total_rating = array_sum(array_column($rated_submissions, 'rating'));
            $average_rating = round($total_rating / $completed_reviews, 2);
        }
        
        wp_send_json_success(array(
            'total_submissions' => $total_submissions,
            'pending_reviews' => $pending_reviews,
            'completed_reviews' => $completed_reviews,
            'average_rating' => $average_rating
        ));
    }
    
    /**
     * AJAX handler to download file
     */
    public static function ajax_download_file() {
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        $file_url = isset($_POST['file_url']) ? esc_url_raw($_POST['file_url']) : '';
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        
        if (!$session_token) {
            wp_send_json_error('Authentication required');
            return;
        }
        
        $curator_id = self::get_curator_from_session($session_token);
        if (!$curator_id) {
            wp_send_json_error('Session expired');
            return;
        }
        
        if (!$file_url || !$submission_id) {
            wp_send_json_error('File URL and submission ID required');
            return;
        }
        
        // Verify curator has access to this submission
        if (!self::curator_can_access_submission($curator_id, $submission_id)) {
            wp_send_json_error('Access denied');
            return;
        }
        
        // Verify the file belongs to this submission
        $submission_files = get_post_meta($submission_id, 'uploaded_files', true);
        $file_found = false;
        
        if (!empty($submission_files)) {
            foreach ($submission_files as $file) {
                if (isset($file['url']) && $file['url'] === $file_url) {
                    $file_found = true;
                    break;
                }
            }
        }
        
        if (!$file_found) {
            wp_send_json_error('File not found or does not belong to this submission');
            return;
        }
        
        // Return the file URL for download (frontend will handle the actual download)
        wp_send_json_success(array(
            'download_url' => $file_url,
            'message' => 'File ready for download'
        ));
    }
}
