# S3 Integration Setup Guide

Complete installation and configuration guide for the CF7 Artist Submissions Plugin with Amazon S3 file storage, including advanced features like Lambda thumbnail generation and MediaConvert video processing.

## 🚀 Quick Start

### 1. Plugin Installation

The plugin is now **completely standalone** - no additional dependencies required!

1. **Upload plugin folder** to `/wp-content/plugins/cf7-artist-submissions/`
2. **Activate plugin** through WordPress Admin → Plugins
3. **The plugin works immediately** - no Composer or command line tools needed

### 2. Configure Contact Form 7

Update your Contact Form 7 form to use the new Uppy upload field:

**Replace existing file fields:**
```
Old: [file your-work]
New: [uppy* your-work max_files:20 max_size:5120]
```

**Field Parameters:**
- `uppy*` - Required upload field
- `uppy` - Optional upload field  
- `max_files` - Maximum files allowed (default: 20)
- `max_size` - Max file size in MB (default: 5120 = 5GB)

**Example Complete Form:**
```
<label> Your Name (required)
    [text* your-name] </label>

<label> Your Email (required)
    [email* your-email] </label>

<label> Upload Your Artwork (required)
    [uppy* your-work max_files:10 max_size:2048] </label>

<label> Artist Statement
    [textarea your-statement] </label>

[submit "Send"]
```

---

## 🔐 Amazon Web Services Setup

### Step 1: Create AWS Account

1. **Go to AWS Console**: https://aws.amazon.com/console/
2. **Click "Create an AWS Account"**
3. **Enter account details:**
   - Email address
   - Password
   - AWS account name
4. **Contact Information:**
   - Select "Personal" or "Professional"
   - Enter contact details
5. **Payment Information:**
   - Add credit/debit card (required for verification)
   - AWS Free Tier available for new accounts
6. **Identity Verification:**
   - Phone verification required
7. **Choose Support Plan:**
   - Select "Basic support - Free" for getting started

### Step 2: AWS Console Overview

Once logged in to the AWS Console:

1. **Services Menu:** Top-left corner - access all AWS services
2. **Region Selector:** Top-right - choose your preferred region
3. **Account Menu:** Top-right - billing, security credentials
4. **Search Bar:** Find services quickly

**Recommended Regions for Performance:**
- **US East (N. Virginia)** - `us-east-1` (lowest latency for most users)
- **EU (Ireland)** - `eu-west-1` (European users)
- **Asia Pacific (Tokyo)** - `ap-northeast-1` (Asian users)

---

## 📦 S3 Bucket Setup

### Step 1: Create S3 Bucket

1. **Navigate to S3:**
   - Click "Services" → "Storage" → "S3"
   - Or search "S3" in the search bar

2. **Create Bucket:**
   - Click "Create bucket"
   - **Bucket name**: Choose unique name (e.g., `my-artist-submissions-2025`)
   - **Region**: Select region closest to your users
   - **Object Ownership**: ACLs disabled (recommended)
   - **Block Public Access**: Keep all settings checked ✅
   - **Bucket Versioning**: Disable (unless you need file history)
   - **Tags**: Add optional tags for organization
   - **Default encryption**: 
     - **Encryption type**: Server-side encryption with Amazon S3 managed keys (SSE-S3)
     - **Bucket Key**: Enabled ✅ (reduces encryption costs)
   - Click "Create bucket"

### Step 2: Configure CORS Policy (Do This First)

Essential for browser uploads from your website:

1. **Navigate to CORS configuration:**
   - In your bucket, go to "Permissions" tab
   - Scroll to "Cross-origin resource sharing (CORS)"
   - Click "Edit"

