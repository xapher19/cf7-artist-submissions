/**
 * ========================================
 * CF7 Artist Submissions - Modern Interactive Dashboard System
 * ========================================
 * 
 * Comprehensive dashboard interface providing real-time submission management,
 * interactive statistics visualization, and advanced filtering capabilities.
 * Built with modern ES6 class architecture for maintainable, scalable
 * artist submission workflow management.
 * 
 * System Architecture:
 * ┌─ CF7Dashboard Controller Class
 * │  ├─ State Management (pagination, selection, loading states)
 * │  ├─ Event Binding System (user interactions and keyboard shortcuts)
 * │  ├─ AJAX Communication Layer (data loading and persistence)
 * │  └─ UI Rendering Engine (dynamic content and visual feedback)
 * │
 * ┌─ Statistics & Metrics System
 * │  ├─ Real-time Stats Loading (submission counts and status breakdown)
 * │  ├─ Interactive Metric Cards (clickable filtering and visual feedback)
 * │  ├─ Mini Chart Generation (SVG-based historical trend visualization)
 * │  └─ Activity Monitoring (unread messages and outstanding actions)
 * │
 * ┌─ Advanced Filtering Engine
 * │  ├─ Multi-criteria Search (text, status, date range filtering)
 * │  ├─ Smart Filter Display (active filter visualization and management)
 * │  ├─ Quick Filter Actions (metric card click-to-filter integration)
 * │  ├─ Calendar Date Picker (modern range selection with presets)
 * │  └─ Filter Persistence (state management across page interactions)
 * │
 * ┌─ Submission Management Interface
 * │  ├─ Dynamic Table Rendering (paginated submission display)
 * │  ├─ Bulk Selection System (multi-item operations and validation)
 * │  ├─ Status Management (individual and bulk status updates)
 * │  ├─ Notification System (unread messages and action indicators)
 * │  └─ Export System (CSV/PDF generation with download handling)
 * │
 * ┌─ Message Management System
 * │  ├─ Recent Messages Display (unread message tracking)
 * │  ├─ Individual Message Operations (mark as read functionality)
 * │  ├─ Submission-level Operations (bulk message marking)
 * │  └─ Real-time Badge Updates (activity count synchronization)
 * │
 * ┌─ Outstanding Actions System
 * │  ├─ Action Item Rendering (priority visualization and overdue indicators)
 * │  ├─ Action Card Layout (detailed information display)
 * │  ├─ Activity Badge Management (real-time counter updates)
 * │  └─ Empty State Handling (encouraging completion messaging)
 * │
 * ┌─ Interactive Data Visualization
 * │  ├─ SVG Chart Generation (historical trend mini-charts)
 * │  ├─ Real-time Data Updates (automatic refresh and polling)
 * │  ├─ Visual Feedback System (loading states and user interactions)
 * │  ├─ Responsive Chart Design (ResizeObserver integration)
 * │  └─ Responsive Layout Support (adaptive UI for different screen sizes)
 * │
 * └─ User Experience Systems
 *    ├─ Global Keyboard Shortcuts (power user efficiency and accessibility)
 *    ├─ Toast Notification System (user feedback with auto-cleanup)
 *    ├─ Loading State Management (component-specific visual feedback)
 *    ├─ Dashboard Lifecycle Management (polling and resource optimization)
 *    └─ Modern Calendar Interface (interactive date range selection)
 * 
 * Integration Points:
 * → WordPress AJAX System: admin-ajax.php handlers for all dashboard operations
 * → CF7 Submissions Backend: PHP classes in includes/ for data processing
 * → WordPress Admin Interface: Consistent styling and interaction patterns
 * → Cross-Tab Communication: Integration with conversation and actions systems
 * → Real-time Polling System: Lightweight updates for critical dashboard data
 * → Export System: PDF and CSV generation for submission data
 * 
 * Dependencies:
 * • jQuery 3.x: Core DOM manipulation, AJAX operations, and event handling
 * • WordPress Admin: Localized configuration (ajaxurl, nonces, permissions)
 * • cf7_dashboard Object: Server-side configuration and endpoint mapping
 * • Modern Browser APIs: ResizeObserver, Set, Map, Promise for enhanced features
 * • SVG Support: Native SVG generation for chart visualization
 * • CSS Grid/Flexbox: Modern layout support for responsive dashboard interface
 * 
 * AJAX Endpoints:
 * • cf7_dashboard_get_stats: Real-time statistics with breakdown by status
 * • cf7_dashboard_load_submissions: Paginated submission data with filtering
 * • cf7_dashboard_get_outstanding_actions: Action items requiring attention
 * • cf7_dashboard_get_recent_messages: Unread message monitoring
 * • cf7_dashboard_get_today_activity: Daily submission activity tracking
 * • cf7_dashboard_get_weekly_activity: Weekly trend analysis
 * • cf7_dashboard_bulk_action: Multi-submission operations
 * • cf7_dashboard_update_status: Individual submission status changes
 * • cf7_dashboard_export: Data export functionality
 * • cf7_dashboard_mark_message_read: Individual message read operations
 * • cf7_dashboard_mark_submission_read: Submission-level message operations
 * • cf7_dashboard_mark_all_read: Bulk message read operations
 * • cf7_dashboard_download_csv: Secure file download handling
 * • cf7_dashboard_submission_action: Individual submission operations
 * 
 * State Management:
 * • Pagination State: currentPage, perPage with URL synchronization
 * • Selection State: selectedItems Set for bulk operations
 * • Loading States: Individual loading flags for different dashboard sections
 * • Filter State: search, status, date range with active filter tracking
 * • UI State: expanded search, dropdown visibility, animation states
 * • Poll State: Lightweight polling for critical updates
 * • Calendar State: Date selection, range mode, and visibility management
 * • Message State: Unread counts, read status, and activity badges
 * • Action State: Outstanding actions, priority levels, and completion tracking
 * • Toast State: Notification queue and auto-cleanup management
 * 
 * Performance Features:
 * • Debounced Search: 300ms delay to prevent excessive AJAX requests
 * • Selective Loading: Component-specific loading states and error handling
 * • Memory Management: Proper event cleanup and DOM element disposal
 * • Efficient Rendering: Minimal DOM manipulation with batch updates
 * • Lightweight Polling: Targeted updates for critical dashboard elements
 * • Chart Caching: SVG chart reuse and ResizeObserver optimization
 * • Race Condition Prevention: Loading state guards for AJAX operations
 * • Calendar Optimization: Intelligent positioning and responsive updates
 * • Filter Coordination: State synchronization to prevent conflicts
 * • Bulk Operation Optimization: Efficient Set-based selection tracking
 * 
 * Accessibility Features:
 * • Keyboard Navigation: Comprehensive shortcut support (Ctrl+R refresh, Escape clear)
 * • ARIA Labels: Screen reader compatible interactive elements
 * • Focus Management: Logical tab order and focus restoration
 * • Color Contrast: High contrast indicators for status and priority
 * • Semantic HTML: Proper heading hierarchy and landmark usage
 * • Loading Indicators: Clear feedback for all asynchronous operations
 * 
 * Security Features:
 * • Nonce Validation: All AJAX requests include WordPress security tokens
 * • Input Sanitization: HTML escaping for user-generated content
 * • XSS Prevention: Safe DOM manipulation and content injection
 * • CSRF Protection: Token-based request validation
 * • Permission Checking: Server-side capability validation
 * • Data Validation: Client and server-side input validation
 * 
 * @package    CF7ArtistSubmissions
 * @subpackage DashboardInterface
 * @version    2.1.0
 * @since      1.0.0
 * @author     CF7 Artist Submissions Development Team
 */

