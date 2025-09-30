<?php
/**
 * User Administration Class
 *
 * Handles admin interface for student management and testing tools
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_User_Admin {
    
    /**
     * User manager instance
     */
    private $user_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->user_manager = new Clarity_AWS_GHL_User_Manager();
        
        // Initialize hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_clarity_get_students', array($this, 'ajax_get_students'));
        add_action('wp_ajax_clarity_delete_test_user', array($this, 'ajax_delete_test_user'));
        add_action('wp_ajax_clarity_reset_user_progress', array($this, 'ajax_reset_user_progress'));
        add_action('wp_ajax_clarity_reset_demo', array($this, 'ajax_reset_demo'));
        add_action('wp_ajax_clarity_create_test_users', array($this, 'ajax_create_test_users'));
        add_action('wp_ajax_clarity_bulk_enroll_users', array($this, 'ajax_bulk_enroll_users'));
        add_action('wp_ajax_clarity_update_user_access', array($this, 'ajax_update_user_access'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_submenu_page(
            'clarity-aws-ghl',
            'User Management',
            'User Management',
            'manage_options',
            'clarity-user-management',
            array($this, 'render_user_management_page')
        );
        
        add_submenu_page(
            'clarity-aws-ghl',
            'Testing Tools',
            'Testing Tools',
            'manage_options',
            'clarity-testing-tools',
            array($this, 'render_testing_tools_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'clarity-user-management') === false && strpos($hook, 'clarity-testing-tools') === false) {
            return;
        }
        
        wp_enqueue_script('clarity-user-admin', CLARITY_AWS_GHL_PLUGIN_URL . 'admin/js/user-admin.js', array('jquery'), CLARITY_AWS_GHL_VERSION, true);
        wp_enqueue_style('clarity-admin-css', CLARITY_AWS_GHL_PLUGIN_URL . 'admin/css/admin.css', array(), CLARITY_AWS_GHL_VERSION);
        
        wp_localize_script('clarity-user-admin', 'clarityUserAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clarity_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this user?', 'clarity-aws-ghl'),
                'confirm_reset' => __('Are you sure you want to reset this user\'s progress?', 'clarity-aws-ghl'),
                'confirm_demo_reset' => __('Are you sure you want to reset the entire demo environment? This will delete all test users and their progress.', 'clarity-aws-ghl'),
                'processing' => __('Processing...', 'clarity-aws-ghl'),
                'success' => __('Success!', 'clarity-aws-ghl'),
                'error' => __('Error', 'clarity-aws-ghl')
            )
        ));
    }
    
    /**
     * Render user management page
     */
    public function render_user_management_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('User Management', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-admin-section">
                <div class="clarity-user-actions">
                    <button type="button" class="button button-primary" id="refresh-users-btn">
                        <?php _e('Refresh Users', 'clarity-aws-ghl'); ?>
                    </button>
                    <button type="button" class="button" id="bulk-actions-btn">
                        <?php _e('Bulk Actions', 'clarity-aws-ghl'); ?>
                    </button>
                </div>
                
                <div class="clarity-users-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="total-students">-</div>
                        <div class="stat-label"><?php _e('Total Students', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="active-students">-</div>
                        <div class="stat-label"><?php _e('Active This Week', 'clarity-aws-ghl'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="completed-courses">-</div>
                        <div class="stat-label"><?php _e('Completed Courses', 'clarity-aws-ghl'); ?></div>
                    </div>
                </div>
                
                <h2><?php _e('Student Management', 'clarity-aws-ghl'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped" id="students-table">
                    <thead>
                        <tr>
                            <th scope="col" class="check-column"><input type="checkbox" id="select-all-students"></th>
                            <th scope="col"><?php _e('Student Name', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Email', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Access Level', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Enrolled Courses', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Progress', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Registration Date', 'clarity-aws-ghl'); ?></th>
                            <th scope="col"><?php _e('Actions', 'clarity-aws-ghl'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="students-table-body">
                        <tr>
                            <td colspan="8" class="loading-row">
                                <?php _e('Loading students...', 'clarity-aws-ghl'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- User Details Modal -->
            <div id="user-details-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content clarity-modal-large">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Student Details', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body" id="user-details-content">
                        <!-- Content loaded via AJAX -->
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button" id="close-user-modal">
                            <?php _e('Close', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Actions Modal -->
            <div id="bulk-actions-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Bulk Actions', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body">
                        <form id="bulk-actions-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Action', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="bulk_action" id="bulk-action-select">
                                            <option value=""><?php _e('Select Action', 'clarity-aws-ghl'); ?></option>
                                            <option value="enroll"><?php _e('Enroll in Course', 'clarity-aws-ghl'); ?></option>
                                            <option value="update_access"><?php _e('Update Access Level', 'clarity-aws-ghl'); ?></option>
                                            <option value="reset_progress"><?php _e('Reset Progress', 'clarity-aws-ghl'); ?></option>
                                            <option value="delete"><?php _e('Delete Users', 'clarity-aws-ghl'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr id="course-select-row" style="display: none;">
                                    <th scope="row"><?php _e('Course', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="course_id" id="bulk-course-select">
                                            <!-- Populated via AJAX -->
                                        </select>
                                    </td>
                                </tr>
                                <tr id="access-level-row" style="display: none;">
                                    <th scope="row"><?php _e('Access Level', 'clarity-aws-ghl'); ?></th>
                                    <td>
                                        <select name="access_level" id="bulk-access-level">
                                            <option value="1"><?php _e('Free (Tier 1)', 'clarity-aws-ghl'); ?></option>
                                            <option value="2"><?php _e('Core (Tier 2)', 'clarity-aws-ghl'); ?></option>
                                            <option value="3"><?php _e('Premium (Tier 3)', 'clarity-aws-ghl'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button button-primary" id="execute-bulk-action">
                            <?php _e('Execute Action', 'clarity-aws-ghl'); ?>
                        </button>
                        <button type="button" class="button" id="cancel-bulk-action">
                            <?php _e('Cancel', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render testing tools page
     */
    public function render_testing_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Testing Tools', 'clarity-aws-ghl'); ?></h1>
            
            <div class="clarity-admin-section">
                <h2><?php _e('Demo Environment Controls', 'clarity-aws-ghl'); ?></h2>
                <p><?php _e('Use these tools to quickly set up and reset demo environments for testing.', 'clarity-aws-ghl'); ?></p>
                
                <div class="clarity-testing-controls">
                    <h3><?php _e('Quick Setup', 'clarity-aws-ghl'); ?></h3>
                    <div class="testing-actions-grid">
                        <div class="testing-action-card">
                            <div class="action-icon">
                                <i class="dashicons dashicons-groups"></i>
                            </div>
                            <div class="action-content">
                                <h4><?php _e('Create Test Users', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Generate 5 test users with different progress levels (new, 20%, 50%, 80%, completed)', 'clarity-aws-ghl'); ?></p>
                                <button type="button" class="button button-primary" id="create-test-users-btn">
                                    <?php _e('Create Test Users', 'clarity-aws-ghl'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="testing-action-card">
                            <div class="action-icon">
                                <i class="dashicons dashicons-update"></i>
                            </div>
                            <div class="action-content">
                                <h4><?php _e('Reset Demo Environment', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Delete all student users and progress data. Use before creating a fresh demo.', 'clarity-aws-ghl'); ?></p>
                                <button type="button" class="button button-secondary" id="reset-demo-btn">
                                    <?php _e('Reset Demo', 'clarity-aws-ghl'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="testing-action-card">
                            <div class="action-icon">
                                <i class="dashicons dashicons-admin-users"></i>
                            </div>
                            <div class="action-content">
                                <h4><?php _e('User Impersonation', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Quickly switch to any student account to test the user experience.', 'clarity-aws-ghl'); ?></p>
                                <select id="impersonate-user-select" class="regular-text">
                                    <option value=""><?php _e('Select a student...', 'clarity-aws-ghl'); ?></option>
                                </select>
                                <button type="button" class="button" id="impersonate-user-btn">
                                    <?php _e('Impersonate User', 'clarity-aws-ghl'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="clarity-testing-scenarios">
                    <h3><?php _e('Testing Scenarios', 'clarity-aws-ghl'); ?></h3>
                    <div class="scenario-list">
                        <div class="scenario-item">
                            <div class="scenario-number">1</div>
                            <div class="scenario-content">
                                <h4><?php _e('New User Registration', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Test the registration flow from start to dashboard access.', 'clarity-aws-ghl'); ?></p>
                                <div class="scenario-actions">
                                    <a href="/student-registration/" target="_blank" class="button">
                                        <?php _e('Test Registration', 'clarity-aws-ghl'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="scenario-item">
                            <div class="scenario-number">2</div>
                            <div class="scenario-content">
                                <h4><?php _e('Course Progress Tracking', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Impersonate a user with partial progress and test lesson completion.', 'clarity-aws-ghl'); ?></p>
                                <div class="scenario-actions">
                                    <button type="button" class="button" onclick="impersonateUser('intermediate@test.com')">
                                        <?php _e('Test as Intermediate User', 'clarity-aws-ghl'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="scenario-item">
                            <div class="scenario-number">3</div>
                            <div class="scenario-content">
                                <h4><?php _e('Access Level Restrictions', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Test what happens when users try to access higher-tier content.', 'clarity-aws-ghl'); ?></p>
                                <div class="scenario-actions">
                                    <button type="button" class="button" onclick="impersonateUser('new@test.com')">
                                        <?php _e('Test as Free User', 'clarity-aws-ghl'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="scenario-item">
                            <div class="scenario-number">4</div>
                            <div class="scenario-content">
                                <h4><?php _e('Course Completion & Certificates', 'clarity-aws-ghl'); ?></h4>
                                <p><?php _e('Test the complete course flow including certificate download.', 'clarity-aws-ghl'); ?></p>
                                <div class="scenario-actions">
                                    <button type="button" class="button" onclick="impersonateUser('graduate@test.com')">
                                        <?php _e('Test as Graduate', 'clarity-aws-ghl'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Test Results Modal -->
            <div id="test-results-modal" class="clarity-modal" style="display: none;">
                <div class="clarity-modal-content">
                    <div class="clarity-modal-header">
                        <h3><?php _e('Test Results', 'clarity-aws-ghl'); ?></h3>
                        <span class="clarity-modal-close">&times;</span>
                    </div>
                    <div class="clarity-modal-body" id="test-results-content">
                        <!-- Content loaded via AJAX -->
                    </div>
                    <div class="clarity-modal-footer">
                        <button type="button" class="button" id="close-test-results">
                            <?php _e('Close', 'clarity-aws-ghl'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get students data
     */
    public function ajax_get_students() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $students_data = $this->user_manager->get_all_students();
        
        $formatted_students = array();
        $total_completed = 0;
        
        foreach ($students_data as $student_info) {
            $student = $student_info['user'];
            $progress = $student_info['progress'];
            
            // Calculate average progress
            $avg_progress = 0;
            $completed_courses = 0;
            if (!empty($progress)) {
                $total_progress = 0;
                foreach ($progress as $course_progress) {
                    $total_progress += $course_progress['percentage'];
                    if ($course_progress['percentage'] >= 100) {
                        $completed_courses++;
                        $total_completed++;
                    }
                }
                $avg_progress = round($total_progress / count($progress));
            }
            
            $formatted_students[] = array(
                'id' => $student->ID,
                'name' => $student->first_name . ' ' . $student->last_name,
                'email' => $student->user_email,
                'access_level' => $student_info['access_level'],
                'enrolled_courses' => count($student_info['enrolled_courses']),
                'avg_progress' => $avg_progress,
                'completed_courses' => $completed_courses,
                'registration_date' => $student_info['registration_date'] ? date('M j, Y', strtotime($student_info['registration_date'])) : 'N/A'
            );
        }
        
        wp_send_json_success(array(
            'students' => $formatted_students,
            'stats' => array(
                'total_students' => count($students_data),
                'total_completed' => $total_completed
            )
        ));
    }
    
    /**
     * AJAX: Delete test user
     */
    public function ajax_delete_test_user() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        
        // Call user manager method
        $this->user_manager->ajax_delete_test_user();
    }
    
    /**
     * AJAX: Reset user progress
     */
    public function ajax_reset_user_progress() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Call user manager method
        $this->user_manager->ajax_reset_user_progress();
    }
    
    /**
     * AJAX: Reset demo environment
     */
    public function ajax_reset_demo() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Call user manager method
        $this->user_manager->ajax_reset_demo();
    }
    
    /**
     * AJAX: Create test users
     */
    public function ajax_create_test_users() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Call user manager method
        $this->user_manager->ajax_create_test_users();
    }
    
    /**
     * AJAX: Bulk enroll users
     */
    public function ajax_bulk_enroll_users() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_ids = array_map('intval', $_POST['user_ids']);
        $course_id = intval($_POST['course_id']);
        
        if (empty($user_ids) || !$course_id) {
            wp_send_json_error('Invalid parameters');
        }
        
        $enrolled_count = 0;
        foreach ($user_ids as $user_id) {
            if ($this->user_manager->enroll_user_in_course($user_id, $course_id)) {
                $enrolled_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Successfully enrolled %d users in the course.', $enrolled_count),
            'enrolled_count' => $enrolled_count
        ));
    }
    
    /**
     * AJAX: Update user access level
     */
    public function ajax_update_user_access() {
        check_ajax_referer('clarity_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_ids = array_map('intval', $_POST['user_ids']);
        $access_level = intval($_POST['access_level']);
        
        if (empty($user_ids) || !in_array($access_level, array(1, 2, 3))) {
            wp_send_json_error('Invalid parameters');
        }
        
        $updated_count = 0;
        foreach ($user_ids as $user_id) {
            if ($this->user_manager->set_user_access_level($user_id, $access_level)) {
                $updated_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Successfully updated access level for %d users.', $updated_count),
            'updated_count' => $updated_count
        ));
    }
}