2. **Add CORS configuration:**

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["PUT", "POST", "GET", "DELETE"],
        "AllowedOrigins": ["https://your-domain.com", "https://www.your-domain.com"],
        "ExposeHeaders": ["ETag", "x-amz-meta-custom-header"],
        "MaxAgeSeconds": 3000
    }
]
```

**Important:** Replace `your-domain.com` with your actual website domain

### Step 3: Skip Bucket Policy For Now

⚠️ **Important**: Don't add the bucket policy yet! You need to create the IAM user first (next section), then come back to add the bucket policy.

Essential for browser uploads from your website:

1. **Navigate to CORS configuration:**
   - In your bucket, go to "Permissions" tab
   - Scroll to "Cross-origin resource sharing (CORS)"
   - Click "Edit"

2. **Add CORS configuration:**

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["PUT", "POST", "GET", "DELETE"],
        "AllowedOrigins": ["https://your-domain.com", "https://www.your-domain.com"],
        "ExposeHeaders": ["ETag", "x-amz-meta-custom-header"],
        "MaxAgeSeconds": 3000
    }
]
```

**Important:** Replace `your-domain.com` with your actual website domain

---

## 👤 IAM User Setup

### Step 1: Create IAM User

1. **Navigate to IAM:**
   - Services → Security, Identity, & Compliance → IAM
   - Or search "IAM"

2. **Create User:**
   - Click "Users" in left sidebar
   - Click "Create user"
   - **User name**: `cf7-artist-submissions`
   - **Provide user access to the AWS Management Console**: Leave **UNCHECKED** ❌
     - This is optional and not needed for the plugin
     - We only need programmatic access (API keys)
   - Click "Next"

### Step 2: Set Permissions

1. **Choose permission options:**
   - You'll see three options:
     - **Add user to group** - For managing multiple users (skip this)
     - **Copy permissions from existing user** - If you have another user (skip this)
     - **Attach policies directly** - Choose this option ✅
   - Select **"Attach policies directly"**

2. **Create custom policy:**
   - Click **"Create policy"** button
   - This will open a new tab for policy creation

3. **In the new policy tab:**
   - Click **"JSON"** tab (not Visual editor)
   - **Replace all content** with:

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
                "s3:PutObjectAcl",
                "s3:ListBucket",
                "s3:GetBucketLocation"
            ],
            "Resource": [
                "arn:aws:s3:::YOUR-BUCKET-NAME",
                "arn:aws:s3:::YOUR-BUCKET-NAME/*"
            ]
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListAllMyBuckets",
                "s3:GetBucketLocation"
            ],
            "Resource": "*"
        }
    ]
}
```

3. **Name and create policy:**
   - Click **"Next"** (skip tags)
   - **Policy name**: `CF7ArtistSubmissionsPolicy`
   - **Description**: `S3 access for CF7 Artist Submissions plugin`
   - Click **"Create policy"**
   - **Close this tab** and return to the user creation tab

4. **Attach policy to user:**
   - Back on the user creation page, click **"Refresh"** (🔄 icon)
   - In the search box, type: `CF7ArtistSubmissionsPolicy`
   - **Check the box** next to `CF7ArtistSubmissionsPolicy`
   - Click **"Next"** to continue

### Step 3: Create Access Keys

1. **Review and create:**
   - Review user details
   - Click "Create user"

2. **Generate access keys:**
   - Click on the created user
   - Go to "Security credentials" tab
   - Click "Create access key"
   - Select "Application running outside AWS"
   - Click "Next"
   - Add description (optional): "CF7 Artist Submissions WordPress Plugin"
   - Click "Create access key"

3. **⚠️ IMPORTANT - Save credentials:**
   - **Access Key ID**: Copy and save securely
   - **Secret Access Key**: Copy and save securely
   - Click "Download .csv file" for backup
   - **Note**: Secret key is only shown once!

### Step 4: Now Add S3 Bucket Policy

Now that the IAM user exists, go back to your S3 bucket to add the bucket policy:

1. **Navigate back to your S3 bucket:**
   - Go to S3 service
   - Click on your bucket name
   - Go to "Permissions" tab
   - Scroll to "Bucket policy"
   - Click "Edit"

2. **Add bucket policy** (replace placeholders with your actual values):

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "CF7ArtistSubmissionsAccess",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:user/cf7-artist-submissions"
            },
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:PutObjectAcl"
            ],
            "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/*"
        },
        {
            "Sid": "CF7ArtistSubmissionsListBucket",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:user/cf7-artist-submissions"
            },
            "Action": [
                "s3:ListBucket",
                "s3:GetBucketLocation"
            ],
            "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME"
        }
    ]
}
```

