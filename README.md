# CF7 Artist Submissions

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-orange)](https://wordpress.org/plugins/contact-form-7/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple)](https://php.net)

> **Professional artist submission management system for WordPress with modern dashboard, advanced field editing, comprehensive task management, and Amazon S3 cloud storage.**

Transform your Contact Form 7 submissions into a powerful artist management platform featuring interactive dashboards, editable fields, task management, professional tabbed interface, and secure cloud file storage.

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

### 📁 **Modern File Management & Cloud Storage**
- **Amazon S3 Integration**: Secure cloud storage with presigned URLs
- **Modern Upload Interface**: Uppy drag-and-drop with progress tracking
- **Large File Support**: Upload files up to 5GB per file
- **File Previews**: Lightbox galleries for images, embedded video players
- **ZIP Downloads**: Bulk download original files per submission
- **Professional PDF Export**: Custom layouts with artwork integration
- **Audit Logging**: Complete file access tracking

### 🎯 **Form Takeover Mode**
- Guided 3-step submission experience
- Full-screen modal with progress indicators
- Professional styling with success confirmations
- Seamless integration with existing CF7 forms

### 🔧 **Artistic Mediums Management**
- Custom checkbox selector for artistic mediums
- Color-coded medium tags with visual styling
- Automatic population from WordPress taxonomy
- Required field validation support

---

## 📦 Installation

### Requirements
- **WordPress**: 5.6 or higher
- **PHP**: 7.4 or higher  
- **Contact Form 7**: Latest version (required)
- **PHP IMAP Extension**: Enabled (for conversation system)
- **Amazon S3 Account**: For cloud file storage (optional but recommended)

### Quick Install
1. Download and upload to `/wp-content/plugins/cf7-artist-submissions/`
2. Activate through **Plugins > Installed Plugins**
3. Navigate to **Artist Submissions** in WordPress admin
4. Follow the setup wizard to configure your first form
5. Configure Amazon S3 for cloud file storage (see S3 Setup section)

---

## ⚙️ Configuration

### 1. Contact Form 7 Setup

Create a form with these field names:

**Required Fields:**
- `artist-name` - Artist's full name
- `email` - Artist's email address

**Optional Fields:**
- `pronouns`, `phone`, `location`, `website`, `instagram`
- `artist-statement`, `medium`, `availability`
- `submission-comments`

**File Upload Fields:**

**Modern S3 Upload (Recommended):**
```
[cf7as_uploader your-work max_files:20 max_size:5120]
```

**With Form Takeover Mode:**
```
[cf7as_uploader your-work takeover max_files:20 max_size:5120]
```

**Artistic Mediums Field:**
```
[mediums* artistic-mediums label:"Select all mediums that apply to your work:"]
```

**Complete Example Form:**
```html
<label>Your Name (required)
    [text* artist-name]</label>

<label>Your Email (required)
    [email* email]</label>

<label>Your Pronouns
    [text pronouns]</label>

<label>Portfolio Website
    [url website]</label>

<label>Artistic Mediums (required)
    [mediums* artistic-mediums label:"Select all mediums that apply to your work:"]</label>

<label>Artist Statement
    [textarea artist-statement]</label>

<label>Upload Your Works (required)
    [cf7as_uploader your-work takeover max_files:20 max_size:5120]</label>

[submit "Submit Application"]
```

### 2. Amazon S3 Setup

#### Step 1: Create AWS Account
1. Go to **AWS Console**: https://aws.amazon.com/console/
2. Click **"Create an AWS Account"**
3. Complete account setup with email, password, and verification
4. Choose **"Basic support - Free"** for getting started

#### Step 2: Create S3 Bucket
1. **Navigate to S3** in AWS Console
2. **Create bucket** with unique name (e.g., `my-artist-submissions-2025`)
3. **Choose region** closest to your users for best performance
4. **Keep default security settings** (Block Public Access enabled)

#### Step 3: Create IAM User
1. **Go to IAM service** in AWS Console
2. **Create new user** for the plugin
3. **Attach policy** with these permissions:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Effect": "Allow",
         "Action": [
           "s3:GetObject",
           "s3:PutObject",
           "s3:DeleteObject",
           "s3:ListBucket"
         ],
         "Resource": [
           "arn:aws:s3:::your-bucket-name/*",
           "arn:aws:s3:::your-bucket-name"
         ]
       }
     ]
   }
   ```
4. **Generate Access Keys** and save securely

#### Step 4: Configure Plugin
Navigate to **Artist Submissions > Settings** and enter:
- **AWS Access Key ID**: From IAM user
- **AWS Secret Access Key**: From IAM user  
- **S3 Bucket Name**: Your bucket name
- **AWS Region**: Your bucket's region (e.g., `us-east-1`)

### 3. Email Configuration

#### SMTP Settings (Recommended)
Configure SMTP in **Artist Submissions > Settings**:
```
Host: Your email provider's SMTP server
Port: 587 (TLS) or 465 (SSL)
Username: Your email address
Password: Your email password
```

#### Conversation System Setup
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

**Test plus addressing** by sending an email to: `youremail+test@yourdomain.com`

### 4. Artistic Mediums Setup

1. **Navigate to** Artist Submissions > Artistic Mediums
2. **Add medium terms** (e.g., "Oil Painting", "Digital Art", "Sculpture")
3. **Set colors** for visual styling (optional)
4. **Use in forms** with the `[mediums]` shortcode

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

### Form Takeover Experience
When enabled, the form takeover provides a guided 3-step process:

1. **Step 1: Your Details**
   - Form fields extracted from your CF7 form
   - Real-time validation
   - Clean, professional styling

2. **Step 2: Upload Works**
   - Drag-and-drop file interface
   - Progress tracking for each file
   - Support for large files up to 5GB

3. **Step 3: Review & Submit**
   - Summary of all entered information
   - File list with details
   - Final submission confirmation

### Artistic Mediums Features
- **Visual Selection**: Color-coded checkboxes for easy identification
- **Responsive Layout**: Horizontal grid that adapts to screen size
- **Text Wrapping**: Long medium names wrap gracefully
- **Validation**: Required field support with error messaging

### Status Workflow
- **New** (blue) → **Reviewed** (green) → **Awaiting Information** (orange)
- **Selected** (purple) or **Rejected** (red)

### Export Options
- **CSV Export**: Bulk data export with filtering
- **PDF Export**: Professional submission packets with artwork layouts
- **ZIP Downloads**: Bulk file downloads per submission
- **Audit Logs**: Complete activity tracking for compliance

---

## 🛠️ Troubleshooting

### Common Issues

**Plugin not loading properly:**
- Verify WordPress 5.6+ and PHP 7.4+
- Check browser console for JavaScript errors
- Clear caching plugins
- Ensure Contact Form 7 is active

**File uploads failing:**
- Verify S3 configuration and credentials
- Check AWS IAM permissions
- Ensure bucket exists and is accessible
- Test with smaller files first

**Mediums not displaying:**
- Add artistic medium terms in WordPress admin
- Verify mediums shortcode syntax
- Check form ID in plugin settings
- Clear any caching

**Email not working:**
- Verify SMTP configuration
- Test plus addressing with a simple email
- Check IMAP settings and credentials
- Ensure PHP IMAP extension is enabled

**Actions not saving:**
- Confirm user has proper edit permissions
- Check database connectivity
- Enable WordPress debug mode for detailed errors

### AWS-Specific Issues

**S3 Access Denied:**
- Verify IAM user permissions
- Check bucket policy configuration
- Ensure access keys are correct
- Confirm bucket region matches plugin settings

**Large File Upload Issues:**
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)
- Verify AWS S3 multipart upload permissions
- Ensure stable internet connection for large files

---

## 📋 System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| WordPress | 5.6 | 6.0+ |
| PHP | 7.4 | 8.0+ |
| MySQL | 5.6 | 8.0+ |
| Memory | 128MB | 256MB+ |

**Browser Support**: Chrome 70+, Firefox 65+, Safari 12+, Edge 79+

**AWS Requirements**:
- Active AWS account
- S3 bucket with appropriate permissions
- IAM user with programmatic access

---

## 💰 Pricing Information

### WordPress Plugin
- **Free** - Core plugin functionality

### AWS Costs (Pay-as-you-use)
- **S3 Storage**: ~$0.023/GB/month
- **Data Transfer**: ~$0.09/GB (first 1GB free monthly)
- **Requests**: ~$0.0004/1000 requests

**Example Monthly Costs**:
- **Small Organization** (10GB storage, 1000 files): ~$0.50/month
- **Medium Organization** (100GB storage, 10K files): ~$2.50/month
- **Large Organization** (1TB storage, 100K files): ~$25/month

*Costs may vary by region and usage patterns*

---

## 🔒 Security Features

- **Presigned URLs**: Secure, time-limited file access
- **Private S3 Buckets**: No public access to uploaded files
- **Plus Addressing**: Secure email routing without exposing addresses
- **Audit Logging**: Complete activity tracking
- **Role-based Access**: WordPress user role integration
- **Input Validation**: Comprehensive form and file validation

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

## 📅 Changelog

### 1.1.0 - S3 Integration & Modernization (July 2025)
- **NEW:** Complete Amazon S3 integration with secure file storage
- **NEW:** Modern drag-and-drop file upload interface
- **NEW:** Chunked upload support for large files up to 5GB per file
- **NEW:** Presigned URLs for secure S3 file access
- **NEW:** Form takeover mode with guided 3-step submission process
- **NEW:** Artistic mediums form tag with color-coded checkboxes
- **NEW:** ZIP download functionality for bulk file retrieval
- **NEW:** File metadata database with comprehensive tracking
- **IMPROVED:** Standalone operation - no external dependencies required
- **ENHANCED:** File preview system with lightbox galleries
- **ENHANCED:** Progress tracking with detailed upload status
- **OPTIMIZED:** Shared hosting compatibility

### 1.0.1 - Enhancement Release
- **New:** Custom add submission interface
- **Improved:** Hidden mediums taxonomy from WordPress admin navigation
- **Enhanced:** JavaScript compliance across all components
- **Fixed:** Add submission functionality with independent operation
- **Optimized:** AJAX-powered submission creation
- **Updated:** Form validation and user feedback systems

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

*Transform your artist submission process with professional tools, modern interface design, and secure cloud storage.*
- **ZIP Downloads**: Bulk download original files per submission
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
5. **For S3 file storage**: See detailed setup guide in `S3-SETUP-GUIDE.md`
6. **For self-hosted Uppy files**: See setup guide in `UPPY-SETUP.md` (eliminates CORS issues)

### 🔧 Development Setup

If you're developing locally and need to update Uppy files:

```bash
# Run the build script to download latest Uppy files
php build-uppy.php

