/**
 * CF7 Artist Submissions - Enhanced Multi-Curator Rating System JavaScript
 *
 * Handles the interactive functionality for the enhanced multi-curator rating system.
 * Provides AJAX-powered rating submission, real-time average updates, and user feedback.
 *
 * Features:
 * • Interactive 5-star rating interface
 * • Real-time average rating updates
 * • Individual curator rating management
 * • Visual feedback and animations
 * • Loading states and error handling
 * • Responsive rating interactions
 *
 * @package CF7_Artist_Submissions
 * @subpackage EnhancedRatingsJS
 * @since 1.3.0
 * @version 1.3.0
 */

(function($) {
    'use strict';
    
    // Enhanced Rating System Handler
    var CF7EnhancedRatingsHandler = {
        
        // Configuration
        config: {
            submissionId: null,
            nonce: null,
            ajaxUrl: null,
            strings: {},
            currentUserRating: 0,
            selectedRating: 0
        },
        
        // Initialize the handler
        init: function() {
            this.config.ajaxUrl = cf7EnhancedRatings.ajaxurl;
            this.config.nonce = cf7EnhancedRatings.nonce;
            this.config.strings = cf7EnhancedRatings.strings;
            
            // Get submission ID from the rating container
            var ratingContainer = $('.cf7-enhanced-rating-system');
            if (ratingContainer.length) {
                this.config.submissionId = ratingContainer.data('submission-id');
            }
            
            // Get current user rating
            var userRatingStars = $('.cf7-rating-stars');
            if (userRatingStars.length) {
                this.config.currentUserRating = parseInt(userRatingStars.data('current-rating')) || 0;
                this.config.selectedRating = this.config.currentUserRating;
            }
            
            this.bindEvents();
            this.updateSaveButton();
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Rating star hover events
            $(document).on('mouseenter', '.cf7-rating-star', function() {
                var rating = parseInt($(this).data('rating'));
                self.highlightStars(rating);
            });
            
            $(document).on('mouseleave', '.cf7-rating-stars', function() {
                self.highlightStars(self.config.selectedRating);
            });
            
            // Rating star click events
            $(document).on('click', '.cf7-rating-star', function() {
                var rating = parseInt($(this).data('rating'));
                self.selectRating(rating);
            });
            
            // Save rating button
            $(document).on('click', '#cf7-save-rating-btn', function(e) {
                e.preventDefault();
                self.saveRating();
            });
            
            // Clear rating button
            $(document).on('click', '#cf7-clear-rating-btn', function(e) {
                e.preventDefault();
                self.clearRating();
            });
            
            // Keyboard navigation
            $(document).on('keydown', '.cf7-rating-star', function(e) {
                var currentRating = parseInt($(this).data('rating'));
                var newRating = currentRating;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        newRating = Math.max(1, currentRating - 1);
                        break;
                    case 'ArrowRight':
                        newRating = Math.min(5, currentRating + 1);
                        break;
                    case 'Enter':
                    case ' ':
                        self.selectRating(currentRating);
                        return;
                }
                
                if (newRating !== currentRating) {
                    e.preventDefault();
                    $('.cf7-rating-star[data-rating="' + newRating + '"]').focus();
                    self.highlightStars(newRating);
                }
            });
        },
        
        // ============================================================================
        // RATING OPERATIONS
        // ============================================================================
        
        // Select a rating
        selectRating: function(rating) {
            this.config.selectedRating = rating;
            this.highlightStars(rating);
            this.updateSaveButton();
        },
        
        // Highlight stars up to the given rating
        highlightStars: function(rating) {
            $('.cf7-rating-star').each(function(index) {
                var starRating = index + 1;
                var star = $(this).find('.cf7-star');
                
                if (starRating <= rating) {
                    star.removeClass('cf7-star-empty').addClass('cf7-star-filled');
                } else {
                    star.removeClass('cf7-star-filled').addClass('cf7-star-empty');
                }
            });
        },
        
        // Update save button state
        updateSaveButton: function() {
            var saveBtn = $('#cf7-save-rating-btn');
            var hasChanged = this.config.selectedRating !== this.config.currentUserRating;
            var hasRating = this.config.selectedRating > 0;
            
            saveBtn.prop('disabled', !hasChanged || !hasRating);
            
            if (hasChanged && hasRating) {
                saveBtn.removeClass('button').addClass('button-primary');
            } else {
                saveBtn.removeClass('button-primary').addClass('button');
            }
        },
        
        // Save the rating
        saveRating: function() {
            var self = this;
            var saveBtn = $('#cf7-save-rating-btn');
            var statusEl = $('.cf7-rating-status');
            
            // Validate rating
            if (self.config.selectedRating < 1 || self.config.selectedRating > 5) {
                self.showStatus(statusEl, self.config.strings.select_rating, 'error');
                return;
            }
            
            // Show loading state
            saveBtn.prop('disabled', true);
            saveBtn.html('<span class="dashicons dashicons-update spin"></span> ' + self.config.strings.saving);
            self.showStatus(statusEl, self.config.strings.saving, 'loading');
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_save_curator_rating',
                    submission_id: self.config.submissionId,
                    rating: self.config.selectedRating,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.config.currentUserRating = self.config.selectedRating;
                        self.showStatus(statusEl, self.config.strings.rating_saved, 'success');
                        
                        // Update average rating display
                        self.updateAverageDisplay(response.data);
                        
                        // Add clear button if it doesn't exist
                        if (!$('#cf7-clear-rating-btn').length && response.data.rating_id) {
                            var clearBtn = '<button type="button" id="cf7-clear-rating-btn" class="button" data-rating-id="' + response.data.rating_id + '">' +
                                '<span class="dashicons dashicons-dismiss"></span> Clear Rating</button>';
                            saveBtn.after(clearBtn);
                        }
                        
                        // Refresh ratings list
                        self.refreshRatingsList();
                    } else {
                        self.showStatus(statusEl, response.data || self.config.strings.error_general, 'error');
                    }
                },
                error: function() {
                    self.showStatus(statusEl, self.config.strings.error_general, 'error');
                },
                complete: function() {
                    // Reset button state
                    saveBtn.html('<span class="dashicons dashicons-star-filled"></span> Save Rating');
                    self.updateSaveButton();
                }
            });
        },
        
        // Clear the rating
        clearRating: function() {
            var self = this;
            
            // Confirm clearing
            if (!confirm(self.config.strings.confirm_clear)) {
                return;
            }
            
            var clearBtn = $('#cf7-clear-rating-btn');
            var statusEl = $('.cf7-rating-status');
            var ratingId = clearBtn.data('rating-id');
            
            // Show loading state
            clearBtn.prop('disabled', true);
            clearBtn.html('<span class="dashicons dashicons-update spin"></span> Clearing...');
            self.showStatus(statusEl, 'Clearing rating...', 'loading');
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_delete_curator_rating',
                    rating_id: ratingId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.config.currentUserRating = 0;
                        self.config.selectedRating = 0;
                        self.highlightStars(0);
                        self.showStatus(statusEl, self.config.strings.rating_cleared, 'success');
                        
                        // Remove clear button
                        clearBtn.remove();
                        
                        // Refresh displays
                        self.refreshAverageDisplay();
                        self.refreshRatingsList();
                        self.updateSaveButton();
                    } else {
                        self.showStatus(statusEl, response.data || self.config.strings.error_general, 'error');
                    }
                },
                error: function() {
                    self.showStatus(statusEl, self.config.strings.error_general, 'error');
                },
                complete: function() {
                    if (clearBtn.length) {
                        clearBtn.prop('disabled', false);
                        clearBtn.html('<span class="dashicons dashicons-dismiss"></span> Clear Rating');
                    }
                }
            });
        },
        
        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        // Update average rating display
        updateAverageDisplay: function(data) {
            var averageDisplay = $('.cf7-average-rating-display');
            
            // Update stars
            averageDisplay.find('.cf7-star').each(function(index) {
                var starNum = index + 1;
                if (starNum <= data.stars) {
                    $(this).removeClass('cf7-star-empty').addClass('cf7-star-filled');
                } else {
                    $(this).removeClass('cf7-star-filled').addClass('cf7-star-empty');
                }
            });
            
            // Update numbers
            averageDisplay.find('.cf7-average-number').text(data.average_rating);
            averageDisplay.find('.cf7-rating-count').text('(' + data.rating_count + ' ' + 
                (data.rating_count === 1 ? 'rating' : 'ratings') + ')');
        },
        
        // Refresh average rating display
        refreshAverageDisplay: function() {
            var self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_curator_ratings',
                    submission_id: self.config.submissionId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateAverageDisplay(response.data);
                    }
                }
            });
        },
        
        // Refresh the ratings list
        refreshRatingsList: function() {
            var self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_curator_ratings',
                    submission_id: self.config.submissionId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data.ratings) {
                        self.renderRatingsList(response.data.ratings);
                        self.updateAverageDisplay(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to refresh ratings list');
                }
            });
        },
        
        // Render the ratings list
        renderRatingsList: function(ratings) {
            var ratingsList = $('#cf7-ratings-list');
            var allRatingsSection = $('.cf7-all-ratings-section');
            
            if (ratings.length === 0) {
                allRatingsSection.hide();
                return;
            }
            
            allRatingsSection.show();
            
            var html = '';
            for (var i = 0; i < ratings.length; i++) {
                var rating = ratings[i];
                html += this.renderRatingItem(rating);
            }
            
            ratingsList.html(html);
        },
        
        // Render a single rating item
        renderRatingItem: function(rating) {
            var guestIndicator = rating.is_guest_curator ? 
                '<span class="cf7-curator-type">(Guest Curator)</span>' : '';
            
            var stars = '';
            for (var i = 1; i <= 5; i++) {
                stars += '<span class="cf7-star ' + (i <= rating.rating ? 'cf7-star-filled' : 'cf7-star-empty') + '">★</span>';
            }
            
            return '<div class="cf7-rating-item" data-rating-id="' + rating.id + '">' +
                '<div class="cf7-rating-curator">' +
                    '<span class="cf7-curator-name">' + this.escapeHtml(rating.curator_name) + '</span>' +
                    guestIndicator +
                '</div>' +
                '<div class="cf7-rating-value">' +
                    '<div class="cf7-rating-stars-display">' + stars + '</div>' +
                    '<span class="cf7-rating-number">' + rating.rating + '/5</span>' +
                '</div>' +
                '<div class="cf7-rating-meta">' +
                    '<span class="cf7-rating-date" title="' + this.escapeHtml(rating.formatted_date) + '">' +
                        this.escapeHtml(rating.relative_time) +
                    '</span>' +
                '</div>' +
            '</div>';
        },
        
        // Show status message
        showStatus: function(element, message, type) {
            element.removeClass('cf7-status-success cf7-status-error cf7-status-loading');
            element.addClass('cf7-status-' + type);
            element.text(message).show();
            
            if (type === 'success' || type === 'error') {
                setTimeout(function() {
                    element.fadeOut();
                }, 3000);
            }
        },
        
        // Escape HTML
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof cf7EnhancedRatings !== 'undefined') {
            CF7EnhancedRatingsHandler.init();
        }
    });
    
})(jQuery);
