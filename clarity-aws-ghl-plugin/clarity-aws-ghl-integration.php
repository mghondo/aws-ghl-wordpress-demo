<?php
/**
 * Plugin Name: Clarity AWS GoHighLevel Integration
 * Plugin URI: https://github.com/mghondo/aws-ghl-wordpress-demo
 * Description: Complete integration between WordPress, AWS S3, and GoHighLevel CRM. Handles webhook processing, file storage, and lead management.
 * Version: 1.0.0
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
define('CLARITY_AWS_GHL_VERSION', '1.0.0');
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
        
        // Admin includes
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-settings.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-dashboard.php';
        require_once CLARITY_AWS_GHL_PLUGIN_DIR . 'admin/class-logs.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->database = new Clarity_AWS_GHL_Database();
        $this->admin = new Clarity_AWS_GHL_Admin();
        $this->post_types = new Clarity_AWS_GHL_Post_Types();
        
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
            __('Webhook Logs', 'clarity-aws-ghl'),
            __('Webhook Logs', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-logs',
            array($this, 'admin_logs_page')
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
    
    public function admin_logs_page() {
        $logs = new Clarity_AWS_GHL_Logs();
        $logs->render();
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
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules for custom post types
        flush_rewrite_rules();
        
        // Log activation
        error_log('Clarity AWS GHL Integration plugin activated');
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
            'clarity_ghl_webhook_logs'
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
            'clarity_log_retention_days' => 30
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
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