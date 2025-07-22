<?php
/**
 * CF7 Artist Submissions - Custom Post Type Management System
 *
 * Comprehensive custom post type and taxonomy management for artist submissions
 * with advanced admin interface features, status management, artistic medium
 * categorization, and modern dashboard integration for streamlined workflow.
 *
 * Features:
 * • Custom post type registration with configurable menu labels
 * • Hierarchical status taxonomy with radio button interface
 * • Artistic mediums taxonomy with color-coded visualization
 * • Advanced admin columns with sortable submission data
 * • Bulk CSV export functionality with comprehensive data extraction
 * • Modern dashboard integration with real-time statistics display
 *
 * @package CF7_Artist_Submissions
 * @subpackage PostTypeManagement
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * CF7 Artist Submissions Post Type Class
 * 
 * Comprehensive custom post type and taxonomy management system for artist
 * submissions with advanced admin interface features, status workflow management,
 * and modern dashboard integration. Provides complete content type infrastructure
 * with enhanced user experience and administrative workflow optimization.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Post_Type {
    
    // ============================================================================
    // INITIALIZATION SECTION
    // ============================================================================
    
    /**
     * Initialize comprehensive post type management system with complete functionality.
     * 
     * Establishes custom post type registration, taxonomy creation, admin interface
     * enhancements, and dashboard integration. Sets up advanced admin columns, bulk
     * actions, sorting capabilities, and modern statistics display for optimized
     * submission management workflow and enhanced user experience.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('init', array($this, 'register_mediums_taxonomy'));
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
        
        // Add modern dashboard in the right position
        add_action('all_admin_notices', array($this, 'add_submissions_dashboard_notice'));
        add_action('admin_footer', array($this, 'move_dashboard_script'));
    }
    
    /**
     * Enqueue administrative assets for submission management interfaces.
     * Loads CSS and JavaScript for list pages with Tabs system coordination.
     */
    public function enqueue_admin_scripts($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        
        // Only load admin styles on submission list page
        if ($screen->id === 'edit-cf7_submission') {
            // Enqueue common styles first (foundation for all other styles)
            wp_enqueue_style('cf7-common-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/common.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            
            // Enqueue admin styles for submission list page (depends on common.css)
            wp_enqueue_style('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/admin.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            
            wp_enqueue_script('jquery');
        }
        
        // For single submission edit pages, the Tabs system handles all assets
    }
    
    // ============================================================================
    // REGISTRATION SECTION
    // ============================================================================
    
    /**
     * Register artist submission custom post type with comprehensive configuration.
     * 
     * Creates custom post type for storing artist submissions with configurable
     * menu labels, appropriate capabilities, and optimized admin interface settings.
     * Implements WordPress best practices for custom content types with enhanced
     * administrative workflow integration and user experience optimization.
     * 
     * @since 1.0.0
     */
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
    
    /**
     * Register submission status taxonomy with comprehensive workflow management.
     * 
     * Creates hierarchical taxonomy for submission status tracking with predefined
     * workflow terms, custom radio button interface, and enhanced admin integration.
     * Implements complete submission lifecycle management with status progression
     * tracking and administrative workflow optimization capabilities.
     * 
     * @since 1.0.0
     */
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
        wp_insert_term('Shortlisted', 'submission_status', array('slug' => 'shortlisted'));
        wp_insert_term('Selected', 'submission_status', array('slug' => 'selected'));
        wp_insert_term('Rejected', 'submission_status', array('slug' => 'rejected'));
    }
    
    /**
     * Register artistic mediums taxonomy with comprehensive categorization system.
     * 
     * Creates non-hierarchical taxonomy for artistic medium tagging with predefined
     * comprehensive medium categories, color-coded visualization, and enhanced admin
     * interface. Implements complete artistic categorization system with visual
     * organization and professional presentation capabilities.
     * 
     * @since 1.0.0
     */
    public function register_mediums_taxonomy() {
        $labels = array(
            'name'                       => __('Artistic Mediums', 'cf7-artist-submissions'),
            'singular_name'              => __('Medium', 'cf7-artist-submissions'),
            'search_items'               => __('Search Mediums', 'cf7-artist-submissions'),
            'popular_items'              => __('Popular Mediums', 'cf7-artist-submissions'),
            'all_items'                  => __('All Mediums', 'cf7-artist-submissions'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Medium', 'cf7-artist-submissions'),
            'update_item'                => __('Update Medium', 'cf7-artist-submissions'),
            'add_new_item'               => __('Add New Medium', 'cf7-artist-submissions'),
            'new_item_name'              => __('New Medium Name', 'cf7-artist-submissions'),
            'separate_items_with_commas' => __('Separate mediums with commas', 'cf7-artist-submissions'),
            'add_or_remove_items'        => __('Add or remove mediums', 'cf7-artist-submissions'),
            'choose_from_most_used'      => __('Choose from most used mediums', 'cf7-artist-submissions'),
            'not_found'                  => __('No mediums found.', 'cf7-artist-submissions'),
            'menu_name'                  => __('Mediums', 'cf7-artist-submissions'),
        );
        
        $args = array(
            'hierarchical'          => false, // Tag-like behavior
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_menu'          => 'edit.php?post_type=cf7_submission',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'artistic-medium'),
            'show_tagcloud'         => true,
            'meta_box_cb'           => 'post_tags_meta_box', // Use default tag-style meta box
        );
        
        register_taxonomy('artistic_medium', 'cf7_submission', $args);
        
        // Add comprehensive artistic mediums as default terms with unique colors
        $default_mediums = array(
            'Art writing' => array('bg' => '#FF6B6B', 'text' => '#8B0000'),           // Coral red with very dark red text
            'Artists\' books' => array('bg' => '#4ECDC4', 'text' => '#1A5C57'),      // Turquoise with very dark teal text
            'Ceramics' => array('bg' => '#45B7D1', 'text' => '#1A4F73'),            // Sky blue with very dark blue text
            'Collage' => array('bg' => '#96CEB4', 'text' => '#2F5233'),             // Mint green with very dark green text
            'Digital' => array('bg' => '#FFEAA7', 'text' => '#8B6F00'),             // Warm yellow with very dark yellow text
            'Drawing' => array('bg' => '#DDA0DD', 'text' => '#6B2C6B'),             // Plum with very dark purple text
            'Film / Video' => array('bg' => '#FFB6C1', 'text' => '#8B3A4F'),        // Light pink with very dark pink text
            'Glass' => array('bg' => '#87CEEB', 'text' => '#2F4F8F'),               // Sky blue light with very dark blue text
            'Graffiti' => array('bg' => '#FF8C69', 'text' => '#8B2500'),            // Salmon with very dark red text
            'Illustration' => array('bg' => '#98D8C8', 'text' => '#2F4F4F'),        // Mint with very dark teal text
            'Installation' => array('bg' => '#F7DC6F', 'text' => '#8B7355'),        // Light yellow with very dark brown text
            'Internet' => array('bg' => '#BB8FCE', 'text' => '#4B0082'),            // Light purple with very dark purple text
            'Jewellery' => array('bg' => '#F8C471', 'text' => '#8B4513'),           // Peach with very dark brown text
            'Live art' => array('bg' => '#85C1E9', 'text' => '#191970'),            // Light blue with very dark blue text
            'Painting' => array('bg' => '#F1948A', 'text' => '#8B0000'),            // Light red with very dark red text
            'Photography' => array('bg' => '#82E0AA', 'text' => '#006400'),         // Light green with very dark green text
            'Printmaking' => array('bg' => '#D7BDE2', 'text' => '#483D8B'),         // Lavender with very dark purple text
            'Projection' => array('bg' => '#A9DFBF', 'text' => '#228B22'),          // Pale green with very dark green text
            'Sculpture' => array('bg' => '#F9E79F', 'text' => '#8B7500'),           // Pale yellow with very dark yellow text
            'Socially Engaged Practice' => array('bg' => '#AED6F1', 'text' => '#000080'), // Pale blue with very dark blue text
            'Sound' => array('bg' => '#FADBD8', 'text' => '#8B1538'),               // Pale pink with very dark pink text
            'Text' => array('bg' => '#D5DBDB', 'text' => '#2F4F4F'),                // Light gray with very dark gray text
            'Textile' => array('bg' => '#ABEBC6', 'text' => '#2E8B57')              // Pale mint with very dark green text
        );
        
        foreach ($default_mediums as $medium => $colors) {
            if (!term_exists($medium, 'artistic_medium')) {
                $term = wp_insert_term($medium, 'artistic_medium');
                if (!is_wp_error($term)) {
                    // Add color meta to the term
                    add_term_meta($term['term_id'], 'medium_color', $colors['bg'], true);
                    add_term_meta($term['term_id'], 'medium_text_color', $colors['text'], true);
                }
            } else {
                // Update existing term with colors - always update to ensure we have the latest colors
                $existing_term = get_term_by('name', $medium, 'artistic_medium');
                if ($existing_term) {
                    // Update both background and text colors (in case we've changed them)
                    update_term_meta($existing_term->term_id, 'medium_color', $colors['bg']);
                    update_term_meta($existing_term->term_id, 'medium_text_color', $colors['text']);
                }
            }
        }
    }
    
    // ============================================================================
    // ADMIN INTERFACE SECTION
    // ============================================================================
    
    /**
     * Custom meta box interface for status selection with radio buttons.
     * Provides user-friendly status selection interface for submission workflow.
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
     * Define custom admin columns for enhanced submission list display.
     * Configures optimized column layout for submission management interface.
     */
    public function set_custom_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Name', 'cf7-artist-submissions'),
            'submission_date' => __('Submission Date', 'cf7-artist-submissions'),
            'status' => __('Status', 'cf7-artist-submissions'),
            'artistic_mediums' => __('Artistic Mediums', 'cf7-artist-submissions'),
            'notes' => __('Curator Notes', 'cf7-artist-submissions'),
        );
        return $columns;
    }
    
    /**
     * Configure sortable columns for enhanced list navigation.
     * Enables sorting by title, submission date, and status for improved workflow.
     */
    public function set_sortable_columns($columns) {
        $columns['title'] = 'title';
        $columns['submission_date'] = 'submission_date';
        $columns['status'] = 'status';
        return $columns;
    }
    
    /**
     * Handle query sorting for custom submission columns.
     * Implements sorting logic for submission date and status columns.
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
     * Display formatted content for custom admin columns.
     * Renders submission data with enhanced formatting and visual elements.
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'submission_date':
                $date = get_post_meta($post_id, 'cf7_submission_date', true);
                if (!empty($date)) {
                    $formatted_date = date_i18n('F j, Y', strtotime($date));
                    $formatted_time = date_i18n('g:i a', strtotime($date));
                    echo '<div class="submission-date-wrapper">';
                    echo '<div class="submission-date">' . esc_html($formatted_date) . '</div>';
                    echo '<div class="submission-time">' . esc_html($formatted_time) . '</div>';
                    echo '</div>';
                } else {
                    $formatted_date = get_the_date('F j, Y', $post_id);
                    $formatted_time = get_the_date('g:i a', $post_id);
                    echo '<div class="submission-date-wrapper">';
                    echo '<div class="submission-date">' . esc_html($formatted_date) . '</div>';
                    echo '<div class="submission-time">' . esc_html($formatted_time) . '</div>';
                    echo '</div>';
                }
                break;
                
            case 'status':
                $terms = get_the_terms($post_id, 'submission_status');
                if (!empty($terms)) {
                    $status = $terms[0]->name;
                    $status_slug = sanitize_html_class(strtolower(str_replace(' ', '-', $status)));
                    echo '<span class="submission-status status-' . $status_slug . '">' . esc_html($status) . '</span>';
                } else {
                    echo '<span class="submission-status status-new">New</span>';
                }
                break;
                
            case 'artistic_mediums':
                $terms = get_the_terms($post_id, 'artistic_medium');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $medium_tags = array();
                    foreach ($terms as $term) {
                        $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                        $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                        
                        if ($bg_color && $text_color) {
                            $style = ' style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . '; border: 1px solid ' . esc_attr($text_color) . '; padding: 2px 6px; border-radius: 3px; font-size: 11px; white-space: nowrap; font-weight: 500;"';
                        } else {
                            $style = ' style="background-color: #4299e1; color: #fff; border: 1px solid #2c5aa0; padding: 2px 6px; border-radius: 3px; font-size: 11px; white-space: nowrap; font-weight: 500;"';
                        }
                        $medium_tags[] = '<span class="medium-tag" data-color="' . esc_attr($bg_color) . '"' . $style . '>' . esc_html($term->name) . '</span>';
                    }
                    $all_mediums = implode(' ', $medium_tags);
                    
                    // Truncate if too many tags
                    if (count($medium_tags) > 3) {
                        $visible_tags = array_slice($medium_tags, 0, 3);
                        $remaining_count = count($medium_tags) - 3;
                        $all_mediums = implode(' ', $visible_tags) . ' <span style="color: #666; font-size: 11px;">+' . $remaining_count . ' more</span>';
                    }
                    
                    echo '<div class="mediums-content">' . $all_mediums . '</div>';
                } else {
                    echo '<span class="no-mediums">—</span>';
                }
                break;
                
            case 'notes':
                $notes = get_post_meta($post_id, 'cf7_curator_notes', true);
                if (!empty($notes)) {
                    // Truncate notes to 80 characters with ellipsis
                    if (strlen($notes) > 80) {
                        $truncated = substr($notes, 0, 80) . '...';
                        echo '<div class="notes-content" title="' . esc_attr($notes) . '">' . esc_html($truncated) . '</div>';
                    } else {
                        echo '<div class="notes-content">' . esc_html($notes) . '</div>';
                    }
                } else {
                    echo '<span class="no-notes">—</span>';
                }
                break;
        }
    }
    
    // ============================================================================
    // BULK ACTIONS SECTION
    // ============================================================================
    
    /**
     * Register CSV export bulk action for comprehensive data extraction.
     * Adds export functionality to submission list bulk actions menu.
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['export_csv'] = __('Export to CSV', 'cf7-artist-submissions');
        return $bulk_actions;
    }
    
    /**
     * Process CSV export bulk action with comprehensive data extraction.
     * 
     * Handles bulk export requests with security validation, data processing,
     * and file generation. Implements complete submission data extraction with
     * metadata organization, status information, and curator notes integration
     * for comprehensive reporting and administrative workflow support.
     * 
     * @since 1.0.0
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
     * Display admin notices for bulk action results.
     * Shows success messages and export confirmation feedback.
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
     * Generate and serve CSV file with submission data.
     * Creates formatted export with metadata and custom fields.
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
     * Get all meta field keys from selected submissions.
     * Filters and returns relevant CF7 metadata fields for export.
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
     * Order meta fields in logical sequence for CSV export.
     * Prioritizes key fields and organizes remaining metadata.
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
    
    // ============================================================================
    // DASHBOARD INTEGRATION SECTION  
    // ============================================================================
    
    /**
     * Display modern dashboard via admin notice positioning.
     * Renders submission statistics and overview cards in admin list view.
     *
     * @since 1.0.0
     */
    public function add_submissions_dashboard_notice() {
        $screen = get_current_screen();
        
        if (!$screen || $screen->id !== 'edit-cf7_submission') {
            return;
        }
        
        // Get submission statistics
        $stats = $this->get_submission_statistics();
        
        echo '<div id="cf7-dashboard-placeholder" style="display: none;">';
        echo '<div class="cf7-submissions-dashboard">';
        echo '<div class="cf7-dashboard-cards">';
        
        // Total submissions card
        echo '<div class="cf7-dashboard-card cf7-card-total">';
        echo '<div class="cf7-card-icon"><span class="dashicons dashicons-portfolio"></span></div>';
        echo '<div class="cf7-card-content">';
        echo '<div class="cf7-card-number">' . esc_html($stats['total']) . '</div>';
        echo '<div class="cf7-card-label">Total Submissions</div>';
        echo '</div>';
        echo '</div>';
        
        // New submissions card
        echo '<div class="cf7-dashboard-card cf7-card-new">';
        echo '<div class="cf7-card-icon"><span class="dashicons dashicons-star-filled"></span></div>';
        echo '<div class="cf7-card-content">';
        echo '<div class="cf7-card-number">' . esc_html($stats['new']) . '</div>';
        echo '<div class="cf7-card-label">New</div>';
        echo '</div>';
        echo '</div>';
        
        // Reviewed submissions card
        echo '<div class="cf7-dashboard-card cf7-card-reviewed">';
        echo '<div class="cf7-card-icon"><span class="dashicons dashicons-visibility"></span></div>';
        echo '<div class="cf7-card-content">';
        echo '<div class="cf7-card-number">' . esc_html($stats['reviewed']) . '</div>';
        echo '<div class="cf7-card-label">Reviewed</div>';
        echo '</div>';
        echo '</div>';
        
        // Shortlisted submissions card
        echo '<div class="cf7-dashboard-card cf7-card-shortlisted">';
        echo '<div class="cf7-card-icon"><span class="dashicons dashicons-paperclip"></span></div>';
        echo '<div class="cf7-card-content">';
        echo '<div class="cf7-card-number">' . esc_html($stats['shortlisted']) . '</div>';
        echo '<div class="cf7-card-label">Shortlisted</div>';
        echo '</div>';
        echo '</div>';
        
        // Selected submissions card
        echo '<div class="cf7-dashboard-card cf7-card-selected">';
        echo '<div class="cf7-card-icon"><span class="dashicons dashicons-yes-alt"></span></div>';
        echo '<div class="cf7-card-content">';
        echo '<div class="cf7-card-number">' . esc_html($stats['selected']) . '</div>';
        echo '<div class="cf7-card-label">Selected</div>';
        echo '</div>';
        echo '</div>';
        
        // Recent activity indicator
        $recent_count = $this->get_recent_submissions_count();
        if ($recent_count > 0) {
            echo '<div class="cf7-dashboard-recent">';
            echo '<span class="dashicons dashicons-clock"></span>';
            echo '<span>' . sprintf(_n('%d new submission in the last 24 hours', '%d new submissions in the last 24 hours', $recent_count, 'cf7-artist-submissions'), $recent_count) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * JavaScript positioning for dashboard elements.
     * Moves dashboard HTML from admin notice to proper location.
     */
    public function move_dashboard_script() {
        $screen = get_current_screen();
        
        if (!$screen || $screen->id !== 'edit-cf7_submission') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Wait a bit for the page to fully load
            setTimeout(function() {
                var dashboardPlaceholder = $('#cf7-dashboard-placeholder');
                var dashboard = dashboardPlaceholder.find('.cf7-submissions-dashboard');
                
                if (dashboard.length) {
                    if ($('.subsubsub').length) {
                        // Insert dashboard AFTER the subsubsub (filter links)
                        $('.subsubsub').after(dashboard);
                    } else {
                        // Fallback: insert after page title
                        $('.wrap .wp-heading-inline').after(dashboard);
                    }
                    
                    // Show the dashboard and remove placeholder
                    dashboard.show();
                    dashboardPlaceholder.remove();
                }
            }, 100);
        });
        </script>
        <?php
    }
    
    /**
     * Legacy dashboard method for backward compatibility.
     * Maintained for existing integrations but no longer active.
     */
    public function add_submissions_dashboard($which) {
        // This method is no longer used but kept to avoid breaking existing code
        return;
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS SECTION
    // ============================================================================
    
    /**
     * Get submission statistics by status.
     * Returns counts for dashboard display and reporting.
     */
    private function get_submission_statistics() {
        $total = wp_count_posts('cf7_submission');
        
        // Get status counts from actual posts
        $status_counts = array(
            'new' => 0,
            'awaiting-information' => 0,
            'reviewed' => 0,
            'shortlisted' => 0,
            'selected' => 0,
            'rejected' => 0
        );
        
        // Get all submissions and their status
        $all_submissions = get_posts(array(
            'post_type' => 'cf7_submission',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($all_submissions as $post_id) {
            $terms = wp_get_object_terms($post_id, 'submission_status');
            if (empty($terms) || is_wp_error($terms)) {
                // No status assigned = new
                $status_counts['new']++;
            } else {
                $status_slug = $terms[0]->slug;
                if (isset($status_counts[$status_slug])) {
                    $status_counts[$status_slug]++;
                } elseif ($status_slug === 'awaiting-information') {
                    $status_counts['awaiting-information']++;
                } else {
                    // Handle any other statuses
                    $status_counts['new']++;
                }
            }
        }
        
        return array(
            'total' => $total->publish,
            'new' => $status_counts['new'] + $status_counts['awaiting-information'], // Combine new and awaiting info
            'reviewed' => $status_counts['reviewed'],
            'shortlisted' => $status_counts['shortlisted'],
            'selected' => $status_counts['selected'],
            'rejected' => $status_counts['rejected']
        );
    }
    
    /**
     * Get recent submissions count for dashboard display.
     * Returns 24-hour submission activity count.
     */
    private function get_recent_submissions_count() {
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $recent_posts = get_posts(array(
            'post_type' => 'cf7_submission',
            'date_query' => array(
                array(
                    'after' => $yesterday,
                    'inclusive' => true
                )
            ),
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        return count($recent_posts);
    }
}