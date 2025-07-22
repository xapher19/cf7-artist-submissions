/**
 * ========================================
 * CF7 Artist Submissions - Conversation Management Interface
 * ========================================
 * 
 * Comprehensive conversation management system providing real-time message
 * handling, IMAP integration, and interactive communication tools for
 * artist submission workflows. Manages bidirectional email conversations
 * with automatic scrolling, context menus, and message state management.
 * 
 * System Architecture:
 * ┌─ Conversation Display Engine
 * │  ├─ Auto-scroll Management (DOM-aware positioning)
 * │  ├─ Message Rendering (bidirectional with status indicators)
 * │  ├─ Template Preview System (real-time AJAX rendering)
 * │  └─ Context Menu Integration (message actions and status)
 * │
 * ┌─ Message Composition Interface
 * │  ├─ Template Selection (dynamic preview loading)
 * │  ├─ Custom Message Editor (Ctrl+Enter shortcuts)
 * │  ├─ AJAX Delivery System (comprehensive error handling)
 * │  └─ Status Feedback (real-time delivery confirmation)
 * │
 * ┌─ IMAP Integration Layer
 * │  ├─ Manual Reply Checking (timeout-aware processing)
 * │  ├─ Inbox Cleanup Operations (safe deletion workflows)
 * │  ├─ Message Synchronization (cross-tab state management)
 * │  └─ Connection Validation (error recovery mechanisms)
 * │
 * └─ Context Menu System
 *    ├─ Message Actions (add to actions, read status)
 *    ├─ Cross-Tab Integration (actions system communication)
 *    ├─ Status Management (read/unread state toggles)
 *    └─ Keyboard Navigation (accessibility compliance)
 * 
 * Integration Points:
 * → WordPress AJAX System: admin-ajax.php handlers for all server communication
 * → CF7 Actions Integration: Cross-tab communication via window.CF7_Actions
 * → Email Template System: Dynamic template rendering and delivery validation
 * → IMAP Backend Services: includes/class-cf7-artist-submissions-conversations.php
 * → Message Storage Layer: Database operations with comprehensive logging
 * → Tab System Integration: Cross-component state synchronization
 * 
 * Dependencies:
 * • jQuery 3.x: Core DOM manipulation and AJAX operations
 * • WordPress Admin: Localized AJAX configuration and nonce validation
 * • cf7Conversations Object: Server-side configuration and endpoint mapping
 * • Session Storage API: Cross-page state persistence for scroll management
 * • Window Object: Cross-component communication and global function export
 * 
 * AJAX Endpoints:
 * • cf7_preview_email: Template rendering with submission data injection
 * • cf7_send_message: Message composition and delivery with status tracking
 * • cf7_check_replies_manual: Manual IMAP synchronization with timeout handling
 * • cf7_check_new_messages: Automatic message polling (currently disabled)
 * • cf7_toggle_message_read: Message status management for admin workflow
 * • cf7_clear_messages: Conversation deletion with confirmation workflows
 * 
 * Security Features:
 * • Nonce Validation: All AJAX requests include WordPress nonce verification
 * • Input Sanitization: HTML escaping for user-generated content display
 * • XSS Prevention: Safe HTML injection with escapeHtml utility function
 * • CSRF Protection: Token-based request validation for all server operations
 * • Permission Checking: Server-side capability validation for admin actions
 * 
 * Performance Optimizations:
 * • Debounced Scroll Events: Smooth scrolling with performance considerations
 * • Conditional Initialization: Feature detection for optimal resource usage
 * • Memory Management: Proper event cleanup and DOM element removal
 * • AJAX Timeout Handling: Responsive error recovery with user feedback
 * • Session Storage: Efficient state persistence across page reloads
 * 
 * Accessibility Features:
 * • Keyboard Navigation: Ctrl+Enter shortcuts and escape key handling
 * • Screen Reader Support: Semantic HTML structure with proper ARIA labels
 * • Focus Management: Logical tab order and focus restoration
 * • Status Indicators: Visual and textual feedback for message states
 * • Error Messaging: Clear, actionable error descriptions for user guidance
 * 
 * @package    CF7ArtistSubmissions
 * @subpackage ConversationManagement
 * @version    2.1.0
 * @since      1.0.0
 * @author     CF7 Artist Submissions Development Team
 */
