/**
 * CF7 Artist Submissions - Filter Controls
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize date pickers
        $('.date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            maxDate: '+0d', // Prevent future dates
            showOtherMonths: true,
            selectOtherMonths: true,
            beforeShow: function(input, inst) {
                // Ensure datepicker appears above other elements
                inst.dpDiv.css({
                    'z-index': 999999
                });
                
                // Position the datepicker properly after rendering
                setTimeout(function() {
                    var inputOffset = $(input).offset();
                    inst.dpDiv.css({
                        top: inputOffset.top + $(input).outerHeight() + 2,
                        left: inputOffset.left
                    });
                }, 0);
            }
        });
        
        // Handle date picker relationships
        $('#date_start').datepicker('option', 'onSelect', function(selectedDate) {
            $('#date_end').datepicker('option', 'minDate', selectedDate);
        });
        
        $('#date_end').datepicker('option', 'onSelect', function(selectedDate) {
            $('#date_start').datepicker('option', 'maxDate', selectedDate);
        });
        
        // Handle clear filters button
        $('.clear-filters').on('click', function(e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        });
        
        // Make Enter key in date fields submit the form
        $('.date-picker').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                $(this).closest('form').submit();
            }
        });
    });
    
})(jQuery);