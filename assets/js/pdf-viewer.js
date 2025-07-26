/**
 * CF7 Artist Submissions - PDF Viewer JavaScript
 * 
 * Interactive functionality for PDF and text submission viewing interface
 * with tab switching, file loading, and responsive viewer management.
 * 
 * @package CF7_Artist_Submissions
 * @subpackage PDFViewer
 * @since 1.2.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    
    /**
     * Initialize PDF viewer functionality
     * Make this function globally available for integration with tabs
     */
    function initPDFViewer() {
        setupTabSwitching();
        loadInitialContent();
        setupErrorHandling();
    }
    
    // Make initialization function globally available
    window.initPDFViewer = initPDFViewer;
    
    // =============================================================================
    // TAB SWITCHING
    // =============================================================================
    
    /**
     * Setup tab switching functionality
     */
    function setupTabSwitching() {
        $('.cf7-viewer-tab').on('click', function() {
            var $tab = $(this);
            var tabId = $tab.data('tab');
            
            // Update active tab
            $('.cf7-viewer-tab').removeClass('active');
            $tab.addClass('active');
            
            // Update active panel
            $('.cf7-viewer-panel').removeClass('active');
            $('.cf7-viewer-panel[data-panel="' + tabId + '"]').addClass('active');
            
            // Load content if needed
            loadPanelContent(tabId);
        });
    }
    
    // =============================================================================
    // CONTENT LOADING
    // =============================================================================
    
    /**
     * Load initial content for the first active tab
     */
    function loadInitialContent() {
        var $activeTab = $('.cf7-viewer-tab.active');
        if ($activeTab.length) {
            var tabId = $activeTab.data('tab');
            loadPanelContent(tabId);
        }
    }
    
    /**
     * Load content for a specific panel
     */
    function loadPanelContent(tabId) {
        var $panel = $('.cf7-viewer-panel[data-panel="' + tabId + '"]');
        var $body = $panel.find('.cf7-viewer-body');
        
        // Skip if already loaded or is text content
        if ($body.hasClass('loaded') || tabId === 'text_content') {
            return;
        }
        
        var fileId = $body.data('file-id');
        var fileType = $body.data('file-type');
        
        if (!fileId || !fileType) {
            showError($body, 'Missing file information');
            return;
        }
        
        // Show loading state
        showLoading($body);
        
        // Load content based on file type
        switch (fileType) {
            case 'pdf':
                loadPDFContent($body, fileId);
                break;
            case 'image':
                loadImageContent($body, fileId);
                break;
            case 'text':
                loadTextContent($body, fileId);
                break;
            default:
                showUnsupportedFile($body, fileType);
                break;
        }
    }
    
    /**
     * Load PDF content
     */
    function loadPDFContent($body, fileId) {
        // Get file URL from the viewer header link if available
        var $panel = $body.closest('.cf7-viewer-panel');
        var $link = $panel.find('.cf7-viewer-header a[target="_blank"]');
        var fileUrl = $link.length ? $link.attr('href') : '';
        
        if (!fileUrl) {
            showError($body, 'PDF file URL not found');
            return;
        }
        
        $.ajax({
            url: cf7_pdf_viewer.ajax_url,
            type: 'POST',
            data: {
                action: 'cf7_view_pdf',
                nonce: cf7_pdf_viewer.nonce,
                file_id: fileId,
                file_url: fileUrl
            },
            success: function(response) {
                if (response.success) {
                    $body.html(response.data.html);
                    $body.addClass('loaded');
                    
                    // Check if PDF embed is supported
                    setTimeout(function() {
                        checkPDFEmbed($body);
                    }, 1000);
                } else {
                    showError($body, response.data || 'Failed to load PDF');
                }
            },
            error: function() {
                showError($body, 'Network error while loading PDF');
            }
        });
    }
    
    /**
     * Load image content
     */
    function loadImageContent($body, fileId) {
        var $panel = $body.closest('.cf7-viewer-panel');
        var $link = $panel.find('.cf7-viewer-header a[target="_blank"]');
        var fileUrl = $link.length ? $link.attr('href') : '';
        
        if (!fileUrl) {
            showError($body, 'Image file URL not found');
            return;
        }
        
        var $img = $('<img>')
            .attr('src', fileUrl)
            .css({
                'max-width': '100%',
                'height': 'auto',
                'border': '1px solid #e1e5e9',
                'border-radius': '4px'
            })
            .on('load', function() {
                $body.html($img);
                $body.addClass('loaded');
            })
            .on('error', function() {
                showError($body, 'Failed to load image file');
            });
    }
    
    /**
     * Load text content
     */
    function loadTextContent($body, fileId) {
        // For text files, we would need the URL or content
        // This is a placeholder for future implementation
        showUnsupportedFile($body, 'text');
    }
    
    // =============================================================================
    // ERROR HANDLING AND STATES
    // =============================================================================
    
    /**
     * Show loading state
     */
    function showLoading($container) {
        $container.html('<div class="cf7-viewer-loading">Loading preview...</div>');
    }
    
    /**
     * Show error message
     */
    function showError($container, message) {
        var html = '<div class="cf7-viewer-error" style="text-align: center; padding: 40px 20px; color: #dc3545;">';
        html += '<p><strong>Error:</strong> ' + message + '</p>';
        html += '</div>';
        $container.html(html);
    }
    
    /**
     * Show unsupported file message
     */
    function showUnsupportedFile($container, fileType) {
        var $panel = $container.closest('.cf7-viewer-panel');
        var $link = $panel.find('.cf7-viewer-header a[target="_blank"]');
        
        var html = '<div class="cf7-viewer-unsupported" style="text-align: center; padding: 40px 20px; color: #6c757d;">';
        html += '<p>' + cf7_pdf_viewer.strings.unsupported + '</p>';
        
        if ($link.length) {
            html += '<p><a href="' + $link.attr('href') + '" target="_blank" class="cf7-btn cf7-btn-primary">';
            html += '<span class="dashicons dashicons-external"></span> Open File in New Tab</a></p>';
        }
        
        html += '</div>';
        $container.html(html);
        $container.addClass('loaded');
    }
    
    /**
     * Check if PDF embed is working
     */
    function checkPDFEmbed($body) {
        var $embed = $body.find('embed');
        var $fallback = $body.find('.cf7-pdf-fallback');
        
        if ($embed.length && $fallback.length) {
            // Simple check - if embed dimensions are 0, show fallback
            if ($embed[0].offsetHeight === 0) {
                $embed.parent().hide();
                $fallback.show();
            }
        }
    }
    
    /**
     * Setup global error handling
     */
    function setupErrorHandling() {
        // Handle PDF embed errors
        $(document).on('error', '.cf7-pdf-embed-container embed', function() {
            var $embed = $(this);
            var $container = $embed.closest('.cf7-pdf-viewer');
            var $fallback = $container.find('.cf7-pdf-fallback');
            
            $embed.parent().hide();
            $fallback.show();
        });
        
        // Handle image loading errors
        $(document).on('error', '.cf7-viewer-body img', function() {
            var $img = $(this);
            var $container = $img.closest('.cf7-viewer-body');
            showError($container, 'Failed to load image');
        });
    }
    
    // =============================================================================
    // RESPONSIVE HANDLING
    // =============================================================================
    
    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        $(window).on('resize', function() {
            // Adjust PDF embed height for mobile
            var $embeds = $('.cf7-pdf-embed-container embed');
            if ($(window).width() <= 768) {
                $embeds.height(400);
            } else {
                $embeds.height(600);
            }
        });
    }
    
    // =============================================================================
    // UTILITY FUNCTIONS
    // =============================================================================
    
    /**
     * Get file extension from URL
     */
    function getFileExtension(url) {
        return url.split('.').pop().toLowerCase();
    }
    
    /**
     * Check if file type is supported for preview
     */
    function isPreviewSupported(fileType) {
        var supportedTypes = ['pdf', 'image', 'jpg', 'jpeg', 'png', 'gif'];
        return supportedTypes.indexOf(fileType.toLowerCase()) !== -1;
    }
    
    // =============================================================================
    // INITIALIZATION CALL
    // =============================================================================
    
    // Initialize when DOM is ready
    if ($('.cf7-submission-viewer-container').length) {
        initPDFViewer();
        handleResponsive();
    }
});