**Replace these placeholders:**
- `YOUR-ACCOUNT-ID`: Your 12-digit AWS Account ID
- `YOUR-BUCKET-NAME`: Your actual bucket name (e.g., `my-artist-submissions-2025`)

**To find your Account ID:**
1. Click on your account name (top-right corner)
2. Click "Account" 
3. Your 12-digit Account ID is displayed

**Example with real values:**
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "CF7ArtistSubmissionsAccess",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::123456789012:user/cf7-artist-submissions"
            },
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:PutObjectAcl"
            ],
            "Resource": "arn:aws:s3:::my-artist-submissions-2025/*"
        },
        {
            "Sid": "CF7ArtistSubmissionsListBucket",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::123456789012:user/cf7-artist-submissions"
            },
            "Action": [
                "s3:ListBucket",
                "s3:GetBucketLocation"
            ],
            "Resource": "arn:aws:s3:::my-artist-submissions-2025"
        }
    ]
}
```

3. **Save the policy:**
   - Click "Save changes"
   - ⚠️ **You may see a warning**: "This bucket has public access blocked"
   - **This is normal and expected!** ✅
   - The warning appears because Block Public Access is enabled (which is secure)
   - Our policy only grants access to the specific IAM user, not the general public
   - Click "Save changes" anyway - the policy will work correctly
   - You should see: "Bucket policy has been successfully saved"

---

## 🔧 WordPress Plugin Configuration

### Step 1: Configure S3 Settings

1. **Go to WordPress Admin:**
   - Navigate to **Artist Submissions > Settings**
   - Scroll to **S3 Configuration** section

2. **Enter AWS credentials:**
   - **AWS Access Key ID**: Paste from IAM user creation
   - **AWS Secret Access Key**: Paste from IAM user creation
   - **AWS Region**: Enter region code (e.g., `us-east-1`, `eu-west-1`)
   - **S3 Bucket Name**: Enter your bucket name (without `s3://`)

3. **Test connection:**
   - Click **"Test S3 Connection"** button
   - Should show: ✅ "S3 connection successful!"

### Step 2: Update Contact Form 7

Replace your existing file upload fields:

**Before:**
```
[file* portfolio]
```

**After:**
```
[uppy* portfolio max_files:10 max_size:2048]
```

**Save your form** and test the upload functionality.

---

## 🖼️ Advanced: Lambda Thumbnail Generation

### Overview

AWS Lambda can automatically generate thumbnails for uploaded images, providing faster loading times in your admin interface.

### Step 1: Create Lambda Function

1. **Navigate to Lambda:**
   - Services → Compute → Lambda

2. **Create function:**
   - Click "Create function"
   - Select "Author from scratch"
   - **Function name**: `cf7-thumbnail-generator`
   - **Runtime**: Python 3.9
   - **Architecture**: x86_64
   - Click "Create function"

### Step 2: Configure Lambda Code

1. **Replace function code** with:

```python
import json
import boto3
import os
from PIL import Image
from io import BytesIO
import urllib.parse

s3 = boto3.client('s3')

def lambda_handler(event, context):
    # Get bucket and object key from S3 event
    bucket = event['Records'][0]['s3']['bucket']['name']
    key = urllib.parse.unquote_plus(event['Records'][0]['s3']['object']['key'], encoding='utf-8')
    
    # Only process images in uploads folder
    if not key.startswith('uploads/') or not key.lower().endswith(('.jpg', '.jpeg', '.png', '.gif', '.bmp')):
        return {
            'statusCode': 200,
            'body': json.dumps('Not an image file or not in uploads folder')
        }
    
    try:
        # Download original image
        response = s3.get_object(Bucket=bucket, Key=key)
        image_data = response['Body'].read()
        
        # Open image with PIL
        with Image.open(BytesIO(image_data)) as image:
            # Convert to RGB if necessary
            if image.mode in ('RGBA', 'LA', 'P'):
                image = image.convert('RGB')
            
            # Create thumbnail (300x300 max, maintain aspect ratio)
            image.thumbnail((300, 300), Image.Resampling.LANCZOS)
            
            # Save thumbnail to bytes
            thumbnail_buffer = BytesIO()
            image.save(thumbnail_buffer, 'JPEG', quality=85, optimize=True)
            thumbnail_buffer.seek(0)
            
            # Generate thumbnail key
            path_parts = key.split('/')
            filename = path_parts[-1]
            name, ext = os.path.splitext(filename)
            thumbnail_key = f"thumbnails/{path_parts[1]}/{name}_thumb.jpg"
            
            # Upload thumbnail to S3
            s3.put_object(
                Bucket=bucket,
                Key=thumbnail_key,
                Body=thumbnail_buffer.getvalue(),
                ContentType='image/jpeg',
                Metadata={
                    'original-key': key,
                    'generated-by': 'cf7-thumbnail-generator'
                }
            )
            
        return {
            'statusCode': 200,
            'body': json.dumps(f'Thumbnail created: {thumbnail_key}')
        }
        
    except Exception as e:
        print(f'Error processing {key}: {str(e)}')
        return {
            'statusCode': 500,
            'body': json.dumps(f'Error: {str(e)}')
        }
```

