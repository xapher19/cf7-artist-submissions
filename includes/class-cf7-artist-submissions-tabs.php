<?php
/**
 * CF7 Artist Submissions - Professional Tabbed Admin Interface System
 *
 * Complete transformation engine converting standard WordPress edit pages into
 * modern, professional tabbed interfaces with specialized content management,
 * AJAX-powered navigation, integrated field editing capabilities, and
 * comprehensive asset coordination for streamlined submission administration.
 *
 * Features:
 * • Professional 6-tab layout with Profile, Works, Conversations, Actions, Notes, and PDF Export
 * • Editable header system with real-time artist information updates and status management
 * • Advanced AJAX tab loading with smart navigation and URL hash support integration
 * • Comprehensive field editing system with validation and live preview capabilities
 * • Integrated lightbox image viewing with professional gallery presentation
 * • Seamless conversation management interface with real-time messaging integration
 * • Advanced action and task management interface with priority tracking
 * • Independent curator notes system with auto-save and audit trail logging
 * • Complete WordPress admin integration with custom styling and asset management
 * • Professional PDF export functionality with customizable options and watermarking
 *
 * @package CF7_Artist_Submissions
 * @subpackage TabsInterface
 * @since 1.0.0
 * @version 1.1.0
 */

/**
 * CF7 Artist Submissions Tabs Class
 * 
 * Comprehensive tabbed interface management system providing complete transformation
 * of WordPress admin edit pages into modern, professional submission interfaces.
 * Integrates advanced AJAX navigation, real-time field editing, conversation
 * management, action tracking, and seamless asset coordination for optimal
 * administrative workflow and user experience enhancement.
 * 
 * @since 1.0.0
 */
class CF7_Artist_Submissions_Tabs {
    
    /**
     * Initialize comprehensive tabbed interface system with admin integration.
     * 
     * Establishes complete tabbed interface infrastructure including admin menu
     * modifications, comprehensive asset management with dependency coordination,
     * extensive AJAX handler registration for all tab functionality, custom page
     * layout overrides, and integrated saving mechanisms for fields and notes.
     * Provides foundation for modern submission management interface.
     * 
     * @since 1.0.0
     */
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_tab_assets'));
        add_action('add_meta_boxes', array(__CLASS__, 'replace_meta_boxes_with_tabs'), 20);
        add_action('wp_ajax_cf7_load_tab_content', array(__CLASS__, 'ajax_load_tab_content'));
        add_action('wp_ajax_cf7_update_status', array(__CLASS__, 'ajax_update_status'));
        add_action('wp_ajax_cf7_save_submission_data', array(__CLASS__, 'ajax_save_submission_data'));
        add_action('wp_ajax_cf7_save_curator_notes', array(__CLASS__, 'ajax_save_curator_notes'));
        add_action('wp_ajax_cf7_save_artistic_mediums', array(__CLASS__, 'ajax_save_artistic_mediums'));
        
        // S3 and file handling AJAX actions
        add_action('wp_ajax_cf7as_download_zip', array(__CLASS__, 'ajax_download_zip'));
        
        // Override the post edit page layout
        add_action('edit_form_after_title', array(__CLASS__, 'render_custom_page_layout'));
        add_action('admin_head', array(__CLASS__, 'hide_default_elements'));
        
