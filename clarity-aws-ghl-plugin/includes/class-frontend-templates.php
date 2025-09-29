<?php
/**
 * Frontend Course Display Templates
 *
 * Handles frontend course listing and display functionality
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Frontend_Templates {
    
    /**
     * Course manager instance
     */
    private $course_manager;
    private $lesson_handler;
    private $progress_tracker;
    private $db_courses;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        $this->course_manager = new Clarity_AWS_GHL_Course_Manager();
        $this->lesson_handler = new Clarity_AWS_GHL_Lesson_Handler();
        $this->progress_tracker = new Clarity_AWS_GHL_Progress_Tracker();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Shortcodes for course display
        add_shortcode('clarity_course_catalog', array($this, 'render_course_catalog'));
        add_shortcode('clarity_course_card', array($this, 'render_course_card'));
        add_shortcode('clarity_course_detail', array($this, 'render_course_detail'));
        add_shortcode('clarity_course_enrollment', array($this, 'render_course_enrollment'));
        
        // AJAX handlers for frontend actions
        add_action('wp_ajax_clarity_enroll_course', array($this, 'ajax_enroll_course'));
        add_action('wp_ajax_nopriv_clarity_enroll_course', array($this, 'ajax_enroll_course_guest'));
        
        // Template filters
        add_filter('the_content', array($this, 'filter_course_content'));
        
        // Rewrite rules for course URLs
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_course_templates'));
    }
    
    /**
     * Render course catalog shortcode
     */
    public function render_course_catalog($atts) {
        $atts = shortcode_atts(array(
            'tier' => '',
            'status' => 'published',
            'limit' => -1,
            'show_pricing' => 'true',
            'show_enrollment' => 'true',
            'layout' => 'grid'
        ), $atts);
        
        $args = array(
            'status' => $atts['status']
        );
        
        $courses = $this->course_manager->get_all_courses($args);
        
        // Filter by tier if specified
        if (!empty($atts['tier'])) {
            $tier = intval($atts['tier']);
            $courses = array_filter($courses, function($course) use ($tier) {
                return $course->course_tier == $tier;
            });
        }
        
        // Limit results
        if ($atts['limit'] > 0) {
            $courses = array_slice($courses, 0, $atts['limit']);
        }
        
        ob_start();
        ?>
        <div class="clarity-course-catalog layout-<?php echo esc_attr($atts['layout']); ?>">
            <?php if (empty($courses)): ?>
                <div class="no-courses">
                    <h3><?php _e('No courses available', 'clarity-aws-ghl'); ?></h3>
                    <p><?php _e('Check back soon for new courses!', 'clarity-aws-ghl'); ?></p>
                </div>
            <?php else: ?>
                <div class="course-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card" data-course-id="<?php echo esc_attr($course->id); ?>">
                            <?php echo $this->render_single_course_card($course, $atts); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render single course card shortcode
     */
    public function render_course_card($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'show_pricing' => 'true',
            'show_enrollment' => 'true',
            'show_progress' => 'auto'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>' . __('Course ID is required.', 'clarity-aws-ghl') . '</p>';
        }
        
        $course = $this->course_manager->get_course($atts['course_id']);
        
        if (!$course) {
            return '<p>' . __('Course not found.', 'clarity-aws-ghl') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="clarity-course-card" data-course-id="<?php echo esc_attr($course->id); ?>">
            <?php echo $this->render_single_course_card($course, $atts); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render course detail shortcode
     */
    public function render_course_detail($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'show_lessons' => 'true',
            'show_enrollment' => 'true',
            'show_requirements' => 'true'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>' . __('Course ID is required.', 'clarity-aws-ghl') . '</p>';
        }
        
        $course = $this->course_manager->get_course($atts['course_id']);
        
        if (!$course) {
            return '<p>' . __('Course not found.', 'clarity-aws-ghl') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $is_enrolled = false;
        $progress_data = null;
        
        if ($user_id) {
            $progress_data = $this->course_manager->get_user_course_progress($user_id, $course->id);
            $is_enrolled = !is_null($progress_data);
        }
        
        $lessons = $this->course_manager->get_course_lessons($course->id, false);
        
        ob_start();
        ?>
        <div class="clarity-course-detail" data-course-id="<?php echo esc_attr($course->id); ?>">
            <div class="course-header">
                <div class="course-info">
                    <div class="course-tier">
                        <span class="tier-badge tier-<?php echo esc_attr($course->course_tier); ?>">
                            <?php printf(__('Tier %d', 'clarity-aws-ghl'), $course->course_tier); ?>
                        </span>
                    </div>
                    <h1 class="course-title"><?php echo esc_html($course->course_title); ?></h1>
                    <div class="course-meta">
                        <span class="lesson-count">
                            <i class="dashicons dashicons-video-alt3"></i>
                            <?php printf(_n('%d lesson', '%d lessons', $course->total_lessons, 'clarity-aws-ghl'), $course->total_lessons); ?>
                        </span>
                        <?php if ($course->course_price > 0): ?>
                            <span class="course-price">
                                <i class="dashicons dashicons-money-alt"></i>
                                $<?php echo esc_html(number_format($course->course_price, 2)); ?>
                            </span>
                        <?php else: ?>
                            <span class="course-price free">
                                <i class="dashicons dashicons-heart"></i>
                                <?php _e('Free', 'clarity-aws-ghl'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($is_enrolled && $progress_data): ?>
                    <div class="enrollment-status enrolled">
                        <div class="progress-summary">
                            <div class="progress-circle">
                                <svg viewBox="0 0 36 36" class="circular-chart">
                                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <path class="circle" stroke-dasharray="<?php echo esc_attr($progress_data['progress_percentage']); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <text x="18" y="20.35" class="percentage"><?php echo esc_html($progress_data['progress_percentage']); ?>%</text>
                                </svg>
                            </div>
                            <div class="progress-text">
                                <h4><?php _e('Your Progress', 'clarity-aws-ghl'); ?></h4>
                                <p><?php printf(__('%d%% Complete', 'clarity-aws-ghl'), $progress_data['progress_percentage']); ?></p>
                                <a href="#course-lessons" class="button button-primary">
                                    <?php echo $progress_data['progress_percentage'] > 0 ? __('Continue Learning', 'clarity-aws-ghl') : __('Start Course', 'clarity-aws-ghl'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php elseif ($atts['show_enrollment'] === 'true'): ?>
                    <div class="enrollment-status not-enrolled">
                        <?php echo $this->render_enrollment_form($course); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="course-content">
                <?php if (!empty($course->course_description)): ?>
                    <div class="course-description">
                        <h3><?php _e('Course Description', 'clarity-aws-ghl'); ?></h3>
                        <div class="description-content">
                            <?php echo wp_kses_post(wpautop($course->course_description)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_requirements'] === 'true' && !empty($course->access_requirements)): ?>
                    <div class="course-requirements">
                        <h3><?php _e('Requirements', 'clarity-aws-ghl'); ?></h3>
                        <?php echo $this->render_course_requirements($course); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_lessons'] === 'true'): ?>
                    <div class="course-lessons" id="course-lessons">
                        <h3><?php _e('Course Content', 'clarity-aws-ghl'); ?></h3>
                        <div class="lessons-list">
                            <?php foreach ($lessons as $index => $lesson): ?>
                                <div class="lesson-item <?php echo $is_enrolled ? 'enrolled' : 'locked'; ?>" data-lesson-id="<?php echo esc_attr($lesson->id); ?>">
                                    <div class="lesson-number">
                                        <?php if ($is_enrolled): ?>
                                            <?php
                                            $lesson_progress = null;
                                            if ($progress_data) {
                                                foreach ($progress_data['lessons'] as $lp) {
                                                    if ($lp['lesson']->id == $lesson->id) {
                                                        $lesson_progress = $lp;
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php if ($lesson_progress && $lesson_progress['completed']): ?>
                                                <i class="dashicons dashicons-yes-alt completed"></i>
                                            <?php elseif ($lesson_progress && $lesson_progress['can_access']): ?>
                                                <span class="number"><?php echo $index + 1; ?></span>
                                            <?php else: ?>
                                                <i class="dashicons dashicons-lock locked"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <i class="dashicons dashicons-lock locked"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lesson-content">
                                        <h4 class="lesson-title"><?php echo esc_html($lesson->lesson_title); ?></h4>
                                        <?php if (!empty($lesson->lesson_description)): ?>
                                            <p class="lesson-description"><?php echo esc_html($lesson->lesson_description); ?></p>
                                        <?php endif; ?>
                                        <div class="lesson-meta">
                                            <?php if ($lesson->duration_minutes): ?>
                                                <span class="duration">
                                                    <i class="dashicons dashicons-clock"></i>
                                                    <?php echo esc_html($lesson->duration_minutes); ?> min
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($lesson->is_preview): ?>
                                                <span class="preview-badge"><?php _e('Preview', 'clarity-aws-ghl'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="lesson-actions">
                                        <?php if ($is_enrolled && $lesson_progress && $lesson_progress['can_access']): ?>
                                            <a href="<?php echo home_url('/lesson/' . $lesson->lesson_slug); ?>" class="button">
                                                <?php echo $lesson_progress['completed'] ? __('Review', 'clarity-aws-ghl') : __('Start', 'clarity-aws-ghl'); ?>
                                            </a>
                                        <?php elseif ($lesson->is_preview): ?>
                                            <a href="<?php echo home_url('/lesson/' . $lesson->lesson_slug); ?>" class="button preview">
                                                <?php _e('Preview', 'clarity-aws-ghl'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="locked-indicator"><?php _e('Locked', 'clarity-aws-ghl'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render course enrollment form shortcode
     */
    public function render_course_enrollment($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'redirect_url' => '',
            'button_text' => ''
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>' . __('Course ID is required.', 'clarity-aws-ghl') . '</p>';
        }
        
        $course = $this->course_manager->get_course($atts['course_id']);
        
        if (!$course) {
            return '<p>' . __('Course not found.', 'clarity-aws-ghl') . '</p>';
        }
        
        return $this->render_enrollment_form($course, $atts);
    }
    
    /**
     * Render single course card
     */
    private function render_single_course_card($course, $atts = array()) {
        $user_id = get_current_user_id();
        $is_enrolled = false;
        $progress = 0;
        
        if ($user_id) {
            $progress_data = $this->course_manager->get_user_course_progress($user_id, $course->id);
            if ($progress_data) {
                $is_enrolled = true;
                $progress = $progress_data['progress_percentage'];
            }
        }
        
        ob_start();
        ?>
        <div class="course-card-inner">
            <?php if (!empty($course->featured_image)): ?>
                <div class="course-image">
                    <img src="<?php echo esc_url($course->featured_image); ?>" alt="<?php echo esc_attr($course->course_title); ?>">
                    <div class="course-tier-overlay">
                        <span class="tier-badge tier-<?php echo esc_attr($course->course_tier); ?>">
                            <?php printf(__('Tier %d', 'clarity-aws-ghl'), $course->course_tier); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="course-content">
                <h3 class="course-title">
                    <a href="<?php echo home_url('/course/' . $course->course_slug); ?>">
                        <?php echo esc_html($course->course_title); ?>
                    </a>
                </h3>
                
                <?php if (!empty($course->course_description)): ?>
                    <p class="course-excerpt">
                        <?php echo esc_html(wp_trim_words($course->course_description, 20)); ?>
                    </p>
                <?php endif; ?>
                
                <div class="course-meta">
                    <span class="lesson-count">
                        <i class="dashicons dashicons-video-alt3"></i>
                        <?php printf(_n('%d lesson', '%d lessons', $course->total_lessons, 'clarity-aws-ghl'), $course->total_lessons); ?>
                    </span>
                    
                    <?php if ($atts['show_pricing'] === 'true'): ?>
                        <?php if ($course->course_price > 0): ?>
                            <span class="course-price">
                                <i class="dashicons dashicons-money-alt"></i>
                                $<?php echo esc_html(number_format($course->course_price, 2)); ?>
                            </span>
                        <?php else: ?>
                            <span class="course-price free">
                                <i class="dashicons dashicons-heart"></i>
                                <?php _e('Free', 'clarity-aws-ghl'); ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_enrolled && ($atts['show_progress'] === 'true' || $atts['show_progress'] === 'auto')): ?>
                    <div class="course-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                        </div>
                        <span class="progress-text"><?php echo esc_html($progress); ?>% Complete</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="course-actions">
                <?php if ($is_enrolled): ?>
                    <a href="<?php echo home_url('/course/' . $course->course_slug); ?>" class="button button-primary enrolled">
                        <?php echo $progress > 0 ? __('Continue', 'clarity-aws-ghl') : __('Start Course', 'clarity-aws-ghl'); ?>
                    </a>
                <?php elseif ($atts['show_enrollment'] === 'true'): ?>
                    <?php if ($course->course_price > 0): ?>
                        <button class="button button-primary enroll-button" data-course-id="<?php echo esc_attr($course->id); ?>">
                            <?php printf(__('Enroll - $%s', 'clarity-aws-ghl'), number_format($course->course_price, 2)); ?>
                        </button>
                    <?php else: ?>
                        <button class="button button-primary enroll-button" data-course-id="<?php echo esc_attr($course->id); ?>">
                            <?php _e('Enroll Free', 'clarity-aws-ghl'); ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo home_url('/course/' . $course->course_slug); ?>" class="button">
                        <?php _e('Learn More', 'clarity-aws-ghl'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render enrollment form
     */
    private function render_enrollment_form($course, $atts = array()) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            ob_start();
            ?>
            <div class="enrollment-form guest">
                <h4><?php _e('Ready to start learning?', 'clarity-aws-ghl'); ?></h4>
                <p><?php _e('Please log in or create an account to enroll in this course.', 'clarity-aws-ghl'); ?></p>
                <div class="auth-buttons">
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                        <?php _e('Log In', 'clarity-aws-ghl'); ?>
                    </a>
                    <a href="<?php echo wp_registration_url(); ?>" class="button">
                        <?php _e('Sign Up', 'clarity-aws-ghl'); ?>
                    </a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        $button_text = !empty($atts['button_text']) ? $atts['button_text'] : '';
        if (empty($button_text)) {
            $button_text = $course->course_price > 0 ? 
                sprintf(__('Enroll Now - $%s', 'clarity-aws-ghl'), number_format($course->course_price, 2)) :
                __('Enroll Free', 'clarity-aws-ghl');
        }
        
        ob_start();
        ?>
        <div class="enrollment-form logged-in">
            <h4><?php _e('Ready to start learning?', 'clarity-aws-ghl'); ?></h4>
            <?php if ($course->course_price > 0): ?>
                <div class="pricing-info">
                    <span class="price">$<?php echo esc_html(number_format($course->course_price, 2)); ?></span>
                    <span class="billing-info"><?php _e('One-time payment', 'clarity-aws-ghl'); ?></span>
                </div>
            <?php endif; ?>
            
            <form class="enrollment-form-fields" data-course-id="<?php echo esc_attr($course->id); ?>">
                <?php wp_nonce_field('clarity_enroll_course', 'enrollment_nonce'); ?>
                <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id); ?>">
                <?php if (!empty($atts['redirect_url'])): ?>
                    <input type="hidden" name="redirect_url" value="<?php echo esc_url($atts['redirect_url']); ?>">
                <?php endif; ?>
                
                <button type="submit" class="button button-primary button-large enroll-submit">
                    <?php echo esc_html($button_text); ?>
                </button>
            </form>
            
            <div class="enrollment-benefits">
                <ul>
                    <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Lifetime access', 'clarity-aws-ghl'); ?></li>
                    <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Expert instruction', 'clarity-aws-ghl'); ?></li>
                    <?php if ($course->completion_certificate): ?>
                        <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Certificate of completion', 'clarity-aws-ghl'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render course requirements
     */
    private function render_course_requirements($course) {
        $requirements = json_decode($course->access_requirements, true);
        
        if (empty($requirements)) {
            return '<p>' . __('No special requirements.', 'clarity-aws-ghl') . '</p>';
        }
        
        ob_start();
        ?>
        <ul class="requirements-list">
            <?php if (isset($requirements['registration']) && $requirements['registration']): ?>
                <li><i class="dashicons dashicons-admin-users"></i> <?php _e('Account registration required', 'clarity-aws-ghl'); ?></li>
            <?php endif; ?>
            
            <?php if (isset($requirements['prerequisite'])): ?>
                <li><i class="dashicons dashicons-welcome-learn-more"></i> <?php _e('Complete previous tier course', 'clarity-aws-ghl'); ?></li>
            <?php endif; ?>
            
            <?php if (isset($requirements['payment']) && $requirements['payment']): ?>
                <li><i class="dashicons dashicons-money-alt"></i> <?php _e('Payment required', 'clarity-aws-ghl'); ?></li>
            <?php endif; ?>
        </ul>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add rewrite rules for course URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^course/([^/]+)/?$',
            'index.php?clarity_course=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^lesson/([^/]+)/?$',
            'index.php?clarity_lesson=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'clarity_course';
        $vars[] = 'clarity_lesson';
        return $vars;
    }
    
    /**
     * Handle course template routing
     */
    public function handle_course_templates() {
        $course_slug = get_query_var('clarity_course');
        $lesson_slug = get_query_var('clarity_lesson');
        
        if ($course_slug) {
            $this->load_course_template($course_slug);
        } elseif ($lesson_slug) {
            $this->load_lesson_template($lesson_slug);
        }
    }
    
    /**
     * Load course template
     */
    private function load_course_template($course_slug) {
        $course = $this->course_manager->get_course($course_slug);
        
        if (!$course) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Set up template data
        global $clarity_course;
        $clarity_course = $course;
        
        // Load template
        include(CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/single-course.php');
        exit;
    }
    
    /**
     * Load lesson template
     */
    private function load_lesson_template($lesson_slug) {
        global $wpdb;
        $tables = $this->db_courses->get_table_names();
        
        $lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['lessons']} WHERE lesson_slug = %s",
            $lesson_slug
        ));
        
        if (!$lesson) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Check access
        $user_id = get_current_user_id();
        if ($user_id && !$lesson->is_preview && !$this->course_manager->can_access_lesson($user_id, $lesson->id)) {
            wp_redirect(home_url('/course/' . $lesson->course_id));
            exit;
        }
        
        // Set up template data
        global $clarity_lesson;
        $clarity_lesson = $lesson;
        
        // Load template
        include(CLARITY_AWS_GHL_PLUGIN_DIR . 'templates/single-lesson.php');
        exit;
    }
    
    /**
     * Filter course content
     */
    public function filter_course_content($content) {
        // Add course-specific content filtering if needed
        return $content;
    }
    
    /**
     * AJAX: Enroll in course
     */
    public function ajax_enroll_course() {
        check_ajax_referer('clarity_enroll_course', 'enrollment_nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(__('Please log in to enroll', 'clarity-aws-ghl'));
        }
        
        $course_id = intval($_POST['course_id']);
        $course = $this->course_manager->get_course($course_id);
        
        if (!$course) {
            wp_send_json_error(__('Course not found', 'clarity-aws-ghl'));
        }
        
        // For paid courses, this would integrate with a payment system
        // For now, we'll just enroll them directly
        $payment_status = $course->course_price > 0 ? 'pending' : 'free';
        
        $enrollment_id = $this->course_manager->enroll_user($user_id, $course_id, $payment_status);
        
        if ($enrollment_id) {
            $redirect_url = !empty($_POST['redirect_url']) ? 
                esc_url($_POST['redirect_url']) : 
                home_url('/course/' . $course->course_slug);
                
            wp_send_json_success(array(
                'message' => __('Successfully enrolled!', 'clarity-aws-ghl'),
                'redirect_url' => $redirect_url
            ));
        } else {
            wp_send_json_error(__('Enrollment failed', 'clarity-aws-ghl'));
        }
    }
    
    /**
     * AJAX: Handle guest enrollment (redirect to login)
     */
    public function ajax_enroll_course_guest() {
        wp_send_json_error(array(
            'message' => __('Please log in to enroll', 'clarity-aws-ghl'),
            'login_url' => wp_login_url(get_referer())
        ));
    }
}