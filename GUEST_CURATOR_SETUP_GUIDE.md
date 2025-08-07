# Guest Curator System Setup Guide

## Overview

The CF7 Artist Submissions Guest Curator System allows you to invite external reviewers to rate and comment on submissions without requiring full WordPress user accounts. This system provides secure, token-based authentication and comprehensive permission management.

## Features

### 🎨 Guest Curator Management
- Add guest curators with name, email, and permissions
- Assign curators to specific open calls
- Generate secure login links via email
- Track curator activity and permissions

### ⭐ Enhanced Rating System
- Multi-curator rating support
- Individual ratings with automatic averages
- Backward compatibility with existing ratings
- Real-time rating updates

### 💬 Enhanced Notes System
- Comment-style notes interface
- Multi-curator notes support
- Threaded conversations
- Date/time tracking

### 🔐 Secure Portal Access
- Token-based authentication (2-hour expiration)
- Email-delivered login links
- Session management
- Access logging

## Initial Setup

### 1. Plugin Activation
The system is automatically initialized when the plugin is activated. Database tables are created automatically:
- `wp_cf7as_guest_curators` - Curator information
- `wp_cf7as_curator_permissions` - Access permissions
- `wp_cf7as_curator_notes` - Enhanced notes system
- `wp_cf7as_work_ratings` - Enhanced ratings system

### 2. Admin Interface Access
Navigate to **Submissions > Guest Curators** in your WordPress admin to:
- Add new guest curators
- Manage permissions
- Send login links
- Monitor activity

### 3. Create Your First Guest Curator

1. Go to **Submissions > Guest Curators**
2. Click **Add New Curator**
3. Fill in the curator's details:
   - Name
   - Email address
   - Select permissions (view, rate, comment)
4. Assign to specific open calls
5. Click **Save Curator**

### 4. Send Login Access

1. From the Guest Curators list
2. Click **Send Login Link** for the curator
3. Curator receives secure email with portal access
4. Link expires in 2 hours for security

### 5. Verify Portal Access

**Quick Test:**
1. Visit `yoursite.com/curator-portal/` in your browser
2. You should see the curator login form
3. If you see a 404 error, follow the troubleshooting steps below

**Admin Debug Tools:**
- Look for "Curator Portal Debug" in your WordPress admin bar
- Use "Test Portal" to quickly check if the portal is working
- Use "Flush Rewrite Rules" if you encounter URL issues

## Curator Portal Features

### Submissions View
- Grid layout of assigned submissions
- Thumbnail previews
- Progress tracking (rated/unrated)
- Search and filter capabilities

### Rating Interface
- Interactive 5-star rating system
- Save ratings in real-time
- View average ratings from all curators
- Individual curator rating tracking

### Notes System
- Add comments and notes
- View conversation history
- Real-time note saving
- Curator identification

### File Management
- Preview images and documents
- Download original files
- High-resolution image viewing
- File type support (images, PDFs, etc.)

## Permission System

### View Permission
- See assigned submissions
- Access submission details
- Download files

### Rate Permission
- Submit 1-5 star ratings
- Update existing ratings
- View rating averages

### Comment Permission
- Add notes and comments
- Participate in discussions
- View comment history

## Email Templates

### Login Email Template
Located at: `templates/curator-login-email.php`

Customizable email template includes:
- Professional HTML design
- Security information
- Clear call-to-action button
- Branding consistency

## Portal URL Structure

Guest curators access the system via:
- `yoursite.com/curator-portal/` - Login form
- `yoursite.com/curator-portal/[token]` - Direct login with token

## Security Features

### Token Authentication
- Unique tokens per login request
- 2-hour expiration for security
- One-time use tokens
- Session-based access after initial login

### Permission Validation
- Granular permission checking
- Access restricted to assigned open calls
- Session validation on all actions
- Audit logging for security

### Data Protection
- Secure token generation
- Encrypted session storage
- Protected file access
- XSS and CSRF protection

## Integration Notes

### Existing Data Migration
- Automatic migration of legacy ratings
- Preservation of existing curator notes
- Backward compatibility maintained
- No data loss during upgrade

### Open Call Integration
- Seamless integration with existing open calls
- Automatic submission filtering
- Permission-based access control
- Real-time updates

## Troubleshooting

### Portal Access Issues

**❌ Login portal page doesn't exist / shows 404 error:**

