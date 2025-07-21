/**
 * CF7 Artist Submissions - Modern Admin Interface JavaScript
 * 
 * Handles both legacy functionality and modern settings page interactions.
 * 
 * @package CF7_Artist_Submissions
 * @since 2.1.0
 */
(function($) {
    'use strict';
    
    // Modern Admin Interface Controller
    const CF7AdminInterface = {
        
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
         * Global safety mechanism to ensure buttons never get permanently stuck
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
        
        /**
         * Perform safe AJAX test with automatic button reset fallback
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
        
        testEmailConfig: function($button, originalHtml) {
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'validate_email_config',
                nonce: cf7ArtistSubmissions.nonce
            });
        },
        
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
        
        testImapConnection: function($button, originalHtml) {
            this.performSafeAjaxTest($button, originalHtml, {
                action: 'cf7_test_imap',
                nonce: cf7ArtistSubmissions.conversationNonce
            }, null, function(xhr, status, error) {
                CF7AdminInterface.showTestResults('AJAX request failed: ' + error + ' (Status: ' + status + ')', false);
            });
        },
        
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
    
    // Document ready initialization
    $(document).ready(function() {
        // Initialize modern interface if present
        if ($('.cf7-modern-settings').length) {
            CF7AdminInterface.init();
        }
    });
    
    // Export CF7AdminInterface for external use
    window.CF7AdminInterface = CF7AdminInterface;
    
})(jQuery);
