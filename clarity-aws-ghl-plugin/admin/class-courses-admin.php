<?php
/**
 * Course Management Admin Pages
 *
 * Handles admin interface for course management system
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Courses_Admin {
    
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
        add_action('admin_menu', array($this, 'add_course_menu_pages'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_clarity_create_course', array($this, 'ajax_create_course'));
        add_action('wp_ajax_clarity_get_course', array($this, 'ajax_get_course'));
        add_action('wp_ajax_clarity_update_course', array($this, 'ajax_update_course'));
        add_action('wp_ajax_clarity_create_lesson', array($this, 'ajax_create_lesson'));
        add_action('wp_ajax_clarity_edit_lesson', array($this, 'ajax_edit_lesson'));
        add_action('wp_ajax_clarity_delete_lesson', array($this, 'ajax_delete_lesson'));
        add_action('wp_ajax_clarity_get_lessons', array($this, 'ajax_get_lessons'));
        add_action('wp_ajax_clarity_reorder_lessons', array($this, 'ajax_reorder_lessons'));
        add_action('wp_ajax_clarity_delete_course', array($this, 'ajax_delete_course'));
        add_action('wp_ajax_clarity_reset_user_progress', array($this, 'ajax_reset_user_progress'));
        add_action('wp_ajax_clarity_complete_lesson', array($this, 'ajax_complete_lesson'));
        add_action('wp_ajax_clarity_enroll_user', array($this, 'ajax_enroll_user'));
        add_action('wp_ajax_clarity_get_course_stats', array($this, 'ajax_get_course_stats'));
        
        // Standalone lesson management AJAX handlers
        add_action('wp_ajax_clarity_create_standalone_lesson', array($this, 'ajax_create_standalone_lesson'));
        add_action('wp_ajax_clarity_get_standalone_lesson', array($this, 'ajax_get_standalone_lesson'));
        add_action('wp_ajax_clarity_edit_standalone_lesson', array($this, 'ajax_edit_standalone_lesson'));
        add_action('wp_ajax_clarity_delete_standalone_lesson', array($this, 'ajax_delete_standalone_lesson'));
        
        // Lesson assignment AJAX handlers
        add_action('wp_ajax_clarity_get_available_lessons', array($this, 'ajax_get_available_lessons'));
        add_action('wp_ajax_clarity_get_course_lessons', array($this, 'ajax_get_course_lessons'));
        add_action('wp_ajax_clarity_assign_lesson_to_course', array($this, 'ajax_assign_lesson_to_course'));
        add_action('wp_ajax_clarity_remove_lesson_from_course', array($this, 'ajax_remove_lesson_from_course'));
    }
    
    /**
     * Add course management pages to existing AWS GHL menu
     */
    public function add_course_menu_pages() {
        add_submenu_page(
            'clarity-aws-ghl',
            __('Course Management', 'clarity-aws-ghl'),
            __('Course Management', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-courses',
            array($this, 'render_courses_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Lessons', 'clarity-aws-ghl'),
            __('Lessons', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-lessons',
            array($this, 'render_lessons_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Student Progress', 'clarity-aws-ghl'),
            __('Student Progress', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-progress',
            array($this, 'render_progress_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            __('Course Analytics', 'clarity-aws-ghl'),
            __('Course Analytics', 'clarity-aws-ghl'),
            'manage_options',
            'clarity-aws-ghl-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'clarity-aws-ghl-courses') === false && 
            strpos($hook, 'clarity-aws-ghl-lessons') === false &&
            strpos($hook, 'clarity-aws-ghl-progress') === false &&
            strpos($hook, 'clarity-aws-ghl-analytics') === false) {
            return;
        }
        
        wp_enqueue_script(
            'clarity-courses-admin',
            CLARITY_AWS_GHL_PLUGIN_URL . 'admin/js/courses-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            CLARITY_AWS_GHL_VERSION,
            true
        );
        
        wp_localize_script('clarity-courses-admin', 'clarityCoursesAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clarity_courses_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this course?', 'clarity-aws-ghl'),
                'confirm_reset' => __('Are you sure you want to reset this user\'s progress?', 'clarity-aws-ghl'),
                'processing' => __('Processing...', 'clarity-aws-ghl'),
                'success' => __('Success!', 'clarity-aws-ghl'),
                'error' => __('Error', 'clarity-aws-ghl')
            )
        ));
    }
    
    /**
     * Render course management page
     */
    public function render_courses_page() {
        $courses = $this->course_manager->get_all_courses(array('status' => ''));
        ?>
        <div class="wrap">
            <h1><?php _e('Course Management', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-admin-header">
                <div class="clarity-stats-grid">
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo count($courses); ?></div>
                        <div class="clarity-stat-label"><?php _e('Total Courses', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo $this->get_total_students(); ?></div>
                        <div class="clarity-stat-label"><?php _e('Total Students', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo $this->get_completion_rate(); ?>%</div>
                        <div class="clarity-stat-label"><?php _e('Completion Rate', 'clarity-aws-ghl'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="clarity-admin-section">
                <h2><?php _e('Course Management', 'clarity-aws-ghl'); ?></h2>
                
                <div class="clarity-course-actions">
                    <button type="button" class="button button-primary" id="add-course-btn">
                        <?php _e('Add New Course', 'clarity-aws-ghl'); ?>
                    </button>
                    <button type="button" class="button" id="bulk-actions-btn">
                        <?php _e('Bulk Actions', 'clarity-aws-ghl'); ?>
                    </button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="check-column"><input type="checkbox" id="select-all-courses"></th>
                            <th scope="col"><?php _e('Course Title', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Tier', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Price', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Lessons', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Students', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Status', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Actions', 'clarity-aws-ghl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr data-course-id="<?php echo esc_attr($course->id); ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="course[]" value="<?php echo esc_attr($course->id); ?>">
                                </th>
                                <td>
                                    <strong><?php echo esc_html($course->course_title); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="#" class="edit-course" data-course-id="<?php echo esc_attr($course->id); ?>">
                                                <?php _e('Edit', 'clarity-aws-ghl'); ?>
                                            </a> |
                                        </span>
                                        <span class="view">
                                            <a href="#" class="view-lessons" data-course-id="<?php echo esc_attr($course->id); ?>">
                                                <?php _e('Lessons', 'clarity-aws-ghl'); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="#" class="delete-course" data-course-id="<?php echo esc_attr($course->id); ?>">
                                                <?php _e('Delete', 'clarity-aws-ghl'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($course->course_tier); ?></td>
                                <td>$<?php echo esc_html($course->course_price); ?></td>
                                <td><?php echo esc_html($course->total_lessons); ?></td>
                                <td><?php echo $this->get_course_student_count($course->id); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($course->course_status); ?>">
                                        <?php echo esc_html(ucfirst($course->course_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small edit-course" 
                                            data-course-id="<?php echo esc_attr($course->id); ?>">
                                        <?php _e('Edit', 'clarity-aws-ghl'); ?>
                                    </button>
                                    <button type="button" class="button button-small manage-lessons" 
                                            data-course-id="<?php echo esc_attr($course->id); ?>"
                                            data-course-name="<?php echo esc_attr($course->course_title); ?>">
                                        <?php _e('Manage Lessons', 'clarity-aws-ghl'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add Course Modal -->
            <div id="add-course-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Add New Course', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="add-course-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Course Title', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" name="course_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Course Slug', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" name="course_slug" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'clarity-aws-ghl'); ?></th>
                                    <td><textarea name="course_description" rows="4" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Course Tier', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="course_tier" required>
                                            <option value="1"><?php _e('Tier 1 - Free ($0)', 'clarity-aws-ghl'); ?></option>
                                            <option value="2"><?php _e('Tier 2 - Core ($497)', 'clarity-aws-ghl'); ?></option>
                                            <option value="3"><?php _e('Tier 3 - Premium ($1997)', 'clarity-aws-ghl'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Custom Price', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="number" name="course_price" step="0.01" class="small-text"> 
                                    <span class="description"><?php _e('Leave empty to use tier default', 'clarity-aws-ghl'); ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Status', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="course_status">
                                            <option value="draft"><?php _e('Draft', 'clarity-aws-ghl'); ?></option>
                                            <option value="published"><?php _e('Published', 'clarity-aws-ghl'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button button-primary" id="save-course-btn">
                            <?php _e('Create Course', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button clarity-modal-close">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Edit Course Modal -->
            <div id="edit-course-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Edit Course', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="edit-course-form">
                            <input type="hidden" id="edit-course-id" name="course_id">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Course Title', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" name="course_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'clarity-aws-ghl'); ?></th>
                                    <td><textarea name="course_description" rows="4" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Course Price', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <input type="number" name="course_price" step="0.01" min="0" class="regular-text" placeholder="0.00">
                                        <p class="description"><?php _e('Enter price in dollars (e.g., 497.00 for $497)', 'clarity-aws-ghl'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Course Status', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="course_status">
                                            <option value="published"><?php _e('Published', 'clarity-aws-ghl'); ?></option>
                                            <option value="draft"><?php _e('Draft', 'clarity-aws-ghl'); ?></option>
                                            <option value="archived"><?php _e('Archived', 'clarity-aws-ghl'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button button-primary" id="update-course-btn">
                            <?php _e('Update Course', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button clarity-modal-close">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Lesson Management Modal -->
            <div id="lesson-management-modal" class="clarity-modal clarity-modal-large" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3 id="lesson-modal-title"><?php _e('Manage Lessons', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <div class="lesson-assignment-container">
                            <input type="hidden" id="lesson-course-id" value="">
                            <h4 id="lesson-course-name"><?php _e('Course Name', 'clarity-aws-ghl'); ?></h4>
                            
                            <div class="lesson-assignment-grid">
                                <!-- Available Lessons Column -->
                                <div class="available-lessons-section">
                                    <h4><?php _e('Available Lessons', 'clarity-aws-ghl'); ?></h4>
                                    <p class="section-description"><?php _e('Click to add lessons to this course', 'clarity-aws-ghl'); ?></p>
                                    <div id="available-lessons-list" class="lessons-list">
                                        <div class="lessons-loading">
                                            <p><?php _e('Loading available lessons...', 'clarity-aws-ghl'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Assigned Lessons Column -->
                                <div class="assigned-lessons-section">
                                    <h4><?php _e('Course Lessons', 'clarity-aws-ghl'); ?></h4>
                                    <p class="section-description"><?php _e('Drag to reorder • Click to remove', 'clarity-aws-ghl'); ?></p>
                                    <div id="assigned-lessons-list" class="lessons-list sortable-lessons">
                                        <div class="lessons-loading">
                                            <p><?php _e('Loading course lessons...', 'clarity-aws-ghl'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="lesson-assignment-footer">
                                <p class="lesson-count-info">
                                    <span id="available-count">0</span> <?php _e('available', 'clarity-aws-ghl'); ?> • 
                                    <span id="assigned-count">0</span> <?php _e('assigned', 'clarity-aws-ghl'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add/Edit Lesson Modal -->
            <div id="lesson-form-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3 id="lesson-form-title"><?php _e('Add New Lesson', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="lesson-form">
                            <input type="hidden" id="lesson-id" name="lesson_id" value="">
                            <input type="hidden" id="course-id" name="course_id" value="">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Lesson Title', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" id="lesson-title" name="lesson_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Video URL', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <input type="url" id="lesson-video-url" name="video_url" class="regular-text" placeholder="https://youtube.com/watch?v=...">
                                        <p class="description"><?php _e('YouTube or Vimeo URL', 'clarity-aws-ghl'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'clarity-aws-ghl'); ?></th>
                                    <td><textarea id="lesson-description" name="lesson_description" rows="3" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Duration (minutes)', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="number" id="lesson-duration" name="duration_minutes" class="small-text" min="1"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Lesson Order', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="number" id="lesson-order" name="lesson_order" class="small-text" min="1" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Preview Lesson', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="lesson-preview" name="is_preview" value="1">
                                            <?php _e('Allow non-enrolled users to preview this lesson', 'clarity-aws-ghl'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <div id="video-preview-container" style="display: none;">
                                <h4><?php _e('Video Preview', 'clarity-aws-ghl'); ?></h4>
                                <div id="video-preview"></div>
                            </div>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button button-primary" id="save-lesson-btn">
                            <?php _e('Save Lesson', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button" id="preview-video-btn">
                            <?php _e('Preview Video', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button clarity-modal-close">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render student progress page
     */
    public function render_progress_page() {
        $users = get_users(array('role' => 'student'));
        ?>
        <div class="wrap">
            <h1><?php _e('Student Progress', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-admin-section">
                <h2><?php _e('Student Management & Testing Controls', 'clarity-aws-ghl'); ?></h2>
                
                <div class="clarity-testing-controls">
                    <h3><?php _e('Admin Testing Controls', 'clarity-aws-ghl'); ?></h3>
                    <div class="clarity-control-group">
                        <label for="test-user-select"><?php _e('Select User:', 'clarity-aws-ghl'); ?></label>
                        <select id="test-user-select">
                            <option value=""><?php _e('Select a student...', 'clarity-aws-ghl'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="clarity-control-group">
                        <label for="test-course-select"><?php _e('Select Course:', 'clarity-aws-ghl'); ?></label>
                        <select id="test-course-select">
                            <option value=""><?php _e('Select a course...', 'clarity-aws-ghl'); ?></option>
                            <?php
                            $courses = $this->course_manager->get_all_courses();
                            foreach ($courses as $course):
                            ?>
                                <option value="<?php echo esc_attr($course->id); ?>">
                                    <?php echo esc_html($course->course_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="clarity-control-actions">
                        <button type="button" class="button button-primary" id="enroll-user-btn">
                            <?php _e('Enroll User', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button" id="complete-lesson-btn">
                            <?php _e('Mark Next Lesson Complete', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="reset-progress-btn">
                            <?php _e('Reset Progress', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Student', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Course', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Progress', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Enrolled', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Last Activity', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Actions', 'clarity-aws-ghl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $enrollments = $this->get_all_enrollments();
                        foreach ($enrollments as $enrollment):
                            $user = get_userdata($enrollment->user_id);
                            $course = $this->course_manager->get_course($enrollment->course_id);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                    <small><?php echo esc_html($user->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html($course->course_title); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo esc_attr($enrollment->progress_percentage); ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo esc_html($enrollment->progress_percentage); ?>%</span>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($enrollment->enrollment_date))); ?></td>
                                <td><?php echo $enrollment->updated_at ? esc_html(date('M j, Y', strtotime($enrollment->updated_at))) : '-'; ?></td>
                                <td>
                                    <button type="button" class="button button-small view-progress" 
                                            data-user-id="<?php echo esc_attr($enrollment->user_id); ?>"
                                            data-course-id="<?php echo esc_attr($enrollment->course_id); ?>">
                                        <?php _e('View Details', 'clarity-aws-ghl'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render lessons management page
     */
    public function render_lessons_page() {
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Get all lessons
        $lessons = $wpdb->get_results("SELECT * FROM {$tables['lessons']} ORDER BY lesson_title");
        ?>
        <div class="wrap">
            <h1><?php _e('Lesson Management', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-admin-section">
                <div class="clarity-course-actions">
                    <button type="button" class="button button-primary" id="add-new-lesson-btn">
                        <?php _e('Add New Lesson', 'clarity-aws-ghl'); ?>
                    </button>
                </div>
                
                <h2><?php _e('All Lessons', 'clarity-aws-ghl'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Title', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Video URL', 'clarity-aws-ghl'); ?></th>
                            <th scope="col" style="width: 100px;"><?php _e('Type', 'clarity-aws-ghl'); ?></th>
                            <th scope="col" style="width: 150px;"><?php _e('Actions', 'clarity-aws-ghl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lessons)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px;">
                                    <?php _e('No lessons found. Create your first lesson!', 'clarity-aws-ghl'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                                    <td>
                                        <strong><?php echo esc_html($lesson->lesson_title); ?></strong>
                                        <?php if (!empty($lesson->lesson_content)): ?>
                                            <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($lesson->lesson_content, 15)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($lesson->video_url)): ?>
                                            <a href="<?php echo esc_url($lesson->video_url); ?>" target="_blank">
                                                <?php echo esc_html(wp_trim_words($lesson->video_url, 8, '...')); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;"><?php _e('No video', 'clarity-aws-ghl'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($lesson->video_type)): ?>
                                            <span class="video-type-badge"><?php echo esc_html($lesson->video_type); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small edit-standalone-lesson" 
                                                data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                                            <?php _e('Edit', 'clarity-aws-ghl'); ?>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete delete-standalone-lesson" 
                                                data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                                            <?php _e('Delete', 'clarity-aws-ghl'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add New Lesson Modal -->
            <div id="add-new-lesson-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Add New Lesson', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="add-new-lesson-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Lesson Title', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" name="lesson_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Video URL', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="url" name="video_url" class="large-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'clarity-aws-ghl'); ?></th>
                                    <td><textarea name="lesson_content" rows="4" class="large-text"></textarea></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button" onclick="jQuery('#add-new-lesson-modal').hide()">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="save-new-lesson-btn">
                            <?php _e('Create Lesson', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Edit Lesson Modal -->
            <div id="edit-standalone-lesson-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Edit Lesson', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="edit-standalone-lesson-form">
                            <input type="hidden" id="edit-standalone-lesson-id" name="lesson_id">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Lesson Title', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="text" name="lesson_title" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Video URL', 'clarity-aws-ghl'); ?></th>
                                    <td><input type="url" name="video_url" class="large-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'clarity-aws-ghl'); ?></th>
                                    <td><textarea name="lesson_content" rows="4" class="large-text"></textarea></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button" onclick="jQuery('#edit-standalone-lesson-modal').hide()">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="update-standalone-lesson-btn">
                            <?php _e('Update Lesson', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render course analytics page
     */
    public function render_analytics_page() {
        $stats = $this->get_course_analytics();
        ?>
        <div class="wrap">
            <h1><?php _e('Course Analytics', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-analytics-dashboard">
                <div class="clarity-stats-grid">
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo esc_html($stats['total_enrollments']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Total Enrollments', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo esc_html($stats['active_students']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Active Students', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number"><?php echo esc_html($stats['completed_courses']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Completed Courses', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="clarity-stat-card">
                        <div class="clarity-stat-number">$<?php echo esc_html(number_format($stats['total_revenue'], 2)); ?></div>
                        <div class="clarity-stat-label"><?php _e('Total Revenue', 'clarity-aws-ghl'); ?></div>
                    </div>
                </div>
                
                <div class="clarity-analytics-charts">
                    <div class="clarity-chart-section">
                        <h3><?php _e('Enrollment by Tier', 'clarity-aws-ghl'); ?></h3>
                        <div id="tier-chart" class="clarity-chart-placeholder">
                            <?php foreach ($stats['tier_breakdown'] as $tier => $count): ?>
                                <div class="tier-stat">
                                    <span class="tier-label"><?php echo sprintf(__('Tier %d', 'clarity-aws-ghl'), $tier); ?></span>
                                    <span class="tier-count"><?php echo esc_html($count); ?> students</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="clarity-chart-section">
                        <h3><?php _e('Course Performance', 'clarity-aws-ghl'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Course', 'clarity-aws-ghl'); ?></th>
                                    <th><?php _e('Enrollments', 'clarity-aws-ghl'); ?></th>
                                    <th><?php _e('Completion Rate', 'clarity-aws-ghl'); ?></th>
                                    <th><?php _e('Avg. Progress', 'clarity-aws-ghl'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['course_performance'] as $course): ?>
                                    <tr>
                                        <td><?php echo esc_html($course['title']); ?></td>
                                        <td><?php echo esc_html($course['enrollments']); ?></td>
                                        <td><?php echo esc_html($course['completion_rate']); ?>%</td>
                                        <td><?php echo esc_html($course['avg_progress']); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Helper methods for data retrieval
     */
    private function get_total_students() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->db_courses->get_table_names()['enrollments']}");
    }
    
    private function get_completion_rate() {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['enrollments']}");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['enrollments']} WHERE progress_percentage >= 100");
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
    
    private function get_course_student_count($course_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->db_courses->get_table_names()['enrollments']} WHERE course_id = %d",
            $course_id
        ));
    }
    
    private function get_all_enrollments() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->db_courses->get_table_names()['enrollments']} ORDER BY enrollment_date DESC");
    }
    
    private function get_course_analytics() {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $stats = array();
        
        // Basic stats
        $stats['total_enrollments'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['enrollments']}");
        $stats['active_students'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$tables['enrollments']} WHERE enrollment_status = 'active'");
        $stats['completed_courses'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['enrollments']} WHERE progress_percentage >= 100");
        $stats['total_revenue'] = (float) $wpdb->get_var("SELECT SUM(payment_amount) FROM {$tables['enrollments']} WHERE payment_status IN ('paid', 'free')");
        
        // Tier breakdown
        $tier_breakdown = $wpdb->get_results("
            SELECT c.course_tier, COUNT(e.id) as count 
            FROM {$tables['courses']} c 
            LEFT JOIN {$tables['enrollments']} e ON c.id = e.course_id 
            GROUP BY c.course_tier
        ");
        
        $stats['tier_breakdown'] = array();
        foreach ($tier_breakdown as $tier) {
            $stats['tier_breakdown'][$tier->course_tier] = $tier->count;
        }
        
        // Course performance
        $course_performance = $wpdb->get_results("
            SELECT 
                c.course_title as title,
                COUNT(e.id) as enrollments,
                ROUND(AVG(CASE WHEN e.progress_percentage >= 100 THEN 100 ELSE 0 END)) as completion_rate,
                ROUND(AVG(e.progress_percentage)) as avg_progress
            FROM {$tables['courses']} c 
            LEFT JOIN {$tables['enrollments']} e ON c.id = e.course_id 
            GROUP BY c.id
        ");
        
        $stats['course_performance'] = $course_performance;
        
        return $stats;
    }
    
    /**
     * AJAX Handlers
     */
    public function ajax_create_course() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $course_data = array(
            'course_title' => sanitize_text_field($_POST['course_title']),
            'course_slug' => sanitize_title($_POST['course_slug']),
            'course_description' => sanitize_textarea_field($_POST['course_description']),
            'course_tier' => intval($_POST['course_tier']),
            'course_status' => sanitize_text_field($_POST['course_status']),
            'course_order' => 999
        );
        
        // Set price based on tier or custom price
        if (!empty($_POST['course_price'])) {
            $course_data['course_price'] = floatval($_POST['course_price']);
        } else {
            $tier_pricing = array(1 => 0, 2 => 497, 3 => 1997);
            $course_data['course_price'] = $tier_pricing[$course_data['course_tier']];
        }
        
        $result = $wpdb->insert($tables['courses'], $course_data);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Course created successfully'));
        } else {
            wp_send_json_error('Failed to create course');
        }
    }
    
    public function ajax_enroll_user() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        $result = $this->course_manager->enroll_user($user_id, $course_id, 'paid');
        
        if ($result) {
            wp_send_json_success(array('message' => 'User enrolled successfully'));
        } else {
            wp_send_json_error('Failed to enroll user');
        }
    }
    
    public function ajax_complete_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        // Get next incomplete lesson
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $next_lesson = $wpdb->get_row($wpdb->prepare("
            SELECT l.* FROM {$tables['lessons']} l
            LEFT JOIN {$tables['user_progress']} p ON l.id = p.lesson_id AND p.user_id = %d
            WHERE l.course_id = %d AND (p.is_completed IS NULL OR p.is_completed = 0)
            ORDER BY l.lesson_order ASC
            LIMIT 1
        ", $user_id, $course_id));
        
        if ($next_lesson) {
            $result = $this->course_manager->mark_lesson_complete($user_id, $next_lesson->id);
            if ($result) {
                wp_send_json_success(array('message' => 'Lesson marked complete'));
            } else {
                wp_send_json_error('Failed to mark lesson complete');
            }
        } else {
            wp_send_json_error('No incomplete lessons found');
        }
    }
    
    public function ajax_reset_user_progress() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        // Reset user progress
        $wpdb->delete($tables['user_progress'], array(
            'user_id' => $user_id,
            'course_id' => $course_id
        ));
        
        // Reset enrollment progress
        $wpdb->update($tables['enrollments'], 
            array('progress_percentage' => 0, 'completion_date' => null),
            array('user_id' => $user_id, 'course_id' => $course_id)
        );
        
        wp_send_json_success(array('message' => 'Progress reset successfully'));
    }
    
    /**
     * AJAX: Get lessons for a course
     */
    public function ajax_get_lessons() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $course_id = intval($_POST['course_id']);
        $lessons = $this->course_manager->get_course_lessons($course_id, true);
        
        wp_send_json_success($lessons);
    }
    
    /**
     * AJAX: Create new lesson
     */
    public function ajax_create_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $lesson_data = array(
            'course_id' => intval($_POST['course_id']),
            'lesson_title' => sanitize_text_field($_POST['lesson_title']),
            'lesson_slug' => sanitize_title($_POST['lesson_title']),
            'lesson_description' => sanitize_textarea_field($_POST['lesson_description']),
            'video_url' => esc_url_raw($_POST['video_url']),
            'video_type' => $this->detect_video_type($_POST['video_url']),
            'lesson_order' => intval($_POST['lesson_order']),
            'duration_minutes' => intval($_POST['duration_minutes']),
            'is_preview' => isset($_POST['is_preview']) ? 1 : 0,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($tables['lessons'], $lesson_data);
        
        if ($result !== false) {
            // Update course total lessons count
            $lesson_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['lessons']} WHERE course_id = %d",
                $lesson_data['course_id']
            ));
            
            $wpdb->update($tables['courses'], 
                array('total_lessons' => $lesson_count),
                array('id' => $lesson_data['course_id'])
            );
            
            wp_send_json_success(array('message' => 'Lesson created successfully', 'lesson_id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Failed to create lesson');
        }
    }
    
    /**
     * AJAX: Edit existing lesson
     */
    public function ajax_edit_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $lesson_id = intval($_POST['lesson_id']);
        $lesson_data = array(
            'lesson_title' => sanitize_text_field($_POST['lesson_title']),
            'lesson_slug' => sanitize_title($_POST['lesson_title']),
            'lesson_description' => sanitize_textarea_field($_POST['lesson_description']),
            'video_url' => esc_url_raw($_POST['video_url']),
            'video_type' => $this->detect_video_type($_POST['video_url']),
            'lesson_order' => intval($_POST['lesson_order']),
            'duration_minutes' => intval($_POST['duration_minutes']),
            'is_preview' => isset($_POST['is_preview']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update($tables['lessons'], $lesson_data, array('id' => $lesson_id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Lesson updated successfully'));
        } else {
            wp_send_json_error('Failed to update lesson');
        }
    }
    
    /**
     * AJAX: Delete lesson
     */
    public function ajax_delete_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $lesson_id = intval($_POST['lesson_id']);
        
        // Get course_id before deleting
        $course_id = $wpdb->get_var($wpdb->prepare(
            "SELECT course_id FROM {$tables['lessons']} WHERE id = %d",
            $lesson_id
        ));
        
        // Delete lesson
        $result = $wpdb->delete($tables['lessons'], array('id' => $lesson_id));
        
        if ($result !== false) {
            // Delete associated progress records
            $wpdb->delete($tables['user_progress'], array('lesson_id' => $lesson_id));
            
            // Update course total lessons count
            $lesson_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['lessons']} WHERE course_id = %d",
                $course_id
            ));
            
            $wpdb->update($tables['courses'], 
                array('total_lessons' => $lesson_count),
                array('id' => $course_id)
            );
            
            wp_send_json_success(array('message' => 'Lesson deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete lesson');
        }
    }
    
    /**
     * AJAX: Reorder lessons
     */
    public function ajax_reorder_lessons() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $lesson_ids = array_map('intval', $_POST['lesson_ids']);
        
        foreach ($lesson_ids as $index => $lesson_id) {
            $wpdb->update($tables['lessons'], 
                array('lesson_order' => $index + 1),
                array('id' => $lesson_id)
            );
        }
        
        wp_send_json_success(array('message' => 'Lessons reordered successfully'));
    }
    
    /**
     * Get course data for editing
     */
    public function ajax_get_course() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $course_id = intval($_POST['course_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['courses']} WHERE id = %d",
            $course_id
        ));
        
        if (!$course) {
            wp_send_json_error('Course not found');
        }
        
        wp_send_json_success($course);
    }
    
    /**
     * Update course
     */
    public function ajax_update_course() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $course_id = intval($_POST['course_id']);
        $course_title = sanitize_text_field($_POST['course_title']);
        $course_description = sanitize_textarea_field($_POST['course_description']);
        $course_price = floatval($_POST['course_price']);
        $course_status = sanitize_text_field($_POST['course_status']);
        
        if (empty($course_title)) {
            wp_send_json_error('Course title is required');
        }
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        $course_data = array(
            'course_title' => $course_title,
            'course_description' => $course_description,
            'course_price' => $course_price,
            'course_status' => $course_status
        );
        
        $result = $wpdb->update(
            $tables['courses'],
            $course_data,
            array('id' => $course_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update course');
        }
        
        wp_send_json_success(array('message' => 'Course updated successfully'));
    }
    
    /**
     * Create standalone lesson (not attached to course)
     */
    public function ajax_create_standalone_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_title = sanitize_text_field($_POST['lesson_title']);
        $lesson_content = sanitize_textarea_field($_POST['lesson_content']);
        $video_url = esc_url_raw($_POST['video_url']);
        
        if (empty($lesson_title)) {
            wp_send_json_error('Lesson title is required');
        }
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        $lesson_data = array(
            'lesson_title' => $lesson_title,
            'lesson_content' => $lesson_content,
            'video_url' => $video_url,
            'video_type' => $this->detect_video_type($video_url),
            'lesson_order' => 0, // Not assigned to course yet
            'course_id' => 0, // Not assigned to course yet
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($tables['lessons'], $lesson_data);
        
        if ($result === false) {
            wp_send_json_error('Failed to create lesson');
        }
        
        wp_send_json_success(array('message' => 'Lesson created successfully'));
    }
    
    /**
     * Edit standalone lesson
     */
    public function ajax_edit_standalone_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $lesson_title = sanitize_text_field($_POST['lesson_title']);
        $lesson_content = sanitize_textarea_field($_POST['lesson_content']);
        $video_url = esc_url_raw($_POST['video_url']);
        
        if (empty($lesson_title)) {
            wp_send_json_error('Lesson title is required');
        }
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        $lesson_data = array(
            'lesson_title' => $lesson_title,
            'lesson_content' => $lesson_content,
            'video_url' => $video_url,
            'video_type' => $this->detect_video_type($video_url)
        );
        
        $result = $wpdb->update(
            $tables['lessons'],
            $lesson_data,
            array('id' => $lesson_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update lesson');
        }
        
        wp_send_json_success(array('message' => 'Lesson updated successfully'));
    }
    
    /**
     * Get standalone lesson data for editing
     */
    public function ajax_get_standalone_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['lessons']} WHERE id = %d",
            $lesson_id
        ));
        
        if (!$lesson) {
            wp_send_json_error('Lesson not found');
        }
        
        wp_send_json_success($lesson);
    }
    
    /**
     * Delete standalone lesson
     */
    public function ajax_delete_standalone_lesson() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Check if lesson is assigned to any course
        $assigned_courses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['lessons']} WHERE id = %d AND course_id > 0",
            $lesson_id
        ));
        
        if ($assigned_courses > 0) {
            wp_send_json_error('Cannot delete lesson that is assigned to a course. Remove from course first.');
        }
        
        $result = $wpdb->delete($tables['lessons'], array('id' => $lesson_id));
        
        if ($result === false) {
            wp_send_json_error('Failed to delete lesson');
        }
        
        wp_send_json_success(array('message' => 'Lesson deleted successfully'));
    }
    
    /**
     * Get available lessons (not assigned to any course)
     */
    public function ajax_get_available_lessons() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Get lessons that are not assigned to any course (course_id = 0 or NULL)
        $lessons = $wpdb->get_results("
            SELECT * FROM {$tables['lessons']} 
            WHERE course_id = 0 OR course_id IS NULL 
            ORDER BY lesson_title
        ");
        
        wp_send_json_success($lessons);
    }
    
    /**
     * Get lessons assigned to a specific course
     */
    public function ajax_get_course_lessons() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $course_id = intval($_POST['course_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Get lessons assigned to this course, ordered by lesson_order
        $lessons = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$tables['lessons']} 
            WHERE course_id = %d 
            ORDER BY lesson_order ASC
        ", $course_id));
        
        wp_send_json_success($lessons);
    }
    
    /**
     * Assign lesson to course
     */
    public function ajax_assign_lesson_to_course() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $course_id = intval($_POST['course_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Check if lesson is already assigned to this course
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['lessons']} WHERE id = %d AND course_id = %d",
            $lesson_id, $course_id
        ));
        
        if ($existing > 0) {
            wp_send_json_error('Lesson is already assigned to this course');
        }
        
        // Get next lesson order for this course
        $next_order = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(lesson_order), 0) + 1 FROM {$tables['lessons']} WHERE course_id = %d",
            $course_id
        ));
        
        // Assign lesson to course
        $result = $wpdb->update(
            $tables['lessons'],
            array(
                'course_id' => $course_id,
                'lesson_order' => $next_order
            ),
            array('id' => $lesson_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to assign lesson to course');
        }
        
        wp_send_json_success(array('message' => 'Lesson assigned successfully'));
    }
    
    /**
     * Remove lesson from course
     */
    public function ajax_remove_lesson_from_course() {
        check_ajax_referer('clarity_courses_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $course_id = intval($_POST['course_id']);
        
        global $wpdb;
        $db_courses = new Clarity_AWS_GHL_Database_Courses();
        $tables = $db_courses->get_table_names();
        
        // Remove lesson from course (set course_id to 0 and lesson_order to 0)
        $result = $wpdb->update(
            $tables['lessons'],
            array(
                'course_id' => 0,
                'lesson_order' => 0
            ),
            array('id' => $lesson_id, 'course_id' => $course_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to remove lesson from course');
        }
        
        // Reorder remaining lessons
        $remaining_lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$tables['lessons']} WHERE course_id = %d ORDER BY lesson_order",
            $course_id
        ));
        
        foreach ($remaining_lessons as $index => $lesson) {
            $wpdb->update(
                $tables['lessons'],
                array('lesson_order' => $index + 1),
                array('id' => $lesson->id)
            );
        }
        
        wp_send_json_success(array('message' => 'Lesson removed successfully'));
    }
    
    /**
     * Detect video type from URL
     */
    private function detect_video_type($url) {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }
        return 'other';
    }
}