/**
 * CF7 Artist Submissions - Tabbed Interface Management System
 *
 * Advanced tab-based interface controller for artist submission management
 * with comprehensive state management, dynamic content loading, and seamless
 * integration coordination between multiple system components.
 *
 * Features:
 * • Multi-tab navigation with profile, works, actions, and conversations
 * • State persistence with localStorage and URL hash support
 * • Dynamic content loading with AJAX and component coordination
 * • Component re-initialization for profile fields and lightbox systems
 * • Auto-save functionality for profile data and curator notes
 * • Real-time status management with visual feedback
 * • Widget deep linking from dashboard components
 * • Comprehensive error handling and user feedback
 * • Keyboard navigation and accessibility support
 * • Cross-tab communication with custom events
 * • Memory management and performance optimization
 * • Security with nonce validation and access control
 *
 * @package CF7_Artist_Submissions
 * @subpackage TabInterface
 * @since 1.0.0
 * @version 1.0.0
 */

jQuery(document).ready(function($) {
    
    // ============================================================================
    // TAB SYSTEM INITIALIZATION
    // ============================================================================
    
    // Initialize tabs if they exist
    if ($('.cf7-tabs-nav').length) {
        initializeTabs();
    }
    
    /**
     * Manage complete lifecycle of tab-specific components during navigation.
     * Ensures all interactive elements maintain functionality when switching tabs
     * with event-driven component coordination.
     * 
     * @since 1.0.0
     */
    
    $(document).on('cf7_tab_changed', function(e, tabId) {
        // Re-initialize editable fields for the profile tab
        if (tabId === 'cf7-tab-profile' && typeof initEditableFields === 'function') {
            setTimeout(initEditableFields, 100);
        }
        
        // Re-initialize lightbox for the works tab
        if (tabId === 'cf7-tab-works' && typeof initLightbox === 'function') {
            setTimeout(initLightbox, 100);
        }
        
        // Special handling for Works tab with integrated file viewer
        if (tabId === 'cf7-tab-works' && $('#cf7-integrated-file-viewer').length > 0) {
            // Initialize PDF viewer for integrated file viewer
            setTimeout(function() {
                if (typeof window.initPDFViewer === 'function') {
                    window.initPDFViewer();
                }
                // Also initialize lightbox for the integrated viewer
                if (typeof window.initLightbox === 'function') {
                    window.initLightbox();
                }
            }, 100);
        }
        
        // Initialize actions manager for the actions tab
        if (tabId === 'cf7-tab-actions') {
            // Initialize actions manager for the actions tab
            setTimeout(function() {
                // Try CF7_Actions.init first
                if (typeof window.CF7_Actions !== 'undefined' && typeof window.CF7_Actions.init === 'function') {
                    window.CF7_Actions.init();
                } else if (jQuery('.cf7-actions-container').length > 0 && typeof ActionsManager !== 'undefined') {
                    // Fallback to direct initialization
                    if (!window.actionsManager) {
                        try {
                            window.actionsManager = new ActionsManager();
                        } catch (error) {
                            console.error('Failed to create ActionsManager:', error);
                        }
                    }
                }
            }, 200);
        }
        
        // Re-initialize conversation interface and scroll to bottom
        if (tabId === 'cf7-tab-conversations') {
            setTimeout(function() {
                // Debug: Log what we're looking for
                
                // Multiple attempts to find the conversation container
                const conversationSelectors = [
                    '#cf7-message-thread',
                    '.conversation-messages', 
                    '.cf7-conversation-container',
                    '#cf7-conversation-container'
                ];
                
                let conversationDiv = null;
                for (let selector of conversationSelectors) {
                    conversationDiv = $(selector);
                    if (conversationDiv.length) {
                        break;
                    }
                }
                
                if (conversationDiv && conversationDiv.length) {
                    const scrollHeight = conversationDiv[0].scrollHeight;
                    const clientHeight = conversationDiv.height();
                    
                    // Immediate scroll
                    conversationDiv.scrollTop(scrollHeight);
                    
                    // Follow up with animated scroll after a short delay
                    setTimeout(function() {
                        conversationDiv.animate({
                            scrollTop: scrollHeight
                        }, 300);
                    }, 100);
                    
                    // Final scroll attempt after longer delay for any dynamic content
                    setTimeout(function() {
                        conversationDiv.scrollTop(conversationDiv[0].scrollHeight);
                    }, 500);
                }
                
                // Re-initialize conversation scripts if available
                if (typeof initConversationInterface === 'function') {
                    initConversationInterface();
                }
                
                // Also try to trigger the scroll function from conversation.js
                if (typeof window.scrollToBottom === 'function') {
                    setTimeout(window.scrollToBottom, 300);
                    setTimeout(window.scrollToBottom, 600);
                }
            }, 200);
        }
    });
});

