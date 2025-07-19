<?php
/**
 * Settings Page for CF7 Artist Submissions
 */
class CF7_Artist_Submissions_Settings {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'settings_notice'));
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
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
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
                    do_settings_sections('cf7-artist-submissions');
                    submit_button();
                    ?>
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
        </div>
        <?php
    }
    
    public function render_main_section() {
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
}