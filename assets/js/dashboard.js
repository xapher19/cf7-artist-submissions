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
                messages: false
            };

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadOutstandingActions();
            this.loadSubmissions();
            this.loadRecentMessages();
            this.startPolling();
        }

        bindEvents() {
            // Search functionality
            $(document).on('input', '.cf7-search-input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadSubmissions();
                }, 300);
            });

            // Filter changes
            $(document).on('change', '.cf7-filter-select', () => {
                this.currentPage = 1;
                this.loadSubmissions();
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

        loadOutstandingActions() {
            if (this.loadingStates.stats) return;
            
            this.loadingStates.stats = true;
            this.showActionsLoading();

            const data = {
                action: 'cf7_dashboard_get_outstanding_actions',
                nonce: cf7_dashboard.nonce
            };

            console.log('Loading outstanding actions with data:', data);

            $.post(ajaxurl, data)
                .done((response) => {
                    console.log('Outstanding actions response:', response);
                    if (response.success) {
                        this.renderOutstandingActions(response.data);
                    } else {
                        console.error('Outstanding actions error:', response.data);
                        this.showToast('Error loading outstanding actions: ' + (response.data || 'Unknown error'), 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Outstanding actions AJAX failed:', xhr, status, error);
                    this.showToast('Failed to load outstanding actions', 'error');
                })
                .always(() => {
                    this.loadingStates.stats = false;
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
                search: $('.cf7-search-input').val(),
                status: $('.cf7-status-filter').val(),
                orderby: $('.cf7-order-filter').val()
            };

            $.post(ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.renderSubmissions(response.data);
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
                            <a href="${action.edit_link}" class="cf7-action-btn cf7-btn-primary">View</a>
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
            const date = new Date(submission.date);
            const formattedDate = date.toLocaleDateString();
            const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            return `
                <div class="cf7-submission-row" data-id="${submission.id}">
                    <input type="checkbox" class="cf7-submission-checkbox" value="${submission.id}">
                    <div class="cf7-submission-info">
                        <div class="cf7-submission-title">
                            <a href="${submission.view_url}" target="_blank">${submission.title}</a>
                        </div>
                        <div class="cf7-submission-meta">
                            ${submission.email} • ID: ${submission.id}
                        </div>
                    </div>
                    <div class="cf7-submission-date">
                        <div class="cf7-submission-date-main">${formattedDate}</div>
                        <div class="cf7-submission-date-time">${formattedTime}</div>
                    </div>
                    <div class="cf7-status-badge ${submission.status}">
                        <span class="cf7-status-dot"></span>
                        ${submission.status}
                    </div>
                    <div class="cf7-submission-actions">
                        <button class="cf7-action-btn" data-action="view" title="View Submission">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="cf7-action-btn" data-action="edit" title="Edit Status">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>
                </div>
            `;
        }

        buildEmptyState() {
            return `
                <div class="cf7-loading-state">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📭</div>
                    <h3>No submissions found</h3>
                    <p>Try adjusting your search or filter criteria.</p>
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
                search: $('.cf7-search-input').val(),
                status: $('.cf7-status-filter').val()
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
            this.loadOutstandingActions();
            this.loadSubmissions();
            this.loadRecentMessages();
        }

        startPolling() {
            // Refresh data every 30 seconds
            setInterval(() => {
                if (!document.hidden) {
                    this.loadOutstandingActions();
                    this.loadRecentMessages();
                }
            }, 30000);
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
            $('.cf7-submissions-table').html(`
                <div class="cf7-loading-state">
                    <div class="cf7-loading-spinner"></div>
                    <div>Loading submissions...</div>
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

    CF7Dashboard.prototype.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

})(jQuery);