### Step 3: Add PIL Layer

1. **Create deployment package:**
   - PIL (Pillow) is not included in Lambda by default
   - Create a Lambda layer or use existing public layer

2. **Add PIL layer:**
   - In your Lambda function, scroll to "Layers"
   - Click "Add a layer"
   - Select "AWS layers"
   - Choose "AWSLambda-Python39-SciPy1x" (includes PIL)
   - Click "Add"

### Step 4: Configure S3 Trigger

1. **Add trigger:**
   - Click "Add trigger"
   - Select "S3"
   - **Bucket**: Your S3 bucket
   - **Event type**: All object create events
   - **Prefix**: `uploads/`
   - **Suffix**: Leave empty
   - Click "Add"

### Step 5: Set Permissions

1. **Update execution role:**
   - Go to "Configuration" → "Permissions"
   - Click on the execution role
   - Add policy for S3 access:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject"
            ],
            "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/*"
        }
    ]
}
```

---

## 🎬 Advanced: MediaConvert Video Processing

### Overview

AWS MediaConvert can automatically process uploaded videos to create web-optimized versions and generate video thumbnails.

### Step 1: Create MediaConvert Job Template

1. **Navigate to MediaConvert:**
   - Services → Media Services → MediaConvert

2. **Create job template:**
   - Click "Job templates"
   - Click "Create template"
   - **Name**: `cf7-video-optimization`

### Step 2: Configure Output Settings

1. **Input settings:**
   - **Source**: Will be set dynamically by Lambda

2. **Output groups:**
   - Click "Add output group"
   - Select "File group"
   - **Destination**: `s3://YOUR-BUCKET-NAME/processed/`

3. **Video output:**
   - **Container**: MP4
   - **Video codec**: H.264
   - **Resolution**: 1920x1080 (or lower for web)
   - **Bitrate**: 5000 kbps
   - **Frame rate**: 30 fps

4. **Thumbnail output:**
   - Add another output
   - **Container**: No container
   - **Video codec**: Frame capture to JPEG
   - **Thumbnail interval**: 10 seconds

### Step 3: Create MediaConvert Lambda Trigger

1. **Create new Lambda function:**
   - **Name**: `cf7-video-processor`
   - **Runtime**: Python 3.9

2. **Function code:**