/**
 * Comprehensive tab navigation system handling click events, state persistence,
 * URL routing, and widget-based deep linking with multi-priority tab selection.
 * 
 * @since 1.0.0
 */
function initializeTabs() {
    const $ = jQuery;
    
    // Handle all tab click events with state management and visual updates
    $('.cf7-tab-link').on('click', function(e) {
        e.preventDefault();
        
        const tabId = $(this).data('tab');
        const $navItem = $(this).parent();
        const $tabsContainer = $(this).closest('.cf7-tabs-container');
        
        // Update active states
        $tabsContainer.find('.cf7-tab-nav-item').removeClass('active');
        $tabsContainer.find('.cf7-tab-content').removeClass('active');
        
        $navItem.addClass('active');
        $tabsContainer.find('#' + tabId).addClass('active');
        
        // Store active tab in localStorage
        localStorage.setItem('cf7_active_tab', tabId);
        
        // Load AJAX content for tabs that don't have static content
        // Profile tab has static content, others need AJAX loading
        if (tabId !== 'cf7-tab-profile') {
            const $tabContent = $('#' + tabId);
            const currentContent = $tabContent.html().trim();
            
            // Only load if tab is empty or contains just comments
            if (!currentContent || currentContent.includes('<!-- Content loaded via AJAX -->')) {
                loadTabContent(tabId);
            }
        }
        
        $(document).trigger('cf7_tab_changed', [tabId]);
    });
    
    // Multi-priority tab selection with URL hashes, widget routing, and saved state
    const savedTab = localStorage.getItem('cf7_active_tab');
    
    // Handle URL hash navigation first (highest priority)
    if (window.location.hash) {
        const hashTab = window.location.hash.substring(1);
        if ($('#' + hashTab).length) {
            $('.cf7-tab-link[data-tab="' + hashTab + '"]').trigger('click');
            return;
        }
    }
    
    // Check for URL parameters that indicate widget navigation
    const urlParams = new URLSearchParams(window.location.search);
    const fromWidget = urlParams.get('from');
    
    if (fromWidget === 'unread-messages') {
        // Navigate to conversations tab for unread messages
        const conversationsTab = $('.cf7-tab-link[data-tab="cf7-tab-conversations"]');
        if (conversationsTab.length) {
            conversationsTab.trigger('click');
            return;
        }
    } else if (fromWidget === 'outstanding-actions') {
        // Navigate to actions tab for outstanding actions
        const actionsTab = $('.cf7-tab-link[data-tab="cf7-tab-actions"]');
        if (actionsTab.length) {
            actionsTab.trigger('click');
            return;
        }
    }
    
    // Check for saved tab (but default to profile for new sessions from dashboard)
    if (savedTab && $('#' + savedTab).length && !fromWidget) {
        $('.cf7-tab-link[data-tab="' + savedTab + '"]').trigger('click');
    } else {
        // Default to profile tab
        const profileTab = $('.cf7-tab-link[data-tab="cf7-tab-profile"]');
        if (profileTab.length) {
            profileTab.trigger('click');
        } else {
            // Fallback to first tab if profile doesn't exist
            $('.cf7-tab-link').first().trigger('click');
        }
    }
}

/**
 * Dynamic content loading system for tab interfaces with comprehensive
 * error handling, loading states, and callback support for server-side rendering.
 * 
 * @since 1.0.0
 */
function loadTabContent(tabId, callback) {
    const $ = jQuery;
    
    if (!window.cf7TabsAjax) {
        console.warn('CF7 Tabs: AJAX configuration not found');
        return;
    }
    
    // Get post ID from localized data, fallback to DOM element
    const postId = window.cf7TabsAjax.post_id || $('#post_ID').val();
    if (!postId) {
        console.warn('CF7 Tabs: Post ID not found');
        return;
    }
    
    $.ajax({
        url: window.cf7TabsAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cf7_load_tab_content',
            tab_id: tabId,
            post_id: postId,
            nonce: window.cf7TabsAjax.nonce
        },
        beforeSend: function() {
            $('#' + tabId).html('<div class="cf7-tab-loading">Loading...</div>');
        },
        success: function(response) {
            if (response.success) {
                $('#' + tabId).html(response.data.content);
                if (callback) callback();
            } else {
                $('#' + tabId).html('<div class="cf7-tab-error">Error loading content: ' + response.data.message + '</div>');
            }
        },
        error: function() {
            $('#' + tabId).html('<div class="cf7-tab-error">Error loading content</div>');
        }
    });
}

// ============================================================================
// DATA PERSISTENCE AND STATUS MANAGEMENT
// ============================================================================

