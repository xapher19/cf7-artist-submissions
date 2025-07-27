/**
 * CF7 Artist Submissions - Ratings System JavaScript
 *
 * Interactive functionality for the work rating and commenting system.
 * Provides smooth animations, AJAX operations, and user feedback for
 * rating submitted works with 5-star system and curator comments.
 *
 * @package CF7_Artist_Submissions
 * @subpackage JavaScript
 * @since 1.2.0
 * @version 1.2.0
 */

(function($) {
    'use strict';
    
    /**
     * CF7 Ratings System
     * 
     * Manages all rating interactions including star selection,
     * comment editing, saving operations, and visual feedback.
     */
    class CF7RatingsSystem {
        
        constructor() {
            this.init();
        }
        
        /**
         * Initialize the ratings system
         */
        init() {
            this.bindEvents();
            this.initializeExistingRatings();
        }
        
        /**
         * Bind event handlers
         */
        bindEvents() {
            // Star rating interactions
            $(document).on('click', '.cf7-star', this.handleStarClick.bind(this));
            $(document).on('mouseenter', '.cf7-star', this.handleStarHover.bind(this));
            $(document).on('mouseleave', '.cf7-rating-stars', this.handleStarsLeave.bind(this));
            
            // Clear rating button
            $(document).on('click', '.cf7-clear-rating', this.handleClearRating.bind(this));
            
            // Save rating and comments
            $(document).on('click', '.cf7-save-rating', this.handleSaveRating.bind(this));
            
            // Auto-save comments on blur (optional)
            $(document).on('blur', '.cf7-work-comments', this.handleCommentBlur.bind(this));
            
            // Track changes to comments
            $(document).on('input', '.cf7-work-comments', this.handleCommentInput.bind(this));
        }
        
        /**
         * Initialize existing ratings display
         */
        initializeExistingRatings() {
            $('.cf7-rating-stars').each((index, element) => {
                const $stars = $(element);
                const currentRating = parseInt($stars.data('current-rating')) || 0;
                this.updateStarsDisplay($stars, currentRating);
            });
        }
        
        /**
         * Handle star click events
         */
        handleStarClick(e) {
            e.preventDefault();
            const $star = $(e.currentTarget);
            const $starsContainer = $star.closest('.cf7-rating-stars');
            const $ratingContainer = $star.closest('.cf7-work-rating');
            const rating = parseInt($star.data('rating'));
            
            // Add animation effect
            $star.addClass('cf7-rating-animation');
            setTimeout(() => $star.removeClass('cf7-rating-animation'), 300);
            
            // Update display
            this.updateStarsDisplay($starsContainer, rating);
            
            // Store current rating
            $starsContainer.data('current-rating', rating);
            
            // Clear any previous status
            this.clearStatus($starsContainer);
            
            // Enable auto-save after a short delay
            this.scheduleAutoSave($ratingContainer);
        }
        
        /**
         * Handle star hover effects
         */
        handleStarHover(e) {
            const $star = $(e.currentTarget);
            const $starsContainer = $star.closest('.cf7-rating-stars');
            const rating = parseInt($star.data('rating'));
            
            // Don't show hover effect if currently saving
            if ($starsContainer.hasClass('cf7-saving')) {
                return;
            }
            
            // Temporarily update display for hover effect
            this.updateStarsDisplay($starsContainer, rating, true);
        }
        
        /**
         * Handle mouse leave from stars container
         */
        handleStarsLeave(e) {
            const $starsContainer = $(e.currentTarget);
            const currentRating = parseInt($starsContainer.data('current-rating')) || 0;
            
            // Don't revert if currently saving
            if ($starsContainer.hasClass('cf7-saving')) {
                return;
            }
            
            // Revert to current rating
            this.updateStarsDisplay($starsContainer, currentRating);
        }
        
        /**
         * Handle clear rating button
         */
        handleClearRating(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $starsContainer = $button.closest('.cf7-rating-stars');
            const $ratingContainer = $button.closest('.cf7-work-rating');
            
            if (confirm(cf7RatingsAjax.strings.confirm_remove)) {
                this.updateStarsDisplay($starsContainer, 0);
                $starsContainer.data('current-rating', 0);
                this.clearStatus($starsContainer);
                this.scheduleAutoSave($ratingContainer);
            }
        }
        
        /**
         * Handle save rating button click
         */
        handleSaveRating(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $ratingContainer = $button.closest('.cf7-work-rating');
            
            this.saveRating($ratingContainer);
        }
        
        /**
         * Handle comment blur for auto-save
         */
        handleCommentBlur(e) {
            const $textarea = $(e.currentTarget);
            const $ratingContainer = $textarea.closest('.cf7-work-rating');
            
            // Only auto-save if content has changed
            if ($textarea.data('changed')) {
                this.scheduleAutoSave($ratingContainer);
            }
        }
        
        /**
         * Handle comment input to track changes
         */
        handleCommentInput(e) {
            const $textarea = $(e.currentTarget);
            $textarea.data('changed', true);
            
            // Clear any previous status
            const $ratingContainer = $textarea.closest('.cf7-work-rating');
            this.clearStatus($ratingContainer.find('.cf7-rating-stars'));
        }
        
        /**
         * Update stars visual display
         */
        updateStarsDisplay($starsContainer, rating, isHover = false) {
            const $stars = $starsContainer.find('.cf7-star').not('.cf7-clear-rating');
            
            $stars.each((index, star) => {
                const $star = $(star);
                const starValue = parseInt($star.data('rating'));
                
                if (starValue <= rating) {
                    $star.addClass('active');
                } else {
                    $star.removeClass('active');
                }
            });
            
            // Add hover class if this is a hover effect
            if (isHover) {
                $starsContainer.addClass('cf7-hover-effect');
            } else {
                $starsContainer.removeClass('cf7-hover-effect');
            }
        }
        
        /**
         * Schedule auto-save with debouncing
         */
        scheduleAutoSave($container) {
            // Clear any existing timeout
            if ($container.data('save-timeout')) {
                clearTimeout($container.data('save-timeout'));
            }
            
            // Schedule new save
            const timeout = setTimeout(() => {
                this.saveRating($container);
            }, 1500); // Save after 1.5 seconds of inactivity
            
            $container.data('save-timeout', timeout);
        }
        
        /**
         * Save rating and comments via AJAX
         */
        saveRating($ratingContainer) {
            const $starsContainer = $ratingContainer.find('.cf7-rating-stars');
            const $textarea = $ratingContainer.find('.cf7-work-comments');
            const $saveButton = $ratingContainer.find('.cf7-save-rating');
            
            // Get data
            const submissionId = $ratingContainer.data('submission-id');
            const fileId = $ratingContainer.data('file-id');
            const rating = parseInt($starsContainer.data('current-rating')) || 0;
            const comments = $textarea.val().trim();
            
            // Clear any existing timeout
            if ($ratingContainer.data('save-timeout')) {
                clearTimeout($ratingContainer.data('save-timeout'));
            }
            
            // Show saving state
            this.showSavingState($ratingContainer, true);
            
            // Disable interactions
            $starsContainer.addClass('cf7-saving');
            $saveButton.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: cf7RatingsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_save_work_rating',
                    nonce: cf7RatingsAjax.nonce,
                    submission_id: submissionId,
                    file_id: fileId,
                    rating: rating,
                    comments: comments
                },
                success: (response) => {
                    this.handleSaveSuccess($ratingContainer, response);
                },
                error: (xhr, status, error) => {
                    this.handleSaveError($ratingContainer, error);
                },
                complete: () => {
                    // Re-enable interactions
                    $starsContainer.removeClass('cf7-saving');
                    $saveButton.prop('disabled', false);
                    this.showSavingState($ratingContainer, false);
                }
            });
        }
        
        /**
         * Handle successful save response
         */
        handleSaveSuccess($ratingContainer, response) {
            if (response.success) {
                // Show success status
                this.showStatus($ratingContainer, cf7RatingsAjax.strings.saved, 'success');
                
                // Add success animation
                $ratingContainer.addClass('cf7-save-success');
                setTimeout(() => $ratingContainer.removeClass('cf7-save-success'), 600);
                
                // Clear changed flag
                $ratingContainer.find('.cf7-work-comments').data('changed', false);
                
                // Update last updated time if provided
                if (response.data && response.data.timestamp) {
                    this.updateLastUpdated($ratingContainer, response.data.timestamp);
                }
                
                // Clear success message after delay
                setTimeout(() => {
                    this.clearStatus($ratingContainer.find('.cf7-rating-stars'));
                }, 3000);
                
            } else {
                this.handleSaveError($ratingContainer, response.data || 'Unknown error');
            }
        }
        
        /**
         * Handle save error
         */
        handleSaveError($ratingContainer, error) {
            const errorMessage = typeof error === 'string' ? error : cf7RatingsAjax.strings.error;
            this.showStatus($ratingContainer, errorMessage, 'error');
            
            // Clear error message after delay
            setTimeout(() => {
                this.clearStatus($ratingContainer.find('.cf7-rating-stars'));
            }, 5000);
        }
        
        /**
         * Show saving state
         */
        showSavingState($ratingContainer, saving) {
            const $saveButton = $ratingContainer.find('.cf7-save-rating');
            const $saveText = $saveButton.find('.cf7-save-text');
            const $saveSpinner = $saveButton.find('.cf7-save-spinner');
            
            if (saving) {
                $saveText.hide();
                $saveSpinner.show();
                this.showStatus($ratingContainer, cf7RatingsAjax.strings.saving, 'saving');
            } else {
                $saveText.show();
                $saveSpinner.hide();
            }
        }
        
        /**
         * Show status message
         */
        showStatus($ratingContainer, message, type) {
            const $statusContainer = $ratingContainer.find('.cf7-rating-status');
            $statusContainer
                .removeClass('success error saving')
                .addClass(type)
                .text(message);
        }
        
        /**
         * Clear status message
         */
        clearStatus($starsContainer) {
            const $statusContainer = $starsContainer.closest('.cf7-work-rating').find('.cf7-rating-status');
            $statusContainer
                .removeClass('success error saving')
                .text('');
        }
        
        /**
         * Update last updated timestamp
         */
        updateLastUpdated($ratingContainer, timestamp) {
            const $lastUpdated = $ratingContainer.find('.cf7-last-updated');
            if ($lastUpdated.length) {
                // Format timestamp (assuming it's in MySQL format)
                const date = new Date(timestamp);
                const formatted = date.toLocaleString();
                $lastUpdated.text(cf7RatingsAjax.strings.last_updated + ' ' + formatted);
            }
        }
        
        /**
         * Load rating data for a specific work
         */
        loadRatingData(submissionId, fileId) {
            return $.ajax({
                url: cf7RatingsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_get_work_rating',
                    nonce: cf7RatingsAjax.nonce,
                    submission_id: submissionId,
                    file_id: fileId
                }
            });
        }
        
        /**
         * Refresh rating display from server
         */
        refreshRating($ratingContainer) {
            const submissionId = $ratingContainer.data('submission-id');
            const fileId = $ratingContainer.data('file-id');
            
            this.loadRatingData(submissionId, fileId)
                .done((response) => {
                    if (response.success && response.data) {
                        const data = response.data;
                        
                        // Update stars
                        const $starsContainer = $ratingContainer.find('.cf7-rating-stars');
                        this.updateStarsDisplay($starsContainer, data.rating);
                        $starsContainer.data('current-rating', data.rating);
                        
                        // Update comments
                        $ratingContainer.find('.cf7-work-comments').val(data.comments);
                        
                        // Update timestamp
                        if (data.updated_at) {
                            this.updateLastUpdated($ratingContainer, data.updated_at);
                        }
                    }
                })
                .fail(() => {
                    this.showStatus($ratingContainer, cf7RatingsAjax.strings.error, 'error');
                });
        }
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Initialize ratings system
        window.cf7RatingsInstance = new CF7RatingsSystem();
        
        // Check if ratings are immediately available
        const initialRatingContainers = $('.cf7-work-rating').length;
        
        // Re-initialize when tabs change (especially when switching to "Submitted Works" tab)
        $(document).on('cf7_tab_changed', function(event, tabId) {
            // Small delay to allow content to load
            setTimeout(function() {
                const ratingContainers = $('.cf7-work-rating').length;
                
                if (ratingContainers > 0 && window.cf7RatingsInstance) {
                    window.cf7RatingsInstance.initializeExistingRatings();
                }
            }, 100);
        });
        
        // Fallback: Check periodically for new rating containers
        let checkCount = 0;
        const checkInterval = setInterval(function() {
            checkCount++;
            const ratingContainers = $('.cf7-work-rating').length;
            
            if (ratingContainers > 0) {
                if (window.cf7RatingsInstance) {
                    window.cf7RatingsInstance.initializeExistingRatings();
                }
                clearInterval(checkInterval);
            } else if (checkCount >= 20) { // Stop after 10 seconds
                clearInterval(checkInterval);
            }
        }, 500);
    });
    
    /**
     * Make CF7RatingsSystem available globally for other scripts
     */
    window.CF7RatingsSystem = CF7RatingsSystem;
    
})(jQuery);
