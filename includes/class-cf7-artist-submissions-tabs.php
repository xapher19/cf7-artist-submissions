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
        add_action('wp_ajax_cf7as_download_file', array(__CLASS__, 'ajax_download_file'));
        
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
        
        // Get dashboard tag from open call configuration
        $dashboard_tag = '';
        $open_call_terms = wp_get_object_terms($post->ID, 'open_call');
        if (!is_wp_error($open_call_terms) && !empty($open_call_terms)) {
            $open_call_term = $open_call_terms[0];
            $open_calls_config = get_option('cf7_artist_submissions_open_calls', array());
            
            foreach ($open_calls_config as $call_config) {
                if (isset($call_config['title']) && $call_config['title'] === $open_call_term->name) {
                    if (!empty($call_config['dashboard_tag'])) {
                        $dashboard_tag = $call_config['dashboard_tag'];
                        break;
                    }
                }
            }
            
            // Fallback to the term name if no dashboard tag is set
            if (empty($dashboard_tag)) {
                $dashboard_tag = $open_call_term->name;
            }
        }
        
        // Get current status
        $status_terms = wp_get_object_terms($post->ID, 'submission_status');
        $current_status = (!is_wp_error($status_terms) && !empty($status_terms)) ? $status_terms[0]->slug : 'new';
        
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
                        <?php if (!empty($dashboard_tag)): ?>
                            <span class="cf7-artist-open-call">
                                <span class="dashicons dashicons-tag"></span>
                                <?php echo esc_html($dashboard_tag); ?>
                            </span>
                        <?php endif; ?>
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
     * Render submitted works tab with lightbox gallery integration and PDF viewer.
     * Displays artist submissions with professional image preview capabilities and inline file viewing.
     */
    public static function render_works_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <!-- File Gallery Section -->
            <div class="cf7-works-gallery-section">
                <?php self::render_submitted_files($post); ?>
            </div>
            
            <!-- PDF/Document Viewer Section -->
            <div class="cf7-works-viewer-section">
                <?php self::render_integrated_file_viewer($post); ?>
            </div>
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
     * Render PDF export tab with enhanced export options.
     * Provides professional PDF generation with ratings, curator functionality,
     * and comprehensive content selection including AWS Lambda integration.
     */
    public static function render_export_tab($post) {
        ?>
        <div class="cf7-tab-section">
            <h3 class="cf7-tab-section-title"><?php _e('Export Options', 'cf7-artist-submissions'); ?></h3>
            <div class="cf7-export-options">
                <div class="cf7-export-section">
                    <p class="description"><?php _e('Generate a professionally formatted PDF with selected content sections. Choose which information to include in your export.', 'cf7-artist-submissions'); ?></p>
                    
                    <div class="cf7-export-options-list">
                        <div class="cf7-export-option-group">
                            <h4><?php _e('Basic Information', 'cf7-artist-submissions'); ?></h4>
                            <label>
                                <input type="checkbox" name="include_personal_info" checked> 
                                <?php _e('Include Personal Information', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Name, email, contact details, bio', 'cf7-artist-submissions'); ?></span>
                            </label>
                            <label>
                                <input type="checkbox" name="include_works" checked> 
                                <?php _e('Include Submitted Works', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Artwork images, descriptions, details', 'cf7-artist-submissions'); ?></span>
                            </label>
                        </div>
                        
                        <div class="cf7-export-option-group">
                            <h4><?php _e('Evaluation & Feedback', 'cf7-artist-submissions'); ?></h4>
                            <label>
                                <input type="checkbox" name="include_ratings"> 
                                <?php _e('Include Ratings & Scores', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Technical, creative, and overall ratings', 'cf7-artist-submissions'); ?></span>
                            </label>
                            <label>
                                <input type="checkbox" name="include_curator_notes"> 
                                <?php _e('Include Curator Notes', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Internal curator observations and notes', 'cf7-artist-submissions'); ?></span>
                            </label>
                            <label>
                                <input type="checkbox" name="include_curator_comments"> 
                                <?php _e('Include Curator Comments', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Curator feedback and discussion threads', 'cf7-artist-submissions'); ?></span>
                            </label>
                        </div>
                        
                        <div class="cf7-export-option-group">
                            <h4><?php _e('Document Options', 'cf7-artist-submissions'); ?></h4>
                            <label>
                                <input type="checkbox" name="confidential_watermark" checked> 
                                <?php _e('Add "Private & Confidential" Watermark', 'cf7-artist-submissions'); ?>
                                <span class="cf7-option-description"><?php _e('Adds security watermark to document', 'cf7-artist-submissions'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="cf7-export-actions">
                        <button type="button" class="button button-primary cf7-export-pdf-btn" data-post-id="<?php echo esc_attr($post->ID); ?>">
                            <span class="dashicons dashicons-pdf"></span>
                            <?php _e('Generate PDF', 'cf7-artist-submissions'); ?>
                        </button>
                        <div class="cf7-export-status"></div>
                        <div class="cf7-export-progress" style="display: none;">
                            <div class="cf7-progress-bar">
                                <div class="cf7-progress-fill"></div>
                            </div>
                            <div class="cf7-progress-text">Generating PDF...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .cf7-export-options {
            padding: 0;
        }
        
        .cf7-export-option-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        
        .cf7-export-option-group h4 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 14px;
            font-weight: 600;
        }
        
        .cf7-export-options-list label {
            display: block;
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .cf7-export-options-list input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .cf7-option-description {
            display: block;
            color: #666;
            font-size: 12px;
            margin-left: 24px;
            margin-top: 2px;
        }
        
        .cf7-export-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #dcdcde;
        }
        
        .cf7-export-pdf-btn {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            min-width: 140px;
        }
        
        .cf7-export-pdf-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cf7-export-status {
            margin-top: 12px;
            padding: 8px 0;
            font-size: 13px;
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
        
        .cf7-export-progress {
            margin-top: 15px;
        }
        
        .cf7-progress-bar {
            width: 100%;
            height: 8px;
            background: #e1e1e1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .cf7-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #3498db 0%, #2ecc71 100%);
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s ease;
            animation: progress-pulse 1.5s infinite;
        }
        
        @keyframes progress-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .cf7-progress-text {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .cf7-download-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #00a32a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .cf7-download-link:hover {
            background: #008a20;
            color: white;
            text-decoration: none;
        }
        
        .cf7-download-link .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
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
            // Skip internal meta, file fields, header fields, uploader data fields, open call fields, and any fields related to 'works' or 'files'
            if (substr($key, 0, 1) === '_' || 
                substr($key, 0, 8) === 'cf7_file_' || 
                substr($key, -5) === '_data' ||  // Skip uploader data fields
                $key === 'cf7_submission_date' || 
                $key === 'cf7_curator_notes' ||
                $key === 'cf7_your-work-raw' ||
                $key === 'cf7_artist-name' || 
                $key === 'cf7_your-name' ||
                $key === 'cf7_pronouns' ||
                $key === 'cf7_your-pronouns' ||
                $key === 'cf7_email' ||
                $key === 'cf7_your-email' ||
                $key === 'cf7_mediums' ||  // Skip duplicate mediums field
                $key === 'cf7_artistic-mediums' ||  // Skip duplicate artistic mediums field
                $key === 'cf7_artistic_mediums' ||  // Skip artistic mediums without hyphen
                $key === 'cf7_open_call' ||  // Skip open call field
                $key === 'cf7_open_call_slug' ||  // Skip open call slug field
                strpos($key, 'medium') !== false ||  // Skip any field containing 'medium'
                strpos($key, 'work') !== false ||
                strpos($key, 'files') !== false ||
                strpos($key, 'open_call') !== false ||  // Skip any field containing 'open_call'
                strpos($key, 'call') !== false) {  // Skip any field containing 'call'
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
        // Determine if this is a text-based submission by checking the open call configuration
        $is_text_based = false;
        $taxonomy = 'artistic_medium';
        $field_label = __('Artistic Mediums', 'cf7-artist-submissions');
        $field_icon = 'dashicons-art';
        
        // Get the open call terms for this submission
        $open_call_terms = wp_get_object_terms($post_id, 'open_call');
        if (!is_wp_error($open_call_terms) && !empty($open_call_terms)) {
            $open_call_term = $open_call_terms[0];
            $open_calls_config = get_option('cf7_artist_submissions_open_calls', array());
            
            // Check if this open call is configured as text-based
            if (!empty($open_calls_config['calls'])) {
                foreach ($open_calls_config['calls'] as $call_config) {
                    if (isset($call_config['title']) && $call_config['title'] === $open_call_term->name) {
                        if (isset($call_config['call_type']) && $call_config['call_type'] === 'text_based') {
                            $is_text_based = true;
                            $taxonomy = 'text_medium';
                            $field_label = __('Text Mediums', 'cf7-artist-submissions');
                            $field_icon = 'dashicons-edit';
                            break;
                        }
                    }
                }
            }
        }
        
        // Get current medium terms assigned to this submission
        $current_terms = get_the_terms($post_id, $taxonomy);
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
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($all_mediums) || is_wp_error($all_mediums)) {
            echo '<div class="cf7-no-mediums">';
            echo '<p>' . sprintf(__('No %s are available. Please configure the mediums taxonomy.', 'cf7-artist-submissions'), strtolower($field_label)) . '</p>';
            echo '</div>';
            return;
        }
        
        $field_data_type = $is_text_based ? 'text_mediums' : 'artistic_mediums';
        echo '<div class="cf7-artistic-mediums-field cf7-profile-field" data-post-id="' . esc_attr($post_id) . '" data-field="' . esc_attr($field_data_type) . '" data-type="mediums" data-taxonomy="' . esc_attr($taxonomy) . '">';
        
        echo '<div class="cf7-field-header">';
        echo '<span class="cf7-field-icon dashicons ' . esc_attr($field_icon) . '"></span>';
        echo '<label class="cf7-field-label">' . esc_html($field_label) . '</label>';
        echo '</div>';
        
        echo '<div class="cf7-field-content">';
        
        // Display mode - shown when not in edit mode
        echo '<div class="cf7-mediums-display">';
        if (!empty($current_medium_names)) {
            // Get the full term objects to access colors
            $current_terms_objects = get_the_terms($post_id, $taxonomy);
            foreach ($current_terms_objects as $term) {
                $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                
                // Use default colors for text mediums if none set
                if ($is_text_based && !$bg_color) {
                    $bg_color = '#805AD5';
                    $text_color = '#ffffff';
                }
                
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
            
            // Use default colors for text mediums if none set
            if ($is_text_based && !$bg_color) {
                $bg_color = '#805AD5';
                $text_color = '#ffffff';
            }
            
            $style_vars = '';
            $data_attrs = '';
            if ($bg_color && $text_color) {
                $style_vars = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
            } else {
                $data_attrs = ' data-color=""';
            }
            
            $field_name = $is_text_based ? 'text_mediums[]' : 'artistic_mediums[]';
            echo '<label class="cf7-medium-checkbox"' . $data_attrs . $style_vars . '>';
            echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
            echo '<span class="checkmark"></span>';
            echo '<span class="medium-name">' . esc_html($term->name) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Hidden input to store current values for the edit system
        echo '<input type="hidden" name="cf7_editable_fields[' . esc_attr($field_data_type) . ']" value="' . esc_attr(implode(',', $current_medium_ids)) . '" class="field-input" />';
        
        echo '</div>'; // .cf7-field-content
        echo '</div>'; // .cf7-artistic-mediums-field
    }
    
    /**
     * Render modern works list view with integrated rating system.
     * Displays submitted works in a professional list layout with ratings and comments.
     */
    public static function render_works_list_view($post, $files) {
        // Initialize ratings system if not already done
        if (class_exists('CF7_Artist_Submissions_Ratings')) {
            CF7_Artist_Submissions_Ratings::maybe_create_table();
        }
        
        echo '<div class="cf7-works-list-view">';
        echo '<div class="cf7-works-list-header">';
        echo '<h3 class="cf7-works-list-title">' . __('Submitted Works', 'cf7-artist-submissions') . '</h3>';
        echo '<div class="cf7-works-count">' . count($files) . ' ' . _n('work', 'works', count($files), 'cf7-artist-submissions') . '</div>';
        echo '</div>';
        
        echo '<div class="cf7-works-list-body">';
        
        // Initialize media converter once for efficiency
        $converter = null;
        if (class_exists('CF7_Artist_Submissions_Media_Converter')) {
            $converter = new CF7_Artist_Submissions_Media_Converter();
        }
        
        // Initialize S3 handler
        $s3_handler = null;
        if (class_exists('CF7_Artist_Submissions_S3_Handler')) {
            $s3_handler = new CF7_Artist_Submissions_S3_Handler();
        }
        
        foreach ($files as $file) {
            $file_ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
            $mime_type = $file->mime_type;
            $file_size = self::format_file_size($file->file_size);
            
            // Get work metadata for this file
            $work_title = get_post_meta($post->ID, 'cf7_work_title_' . sanitize_key($file->original_name), true);
            $work_statement = get_post_meta($post->ID, 'cf7_work_statement_' . sanitize_key($file->original_name), true);
            
            // Use work title for display if available, otherwise use filename
            $display_title = !empty($work_title) ? $work_title : pathinfo($file->original_name, PATHINFO_FILENAME);
            
            // Get URLs for preview and download
            $original_download_url = '';
            $preview_url = '';
            $thumbnail_url = '';
            $has_video_thumbnail = false;
            
            if ($s3_handler) {
                $original_download_url = $s3_handler->get_presigned_download_url($file->s3_key);
                $preview_url = $original_download_url; // Fallback to original
                $thumbnail_url = $original_download_url; // Fallback to original
                
                // Get converted versions using the media converter
                if ($converter) {
                    if (self::is_image_file($file_ext)) {
                        // Get best version for preview (medium or large)
                        $preview_version = $converter->get_best_version_for_serving($file->s3_key, 'large');
                        if (!$preview_version) {
                            $preview_version = $converter->get_best_version_for_serving($file->s3_key, 'medium');
                        }
                        
                        if ($preview_version && !empty($preview_version->converted_s3_key)) {
                            $preview_url = $s3_handler->get_presigned_preview_url($preview_version->converted_s3_key);
                        }
                        
                        // Get thumbnail version
                        $thumbnail_version = $converter->get_thumbnail_version($file->s3_key);
                        if ($thumbnail_version && !empty($thumbnail_version->converted_s3_key)) {
                            $thumbnail_url = $s3_handler->get_presigned_preview_url($thumbnail_version->converted_s3_key);
                        }
                    } elseif (self::is_video_file($file_ext)) {
                        // Get converted versions for videos
                        $video_version = $converter->get_best_version_for_serving($file->s3_key, 'web');
                        if (!$video_version) {
                            $video_version = $converter->get_best_version_for_serving($file->s3_key, 'medium');
                        }
                        
                        if ($video_version && !empty($video_version->converted_s3_key)) {
                            $preview_url = $s3_handler->get_presigned_preview_url($video_version->converted_s3_key);
                        }
                        
                        // Get video thumbnail
                        $thumbnail_version = $converter->get_thumbnail_version($file->s3_key);
                        if ($thumbnail_version && !empty($thumbnail_version->converted_s3_key)) {
                            $thumbnail_url = $s3_handler->get_presigned_preview_url($thumbnail_version->converted_s3_key);
                            $has_video_thumbnail = true;
                        }
                    }
                }
            }
            
            echo '<div class="cf7-work-item">';
            
            // Work preview section
            echo '<div class="cf7-work-preview">';
            if (self::is_image_file($file_ext) && $preview_url) {
                echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($display_title) . '" />';
                echo '<div class="cf7-preview-overlay">';
                echo '<a href="' . esc_url($preview_url) . '" class="cf7-preview-button" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                echo '<span class="dashicons dashicons-visibility"></span> ' . __('Preview', 'cf7-artist-submissions');
                echo '</a>';
                echo '</div>';
            } elseif (self::is_video_file($file_ext)) {
                if ($has_video_thumbnail) {
                    echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($display_title) . '" />';
                    echo '<div class="cf7-preview-overlay cf7-video-overlay">';
                    echo '<a href="' . esc_url($preview_url) . '" class="cf7-preview-button" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                    echo '<span class="dashicons dashicons-video-alt3"></span> ' . __('Play Video', 'cf7-artist-submissions');
                    echo '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="cf7-file-icon"><span class="dashicons dashicons-video-alt3"></span></div>';
                }
            } else {
                echo '<div class="cf7-file-icon">' . self::get_file_icon($file_ext) . '</div>';
            }
            echo '</div>';
            
            // Work information section
            echo '<div class="cf7-work-info">';
            echo '<div class="cf7-work-header">';
            echo '<h4 class="cf7-work-title">' . esc_html($display_title) . '</h4>';
            echo '</div>';
            
            echo '<div class="cf7-work-meta">';
            echo '<div class="cf7-work-meta-item">';
            echo '<span class="dashicons dashicons-media-default"></span>';
            echo '<span>' . esc_html($file->original_name) . '</span>';
            echo '</div>';
            echo '<div class="cf7-work-meta-item">';
            echo '<span class="dashicons dashicons-archive"></span>';
            echo '<span>' . esc_html($file_size) . ' • ' . esc_html(strtoupper($file_ext)) . '</span>';
            echo '</div>';
            echo '<div class="cf7-work-meta-item">';
            echo '<span class="dashicons dashicons-calendar-alt"></span>';
            echo '<span>' . esc_html(date_i18n(get_option('date_format'), strtotime($file->created_at))) . '</span>';
            echo '</div>';
            echo '</div>';
            
            // Display work statement if available
            if (!empty($work_statement)) {
                echo '<div class="cf7-work-description">' . esc_html($work_statement) . '</div>';
            }
            
            // Work actions
            echo '<div class="cf7-work-actions">';
            
            // Preview button
            if (self::is_image_file($file_ext) && $preview_url) {
                echo '<a href="' . esc_url($preview_url) . '" class="cf7-work-action-btn primary" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                echo '<span class="dashicons dashicons-visibility"></span> ' . __('Preview', 'cf7-artist-submissions');
                echo '</a>';
            } elseif (self::is_video_file($file_ext) && $preview_url) {
                echo '<a href="' . esc_url($preview_url) . '" class="cf7-work-action-btn primary" data-lightbox="submission-gallery" data-title="' . esc_attr($display_title) . '">';
                echo '<span class="dashicons dashicons-video-alt3"></span> ' . __('Preview', 'cf7-artist-submissions');
                echo '</a>';
            }
            
            // Download button
            if ($original_download_url) {
                $download_url = admin_url('admin-ajax.php?action=cf7as_download_file&file_id=' . $file->id . '&nonce=' . wp_create_nonce('cf7as_download_file'));
                echo '<a href="' . esc_url($download_url) . '" class="cf7-work-action-btn">';
                echo '<span class="dashicons dashicons-download"></span> ' . __('Download', 'cf7-artist-submissions');
                echo '</a>';
            }
            
            echo '</div>'; // .cf7-work-actions
            echo '</div>'; // .cf7-work-info
            
            // Rating system section
            if (class_exists('CF7_Artist_Submissions_Ratings')) {
                echo CF7_Artist_Submissions_Ratings::render_rating_interface($post->ID, $file->id);
            }
            
            echo '</div>'; // .cf7-work-item
        }
        
        echo '</div>'; // .cf7-works-list-body
        
        // ZIP download section
        echo '<div class="cf7-works-list-footer" style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">';
        echo '<div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">';
        echo '<div>';
        echo '<strong>' . __('Download All Files', 'cf7-artist-submissions') . '</strong><br>';
        echo '<span style="color: #6b7280; font-size: 0.875rem;">' . sprintf(__('Download all %d submitted files as a ZIP archive.', 'cf7-artist-submissions'), count($files)) . '</span>';
        echo '</div>';
        echo '<a href="' . admin_url('admin-ajax.php?action=cf7as_download_zip&submission_id=' . $post->ID . '&nonce=' . wp_create_nonce('cf7as_zip_download_' . $post->ID)) . '" class="cf7-work-action-btn primary">';
        echo '<span class="dashicons dashicons-download"></span> ' . __('Download ZIP', 'cf7-artist-submissions');
        echo '</a>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .cf7-works-list-view
    }

    /**
     * Render submitted files gallery with S3 integration and modern preview system.
     * Displays uploaded files with thumbnails, lightbox previews, and ZIP download.
     */
    public static function render_submitted_files($post) {
        // Initialize ratings system if not already done
        if (class_exists('CF7_Artist_Submissions_Ratings')) {
            CF7_Artist_Submissions_Ratings::maybe_create_table();
        }
        
        // Get files from the S3-based system
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7as_files';
        
        // Check if table exists, create if it doesn't  
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists && class_exists('CF7_Artist_Submissions_Metadata_Manager')) {
            CF7_Artist_Submissions_Metadata_Manager::create_files_table();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        }
        
        // Get total count of files in table
        $total_files = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Check both string and integer versions
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE submission_id = %s OR submission_id = %d ORDER BY created_at ASC",
            (string) $post->ID, (int) $post->ID
        ));
        
        if (empty($files)) {
            echo '<div class="cf7-works-list-view">';
            echo '<div class="cf7-works-list-header">';
            echo '<h3 class="cf7-works-list-title">' . __('Submitted Works', 'cf7-artist-submissions') . '</h3>';
            echo '<div class="cf7-works-count">0 ' . __('works', 'cf7-artist-submissions') . '</div>';
            echo '</div>';
            echo '<div class="cf7-works-list-body">';
            echo '<div class="cf7-no-works">';
            echo '<div class="dashicons dashicons-images-alt2"></div>';
            echo '<h3>' . __('No files submitted', 'cf7-artist-submissions') . '</h3>';
            echo '<p>' . __('This artist has not uploaded any works yet.', 'cf7-artist-submissions') . '</p>';
            
            // Add debugging info for administrators
            if (current_user_can('manage_options')) {
                $all_submission_ids = $wpdb->get_col("SELECT DISTINCT submission_id FROM {$table_name}");
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
            echo '</div>';
            return;
        }
        
        // Render modern list view with ratings
        self::render_works_list_view($post, $files);
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
    
    private static function is_document_file($extension) {
        return in_array($extension, array('doc', 'docx', 'txt', 'rtf'));
    }
    
    private static function get_file_icon($extension) {
        switch ($extension) {
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
     * Render integrated file viewer for PDFs and documents.
     * Provides inline viewing capabilities for submitted files using PDF viewer functionality.
     */
    public static function render_integrated_file_viewer($post) {
        // Check if PDF viewer class is available
        if (!class_exists('CF7_Artist_Submissions_PDF_Viewer')) {
            return;
        }
        
        $pdf_viewer = new CF7_Artist_Submissions_PDF_Viewer();
        
        // Get all file fields from submission (both S3 files and legacy metadata)
        $file_fields = $pdf_viewer->get_submission_files($post->ID);
        $text_content = $pdf_viewer->get_text_submission_content($post->ID);
        
        // Only show viewer if there are viewable files or text content
        if (empty($file_fields) && empty($text_content)) {
            return;
        }
        
        echo '<div class="cf7-integrated-file-viewer">';
        echo '<h4>' . __('File Viewer', 'cf7-artist-submissions') . '</h4>';
        echo '<p class="description">' . __('Click on a file below to view it inline.', 'cf7-artist-submissions') . '</p>';
        
        echo '<div class="cf7-submission-viewer-container">';
        
        if (!empty($file_fields) || !empty($text_content)) {
            echo '<div class="cf7-viewer-tabs">';
            
            $tab_index = 0;
            
            // File tabs
            foreach ($file_fields as $field_name => $files) {
                if (!empty($files)) {
                    foreach ($files as $file_index => $file_info) {
                        $tab_id = 'file_' . $field_name . '_' . $file_index;
                        $active_class = ($tab_index === 0) ? ' active' : '';
                        
                        echo '<div class="cf7-viewer-tab' . $active_class . '" data-tab="' . $tab_id . '">';
                        echo '<span class="cf7-file-icon ' . $pdf_viewer->get_file_icon_class($file_info['type']) . '"></span>';
                        echo '<span class="cf7-file-name">' . esc_html($file_info['name']) . '</span>';
                        echo '</div>';
                        
                        $tab_index++;
                    }
                }
            }
            
            // Text content tab
            if (!empty($text_content)) {
                $active_class = ($tab_index === 0) ? ' active' : '';
                echo '<div class="cf7-viewer-tab' . $active_class . '" data-tab="text_content">';
                echo '<span class="cf7-file-icon cf7-icon-text"></span>';
                echo '<span class="cf7-file-name">Text Submission</span>';
                echo '</div>';
            }
            
            echo '</div>';
            
            echo '<div class="cf7-viewer-content">';
            
            $tab_index = 0;
            
            // File content panels
            foreach ($file_fields as $field_name => $files) {
                if (!empty($files)) {
                    foreach ($files as $file_index => $file_info) {
                        $tab_id = 'file_' . $field_name . '_' . $file_index;
                        $active_class = ($tab_index === 0) ? ' active' : '';
                        
                        echo '<div class="cf7-viewer-panel' . $active_class . '" data-panel="' . $tab_id . '">';
                        echo '<div class="cf7-viewer-header">';
                        echo '<h4>' . esc_html($file_info['name']) . '</h4>';
                        echo '<div class="cf7-viewer-actions">';
                        
                        if (isset($file_info['url'])) {
                            echo '<a href="' . esc_url($file_info['url']) . '" target="_blank" class="cf7-btn cf7-btn-secondary">';
                            echo '<span class="dashicons dashicons-external"></span> Open in New Tab</a>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="cf7-viewer-body" data-file-id="' . esc_attr($file_info['id']) . '" data-file-type="' . esc_attr($file_info['type']) . '">';
                        echo '<div class="cf7-viewer-loading">Loading preview...</div>';
                        echo '</div>';
                        
                        echo '</div>';
                        
                        $tab_index++;
                    }
                }
            }
            
            // Text content panel
            if (!empty($text_content)) {
                $active_class = ($tab_index === 0) ? ' active' : '';
                echo '<div class="cf7-viewer-panel' . $active_class . '" data-panel="text_content">';
                echo '<div class="cf7-viewer-header">';
                echo '<h4>Text Submission</h4>';
                echo '</div>';
                echo '<div class="cf7-viewer-body cf7-text-content">';
                echo '<div class="cf7-text-viewer">' . nl2br(esc_html($text_content)) . '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>'; // .cf7-submission-viewer-container
        echo '</div>'; // .cf7-integrated-file-viewer
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_tabs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check required data
        if (!isset($_POST['post_id']) || !isset($_POST['status'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        // Validate post exists and is correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'cf7_submission') {
            wp_send_json_error(array('message' => 'Invalid post'));
            return;
        }
        
        // Check permissions for specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
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
                // Handle artistic mediums and text mediums separately
                if ($meta_key === 'artistic_mediums' || $meta_key === 'text_mediums') {
                    if (is_array($value)) {
                        // IMPORTANT: Determine the correct taxonomy based on what's actually stored
                        // The field name might be wrong due to frontend/backend mismatch
                        $taxonomy = null;
                        $actual_meta_key = $meta_key;
                        
                        // Check if these IDs exist in text_medium taxonomy first
                        if (!empty($value)) {
                            $test_term = get_term($value[0], 'text_medium');
                            if (!is_wp_error($test_term) && $test_term) {
                                $taxonomy = 'text_medium';
                                $actual_meta_key = 'text_mediums';
                            } else {
                                // Fall back to artistic_medium
                                $taxonomy = 'artistic_medium';
                                $actual_meta_key = 'artistic_mediums';
                            }
                        } else {
                            // If empty array, determine based on submission type
                            $is_text_based = false;
                            $open_calls = get_option('cf7as_open_calls', array());
                            
                            if (!empty($open_calls)) {
                                foreach ($open_calls as $call) {
                                    if (!empty($call['form_id'])) {
                                        $form_meta = get_post_meta($post_id, '_cf7_form_id', true);
                                        if ($form_meta == $call['form_id'] && !empty($call['is_text_based'])) {
                                            $is_text_based = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            $taxonomy = $is_text_based ? 'text_medium' : 'artistic_medium';
                            $actual_meta_key = $is_text_based ? 'text_mediums' : 'artistic_mediums';
                        }
                        
                        // Get old mediums for logging
                        $old_terms = get_the_terms($post_id, $taxonomy);
                        $old_medium_names = array();
                        if (!empty($old_terms) && !is_wp_error($old_terms)) {
                            foreach ($old_terms as $term) {
                                $old_medium_names[] = $term->name;
                            }
                        }
                        
                        // Update the mediums
                        $medium_ids = array_map('intval', $value);
                        $result = wp_set_object_terms($post_id, $medium_ids, $taxonomy);
                        
                        if (!is_wp_error($result)) {
                            $updated_fields++;
                            
                            // Get new medium names for logging
                            $new_terms = get_the_terms($post_id, $taxonomy);
                            $new_medium_names = array();
                            if (!empty($new_terms) && !is_wp_error($new_terms)) {
                                foreach ($new_terms as $term) {
                                    $new_medium_names[] = $term->name;
                                }
                            }
                            
                            // Log the update
                            $old_medium_names_str = implode(', ', $old_medium_names);
                            $new_medium_names_str = implode(', ', $new_medium_names);
                            do_action('cf7_artist_submission_field_updated', $post_id, $actual_meta_key, $old_medium_names_str, $new_medium_names_str);
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
            if ($meta_key === 'artistic_mediums' || $meta_key === 'text_mediums') {
                // Only return medium data if we actually processed it (value was an array)
                if (is_array($value)) {
                    // Determine the correct taxonomy and field key based on actual data
                    $taxonomy = null;
                    $actual_field_key = $meta_key;
                    
                    // Check if these IDs exist in text_medium taxonomy first
                    if (!empty($value)) {
                        $test_term = get_term($value[0], 'text_medium');
                        if (!is_wp_error($test_term) && $test_term) {
                            $taxonomy = 'text_medium';
                            $actual_field_key = 'text_mediums';
                        } else {
                            $taxonomy = 'artistic_medium';
                            $actual_field_key = 'artistic_mediums';
                        }
                    } else {
                        // If empty, determine based on submission type
                        $is_text_based = false;
                        $open_calls = get_option('cf7as_open_calls', array());
                        
                        if (!empty($open_calls)) {
                            foreach ($open_calls as $call) {
                                if (!empty($call['form_id'])) {
                                    $form_meta = get_post_meta($post_id, '_cf7_form_id', true);
                                    if ($form_meta == $call['form_id'] && !empty($call['is_text_based'])) {
                                        $is_text_based = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $taxonomy = $is_text_based ? 'text_medium' : 'artistic_medium';
                        $actual_field_key = $is_text_based ? 'text_mediums' : 'artistic_mediums';
                    }
                    
                    // Clear cache to ensure fresh data
                    wp_cache_delete($post_id, $taxonomy . '_relationships');
                    
                    // Get updated mediums display HTML
                    $terms = get_the_terms($post_id, $taxonomy);
                    $medium_tags_html = '';
                    if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                            $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                            
                            // Use default colors for text mediums if none set
                            if ($taxonomy === 'text_medium' && !$bg_color) {
                                $bg_color = '#805AD5';
                                $text_color = '#ffffff';
                            }
                            
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
                    
                    $updated_data[$actual_field_key] = $medium_tags_html;
                }
                // If we didn't process mediums (value wasn't an array), don't return medium data
            } else {
                $updated_data[$meta_key] = get_post_meta($post_id, $meta_key, true);
            }
        }
        
        // Always ensure we have current medium data in the response, regardless of what was saved
        // This handles cases where the general save is triggered but didn't process mediums
        if (!isset($updated_data['artistic_mediums']) && !isset($updated_data['text_mediums'])) {
            // Check what type of submission this is and get the appropriate mediums
            $is_text_based = false;
            $open_calls = get_option('cf7as_open_calls', array());
            
            if (!empty($open_calls)) {
                foreach ($open_calls as $call) {
                    if (!empty($call['form_id'])) {
                        $form_meta = get_post_meta($post_id, '_cf7_form_id', true);
                        if ($form_meta == $call['form_id'] && !empty($call['is_text_based'])) {
                            $is_text_based = true;
                            break;
                        }
                    }
                }
            }
            
            $taxonomy = $is_text_based ? 'text_medium' : 'artistic_medium';
            $field_key = $is_text_based ? 'text_mediums' : 'artistic_mediums';
            
            // Get current mediums for display
            $terms = get_the_terms($post_id, $taxonomy);
            
            $medium_tags_html = '';
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                    $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                    
                    // Use default colors for text mediums if none set
                    if ($is_text_based && !$bg_color) {
                        $bg_color = '#805AD5';
                        $text_color = '#ffffff';
                    }
                    
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
            $updated_data[$field_key] = $medium_tags_html;
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
        $field_type = isset($_POST['field_type']) ? sanitize_text_field($_POST['field_type']) : 'artistic_mediums';
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Determine taxonomy based on field type
        $taxonomy = ($field_type === 'text_mediums') ? 'text_medium' : 'artistic_medium';
        
        // Get old mediums for logging
        $old_terms = get_the_terms($post_id, $taxonomy);
        $old_medium_names = array();
        if (!empty($old_terms) && !is_wp_error($old_terms)) {
            foreach ($old_terms as $term) {
                $old_medium_names[] = $term->name;
            }
        }
        
        // Update the mediums
        $result = wp_set_object_terms($post_id, $medium_ids, $taxonomy);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Failed to update mediums: ' . $result->get_error_message()));
        }
        
        // Ensure database changes are committed
        wp_cache_delete($post_id, $taxonomy . '_relationships');
        
        // Get updated mediums for response
        $updated_terms = get_the_terms($post_id, $taxonomy);
        
        $medium_names = array();
        $medium_tags_html = '';
        
        if (!empty($updated_terms) && !is_wp_error($updated_terms)) {
            foreach ($updated_terms as $term) {
                $medium_names[] = $term->name;
                $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                
                // Use default colors for text mediums if none set
                if ($field_type === 'text_mediums' && !$bg_color) {
                    $bg_color = '#805AD5';
                    $text_color = '#ffffff';
                }
                
                if ($bg_color && $text_color) {
                    $style = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                    $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
                } else {
                    $style = '';
                    $data_attrs = ' data-color=""';
                }
                
                $medium_tags_html .= '<span class="cf7-medium-tag"' . $data_attrs . $style . '>' . esc_html($term->name) . '</span>';
            }
        } else {
            // Only show "no mediums" if we actually intended to clear the mediums (empty array)
            if (empty($medium_ids)) {
                $medium_tags_html = '<span class="cf7-no-mediums-text">' . __('No mediums selected', 'cf7-artist-submissions') . '</span>';
            } else {
                // If we had medium IDs but couldn't retrieve terms, try again or show error
                // Try to get terms one more time
                $updated_terms = get_the_terms($post_id, $taxonomy);
                if (!empty($updated_terms) && !is_wp_error($updated_terms)) {
                    foreach ($updated_terms as $term) {
                        $medium_names[] = $term->name;
                        $bg_color = get_term_meta($term->term_id, 'medium_color', true);
                        $text_color = get_term_meta($term->term_id, 'medium_text_color', true);
                        
                        if ($field_type === 'text_mediums' && !$bg_color) {
                            $bg_color = '#805AD5';
                            $text_color = '#ffffff';
                        }
                        
                        if ($bg_color && $text_color) {
                            $style = ' style="--medium-color: ' . esc_attr($bg_color) . '; --medium-text-color: ' . esc_attr($text_color) . ';"';
                            $data_attrs = ' data-color="' . esc_attr($bg_color) . '" data-text-color="' . esc_attr($text_color) . '"';
                        } else {
                            $style = '';
                            $data_attrs = ' data-color=""';
                        }
                        
                        $medium_tags_html .= '<span class="cf7-medium-tag"' . $data_attrs . $style . '>' . esc_html($term->name) . '</span>';
                    }
                } else {
                    $medium_tags_html = '<span class="cf7-mediums-error">Mediums saved but display error. Please refresh the page.</span>';
                }
            }
        }
        
        // Log the update
        $new_medium_names = implode(', ', $medium_names);
        $old_medium_names_str = implode(', ', $old_medium_names);
        do_action('cf7_artist_submission_field_updated', $post_id, $field_type, $old_medium_names_str, $new_medium_names);
        
        wp_send_json_success(array(
            'message' => 'Mediums saved successfully',
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
            $zip_downloader = new CF7_Artist_Submissions_ZIP_Downloader();
            $zip_downloader->download_submission_zip($submission_id);
            
        } catch (Exception $e) {
            wp_die(__('Error creating ZIP file: ', 'cf7-artist-submissions') . $e->getMessage());
        }
        
        exit;
    }

    /**
     * AJAX handler for downloading individual files
     */
    public static function ajax_download_file() {
        
        // Verify nonce
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'cf7as_download_file')) {
            wp_die(__('Security check failed', 'cf7-artist-submissions'));
        }

        $file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
        if (!$file_id) {
            wp_die(__('Invalid file ID', 'cf7-artist-submissions'));
        }

        global $wpdb;

        // Get file information directly from cf7as_files table
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cf7as_files WHERE id = %d",
            $file_id
        ));
        
        if (!$file) {
            wp_die(__('File not found', 'cf7-artist-submissions'));
        }
        
        // Check permissions for the specific submission
        $submission_id = intval($file->submission_id);
        if ($submission_id && !current_user_can('edit_post', $submission_id)) {
            wp_die(__('Insufficient permissions', 'cf7-artist-submissions'));
        } elseif (!$submission_id && !current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'cf7-artist-submissions'));
        }


        try {
            // Initialize S3 handler
            if (!class_exists('CF7_Artist_Submissions_S3_Handler')) {
                require_once plugin_dir_path(__FILE__) . 'class-s3-handler.php';
            }
            
            $s3_handler = new CF7_Artist_Submissions_S3_Handler();
            $init_result = $s3_handler->init();
            
            if (!$init_result) {
                wp_die(__('Error initializing S3 connection', 'cf7-artist-submissions'));
            }

            // Get the S3 key for the original file
            $s3_key = $file->s3_key ? $file->s3_key : $file->file_path; // fallback to file_path if s3_key is null
            
            if (empty($s3_key)) {
                wp_die(__('File path not found', 'cf7-artist-submissions'));
            }
            
            // Get presigned download URL for the original file
            $download_url = $s3_handler->get_presigned_download_url($s3_key, 300); // 5 minutes expiry
            
            if (!$download_url) {
                wp_die(__('Error generating download link', 'cf7-artist-submissions'));
            }

            // Log the download action
            if (class_exists('CF7_Artist_Submissions_Action_Log')) {
                CF7_Artist_Submissions_Action_Log::log_action(
                    $file->submission_id,
                    'file_downloaded',
                    array(
                        'file_id' => $file_id,
                        'file_name' => $file->original_name,
                        's3_key' => $s3_key,
                        'user_id' => get_current_user_id()
                    )
                );
            }

            // Redirect to the presigned URL for direct download from S3
            wp_redirect($download_url);
            exit;
            
        } catch (Exception $e) {
            wp_die(__('Error downloading file: ', 'cf7-artist-submissions') . $e->getMessage());
        }
    }
}
