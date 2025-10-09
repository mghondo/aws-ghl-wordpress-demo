<?php
/**
 * Integrations Class
 *
 * Connects existing S3 and GHL webhook components to the plugin
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Integrations {
    
    /**
     * S3 Integration instance
     */
    private $s3_integration;
    
    /**
     * GHL Webhook instance
     */
    private $ghl_webhook;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_integrations'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Initialize integrations
     */
    public function init_integrations() {
        $this->init_s3_integration();
        $this->init_ghl_webhook();
    }
    
    /**
     * Initialize S3 integration
     */
    private function init_s3_integration() {
        $bucket_name = get_option('clarity_s3_bucket_name');
        $region = get_option('clarity_s3_region');
        $access_key = get_option('clarity_s3_access_key');
        $secret_key = get_option('clarity_s3_secret_key');
        
        if ($bucket_name && $region && $access_key && $secret_key) {
            require_once CLARITY_AWS_GHL_PATH . 'includes/class-s3-integration.php';
            
            $this->s3_integration = new Clarity_AWS_S3_Integration(array(
                'bucket_name' => $bucket_name,
                'region' => $region,
                'access_key' => $access_key,
                'secret_key' => $secret_key,
                'delete_local' => get_option('clarity_s3_delete_local', false)
            ));
        }
    }
    
    /**
     * Initialize GHL webhook
     */
    private function init_ghl_webhook() {
        if (get_option('clarity_ghl_webhook_enabled', true)) {
            require_once CLARITY_AWS_GHL_PATH . 'includes/class-ghl-webhook.php';
            
            $this->ghl_webhook = new Clarity_GHL_Webhook(array(
                'webhook_secret' => get_option('clarity_ghl_webhook_secret', ''),
                'create_contacts' => get_option('clarity_ghl_create_contacts', true),
                'notification_email' => get_option('clarity_ghl_notification_email', ''),
                's3_integration' => $this->s3_integration,
                'database' => clarity_aws_ghl()->database
            ));
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        if ($this->ghl_webhook) {
            $this->ghl_webhook->register_rest_routes();
        }
    }
    
    /**
     * Get S3 integration instance
     */
    public function get_s3_integration() {
        return $this->s3_integration;
    }
    
    /**
     * Get GHL webhook instance
     */
    public function get_ghl_webhook() {
        return $this->ghl_webhook;
    }
    
    /**
     * Check if S3 is configured
     */
    public function is_s3_configured() {
        return !empty($this->s3_integration);
    }
    
    /**
     * Check if GHL webhook is configured
     */
    public function is_ghl_configured() {
        return !empty($this->ghl_webhook);
    }
}