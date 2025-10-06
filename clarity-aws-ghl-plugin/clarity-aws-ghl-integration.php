<?php
/**
 * Plugin Name: Clarity AWS GoHighLevel Integration
 * Plugin URI: https://github.com/mghondo/aws-ghl-wordpress-demo
 * Description: Complete integration between WordPress, AWS S3, and GoHighLevel CRM. Handles webhook processing, file storage, and lead management.
 * Version: 1.0.1
 * Author: Morgan Hondros
 * Author URI: https://github.com/mghondo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clarity-aws-ghl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package Clarity_AWS_GHL
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLARITY_AWS_GHL_VERSION', '1.0.1');
define('CLARITY_AWS_GHL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLARITY_AWS_GHL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLARITY_AWS_GHL_PLUGIN_FILE', __FILE__);
define('CLARITY_AWS_GHL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CLARITY_AWS_GHL_PATH', CLARITY_AWS_GHL_PLUGIN_DIR);
define('CLARITY_AWS_GHL_URL', CLARITY_AWS_GHL_PLUGIN_URL);

/**
 * Main Plugin Class
 */
class Clarity_AWS_GHL_Integration {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $admin;
    public $s3_integration;
    public $ghl_webhook;
    public $database;
    public $post_types;
    public $course_manager;
    public $lesson_handler;
    public $courses_admin;
    public $progress_tracker;
    public $frontend_templates;
    public $user_manager;
    public $user_admin;
    public $frontend_scripts;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('Clarity_AWS_GHL_Integration', 'uninstall'));
        
        // WordPress init hooks
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_clarity_test_s3_connection', array($this, 'ajax_test_s3_connection'));
        add_action('wp_ajax_clarity_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_clarity_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_clarity_test_certificate_endpoint', array($this, 'ajax_test_certificate_endpoint'));
        add_action('wp_ajax_clarity_generate_user_certificate', array($this, 'ajax_generate_user_certificate'));
        add_action('wp_ajax_clarity_fix_database_schema', array($this, 'ajax_fix_database_schema'));
        add_action('wp_ajax_clarity_test_database_operations', array($this, 'ajax_test_database_operations'));
        add_action('wp_ajax_clarity_clear_certificate_data', array($this, 'ajax_clear_certificate_data'));
        add_action('wp_ajax_clarity_view_certificate_logs', array($this, 'ajax_view_certificate_logs'));
        add_action('wp_ajax_clarity_fix_url_field_size', array($this, 'ajax_fix_url_field_size'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-database.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-admin.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-integrations.php';
        
        // Integration classes (adapted from theme)
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-s3-integration.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-ghl-webhook.php';
        
        // Course system includes
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-database-courses.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-course-manager.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-course-routing.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-contact-form-handler.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-lesson-handler.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-progress-tracker.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-certificate-manager.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-frontend-templates.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-user-manager.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'includes/class-frontend-scripts.php';
        
        // Admin includes
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-settings.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-dashboard.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-logs.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-courses-admin.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-user-admin.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->database = new Clarity_AWS_GHL_Database();
        $this->admin = new Clarity_AWS_GHL_Admin();
        $this->post_types = new Clarity_AWS_GHL_Post_Types();
        
        // Initialize course system components
        $this->course_manager = new Clarity_AWS_GHL_Course_Manager();
        $this->lesson_handler = new Clarity_AWS_GHL_Lesson_Handler();
        $this->courses_admin = new Clarity_AWS_GHL_Courses_Admin();
        $this->progress_tracker = new Clarity_AWS_GHL_Progress_Tracker();
        $this->frontend_templates = new Clarity_AWS_GHL_Frontend_Templates();
        
        // Initialize user management system
        $this->user_manager = new Clarity_AWS_GHL_User_Manager();
        $this->user_admin = new Clarity_AWS_GHL_User_Admin();
        $this->frontend_scripts = new Clarity_AWS_GHL_Frontend_Scripts();
        
        // Initialize integrations with configuration
        $this->s3_integration = new Clarity_AWS_S3_Integration(array(
            'bucket_name' => get_option('clarity_s3_bucket_name', ''),
            'region' => get_option('clarity_s3_region', 'us-east-1'),
            'access_key' => get_option('clarity_s3_access_key', ''),
            'secret_key' => get_option('clarity_s3_secret_key', ''),
            'delete_local' => get_option('clarity_s3_delete_local', false)
        ));
        
        $this->ghl_webhook = new Clarity_GHL_Webhook(array(
            'webhook_secret' => get_option('clarity_ghl_webhook_secret', ''),
            'create_contacts' => get_option('clarity_ghl_create_contacts', true),
            'notification_email' => get_option('clarity_ghl_notification_email', ''),
            's3_integration' => $this->s3_integration,
            'database' => $this->database
        ));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components that need WordPress to be fully loaded
        do_action('clarity_aws_ghl_init');
        
        // Add course URL rewrite rules
        $this->add_course_rewrite_rules();
        
        // Ensure MainPage exists
        $this->maybe_create_main_page();
        
        // Ensure Course page exists  
        $this->maybe_create_course_page();
    }
    
    /**
     * Add rewrite rules for course URLs
     */
    private function add_course_rewrite_rules() {
        // Add rewrite rules for /course/[course-slug]/ URLs
        add_rewrite_rule(
            '^course/([^/]+)/?$',
            'index.php?pagename=course&course_slug=$matches[1]',
            'top'
        );
        
        // Add query var for course slug
        add_filter('query_vars', function($vars) {
            $vars[] = 'course_slug';
            return $vars;
        });
        
        // Check if rewrite rules need to be flushed
        // Force flush for debugging - remove the '2' after confirming it works
        if (!get_option('clarity_rewrite_rules_flushed_2')) {
            flush_rewrite_rules();
            update_option('clarity_rewrite_rules_flushed_2', true);
        }
    }
    
    /**
     * Check and create MainPage if it doesn't exist
     */
    public function maybe_create_main_page() {
        // Only run this check once per session to avoid performance impact
        if (get_transient('clarity_mainpage_checked')) {
            return;
        }
        
        // Check if page exists
        $existing_page = get_page_by_path('mainpage');
        
        if (!$existing_page) {
            $this->create_main_page();
        }
        
        // Set transient to prevent checking on every page load (expires in 1 hour)
        set_transient('clarity_mainpage_checked', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Check and create Course page if it doesn't exist
     */
    public function maybe_create_course_page() {
        // Only run this check once per session to avoid performance impact
        if (get_transient('clarity_coursepage_checked')) {
            return;
        }
        
        // Check if page exists
        $existing_page = get_page_by_path('course');
        
        if (!$existing_page) {
            $this->create_course_page();
        }
        
        // Set transient to prevent checking on every page load (expires in 1 hour)
        set_transient('clarity_coursepage_checked', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'clarity-aws-ghl',
            false,
            dirname(CLARITY_AWS_GHL_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Register plugin settings
     */
    private function register_settings() {
        // S3 Settings
        register_setting('clarity_aws_ghl_s3', 'clarity_s3_bucket_name');
        register_setting('clarity_aws_ghl_s3', 'clarity_s3_region');
        register_setting('clarity_aws_ghl_s3', 'clarity_s3_access_key');
        register_setting('clarity_aws_ghl_s3', 'clarity_s3_secret_key');
        register_setting('clarity_aws_ghl_s3', 'clarity_s3_delete_local');
        
        // GHL Settings
        register_setting('clarity_aws_ghl_ghl', 'clarity_ghl_webhook_secret');
        register_setting('clarity_aws_ghl_ghl', 'clarity_ghl_webhook_enabled');
        register_setting('clarity_aws_ghl_ghl', 'clarity_ghl_create_contacts');
        register_setting('clarity_aws_ghl_ghl', 'clarity_ghl_notification_email');
        
        // General Settings
        register_setting('clarity_aws_ghl_general', 'clarity_debug_mode');
        register_setting('clarity_aws_ghl_general', 'clarity_log_retention_days');
        
        // Certificate Settings
        register_setting('clarity_aws_ghl_certificates', 'clarity_certificate_lambda_endpoint');
        register_setting('clarity_aws_ghl_certificates', 'clarity_certificate_enabled');
        register_setting('clarity_aws_ghl_certificates', 'clarity_certificate_auto_generate');
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        // Main menu page
        add_menu_page(
            __('AWS GHL Integration', 'clarity-aws-ghl'),
            __('AWS GHL', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl',
            array($this, 'admin_dashboard_page'),
            'dashicons-cloud',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'clarity-aws-ghl',
            __('Dashboard', 'clarity-aws-ghl'),
            __('Dashboard', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('S3 Settings', 'clarity-aws-ghl'),
            __('S3 Settings', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-s3',
            array($this, 'admin_s3_settings_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('GHL Settings', 'clarity-aws-ghl'),
            __('GHL Settings', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-ghl',
            array($this, 'admin_ghl_settings_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Certificate Settings', 'clarity-aws-ghl'),
            __('Certificates', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-certificates',
            array($this, 'admin_certificate_settings_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Webhook Logs', 'clarity-aws-ghl'),
            __('Webhook Logs', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-logs',
            array($this, 'admin_logs_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Hero', 'clarity-aws-ghl'),
            __('Hero', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-hero',
            array($this, 'admin_hero_background_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('About Us', 'clarity-aws-ghl'),
            __('About Us', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-about',
            array($this, 'admin_about_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('GHL Contacts', 'clarity-aws-ghl'),
            __('GHL Contacts', 'clarity-aws-ghl'),
            'manage_options',
            'edit.php?post_type=ghl_contact'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'clarity-aws-ghl') === false) {
            return;
        }
        
        wp_enqueue_style(
            'clarity-aws-ghl-admin',
            CLARITY_AWS_GHL_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            CLARITY_AWS_GHL_VERSION
        );
        
        wp_enqueue_script(
            'clarity-aws-ghl-admin',
            CLARITY_AWS_GHL_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            CLARITY_AWS_GHL_VERSION,
            true
        );
        
        // Enqueue WordPress media scripts for image upload
        wp_enqueue_media();
        
        wp_localize_script('clarity-aws-ghl-admin', 'clarityAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clarity_aws_ghl_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'clarity-aws-ghl'),
                'success' => __('Success!', 'clarity-aws-ghl'),
                'error' => __('Error', 'clarity-aws-ghl'),
                'confirm_clear' => __('Are you sure you want to clear all logs?', 'clarity-aws-ghl')
            )
        ));
    }
    
    /**
     * Admin page callbacks
     */
    public function admin_dashboard_page() {
        $dashboard = new Clarity_AWS_GHL_Dashboard();
        $dashboard->render();
    }
    
    public function admin_s3_settings_page() {
        $settings = new Clarity_AWS_GHL_Settings();
        $settings->render_s3_page();
    }
    
    public function admin_ghl_settings_page() {
        $settings = new Clarity_AWS_GHL_Settings();
        $settings->render_ghl_page();
    }
    
    public function admin_certificate_settings_page() {
        $this->render_certificate_settings_page();
    }
    
    public function admin_logs_page() {
        $logs = new Clarity_AWS_GHL_Logs();
        $logs->render();
    }
    
    public function admin_hero_background_page() {
        $this->render_hero_background_page();
    }
    
    public function admin_about_page() {
        $this->render_about_page();
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_test_s3_connection() {
        check_ajax_referer('clarity_aws_ghl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $result = $this->s3_integration->test_connection();
        wp_send_json($result);
    }
    
    public function ajax_test_webhook() {
        check_ajax_referer('clarity_aws_ghl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $result = $this->ghl_webhook->test_endpoint();
        wp_send_json($result);
    }
    
    public function ajax_clear_logs() {
        check_ajax_referer('clarity_aws_ghl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $result = $this->database->clear_webhook_logs();
        wp_send_json_success(array('message' => __('Logs cleared successfully', 'clarity-aws-ghl')));
    }
    
    public function ajax_test_certificate_endpoint() {
        check_ajax_referer('clarity_certificate_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $endpoint = sanitize_url($_POST['endpoint']);
        
        if (empty($endpoint)) {
            wp_send_json_error(array('message' => __('No endpoint URL provided', 'clarity-aws-ghl')));
        }
        
        // Test data for certificate generation
        $test_data = array(
            'recipient_name' => 'Test Student',
            'course_title' => 'Test Course',
            'completion_date' => date('Y-m-d'),
            'tier_level' => 1,
            'user_id' => 999,
            'course_id' => 1
        );
        
        // Make HTTP request to Lambda endpoint
        $response = wp_remote_post($endpoint, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($test_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Failed to connect to endpoint: ', 'clarity-aws-ghl') . $response->get_error_message()));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error(array('message' => sprintf(__('Endpoint returned error code %d: %s', 'clarity-aws-ghl'), $response_code, $response_body)));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON response from endpoint', 'clarity-aws-ghl')));
        }
        
        if (isset($data['certificate_url'])) {
            wp_send_json_success(array('message' => sprintf(__('Test successful! Certificate generated: %s', 'clarity-aws-ghl'), $data['certificate_url'])));
        } else {
            wp_send_json_error(array('message' => __('Endpoint responded but did not return certificate_url', 'clarity-aws-ghl')));
        }
    }
    
    public function ajax_generate_user_certificate() {
        check_ajax_referer('clarity_progress_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $course_id = intval($_POST['course_id']);
        if (!$course_id) {
            wp_send_json_error('Invalid course ID');
        }
        
        // Verify the course is completed
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $enrollment = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$enrollments_table} 
            WHERE user_id = %d AND course_id = %d AND progress_percentage >= 100
        ", $user_id, $course_id));
        
        if (!$enrollment) {
            wp_send_json_error('Course not completed');
        }
        
        // Check if certificate already exists (unless force regenerate)
        $force_regenerate = isset($_POST['force_regenerate']) && $_POST['force_regenerate'] === 'true';
        if (!empty($enrollment->certificate_url) && !$force_regenerate) {
            wp_send_json_success(array(
                'message' => 'Certificate already exists',
                'certificate_url' => $enrollment->certificate_url
            ));
            return;
        }
        
        // Generate certificate using the certificate manager
        if (class_exists('Clarity_AWS_GHL_Certificate_Manager')) {
            $cert_manager = new Clarity_AWS_GHL_Certificate_Manager();
            $result = $cert_manager->generate_certificate($user_id, $course_id);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => 'Certificate generated successfully',
                    'certificate_url' => $result['certificate_url'],
                    'certificate_number' => $result['certificate_number']
                ));
            } else {
                wp_send_json_error($result['error']);
            }
        } else {
            wp_send_json_error('Certificate manager not available');
        }
    }
    
    public function ajax_fix_database_schema() {
        check_ajax_referer('clarity_database_fix_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'clarity-aws-ghl')));
        }
        
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        // Check if column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$enrollments_table} LIKE 'certificate_number'");
        
        if (!empty($column_exists)) {
            wp_send_json_success(array('message' => __('Certificate_number column already exists', 'clarity-aws-ghl')));
            return;
        }
        
        // Add the missing column
        $result = $wpdb->query("ALTER TABLE {$enrollments_table} ADD COLUMN certificate_number varchar(50) DEFAULT NULL AFTER certificate_url");
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to add certificate_number column: ', 'clarity-aws-ghl') . $wpdb->last_error));
            return;
        }
        
        // Add index
        $wpdb->query("ALTER TABLE {$enrollments_table} ADD INDEX certificate_number (certificate_number)");
        
        // Also increase certificate_url field size for long S3 URLs
        $wpdb->query("ALTER TABLE {$enrollments_table} MODIFY COLUMN certificate_url TEXT");
        
        wp_send_json_success(array('message' => __('Certificate_number column added and certificate_url field expanded successfully! Page will reload...', 'clarity-aws-ghl')));
    }
    
    public function ajax_test_database_operations() {
        check_ajax_referer('clarity_database_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'clarity-aws-ghl')));
        }
        
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        $user_id = get_current_user_id();
        $course_id = 1; // Test with course 1
        
        $results = array();
        
        // Test 1: Check if enrollment exists
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$enrollments_table} WHERE user_id = %d AND course_id = %d
        ", $user_id, $course_id));
        
        if ($existing) {
            $results[] = "✓ Found existing enrollment (ID: {$existing->id})";
            
            // Test 2: Try updating existing enrollment
            $update_result = $wpdb->update(
                $enrollments_table,
                array(
                    'certificate_issued' => 1,
                    'certificate_url' => 'https://test-update.com',
                    'certificate_number' => 'TEST-123',
                    'updated_at' => current_time('mysql')
                ),
                array('user_id' => $user_id, 'course_id' => $course_id),
                array('%d', '%s', '%s', '%s'),
                array('%d', '%d')
            );
            
            if ($update_result === false) {
                $results[] = "❌ Update failed: " . $wpdb->last_error;
            } else {
                $results[] = "✓ Update successful (rows affected: $update_result)";
            }
        } else {
            $results[] = "⚠️ No enrollment found for user $user_id, course $course_id";
            
            // Test 3: Try creating enrollment
            $insert_result = $wpdb->insert(
                $enrollments_table,
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'enrollment_date' => current_time('mysql'),
                    'completion_date' => current_time('mysql'),
                    'progress_percentage' => 100,
                    'certificate_issued' => 1,
                    'certificate_url' => 'https://test-insert.com',
                    'certificate_number' => 'TEST-INSERT-123',
                    'enrollment_status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($insert_result === false) {
                $results[] = "❌ Insert failed: " . $wpdb->last_error;
            } else {
                $results[] = "✓ Insert successful (ID: " . $wpdb->insert_id . ")";
            }
        }
        
        $message = implode('<br>', $results);
        wp_send_json_success(array('message' => $message));
    }
    
    public function ajax_clear_certificate_data() {
        check_ajax_referer('clarity_clear_certificate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'clarity-aws-ghl')));
        }
        
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        $progress_table = $wpdb->prefix . 'clarity_user_progress';
        $user_id = get_current_user_id();
        
        $results = array();
        
        // Clear certificate data from enrollments (keep enrollment but reset certificate fields)
        $update_result = $wpdb->update(
            $enrollments_table,
            array(
                'certificate_issued' => 0,
                'certificate_url' => null,
                'certificate_number' => null,
                'completion_date' => null,
                'progress_percentage' => 0,
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            wp_send_json_error(array('message' => 'Failed to clear certificate data: ' . $wpdb->last_error));
        }
        
        $results[] = "✓ Cleared certificate data for $update_result enrollment(s)";
        
        // Clear all progress data for this user
        $delete_result = $wpdb->delete(
            $progress_table,
            array('user_id' => $user_id),
            array('%d')
        );
        
        if ($delete_result === false) {
            $results[] = "⚠️ Warning: Could not clear progress data: " . $wpdb->last_error;
        } else {
            $results[] = "✓ Cleared $delete_result lesson progress record(s)";
        }
        
        $results[] = "✓ Your account remains intact with admin privileges";
        $results[] = "✓ You can now re-enroll and generate fresh certificates";
        
        $message = implode('<br>', $results);
        wp_send_json_success(array('message' => $message . '<br><br>🔄 Page will reload in 2 seconds...'));
    }
    
    public function ajax_view_certificate_logs() {
        check_ajax_referer('clarity_logs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'clarity-aws-ghl')));
        }
        
        // Read PHP error log to capture error_log() calls
        $logs = array();
        
        // Try different possible log locations
        $log_files = array(
            ini_get('error_log'),
            ABSPATH . 'wp-content/debug.log',
            '/tmp/php_errors.log',
            '/var/log/php_errors.log'
        );
        
        $found_logs = false;
        
        foreach ($log_files as $log_file) {
            if ($log_file && file_exists($log_file) && is_readable($log_file)) {
                $content = file_get_contents($log_file);
                
                // Filter for certificate-related logs only
                $lines = explode("\n", $content);
                $certificate_lines = array_filter($lines, function($line) {
                    return strpos($line, 'Certificate Manager') !== false;
                });
                
                if (!empty($certificate_lines)) {
                    $logs[] = "<strong>From: $log_file</strong>";
                    $logs = array_merge($logs, array_slice(array_reverse($certificate_lines), 0, 20)); // Last 20 entries
                    $found_logs = true;
                    break;
                }
            }
        }
        
        // Check for stored debug information
        $last_error = get_option('clarity_last_certificate_error', null);
        
        if ($last_error) {
            $logs[] = "<strong style='color: red;'>LAST CERTIFICATE ERROR:</strong>";
            $logs[] = "<strong>Timestamp:</strong> " . $last_error['timestamp'];
            $logs[] = "<strong>User ID:</strong> " . $last_error['user_id'];
            $logs[] = "<strong>Course ID:</strong> " . $last_error['course_id'];
            $logs[] = "<strong>Certificate URL from Lambda:</strong> " . $last_error['certificate_url'];
            $logs[] = "<strong>Certificate Number from Lambda:</strong> " . $last_error['certificate_number'];
            $logs[] = "<strong>Full Lambda Response:</strong>";
            $logs[] = "<pre style='background: #f0f0f0; padding: 10px; border-radius: 4px; font-size: 11px;'>" . 
                     htmlspecialchars(json_encode($last_error['lambda_response'], JSON_PRETTY_PRINT)) . "</pre>";
            $logs[] = "";
        }
        
        if (!$found_logs && !$last_error) {
            // If no logs found, create a mock log entry to test logging
            error_log("Certificate Manager: Test log entry at " . current_time('mysql'));
            $logs[] = "No certificate logs found. Logs may not be enabled.";
            $logs[] = "Created test log entry. Try regenerating certificate again.";
            $logs[] = "";
            $logs[] = "To enable logging, add to wp-config.php:";
            $logs[] = "define('WP_DEBUG', true);";
            $logs[] = "define('WP_DEBUG_LOG', true);";
        }
        
        $logs_html = implode('<br>', $logs);
        wp_send_json_success(array('logs' => $logs_html));
    }
    
    public function ajax_fix_url_field_size() {
        check_ajax_referer('clarity_fix_url_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'clarity-aws-ghl')));
        }
        
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        // Expand certificate_url field from varchar(500) to TEXT
        $result = $wpdb->query("ALTER TABLE {$enrollments_table} MODIFY COLUMN certificate_url TEXT");
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to expand certificate_url field: ' . $wpdb->last_error));
        }
        
        wp_send_json_success(array('message' => 'Certificate URL field expanded to TEXT successfully! Now try regenerating your certificate.'));
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if configuration is complete
        if (!$this->is_configured()) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __('AWS GHL Integration needs configuration. <a href="%s">Configure now</a>', 'clarity-aws-ghl'),
                admin_url('admin.php?page=clarity-aws-ghl')
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check if plugin is properly configured
     */
    public function is_configured() {
        $s3_configured = !empty(get_option('clarity_s3_bucket_name')) &&
                        !empty(get_option('clarity_s3_access_key')) &&
                        !empty(get_option('clarity_s3_secret_key'));
        
        return $s3_configured;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->database->create_tables();
        
        // Create course database tables
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $db_courses->create_course_tables();
        
        // Force database migration to add missing columns
        $this->force_database_migration();
        
        // Set default options
        $this->set_default_options();
        
        // Create MainPage if it doesn't exist
        $this->create_main_page();
        
        // Flush rewrite rules for custom post types
        flush_rewrite_rules();
        
        // Log activation
        error_log('Clarity AWS GHL Integration plugin activated');
    }
    
    /**
     * Force database migration to add missing columns
     */
    private function force_database_migration() {
        global $wpdb;
        
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        // Check if course_icon column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$courses_table} LIKE 'course_icon'");
        
        if (empty($column_exists)) {
            // Add course_icon column
            $result = $wpdb->query("ALTER TABLE {$courses_table} ADD COLUMN course_icon varchar(50) NOT NULL DEFAULT 'bi-mortarboard' AFTER course_price");
            error_log('Force migration: Added course_icon column. Result: ' . $result);
            
            if ($result === false) {
                error_log('Force migration: Failed to add course_icon column. Error: ' . $wpdb->last_error);
            }
        } else {
            error_log('Force migration: course_icon column already exists');
        }
        
        // Check if certificate_number column exists in enrollments table
        $cert_column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$enrollments_table} LIKE 'certificate_number'");
        
        if (empty($cert_column_exists)) {
            // Add certificate_number column
            $result = $wpdb->query("ALTER TABLE {$enrollments_table} ADD COLUMN certificate_number varchar(50) DEFAULT NULL AFTER certificate_url");
            error_log('Force migration: Added certificate_number column. Result: ' . $result);
            
            if ($result !== false) {
                // Add index for certificate_number
                $wpdb->query("ALTER TABLE {$enrollments_table} ADD INDEX certificate_number (certificate_number)");
                error_log('Force migration: Added certificate_number index');
            } else {
                error_log('Force migration: Failed to add certificate_number column. Error: ' . $wpdb->last_error);
            }
        } else {
            error_log('Force migration: certificate_number column already exists');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Clarity AWS GHL Integration plugin deactivated');
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        $database = new Clarity_AWS_GHL_Database();
        $database->drop_tables();
        
        // Remove course database tables
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $db_courses->drop_course_tables();
        
        // Remove options
        $options = array(
            'clarity_s3_bucket_name',
            'clarity_s3_region',
            'clarity_s3_access_key',
            'clarity_s3_secret_key',
            'clarity_s3_delete_local',
            'clarity_ghl_webhook_secret',
            'clarity_ghl_webhook_enabled',
            'clarity_ghl_create_contacts',
            'clarity_ghl_notification_email',
            'clarity_debug_mode',
            'clarity_log_retention_days',
            'clarity_ghl_webhook_logs',
            'clarity_aws_ghl_course_db_version',
            'clarity_certificate_enabled',
            'clarity_certificate_auto_generate',
            'clarity_certificate_lambda_endpoint'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Log uninstall
        error_log('Clarity AWS GHL Integration plugin uninstalled');
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'clarity_s3_region' => 'us-east-1',
            'clarity_s3_delete_local' => false,
            'clarity_ghl_webhook_enabled' => true,
            'clarity_ghl_create_contacts' => true,
            'clarity_debug_mode' => false,
            'clarity_log_retention_days' => 30,
            'clarity_certificate_enabled' => true,
            'clarity_certificate_auto_generate' => true,
            'clarity_certificate_lambda_endpoint' => ''
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Create MainPage with clarity_main_page shortcode
     */
    private function create_main_page() {
        // Check if page already exists
        $existing_page = get_page_by_path('mainpage');
        
        if ($existing_page) {
            error_log('MainPage already exists with ID: ' . $existing_page->ID);
            return $existing_page->ID;
        }
        
        // Create the page
        $page_data = array(
            'post_title'    => 'MainPage',
            'post_name'     => 'mainpage',
            'post_content'  => '[clarity_main_page]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            error_log('MainPage created successfully with ID: ' . $page_id);
            
            // Optionally set it as the homepage
            // update_option('page_on_front', $page_id);
            // update_option('show_on_front', 'page');
            
            return $page_id;
        } else {
            error_log('Error creating MainPage: ' . $page_id->get_error_message());
            return false;
        }
    }
    
    /**
     * Create course page for course viewer
     */
    private function create_course_page() {
        // Check if page already exists
        $existing_page = get_page_by_path('course');
        
        if ($existing_page) {
            error_log('Course page already exists with ID: ' . $existing_page->ID);
            return $existing_page->ID;
        }
        
        // Create the page
        $page_data = array(
            'post_title'    => 'Course',
            'post_name'     => 'course',
            'post_content'  => '<!-- Course content handled by template -->',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
            'meta_input'    => array(
                '_wp_page_template' => 'page-course.php'
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            error_log('Course page created successfully with ID: ' . $page_id);
            return $page_id;
        } else {
            error_log('Error creating Course page: ' . $page_id->get_error_message());
            return false;
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
     * Get plugin info
     */
    public function get_plugin_info() {
        $s3_configured = $this->s3_integration ? $this->s3_integration->is_configured() : false;
        $webhook_url = $this->ghl_webhook ? $this->ghl_webhook->get_webhook_url() : rest_url('clarity-ghl/v1/webhook');
        
        return array(
            'version' => CLARITY_AWS_GHL_VERSION,
            'plugin_dir' => CLARITY_AWS_GHL_PLUGIN_DIR,
            'plugin_url' => CLARITY_AWS_GHL_PLUGIN_URL,
            'is_configured' => $this->is_configured(),
            's3_status' => array(
                'connected' => $s3_configured,
                'bucket' => get_option('clarity_s3_bucket_name', '')
            ),
            'webhook_url' => $webhook_url
        );
    }
    
    /**
     * Render hero background settings page
     */
    public function render_hero_background_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'clarity_hero_bg_settings')) {
            $bg_type = sanitize_text_field($_POST['bg_type']);
            $custom_image = esc_url_raw($_POST['custom_image']);
            $image_position = sanitize_text_field($_POST['image_position']);
            $hero_title = sanitize_text_field($_POST['hero_title']);
            $hero_description = sanitize_textarea_field($_POST['hero_description']);
            $hero_darkness = intval($_POST['hero_darkness']);
            $hero_darkness = max(0, min(100, $hero_darkness)); // Ensure between 0 and 100
            
            // Validate background type
            if (in_array($bg_type, array('default', 'slideshow', 'custom'))) {
                update_option('clarity_hero_bg_type', $bg_type);
                update_option('clarity_hero_custom_image', $custom_image);
                update_option('clarity_hero_image_position', $image_position);
                update_option('clarity_hero_title', $hero_title);
                update_option('clarity_hero_description', $hero_description);
                update_option('clarity_hero_darkness', $hero_darkness);
                
                echo '<div class="notice notice-success"><p>Hero settings saved successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid background type selected.</p></div>';
            }
        }
        
        // Get current settings
        $bg_type = get_option('clarity_hero_bg_type', 'default');
        $custom_image = get_option('clarity_hero_custom_image', '');
        $image_position = get_option('clarity_hero_image_position', 'center center');
        $hero_title = get_option('clarity_hero_title', 'Transform Your Skills with Our Course Platform');
        $hero_description = get_option('clarity_hero_description', 'Join thousands of students who are mastering new skills through our comprehensive three-tier learning system. From free introductory content to premium mentorship programs.');
        $hero_darkness = get_option('clarity_hero_darkness', 80); // Default 80% darkness
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>Configure the hero section text content and background settings for your main page.</p>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('clarity_hero_bg_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Background Type</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="bg_type" value="default" <?php checked($bg_type, 'default'); ?>>
                                    Default (Animated Shapes)
                                </label><br>
                                
                                <label>
                                    <input type="radio" name="bg_type" value="slideshow" <?php checked($bg_type, 'slideshow'); ?>>
                                    Home Slide Presentation
                                </label><br>
                                <p class="description">Images will randomly slide from different directions every 5 seconds using the Netlify image inventory.</p>
                                
                                <label>
                                    <input type="radio" name="bg_type" value="custom" <?php checked($bg_type, 'custom'); ?>>
                                    Custom Image
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Hero Title</th>
                        <td>
                            <input type="text" name="hero_title" value="<?php echo esc_attr($hero_title); ?>" class="regular-text" placeholder="Transform Your Skills with Our Course Platform">
                            <p class="description">The main headline text for your hero section.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Hero Description</th>
                        <td>
                            <textarea name="hero_description" rows="3" class="large-text" placeholder="Join thousands of students who are mastering new skills..."><?php echo esc_textarea($hero_description); ?></textarea>
                            <p class="description">The description text that appears below the hero title.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Image Darkness</th>
                        <td>
                            <label for="hero_darkness">
                                <input type="range" id="hero_darkness" name="hero_darkness" min="0" max="100" value="<?php echo esc_attr($hero_darkness); ?>" style="width: 300px;">
                                <span id="darkness_value"><?php echo esc_html($hero_darkness); ?>%</span>
                            </label>
                            <p class="description">Adjust how dark the background images appear (0% = no darkening, 100% = completely black). Applies to slideshow and custom images.</p>
                            <div id="darkness_preview" style="margin-top: 10px; width: 300px; height: 100px; background: url('<?php echo $custom_image ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzY2NiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmaWxsPSIjZmZmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+UHJldmlldyBBcmVhPC90ZXh0Pjwvc3ZnPg=='; ?>') center/cover; position: relative;">
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, <?php echo $hero_darkness / 100; ?>);"></div>
                                <div style="position: relative; color: white; padding: 20px; text-align: center; font-weight: bold;">Sample Text</div>
                            </div>
                        </td>
                    </tr>
                    
                    <tr id="custom-image-row" style="<?php echo $bg_type !== 'custom' ? 'display:none;' : ''; ?>">
                        <th scope="row">Custom Image URL</th>
                        <td>
                            <input type="url" name="custom_image" value="<?php echo esc_attr($custom_image); ?>" class="regular-text" placeholder="https://example.com/image.jpg">
                            <p class="description">Enter the URL of your custom background image.</p>
                            
                            <?php if ($custom_image): ?>
                                <div style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($custom_image); ?>" alt="Current background" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;">
                                    <p class="description">Current background image</p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr id="image-position-row" style="<?php echo $bg_type !== 'custom' ? 'display:none;' : ''; ?>">
                        <th scope="row">Image Position</th>
                        <td>
                            <select name="image_position">
                                <option value="left top" <?php selected($image_position, 'left top'); ?>>Top Left</option>
                                <option value="center top" <?php selected($image_position, 'center top'); ?>>Top Center</option>
                                <option value="right top" <?php selected($image_position, 'right top'); ?>>Top Right</option>
                                <option value="left center" <?php selected($image_position, 'left center'); ?>>Center Left</option>
                                <option value="center center" <?php selected($image_position, 'center center'); ?>>Center</option>
                                <option value="right center" <?php selected($image_position, 'right center'); ?>>Center Right</option>
                                <option value="left bottom" <?php selected($image_position, 'left bottom'); ?>>Bottom Left</option>
                                <option value="center bottom" <?php selected($image_position, 'center bottom'); ?>>Bottom Center</option>
                                <option value="right bottom" <?php selected($image_position, 'right bottom'); ?>>Bottom Right</option>
                            </select>
                            <p class="description">Choose how the background image should be positioned.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Hero Settings'); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('input[name="bg_type"]').change(function() {
                    var bgType = $(this).val();
                    if (bgType === 'custom') {
                        $('#custom-image-row, #image-position-row').show();
                    } else {
                        $('#custom-image-row, #image-position-row').hide();
                    }
                });
                
                // Darkness slider real-time preview
                $('#hero_darkness').on('input', function() {
                    var darkness = $(this).val();
                    $('#darkness_value').text(darkness + '%');
                    
                    // Update preview overlay
                    $('#darkness_preview > div').first().css('background', 'rgba(0, 0, 0, ' + (darkness / 100) + ')');
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render about page
     */
    public function render_about_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'clarity_about_settings')) {
            $about_title = sanitize_text_field($_POST['about_title']);
            $about_description = sanitize_textarea_field($_POST['about_description']);
            $about_feature_1 = sanitize_text_field($_POST['about_feature_1']);
            $about_feature_2 = sanitize_text_field($_POST['about_feature_2']);
            $about_feature_3 = sanitize_text_field($_POST['about_feature_3']);
            $about_feature_4 = sanitize_text_field($_POST['about_feature_4']);
            $about_image = sanitize_text_field($_POST['about_image']);
            
            update_option('clarity_about_title', $about_title);
            update_option('clarity_about_description', $about_description);
            update_option('clarity_about_feature_1', $about_feature_1);
            update_option('clarity_about_feature_2', $about_feature_2);
            update_option('clarity_about_feature_3', $about_feature_3);
            update_option('clarity_about_feature_4', $about_feature_4);
            update_option('clarity_about_image', $about_image);
            
            echo '<div class="notice notice-success"><p>About Us settings saved successfully!</p></div>';
        }
        
        // Get current settings
        $about_title = get_option('clarity_about_title', 'Innovative Learning for a Skills-First World');
        $about_description = get_option('clarity_about_description', 'Our comprehensive three-tier learning system is designed to take you from beginner to expert. Whether you\'re just starting out or looking to advance your skills, we have the perfect program for you.');
        $about_feature_1 = get_option('clarity_about_feature_1', 'Free introductory courses to get you started');
        $about_feature_2 = get_option('clarity_about_feature_2', 'Core product with comprehensive training materials');
        $about_feature_3 = get_option('clarity_about_feature_3', 'Premium access with personal mentorship');
        $about_feature_4 = get_option('clarity_about_feature_4', 'Progress tracking and certificates');
        $about_image = get_option('clarity_about_image', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('About Us Settings', 'clarity-aws-ghl'); ?></h1>
            
            <p><?php _e('Configure the About Us section content that appears on the main page.', 'clarity-aws-ghl'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('clarity_about_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Section Title</th>
                        <td>
                            <input type="text" name="about_title" value="<?php echo esc_attr($about_title); ?>" class="regular-text" />
                            <p class="description">The main heading for the About Us section.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Description</th>
                        <td>
                            <textarea name="about_description" rows="4" cols="50" class="large-text"><?php echo esc_textarea($about_description); ?></textarea>
                            <p class="description">The descriptive text that explains your learning system.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Feature 1</th>
                        <td>
                            <input type="text" name="about_feature_1" value="<?php echo esc_attr($about_feature_1); ?>" class="regular-text" />
                            <p class="description">First feature bullet point.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Feature 2</th>
                        <td>
                            <input type="text" name="about_feature_2" value="<?php echo esc_attr($about_feature_2); ?>" class="regular-text" />
                            <p class="description">Second feature bullet point.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Feature 3</th>
                        <td>
                            <input type="text" name="about_feature_3" value="<?php echo esc_attr($about_feature_3); ?>" class="regular-text" />
                            <p class="description">Third feature bullet point.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Feature 4</th>
                        <td>
                            <input type="text" name="about_feature_4" value="<?php echo esc_attr($about_feature_4); ?>" class="regular-text" />
                            <p class="description">Fourth feature bullet point.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">About Us Image</th>
                        <td>
                            <input type="hidden" name="about_image" id="about_image" value="<?php echo esc_attr($about_image); ?>" />
                            <div id="about_image_preview" style="margin-bottom: 10px;">
                                <?php if ($about_image): ?>
                                    <img src="<?php echo esc_url($about_image); ?>" alt="About Us Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;">
                                <?php else: ?>
                                    <div style="width: 300px; height: 200px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                        No image selected
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="upload_about_image" class="button button-secondary">
                                <?php echo $about_image ? 'Change Image' : 'Upload Image'; ?>
                            </button>
                            <button type="button" id="remove_about_image" class="button button-secondary" style="<?php echo !$about_image ? 'display:none;' : ''; ?>">
                                Remove Image
                            </button>
                            <p class="description">Upload an image for the About Us section. Recommended size: 600x400px or larger.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save About Us Settings'); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                var mediaUploader;
                
                $('#upload_about_image').click(function(e) {
                    e.preventDefault();
                    
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    mediaUploader = wp.media({
                        title: 'Choose About Us Image',
                        button: {
                            text: 'Choose Image'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#about_image').val(attachment.url);
                        $('#about_image_preview').html('<img src="' + attachment.url + '" alt="About Us Preview" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;">');
                        $('#upload_about_image').text('Change Image');
                        $('#remove_about_image').show();
                    });
                    
                    mediaUploader.open();
                });
                
                $('#remove_about_image').click(function(e) {
                    e.preventDefault();
                    $('#about_image').val('');
                    $('#about_image_preview').html('<div style="width: 300px; height: 200px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #666;">No image selected</div>');
                    $('#upload_about_image').text('Upload Image');
                    $(this).hide();
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render certificate settings page
     */
    public function render_certificate_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'clarity_certificate_settings')) {
            $lambda_endpoint = esc_url_raw($_POST['certificate_lambda_endpoint']);
            $certificate_enabled = isset($_POST['certificate_enabled']) ? 1 : 0;
            $auto_generate = isset($_POST['certificate_auto_generate']) ? 1 : 0;
            
            update_option('clarity_certificate_lambda_endpoint', $lambda_endpoint);
            update_option('clarity_certificate_enabled', $certificate_enabled);
            update_option('clarity_certificate_auto_generate', $auto_generate);
            
            echo '<div class="notice notice-success"><p>Certificate settings saved successfully!</p></div>';
        }
        
        // Get current settings
        $lambda_endpoint = get_option('clarity_certificate_lambda_endpoint', '');
        $certificate_enabled = get_option('clarity_certificate_enabled', 1);
        $auto_generate = get_option('clarity_certificate_auto_generate', 1);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Certificate Settings', 'clarity-aws-ghl'); ?></h1>
            <p><?php _e('Configure AWS Lambda certificate generation settings for course completion certificates.', 'clarity-aws-ghl'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('clarity_certificate_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="certificate_enabled"><?php _e('Enable Certificates', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="certificate_enabled" name="certificate_enabled" value="1" <?php checked($certificate_enabled, 1); ?>>
                            <label for="certificate_enabled"><?php _e('Enable certificate generation system', 'clarity-aws-ghl'); ?></label>
                            <p class="description"><?php _e('Turn certificate generation on or off globally.', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="certificate_lambda_endpoint"><?php _e('AWS Lambda Endpoint', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="certificate_lambda_endpoint" name="certificate_lambda_endpoint" 
                                   value="<?php echo esc_attr($lambda_endpoint); ?>" class="regular-text" 
                                   placeholder="https://api.amazonaws.com/prod/certificates">
                            <p class="description">
                                <?php _e('Enter your AWS API Gateway endpoint URL for certificate generation. Example: https://xpee6m6zo2.execute-api.us-east-1.amazonaws.com/prod/certificates', 'clarity-aws-ghl'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="certificate_auto_generate"><?php _e('Auto-Generate Certificates', 'clarity-aws-ghl'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="certificate_auto_generate" name="certificate_auto_generate" value="1" <?php checked($auto_generate, 1); ?>>
                            <label for="certificate_auto_generate"><?php _e('Automatically generate certificates when courses are completed', 'clarity-aws-ghl'); ?></label>
                            <p class="description"><?php _e('When enabled, certificates will be generated automatically when students complete 100% of a course.', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Certificate System Status', 'clarity-aws-ghl'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Lambda Endpoint Status', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <?php if (!empty($lambda_endpoint)): ?>
                                <span style="color: green;">✓ Configured</span>
                                <p class="description"><?php echo esc_html($lambda_endpoint); ?></p>
                            <?php else: ?>
                                <span style="color: red;">✗ Not configured</span>
                                <p class="description"><?php _e('Please enter your AWS Lambda endpoint URL above.', 'clarity-aws-ghl'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Database Schema', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <?php
                            global $wpdb;
                            $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
                            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$enrollments_table} LIKE 'certificate_number'");
                            ?>
                            <?php if (!empty($column_exists)): ?>
                                <span style="color: green;">✓ Certificate fields present</span>
                                <p class="description"><?php _e('Database schema includes certificate_number, certificate_url, and certificate_issued fields.', 'clarity-aws-ghl'); ?></p>
                            <?php else: ?>
                                <span style="color: red;">✗ Certificate fields missing</span>
                                <p class="description"><?php _e('Database schema needs to be updated.', 'clarity-aws-ghl'); ?></p>
                                <button type="button" id="fix-database-schema" class="button button-secondary" style="margin-top: 10px;">
                                    <?php _e('Fix Database Schema', 'clarity-aws-ghl'); ?>
                                </button>
                                <div id="fix-database-result" style="margin-top: 10px;"></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Certificate Manager', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <?php if (class_exists('Clarity_AWS_GHL_Certificate_Manager')): ?>
                                <span style="color: green;">✓ Certificate manager loaded</span>
                                <p class="description"><?php _e('Certificate generation system is ready.', 'clarity-aws-ghl'); ?></p>
                            <?php else: ?>
                                <span style="color: red;">✗ Certificate manager not found</span>
                                <p class="description"><?php _e('Certificate manager class is not loaded.', 'clarity-aws-ghl'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Database Debug Information', 'clarity-aws-ghl'); ?></h2>
                <p><?php _e('Debug information for certificate database issues:', 'clarity-aws-ghl'); ?></p>
                
                <?php
                // Show current enrollments for current user
                $current_user_id = get_current_user_id();
                global $wpdb;
                $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
                $user_enrollments = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$enrollments_table} WHERE user_id = %d
                ", $current_user_id));
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Current User Enrollments', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <?php if ($user_enrollments): ?>
                                <table border="1" style="border-collapse: collapse; font-size: 12px;">
                                    <tr><th>ID</th><th>Course ID</th><th>Progress %</th><th>Certificate URL</th><th>Certificate #</th><th>Status</th></tr>
                                    <?php foreach ($user_enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo $enrollment->id; ?></td>
                                            <td><?php echo $enrollment->course_id; ?></td>
                                            <td><?php echo $enrollment->progress_percentage; ?></td>
                                            <td><?php echo substr($enrollment->certificate_url ?? 'NULL', 0, 30) . '...'; ?></td>
                                            <td><?php echo $enrollment->certificate_number ?? 'NULL'; ?></td>
                                            <td><?php echo $enrollment->enrollment_status; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php else: ?>
                                <span style="color: red;">No enrollments found for current user (ID: <?php echo $current_user_id; ?>)</span>
                                <p class="description">This might be why certificate generation is failing.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Manual Database Test', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <button type="button" id="test-database-operations" class="button button-secondary">
                                <?php _e('Test Database Operations', 'clarity-aws-ghl'); ?>
                            </button>
                            <p class="description"><?php _e('Test database insert/update operations to debug certificate save issues.', 'clarity-aws-ghl'); ?></p>
                            <div id="database-test-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Clear Certificate Data', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <button type="button" id="clear-certificate-data" class="button button-secondary" style="background: #ffc107; color: #212529;">
                                <?php _e('Clear My Certificate Data', 'clarity-aws-ghl'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Reset certificate fields for your account only. Keeps admin access intact.', 'clarity-aws-ghl'); ?>
                            </p>
                            <div id="clear-certificate-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Fix URL Field Size', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <button type="button" id="fix-url-field-size" class="button button-primary">
                                <?php _e('Expand certificate_url Field', 'clarity-aws-ghl'); ?>
                            </button>
                            <p class="description">
                                <?php _e('S3 URLs are too long for the current varchar(500) field. This expands it to TEXT.', 'clarity-aws-ghl'); ?>
                            </p>
                            <div id="fix-url-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Debug Logs', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <button type="button" id="view-certificate-logs" class="button button-secondary">
                                <?php _e('View Certificate Logs', 'clarity-aws-ghl'); ?>
                            </button>
                            <p class="description">
                                <?php _e('View recent certificate-related error logs to debug issues.', 'clarity-aws-ghl'); ?>
                            </p>
                            <div id="certificate-logs-result" style="margin-top: 10px; max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; font-family: monospace; font-size: 12px; border: 1px solid #ddd;"></div>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Test Certificate Generation', 'clarity-aws-ghl'); ?></h2>
                <p><?php _e('Use this section to test your certificate configuration:', 'clarity-aws-ghl'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Test Endpoint', 'clarity-aws-ghl'); ?></th>
                        <td>
                            <button type="button" id="test-certificate-endpoint" class="button button-secondary" 
                                    <?php echo empty($lambda_endpoint) ? 'disabled' : ''; ?>>
                                <?php _e('Test Lambda Endpoint', 'clarity-aws-ghl'); ?>
                            </button>
                            <p class="description"><?php _e('Send a test request to your Lambda endpoint to verify it\'s working.', 'clarity-aws-ghl'); ?></p>
                            <div id="test-certificate-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Certificate Settings'); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-certificate-endpoint').click(function() {
                    var button = $(this);
                    var resultDiv = $('#test-certificate-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing certificate endpoint...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_test_certificate_endpoint',
                            nonce: '<?php echo wp_create_nonce('clarity_certificate_test_nonce'); ?>',
                            endpoint: $('#certificate_lambda_endpoint').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;">✓ ' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ ' + response.data.message + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ Failed to test endpoint</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Lambda Endpoint');
                        }
                    });
                });
                
                $('#fix-database-schema').click(function() {
                    var button = $(this);
                    var resultDiv = $('#fix-database-result');
                    
                    button.prop('disabled', true).text('Fixing...');
                    resultDiv.html('<p>Updating database schema...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_fix_database_schema',
                            nonce: '<?php echo wp_create_nonce('clarity_database_fix_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;">✓ ' + response.data.message + '</div>');
                                button.remove();
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ ' + response.data.message + '</div>');
                                button.prop('disabled', false).text('Fix Database Schema');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ Failed to fix database schema</div>');
                            button.prop('disabled', false).text('Fix Database Schema');
                        }
                    });
                });
                
                $('#test-database-operations').click(function() {
                    var button = $(this);
                    var resultDiv = $('#database-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Running database tests...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_test_database_operations',
                            nonce: '<?php echo wp_create_nonce('clarity_database_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;">' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">' + response.data.message + '</div>');
                            }
                            button.prop('disabled', false).text('Test Database Operations');
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">Failed to test database operations</div>');
                            button.prop('disabled', false).text('Test Database Operations');
                        }
                    });
                });
                
                $('#clear-certificate-data').click(function() {
                    if (!confirm('Are you sure you want to clear your certificate data? This will reset your enrollment progress.')) {
                        return;
                    }
                    
                    var button = $(this);
                    var resultDiv = $('#clear-certificate-result');
                    
                    button.prop('disabled', true).text('Clearing...');
                    resultDiv.html('<p>Clearing certificate data...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_clear_certificate_data',
                            nonce: '<?php echo wp_create_nonce('clarity_clear_certificate_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;">' + response.data.message + '</div>');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">' + response.data.message + '</div>');
                            }
                            button.prop('disabled', false).text('Clear My Certificate Data');
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">Failed to clear certificate data</div>');
                            button.prop('disabled', false).text('Clear My Certificate Data');
                        }
                    });
                });
                
                $('#fix-url-field-size').click(function() {
                    var button = $(this);
                    var resultDiv = $('#fix-url-result');
                    
                    button.prop('disabled', true).text('Fixing...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_fix_url_field_size',
                            nonce: '<?php echo wp_create_nonce('clarity_fix_url_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;">✓ ' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ ' + response.data.message + '</div>');
                            }
                            button.prop('disabled', false).text('Expand certificate_url Field');
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red; padding: 10px; background: #fdf0f0; border: 1px solid #f44336;">✗ Failed to expand field</div>');
                            button.prop('disabled', false).text('Expand certificate_url Field');
                        }
                    });
                });
                
                $('#view-certificate-logs').click(function() {
                    var button = $(this);
                    var resultDiv = $('#certificate-logs-result');
                    
                    button.prop('disabled', true).text('Loading...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clarity_view_certificate_logs',
                            nonce: '<?php echo wp_create_nonce('clarity_logs_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html(response.data.logs);
                            } else {
                                resultDiv.html('<div style="color: red;">Failed to load logs: ' + response.data.message + '</div>');
                            }
                            button.prop('disabled', false).text('View Certificate Logs');
                        },
                        error: function() {
                            resultDiv.html('<div style="color: red;">Error loading logs</div>');
                            button.prop('disabled', false).text('View Certificate Logs');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function clarity_aws_ghl_init() {
    return Clarity_AWS_GHL_Integration::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'clarity_aws_ghl_init');

/**
 * Helper function to get the main plugin instance
 */
function clarity_aws_ghl() {
    return Clarity_AWS_GHL_Integration::get_instance();
}