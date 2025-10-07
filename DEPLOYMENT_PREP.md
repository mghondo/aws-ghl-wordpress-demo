# Deployment Preparation Checklist

## 1. Environment Configuration

### Create .env file
Copy `.env.example` to `.env` and update with production values:
```bash
cp .env.example .env
```

**Required Updates:**
- [ ] AWS_S3_ACCESS_KEY_ID - Use production AWS credentials
- [ ] AWS_S3_SECRET_ACCESS_KEY - Use production AWS credentials  
- [ ] GHL_WEBHOOK_SECRET - Set actual webhook secret
- [ ] WP_DEBUG - Set to `false` for production
- [ ] WP_DEBUG_LOG - Set to `false` for production

## 2. Initial Setup Files (RUN FIRST, THEN REMOVE)

### Setup files that MUST be run once on production:
These files create essential database tables and pages. Run them in this order after deployment:

```bash
# 1. Create database tables for courses
php create-course-tables.php

# 2. Create WordPress pages with correct templates
php setup-course-pages.php
php create-dashboard-page.php
php create-register-page.php

# 3. Flush rewrite rules for permalinks
php flush-rewrite-rules.php
```

### Files to remove IMMEDIATELY after running setup:
```bash
# Remove ALL setup files after successful initialization
rm create-course-tables.php
rm setup-course-pages.php
rm create-dashboard-page.php
rm create-register-page.php
rm setup-funnel.php
rm update-course-images.php
rm flush-rewrite-rules.php

# Database check/fix utilities (remove after confirming DB is correct)
rm check-db-structure.php
rm check-users.php
rm fix-database.php
rm verify-pages.php
```

### Debug/Test files to remove BEFORE deployment:
These are development-only files that should never go to production:

```bash
# Debug files - expose internal data, security risk
rm debug-certificate.php
rm debug-checkout.php
rm debug-funnel.php
rm debug-save.php

# Test files - bypass security, not needed in production
rm test-auth-flow.php
rm test-course-routing.php
rm test-dashboard.php
rm test-header-auth.php
rm test-image-upload.html
rm test-assets.html
```

## 3. Database Setup

### Export local database:
```bash
# If using local MySQL/MariaDB
mysqldump -u [username] -p [database_name] > database_backup.sql
```

### Import to production:
1. Create database on hosting provider
2. Import SQL file through phpMyAdmin or command line
3. Update `wp-config.php` with production database credentials

## 4. WordPress Configuration

### Update wp-config.php:
```php
// Production settings
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('DISALLOW_FILE_EDIT', true);

// Update database credentials
define('DB_NAME', 'production_db_name');
define('DB_USER', 'production_db_user');
define('DB_PASSWORD', 'production_db_password');
define('DB_HOST', 'production_db_host');

// Set production URL
define('WP_HOME', 'https://yourdomain.com');
define('WP_SITEURL', 'https://yourdomain.com');
```

## 5. AWS Lambda Functions

### Deploy Lambda functions:
1. Navigate to lambda directory
2. Create deployment packages:
```bash
cd lambda
zip -r certificate-generator-deploy.zip handler.py templates/ requirements.txt
```

3. Upload to AWS Lambda:
   - Create new Lambda function in AWS Console
   - Upload zip file
   - Set handler to `handler.lambda_handler`
   - Configure environment variables
   - Set up API Gateway trigger

## 6. File Permissions

Set correct permissions after upload:
```bash
# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# WordPress upload directory
chmod -R 775 wp-content/uploads
```

## 7. SSL Certificate

- Enable SSL through hosting provider
- Update all URLs to use HTTPS
- Add SSL redirect in .htaccess

## 8. Plugin Installation

Required WordPress plugins:
1. Upload and activate the `clarity-aws-ghl-plugin`
2. Configure plugin settings with AWS and GHL credentials

## 9. Final Checks

- [ ] Test all forms and submissions
- [ ] Verify AWS S3 integration works
- [ ] Test GoHighLevel webhook integration
- [ ] Check certificate generation
- [ ] Verify user registration/login flow
- [ ] Test course access and navigation
- [ ] Check all images load from S3
- [ ] Verify SSL is working properly

## 10. Backup Strategy

Set up regular backups:
- Database backups (daily)
- File backups (weekly)
- S3 bucket versioning enabled

## Deployment Commands Summary

```bash
# 1. Remove debug/test files BEFORE deployment
rm debug-*.php test-*.php test-*.html

# 2. Create production .env
cp .env.example .env
# Edit .env with production values

# 3. Export database (if you have existing data to migrate)
mysqldump -u [user] -p [database] > backup.sql

# 4. Prepare Lambda functions
cd lambda
zip -r certificate-generator.zip handler.py templates/ requirements.txt

# 5. Upload ALL files to hosting (including setup files)
# Use FTP/SFTP or hosting provider's file manager

# 6. Import database on production (if migrating data)
# Use phpMyAdmin or MySQL command line

# 7. Run setup scripts ON PRODUCTION (in order):
php create-course-tables.php
php setup-course-pages.php
php create-dashboard-page.php  
php create-register-page.php
php flush-rewrite-rules.php

# 8. Remove setup files after successful initialization
rm create-*.php setup-*.php check-*.php fix-*.php verify-*.php update-*.php flush-*.php

# 9. Set permissions
chmod -R 755 .
chmod -R 775 wp-content/uploads

# 10. Test everything!
```

## Hosting Requirements

Minimum requirements:
- PHP 7.4 or higher
- MySQL 5.7 or higher
- HTTPS/SSL certificate
- PHP extensions: curl, json, mysqli, gd
- Write permissions for uploads directory
- Cron job support (for scheduled tasks)