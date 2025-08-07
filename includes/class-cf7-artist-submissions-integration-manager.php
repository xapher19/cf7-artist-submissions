<?php
/**
 * CF7 Artist Submissions - Integration Manager
 *
 * Manages the integration of enhanced systems with the existing plugin.
 * Handles database table creation, migration processes, and system upgrades.
 *
 * Features:
 * • Database table creation for enhanced systems
 * • Migration from legacy systems to enhanced versions
 * • Version management and upgrade processes
 * • Integration with existing CF7 Artist Submissions functionality
 *
 * @package CF7_Artist_Submissions
 * @subpackage IntegrationManager
 * @since 1.3.0
 * @version 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Artist Submissions Integration Manager Class
 * 
 * Manages integration and database setup for enhanced systems.
 */
class CF7_Artist_Submissions_Integration_Manager {
    
    /**
     * Initialize the integration manager
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'handle_database_setup'));
        add_action('admin_init', array(__CLASS__, 'handle_migrations'));
        
        // Add admin notice for setup completion
        add_action('admin_notices', array(__CLASS__, 'show_setup_notices'));
    }
    
    /**
     * Handle database table creation for enhanced systems
     */
    public static function handle_database_setup() {
        $setup_complete = get_option('cf7as_enhanced_systems_setup', false);
        
        if (!$setup_complete) {
            self::create_enhanced_database_tables();
            update_option('cf7as_enhanced_systems_setup', true);
        }
    }
    
    /**
     * Create database tables for enhanced systems
     */
    public static function create_enhanced_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $tables_created = array();
        
        // Guest Curators table
        if (class_exists('CF7_Artist_Submissions_Guest_Curators')) {
            $result = CF7_Artist_Submissions_Guest_Curators::create_guest_curators_table();
            if ($result) {
                $tables_created[] = 'guest_curators';
            }
            
            $result = CF7_Artist_Submissions_Guest_Curators::create_curator_permissions_table();
            if ($result) {
                $tables_created[] = 'curator_permissions';
            }
        }
        
