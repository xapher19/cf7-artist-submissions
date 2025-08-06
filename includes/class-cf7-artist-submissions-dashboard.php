<?php
/**
 * CF7 Artist Submissions - Dashboard Management System
 *
 * Modern interactive dashboard interface providing comprehensive submission
 * management capabilities with real-time statistics, advanced filtering,
 * bulk operations, and seamless AJAX integration for efficient workflow
 * management and administrative oversight.
 *
 * Features:
 * • Real-time statistics and activity metrics with trend analysis
 * • Interactive submission table with advanced filtering and search
 * • Bulk actions for efficient status management and data export
 * • Live message tracking with unread notification system
 * • Outstanding actions monitoring with priority indicators
 * • Professional responsive design with mobile optimization
 * • Performance-optimized AJAX loading with smart pagination
 * • Comprehensive CSV export functionality with filtering support
 *
 * @package CF7_Artist_Submissions
 * @subpackage Dashboard
 * @since 1.0.0
 * @version 1.3.0
 */

/**
 * CF7 Artist Submissions Dashboard Class
 * 
 * Comprehensive dashboard management system providing modern interface for
 * artist submission administration. Integrates real-time statistics, interactive
 * widgets, advanced filtering capabilities, and bulk operations with seamless
 * AJAX functionality for efficient submission workflow management.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Dashboard {
    
    /**
     * Initialize comprehensive dashboard system with admin integration.
     * 
     * Establishes complete dashboard infrastructure including admin menu
     * integration, asset management, and comprehensive AJAX handlers for
     * submissions loading, bulk actions, status updates, messaging, and
     * real-time statistics. Provides foundation for modern submission
     * management workflow with performance optimization.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_dashboard_page'), 999); // Run late to modify menu
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_cf7_dashboard_load_submissions', array($this, 'ajax_load_submissions'));
        add_action('wp_ajax_cf7_dashboard_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_cf7_dashboard_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_cf7_dashboard_get_recent_messages', array($this, 'ajax_load_recent_messages'));
        add_action('wp_ajax_cf7_dashboard_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_cf7_dashboard_get_outstanding_actions', array($this, 'ajax_get_outstanding_actions'));
        add_action('wp_ajax_cf7_dashboard_export', array($this, 'ajax_export'));
        add_action('wp_ajax_cf7_dashboard_download_csv', array($this, 'ajax_download_csv'));
        add_action('wp_ajax_cf7_dashboard_submission_action', array($this, 'ajax_submission_action'));
        add_action('wp_ajax_cf7_dashboard_mark_message_read', array($this, 'ajax_mark_message_read'));
        add_action('wp_ajax_cf7_dashboard_mark_submission_read', array($this, 'ajax_mark_submission_read'));
        add_action('wp_ajax_cf7_dashboard_mark_all_read', array($this, 'ajax_mark_all_read'));
        add_action('wp_ajax_cf7_dashboard_get_today_activity', array($this, 'ajax_get_today_activity'));
        add_action('wp_ajax_cf7_dashboard_get_weekly_activity', array($this, 'ajax_get_weekly_activity'));
    }

    /**
     * Add dashboard page to WordPress admin menu system.
     * Integrates dashboard as primary submenu item with menu reorganization.
     */
    public function add_dashboard_page() {
        global $submenu;
        
        // Add dashboard as submenu with position 0 to make it first
        $dashboard_page = add_submenu_page(
            'edit.php?post_type=cf7_submission',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'cf7-dashboard',
            array($this, 'render_dashboard_page'),
            0  // Position 0 to make it first
        );
        
        // Modify submenu to reorder and hide items
        if (isset($submenu['edit.php?post_type=cf7_submission'])) {
            $dashboard_item = null;
            $other_items = array();
            
            // Find dashboard item and separate it
            foreach ($submenu['edit.php?post_type=cf7_submission'] as $key => $menu_item) {
                if ($menu_item[2] === 'cf7-dashboard') {
                    $dashboard_item = $menu_item;
                } elseif ($menu_item[2] !== 'edit.php?post_type=cf7_submission') {
                    // Keep other items except "All Submissions"
                    $other_items[] = $menu_item;
                }
            }
            
            // Rebuild submenu with dashboard first
            $submenu['edit.php?post_type=cf7_submission'] = array();
            if ($dashboard_item) {
                $submenu['edit.php?post_type=cf7_submission'][0] = $dashboard_item;
            }
            
            // Add other items after dashboard
            $counter = 1;
            foreach ($other_items as $item) {
                $submenu['edit.php?post_type=cf7_submission'][$counter] = $item;
                $counter++;
            }
        }
        
        // Redirect main menu to dashboard
        add_action('admin_init', array($this, 'redirect_main_menu_to_dashboard'));
    }
    
    /**
     * Redirect main menu access to dashboard for seamless user experience.
     * Automatically redirects standard post listing to dashboard interface.
     */
    public function redirect_main_menu_to_dashboard() {
        global $pagenow;
        
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'cf7_submission' && !isset($_GET['page'])) {
            wp_redirect(admin_url('edit.php?post_type=cf7_submission&page=cf7-dashboard'));
            exit;
        }
    }
    
    /**
     * Enqueue dashboard assets with dependency management and localization.
     * Loads CSS and JavaScript files with proper version control and translations.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'cf7-dashboard') === false) {
            return;
        }
        
        // Enqueue common styles first (foundation for all other styles)
        wp_enqueue_style('cf7-common-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/common.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
        
        // Enqueue dashboard-specific styles (depends on common.css)
        wp_enqueue_style('cf7-dashboard-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/dashboard.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
        
        wp_enqueue_script('cf7-dashboard-js', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/dashboard.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
        
        wp_localize_script('cf7-dashboard-js', 'cf7_dashboard', array(
            'nonce' => wp_create_nonce('cf7_dashboard_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'cf7-artist-submissions'),
                'error' => __('Error loading data', 'cf7-artist-submissions'),
                'success' => __('Action completed successfully', 'cf7-artist-submissions'),
                'confirmDelete' => __('Are you sure you want to delete the selected submissions?', 'cf7-artist-submissions'),
                'noItemsSelected' => __('Please select items first', 'cf7-artist-submissions'),
            )
        ));
        
        // Ensure ajaxurl is available
        wp_localize_script('cf7-dashboard-js', 'ajaxurl', admin_url('admin-ajax.php'));
    }
    
    /**
     * Render comprehensive dashboard interface with real-time statistics.
     * 
     * Generates complete dashboard HTML including metrics overview, activity
     * indicators, submission management table, recent messages panel, and
     * outstanding actions tracking. Provides server-side rendered statistics
     * for immediate display with AJAX enhancement for dynamic updates and
     * seamless user interaction experience.
     * 
     * @since 1.0.0
     */
    public function render_dashboard_page() {
        // Get initial stats for server-side rendering
        global $wpdb;
        $post_table = $wpdb->prefix . 'posts';
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$post_table} WHERE post_type = %s AND post_status = %s", 'cf7_submission', 'publish'));
        $new = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p LEFT JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id LEFT JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND (t.name = %s OR t.name IS NULL)", 'cf7_submission', 'publish', 'New'));
        $reviewed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND t.name = %s", 'cf7_submission', 'publish', 'Reviewed'));
        $awaiting_information = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND t.name = %s", 'cf7_submission', 'publish', 'Awaiting Information'));
        $shortlisted = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND t.name = %s", 'cf7_submission', 'publish', 'Shortlisted'));
        $selected = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND t.name = %s", 'cf7_submission', 'publish', 'Selected'));
        $rejected = $wpdb->get_var($wpdb->prepare("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = %s AND p.post_status = %s AND t.name = %s", 'cf7_submission', 'publish', 'Rejected'));
        $unread_messages = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT c.submission_id) FROM {$conversations_table} c WHERE c.direction = %s AND c.admin_viewed_at IS NULL", 'inbound'));
        
        $stats = array(
            'total' => (int) $total,
            'new' => (int) $new,
            'reviewed' => (int) $reviewed,
            'awaiting_information' => (int) $awaiting_information,
            'shortlisted' => (int) $shortlisted,
            'selected' => (int) $selected,
            'rejected' => (int) $rejected,
            'unread_messages' => (int) $unread_messages
        );
        
        ?>
        <div class="cf7-modern-dashboard">
            <!-- Dashboard Header -->
            <div class="cf7-gradient-header cf7-header-context">
                <div class="cf7-header-content">
                    <h1 class="cf7-header-title">Artist Submissions Dashboard</h1>
                    <p class="cf7-header-subtitle">Manage and review artist submissions</p>
                </div>
                <div class="cf7-header-actions">
                    <button class="cf7-btn cf7-btn-primary" id="cf7-refresh-dashboard">
                        <span class="dashicons dashicons-update"></span>
                        Refresh
                    </button>
                    <button class="cf7-btn cf7-btn-secondary" id="cf7-export-all">
                        <span class="dashicons dashicons-download"></span>
                        Export All
                    </button>
                </div>
            </div>

            <!-- Key Metrics Dashboard -->
            <div class="cf7-metrics-overview">
                <!-- Primary Stats Row -->
                <div class="cf7-primary-metrics">
                    <div class="cf7-metric-card metric-total" data-type="overview">
                        <div class="metric-value"><?php echo $stats['total']; ?></div>
                        <div class="metric-label">Total Submissions</div>
                        <div class="metric-trend" data-change="<?php echo $stats['total_change'] ?? 0; ?>">
                            <?php if (($stats['total_change'] ?? 0) > 0): ?>
                                <span class="trend-up">+<?php echo number_format($stats['total_change'], 1); ?>%</span>
                            <?php elseif (($stats['total_change'] ?? 0) < 0): ?>
                                <span class="trend-down"><?php echo number_format($stats['total_change'], 1); ?>%</span>
                            <?php else: ?>
                                <span class="trend-neutral">—</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="cf7-metric-card metric-pending" data-type="workflow">
                        <div class="metric-value"><?php echo $stats['new'] + $stats['awaiting_information']; ?></div>
                        <div class="metric-label">Needs Review</div>
                        <div class="metric-breakdown">
                            <span class="breakdown-item new"><?php echo $stats['new']; ?> new</span>
                            <span class="breakdown-item waiting"><?php echo $stats['awaiting_information']; ?> waiting</span>
                        </div>
                    </div>

                    <div class="cf7-metric-card metric-pipeline" data-type="progress">
                        <div class="metric-value"><?php echo $stats['shortlisted'] + $stats['reviewed']; ?></div>
                        <div class="metric-label">In Progress</div>
                        <div class="metric-breakdown">
                            <span class="breakdown-item reviewed"><?php echo $stats['reviewed']; ?> reviewed</span>
                            <span class="breakdown-item shortlisted"><?php echo $stats['shortlisted']; ?> shortlisted</span>
                        </div>
                    </div>

                    <div class="cf7-metric-card metric-decisions" data-type="outcomes">
                        <div class="metric-value"><?php echo $stats['selected'] + $stats['rejected']; ?></div>
                        <div class="metric-label">Decisions Made</div>
                        <div class="metric-breakdown">
                            <span class="breakdown-item selected"><?php echo $stats['selected']; ?> selected</span>
                            <span class="breakdown-item rejected"><?php echo $stats['rejected']; ?> rejected</span>
                        </div>
                    </div>
                </div>

                <!-- Activity Indicators Row -->
                <div class="cf7-activity-metrics">
                    <div class="cf7-activity-card activity-messages" data-type="conversations">
                        <div class="activity-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                            <?php if ($stats['unread_messages'] > 0): ?>
                                <span class="activity-badge"><?php echo $stats['unread_messages']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-label">Unread Messages</div>
                            <div class="activity-detail">Click to review conversations</div>
                        </div>
                    </div>

                    <div class="cf7-activity-card activity-actions" data-type="actions" id="activity-outstanding-actions">
                        <div class="activity-icon">
                            <span class="dashicons dashicons-bell"></span>
                            <span class="activity-badge activity-actions-count" style="display: none;">0</span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-label">Outstanding Actions</div>
                            <div class="activity-detail">Click to manage tasks</div>
                        </div>
                    </div>

                    <div class="cf7-activity-card activity-today" data-type="recent">
                        <div class="activity-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-label">Today's Activity</div>
                            <div class="activity-detail" id="activity-today-detail">Loading...</div>
                        </div>
                    </div>

                    <div class="cf7-activity-card activity-weekly" data-type="weekly">
                        <div class="activity-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span class="activity-badge activity-weekly-count" style="display: none;">0</span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-label">This Week</div>
                            <div class="activity-detail" id="activity-weekly-detail">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="cf7-dashboard-grid">
                <!-- Left Panel: Submissions Table -->
                <div class="cf7-dashboard-panel cf7-submissions-panel">
                    <div class="cf7-panel-header">
                        <div class="cf7-header-left">
                            <h2 id="cf7-panel-title">Submissions</h2>
                        </div>
                        <div class="cf7-header-right">
                            <!-- Search with expandable input -->
                            <div class="cf7-search-wrapper">
                                <div class="cf7-search-container">
                                    <button class="cf7-btn cf7-btn-icon cf7-search-toggle" id="cf7-search-toggle" title="Search submissions">
                                        <span class="dashicons dashicons-search"></span>
                                    </button>
                                    <div class="cf7-search-input-expandable" id="cf7-search-input-expandable">
                                        <input type="text" id="cf7-search-input" placeholder="Search by name, email, artistic medium, status, call..." class="cf7-search-field">
                                        <button class="cf7-search-close" id="cf7-search-close" title="Close search">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status filter dropdown -->
                            <div class="cf7-status-filter-wrapper">
                                <div class="cf7-status-filter-dropdown" data-current="">
                                    <div class="cf7-status-filter-display">
                                        <span class="cf7-status-icon dashicons dashicons-category" style="color: #718096;"></span>
                                        <span class="cf7-status-text">All Statuses</span>
                                        <span class="cf7-status-arrow dashicons dashicons-arrow-down-alt2"></span>
                                    </div>
                                    <div class="cf7-status-filter-menu">
                                        <div class="cf7-status-filter-option active" data-value="" data-icon="dashicons-category" data-color="#718096">
                                            <span class="cf7-status-icon dashicons dashicons-category" style="color: #718096;"></span>
                                            <span class="cf7-status-label">All Statuses</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="new" data-icon="dashicons-star-filled" data-color="#4299e1">
                                            <span class="cf7-status-icon dashicons dashicons-star-filled" style="color: #4299e1;"></span>
                                            <span class="cf7-status-label">New</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="reviewed" data-icon="dashicons-visibility" data-color="#9f7aea">
                                            <span class="cf7-status-icon dashicons dashicons-visibility" style="color: #9f7aea;"></span>
                                            <span class="cf7-status-label">Reviewed</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="awaiting-information" data-icon="dashicons-clock" data-color="#dd6b20">
                                            <span class="cf7-status-icon dashicons dashicons-clock" style="color: #dd6b20;"></span>
                                            <span class="cf7-status-label">Awaiting Information</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="shortlisted" data-icon="dashicons-paperclip" data-color="#ec4899">
                                            <span class="cf7-status-icon dashicons dashicons-paperclip" style="color: #ec4899;"></span>
                                            <span class="cf7-status-label">Shortlisted</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="selected" data-icon="dashicons-yes-alt" data-color="#48bb78">
                                            <span class="cf7-status-icon dashicons dashicons-yes-alt" style="color: #48bb78;"></span>
                                            <span class="cf7-status-label">Selected</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="rejected" data-icon="dashicons-dismiss" data-color="#f56565">
                                            <span class="cf7-status-icon dashicons dashicons-dismiss" style="color: #f56565;"></span>
                                            <span class="cf7-status-label">Rejected</span>
                                        </div>
                                        <div class="cf7-status-filter-separator"></div>
                                        <div class="cf7-status-filter-option" data-value="unread_messages" data-icon="dashicons-email-alt" data-color="#ef4444">
                                            <span class="cf7-status-icon dashicons dashicons-email-alt" style="color: #ef4444;"></span>
                                            <span class="cf7-status-label">Has Unread Messages</span>
                                        </div>
                                        <div class="cf7-status-filter-option" data-value="outstanding_actions" data-icon="dashicons-bell" data-color="#f59e0b">
                                            <span class="cf7-status-icon dashicons dashicons-bell" style="color: #f59e0b;"></span>
                                            <span class="cf7-status-label">Has Outstanding Actions</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Open Call filter dropdown -->
                            <div class="cf7-call-filter-wrapper">
                                <div class="cf7-call-filter-dropdown" data-current="">
                                    <div class="cf7-call-filter-display">
                                        <span class="cf7-call-icon dashicons dashicons-megaphone" style="color: #718096;"></span>
                                        <span class="cf7-call-text">All Calls</span>
                                        <span class="cf7-call-arrow dashicons dashicons-arrow-down-alt2"></span>
                                    </div>
                                    <div class="cf7-call-filter-menu">
                                        <div class="cf7-call-filter-option active" data-value="" data-icon="dashicons-megaphone" data-color="#718096">
                                            <span class="cf7-call-icon dashicons dashicons-megaphone" style="color: #718096;"></span>
                                            <span class="cf7-call-label">All Calls</span>
                                        </div>
                                        <?php
                                        // Get all open calls
                                        $open_calls = get_terms(array(
                                            'taxonomy' => 'open_call',
                                            'hide_empty' => false,
                                            'orderby' => 'name',
                                            'order' => 'ASC'
                                        ));
                                        
                                        if (!empty($open_calls) && !is_wp_error($open_calls)) {
                                            // Get open calls configuration for dashboard tags
                                            $open_calls_options = get_option('cf7_artist_submissions_open_calls', array());
                                            $calls_config = $open_calls_options['calls'] ?? array();
                                            
                                            foreach ($open_calls as $call) {
                                                // Look for dashboard tag in configuration
                                                $display_name = $call->name; // Default to taxonomy name
                                                
                                                if (!empty($calls_config)) {
                                                    foreach ($calls_config as $call_config) {
                                                        // Match by title or term name
                                                        if ((isset($call_config['title']) && $call_config['title'] === $call->name) ||
                                                            (isset($call_config['term_id']) && $call_config['term_id'] == $call->term_id)) {
                                                            if (!empty($call_config['dashboard_tag'])) {
                                                                $display_name = $call_config['dashboard_tag'];
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                echo '<div class="cf7-call-filter-option" data-value="' . esc_attr($call->slug) . '" data-icon="dashicons-portfolio" data-color="#4299e1">';
                                                echo '<span class="cf7-call-icon dashicons dashicons-portfolio" style="color: #4299e1;"></span>';
                                                echo '<span class="cf7-call-label">' . esc_html($display_name) . '</span>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date filter calendar trigger -->
                            <div class="cf7-calendar-date-picker">
                                <div class="cf7-calendar-trigger" tabindex="0" role="button" aria-label="Select date range" title="Filter by date">
                                    <span class="cf7-calendar-icon"></span>
                                    <span class="cf7-calendar-text">Date</span>
                                    <span class="cf7-calendar-arrow">▼</span>
                                </div>
                                
                                <div class="cf7-calendar-dropdown">
                                    <div class="cf7-calendar-header">
                                        <button type="button" class="cf7-calendar-nav" data-action="prev-month">‹</button>
                                        <div class="cf7-calendar-month-year">
                                            <span class="cf7-calendar-month">January</span>
                                            <span class="cf7-calendar-year">2025</span>
                                        </div>
                                        <button type="button" class="cf7-calendar-nav" data-action="next-month">›</button>
                                    </div>
                                    
                                    <div class="cf7-calendar-weekdays">
                                        <div class="cf7-calendar-weekday">Su</div>
                                        <div class="cf7-calendar-weekday">Mo</div>
                                        <div class="cf7-calendar-weekday">Tu</div>
                                        <div class="cf7-calendar-weekday">We</div>
                                        <div class="cf7-calendar-weekday">Th</div>
                                        <div class="cf7-calendar-weekday">Fr</div>
                                        <div class="cf7-calendar-weekday">Sa</div>
                                    </div>
                                    
                                    <div class="cf7-calendar-grid" id="cf7-calendar-grid">
                                        <!-- Calendar days will be populated by JavaScript -->
                                    </div>
                                    
                                    <div class="cf7-calendar-footer">
                                        <div class="cf7-calendar-presets">
                                            <button type="button" class="cf7-calendar-preset" data-range="today">Today</button>
                                            <button type="button" class="cf7-calendar-preset" data-range="week">Last 7 Days</button>
                                            <button type="button" class="cf7-calendar-preset" data-range="month">Last 30 Days</button>
                                        </div>
                                        <div class="cf7-calendar-actions">
                                            <button type="button" class="cf7-calendar-clear">Clear</button>
                                            <button type="button" class="cf7-calendar-apply">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orange Clear Filters Bar -->
                    <div class="cf7-clear-filters" id="cf7-clear-filters" style="display: none;">
                        <div class="cf7-clear-filters-content">
                            <div class="cf7-clear-filters-text">
                                <strong>Filters Applied:</strong>
                            </div>
                            <div class="cf7-active-filters" id="cf7-active-filters">
                                <!-- Active filters will be populated here -->
                            </div>
                        </div>
                        <button class="cf7-clear-all-btn" id="cf7-clear-all">
                            <span class="dashicons dashicons-no-alt"></span>
                            Clear All Filters
                        </button>
                    </div>
                    
                    <!-- Hidden date inputs for compatibility -->
                    <input type="hidden" id="cf7-date-from" class="cf7-date-input">
                    <input type="hidden" id="cf7-date-to" class="cf7-date-input">
                    
                    <!-- Bulk Actions Bar -->
                    <div class="cf7-bulk-actions" id="cf7-bulk-actions" style="display: none;">
                        <div class="cf7-bulk-left">
                            <label class="cf7-select-all-wrapper">
                                <input type="checkbox" class="cf7-select-all">
                                <span class="cf7-select-all-label">Select All</span>
                            </label>
                            <span class="cf7-selected-count">0 items selected</span>
                        </div>
                        <div class="cf7-bulk-right">
                            <select id="cf7-bulk-action-select" class="cf7-bulk-select">
                                <option value="">Bulk Actions</option>
                                <option value="export">Export Selected</option>
                                <option value="status-reviewed">Mark as Reviewed</option>
                                <option value="status-awaiting-information">Mark as Awaiting Information</option>
                                <option value="status-shortlisted">Mark as Shortlisted</option>
                                <option value="status-selected">Mark as Selected</option>
                                <option value="status-rejected">Mark as Rejected</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                            <button class="cf7-btn cf7-btn-secondary" id="cf7-apply-bulk">Apply</button>
                        </div>
                    </div>

                    <!-- Submissions Table -->
                    <div class="cf7-submissions-container">
                        <div class="cf7-submissions-table" id="cf7-submissions-table">
                            <div class="cf7-loading-state">
                                <div class="cf7-loading-spinner"></div>
                                <p>Loading submissions...</p>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="cf7-pagination" id="cf7-pagination" style="display: block;">
                            <div class="cf7-pagination-info">Loading submissions...</div>
                            <div class="cf7-pagination-controls">
                                <div class="cf7-per-page-selector">
                                    <label for="cf7-per-page">Show:</label>
                                    <select id="cf7-per-page" class="cf7-per-page-select">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span>per page</span>
                                </div>
                                <div class="cf7-pagination-buttons">
                                    <button class="cf7-page-btn" disabled>‹ Previous</button>
                                    <button class="cf7-page-btn active">1</button>
                                    <button class="cf7-page-btn" disabled>Next ›</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Recent Activity & Messages -->
                <div class="cf7-dashboard-panel cf7-activity-panel">
                    <div class="cf7-panel-header">
                        <h2>Recent Activity</h2>
                        <button class="cf7-btn cf7-btn-ghost" id="cf7-refresh-activity">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                    
                    <!-- Recent Messages -->
                    <div class="cf7-activity-section">
                        <div class="cf7-activity-header">
                            <h3>Unread Messages</h3>
                            <span class="cf7-count-badge" id="cf7-unread-count">
                                <span id="cf7-unread-messages-stat"><?php echo $stats['unread_messages']; ?></span>
                            </span>
                        </div>
                        <div class="cf7-recent-messages" id="cf7-recent-messages">
                            <div class="cf7-loading-state">
                                <div class="cf7-loading-spinner"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="cf7-activity-section">
                        <h3>Outstanding Actions</h3>
                        <div class="cf7-outstanding-actions" id="cf7-outstanding-actions">
                            <div class="cf7-loading-state">
                                <div class="cf7-loading-spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="cf7-toast-container" id="cf7-toast-container"></div>
        <?php
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler for loading submissions with advanced filtering capabilities.
     * 
     * Processes submission retrieval requests with comprehensive filtering options
     * including search, status, date range, and pagination. Supports specialized
     * filters for unread messages and outstanding actions with optimized database
     * queries and formatted response data for frontend display.
     * 
     * @since 1.0.0
     */
    public function ajax_load_submissions() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $open_call = sanitize_text_field($_POST['open_call'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        // Validate and limit pagination parameters
        $page = max(1, $page);
        $per_page = max(1, min(100, $per_page)); // Limit to 100 items per page
        
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(),
            'tax_query' => array()
        );
        
        if (!empty($search)) {
            // Enhanced search that includes artistic medium terms
            $search_results = $this->perform_enhanced_search($search);
            
            if (!empty($search_results)) {
                $args['post__in'] = $search_results;
                // Don't use WordPress default search if we have custom results
            } else {
                // Fallback to default WordPress search if no custom results found
                $args['s'] = $search;
            }
        }
        
        // Add open call filtering
        if (!empty($open_call)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'open_call',
                'field' => 'slug',
                'terms' => $open_call
            );
        }
        
        if (!empty($status)) {
            if ($status === 'unread_messages') {
                // Filter submissions with unread messages
                global $wpdb;
                $conversations_table = $wpdb->prefix . 'cf7_conversations';
                $submission_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT submission_id 
                    FROM {$conversations_table} 
                    WHERE direction = %s AND admin_viewed_at IS NULL
                ", 'inbound'));
                
                if (!empty($submission_ids)) {
                    $args['post__in'] = $submission_ids;
                } else {
                    // No submissions with unread messages, return empty result
                    $args['post__in'] = array(0); // Non-existent ID
                }
            } elseif ($status === 'outstanding_actions') {
                // Filter submissions with outstanding actions
                global $wpdb;
                $actions_table = $wpdb->prefix . 'cf7_actions';
                $submission_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT submission_id 
                    FROM {$actions_table} 
                    WHERE status = %s
                ", 'pending'));
                
                if (!empty($submission_ids)) {
                    $args['post__in'] = $submission_ids;
                } else {
                    // No submissions with outstanding actions, return empty result
                    $args['post__in'] = array(0); // Non-existent ID
                }
            } elseif ($status === 'workflow') {
                // Workflow filter: new + awaiting-information
                $args['tax_query'][] = array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => array('new', 'awaiting-information'),
                    'operator' => 'IN'
                );
            } elseif ($status === 'progress') {
                // Progress filter: reviewed + shortlisted
                $args['tax_query'][] = array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => array('reviewed', 'shortlisted'),
                    'operator' => 'IN'
                );
            } elseif ($status === 'outcomes') {
                // Outcomes filter: selected + rejected
                $args['tax_query'][] = array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => array('selected', 'rejected'),
                    'operator' => 'IN'
                );
            } else {
                // Regular status filter
                $args['tax_query'][] = array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => $status
                );
            }
        }
        
        // Set tax_query relation if we have multiple taxonomy filters
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        
        // Add date filtering
        if (!empty($date_from) || !empty($date_to)) {
            $date_query = array('relation' => 'AND');
            
            if (!empty($date_from)) {
                $date_query[] = array(
                    'after' => $date_from,
                    'inclusive' => true
                );
            }
            
            if (!empty($date_to)) {
                $date_query[] = array(
                    'before' => $date_to . ' 23:59:59',
                    'inclusive' => true
                );
            }
            
            $args['date_query'] = $date_query;
        }
        
        $query = new WP_Query($args);
        $submissions = array();
        
        foreach ($query->posts as $post) {
            $submissions[] = $this->format_submission_data($post);
        }
        
        wp_send_json_success(array(
            'submissions' => $submissions,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $query->found_posts,
                'total_pages' => $query->max_num_pages
            ),
            // Legacy fields for backward compatibility
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }
    
    /**
     * Format submission data for frontend display with notification counts.
     * Structures submission information with status, artist details, and activity indicators.
     */
    private function format_submission_data($post) {
        $status_terms = wp_get_object_terms($post->ID, 'submission_status');
        $status = (!is_wp_error($status_terms) && !empty($status_terms)) ? $status_terms[0]->name : 'New';
        $status_slug = (!is_wp_error($status_terms) && !empty($status_terms)) ? $status_terms[0]->slug : 'new';
        
        // Get notification counts
        global $wpdb;
        
        // Count unread messages for this submission
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        $unread_messages_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$conversations_table} 
            WHERE submission_id = %d AND direction = 'inbound' AND admin_viewed_at IS NULL
        ", $post->ID));
        
        // Count outstanding actions for this submission
        $actions_table = $wpdb->prefix . 'cf7_actions';
        $outstanding_actions_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$actions_table} 
            WHERE submission_id = %d AND status = 'pending'
        ", $post->ID));
        
        // Get open call information
        $call_terms = wp_get_object_terms($post->ID, 'open_call');
        $open_call = (!is_wp_error($call_terms) && !empty($call_terms)) ? $call_terms[0]->name : 'General Submissions';
        $open_call_slug = (!is_wp_error($call_terms) && !empty($call_terms)) ? $call_terms[0]->slug : 'general-submissions';
        
        // Get dashboard tag and call type if available
        $dashboard_display_name = $open_call;
        $call_type = 'visual_arts'; // Default to visual arts
        if (!is_wp_error($call_terms) && !empty($call_terms)) {
            $open_calls_options = get_option('cf7_artist_submissions_open_calls', array());
            if (!empty($open_calls_options['calls'])) {
                foreach ($open_calls_options['calls'] as $call_config) {
                    if (isset($call_config['title']) && $call_config['title'] === $open_call) {
                        if (!empty($call_config['dashboard_tag'])) {
                            $dashboard_display_name = $call_config['dashboard_tag'];
                        }
                        if (!empty($call_config['call_type'])) {
                            $call_type = $call_config['call_type'];
                        }
                        break;
                    }
                }
            }
        }
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title ?: 'Untitled Submission',
            'date' => get_the_date('Y-m-d H:i:s', $post),
            'time' => get_the_date('g:i a', $post),
            'status' => $status_slug, // Use slug for CSS classes
            'status_label' => $status, // Human readable label
            'open_call' => $dashboard_display_name, // Use dashboard tag if available, otherwise full name
            'open_call_slug' => $open_call_slug,
            'call_type' => $call_type, // Add call type for mediums determination
            'artist_name' => get_post_meta($post->ID, 'cf7_artist-name', true) ?: 'Unknown Artist',
            'email' => get_post_meta($post->ID, 'cf7_email', true) ?: get_post_meta($post->ID, 'your-email', true) ?: get_post_meta($post->ID, 'cf7_your-email', true) ?: get_post_meta($post->ID, 'email', true) ?: 'No email',
            'mediums' => $this->get_submission_mediums($post->ID),
            'text_mediums' => $this->get_submission_text_mediums($post->ID),
            'notes' => get_post_meta($post->ID, 'cf7_curator_notes', true),
            'view_url' => get_edit_post_link($post->ID),
            'edit_url' => get_edit_post_link($post->ID),
            'unread_messages_count' => (int) $unread_messages_count,
            'outstanding_actions_count' => (int) $outstanding_actions_count
        );
    }
    
    /**
     * AJAX handler for bulk actions with comprehensive validation.
     * Processes batch operations including export, delete, and status changes.
     * 
     * @since 1.0.0
     */
    public function ajax_bulk_action() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $post_ids = array_map('intval', $_POST['ids'] ?? array());
        
        if (empty($post_ids)) {
            wp_send_json_error('No items selected');
        }
        
        // Limit number of items to prevent DoS
        if (count($post_ids) > 1000) {
            wp_send_json_error('Too many items selected. Maximum 1000 allowed.');
            return;
        }
        
        // Validate all post IDs belong to cf7_submission post type
        foreach ($post_ids as $post_id) {
            if (!get_post($post_id) || get_post_type($post_id) !== 'cf7_submission') {
                wp_send_json_error('Invalid submission ID detected');
                return;
            }
        }
        
        switch ($action) {
            case 'export':
                $this->handle_export($post_ids);
                break;
            case 'delete':
                $this->handle_delete($post_ids);
                break;
            default:
                if (strpos($action, 'status-') === 0) {
                    $status = str_replace('status-', '', $action);
                    $this->handle_status_change($post_ids, $status);
                }
                break;
        }
    }
    
    /**
     * Handle CSV export generation for selected submissions.
     * Creates downloadable CSV file with comprehensive submission data for selected entries.
     */
    private function handle_export($post_ids) {
        $filename = 'submissions-' . date('Y-m-d-H-i-s') . '.csv';
        $file_path = wp_upload_dir()['path'] . '/' . $filename;
        
        $handle = fopen($file_path, 'w');
        
        // Comprehensive CSV headers with all available fields
        fputcsv($handle, array(
            'ID', 
            'Title',
            'Artist Name', 
            'Email', 
            'Phone',
            'Pronouns',
            'Location',
            'Website',
            'Instagram',
            'Date', 
            'Status', 
            'Open Call',
            'Artistic Mediums',
            'Text Mediums',
            'Artist Statement',
            'Submission Comments',
            'Curator Notes',
            'Unread Messages',
            'Outstanding Actions',
            'File Count',
            'View URL'
        ));
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $data = $this->format_submission_data($post);
                
                // Get additional field data not in format_submission_data
                $phone = get_post_meta($post_id, 'cf7_phone', true) ?: '';
                $pronouns = get_post_meta($post_id, 'cf7_pronouns', true) ?: '';
                $location = get_post_meta($post_id, 'cf7_location', true) ?: '';
                $website = get_post_meta($post_id, 'cf7_website', true) ?: '';
                $instagram = get_post_meta($post_id, 'cf7_instagram', true) ?: '';
                $artist_statement = get_post_meta($post_id, 'cf7_artist-statement', true) ?: get_post_meta($post_id, 'cf7_artistic-statement', true) ?: '';
                $submission_comments = get_post_meta($post_id, 'cf7_submission-comments', true) ?: '';
                
                // Get file count from cf7as_files table
                global $wpdb;
                $file_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}cf7as_files WHERE submission_id = %d",
                    $post_id
                ));
                
                fputcsv($handle, array(
                    $data['id'],
                    $data['title'],
                    $data['artist_name'],
                    $data['email'],
                    $phone,
                    $pronouns,
                    $location,
                    $website,
                    $instagram,
                    $data['date'],
                    $data['status_label'],
                    $data['open_call'],
                    $this->format_mediums_for_export($data['mediums']),
                    $this->format_mediums_for_export($data['text_mediums']),
                    $artist_statement,
                    $submission_comments,
                    $data['notes'],
                    isset($data['unread_messages_count']) ? $data['unread_messages_count'] : 0,
                    isset($data['outstanding_actions_count']) ? $data['outstanding_actions_count'] : 0,
                    $file_count ?: 0,
                    $data['view_url']
                ));
            }
        }
        
        fclose($handle);
        
        $download_url = wp_upload_dir()['url'] . '/' . $filename;
        
        wp_send_json_success(array(
            'message' => 'Export completed',
            'download_url' => $download_url,
            'filename' => $filename
        ));
    }
    
    /**
     * Handle bulk deletion of selected submissions.
     * Removes submissions with cache cleanup and audit trail.
     */
    private function handle_delete($post_ids) {
        $deleted = 0;
        foreach ($post_ids as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted++;
            }
        }
        
        // Clear today's stats cache after bulk deletions
        $this->clear_daily_stats_cache();
        
        wp_send_json_success(array(
            'message' => sprintf('%d submissions deleted', $deleted)
        ));
    }
    
    /**
     * Handle bulk status changes for selected submissions.
     * Updates submission status with validation and audit logging.
     */
    private function handle_status_change($post_ids, $status) {
        $updated = 0;
        
        // Get the term
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        foreach ($post_ids as $post_id) {
            // Get old status for audit log
            $old_terms = wp_get_post_terms($post_id, 'submission_status');
            $old_status = !empty($old_terms) ? $old_terms[0]->name : 'None';
            
            wp_set_post_terms($post_id, array($term->term_id), 'submission_status');
            
            // Log status change to audit trail
            if (class_exists('CF7_Artist_Submissions_Action_Log')) {
                CF7_Artist_Submissions_Action_Log::log_status_change(
                    $post_id,
                    $old_status,
                    $term->name
                );
            }
            
            $updated++;
        }
        
        // Clear today's stats cache after bulk status changes
        $this->clear_daily_stats_cache();
        
        wp_send_json_success(array(
            'message' => sprintf('%d submissions updated to %s', $updated, $term->name)
        ));
    }
    
    /**
     * AJAX handler for loading unread messages with submission context.
     * Retrieves unviewed messages grouped by submission for activity panel.
     * 
     * @since 1.0.0
     */
    public function ajax_load_recent_messages() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        
        // Get submissions with unviewed messages, grouped by submission
        $submissions_with_unviewed = $wpdb->get_results($wpdb->prepare("
            SELECT 
                c.submission_id,
                COUNT(*) as unviewed_count,
                MAX(COALESCE(c.received_at, c.sent_at)) as latest_message_date,
                p.post_title as submission_title,
                p.ID as post_id
            FROM {$conversations_table} c
            LEFT JOIN {$wpdb->posts} p ON c.submission_id = p.ID
            WHERE c.admin_viewed_at IS NULL 
            AND c.direction = 'inbound'
            GROUP BY c.submission_id
            ORDER BY latest_message_date DESC 
            LIMIT 10
        "));
        
        $messages = array();
        foreach ($submissions_with_unviewed as $submission) {
            // Get artist name from submission meta
            $artist_name = $this->get_artist_name($submission->submission_id);
            $time_diff = human_time_diff(strtotime($submission->latest_message_date), current_time('timestamp'));
            
            $messages[] = array(
                'id' => $submission->submission_id,
                'submission_id' => $submission->submission_id,
                'submission_title' => $submission->submission_title ?: 'Submission #' . $submission->submission_id,
                'artist_name' => $artist_name,
                'unviewed_count' => $submission->unviewed_count,
                'time_ago' => $time_diff . ' ago',
                'unread' => true,
                'view_url' => admin_url('post.php?post=' . $submission->submission_id . '&action=edit&from=unread-messages#cf7-tab-conversations')
            );
        }
        
        wp_send_json_success($messages);
    }
    
    /**
     * AJAX handler for status updates with validation and logging.
     * Updates submission status via dashboard interface with audit trail.
     * 
     * @since 1.0.0
     */
    public function ajax_update_status() {
        check_ajax_referer('cf7_dashboard_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Validate post ID
        if (!$post_id || !get_post($post_id) || get_post_type($post_id) !== 'cf7_submission') {
            wp_send_json_error('Invalid submission ID');
            return;
        }
        
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        // Get old status for audit log
        $old_terms = wp_get_post_terms($post_id, 'submission_status');
        $old_status = !empty($old_terms) ? $old_terms[0]->name : 'None';
        
        wp_set_post_terms($post_id, array($term->term_id), 'submission_status');
        
        // Log status change to audit trail
        if (class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_status_change(
                $post_id,
                $old_status,
                $term->name
            );
        }
        
        // Clear today's stats cache after status update
        $this->clear_daily_stats_cache();
        
        wp_send_json_success(array(
            'message' => 'Status updated successfully'
        ));
    }
    
    /**
     * AJAX handler for dashboard statistics with percentage calculations.
     * Provides real-time submission counts and trends for dashboard widgets.
     * 
     * @since 1.0.0
     */
    public function ajax_get_stats() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if cache-busting is requested (from JavaScript)
        $cache_buster = isset($_POST['_cache_buster']);
        if ($cache_buster) {
            // Clear the cache to ensure fresh data
            $this->clear_daily_stats_cache();
        }
        
        try {
            $post_counts = wp_count_posts('cf7_submission');
            $total = 0;
            if ($post_counts) {
                $total = ($post_counts->publish ?? 0) + ($post_counts->private ?? 0);
            }
            
            $stats = array(
                'total' => $total,
                'new' => $this->get_submissions_count_by_status('new'),
                'reviewed' => $this->get_submissions_count_by_status('reviewed'),
                'awaiting-information' => $this->get_submissions_count_by_status('awaiting-information'),
                'shortlisted' => $this->get_submissions_count_by_status('shortlisted'),
                'selected' => $this->get_submissions_count_by_status('selected'),
                'rejected' => $this->get_submissions_count_by_status('rejected'),
                'unread_messages' => $this->get_unread_messages_count(),
            );
            
            // Calculate real percentage changes
            $stats = array_merge($stats, $this->calculate_percentage_changes($stats));
            
            // Debug: also send raw post count info
            $debug_info = array(
                'post_type_exists' => post_type_exists('cf7_submission'),
                'taxonomy_exists' => taxonomy_exists('submission_status'),
                'raw_post_counts' => $post_counts,
                'total_calculation' => $total,
                'terms_exist' => array(
                    'new' => term_exists('new', 'submission_status') ? 'yes' : 'no',
                    'reviewed' => term_exists('reviewed', 'submission_status') ? 'yes' : 'no',
                    'awaiting-information' => term_exists('awaiting-information', 'submission_status') ? 'yes' : 'no',
                    'shortlisted' => term_exists('shortlisted', 'submission_status') ? 'yes' : 'no',
                    'selected' => term_exists('selected', 'submission_status') ? 'yes' : 'no',
                    'rejected' => term_exists('rejected', 'submission_status') ? 'yes' : 'no',
                )
            );
            $stats['debug'] = $debug_info;
            
            wp_send_json_success($stats);
        } catch (Exception $e) {
            wp_send_json_error('Error calculating stats: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for CSV export generation with security validation.
     * Creates downloadable CSV files of filtered submission data.
     * 
     * @since 1.0.0
     */
    public function ajax_export() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Generate CSV export with comprehensive data
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        // Apply status filter if provided (using taxonomy terms)
        if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'submission_status',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_POST['status']),
                )
            );
        }
        
        // Apply search filter
        if (!empty($_POST['search'])) {
            $args['s'] = sanitize_text_field($_POST['search']);
        }
        
        // Apply open call filter
        if (!empty($_POST['open_call']) && $_POST['open_call'] !== 'all') {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = array(
                'taxonomy' => 'open_call',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['open_call']),
            );
        }
        
        $submissions = get_posts($args);
        
        // Create comprehensive CSV content using format_submission_data for consistency
        $csv_headers = array(
            'ID', 
            'Title',
            'Artist Name', 
            'Email', 
            'Phone',
            'Pronouns',
            'Location',
            'Website',
            'Instagram',
            'Date', 
            'Status', 
            'Open Call',
            'Artistic Mediums',
            'Text Mediums',
            'Artist Statement',
            'Submission Comments',
            'Curator Notes',
            'Unread Messages',
            'Outstanding Actions',
            'File Count',
            'View URL'
        );
        
        $csv_content = implode(',', $csv_headers) . "\n";
        
        foreach ($submissions as $submission) {
            $data = $this->format_submission_data($submission);
            
            // Get additional field data not in format_submission_data
            $phone = get_post_meta($submission->ID, 'cf7_phone', true) ?: '';
            $pronouns = get_post_meta($submission->ID, 'cf7_pronouns', true) ?: '';
            $location = get_post_meta($submission->ID, 'cf7_location', true) ?: '';
            $website = get_post_meta($submission->ID, 'cf7_website', true) ?: '';
            $instagram = get_post_meta($submission->ID, 'cf7_instagram', true) ?: '';
            $artist_statement = get_post_meta($submission->ID, 'cf7_artist-statement', true) ?: get_post_meta($submission->ID, 'cf7_artistic-statement', true) ?: '';
            $submission_comments = get_post_meta($submission->ID, 'cf7_submission-comments', true) ?: '';
            
            // Get file count from cf7as_files table
            global $wpdb;
            $file_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cf7as_files WHERE submission_id = %d",
                $submission->ID
            ));
            
            $csv_row = array(
                $data['id'],
                $data['title'],
                $data['artist_name'],
                $data['email'],
                $phone,
                $pronouns,
                $location,
                $website,
                $instagram,
                $data['date'],
                $data['status_label'],
                $data['open_call'],
                $this->format_mediums_for_export($data['mediums']),
                $this->format_mediums_for_export($data['text_mediums']),
                $artist_statement,
                $submission_comments,
                $data['notes'],
                $data['unread_messages_count'],
                $data['outstanding_actions_count'],
                $file_count ?: 0,
                $data['view_url']
            );
            
            // Properly escape CSV values
            $csv_content .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $csv_row)) . "\n";
        }
        
        // Save to temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'cf7-submissions-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($filepath, $csv_content);
        
        $download_url = $upload_dir['url'] . '/' . $filename;
        
        wp_send_json_success(array(
            'message' => 'Export generated successfully',
            'download_url' => $download_url,
            'filename' => $filename
        ));
    }
    
    /**
     * AJAX handler for CSV download with security validation.
     * Serves generated CSV files with proper headers and cleanup.
     * 
     * @since 1.0.0
     */
    public function ajax_download_csv() {
        // Check nonce
        if (!wp_verify_nonce($_GET['nonce'], 'cf7_dashboard_nonce')) {
            wp_die('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $filename = sanitize_file_name($_GET['file']);
        if (empty($filename)) {
            wp_die('Invalid filename');
        }
        
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        // Verify file exists and is a CSV
        if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'csv') {
            wp_die('File not found or invalid file type');
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file content
        readfile($filepath);
        
        // Clean up - delete the temporary file
        unlink($filepath);
        
        exit;
    }
    
    /**
     * AJAX handler for outstanding actions with priority sorting.
     * Retrieves pending actions ordered by priority and due date.
     * 
     * @since 1.0.0
     */
    public function ajax_get_outstanding_actions() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get outstanding actions (pending status) with artist info and assignee info
        $table_name = $wpdb->prefix . 'cf7_actions';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, p.post_title as artist_name, p.ID as submission_id,
                   u.display_name as assigned_to_name
            FROM {$table_name} a
            LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
            LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
            WHERE a.status = %s
            ORDER BY 
                CASE 
                    WHEN a.priority = 'high' THEN 1
                    WHEN a.priority = 'medium' THEN 2
                    WHEN a.priority = 'low' THEN 3
                    ELSE 4
                END,
                a.due_date ASC,
                a.created_at DESC
            LIMIT 10
        ", 'pending'));
        
        $actions = array();
        $current_time = current_time('timestamp');
        
        foreach ($results as $result) {
            $due_date = null;
            $is_overdue = false;
            $due_date_formatted = '';
            
            if ($result->due_date && $result->due_date !== '0000-00-00 00:00:00') {
                $due_date = strtotime($result->due_date);
                $is_overdue = $due_date < $current_time;
                
                if ($is_overdue) {
                    $days_overdue = floor(($current_time - $due_date) / DAY_IN_SECONDS);
                    $due_date_formatted = $days_overdue === 0 ? 'Due today' : $days_overdue . ' days overdue';
                } else {
                    $days_until = ceil(($due_date - $current_time) / DAY_IN_SECONDS);
                    $due_date_formatted = $days_until === 0 ? 'Due today' : 'Due in ' . $days_until . ' days';
                }
            }
            
            $actions[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'description' => wp_trim_words($result->description, 15),
                'priority' => $result->priority,
                'artist_name' => $result->artist_name ?: 'Unknown Artist',
                'assigned_to_name' => $result->assigned_to_name ?: 'Unassigned',
                'submission_id' => $result->submission_id,
                'due_date_formatted' => $due_date_formatted,
                'is_overdue' => $is_overdue,
                'created_at' => human_time_diff(strtotime($result->created_at), current_time('timestamp')) . ' ago',
                'edit_link' => admin_url('post.php?post=' . $result->submission_id . '&action=edit&from=outstanding-actions#cf7-tab-actions')
            );
        }
        
        wp_send_json_success(array(
            'actions' => $actions,
            'total_count' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'pending'))
        ));
    }
    
    public function ajax_submission_action() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $submission_id = intval($_POST['id']);
        $action = sanitize_text_field($_POST['submission_action']);
        
        if (!$submission_id || !$action) {
            wp_send_json_error('Missing required parameters');
        }
        
        switch ($action) {
            case 'delete':
                if (wp_delete_post($submission_id, true)) {
                    wp_send_json_success(array('message' => 'Submission deleted successfully'));
                } else {
                    wp_send_json_error('Failed to delete submission');
                }
                break;
                
            default:
                wp_send_json_error('Unknown action');
        }
    }
    
    // =====================================================================
    // UTILITY FUNCTIONS SECTION
    // =====================================================================

    /**
     * Get submission counts by status with taxonomy validation.
     * Returns count of submissions for specified status with error handling.
     */
    private function get_submissions_count_by_status($status) {
        // Check if taxonomy exists
        if (!taxonomy_exists('submission_status')) {
            return 0;
        }
        
        // Check if term exists
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            return 0;
        }
        
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => $status,
                )
            ),
            'fields' => 'ids'
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Calculate percentage changes for dashboard trend indicators.
     * Compares current statistics with cached historical data for trend analysis.
     */
    private function calculate_percentage_changes($current_stats) {
        // Get cached stats from 7 days ago for comparison
        $previous_stats = $this->get_cached_daily_stats(7);
        
        $changes = array();
        
        foreach (['total', 'new', 'reviewed', 'awaiting-information', 'shortlisted', 'selected', 'rejected', 'unread_messages'] as $stat_type) {
            // Convert underscore to hyphen for consistency with JavaScript
            $current_key = str_replace('-', '_', $stat_type);
            $current = $current_stats[$current_key];
            $previous = $previous_stats[$current_key] ?? 0;
            
            if ($previous > 0) {
                $change = (($current - $previous) / $previous) * 100;
                $changes[$stat_type . '_change'] = round($change, 1);
            } else {
                // If no previous data, show as 100% increase if we have current data
                $changes[$stat_type . '_change'] = $current > 0 ? 100 : 0;
            }
        }
        
        // Cache today's stats for future comparisons
        $this->cache_daily_stats($current_stats);
        
        return $changes;
    }
    
    /**
     * Cache daily statistics for trend calculations and performance.
     * Stores current statistics for historical comparison and reporting.
     */
    private function cache_daily_stats($stats) {
        $today = date('Y-m-d');
        $cache_key = 'cf7_daily_stats_' . $today;
        
        // Only cache once per day
        if (!get_transient($cache_key)) {
            $cache_data = array(
                'date' => $today,
                'stats' => $stats,
                'timestamp' => current_time('timestamp')
            );
            
            // Cache for 25 hours (longer than a day to avoid edge cases)
            set_transient($cache_key, $cache_data, 25 * HOUR_IN_SECONDS);
            
            // Also store in options for permanent history (keep last 30 days)
            $this->store_daily_stats_history($today, $stats);
        }
    }
    
    /**
     * Clear daily statistics cache to force data refresh.
     * Removes cached statistics to ensure fresh data retrieval.
     */
    private function clear_daily_stats_cache() {
        $today = date('Y-m-d');
        $cache_key = 'cf7_daily_stats_' . $today;
        delete_transient($cache_key);
    }
    
    /**
     * Retrieve cached daily statistics from specified date.
     * Attempts multiple cache sources with fallback to historical calculation.
     */
    private function get_cached_daily_stats($days_ago) {
        $target_date = date('Y-m-d', strtotime("-{$days_ago} days"));
        $cache_key = 'cf7_daily_stats_' . $target_date;
        
        // Try to get from transient cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data && isset($cached_data['stats'])) {
            return $cached_data['stats'];
        }
        
        // Try to get from permanent history
        $history_stats = $this->get_daily_stats_from_history($target_date);
        if ($history_stats) {
            return $history_stats;
        }
        
        // Fallback: calculate historical stats (less accurate but works)
        return $this->get_historical_stats($days_ago);
    }
    
    /**
     * Store daily statistics in permanent history for trend analysis.
     * Maintains rolling 30-day history of dashboard statistics.
     */
    private function store_daily_stats_history($date, $stats) {
        $history_key = 'cf7_daily_stats_history';
        $history = get_option($history_key, array());
        
        // Add today's stats
        $history[$date] = $stats;
        
        // Keep only last 30 days
        if (count($history) > 30) {
            $dates = array_keys($history);
            sort($dates);
            $oldest_dates = array_slice($dates, 0, count($dates) - 30);
            
            foreach ($oldest_dates as $old_date) {
                unset($history[$old_date]);
            }
        }
        
        update_option($history_key, $history);
    }
    
    /**
     * Get daily statistics from permanent history storage.
     * Retrieves stored historical statistics for specified date.
     */
    private function get_daily_stats_from_history($date) {
        $history_key = 'cf7_daily_stats_history';
        $history = get_option($history_key, array());
        
        return isset($history[$date]) ? $history[$date] : null;
    }
    
    /**
     * Calculate historical statistics for trend comparison.
     * Generates statistics from specified date threshold for percentage calculations.
     */
    private function get_historical_stats($days_ago) {
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days_ago} days"));
        
        // Get total submissions from X days ago
        $total_args = array(
            'post_type' => 'cf7_submission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'date_query' => array(
                array(
                    'before' => $date_threshold,
                )
            ),
            'fields' => 'ids'
        );
        
        $total_query = new WP_Query($total_args);
        $historical_total = $total_query->found_posts;
        
        $historical_stats = array(
            'total' => $historical_total,
            'new' => $this->get_submissions_count_by_status_before_date('new', $date_threshold),
            'reviewed' => $this->get_submissions_count_by_status_before_date('reviewed', $date_threshold),
            'awaiting_information' => $this->get_submissions_count_by_status_before_date('awaiting-information', $date_threshold),
            'shortlisted' => $this->get_submissions_count_by_status_before_date('shortlisted', $date_threshold),
            'selected' => $this->get_submissions_count_by_status_before_date('selected', $date_threshold),
            'rejected' => $this->get_submissions_count_by_status_before_date('rejected', $date_threshold),
            'unread_messages' => 0, // Historical unread messages tracking not implemented yet
        );
        
        return $historical_stats;
    }
    
    /**
     * Get submission counts by status before specified date.
     * Returns historical counts for trend percentage calculations.
     */
    private function get_submissions_count_by_status_before_date($status, $date_threshold) {
        // Check if taxonomy exists
        if (!taxonomy_exists('submission_status')) {
            return 0;
        }
        
        // Check if term exists
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            return 0;
        }
        
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => $status,
                )
            ),
            'date_query' => array(
                array(
                    'before' => $date_threshold,
                )
            ),
            'fields' => 'ids'
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get total count of unread messages across all submissions.
     * Delegates to conversations class for centralized message counting.
     */
    private function get_unread_messages_count() {
        return CF7_Artist_Submissions_Conversations::get_total_unviewed_count();
    }
    
    /**
     * AJAX handler for marking individual messages as read.
     * 
     * Updates specific message read status in conversations table with
     * timestamp tracking and validation. Provides granular message
     * management for dashboard message activity panel.
     * 
     * @since 1.0.0
     */
    public function ajax_mark_message_read() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $message_id = intval($_POST['message_id']);
        if (!$message_id) {
            wp_send_json_error('Invalid message ID');
        }
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        
        // Get the submission ID for this message
        $submission_id = $wpdb->get_var($wpdb->prepare(
            "SELECT submission_id FROM {$conversations_table} WHERE id = %d",
            $message_id
        ));
        
        if (!$submission_id) {
            wp_send_json_error('Message not found');
        }
        
        // Mark this specific message as viewed
        $result = $wpdb->update(
            $conversations_table,
            array('admin_viewed_at' => current_time('mysql')),
            array('id' => $message_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Message marked as read');
        } else {
            wp_send_json_error('Failed to mark message as read');
        }
    }
    
    /**
     * AJAX handler for marking all submission messages as read.
     * 
     * Marks all unread messages for specific submission using conversations
     * class delegation. Provides batch message management functionality
     * for submission-specific message clearing operations.
     * 
     * @since 1.0.0
     */
    public function ajax_mark_submission_read() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $submission_id = intval($_POST['submission_id']);
        if (!$submission_id) {
            wp_send_json_error('Invalid submission ID');
        }
        
        // Use the conversations class method to mark messages as viewed
        $result = CF7_Artist_Submissions_Conversations::mark_messages_as_viewed($submission_id);
        
        if ($result !== false) {
            wp_send_json_success('Messages marked as read for submission');
        } else {
            wp_send_json_error('Failed to mark messages as read');
        }
    }
    
    /**
     * AJAX handler for bulk message read operations.
     * 
     * Supports both submission-specific and global message read operations
     * based on submission ID parameter. Provides comprehensive message
     * management with conditional logic for targeted or system-wide
     * message status updates.
     * 
     * @since 1.0.0
     */
    public function ajax_mark_all_read() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        if ($submission_id) {
            // Mark all messages for a specific submission
            $result = CF7_Artist_Submissions_Conversations::mark_messages_as_viewed($submission_id);
            $message = sprintf('%d messages marked as read for submission', $result);
        } else {
            // Mark all unviewed messages across all submissions
            global $wpdb;
            $conversations_table = $wpdb->prefix . 'cf7_conversations';
            
            $result = $wpdb->update(
                $conversations_table,
                array('admin_viewed_at' => current_time('mysql')),
                array(
                    'direction' => 'inbound',
                    'admin_viewed_at' => null
                ),
                array('%s'),
                array('%s', '%s')
            );
            $message = sprintf('%d messages marked as read', $result);
        }
        
        wp_send_json_success($message);
    }

    /**
     * AJAX handler for retrieving today's activity statistics.
     * 
     * Provides daily submission count for dashboard activity indicators
     * with date filtering and validation. Supports real-time activity
     * tracking for current day submission management.
     * 
     * @since 1.0.0
     */
    public function ajax_get_today_activity() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        $count = get_posts(array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $today_start,
                    'before' => $today_end,
                    'inclusive' => true
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        wp_send_json_success(array(
            'count' => count($count),
            'date' => date('Y-m-d')
        ));
    }
    
    /**
     * AJAX handler for weekly activity statistics and trends.
     * 
     * Calculates submission counts for specified date ranges with flexible
     * date parameters. Provides weekly trend analysis for dashboard activity
     * monitoring and submission volume tracking functionality.
     * 
     * @since 1.0.0
     */
    public function ajax_get_weekly_activity() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        if (empty($date_from)) {
            // Default to start of current week
            $start_of_week = date('Y-m-d', strtotime('this week'));
            $date_from = $start_of_week;
        }
        
        if (empty($date_to)) {
            $date_to = date('Y-m-d');
        }

        $week_start = $date_from . ' 00:00:00';
        $week_end = $date_to . ' 23:59:59';

        $count = get_posts(array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $week_start,
                    'before' => $week_end,
                    'inclusive' => true
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        wp_send_json_success(array(
            'count' => count($count),
            'date_from' => $date_from,
            'date_to' => $date_to
        ));
    }
    
    /**
     * Perform enhanced search including artistic medium terms.
     * Searches through post content, titles, meta fields, and artistic medium taxonomy terms.
     * 
     * @param string $search Search query (can be comma-separated for multiple terms)
     * @return array Array of post IDs that match the search criteria
     */
    private function perform_enhanced_search($search) {
        global $wpdb;
        
        $search = trim($search);
        if (empty($search)) {
            return array();
        }
        
        // Split search by commas and clean up terms
        $search_terms = array_map('trim', explode(',', $search));
        $search_terms = array_filter($search_terms); // Remove empty terms
        
        $post_ids = array();
        
        foreach ($search_terms as $term) {
            $term = sanitize_text_field($term);
            if (empty($term)) {
                continue;
            }
            
            $term_post_ids = array();
            
            // 1. Search in artistic medium taxonomy
            $medium_terms = get_terms(array(
                'taxonomy' => 'artistic_medium',
                'name__like' => $term,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($medium_terms) && !empty($medium_terms)) {
                foreach ($medium_terms as $medium_term) {
                    $posts_with_medium = get_objects_in_term($medium_term->term_id, 'artistic_medium');
                    if (!empty($posts_with_medium)) {
                        $term_post_ids = array_merge($term_post_ids, $posts_with_medium);
                    }
                }
            }
            
            // 2. Search in post titles and content (WordPress default search behavior)
            $wp_search_query = new WP_Query(array(
                'post_type' => 'cf7_submission',
                'post_status' => 'publish',
                's' => $term,
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            if (!empty($wp_search_query->posts)) {
                $term_post_ids = array_merge($term_post_ids, $wp_search_query->posts);
            }
            
            // 3. Search in common meta fields
            $meta_fields = array(
                'cf7_artist-name',
                'cf7_email',
                'cf7_your-email',
                'cf7_phone',
                'cf7_location',
                'cf7_website',
                'cf7_instagram',
                'cf7_artist-statement',
                'cf7_submission-comments',
                'cf7_curator_notes'
            );
            
            foreach ($meta_fields as $field) {
                $meta_query = new WP_Query(array(
                    'post_type' => 'cf7_submission',
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => $field,
                            'value' => $term,
                            'compare' => 'LIKE'
                        )
                    ),
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                
                if (!empty($meta_query->posts)) {
                    $term_post_ids = array_merge($term_post_ids, $meta_query->posts);
                }
            }
            
            // 4. Search in open_call taxonomy as well
            $call_terms = get_terms(array(
                'taxonomy' => 'open_call',
                'name__like' => $term,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($call_terms) && !empty($call_terms)) {
                foreach ($call_terms as $call_term) {
                    $posts_with_call = get_objects_in_term($call_term->term_id, 'open_call');
                    if (!empty($posts_with_call)) {
                        $term_post_ids = array_merge($term_post_ids, $posts_with_call);
                    }
                }
            }
            
            // 5. Search in submission_status taxonomy
            $status_terms = get_terms(array(
                'taxonomy' => 'submission_status',
                'name__like' => $term,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($status_terms) && !empty($status_terms)) {
                foreach ($status_terms as $status_term) {
                    $posts_with_status = get_objects_in_term($status_term->term_id, 'submission_status');
                    if (!empty($posts_with_status)) {
                        $term_post_ids = array_merge($term_post_ids, $posts_with_status);
                    }
                }
            }
            
            // Remove duplicates for this term
            $term_post_ids = array_unique($term_post_ids);
            
            // For comma-separated searches, we want posts that match ANY of the terms (OR logic)
            $post_ids = array_merge($post_ids, $term_post_ids);
        }
        
        // Remove duplicates and ensure all IDs are for cf7_submission post type
        $post_ids = array_unique($post_ids);
        $validated_ids = array();
        
        foreach ($post_ids as $id) {
            if (get_post_type($id) === 'cf7_submission') {
                $validated_ids[] = $id;
            }
        }
        
        return $validated_ids;
    }

    /**
     * Get artist name from submission metadata with fallback options.
     * Attempts multiple field patterns and provides intelligent email parsing.
     */
    private function get_artist_name($submission_id) {
        // Try common artist name field patterns
        $name_fields = array(
            'artist-name', 
            'artist_name', 
            'your-name', 
            'name', 
            'full-name',
            'first-name',
            'fname'
        );
        
        foreach ($name_fields as $field) {
            $name = get_post_meta($submission_id, 'cf7_' . $field, true);
            if (!empty($name)) {
                return $name;
            }
        }
        
        // Fallback: try to get from email or use generic
        $email = get_post_meta($submission_id, 'cf7_email', true);
        if (empty($email)) {
            $email = get_post_meta($submission_id, 'cf7_your-email', true);
        }
        
        if (!empty($email)) {
            // Extract name part from email if possible
            $email_parts = explode('@', $email);
            $local_part = $email_parts[0];
            // Convert common patterns like firstname.lastname to readable format
            $readable_name = str_replace(array('.', '_', '-'), ' ', $local_part);
            return ucwords($readable_name);
        }
        
        return 'Artist #' . $submission_id;
    }
    
    /**
     * Get artistic mediums for submission with color metadata.
     * 
     * Retrieves medium taxonomy terms with associated color styling
     * for dashboard display and submission categorization. Provides
     * comprehensive medium information with visual presentation data.
     * 
     * @since 1.0.0
     */
    private function get_submission_mediums($post_id) {
        $terms = get_the_terms($post_id, 'artistic_medium');
        
        if (empty($terms) || is_wp_error($terms)) {
            return array();
        }
        
        $mediums = array();
        foreach ($terms as $term) {
            $bg_color = get_term_meta($term->term_id, 'medium_color', true);
            $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
            
            $mediums[] = array(
                'name' => $term->name,
                'slug' => $term->slug,
                'bg_color' => $bg_color ?: '#6b7280',
                'text_color' => $text_color ?: '#ffffff'
            );
        }
        
        return $mediums;
    }
    
    /**
     * Get text mediums for submission with color metadata.
     * 
     * Retrieves text medium taxonomy terms with associated color styling
     * for dashboard display and submission categorization. Provides
     * comprehensive text medium information with visual presentation data.
     * 
     * @since 1.2.0
     */
    private function get_submission_text_mediums($post_id) {
        $terms = get_the_terms($post_id, 'text_medium');
        
        if (empty($terms) || is_wp_error($terms)) {
            return array();
        }
        
        $mediums = array();
        foreach ($terms as $term) {
            $bg_color = get_term_meta($term->term_id, 'medium_color', true);
            $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
            
            $mediums[] = array(
                'name' => $term->name,
                'slug' => $term->slug,
                'bg_color' => $bg_color ?: '#805AD5',
                'text_color' => $text_color ?: '#ffffff'
            );
        }
        
        return $mediums;
    }
    
    /**
     * Format mediums data for CSV export.
     * 
     * Extracts medium names from array of medium objects and formats them
     * as a comma-separated string suitable for CSV export.
     * 
     * @param array $mediums Array of medium objects with name, slug, and color data
     * @return string Comma-separated list of medium names
     * @since 1.2.0
     */
    private function format_mediums_for_export($mediums) {
        if (empty($mediums) || !is_array($mediums)) {
            return '';
        }
        
        $names = array();
        foreach ($mediums as $medium) {
            if (is_array($medium) && isset($medium['name'])) {
                $names[] = $medium['name'];
            } elseif (is_string($medium)) {
                $names[] = $medium;
            }
        }
        
        return implode(', ', $names);
    }
}
