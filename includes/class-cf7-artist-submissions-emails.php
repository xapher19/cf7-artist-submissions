<?php
/**
 * Email Management for CF7 Artist Submissions
 */
class CF7_Artist_Submissions_Emails {
    
    /**
     * Email templates loaded from options
     */
    private $templates = array();
    
    /**
     * Available email triggers
     */
    private $triggers = array();
    
    /**
     * Temporary storage for email override
     */
    private $temp_from_email = '';
    private $temp_from_name = '';
    
    /**
     * Initialize the email system
     */
    public function init() {
        // Load templates from database
        $this->templates = get_option('cf7_artist_submissions_email_templates', array());
        
        // Set up triggers
        $this->triggers = array(
            'submission_received' => array(
                'name' => __('Submission Received', 'cf7-artist-submissions'),
                'description' => __('Sent when a new submission is received', 'cf7-artist-submissions'),
                'auto' => true,
            ),
            'status_changed_to_selected' => array(
                'name' => __('Status Changed to Selected', 'cf7-artist-submissions'),
                'description' => __('Sent when an artist is selected', 'cf7-artist-submissions'),
                'auto' => false,
            ),
            'status_changed_to_reviewed' => array(
                'name' => __('Status Changed to Reviewed', 'cf7-artist-submissions'),
                'description' => __('Sent when a submission is marked as reviewed', 'cf7-artist-submissions'),
                'auto' => false,
            ),
            'custom_notification' => array(
                'name' => __('Custom Notification', 'cf7-artist-submissions'),
                'description' => __('A custom email that can be sent manually at any time', 'cf7-artist-submissions'),
                'auto' => false,
            )
        );
        
        // Register email settings page
        // Removed admin menu hook - settings now handled by main settings class
        // add_action('admin_menu', array($this, 'add_email_settings_page'));
        
        // Add meta box for email logs and manual sending
        add_action('add_meta_boxes', array($this, 'add_email_meta_boxes'));
        
        // Handle manual email sending and previews
        add_action('wp_ajax_cf7_send_manual_email', array($this, 'ajax_send_manual_email'));
        add_action('wp_ajax_cf7_preview_email', array($this, 'ajax_preview_email'));
        add_action('wp_ajax_cf7_preview_wc_template', array($this, 'ajax_preview_wc_template'));
        add_action('wp_ajax_cf7_test_imap', array($this, 'ajax_test_imap'));
        
        // Register for submission events
        add_action('cf7_artist_submission_created', array($this, 'trigger_submission_received'), 10, 1);
        add_action('cf7_artist_submission_status_changed', array($this, 'handle_status_change'), 10, 3);
        
        // Email settings
        // Email settings now handled by main settings class
        // add_action('admin_init', array($this, 'register_email_settings'));
    }
    
    /**
     * Register email templates settings
     */
    public function register_email_settings() {
        register_setting('cf7_artist_submissions_email_options', 'cf7_artist_submissions_email_options');
        register_setting('cf7_artist_submissions_email_templates', 'cf7_artist_submissions_email_templates');
        
        add_settings_section(
            'cf7_artist_submissions_email_settings',
            __('Email Settings', 'cf7-artist-submissions'),
            array($this, 'render_email_settings_section'),
            'cf7-artist-submissions-emails'
        );
        
        add_settings_field(
            'from_email',
            __('From Email', 'cf7-artist-submissions'),
            array($this, 'render_from_email_field'),
            'cf7-artist-submissions-emails',
            'cf7_artist_submissions_email_settings'
        );
        
        add_settings_field(
            'from_name',
            __('From Name', 'cf7-artist-submissions'),
            array($this, 'render_from_name_field'),
            'cf7-artist-submissions-emails',
            'cf7_artist_submissions_email_settings'
        );
        
        // Add WooCommerce email template option if WooCommerce is active
        if (class_exists('WooCommerce')) {
            add_settings_field(
                'use_wc_template',
                __('WooCommerce Email Template', 'cf7-artist-submissions'),
                array($this, 'render_wc_template_field'),
                'cf7-artist-submissions-emails',
                'cf7_artist_submissions_email_settings'
            );
        }
        
        // Add a section for each trigger to manage templates
        foreach ($this->triggers as $trigger_id => $trigger) {
            add_settings_section(
                'cf7_artist_submissions_email_template_' . $trigger_id,
                $trigger['name'],
                array($this, 'render_template_section'),
                'cf7-artist-submissions-email-templates'
            );
        }
    }
    
    /**
     * Add email settings page
     */
    public function add_email_settings_page() {
        add_submenu_page(
            'edit.php?post_type=cf7_submission',
            __('Email Settings', 'cf7-artist-submissions'),
            __('Email Settings', 'cf7-artist-submissions'),
            'manage_options',
            'cf7-artist-submissions-emails',
            array($this, 'render_email_settings_page')
        );
    }
    
