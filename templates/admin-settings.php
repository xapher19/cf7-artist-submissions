<?php
/**
 * CF7 Artist Submissions - Admin Settings Template
 *
 * Main plugin settings interface template providing comprehensive configuration
 * management with modern tabbed navigation, card-based design, and integrated
 * settings export/import functionality for complete system administration.
 *
 * Features:
 * • Modern tabbed interface with intuitive navigation
 * • General, email, template, IMAP, debug, and audit configuration tabs
 * • Settings export and import functionality with validation
 * • Real-time form validation and interactive feedback
 * • Professional card-based design with responsive layout
 * • Modal interfaces for enhanced user experience
 *
 * @package CF7_Artist_Submissions
 * @subpackage Templates
 * @since 1.0.0
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="cf7-modern-settings">
    <!-- Settings Header -->
    <div class="cf7-gradient-header cf7-header-context cf7-settings-nav">
        <div class="cf7-header-content">
            <h1 class="cf7-header-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>
            <p class="cf7-header-subtitle">Configure your artist submissions system</p>
        </div>
        <div class="cf7-header-actions">
            <button class="cf7-btn cf7-btn-primary" id="cf7-export-settings">
                <span class="dashicons dashicons-download"></span>
                Export Settings
            </button>
            <button class="cf7-btn cf7-btn-secondary" id="cf7-import-settings">
                <span class="dashicons dashicons-upload"></span>
                Import Settings
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <nav class="cf7-settings-nav">
        <div class="cf7-nav-tabs">
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=general" 
               class="cf7-nav-tab <?php echo $current_tab === 'general' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                <span class="tab-label"><?php _e('General', 'cf7-artist-submissions'); ?></span>
            </a>
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=email" 
               class="cf7-nav-tab <?php echo $current_tab === 'email' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-email-alt"></span>
                <span class="tab-label"><?php _e('Email', 'cf7-artist-submissions'); ?></span>
            </a>
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=templates" 
               class="cf7-nav-tab <?php echo $current_tab === 'templates' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-editor-code"></span>
                <span class="tab-label"><?php _e('Templates', 'cf7-artist-submissions'); ?></span>
            </a>
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=imap" 
               class="cf7-nav-tab <?php echo $current_tab === 'imap' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-networking"></span>
                <span class="tab-label"><?php _e('IMAP', 'cf7-artist-submissions'); ?></span>
            </a>
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=debug" 
               class="cf7-nav-tab <?php echo $current_tab === 'debug' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-tools"></span>
                <span class="tab-label"><?php _e('Debug', 'cf7-artist-submissions'); ?></span>
            </a>
            <a href="?post_type=cf7_submission&page=cf7-artist-submissions-settings&tab=audit" 
               class="cf7-nav-tab <?php echo $current_tab === 'audit' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-chart-line"></span>
                <span class="tab-label"><?php _e('Audit Log', 'cf7-artist-submissions'); ?></span>
            </a>
        </div>
    </nav>

    <!-- Settings Content -->
    <div class="cf7-settings-content">
        <?php
        // Include the appropriate tab content
        switch ($current_tab) {
            case 'general':
                include 'settings/general-tab.php';
                break;
            case 'email':
                include 'settings/email-tab.php';
                break;
            case 'templates':
                include 'settings/templates-tab.php';
                break;
            case 'imap':
                include 'settings/imap-tab.php';
                break;
            case 'debug':
                include 'settings/debug-tab.php';
                break;
            case 'audit':
                include 'settings/audit-tab.php';
                break;
            default:
                include 'settings/general-tab.php';
        }
        ?>
    </div>
</div>

<!-- Settings JavaScript -->
<script>
jQuery(document).ready(function($) {
    // Initialize modern settings interface
    if (typeof CF7AdminInterface !== 'undefined') {
        CF7AdminInterface.init();
    }
    
    // Settings page interactivity
    $('.cf7-settings-form').on('submit', function() {
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
    });
    
    // Test buttons functionality
    $('.cf7-test-btn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const action = $btn.data('action');
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
        
        // Store original text for restoration
        if (!$btn.data('original-text')) {
            $btn.data('original-text', $btn.text());
        }
        
        // Reset after timeout if no response
        setTimeout(() => {
            if ($btn.prop('disabled')) {
                $btn.prop('disabled', false).html($btn.data('original-text') || 'Test');
            }
        }, 30000);
    });
    
    // Close modal handlers
    $('.cf7-modal-close').on('click', function() {
        $('.cf7-modal').hide();
    });
    
    // Close modal on background click
    $('.cf7-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // ESC key to close modals
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.cf7-modal:visible').hide();
        }
    });
    
    // Form validation enhancement
    $('.cf7-field-input[required]').on('blur', function() {
        const $field = $(this);
        if (!$field.val().trim()) {
            $field.addClass('cf7-field-error');
        } else {
            $field.removeClass('cf7-field-error');
        }
    });
    
    // Email validation
    $('.cf7-field-input[type="email"]').on('blur', function() {
        const $field = $(this);
        const email = $field.val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $field.addClass('cf7-field-error');
        } else {
            $field.removeClass('cf7-field-error');
        }
    });
    
    // Success message auto-hide
    $('.notice.is-dismissible').delay(5000).fadeOut();
});
</script>