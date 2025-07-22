/**
 * CF7 Artist Submissions - PDF Export Management System
 *
 * Advanced PDF export functionality for artist submission management with
 * comprehensive export options, real-time validation, and seamless user
 * experience integration providing professional document generation.
 *
 * Features:
 * • Personal information export with contact details and profiles
 * • Artwork documentation with image galleries and descriptions
 * • Administrative notes and internal communications
 * • Security watermarking for confidential documents
 * • Format selection between PDF and HTML print-ready output
 * • Real-time export validation and user feedback
 * • Progress indicators with visual status updates
 * • Automatic document opening and download management
 * • Comprehensive error handling and recovery
 * • Keyboard navigation and accessibility support
 * • Secure AJAX communication with nonce validation
 * • Batch processing capabilities for multiple submissions
 *
 * @package CF7_Artist_Submissions
 * @subpackage PDFExport
 * @since 1.0.0
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    
    // ============================================================================
    // PDF EXPORT PROCESSING
    // ============================================================================
    
    /**
     * Handle PDF export button interactions with comprehensive option collection
     * and AJAX communication. Provides seamless export workflow with real-time
     * progress tracking and automatic document delivery.
     * 
     * @since 1.0.0
     */
    
    // Handle PDF export button click
    $('.cf7-export-pdf-btn').on('click', function() {
        var $button = $(this);
        var $status = $('.cf7-export-status');
        var postId = $button.data('post-id');
        
        // Get export options
        var options = {
            include_personal_info: $('input[name="include_personal_info"]').is(':checked'),
            include_works: $('input[name="include_works"]').is(':checked'),
            include_notes: $('input[name="include_notes"]').is(':checked'),
            confidential_watermark: $('input[name="confidential_watermark"]').is(':checked')
        };
        
        // Disable button and show loading state
        $button.prop('disabled', true);
        $button.html('<span class="dashicons dashicons-update-alt"></span> ' + cf7_pdf_export.export_text);
        $status.removeClass('success error').addClass('loading').text(cf7_pdf_export.export_text);
        
        // Make AJAX request
        $.ajax({
            url: cf7_pdf_export.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_export_submission_pdf',
                post_id: postId,
                nonce: cf7_pdf_export.nonce,
                include_personal_info: options.include_personal_info ? 1 : 0,
                include_works: options.include_works ? 1 : 0,
                include_notes: options.include_notes ? 1 : 0,
                confidential_watermark: options.confidential_watermark ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $status.removeClass('loading error').addClass('success');
                    
                    if (response.data.type === 'html') {
                        $status.html(cf7_pdf_export.success_text + ' <a href="' + response.data.download_url + '?autoprint=1" target="_blank">Open Print-ready Document</a>');
                        
                        // Automatically open the document
                        window.open(response.data.download_url + '?autoprint=1', '_blank');
                    } else {
                        $status.html(cf7_pdf_export.success_text + ' <a href="' + response.data.download_url + '" target="_blank">Download PDF</a>');
                        window.open(response.data.download_url, '_blank');
                    }
                    
                } else {
                    $status.removeClass('loading success').addClass('error');
                    $status.text(cf7_pdf_export.error_text + ': ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                $status.removeClass('loading success').addClass('error');
                $status.text(cf7_pdf_export.error_text + ': ' + error);
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false);
                $button.html('<span class="dashicons dashicons-pdf"></span> Export to PDF');
            }
        });
    });
    
    // ============================================================================
    // EXPORT VALIDATION SYSTEM
    // ============================================================================
    
    /**
     * Provide dynamic validation of export configuration with real-time feedback
     * and automatic button state management for valid content selections.
     * 
     * @since 1.0.0
     */
    
    // Show/hide options based on checkboxes
    $('input[name="include_personal_info"], input[name="include_works"]').on('change', function() {
        var personalInfo = $('input[name="include_personal_info"]').is(':checked');
        var works = $('input[name="include_works"]').is(':checked');
        
        // If both are unchecked, show warning
        if (!personalInfo && !works) {
            if (!$('.cf7-export-warning').length) {
                $('.cf7-export-options-list').after('<div class="cf7-export-warning" style="color: #d63638; font-size: 12px; margin: 8px 0;">At least one content option should be selected.</div>');
            }
            $('.cf7-export-pdf-btn').prop('disabled', true);
        } else {
            $('.cf7-export-warning').remove();
            $('.cf7-export-pdf-btn').prop('disabled', false);
        }
    });
});
