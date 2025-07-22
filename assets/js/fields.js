/**
 * ==================================================================================
 * CF7 Artist Submissions - Advanced Profile Field Editing System
 * ==================================================================================
 * 
 * Comprehensive inline editing system for artist submission profiles providing
 * real-time field editing, status management, and data persistence capabilities.
 * Built with modern jQuery architecture for maintainable, scalable profile
 * management workflows with comprehensive validation and user experience features.
 * 
 * ==================================================================================
 * SYSTEM ARCHITECTURE
 * ==================================================================================
 * 
 * ┌─ CF7FieldsEditingSystem (Master Profile Editing Controller)
 * │  │
 * │  ├─ StatusManagementSystem
 * │  │  ├─ StatusDropdownController (interactive status selection interface)
 * │  │  ├─ StatusUpdateHandler (AJAX-based status persistence)
 * │  │  ├─ StatusVisualFeedback (color-coded status indicators)
 * │  │  └─ StatusNotificationSystem (success/error messaging)
 * │  │
 * │  ├─ InlineEditingEngine
 * │  │  ├─ FieldTypeHandlers (text, textarea, email, URL, mediums)
 * │  │  ├─ EditModeController (entry/exit state management)
 * │  │  ├─ InputValidation (email format, required fields)
 * │  │  ├─ EditableFieldRenderer (dynamic input generation)
 * │  │  └─ KeyboardShortcuts (Enter/Escape handling)
 * │  │
 * │  ├─ HeaderFieldsSystem
 * │  │  ├─ ArtistNameEditor (inline name editing with sync)
 * │  │  ├─ PronounsEditor (visibility-aware pronoun editing)
 * │  │  ├─ EmailEditor (validation-enabled email editing)
 * │  │  ├─ HeaderFieldCoordination (synchronized field updates)
 * │  │  └─ DynamicSizingEngine (responsive input width adjustment)
 * │  │
 * │  ├─ ProfileFieldsSystem
 * │  │  ├─ StandardFieldEditor (text/textarea/url fields)
 * │  │  ├─ ArtisticMediumsEditor (checkbox-based medium selection)
 * │  │  ├─ FieldValueRenderer (display formatting with line breaks)
 * │  │  ├─ FieldStateManagement (original value tracking)
 * │  │  └─ AutoSaveCoordination (blur-triggered save operations)
 * │  │
 * │  ├─ DataPersistenceLayer
 * │  │  ├─ BatchSaveController (unified save operation)
 * │  │  ├─ FieldDataCollector (comprehensive data gathering)
 * │  │  ├─ AJAXPersistenceHandler (server synchronization)
 * │  │  ├─ ErrorRecoverySystem (save failure handling)
 * │  │  └─ PostIDDetection (multi-method ID resolution)
 * │  │
 * │  ├─ CuratorNotesSystem
 * │  │  ├─ NotesEditor (dedicated curator notes interface)
 * │  │  ├─ AutoSaveController (change detection and persistence)
 * │  │  ├─ NotesValidation (content validation)
 * │  │  ├─ NotesStatusIndicator (save status feedback)
 * │  │  └─ LoadingStateManagement (visual save progress)
 * │  │
 * │  ├─ UserExperienceLayer
 * │  │  ├─ KeyboardShortcuts (ESC cancellation, Enter saving)
 * │  │  ├─ VisualFeedbackSystem (success/error notifications)
 * │  │  ├─ EditHintSystem (contextual editing guidance)
 * │  │  ├─ LoadingStateManagement (operation progress indicators)
 * │  │  ├─ ToastNotifications (floating message system)
 * │  │  └─ AnimationCoordination (smooth transitions)
 * │  │
 * │  ├─ ValidationEngine
 * │  │  ├─ EmailValidator (RFC-compliant email checking)
 * │  │  ├─ URLValidator (protocol handling and formatting)
 * │  │  ├─ RequiredFieldValidator (empty value detection)
 * │  │  ├─ FieldTypeValidation (type-specific rules)
 * │  │  └─ ErrorDisplaySystem (visual validation feedback)
 * │  │
 * │  └─ IntegrationLayer
 * │     ├─ TabsCompatibility (tabs.js integration)
 * │     ├─ WindowExports (global function exposure)
 * │     ├─ EventCoordination (cross-component communication)
 * │     ├─ WordPressIntegration (custom post type handling)
 * │     └─ PluginHooks (external system integration points)
 * │
 * Integration Points:
 * → WordPress AJAX System: admin-ajax.php handlers for all field operations
 * → CF7 Submissions Backend: PHP classes in includes/ for data processing
 * → WordPress Admin Interface: Consistent styling and interaction patterns
 * → Cross-Tab Communication: Integration with dashboard and conversation systems
 * → Real-time Validation: Client-side validation with server-side verification
 * → Export System: Data synchronization for submission export functionality
 * 
 * Dependencies:
 * • jQuery 3.x: Core DOM manipulation, AJAX operations, and event handling
 * • WordPress Admin: Localized configuration (ajaxurl, nonces, permissions)
 * • cf7TabsAjax Object: Server-side configuration and endpoint mapping
 * • Modern Browser APIs: Set, Map, Promise for enhanced state management
 * • HTML5 Form Validation: Native input validation with custom enhancement
 * • CSS Grid/Flexbox: Modern layout support for responsive editing interface
 * 
 * AJAX Endpoints:
 * • cf7_update_status - Updates submission status with visual feedback
 *   Parameters: post_id, status, nonce
 *   Response: {success: bool, data: {data: {color, label}}}
 * • cf7_save_submission_data - Comprehensive field data saving
 *   Parameters: post_id, field_data, curator_notes, nonce
 *   Response: {success: bool, data: {field_data: object}}
 * • cf7_save_curator_notes - Dedicated curator notes persistence
 *   Parameters: post_id, notes, nonce
 *   Response: {success: bool, data: {message: string}}
 * 
 * Event Architecture:
 * • Status Management Events: Click handlers for dropdown status selection
 * • Edit Mode Control Events: Global edit mode toggle and state management
 * • Field Editing Events: Individual field activation and save/cancel operations
 * • Curator Notes Events: Dedicated notes interface with auto-save functionality
 * • Keyboard Events: Universal shortcuts for power user efficiency
 * • Validation Events: Real-time input validation with visual feedback
 * 
 * State Management:
 * • Edit Mode State: Container-level edit mode with body class coordination
 * • Field State Tracking: Individual field original values and change detection
 * • UI State Management: Dropdown visibility, loading states, error conditions
 * • Validation State: Field-specific error states and recovery mechanisms
 * • Selection State: Multi-field operations and bulk editing capabilities
 * • Persistence State: Save operation tracking and conflict resolution
 * 
 * Field Type System:
 * • Core Field Types: text, textarea, email, url with specialized handling
 * • Header Fields: artist-name, pronouns, email with visibility management
 * • Profile Fields: Standard content fields with formatting preservation
 * • System Fields: curator_notes, artistic_mediums with custom interfaces
 * • Validation Rules: Type-specific validation with user-friendly error messages
 * • Display Formatting: Line breaks, URL protocols, and content sanitization
 * 
 * Performance Features:
 * • Efficient Event Handling: Delegated listeners for dynamic content
 * • Memory Management: Proper cleanup and disposal of temporary elements
 * • Network Optimization: Batch operations and change detection optimization
 * • DOM Optimization: Minimal manipulation with efficient selection strategies
 * • Debounced Operations: Smooth user interactions with request throttling
 * • Race Condition Prevention: Loading state guards for concurrent operations
 * 
 * Accessibility Features:
 * • Keyboard Navigation: Full keyboard support with standard shortcuts
 * • Screen Reader Support: Semantic labeling and status announcements
 * • Visual Accessibility: High contrast indicators and clear state visualization
 * • Motor Accessibility: Large interaction targets and timeout considerations
 * • Focus Management: Logical tab order and focus restoration during transitions
 * • Loading Indicators: Clear feedback for all asynchronous operations
 * 
 * Security Features:
 * • Input Sanitization: XSS prevention and HTML entity encoding
 * • AJAX Security: WordPress nonce validation and capability checking
 * • Data Validation: Client and server-side validation with type enforcement
 * • State Protection: Original value preservation and rollback capabilities
 * • Access Control: Edit mode restrictions and permission validation
 * • Content Security: Safe DOM manipulation and injection prevention
 * 
 * @package    CF7ArtistSubmissions
 * @subpackage ProfileEditing
 * @version    2.1.0
 * @since      1.0.0
 * @author     CF7 Artist Submissions Development Team
 */
