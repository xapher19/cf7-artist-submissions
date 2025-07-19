/**
 * CF7 Artist Submissions - Email Management Scripts
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Preview email
        $('#cf7_preview_email').on('click', function() {
            const templateId = $('#cf7_email_template').val();
            const submissionId = $('#cf7_email_submission_id').val();
            
            // Show loading state
            $(this).prop('disabled', true);
            
            // Make AJAX request to preview email
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_preview_email',
                    template_id: templateId,
                    submission_id: submissionId,
                    nonce: $('#cf7_email_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Populate the preview modal
                        $('.cf7-email-preview-subject').html('<strong>Subject:</strong> ' + response.data.subject);
                        $('.cf7-email-preview-body').html(response.data.body);
                        
                        // Show the modal
                        $('#cf7-email-preview-modal').fadeIn();
                    } else {
                        // Show error message
                        $('#cf7_email_message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                    
                    // Reset button state
                    $('#cf7_preview_email').prop('disabled', false);
                },
                error: function() {
                    // Show generic error
                    $('#cf7_email_message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>Error generating preview.</p>')
                        .show();
                        
                    // Reset button state
                    $('#cf7_preview_email').prop('disabled', false);
                }
            });
        });
        
        // Close preview modal
        $('.cf7-close-preview').on('click', function() {
            $('#cf7-email-preview-modal').fadeOut();
        });
        
        // Send email
        $('#cf7_send_email, .cf7-send-from-preview').on('click', function() {
            const templateId = $('#cf7_email_template').val();
            const submissionId = $('#cf7_email_submission_id').val();
            const recipient = $('#cf7_email_to').val();
            
            // Show loading state
            $('.spinner').addClass('is-active');
            $('#cf7_send_email, #cf7_preview_email, .cf7-send-from-preview').prop('disabled', true);
            
            // Close modal if sending from preview
            if ($(this).hasClass('cf7-send-from-preview')) {
                $('#cf7-email-preview-modal').fadeOut();
            }
            
            // Send the email via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_send_manual_email',
                    template_id: templateId,
                    submission_id: submissionId,
                    recipient: recipient,
                    nonce: $('#cf7_email_nonce').val()
                },
                success: function(response) {
                    // Hide spinner
                    $('.spinner').removeClass('is-active');
                    
                    if (response.success) {
                        // Show success message
                        $('#cf7_email_message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                            
                        // Reload page after delay to update email log
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        $('#cf7_email_message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                            
                        // Re-enable buttons
                        $('#cf7_send_email, #cf7_preview_email').prop('disabled', false);
                    }
                },
                error: function() {
                    // Hide spinner
                    $('.spinner').removeClass('is-active');
                    
                    // Show generic error
                    $('#cf7_email_message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>Error sending email.</p>')
                        .show();
                        
                    // Re-enable buttons
                    $('#cf7_send_email, #cf7_preview_email').prop('disabled', false);
                }
            });
        });
    });
})(jQuery);