# Upload the generated assets/vendor/uppy/ folder to your webserver
```

The plugin automatically detects local Uppy files and falls back to CDN if they're not available.

---

### 📁 File Upload System
This plugin uses **self-hosted Uppy** for modern drag-and-drop file uploads, eliminating CORS issues:

- ✅ **Self-hosted**: Uppy files served from your domain (no CDN dependencies)
- ✅ **No CORS issues**: All files loaded from your server
- ✅ **Automatic fallback**: Falls back to CDN if local files aren't found
- ✅ **Easy updates**: Run `php build-uppy.php` to update Uppy version
- 🆕 **Form Takeover Mode**: Complete multi-step submission experience with guided workflow

#### 🎯 Form Takeover Feature
Enable a modern, guided submission experience by adding the `takeover` option:

```
[uploader your-work takeover]
```

**What it does:**
- Replaces entire form with a single "Submit My Work" button
- Opens full-screen modal with 3-step process:
  1. **Your Details** - Form fields from your CF7 form
  2. **Upload Works** - Drag-and-drop file interface
  3. **Review & Submit** - Summary before final submission
- Professional styling with progress indicators
- Success confirmation popup

**For developers**: See `FORM-TAKEOVER.md` for complete documentation.

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

**File Upload Fields** (choose one format):

**Standard File Upload:**
- `artist-headshot` - Profile photo
- `artwork-1`, `artwork-2`, `artwork-3` - Artwork images  
- `cv` - Curriculum Vitae (PDF)

**Modern S3 Upload (Recommended):**
- `[uppy* your-work max_files:20 max_size:5120]` - Multi-file drag-and-drop upload (5GB max)
- `[uppy artist-headshot max_files:1 max_size:100]` - Single profile photo
- Parameters: `max_files` (default: 20), `max_size` in MB (default: 5120MB = 5GB)

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

### 1.1.0 - S3 Integration & Modernization (July 2025)
- **NEW:** Complete Amazon S3 integration with secure file storage
- **NEW:** Modern Uppy file upload interface with drag-and-drop
- **NEW:** Chunked upload support for large files up to 5GB per file
- **NEW:** Presigned URLs for secure S3 file access
- **NEW:** File metadata database with `cf7as_files` table
- **NEW:** ZIP download functionality for bulk file retrieval
- **NEW:** REST API endpoints for file operations
- **NEW:** Automatic GitHub update system
- **IMPROVED:** Standalone operation - no external dependencies required
- **ENHANCED:** File preview system with lightbox galleries
- **ENHANCED:** Progress tracking with detailed upload status
- **OPTIMIZED:** Removed AWS SDK dependency for shared hosting compatibility

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