jQuery(document).ready(function($) {
    
    /**
     * Handle all save operations for profile fields and curator notes with
     * comprehensive validation, error handling, and user feedback.
     * 
     * @since 1.0.0
     */
    $(document).on('click', '.cf7-save-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const postId = $('#post_ID').val() || $('input[name="post_ID"]').val();
        
        if (!postId) {
            alert('Error: Post ID not found');
            return;
        }
        
        // Show loading state
        const originalText = $button.text();
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update cf7-spin"></span> Saving...');
        
        // Gather all profile field data
        const fieldData = {};
        $('.cf7-profile-field').each(function() {
            const $field = $(this);
            const key = $field.data('field') || $field.data('key');
            const $hiddenInput = $field.find('input[name^="cf7_editable_fields"]');
            
            if (key && $hiddenInput.length) {
                fieldData[key] = $hiddenInput.val();
            }
        });
        
        // Gather curator notes
        const curatorNotes = $('textarea[name="cf7_curator_notes"]').val() || '';
        
        // Make AJAX request to save
        $.ajax({
            url: cf7TabsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_save_submission_data',
                post_id: postId,
                field_data: fieldData,
                curator_notes: curatorNotes,
                nonce: cf7TabsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSaveFeedback('Changes saved successfully!', 'success');
                    
                    // Update the page title if artist name changed
                    if (fieldData['cf7_artist-name']) {
                        $('h1.cf7-artist-title').text(fieldData['cf7_artist-name']);
                        document.title = fieldData['cf7_artist-name'] + ' - Artist Submissions';
                    }
                } else {
                    showSaveFeedback('Error saving changes: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showSaveFeedback('Error saving changes. Please try again.', 'error');
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> ' + originalText);
            }
        });
    });
    
    /**
     * Comprehensive status update handler with visual feedback, validation,
     * and real-time UI updates for submission status management.
     * 
     * @since 1.0.0
     */
    $(document).on('click', '.cf7-status-option', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $option = $(this);
        const $dropdown = $option.closest('.cf7-status-dropdown');
        const $circle = $dropdown.find('.cf7-status-circle');
        const newStatus = $option.data('status');
        const postId = $dropdown.data('post-id');
        
        // Don't do anything if it's already the current status
        if ($option.hasClass('active')) {
            return;
        }
        
        // Show loading state
        $circle.prop('disabled', true);
        $circle.find('.dashicons').addClass('cf7-spin');
        
        // Make AJAX request
        $.ajax({
            url: cf7TabsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cf7_update_status',
                post_id: postId,
                status: newStatus,
                nonce: cf7TabsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the circle appearance
                    $circle.css('background-color', response.data.data.color);
                    $circle.attr('title', response.data.data.label);
                    $circle.find('.dashicons')
                        .removeClass()
                        .addClass('dashicons dashicons-' + response.data.data.icon);
                    
                    // Update active status in dropdown
                    $dropdown.find('.cf7-status-option').removeClass('active');
                    $option.addClass('active');
                    
                    // Show success feedback
                    showStatusUpdateFeedback('Status updated to ' + response.data.data.label, 'success');
                } else {
                    showStatusUpdateFeedback('Failed to update status: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showStatusUpdateFeedback('Error updating status', 'error');
            },
            complete: function() {
                // Remove loading state
                $circle.prop('disabled', false);
                $circle.find('.dashicons').removeClass('cf7-spin');
            }
        });
    });
    
    // Handle dropdown visibility and interaction states
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.cf7-status-dropdown').length) {
            $('.cf7-status-menu').hide();
        }
    });
    
    // Show dropdown on circle click
    $(document).on('click', '.cf7-status-circle', function(e) {
        e.stopPropagation();
        const $menu = $(this).siblings('.cf7-status-menu');
        $('.cf7-status-menu').not($menu).hide();
        $menu.toggle();
    });
});

// ============================================================================
// USER FEEDBACK SYSTEM
// ============================================================================

/**
 * Status update feedback with auto-cleanup and accessibility features.
 */
function showStatusUpdateFeedback(message, type) {
    // Remove existing feedback
    jQuery('.cf7-status-feedback').remove();
    
    // Create feedback element
    const $feedback = jQuery('<div class="cf7-status-feedback cf7-feedback-' + type + '">' + message + '</div>');
    
    // Add to page
    jQuery('.cf7-custom-header').after($feedback);
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        $feedback.fadeOut(function() {
            jQuery(this).remove();
        });
    }, 3000);
}

/**
 * Save operation feedback with extended display duration for user confirmation.
 */
function showSaveFeedback(message, type) {
    // Remove existing feedback
    jQuery('.cf7-save-feedback').remove();
    
    // Create feedback element
    const $feedback = jQuery('<div class="cf7-save-feedback cf7-feedback-' + type + '">' + message + '</div>');
    
    // Add to page
    jQuery('.cf7-custom-header').after($feedback);
    
    // Auto-remove after 4 seconds
    setTimeout(function() {
        $feedback.fadeOut(function() {
            jQuery(this).remove();
        });
    }, 4000);
}