```python
import json
import boto3
import os

mediaconvert = boto3.client('mediaconvert', region_name='us-east-1')  # Adjust region
s3 = boto3.client('s3')

def lambda_handler(event, context):
    # Get bucket and object key
    bucket = event['Records'][0]['s3']['bucket']['name']
    key = event['Records'][0]['s3']['object']['key']
    
    # Only process videos
    if not key.lower().endswith(('.mp4', '.mov', '.avi', '.mkv', '.webm')):
        return {
            'statusCode': 200,
            'body': json.dumps('Not a video file')
        }
    
    try:
        # Get MediaConvert endpoint
        endpoints = mediaconvert.describe_endpoints()
        endpoint_url = endpoints['Endpoints'][0]['Url']
        
        # Create MediaConvert client with endpoint
        mc_client = boto3.client('mediaconvert', endpoint_url=endpoint_url)
        
        # Create job
        job_settings = {
            "Role": "arn:aws:iam::YOUR-ACCOUNT-ID:role/MediaConvertRole",
            "Settings": {
                "Inputs": [
                    {
                        "FileInput": f"s3://{bucket}/{key}",
                        "AudioSelectors": {
                            "Audio Selector 1": {
                                "DefaultSelection": "DEFAULT"
                            }
                        },
                        "VideoSelector": {},
                        "TimecodeSource": "ZEROBASED"
                    }
                ],
                "OutputGroups": [
                    {
                        "Name": "Web Optimized",
                        "Outputs": [
                            {
                                "VideoDescription": {
                                    "CodecSettings": {
                                        "Codec": "H_264",
                                        "H264Settings": {
                                            "MaxBitrate": 5000000,
                                            "RateControlMode": "QVBR"
                                        }
                                    },
                                    "Width": 1920,
                                    "Height": 1080
                                },
                                "AudioDescriptions": [
                                    {
                                        "CodecSettings": {
                                            "Codec": "AAC",
                                            "AacSettings": {
                                                "Bitrate": 128000,
                                                "CodingMode": "CODING_MODE_2_0",
                                                "SampleRate": 48000
                                            }
                                        }
                                    }
                                ],
                                "ContainerSettings": {
                                    "Container": "MP4"
                                }
                            }
                        ],
                        "OutputGroupSettings": {
                            "Type": "FILE_GROUP_SETTINGS",
                            "FileGroupSettings": {
                                "Destination": f"s3://{bucket}/processed/"
                            }
                        }
                    }
                ]
            }
        }
        
        # Submit job
        response = mc_client.create_job(**job_settings)
        
        return {
            'statusCode': 200,
            'body': json.dumps(f'MediaConvert job created: {response["Job"]["Id"]}')
        }
        
    except Exception as e:
        print(f'Error: {str(e)}')
        return {
            'statusCode': 500,
            'body': json.dumps(f'Error: {str(e)}')
        }
```

---

## 📊 Monitoring & Optimization

### CloudWatch Monitoring

1. **Set up CloudWatch alarms:**
   - **S3 requests**: Monitor API call volume
   - **Lambda errors**: Track thumbnail generation failures
   - **Storage costs**: Monitor S3 storage usage

2. **Key metrics to watch:**
   - `NumberOfObjects`: Total files stored
   - `BucketSizeBytes`: Storage usage
   - `AllRequests`: API request count
   - `4xxErrors`: Client errors
   - `5xxErrors`: Server errors

### Cost Optimization

1. **S3 storage classes:**
   - **Standard**: For frequently accessed files
   - **Standard-IA**: For archived submissions
   - **Glacier**: For long-term archival

2. **Lifecycle policies:**
   - Move old files to cheaper storage classes
   - Delete incomplete multipart uploads
   - Archive thumbnails after 1 year

3. **CloudFront CDN:**
   - Faster file delivery worldwide
   - Reduced S3 bandwidth costs
   - Better user experience

### Example Lifecycle Policy:

```json
{
    "Rules": [
        {
            "ID": "ArchiveOldSubmissions",
            "Status": "Enabled",
            "Filter": {
                "Prefix": "uploads/"
            },
            "Transitions": [
                {
                    "Days": 30,
                    "StorageClass": "STANDARD_IA"
                },
                {
                    "Days": 365,
                    "StorageClass": "GLACIER"
                }
            ]
        },
        {
            "ID": "CleanupIncompleteUploads",
            "Status": "Enabled",
            "AbortIncompleteMultipartUpload": {
                "DaysAfterInitiation": 7
            }
        }
    ]
}
```

---

## 🚨 Troubleshooting

### Common Issues

**S3 Connection Failed:**
- ✅ Verify AWS credentials are correct
- ✅ Check IAM user has required permissions
- ✅ Confirm bucket name and region are correct
- ✅ Test credentials using AWS CLI: `aws s3 ls s3://your-bucket-name`

**CORS Errors:**
- ✅ Add your domain to CORS policy
- ✅ Include both `http://` and `https://` versions
- ✅ Check browser developer console for specific errors

