/**
 * CF7 Artist Submissions - Conversation Management Interface
 * 
 * Comprehensive conversation management system providing real-time message
 * handling, IMAP integration, and interactive communication tools for
 * artist submission workflows. Manages bidirectional email conversations
 * with automatic scrolling, context menus, and message state management.
 * 
 * Features:
 * • Real-time message handling with bidirectional conversation support
 * • IMAP integration with manual reply checking and timeout handling
 * • Interactive message composition with template and custom modes
 * • Context menu system for message actions and status management
 * • Auto-scroll management with cross-page state persistence
 * • Template preview system with dynamic AJAX rendering
 * • Message status management for admin workflow optimization
 * • Cross-tab integration with CF7 Actions system
 * • Keyboard accessibility with Ctrl+Enter shortcuts
 * • Comprehensive error handling and user feedback
 * • Security features with nonce validation and XSS prevention
 * • Performance optimizations with debounced events
 * 
 * @package    CF7ArtistSubmissions
 * @subpackage ConversationManagement
 * @since      1.0.0
 * @version    1.0.0
 */
jQuery(document).ready(function($) {
    
    // ============================================================================
    // CONVERSATION DISPLAY MANAGEMENT
    // ============================================================================

    /**
     * Intelligent conversation scroll management with cross-page state persistence.
     * 
     * Provides automatic scrolling to bottom of conversation with intelligent
     * container detection, overflow checking, and smooth animation. Handles
     * multiple container selector patterns for flexible DOM integration with
     * session storage support for scroll position restoration.
     * 
     * @since 1.0.0
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
    
    // ============================================================================
    // KEYBOARD SHORTCUTS
    // ============================================================================
    
    /**
     * Keyboard shortcut handler for efficient message sending.
     * 
     * Implements Ctrl+Enter shortcut for quick message dispatch without
     * requiring mouse interaction. Uses event delegation for dynamic elements.
     */
    $(document).on('keydown', '#message-body', function(e) {
        if (e.ctrlKey && e.which === 13) { // Ctrl+Enter
            $('#send-message-btn').trigger('click');
        }
    });
    
    /**
     * Handle message type selection with interface updates.
     * 
     * Manages switching between custom and template message modes.
     * Uses event delegation to handle dynamically loaded elements.
     */
    $(document).on('change', '#message-type', function() {
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
    
    /**
     * Load template preview with email nonce validation.
     * 
     * Loads and displays email template preview with comprehensive error handling.
     */
    function loadTemplatePreview(templateId) {
        if (!templateId || templateId === 'custom') {
            return;
        }
        
        var submissionId = $('#submission-id').val();
        
        // Try both possible nonce field IDs (hyphen and underscore versions)
        var emailNonce = $('#cf7-email-nonce').val() || $('#cf7_email_nonce').val();
        
        // Check if we have the email nonce
        if (!emailNonce) {
            $('#template-preview-content').html('<p>Error: Email nonce not found</p>');
            return;
        }
        
        // Check if cf7Conversations is available
        if (typeof cf7Conversations === 'undefined') {
            $('#template-preview-content').html('<p>Configuration error - please refresh the page and try again.</p>');
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
                    // Try multiple selectors for backwards compatibility
                    var $previewSubject = $('#template-preview-content .preview-subject');
                    var $previewBody = $('#template-preview-content .preview-body');
                    
                    // Fallback to legacy selectors if new ones don't exist
                    if ($previewSubject.length === 0) {
                        $previewSubject = $('.preview-subject');
                    }
                    if ($previewBody.length === 0) {
                        $previewBody = $('.preview-body');
                    }
                    
                    // Update the preview content
                    if ($previewSubject.length > 0) {
                        $previewSubject.html('<strong>Subject:</strong> ' + response.data.subject);
                    }
                    if ($previewBody.length > 0) {
                        $previewBody.html('<strong>Body:</strong><br>' + response.data.body);
                    }
                    
                    // If neither selector worked, fall back to container
                    if ($previewSubject.length === 0 && $previewBody.length === 0) {
                        $('#template-preview-content').html(
                            '<div class="preview-subject"><strong>Subject:</strong> ' + response.data.subject + '</div>' +
                            '<div class="preview-body"><strong>Body:</strong><br>' + response.data.body + '</div>'
                        );
                    }
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                    $('#template-preview-content').html('<p>Error loading template preview: ' + errorMsg + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#template-preview-content').html('<p>Error loading template preview: ' + error + '</p>');
            }
        });
    }
    
    // ============================================================================
    // MESSAGE DELIVERY SYSTEM
    // ============================================================================

    /**
     * Message delivery handler with comprehensive validation and feedback.
     * 
     * Uses event delegation to handle dynamically loaded send button.
     */
    $(document).on('click', '#send-message-btn', function(e) {
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
                $status.text('Error sending message: ' + error).addClass('error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Send Message');
            }
        });
    });
    
    // ============================================================================
    // IMAP INTEGRATION SYSTEM
    // ============================================================================

    /**
     * Manual IMAP reply checking with comprehensive timeout handling.
     * 
     * Initiates manual check for new email replies with progress tracking,
     * timeout warnings, and automatic conversation refresh. Provides
     * reliable inbox synchronization with detailed user feedback.
     * 
     * @since 1.0.0
     */
    $('#check-replies-manual').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var originalText = button.text();
        var submissionId = button.data('submission-id');
        
        // Check if cf7Conversations is available
        if (typeof cf7Conversations === 'undefined') {
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
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(timeoutWarning);
                
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
    
    // ============================================================================
    // CONVERSATION DISPLAY ENGINE
    // ============================================================================

    /**
     * Dynamic conversation display with comprehensive message rendering.
     * 
     * Renders conversation messages with full classification, status indicators,
     * and interactive elements. Provides complete conversation visualization
     * with bidirectional support and accessibility features.
     * 
     * @since 1.0.0
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
    
    /**
     * Escape HTML content to prevent XSS vulnerabilities.
     * 
     * Provides secure text escaping for user-generated content display.
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Automated message checking for new conversation replies.
     * 
     * Performs background checks for new messages with submission validation.
     */
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
    
    /**
     * Trigger page reload for conversation refresh.
     * 
     * Simple page reload mechanism for conversation updates.
     */
    function refreshMessages() {
        location.reload();
    }
    
    // Auto-polling disabled for now to reduce server load
    // TODO: Re-enable when proper new message detection is implemented
    // Start auto-polling for new messages every 30 seconds
    // setInterval(checkForNewMessages, 30000);
    
    // Initial check after 5 seconds - also disabled
    // setTimeout(checkForNewMessages, 5000);
    
    
    // ============================================================================
    // CONTEXT MENU SYSTEM
    // ============================================================================

    /**
     * Context menu initialization with comprehensive event management.
     * 
     * Sets up right-click context menus for conversation messages with
     * keyboard accessibility, outside click dismissal, and conditional
     * activation based on conversation interface presence.
     * 
     * @since 1.0.0
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
    
    /**
     * Display context menu for message interaction.
     * 
     * Creates and positions contextual menu with message-specific actions
     * including Actions integration and read status management.
     */
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
    
    /**
     * Process context menu action selection.
     * 
     * Handles context menu interactions including Actions integration
     * and message read status management with user feedback.
     */
    function handleContextMenuAction($item, $message) {
        var action = $item.data('action');
        var messageId = $item.data('message-id');
        var currentStatus = $item.data('current-status');
        
        if (action === 'add-to-actions') {
            // Use the global CF7_Actions interface for cross-tab functionality
            if (typeof window.CF7_Actions !== 'undefined' && typeof window.CF7_Actions.openModal === 'function') {
                var messageText = $message.find('.message-content').first().text().trim();
                var truncatedText = messageText.length > 100 ? messageText.substring(0, 100) + '...' : messageText;
                
                try {
                    window.CF7_Actions.openModal({
                        messageId: messageId,
                        title: 'Follow up on message',
                        description: 'Regarding: ' + truncatedText
                    });
                } catch (error) {
                    showNotice('Error opening Actions modal. Please try again.', 'error');
                }
            } else {
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
    
    /**
     * Initialize context menu system.
     * 
     * Sets up context menu functionality when document is ready.
     */
    initializeContextMenu();
    
    // ============================================================================
    // MESSAGE MANAGEMENT OPERATIONS
    // ============================================================================

    /**
     * Manual conversation refresh functionality.
     * 
     * Provides simple page reload mechanism for conversation updates.
     * Used when automatic refresh is needed or AJAX updates fail.
     */
    $('#refresh-messages-btn').on('click', function(e) {
        e.preventDefault();
        refreshMessages();
    });

    /**
     * Clear messages initialization with modal display.
     * 
     * Initiates secure message clearing workflow by displaying confirmation
     * modal with safety mechanisms and user guidance for destructive operation.
     */
    $('#cf7-clear-messages-btn').on('click', function(e) {
        e.preventDefault();
        showClearMessagesModal();
    });

    /**
     * Clear messages modal dismissal handlers.
     * 
     * Provides multiple dismissal methods for modal interface.
     */
    $('#cf7-clear-cancel, #cf7-clear-messages-modal .cf7-modal-close').on('click', function() {
        hideClearMessagesModal();
    });

    /**
     * Modal outside click dismissal handler.
     * 
     * Closes modal when clicking outside modal content area.
     */
    $('#cf7-clear-messages-modal').on('click', function(e) {
        if (e.target === this) {
            hideClearMessagesModal();
        }
    });

    /**
     * Confirmation input monitoring for clear operation.
     * 
     * Enables confirm button only when correct confirmation text is entered.
     */
    $('#cf7-clear-confirmation').on('input', function() {
        const confirmText = $(this).val().trim();
        const confirmButton = $('#cf7-clear-confirm');
        
        if (confirmText === 'CLEAR') {
            confirmButton.prop('disabled', false);
        } else {
            confirmButton.prop('disabled', true);
        }
    });

    /**
     * Clear confirmation handler with validation.
     * 
     * Processes final confirmation and triggers message clearing operation.
     */
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

// ============================================================================
// GLOBAL UTILITY FUNCTIONS
// ============================================================================

/**
 * Display temporary user notifications with auto-dismissal.
 * 
 * Creates WordPress-styled admin notices with automatic timeout
 * and manual dismissal options. Provides consistent user feedback
 * across all conversation operations and error conditions.
 * 
 * @since 1.0.0
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
 * Display clear messages confirmation modal.
 * 
 * Shows modal interface for message clearing with input focus,
 * button state initialization, and smooth fade-in animation.
 * Prepares secure workflow for destructive operation confirmation.
 */
function showClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeIn(200);
    jQuery('#cf7-clear-confirmation').val('').focus();
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

/**
 * Hide clear messages confirmation modal.
 * 
 * Dismisses modal interface with cleanup of form state and
 * smooth fade-out animation. Ensures secure state reset
 * preventing partial confirmation states.
 */
function hideClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeOut(200);
    jQuery('#cf7-clear-confirmation').val('');
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

/**
 * Execute conversation clearing with comprehensive validation.
 * 
 * Performs secure deletion of all conversation messages with
 * extensive validation, error handling, and user feedback.
 * Includes safety checks and graceful error recovery.
 * 
 * @since 1.0.0
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
        return;
    }
    
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
                showNotice(errorMessage, 'error');
                confirmButton.prop('disabled', false).text('Permanently Delete All Messages');
            }
        },
        error: function(xhr, status, error) {
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