    /**
     * Render email settings page
     */
    public function render_email_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we're in the templates tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-emails&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('General Settings', 'cf7-artist-submissions'); ?></a>
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-emails&tab=templates" class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>"><?php _e('Email Templates', 'cf7-artist-submissions'); ?></a>
            </h2>
            
            <form method="post" action="options.php">
                <?php if ($active_tab === 'settings'): ?>
                    <?php
                    settings_fields('cf7_artist_submissions_email_options');
                    do_settings_sections('cf7-artist-submissions-emails');
                    submit_button();
                    ?>
                <?php elseif ($active_tab === 'templates'): ?>
                    <?php
                    settings_fields('cf7_artist_submissions_email_templates');
                    do_settings_sections('cf7-artist-submissions-email-templates');
                    submit_button();
                    ?>
                    
                    <div class="cf7-artist-email-merge-tags">
                        <h3><?php _e('Available Merge Tags', 'cf7-artist-submissions'); ?></h3>
                        <p><?php _e('Use these tags in your email templates to include dynamic content:', 'cf7-artist-submissions'); ?></p>
                        <table class="widefat fixed">
                            <thead>
                                <tr>
                                    <th><?php _e('Tag', 'cf7-artist-submissions'); ?></th>
                                    <th><?php _e('Description', 'cf7-artist-submissions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{artist_name}</code></td>
                                    <td><?php _e('The name of the artist', 'cf7-artist-submissions'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>{email}</code></td>
                                    <td><?php _e('The email address of the artist', 'cf7-artist-submissions'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>{submission_date}</code></td>
                                    <td><?php _e('The date the submission was received', 'cf7-artist-submissions'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>{submission_id}</code></td>
                                    <td><?php _e('The ID of the submission', 'cf7-artist-submissions'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>{status}</code></td>
                                    <td><?php _e('The current status of the submission', 'cf7-artist-submissions'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>{site_name}</code></td>
                                    <td><?php _e('Your website name', 'cf7-artist-submissions'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p><em><?php _e('You can also use any custom field from the submission by using the format {field_name}', 'cf7-artist-submissions'); ?></em></p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render email settings section
     */
    public function render_email_settings_section() {
        echo '<p>' . __('Configure the default settings for all emails sent from the submissions system.', 'cf7-artist-submissions') . '</p>';
        
        // Check if WP Mail SMTP is active
        if (class_exists('WPMailSMTP\\WP')) {
            echo '<div class="notice notice-success inline"><p>' . __('WP Mail SMTP detected! Emails will be sent using your configured SMTP settings.', 'cf7-artist-submissions') . '</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p>' . __('WP Mail SMTP not detected. For reliable email delivery, we recommend installing the WP Mail SMTP plugin.', 'cf7-artist-submissions') . ' <a href="' . admin_url('plugin-install.php?s=wp+mail+smtp&tab=search&type=term') . '">' . __('Install Now', 'cf7-artist-submissions') . '</a></p></div>';
        }
    }
    
    /**
     * Render template section
     */
    public function render_template_section($args) {
        $section_id = $args['id'];
        $trigger_id = str_replace('cf7_artist_submissions_email_template_', '', $section_id);
        $trigger = $this->triggers[$trigger_id];
        
        echo '<p>' . esc_html($trigger['description']) . '</p>';
        
        // Get current template settings
        $templates = get_option('cf7_artist_submissions_email_templates', array());
        $template = isset($templates[$trigger_id]) ? $templates[$trigger_id] : array(
            'enabled' => false,
            'subject' => '',
            'body' => '',
            'auto_send' => $trigger['auto']
        );
        
        // Enable/disable toggle
        echo '<div class="cf7-template-field">';
        echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]">';
        echo '<input type="checkbox" id="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]" name="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]" value="1" ' . checked(1, $template['enabled'], false) . '>';
        echo ' ' . __('Enable this email template', 'cf7-artist-submissions');
        echo '</label>';
        echo '</div>';
        
        // Auto-send toggle (only if applicable)
        if ($trigger['auto'] !== null) {
            echo '<div class="cf7-template-field">';
            echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]">';
            echo '<input type="checkbox" id="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]" name="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]" value="1" ' . checked(1, $template['auto_send'], false) . ' ' . disabled($trigger['auto'], true, false) . '>';
            echo ' ' . __('Automatically send this email when triggered', 'cf7-artist-submissions');
            echo '</label>';
            echo '</div>';
        }
        
        // Subject field
        echo '<div class="cf7-template-field">';
        echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][subject]">' . __('Email Subject:', 'cf7-artist-submissions') . '</label>';
        echo '<input type="text" id="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][subject]" name="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][subject]" value="' . esc_attr($template['subject']) . '" class="large-text">';
        echo '</div>';
        
        // Body field
        echo '<div class="cf7-template-field">';
        echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][body]">' . __('Email Body:', 'cf7-artist-submissions') . '</label>';
        
        $content = $template['body'];
        if (empty($content)) {
            // Default template based on trigger
            switch ($trigger_id) {
                case 'submission_received':
                    $content = "Dear {artist_name},\n\nThank you for your submission. We have received your application and will review it shortly.\n\nRegards,\n{site_name} Team";
                    break;
                case 'status_changed_to_selected':
                    $content = "Dear {artist_name},\n\nCongratulations! We are pleased to inform you that your submission has been selected.\n\nRegards,\n{site_name} Team";
                    break;
                case 'status_changed_to_reviewed':
                    $content = "Dear {artist_name},\n\nThank you for your submission. We have completed our review process.\n\nRegards,\n{site_name} Team";
                    break;
                case 'custom_notification':
                    $content = "Dear {artist_name},\n\nThis is a custom notification regarding your submission.\n\nRegards,\n{site_name} Team";
                    break;
            }
        }
        
        // Use WordPress editor for the email body
        wp_editor(
            $content,
            'cf7_artist_submissions_email_templates_' . $trigger_id . '_body',
            array(
                'textarea_name' => 'cf7_artist_submissions_email_templates[' . $trigger_id . '][body]',
                'textarea_rows' => 10,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            )
        );
        echo '</div>';
    }
    
    /**
     * Render from email field
     */
    public function render_from_email_field() {
        $options = get_option('cf7_artist_submissions_email_options', array());
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        
        echo '<input type="email" id="cf7_artist_submissions_email_options[from_email]" name="cf7_artist_submissions_email_options[from_email]" value="' . esc_attr($from_email) . '" class="regular-text">';
        echo '<p class="description">' . __('The email address that emails will be sent from. Make sure this email is authorized in your SMTP provider settings.', 'cf7-artist-submissions') . '</p>';
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $options = get_option('cf7_artist_submissions_email_options', array());
        $from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        
        echo '<input type="text" id="cf7_artist_submissions_email_options[from_name]" name="cf7_artist_submissions_email_options[from_name]" value="' . esc_attr($from_name) . '" class="regular-text">';
        echo '<p class="description">' . __('The name that emails will be sent from (e.g. "Pup and Tiger").', 'cf7-artist-submissions') . '</p>';
    }
    
    /**
     * Render WooCommerce template field
     */
    public function render_wc_template_field() {
        $options = get_option('cf7_artist_submissions_email_options', array());
        $use_wc_template = isset($options['use_wc_template']) ? $options['use_wc_template'] : false;
        
        echo '<label>';
        echo '<input type="checkbox" id="cf7_artist_submissions_email_options[use_wc_template]" name="cf7_artist_submissions_email_options[use_wc_template]" value="1" ' . checked(1, $use_wc_template, false) . '>';
        echo ' ' . __('Use WooCommerce email template', 'cf7-artist-submissions');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, emails will be styled using the WooCommerce email template for consistent branding.', 'cf7-artist-submissions') . '</p>';
        
        // Preview of WooCommerce template
        echo '<div class="wc-template-preview" style="margin-top: 10px;">';
        echo '<a href="#" class="button" id="preview-wc-template">' . __('Preview WooCommerce Template', 'cf7-artist-submissions') . '</a>';
        echo '</div>';
        
        // Add preview modal
        echo '<div id="wc-template-preview-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:99999;">';
        echo '<div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:20px; max-width:800px; width:90%; max-height:80vh; overflow:auto; border-radius:5px;">';
        echo '<h3>' . __('WooCommerce Email Template Preview', 'cf7-artist-submissions') . '</h3>';
        echo '<div id="wc-template-preview-content" style="border:1px solid #ddd; padding:15px; margin:15px 0;"></div>';
        echo '<button type="button" class="button" id="close-wc-preview">' . __('Close Preview', 'cf7-artist-submissions') . '</button>';
        echo '</div>';
        echo '</div>';
        
        // Add script for preview
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#preview-wc-template').on('click', function(e) {
                    e.preventDefault();
                    
                    // Show loading
                    $('#wc-template-preview-content').html('<p>Loading preview...</p>');
                    $('#wc-template-preview-modal').show();
                    
                    // Load preview via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cf7_preview_wc_template',
                            nonce: '<?php echo wp_create_nonce('cf7_wc_template_preview_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#wc-template-preview-content').html(response.data.template);
                            } else {
                                $('#wc-template-preview-content').html('<p>Error loading preview: ' + response.data.message + '</p>');
                            }
                        },
                        error: function() {
                            $('#wc-template-preview-content').html('<p>Error loading preview.</p>');
                        }
                    });
                });
                
                $('#close-wc-preview').on('click', function() {
                    $('#wc-template-preview-modal').hide();
                });
            });
        </script>
        <?php
    }
    
    /**
     * Add email-related meta boxes to the submission editor
     */
    public function add_email_meta_boxes() {
        add_meta_box(
            'cf7_submission_emails',
            __('Email Management', 'cf7-artist-submissions'),
            array($this, 'render_email_meta_box'),
            'cf7_submission',
            'normal',
            'high'
        );
    }
    
    /**
     * Render the email meta box
     */
    public function render_email_meta_box($post) {
        // Get submission email data
        $artist_email = $this->get_submission_email($post->ID);
        
        // If no email found
        if (empty($artist_email)) {
            echo '<p>' . __('No email address found for this submission.', 'cf7-artist-submissions') . '</p>';
            return;
        }
        
        // Get email logs
        $action_logs = CF7_Artist_Submissions_Action_Log::get_logs_for_submission($post->ID, 'email_sent');
        
        // Email Sending Interface
        echo '<div class="cf7-email-sending-interface">';
        echo '<h3>' . __('Send Email', 'cf7-artist-submissions') . '</h3>';
        
        echo '<input type="hidden" id="cf7_email_submission_id" value="' . esc_attr($post->ID) . '">';
        echo '<input type="hidden" id="cf7_email_nonce" value="' . wp_create_nonce('cf7_send_email_nonce') . '">';
        
        echo '<div class="cf7-email-field">';
        echo '<label for="cf7_email_template">' . __('Email Template:', 'cf7-artist-submissions') . '</label>';
        echo '<select id="cf7_email_template" name="cf7_email_template">';
        
        foreach ($this->triggers as $trigger_id => $trigger) {
            // Get template data
            $templates = get_option('cf7_artist_submissions_email_templates', array());
            $template = isset($templates[$trigger_id]) ? $templates[$trigger_id] : array('enabled' => false);
            
            // Only show enabled templates
            if (isset($template['enabled']) && $template['enabled']) {
                echo '<option value="' . esc_attr($trigger_id) . '">' . esc_html($trigger['name']) . '</option>';
            }
        }
        
        echo '</select>';
        echo '</div>';
        
        echo '<div class="cf7-email-field">';
        echo '<label for="cf7_email_to">' . __('Recipient:', 'cf7-artist-submissions') . '</label>';
        echo '<input type="email" id="cf7_email_to" name="cf7_email_to" value="' . esc_attr($artist_email) . '" class="regular-text" readonly>';
        echo '</div>';
        
        echo '<div class="cf7-email-actions">';
        echo '<button type="button" id="cf7_preview_email" class="button">' . __('Preview Email', 'cf7-artist-submissions') . '</button>';
        echo '<button type="button" id="cf7_send_email" class="button button-primary">' . __('Send Email', 'cf7-artist-submissions') . '</button>';
        echo '<span class="spinner"></span>';
        echo '<div id="cf7_email_message" class="notice" style="display:none;"></div>';
        echo '</div>';
        
        echo '</div>'; // End email sending interface
        
        // Email Preview Modal
        echo '<div id="cf7-email-preview-modal" style="display:none;">';
        echo '<div class="cf7-email-preview-content">';
        echo '<h3>' . __('Email Preview', 'cf7-artist-submissions') . '</h3>';
        echo '<div class="cf7-email-preview-subject"></div>';
        echo '<div class="cf7-email-preview-body"></div>';
        echo '<div class="cf7-email-preview-actions">';
        echo '<button type="button" class="button cf7-close-preview">' . __('Close', 'cf7-artist-submissions') . '</button>';
        echo '<button type="button" class="button button-primary cf7-send-from-preview">' . __('Send Email', 'cf7-artist-submissions') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Email Log
        echo '<div class="cf7-email-log">';
        echo '<h3>' . __('Email Log', 'cf7-artist-submissions') . '</h3>';
        
        if (empty($action_logs)) {
            echo '<p>' . __('No emails have been sent for this submission.', 'cf7-artist-submissions') . '</p>';
        } else {
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Date & Time', 'cf7-artist-submissions') . '</th>';
            echo '<th>' . __('Template', 'cf7-artist-submissions') . '</th>';
            echo '<th>' . __('Subject', 'cf7-artist-submissions') . '</th>';
            echo '<th>' . __('Sent By', 'cf7-artist-submissions') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($action_logs as $log) {
                $log_data = json_decode($log->data, true);
                
                echo '<tr>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->date_created))) . '</td>';
                echo '<td>' . esc_html($log_data['template_name'] ?? $log_data['template_id']) . '</td>';
                echo '<td>' . esc_html($log_data['subject']) . '</td>';
                
                $user = get_user_by('id', $log->user_id);
                echo '<td>' . esc_html($user ? $user->display_name : __('System', 'cf7-artist-submissions')) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        echo '</div>'; // End email log
        
        // Add script for email preview and sending
        $this->add_email_scripts();
    }
    
    /**
     * Add scripts for email handling
     */
    private function add_email_scripts() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Preview email
                $('#cf7_preview_email').on('click', function() {
                    const templateId = $('#cf7_email_template').val();
                    const submissionId = $('#cf7_email_submission_id').val();
                    
                    // Show loading state
                    $(this).prop('disabled', true);
                    
                    // Make AJAX request to preview email
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cf7_preview_email',
                            template_id: templateId,
                            submission_id: submissionId,
                            nonce: $('#cf7_email_nonce').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                // Populate the preview modal
                                $('.cf7-email-preview-subject').html('<strong>Subject:</strong> ' + response.data.subject);
                                $('.cf7-email-preview-body').html(response.data.body);
                                
                                // Show the modal
                                $('#cf7-email-preview-modal').fadeIn();
                            } else {
                                // Show error message
                                $('#cf7_email_message')
                                    .removeClass('notice-success')
                                    .addClass('notice-error')
                                    .html('<p>' + response.data.message + '</p>')
                                    .show();
                            }
                            
                            // Reset button state
                            $('#cf7_preview_email').prop('disabled', false);
                        },
                        error: function() {
                            // Show generic error
                            $('#cf7_email_message')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .html('<p>Error generating preview.</p>')
                                .show();
                                
                            // Reset button state
                            $('#cf7_preview_email').prop('disabled', false);
                        }
                    });
                });
                
                // Close preview modal
                $('.cf7-close-preview').on('click', function() {
                    $('#cf7-email-preview-modal').fadeOut();
                });
                
                // Send email
                $('#cf7_send_email, .cf7-send-from-preview').on('click', function() {
                    const templateId = $('#cf7_email_template').val();
                    const submissionId = $('#cf7_email_submission_id').val();
                    const recipient = $('#cf7_email_to').val();
                    
                    // Show loading state
                    $('.spinner').addClass('is-active');
                    $('#cf7_send_email, #cf7_preview_email, .cf7-send-from-preview').prop('disabled', true);
                    
                    // Close modal if sending from preview
                    if ($(this).hasClass('cf7-send-from-preview')) {
                        $('#cf7-email-preview-modal').fadeOut();
                    }
                    
                    // Send the email via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cf7_send_manual_email',
                            template_id: templateId,
                            submission_id: submissionId,
                            recipient: recipient,
                            nonce: $('#cf7_email_nonce').val()
                        },
                        success: function(response) {
                            // Hide spinner
                            $('.spinner').removeClass('is-active');
                            
                            if (response.success) {
                                // Show success message
                                $('#cf7_email_message')
                                    .removeClass('notice-error')
                                    .addClass('notice-success')
                                    .html('<p>' + response.data.message + '</p>')
                                    .show();
                                
                                // Reload page after longer delay to ensure log is written
                                setTimeout(function() {
                                    window.location.reload(true); // Force reload from server, not cache
                                }, 3000);
                            } else {
                                // Show error message
                                $('#cf7_email_message')
                                    .removeClass('notice-success')
                                    .addClass('notice-error')
                                    .html('<p>' + response.data.message + '</p>')
                                    .show();
                                
                                // Re-enable buttons
                                $('#cf7_send_email, #cf7_preview_email').prop('disabled', false);
                            }
                        },
                        error: function() {
                            // Hide spinner
                            $('.spinner').removeClass('is-active');
                            
                            // Show generic error
                            $('#cf7_email_message')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .html('<p>Error sending email.</p>')
                                .show();
                            
                            // Re-enable buttons
                            $('#cf7_send_email, #cf7_preview_email').prop('disabled', false);
                        }
                    });
                });
            });
        </script>
        <style type="text/css">
            .cf7-email-sending-interface {
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
            }
            .cf7-email-field {
                margin-bottom: 15px;
            }
            .cf7-email-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .cf7-email-actions {
                display: flex;
                align-items: center;
            }
            .cf7-email-actions .spinner {
                float: none;
                margin-left: 10px;
            }
            .cf7-email-actions button {
                margin-right: 10px;
            }
            #cf7_email_message {
                margin: 10px 0 0 0;
                padding: 5px 10px;
            }
            #cf7-email-preview-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                z-index: 99999;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .cf7-email-preview-content {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                width: 80%;
                max-width: 800px;
                max-height: 80vh;
                overflow-y: auto;
            }
            .cf7-email-preview-subject {
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .cf7-email-preview-body {
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f8f8;
                border: 1px solid #eee;
                border-radius: 3px;
            }
            .cf7-email-preview-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 15px;
                gap: 10px;
            }
            .cf7-email-log {
                margin-top: 20px;
            }
        </style>
        <?php
    }
    
    /**
     * Handle AJAX request to preview an email
     */
    public function ajax_preview_email() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_send_email_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check required data
        if (!isset($_POST['template_id']) || !isset($_POST['submission_id'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
            return;
        }
        
        try {
            $template_id = sanitize_text_field($_POST['template_id']);
            $submission_id = intval($_POST['submission_id']);
            
            // Get template
            $templates = get_option('cf7_artist_submissions_email_templates', array());
            if (!isset($templates[$template_id])) {
                wp_send_json_error(array('message' => 'Template not found'));
                return;
            }
            
            $template = $templates[$template_id];
            
            // Process merge tags
            $subject = $this->process_merge_tags($template['subject'], $submission_id);
            $body = $this->process_merge_tags($template['body'], $submission_id);
            
            // Convert line breaks to HTML in the body
            $body = wpautop($body);
            
            // Check if we should use WooCommerce template
            $options = get_option('cf7_artist_submissions_email_options', array());
            $use_wc_template = isset($options['use_wc_template']) && $options['use_wc_template'] && class_exists('WooCommerce');
            
            // Apply WooCommerce template if needed
            if ($use_wc_template) {
                // Make sure WooCommerce is properly initialized
                require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
                require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
                
                $wc_emails = new WC_Emails();
                $wc_emails->init();
                
                // Create a generic WC_Email object to access template methods
                $email = new WC_Email();
                
                // Get the formatted email
                $body = $this->format_woocommerce_email($body, $subject, $email);
            }
            
            wp_send_json_success(array(
                'subject' => $subject,
                'body' => $body
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Format email content using WooCommerce template
     */
    public function format_woocommerce_email($content, $heading, $email) {
        // Get the email template content
        ob_start();
        
        // Include the WooCommerce email header
        wc_get_template('emails/email-header.php', array('email_heading' => $heading));
        
        // Add our content
        echo $content;
        
        // Include the WooCommerce email footer
        wc_get_template('emails/email-footer.php');
        
        // Get the complete email template
        $formatted_email = ob_get_clean();
        
        // Apply WooCommerce inline styles
        $formatted_email = $email->style_inline($formatted_email);
        
        return $formatted_email;
    }
    
    /**
     * Handle AJAX request to preview WooCommerce template
     */
    public function ajax_preview_wc_template() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_wc_template_preview_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        try {
            // Only proceed if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array('message' => 'WooCommerce is not active'));
                return;
            }
            
            // Make sure WooCommerce is properly initialized
            require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
            require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
            
            $wc_emails = new WC_Emails();
            $wc_emails->init();
            
            // Create a generic WC_Email object to access template methods
            $email = new WC_Email();
            
            // Get the sample content
            $sample_content = '<p>This is a sample email content that would be sent to artists.</p>
                             <p>The content you write in your email templates will be styled using the WooCommerce email template, maintaining consistent branding across all emails from your site.</p>
                             <p>Any formatting, links, and content you add will be preserved and styled according to your WooCommerce email settings.</p>';
            
            // Format sample content with WooCommerce template
            $email_heading = 'Sample WooCommerce Email Template';
            $formatted_email = $this->format_woocommerce_email($sample_content, $email_heading, $email);
            
            wp_send_json_success(array('template' => $formatted_email));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Handle AJAX request to send manual email
     */
    public function ajax_send_manual_email() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf7_send_email_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check required data
        if (!isset($_POST['template_id']) || !isset($_POST['submission_id']) || !isset($_POST['recipient'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
            return;
        }
        
        $template_id = sanitize_text_field($_POST['template_id']);
        $submission_id = intval($_POST['submission_id']);
        $recipient = sanitize_email($_POST['recipient']);
        
        // Validate email
        if (!is_email($recipient)) {
            wp_send_json_error(array('message' => 'Invalid recipient email address'));
            return;
        }
        
        // Test action logging before sending email
        $test_result = CF7_Artist_Submissions_Action_Log::test_logging();
        if (is_wp_error($test_result)) {
            wp_send_json_error(array('message' => 'Logging system not working: ' . $test_result->get_error_message()));
            return;
        }
        
        // Send the email
        $result = $this->send_email($template_id, $submission_id, $recipient);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => 'Email sent successfully'));
        }
    }
    
    /**
     * Trigger 'submission received' email
     */
    public function trigger_submission_received($submission_id) {
        // Get template
        $templates = get_option('cf7_artist_submissions_email_templates', array());
        if (!isset($templates['submission_received']) || !$templates['submission_received']['enabled']) {
            return;
        }
        
        $template = $templates['submission_received'];
        
        // Only send if auto-send is enabled
        if (!isset($template['auto_send']) || !$template['auto_send']) {
            return;
        }
        
        // Get recipient email
        $recipient = $this->get_submission_email($submission_id);
        if (empty($recipient)) {
            return;
        }
        
        // Send the email
        $this->send_email('submission_received', $submission_id, $recipient);
    }
    
    /**
     * Handle status change
     */
    public function handle_status_change($submission_id, $new_status, $old_status) {
        // For status changes, we don't auto-send emails
        // They should be sent manually to avoid accidental notifications
        
        // The UI will allow manually sending emails based on status changes
    }
    
    /**
     * Send an email using a template
     */
    public function send_email($template_id, $submission_id, $to_email) {
        // Get template
        $templates = get_option('cf7_artist_submissions_email_templates', array());
        if (!isset($templates[$template_id])) {
            return new WP_Error('invalid_template', 'Email template not found');
        }
        
        $template = $templates[$template_id];
        if (!$template['enabled']) {
            return new WP_Error('template_disabled', 'Email template is disabled');
        }
        
        // Get email settings
        $options = get_option('cf7_artist_submissions_email_options', array());
        $from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        
        // Process merge tags
        $subject = $this->process_merge_tags($template['subject'], $submission_id);
        $body = $this->process_merge_tags($template['body'], $submission_id);
        
        // Convert line breaks to HTML in the body
        $body = wpautop($body);
        
        // Check if we should use WooCommerce template
        $use_wc_template = isset($options['use_wc_template']) && $options['use_wc_template'] && class_exists('WooCommerce');
        
        try {
            // Apply WooCommerce template if needed
            if ($use_wc_template) {
                // Make sure WooCommerce is properly initialized
                require_once(WC_ABSPATH . 'includes/class-wc-emails.php');
                require_once(WC_ABSPATH . 'includes/emails/class-wc-email.php');
                
                $wc_emails = new WC_Emails();
                $wc_emails->init();
                
                // Create a generic WC_Email object to access template methods
                $email = new WC_Email();
                
                // Format email with WooCommerce template
                $body = $this->format_woocommerce_email($body, $subject, $email);
            }
            
            // Get email settings
            $options = get_option('cf7_artist_submissions_email_options', array());
            $from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
            $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
            
            // Apply WordPress filters to override from address (SMTP2GO should respect these)
            $this->temp_from_email = $from_email;
            $this->temp_from_name = $from_name;
            add_filter('wp_mail_from', array($this, 'override_from_email'), 10);
            add_filter('wp_mail_from_name', array($this, 'override_from_name'), 10);
            
            // Generate reply token for conversation threading
            $reply_token = CF7_Artist_Submissions_Conversations::generate_reply_token($submission_id);
            
            // Get IMAP settings for reply-to address (this is the email we'll monitor for replies)
            $imap_options = get_option('cf7_artist_submissions_imap_options', array());
            $imap_email = isset($imap_options['username']) ? $imap_options['username'] : $from_email;
            
            // Create reply-to address using plus addressing with IMAP email for conversation threading
            $email_parts = explode('@', $imap_email);
            $local_part = $email_parts[0];
            $domain_part = isset($email_parts[1]) ? $email_parts[1] : 'example.com';
            $reply_to_email = $local_part . '+SUB' . $submission_id . '_' . $reply_token . '@' . $domain_part;
            
            // Set up headers with conversation threading reply-to
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Reply-To: <' . $reply_to_email . '>'
            );
            
            // Send the email
            $mail_sent = wp_mail($to_email, $subject, $body, $headers);
            
            // Remove our temporary filters
            remove_filter('wp_mail_from', array($this, 'override_from_email'), 10);
            remove_filter('wp_mail_from_name', array($this, 'override_from_name'), 10);
            
            // Log the email sent
            if ($mail_sent) {
                // Get template name for logging
                $template_name = isset($this->triggers[$template_id]) ? $this->triggers[$template_id]['name'] : $template_id;
                
                // Create log data
                $log_data = array(
                    'template_id' => $template_id,
                    'template_name' => $template_name,
                    'recipient' => $to_email,
                    'subject' => $subject
                );
                
                // Log the email action
                $log_result = CF7_Artist_Submissions_Action_Log::log_action(
                    $submission_id,
                    'email_sent',
                    json_encode($log_data)
                );
                
                return true;
            } else {
                return new WP_Error('email_failed', 'Failed to send email');
            }
        } catch (Exception $e) {
            return new WP_Error('email_error', $e->getMessage());
        }
    }
    
    /**
     * Process merge tags in content
     */
    public function process_merge_tags($content, $submission_id) {
        // Get submission data
        $submission = get_post($submission_id);
        if (!$submission) {
            return $content;
        }
        
        // Get status
        $status_terms = wp_get_object_terms($submission_id, 'submission_status');
        $status = !empty($status_terms) ? $status_terms[0]->name : 'New';
        
        // Basic merge tags
        $merge_tags = array(
            '{artist_name}' => $submission->post_title,
            '{submission_date}' => get_post_meta($submission_id, 'cf7_submission_date', true),
            '{submission_id}' => $submission_id,
            '{status}' => $status,
            '{site_name}' => get_bloginfo('name')
        );
        
        // Add email merge tag
        $email = $this->get_submission_email($submission_id);
        if ($email) {
            $merge_tags['{email}'] = $email;
        }
        
        // Get all custom fields
        $meta_keys = get_post_custom_keys($submission_id);
        if (!empty($meta_keys)) {
            foreach ($meta_keys as $key) {
                // Skip internal meta
                if (substr($key, 0, 1) === '_' || 
                    substr($key, 0, 8) === 'cf7_file_' || 
                    $key === 'cf7_submission_date' || 
                    $key === 'cf7_curator_notes') {
                    continue;
                }
                
                $value = get_post_meta($submission_id, $key, true);
                if (is_string($value)) {
                    // Create merge tag without cf7_ prefix
                    $tag_key = substr($key, 4); // Remove cf7_ prefix
                    $merge_tags['{' . $tag_key . '}'] = $value;
                }
            }
        }
        
        // Replace all merge tags
        return str_replace(array_keys($merge_tags), array_values($merge_tags), $content);
    }
    
    /**
     * Get submission email
     */
    public function get_submission_email($submission_id) {
        // Try to get email from meta fields
        $email_fields = array('cf7_email', 'cf7_your-email', 'cf7_user_email');
        
        foreach ($email_fields as $field) {
            $email = get_post_meta($submission_id, $field, true);
            if (!empty($email) && is_email($email)) {
                return $email;
            }
        }
        
        // Try to find any field containing 'email'
        $meta_keys = get_post_custom_keys($submission_id);
        if (!empty($meta_keys)) {
            foreach ($meta_keys as $key) {
                if (strpos($key, 'email') !== false) {
                    $email = get_post_meta($submission_id, $key, true);
                    if (!empty($email) && is_email($email)) {
                        return $email;
                    }
                }
            }
        }
        
        return '';
    }
    
    /**
     * Temporarily override wp_mail from email
     */
    public function override_from_email($from_email) {
        return $this->temp_from_email;
    }
    
    /**
     * Temporarily override wp_mail from name
     */
    public function override_from_name($from_name) {
        return $this->temp_from_name;
    }
    
    /**
     * Override PHPMailer from settings directly (for plugins that bypass wp_mail filters)
     */
    public function override_phpmailer_from($phpmailer) {
        if (!empty($this->temp_from_email)) {
            try {
                $phpmailer->setFrom($this->temp_from_email, $this->temp_from_name);
            } catch (Exception $e) {
                // If setFrom fails, just continue - wp_mail will handle the error
            }
        }
    }
    
    /**
     * Override WP Mail SMTP specific options
     */
    public function override_wp_mail_smtp_options($options) {
        if (!empty($this->temp_from_email)) {
            $options['mail']['from_email'] = $this->temp_from_email;
            $options['mail']['from_name'] = $this->temp_from_name;
        }
        return $options;
    }
    
    /**
     * AJAX handler for testing IMAP connection
     */
    public function ajax_test_imap() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_conversation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $options = get_option('cf7_artist_submissions_imap_options', array());
        
        if (empty($options['server']) || empty($options['username']) || empty($options['password'])) {
            wp_send_json_error(array('message' => 'IMAP settings incomplete. Please fill in all required fields.'));
            return;
        }
        
        // Test IMAP connection
        $server = $options['server'];
        $port = $options['port'];
        $username = $options['username'];
        $password = $options['password'];
        $encryption = $options['encryption'];
        
        // Build connection string
        $connection_string = '{' . $server . ':' . $port;
        if ($encryption === 'ssl') {
            $connection_string .= '/ssl';
        } elseif ($encryption === 'tls') {
            $connection_string .= '/tls';
        }
        $connection_string .= '}INBOX';
        
        try {
            if (!function_exists('imap_open')) {
                wp_send_json_error(array('message' => 'IMAP extension not available. Please enable PHP IMAP extension.'));
                return;
            }
            
            // Try to connect
            $connection = @imap_open($connection_string, $username, $password);
            
            if ($connection === false) {
                $error = imap_last_error();
                wp_send_json_error(array('message' => 'IMAP connection failed: ' . $error));
                return;
            }
            
            // Test successful - get mailbox info
            $mailbox_info = imap_status($connection, $connection_string, SA_ALL);
            imap_close($connection);
            
            wp_send_json_success(array(
                'message' => 'IMAP connection successful!',
                'details' => array(
                    'messages' => $mailbox_info->messages,
                    'recent' => $mailbox_info->recent,
                    'unseen' => $mailbox_info->unseen
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'IMAP test failed: ' . $e->getMessage()));
        }
    }
}