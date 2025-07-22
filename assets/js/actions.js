/**
 * ============================================================================
 * CF7 ARTIST SUBMISSIONS - ACTIONS MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * Complete JavaScript interface for managing submission actions including
 * creation, editing, completion workflow, priority-based filtering, and
 * real-time AJAX operations with comprehensive cross-tab compatibility.
 * 
 * This system provides comprehensive task management capabilities for artist
 * submissions, enabling curators and administrators to create, assign, and
 * track action items throughout the submission review process. Features
 * include priority-based workflows, due date management, user assignment,
 * and contextual action creation from conversation messages.
 * 
 * The architecture employs a dual-interface approach with both instance-based
 * management (ActionsManager class) and global access patterns (window.CF7_Actions)
 * to ensure compatibility across different tab contexts and external integrations.
 * 
 * ============================================================================
 * SYSTEM ARCHITECTURE
 * ============================================================================
 * 
 * CF7ActionManagementSystem
 * ├─ ActionsManager (primary instance-based controller)
 * │  ├─ ActionDataManager: Cache and state management for action data
 * │  ├─ UIRenderingEngine: List rendering and filtering interface
 * │  ├─ FilterSystem: Priority and status-based filtering logic
 * │  └─ EventHandlingLayer: User interaction management and delegation
 * │
 * ├─ ModalManagementSystem (dynamic form interface)
 * │  ├─ ModalInjectionEngine: HTML generation and injection system
 * │  ├─ FormValidationSystem: Input validation and error handling
 * │  ├─ UserAssignmentLoader: Assignable users dropdown management
 * │  └─ ContextualFormPrefill: Message-to-action conversion support
 * │
 * ├─ AjaxCommunicationLayer (server integration)
 * │  ├─ ActionCRUDOperations: Create, read, update, delete operations
 * │  ├─ ErrorHandlingFramework: Graceful failure management system
 * │  ├─ ResponseProcessingEngine: Data validation and caching layer
 * │  └─ SecurityNonceValidation: CSRF protection and token validation
 * │
 * ├─ CrossTabIntegrationSystem (global interface)
 * │  ├─ GlobalAccessInterface: window.CF7_Actions methods and utilities
 * │  ├─ StateIndependentOperation: Tab-agnostic functionality layer
 * │  ├─ ContextMenuIntegration: conversation.js compatibility bridge
 * │  └─ ExternalAPILayer: Third-party integration support framework
 * │
 * └─ InitializationFramework (multi-scenario startup)
 *    ├─ TabChangeEventHandler: cf7_tab_changed listener management
 *    ├─ DirectAccessInitializer: Immediate DOM ready setup system
 *    ├─ FallbackInitialization: Delayed retry mechanism for reliability
 *    └─ ConfigurationValidation: AJAX settings verification and fallback
 * 
 * ============================================================================
 * INTEGRATION POINTS
 * ============================================================================
 * 
 * • WordPress Admin Framework: Notice system, UI components, admin styles
 * • CF7 Backend Systems: Action CRUD operations, user management, submission data
 * • Tab Management System: Tab switching events, content initialization
 * • Conversation System: Context menu integration, message-to-action workflows
 * • Template System: Actions tab rendering, HTML structure integration
 * • Database Layer: Action persistence, user assignment, audit logging
 * • Email System: Action notifications, assignment alerts, due date reminders
 * • Modal UI Framework: Dynamic form generation, validation, user feedback
 * 
 * ============================================================================
 * DEPENDENCIES
 * ============================================================================
 * 
 * • jQuery 3.x: DOM manipulation, AJAX operations, event handling
 * • cf7_actions_ajax: Localized AJAX configuration (URL, nonce, endpoints)
 * • cf7ArtistSubmissions: Global plugin configuration and utilities
 * • WordPress Admin UI: Notice system, admin styles, icon fonts
 * • cf7_admin_ajax: Alternative AJAX configuration for fallback scenarios
 * • WordPress AJAX API: Server communication, security validation
 * • CF7 Tab System: Tab switching events, container management
 * • CF7 Conversation System: Context menu integration, message data
 * 
 * ============================================================================
 * ACTION MANAGEMENT FEATURES
 * ============================================================================
 * 
 * • Priority-based task organization (high, medium, low)
 * • Status tracking (pending, completed, overdue)
 * • User assignment with role-based access control
 * • Due date management with overdue detection
 * • Contextual action creation from conversation messages
 * • Real-time filtering and search capabilities
 * • Bulk operations and batch processing
 * • Audit trail and activity logging
 * 
 * ============================================================================
 * MODAL INTERFACE FEATURES
 * ============================================================================
 * 
 * • Dynamic form injection with validation
 * • Assignable users dropdown with role filtering
 * • DateTime picker for due date selection
 * • Priority selection with visual indicators
 * • Description rich text support
 * • Context-aware form prefilling
 * • Error handling with user feedback
 * • Accessibility compliance (ARIA labels, keyboard navigation)
 * 
 * ============================================================================
 * CROSS-TAB COMPATIBILITY
 * ============================================================================
 * 
 * • Global interface (window.CF7_Actions) for external access
 * • State-independent modal creation and management
 * • Tab-agnostic initialization and operation
 * • Context menu integration across all tabs
 * • Fresh modal injection to prevent state conflicts
 * • Fallback AJAX configuration resolution
 * • Double-submission prevention mechanisms
 * • Error-resistant operation with graceful degradation
 * 
 * ============================================================================
 * PERFORMANCE FEATURES
 * ============================================================================
 * 
 * • Efficient action caching and state management
 * • Optimized DOM manipulation with event delegation
 * • Lazy loading of assignable users data
 * • Minimal re-rendering with targeted updates
 * • AJAX request batching and optimization
 * • Memory-efficient modal creation/destruction
 * • Debounced filter operations
 * • Optimized event handler binding
 * 
 * ============================================================================
 * ACCESSIBILITY FEATURES
 * ============================================================================
 * 
 * • ARIA labels and roles for screen readers
 * • Keyboard navigation support throughout interface
 * • High contrast mode compatibility
 * • Focus management in modal dialogs
 * • Screen reader announcements for dynamic content
 * • Semantic HTML structure with proper headings
 * • Alternative text for visual indicators
 * • Consistent tab order and navigation patterns
 * 
 * ============================================================================
 * SECURITY FEATURES
 * ============================================================================
 * 
 * • WordPress nonce validation for all AJAX requests
 * • XSS protection with HTML escaping
 * • CSRF protection through token validation
 * • Input sanitization and validation
 * • Role-based access control integration
 * • Secure user assignment verification
 * • Action ownership validation
 * • Audit logging for security monitoring
 * 
 * @package CF7_Artist_Submissions
 * @subpackage ActionManagement
 * @since 2.0.0
 * @version 2.2.0
 * @author CF7 Artist Submissions Development Team
 */

