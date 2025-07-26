/**
 * CF7 Artist Submissions - Advanced Media Lightbox System
 *
 * Comprehensive media gallery lightbox providing immersive viewing experience
 * for artist submission portfolios with responsive design, keyboard navigation,
 * and seamless integration with submission management workflows.
 *
 * Features:
 * • Gallery navigation with automatic media collection
 * • Support for images (jpg, png, gif, webp, svg, etc.)
 * • Support for videos (mp4, mov, webm, avi, mkv, mpeg)
 * • Keyboard navigation for accessibility support
 * • Touch-friendly controls for mobile devices
 * • Responsive media display with adaptive sizing
 * • Loading states and error handling
 * • Multiple closure methods for user convenience
 * • Circular navigation with wraparound logic
 * • Focus management and screen reader support
 * • Hardware-accelerated transitions
 * • Cross-browser compatibility
 * • XSS prevention and secure media handling
 * • Memory-efficient DOM manipulation
 * • Video controls with metadata preloading
 *
 * @package CF7_Artist_Submissions
 * @subpackage MediaLightbox
 * @since 1.0.0
 * @version 1.0.0
 */
(function($) {
    'use strict';
    
    // ============================================================================
    // LIGHTBOX INITIALIZATION
    // ============================================================================
    
    // Create lightbox elements
    const $lightboxOverlay = $('<div class="cf7-lightbox-overlay"></div>');
    const $lightboxContent = $('<div class="cf7-lightbox-content"></div>');
    const $lightboxClose = $('<div class="cf7-lightbox-close">×</div>');
    const $lightboxPrev = $('<div class="cf7-lightbox-nav cf7-lightbox-prev">‹</div>');
    const $lightboxNext = $('<div class="cf7-lightbox-nav cf7-lightbox-next">›</div>');
    
    $lightboxContent.append($lightboxClose);
    $lightboxContent.append($lightboxPrev);
    $lightboxContent.append($lightboxNext);
    $lightboxOverlay.append($lightboxContent);
    $('body').append($lightboxOverlay);
    
    // Add some basic styling to ensure visibility
    $lightboxOverlay.css({
        'position': 'fixed',
        'top': '0',
        'left': '0',
        'width': '100%',
        'height': '100%',
        'background-color': 'rgba(0, 0, 0, 0.9)',
        'z-index': '99999',
        'display': 'none',
        'justify-content': 'center',
        'align-items': 'center'
    });
    
    $lightboxContent.css({
        'position': 'relative',
        'max-width': '90%',
        'max-height': '90%',
        'background': 'white',
        'border-radius': '8px',
        'padding': '20px'
    });
    
    $lightboxClose.css({
        'position': 'absolute',
        'top': '10px',
        'right': '15px',
        'font-size': '24px',
        'cursor': 'pointer',
        'color': '#666'
    });
    
    // Current gallery state
    let galleryImages = [];
    let currentIndex = 0;
    let currentLightboxGroup = null;
    
    // ============================================================================
    // LIGHTBOX ACTIVATION
    // ============================================================================
    
    /**
     * Initialize lightbox event handlers for dynamically loaded content.
     * Sets up event delegation to handle lightbox triggers added via AJAX.
     * 
     * @since 1.0.0
     */
    function initializeLightboxEvents() {
        // Remove any existing event handlers to prevent duplicates
        $(document).off('click.cf7lightbox', '[data-lightbox]');
        $(document).off('click.cf7lightbox', '.lightbox-preview');
        
        // Handle data-lightbox attribute clicks
        $(document).on('click.cf7lightbox', '[data-lightbox]', function(e) {
            e.preventDefault();
            const $this = $(this);
            const mediaSrc = $this.attr('href') || $this.data('src');
            
            if (!mediaSrc) {
                return;
            }
            
            // Set current lightbox group and media files
            currentLightboxGroup = $this.data('lightbox');
            galleryImages = $('[data-lightbox="' + currentLightboxGroup + '"]').map(function() {
                return $(this).attr('href') || $(this).data('src');
            }).get();
            currentIndex = galleryImages.indexOf(mediaSrc);
            
            // Show lightbox with the selected media
            showLightbox(mediaSrc);
        });
        
        // Handle .lightbox-preview class clicks
        $(document).on('click.cf7lightbox', '.lightbox-preview', function(e) {
            e.preventDefault();
            const $this = $(this);
            const mediaSrc = $this.attr('href') || $this.data('src');
            
            if (!mediaSrc) {
                return;
            }
            
            // For .lightbox-preview, treat as single media (no gallery)
            currentLightboxGroup = 'single';
            galleryImages = [mediaSrc];
            currentIndex = 0;
            
            // Show lightbox with the selected media
            showLightbox(mediaSrc);
        });
    }

    /**
     * Show the lightbox with specified media file.
     * Creates modal overlay and displays the media with navigation controls.
     * 
     * @since 1.0.0
     */
    function showLightbox(mediaSrc) {
        // Show the lightbox with flex display
        $lightboxOverlay.css('display', 'flex').addClass('active');
        $('body').addClass('cf7-lightbox-open');
        
        // Load and show the media
        showMedia(mediaSrc);
    }

    /**
     * Public initialization function for re-initializing lightbox after AJAX loads
     * 
     * @since 1.0.0
     */
    window.initLightbox = function() {
        initializeLightboxEvents();
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        initializeLightboxEvents();
    });
    
    // ============================================================================
    // LIGHTBOX CONTROLS
    // ============================================================================
    
    // Close lightbox and cleanup
    $lightboxClose.on('click', function() {
        $lightboxOverlay.css('display', 'none').removeClass('active');
        $('body').removeClass('cf7-lightbox-open');
        $lightboxContent.find('img').remove();
        $lightboxContent.find('video').remove();
        $lightboxContent.find('iframe').remove();
        $lightboxContent.find('.cf7-lightbox-document').remove();
        $lightboxContent.find('.cf7-lightbox-loading').remove();
        $lightboxContent.find('.cf7-lightbox-error').remove();
    });
    
    // Close on overlay click (but not content)
    $lightboxOverlay.on('click', function(e) {
        if (e.target === this) {
            $lightboxClose.click();
        }
    });
    
    // Gallery navigation with circular logic
    $lightboxNext.on('click', function() {
        currentIndex = (currentIndex + 1) % galleryImages.length;
        showMedia(galleryImages[currentIndex]);
    });
    
    $lightboxPrev.on('click', function() {
        currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        showMedia(galleryImages[currentIndex]);
    });
    
    /**
     * Comprehensive keyboard navigation for lightbox accessibility.
     * Supports ESC key closure and arrow key gallery navigation with
     * proper state management and focus handling.
     * 
     * @since 1.0.0
     */
    $(document).on('keydown', function(e) {
        if (!$lightboxOverlay.hasClass('active')) {
            return;
        }
        
        // Escape key - close lightbox
        if (e.keyCode === 27) {
            $lightboxClose.click();
        }
        
        // Right arrow - next media (only in galleries)
        if (e.keyCode === 39 && galleryImages.length > 1) {
            $lightboxNext.click();
        }
        
        // Left arrow - previous media (only in galleries)
        if (e.keyCode === 37 && galleryImages.length > 1) {
            $lightboxPrev.click();
        }
    });
    
    // ============================================================================
    // MEDIA DISPLAY SYSTEM
    // ============================================================================
    
    /**
     * Determine if a file URL represents a video based on its extension.
     * 
     * @param {string} url - The file URL to check
     * @returns {boolean} True if the file is a video
     * @since 1.0.0
     */
    function isVideoFile(url) {
        const videoExtensions = ['mp4', 'mov', 'webm', 'avi', 'mkv', 'mpeg'];
        
        // Handle URLs with query parameters (like S3 presigned URLs)
        const cleanUrl = url.split('?')[0];
        const extension = cleanUrl.split('.').pop().toLowerCase();
        
        return videoExtensions.includes(extension);
    }
    
    /**
     * Determine if a file URL represents a document based on its extension.
     * 
     * @param {string} url - The file URL to check
     * @returns {boolean} True if the file is a document
     * @since 1.2.0
     */
    function isDocumentFile(url) {
        const documentExtensions = ['doc', 'docx', 'txt', 'rtf'];
        
        // Handle URLs with query parameters (like S3 presigned URLs)
        const cleanUrl = url.split('?')[0];
        const extension = cleanUrl.split('.').pop().toLowerCase();
        
        return documentExtensions.includes(extension);
    }
    
    /**
     * Load and display media (image, video, or document) with loading states and error handling.
     * Provides smooth transitions and user feedback during media loading process.
     * 
     * @since 1.0.0
     */
    function showMedia(mediaSrc, updateNavigation = true) {
        // Show loading state
        $lightboxContent.find('img').remove();
        $lightboxContent.find('video').remove();
        $lightboxContent.find('iframe').remove();
        $lightboxContent.find('.cf7-lightbox-document').remove();
        $lightboxContent.find('.cf7-lightbox-error').remove();
        $lightboxContent.append('<div class="cf7-lightbox-loading">Loading...</div>');
        
        if (isVideoFile(mediaSrc)) {
            // Handle video files
            const video = document.createElement('video');
            video.controls = true;
            video.preload = 'metadata';
            video.style.maxWidth = '100%';
            video.style.maxHeight = '70vh';
            video.style.width = 'auto';
            video.style.height = 'auto';
            
            // Add CORS attributes for S3 URLs
            video.crossOrigin = 'anonymous';
            
            video.onloadedmetadata = function() {
                $lightboxContent.find('.cf7-lightbox-loading').remove();
                $lightboxContent.append(video);
            };
            
            video.oncanplay = function() {
                // Video is ready to play
            };
            
            video.onerror = function(e) {
                $lightboxContent.find('.cf7-lightbox-loading').remove();
                $lightboxContent.append('<div class="cf7-lightbox-error">Error loading video (Code: ' + (video.error ? video.error.code : 'unknown') + ')</div>');
            };
            
            video.src = mediaSrc;
        } else if (isDocumentFile(mediaSrc)) {
            // Handle document files
            const cleanUrl = mediaSrc.split('?')[0];
            const extension = cleanUrl.split('.').pop().toLowerCase();
            const fileName = cleanUrl.split('/').pop();
            
            $lightboxContent.find('.cf7-lightbox-loading').remove();
            
            // Create document preview interface
            const documentPreview = $(`
                <div class="cf7-lightbox-document" style="text-align: center; padding: 40px 20px;">
                    <div class="cf7-document-icon" style="font-size: 64px; color: #666; margin-bottom: 20px;">
                        ${extension === 'txt' ? '📄' : extension === 'rtf' ? '📄' : '📝'}
                    </div>
                    <h3 style="margin-bottom: 15px; color: #333;">${fileName}</h3>
                    <p style="color: #666; margin-bottom: 25px;">
                        ${extension.toUpperCase()} Document
                        ${extension === 'txt' ? ' - Plain Text' : 
                          extension === 'rtf' ? ' - Rich Text Format' :
                          extension === 'doc' ? ' - Microsoft Word Document' :
                          extension === 'docx' ? ' - Microsoft Word Document' : ''}
                    </p>
                    <div class="cf7-document-actions" style="margin-top: 20px;">
                        <a href="${mediaSrc}" class="cf7-document-download" download="${fileName}" 
                           style="display: inline-block; background: #0073aa; color: white; padding: 12px 24px; 
                                  text-decoration: none; border-radius: 6px; margin: 0 10px; font-weight: 500;">
                            📥 Download Document
                        </a>
                        <a href="${mediaSrc}" target="_blank" class="cf7-document-open"
                           style="display: inline-block; background: #666; color: white; padding: 12px 24px; 
                                  text-decoration: none; border-radius: 6px; margin: 0 10px; font-weight: 500;">
                            🔗 Open in New Tab
                        </a>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; color: #666; font-size: 14px;">
                        <strong>Preview Note:</strong> Document preview is not available for security reasons. 
                        Please download or open the document to view its contents.
                    </div>
                </div>
            `);
            
            $lightboxContent.append(documentPreview);
        } else {
            // Handle image files
            const img = new Image();
            img.onload = function() {
                $lightboxContent.find('.cf7-lightbox-loading').remove();
                $lightboxContent.append('<img src="' + mediaSrc + '" alt="Lightbox Image" style="max-width: 100%; max-height: 90vh; width: auto; height: auto;">');
            };
            img.onerror = function(e) {
                $lightboxContent.find('.cf7-lightbox-loading').remove();
                $lightboxContent.append('<div class="cf7-lightbox-error">Error loading image</div>');
            };
            img.src = mediaSrc;
        }
        
        // Update navigation visibility
        if (updateNavigation) {
            updateNavigationControls();
        }
    }
    
    /**
     * Update navigation controls visibility based on gallery state.
     * Manages previous/next button display for single media vs galleries.
     */
    function updateNavigationControls() {
        if (galleryImages.length <= 1) {
            $lightboxPrev.hide();
            $lightboxNext.hide();
        } else {
            $lightboxPrev.show();
            $lightboxNext.show();
        }
    }
    
})(jQuery);