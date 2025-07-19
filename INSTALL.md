# CF7 Artist Submissions - Installation & Testing Guide

## 🚀 **Quick Installation**

1. **Upload Plugin Directory**: Upload the entire `cf7-artist-submissions` folder to your WordPress `/wp-content/plugins/` directory.

2. **Activate Plugin**: Go to WordPress Admin → Plugins → Activate "CF7 Artist Submissions"

3. **Configure Settings**: Go to Submissions → Settings to configure your Contact Form 7 integration

## ✅ **Testing Steps**

### Phase 1: Basic Functionality
1. **Set up CF7 Form**: Configure which Contact Form 7 form to track
2. **Test Submission**: Submit a test form entry
3. **Verify Storage**: Check Submissions menu to see stored submission
4. **Test Email**: Verify emails are sent with correct "From" address

### Phase 2: Conversation System (Advanced)
1. **Configure IMAP**: Go to Settings → IMAP Settings tab
2. **Test Connection**: Use the "Test Connection" button
3. **Test Conversations**: Send a message to an artist from submission view
4. **Test Replies**: Configure catch-all email forwarding for replies

## ⚙️ **Configuration Requirements**

### Email System (SMTP2GO Recommended)
- **SMTP Server**: Configured via WP Mail SMTP plugin
- **From Address**: Set in plugin settings
- **Working**: ✅ Confirmed working with SMTP2GO

### IMAP System (For Conversations)
- **Single Email Address**: Uses your existing email with plus addressing
- **No Extra Costs**: Works with your current single email account
- **Plus Addressing**: Emails use `your-email+SUB123_token@yourdomain.com` format
- **IMAP Access**: Configure your email provider's IMAP settings
- **PHP IMAP Extension**: Must be enabled on server

## 🛠️ **Recent Fixes Applied**

- ✅ **Syntax Errors**: Fixed missing braces in conversation class
- ✅ **Error Handling**: Added proper error handling to prevent crashes
- ✅ **Dependency Checks**: Contact Form 7 requirement validation
- ✅ **Database Structure**: Robust table creation with proper schema

## 📧 **Email Configuration**

### Single Email Address Setup (No Extra Costs!)
The conversation system now uses **plus addressing** with your existing single email address:

**How it works:**
- **Outgoing emails** use: `your-email+SUB123_token@yourdomain.com`
- **All replies** go to your main inbox: `your-email@yourdomain.com`
- **No forwarding needed** - most email providers support plus addressing natively
- **No extra email accounts** required

**Supported by:**
- ✅ Gmail/Google Workspace (`yourname+tag@gmail.com`)
- ✅ Most cPanel hosting providers
- ✅ Office 365/Outlook
- ✅ Yahoo Mail
- ✅ Many others

### SMTP Settings (via WP Mail SMTP)
- **Mailer**: SMTP2GO (recommended)
- **SMTP Host**: mail.smtp2go.com
- **SMTP Port**: 587 (TLS) or 465 (SSL)
- **Authentication**: Enable
- **Username/Password**: Your SMTP2GO credentials

### Plugin Email Settings
- **From Email**: Set your desired sending address
- **From Name**: Set your organization name

## 🗃️ **Database Tables Created**

1. **`wp_cf7_action_logs`**: Tracks all plugin actions
2. **`wp_cf7_conversations`**: Stores email conversation threads

## 🔍 **Troubleshooting**

### If Plugin Crashes
1. Check WordPress error logs
2. Verify all required classes are present
3. Ensure Contact Form 7 is active
4. Check PHP version (requires 7.4+)

### Email Issues
1. Test SMTP settings in WP Mail SMTP
2. Verify "From" email in plugin settings
3. Check spam folders
4. Verify DNS records for email domain

### IMAP Issues
1. Enable PHP IMAP extension
2. Test IMAP connection in settings
3. Verify your email provider supports plus addressing
4. Check IMAP credentials (use your main email address)
5. Ensure IMAP access is enabled in your email account

## 📁 **File Structure**

```
cf7-artist-submissions/
├── cf7-artist-submissions.php          # Main plugin file
├── assets/
│   ├── css/
│   │   ├── admin.css                   # Admin styling
│   │   ├── lightbox.css               # Lightbox styling
│   │   └── conversations.css          # Conversation UI styling
│   └── js/
│       ├── admin.js                    # Admin functionality
│       ├── conversation.js            # Conversation interface with auto-refresh, keyboard shortcuts, and message management
│       └── settings.js                 # Settings page
├── includes/
│   ├── class-cf7-artist-submissions-post-type.php
│   ├── class-cf7-artist-submissions-form-handler.php
│   ├── class-cf7-artist-submissions-admin.php
│   ├── class-cf7-artist-submissions-settings.php
│   ├── class-cf7-artist-submissions-action-log.php
│   ├── class-cf7-artist-submissions-emails.php
│   └── class-cf7-artist-submissions-conversations.php
└── templates/
    ├── admin-settings.php
    ├── submission-list.php
    └── submission-view.php
```

## 🎯 **Next Steps After Installation**

1. **Basic Setup**: Configure CF7 form tracking
2. **Test Submissions**: Submit test entries
3. **Email Configuration**: Set up SMTP and test sending
4. **IMAP Setup**: Configure for two-way conversations
5. **Production Use**: Deploy with proper email infrastructure

Ready to test! 🚀
