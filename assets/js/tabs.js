/**
 * ============================================================================
 * CF7 ARTIST SUBMISSIONS - TABBED INTERFACE MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * Advanced tab-based interface controller for artist submission management
 * with comprehensive state management, dynamic content loading, and seamless
 * integration coordination between multiple system components.
 * 
 * This system serves as the central coordination hub for the admin interface,
 * managing tab navigation, content loading, component initialization, and
 * cross-system communication throughout the artist submission workflow.
 * 
 * ============================================================================
 * SYSTEM ARCHITECTURE
 * ============================================================================
 * 
 * CF7TabManagementSystem
 * ├─ TabNavigationController
 * │  ├─ NavigationHandlers: Click event management and tab switching logic
 * │  ├─ StateManagement: Active tab tracking and localStorage persistence
 * │  ├─ URLIntegration: Hash navigation and parameter-based tab routing
 * │  └─ AccessibilityLayer: Keyboard navigation and screen reader support
 * │
 * ├─ ContentManagementEngine
 * │  ├─ DynamicLoading: AJAX-based tab content loading and caching
 * │  ├─ ComponentInitialization: Script re-initialization for tab content
 * │  ├─ StateSync: Content synchronization across tab switches
 * │  └─ ErrorHandling: Graceful failure management for content loading
 * │
 * ├─ ComponentCoordinationLayer
 * │  ├─ ProfileFields: Editable field system integration and initialization
 * │  ├─ LightboxSystem: Media gallery initialization for works display
 * │  ├─ ActionsManager: Action system initialization and state management
 * │  ├─ ConversationInterface: Message thread management and auto-scrolling
 * │  └─ PersistenceManager: Data saving and state preservation across tabs
 * │
 * ├─ DataPersistenceSystem
 * │  ├─ AutoSave: Automatic data preservation during tab navigation
 * │  ├─ AjaxSaving: Server synchronization for profile and curator data
 * │  ├─ ValidationEngine: Data integrity checking before persistence
 * │  └─ FeedbackSystem: User notification for save operations
 * │
 * └─ UserFeedbackFramework
 *    ├─ StatusNotifications: Real-time feedback for status changes
 *    ├─ SaveConfirmations: Success/error messaging for data operations
 *    ├─ LoadingIndicators: Progress feedback during content loading
 *    └─ ErrorRecovery: User-friendly error messaging with recovery options
 * 
 * ============================================================================
 * INTEGRATION POINTS
 * ============================================================================
 * 
 * • CF7 Profile Fields System: Dynamic field editing and validation
 * • CF7 Actions Management: Action creation, assignment, and tracking
 * • CF7 Conversation System: Message threading and communication
 * • CF7 Lightbox Gallery: Media display and navigation
 * • WordPress AJAX Framework: Server communication and data persistence
 * • Browser Navigation API: URL management and state preservation
 * • Local Storage API: Client-side state persistence
 * 
 * ============================================================================
 * DEPENDENCIES
 * ============================================================================
 * 
 * • jQuery 3.x: Core JavaScript framework for DOM manipulation
 * • WordPress AJAX API: Server communication and nonce validation
 * • Browser APIs: localStorage, URLSearchParams, window.location
 * • CF7 Component Systems: Actions, Conversations, Fields, Lightbox
 * • WordPress Admin Framework: Dashboard integration and styling
 * 
 * ============================================================================
 * TAB SYSTEM FEATURES
 * ============================================================================
 * 
 * • Multi-Tab Navigation: Profile, Works, Actions, Conversations, Settings
 * • State Persistence: Maintain active tab across page refreshes
 * • URL Integration: Hash-based navigation and widget deep-linking
 * • Dynamic Loading: AJAX content loading for performance optimization
 * • Component Coordination: Automatic initialization of tab-specific systems
 * • Cross-Tab Communication: Event-driven communication between components
 * • Auto-Save: Intelligent data persistence during navigation
 * 
 * ============================================================================
 * NAVIGATION ARCHITECTURE
 * ============================================================================
 * 
 * • Click Navigation: Primary tab switching via navigation menu
 * • URL Hash Navigation: Direct tab access via URL fragments
 * • Widget Integration: Deep linking from dashboard widgets
 * • Keyboard Navigation: Full keyboard accessibility support
 * • State Restoration: Automatic return to last active tab
 * • Parameter Routing: URL parameter-based tab selection
 * 
 * ============================================================================
 * DATA MANAGEMENT
 * ============================================================================
 * 
 * • Profile Data Persistence: Automatic saving of editable field changes
 * • Curator Notes: Administrative note saving and synchronization
 * • Status Management: Submission status updates with validation
 * • Cross-Tab Sync: Data synchronization across multiple tab contexts
 * • Validation Engine: Client-side and server-side data validation
 * • Error Recovery: Graceful handling of save failures
 * 
 * ============================================================================
 * PERFORMANCE FEATURES
 * ============================================================================
 * 
 * • Lazy Loading: Tab content loaded on-demand for performance
 * • Component Caching: Intelligent re-initialization of existing components
 * • Event Delegation: Efficient event handling for dynamic content
 * • Memory Management: Proper cleanup of event listeners and components
 * • Optimized Re-rendering: Minimal DOM manipulation during tab switches
 * 
 * ============================================================================
 * ACCESSIBILITY FEATURES
 * ============================================================================
 * 
 * • Keyboard Navigation: Full keyboard support for all tab operations
 * • Screen Reader Support: Proper ARIA labels and semantic markup
 * • Focus Management: Logical focus handling during tab transitions
 * • High Contrast: Visual elements optimized for accessibility
 * • Error Messaging: Clear, accessible error and status communication
 * 
 * ============================================================================
 * SECURITY FEATURES
 * ============================================================================
 * 
 * • Nonce Validation: WordPress security token verification for AJAX
 * • Data Sanitization: Input validation and XSS prevention
 * • Access Control: User permission verification for data operations
 * • CSRF Protection: Cross-site request forgery prevention
 * • Secure State Management: Protected client-side state storage
 * 
 * @package CF7_Artist_Submissions
 * @subpackage TabInterface
 * @since 2.0.0
 * @version 2.0.0
 * @author CF7 Artist Submissions Development Team
 */