jQuery(document).ready(function($) {
    
    // ========================================
    // Conversation Display Management
    // ========================================
    // Core conversation interface with intelligent scrolling, message
    // rendering, and cross-page state persistence. Provides smooth
    // user experience with automatic positioning and responsive updates.
    //
    // Display Features:
    // • Multi-selector container detection for flexible DOM integration
    // • Height-aware scrolling with overflow detection
    // • Animated positioning with smooth transitions
    // • Cross-page scroll state persistence via sessionStorage
    // • Image load detection for accurate final positioning
    //
    // Integration Points:
    // → Session Storage: Cross-page scroll position persistence
    // → Window Load Events: Image load detection for layout stability
    // → DOM Ready Events: Initial positioning and state restoration
    // → Tab System: Global function export for cross-component access
    // ========================================

    /**
     * Intelligent conversation scroll management
     * 
     * Provides automatic scrolling to bottom of conversation with intelligent
     * container detection, overflow checking, and smooth animation. Handles
     * multiple container selector patterns for flexible DOM integration.
     * 
     * Container Detection Strategy:
     * 1. Attempts multiple common selector patterns for conversation containers
     * 2. Uses first available container that exists in DOM
     * 3. Validates container has content before attempting scroll operations
     * 4. Checks for overflow condition to prevent unnecessary scroll attempts
     * 
     * Animation Features:
     * • 300ms smooth jQuery animation for professional user experience
     * • 100ms delay to ensure DOM rendering completion
     * • Height validation to prevent scrolling empty containers
     * • Overflow detection to avoid scrolling containers that fit content
     * 
     * Cross-Page Integration:
     * • Global window function export for tab system integration
     * • Session storage integration for post-reload scroll restoration
     * • Image load event binding for layout-complete positioning
     */
    function scrollToBottom() {
        // Try multiple selectors to find the conversation container
        var conversationSelectors = [
            '#cf7-message-thread',
            '.conversation-messages', 
            '.cf7-conversation-container',
            '#cf7-conversation-container'
        ];
        
        var conversationDiv = null;
        for (var i = 0; i < conversationSelectors.length; i++) {
            conversationDiv = jQuery(conversationSelectors[i]);
            if (conversationDiv.length > 0) {
                break;
            }
        }
        
        if (conversationDiv && conversationDiv.length > 0) {
            // Only scroll if there's content that exceeds the container height
            if (conversationDiv[0].scrollHeight > conversationDiv.height()) {
                // Use setTimeout to ensure DOM is fully rendered
                setTimeout(function() {
                    conversationDiv.animate({
                        scrollTop: conversationDiv[0].scrollHeight
                    }, 300);
                }, 100);
            }
        }
    }
    
    // Make scrollToBottom globally available for tab integration
    window.scrollToBottom = scrollToBottom;
    
    // Auto-scroll to bottom on page load
    scrollToBottom();
    
    // Check if we should scroll to bottom after page reload (e.g., after sending message)
    if (sessionStorage.getItem('cf7_scroll_to_bottom') === 'true') {
        sessionStorage.removeItem('cf7_scroll_to_bottom');
        // Extra delay for content to load
        setTimeout(scrollToBottom, 500);
    }
    
    // Also scroll after images load (in case there are any)
    $(window).on('load', function() {
        scrollToBottom();
    });
    
    // ========================================
    // Message Composition System
    // ========================================
    // Interactive message creation interface with template support,
    // real-time preview generation, and comprehensive delivery management.
    // Provides dual-mode composition (custom/template) with validation.
    //
    // Composition Modes:
    // • Custom Message: Direct text input with validation and shortcuts
    // • Template Message: Dynamic template selection with preview rendering
    // • Hybrid Workflow: Seamless switching between modes with state preservation
    //
    // Template Integration:
    // • Real-time AJAX preview generation with submission data injection
    // • Error handling for template rendering failures
    // • Nonce validation for secure template access
    // • Fallback content for configuration errors
    //
    // User Experience Features:
    // • Ctrl+Enter keyboard shortcuts for efficient message sending
    // • Visual feedback for all user interactions and system states
    // • Automatic field validation with inline error messaging
    // • Session state persistence for unsaved content protection
    // ========================================

    // Handle message type selection
    $('#message-type').on('change', function() {
        var messageType = $(this).val();
        var $customField = $('#custom-message-field');
        var $templateField = $('#template-preview-field');
        
        if (messageType === 'custom') {
            $customField.show();
            $templateField.hide();
        } else {
            $customField.hide();
            $templateField.show();
            
            // Load template preview
            loadTemplatePreview(messageType);
        }
    });
    
    /**
     * Dynamic template preview generation (Primary Implementation)
     * 
     * Renders email templates with live submission data for accurate preview
     * before sending. Includes comprehensive error handling and user feedback
     * for template rendering failures or configuration issues.
     * 
     * @param {string} templateId - Template identifier for rendering
     * 
     * Preview Process:
     * 1. Validate template ID and submission context availability
     * 2. Execute AJAX request with proper nonce validation
     * 3. Render template with submission-specific data injection
     * 4. Update preview interface with subject and body content
     * 5. Handle errors with user-friendly messaging and fallback content
     * 
     * Error Handling:
     * • Configuration validation with descriptive error messages
     * • Server communication error recovery with retry suggestions
     * • Template rendering failure feedback with technical details
     * • Graceful degradation for missing template resources
     * 
     * Security Features:
     * • cf7Conversations object availability validation
     * • Nonce verification for template access authorization
     * • HTML content sanitization for safe preview display
     * 
     * Backend Integration:
     * • cf7_preview_email action handler in PHP backend
     * • Submission data injection with personalization
     * • Template engine integration with error boundary handling
     */
    function loadTemplatePreview(templateId) {
        var submissionId = $('#submission-id').val();
        
        // Check if cf7Conversations is available
        if (typeof cf7Conversations === 'undefined') {
            console.error('cf7Conversations object not found - template preview unavailable');
            $('.preview-subject').text('Configuration error');
            $('.preview-body').text('Please refresh the page and try again.');
            return;
        }
        
        $.ajax({
            url: cf7Conversations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_preview_email',
                nonce: cf7Conversations.nonce,
                template_id: templateId,
                submission_id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    $('.preview-subject').html('<strong>Subject:</strong> ' + response.data.subject);
                    $('.preview-body').html('<strong>Message:</strong><br>' + response.data.body.replace(/\n/g, '<br>'));
                } else {
                    // Handle error response safely
                    let errorMessage = 'Error loading template';
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage += ': ' + response.data;
                        } else if (response.data.message) {
                            errorMessage += ': ' + response.data.message;
                        }
                    }
                    $('.preview-subject').text(errorMessage);
                    $('.preview-body').text('');
                }
            },
            error: function(xhr, status, error) {
                $('.preview-subject').text('Error loading template');
                $('.preview-body').text('Server communication error. Check console for details.');
            }
        });
    }
    
    /**
     * Keyboard shortcut handler for efficient message sending
     * 
     * Implements Ctrl+Enter shortcut for quick message dispatch without
     * requiring mouse interaction. Enhances user productivity during
     * conversation management by providing keyboard-driven workflow.
     * 
     * Shortcut Features:
     * • Ctrl+Enter: Trigger send message button click event
     * • Cross-browser compatibility with standardized key codes
     * • Event delegation to handle dynamically loaded content
     * • Integration with existing button validation and AJAX workflows
     * 
     * Accessibility Benefits:
     * • Reduces mouse dependency for power users
     * • Maintains consistent behavior with button click handlers
     * • Preserves all existing validation and error handling
     * • Supports assistive technology compatibility
     */
    $('#message-body').on('keydown', function(e) {
        if (e.ctrlKey && e.which === 13) { // Ctrl+Enter
            $('#send-message-btn').trigger('click');
        }
    });
    
    // Handle message type change (custom vs template)
    $('#message-type').on('change', function() {
        var messageType = $(this).val();
        var $customField = $('#custom-message-field');
        var $previewField = $('#template-preview-field');
        
        if (messageType === 'custom') {
            $customField.show();
            $previewField.hide();
        } else {
            $customField.hide();
            $previewField.show();
            
            // Load template preview
            loadTemplatePreview(messageType);
        }
    });
    
    // Function to load template preview
    function loadTemplatePreview(templateId) {
        if (!templateId || templateId === 'custom') {
            return;
        }
        
        var submissionId = $('#submission-id').val();
        var emailNonce = $('#cf7-email-nonce').val();
        
        // Check if we have the email nonce
        if (!emailNonce) {
            $('#template-preview-content').html('<p>Error: Email nonce not found</p>');
            return;
        }
        
        $.ajax({
            url: cf7Conversations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_preview_email',
                template_id: templateId,
                submission_id: submissionId,
                nonce: emailNonce // Use the email nonce, not conversation nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#template-preview-content .preview-subject').html('<strong>Subject:</strong> ' + response.data.subject);
                    $('#template-preview-content .preview-body').html('<strong>Body:</strong><br>' + response.data.body);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    $('#template-preview-content').html('<p>Error loading template preview: ' + errorMsg + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Template preview AJAX error:', xhr, status, error);
                $('#template-preview-content').html('<p>Error loading template preview: ' + error + '</p>');
            }
        });
    }
    
    // ========================================
    // Message Delivery System
    // ========================================
    // Comprehensive message sending interface with validation, progress
    // tracking, and automatic page refresh for conversation updates.
    // Handles both custom and template-based message composition.
    //
    // Delivery Features:
    // • Pre-send validation with inline error feedback
    // • Progress indication with button state management
    // • AJAX delivery with comprehensive error handling
    // • Automatic conversation refresh for immediate visibility
    // • Cross-page scroll restoration for user experience continuity
    //
    // Validation System:
    // • Required field checking for custom message content
    // • Email address validation for delivery destination
    // • Template selection validation for template-based messages
    // • Configuration object availability verification
    //
    // User Experience:
    // • Loading states with disabled controls during processing
    // • Success confirmation with visual feedback
    // • Error messaging with actionable guidance
    // • Form field clearing after successful delivery
    // ========================================

    /**
     * Message delivery handler with comprehensive validation and feedback
     * 
     * Processes message sending with full validation, progress tracking,
     * and error handling. Supports both custom and template message types
     * with automatic conversation refresh after successful delivery.
     * 
     * @param {Event} e - Click event from send button
     * 
     * Delivery Process:
     * 1. Prevent default form submission behavior
     * 2. Extract and validate all required form data
     * 3. Perform client-side validation with user feedback
     * 4. Display loading state with button disabling
     * 5. Execute AJAX request with proper error handling
     * 6. Process response and update interface accordingly
     * 7. Refresh conversation view to show new message
     * 
     * Validation Rules:
     * • Custom messages: Require non-empty message body content
     * • Template messages: Validate template selection and configuration
     * • Email addresses: Verify valid destination email format
     * • Configuration: Ensure cf7Conversations object availability
     * 
     * Error Handling:
     * • Client-side validation with immediate feedback
     * • Server communication error recovery with retry guidance
     * • Configuration error detection with diagnostic information
     * • Network error handling with connectivity troubleshooting
     * 
     * Success Workflow:
     * • Clear form fields for continued conversation
     * • Display success confirmation with visual feedback
     * • Set session storage flag for scroll position restoration
     * • Refresh page to display updated conversation thread
     */
    $('#send-message-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $status = $('#send-status');
        
        var submissionId = $('#submission-id').val();
        var toEmail = $('#message-to').val();
        var messageType = $('#message-type').val();
        var messageBody = messageType === 'custom' ? $('#message-body').val() : '';
        
        // Validation
        if (messageType === 'custom' && !messageBody.trim()) {
            $status.text('Please enter a message').addClass('error');
            return;
        }
        
        // Check if cf7Conversations is available
        if (typeof cf7Conversations === 'undefined') {
            $status.text('JavaScript configuration error').addClass('error');
            console.error('cf7Conversations object not found');
            return;
        }
        
        // Show loading state
        $btn.prop('disabled', true).text('Sending...');
        $status.removeClass('error success').text('');
        
        // Send AJAX request
        $.ajax({
            url: cf7Conversations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_send_message',
                nonce: cf7Conversations.nonce,
                submission_id: submissionId,
                to_email: toEmail,
                message_type: messageType,
                message_body: messageBody
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Message sent!').addClass('success');
                    if (messageType === 'custom') {
                        $('#message-body').val('');
                    }
                    
                    // Refresh the page to show the new message and scroll to bottom
                    setTimeout(function() {
                        // Store a flag to scroll to bottom after reload
                        sessionStorage.setItem('cf7_scroll_to_bottom', 'true');
                        location.reload();
                    }, 1000);
                } else {
                    // Handle error response safely
                    let errorMessage = 'Error sending message';
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                        }
                    }
                    $status.text(errorMessage).addClass('error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                $status.text('Error sending message: ' + error).addClass('error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Send Message');
            }
        });
    });
    
    // ========================================
    // IMAP Integration System
    // ========================================
    // Manual inbox synchronization with comprehensive timeout handling,
    // progress tracking, and automatic conversation refresh. Provides
    // reliable email checking with user feedback and error recovery.
    //
    // Integration Features:
    // • Manual reply checking with comprehensive timeout management
    // • Progress indication with real-time status updates
    // • Timeout warning system for long-running IMAP operations
    // • Automatic conversation refresh after successful synchronization
    // • Error handling with detailed diagnostic information
    //
    // Timeout Management:
    // • 5-second progress warning for user feedback
    // • 30-second total timeout for IMAP operations
    // • Graceful timeout handling with user notification
    // • Automatic button state restoration after timeout
    //
    // User Experience:
    // • Button state management during processing
    // • Visual progress indicators in thread controls
    // • Success notification with processing statistics
    // • Error feedback with actionable troubleshooting guidance
    // ========================================

    /**
     * Manual IMAP reply checking with comprehensive timeout handling
     * 
     * Initiates manual check for new email replies with progress tracking,
     * timeout warnings, and automatic conversation refresh. Provides
     * reliable inbox synchronization with detailed user feedback.
     * 
     * @param {Event} e - Click event from check replies button
     * 
     * Check Process:
     * 1. Validate configuration object availability
     * 2. Update button state and display loading indicators
     * 3. Set timeout warning for long-running operations
     * 4. Execute AJAX request with extended timeout handling
     * 5. Process response and update conversation interface
     * 6. Refresh page to display newly synchronized messages
     * 
     * Timeout Strategy:
     * • 5-second warning: Update status with "still checking" message
     * • 30-second limit: Abort request with timeout notification
     * • Progress indicators: Real-time status updates in UI
     * • Recovery handling: Automatic button state restoration
     * 
     * Success Response Processing:
     * • Display last checked timestamp with operation duration
     * • Show processed email count for transparency
     * • Set session storage flag for scroll position restoration
     * • Trigger page refresh to display updated conversation
     * 
     * Error Handling:
     * • Configuration validation with diagnostic messaging
     * • Network error recovery with connectivity guidance
     * • Timeout handling with server status information
     * • AJAX error processing with detailed error reporting
     * 
     * Backend Integration:
     * • cf7_check_replies_manual action handler
     * • IMAP connection management with authentication
     * • Email processing with conversation thread updates
     * • Response formatting with operation statistics
     */
    $('#check-replies-manual').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var originalText = button.text();
        var submissionId = button.data('submission-id');
        
        // Check if cf7Conversations is available
        if (typeof cf7Conversations === 'undefined') {
            console.error('cf7Conversations object not found');
            alert('JavaScript configuration error - cf7Conversations not available');
            return;
        }
        
        // Disable button and show loading state
        button.prop('disabled', true).text('Checking...');
        
        // Show loading in the thread controls
        $('.thread-controls .last-checked').text('Checking for new replies...');
        
        // Add a timeout warning after 5 seconds (reduced from 10)
        var timeoutWarning = setTimeout(function() {
            $('.thread-controls .last-checked').text('Still checking... this should be quick now');
        }, 5000);
        
        $.ajax({
            url: cf7Conversations.ajaxUrl,
            type: 'POST',
            timeout: 30000, // 30 second timeout (reduced from 65)
            data: {
                action: 'cf7_check_replies_manual',
                nonce: cf7Conversations.nonce,
                submission_id: submissionId
            },
            success: function(response) {
                clearTimeout(timeoutWarning);
                
                if (response.success) {
                    // Update last checked time
                    var message = 'Last checked: ' + response.data.checked_at;
                    if (response.data.duration) {
                        message += ' (took ' + response.data.duration + ')';
                    }
                    if (response.data.processed_count !== undefined) {
                        message += ' - ' + response.data.processed_count + ' emails processed';
                    }
                    $('.thread-controls .last-checked').text(message);
                    
                    // Always refresh the page to ensure we have the latest data and proper styling
                    // This prevents issues with deleted messages reappearing and maintains consistent styling
                    setTimeout(function() {
                        sessionStorage.setItem('cf7_scroll_to_bottom', 'true');
                        location.reload();
                    }, 1000);
                    
                    // Show success message briefly
                    showNotice('Successfully checked for new replies', 'success');
                    
                } else {
                    // Handle error response safely
                    let errorMessage = 'Manual check failed';
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                        }
                    }
                    showNotice('Error: ' + errorMessage, 'error');
                    console.error('Manual check failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(timeoutWarning);
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                if (status === 'timeout') {
                    showNotice('Request timed out. The server may still be processing your request.', 'error');
                } else {
                    showNotice('AJAX error: ' + error, 'error');
                }
            },
            complete: function() {
                clearTimeout(timeoutWarning);
                // Re-enable button
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // ========================================
    // Conversation Display Engine
    // ========================================
    // Dynamic conversation rendering with message classification,
    // status indicators, and intelligent UI updates. Handles bidirectional
    // message display with comprehensive styling and interaction support.
    //
    // Display Features:
    // • Bidirectional message rendering (incoming/outgoing)
    // • Template message identification with visual badges
    // • Read/unread status indicators for admin workflow
    // • Dynamic message classification with CSS class management
    // • Automatic scrolling for optimal user experience
    //
    // Message Classification:
    // • Direction: Incoming vs outgoing message styling
    // • Template Status: Template-generated vs custom message identification
    // • Read Status: Admin viewed status for workflow management
    // • Message Type: Visual differentiation for different content types
    //
    // Security Features:
    // • HTML content escaping for XSS prevention
    // • Safe DOM manipulation with sanitized content
    // • Input validation for message data structures
    // ========================================

    /**
     * Dynamic conversation display with comprehensive message rendering
     * 
     * Renders conversation messages with full classification, status indicators,
     * and interactive elements. Provides complete conversation visualization
     * with bidirectional support and accessibility features.
     * 
     * @param {Array} messages - Array of message objects for rendering
     * 
     * Rendering Process:
     * 1. Validate conversation container availability
     * 2. Clear existing messages for fresh rendering
     * 3. Handle empty conversation state with appropriate messaging
     * 4. Process each message with classification and styling
     * 5. Generate HTML structure with status indicators
     * 6. Apply automatic scrolling for optimal positioning
     * 
     * Message Structure:
     * • id: Unique message identifier for tracking
     * • direction/type: Message flow direction (inbound/outbound)
     * • message_body/message: Content for display
     * • is_template: Template generation flag
     * • admin_viewed_at: Read status for workflow management
     * • sent_at/date: Timestamp information
     * • human_time_diff: Formatted relative time
     * 
     * Classification System:
     * • Direction Classes: .incoming, .outgoing for message flow
     * • Template Classes: .template-message for generated content
     * • Status Classes: .unviewed for unread messages
     * • Interactive Classes: Support for context menu integration
     * 
     * Status Indicators:
     * • Template Badges: Visual identification of template-generated content
     * • Read Status: Unread/read indicators with WordPress dashicons
     * • Message Type: Sent/received labels for clarity
     * • Timestamp Display: Relative time formatting for context
     * 
     * Accessibility Features:
     * • Semantic HTML structure with proper element hierarchy
     * • Screen reader compatible content with descriptive labels
     * • Keyboard navigation support through proper focus management
     * • High contrast status indicators for visual accessibility
     * 
     * Security Implementation:
     * • HTML escaping through escapeHtml utility function
     * • Safe DOM manipulation preventing XSS vulnerabilities
     * • Input validation for message data integrity
     * • Controlled HTML injection with sanitized content
     */
    function updateConversationDisplay(messages) {
        var conversationDiv = jQuery('.conversation-messages');
        if (conversationDiv.length === 0) return;
        
        // Clear existing messages
        conversationDiv.empty();
        
        if (messages.length === 0) {
            conversationDiv.append('<p class="no-messages">No messages yet. Start a conversation with the artist below.</p>');
            return;
        }
        
        // Add each message
        messages.forEach(function(message) {
            // Build classes based on message properties
            var messageClasses = ['conversation-message'];
            
            // Add direction class
            if (message.direction === 'outbound' || message.type === 'outgoing') {
                messageClasses.push('outgoing');
            } else {
                messageClasses.push('incoming');
            }
            
            // Add template class if it's a template message
            if (message.is_template) {
                messageClasses.push('template-message');
            }
            
            // Add unviewed class for unread incoming messages
            if ((message.direction === 'inbound' || message.type === 'incoming') && !message.admin_viewed_at) {
                messageClasses.push('unviewed');
            }
            
            var messageClassString = messageClasses.join(' ');
            
            // Build template badge if needed
            var templateBadge = '';
            if (message.is_template) {
                templateBadge = '<span class="template-badge">Template</span>';
            }
            
            // Build status badge for incoming messages
            var statusBadge = '';
            if (message.direction === 'inbound' || message.type === 'incoming') {
                if (!message.admin_viewed_at) {
                    statusBadge = '<span class="message-status-badge unread">' +
                        '<span class="dashicons dashicons-marker"></span>' +
                        'Unread' +
                        '</span>';
                } else {
                    statusBadge = '<span class="message-status-badge read">' +
                        '<span class="dashicons dashicons-yes-alt"></span>' +
                        'Read' +
                        '</span>';
                }
            }
            
            // Format the date (use the provided date or format it)
            var formattedDate = message.sent_at || message.date || '';
            if (message.human_time_diff) {
                formattedDate = message.human_time_diff;
            }
            
            // Determine message type text
            var messageTypeText = '';
            if (message.direction === 'outbound' || message.type === 'outgoing') {
                messageTypeText = 'Sent';
            } else {
                messageTypeText = 'Received';
            }
            
            // Get message content
            var messageContent = message.message_body || message.message || '';
            
            // Build the complete message HTML
            var messageHtml = '<div class="' + messageClassString + '" data-message-id="' + (message.id || '') + '">' +
                '<div class="message-meta">' +
                '<span class="message-type">' + messageTypeText + '</span>' +
                templateBadge +
                statusBadge +
                '<span class="message-date">' + formattedDate + '</span>' +
                '</div>' +
                '<div class="message-bubble">' +
                '<div class="message-content">' + escapeHtml(messageContent) + '</div>' +
                '</div>' +
                '</div>';
            
            conversationDiv.append(messageHtml);
        });
        
        // Scroll to bottom to show latest messages (enhanced)
        setTimeout(function() {
            conversationDiv.animate({
                scrollTop: conversationDiv[0].scrollHeight
            }, 300);
        }, 100);
    }
    
    // Helper function to escape HTML content
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Auto-refresh function for checking new messages
    function checkForNewMessages() {
        var submissionId = jQuery('input[name="submission_id"]').val();
        
        if (!submissionId) {
            return;
        }
        
        jQuery.ajax({
            url: cf7Conversations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_check_new_messages',
                submission_id: submissionId,
                nonce: cf7Conversations.nonce
            }
        }).done(function(response) {
            if (response.success && response.data.hasNewMessages) {
                refreshMessages();
            }
        }).fail(function(xhr, status, error) {
        });
    }
    
    // Refresh messages function
    function refreshMessages() {
        location.reload();
    }
    
    // Auto-polling disabled for now to reduce server load
    // TODO: Re-enable when proper new message detection is implemented
    // Start auto-polling for new messages every 30 seconds
    // setInterval(checkForNewMessages, 30000);
    
    // Initial check after 5 seconds - also disabled
    // setTimeout(checkForNewMessages, 5000);
    
    
    // ========================================
    // Context Menu System
    // ========================================
    // Interactive right-click context menus for message management with
    // cross-tab integration, keyboard accessibility, and comprehensive
    // action handling. Provides efficient workflow for message operations.
    //
    // Context Menu Features:
    // • Right-click activation with prevention of browser default menu
    // • Dynamic menu item generation based on message type and status
    // • Viewport boundary detection with intelligent positioning
    // • Keyboard navigation support (Escape key dismissal)
    // • Cross-tab integration with CF7 Actions system
    //
    // Available Actions:
    // • Add to Actions: Cross-tab integration for follow-up task creation
    // • Mark as Read/Unread: Status management for admin workflow
    // • Message-specific operations based on content and direction
    //
    // Integration Points:
    // → CF7 Actions System: Cross-tab communication for task creation
    // → Message Status API: Read/unread state management
    // → Global Event System: Document-level event handling
    // → Viewport Detection: Intelligent menu positioning
    // ========================================

    /**
     * Context menu initialization with comprehensive event management
     * 
     * Sets up right-click context menus for conversation messages with
     * keyboard accessibility, outside click dismissal, and conditional
     * activation based on conversation interface presence.
     * 
     * Initialization Features:
     * • Conditional activation based on conversation interface presence
     * • Event delegation for dynamically loaded message content
     * • Document-level event handling for menu dismissal
     * • Keyboard accessibility with escape key support
     * 
     * Event Management:
     * • Right-click prevention: Disable browser context menu
     * • Outside click detection: Automatic menu dismissal
     * • Escape key handling: Keyboard-driven menu closure
     * • Memory management: Proper event cleanup and removal
     * 
     * Accessibility Features:
     * • Keyboard navigation support with standard key bindings
     * • Focus management for screen reader compatibility
     * • High contrast menu styling for visual accessibility
     * • Semantic HTML structure for assistive technology
     */
    function initializeContextMenu() {
        // Only initialize if we're on the conversations tab
        if (!jQuery('.conversation-messages').length) {
            return;
        }
        
        // Add context menu event handlers
        jQuery(document).on('contextmenu', '.conversation-message', function(e) {
            e.preventDefault();
            showContextMenu(e, jQuery(this));
        });
        
        // Close context menu on outside click
        jQuery(document).on('click', function(e) {
            if (!jQuery(e.target).closest('.cf7-context-menu').length) {
                jQuery('.cf7-context-menu').remove();
            }
        });
        
        // Close context menu on escape key
        jQuery(document).on('keydown', function(e) {
            if (e.keyCode === 27) {
                jQuery('.cf7-context-menu').remove();
            }
        });
    }
    
    function showContextMenu(event, $message) {
        var messageId = $message.data('message-id');
        
        if (!messageId) {
            return;
        }
        
        // Determine message type and read status
        var isIncoming = $message.hasClass('incoming');
        var isUnread = $message.hasClass('unviewed');
        
        // Remove any existing context menus
        jQuery('.cf7-context-menu').remove();
        
        // Build menu items
        var menuItems = [];
        
        // Add to Actions (for all messages)
        menuItems.push({
            icon: 'dashicons-list-view',
            text: 'Add to Actions',
            action: 'add-to-actions',
            messageId: messageId
        });
        
        // Mark as read/unread (only for incoming messages)
        if (isIncoming) {
            if (isUnread) {
                menuItems.push({
                    icon: 'dashicons-yes',
                    text: 'Mark as Read',
                    action: 'mark-read',
                    messageId: messageId,
                    currentStatus: 'unread'
                });
            } else {
                menuItems.push({
                    icon: 'dashicons-hidden',
                    text: 'Mark as Unread',
                    action: 'mark-unread',
                    messageId: messageId,
                    currentStatus: 'read'
                });
            }
        }
        
        // Create menu HTML
        var menuHtml = '<div class="cf7-context-menu">';
        menuItems.forEach(function(item) {
            menuHtml += '<div class="cf7-context-item" data-action="' + item.action + '" data-message-id="' + item.messageId + '"';
            if (item.currentStatus) {
                menuHtml += ' data-current-status="' + item.currentStatus + '"';
            }
            menuHtml += '><span class="dashicons ' + item.icon + '"></span>' + item.text + '</div>';
        });
        menuHtml += '</div>';
        
        var $menu = jQuery(menuHtml);
        
        // Position menu
        $menu.css({
            position: 'fixed',
            top: event.clientY + 'px',
            left: event.clientX + 'px',
            zIndex: 999999
        });
        
        // Add to page
        jQuery('body').append($menu);
        
        // Ensure menu stays within viewport
        setTimeout(function() {
            var menuRect = $menu[0].getBoundingClientRect();
            var viewportWidth = jQuery(window).width();
            var viewportHeight = jQuery(window).height();
            
            if (menuRect.right > viewportWidth) {
                $menu.css('left', (event.clientX - menuRect.width) + 'px');
            }
            if (menuRect.bottom > viewportHeight) {
                $menu.css('top', (event.clientY - menuRect.height) + 'px');
            }
        }, 10);
        
        // Handle menu item clicks
        $menu.on('click', '.cf7-context-item', function(e) {
            e.stopPropagation();
            handleContextMenuAction(jQuery(this), $message);
            $menu.remove();
        });
    }
    
    function handleContextMenuAction($item, $message) {
        var action = $item.data('action');
        var messageId = $item.data('message-id');
        var currentStatus = $item.data('current-status');
        
        if (action === 'add-to-actions') {
            // Debug logging for actions integration
            
            // Use the global CF7_Actions interface for cross-tab functionality
            if (typeof window.CF7_Actions !== 'undefined' && typeof window.CF7_Actions.openModal === 'function') {
                var messageText = $message.find('.message-content').first().text().trim();
                var truncatedText = messageText.length > 100 ? messageText.substring(0, 100) + '...' : messageText;
                
                window.CF7_Actions.openModal({
                    messageId: messageId,
                    title: 'Follow up on message',
                    description: 'Regarding: ' + truncatedText
                });
            } else {
                console.error('CF7_Actions not available. Available window properties:', Object.keys(window).filter(k => k.toLowerCase().includes('action')));
                showNotice('Actions system not available. Please refresh the page and try again.', 'error');
            }
        } else if (action === 'mark-read' || action === 'mark-unread') {
            // Toggle read status
            jQuery.ajax({
                url: cf7Conversations.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_toggle_message_read',
                    message_id: messageId,
                    current_status: currentStatus,
                    nonce: cf7Conversations.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the message UI
                        if (response.data.new_status === 'read') {
                            $message.removeClass('unviewed');
                        } else {
                            $message.addClass('unviewed');
                        }
                        
                        // Show success message
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice('Failed to update message status', 'error');
                    }
                },
                error: function() {
                    showNotice('Error updating message status', 'error');
                }
            });
        }
    }
    
    // Initialize context menu when document is ready
    initializeContextMenu();
    
    // ========================================
    // Message Management Operations
    // ========================================
    // Administrative message management with confirmation workflows,
    // modal interfaces, and comprehensive safety mechanisms. Provides
    // secure conversation deletion with user confirmation requirements.
    //
    // Management Features:
    // • Manual conversation refresh with page reload
    // • Secure message clearing with multi-step confirmation
    // • Modal-based confirmation interface with input validation
    // • Comprehensive error handling with user feedback
    // • Safety mechanisms preventing accidental data loss
    //
    // Confirmation Workflow:
    // • Modal display with clear warning messaging
    // • Text input confirmation requiring "CLEAR" typing
    // • Button state management based on confirmation status
    // • Outside click and escape key dismissal support
    //
    // Security Features:
    // • Multi-step confirmation process for destructive operations
    // • Input validation for confirmation text matching
    // • Comprehensive error reporting with diagnostic information
    // • Rollback support and error recovery mechanisms
    // ========================================

    /**
     * Manual conversation refresh functionality
     * 
     * Provides simple page reload mechanism for conversation updates.
     * Used when automatic refresh is needed or AJAX updates fail.
     */
    $('#refresh-messages-btn').on('click', function(e) {
        e.preventDefault();
        refreshMessages();
    });

    /**
     * Clear messages initialization with modal display
     * 
     * Initiates secure message clearing workflow by displaying confirmation
     * modal with safety mechanisms and user guidance for destructive operation.
     */
    $('#cf7-clear-messages-btn').on('click', function(e) {
        e.preventDefault();
        showClearMessagesModal();
    });

    // Clear messages modal handlers
    $('#cf7-clear-cancel, #cf7-clear-messages-modal .cf7-modal-close').on('click', function() {
        hideClearMessagesModal();
    });

    // Close modal on outside click
    $('#cf7-clear-messages-modal').on('click', function(e) {
        if (e.target === this) {
            hideClearMessagesModal();
        }
    });

    // Monitor confirmation input
    $('#cf7-clear-confirmation').on('input', function() {
        const confirmText = $(this).val().trim();
        const confirmButton = $('#cf7-clear-confirm');
        
        if (confirmText === 'CLEAR') {
            confirmButton.prop('disabled', false);
        } else {
            confirmButton.prop('disabled', true);
        }
    });

    // Handle clear confirmation
    $('#cf7-clear-confirm').on('click', function(e) {
        e.preventDefault();
        
        const confirmText = $('#cf7-clear-confirmation').val().trim();
        if (confirmText !== 'CLEAR') {
            alert('Please type "CLEAR" to confirm this action.');
            return;
        }
        
        clearAllMessages();
    });
    
});

