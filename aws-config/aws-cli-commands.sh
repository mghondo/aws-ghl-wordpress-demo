#!/bin/bash

# AWS S3 Bucket Setup Script for Clarity AWS GHL Demo
# Run these commands after configuring AWS CLI with appropriate credentials

# Configuration variables
BUCKET_NAME="clarity-aws-ghl-demo-storage"
REGION="us-east-1"
POLICY_FILE="s3-bucket-policy.json"

echo "Setting up AWS S3 bucket for Clarity AWS GHL WordPress integration..."

# Step 1: Create S3 bucket
echo "Creating S3 bucket: $BUCKET_NAME"
aws s3 mb s3://$BUCKET_NAME --region $REGION

# Step 2: Enable versioning
echo "Enabling versioning on bucket..."
aws s3api put-bucket-versioning \
    --bucket $BUCKET_NAME \
    --versioning-configuration Status=Enabled

# Step 3: Configure server-side encryption
echo "Configuring server-side encryption..."
aws s3api put-bucket-encryption \
    --bucket $BUCKET_NAME \
    --server-side-encryption-configuration '{
        "Rules": [
            {
                "ApplyServerSideEncryptionByDefault": {
                    "SSEAlgorithm": "AES256"
                }
            }
        ]
    }'

# Step 4: Block public access
echo "Blocking public access..."
aws s3api put-public-access-block \
    --bucket $BUCKET_NAME \
    --public-access-block-configuration \
        BlockPublicAcls=true,\
        IgnorePublicAcls=true,\
        BlockPublicPolicy=true,\
        RestrictPublicBuckets=true

# Step 5: Configure CORS for web uploads
echo "Configuring CORS..."
aws s3api put-bucket-cors \
    --bucket $BUCKET_NAME \
    --cors-configuration '{
        "CORSRules": [
            {
                "AllowedHeaders": ["*"],
                "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
                "AllowedOrigins": ["*"],
                "ExposeHeaders": ["ETag"],
                "MaxAgeSeconds": 3000
            }
        ]
    }'

# Step 6: Create folder structure
echo "Creating folder structure..."
aws s3api put-object --bucket $BUCKET_NAME --key uploads/
aws s3api put-object --bucket $BUCKET_NAME --key backups/
aws s3api put-object --bucket $BUCKET_NAME --key ghl-webhooks/
aws s3api put-object --bucket $BUCKET_NAME --key temp/
aws s3api put-object --bucket $BUCKET_NAME --key logs/

# Step 7: Create IAM user for WordPress application
echo "Creating IAM user for WordPress..."
aws iam create-user --user-name clarity-wordpress-s3-user

# Step 8: Attach policy to user
echo "Creating and attaching IAM policy..."
aws iam put-user-policy \
    --user-name clarity-wordpress-s3-user \
    --policy-name ClarityS3Access \
    --policy-document file://s3-iam-policy.json

# Step 9: Create access keys
echo "Creating access keys..."
aws iam create-access-key --user-name clarity-wordpress-s3-user > access-keys.json

echo "Setup complete!"
echo "IMPORTANT: Save the access keys from access-keys.json securely and add them to your WordPress environment."
echo "Bucket URL: https://$BUCKET_NAME.s3.$REGION.amazonaws.com"