        // Handle saving for fields and notes
        add_action('save_post_cf7_submission', array(__CLASS__, 'save_fields'), 10, 2);
        add_action('save_post_cf7_submission', array(__CLASS__, 'save_notes'), 10, 2);
    }
    
    /**
     * Enqueue comprehensive tabbed interface assets with dependency management.
     * 
     * Loads all required CSS and JavaScript files in proper dependency order for
     * optimal performance and functionality. Includes specialized styles for tabs,
     * conversations, actions, lightbox viewing, and admin interface components.
     * Provides complete AJAX configuration with nonce security and localized
     * strings for seamless frontend-backend integration across all components.
     * 
     * @since 1.0.0
     */
    public static function enqueue_tab_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        
        // Only on single submission edit page
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            // Enqueue common styles first (foundation for all other styles)
            wp_enqueue_style('cf7-common-css', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/common.css', array(), CF7_ARTIST_SUBMISSIONS_VERSION);
            
            // Enqueue specialized styles in logical order (all depend on common.css)
            wp_enqueue_style('cf7-artist-submissions-tabs', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/tabs.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-conversations', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/conversations.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-actions', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/actions.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-lightbox', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/lightbox.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            wp_enqueue_style('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/css/admin.css', array('cf7-common-css'), CF7_ARTIST_SUBMISSIONS_VERSION);
            
            wp_enqueue_script('cf7-artist-submissions-tabs', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/tabs.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-lightbox', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/lightbox.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-fields', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/fields.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-actions', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/actions.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-conversation', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/conversation.js', array('jquery', 'cf7-artist-submissions-actions'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            wp_enqueue_script('cf7-artist-submissions-admin', CF7_ARTIST_SUBMISSIONS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CF7_ARTIST_SUBMISSIONS_VERSION, true);
            
            // Add AJAX configuration for tabs
            wp_localize_script('cf7-artist-submissions-tabs', 'cf7TabsAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_tabs_nonce'),
                'post_id' => get_the_ID()
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
            
            // Add Fields AJAX data (for fields.js)
            wp_localize_script('cf7-artist-submissions-fields', 'cf7FieldsAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_tabs_nonce'),
                'post_id' => get_the_ID(),
                'strings' => array(
                    'saving' => __('Saving...', 'cf7-artist-submissions'),
                    'saved' => __('Changes saved!', 'cf7-artist-submissions'),
                    'error' => __('Error saving changes', 'cf7-artist-submissions')
                )
            ));
        }
    }
    
    /**
     * Replace default WordPress meta boxes with integrated tabbed interface.
     * Removes standard submission meta boxes and integrates unified tabs system.
     */
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
    
    /**
     * Render comprehensive tabbed interface with editable artist header.
     * 
     * Generates complete tabbed administration interface including professional
     * artist profile header with editable fields and status management, six
     * specialized content tabs for submission administration, integrated AJAX
     * navigation system, and seamless WordPress admin integration. Provides
     * modern, intuitive interface for efficient submission workflow management.
     * 
     * @since 1.0.0
     */
    public static function render_tabbed_interface($post) {
        // Get artist information for the header
        $artist_name = get_post_meta($post->ID, 'cf7_artist-name', true);
        if (empty($artist_name)) {
            $artist_name = get_post_meta($post->ID, 'cf7_your-name', true);
        }
        if (empty($artist_name)) {
            $artist_name = $post->post_title;
        }
        
        // Get pronouns
        $pronouns = get_post_meta($post->ID, 'cf7_pronouns', true);
        if (empty($pronouns)) {
            $pronouns = get_post_meta($post->ID, 'cf7_your-pronouns', true);
        }
        
        // Get email
        $email = get_post_meta($post->ID, 'cf7_email', true);
        if (empty($email)) {
            $email = get_post_meta($post->ID, 'cf7_your-email', true);
        }
        
        // Get current status
        $status_terms = wp_get_object_terms($post->ID, 'submission_status');
        $current_status = !empty($status_terms) ? $status_terms[0]->slug : 'new';
        
        // Generate initials for avatar
        $initials = '';
        if ($artist_name) {
            $names = explode(' ', $artist_name);
            $initials = strtoupper(substr($names[0], 0, 1));
            if (count($names) > 1) {
                $initials .= strtoupper(substr(end($names), 0, 1));
            }
        } else {
            $initials = 'A';
        }
        ?>
        
        <!-- Artist Profile Header -->
        <div class="cf7-gradient-header cf7-header-context cf7-artist-header">
            <div class="cf7-artist-header-content">
                <div class="cf7-status-selector cf7-artist-status">
                    <?php echo self::render_status_circle($current_status, $post->ID); ?>
                </div>
                <div class="cf7-artist-info">
                    <h1 class="cf7-artist-name">
                        <span class="cf7-header-field" data-field="artist-name" data-original="<?php echo esc_attr($artist_name ?: __('Unknown Artist', 'cf7-artist-submissions')); ?>">
                            <span class="field-value"><?php echo esc_html($artist_name ?: __('Unknown Artist', 'cf7-artist-submissions')); ?></span>
                        </span>
                        <?php if (!empty($pronouns)): ?>
                            <span class="cf7-artist-pronouns">
                                (<span class="cf7-header-field" data-field="pronouns" data-original="<?php echo esc_attr($pronouns); ?>">
                                    <span class="field-value"><?php echo esc_html($pronouns); ?></span>
                                </span>)
                            </span>
                        <?php else: ?>
                            <span class="cf7-artist-pronouns" style="display: none;">
                                (<span class="cf7-header-field" data-field="pronouns" data-original="">
                                    <span class="field-value"></span>
                                </span>)
                            </span>
                        <?php endif; ?>
                    </h1>
                    <p class="cf7-artist-subtitle">
                        <span class="cf7-header-field" data-field="email" data-original="<?php echo esc_attr($email); ?>">
                            <span class="field-value"><?php echo esc_html($email ?: __('No email provided', 'cf7-artist-submissions')); ?></span>
                        </span>
                    </p>
                </div>
            </div>
            <div class="cf7-profile-actions">
                <button type="button" class="cf7-edit-save-button cf7-btn cf7-btn-primary" data-edit-text="<?php esc_attr_e('Edit Profile', 'cf7-artist-submissions'); ?>" data-save-text="<?php esc_attr_e('Save Changes', 'cf7-artist-submissions'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Edit Profile', 'cf7-artist-submissions'); ?>
                </button>
            </div>
        </div>
        
        <!-- Tabbed Interface -->
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
                <div class="cf7-tab-nav-item">
                    <button type="button" class="cf7-tab-link" data-tab="cf7-tab-export">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php _e('Export PDF', 'cf7-artist-submissions'); ?>
                    </button>
                </div>
            </nav>
            
            <div class="cf7-tabs-content">
                <div id="cf7-tab-profile" class="cf7-tab-content">
                    <?php self::render_profile_tab($post); ?>
                </div>
                
                <div id="cf7-tab-works" class="cf7-tab-content">
                    <!-- Content loaded via AJAX -->
                </div>
                
                <div id="cf7-tab-conversations" class="cf7-tab-content">
                    <!-- Content loaded via AJAX -->
                </div>
                
                <div id="cf7-tab-actions" class="cf7-tab-content">
                    <!-- Content loaded via AJAX -->
                </div>
                
                <div id="cf7-tab-notes" class="cf7-tab-content">
                    <!-- Content loaded via AJAX -->
                </div>
                
                <div id="cf7-tab-export" class="cf7-tab-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render artist profile tab with comprehensive submission details.
     * Displays organized profile information with editable field system.
     */
    public static function render_profile_tab($post) {
        ?>
        <div class="cf7-profile-tab-container">
            <div class="cf7-profile-content">
                <?php self::render_submission_details($post); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render submitted works tab with lightbox gallery integration.
     * Displays artist submissions with professional image preview capabilities.
     */
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
    
    /**
     * Render conversation management tab with integrated messaging system.
     * Delegates to conversation class for comprehensive email communication interface.
     */
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

    /**
     * Render action management tab with task tracking interface.
     * Delegates to actions class for comprehensive task and priority management.
     */
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

    /**
     * Render curator notes tab with independent saving system.
     * Provides private note-taking interface with auto-save functionality.
     */
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
     * Render PDF export tab with customizable export options.
     * Provides professional PDF generation with watermarking and content selection.
     */
    public static function render_export_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Export Options', 'cf7-artist-submissions'); ?></h3>
            <div class="cf7-export-options">
                <div class="cf7-export-section">
                    <p class="description"><?php _e('Generate a beautifully formatted PDF with artist information and submitted works.', 'cf7-artist-submissions'); ?></p>
                    
                    <div class="cf7-export-options-list">
                        <label>
                            <input type="checkbox" name="include_personal_info" checked> 
                            <?php _e('Include Personal Information', 'cf7-artist-submissions'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="include_works" checked> 
                            <?php _e('Include Submitted Works', 'cf7-artist-submissions'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="include_notes"> 
                            <?php _e('Include Curator Notes', 'cf7-artist-submissions'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="confidential_watermark" checked> 
                            <?php _e('Add "Private & Confidential" Watermark', 'cf7-artist-submissions'); ?>
                        </label>
                    </div>
                    
                    <div class="cf7-export-actions">
                        <button type="button" class="button button-primary cf7-export-pdf-btn" data-post-id="<?php echo esc_attr($post->ID); ?>">
                            <span class="dashicons dashicons-pdf"></span>
                            <?php _e('Export to PDF', 'cf7-artist-submissions'); ?>
                        </button>
                        <div class="cf7-export-status"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .cf7-export-options {
            padding: 0;
        }
        
        .cf7-export-options-list {
            margin: 12px 0;
        }
        
        .cf7-export-options-list label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .cf7-export-options-list input[type="checkbox"] {
            margin-right: 6px;
        }
        
        .cf7-export-actions {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #dcdcde;
        }
        
        .cf7-export-pdf-btn {
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .cf7-export-pdf-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cf7-export-status {
            margin-top: 8px;
            padding: 6px 0;
            font-size: 12px;
        }
        
        .cf7-export-status.success {
            color: #00a32a;
        }
        
        .cf7-export-status.error {
            color: #d63638;
        }
        
        .cf7-export-status.loading {
            color: #2271b1;
        }
        </style>
        <?php
    }
    
    // ============================================================================
    // CONTENT RENDERING SECTION
    // ============================================================================
    
    /**
     * Render comprehensive submission details with categorized field organization.
     * 
     * Processes and displays all submission metadata in organized sections including
     * profile information, contact details, and additional data. Implements advanced
     * field categorization, editable field system integration, artistic mediums
     * management, and professional layout presentation for optimal administrative
     * workflow and data accessibility across all submission types.
     * 
     * @since 1.0.0
     */
    public static function render_submission_details($post) {
        // Add nonce field for editable fields
        wp_nonce_field('cf7_artist_submissions_fields_nonce', 'cf7_artist_submissions_fields_nonce');
        
        $meta_keys = get_post_custom_keys($post->ID);
        if (empty($meta_keys)) {
            echo '<div class="cf7-profile-empty">';
            echo '<div class="cf7-empty-icon"><span class="dashicons dashicons-admin-users"></span></div>';
            echo '<h3>' . __('No profile data found', 'cf7-artist-submissions') . '</h3>';
            echo '<p>' . __('This submission does not contain any profile information.', 'cf7-artist-submissions') . '</p>';
            echo '</div>';
            return;
        }
        
        // Group fields by category for better organization
        $contact_fields = array();
        $profile_fields = array();
        $other_fields = array();
        
        foreach ($meta_keys as $key) {
            // Skip internal meta, file fields, header fields, and any fields related to 'works' or 'files'
            if (substr($key, 0, 1) === '_' || 
                substr($key, 0, 8) === 'cf7_file_' || 
                $key === 'cf7_submission_date' || 
                $key === 'cf7_curator_notes' ||
                $key === 'cf7_your-work-raw' ||
                $key === 'cf7_artist-name' || 
                $key === 'cf7_your-name' ||
                $key === 'cf7_pronouns' ||
                $key === 'cf7_your-pronouns' ||
                $key === 'cf7_email' ||
                $key === 'cf7_your-email' ||
                strpos($key, 'work') !== false ||
                strpos($key, 'files') !== false) {
                continue;
            }
            
            $value = get_post_meta($post->ID, $key, true);
            if (empty($value) || is_array($value)) {
                continue;
            }
            
            // Categorize fields
            if (strpos($key, 'phone') !== false) {
                $contact_fields[$key] = $value;
            } elseif (strpos($key, 'statement') !== false || strpos($key, 'bio') !== false) {
                $profile_fields[$key] = $value;
            } else {
                $other_fields[$key] = $value;
            }
        }
        
        // Separate artist statement fields from other profile fields for proper ordering
        $statement_fields = array();
        $non_statement_profile_fields = array();
        
        foreach ($profile_fields as $key => $value) {
            if (strpos($key, 'statement') !== false || strpos($key, 'bio') !== false) {
                $statement_fields[$key] = $value;
            } else {
                $non_statement_profile_fields[$key] = $value;
            }
        }
        
        echo '<div class="cf7-profile-grid">';
        
        // Profile Information Section
        if (!empty($non_statement_profile_fields) || !empty($statement_fields) || true) { // Always show this section now since it includes mediums
            echo '<div class="cf7-profile-section">';
            echo '<h3 class="cf7-section-title">';
            echo '<span class="dashicons dashicons-admin-users"></span>';
            echo __('Profile Information', 'cf7-artist-submissions');
            echo '</h3>';
            echo '<div class="cf7-profile-fields">';
            
            // First render non-statement profile fields
            foreach ($non_statement_profile_fields as $key => $value) {
                self::render_modern_field($post->ID, $key, $value);
            }
            
            // Add artistic mediums field within profile information (before artist statement)
            self::render_artistic_mediums_field($post->ID);
            
            // Then render artist statement fields
            foreach ($statement_fields as $key => $value) {
                self::render_modern_field($post->ID, $key, $value);
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Contact Information Section
        if (!empty($contact_fields)) {
            echo '<div class="cf7-profile-section">';
            echo '<h3 class="cf7-section-title">';
            echo '<span class="dashicons dashicons-email"></span>';
            echo __('Contact Information', 'cf7-artist-submissions');
            echo '</h3>';
            echo '<div class="cf7-profile-fields">';
            
            foreach ($contact_fields as $key => $value) {
                self::render_modern_field($post->ID, $key, $value);
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Additional Information Section
        if (!empty($other_fields)) {
            echo '<div class="cf7-profile-section">';
            echo '<h3 class="cf7-section-title">';
            echo '<span class="dashicons dashicons-info"></span>';
            echo __('Additional Information', 'cf7-artist-submissions');
            echo '</h3>';
            echo '<div class="cf7-profile-fields">';
            
            foreach ($other_fields as $key => $value) {
                self::render_modern_field($post->ID, $key, $value);
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>'; // .cf7-profile-grid
    }
    
    /**
     * Render modern field layout with intelligent type detection and editing.
     * Formats individual submission fields with appropriate icons and interactive editing.
     */
    private static function render_modern_field($post_id, $key, $value) {
        // Format label from meta key
        $label = ucwords(str_replace(array('cf7_', '_', '-'), ' ', $key));
        
        // Determine field type based on key name or content
        $field_type = 'text';
        $field_icon = 'dashicons-text';
        
        if (strpos($key, 'email') !== false) {
            $field_type = 'email';
            $field_icon = 'dashicons-email';
        } 
        elseif (strpos($key, 'website') !== false || 
               strpos($key, 'portfolio') !== false || 
               strpos($key, 'url') !== false || 
               strpos($key, 'link') !== false) {
            $field_type = 'url';
            $field_icon = 'dashicons-admin-links';
        }
        elseif (strpos($key, 'phone') !== false) {
            $field_icon = 'dashicons-phone';
        }
        elseif (strpos($key, 'statement') !== false || strlen($value) > 100) {
            $field_type = 'textarea';
            $field_icon = 'dashicons-editor-paragraph';
        }
        elseif (strpos($key, 'name') !== false) {
            $field_icon = 'dashicons-admin-users';
        }
        
        echo '<div class="cf7-profile-field" data-field="' . esc_attr($key) . '" data-key="' . esc_attr($key) . '" data-post-id="' . esc_attr($post_id) . '" data-type="' . esc_attr($field_type) . '" data-original="' . esc_attr($value) . '">';
        
        echo '<div class="cf7-field-header">';
        echo '<span class="cf7-field-icon dashicons ' . esc_attr($field_icon) . '"></span>';
        echo '<label class="cf7-field-label">' . esc_html($label) . '</label>';
        echo '</div>';
        
        echo '<div class="cf7-field-content">';
        
        // Make URLs clickable - check if value looks like a URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            echo '<a href="' . esc_url($value) . '" target="_blank" class="cf7-field-value cf7-field-link">' . esc_html($value) . '</a>';
        }
        // Check for fields that typically contain URLs
        elseif (strpos($key, 'website') !== false || 
               strpos($key, 'portfolio') !== false || 
               strpos($key, 'url') !== false || 
               strpos($key, 'link') !== false) {
            
            // If it doesn't have http://, add it
            $url = (strpos($value, 'http') === 0) ? $value : 'http://' . $value;
            echo '<a href="' . esc_url($url) . '" target="_blank" class="cf7-field-value cf7-field-link">' . esc_html($value) . '</a>';
        } 
        else {
            if ($field_type === 'textarea') {
                echo '<div class="cf7-field-value cf7-field-textarea">' . nl2br(esc_html($value)) . '</div>';
            } else {
                echo '<span class="cf7-field-value">' . esc_html($value) . '</span>';
            }
        }
        
        // Add hidden input that will be used when editing
        echo '<input type="hidden" name="cf7_editable_fields[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="field-input" />';
        
        echo '</div>'; // .cf7-field-content
        echo '</div>'; // .cf7-profile-field
    }
    
    /**
     * Render artistic mediums field with interactive tag selection system.
     * 
     * Provides comprehensive artistic medium management with visual tag display,
     * interactive editing capabilities, taxonomy integration, and color-coded
     * presentation. Supports real-time updates and seamless integration with
     * WordPress taxonomy system for professional medium categorization and
     * administrative workflow optimization across submission management.
     * 
     * @since 1.0.0
     */
    private static function render_artistic_mediums_field($post_id) {
        // Get current medium terms assigned to this submission
        $current_terms = get_the_terms($post_id, 'artistic_medium');
        $current_medium_ids = array();
        $current_medium_names = array();
        
        if (!empty($current_terms) && !is_wp_error($current_terms)) {
            foreach ($current_terms as $term) {
                $current_medium_ids[] = $term->term_id;
                $current_medium_names[] = $term->name;
            }
        }
        
        // Get all available medium terms
        $all_mediums = get_terms(array(
            'taxonomy' => 'artistic_medium',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($all_mediums) || is_wp_error($all_mediums)) {
            echo '<div class="cf7-no-mediums">';
            echo '<p>' . __('No artistic mediums are available. Please configure the mediums taxonomy.', 'cf7-artist-submissions') . '</p>';
            echo '</div>';
            return;
        }
        
        echo '<div class="cf7-artistic-mediums-field cf7-profile-field" data-post-id="' . esc_attr($post_id) . '" data-field="artistic_mediums" data-type="mediums">';
        
        echo '<div class="cf7-field-header">';
        echo '<span class="cf7-field-icon dashicons dashicons-art"></span>';
        echo '<label class="cf7-field-label">' . __('Artistic Mediums', 'cf7-artist-submissions') . '</label>';
        echo '</div>';
        
        echo '<div class="cf7-field-content">';
        
        // Display mode - shown when not in edit mode
        echo '<div class="cf7-mediums-display">';
        if (!empty($current_medium_names)) {
            // Get the full term objects to access colors
            $current_terms_objects = get_the_terms($post_id, 'artistic_medium');
            foreach ($current_terms_objects as $term) {
                $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                
                if ($bg_color && $text_color) {
                    $style = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                    $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
                } else {
                    $style = '';
                    $data_attrs = ' data-color=""';
                }
                echo '<span class="cf7-medium-tag"' . $data_attrs . $style . '>' . esc_html($term->name) . '</span>';
            }
        } else {
            echo '<span class="cf7-no-mediums-text">' . __('No mediums selected', 'cf7-artist-submissions') . '</span>';
        }
        echo '</div>';
        
        // Edit mode - shown when in edit mode (hidden by CSS initially)
        echo '<div class="cf7-mediums-edit" style="display: none;">';
        foreach ($all_mediums as $term) {
            $checked = in_array($term->term_id, $current_medium_ids) ? 'checked' : '';
            $bg_color = get_term_meta($term->term_id, 'medium_color', true);
            $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
            
            $style_vars = '';
            $data_attrs = '';
            if ($bg_color && $text_color) {
                $style_vars = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
            } else {
                $data_attrs = ' data-color=""';
            }
            
            echo '<label class="cf7-medium-checkbox"' . $data_attrs . $style_vars . '>';
            echo '<input type="checkbox" name="artistic_mediums[]" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
            echo '<span class="checkmark"></span>';
            echo '<span class="medium-name">' . esc_html($term->name) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Hidden input to store current values for the edit system
        echo '<input type="hidden" name="cf7_editable_fields[artistic_mediums]" value="' . esc_attr(implode(',', $current_medium_ids)) . '" class="field-input" />';
        
        echo '</div>'; // .cf7-field-content
        echo '</div>'; // .cf7-artistic-mediums-field
    }
    
    /**
     * Render submitted files gallery with S3 integration and modern preview system.
     * Displays uploaded files with thumbnails, lightbox previews, and ZIP download.
     */
    public static function render_submitted_files($post) {
        // Get files from the S3-based system
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_files';
        
        error_log('CF7AS Tabs Debug - Querying files for submission_id: ' . $post->ID . ' (type: ' . gettype($post->ID) . ')');
        error_log('CF7AS Tabs Debug - Table name: ' . $table_name);
        
        // Check if table exists, create if it doesn't
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        error_log('CF7AS Tabs Debug - Table exists: ' . ($table_exists ? 'Yes' : 'No'));
        
        if (!$table_exists && class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
            error_log('CF7AS Tabs Debug - Creating missing files table...');
            CF7_Artist_Submissions_Metadata_Manager::create_files_table();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            error_log('CF7AS Tabs Debug - Table created successfully: ' . ($table_exists ? 'Yes' : 'No'));
        }
        
        // Get total count of files in table
        $total_files = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        error_log('CF7AS Tabs Debug - Total files in table: ' . $total_files);
        
        // Check both string and integer versions
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE submission_id = %s OR submission_id = %d ORDER BY created_at ASC",
            (string) $post->ID, (int) $post->ID
        ));
        
        error_log('CF7AS Tabs Debug - Found ' . count($files) . ' files for submission ' . $post->ID);
        error_log('CF7AS Tabs Debug - Files data: ' . print_r($files, true));
        
        // Additional debug: Check what submission_ids are actually in the table
        $all_submission_ids = $wpdb->get_col("SELECT DISTINCT submission_id FROM {$table_name}");
        error_log('CF7AS Tabs Debug - All submission_ids in table: ' . print_r($all_submission_ids, true));
        
        if (empty($files)) {
            echo '<div class="cf7as-file-preview-container">';
            echo '<div class="cf7as-no-files">';
            echo '<div class="cf7as-empty-icon"><span class="dashicons dashicons-images-alt2"></span></div>';
            echo '<h3>' . __('No files submitted', 'cf7-artist-submissions') . '</h3>';
            echo '<p>' . __('This artist has not uploaded any works yet.', 'cf7-artist-submissions') . '</p>';
            
            // Add debugging info for administrators
            if (current_user_can('manage_options')) {
                echo '<div style="margin-top: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
                echo '<strong>Debug Info (Administrators only):</strong><br>';
                echo 'Submission ID: ' . $post->ID . '<br>';
                echo 'Table exists: ' . ($table_exists ? 'Yes' : 'No') . '<br>';
                echo 'Total files in database: ' . $total_files . '<br>';
                echo 'All submission IDs: ' . implode(', ', $all_submission_ids) . '<br>';
                if (!$table_exists) {
                    echo '<br><strong style="color: #d63638;">Solution:</strong> The files table is missing. <a href="' . admin_url('plugins.php') . '" style="color: #2271b1;">Deactivate and reactivate the plugin</a> to create the missing database table.';
                }
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            return;
        }
        
        // Display ZIP download button
        echo '<div class="cf7as-zip-download-container">';
        echo '<a href="' . admin_url('admin-ajax.php?action=cf7as_download_zip&submission_id=' . $post->ID . '&nonce=' . wp_create_nonce('cf7as_zip_download_' . $post->ID)) . '" class="cf7as-zip-download">';
        echo __('Download All Original Files (.zip)', 'cf7-artist-submissions');
        echo '</a>';
        echo '<p class="description">' . sprintf(__('Download all %d submitted files as a ZIP archive.', 'cf7-artist-submissions'), count($files)) . '</p>';
        echo '</div>';
        
        echo '<div class="cf7as-file-preview-container">';
        echo '<h4>' . __('Submitted Works', 'cf7-artist-submissions') . '</h4>';
        echo '<div class="cf7as-file-preview-grid">';
        
        foreach ($files as $file) {
            $file_ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
            $mime_type = $file->mime_type;
            $file_size = self::format_file_size($file->file_size);
            
            // Get work metadata for this file (fetch once)
            $work_title = get_post_meta($post->ID, 'cf7_work_title_' . sanitize_key($file->original_name), true);
            $work_statement = get_post_meta($post->ID, 'cf7_work_statement_' . sanitize_key($file->original_name), true);
            
            // Use work title for display if available, otherwise use filename
            $display_title = !empty($work_title) ? $work_title : $file->original_name;
            
            // Get S3 presigned URL for download (works reliably)
            if (!class_exists('CF7_Artist_Submissions_S3_Handler')) {
                continue;
            }
            
            $s3_handler = new CF7_Artist_Submissions_S3_Handler();
            $download_url = $s3_handler->get_presigned_download_url($file->s3_key);
            
            if (!$download_url) {
                continue;
            }
            
            echo '<div class="cf7as-file-item">';
            
            // File preview/thumbnail
            if (self::is_image_file($file_ext)) {
                // Use thumbnail if available for display, but use download URL for lightbox since that's working
                $thumbnail_url = !empty($file->thumbnail_url) ? $file->thumbnail_url : $download_url;
                
                echo '<a href="' . esc_url($download_url) . '" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($display_title) . '" class="cf7as-file-thumbnail">';
                echo '</a>';
                
            } elseif (self::is_video_file($file_ext)) {
                // Inline video player for videos - use download URL since that's working
                echo '<video class="cf7as-video-preview" controls preload="metadata">';
                echo '<source src="' . esc_url($download_url) . '" type="' . esc_attr($mime_type) . '">';
                echo __('Your browser does not support the video tag.', 'cf7-artist-submissions');
                echo '</video>';
                
            } else {
                // Document/other file icon
                echo '<div class="cf7as-file-icon">';
                echo self::get_file_icon($file_ext);
                echo '</div>';
            }
            
            // File information
            echo '<div class="cf7as-file-info">';
            
            // Display work title if available, otherwise show filename
            if (!empty($work_title)) {
                echo '<div class="cf7as-work-title">' . esc_html($work_title) . '</div>';
                echo '<div class="cf7as-file-name">' . esc_html($file->original_name) . '</div>';
            } else {
                echo '<div class="cf7as-file-name">' . esc_html($file->original_name) . '</div>';
            }
            
            // Display work statement if available
            if (!empty($work_statement)) {
                echo '<div class="cf7as-work-statement">' . esc_html($work_statement) . '</div>';
            }
            
            echo '<div class="cf7as-file-meta">' . esc_html($file_size) . ' • ' . esc_html(strtoupper($file_ext)) . '</div>';
            echo '</div>';
            
            // File actions
            echo '<div class="cf7as-file-actions">';
            
            // Preview button for images/videos
            if (self::is_image_file($file_ext)) {
                echo '<a href="' . esc_url($download_url) . '" class="button button-small" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                echo '<span class="dashicons dashicons-visibility"></span> ' . __('Preview', 'cf7-artist-submissions');
                echo '</a>';
            }
            
            // Download button
            echo '<a href="' . esc_url($download_url) . '" class="button button-small" download="' . esc_attr($file->original_name) . '">';
            echo '<span class="dashicons dashicons-download"></span> ' . __('Download', 'cf7-artist-submissions');
            echo '</a>';
            
            echo '</div>'; // .cf7as-file-actions
            echo '</div>'; // .cf7as-file-item
        }
        
        echo '</div>'; // .cf7as-file-preview-grid
        echo '</div>'; // .cf7as-file-preview-container
    }
    

    
    /**
     * Helper methods for file type detection and formatting
     */
    private static function is_image_file($extension) {
        return in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'));
    }
    
    private static function is_video_file($extension) {
        return in_array($extension, array('mp4', 'mov', 'webm', 'avi', 'mkv', 'mpeg'));
    }
    
    private static function get_file_icon($extension) {
        switch ($extension) {
            case 'pdf':
                return '<span class="dashicons dashicons-pdf"></span>';
            case 'doc':
            case 'docx':
                return '<span class="dashicons dashicons-media-document"></span>';
            case 'txt':
            case 'rtf':
                return '<span class="dashicons dashicons-media-text"></span>';
            default:
                return '<span class="dashicons dashicons-media-default"></span>';
        }
    }
    
    private static function format_file_size($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Render curator notes interface with independent saving functionality.
     * Provides private note-taking system with auto-save and audit trail integration.
     */
    public static function render_curator_notes($post) {
        wp_nonce_field('cf7_artist_submissions_notes_nonce', 'cf7_artist_submissions_notes_nonce');
        $notes = get_post_meta($post->ID, 'cf7_curator_notes', true);
        
        echo '<div class="cf7-curator-notes-container">';
        echo '<textarea name="cf7_curator_notes" id="cf7_curator_notes" rows="8" style="width: 100%; margin-bottom: 15px;" placeholder="' . __('Add your notes about this submission...', 'cf7-artist-submissions') . '">' . esc_textarea($notes) . '</textarea>';
        echo '<div class="cf7-curator-notes-controls">';
        echo '<button type="button" id="cf7-save-curator-notes" class="button button-primary" data-post-id="' . $post->ID . '">';
        echo '<span class="dashicons dashicons-saved"></span> ' . __('Save Notes', 'cf7-artist-submissions');
        echo '</button>';
        echo '<span class="cf7-save-status" style="margin-left: 10px; font-style: italic; color: #666;"></span>';
        echo '</div>';
        echo '<p class="description" style="margin-top: 10px;">' . __('Private notes visible only to curators and administrators.', 'cf7-artist-submissions') . '</p>';
        echo '</div>';
    }
    
    // ============================================================================
    // AJAX HANDLERS SECTION
    // ============================================================================
    
    /**
     * AJAX handler for dynamic tab content loading with security validation.
     * 
     * Processes secure tab content requests with comprehensive nonce verification,
     * permission checking, and dynamic content generation. Supports all tab types
     * including profile, works, conversations, actions, notes, and export tabs.
     * Provides seamless AJAX navigation experience with proper error handling
     * and optimized content delivery for enhanced administrative efficiency.
     * 
     * @since 1.0.0
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
            case 'cf7-tab-export':
                self::render_export_tab($post);
                break;
            default:
                echo '<p>Invalid tab</p>';
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(array('content' => $content));
    }
    
    /**
     * Save editable field data with validation and audit trail integration.
     * 
     * Processes field updates during post save with comprehensive validation,
     * type-specific sanitization, and change detection. Handles standard fields,
     * artistic mediums taxonomy updates, and automatic post title synchronization.
     * Implements audit logging and WordPress action hooks for extensible field
     * management and administrative oversight across submission workflows.
     * 
     * @since 1.0.0
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
     * Save curator notes with change detection and audit logging.
     * Processes curator notes updates with validation and audit trail integration.
     * 
     * @since 1.0.0
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
    
    // ============================================================================
    // WORDPRESS INTEGRATION SECTION
    // ============================================================================
    
    /**
     * Hide default WordPress edit page elements for clean tabbed interface.
     * Removes standard admin elements to provide seamless tabbed experience.
     */
    public static function hide_default_elements() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'cf7_submission') {
            return;
        }
        ?>
        <style>
            /* Hide WordPress admin header elements */
            .wp-heading-inline,
            .page-title-action,
            #screen-options-link-wrap,
            #screen-meta,
            .screen-meta-toggle {
                display: none !important;
            }
            
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
            
            /* Move content up and add proper margins */
            #wpbody-content {
                padding-top: 10px !important;
            }
            
            .wrap {
                margin-top: 0 !important;
                margin-right: 20px !important;
            }
            
            /* Make content area full width with proper spacing */
            #poststuff {
                margin-right: 0 !important;
                margin-top: 0 !important;
            }
            
            #post-body.columns-2 #postbox-container-1 {
                display: none;
            }
            
            #post-body.columns-2 #postbox-container-2 {
                margin-right: 0;
                width: 100%;
            }
            
            /* Artist header contained within post area - using shared gradient header */
            .cf7-gradient-header.cf7-artist-header {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            
            /* Remove postbox styling from our container */
            #cf7_submission_tabs.postbox {
                border: none;
                box-shadow: none;
                background: transparent;
                margin-top: 0;
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
     * Render custom page layout integration hook.
     * Maintains backward compatibility for custom layout rendering.
     */
    public static function render_custom_page_layout($post) {
        // This method is now replaced by the integrated header in render_tabbed_interface
        // Left empty to avoid conflicts but maintain backward compatibility
    }
    
    /**
     * Render professional status circle dropdown with visual status management.
     * Creates interactive status selector with color-coded indicators and smooth transitions.
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
            'shortlisted' => array(
                'label' => __('Shortlisted', 'cf7-artist-submissions'),
                'color' => '#ec4899',
                'icon' => 'paperclip'
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
     * AJAX handler for status updates with validation and audit integration.
     * 
     * Processes submission status changes with comprehensive security validation,
     * status verification, and taxonomy updates. Provides real-time status
     * management with visual feedback and maintains audit trail for administrative
     * oversight. Supports all submission statuses with proper error handling
     * and seamless frontend integration for efficient workflow management.
     * 
     * @since 1.0.0
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
        $valid_statuses = array('new', 'reviewed', 'awaiting-information', 'shortlisted', 'selected', 'rejected');
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
            'shortlisted' => array('label' => __('Shortlisted', 'cf7-artist-submissions'), 'color' => '#ec4899', 'icon' => 'paperclip'),
            'selected' => array('label' => __('Selected', 'cf7-artist-submissions'), 'color' => '#10b981', 'icon' => 'yes-alt'),
            'rejected' => array('label' => __('Rejected', 'cf7-artist-submissions'), 'color' => '#ef4444', 'icon' => 'dismiss')
        );
        
        wp_send_json_success(array(
            'status' => $new_status,
            'data' => $statuses[$new_status]
        ));
    }
    
    /**
     * AJAX handler for comprehensive submission data saving with field validation.
     * 
     * Processes real-time field updates including standard submission data,
     * artistic mediums taxonomy management, and curator notes. Implements
     * comprehensive validation, type-specific sanitization, and change detection
     * with audit trail integration. Provides seamless frontend editing experience
     * with proper error handling and optimized data persistence workflows.
     * 
     * @since 1.0.0
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
                // Handle artistic mediums separately
                if ($meta_key === 'artistic_mediums') {
                    if (is_array($value)) {
                        // Get old mediums for logging
                        $old_terms = get_the_terms($post_id, 'artistic_medium');
                        $old_medium_names = array();
                        if (!empty($old_terms) && !is_wp_error($old_terms)) {
                            foreach ($old_terms as $term) {
                                $old_medium_names[] = $term->name;
                            }
                        }
                        
                        // Update the artistic mediums
                        $medium_ids = array_map('intval', $value);
                        $result = wp_set_object_terms($post_id, $medium_ids, 'artistic_medium');
                        
                        if (!is_wp_error($result)) {
                            $updated_fields++;
                            
                            // Get new medium names for logging
                            $new_terms = get_the_terms($post_id, 'artistic_medium');
                            $new_medium_names = array();
                            if (!empty($new_terms) && !is_wp_error($new_terms)) {
                                foreach ($new_terms as $term) {
                                    $new_medium_names[] = $term->name;
                                }
                            }
                            
                            // Log the update
                            $old_medium_names_str = implode(', ', $old_medium_names);
                            $new_medium_names_str = implode(', ', $new_medium_names);
                            do_action('cf7_artist_submission_field_updated', $post_id, 'artistic_mediums', $old_medium_names_str, $new_medium_names_str);
                        }
                    }
                    continue;
                }
                
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
        
        // Get updated field values to return to the frontend
        $updated_data = array();
        
        // Get all meta fields that were potentially updated
        foreach ($field_data as $meta_key => $value) {
            if ($meta_key === 'artistic_mediums') {
                // Get updated artistic mediums display HTML
                $terms = get_the_terms($post_id, 'artistic_medium');
                $medium_tags_html = '';
                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                        $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                        
                        if ($bg_color && $text_color) {
                            $style = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                            $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
                        } else {
                            $style = '';
                            $data_attrs = ' data-color=""';
                        }
                        $medium_tags_html .= '<span class="cf7-medium-tag"' . $data_attrs . $style . '>' . esc_html($term->name) . '</span>';
                    }
                }
                if (empty($medium_tags_html)) {
                    $medium_tags_html = '<span class="cf7-no-mediums-text">' . __('No mediums selected', 'cf7-artist-submissions') . '</span>';
                }
                $updated_data['artistic_mediums'] = $medium_tags_html;
            } else {
                $updated_data[$meta_key] = get_post_meta($post_id, $meta_key, true);
            }
        }
        
        wp_send_json_success(array(
            'message' => "Successfully updated {$updated_fields} field(s)",
            'updated_fields' => $updated_fields,
            'field_data' => $updated_data
        ));
    }
    
    /**
     * AJAX handler for independent curator notes saving with audit integration.
     * Provides real-time notes saving with validation and change logging.
     * 
     * @since 1.0.0
     */
    public static function ajax_save_curator_notes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $post_id = intval($_POST['post_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Get old notes for logging
        $old_notes = get_post_meta($post_id, 'cf7_curator_notes', true);
        
        // Update the notes
        update_post_meta($post_id, 'cf7_curator_notes', $notes);
        
        // Log the update
        do_action('cf7_artist_submission_field_updated', $post_id, 'cf7_curator_notes', $old_notes, $notes);
        
        wp_send_json_success(array(
            'message' => 'Curator notes saved successfully',
            'notes' => $notes
        ));
    }
    
    /**
     * AJAX handler for artistic mediums taxonomy management with validation.
     * Processes medium assignments with taxonomy integration and audit logging.
     * 
     * @since 1.0.0
     */
    public static function ajax_save_artistic_mediums() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $post_id = intval($_POST['post_id']);
        $medium_ids = isset($_POST['medium_ids']) ? array_map('intval', $_POST['medium_ids']) : array();
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Get old mediums for logging
        $old_terms = get_the_terms($post_id, 'artistic_medium');
        $old_medium_names = array();
        if (!empty($old_terms) && !is_wp_error($old_terms)) {
            foreach ($old_terms as $term) {
                $old_medium_names[] = $term->name;
            }
        }
        
        // Update the artistic mediums
        $result = wp_set_object_terms($post_id, $medium_ids, 'artistic_medium');
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Failed to update artistic mediums: ' . $result->get_error_message()));
        }
        
        // Get updated mediums for response
        $updated_terms = get_the_terms($post_id, 'artistic_medium');
        $medium_names = array();
        $medium_tags_html = '';
        
        if (!empty($updated_terms) && !is_wp_error($updated_terms)) {
            foreach ($updated_terms as $term) {
                $medium_names[] = $term->name;
                $medium_tags_html .= '<span class="cf7-medium-tag">' . esc_html($term->name) . '</span>';
            }
        } else {
            $medium_tags_html = '<span class="cf7-no-mediums-text">' . __('No mediums selected', 'cf7-artist-submissions') . '</span>';
        }
        
        // Log the update
        $new_medium_names = implode(', ', $medium_names);
        $old_medium_names_str = implode(', ', $old_medium_names);
        do_action('cf7_artist_submission_field_updated', $post_id, 'artistic_mediums', $old_medium_names_str, $new_medium_names);
        
        wp_send_json_success(array(
            'message' => 'Artistic mediums saved successfully',
            'medium_names' => $medium_names,
            'medium_tags_html' => $medium_tags_html
        ));
    }
    
    /**
     * AJAX handler for ZIP download functionality
     * 
     * Creates and streams a ZIP file containing all original files for a submission
     */
    public static function ajax_download_zip() {
        // Verify nonce and permissions
        $submission_id = intval($_GET['submission_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        if (!wp_verify_nonce($nonce, 'cf7as_zip_download_' . $submission_id)) {
            wp_die(__('Invalid security token.', 'cf7-artist-submissions'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'cf7-artist-submissions'));
        }
        
        // Verify submission exists
        $post = get_post($submission_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            wp_die(__('Submission not found.', 'cf7-artist-submissions'));
        }
        
        try {
            // Use the ZIP downloader to create and stream the ZIP file
            $zip_downloader = new CF7_Artist_Submissions_Zip_Downloader();
            $zip_downloader->download_submission_zip($submission_id);
            
        } catch (Exception $e) {
            wp_die(__('Error creating ZIP file: ', 'cf7-artist-submissions') . $e->getMessage());
        }
        
        exit;
    }
}
