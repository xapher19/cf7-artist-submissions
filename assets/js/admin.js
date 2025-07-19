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
    
})(jQuery);