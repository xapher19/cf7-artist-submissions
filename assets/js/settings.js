/**
 * CF7 Artist Submissions - Settings Page Scripts
 */
(function($) {
    'use strict';
    
    // Handle clear log button
    $('.cf7-artist-clear-log').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(cf7ArtistSettings.clearLogConfirm)) {
            return;
        }
        
        $.ajax({
            url: cf7ArtistSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cf7_artist_clear_debug_log',
                nonce: cf7ArtistSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(cf7ArtistSettings.clearLogSuccess);
                    location.reload();
                }
            }
        });
    });
    
})(jQuery);