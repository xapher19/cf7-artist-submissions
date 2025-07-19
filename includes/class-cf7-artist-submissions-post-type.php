<?php
/**
 * Custom Post Type for CF7 Submissions
 */
class CF7_Artist_Submissions_Post_Type {
    
    public function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_filter('manage_cf7_submission_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cf7_submission_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        
        // Add bulk action for CSV export
        add_filter('bulk_actions-edit-cf7_submission', array($this, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-cf7_submission', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Add admin notices for export success
        add_action('admin_notices', array($this, 'bulk_action_admin_notice'));
        
        // Make columns sortable
        add_filter('manage_edit-cf7_submission_sortable_columns', array($this, 'set_sortable_columns'));
        add_action('pre_get_posts', array($this, 'sort_submissions'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        
        wp_enqueue_style('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/admin.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
    }
    
    public function register_post_type() {
        $options = get_option('cf7_artist_submissions_options', array());
        $menu_label = !empty($options['menu_label']) ? $options['menu_label'] : 'Submissions';
        
        $labels = array(
            'name'               => $menu_label,
            'singular_name'      => 'Submission',
            'menu_name'          => $menu_label,
            'name_admin_bar'     => 'Submission',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Submission',
            'new_item'           => 'New Submission',
            'edit_item'          => 'Edit Submission',
            'view_item'          => 'View Submission',
            'all_items'          => 'All Submissions',
            'search_items'       => 'Search Submissions',
            'parent_item_colon'  => 'Parent Submissions:',
            'not_found'          => 'No submissions found.',
            'not_found_in_trash' => 'No submissions found in Trash.'
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 3, // Below Dashboard
            'menu_icon'          => 'dashicons-format-aside',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'submission'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array('title', 'custom-fields'),
        );
        
        register_post_type('cf7_submission', $args);
    }
    
    public function register_taxonomy() {
        $labels = array(
            'name'                       => 'Statuses',
            'singular_name'              => 'Status',
            'search_items'               => 'Search Statuses',
            'popular_items'              => 'Popular Statuses',
            'all_items'                  => 'All Statuses',
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => 'Edit Status',
            'update_item'                => 'Update Status',
            'add_new_item'               => 'Add New Status',
            'new_item_name'              => 'New Status Name',
            'separate_items_with_commas' => 'Separate statuses with commas',
            'add_or_remove_items'        => 'Add or remove statuses',
            'choose_from_most_used'      => 'Choose from the most used statuses',
            'menu_name'                  => 'Status',
        );
        
        $args = array(
            'hierarchical'          => true, // Changed to true to make it work as a radio select
            'labels'                => $labels,
            'show_ui'               => false, // Hide from admin menu since we have interactive status on artist pages
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'submission-status'),
            'meta_box_cb'           => array($this, 'status_meta_box')
        );
        
        register_taxonomy('submission_status', 'cf7_submission', $args);
        
        // Register all status terms with slugs
        wp_insert_term('New', 'submission_status', array('slug' => 'new'));
        wp_insert_term('Reviewed', 'submission_status', array('slug' => 'reviewed'));
        wp_insert_term('Awaiting Information', 'submission_status', array('slug' => 'awaiting-information'));
        wp_insert_term('Selected', 'submission_status', array('slug' => 'selected'));
        wp_insert_term('Rejected', 'submission_status', array('slug' => 'rejected'));
    }
    
    /**
     * Custom meta box for status as radio buttons
     */
    public function status_meta_box($post, $box) {
        $taxonomy = $box['args']['taxonomy'];
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        if (empty($terms)) {
            return;
        }
        
        $post_terms = wp_get_object_terms($post->ID, $taxonomy);
        $selected_term_id = !empty($post_terms) ? $post_terms[0]->term_id : 0;
        
        echo '<div class="tagsdiv" id="' . esc_attr($taxonomy) . '">';
        echo '<div class="jaxtag">';
        echo '<div class="nojs-tags">';
        
        echo '<p>' . __('Select the submission status:', 'cf7-artist-submissions') . '</p>';
        
        foreach ($terms as $term) {
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="radio" name="tax_input[' . esc_attr($taxonomy) . '][]" value="' . esc_attr($term->term_id) . '" ' . checked($term->term_id, $selected_term_id, false) . '>';
            echo ' ' . esc_html($term->name);
            echo '</label>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Define the columns for the submissions list
     */
    public function set_custom_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Name', 'cf7-artist-submissions'),
            'submission_date' => __('Submission Date', 'cf7-artist-submissions'),
            'status' => __('Status', 'cf7-artist-submissions'),
            'notes' => __('Curator Notes', 'cf7-artist-submissions'),
        );
        return $columns;
    }
    
    /**
     * Make columns sortable
     */
    public function set_sortable_columns($columns) {
        $columns['title'] = 'title';
        $columns['submission_date'] = 'submission_date';
        $columns['status'] = 'status';
        return $columns;
    }
    
    /**
     * Handle the sorting of columns
     */
    public function sort_submissions($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'cf7_submission') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        // Sort by submission date
        if ('submission_date' === $orderby) {
            $query->set('meta_key', 'cf7_submission_date');
            $query->set('orderby', 'meta_value');
        }
        
        // Sort by status
        if ('status' === $orderby) {
            $query->set('orderby', 'taxonomy');
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'submission_status',
                    'field' => 'id',
                    'terms' => get_terms(array(
                        'taxonomy' => 'submission_status',
                        'fields' => 'ids',
                        'hide_empty' => false
                    ))
                )
            ));
        }
    }
    
