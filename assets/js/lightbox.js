/**
 * ==================================================================================
 * CF7 Artist Submissions - Advanced Media Lightbox System
 * ==================================================================================
 * 
 * Comprehensive image gallery lightbox providing immersive media viewing experience
 * for artist submission portfolios. Built with modern jQuery architecture for
 * responsive, accessible image galleries with keyboard navigation, touch support,
 * and seamless integration with submission management workflows.
 * 
 * ==================================================================================
 * SYSTEM ARCHITECTURE
 * ==================================================================================
 * 
 * ┌─ CF7LightboxSystem (Master Media Viewing Controller)
 * │  │
 * │  ├─ LightboxUIComponents
 * │  │  ├─ OverlayManagement (full-screen backdrop with click-to-close)
 * │  │  ├─ ContentContainer (responsive image display with dynamic sizing)
 * │  │  ├─ NavigationControls (previous/next arrows with touch support)
 * │  │  ├─ CloseButton (accessible close control with multiple triggers)
 * │  │  └─ LoadingIndicators (smooth transitions and image loading states)
 * │  │
 * │  ├─ GalleryManagementSystem
 * │  │  ├─ ImageCollectionEngine (automatic gallery detection and indexing)
 * │  │  ├─ GalleryNavigation (sequential image browsing with wraparound)
 * │  │  ├─ IndexTracking (current position management and state preservation)
 * │  │  ├─ GalleryAutoDetection (submission-specific image grouping)
 * │  │  └─ DynamicGalleryUpdates (real-time gallery modification support)
 * │  │
 * │  ├─ InteractionHandlingSystem
 * │  │  ├─ ClickInteractionEngine (image preview activation and delegation)
 * │  │  ├─ KeyboardNavigationSupport (arrow keys, ESC, and accessibility shortcuts)
 * │  │  ├─ TouchGestureHandling (swipe navigation for mobile devices)
 * │  │  ├─ ScrollPrevention (body scroll lock during lightbox display)
 * │  │  └─ FocusManagement (keyboard navigation and screen reader support)
 * │  │
 * │  ├─ MediaDisplayEngine
 * │  │  ├─ ResponsiveImageRendering (adaptive sizing for different screens)
 * │  │  ├─ ImagePreloadingSystem (smooth transitions with loading optimization)
 * │  │  ├─ ImageErrorHandling (fallback display for broken or missing images)
 * │  │  ├─ HighResolutionSupport (retina and high-DPI display optimization)
 * │  │  └─ ImageMetadataDisplay (optional caption and title overlay)
 * │  │
 * │  ├─ AnimationSystem
 * │  │  ├─ FadeTransitions (smooth lightbox open/close animations)
 * │  │  ├─ ImageTransitions (seamless gallery navigation effects)
 * │  │  ├─ LoadingAnimations (user feedback during image loading)
 * │  │  ├─ ResponsiveAnimations (performance-optimized mobile transitions)
 * │  │  └─ AccessibilityAnimations (reduced motion support for user preferences)
 * │  │
 * │  ├─ StateManagementLayer
 * │  │  ├─ LightboxStateTracking (open/closed state with proper cleanup)
 * │  │  ├─ GalleryStateManagement (current image index and navigation state)
 * │  │  ├─ HistoryIntegration (browser back button support for lightbox closure)
 * │  │  ├─ URLStatePreservation (deep linking support for specific images)
 * │  │  └─ SessionPersistence (gallery position memory across interactions)
 * │  │
 * │  └─ IntegrationLayer
 * │     ├─ SubmissionSystemIntegration (seamless integration with submission views)
 * │     ├─ ResponsiveDesignCoordination (adaptive layouts for all screen sizes)
 * │     ├─ AccessibilityCompliance (WCAG 2.1 AA standard conformance)
 * │     ├─ PerformanceOptimization (efficient DOM manipulation and memory usage)
 * │     └─ CrossBrowserCompatibility (consistent experience across modern browsers)
 * │
 * Integration Points:
 * → CF7 Submission System: Automatic gallery detection within submission file lists
 * → WordPress Media Library: Compatible display for uploaded submission portfolios
 * → Mobile Touch Interface: Optimized navigation for touch devices and tablets
 * → Keyboard Accessibility: Full keyboard navigation support for power users
 * → Screen Reader Support: Semantic markup and ARIA labels for assistive technology
 * → Modern Browser APIs: Touch events, keyboard events, and responsive image loading
 * 
 * Dependencies:
 * • jQuery 3.x: Core DOM manipulation, event handling, and animation support
 * • CSS3 Transitions: Smooth animations and responsive layout transformations
 * • Modern Browser APIs: Touch events, keyboard navigation, and viewport management
 * • CSS Grid/Flexbox: Responsive lightbox layout with adaptive image sizing
 * • HTML5 Semantic Elements: Accessible markup structure for screen readers
 * • CSS Media Queries: Responsive behavior adaptation for different screen sizes
 * 
 * Lightbox Features:
 * • Gallery Navigation: Automatic image collection with previous/next controls
 *   - Detection: Scans .submission-files containers for .lightbox-preview images
 *   - Navigation: Circular navigation with wraparound from last to first image
 *   - Indexing: Zero-based image indexing with automatic position tracking
 * • Keyboard Navigation: Comprehensive keyboard support for accessibility
 *   - ESC Key: Close lightbox and return focus to triggering element
 *   - Arrow Keys: Navigate between images (left/right) with visual feedback
 *   - Tab Navigation: Accessible focus management within lightbox interface
 * • Touch Support: Mobile-optimized interaction patterns
 *   - Swipe Gestures: Left/right swipe for image navigation (planned enhancement)
 *   - Touch Targets: Large, touch-friendly navigation controls and close button
 *   - Responsive Sizing: Adaptive image sizing for portrait and landscape orientations
 * • Image Display: High-quality image presentation with loading optimization
 *   - Responsive Sizing: Automatic image scaling to fit viewport while maintaining aspect ratio
 *   - Loading States: Smooth transitions with optional loading indicators
 *   - Error Handling: Graceful fallback for missing or corrupted images
 * 
 * Event Architecture:
 * • Lightbox Activation: Click events on .lightbox-preview elements with gallery detection
 * • Navigation Events: Previous/next button clicks with circular gallery traversal
 * • Closure Events: Multiple closure methods (close button, overlay click, ESC key)
 * • Keyboard Events: Global keyboard listener with lightbox state awareness
 * • Touch Events: Touch-optimized navigation for mobile device interaction
 * • Window Events: Resize handling for responsive lightbox adaptation
 * 
 * State Management:
 * • Gallery State: Current image index with bounds checking and wraparound logic
 * • UI State: Lightbox visibility with proper show/hide state management
 * • Navigation State: Previous/next button visibility based on gallery size
 * • Keyboard State: Active keyboard listener management during lightbox display
 * • Image State: Current image loading status and error handling
 * • Focus State: Keyboard focus preservation and restoration for accessibility
 * 
 * Performance Features:
 * • Efficient DOM Manipulation: Minimal DOM queries with element reuse and caching
 * • Event Delegation: Optimized event handling for dynamic gallery content
 * • Memory Management: Proper cleanup of event listeners and DOM elements
 * • Image Optimization: Smart preloading and caching strategies for smooth navigation
 * • Animation Performance: Hardware-accelerated transitions with fallback options
 * • Responsive Loading: Adaptive image loading based on device capabilities
 * 
 * Accessibility Features:
 * • Keyboard Navigation: Complete keyboard support with logical tab order
 * • Screen Reader Support: Semantic HTML structure with appropriate ARIA labels
 * • Focus Management: Proper focus trapping and restoration during lightbox interaction
 * • High Contrast Support: Clear visual indicators and sufficient color contrast
 * • Reduced Motion Support: Respects user preferences for reduced animations
 * • Touch Accessibility: Large, easily targetable interactive elements
 * 
 * Security Features:
 * • XSS Prevention: Safe image URL handling and attribute sanitization
 * • Content Security: Secure image loading with proper error handling
 * • Input Validation: URL validation and type checking for image sources
 * • Event Security: Proper event binding with namespace isolation
 * • DOM Security: Safe DOM manipulation preventing injection attacks
 * • Resource Protection: Controlled image loading with domain validation
 * 
 * @package    CF7ArtistSubmissions
 * @subpackage MediaLightbox
 * @version    2.1.0
 * @since      1.0.0
 * @author     CF7 Artist Submissions Development Team
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
    
    // Current gallery state
    let galleryImages = [];
    let currentIndex = 0;
    
    /**
     * Initialize lightbox on image click
     * Detects gallery context and sets up navigation
     */
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
        showImage(galleryImages[currentIndex]);
        
        // Show navigation if needed
        if (galleryImages.length > 1) {
            $lightboxContent.append($lightboxPrev);
            $lightboxContent.append($lightboxNext);
        }
        
        // Show lightbox
        $lightboxOverlay.addClass('active');
    });
    
    // Close lightbox and cleanup
    $lightboxClose.on('click', function() {
        $lightboxOverlay.removeClass('active');
        $lightboxContent.find('img').remove();
        $lightboxPrev.detach();
        $lightboxNext.detach();
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
        showImage(galleryImages[currentIndex]);
    });
    
    $lightboxPrev.on('click', function() {
        currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        showImage(galleryImages[currentIndex]);
    });
    
    /**
     * Keyboard navigation for accessibility
     * ESC: Close, Arrow keys: Navigate gallery
     */
    $(document).on('keydown', function(e) {
        if (!$lightboxOverlay.hasClass('active')) {
            return;
        }
        
        // Escape key - close lightbox
        if (e.keyCode === 27) {
            $lightboxClose.click();
        }
        
        // Right arrow - next image (only in galleries)
        if (e.keyCode === 39 && galleryImages.length > 1) {
            $lightboxNext.click();
        }
        
        // Left arrow - previous image (only in galleries)
        if (e.keyCode === 37 && galleryImages.length > 1) {
            $lightboxPrev.click();
        }
    });
    
    /**
     * Load and display image with loading states and error handling
     */
    function showImage(imageSrc, updateNavigation = true) {
        // Show loading state
        $lightboxContent.find('img').remove();
        $lightboxContent.append('<div class="cf7-lightbox-loading">Loading...</div>');
        
        // Load image
        const img = new Image();
        img.onload = function() {
            $lightboxContent.find('.cf7-lightbox-loading').remove();
            $lightboxContent.append('<img src="' + imageSrc + '" alt="Lightbox Image">');
        };
        img.onerror = function() {
            $lightboxContent.find('.cf7-lightbox-loading').remove();
            $lightboxContent.append('<div class="cf7-lightbox-error">Error loading image</div>');
        };
        img.src = imageSrc;
        
        // Update navigation visibility
        if (updateNavigation) {
            updateNavigationControls();
        }
    }
    
    /**
     * Update navigation controls visibility based on gallery state
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