**"Public access is blocked" Warning:**
- ✅ This warning is **normal and expected**
- ✅ Block Public Access should remain **enabled** for security
- ✅ The bucket policy works despite this warning
- ✅ Only the specific IAM user can access files (not the general public)
- ✅ This is the **correct and secure** configuration

**Large File Upload Failures:**
- ✅ Confirm chunked uploads are enabled (files >50MB)
- ✅ Check server timeout settings
- ✅ Verify bucket policy allows multipart uploads

**Thumbnail Generation Not Working:**
- ✅ Check Lambda function logs in CloudWatch
- ✅ Verify S3 trigger is configured correctly
- ✅ Ensure PIL layer is added to Lambda function

### Debug Mode

Enable WordPress debug mode for detailed error logging:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `/wp-content/debug.log`

---

## 🎯 Production Checklist

### Security
- ✅ AWS credentials stored securely (not in code)
- ✅ S3 bucket is private (public access blocked)
- ✅ IAM user has minimal required permissions
- ✅ CORS policy restricts to your domain only
- ✅ SSL/HTTPS enabled on WordPress site

### Performance
- ✅ CloudFront CDN configured (optional)
- ✅ S3 Transfer Acceleration enabled (optional)
- ✅ Thumbnail generation working
- ✅ Lifecycle policies configured

### Monitoring
- ✅ CloudWatch alarms set up
- ✅ Billing alerts configured
- ✅ Regular backup of WordPress database
- ✅ Monitor S3 storage costs

### Testing
- ✅ Test file uploads <50MB (single upload)
- ✅ Test file uploads >50MB (chunked upload)
- ✅ Test various file types (images, videos, documents)
- ✅ Test form submission with uploaded files
- ✅ Verify files appear in admin interface

---

**🎉 Congratulations!** Your CF7 Artist Submissions plugin is now configured with enterprise-grade file storage, automatic thumbnails, and professional video processing capabilities!

#### Create S3 Bucket

1. **Log into AWS Console** → S3
2. **Create new bucket** with unique name (e.g., `my-artist-submissions-2025`)
3. **Choose region** closest to your users
4. **Keep default settings** for now

### Configure Bucket Policy

Replace `YOUR-ACCOUNT-ID`, `YOUR-USER`, and `your-bucket-name`:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "CF7ArtistSubmissions",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:user/YOUR-USER"
            },
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject"
            ],
            "Resource": "arn:aws:s3:::your-bucket-name/*"
        },
        {
            "Sid": "CF7ArtistSubmissionsListBucket",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:user/YOUR-USER"
            },
            "Action": "s3:ListBucket",
            "Resource": "arn:aws:s3:::your-bucket-name"
        }
    ]
}
```

#### Configure CORS Policy

Essential for browser uploads:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["PUT", "POST", "GET"],
        "AllowedOrigins": ["https://your-domain.com"],
        "ExposeHeaders": []
    }
]
```

### Create IAM User

1. **Go to IAM** → Users → Create user
2. **Username**: `cf7-artist-submissions`
3. **Attach policies**: Create custom policy with S3 permissions
4. **Save Access Key ID and Secret Access Key**

**Custom Policy Example:**
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
                "arn:aws:s3:::your-bucket-name",
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

### 3. Plugin Configuration

1. **Go to WordPress Admin** → CF7 Submissions → Settings
2. **Scroll to S3 Configuration section**
3. **Fill in your AWS details:**
   - **AWS Access Key ID**: From IAM user creation
   - **AWS Secret Access Key**: From IAM user creation
   - **AWS Region**: Match your S3 bucket region
   - **S3 Bucket Name**: Your bucket name
4. **Click "Test S3 Connection"** to verify

**That's it!** No command line tools or technical setup required.

## 📁 Amazon S3 Setup

### Create S3 Bucket

Files are organized in S3 as:

```
your-bucket-name/
├── uploads/
│   ├── 123/                    # Submission ID
│   │   ├── artwork1.jpg        # Original files
│   │   ├── cv.pdf
│   │   └── statement.docx
│   └── 124/
│       └── portfolio.zip
└── thumbnails/                 # Generated thumbnails
    ├── 123/
    │   └── artwork1_thumb.jpg
    └── 124/
        └── portfolio_thumb.jpg
```

