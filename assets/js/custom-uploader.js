/**
 * CF7 Artist Submissions - Custom S3 File Uploader
 * 
 * A custom drag-and-drop file uploader built from scratch for reliable S3 integration.
 * No external dependencies except jQuery.
 * 
 * @package CF7_Artist_Submissions
 * @since 1.2.0
 */

(function($) {
    'use strict';
    
    // Custom uploader class
    class CF7ArtistSubmissionsUploader {
        constructor(container, options = {}) {
            this.container = $(container);
            this.containerId = this.container.attr('id');
            this.options = {
                maxFiles: parseInt(this.container.data('max-files')) || 20,
                maxSize: parseInt(this.container.data('max-size')) || (5 * 1024 * 1024 * 1024), // 5GB
                chunkSize: 10 * 1024 * 1024, // 10MB chunks
                chunkThreshold: 50 * 1024 * 1024, // Use chunks for files > 50MB
                allowedTypes: [
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                    'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm',
                    'application/pdf',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain', 'application/rtf'
                ],
                ...options
            };
            
            this.files = [];
            this.uploadQueue = [];
            this.isUploading = false;
            this.maxRetries = 3; // Max retries for failed chunks
            this.dragExpanded = false; // Track drag area expansion state
            this.dragTimeout = null; // For throttling drag events
            
            // Submission modal drag state
            this.submissionDragExpanded = false;
            this.submissionDragTimeout = null;
            
            this.init();
        }
        
        init() {
            console.log('Initializing custom uploader for:', this.containerId);
            console.log('Options:', this.options);
            
            // Check if form takeover is enabled
            if (this.container.data('form-takeover') === true || this.container.data('form-takeover') === 'true') {
                this.setupFormTakeover();
            } else {
                this.createUI();
                this.bindEvents();
                this.setupFormIntegration();
            }
            
            // Mark as initialized
            this.container.addClass('custom-uploader-initialized');
        }
        
        setupFormIntegration() {
            const form = this.container.closest('form');
            if (!form.length) return;
            
            // Store original submit button value and set initial state
            const submitBtn = form.find('input[type="submit"], button[type="submit"]');
            if (submitBtn.length && !submitBtn.data('original-value')) {
                submitBtn.data('original-value', submitBtn.val() || submitBtn.text() || 'Submit');
            }
            
            // Set initial form submission state
            this.updateFormSubmissionState();
            
            // Intercept form submission to ensure files are uploaded first
            form.on('submit.cf7uploader', (e) => {
                const pendingFiles = this.files.filter(f => f.status === 'pending');
                const uploadingFiles = this.files.filter(f => f.status === 'uploading');
                const missingTitles = this.files.filter(f => f.workTitle === undefined || f.workTitle.trim() === '');
                
                // Check for missing work titles
                if (this.files.length > 0 && missingTitles.length > 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    console.log('Form submission prevented - missing work titles');
                    this.showError('Please add work titles for all uploaded files before submitting.');
                    return false;
                }
                
                // If there are files to upload, prevent submission and upload them first
                if (pendingFiles.length > 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    console.log('Form submission prevented - uploading files first');
                    this.uploadAllThenSubmit();
                    return false;
                }
                
                // If files are currently uploading, prevent submission
                if (uploadingFiles.length > 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    console.log('Form submission prevented - uploads in progress');
                    this.showError('Please wait for file uploads to complete before submitting.');
                    return false;
                }
                
                // Allow normal form submission if no files or all files uploaded
                console.log('Form submission allowed - all files processed');
                return true;
            });
        }
        
        setupFormTakeover() {
            const form = this.container.closest('form');
            if (!form.length) return;
            
            // Store original form content
            this.originalFormContent = form.html();
            
            // Replace form with submission button
            this.replaceFormWithSubmissionButton(form);
        }
        
        replaceFormWithSubmissionButton(form) {
            const formHtml = `
                <div class="cf7as-form-takeover">
                    <div class="cf7as-submission-intro">
                        <h2>Ready to Submit Your Work?</h2>
                        <p>Click the button below to begin your submission process. You'll be able to fill out your details and upload your artwork files.</p>
                    </div>
                    <div class="cf7as-submission-button-container">
                        <button type="button" class="cf7as-submit-work-btn">
                            <span class="cf7as-btn-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7,10 12,5 17,10"></polyline>
                                    <line x1="12" y1="5" x2="12" y2="15"></line>
                                </svg>
                            </span>
                            Submit My Work
                        </button>
                    </div>
                </div>
            `;
            
            form.html(formHtml);
            
            // Bind click event to submission button
            form.find('.cf7as-submit-work-btn').on('click', (e) => {
                e.preventDefault();
                this.startSubmissionProcess(form);
            });
        }
        
        startSubmissionProcess(form) {
            // Create the multi-step modal
            this.createSubmissionModal(form);
        }
        
        createSubmissionModal(form) {
            // Parse original form to extract fields
            const tempDiv = $('<div>').html(this.originalFormContent);
            const formFields = this.extractFormFields(tempDiv);
            
            const modalHtml = `
                <div class="cf7as-submission-modal" style="display: none;">
                    <div class="cf7as-submission-overlay"></div>
                    <div class="cf7as-submission-content">
                        <div class="cf7as-submission-header">
                            <h2 class="cf7as-submission-title">Submit Your Work</h2>
                            <button type="button" class="cf7as-submission-close">&times;</button>
                        </div>
                        
                        <div class="cf7as-submission-steps">
                            <div class="cf7as-step cf7as-step-1 active">
                                <span class="cf7as-step-number">1</span>
                                <span class="cf7as-step-label">Your Details</span>
                            </div>
                            <div class="cf7as-step cf7as-step-2">
                                <span class="cf7as-step-number">2</span>
                                <span class="cf7as-step-label">Upload Work</span>
                            </div>
                            <div class="cf7as-step cf7as-step-3">
                                <span class="cf7as-step-number">3</span>
                                <span class="cf7as-step-label">Submit</span>
                            </div>
                        </div>
                        
                        <div class="cf7as-submission-body">
                            <!-- Step 1: Form Fields -->
                            <div class="cf7as-submission-step cf7as-step-content-1" style="display: block;">
                                <h3>Tell us about yourself</h3>
                                <div class="cf7as-form-fields">
                                    ${formFields}
                                </div>
                                <div class="cf7as-step-actions">
                                    <button type="button" class="cf7as-btn cf7as-btn-primary cf7as-continue-to-upload">
                                        Continue to Upload Work
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9,18 15,12 9,6"></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Step 2: File Upload -->
                            <div class="cf7as-submission-step cf7as-step-content-2" style="display: none;">
                                <div class="cf7as-upload-container">
                                    <!-- Upload interface will be inserted here -->
                                </div>
                                <div class="cf7as-step-actions">
                                    <button type="button" class="cf7as-btn cf7as-btn-secondary cf7as-back-to-details">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Details
                                    </button>
                                    <button type="button" class="cf7as-btn cf7as-btn-primary cf7as-continue-to-submit" style="display: none;">
                                        Continue to Submit
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9,18 15,12 9,6"></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Step 3: Final Submit -->
                            <div class="cf7as-submission-step cf7as-step-content-3" style="display: none;">
                                <div class="cf7as-submission-review">
                                    <h3>Ready to Submit</h3>
                                    <p>Please review your submission and click submit when ready.</p>
                                    <div class="cf7as-submission-summary">
                                        <!-- Summary will be populated here -->
                                    </div>
                                </div>
                                <div class="cf7as-step-actions">
                                    <button type="button" class="cf7as-btn cf7as-btn-secondary cf7as-back-to-upload">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Upload
                                    </button>
                                    <button type="button" class="cf7as-btn cf7as-btn-success cf7as-final-submit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        Submit My Work
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if present
            $('.cf7as-submission-modal').remove();
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Cache modal elements
            this.submissionModal = $('.cf7as-submission-modal');
            this.currentStep = 1;
            
            // Bind modal events
            this.bindSubmissionModalEvents(form);
            
            // Show modal
            this.showSubmissionModal();
        }
        
        extractFormFields(tempDiv) {
            let fieldsHtml = '';
            
            // Find all form inputs, textareas, and selects
            tempDiv.find('input[type="text"], input[type="email"], input[type="tel"], input[type="url"], textarea, select').each(function() {
                const $field = $(this);
                const fieldName = $field.attr('name') || '';
                const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
                
                // Try to find the actual label from the original form
                let fieldLabel = '';
                
                // Method 1: Look for a label element with matching 'for' attribute
                const $associatedLabel = tempDiv.find(`label[for="${$field.attr('id')}"]`);
                if ($associatedLabel.length) {
                    fieldLabel = $associatedLabel.text().trim();
                }
                
                // Method 2: Look for a parent label element
                if (!fieldLabel) {
                    const $parentLabel = $field.closest('label');
                    if ($parentLabel.length) {
                        // Get text but exclude nested input text
                        fieldLabel = $parentLabel.clone().find('input, textarea, select').remove().end().text().trim();
                    }
                }
                
                // Method 3: Look for preceding label or text
                if (!fieldLabel) {
                    const $precedingLabel = $field.prevAll('label').first();
                    if ($precedingLabel.length) {
                        fieldLabel = $precedingLabel.text().trim();
                    }
                }
                
                // Method 4: Use placeholder as fallback
                if (!fieldLabel) {
                    fieldLabel = $field.attr('placeholder') || '';
                }
                
                // Method 5: Generate from field name as last resort
                if (!fieldLabel) {
                    fieldLabel = fieldName.replace(/[\[\]]/g, '').replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
                
                const isRequired = $field.hasClass('wpcf7-validates-as-required') || $field.attr('required');
                
                if (fieldName && !fieldName.includes('uploader')) {
                    // Check if this is an artist statement or description field that should span full width
                    const isArtistStatement = fieldLabel.toLowerCase().includes('statement') || 
                                            fieldLabel.toLowerCase().includes('description') || 
                                            fieldName.toLowerCase().includes('statement') || 
                                            fieldName.toLowerCase().includes('description');
                    
                    const fieldGroupClass = isArtistStatement ? 'cf7as-field-group cf7as-field-group-full-width' : 'cf7as-field-group';
                    
                    if (fieldType === 'textarea') {
                        const textareaClass = isArtistStatement ? 'cf7as-textarea-large' : '';
                        fieldsHtml += `
                            <div class="${fieldGroupClass}">
                                <label for="${fieldName}">${fieldLabel}${isRequired ? ' *' : ''}</label>
                                <textarea name="${fieldName}" id="${fieldName}" class="${textareaClass}" ${isRequired ? 'required' : ''}></textarea>
                            </div>
                        `;
                    } else if (fieldType === 'select') {
                        const options = $field.html();
                        fieldsHtml += `
                            <div class="${fieldGroupClass}">
                                <label for="${fieldName}">${fieldLabel}${isRequired ? ' *' : ''}</label>
                                <select name="${fieldName}" id="${fieldName}" ${isRequired ? 'required' : ''}>${options}</select>
                            </div>
                        `;
                    } else {
                        fieldsHtml += `
                            <div class="${fieldGroupClass}">
                                <label for="${fieldName}">${fieldLabel}${isRequired ? ' *' : ''}</label>
                                <input type="${fieldType}" name="${fieldName}" id="${fieldName}" ${isRequired ? 'required' : ''} />
                            </div>
                        `;
                    }
                }
            });
            
            return fieldsHtml;
        }
        
        bindSubmissionModalEvents(form) {
            const modal = this.submissionModal;
            
            // Close modal
            modal.find('.cf7as-submission-close').on('click', () => {
                this.closeSubmissionModal();
            });
            
            modal.find('.cf7as-submission-overlay').on('click', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeSubmissionModal();
                }
            });
            
            // Step navigation
            modal.find('.cf7as-continue-to-upload').on('click', () => {
                if (this.validateFormFields()) {
                    this.goToStep(2);
                    this.initializeUploaderInModal();
                }
            });
            
            modal.find('.cf7as-back-to-details').on('click', () => {
                this.goToStep(1);
            });
            
            modal.find('.cf7as-continue-to-submit').on('click', () => {
                this.goToStep(3);
                this.populateSubmissionSummary();
            });
            
            modal.find('.cf7as-back-to-upload').on('click', () => {
                this.goToStep(2);
            });
            
            modal.find('.cf7as-final-submit').on('click', () => {
                this.finalizeSubmission(form);
            });
            
            // Escape key to close modal
            $(document).on('keydown.cf7as-submission', (e) => {
                if (e.keyCode === 27 && modal.is(':visible')) {
                    this.closeSubmissionModal();
                }
            });
        }
        
        validateFormFields() {
            const modal = this.submissionModal;
            let isValid = true;
            
            modal.find('.cf7as-step-content-1 input[required], .cf7as-step-content-1 textarea[required], .cf7as-step-content-1 select[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                $field.removeClass('cf7as-field-error');
                
                if (!value) {
                    $field.addClass('cf7as-field-error');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.showModalError('Please fill in all required fields.');
            }
            
            return isValid;
        }
        
        goToStep(stepNumber) {
            const modal = this.submissionModal;
            
            // Update step indicators
            modal.find('.cf7as-step').removeClass('active completed');
            for (let i = 1; i < stepNumber; i++) {
                modal.find(`.cf7as-step-${i}`).addClass('completed');
            }
            modal.find(`.cf7as-step-${stepNumber}`).addClass('active');
            
            // Show/hide step content
            modal.find('.cf7as-submission-step').hide();
            modal.find(`.cf7as-step-content-${stepNumber}`).show();
            
            this.currentStep = stepNumber;
        }
        
        initializeUploaderInModal() {
            const uploadContainer = this.submissionModal.find('.cf7as-upload-container');
            
            if (uploadContainer.find('.cf7as-modal-body-inner').length) {
                return; // Already initialized
            }
            
            // Create the upload interface
            this.createModalUploadInterface(uploadContainer);
            this.bindModalUploadEvents();
            this.bindSubmissionModalDragEvents();
            
            // Watch for file uploads to show continue button
            this.watchForCompletedUploads();
        }
        
        createModalUploadInterface(container) {
            const maxSizeGB = Math.round(this.options.maxSize / (1024*1024*1024));
            
            const uploadHtml = `
                <div class="cf7as-modal-body-inner">
                    <div class="cf7as-upload-area" data-container-id="${this.containerId}">
                        <div class="cf7as-upload-prompt">
                            <div class="cf7as-upload-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7,10 12,5 17,10"></polyline>
                                    <line x1="12" y1="5" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <div class="cf7as-upload-text">
                                <h3>Upload Your Artwork</h3>
                                <p>Drag & drop files here or <button type="button" class="cf7as-browse-btn">browse files</button></p>
                                <small>Max ${maxSizeGB}GB per file, up to ${this.options.maxFiles} files</small>
                            </div>
                        </div>
                        <input type="file" class="cf7as-file-input" multiple accept="image/*,video/*,application/pdf,.doc,.docx,.txt,.rtf" style="display: none;">
                    </div>
                    
                    <div class="cf7as-work-content">
                        <div class="cf7as-work-grid-container">
                            <div class="cf7as-work-grid"></div>
                        </div>
                    </div>
                    
                    <div class="cf7as-work-editor">
                        <div class="cf7as-editor-header">
                            <h3 class="cf7as-editor-title">Edit Work Details</h3>
                            <p class="cf7as-editor-subtitle">Select a work to edit its information</p>
                        </div>
                        <div class="cf7as-editor-body">
                            <div class="cf7as-editor-field">
                                <label for="cf7as-selected-work-title">Work Title *</label>
                                <input type="text" id="cf7as-selected-work-title" class="cf7as-selected-work-title" placeholder="Enter the title of this work">
                            </div>
                            <div class="cf7as-editor-field">
                                <label for="cf7as-selected-work-statement">Work Statement</label>
                                <textarea id="cf7as-selected-work-statement" class="cf7as-selected-work-statement" placeholder="Describe this work, techniques used, artistic intentions, etc." rows="4"></textarea>
                            </div>
                        </div>
                        <div class="cf7as-editor-actions">
                            <button type="button" class="cf7as-upload-single-btn">Upload This File</button>
                            <button type="button" class="cf7as-remove-single-btn">Remove File</button>
                        </div>
                    </div>
                </div>
            `;
            
            container.html(uploadHtml);
            
            // Cache new DOM elements for submission modal (separate from regular modal)
            this.submissionModalBody = container.find('.cf7as-modal-body-inner');
            this.submissionUploadArea = container.find('.cf7as-upload-area');
            this.submissionFileInput = container.find('.cf7as-file-input');
            this.submissionWorkContent = container.find('.cf7as-work-content');
            this.submissionWorkGrid = container.find('.cf7as-work-grid');
            this.submissionWorkEditor = container.find('.cf7as-work-editor');
            this.submissionBrowseBtn = container.find('.cf7as-browse-btn');
            this.submissionSelectedWorkTitle = container.find('.cf7as-selected-work-title');
            this.submissionSelectedWorkStatement = container.find('.cf7as-selected-work-statement');
            this.submissionUploadSingleBtn = container.find('.cf7as-upload-single-btn');
            this.submissionRemoveSingleBtn = container.find('.cf7as-remove-single-btn');
        }
        
        bindModalUploadEvents() {
            const self = this;
            
            // Work selection - use submission modal elements
            this.submissionWorkGrid.on('click', '.cf7as-work-item', function(e) {
                e.preventDefault();
                const fileId = $(this).data('file-id');
                self.selectFile(fileId);
            });
            
            // Work title input
            this.submissionSelectedWorkTitle.on('input', function() {
                if (self.selectedFileId) {
                    const file = self.files.find(f => f.id === self.selectedFileId);
                    if (file) {
                        file.workTitle = $(this).val();
                        self.updateWorkTitleDisplay(self.selectedFileId, $(this).val());
                        self.updateFormSubmissionState();
                    }
                }
            });
            
            // Work statement input
            this.submissionSelectedWorkStatement.on('input', function() {
                if (self.selectedFileId) {
                    const file = self.files.find(f => f.id === self.selectedFileId);
                    if (file) {
                        file.workStatement = $(this).val();
                    }
                }
            });
            
            // Upload single file
            this.submissionUploadSingleBtn.on('click', function(e) {
                e.preventDefault();
                if (self.selectedFileId) {
                    const file = self.files.find(f => f.id === self.selectedFileId);
                    if (file && file.status === 'pending') {
                        
                        // Check if work title is provided
                        if (!file.workTitle || file.workTitle.trim() === '') {
                            self.showError('Please provide a work title before uploading.');
                            self.submissionSelectedWorkTitle.focus();
                            return;
                        }
                        
                        self.uploadFile(self.selectedFileId);
                    }
                }
            });
            
            // Remove single file
            this.submissionRemoveSingleBtn.on('click', function(e) {
                e.preventDefault();
                if (self.selectedFileId) {
                    self.removeFile(self.selectedFileId);
                    self.selectedFileId = null;
                    self.updateWorkEditor();
                }
            });
        }
        
        bindSubmissionModalDragEvents() {
            const uploadArea = this.submissionUploadArea;
            const fileInput = this.submissionFileInput;
            const modal = this.submissionModal;
            
            // Debug: Check if elements exist
            console.log('Binding submission modal drag events');
            console.log('Upload area found:', uploadArea && uploadArea.length > 0);
            console.log('File input found:', fileInput && fileInput.length > 0);
            console.log('Modal found:', modal && modal.length > 0);
            
            if (!uploadArea || uploadArea.length === 0) {
                console.error('Upload area not found for submission modal');
                return;
            }
            
            if (!modal || modal.length === 0) {
                console.error('Modal not found for submission modal');
                return;
            }
            
            // Prevent default drag behaviors globally on the document when modal is open
            $(document).on('dragenter.submission-modal dragover.submission-modal', (e) => {
                if (modal.is(':visible')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Document drag event prevented');
                }
            });
            
            $(document).on('drop.submission-modal', (e) => {
                if (modal.is(':visible')) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Document drop event prevented');
                }
            });
            
            // Prevent default drag behaviors on upload area (but still allow class changes)
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.on(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log(`Upload area ${eventName} event`);
                    
                    // Handle dragover class immediately in the same handler
                    if (eventName === 'dragenter' || eventName === 'dragover') {
                        console.log(`Adding dragover class on ${eventName}`);
                        uploadArea.addClass('cf7as-dragover');
                    } else if (eventName === 'dragleave') {
                        // Only remove dragover if we're actually leaving the upload area
                        const rect = uploadArea[0].getBoundingClientRect();
                        const x = e.originalEvent.clientX;
                        const y = e.originalEvent.clientY;
                        
                        if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                            console.log('Removing dragover class on dragleave');
                            uploadArea.removeClass('cf7as-dragover');
                        }
                    } else if (eventName === 'drop') {
                        console.log('Removing dragover class on drop');
                        uploadArea.removeClass('cf7as-dragover');
                    }
                });
            });
            
            // Remove the duplicate event handlers - handle everything in one place
            // Also bind to the modal container as fallback using event delegation
            modal.on('dragenter dragover dragleave drop', '.cf7as-upload-area', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log(`Delegated upload area ${e.type} event`);
                
                const target = $(e.currentTarget);
                
                if (e.type === 'dragenter' || e.type === 'dragover') {
                    console.log(`Delegated: Adding dragover class on ${e.type}`);
                    target.addClass('cf7as-dragover');
                } else if (e.type === 'dragleave') {
                    // Only remove if leaving the element bounds
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = e.originalEvent.clientX;
                    const y = e.originalEvent.clientY;
                    
                    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                        console.log('Delegated: Removing dragover class on dragleave');
                        target.removeClass('cf7as-dragover');
                    }
                } else if (e.type === 'drop') {
                    console.log('Delegated: Removing dragover class on drop');
                    target.removeClass('cf7as-dragover');
                    
                    // Handle the file drop
                    const files = e.originalEvent.dataTransfer.files;
                    console.log('Files dropped on upload area (delegated):', files.length);
                    if (files && files.length > 0) {
                        this.addFiles(files);
                    }
                }
            });
            
            // Add click test to verify element is interactive
            uploadArea.on('click', (e) => {
                console.log('Upload area clicked - element is interactive');
            });
            
            // Prevent default drag behaviors on entire modal
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                modal.on(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log(`Modal ${eventName} event`);
                });
            });
            
            // Handle files dropped anywhere in the modal
            modal.on('drop', (e) => {
                console.log('Drop event on modal');
                const files = e.originalEvent.dataTransfer.files;
                console.log('Files dropped on modal:', files.length);
                if (files && files.length > 0) {
                    // Collapse drag area immediately after drop
                    if (this.submissionDragExpanded) {
                        this.collapseSubmissionModalDragAreaImmediate();
                    } else {
                        uploadArea.removeClass('cf7as-dragover');
                    }
                    this.addFiles(files);
                }
            });
            
            // Visual feedback when dragging over modal
            modal.on('dragenter dragover', (e) => {
                const dt = e.originalEvent.dataTransfer;
                const hasFiles = dt && (dt.types.includes('Files') || dt.types.includes('application/x-moz-file') || dt.files.length > 0);
                
                console.log('Drag over modal - dataTransfer types:', dt ? dt.types : 'no dataTransfer');
                console.log('Has files:', hasFiles);
                
                if (hasFiles) {
                    console.log('Files detected in drag over modal');
                    
                    // If there are already files present, expand the drag area to fill the modal
                    if (this.files.length > 0) {
                        // Only expand if not already expanded to prevent repeated calls
                        if (!this.submissionDragExpanded) {
                            this.expandSubmissionModalDragArea();
                        }
                    } else {
                        // Just show dragover state for empty modal
                        uploadArea.addClass('cf7as-dragover');
                    }
                }
            });
            
            modal.on('dragleave', (e) => {
                // Only handle dragleave if we're leaving the modal entirely
                const relatedTarget = e.originalEvent.relatedTarget;
                if (!relatedTarget || !modal[0].contains(relatedTarget)) {
                    if (this.files.length > 0) {
                        // Use delayed collapse to prevent flickering during rapid events
                        this.collapseSubmissionModalDragArea();
                    } else {
                        uploadArea.removeClass('cf7as-dragover');
                    }
                }
            });
            
            // Browse button click
            this.submissionBrowseBtn.on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Browse button clicked, triggering file input');
                console.log('File input element:', this.submissionFileInput.length);
                if (this.submissionFileInput.length > 0) {
                    this.submissionFileInput[0].click();
                } else {
                    console.error('File input not found');
                }
            });
            
            // File input change
            fileInput.on('change', (e) => {
                const files = e.target.files;
                console.log('File input changed, files:', files.length);
                this.addFiles(files);
                // Clear the input so the same file can be selected again
                fileInput.val('');
            });
        }
        
        watchForCompletedUploads() {
            // Monitor for completed uploads and show continue button
            const checkUploads = () => {
                const uploadedFiles = this.files.filter(f => f.status === 'uploaded').length;
                const totalFiles = this.files.length;
                const continueBtn = this.submissionModal.find('.cf7as-continue-to-submit');
                
                if (totalFiles > 0 && uploadedFiles === totalFiles) {
                    continueBtn.show();
                } else {
                    continueBtn.hide();
                }
            };
            
            // Check every second
            this.uploadCheckInterval = setInterval(checkUploads, 1000);
        }
        
        populateSubmissionSummary() {
            const modal = this.submissionModal;
            const summaryContainer = modal.find('.cf7as-submission-summary');
            
            // Get form data
            const formData = {};
            modal.find('.cf7as-step-content-1 input, .cf7as-step-content-1 textarea, .cf7as-step-content-1 select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const value = $field.val();
                if (name && value) {
                    formData[name] = value;
                }
            });
            
            // Create summary HTML
            let summaryHtml = '<div class="cf7as-summary-section"><h4>Your Details</h4>';
            Object.keys(formData).forEach(key => {
                const label = key.replace(/[\[\]]/g, '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                summaryHtml += `<p><strong>${label}:</strong> ${formData[key]}</p>`;
            });
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="cf7as-summary-section"><h4>Uploaded Files</h4>';
            this.files.forEach(file => {
                summaryHtml += `<p><strong>${file.workTitle || file.name}:</strong> ${file.name} (${this.formatBytes(file.size)})</p>`;
            });
            summaryHtml += '</div>';
            
            summaryContainer.html(summaryHtml);
        }
        
        finalizeSubmission(form) {
            // Collect all form data
            const formData = new FormData(form[0]);
            
            // Add modal form data
            this.submissionModal.find('.cf7as-step-content-1 input, .cf7as-step-content-1 textarea, .cf7as-step-content-1 select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const value = $field.val();
                if (name && value) {
                    formData.set(name, value);
                }
            });
            
            // Add file data
            formData.set(this.containerId + '_data', JSON.stringify(this.files.filter(f => f.status === 'uploaded').map(f => ({
                id: f.id,
                filename: f.name,
                size: f.size,
                type: f.type,
                s3_key: f.s3Key,
                work_title: f.workTitle,
                work_statement: f.workStatement
            }))));
            
            // Show loading state
            const submitBtn = this.submissionModal.find('.cf7as-final-submit');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="cf7as-spinner"></span> Submitting...').prop('disabled', true);
            
            // Submit via AJAX
            $.ajax({
                url: form.attr('action') || window.location.href,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.showSuccessMessage();
                    this.closeSubmissionModal();
                },
                error: (xhr, status, error) => {
                    submitBtn.html(originalText).prop('disabled', false);
                    this.showModalError('Submission failed. Please try again.');
                    console.error('Submission error:', error);
                }
            });
        }
        
        showSuccessMessage() {
            const successHtml = `
                <div class="cf7as-success-popup" style="display: none;">
                    <div class="cf7as-success-overlay"></div>
                    <div class="cf7as-success-content">
                        <div class="cf7as-success-icon">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="16,9 10,14 8,12"></polyline>
                            </svg>
                        </div>
                        <h2>Submission Received!</h2>
                        <p>Thank you for your submission. We have received your artwork and details successfully.</p>
                        <p>You will receive an email confirmation shortly with more information about the next steps.</p>
                        <button type="button" class="cf7as-btn cf7as-btn-primary cf7as-close-success">Close</button>
                    </div>
                </div>
            `;
            
            $('body').append(successHtml);
            
            const popup = $('.cf7as-success-popup');
            popup.fadeIn(300);
            
            popup.find('.cf7as-close-success, .cf7as-success-overlay').on('click', () => {
                popup.fadeOut(300, () => popup.remove());
            });
        }
        
        showSubmissionModal() {
            // Ensure modal is properly positioned for fullscreen
            this.submissionModal.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100vw',
                'height': '100vh',
                'margin': '0',
                'padding': '0',
                'display': 'flex'
            }).hide().fadeIn(300);
            
            $('body').addClass('cf7as-submission-modal-open');
            
            // Prevent body scrolling
            $('html, body').css({
                'overflow': 'hidden',
                'height': '100%',
                'margin': '0',
                'padding': '0'
            });
        }
        
        closeSubmissionModal() {
            this.submissionModal.fadeOut(300);
            $('body').removeClass('cf7as-submission-modal-open');
            
            // Restore body scrolling
            $('html, body').css({
                'overflow': '',
                'height': '',
                'margin': '',
                'padding': ''
            });
            
            // Clean up intervals
            if (this.uploadCheckInterval) {
                clearInterval(this.uploadCheckInterval);
                this.uploadCheckInterval = null;
            }
            
            // Clean up submission modal drag area
            if (this.submissionDragExpanded) {
                this.collapseSubmissionModalDragAreaImmediate();
            }
            
            // Remove event listeners
            $(document).off('keydown.cf7as-submission');
            $(document).off('dragenter.submission-modal dragover.submission-modal drop.submission-modal');
        }
        
        showModalError(message) {
            // Remove existing error
            this.submissionModal.find('.cf7as-modal-error').remove();
            
            const errorHtml = `<div class="cf7as-modal-error">${message}</div>`;
            this.submissionModal.find('.cf7as-submission-body').prepend(errorHtml);
            
            setTimeout(() => {
                this.submissionModal.find('.cf7as-modal-error').fadeOut(() => {
                    $(this).remove();
                });
            }, 5000);
        }
        

        
        async uploadAllThenSubmit() {
            const form = this.container.closest('form');
            if (!form.length) return;
            
            const pendingFiles = this.files.filter(f => f.status === 'pending');
            if (pendingFiles.length === 0) {
                // No files to upload, submit form
                this.submitForm();
                return;
            }
            
            try {
                // Show status
                this.showStatus('Uploading files...');
                
                // Upload all pending files
                await this.uploadAll();
                
                // After successful upload, submit the form
                console.log('All files uploaded, submitting form...');
                this.showStatus('Submitting form...');
                this.submitForm();
                
            } catch (error) {
                console.error('Upload failed, preventing form submission:', error);
                this.hideStatus();
                this.showError('File upload failed. Please try again before submitting.');
            }
        }
        
        submitForm() {
            const form = this.container.closest('form');
            if (!form.length) return;
            
            // Temporarily remove our submit handler to avoid infinite loop
            form.off('submit.cf7uploader');
            
            // Re-enable submit button
            const submitBtn = form.find('input[type="submit"], button[type="submit"]');
            submitBtn.prop('disabled', false);
            submitBtn.val(submitBtn.data('original-value') || 'Submit');
            
            // Trigger form submission
            console.log('Triggering form submission...');
            form.submit();
        }
        
        // Modal control methods
        openModal() {
            // Move modal to body to escape container positioning constraints
            this.modal.appendTo('body');
            
            // Ensure modal is properly positioned to cover entire viewport
            this.modal.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100vw',
                'height': '100vh',
                'z-index': '2147483647',
                'margin': '0',
                'padding': '0'
            });
            
            // Ensure modal content covers full viewport
            this.modal.find('.cf7as-modal-content').css({
                'width': '100vw',
                'height': '100vh',
                'max-width': 'none',
                'max-height': 'none',
                'margin': '0',
                'padding': '0',
                'border-radius': '0'
            });
            
            // Ensure overlay covers full viewport
            this.modal.find('.cf7as-modal-overlay').css({
                'width': '100vw',
                'height': '100vh',
                'margin': '0',
                'padding': '0'
            });
            
            this.modal.show();
            $('body').addClass('cf7as-modal-open');
            
            // Prevent body scrolling and ensure fullscreen
            $('html, body').css({
                'overflow': 'hidden',
                'height': '100%',
                'margin': '0',
                'padding': '0'
            });
        }
        
        closeModal() {
            this.modal.hide();
            $('body').removeClass('cf7as-modal-open');
            
            // Collapse drag area immediately and clean up
            this.collapseDragAreaImmediate();
            
            // Remove document-level drag event handlers
            $(document).off('dragenter.cf7as-modal dragover.cf7as-modal dragleave.cf7as-modal drop.cf7as-modal');
            
            // Restore body scrolling
            $('html, body').css({
                'overflow': '',
                'height': ''
            });
            
            // Move modal back to original container to keep it with the uploader instance
            this.modal.appendTo(this.container);
        }
        
        // Drag area expansion methods
        expandDragArea() {
            if (this.dragExpanded) return; // Already expanded
            
            // Clear any pending collapse timeout
            if (this.dragTimeout) {
                clearTimeout(this.dragTimeout);
                this.dragTimeout = null;
            }
            
            this.dragExpanded = true;
            this.uploadArea.addClass('cf7as-drag-expanded');
            
            // Force inline styles as backup for theme conflicts
            this.uploadArea.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'right': '0',
                'bottom': '0',
                'width': '100vw',
                'height': '100vh',
                'z-index': '2147483648',
                'background': 'rgba(59, 130, 246, 0.1)',
                'border': '3px dashed #3b82f6',
                'border-radius': '0',
                'margin': '0',
                'padding': '0',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'transform': 'none',
                'transition': 'none',
                'box-shadow': 'none'
            });
        }
        
        collapseDragArea() {
            if (!this.dragExpanded) return; // Already collapsed
            
            // Clear any existing timeout
            if (this.dragTimeout) {
                clearTimeout(this.dragTimeout);
            }
            
            // Add a small delay to prevent flickering during rapid events
            this.dragTimeout = setTimeout(() => {
                this.dragExpanded = false;
                this.uploadArea.removeClass('cf7as-drag-expanded');
                
                // Reset inline styles
                this.uploadArea.css({
                    'position': '',
                    'top': '',
                    'left': '',
                    'right': '',
                    'bottom': '',
                    'width': '',
                    'height': '',
                    'z-index': '',
                    'background': '',
                    'border': '',
                    'border-radius': '',
                    'margin': '',
                    'padding': '',
                    'display': '',
                    'align-items': '',
                    'justify-content': '',
                    'transform': '',
                    'transition': '',
                    'box-shadow': ''
                });
                
                this.dragTimeout = null;
            }, 200); // Increased to 200ms for more stability
        }
        
        // Immediately collapse without delay - for definitive actions like drops
        collapseDragAreaImmediate() {
            if (!this.dragExpanded) return; // Already collapsed
            
            // Clear any pending timeout
            if (this.dragTimeout) {
                clearTimeout(this.dragTimeout);
                this.dragTimeout = null;
            }
            
            // Collapse immediately
            this.dragExpanded = false;
            this.uploadArea.removeClass('cf7as-drag-expanded');
            
            // Reset inline styles immediately
            this.uploadArea.css({
                'position': '',
                'top': '',
                'left': '',
                'right': '',
                'bottom': '',
                'width': '',
                'height': '',
                'z-index': '',
                'background': '',
                'border': '',
                'border-radius': '',
                'margin': '',
                'padding': '',
                'display': '',
                'align-items': '',
                'justify-content': '',
                'transform': '',
                'transition': '',
                'box-shadow': ''
            });
        }
        
        // Submission modal drag area expansion methods
        expandSubmissionModalDragArea() {
            if (this.submissionDragExpanded) return; // Already expanded
            
            // Clear any pending collapse timeout
            if (this.submissionDragTimeout) {
                clearTimeout(this.submissionDragTimeout);
                this.submissionDragTimeout = null;
            }
            
            this.submissionDragExpanded = true;
            this.submissionUploadArea.addClass('cf7as-drag-expanded');
            
            // Force inline styles for full modal coverage
            this.submissionUploadArea.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'right': '0',
                'bottom': '0',
                'width': '100vw',
                'height': '100vh',
                'z-index': '2147483649', // Higher than regular modal
                'background': 'rgba(59, 130, 246, 0.1)',
                'border': '3px dashed #3b82f6',
                'border-radius': '0',
                'margin': '0',
                'padding': '0',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'transform': 'none',
                'transition': 'none',
                'box-shadow': 'none'
            });
        }
        
        collapseSubmissionModalDragArea() {
            if (!this.submissionDragExpanded) return; // Already collapsed
            
            // Clear any existing timeout
            if (this.submissionDragTimeout) {
                clearTimeout(this.submissionDragTimeout);
            }
            
            // Add a small delay to prevent flickering during rapid events
            this.submissionDragTimeout = setTimeout(() => {
                this.submissionDragExpanded = false;
                this.submissionUploadArea.removeClass('cf7as-drag-expanded');
                
                // Reset inline styles
                this.submissionUploadArea.css({
                    'position': '',
                    'top': '',
                    'left': '',
                    'right': '',
                    'bottom': '',
                    'width': '',
                    'height': '',
                    'z-index': '',
                    'background': '',
                    'border': '',
                    'border-radius': '',
                    'margin': '',
                    'padding': '',
                    'display': '',
                    'align-items': '',
                    'justify-content': '',
                    'transform': '',
                    'transition': '',
                    'box-shadow': ''
                });
                
                this.submissionDragTimeout = null;
            }, 200);
        }
        
        collapseSubmissionModalDragAreaImmediate() {
            if (!this.submissionDragExpanded) return; // Already collapsed
            
            // Clear any pending timeout
            if (this.submissionDragTimeout) {
                clearTimeout(this.submissionDragTimeout);
                this.submissionDragTimeout = null;
            }
            
            // Collapse immediately
            this.submissionDragExpanded = false;
            this.submissionUploadArea.removeClass('cf7as-drag-expanded');
            
            // Reset inline styles immediately
            this.submissionUploadArea.css({
                'position': '',
                'top': '',
                'left': '',
                'right': '',
                'bottom': '',
                'width': '',
                'height': '',
                'z-index': '',
                'background': '',
                'border': '',
                'border-radius': '',
                'margin': '',
                'padding': '',
                'display': '',
                'align-items': '',
                'justify-content': '',
                'transform': '',
                'transition': '',
                'box-shadow': ''
            });
        }
        
        createUI() {
            const maxSizeGB = Math.round(this.options.maxSize / (1024*1024*1024));
            
            // Create button-based interface
            const html = `
                <div class="cf7as-custom-uploader">
                    <div class="cf7as-upload-button-container">
                        <button type="button" class="cf7as-open-modal-btn">
                            <span class="cf7as-upload-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7,10 12,5 17,10"></polyline>
                                    <line x1="12" y1="5" x2="12" y2="15"></line>
                                </svg>
                            </span>
                            <span>Upload Artwork Files</span>
                        </button>
                        <div class="cf7as-upload-summary">
                            <span class="cf7as-file-count">0 files selected</span>
                        </div>
                    </div>
                    
                    <!-- Full-screen modal -->
                    <div class="cf7as-upload-modal" style="display: none;">
                        <div class="cf7as-modal-overlay"></div>
                        <div class="cf7as-modal-content">
                            <div class="cf7as-modal-header">
                                <h2>Upload Artwork Files</h2>
                                <button type="button" class="cf7as-modal-close">&times;</button>
                            </div>
                            
                            <div class="cf7as-modal-body">
                                <div class="cf7as-modal-body-inner">
                                    <!-- Upload area - slides to top right when files added -->
                                    <div class="cf7as-upload-area" data-container-id="${this.containerId}">
                                        <div class="cf7as-upload-prompt">
                                            <div class="cf7as-upload-icon">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7,10 12,5 17,10"></polyline>
                                                    <line x1="12" y1="5" x2="12" y2="15"></line>
                                                </svg>
                                            </div>
                                            <div class="cf7as-upload-text">
                                                <h3>Drag & drop files here</h3>
                                                <p>or <button type="button" class="cf7as-browse-btn">browse files</button></p>
                                                <small>Max ${maxSizeGB}GB per file, up to ${this.options.maxFiles} files</small>
                                            </div>
                                        </div>
                                        <input type="file" class="cf7as-file-input" multiple style="display: none;">
                                    </div>
                                    
                                    <!-- Work content area - shows when files are added -->
                                    <div class="cf7as-work-content">
                                        <div class="cf7as-work-grid-container">
                                            <div class="cf7as-work-grid"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Work editing panel - fixed on right side -->
                                    <div class="cf7as-work-editor">
                                        <div class="cf7as-editor-header">
                                            <h3 class="cf7as-editor-title">Edit Work Details</h3>
                                            <p class="cf7as-editor-subtitle">Select a work to edit its information</p>
                                        </div>
                                        <div class="cf7as-editor-body">
                                            <div class="cf7as-editor-field">
                                                <label for="cf7as-selected-work-title">Work Title *</label>
                                                <input type="text" id="cf7as-selected-work-title" class="cf7as-selected-work-title" placeholder="Enter the title of this work">
                                            </div>
                                            <div class="cf7as-editor-field">
                                                <label for="cf7as-selected-work-statement">Work Statement</label>
                                                <textarea id="cf7as-selected-work-statement" class="cf7as-selected-work-statement" placeholder="Describe this work, techniques used, artistic intentions, etc." rows="4"></textarea>
                                            </div>
                                        </div>
                                        <div class="cf7as-editor-actions">
                                            <button type="button" class="cf7as-upload-single-btn">Upload This File</button>
                                            <button type="button" class="cf7as-remove-single-btn">Remove File</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="cf7as-modal-footer">
                                <div class="cf7as-upload-controls">
                                    <button type="button" class="cf7as-upload-all-btn">Upload All Files</button>
                                    <button type="button" class="cf7as-clear-all-btn">Clear All</button>
                                    <button type="button" class="cf7as-done-btn" style="display: none;">Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thumbnails preview outside modal -->
                    <div class="cf7as-thumbnails-preview"></div>
                    
                    <!-- Hidden input to store file data for Contact Form 7 -->
                    <input type="hidden" id="${this.containerId}_data" name="${this.containerId}_data" value="">
                </div>
            `;
            
            this.container.html(html);
            
            // Cache DOM elements
            this.openModalBtn = this.container.find('.cf7as-open-modal-btn');
            this.fileSummary = this.container.find('.cf7as-file-count');
            this.modal = this.container.find('.cf7as-upload-modal');
            this.modalOverlay = this.container.find('.cf7as-modal-overlay');
            this.modalClose = this.container.find('.cf7as-modal-close');
            this.modalBody = this.container.find('.cf7as-modal-body');
            this.modalBodyInner = this.container.find('.cf7as-modal-body-inner');
            this.uploadArea = this.container.find('.cf7as-upload-area');
            this.fileInput = this.container.find('.cf7as-file-input');
            this.workContent = this.container.find('.cf7as-work-content');
            this.workGrid = this.container.find('.cf7as-work-grid');
            this.workEditor = this.container.find('.cf7as-work-editor');
            this.uploadControls = this.container.find('.cf7as-upload-controls');
            this.browseBtn = this.container.find('.cf7as-browse-btn');
            this.uploadAllBtn = this.container.find('.cf7as-upload-all-btn');
            this.clearAllBtn = this.container.find('.cf7as-clear-all-btn');
            this.doneBtn = this.container.find('.cf7as-done-btn');
            this.thumbnailsPreview = this.container.find('.cf7as-thumbnails-preview');
            this.hiddenInput = this.container.find(`#${this.containerId}_data`);
            
            // Editor elements
            this.selectedWorkTitle = this.container.find('.cf7as-selected-work-title');
            this.selectedWorkStatement = this.container.find('.cf7as-selected-work-statement');
            this.uploadSingleBtn = this.container.find('.cf7as-upload-single-btn');
            this.removeSingleBtn = this.container.find('.cf7as-remove-single-btn');
            
            // Current selection tracking
            this.selectedFileId = null;
        }
        
        bindEvents() {
            const self = this;
            
            // Modal controls
            this.openModalBtn.on('click', function(e) {
                e.preventDefault();
                self.openModal();
            });
            
            this.modalClose.on('click', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            this.modalOverlay.on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            this.doneBtn.on('click', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Escape key to close modal
            $(document).on('keydown.cf7as-modal', function(e) {
                if (e.keyCode === 27 && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
            
            // Enhanced drag and drop events with dynamic expansion
            this.uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('cf7as-dragover');
            });
            
            this.uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Only remove dragover if leaving the upload area entirely
                if (!$.contains(this, e.relatedTarget)) {
                    $(this).removeClass('cf7as-dragover');
                }
            });
            
            this.uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('cf7as-dragover');
                
                // Collapse drag area immediately after drop
                if (self.files.length > 0) {
                    self.collapseDragAreaImmediate();
                }
                
                const files = e.originalEvent.dataTransfer.files;
                self.addFiles(files);
            });
            
            // Global drag events to handle drag expansion when files are present
            // Use document-level events for more reliable drag detection
            $(document).on('dragenter.cf7as-modal', function(e) {
                // Only handle when modal is open and has files
                if (self.modal.is(':visible') && self.files.length > 0) {
                    // Check if we're dragging files
                    const dt = e.originalEvent.dataTransfer;
                    if (dt && dt.types && dt.types.indexOf('Files') !== -1) {
                        self.expandDragArea();
                    }
                }
            });
            
            $(document).on('dragover.cf7as-modal', function(e) {
                // Maintain expansion during dragover
                if (self.modal.is(':visible') && self.files.length > 0 && self.dragExpanded) {
                    e.preventDefault(); // Prevent default to allow drop
                    // Keep the area expanded - don't collapse during dragover
                    if (self.dragTimeout) {
                        clearTimeout(self.dragTimeout);
                        self.dragTimeout = null;
                    }
                }
            });
            
            $(document).on('dragleave.cf7as-modal', function(e) {
                // Only collapse if we've left the entire window
                if (self.modal.is(':visible') && self.files.length > 0) {
                    // Check if cursor is leaving the window entirely
                    if (e.originalEvent.clientX <= 0 || e.originalEvent.clientY <= 0 || 
                        e.originalEvent.clientX >= window.innerWidth || 
                        e.originalEvent.clientY >= window.innerHeight) {
                        self.collapseDragArea();
                    }
                }
            });
            
            // Global drop event to ensure collapse happens
            $(document).on('drop.cf7as-modal', function(e) {
                if (self.modal.is(':visible') && self.files.length > 0) {
                    self.collapseDragAreaImmediate();
                }
            });
            
            // Browse button
            this.browseBtn.on('click', function(e) {
                e.preventDefault();
                self.fileInput.click();
            });
            
            // Upload area click - when compact, clicking the area opens file browser
            this.uploadArea.on('click', function(e) {
                e.preventDefault();
                // Only handle clicks when in compact mode (has files) 
                // and not during drag operations
                if (self.files.length > 0 && !self.dragExpanded) {
                    self.fileInput.click();
                }
            });
            
            // File input change
            this.fileInput.on('change', function() {
                self.addFiles(this.files);
                this.value = ''; // Clear input
            });
            
            // Control buttons
            this.uploadAllBtn.on('click', function(e) {
                e.preventDefault();
                self.uploadAll();
            });
            
            this.clearAllBtn.on('click', function(e) {
                e.preventDefault();
                self.clearAll();
            });
            
            // Work editor events
            this.selectedWorkTitle.on('input', function(e) {
                if (self.selectedFileId) {
                    const fileData = self.files.find(f => f.id === self.selectedFileId);
                    if (fileData) {
                        fileData.workTitle = e.target.value;
                        self.updateFormData();
                        self.updateFormSubmissionState();
                        self.updateWorkItemDisplay(self.selectedFileId);
                    }
                }
            });
            
            this.selectedWorkStatement.on('input', function(e) {
                if (self.selectedFileId) {
                    const fileData = self.files.find(f => f.id === self.selectedFileId);
                    if (fileData) {
                        fileData.workStatement = e.target.value;
                        self.updateFormData();
                    }
                }
            });
            
            this.uploadSingleBtn.on('click', function(e) {
                e.preventDefault();
                if (self.selectedFileId) {
                    self.uploadFile(self.selectedFileId);
                }
            });
            
            this.removeSingleBtn.on('click', function(e) {
                e.preventDefault();
                if (self.selectedFileId) {
                    self.removeFile(self.selectedFileId);
                }
            });
        }
        
        addFiles(fileList) {
            console.log('addFiles called with:', fileList ? fileList.length : 'null', 'files');
            
            if (!fileList || fileList.length === 0) {
                console.log('No files to add');
                return;
            }
            
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                console.log('Processing file:', file.name, 'type:', file.type, 'size:', file.size);
                
                // Check file count limit
                if (this.files.length >= this.options.maxFiles) {
                    this.showError(`Maximum ${this.options.maxFiles} files allowed`);
                    break;
                }
                
                // Check file size
                if (file.size > this.options.maxSize) {
                    this.showError(`File "${file.name}" is too large (max ${Math.round(this.options.maxSize / (1024*1024*1024))}GB)`);
                    continue;
                }
                
                // Check file type with fallback to extension detection
                let mimeType = file.type;
                
                if (!mimeType || mimeType === '') {
                    // Fallback to file extension detection
                    mimeType = this.getMimeTypeFromExtension(file.name);
                }
                
                if (!this.isAllowedType(mimeType)) {
                    this.showError(`File type "${mimeType}" not allowed for "${file.name}"`);
                    continue;
                }
                
                // Check for duplicates
                if (this.files.find(f => f.name === file.name && f.size === file.size)) {
                    this.showError(`File "${file.name}" already added`);
                    continue;
                }
                
                // Add to files array
                const fileData = {
                    id: this.generateFileId(),
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: mimeType, // Use the detected/fallback MIME type
                    status: 'pending', // pending, uploading, uploaded, error
                    progress: 0,
                    s3Key: null,
                    uploadUrl: null,
                    error: null,
                    // Metadata fields
                    workTitle: '',
                    workStatement: '',
                    // Chunking properties
                    isChunked: file.size > this.options.chunkThreshold,
                    chunks: [],
                    currentChunk: 0,
                    totalChunks: 0,
                    uploadId: null // For multipart upload
                };
                
                this.files.push(fileData);
                this.renderWorkItem(fileData);
            }
            
            this.updateUI();
            
            // Select first file if none selected
            if (this.files.length > 0 && !this.selectedFileId) {
                this.selectWorkItem(this.files[0].id);
            }
        }
        
        isAllowedType(mimeType) {
            return this.options.allowedTypes.includes(mimeType) || 
                   this.options.allowedTypes.some(type => {
                       if (type.endsWith('/*')) {
                           return mimeType.startsWith(type.slice(0, -1));
                       }
                       return false;
                   });
        }
        
        getMimeTypeFromExtension(filename) {
            const extension = filename.toLowerCase().split('.').pop();
            
            const mimeMap = {
                // Images
                'jpg': 'image/jpeg',
                'jpeg': 'image/jpeg',
                'png': 'image/png',
                'gif': 'image/gif',
                'webp': 'image/webp',
                'svg': 'image/svg+xml',
                // Videos
                'mp4': 'video/mp4',
                'avi': 'video/avi',
                'mov': 'video/mov',
                'wmv': 'video/wmv',
                'webm': 'video/webm',
                // Documents
                'pdf': 'application/pdf',
                'doc': 'application/msword',
                'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt': 'text/plain',
                'rtf': 'application/rtf'
            };
            
            return mimeMap[extension] || 'application/octet-stream';
        }
        
        generateFileId() {
            return 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        isImageFile(mimeType) {
            return mimeType.startsWith('image/');
        }
        
        renderWorkItem(fileData) {
            const fileSize = this.formatBytes(fileData.size);
            const fileIcon = this.getFileIcon(fileData.type);
            
            // Create preview content for different file types
            let previewContent = '';
            
            if (this.isImageFile(fileData.type)) {
                const objectUrl = window.URL ? window.URL.createObjectURL(fileData.file) : null;
                
                if (objectUrl) {
                    // Properly escape filename for inline event handlers
                    const escapedName = fileData.name.replace(/'/g, "\\'").replace(/"/g, '\\"');
                    
                    // Don't immediately revoke the URL, let it persist for thumbnail display
                    // Include fallback icon that shows if image fails to load
                    previewContent = `
                        <img src="${objectUrl}" alt="${this.escapeHtml(fileData.name)}" 
                            onload="this.style.opacity='1'; this.nextElementSibling.style.display='none';" 
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            style="opacity:0; transition: opacity 0.3s ease;">
                        <div class="cf7as-file-icon" style="display: flex;">${fileIcon}</div>`;
                } else {
                    previewContent = `<div class="cf7as-file-icon">${fileIcon}</div>`;
                }
            } else if (fileData.type.startsWith('video/')) {
                const objectUrl = window.URL ? window.URL.createObjectURL(fileData.file) : null;
                
                if (objectUrl) {
                    // Properly escape filename for inline event handlers
                    const escapedName = fileData.name.replace(/'/g, "\\'").replace(/"/g, '\\"');
                    
                    previewContent = `
                        <video src="${objectUrl}" muted preload="metadata" 
                            onloadeddata="this.style.opacity='1'; this.nextElementSibling.style.display='none';" 
                            onloadedmetadata="if(this.readyState >= 1) { this.style.opacity='1'; this.nextElementSibling.style.display='none'; }"
                            oncanplay="this.style.opacity='1'; this.nextElementSibling.style.display='none';"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            onsuspend="if(this.readyState >= 1) { this.style.opacity='1'; this.nextElementSibling.style.display='none'; }"
                            style="opacity:0; transition: opacity 0.3s ease; max-width: 100%; max-height: 100%; object-fit: cover;"></video>
                        <div class="cf7as-file-icon" style="display: none;">${fileIcon}</div>`;
                } else {
                    previewContent = `<div class="cf7as-file-icon">${fileIcon}</div>`;
                }
            } else {
                previewContent = `<div class="cf7as-file-icon">${fileIcon}</div>`;
            }
            
            const workItemHtml = `
                <div class="cf7as-work-item" data-file-id="${fileData.id}">
                    <div class="cf7as-work-preview">
                        ${previewContent}
                        <div class="cf7as-work-status-badge">${this.getStatusLabel(fileData.status)}</div>
                        <div class="cf7as-progress-overlay">
                            <div class="cf7as-progress-fill" style="width: ${fileData.progress}%"></div>
                        </div>
                    </div>
                    <div class="cf7as-work-info">
                        <div class="cf7as-work-title-display">${this.escapeHtml(fileData.workTitle || 'Untitled Work')}</div>
                        <div class="cf7as-work-filename">${this.escapeHtml(fileData.name)}</div>
                        <div class="cf7as-work-meta">
                            <span class="cf7as-work-size">${fileSize}</span>
                        </div>
                    </div>
                </div>
            `;
            
            const workElement = $(workItemHtml);
            
            // Add click handler for selection
            workElement.on('click', (e) => {
                e.preventDefault();
                this.selectWorkItem(fileData.id);
            });
            
            // Append to the appropriate grid based on which modal is active
            if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                this.submissionWorkGrid.append(workElement);
            } else if (this.workGrid && this.workGrid.length > 0) {
                this.workGrid.append(workElement);
            }
            
            // Setup fallback visibility for videos with metadata
            setTimeout(() => {
                // Find in both grids
                let appendedElement = null;
                if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                    appendedElement = this.submissionWorkGrid.find(`[data-file-id="${fileData.id}"]`);
                }
                if (!appendedElement || appendedElement.length === 0) {
                    if (this.workGrid && this.workGrid.length > 0) {
                        appendedElement = this.workGrid.find(`[data-file-id="${fileData.id}"]`);
                    }
                }
                
                if (appendedElement && appendedElement.length > 0) {
                    const previewDiv = appendedElement.find('.cf7as-work-preview');
                    
                    if (previewDiv.length > 0) {
                        const video = previewDiv.find('video');
                        if (video.length > 0) {
                            // Check if video failed to load
                            if (video[0].readyState === 0) {
                                // Try to force video loading
                                video[0].load();
                                
                                // Set a fallback timeout to show icon if video doesn't load
                                setTimeout(() => {
                                    if (video[0].readyState === 0) {
                                        video.css('display', 'none');
                                        previewDiv.find('.cf7as-file-icon').css('display', 'flex');
                                    }
                                }, 2000);
                            } else if (video[0].readyState >= 1) {
                                // Video has metadata, show it after a short delay if events haven't fired
                                setTimeout(() => {
                                    if (video.css('opacity') === '0' || video.css('opacity') === 0) {
                                        video.css('opacity', '1');
                                        previewDiv.find('.cf7as-file-icon').css('display', 'none');
                                    }
                                }, 1000);
                            }
                        }
                    }
                }
            }, 100);
        }
        
        selectWorkItem(fileId) {
            const fileData = this.files.find(f => f.id === fileId);
            if (!fileData) {
                return;
            }
            
            // Update visual selection - check both grids
            const workGrid = this.submissionWorkGrid && this.submissionWorkGrid.length > 0 ? this.submissionWorkGrid : this.workGrid;
            if (workGrid && workGrid.length > 0) {
                workGrid.find('.cf7as-work-item').removeClass('selected');
                workGrid.find(`[data-file-id="${fileId}"]`).addClass('selected');
            }
            
            // Update editor panel
            this.selectedFileId = fileId;
            this.selectedWorkTitle.val(fileData.workTitle);
            this.selectedWorkStatement.val(fileData.workStatement);
            
            // Update editor header
            this.workEditor.find('.cf7as-editor-title').text(`Edit: ${fileData.workTitle || fileData.name}`);
            this.workEditor.find('.cf7as-editor-subtitle').text(`${this.formatBytes(fileData.size)} • ${fileData.status}`);
            
            // Update action button states
            this.uploadSingleBtn.prop('disabled', fileData.status === 'uploaded' || fileData.status === 'uploading');
            if (fileData.status === 'uploaded') {
                this.uploadSingleBtn.text('File Uploaded');
            } else if (fileData.status === 'uploading') {
                this.uploadSingleBtn.text('Uploading...');
            } else {
                this.uploadSingleBtn.text('Upload This File');
            }
        }
        
        updateWorkItemDisplay(fileId) {
            const fileData = this.files.find(f => f.id === fileId);
            if (!fileData) {
                return;
            }
            
            const workElement = this.workGrid.find(`[data-file-id="${fileId}"]`);
            
            if (workElement.length > 0) {
                // Update title display
                workElement.find('.cf7as-work-title-display').text(fileData.workTitle || 'Untitled Work');
                
                // Update status
                workElement.removeClass('pending uploading uploaded error').addClass(fileData.status);
                
                const statusBadge = workElement.find('.cf7as-work-status-badge');
                if (statusBadge.length > 0) {
                    const newText = this.getStatusLabel(fileData.status);
                    statusBadge.text(newText);
                }
                
                // Update progress
                workElement.find('.cf7as-progress-fill').css('width', fileData.progress + '%');
                
                // Update editor if this item is selected
                if (this.selectedFileId === fileId) {
                    this.workEditor.find('.cf7as-editor-subtitle').text(`${this.formatBytes(fileData.size)} • ${this.getStatusLabel(fileData.status)}`);
                }
            }
        }
        
        getStatusLabel(status) {
            const statusLabels = {
                'pending': 'Ready',
                'uploading': 'Uploading',
                'uploaded': 'Complete',
                'error': 'Failed'
            };
            return statusLabels[status] || status;
        }
        
        getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) {
                return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21,15 16,10 5,21"></polyline>
                </svg>`;
            } else if (mimeType.startsWith('video/')) {
                return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="23,7 16,12 23,17"></polygon>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                </svg>`;
            } else if (mimeType === 'application/pdf') {
                return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"></path>
                </svg>`;
            } else {
                return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"></path>
                </svg>`;
            }
        }
        
        updateUI() {
            // Check if this is a modal context or regular uploader context
            const isModalContext = this.submissionModal && this.submissionModal.length > 0;
            
            // Update file count in button area (only for regular uploader)
            if (this.fileSummary && this.fileSummary.length > 0) {
                const fileCount = this.files.length;
                this.fileSummary.text(fileCount === 0 ? '0 files selected' : 
                                      fileCount === 1 ? '1 file selected' : 
                                      `${fileCount} files selected`);
            }
            
            // Update modal layout based on whether files exist
            if (this.modalBody && this.modalBody.length > 0) {
                if (this.files.length > 0) {
                    this.modalBody.addClass('has-files');
                } else {
                    this.modalBody.removeClass('has-files');
                    this.selectedFileId = null;
                }
            }
            
            // Update submission modal layout separately if it exists
            if (this.submissionModalBody && this.submissionModalBody.length > 0) {
                if (this.files.length > 0) {
                    this.submissionModalBody.addClass('has-files');
                } else {
                    this.submissionModalBody.removeClass('has-files');
                    this.selectedFileId = null;
                }
            }
            
            // Update thumbnails preview (only if element exists)
            if (this.thumbnailsPreview && this.thumbnailsPreview.length > 0) {
                this.updateThumbnailsPreview();
            }
            
            // Show/hide upload controls (only for regular uploader)
            if (this.uploadControls && this.uploadControls.length > 0) {
                if (this.files.length > 0) {
                    this.uploadControls.show();
                    
                    // Show done button if all files are uploaded
                    const uploadedFiles = this.files.filter(f => f.status === 'uploaded').length;
                    if (uploadedFiles === this.files.length && uploadedFiles > 0) {
                        if (this.doneBtn && this.doneBtn.length > 0) {
                            this.doneBtn.show();
                        }
                    } else {
                        if (this.doneBtn && this.doneBtn.length > 0) {
                            this.doneBtn.hide();
                        }
                    }
                } else {
                    this.uploadControls.hide();
                    if (this.doneBtn && this.doneBtn.length > 0) {
                        this.doneBtn.hide();
                    }
                }
            }
            
            // Update form data (only if method exists)
            if (typeof this.updateFormData === 'function') {
                this.updateFormData();
            }
            
            // Enable/disable form submission based on upload status
            this.updateFormSubmissionState();
        }
        
        updateWorkGrid() {
            // Grid rendering is handled by renderWorkItem() method
            // This method is kept for compatibility but does not interfere with thumbnails
        }
        
        updateWorkTitleDisplay(fileId, title) {
            // Update the title in the regular work grid
            if (this.workGrid && this.workGrid.length > 0) {
                const workItem = this.workGrid.find(`[data-file-id="${fileId}"]`);
                if (workItem.length > 0) {
                    workItem.find('.cf7as-work-title-display').text(title || 'Untitled Work');
                }
            }
            
            // Update the title in the submission work grid
            if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                const workItem = this.submissionWorkGrid.find(`[data-file-id="${fileId}"]`);
                if (workItem.length > 0) {
                    workItem.find('.cf7as-work-title-display').text(title || 'Untitled Work');
                }
            }
        }
        
        updateWorkEditor() {
            // Determine which elements to update based on which modal is active
            const workTitle = this.submissionSelectedWorkTitle && this.submissionSelectedWorkTitle.length > 0 
                            ? this.submissionSelectedWorkTitle 
                            : this.selectedWorkTitle;
            const workStatement = this.submissionSelectedWorkStatement && this.submissionSelectedWorkStatement.length > 0 
                                ? this.submissionSelectedWorkStatement 
                                : this.selectedWorkStatement;
            const uploadBtn = this.submissionUploadSingleBtn && this.submissionUploadSingleBtn.length > 0 
                            ? this.submissionUploadSingleBtn 
                            : this.uploadSingleBtn;
            const removeBtn = this.submissionRemoveSingleBtn && this.submissionRemoveSingleBtn.length > 0 
                            ? this.submissionRemoveSingleBtn 
                            : this.removeSingleBtn;
            
            if (!this.selectedFileId) {
                // No file selected, show default state
                if (workTitle) workTitle.val('');
                if (workStatement) workStatement.val('');
                if (uploadBtn) uploadBtn.prop('disabled', true);
                if (removeBtn) removeBtn.prop('disabled', true);
                return;
            }
            
            const file = this.files.find(f => f.id === this.selectedFileId);
            if (file) {
                if (workTitle) workTitle.val(file.workTitle || '');
                if (workStatement) workStatement.val(file.workStatement || '');
                if (uploadBtn) uploadBtn.prop('disabled', file.status !== 'pending');
                if (removeBtn) removeBtn.prop('disabled', false);
            }
        }
        
        selectFile(fileId) {
            this.selectedFileId = fileId;
            
            // Update visual selection in regular grid
            if (this.workGrid && this.workGrid.length > 0) {
                this.workGrid.find('.cf7as-work-item').removeClass('cf7as-work-selected');
                this.workGrid.find(`[data-file-id="${fileId}"]`).addClass('cf7as-work-selected');
            }
            
            // Update visual selection in submission grid
            if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                this.submissionWorkGrid.find('.cf7as-work-item').removeClass('cf7as-work-selected');
                this.submissionWorkGrid.find(`[data-file-id="${fileId}"]`).addClass('cf7as-work-selected');
            }
            
            // Update work editor
            this.updateWorkEditor();
        }
        
        getStatusIcon(status) {
            console.log('🏷️ getStatusIcon called with status:', status);
            let icon;
            
            switch (status) {
                case 'uploading':
                    icon = '<div class="cf7as-spinner"></div>';
                    console.log('  → Uploading spinner generated');
                    break;
                case 'uploaded':
                    icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2"><polyline points="20,6 9,17 4,12"></polyline></svg>';
                    console.log('  → Uploaded checkmark generated');
                    break;
                case 'error':
                    icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                    console.log('  → Error X icon generated');
                    break;
                default:
                    icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2"><circle cx="12" cy="12" r="3"></circle></svg>';
                    console.log('  → Default pending circle generated');
                    break;
            }
            
            console.log('  → Status icon HTML length:', icon.length);
            return icon;
        }
        
        updateThumbnailsPreview() {
            if (this.files.length === 0) {
                this.thumbnailsPreview.empty();
                return;
            }
            
            let previewHtml = '<div class="cf7as-thumbnails-grid">';
            
            this.files.forEach(fileData => {
                if (this.isImageFile(fileData.type)) {
                    const objectUrl = window.URL ? window.URL.createObjectURL(fileData.file) : null;
                    if (objectUrl) {
                        previewHtml += `
                            <div class="cf7as-thumbnail-item" data-file-id="${fileData.id}">
                                <img src="${objectUrl}" alt="${this.escapeHtml(fileData.name)}" onload="if(window.URL && window.URL.revokeObjectURL) window.URL.revokeObjectURL(this.src)">
                                <div class="cf7as-thumbnail-info">
                                    <div class="cf7as-thumbnail-name">${this.escapeHtml(fileData.workTitle || fileData.name)}</div>
                                    <div class="cf7as-thumbnail-status cf7as-status-${fileData.status}">${fileData.status}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        previewHtml += `
                            <div class="cf7as-thumbnail-item" data-file-id="${fileData.id}">
                                <div class="cf7as-file-icon-large">${this.getFileIcon(fileData.type)}</div>
                                <div class="cf7as-thumbnail-info">
                                    <div class="cf7as-thumbnail-name">${this.escapeHtml(fileData.workTitle || fileData.name)}</div>
                                    <div class="cf7as-thumbnail-status cf7as-status-${fileData.status}">${fileData.status}</div>
                                </div>
                            </div>
                        `;
                    }
                } else if (fileData.type.startsWith('video/')) {
                    const objectUrl = window.URL ? window.URL.createObjectURL(fileData.file) : null;
                    if (objectUrl) {
                        previewHtml += `
                            <div class="cf7as-thumbnail-item" data-file-id="${fileData.id}">
                                <video src="${objectUrl}" muted onloadeddata="if(window.URL && window.URL.revokeObjectURL) window.URL.revokeObjectURL(this.src)"></video>
                                <div class="cf7as-thumbnail-info">
                                    <div class="cf7as-thumbnail-name">${this.escapeHtml(fileData.workTitle || fileData.name)}</div>
                                    <div class="cf7as-thumbnail-status cf7as-status-${fileData.status}">${fileData.status}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        previewHtml += `
                            <div class="cf7as-thumbnail-item" data-file-id="${fileData.id}">
                                <div class="cf7as-file-icon-large">${this.getFileIcon(fileData.type)}</div>
                                <div class="cf7as-thumbnail-info">
                                    <div class="cf7as-thumbnail-name">${this.escapeHtml(fileData.workTitle || fileData.name)}</div>
                                    <div class="cf7as-thumbnail-status cf7as-status-${fileData.status}">${fileData.status}</div>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    previewHtml += `
                        <div class="cf7as-thumbnail-item" data-file-id="${fileData.id}">
                            <div class="cf7as-file-icon-large">${this.getFileIcon(fileData.type)}</div>
                            <div class="cf7as-thumbnail-info">
                                <div class="cf7as-thumbnail-name">${this.escapeHtml(fileData.workTitle || fileData.name)}</div>
                                <div class="cf7as-thumbnail-status cf7as-status-${fileData.status}">${fileData.status}</div>
                            </div>
                        </div>
                    `;
                }
            });
            
            previewHtml += '</div>';
            this.thumbnailsPreview.html(previewHtml);
        }
        
        updateFormData() {
            // Only update hidden input if it exists (regular uploader context)
            if (this.hiddenInput && this.hiddenInput.length > 0) {
                // Update hidden input with complete file data including metadata
                const fileData = this.files.filter(f => f.status === 'uploaded').map(f => ({
                    id: f.id,
                    filename: f.name,
                    size: f.size,
                    type: f.type,
                    s3_key: f.s3Key,
                    work_title: f.workTitle,
                    work_statement: f.workStatement
                }));
                
                this.hiddenInput.val(JSON.stringify(fileData));
            }
            // In modal context, form data is handled by finalizeSubmission method
        }
        
        updateFormSubmissionState() {
            const form = this.container.closest('form');
            if (!form.length) return;
            
            const pendingFiles = this.files.filter(f => f.status === 'pending').length;
            const uploadingFiles = this.files.filter(f => f.status === 'uploading').length;
            const uploadedFiles = this.files.filter(f => f.status === 'uploaded').length;
            const hasFiles = this.files.length > 0;
            
            // Check for missing work titles (required fields)
            const missingTitles = this.files.filter(f => !f.workTitle || f.workTitle.trim() === '').length;
            
            // Find submit button
            const submitBtn = form.find('input[type="submit"], button[type="submit"]');
            
            // Store original button text if not stored
            if (!submitBtn.data('original-value')) {
                submitBtn.data('original-value', submitBtn.val() || submitBtn.text() || 'Submit');
            }
            
            if (hasFiles) {
                if (missingTitles > 0) {
                    // Disable if work titles are missing
                    submitBtn.prop('disabled', true);
                    submitBtn.val('Please add work titles for all files');
                } else if (pendingFiles > 0) {
                    // Disable if files haven't been uploaded yet
                    submitBtn.prop('disabled', true);
                    submitBtn.val('Please upload all files first');
                } else if (uploadingFiles > 0) {
                    // Disable while files are uploading
                    submitBtn.prop('disabled', true);
                    submitBtn.val(`Uploading files... (${uploadedFiles}/${this.files.length})`);
                } else if (uploadedFiles === this.files.length) {
                    // Enable when all files are uploaded and have titles
                    submitBtn.prop('disabled', false);
                    submitBtn.val(submitBtn.data('original-value'));
                } else {
                    // Fallback - something's wrong
                    submitBtn.prop('disabled', true);
                    submitBtn.val('Please check file upload status');
                }
            } else {
                // No files - enable submission (form might not require files)
                submitBtn.prop('disabled', false);
                submitBtn.val(submitBtn.data('original-value'));
            }
            
            // Also update the button text in the uploader interface
            this.updateUploaderButtonState();
        }
        
        updateUploaderButtonState() {
            const pendingFiles = this.files.filter(f => f.status === 'pending').length;
            const uploadingFiles = this.files.filter(f => f.status === 'uploading').length;
            const uploadedFiles = this.files.filter(f => f.status === 'uploaded').length;
            const missingTitles = this.files.filter(f => !f.workTitle || f.workTitle.trim() === '').length;
            
            // Update the upload button state
            if (this.files.length === 0) {
                this.uploadAllBtn.prop('disabled', false).text('Upload All Files');
            } else if (missingTitles > 0) {
                this.uploadAllBtn.prop('disabled', true).text(`Add Titles for ${missingTitles} File${missingTitles > 1 ? 's' : ''}`);
            } else if (pendingFiles > 0) {
                this.uploadAllBtn.prop('disabled', false).text(`Upload ${pendingFiles} File${pendingFiles > 1 ? 's' : ''}`);
            } else if (uploadingFiles > 0) {
                this.uploadAllBtn.prop('disabled', true).text(`Uploading... (${uploadedFiles}/${this.files.length})`);
            } else if (uploadedFiles === this.files.length && this.files.length > 0) {
                this.uploadAllBtn.prop('disabled', true).text('All Files Uploaded');
            }
        }
        
        showStatus(message, isProcessing = true) {
            if (!this.statusMessage.length) return;
            
            this.statusMessage.find('.cf7as-status-text').text(message);
            if (isProcessing) {
                this.statusMessage.find('.cf7as-spinner').show();
            } else {
                this.statusMessage.find('.cf7as-spinner').hide();
            }
            this.statusMessage.show();
        }
        
        hideStatus() {
            if (this.statusMessage.length) {
                this.statusMessage.hide();
            }
        }
        
        async uploadFile(fileId) {
            const fileData = this.files.find(f => f.id === fileId);
            if (!fileData) return;
            
            console.log('Uploading file:', fileData.name, 'Size:', this.formatBytes(fileData.size));
            console.log('Chunked upload:', fileData.isChunked);
            
            try {
                fileData.status = 'uploading';
                this.updateFileStatus(fileId);
                
                if (fileData.isChunked) {
                    await this.uploadFileChunked(fileData);
                } else {
                    await this.uploadFileRegular(fileData);
                }
                
                fileData.status = 'uploaded';
                fileData.progress = 100;
                this.updateFileStatus(fileId);
                
                console.log('✅ Upload successful:', fileData.name);
                
            } catch (error) {
                console.error('❌ Upload failed:', error);
                fileData.status = 'error';
                fileData.error = error.message;
                this.updateFileStatus(fileId);
                this.showError(`Upload failed for "${fileData.name}": ${error.message}`);
            }
            
            this.updateUI();
        }
        
        async uploadFileRegular(fileData) {
            console.log('Using regular upload for:', fileData.name);
            
            // Get presigned URL
            const presignedData = await this.getPresignedUrl(fileData.file);
            fileData.uploadUrl = presignedData.url;
            fileData.s3Key = presignedData.key;
            
            // Upload to S3
            await this.uploadToS3(fileData, presignedData.url);
        }
        
        async uploadFileChunked(fileData) {
            console.log('Using chunked upload for:', fileData.name);
            
            // Initialize multipart upload
            await this.initializeMultipartUpload(fileData);
            
            // Prepare chunks
            this.prepareFileChunks(fileData);
            
            // Upload chunks
            await this.uploadChunks(fileData);
            
            // Complete multipart upload
            await this.completeMultipartUpload(fileData);
        }
        
        async initializeMultipartUpload(fileData) {
            console.log('Initializing multipart upload for:', fileData.name);
            
            const restUrl = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.rest_url 
                : '/wp-json/';
            const nonce = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.nonce 
                : '';
            
            const response = await fetch(restUrl + 'cf7as/v1/initiate-multipart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    filename: fileData.name,
                    type: fileData.type,
                    size: fileData.size
                })
            });
            
            if (!response.ok) {
                throw new Error(`Failed to initialize multipart upload: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success || !data.data) {
                throw new Error(data.message || 'Invalid multipart init response');
            }
            
            fileData.uploadId = data.data.uploadId;
            fileData.s3Key = data.data.key;
            
            console.log('Multipart upload initialized:', fileData.uploadId);
        }
        
        prepareFileChunks(fileData) {
            const file = fileData.file;
            const chunkSize = this.options.chunkSize;
            const totalChunks = Math.ceil(file.size / chunkSize);
            
            fileData.totalChunks = totalChunks;
            fileData.chunks = [];
            fileData.currentChunk = 0;
            
            console.log(`Preparing ${totalChunks} chunks of ${this.formatBytes(chunkSize)} each`);
            
            for (let i = 0; i < totalChunks; i++) {
                const start = i * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunkBlob = file.slice(start, end);
                
                fileData.chunks.push({
                    index: i + 1, // S3 part numbers start at 1
                    blob: chunkBlob,
                    size: end - start,
                    etag: null,
                    uploaded: false
                });
            }
        }
        
        async uploadChunks(fileData) {
            console.log(`Uploading ${fileData.chunks.length} chunks for:`, fileData.name);
            
            const restUrl = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.rest_url 
                : '/wp-json/';
            const nonce = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.nonce 
                : '';
            
            let uploadedBytes = 0;
            
            for (let i = 0; i < fileData.chunks.length; i++) {
                const chunk = fileData.chunks[i];
                fileData.currentChunk = i + 1;
                
                console.log(`Uploading chunk ${chunk.index}/${fileData.chunks.length} (${this.formatBytes(chunk.size)})`);
                
                try {
                    // Get presigned URL for this chunk
                    const chunkUrlResponse = await fetch(restUrl + 'cf7as/v1/upload-part-url', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce
                        },
                        body: JSON.stringify({
                            uploadId: fileData.uploadId,
                            key: fileData.s3Key,
                            partNumber: chunk.index
                        })
                    });
                    
                    if (!chunkUrlResponse.ok) {
                        throw new Error(`Failed to get chunk upload URL: ${chunkUrlResponse.status}`);
                    }
                    
                    const chunkUrlData = await chunkUrlResponse.json();
                    
                    if (!chunkUrlData.success || !chunkUrlData.data || !chunkUrlData.data.url) {
                        throw new Error(chunkUrlData.message || 'Invalid chunk URL response');
                    }
                    
                    // Upload the chunk
                    const etag = await this.uploadChunkToS3(chunk, chunkUrlData.data.url);
                    chunk.etag = etag;
                    chunk.uploaded = true;
                    
                    uploadedBytes += chunk.size;
                    
                    // Update progress based on chunks uploaded
                    const progress = Math.round((uploadedBytes / fileData.size) * 100);
                    fileData.progress = progress;
                    this.updateFileProgress(fileData.id, progress, {
                        current: chunk.index,
                        total: fileData.chunks.length
                    });
                    
                    console.log(`✅ Chunk ${chunk.index} uploaded successfully, ETag:`, etag);
                    
                } catch (error) {
                    console.error(`❌ Failed to upload chunk ${chunk.index}:`, error);
                    throw new Error(`Chunk ${chunk.index} upload failed: ${error.message}`);
                }
            }
            
            console.log('All chunks uploaded successfully');
        }
        
        async uploadChunkToS3(chunk, presignedUrl) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Get ETag from response headers
                        const etag = xhr.getResponseHeader('ETag');
                        if (!etag) {
                            reject(new Error('Missing ETag in response'));
                            return;
                        }
                        
                        // Remove quotes from ETag if present
                        const cleanETag = etag.replace(/"/g, '');
                        resolve(cleanETag);
                    } else {
                        console.error('Chunk upload failed, status:', xhr.status, 'response:', xhr.responseText);
                        reject(new Error(`Chunk upload failed: ${xhr.status} ${xhr.statusText}`));
                    }
                });
                
                xhr.addEventListener('error', () => {
                    console.error('Chunk upload network error');
                    reject(new Error('Network error during chunk upload'));
                });
                
                xhr.addEventListener('abort', () => {
                    console.error('Chunk upload aborted');
                    reject(new Error('Chunk upload aborted'));
                });
                
                // Open PUT request to S3
                xhr.open('PUT', presignedUrl);
                
                // CRITICAL: Do NOT set Content-Type header
                // S3 presigned URL only signs 'host' header
                console.log('Sending chunk PUT request to S3 with no explicit headers');
                
                // Send chunk data
                xhr.send(chunk.blob);
            });
        }
        
        async completeMultipartUpload(fileData) {
            console.log('Completing multipart upload for:', fileData.name);
            
            const restUrl = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.rest_url 
                : '/wp-json/';
            const nonce = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.nonce 
                : '';
            
            // Prepare parts data
            const parts = fileData.chunks
                .filter(chunk => chunk.uploaded && chunk.etag)
                .map(chunk => ({
                    PartNumber: chunk.index,
                    ETag: chunk.etag
                }));
            
            if (parts.length !== fileData.chunks.length) {
                throw new Error('Not all chunks were uploaded successfully');
            }
            
            const response = await fetch(restUrl + 'cf7as/v1/complete-multipart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    uploadId: fileData.uploadId,
                    key: fileData.s3Key,
                    parts: parts
                })
            });
            
            if (!response.ok) {
                throw new Error(`Failed to complete multipart upload: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to complete multipart upload');
            }
            
            console.log('✅ Multipart upload completed successfully');
        }
        
        async getPresignedUrl(file) {
            const restUrl = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.rest_url 
                : '/wp-json/';
            const nonce = (typeof cf7as_uploader_config !== 'undefined') 
                ? cf7as_uploader_config.nonce 
                : '';
            
            console.log('Getting presigned URL for:', file.name);
            console.log('REST URL:', restUrl);
            console.log('Endpoint:', restUrl + 'cf7as/v1/presigned-url');
            
            const response = await fetch(restUrl + 'cf7as/v1/presigned-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    filename: file.name,
                    type: file.type,
                    size: file.size
                })
            });
            
            if (!response.ok) {
                throw new Error(`Failed to get presigned URL: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success || !data.data || !data.data.url) {
                throw new Error(data.message || 'Invalid presigned URL response');
            }
            
            return data.data;
        }
        
        async uploadToS3(fileData, presignedUrl) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                // Progress tracking
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const progress = Math.round((e.loaded / e.total) * 100);
                        fileData.progress = progress;
                        this.updateFileProgress(fileData.id, progress);
                    }
                });
                
                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('S3 upload successful, status:', xhr.status);
                        resolve();
                    } else {
                        console.error('S3 upload failed, status:', xhr.status, 'response:', xhr.responseText);
                        reject(new Error(`S3 upload failed: ${xhr.status} ${xhr.statusText}`));
                    }
                });
                
                xhr.addEventListener('error', () => {
                    console.error('S3 upload network error');
                    reject(new Error('Network error during upload'));
                });
                
                xhr.addEventListener('abort', () => {
                    console.error('S3 upload aborted');
                    reject(new Error('Upload aborted'));
                });
                
                // Open PUT request to S3
                xhr.open('PUT', presignedUrl);
                
                // CRITICAL: Do NOT set Content-Type header
                // S3 presigned URL only signs 'host' header
                console.log('Sending PUT request to S3 with no explicit headers');
                
                // Send raw file data
                xhr.send(fileData.file);
            });
        }
        
        updateFileStatus(fileId) {
            const fileData = this.files.find(f => f.id === fileId);
            if (!fileData) return;
            
            // Update work item display
            this.updateWorkItemDisplay(fileId);
            
            // Update form submission state when file status changes
            this.updateFormSubmissionState();
        }
        
        updateFileProgress(fileId, progress, chunkInfo = null) {
            const fileData = this.files.find(f => f.id === fileId);
            if (!fileData) return;
            
            fileData.progress = progress;
            
            // Update work item progress
            const workElement = this.workGrid.find(`[data-file-id="${fileId}"]`);
            workElement.find('.cf7as-progress-fill').css('width', progress + '%');
            
            // Update editor if this item is selected
            if (this.selectedFileId === fileId) {
                let statusText = `${this.formatBytes(fileData.size)} • ${fileData.status}`;
                if (chunkInfo) {
                    statusText += ` (chunk ${chunkInfo.current}/${chunkInfo.total})`;
                } else if (progress > 0 && progress < 100) {
                    statusText += ` (${progress}%)`;
                }
                this.workEditor.find('.cf7as-editor-subtitle').text(statusText);
            }
        }
        
        async uploadAll() {
            if (this.isUploading) return;
            
            const pendingFiles = this.files.filter(f => f.status === 'pending');
            if (pendingFiles.length === 0) {
                this.showError('No files to upload');
                return;
            }
            
            // Check for missing titles before uploading
            const missingTitles = this.files.filter(f => !f.workTitle || f.workTitle.trim() === '');
            if (missingTitles.length > 0) {
                this.showError('Please add work titles for all files before uploading.');
                return;
            }
            
            this.isUploading = true;
            this.uploadAllBtn.prop('disabled', true).text('Uploading...');
            
            console.log('Starting upload of', pendingFiles.length, 'files');
            
            try {
                // Upload files one by one (could be made concurrent if needed)
                for (const fileData of pendingFiles) {
                    await this.uploadFile(fileData.id);
                }
                
                console.log('All uploads completed successfully');
                
            } catch (error) {
                console.error('Upload batch failed:', error);
                throw error; // Re-throw to be caught by uploadAllThenSubmit
                
            } finally {
                this.isUploading = false;
                this.updateUploaderButtonState();
            }
        }
        
        removeFile(fileId) {
            console.log('Removing file:', fileId);
            
            // Find the file data before removing it
            const fileData = this.files.find(f => f.id === fileId);
            
            // Clean up object URLs if they exist
            if (fileData && (this.isImageFile(fileData.type) || fileData.type.startsWith('video/'))) {
                // Check both regular and submission grids for the element
                let workElement = null;
                
                if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                    workElement = this.submissionWorkGrid.find(`[data-file-id="${fileId}"]`);
                }
                
                if (!workElement || workElement.length === 0) {
                    if (this.workGrid && this.workGrid.length > 0) {
                        workElement = this.workGrid.find(`[data-file-id="${fileId}"]`);
                    }
                }
                
                if (workElement && workElement.length > 0) {
                    const img = workElement.find('img');
                    const video = workElement.find('video');
                    
                    if (img.length && img.attr('src') && img.attr('src').startsWith('blob:')) {
                        try {
                            window.URL.revokeObjectURL(img.attr('src'));
                        } catch (e) {
                            console.warn('Failed to revoke object URL for image:', e);
                        }
                    }
                    
                    if (video.length && video.attr('src') && video.attr('src').startsWith('blob:')) {
                        try {
                            window.URL.revokeObjectURL(video.attr('src'));
                        } catch (e) {
                            console.warn('Failed to revoke object URL for video:', e);
                        }
                    }
                }
            }
            
            // Remove from array
            this.files = this.files.filter(f => f.id !== fileId);
            
            // Remove from DOM - check both grids
            if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                this.submissionWorkGrid.find(`[data-file-id="${fileId}"]`).remove();
            }
            
            if (this.workGrid && this.workGrid.length > 0) {
                this.workGrid.find(`[data-file-id="${fileId}"]`).remove();
            }
            
            // Clear selection if this file was selected
            if (this.selectedFileId === fileId) {
                this.selectedFileId = null;
                if (this.files.length > 0) {
                    // Select first available file
                    this.selectWorkItem(this.files[0].id);
                }
            }
            
            this.updateUI();
        }
        
        clearAll() {
            if (this.isUploading) {
                if (!confirm('Upload in progress. Are you sure you want to clear all files?')) {
                    return;
                }
            }
            
            // Clean up object URLs before clearing files
            this.files.forEach(fileData => {
                if (this.isImageFile(fileData.type) || fileData.type.startsWith('video/')) {
                    // Check both grids for the element
                    let workElement = null;
                    
                    if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                        workElement = this.submissionWorkGrid.find(`[data-file-id="${fileData.id}"]`);
                    }
                    
                    if (!workElement || workElement.length === 0) {
                        if (this.workGrid && this.workGrid.length > 0) {
                            workElement = this.workGrid.find(`[data-file-id="${fileData.id}"]`);
                        }
                    }
                    
                    if (workElement && workElement.length > 0) {
                        const img = workElement.find('img');
                        const video = workElement.find('video');
                        
                        if (img.length && img.attr('src') && img.attr('src').startsWith('blob:')) {
                            try {
                                window.URL.revokeObjectURL(img.attr('src'));
                            } catch (e) {
                                console.warn('Failed to revoke object URL for image:', e);
                            }
                        }
                        
                        if (video.length && video.attr('src') && video.attr('src').startsWith('blob:')) {
                            try {
                                window.URL.revokeObjectURL(video.attr('src'));
                            } catch (e) {
                                console.warn('Failed to revoke object URL for video:', e);
                            }
                        }
                    }
                }
            });
            
            this.files = [];
            this.uploadQueue = [];
            this.selectedFileId = null;
            
            // Clear both grids
            if (this.submissionWorkGrid && this.submissionWorkGrid.length > 0) {
                this.submissionWorkGrid.empty();
            }
            
            if (this.workGrid && this.workGrid.length > 0) {
                this.workGrid.empty();
            }
            
            this.updateUI();
            
            console.log('All files cleared');
        }
        
        showError(message, type = 'error') {
            console.log(type === 'warning' ? 'Upload warning:' : 'Upload error:', message);
            
            // Create notification
            const iconSvg = type === 'warning' ? 
                `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>` :
                `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>`;
            
            const messageClass = type === 'warning' ? 'cf7as-warning-message' : 'cf7as-error-message';
            
            const messageHtml = `
                <div class="${messageClass}">
                    ${iconSvg}
                    <span>${this.escapeHtml(message)}</span>
                    <button type="button" class="cf7as-error-close">&times;</button>
                </div>
            `;
            
            const messageElement = $(messageHtml);
            
            // Remove existing messages of same type
            this.container.find(`.${messageClass}`).remove();
            this.container.prepend(messageElement);
            
            // Auto-hide after timeout (longer for warnings since they need more reading time)
            const timeout = type === 'warning' ? 8000 : 5000;
            setTimeout(() => {
                messageElement.fadeOut(300, () => messageElement.remove());
            }, timeout);
            
            // Manual close
            messageElement.find('.cf7as-error-close').on('click', () => {
                messageElement.fadeOut(300, () => messageElement.remove());
            });
        }
        
        formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
    
    // Initialize uploaders when DOM is ready
    $(document).ready(function() {
        console.log('CF7 Artist Submissions Custom Uploader Script Loaded');
        
        // Find all uploader containers
        const containers = $('.cf7as-uploader');
        console.log('Found', containers.length, 'uploader containers');
        
        containers.each(function() {
            const $container = $(this);
            
            // Skip if already initialized
            if ($container.hasClass('custom-uploader-initialized')) {
                return;
            }
            
            // Initialize custom uploader
            new CF7ArtistSubmissionsUploader($container);
            
            console.log('Custom uploader initialized for:', $container.attr('id'));
        });
    });
    
})(jQuery);
