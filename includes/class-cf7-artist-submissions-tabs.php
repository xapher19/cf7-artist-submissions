<?php
/**
 * CF7 Artist Submissions - Tabbed Admin Interface
 */
class CF7_Artist_Submissions_Tabs {
    
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_tab_assets'));
        add_action('add_meta_boxes', array(__CLASS__, 'replace_meta_boxes_with_tabs'), 20);
        add_action('wp_ajax_cf7_load_tab_content', array(__CLASS__, 'ajax_load_tab_content'));
        add_action('wp_ajax_cf7_update_status', array(__CLASS__, 'ajax_update_status'));
        add_action('wp_ajax_cf7_save_submission_data', array(__CLASS__, 'ajax_save_submission_data'));
        
        // Override the post edit page layout
        add_action('edit_form_after_title', array(__CLASS__, 'render_custom_page_layout'));
        add_action('admin_head', array(__CLASS__, 'hide_default_elements'));
        
        // Handle saving for fields and notes
        add_action('save_post_cf7_submission', array(__CLASS__, 'save_fields'), 10, 2);
        add_action('save_post_cf7_submission', array(__CLASS__, 'save_notes'), 10, 2);
    }
    
    public static function enqueue_tab_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        
        // Only on single submission edit page
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('cf7-artist-submissions-tabs', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/tabs.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-lightbox', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/lightbox.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-conversations', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/conversations.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-actions', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/actions.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/admin.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            
            wp_enqueue_script('cf7-artist-submissions-tabs', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/tabs.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-lightbox', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/lightbox.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-fields', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/fields.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-actions', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/actions.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-conversation', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/conversation.js', array('jquery', 'cf7-artist-submissions-actions'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            
            // Add AJAX configuration for tabs
            wp_localize_script('cf7-artist-submissions-tabs', 'cf7TabsAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_tabs_nonce')
            ));
            
            // Add Actions AJAX data globally (for cross-tab functionality)
            wp_localize_script('cf7-artist-submissions-tabs', 'cf7_actions_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_actions_nonce')
            ));
            
            // Add Admin AJAX data (for admin.js compatibility)
            wp_localize_script('cf7-artist-submissions-admin', 'cf7ArtistSubmissions', array(
                'nonce' => wp_create_nonce('cf7_artist_submissions_nonce')
            ));
            
            // Add Conversations AJAX data (for conversation.js)
            wp_localize_script('cf7-artist-submissions-conversation', 'cf7Conversations', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_conversation_nonce'),
                'strings' => array(
                    'sending' => __('Sending...', 'cf7-artist-submissions'),
                    'sent' => __('Message sent!', 'cf7-artist-submissions'),
                    'error' => __('Error sending message', 'cf7-artist-submissions'),
                    'required' => __('Please fill in all fields', 'cf7-artist-submissions')
                )
            ));
        }
    }
    
    public static function replace_meta_boxes_with_tabs() {
        // Remove existing meta boxes
        remove_meta_box('cf7_submission_details', 'cf7_submission', 'normal');
        remove_meta_box('cf7_submission_files', 'cf7_submission', 'normal');
        remove_meta_box('cf7-conversation', 'cf7_submission', 'normal');
        remove_meta_box('cf7_submission_notes', 'cf7_submission', 'side');
        
        // Add tabbed interface
        add_meta_box(
            'cf7_submission_tabs',
            __('Artist Submission', 'cf7-artist-submissions'),
            array(__CLASS__, 'render_tabbed_interface'),
            'cf7_submission',
            'normal',
            'high'
        );
    }
    
    public static function render_tabbed_interface($post) {
        ?>
        <div class="cf7-tabs-container">
            <nav class="cf7-tabs-nav">
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-profile">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Profile', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-works">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <?php _e('Submitted Works', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-conversations">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Conversations', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-actions">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Actions', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-notes">
                        <span class="dashicons dashicons-edit-large"></span>
                        <?php _e('Curator Notes', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </nav>
            
            <div class="cf7-tabs-content">
                <div id="cf7-tab-profile" class="cf7-tab-content">
                    <?php self::render_profile_tab($post); ?>
                </div>
                
                <div id="cf7-tab-works" class="cf7-tab-content">
                    <?php self::render_works_tab($post); ?>
                </div>
                
                <div id="cf7-tab-conversations" class="cf7-tab-content">
                    <?php self::render_conversations_tab($post); ?>
                </div>
                
                <div id="cf7-tab-actions" class="cf7-tab-content">
                    <?php self::render_actions_tab($post); ?>
                </div>
                
                <div id="cf7-tab-notes" class="cf7-tab-content">
                    <?php self::render_notes_tab($post); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public static function render_profile_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Submission Details', 'cf7-artist-submissions'); ?></h3>
            <?php 
            // Render the details content (copied from original meta box)
            self::render_submission_details($post);
            ?>
        </div>
        <?php
    }
    
    public static function render_works_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Submitted Works', 'cf7-artist-submissions'); ?></h3>
            <?php 
            // Render the files content (copied from original meta box)
            self::render_submitted_files($post);
            ?>
        </div>
        <?php
    }
    
    public static function render_conversations_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Artist Conversation', 'cf7-artist-submissions'); ?></h3>
            <?php 
            // Render the conversation content
            if (class_exists('CF7_Artist_Submissions_Conversations')) {
                CF7_Artist_Submissions_Conversations::render_conversation_meta_box($post);
            } else {
                echo '<p>' . __('Conversation system not available.', 'cf7-artist-submissions') . '</p>';
            }
            ?>
        </div>
        <?php
    }
    
    public static function render_actions_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <?php 
            // Render the actions content
            if (class_exists('CF7_Artist_Submissions_Actions')) {
                CF7_Artist_Submissions_Actions::render_actions_tab($post);
            } else {
                echo '<p>' . __('Actions system not available.', 'cf7-artist-submissions') . '</p>';
            }
            ?>
        </div>
        <?php
    }

    public static function render_notes_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Curator Notes', 'cf7-artist-submissions'); ?></h3>
            <?php 
            // Render the notes content
            self::render_curator_notes($post);
            ?>
        </div>
        <?php
    }
    
    /**
     * Render submission details (copied from admin class)
     */
    public static function render_submission_details($post) {
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
            
            echo '<tr>';
            echo '<th scope="row"><strong>' . esc_html($label) . '</strong></th>';
            echo '<td class="editable-field" 
                      data-field="' . esc_attr($key) . '" 
                      data-key="' . esc_attr($key) . '" 
                      data-post-id="' . esc_attr($post->ID) . '"
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
        
        echo '</table>';
    }
    
    /**
     * Render submitted files (copied from admin class)
     */
    public static function render_submitted_files($post) {
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
    
    /**
     * Render curator notes (copied from admin class)
     */
    public static function render_curator_notes($post) {
        wp_nonce_field('cf7_artist_submissions_notes_nonce', 'cf7_artist_submissions_notes_nonce');
        $notes = get_post_meta($post->ID, 'cf7_curator_notes', true);
        
        echo '<textarea name="cf7_curator_notes" id="cf7_curator_notes" rows="8" style="width: 100%;" placeholder="' . __('Add your notes about this submission...', 'cf7-artist-submissions') . '">' . esc_textarea($notes) . '</textarea>';
        echo '<p class="description">' . __('Private notes visible only to curators and administrators.', 'cf7-artist-submissions') . '</p>';
    }
    
    /**
     * AJAX handler for loading tab content
     */
    public static function ajax_load_tab_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check required data
        if (!isset($_POST['tab_id']) || !isset($_POST['post_id'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }
        
        $tab_id = sanitize_text_field($_POST['tab_id']);
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'cf7_submission') {
            wp_send_json_error(array('message' => 'Invalid post'));
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        ob_start();
        
        switch ($tab_id) {
            case 'cf7-tab-profile':
                self::render_profile_tab($post);
                break;
            case 'cf7-tab-works':
                self::render_works_tab($post);
                break;
            case 'cf7-tab-conversations':
                self::render_conversations_tab($post);
                break;
            case 'cf7-tab-actions':
                self::render_actions_tab($post);
                break;
            case 'cf7-tab-notes':
                self::render_notes_tab($post);
                break;
            default:
                echo '<p>Invalid tab</p>';
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(array('content' => $content));
    }
    
    /**
     * Save the edited fields when the post is updated
     */
    public static function save_fields($post_id, $post) {
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
    
    /**
     * Save curator notes
     */
    public static function save_notes($post_id, $post) {
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
    
    /**
     * Hide default WordPress post page elements
     */
    public static function hide_default_elements() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        ?>
        <style>
            /* Hide the title input field */
            #titlediv {
                display: none;
            }
            
            /* Hide the publish meta box */
            #submitdiv {
                display: none;
            }
            
            /* Hide the slug editor */
            #edit-slug-box {
                display: none;
            }
            
            /* Make content area full width */
            #poststuff {
                margin-right: 0 !important;
            }
            
            #post-body.columns-2 #postbox-container-1 {
                display: none;
            }
            
            #post-body.columns-2 #postbox-container-2 {
                margin-right: 0;
                width: 100%;
            }
            
            /* Remove postbox styling from our container */
            #cf7_submission_tabs.postbox {
                border: none;
                box-shadow: none;
                background: transparent;
            }
            
            #cf7_submission_tabs .postbox-header {
                display: none;
            }
            
            #cf7_submission_tabs .inside {
                margin: 0;
                padding: 0;
            }
        </style>
        <?php
    }
    
    /**
     * Render custom page layout after title
     */
    public static function render_custom_page_layout($post) {
        if (!$post || $post->post_type !== 'cf7_submission') {
            return;
        }
        
        // Get artist name from submission data
        $artist_name = get_post_meta($post->ID, 'cf7_artist-name', true);
        if (empty($artist_name)) {
            $artist_name = get_post_meta($post->ID, 'cf7_your-name', true);
        }
        if (empty($artist_name)) {
            $artist_name = $post->post_title;
        }
        
        // Get current status
        $status_terms = wp_get_object_terms($post->ID, 'submission_status');
        $current_status = !empty($status_terms) ? $status_terms[0]->slug : 'new';
        
        ?>
        <div class="cf7-custom-header">
            <div class="cf7-header-left">
                <h1 class="cf7-artist-title"><?php echo esc_html($artist_name); ?></h1>
                <div class="cf7-status-selector">
                    <?php echo self::render_status_circle($current_status, $post->ID); ?>
                </div>
            </div>
            <div class="cf7-header-right">
                <button type="submit" class="cf7-save-button" form="post">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Changes', 'cf7-artist-submissions'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render status circle dropdown
     */
    public static function render_status_circle($current_status, $post_id) {
        $statuses = array(
            'new' => array(
                'label' => __('New', 'cf7-artist-submissions'),
                'color' => '#007cba',
                'icon' => 'star-filled'
            ),
            'reviewed' => array(
                'label' => __('Reviewed', 'cf7-artist-submissions'),
                'color' => '#7c3aed',
                'icon' => 'visibility'
            ),
            'awaiting-information' => array(
                'label' => __('Awaiting Information', 'cf7-artist-submissions'),
                'color' => '#f59e0b',
                'icon' => 'clock'
            ),
            'selected' => array(
                'label' => __('Selected', 'cf7-artist-submissions'),
                'color' => '#10b981',
                'icon' => 'yes-alt'
            ),
            'rejected' => array(
                'label' => __('Rejected', 'cf7-artist-submissions'),
                'color' => '#ef4444',
                'icon' => 'dismiss'
            )
        );
        
        $current = isset($statuses[$current_status]) ? $statuses[$current_status] : $statuses['new'];
        
        ob_start();
        ?>
        <div class="cf7-status-dropdown" data-post-id="<?php echo esc_attr($post_id); ?>">
            <button type="button" class="cf7-status-circle" 
                    style="background-color: <?php echo esc_attr($current['color']); ?>"
                    title="<?php echo esc_attr($current['label']); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($current['icon']); ?>"></span>
            </button>
            <div class="cf7-status-menu">
                <?php foreach ($statuses as $status_key => $status): ?>
                    <button type="button" 
                            class="cf7-status-option <?php echo $status_key === $current_status ? 'active' : ''; ?>"
                            data-status="<?php echo esc_attr($status_key); ?>"
                            style="border-left-color: <?php echo esc_attr($status['color']); ?>">
                        <span class="cf7-status-icon dashicons dashicons-<?php echo esc_attr($status['icon']); ?>" 
                              style="color: <?php echo esc_attr($status['color']); ?>"></span>
                        <span class="cf7-status-label"><?php echo esc_html($status['label']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for status updates
     */
    public static function ajax_update_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        // Validate status
        $valid_statuses = array('new', 'reviewed', 'awaiting-information', 'selected', 'rejected');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Invalid status'));
            return;
        }
        
        // Update the status
        wp_set_object_terms($post_id, $new_status, 'submission_status');
        
        // Get the new status data for response
        $statuses = array(
            'new' => array('label' => __('New', 'cf7-artist-submissions'), 'color' => '#007cba', 'icon' => 'star-filled'),
            'reviewed' => array('label' => __('Reviewed', 'cf7-artist-submissions'), 'color' => '#7c3aed', 'icon' => 'visibility'),
            'awaiting-information' => array('label' => __('Awaiting Information', 'cf7-artist-submissions'), 'color' => '#f59e0b', 'icon' => 'clock'),
            'selected' => array('label' => __('Selected', 'cf7-artist-submissions'), 'color' => '#10b981', 'icon' => 'yes-alt'),
            'rejected' => array('label' => __('Rejected', 'cf7-artist-submissions'), 'color' => '#ef4444', 'icon' => 'dismiss')
        );
        
        wp_send_json_success(array(
            'status' => $new_status,
            'data' => $statuses[$new_status]
        ));
    }
    
    /**
     * AJAX handler to save submission data
     */
    public static function ajax_save_submission_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $field_data = isset($_POST['field_data']) ? $_POST['field_data'] : array();
        $curator_notes = isset($_POST['curator_notes']) ? sanitize_textarea_field($_POST['curator_notes']) : '';
        
        // Validate post
        if (!$post_id || get_post_type($post_id) !== 'cf7_submission') {
            wp_send_json_error(array('message' => 'Invalid post'));
            return;
        }
        
        // Check edit permissions for this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied for this post'));
            return;
        }
        
        $updated_fields = 0;
        
        // Save field data
        if (is_array($field_data)) {
            foreach ($field_data as $meta_key => $value) {
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
                    $updated_fields++;
                    
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
        
        // Save curator notes
        if (!empty($curator_notes)) {
            $old_notes = get_post_meta($post_id, 'cf7_curator_notes', true);
            if ($old_notes !== $curator_notes) {
                update_post_meta($post_id, 'cf7_curator_notes', $curator_notes);
                $updated_fields++;
                
                // Log the notes update
                do_action('cf7_artist_submission_field_updated', $post_id, 'cf7_curator_notes', $old_notes, $curator_notes);
            }
        }
        
        wp_send_json_success(array(
            'message' => "Successfully updated {$updated_fields} field(s)",
            'updated_fields' => $updated_fields
        ));
    }
}
