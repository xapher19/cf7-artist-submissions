# CF7 Artist Submissions

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-orange)](https://wordpress.org/plugins/contact-form-7/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple)](https://php.net)

> **Professional artist submission management system for WordPress with two-way email conversations and modern tabbed interface.**

Store and manage Contact Form 7 submissions as comprehensive artist profiles with built-in conversation system, file management, and administrative tools.

---

## 🚀 Features

### 📋 **Core Functionality**
- **Form Integration**: Connect to any Contact Form 7 form
- **Data Storage**: Store all form field values and uploaded files securely
- **Status Management**: Tag submissions with custom statuses (New, Selected, Reviewed)
- **Admin Notes**: Add private curator notes visible only to administrators
- **File Management**: View file uploads with lightbox preview and download options

### 💬 **Advanced Conversation System**
- **Two-Way Email**: Complete email conversation threads with artists
- **Template Integration**: Send templated emails directly from the interface
- **Plus Addressing**: Cost-effective single email solution (no extra accounts needed)
- **Auto-Refresh**: Real-time conversation updates with visual indicators
- **Message Management**: Rich conversation history with visual differentiation

### 🎨 **Modern Tabbed Interface**
- **Organized Layout**: Clean tabbed interface reduces clutter
- **Profile Tab**: Submission details with inline editing capabilities
- **Works Tab**: File gallery with thumbnail previews and lightbox
- **Conversations Tab**: Complete email interface with auto-scroll
- **Notes Tab**: Dedicated curator notes section
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile

### ⚡ **Performance & UX**
- **AJAX Loading**: Dynamic content loading for better performance
- **State Management**: Remembers active tab and user preferences
- **URL Hash Support**: Direct tab linking and bookmarking
- **Auto-Scroll**: Conversations automatically scroll to latest messages
- **Keyboard Shortcuts**: Enhanced productivity features

---

## 📦 Installation

### Quick Setup

1. **Install Plugin**
   ```bash
   # Upload to /wp-content/plugins/cf7-artist-submissions/
   # Or install via WordPress admin
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "CF7 Artist Submissions"

3. **Configure Settings**
   - Navigate to **Submissions → Settings**
   - Configure Contact Form 7 integration
   - Set up email and conversation settings

### Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| WordPress | 5.6+ | Tested up to 6.8.2 |
| PHP | 7.4+ | Required for modern features |
| Contact Form 7 | Latest | Must be installed and active |
| PHP IMAP Extension | Enabled | For conversation system |

---

## ⚙️ Configuration

### 🔧 **Basic Form Setup**

1. **Select CF7 Form**: Choose which Contact Form 7 form to track
2. **Test Submission**: Submit a test form entry
3. **Verify Storage**: Check Submissions menu for stored data

### 📧 **Email Configuration**

#### SMTP Setup (Recommended: SMTP2GO)
```
SMTP Host: mail.smtp2go.com
SMTP Port: 587 (TLS) or 465 (SSL)
Authentication: Enabled
```

#### Plugin Email Settings
- **From Email**: Your organization's sending address
- **From Name**: Your organization name
- **Reply Handling**: Automatic conversation threading

### 💬 **Conversation System Setup**

#### Plus Addressing (Single Email Solution)
The conversation system uses **plus addressing** with your existing email - no extra costs!

**How it works:**
- **Your email**: `contact@yourwebsite.com`
- **Outgoing emails**: `contact+SUB123_token@yourwebsite.com`
- **Replies go to**: Your main inbox automatically
- **No forwarding needed**: Works with most email providers

**Supported Providers:**
- ✅ Gmail/Google Workspace
- ✅ cPanel hosting providers
- ✅ Office 365/Outlook
- ✅ Yahoo Mail
- ✅ Most modern email providers

#### IMAP Configuration
```
Server: Your email provider's IMAP server
Username: contact@yourwebsite.com (your main email)
Password: Your regular email password
Port: 993 (SSL) or 143 (STARTTLS)
```

#### Test Plus Addressing
Send an email to: `youremail+test@yourdomain.com`
If it arrives in your main inbox, plus addressing works!

---

## 🎯 Usage Guide

### 📋 **Managing Submissions**

#### Tabbed Interface Navigation
- **Profile Tab**: View and edit submission details inline
- **Submitted Works Tab**: Browse file gallery with lightbox previews
- **Conversations Tab**: Send emails and view conversation history
- **Curator Notes Tab**: Add private administrative notes

#### Status Management
- Set submission status (New, Selected, Reviewed, etc.)
- Add private curator notes
- Track submission progress

### 💬 **Conversation Features**

#### Sending Messages
1. Navigate to **Conversations Tab**
2. Choose message type (Manual or Template)
3. Compose message with rich editing
4. Send with automatic conversation threading

#### Template System
- **Pre-defined Templates**: Quick responses for common scenarios
- **Merge Tags**: Automatic personalization with submission data
- **Visual Differentiation**: Template messages appear in green
- **Preview System**: Preview before sending

#### Managing Conversations
- **Auto-Refresh**: Conversations update automatically
- **Visual Indicators**: See new messages and activity
- **Scroll Management**: Auto-scroll to latest messages
- **Message History**: Complete conversation threads

### 📁 **File Management**

#### File Display
- **Thumbnail Previews**: Images show with lightbox functionality
- **Download Options**: Direct download links for all files
- **File Information**: Size, type, and availability status
- **Gallery View**: Organized file gallery interface

---

## 🛠️ Technical Details

### File Structure
```
cf7-artist-submissions/
├── cf7-artist-submissions.php          # Main plugin file
├── assets/
│   ├── css/
│   │   ├── admin.css                   # Admin interface styling
│   │   ├── lightbox.css               # Lightbox functionality
│   │   ├── conversations.css          # Conversation interface
│   │   └── tabs.css                    # Tabbed interface styling
│   └── js/
│       ├── admin.js                    # Admin functionality
│       ├── conversation.js            # Conversation management
│       ├── fields.js                  # Inline field editing
│       ├── lightbox.js                # File preview system
│       ├── settings.js                # Settings interface
│       └── tabs.js                     # Tab functionality
├── includes/
│   ├── class-cf7-artist-submissions-tabs.php          # Tabbed interface
│   ├── class-cf7-artist-submissions-conversations.php # Conversation system
│   ├── class-cf7-artist-submissions-emails.php       # Email management
│   ├── class-cf7-artist-submissions-admin.php        # Admin interface
│   ├── class-cf7-artist-submissions-settings.php     # Settings management
│   ├── class-cf7-artist-submissions-action-log.php   # Activity logging
│   ├── class-cf7-artist-submissions-form-handler.php # Form processing
│   └── class-cf7-artist-submissions-post-type.php    # Data storage
└── templates/
    ├── admin-settings.php             # Settings interface
    ├── submission-list.php            # Submissions overview
    └── submission-view.php            # Single submission view
```

### Database Tables
- **`wp_cf7_action_logs`**: Activity and change tracking
- **`wp_cf7_conversations`**: Email conversation threads with template support

### Dependencies
- **jQuery**: WordPress core (for interface interactions)
- **WordPress Meta Box API**: Admin interface framework
- **AJAX Handlers**: Dynamic content loading
- **PHP IMAP Extension**: Email conversation processing

---

## 🔧 Customization

### CSS Styling
Customize the interface appearance:
```css
/* Tab styling */
.cf7-tabs-nav { /* Tab navigation */ }
.cf7-tab-content { /* Tab content areas */ }

/* Conversation styling */
.conversation-message { /* Message bubbles */ }
.template-message { /* Template message styling */ }
```

### JavaScript Enhancement
Extend functionality:
```javascript
// Tab change events
$(document).on('cf7_tab_changed', function(e, tabId) {
    // Custom tab change handling
});

// Conversation events
$(document).on('cf7_message_sent', function(e, data) {
    // Custom message handling
});
```

### PHP Hooks
```php
// Field update tracking
do_action('cf7_artist_submission_field_updated', $post_id, $field, $old_value, $new_value);

// Status change tracking
do_action('cf7_artist_submission_status_changed', $post_id, $new_status, $old_status);
```

---

## 🐛 Troubleshooting

### Common Issues

#### Plugin Crashes
- ✅ Check WordPress error logs
- ✅ Verify Contact Form 7 is active
- ✅ Ensure PHP 7.4+ is installed
- ✅ Check file permissions

#### Email Issues
- ✅ Test SMTP settings in WP Mail SMTP
- ✅ Verify "From" email in plugin settings
- ✅ Check spam folders
- ✅ Verify DNS records for email domain

#### IMAP/Conversation Issues
- ✅ Enable PHP IMAP extension
- ✅ Test IMAP connection in settings
- ✅ Verify plus addressing support
- ✅ Check IMAP credentials
- ✅ Ensure IMAP access is enabled

#### Tab Interface Issues
- ✅ Clear browser cache
- ✅ Check JavaScript console for errors
- ✅ Verify jQuery is loaded
- ✅ Test in different browsers

#### File Upload Issues
- ✅ Check file permissions in wp-content/uploads/
- ✅ Verify file size limits
- ✅ Check file type restrictions
- ✅ Test file path accessibility

---

## 🚀 Browser Support

| Browser | Version | Status |
|---------|---------|---------|
| Chrome | Latest | ✅ Fully Supported |
| Firefox | Latest | ✅ Fully Supported |
| Safari | Latest | ✅ Fully Supported |
| Edge | Latest | ✅ Fully Supported |
| Mobile | iOS/Android | ✅ Responsive Design |

---

## 📄 License

This plugin is licensed under the [GPL v2 or later](http://www.gnu.org/licenses/gpl-2.0.html).

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

## 📞 Support

- **Documentation**: See inline help in WordPress admin
- **Issues**: Report bugs via GitHub issues
- **Email**: Contact plugin developer for support

---

**Ready to transform your artist submission management! 🎨**