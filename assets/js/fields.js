/**
 * CF7 Artist Submissions - Dynamic Field Editing
 */
(function($) {
    'use strict';
    
    // Initialize editable fields
    $(document).ready(function() {
        initEditableFields();
    });
    
    function initEditableFields() {
        // Make fields editable on click
        $('.editable-field').on('click', function(e) {
            // Don't trigger if we clicked on a link or already editing
            if ($(e.target).is('a') || $(this).hasClass('editing')) {
                return;
            }
            
            const $field = $(this);
            const fieldType = $field.data('type');
            const originalValue = $field.data('original');
            const fieldKey = $field.data('key') || $field.data('field');
            
            // Hide the display value
            $field.find('.field-value').hide();
            
            // Create an editable input or textarea
            let $input;
            
            if (fieldType === 'textarea') {
                $input = $('<textarea></textarea>')
                    .val(originalValue)
                    .addClass('edit-active');
            } else {
                $input = $('<input>')
                    .attr('type', fieldType)
                    .val(originalValue)
                    .addClass('edit-active');
            }
            
            // Add editing class and insert the input
            $field.addClass('editing');
            $field.append($input);
            
            // Focus on the new input and select all text
            $input.focus().select();
            
            // Save value on Enter key (except in textarea)
            $input.on('keydown', function(e) {
                // Enter key (13) saves unless it's a textarea
                if (e.keyCode === 13 && fieldType !== 'textarea') {
                    e.preventDefault();
                    saveField($field, $input, fieldKey);
                }
                
                // Escape key (27) cancels
                if (e.keyCode === 27) {
                    cancelEdit($field);
                }
            });
            
            // Save value on blur
            $input.on('blur', function() {
                saveField($field, $input, fieldKey);
            });
            
            // Add save and cancel buttons for textarea
            if (fieldType === 'textarea') {
                const $controls = $('<div class="edit-controls"></div>');
                const $saveBtn = $('<button type="button" class="button button-primary save-btn">Save</button>');
                const $cancelBtn = $('<button type="button" class="button cancel-btn">Cancel</button>');
                
                $controls.append($saveBtn).append($cancelBtn);
                $field.append($controls);
                
                $saveBtn.on('click', function() {
                    saveField($field, $input, fieldKey);
                });
                
                $cancelBtn.on('click', function() {
                    cancelEdit($field);
                });
            }
        });
    }
    
    function saveField($field, $input, fieldKey) {
        const newValue = $input.val();
        let $hiddenInput = $field.find('input[name^="cf7_editable_fields"]');
        
        // Create hidden input if it doesn't exist
        if (!$hiddenInput.length) {
            $hiddenInput = $('<input type="hidden" name="cf7_editable_fields[' + fieldKey + ']">');
            $field.append($hiddenInput);
        }
        
        // Update the hidden input value
        $hiddenInput.val(newValue);
        
        // Update the display value
        const $displayValue = $field.find('.field-value');
        $displayValue.text(newValue);
        
        // If it's a link, update the href
        if ($displayValue.is('a')) {
            let url = newValue;
            if (url.indexOf('http') !== 0) {
                url = 'http://' + url;
            }
            $displayValue.attr('href', url);
        }
        
        // Remove the editing UI
        $field.removeClass('editing');
        $field.find('.edit-active, .edit-controls').remove();
        $displayValue.show();
        
        // Update the original data attribute
        $field.data('original', newValue);
        
        // Show a success message that fades out
        const $message = $('<div class="edit-success">Updated</div>');
        $field.append($message);
        
        setTimeout(function() {
            $message.fadeOut(500, function() {
                $(this).remove();
            });
        }, 1500);
    }
    
    function cancelEdit($field) {
        // Remove the editing UI
        $field.removeClass('editing');
        $field.find('.edit-active, .edit-controls').remove();
        
        // Show the display value again
        $field.find('.field-value').show();
    }
    
})(jQuery);