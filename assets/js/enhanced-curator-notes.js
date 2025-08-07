/**
 * CF7 Artist Submissions - Enhanced Curator Notes JavaScript
 *
 * Handles the interactive functionality for the enhanced curator notes system.
 * Provides AJAX-powered note adding, editing, deleting, and real-time updates.
 *
 * Features:
 * • Real-time note adding with AJAX
 * • In-line editing of notes
 * • Confirmation dialogs for deletions
 * • Loading states and user feedback
 * • Input validation and error handling
 * • Responsive interface updates
 *
 * @package CF7_Artist_Submissions
 * @subpackage EnhancedCuratorNotesJS
 * @since 1.3.0
 * @version 1.3.0
 */

(function($) {
    'use strict';
    
    // Enhanced Curator Notes Handler
    var CF7EnhancedNotesHandler = {
        
        // Configuration
        config: {
            submissionId: null,
            nonce: null,
            ajaxUrl: null,
            strings: {}
        },
        
        // Initialize the handler
        init: function() {
            this.config.ajaxUrl = cf7EnhancedNotes.ajaxurl;
            this.config.nonce = cf7EnhancedNotes.nonce;
            this.config.strings = cf7EnhancedNotes.strings;
            
            // Get submission ID from the notes container
            var notesContainer = $('.cf7-enhanced-curator-notes');
            if (notesContainer.length) {
                this.config.submissionId = notesContainer.data('submission-id');
            }
            
            this.bindEvents();
        },
        
        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Add note button
            $(document).on('click', '#cf7-add-note-btn', function(e) {
                e.preventDefault();
                self.addNote();
            });
            
            // Add note with Enter key (Ctrl+Enter or Cmd+Enter)
            $(document).on('keydown', '#cf7-new-note-content', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    self.addNote();
                }
            });
            
            // Edit note button
            $(document).on('click', '.cf7-edit-note-btn', function(e) {
                e.preventDefault();
                var noteId = $(this).data('note-id');
                self.editNote(noteId);
            });
            
            // Delete note button
            $(document).on('click', '.cf7-delete-note-btn', function(e) {
                e.preventDefault();
                var noteId = $(this).data('note-id');
                self.deleteNote(noteId);
            });
            
            // Save edited note
            $(document).on('click', '.cf7-save-note-edit', function(e) {
                e.preventDefault();
                var noteId = $(this).data('note-id');
                self.saveNoteEdit(noteId);
            });
            
            // Cancel note edit
            $(document).on('click', '.cf7-cancel-note-edit', function(e) {
                e.preventDefault();
                var noteId = $(this).data('note-id');
                self.cancelNoteEdit(noteId);
            });
        },
        
        // ============================================================================
        // NOTE OPERATIONS
        // ============================================================================
        
        // Add a new note
        addNote: function() {
            var self = this;
            var textarea = $('#cf7-new-note-content');
            var content = textarea.val().trim();
            var addBtn = $('#cf7-add-note-btn');
            var statusEl = $('.cf7-note-status');
            
            // Validate content
            if (content === '') {
                self.showStatus(statusEl, self.config.strings.note_required, 'error');
                textarea.focus();
                return;
            }
            
            // Show loading state
            addBtn.prop('disabled', true);
            addBtn.html('<span class="dashicons dashicons-update spin"></span> ' + self.config.strings.saving);
            self.showStatus(statusEl, self.config.strings.saving, 'loading');
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_add_curator_note',
                    submission_id: self.config.submissionId,
                    note_content: content,
                    note_type: 'note',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showStatus(statusEl, self.config.strings.note_added, 'success');
                        textarea.val(''); // Clear textarea
                        self.refreshNotesList();
                    } else {
                        self.showStatus(statusEl, response.data || self.config.strings.error_general, 'error');
                    }
                },
                error: function() {
                    self.showStatus(statusEl, self.config.strings.error_general, 'error');
                },
                complete: function() {
                    // Reset button state
                    addBtn.prop('disabled', false);
                    addBtn.html('<span class="dashicons dashicons-plus-alt"></span> ' + 'Add Note');
                }
            });
        },
        
        // Edit a note
        editNote: function(noteId) {
            var noteItem = $('.cf7-note-item[data-note-id="' + noteId + '"]');
            var noteContent = noteItem.find('.cf7-note-text');
            var noteActions = noteItem.find('.cf7-note-actions');
            var originalText = noteContent.text().trim();
            
            // Create edit form
            var editForm = $('<div class="cf7-note-edit-form">' +
                '<textarea class="cf7-note-edit-textarea" rows="4">' + 
                this.escapeHtml(originalText) + '</textarea>' +
                '<div class="cf7-note-edit-actions">' +
                    '<button type="button" class="button button-primary cf7-save-note-edit" data-note-id="' + noteId + '">' +
                        '<span class="dashicons dashicons-yes"></span> Save' +
                    '</button>' +
                    '<button type="button" class="button cf7-cancel-note-edit" data-note-id="' + noteId + '">' +
                        '<span class="dashicons dashicons-no"></span> Cancel' +
                    '</button>' +
                '</div>' +
            '</div>');
            
            // Replace content with edit form
            noteContent.hide();
            noteActions.hide();
            noteContent.after(editForm);
            
            // Focus textarea
            editForm.find('.cf7-note-edit-textarea').focus();
        },
        
        // Save edited note
        saveNoteEdit: function(noteId) {
            var self = this;
            var noteItem = $('.cf7-note-item[data-note-id="' + noteId + '"]');
            var editForm = noteItem.find('.cf7-note-edit-form');
            var textarea = editForm.find('.cf7-note-edit-textarea');
            var content = textarea.val().trim();
            var saveBtn = editForm.find('.cf7-save-note-edit');
            
            // Validate content
            if (content === '') {
                alert(self.config.strings.note_required);
                textarea.focus();
                return;
            }
            
            // Show loading state
            saveBtn.prop('disabled', true);
            saveBtn.html('<span class="dashicons dashicons-update spin"></span> ' + self.config.strings.saving);
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_update_curator_note',
                    note_id: noteId,
                    note_content: content,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update note content
                        var noteContent = noteItem.find('.cf7-note-text');
                        noteContent.html(self.formatNoteContent(content));
                        
                        // Remove edit form and show original elements
                        editForm.remove();
                        noteContent.show();
                        noteItem.find('.cf7-note-actions').show();
                        
                        // Add edited indicator
                        var noteMeta = noteItem.find('.cf7-note-meta');
                        if (!noteMeta.find('.cf7-note-edited').length) {
                            noteMeta.append(' <span class="cf7-note-edited">(edited)</span>');
                        }
                        
                        self.showTemporaryMessage(self.config.strings.note_updated, 'success');
                    } else {
                        alert(response.data || self.config.strings.error_general);
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                },
                complete: function() {
                    saveBtn.prop('disabled', false);
                    saveBtn.html('<span class="dashicons dashicons-yes"></span> Save');
                }
            });
        },
        
        // Cancel note edit
        cancelNoteEdit: function(noteId) {
            var noteItem = $('.cf7-note-item[data-note-id="' + noteId + '"]');
            var editForm = noteItem.find('.cf7-note-edit-form');
            
            // Remove edit form and show original elements
            editForm.remove();
            noteItem.find('.cf7-note-text').show();
            noteItem.find('.cf7-note-actions').show();
        },
        
        // Delete a note
        deleteNote: function(noteId) {
            var self = this;
            
            // Confirm deletion
            if (!confirm(self.config.strings.confirm_delete)) {
                return;
            }
            
            var noteItem = $('.cf7-note-item[data-note-id="' + noteId + '"]');
            var deleteBtn = noteItem.find('.cf7-delete-note-btn');
            
            // Show loading state
            deleteBtn.prop('disabled', true);
            deleteBtn.html('<span class="dashicons dashicons-update spin"></span> ' + 'Deleting...');
            
            // Send AJAX request
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_delete_curator_note',
                    note_id: noteId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove note item with animation
                        noteItem.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if no notes remain
                            if ($('.cf7-note-item').length === 0) {
                                $('#cf7-notes-list').html(
                                    '<div class="cf7-no-notes">' +
                                        '<p>' + 'No notes have been added yet.' + '</p>' +
                                    '</div>'
                                );
                            }
                        });
                        
                        self.showTemporaryMessage(self.config.strings.note_deleted, 'success');
                    } else {
                        alert(response.data || self.config.strings.error_general);
                        deleteBtn.prop('disabled', false);
                        deleteBtn.html('<span class="dashicons dashicons-trash"></span> Delete');
                    }
                },
                error: function() {
                    alert(self.config.strings.error_general);
                    deleteBtn.prop('disabled', false);
                    deleteBtn.html('<span class="dashicons dashicons-trash"></span> Delete');
                }
            });
        },
        
        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        // Refresh the notes list
        refreshNotesList: function() {
            var self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cf7_get_curator_notes',
                    submission_id: self.config.submissionId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderNotesList(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to refresh notes list');
                }
            });
        },
        
        // Render the notes list
        renderNotesList: function(notes) {
            var notesList = $('#cf7-notes-list');
            
            if (notes.length === 0) {
                notesList.html(
                    '<div class="cf7-no-notes">' +
                        '<p>No notes have been added yet.</p>' +
                    '</div>'
                );
                return;
            }
            
            var html = '';
            for (var i = 0; i < notes.length; i++) {
                var note = notes[i];
                html += this.renderNoteItem(note);
            }
            
            notesList.html(html);
        },
        
        // Render a single note item
        renderNoteItem: function(note) {
            var editedIndicator = (note.updated_at !== note.created_at) ? 
                '<span class="cf7-note-edited">(edited)</span>' : '';
            
            var guestIndicator = note.guest_curator_id ? 
                '<span class="cf7-curator-type">(Guest Curator)</span>' : '';
            
            // Check if current user can edit/delete this note
            var canEdit = this.canEditNote(note);
            var actionButtons = '';
            
            if (canEdit) {
                actionButtons = '<div class="cf7-note-actions">' +
                    '<button type="button" class="cf7-edit-note-btn cf7-note-action-btn" data-note-id="' + note.id + '">' +
                        '<span class="dashicons dashicons-edit"></span> Edit' +
                    '</button>' +
                    '<button type="button" class="cf7-delete-note-btn cf7-note-action-btn" data-note-id="' + note.id + '">' +
                        '<span class="dashicons dashicons-trash"></span> Delete' +
                    '</button>' +
                '</div>';
            }
            
            return '<div class="cf7-note-item" data-note-id="' + note.id + '">' +
                '<div class="cf7-note-header">' +
                    '<div class="cf7-note-author">' +
                        '<span class="cf7-curator-name">' + this.escapeHtml(note.curator_name) + '</span>' +
                        guestIndicator +
                    '</div>' +
                    '<div class="cf7-note-meta">' +
                        '<span class="cf7-note-date" title="' + this.escapeHtml(note.formatted_date) + '">' +
                            this.escapeHtml(note.relative_time) +
                        '</span>' +
                        editedIndicator +
                    '</div>' +
                '</div>' +
                '<div class="cf7-note-content">' +
                    '<div class="cf7-note-text">' + this.formatNoteContent(note.note_content) + '</div>' +
                '</div>' +
                actionButtons +
            '</div>';
        },
        
        // Check if current user can edit a note
        canEditNote: function(note) {
            // Check if we have user information available
            if (typeof cf7EnhancedNotes.currentUser === 'undefined') {
                return false; // No user info, deny access
            }
            
            var currentUser = cf7EnhancedNotes.currentUser;
            
            // Administrators can edit all notes
            if (currentUser.can_manage_options) {
                return true;
            }
            
            // Regular users can only edit their own notes
            if (note.user_id && currentUser.id) {
                return parseInt(note.user_id) === parseInt(currentUser.id);
            }
            
            // Guest curators can only edit their own notes
            if (note.guest_curator_id && currentUser.guest_curator_id) {
                return parseInt(note.guest_curator_id) === parseInt(currentUser.guest_curator_id);
            }
            
            return false;
        },
        
        // Format note content (convert newlines to paragraphs)
        formatNoteContent: function(content) {
            return '<p>' + this.escapeHtml(content).replace(/\n\s*\n/g, '</p><p>').replace(/\n/g, '<br>') + '</p>';
        },
        
        // Show status message
        showStatus: function(element, message, type) {
            element.removeClass('cf7-status-success cf7-status-error cf7-status-loading');
            element.addClass('cf7-status-' + type);
            element.text(message).show();
            
            if (type === 'success' || type === 'error') {
                setTimeout(function() {
                    element.fadeOut();
                }, 3000);
            }
        },
        
        // Show temporary message
        showTemporaryMessage: function(message, type) {
            var messageEl = $('<div class="cf7-temporary-message cf7-message-' + type + '">' + 
                message + '</div>');
            
            $('.cf7-enhanced-curator-notes').prepend(messageEl);
            
            setTimeout(function() {
                messageEl.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
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
        if (typeof cf7EnhancedNotes !== 'undefined') {
            CF7EnhancedNotesHandler.init();
        }
    });
    
})(jQuery);