1. **Flush Rewrite Rules** (Most common fix):
   - Go to WordPress Admin → Settings → Permalinks
   - Click "Save Changes" (don't change anything)
   - Test the portal URL: `yoursite.com/curator-portal/`

2. **Use Admin Debug Tools**:
   - Look for "Curator Portal Debug" in the WordPress admin bar
   - Click "Flush Rewrite Rules" to refresh URL routing
   - Click "Test Portal" to test the portal page

3. **Manual Rewrite Rule Check**:
   - Add `?debug=1` to the portal URL to see debug information
   - Check if query variables are being detected

4. **Plugin Reactivation**:
   - Deactivate CF7 Artist Submissions plugin
   - Reactivate the plugin
   - This will recreate all database tables and rewrite rules

**❌ Email button doesn't work / leads to 404:**

1. **Check Email Link Format**:
   - Email should contain: `yoursite.com/curator-portal/[secure-token]`
   - Verify the token is being generated correctly

2. **Token Expiration**:
   - Tokens expire after 2 hours for security
   - Generate a new login link if needed

3. **Email Client Issues**:
   - Some email clients break long URLs or don't make buttons clickable
   - Use the plain text link provided below the button
   - Copy and paste the URL directly into browser

4. **Email Template Testing**:
   - Use "Curator Portal Debug" → "Preview Email Template" in admin bar
   - This shows how the email will look in different clients

**❌ Email links not clickable:**

1. **Use Fallback Link**:
   - Every email contains a plain text version below the button
   - Copy and paste this link directly into your browser

2. **Email Client Compatibility**:
   - Some corporate email systems strip styling from buttons
   - Gmail, Outlook, and Apple Mail should show clickable buttons
   - If buttons don't work, use the text link provided

3. **Mobile Email Apps**:
   - Try opening the email in a different email app
   - Desktop email clients often have better link support

**❌ Portal gets stuck on "Verifying your access...":**

1. **Check Browser Console for Errors**:
   - Open portal page and press F12
   - Look for JavaScript errors in Console tab
   - Check Network tab for failed AJAX requests to admin-ajax.php

2. **Token Issues**:
   - Tokens expire after 2 hours - generate a new login link
   - Verify URL format: `yoursite.com/curator-portal/[token]/`
   - Not: `yoursite.com/curator-portal/?token=[token]`

3. **AJAX Authentication Problems**:
   - WordPress security nonce may be blocking requests
   - Database connection issues with curator table
   - Plugin conflicts interfering with AJAX calls

4. **Debug the Authentication**:
   - Enable WordPress debug mode in wp-config.php:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     ```
   - Check /wp-content/debug.log for authentication errors

5. **Test AJAX Directly**:
   - Open browser console on portal page
   - Run: `console.log(CF7PortalData)` to verify JS data is loaded
   - Test manual AJAX call to verify endpoint is working

### Step-by-Step Fix for Portal 404 Issues

If your curator portal shows a 404 error, follow these steps in order:

**Step 1: Flush Permalinks (90% success rate)**
```
1. Go to WordPress Admin
2. Navigate to Settings → Permalinks  
3. Click "Save Changes" (don't change any settings)
4. Test portal URL: yoursite.com/curator-portal/
```

**Step 2: Use Admin Debug Tools**
```
1. Look for "Curator Portal Debug" in your admin bar
2. Click "Flush Rewrite Rules"
3. Click "Test Portal" to verify it works
4. Click "Preview Email Template" to test email formatting
```

**Step 3: Plugin Reactivation**
```
1. Go to Plugins → Installed Plugins
2. Deactivate "CF7 Artist Submissions"
3. Reactivate "CF7 Artist Submissions"  
4. Test portal URL again
```

**Step 4: Manual URL Test**
```
Try these URLs in order:
1. yoursite.com/curator-portal/
2. yoursite.com/curator-portal/?debug=1
3. yoursite.com/?cf7_curator_portal=1
```

**Step 5: Contact Support**
```
If none of the above work, provide:
1. WordPress version
2. Active theme name
3. List of active plugins
4. Any error messages from debug mode
```

### Common Issues

**Login links not working:**
- Check rewrite rules (may need to re-activate plugin)
- Verify email delivery
- Check token expiration

**Portal stuck on "Verifying your access...":**
- Check browser console for JavaScript errors
- Verify token hasn't expired (2 hour limit)
- Test with fresh login link generation
- Enable WordPress debug mode to check for server errors

**Permissions not working:**
- Verify curator has correct permissions
- Check open call assignments
- Confirm session is valid

**Email not sending:**
- Check WordPress mail configuration
- Verify curator email address
- Check spam folders

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Best Practices

### Curator Management
1. Use clear, descriptive curator names
2. Regularly review and update permissions
3. Remove inactive curators
4. Monitor login activity

### Security
1. Use strong email validation
2. Regular security audits
3. Monitor login attempts
4. Keep tokens short-lived

### Performance
1. Limit concurrent active curators
2. Regular database cleanup
3. Monitor session storage
4. Optimize image delivery

## Advanced Configuration

### Custom Email Templates
Modify `templates/curator-login-email.php` to customize:
- Branding and styling
- Email content and messaging
- Security information display
- Call-to-action buttons

### Portal Styling
Customize `assets/css/curator-portal.css` for:
- Brand colors and fonts
- Layout adjustments
- Mobile responsiveness
- Dark mode support

### Permission Extensions
The system supports extending permissions through:
- Custom permission types
- Role-based access
- Time-based restrictions
- Content-specific permissions

## Support and Updates

The Guest Curator System is actively maintained and regularly updated. For support:

1. Check WordPress admin error logs
2. Review plugin documentation
3. Contact plugin support team
4. Submit feature requests

---

*Last updated: December 2024*
*Version: 1.3.0*
