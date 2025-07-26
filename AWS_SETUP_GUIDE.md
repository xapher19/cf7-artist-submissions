# AWS Lambda and MediaConvert Setup Guide

This guide will walk you through setting up AWS Lambda and MediaConvert services to enable automated media conversion for the CF7 Artist Submissions plugin.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [AWS IAM Setup](#aws-iam-setup)
3. [S3 Bucket Configuration](#s3-bucket-configuration)
4. [Lambda Function Setup](#lambda-function-setup)
5. [MediaConvert Setup](#mediaconvert-setup)
6. [WordPress Plugin Configuration](#wordpress-plugin-configuration)
7. [Testing the Setup](#testing-the-setup)
8. [Troubleshooting](#troubleshooting)

## Prerequisites

Before starting, ensure you have:

- An AWS account with administrative access
- AWS CLI installed and configured (optional but recommended)
- Node.js 18.x or later (for Lambda function development - **only needed on your local development machine**)
- Your WordPress site with CF7 Artist Submissions plugin installed

**Important Note:** The Node.js code in this guide is **only** for creating the AWS Lambda function. The Lambda function runs entirely on AWS servers, not your web server. Your WordPress hosting environment doesn't need to support Node.js at all - it only needs to support PHP as usual.

## AWS IAM Setup

### 1. Create IAM User for WordPress Plugin

1. **Log into AWS Console** → IAM → Users → Create User
2. **User Details:**
   - Username: `cf7-artist-submissions`
   - Access type: Programmatic access
3. **Create Custom Policy** (see below)
4. **Attach the custom policy** to your user
5. **Generate Access Keys** and save securely

### 2. Create Custom IAM Policy

Create a custom policy named `CF7ArtistSubmissionsPolicy` with comprehensive permissions for S3, Lambda, and MediaConvert:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "S3BucketAccess",
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
        },
        {
            "Sid": "LambdaInvocation",
            "Effect": "Allow",
            "Action": [
                "lambda:InvokeFunction",
                "lambda:GetFunction"
            ],
            "Resource": "arn:aws:lambda:*:*:function:cf7as-*"
        },
        {
            "Sid": "LambdaManagement",
            "Effect": "Allow",
            "Action": [
                "lambda:CreateFunction",
                "lambda:UpdateFunctionCode",
                "lambda:UpdateFunctionConfiguration",
                "lambda:DeleteFunction",
                "lambda:GetFunction",
                "lambda:ListFunctions",
                "lambda:InvokeFunction"
            ],
            "Resource": "arn:aws:lambda:*:*:function:cf7as-*"
        },
        {
            "Sid": "IAMRoleAccess",
            "Effect": "Allow",
            "Action": [
                "iam:GetRole",
                "iam:PassRole"
            ],
            "Resource": [
                "arn:aws:iam::*:role/CF7AS-Lambda-Execution-Role",
                "arn:aws:iam::*:role/CF7AS-MediaConvert-Role"
            ]
        },
        {
            "Sid": "MediaConvertAccess",
            "Effect": "Allow",
            "Action": [
                "mediaconvert:CreateJob",
                "mediaconvert:GetJob",
                "mediaconvert:ListJobs",
                "mediaconvert:CancelJob",
                "mediaconvert:DescribeEndpoints"
            ],
            "Resource": "*"
        },
        {
            "Sid": "PassRoleToMediaConvert",
            "Effect": "Allow",
            "Action": [
                "iam:PassRole"
            ],
            "Resource": "arn:aws:iam::*:role/CF7AS-MediaConvert-Role"
        }
    ]
}
```

**Important:** Replace `your-bucket-name` with your actual S3 bucket name before creating the policy.

### 3. Create MediaConvert Service Role

MediaConvert needs a service role to access your S3 bucket. Here's how to create it:

1. **Navigate to IAM Console:**
   - Go to AWS Console → Search for "IAM" → Click "IAM"
   - In the left sidebar, click **"Roles"**
   - Click the **"Create role"** button

2. **Select Service Type:**
   - Under "Trusted entity type", select **"AWS service"**
   - Under "Use case", scroll down and find **"MediaConvert"**
   - Select **"MediaConvert"** and click **"Next"**

3. **Attach Permissions:**
   - Search for and select these policies (check the boxes):
     - `AmazonS3FullAccess` (allows MediaConvert to read/write S3 files)
     - `AmazonAPIGatewayInvokeFullAccess` (for WordPress callbacks)
   - Click **"Next"**

4. **Name and Create Role:**
   - **Role name:** `CF7AS-MediaConvert-Role`
   - **Description:** "Service role for CF7 Artist Submissions MediaConvert jobs"
   - Click **"Create role"**

5. **Copy the Role ARN:**
   - After creation, click on your new role name
   - Copy the **Role ARN** (looks like: `arn:aws:iam::123456789012:role/CF7AS-MediaConvert-Role`)
   - You'll need this ARN for the plugin configuration

## S3 Bucket Configuration

### 1. Create S3 Bucket

1. **Navigate to S3** in AWS Console
2. **Create bucket** with unique name (e.g., `my-artist-submissions-2025`)
3. **Choose region** closest to your users for best performance
4. **Keep default security settings** (Block Public Access enabled)

Or using AWS CLI:
```bash
# Using AWS CLI (replace 'your-bucket-name' with your actual bucket)
aws s3 mb s3://your-bucket-name --region eu-west-1
```

### 2. Set Bucket Policy (Optional but Recommended)

Replace `your-bucket-name` and `your-account-id` with your actual values:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "AllowCF7ASAccess",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::your-account-id:user/cf7-artist-submissions"
            },
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
        },
        {
            "Sid": "AllowMediaConvertAccess",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::your-account-id:role/CF7AS-MediaConvert-Role"
            },
            "Action": [
                "s3:GetObject",
                "s3:PutObject"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

**Note:** The plugin works with the IAM user permissions alone. The bucket policy provides an additional layer of security but is not required if your IAM policy is configured correctly.

### 3. Enable CORS (if needed for direct uploads)

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
        "AllowedOrigins": ["https://yourdomain.com"],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3000
    }
]
```

## Lambda Function Setup

**Important:** This section involves creating a Node.js function that will run on AWS Lambda servers, not your web server. You only need Node.js installed on your local development machine (laptop/desktop) to create and package the function. Once deployed, it runs entirely on AWS infrastructure.

**Note:** The Lambda function code has already been created in the `lambda-functions/cf7as-image-converter/` directory of this project.

### 1. Navigate to Lambda Function Directory

```bash
cd lambda-functions/cf7as-image-converter
```

### 2. Install Dependencies

```bash
npm install
```

### 3. Lambda Function Code

The main Lambda function code is in `index.js` and includes:

The main Lambda function code is in `index.js` and includes:

- **Image processing** with Sharp library (WebP, JPEG, PNG conversion)
- **Multiple preset support** (thumbnail, medium, large sizes)
- **WordPress callback system** for job status updates
- **Comprehensive error handling** and logging
- **Test mode** for connection verification

See the complete code in `lambda-functions/cf7as-image-converter/index.js`.

### 4. Configure AWS CLI (if not already done)

Before deploying, ensure AWS CLI is configured with your credentials:

```bash
# Configure AWS CLI with your credentials
aws configure

# You'll be prompted to enter:
# - AWS Access Key ID: (from your IAM user)
# - AWS Secret Access Key: (from your IAM user)  
# - Default region name: eu-west-1
# - Default output format: json
```

### 5. Create Lambda Execution Role

**Important:** This role needs to be created through the AWS Console since your IAM user doesn't have role creation permissions.

1. **Navigate to IAM Console:**
   - Go to AWS Console → Search for "IAM" → Click "IAM"
   - In the left sidebar, click **"Roles"**
   - Click the **"Create role"** button

2. **Select Service Type:**
   - Under "Trusted entity type", select **"AWS service"**
   - Under "Use case", select **"Lambda"**
   - Click **"Next"**

3. **Attach Permissions:**
   - Search for and select these policies (check the boxes):
     - `AWSLambdaBasicExecutionRole` (for CloudWatch logging)
     - `AmazonS3FullAccess` (for S3 file access)
   - Click **"Next"**

4. **Name and Create Role:**
   - **Role name:** `CF7AS-Lambda-Execution-Role`
   - **Description:** "Execution role for CF7AS Lambda functions"
   - Click **"Create role"**

### 6. Package and Deploy Lambda Function

```bash
# Package the function
npm run package

# Deploy for the first time
npm run deploy-create

# The deploy-create script will automatically use your AWS Account ID
```

### 7. For Future Updates

After the initial deployment, use this command to update the function code:

```bash
# Package and update existing function
npm run package
npm run deploy-update
```

## MediaConvert Setup

### 1. Activate MediaConvert Service

**Important:** MediaConvert IS included in the AWS free tier (first 20 minutes of HD transcoding per month), but it requires explicit activation that sometimes doesn't work through the normal console flow.

**If you're getting "subscription required" errors even after trying console activation:**

**Option A: AWS Support (Fastest)**
1. **Go to AWS Support Center**: https://console.aws.amazon.com/support/
2. **Create a case** → **Service Limit Increase**
3. **Service**: AWS Elemental MediaConvert
4. **Request**: "Please activate AWS Elemental MediaConvert for my account in eu-west-1 region"
5. **Use case**: "Setting up media processing for web application"
6. **AWS Support usually activates this within 1-2 hours**

**Option B: Alternative Console Method**
1. **Go to AWS Console** → Make sure you're in **EU (Ireland) eu-west-1** region
2. **Go to Billing & Cost Management** → **Preferences**
3. **Look for "AWS Elemental MediaConvert"** in services
4. **Or try Cost Explorer** → **Service costs** → Look for MediaConvert

**Option C: Skip MediaConvert for Now**
- **Your Lambda function handles all image processing perfectly**
- **MediaConvert is only needed for video processing**
- **You can add it later when you need video features**

**Free Tier Includes:**
- First 20 minutes of HD video transcoding per month (FREE)
- After that: ~$0.015 per minute of HD video
- For most users, this covers typical usage at no cost

### 2. Get MediaConvert Endpoint

**After activation** (you should see the MediaConvert job creation interface), run this command to get your account-specific endpoint:

```bash
aws mediaconvert describe-endpoints --region eu-west-1
```

The response will include your account-specific endpoint like:
`https://1234567890.mediaconvert.eu-west-1.amazonaws.com`

**Troubleshooting:**
- If you still get "SubscriptionRequiredException": Go back to AWS Console → MediaConvert and look for any "Enable" or "Subscribe" buttons
- Make sure you're in the **eu-west-1** region in both console and CLI
- The service activation can take 2-3 minutes to propagate to API access

### 2. Create MediaConvert Job Template (Optional)

Create a job template for consistent video processing:

```json
{
    "Name": "CF7AS-Video-Web-Template",
    "Description": "CF7 Artist Submissions video conversion template",
    "Settings": {
        "OutputGroups": [
            {
                "Name": "File Group",
                "OutputGroupSettings": {
                    "Type": "FILE_GROUP_SETTINGS",
                    "FileGroupSettings": {}
                },
                "Outputs": [
                    {
                        "NameModifier": "_web",
                        "VideoDescription": {
                            "Width": 1280,
                            "Height": 720,
                            "CodecSettings": {
                                "Codec": "H_264",
                                "H264Settings": {
                                    "RateControlMode": "CBR",
                                    "Bitrate": 2000000
                                }
                            }
                        },
                        "AudioDescriptions": [
                            {
                                "CodecSettings": {
                                    "Codec": "AAC",
                                    "AacSettings": {
                                        "Bitrate": 128000,
                                        "SampleRate": 48000
                                    }
                                }
                            }
                        ],
                        "ContainerSettings": {
                            "Container": "MP4"
                        }
                    }
                ]
            }
        ],
        "TimecodeConfig": {
            "Source": "ZEROBASED"
        }
    }
}
```

## WordPress Plugin Configuration

### 1. Configure AWS Settings

In your WordPress admin:

1. **Go to:** CF7 Artist Submissions → Settings → General
2. **Fill in AWS Configuration:**
   - **AWS Access Key:** `AKIA...` (from IAM user)
   - **AWS Secret Key:** `...` (from IAM user)
   - **AWS Region:** `eu-west-1` (to match your S3 bucket location)
   - **S3 Bucket:** `your-bucket-name`
   - **Lambda Function Name:** `cf7as-image-converter`
   - **MediaConvert Endpoint:** `https://1234567890.mediaconvert.eu-west-1.amazonaws.com`

### 2. Enable Media Conversion

1. **Check:** "Enable Media Conversion"
2. **Save** settings

### 3. Test Connection

1. Click **"Test S3 Connection"** - Should show success
2. Click **"Test Lambda Connection"** - Should show success
3. Click **"Test Media Conversion"** - Should process test file

## Testing the Setup

### 1. Upload Test File

1. Create a test Contact Form 7 form with your uploader field
2. Upload a test image (JPEG/PNG)
3. Check WordPress error logs for processing messages

### 2. Verify S3 Structure

Your S3 bucket should have:
```
uploads/
  └── [submission-id]/
      ├── original-file.jpg
      └── converted/
          ├── original-file_thumb.webp
          ├── original-file_medium.webp
          └── original-file_large.webp
```

### 3. Check Database

Verify conversion jobs in database:
```sql
SELECT * FROM wp_cf7as_conversion_jobs ORDER BY created_at DESC LIMIT 5;
SELECT * FROM wp_cf7as_converted_files ORDER BY created_at DESC LIMIT 10;
```

## Troubleshooting

### Common Issues

#### 1. Lambda Function Errors
```bash
# Check Lambda logs
aws logs describe-log-groups --log-group-name-prefix /aws/lambda/cf7as-image-converter
aws logs get-log-events --log-group-name /aws/lambda/cf7as-image-converter --log-stream-name [STREAM-NAME]
```

#### 2. S3 Permission Errors
- Verify IAM policies
- Check bucket policies
- Ensure CORS is configured for direct uploads

#### 3. MediaConvert Errors
- Verify service role has S3 access
- Check input file formats are supported
- Ensure output S3 paths are valid

#### 4. WordPress Integration Issues
- Enable WordPress debug logging
- Check PHP error logs
- Verify AWS credentials in plugin settings

### Debug Commands

```bash
# Test S3 access
aws s3 ls s3://your-bucket-name --region eu-west-1

# Test Lambda function
aws lambda invoke \
    --function-name cf7as-image-converter \
    --payload '{"test": true}' \
    response.json && cat response.json

# Check MediaConvert endpoint
aws mediaconvert describe-endpoints --region eu-west-1
```

### Performance Optimization

1. **Lambda Memory:** Increase to 1024MB+ for faster image processing
2. **Lambda Timeout:** Set to 5+ minutes for large files
3. **S3 Transfer Acceleration:** Enable for faster uploads
4. **CloudFront:** Add CDN for faster delivery of converted files

## Cost Optimization

- **Lambda:** ~$0.0000166667 per GB-second
- **MediaConvert:** ~$0.0075 per minute of video processed
- **S3:** ~$0.023 per GB stored per month
- **Data Transfer:** First 1 GB/month free, then ~$0.09 per GB

### Estimated Monthly Costs (100 files/month):
- **Lambda:** ~$5-10
- **MediaConvert:** ~$10-20 (if processing videos)
- **S3:** ~$5-15 (depending on storage)
- **Total:** ~$20-45/month

## Security Best Practices

1. **Use least-privilege IAM policies**
2. **Enable S3 bucket versioning and encryption**
3. **Set up CloudTrail for auditing**
4. **Use VPC endpoints for private communication**
5. **Regularly rotate AWS access keys**
6. **Monitor costs and usage**

## Support

For issues specific to this setup:
1. Check AWS CloudWatch logs
2. Enable WordPress debug logging
3. Review plugin error logs
4. Test individual components separately

For AWS service issues, consult AWS documentation and support.
