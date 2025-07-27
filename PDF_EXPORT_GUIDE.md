# CF7 Artist Submissions - Enhanced PDF Export System

The CF7 Artist Submissions plugin has been overhauled with a new **AWS Lambda-powered PDF export system** that provides professional, pixel-perfect PDF generation using Puppeteer and Chrome in the cloud.

## 🆕 What's New in Version 1.2.0

### Enhanced PDF Export Features

- **🚀 AWS Lambda Integration**: Cloud-based PDF generation using Puppeteer and Chrome
- **📊 Ratings Support**: Include evaluation scores and ratings in PDF exports
- **💬 Curator Comments**: Add curator feedback and discussion threads to PDFs
- **📝 Enhanced Notes**: Expanded curator notes functionality
- **🎨 Professional Styling**: Pixel-perfect rendering with consistent formatting
- **📱 Progress Tracking**: Real-time status updates during PDF generation
- **🔒 Secure Storage**: PDFs stored in S3 with presigned download URLs
- **⚡ Fallback Support**: Automatic fallback to HTML print-to-PDF when Lambda is unavailable

### New Export Options

The PDF export interface now includes comprehensive section selection:

#### Basic Information
- ✅ **Personal Information**: Name, email, contact details, bio
- ✅ **Submitted Works**: Artwork images, descriptions, details

#### Evaluation & Feedback
- ✅ **Ratings & Scores**: Technical, creative, and overall ratings
- ✅ **Curator Notes**: Internal curator observations and notes  
- ✅ **Curator Comments**: Curator feedback and discussion threads

#### Document Options
- ✅ **Confidential Watermark**: Security watermark for sensitive documents

## 🏗️ Architecture Overview

```
WordPress Plugin → AWS Lambda → Puppeteer/Chrome → PDF → S3 → Download URL → User
```

### Components

1. **WordPress Plugin** (`class-cf7-artist-submissions-pdf-export.php`)
   - Handles export requests and data preparation
   - Manages job tracking and status updates
   - Provides fallback HTML generation

2. **AWS Lambda Function** (`lambda-functions/cf7as-pdf-generator/`)
   - Generates professional PDFs using Puppeteer
   - Uploads to S3 and returns download URLs
   - Handles complex layouts and image rendering

3. **Frontend Interface** (`assets/js/pdf-export.js`)
   - Enhanced UI with progress tracking
   - Real-time status updates
   - Comprehensive export options

## 🚀 Setup Instructions

### 1. AWS Lambda Deployment

Deploy the PDF generator Lambda function:

```bash
cd lambda-functions/cf7as-pdf-generator/
./deploy.sh
```

The deployment script will:
- Create necessary IAM roles and policies
- Install dependencies (Puppeteer, Chrome)
- Package and deploy the function
- Run connectivity tests
- Provide the function ARN

### 2. WordPress Configuration

1. **Navigate to WordPress Admin** → CF7 Artist Submissions → Settings → AWS
2. **Scroll to "PDF Generation (AWS Lambda)"** section
3. **Enable AWS Lambda PDF Generation** toggle
4. **Enter the Lambda Function ARN** from deployment output
5. **Test the connection** using the "Test PDF Lambda Function" button
6. **Save settings**

### 3. Required AWS Permissions

