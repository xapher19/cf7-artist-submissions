/**
 * CF7 Artist Submissions - Modern Interactive Dashboard
 */

(function($) {
    'use strict';

    class CF7Dashboard {
        constructor() {
            this.currentPage = 1;
            this.selectedItems = new Set();
            this.searchTimeout = null;
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
            this.startPolling();
        }

        bindEvents() {
            // Search functionality with new HTML structure - super responsive real-time search
            $(document).on('input', '#cf7-search-input', (e) => {
                clearTimeout(this.searchTimeout);
                
                // Add visual feedback while typing
                const $searchContainer = $('.cf7-search-input-container');
                $searchContainer.addClass('cf7-searching');
                
                this.searchTimeout = setTimeout(() => {
                    this.currentPage = 1; // Reset to first page when searching
                    this.loadSubmissions();
                    $searchContainer.removeClass('cf7-searching');
                }, 300); // 300ms delay for responsive real-time search
            });

            // Clear search when field is empty
            $(document).on('keyup', '#cf7-search-input', (e) => {
                if (e.target.value === '') {
                    clearTimeout(this.searchTimeout);
                    this.currentPage = 1;
                    this.loadSubmissions();
                }
            });

            // Date range validation
            $(document).on('change', '#cf7-date-from', (e) => {
                const fromDate = e.target.value;
                const toDate = $('#cf7-date-to').val();
                
                if (fromDate && toDate && fromDate > toDate) {
                    $('#cf7-date-to').val(fromDate);
                }
            });

            $(document).on('change', '#cf7-date-to', (e) => {
                const toDate = e.target.value;
                const fromDate = $('#cf7-date-from').val();
                
                if (fromDate && toDate && toDate < fromDate) {
                    $('#cf7-date-from').val(toDate);
                }
            });

            // Date preset buttons
            $(document).on('click', '.cf7-date-preset', (e) => {
                const range = $(e.target).data('range');
                this.setDateRange(range);
                
                // Update active state
                $('.cf7-date-preset').removeClass('active');
                $(e.target).addClass('active');
            });

            // Filter changes - fix status filter selector and add date filters
            $(document).on('change', '#cf7-status-filter, #cf7-date-from, #cf7-date-to', () => {
                this.currentPage = 1;
                this.loadSubmissions();
            });

            // Custom status filter display update
            $(document).on('change', '#cf7-status-filter', (e) => {
                this.updateStatusFilterDisplay(e.target);
            });

            // Bulk selection
            $(document).on('change', '.cf7-select-all', (e) => {
                const isChecked = e.target.checked;
                $('.cf7-submission-checkbox').prop('checked', isChecked);
                this.updateSelectedItems();
            });

            $(document).on('change', '.cf7-submission-checkbox', () => {
                this.updateSelectedItems();
            });

            // Bulk actions
            $(document).on('click', '.cf7-bulk-action-btn', (e) => {
                const action = $(e.target).data('action');
                this.performBulkAction(action);
            });

            // Pagination
            $(document).on('click', '.cf7-page-btn', (e) => {
                const page = $(e.target).data('page');
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadSubmissions();
                }
            });

            // Individual submission actions
            $(document).on('click', '.cf7-action-btn', (e) => {
                const $button = $(e.currentTarget);
                const action = $button.data('action');
                const submissionId = $button.closest('.cf7-submission-row').data('id');
                this.performSubmissionAction(action, submissionId);
            });

            // Real-time updates
            $(document).on('click', '.cf7-refresh-btn', () => {
                this.refreshAll();
            });

            // Export functionality
            $(document).on('click', '.cf7-export-btn', () => {
                this.exportSubmissions();
            });

            // Message actions
            $(document).on('click', '.cf7-mark-submission-read-btn', (e) => {
                e.preventDefault();
                const submissionId = $(e.target).closest('.cf7-mark-submission-read-btn').data('submission-id');
                this.markSubmissionRead(submissionId);
            });

            $(document).on('click', '.cf7-mark-all-read-btn', (e) => {
                e.preventDefault();
                this.markAllMessagesRead();
            });
            
            // Ensure all internal links open in same tab
            $(document).on('click', 'a[href*="post.php"], a[href*="admin.php"]', (e) => {
                // Remove target="_blank" if present and ensure same tab opening
                $(e.target).removeAttr('target');
            });
        }

        loadStats() {
            if (this.loadingStates.stats) return;
            
            this.loadingStates.stats = true;
            this.showStatsLoading();

            const data = {
                action: 'cf7_dashboard_get_stats',
                nonce: cf7_dashboard.nonce
            };

            console.log('Loading stats with data:', data);
            console.log('Using ajaxurl:', ajaxurl);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json'
            })
                .done((response) => {
                    console.log('Stats response:', response);
                    if (response && response.success) {
                        this.renderStats(response.data);
                    } else {
                        console.error('Stats error:', response);
                        this.showToast('Error loading stats: ' + (response?.data || 'Unknown error'), 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Stats AJAX failed:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
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

            console.log('Loading outstanding actions with data:', data);
            console.log('Using ajaxurl:', ajaxurl);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json'
            })
                .done((response) => {
                    console.log('Outstanding actions response:', response);
                    if (response && response.success) {
                        this.renderOutstandingActions(response.data);
                    } else {
                        console.error('Outstanding actions error:', response);
                        this.showToast('Error loading outstanding actions: ' + (response?.data || 'Unknown error'), 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Outstanding actions AJAX failed:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                    this.showToast('Failed to load outstanding actions', 'error');
                })
                .always(() => {
                    this.loadingStates.actions = false;
                    this.hideActionsLoading();
                });
        }

        loadSubmissions() {
            if (this.loadingStates.submissions) return;
            
            this.loadingStates.submissions = true;
            this.showSubmissionsLoading();

            const data = {
                action: 'cf7_dashboard_load_submissions',
                nonce: cf7_dashboard.nonce,
                page: this.currentPage,
                search: $('#cf7-search-input').val(),
                status: $('#cf7-status-filter').val(),
                date_from: $('#cf7-date-from').val(),
                date_to: $('#cf7-date-to').val(),
                orderby: $('.cf7-order-filter').val()
            };

            // Log the data being sent for debugging
            console.log('Loading submissions with filters:', {
                search: data.search,
                status: data.status,
                date_from: data.date_from,
                date_to: data.date_to,
                page: data.page
            });

            $.post(ajaxurl, data)
                .done((response) => {
                    console.log('Submissions response:', response);
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
            const statTypes = ['total', 'new', 'reviewed', 'awaiting-information', 'selected', 'rejected', 'unread_messages'];
            
            statTypes.forEach(type => {
                const $card = $(`.cf7-stat-card[data-type="${type}"]`);
                let $number = $card.find('.cf7-stat-number');
                
                // If the stat card structure was destroyed, rebuild it
                if ($number.length === 0) {
                    const iconMap = {
                        'total': 'dashicons-chart-bar',
                        'new': 'dashicons-star-filled',
                        'reviewed': 'dashicons-visibility',
                        'awaiting-information': 'dashicons-clock',
                        'selected': 'dashicons-yes-alt',
                        'rejected': 'dashicons-dismiss',
                        'unread_messages': 'dashicons-email'
                    };
                    
                    const titleMap = {
                        'total': 'Total Submissions',
                        'new': 'New',
                        'reviewed': 'Reviewed', 
                        'awaiting-information': 'Awaiting Information',
                        'selected': 'Selected',
                        'rejected': 'Rejected',
                        'unread_messages': 'Unread Messages'
                    };
                    
                    const iconClass = `${type} ${iconMap[type] ? iconMap[type].replace('dashicons-', '') : 'chart-bar'}`;
                    
                    $card.html(`
                        <div class="cf7-stat-header">
                            <div class="cf7-stat-icon ${type}">
                                <span class="dashicons ${iconMap[type] || 'dashicons-chart-bar'}"></span>
                            </div>
                            <div class="cf7-stat-content">
                                <h3>${titleMap[type] || type}</h3>
                                <div class="cf7-stat-number">0</div>
                            </div>
                        </div>
                    `);
                    $number = $card.find('.cf7-stat-number');
                }
                
                const value = stats[type] || 0;
                
                // Update the number
                $number.text(value);
                
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
            });
            
            // Update unread messages count in activity panel
            const unreadCount = stats.unread_messages || 0;
            $('#cf7-unread-messages-stat').text(unreadCount);
        }

        renderOutstandingActions(data) {
            const $container = $('#cf7-outstanding-actions');
            const actions = data.actions || [];
            const totalCount = data.total_count || 0;
            
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
                const overdueClass = action.is_overdue ? 'cf7-action-overdue' : '';
                
                html += `
                    <div class="cf7-action-item ${priorityClass} ${overdueClass}">
                        <div class="cf7-action-content">
                            <div class="cf7-action-header">
                                <h5 class="cf7-action-title">${this.escapeHtml(action.title)}</h5>
                                <span class="cf7-action-priority cf7-priority-${action.priority}">${action.priority}</span>
                            </div>
                            ${action.description ? `<p class="cf7-action-description">${this.escapeHtml(action.description)}</p>` : ''}
                            <div class="cf7-action-meta">
                                <span class="cf7-action-artist">👤 ${this.escapeHtml(action.artist_name)}</span>
                                ${action.due_date_formatted ? `<span class="cf7-action-due ${action.is_overdue ? 'overdue' : ''}">${action.due_date_formatted}</span>` : ''}
                                <span class="cf7-action-created">${action.created_at}</span>
                            </div>
                        </div>
                        <div class="cf7-action-actions">
                            <a href="${action.edit_link}" class="cf7-action-btn cf7-btn-primary" title="View Submission">
                                <span class="dashicons dashicons-visibility"></span>
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
            
            return `
                <div class="cf7-submission-row" data-id="${submission.id}">
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
                        <span class="cf7-status-dot"></span>
                        ${submission.status}
                    </div>
                </div>
            `;
        }

        buildEmptyState() {
            const searchTerm = $('#cf7-search-input').val();
            const statusFilter = $('#cf7-status-filter').val();
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
                    ${hasFilters ? `<button class="cf7-btn cf7-btn-ghost" onclick="$('#cf7-search-input, #cf7-status-filter, #cf7-date-from, #cf7-date-to').val('').trigger('change')">Clear All Filters</button>` : ''}
                </div>
            `;
        }

        renderPagination(pagination) {
            if (!pagination || pagination.total_pages <= 1) {
                $('.cf7-pagination').hide();
                return;
            }

            const $pagination = $('.cf7-pagination');
            const $info = $pagination.find('.cf7-pagination-info');
            const $buttons = $pagination.find('.cf7-pagination-buttons');

            // Update info
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
            $info.text(`Showing ${start}-${end} of ${pagination.total_items} submissions`);

            // Build pagination buttons
            let buttonsHtml = '';
            
            // Previous button
            if (pagination.current_page > 1) {
                buttonsHtml += `<button class="cf7-page-btn" data-page="${pagination.current_page - 1}">‹ Previous</button>`;
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
            }

            $buttons.html(buttonsHtml);
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
                            <div class="cf7-message-avatar">${submission.artist_name.charAt(0).toUpperCase()}</div>
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

            if (count > 0) {
                $bulkActions.slideDown(200);
                $selectedCount.text(`${count} item${count !== 1 ? 's' : ''} selected`);
            } else {
                $bulkActions.slideUp(200);
            }
        }

        performBulkAction(action) {
            if (this.selectedItems.size === 0) {
                this.showToast('Please select items first', 'info');
                return;
            }

            const confirmActions = ['delete', 'export'];
            if (confirmActions.includes(action)) {
                const actionName = action === 'delete' ? 'delete' : 'export';
                if (!confirm(`Are you sure you want to ${actionName} ${this.selectedItems.size} item(s)?`)) {
                    return;
                }
            }

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
                            window.open(response.data.download_url, '_blank');
                        }
                        
                        this.refreshAll();
                    } else {
                        this.showToast(response.data || 'Action failed', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to perform action', 'error');
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
                        this.refreshAll();
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
            const newStatus = prompt('Enter new status (new, reviewed, selected, rejected):');
            if (newStatus && ['new', 'reviewed', 'selected', 'rejected'].includes(newStatus)) {
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
                            this.refreshAll();
                        } else {
                            this.showToast(response.data || 'Failed to update status', 'error');
                        }
                    })
                    .fail(() => {
                        this.showToast('Failed to update status', 'error');
                    });
            }
        }

        exportSubmissions() {
            const data = {
                action: 'cf7_dashboard_export',
                nonce: cf7_dashboard.nonce,
                search: $('#cf7-search-input').val(),
                status: $('#cf7-status-filter').val(),
                date_from: $('#cf7-date-from').val(),
                date_to: $('#cf7-date-to').val()
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success && response.data.download_url) {
                        window.open(response.data.download_url, '_blank');
                        this.showToast('Export started - download will begin shortly', 'success');
                    } else {
                        this.showToast(response.data || 'Export failed', 'error');
                    }
                })
                .fail(() => {
                    this.showToast('Failed to export submissions', 'error');
                });
        }

        refreshAll() {
            this.loadStats();
            this.loadOutstandingActions();
            this.loadSubmissions();
            this.loadRecentMessages();
        }

        startPolling() {
            // Refresh data every 30 seconds
            setInterval(() => {
                if (!document.hidden) {
                    this.loadStats();
                    this.loadOutstandingActions();
                    this.loadRecentMessages();
                }
            }, 30000);
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
    }

    // Initialize dashboard when DOM is ready
    $(document).ready(function() {
        // Only initialize if we're on the dashboard page
        if ($('.cf7-modern-dashboard').length) {
            new CF7Dashboard();
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
                    // Refresh actions to update counts
                    this.loadOutstandingActions();
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
                    // Refresh actions and messages
                    this.loadOutstandingActions();
                    this.showToast('All messages marked as read', 'success');
                } else {
                    this.showToast('Failed to mark messages as read', 'error');
                }
            })
            .fail(() => {
                this.showToast('Failed to mark messages as read', 'error');
            });
    };

    CF7Dashboard.prototype.setDateRange = function(range) {
        const today = new Date();
        let fromDate = '';
        let toDate = '';

        switch(range) {
            case 'today':
                fromDate = toDate = today.toISOString().split('T')[0];
                break;
            case 'week':
                // Last 7 days (including today)
                const sevenDaysAgo = new Date(today);
                sevenDaysAgo.setDate(today.getDate() - 6);
                fromDate = sevenDaysAgo.toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
            case 'month':
                // Last 30 days (including today)
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 29);
                fromDate = thirtyDaysAgo.toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
            case 'clear':
                fromDate = toDate = '';
                break;
        }

        $('#cf7-date-from').val(fromDate);
        $('#cf7-date-to').val(toDate);
        
        this.currentPage = 1;
        this.loadSubmissions();
    };

    CF7Dashboard.prototype.updateFilterIndicators = function() {
        const $filterBar = $('.cf7-date-filter-bar');
        const hasDateFilter = $('#cf7-date-from').val() || $('#cf7-date-to').val();
        const hasStatusFilter = $('#cf7-status-filter').val();
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

    CF7Dashboard.prototype.updateStatusFilterDisplay = function(select) {
        const $select = $(select);
        const $display = $('.cf7-status-filter-display');
        const $icon = $display.find('.cf7-status-icon');
        const $text = $display.find('.cf7-status-text');
        
        const selectedOption = $select.find('option:selected');
        const value = selectedOption.val();
        const text = selectedOption.text();
        const icon = selectedOption.data('icon') || 'dashicons-category';
        const color = selectedOption.data('color') || '#718096';
        
        // Update icon
        $icon.removeClass().addClass('cf7-status-icon dashicons ' + icon);
        $icon.css('color', color);
        
        // Update text
        $text.text(text);
        
        // Update display status class
        $display.removeClass('status-new status-reviewed status-awaiting-information status-selected status-rejected');
        if (value) {
            $display.addClass('status-' + value);
        }
    };

    CF7Dashboard.prototype.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

})(jQuery);
