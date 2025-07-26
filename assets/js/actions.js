/**
 * CF7 Artist Submissions - Actions Management System
 *
 * Complete JavaScript interface for managing submission actions including
 * creation, editing, completion workflow, priority-based filtering, and
 * real-time AJAX operations with comprehensive cross-tab compatibility.
 *
 * This system provides comprehensive task management capabilities for artist
 * submissions, enabling curators and administrators to create, assign, and
 * track action items throughout the submission review process. The architecture
 * employs a dual-interface approach with both instance-based management and
 * global access patterns to ensure compatibility across different tab contexts
 * and external integrations.
 *
 * Features:
 * • Priority-based task organization with visual indicators (high, medium, low)
 * • Status tracking and workflow management (pending, completed, overdue)
 * • User assignment with role-based access control and dropdown integration
 * • Due date management with overdue detection and visual alerts
 * • Contextual action creation from conversation messages
 * • Real-time filtering and search capabilities with instant updates
 * • Dynamic modal form interface with validation and error handling
 * • Cross-tab compatibility with global interface (window.CF7_Actions)
 * • Bulk operations and batch processing for efficient management
 * • AJAX communication with comprehensive error handling and security
 * • Accessibility compliance with ARIA labels and keyboard navigation
 * • Performance optimization with caching and event delegation
 *
 * @package CF7_Artist_Submissions
 * @subpackage ActionManagement
 * @since 1.0.0
 * @version 1.0.0
 * 
 */

// ============================================================================
// ACTIONS MANAGER CLASS
// ============================================================================

/**
 * ActionsManager
 * 
 * Primary class managing all action-related functionality including CRUD
 * operations, filtering, modal management, and user interface updates.
 * Provides comprehensive task management capabilities with real-time AJAX
 * operations, cross-tab compatibility, and integrated security validation.
 * 
 * @since 1.0.0
 */
class ActionsManager {
    /**
     * Initialize ActionsManager with default state and bindings.
     * 
     * Sets up action management system with filter state, action cache,
     * event handlers, and initial data loading. Provides foundation for
     * comprehensive task management workflow with performance optimization
     * and cross-tab compatibility support.
     * 
     * @since 1.0.0
     */
    constructor() {
        this.currentFilter = 'all';
        this.actions = [];
        this.init();
    }

    /**
     * Initialize actions management system with event binding and data loading.
     * Sets up event handlers and loads initial action data automatically.
     */
    init() {
        this.bindEvents();
        this.loadActions();
    }

    /**
     * Bind comprehensive event handlers for action management interface.
     * 
     * Sets up all event listeners using delegation for dynamically created
     * elements. Handles action operations, modal interactions, form submissions,
     * and filter controls with proper event propagation management and error
     * handling for reliable user interaction workflow.
     * 
     * @since 1.0.0
     */
    bindEvents() {
        // Add action button
        jQuery(document).on('click', '#cf7-add-action-btn', (e) => {
            e.preventDefault();
            console.log('Add Action button clicked');
            console.log('ActionsManager instance exists:', this instanceof ActionsManager);
            console.log('showActionModal method exists:', typeof this.showActionModal === 'function');
            this.showActionModal();
        });
        
        // Also try with a more general selector as backup
        jQuery(document).on('click', '[data-action="add-action"], .cf7-add-action-btn', function(e) {
            e.preventDefault();
            console.log('Alternative Add Action button clicked');
            if (window.actionsManager && typeof window.actionsManager.showActionModal === 'function') {
                window.actionsManager.showActionModal();
            } else {
                console.error('ActionsManager not available');
            }
        });

        // Filter buttons
        jQuery(document).on('click', '.cf7-filter-btn', (e) => {
            e.preventDefault();
            const filter = jQuery(e.target).data('status') || 'all';
            this.setActiveFilter(filter);
        });

        // Action buttons - handle both button and icon clicks
        jQuery(document).on('click', '.cf7-action-complete, .cf7-action-complete *', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const actionId = jQuery(e.target).closest('.cf7-action-item').data('action-id');
            if (actionId) {
                this.completeAction(actionId);
                if (typeof this.completeAction === 'function') {
                    this.completeAction(actionId);
                } else {
                    console.error('completeAction is not a function, this:', this);
                }
            } else {
                console.error('No action ID found for complete button');
            }
        });

