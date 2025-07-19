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
            conversationDiv = $(conversationSelectors[i]);
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
                    $('.preview-subject').text('Error loading template');
                    $('.preview-body').text('');
                }
            },
            error: function() {
                $('.preview-subject').text('Error loading template');
                $('.preview-body').text('');
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
        
        console.log('Sending AJAX request to:', cf7Conversations.ajaxUrl);
        
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
                console.log('AJAX success response:', response);
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
                    $status.text(response.data.message || 'Error sending message').addClass('error');
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
        
        console.log('Manual check started for submission:', submissionId);
        console.log('cf7Conversations available:', typeof cf7Conversations !== 'undefined');
        
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
                    
                    // If we have updated messages for this submission, refresh the conversation
                    if (response.data.messages && response.data.submission_id) {
                        updateConversationDisplay(response.data.messages);
                    } else {
                        // No new messages, just refresh to be sure and scroll to bottom
                        setTimeout(function() {
                            sessionStorage.setItem('cf7_scroll_to_bottom', 'true');
                            location.reload();
                        }, 1000);
                    }
                    
                    // Show success message briefly
                    showNotice('Successfully checked for new replies', 'success');
                    
                } else {
                    showNotice('Error: ' + response.data.message, 'error');
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
        var conversationDiv = $('.conversation-messages');
        if (conversationDiv.length === 0) return;
        
        // Clear existing messages
        conversationDiv.empty();
        
        if (messages.length === 0) {
            conversationDiv.append('<p class="no-messages">No messages yet. Start a conversation with the artist below.</p>');
            return;
        }
        
        // Add each message
        messages.forEach(function(message) {
            var messageClass = message.type === 'outgoing' ? 'outgoing' : 'incoming';
            var messageHtml = '<div class="conversation-message ' + messageClass + '">' +
                '<div class="message-meta">' +
                '<span class="message-type">' + (message.type === 'outgoing' ? 'Sent' : 'Received') + '</span>' +
                '<span class="message-date">' + message.sent_at + '</span>' +
                '</div>' +
                '<div class="message-bubble">' +
                '<div class="message-content">' + message.message + '</div>' +
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
     * Show a temporary notice to the user
     */
    function showNotice(message, type) {
        // Remove any existing notices
        $('.cf7-notice').remove();
        
        // Create notice element
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible cf7-notice">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
            '</div>');
        
        // Add notice to the page
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
        
        // Handle manual dismiss
        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Auto-refresh function for checking new messages
    function checkForNewMessages() {
        var submissionId = $('input[name="submission_id"]').val();
        
        if (!submissionId) {
            console.log('No submission ID found, skipping auto-refresh');
            return;
        }
        
        $.ajax({
            url: cf7_artist_submissions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_check_new_messages',
                submission_id: submissionId,
                nonce: cf7_artist_submissions_ajax.nonce
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
    
    // Start auto-polling for new messages every 30 seconds
    setInterval(checkForNewMessages, 30000);
    
    // Initial check after 5 seconds
    setTimeout(checkForNewMessages, 5000);
    
    // Manual refresh button
    $('#refresh-messages-btn').on('click', function(e) {
        e.preventDefault();
        refreshMessages();
    });
    
    console.log('Conversation management initialized with auto-refresh and keyboard shortcuts');
});
