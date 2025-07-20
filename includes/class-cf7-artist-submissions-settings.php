<?php
/**
 * Settings Page for CF7 Artist Submissions
 */
class CF7_Artist_Submissions_Settings {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'settings_notice'));
        
        // AJAX handlers for daily summary testing
        add_action('wp_ajax_test_daily_summary', array($this, 'ajax_test_daily_summary'));
        add_action('wp_ajax_setup_daily_cron', array($this, 'ajax_setup_daily_cron'));
        add_action('wp_ajax_clear_daily_cron', array($this, 'ajax_clear_daily_cron'));
        add_action('wp_ajax_update_actions_schema', array($this, 'ajax_update_actions_schema'));
        
        // AJAX handlers for email debugging
        add_action('wp_ajax_validate_email_config', array($this, 'ajax_validate_email_config'));
        add_action('wp_ajax_test_smtp_config', array($this, 'ajax_test_smtp_config'));
    }
    
    public function add_settings_page() {
        // Add as submenu under Submissions instead of under Settings
        add_submenu_page(
            'edit.php?post_type=cf7_submission',  // Parent slug
            __('CF7 Submissions Settings', 'cf7-artist-submissions'),
            __('Settings', 'cf7-artist-submissions'),
            'manage_options',
            'cf7-artist-submissions-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function settings_notice() {
        $screen = get_current_screen();
        
        // Only show on plugins page or submissions post type screen
        if (!$screen || (!in_array($screen->id, array('plugins')) && $screen->post_type !== 'cf7_submission')) {
            return;
        }
        
        $options = get_option('cf7_artist_submissions_options', array());
        if (empty($options['form_id'])) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php _e('CF7 Artist Submissions is active but no form has been selected yet.', 'cf7-artist-submissions'); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=cf7_submission&page=cf7-artist-submissions-settings'); ?>">
                        <?php _e('Configure settings now', 'cf7-artist-submissions'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    public function register_settings() {
        register_setting('cf7_artist_submissions_options', 'cf7_artist_submissions_options', array($this, 'validate_options'));
        register_setting('cf7_artist_submissions_email_options', 'cf7_artist_submissions_email_options', array($this, 'validate_email_options'));
        register_setting('cf7_artist_submissions_email_templates', 'cf7_artist_submissions_email_templates', array($this, 'validate_email_templates'));
        register_setting('cf7_artist_submissions_imap_options', 'cf7_artist_submissions_imap_options', array($this, 'validate_imap_options'));
        
        add_settings_section(
            'cf7_artist_submissions_main',
            __('Main Settings', 'cf7-artist-submissions'),
            array($this, 'render_main_section'),
            'cf7-artist-submissions'
        );
        
        add_settings_field(
            'form_id',
            __('Contact Form 7 ID', 'cf7-artist-submissions'),
            array($this, 'render_form_id_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_main'
        );
        
        add_settings_field(
            'menu_label',
            __('Menu Label', 'cf7-artist-submissions'),
            array($this, 'render_menu_label_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_main'
        );
        
        add_settings_field(
            'store_files',
            __('Store Uploaded Files', 'cf7-artist-submissions'),
            array($this, 'render_store_files_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_main'
        );
        
        // IMAP Settings Section
        add_settings_section(
            'cf7_artist_submissions_imap',
            __('IMAP Settings (for Email Conversations)', 'cf7-artist-submissions'),
            array($this, 'render_imap_section'),
            'cf7-artist-submissions'
        );
        
        add_settings_field(
            'imap_server',
            __('IMAP Server', 'cf7-artist-submissions'),
            array($this, 'render_imap_server_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
        
        add_settings_field(
            'imap_port',
            __('IMAP Port', 'cf7-artist-submissions'),
            array($this, 'render_imap_port_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
        
        add_settings_field(
            'imap_username',
            __('IMAP Username', 'cf7-artist-submissions'),
            array($this, 'render_imap_username_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
        
        add_settings_field(
            'imap_password',
            __('IMAP Password', 'cf7-artist-submissions'),
            array($this, 'render_imap_password_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
        
        add_settings_field(
            'imap_encryption',
            __('IMAP Encryption', 'cf7-artist-submissions'),
            array($this, 'render_imap_encryption_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
        
        add_settings_field(
            'imap_delete_processed',
            __('Delete Processed Emails', 'cf7-artist-submissions'),
            array($this, 'render_imap_delete_processed_field'),
            'cf7-artist-submissions',
            'cf7_artist_submissions_imap'
        );
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        
        // Get available forms
        $forms = array();
        if (class_exists('WPCF7_ContactForm')) {
            $cf7_forms = WPCF7_ContactForm::find();
            foreach ($cf7_forms as $form) {
                $forms[$form->id()] = $form->title();
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=general" 
                   class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General Settings', 'cf7-artist-submissions'); ?>
                </a>
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=email" 
                   class="nav-tab <?php echo $current_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email Settings', 'cf7-artist-submissions'); ?>
                </a>
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=templates" 
                   class="nav-tab <?php echo $current_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email Templates', 'cf7-artist-submissions'); ?>
                </a>
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=imap" 
                   class="nav-tab <?php echo $current_tab === 'imap' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('IMAP Settings', 'cf7-artist-submissions'); ?>
                </a>
                <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=debug" 
                   class="nav-tab <?php echo $current_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Debug', 'cf7-artist-submissions'); ?>
                </a>
            </nav>
            
            <!-- Tab Content -->
            <?php if ($current_tab === 'general'): ?>
                
                <?php if (empty($forms)): ?>
                    <div class="notice notice-error">
                        <p>
                            <?php _e('No Contact Form 7 forms found. Please create at least one form first.', 'cf7-artist-submissions'); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('cf7_artist_submissions_options');
                        ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Contact Form 7 ID', 'cf7-artist-submissions'); ?></th>
                                <td>
                                    <?php $this->render_form_id_field(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Menu Label', 'cf7-artist-submissions'); ?></th>
                                <td>
                                    <?php $this->render_menu_label_field(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Store Uploaded Files', 'cf7-artist-submissions'); ?></th>
                                <td>
                                    <?php $this->render_store_files_field(); ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                    
                    <?php
                    $options = get_option('cf7_artist_submissions_options', array());
                    if (!empty($options['form_id'])):
                        $form_id = $options['form_id'];
                        $form_title = isset($forms[$form_id]) ? $forms[$form_id] : '';
                    ?>
                    <div class="cf7-artist-current-form" style="margin-top: 30px;">
                        <h2><?php _e('Currently Tracking Form', 'cf7-artist-submissions'); ?></h2>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Form ID', 'cf7-artist-submissions'); ?></th>
                                    <th><?php _e('Form Title', 'cf7-artist-submissions'); ?></th>
                                    <th><?php _e('Actions', 'cf7-artist-submissions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo esc_html($form_id); ?></td>
                                    <td><?php echo esc_html($form_title); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wpcf7&post=' . $form_id . '&action=edit'); ?>" class="button">
                                            <?php _e('Edit Form', 'cf7-artist-submissions'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('edit.php?post_type=cf7_submission'); ?>" class="button">
                                            <?php _e('View Submissions', 'cf7-artist-submissions'); ?>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif ($current_tab === 'email'): ?>
                
                <h2><?php _e('Email Configuration', 'cf7-artist-submissions'); ?></h2>
                <p><?php _e('Configure email settings for sending notifications and managing conversations with artists.', 'cf7-artist-submissions'); ?></p>
                
                <?php
                // Check if functions exist to prevent critical errors
                if (function_exists('settings_fields') && function_exists('submit_button')):
                ?>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('cf7_artist_submissions_email_options');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('From Email Address', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_from_email_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('From Name', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_from_name_field(); ?>
                            </td>
                        </tr>
                        <?php if (class_exists('WooCommerce')): ?>
                        <tr>
                            <th scope="row"><?php _e('WooCommerce Email Template', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_wc_template_field(); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <?php submit_button(__('Save Email Settings', 'cf7-artist-submissions')); ?>
                </form>
                
                <!-- Daily Summary Email Testing -->
                <div class="card" style="margin-top: 30px;">
                    <h3><?php _e('Daily Summary Email Testing', 'cf7-artist-submissions'); ?></h3>
                    <p><?php _e('Test the daily summary email functionality and WooCommerce template integration.', 'cf7-artist-submissions'); ?></p>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="send-test-summary-email" class="button button-secondary">
                            <?php _e('Send Test Summary Email to Current User', 'cf7-artist-submissions'); ?>
                        </button>
                        <div id="test-summary-result" style="margin-top: 10px;"></div>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="send-summary-to-all" class="button button-primary">
                            <?php _e('Send Daily Summary to All Users', 'cf7-artist-submissions'); ?>
                        </button>
                        <div id="summary-all-result" style="margin-top: 10px;"></div>
                    </div>
                    
                    <p class="description">
                        <?php _e('The test email will use your current email settings and will include any pending actions assigned to you. The summary will respect the WooCommerce template setting if enabled.', 'cf7-artist-submissions'); ?>
                    </p>
                </div>
                <?php else: ?>
                    <div class="notice notice-error">
                        <p><?php _e('Settings functions not available. Please ensure WordPress is properly loaded.', 'cf7-artist-submissions'); ?></p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($current_tab === 'templates'): ?>
                
                <h2><?php _e('Email Templates', 'cf7-artist-submissions'); ?></h2>
                <p><?php _e('Configure email templates that will be sent to artists for various events and status changes.', 'cf7-artist-submissions'); ?></p>
                
                <form action="options.php" method="post">
                    <?php
                    settings_fields('cf7_artist_submissions_email_templates');
                    ?>
                    
                    <?php $this->render_email_templates(); ?>
                    
                    <?php submit_button(__('Save Email Templates', 'cf7-artist-submissions')); ?>
                </form>
                
                <!-- Merge Tags Reference -->
                <div class="cf7-artist-email-merge-tags" style="margin-top: 30px;">
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
                
            <?php elseif ($current_tab === 'imap'): ?>
                
                <h2><?php _e('IMAP Configuration for Email Conversations', 'cf7-artist-submissions'); ?></h2>
                <p><?php _e('Configure these settings to enable two-way email conversations with artists using plus addressing with your existing email address.', 'cf7-artist-submissions'); ?></p>
                <p class="description"><?php _e('Uses your single email address with plus addressing (e.g., your-email+SUB123@domain.com). No extra email accounts or forwarding needed!', 'cf7-artist-submissions'); ?></p>
                
                <form action="options.php" method="post">
                    <?php
                    settings_fields('cf7_artist_submissions_imap_options');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('IMAP Server', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_server_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('IMAP Port', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_port_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('IMAP Username', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_username_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('IMAP Password', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_password_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('IMAP Encryption', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_encryption_field(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Delete Processed Emails', 'cf7-artist-submissions'); ?></th>
                            <td>
                                <?php $this->render_imap_delete_processed_field(); ?>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Save IMAP Settings', 'cf7-artist-submissions')); ?>
                </form>
                
                <!-- Test Connection Section -->
                <div style="margin-top: 30px;">
                    <h3><?php _e('Test IMAP Connection', 'cf7-artist-submissions'); ?></h3>
                    <p><?php _e('Use this button to test your IMAP connection settings:', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="test-imap-connection" class="button">
                        <?php _e('Test Connection', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="imap-test-result" style="margin-top: 10px;"></div>
                </div>
                
            <?php elseif ($current_tab === 'debug'): ?>
                
                <h2><?php _e('Debug Information', 'cf7-artist-submissions'); ?></h2>
                <p><?php _e('Debug information for troubleshooting IMAP and conversation issues.', 'cf7-artist-submissions'); ?></p>
                
                <div class="card">
                    <h3><?php _e('IMAP Status', 'cf7-artist-submissions'); ?></h3>
                    
                    <?php 
                    $imap_settings = get_option('cf7_artist_submissions_imap_options', array());
                    $last_check = get_option('cf7_last_imap_check', '');
                    
                    echo '<h4>IMAP Configuration:</h4>';
                    echo '<pre>';
                    if (!empty($imap_settings)) {
                        $safe_settings = $imap_settings;
                        if (isset($safe_settings['password'])) {
                            $safe_settings['password'] = str_repeat('*', strlen($safe_settings['password']));
                        }
                        print_r($safe_settings);
                    } else {
                        echo 'No IMAP settings found.';
                    }
                    echo '</pre>';
                    
                    echo '<h4>Last IMAP Check:</h4>';
                    echo '<p>' . ($last_check ? $last_check : 'Never checked') . '</p>';
                    
                    echo '<h4>PHP IMAP Extension:</h4>';
                    echo '<p>' . (extension_loaded('imap') ? 'Available' : 'NOT AVAILABLE - This is required for IMAP functionality') . '</p>';
                    ?>
                    
                    <h4><?php _e('Manual IMAP Check', 'cf7-artist-submissions'); ?></h4>
                    <button type="button" id="debug-check-imap" class="button button-secondary">
                        <?php _e('Check IMAP Now', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="debug-imap-result" style="margin-top: 10px;"></div>
                    
                    <h4><?php _e('Debug Inbox Contents', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('This will show you the last 10 emails in your inbox with details about whether they match the expected format.', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="debug-inbox" class="button button-secondary">
                        <?php _e('Debug Inbox', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="debug-inbox-result" style="margin-top: 10px;"></div>
                    
                    <h4><?php _e('Clean Up IMAP Inbox', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('This will scan all emails on the IMAP server and delete any that have already been processed and stored in the database. It will also delete orphaned emails from deleted submissions. Use this to clean up your inbox manually.', 'cf7-artist-submissions'); ?></p>
                    <p class="description" style="color: #d63384;"><?php _e('⚠️ Warning: This will permanently delete processed emails and orphaned emails from your IMAP server. Make sure you have backups if needed.', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="cleanup-imap-inbox" class="button button-secondary">
                        <?php _e('Clean Up Inbox', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="cleanup-imap-result" style="margin-top: 10px;"></div>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Database Status', 'cf7-artist-submissions'); ?></h3>
                    
                    <?php
                    // Include conversations class to use debug method
                    if (class_exists('CF7_Artist_Submissions_Conversations')) {
                        $db_status = CF7_Artist_Submissions_Conversations::debug_database_status();
                        
                        echo '<h4>Conversations Table:</h4>';
                        echo '<ul>';
                        echo '<li>Table Name: ' . esc_html($db_status['table_name']) . '</li>';
                        echo '<li>Table Exists: ' . ($db_status['table_exists'] ? 'YES' : 'NO') . '</li>';
                        
                        if (isset($db_status['error'])) {
                            echo '<li style="color: red;">Error: ' . esc_html($db_status['error']) . '</li>';
                        } else {
                            echo '<li>Total Messages: ' . esc_html($db_status['total_messages']) . '</li>';
                        }
                        echo '</ul>';
                        
                        if (isset($db_status['recent_messages']) && !empty($db_status['recent_messages'])) {
                            echo '<h4>Recent Messages:</h4>';
                            echo '<table class="widefat" style="margin-top: 10px;">';
                            echo '<thead><tr><th>ID</th><th>Submission</th><th>Direction</th><th>Subject</th><th>Date</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($db_status['recent_messages'] as $msg) {
                                echo '<tr>';
                                echo '<td>' . esc_html($msg->id) . '</td>';
                                echo '<td>' . esc_html($msg->submission_id) . '</td>';
                                echo '<td>' . esc_html($msg->direction) . '</td>';
                                echo '<td>' . esc_html(substr($msg->subject, 0, 50)) . '</td>';
                                echo '<td>' . esc_html($msg->sent_at) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p>No recent messages found in database.</p>';
                        }
                        
                        if (isset($db_status['db_error'])) {
                            echo '<p style="color: red;">Database Error: ' . esc_html($db_status['db_error']) . '</p>';
                        }
                    } else {
                        echo '<p>Conversations class not loaded.</p>';
                    }
                    ?>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Token Migration', 'cf7-artist-submissions'); ?></h3>
                    <p><?php _e('Migrate existing conversations to use consistent reply tokens. This fixes issues where artists reply to old emails with different tokens.', 'cf7-artist-submissions'); ?></p>
                    
                    <button type="button" id="migrate-tokens" class="button button-secondary">
                        <?php _e('Migrate to Consistent Tokens', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="migrate-tokens-result" style="margin-top: 10px;"></div>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Daily Summary Emails', 'cf7-artist-submissions'); ?></h3>
                    <p><?php _e('Test the daily summary email system by manually triggering emails for users with pending actions.', 'cf7-artist-submissions'); ?></p>
                    
                    <?php
                    // Show current daily summary status
                    $next_scheduled = wp_next_scheduled('cf7_daily_summary_cron');
                    if ($next_scheduled) {
                        echo '<p><strong>Next scheduled email:</strong> ' . date(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled) . '</p>';
                    } else {
                        echo '<p style="color: #d63384;"><strong>⚠️ Daily emails are not scheduled!</strong> They should be set up automatically.</p>';
                    }
                    
                    // Show users with pending actions
                    if (class_exists('CF7_Artist_Submissions_Actions')) {
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'cf7_actions';
                        $users_with_actions = $wpdb->get_results(
                            "SELECT DISTINCT a.assigned_to, u.display_name, u.user_email, COUNT(*) as pending_count
                             FROM $table_name a 
                             LEFT JOIN {$wpdb->users} u ON a.assigned_to = u.ID
                             WHERE a.status = 'pending' 
                             AND a.assigned_to IS NOT NULL 
                             GROUP BY a.assigned_to 
                             ORDER BY u.display_name"
                        );
                        
                        if (!empty($users_with_actions)) {
                            echo '<h4>Users with Pending Actions:</h4>';
                            echo '<ul style="margin-left: 20px;">';
                            foreach ($users_with_actions as $user) {
                                echo '<li>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ') - ' . esc_html($user->pending_count) . ' pending action(s)</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No users currently have pending actions assigned to them.</p>';
                        }
                    }
                    ?>
                    
                    <h4><?php _e('Email Configuration Debug', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('Check email configuration and test SMTP settings:', 'cf7-artist-submissions'); ?></p>
                    
                    <?php
                    // Show current email configuration
                    $email_options = get_option('cf7_artist_submissions_email_options', array());
                    $from_email = isset($email_options['from_email']) ? $email_options['from_email'] : get_option('admin_email');
                    $from_name = isset($email_options['from_name']) && !empty($email_options['from_name']) ? $email_options['from_name'] : get_bloginfo('name');
                    
                    echo '<div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">';
                    echo '<strong>Current Email Configuration:</strong><br>';
                    echo 'From Email: ' . esc_html($from_email) . '<br>';
                    echo 'From Name: ' . esc_html($from_name) . '<br>';
                    echo 'WooCommerce Templates: ' . (isset($email_options['use_wc_template']) && $email_options['use_wc_template'] && class_exists('WooCommerce') ? 'Enabled' : 'Disabled') . '<br>';
                    echo 'WordPress Admin Email: ' . esc_html(get_option('admin_email')) . '<br>';
                    echo 'Site Name: ' . esc_html(get_bloginfo('name'));
                    echo '</div>';
                    ?>
                    
                    <button type="button" id="validate-email-config" class="button button-secondary">
                        <?php _e('Validate Email Configuration', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" id="test-smtp-config" class="button button-secondary">
                        <?php _e('Test SMTP Configuration', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="email-config-result" style="margin-top: 10px;"></div>
                    
                    <h4><?php _e('Test Daily Summary', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('Send test summary emails to all users with pending actions:', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="test-daily-summary" class="button button-secondary">
                        <?php _e('Send Test Summary Emails', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="test-daily-summary-result" style="margin-top: 10px;"></div>
                    
                    <h4><?php _e('Cron Management', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('Manage the automated daily email schedule:', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="setup-daily-cron" class="button button-secondary">
                        <?php _e('Setup Daily Cron', 'cf7-artist-submissions'); ?>
                    </button>
                    <button type="button" id="clear-daily-cron" class="button button-secondary">
                        <?php _e('Clear Daily Cron', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="daily-cron-result" style="margin-top: 10px;"></div>
                    
                    <h4><?php _e('Database Schema', 'cf7-artist-submissions'); ?></h4>
                    <p><?php _e('Update the actions database table to include the latest schema changes:', 'cf7-artist-submissions'); ?></p>
                    <button type="button" id="update-actions-schema" class="button button-secondary">
                        <?php _e('Update Actions Schema', 'cf7-artist-submissions'); ?>
                    </button>
                    <div id="update-schema-result" style="margin-top: 10px;"></div>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Live Debug Messages', 'cf7-artist-submissions'); ?></h3>
                    <p><?php _e('Recent activity and debugging information from conversation processing.', 'cf7-artist-submissions'); ?></p>
                    
                    <?php
                    $debug_messages = get_option('cf7_debug_messages', array());
                    if (!empty($debug_messages)) {
                        echo '<div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">';
                        echo '<table class="widefat" style="margin-top: 10px;">';
                        echo '<thead><tr><th>Time</th><th>Action</th><th>Details</th></tr></thead>';
                        echo '<tbody>';
                        
                        // Show most recent messages first
                        $recent_messages = array_reverse($debug_messages);
                        foreach ($recent_messages as $msg) {
                            echo '<tr>';
                            echo '<td>' . esc_html($msg['timestamp']) . '</td>';
                            echo '<td>' . esc_html($msg['action']) . '</td>';
                            
                            $details = '';
                            foreach ($msg as $key => $value) {
                                if ($key !== 'timestamp' && $key !== 'action') {
                                    $details .= $key . ': ' . $value . '<br>';
                                }
                            }
                            echo '<td>' . $details . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        
                        echo '<p><button type="button" id="clear-debug-messages" class="button button-secondary" style="margin-top: 10px;">Clear Debug Messages</button></p>';
                    } else {
                        echo '<p>No debug messages yet. Activity will appear here as conversations are processed.</p>';
                    }
                    ?>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Recent Conversations', 'cf7-artist-submissions'); ?></h3>
                    
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'cf7_conversations';
                    
                    // Check if table exists
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        $recent_messages = $wpdb->get_results(
                            "SELECT * FROM $table_name ORDER BY received_at DESC LIMIT 10"
                        );
                        
                        if ($recent_messages) {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr><th>ID</th><th>Submission</th><th>Direction</th><th>From</th><th>Subject</th><th>Received</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($recent_messages as $message) {
                                echo '<tr>';
                                echo '<td>' . $message->id . '</td>';
                                echo '<td>' . $message->submission_id . '</td>';
                                echo '<td>' . $message->direction . '</td>';
                                echo '<td>' . esc_html($message->from_email) . '</td>';
                                echo '<td>' . esc_html($message->subject) . '</td>';
                                echo '<td>' . $message->received_at . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p>No conversation messages found.</p>';
                        }
                    } else {
                        echo '<p>Conversations table does not exist. Please activate the plugin to create it.</p>';
                    }
                    ?>
                </div>
                
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test-imap-connection').on('click', function() {
                var $button = $(this);
                var $result = $('#imap-test-result');
                
                // Show loading state
                $button.prop('disabled', true).text('<?php _e('Testing...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Testing IMAP connection...', 'cf7-artist-submissions'); ?></p></div>');
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_test_imap',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Test Connection', 'cf7-artist-submissions'); ?>');
                        
                        if (response.success) {
                            var detailsHtml = '';
                            if (response.data.details) {
                                detailsHtml = '<br><?php _e('Messages:', 'cf7-artist-submissions'); ?> ' + response.data.details.messages + 
                                             ', <?php _e('Recent:', 'cf7-artist-submissions'); ?> ' + response.data.details.recent + 
                                             ', <?php _e('Unseen:', 'cf7-artist-submissions'); ?> ' + response.data.details.unseen;
                            }
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + detailsHtml + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Test Connection', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Connection error. Please try again.', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Debug IMAP check
            $('#debug-check-imap').on('click', function() {
                var $button = $(this);
                var $result = $('#debug-imap-result');
                
                $button.prop('disabled', true).text('<?php _e('Checking...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Checking IMAP and processing emails...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_check_replies_manual',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Check IMAP Now', 'cf7-artist-submissions'); ?>');
                        
                        if (response.success) {
                            var message = '<?php _e('IMAP check completed successfully!', 'cf7-artist-submissions'); ?>';
                            if (response.data.checked_at) {
                                message += '<br><?php _e('Last checked:', 'cf7-artist-submissions'); ?> ' + response.data.checked_at;
                            }
                            $result.html('<div class="notice notice-success"><p>' + message + '</p></div>');
                            
                            // Refresh the page after 2 seconds to show updated data
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Check IMAP Now', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('AJAX error. Please try again.', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Debug inbox contents
            $('#debug-inbox').on('click', function() {
                var $button = $(this);
                var $result = $('#debug-inbox-result');
                
                $button.prop('disabled', true).text('<?php _e('Debugging...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Analyzing inbox contents...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_debug_inbox',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Debug Inbox', 'cf7-artist-submissions'); ?>');
                        
                        if (response.success) {
                            var html = '<div class="notice notice-success"><p><?php _e('Inbox analysis completed!', 'cf7-artist-submissions'); ?></p>';
                            html += '<pre style="background: #f9f9f9; padding: 10px; margin: 10px 0; max-height: 400px; overflow-y: auto;">';
                            response.data.debug_info.forEach(function(line) {
                                html += line + '\n';
                            });
                            html += '</pre></div>';
                            $result.html(html);
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Debug Inbox', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('AJAX error. Please try again.', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Clean up IMAP inbox
            $('#cleanup-imap-inbox').on('click', function() {
                var $button = $(this);
                var $result = $('#cleanup-imap-result');
                
                if (!confirm('<?php _e('This will permanently delete all processed emails from your IMAP server. Are you sure you want to continue?', 'cf7-artist-submissions'); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true).text('<?php _e('Cleaning...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Scanning IMAP server and cleaning up processed emails...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: 120000, // 2 minute timeout for cleanup operations
                    data: {
                        action: 'cf7_cleanup_imap_inbox',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Clean Up Inbox', 'cf7-artist-submissions'); ?>');
                        
                        if (response.success) {
                            var message = '<?php _e('IMAP cleanup completed!', 'cf7-artist-submissions'); ?>';
                            if (response.data.deleted_count !== undefined) {
                                message += '<br><?php _e('Emails deleted:', 'cf7-artist-submissions'); ?> ' + response.data.deleted_count;
                            }
                            if (response.data.orphaned_count !== undefined && response.data.orphaned_count > 0) {
                                message += '<br><?php _e('Orphaned emails deleted:', 'cf7-artist-submissions'); ?> ' + response.data.orphaned_count;
                            }
                            if (response.data.scanned_count !== undefined) {
                                message += '<br><?php _e('Emails scanned:', 'cf7-artist-submissions'); ?> ' + response.data.scanned_count;
                            }
                            if (response.data.folders_deleted !== undefined && response.data.folders_deleted > 0) {
                                message += '<br><?php _e('Empty folders deleted:', 'cf7-artist-submissions'); ?> ' + response.data.folders_deleted;
                            }
                            if (response.data.duration !== undefined) {
                                message += '<br><?php _e('Duration:', 'cf7-artist-submissions'); ?> ' + response.data.duration;
                            }
                            $result.html('<div class="notice notice-success"><p>' + message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $button.prop('disabled', false).text('<?php _e('Clean Up Inbox', 'cf7-artist-submissions'); ?>');
                        
                        var errorMessage = '<?php _e('AJAX error. Please try again.', 'cf7-artist-submissions'); ?>';
                        if (status === 'timeout') {
                            errorMessage = '<?php _e('Operation timed out. The cleanup may still be running on the server.', 'cf7-artist-submissions'); ?>';
                        }
                        $result.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                    }
                });
            });
            
            // Migrate tokens
            $('#migrate-tokens').on('click', function() {
                var $button = $(this);
                var $result = $('#migrate-tokens-result');
                
                if (!confirm('<?php _e('This will update all existing conversation tokens to be consistent. Continue?', 'cf7-artist-submissions'); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true).text('<?php _e('Migrating...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Migrating tokens...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_migrate_tokens',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Migrate to Consistent Tokens', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            var message = '<?php _e('Migration completed successfully!', 'cf7-artist-submissions'); ?>';
                            if (response.data.submissions_updated) {
                                message += '<br><?php _e('Updated submissions:', 'cf7-artist-submissions'); ?> ' + response.data.submissions_updated;
                            }
                            $result.html('<div class="notice notice-success"><p>' + message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Migrate to Consistent Tokens', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Migration error. Please try again.', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Clear debug messages
            $('#clear-debug-messages').on('click', function() {
                var $button = $(this);
                
                if (!confirm('<?php _e('Are you sure you want to clear all debug messages?', 'cf7-artist-submissions'); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true).text('<?php _e('Clearing...', 'cf7-artist-submissions'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_clear_debug_messages',
                        nonce: '<?php echo wp_create_nonce('cf7_conversation_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Clear Debug Messages', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            location.reload(); // Reload to show cleared messages
                        } else {
                            alert('<?php _e('Error clearing debug messages', 'cf7-artist-submissions'); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Clear Debug Messages', 'cf7-artist-submissions'); ?>');
                        alert('<?php _e('Error clearing debug messages', 'cf7-artist-submissions'); ?>');
                    }
                });
            });
            
            // Test daily summary emails
            $('#test-daily-summary').on('click', function() {
                var $button = $(this);
                var $result = $('#test-daily-summary-result');
                
                $button.prop('disabled', true).text('<?php _e('Sending...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Sending daily summary emails...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_daily_summary',
                        nonce: '<?php echo wp_create_nonce('cf7_daily_summary_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Send Test Summary Emails', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Send Test Summary Emails', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error sending summary emails', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Setup daily cron
            $('#setup-daily-cron').on('click', function() {
                var $button = $(this);
                var $result = $('#daily-cron-result');
                
                $button.prop('disabled', true).text('<?php _e('Setting up...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Setting up daily cron...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'setup_daily_cron',
                        nonce: '<?php echo wp_create_nonce('cf7_daily_summary_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Setup Daily Cron', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            // Reload page to show updated cron status
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Setup Daily Cron', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error setting up cron', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Clear daily cron
            $('#clear-daily-cron').on('click', function() {
                var $button = $(this);
                var $result = $('#daily-cron-result');
                
                $button.prop('disabled', true).text('<?php _e('Clearing...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Clearing daily cron...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_daily_cron',
                        nonce: '<?php echo wp_create_nonce('cf7_daily_summary_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Clear Daily Cron', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            // Reload page to show updated cron status
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Clear Daily Cron', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error clearing cron', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Update actions schema
            $('#update-actions-schema').on('click', function() {
                var $button = $(this);
                var $result = $('#update-schema-result');
                
                $button.prop('disabled', true).text('<?php _e('Updating...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Updating database schema...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_actions_schema',
                        nonce: '<?php echo wp_create_nonce('cf7_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Update Actions Schema', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Update Actions Schema', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error updating schema', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Send test summary email to current user
            $('#send-test-summary-email').on('click', function() {
                var $button = $(this);
                var $result = $('#test-summary-result');
                
                $button.prop('disabled', true).text('<?php _e('Sending...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Sending test summary email...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_test_summary_email',
                        nonce: '<?php echo wp_create_nonce('cf7_email_test_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Send Test Summary Email to Current User', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Send Test Summary Email to Current User', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error sending test email', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Send summary to all users
            $('#send-summary-to-all').on('click', function() {
                var $button = $(this);
                var $result = $('#summary-all-result');
                
                if (!confirm('<?php _e('This will send daily summary emails to all users with pending actions. Continue?', 'cf7-artist-submissions'); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true).text('<?php _e('Sending...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Sending daily summary emails to all users...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cf7_send_summary_to_all',
                        nonce: '<?php echo wp_create_nonce('cf7_email_test_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Send Daily Summary to All Users', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Send Daily Summary to All Users', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error sending summary emails', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Validate email configuration
            $('#validate-email-config').on('click', function() {
                var $button = $(this);
                var $result = $('#email-config-result');
                
                $button.prop('disabled', true).text('<?php _e('Validating...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Validating email configuration...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'validate_email_config'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Validate Email Configuration', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Validate Email Configuration', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error validating email configuration', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
            
            // Test SMTP configuration
            $('#test-smtp-config').on('click', function() {
                var $button = $(this);
                var $result = $('#email-config-result');
                var testEmail = '<?php echo esc_js(get_option('admin_email')); ?>';
                
                // Prompt for test email address
                var userEmail = prompt('<?php _e('Enter email address to send test email to:', 'cf7-artist-submissions'); ?>', testEmail);
                if (!userEmail) {
                    return; // User cancelled
                }
                
                $button.prop('disabled', true).text('<?php _e('Sending Test...', 'cf7-artist-submissions'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Sending SMTP test email...', 'cf7-artist-submissions'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_smtp_config',
                        test_email: userEmail
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Test SMTP Configuration', 'cf7-artist-submissions'); ?>');
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Test SMTP Configuration', 'cf7-artist-submissions'); ?>');
                        $result.html('<div class="notice notice-error"><p><?php _e('Error testing SMTP configuration', 'cf7-artist-submissions'); ?></p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }    public function render_main_section() {
        echo '<p>' . __('Configure which Contact Form 7 form to track and store submissions from.', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_form_id_field() {
        $options = get_option('cf7_artist_submissions_options', array());
        $form_id = isset($options['form_id']) ? $options['form_id'] : '';
        
        // Get all CF7 forms
        $forms = array();
        if (class_exists('WPCF7_ContactForm')) {
            $cf7_forms = WPCF7_ContactForm::find();
            foreach ($cf7_forms as $form) {
                $forms[$form->id()] = $form->title();
            }
        }
        
        if (empty($forms)) {
            echo '<select name="cf7_artist_submissions_options[form_id]" disabled>';
            echo '<option>' . __('No Contact Form 7 forms found', 'cf7-artist-submissions') . '</option>';
            echo '</select>';
            echo '<p class="description">' . __('Please create a form in Contact Form 7 first.', 'cf7-artist-submissions') . '</p>';
        } else {
            echo '<select name="cf7_artist_submissions_options[form_id]">';
            echo '<option value="">' . __('-- Select a form --', 'cf7-artist-submissions') . '</option>';
            
            foreach ($forms as $id => $title) {
                echo '<option value="' . esc_attr($id) . '" ' . selected($form_id, $id, false) . '>';
                echo esc_html($title) . ' (ID: ' . esc_html($id) . ')';
                echo '</option>';
            }
            
            echo '</select>';
            echo '<p class="description">' . __('Select which Contact Form 7 form to track submissions from.', 'cf7-artist-submissions') . '</p>';
        }
    }
    
    public function render_menu_label_field() {
        $options = get_option('cf7_artist_submissions_options', array());
        $menu_label = isset($options['menu_label']) ? $options['menu_label'] : 'Submissions';
        
        echo '<input type="text" name="cf7_artist_submissions_options[menu_label]" value="' . esc_attr($menu_label) . '" class="regular-text">';
        echo '<p class="description">' . __('The label shown in the admin menu. Default: "Submissions"', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_store_files_field() {
        $options = get_option('cf7_artist_submissions_options', array());
        $store_files = isset($options['store_files']) ? $options['store_files'] : 'yes';
        
        echo '<label>';
        echo '<input type="checkbox" name="cf7_artist_submissions_options[store_files]" value="yes" ' . checked('yes', $store_files, false) . '>';
        echo ' ' . __('Store uploaded files with submissions', 'cf7-artist-submissions');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, files uploaded through the form will be stored in the wp-content/uploads/cf7-submissions directory.', 'cf7-artist-submissions') . '</p>';
    }
    
    public function validate_options($input) {
        $valid = array();
        
        $valid['form_id'] = isset($input['form_id']) ? sanitize_text_field($input['form_id']) : '';
        $valid['menu_label'] = isset($input['menu_label']) ? sanitize_text_field($input['menu_label']) : 'Submissions';
        $valid['store_files'] = isset($input['store_files']) ? 'yes' : 'no';
        
        return $valid;
    }
    
    // IMAP Settings Section Methods
    public function render_imap_section() {
        echo '<p>' . __('Configure IMAP settings to enable two-way email conversations with artists. This uses plus addressing with your existing email address.', 'cf7-artist-submissions') . '</p>';
        echo '<p class="description">' . __('Uses your single email address with plus addressing (e.g., your-email+SUB123@domain.com). No extra email accounts or forwarding needed!', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_server_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $server = isset($options['server']) ? $options['server'] : '';
        
        echo '<input type="text" name="cf7_artist_submissions_imap_options[server]" value="' . esc_attr($server) . '" class="regular-text">';
        echo '<p class="description">' . __('IMAP server hostname (e.g., imap.gmail.com, mail.yourdomain.com)', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_port_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $port = isset($options['port']) ? $options['port'] : '993';
        
        echo '<input type="number" name="cf7_artist_submissions_imap_options[port]" value="' . esc_attr($port) . '" class="small-text" min="1" max="65535">';
        echo '<p class="description">' . __('IMAP port (usually 993 for SSL/TLS, 143 for non-encrypted)', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_username_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $username = isset($options['username']) ? $options['username'] : '';
        
        echo '<input type="text" name="cf7_artist_submissions_imap_options[username]" value="' . esc_attr($username) . '" class="regular-text">';
        echo '<p class="description">' . __('IMAP username (usually your email address)', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_password_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $password = isset($options['password']) ? $options['password'] : '';
        
        echo '<input type="password" name="cf7_artist_submissions_imap_options[password]" value="' . esc_attr($password) . '" class="regular-text">';
        echo '<p class="description">' . __('IMAP password (consider using app-specific passwords for Gmail)', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_encryption_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $encryption = isset($options['encryption']) ? $options['encryption'] : 'ssl';
        
        echo '<select name="cf7_artist_submissions_imap_options[encryption]">';
        echo '<option value="ssl"' . selected('ssl', $encryption, false) . '>SSL/TLS</option>';
        echo '<option value="tls"' . selected('tls', $encryption, false) . '>STARTTLS</option>';
        echo '<option value="none"' . selected('none', $encryption, false) . '>None (not recommended)</option>';
        echo '</select>';
        echo '<p class="description">' . __('Encryption method for IMAP connection', 'cf7-artist-submissions') . '</p>';
    }
    
    public function render_imap_delete_processed_field() {
        $options = get_option('cf7_artist_submissions_imap_options', array());
        $delete_processed = isset($options['delete_processed']) ? $options['delete_processed'] : '1'; // Default to enabled
        
        echo '<label><input type="checkbox" name="cf7_artist_submissions_imap_options[delete_processed]" value="1"' . checked('1', $delete_processed, false) . '> ';
        echo __('Delete emails from server after processing', 'cf7-artist-submissions') . '</label>';
        echo '<p class="description">' . __('When enabled, emails are permanently deleted from the IMAP server after being imported into the database. This prevents duplicate processing when messages are cleared. Recommended for privacy and to avoid reprocessing emails.', 'cf7-artist-submissions') . '</p>';
    }
    
    /**
     * Validate email options
     */
    public function validate_email_options($input) {
        $valid = array();
        
        // Validate from email
        if (isset($input['from_email'])) {
            $email = sanitize_email($input['from_email']);
            if (is_email($email)) {
                $valid['from_email'] = $email;
            } else {
                add_settings_error('cf7_artist_submissions_email_options', 'invalid_email', 'Please enter a valid email address.');
                $valid['from_email'] = get_option('admin_email');
            }
        }
        
        // Validate from name
        if (isset($input['from_name'])) {
            $valid['from_name'] = sanitize_text_field($input['from_name']);
        }
        
        // Validate WooCommerce template option
        $valid['use_wc_template'] = isset($input['use_wc_template']) ? true : false;
        
        return $valid;
    }

    public function validate_imap_options($input) {
        $valid = array();
        
        $valid['server'] = isset($input['server']) ? sanitize_text_field($input['server']) : '';
        $valid['port'] = isset($input['port']) ? intval($input['port']) : 993;
        $valid['username'] = isset($input['username']) ? sanitize_text_field($input['username']) : '';
        $valid['password'] = isset($input['password']) ? $input['password'] : ''; // Don't sanitize password
        $valid['encryption'] = isset($input['encryption']) ? sanitize_text_field($input['encryption']) : 'ssl';
        
        // Validate port range
        if ($valid['port'] < 1 || $valid['port'] > 65535) {
            $valid['port'] = 993;
        }
        
        // Validate encryption method
        if (!in_array($valid['encryption'], array('ssl', 'tls', 'none'))) {
            $valid['encryption'] = 'ssl';
        }
        
        return $valid;
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
        $this->add_wc_template_preview_script();
    }
    
    /**
     * Add WC template preview script
     */
    private function add_wc_template_preview_script() {
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
     * Render email templates
     */
    public function render_email_templates() {
        // Available email triggers
        $triggers = array(
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
        
        foreach ($triggers as $trigger_id => $trigger) {
            echo '<div class="cf7-email-template-section" style="margin-bottom: 30px; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">';
            echo '<h3>' . esc_html($trigger['name']) . '</h3>';
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
            echo '<div class="cf7-template-field" style="margin-bottom: 15px;">';
            echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]">';
            echo '<input type="checkbox" id="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]" name="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][enabled]" value="1" ' . checked(1, $template['enabled'], false) . '>';
            echo ' ' . __('Enable this email template', 'cf7-artist-submissions');
            echo '</label>';
            echo '</div>';
            
            // Auto-send toggle (only if applicable)
            if ($trigger['auto'] !== false) {
                echo '<div class="cf7-template-field" style="margin-bottom: 15px;">';
                echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]">';
                echo '<input type="checkbox" id="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]" name="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][auto_send]" value="1" ' . checked(1, $template['auto_send'], false) . '>';
                echo ' ' . __('Automatically send this email when triggered', 'cf7-artist-submissions');
                echo '</label>';
                echo '</div>';
            }
            
            // Subject field
            echo '<div class="cf7-template-field" style="margin-bottom: 15px;">';
            echo '<label for="cf7_artist_submissions_email_templates[' . esc_attr($trigger_id) . '][subject]">' . __('Email Subject:', 'cf7-artist-submissions') . '</label><br>';
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
            
            echo '</div>'; // End template section
        }
    }
    
    /**
     * Validate email templates
     */
    public function validate_email_templates($input) {
        $valid = array();
        
        if (is_array($input)) {
            foreach ($input as $template_id => $template_data) {
                $valid[$template_id] = array(
                    'enabled' => isset($template_data['enabled']) ? true : false,
                    'auto_send' => isset($template_data['auto_send']) ? true : false,
                    'subject' => isset($template_data['subject']) ? sanitize_text_field($template_data['subject']) : '',
                    'body' => isset($template_data['body']) ? wp_kses_post($template_data['body']) : ''
                );
            }
        }
        
        return $valid;
    }
    
    /**
     * AJAX handler to test daily summary emails
     */
    public function ajax_test_daily_summary() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_daily_summary_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            error_log('CF7 Settings: Test daily summary called');
            $result = CF7_Artist_Submissions_Actions::send_daily_summary_to_all();
            error_log('CF7 Settings: Result = ' . print_r($result, true));
            
            $message = sprintf(
                'Daily summary emails sent! Successfully sent to %d users, %d failed.',
                $result['sent'],
                $result['failed']
            );
            
            if ($result['total_users'] === 0) {
                $message = 'No users have pending actions assigned to them.';
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'details' => $result
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to setup daily cron
     */
    public function ajax_setup_daily_cron() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            CF7_Artist_Submissions_Actions::setup_daily_summary_cron();
            
            $next_scheduled = wp_next_scheduled('cf7_daily_summary_cron');
            if ($next_scheduled) {
                $next_time = date(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled);
                $message = 'Daily summary cron scheduled successfully. Next run: ' . $next_time;
            } else {
                $message = 'Daily summary cron setup attempted, but schedule not found.';
            }
            
            wp_send_json_success(array('message' => $message));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to clear daily cron
     */
    public function ajax_clear_daily_cron() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            CF7_Artist_Submissions_Actions::clear_daily_summary_cron();
            wp_send_json_success(array('message' => 'Daily summary cron cleared successfully.'));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to update actions schema
     */
    public function ajax_update_actions_schema() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cf7_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            // Force schema update
            delete_transient('cf7_actions_schema_checked');
            CF7_Artist_Submissions_Actions::check_and_update_schema();
            
            wp_send_json_success(array('message' => 'Database schema updated successfully. The assigned_to column has been added to the actions table.'));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error updating schema: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to validate email configuration
     */
    public function ajax_validate_email_config() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            $validation = CF7_Artist_Submissions_Actions::validate_email_config();
            $smtp_info = CF7_Artist_Submissions_Actions::get_smtp_config_info();
            
            $response = array(
                'validation' => $validation,
                'smtp_info' => $smtp_info
            );
            
            if ($validation['valid']) {
                $message = '✓ Email configuration is valid';
                $message .= '<br>From: ' . esc_html($validation['from_name']) . ' &lt;' . esc_html($validation['from_email']) . '&gt;';
                $message .= '<br>Site: ' . esc_html($validation['site_host']);
                $message .= '<br><br><strong>SMTP Configuration:</strong>';
                $message .= '<br>SMTP Configured: ' . ($smtp_info['smtp_configured'] ? 'Yes' : 'No');
                $message .= '<br>Mailer Type: ' . esc_html($smtp_info['mailer_type']);
                if (!empty($smtp_info['plugins_detected'])) {
                    $message .= '<br>Plugins Detected: ' . esc_html(implode(', ', $smtp_info['plugins_detected']));
                }
                wp_send_json_success(array('message' => $message, 'details' => $response));
            } else {
                $message = '✗ Email configuration has issues:';
                foreach ($validation['issues'] as $issue) {
                    $message .= '<br>• ' . esc_html($issue);
                }
                wp_send_json_error(array('message' => $message, 'details' => $response));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error validating email config: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to test SMTP configuration
     */
    public function ajax_test_smtp_config() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Check if actions class is available
        if (!class_exists('CF7_Artist_Submissions_Actions')) {
            wp_send_json_error(array('message' => 'Actions class not available'));
            return;
        }
        
        try {
            // Get test email from request or use admin email
            $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : get_option('admin_email');
            
            if (empty($test_email) || !is_email($test_email)) {
                wp_send_json_error(array('message' => 'Invalid test email address'));
                return;
            }
            
            $result = CF7_Artist_Submissions_Actions::test_smtp_configuration($test_email);
            
            if ($result['success']) {
                $message = '✓ SMTP test email sent successfully to ' . esc_html($test_email);
                $message .= '<br><br><strong>Configuration Details:</strong>';
                $message .= '<br>SMTP Configured: ' . ($result['smtp_info']['smtp_configured'] ? 'Yes' : 'No');
                $message .= '<br>Mailer Type: ' . esc_html($result['smtp_info']['mailer_type']);
                if (!empty($result['smtp_info']['plugins_detected'])) {
                    $message .= '<br>Plugins Detected: ' . esc_html(implode(', ', $result['smtp_info']['plugins_detected']));
                }
                $message .= '<br><br>Check your email inbox to confirm receipt.';
                wp_send_json_success(array('message' => $message, 'details' => $result));
            } else {
                $message = '✗ SMTP test failed';
                if (isset($result['error'])) {
                    $message .= '<br>Error: ' . esc_html($result['error']);
                }
                wp_send_json_error(array('message' => $message, 'details' => $result));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error testing SMTP: ' . $e->getMessage()));
        }
    }
}