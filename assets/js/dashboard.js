/**
 * CF7 Artist Submissions - Modern Interactive Dashboard
 */

(function($) {
    'use strict';

    class CF7Dashboard {
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

        updateSelectedItems() {
            this.selectedItems.clear();
            
            $('.cf7-submission-checkbox:checked').each((i, el) => {
                this.selectedItems.add($(el).val());
            });

            this.updateBulkActions();
        }

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

        // Selective refresh methods for better performance
        refreshSubmissions() {
            // Only refresh submissions list and stats
            this.loadSubmissions();
            this.loadStats();
        }

        refreshMessages() {
            // Refresh messages and stats to update badge counts
            this.loadRecentMessages();
            this.loadStats();
        }

        refreshActivity() {
            // Refresh activity cards (messages, actions, and today's activity)
            this.loadRecentMessages();
            this.loadOutstandingActions();
            this.updateTodayActivity();
        }

        refreshStats() {
            // Only refresh stats
            this.loadStats();
        }

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

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }

        showStatsLoading() {
            $('.cf7-stat-card').each(function() {
                const $card = $(this);
                const $number = $card.find('.cf7-stat-number');
                if ($number.length) {
                    $number.html('<div class="cf7-loading-spinner" style="width: 20px; height: 20px; margin: 0;"></div>');
                }
            });
        }

        showActionsLoading() {
            $('#cf7-outstanding-actions').html(`
                <div class="cf7-loading-state">
                    <div class="cf7-loading-spinner"></div>
                    <p>Loading outstanding actions...</p>
                </div>
            `);
        }

        hideActionsLoading() {
            // Loading state will be replaced by actual content
        }

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

        hideSubmissionsLoading() {
            // Loading state will be replaced by actual content
        }

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

    // Initialize dashboard when DOM is ready
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

    CF7Dashboard.prototype.toggleCalendar = function() {
        const $picker = $('.cf7-calendar-date-picker');
        const $trigger = $('.cf7-calendar-trigger');
        
        if (this.calendar.isOpen) {
            this.closeCalendar();
        } else {
            this.openCalendar();
        }
    };

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

    CF7Dashboard.prototype.closeCalendar = function() {
        const $picker = $('.cf7-calendar-date-picker');
        const $trigger = $('.cf7-calendar-trigger');
        
        $picker.removeClass('open');
        $trigger.removeClass('active');
        this.calendar.isOpen = false;
    };

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

    CF7Dashboard.prototype.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // Global reference for inline event handlers
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
