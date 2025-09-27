# AWS S3 Integration Setup Guide

This guide will help you set up AWS S3 bucket integration for the Clarity WordPress theme.

## Prerequisites

- AWS Account with appropriate permissions
- AWS CLI installed and configured
- Composer (for AWS SDK installation)
- WordPress installation with Clarity theme

## Step 1: Install AWS SDK

```bash
# In your WordPress root directory
composer require aws/aws-sdk-php
```

## Step 2: Create AWS S3 Bucket

### Option A: Using AWS CLI (Recommended)

1. Make the setup script executable:
```bash
chmod +x aws-config/aws-cli-commands.sh
```

2. Run the setup script:
```bash
./aws-config/aws-cli-commands.sh
```

### Option B: Using AWS Console

1. Log into AWS Console
2. Navigate to S3 service
3. Create bucket with name: `clarity-aws-ghl-demo-storage`
4. Configure settings as specified in `s3-bucket-setup.json`

## Step 3: Configure IAM User

The setup script will create an IAM user with appropriate permissions. If doing manually:

1. Create IAM user: `clarity-wordpress-s3-user`
2. Attach the policy from `s3-iam-policy.json`
3. Generate access keys and save securely

## Step 4: Configure WordPress

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Edit `.env` with your AWS credentials:
```env
AWS_S3_BUCKET_NAME=clarity-aws-ghl-demo-storage
AWS_S3_REGION=us-east-1
AWS_S3_ACCESS_KEY_ID=your_access_key_here
AWS_S3_SECRET_ACCESS_KEY=your_secret_key_here
```

3. In WordPress admin, go to **Settings > AWS S3 Settings**
4. Enter your S3 configuration
5. Test the connection

## Step 5: Test Integration

Run the integration test:
```bash
php tests/s3-integration-test.php
```

## S3 Bucket Structure

The bucket will be organized as follows:

```
clarity-aws-ghl-demo-storage/
├── uploads/           # WordPress media uploads
│   ├── 2025/01/      # Year/month organization
│   └── 2025/02/
├── backups/          # Site backups
├── ghl-webhooks/     # GoHighLevel webhook data
├── temp/             # Temporary files
└── logs/             # Application logs
```

## Security Features

- **Private bucket**: No public access by default
- **Server-side encryption**: AES256 encryption enabled
- **Versioning**: File versioning enabled for backup
- **IAM policy**: Minimal required permissions
- **Presigned URLs**: Secure temporary access to files

## WordPress Integration Features

- **Automatic upload**: Media uploads automatically go to S3
- **Presigned URLs**: Secure file access without exposing keys
- **Local cleanup**: Option to delete local files after S3 upload
- **Admin interface**: Settings page for configuration
- **Connection testing**: Test S3 connectivity from admin
- **Error handling**: Comprehensive error logging

## Troubleshooting

### Connection Issues

1. Verify AWS credentials are correct
2. Check IAM user has required permissions
3. Ensure bucket exists and is in correct region
4. Check firewall/network connectivity

### Upload Issues

1. Verify file permissions on WordPress
2. Check allowed file types configuration
3. Review error logs in WordPress
4. Test with smaller files first

### Common Errors

**Access Denied**: Check IAM permissions
**Bucket Not Found**: Verify bucket name and region
**Invalid Credentials**: Check access key and secret key
**Network Timeout**: Check network connectivity

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `AWS_S3_BUCKET_NAME` | S3 bucket name | `clarity-aws-ghl-demo-storage` |
| `AWS_S3_REGION` | AWS region | `us-east-1` |
| `AWS_S3_ACCESS_KEY_ID` | AWS access key | - |
| `AWS_S3_SECRET_ACCESS_KEY` | AWS secret key | - |
| `AWS_S3_DELETE_LOCAL_FILES` | Delete local after upload | `false` |

## API Reference

### WordPress Functions

```php
// Upload file to S3
clarity_s3_upload_file($file_path, $s3_key, $metadata);

// Get secure download URL
clarity_s3_get_download_url($s3_key, $expires);

// Check if S3 is configured
clarity_s3_is_configured();

// Get bucket information
clarity_s3_get_bucket_info();
```

### Class Methods

```php
$s3 = new Clarity_AWS_S3_Integration();

// Upload file
$result = $s3->upload_to_s3($upload_data);

// Download file
$content = $s3->download_from_s3($s3_key);

// Delete file
$success = $s3->delete_from_s3($s3_key);

// List files
$files = $s3->list_s3_files($prefix);

// Generate presigned URL
$url = $s3->generate_presigned_url($s3_key, '+1 hour');
```

## Cost Optimization

- Enable lifecycle policies to automatically delete old files
- Use intelligent tiering for cost optimization
- Monitor usage with CloudWatch
- Set up billing alerts

## Next Steps

After S3 setup is complete:

1. Test file uploads through WordPress media library
2. Configure GoHighLevel webhook integration
3. Set up automated backups
4. Implement custom upload workflows

## Support

For issues with this integration:

1. Check the WordPress error logs
2. Review the S3 integration test results
3. Verify AWS console for bucket access
4. Check GitHub issues for known problems