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
            'clarity_aws_ghl_course_db_version'
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