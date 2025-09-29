<?php
/**
 * Course Manager Class
 *
 * Handles course operations, enrollment, and user management
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Course_Manager {
    
    /**
     * Database instance
     */
    private $db_courses;
    private $tables;
    
    /**
     * Course tier pricing
     */
    private $tier_pricing = array(
        1 => 0,      // Free tier
        2 => 497,    // Core product
        3 => 1997    // Premium
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        $this->tables = $this->db_courses->get_table_names();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Register user role
        $this->register_student_role();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // User enrollment hooks
        add_action('user_register', array($this, 'enroll_user_in_free_course'));
        add_action('wp_ajax_enroll_in_course', array($this, 'ajax_enroll_in_course'));
        add_action('wp_ajax_mark_lesson_complete', array($this, 'ajax_mark_lesson_complete'));
        add_action('wp_ajax_get_course_progress', array($this, 'ajax_get_course_progress'));
        
        // Admin hooks
        add_action('wp_ajax_admin_toggle_lesson', array($this, 'ajax_admin_toggle_lesson'));
        add_action('wp_ajax_admin_reset_progress', array($this, 'ajax_admin_reset_progress'));
        add_action('wp_ajax_admin_bulk_complete', array($this, 'ajax_admin_bulk_complete'));
        
        // Certificate generation
        add_action('clarity_course_completed', array($this, 'generate_certificate'), 10, 2);
    }
    
    /**
     * Register student role
     */
    private function register_student_role() {
        $role = get_role('student');
        
        if (!$role) {
            add_role('student', __('Student', 'clarity-aws-ghl'), array(
                'read' => true,
                'view_courses' => true,
                'enroll_courses' => true,
                'complete_lessons' => true
            ));
        }
    }
    
    /**
     * Get all courses
     */
    public function get_all_courses($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'published',
            'orderby' => 'course_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query = "SELECT c.*, 
                         COALESCE(COUNT(l.id), 0) as total_lessons
                  FROM {$this->tables['courses']} c
                  LEFT JOIN {$this->tables['lessons']} l ON c.id = l.course_id
                  WHERE 1=1";
        
        if ($args['status']) {
            $query .= $wpdb->prepare(" AND c.course_status = %s", $args['status']);
        }
        
        $query .= " GROUP BY c.id ORDER BY c.{$args['orderby']} {$args['order']}";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get course by ID or slug
     */
    public function get_course($identifier) {
        global $wpdb;
        
        if (is_numeric($identifier)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tables['courses']} WHERE id = %d",
                $identifier
            ));
        } else {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tables['courses']} WHERE course_slug = %s",
                $identifier
            ));
        }
    }
    
    /**
     * Get course lessons
     */
    public function get_course_lessons($course_id, $include_locked = true) {
        global $wpdb;
        
        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} 
            WHERE course_id = %d 
            ORDER BY lesson_order ASC",
            $course_id
        ));
        
        if (!$include_locked && is_user_logged_in()) {
            $user_id = get_current_user_id();
            foreach ($lessons as &$lesson) {
                $lesson->is_locked = !$this->can_access_lesson($user_id, $lesson->id);
            }
        }
        
        return $lessons;
    }
    
    /**
     * Enroll user in course
     */
    public function enroll_user($user_id, $course_id, $payment_status = 'pending') {
        global $wpdb;
        
        // Check if already enrolled
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['enrollments']} 
            WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        if ($existing) {
            return $existing;
        }
        
        // Get course details
        $course = $this->get_course($course_id);
        
        // Insert enrollment
        $wpdb->insert($this->tables['enrollments'], array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'enrollment_status' => 'active',
            'payment_status' => $payment_status,
            'payment_amount' => $course->course_price
        ));
        
        $enrollment_id = $wpdb->insert_id;
        
        // Trigger enrollment action
        do_action('clarity_course_enrolled', $user_id, $course_id);
        
        return $enrollment_id;
    }
    
    /**
     * Auto-enroll in free course on registration
     */
    public function enroll_user_in_free_course($user_id) {
        global $wpdb;
        
        // Get tier 1 (free) course
        $free_course = $wpdb->get_row(
            "SELECT * FROM {$this->tables['courses']} 
            WHERE course_tier = 1 AND course_status = 'published' 
            LIMIT 1"
        );
        
        if ($free_course) {
            $this->enroll_user($user_id, $free_course->id, 'free');
        }
    }
    
    /**
     * Check if user can access lesson
     */
    public function can_access_lesson($user_id, $lesson_id) {
        global $wpdb;
        
        // Get lesson details
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} WHERE id = %d",
            $lesson_id
        ));
        
        if (!$lesson) {
            return false;
        }
        
        // Check enrollment
        $enrolled = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['enrollments']} 
            WHERE user_id = %d AND course_id = %d AND enrollment_status = 'active'",
            $user_id, $lesson->course_id
        ));
        
        if (!$enrolled) {
            return false;
        }
        
        // Check if it's the first lesson
        if ($lesson->lesson_order == 1) {
            return true;
        }
        
        // Check if previous lesson is completed
        $previous_lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} 
            WHERE course_id = %d AND lesson_order < %d 
            ORDER BY lesson_order DESC LIMIT 1",
            $lesson->course_id, $lesson->lesson_order
        ));
        
        if ($previous_lesson) {
            $is_completed = $wpdb->get_var($wpdb->prepare(
                "SELECT is_completed FROM {$this->tables['user_progress']} 
                WHERE user_id = %d AND lesson_id = %d",
                $user_id, $previous_lesson->id
            ));
            
            return (bool) $is_completed;
        }
        
        return true;
    }
    
    /**
     * Mark lesson as complete
     */
    public function mark_lesson_complete($user_id, $lesson_id) {
        global $wpdb;
        
        // Check if can access
        if (!$this->can_access_lesson($user_id, $lesson_id)) {
            return false;
        }
        
        // Get lesson details
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} WHERE id = %d",
            $lesson_id
        ));
        
        // Update or insert progress
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['user_progress']} 
            WHERE user_id = %d AND lesson_id = %d",
            $user_id, $lesson_id
        ));
        
        if ($existing) {
            $wpdb->update(
                $this->tables['user_progress'],
                array(
                    'is_completed' => 1,
                    'completion_date' => current_time('mysql')
                ),
                array(
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id
                )
            );
        } else {
            $wpdb->insert(
                $this->tables['user_progress'],
                array(
                    'user_id' => $user_id,
                    'course_id' => $lesson->course_id,
                    'lesson_id' => $lesson_id,
                    'is_completed' => 1,
                    'completion_date' => current_time('mysql')
                )
            );
        }
        
        // Update course progress
        $this->update_course_progress($user_id, $lesson->course_id);
        
        // Check if course is completed
        $this->check_course_completion($user_id, $lesson->course_id);
        
        return true;
    }
    
    /**
     * Update course progress percentage
     */
    private function update_course_progress($user_id, $course_id) {
        global $wpdb;
        
        // Get total lessons
        $total_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['lessons']} WHERE course_id = %d",
            $course_id
        ));
        
        // Get completed lessons
        $completed_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['user_progress']} 
            WHERE user_id = %d AND course_id = %d AND is_completed = 1",
            $user_id, $course_id
        ));
        
        $progress = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100) : 0;
        
        // Update enrollment progress
        $wpdb->update(
            $this->tables['enrollments'],
            array('progress_percentage' => $progress),
            array(
                'user_id' => $user_id,
                'course_id' => $course_id
            )
        );
        
        return $progress;
    }
    
    /**
     * Check if course is completed and trigger actions
     */
    private function check_course_completion($user_id, $course_id) {
        global $wpdb;
        
        $progress = $this->update_course_progress($user_id, $course_id);
        
        if ($progress >= 100) {
            // Update completion date
            $wpdb->update(
                $this->tables['enrollments'],
                array('completion_date' => current_time('mysql')),
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id
                )
            );
            
            // Trigger completion action
            do_action('clarity_course_completed', $user_id, $course_id);
            
            // Check for next tier unlock
            $this->check_next_tier_unlock($user_id, $course_id);
        }
    }
    
    /**
     * Check and unlock next tier course
     */
    private function check_next_tier_unlock($user_id, $completed_course_id) {
        global $wpdb;
        
        $completed_course = $this->get_course($completed_course_id);
        $next_tier = $completed_course->course_tier + 1;
        
        if ($next_tier <= 3) {
            // Get next tier course
            $next_course = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tables['courses']} 
                WHERE course_tier = %d AND course_status = 'published' 
                LIMIT 1",
                $next_tier
            ));
            
            if ($next_course) {
                // Send notification about next course availability
                do_action('clarity_next_course_unlocked', $user_id, $next_course->id);
            }
        }
    }
    
    /**
     * Get user's course progress
     */
    public function get_user_course_progress($user_id, $course_id) {
        global $wpdb;
        
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['enrollments']} 
            WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        if (!$enrollment) {
            return null;
        }
        
        $lessons = $this->get_course_lessons($course_id);
        $progress_data = array();
        
        foreach ($lessons as $lesson) {
            $progress = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tables['user_progress']} 
                WHERE user_id = %d AND lesson_id = %d",
                $user_id, $lesson->id
            ));
            
            $progress_data[] = array(
                'lesson' => $lesson,
                'completed' => $progress ? $progress->is_completed : 0,
                'completion_date' => $progress ? $progress->completion_date : null,
                'can_access' => $this->can_access_lesson($user_id, $lesson->id)
            );
        }
        
        return array(
            'enrollment' => $enrollment,
            'lessons' => $progress_data,
            'progress_percentage' => $enrollment->progress_percentage
        );
    }
    
    /**
     * Generate completion certificate
     */
    public function generate_certificate($user_id, $course_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        $course = $this->get_course($course_id);
        
        // Certificate data
        $certificate_data = array(
            'user_name' => $user->display_name,
            'course_name' => $course->course_title,
            'completion_date' => current_time('mysql'),
            'certificate_id' => wp_generate_uuid4()
        );
        
        // Generate certificate URL (placeholder for actual implementation)
        $certificate_url = site_url('/certificates/' . $certificate_data['certificate_id']);
        
        // Update enrollment with certificate
        $wpdb->update(
            $this->tables['enrollments'],
            array(
                'certificate_issued' => 1,
                'certificate_url' => $certificate_url
            ),
            array(
                'user_id' => $user_id,
                'course_id' => $course_id
            )
        );
        
        // Send certificate email
        do_action('clarity_certificate_generated', $user_id, $course_id, $certificate_url);
        
        return $certificate_url;
    }
    
    /**
     * AJAX: Enroll in course
     */
    public function ajax_enroll_in_course() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $course_id = intval($_POST['course_id']);
        
        if (!$user_id) {
            wp_send_json_error('Please login to enroll');
        }
        
        $enrollment_id = $this->enroll_user($user_id, $course_id);
        
        if ($enrollment_id) {
            wp_send_json_success(array(
                'message' => 'Successfully enrolled in course',
                'enrollment_id' => $enrollment_id
            ));
        } else {
            wp_send_json_error('Failed to enroll in course');
        }
    }
    
    /**
     * AJAX: Mark lesson complete
     */
    public function ajax_mark_lesson_complete() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = intval($_POST['lesson_id']);
        
        if (!$user_id) {
            wp_send_json_error('Please login to complete lessons');
        }
        
        $result = $this->mark_lesson_complete($user_id, $lesson_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Lesson marked as complete',
                'next_lesson' => $this->get_next_lesson($lesson_id)
            ));
        } else {
            wp_send_json_error('Unable to mark lesson as complete');
        }
    }
    
    /**
     * Get next lesson in sequence
     */
    private function get_next_lesson($current_lesson_id) {
        global $wpdb;
        
        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} WHERE id = %d",
            $current_lesson_id
        ));
        
        if ($current) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tables['lessons']} 
                WHERE course_id = %d AND lesson_order > %d 
                ORDER BY lesson_order ASC LIMIT 1",
                $current->course_id, $current->lesson_order
            ));
        }
        
        return null;
    }
}