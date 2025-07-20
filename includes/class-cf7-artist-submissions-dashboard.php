<?php
/**
 * Modern Interactive Dashboard for CF7 Artist Submissions
 */
class CF7_Artist_Submissions_Dashboard {
    
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
        add_action('wp_ajax_cf7_dashboard_submission_action', array($this, 'ajax_submission_action'));
        add_action('wp_ajax_cf7_dashboard_mark_message_read', array($this, 'ajax_mark_message_read'));
        add_action('wp_ajax_cf7_dashboard_mark_submission_read', array($this, 'ajax_mark_submission_read'));
        add_action('wp_ajax_cf7_dashboard_mark_all_read', array($this, 'ajax_mark_all_read'));
    }

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
    
    public function redirect_main_menu_to_dashboard() {
        global $pagenow;
        
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'cf7_submission' && !isset($_GET['page'])) {
            wp_redirect(admin_url('edit.php?post_type=cf7_submission&page=cf7-dashboard'));
            exit;
        }
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'cf7-dashboard') === false) {
            return;
        }
        
        wp_enqueue_script('cf7-dashboard-js', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/dashboard.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
        wp_enqueue_style('cf7-dashboard-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/dashboard.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
        
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
    
    public function render_dashboard_page() {
        // Get initial stats for server-side rendering
        global $wpdb;
        $post_table = $wpdb->prefix . 'posts';
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$post_table} WHERE post_type = 'cf7_submission' AND post_status = 'publish'");
        $new = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$post_table} p LEFT JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id LEFT JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = 'cf7_submission' AND p.post_status = 'publish' AND (t.name = 'New' OR t.name IS NULL)");
        $reviewed = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = 'cf7_submission' AND p.post_status = 'publish' AND t.name = 'Reviewed'");
        $awaiting_information = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = 'cf7_submission' AND p.post_status = 'publish' AND t.name = 'Awaiting Information'");
        $selected = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = 'cf7_submission' AND p.post_status = 'publish' AND t.name = 'Selected'");
        $rejected = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$post_table} p INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id INNER JOIN {$wpdb->prefix}terms t ON tr.term_taxonomy_id = t.term_id WHERE p.post_type = 'cf7_submission' AND p.post_status = 'publish' AND t.name = 'Rejected'");
        $unread_messages = $wpdb->get_var("SELECT COUNT(DISTINCT c.submission_id) FROM {$conversations_table} c WHERE c.is_admin_read = 0");
        
        $stats = array(
            'total' => (int) $total,
            'new' => (int) $new,
            'reviewed' => (int) $reviewed,
            'awaiting_information' => (int) $awaiting_information,
            'selected' => (int) $selected,
            'rejected' => (int) $rejected,
            'unread_messages' => (int) $unread_messages
        );
        
        ?>
        <div class="cf7-modern-dashboard">
            <!-- Dashboard Header -->
            <div class="cf7-dashboard-header">
                <div class="cf7-header-content">
                    <h1 class="cf7-dashboard-title">Artist Submissions Dashboard</h1>
                    <p class="cf7-dashboard-subtitle">Manage and review artist submissions with powerful tools</p>
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

            <!-- Statistics Cards -->
                        <!-- Statistics Cards Grid (2 rows x 3 columns) -->
            <div class="cf7-stats-grid">
                <!-- Row 1: Overview & Statuses -->
                <div class="cf7-stat-card" data-type="total">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon total">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>Total Submissions</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-stat-card" data-type="new">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon new">
                            <span class="dashicons dashicons-star-filled"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>New</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-stat-card" data-type="reviewed">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon reviewed">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>Reviewed</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 2: Status Workflow -->
                <div class="cf7-stat-card" data-type="awaiting-information">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon awaiting-information">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>Awaiting Information</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-stat-card" data-type="selected">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon selected">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>Selected</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="cf7-stat-card" data-type="rejected">
                    <div class="cf7-stat-header">
                        <div class="cf7-stat-icon rejected">
                            <span class="dashicons dashicons-dismiss"></span>
                        </div>
                        <div class="cf7-stat-content">
                            <h3>Rejected</h3>
                            <div class="cf7-stat-number">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="cf7-dashboard-grid">
                <!-- Left Panel: Submissions Table -->
                <div class="cf7-dashboard-panel cf7-submissions-panel">
                    <div class="cf7-panel-header">
                        <h2>Submissions</h2>
                        <div class="cf7-panel-controls">
                            <div class="cf7-search-wrapper">
                                <div class="cf7-search-input-container">
                                    <input type="text" id="cf7-search-input" placeholder="Search submissions..." class="cf7-search-field">
                                    <div class="cf7-search-icon-wrapper">
                                        <span class="dashicons dashicons-search"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="cf7-status-filter-wrapper">
                                <select id="cf7-status-filter" class="cf7-status-filter">
                                    <option value="" data-icon="dashicons-category" data-color="#718096">All Statuses</option>
                                    <option value="new" data-icon="dashicons-star-filled" data-color="#4299e1">New</option>
                                    <option value="reviewed" data-icon="dashicons-visibility" data-color="#9f7aea">Reviewed</option>
                                    <option value="awaiting-information" data-icon="dashicons-clock" data-color="#dd6b20">Awaiting Information</option>
                                    <option value="selected" data-icon="dashicons-yes-alt" data-color="#48bb78">Selected</option>
                                    <option value="rejected" data-icon="dashicons-dismiss" data-color="#f56565">Rejected</option>
                                </select>
                                <div class="cf7-status-filter-display">
                                    <span class="cf7-status-icon dashicons dashicons-category"></span>
                                    <span class="cf7-status-text">All Statuses</span>
                                    <span class="cf7-status-arrow dashicons dashicons-arrow-down-alt2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date Filters Bar -->
                    <div class="cf7-date-filter-bar">
                        <div class="cf7-date-filter-container">
                            <span class="cf7-filter-label">Filter by Date:</span>
                            <div class="cf7-date-inputs">
                                <input type="date" id="cf7-date-from" class="cf7-date-input" placeholder="From date">
                                <span class="cf7-date-separator">to</span>
                                <input type="date" id="cf7-date-to" class="cf7-date-input" placeholder="To date">
                            </div>
                            <div class="cf7-date-presets">
                                <button type="button" class="cf7-date-preset" data-range="today">Today</button>
                                <button type="button" class="cf7-date-preset" data-range="week">Last 7 Days</button>
                                <button type="button" class="cf7-date-preset" data-range="month">Last 30 Days</button>
                                <button type="button" class="cf7-date-preset" data-range="clear">Clear</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Actions Bar -->
                    <div class="cf7-bulk-actions" id="cf7-bulk-actions" style="display: none;">
                        <div class="cf7-bulk-left">
                            <span class="cf7-selected-count">0 items selected</span>
                        </div>
                        <div class="cf7-bulk-right">
                            <select id="cf7-bulk-action-select" class="cf7-bulk-select">
                                <option value="">Bulk Actions</option>
                                <option value="export">Export Selected</option>
                                <option value="status-reviewed">Mark as Reviewed</option>
                                <option value="status-awaiting-information">Mark as Awaiting Information</option>
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
                        <div class="cf7-pagination" id="cf7-pagination"></div>
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
    
    public function ajax_load_submissions() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array()
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        if (!empty($status)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'submission_status',
                    'field' => 'slug',
                    'terms' => $status
                )
            );
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
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }
    
    private function format_submission_data($post) {
        $status_terms = wp_get_object_terms($post->ID, 'submission_status');
        $status = !empty($status_terms) ? $status_terms[0]->name : 'New';
        $status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'new';
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title ?: 'Untitled Submission',
            'date' => get_the_date('Y-m-d H:i:s', $post),
            'time' => get_the_date('g:i a', $post),
            'status' => $status_slug, // Use slug for CSS classes
            'status_label' => $status, // Human readable label
            'artist_name' => get_post_meta($post->ID, 'cf7_artist-name', true) ?: 'Unknown Artist',
            'email' => get_post_meta($post->ID, 'cf7_email', true) ?: get_post_meta($post->ID, 'your-email', true) ?: 'No email',
            'notes' => get_post_meta($post->ID, 'cf7_curator_notes', true),
            'view_url' => get_edit_post_link($post->ID),
            'edit_url' => get_edit_post_link($post->ID)
        );
    }
    
    public function ajax_bulk_action() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $post_ids = array_map('intval', $_POST['ids'] ?? array());
        
        if (empty($post_ids)) {
            wp_send_json_error('No items selected');
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
    
    private function handle_export($post_ids) {
        $filename = 'submissions-' . date('Y-m-d-H-i-s') . '.csv';
        $file_path = wp_upload_dir()['path'] . '/' . $filename;
        
        $handle = fopen($file_path, 'w');
        
        // CSV headers
        fputcsv($handle, array('ID', 'Artist Name', 'Email', 'Date', 'Status', 'Notes'));
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $data = $this->format_submission_data($post);
                fputcsv($handle, array(
                    $data['id'],
                    $data['artist_name'],
                    $data['email'],
                    $data['date'],
                    $data['status'],
                    $data['notes']
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
    
    private function handle_delete($post_ids) {
        $deleted = 0;
        foreach ($post_ids as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d submissions deleted', $deleted)
        ));
    }
    
    private function handle_status_change($post_ids, $status) {
        $updated = 0;
        
        // Get the term
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        foreach ($post_ids as $post_id) {
            wp_set_post_terms($post_id, array($term->term_id), 'submission_status');
            $updated++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d submissions updated to %s', $updated, $term->name)
        ));
    }
    
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
                'view_url' => admin_url('post.php?post=' . $submission->submission_id . '&action=edit#cf7-conversation')
            );
        }
        
        wp_send_json_success($messages);
    }
    
    public function ajax_update_status() {
        check_ajax_referer('cf7_dashboard_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $term = get_term_by('slug', $status, 'submission_status');
        if (!$term) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        wp_set_post_terms($post_id, array($term->term_id), 'submission_status');
        
        wp_send_json_success(array(
            'message' => 'Status updated successfully'
        ));
    }
    
    public function ajax_get_stats() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
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
    
    public function ajax_export() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Generate CSV export
        $args = array(
            'post_type' => 'cf7_submission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        // Apply filters if provided
        if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
            $args['meta_query'][] = array(
                'key' => 'cf7_submission_status',
                'value' => sanitize_text_field($_POST['status']),
                'compare' => '='
            );
        }
        
        if (!empty($_POST['search'])) {
            $args['s'] = sanitize_text_field($_POST['search']);
        }
        
        $submissions = get_posts($args);
        
        // Create CSV content
        $csv_content = "ID,Title,Email,Status,Date,Link\n";
        foreach ($submissions as $submission) {
            $email = get_post_meta($submission->ID, 'your-email', true);
            $status = get_post_meta($submission->ID, 'cf7_submission_status', true) ?: 'new';
            $link = admin_url('post.php?post=' . $submission->ID . '&action=edit');
            
            $csv_content .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $submission->ID,
                str_replace('"', '""', $submission->post_title),
                str_replace('"', '""', $email),
                $status,
                get_the_date('Y-m-d H:i:s', $submission->ID),
                $link
            );
        }
        
        // Save to temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'cf7-submissions-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($filepath, $csv_content);
        
        $download_url = $upload_dir['url'] . '/' . $filename;
        
        wp_send_json_success(array(
            'message' => 'Export generated successfully',
            'download_url' => $download_url
        ));
    }
    
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
        
        // Get outstanding actions (pending status) with artist info
        $table_name = $wpdb->prefix . 'cf7_actions';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, p.post_title as artist_name, p.ID as submission_id
            FROM {$table_name} a
            LEFT JOIN {$wpdb->posts} p ON a.submission_id = p.ID
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
                'submission_id' => $result->submission_id,
                'due_date_formatted' => $due_date_formatted,
                'is_overdue' => $is_overdue,
                'created_at' => human_time_diff(strtotime($result->created_at), current_time('timestamp')) . ' ago',
                'edit_link' => admin_url('post.php?post=' . $result->submission_id . '&action=edit#cf7-tab-actions')
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
    
    private function calculate_percentage_changes($current_stats) {
        // Get cached stats from 7 days ago for comparison
        $previous_stats = $this->get_cached_daily_stats(7);
        
        $changes = array();
        
        foreach (['total', 'new', 'reviewed', 'awaiting_information', 'selected', 'rejected'] as $stat_type) {
            $current = $current_stats[$stat_type];
            $previous = $previous_stats[$stat_type] ?? 0;
            
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
    
    private function get_daily_stats_from_history($date) {
        $history_key = 'cf7_daily_stats_history';
        $history = get_option($history_key, array());
        
        return isset($history[$date]) ? $history[$date] : null;
    }
    
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
            'selected' => $this->get_submissions_count_by_status_before_date('selected', $date_threshold),
        );
        
        return $historical_stats;
    }
    
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
     * Get count of unread messages
     */
    private function get_unread_messages_count() {
        return CF7_Artist_Submissions_Conversations::get_total_unviewed_count();
    }
    
    /**
     * Get unread messages count from 7 days ago for percentage calculation
     */
    private function get_unread_messages_count_before_date($date) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'cf7_conversations';
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$conversations_table} 
            WHERE read_status = 0 
            AND direction = 'inbound'
            AND (received_at < %s OR (received_at IS NULL AND sent_at < %s))
        ", $date, $date));
        
        return intval($count);
    }
    
    /**
     * Mark a message as read
     */
    public function ajax_mark_message_read() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
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
     * Mark all messages as read for a specific submission
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
     * Mark all messages as read for a submission
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
     * Get artist name from submission
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
}