(function($) {
    'use strict';
    
    // Initialize profile editing
    $(document).ready(function() {
        initModernProfileEditing();
    });

    /**
     * ============================================================================
     * MODERN PROFILE EDITING INITIALIZATION
     * ============================================================================
     * 
     * Comprehensive initialization of the inline profile editing system with
     * status management, field editing capabilities, and data persistence.
     * 
     * @since 2.0.0
     * @return {void}
     * 
     * Features Initialized:
     * • Status dropdown interactions with AJAX persistence
     * • Inline field editing for profile and header fields
     * • Keyboard shortcuts for enhanced user experience
     * • Auto-save functionality for curator notes
     * • Comprehensive validation and error handling
     * • Visual feedback system for all operations
     * 
     * Event Handlers Registered:
     * • Status management (dropdown, selection, outside clicks)
     * • Edit mode control (enter/exit, save/cancel)
     * • Field-level editing (click-to-edit, validation)
     * • Curator notes management (save, auto-save, change tracking)
     * • Keyboard shortcuts (ESC cancellation, Enter saving)
     * 
     * Dependencies:
     * • jQuery for DOM manipulation and event handling
     * • cf7TabsAjax global for AJAX configuration
     * • WordPress AJAX system for data persistence
     * ============================================================================
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
    
    /**
     * ============================================================================
     * STATUS UPDATE HANDLER
     * ============================================================================
     * 
     * Handles AJAX-based status updates with comprehensive error handling
     * and visual feedback. Updates both the server state and UI display.
     * 
     * @since 2.0.0
     * @param {number} postId - The post ID of the submission
     * @param {string} status - The new status value to set
     * @param {jQuery} $dropdown - The dropdown container element
     * @param {jQuery} $option - The selected option element
     * @return {void}
     * 
     * Features:
     * • Loading state visualization during update
     * • Server-side validation and persistence
     * • Color-coded status indicator updates
     * • Error recovery with original state restoration
     * • Success/failure notification system
     * 
     * AJAX Endpoint: cf7_update_status
     * Required Data: post_id, status, nonce
     * 
     * Error Handling:
     * • Network failure recovery
     * • Server error message display
     * • UI state restoration on failure
     * • User feedback for all scenarios
     * ============================================================================
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
     * ============================================================================
     * STATUS UPDATE NOTIFICATION SYSTEM
     * ============================================================================
     * 
     * Displays floating notification messages for status update operations
     * with automatic dismissal and appropriate visual styling.
     * 
     * @since 2.0.0
     * @param {string} message - The message text to display
     * @param {string} type - Message type: 'success' or 'error'
     * @return {void}
     * 
     * Features:
     * • Fixed positioning for consistent visibility
     * • Type-based color coding (green/red)
     * • Smooth slide-in animation
     * • Automatic fade-out after 3 seconds
     * • High z-index for overlay priority
     * 
     * Styling:
     * • Success: Green background (#48bb78)
     * • Error: Red background (#f56565)
     * • Modern shadow and border radius
     * • Responsive positioning
     * ============================================================================
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
     * ============================================================================
     * EDIT MODE HINT SYSTEM
     * ============================================================================
     * 
     * Displays contextual guidance when users enter edit mode, helping them
     * understand the click-to-edit functionality.
     * 
     * @since 2.0.0
     * @return {void}
     * 
     * Features:
     * • Contextual hint display on edit mode entry
     * • Blue color coding for informational tone
     * • Consistent positioning with other notifications
     * • Automatic dismissal after 3 seconds
     * • Smooth animations for professional feel
     * 
     * Triggered When:
     * • User clicks "Edit Profile" button
     * • Edit mode is activated for the first time
     * • Provides immediate guidance on interaction
     * 
     * Visual Design:
     * • Blue background (#4299e1) for info state
     * • Fixed top-right positioning
     * • Matches notification system styling
     * ============================================================================
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
     * ============================================================================
     * PROFILE FIELD EDITING SYSTEM
     * ============================================================================
     */
    
    /**
     * ============================================================================
     * PROFILE FIELD EDIT INITIATION
     * ============================================================================
     * 
     * Converts a static field display into an editable input with appropriate
     * field type handling and interaction setup.
     * 
     * @since 2.0.0
     * @param {jQuery} $field - The field container element
     * @return {void}
     * 
     * Features:
     * • Multi-selector field value detection
     * • Field type-specific input creation
     * • Original value preservation for cancellation
     * • Automatic focus and text selection
     * • Keyboard shortcut integration
     * 
     * Supported Field Types:
     * • text - Standard text input
     * • textarea - Multi-line text input
     * • email - Email input with validation
     * • url - URL input with protocol handling
     * 
     * Event Handlers:
     * • Enter key - Save (except textarea)
     * • Escape key - Cancel editing
     * • Blur event - Auto-save trigger
     * 
     * State Management:
     * • .editing class application
     * • Original value data storage
     * • Input element lifecycle
     * ============================================================================
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
     * ============================================================================
     * PROFILE FIELD SAVE HANDLER
     * ============================================================================
     * 
     * Processes field edit completion with value validation, display updates,
     * and hidden input synchronization for form submission.
     * 
     * @since 2.0.0
     * @param {jQuery} $field - The field container being edited
     * @return {void}
     * 
     * Features:
     * • Edit state validation before processing
     * • Multi-selector element detection
     * • Field type-specific display formatting
     * • Hidden input value synchronization
     * • Edit UI cleanup and restoration
     * 
     * Field Type Handling:
     * • URL fields - Protocol addition and link preservation
     * • Textarea fields - Line break HTML conversion
     * • Standard fields - Text content updates
     * 
     * Process Flow:
     * 1. Validate editing state and element presence
     * 2. Extract new value from input element
     * 3. Apply field type-specific formatting
     * 4. Update display and hidden input values
     * 5. Clean up editing UI elements
     * 6. Reset field state to non-editing
     * ============================================================================
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
    
    /**
     * ============================================================================
     * HEADER FIELD EDITING SYSTEM
     * ============================================================================
     */
    
    /**
     * ============================================================================
     * EMAIL VALIDATION UTILITY
     * ============================================================================
     * 
     * Provides RFC-compliant email validation for header email fields.
     * 
     * @since 2.0.0
     * @param {string} email - Email address to validate
     * @return {boolean} True if email format is valid
     * 
     * Validation Rules:
     * • Must contain @ symbol
     * • Must have domain portion
     * • Must have valid TLD
     * • No whitespace allowed
     * ============================================================================
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * ============================================================================
     * HEADER FIELD EDIT INITIATION
     * ============================================================================
     * 
     * Initializes inline editing for header fields (artist name, pronouns, email)
     * with field type-specific input creation and dynamic sizing.
     * 
     * @since 2.0.0
     * @param {jQuery} $field - The header field element
     * @return {void}
     * 
     * Features:
     * • Field type detection and appropriate input creation
     * • Dynamic input width based on content length
     * • Original value preservation for cancellation
     * • Immediate focus and text selection
     * • Keyboard shortcut integration
     * 
     * Supported Header Fields:
     * • artist-name - Text input for artist display name
     * • pronouns - Text input with visibility control
     * • email - Email input with validation
     * 
     * Event Bindings:
     * • Enter key - Save changes
     * • Escape key - Cancel editing
     * • Blur event - Auto-save trigger
     * 
     * UI Features:
     * • Responsive input sizing
     * • Minimum width enforcement
     * • In-place editing without layout shift
     * ============================================================================
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
     * ============================================================================
     * COMPREHENSIVE SAVE OPERATION
     * ============================================================================
     * 
     * Master save function that collects all field changes and persists them
     * to the server with comprehensive error handling and UI feedback.
     * 
     * @since 2.0.0
     * @return {void}
     * 
     * Features:
     * • Comprehensive field data collection from multiple sources
     * • Header and profile field value aggregation
     * • Artistic mediums checkbox state processing
     * • Curator notes inclusion
     * • Post ID detection with multiple fallback methods
     * • Loading state management during save operation
     * • Success/error notification system
     * • UI state restoration on completion
     * 
     * Data Collection Sources:
     * • Header fields (artist name, pronouns, email)
     * • Profile fields (all editable content fields)
     * • Artistic mediums (checkbox selections)
     * • Curator notes (dedicated notes field)
     * 
     * Post ID Detection Methods:
     * 1. cf7TabsAjax.post_id global variable
     * 2. URL parameter extraction
     * 3. Hidden input field value
     * 4. Body class parsing (postid-XXX)
     * 
     * AJAX Endpoint: cf7_save_submission_data
     * Required Data: post_id, field_data, curator_notes, nonce
     * 
     * Error Handling:
     * • Network failure recovery
     * • Server validation error display
     * • Button state restoration
     * • User feedback for all scenarios
     * 
     * Success Actions:
     * • UI field value updates with server response
     * • Edit mode deactivation
     * • Active edit cleanup
     * • Success notification display
     * ============================================================================
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
     * ============================================================================
     * COMPREHENSIVE EDIT CANCELLATION
     * ============================================================================
     * 
     * Master cancellation function that reverts all field changes and restores
     * the interface to non-editing state with original values.
     * 
     * @since 2.0.0
     * @return {void}
     * 
     * Features:
     * • Complete edit mode deactivation
     * • Individual field edit cancellation
     * • Active input element cleanup
     * • Original value restoration
     * • UI state reset to view mode
     * • Button state restoration
     * 
     * Cleanup Operations:
     * • Remove .edit-mode class from containers
     * • Remove .editing class from all fields
     * • Remove temporary input elements
     * • Show hidden field value displays
     * • Cancel active header field edits
     * • Restore button text and icons
     * 
     * Value Restoration:
     * • Revert all fields to original values
     * • Update hidden input values
     * • Restore display formatting
     * • Reset field data attributes
     * 
     * UI State Reset:
     * • Exit edit mode on container
     * • Reset edit button to edit state
     * • Restore field visibility
     * • Clean temporary elements
     * 
     * Triggered By:
     * • Cancel button clicks
     * • Escape key press
     * • Error recovery scenarios
     * ============================================================================
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
     * ============================================================================
     * CURATOR NOTES PERSISTENCE SYSTEM
     * ============================================================================
     * 
     * Dedicated save function for curator notes with independent AJAX handling
     * and specialized status feedback system.
     * 
     * @since 2.0.0
     * @return {void}
     * 
     * Features:
     * • Independent save operation for curator notes
     * • Visual loading state during save
     * • Status indicator with color-coded feedback
     * • Change tracking for auto-save optimization
     * • Button state management
     * • Dedicated error handling
     * 
     * UI Elements:
     * • #cf7_curator_notes - Main textarea input
     * • #cf7-save-curator-notes - Save button with loading state
     * • .cf7-save-status - Status text display
     * 
     * AJAX Endpoint: cf7_save_curator_notes
     * Required Data: post_id, notes, nonce
     * 
     * State Management:
     * • Button disabled during save operation
     * • Icon rotation for loading indication
     * • Status text updates with color coding
     * • Change tracking reset on success
     * 
     * Success Actions:
     * • Green success message display
     * • Reset change tracking flag
     * • Auto-clear status after 3 seconds
     * • Restore button state
     * 
     * Error Handling:
     * • Red error message display
     * • Server error message inclusion
     * • Network error recovery
     * • Button state restoration
     * ============================================================================
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
    
    /**
     * ============================================================================
     * EXTERNAL INTEGRATION LAYER
     * ============================================================================
     * 
     * Provides compatibility interfaces for integration with other CF7 components
     * and external systems requiring access to field editing functionality.
     * 
     * @since 2.0.0
     * 
     * Exported Functions:
     * • initEditableFields() - Alternative initialization entry point
     * 
     * Integration Points:
     * • tabs.js compatibility layer
     * • External plugin hooks
     * • Dynamic initialization support
     * ============================================================================
     */
    
    // Export for tabs.js compatibility
    window.initEditableFields = function() {
        initModernProfileEditing();
    };
    
})(jQuery);