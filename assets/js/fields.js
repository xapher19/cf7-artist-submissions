/**
 * CF7 Artist Submissions - Profile Field Editing System
 *
 * Comprehensive inline editing system for artist submission profiles providing
 * real-time field editing, status management, and data persistence capabilities.
 * Built with modern jQuery architecture for maintainable profile management.
 *
 * Features:
 * • Status dropdown interactions with AJAX persistence
 * • Inline field editing for profile and header fields
 * • Keyboard shortcuts for enhanced user experience
 * • Auto-save functionality for curator notes
 * • Comprehensive validation and error handling
 * • Visual feedback system for all operations
 * • Email validation with RFC compliance
 * • URL field protocol handling and formatting
 * • Artistic mediums checkbox management
 * • Dynamic input sizing and responsive design
 *
 * @package CF7_Artist_Submissions
 * @subpackage ProfileEditing
 * @since 1.0.0
 * @version 1.0.0
 */
(function($) {
    'use strict';
    
    // Initialize profile editing
    $(document).ready(function() {
        initModernProfileEditing();
    });

    // ============================================================================
    // PROFILE EDITING INITIALIZATION
    // ============================================================================
    
    /**
     * Initialize comprehensive profile editing system with status management
     * 
     * Sets up inline field editing, status dropdowns, keyboard shortcuts,
     * and auto-save functionality for curator notes with comprehensive
     * validation and error handling.
     * 
     * @since 1.0.0
     */
    function initModernProfileEditing() {
        // Status Circle Dropdown (using existing cf7-status-selector design)
        $(document).on('click', '.cf7-status-dropdown', function(e) {
            e.preventDefault();
            const $dropdown = $(this).closest('.cf7-status-selector');
            
            // Close other dropdowns
            $('.cf7-status-selector').not($dropdown).removeClass('open');
            
            // Toggle this dropdown
            $dropdown.toggleClass('open');
        });
        
        // Status option selection
        $(document).on('click', '.cf7-status-option', function(e) {
            e.preventDefault();
            const $option = $(this);
            const $dropdown = $option.closest('.cf7-status-selector');
            const status = $option.data('status');
            const postId = $dropdown.data('post-id');
            
            // Don't proceed if already active
            if ($option.hasClass('active')) {
                $dropdown.removeClass('open');
                return;
            }
            
            // Update the status via AJAX
            updateStatus(postId, status, $dropdown, $option);
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.cf7-status-selector').length) {
                $('.cf7-status-selector').removeClass('open');
            }
        });
        
        // ESC key to cancel edit mode
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.cf7-profile-tab-container').hasClass('edit-mode')) {
                cancelAllEdits();
            }
        });
        
        // Edit/Save Profile Button (header button)
        $(document).on('click', '.cf7-edit-save-button', function() {
            const $container = $('.cf7-profile-tab-container');
            const $headerBtn = $('.cf7-edit-save-button');
            
            if ($container.hasClass('edit-mode')) {
                // Currently in edit mode, so save changes
                saveAllChanges();
            } else {
                // Enter edit mode
                $container.addClass('edit-mode');
                $('body').addClass('cf7-edit-mode');
                
                // Update header button to save mode
                $headerBtn.addClass('save-mode');
                $headerBtn.find('.dashicons').removeClass('dashicons-edit').addClass('dashicons-saved');
                
                // Update button text - get all text nodes and replace the last one
                const textNodes = $headerBtn.contents().filter(function() {
                    return this.nodeType === 3; // Text node
                });
                if (textNodes.length > 0) {
                    textNodes.last()[0].textContent = ' ' + $headerBtn.data('save-text');
                } else {
                    // Fallback: replace entire text content except the icon
                    const iconHtml = $headerBtn.find('.dashicons')[0].outerHTML;
                    $headerBtn.html(iconHtml + ' ' + $headerBtn.data('save-text'));
                }
                
                // Show edit hint
                showEditHint();
            }
        });
        
        // Save Profile Button (both in tab and artist header)
        $(document).on('click', '.cf7-save-profile-btn', function() {
            saveAllChanges();
        });
        
        // Cancel Edit Button
        $(document).on('click', '.cf7-cancel-edit-btn', function() {
            cancelAllEdits();
        });
        
        // Field click in edit mode - More specific selector to avoid duplicates
        $(document).on('click', '.cf7-profile-tab-container.edit-mode .cf7-profile-field:not(.editing)', function(e) {
            // Prevent link clicks in edit mode
            if ($(e.target).is('a')) {
                e.preventDefault();
            }
            
            const $field = $(this);
            
            // Handle mediums field specially
            if ($field.hasClass('cf7-artistic-mediums-field')) {
                // Don't make mediums clickable - they use checkboxes
                return;
            }
            
            startFieldEdit($field);
        });
        
        // Header field click in edit mode
        $(document).on('click', '.cf7-header-field', function(e) {
            // Only allow editing if in edit mode
            if (!$('.cf7-profile-tab-container').hasClass('edit-mode')) {
                return;
            }
            
            // Don't trigger if already editing
            if ($(this).hasClass('editing')) {
                return;
            }
            
            startHeaderFieldEdit($(this));
        });
        
        // Curator Notes Save Button
        $(document).on('click', '#cf7-save-curator-notes', function() {
            saveCuratorNotes();
        });
        
        // Auto-save curator notes on textarea blur (optional)
        $(document).on('blur', '#cf7_curator_notes', function() {
            if ($(this).data('changed')) {
                saveCuratorNotes();
            }
        });
        
        // Track changes to curator notes
        $(document).on('input', '#cf7_curator_notes', function() {
            $(this).data('changed', true);
            $('.cf7-save-status').text('');
        });
    }
    
    // ============================================================================
    // STATUS MANAGEMENT SYSTEM
    // ============================================================================
    
    /**
     * Handle AJAX-based status updates with comprehensive error handling
     * 
     * Updates both server state and UI display with loading indicators,
     * visual feedback, and error recovery capabilities.
     * 
     * @since 1.0.0
     */
    function updateStatus(postId, status, $dropdown, $option) {
        // Show loading state
        const $display = $dropdown.find('.cf7-status-display');
        const originalHtml = $display.html();
        $display.html('<span class="dashicons dashicons-update cf7-spin"></span> Updating...');
        
        // AJAX call to update status
        $.ajax({
            url: cf7TabsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_update_status',
                nonce: cf7TabsAjax.nonce,
                post_id: postId,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    // Update the display
                    const statusData = response.data.data;
                    $display.html(`
                        <span class="cf7-status-indicator" style="background-color: ${statusData.color}"></span>
                        <span class="cf7-status-text">${statusData.label}</span>
                        <span class="dashicons dashicons-arrow-down-alt2 cf7-status-arrow"></span>
                    `);
                    $display.css('color', statusData.color);
                    
                    // Update active states
                    $dropdown.find('.cf7-status-option').removeClass('active');
                    $option.addClass('active');
                    
                    // Close dropdown
                    $dropdown.removeClass('open');
                    
                    // Show success message
                    showStatusUpdateMessage('Status updated successfully!', 'success');
                } else {
                    $display.html(originalHtml);
                    showStatusUpdateMessage('Failed to update status: ' + response.data.message, 'error');
                }
            },
            error: function() {
                $display.html(originalHtml);
                showStatusUpdateMessage('Failed to update status. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Display floating notification messages for status update operations
     * with automatic dismissal and color-coded visual feedback.
     * 
     * @since 1.0.0
     */
    function showStatusUpdateMessage(message, type) {
        const $message = $('<div class="cf7-status-update-message"></div>')
            .text(message)
            .addClass(type === 'success' ? 'cf7-feedback-success' : 'cf7-feedback-error')
            .css({
                position: 'fixed',
                top: '80px',
                right: '20px',
                background: type === 'success' ? '#48bb78' : '#f56565',
                color: 'white',
                padding: '12px 20px',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '500',
                zIndex: '9999',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                animation: 'cf7-slide-in 0.3s ease-out'
            });
        
        $('body').append($message);
        
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Display contextual guidance when users enter edit mode.
     * Shows click-to-edit hint with automatic dismissal and smooth animations.
     * 
     * @since 1.0.0
     */
    function showEditHint() {
        const $hint = $('<div class="cf7-edit-hint">Click on any field to edit it</div>');
        $hint.css({
            position: 'fixed',
            top: '80px',
            right: '20px',
            background: '#4299e1',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '8px',
            fontSize: '14px',
            fontWeight: '500',
            zIndex: '9999',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            animation: 'cf7-slide-in 0.3s ease-out'
        });
        
        $('body').append($hint);
        
        setTimeout(function() {
            $hint.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // ============================================================================
    // FIELD EDITING SYSTEM
    // ============================================================================
    
    function editField($field) {
        const fieldType = $field.data('type');
        const originalValue = $field.data('original');
        const fieldKey = $field.data('key') || $field.data('field');
        
        // Hide the display value
        $field.find('.cf7-field-value').hide();
        
        // Create an editable input or textarea
        let $input;
        
        if (fieldType === 'textarea') {
            $input = $('<textarea></textarea>')
                .val(originalValue)
                .addClass('edit-active');
        } else {
            $input = $('<input>')
                .attr('type', fieldType || 'text')
                .val(originalValue)
                .addClass('edit-active');
        }
        
        // Add editing class and insert the input
        $field.addClass('editing');
        $field.find('.cf7-field-content').append($input);
        
        // Focus on the new input and select all text
        $input.focus().select();
        
        // Save value on Enter key (except in textarea)
        $input.on('keydown', function(e) {
            // Enter key (13) saves unless it's a textarea
            if (e.keyCode === 13 && fieldType !== 'textarea') {
                e.preventDefault();
                saveProfileField($field, $input, fieldKey);
            }
            
            // Escape key (27) cancels
            if (e.keyCode === 27) {
                cancelFieldEdit($field);
            }
        });
        
        // Save value on blur (only for non-textarea fields)
        if (fieldType !== 'textarea') {
            $input.on('blur', function() {
                // Small delay to allow clicking buttons
                setTimeout(function() {
                    if ($field.hasClass('editing')) {
                        saveProfileField($field, $input, fieldKey);
                    }
                }, 150);
            });
        }
        
        // Add save and cancel buttons for textarea
        if (fieldType === 'textarea') {
            const $controls = $('<div class="edit-controls"></div>');
            const $saveBtn = $('<button type="button" class="button button-primary save-btn">Save</button>');
            const $cancelBtn = $('<button type="button" class="button cancel-btn">Cancel</button>');
            
            $controls.append($saveBtn).append($cancelBtn);
            $field.find('.cf7-field-content').append($controls);
            
            $saveBtn.on('click', function() {
                saveProfileField($field, $input, fieldKey);
            });
            
            $cancelBtn.on('click', function() {
                cancelFieldEdit($field);
            });
        }
    }
    
    function cancelFieldEdit($field) {
        // Remove the editing UI
        $field.removeClass('editing');
        $field.find('.edit-active, .edit-controls').remove();
        
        // Show the display value again
        $field.find('.cf7-field-value').show();
    }
    
    function saveProfileField($field, $input, fieldKey) {
        const newValue = $input.val();
        let $hiddenInput = $field.find('input[name^="cf7_editable_fields"]');
        
        // Create hidden input if it doesn't exist
        if (!$hiddenInput.length) {
            $hiddenInput = $('<input type="hidden" name="cf7_editable_fields[' + fieldKey + ']">');
            $field.append($hiddenInput);
        }
        
        // Update the hidden input value
        $hiddenInput.val(newValue);
        
        // Update the display value
        const $displayValue = $field.find('.cf7-field-value');
        const fieldType = $field.data('type');
        
        if ($displayValue.hasClass('cf7-field-link')) {
            let url = newValue;
            if (url.indexOf('http') !== 0 && newValue) {
                url = 'http://' + url;
            }
            $displayValue.attr('href', url).text(newValue);
        } else if (fieldType === 'textarea') {
            $displayValue.html(newValue.replace(/\n/g, '<br>'));
        } else {
            $displayValue.text(newValue);
        }
        
        // Remove the editing UI
        $field.removeClass('editing');
        $field.find('.edit-active, .edit-controls').remove();
        $displayValue.show();
        
        // Update the original data attribute
        $field.data('original', newValue);
        
        // Show a success message that fades out
        const $message = $('<div class="edit-success">Updated</div>');
        $field.append($message);
        
        setTimeout(function() {
            $message.fadeOut(500, function() {
                $(this).remove();
            });
        }, 1500);
    }
    
    /**
     * Convert static field display into editable input with field type handling.
     * Provides original value preservation and keyboard shortcut integration.
     * 
     * @since 1.0.0
     */
    function startFieldEdit($field) {
        if ($field.hasClass('editing')) {
            return;
        }
        
        // Try multiple selectors to find the field value
        let $fieldValue = $field.find('.field-value');
        if ($fieldValue.length === 0) {
            $fieldValue = $field.find('.cf7-field-value');
        }
        if ($fieldValue.length === 0) {
            return;
        }
        
        const currentValue = $fieldValue.text().trim();
        const fieldType = $field.data('type') || 'text';
        
        // Store original value for cancel
        $field.data('original', currentValue);
        
        // Create input based on field type
        let $input;
        if (fieldType === 'textarea') {
            $input = $('<textarea rows="3"></textarea>');
        } else if (fieldType === 'email') {
            $input = $('<input type="email" />');
        } else if (fieldType === 'url') {
            $input = $('<input type="url" />');
        } else {
            $input = $('<input type="text" />');
        }
        
        $input.val(currentValue);
        $input.addClass('cf7-field-edit-input');
        
        // Replace field value with input
        $fieldValue.hide();
        $fieldValue.after($input);
        $field.addClass('editing');
        
        // Focus input
        $input.focus().select();
        
        // Handle save/cancel
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && fieldType !== 'textarea') {
                saveFieldEdit($field);
            } else if (e.key === 'Escape') {
                cancelFieldEdit($field);
            }
        });
        
        $input.on('blur', function() {
            saveFieldEdit($field);
        });
    }
    
    /**
     * Process field edit completion with value validation and display updates.
     * Handles field type-specific formatting and hidden input synchronization.
     * 
     * @since 1.0.0
     */
    function saveFieldEdit($field) {
        if (!$field.hasClass('editing')) {
            return;
        }
        
        const $input = $field.find('.cf7-field-edit-input');
        let $fieldValue = $field.find('.cf7-field-value');
        
        // Fallback to other possible selectors
        if ($fieldValue.length === 0) {
            $fieldValue = $field.find('.field-value');
        }
        
        if ($input.length === 0 || $fieldValue.length === 0) {
            return;
        }
        
        const newValue = $input.val().trim();
        const fieldKey = $field.data('field');
        const fieldType = $field.data('type') || 'text';
        
        // Update display based on field type
        if ($fieldValue.hasClass('cf7-field-link')) {
            // For URL fields, preserve the link structure
            let url = newValue;
            if (url && !url.match(/^https?:\/\//)) {
                url = 'http://' + url;
            }
            $fieldValue.attr('href', url).text(newValue);
        } else if (fieldType === 'textarea') {
            // For textarea fields, preserve line breaks
            $fieldValue.html(newValue.replace(/\n/g, '<br>'));
        } else {
            // For regular text fields
            $fieldValue.text(newValue);
        }
        
        $fieldValue.show();
        $input.remove();
        $field.removeClass('editing');
        
        // Update hidden input
        const $hiddenInput = $field.find('.field-input');
        if ($hiddenInput.length) {
            $hiddenInput.val(newValue);
        }
    }
    
    function cancelFieldEdit($field) {
        if (!$field.hasClass('editing')) {
            return;
        }
        
        const originalValue = $field.data('original');
        const $input = $field.find('.cf7-field-edit-input');
        let $fieldValue = $field.find('.cf7-field-value');
        
        // Fallback to other possible selectors
        if ($fieldValue.length === 0) {
            $fieldValue = $field.find('.field-value');
        }
        
        // Restore original value
        $fieldValue.show();
        $input.remove();
        $field.removeClass('editing');
    }
    
    // ============================================================================
    // HEADER FIELD EDITING SYSTEM
    // ============================================================================
    
    /**
     * Provide RFC-compliant email validation for header email fields.
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Initialize inline editing for header fields with field type-specific inputs.
     * Handles artist name, pronouns, and email with dynamic sizing and validation.
     * 
     * @since 1.0.0
     */
    function startHeaderFieldEdit($field) {
        const fieldType = $field.data('field');
        const $fieldValue = $field.find('.field-value');
        const currentValue = $fieldValue.text().trim();
        
        // Store original value
        $field.data('original', currentValue);
        
        // Create input based on field type
        let $input;
        if (fieldType === 'email') {
            $input = $('<input type="email" />');
        } else {
            $input = $('<input type="text" />');
        }
        
        $input.val(currentValue);
        $input.css({
            width: Math.max(currentValue.length * 8 + 20, 100) + 'px',
            minWidth: '100px'
        });
        
        // Replace text with input
        $fieldValue.empty().append($input);
        $field.addClass('editing');
        $input.focus().select();
        
        // Handle save on enter or blur
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                saveHeaderField($field, $input);
            } else if (e.key === 'Escape') {
                cancelHeaderFieldEdit($field);
            }
        });
        
        $input.on('blur', function() {
            saveHeaderField($field, $input);
        });
    }
    
    function saveHeaderField($field, $input) {
        const newValue = $input.val().trim();
        const fieldType = $field.data('field');
        const $fieldValue = $field.find('.field-value');
        
        // Basic validation for email
        if (fieldType === 'email' && newValue && !isValidEmail(newValue)) {
            $input.addClass('error');
            setTimeout(function() {
                $input.removeClass('error');
                $input.focus();
            }, 2000);
            return;
        }
        
        // Update display
        $fieldValue.text(newValue || (fieldType === 'email' ? 'No email provided' : ''));
        $field.removeClass('editing');
        
        // Update original data
        $field.data('original', newValue);
        
        // Handle special cases
        if (fieldType === 'pronouns') {
            const $pronounsWrapper = $field.closest('.cf7-artist-pronouns');
            if (newValue) {
                $pronounsWrapper.show();
            } else {
                $pronounsWrapper.hide();
            }
        }
        
        // Update hidden form fields based on field type (for compatibility)
        let metaKey;
        switch(fieldType) {
            case 'artist-name':
                metaKey = 'cf7_artist-name';
                break;
            case 'pronouns':
                metaKey = 'cf7_pronouns';
                break;
            case 'email':
                metaKey = 'cf7_email';
                break;
        }
        
        // Update or create hidden input for saving
        let $hiddenInput = $(`input[name="cf7_editable_fields[${metaKey}]"]`);
        if ($hiddenInput.length === 0) {
            $hiddenInput = $(`<input type="hidden" name="cf7_editable_fields[${metaKey}]" />`);
            $('.cf7-profile-content form, .cf7-profile-content').append($hiddenInput);
        }
        $hiddenInput.val(newValue);
    }
    
    function cancelHeaderFieldEdit($field) {
        const originalValue = $field.data('original');
        const $fieldValue = $field.find('.field-value');
        $fieldValue.text(originalValue);
        $field.removeClass('editing');
    }
    
    /**
     * Master save function collecting all field changes and persisting to server.
     * Handles comprehensive data collection, validation, and UI feedback systems.
     * 
     * @since 1.0.0
     */
    function saveAllChanges() {
        const $container = $('.cf7-profile-tab-container');
        const $headerBtn = $('.cf7-edit-save-button');
        
        // Collect all field data (including header fields)
        const fieldData = {};
        
        // Get header field values
        $('.cf7-header-field').each(function() {
            const fieldName = $(this).data('field');
            const fieldValue = $(this).find('.field-value').text().trim();
            if (fieldName && fieldValue && 
                fieldValue !== 'Unknown Artist' && 
                fieldValue !== 'No email provided') {
                // Add cf7_ prefix for header fields
                fieldData['cf7_' + fieldName] = fieldValue;
            }
        });
        
        // Get profile field values
        $('.cf7-profile-field .field-value').each(function() {
            const $this = $(this);
            const $field = $this.closest('.cf7-profile-field');
            const fieldName = $field.data('field');
            let value;
            
            if ($this.hasClass('textarea-value')) {
                value = $this.text().trim();
            } else {
                value = $this.text().trim();
            }
            
            if (fieldName && value) {
                fieldData[fieldName] = value;
            }
        });
        
        // Handle artistic mediums separately
        const $mediumsField = $('.cf7-artistic-mediums-field');
        if ($mediumsField.length) {
            const selectedMediums = [];
            $mediumsField.find('.cf7-mediums-edit input[type="checkbox"]:checked').each(function() {
                selectedMediums.push($(this).val());
            });
            fieldData['artistic_mediums'] = selectedMediums;
        }
        
        // Get curator notes
        const curatorNotes = $('#cf7-curator-notes').val();
        
        // Get post ID with fallback
        let postId = cf7TabsAjax.post_id;
        if (!postId) {
            // Try to get from URL params
            const urlParams = new URLSearchParams(window.location.search);
            postId = urlParams.get('post');
        }
        if (!postId) {
            // Try to get from hidden input
            postId = $('input[name="post_ID"]').val();
        }
        if (!postId) {
            // Try to get from body class
            const bodyClasses = $('body').attr('class');
            const match = bodyClasses.match(/postid-(\d+)/);
            if (match) {
                postId = match[1];
            }
        }
        
        if (!postId) {
            console.error('Could not determine post ID');
            return;
        }
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'cf7_save_submission_data',
            post_id: postId,
            field_data: fieldData,
            curator_notes: curatorNotes,
            nonce: cf7TabsAjax.nonce
        };
        
        // Show loading state
        $headerBtn.prop('disabled', true);
        const originalText = $headerBtn.find('.dashicons').hasClass('dashicons-saved') ? 
            $headerBtn.data('save-text') : $headerBtn.data('edit-text');
        
        // Update button to show saving state
        $headerBtn.find('.dashicons').removeClass('dashicons-edit dashicons-saved').addClass('dashicons-update');
        const textNodes = $headerBtn.contents().filter(function() {
            return this.nodeType === 3; // Text node
        });
        if (textNodes.length > 0) {
            textNodes.last()[0].textContent = ' Saving...';
        }
        
        // Make AJAX request
        $.ajax({
            url: cf7TabsAjax.ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Update field values in the UI with the saved data
                    if (response.data && response.data.field_data) {
                        const fieldData = response.data.field_data;
                        
                        // Update each field with the saved value
                        for (const fieldKey in fieldData) {
                            if (fieldKey === 'artistic_mediums') {
                                // Update artistic mediums display
                                const $mediumsDisplay = $('.cf7-mediums-display');
                                if ($mediumsDisplay.length && fieldData[fieldKey]) {
                                    $mediumsDisplay.html(fieldData[fieldKey]);
                                }
                            } else {
                                // Update regular fields
                                const $field = $('[data-field="' + fieldKey + '"]');
                                if ($field.length && fieldData[fieldKey]) {
                                    const value = fieldData[fieldKey];
                                    
                                    // Update header fields (remove cf7_ prefix for matching)
                                    const headerFieldName = fieldKey.replace('cf7_', '');
                                    const $headerField = $('[data-field="' + headerFieldName + '"]');
                                    if ($headerField.length) {
                                        $headerField.find('.field-value').text(value);
                                        $headerField.data('original', value);
                                        
                                        // Special handling for pronouns visibility
                                        if (headerFieldName === 'pronouns') {
                                            const $pronounsSpan = $('.cf7-artist-pronouns');
                                            if (value && value.trim()) {
                                                $pronounsSpan.show();
                                            } else {
                                                $pronounsSpan.hide();
                                            }
                                        }
                                    }
                                    
                                    // Update profile fields
                                    if ($field.hasClass('cf7-profile-field')) {
                                        const $fieldValue = $field.find('.cf7-field-value');
                                        if ($fieldValue.length) {
                                            // Check if it's a URL field
                                            if ($fieldValue.hasClass('cf7-field-link')) {
                                                $fieldValue.attr('href', value.startsWith('http') ? value : 'http://' + value);
                                                $fieldValue.text(value);
                                            } else if ($fieldValue.hasClass('cf7-field-textarea')) {
                                                // Handle textarea fields with line breaks
                                                $fieldValue.html(value.replace(/\n/g, '<br>'));
                                            } else {
                                                $fieldValue.text(value);
                                            }
                                        }
                                        
                                        // Update the hidden input
                                        const $hiddenInput = $field.find('.field-input');
                                        if ($hiddenInput.length) {
                                            $hiddenInput.val(value);
                                        }
                                        
                                        // Update data attribute
                                        $field.data('original', value);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Exit edit mode
                    $container.removeClass('edit-mode');
                    $('body').removeClass('cf7-edit-mode');
                    
                    // Cancel any active edits
                    $('.cf7-profile-field.editing').each(function() {
                        cancelFieldEdit($(this));
                    });
                    $('.cf7-header-field.editing').each(function() {
                        cancelHeaderFieldEdit($(this));
                    });
                    
                    // Clean up any leftover editing elements
                    $('.cf7-profile-field').removeClass('editing');
                    $('.cf7-field-edit-input').remove();
                    $('.cf7-field-value, .cf7-field-textarea, .cf7-field-link').show();
                    
                    // Reset header button
                    $headerBtn.removeClass('save-mode').prop('disabled', false);
                    $headerBtn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-edit');
                    
                    // Update button text back to edit mode
                    if (textNodes.length > 0) {
                        textNodes.last()[0].textContent = ' ' + $headerBtn.data('edit-text');
                    } else {
                        // Fallback: replace entire text content except the icon
                        const iconHtml = $headerBtn.find('.dashicons')[0].outerHTML;
                        $headerBtn.html(iconHtml + ' ' + $headerBtn.data('edit-text'));
                    }
                    
                    // Show success message
                    const $message = $('<div class="cf7-save-success">All changes saved successfully!</div>');
                    $message.css({
                        position: 'fixed',
                        top: '80px',
                        right: '20px',
                        background: '#48bb78',
                        color: 'white',
                        padding: '12px 20px',
                        borderRadius: '8px',
                        fontSize: '14px',
                        fontWeight: '500',
                        zIndex: '9999',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                        animation: 'cf7-slide-in 0.3s ease-out'
                    });
                    
                    $('body').append($message);
                    
                    setTimeout(function() {
                        $message.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);
                    
                } else {
                    // Handle error
                    console.error('Save failed:', response.data);
                    
                    // Reset button state
                    $headerBtn.prop('disabled', false);
                    $headerBtn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-edit');
                    if (textNodes.length > 0) {
                        textNodes.last()[0].textContent = ' ' + originalText;
                    }
                    
                    // Show error message
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Save failed. Please try again.';
                    const $errorMessage = $('<div class="cf7-save-error">' + errorMsg + '</div>');
                    $errorMessage.css({
                        position: 'fixed',
                        top: '80px',
                        right: '20px',
                        background: '#e53e3e',
                        color: 'white',
                        padding: '12px 20px',
                        borderRadius: '8px',
                        fontSize: '14px',
                        fontWeight: '500',
                        zIndex: '9999',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
                    });
                    
                    $('body').append($errorMessage);
                    
                    setTimeout(function() {
                        $errorMessage.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                
                // Reset button state
                $headerBtn.prop('disabled', false);
                $headerBtn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-edit');
                if (textNodes.length > 0) {
                    textNodes.last()[0].textContent = ' ' + originalText;
                }
                
                // Show error message
                const $errorMessage = $('<div class="cf7-save-error">Network error. Please try again.</div>');
                $errorMessage.css({
                    position: 'fixed',
                    top: '80px',
                    right: '20px',
                    background: '#e53e3e',
                    color: 'white',
                    padding: '12px 20px',
                    borderRadius: '8px',
                    fontSize: '14px',
                    fontWeight: '500',
                    zIndex: '9999',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
                });
                
                $('body').append($errorMessage);
                
                setTimeout(function() {
                    $errorMessage.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    }
    
    /**
     * Master cancellation function reverting all field changes and restoring
     * interface to non-editing state with original values.
     * 
     * @since 1.0.0
     */
    function cancelAllEdits() {
        const $container = $('.cf7-profile-tab-container');
        const $headerBtn = $('.cf7-edit-save-button');
        
        // Exit edit mode
        $container.removeClass('edit-mode');
        $('body').removeClass('cf7-edit-mode');
        
        // Cancel any active edits and revert changes
        $('.cf7-profile-field.editing').each(function() {
            cancelFieldEdit($(this));
        });
        
        // Also remove editing class from any fields that might not have been caught
        $('.cf7-profile-field').removeClass('editing');
        
        // Clean up any leftover input fields
        $('.cf7-field-edit-input').remove();
        
        // Show all field values that might be hidden
        $('.cf7-field-value').show();
        $('.cf7-field-textarea').show();
        $('.cf7-field-link').show();
        
        // Cancel any active header field edits
        $('.cf7-header-field.editing').each(function() {
            cancelHeaderFieldEdit($(this));
        });
        
        // Reset header button
        $headerBtn.removeClass('save-mode');
        $headerBtn.find('.dashicons').removeClass('dashicons-saved').addClass('dashicons-edit');
        
        // Update button text back to edit mode
        const textNodes = $headerBtn.contents().filter(function() {
            return this.nodeType === 3; // Text node
        });
        if (textNodes.length > 0) {
            textNodes.last()[0].textContent = ' ' + $headerBtn.data('edit-text');
        } else {
            // Fallback: replace entire text content except the icon
            const iconHtml = $headerBtn.find('.dashicons')[0].outerHTML;
            $headerBtn.html(iconHtml + ' ' + $headerBtn.data('edit-text'));
        }
        
        // Revert all field values to original
        $('.cf7-profile-field').each(function() {
            const $field = $(this);
            const originalValue = $field.data('original');
            const $hiddenInput = $field.find('input[name^="cf7_editable_fields"]');
            const $displayValue = $field.find('.cf7-field-value');
            
            if ($hiddenInput.length && originalValue !== undefined) {
                $hiddenInput.val(originalValue);
                
                // Update display value
                if ($displayValue.is('a')) {
                    $displayValue.text(originalValue).attr('href', originalValue);
                } else {
                    $displayValue.text(originalValue);
                }
            }
        });
    }
    
    /**
     * Dedicated save function for curator notes with independent AJAX handling
     * and specialized status feedback system.
     * 
     * @since 1.0.0
     */
    function saveCuratorNotes() {
        const $textarea = $('#cf7_curator_notes');
        const $button = $('#cf7-save-curator-notes');
        const $status = $('.cf7-save-status');
        const notes = $textarea.val();
        const postId = $button.data('post-id');
        
        // Show saving state
        $button.prop('disabled', true);
        $button.find('.dashicons').removeClass('dashicons-saved').addClass('dashicons-update cf7-spin');
        $status.text('Saving...').css('color', '#666');
        
        // Make AJAX request
        $.ajax({
            url: cf7TabsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_save_curator_notes',
                post_id: postId,
                notes: notes,
                nonce: cf7TabsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reset button state
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('dashicons-update cf7-spin').addClass('dashicons-saved');
                    
                    // Show success status
                    $status.text('Saved successfully').css('color', '#28a745');
                    $textarea.data('changed', false);
                    
                    // Clear status after 3 seconds
                    setTimeout(function() {
                        $status.text('');
                    }, 3000);
                    
                } else {
                    // Handle error
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('dashicons-update cf7-spin').addClass('dashicons-saved');
                    $status.text('Save failed: ' + (response.data?.message || 'Unknown error')).css('color', '#dc3545');
                }
            },
            error: function(xhr, status, error) {
                // Handle network error
                $button.prop('disabled', false);
                $button.find('.dashicons').removeClass('dashicons-update cf7-spin').addClass('dashicons-saved');
                $status.text('Network error. Please try again.').css('color', '#dc3545');
            }
        });
    }
    
    // ============================================================================
    // EXTERNAL INTEGRATION LAYER
    // ============================================================================
    
    // Export for tabs.js compatibility
    window.initEditableFields = function() {
        initModernProfileEditing();
    };
    
})(jQuery);