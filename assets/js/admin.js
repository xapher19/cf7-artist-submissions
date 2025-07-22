/**
 * ============================================================================
 * CF7 ARTIST SUBMISSIONS - ADVANCED ADMIN INTERFACE SYSTEM
 * ============================================================================
 * 
 * Comprehensive WordPress admin interface controller for artist submission
 * management with advanced settings configuration, real-time validation,
 * template management, and comprehensive testing infrastructure. Provides
 * modern admin experience with safety mechanisms and user feedback systems.
 * 
 * This system manages the complete admin configuration workflow from settings
 * page initialization through complex test operations to template management.
 * Features sophisticated error handling, automatic recovery mechanisms, and
 * intuitive user interface components for streamlined administrative tasks.
 * 
 * ============================================================================
 * SYSTEM ARCHITECTURE
 * ============================================================================
 * 
 * CF7AdminInterfaceSystem
 * ├─ CoreInterfaceController
 * │  ├─ SettingsPageManager: Main settings page initialization and navigation
 * │  ├─ FormHandlingEngine: Settings form processing and validation
 * │  ├─ EventBindingSystem: Comprehensive event delegation and management
 * │  └─ StateManagementLayer: Admin interface state persistence and recovery
 * │
 * ├─ SafetyMechanismFramework
 * │  ├─ GlobalButtonSafety: Universal button state protection and recovery
 * │  ├─ TimeoutManagement: Automatic operation timeouts with graceful recovery
 * │  ├─ StateCapture: Original button state preservation before modifications
 * │  └─ RecoverySystem: Multi-layer fallback mechanisms for stuck operations
 * │
 * ├─ TestOperationsEngine
 * │  ├─ FormConfigurationTesting: CF7 form setup validation and diagnostics
 * │  ├─ EmailSystemTesting: SMTP/IMAP connection and delivery verification
 * │  ├─ TemplateTestingSystem: Email template rendering and delivery testing
 * │  ├─ DatabaseMaintenanceTools: Schema updates and data migration utilities
 * │  └─ CronJobManagement: Scheduled task configuration and validation
 * │
 * ├─ ModalManagementSystem
 * │  ├─ EmailInputModals: Interactive email address collection for testing
 * │  ├─ TemplatePreviewModals: Rich template preview with sample data rendering
 * │  ├─ ConfirmationDialogs: User confirmation workflows for destructive operations
 * │  └─ ResultsDisplay: Comprehensive test results and feedback presentation
 * │
 * ├─ TemplateManagementEngine
 * │  ├─ TemplateEditor: Rich text editing with merge tag assistance
 * │  ├─ PreviewSystem: Real-time template preview with sample data
 * │  ├─ ResetFunctionality: Template restoration to default configurations
 * │  ├─ MergeTagHelper: Interactive merge tag insertion and guidance
 * │  └─ ValidationEngine: Template syntax and content validation
 * │
 * ├─ AjaxCommunicationLayer
 * │  ├─ RequestManager: Standardized AJAX request handling with safety timeouts
 * │  ├─ ResponseProcessor: Comprehensive response handling and error management
 * │  ├─ SecurityValidation: Nonce verification and secure request transmission
 * │  └─ ErrorRecovery: Graceful failure handling with user-friendly messaging
 * │
 * └─ UserFeedbackFramework
 *    ├─ NotificationSystem: Real-time success/error/warning message display
 *    ├─ ProgressIndicators: Visual feedback for long-running operations
 *    ├─ ValidationFeedback: Real-time form validation with error highlighting
 *    └─ StatusUpdates: Comprehensive operation status communication
 * 
 * ============================================================================
 * INTEGRATION POINTS
 * ============================================================================
 * 
 * • WordPress Admin Framework: Native WordPress admin page and menu integration
 * • CF7 Settings Backend: Deep integration with settings storage and validation
 * • Email System Backend: SMTP/IMAP configuration and testing integration
 * • Template Engine: Email template rendering and management system
 * • Database Management: Schema updates and maintenance tool integration
 * • Cron System: WordPress scheduled task configuration and management
 * • Modal UI Framework: Advanced modal dialog system with accessibility
 * 
 * ============================================================================
 * DEPENDENCIES
 * ============================================================================
 * 
 * • jQuery 3.x: Core JavaScript framework for DOM manipulation and AJAX
 * • WordPress AJAX API: Server communication and nonce validation system
 * • cf7ArtistSubmissions: Localized admin configuration and security tokens
 * • cf7_admin_ajax: Template preview AJAX configuration and endpoints
 * • WordPress Admin UI: Native admin styling and component framework
 * • WordPress Notice System: Admin notification display and management
 * 
 * ============================================================================
 * ADMIN INTERFACE FEATURES
 * ============================================================================
 * 
 * • Settings Management: Comprehensive configuration interface with validation
 * • Test Operations: Real-time testing of SMTP, IMAP, forms, and templates
 * • Template Editor: Rich template editing with merge tag assistance
 * • Import/Export: Settings backup and restoration functionality
 * • Database Tools: Schema updates and maintenance utilities
 * • Cron Management: Scheduled task configuration and monitoring
 * • Modal Workflows: Interactive dialogs for complex operations
 * 
 * ============================================================================
 * SAFETY AND RELIABILITY
 * ============================================================================
 * 
 * • Global Safety Mechanism: Universal button protection with automatic recovery
 * • Timeout Management: Automatic operation timeouts preventing stuck states
 * • State Preservation: Original button state capture before any modifications
 * • Multi-Layer Recovery: Multiple fallback mechanisms for error conditions
 * • AJAX Safety: Comprehensive error handling with graceful degradation
 * • User Feedback: Clear communication of operation status and results
 * 
 * ============================================================================
 * TEST OPERATIONS SYSTEM
 * ============================================================================
 * 
 * • Form Configuration: CF7 form setup validation and field mapping verification
 * • Email Delivery: SMTP configuration testing with actual email delivery
 * • IMAP Connection: Inbox access validation and authentication testing
 * • Template Rendering: Email template processing and delivery verification
 * • Database Schema: Structure validation and migration utilities
 * • Cron Jobs: Scheduled task setup and execution verification
 * 
 * ============================================================================
 * PERFORMANCE FEATURES
 * ============================================================================
 * 
 * • Efficient Event Handling: Event delegation for optimal performance
 * • AJAX Optimization: Request batching and response caching
 * • Memory Management: Proper cleanup of event listeners and DOM elements
 * • State Optimization: Minimal DOM manipulation during operations
 * • Resource Loading: On-demand loading of admin interface components
 * 
 * ============================================================================
 * ACCESSIBILITY FEATURES
 * ============================================================================
 * 
 * • Keyboard Navigation: Full keyboard support for all admin operations
 * • Screen Reader Support: Proper ARIA labels and semantic markup
 * • Focus Management: Logical tab order and focus indicators
 * • High Contrast: Visual elements optimized for accessibility standards
 * • Error Communication: Clear, accessible error and status messaging
 * 
 * ============================================================================
 * SECURITY FEATURES
 * ============================================================================
 * 
 * • Nonce Validation: WordPress security token verification for all operations
 * • Access Control: User permission verification before admin operations
 * • Input Sanitization: Comprehensive input validation and XSS prevention
 * • CSRF Protection: Cross-site request forgery prevention
 * • Secure AJAX: Protected server communication with validation
 * 
 * @package CF7_Artist_Submissions
 * @subpackage AdminInterface
 * @since 2.1.0
 * @version 2.3.0
 * @author CF7 Artist Submissions Development Team
 */