## 🎯 Supported File Types

### Images
JPG, JPEG, PNG, GIF, WebP, SVG, BMP, TIFF

### Videos  
MP4, MOV, WebM, AVI, MKV, MPEG

### Documents
PDF, DOC, DOCX, TXT, RTF

## 🛡️ Security Features

- **Presigned URLs**: Time-limited access (expires in 1 hour)
- **File Type Validation**: Client and server-side checking
- **MIME Type Verification**: Content-based validation
- **Size Limits**: Configurable per-file size limits
- **Private Bucket**: Files not publicly accessible

## 🔧 Advanced Configuration

### Environment Variables (Optional)

For enhanced security, you can use environment variables:

```php
// wp-config.php
define('CF7AS_AWS_ACCESS_KEY', 'your-access-key');
define('CF7AS_AWS_SECRET_KEY', 'your-secret-key');
define('CF7AS_AWS_REGION', 'us-east-1');
define('CF7AS_S3_BUCKET', 'your-bucket-name');
```

### Thumbnail Generation

The plugin supports external thumbnail generation. Configure webhook URLs for:

- **Image Thumbnails**: AWS Lambda, Cloudinary, etc.
- **Video Previews**: AWS MediaConvert, FFmpeg service
- **Document Previews**: PDF thumbnail services

### CDN Integration (Recommended)

For better performance, set up CloudFront:

1. **Create CloudFront Distribution**
2. **Origin**: Your S3 bucket
3. **Update plugin settings** with CloudFront domain

## 🚨 Troubleshooting

### Connection Issues

**Error: "S3 connection failed"**
- ✅ Check AWS credentials are correct
- ✅ Verify bucket exists in specified region
- ✅ Confirm IAM user has required permissions
- ✅ Test S3 connection using plugin's built-in test button

**Error: "Access Denied"**
- ✅ Review bucket policy syntax
- ✅ Check IAM user policy
- ✅ Verify account ID in bucket policy

### Upload Issues

**Files not uploading**
- ✅ Check browser console for errors
- ✅ Verify CORS configuration
- ✅ Test with simple file first
- ✅ Check file size limits

**JavaScript errors**
- ✅ Ensure Uppy CDN loads properly
- ✅ Check for plugin conflicts
- ✅ Verify jQuery is available

### Performance Optimization

**Large file uploads**
- Consider multipart uploads for files >100MB
- Implement progress indicators
- Add upload resumption capability

**Bandwidth costs**
- Set up S3 lifecycle policies
- Use appropriate storage classes
- Consider CloudFront for file delivery

## 📊 Monitoring & Maintenance

### AWS CloudWatch

Monitor these metrics:
- **NumberOfObjects**: Total files stored
- **BucketSizeBytes**: Storage usage
- **AllRequests**: API request count

### WordPress Health Checks

The plugin provides health checks for:
- ✅ S3 connectivity
- ✅ Bucket permissions
- ✅ CORS configuration
- ✅ File upload functionality

### Backup Strategy

**Automated Backups:**
- Enable S3 versioning
- Set up cross-region replication
- Configure lifecycle policies

**Database Backups:**
- Include `cf7as_files` table in WordPress backups
- Maintain metadata consistency

## 🔄 Migration from Legacy System

If upgrading from the old file system:

1. **Backup existing files**
2. **Run migration script** (provided in plugin)
3. **Test S3 integration**
4. **Update forms to use Uppy fields**
5. **Verify file access in admin**

Migration preserves all existing submissions and metadata.

## 📞 Support

### Common Resources

- **AWS S3 Documentation**: https://docs.aws.amazon.com/s3/
- **Uppy Documentation**: https://uppy.io/docs/
- **WordPress REST API**: https://developer.wordpress.org/rest-api/

### Getting Help

For technical support:
1. **Check error logs**: `/wp-content/debug.log`
2. **Enable debug mode**: `define('WP_DEBUG', true);`
3. **Test S3 connection**: Use plugin's built-in test
4. **Contact support**: Include error messages and configuration details

---

**🎉 You're all set!** Your artist submission system now features modern file uploads, secure cloud storage, and professional file management capabilities.
