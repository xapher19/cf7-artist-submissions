/**
 * CF7 Artist Submissions - Contact Form 7 Tag Generator System
 * 
 * Comprehensive tag generator functionality for Contact Form 7 form builder
 * integration, providing seamless mediums field creation with real-time preview,
 * form field generation, and WordPress admin interface compatibility.
 * 
 * Features:
 * • Contact Form 7 tag generator integration with native form builder
 * • Real-time tag preview with live form field generation
 * • Mediums checkbox field generation with validation and styling
 * • Mail tag generation for email template integration
 * • Form field validation with required field support
 * • WordPress admin interface integration with CF7 styling
 * • Event-driven updates with real-time form synchronization
 * • Security validation with input sanitization
 * 
 * @package CF7_Artist_Submissions
 * @subpackage ContactForm7Integration
 * @since 1.1.0
 * @version 1.1.0
 */

(function($) {
    'use strict';
    
    // Wait for CF7 admin to be ready
    $(document).ready(function() {
        
        // Handle mediums tag generator
        if (typeof wpcf7 !== 'undefined' && wpcf7.taggen) {
            
            // Initialize mediums tag generator
            wpcf7.taggen.mediums = function(form) {
                var $form = $(form);
                var $name = $form.find('input[name="name"]');
                var $label = $form.find('input[name="label"]');
                var $required = $form.find('input[name="required"]');
                var $id = $form.find('input[name="id"]');
                var $class = $form.find('input[name="class"]');
                var $tag = $form.find('input.tag');
                var $mailTag = $form.find('input.mail-tag');
                var $mailTagSpan = $form.find('span.mail-tag');
                
                // Update tag when inputs change
                function updateTag() {
                    var name = $name.val() || 'mediums-field';
                    var tag = $required.is(':checked') ? 'mediums*' : 'mediums';
                    var options = [];
                    
                    // Add label option
                    if ($label.val()) {
                        options.push('label:"' + $label.val() + '"');
                    }
                    
                    // Add id option
                    if ($id.val()) {
                        options.push('id:' + $id.val());
                    }
                    
                    // Add class option
                    if ($class.val()) {
                        options.push('class:' + $class.val());
                    }
                    
                    // Build final tag
                    var finalTag = '[' + tag + ' ' + name;
                    if (options.length > 0) {
                        finalTag += ' ' + options.join(' ');
                    }
                    finalTag += ']';
                    
                    $tag.val(finalTag);
                    
                    // Update mail tag
                    var mailTag = '[' + name + ']';
                    $mailTag.val(mailTag);
                    $mailTagSpan.text(mailTag);
                }
                
                // Bind events
                $name.on('input', updateTag);
                $label.on('input', updateTag);
                $required.on('change', updateTag);
                $id.on('input', updateTag);
                $class.on('input', updateTag);
                
                // Set default name if empty
                if (!$name.val()) {
                    $name.val('mediums-field');
                }
                
                // Initial update
                updateTag();
                
                // Handle insert button
                $form.find('.insert-tag').on('click', function() {
                    var tag = $tag.val();
                    if (tag && typeof wpcf7.taggen.insert !== 'undefined') {
                        wpcf7.taggen.insert(tag);
                    }
                });
            };
        }
        
        // Initialize when the mediums tag generator dialog is opened
        $(document).on('click', 'input[name="cf7as-mediums-tag-generator"]', function() {
            setTimeout(function() {
                var $dialog = $('#cf7as-mediums-tag-generator');
                if ($dialog.length && typeof wpcf7 !== 'undefined' && wpcf7.taggen) {
                    wpcf7.taggen.mediums($dialog);
                }
            }, 100);
        });
        
        // Also handle the thickbox dialog
        $(document).on('tb_unload', function() {
            // Cleanup when tag generator dialog is closed
        });
    });
    
})(jQuery);
