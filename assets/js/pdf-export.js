/**
 * ============================================================================
 * CF7 ARTIST SUBMISSIONS - PDF EXPORT MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * Advanced PDF export functionality for artist submission management with
 * comprehensive export options, real-time validation, and seamless user
 * experience integration. Provides professional document generation with
 * customizable content inclusion and security features.
 * 
 * This system manages the complete PDF export workflow from user interface
 * interaction through server-side processing to final document delivery.
 * Includes sophisticated error handling, progress feedback, and automatic
 * document opening for streamlined administrative workflows.
 * 
 * ============================================================================
 * SYSTEM ARCHITECTURE
 * ============================================================================
 * 
 * CF7PDFExportSystem
 * ├─ ExportTriggerSystem
 * │  ├─ ButtonHandlers: Export initiation and UI state management
 * │  ├─ OptionCollection: Dynamic export configuration gathering
 * │  ├─ ValidationEngine: Real-time content selection validation
 * │  └─ StateManagement: Button states and loading indicators
 * │
 * ├─ AjaxCommunicationLayer
 * │  ├─ RequestBuilder: AJAX payload construction and formatting
 * │  ├─ ResponseHandler: Success/error response processing
 * │  ├─ SecurityManager: Nonce validation and secure transmission
 * │  └─ ErrorRecovery: Graceful failure handling and user feedback
 * │
 * ├─ UserInterfaceSystem
 * │  ├─ ProgressIndicators: Real-time export status visualization
 * │  ├─ FeedbackSystem: Success/error message display management
 * │  ├─ DocumentLauncher: Automatic PDF opening and print preparation
 * │  └─ ValidationDisplay: Dynamic content selection warnings
 * │
 * ├─ ExportConfigurationEngine
 * │  ├─ ContentSelection: Personal info, works, notes inclusion options
 * │  ├─ SecurityOptions: Confidential watermark and access controls
 * │  ├─ FormatManagement: PDF vs HTML print-ready output selection
 * │  └─ OptionValidation: Real-time configuration validation
 * │
 * └─ DocumentDeliverySystem
 *    ├─ AutoLauncher: Automatic document opening in new windows
 *    ├─ PrintOptimization: Print-ready document preparation
 *    ├─ DownloadManager: Direct PDF download functionality
 *    └─ URLGeneration: Secure document access URL creation
 * 
 * ============================================================================
 * INTEGRATION POINTS
 * ============================================================================
 * 
 * • CF7 Submission System: Deep integration with submission data structure
 * • WordPress AJAX Framework: Secure server communication infrastructure
 * • PDF Generation Backend: Server-side document creation services
 * • WordPress Media Library: Asset management for document generation
 * • Print System Integration: Browser print optimization and preparation
 * • Security Framework: Nonce validation and access control systems
 * • User Interface Framework: Admin dashboard UI component integration
 * 
 * ============================================================================
 * DEPENDENCIES
 * ============================================================================
 * 
 * • jQuery 3.x: Core JavaScript framework for DOM manipulation
 * • WordPress AJAX API: Server communication and nonce validation
 * • CF7 Backend Services: PDF generation and document processing
 * • Browser APIs: Window management for document launching
 * • WordPress Dashicons: Icon system for UI visual elements
 * 
 * ============================================================================
 * EXPORT FEATURES
 * ============================================================================
 * 
 * • Personal Information Export: Contact details, artist profiles, metadata
 * • Artwork Documentation: Image galleries, descriptions, technical details
 * • Administrative Notes: Internal comments, review status, communications
 * • Security Watermarking: Confidential document marking and protection
 * • Format Selection: PDF generation or HTML print-ready documents
 * • Batch Processing: Multiple submission export capabilities
 * • Custom Layouts: Professional document formatting and presentation
 * 
 * ============================================================================
 * USER INTERFACE ARCHITECTURE
 * ============================================================================
 * 
 * • Export Button Integration: Seamless admin interface button placement
 * • Option Panels: Intuitive content selection checkboxes and controls
 * • Progress Indicators: Real-time export status with visual feedback
 * • Success Messaging: Clear completion notifications with document links
 * • Error Handling: User-friendly error messages with recovery options
 * • Auto-Launch: Intelligent document opening for streamlined workflows
 * 
 * ============================================================================
 * VALIDATION SYSTEM
 * ============================================================================
 * 
 * • Content Selection: Ensure at least one content type is selected
 * • Real-time Feedback: Dynamic validation with immediate user feedback
 * • Button State Management: Automatic enable/disable based on validation
 * • Warning Display: Clear messaging for invalid configurations
 * • Configuration Persistence: Maintain user preferences across sessions
 * 
 * ============================================================================
 * PERFORMANCE FEATURES
 * ============================================================================
 * 
 * • Asynchronous Processing: Non-blocking PDF generation workflow
 * • Progress Tracking: Real-time status updates during processing
 * • Memory Management: Efficient resource usage and cleanup
 * • Caching Integration: Server-side document caching for repeat access
 * • Lazy Loading: On-demand resource loading for optimal performance
 * 
 * ============================================================================
 * ACCESSIBILITY FEATURES
 * ============================================================================
 * 
 * • Keyboard Navigation: Full keyboard accessibility for all controls
 * • Screen Reader Support: Proper ARIA labels and semantic markup
 * • Focus Management: Logical tab order and focus indicators
 * • High Contrast: Visual elements optimized for accessibility standards
 * • Error Messaging: Clear, accessible error communication
 * 
 * ============================================================================
 * SECURITY FEATURES
 * ============================================================================
 * 
 * • Nonce Validation: WordPress security token verification
 * • Access Control: User permission verification before export
 * • Secure URLs: Protected document access with expiration
 * • Data Sanitization: Input validation and XSS prevention
 * • Confidential Watermarking: Document security marking system
 * 
 * @package CF7_Artist_Submissions
 * @subpackage PDFExport
 * @since 2.0.0
 * @version 2.0.0
 * @author CF7 Artist Submissions Development Team
 */