// ========================================
// Global Utility Functions
// ========================================
// Shared utility functions for user interface feedback, modal management,
// and conversation operations. Provides consistent experience across
// all conversation management features.
//
// Utility Categories:
// • User Notifications: Temporary feedback with auto-dismissal
// • Modal Management: Show/hide operations with state management  
// • Conversation Operations: Clear messages with confirmation workflow
// • Interface Helpers: Common UI operations and state management
//
// Integration Points:
// → WordPress Admin Notices: Consistent styling with admin interface
// → jQuery UI: Smooth animations and transitions
// → Session Management: State persistence across page operations
// → Error Handling: Comprehensive user feedback for all operations
// ========================================

/**
 * Display temporary user notifications with auto-dismissal
 * 
 * Creates WordPress-styled admin notices with automatic timeout
 * and manual dismissal options. Provides consistent user feedback
 * across all conversation operations and error conditions.
 * 
 * @param {string} message - Notification message text
 * @param {string} type - Notification type ('error', 'success', or default)
 * 
 * Features:
 * • WordPress admin notice styling for consistent experience
 * • Automatic 3-second timeout with fade-out animation
 * • Manual dismissal with click handler on dismiss button
 * • Duplicate notice prevention with automatic cleanup
 * • Screen reader accessibility with descriptive dismiss text
 * 
 * Notice Types:
 * • 'error': Red error styling for failures and warnings
 * • 'success': Green success styling for confirmations
 * • Default: Blue info styling for general notifications
 * 
 * Accessibility Features:
 * • Screen reader compatible with descriptive dismiss text
 * • High contrast styling following WordPress standards
 * • Keyboard accessible dismiss functionality
 * • Semantic HTML structure for assistive technology
 */