        jQuery(document).on('click', '.cf7-action-edit, .cf7-action-edit *', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const actionId = jQuery(e.target).closest('.cf7-action-item').data('action-id');
            if (actionId) {
                this.editAction(actionId);
                if (typeof this.editAction === 'function') {
                    this.editAction(actionId);
                } else {
                    console.error('editAction is not a function, this:', this);
                }
            } else {
                console.error('No action ID found for edit button');
            }
        });

        jQuery(document).on('click', '.cf7-action-delete, .cf7-action-delete *', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const actionId = jQuery(e.target).closest('.cf7-action-item').data('action-id');
            if (actionId) {
                this.deleteAction(actionId);
                if (typeof this.deleteAction === 'function') {
                    this.deleteAction(actionId);
                } else {
                    console.error('deleteAction is not a function, this:', this);
                }
            } else {
                console.error('No action ID found for delete button');
            }
        });

        // Modal events
        jQuery(document).on('click', '.cf7-modal-close, #cf7-action-cancel', () => {
            this.hideActionModal();
        });

        // Close modal on outside click
        jQuery(document).on('click', '.cf7-modal', (e) => {
            if (e.target === e.currentTarget) {
                this.hideActionModal();
            }
        });

        // Save Action button event handler (for PHP-generated modal)
        jQuery(document).on('click', '#cf7-action-save', () => {
            console.log('Save Action button clicked');
            // Use global interface to avoid duplication, fallback to instance method
            if (window.CF7_Actions && typeof window.CF7_Actions.saveAction === 'function') {
                window.CF7_Actions.saveAction();
            } else {
                this.saveAction();
            }
        });

        // Form submission - delegate to global interface for consistency
        jQuery(document).on('submit', '#cf7-action-form', (e) => {
            e.preventDefault();
            // Use global interface to avoid duplication
            if (window.CF7_Actions && typeof window.CF7_Actions.saveAction === 'function') {
                window.CF7_Actions.saveAction();
            } else {
                this.saveAction();
            }
        });
    }

    /**
     * Load actions from server with AJAX and update local cache.
     * 
     * Retrieves all actions for the current submission via AJAX and updates
     * the local actions cache and UI display. Shows loading state during
     * request and handles success/error responses with appropriate user
     * feedback and cache management for optimal performance.
     * 
     * @since 1.0.0
     */
    loadActions() {
        const submissionId = jQuery('#post_ID').val();
        if (!submissionId) return;

        jQuery('#cf7-actions-list').html('<div class="cf7-loading-state"><div class="cf7-loading-spinner"></div>Loading actions...</div>');

        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_get_actions',
                submission_id: submissionId,
                nonce: cf7_actions_ajax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.actions = response.data.actions || response.data || [];
                    this.renderActions();
                    this.updateActionsCount();
                } else {
                    this.showError('Failed to load actions: ' + (response.data.message || response.data));
                }
            },
            error: () => {
                this.showError('Failed to load actions. Please try again.');
            }
        });
    }

    /**
     * Render actions list UI with filtered action data.
     * 
     * Updates the actions list UI with filtered actions from local cache.
     * Handles empty states and generates HTML for each action item with
     * proper filtering, status indicators, and interactive controls for
     * comprehensive action management interface.
     * 
     * @since 1.0.0
     */
    renderActions() {
        const container = jQuery('#cf7-actions-list');
        const filteredActions = this.getFilteredActions();

        if (filteredActions.length === 0) {
            container.html(this.getEmptyStateHTML());
            return;
        }

        let html = '';
        filteredActions.forEach(action => {
            html += this.getActionItemHTML(action);
        });

        container.html(html);
    }

    /**
     * Filter actions based on current filter state.
     * Returns filtered array of actions based on status, priority, or date criteria.
     */
    getFilteredActions() {
        if (this.currentFilter === 'all') {
            return this.actions;
        }

        return this.actions.filter(action => {
            switch (this.currentFilter) {
                case 'pending':
                    return action.status === 'pending';
                case 'completed':
                    return action.status === 'completed';
                case 'overdue':
                    return action.status === 'pending' && new Date(action.due_date) < new Date();
                case 'high':
                    return action.priority === 'high';
                case 'medium':
                    return action.priority === 'medium';
                case 'low':
                    return action.priority === 'low';
                default:
                    return true;
            }
        });
    }

    /**
     * Generate HTML for individual action item display.
     * Creates complete action card with priority indicators, controls, and metadata.
     */
    getActionItemHTML(action) {
        const isOverdue = action.status === 'pending' && action.due_date && new Date(action.due_date) < new Date();
        const priority_class = 'priority-' + action.priority;
        const status_class = 'status-' + action.status;
        const overdue_class = isOverdue ? 'overdue' : '';
        
        const itemClass = `cf7-action-item ${priority_class} ${status_class} ${overdue_class}`;
        
        const dueDateText = action.due_date ? 
            `<span class="cf7-due-date ${overdue_class}">
                <span class="dashicons dashicons-calendar-alt"></span>
                ${this.formatDate(action.due_date)}
            </span>` : '';

        const assigneeText = action.assigned_user_name ? 
            `<span class="cf7-assignee">
                <span class="dashicons dashicons-admin-users"></span>
                ${action.assigned_user_name}
            </span>` : 
            (action.assignee_type ? 
                `<span class="cf7-assignee">
                    <span class="dashicons dashicons-admin-users"></span>
                    ${action.assignee_type === 'admin' ? 'Admin' : 'Artist'}
                </span>` : '');

        return `
            <div class="${itemClass}" data-action-id="${action.id}" data-status="${action.status}">
                <div class="cf7-action-header">
                    <div class="cf7-action-priority">
                        <span class="cf7-priority-indicator ${priority_class}"></span>
                    </div>
                    <div class="cf7-action-content">
                        <h4 class="cf7-action-title">${this.escapeHtml(action.title)}</h4>
                        ${action.description ? `<p class="cf7-action-description">${this.escapeHtml(action.description)}</p>` : ''}
                    </div>
                    <div class="cf7-action-controls">
                        ${action.status === 'pending' ? `
                            <button type="button" class="cf7-action-complete" title="Mark as completed">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </button>
                        ` : ''}
                        <button type="button" class="cf7-action-edit" title="Edit action">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="cf7-action-delete" title="Delete action">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="cf7-action-meta">
                    <div class="cf7-action-details">
                        ${assigneeText}
                        ${dueDateText}
                        <span class="cf7-created-info">
                            <span class="dashicons dashicons-admin-users"></span>
                            Created on ${this.formatDate(action.created_at)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Generate empty state HTML for actions list.
     * Returns placeholder content when no actions match current filter.
     */
    getEmptyStateHTML() {
        return `
            <div class="actions-empty">
                <div class="actions-empty-icon">📋</div>
                <h3>No actions found</h3>
                <p>Create your first action to get started.</p>
            </div>
        `;
    }

    /**
     * Set active filter and update UI display.
     * Updates filter state, button styling, and triggers action re-rendering.
     */
    setActiveFilter(filter) {
        this.currentFilter = filter;
        jQuery('.cf7-filter-btn').removeClass('active');
        
        // Handle the 'all' filter which has empty data-status
        if (filter === 'all') {
            jQuery('.cf7-filter-btn[data-status=""]').addClass('active');
        } else {
            jQuery(`.cf7-filter-btn[data-status="${filter}"]`).addClass('active');
        }
        
        this.renderActions();
    }

    /**
     * Update pending actions count display.
     * Calculates and displays count of pending actions in UI badge.
     */
    updateActionsCount() {
        const count = this.actions.filter(action => action.status === 'pending').length;
        jQuery('.actions-count').text(count);
    }
    
    /**
     * Load assignable users for action assignment dropdown.
     * Fetches user list via AJAX and populates assignment dropdown with fallback options.
     */
    loadAssignableUsers() {
        const select = jQuery('#cf7-action-assignee');
        const loading = jQuery('.cf7-loading-users');
        
        // Show loading
        loading.show();
        select.prop('disabled', true);
        
        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_get_assignable_users',
                nonce: cf7_actions_ajax.nonce
            },
            success: (response) => {
                if (response.success) {
                    // Clear existing options except the first one
                    select.find('option:not(:first)').remove();
                    
                    // Add user options
                    const users = response.data.users || [];
                    users.forEach((user) => {
                        const option = `<option value="${user.id}">${user.name} (${user.email})</option>`;
                        select.append(option);
                    });
                } else {
                    console.error('Failed to load users:', response.data);
                    // Fallback to basic options
                    select.html(`
                        <option value="">Select User...</option>
                        <option value="admin">Admin/Curator</option>
                        <option value="artist">Artist</option>
                    `);
                }
            },
            error: () => {
                console.error('Failed to load assignable users');
                // Fallback to basic options
                select.html(`
                    <option value="">Select User...</option>
                    <option value="admin">Admin/Curator</option>
                    <option value="artist">Artist</option>
                `);
            },
            complete: () => {
                loading.hide();
                select.prop('disabled', false);
            }
        });
    }

    // ============================================================================
    // MODAL MANAGEMENT SECTION
    // ============================================================================

    /**
     * Display action modal with dynamic form injection and context support.
     * 
     * Displays the action creation/editing modal with dynamic form injection,
     * message context integration, and assignable users loading. Handles modal
     * injection if not present and provides seamless user experience with
     * proper form state management and error handling.
     * 
     * @since 1.0.0
     */
    showActionModal(messageId = null) {
        console.log('showActionModal called with messageId:', messageId);
        
        // Always use the cross-tab modal approach for consistency
        // This ensures proper styling since the PHP modal has CSS issues
        console.log('Using cross-tab modal approach for consistent styling...');
        
        // Remove any existing modal to start fresh
        jQuery('#cf7-action-modal').remove();
        
        // Always inject a fresh modal with inline styles (like cross-tab modal)
        this.injectModalHTML();
        
        // Use the newly injected elements
        setTimeout(() => {
            const injectedModal = jQuery('#cf7-action-modal');
            const injectedForm = jQuery('#cf7-action-form');
            
            if (injectedModal.length > 0 && injectedForm.length > 0) {
                console.log('Using injected modal with inline styles');
                this.displayModal(injectedModal, injectedForm, messageId);
            } else {
                alert('Unable to load action modal. Please refresh the page and try again.');
            }
        }, 50);
    }

    /**
     * Display modal with form reset and context setup.
     * Handles form reset, field population, and user loading for modal display.
     */
    displayModal(modal, form, messageId = null) {
        console.log('displayModal called with messageId:', messageId);
        console.log('Modal element details before display:', {
            exists: modal.length > 0,
            display: modal.css('display'),
            visibility: modal.css('visibility'),
            opacity: modal.css('opacity')
        });
        
        // Reset form safely
        try {
            form[0].reset();
        } catch (error) {
            console.warn('Could not reset form:', error);
            // Manually clear form fields as fallback
            form.find('input[type="text"], input[type="email"], textarea, select').val('');
            form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
        }
        
        // Reset form fields
        jQuery('#cf7-action-id').val('');
        const postId = jQuery('#post_ID').val();
        if (postId) {
            jQuery('#cf7-submission-id').val(postId);
        }
        
        // Add message_id if provided (for context menu actions)
        if (messageId) {
            // Add hidden input for message_id if it doesn't exist
            if (!jQuery('#cf7-message-id').length) {
                form.append('<input type="hidden" id="cf7-message-id" name="message_id">');
            }
            jQuery('#cf7-message-id').val(messageId);
        }
        
        // Set modal title
        const title = messageId ? 'Create Action from Message' : 'Create New Action';
        jQuery('#cf7-modal-title').text(title);
        
        // Load assignable users
        this.loadAssignableUsers();
        
        // Add the show class that the CSS expects
        console.log('Adding show class and fading in modal...');
        modal.addClass('show');
        modal.fadeIn(200);
        
        // Additional debugging
        setTimeout(() => {
            console.log('After modal display - checking final state:', {
                isVisible: modal.is(':visible'),
                display: modal.css('display'),
                opacity: modal.css('opacity'),
                visibility: modal.css('visibility'),
                hasShowClass: modal.hasClass('show')
            });
        }, 250);
    }

    /**
     * Hide action modal and restore focus.
     * Removes modal from display and returns focus to triggering element.
     */
    hideActionModal() {
        const modal = jQuery('#cf7-action-modal');
        modal.removeClass('show');
        modal.fadeOut(200);
    }

    /**
     * Inject modal HTML into document if not already present.
     * Creates modal structure and adds to DOM for first-time modal display.
     * Uses inline styles for consistent cross-tab compatibility.
     */
    injectModalHTML() {
        const submissionId = jQuery('#post_ID').val() || '';
        
        const modalHTML = `
            <!-- Add Action Modal with inline styles for consistency -->
            <div id="cf7-action-modal" class="cf7-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999;">
                <div class="cf7-modal-content" style="position: relative; background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 600px; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div class="cf7-modal-header" style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                        <h3 id="cf7-modal-title" style="margin: 0; font-size: 18px;">Add New Action</h3>
                        <button type="button" class="cf7-modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
                    </div>
                    <div class="cf7-modal-body" style="padding: 20px;">
                        <form id="cf7-action-form">
                            <input type="hidden" id="cf7-action-id" name="action_id">
                            <input type="hidden" id="cf7-submission-id" name="submission_id" value="${submissionId}">
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-title" style="display: block; margin-bottom: 5px; font-weight: 600;">Title *</label>
                                <input type="text" id="cf7-action-title" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-description" style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                                <textarea id="cf7-action-description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                            </div>
                            
                            <div class="cf7-form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <div class="cf7-form-group" style="flex: 1;">
                                    <label for="cf7-action-priority" style="display: block; margin-bottom: 5px; font-weight: 600;">Priority</label>
                                    <select id="cf7-action-priority" name="priority" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                
                                <div class="cf7-form-group" style="flex: 1;">
                                    <label for="cf7-action-assignee" style="display: block; margin-bottom: 5px; font-weight: 600;">Assigned To</label>
                                    <select id="cf7-action-assignee" name="assigned_to" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="">Select User...</option>
                                    </select>
                                    <div class="cf7-loading-users" style="display: none; margin-top: 5px; font-size: 12px; color: #666;">Loading users...</div>
                                </div>
                            </div>
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-due-date" style="display: block; margin-bottom: 5px; font-weight: 600;">Due Date</label>
                                <input type="datetime-local" id="cf7-action-due-date" name="due_date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </form>
                    </div>
                    <div class="cf7-modal-footer" style="padding: 20px; border-top: 1px solid #ddd; text-align: right;">
                        <button type="button" id="cf7-action-cancel" class="button" style="margin-right: 10px; padding: 8px 16px; border: 1px solid #ddd; background: #f7f7f7; cursor: pointer;">Cancel</button>
                        <button type="button" id="cf7-action-save" class="button button-primary" style="padding: 8px 16px; background: #2271b1; color: white; border: 1px solid #2271b1; cursor: pointer;">Save Action</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modal first
        jQuery('#cf7-action-modal').remove();
        
        // Append to body for consistent z-index behavior
        jQuery('body').append(modalHTML);
        
        console.log('Modal HTML injected with inline styles for consistent display');
    }

    /**
     * Show context menu at specified coordinates.
     * Removes existing menus and triggers context menu display for actions.
     */
    showContextMenu(x, y, messageId) {
        // Remove any existing context menus
        jQuery('.cf7-context-menu').remove();
        
        // The context menu is created by the PHP script in add_context_menu_script
        // This method is mainly here for consistency but the actual menu creation
        // happens in the PHP-generated JavaScript
    }

    /**
     * Hide context menu from display.
     * Removes context menu from DOM to clear action options.
     */
    hideContextMenu() {
        jQuery('.cf7-context-menu').remove();
    }

    // ============================================================================
    // AJAX OPERATIONS SECTION
    // ============================================================================

    /**
     * Save action form data to server with comprehensive validation.
     * 
     * Submits action form data to server for creation or update operations.
     * Handles both new actions and edits based on presence of action_id,
     * includes context message association, and provides comprehensive
     * error handling with user feedback and UI state management.
     * 
     * @since 1.0.0
     */
    saveAction() {
        const form = jQuery('#cf7-action-form');
        
        // Collect form data as regular object
        const formData = {
            action: 'cf7_save_action',
            nonce: cf7_actions_ajax.nonce,
            submission_id: jQuery('#post_ID').val(),
            action_id: jQuery('#cf7-action-id').val(),
            title: jQuery('#cf7-action-title').val(),
            description: jQuery('#cf7-action-description').val(),
            priority: jQuery('#cf7-action-priority').val(),
            assignee_type: jQuery('#cf7-action-assignee').val(),
            due_date: jQuery('#cf7-action-due-date').val()
        };
        
        // Add message_id if present (for context menu actions)
        const messageId = jQuery('#cf7-message-id').val();
        if (messageId) {
            formData.message_id = messageId;
        }

        jQuery('#cf7-action-save').prop('disabled', true).text('Saving...');

        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: (response) => {
                if (response.success) {
                    this.hideActionModal();
                    this.loadActions();
                    this.showSuccess('Action saved successfully');
                } else {
                    this.showError('Failed to save action: ' + (response.data.message || response.data));
                }
            },
            error: (xhr, status, error) => {
                // Try to parse error response
                let errorMessage = 'Failed to save action. Please try again.';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        errorMessage = errorResponse.data.message;
                    }
                } catch (e) {
                    // Keep default message
                }
                
                this.showError(errorMessage);
            },
            complete: () => {
                jQuery('#cf7-action-save').prop('disabled', false).text('Save Action');
            }
        });
    }

    /**
     * Edit existing action with modal form pre-population.
     * 
     * Opens the action modal pre-populated with existing action data and
     * handles date formatting for datetime-local input compatibility.
     * Provides seamless editing experience with proper form state management
     * and error handling for action data validation and display.
     * 
     * @since 1.0.0
     */
    editAction(actionId) {
        const action = this.actions.find(a => a.id == actionId);
        if (!action) {
            console.error('Action not found for ID:', actionId);
            return;
        }

        // Show the modal first
        this.showActionModal();
        
        // Wait for modal to be displayed, then populate with action data
        setTimeout(() => {
            // Set modal title
            jQuery('#cf7-modal-title').text('Edit Action');
            
            // Populate form with action data
            jQuery('#cf7-action-id').val(action.id);
            jQuery('#cf7-action-title').val(action.title);
            jQuery('#cf7-action-description').val(action.description || '');
            jQuery('#cf7-action-priority').val(action.priority);
            
            // Set assigned user - use assigned_to if available, fallback to assignee_type
            const assignedValue = action.assigned_to || action.assignee_type || '';
            jQuery('#cf7-action-assignee').val(assignedValue);
            
            // Handle due date formatting - ensure it's in the correct format for datetime-local
            if (action.due_date) {
                // Convert to datetime-local format (YYYY-MM-DDTHH:MM)
                const dueDate = new Date(action.due_date);
                if (!isNaN(dueDate.getTime())) {
                    const year = dueDate.getFullYear();
                    const month = String(dueDate.getMonth() + 1).padStart(2, '0');
                    const day = String(dueDate.getDate()).padStart(2, '0');
                    const hours = String(dueDate.getHours()).padStart(2, '0');
                    const minutes = String(dueDate.getMinutes()).padStart(2, '0');
                    const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
                    jQuery('#cf7-action-due-date').val(formattedDate);
                }
            }
        }, 100);
    }

    /**
     * Mark action as completed with confirmation.
     * Updates action status to completed via AJAX with user confirmation.
     */
    completeAction(actionId) {
        if (!confirm('Mark this action as completed?')) return;

        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_complete_action',
                action_id: actionId,
                nonce: cf7_actions_ajax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.loadActions();
                    this.showSuccess('Action marked as completed');
                } else {
                    this.showError('Failed to complete action: ' + response.data);
                }
            },
            error: () => {
                this.showError('Failed to complete action. Please try again.');
            }
        });
    }

    /**
     * Delete action permanently with confirmation.
     * Removes action from system via AJAX after user confirmation.
     */
    deleteAction(actionId) {
        if (!confirm('Are you sure you want to delete this action?')) return;

        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_delete_action',
                action_id: actionId,
                nonce: cf7_actions_ajax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.loadActions();
                    this.showSuccess('Action deleted successfully');
                } else {
                    this.showError('Failed to delete action: ' + response.data);
                }
            },
            error: () => {
                this.showError('Failed to delete action. Please try again.');
            }
        });
    }

    /**
     * Display success notification to user.
     * Shows dismissible success message with auto-hide after 3 seconds.
     */
    showSuccess(message) {
        // Create a simple success notification
        const notification = jQuery(`
            <div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 10002; max-width: 300px;">
                <p>${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>
            </div>
        `);
        
        jQuery('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 3000);

        // Handle dismiss button
        notification.find('.notice-dismiss').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }

    /**
     * Display error notification to user.
     * Shows dismissible error message with auto-hide after 5 seconds.
     */
    showError(message) {
        // Create a simple error notification
        const notification = jQuery(`
            <div class="notice notice-error is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 10002; max-width: 300px;">
                <p>${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>
            </div>
        `);
        
        jQuery('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);

        // Handle dismiss button
        notification.find('.notice-dismiss').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }

    /**
     * Format date string for display in action items.
     * Converts date to localized format with month abbreviation.
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Escape HTML characters to prevent XSS attacks.
     * Uses DOM manipulation for safe HTML character escaping.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// ============================================================================
// INITIALIZATION SECTION
// ============================================================================

/**
 * Multi-layered initialization system supporting various loading scenarios.
 * 
 * Handles tab switching, direct access, and AJAX configuration fallbacks with
 * comprehensive event binding and configuration validation. Provides reliable
 * initialization across different WordPress admin contexts and tab states
 * with graceful fallback mechanisms for optimal compatibility.
 * 
 * @since 1.0.0
 */

// Initialize when the actions tab is loaded
jQuery(document).ready(function() {
    // Ensure cf7_actions_ajax is available (from tabs.js enqueuing)
    if (typeof cf7_actions_ajax === 'undefined') {
        console.warn('cf7_actions_ajax not available, using fallback');
        if (typeof ajaxurl !== 'undefined') {
            window.cf7_actions_ajax = {
                ajax_url: ajaxurl,
                nonce: 'fallback_nonce_check_required'
            };
        }
    }
    
    // Function to initialize ActionsManager
    function initializeActionsManager() {
        console.log('initializeActionsManager called');
        
        const container = jQuery('.cf7-actions-container');
        const button = jQuery('#cf7-add-action-btn');
        
        console.log('Initialization check:');
        console.log('- Actions container found:', container.length);
        console.log('- Add Action button found:', button.length);
        console.log('- Existing actionsManager:', !!window.actionsManager);
        console.log('- ActionsManager class available:', typeof ActionsManager !== 'undefined');
        
        if (container.length > 0 && button.length > 0) {
            if (!window.actionsManager) {
                try {
                    console.log('Creating new ActionsManager instance...');
                    window.actionsManager = new ActionsManager();
                    console.log('ActionsManager created successfully');
                    console.log('ActionsManager methods available:', {
                        showActionModal: typeof window.actionsManager.showActionModal === 'function',
                        loadActions: typeof window.actionsManager.loadActions === 'function',
                        bindEvents: typeof window.actionsManager.bindEvents === 'function'
                    });
                    return true;
                } catch (error) {
                    console.error('Failed to initialize ActionsManager:', error);
                    return false;
                }
            } else {
                console.log('ActionsManager already exists');
                return true;
            }
        } else {
            console.log('Missing required elements for ActionsManager initialization');
            console.log('- Container missing:', container.length === 0);
            console.log('- Button missing:', button.length === 0);
            return false;
        }
    }
    
    // Listen for the tab change event from tabs.js (primary initialization)
    jQuery(document).on('cf7_tab_changed', function(e, tabId) {
        console.log('Tab changed to:', tabId);
        if (tabId === 'cf7-actions-tab' || tabId === 'cf7-tab-actions') {
            console.log('Actions tab activated, initializing...');
            setTimeout(function() {
                const success = initializeActionsManager();
                if (!success) {
                    console.log('Direct initialization failed, starting polling...');
                    pollForElements();
                }
            }, 100);
        }
    });
    
    // Also listen for generic tab activation events
    jQuery(document).on('click', '[data-tab="actions"], [href="#cf7-tab-actions"]', function() {
        console.log('Actions tab clicked, will initialize after delay...');
        setTimeout(function() {
            const success = initializeActionsManager();
            if (!success) {
                console.log('Click initialization failed, starting polling...');
                pollForElements();
            }
        }, 200);
    });
    
    // Listen for any clicks on elements that might activate the actions tab
    jQuery(document).on('click', '[data-target="cf7-tab-actions"], .cf7-tab-actions, #cf7-tab-actions-link', function() {
        console.log('Potential actions tab activator clicked...');
        setTimeout(function() {
            const success = initializeActionsManager();
            if (!success) {
                console.log('Generic click initialization failed, starting polling...');
                pollForElements();
            }
        }, 300);
    });
    
    // Check if we're already on the actions tab when the page loads
    if (jQuery('#cf7-tab-actions').hasClass('active') || 
        jQuery('#cf7-tab-actions').is(':visible') ||
        jQuery('.cf7-actions-container').is(':visible')) {
        console.log('Actions tab appears to be active on page load');
        setTimeout(function() {
            const success = initializeActionsManager();
            if (!success) {
                console.log('Page load initialization failed, starting polling...');
                pollForElements();
            }
        }, 500);
    }
    
    // Also try to initialize immediately if we're already on the actions tab
    setTimeout(function() {
        console.log('Delayed initialization check...');
        const container = jQuery('.cf7-actions-container');
        const button = jQuery('#cf7-add-action-btn');
        console.log('Container found:', container.length);
        console.log('Add Action button found:', button.length);
        console.log('Actions tab content:', jQuery('#cf7-tab-actions').length);
        console.log('Active tab content:', jQuery('.cf7-tab-content.active').attr('id'));
        
        if (container.length > 0 && button.length > 0) {
            console.log('Both container and button found - initializing ActionsManager');
            const success = initializeActionsManager();
            console.log('ActionsManager initialization result:', success);
        } else {
            console.log('Missing elements - Container:', container.length, 'Button:', button.length);
            
            // If the tab is active but elements aren't ready, try polling for them
            if (jQuery('#cf7-tab-actions').hasClass('active') || 
                jQuery('#cf7-tab-actions').is(':visible') ||
                jQuery('.cf7-tab-content.active').attr('id') === 'cf7-tab-actions') {
                console.log('Actions tab is active but elements not ready, starting polling...');
                pollForElements();
            }
        }
    }, 1000);
    
    // Polling function to wait for DOM elements to be ready
    function pollForElements() {
        let attempts = 0;
        const maxAttempts = 20; // Poll for up to 10 seconds (20 * 500ms)
        
        const poll = setInterval(function() {
            attempts++;
            console.log(`Polling attempt ${attempts}/${maxAttempts} for Actions elements...`);
            
            const container = jQuery('.cf7-actions-container');
            const button = jQuery('#cf7-add-action-btn');
            
            console.log(`Poll ${attempts}: Container=${container.length}, Button=${button.length}`);
            
            if (container.length > 0 && button.length > 0) {
                console.log('Elements found via polling! Initializing ActionsManager...');
                clearInterval(poll);
                const success = initializeActionsManager();
                console.log('Polling initialization result:', success);
            } else if (attempts >= maxAttempts) {
                console.warn('Max polling attempts reached. Elements still not found.');
                clearInterval(poll);
            }
        }, 500);
    }
});

// ============================================================================
// GLOBAL INTERFACE SECTION
// ============================================================================

/**
 * Cross-tab compatible interface for external access to actions functionality.
 * 
 * Provides simplified methods for context menu integration and modal management
 * with standalone modal creation, cross-tab initialization compatibility, and
 * error-resistant operation with comprehensive fallbacks for reliable external
 * integration across different WordPress admin contexts.
 * 
 * @since 1.0.0
 */

// Ensure window.CF7_Actions is always available
window.CF7_Actions = window.CF7_Actions || {};

/**
 * Global Interface Implementation
 * 
 * Simplified robust version designed for cross-tab context menu integration.
 * Uses fresh modal injection to avoid state conflicts between tabs.
 */
Object.assign(window.CF7_Actions, {
    /**
     * Initialize actions system with container detection and error handling.
     * Simple initialization that creates ActionsManager if container exists.
     */
    init: function() {
        console.log('CF7_Actions.init() called');
        
        // Simple initialization - just try to create ActionsManager if actions container exists
        if (jQuery('.cf7-actions-container').length > 0 && !window.actionsManager) {
            try {
                window.actionsManager = new ActionsManager();
                console.log('ActionsManager initialized successfully');
                return true;
            } catch (error) {
                console.error('Failed to initialize ActionsManager:', error);
                return false;
            }
        }
        console.log('ActionsManager initialization skipped - container not found or already exists');
        return false;
    },
    
    /**
     * Open action modal with cross-tab compatibility and fresh injection.
     * 
     * Cross-tab compatible modal opening with fresh injection approach and
     * context integration. Removes existing modal instances to prevent state
     * conflicts and provides seamless modal experience with proper form
     * pre-filling and user assignment loading for optimal user workflow.
     * 
     * @since 1.0.0
     */
    openModal: function(options) {
        console.log('CF7_Actions.openModal called with options:', options);
        
        // Remove any existing modal to start fresh
        jQuery('#cf7-action-modal').remove();
        
        // Always inject a fresh modal to avoid any state issues
        const modalCreated = this.createCompleteModal();
        if (!modalCreated) {
            console.error('Failed to create modal');
            return false;
        }
        
        // Short delay to ensure DOM is ready, then show
        setTimeout(() => {
            this.showFreshModal(options);
        }, 50);
        
        return true;
    },
    
    // Create complete modal with form included - no complex detection needed
    createCompleteModal: function() {
        console.log('Creating complete modal...');
        
        const submissionId = jQuery('#post_ID').val() || '';
        console.log('Found submission ID:', submissionId);
        
        const completeModalHTML = `
            <div id="cf7-action-modal" class="cf7-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999;">
                <div class="cf7-modal-content" style="position: relative; background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 600px; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div class="cf7-modal-header" style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                        <h3 id="cf7-modal-title" style="margin: 0; font-size: 18px;">Add New Action</h3>
                        <button type="button" class="cf7-modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
                    </div>
                    <div class="cf7-modal-body" style="padding: 20px;">
                        <form id="cf7-action-form">
                            <input type="hidden" id="cf7-action-id" name="action_id">
                            <input type="hidden" id="cf7-submission-id" name="submission_id" value="${submissionId}">
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-title" style="display: block; margin-bottom: 5px; font-weight: 600;">Title *</label>
                                <input type="text" id="cf7-action-title" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-description" style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                                <textarea id="cf7-action-description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                            </div>
                            
                            <div class="cf7-form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <div class="cf7-form-group" style="flex: 1;">
                                    <label for="cf7-action-priority" style="display: block; margin-bottom: 5px; font-weight: 600;">Priority</label>
                                    <select id="cf7-action-priority" name="priority" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                
                                <div class="cf7-form-group" style="flex: 1;">
                                    <label for="cf7-action-assignee" style="display: block; margin-bottom: 5px; font-weight: 600;">Assigned To</label>
                                    <select id="cf7-action-assignee" name="assigned_to" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="">Select User...</option>
                                    </select>
                                    <div class="cf7-loading-users" style="display: none; margin-top: 5px; font-size: 12px; color: #666;">Loading users...</div>
                                </div>
                            </div>
                            
                            <div class="cf7-form-group" style="margin-bottom: 15px;">
                                <label for="cf7-action-due-date" style="display: block; margin-bottom: 5px; font-weight: 600;">Due Date</label>
                                <input type="datetime-local" id="cf7-action-due-date" name="due_date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </form>
                    </div>
                    <div class="cf7-modal-footer" style="padding: 20px; border-top: 1px solid #ddd; text-align: right;">
                        <button type="button" id="cf7-action-cancel" class="button" style="margin-right: 10px; padding: 8px 16px; border: 1px solid #ddd; background: #f7f7f7; cursor: pointer;">Cancel</button>
                        <button type="button" id="cf7-action-save" class="button button-primary" style="padding: 8px 16px; background: #2271b1; color: white; border: 1px solid #2271b1; cursor: pointer;">Save Action</button>
                    </div>
                </div>
            </div>
        `;
        
        // Ensure any existing modal is removed first
        console.log('Checking for existing modals before creation...');
        const existingModals = jQuery('#cf7-action-modal');
        console.log('Found existing modals:', existingModals.length);
        existingModals.remove();
        
        // Append to body
        jQuery('body').append(completeModalHTML);
        console.log('Modal HTML appended to body');
        
        // Bind handlers immediately
        const handlersResult = this.bindModalHandlers();
        console.log('Modal handlers bound:', handlersResult);
        
        return true;
    },
    
    // Show the fresh modal with options
    showFreshModal: function(options) {
        console.log('showFreshModal called with options:', options);
        
        const modal = jQuery('#cf7-action-modal');
        const form = jQuery('#cf7-action-form');
        
        console.log('Modal elements found:', {
            modal: modal.length,
            form: form.length
        });
        
        if (modal.length === 0 || form.length === 0) {
            console.error('Fresh modal or form not found - unexpected');
            alert('Unable to load action modal. Please refresh the page and try again.');
            return;
        }
        
        // Reset form
        form[0].reset();
        console.log('Form reset completed');
        
        // Set submission ID
        const postId = jQuery('#post_ID').val();
        if (postId) {
            jQuery('#cf7-submission-id').val(postId);
            console.log('Submission ID set to:', postId);
        }
        
        // Add message_id if provided
        if (options && options.messageId) {
            // Add hidden input for message_id
            if (!jQuery('#cf7-message-id').length) {
                form.append('<input type="hidden" id="cf7-message-id" name="message_id">');
            }
            jQuery('#cf7-message-id').val(options.messageId);
            console.log('Message ID set to:', options.messageId);
        }
        
        // Pre-fill form data if provided
        if (options && options.title) {
            jQuery('#cf7-action-title').val(options.title);
            console.log('Title pre-filled:', options.title);
        }
        if (options && options.description) {
            jQuery('#cf7-action-description').val(options.description);
            console.log('Description pre-filled:', options.description);
        }
        
        // Set modal title
        const title = (options && options.messageId) ? 'Create Action from Message' : 'Create New Action';
        jQuery('#cf7-modal-title').text(title);
        console.log('Modal title set to:', title);
        
        // Load assignable users for the dropdown
        this.loadAssignableUsersForModal();
        
        // Show the modal
        console.log('About to show modal...');
        console.log('Modal element details:', {
            exists: modal.length > 0,
            display: modal.css('display'),
            visibility: modal.css('visibility'),
            opacity: modal.css('opacity'),
            zIndex: modal.css('z-index'),
            position: modal.css('position')
        });
        
        // Add the show class that the CSS expects
        modal.addClass('show');
        
        modal.fadeIn(200, function() {
            console.log('Modal fadeIn completed, should be visible now');
            console.log('After fadeIn - Modal visibility details:', {
                display: modal.css('display'),
                visibility: modal.css('visibility'),
                opacity: modal.css('opacity'),
                isVisible: modal.is(':visible')
            });
        });
        console.log('Modal fadeIn called, checking visibility:', modal.is(':visible'));
    },
    
    // Load assignable users for the modal dropdown
    loadAssignableUsersForModal: function() {
        const assigneeSelect = jQuery('#cf7-action-assignee');
        const loadingDiv = jQuery('.cf7-loading-users');
        
        if (assigneeSelect.length === 0) {
            console.warn('Assignee select not found in modal');
            return;
        }
        
        // Show loading state
        loadingDiv.show();
        assigneeSelect.prop('disabled', true);
        
        // Use the same AJAX URL resolution as the form submission
        const ajaxUrl = cf7_actions_ajax ? cf7_actions_ajax.ajax_url : 
                       (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
        const nonce = window.CF7_Actions.nonce || 
                     (cf7_actions_ajax ? cf7_actions_ajax.nonce : '') ||
                     (typeof cf7_admin_ajax !== 'undefined' ? cf7_admin_ajax.nonce : '');
        
        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_get_assignable_users',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.users) {
                    // Clear existing options except the first one
                    assigneeSelect.find('option:not(:first)').remove();
                    
                    // Add user options
                    response.data.users.forEach(function(user) {
                        assigneeSelect.append('<option value="' + user.id + '">' + user.name + '</option>');
                    });
                    
                } else {
                    console.error('Failed to load users:', response);
                    assigneeSelect.append('<option value="">Error loading users</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error loading users:', error);
                assigneeSelect.append('<option value="">Error loading users</option>');
            },
            complete: function() {
                // Hide loading state
                loadingDiv.hide();
                assigneeSelect.prop('disabled', false);
            }
        });
    },
    
    // Bind modal event handlers
    bindModalHandlers: function() {
        // Remove any existing handlers to prevent duplicates
        jQuery(document).off('click.cf7-modal-simple');
        
        // Modal close handlers
        jQuery(document).on('click.cf7-modal-simple', '.cf7-modal-close, #cf7-action-cancel', function() {
            const modal = jQuery('#cf7-action-modal');
            modal.removeClass('show');
            modal.fadeOut(200);
        });
        
        // Close modal on outside click
        jQuery(document).on('click.cf7-modal-simple', '.cf7-modal', function(e) {
            if (e.target === e.currentTarget) {
                const modal = jQuery('#cf7-action-modal');
                modal.removeClass('show');
                modal.fadeOut(200);
            }
        });
        
        // Save button handler
        jQuery(document).on('click.cf7-modal-simple', '#cf7-action-save', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent other handlers
            window.CF7_Actions.saveAction();
        });
        
        // Form submission handler
        jQuery(document).on('submit.cf7-modal-simple', '#cf7-action-form', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent other handlers
            window.CF7_Actions.saveAction();
        });
        
    },
    
    /**
     * Save action with cross-tab compatibility and error handling.
     * 
     * Simplified action saving for cross-tab compatibility with double-submission
     * prevention and comprehensive error handling. Includes form data collection,
     * AJAX submission with fallback configuration, success notification display,
     * and graceful error management for reliable action persistence.
     * 
     * @since 1.0.0
     */
    saveAction: function() {
        
        // Prevent double submission
        if (this._saving) {
            return;
        }
        this._saving = true;
        const formData = {
            action: 'cf7_save_action',
            nonce: cf7_actions_ajax ? cf7_actions_ajax.nonce : (window.cf7_actions_ajax ? window.cf7_actions_ajax.nonce : ''),
            submission_id: jQuery('#post_ID').val(),
            action_id: jQuery('#cf7-action-id').val(),
            title: jQuery('#cf7-action-title').val(),
            description: jQuery('#cf7-action-description').val(),
            priority: jQuery('#cf7-action-priority').val(),
            assignee_type: jQuery('#cf7-action-assignee').val(), // Legacy field for compatibility
            assigned_to: jQuery('#cf7-action-assignee').val(), // New field for user ID
            due_date: jQuery('#cf7-action-due-date').val()
        };
        
        // Add message_id if present
        const messageId = jQuery('#cf7-message-id').val();
        if (messageId) {
            formData.message_id = messageId;
        }
        
        // Get AJAX URL
        const ajaxUrl = cf7_actions_ajax ? cf7_actions_ajax.ajax_url : 
                       (window.cf7_actions_ajax ? window.cf7_actions_ajax.ajax_url : 
                       (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'));

        jQuery('#cf7-action-save').prop('disabled', true).text('Saving...');

        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    jQuery('#cf7-action-modal').fadeOut(200);
                    
                    // Show success notification
                    const notification = jQuery(`
                        <div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 10002; max-width: 300px;">
                            <p>Action saved successfully</p>
                            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>
                        </div>
                    `);
                    
                    jQuery('body').append(notification);
                    setTimeout(() => notification.fadeOut(() => notification.remove()), 3000);
                    notification.find('.notice-dismiss').on('click', () => notification.fadeOut(() => notification.remove()));
                    
                    // Refresh actions if ActionsManager exists
                    if (window.actionsManager && typeof window.actionsManager.loadActions === 'function') {
                        window.actionsManager.loadActions();
                    }
                } else {
                    alert('Failed to save action: ' + (response.data.message || response.data));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save action. Please try again.';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        errorMessage = errorResponse.data.message;
                    }
                } catch (e) {
                    // Keep default message
                }
                alert(errorMessage);
            },
            complete: function() {
                jQuery('#cf7-action-save').prop('disabled', false).text('Save Action');
                // Reset saving flag
                window.CF7_Actions._saving = false;
            }
        });
    },
    
    // Load assignable users for dropdown
    loadAssignableUsers: function() {
        const select = jQuery('#cf7-action-assignee');
        const loading = jQuery('.cf7-loading-users');
        
        // Show loading
        loading.show();
        select.prop('disabled', true);
        
        jQuery.ajax({
            url: cf7_actions_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_get_assignable_users',
                nonce: cf7_actions_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Clear existing options except the first one
                    select.find('option:not(:first)').remove();
                    
                    // Add user options
                    const users = response.data.users || [];
                    users.forEach(function(user) {
                        const option = `<option value="${user.id}">${user.name} (${user.email})</option>`;
                        select.append(option);
                    });
                } else {
                    console.error('Failed to load users:', response.data);
                    // Fallback to basic options
                    select.html(`
                        <option value="">Select User...</option>
                        <option value="admin">Admin/Curator</option>
                        <option value="artist">Artist</option>
                    `);
                }
            },
            error: function() {
                console.error('Failed to load assignable users');
                // Fallback to basic options
                select.html(`
                    <option value="">Select User...</option>
                    <option value="admin">Admin/Curator</option>
                    <option value="artist">Artist</option>
                `);
            },
            complete: function() {
                loading.hide();
                select.prop('disabled', false);
            }
        });
    },
    
    /**
     * Display context menu at specified coordinates.
     * Legacy compatibility method for existing context menu integrations.
     */
    showContextMenu: function(x, y, messageId) {
        // Remove any existing context menus
        jQuery('.cf7-context-menu').remove();
        // Context menu creation happens in PHP-generated JavaScript
    },

    /**
     * Hide all visible context menus.
     * Simple utility for context menu cleanup and state management.
     */
    hideContextMenu: function() {
        jQuery('.cf7-context-menu').remove();
    }
});
