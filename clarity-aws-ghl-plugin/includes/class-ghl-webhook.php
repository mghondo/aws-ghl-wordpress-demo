<?php
/**
 * GoHighLevel Webhook Handler (Plugin Version)
 *
 * Core webhook functionality for receiving and processing GHL data
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_GHL_Webhook {
    
    private $webhook_secret;
    private $create_contacts;
    private $notification_email;
    private $s3_integration;
    private $database;
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->webhook_secret = isset($config['webhook_secret']) ? $config['webhook_secret'] : '';
        $this->create_contacts = isset($config['create_contacts']) ? $config['create_contacts'] : true;
        $this->notification_email = isset($config['notification_email']) ? $config['notification_email'] : '';
        $this->s3_integration = isset($config['s3_integration']) ? $config['s3_integration'] : null;
        $this->database = isset($config['database']) ? $config['database'] : null;
    }
    
    /**
     * Register REST API endpoint
     */
    public function register_rest_routes() {
        register_rest_route('clarity-ghl/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
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
            
            // Validate content type
            if (!$this->is_valid_content_type($request->get_content_type())) {
                return $this->error_response(400, 'Invalid content type. Expected application/json');
            }
            
            // Verify webhook signature if secret is configured
            if (!empty($this->webhook_secret)) {
                if (!$this->verify_signature($raw_body, $headers)) {
                    $this->log_webhook_to_db(array(
                        'event_type' => 'signature_verification_failed',
                        'status' => 'error',
                        'payload' => $raw_body,
                        'error_message' => 'Signature verification failed'
                    ));
                    return $this->error_response(401, 'Signature verification failed');
                }
            }
            
            // Parse JSON data
            $webhook_data = json_decode($raw_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log_webhook_to_db(array(
                    'event_type' => 'invalid_json',
                    'status' => 'error',
                    'payload' => $raw_body,
                    'error_message' => 'Invalid JSON: ' . json_last_error_msg()
                ));
                return $this->error_response(400, 'Invalid JSON: ' . json_last_error_msg());
            }
            
            // Process webhook data
            $result = $this->process_webhook_data($webhook_data, $headers, $raw_body);
            
            if ($result['success']) {
                $processing_time = round((microtime(true) - $start_time) * 1000, 2);
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                    'processing_time_ms' => $processing_time,
                    's3_key' => $result['s3_key'],
                    'contact_id' => $result['contact_id'],
                    'timestamp' => current_time('c')
                ), 200);
            } else {
                return $this->error_response(500, 'Failed to process webhook: ' . $result['error']);
            }
            
        } catch (Exception $e) {
            $this->log_webhook_to_db(array(
                'event_type' => 'processing_error',
                'status' => 'error',
                'payload' => isset($raw_body) ? $raw_body : '',
                'error_message' => $e->getMessage()
            ));
            
            return $this->error_response(500, 'Internal server error');
        }
    }
    
    /**
     * Process webhook data and store
     */
    private function process_webhook_data($data, $headers, $raw_body) {
        try {
            $start_processing = microtime(true);
            
            // Extract event details
            $event_type = $this->extract_event_type($data);
            $contact_id = $this->extract_contact_id($data);
            
            $log_data = array(
                'event_type' => $event_type,
                'contact_id' => $contact_id,
                'payload' => $raw_body,
                'status' => 'pending'
            );
            
            // Store in S3 if integration is available
            $s3_key = null;
            if ($this->s3_integration && $this->s3_integration->is_configured()) {
                $s3_result = $this->s3_integration->upload_webhook_data($data, $contact_id);
                if ($s3_result['success']) {
                    $s3_key = $s3_result['s3_key'];
                    $log_data['s3_key'] = $s3_key;
                }
            }
            
            // Create WordPress contact post if enabled
            if ($this->create_contacts && $this->is_contact_event($event_type)) {
                $this->create_contact_post($data);
            }
            
            // Calculate processing time
            $processing_time = round((microtime(true) - $start_processing) * 1000, 2);
            $log_data['processing_time_ms'] = $processing_time;
            $log_data['status'] = 'success';
            
            // Log to database
            $this->log_webhook_to_db($log_data);
            
            // Send notification if configured
            if ($this->notification_email && $this->is_important_event($event_type)) {
                $this->send_notification($event_type, $contact_id, $data);
            }
            
            return array(
                'success' => true,
                's3_key' => $s3_key,
                'contact_id' => $contact_id,
                'processing_time_ms' => $processing_time
            );
            
        } catch (Exception $e) {
            // Log error
            $this->log_webhook_to_db(array(
                'event_type' => $event_type ?? 'unknown',
                'contact_id' => $contact_id ?? null,
                'payload' => $raw_body,
                'status' => 'error',
                'error_message' => $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Log webhook to database
     */
    private function log_webhook_to_db($data) {
        if (!$this->database) {
            return false;
        }
        
        return $this->database->log_webhook($data);
    }
    
    /**
     * Create WordPress contact post
     */
    private function create_contact_post($data) {
        $contact_data = $this->extract_contact_data($data);
        
        if (empty($contact_data['email']) && empty($contact_data['phone'])) {
            return false;
        }
        
        // Check if contact already exists
        $existing_contact = $this->find_existing_contact($contact_data);
        if ($existing_contact) {
            return $this->update_contact_post($existing_contact, $contact_data);
        }
        
        // Create new contact post
        $post_data = array(
            'post_type' => 'ghl_contact',
            'post_status' => 'publish',
            'post_title' => $contact_data['name'] ?: $contact_data['email'],
            'meta_input' => array(
                '_ghl_contact_id' => $contact_data['id'],
                '_ghl_email' => $contact_data['email'],
                '_ghl_phone' => $contact_data['phone'],
                '_ghl_first_name' => $contact_data['first_name'],
                '_ghl_last_name' => $contact_data['last_name'],
                '_ghl_tags' => $contact_data['tags'],
                '_ghl_source' => $contact_data['source'],
                '_ghl_created_at' => $contact_data['created_at'],
                '_ghl_last_synced' => current_time('mysql')
            )
        );
        
        return wp_insert_post($post_data);
    }
    
    /**
     * Extract contact data from webhook
     */
    private function extract_contact_data($data) {
        $contact = isset($data['contact']) ? $data['contact'] : $data;
        
        return array(
            'id' => $contact['id'] ?? $contact['contactId'] ?? '',
            'email' => $contact['email'] ?? '',
            'phone' => $contact['phone'] ?? $contact['phoneNumber'] ?? '',
            'first_name' => $contact['firstName'] ?? $contact['first_name'] ?? '',
            'last_name' => $contact['lastName'] ?? $contact['last_name'] ?? '',
            'name' => trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? '')),
            'tags' => $contact['tags'] ?? array(),
            'source' => $contact['source'] ?? 'webhook',
            'created_at' => $contact['createdAt'] ?? current_time('mysql')
        );
    }
    
    /**
     * Find existing contact by email or phone
     */
    private function find_existing_contact($contact_data) {
        $meta_query = array('relation' => 'OR');
        
        if (!empty($contact_data['email'])) {
            $meta_query[] = array(
                'key' => '_ghl_email',
                'value' => $contact_data['email'],
                'compare' => '='
            );
        }
        
        if (!empty($contact_data['phone'])) {
            $meta_query[] = array(
                'key' => '_ghl_phone',
                'value' => $contact_data['phone'],
                'compare' => '='
            );
        }
        
        $posts = get_posts(array(
            'post_type' => 'ghl_contact',
            'meta_query' => $meta_query,
            'posts_per_page' => 1
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Update existing contact post
     */
    private function update_contact_post($post, $contact_data) {
        $meta_updates = array(
            '_ghl_last_synced' => current_time('mysql')
        );
        
        foreach ($contact_data as $key => $value) {
            if (!empty($value)) {
                $meta_updates['_ghl_' . $key] = $value;
            }
        }
        
        foreach ($meta_updates as $meta_key => $meta_value) {
            update_post_meta($post->ID, $meta_key, $meta_value);
        }
        
        return $post->ID;
    }
    
    /**
     * Extract event type from webhook data
     */
    private function extract_event_type($data) {
        if (isset($data['type'])) {
            return sanitize_text_field($data['type']);
        } elseif (isset($data['event'])) {
            return sanitize_text_field($data['event']);
        } elseif (isset($data['eventType'])) {
            return sanitize_text_field($data['eventType']);
        }
        
        return 'unknown';
    }
    
    /**
     * Extract contact ID from webhook data
     */
    private function extract_contact_id($data) {
        if (isset($data['contactId'])) {
            return sanitize_text_field($data['contactId']);
        } elseif (isset($data['contact']['id'])) {
            return sanitize_text_field($data['contact']['id']);
        } elseif (isset($data['id'])) {
            return sanitize_text_field($data['id']);
        }
        
        return null;
    }
    
    /**
     * Check if event is contact-related
     */
    private function is_contact_event($event_type) {
        $contact_events = array(
            'ContactCreate', 'ContactUpdate', 'contact.created', 'contact.updated'
        );
        
        return in_array($event_type, $contact_events);
    }
    
    /**
     * Check if event requires notification
     */
    private function is_important_event($event_type) {
        $important_events = array(
            'ContactCreate', 'OpportunityCreate', 'AppointmentScheduled'
        );
        
        return in_array($event_type, $important_events);
    }
    
    /**
     * Send email notification
     */
    private function send_notification($event_type, $contact_id, $data) {
        if (empty($this->notification_email)) {
            return false;
        }
        
        $subject = sprintf('[GHL Webhook] New %s Event', $event_type);
        
        $message = "A new GoHighLevel webhook event was received:\n\n";
        $message .= "Event Type: " . $event_type . "\n";
        $message .= "Contact ID: " . ($contact_id ?: 'N/A') . "\n";
        $message .= "Timestamp: " . current_time('c') . "\n\n";
        $message .= "Data: " . json_encode($data, JSON_PRETTY_PRINT);
        
        return wp_mail($this->notification_email, $subject, $message);
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_signature($raw_body, $headers) {
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
     * Get webhook endpoint URL
     */
    public function get_webhook_url() {
        return rest_url('clarity-ghl/v1/webhook');
    }
}