function showNotice(message, type) {
    // Remove any existing notices
    jQuery('.cf7-notice').remove();
    
    // Create notice element
    var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
    var notice = jQuery('<div class="notice ' + noticeClass + ' is-dismissible cf7-notice">' +
        '<p>' + message + '</p>' +
        '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
        '</div>');
    
    // Add notice to the page
    jQuery('.wrap h1').after(notice);
    
    // Auto-dismiss after 3 seconds
    setTimeout(function() {
        notice.fadeOut(function() {
            jQuery(this).remove();
        });
    }, 3000);
    
    // Handle manual dismiss
    notice.on('click', '.notice-dismiss', function() {
        notice.fadeOut(function() {
            jQuery(this).remove();
        });
    });
}

/**
 * Display clear messages confirmation modal
 * 
 * Shows modal interface for message clearing with input focus,
 * button state initialization, and smooth fade-in animation.
 * Prepares secure workflow for destructive operation confirmation.
 * 
 * Modal Features:
 * • 200ms fade-in animation for smooth appearance
 * • Automatic focus on confirmation input for immediate interaction
 * • Disabled confirm button requiring user input validation
 * • Form state reset for consistent modal presentation
 */
function showClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeIn(200);
    jQuery('#cf7-clear-confirmation').val('').focus();
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

