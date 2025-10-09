<?php
/**
 * User Manager Class
 *
 * Handles student authentication, registration, and management for the three-tier course platform
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_User_Manager {
    
    /**
     * Database instance
     */
    private $db_courses;
    private $tables;
    
    /**
     * Course access levels
     */
    public $access_levels = array(
        1 => 'Free',      // Tier 1 - Free access
        2 => 'Core',      // Tier 2 - Core product
        3 => 'Premium'    // Tier 3 - Premium
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        $this->tables = $this->db_courses->get_table_names();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Register student role
        $this->register_student_role();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // User registration hooks
        add_action('wp_loaded', array($this, 'handle_custom_registration'));
        add_action('wp_loaded', array($this, 'handle_custom_login'));
        add_action('wp_logout', array($this, 'handle_custom_logout'));
        
        // AJAX handlers
        add_action('wp_ajax_clarity_register_student', array($this, 'ajax_register_student'));
        add_action('wp_ajax_nopriv_clarity_register_student', array($this, 'ajax_register_student'));
        add_action('wp_ajax_clarity_login_student', array($this, 'ajax_login_student'));
        add_action('wp_ajax_nopriv_clarity_login_student', array($this, 'ajax_login_student'));
        add_action('wp_ajax_clarity_contact_form', array($this, 'ajax_contact_form'));
        add_action('wp_ajax_nopriv_clarity_contact_form', array($this, 'ajax_contact_form'));
        add_action('wp_ajax_clarity_save_hero_bg_settings', array($this, 'ajax_save_hero_bg_settings'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_clarity_delete_test_user', array($this, 'ajax_delete_test_user'));
        add_action('wp_ajax_clarity_reset_user_progress', array($this, 'ajax_reset_user_progress'));
        add_action('wp_ajax_clarity_enroll_user_bulk', array($this, 'ajax_enroll_user_bulk'));
        add_action('wp_ajax_clarity_reset_demo', array($this, 'ajax_reset_demo'));
        add_action('wp_ajax_clarity_create_test_users', array($this, 'ajax_create_test_users'));
        add_action('wp_ajax_clarity_impersonate_user', array($this, 'ajax_impersonate_user'));
        
        // Shortcodes
        add_shortcode('clarity_student_registration', array($this, 'render_registration_form'));
        add_shortcode('clarity_student_login', array($this, 'render_login_form'));
        add_shortcode('clarity_student_dashboard', array($this, 'render_student_dashboard'));
        add_shortcode('clarity_contact_form', array($this, 'render_contact_form'));
        add_shortcode('clarity_main_page', array($this, 'render_main_page'));
        
        // User role capabilities
        add_action('init', array($this, 'setup_student_capabilities'));
        
        // Course completion webhook
        add_action('clarity_course_completed', array($this, 'handle_course_completion'), 10, 2);
        
        // Manual webhook trigger for testing
        add_action('wp_ajax_test_course_completion_webhook', array($this, 'ajax_test_course_completion_webhook'));
        
        // Redirect after login
        add_filter('login_redirect', array($this, 'student_login_redirect'), 10, 3);
    }
    
    /**
     * Register student role
     */
    public function register_student_role() {
        if (!get_role('clarity_student')) {
            add_role('clarity_student', 'Student', array(
                'read' => true,
                'clarity_access_courses' => true,
                'clarity_view_progress' => true,
                'clarity_download_certificates' => true,
            ));
        }
    }
    
    /**
     * Setup student capabilities
     */
    public function setup_student_capabilities() {
        $role = get_role('clarity_student');
        if ($role) {
            $role->add_cap('clarity_access_courses');
            $role->add_cap('clarity_view_progress');
            $role->add_cap('clarity_download_certificates');
        }
    }
    
    /**
     * Handle custom login
     */
    public function handle_custom_login() {
        if (!isset($_POST['clarity_login_nonce']) || !wp_verify_nonce($_POST['clarity_login_nonce'], 'clarity_student_login')) {
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($email) || empty($password)) {
            wp_redirect(add_query_arg('login_error', urlencode('Email and password are required.'), wp_get_referer()));
            exit;
        }
        
        // Attempt login
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_redirect(add_query_arg('login_error', urlencode('Invalid email or password.'), wp_get_referer()));
            exit;
        }
        
        // Check if user is a student
        if (!in_array('clarity_student', $user->roles)) {
            wp_redirect(add_query_arg('login_error', urlencode('Access denied. Student account required.'), wp_get_referer()));
            exit;
        }
        
        // Log user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        wp_redirect(home_url('/student-dashboard/'));
        exit;
    }
    
    /**
     * Handle custom logout
     */
    public function handle_custom_logout() {
        // This is called when user logs out
        // Add any custom logout logic here if needed
    }
    
    /**
     * Handle custom registration
     */
    public function handle_custom_registration() {
        if (!isset($_POST['clarity_register_nonce']) || !wp_verify_nonce($_POST['clarity_register_nonce'], 'clarity_student_registration')) {
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = array();
        
        if (empty($email) || !is_email($email)) {
            $errors[] = 'Valid email address is required.';
        }
        
        if (email_exists($email)) {
            $errors[] = 'An account with this email already exists.';
        }
        
        if (empty($first_name) || empty($last_name)) {
            $errors[] = 'First name and last name are required.';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!empty($errors)) {
            wp_redirect(add_query_arg('registration_error', urlencode(implode(' ', $errors)), wp_get_referer()));
            exit;
        }
        
        // Create user
        $user_id = $this->create_student_user($email, $first_name, $last_name, $password);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('registration_error', urlencode($user_id->get_error_message()), wp_get_referer()));
            exit;
        }
        
        // Auto-enroll in free course
        $this->enroll_user_in_course($user_id, 1); // Tier 1 (Free)
        
        // Send to GHL webhook if configured
        $this->send_registration_to_ghl($email, $first_name, $last_name);
        
        // Log user in and redirect
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_redirect(add_query_arg('registration_success', '1', home_url('/student-dashboard/')));
        exit;
    }
    
    /**
     * Create student user
     */
    public function create_student_user($email, $first_name, $last_name, $password) {
        $username = sanitize_user($email);
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'clarity_student'
        ));
        
        // Add custom meta
        update_user_meta($user_id, 'clarity_registration_date', current_time('mysql'));
        update_user_meta($user_id, 'clarity_access_level', 1); // Start with free tier
        
        return $user_id;
    }
    
    /**
     * Enroll user in course
     */
    public function enroll_user_in_course($user_id, $course_id) {
        global $wpdb;
        
        // Check if already enrolled
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['enrollments']} WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        if ($existing) {
            return false; // Already enrolled
        }
        
        // Enroll user
        $result = $wpdb->insert(
            $this->tables['enrollments'],
            array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'enrollment_date' => current_time('mysql'),
                'enrollment_status' => 'active'
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get user course access level
     */
    public function get_user_access_level($user_id) {
        return (int) get_user_meta($user_id, 'clarity_access_level', true) ?: 1;
    }
    
    /**
     * Set user course access level
     */
    public function set_user_access_level($user_id, $level) {
        return update_user_meta($user_id, 'clarity_access_level', $level);
    }
    
    /**
     * Check if user can access course
     */
    public function user_can_access_course($user_id, $course_id) {
        global $wpdb;
        
        // Get course tier
        $course_tier = $wpdb->get_var($wpdb->prepare(
            "SELECT course_tier FROM {$this->tables['courses']} WHERE id = %d",
            $course_id
        ));
        
        if (!$course_tier) {
            return false;
        }
        
        // Get user access level
        $user_level = $this->get_user_access_level($user_id);
        
        // Check if user's access level covers this course tier
        return $user_level >= $course_tier;
    }
    
    /**
     * Get user enrolled courses
     */
    public function get_user_enrolled_courses($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, e.enrollment_date, e.enrollment_status
            FROM {$this->tables['courses']} c
            INNER JOIN {$this->tables['enrollments']} e ON c.id = e.course_id
            WHERE e.user_id = %d AND e.enrollment_status = 'active'
            ORDER BY e.enrollment_date DESC
        ", $user_id));
    }
    
    /**
     * Get user course progress
     */
    public function get_user_course_progress($user_id, $course_id) {
        global $wpdb;
        
        // Get total lessons in course
        $total_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['lessons']} WHERE course_id = %d",
            $course_id
        ));
        
        if (!$total_lessons) {
            return array('completed' => 0, 'total' => 0, 'percentage' => 0);
        }
        
        // Get completed lessons
        $completed_lessons = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.lesson_id)
            FROM {$this->tables['user_progress']} p
            INNER JOIN {$this->tables['lessons']} l ON p.lesson_id = l.id
            WHERE p.user_id = %d AND l.course_id = %d AND p.completion_status = 'completed'
        ", $user_id, $course_id));
        
        $percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
        
        return array(
            'completed' => $completed_lessons,
            'total' => $total_lessons,
            'percentage' => $percentage
        );
    }
    
    /**
     * Send registration to GHL webhook
     */
    private function send_registration_to_ghl($email, $first_name, $last_name) {
        error_log("Clarity: send_registration_to_ghl called for email {$email}");
        
        $webhook_url = 'https://services.leadconnectorhq.com/hooks/dx7Ru0l4s4q30jYQBuAz/webhook-trigger/75422136-564a-423f-b369-4dedf365f3ba';
        
        $data = array(
            'event' => 'student_registration',
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'registration_date' => current_time('c'),
            'source' => 'clarity_course_platform'
        );
        
        error_log("Clarity: Registration webhook data - " . json_encode($data));
        
        // Send to webhook
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log("Clarity: Registration webhook error - " . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            error_log("Clarity: Registration webhook response - Code: {$response_code}, Body: {$response_body}");
        }
    }
    
    /**
     * Student login redirect
     */
    public function student_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && in_array('clarity_student', $user->roles)) {
            return home_url('/student-dashboard/');
        }
        return $redirect_to;
    }
    
    /**
     * AJAX: Register student
     */
    public function ajax_register_student() {
        check_ajax_referer('clarity_student_registration', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($email) || !is_email($email)) {
            wp_send_json_error('Valid email address is required.');
        }
        
        if (email_exists($email)) {
            wp_send_json_error('An account with this email already exists.');
        }
        
        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error('First name and last name are required.');
        }
        
        if (empty($password) || strlen($password) < 6) {
            wp_send_json_error('Password must be at least 6 characters long.');
        }
        
        // Create user
        $user_id = $this->create_student_user($email, $first_name, $last_name, $password);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Auto-enroll in free course
        $this->enroll_user_in_course($user_id, 1);
        
        // Send to GHL
        $this->send_registration_to_ghl($email, $first_name, $last_name);
        
        wp_send_json_success(array(
            'message' => 'Registration successful! Welcome to our course platform.',
            'redirect' => home_url('/student-dashboard/')
        ));
    }
    
    /**
     * AJAX: Login student
     */
    public function ajax_login_student() {
        check_ajax_referer('clarity_student_login', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($email) || empty($password)) {
            wp_send_json_error('Email and password are required.');
        }
        
        // Attempt login
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error('Invalid email or password.');
        }
        
        // Check if user is a student
        if (!in_array('clarity_student', $user->roles)) {
            wp_send_json_error('Access denied. Student account required.');
        }
        
        // Log user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        wp_send_json_success(array(
            'message' => 'Login successful! Welcome back.',
            'redirect' => home_url('/student-dashboard/')
        ));
    }
    
    // ADMIN TESTING TOOLS
    
    /**
     * AJAX: Delete test user
     */
    public function ajax_delete_test_user() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        
        if (!$user_id) {
            wp_send_json_error('Invalid user ID');
        }
        
        // Delete user progress
        global $wpdb;
        $wpdb->delete($this->tables['user_progress'], array('user_id' => $user_id));
        $wpdb->delete($this->tables['enrollments'], array('user_id' => $user_id));
        
        // Delete WordPress user
        require_once(ABSPATH.'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);
        
        if ($result) {
            wp_send_json_success('User deleted successfully');
        } else {
            wp_send_json_error('Failed to delete user');
        }
    }
    
    /**
     * AJAX: Reset user progress
     */
    public function ajax_reset_user_progress() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        
        if (!$user_id) {
            wp_send_json_error('Invalid user ID');
        }
        
        global $wpdb;
        $result = $wpdb->delete($this->tables['user_progress'], array('user_id' => $user_id));
        
        if ($result !== false) {
            wp_send_json_success('User progress reset successfully');
        } else {
            wp_send_json_error('Failed to reset user progress');
        }
    }
    
    /**
     * AJAX: Reset demo (clear all test data)
     */
    public function ajax_reset_demo() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get all student users
        $students = get_users(array('role' => 'clarity_student'));
        
        // Delete all student progress and enrollments
        $wpdb->query("DELETE FROM {$this->tables['user_progress']} WHERE user_id IN (SELECT ID FROM {$wpdb->users} WHERE ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%clarity_student%'))");
        $wpdb->query("DELETE FROM {$this->tables['enrollments']} WHERE user_id IN (SELECT ID FROM {$wpdb->users} WHERE ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%clarity_student%'))");
        
        // Delete all student users
        require_once(ABSPATH.'wp-admin/includes/user.php');
        foreach ($students as $student) {
            wp_delete_user($student->ID);
        }
        
        wp_send_json_success('Demo environment reset successfully');
    }
    
    /**
     * AJAX: Create test users with various progress levels
     */
    public function ajax_create_test_users() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_users = array(
            array('name' => 'New Student', 'email' => 'new@test.com', 'progress' => 0),
            array('name' => 'Beginner Student', 'email' => 'beginner@test.com', 'progress' => 20),
            array('name' => 'Intermediate Student', 'email' => 'intermediate@test.com', 'progress' => 50),
            array('name' => 'Advanced Student', 'email' => 'advanced@test.com', 'progress' => 80),
            array('name' => 'Graduate Student', 'email' => 'graduate@test.com', 'progress' => 100),
        );
        
        $created_users = array();
        
        foreach ($test_users as $test_user) {
            // Create user
            $user_id = $this->create_student_user(
                $test_user['email'],
                explode(' ', $test_user['name'])[0],
                explode(' ', $test_user['name'])[1],
                'password123'
            );
            
            if (!is_wp_error($user_id)) {
                // Enroll in free course
                $this->enroll_user_in_course($user_id, 1);
                
                // Simulate progress if needed
                if ($test_user['progress'] > 0) {
                    $this->simulate_user_progress($user_id, 1, $test_user['progress']);
                }
                
                $created_users[] = array(
                    'name' => $test_user['name'],
                    'email' => $test_user['email'],
                    'progress' => $test_user['progress']
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Test users created successfully',
            'users' => $created_users
        ));
    }
    
    /**
     * Simulate user progress for testing
     */
    private function simulate_user_progress($user_id, $course_id, $percentage) {
        global $wpdb;
        
        // Get course lessons
        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->tables['lessons']} WHERE course_id = %d ORDER BY lesson_order",
            $course_id
        ));
        
        if (empty($lessons)) {
            return;
        }
        
        $total_lessons = count($lessons);
        $lessons_to_complete = floor(($percentage / 100) * $total_lessons);
        
        // Mark lessons as completed
        for ($i = 0; $i < $lessons_to_complete; $i++) {
            if (isset($lessons[$i])) {
                $wpdb->insert(
                    $this->tables['user_progress'],
                    array(
                        'user_id' => $user_id,
                        'lesson_id' => $lessons[$i]->id,
                        'completion_status' => 'completed',
                        'completion_date' => current_time('mysql'),
                        'time_spent' => rand(300, 1800) // Random time between 5-30 minutes
                    ),
                    array('%d', '%d', '%s', '%s', '%d')
                );
            }
        }
    }
    
    /**
     * Get all students for admin management
     */
    public function get_all_students() {
        $students = get_users(array('role' => 'clarity_student'));
        $student_data = array();
        
        foreach ($students as $student) {
            $enrolled_courses = $this->get_user_enrolled_courses($student->ID);
            $progress_data = array();
            
            foreach ($enrolled_courses as $course) {
                $progress_data[] = $this->get_user_course_progress($student->ID, $course->id);
            }
            
            $student_data[] = array(
                'user' => $student,
                'access_level' => $this->get_user_access_level($student->ID),
                'enrolled_courses' => $enrolled_courses,
                'progress' => $progress_data,
                'registration_date' => get_user_meta($student->ID, 'clarity_registration_date', true)
            );
        }
        
        return $student_data;
    }
    
    /**
     * Render registration form shortcode
     */
    public function render_registration_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => home_url('/student-dashboard/')
        ), $atts);
        
        ob_start();
        include CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render login form shortcode
     */
    public function render_login_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => home_url('/student-dashboard/')
        ), $atts);
        
        ob_start();
        include CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/login-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render student dashboard shortcode
     */
    public function render_student_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to access your dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('clarity_student', $current_user->roles)) {
            return '<p>Access denied. Student account required.</p>';
        }
        
        ob_start();
        include CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/student-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Render contact form shortcode
     */
    public function render_contact_form($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Get In Touch',
            'subtitle' => 'We\'d love to hear from you'
        ), $atts);
        
        ob_start();
        include CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/contact-form.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Handle contact form submission
     */
    public function ajax_contact_form() {
        check_ajax_referer('clarity_contact_form', 'nonce');
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $interest = sanitize_text_field($_POST['interest']);
        $message = sanitize_textarea_field($_POST['message']);
        $subscribe = isset($_POST['subscribe_newsletter']) ? true : false;
        
        // Validation
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            wp_send_json_error('Please fill in all required fields.');
        }
        
        if (!is_email($email)) {
            wp_send_json_error('Please enter a valid email address.');
        }
        
        // Send to GHL webhook with contact form data
        $this->send_contact_to_ghl($name, $email, $subject, $interest, $message, $subscribe);
        
        // Send notification email to admin (optional)
        $admin_email = get_option('admin_email');
        $email_subject = 'New Contact Form Submission: ' . $subject;
        $email_message = "Name: {$name}\n";
        $email_message .= "Email: {$email}\n";
        $email_message .= "Interest: {$interest}\n\n";
        $email_message .= "Message:\n{$message}\n\n";
        $email_message .= "Subscribe to newsletter: " . ($subscribe ? 'Yes' : 'No');
        
        wp_mail($admin_email, $email_subject, $email_message);
        
        wp_send_json_success(array(
            'message' => 'Thank you for your message! We\'ll get back to you soon.'
        ));
    }
    
    /**
     * Send contact form data to GHL webhook
     */
    private function send_contact_to_ghl($name, $email, $subject, $interest, $message, $subscribe) {
        $webhook_url = get_option('clarity_ghl_webhook_url');
        
        if (empty($webhook_url)) {
            return;
        }
        
        $data = array(
            'event' => 'contact_form_submission',
            'contact' => array(
                'name' => $name,
                'email' => $email,
                'firstName' => explode(' ', $name)[0],
                'lastName' => isset(explode(' ', $name)[1]) ? explode(' ', $name)[1] : '',
            ),
            'submission' => array(
                'subject' => $subject,
                'interest' => $interest,
                'message' => $message,
                'subscribe_newsletter' => $subscribe,
                'source' => 'clarity_contact_form',
                'submitted_at' => current_time('c')
            ),
            'tags' => array('contact-form', 'website-lead', 'interest-' . $interest)
        );
        
        // Send to webhook
        wp_remote_post($webhook_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
    }
    
    /**
     * Send course completion data to GHL webhook
     */
    private function send_course_completion_to_ghl($user_id, $course_id) {
        error_log("Clarity: send_course_completion_to_ghl called for user {$user_id}, course {$course_id}");
        
        // Only trigger for Tier 1 course (Real Estate Foundations)
        // Check by course tier instead of ID since ID may vary
        global $wpdb;
        $tables = (new Clarity_AWS_GHL_Database_Courses())->get_table_names();
        
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['courses']} WHERE id = %d",
            $course_id
        ));
        
        error_log("Clarity: Found course - ID: {$course_id}, Title: " . ($course ? $course->course_title : 'NOT FOUND') . ", Tier: " . ($course ? $course->course_tier : 'N/A'));
        
        // Only trigger for tier 1 course (Real Estate Foundations)
        if (!$course || $course->course_tier != 1) {
            error_log("Clarity: Skipping webhook - not tier 1 course");
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("Clarity: User not found for ID {$user_id}");
            return;
        }
        
        error_log("Clarity: Sending webhook for user {$user->user_email}");
        
        $webhook_url = 'https://services.leadconnectorhq.com/hooks/dx7Ru0l4s4q30jYQBuAz/webhook-trigger/3df2ed90-b622-4e2b-ae4e-a0097ff6f265';
        
        $data = array(
            'email' => $user->user_email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'courseName' => 'Real Estate Foundations'
        );
        
        error_log("Clarity: Webhook data - " . json_encode($data));
        
        // Send to webhook
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log("Clarity: Webhook error - " . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            error_log("Clarity: Webhook response - Code: {$response_code}, Body: {$response_body}");
        }
    }
    
    /**
     * Handle course completion webhook trigger
     */
    public function handle_course_completion($user_id, $course_id) {
        error_log("Clarity: Course completion triggered for user {$user_id}, course {$course_id}");
        $this->send_course_completion_to_ghl($user_id, $course_id);
    }
    
    /**
     * AJAX: Test course completion webhook
     */
    public function ajax_test_course_completion_webhook() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = get_current_user_id();
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 1;
        
        error_log("Clarity: Manual webhook test triggered for user {$user_id}, course {$course_id}");
        
        // Directly call the webhook function
        $this->send_course_completion_to_ghl($user_id, $course_id);
        
        wp_send_json_success(array(
            'message' => 'Webhook test triggered',
            'user_id' => $user_id,
            'course_id' => $course_id
        ));
    }
    
    /**
     * Render main page shortcode
     */
    public function render_main_page($atts) {
        try {
            error_log('Clarity: Main page shortcode called');
            
            $atts = shortcode_atts(array(
                'show_controls' => 'false' // Controls are now in admin
            ), $atts);
            
            // Check if template file exists
            $template_path = CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/main-page.php';
            if (!file_exists($template_path)) {
                error_log('Clarity: Template file not found at ' . $template_path);
                return '<div class="clarity-error">Template file not found.</div>';
            }
            
            // Include the template - it handles its own output buffering and returns content
            $content = include $template_path;
            
            if (empty($content)) {
                error_log('Clarity: Template returned empty content');
                return '<div class="clarity-error">Template generated empty content.</div>';
            }
            
            error_log('Clarity: Main page shortcode rendered successfully, content length: ' . strlen($content));
            return $content;
            
        } catch (Exception $e) {
            error_log('Clarity: Error in main page shortcode: ' . $e->getMessage());
            return '<div class="clarity-error">Error loading main page template: ' . esc_html($e->getMessage()) . '</div>';
        } catch (ParseError $e) {
            error_log('Clarity: Parse error in main page template: ' . $e->getMessage());
            return '<div class="clarity-error">Template parsing error.</div>';
        } catch (Error $e) {
            error_log('Clarity: Fatal error in main page template: ' . $e->getMessage());
            return '<div class="clarity-error">Template fatal error.</div>';
        }
    }
    
    /**
     * AJAX: Save hero background settings
     */
    public function ajax_save_hero_bg_settings() {
        check_ajax_referer('clarity_student_registration', 'nonce'); // Reuse existing nonce
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $bg_type = sanitize_text_field($_POST['bg_type']);
        $custom_image = esc_url_raw($_POST['custom_image']);
        $image_position = sanitize_text_field($_POST['image_position']);
        
        // Validate background type
        if (!in_array($bg_type, array('default', 'slideshow', 'custom'))) {
            wp_send_json_error('Invalid background type');
        }
        
        // Save settings
        update_option('clarity_hero_bg_type', $bg_type);
        update_option('clarity_hero_custom_image', $custom_image);
        update_option('clarity_hero_image_position', $image_position);
        
        wp_send_json_success('Background settings saved successfully');
    }
}