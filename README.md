# CF7 Artist Submissions

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-orange)](https://wordpress.org/plugins/contact-form-7/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple)](https://php.net)

> **Professional artist submission management system for WordPress with modern dashboard, advanced field editing, and comprehensive task management.**

Transform your Contact Form 7 submissions into a powerful artist management platform featuring interactive dashboards, editable fields, task management, and professional tabbed interface.

---

## 🚀 Key Features

### 🎨 **Modern Interactive Dashboard**
- Real-time statistics and submission counts
- Interactive widgets for unread messages and outstanding tasks
- Smart filtering by status, date range, and custom criteria
- Professional CSV and PDF export functionality

### 📋 **Professional Tabbed Interface**
- 5-tab layout: Profile, Works, Conversations, Actions, and Curator Notes
- Editable header with click-to-edit artist information
- AJAX loading for seamless navigation
- Mobile-responsive design

### ✏️ **Advanced Field Editing**
- Inline editing for all submission fields
- Auto-save with visual feedback
- Independent save systems for different content types
- Real-time validation and error handling

### 📝 **Task Management System**
- Create and assign actions to team members
- Set due dates with automatic notifications
- Priority-based organization
- Daily email summaries of pending tasks

### 💬 **Conversation Management**
- Threaded email conversations with artists
- Template-based responses
- Plus addressing (no extra email accounts needed)
- Auto-refresh for real-time updates

### 📁 **File Management & Export**
- Secure file storage with lightbox preview
- Professional PDF export with custom layouts
- Comprehensive audit logging
- Bulk operations and filtering

---

## 📦 Installation

### Requirements
- **WordPress**: 5.6 or higher
- **PHP**: 7.4 or higher  
- **Contact Form 7**: Latest version (required)
- **PHP IMAP Extension**: Enabled (for conversation system)

### Quick Install
1. Download and upload to `/wp-content/plugins/cf7-artist-submissions/`
2. Activate through **Plugins > Installed Plugins**
3. Navigate to **Artist Submissions** in WordPress admin
4. Follow the setup wizard to configure your first form

---

## ⚙️ Configuration

### 1. Contact Form 7 Setup

Create a form with these field names:

**Required Fields:**
- `artist-name` - Artist's full name
- `email` - Artist's email address

**Optional Fields:**
- `pronouns`, `phone`, `location`, `website`, `instagram`
- `artistic-statement`, `medium`, `availability`
- `submission-comments`

**File Upload Fields:**
- `artist-headshot` - Profile photo
- `artwork-1`, `artwork-2`, `artwork-3` - Artwork images
- `cv` - Curriculum Vitae (PDF)

### 2. Email Configuration

Navigate to **Artist Submissions > Settings** and configure:

**SMTP Settings** (recommended):
```
Host: mail.smtp2go.com
Port: 587 (TLS) or 465 (SSL)
Authentication: Enabled
```

**Plugin Email Settings**:
- From Email: your-organization@domain.com
- From Name: Your Organization Name

### 3. Conversation System

The plugin uses **plus addressing** with your existing email:
- Your email: `contact@yourwebsite.com`
- Outgoing emails: `contact+SUB123_token@yourwebsite.com`
- Replies automatically route to your main inbox

**IMAP Configuration**:
```
Server: Your email provider's IMAP server
Username: contact@yourwebsite.com
Password: Your email password
Port: 993 (SSL) or 143 (STARTTLS)
```

Test plus addressing by sending an email to: `youremail+test@yourdomain.com`

---

## 📖 Usage Guide

### Dashboard Overview
1. Navigate to **Artist Submissions** for the main dashboard
2. View real-time statistics and submission status distribution
3. Use interactive widgets to jump directly to specific tasks:
   - **Unread Messages** → Conversations tab
   - **Outstanding Actions** → Actions tab
   - **Recent Activity** → Latest submissions

### Managing Submissions
1. **Profile Tab**: View and edit all submission details inline
2. **Works Tab**: Browse artwork gallery with lightbox preview
3. **Conversations Tab**: Send messages and track email threads
4. **Actions Tab**: Create tasks, set deadlines, assign team members
5. **Curator Notes Tab**: Add private internal notes

### Status Workflow
- **New** (blue) → **Reviewed** (green) → **Awaiting Information** (orange)
- **Selected** (purple) or **Rejected** (red)

### Export Options
- **CSV Export**: Bulk data export with filtering
- **PDF Export**: Professional submission packets with artwork layouts
- **Audit Logs**: Complete activity tracking for compliance

---

## 🛠️ Troubleshooting

### Common Issues

**Interface not loading:**
- Verify WordPress 5.6+ and PHP 7.4+
- Check browser console for JavaScript errors
- Clear caching plugins

**Email not working:**
- Verify SMTP configuration
- Test plus addressing with a simple email
- Check IMAP settings and credentials

**Actions not saving:**
- Confirm user has proper edit permissions
- Check database connectivity
- Enable WordPress debug mode for detailed errors

---

## 📋 System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| WordPress | 5.6 | 6.0+ |
| PHP | 7.4 | 8.0+ |
| MySQL | 5.6 | 8.0+ |
| Memory | 128MB | 256MB+ |

**Browser Support**: Chrome 70+, Firefox 65+, Safari 12+, Edge 79+

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

## 📅 Changelog

### 1.0.1 - Enhancement Release
- **New:** Custom add submission interface replacing artist view-based implementation
- **Improved:** Hidden mediums taxonomy from WordPress admin navigation for cleaner interface
- **Enhanced:** JavaScript SDF (Standard Document Format) compliance across all components
- **Fixed:** Add submission functionality now works independently without existing submission data
- **Optimized:** AJAX-powered submission creation with file upload management
- **Updated:** Form validation and user feedback systems for improved UX

### 1.0.0 - Initial Release
- Contact Form 7 integration with custom post type creation
- Modern interactive dashboard with real-time statistics
- Professional 5-tab interface (Profile, Works, Conversations, Actions, Notes)
- Advanced inline field editing system with auto-save
- Task management system with assignments and due dates
- Two-way email conversation system with plus addressing
- Secure file management with lightbox preview
- Professional PDF export with configurable layouts
- Comprehensive audit logging for compliance
- Status management workflow with circular badges
- CSV export functionality with advanced filtering
- Responsive design optimized for all devices

---

*Transform your artist submission process with professional tools and modern interface design.*