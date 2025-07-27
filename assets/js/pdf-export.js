/**
 * CF7 Artist Submissions - AWS Lambda PDF Export Management System
 *
 * Advanced PDF export functionality for artist submission management with
 * AWS Lambda integration, real-time progress tracking, comprehensive export
 * options, and seamless user experience for cloud-based PDF generation.
 *
 * Features:
 * • AWS Lambda-powered PDF generation with Puppeteer
 * • Real-time job status tracking and progress indicators
 * • Enhanced export options (ratings, curator notes/comments)
 * • Professional download handling with presigned URLs
 * • Fallback support for HTML print-ready documents
 * • Comprehensive error handling and user feedback
 * • Configurable content sections with validation
 * • Progress animation and visual status updates
 * • Automatic retry mechanisms for failed requests
 * • Keyboard navigation and accessibility support
 * • Secure AJAX communication with nonce validation
 *
 * @package CF7_Artist_Submissions
 * @subpackage PDFExport
 * @since 1.2.0
 * @version 1.2.0
 */
jQuery(document).ready(function($) {
    
    console.log('=== PDF EXPORT SCRIPT LOADED ===');
    console.log('cf7_pdf_export object:', typeof cf7_pdf_export !== 'undefined' ? cf7_pdf_export : 'UNDEFINED');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Export button elements found:', $('.cf7-export-pdf-btn').length);
    console.log('Status elements found:', $('.cf7-export-status').length);
    console.log('Progress elements found:', $('.cf7-export-progress').length);
    
    // Check if we're on the right page
    console.log('Current URL:', window.location.href);
    console.log('Page contains post.php:', window.location.href.indexOf('post.php') !== -1);
    
    // Wait for tabs to load and check again
    setTimeout(function() {
        console.log('=== AFTER TAB LOAD CHECK ===');
        console.log('Export button elements found (after delay):', $('.cf7-export-pdf-btn').length);
        console.log('Status elements found (after delay):', $('.cf7-export-status').length);
        console.log('Progress elements found (after delay):', $('.cf7-export-progress').length);
    }, 2000);
    
    // ============================================================================
    // PDF EXPORT PROCESSING WITH LAMBDA INTEGRATION
    // ============================================================================
    
    /**
     * Handle PDF export button interactions with AWS Lambda support and 
     * comprehensive progress tracking. Provides seamless export workflow 
     * with real-time status updates and automatic document delivery.
     * 
     * @since 1.2.0
     */
    
    let statusCheckInterval;
    let statusCheckCounter = 0;
    
    // Handle PDF export button click using event delegation
    $(document).on('click', '.cf7-export-pdf-btn', function(e) {
        e.preventDefault(); // Prevent any default form submission
        
        console.log('=== PDF EXPORT BUTTON CLICKED ===');
        console.log('Button element:', this);
        console.log('Event object:', e);
        
        var $button = $(this);
        var $status = $('.cf7-export-status');
        var $progress = $('.cf7-export-progress');
        var postId = $button.data('post-id');
        
        console.log('Post ID:', postId);
        console.log('Button disabled:', $button.prop('disabled'));
        console.log('Status element found:', $status.length > 0);
        console.log('Progress element found:', $progress.length > 0);
        
        // If no status element found, create one
        if ($status.length === 0) {
            console.log('Creating status element...');
            $button.after('<div class="cf7-export-status"></div>');
            $status = $('.cf7-export-status');
        }
        
        // If no progress element found, create one
        if ($progress.length === 0) {
            console.log('Creating progress element...');
            $status.after('<div class="cf7-export-progress" style="display: none;"><div class="cf7-progress-bar"><div class="cf7-progress-fill"></div></div><div class="cf7-progress-text"></div></div>');
            $progress = $('.cf7-export-progress');
            
            // Add basic styling if not present
            if (!$('head').find('style[data-cf7-export-dynamic]').length) {
                $('head').append('<style data-cf7-export-dynamic>.cf7-export-status { margin-top: 10px; padding: 8px; font-size: 12px; text-align: center; } .cf7-export-status.success { color: #00a32a; background: #f0f8f0; border: 1px solid #00a32a; } .cf7-export-status.error { color: #d63638; background: #fef7f7; border: 1px solid #d63638; } .cf7-export-status.loading { color: #2271b1; background: #f0f6fc; border: 1px solid #2271b1; } .cf7-export-progress { margin-top: 10px; } .cf7-progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; } .cf7-progress-fill { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.3s ease; width: 0%; } .cf7-progress-text { text-align: center; margin-top: 5px; font-size: 12px; color: #666; }</style>');
            }
        }
        
        // Check if cf7_pdf_export is properly loaded
        if (typeof cf7_pdf_export === 'undefined') {
            console.error('cf7_pdf_export object not found! Export cannot proceed.');
            alert('PDF export configuration not loaded. Please refresh the page and try again.');
            return;
        }
        
        console.log('cf7_pdf_export configuration:', cf7_pdf_export);
        
        // Get export options with new fields
        var options = {
            include_personal_info: $('input[name="include_personal_info"]').is(':checked'),
            include_works: $('input[name="include_works"]').is(':checked'),
            include_ratings: $('input[name="include_ratings"]').is(':checked'),
            include_curator_notes: $('input[name="include_curator_notes"]').is(':checked'),
            include_curator_comments: $('input[name="include_curator_comments"]').is(':checked'),
            confidential_watermark: $('input[name="confidential_watermark"]').is(':checked')
        };
        
        console.log('=== EXPORT OPTIONS ===');
        console.log('Options:', options);
        console.log('Personal info checkbox found:', $('input[name="include_personal_info"]').length > 0);
        console.log('Works checkbox found:', $('input[name="include_works"]').length > 0);
        console.log('Personal info checked:', $('input[name="include_personal_info"]').is(':checked'));
        console.log('Works checked:', $('input[name="include_works"]').is(':checked'));
        
        // Disable button and show loading state
        $button.prop('disabled', true);
        $button.html('<span class="dashicons dashicons-update-alt"></span> ' + cf7_pdf_export.export_text);
        $status.removeClass('success error').addClass('loading').text('');
        
        console.log('Lambda available:', cf7_pdf_export.lambda_available);
        
        // Show progress bar if Lambda is available
        if (cf7_pdf_export.lambda_available) {
            $progress.show();
            updateProgress(10, cf7_pdf_export.processing_text);
        }
        
        // Make AJAX request
        console.log('=== MAKING AJAX REQUEST ===');
        console.log('URL:', cf7_pdf_export.ajax_url);
        console.log('cf7_pdf_export object:', cf7_pdf_export);
        
        var ajaxData = {
            action: 'cf7_export_submission_pdf',
            post_id: postId,
            nonce: cf7_pdf_export.nonce,
            include_personal_info: options.include_personal_info ? 1 : 0,
            include_works: options.include_works ? 1 : 0,
            include_ratings: options.include_ratings ? 1 : 0,
            include_curator_notes: options.include_curator_notes ? 1 : 0,
            include_curator_comments: options.include_curator_comments ? 1 : 0,
            confidential_watermark: options.confidential_watermark ? 1 : 0
        };
        
        console.log('AJAX Data:', ajaxData);
        
        // Log the exact data that will be sent
        console.log('=== FINAL AJAX CHECK ===');
        console.log('About to send AJAX request with:');
        console.log('URL:', cf7_pdf_export.ajax_url);
        console.log('Data keys:', Object.keys(ajaxData));
        console.log('Data values:', ajaxData);
        console.log('Nonce:', ajaxData.nonce);
        console.log('Post ID:', ajaxData.post_id);
        
        // Validate required data
        if (!cf7_pdf_export.ajax_url) {
            console.error('AJAX URL not found!');
            alert('PDF export configuration error: AJAX URL missing');
            resetButton($button);
            return;
        }
        
        if (!cf7_pdf_export.nonce) {
            console.error('Security nonce not found!');
            alert('PDF export configuration error: Security nonce missing');
            resetButton($button);
            return;
        }
        
        if (!postId) {
            console.error('Post ID not found!');
            alert('PDF export error: Submission ID missing');
            resetButton($button);
            return;
        }
        
        console.log('=== STARTING AJAX REQUEST ===');
        console.log('Making AJAX call to:', cf7_pdf_export.ajax_url);
        
        $.ajax({
            url: cf7_pdf_export.ajax_url,
            type: 'POST',
            data: ajaxData,
            beforeSend: function(xhr, settings) {
                console.log('AJAX beforeSend called');
                console.log('XHR object:', xhr);
                console.log('Settings:', settings);
            },
            success: function(response) {
                console.log('=== AJAX SUCCESS ===');
                console.log('Raw response:', response);
                console.log('Response type:', typeof response);
                console.log('Response success:', response.success);
                console.log('Response data:', response.data);
                
                if (response.success) {
                    if (response.data.type === 'lambda') {
                        // Check if Lambda processing completed immediately
                        if (response.data.status === 'completed' && response.data.download_url) {
                            // Immediate success - PDF is ready
                            handleLambdaSuccess(response.data, $button, $status, $progress);
                        } else {
                            // Async Lambda processing - start status checking
                            handleLambdaResponse(response.data, $button, $status, $progress);
                        }
                    } else if (response.data.type === 'html') {
                        // Legacy HTML fallback
                        handleLegacyResponse(response.data, $button, $status, $progress);
                    }
                } else {
                    console.log('=== AJAX ERROR RESPONSE ===');
                    console.log('Error data:', response.data);
                    console.log('Error message:', response.data ? response.data.message : 'No error message');
                    
                    handleError(response.data.message || 'Unknown error', $button, $status, $progress);
                }
            },
            error: function(xhr, status, error) {
                console.log('=== AJAX REQUEST FAILED ===');
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response text:', xhr.responseText);
                console.log('Status code:', xhr.status);
                
                var errorMsg = 'Connection failed';
                if (xhr.status === 0) {
                    errorMsg = 'Network error - check your connection';
                } else if (xhr.status === 403) {
                    errorMsg = 'Access denied - please refresh and try again';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error - please try again later';
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMsg = response.data?.message || response.message || errorMsg;
                    } catch (e) {
                        errorMsg = 'Server returned invalid response';
                    }
                }
                
                handleError(errorMsg, $button, $status, $progress);
            }
        });
    });
    
    // ============================================================================
    // LAMBDA RESPONSE HANDLING
    // ============================================================================
    
    /**
     * Handle Lambda-based PDF generation response with status tracking
     */
    function handleLambdaResponse(data, $button, $status, $progress) {
        updateProgress(30, 'Processing in AWS Lambda...');
        
        // Start checking job status
        statusCheckCounter = 0;
        statusCheckInterval = setInterval(function() {
            checkJobStatus(data.job_id, $button, $status, $progress);
        }, cf7_pdf_export.status_check_interval);
    }
    
    /**
     * Check job status via AJAX
     */
    function checkJobStatus(jobId, $button, $status, $progress) {
        statusCheckCounter++;
        
        // Update progress
        var progressPercent = Math.min(30 + (statusCheckCounter * 5), 90);
        updateProgress(progressPercent, 'Generating PDF...');
        
        // Stop checking after maximum attempts
        if (statusCheckCounter >= cf7_pdf_export.max_status_checks) {
            clearInterval(statusCheckInterval);
            handleError('PDF generation timeout. Please try again.', $button, $status, $progress);
            return;
        }
        
        $.ajax({
            url: cf7_pdf_export.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_check_pdf_status',
                job_id: jobId,
                nonce: cf7_pdf_export.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var jobStatus = response.data.status;
                    
                    if (jobStatus === 'completed') {
                        clearInterval(statusCheckInterval);
                        handleLambdaSuccess(response.data, $button, $status, $progress);
                    } else if (jobStatus === 'failed') {
                        clearInterval(statusCheckInterval);
                        var errorMsg = response.data.error_message || 'PDF generation failed';
                        handleError(errorMsg, $button, $status, $progress);
                    }
                    // Continue checking if status is still 'processing'
                }
            },
            error: function() {
                // Continue checking on AJAX errors (temporary network issues)
                console.log('Status check failed, retrying...');
            }
        });
    }
    
    /**
     * Handle successful Lambda PDF generation
     */
    function handleLambdaSuccess(data, $button, $status, $progress) {
        updateProgress(100, 'PDF generated successfully!');
        
        setTimeout(function() {
            $progress.hide();
            $status.removeClass('loading error').addClass('success');
            
            // Create download link
            var downloadHtml = cf7_pdf_export.success_text + '<br>';
            downloadHtml += '<a href="' + data.download_url + '" target="_blank" class="cf7-download-link">';
            downloadHtml += '<span class="dashicons dashicons-download"></span>';
            downloadHtml += cf7_pdf_export.download_text;
            downloadHtml += '</a>';
            
            $status.html(downloadHtml);
            
            // Automatically start download
            window.open(data.download_url, '_blank');
            
            // Re-enable button
            resetButton($button);
        }, 1000);
    }
    
    // ============================================================================
    // LEGACY RESPONSE HANDLING
    // ============================================================================
    
    /**
     * Handle legacy HTML fallback response
     */
    function handleLegacyResponse(data, $button, $status, $progress) {
        $progress.hide();
        $status.removeClass('loading error').addClass('success');
        
        var downloadHtml = cf7_pdf_export.success_text + '<br>';
        downloadHtml += '<a href="' + data.download_url + '?autoprint=1" target="_blank" class="cf7-download-link">';
        downloadHtml += '<span class="dashicons dashicons-media-document"></span>';
        downloadHtml += 'Open Print-ready Document';
        downloadHtml += '</a>';
        
        $status.html(downloadHtml);
        
        // Automatically open the document
        window.open(data.download_url + '?autoprint=1', '_blank');
        
        // Re-enable button
        resetButton($button);
    }
    
    // ============================================================================
    // ERROR HANDLING
    // ============================================================================
    
    /**
     * Handle errors with user-friendly messaging
     */
    function handleError(message, $button, $status, $progress) {
        clearInterval(statusCheckInterval);
        $progress.hide();
        $status.removeClass('loading success').addClass('error');
        $status.text(cf7_pdf_export.error_text + ': ' + message);
        
        // Re-enable button
        resetButton($button);
    }
    
    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================
    
    /**
     * Update progress bar and text
     */
    function updateProgress(percent, text) {
        $('.cf7-progress-fill').css('width', percent + '%');
        $('.cf7-progress-text').text(text);
    }
    
    /**
     * Reset export button to original state
     */
    function resetButton($button) {
        $button.prop('disabled', false);
        $button.html('<span class="dashicons dashicons-pdf"></span> ' + 
                    ($button.text().includes('Generate') ? 'Generate PDF' : 'Export to PDF'));
    }
    
    // ============================================================================
    // EXPORT VALIDATION SYSTEM
    // ============================================================================
    
    /**
     * Provide dynamic validation of export configuration with real-time feedback
     * and automatic button state management for valid content selections.
     * 
     * @since 1.2.0
     */
    
    // Enhanced validation for new options
    $('input[name="include_personal_info"], input[name="include_works"]').on('change', function() {
        var personalInfo = $('input[name="include_personal_info"]').is(':checked');
        var works = $('input[name="include_works"]').is(':checked');
        
        // If both basic content types are unchecked, show warning
        if (!personalInfo && !works) {
            if (!$('.cf7-export-warning').length) {
                $('.cf7-export-options-list').after('<div class="cf7-export-warning" style="color: #d63638; font-size: 12px; margin: 8px 0; padding: 8px; background: #fef7f7; border-left: 4px solid #d63638; border-radius: 4px;">At least one content option (Personal Information or Submitted Works) should be selected.</div>');
            }
            $('.cf7-export-pdf-btn').prop('disabled', true);
        } else {
            $('.cf7-export-warning').remove();
            $('.cf7-export-pdf-btn').prop('disabled', false);
        }
    });
    
    // Add helpful tooltips for advanced options
    $('input[name="include_ratings"], input[name="include_curator_notes"], input[name="include_curator_comments"]').on('change', function() {
        var $label = $(this).closest('label');
        var $description = $label.find('.cf7-option-description');
        
        if ($(this).is(':checked')) {
            $description.css('color', '#2271b1');
        } else {
            $description.css('color', '#666');
        }
    });
    
    // Clean up intervals when page unloads
    $(window).on('beforeunload', function() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
    });
    
    // Reinitialize when tabs change (for tab-based submissions view)
    $(document).on('cf7_tab_changed', function(e, tabId) {
        console.log('=== TAB CHANGED EVENT ===');
        console.log('New tab ID:', tabId);
        console.log('Export button elements found after tab change:', $('.cf7-export-pdf-btn').length);
        
        // If we're on the actions tab (where PDF export is), check button availability
        if (tabId === 'cf7-tab-actions') {
            setTimeout(function() {
                console.log('Export button elements found after actions tab load:', $('.cf7-export-pdf-btn').length);
            }, 500);
        }
    });
});