        // Enhanced Curator Notes table
        if (class_exists('CF7_Artist_Submissions_Enhanced_Curator_Notes')) {
            $notes_table = $wpdb->prefix . 'cf7as_curator_notes';
            
            $sql = "CREATE TABLE $notes_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                submission_id bigint(20) NOT NULL,
                curator_id bigint(20) NULL,
                guest_curator_id bigint(20) NULL,
                curator_name varchar(100) NOT NULL,
                note_content longtext NOT NULL,
                note_type varchar(20) DEFAULT 'note',
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY submission_id (submission_id),
                KEY curator_id (curator_id),
                KEY guest_curator_id (guest_curator_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            if ($result) {
                $tables_created[] = 'curator_notes';
            }
        }
        
        // Update existing ratings table for multi-curator support
        if (class_exists('CF7_Artist_Submissions_Enhanced_Ratings')) {
            self::update_ratings_table_structure();
            $tables_created[] = 'ratings_enhanced';
        }
        
        // Log table creation
        if (!empty($tables_created) && class_exists('CF7_Artist_Submissions_Action_Log')) {
            CF7_Artist_Submissions_Action_Log::log_action(
                0,
                'enhanced_systems_setup',
                array(
                    'tables_created' => $tables_created,
                    'setup_date' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Update ratings table structure for multi-curator support
     */
    public static function update_ratings_table_structure() {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'cf7as_work_ratings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $ratings_table
        ));
        
        if (!$table_exists) {
            return false;
        }
        
        // Check if new columns exist
        $table_structure = $wpdb->get_results("DESCRIBE $ratings_table");
        $has_new_columns = false;
        
        foreach ($table_structure as $column) {
            if ($column->Field === 'curator_name' || $column->Field === 'guest_curator_id') {
                $has_new_columns = true;
                break;
            }
        }
        
        // Add new columns if they don't exist
        if (!$has_new_columns) {
            $wpdb->query("
                ALTER TABLE $ratings_table 
                ADD COLUMN curator_name VARCHAR(100) NULL AFTER curator_id,
                ADD COLUMN guest_curator_id INT(11) NULL AFTER curator_name,
                ADD COLUMN rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER rating
            ");
        }
        
        return true;
    }
    
    /**
     * Handle migrations from legacy systems
     */
    public static function handle_migrations() {
        // Run migrations only once per version
        $migrations_run = get_option('cf7as_migrations_run', array());
        $current_version = CF7_ARTIST_SUBMISSIONS_VERSION;
        
        if (!in_array($current_version, $migrations_run)) {
            self::run_migrations();
            $migrations_run[] = $current_version;
            update_option('cf7as_migrations_run', $migrations_run);
        }
    }
    
    /**
     * Run migration processes
     */
    public static function run_migrations() {
        // Migrate curator notes
        if (class_exists('CF7_Artist_Submissions_Enhanced_Curator_Notes')) {
            CF7_Artist_Submissions_Enhanced_Curator_Notes::maybe_migrate_legacy_notes();
        }
        
        // Migrate ratings
        if (class_exists('CF7_Artist_Submissions_Enhanced_Ratings')) {
            CF7_Artist_Submissions_Enhanced_Ratings::maybe_migrate_legacy_ratings();
        }
    }
    
    /**
     * Show admin notices for setup completion
     */
    public static function show_setup_notices() {
        $setup_complete = get_option('cf7as_enhanced_systems_setup', false);
        $notice_dismissed = get_user_meta(get_current_user_id(), 'cf7as_enhanced_setup_notice_dismissed', true);
        
        if ($setup_complete && !$notice_dismissed && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-success is-dismissible" id="cf7as-enhanced-setup-notice">
                <h3><?php _e('CF7 Artist Submissions - Enhanced Systems Activated!', 'cf7-artist-submissions'); ?></h3>
                <p>
                    <?php _e('The enhanced guest curator system has been successfully set up with the following new features:', 'cf7-artist-submissions'); ?>
                </p>
                <ul style="list-style: disc; padding-left: 20px; margin: 10px 0;">
                    <li><?php _e('Guest Curator Management - Manage external reviewers without WordPress accounts', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('Enhanced Curator Notes - Comment-like system with individual attribution', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('Multi-Curator Ratings - Multiple reviewers can rate with automatic averages', 'cf7-artist-submissions'); ?></li>
                    <li><?php _e('Email-Based Authentication - Secure tokenized access for guest curators', 'cf7-artist-submissions'); ?></li>
                </ul>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=cf7_submission&page=cf7-artist-submissions-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Guest Curators', 'cf7-artist-submissions'); ?>
                    </a>
                    <button type="button" class="notice-dismiss" onclick="cf7as_dismiss_setup_notice()">
                        <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'cf7-artist-submissions'); ?></span>
                    </button>
                </p>
            </div>
            
            <script>
            function cf7as_dismiss_setup_notice() {
                document.getElementById('cf7as-enhanced-setup-notice').style.display = 'none';
                
                // AJAX call to dismiss notice permanently
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: new FormData(Object.assign(document.createElement('form'), {
                        innerHTML: '<input name="action" value="cf7as_dismiss_setup_notice">' +
                                  '<input name="nonce" value="<?php echo wp_create_nonce('cf7as_dismiss_setup_notice'); ?>">'
                    }))
                });
            }
            </script>
            <?php
        }
    }
    
    /**
     * AJAX handler to dismiss setup notice
     */
    public static function ajax_dismiss_setup_notice() {
        if (!wp_verify_nonce($_POST['nonce'], 'cf7as_dismiss_setup_notice')) {
            wp_die('Security check failed');
        }
        
        update_user_meta(get_current_user_id(), 'cf7as_enhanced_setup_notice_dismissed', true);
        wp_die('success');
    }
    
    /**
     * Get enhanced systems status
     */
    public static function get_systems_status() {
        global $wpdb;
        
        $status = array(
            'setup_complete' => get_option('cf7as_enhanced_systems_setup', false),
            'tables' => array(),
            'migrations' => array()
        );
        
        // Check table existence
        $tables_to_check = array(
            'guest_curators' => $wpdb->prefix . 'cf7as_guest_curators',
            'curator_permissions' => $wpdb->prefix . 'cf7as_curator_permissions',
            'curator_notes' => $wpdb->prefix . 'cf7as_curator_notes'
        );
        
        foreach ($tables_to_check as $name => $table_name) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));
            $status['tables'][$name] = !empty($exists);
        }
        
        // Check migration status
        $migrations_run = get_option('cf7as_migrations_run', array());
        $status['migrations'] = array(
            'curator_notes' => get_option('cf7as_curator_notes_migrated', false),
            'ratings' => get_option('cf7as_ratings_migrated', false),
            'version_migrations' => $migrations_run
        );
        
        return $status;
    }
}
