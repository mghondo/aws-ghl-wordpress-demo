<?php
/**
 * Settings Pages
 *
 * Handles all settings pages for the plugin
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Settings will be initialized when called
    }
    
    /**
     * Render S3 settings page
     */
    public function render_s3_page() {
        if (isset($_POST['submit'])) {
            $this->save_s3_settings();
        }
        
        $bucket_name = get_option('clarity_s3_bucket_name', '');
        $region = get_option('clarity_s3_region', 'us-east-1');
        $access_key = get_option('clarity_s3_access_key', '');
        $secret_key = get_option('clarity_s3_secret_key', '');
        $delete_local = get_option('clarity_s3_delete_local', false);
        
        ?>
        <div class="wrap">
            <h1><?php _e('AWS S3 Settings', 'clarity-aws-ghl'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('clarity_s3_settings_nonce', 'clarity_s3_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="clarity_s3_bucket_name"><?php _e('Bucket Name', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="clarity_s3_bucket_name" name="clarity_s3_bucket_name" 
                                   value="<?php echo esc_attr($bucket_name); ?>" class="regular-text" required />
                            <p class="description"><?php _e('The name of your AWS S3 bucket', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_s3_region"><?php _e('AWS Region', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <select id="clarity_s3_region" name="clarity_s3_region">
                                <?php
                                $regions = array(
                                    'us-east-1' => 'US East (N. Virginia)',
                                    'us-east-2' => 'US East (Ohio)',
                                    'us-west-1' => 'US West (N. California)',
                                    'us-west-2' => 'US West (Oregon)',
                                    'eu-west-1' => 'Europe (Ireland)',
                                    'eu-west-2' => 'Europe (London)',
                                    'eu-central-1' => 'Europe (Frankfurt)',
                                    'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                    'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                    'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                                );
                                
                                foreach ($regions as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($region, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('The AWS region where your S3 bucket is located', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_s3_access_key"><?php _e('Access Key ID', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="clarity_s3_access_key" name="clarity_s3_access_key" 
                                   value="<?php echo esc_attr($access_key); ?>" class="regular-text" required />
                            <p class="description"><?php _e('Your AWS IAM user access key ID', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_s3_secret_key"><?php _e('Secret Access Key', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="clarity_s3_secret_key" name="clarity_s3_secret_key" 
                                   value="<?php echo esc_attr($secret_key); ?>" class="regular-text" required />
                            <p class="description"><?php _e('Your AWS IAM user secret access key', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_s3_delete_local"><?php _e('Delete Local Files', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="clarity_s3_delete_local" name="clarity_s3_delete_local" 
                                   value="1" <?php checked($delete_local, true); ?> />
                            <label for="clarity_s3_delete_local"><?php _e('Delete local files after successful S3 upload', 'clarity-aws-ghl'); ?></label>
                            <p class="description"><?php _e('WARNING: Enable this only if you are confident in your S3 setup', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="clarity-settings-actions">
                    <?php submit_button(__('Save S3 Settings', 'clarity-aws-ghl'), 'primary', 'submit'); ?>
                    
                    <button type="button" class="button" id="test-s3-connection">
                        <?php _e('Test S3 Connection', 'clarity-aws-ghl'); ?>
                    </button>
                </div>
                
                <div id="s3-test-result" style="margin-top: 15px;"></div>
            </form>
            
            <div class="clarity-settings-help">
                <h3><?php _e('S3 Setup Instructions', 'clarity-aws-ghl'); ?></h3>
                <ol>
                    <li><?php _e('Create an AWS S3 bucket in your preferred region', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Create an IAM user with S3 access permissions', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Generate access keys for the IAM user', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Enter the credentials above and test the connection', 'clarity-aws-ghl'); ?></li>
                </ol>
                
                <p>
                    <strong><?php _e('Need help?', 'clarity-aws-ghl'); ?></strong>
                    <a href="https://github.com/mghondo/aws-ghl-wordpress-demo/blob/main/aws-config/README.md" target="_blank">
                        <?php _e('View our S3 setup guide', 'clarity-aws-ghl'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render GHL settings page
     */
    public function render_ghl_page() {
        if (isset($_POST['submit'])) {
            $this->save_ghl_settings();
        }
        
        $webhook_secret = get_option('clarity_ghl_webhook_secret', '');
        $webhook_enabled = get_option('clarity_ghl_webhook_enabled', true);
        $create_contacts = get_option('clarity_ghl_create_contacts', true);
        $notification_email = get_option('clarity_ghl_notification_email', '');
        
        $webhook_url = rest_url('clarity-ghl/v1/webhook');
        
        ?>
        <div class="wrap">
            <h1><?php _e('GoHighLevel Settings', 'clarity-aws-ghl'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('clarity_ghl_settings_nonce', 'clarity_ghl_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="clarity_ghl_webhook_enabled"><?php _e('Enable Webhooks', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="clarity_ghl_webhook_enabled" name="clarity_ghl_webhook_enabled" 
                                   value="1" <?php checked($webhook_enabled, true); ?> />
                            <label for="clarity_ghl_webhook_enabled"><?php _e('Enable GoHighLevel webhook processing', 'clarity-aws-ghl'); ?></label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_ghl_webhook_secret"><?php _e('Webhook Secret', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="clarity_ghl_webhook_secret" name="clarity_ghl_webhook_secret" 
                                   value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" />
                            <p class="description"><?php _e('Optional: Secret key for webhook signature verification (recommended for security)', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_ghl_create_contacts"><?php _e('Create Contact Posts', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="clarity_ghl_create_contacts" name="clarity_ghl_create_contacts" 
                                   value="1" <?php checked($create_contacts, true); ?> />
                            <label for="clarity_ghl_create_contacts"><?php _e('Automatically create WordPress posts for new GHL contacts', 'clarity-aws-ghl'); ?></label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clarity_ghl_notification_email"><?php _e('Notification Email', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="clarity_ghl_notification_email" name="clarity_ghl_notification_email" 
                                   value="<?php echo esc_attr($notification_email); ?>" class="regular-text" />
                            <p class="description"><?php _e('Optional: Email address to receive notifications for important webhook events', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="clarity-settings-actions">
                    <?php submit_button(__('Save GHL Settings', 'clarity-aws-ghl'), 'primary', 'submit'); ?>
                    
                    <button type="button" class="button" id="test-webhook-endpoint">
                        <?php _e('Test Webhook Endpoint', 'clarity-aws-ghl'); ?>
                    </button>
                </div>
                
                <div id="webhook-test-result" style="margin-top: 15px;"></div>
            </form>
            
            <div class="clarity-webhook-info">
                <h3><?php _e('Webhook Configuration', 'clarity-aws-ghl'); ?></h3>
                
                <table class="widefat">
                    <tr>
                        <th><?php _e('Webhook URL:', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <input type="text" value="<?php echo esc_attr($webhook_url); ?>" readonly class="large-text" onclick="this.select();" />
                            <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_url); ?>')">
                                <?php _e('Copy', 'clarity-aws-ghl'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Method:', 'clarity-aws-ghl'); ?></th>
                        <td><code>POST</code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Content-Type:', 'clarity-aws-ghl'); ?></th>
                        <td><code>application/json</code></td>
                    </tr>
                    <?php if ($webhook_secret): ?>
                    <tr>
                        <th><?php _e('Signature Header:', 'clarity-aws-ghl'); ?></th>
                        <td><code>X-GHL-Signature</code></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="clarity-settings-help">
                <h3><?php _e('GoHighLevel Setup Instructions', 'clarity-aws-ghl'); ?></h3>
                <ol>
                    <li><?php _e('Log in to your GoHighLevel account', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Navigate to Settings > Integrations > Webhooks', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Add a new webhook endpoint using the URL above', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Configure the webhook secret (if using)', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Select the events you want to receive', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Test the webhook to ensure it\'s working', 'clarity-aws-ghl'); ?></li>
                </ol>
                
                <h4><?php _e('Supported Events', 'clarity-aws-ghl'); ?></h4>
                <ul>
                    <li><?php _e('Contact Created/Updated/Deleted', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Opportunity Created/Updated', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Form Submissions', 'clarity-aws-ghl'); ?></li>
                    <li><?php _e('Appointment Scheduled/Updated', 'clarity-aws-ghl'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save S3 settings
     */
    private function save_s3_settings() {
        if (!wp_verify_nonce($_POST['clarity_s3_settings_nonce'], 'clarity_s3_settings_nonce')) {
            wp_die(__('Security check failed', 'clarity-aws-ghl'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        update_option('clarity_s3_bucket_name', sanitize_text_field($_POST['clarity_s3_bucket_name']));
        update_option('clarity_s3_region', sanitize_text_field($_POST['clarity_s3_region']));
        update_option('clarity_s3_access_key', sanitize_text_field($_POST['clarity_s3_access_key']));
        update_option('clarity_s3_secret_key', sanitize_text_field($_POST['clarity_s3_secret_key']));
        update_option('clarity_s3_delete_local', isset($_POST['clarity_s3_delete_local']));
        
        add_settings_error(
            'clarity_s3_settings',
            'settings_saved',
            __('S3 settings saved successfully.', 'clarity-aws-ghl'),
            'success'
        );
        
        settings_errors('clarity_s3_settings');
    }
    
    /**
     * Save GHL settings
     */
    private function save_ghl_settings() {
        if (!wp_verify_nonce($_POST['clarity_ghl_settings_nonce'], 'clarity_ghl_settings_nonce')) {
            wp_die(__('Security check failed', 'clarity-aws-ghl'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        update_option('clarity_ghl_webhook_enabled', isset($_POST['clarity_ghl_webhook_enabled']));
        update_option('clarity_ghl_webhook_secret', sanitize_text_field($_POST['clarity_ghl_webhook_secret']));
        update_option('clarity_ghl_create_contacts', isset($_POST['clarity_ghl_create_contacts']));
        update_option('clarity_ghl_notification_email', sanitize_email($_POST['clarity_ghl_notification_email']));
        
        add_settings_error(
            'clarity_ghl_settings',
            'settings_saved',
            __('GoHighLevel settings saved successfully.', 'clarity-aws-ghl'),
            'success'
        );
        
        settings_errors('clarity_ghl_settings');
    }
}