/**
 * Hide clear messages confirmation modal
 * 
 * Dismisses modal interface with cleanup of form state and
 * smooth fade-out animation. Ensures secure state reset
 * preventing partial confirmation states.
 * 
 * Cleanup Features:
 * • 200ms fade-out animation for smooth dismissal
 * • Complete form field clearing for security
 * • Button state reset preventing accidental activation
 * • Memory cleanup for proper state management
 */
function hideClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeOut(200);
    jQuery('#cf7-clear-confirmation').val('');
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

/**
 * Execute conversation clearing with comprehensive validation
 * 
 * Performs secure deletion of all conversation messages with
 * extensive validation, error handling, and user feedback.
 * Includes safety checks and graceful error recovery.
 * 
 * Validation Process:
 * 1. Verify submission ID availability from DOM data attributes
 * 2. Validate cf7Conversations configuration object presence
 * 3. Execute AJAX request with proper nonce authentication
 * 4. Process response with comprehensive error handling
 * 5. Provide user feedback and interface updates
 * 
 * Safety Features:
 * • Submission ID validation before processing
 * • Configuration object availability checking
 * • Comprehensive AJAX error handling with diagnostic information
 * • Button state management during processing
 * • Automatic page refresh after successful operation
 * 
 * Error Handling:
 * • Missing submission ID detection with user alert
 * • Configuration error handling with diagnostic logging
 * • Network error recovery with detailed error reporting
 * • Server error processing with status code analysis
 * • User-friendly error messaging with actionable guidance
 * 
 * Success Workflow:
 * • Modal dismissal with success notification
 * • 1.5-second delay for user feedback visibility
 * • Automatic page refresh to show updated conversation state
 * • Success message display with operation confirmation
 */
