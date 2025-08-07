/**
 * CF7 Artist Submissions - Guest Curators Admin JavaScript
 *
 * Handles the administrative interface for guest curator management.
 * Provides AJAX-powered CRUD operations, modal interfaces, and real-time updates.
 *
 * Features:
 * • Dynamic curator list with real-time updates
 * • Modal forms for adding and editing curators
 * • Permission management for open calls
 * • Login link generation and email sending
 * • Clipboard functionality for portal URLs
 * • Loading states and user feedback
 *
 * @package CF7_Artist_Submissions
 * @subpackage GuestCuratorsAdminJS
 * @since 1.3.0
 * @version 1.3.0
 */

(function($) {
    'use strict';
    
    // Guest Curators Admin Handler
    var CF7GuestCuratorsAdmin = {
        
        // Configuration
        config: {
            ajaxUrl: '',
            nonce: '',
            strings: {},
            currentCuratorId: null,
            isEditing: false
        },
        
        // Initialize the admin interface
        init: function() {
            this.config.ajaxUrl = cf7GuestCurators.ajaxurl;
            this.config.nonce = cf7GuestCurators.nonce;
            this.config.strings = cf7GuestCurators.strings;
            
            this.bindEvents();
            this.loadCuratorsList();
            this.loadOpenCalls();
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Add curator button
            $(document).on('click', '#cf7-add-guest-curator', function(e) {
                e.preventDefault();
                self.showAddCuratorModal();
            });
            
            // Edit curator button
            $(document).on('click', '.cf7-edit-curator', function(e) {
                e.preventDefault();
                var curatorId = $(this).data('curator-id');
                self.showEditCuratorModal(curatorId);
            });
            
            // Delete curator button
            $(document).on('click', '.cf7-delete-curator', function(e) {
                e.preventDefault();
                var curatorId = $(this).data('curator-id');
                var curatorName = $(this).data('curator-name');
                self.deleteCurator(curatorId, curatorName);
            });
            
            // Send login link button
            $(document).on('click', '.cf7-send-login-link', function(e) {
                e.preventDefault();
                var curatorId = $(this).data('curator-id');
                var curatorEmail = $(this).data('curator-email');
                self.sendLoginLink(curatorId, curatorEmail);
            });
            
            // Save curator button
            $(document).on('click', '#cf7-save-curator', function(e) {
                e.preventDefault();
                self.saveCurator();
            });
            
            // Cancel curator button
            $(document).on('click', '#cf7-cancel-curator', function(e) {
                e.preventDefault();
                self.hideModal();
            });
            
            // Modal close button
            $(document).on('click', '.cf7-modal-close', function(e) {
                e.preventDefault();
                self.hideModal();
            });
            
            // Copy portal URL button
            $(document).on('click', '#cf7-copy-portal-url', function(e) {
                e.preventDefault();
                self.copyPortalUrl();
            });
            
            // Modal background click
            $(document).on('click', '#cf7-guest-curator-modal', function(e) {
                if (e.target === this) {
                    self.hideModal();
                }
            });
            
            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.hideModal();
                }
            });
        },
        
        // ============================================================================
        // CURATOR MANAGEMENT
        // ============================================================================
        
        // Show add curator modal
        showAddCuratorModal: function() {
            this.config.isEditing = false;
            this.config.currentCuratorId = null;
            
            $('#cf7-modal-title').text('Add Guest Curator');
            $('#cf7-guest-curator-form')[0].reset();
            $('#curator-id').val('');
            
            this.showModal();
        },
        
        // Show edit curator modal
        showEditCuratorModal: function(curatorId) {
            var self = this;
            this.config.isEditing = true;
            this.config.currentCuratorId = curatorId;
            
            $('#cf7-modal-title').text('Edit Guest Curator');
            
            // Load curator data
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_guest_curator',
                    curator_id: curatorId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var curator = response.data;
                        
                        $('#curator-id').val(curator.id);
                        $('#curator-name').val(curator.name);
                        $('#curator-email').val(curator.email);
                        $('#curator-status').val(curator.status);
                        
                        // Load and set permissions
                        self.loadCuratorPermissions(curator.id);
                    } else {
                        alert(response.data || self.config.strings.error_general);
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                }
            });
            
            this.showModal();
        },
        
        // Save curator (add or update)
        saveCurator: function() {
            var self = this;
            var form = $('#cf7-guest-curator-form');
            var saveBtn = $('#cf7-save-curator');
            
            // Get form data
            var formData = {
                action: self.config.isEditing ? 'cf7_update_guest_curator' : 'cf7_add_guest_curator',
                nonce: self.config.nonce,
                name: $('#curator-name').val().trim(),
                email: $('#curator-email').val().trim(),
                status: $('#curator-status').val(),
                open_calls: []
            };
            
            if (self.config.isEditing) {
                formData.curator_id = self.config.currentCuratorId;
            }
            
            // Get selected open calls
            $('#cf7-open-call-permissions input[type="checkbox"]:checked').each(function() {
                formData.open_calls.push($(this).val());
            });
            
            // Validate form
            if (!formData.name) {
                alert('Please enter a curator name.');
                $('#curator-name').focus();
                return;
            }
            
            if (!formData.email) {
                alert('Please enter an email address.');
                $('#curator-email').focus();
                return;
            }
            
            if (!self.isValidEmail(formData.email)) {
                alert('Please enter a valid email address.');
                $('#curator-email').focus();
                return;
            }
            
            // Show loading state
            saveBtn.prop('disabled', true);
            saveBtn.html('<span class="dashicons dashicons-update spin"></span> ' + self.config.strings.saving);
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.hideModal();
                        self.loadCuratorsList();
                        
                        var message = self.config.isEditing ? 
                            'Guest curator updated successfully.' : 
                            'Guest curator added successfully.';
                        self.showNotice(message, 'success');
                    } else {
                        alert(response.data || self.config.strings.error_general);
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                },
                complete: function() {
                    saveBtn.prop('disabled', false);
                    saveBtn.text('Save Curator');
                }
            });
        },
        
        // Delete curator
        deleteCurator: function(curatorId, curatorName) {
            var self = this;
            
            if (!confirm(self.config.strings.confirm_delete.replace('%s', curatorName))) {
                return;
            }
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_delete_guest_curator',
                    curator_id: curatorId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.loadCuratorsList();
                        self.showNotice('Guest curator deleted successfully.', 'success');
                    } else {
                        alert(response.data || self.config.strings.error_general);
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                }
            });
        },
        
        // Send login link to curator
        sendLoginLink: function(curatorId, curatorEmail) {
            var self = this;
            var button = $('.cf7-send-login-link[data-curator-id="' + curatorId + '"]');
            var originalText = button.text();
            
            // Show loading state
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update spin"></span> Sending...');
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_send_curator_login_link',
                    curator_id: curatorId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Login link sent to ' + curatorEmail, 'success');
                    } else {
                        alert(response.data || self.config.strings.error_general);
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                },
                complete: function() {
                    button.prop('disabled', false);
                    button.text(originalText);
                }
            });
        },
        
        // ============================================================================
        // DATA LOADING
        // ============================================================================
        
        // Load curators list
        loadCuratorsList: function() {
            var self = this;
            var tbody = $('#cf7-guest-curators-tbody');
            
            tbody.html('<tr><td colspan="6" style="text-align: center;">' + self.config.strings.loading + '</td></tr>');
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_guest_curators',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderCuratorsList(response.data);
                    } else {
                        tbody.html('<tr><td colspan="6" style="text-align: center; color: red;">Failed to load curators</td></tr>');
                    }
                },
                error: function() {
                    tbody.html('<tr><td colspan="6" style="text-align: center; color: red;">Failed to load curators</td></tr>');
                }
            });
        },
        
        // Render curators list
        renderCuratorsList: function(curators) {
            var tbody = $('#cf7-guest-curators-tbody');
            var html = '';
            
            if (curators.length === 0) {
                html = '<tr><td colspan="6" style="text-align: center; color: #666;">No guest curators found. <a href="#" id="cf7-add-first-curator">Add your first curator</a></td></tr>';
            } else {
                for (var i = 0; i < curators.length; i++) {
                    var curator = curators[i];
                    html += this.renderCuratorRow(curator);
                }
            }
            
            tbody.html(html);
        },
        
        // Render single curator row
        renderCuratorRow: function(curator) {
            var statusBadge = curator.status === 'active' ? 
                '<span class="cf7-status-badge cf7-status-active">Active</span>' : 
                '<span class="cf7-status-badge cf7-status-inactive">Inactive</span>';
            
            var openCallsText = curator.open_calls || 'None assigned';
            if (curator.open_calls_count > 50) {
                openCallsText = curator.open_calls_count + ' open calls';
            }
            
            return '<tr>' +
                '<td><strong>' + this.escapeHtml(curator.name) + '</strong></td>' +
                '<td>' + this.escapeHtml(curator.email) + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' + this.escapeHtml(openCallsText) + '</td>' +
                '<td>' + this.escapeHtml(curator.last_login) + '</td>' +
                '<td>' +
                    '<button type="button" class="button button-small cf7-edit-curator" data-curator-id="' + curator.id + '">' +
                        'Edit' +
                    '</button> ' +
                    '<button type="button" class="button button-small cf7-send-login-link" data-curator-id="' + curator.id + '" data-curator-email="' + this.escapeHtml(curator.email) + '">' +
                        'Send Link' +
                    '</button> ' +
                    '<button type="button" class="button button-small cf7-delete-curator" data-curator-id="' + curator.id + '" data-curator-name="' + this.escapeHtml(curator.name) + '" style="color: #d63638;">' +
                        'Delete' +
                    '</button>' +
                '</td>' +
            '</tr>';
        },
        
        // Load open calls for permissions
        loadOpenCalls: function() {
            var self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_open_calls',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderOpenCallsPermissions(response.data);
                    }
                },
                error: function() {
                    $('#cf7-open-call-permissions').html('<p>Failed to load open calls</p>');
                }
            });
        },
        
        // Render open calls permissions checkboxes
        renderOpenCallsPermissions: function(openCalls) {
            var container = $('#cf7-open-call-permissions');
            var html = '';
            
            if (openCalls.length === 0) {
                html = '<p style="color: #666; font-style: italic;">No open calls found. Create open call taxonomies first.</p>';
            } else {
                html = '<div class="cf7-permissions-grid">';
                for (var i = 0; i < openCalls.length; i++) {
                    var openCall = openCalls[i];
                    html += '<label class="cf7-permission-item">' +
                        '<input type="checkbox" value="' + openCall.term_id + '"> ' +
                        this.escapeHtml(openCall.name) +
                        '</label>';
                }
                html += '</div>';
            }
            
            container.html(html);
        },
        
        // Load curator permissions for editing
        loadCuratorPermissions: function(curatorId) {
            var self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_curator_permissions',
                    curator_id: curatorId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Check the appropriate checkboxes
                        $('#cf7-open-call-permissions input[type="checkbox"]').prop('checked', false);
                        for (var i = 0; i < response.data.length; i++) {
                            $('#cf7-open-call-permissions input[value="' + response.data[i] + '"]').prop('checked', true);
                        }
                    }
                }
            });
        },
        
        // ============================================================================
        // MODAL MANAGEMENT
        // ============================================================================
        
        // Show modal
        showModal: function() {
            $('#cf7-guest-curator-modal').show();
            $('body').addClass('cf7-modal-open');
            $('#curator-name').focus();
        },
        
        // Hide modal
        hideModal: function() {
            $('#cf7-guest-curator-modal').hide();
            $('body').removeClass('cf7-modal-open');
            this.config.isEditing = false;
            this.config.currentCuratorId = null;
        },
        
        // Copy portal URL to clipboard
        copyPortalUrl: function() {
            var url = $('#cf7-portal-url').text();
            var self = this;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function() {
                    self.showNotice(self.config.strings.copied, 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    self.showNotice(self.config.strings.copied, 'success');
                } catch (err) {
                    console.error('Failed to copy: ', err);
                }
                document.body.removeChild(textArea);
            }
        },
        
        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        // Show admin notice
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = '<div class="notice ' + noticeClass + ' is-dismissible cf7-temp-notice">' +
                '<p>' + message + '</p>' +
            '</div>';
            
            $('.wrap h1').after(notice);
            
            setTimeout(function() {
                $('.cf7-temp-notice').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        // Validate email address
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
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
        if (typeof cf7GuestCurators !== 'undefined') {
            CF7GuestCuratorsAdmin.init();
        }
    });
    
})(jQuery);