The Lambda function requires these IAM permissions:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::your-bucket/*"
    },
    {
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket"
      ],
      "Resource": "arn:aws:s3:::your-bucket"
    },
    {
      "Effect": "Allow",
      "Action": [
        "logs:CreateLogGroup",
        "logs:CreateLogStream",
        "logs:PutLogEvents"
      ],
      "Resource": "arn:aws:logs:eu-west-1:*:*"
    }
  ]
}
```

## 💡 How It Works

### 1. Export Request
When a user clicks "Generate PDF":
1. WordPress collects all submission data and export options
2. Data is packaged and sent to AWS Lambda
3. A unique job ID is created for tracking

### 2. PDF Generation
The Lambda function:
1. Receives submission data and rendering options
2. Generates professional HTML with CSS styling
3. Uses Puppeteer to render HTML to PDF with Chrome
4. Uploads the PDF to S3 with metadata

### 3. Download Delivery
After generation:
1. S3 returns a presigned download URL
2. WordPress receives the callback with URL
3. User gets automatic download + download link

### 4. Progress Tracking
The system provides real-time feedback:
- "Generating PDF..." with progress bar
- Status checks every 3 seconds
- Success/error notifications
- Automatic download initiation

## 🎨 PDF Features

### Professional Styling
- **Typography**: Segoe UI font family for readability
- **Layout**: Grid-based responsive design
- **Colors**: Professional color scheme with brand integration
- **Spacing**: Optimized margins and padding for print

### Content Sections
- **Header**: Site logo and document title
- **Artist Information**: Contact details and biography
- **Submitted Works**: Image gallery with descriptions
- **Ratings**: Star ratings with comments and reviewers
- **Curator Notes**: Professional formatting with highlights
- **Curator Comments**: Discussion threads with timestamps
- **Footer**: Generation metadata and confidentiality notices

### Advanced Features
- **Watermarks**: Optional "Private & Confidential" overlay
- **Page Headers/Footers**: Site branding and page numbers
- **Image Optimization**: Automatic scaling and quality optimization
- **Print Optimization**: A4 format with proper margins

## 🔧 Configuration Options

### Lambda Function Settings
- **Function Name**: `cf7as-pdf-generator`
- **Runtime**: Node.js 18.x
- **Memory**: 2048 MB (recommended)
- **Timeout**: 300 seconds (5 minutes)
- **Environment**: Production-ready with error handling

### WordPress Settings
- **Enable PDF Lambda**: Toggle AWS Lambda vs HTML fallback
- **Function ARN**: Full AWS Lambda function identifier
- **Progress Tracking**: Real-time status updates
- **Cleanup Schedule**: Automatic old PDF cleanup (7 days)

## 🛠️ Troubleshooting

### Common Issues

1. **IAM Permission Denied (CreateRole)**
   - **Error**: `User is not authorized to perform: iam:CreateRole`
   - **Solution**: Your AWS user lacks IAM permissions. See `lambda-functions/cf7as-pdf-generator/MANUAL_IAM_SETUP.md` for detailed instructions
   - **Quick Fix**: Ask your AWS administrator to create the `cf7as-pdf-generator-role` IAM role

2. **Lambda Function Not Found**
   - Verify the function ARN is correct
   - Check AWS region matches
   - Ensure function is deployed

3. **Permission Denied**
   - Verify IAM permissions
   - Check S3 bucket policy
   - Confirm AWS credentials

4. **PDF Generation Timeout**
   - Check Lambda function logs
   - Verify image URLs are accessible
   - Increase Lambda timeout if needed

5. **Images Not Loading**
   - Ensure image URLs are publicly accessible
   - Check CORS policies on image hosting
   - Verify SSL certificates are valid

### Debug Mode

Enable WordPress debug mode to see detailed logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log` for:
- Lambda invocation details
- PDF generation progress
- Error messages and stack traces

### Testing Tools

Use the built-in test functions:
- **Test PDF Lambda Function**: Verifies connectivity
- **S3 Connection Test**: Confirms bucket access
- **AWS Configuration Test**: Validates all settings

## 📊 Performance & Costs

### Performance Benefits
- **Faster Generation**: Cloud processing vs browser rendering
- **Better Quality**: Professional Chrome rendering engine
- **Consistency**: Identical output across all devices/browsers
- **Reliability**: AWS infrastructure with 99.9% uptime

### Cost Considerations
- **Lambda Execution**: ~$0.000016 per 100ms (typically 5-15 seconds per PDF)
- **S3 Storage**: ~$0.023 per GB per month
- **Data Transfer**: ~$0.09 per GB (first 1GB free per month)
- **Typical Cost**: ~$0.001-0.005 per PDF generated

### Optimization Tips
- Use image compression for faster processing
- Enable automatic cleanup to manage S3 costs
- Monitor CloudWatch logs for performance tuning
- Consider reserved Lambda capacity for high volume

## 🔄 Migration from Legacy System

### Automatic Fallback
If Lambda is not configured, the system automatically falls back to the legacy HTML print-to-PDF system, ensuring continuous operation.

### Gradual Migration
1. **Phase 1**: Deploy Lambda function (users still get HTML)
2. **Phase 2**: Enable Lambda in settings (mixed operation)
3. **Phase 3**: Full Lambda operation (HTML as backup only)

### Data Compatibility
All existing submission data is fully compatible with the new PDF system. No data migration is required.

## 🚨 Important Notes

### Security
- PDFs are stored with unique filenames
- Presigned URLs expire after 1 hour
- Confidential watermarks available for sensitive content
- All AWS communication uses IAM authentication

### Cleanup
- Old PDFs are automatically deleted after 7 days
- Job tracking data is cleaned up on schedule
- Failed generations are logged and retried once

### Monitoring
- CloudWatch logs track all Lambda executions
- WordPress logs record PDF generation requests
- S3 access logs available for audit trails

## 📞 Support

### Resources
- **AWS Setup Guide**: `AWS_SETUP_GUIDE.md`
- **Lambda Documentation**: `lambda-functions/cf7as-pdf-generator/README.md`
- **WordPress Plugin Documentation**: WordPress admin help sections

### Troubleshooting Steps
1. Check WordPress debug logs
2. Verify AWS configuration in settings
3. Test Lambda function connectivity
4. Review CloudWatch logs for Lambda errors
5. Confirm S3 bucket permissions and policies

---

The enhanced PDF export system provides a professional, scalable solution for generating high-quality artist submission documents with comprehensive formatting and cloud-based reliability.
