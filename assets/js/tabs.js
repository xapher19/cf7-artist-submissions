/**
 * CF7 Artist Submissions - Tab Management
 */

jQuery(document).ready(function($) {
    // Initialize tabs if they exist
    if ($('.cf7-tabs-nav').length) {
        initializeTabs();
    }
    
    // Re-initialize when tabs content changes
    $(document).on('cf7_tab_changed', function(e, tabId) {
        // Re-initialize any scripts that might be needed in the new tab content
        if (tabId === 'cf7-tab-profile' && typeof initEditableFields === 'function') {
            // Re-initialize editable fields for the profile tab
            setTimeout(initEditableFields, 100);
        }
        
        if (tabId === 'cf7-tab-works' && typeof initLightbox === 'function') {
            // Re-initialize lightbox for the works tab
            setTimeout(initLightbox, 100);
        }
        
        if (tabId === 'cf7-tab-conversations') {
            // Re-initialize conversation interface and scroll to bottom
            setTimeout(function() {
                // Debug: Log what we're looking for
                console.log('CF7 Tabs: Attempting to scroll conversations tab');
                
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
                        console.log('CF7 Tabs: Found conversation container with selector:', selector);
                        break;
                    }
                }
                
                if (conversationDiv && conversationDiv.length) {
                    const scrollHeight = conversationDiv[0].scrollHeight;
                    const clientHeight = conversationDiv.height();
                    console.log('CF7 Tabs: Scroll info - scrollHeight:', scrollHeight, 'clientHeight:', clientHeight);
                    
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
                        console.log('CF7 Tabs: Final scroll attempt completed');
                    }, 500);
                } else {
                    console.log('CF7 Tabs: No conversation container found');
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

function initializeTabs() {
    const $ = jQuery;
    
    // Handle tab clicks
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
        
        // Trigger custom event for other scripts
        $(document).trigger('cf7_tab_changed', [tabId]);
    });
    
    // Restore last active tab or default to profile tab
    const savedTab = localStorage.getItem('cf7_active_tab');
    
    // Handle URL hash navigation first (highest priority)
    if (window.location.hash) {
        const hashTab = window.location.hash.substring(1);
        if ($('#' + hashTab).length) {
            $('.cf7-tab-link[data-tab="' + hashTab + '"]').trigger('click');
            return;
        }
    }
    
    // Check for saved tab (but default to profile for new sessions)
    if (savedTab && $('#' + savedTab).length) {
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

// AJAX loading for tab content
function loadTabContent(tabId, callback) {
    const $ = jQuery;
    
    if (!window.cf7TabsAjax) {
        console.warn('CF7 Tabs: AJAX configuration not found');
        return;
    }
    
    const postId = $('#post_ID').val();
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