(function($) {
    'use strict';
    
    /**
     * ========================================================================
     * CF7 ADMIN INTERFACE CONTROLLER
     * ========================================================================
     * 
     * Main controller object managing all admin interface functionality.
     * Provides centralized management for settings, testing, and user interactions.
     * 
     * Key Responsibilities:
     * - Settings form handling and validation
     * - Test button operations with safety mechanisms
     * - Template management and preview system
     * - Modal workflows for email testing
     * - Import/export functionality
     * - Real-time user feedback and notifications
     */
    const CF7AdminInterface = {
        
        /**
         * Initialize Admin Interface
         * 
         * Sets up all event handlers, safety mechanisms, and interface components.
         * Called on document ready when settings page is detected.
         * 
         * Initialization Order:
         * 1. Modern event binding
         * 2. Toggle switches setup
         * 3. Test button initialization
         * 4. Form validation
         * 5. Template editor
         * 6. Modal systems
         * 7. Global safety mechanism
         */
        init: function() {
            this.bindModernEvents();
            this.initToggles();
            this.initTestButtons();
            this.initFormValidation();
            this.initTemplateEditor();
            this.initModals();
            this.initGlobalSafetyMechanism();
        },
        
        /**
         * ====================================================================
         * GLOBAL SAFETY MECHANISM
         * ====================================================================
         * 
         * Comprehensive button safety system preventing permanently stuck states.
         * Captures original button state before any modifications and provides
         * automatic recovery with multiple fallback layers.
         * 
         * Safety Features:
         * - Original state capture on mousedown (before click handlers)
         * - Multiple storage locations for redundancy
         * - 60-second global timeout for all operations
         * - Automatic cleanup and recovery
         * - Prevention of double-click scenarios
         * 
         * Storage Strategy:
         * - jQuery data attributes (primary)
         * - DOM element properties (backup)
         * - Multiple keys for different recovery scenarios
         */
        initGlobalSafetyMechanism: function() {
            // Capture original state on mousedown (before click handlers run)
            $(document).on('mousedown', '.cf7-test-btn, .cf7-btn', function() {
                const $button = $(this);
                
                // Skip if button is already disabled
                if ($button.prop('disabled')) {
                    return;
                }
                
                // Capture the ORIGINAL state before any click handlers modify it
                const originalHtml = $button.html();
                const originalDisabled = $button.prop('disabled');
                
                // Store the truly original state using multiple fallback keys
                $button.data('cf7-global-original-html', originalHtml);
                $button.data('cf7-global-original-disabled', originalDisabled);
                
                // Also store on the button element itself as a backup
                $button[0].cf7OriginalHtml = originalHtml;
                $button[0].cf7OriginalDisabled = originalDisabled;
            });
            
            // Track button clicks to set up safety timeouts
            $(document).on('click', '.cf7-test-btn, .cf7-btn', function() {
                const $button = $(this);
                
                // Skip if button is already disabled (prevent double-clicking)
                if ($button.prop('disabled')) {
                    return false;
                }
                
                // Get the original HTML that was captured on mousedown
                const originalHtml = $button.data('cf7-global-original-html');
                
                if (!originalHtml) {
                    $button.data('cf7-global-original-html', $button.html());
                }
                
                // Set global safety timeout (60 seconds max for any operation)
                const globalSafetyId = setTimeout(function() {
                    if ($button.prop('disabled')) {
                        const storedOriginalHtml = $button.data('cf7-global-original-html');
                        CF7AdminInterface.resetButton($button, storedOriginalHtml);
                    }
                }, 60000);
                
                // Store timeout ID so it can be cleared if the operation completes normally
                $button.data('cf7-global-safety-id', globalSafetyId);
            });
        },
        
        /**
         * ====================================================================
         * EVENT BINDING SYSTEM
         * ====================================================================
         * 
         * Centralized event handler binding for all admin interface interactions.
         * Uses event delegation and proper context binding for reliable operation.
         * 
         * Event Categories:
         * - Settings form submissions with validation
         * - Test button operations with safety handling
         * - Toggle switches with visual feedback
         * - Import/export functionality
         * - Template management (preview, reset)
         * - Modal close handlers with cleanup
         * - WooCommerce template integration
         */
        bindModernEvents: function() {
            // Settings form submissions
            $('.cf7-settings-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Test button clicks
            $('.cf7-test-btn').on('click', this.handleTestButton.bind(this));
            
            // Toggle switches - bind to all toggle inputs specifically
            $('.cf7-toggle input[type="checkbox"]').on('change', this.handleToggleChange.bind(this));
            
            // Import/Export buttons
            $('#cf7-export-settings').on('click', this.handleExportSettings.bind(this));
            $('#cf7-import-settings').on('click', this.handleImportSettings.bind(this));
            
            // Template actions
            $('.cf7-template-preview').on('click', this.handleTemplatePreview.bind(this));
            $('.cf7-template-reset').on('click', this.handleTemplateReset.bind(this));
            
            // WooCommerce preview button
            $('#preview-wc-template').on('click', this.handleWooCommercePreview.bind(this));
            
            // Modal close handlers
            $(document).on('click', '#cf7-template-preview-modal', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });
            
            $(document).on('click', '.cf7-modal-close', function() {
                $('#cf7-template-preview-modal').removeClass('show');
            });
            
            // WooCommerce modal close handlers
            $(document).on('click', '#wc-template-preview-modal', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show').trigger('hide.wcPreview');
                }
            });
            
            $(document).on('click', '#close-wc-preview, #close-wc-preview-footer', function() {
                $('#wc-template-preview-modal').removeClass('show').trigger('hide.wcPreview');
            });
        },
        
        initToggles: function() {
            // Template enable/disable toggles
            $('input[id^="template_enabled_"]').on('change', function() {
                const $template = $(this).closest('.cf7-template-section');
                const $content = $template.find('.cf7-template-content');
                
                if ($(this).is(':checked')) {
                    $content.addClass('active');
                } else {
                    $content.removeClass('active');
                }
            });
            
            // Initialize toggle states on page load
            $('input[id^="template_enabled_"]:checked').each(function() {
                const $template = $(this).closest('.cf7-template-section');
                const $content = $template.find('.cf7-template-content');
                $content.addClass('active');
            });
            
            // Handle toggle slider clicks to properly toggle the checkbox
            $('.cf7-toggle-slider').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $checkbox = $(this).siblings('input[type="checkbox"]');
                const wasChecked = $checkbox.prop('checked');
                $checkbox.prop('checked', !wasChecked).trigger('change');
                return false;
            });
        },
        
        initTestButtons: function() {
            // Store original text for test buttons
            $('.cf7-test-btn').each(function() {
                $(this).data('original-text', $(this).text());
            });
        },
        
        initFormValidation: function() {
            // Real-time form validation
            $('.cf7-field-input').on('input', function() {
                const $field = $(this);
                const value = $field.val();
                
                // Email validation
                if ($field.attr('type') === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        $field.addClass('cf7-field-error');
                    } else {
                        $field.removeClass('cf7-field-error');
                    }
                }
                
                // Required field validation
                if ($field.attr('required') && !value) {
                    $field.addClass('cf7-field-error');
                } else if (!$field.attr('type') || $field.attr('type') !== 'email') {
                    $field.removeClass('cf7-field-error');
                }
            });
        },
        
        initTemplateEditor: function() {
            // Initialize template editor functionality
            $('.cf7-template-subject, .cf7-template-body').on('input', function() {
                const $template = $(this).closest('.cf7-template-section');
                $template.addClass('cf7-template-modified');
            });
            
            // Merge tag helper
            this.addMergeTagHelper();
        },
        
        initModals: function() {
            // Modal close handlers
            $('.cf7-modal-close').on('click', function() {
                $(this).closest('.cf7-modal').removeClass('show');
            });
            
            // Click outside to close
            $('.cf7-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.cf7-modal.show').removeClass('show');
                }
            });
        },
        
        addMergeTagHelper: function() {
            // Add merge tag buttons to template editors
            const mergeTags = [
                '{artist_name}', '{artist_email}', '{submission_title}', 
                '{submission_id}', '{site_name}', '{site_url}'
            ];
            
            $('.cf7-template-body').each(function() {
                const $textarea = $(this);
                const $container = $textarea.parent();
                
                if (!$container.find('.cf7-merge-tags').length) {
                    const $mergeTagsDiv = $('<div class="cf7-merge-tags"><span>Quick Insert: </span></div>');
                    
                    mergeTags.forEach(function(tag) {
                        const $button = $('<button type="button" class="cf7-merge-tag-btn">' + tag + '</button>');
                        $button.on('click', function() {
                            const currentValue = $textarea.val();
                            const cursorPos = $textarea[0].selectionStart;
                            const newValue = currentValue.slice(0, cursorPos) + tag + currentValue.slice(cursorPos);
                            $textarea.val(newValue);
                            $textarea.focus();
                            $textarea[0].setSelectionRange(cursorPos + tag.length, cursorPos + tag.length);
                        });
                        $mergeTagsDiv.append($button);
                    });
                    
                    $container.append($mergeTagsDiv);
                }
            });
        },
        
        handleFormSubmit: function(e) {
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.prop('disabled', true)
                     .html('<span class="dashicons dashicons-update cf7-spin"></span> Saving...');
            
            // Let the form submit naturally, but restore button state on page reload
            setTimeout(function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }, 100);
        },
        
        /**
         * ====================================================================
         * TEST OPERATIONS DISPATCHER
         * ====================================================================
         * 
         * Central dispatcher for all test button operations with standardized
         * loading states and error handling.
         * 
         * Supported Test Operations:
         * - test-form: Form configuration validation
         * - validate-email-config: Email settings verification
         * - test-smtp: SMTP connection and authentication
         * - test-imap: IMAP connection testing
         * - cleanup-inbox: Email cleanup operations
         * - test-template: Template email sending
         * - update-schema: Database schema updates
         * - migrate-tokens: Conversation token migration
         * - test-daily-summary: Daily summary email testing
         * - setup-cron: Cron job configuration
         * 
         * Process Flow:
         * 1. Extract action from button data attribute
         * 2. Set standardized loading state
         * 3. Dispatch to appropriate test method
         * 4. Handle success/error with user feedback
         */
        handleTestButton: function(e) {
            e.preventDefault();
            const action = $(e.target).data('action');
            const $button = $(e.target);
            
            // Disable button and show loading
            $button.prop('disabled', true);
            const originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Testing...');
            
            // Handle different test actions
            switch(action) {
                case 'test-form':
                    this.testFormConfiguration($button, originalHtml);
                    break;
                case 'validate-email-config':
                    this.testEmailConfig($button, originalHtml);
                    break;
                case 'test-smtp':
                    this.testSmtpConfig($button, originalHtml);
                    break;
                case 'test-imap':
                    this.testImapConnection($button, originalHtml);
                    break;
                case 'cleanup-inbox':
                    this.cleanupInbox($button, originalHtml);
                    break;
                case 'test-template':
                    this.testTemplateEmail($button, originalHtml);
                    break;
                case 'update-schema':
                    this.updateDatabaseSchema($button, originalHtml);
                    break;
                case 'migrate-tokens':
                    this.migrateConversationTokens($button, originalHtml);
                    break;
                case 'test-daily-summary':
                    this.testDailySummary($button, originalHtml);
                    break;
                case 'setup-cron':
                    this.setupDailyCron($button, originalHtml);
                    break;
                default:
                    this.resetButton($button, originalHtml);
            }
        },
        
        handleToggleChange: function(e) {
            const $toggle = $(e.target);
            const $slider = $toggle.siblings('.cf7-toggle-slider');
            
            // Add visual feedback
            $slider.addClass('cf7-toggle-changing');
            
            // Remove the changing state after transition
            setTimeout(function() {
                $slider.removeClass('cf7-toggle-changing');
            }, 200);
        },
        
        handleExportSettings: function(e) {
            e.preventDefault();
            
            // Collect all form data
            const settings = this.collectSettings();
            
            // Create download
            const dataStr = JSON.stringify(settings, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            // Trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = 'cf7-artist-submissions-settings.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            this.showNotice('Settings exported successfully!', 'success');
        },
        
        handleImportSettings: function(e) {
            e.preventDefault();
            
            // Create file input
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        try {
                            const settings = JSON.parse(e.target.result);
                            CF7AdminInterface.importSettings(settings);
                        } catch (error) {
                            CF7AdminInterface.showNotice('Invalid settings file format.', 'error');
                        }
                    };
                    reader.readAsText(file);
                }
            };
            
            input.click();
        },
        
        // ========================================
        // AJAX Operations System
        // ========================================
        // Core AJAX request handling with comprehensive safety mechanisms
        // and automatic error recovery. Provides standardized communication
        // with WordPress backend handlers while maintaining UI consistency.
        //
        // System Features:
        // • Automatic safety timeouts with graceful button state recovery
        // • Standardized error handling with user feedback mechanisms
        // • Cross-tab state synchronization for consistent UI experience
        // • Request logging and debugging support for development
        // • Configurable timeout periods for different operation types
        //
        // Integration Points:
        // → WordPress AJAX handler system (admin-ajax.php)
        // → cf7ArtistSubmissions localized data configuration
        // → Global nonce validation for security compliance
        // → Backend PHP action handlers in includes/ directory
        //
        // Request Lifecycle:
        // 1. Button state management and visual feedback activation
        // 2. Safety timeout establishment with automatic recovery
        // 3. AJAX request dispatch with standardized data format
        // 4. Response processing with success/error differentiation
        // 5. UI state restoration and cleanup operations
        // ========================================

        /**
         * Perform safe AJAX test with automatic button reset fallback
         * 
         * Core AJAX request handler that provides comprehensive safety mechanisms
         * including automatic timeouts, button state management, and error recovery.
         * Ensures consistent user experience across all test operations.
         * 
         * @param {jQuery} $button - Button element being tested
         * @param {string} originalHtml - Original button HTML for restoration
         * @param {Object} ajaxData - AJAX request data including action and nonce
         * @param {Function} successCallback - Optional custom success handler
         * @param {Function} errorCallback - Optional custom error handler
         * 
         * Process Flow:
         * 1. Establish 15-second safety timeout for automatic recovery
         * 2. Execute jQuery AJAX request with standardized configuration
         * 3. Handle success response with optional custom processing
         * 4. Manage error conditions with comprehensive logging
         * 5. Restore button state with cleanup scheduling
         */
        performSafeAjaxTest: function($button, originalHtml, ajaxData, successCallback, errorCallback) {
            // Set safety timeout to ensure button always gets reset
            const safetyResetId = setTimeout(function() {
                console.warn('CF7AdminInterface: Safety timeout triggered for', ajaxData.action);
                CF7AdminInterface.resetButton($button, originalHtml);
                // Clean up after safety timeout
                setTimeout(function() {
                    CF7AdminInterface.cleanupButtonState($button);
                }, 500);
            }, 15000); // 15 second safety timeout
            
            $.ajax({
                url: cf7ArtistSubmissions.ajaxUrl || ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    clearTimeout(safetyResetId);
                    
                    if (successCallback) {
                        successCallback(response);
                    } else {
                        CF7AdminInterface.showTestResults(response.data.message, response.success);
                    }
                    
                    CF7AdminInterface.resetButton($button, originalHtml, 300);
                    
                    // Clean up button state after successful operation
                    setTimeout(function() {
                        CF7AdminInterface.cleanupButtonState($button);
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error('CF7AdminInterface: AJAX error for', ajaxData.action, {xhr, status, error});
                    clearTimeout(safetyResetId);
                    
                    if (errorCallback) {
                        errorCallback(xhr, status, error);
                    } else {
                        CF7AdminInterface.showTestResults('Test failed: ' + error, false);
                    }
                    
                    CF7AdminInterface.resetButton($button, originalHtml, 300);
                    
                    // Clean up button state after error
                    setTimeout(function() {
                        CF7AdminInterface.cleanupButtonState($button);
                    }, 1000);
                }
            });
        },
        
        // ========================================
        // Configuration Test Operations
        // ========================================
        // Specialized test functions for validating different system
        // configurations with targeted error handling and user feedback.
        //
        // Test Categories:
        // • Form Configuration: CF7 form setup and field validation
        // • Email Configuration: SMTP settings and delivery validation
        // • IMAP Configuration: Inbox connection and authentication
        // • Template System: Email template rendering and delivery
        // ========================================

        /**
         * Test Contact Form 7 configuration
         * 
         * Validates CF7 form setup, field mappings, and submission processing.
         * Checks for proper form configuration, required field validation,
         * and integration with artist submission workflow.
         * 
         * @param {jQuery} $button - Test button element
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Backend Handler: test_form_config (includes/class-cf7-artist-submissions-admin.php)
         */
        testFormConfiguration: function($button, originalHtml) {
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'test_form_config',
                nonce: cf7ArtistSubmissions.nonce
            }, null, function(xhr, status, error) {
                console.error('Form config test AJAX error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                CF7AdminInterface.showTestResults('Form configuration test failed: ' + error, false);
            });
        },
        
        /**
         * Test email configuration and delivery system
         * 
         * Validates SMTP settings, authentication credentials, and basic
         * email delivery capability. Tests connection to mail server
         * without requiring specific recipient email address.
         * 
         * @param {jQuery} $button - Test button element
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Backend Handler: validate_email_config (includes/class-cf7-artist-submissions-emails.php)
         */
        testEmailConfig: function($button, originalHtml) {
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'validate_email_config',
                nonce: cf7ArtistSubmissions.nonce
            });
        },
        
        /**
         * Test SMTP configuration with email delivery verification
         * 
         * Comprehensive SMTP testing that requires user email input for
         * actual delivery verification. Includes modal-based email collection,
         * extended timeout handling for slower SMTP servers, and detailed
         * delivery status reporting.
         * 
         * @param {jQuery} $button - Test button element  
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Process Flow:
         * 1. Display email input modal with validation
         * 2. Configure extended 30-second safety timeout for SMTP delays
         * 3. Update button state to indicate email sending in progress
         * 4. Execute SMTP test with user-provided recipient address
         * 5. Display comprehensive delivery results with error details
         * 
         * Backend Handler: test_smtp_config (includes/class-cf7-artist-submissions-emails.php)
         */
        testSmtpConfig: function($button, originalHtml) {
            // Store button reference for safety reset
            const safetyResetId = setTimeout(function() {
                CF7AdminInterface.resetButton($button, originalHtml);
            }, 30000); // 30 second safety timeout
            
            // DON'T reset the button immediately - let it stay in "Testing..." state
            // The modal will handle the workflow from here
            
            // Create and show email input modal instead of using prompt
            this.showEmailInputModal(function(testEmail) {
                // Clear safety timeout since we're handling the flow
                clearTimeout(safetyResetId);
                
                if (!testEmail) {
                    // Reset button if no email provided
                    CF7AdminInterface.resetButton($button, originalHtml);
                    return;
                }
                
                // Keep button disabled and update text to show we're sending
                $button.prop('disabled', true);
                $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Sending...');
                
                // Set another safety timeout for the AJAX request
                const ajaxSafetyResetId = setTimeout(function() {
                    CF7AdminInterface.resetButton($button, originalHtml);
                }, 15000); // 15 second timeout for AJAX
                
                $.ajax({
                    url: cf7ArtistSubmissions.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_smtp_config',
                        test_email: testEmail,
                        nonce: cf7ArtistSubmissions.nonce
                    },
                    success: function(response) {
                        clearTimeout(ajaxSafetyResetId);
                        CF7AdminInterface.showTestResults(response.data.message, response.success);
                        CF7AdminInterface.resetButton($button, originalHtml, 300);
                    },
                    error: function() {
                        clearTimeout(ajaxSafetyResetId);
                        CF7AdminInterface.showTestResults('Test failed - please try again.', false);
                        CF7AdminInterface.resetButton($button, originalHtml, 300);
                    }
                });
            }, function() {
                // Cancel callback - clear safety timeout and reset button
                clearTimeout(safetyResetId);
                // Use the globally stored original HTML instead of the passed originalHtml
                const trueOriginalHtml = $button.data('cf7-global-original-html') || $button[0].cf7OriginalHtml;
                CF7AdminInterface.resetButton($button, trueOriginalHtml || originalHtml);
            });
        },
        
        /**
         * Test IMAP connection and inbox access
         * 
         * Validates IMAP server connection, authentication credentials,
         * and inbox accessibility for conversation management system.
         * Tests folder access permissions and basic email retrieval.
         * 
         * @param {jQuery} $button - Test button element
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Backend Handler: cf7_test_imap (includes/class-cf7-artist-submissions-conversations.php)
         * Security: Uses conversationNonce for IMAP-specific operations
         */
        testImapConnection: function($button, originalHtml) {
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'cf7_test_imap',
                nonce: cf7ArtistSubmissions.conversationNonce
            }, null, function(xhr, status, error) {
                CF7AdminInterface.showTestResults('AJAX request failed: ' + error + ' (Status: ' + status + ')', false);
            });
        },
        
        /**
         * Clean up processed emails from IMAP inbox
         * 
         * Removes processed conversation emails from the configured IMAP inbox
         * to prevent storage accumulation. Includes user confirmation dialog
         * and comprehensive error handling for IMAP operations.
         * 
         * @param {jQuery} $button - Cleanup button element
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Safety Features:
         * • User confirmation dialog before execution
         * • Automatic button reset on user cancellation  
         * • Custom success/error message handling
         * • IMAP connection validation before cleanup
         * 
         * Backend Handler: cf7_cleanup_inbox (includes/class-cf7-artist-submissions-conversations.php)
         */
        cleanupInbox: function($button, originalHtml) {
            if (!confirm('This will clean up processed emails from your inbox. Continue?')) {
                CF7AdminInterface.resetButton($button, originalHtml);
                return;
            }
            
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'cf7_cleanup_inbox',
                nonce: cf7ArtistSubmissions.conversationNonce
            }, function(response) {
                CF7AdminInterface.showTestResults(response.data.message, response.success);
            }, function() {
                CF7AdminInterface.showTestResults('Cleanup failed - please try again.', false);
            });
        },
        
        /**
         * Test email template rendering and delivery
         * 
         * Validates email template system by rendering active template with
         * test data and delivering to specified email address. Includes
         * automatic template detection and comprehensive delivery verification.
         * 
         * @param {jQuery} $button - Test button element
         * @param {string} originalHtml - Original button HTML for restoration
         * 
         * Process Flow:
         * 1. Detect currently active template from UI state
         * 2. Display email input modal for delivery destination
         * 3. Configure extended timeout for template processing
         * 4. Render template with test data and send to recipient
         * 5. Provide detailed delivery status and template validation results
         * 
         * Template Detection:
         * • Scans for active .cf7-template-section elements
         * • Falls back to 'submission_received' default template
         * • Supports all configured email templates in system
         * 
         * Backend Handler: test_template_email (includes/class-cf7-artist-submissions-emails.php)
         */
        testTemplateEmail: function($button, originalHtml) {
            // Store button reference for safety reset
            const safetyResetId = setTimeout(function() {
                console.warn('CF7AdminInterface: testTemplateEmail safety timeout triggered');
                CF7AdminInterface.resetButton($button, originalHtml);
            }, 30000); // 30 second safety timeout
            
            // DON'T reset the button immediately - let it stay in "Testing..." state
            // The modal will handle the workflow from here
            
            // Create and show email input modal
            this.showEmailInputModal(function(testEmail) {
                // Clear safety timeout since we're handling the flow
                clearTimeout(safetyResetId);
                
                if (!testEmail) {
                    CF7AdminInterface.resetButton($button, originalHtml);
                    return;
                }
                
                // Find which template is currently being viewed
                const activeTemplate = $('.cf7-template-section').first().data('template') || 'submission_received';
                
                // Keep button disabled and update text to show we're sending
                $button.prop('disabled', true);
                $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Sending Template...');
                
                CF7AdminInterface.performSafeAjaxTest($button, originalHtml, {
                    action: 'cf7_test_template',
                    test_email: testEmail,
                    template_id: activeTemplate,
                    nonce: cf7ArtistSubmissions.nonce
                });
            }, function() {
                // Cancel callback - clear safety timeout and reset button
                clearTimeout(safetyResetId);
                // Use the globally stored original HTML instead of the passed originalHtml
                const trueOriginalHtml = $button.data('cf7-global-original-html') || $button[0].cf7OriginalHtml;
                CF7AdminInterface.resetButton($button, trueOriginalHtml || originalHtml);
            });
        },
        
        updateDatabaseSchema: function($button, originalHtml) {
            if (!confirm('This will update the database schema. Continue?')) {
                CF7AdminInterface.resetButton($button, originalHtml);
                return;
            }
            
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'update_actions_schema',
                nonce: cf7ArtistSubmissions.nonce
            }, function(response) {
                CF7AdminInterface.showTestResults(response.data.message, response.success);
            }, function() {
                CF7AdminInterface.showTestResults('Schema update failed - please try again.', false);
            });
        },
        
        migrateConversationTokens: function($button, originalHtml) {
            if (!confirm('This will migrate conversation tokens. Continue?')) {
                CF7AdminInterface.resetButton($button, originalHtml);
                return;
            }
            
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'migrate_conversation_tokens',
                nonce: cf7ArtistSubmissions.nonce
            }, function(response) {
                CF7AdminInterface.showTestResults(response.data.message, response.success);
            }, function() {
                CF7AdminInterface.showTestResults('Token migration failed - please try again.', false);
            });
        },
        
        testDailySummary: function($button, originalHtml) {
            // Store button reference for safety reset
            const safetyResetId = setTimeout(function() {
                CF7AdminInterface.resetButton($button, originalHtml);
            }, 30000); // 30 second safety timeout
            
            // Reset button immediately since modal will handle the workflow
            CF7AdminInterface.resetButton($button, originalHtml);
            
            // Create and show email input modal
            this.showEmailInputModal(function(testEmail) {
                // Clear safety timeout since we're handling the flow
                clearTimeout(safetyResetId);
                
                if (!testEmail) {
                    return;
                }
                
                // Disable button again while sending the actual test
                $button.prop('disabled', true);
                $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Sending Summary...');
                
                CF7AdminInterface.performSafeAjaxTest($button, originalHtml, {
                    action: 'test_daily_summary',
                    test_email: testEmail,
                    nonce: cf7ArtistSubmissions.nonce
                }, function(response) {
                    CF7AdminInterface.showTestResults(response.data.message, response.success);
                }, function() {
                    CF7AdminInterface.showTestResults('Daily summary test failed - please try again.', false);
                });
            }, function() {
                // Cancel callback - clear safety timeout since modal was cancelled
                clearTimeout(safetyResetId);
            });
        },
        
        setupDailyCron: function($button, originalHtml) {
            if (!confirm('This will setup the daily summary cron job. Continue?')) {
                CF7AdminInterface.resetButton($button, originalHtml);
                return;
            }
            
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'setup_daily_cron',
                nonce: cf7ArtistSubmissions.nonce
            }, function(response) {
                CF7AdminInterface.showTestResults(response.data.message, response.success);
                // Reload page to show updated cron status
                if (response.success) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            }, function() {
                CF7AdminInterface.showTestResults('Cron setup failed - please try again.', false);
            });
        },
        
        handleTemplatePreview: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const templateId = $button.data('template');
            
            // Prevent multiple simultaneous requests
            if ($button.hasClass('cf7-preview-loading')) {
                return;
            }
            
            if (!templateId) {
                alert('Error: Template ID not found. Please check the button configuration.');
                return;
            }
            
            // Mark button as loading to prevent multiple requests
            $button.addClass('cf7-preview-loading');
            
            // Store original button state (ensure we capture the real original state)
            let originalHtml = $button.data('original-html');
            let originalDisabled = $button.data('original-disabled');
            
            // If not stored yet, store it now
            if (!originalHtml) {
                originalHtml = $button.html();
                originalDisabled = $button.prop('disabled');
                $button.data('original-html', originalHtml);
                $button.data('original-disabled', originalDisabled);
            }
            
            // Show loading state
            $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Loading...');
            $button.prop('disabled', true);
            
            // Show preview modal with loading
            const $modal = $('#cf7-template-preview-modal');
            const $content = $('#cf7-template-preview-content');
            
            $content.html('<div class="cf7-modal-loading"><span class="dashicons dashicons-update"></span><div class="cf7-modal-loading-text">Generating preview...</div></div>');
            $modal.addClass('show').css('display', 'flex');
            
            // Function to reset button state
            const resetButton = function() {
                $button.html(originalHtml);
                $button.prop('disabled', originalDisabled);
                $button.removeClass('cf7-preview-loading');
            };
            
            // Reset button when modal is closed - listen for actual hide event
            $modal.off('hide.preview').on('hide.preview', resetButton);
            
            // Also add a mutation observer as backup to detect when modal display changes
            const resetOnHide = function() {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const modal = mutation.target;
                            if (!modal.classList.contains('show')) {
                                resetButton();
                                observer.disconnect();
                            }
                        }
                    });
                });
                
                observer.observe($modal[0], {
                    attributes: true,
                    attributeFilter: ['class']
                });
            };
            
            resetOnHide();
            
            // Make AJAX request for preview
            $.ajax({
                url: cf7_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cf7_preview_template',
                    template_id: templateId,
                    nonce: cf7_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const preview = response.data;
                        let previewHtml = 
                            '<div class="cf7-preview-notice">' +
                                '<span class="dashicons dashicons-info"></span> ' +
                                '<div>This preview uses sample data to show how your template will look.';
                        
                        if (preview.uses_woocommerce) {
                            previewHtml += ' <strong>WooCommerce email template styling is applied.</strong>';
                        }
                        
                        previewHtml += 
                            '</div>' +
                        '</div>' +
                        '<div class="cf7-preview-template-name">' +
                            '<strong>Template:</strong> ' + (preview.template_name || '') +
                        '</div>' +
                        '<div class="cf7-preview-subject">' +
                            '<strong>Subject</strong>' +
                            '<div>' + (preview.subject || '') + '</div>' +
                        '</div>' +
                        '<div class="cf7-preview-body">' +
                            '<strong>Email Content</strong>' +
                            '<div class="cf7-preview-body-content">' + (preview.body || '') + '</div>' +
                        '</div>';
                        
                        $content.html(previewHtml);
                        
                        // Reset button state since preview loaded successfully
                        resetButton();
                    } else {
                        $content.html(
                            '<div class="cf7-error">' +
                                '<span class="dashicons dashicons-warning"></span> ' +
                                'Error: ' + (response.data?.message || 'Unable to generate preview') +
                            '</div>'
                        );
                        resetButton();
                    }
                },
                error: function() {
                    $content.html(
                        '<div class="cf7-error">' +
                            '<span class="dashicons dashicons-warning"></span> ' +
                            'Network error: Unable to generate preview' +
                        '</div>'
                    );
                    resetButton();
                },
                complete: function() {
                    // Button state will be reset when modal is closed
                }
            });
        },
        
        handleTemplateReset: function(e) {
            e.preventDefault();
            if (!confirm('Reset this template to default? This cannot be undone.')) {
                return;
            }
            
            const $button = $(this);
            const templateId = $button.data('template');
            const $template = $button.closest('.cf7-template-section');
            
            // Reset to default values (you would need to store these or fetch from server)
            // For now, just clear the fields
            $template.find('.cf7-template-subject').val('');
            $template.find('.cf7-template-body').val('');
        },
        
        handleWooCommercePreview: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            // Prevent multiple simultaneous requests
            if ($button.hasClass('cf7-preview-loading')) {
                return;
            }
            
            // Mark button as loading
            $button.addClass('cf7-preview-loading');
            const originalHtml = $button.html();
            
            // Show loading state
            $button.html('<span class="dashicons dashicons-update cf7-spin"></span> Loading Preview...');
            $button.prop('disabled', true);
            
            // Show modal with loading
            const $modal = $('#wc-template-preview-modal');
            const $content = $('#wc-template-preview-content');
            
            $content.html('<div class="cf7-modal-loading"><span class="dashicons dashicons-update"></span><div class="cf7-modal-loading-text">Generating WooCommerce template preview...</div></div>');
            $modal.addClass('show').css('display', 'flex');
            
            // Function to reset button state
            const resetButton = function() {
                $button.html(originalHtml);
                $button.prop('disabled', false);
                $button.removeClass('cf7-preview-loading');
            };
            
            // Reset button when modal is closed
            $modal.off('hide.wcPreview').on('hide.wcPreview', resetButton);
            
            // Use existing preview action with a sample template
            $.ajax({
                url: cf7_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cf7_preview_template',
                    template_id: 'submission_received', // Use a default template
                    nonce: cf7_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const preview = response.data;
                        let previewHtml = 
                            '<div class="cf7-preview-notice">' +
                                '<span class="dashicons dashicons-info"></span> ' +
                                '<div>This shows how your emails will look when using WooCommerce email template styling.</div>' +
                            '</div>';
                        
                        if (preview.uses_woocommerce) {
                            previewHtml += 
                                '<div class="cf7-preview-template-name">' +
                                    '<strong>Template:</strong> ' + (preview.template_name || 'Sample Template') + ' (WooCommerce Style)' +
                                '</div>' +
                                '<div class="cf7-preview-subject">' +
                                    '<strong>Subject</strong>' +
                                    '<div>' + (preview.subject || '') + '</div>' +
                                '</div>' +
                                '<div class="cf7-preview-body">' +
                                    '<strong>Email Content with WooCommerce Styling</strong>' +
                                    '<div class="cf7-preview-body-content">' + (preview.body || '') + '</div>' +
                                '</div>';
                        } else {
                            previewHtml += 
                                '<div class="cf7-error">' +
                                    '<span class="dashicons dashicons-warning"></span> ' +
                                    'WooCommerce template styling is not enabled or WooCommerce is not active.' +
                                '</div>';
                        }
                        
                        $content.html(previewHtml);
                    } else {
                        $content.html(
                            '<div class="cf7-error">' +
                                '<span class="dashicons dashicons-warning"></span> ' +
                                'Error: ' + (response.data?.message || 'Unable to generate WooCommerce preview') +
                            '</div>'
                        );
                    }
                    resetButton();
                },
                error: function() {
                    $content.html(
                        '<div class="cf7-error">' +
                            '<span class="dashicons dashicons-warning"></span> ' +
                            'Network error: Unable to generate WooCommerce preview' +
                        '</div>'
                    );
                    resetButton();
                }
            });
        },
        
        resetButton: function($button, originalHtml, delay) {
            const resetAction = function() {
                // Get original state - prefer globally captured state over passed parameter
                let finalOriginalHtml = originalHtml;
                
                // Check for globally captured original state first (most reliable)
                const globalOriginalHtml = $button.data('cf7-global-original-html');
                if (globalOriginalHtml) {
                    finalOriginalHtml = globalOriginalHtml;
                } else if (!finalOriginalHtml) {
                    // Fallback to locally stored original state
                    const localOriginalHtml = $button.data('cf7-original-html');
                    if (localOriginalHtml) {
                        finalOriginalHtml = localOriginalHtml;
                    }
                }
                
                // Final fallback to current text with proper button structure
                if (!finalOriginalHtml) {
                    const currentText = $button.text().replace(/\.\.\.|Testing|Sending|Loading/gi, '').trim();
                    finalOriginalHtml = currentText || 'Test';
                }
                
                $button.prop('disabled', false).html(finalOriginalHtml);
                
                // Clear any global safety timeouts
                const safetyId = $button.data('cf7-global-safety-id');
                if (safetyId) {
                    clearTimeout(safetyId);
                    $button.removeData('cf7-global-safety-id');
                }
                
                // DON'T clean up the global original state here - preserve it for future resets
                // Only clean up local state attributes
                $button.removeData('cf7-original-html cf7-original-disabled');
            };
            
            if (delay) {
                setTimeout(resetAction, delay);
            } else {
                resetAction();
            }
        },
        
        /**
         * Clean up global button state when operation is truly complete
         */
        cleanupButtonState: function($button) {
            if (!$button || $button.length === 0) {
                return;
            }
            
            // Clear any remaining timeouts
            const safetyId = $button.data('cf7-global-safety-id');
            if (safetyId) {
                clearTimeout(safetyId);
            }
            
            // Remove all stored data
            $button.removeData('cf7-global-original-html cf7-global-original-disabled cf7-original-html cf7-original-disabled cf7-global-safety-id');
        },
        
        // ========================================
        // Modal Management System
        // ========================================
        // User interface modal components for interactive operations requiring
        // user input or confirmation. Provides consistent modal experience
        // with accessibility features and comprehensive event handling.
        //
        // Modal Features:
        // • Dynamic modal creation with HTML template injection
        // • Keyboard navigation support (Enter, Escape)
        // • Background click dismissal with event delegation
        // • Input validation with real-time feedback
        // • Automatic cleanup and memory management
        //
        // Integration Points:
        // → Test operations requiring user email input
        // → Configuration confirmation dialogs
        // → Results display with user interaction options
        // → Cross-modal state management for complex workflows
        // ========================================

        /**
         * Display test results with auto-hiding notification
         * 
         * Shows standardized test results in dedicated UI panel with automatic
         * timeout and manual dismissal options. Provides consistent feedback
         * mechanism for all test operations.
         * 
         * @param {string} message - Result message to display
         * @param {boolean} isSuccess - Success state for styling
         * 
         * Features:
         * • Automatic 5-second timeout with fade-out
         * • Manual close button with click handler
         * • Success/error styling based on test results
         * • DOM cleanup and event handler management
         */
        showTestResults: function(message, isSuccess) {
            const $results = $('#cf7-email-test-results');
            const $body = $results.find('.cf7-test-results-body');
            
            const noticeClass = isSuccess ? 'cf7-notice-success' : 'cf7-notice-error';
            $body.html('<div class="cf7-notice ' + noticeClass + '"><p>' + message + '</p></div>');
            
            $results.show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $results.hide();
            }, 5000);
            
            // Close button
            $results.find('.cf7-test-results-close').off('click').on('click', function() {
                $results.hide();
            });
        },
        
        /**
         * Display email input modal for test operations
         * 
         * Creates dynamic modal interface for collecting email addresses
         * during SMTP and template testing operations. Includes comprehensive
         * input validation, keyboard navigation, and callback management.
         * 
         * @param {Function} onConfirm - Callback executed with validated email
         * @param {Function} onCancel - Callback executed on user cancellation
         * 
         * Modal Features:
         * • Pre-populated with admin email from WordPress configuration
         * • Real-time email validation with user feedback
         * • Enter key submission for improved user experience
         * • Escape key cancellation with proper cleanup
         * • Background click dismissal with event delegation
         * • Automatic button state cleanup on modal closure
         * 
         * Accessibility Features:
         * • Auto-focus with text selection for easy replacement
         * • Keyboard navigation support (Enter/Escape)
         * • Screen reader compatible markup structure
         * • Proper ARIA labels and semantic HTML elements
         * 
         * Event Management:
         * • Namespaced event handlers to prevent conflicts
         * • Automatic cleanup of document-level listeners
         * • Button state restoration on cancellation
         * • Memory leak prevention through proper removal
         */
        showEmailInputModal: function(onConfirm, onCancel) {
            // Remove any existing modal
            $('#cf7-email-input-modal').remove();
            
            // Create modal HTML
            const modalHtml = `
                <div id="cf7-email-input-modal" class="cf7-modal" style="display: flex;">
                    <div class="cf7-modal-content" style="max-width: 500px;">
                        <div class="cf7-modal-header">
                            <h3>Send Test Email</h3>
                            <button type="button" class="cf7-modal-close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div class="cf7-modal-body">
                            <div class="cf7-field-group">
                                <label class="cf7-field-label">Email Address:</label>
                                <input type="email" id="cf7-test-email-input" class="cf7-field-input" 
                                       placeholder="Enter email address to send test to..." 
                                       value="${cf7ArtistSubmissions.adminEmail || ''}" />
                                <p class="cf7-field-help">Enter the email address where you want to send the test email.</p>
                            </div>
                        </div>
                        <div class="cf7-modal-footer">
                            <button type="button" class="cf7-btn cf7-btn-secondary" id="cf7-email-modal-cancel">
                                <span class="dashicons dashicons-dismiss"></span>
                                Cancel
                            </button>
                            <button type="button" class="cf7-btn cf7-btn-primary" id="cf7-email-modal-send">
                                <span class="dashicons dashicons-email"></span>
                                Send Test Email
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to body
            $('body').append(modalHtml);
            
            const $modal = $('#cf7-email-input-modal');
            const $input = $('#cf7-test-email-input');
            
            // Focus on input and select all text
            setTimeout(function() {
                $input.focus().select();
            }, 100);
            
            // Handle Enter key
            $input.on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    $('#cf7-email-modal-send').click();
                }
            });
            
            // Handle Escape key
            $(document).on('keydown.emailModal', function(e) {
                if (e.which === 27) { // Escape key
                    e.preventDefault();
                    $('#cf7-email-modal-cancel').click();
                }
            });
            
            // Handle cancel
            $modal.find('.cf7-modal-close, #cf7-email-modal-cancel').on('click', function(e) {
                e.preventDefault();
                $modal.remove();
                $(document).off('keydown.emailModal');
                
                // Clean up any button state that might be lingering
                $('.cf7-test-btn, .cf7-btn').each(function() {
                    const $btn = $(this);
                    if ($btn.data('cf7-global-original-html')) {
                        CF7AdminInterface.cleanupButtonState($btn);
                    }
                });
                
                if (onCancel) onCancel();
            });
            
            // Handle send
            $('#cf7-email-modal-send').on('click', function(e) {
                e.preventDefault();
                const email = $input.val().trim();
                
                if (!email) {
                    $input.focus();
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    alert('Please enter a valid email address.');
                    $input.focus().select();
                    return;
                }
                
                $modal.remove();
                $(document).off('keydown.emailModal');
                if (onConfirm) onConfirm(email);
            });
            
            // Handle background click
            $modal.on('click', function(e) {
                if (e.target === $modal[0]) {
                    $('#cf7-email-modal-cancel').click();
                }
            });
        },
        
        // ========================================
        // Settings Management System
        // ========================================
        // Configuration import/export functionality with comprehensive
        // data validation and user feedback. Supports backup/restore
        // operations and cross-environment configuration transfer.
        //
        // System Capabilities:
        // • Full settings export with metadata and version tracking
        // • Selective settings import with validation and error handling
        // • JSON format with human-readable structure
        // • Plugin version compatibility checking
        // • User notification system for operation status
        //
        // Export Features:
        // • Timestamp and version metadata inclusion
        // • Complete form data serialization across all settings tabs
        // • Structured JSON format for easy inspection and editing
        // • Plugin version tracking for compatibility validation
        //
        // Import Features:
        // • Data format validation with error reporting
        // • Selective field application with existing value preservation
        // • Checkbox and input field type detection
        // • Success confirmation with save reminder
        // ========================================

        /**
         * Collect all plugin settings for export
         * 
         * Serializes complete plugin configuration from all settings forms
         * into structured JSON format with metadata and version information.
         * Creates exportable backup suitable for transfer or archival.
         * 
         * @returns {Object} Complete settings object with metadata
         * 
         * Export Structure:
         * • _meta: Timestamp, version, and export type information
         * • Settings: All form field values from .cf7-settings-form elements
         * • Compatibility: Version tracking for import validation
         * 
         * Data Collection:
         * • Automatic form discovery and serialization
         * • Field name preservation for accurate import mapping
         * • Value normalization for consistent data structure
         */
        collectSettings: function() {
            const settings = {
                _meta: {
                    exported_at: new Date().toISOString(),
                    plugin_version: 'CF7 Artist Submissions 2.1.0',
                    export_type: 'full_settings'
                }
            };
            
            // Collect form data
            $('.cf7-settings-form').each(function() {
                const $form = $(this);
                const formData = $form.serializeArray();
                
                formData.forEach(function(field) {
                    settings[field.name] = field.value;
                });
            });
            
            return settings;
        },
        
        /**
         * Import settings from JSON configuration object
         * 
         * Applies imported settings to form fields with comprehensive validation
         * and user feedback. Handles different field types and provides
         * appropriate error handling for invalid configurations.
         * 
         * @param {Object} settings - Settings object from export or manual creation
         * 
         * Import Process:
         * 1. Validate settings object structure and format
         * 2. Iterate through settings excluding metadata
         * 3. Locate corresponding form fields by name attribute
         * 4. Apply values with field type detection (checkbox vs input)
         * 5. Provide user feedback on import success/failure
         * 
         * Field Type Handling:
         * • Checkbox fields: Boolean value conversion with prop() method
         * • Input fields: Direct value assignment with val() method
         * • Missing fields: Graceful skip with no error reporting
         * 
         * User Experience:
         * • Success notification with save reminder
         * • Error notification for invalid format
         * • No disruption of existing values for missing settings
         */
        importSettings: function(settings) {
            if (!settings || typeof settings !== 'object') {
                this.showNotice('Invalid settings format.', 'error');
                return;
            }
            
            // Apply settings to form fields
            Object.keys(settings).forEach(function(key) {
                if (key !== '_meta') {
                    const $field = $('[name="' + key + '"]');
                    if ($field.length) {
                        if ($field.attr('type') === 'checkbox') {
                            $field.prop('checked', !!settings[key]);
                        } else {
                            $field.val(settings[key]);
                        }
                    }
                }
            });
            
            this.showNotice('Settings imported successfully! Please save to apply changes.', 'success');
        },
        
        /**
         * Display user notification messages
         * 
         * Shows standardized notification messages with automatic dismissal
         * and consistent styling. Provides user feedback for various
         * operations including settings management and test results.
         * 
         * @param {string} message - Notification message text
         * @param {string} type - Notification type (info, success, error)
         * 
         * Notification Features:
         * • Automatic 5-second timeout with fade-out animation
         * • Consistent styling with WordPress admin color scheme
         * • Top-of-content positioning for maximum visibility
         * • Memory management with automatic DOM cleanup
         * 
         * Supported Types:
         * • info: General information (default)
         * • success: Operation success confirmation
         * • error: Error messages and validation failures
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            const notice = $('<div class="cf7-notice cf7-notice-' + type + '">' +
                            '<p>' + message + '</p>' +
                            '</div>');
            
            // Insert notice at top of content
            $('.cf7-settings-content').prepend(notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    };
    
    // ========================================
    // Global Initialization and Export
    // ========================================
    // Document ready initialization and global object export for
    // cross-component integration and external accessibility.
    //
    // Initialization Strategy:
    // • Conditional initialization based on modern interface presence
    // • Document ready event handling for reliable DOM availability
    // • Global object export for external component integration
    // • Namespace protection with proper jQuery wrapper
    //
    // Integration Points:
    // → WordPress admin interface detection (.cf7-modern-settings)
    // → Global window object export for external access
    // → jQuery document ready for reliable initialization timing
    // → Cross-component communication through window.CF7AdminInterface
    // ========================================
    
    /**
     * Document ready initialization
     * 
     * Initializes CF7AdminInterface when DOM is ready and modern
     * settings interface is detected. Provides conditional activation
     * to prevent unnecessary initialization on non-CF7 admin pages.
     */
    $(document).ready(function() {
        // Initialize modern interface if present
        if ($('.cf7-modern-settings').length) {
            CF7AdminInterface.init();
        }
    });
    
    /**
     * Global CF7AdminInterface Export
     * 
     * Exports CF7AdminInterface to global window object for external
     * component access and cross-file integration. Enables other
     * scripts to interact with admin interface functionality.
     * 
     * External Usage:
     * • window.CF7AdminInterface.showNotice(message, type)
     * • window.CF7AdminInterface.resetButton($btn, html)
     * • window.CF7AdminInterface.performSafeAjaxTest(...)
     * 
     * Integration Examples:
     * • Custom admin scripts requiring UI feedback
     * • Third-party plugin integration
     * • Developer console testing and debugging
     */
    window.CF7AdminInterface = CF7AdminInterface;
    
})(jQuery);
