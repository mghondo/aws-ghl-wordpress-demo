=== Clarity AWS GoHighLevel Integration ===
Contributors: clarity
Tags: gohighlevel, aws, s3, webhooks, crm
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later

Complete WordPress plugin for integrating GoHighLevel CRM webhooks with AWS S3 storage.

== Description ==

The Clarity AWS GoHighLevel Integration plugin provides a complete solution for receiving GoHighLevel webhook events and storing them securely in AWS S3. This plugin bridges your WordPress site with your GoHighLevel CRM system, enabling automated data synchronization and storage.

**Key Features:**

* **Webhook Endpoint**: Secure REST API endpoint for GoHighLevel webhooks
* **AWS S3 Integration**: Automatic upload of webhook data to S3 storage
* **Contact Management**: Create WordPress posts for GHL contacts automatically
* **Admin Dashboard**: Real-time statistics and system overview
* **Webhook Logging**: Comprehensive logging with filtering and export
* **Security**: HMAC signature verification for webhook security
* **Custom Post Types**: Dedicated post types for GHL contacts and opportunities

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clarity-aws-ghl-plugin/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure AWS S3 credentials in AWS GHL > S3 Settings
4. Set up GoHighLevel webhook settings in AWS GHL > GHL Settings
5. Use the webhook URL provided in your GoHighLevel integration

== Configuration ==

**AWS S3 Setup:**
1. Create an S3 bucket in your preferred AWS region
2. Create IAM user with S3 access permissions
3. Enter credentials in plugin settings
4. Test connection to verify setup

**GoHighLevel Setup:**
1. Copy webhook URL from plugin settings
2. Add webhook endpoint in your GHL account
3. Configure webhook secret for security (recommended)
4. Select events you want to receive

== Webhook URL ==

Your webhook endpoint will be:
`https://yoursite.com/wp-json/clarity-ghl/v1/webhook`

== Supported Events ==

* Contact Created/Updated/Deleted
* Opportunity Created/Updated
* Form Submissions  
* Appointment Scheduled/Updated
* Custom Events

== Database Tables ==

The plugin creates three database tables:
* `wp_clarity_webhook_logs` - Webhook activity logs
* `wp_clarity_ghl_contacts` - Contact synchronization data
* `wp_clarity_s3_files` - S3 file upload tracking

== File Structure ==

```
clarity-aws-ghl-plugin/
├── clarity-aws-ghl-integration.php (Main plugin file)
├── admin/
│   ├── class-admin.php (Admin interface)
│   ├── class-dashboard.php (Dashboard widgets)
│   ├── class-settings.php (Settings pages)
│   ├── class-logs.php (Log management)
│   ├── css/admin.css (Admin styles)
│   └── js/admin.js (Admin JavaScript)
└── includes/
    ├── class-database.php (Database operations)
    ├── class-post-types.php (Custom post types)
    ├── class-integrations.php (Integration hooks)
    ├── class-s3-integration.php (AWS S3 handler)
    └── class-ghl-webhook.php (Webhook processor)
```

== Security ==

* HMAC-SHA256 signature verification
* Nonce verification for admin actions
* Sanitized input validation
* Secure credential storage
* Private S3 bucket access

== Requirements ==

* WordPress 5.0+
* PHP 7.4+
* cURL extension
* Valid AWS S3 account
* GoHighLevel account with webhook access

== Changelog ==

= 1.0.0 =
* Initial release
* AWS S3 integration
* GoHighLevel webhook processing
* Admin dashboard and settings
* Custom post types for contacts
* Comprehensive webhook logging

== Support ==

For support and documentation, visit:
https://github.com/mghondo/aws-ghl-wordpress-demo