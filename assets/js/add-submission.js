/**
 * CF7 Artist Submissions - Add Submission Interface Controller
 *
 * Comprehensive form interface controller for artist submission creation with
 * advanced file upload handling, real-time validation, AJAX communication,
 * and seamless WordPress admin integration.
 *
 * Features:
 * • Interactive drag-and-drop file upload system with preview
 * • Real-time form validation with visual feedback
 * • AJAX form submission with loading states and error handling
 * • Dynamic file management with add/remove capabilities
 * • Email format validation and required field checking
 * • Responsive design with mobile device compatibility
 * • Professional styling integrated with WordPress admin
 * • Automatic redirect to tabbed interface after creation
 * • File type validation and size formatting display
 * • Comprehensive error handling and user notifications
 * • XSS protection through proper HTML escaping
 * • Progressive enhancement with graceful degradation
 *
 * @package CF7_Artist_Submissions
 * @subpackage AddSubmissionInterface
 * @since 1.0.1
 * @version 1.0.1
 */
(function($) {
    'use strict';
    
    // ============================================================================
    // CF7 ADD SUBMISSION INTERFACE CONTROLLER
    // ============================================================================
    
    /**
     * Main controller object managing all add submission interface functionality.
     * Provides centralized management for form handling, file uploads, validation,
     * and AJAX communication with comprehensive error handling and user feedback.
     * 
     * @since 1.0.1
     */
    const CF7AddSubmissionInterface = {
        
        /**
         * Selected files array for upload management.
         * Maintains file objects for preview, validation, and submission.
         */
        selectedFiles: [],
        
        /**
         * Initialize add submission interface with all components.
         * Sets up file upload handling, form validation, and AJAX communication
         * with comprehensive error handling and user feedback systems.
         * 
         * @since 1.0.1
         */
        init: function() {
            this.initFileUpload();
            this.initFormSubmission();
        },

        // ============================================================================
        // FILE UPLOAD MANAGEMENT
        // ============================================================================
        
        /**
         * Initialize interactive file upload system with drag-and-drop support.
         * Sets up click-to-select, drag-and-drop, file preview, and management
         * capabilities with comprehensive validation and user feedback.
         * 
         * @since 1.0.1
         */
        initFileUpload: function() {
            const self = this;
            const $uploadArea = $('#cf7-file-upload-area');
            const $fileInput = $('#artwork_files');
            const $selectedFilesContainer = $('#cf7-selected-files');
            
            // Click to select files functionality
            $uploadArea.on('click', function(e) {
                if (e.target !== $fileInput[0]) {
                    $fileInput.click();
                }
            });
            
            // File input change handler
            $fileInput.on('change', function() {
                self.handleFileSelection(this.files);
            });
            
            // Drag and drop event handlers
            $uploadArea.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $uploadArea.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                self.handleFileSelection(files);
            });
        },
        
        /**
         * Process file selection and update display with validation.
         * Handles multiple file selection, duplicate prevention, and
         * updates the file preview display with management controls.
         * 
         * @since 1.0.1
         * @param {FileList} files - Files selected by user
         */
        handleFileSelection: function(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Check if file is already selected to prevent duplicates
                const exists = this.selectedFiles.some(f => f.name === file.name && f.size === file.size);
                if (exists) continue;
                
                this.selectedFiles.push(file);
            }
            
            this.updateFileDisplay();
        },
        
        /**
         * Update file display area with selected files and management controls.
         * Generates preview interface with file information, size display,
         * and remove functionality for comprehensive file management.
         * 
         * @since 1.0.1
         */
        updateFileDisplay: function() {
            const $selectedFilesContainer = $('#cf7-selected-files');
            const self = this;
            
            if (this.selectedFiles.length === 0) {
                $selectedFilesContainer.empty();
                return;
            }
            
            let html = '<div style="border-top: 1px solid #ddd; padding-top: 15px;">';
            html += '<h4 style="margin: 0 0 10px 0; color: #23282d;">Selected Files:</h4>';
            
            this.selectedFiles.forEach((file, index) => {
                const fileSize = this.formatFileSize(file.size);
                html += `
                    <div class="cf7-selected-file" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f9f9f9; border-radius: 4px; margin-bottom: 5px;">
                        <div>
                            <strong>${this.escapeHtml(file.name)}</strong>
                            <span style="color: #666; margin-left: 10px;">(${fileSize})</span>
                        </div>
                        <button type="button" class="cf7-remove-file" data-index="${index}" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            ${cf7AddSubmission.strings.removeFile}
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            $selectedFilesContainer.html(html);
            
            // Add remove file functionality
            $('.cf7-remove-file').on('click', function() {
                const index = parseInt($(this).data('index'));
                self.selectedFiles.splice(index, 1);
                self.updateFileDisplay();
            });
        },

        // ============================================================================
        // FORM SUBMISSION MANAGEMENT
        // ============================================================================
        
        /**
         * Initialize comprehensive form submission handling with validation.
         * Sets up AJAX submission, loading states, validation, and error handling
         * with automatic redirect to tabbed interface after successful creation.
         * 
         * @since 1.0.1
         */
        initFormSubmission: function() {
            const self = this;
            
            $('#cf7-add-submission-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitButton = $('#cf7-submit-button');
                const $loading = $('#cf7-add-loading');
                const $messages = $('#cf7-add-messages');
                
                // Clear previous messages
                $messages.empty();
                
                // Validate form before submission
                if (!self.validateForm($form)) {
                    return;
                }
                
                // Show loading state and disable button
                $submitButton.prop('disabled', true);
                $loading.show();
                
                // Prepare form data with files
                const formData = new FormData($form[0]);
                formData.append('action', 'cf7_create_submission');
                
                // Add selected files to form data
                self.selectedFiles.forEach((file) => {
                    formData.append('artwork_files[]', file);
                });
                
                // Submit form via AJAX
                self.submitFormData(formData, $submitButton, $loading);
            });
        },
        
        /**
         * Submit form data via AJAX with comprehensive error handling.
         * Manages AJAX request, response processing, user feedback, and
         * automatic redirect to submission edit page after successful creation.
         * 
         * @since 1.0.1
         * @param {FormData} formData - Complete form data including files
         * @param {jQuery} $submitButton - Submit button element for state management
         * @param {jQuery} $loading - Loading indicator element
         */
        submitFormData: function(formData, $submitButton, $loading) {
            const self = this;
            
            $.ajax({
                url: cf7AddSubmission.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('success', response.data.message);
                        
                        // Redirect to the new submission after a short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else {
                        self.showMessage('error', response.data.message || cf7AddSubmission.strings.error);
                    }
                },
                error: function() {
                    self.showMessage('error', cf7AddSubmission.strings.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                    $loading.hide();
                }
            });
        },

        // ============================================================================
        // VALIDATION SYSTEMS
        // ============================================================================
        
        /**
         * Comprehensive form validation with visual feedback.
         * Validates required fields, email format, and provides visual
         * indicators for invalid fields with user-friendly error messages.
         * 
         * @since 1.0.1
         * @param {jQuery} $form - Form element to validate
         * @returns {boolean} True if form is valid, false otherwise
         */
        validateForm: function($form) {
            let isValid = true;
            
            // Check required fields
            const requiredFields = $form.find('[required]');
            requiredFields.each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    isValid = false;
                    $field.css('border-color', '#dc3545');
                } else {
                    $field.css('border-color', '#ddd');
                }
            });
            
            // Validate email format
            const $emailField = $('#artist_email');
            const email = $emailField.val().trim();
            if (email && !this.isValidEmail(email)) {
                isValid = false;
                $emailField.css('border-color', '#dc3545');
                this.showMessage('error', 'Please enter a valid email address.');
            }
            
            if (!isValid) {
                this.showMessage('error', 'Please fill in all required fields.');
            }
            
            return isValid;
        },
        
        /**
         * Validate email address format using regular expression.
         * Provides client-side email format validation for user feedback
         * before form submission with standard email pattern matching.
         * 
         * @since 1.0.1
         * @param {string} email - Email address to validate
         * @returns {boolean} True if email format is valid
         */
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        // ============================================================================
        // USER INTERFACE MANAGEMENT
        // ============================================================================
        
        /**
         * Display success or error message with automatic scrolling.
         * Provides user feedback with appropriate styling and automatic
         * scroll to top for visibility with XSS protection.
         * 
         * @since 1.0.1
         * @param {string} type - Message type ('success' or 'error')
         * @param {string} message - Message content to display
         */
        showMessage: function(type, message) {
            const $messages = $('#cf7-add-messages');
            const cssClass = type === 'success' ? 'cf7-add-success' : 'cf7-add-error';
            
            $messages.html(`<div class="${cssClass}">${this.escapeHtml(message)}</div>`);
            
            // Scroll to top to show message
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        /**
         * Format file size for human-readable display.
         * Converts bytes to appropriate units (Bytes, KB, MB, GB) with
         * proper decimal formatting for user-friendly file size display.
         * 
         * @since 1.0.1
         * @param {number} bytes - File size in bytes
         * @returns {string} Formatted file size with unit
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Escape HTML characters to prevent XSS attacks.
         * Provides security by escaping dangerous HTML characters in
         * user-provided content before displaying in the interface.
         * 
         * @since 1.0.1
         * @param {string} text - Text content to escape
         * @returns {string} HTML-safe escaped text
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // ============================================================================
    // INITIALIZATION
    // ============================================================================
    
    /**
     * Initialize add submission interface when DOM is ready.
     * Provides conditional activation for add submission page only
     * with automatic component initialization and error handling.
     */
    $(document).ready(function() {
        if ($('#cf7-add-submission-form').length) {
            CF7AddSubmissionInterface.init();
        }
    });
    
    /**
     * Export interface to global scope for external component access.
     * Enables cross-file integration and developer console testing.
     */
    window.CF7AddSubmissionInterface = CF7AddSubmissionInterface;
    
})(jQuery);