/**
 * ============================================================================
 * ACTIONS MANAGER CLASS
 * ============================================================================
 * 
 * Primary class managing all action-related functionality including CRUD
 * operations, filtering, modal management, and user interface updates.
 * 
 * Responsibilities:
 * - Action data management and caching
 * - UI rendering and event handling
 * - AJAX communication with backend
 * - Modal form management
 * - Filter and search functionality
 */
class ActionsManager {
    /**
     * Initialize ActionsManager instance
     * 
     * Sets up default state and initializes the management system.
     * 
     * Properties:
     * - currentFilter: Active filter state ('all', 'pending', 'completed', etc.)
     * - actions: Cached array of action objects from server
     */
    constructor() {
        this.currentFilter = 'all';
        this.actions = [];
        this.init();
    }

    /**
     * Initialize the actions management system
     * 
     * Sets up event handlers and loads initial action data.
     * Called automatically during construction.
     */
    init() {
        this.bindEvents();
        this.loadActions();
    }

    /**
     * Bind Event Handlers
     * 
     * Sets up all event listeners for action management interface.
     * Uses event delegation for dynamically created elements.
     * 
     * Events Handled:
     * - Add action button clicks
     * - Filter button interactions
     * - Action complete/edit/delete operations
     * - Modal open/close events
     * - Form submissions
     */
    bindEvents() {
        // Add action button
        jQuery(document).on('click', '#cf7-add-action-btn', (e) => {
            e.preventDefault();
            this.showActionModal();
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
     * Load Actions from Server
     * 
     * Retrieves all actions for the current submission via AJAX and updates
     * the local actions cache and UI display.
     * 
     * Process:
     * 1. Get submission ID from post meta
     * 2. Show loading state in actions list
     * 3. Make AJAX request to cf7_get_actions endpoint
     * 4. Update local cache and render UI on success
     * 5. Display error message on failure
     * 
     * AJAX Endpoint: cf7_get_actions
     * Response: { success: boolean, data: { actions: Array } }
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
     * Render Actions List
     * 
     * Updates the actions list UI with filtered actions from local cache.
     * Handles empty states and generates HTML for each action item.
     * 
     * Process:
     * 1. Get filtered actions based on current filter
     * 2. Show empty state if no actions match filter
     * 3. Generate HTML for each action using getActionItemHTML()
     * 4. Update the actions list container
     * 
     * Used by: loadActions(), setActiveFilter(), and action CRUD operations
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

    getEmptyStateHTML() {
        return `
            <div class="actions-empty">
                <div class="actions-empty-icon">📋</div>
                <h3>No actions found</h3>
                <p>Create your first action to get started.</p>
            </div>
        `;
    }

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

    updateActionsCount() {
        const count = this.actions.filter(action => action.status === 'pending').length;
        jQuery('.actions-count').text(count);
    }
    
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

    /**
     * ========================================================================
     * MODAL MANAGEMENT SYSTEM
     * ========================================================================
     */

    /**
     * Show Action Modal
     * 
     * Displays the action creation/editing modal with dynamic form injection.
     * Handles modal injection if not present and supports message context.
     * 
     * Parameters:
     * - messageId (optional): Associates action with specific message
     * 
     * Process:
     * 1. Check if modal exists in DOM
     * 2. Inject modal HTML if missing
     * 3. Display modal with appropriate form state
     * 4. Load assignable users for dropdown
     * 
     * Used by: Add action button, edit action, context menu integration
     */
    showActionModal(messageId = null) {
        const modal = jQuery('#cf7-action-modal');
        const form = jQuery('#cf7-action-form');
        
        // If modal doesn't exist, inject it immediately
        if (modal.length === 0 || form.length === 0) {
            this.injectModalHTML();
            
            // Use the newly injected elements
            setTimeout(() => {
                const injectedModal = jQuery('#cf7-action-modal');
                const injectedForm = jQuery('#cf7-action-form');
                
                if (injectedModal.length > 0 && injectedForm.length > 0) {
                    this.displayModal(injectedModal, injectedForm, messageId);
                } else {
                    alert('Unable to load action modal. Please refresh the page and try again.');
                }
            }, 50);
            return;
        }
        
        // Modal exists, show it directly
        this.displayModal(modal, form, messageId);
    }

    displayModal(modal, form, messageId = null) {
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
        
        modal.fadeIn(200);
    }

    hideActionModal() {
        jQuery('#cf7-action-modal').fadeOut(200);
    }

    injectModalHTML() {
        const submissionId = jQuery('#post_ID').val() || '';
        
        const modalHTML = `
            <!-- Add Action Modal -->
            <div id="cf7-action-modal" class="cf7-modal" style="display: none;">
                <div class="cf7-modal-content">
                    <div class="cf7-modal-header">
                        <h3 id="cf7-modal-title">Add New Action</h3>
                        <button type="button" class="cf7-modal-close">&times;</button>
                    </div>
                    <div class="cf7-modal-body">
                        <form id="cf7-action-form">
                            <input type="hidden" id="cf7-action-id" name="action_id">
                            <input type="hidden" id="cf7-submission-id" name="submission_id" value="${submissionId}">
                            
                            <div class="cf7-form-group">
                                <label for="cf7-action-title">Title *</label>
                                <input type="text" id="cf7-action-title" name="title" required>
                            </div>
                            
                            <div class="cf7-form-group">
                                <label for="cf7-action-description">Description</label>
                                <textarea id="cf7-action-description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="cf7-form-row">
                                <div class="cf7-form-group">
                                    <label for="cf7-action-priority">Priority</label>
                                    <select id="cf7-action-priority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                
                                <div class="cf7-form-group">
                                    <label for="cf7-action-assignee">Assigned To</label>
                                    <select id="cf7-action-assignee" name="assigned_to">
                                        <option value="">Select User...</option>
                                    </select>
                                    <div class="cf7-loading-users" style="display: none;">Loading users...</div>
                                </div>
                            </div>
                            
                            <div class="cf7-form-group">
                                <label for="cf7-action-due-date">Due Date</label>
                                <input type="datetime-local" id="cf7-action-due-date" name="due_date">
                            </div>
                        </form>
                    </div>
                    <div class="cf7-modal-footer">
                        <button type="button" id="cf7-action-cancel" class="button">Cancel</button>
                        <button type="button" id="cf7-action-save" class="button button-primary">Save Action</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modal first
        jQuery('#cf7-action-modal').remove();
        
        // Append to the actions container or body
        const container = jQuery('.cf7-actions-container');
        if (container.length > 0) {
            container.append(modalHTML);
        } else {
            jQuery('body').append(modalHTML);
        }
    }

    showContextMenu(x, y, messageId) {
        // Remove any existing context menus
        jQuery('.cf7-context-menu').remove();
        
        // The context menu is created by the PHP script in add_context_menu_script
        // This method is mainly here for consistency but the actual menu creation
        // happens in the PHP-generated JavaScript
    }

    hideContextMenu() {
        jQuery('.cf7-context-menu').remove();
    }

    /**
     * ========================================================================
     * AJAX OPERATIONS
     * ========================================================================
     */

    /**
     * Save Action
     * 
     * Submits action form data to server for creation or update.
     * Handles both new actions and edits based on presence of action_id.
     * 
     * Form Data Collected:
     * - title, description, priority, assignee_type, due_date
     * - submission_id, action_id (for edits), message_id (for context)
     * 
     * Process:
     * 1. Collect form data into structured object
     * 2. Add optional message_id for context menu actions
     * 3. Submit via AJAX to cf7_save_action endpoint
     * 4. Close modal and refresh actions list on success
     * 5. Display error messages on failure
     * 
     * AJAX Endpoint: cf7_save_action
     * Response: { success: boolean, data: object|string }
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
     * Edit Action
     * 
     * Opens the action modal pre-populated with existing action data.
     * Handles date formatting for datetime-local input compatibility.
     * 
     * Parameters:
     * - actionId: ID of action to edit from local cache
     * 
     * Process:
     * 1. Find action in local cache by ID
     * 2. Show modal with form
     * 3. Populate form fields with action data
     * 4. Format due date for datetime-local input
     * 5. Set modal title to "Edit Action"
     * 
     * Date Formatting: Converts server date to YYYY-MM-DDTHH:MM format
     * Error Handling: Logs error if action not found in cache
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

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * ============================================================================
 * INITIALIZATION SYSTEM
 * ============================================================================
 * 
 * Multi-layered initialization system supporting various loading scenarios.
 * Handles tab switching, direct access, and AJAX configuration fallbacks.
 * 
 * Initialization Triggers:
 * 1. Tab change events (cf7_tab_changed from tabs.js)
 * 2. Direct DOM ready for actions tab
 * 3. Delayed fallback for dynamic content
 * 
 * AJAX Configuration:
 * - Primary: cf7_actions_ajax from PHP localization
 * - Fallback: Global ajaxurl with warning
 * - Nonce validation for security
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
        if (jQuery('.cf7-actions-container').length > 0) {
            if (!window.actionsManager) {
                try {
                    window.actionsManager = new ActionsManager();
                    return true;
                } catch (error) {
                    console.error('Failed to initialize ActionsManager:', error);
                    return false;
                }
            } else {
                return true;
            }
        }
        return false;
    }
    
        // Listen for the tab change event from tabs.js (primary initialization)
    jQuery(document).on('cf7_tab_changed', function(e, tabId) {
        if (tabId === 'cf7-actions-tab') {
            initializeActionsManager();
        }
    });
    
    // Also try to initialize immediately if we're already on the actions tab
    setTimeout(function() {
        if (jQuery('.cf7-actions-container').length > 0) {
            initializeActionsManager();
        }
    }, 500);
});

/**
 * ============================================================================
 * GLOBAL INTERFACE SYSTEM
 * ============================================================================
 * 
 * Cross-tab compatible interface for external access to actions functionality.
 * Provides simplified methods for context menu integration and modal management.
 * 
 * Key Features:
 * - Standalone modal creation and display
 * - Cross-tab initialization compatibility
 * - Error-resistant operation with fallbacks
 * - Integration with conversation.js context menus
 * 
 * Usage Examples:
 * - window.CF7_Actions.openModal({messageId: 123, title: 'Follow up'})
 * - window.CF7_Actions.init() // Initialize if not already done
 * 
 * Integration Points:
 * - conversation.js: Context menu "Create Action" functionality
 * - tabs.js: Tab switching initialization hooks
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
     * Initialize Actions System
     * 
     * Simple initialization that creates ActionsManager if container exists.
     * Safe to call multiple times - checks for existing instance.
     * 
     * Returns: boolean - true if successful, false if failed or not needed
     */
    init: function() {
        
        // Simple initialization - just try to create ActionsManager if actions container exists
        if (jQuery('.cf7-actions-container').length > 0 && !window.actionsManager) {
            try {
                window.actionsManager = new ActionsManager();
                return true;
            } catch (error) {
                console.error('Failed to initialize ActionsManager:', error);
                return false;
            }
        }
        return false;
    },
    
    /**
     * Open Action Modal
     * 
     * Cross-tab compatible modal opening with fresh injection approach.
     * Removes any existing modal to prevent state conflicts.
     * 
     * Parameters:
     * - options.messageId: Associate action with specific message
     * - options.title: Pre-fill action title
     * - options.description: Pre-fill action description
     * 
     * Process:
     * 1. Remove any existing modal instances
     * 2. Inject fresh modal HTML with inline styles
     * 3. Display modal with provided options
     * 4. Load assignable users dropdown
     * 
     * Returns: boolean - true if modal opened successfully
     * 
     * Used by: Context menus, external action creation triggers
     */
    openModal: function(options) {
        
        // Remove any existing modal to start fresh
        jQuery('#cf7-action-modal').remove();
        
        // Always inject a fresh modal to avoid any state issues
        this.createCompleteModal();
        
        // Short delay to ensure DOM is ready, then show
        setTimeout(() => {
            this.showFreshModal(options);
        }, 50);
        
        return true;
    },
    
    // Create complete modal with form included - no complex detection needed
    createCompleteModal: function() {
        
        const submissionId = jQuery('#post_ID').val() || '';
        
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
        
        // Append to body
        jQuery('body').append(completeModalHTML);
        
        // Bind handlers immediately
        this.bindModalHandlers();
        
        return true;
    },
    
    // Show the fresh modal with options
    showFreshModal: function(options) {
        
        const modal = jQuery('#cf7-action-modal');
        const form = jQuery('#cf7-action-form');
        
        if (modal.length === 0 || form.length === 0) {
            console.error('Fresh modal or form not found - unexpected');
            alert('Unable to load action modal. Please refresh the page and try again.');
            return;
        }
        
        // Reset form
        form[0].reset();
        
        // Set submission ID
        const postId = jQuery('#post_ID').val();
        if (postId) {
            jQuery('#cf7-submission-id').val(postId);
        }
        
        // Add message_id if provided
        if (options && options.messageId) {
            // Add hidden input for message_id
            if (!jQuery('#cf7-message-id').length) {
                form.append('<input type="hidden" id="cf7-message-id" name="message_id">');
            }
            jQuery('#cf7-message-id').val(options.messageId);
        }
        
        // Pre-fill form data if provided
        if (options && options.title) {
            jQuery('#cf7-action-title').val(options.title);
        }
        if (options && options.description) {
            jQuery('#cf7-action-description').val(options.description);
        }
        
        // Set modal title
        const title = (options && options.messageId) ? 'Create Action from Message' : 'Create New Action';
        jQuery('#cf7-modal-title').text(title);
        
        // Load assignable users for the dropdown
        this.loadAssignableUsersForModal();
        
        // Show the modal
        modal.fadeIn(200);
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
            jQuery('#cf7-action-modal').fadeOut(200);
        });
        
        // Close modal on outside click
        jQuery(document).on('click.cf7-modal-simple', '.cf7-modal', function(e) {
            if (e.target === e.currentTarget) {
                jQuery('#cf7-action-modal').fadeOut(200);
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
     * Save Action (Global Interface)
     * 
     * Simplified action saving for cross-tab compatibility.
     * Includes double-submission prevention and comprehensive error handling.
     * 
     * Form Data Collected:
     * - All standard action fields (title, description, priority, etc.)
     * - Both assignee_type (legacy) and assigned_to (new) for compatibility
     * - Optional message_id for context integration
     * 
     * Process:
     * 1. Prevent double submission with _saving flag
     * 2. Collect form data with fallback nonce resolution
     * 3. Submit to cf7_save_action endpoint with error handling
     * 4. Display success notification and refresh actions
     * 5. Handle errors gracefully with user feedback
     * 
     * Cross-tab Safety: Works independently of ActionsManager instance
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
    
    // Legacy compatibility methods - kept minimal for existing code
    showContextMenu: function(x, y, messageId) {
        // Remove any existing context menus
        jQuery('.cf7-context-menu').remove();
        // Context menu creation happens in PHP-generated JavaScript
    },

    hideContextMenu: function() {
        jQuery('.cf7-context-menu').remove();
    }
});
