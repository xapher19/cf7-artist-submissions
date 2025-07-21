<?php
/**
 * Admin Interface for CF7 Artist Submissions
 * 
 * This class handles the admin interface components including meta boxes,
 * AJAX status updates, field saving, and asset management coordination
 * with other system components.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.0.0
 * @since 2.0.0 Enhanced with tabbed interface coordination
 */

/**
 * CF7 Artist Submissions Admin Class
 * 
 * Manages the WordPress admin interface for artist submissions:
 * - Meta boxes for submission details, files, and notes
 * - AJAX handlers for status updates
 * - Field saving and validation
 * - Asset management coordination with tabs system
 * - Status change logging and notifications
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Admin {
    
    /**
     * Initialize the admin interface.
     * 
     * Sets up hooks for asset management, meta boxes, AJAX handlers,
     * and post saving functionality.
     * 
     * @since 1.0.0
     * @since 2.0.0 Enhanced asset coordination with tabs system
     * 
     * @return void
     */
    public function init() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('wp_ajax_update_submission_status', array($this, 'ajax_update_status'));
        
        // Register the save_notes hook during initialization
        add_action('save_post_cf7_submission', array($this, 'save_notes'), 10, 2);
        
        // Register the save_fields hook for editable fields
        add_action('save_post_cf7_submission', array($this, 'save_fields'), 10, 2);
    }
    
    public function enqueue_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        
        // For single submission edit page, let the Tabs system handle all assets
        // For submission list pages, the Post Type class handles admin.css
        // Dashboard assets are handled by the Dashboard class
        // This prevents conflicts and ensures consistent loading
    }
    
    public function ajax_update_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_artist_submissions_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check required data
        if (!isset($_POST['post_id']) || !isset($_POST['status_id'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }
        
        $post_id = intval($_POST['post_id']);
        $status_id = intval($_POST['status_id']);
        
        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Get the old status
        $old_status_terms = wp_get_object_terms($post_id, 'submission_status');
        $old_status = !empty($old_status_terms) ? $old_status_terms[0]->name : 'None';
        
        // Update the status
        $result = wp_set_object_terms($post_id, $status_id, 'submission_status');
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Get the status term for response
        $term = get_term($status_id, 'submission_status');
        
        // Log the status change
        do_action('cf7_artist_submission_status_changed', $post_id, $term->name, $old_status);
        
        wp_send_json_success(array(
            'status_name' => $term->name,
            'status_slug' => sanitize_title($term->name)
        ));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'cf7_submission_details',
            __('Submission Details', 'cf7-artist-submissions'),
            array($this, 'render_details_meta_box'),
            'cf7_submission',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cf7_submission_files',
            __('Submitted Works', 'cf7-artist-submissions'),
            array($this, 'render_files_meta_box'),
            'cf7_submission',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cf7_submission_notes',
            __('Curator Notes', 'cf7-artist-submissions'),
            array($this, 'render_notes_meta_box'),
            'cf7_submission',
            'side',
            'default'
        );
    }
    
    public function render_details_meta_box($post) {
        // Add nonce field for editable fields
        wp_nonce_field('cf7_artist_submissions_fields_nonce', 'cf7_artist_submissions_fields_nonce');
        
        $meta_keys = get_post_custom_keys($post->ID);
        if (empty($meta_keys)) {
            echo '<p>' . __('No submission data found.', 'cf7-artist-submissions') . '</p>';
            return;
        }
        
        echo '<p class="field-edit-hint">' . __('Click on any value to edit it. Press Enter to save, or Escape to cancel.', 'cf7-artist-submissions') . '</p>';
        
        echo '<table class="form-table submission-details">';
        
        foreach ($meta_keys as $key) {
            // Skip internal meta, file fields, and any fields related to 'works' or 'files'
            if (substr($key, 0, 1) === '_' || 
                substr($key, 0, 8) === 'cf7_file_' || 
                $key === 'cf7_submission_date' || 
                $key === 'cf7_curator_notes' ||
                $key === 'cf7_your-work-raw' ||
                strpos($key, 'work') !== false ||
                strpos($key, 'files') !== false) {
                continue;
            }
            
            $value = get_post_meta($post->ID, $key, true);
            if (empty($value) || is_array($value)) {
                continue;
            }
            
            // Format label from meta key
            $label = ucwords(str_replace(array('cf7_', '_', '-'), ' ', $key));
            
            echo '<tr>';
            echo '<th scope="row"><strong>' . esc_html($label) . '</strong></th>';
            
            // Determine field type based on key name or content
            $field_type = 'text';
            if (strpos($key, 'email') !== false) {
                $field_type = 'email';
            } 
            elseif (strpos($key, 'website') !== false || 
                   strpos($key, 'portfolio') !== false || 
                   strpos($key, 'url') !== false || 
                   strpos($key, 'link') !== false) {
                $field_type = 'url';
            }
            elseif (strpos($key, 'statement') !== false || strlen($value) > 100) {
                $field_type = 'textarea';
            }
            
            // Add data attributes for JavaScript
            echo '<td class="editable-field" 
                      data-key="' . esc_attr($key) . '" 
                      data-type="' . esc_attr($field_type) . '" 
                      data-original="' . esc_attr($value) . '">';
            
            // Make URLs clickable - check if value looks like a URL
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                echo '<a href="' . esc_url($value) . '" target="_blank" class="field-value">' . esc_html($value) . '</a>';
            }
            // Check for fields that typically contain URLs
            elseif (strpos($key, 'website') !== false || 
                   strpos($key, 'portfolio') !== false || 
                   strpos($key, 'url') !== false || 
                   strpos($key, 'link') !== false) {
                
                // If it doesn't have http://, add it
                $url = (strpos($value, 'http') === 0) ? $value : 'http://' . $value;
                echo '<a href="' . esc_url($url) . '" target="_blank" class="field-value">' . esc_html($value) . '</a>';
            } 
            else {
                echo '<span class="field-value">' . esc_html($value) . '</span>';
            }
            
            // Add hidden input that will be used when editing
            echo '<input type="hidden" name="cf7_editable_fields[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="field-input" />';
            
            echo '</td>';
            echo '</tr>';
        }
        
        // Add submission date at the end (read-only)
        $date = get_post_meta($post->ID, 'cf7_submission_date', true);
        if (!empty($date)) {
            echo '<tr>';
            echo '<th scope="row"><strong>' . __('Submission Date', 'cf7-artist-submissions') . '</strong></th>';
            echo '<td class="read-only">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date))) . ' <em>(not editable)</em></td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Save the edited fields when the post is updated
     */
    public function save_fields($post_id, $post) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is not a CF7 submission post type
        if (get_post_type($post_id) !== 'cf7_submission') {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['cf7_artist_submissions_fields_nonce']) || !wp_verify_nonce($_POST['cf7_artist_submissions_fields_nonce'], 'cf7_artist_submissions_fields_nonce')) {
            return;
        }
        
        // Check if user has permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if we have fields to save
        if (!isset($_POST['cf7_editable_fields']) || !is_array($_POST['cf7_editable_fields'])) {
            return;
        }
        
        // Save each field
        foreach ($_POST['cf7_editable_fields'] as $meta_key => $value) {
            // Skip empty values or non-cf7 meta keys for safety
            if (empty($value) || strpos($meta_key, 'cf7_') !== 0) {
                continue;
            }
            
            // Get the old value
            $old_value = get_post_meta($post_id, $meta_key, true);
            
            // Only proceed if the value has changed
            if ($old_value !== $value) {
                // Sanitize based on field type
                if (strpos($meta_key, 'email') !== false) {
                    $value = sanitize_email($value);
                } 
                elseif (strpos($meta_key, 'website') !== false || 
                       strpos($meta_key, 'portfolio') !== false || 
                       strpos($meta_key, 'url') !== false || 
                       strpos($meta_key, 'link') !== false) {
                    $value = esc_url_raw($value);
                }
                elseif (strpos($meta_key, 'statement') !== false) {
                    $value = sanitize_textarea_field($value);
                }
                else {
                    $value = sanitize_text_field($value);
                }
                
                // Update the post meta
                update_post_meta($post_id, $meta_key, $value);
                
                // If the field is artist-name, also update the post title
                if ($meta_key === 'cf7_artist-name') {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_title' => $value
                    ));
                }
                
                // Log the field update
                do_action('cf7_artist_submission_field_updated', $post_id, $meta_key, $old_value, $value);
            }
        }
    }
    
    public function render_files_meta_box($post) {
        // Start with an empty array of file URLs to display
        $file_urls = array();
        
        // APPROACH 1: Check for standard file format (cf7_file_your-work)
        $standard_files = get_post_meta($post->ID, 'cf7_file_your-work', true);
        if (!empty($standard_files)) {
            if (is_array($standard_files)) {
                $file_urls = $standard_files;
            } else {
                $file_urls = array($standard_files);
            }
        }
        
        // APPROACH 2: If no files found, check for comma-separated URLs in cf7_your-work
        if (empty($file_urls)) {
            $comma_separated_urls = get_post_meta($post->ID, 'cf7_your-work', true);
            if (!empty($comma_separated_urls)) {
                // Split by commas
                $file_urls = array_map('trim', explode(',', $comma_separated_urls));
            }
        }
        
        // APPROACH 3: Check for any other file fields
        if (empty($file_urls)) {
            $meta_keys = get_post_custom_keys($post->ID);
            if (!empty($meta_keys)) {
                foreach ($meta_keys as $key) {
                    if (substr($key, 0, 8) === 'cf7_file_') {
                        $file_data = get_post_meta($post->ID, $key, true);
                        
                        // Handle both string and array values
                        if (is_array($file_data)) {
                            $file_urls = array_merge($file_urls, $file_data);
                        } else {
                            $file_urls[] = $file_data;
                        }
                    }
                }
            }
        }
        
        // Display message if no files found
        if (empty($file_urls)) {
            echo '<p>' . __('No files submitted.', 'cf7-artist-submissions') . '</p>';
            return;
        }
        
        echo '<p><em>' . __('Files cannot be edited but are displayed for reference.', 'cf7-artist-submissions') . '</em></p>';
        
        // Display the files
        echo '<div class="submission-files">';
        foreach ($file_urls as $url) {
            // Skip if URL is empty or invalid
            if (empty($url)) {
                continue;
            }
            
            $filename = basename($url);
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            echo '<div class="file-item">';
            
            // Display preview for images
            $img_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            if (in_array(strtolower($file_ext), $img_extensions)) {
                echo '<div class="file-preview">';
                echo '<a href="' . esc_url($url) . '" class="lightbox-preview" target="_blank">';
                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($filename) . '" />';
                echo '</a>';
                echo '</div>';
            } else {
                echo '<div class="file-download">';
                echo '<a href="' . esc_url($url) . '" target="_blank">';
                echo '<span class="dashicons dashicons-media-document"></span> ';
                echo esc_html($filename);
                echo '</a>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
    }
    
    public function render_notes_meta_box($post) {
        wp_nonce_field('cf7_artist_submissions_notes_nonce', 'cf7_artist_submissions_notes_nonce');
        
        $notes = get_post_meta($post->ID, 'cf7_curator_notes', true);
        
        echo '<textarea name="cf7_curator_notes" id="cf7_curator_notes" style="width:100%; min-height:150px;">' . esc_textarea($notes) . '</textarea>';
        echo '<p class="description">' . __('Add private curator notes about this submission.', 'cf7-artist-submissions') . '</p>';
    }
    
    public function save_notes($post_id, $post) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is not a CF7 submission post type
        if (get_post_type($post_id) !== 'cf7_submission') {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['cf7_artist_submissions_notes_nonce']) || !wp_verify_nonce($_POST['cf7_artist_submissions_notes_nonce'], 'cf7_artist_submissions_notes_nonce')) {
            return;
        }
        
        // Check if user has permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save notes
        if (isset($_POST['cf7_curator_notes'])) {
            $old_notes = get_post_meta($post_id, 'cf7_curator_notes', true);
            $new_notes = sanitize_textarea_field($_POST['cf7_curator_notes']);
            
            // Only update if changed
            if ($old_notes !== $new_notes) {
                update_post_meta($post_id, 'cf7_curator_notes', $new_notes);
                
                // Log the notes update
                do_action('cf7_artist_submission_field_updated', $post_id, 'cf7_curator_notes', $old_notes, $new_notes);
            }
        }
    }
}