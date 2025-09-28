<?php
/**
 * AWS S3 Integration Class (Plugin Version)
 *
 * Handles file uploads, downloads, and management with AWS S3
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_S3_Integration {
    
    private $bucket_name;
    private $region;
    private $access_key;
    private $secret_key;
    private $delete_local;
    private $s3_client;
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->bucket_name = isset($config['bucket_name']) ? $config['bucket_name'] : '';
        $this->region = isset($config['region']) ? $config['region'] : 'us-east-1';
        $this->access_key = isset($config['access_key']) ? $config['access_key'] : '';
        $this->secret_key = isset($config['secret_key']) ? $config['secret_key'] : '';
        $this->delete_local = isset($config['delete_local']) ? $config['delete_local'] : false;
        
        // Initialize S3 client if credentials are provided
        if ($this->access_key && $this->secret_key) {
            $this->init_s3_client();
        }
    }
    
    /**
     * Initialize AWS S3 Client
     */
    private function init_s3_client() {
        if (empty($this->access_key) || empty($this->secret_key)) {
            return false;
        }
        
        try {
            // Use cURL-based S3 client for environments without AWS SDK
            $this->s3_client = true; // Placeholder - will implement cURL-based methods
            return true;
        } catch (Exception $e) {
            error_log('S3 Client initialization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload file to S3 using cURL
     */
    public function upload_file($file_path, $s3_key = null) {
        if (!file_exists($file_path)) {
            return array('success' => false, 'message' => 'File does not exist');
        }
        
        if (!$s3_key) {
            $s3_key = 'uploads/' . date('Y/m/') . basename($file_path);
        }
        
        $file_content = file_get_contents($file_path);
        $content_type = mime_content_type($file_path);
        
        $result = $this->s3_put_object($s3_key, $file_content, $content_type);
        
        if ($result['success']) {
            // Store file info in database
            $plugin = clarity_aws_ghl();
            if ($plugin && $plugin->database) {
                $plugin->database->add_s3_file(array(
                    'file_name' => basename($file_path),
                    's3_key' => $s3_key,
                    'file_size' => filesize($file_path),
                    'content_type' => $content_type,
                    'uploaded_at' => current_time('mysql')
                ));
            }
            
            // Delete local file if configured
            if ($this->delete_local) {
                unlink($file_path);
            }
            
            return array(
                'success' => true,
                'message' => 'File uploaded successfully',
                's3_key' => $s3_key,
                's3_url' => $this->get_s3_url($s3_key)
            );
        }
        
        return $result;
    }
    
    /**
     * Upload webhook data to S3
     */
    public function upload_webhook_data($data, $contact_id = null) {
        $filename = 'webhook-' . ($contact_id ? $contact_id . '-' : '') . time() . '.json';
        $s3_key = 'webhooks/' . date('Y/m/d/') . $filename;
        
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        
        return $this->s3_put_object($s3_key, $json_data, 'application/json');
    }
    
    /**
     * Download file from S3
     */
    public function download_file($s3_key) {
        return $this->s3_get_object($s3_key);
    }
    
    /**
     * Delete file from S3
     */
    public function delete_file($s3_key) {
        return $this->s3_delete_object($s3_key);
    }
    
    /**
     * Test S3 connection
     */
    public function test_connection() {
        if (!$this->bucket_name || !$this->access_key || !$this->secret_key) {
            return array(
                'success' => false,
                'message' => 'S3 credentials not configured'
            );
        }
        
        // Test with a simple HEAD request to bucket
        $result = $this->s3_head_bucket();
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'S3 connection successful. Bucket: ' . $this->bucket_name
            );
        }
        
        return $result;
    }
    
    /**
     * PUT object to S3 using cURL
     */
    private function s3_put_object($key, $content, $content_type = 'application/octet-stream') {
        $host = $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com';
        $url = 'https://' . $host . '/' . $key;
        
        $headers = $this->generate_s3_headers('PUT', $key, $content, $content_type);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return array('success' => false, 'message' => 'cURL error: ' . $error);
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            return array(
                'success' => true,
                'message' => 'Object uploaded successfully',
                's3_key' => $key,
                's3_url' => $url
            );
        } else {
            return array(
                'success' => false,
                'message' => 'S3 upload failed. HTTP Code: ' . $http_code . '. Response: ' . $response
            );
        }
    }
    
    /**
     * GET object from S3 using cURL
     */
    private function s3_get_object($key) {
        $host = $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com';
        $url = 'https://' . $host . '/' . $key;
        
        $headers = $this->generate_s3_headers('GET', $key);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return array('success' => false, 'message' => 'cURL error: ' . $error);
        }
        
        if ($http_code === 200) {
            return array('success' => true, 'data' => $response);
        } else {
            return array('success' => false, 'message' => 'S3 download failed. HTTP Code: ' . $http_code);
        }
    }
    
    /**
     * DELETE object from S3 using cURL
     */
    private function s3_delete_object($key) {
        $host = $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com';
        $url = 'https://' . $host . '/' . $key;
        
        $headers = $this->generate_s3_headers('DELETE', $key);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return array('success' => false, 'message' => 'cURL error: ' . $error);
        }
        
        if ($http_code === 204) {
            return array('success' => true, 'message' => 'Object deleted successfully');
        } else {
            return array('success' => false, 'message' => 'S3 delete failed. HTTP Code: ' . $http_code);
        }
    }
    
    /**
     * HEAD bucket to test connection
     */
    private function s3_head_bucket() {
        $host = $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com';
        $url = 'https://' . $host . '/';
        
        $headers = $this->generate_s3_headers('HEAD', '');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return array('success' => false, 'message' => 'cURL error: ' . $error);
        }
        
        if ($http_code === 200) {
            return array('success' => true, 'message' => 'Bucket accessible');
        } else {
            return array('success' => false, 'message' => 'Bucket not accessible. HTTP Code: ' . $http_code);
        }
    }
    
    /**
     * Generate S3 authentication headers
     */
    private function generate_s3_headers($method, $key, $content = '', $content_type = '') {
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        $host = $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com';
        $canonical_uri = '/' . $key;
        $canonical_querystring = '';
        
        $canonical_headers = "host:" . $host . "\n" .
                           "x-amz-content-sha256:" . hash('sha256', $content) . "\n" .
                           "x-amz-date:" . $timestamp . "\n";
        
        if ($content_type) {
            $canonical_headers = "content-type:" . $content_type . "\n" . $canonical_headers;
            $signed_headers = 'content-type;host;x-amz-content-sha256;x-amz-date';
        } else {
            $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
        }
        
        $canonical_request = $method . "\n" .
                           $canonical_uri . "\n" .
                           $canonical_querystring . "\n" .
                           $canonical_headers . "\n" .
                           $signed_headers . "\n" .
                           hash('sha256', $content);
        
        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = $date . '/' . $this->region . '/s3/aws4_request';
        $string_to_sign = $algorithm . "\n" .
                         $timestamp . "\n" .
                         $credential_scope . "\n" .
                         hash('sha256', $canonical_request);
        
        $signing_key = $this->get_signature_key($this->secret_key, $date, $this->region, 's3');
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        $authorization_header = $algorithm . ' ' .
                               'Credential=' . $this->access_key . '/' . $credential_scope . ', ' .
                               'SignedHeaders=' . $signed_headers . ', ' .
                               'Signature=' . $signature;
        
        $headers = array(
            'Authorization: ' . $authorization_header,
            'Host: ' . $host,
            'X-Amz-Content-Sha256: ' . hash('sha256', $content),
            'X-Amz-Date: ' . $timestamp,
        );
        
        if ($content_type) {
            $headers[] = 'Content-Type: ' . $content_type;
        }
        
        return $headers;
    }
    
    /**
     * Generate AWS4 signature key
     */
    private function get_signature_key($key, $date_stamp, $region_name, $service_name) {
        $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $key, true);
        $k_region = hash_hmac('sha256', $region_name, $k_date, true);
        $k_service = hash_hmac('sha256', $service_name, $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        return $k_signing;
    }
    
    /**
     * Get S3 URL for a key
     */
    private function get_s3_url($key) {
        return 'https://' . $this->bucket_name . '.s3.' . $this->region . '.amazonaws.com/' . $key;
    }
    
    /**
     * Check if S3 is configured
     */
    public function is_configured() {
        return !empty($this->bucket_name) && !empty($this->access_key) && !empty($this->secret_key);
    }
}