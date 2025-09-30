<?php
/**
 * Frontend Scripts Manager
 *
 * Handles frontend script enqueuing and localization for user-facing features
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Frontend_Scripts {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'localize_ajax_data'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on pages that need authentication features
        if (is_page(array('student-registration', 'student-login', 'student-dashboard')) || 
            has_shortcode(get_post()->post_content ?? '', 'clarity_student_registration') ||
            has_shortcode(get_post()->post_content ?? '', 'clarity_student_login') ||
            has_shortcode(get_post()->post_content ?? '', 'clarity_student_dashboard') ||
            has_shortcode(get_post()->post_content ?? '', 'clarity_contact_form')) {
            
            wp_enqueue_script('jquery');
            wp_enqueue_script('clarity-frontend', CLARITY_AWS_GHL_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CLARITY_AWS_GHL_VERSION, true);
            wp_enqueue_style('clarity-frontend', CLARITY_AWS_GHL_PLUGIN_URL . 'assets/css/frontend.css', array(), CLARITY_AWS_GHL_VERSION);
        }
    }
    
    /**
     * Localize AJAX data for frontend
     */
    public function localize_ajax_data() {
        if (wp_script_is('clarity-frontend', 'enqueued')) {
            wp_localize_script('clarity-frontend', 'clarityAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clarity_student_registration'),
                'login_nonce' => wp_create_nonce('clarity_student_login'),
                'contact_nonce' => wp_create_nonce('clarity_contact_form'),
                'strings' => array(
                    'processing' => __('Processing...', 'clarity-aws-ghl'),
                    'success' => __('Success!', 'clarity-aws-ghl'),
                    'error' => __('Error', 'clarity-aws-ghl'),
                    'password_mismatch' => __('Passwords do not match.', 'clarity-aws-ghl'),
                    'registration_success' => __('Registration successful! Redirecting...', 'clarity-aws-ghl'),
                    'login_success' => __('Login successful! Redirecting...', 'clarity-aws-ghl'),
                )
            ));
        }
    }
}