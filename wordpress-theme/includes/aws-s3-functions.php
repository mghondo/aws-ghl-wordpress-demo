<?php
/**
 * AWS S3 Helper Functions
 *
 * Utility functions for S3 operations
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Upload file to S3 with error handling
 */
function clarity_s3_upload_file($file_path, $s3_key = null, $metadata = array()) {
    $s3_integration = new Clarity_AWS_S3_Integration();
    
    if (!$s3_key) {
        $s3_key = 'uploads/' . date('Y/m/') . basename($file_path);
    }
    
    return $s3_integration->upload_to_s3(array('file' => $file_path), 'manual');
}

/**
 * Get secure download URL for S3 file
 */
function clarity_s3_get_download_url($s3_key, $expires = '+1 hour') {
    $s3_integration = new Clarity_AWS_S3_Integration();
    return $s3_integration->generate_presigned_url($s3_key, $expires);
}

/**
 * Check if S3 is properly configured
 */
function clarity_s3_is_configured() {
    $bucket_name = get_option('clarity_s3_bucket_name', '');
    $access_key = get_option('clarity_s3_access_key', '');
    $secret_key = get_option('clarity_s3_secret_key', '');
    
    return !empty($bucket_name) && !empty($access_key) && !empty($secret_key);
}

/**
 * Get S3 bucket info
 */
function clarity_s3_get_bucket_info() {
    return array(
        'bucket_name' => get_option('clarity_s3_bucket_name', ''),
        'region' => get_option('clarity_s3_region', 'us-east-1'),
        'configured' => clarity_s3_is_configured(),
    );
}

/**
 * Format file size
 */
function clarity_format_file_size($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get file extension from S3 key
 */
function clarity_get_file_extension($s3_key) {
    return pathinfo($s3_key, PATHINFO_EXTENSION);
}

/**
 * Check if file type is allowed for S3 upload
 */
function clarity_s3_is_allowed_file_type($file_path) {
    $allowed_types = get_option('clarity_s3_allowed_types', array(
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'rar', 'tar', 'gz',
        'mp4', 'avi', 'mov', 'wmv',
        'mp3', 'wav', 'ogg'
    ));
    
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    return in_array($file_extension, $allowed_types);
}

/**
 * Sanitize S3 key
 */
function clarity_sanitize_s3_key($key) {
    // Remove special characters and spaces
    $key = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '', $key);
    
    // Remove double slashes
    $key = preg_replace('/\/+/', '/', $key);
    
    // Remove leading slash
    $key = ltrim($key, '/');
    
    return $key;
}

/**
 * Generate unique S3 key for file
 */
function clarity_generate_unique_s3_key($original_filename, $folder = 'uploads') {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $basename = pathinfo($original_filename, PATHINFO_FILENAME);
    
    // Sanitize filename
    $basename = sanitize_file_name($basename);
    
    // Add timestamp to ensure uniqueness
    $timestamp = time();
    $unique_filename = $basename . '-' . $timestamp . '.' . $extension;
    
    // Create S3 key with date-based folder structure
    $s3_key = $folder . '/' . date('Y/m/') . $unique_filename;
    
    return clarity_sanitize_s3_key($s3_key);
}

/**
 * Log S3 operations
 */
function clarity_s3_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[Clarity S3] ' . $message;
        error_log($log_message);
    }
    
    // Also log to custom S3 log if enabled
    if (get_option('clarity_s3_enable_logging', false)) {
        $log_file = WP_CONTENT_DIR . '/clarity-s3.log';
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Handle S3 upload errors
 */
function clarity_s3_handle_error($error, $context = '') {
    $error_message = is_string($error) ? $error : $error->getMessage();
    
    clarity_s3_log("Error in {$context}: {$error_message}", 'error');
    
    // Show admin notice if in admin area
    if (is_admin()) {
        add_action('admin_notices', function() use ($error_message, $context) {
            echo '<div class="notice notice-error"><p><strong>S3 Error:</strong> ' . esc_html($error_message) . '</p></div>';
        });
    }
    
    return false;
}

/**
 * Backup important files to S3
 */
function clarity_s3_backup_files($files, $backup_folder = 'backups') {
    $results = array();
    
    foreach ($files as $file_path) {
        if (file_exists($file_path)) {
            $filename = basename($file_path);
            $s3_key = $backup_folder . '/' . date('Y-m-d') . '/' . $filename;
            
            $result = clarity_s3_upload_file($file_path, $s3_key);
            $results[$file_path] = $result;
            
            clarity_s3_log("Backup uploaded: {$s3_key}", 'info');
        }
    }
    
    return $results;
}

/**
 * Clean up old S3 files
 */
function clarity_s3_cleanup_old_files($folder = 'temp', $days_old = 7) {
    $s3_integration = new Clarity_AWS_S3_Integration();
    $files = $s3_integration->list_s3_files($folder . '/');
    
    $deleted_count = 0;
    $cutoff_date = time() - ($days_old * 24 * 60 * 60);
    
    foreach ($files as $file) {
        $file_date = strtotime($file['LastModified']);
        
        if ($file_date < $cutoff_date) {
            if ($s3_integration->delete_from_s3($file['Key'])) {
                $deleted_count++;
                clarity_s3_log("Deleted old file: {$file['Key']}", 'info');
            }
        }
    }
    
    return $deleted_count;
}