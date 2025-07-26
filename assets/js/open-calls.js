/**
 * CF7 Artist Submissions - Open Calls Settings Interface
 *
 * Interactive JavaScript functionality for the Open Calls settings tab,
 * providing dynamic add/remove capabilities, form management, and
 * real-time title updates for multiple open call configurations.
 *
 * Features:
 * • Dynamic add/remove open call configurations
 * • Real-time title updates in header sections
 * • Collapsible content sections with smooth animations
 * • Form field management and validation
 * • Index management for proper form submission
 * • Confirmation dialogs for destructive actions
 *
 * @package CF7_Artist_Submissions
 * @subpackage Assets/JS
 * @since 1.2.0
 * @version 1.2.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // ============================================================================
    // OPEN CALLS INTERFACE CONTROLLER
    // ============================================================================
    
    /**
     * Open Calls management system for dynamic configuration interface.
     * Handles all interactive elements within the open calls settings tab.
     */
    const CF7OpenCalls = {
        
        /**
         * Global call index counter for new call creation
         */
        callIndex: 0,
        
        /**
         * Initialize the open calls interface
         */
        init: function() {
            // Set initial call index based on existing calls
            this.callIndex = $('.cf7-open-call-item').length;
            
            // Bind all event handlers
            this.bindEvents();
            
            // Initialize existing calls state
            this.initializeExistingCalls();
        },
        
        /**
         * Bind all event handlers for open calls functionality
         */
        bindEvents: function() {
            // Add new open call
            $(document).on('click', '#cf7-add-open-call', this.addNewCall.bind(this));
            
            // Remove open call
            $(document).on('click', '.cf7-remove-call', this.removeCall.bind(this));
            
            // Toggle call content
            $(document).on('click', '.cf7-toggle-call', this.toggleCallContent.bind(this));
            
            // Update call title in header when typing
            $(document).on('input', '.cf7-call-title-input', this.updateCallTitle.bind(this));
            
            // Handle form submission to ensure proper data
            $(document).on('submit', '#cf7-open-calls-form', this.handleFormSubmission.bind(this));
        },
        
        /**
         * Initialize existing calls on page load
         */
        initializeExistingCalls: function() {
            // Show all call content by default (remove max-height restriction)
            $('.cf7-call-content').css({
                'max-height': 'none',
                'overflow': 'visible',
                'padding': '20px'
            }).show();
            
            // Ensure all toggle icons show expanded state
            $('.cf7-toggle-call .dashicons').removeClass('dashicons-arrow-down-alt2')
                                           .addClass('dashicons-arrow-up-alt2');
            
            // Remove any collapsed classes
            $('.cf7-open-call-item').removeClass('collapsed');
        },
        
        /**
         * Add a new open call configuration
         */
        addNewCall: function(e) {
            e.preventDefault();
            
            const template = $('#cf7-open-call-template').html();
            if (!template) {
                console.error('CF7 Open Calls: Template not found');
                return;
            }
            
            // Replace placeholder with current index
            const newCall = template.replace(/\{\{INDEX\}\}/g, this.callIndex);
            
            // Add to the list
            $('#cf7-open-calls-list').append(newCall);
            
            // Get the newly added call
            const $newCall = $('.cf7-open-call-item').last();
            
            // Expand the newly added call with proper CSS
            $newCall.find('.cf7-call-content').css({
                'max-height': 'none',
                'overflow': 'visible',
                'padding': '20px'
            }).show();
            
            $newCall.find('.cf7-toggle-call .dashicons')
                   .removeClass('dashicons-arrow-down-alt2')
                   .addClass('dashicons-arrow-up-alt2');
            
            $newCall.removeClass('collapsed');
            
            // Focus on title input for immediate editing
            $newCall.find('.cf7-call-title-input').focus();
            
            // Increment index for next call
            this.callIndex++;
            
            // Scroll to new call
            $('html, body').animate({
                scrollTop: $newCall.offset().top - 50
            }, 500);
        },
        
        /**
         * Remove an existing open call configuration
         */
        removeCall: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $call = $button.closest('.cf7-open-call-item');
            const callTitle = $call.find('.cf7-call-title-input').val() || 'New Open Call';
            
            // Confirm removal
            if (!confirm('Are you sure you want to remove "' + callTitle + '"? This action cannot be undone.')) {
                return;
            }
            
            // Animate removal
            $call.slideUp(400, function() {
                $(this).remove();
                // Update indices after removal
                CF7OpenCalls.updateCallIndices();
            });
        },
        
        /**
         * Toggle call content visibility
         */
        toggleCallContent: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $call = $button.closest('.cf7-open-call-item');
            const $content = $call.find('.cf7-call-content');
            const $icon = $button.find('.dashicons');
            
            // Toggle content with proper CSS states
            if ($content.is(':visible')) {
                // Hide content
                $content.slideUp(300, function() {
                    $content.css({
                        'max-height': '0',
                        'overflow': 'hidden',
                        'padding': '0 20px'
                    });
                });
                $icon.removeClass('dashicons-arrow-up-alt2')
                     .addClass('dashicons-arrow-down-alt2');
                $call.addClass('collapsed');
            } else {
                // Show content
                $content.css({
                    'max-height': 'none',
                    'overflow': 'visible',
                    'padding': '20px'
                }).slideDown(300);
                $icon.removeClass('dashicons-arrow-down-alt2')
                     .addClass('dashicons-arrow-up-alt2');
                $call.removeClass('collapsed');
            }
        },
        
        /**
         * Update call title in header when user types
         */
        updateCallTitle: function(e) {
            const $input = $(e.currentTarget);
            const $call = $input.closest('.cf7-open-call-item');
            const $titleDisplay = $call.find('.cf7-call-title');
            
            // Get new title or use default
            const newTitle = $input.val().trim() || 'New Open Call';
            
            // Update the header title with icon
            $titleDisplay.html('<span class="dashicons dashicons-megaphone"></span>' + this.escapeHtml(newTitle));
        },
        
        /**
         * Update all call indices after removal
         */
        updateCallIndices: function() {
            $('.cf7-open-call-item').each(function(index) {
                const $call = $(this);
                
                // Update data attribute
                $call.attr('data-index', index);
                
                // Update all form field names
                $call.find('input, select, textarea').each(function() {
                    const $field = $(this);
                    const currentName = $field.attr('name');
                    
                    if (currentName && currentName.includes('[calls][')) {
                        const newName = currentName.replace(/\[calls\]\[\d+\]/, '[calls][' + index + ']');
                        $field.attr('name', newName);
                    }
                });
            });
            
            // Update global counter
            this.callIndex = $('.cf7-open-call-item').length;
        },
        
        /**
         * Handle form submission to ensure data integrity
         */
        handleFormSubmission: function(e) {
            // Validate that at least one call is configured
            const callCount = $('.cf7-open-call-item').length;
            
            if (callCount === 0) {
                e.preventDefault();
                alert('Please add at least one open call before saving.');
                return false;
            }
            
            // Check for empty titles
            let hasEmptyTitle = false;
            $('.cf7-call-title-input').each(function() {
                if (!$(this).val().trim()) {
                    hasEmptyTitle = true;
                    return false;
                }
            });
            
            if (hasEmptyTitle) {
                const proceed = confirm('Some open calls have empty titles. Would you like to continue anyway?');
                if (!proceed) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // ============================================================================
    // INITIALIZATION
    // ============================================================================
    
    // Initialize when the open calls tab is present
    if ($('#cf7-open-calls-form').length || $('.cf7-open-calls-container').length) {
        CF7OpenCalls.init();
    }
    
    // Also initialize when switching to the open calls tab
    $(document).on('cf7_tab_changed', function(e, tabId) {
        if (tabId === 'open-calls-tab' || $(tabId).find('.cf7-open-calls-container').length) {
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                CF7OpenCalls.init();
            }, 100);
        }
    });
    
    // Export to global scope for debugging
    window.CF7OpenCalls = CF7OpenCalls;
});