    /**
     * Display the content for our custom columns
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'submission_date':
                $date = get_post_meta($post_id, 'cf7_submission_date', true);
                if (!empty($date)) {
                    echo esc_html(date_i18n('F j, Y g:i a', strtotime($date)));
                } else {
                    echo get_the_date('F j, Y g:i a', $post_id);
                }
                break;
                
            case 'status':
                $terms = get_the_terms($post_id, 'submission_status');
                if (!empty($terms)) {
                    $status = $terms[0]->name;
                    echo '<span class="submission-status status-' . sanitize_html_class(strtolower($status)) . '">' . esc_html($status) . '</span>';
                } else {
                    echo '<span class="submission-status status-new">New</span>';
                }
                break;
                
            case 'notes':
                $notes = get_post_meta($post_id, 'cf7_curator_notes', true);
                if (!empty($notes)) {
                    // Truncate notes to 100 characters with ellipsis
                    if (strlen($notes) > 100) {
                        $notes = substr($notes, 0, 100) . '...';
                    }
                    echo esc_html($notes);
                } else {
                    echo '<span class="no-notes">—</span>';
                }
                break;
        }
    }
    
    /**
     * Register bulk actions for CSV export
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['export_csv'] = __('Export to CSV', 'cf7-artist-submissions');
        return $bulk_actions;
    }
    
    /**
     * Handle the CSV export bulk action
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action !== 'export_csv') {
            return $redirect_to;
        }
        
        // Security check
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to export submissions.', 'cf7-artist-submissions'));
        }
        
        // Verify nonce for bulk actions
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-posts')) {
            wp_die(__('Security check failed.', 'cf7-artist-submissions'));
        }
        
        // No submissions selected
        if (empty($post_ids)) {
            return $redirect_to;
        }
        
        // Generate and serve the CSV file
        $this->generate_csv_export($post_ids);
        
        // We're serving the file, so this won't actually redirect
        return $redirect_to;
    }
    
    /**
     * Admin notice for export actions
     */
    public function bulk_action_admin_notice() {
        if (!empty($_REQUEST['export_csv']) && (int) $_REQUEST['export_csv'] === 1) {
            $message = sprintf(
                __('Exported %d submissions to CSV.', 'cf7-artist-submissions'),
                (int) $_REQUEST['post_count']
            );
            echo '<div class="updated"><p>' . esc_html($message) . '</p></div>';
        }
    }
    
    /**
     * Generate and serve the CSV export
     */
    private function generate_csv_export($post_ids) {
        // Validate post IDs
        $post_ids = array_map('intval', $post_ids);
        $post_ids = array_filter($post_ids, function($id) {
            return get_post_type($id) === 'cf7_submission';
        });
        
        if (empty($post_ids)) {
            return;
        }
        
        // Get all possible meta fields across all selected submissions
        $meta_fields = $this->get_all_meta_fields($post_ids);
        
        // Sort meta fields logically
        $ordered_fields = $this->order_meta_fields($meta_fields);
        
        // Set up CSV headers
        $headers = array(
            'ID',
            'Name',
            'Submission Date',
            'Status',
        );
        
        // Add meta fields to headers (clean up the field names)
        foreach ($ordered_fields as $field) {
            $headers[] = ucwords(str_replace(array('cf7_', '_', '-'), ' ', $field));
        }
        
        // Add Curator Notes to the end
        $headers[] = 'Curator Notes';
        
        // Start output buffer for CSV content
        ob_start();
        
        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers to CSV
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }
            
            // Get status
            $status_terms = wp_get_object_terms($post_id, 'submission_status');
            $status = !empty($status_terms) ? $status_terms[0]->name : 'New';
            
            // Start with core fields
            $row = array(
                $post_id,
                $post->post_title,
                get_post_meta($post_id, 'cf7_submission_date', true),
                $status,
            );
            
            // Add meta fields
            foreach ($ordered_fields as $field) {
                $value = get_post_meta($post_id, $field, true);
                
                // Format value for CSV
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }
            
            // Add curator notes
            $row[] = get_post_meta($post_id, 'cf7_curator_notes', true);
            
            // Write row to CSV
            fputcsv($output, $row);
        }
        
        // Get CSV content from output buffer
        $csv_content = ob_get_clean();
        
        // Set headers for file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=submissions-export-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output CSV content
        echo $csv_content;
        exit;
    }
    
    /**
     * Get all meta fields from the selected submissions
     */
    private function get_all_meta_fields($post_ids) {
        global $wpdb;
        
        $meta_fields = array();
        
        // Get all meta keys for these posts
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} 
             WHERE post_id IN (" . implode(',', array_fill(0, count($post_ids), '%d')) . ") 
             AND meta_key LIKE 'cf7_%'",
            $post_ids
        );
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $row) {
            // Skip certain meta keys
            if ($row->meta_key === 'cf7_submission_date' || 
                $row->meta_key === 'cf7_curator_notes' ||
                substr($row->meta_key, 0, 8) === 'cf7_file_' ||
                $row->meta_key === 'cf7_your-work-raw') {
                continue;
            }
            
            $meta_fields[] = $row->meta_key;
        }
        
        return $meta_fields;
    }
    
    /**
     * Order meta fields in a logical manner
     */
    private function order_meta_fields($fields) {
        $ordered = array();
        
        // Priority fields
        $priority_fields = array(
            'cf7_artist-name',
            'cf7_pronouns',
            'cf7_email',
            'cf7_portfolio-link',
            'cf7_artist-statement'
        );
        
        // First add priority fields in order
        foreach ($priority_fields as $field) {
            if (in_array($field, $fields)) {
                $ordered[] = $field;
            }
        }
        
        // Then add remaining fields
        foreach ($fields as $field) {
            if (!in_array($field, $ordered)) {
                $ordered[] = $field;
            }
        }
        
        return $ordered;
    }
}