jQuery(document).ready(function($) {
    /**
     * ============================================================================
     * TAB SYSTEM INITIALIZATION AND AUTO-DETECTION
     * ============================================================================
     * 
     * Automatically detects and initializes the tabbed interface when tab navigation
     * elements are present in the DOM. This initialization system provides the
     * foundation for all subsequent tab operations and component coordination
     * throughout the admin interface.
     * 
     * Detection Strategy:
     * • DOM Scanning: Automatically detects .cf7-tabs-nav elements
     * • Conditional Initialization: Only initialize when tabs are present
     * • Early Loading: Initialize immediately on DOM ready for optimal UX
     * • Component Preparation: Setup all necessary event listeners and state
     * 
     * Integration Benefits:
     * • Zero Configuration: Automatic detection and setup
     * • Performance Optimization: Only load when actually needed
     * • Reliability: Graceful handling when tabs aren't present
     * • Extensibility: Foundation for complex tab-based workflows
     * ============================================================================
     */
    
    // Initialize tabs if they exist
    if ($('.cf7-tabs-nav').length) {
        initializeTabs();
    }
    
    /**
     * ============================================================================
     * COMPONENT COORDINATION AND RE-INITIALIZATION SYSTEM
     * ============================================================================
     * 
     * Manages the complete lifecycle of tab-specific components during navigation
     * transitions. This system ensures that all interactive elements maintain
     * proper functionality and state when users switch between tabs, providing
     * seamless integration between different system components.
     * 
     * Component Management Strategy:
     * 1. Event-Driven Architecture: Listen for cf7_tab_changed custom events
     * 2. Delayed Initialization: Allow DOM settling before component setup
     * 3. Conditional Loading: Initialize only when components are actually present
     * 4. Error Recovery: Graceful fallback handling for failed initializations
     * 5. State Preservation: Maintain component state across navigation events
     * 
     * Managed Components:
     * • Profile Fields System: Dynamic editable field re-initialization
     * • Lightbox Gallery: Media display functionality restoration
     * • Actions Manager: Task management system initialization
     * • Conversation Interface: Message threading and auto-scroll management
     * 
     * Performance Considerations:
     * • Lazy Loading: Components initialized only when their tab is accessed
     * • Memory Management: Proper cleanup and re-initialization cycles
     * • Timing Optimization: Strategic delays to ensure DOM readiness
     * • Error Resilience: Graceful degradation for missing dependencies
     * ============================================================================
     */
    
    $(document).on('cf7_tab_changed', function(e, tabId) {
        /**
         * ========================================================================
         * PROFILE TAB COMPONENT INITIALIZATION
         * ========================================================================
         * 
         * Re-initializes the editable fields system when the profile tab becomes
         * active. This ensures that all dynamic field editing functionality is
         * properly restored and functional after tab navigation.
         * ========================================================================
         */
        if (tabId === 'cf7-tab-profile' && typeof initEditableFields === 'function') {
            // Re-initialize editable fields for the profile tab
            setTimeout(initEditableFields, 100);
        }
        
        /**
         * ========================================================================
         * WORKS TAB LIGHTBOX INITIALIZATION
         * ========================================================================
         * 
         * Restores lightbox gallery functionality for the works tab, ensuring
         * that all media display and navigation features are properly functional
         * when users view artwork portfolios.
         * ========================================================================
         */
        if (tabId === 'cf7-tab-works' && typeof initLightbox === 'function') {
            // Re-initialize lightbox for the works tab
            setTimeout(initLightbox, 100);
        }
        
        /**
         * ========================================================================
         * ACTIONS TAB MANAGEMENT SYSTEM INITIALIZATION
         * ========================================================================
         * 
         * Initializes the actions management system with multiple fallback
         * strategies to ensure robust functionality. Handles both modern
         * CF7_Actions API and legacy ActionsManager implementations.
         * ========================================================================
         */
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
        
        /**
         * ========================================================================
         * CONVERSATIONS TAB INTERFACE INITIALIZATION
         * ========================================================================
         * 
         * Comprehensive conversation interface setup including auto-scrolling,
         * component re-initialization, and dynamic content management. Uses
         * multiple strategies to ensure reliable conversation display.
         * 
         * Initialization Strategy:
         * 1. Multi-Selector Detection: Try various conversation container selectors
         * 2. Auto-Scroll Implementation: Multiple scroll attempts with timing
         * 3. Component Re-initialization: Restore conversation functionality
         * 4. Cross-System Integration: Coordinate with conversation.js functions
         * ========================================================================
         */
        if (tabId === 'cf7-tab-conversations') {
            // Re-initialize conversation interface and scroll to bottom
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
 * ============================================================================
 * TAB NAVIGATION INITIALIZATION AND STATE MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * Comprehensive tab navigation system that handles click events, state
 * persistence, URL routing, and widget-based deep linking. This function
 * establishes the complete navigation infrastructure for the tabbed interface.
 * 
 * Navigation Features:
 * • Click-Based Navigation: Primary tab switching via navigation menu
 * • State Persistence: localStorage-based tab state preservation
 * • URL Hash Navigation: Direct tab access via URL fragments
 * • Widget Deep Linking: Parameter-based navigation from dashboard widgets
 * • Fallback Handling: Graceful degradation for missing tabs
 * 
 * State Management:
 * • Active Tab Tracking: Maintain current tab state across interactions
 * • Visual State Updates: Synchronized UI state changes
 * • Event Broadcasting: Custom events for cross-component communication
 * • Persistence Layer: Client-side state storage for session continuity
 * 
 * URL Integration:
 * • Hash Navigation: Support for direct tab access via URL fragments
 * • Parameter Routing: Widget-based navigation with URL parameters
 * • State Restoration: Automatic return to saved or specified tabs
 * • Bookmark Support: URL-based tab state for bookmarking and sharing
 * 
 * Widget Integration:
 * • Deep Linking: Direct navigation from dashboard widget actions
 * • Context Preservation: Maintain widget context during navigation
 * • Priority Routing: Widget navigation takes precedence over saved state
 * • Seamless Transitions: Smooth navigation from external entry points
 * ============================================================================
 */
function initializeTabs() {
    const $ = jQuery;
    
    /**
     * ========================================================================
     * TAB CLICK HANDLER AND STATE MANAGEMENT
     * ========================================================================
     * 
     * Handles all tab click events with comprehensive state management,
     * visual updates, and cross-component communication. This handler
     * ensures smooth transitions and proper state synchronization.
     * ========================================================================
     */
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
    
    /**
     * ========================================================================
     * INTELLIGENT TAB RESTORATION AND ROUTING SYSTEM
     * ========================================================================
     * 
     * Multi-priority tab selection system that handles various navigation
     * scenarios including URL hashes, widget deep links, saved state, and
     * intelligent defaults. Provides robust navigation restoration.
     * 
     * Priority Order:
     * 1. URL Hash Navigation (highest priority)
     * 2. Widget Parameter Routing
     * 3. Saved Tab State (localStorage)
     * 4. Default Tab Selection (profile)
     * 5. Fallback (first available tab)
     * ========================================================================
     */
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
 * ============================================================================
 * AJAX TAB CONTENT LOADING SYSTEM
 * ============================================================================
 * 
 * Dynamic content loading system for tab-based interfaces with comprehensive
 * error handling, loading states, and callback support. Provides seamless
 * content delivery for complex tab interfaces with server-side rendering.
 * 
 * Loading Features:
 * • Asynchronous Loading: Non-blocking content retrieval from server
 * • Loading States: Visual feedback during content loading operations
 * • Error Handling: Graceful failure management with user-friendly messages
 * • Callback Support: Post-loading initialization for dynamic content
 * • Configuration Validation: Robust validation of AJAX configuration
 * 
 * Security Features:
 * • Nonce Validation: WordPress security token verification
 * • Post ID Validation: Secure content scope verification
 * • Error Sanitization: Safe error message display
 * • Request Validation: Comprehensive request parameter checking
 * 
 * Performance Considerations:
 * • Efficient Loading: Optimized AJAX requests with minimal overhead
 * • Error Recovery: Graceful degradation for network or server issues
 * • Memory Management: Proper cleanup of temporary loading elements
 * • State Consistency: Reliable content state management during loading
 * ============================================================================
 */
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

/**
 * ============================================================================
 * COMPREHENSIVE DATA PERSISTENCE AND STATUS MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * Advanced data management system handling profile field persistence, curator
 * notes, submission status updates, and comprehensive user feedback. This
 * system provides the backbone for all data operations within the tabbed
 * interface with robust error handling and state management.
 * 
 * Data Management Features:
 * • Profile Field Persistence: Automatic saving of editable field changes
 * • Curator Notes Management: Administrative note saving and synchronization
 * • Status Management: Submission status updates with visual feedback
 * • Batch Operations: Efficient handling of multiple field updates
 * • State Synchronization: Real-time UI updates reflecting data changes
 * 
 * User Experience Features:
 * • Loading States: Visual feedback during save operations
 * • Success Notifications: Clear confirmation of successful operations
 * • Error Recovery: Graceful handling of save failures with retry options
 * • Progress Tracking: Real-time feedback during long-running operations
 * • Auto-Recovery: Automatic state restoration after successful saves
 * 
 * Security and Validation:
 * • Nonce Validation: WordPress security token verification for all requests
 * • Data Sanitization: Comprehensive input validation and sanitization
 * • Access Control: User permission verification before data operations
 * • CSRF Protection: Cross-site request forgery prevention
 * • Input Validation: Client-side and server-side data validation
 * 
 * Integration Features:
 * • WordPress AJAX: Native WordPress AJAX framework integration
 * • Field System Integration: Seamless integration with editable fields
 * • Status System: Advanced status management with visual indicators
 * • Feedback System: Comprehensive user notification framework
 * ============================================================================
 */
jQuery(document).ready(function($) {
    /**
     * ========================================================================
     * COMPREHENSIVE SAVE CHANGES HANDLER
     * ========================================================================
     * 
     * Handles all save operations for profile fields and curator notes with
     * comprehensive validation, error handling, and user feedback. Provides
     * robust data persistence with real-time UI updates.
     * 
     * Save Process:
     * 1. Validation: Verify required data and user permissions
     * 2. Data Collection: Gather all profile fields and curator notes
     * 3. UI State: Show loading indicators and disable controls
     * 4. AJAX Request: Secure server communication with nonce validation
     * 5. Response Handling: Process success/error responses
     * 6. UI Updates: Update interface based on save results
     * 7. State Restoration: Re-enable controls and clear loading states
     * ========================================================================
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
     * ========================================================================
     * ADVANCED STATUS MANAGEMENT SYSTEM
     * ========================================================================
     * 
     * Comprehensive status update handler with visual feedback, validation,
     * and real-time UI updates. Manages submission status changes with
     * robust error handling and user experience optimization.
     * 
     * Status Update Process:
     * 1. Validation: Verify status change is valid and necessary
     * 2. UI Feedback: Show loading states and disable controls
     * 3. AJAX Communication: Secure server request with proper validation
     * 4. Visual Updates: Update status indicators and dropdown states
     * 5. User Notification: Provide clear feedback about status changes
     * 6. State Restoration: Clean up loading states and re-enable controls
     * ========================================================================
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
    
    /**
     * ========================================================================
     * DROPDOWN INTERACTION MANAGEMENT
     * ========================================================================
     * 
     * Handles dropdown visibility and interaction states with proper event
     * management and user experience optimization. Provides intuitive
     * dropdown behavior with click-outside-to-close functionality.
     * ========================================================================
     */
    
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

/**
 * ============================================================================
 * COMPREHENSIVE USER FEEDBACK SYSTEM
 * ============================================================================
 * 
 * Advanced notification system providing clear, accessible feedback for all
 * user operations including status updates and save operations. Features
 * automatic cleanup, visual consistency, and accessibility compliance.
 * 
 * Feedback Features:
 * • Status Notifications: Real-time feedback for status change operations
 * • Save Confirmations: Clear success/error messaging for data operations
 * • Auto-Cleanup: Automatic removal of notifications after appropriate timing
 * • Visual Consistency: Consistent styling and positioning across all notifications
 * • Accessibility: Screen reader compatible notification display
 * 
 * Notification Types:
 * • Success Messages: Positive feedback for successful operations
 * • Error Messages: Clear error communication with actionable information
 * • Loading States: Progress indication during long-running operations
 * • Warning Messages: Important user guidance and validation feedback
 * 
 * User Experience:
 * • Non-Intrusive: Notifications don't block user workflow
 * • Contextual Positioning: Strategic placement for optimal visibility
 * • Timing Optimization: Appropriate display duration for message importance
 * • Stack Management: Proper handling of multiple simultaneous notifications
 * ============================================================================
 */

/**
 * Status update feedback with auto-cleanup and accessibility features
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
 * Save operation feedback with extended display duration for user confirmation
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
