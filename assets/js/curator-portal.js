/**
 * Secure Curator Portal JavaScript - API-Only Version
 * 
 * This script handles all frontend interactions for the curator portal
 * using ONLY secure API endpoints. No direct backend access.
 * 
 * @version 1.3.0
 */

(function($) {
    'use strict';

    // Global portal object
    window.CF7SecurePortal = {
        config: window.CF7CuratorPortalConfig || {},
        session: null,
        
        /**
         * Initialize the secure portal
         */
        init: function() {
            console.log('CF7SecurePortal: Initializing...');
            
            // Check for existing session
            this.loadSession();
            console.log('CF7SecurePortal: Session loaded:', this.session);
            
            // Check for token in URL (from email links)
            const urlToken = this.getTokenFromUrl();
            console.log('CF7SecurePortal: URL token:', urlToken);
            
            if (urlToken) {
                console.log('CF7SecurePortal: Processing URL token...');
                // Process token authentication first
                this.processUrlToken(urlToken);
                return;
            }
            
            // Initialize based on current page type
            if (this.isSubmissionPage()) {
                console.log('CF7SecurePortal: Initializing submission page...');
                this.initSubmissionPage();
            } else {
                console.log('CF7SecurePortal: Initializing main portal...');
                this.initMainPortal();
            }
        },
        
        /**
         * Check if this is a submission page
         */
        isSubmissionPage: function() {
            return this.config.submissionId && this.config.submissionId > 0;
        },
        
        /**
         * Get token from URL parameters (from email links)
         */
        getTokenFromUrl: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const pathParts = window.location.pathname.split('/');
            
            console.log('CF7SecurePortal: getTokenFromUrl - URL:', window.location.href);
            console.log('CF7SecurePortal: getTokenFromUrl - Search params:', urlParams.toString());
            console.log('CF7SecurePortal: getTokenFromUrl - Path parts:', pathParts);
            
            // Check URL parameters first (?curator_token=...)
            if (urlParams.has('curator_token')) {
                const token = urlParams.get('curator_token');
                console.log('CF7SecurePortal: Found token in URL params:', token);
                return token;
            }
            
            // Check path segments (../curator-portal/TOKEN/)
            const portalIndex = pathParts.indexOf('curator-portal');
            console.log('CF7SecurePortal: Portal index in path:', portalIndex);
            
            if (portalIndex !== -1 && pathParts[portalIndex + 1]) {
                const potentialToken = pathParts[portalIndex + 1];
                console.log('CF7SecurePortal: Potential token from path:', potentialToken);
                
                // Basic token validation (should be alphanumeric, reasonable length)
                if (potentialToken.length > 10 && /^[a-zA-Z0-9]+$/.test(potentialToken)) {
                    console.log('CF7SecurePortal: Valid token found in path:', potentialToken);
                    return potentialToken;
                } else {
                    console.log('CF7SecurePortal: Invalid token format in path:', potentialToken);
                }
            }
            
            console.log('CF7SecurePortal: No token found in URL');
            return null;
        },
        
        /**
         * Process token from URL and authenticate
         */
        processUrlToken: function(token) {
            console.log('CF7SecurePortal: Processing token:', token);
            
            // Show processing message
            $('#cf7-portal-loading').show();
            $('#cf7-portal-auth').hide();
            $('#cf7-portal-main').hide();
            
            console.log('CF7SecurePortal: Making API call for token verification...');
            
            // Verify token and create session
            this.apiCall('cf7_portal_authenticate', {
                action_type: 'verify_token',
                token: token
            })
            .done((response) => {
                console.log('CF7SecurePortal: Token verification response:', response);
                
                if (response.success && response.data.session_token) {
                    console.log('CF7SecurePortal: Token verified successfully, saving session...');
                    
                    // Save session data
                    this.saveSession({
                        token: response.data.session_token,
                        curator: response.data.curator || {},
                        expires: response.data.expires || (Date.now() + (7 * 24 * 60 * 60 * 1000))
                    });
                    
                    console.log('CF7SecurePortal: Session saved, redirecting to clean URL...');
                    
                    // Redirect to clean URL (remove token from URL)
                    const cleanUrl = this.config.portalUrl || window.location.origin + window.location.pathname.split('/curator-portal/')[0] + '/curator-portal/';
                    console.log('CF7SecurePortal: Redirecting to:', cleanUrl);
                    
                    window.location.href = cleanUrl;
                } else {
                    console.log('CF7SecurePortal: Token verification failed:', response);
                    // Token verification failed
                    this.showTokenError(response.data ? response.data.message : 'Invalid access link');
                }
            })
            .fail((xhr, status, error) => {
                console.log('CF7SecurePortal: Token verification API call failed:', xhr, status, error);
                this.showTokenError('Error processing access link');
            });
        },
        
        /**
         * Show token verification error
         */
        showTokenError: function(message) {
            $('#cf7-portal-loading').hide();
            $('#cf7-portal-main').hide();
            $('#cf7-portal-auth').html(`
                <div class="cf7-portal-login-card">
                    <h2>Access Link Error</h2>
                    <div class="cf7-message cf7-message-error">
                        ${message}
                    </div>
                    <p>Please request a new access link:</p>
                    <form id="cf7-auth-form">
                        <div class="cf7-form-group">
                            <label for="curator-email">Email Address</label>
                            <input type="email" id="curator-email" name="email" required>
                        </div>
                        <button type="submit" class="cf7-btn cf7-btn-primary">Request New Access Link</button>
                    </form>
                    <div id="cf7-auth-status" class="cf7-auth-status"></div>
                </div>
            `).show();
            
            // Bind form submission
            $('#cf7-auth-form').on('submit', (e) => {
                e.preventDefault();
                this.handleEmailRequest();
            });
        },
        
        /**
         * Load session from localStorage
         */
        loadSession: function() {
            try {
                const sessionData = localStorage.getItem('cf7_curator_session');
                if (sessionData) {
                    this.session = JSON.parse(sessionData);
                }
            } catch (e) {
                localStorage.removeItem('cf7_curator_session');
            }
        },
        
        /**
         * Save session to localStorage
         */
        saveSession: function(sessionData) {
            try {
                localStorage.setItem('cf7_curator_session', JSON.stringify(sessionData));
                this.session = sessionData;
            } catch (e) {
                // Session storage failed - continue without session persistence
            }
        },
        
        /**
         * Initialize submission page
         */
        initSubmissionPage: function() {
            
            if (!this.session || !this.session.token) {
                this.redirectToLogin();
                return;
            }
            
            // Validate session and load content
            this.validateSessionAndLoadSubmission();
        },
        
        /**
         * Initialize main portal
         */
        initMainPortal: function() {
            console.log('CF7SecurePortal: initMainPortal - checking session:', this.session);
            
            if (!this.session || !this.session.token) {
                console.log('CF7SecurePortal: No session found, showing login form...');
                this.showLoginForm();
            } else {
                console.log('CF7SecurePortal: Session found, validating...');
                this.validateSessionAndLoadPortal();
            }
        },
        
        /**
         * Validate session and load submission page
         */
        validateSessionAndLoadSubmission: function() {
            this.apiCall('cf7_portal_validate_session', {
                session_token: this.session.token
            })
            .done((response) => {
                if (response.success) {
                    $('#auth-check').hide();
                    $('#submission-content').show();
                    this.loadSubmissionData();
                } else {
                    this.redirectToLogin();
                }
            })
            .fail(() => {
                this.redirectToLogin();
            });
        },
        
        /**
         * Load submission data via secure API
         */
        loadSubmissionData: function() {
            this.apiCall('cf7_portal_get_submission_details', {
                submission_id: this.config.submissionId,
                session_token: this.session.token
            })
            .done((response) => {
                if (response.success && response.data) {
                    this.populateSubmissionHeader(response.data);
                    this.loadTabContent('profile'); // Load default tab
                } else {
                    this.showError('Failed to load submission data');
                }
            })
            .fail(() => {
                this.showError('Error loading submission data');
            });
        },
        
        /**
         * Populate submission header
         */
        populateSubmissionHeader: function(data) {
            if (data.title) {
                $('#submission-title').text(data.title);
            }
            if (data.date && data.id) {
                $('#submission-meta').text('Submitted on ' + data.date + ' | ID: ' + data.id);
            }
        },
        
        /**
         * Load tab content via secure API
         */
        loadTabContent: function(tabName) {
            const tabPane = $('#' + tabName);
            
            // Skip if already loaded
            if (tabPane.find('.loading-content').length === 0) {
                return;
            }
            
            this.apiCall('cf7_portal_get_tab_content', {
                submission_id: this.config.submissionId,
                tab: tabName,
                session_token: this.session.token
            })
            .done((response) => {
                if (response.success && response.data.content) {
                    tabPane.html(response.data.content);
                } else {
                    tabPane.html('<div class="error-content">Failed to load ' + tabName + ' content</div>');
                }
            })
            .fail(() => {
                tabPane.html('<div class="error-content">Error loading ' + tabName + ' content</div>');
            });
        },
        
        /**
         * Show login form - GENERATE EMAIL FORM IF NEEDED
         */
        showLoginForm: function() {
            // Hide loading and main sections
            $('#cf7-portal-loading').hide();
            $('#cf7-portal-main').hide();
            
            // Check if we have the PHP email form (render_portal template)
            const authSection = $('#cf7-portal-auth');
            const existingForm = $('#cf7-auth-form');
            
            if (existingForm.length > 0) {
                // We have the PHP form, use it
                authSection.show();
                existingForm.on('submit', (e) => {
                    e.preventDefault();
                    this.handleEmailRequest();
                });
            } else {
                // We're on clean URL template, generate our own form
                const loginHtml = `
                    <div class="cf7-portal-login-card">
                        <h2>Curator Portal Access</h2>
                        <p>Please enter your email address to receive a secure access link.</p>
                        <form id="cf7-auth-form">
                            <div class="cf7-form-group">
                                <label for="curator-email">Email Address</label>
                                <input type="email" id="curator-email" name="email" required>
                            </div>
                            <button type="submit" class="cf7-btn cf7-btn-primary">Request Access Link</button>
                        </form>
                        <div id="cf7-auth-status" class="cf7-auth-status"></div>
                    </div>
                `;
                
                authSection.html(loginHtml).show();
                
                // Bind the new form
                $('#cf7-auth-form').on('submit', (e) => {
                    e.preventDefault();
                    this.handleEmailRequest();
                });
            }
        },
        
        /**
         * Handle email request form submission - NO PASSWORD REQUIRED
         */
        handleEmailRequest: function() {
            const email = $('#curator-email').val();
            
            if (!email) {
                this.showMessage('Please enter your email address', 'error');
                return;
            }
            
            this.showMessage('Sending access link...', 'info');
            
            this.apiCall('cf7_portal_curator_login', {
                email: email
            })
            .done((response) => {
                if (response.success) {
                    this.showMessage('Access link sent! Please check your email for the secure login link.', 'success');
                    
                    // Optional: Show additional instructions
                    setTimeout(() => {
                        this.showMessage('Check your email (including spam folder) for the access link. The link will be valid for 24 hours.', 'info');
                    }, 3000);
                } else {
                    this.showMessage(response.data.message || 'Failed to send access link', 'error');
                }
            })
            .fail(() => {
                this.showMessage('Error occurred while sending access link', 'error');
            });
        },
        
        /**
         * Validate session and load main portal
         */
        validateSessionAndLoadPortal: function() {
            console.log('CF7SecurePortal: Validating session with token:', this.session.token);
            
            this.apiCall('cf7_portal_validate_session', {
                session_token: this.session.token
            })
            .done((response) => {
                console.log('CF7SecurePortal: Session validation response:', response);
                
                if (response.success) {
                    console.log('CF7SecurePortal: Session valid, loading main portal interface...');
                    this.loadMainPortalInterface();
                } else {
                    console.log('CF7SecurePortal: Session invalid, clearing session and showing login form...');
                    console.log('CF7SecurePortal: Session validation error details:', response.data);
                    this.clearSession();
                    this.showLoginForm();
                }
            })
            .fail((xhr, status, error) => {
                console.log('CF7SecurePortal: Session validation API call failed:', xhr, status, error);
                this.clearSession();
                this.showLoginForm();
            });
        },
        
        /**
         * Load main portal interface - USE EXISTING PHP TEMPLATE
         */
        loadMainPortalInterface: function() {
            console.log('CF7SecurePortal: Loading main portal interface...');
            
            // Hide auth section, show main portal section
            $('#cf7-portal-auth').hide();
            $('#cf7-portal-loading').hide();
            $('#cf7-portal-main').show();
            
            console.log('CF7SecurePortal: Portal sections updated - auth hidden, main shown');
            
            // Update user info if session contains curator data
            if (this.session && this.session.curator) {
                console.log('CF7SecurePortal: Updating user info with curator:', this.session.curator);
                $('.cf7-portal-user-info').show().find('.cf7-curator-name').text(this.session.curator.name);
            }
            
            // Bind logout button if it exists
            $('#cf7-portal-logout').on('click', () => {
                console.log('CF7SecurePortal: Logout button clicked');
                this.handleLogout();
            });
            
            console.log('CF7SecurePortal: Loading statistics and submissions...');
            
            // Load data into existing structure
            this.loadStatistics();
            this.loadSubmissions();
        },
        
        /**
         * Load portal statistics - WORKS WITH PHP TEMPLATE
         */
        loadStatistics: function() {
            console.log('CF7SecurePortal: Loading statistics...');
            
            this.apiCall('cf7_portal_get_statistics', {
                session_token: this.session.token
            })
            .done((response) => {
                console.log('CF7SecurePortal: Statistics API response:', response);
                
                if (response.success && response.data) {
                    const stats = response.data;
                    console.log('CF7SecurePortal: Statistics data:', stats);
                    
                    // Update PHP template elements
                    $('#cf7-total-submissions').text(stats.total_submissions || 0);
                    $('#cf7-rated-submissions').text(stats.completed_reviews || 0);
                } else {
                    console.log('CF7SecurePortal: Statistics API error:', response);
                    $('#cf7-total-submissions, #cf7-rated-submissions').text('Error');
                }
            })
            .fail((xhr, status, error) => {
                console.log('CF7SecurePortal: Statistics API call failed:', xhr, status, error);
                $('#cf7-total-submissions, #cf7-rated-submissions').text('Error');
            });
        },
        
        /**
         * Load submissions list - WORKS WITH PHP TEMPLATE
         */
        loadSubmissions: function() {
            console.log('CF7SecurePortal: Loading submissions...');
            
            this.apiCall('cf7_portal_get_submissions', {
                session_token: this.session.token,
                page: 1,
                per_page: 20
            })
            .done((response) => {
                console.log('CF7SecurePortal: Submissions API response:', response);
                
                if (response.success && response.data && response.data.submissions) {
                    console.log('CF7SecurePortal: Found submissions:', response.data.submissions.length);
                    this.renderSubmissionsList(response.data.submissions);
                } else {
                    console.log('CF7SecurePortal: No submissions found or API error');
                    console.log('CF7SecurePortal: Response success:', response.success);
                    console.log('CF7SecurePortal: Response data:', response.data);
                    
                    if (response.data && response.data.message) {
                        $('#cf7-submissions-list').html('<p>Error: ' + response.data.message + '</p>');
                    } else {
                        $('#cf7-submissions-list').html('<p>No submissions available</p>');
                    }
                }
            })
            .fail((xhr, status, error) => {
                console.log('CF7SecurePortal: Submissions API call failed:', xhr, status, error);
                $('#cf7-submissions-list').html('<p>Error loading submissions</p>');
            });
        },
        
        /**
         * Render submissions list
         */
        renderSubmissionsList: function(submissions) {
            if (!submissions || submissions.length === 0) {
                $('#cf7-submissions-list').html('<p>No submissions available</p>');
                return;
            }
            
            let html = '<div class="cf7-submissions-grid">';
            
            submissions.forEach(submission => {
                html += `
                    <div class="cf7-submission-card" data-id="${submission.id}">
                        <h3>${submission.title}</h3>
                        <p>${submission.artist.name}</p>
                        <p class="cf7-submission-date">${submission.date}</p>
                        <div class="cf7-submission-actions">
                            <a href="${this.config.portalUrl}submission/${submission.id}/" class="cf7-btn cf7-btn-primary">Review</a>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            $('#cf7-submissions-list').html(html);
        },
        
        /**
         * Handle logout
         */
        handleLogout: function() {
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            // Clear session
            this.clearSession();
            
            // Redirect to clean URL
            window.location.href = this.config.portalUrl;
        },
        
        /**
         * Clear session data
         */
        clearSession: function() {
            localStorage.removeItem('cf7_curator_session');
            this.session = null;
        },
        
        /**
         * Redirect to login
         */
        redirectToLogin: function() {
            this.clearSession();
            window.location.href = this.config.portalUrl;
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            $('#auth-check').hide();
            $('#submission-content').html(`
                <div class="error-content">
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `).show();
        },
        
        /**
         * Show message - WORKS WITH PHP TEMPLATE
         */
        showMessage: function(message, type = 'info') {
            // Try PHP template message container first
            let messageEl = $('#cf7-auth-status');
            if (messageEl.length === 0) {
                // Fallback to JavaScript generated container
                messageEl = $('#cf7-login-message');
            }
            
            if (messageEl.length > 0) {
                messageEl.removeClass('cf7-message-error cf7-message-success cf7-message-info')
                         .addClass('cf7-message cf7-message-' + type)
                         .text(message)
                         .show();
            }
        },
        
        /**
         * Make secure API call
         */
        apiCall: function(action, data = {}) {
            const requestData = {
                action: action,
                nonce: this.config.nonce,
                ...data
            };
            
            console.log('CF7SecurePortal: Making API call:', action, 'with data:', requestData);
            
            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: requestData,
                timeout: 30000
            }).done((response) => {
                console.log('CF7SecurePortal: API call', action, 'response:', response);
            }).fail((xhr, status, error) => {
                console.log('CF7SecurePortal: API call', action, 'failed:', xhr, status, error);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize secure portal
        CF7SecurePortal.init();
        
        // Initialize tab functionality for submission pages
        $('.cf7-tab-link').on('click', function(e) {
            e.preventDefault();
            const targetTab = $(this).data('tab');
            
            // Update active link
            $('.cf7-tab-link').removeClass('active');
            $(this).addClass('active');
            
            // Update active pane
            $('.cf7-tab-pane').removeClass('active');
            $('#' + targetTab).addClass('active');
            
            // Load tab content via secure API
            if (CF7SecurePortal.isSubmissionPage()) {
                CF7SecurePortal.loadTabContent(targetTab);
            }
        });
        
    });

})(jQuery);
