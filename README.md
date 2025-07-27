# CF7 Artist Submissions

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-orange)](https://wordpress.org/plugins/contact-form-7/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple)](https://php.net)

> **Professional artist submission management system for WordPress with modern dashboard, advanced field editing, comprehensive task management, Amazon S3 cloud storage, and AI-powered media conversion.**

Transform your Contact Form 7 submissions into a powerful artist management platform featuring interactive dashboards, editable fields, task management, professional tabbed interface, secure cloud file storage, and automated media processing.

---

## 🚀 Key Features

### 🎨 **Modern Interactive Dashboard**
- Real-time statistics and submission counts with trend analysis
- Interactive widgets for unread messages and outstanding tasks
- Smart filtering by status, date range, open calls, and custom criteria
- Professional CSV and PDF export functionality with bulk operations
- Open calls management with dashboard tag integration

### 📋 **Professional Tabbed Interface**
- 5-tab layout: Profile, Works, Conversations, Actions, and Curator Notes
- Editable header with click-to-edit artist information
- AJAX loading for seamless navigation with smart performance optimization
- Mobile-responsive design with professional glass morphism styling

### ✏️ **Advanced Field Editing System**
- Inline editing for all submission fields with real-time validation
- Auto-save with visual feedback and error handling
- Independent save systems for different content types
- Custom field support with metadata integration

### 📝 **Task Management & Actions System**
- Create and assign actions to team members with role-based access
- Set due dates with automatic notifications and priority management
- Daily email summaries of pending tasks with SMTP integration
- Comprehensive audit trail for complete action lifecycle tracking
- User assignment system with capability-based filtering

### 💬 **Two-Way Conversation Management**
- Threaded email conversations with artists using IMAP automation
- Template-based responses with professional styling integration
- Plus addressing system (no extra email accounts needed)
- Auto-refresh for real-time updates with secure token generation
- WooCommerce template integration for professional appearance

### 📁 **Advanced File Management & Cloud Storage**
- **Amazon S3 Integration**: Secure cloud storage with presigned URLs and signature V4 authentication
- **Modern Upload Interface**: Uppy drag-and-drop with progress tracking and chunked uploads
- **Large File Support**: Upload files up to 5GB per file with multipart upload handling
- **File Previews**: Lightbox galleries for images, embedded video players, and PDF viewers
- **ZIP Downloads**: Bulk download original files per submission with artist identification
- **Professional PDF Export**: Custom layouts with artwork integration and configurable templates
- **Comprehensive Audit Logging**: Complete file access tracking and compliance monitoring

### 🎯 **Form Takeover Mode & Custom Fields**
- **Form Takeover Experience**: Guided 3-step submission with full-screen modal
- **Custom Uploader Field**: `[cf7as_uploader]` tag with advanced configuration options
- **Artistic Mediums Selector**: `[mediums]` tag with color-coded visual styling
- **Asset Loading System**: Shortcode-based loading with `[cf7as_load_assets]` for conflict prevention
- **Tag Generator Integration**: CF7 form builder integration with visual tag generator

### 🔧 **Open Calls Management System**
- Multiple open call configuration with individual Contact Form 7 assignments
- Call status management (active/inactive) with visual indicators
- Dynamic add/remove functionality with bulk operations
- Taxonomy integration with custom term management
- Dashboard filtering integration with call-specific statistics

### 🤖 **AI-Powered Media Processing**
- **AWS Lambda Integration**: Automated image conversion and processing
- **Thumbnail Generation**: Automatic preview generation with multiple size variants
- **Video Processing**: Support for video files with query parameters and CORS compatibility
- **Media Conversion**: Advanced file format handling and optimization
- **Fallback Systems**: WordPress media library integration for local processing

### 🛠️ **Advanced Technical Features**
- **REST API Endpoints**: Comprehensive API for file operations and metadata management
- **Metadata Management**: Complete file tracking with S3 keys and thumbnail URLs
- **Memory-Efficient Processing**: Optimized for large file handling and streaming
- **GitHub Auto-Updater**: Automatic plugin updates from repository
- **WordPress Integration**: Role-based access, custom post types, and taxonomy support

---

## 📦 Installation

### Requirements
- **WordPress**: 5.6 or higher
- **PHP**: 7.4 or higher  
- **Contact Form 7**: Latest version (required)
- **PHP IMAP Extension**: Enabled (for conversation system)
- **Amazon S3 Account**: For cloud file storage (optional but recommended)
- **AWS Lambda**: For advanced media processing (optional)

### Quick Install
1. Download and upload to `/wp-content/plugins/cf7-artist-submissions/`
2. Activate through **Plugins > Installed Plugins**
3. Navigate to **Artist Submissions** in WordPress admin
4. Follow the setup wizard to configure your first form
5. Configure Amazon S3 for cloud file storage (see S3 Setup section)
6. **For asset loading**: Add `[cf7as_load_assets]` shortcode to pages with CF7 forms

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

**Custom Field Tags:**

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

### 2. Asset Loading Configuration

The plugin uses a **shortcode-based asset loading system** to prevent conflicts with other plugins and themes. Scripts and styles are only loaded on pages where you explicitly add the shortcode.

#### Asset Loading Shortcode

Add this shortcode to any page that contains CF7 forms with CF7AS custom fields:

```
[cf7as_load_assets]
```

#### When to Use the Shortcode

Add the `[cf7as_load_assets]` shortcode to pages that contain:
- CF7 forms with custom uploader fields (`[cf7as_uploader]`)
- CF7 forms with mediums fields (`[mediums]`)
- CF7 forms with takeover functionality
- Any other CF7AS custom fields

#### Assets Loaded

When the shortcode is used, the following assets are loaded:
- **CSS**: `common.css`, `custom-uploader.css`, `lightbox.css`
- **JavaScript**: `custom-uploader.js` with REST API configuration
- **Admin Assets**: Automatically loaded in WordPress admin areas

### 3. Amazon S3 Setup

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

### 4. AWS Lambda Media Processing (Optional)

For advanced media processing capabilities:

#### Step 1: Deploy Lambda Function
1. Navigate to `lambda-functions/cf7as-image-converter/`
2. Run `npm install` to install dependencies
3. Update `package.json` with your AWS Account ID
4. Run `npm run deploy-create` for first deployment

#### Step 2: Configure Lambda Integration
- **Function Name**: `cf7as-image-converter`
- **Runtime**: Node.js 18.x
- **Memory**: 1024 MB (recommended)
- **Timeout**: 5 minutes (300 seconds)

### 5. Email Configuration

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

### 6. Open Calls Management

#### Setting Up Open Calls
1. Navigate to **Artist Submissions > Settings > Open Calls**
2. Add new open calls with titles and descriptions
3. Assign Contact Form 7 forms to each call
4. Configure call status (active/inactive)
5. Set dashboard tags for filtering

#### Dashboard Integration
- Open calls appear in dashboard filtering dropdown
- Statistics are calculated per open call
- Dashboard tags provide user-friendly names

### 7. Artistic Mediums Setup

1. **Navigate to** Artist Submissions > Artistic Mediums (in WordPress admin)
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
4. Filter submissions by open calls using dashboard tags

### Managing Submissions
1. **Profile Tab**: View and edit all submission details inline with auto-save
2. **Works Tab**: Browse artwork gallery with lightbox preview, integrated PDF viewer, and video support
3. **Conversations Tab**: Send messages, track email threads, and use template responses
4. **Actions Tab**: Create tasks, set deadlines, assign team members, and track progress
5. **Curator Notes Tab**: Add private internal notes with rich text editing

### Form Takeover Experience
When enabled, the form takeover provides a guided 3-step process:

1. **Step 1: Your Details**
   - Form fields extracted from your CF7 form
   - Real-time validation with error messaging
   - Clean, professional styling with progress indicators

2. **Step 2: Upload Works**
   - Drag-and-drop file interface with Uppy integration
   - Progress tracking for each file with chunked uploads
   - Support for large files up to 5GB with S3 multipart upload

3. **Step 3: Review & Submit**
   - Summary of all entered information
   - File list with thumbnails and details
   - Final submission confirmation with success feedback

### Artistic Mediums Features
- **Visual Selection**: Color-coded checkboxes for easy identification
- **Responsive Layout**: Horizontal grid that adapts to screen size
- **Text Wrapping**: Long medium names wrap gracefully
- **Validation**: Required field support with error messaging
- **Taxonomy Integration**: Automatic population from WordPress terms

### Status Workflow
- **New** (blue) → **Reviewed** (green) → **Awaiting Information** (orange)
- **Selected** (purple) or **Rejected** (red)
- **Custom Statuses**: Add your own workflow stages

### Export & Download Options
- **CSV Export**: Bulk data export with advanced filtering and custom fields
- **PDF Export**: Professional submission packets with artwork layouts and metadata
- **ZIP Downloads**: Bulk file downloads per submission with artist identification
- **Audit Logs**: Complete activity tracking for compliance and reporting

---

## 🛠️ Troubleshooting

### Common Issues

**Plugin not loading properly:**
- Verify WordPress 5.6+ and PHP 7.4+
- Check browser console for JavaScript errors
- Clear caching plugins and CDN caches
- Ensure Contact Form 7 is active
- Add `[cf7as_load_assets]` shortcode to pages with CF7 forms

**File uploads failing:**
- Verify S3 configuration and credentials in settings
- Check AWS IAM permissions for bucket access
- Ensure bucket exists and is accessible in correct region
- Test with smaller files first (under 100MB)
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)

