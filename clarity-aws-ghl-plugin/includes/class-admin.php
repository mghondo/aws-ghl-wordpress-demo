<?php
/**
 * Admin Class
 *
 * Handles all admin-related functionality
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_clarity_test_s3_connection', array($this, 'ajax_test_s3_connection'));
        add_action('wp_ajax_clarity_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_clarity_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_clarity_refresh_stats', array($this, 'ajax_refresh_stats'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AWS GHL Integration', 'clarity-aws-ghl'),
            __('AWS GHL', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl',
            array($this, 'dashboard_page'),
            'dashicons-cloud',
            30
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Dashboard', 'clarity-aws-ghl'),
            __('Dashboard', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('S3 Settings', 'clarity-aws-ghl'),
            __('S3 Settings', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-s3',
            array($this, 's3_settings_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('GHL Settings', 'clarity-aws-ghl'),
            __('GHL Settings', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-ghl',
            array($this, 'ghl_settings_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Webhook Logs', 'clarity-aws-ghl'),
            __('Webhook Logs', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-logs',
            array($this, 'logs_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'clarity-aws-ghl') === false) {
            return;
        }
        
        wp_enqueue_script(
            'clarity-admin-js',
            CLARITY_AWS_GHL_URL . 'admin/js/admin.js',
            array('jquery'),
            CLARITY_AWS_GHL_VERSION,
            true
        );
        
        wp_enqueue_style(
            'clarity-admin-css',
            CLARITY_AWS_GHL_URL . 'admin/css/admin.css',
            array(),
            CLARITY_AWS_GHL_VERSION
        );
        
        wp_localize_script('clarity-admin-js', 'clarityAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clarity_ajax_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'clarity-aws-ghl'),
                'success' => __('Success', 'clarity-aws-ghl'),
                'error' => __('Error', 'clarity-aws-ghl'),
                'confirm_clear' => __('Are you sure you want to clear all logs? This action cannot be undone.', 'clarity-aws-ghl')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $dashboard = new Clarity_AWS_GHL_Dashboard();
        $dashboard->render();
    }
    
    /**
     * S3 settings page
     */
    public function s3_settings_page() {
        $settings = new Clarity_AWS_GHL_Settings();
        $settings->render_s3_page();
    }
    
    /**
     * GHL settings page
     */
    public function ghl_settings_page() {
        $settings = new Clarity_AWS_GHL_Settings();
        $settings->render_ghl_page();
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        $logs = new Clarity_AWS_GHL_Logs();
        $logs->render();
    }
    
    /**
     * AJAX: Test S3 connection
     */
    public function ajax_test_s3_connection() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $plugin = clarity_aws_ghl();
        $s3 = $plugin->get_s3_integration();
        
        if (!$s3) {
            wp_send_json_error(__('S3 integration not configured', 'clarity-aws-ghl'));
        }
        
        $result = $s3->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Test webhook endpoint
     */
    public function ajax_test_webhook() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $webhook_url = rest_url('clarity-ghl/v1/webhook');
        
        $test_data = array(
            'type' => 'ContactCreate',
            'contactId' => 'test-' . time(),
            'contact' => array(
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com'
            )
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($test_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success(__('Webhook endpoint is responding correctly', 'clarity-aws-ghl'));
        } else {
            wp_send_json_error(sprintf(__('Webhook returned status code: %d', 'clarity-aws-ghl'), $code));
        }
    }
    
    /**
     * AJAX: Clear all logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $plugin = clarity_aws_ghl();
        $database = $plugin->database;
        
        $result = $database->clear_logs();
        
        if ($result) {
            wp_send_json_success(__('All logs cleared successfully', 'clarity-aws-ghl'));
        } else {
            wp_send_json_error(__('Failed to clear logs', 'clarity-aws-ghl'));
        }
    }
    
    /**
     * AJAX: Refresh dashboard stats
     */
    public function ajax_refresh_stats() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'clarity-aws-ghl'));
        }
        
        $plugin = clarity_aws_ghl();
        $database = $plugin->database;
        $stats = $database->get_stats();
        $plugin_info = $plugin->get_plugin_info();
        
        ob_start();
        $this->render_status_cards_html($plugin_info, $stats);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Render status cards HTML for AJAX refresh
     */
    private function render_status_cards_html($plugin_info, $stats) {
        ?>
        <!-- S3 Status -->
        <div class="clarity-status-card">
            <h3><?php _e('AWS S3 Storage', 'clarity-aws-ghl'); ?></h3>
            <div class="status-value <?php echo $plugin_info['s3_status']['connected'] ? 'status-success' : 'status-error'; ?>">
                <?php echo $plugin_info['s3_status']['connected'] ? __('Connected', 'clarity-aws-ghl') : __('Disconnected', 'clarity-aws-ghl'); ?>
            </div>
            <div class="status-description">
                <?php 
                if ($plugin_info['s3_status']['connected']) {
                    printf(__('Bucket: %s', 'clarity-aws-ghl'), esc_html($plugin_info['s3_status']['bucket']));
                } else {
                    _e('Configuration required', 'clarity-aws-ghl');
                }
                ?>
            </div>
        </div>
        
        <!-- Webhook Status -->
        <div class="clarity-status-card">
            <h3><?php _e('Webhook Endpoint', 'clarity-aws-ghl'); ?></h3>
            <div class="status-value status-success">
                <?php _e('Active', 'clarity-aws-ghl'); ?>
            </div>
            <div class="status-description">
                <?php printf(__('%d webhooks today', 'clarity-aws-ghl'), $stats['todays_webhooks']); ?>
            </div>
        </div>
        
        <!-- Total Webhooks -->
        <div class="clarity-status-card">
            <h3><?php _e('Total Webhooks', 'clarity-aws-ghl'); ?></h3>
            <div class="status-value status-info">
                <?php echo number_format($stats['total_webhooks']); ?>
            </div>
            <div class="status-description">
                <?php printf(__('%d successful, %d failed', 'clarity-aws-ghl'), $stats['successful_webhooks'], $stats['failed_webhooks']); ?>
            </div>
        </div>
        
        <!-- GHL Contacts -->
        <div class="clarity-status-card">
            <h3><?php _e('GHL Contacts', 'clarity-aws-ghl'); ?></h3>
            <div class="status-value status-info">
                <?php echo number_format($stats['total_contacts']); ?>
            </div>
            <div class="status-description">
                <?php printf(__('%d new this week', 'clarity-aws-ghl'), $stats['recent_contacts']); ?>
            </div>
        </div>
        <?php
    }
}