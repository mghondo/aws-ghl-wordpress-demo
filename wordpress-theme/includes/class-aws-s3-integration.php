<?php
/**
 * AWS S3 Integration Class
 *
 * Handles file uploads, downloads, and management with AWS S3
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clarity_Theme_AWS_S3_Integration {
    
    private $bucket_name;
    private $region;
    private $access_key;
    private $secret_key;
    private $s3_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->bucket_name = get_option('clarity_s3_bucket_name', 'clarity-aws-ghl-demo-storage');
        $this->region = get_option('clarity_s3_region', 'us-east-1');
        $this->access_key = get_option('clarity_s3_access_key', '');
        $this->secret_key = get_option('clarity_s3_secret_key', '');
        
        // Initialize S3 client if AWS SDK is available
        if (class_exists('Aws\S3\S3Client')) {
            $this->init_s3_client();
        }
        
        // Hook into WordPress
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        // File upload handling
        add_filter('wp_handle_upload', array($this, 'upload_to_s3'), 10, 2);
        
        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_clarity_s3_test_connection', array($this, 'test_s3_connection'));
        add_action('wp_ajax_clarity_s3_upload_file', array($this, 'ajax_upload_file'));
        
        // Custom upload directory for S3
        add_filter('upload_dir', array($this, 'custom_upload_dir'));
    }
    
    /**
     * Initialize AWS S3 Client
     */
    private function init_s3_client() {
        if (empty($this->access_key) || empty($this->secret_key)) {
            return false;
        }
        
        try {
            $this->s3_client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->access_key,
                    'secret' => $this->secret_key,
                ],
            ]);
            return true;
        } catch (Exception $e) {
            error_log('S3 Client initialization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload file to S3
     */
    public function upload_to_s3($upload, $context = 'upload') {
        if (!$this->s3_client || !isset($upload['file'])) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        $file_name = basename($file_path);
        $s3_key = 'uploads/' . date('Y/m/') . $file_name;
        
        try {
            // Upload to S3
            $result = $this->s3_client->putObject([
                'Bucket' => $this->bucket_name,
                'Key' => $s3_key,
                'SourceFile' => $file_path,
                'ACL' => 'private',
                'Metadata' => [
                    'uploaded_by' => 'wordpress',
                    'upload_time' => current_time('timestamp'),
                ]
            ]);
            
            // Update upload info
            $upload['s3_url'] = $result['ObjectURL'];
            $upload['s3_key'] = $s3_key;
            
            // Optionally delete local file
            if (get_option('clarity_s3_delete_local', false)) {
                unlink($file_path);
            }
            
            // Log successful upload
            error_log("File uploaded to S3: {$s3_key}");
            
        } catch (Exception $e) {
            error_log('S3 Upload failed: ' . $e->getMessage());
        }
        
        return $upload;
    }
    
    /**
     * Download file from S3
     */
    public function download_from_s3($s3_key, $local_path = null) {
        if (!$this->s3_client) {
            return false;
        }
        
        try {
            if ($local_path) {
                // Download to specific path
                $this->s3_client->getObject([
                    'Bucket' => $this->bucket_name,
                    'Key' => $s3_key,
                    'SaveAs' => $local_path,
                ]);
                return $local_path;
            } else {
                // Get object data
                $result = $this->s3_client->getObject([
                    'Bucket' => $this->bucket_name,
                    'Key' => $s3_key,
                ]);
                return $result['Body'];
            }
        } catch (Exception $e) {
            error_log('S3 Download failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete file from S3
     */
    public function delete_from_s3($s3_key) {
        if (!$this->s3_client) {
            return false;
        }
        
        try {
            $this->s3_client->deleteObject([
                'Bucket' => $this->bucket_name,
                'Key' => $s3_key,
            ]);
            return true;
        } catch (Exception $e) {
            error_log('S3 Delete failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List files in S3 bucket
     */
    public function list_s3_files($prefix = '') {
        if (!$this->s3_client) {
            return array();
        }
        
        try {
            $result = $this->s3_client->listObjects([
                'Bucket' => $this->bucket_name,
                'Prefix' => $prefix,
            ]);
            
            return isset($result['Contents']) ? $result['Contents'] : array();
        } catch (Exception $e) {
            error_log('S3 List failed: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Generate presigned URL for secure file access
     */
    public function generate_presigned_url($s3_key, $expires = '+1 hour') {
        if (!$this->s3_client) {
            return false;
        }
        
        try {
            $command = $this->s3_client->getCommand('GetObject', [
                'Bucket' => $this->bucket_name,
                'Key' => $s3_key,
            ]);
            
            $request = $this->s3_client->createPresignedRequest($command, $expires);
            return (string) $request->getUri();
        } catch (Exception $e) {
            error_log('S3 Presigned URL failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test S3 connection
     */
    public function test_s3_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'clarity_s3_test_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!$this->s3_client) {
            wp_send_json_error('S3 client not initialized');
        }
        
        try {
            // Test by listing bucket contents
            $this->s3_client->headBucket([
                'Bucket' => $this->bucket_name,
            ]);
            wp_send_json_success('S3 connection successful');
        } catch (Exception $e) {
            wp_send_json_error('S3 connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX file upload handler
     */
    public function ajax_upload_file() {
        if (!wp_verify_nonce($_POST['nonce'], 'clarity_s3_upload_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file provided');
        }
        
        $file = $_FILES['file'];
        $upload_dir = wp_upload_dir();
        $temp_path = $upload_dir['path'] . '/' . $file['name'];
        
        if (move_uploaded_file($file['tmp_name'], $temp_path)) {
            $upload_result = $this->upload_to_s3(array('file' => $temp_path));
            
            if (isset($upload_result['s3_url'])) {
                wp_send_json_success(array(
                    'message' => 'File uploaded successfully',
                    's3_url' => $upload_result['s3_url'],
                    's3_key' => $upload_result['s3_key'],
                ));
            } else {
                wp_send_json_error('Failed to upload to S3');
            }
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }
    
    /**
     * Custom upload directory for S3 integration
     */
    public function custom_upload_dir($upload) {
        // Add S3 metadata to upload directory info
        $upload['s3_bucket'] = $this->bucket_name;
        $upload['s3_region'] = $this->region;
        return $upload;
    }
    
    /**
     * Add admin menu for S3 settings
     */
    public function add_admin_menu() {
        add_options_page(
            'AWS S3 Settings',
            'AWS S3 Settings',
            'manage_options',
            'clarity-s3-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('clarity_s3_settings', 'clarity_s3_bucket_name');
        register_setting('clarity_s3_settings', 'clarity_s3_region');
        register_setting('clarity_s3_settings', 'clarity_s3_access_key');
        register_setting('clarity_s3_settings', 'clarity_s3_secret_key');
        register_setting('clarity_s3_settings', 'clarity_s3_delete_local');
    }
    
    /**
     * Admin settings page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AWS S3 Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('clarity_s3_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Bucket Name</th>
                        <td><input type="text" name="clarity_s3_bucket_name" value="<?php echo esc_attr($this->bucket_name); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Region</th>
                        <td><input type="text" name="clarity_s3_region" value="<?php echo esc_attr($this->region); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Access Key</th>
                        <td><input type="text" name="clarity_s3_access_key" value="<?php echo esc_attr($this->access_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Secret Key</th>
                        <td><input type="password" name="clarity_s3_secret_key" value="<?php echo esc_attr($this->secret_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Delete Local Files</th>
                        <td><input type="checkbox" name="clarity_s3_delete_local" value="1" <?php checked(get_option('clarity_s3_delete_local'), 1); ?> /> Delete local files after S3 upload</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Connection Test</h2>
            <button id="test-s3-connection" class="button">Test S3 Connection</button>
            <div id="s3-test-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-s3-connection').click(function() {
                $.post(ajaxurl, {
                    action: 'clarity_s3_test_connection',
                    nonce: '<?php echo wp_create_nonce('clarity_s3_test_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#s3-test-result').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        $('#s3-test-result').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize the S3 integration
new Clarity_Theme_AWS_S3_Integration();