<?php
/**
 * GoHighLevel Webhook Handler
 *
 * Core webhook functionality for receiving and processing GHL data
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clarity_GHL_Webhook {
    
    private $webhook_secret;
    private $s3_integration;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->webhook_secret = get_option('clarity_ghl_webhook_secret', '');
        $this->s3_integration = new Clarity_AWS_S3_Integration();
        
        // Register REST API endpoint
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'register_webhook_settings'));
    }
    
    /**
     * Register webhook REST API endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('clarity-ghl/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array()
        ));
    }
    
    /**
     * Handle incoming webhook request
     */
    public function handle_webhook($request) {
        $start_time = microtime(true);
        
        try {
            // Get raw body and headers
            $raw_body = $request->get_body();
            $headers = $request->get_headers();
            
            $this->log_webhook('Webhook received', array(
                'method' => $request->get_method(),
                'content_type' => $request->get_content_type(),
                'body_size' => strlen($raw_body)
            ));
            
            // Validate content type
            if (!$this->is_valid_content_type($request->get_content_type())) {
                return $this->error_response(400, 'Invalid content type. Expected application/json');
            }
            
            // Verify webhook signature if secret is configured
            if (!empty($this->webhook_secret)) {
                if (!$this->verify_signature($raw_body, $headers)) {
                    $this->log_webhook('Signature verification failed', array('headers' => $headers));
                    return $this->error_response(401, 'Signature verification failed');
                }
                $this->log_webhook('Signature verification passed');
            } else {
                $this->log_webhook('No webhook secret configured - skipping signature verification');
            }
            
            // Parse JSON data
            $webhook_data = json_decode($raw_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->error_response(400, 'Invalid JSON: ' . json_last_error_msg());
            }
            
            // Process webhook data
            $result = $this->process_webhook_data($webhook_data, $headers);
            
            if ($result['success']) {
                $processing_time = round((microtime(true) - $start_time) * 1000, 2);
                
                $this->log_webhook('Webhook processed successfully', array(
                    'processing_time_ms' => $processing_time,
                    's3_key' => $result['s3_key'],
                    'event_type' => $result['event_type']
                ));
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                    'processing_time_ms' => $processing_time,
                    's3_key' => $result['s3_key'],
                    'timestamp' => current_time('c')
                ), 200);
            } else {
                return $this->error_response(500, 'Failed to process webhook: ' . $result['error']);
            }
            
        } catch (Exception $e) {
            $this->log_webhook('Webhook processing error: ' . $e->getMessage(), array(), 'error');
            return $this->error_response(500, 'Internal server error');
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_signature($raw_body, $headers) {
        // Check for signature in headers (common GHL patterns)
        $signature_header = null;
        
        // Try different header formats
        if (isset($headers['x_ghl_signature'])) {
            $signature_header = $headers['x_ghl_signature'][0];
        } elseif (isset($headers['x_gohighlevel_signature'])) {
            $signature_header = $headers['x_gohighlevel_signature'][0];
        } elseif (isset($headers['x_hub_signature_256'])) {
            $signature_header = $headers['x_hub_signature_256'][0];
        }
        
        if (!$signature_header) {
            return false;
        }
        
        // Extract signature (remove sha256= prefix if present)
        $signature = str_replace('sha256=', '', $signature_header);
        
        // Calculate expected signature
        $expected_signature = hash_hmac('sha256', $raw_body, $this->webhook_secret);
        
        // Secure comparison
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Process webhook data and store in S3
     */
    private function process_webhook_data($data, $headers) {
        try {
            // Extract event type
            $event_type = $this->extract_event_type($data);
            
            // Prepare data for storage
            $storage_data = array(
                'timestamp' => current_time('c'),
                'event_type' => $event_type,
                'headers' => $this->sanitize_headers($headers),
                'data' => $data,
                'metadata' => array(
                    'source' => 'gohighlevel',
                    'endpoint' => '/wp-json/clarity-ghl/v1/webhook',
                    'wordpress_version' => get_bloginfo('version'),
                    'theme_version' => wp_get_theme()->get('Version')
                )
            );
            
            // Generate S3 key
            $s3_key = $this->generate_s3_key($event_type);
            
            // Store in S3
            $s3_result = $this->store_webhook_in_s3($storage_data, $s3_key);
            
            if ($s3_result) {
                return array(
                    'success' => true,
                    's3_key' => $s3_key,
                    'event_type' => $event_type
                );
            } else {
                return array(
                    'success' => false,
                    'error' => 'Failed to store data in S3'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Extract event type from webhook data
     */
    private function extract_event_type($data) {
        // Common GHL event type patterns
        if (isset($data['event'])) {
            return sanitize_text_field($data['event']);
        } elseif (isset($data['type'])) {
            return sanitize_text_field($data['type']);
        } elseif (isset($data['eventType'])) {
            return sanitize_text_field($data['eventType']);
        } elseif (isset($data['action'])) {
            return sanitize_text_field($data['action']);
        }
        
        return 'unknown';
    }
    
    /**
     * Generate S3 key for webhook data
     */
    private function generate_s3_key($event_type) {
        $date = current_time('Y/m/d');
        $timestamp = current_time('His');
        $random = wp_generate_password(8, false);
        
        return "ghl-webhooks/{$date}/{$event_type}-{$timestamp}-{$random}.json";
    }
    
    /**
     * Store webhook data in S3
     */
    private function store_webhook_in_s3($data, $s3_key) {
        // Create temporary file
        $temp_file = wp_tempnam('ghl_webhook_');
        
        if (!$temp_file) {
            return false;
        }
        
        // Write JSON data to temp file
        $json_data = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($temp_file, $json_data);
        
        // Upload to S3 using existing integration
        $upload_result = $this->s3_integration->upload_to_s3(array(
            'file' => $temp_file,
            's3_key' => $s3_key
        ));
        
        // Clean up temp file
        unlink($temp_file);
        
        return isset($upload_result['s3_url']);
    }
    
    /**
     * Sanitize headers for logging
     */
    private function sanitize_headers($headers) {
        $sanitized = array();
        
        // Include only safe headers
        $safe_headers = array(
            'content_type', 'content_length', 'user_agent',
            'x_forwarded_for', 'x_real_ip', 'accept'
        );
        
        foreach ($safe_headers as $header) {
            if (isset($headers[$header])) {
                $sanitized[$header] = is_array($headers[$header]) ? $headers[$header][0] : $headers[$header];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate content type
     */
    private function is_valid_content_type($content_type) {
        $valid_types = array(
            'application/json',
            'application/json; charset=utf-8',
            'text/json'
        );
        
        return in_array(strtolower($content_type), $valid_types);
    }
    
    /**
     * Log webhook activity
     */
    private function log_webhook($message, $data = array(), $level = 'info') {
        $log_entry = array(
            'timestamp' => current_time('c'),
            'level' => $level,
            'message' => $message,
            'data' => $data
        );
        
        // Log to WordPress debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Clarity GHL Webhook] ' . $message . (!empty($data) ? ' | Data: ' . wp_json_encode($data) : ''));
        }
        
        // Store in option for simple logging (last 100 entries)
        $logs = get_option('clarity_ghl_webhook_logs', array());
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100); // Keep last 100 entries
        update_option('clarity_ghl_webhook_logs', $logs);
    }
    
    /**
     * Return error response
     */
    private function error_response($code, $message) {
        return new WP_REST_Response(array(
            'success' => false,
            'error' => $message,
            'timestamp' => current_time('c')
        ), $code);
    }
    
    /**
     * Register webhook settings
     */
    public function register_webhook_settings() {
        register_setting('clarity_ghl_settings', 'clarity_ghl_webhook_secret');
        register_setting('clarity_ghl_settings', 'clarity_ghl_webhook_enabled');
    }
    
    /**
     * Get webhook endpoint URL
     */
    public function get_webhook_url() {
        return rest_url('clarity-ghl/v1/webhook');
    }
    
    /**
     * Get webhook logs
     */
    public function get_webhook_logs($limit = 20) {
        $logs = get_option('clarity_ghl_webhook_logs', array());
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Clear webhook logs
     */
    public function clear_webhook_logs() {
        return delete_option('clarity_ghl_webhook_logs');
    }
}

// Initialize the GHL webhook handler
new Clarity_GHL_Webhook();