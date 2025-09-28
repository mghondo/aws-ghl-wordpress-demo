<?php
/**
 * Database Management Class
 *
 * Handles database table creation and management for the plugin
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Table names
     */
    private $webhook_logs_table;
    private $ghl_contacts_table;
    private $s3_files_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->webhook_logs_table = $wpdb->prefix . 'clarity_webhook_logs';
        $this->ghl_contacts_table = $wpdb->prefix . 'clarity_ghl_contacts';
        $this->s3_files_table = $wpdb->prefix . 'clarity_s3_files';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Webhook logs table
        $webhook_logs_sql = "CREATE TABLE {$this->webhook_logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id varchar(100) NOT NULL,
            event_type varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'success',
            http_code int(3) NOT NULL DEFAULT 200,
            processing_time_ms int(10) unsigned DEFAULT NULL,
            s3_key varchar(500) DEFAULT NULL,
            payload_size int(10) unsigned DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id),
            KEY event_type (event_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // GHL contacts table
        $ghl_contacts_sql = "CREATE TABLE {$this->ghl_contacts_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ghl_contact_id varchar(100) NOT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            source varchar(100) DEFAULT NULL,
            tags text DEFAULT NULL,
            custom_fields text DEFAULT NULL,
            wp_user_id bigint(20) unsigned DEFAULT NULL,
            last_sync_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ghl_contact_id (ghl_contact_id),
            KEY email (email),
            KEY wp_user_id (wp_user_id),
            KEY last_sync_at (last_sync_at)
        ) $charset_collate;";
        
        // S3 files table
        $s3_files_sql = "CREATE TABLE {$this->s3_files_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            s3_key varchar(500) NOT NULL,
            original_filename varchar(255) NOT NULL,
            file_size bigint(20) unsigned DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            upload_type varchar(50) DEFAULT 'webhook',
            source_reference varchar(100) DEFAULT NULL,
            wp_attachment_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY s3_key (s3_key),
            KEY upload_type (upload_type),
            KEY source_reference (source_reference),
            KEY wp_attachment_id (wp_attachment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($webhook_logs_sql);
        dbDelta($ghl_contacts_sql);
        dbDelta($s3_files_sql);
        
        // Update database version
        update_option('clarity_aws_ghl_db_version', self::DB_VERSION);
    }
    
    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $this->webhook_logs_table,
            $this->ghl_contacts_table,
            $this->s3_files_table
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        delete_option('clarity_aws_ghl_db_version');
    }
    
    /**
     * Log webhook activity
     */
    public function log_webhook($data) {
        global $wpdb;
        
        $default_data = array(
            'webhook_id' => wp_generate_uuid4(),
            'event_type' => 'unknown',
            'status' => 'success',
            'http_code' => 200,
            'processing_time_ms' => null,
            's3_key' => null,
            'payload_size' => null,
            'user_agent' => null,
            'ip_address' => $this->get_client_ip(),
            'error_message' => null,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $default_data);
        
        return $wpdb->insert(
            $this->webhook_logs_table,
            $data,
            array(
                '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s'
            )
        );
    }
    
    /**
     * Get webhook logs
     */
    public function get_webhook_logs($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['event_type'])) {
            $where_clauses[] = 'event_type = %s';
            $where_values[] = $filters['event_type'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT * FROM {$this->webhook_logs_table} 
                {$where_sql} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get webhook logs count
     */
    public function get_webhook_logs_count($filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply same filters as get_webhook_logs
        if (!empty($filters['event_type'])) {
            $where_clauses[] = 'event_type = %s';
            $where_values[] = $filters['event_type'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->webhook_logs_table} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Clear old webhook logs
     */
    public function clear_webhook_logs($days_old = null) {
        global $wpdb;
        
        if ($days_old === null) {
            $days_old = get_option('clarity_log_retention_days', 30);
        }
        
        if ($days_old <= 0) {
            // Clear all logs
            return $wpdb->query("TRUNCATE TABLE {$this->webhook_logs_table}");
        } else {
            // Clear logs older than specified days
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
            return $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->webhook_logs_table} WHERE created_at < %s",
                    $cutoff_date
                )
            );
        }
    }
    
    /**
     * Save or update GHL contact
     */
    public function save_ghl_contact($contact_data) {
        global $wpdb;
        
        $default_data = array(
            'ghl_contact_id' => '',
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone' => null,
            'source' => null,
            'tags' => null,
            'custom_fields' => null,
            'wp_user_id' => null,
            'last_sync_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($contact_data, $default_data);
        
        // Serialize arrays
        if (is_array($data['tags'])) {
            $data['tags'] = serialize($data['tags']);
        }
        
        if (is_array($data['custom_fields'])) {
            $data['custom_fields'] = serialize($data['custom_fields']);
        }
        
        // Check if contact exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$this->ghl_contacts_table} WHERE ghl_contact_id = %s",
                $data['ghl_contact_id']
            )
        );
        
        if ($existing) {
            // Update existing contact
            $data['updated_at'] = current_time('mysql');
            return $wpdb->update(
                $this->ghl_contacts_table,
                $data,
                array('ghl_contact_id' => $data['ghl_contact_id']),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new contact
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert(
                $this->ghl_contacts_table,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Get GHL contact by ID
     */
    public function get_ghl_contact($ghl_contact_id) {
        global $wpdb;
        
        $contact = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->ghl_contacts_table} WHERE ghl_contact_id = %s",
                $ghl_contact_id
            )
        );
        
        if ($contact) {
            // Unserialize arrays
            if (!empty($contact->tags)) {
                $contact->tags = maybe_unserialize($contact->tags);
            }
            
            if (!empty($contact->custom_fields)) {
                $contact->custom_fields = maybe_unserialize($contact->custom_fields);
            }
        }
        
        return $contact;
    }
    
    /**
     * Get all GHL contacts
     */
    public function get_ghl_contacts($limit = 50, $offset = 0) {
        global $wpdb;
        
        $contacts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->ghl_contacts_table} 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
        
        // Unserialize arrays for each contact
        foreach ($contacts as $contact) {
            if (!empty($contact->tags)) {
                $contact->tags = maybe_unserialize($contact->tags);
            }
            
            if (!empty($contact->custom_fields)) {
                $contact->custom_fields = maybe_unserialize($contact->custom_fields);
            }
        }
        
        return $contacts;
    }
    
    /**
     * Log S3 file upload
     */
    public function log_s3_file($file_data) {
        global $wpdb;
        
        $default_data = array(
            's3_key' => '',
            'original_filename' => '',
            'file_size' => null,
            'mime_type' => null,
            'upload_type' => 'webhook',
            'source_reference' => null,
            'wp_attachment_id' => null,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($file_data, $default_data);
        
        return $wpdb->insert(
            $this->s3_files_table,
            $data,
            array('%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Webhook stats
        $stats['total_webhooks'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->webhook_logs_table}");
        $stats['successful_webhooks'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->webhook_logs_table} WHERE status = 'success'");
        $stats['failed_webhooks'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->webhook_logs_table} WHERE status = 'error'");
        
        // Today's webhooks
        $today = current_time('Y-m-d');
        $stats['todays_webhooks'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->webhook_logs_table} WHERE DATE(created_at) = %s",
                $today
            )
        );
        
        // Contact stats
        $stats['total_contacts'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->ghl_contacts_table}");
        $stats['recent_contacts'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->ghl_contacts_table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        // S3 file stats
        $stats['total_files'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->s3_files_table}");
        $stats['total_file_size'] = (int) $wpdb->get_var("SELECT SUM(file_size) FROM {$this->s3_files_table}");
        
        // Event type breakdown
        $stats['event_types'] = $wpdb->get_results(
            "SELECT event_type, COUNT(*) as count 
             FROM {$this->webhook_logs_table} 
             GROUP BY event_type 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return $stats;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}