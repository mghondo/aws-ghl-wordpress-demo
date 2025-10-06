<?php
/**
 * Certificate Manager Class
 *
 * Handles automatic certificate generation via AWS Lambda when students complete courses
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Certificate_Manager {
    
    /**
     * Lambda API endpoint URL
     */
    private $lambda_endpoint;
    
    /**
     * Database instances
     */
    private $db_courses;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->lambda_endpoint = get_option('clarity_certificate_lambda_endpoint', '');
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into course completion
        add_action('clarity_course_completed', array($this, 'handle_course_completion'), 10, 2);
        
        // Admin AJAX handlers for manual certificate generation
        add_action('wp_ajax_clarity_generate_certificate', array($this, 'ajax_generate_certificate'));
        add_action('wp_ajax_clarity_regenerate_certificate', array($this, 'ajax_regenerate_certificate'));
        
        // Add certificate column to admin course enrollments table
        add_filter('clarity_enrollment_table_columns', array($this, 'add_certificate_column'));
        add_action('clarity_enrollment_table_column_certificate', array($this, 'display_certificate_column'), 10, 2);
        
        // Add certificate download shortcode
        add_shortcode('clarity_certificate_download', array($this, 'certificate_download_shortcode'));
    }
    
    /**
     * Handle course completion - automatically generate certificate
     */
    public function handle_course_completion($user_id, $course_id) {
        try {
            error_log("Certificate Manager: Course completion triggered for user $user_id, course $course_id");
            
            // Check if certificates are enabled
            if (!get_option('clarity_certificate_enabled', 1)) {
                error_log("Certificate Manager: Certificate generation is disabled");
                return;
            }
            
            // Check if auto-generation is enabled
            if (!get_option('clarity_certificate_auto_generate', 1)) {
                error_log("Certificate Manager: Auto-generation is disabled");
                return;
            }
            
            // Check if Lambda endpoint is configured
            if (empty($this->lambda_endpoint)) {
                error_log("Certificate Manager: Lambda endpoint not configured");
                return;
            }
            
            // Check if certificate already issued
            if ($this->certificate_already_issued($user_id, $course_id)) {
                error_log("Certificate Manager: Certificate already issued for user $user_id, course $course_id");
                return;
            }
            
            // Verify course completion
            if (!$this->verify_course_completion($user_id, $course_id)) {
                error_log("Certificate Manager: Course not fully completed for user $user_id, course $course_id");
                return;
            }
            
            // Generate certificate
            $result = $this->generate_certificate($user_id, $course_id);
            
            if ($result['success']) {
                error_log("Certificate Manager: Certificate generated successfully for user $user_id, course $course_id - " . $result['certificate_number']);
                
                // Fire action for other plugins/themes
                do_action('clarity_certificate_generated', $user_id, $course_id, $result);
                
                // Send email notification
                $this->send_certificate_email($user_id, $course_id, $result);
            } else {
                error_log("Certificate Manager: Failed to generate certificate for user $user_id, course $course_id - " . $result['error']);
            }
            
        } catch (Exception $e) {
            error_log("Certificate Manager: Exception in handle_course_completion - " . $e->getMessage());
        }
    }
    
    /**
     * Generate certificate via Lambda API
     */
    public function generate_certificate($user_id, $course_id) {
        try {
            // Get user data
            $user_data = $this->get_user_data($user_id);
            if (!$user_data) {
                return array('success' => false, 'error' => 'User data not found');
            }
            
            // Get course data
            $course_data = $this->get_course_data($course_id);
            if (!$course_data) {
                return array('success' => false, 'error' => 'Course data not found');
            }
            
            // Get enrollment data
            $enrollment_data = $this->get_enrollment_data($user_id, $course_id);
            if (!$enrollment_data) {
                return array('success' => false, 'error' => 'Enrollment data not found');
            }
            
            // Prepare API request data
            $request_data = array(
                'recipient_name' => trim($user_data['first_name'] . ' ' . $user_data['last_name']),
                'course_title' => $course_data['course_title'],
                'tier_level' => intval($course_data['course_tier']),
                'completion_date' => date('Y-m-d', strtotime($enrollment_data['completion_date'])),
                'user_id' => intval($user_id),
                'course_id' => intval($course_id)
            );
            
            error_log("Certificate Manager: Sending request to Lambda - " . json_encode($request_data));
            
            // Call Lambda API
            $response = wp_remote_post($this->lambda_endpoint, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                ),
                'body' => json_encode($request_data),
                'timeout' => 45,
                'sslverify' => true
            ));
            
            // Handle API response
            if (is_wp_error($response)) {
                error_log("Certificate Manager: API request failed - " . $response->get_error_message());
                return array('success' => false, 'error' => 'API request failed: ' . $response->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            error_log("Certificate Manager: API response code $response_code, body: $response_body");
            
            if ($response_code !== 200) {
                return array('success' => false, 'error' => 'API returned error code: ' . $response_code);
            }
            
            error_log("Certificate Manager: Raw Lambda response: " . $response_body);
            
            $result = json_decode($response_body, true);
            
            if (!$result) {
                error_log("Certificate Manager: Failed to parse JSON response");
                return array('success' => false, 'error' => 'Invalid API response format');
            }
            
            error_log("Certificate Manager: Parsed Lambda response: " . json_encode($result));
            
            // Handle Lambda response format (nested body)
            if (isset($result['body']) && is_string($result['body'])) {
                $body_data = json_decode($result['body'], true);
                if ($body_data) {
                    $result = $body_data;
                }
            }
            
            if (!isset($result['success']) || !$result['success']) {
                $error = isset($result['error']) ? $result['error'] : 'Unknown API error';
                return array('success' => false, 'error' => $error);
            }
            
            // Update database with certificate information
            error_log("Certificate Manager: Attempting to save certificate - URL: " . $result['certificate_url'] . ", Number: " . $result['certificate_number']);
            
            $update_result = $this->update_enrollment_certificate(
                $user_id,
                $course_id,
                $result['certificate_url'],
                $result['certificate_number']
            );
            
            if (!$update_result) {
                $debug_info = array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'certificate_url' => $result['certificate_url'],
                    'certificate_number' => $result['certificate_number'],
                    'lambda_response' => $result,
                    'timestamp' => current_time('mysql')
                );
                
                // Store debug info in WordPress options for viewing
                update_option('clarity_last_certificate_error', $debug_info);
                
                error_log("Certificate Manager: Failed to update database with certificate info - user_id: $user_id, course_id: $course_id");
                error_log("Certificate Manager: Attempted to save URL: " . $result['certificate_url']);
                error_log("Certificate Manager: Attempted to save Number: " . $result['certificate_number']);
                
                return array('success' => false, 'error' => 'Failed to save certificate to database. Debug info stored for admin review.');
            }
            
            error_log("Certificate Manager: Certificate saved successfully to database");
            
            return array(
                'success' => true,
                'certificate_url' => $result['certificate_url'],
                'certificate_number' => $result['certificate_number'],
                'message' => isset($result['message']) ? $result['message'] : 'Certificate generated successfully'
            );
            
        } catch (Exception $e) {
            error_log("Certificate Manager: Exception in generate_certificate - " . $e->getMessage());
            return array('success' => false, 'error' => 'Internal error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user data for certificate
     */
    private function get_user_data($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Try to get name from user meta first, fallback to display name
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        
        if (empty($first_name) && empty($last_name)) {
            // Split display name if no first/last name set
            $name_parts = explode(' ', $user->display_name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        }
        
        return array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $user->user_email
        );
    }
    
    /**
     * Get course data for certificate
     */
    private function get_course_data($course_id) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT course_title, course_tier FROM {$courses_table} WHERE id = %d",
            $course_id
        ), ARRAY_A);
        
        return $course;
    }
    
    /**
     * Get enrollment data for certificate
     */
    private function get_enrollment_data($user_id, $course_id) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT completion_date, progress_percentage, certificate_issued, certificate_url, certificate_number 
             FROM {$enrollments_table} 
             WHERE user_id = %d AND course_id = %d",
            $user_id,
            $course_id
        ), ARRAY_A);
        
        return $enrollment;
    }
    
    /**
     * Check if certificate already issued
     */
    private function certificate_already_issued($user_id, $course_id) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $issued = $wpdb->get_var($wpdb->prepare(
            "SELECT certificate_issued FROM {$enrollments_table} 
             WHERE user_id = %d AND course_id = %d",
            $user_id,
            $course_id
        ));
        
        return (bool) $issued;
    }
    
    /**
     * Verify course completion (100% progress)
     */
    private function verify_course_completion($user_id, $course_id) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $progress = $wpdb->get_var($wpdb->prepare(
            "SELECT progress_percentage FROM {$enrollments_table} 
             WHERE user_id = %d AND course_id = %d",
            $user_id,
            $course_id
        ));
        
        return intval($progress) >= 100;
    }
    
    /**
     * Update enrollment record with certificate information
     */
    private function update_enrollment_certificate($user_id, $course_id, $certificate_url, $certificate_number) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        // First, check if enrollment exists
        $enrollment = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$enrollments_table} 
            WHERE user_id = %d AND course_id = %d
        ", $user_id, $course_id));
        
        if (!$enrollment) {
            // Create enrollment if it doesn't exist
            $insert_result = $wpdb->insert(
                $enrollments_table,
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'enrollment_date' => current_time('mysql'),
                    'completion_date' => current_time('mysql'),
                    'progress_percentage' => 100,
                    'certificate_issued' => 1,
                    'certificate_url' => $certificate_url,
                    'certificate_number' => $certificate_number,
                    'enrollment_status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($insert_result === false) {
                error_log("Certificate Manager: Failed to create enrollment - " . $wpdb->last_error);
                return false;
            }
            
            error_log("Certificate Manager: Created new enrollment for user $user_id, course $course_id");
            return true;
        }
        
        // Update existing enrollment
        $result = $wpdb->update(
            $enrollments_table,
            array(
                'certificate_issued' => 1,
                'certificate_url' => $certificate_url,
                'certificate_number' => $certificate_number,
                'completion_date' => current_time('mysql'),
                'progress_percentage' => 100,
                'updated_at' => current_time('mysql')
            ),
            array(
                'user_id' => $user_id,
                'course_id' => $course_id
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s'),
            array('%d', '%d')
        );
        
        if ($result === false) {
            error_log("Certificate Manager: Failed to update enrollment - " . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Send certificate email notification
     */
    private function send_certificate_email($user_id, $course_id, $certificate_data) {
        $user = get_userdata($user_id);
        $course_data = $this->get_course_data($course_id);
        
        if (!$user || !$course_data) {
            return false;
        }
        
        $subject = sprintf(
            '[%s] Your Certificate is Ready - %s',
            get_bloginfo('name'),
            $course_data['course_title']
        );
        
        $message = sprintf(
            "Congratulations %s!\n\n" .
            "You have successfully completed %s and earned your certificate.\n\n" .
            "Certificate Number: %s\n" .
            "Download your certificate: %s\n\n" .
            "You can also view and download your certificate anytime from your student dashboard.\n\n" .
            "Best regards,\n" .
            "The %s Team",
            $user->display_name,
            $course_data['course_title'],
            $certificate_data['certificate_number'],
            $certificate_data['certificate_url'],
            get_bloginfo('name')
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * AJAX handler for manual certificate generation
     */
    public function ajax_generate_certificate() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'clarity_certificate_nonce') || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        if (!$user_id || !$course_id) {
            wp_send_json_error('Invalid user or course ID');
        }
        
        // Check if already issued
        if ($this->certificate_already_issued($user_id, $course_id)) {
            wp_send_json_error('Certificate already issued for this user/course');
        }
        
        // Generate certificate
        $result = $this->generate_certificate($user_id, $course_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX handler for certificate regeneration
     */
    public function ajax_regenerate_certificate() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'clarity_certificate_nonce') || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        if (!$user_id || !$course_id) {
            wp_send_json_error('Invalid user or course ID');
        }
        
        // Reset certificate status to allow regeneration
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $wpdb->update(
            $enrollments_table,
            array(
                'certificate_issued' => 0,
                'certificate_url' => null,
                'certificate_number' => null
            ),
            array(
                'user_id' => $user_id,
                'course_id' => $course_id
            )
        );
        
        // Generate new certificate
        $result = $this->generate_certificate($user_id, $course_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Add certificate column to enrollment table
     */
    public function add_certificate_column($columns) {
        $columns['certificate'] = 'Certificate';
        return $columns;
    }
    
    /**
     * Display certificate column content
     */
    public function display_certificate_column($enrollment, $user_data) {
        if ($enrollment->certificate_issued) {
            echo '<a href="' . esc_url($enrollment->certificate_url) . '" target="_blank" class="button button-small">';
            echo 'View Certificate';
            echo '</a>';
            echo '<br><small>No: ' . esc_html($enrollment->certificate_number) . '</small>';
        } else {
            echo '<span class="description">Not issued</span>';
            if (current_user_can('manage_options')) {
                echo '<br><button type="button" class="button button-small generate-certificate" ';
                echo 'data-user-id="' . $enrollment->user_id . '" ';
                echo 'data-course-id="' . $enrollment->course_id . '">';
                echo 'Generate</button>';
            }
        }
    }
    
    /**
     * Certificate download shortcode
     */
    public function certificate_download_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'course_id' => 0,
            'text' => 'Download Certificate'
        ), $atts);
        
        if (!$atts['course_id'] || !$atts['user_id']) {
            return '<p>Invalid certificate request.</p>';
        }
        
        $enrollment_data = $this->get_enrollment_data($atts['user_id'], $atts['course_id']);
        
        if (!$enrollment_data || !$enrollment_data['certificate_issued']) {
            return '<p>Certificate not available.</p>';
        }
        
        return sprintf(
            '<a href="%s" target="_blank" class="clarity-certificate-download button">%s</a>',
            esc_url($enrollment_data['certificate_url']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Get certificate statistics for admin dashboard
     */
    public function get_certificate_stats() {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
        
        $stats = array();
        
        // Total certificates issued
        $stats['total_issued'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$enrollments_table} WHERE certificate_issued = 1"
        );
        
        // Certificates issued this month
        $stats['this_month'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$enrollments_table} 
             WHERE certificate_issued = 1 
             AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        );
        
        // Certificates by tier
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $stats['by_tier'] = $wpdb->get_results(
            "SELECT c.course_tier, COUNT(e.id) as count 
             FROM {$enrollments_table} e 
             JOIN {$courses_table} c ON e.course_id = c.id 
             WHERE e.certificate_issued = 1 
             GROUP BY c.course_tier 
             ORDER BY c.course_tier"
        );
        
        return $stats;
    }
}

// Initialize the certificate manager
new Clarity_AWS_GHL_Certificate_Manager();