/**
 * JavaScript for conversation management
 */
jQuery(document).ready(function($) {
    
    /**
     * Scroll conversation to bottom
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
    
    // Function to load template preview
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
    
    // Handle Ctrl+Enter shortcut for sending messages
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
    
    // Handle send message button
    $('#send-message-btn').on('click', function(e) {
        e.preventDefault();
        
        console.log('Send message button clicked');
        console.log('cf7Conversations object:', typeof cf7Conversations !== 'undefined' ? cf7Conversations : 'undefined');
        
        var $btn = $(this);
        var $status = $('#send-status');
        
        var submissionId = $('#submission-id').val();
        var toEmail = $('#message-to').val();
        var messageType = $('#message-type').val();
        var messageBody = messageType === 'custom' ? $('#message-body').val() : '';
        
        console.log('Form values:', {
            submissionId: submissionId,
            toEmail: toEmail,
            messageType: messageType,
            messageBody: messageBody
        });
        
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
    
    // Handle manual reply check button
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
                console.log('Manual check response:', response);
                
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
    
    /**
     * Update the conversation display with new messages
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
            console.log('No submission ID found, skipping auto-refresh');
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
                console.log('New messages found, refreshing...');
                refreshMessages();
            }
        }).fail(function(xhr, status, error) {
            console.log('Auto-refresh check failed:', error);
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
    
    console.log('Auto-refresh disabled - manual refresh required for new messages');
    
    // Context Menu Manager for Conversation Messages
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
            console.log('Add to actions clicked');
            console.log('window.CF7_Actions exists:', typeof window.CF7_Actions !== 'undefined');
            console.log('CF7_Actions.openModal exists:', typeof window.CF7_Actions !== 'undefined' && typeof window.CF7_Actions.openModal === 'function');
            
            // Use the global CF7_Actions interface for cross-tab functionality
            if (typeof window.CF7_Actions !== 'undefined' && typeof window.CF7_Actions.openModal === 'function') {
                var messageText = $message.find('.message-content').first().text().trim();
                var truncatedText = messageText.length > 100 ? messageText.substring(0, 100) + '...' : messageText;
                
                console.log('Calling CF7_Actions.openModal with text:', truncatedText);
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
    
    // Manual refresh button
    $('#refresh-messages-btn').on('click', function(e) {
        e.preventDefault();
        refreshMessages();
    });

    // Clear messages functionality
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
    
    console.log('Conversation management initialized');
});

/**
 * Show a temporary notice to the user
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

// Show clear messages modal
function showClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeIn(200);
    jQuery('#cf7-clear-confirmation').val('').focus();
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

// Hide clear messages modal
function hideClearMessagesModal() {
    jQuery('#cf7-clear-messages-modal').fadeOut(200);
    jQuery('#cf7-clear-confirmation').val('');
    jQuery('#cf7-clear-confirm').prop('disabled', true);
}

// Clear all messages
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
    console.log('Clear messages starting:', {
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
            console.log('Clear messages response:', response);
            
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