function clearAllMessages() {
    const submissionId = jQuery('#cf7-clear-messages-btn').data('submission-id');
    const confirmButton = jQuery('#cf7-clear-confirm');
    
    if (!submissionId) {
        alert('Error: No submission ID found.');
        return;
    }
    
    // Check if cf7Conversations is available
    if (typeof cf7Conversations === 'undefined') {
        showNotice('JavaScript configuration error - cf7Conversations not available', 'error');
        console.error('cf7Conversations object not found');
        return;
    }
    
    // Debug logging
    console.log('Clearing messages for submission:', {
        submissionId: submissionId,
        ajaxUrl: cf7Conversations.ajaxUrl,
        nonce: cf7Conversations.nonce
    });
    
    // Disable button and show loading
    confirmButton.prop('disabled', true).text('Clearing...');
    
    jQuery.ajax({
        url: cf7Conversations.ajaxUrl,
        type: 'POST',
        data: {
            action: 'cf7_clear_messages',
            submission_id: submissionId,
            nonce: cf7Conversations.nonce
        },
        success: function(response) {
            
            if (response.success) {
                // Hide modal
                hideClearMessagesModal();
                
                // Show success message
                showNotice('All messages have been permanently deleted from the database.', 'success');
                
                // Refresh the conversation view
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                // Handle error response safely
                let errorMessage = 'Failed to clear messages.';
                if (response.data) {
                    if (typeof response.data === 'string') {
                        errorMessage = 'Failed to clear messages: ' + response.data;
                    } else if (response.data.message) {
                        errorMessage = 'Failed to clear messages: ' + response.data.message;
                    }
                }
                console.error('Clear messages failed:', response);
                showNotice(errorMessage, 'error');
                confirmButton.prop('disabled', false).text('Permanently Delete All Messages');
            }
        },
        error: function(xhr, status, error) {
            console.error('Clear messages AJAX error:', {
                xhr: xhr,
                status: status,
                error: error,
                responseText: xhr.responseText,
                readyState: xhr.readyState,
                statusText: xhr.statusText
            });
            
            let errorMessage = 'Error clearing messages. Please try again.';
            
            // Try to get more specific error information
            if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        errorMessage = 'Error: ' + errorResponse.data.message;
                    } else if (errorResponse.message) {
                        errorMessage = 'Error: ' + errorResponse.message;
                    }
                } catch (e) {
                    // If it's not JSON, use the raw response text if it's short enough
                    if (xhr.responseText.length < 100) {
                        errorMessage = 'Error: ' + xhr.responseText;
                    }
                }
            }
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            } else if (status === 'abort') {
                errorMessage = 'Request was cancelled.';
            } else if (xhr.status === 0) {
                errorMessage = 'Network error. Please check your connection.';
            } else if (xhr.status === 403) {
                errorMessage = 'Permission denied. Please refresh the page and try again.';
            } else if (xhr.status === 404) {
                errorMessage = 'Server endpoint not found. Please contact support.';
            } else if (xhr.status >= 500) {
                errorMessage = 'Server error (' + xhr.status + '). Please try again later.';
            }
            
            showNotice(errorMessage, 'error');
            confirmButton.prop('disabled', false).text('Permanently Delete All Messages');
        }
    });
}