**Custom fields not displaying:**
- Add `[cf7as_load_assets]` shortcode to page
- Verify Contact Form 7 form is properly configured
- Check form ID in plugin settings matches your form
- Clear any caching plugins or CDN caches

**Mediums not displaying:**
- Add artistic medium terms in WordPress admin (Artistic Mediums)
- Verify mediums shortcode syntax: `[mediums* field-name]`
- Ensure `[cf7as_load_assets]` shortcode is present on page
- Check browser console for JavaScript errors

**Email conversations not working:**
- Verify SMTP configuration in settings
- Test plus addressing with a simple email to `youremail+test@yourdomain.com`
- Check IMAP settings and credentials
- Ensure PHP IMAP extension is enabled on server
- Verify firewall allows IMAP connections

**Actions not saving:**
- Confirm user has proper edit permissions
- Check database connectivity and table structure
- Enable WordPress debug mode for detailed errors
- Verify user roles and capabilities

### AWS-Specific Issues

**S3 Access Denied:**
- Verify IAM user permissions include all required S3 actions
- Check bucket policy configuration and region settings
- Ensure access keys are correct and not expired
- Confirm bucket region matches plugin settings exactly

**Large File Upload Issues:**
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`, `max_execution_time`)
- Verify AWS S3 multipart upload permissions in IAM policy
- Ensure stable internet connection for large file transfers
- Monitor AWS CloudWatch logs for Lambda function errors

**Lambda Function Issues:**
- Check function configuration (memory, timeout, runtime)
- Verify Node.js dependencies are properly installed
- Monitor CloudWatch logs for execution errors
- Test function with simple payload before processing files

### Performance Issues

**Dashboard loading slowly:**
- Check database performance and optimize tables
- Reduce number of submissions displayed per page
- Enable object caching (Redis/Memcached)
- Monitor server resources during peak usage

**File processing delays:**
- Increase Lambda function memory allocation
- Check S3 transfer acceleration settings
- Monitor network bandwidth during uploads
- Consider regional AWS service placement

---

## 📋 System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| WordPress | 5.6 | 6.0+ |
| PHP | 7.4 | 8.1+ |
| MySQL | 5.6 | 8.0+ |
| Memory | 128MB | 512MB+ |
| PHP Extensions | IMAP, cURL, GD | IMAP, cURL, GD, Imagick |

**Browser Support**: Chrome 70+, Firefox 65+, Safari 12+, Edge 79+

**AWS Requirements**:
- Active AWS account with billing enabled
- S3 bucket with appropriate permissions
- IAM user with programmatic access
- Lambda function for media processing (optional)

---

## 💰 Pricing Information

### WordPress Plugin
- **Free** - Core plugin functionality with all features

### AWS Costs (Pay-as-you-use)
- **S3 Storage**: ~$0.023/GB/month (varies by region)
- **Data Transfer**: ~$0.09/GB (first 1GB free monthly)
- **API Requests**: ~$0.0004/1000 requests
- **Lambda Processing**: ~$0.20/million requests + compute time

**Example Monthly Costs**:
- **Small Organization** (10GB storage, 1K files, 100 submissions): ~$1.00/month
- **Medium Organization** (100GB storage, 10K files, 1K submissions): ~$3.50/month
- **Large Organization** (1TB storage, 100K files, 10K submissions): ~$30/month

*Costs may vary by region, usage patterns, and AWS pricing changes*

---

## 🔒 Security Features

- **Presigned URLs**: Secure, time-limited file access with AWS Signature V4
- **Private S3 Buckets**: No public access to uploaded files
- **Plus Addressing**: Secure email routing without exposing addresses
- **Comprehensive Audit Logging**: Complete activity tracking for compliance
- **Role-based Access Control**: WordPress user role integration with capability filtering
- **Input Validation**: Comprehensive form and file validation with sanitization
- **CSRF Protection**: WordPress nonce verification for all operations
- **SQL Injection Prevention**: Prepared statements for all database operations

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

## 📅 Changelog

### 1.2.0 - Open Calls & Advanced Management (Current)
**🎯 Open Calls Management System**
- **NEW:** **Open Calls Configuration** - Multiple open call management with individual CF7 form assignments
- **NEW:** **Dashboard Tag Integration** - User-friendly call filtering with custom dashboard labels
- **NEW:** **Call Status Management** - Active/inactive call control with visual indicators
- **NEW:** **Taxonomy Integration** - Custom term management for call organization

**⚡ Dashboard & Interface Improvements**
- **ENHANCED:** **Dashboard Filtering** - Open calls integration with real-time statistics
- **ENHANCED:** **Settings Navigation** - Improved responsive design that scales properly
- **ENHANCED:** **Tab Interface** - Better mobile responsiveness and touch interactions
- **ENHANCED:** **Performance Optimization** - Faster loading with optimized queries

**🔧 Technical Enhancements**
- **IMPROVED:** **Asset Loading System** - Shortcode-based loading with `[cf7as_load_assets]`
- **IMPROVED:** **Form Tag Generator** - Enhanced CF7 integration with visual builders
- **IMPROVED:** **REST API** - Extended endpoints for metadata and file operations
- **IMPROVED:** **Database Schema** - Optimized table structure for better performance

### 1.1.0 - S3 Integration & Media Revolution (July 2025)
**🎯 Form Experience Revolution**
- **NEW:** **Form Takeover Mode** - Complete multi-step submission experience with guided 3-step workflow
- **NEW:** **Artistic Mediums Selection** - Custom `[mediums]` form tag with color-coded visual styling
- **NEW:** **Enhanced Drag-and-Drop** - Improved stability and responsiveness with smart drag counter management
- **NEW:** **Advanced Form Validation** - Enhanced upload process with duplicate file detection

**🎥 Media & File Management**
- **NEW:** **Amazon S3 Integration** - Complete cloud storage with presigned URLs and secure file handling
- **NEW:** **Modern Uppy Interface** - Advanced drag-and-drop with progress tracking and chunked uploads
- **NEW:** **Large File Support** - Upload files up to 5GB per file with multipart handling
- **NEW:** **Video File Support** - Comprehensive video handling in lightbox with embedded players
- **NEW:** **Enhanced Lightbox** - Navigation controls, improved styling, and optimized image handling
- **NEW:** **ZIP Downloads with Artist Names** - Bulk file downloads with proper identification
- **NEW:** **Thumbnail Generation** - Automatic preview generation with multiple size variants
- **NEW:** **AWS Lambda Integration** - Automated media processing and conversion

**⚡ Performance & UX Improvements**
- **ENHANCED:** **Compact Uploader Design** - Immediate drag area collapse for cleaner interface
- **ENHANCED:** **AJAX Tab Loading** - Improved performance for tab content switching
- **ENHANCED:** **Upload Progress Tracking** - Detailed status updates during file uploads
- **ENHANCED:** **Form Handler Optimization** - Better file submission debugging and processing

**🛠️ Technical Enhancements**
- **NEW:** **REST API Endpoints** - Comprehensive API for file operations and metadata management
- **NEW:** **Metadata Management System** - Complete file tracking with S3 integration
- **NEW:** **GitHub Auto-Updater** - Automatic plugin updates from repository
- **IMPROVED:** **S3 Handler** - Advanced integration with error handling and retry mechanisms
- **IMPROVED:** **Security Implementation** - Enhanced authentication and validation
- **FIXED:** **Cross-browser Compatibility** - Resolved drag-and-drop issues across platforms
- **FIXED:** **File Upload Stability** - Enhanced error handling and recovery systems

### 1.0.1 - Enhancement Release
- **NEW:** Custom add submission interface replacing artist view-based implementation
- **IMPROVED:** Hidden artistic mediums taxonomy from WordPress admin navigation for cleaner interface
- **ENHANCED:** JavaScript compliance across all components with better error handling
- **FIXED:** Add submission functionality now works independently without existing submission data
- **OPTIMIZED:** AJAX-powered submission creation with integrated file upload management
- **UPDATED:** Form validation and user feedback systems for improved user experience

### 1.0.0 - Initial Release
- Contact Form 7 integration with custom post type creation and metadata handling
- Modern interactive dashboard with real-time statistics and trend analysis
- Professional 5-tab interface (Profile, Works, Conversations, Actions, Notes)
- Advanced inline field editing system with auto-save and validation
- Comprehensive task management system with assignments, due dates, and notifications
- Two-way email conversation system with plus addressing and IMAP automation
- Secure file management with lightbox preview and professional styling
- Professional PDF export with configurable layouts and artwork integration
- Comprehensive audit logging for compliance and activity tracking
- Status management workflow with visual indicators and circular badges
- CSV export functionality with advanced filtering and bulk operations
- Responsive design optimized for all devices with mobile-first approach

---

*Transform your artist submission process with professional tools, modern interface design, secure cloud storage, and automated media processing.*