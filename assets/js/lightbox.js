/**
 * CF7 Artist Submissions - Lightbox Scripts
 */
(function($) {
    'use strict';
    
    // Create lightbox elements
    const $lightboxOverlay = $('<div class="cf7-lightbox-overlay"></div>');
    const $lightboxContent = $('<div class="cf7-lightbox-content"></div>');
    const $lightboxClose = $('<div class="cf7-lightbox-close">×</div>');
    const $lightboxPrev = $('<div class="cf7-lightbox-nav cf7-lightbox-prev">‹</div>');
    const $lightboxNext = $('<div class="cf7-lightbox-nav cf7-lightbox-next">›</div>');
    
    $lightboxContent.append($lightboxClose);
    $lightboxOverlay.append($lightboxContent);
    $('body').append($lightboxOverlay);
    
    // Current gallery images
    let galleryImages = [];
    let currentIndex = 0;
    
    // Open lightbox on image click
    $(document).on('click', '.lightbox-preview', function(e) {
        e.preventDefault();
        
        // Get all images in the same gallery
        const $gallery = $(this).closest('.submission-files');
        galleryImages = $gallery.find('.lightbox-preview').map(function() {
            return $(this).attr('href');
        }).get();
        
        // Find current image index
        currentIndex = galleryImages.indexOf($(this).attr('href'));
        
        // Show the image
        showImage(currentIndex);
        
        // Show navigation if needed
        if (galleryImages.length > 1) {
            $lightboxContent.append($lightboxPrev);
            $lightboxContent.append($lightboxNext);
        }
        
        // Show lightbox
        $lightboxOverlay.addClass('active');
    });
    
    // Close lightbox
    $lightboxClose.on('click', function() {
        $lightboxOverlay.removeClass('active');
        $lightboxContent.find('img').remove();
        $lightboxPrev.detach();
        $lightboxNext.detach();
    });
    
    // Close on overlay click
    $lightboxOverlay.on('click', function(e) {
        if (e.target === this) {
            $lightboxClose.click();
        }
    });
    
    // Next image
    $lightboxNext.on('click', function() {
        currentIndex = (currentIndex + 1) % galleryImages.length;
        showImage(currentIndex);
    });
    
    // Previous image
    $lightboxPrev.on('click', function() {
        currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        showImage(currentIndex);
    });
    
    // Keyboard navigation
    $(document).on('keydown', function(e) {
        if (!$lightboxOverlay.hasClass('active')) {
            return;
        }
        
        // Escape key
        if (e.keyCode === 27) {
            $lightboxClose.click();
        }
        
        // Right arrow
        if (e.keyCode === 39 && galleryImages.length > 1) {
            $lightboxNext.click();
        }
        
        // Left arrow
        if (e.keyCode === 37 && galleryImages.length > 1) {
            $lightboxPrev.click();
        }
    });
    
    // Show image in lightbox
    function showImage(index) {
        const imageUrl = galleryImages[index];
        
        // Remove existing image
        $lightboxContent.find('img').remove();
        
        // Create new image
        const $img = $('<img src="' + imageUrl + '" alt="">');
        
        // Insert before close button
        $lightboxContent.prepend($img);
    }
    
})(jQuery);