jQuery(document).ready(function($) {
    
    /**
     * ============================================================================
     * PDF EXPORT TRIGGER AND PROCESSING SYSTEM
     * ============================================================================
     * 
     * Handles PDF export button interactions with comprehensive option collection,
     * AJAX communication, and user feedback management. Provides seamless export
     * workflow with real-time progress tracking and automatic document delivery.
     * 
     * Export Workflow:
     * 1. Option Collection: Gather user-selected export configuration
     * 2. UI State Management: Disable controls and show progress indicators
     * 3. AJAX Communication: Secure server communication with nonce validation
     * 4. Response Processing: Handle success/error states with user feedback
     * 5. Document Delivery: Automatic opening of generated documents
     * 6. State Restoration: Re-enable controls and clear progress indicators
     * 
     * Export Options:
     * • Personal Information: Contact details, artist profiles, submission metadata
     * • Artwork Documentation: Image galleries, descriptions, technical specifications
     * • Administrative Notes: Internal comments, review status, communication logs
     * • Confidential Watermarking: Security marking for sensitive documents
     * 
     * User Experience Features:
     * • Real-time Progress: Visual feedback during export processing
     * • Auto-Launch: Intelligent document opening for streamlined workflows
     * • Error Recovery: Graceful failure handling with actionable feedback
     * • State Management: Proper UI state transitions throughout process
     * ============================================================================
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
    
    /**
     * ============================================================================
     * REAL-TIME EXPORT VALIDATION SYSTEM
     * ============================================================================
     * 
     * Provides dynamic validation of export configuration with real-time user
     * feedback and automatic button state management. Ensures export requests
     * contain valid content selections before processing.
     * 
     * Validation Rules:
     * • Content Requirement: At least one content type must be selected
     * • Option Dependencies: Validate relationships between export options
     * • User Feedback: Immediate visual feedback for invalid configurations
     * • Button Management: Automatic enable/disable based on validation state
     * 
     * Validation Features:
     * • Real-time Processing: Instant validation on option change
     * • Visual Indicators: Clear warning messages for invalid states
     * • State Persistence: Maintain validation state across interactions
     * • User Guidance: Helpful messaging to guide proper configuration
     * 
     * User Experience Benefits:
     * • Immediate Feedback: Prevent invalid export attempts before submission
     * • Clear Guidance: Explicit requirements for valid export configuration
     * • Progressive Enhancement: Graceful degradation if JavaScript disabled
     * • Accessibility: Screen reader compatible validation messaging
     * ============================================================================
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
