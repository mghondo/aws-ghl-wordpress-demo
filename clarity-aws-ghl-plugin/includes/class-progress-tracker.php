<?php
/**
 * User Progress Tracking System
 *
 * Handles frontend user progress tracking and dashboard functionality
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Progress_Tracker {
    
    /**
     * Course manager instance
     */
    private $course_manager;
    private $lesson_handler;
    private $db_courses;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        $this->course_manager = new Clarity_AWS_GHL_Course_Manager();
        $this->lesson_handler = new Clarity_AWS_GHL_Lesson_Handler();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Frontend AJAX handlers
        add_action('wp_ajax_clarity_get_user_progress', array($this, 'ajax_get_user_progress'));
        add_action('wp_ajax_clarity_update_lesson_progress', array($this, 'ajax_update_lesson_progress'));
        add_action('wp_ajax_clarity_save_lesson_notes', array($this, 'ajax_save_lesson_notes'));
        add_action('wp_ajax_clarity_get_next_lesson', array($this, 'ajax_get_next_lesson'));
        add_action('wp_ajax_clarity_generate_user_certificate', array($this, 'ajax_generate_user_certificate'));
        
        // Shortcodes
        add_shortcode('clarity_user_dashboard', array($this, 'render_user_dashboard'));
        add_shortcode('clarity_course_progress', array($this, 'render_course_progress'));
        add_shortcode('clarity_lesson_navigation', array($this, 'render_lesson_navigation'));
        
        // Frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // User profile fields
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on pages that need it
        if (!$this->should_enqueue_scripts()) {
            return;
        }
        
        wp_enqueue_style(
            'clarity-progress-tracker',
            CLARITY_AWS_GHL_PLUGIN_URL . 'assets/css/progress-tracker.css',
            array(),
            CLARITY_AWS_GHL_VERSION
        );
        
        wp_enqueue_script(
            'clarity-progress-tracker',
            CLARITY_AWS_GHL_PLUGIN_URL . 'assets/js/progress-tracker.js',
            array('jquery'),
            CLARITY_AWS_GHL_VERSION,
            true
        );
        
        wp_localize_script('clarity-progress-tracker', 'clarityProgress', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clarity_progress_nonce'),
            'user_id' => get_current_user_id(),
            'strings' => array(
                'loading' => __('Loading...', 'clarity-aws-ghl'),
                'error' => __('An error occurred', 'clarity-aws-ghl'),
                'success' => __('Success!', 'clarity-aws-ghl'),
                'confirm_complete' => __('Mark this lesson as complete?', 'clarity-aws-ghl'),
                'lesson_completed' => __('Lesson completed!', 'clarity-aws-ghl'),
                'generating_certificate' => __('Generating certificate...', 'clarity-aws-ghl'),
                'certificate_generated' => __('Certificate generated! Refreshing page...', 'clarity-aws-ghl'),
                'certificate_error' => __('Failed to generate certificate', 'clarity-aws-ghl')
            )
        ));
    }
    
    /**
     * Check if scripts should be enqueued
     */
    private function should_enqueue_scripts() {
        global $post;
        
        // Enqueue on posts/pages with course shortcodes
        if (is_singular() && $post) {
            if (has_shortcode($post->post_content, 'clarity_user_dashboard') ||
                has_shortcode($post->post_content, 'clarity_course_progress') ||
                has_shortcode($post->post_content, 'clarity_lesson_navigation') ||
                has_shortcode($post->post_content, 'clarity_course_player')) {
                return true;
            }
        }
        
        // Enqueue on course-related pages
        if (is_user_logged_in() && (is_page('courses') || is_page('my-progress') || is_page('dashboard'))) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render user dashboard shortcode
     */
    public function render_user_dashboard($atts) {
        $atts = shortcode_atts(array(
            'show_progress' => 'true',
            'show_certificates' => 'true',
            'show_recent_activity' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'clarity-aws-ghl') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $user_data = $this->get_user_dashboard_data($user_id);
        
        ob_start();
        ?>
        <div class="clarity-user-dashboard">
            <div class="dashboard-header">
                <h2><?php printf(__('Welcome back, %s!', 'clarity-aws-ghl'), wp_get_current_user()->display_name); ?></h2>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo esc_html($user_data['total_courses']); ?></span>
                        <span class="stat-label"><?php _e('Enrolled Courses', 'clarity-aws-ghl'); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo esc_html($user_data['completed_lessons']); ?></span>
                        <span class="stat-label"><?php _e('Lessons Completed', 'clarity-aws-ghl'); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo esc_html($user_data['certificates']); ?></span>
                        <span class="stat-label"><?php _e('Certificates Earned', 'clarity-aws-ghl'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_progress'] === 'true'): ?>
            <div class="dashboard-section">
                <h3><?php _e('Course Progress', 'clarity-aws-ghl'); ?></h3>
                <div class="course-progress-list">
                    <?php foreach ($user_data['enrollments'] as $enrollment): ?>
                        <div class="course-progress-item">
                            <div class="course-info">
                                <h4><?php echo esc_html($enrollment['course_title']); ?></h4>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($enrollment['progress']); ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo esc_html($enrollment['progress']); ?>% Complete</span>
                            </div>
                            <div class="course-actions">
                                <a href="<?php echo esc_url($enrollment['continue_url']); ?>" class="button button-primary">
                                    <?php echo $enrollment['progress'] > 0 ? __('Continue', 'clarity-aws-ghl') : __('Start Course', 'clarity-aws-ghl'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_recent_activity'] === 'true' && !empty($user_data['recent_activity'])): ?>
            <div class="dashboard-section">
                <h3><?php _e('Recent Activity', 'clarity-aws-ghl'); ?></h3>
                <div class="activity-list">
                    <?php foreach ($user_data['recent_activity'] as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <span class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                            </div>
                            <div class="activity-content">
                                <p><?php echo esc_html($activity['description']); ?></p>
                                <span class="activity-time"><?php echo esc_html($activity['time_ago']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render course progress shortcode
     */
    public function render_course_progress($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'show_lessons' => 'true',
            'show_navigation' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view course progress.', 'clarity-aws-ghl') . '</p>';
        }
        
        if (empty($atts['course_id'])) {
            return '<p>' . __('Course ID is required.', 'clarity-aws-ghl') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $course_id = intval($atts['course_id']);
        $progress_data = $this->course_manager->get_user_course_progress($user_id, $course_id);
        
        if (!$progress_data) {
            return '<p>' . __('You are not enrolled in this course.', 'clarity-aws-ghl') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="clarity-course-progress" data-course-id="<?php echo esc_attr($course_id); ?>">
            <div class="course-progress-header">
                <h3><?php _e('Course Progress', 'clarity-aws-ghl'); ?></h3>
                <div class="overall-progress">
                    <div class="progress-circle">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle" stroke-dasharray="<?php echo esc_attr($progress_data['progress_percentage']); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="percentage"><?php echo esc_html($progress_data['progress_percentage']); ?>%</text>
                        </svg>
                    </div>
                    <div class="progress-stats">
                        <p><?php printf(__('%d of %d lessons completed', 'clarity-aws-ghl'), 
                            count(array_filter($progress_data['lessons'], function($l) { return $l['completed']; })),
                            count($progress_data['lessons'])
                        ); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_lessons'] === 'true'): ?>
            <div class="lessons-list">
                <?php foreach ($progress_data['lessons'] as $index => $lesson_data): ?>
                    <div class="lesson-item <?php echo $lesson_data['completed'] ? 'completed' : ''; ?> <?php echo $lesson_data['can_access'] ? 'accessible' : 'locked'; ?>">
                        <div class="lesson-status">
                            <?php if ($lesson_data['completed']): ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php elseif ($lesson_data['can_access']): ?>
                                <span class="lesson-number"><?php echo $index + 1; ?></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-lock"></span>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-content">
                            <h4><?php echo esc_html($lesson_data['lesson']->lesson_title); ?></h4>
                            <?php if (!empty($lesson_data['lesson']->duration_minutes)): ?>
                                <span class="lesson-duration"><?php echo esc_html($lesson_data['lesson']->duration_minutes); ?> min</span>
                            <?php endif; ?>
                            <?php if ($lesson_data['completion_date']): ?>
                                <span class="completion-date"><?php printf(__('Completed %s', 'clarity-aws-ghl'), 
                                    date('M j, Y', strtotime($lesson_data['completion_date']))); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-actions">
                            <?php if ($lesson_data['can_access']): ?>
                                <button class="button start-lesson" data-lesson-id="<?php echo esc_attr($lesson_data['lesson']->id); ?>">
                                    <?php echo $lesson_data['completed'] ? __('Review', 'clarity-aws-ghl') : __('Start', 'clarity-aws-ghl'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render lesson navigation shortcode
     */
    public function render_lesson_navigation($atts) {
        $atts = shortcode_atts(array(
            'lesson_id' => '',
            'course_id' => '',
            'show_progress' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '';
        }
        
        $lesson_id = intval($atts['lesson_id']);
        $course_id = intval($atts['course_id']);
        $user_id = get_current_user_id();
        
        if (!$lesson_id || !$course_id) {
            return '';
        }
        
        $navigation_data = $this->get_lesson_navigation_data($user_id, $lesson_id, $course_id);
        
        ob_start();
        ?>
        <div class="clarity-lesson-navigation" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
            <div class="lesson-nav-header">
                <?php if ($atts['show_progress'] === 'true'): ?>
                <div class="lesson-progress-indicator">
                    <span><?php printf(__('Lesson %d of %d', 'clarity-aws-ghl'), 
                        $navigation_data['current_position'], $navigation_data['total_lessons']); ?></span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr($navigation_data['course_progress']); ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="lesson-nav-actions">
                <?php if ($navigation_data['previous_lesson']): ?>
                    <a href="<?php echo esc_url($navigation_data['previous_lesson']['url']); ?>" class="button nav-button nav-previous">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php _e('Previous Lesson', 'clarity-aws-ghl'); ?>
                    </a>
                <?php endif; ?>
                
                <div class="lesson-center-actions">
                    <?php if (!$navigation_data['is_completed']): ?>
                        <button class="button button-primary complete-lesson" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                            <?php _e('Mark Complete', 'clarity-aws-ghl'); ?>
                        </button>
                    <?php else: ?>
                        <span class="lesson-completed-indicator">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Completed', 'clarity-aws-ghl'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($navigation_data['next_lesson']): ?>
                    <a href="<?php echo esc_url($navigation_data['next_lesson']['url']); ?>" 
                       class="button nav-button nav-next <?php echo $navigation_data['next_lesson']['accessible'] ? '' : 'disabled'; ?>">
                        <?php _e('Next Lesson', 'clarity-aws-ghl'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php elseif ($navigation_data['course_progress'] >= 100): ?>
                    <div class="course-completed">
                        <span class="completion-badge">
                            <span class="dashicons dashicons-awards"></span>
                            <?php _e('Course Complete!', 'clarity-aws-ghl'); ?>
                        </span>
                        <?php if ($navigation_data['certificate_url']): ?>
                            <a href="<?php echo esc_url($navigation_data['certificate_url']); ?>" class="button button-primary certificate-download">
                                <?php _e('Download Certificate', 'clarity-aws-ghl'); ?>
                            </a>
                        <?php else: ?>
                            <button type="button" class="button button-primary get-certificate" 
                                    data-user-id="<?php echo get_current_user_id(); ?>" 
                                    data-course-id="<?php echo esc_attr($course_id); ?>">
                                <?php _e('Get Certificate', 'clarity-aws-ghl'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get user dashboard data
     */
    private function get_user_dashboard_data($user_id) {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        // Get enrollments
        $enrollments = $wpdb->get_results($wpdb->prepare("
            SELECT e.*, c.course_title, c.course_slug 
            FROM {$tables['enrollments']} e
            JOIN {$tables['courses']} c ON e.course_id = c.id
            WHERE e.user_id = %d AND e.enrollment_status = 'active'
            ORDER BY e.enrollment_date DESC
        ", $user_id));
        
        // Get stats
        $total_courses = count($enrollments);
        $completed_lessons = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$tables['user_progress']} 
            WHERE user_id = %d AND is_completed = 1
        ", $user_id));
        
        $certificates = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$tables['enrollments']} 
            WHERE user_id = %d AND certificate_issued = 1
        ", $user_id));
        
        // Format enrollments
        $enrollment_data = array();
        foreach ($enrollments as $enrollment) {
            $enrollment_data[] = array(
                'course_title' => $enrollment->course_title,
                'progress' => $enrollment->progress_percentage,
                'continue_url' => home_url('/course/' . $enrollment->course_slug)
            );
        }
        
        // Get recent activity
        $recent_activity = $this->get_user_recent_activity($user_id);
        
        return array(
            'total_courses' => $total_courses,
            'completed_lessons' => $completed_lessons,
            'certificates' => $certificates,
            'enrollments' => $enrollment_data,
            'recent_activity' => $recent_activity
        );
    }
    
    /**
     * Get user recent activity
     */
    private function get_user_recent_activity($user_id, $limit = 5) {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT p.completion_date, l.lesson_title, c.course_title,
                   'lesson_completed' as activity_type
            FROM {$tables['user_progress']} p
            JOIN {$tables['lessons']} l ON p.lesson_id = l.id
            JOIN {$tables['courses']} c ON p.course_id = c.id
            WHERE p.user_id = %d AND p.is_completed = 1
            ORDER BY p.completion_date DESC
            LIMIT %d
        ", $user_id, $limit));
        
        $formatted_activities = array();
        foreach ($activities as $activity) {
            $formatted_activities[] = array(
                'description' => sprintf(__('Completed "%s" in %s', 'clarity-aws-ghl'), 
                    $activity->lesson_title, $activity->course_title),
                'time_ago' => human_time_diff(strtotime($activity->completion_date), current_time('timestamp')) . ' ago',
                'icon' => 'yes-alt'
            );
        }
        
        return $formatted_activities;
    }
    
    /**
     * Get lesson navigation data
     */
    private function get_lesson_navigation_data($user_id, $lesson_id, $course_id) {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        // Get current lesson
        $current_lesson = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$tables['lessons']} WHERE id = %d
        ", $lesson_id));
        
        // Get lesson progress
        $is_completed = $wpdb->get_var($wpdb->prepare("
            SELECT is_completed FROM {$tables['user_progress']} 
            WHERE user_id = %d AND lesson_id = %d
        ", $user_id, $lesson_id));
        
        // Get course progress
        $course_progress = $this->course_manager->get_user_course_progress($user_id, $course_id);
        
        // Get navigation lessons
        $previous_lesson = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$tables['lessons']} 
            WHERE course_id = %d AND lesson_order < %d 
            ORDER BY lesson_order DESC LIMIT 1
        ", $course_id, $current_lesson->lesson_order));
        
        $next_lesson = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$tables['lessons']} 
            WHERE course_id = %d AND lesson_order > %d 
            ORDER BY lesson_order ASC LIMIT 1
        ", $course_id, $current_lesson->lesson_order));
        
        return array(
            'current_position' => $current_lesson->lesson_order,
            'total_lessons' => count($course_progress['lessons']),
            'course_progress' => $course_progress['progress_percentage'],
            'is_completed' => (bool) $is_completed,
            'previous_lesson' => $previous_lesson ? array(
                'url' => home_url('/lesson/' . $previous_lesson->lesson_slug)
            ) : null,
            'next_lesson' => $next_lesson ? array(
                'url' => home_url('/lesson/' . $next_lesson->lesson_slug),
                'accessible' => $this->course_manager->can_access_lesson($user_id, $next_lesson->id)
            ) : null,
            'certificate_url' => $course_progress['enrollment']->certificate_url
        );
    }
    
    /**
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        if (!current_user_can('manage_options') && get_current_user_id() !== $user->ID) {
            return;
        }
        
        $user_stats = $this->get_user_dashboard_data($user->ID);
        ?>
        <h3><?php _e('Course Progress', 'clarity-aws-ghl'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Enrolled Courses', 'clarity-aws-ghl'); ?></label></th>
                <td><?php echo esc_html($user_stats['total_courses']); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('Completed Lessons', 'clarity-aws-ghl'); ?></label></th>
                <td><?php echo esc_html($user_stats['completed_lessons']); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('Certificates Earned', 'clarity-aws-ghl'); ?></label></th>
                <td><?php echo esc_html($user_stats['certificates']); ?></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user profile fields (placeholder for future custom fields)
     */
    public function save_user_profile_fields($user_id) {
        // Currently no custom fields to save, but this is where they would go
    }
    
    /**
     * AJAX Handlers
     */
    public function ajax_get_user_progress() {
        check_ajax_referer('clarity_progress_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $course_id = intval($_POST['course_id']);
        $progress = $this->course_manager->get_user_course_progress($user_id, $course_id);
        
        wp_send_json_success($progress);
    }
    
    public function ajax_update_lesson_progress() {
        check_ajax_referer('clarity_progress_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $time_spent = intval($_POST['time_spent']);
        $position = intval($_POST['position']);
        $is_completed = isset($_POST['is_completed']) ? (bool) $_POST['is_completed'] : false;
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        // Get course_id for this lesson
        $course_id = $wpdb->get_var($wpdb->prepare("
            SELECT course_id FROM {$tables['lessons']} WHERE id = %d
        ", $lesson_id));
        
        // Update time tracking and completion status
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$tables['user_progress']} 
            (user_id, course_id, lesson_id, is_completed, completion_date, time_spent_seconds, last_position_seconds, created_at)
            VALUES (%d, %d, %d, %d, %s, %d, %d, NOW())
            ON DUPLICATE KEY UPDATE 
            is_completed = VALUES(is_completed),
            completion_date = CASE WHEN VALUES(is_completed) = 1 AND is_completed = 0 THEN NOW() ELSE completion_date END,
            time_spent_seconds = time_spent_seconds + VALUES(time_spent_seconds),
            last_position_seconds = VALUES(last_position_seconds),
            updated_at = NOW()
        ", $user_id, $course_id, $lesson_id, $is_completed, $is_completed ? current_time('mysql') : null, $time_spent, $position));
        
        // If lesson was just completed, check if course is now complete
        if ($is_completed) {
            $this->check_course_completion($user_id, $course_id);
        }
        
        wp_send_json_success();
    }
    
    /**
     * Check if course is complete and trigger certificate generation
     */
    private function check_course_completion($user_id, $course_id) {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        // Get total lessons in course
        $total_lessons = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$tables['lessons']} WHERE course_id = %d
        ", $course_id));
        
        // Get completed lessons for this user
        $completed_lessons = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$tables['user_progress']} 
            WHERE user_id = %d AND course_id = %d AND is_completed = 1
        ", $user_id, $course_id));
        
        // Calculate progress percentage
        $progress_percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
        
        // Update enrollment progress
        $wpdb->update(
            $tables['enrollments'],
            array(
                'progress_percentage' => $progress_percentage,
                'completion_date' => $progress_percentage >= 100 ? current_time('mysql') : null,
                'updated_at' => current_time('mysql')
            ),
            array(
                'user_id' => $user_id,
                'course_id' => $course_id
            )
        );
        
        // If course is 100% complete, trigger certificate generation
        if ($progress_percentage >= 100) {
            do_action('clarity_course_completed', $user_id, $course_id);
        }
    }
    
    public function ajax_save_lesson_notes() {
        check_ajax_referer('clarity_progress_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$tables['user_progress']} 
            (user_id, lesson_id, notes, created_at)
            VALUES (%d, %d, %s, NOW())
            ON DUPLICATE KEY UPDATE 
            notes = VALUES(notes),
            updated_at = NOW()
        ", $user_id, $lesson_id, $notes));
        
        wp_send_json_success();
    }
    
    public function ajax_get_next_lesson() {
        check_ajax_referer('clarity_progress_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $current_lesson_id = intval($_POST['lesson_id']);
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $current = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$tables['lessons']} WHERE id = %d
        ", $current_lesson_id));
        
        if ($current) {
            $next = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$tables['lessons']} 
                WHERE course_id = %d AND lesson_order > %d 
                ORDER BY lesson_order ASC LIMIT 1
            ", $current->course_id, $current->lesson_order));
            
            if ($next) {
                wp_send_json_success(array(
                    'lesson_id' => $next->id,
                    'lesson_title' => $next->lesson_title,
                    'lesson_slug' => $next->lesson_slug,
                    'can_access' => $this->course_manager->can_access_lesson($user_id, $next->id)
                ));
            }
        }
        
        wp_send_json_error('No next lesson found');
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
        $tables = $this->db_courses->get_table_names();
        
        $enrollment = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$tables['enrollments']} 
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
}