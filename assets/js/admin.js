/**
 * CF7 Artist Submissions - Admin Scripts
 */
(function($) {
    'use strict';
    
    // Update submission status via AJAX
    $('#submission-status-selector').on('change', function() {
        const $this = $(this);
        const $form = $this.closest('form');
        const $spinner = $form.find('.spinner');
        const $message = $form.find('.status-message');
        
        const postId = $this.data('post-id');
        const statusId = $this.val();
        
        // Show spinner
        $spinner.addClass('is-active');
        $message.empty();
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_submission_status',
                post_id: postId,
                status_id: statusId,
                nonce: cf7ArtistSubmissions.nonce
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    $message.html('<span class="success">Status updated to ' + response.data.status_name + '</span>');
                } else {
                    $message.html('<span class="error">Error: ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $spinner.removeClass('is-active');
                $message.html('<span class="error">Connection error. Please try again.</span>');
            }
        });
    });
    
    // Test IMAP connection
    $('#test-imap-connection').on('click', function() {
        const $button = $(this);
        const $result = $('#imap-test-result');
        
        // Show loading state
        $button.prop('disabled', true).text('Testing...');
        $result.html('<div class="notice notice-info"><p>Testing IMAP connection...</p></div>');
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_test_imap',
                nonce: cf7ArtistSubmissions.nonce
            },
            success: function(response) {
                $button.prop('disabled', false).text('Test Connection');
                
                if (response.success) {
                    let detailsHtml = '';
                    if (response.data.details) {
                        detailsHtml = '<br>Messages: ' + response.data.details.messages + 
                                     ', Recent: ' + response.data.details.recent + 
                                     ', Unseen: ' + response.data.details.unseen;
                    }
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + detailsHtml + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $button.prop('disabled', false).text('Test Connection');
                $result.html('<div class="notice notice-error"><p>Connection error. Please try again.</p></div>');
            }
        });
    });
    
})(jQuery);