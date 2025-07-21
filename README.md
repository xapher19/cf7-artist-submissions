# CF7 Artist Submissions

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-orange)](https://wordpress.org/plugins/contact-form-7/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple)](https://php.net)

> **Professional artist submission management system for WordPress with modern dashboard, advanced field editing, and comprehensive task management.**

A sophisticated WordPress plugin that transforms Contact Form 7 submissions into a powerful artist management platform featuring interactive dashboards, editable fields, task management, and professional tabbed interface.

---

## 🚀 Features

### 🎨 **Modern Interactive Dashboard**
- **Real-time Statistics**: Live submission counts, status distribution, and activity metrics
- **Interactive Widgets**: Unread messages, outstanding actions, and task management
- **Smart Filtering**: Filter submissions by status, date range, and custom criteria
- **Circular Status Badges**: Professional uniform status indicators with hover effects
- **Export Functionality**: Advanced CSV export with filtering and Professional PDF export with two-column artwork layouts

### 📋 **Professional Tabbed Interface**
- **5-Tab Layout**: Profile, Works, Conversations, Actions, and Curator Notes
- **Editable Header**: Click-to-edit artist name, pronouns, and email directly in header
- **Smart Navigation**: Context-aware tab routing from dashboard widgets
- **AJAX Loading**: Seamless tab switching without page reloads
- **Responsive Design**: Mobile-friendly interface with professional styling

### ✏️ **Advanced Field Editing System**
- **Inline Editing**: Click any field to edit directly in place
- **Visual Feedback**: Professional edit mode indicators and hover states
- **Auto-save**: Automatic saving with success/error notifications
- **Independent Systems**: Separate save buttons for profile fields vs curator notes
- **Real-time Updates**: Instant field updates with AJAX validation

### 📝 **Actions & Task Management**
- **To-Do System**: Create, assign, and track tasks for each submission
- **Action Types**: Configurable action categories (follow-up, review, contact, etc.)
- **User Assignment**: Assign actions to specific team members
- **Due Dates**: Set deadlines with automatic notifications
- **Status Tracking**: Complete actions and maintain audit trail
- **Daily Summaries**: Automated email summaries of pending actions

### 💬 **Conversation Management**
- **Threaded Conversations**: Organized communication history
- **Two-Way Email**: Complete email conversation threads with artists
- **Template Integration**: Send templated emails directly from the interface
- **Plus Addressing**: Cost-effective single email solution (no extra accounts needed)
- **Auto-Refresh**: Real-time conversation updates with visual indicators
- **Internal Notes**: Private team communications

### 🎯 **Smart Status Management**
- **New**: Recently submitted (blue circle)
- **Reviewed**: Has been reviewed (green circle)  
- **Awaiting Information**: Needs more info (orange circle)
- **Selected**: Chosen for exhibition (purple circle)
- **Rejected**: Not selected (red circle)

### 📁 **File Management & Export**
- **Secure Storage**: All uploads stored securely with organized directory structure
- **Lightbox Preview**: Built-in image viewer with zoom and navigation
- **Download Options**: Direct download links for all file types
- **Type Recognition**: Automatic file type detection and appropriate display
- **Gallery View**: Professional artwork gallery presentation
- **PDF Export**: Professional PDF generation with two-column artwork layouts and configurable content sections

### 📊 **Audit & Compliance**
- **Comprehensive Audit Log**: Complete activity trail for all submission actions with artist attribution
- **Email Tracking**: Track all emails sent with templates, recipients, and timestamps
- **Status Change History**: Full audit trail of status changes with user attribution
- **File Upload Logging**: Track all file uploads with metadata and user details
- **Action System Tracking**: Task creation, assignment, and completion logging
- **Advanced Filtering**: Filter audit logs by action type, date range, submission, and artist
- **Compliance Ready**: Professional audit trail for administrative oversight and regulatory requirements
- **Privacy Protection**: Sensitive data properly redacted in audit logs

### 🎨 **Professional Interface**
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

## � Installation

### Automatic Installation
1. Navigate to **Plugins > Add New** in WordPress admin
2. Search for "CF7 Artist Submissions"
3. Click **Install Now** and then **Activate**

### Manual Installation
1. Download or clone this repository to your WordPress plugins directory:
   ```
   /wp-content/plugins/cf7-artist-submissions/
   ```

2. Activate the plugin through the WordPress admin panel:
   - Go to **Plugins > Installed Plugins**
   - Find "CF7 Artist Submissions" 
   - Click **Activate**

3. The plugin will automatically create the necessary database tables and post type.

---

## ⚙️ Configuration

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

### Contact Form 7 Setup

Create a Contact Form 7 form with the following field names (the plugin expects these specific field names):

**Required Fields:**
- `artist-name` - Artist's full name
- `email` - Artist's email address

**Optional Fields:**
- `pronouns` - Artist's pronouns
- `phone` - Phone number
- `location` - Artist's location
- `website` - Artist's website URL
- `instagram` - Instagram handle
- `artistic-statement` - Artist's statement
- `medium` - Primary artistic medium
- `availability` - Artist's availability
- `submission-comments` - Any additional comments

**File Upload Fields:**
- `artist-headshot` - Profile photo
- `artwork-1`, `artwork-2`, `artwork-3` - Artwork images
- `cv` - Curriculum Vitae (PDF)

### Form Configuration Example

```html
<label> Artist Name* [text* artist-name] </label>
<label> Email* [email* email] </label>
<label> Pronouns [text pronouns] </label>
<label> Phone [text phone] </label>
<label> Location [text location] </label>
<label> Website [url website] </label>
<label> Instagram [text instagram] </label>
<label> Artistic Statement [textarea artistic-statement] </label>
<label> Primary Medium [text medium] </label>
<label> Availability [text availability] </label>
<label> Artist Headshot [file artist-headshot limit:2mb filetypes:jpg|jpeg|png] </label>
<label> Artwork 1 [file artwork-1 limit:5mb filetypes:jpg|jpeg|png] </label>
<label> Artwork 2 [file artwork-2 limit:5mb filetypes:jpg|jpeg|png] </label>
<label> Artwork 3 [file artwork-3 limit:5mb filetypes:jpg|jpeg|png] </label>
<label> CV [file cv limit:5mb filetypes:pdf] </label>
<label> Additional Comments [textarea submission-comments] </label>
[submit "Submit Application"]
```

### Additional Notes

For developers looking to extend functionality, the plugin provides WordPress hooks and filters for customization. The audit log system tracks all activities automatically, and the conversation system integrates with standard email workflows.

---

## 📖 Usage Guide

### Modern Dashboard Interface

1. Navigate to **Artist Submissions** to access the modern dashboard
2. **Quick Stats**: View real-time submission counts and status distribution
3. **Interactive Widgets**:
   - **Unread Messages**: Click to jump directly to conversations tab
   - **Outstanding Actions**: Click to navigate to actions tab with pending tasks
   - **Recent Activity**: Monitor latest submission activity
4. **Smart Filtering**: Use status filters and search to find specific submissions
5. **Bulk Actions**: Select multiple submissions for batch operations

### Professional Artist Profile Management

#### Editable Header System
1. **View Mode**: Artist name, pronouns, and email displayed in professional header
2. **Edit Mode**: Click the "Edit" button to make header fields editable
3. **Inline Editing**: Click any field to edit directly in place
4. **Auto-save**: Changes save automatically with visual feedback

#### Tabbed Interface Navigation
- **Profile Tab** (Default): Core artist information and submission details
- **Works Tab**: Artwork galleries and portfolio management
- **Conversations Tab**: Communication history and message threads
- **Actions Tab**: Task management and to-do items
- **Curator Notes Tab**: Private internal notes with independent save system

#### Advanced Field Editing
1. **Click to Edit**: Any field can be edited by clicking on it
2. **Visual Indicators**: Fields show hover states and edit mode styling
3. **Save Options**: 
   - Profile fields: Use main "Save All Changes" button
   - Curator notes: Independent "Save Notes" button
4. **Real-time Feedback**: Success/error notifications for all operations

### Task & Action Management

#### Creating Actions
1. Navigate to the **Actions** tab for any submission
2. Click **"Add New Action"**
3. **Configure Action**:
   - Select action type (follow-up, review, contact, etc.)
   - Set due date and priority
   - Assign to team member
   - Add detailed description
4. **Save** to create the action

#### Managing Actions
- **Dashboard Widget**: View all outstanding actions across submissions
- **Action Status**: Mark actions as complete or in-progress
- **Notifications**: Automatic email reminders for due dates
- **Audit Trail**: Track who completed actions and when

### Conversation Management

#### Starting Conversations
1. Navigate to the **Conversations** tab
2. Click **"New Message"** to start a conversation
3. **Email Integration**: Messages sync with your email system
4. **Templates**: Use pre-configured templates for common responses

#### Managing Conversations
- **Thread View**: All messages organized in conversation threads
- **Auto-Refresh**: New messages appear automatically
- **Status Indicators**: See read/unread status at a glance
- **File Attachments**: Attach files directly to messages

### Status Management & Workflow

#### Circular Status Badges
- **Uniform Design**: All status badges are 32px circular icons
- **Color Coding**: Each status has a distinct color for quick identification
- **Hover Effects**: Professional hover states show status names
- **Consistent Width**: Uniform badge sizing for clean list layouts

#### Status Workflow
1. **New Submissions**: Automatically set to "New" status (blue)
2. **Review Process**: Change to "Reviewed" after initial assessment (green)
3. **Information Requests**: Set to "Awaiting Information" for follow-ups (orange)
4. **Shortlisting**: Mark promising submissions as "Shortlisted" for consideration (amber)
5. **Selection Process**: Mark as "Selected" or "Rejected" (purple/red)
6. **Status History**: Track all status changes with timestamps

### Export & Reporting

#### Professional PDF Export System
1. Navigate to any **Artist Submission** page
2. Find the **"Export to PDF"** button in the interface
3. **Configurable Options**:
   - Include/exclude personal information
   - Include/exclude submitted artworks
   - Include/exclude curator notes
   - Add confidential watermarks
4. **Professional Features**:
   - Two-column artwork layout for gallery-style presentation
   - Responsive design optimized for print
   - Site branding and professional styling
   - Browser-based PDF generation (no server dependencies)
5. **Output**: Downloads beautifully formatted HTML document ready for browser Print-to-PDF

#### Comprehensive Audit Log System
1. Navigate to **Artist Submissions > Settings**
2. Click the **"Audit Log"** tab
3. **Audit Capabilities**:
   - Complete trail of all submission activities
   - Email sending history with templates and recipients
   - Status changes with before/after values and user attribution
   - File upload tracking and user attribution
   - Administrative actions and system events
   - Artist information automatically linked to all actions
   - Action system integration (task creation and completion)
   - Settings changes with before/after values
   - Conversation management activities
4. **Advanced Filtering**:
   - Filter by action type (email sent, status change, file upload, action created/completed)
   - Date range filtering with calendar pickers
   - Submission-specific log viewing
   - User-based activity filtering
   - Artist-specific audit trails
5. **Professional Interface**:
   - Paginated results with navigation
   - Color-coded action types for quick identification
   - Detailed action data with JSON expansion
   - Artist column showing name and email for easy identification
   - Export capabilities for compliance reporting
   - Secure access with proper user permissions

#### Advanced CSV Export Features
1. Go to **Artist Submissions > Dashboard**
2. Click **"Export Submissions"**
3. **Filter Options**:
   - Date range selection
   - Status filtering
   - Custom field inclusion
4. **Download**: Receive formatted CSV with all selected data

---

## 🔧 Technical Details

### Plugin Architecture

The CF7 Artist Submissions plugin integrates with Contact Form 7 to provide professional artist submission management with features including:

- **Modern Dashboard**: Real-time statistics and interactive widgets
- **Professional Interface**: Tabbed navigation for efficient workflow
- **Conversation Management**: Integrated messaging system
- **Action Tracking**: Task assignment and completion system  
- **Audit System**: Comprehensive activity logging
- **File Management**: Secure artwork and document handling

---

## �️ Troubleshooting

### Common Issues

**Modern interface not loading:**
1. Verify WordPress version is 5.0 or higher
2. Check browser console for JavaScript errors
3. Ensure all CSS/JS files are loading properly
4. Clear any caching plugins

**Field editing not working:**
1. Check that jQuery is loaded
2. Verify AJAX endpoints are accessible
3. Confirm user has proper edit permissions
4. Test with browser developer tools

**Dashboard widgets not updating:**
1. Verify AJAX functionality is working
2. Check database table permissions
3. Confirm wp_cron is functioning
4. Test with WP_DEBUG enabled

**Tabbed interface issues:**
1. Check for JavaScript conflicts with other plugins
2. Verify responsive CSS is loading
3. Test tab routing with different browsers
4. Confirm AJAX tab loading is functioning

**Action system not working:**
1. Verify action log database table exists
2. Check user permissions for action management
3. Confirm email notification settings
4. Test cron job functionality

**Conversation system issues:**
1. Verify email configuration is correct
2. Check plus addressing setup
3. Test email delivery with simple messages
4. Confirm conversation refresh is working

### Debug Mode

For debugging assistance, enable WordPress debug mode and check the plugin's audit log system in the admin dashboard under **Artist Submissions > Settings > Audit Log**.

### Performance Optimization

For optimal performance with large datasets:
- Use pagination for submission lists over 100 items  
- Enable object caching if available
- Consider CDN for uploaded artwork files
- Monitor memory usage with large file uploads

---

## 📋 Requirements

### System Requirements
- **WordPress**: 5.0 or higher (6.0+ recommended)
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.6 or higher (8.0+ recommended)
- **Memory**: 128MB minimum (256MB+ recommended)

### Plugin Dependencies
- **Contact Form 7**: Latest version required
- **jQuery**: Included with WordPress
- **WordPress REST API**: For AJAX functionality

### Browser Support
- **Modern Browsers**: Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- **Mobile**: iOS Safari 12+, Chrome Mobile 70+
- **JavaScript**: Required for full functionality
- **CSS Grid**: Required for responsive layout

---

## 🆘 Support

For support and questions:
1. **Documentation**: Review this comprehensive guide
2. **Troubleshooting**: Check the detailed troubleshooting section
3. **Debug Logs**: Enable debug mode for detailed error information
4. **WordPress Forums**: Post in WordPress.org support forums
5. **Plugin Logs**: Check the built-in action logs for system activity

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

## 📅 Changelog

### Version 2.0 (Current) - Major Interface Modernization
- **🚀 NEW**: Modern interactive dashboard with real-time statistics and widgets
- **🎨 NEW**: Professional 5-tab interface (Profile, Works, Conversations, Actions, Notes)
- **✏️ NEW**: Advanced field editing system with inline editing capabilities
- **📝 NEW**: Actions & task management system with assignment and due dates
- **💬 NEW**: Enhanced conversation management with threaded messaging
- **🎯 NEW**: Smart tab navigation with widget-specific routing
- **🔵 NEW**: Circular status badges with uniform design and hover effects
- **📋 NEW**: Editable header system for artist information
- **� NEW**: Independent curator notes save system
- **📱 NEW**: Enhanced responsive design for mobile devices
- **⚡ NEW**: Comprehensive AJAX integration for seamless experience
- **📊 IMPROVED**: Export functionality with advanced filtering options
- **📧 IMPROVED**: Email notification system with action summaries
- **🎨 IMPROVED**: WordPress admin integration with proper element hiding
- **💎 IMPROVED**: Professional styling and visual feedback throughout

### Version 1.5 - Conversation System Enhancement
- **💬 NEW**: Two-way email conversation system
- **📧 NEW**: Template integration for common responses
- **🔄 NEW**: Auto-refresh functionality for real-time updates
- **📁 NEW**: File attachment support in conversations
- **🔒 NEW**: Plus addressing for secure email routing

### Version 1.0 - Initial Release
- **📋 NEW**: Basic Contact Form 7 integration
- **📝 NEW**: Custom post type creation for submissions
- **📁 NEW**: File upload handling and storage
- **📧 NEW**: Email notification system
- **📊 NEW**: Basic export functionality
- **🏷️ NEW**: Status management system

---

*Transform your artist submission process with professional tools and modern interface design.*