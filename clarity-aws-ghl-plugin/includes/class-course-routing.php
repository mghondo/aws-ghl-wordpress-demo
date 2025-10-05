<?php
/**
 * Course Routing Class
 *
 * Handles intelligent routing for courses based on user status and enrollment
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Course_Routing {
    
    /**
     * Course Manager instance
     */
    private $course_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->course_manager = new Clarity_AWS_GHL_Course_Manager();
        
        // Initialize routing hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers for course routing
        add_action('wp_ajax_get_course_route', array($this, 'ajax_get_course_route'));
        add_action('wp_ajax_nopriv_get_course_route', array($this, 'ajax_get_course_route'));
        
        // Handle enrollment form submission
        add_action('admin_post_enroll_in_course', array($this, 'handle_enrollment_submission'));
        add_action('admin_post_nopriv_enroll_in_course', array($this, 'handle_enrollment_submission'));
    }
    
    /**
     * Check if user is enrolled in a course
     */
    public function is_user_enrolled($user_id, $course_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE user_id = %d AND course_id = %d 
            AND enrollment_status = 'active'",
            $user_id, $course_id
        ));
        
        return $enrollment !== null;
    }
    
    /**
     * Get user's enrollments
     */
    public function get_user_enrollments($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'clarity_course_enrollments';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT course_id FROM {$table} 
            WHERE user_id = %d AND enrollment_status = 'active'",
            $user_id
        ));
    }
    
    /**
     * Determine course card click route
     * Returns the appropriate URL based on user login and enrollment status
     */
    public function get_course_click_route($course) {
        // If user not logged in, always go to funnel
        if (!is_user_logged_in()) {
            return home_url('/funnel/' . $course->course_slug);
        }
        
        $user_id = get_current_user_id();
        
        // Check if user is enrolled in this course
        if ($this->is_user_enrolled($user_id, $course->id)) {
            // User is enrolled, go directly to course content
            return home_url('/course/' . $course->course_slug);
        } else {
            // User is logged in but not enrolled, go to funnel
            return home_url('/funnel/' . $course->course_slug);
        }
    }
    
    /**
     * Get funnel page CTA configuration
     * Returns button text, action URL, and other config based on user status
     */
    public function get_funnel_cta_config($course) {
        $config = array();
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;
        
        // Check if already enrolled
        if ($is_logged_in && $this->is_user_enrolled($user_id, $course->id)) {
            $config['type'] = 'enrolled';
            $config['button_text'] = 'Continue Learning';
            $config['button_icon'] = 'bi-play-circle';
            $config['action_url'] = home_url('/course/' . $course->course_slug);
            $config['secondary_button'] = array(
                'text' => 'My Dashboard',
                'url' => home_url('/dashboard'),
                'icon' => 'bi-speedometer2'
            );
            return $config;
        }
        
        // Configuration based on tier
        switch ($course->course_tier) {
            case 1: // Free course
                if ($is_logged_in) {
                    $config['type'] = 'free_enroll';
                    $config['button_text'] = 'Access Course Now';
                    $config['button_icon'] = 'bi-unlock';
                    $config['action'] = 'auto_enroll';
                } else {
                    $config['type'] = 'free_register';
                    $config['button_text'] = 'Start Free Course';
                    $config['button_icon'] = 'bi-rocket';
                    $config['action_url'] = home_url('/register');
                    $config['after_register'] = 'auto_enroll_tier1_and_redirect_dashboard';
                }
                break;
                
            case 2: // Mid tier ($497)
                if ($is_logged_in) {
                    $config['type'] = 'paid_checkout';
                    $config['button_text'] = 'Enroll Now - $497';
                    $config['button_icon'] = 'bi-credit-card';
                    $config['action_url'] = home_url('/checkout?course_id=' . $course->id);
                } else {
                    $config['type'] = 'paid_register';
                    $config['button_text'] = 'Enroll Now - $497';
                    $config['button_icon'] = 'bi-credit-card';
                    $config['action_url'] = home_url('/register');
                    $config['after_register'] = 'auto_enroll_tier1_and_redirect_checkout';
                    $config['checkout_course'] = $course->id;
                }
                break;
                
            case 3: // Premium tier ($1,997)
                if ($is_logged_in) {
                    $config['type'] = 'paid_checkout';
                    $config['button_text'] = 'Enroll Now - $1,997';
                    $config['button_icon'] = 'bi-credit-card';
                    $config['action_url'] = home_url('/checkout?course_id=' . $course->id);
                } else {
                    $config['type'] = 'paid_register';
                    $config['button_text'] = 'Enroll Now - $1,997';
                    $config['button_icon'] = 'bi-credit-card';
                    $config['action_url'] = home_url('/register');
                    $config['after_register'] = 'auto_enroll_tier1_and_redirect_checkout';
                    $config['checkout_course'] = $course->id;
                }
                break;
        }
        
        return $config;
    }
    
    /**
     * Build checkout cart with intelligent bundling
     */
    public function build_checkout_cart($user_id, $course_id) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        
        $cart = array(
            'courses' => array(),
            'subtotal' => 0,
            'discount' => 0,
            'discount_percentage' => 0,
            'total' => 0,
            'bundle_message' => ''
        );
        
        // Get the requested course
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$courses_table} WHERE id = %d",
            $course_id
        ));
        
        if (!$course) {
            return $cart;
        }
        
        // Check user's existing enrollments
        $has_tier1 = $this->is_user_enrolled($user_id, 1);
        $has_tier2 = $this->is_user_enrolled($user_id, 2);
        
        // Build cart based on course tier
        switch ($course->course_tier) {
            case 1: // Tier 1 (Free)
                $cart['courses'][] = $course;
                $cart['total'] = 0;
                break;
                
            case 2: // Tier 2 ($497)
                // Add Tier 1 if not enrolled
                if (!$has_tier1) {
                    $tier1 = $wpdb->get_row(
                        "SELECT * FROM {$courses_table} WHERE course_tier = 1 AND course_status = 'published' LIMIT 1"
                    );
                    if ($tier1) {
                        $cart['courses'][] = $tier1;
                    }
                }
                
                // Add Tier 2
                $cart['courses'][] = $course;
                $cart['subtotal'] = $course->course_price;
                $cart['total'] = $course->course_price;
                break;
                
            case 3: // Tier 3 ($1,997)
                $courses_added = array();
                
                // Add Tier 1 if not enrolled
                if (!$has_tier1) {
                    $tier1 = $wpdb->get_row(
                        "SELECT * FROM {$courses_table} WHERE course_tier = 1 AND course_status = 'published' LIMIT 1"
                    );
                    if ($tier1) {
                        $cart['courses'][] = $tier1;
                        $courses_added[] = 'tier1';
                    }
                }
                
                // Add Tier 2 if not enrolled
                if (!$has_tier2) {
                    $tier2 = $wpdb->get_row(
                        "SELECT * FROM {$courses_table} WHERE course_tier = 2 AND course_status = 'published' LIMIT 1"
                    );
                    if ($tier2) {
                        $cart['courses'][] = $tier2;
                        $courses_added[] = 'tier2';
                        $cart['subtotal'] += $tier2->course_price;
                    }
                }
                
                // Add Tier 3
                $cart['courses'][] = $course;
                $cart['subtotal'] += $course->course_price;
                
                // Apply bundle discount if buying Tier 2 + Tier 3
                if (in_array('tier2', $courses_added)) {
                    // 20% discount on the bundle
                    $cart['discount_percentage'] = 20;
                    $cart['discount'] = round($cart['subtotal'] * 0.20, 2);
                    $cart['total'] = round($cart['subtotal'] - $cart['discount'], 2);
                    
                    $cart['bundle_message'] = "To access Elite Empire Builder, you'll need to complete Real Estate Mastery first. Both courses are included in your purchase at a 20% discount.";
                } else {
                    // No discount, just Tier 3
                    $cart['total'] = $cart['subtotal'];
                }
                break;
        }
        
        // Ensure all numeric values are properly formatted
        $cart['subtotal'] = round($cart['subtotal'], 2);
        $cart['discount'] = round($cart['discount'], 2);
        $cart['total'] = round($cart['total'], 2);
        
        return $cart;
    }
    
    /**
     * Process enrollment after payment
     */
    public function process_post_payment_enrollment($user_id, $cart_courses, $total_paid) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        $enrollment_ids = array();
        
        foreach ($cart_courses as $course) {
            // Skip if already enrolled
            if ($this->is_user_enrolled($user_id, $course->id)) {
                continue;
            }
            
            // Determine payment status based on price
            $payment_status = ($course->course_price == 0) ? 'free' : 'paid';
            
            // Create enrollment record
            $enrollment_data = array(
                'user_id' => $user_id,
                'course_id' => $course->id,
                'enrollment_date' => current_time('mysql'),
                'enrollment_status' => 'active',
                'payment_status' => $payment_status,
                'payment_amount' => $course->course_price,
                'progress_percentage' => 0
            );
            
            $wpdb->insert($enrollments_table, $enrollment_data);
            $enrollment_ids[] = $wpdb->insert_id;
            
            // Log enrollment
            error_log("Enrolled user {$user_id} in course {$course->id} (Tier {$course->course_tier})");
        }
        
        return $enrollment_ids;
    }
    
    /**
     * AJAX: Get course route
     */
    public function ajax_get_course_route() {
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        if (!$course_id) {
            wp_send_json_error('Invalid course ID');
        }
        
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$courses_table} WHERE id = %d",
            $course_id
        ));
        
        if (!$course) {
            wp_send_json_error('Course not found');
        }
        
        $route_url = $this->get_course_click_route($course);
        
        wp_send_json_success(array(
            'url' => $route_url,
            'is_logged_in' => is_user_logged_in(),
            'is_enrolled' => is_user_logged_in() ? $this->is_user_enrolled(get_current_user_id(), $course_id) : false
        ));
    }
    
    /**
     * Handle enrollment form submission
     */
    public function handle_enrollment_submission() {
        // Verify nonce
        if (!isset($_POST['enrollment_nonce']) || !wp_verify_nonce($_POST['enrollment_nonce'], 'enroll_course')) {
            wp_die('Security check failed');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login'));
            exit;
        }
        
        $user_id = get_current_user_id();
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        if (!$course_id) {
            wp_redirect(home_url('/dashboard?error=invalid_course'));
            exit;
        }
        
        // Get course details
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$courses_table} WHERE id = %d",
            $course_id
        ));
        
        if (!$course) {
            wp_redirect(home_url('/dashboard?error=course_not_found'));
            exit;
        }
        
        // Only process if it's a free course
        if ($course->course_price == 0) {
            // Enroll the user
            $this->course_manager->enroll_user($user_id, $course_id, 'free');
            
            // Redirect to dashboard with success message
            wp_redirect(home_url('/dashboard?enrolled=success&course=' . $course->course_slug));
        } else {
            // For paid courses, redirect to checkout
            wp_redirect(home_url('/checkout?course_id=' . $course_id));
        }
        exit;
    }
}

// Initialize the class
new Clarity_AWS_GHL_Course_Routing();