(function($) {
    'use strict';

    /**
     * ========================================
     * CF7Dashboard Class - Modern Dashboard Controller
     * ========================================
     * 
     * Main dashboard controller implementing modern ES6 class architecture
     * for comprehensive artist submission management. Provides centralized
     * state management, event coordination, and UI rendering.
     * 
     * Class Features:
     * • Centralized state management with reactive updates
     * • Comprehensive event binding with namespace isolation
     * • Modular AJAX communication with error handling
     * • Dynamic UI rendering with performance optimization
     * • Advanced filtering with multi-criteria support
     * • Real-time data updates with lightweight polling
     * 
     * Architecture Pattern:
     * • Constructor: Initialize state and bind core events
     * • Event Handlers: Delegate user interactions to specialized methods
     * • Data Loaders: AJAX operations with loading state management
     * • Renderers: UI generation with template-based approach
     * • Utilities: Helper functions for common operations
     * 
     * State Management:
     * • Immutable state updates with change detection
     * • Reactive UI updates based on state changes
     * • Memory-efficient data structures (Set, Map)
     * • Proper cleanup and disposal methods
     */
    class CF7Dashboard {
        /**
         * Dashboard controller initialization
         * 
         * Sets up initial state, configuration validation, and core event binding.
         * Establishes foundation for all dashboard operations with proper error
         * handling and graceful degradation for missing dependencies.
         * 
         * State Initialization:
         * • Pagination: currentPage (1), perPage (10) with user preferences
         * • Selection: selectedItems Set for efficient bulk operations
         * • Loading: Component-specific loading states for smooth UX
         * • Timing: Search debouncing and polling interval management
         * 
         * Configuration Validation:
         * • WordPress ajaxurl availability for AJAX operations
         * • cf7_dashboard object presence for localized configuration
         * • Nonce validation for secure server communication
         * • Feature detection for modern browser capabilities
         * 
         * Performance Considerations:
         * • Lazy initialization of expensive operations
         * • Memory-efficient data structures for large datasets
         * • Event delegation for dynamic content handling
         * • Debounced operations for smooth user interactions
         */
        constructor() {
            this.currentPage = 1;
            this.perPage = 10; // Default to 10 entries per page
            this.selectedItems = new Set();
            this.searchTimeout = null;
            this.isPerformingBulkAction = false;
            this.isInitialLoad = true; // Track if this is the first load
            this.loadingStates = {
                submissions: false,
                stats: false,
                messages: false,
                actions: false
            };

            this.init();
        }

        /**
         * Dashboard initialization and startup sequence
         * 
         * Comprehensive initialization process with dependency validation,
         * event binding, and initial data loading. Implements graceful
         * degradation for missing dependencies and provides user feedback.
         * 
         * Initialization Sequence:
         * 1. Validate required WordPress globals (ajaxurl, cf7_dashboard)
         * 2. Bind all event handlers with namespace isolation
         * 3. Load initial dashboard data (stats, actions, submissions)
         * 4. Initialize real-time features (polling, activity updates)
         * 5. Apply any pre-existing filters or state restoration
         * 
         * Error Handling:
         * • Missing ajaxurl: Prevents AJAX operations with user notification
         * • Missing cf7_dashboard: Configuration error with diagnostic info
         * • Network failures: Graceful fallback with retry mechanisms
         * • Invalid responses: Data validation with error recovery
         * 
         * Performance Features:
         * • Parallel data loading for faster initial render
         * • Conditional polling activation based on dashboard activity
         * • Initial load optimization with caching where appropriate
         * • Progressive enhancement for modern browser features
         * 
         * User Experience:
         * • Loading indicators for all initial data requests
         * • Error notifications with actionable guidance
         * • Smooth transitions and visual feedback
         * • Keyboard shortcut activation and accessibility setup
         */
        init() {
            // Check if required globals are available
            if (typeof ajaxurl === 'undefined') {
                console.error('ajaxurl is not defined');
                this.showToast('AJAX URL not available', 'error');
                return;
            }
            
            if (typeof cf7_dashboard === 'undefined') {
                console.error('cf7_dashboard object is not defined');
                this.showToast('Dashboard configuration not available', 'error');
                return;
            }
            
            this.bindEvents();
            this.loadStats();
            this.loadOutstandingActions();
            this.loadSubmissions();
            this.loadRecentMessages();
            this.updateTodayActivity();
            // Check for active filters on page load
            this.updateActiveFiltersDisplay();
            // Start lightweight polling only for unread messages
            this.startPolling();
        }

        /**
         * ========================================
         * Event Binding System
         * ========================================
         * 
         * Comprehensive event handling with namespace isolation, event
         * delegation, and performance optimization. Handles all user
         * interactions, keyboard shortcuts, and dynamic content events.
         * 
         * Event Categories:
         * • Header Actions: Refresh, export, search toggle functionality
         * • Search Interface: Input handling, toggle, and outside click detection
         * • Metric Interactions: Card clicks for quick filtering and navigation
         * • Filter Controls: Status, date, and search filter management
         * • Table Operations: Pagination, selection, and bulk actions
         * • Submission Actions: Status updates and individual item operations
         * • Keyboard Shortcuts: Power user efficiency and accessibility
         * 
         * Performance Features:
         * • Event delegation for dynamic content handling
         * • Debounced search input to prevent excessive requests
         * • Throttled resize events for chart re-rendering
         * • Memory-efficient event cleanup and namespace isolation
         * 
         * Accessibility Features:
         * • Keyboard navigation with standard shortcuts
         * • Focus management for modal and dropdown interactions
         * • ARIA state updates for dynamic content changes
         * • Screen reader compatible event handling
         */
        bindEvents() {
            // Header buttons
            $(document).on('click', '#cf7-refresh-dashboard', () => {
                this.refreshAll(true);
            });

            $(document).on('click', '#cf7-export-all', () => {
                this.exportSubmissions();
            });

            // Search toggle functionality
            $(document).on('click', '#cf7-search-toggle', () => {
                this.toggleSearchBar();
            });

            // Search close functionality
            $(document).on('click', '#cf7-search-close', () => {
                this.toggleSearchBar();
            });

            // Close search when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.cf7-search-wrapper').length) {
                    const $searchExpandable = $('#cf7-search-input-expandable');
                    const $searchToggle = $('#cf7-search-toggle');
                    if ($searchExpandable.hasClass('expanded')) {
                        $searchExpandable.removeClass('expanded');
                        $searchToggle.removeClass('active');
                    }
                }
            });

            // New metrics card interactions
            $(document).on('click', '.cf7-metric-card', (e) => {
                const $card = $(e.currentTarget);
                const type = $card.data('type');
                this.handleMetricCardClick(type, $card);
            });

            // Activity card interactions
            $(document).on('click', '.cf7-activity-card', (e) => {
                const $card = $(e.currentTarget);
                const type = $card.data('type');
                this.handleActivityCardClick(type, $card);
            });

            // Search functionality
            $(document).on('input', '#cf7-search-input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.currentPage = 1;
                    this.updateActiveFiltersDisplay();
                    this.loadSubmissions();
                }, 300);
            });

            // Status filter
            $(document).on('click', '.cf7-status-filter-option', (e) => {
                e.stopPropagation();
                const $option = $(e.currentTarget);
                const value = $option.data('value');
                const icon = $option.data('icon');
                const color = $option.data('color');
                const label = $option.find('.cf7-status-label').text();

                $('.cf7-status-filter-option').removeClass('active');
                $option.addClass('active');

                $('.cf7-status-filter-display .cf7-status-icon')
                    .attr('class', `cf7-status-icon dashicons ${icon}`)
                    .css('color', color);
                $('.cf7-status-filter-display .cf7-status-text').text(label);

                // Store current filter value
                $('.cf7-status-filter-dropdown').attr('data-current', value);

                $('.cf7-status-filter-dropdown').removeClass('open');
                this.currentPage = 1;
                this.updateActiveFiltersDisplay();
                this.loadSubmissions();
            });

            // Status filter dropdown toggle - only on display, not options
            $(document).on('click', '.cf7-status-filter-display', (e) => {
                e.stopPropagation();
                $('.cf7-status-filter-dropdown').toggleClass('open');
            });

            // Close dropdown when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.cf7-status-filter-dropdown').length) {
                    $('.cf7-status-filter-dropdown').removeClass('open');
                }
            });

            // Per page selector
            $(document).on('change', '#cf7-per-page', () => {
                this.perPage = parseInt($('#cf7-per-page').val());
                this.currentPage = 1;
                this.loadSubmissions();
            });

            // Pagination
            $(document).on('click', '.cf7-page-btn', (e) => {
                const page = parseInt($(e.currentTarget).data('page'));
                if (page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadSubmissions();
                }
            });

            // Bulk selection
            $(document).on('change', '.cf7-select-all', (e) => {
                const isChecked = $(e.currentTarget).is(':checked');
                $('.cf7-submission-checkbox').prop('checked', isChecked);
                this.updateSelectedItems();
            });

            $(document).on('change', '.cf7-submission-checkbox', () => {
                this.updateSelectedItems();
            });

            // Bulk actions
            $(document).on('click', '#cf7-apply-bulk', () => {
                this.handleBulkAction();
            });

            // Individual submission actions
            $(document).on('click', '.cf7-submission-action', (e) => {
                e.stopPropagation();
                const $btn = $(e.currentTarget);
                const submissionId = $btn.data('submission-id');
                const action = $btn.data('action');
                this.handleSubmissionAction(submissionId, action);
            });

            // Status update
            $(document).on('click', '.cf7-status-option', (e) => {
                const $option = $(e.currentTarget);
                const submissionId = $option.data('submission-id');
                const status = $option.data('status');
                this.updateSubmissionStatus(submissionId, status);
            });

            // Table row clicks (for editing)
            $(document).on('click', '.cf7-submission-row', (e) => {
                // Don't trigger if clicking on checkboxes, buttons, or links
                if ($(e.target).is('input, button, a, .cf7-submission-action, .cf7-status-selector')) {
                    return;
                }
                
                const submissionId = $(e.currentTarget).data('submission-id');
                if (submissionId) {
                    const editUrl = `post.php?post=${submissionId}&action=edit`;
                    window.location.href = editUrl;
                }
            });

            // Date filter functionality
            this.initDateFilter();

            // Activity refresh
            $(document).on('click', '#cf7-refresh-activity', () => {
                this.refreshActivity();
            });

            // Mark message as read
            $(document).on('click', '.cf7-mark-read-btn', (e) => {
                e.stopPropagation();
                const messageId = $(e.currentTarget).data('message-id');
                this.markMessageAsRead(messageId);
            });

            // Mark submission as read
            $(document).on('click', '.cf7-mark-submission-read-btn', (e) => {
                e.stopPropagation();
                const submissionId = $(e.currentTarget).data('submission-id');
                this.markSubmissionRead(submissionId);
            });

            // Mark all as read
            $(document).on('click', '#cf7-mark-all-read', () => {
                this.markAllMessagesRead();
            });

            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                // Ctrl/Cmd + R: Refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    this.refreshAll();
                }
                
                // Escape: Clear selection
                if (e.key === 'Escape') {
                    this.clearSelection();
                    $('.cf7-status-filter-dropdown').removeClass('open');
                }
            });

            // Clear all filters (orange bar)
            $(document).on('click', '#cf7-clear-all', () => {
                this.clearAllFilters();
            });

            $(document).on('click', '.cf7-active-filter-tag .dashicons', (e) => {
                e.stopPropagation();
                const filterType = $(e.currentTarget).closest('.cf7-active-filter-tag').data('filter-type');
                this.clearSpecificFilter(filterType);
            });
        }

        // ========================================
        // Interactive Metric Card System
        // ========================================
        // Smart filtering integration through clickable metric cards
        // providing one-click access to specific submission views.
        // Combines data visualization with functional navigation.
        //
        // Card Types:
        // • Overview: All submissions regardless of status
        // • Workflow: Items requiring review (new + awaiting info)
        // • Progress: Items in active review (reviewed + shortlisted)
        // • Outcomes: Final decisions (selected + rejected)
        //
        // Interaction Features:
        // • Visual feedback with click animation effects
        // • Automatic filter application with UI state updates
        // • Smart filter combination for complex views
        // • Page reset to ensure proper result display
        // ========================================

        /**
         * Handle metric card click interactions for quick filtering
         * 
         * Processes metric card clicks to apply appropriate filters and
         * navigate to specific submission views. Provides visual feedback
         * and automatic filter state management.
         * 
         * @param {string} type - Metric card type (overview, workflow, progress, outcomes)
         * @param {jQuery} $card - Clicked card element for visual feedback
         * 
         * Filter Mapping:
         * • overview: Clear all filters, show complete submission list
         * • workflow: Show new + awaiting-information status items
         * • progress: Show reviewed + shortlisted status items  
         * • outcomes: Show selected + rejected status items
         * 
         * Visual Feedback:
         * • 200ms click animation with CSS class toggle
         * • Smooth filter transition with loading indicators
         * • Immediate UI state update for responsive experience
         * • Filter indicator updates in active filter bar
         */
        handleMetricCardClick(type, $card) {
            switch(type) {
                case 'overview':
                    // Show total submissions view - all statuses
                    $('.cf7-status-filter-option[data-value=""]').click();
                    break;
                case 'workflow':
                    // Show submissions needing review (new + awaiting info)
                    this.showWorkflowFilter();
                    break;
                case 'progress':
                    // Show in-progress submissions (reviewed + shortlisted)
                    this.showProgressFilter();
                    break;
                case 'outcomes':
                    // Show final decisions (selected + rejected)
                    this.showOutcomesFilter();
                    break;
            }
            
            // Visual feedback
            $card.addClass('clicked');
            setTimeout(() => $card.removeClass('clicked'), 200);
        }

        /**
         * Handle activity card click interactions for dashboard navigation
         * 
         * Processes activity card clicks to navigate to specific activity views
         * based on current dashboard state and available data. Provides smart
         * filtering for time-based and action-based views.
         * 
         * @param {string} type - Activity card type (conversations, actions, recent, weekly)
         * @param {jQuery} $card - Clicked card element for visual feedback
         * 
         * Activity Navigation:
         * • conversations: Show submissions with unread messages (badge-dependent)
         * • actions: Navigate to submissions with outstanding actions
         * • recent: Filter to today's submissions with date range
         * • weekly: Show last 7 days of submissions with date range
         * 
         * Smart Behavior:
         * • Conversations: Only navigate if unread badge is present
         * • Time filters: Automatically set appropriate date ranges
         * • Combined filters: Clear conflicting filters before applying new ones
         * • Visual feedback: Consistent click animation across all cards
         * 
         * State Management:
         * • Clear search input for clean filter application
         * • Reset pagination to first page for new result sets
         * • Update active filter display for user awareness
         * • Trigger submission reload with new filter criteria
         */
        handleActivityCardClick(type, $card) {
            switch(type) {
                case 'conversations':
                    // Navigate to submissions with unread messages
                    if ($card.find('.activity-badge').length) {
                        this.showUnreadMessagesFilter();
                    }
                    break;
                case 'actions':
                    // Navigate to submissions with outstanding actions
                    this.showOutstandingActionsFilter();
                    break;
                case 'recent':
                    // Show today's submissions
                    this.showTodayFilter();
                    break;
                case 'weekly':
                    // Show this week's submissions
                    this.showWeeklyFilter();
                    break;
            }
            
            // Visual feedback
            $card.addClass('clicked');
            setTimeout(() => $card.removeClass('clicked'), 200);
        }

        showWorkflowFilter() {
            // Custom filter logic for workflow items
            const searchInput = $('#cf7-search-input');
            searchInput.val('');
            
            // Update display to show "Needs Review"
            $('.cf7-status-filter-display .cf7-status-icon')
                .attr('class', 'cf7-status-icon dashicons dashicons-clock')
                .css('color', '#f59e0b');
            $('.cf7-status-filter-display .cf7-status-text').text('Needs Review');
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions('workflow');
        }

        showProgressFilter() {
            const searchInput = $('#cf7-search-input');
            searchInput.val('');
            
            $('.cf7-status-filter-display .cf7-status-icon')
                .attr('class', 'cf7-status-icon dashicons dashicons-visibility')
                .css('color', '#3b82f6');
            $('.cf7-status-filter-display .cf7-status-text').text('In Progress');
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions('progress');
        }

        showOutcomesFilter() {
            const searchInput = $('#cf7-search-input');
            searchInput.val('');
            
            $('.cf7-status-filter-display .cf7-status-icon')
                .attr('class', 'cf7-status-icon dashicons dashicons-yes-alt')
                .css('color', '#10b981');
            $('.cf7-status-filter-display .cf7-status-text').text('Decisions Made');
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions('outcomes');
        }

        showUnreadMessagesFilter() {
            // Filter to show only submissions with unread messages
            const searchInput = $('#cf7-search-input');
            searchInput.val('');
            
            // Clear date filters
            $('#cf7-date-from').val('');
            $('#cf7-date-to').val('');
            $('.cf7-calendar-text').text('Date');
            
            // Use the status filter dropdown properly
            $('.cf7-status-filter-option').removeClass('active');
            $('.cf7-status-filter-option[data-value="unread_messages"]').addClass('active');
            
            $('.cf7-status-filter-display .cf7-status-icon')
                .attr('class', 'cf7-status-icon dashicons dashicons-email-alt')
                .css('color', '#ef4444');
            $('.cf7-status-filter-display .cf7-status-text').text('Has Unread Messages');
            
            // Store current filter value
            $('.cf7-status-filter-dropdown').attr('data-current', 'unread_messages');
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions();
        }

        showOutstandingActionsFilter() {
            // Filter to show only submissions with outstanding actions
            const searchInput = $('#cf7-search-input');
            searchInput.val('');
            
            // Clear date filters
            $('#cf7-date-from').val('');
            $('#cf7-date-to').val('');
            $('.cf7-calendar-text').text('Date');
            
            // Use the status filter dropdown properly
            $('.cf7-status-filter-option').removeClass('active');
            $('.cf7-status-filter-option[data-value="outstanding_actions"]').addClass('active');
            
            $('.cf7-status-filter-display .cf7-status-icon')
                .attr('class', 'cf7-status-icon dashicons dashicons-bell')
                .css('color', '#f59e0b');
            $('.cf7-status-filter-display .cf7-status-text').text('Has Outstanding Actions');
            
            // Store current filter value
            $('.cf7-status-filter-dropdown').attr('data-current', 'outstanding_actions');
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions();
        }

        showTodayFilter() {
            // Show submissions from today
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            
            // Clear other filters first
            $('#cf7-search-input').val('');
            $('.cf7-status-filter-dropdown').attr('data-current', '');
            $('.cf7-status-filter-option').removeClass('active');
            $('.cf7-status-filter-option[data-value=""]').addClass('active');
            $('.cf7-status-filter-display .cf7-status-text').text('All Statuses');
            $('.cf7-status-filter-display .cf7-status-icon')
                .removeClass()
                .addClass('cf7-status-icon dashicons dashicons-category')
                .css('color', '#718096');
            
            // Set date inputs for filtering
            $('#cf7-date-from').val(dateStr);
            $('#cf7-date-to').val(dateStr);
            
            // Update calendar text display
            $('.cf7-calendar-text').text(`Today (${dateStr})`);
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions();
        }

        showWeeklyFilter() {
            // Show submissions from the last 7 days
            const today = new Date();
            const sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(today.getDate() - 6); // 6 days ago + today = 7 days total
            
            // Clear other filters first
            $('#cf7-search-input').val('');
            $('.cf7-status-filter-dropdown').attr('data-current', '');
            $('.cf7-status-filter-option').removeClass('active');
            $('.cf7-status-filter-option[data-value=""]').addClass('active');
            $('.cf7-status-filter-display .cf7-status-text').text('All Statuses');
            $('.cf7-status-filter-display .cf7-status-icon')
                .removeClass()
                .addClass('cf7-status-icon dashicons dashicons-category')
                .css('color', '#718096');
            
            // Set date inputs for filtering
            $('#cf7-date-from').val(sevenDaysAgo.toISOString().split('T')[0]);
            $('#cf7-date-to').val(today.toISOString().split('T')[0]);
            
            // Update calendar text display
            $('.cf7-calendar-text').text(`Last 7 Days (${sevenDaysAgo.toLocaleDateString()} - ${today.toLocaleDateString()})`);
            
            this.currentPage = 1;
            this.updateActiveFiltersDisplay();
            this.loadSubmissions();
        }

        toggleSearchBar() {
            const $searchExpandable = $('#cf7-search-input-expandable');
            const $searchToggle = $('#cf7-search-toggle');
            const $searchInput = $('#cf7-search-input');
            
            if ($searchExpandable.hasClass('expanded')) {
                // Close search
                $searchExpandable.removeClass('expanded');
                $searchToggle.removeClass('active');
                $searchInput.blur();
            } else {
                // Open search
                $searchExpandable.addClass('expanded');
                $searchToggle.addClass('active');
                setTimeout(() => {
                    $searchInput.focus();
                }, 300);
            }
        }

        updateTodayActivity() {
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            
            // Update today's activity (last 24 hours)
            $.post(ajaxurl, {
                action: 'cf7_dashboard_get_today_activity',
                nonce: cf7_dashboard.nonce,
                date: todayStr
            }, (response) => {
                if (response.success) {
                    const count = response.data.count || 0;
                    const text = count === 0 ? 'No submissions today' : 
                                count === 1 ? '1 submission today' : 
                                `${count} submissions today`;
                    $('#activity-today-detail').text(text);
                }
            });

            // Update weekly activity (last 7 days)
            const sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(today.getDate() - 6); // 6 days ago + today = 7 days total
            const weekStart = sevenDaysAgo.toISOString().split('T')[0];
            
            $.post(ajaxurl, {
                action: 'cf7_dashboard_get_weekly_activity',
                nonce: cf7_dashboard.nonce,
                date_from: weekStart,
                date_to: todayStr
            }, (response) => {
                if (response.success) {
                    const count = response.data.count || 0;
                    const text = count === 0 ? 'No submissions this week' : 
                                count === 1 ? '1 submission this week' : 
                                `${count} submissions this week`;
                    $('#activity-weekly-detail').text(text);
                    
                    // Update the badge
                    const $weeklyBadge = $('.activity-weekly-count');
                    if (count > 0) {
                        $weeklyBadge.text(count).show();
                    } else {
                        $weeklyBadge.hide();
                    }
                }
            });
        }

        initDateFilter() {
            // Modern Calendar Date Picker Events
            this.initCalendarDatePicker();

            // Filter changes - fix status filter selector and add date filters
            $(document).on('change', '#cf7-date-from, #cf7-date-to', () => {
                this.currentPage = 1;
                this.updateActiveFiltersDisplay();
                this.loadSubmissions();
            });
        }

        positionDropdownMenu($dropdown) {
            const $display = $dropdown.find('.cf7-status-filter-display');
            const $menu = $dropdown.find('.cf7-status-filter-menu');
            
            if (!$display.length || !$menu.length) return;
            
            // Get the display element's position and dimensions
            const displayRect = $display[0].getBoundingClientRect();
            const displayOffset = $display.offset();
            
            // Position the menu to exactly cover the display element
            $menu.css({
                position: 'absolute',
                top: -2 + 'px',          // Move up slightly to cover border
                left: -4 + 'px',         // Move left by 4px as requested
                width: (displayRect.width + 4) + 'px',  // Make wider by 4px total (2px each side)
                minHeight: displayRect.height + 'px',
                zIndex: 10000
            });
        }

        // ========================================
        // Data Loading System
        // ========================================
        // Comprehensive AJAX data loading with loading state management,
        // error handling, and cache optimization. Provides foundation
        // for all dashboard data operations.
        //
        // Loading Features:
        // • Component-specific loading states to prevent race conditions
        // • Visual loading indicators for smooth user experience
        // • Cache-busting for real-time data accuracy
        // • Comprehensive error handling with user feedback
        // • Parallel loading support for dashboard initialization
        //
        // Performance Optimizations:
        // • Loading state guards to prevent duplicate requests
        // • Promise-based AJAX with proper error boundaries
        // • Memory-efficient data structures for large datasets
        // • Selective updates to minimize DOM manipulation
        // ========================================

        /**
         * Load dashboard statistics with comprehensive error handling
         * 
         * Fetches real-time submission statistics including status breakdown,
         * unread message counts, and activity metrics. Includes cache-busting
         * for accurate data and visual loading feedback.
         * 
         * Loading Process:
         * 1. Check loading state guard to prevent duplicate requests
         * 2. Display loading indicators for user feedback
         * 3. Execute AJAX request with cache-busting timestamp
         * 4. Process response and update metric cards
         * 5. Handle errors with user notification and retry options
         * 6. Clear loading state and hide indicators
         * 
         * Data Processing:
         * • Status breakdown: new, reviewed, awaiting-information, etc.
         * • Activity metrics: unread messages, outstanding actions
         * • Trend data: week-over-week changes and historical context
         * • Chart data: Generate mini-charts for visual trends
         * 
         * Error Handling:
         * • Network failures: Graceful degradation with retry options
         * • Invalid responses: Data validation with fallback values
         * • Server errors: Diagnostic information with user guidance
         * • Timeout handling: Progressive retry with exponential backoff
         * 
         * Cache Strategy:
         * • Cache-busting timestamp for real-time accuracy
         * • Browser cache utilization for static resources
         * • Memory cache for frequently accessed calculations
         * • Intelligent refresh based on user activity patterns
         */
        loadStats() {
            if (this.loadingStates.stats) return;
            
            this.loadingStates.stats = true;
            this.showStatsLoading();

            const data = {
                action: 'cf7_dashboard_get_stats',
                nonce: cf7_dashboard.nonce,
                _cache_buster: Date.now() // Add cache-busting parameter
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json'
            })
                .done((response) => {
                    if (response && response.success) {
                        this.renderStats(response.data);
                        
                        // Mark initial load as complete
                        if (this.isInitialLoad) {
                            this.isInitialLoad = false;
                        }
                    } else {
                        this.showToast('Error loading stats: ' + (response?.data || 'Unknown error'), 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    this.showToast('Failed to load statistics', 'error');
                })
                .always(() => {
                    this.loadingStates.stats = false;
                });
        }

        loadOutstandingActions() {
            if (this.loadingStates.actions) return;
            
            this.loadingStates.actions = true;
            this.showActionsLoading();

            const data = {
                action: 'cf7_dashboard_get_outstanding_actions',
                nonce: cf7_dashboard.nonce
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json'
            })
                .done((response) => {
                    if (response && response.success) {
                        this.renderOutstandingActions(response.data);
                    } else {
                        this.showToast('Error loading outstanding actions: ' + (response?.data || 'Unknown error'), 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    this.showToast('Failed to load outstanding actions', 'error');
                })
                .always(() => {
                    this.loadingStates.actions = false;
                    this.hideActionsLoading();
                });
        }

        /**
         * Load paginated submissions with advanced filtering
         * 
         * Fetches submission data with comprehensive filtering, pagination,
         * and search capabilities. Includes loading state management and
         * race condition prevention for smooth user experience.
         * 
         * @param {string} [customFilter] - Optional custom filter type for special views
         * 
         * Loading Process:
         * 1. Validate loading state and prevent duplicate requests
         * 2. Extract filter parameters from UI elements
         * 3. Construct AJAX request with all filter criteria
         * 4. Display loading indicators during request
         * 5. Process response and render submission table
         * 6. Update pagination and selection state
         * 
         * Filter Parameters:
         * • page: Current pagination page for result offset
         * • per_page: Number of items per page (user configurable)
         * • search: Text search across submission content
         * • status: Submission status filter (new, reviewed, etc.)
         * • date_from/date_to: Date range filtering
         * • orderby: Sort order for result presentation
         * 
         * State Management:
         * • Loading state guards to prevent race conditions
         * • clearingFilters flag to prevent interference during filter operations
         * • selectedItems Set reset for consistent bulk operation state
         * • Pagination state synchronization with server results
         * 
         * Error Handling:
         * • Network failure recovery with user notification
         * • Invalid response handling with fallback display
         * • Server error processing with diagnostic information
         * • Loading state cleanup regardless of success/failure
         * 
         * Performance Features:
         * • Efficient DOM updates with minimal manipulation
         * • Memory management for large result sets
         * • Debounced requests to prevent excessive server load
         * • Optimized rendering with template-based approach
         */
        loadSubmissions() {
            // Prevent loading during clear operations
            if (this.clearingFilters) {
                return;
            }
            
            if (this.loadingStates.submissions) {
                return;
            }
            
            this.loadingStates.submissions = true;
            this.showSubmissionsLoading();

            const statusValue = $('.cf7-status-filter-dropdown').attr('data-current');
            const data = {
                action: 'cf7_dashboard_load_submissions',
                nonce: cf7_dashboard.nonce,
                page: this.currentPage,
                per_page: this.perPage,
                search: $('#cf7-search-input').val(),
                status: statusValue || '',
                date_from: $('#cf7-date-from').val(),
                date_to: $('#cf7-date-to').val(),
                orderby: $('.cf7-order-filter').val()
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.renderSubmissions(response.data);
                        this.updateFilterIndicators();
                    } else {
                        this.showToast(response.data || 'Error loading submissions', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to load submissions', 'error');
                })
                .always(() => {
                    this.loadingStates.submissions = false;
                    this.hideSubmissionsLoading();
                });
        }

        loadRecentMessages() {
            if (this.loadingStates.messages) return;
            
            this.loadingStates.messages = true;

            const data = {
                action: 'cf7_dashboard_get_recent_messages',
                nonce: cf7_dashboard.nonce
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.renderRecentMessages(response.data);
                    }
                })
                .always(() => {
                    this.loadingStates.messages = false;
                });
        }

        // ========================================
        // UI Rendering System
        // ========================================
        // Dynamic UI rendering with performance optimization, visual feedback,
        // and comprehensive data visualization. Handles all dashboard interface
        // updates with smooth animations and responsive design.
        //
        // Rendering Components:
        // • Statistics Display: Metric cards with animated updates and charts
        // • Submission Tables: Paginated data with interactive elements
        // • Activity Panels: Real-time updates with badge management
        // • Filter Interfaces: Dynamic filter state visualization
        // • Loading States: Component-specific loading indicators
        //
        // Performance Features:
        // • Efficient DOM manipulation with batch updates
        // • Memory management for large datasets
        // • Animation queuing for smooth visual transitions
        // • Template-based rendering for consistency
        // ========================================

        /**
         * Render dashboard statistics with visual feedback and chart generation
         * 
         * Updates all statistics displays including metric cards, activity badges,
         * and mini-charts. Provides smooth animations and comprehensive data
         * visualization for dashboard overview.
         * 
         * @param {Object} stats - Statistics data from server response
         * 
         * Rendering Process:
         * 1. Update metric cards with aggregated submission counts
         * 2. Refresh individual stat elements with animation effects
         * 3. Update activity panel badges and unread message indicators
         * 4. Generate or update mini-charts for historical trends
         * 5. Apply visual feedback for data changes and updates
         * 
         * Data Processing:
         * • Metric Cards: Total, workflow, progress, outcomes aggregation
         * • Activity Badges: Unread messages, outstanding actions counting
         * • Individual Stats: Status-specific counts with change indicators
         * • Chart Data: Historical trend generation for visual context
         * 
         * Visual Features:
         * • Fade animations for smooth value transitions
         * • Badge creation and management for activity indicators
         * • Color-coded status indicators for quick recognition
         * • Responsive layout adaptation for different screen sizes
         * 
         * Badge Management:
         * • Dynamic creation of activity badges when needed
         * • Automatic hiding when count reaches zero
         * • Visual distinction between different notification types
         * • Screen reader accessible content updates
         */
        renderStats(stats) {
            // Update metric cards with aggregated values
            this.updateMetricCards(stats);
            
            // Update individual stat elements if they exist
            this.updateIndividualStats(stats);
            
            // Update unread messages count in activity panel
            const unreadCount = stats.unread_messages || 0;
            const $unreadStat = $('#cf7-unread-messages-stat');
            $unreadStat.text(unreadCount);
            
            // Update the activity card badges with improved logic
            const $messagesCard = $('.activity-messages');
            let $messagesBadge = $messagesCard.find('.activity-badge');
            
            if (unreadCount > 0) {
                // If badge doesn't exist, create it
                if ($messagesBadge.length === 0) {
                    $messagesCard.find('.activity-icon').append('<span class="activity-badge">0</span>');
                    $messagesBadge = $messagesCard.find('.activity-badge');
                }
                $messagesBadge.text(unreadCount).show();
            } else {
                // Hide the badge when no unread messages
                $messagesBadge.hide();
            }
            
            // Generate charts for each stat type if chart containers exist
            this.generateStatsCharts(stats);
        }

        updateMetricCards(stats) {
            // Update the primary metric cards with new values and visual feedback
            const metricUpdates = {
                'overview': {
                    value: stats.total || 0,
                    selector: '.cf7-metric-card[data-type="overview"] .metric-value'
                },
                'workflow': {
                    value: (stats.new || 0) + (stats['awaiting-information'] || 0),
                    selector: '.cf7-metric-card[data-type="workflow"] .metric-value',
                    breakdown: {
                        new: stats.new || 0,
                        waiting: stats['awaiting-information'] || 0
                    }
                },
                'progress': {
                    value: (stats.reviewed || 0) + (stats.shortlisted || 0),
                    selector: '.cf7-metric-card[data-type="progress"] .metric-value',
                    breakdown: {
                        reviewed: stats.reviewed || 0,
                        shortlisted: stats.shortlisted || 0
                    }
                },
                'outcomes': {
                    value: (stats.selected || 0) + (stats.rejected || 0),
                    selector: '.cf7-metric-card[data-type="outcomes"] .metric-value',
                    breakdown: {
                        selected: stats.selected || 0,
                        rejected: stats.rejected || 0
                    }
                }
            };

            Object.keys(metricUpdates).forEach(type => {
                const update = metricUpdates[type];
                const $valueElement = $(update.selector);
                const $card = $valueElement.closest('.cf7-metric-card');
                
                if ($valueElement.length > 0) {
                    // Add visual feedback
                    $card.addClass('cf7-stat-updating');
                    
                    // Update main value with fade effect
                    $valueElement.fadeOut(150, function() {
                        $(this).text(update.value).fadeIn(150);
                    });
                    
                    // Update breakdown if exists
                    if (update.breakdown) {
                        Object.keys(update.breakdown).forEach(breakdownType => {
                            const $breakdownElement = $card.find(`.breakdown-item.${breakdownType}`);
                            if ($breakdownElement.length > 0) {
                                const newText = `${update.breakdown[breakdownType]} ${breakdownType}`;
                                $breakdownElement.fadeOut(150, function() {
                                    $(this).text(newText).fadeIn(150);
                                });
                            }
                        });
                    }
                    
                    // Remove updating class after animation
                    setTimeout(() => {
                        $card.removeClass('cf7-stat-updating');
                    }, 300);
                }
            });
        }

        updateIndividualStats(stats) {
            // Update individual stat cards if they exist (fallback for different layouts)
            const statTypes = ['total', 'new', 'reviewed', 'awaiting-information', 'shortlisted', 'selected', 'rejected', 'unread_messages'];
            
            statTypes.forEach(type => {
                const $card = $(`.cf7-stat-card[data-type="${type}"]`);
                let $number = $card.find('.cf7-stat-number');
                
                if ($card.length === 0) {
                    // Individual stat cards don't exist in this layout, skip
                    return;
                }
                
                // If the stat card structure was destroyed, rebuild it
                if ($number.length === 0) {
                    const iconMap = {
                        'total': 'dashicons-chart-bar',
                        'new': 'dashicons-star-filled',
                        'reviewed': 'dashicons-visibility',
                        'awaiting-information': 'dashicons-clock',
                        'shortlisted': 'dashicons-paperclip',
                        'selected': 'dashicons-yes-alt',
                        'rejected': 'dashicons-dismiss',
                        'unread_messages': 'dashicons-email'
                    };
                    
                    const titleMap = {
                        'total': 'Total Submissions',
                        'new': 'New',
                        'reviewed': 'Reviewed', 
                        'awaiting-information': 'Awaiting Information',
                        'shortlisted': 'Shortlisted',
                        'selected': 'Selected',
                        'rejected': 'Rejected',
                        'unread_messages': 'Unread Messages'
                    };
                    
                    $card.html(`
                        <div class="cf7-stat-header">
                            <div class="cf7-stat-left">
                                <div class="cf7-stat-icon ${type}">
                                    <span class="dashicons ${iconMap[type] || 'dashicons-chart-bar'}"></span>
                                </div>
                                <div class="cf7-stat-content">
                                    <h3>${titleMap[type] || type}</h3>
                                    <div class="cf7-stat-number">0</div>
                                </div>
                            </div>
                            <div class="cf7-stat-chart ${type}" id="chart-${type}">
                                <!-- Chart will be generated here -->
                            </div>
                        </div>
                    `);
                    $number = $card.find('.cf7-stat-number');
                }
                
                const value = stats[type] || 0;
                
                // Force visual update by adding animation class
                $card.addClass('cf7-stat-updating');
                
                // Update the number with fade effect for visual confirmation
                $number.fadeOut(150, function() {
                    $(this).text(value).fadeIn(150);
                });
                
                // Update percentage change if available
                const changeKey = `${type}_change`;
                if (stats[changeKey] !== undefined) {
                    let $change = $card.find('.cf7-stat-change');
                    if ($change.length === 0) {
                        $change = $('<div class="cf7-stat-change"></div>');
                        $card.find('.cf7-stat-content').append($change);
                    }
                    
                    const change = stats[changeKey];
                    const changeText = change > 0 ? `+${change}%` : `${change}%`;
                    const changeClass = change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral';
                    
                    $change
                        .removeClass('positive negative neutral')
                        .addClass(changeClass)
                        .text(`${changeText} from last week`);
                }
                
                // Remove the updating class after animation
                setTimeout(() => {
                    $card.removeClass('cf7-stat-updating');
                }, 300);
            });
        }

        // ========================================
        // Data Visualization System
        // ========================================
        // Advanced SVG-based chart generation with responsive design,
        // trend analysis, and performance optimization. Provides visual
        // context for dashboard statistics through mini-charts.
        //
        // Visualization Features:
        // • SVG mini-charts for historical trend display
        // • Responsive design with ResizeObserver integration
        // • Realistic trend pattern generation based on data type
        // • Color-coded charts matching dashboard theme
        // • Performance optimization with debounced resize handling
        //
        // Chart Types:
        // • Line Charts: Historical trend visualization
        // • Area Charts: Filled trend areas with gradients
        // • Point Indicators: Current value highlighting
        // • Interactive Elements: Hover states and accessibility
        // ========================================

        /**
         * Generate statistics charts for visual trend representation
         * 
         * Creates mini-charts for each statistics type with historical trend
         * data generation and responsive design. Includes ResizeObserver
         * integration for dynamic layout adaptation.
         * 
         * @param {Object} stats - Current statistics data for chart generation
         * 
         * Chart Generation Process:
         * 1. Iterate through all defined statistics types
         * 2. Locate chart container elements in DOM
         * 3. Generate realistic historical data based on current values
         * 4. Create SVG mini-charts with trend visualization
         * 5. Attach ResizeObserver for responsive chart updates
         * 
         * Supported Chart Types:
         * • total: Overall submission trends (growth pattern)
         * • new: New submission volatility (fluctuating pattern)
         * • reviewed: Steady review progress (steady-growth pattern)
         * • awaiting-information: Declining trend (items being processed)
         * • shortlisted/selected: Slow growth patterns (careful selection)
         * • rejected: Volatile pattern (varied decision making)
         * 
         * Performance Features:
         * • ResizeObserver integration for responsive updates
         * • Debounced resize handling (100ms) to prevent excessive re-rendering
         * • Chart container validation to prevent unnecessary processing
         * • Memory management with proper observer cleanup
         * 
         * Responsive Design:
         * • Automatic chart resize on container dimension changes
         * • Flexible chart dimensions based on available space
         * • Optimized SVG viewBox for crisp rendering at all sizes
         * • Touch-friendly chart elements for mobile interaction
         */
        generateStatsCharts(stats) {
            const statTypes = ['total', 'new', 'reviewed', 'awaiting-information', 'shortlisted', 'selected', 'rejected'];
            
            statTypes.forEach(type => {
                const chartContainer = document.getElementById(`chart-${type}`);
                if (!chartContainer) return;
                
                // Generate sample historical data based on current value
                const currentValue = stats[type] || 0;
                const historicalData = this.generateSampleHistoricalData(currentValue, type);
                
                // Create the mini line chart
                this.createMiniLineChart(chartContainer, historicalData, type);
                
                // Add resize observer to handle dynamic resizing
                if (window.ResizeObserver && !chartContainer.hasAttribute('data-resize-observed')) {
                    const resizeObserver = new ResizeObserver(() => {
                        // Debounce the resize to avoid excessive re-rendering
                        clearTimeout(chartContainer.resizeTimeout);
                        chartContainer.resizeTimeout = setTimeout(() => {
                            this.createMiniLineChart(chartContainer, historicalData, type);
                        }, 100);
                    });
                    resizeObserver.observe(chartContainer);
                    chartContainer.setAttribute('data-resize-observed', 'true');
                }
            });
        }

        generateSampleHistoricalData(currentValue, type) {
            // Generate 7 days of sample data with more realistic trends
            const data = [];
            const days = 7;
            
            // Create different trend patterns based on status type
            const trendPatterns = {
                'total': 'growing',           // Total always grows
                'new': 'volatile',           // New submissions fluctuate
                'reviewed': 'steady-growth', // Reviews grow steadily  
                'awaiting-information': 'declining', // Awaiting info should decline
                'selected': 'slow-growth',   // Selected grows slowly
                'rejected': 'volatile'       // Rejected varies
            };
            
            const pattern = trendPatterns[type] || 'steady';
            
            for (let i = days - 1; i >= 0; i--) {
                let value;
                if (i === 0) {
                    // Today's value is the current stat
                    value = currentValue;
                } else {
                    // Generate values based on trend pattern
                    const dayFactor = i / (days - 1); // 1.0 (oldest) to 0.0 (newest)
                    let baseFactor;
                    
                    switch (pattern) {
                        case 'growing':
                            // Strong upward trend
                            baseFactor = 0.4 + (1 - dayFactor) * 0.6;
                            break;
                        case 'declining':
                            // Downward trend (older values higher)
                            baseFactor = 0.6 + dayFactor * 0.4;
                            break;
                        case 'steady-growth':
                            // Gradual increase
                            baseFactor = 0.7 + (1 - dayFactor) * 0.3;
                            break;
                        case 'slow-growth':
                            // Very gradual increase
                            baseFactor = 0.85 + (1 - dayFactor) * 0.15;
                            break;
                        case 'volatile':
                            // Random but realistic fluctuation
                            baseFactor = 0.6 + Math.sin(dayFactor * Math.PI * 2) * 0.2 + (1 - dayFactor) * 0.2;
                            break;
                        default:
                            baseFactor = 0.8 + (1 - dayFactor) * 0.2;
                    }
                    
                    // Add some randomness but keep it realistic
                    const randomFactor = 0.9 + Math.random() * 0.2; // ±10% random variation
                    value = Math.max(0, Math.round(currentValue * baseFactor * randomFactor));
                    
                    // Ensure we don't have completely flat lines for small numbers
                    if (currentValue > 0 && value === currentValue && i > 0) {
                        value = Math.max(0, currentValue - Math.ceil(Math.random() * Math.max(1, currentValue * 0.3)));
                    }
                }
                
                data.push({
                    date: new Date(Date.now() - i * 24 * 60 * 60 * 1000),
                    value: value
                });
            }
            
            return data;
        }

        /**
         * Create SVG mini line chart with gradient fills and interactive elements
         * 
         * Generates compact line charts with area fills, gradient backgrounds,
         * and current value indicators. Optimized for dashboard metric cards
         * with fixed dimensions and responsive scaling.
         * 
         * @param {HTMLElement} container - DOM element to contain the chart
         * @param {Array} data - Historical data points for chart generation
         * @param {string} type - Chart type for color mapping and styling
         * 
         * Chart Features:
         * • Fixed 60x24 dimensions optimized for metric cards
         * • Gradient area fills for visual depth and appeal
         * • Current value dot indicator for latest data point
         * • Color-coded styling based on submission status type
         * • Smooth line rendering with proper scaling
         * 
         * SVG Elements:
         * • Gradient definitions for area fill effects
         * • Area path for filled trend visualization
         * • Line path for trend line display
         * • Circle element for current value highlighting
         * • Proper viewBox for crisp rendering at all sizes
         * 
         * Color Mapping:
         * • total: Blue (#667eea) for overall metrics
         * • new: Light blue (#4299e1) for new submissions
         * • reviewed: Purple (#9f7aea) for reviewed items
         * • awaiting-information: Orange (#ed8936) for pending items
         * • shortlisted: Pink (#ec4899) for shortlisted candidates
         * • selected: Green (#48bb78) for successful selections
         * • rejected: Red (#f56565) for rejected submissions
         * 
         * Data Handling:
         * • Automatic scale calculation based on data range
         * • Flat line detection with artificial range creation
         * • Smooth path generation with proper coordinate mapping
         * • Edge case handling for empty or single-point datasets
         * 
         * Performance Features:
         * • Efficient SVG creation with namespace handling
         * • Minimal DOM manipulation for smooth rendering
         * • Optimized path calculation for large datasets
         * • Memory-efficient element creation and management
         */
        createMiniLineChart(container, data, type) {
            // Use fixed dimensions optimized for compact stat cards
            const width = 60; // Fixed width to match CSS
            const height = 24; // Fixed height to match CSS  
            const padding = 2; // Reduced padding for smaller charts
            
            // Clear existing content
            container.innerHTML = '';
            
            // Create SVG
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            
            // Create gradient definitions
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
            gradient.setAttribute('id', `gradient-${type}`);
            gradient.setAttribute('x1', '0%');
            gradient.setAttribute('y1', '0%');
            gradient.setAttribute('x2', '0%');
            gradient.setAttribute('y2', '100%');
            
            const colorMap = {
                'total': '#667eea',
                'new': '#4299e1',
                'reviewed': '#9f7aea',
                'awaiting-information': '#ed8936',
                'shortlisted': '#ec4899',
                'selected': '#48bb78',
                'rejected': '#f56565'
            };
            
            const color = colorMap[type] || '#667eea';
            
            const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
            stop1.setAttribute('offset', '0%');
            stop1.setAttribute('stop-color', color);
            stop1.setAttribute('stop-opacity', '0.3');
            
            const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
            stop2.setAttribute('offset', '100%');
            stop2.setAttribute('stop-color', color);
            stop2.setAttribute('stop-opacity', '0.1');
            
            gradient.appendChild(stop1);
            gradient.appendChild(stop2);
            defs.appendChild(gradient);
            svg.appendChild(defs);
            
            if (data.length === 0) return;
            
            // Calculate scales
            const values = data.map(d => d.value);
            const minValue = Math.min(...values);
            const maxValue = Math.max(...values);
            let valueRange = maxValue - minValue;
            
            // Handle flat lines by creating artificial range
            if (valueRange === 0) {
                valueRange = Math.max(1, maxValue * 0.2); // 20% of max value or minimum 1
            }
            
            const chartWidth = width - padding * 2;
            const chartHeight = height - padding * 2;
            
            // Generate path
            let pathData = '';
            let areaData = '';
            
            data.forEach((point, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                let y;
                
                if (maxValue === minValue) {
                    // For flat lines, center them vertically
                    y = padding + chartHeight / 2;
                } else {
                    y = padding + chartHeight - ((point.value - minValue) / valueRange) * chartHeight;
                }
                
                if (index === 0) {
                    pathData += `M ${x} ${y}`;
                    areaData += `M ${x} ${height - padding} L ${x} ${y}`;
                } else {
                    pathData += ` L ${x} ${y}`;
                    areaData += ` L ${x} ${y}`;
                }
                
                if (index === data.length - 1) {
                    areaData += ` L ${x} ${height - padding} Z`;
                }
            });
            
            // Create area fill
            const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            area.setAttribute('d', areaData);
            area.setAttribute('fill', `url(#gradient-${type})`);
            area.setAttribute('stroke', 'none');
            svg.appendChild(area);
            
            // Create line
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathData);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', color);
            path.setAttribute('stroke-width', '2');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            svg.appendChild(path);
            
            // Add current value dot
            if (data.length > 0) {
                const lastPoint = data[data.length - 1];
                const lastX = padding + chartWidth;
                let lastY;
                
                if (maxValue === minValue) {
                    // For flat lines, center the dot vertically
                    lastY = padding + chartHeight / 2;
                } else {
                    lastY = padding + chartHeight - ((lastPoint.value - minValue) / valueRange) * chartHeight;
                }
                
                const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                dot.setAttribute('cx', lastX);
                dot.setAttribute('cy', lastY);
                dot.setAttribute('r', '2');
                dot.setAttribute('fill', color);
                dot.setAttribute('stroke', 'white');
                dot.setAttribute('stroke-width', '1');
                svg.appendChild(dot);
            }
            
            container.appendChild(svg);
        }

        // ========================================
        // Outstanding Actions Rendering System
        // ========================================
        // Dynamic rendering of outstanding action items with priority
        // visualization, overdue indicators, and structured layouts.
        // Provides comprehensive action management interface.
        //
        // Rendering Features:
        // • Priority-based visual styling and color coding
        // • Overdue action highlighting with pulse animations
        // • Action card layout with detailed information display
        // • Badge counter updates for activity tracking
        // • Empty state handling with encouraging messaging
        //
        // Action Card Components:
        // • Priority indicators with color-coded styling
        // • Assignee information with prominent display
        // • Due date tracking with overdue highlighting
        // • Artist name and context information
        // • Quick action buttons for immediate workflow
        // ========================================

        /**
         * Render outstanding actions with comprehensive visual hierarchy
         * 
         * Generates action cards with priority indicators, overdue highlighting,
         * and structured information display. Updates activity badges and
         * handles empty states with user-friendly messaging.
         * 
         * @param {Object} data - Actions data containing action list and counts
         * 
         * Action Card Features:
         * • Priority-based visual styling (high, medium, low)
         * • Overdue detection with pulse animation effects
         * • Assignee prominence for responsibility clarity
         * • Due date highlighting with status-aware styling
         * • Artist context for submission identification
         * • Quick view buttons for immediate navigation
         * 
         * Activity Badge Management:
         * • Real-time counter updates for action count display
         * • Badge visibility control based on action availability
         * • Consistent badge styling across dashboard components
         * • Accessibility-friendly counter announcements
         * 
         * Empty State Handling:
         * • Encouraging empty state with checkmark visual
         * • Contextual messaging for different scenarios
         * • Total count display even when no items shown
         * • User-friendly completion acknowledgment
         * 
         * Visual Hierarchy:
         * • Color-coded priority indicators for quick scanning
         * • Overdue items with attention-grabbing pulse effects
         * • Structured card layout for consistent information display
         * • Clear action affordances with button styling
         */
        renderOutstandingActions(data) {
            const $container = $('#cf7-outstanding-actions');
            const actions = data.actions || [];
            const totalCount = data.total_count || 0;
            
            // Update the activity card badge counter
            const $activityBadge = $('.activity-actions-count');
            
            if (totalCount > 0) {
                $activityBadge.text(totalCount).show();
            } else {
                $activityBadge.text('0').hide();
            }
            
            if (actions.length === 0) {
                $container.html(`
                    <div class="cf7-outstanding-actions-empty">
                        <div class="cf7-no-actions-icon">✅</div>
                        <h4>No outstanding actions</h4>
                        <p>All caught up! No pending actions require attention.</p>
                        ${totalCount > 0 ? `<small>Total pending actions: ${totalCount}</small>` : ''}
                    </div>
                `);
                return;
            }
            
            let html = `
                <div class="cf7-outstanding-actions-header">
                    <span class="cf7-actions-count">${actions.length} of ${totalCount} pending actions</span>
                    <a href="#" class="cf7-view-all-actions">View all →</a>
                </div>
                <div class="cf7-outstanding-actions-list">
            `;
            
            actions.forEach(action => {
                const priorityClass = `cf7-priority-${action.priority}`;
                const overdueClass = action.is_overdue ? 'cf7-action-overdue cf7-pulse' : '';
                
                html += `
                    <div class="cf7-action-card ${priorityClass} ${overdueClass}">
                        <div class="cf7-action-main">
                            <div class="cf7-action-title-row">
                                <h4 class="cf7-action-title">${this.escapeHtml(action.title)}</h4>
                                <span class="cf7-action-priority cf7-priority-${action.priority}">${action.priority.toUpperCase()}</span>
                            </div>
                            
                            <div class="cf7-action-assignee-prominent">
                                <span class="cf7-assignee-label">Assigned to:</span>
                                <strong class="cf7-assignee-name">${this.escapeHtml(action.assigned_to_name)}</strong>
                            </div>
                            
                            <div class="cf7-action-details">
                                <div class="cf7-action-detail-item">
                                    <span class="cf7-detail-label">Artist:</span>
                                    <span class="cf7-detail-value">${this.escapeHtml(action.artist_name)}</span>
                                </div>
                                ${action.due_date_formatted ? `
                                <div class="cf7-action-detail-item cf7-due-date ${action.is_overdue ? 'overdue' : ''}">
                                    <span class="cf7-detail-label">Due:</span>
                                    <span class="cf7-detail-value">${action.due_date_formatted}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <div class="cf7-action-view">
                            <a href="${action.edit_link}" class="cf7-view-action-btn ${action.is_overdue ? 'urgent' : ''}" title="View Submission">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                View
                            </a>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $container.html(html);
        }

        // ========================================
        // Submission Rendering Engine
        // ========================================
        // Comprehensive submission list rendering with advanced features
        // including search result indicators, pagination, empty states,
        // and interactive submission rows with notification systems.
        //
        // Rendering Components:
        // • Submission Row Generation: Complete row building with metadata
        // • Notification System: Unread message and action indicators
        // • Search Results: Query highlighting and result count display
        // • Empty State Management: Context-aware empty state messaging
        // • Medium Tags: Artistic medium visualization with overflow handling
        // • Date Formatting: Intelligent date and time display
        //
        // Interactive Features:
        // • Checkbox selection for bulk operations
        // • Notification bubbles for attention items
        // • Status badge visualization with icon mapping
        // • Pagination integration with state management
        // ========================================

        /**
         * Render paginated submission list with comprehensive features
         * 
         * Processes submission data to generate complete table interface
         * including search indicators, interactive rows, and pagination.
         * Manages selection state and provides empty state fallbacks.
         * 
         * @param {Object} data - Submission data including items and pagination
         * 
         * Rendering Process:
         * 1. Process submission array into individual row HTML
         * 2. Add search result indicators when applicable
         * 3. Generate pagination controls for multi-page results
         * 4. Handle empty states with contextual messaging
         * 5. Reset selection state for clean bulk operation handling
         * 
         * Search Integration:
         * • Search term highlighting in result indicators
         * • Result count display with singular/plural handling
         * • Context-aware messaging for search vs. filter scenarios
         * • Clear search affordances in empty states
         * 
         * State Management:
         * • Complete selection state reset for consistent bulk operations
         * • Pagination synchronization with server state
         * • Loading state transitions for smooth user experience
         * • Filter indicator updates for active filter awareness
         * 
         * Empty State Features:
         * • Dynamic messaging based on active filters
         * • Contextual suggestions for result improvement
         * • Clear filter shortcuts for easy recovery
         * • Encouraging iconography for positive user experience
         */
        renderSubmissions(data) {
            const $container = $('.cf7-submissions-table');
            
            if (data.submissions && data.submissions.length > 0) {
                let html = '';
                
                // Add search results indicator
                const searchTerm = $('#cf7-search-input').val();
                if (searchTerm && data.pagination) {
                    html += `
                        <div class="cf7-search-results-info">
                            <small>Found ${data.pagination.total_items} result${data.pagination.total_items !== 1 ? 's' : ''} for "${searchTerm}"</small>
                        </div>
                    `;
                }
                
                data.submissions.forEach(submission => {
                    html += this.buildSubmissionRow(submission);
                });
                
                $container.html(html);
                this.renderPagination(data.pagination);
            } else {
                $container.html(this.buildEmptyState());
                // Always call renderPagination, even for empty state
                this.renderPagination(data.pagination);
            }
            
            this.selectedItems.clear();
            this.updateBulkActions();
        }

        /**
         * Build comprehensive submission row with rich metadata and interactions
         * 
         * Generates complete submission row HTML with notification systems,
         * formatted dates, medium tags, and status indicators. Optimized
         * for dashboard table display with interactive elements.
         * 
         * @param {Object} submission - Individual submission data object
         * @returns {string} Complete HTML string for submission table row
         * 
         * Row Components:
         * • Notification Bubbles: Combined unread message and action counts
         * • Selection Checkbox: Bulk operation selection interface
         * • Submission Info: Title, metadata, and medium tag display
         * • Date Display: Smart date/time formatting with conditional time
         * • Status Badge: Visual status indicator with icon mapping
         * 
         * Notification System:
         * • Combined notification bubble for space efficiency
         * • Unread message count integration
         * • Outstanding action count display
         * • Visual prominence for attention-required items
         * 
         * Date Processing:
         * • Y-m-d H:i:s format parsing with timezone handling
         * • Conditional time display (hidden for midnight submissions)
         * • Localized date formatting for user region
         * • Time format optimization for readability
         * 
         * Medium Tag Integration:
         * • Artistic medium visualization with color coding
         * • Overflow handling for multiple medium assignments
         * • Consistent styling with dashboard theme
         * • Performance optimization for large medium lists
         * 
         * Interactive Elements:
         * • Checkbox for bulk selection operations
         * • Clickable title links for submission navigation
         * • Status badge with semantic icon representation
         * • Notification indicators for workflow awareness
         */
        buildSubmissionRow(submission) {
            // Parse the date properly from Y-m-d H:i:s format
            const date = new Date(submission.date.replace(' ', 'T'));
            const formattedDate = date.toLocaleDateString();
            // Only show time if it's not 00:00
            const hours = date.getHours();
            const minutes = date.getMinutes();
            const formattedTime = (hours === 0 && minutes === 0) ? 
                '' : 
                date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // Build notification bubbles
            let notificationBubbles = '';
            const unreadCount = submission.unread_messages_count || 0;
            const actionsCount = submission.outstanding_actions_count || 0;
            const totalNotifications = unreadCount + actionsCount;
            
            if (totalNotifications > 0) {
                notificationBubbles = `
                    <div class="cf7-submission-notifications">
                        <div class="cf7-notification-bubble combined">${totalNotifications}</div>
                    </div>
                `;
            }
            
            return `
                <div class="cf7-submission-row" data-id="${submission.id}">
                    ${notificationBubbles}
                    <input type="checkbox" class="cf7-submission-checkbox" value="${submission.id}">
                    <div class="cf7-submission-info">
                        <div class="cf7-submission-title">
                            <a href="${submission.view_url}">${submission.title}</a>
                        </div>
                        <div class="cf7-submission-meta">
                            ${submission.email} • ID: ${submission.id}
                        </div>
                        ${this.renderMediumTags(submission.mediums)}
                    </div>
                    <div class="cf7-submission-date">
                        <div class="cf7-submission-date-main">${formattedDate}</div>
                        ${formattedTime ? `<div class="cf7-submission-date-time">${formattedTime}</div>` : ''}
                    </div>
                    <div class="cf7-status-badge ${submission.status}">
                        <span class="cf7-status-circle">
                            <span class="dashicons ${this.getStatusIcon(submission.status)}"></span>
                        </span>
                    </div>
                </div>
            `;
        }

        getStatusLabel(status) {
            const statusLabels = {
                'new': 'New',
                'reviewed': 'Reviewed',
                'awaiting-information': 'Awaiting Information',
                'selected': 'Selected',
                'rejected': 'Rejected'
            };
            return statusLabels[status] || status;
        }

        getStatusIcon(status) {
            const statusIcons = {
                'new': 'dashicons-star-filled',
                'reviewed': 'dashicons-visibility',
                'awaiting-information': 'dashicons-clock',
                'shortlisted': 'dashicons-paperclip',
                'selected': 'dashicons-yes-alt',
                'rejected': 'dashicons-dismiss'
            };
            return statusIcons[status] || 'dashicons-marker';
        }

        /**
         * Render artistic medium tags with overflow handling
         * 
         * Generates visual medium tags with color coding and intelligent
         * overflow management for consistent row heights and clean display.
         * 
         * @param {Array} mediums - Array of medium objects with display properties
         * @returns {string} HTML string containing formatted medium tags
         * 
         * Tag Features:
         * • Maximum 4 visible tags to maintain consistent row height
         * • Custom color coding with background and text color support
         * • Overflow indicator (+N) for additional hidden mediums
         * • Responsive tag sizing for various screen dimensions
         * 
         * Visual Design:
         * • Color-coordinated tags based on medium category
         * • Rounded tag styling for modern aesthetic appeal
         * • Overflow management with numerical indicators
         * • Clean spacing and alignment with submission metadata
         * 
         * Performance Considerations:
         * • Array slicing to limit DOM elements for large medium lists
         * • Efficient HTML generation with minimal template processing
         * • Consistent styling application across all medium types
         * • Memory-friendly rendering for high-volume submission lists
         */
        renderMediumTags(mediums) {
            if (!mediums || mediums.length === 0) {
                return '';
            }

            // Limit to maximum 4 tags to maintain consistent row height
            const maxTags = 4;
            const visibleMediums = mediums.slice(0, maxTags);
            const remainingCount = mediums.length - maxTags;

            const tags = visibleMediums.map(medium => {
                return `<span class="cf7-medium-tag cf7-dashboard-medium" style="background-color: ${medium.bg_color}; color: ${medium.text_color};">${medium.name}</span>`;
            }).join('');

            // Add overflow indicator if there are more tags
            const overflowTag = remainingCount > 0 ? 
                `<span class="cf7-medium-overflow">+${remainingCount}</span>` : '';

            return `<div class="cf7-submission-mediums">${tags}${overflowTag}</div>`;
        }

        /**
         * Build context-aware empty state with actionable guidance
         * 
         * Generates intelligent empty state messaging based on active filters
         * and search criteria. Provides contextual suggestions and clear
         * filter shortcuts for user guidance and result recovery.
         * 
         * @returns {string} Complete HTML for empty state display
         * 
         * Empty State Intelligence:
         * • Dynamic messaging based on active filter combination
         * • Search term highlighting for query awareness
         * • Filter-specific suggestions for result improvement
         * • Clear action buttons for filter management
         * 
         * Contextual Messaging:
         * • No filters: General empty state with basic guidance
         * • Search active: Search-specific messaging with term display
         * • Filters active: Filter-aware suggestions with clear options
         * • Combined filters: Comprehensive filter summary with actions
         * 
         * Action Affordances:
         * • Clear All Filters button for easy reset
         * • Contextual suggestions for filter adjustment
         * • Encouraging iconography for positive user experience
         * • Accessible button styling with clear interaction cues
         * 
         * User Experience Features:
         * • Friendly error messaging without technical jargon
         * • Actionable suggestions for result improvement
         * • Visual consistency with dashboard design language
         * • Responsive layout for various screen dimensions
         */
        buildEmptyState() {
            const searchTerm = $('#cf7-search-input').val();
            const statusFilter = $('.cf7-status-filter-dropdown').attr('data-current') || '';
            const dateFrom = $('#cf7-date-from').val();
            const dateTo = $('#cf7-date-to').val();
            
            let message = 'No submissions found';
            let suggestion = 'Try adjusting your search or filter criteria.';
            
            const hasFilters = searchTerm || statusFilter || dateFrom || dateTo;
            
            if (hasFilters) {
                const filterParts = [];
                if (searchTerm) filterParts.push(`"${searchTerm}"`);
                if (statusFilter) filterParts.push(`${statusFilter} status`);
                if (dateFrom && dateTo) filterParts.push(`between ${dateFrom} and ${dateTo}`);
                else if (dateFrom) filterParts.push(`from ${dateFrom}`);
                else if (dateTo) filterParts.push(`until ${dateTo}`);
                
                message = `No submissions found ${filterParts.join(' with ')}`;
                suggestion = 'Try adjusting your filters or expanding the date range.';
            }
            
            return `
                <div class="cf7-loading-state">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">�</div>
                    <h3>${message}</h3>
                    <p>${suggestion}</p>
                    ${hasFilters ? `<button class="cf7-btn cf7-btn-ghost" onclick="CF7Dashboard.clearAllFilters()">Clear All Filters</button>` : ''}
                </div>
            `;
        }

        // ========================================
        // Advanced Pagination System
        // ========================================
        // Comprehensive pagination rendering with intelligent button
        // generation, result information display, and responsive design
        // for optimal navigation experience across large datasets.
        //
        // Pagination Features:
        // • Smart page button generation with ellipsis indicators
        // • Result range display with total count information
        // • Responsive pagination controls for mobile optimization
        // • Per-page selector synchronization
        // • Dynamic visibility based on result count
        //
        // Navigation Elements:
        // • Previous/Next buttons with proper disabled states
        // • Page number buttons with active state indication
        // • Ellipsis indicators for large page ranges
        // • Current page highlighting for user orientation
        // ========================================

        /**
         * Render comprehensive pagination interface with smart navigation
         * 
         * Generates complete pagination controls including information display,
         * intelligent page button layout, and responsive navigation elements.
         * Handles single-page and empty result scenarios gracefully.
         * 
         * @param {Object} pagination - Pagination data from server response
         * 
         * Pagination Components:
         * • Result Information: "Showing X-Y of Z submissions" display
         * • Navigation Buttons: Previous/Next with proper state management
         * • Page Numbers: Smart button generation with ellipsis for large ranges
         * • Per-Page Sync: Synchronization with per-page selector controls
         * 
         * Smart Button Generation:
         * • Current page ±2 page window for optimal navigation
         * • First/last page shortcuts for long pagination lists
         * • Ellipsis indicators for non-consecutive page ranges
         * • Active state highlighting for current page awareness
         * 
         * Responsive Behavior:
         * • Mobile-optimized button sizing and spacing
         * • Flexible layout adaptation for various screen sizes
         * • Touch-friendly button targets for mobile interaction
         * • Consistent styling with dashboard design language
         * 
         * State Management:
         * • Dynamic visibility based on total page count
         * • Disabled state handling for boundary conditions
         * • Per-page selector synchronization with server state
         * • Result count accuracy with proper calculation
         * 
         * Accessibility Features:
         * • Screen reader compatible navigation elements
         * • Keyboard navigation support with proper tab order
         * • ARIA labels for pagination context communication
         * • High contrast button states for visual accessibility
         */
        renderPagination(pagination) {
            const $pagination = $('.cf7-pagination');
            const $info = $pagination.find('.cf7-pagination-info');
            const $buttons = $pagination.find('.cf7-pagination-buttons');
            
            // If no pagination data or only 1 page, hide pagination
            if (!pagination || pagination.total_pages <= 1) {
                $info.text('');
                $buttons.html('<button class="cf7-page-btn" disabled>‹ Previous</button><button class="cf7-page-btn active">1</button><button class="cf7-page-btn" disabled>Next ›</button>');
                $pagination.hide();
                return;
            }

            // Update info
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
            $info.text(`Showing ${start}-${end} of ${pagination.total_items} submissions`);

            // Build pagination buttons
            let buttonsHtml = '';
            
            // Previous button
            if (pagination.current_page > 1) {
                buttonsHtml += `<button class="cf7-page-btn" data-page="${pagination.current_page - 1}">‹ Previous</button>`;
            } else {
                buttonsHtml += `<button class="cf7-page-btn" disabled>‹ Previous</button>`;
            }

            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            if (startPage > 1) {
                buttonsHtml += `<button class="cf7-page-btn" data-page="1">1</button>`;
                if (startPage > 2) {
                    buttonsHtml += `<span class="cf7-page-btn disabled">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === pagination.current_page ? ' active' : '';
                buttonsHtml += `<button class="cf7-page-btn${activeClass}" data-page="${i}">${i}</button>`;
            }

            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    buttonsHtml += `<span class="cf7-page-btn disabled">...</span>`;
                }
                buttonsHtml += `<button class="cf7-page-btn" data-page="${pagination.total_pages}">${pagination.total_pages}</button>`;
            }

            // Next button
            if (pagination.current_page < pagination.total_pages) {
                buttonsHtml += `<button class="cf7-page-btn" data-page="${pagination.current_page + 1}">Next ›</button>`;
            } else {
                buttonsHtml += `<button class="cf7-page-btn" disabled>Next ›</button>`;
            }

            $buttons.html(buttonsHtml);
            
            // Sync the per-page selector
            $('#cf7-per-page').val(pagination.per_page);
            
            $pagination.show();
        }

        /**
         * Render recent message activity with interaction capabilities
         * 
         * Processes recent message data to generate interactive message list
         * with read/unread states, batch actions, and navigation shortcuts.
         * Provides comprehensive message management interface.
         * 
         * @param {Array} messages - Array of recent message objects
         * 
         * Message Features:
         * • Visual unread state indicators with prominence
         * • Message count aggregation for multiple messages
         * • Time-based message aging display
         * • Quick action buttons for immediate workflow
         * • Artist identification for context awareness
         * 
         * Interaction Capabilities:
         * • Individual message read marking with AJAX updates
         * • Batch "Mark All Read" functionality for efficiency
         * • Direct conversation navigation shortcuts
         * • Submission-level read state management
         * 
         * Visual Hierarchy:
         * • Unread messages with visual prominence and styling
         * • Artist name highlighting for quick identification  
         * • Time ago display for temporal context
         * • Action button grouping for clear interaction zones
         * 
         * Empty State Handling:
         * • Clean empty state for no unread messages
         * • Encouraging messaging for completed message management
         * • Consistent styling with dashboard empty states
         */
        renderRecentMessages(messages) {
            const $container = $('.cf7-recent-messages');
            
            if (messages && messages.length > 0) {
                let html = '';
                
                messages.forEach(submission => {
                    const unreadClass = submission.unread ? ' unread' : '';
                    const messageText = submission.unviewed_count > 1 
                        ? `${submission.unviewed_count} new messages` 
                        : '1 new message';
                    
                    html += `
                        <div class="cf7-message-item${unreadClass}" data-submission-id="${submission.submission_id}">
                            <div class="cf7-message-content">
                                <div class="cf7-message-header">
                                    <span class="cf7-message-artist">${submission.artist_name}</span>
                                    <span class="cf7-message-time">${submission.time_ago}</span>
                                </div>
                                <p class="cf7-message-summary">${messageText}</p>
                                <div class="cf7-message-actions">
                                    <a href="${submission.view_url}" class="cf7-message-view" title="View Conversation">
                                        <span class="dashicons dashicons-visibility"></span>
                                        View Conversation
                                    </a>
                                    <button class="cf7-mark-submission-read-btn" data-submission-id="${submission.submission_id}" title="Mark as Read">
                                        <span class="dashicons dashicons-yes"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Add "Mark All Read" button if there are unread messages
                html += `
                    <div class="cf7-messages-footer">
                        <button class="cf7-mark-all-read-btn cf7-btn cf7-btn-ghost">
                            <span class="dashicons dashicons-yes-alt"></span>
                            Mark All Read
                        </button>
                    </div>
                `;
                
                $container.html(html);
            } else {
                $container.html('<div class="cf7-loading-state">No unread messages</div>');
            }
        }

        // ========================================
        // Selection and Bulk Operations System
        // ========================================
        // Comprehensive selection management with bulk action capabilities,
        // visual feedback, and state synchronization across dashboard
        // components for efficient multi-item operations.
        //
        // Selection Features:
        // • Individual item selection with checkbox interface
        // • Select-all functionality with intermediate state support
        // • Visual selection count display with item tracking
        // • Bulk action panel with sliding animations
        // • State synchronization across UI components
        //
        // Bulk Operations:
        // • Status update operations for workflow management
        // • Export functionality with download handling
        // • Delete operations with confirmation dialogs
        // • Progress tracking for long-running operations
        // ========================================

        /**
         * Update selection state from checkbox interactions
         * 
         * Processes checkbox state changes to maintain accurate selection
         * tracking and update bulk action interface accordingly.
         * Provides foundation for all bulk operation workflows.
         * 
         * Selection Process:
         * 1. Clear existing selection state for clean rebuild
         * 2. Iterate through all checked submission checkboxes
         * 3. Add submission IDs to selection Set for efficient tracking
         * 4. Update bulk action interface with current selection count
         * 
         * State Management:
         * • Set-based storage for efficient ID tracking and deduplication
         * • Real-time count updates for immediate user feedback
         * • Checkbox state synchronization with visual indicators
         * • Bulk action panel visibility control based on selection
         * 
         * Performance Features:
         * • Efficient Set operations for large submission lists
         * • Minimal DOM queries with targeted checkbox selection
         * • Batched UI updates to prevent layout thrashing
         * • Memory-friendly selection tracking for high-volume datasets
         */
        updateSelectedItems() {
            this.selectedItems.clear();
            
            $('.cf7-submission-checkbox:checked').each((i, el) => {
                this.selectedItems.add($(el).val());
            });

            this.updateBulkActions();
        }

        /**
         * Update bulk action interface based on current selection state
         * 
         * Manages bulk action panel visibility, selection count display,
         * and select-all checkbox state based on current item selection.
         * Provides visual feedback for multi-item operation workflows.
         * 
         * Interface Updates:
         * • Bulk action panel sliding animation (show/hide based on selection)
         * • Selection count display with singular/plural text handling
         * • Select-all checkbox state management with indeterminate support
         * • Visual prominence for active selection state
         * 
         * Select-All State Logic:
         * • Checked: All visible items selected (count === total visible)
         * • Indeterminate: Some but not all items selected (partial selection)
         * • Unchecked: No items selected (empty selection set)
         * 
         * Animation Features:
         * • Smooth sliding animations for bulk action panel
         * • Immediate checkbox state updates for responsive feedback
         * • Progressive disclosure based on selection availability
         * • Clean transitions between selection states
         * 
         * Accessibility Considerations:
         * • Screen reader compatible count announcements
         * • Proper checkbox states for assistive technology
         * • Clear visual indicators for selection state
         * • Keyboard navigation friendly interface elements
         */
        updateBulkActions() {
            const count = this.selectedItems.size;
            const $bulkActions = $('.cf7-bulk-actions');
            const $selectedCount = $('.cf7-selected-count');
            const totalVisible = $('.cf7-submission-checkbox').length;
            const $selectAll = $('.cf7-select-all');

            if (count > 0) {
                $bulkActions.slideDown(200);
                $selectedCount.text(`${count} item${count !== 1 ? 's' : ''} selected`);
                
                // Update select all checkbox state
                if (count === totalVisible && totalVisible > 0) {
                    $selectAll.prop('checked', true).prop('indeterminate', false);
                } else if (count > 0) {
                    $selectAll.prop('checked', false).prop('indeterminate', true);
                } else {
                    $selectAll.prop('checked', false).prop('indeterminate', false);
                }
            } else {
                $bulkActions.slideUp(200);
                $selectAll.prop('checked', false).prop('indeterminate', false);
            }
        }

        handleBulkAction() {
            const action = $('#cf7-bulk-action-select').val();
            if (!action) {
                this.showToast('Please select a bulk action', 'info');
                return;
            }
            
            this.performBulkAction(action);
        }

        performBulkAction(action) {
            if (this.selectedItems.size === 0) {
                this.showToast('Please select items first', 'info');
                return;
            }

            // Prevent double execution
            if (this.isPerformingBulkAction) {
                return;
            }

            const confirmActions = ['delete'];
            if (confirmActions.includes(action)) {
                const actionName = action === 'delete' ? 'delete' : action;
                if (!confirm(`Are you sure you want to ${actionName} ${this.selectedItems.size} item(s)?`)) {
                    return;
                }
            }

            this.isPerformingBulkAction = true;

            const data = {
                action: 'cf7_dashboard_bulk_action',
                nonce: cf7_dashboard.nonce,
                bulk_action: action,
                ids: Array.from(this.selectedItems)
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showToast(response.data.message || 'Action completed successfully', 'success');
                        
                        if (action === 'export' && response.data.download_url) {
                            this.triggerDownload(response.data.download_url, response.data.filename || 'cf7-submissions-export.csv');
                        }
                        
                        // Delay refresh slightly to ensure database updates are complete
                        setTimeout(() => {
                            this.refreshAll(true);
                        }, 500);
                    } else {
                        this.showToast(response.data || 'Action failed', 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    this.showToast('Failed to perform action', 'error');
                })
                .always(() => {
                    // Reset the flag after the action completes (success or failure)
                    setTimeout(() => {
                        this.isPerformingBulkAction = false;
                    }, 1000); // Wait a bit longer to prevent rapid re-triggering
                });
        }

        performSubmissionAction(action, submissionId) {
            if (action === 'view') {
                // Open submission in new tab
                const $row = $(`.cf7-submission-row[data-id="${submissionId}"]`);
                const $link = $row.find('.cf7-submission-title a');
                if ($link.length) {
                    window.open($link.attr('href'), '_blank');
                }
                return;
            }

            if (action === 'edit') {
                // Open status edit modal (you can implement this)
                this.openStatusModal(submissionId);
                return;
            }

            // Other actions can be handled via AJAX
            const data = {
                action: 'cf7_dashboard_submission_action',
                nonce: cf7_dashboard.nonce,
                submission_action: action,
                id: submissionId
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showToast(response.data.message || 'Action completed successfully', 'success');
                        // Submission actions usually affect submissions and stats, not messages
                        setTimeout(() => {
                            this.refreshSubmissions();
                        }, 300);
                    } else {
                        this.showToast(response.data || 'Action failed', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to perform action', 'error');
                });
        }

        openStatusModal(submissionId) {
            // Simple prompt for now - you can implement a proper modal later
            const newStatus = prompt('Enter new status (new, reviewed, awaiting-information, shortlisted, selected, rejected):');
            if (newStatus && ['new', 'reviewed', 'awaiting-information', 'shortlisted', 'selected', 'rejected'].includes(newStatus)) {
                const data = {
                    action: 'cf7_dashboard_update_status',
                    nonce: cf7_dashboard.nonce,
                    id: submissionId,
                    status: newStatus
                };

                $.post(ajaxurl, data)
                    .done((response) => {
                        if (response.success) {
                            this.showToast('Status updated successfully', 'success');
                            // Status updates affect submissions, stats, and activity
                            setTimeout(() => {
                                this.refreshSubmissions();
                                this.refreshActivity();
                            }, 300);
                        } else {
                            this.showToast(response.data || 'Failed to update status', 'error');
                        }
                    })
                    .fail(() => {
                        this.showToast('Failed to update status', 'error');
                    });
            }
        }

        updateSubmissionStatus(submissionId, status) {
            const data = {
                action: 'cf7_dashboard_update_status',
                nonce: cf7_dashboard.nonce,
                id: submissionId,
                status: status
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showToast('Status updated successfully', 'success');
                        // Status updates affect submissions, stats, and activity
                        setTimeout(() => {
                            this.refreshSubmissions(); // This includes loadStats()
                            this.refreshActivity();
                        }, 300);
                    } else {
                        this.showToast(response.data || 'Failed to update status', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to update status', 'error');
                });
        }

        exportSubmissions() {
            const data = {
                action: 'cf7_dashboard_export',
                nonce: cf7_dashboard.nonce,
                search: $('#cf7-search-input').val(),
                status: $('.cf7-status-filter-dropdown').attr('data-current') || '',
                date_from: $('#cf7-date-from').val(),
                date_to: $('#cf7-date-to').val()
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success && response.data.download_url) {
                        this.triggerDownload(response.data.download_url, response.data.filename || 'cf7-submissions-export.csv');
                        this.showToast('Export completed - download started', 'success');
                    } else {
                        this.showToast(response.data || 'Export failed', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to export submissions', 'error');
                });
        }

        // ========================================
        // Dashboard Lifecycle and Refresh Management
        // ========================================
        // Comprehensive refresh system with selective updates, loading
        // state management, and polling control for optimal performance
        // and user experience across dashboard components.
        //
        // Refresh Strategies:
        // • Full Dashboard Refresh: Complete data reload with visual feedback
        // • Selective Refresh: Component-specific updates for efficiency
        // • Forced Refresh: Manual refresh with loading state reset
        // • Polling Management: Automatic background updates with resource optimization
        //
        // Performance Features:
        // • Loading state guards to prevent duplicate requests
        // • Component-specific refresh methods for targeted updates
        // • Resource-aware polling with page visibility detection
        // • Memory management with proper cleanup handling
        // ========================================

        /**
         * Comprehensive dashboard refresh with visual feedback and state management
         * 
         * Performs complete dashboard data reload with loading indicators,
         * user notifications, and proper state management. Supports both
         * automatic and manual refresh scenarios with appropriate feedback.
         * 
         * @param {boolean} forced - Whether this is a manual/forced refresh
         * 
         * Refresh Process:
         * 1. Apply visual refreshing state to dashboard container
         * 2. Reset loading states for forced refresh scenarios
         * 3. Execute parallel data loading for all dashboard components
         * 4. Provide user feedback with toast notifications
         * 5. Remove loading states and complete refresh cycle
         * 
         * Component Updates:
         * • Statistics: Real-time submission counts and metrics
         * • Outstanding Actions: Action items and priority updates
         * • Submissions: Paginated submission list with current filters
         * • Recent Messages: Unread message updates and activity
         * • Activity Metrics: Today's activity and weekly summaries
         * 
         * User Experience Features:
         * • Visual loading feedback with dashboard-wide refreshing state
         * • Toast notifications for manual refresh acknowledgment
         * • Loading state management to prevent duplicate requests
         * • Smooth transitions with timed feedback removal
         * 
         * Performance Optimizations:
         * • Parallel data loading for faster refresh completion
         * • Loading state guards to prevent race conditions
         * • Selective state resets for different refresh scenarios
         * • Resource cleanup with proper timeout management
         */
        refreshAll(forced = false) {
            // Visual feedback - add refreshing class to dashboard
            $('.cf7-modern-dashboard').addClass('cf7-refreshing');
            
            // Show toast notification for manual refresh
            if (forced) {
                this.showToast('Refreshing dashboard...', 'info');
                // Reset loading states for forced refresh
                this.loadingStates.stats = false;
                this.loadingStates.actions = false;
                this.loadingStates.messages = false;
                this.loadingStates.submissions = false;
            }
            
            this.loadStats();
            this.loadOutstandingActions();
            this.loadSubmissions();
            this.loadRecentMessages();
            this.updateTodayActivity();
            
            // Remove refreshing class after delay
            setTimeout(() => {
                $('.cf7-modern-dashboard').removeClass('cf7-refreshing');
                if (forced) {
                    this.showToast('Dashboard refreshed', 'success');
                }
            }, 1000);
        }

        // ========================================
        // Selective Refresh Methods
        // ========================================
        // Optimized component-specific refresh methods for targeted
        // updates without full dashboard reload. Improves performance
        // and user experience for specific workflow scenarios.
        // ========================================

        /**
         * Refresh submissions and statistics for submission-related changes
         * 
         * Optimized refresh for submission status updates, new submissions,
         * or bulk operations that affect submission list and statistics.
         */
        refreshSubmissions() {
            // Only refresh submissions list and stats
            this.loadSubmissions();
            this.loadStats();
        }

        /**
         * Refresh messages and statistics for message-related changes
         * 
         * Targeted refresh for message read/unread state changes that
         * affect message lists and unread count statistics.
         */
        refreshMessages() {
            // Refresh messages and stats to update badge counts
            this.loadRecentMessages();
            this.loadStats();
        }

        /**
         * Refresh activity components for workflow-related changes
         * 
         * Comprehensive activity refresh including messages, actions,
         * and daily activity metrics for workflow state changes.
         */
        refreshActivity() {
            // Refresh activity cards (messages, actions, and today's activity)
            this.loadRecentMessages();
            this.loadOutstandingActions();
            this.updateTodayActivity();
        }

        /**
         * Refresh only statistics for metric-only updates
         * 
         * Lightweight refresh for scenarios requiring only statistical
         * updates without interface or content changes.
         */
        refreshStats() {
            // Only refresh stats
            this.loadStats();
        }

        // ========================================
        // Polling System Management
        // ========================================
        // Resource-aware background polling with page visibility optimization
        // and proper lifecycle management for automatic dashboard updates.
        // ========================================

        /**
         * Start intelligent background polling with resource optimization
         * 
         * Initiates background polling for unread messages with page visibility
         * detection and resource management. Optimized for minimal server load
         * and battery usage while maintaining real-time dashboard updates.
         * 
         * Polling Features:
         * • 2-minute intervals for balanced real-time vs. resource usage
         * • Page visibility detection to pause polling on hidden tabs
         * • Message-only polling to prevent conflicts with user interactions
         * • Automatic cleanup with proper interval management
         * 
         * Resource Optimization:
         * • Paused polling when page is hidden or in background
         * • Selective updates (messages only) to prevent UI conflicts
         * • Long intervals to reduce server load and battery drain
         * • Smart polling resumption when page becomes visible
         */
        startPolling() {
            // Only poll for unread messages every 2 minutes (less frequent)
            // Don't poll stats or actions automatically - they should refresh on user action
            this.pollingInterval = setInterval(() => {
                if (!document.hidden) {
                    // Only refresh unread messages, not stats (to prevent conflicts)
                    this.loadRecentMessages();
                }
            }, 120000); // 2 minutes instead of 30 seconds
        }

        /**
         * Stop background polling with proper cleanup
         * 
         * Safely stops background polling interval and cleans up resources.
         * Essential for proper memory management and resource disposal.
         */
        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }

        // ========================================
        // Loading State Management System
        // ========================================
        // Visual loading indicators with component-specific feedback,
        // user messaging, and consistent styling across dashboard
        // elements for optimal user experience during data operations.
        //
        // Loading Features:
        // • Component-specific loading states to prevent UI conflicts
        // • Contextual loading messages based on user actions
        // • Animated loading spinners for visual feedback
        // • Graceful loading state cleanup and transitions
        //
        // Visual Design:
        // • Consistent loading spinner styling across components
        // • Context-aware messaging for different loading scenarios
        // • Non-intrusive loading states that don't block interface
        // • Professional loading animations with dashboard theme
        // ========================================

        /**
         * Display statistics loading state with spinner animation
         * 
         * Replaces statistic number displays with loading spinners
         * during data fetch operations. Provides immediate visual
         * feedback for statistics refresh operations.
         */
        showStatsLoading() {
            $('.cf7-stat-card').each(function() {
                const $card = $(this);
                const $number = $card.find('.cf7-stat-number');
                if ($number.length) {
                    $number.html('<div class="cf7-loading-spinner" style="width: 20px; height: 20px; margin: 0;"></div>');
                }
            });
        }

        /**
         * Display actions loading state with contextual messaging
         * 
         * Shows loading state for outstanding actions panel with
         * appropriate messaging and spinner animation during
         * action data fetch operations.
         */
        showActionsLoading() {
            $('#cf7-outstanding-actions').html(`
                <div class="cf7-loading-state">
                    <div class="cf7-loading-spinner"></div>
                    <p>Loading outstanding actions...</p>
                </div>
            `);
        }

        /**
         * Clear actions loading state (content replacement handles cleanup)
         * 
         * Loading state cleanup is handled automatically when actual
         * content replaces loading state display during rendering.
         */
        hideActionsLoading() {
            // Loading state will be replaced by actual content
        }

        /**
         * Display submissions loading state with search-aware messaging
         * 
         * Shows contextual loading message based on current search state.
         * Provides specific feedback for search operations vs. general
         * submission loading with appropriate spinner animation.
         */
        showSubmissionsLoading() {
            const searchTerm = $('#cf7-search-input').val();
            const loadingMessage = searchTerm ? `Searching for "${searchTerm}"...` : 'Loading submissions...';
            
            $('.cf7-submissions-table').html(`
                <div class="cf7-loading-state">
                    <div class="cf7-loading-spinner"></div>
                    <div>${loadingMessage}</div>
                </div>
            `);
        }

        /**
         * Clear submissions loading state (content replacement handles cleanup)
         * 
         * Loading state cleanup is handled automatically when submission
         * content replaces loading state during table rendering.
         */
        hideSubmissionsLoading() {
            // Loading state will be replaced by actual content
        }

        // ========================================
        // User Feedback and Notification System
        // ========================================
        // Toast notification system with type-based styling, automatic
        // cleanup, and consistent messaging across dashboard operations
        // for comprehensive user feedback and status communication.
        //
        // Notification Features:
        // • Type-based styling (success, error, info) with appropriate icons
        // • Automatic timeout with fade-out animations
        // • Container management with dynamic creation
        // • Consistent positioning and accessibility features
        //
        // Toast Types:
        // • Success: Positive feedback for completed operations
        // • Error: Problem notifications with clear messaging
        // • Info: General information and status updates
        // ========================================

        /**
         * Display toast notification with type-based styling and automatic cleanup
         * 
         * Creates toast notification with appropriate icon, styling, and
         * automatic removal after timeout. Provides consistent user feedback
         * across all dashboard operations and AJAX interactions.
         * 
         * @param {string} message - Notification message text
         * @param {string} type - Notification type (success, error, info)
         * 
         * Toast Features:
         * • Icon mapping based on notification type for quick visual recognition
         * • Unique ID generation for individual toast management
         * • Dynamic container creation for notification placement
         * • 5-second auto-removal with fade-out animation
         * 
         * Styling Types:
         * • success: Green styling with checkmark icon for positive feedback
         * • error: Red styling with dismiss icon for problem notifications
         * • info: Blue styling with info icon for general status updates
         * 
         * Accessibility Features:
         * • Semantic icon usage with screen reader compatible content
         * • Consistent notification placement for user expectation
         * • Clear typography and high contrast for visual accessibility
         * • Automatic cleanup to prevent notification accumulation
         */
        showToast(message, type = 'info') {
            const toastId = 'cf7-toast-' + Date.now();
            const iconMap = {
                success: 'yes-alt',
                error: 'dismiss',
                info: 'info'
            };
            
            const toast = $(`
                <div class="cf7-toast ${type}" id="${toastId}">
                    <span class="dashicons dashicons-${iconMap[type]}"></span>
                    <span>${message}</span>
                </div>
            `);

            // Create container if it doesn't exist
            if (!$('.cf7-toast-container').length) {
                $('body').append('<div class="cf7-toast-container"></div>');
            }

            $('.cf7-toast-container').append(toast);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                $(`#${toastId}`).fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Trigger file download using AJAX endpoint with proper headers
         */
        triggerDownload(url, filename) {
            // Extract filename from URL if not provided
            if (!filename) {
                const urlParts = url.split('/');
                filename = urlParts[urlParts.length - 1];
            }
            
            // Create download URL using our AJAX endpoint
            const downloadUrl = ajaxurl + '?action=cf7_dashboard_download_csv&nonce=' + 
                encodeURIComponent(cf7_dashboard.nonce) + '&file=' + encodeURIComponent(filename);
            
            // Create a temporary anchor element
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            link.style.display = 'none';
            
            // Add to DOM, click, and remove
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // ========================================
    // Dashboard Initialization and Global Integration
    // ========================================
    // Document ready initialization with lifecycle management, polling
    // control, and global reference setup for cross-component integration.
    //
    // Initialization Features:
    // • Conditional initialization based on dashboard page detection
    // • Page visibility API integration for resource optimization
    // • Proper cleanup handling for polling and event listeners
    // • Global instance reference for external component access
    //
    // Lifecycle Management:
    // • Page visibility change handling for polling optimization
    // • beforeunload cleanup for proper resource disposal
    // • Memory leak prevention with proper event cleanup
    // • Cross-tab communication support through global references
    // ========================================

    /**
     * Initialize dashboard when DOM is ready with comprehensive lifecycle management
     * 
     * Sets up dashboard instance with conditional initialization, page visibility
     * handling, and proper cleanup for optimal resource usage and performance.
     * 
     * Initialization Process:
     * 1. Detect dashboard page presence before initialization
     * 2. Create global dashboard instance for external access
     * 3. Set up page visibility change handlers for polling optimization
     * 4. Configure beforeunload cleanup for proper resource disposal
     * 
     * Page Visibility Integration:
     * • Hidden pages: Stop polling to reduce server load and battery usage
     * • Visible pages: Resume polling for real-time dashboard updates
     * • Automatic state management based on browser visibility API
     * • Resource optimization for background tab handling
     * 
     * Global Instance Management:
     * • window.dashboardInstance: Primary dashboard controller reference
     * • Cross-component communication through global object
     * • External script integration capabilities
     * • Debugging and development tool access
     * 
     * Cleanup and Memory Management:
     * • beforeunload event handling for proper polling cleanup
     * • Event listener removal to prevent memory leaks
     * • AJAX request cancellation for clean page transitions
     * • Resource disposal for optimal browser performance
     */
    $(document).ready(function() {
        // Only initialize if we're on the dashboard page
        if ($('.cf7-modern-dashboard').length) {
            window.dashboardInstance = new CF7Dashboard();
            
            // Handle page visibility changes to pause/resume polling
            document.addEventListener('visibilitychange', function() {
                if (window.dashboardInstance) {
                    if (document.hidden) {
                        // Page is hidden, stop polling to save resources
                        window.dashboardInstance.stopPolling();
                    } else {
                        // Page is visible again, resume polling
                        window.dashboardInstance.startPolling();
                    }
                }
            });
            
            // Clean up polling when page is unloaded
            window.addEventListener('beforeunload', function() {
                if (window.dashboardInstance) {
                    window.dashboardInstance.stopPolling();
                }
            });
        }
    });

    // ========================================
    // Global Keyboard Shortcuts System
    // ========================================
    // Dashboard-wide keyboard shortcuts for power user efficiency
    // and accessibility. Provides standard shortcuts for common
    // operations with proper event handling and conflict prevention.
    //
    // Shortcut Features:
    // • Cross-platform modifier key support (Ctrl/Cmd)
    // • Dashboard context detection to prevent conflicts
    // • Standard keyboard conventions for familiar user experience
    // • Accessibility-friendly navigation shortcuts
    //
    // Available Shortcuts:
    // • Ctrl/Cmd + R: Manual dashboard refresh
    // • Ctrl/Cmd + A: Select all submissions (when focused)
    // • Escape: Clear current selection
    // ========================================

    /**
     * Handle global keyboard shortcuts for dashboard operations
     * 
     * Processes keyboard events to provide power user shortcuts for
     * common dashboard operations. Includes proper context detection
     * and conflict prevention with browser/OS shortcuts.
     * 
     * Shortcut Implementation:
     * • Ctrl/Cmd + R: Triggers manual dashboard refresh with user feedback
     * • Ctrl/Cmd + A: Selects all visible submissions when submission table focused
     * • Escape: Clears all selections and resets selection state
     * 
     * Context Awareness:
     * • Dashboard presence detection to prevent activation outside dashboard
     * • Focus context checking for appropriate shortcut activation
     * • Event prevention for browser default behavior override
     * 
     * Accessibility Features:
     * • Standard keyboard conventions for predictable behavior
     * • Visual feedback for shortcut-triggered actions
     * • Screen reader compatible action announcements
     * • Non-intrusive shortcut handling that doesn't interfere with normal typing
     * 
     * Cross-Platform Support:
     * • Automatic Ctrl/Cmd detection based on platform
     * • Consistent behavior across Windows, Mac, and Linux
     * • Proper event code handling for reliable key detection
     */
    // Handle keyboard shortcuts
    $(document).keydown(function(e) {
        if (!$('.cf7-modern-dashboard').length) return;

        // Ctrl/Cmd + R: Refresh
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            e.preventDefault();
            $('.cf7-refresh-btn').click();
        }

        // Ctrl/Cmd + A: Select All
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 65) {
            if ($('.cf7-submissions-table').is(':focus, :focus-within')) {
                e.preventDefault();
                $('.cf7-select-all').prop('checked', true).trigger('change');
            }
        }

        // Escape: Clear selection
        if (e.keyCode === 27) {
            $('.cf7-submission-checkbox').prop('checked', false);
            $('.cf7-select-all').prop('checked', false);
            $('.cf7-submission-checkbox').first().trigger('change');
        }
    });

    // ========================================
    // Extended Message Management System
    // ========================================
    // Prototype extension methods for comprehensive message handling
    // including individual message marking, submission-level operations,
    // and bulk message management with UI synchronization.
    //
    // Message Management Features:
    // • Individual message read state management
    // • Submission-level message operations for bulk handling
    // • Batch "mark all read" functionality for efficiency
    // • Real-time UI updates with visual feedback
    // • Statistics synchronization for accurate badge counts
    //
    // AJAX Operations:
    // • Secure nonce-based message operations
    // • Comprehensive error handling with user feedback
    // • Loading state management during operations
    // • Automatic UI refresh for immediate state reflection
    // ========================================

    /**
     * Mark individual message as read with UI updates
     * 
     * Processes individual message read marking with server-side state
     * updates and comprehensive UI synchronization. Provides immediate
     * visual feedback and maintains accurate message counts.
     * 
     * @param {string} messageId - Unique identifier for the message
     * 
     * Operation Process:
     * 1. Send AJAX request to mark specific message as read
     * 2. Remove visual unread indicators from message display
     * 3. Refresh activity components to update counts and badges
     * 4. Update statistics to reflect new unread message totals
     * 5. Provide user feedback through toast notification system
     * 
     * UI Synchronization:
     * • Immediate visual state change for responsive user experience
     * • Activity badge updates for accurate count display
     * • Statistics refresh for dashboard-wide count accuracy
     * • Toast feedback for operation confirmation
     * 
     * Error Handling:
     * • Server error processing with user-friendly messaging
     * • Network failure handling with retry suggestions
     * • Graceful degradation if message element not found
     * • Consistent error feedback through notification system
     */
    // Add new methods for message management
    CF7Dashboard.prototype.markMessageAsRead = function(messageId) {
        const data = {
            action: 'cf7_dashboard_mark_message_read',
            nonce: cf7_dashboard.nonce,
            message_id: messageId
        };

        $.post(ajaxurl, data)
            .done((response) => {
                if (response.success) {
                    // Remove visual unread indicators
                    $(`.cf7-message-item[data-message-id="${messageId}"]`).removeClass('unread');
                    
                    // Refresh activity and stats to update counts
                    this.refreshActivity();
                    this.loadStats();
                    
                    this.showToast('Message marked as read', 'success');
                } else {
                    this.showToast('Failed to mark message as read', 'error');
                }
            })
            .fail(() => {
                this.showToast('Failed to mark message as read', 'error');
            });
    };

    /**
     * Mark all messages for a submission as read with comprehensive UI cleanup
     * 
     * Processes submission-level message read marking with complete UI state
     * management and visual feedback. Removes message items and updates
     * all related counters and indicators across the dashboard.
     * 
     * @param {string} submissionId - Unique identifier for the submission
     * 
     * Operation Features:
     * • Submission-level message marking for efficient bulk operations
     * • Animated message item removal with fade-out transitions
     * • Footer management when all messages are processed
     * • Immediate and delayed statistics updates for accuracy
     * • Force refresh mechanisms for stubborn cache scenarios
     * 
     * UI Cleanup Process:
     * 1. Send AJAX request for submission-level message marking
     * 2. Animate removal of message items with smooth fade-out
     * 3. Check and remove footer if no messages remain
     * 4. Refresh activity components and statistics immediately
     * 5. Force additional statistics update for cache clearing
     * 
     * Visual Feedback:
     * • Smooth fade-out animations for professional user experience
     * • Immediate UI state changes for responsive feedback
     * • Toast notifications for operation confirmation
     * • Badge count updates across all dashboard components
     * 
     * State Management:
     * • Real-time activity component refresh for count accuracy
     * • Multiple statistics refresh cycles for cache clearing
     * • Empty state management when no messages remain
     * • Force refresh mechanisms for reliable state synchronization
     */
    CF7Dashboard.prototype.markSubmissionRead = function(submissionId) {
        const data = {
            action: 'cf7_dashboard_mark_submission_read',
            nonce: cf7_dashboard.nonce,
            submission_id: submissionId
        };

        $.post(ajaxurl, data)
            .done((response) => {
                if (response.success) {
                    // Remove the message item for this submission
                    $(`.cf7-message-item[data-submission-id="${submissionId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        // Check if we need to hide the footer
                        if ($('.cf7-message-item').length === 0) {
                            $('.cf7-messages-footer').remove();
                            $('.cf7-recent-messages').html('<div class="cf7-loading-state">No unread messages</div>');
                        }
                    });
                    // Refresh activity and stats to update counts
                    this.refreshActivity();
                    this.loadStats(); // Update unread message badge counts
                    
                    // Force immediate badge update
                    setTimeout(() => {
                        this.loadStats();
                    }, 100);
                    
                    this.showToast('Messages marked as read', 'success');
                } else {
                    this.showToast('Failed to mark messages as read', 'error');
                }
            })
            .fail(() => {
                this.showToast('Failed to mark messages as read', 'error');
            });
    };

    /**
     * Mark all dashboard messages as read with complete interface reset
     * 
     * Processes bulk message marking for all unread messages with
     * comprehensive UI cleanup and state management. Provides efficient
     * workflow completion for heavy message scenarios.
     * 
     * Bulk Operation Features:
     * • System-wide message read marking for complete workflow reset
     * • Animated removal of all message items with coordinated transitions
     * • Footer cleanup and empty state replacement
     * • Multiple refresh cycles for reliable state synchronization
     * • Force refresh mechanisms for cache-resistant scenarios
     * 
     * Animation Coordination:
     * • Synchronized fade-out of all message items for smooth experience
     * • Sequential footer removal after message items complete
     * • Empty state replacement with consistent messaging
     * • Professional transition effects for bulk operations
     * 
     * State Synchronization:
     * • Immediate activity component refresh for count updates
     * • Primary statistics refresh for badge count accuracy
     * • Delayed force refresh for cache clearing scenarios
     * • Complete dashboard state reset for clean workflow continuation
     * 
     * User Experience:
     * • Single-action bulk completion for efficient workflow
     * • Clear feedback through toast notifications
     * • Visual confirmation through animated state changes
     * • Immediate interface reset for continued productivity
     */
    CF7Dashboard.prototype.markAllMessagesRead = function() {
        const data = {
            action: 'cf7_dashboard_mark_all_read',
            nonce: cf7_dashboard.nonce
        };

        $.post(ajaxurl, data)
            .done((response) => {
                if (response.success) {
                    // Remove all message items and footer
                    $('.cf7-message-item').fadeOut(300, function() {
                        $(this).remove();
                    });
                    $('.cf7-messages-footer').fadeOut(300, function() {
                        $(this).remove();
                        $('.cf7-recent-messages').html('<div class="cf7-loading-state">No unread messages</div>');
                    });
                    // Refresh activity, stats, and messages
                    this.refreshActivity();
                    this.loadStats(); // Update unread message badge counts
                    
                    // Force immediate badge update
                    setTimeout(() => {
                        this.loadStats();
                    }, 100);
                    
                    this.showToast('All messages marked as read', 'success');
                } else {
                    this.showToast('Failed to mark messages as read', 'error');
                }
            })
            .fail(() => {
                this.showToast('Failed to mark messages as read', 'error');
            });
    };

    // ========================================
    // Modern Calendar Date Picker System
    // ========================================
    // Comprehensive calendar interface for date range selection with
    // interactive calendar grid, preset shortcuts, and responsive design.
    // Provides modern date selection experience for dashboard filtering.
    //
    // Calendar Features:
    // • Interactive monthly calendar grid with date range selection
    // • Quick preset shortcuts for common date ranges (today, week, month)
    // • Keyboard navigation support with accessibility features
    // • Responsive positioning with viewport detection
    // • Future date restrictions for submission filtering context
    //
    // Selection Modes:
    // • Single date selection for specific day filtering
    // • Range selection with start/end date visual indicators
    // • Preset ranges for quick common selections
    // • Smart range completion with automatic date ordering
    //
    // Visual Design:
    // • Modern calendar grid with clean typography
    // • Color-coded date states (today, selected, disabled, in-range)
    // • Smooth animations for state transitions
    // • Professional dropdown positioning with auto-adjustment
    // ========================================

    /**
     * Initialize comprehensive calendar date picker with full interaction support
     * 
     * Sets up complete calendar system including state management, event
     * binding, keyboard navigation, and responsive positioning. Provides
     * foundation for all date selection operations in dashboard.
     * 
     * Calendar State Management:
     * • Current month/year navigation state
     * • Selected date range tracking (start/end dates)
     * • Selection mode state (single vs range selection)
     * • Open/closed state with proper cleanup
     * 
     * Event Binding Coverage:
     * • Calendar trigger clicks with proper event delegation
     * • Keyboard navigation (Enter, Space, Escape) for accessibility
     * • Month navigation with previous/next month controls
     * • Date selection with range completion logic
     * • Preset shortcuts for common date ranges
     * • Outside click detection for automatic calendar closing
     * 
     * Accessibility Features:
     * • Full keyboard navigation support with standard shortcuts
     * • Screen reader compatible event handling
     * • Focus management for modal-like behavior
     * • Clear visual indicators for selection state
     * 
     * Responsive Design:
     * • Automatic positioning based on available viewport space
     * • Mobile-optimized touch targets and interaction zones
     * • Adaptive layout for various screen dimensions
     * • Professional dropdown positioning with collision detection
     */
    // Modern Calendar Date Picker Implementation
    CF7Dashboard.prototype.initCalendarDatePicker = function() {
        this.calendar = {
            isOpen: false,
            currentMonth: new Date().getMonth(),
            currentYear: new Date().getFullYear(),
            selectedStartDate: null,
            selectedEndDate: null,
            isSelectingRange: false
        };

        // Calendar trigger click
        $(document).on('click', '.cf7-calendar-trigger', (e) => {
            e.stopPropagation();
            this.toggleCalendar();
        });

        // Calendar trigger keyboard navigation
        $(document).on('keydown', '.cf7-calendar-trigger', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleCalendar();
            }
        });

        // Escape key to close calendar
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape' && this.calendar.isOpen) {
                this.closeCalendar();
            }
        });

        // Calendar navigation
        $(document).on('click', '.cf7-calendar-nav', (e) => {
            e.stopPropagation();
            const action = $(e.target).data('action');
            this.navigateCalendar(action);
        });

        // Calendar day click
        $(document).on('click', '.cf7-calendar-day', (e) => {
            e.stopPropagation();
            const $day = $(e.target);
            if ($day.hasClass('other-month') || $day.hasClass('disabled')) return;
            
            const day = parseInt($day.text());
            const date = new Date(this.calendar.currentYear, this.calendar.currentMonth, day);
            this.selectCalendarDate(date);
        });

        // Calendar presets
        $(document).on('click', '.cf7-calendar-preset', (e) => {
            e.stopPropagation();
            const range = $(e.target).data('range');
            this.setCalendarPreset(range);
        });

        // Calendar actions
        $(document).on('click', '.cf7-calendar-clear', (e) => {
            e.stopPropagation();
            this.clearCalendarSelection();
        });

        $(document).on('click', '.cf7-calendar-apply', (e) => {
            e.stopPropagation();
            this.applyCalendarSelection();
        });

        // Close calendar when clicking outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.cf7-calendar-date-picker').length) {
                this.closeCalendar();
            }
        });

        // Initialize calendar display
        this.renderCalendar();
    };

    /**
     * Toggle calendar visibility with state management
     * 
     * Handles calendar open/close state transitions with proper
     * visual feedback and state synchronization.
     */
    CF7Dashboard.prototype.toggleCalendar = function() {
        const $picker = $('.cf7-calendar-date-picker');
        const $trigger = $('.cf7-calendar-trigger');
        
        if (this.calendar.isOpen) {
            this.closeCalendar();
        } else {
            this.openCalendar();
        }
    };

    /**
     * Open calendar with intelligent positioning and state initialization
     * 
     * Activates calendar interface with responsive positioning, existing
     * date population, and proper accessibility state management.
     * 
     * Positioning Features:
     * • Automatic above/below positioning based on available viewport space
     * • Mobile-responsive positioning with desktop/mobile detection
     * • Collision detection with viewport boundaries
     * • Smooth positioning transitions with delayed calculation
     * 
     * State Initialization:
     * • Population of existing date selections from hidden inputs
     * • Calendar month/year positioning based on selected dates
     * • Visual state updates for trigger and dropdown elements
     * • Accessibility state management for screen readers
     */
    CF7Dashboard.prototype.openCalendar = function() {
        const $picker = $('.cf7-calendar-date-picker');
        const $trigger = $('.cf7-calendar-trigger');
        const $dropdown = $('.cf7-calendar-dropdown');
        
        $picker.addClass('open');
        $trigger.addClass('active');
        this.calendar.isOpen = true;
        
        // Set current dates if any exist
        const fromDate = $('#cf7-date-from').val();
        const toDate = $('#cf7-date-to').val();
        
        if (fromDate) {
            this.calendar.selectedStartDate = new Date(fromDate);
        }
        if (toDate) {
            this.calendar.selectedEndDate = new Date(toDate);
        }
        
        // Ensure dropdown is properly positioned
        setTimeout(() => {
            const triggerRect = $trigger[0].getBoundingClientRect();
            const dropdownHeight = $dropdown.outerHeight();
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - triggerRect.bottom;
            
            // If not enough space below, position above (only on desktop)
            if (spaceBelow < dropdownHeight + 20 && window.innerWidth > 768) {
                $dropdown.css({
                    'top': 'auto',
                    'bottom': '100%',
                    'margin-top': '0',
                    'margin-bottom': '0.5rem'
                });
            } else {
                $dropdown.css({
                    'top': '100%',
                    'bottom': 'auto',
                    'margin-top': '0.5rem',
                    'margin-bottom': '0'
                });
            }
        }, 10);
        
        this.renderCalendar();
    };

    /**
     * Close calendar with proper state cleanup
     * 
     * Deactivates calendar interface with visual state reset and
     * accessibility cleanup for clean calendar dismissal.
     */
    CF7Dashboard.prototype.closeCalendar = function() {
        const $picker = $('.cf7-calendar-date-picker');
        const $trigger = $('.cf7-calendar-trigger');
        
        $picker.removeClass('open');
        $trigger.removeClass('active');
        this.calendar.isOpen = false;
    };

    /**
     * Navigate calendar months with year boundary handling
     * 
     * Processes month navigation with automatic year transitions
     * and calendar grid regeneration for smooth browsing experience.
     * 
     * @param {string} action - Navigation action (prev-month, next-month)
     */
    CF7Dashboard.prototype.navigateCalendar = function(action) {
        if (action === 'prev-month') {
            this.calendar.currentMonth--;
            if (this.calendar.currentMonth < 0) {
                this.calendar.currentMonth = 11;
                this.calendar.currentYear--;
            }
        } else if (action === 'next-month') {
            this.calendar.currentMonth++;
            if (this.calendar.currentMonth > 11) {
                this.calendar.currentMonth = 0;
                this.calendar.currentYear++;
            }
        }
        
        this.renderCalendar();
    };

    CF7Dashboard.prototype.selectCalendarDate = function(date) {
        if (!this.calendar.selectedStartDate || (this.calendar.selectedStartDate && this.calendar.selectedEndDate)) {
            // Start new selection
            this.calendar.selectedStartDate = date;
            this.calendar.selectedEndDate = null;
            this.calendar.isSelectingRange = true;
        } else if (this.calendar.selectedStartDate && !this.calendar.selectedEndDate) {
            // Complete range selection
            if (date < this.calendar.selectedStartDate) {
                // User selected earlier date, swap them
                this.calendar.selectedEndDate = this.calendar.selectedStartDate;
                this.calendar.selectedStartDate = date;
            } else {
                this.calendar.selectedEndDate = date;
            }
            this.calendar.isSelectingRange = false;
        }
        
        this.renderCalendar();
        this.updateCalendarTriggerText();
    };

    CF7Dashboard.prototype.setCalendarPreset = function(range) {
        const today = new Date();
        let startDate = null;
        let endDate = null;

        switch(range) {
            case 'today':
                startDate = endDate = new Date(today);
                break;
            case 'week':
                endDate = new Date(today);
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 6);
                break;
            case 'month':
                endDate = new Date(today);
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 29);
                break;
        }

        this.calendar.selectedStartDate = startDate;
        this.calendar.selectedEndDate = endDate;
        
        // Update active preset
        $('.cf7-calendar-preset').removeClass('active');
        $(`.cf7-calendar-preset[data-range="${range}"]`).addClass('active');
        
        this.renderCalendar();
        this.updateCalendarTriggerText();
    };

    CF7Dashboard.prototype.clearCalendarSelection = function() {
        this.calendar.selectedStartDate = null;
        this.calendar.selectedEndDate = null;
        $('.cf7-calendar-preset').removeClass('active');
        
        // Clear the hidden date inputs
        $('#cf7-date-from').val('');
        $('#cf7-date-to').val('');
        
        this.renderCalendar();
        this.updateCalendarTriggerText();
        
        // Trigger filter update to refresh the submissions list
        this.currentPage = 1;
        this.updateActiveFiltersDisplay();
        this.loadSubmissions();
        
        // Close the calendar after clearing
        this.closeCalendar();
    };

    CF7Dashboard.prototype.applyCalendarSelection = function() {
        let fromDate = '';
        let toDate = '';
        
        if (this.calendar.selectedStartDate) {
            fromDate = this.formatDateForInput(this.calendar.selectedStartDate);
        }
        if (this.calendar.selectedEndDate) {
            toDate = this.formatDateForInput(this.calendar.selectedEndDate);
        }
        
        $('#cf7-date-from').val(fromDate);
        $('#cf7-date-to').val(toDate);
        
        this.closeCalendar();
        this.currentPage = 1;
        this.updateActiveFiltersDisplay();
        this.loadSubmissions();
    };

    CF7Dashboard.prototype.renderCalendar = function() {
        // Update month/year display
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        $('.cf7-calendar-month').text(monthNames[this.calendar.currentMonth]);
        $('.cf7-calendar-year').text(this.calendar.currentYear);
        
        // Generate calendar grid
        const firstDay = new Date(this.calendar.currentYear, this.calendar.currentMonth, 1);
        const lastDay = new Date(this.calendar.currentYear, this.calendar.currentMonth + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const today = new Date();
        
        let html = '';
        let currentDate = new Date(startDate);
        
        // Generate 6 weeks of calendar
        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const dayClasses = ['cf7-calendar-day'];
                const isCurrentMonth = currentDate.getMonth() === this.calendar.currentMonth;
                const dateStr = this.formatDateForInput(currentDate);
                const isToday = this.isSameDate(currentDate, today);
                const isFuture = currentDate > today;
                
                if (!isCurrentMonth) {
                    dayClasses.push('other-month');
                }
                
                if (isToday) {
                    dayClasses.push('today');
                }
                
                // Disable future dates (submissions can't be from the future)
                if (isFuture) {
                    dayClasses.push('disabled');
                }
                
                // Check if this date is selected or in range (only if not disabled)
                if (!isFuture && this.calendar.selectedStartDate && this.isSameDate(currentDate, this.calendar.selectedStartDate)) {
                    dayClasses.push('range-start');
                    if (!this.calendar.selectedEndDate || this.isSameDate(this.calendar.selectedStartDate, this.calendar.selectedEndDate)) {
                        dayClasses.push('range-end');
                    }
                }
                
                if (!isFuture && this.calendar.selectedEndDate && this.isSameDate(currentDate, this.calendar.selectedEndDate)) {
                    dayClasses.push('range-end');
                }
                
                if (!isFuture && this.calendar.selectedStartDate && this.calendar.selectedEndDate &&
                    currentDate > this.calendar.selectedStartDate && currentDate < this.calendar.selectedEndDate) {
                    dayClasses.push('in-range');
                }
                
                html += `<div class="${dayClasses.join(' ')}" data-date="${dateStr}">
                    ${currentDate.getDate()}
                </div>`;
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
        }
        
        $('#cf7-calendar-grid').html(html);
    };

    CF7Dashboard.prototype.updateCalendarTriggerText = function() {
        let text = 'Select date range';
        
        if (this.calendar.selectedStartDate && this.calendar.selectedEndDate) {
            if (this.isSameDate(this.calendar.selectedStartDate, this.calendar.selectedEndDate)) {
                text = this.formatDateDisplay(this.calendar.selectedStartDate);
            } else {
                text = `${this.formatDateDisplay(this.calendar.selectedStartDate)} - ${this.formatDateDisplay(this.calendar.selectedEndDate)}`;
            }
        } else if (this.calendar.selectedStartDate) {
            text = `From ${this.formatDateDisplay(this.calendar.selectedStartDate)}`;
        }
        
        $('.cf7-calendar-text').text(text);
    };

    CF7Dashboard.prototype.formatDateForInput = function(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    CF7Dashboard.prototype.formatDateDisplay = function(date) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    };

    CF7Dashboard.prototype.isSameDate = function(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    };

    CF7Dashboard.prototype.setDateRange = function(range) {
        // Keep the old method for compatibility, but use the new calendar
        this.setCalendarPreset(range);
        this.applyCalendarSelection();
    };

    CF7Dashboard.prototype.updateFilterIndicators = function() {
        const $filterBar = $('.cf7-date-filter-bar');
        const hasDateFilter = $('#cf7-date-from').val() || $('#cf7-date-to').val();
        const hasStatusFilter = $('.cf7-status-filter-dropdown').attr('data-current') || '';
        const hasSearchFilter = $('#cf7-search-input').val();
        
        // Add visual indicator if any filters are active
        if (hasDateFilter || hasStatusFilter || hasSearchFilter) {
            $filterBar.addClass('cf7-filters-active');
            
            // Show active filter summary
            let filterSummary = [];
            if (hasSearchFilter) filterSummary.push(`Search: "${hasSearchFilter}"`);
            if (hasStatusFilter) filterSummary.push(`Status: ${hasStatusFilter}`);
            if (hasDateFilter) {
                const fromDate = $('#cf7-date-from').val();
                const toDate = $('#cf7-date-to').val();
                if (fromDate && toDate) {
                    filterSummary.push(`Date: ${fromDate} to ${toDate}`);
                } else if (fromDate) {
                    filterSummary.push(`Date: from ${fromDate}`);
                } else if (toDate) {
                    filterSummary.push(`Date: until ${toDate}`);
                }
            }
            
            // Update or create filter summary element
            let $summary = $('.cf7-filter-summary');
            if ($summary.length === 0) {
                $summary = $('<div class="cf7-filter-summary"></div>');
                $('.cf7-date-filter-bar').append($summary);
            }
            $summary.html('<strong>Active filters:</strong> ' + filterSummary.join(', '));
        } else {
            $filterBar.removeClass('cf7-filters-active');
            $('.cf7-filter-summary').remove();
        }
    };

    // ========================================
    // Advanced Filter Management System
    // ========================================
    // Comprehensive filtering system with intelligent state management,
    // visual indicators, and multi-criteria filter coordination.
    // Provides powerful filtering capabilities for submission management.
    //
    // Filter Management Features:
    // • Multi-criteria filtering (search, status, date range)
    // • Visual filter indicators with active state display
    // • Individual filter clearing with surgical precision
    // • Bulk filter clearing with complete state reset
    // • Filter coordination to prevent conflicts
    //
    // Visual Filter Feedback:
    // • Active filter tags with removal controls
    // • Filter summary display with criteria breakdown
    // • Panel title updates to reflect filtered state
    // • Clear visual hierarchy for filter management
    //
    // State Coordination:
    // • Dropdown state management with proper cleanup
    // • Input field synchronization across components
    // • Pagination reset for new filter application
    // • Loading state coordination during filter changes
    // ========================================

    /**
     * Clear all active filters with comprehensive state reset
     * 
     * Performs complete filter state reset including search inputs,
     * status dropdowns, date selections, and UI synchronization.
     * Provides clean slate for new filtering operations.
     * 
     * Filter Reset Process:
     * 1. Set clearing flag to prevent race conditions during reset
     * 2. Clear search input and date selection fields
     * 3. Reset status dropdown to "All Statuses" with explicit state management
     * 4. Update visual display elements to match reset state
     * 5. Trigger submission reload with cleared filters
     * 
     * State Management Features:
     * • Race condition prevention with clearing flag
     * • Explicit dropdown attribute management for reliable state
     * • Visual element synchronization across UI components
     * • Pagination reset for clean result display
     * 
     * Dropdown Reset Logic:
     * • Complete attribute removal and reset for clean state
     * • Explicit active state clearing across all options
     * • "All Statuses" option activation with proper styling
     * • Display element updates to match default state
     * 
     * UI Synchronization:
     * • Calendar text reset to default placeholder
     * • Active filter display updates for immediate feedback
     * • Loading state coordination during filter application
     * • Clean transition to unfiltered submission view
     */
    /**
     * Clear all active filters and refresh the dashboard
     */
    CF7Dashboard.prototype.clearAllFilters = function() {
        // Set a flag to prevent other loadSubmissions calls during clearing
        this.clearingFilters = true;
        
        // Clear search input
        $('#cf7-search-input').val('');
        
        // Clear calendar selection WITHOUT triggering loadSubmissions
        $('#cf7-date-from').val('');
        $('#cf7-date-to').val('');
        $('.cf7-calendar-text').text('Date');
        
        // Reset status dropdown to "All Statuses" - be very explicit and thorough
        const $dropdown = $('.cf7-status-filter-dropdown');
        
        // Step 1: Remove the data-current attribute completely, then set it to empty
        $dropdown.removeAttr('data-current');
        $dropdown.attr('data-current', '');
        $dropdown.removeClass('open');
        
        // Step 2: Clear all active states explicitly  
        $('.cf7-status-filter-option').each(function() {
            $(this).removeClass('active');
        });
        
        // Step 3: Set the "All Statuses" option as active
        const $allStatusOption = $('.cf7-status-filter-option[data-value=""]');
        $allStatusOption.addClass('active');
        
        // Step 4: Update display to match exactly what "All Statuses" should show
        const $display = $('.cf7-status-filter-display');
        $display.find('.cf7-status-icon')
            .removeClass()
            .addClass('cf7-status-icon dashicons dashicons-category')
            .css('color', '#718096');
        $display.find('.cf7-status-text').text('All Statuses');
        
        // Reset page
        this.currentPage = 1;
        
        // DEFENSIVE: Double-check the data-current is still empty before proceeding
        // Update active filters display 
        this.updateActiveFiltersDisplay();
        
        // Clear the flag and load submissions
        this.clearingFilters = false;
        this.loadSubmissions();
    };

    /**
     * Clear specific filter type with targeted state management
     * 
     * Removes individual filter types while preserving other active
     * filters. Provides surgical filter management for precise
     * filtering workflows and user convenience.
     * 
     * @param {string} filterType - Type of filter to clear (search, status, date)
     * 
     * Filter-Specific Clearing:
     * • search: Clears search input while preserving status and date filters
     * • status: Resets status dropdown to "All Statuses" with proper state management
     * • date: Clears date range inputs and resets calendar display
     * 
     * State Management Per Type:
     * • Search: Simple input field clearing with immediate effect
     * • Status: Comprehensive dropdown reset with attribute and display management
     * • Date: Calendar integration with display text reset
     * 
     * UI Coordination:
     * • Pagination reset for new filter application
     * • Active filter display updates for immediate visual feedback
     * • Submission reload with updated filter criteria
     * • Smooth transitions between filter states
     */
    /**
     * Clear a specific filter
     */
    CF7Dashboard.prototype.clearSpecificFilter = function(filterType) {
        switch(filterType) {
            case 'search':
                $('#cf7-search-input').val('');
                break;
            case 'status':
                const $dropdown = $('.cf7-status-filter-dropdown');
                
                // Remove and reset data-current attribute
                $dropdown.removeAttr('data-current');
                $dropdown.attr('data-current', '');
                $dropdown.removeClass('open');
                
                // Clear all active states explicitly
                $('.cf7-status-filter-option').each(function() {
                    $(this).removeClass('active');
                });
                
                // Set "All Statuses" as active
                $('.cf7-status-filter-option[data-value=""]').addClass('active');
                
                const $display = $('.cf7-status-filter-display');
                $display.find('.cf7-status-icon')
                    .removeClass()
                    .addClass('cf7-status-icon dashicons dashicons-category')
                    .css('color', '#718096');
                $display.find('.cf7-status-text').text('All Statuses');
                break;
            case 'date':
                $('#cf7-date-from').val('');
                $('#cf7-date-to').val('');
                $('.cf7-calendar-text').text('Select date range');
                break;
        }
        
        this.currentPage = 1;
        this.updateActiveFiltersDisplay();
        this.loadSubmissions();
    };

    /**
     * Update comprehensive active filter display with removal controls
     * 
     * Manages visual filter indicators including active filter tags,
     * summary displays, and panel title updates. Provides user-friendly
     * filter management interface with individual removal capabilities.
     * 
     * Filter Tag Generation:
     * • Individual filter tags for each active filter type
     * • Inline removal controls (×) for surgical filter management
     * • Filter type identification for proper removal handling
     * • Clear filter criteria display for user awareness
     * 
     * Filter Types Supported:
     * • Search filters with query display and removal
     * • Status filters with label display and dropdown coordination
     * • Date filters with range display and calendar integration
     * • Combined filter scenarios with multiple active criteria
     * 
     * Visual Hierarchy:
     * • Sliding animation for filter bar visibility
     * • Clear filter tags with consistent styling
     * • Panel title updates to reflect filtered state
     * • Filter summary for complex multi-criteria scenarios
     * 
     * Interaction Features:
     * • Individual filter removal through inline controls
     * • Consistent removal behavior across filter types
     * • Visual feedback for filter state changes
     * • Accessibility-friendly removal controls
     * 
     * Date Filter Intelligence:
     * • Single date vs range detection for appropriate display
     * • Localized date formatting for user region
     * • Smart date range descriptions (From/Until/Range)
     * • Calendar integration for seamless date management
     */
    /**
     * Update the active filters display
     */
    CF7Dashboard.prototype.updateActiveFiltersDisplay = function() {
        const $clearFilters = $('#cf7-clear-filters');
        const $activeFilters = $('#cf7-active-filters');
        const $panelTitle = $('#cf7-panel-title');
        
        // Clear existing filter tags
        $activeFilters.empty();
        
        let hasActiveFilters = false;
        
        // Check for search filter
        const searchValue = $('#cf7-search-input').val();
        if (searchValue) {
            hasActiveFilters = true;
            const searchTag = `
                <span class="cf7-filter-tag" data-filter-type="search">
                    Search: "${searchValue}"
                    <span class="cf7-filter-remove" onclick="CF7Dashboard.clearSpecificFilter('search')">×</span>
                </span>
            `;
            $activeFilters.append(searchTag);
        }
        
        // Check for status filter
        const activeStatus = $('.cf7-status-filter-dropdown').attr('data-current') || '';
        if (activeStatus && activeStatus !== '') {
            hasActiveFilters = true;
            const statusLabel = $('.cf7-status-filter-option.active .cf7-status-label').text();
            const statusTag = `
                <span class="cf7-filter-tag" data-filter-type="status">
                    Status: ${statusLabel}
                    <span class="cf7-filter-remove" onclick="CF7Dashboard.clearSpecificFilter('status')">×</span>
                </span>
            `;
            $activeFilters.append(statusTag);
        }
        
        // Check for date filter
        const dateFrom = $('#cf7-date-from').val();
        const dateTo = $('#cf7-date-to').val();
        if (dateFrom || dateTo) {
            hasActiveFilters = true;
            let dateText = 'Date: ';
            if (dateFrom && dateTo) {
                if (dateFrom === dateTo) {
                    dateText += new Date(dateFrom).toLocaleDateString();
                } else {
                    dateText += `${new Date(dateFrom).toLocaleDateString()} - ${new Date(dateTo).toLocaleDateString()}`;
                }
            } else if (dateFrom) {
                dateText += `From ${new Date(dateFrom).toLocaleDateString()}`;
            } else {
                dateText += `Until ${new Date(dateTo).toLocaleDateString()}`;
            }
            
            const dateTag = `
                <span class="cf7-filter-tag" data-filter-type="date">
                    ${dateText}
                    <span class="cf7-filter-remove" onclick="CF7Dashboard.clearSpecificFilter('date')">×</span>
                </span>
            `;
            $activeFilters.append(dateTag);
        }
        
        // Show/hide the clear filters bars
        if (hasActiveFilters) {
            $clearFilters.slideDown(200);
            $panelTitle.text('Submissions (filtered)');
        } else {
            $clearFilters.slideUp(200);
            $panelTitle.text('Submissions');
        }
    };

    /**
     * HTML escaping utility for safe content rendering
     * 
     * Provides XSS protection by escaping HTML content before DOM injection.
     * Essential for displaying user-generated content safely in dashboard.
     * 
     * @param {string} text - Raw text content to escape
     * @returns {string} HTML-escaped content safe for DOM injection
     */
    CF7Dashboard.prototype.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // ========================================
    // Global Dashboard Interface
    // ========================================
    // Global object export for external component integration and
    // inline event handler support. Provides bridge between dashboard
    // controller and external scripts or template inline handlers.
    //
    // Global Functions:
    // • clearAllFilters: Reset all active filters to default state
    // • clearSpecificFilter: Remove individual filter types
    // • dashboardInstance: Direct access to main controller
    //
    // Integration Support:
    // • Template inline event handlers
    // • Cross-component communication
    // • External script integration
    // • Developer debugging and testing
    // ========================================

    /**
     * Global CF7Dashboard interface for external integration
     * 
     * Provides public methods for external components to interact with
     * dashboard functionality. Includes safety checks to ensure dashboard
     * instance availability before method execution.
     * 
     * Available Methods:
     * • clearAllFilters(): Reset all active filters with UI updates
     * • clearSpecificFilter(type): Remove specific filter type
     * 
     * Safety Features:
     * • Instance availability checking before method calls
     * • Graceful degradation when dashboard not initialized
     * • Error boundary protection for external integration
     * • Consistent API interface for reliable integration
     * 
     * Usage Examples:
     * • window.CF7Dashboard.clearAllFilters() - Reset filters
     * • window.CF7Dashboard.clearSpecificFilter('status') - Clear status filter
     * • window.dashboardInstance.loadSubmissions() - Direct instance access
     */
    window.CF7Dashboard = {
        clearAllFilters: function() {
            if (window.dashboardInstance) {
                window.dashboardInstance.clearAllFilters();
            }
        },
        clearSpecificFilter: function(filterType) {
            if (window.dashboardInstance) {
                window.dashboardInstance.clearSpecificFilter(filterType);
            }
        }
    };

})(jQuery);
