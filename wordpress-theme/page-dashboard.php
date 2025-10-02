<?php
/**
 * Template Name: Student Dashboard
 * 
 * Dashboard page showing all courses with enrollment status and access controls
 */

if (!defined('ABSPATH')) {
    exit;
}

// Require authentication
$current_user_id = get_current_user_id();
if (!$current_user_id) {
    wp_redirect(home_url('/login'));
    exit;
}

// Get current user data
$current_user = wp_get_current_user();

// Database queries
global $wpdb;
$courses_table = $wpdb->prefix . 'clarity_courses';
$enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';

// Get all courses ordered by tier
$courses = $wpdb->get_results("
    SELECT * FROM {$courses_table} 
    WHERE course_status = 'published' 
    ORDER BY course_tier ASC
");

// Get user's enrollments
$enrollments = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$enrollments_table} 
    WHERE user_id = %d
", $current_user_id));

// Create enrollment lookup array
$user_enrollments = [];
foreach ($enrollments as $enrollment) {
    $user_enrollments[$enrollment->course_id] = $enrollment;
}

// Helper function to check if prerequisites are met
function check_prerequisites_met($course_tier, $user_enrollments, $courses) {
    if ($course_tier <= 1) {
        return true; // Tier 1 has no prerequisites
    }
    
    // Find previous tier course
    $prev_tier = $course_tier - 1;
    foreach ($courses as $prev_course) {
        if ($prev_course->course_tier == $prev_tier) {
            // Check if previous tier is completed
            if (isset($user_enrollments[$prev_course->id])) {
                $enrollment = $user_enrollments[$prev_course->id];
                return !empty($enrollment->completion_date);
            }
            return false;
        }
    }
    return false;
}

// Helper function to get previous course name
function get_previous_course_name($course_tier, $courses) {
    if ($course_tier <= 1) {
        return '';
    }
    
    $prev_tier = $course_tier - 1;
    foreach ($courses as $course) {
        if ($course->course_tier == $prev_tier) {
            return $course->course_title;
        }
    }
    return 'previous course';
}

// Helper function to get next course
function get_next_course($current_tier, $courses) {
    $next_tier = $current_tier + 1;
    foreach ($courses as $course) {
        if ($course->course_tier == $next_tier) {
            return $course;
        }
    }
    return null;
}

get_header();
?>

<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>My Learning Dashboard</h1>
            <p>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</p>
        </div>

        <!-- Course Grid -->
        <div class="row g-4">
            <?php foreach ($courses as $course): 
                $is_enrolled = isset($user_enrollments[$course->id]);
                $enrollment = $is_enrolled ? $user_enrollments[$course->id] : null;
                $prerequisites_met = check_prerequisites_met($course->course_tier, $user_enrollments, $courses);
                $is_completed = $enrollment && !empty($enrollment->completion_date);
                $progress = $enrollment ? $enrollment->progress_percentage : 0;
                $course_url = home_url('/course/' . $course->course_slug);
                
                // Determine course state
                $state = '';
                if ($is_enrolled && $is_completed) {
                    $state = 'completed';
                } elseif ($is_enrolled && !$is_completed) {
                    $state = 'in-progress';
                } elseif (!$prerequisites_met) {
                    $state = 'locked';
                } else {
                    $state = 'available';
                }
            ?>
            
            <div class="col-lg-4 col-md-6">
                <div class="course-card <?php echo $state; ?>" 
                     <?php if ($state === 'available'): ?>
                         data-funnel-url="<?php echo home_url('/funnel/' . $course->course_slug); ?>" 
                         style="cursor: pointer;" 
                         onclick="window.location.href='<?php echo home_url('/funnel/' . $course->course_slug); ?>'"
                     <?php endif; ?>>
                    <!-- Course Image -->
                    <div class="course-image-wrapper">
                        <?php 
                        $featured_image = $course->featured_image;
                        if ($featured_image): 
                            if (strpos($featured_image, 'data:image/') === 0): ?>
                                <img src="<?php echo $featured_image; ?>" alt="<?php echo esc_attr($course->course_title); ?>" class="course-image">
                            <?php else: ?>
                                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($course->course_title); ?>" class="course-image">
                            <?php endif;
                        else: ?>
                            <div class="course-image-placeholder">
                                <i class="bi bi-book-half"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Overlay for locked/completed states -->
                        <?php if ($state === 'locked'): ?>
                            <div class="course-overlay locked-overlay">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                        <?php elseif ($state === 'completed'): ?>
                            <div class="course-overlay completed-overlay">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status Badge -->
                        <span class="status-badge badge-<?php echo $state; ?>">
                            <?php 
                            switch($state) {
                                case 'completed':
                                    echo '<i class="bi bi-check-circle"></i> Completed';
                                    break;
                                case 'in-progress':
                                    echo '<i class="bi bi-play-circle"></i> Enrolled';
                                    break;
                                case 'locked':
                                    echo '<i class="bi bi-lock"></i> Locked';
                                    break;
                                case 'available':
                                    echo '<i class="bi bi-unlock"></i> Available';
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <!-- Course Content -->
                    <div class="course-content">
                        <h3 class="course-title"><?php echo esc_html($course->course_title); ?></h3>
                        <p class="course-description"><?php echo esc_html($course->course_description); ?></p>
                        
                        <!-- Tier Badge -->
                        <div class="course-tier">
                            <span class="tier-label">Tier <?php echo $course->course_tier; ?></span>
                            <?php if ($course->course_price > 0): ?>
                                <span class="course-price">$<?php echo number_format($course->course_price, 0); ?></span>
                            <?php else: ?>
                                <span class="course-price free">Free</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Progress Bar (if enrolled) -->
                        <?php if ($is_enrolled && !$is_completed): ?>
                            <div class="progress-wrapper">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $progress; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Completion Date (if completed) -->
                        <?php if ($is_completed): ?>
                            <p class="completion-date">
                                <i class="bi bi-calendar-check"></i>
                                Completed on <?php echo date('M j, Y', strtotime($enrollment->completion_date)); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Lock Message (if locked) -->
                        <?php if ($state === 'locked'): ?>
                            <p class="lock-message">
                                <i class="bi bi-info-circle"></i>
                                Complete "<?php echo esc_html(get_previous_course_name($course->course_tier, $courses)); ?>" first
                            </p>
                        <?php endif; ?>
                        
                        <!-- Action Button -->
                        <div class="course-action">
                            <?php if ($state === 'completed'): ?>
                                <a href="<?php echo esc_url($course_url); ?>" class="btn btn-outline-success w-100">
                                    <i class="bi bi-arrow-repeat"></i> Review Course
                                </a>
                                <?php 
                                $next_course = get_next_course($course->course_tier, $courses);
                                if ($next_course && !isset($user_enrollments[$next_course->id])): 
                                ?>
                                    <a href="<?php echo home_url('/funnel/' . $next_course->course_slug); ?>" 
                                       class="btn btn-primary w-100 mt-2">
                                        <i class="bi bi-arrow-right-circle"></i> 
                                        Enroll in <?php echo esc_html($next_course->course_title); ?>
                                    </a>
                                <?php endif; ?>
                                
                            <?php elseif ($state === 'in-progress'): ?>
                                <a href="<?php echo esc_url($course_url); ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-play-fill"></i> Continue Learning
                                </a>
                                
                            <?php elseif ($state === 'locked'): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="bi bi-lock"></i> Locked
                                </button>
                                
                            <?php else: // available ?>
                                <a href="<?php echo home_url('/funnel/' . $course->course_slug); ?>" class="btn btn-primary w-100">
                                    <?php if ($course->course_tier == 1): ?>
                                        <i class="bi bi-play-circle"></i> Start Free Course
                                    <?php else: ?>
                                        <i class="bi bi-cart-plus"></i> Enroll Now - $<?php echo number_format($course->course_price, 0); ?>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>

        <!-- Empty State -->
        <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <h3>No Courses Available</h3>
            <p>Check back soon for new learning opportunities!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Dashboard Container */
.dashboard-container {
    min-height: calc(100vh - 100px);
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 40px 0;
}

/* Dashboard Header */
.dashboard-header {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeInDown 0.6s ease-out;
}

.dashboard-header h1 {
    font-size: 36px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}

.dashboard-header p {
    font-size: 18px;
    color: #666;
}

/* Course Card */
.course-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    animation: fadeInUp 0.6s ease-out;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

/* Course Image */
.course-image-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.course-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.course-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: white;
}

/* Course Overlay */
.course-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: white;
}

.locked-overlay {
    background: rgba(0, 0, 0, 0.7);
}

.completed-overlay {
    background: rgba(40, 167, 69, 0.7);
}

/* Status Badge */
.status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 4px;
}

.badge-completed {
    background: #28a745;
}

.badge-in-progress {
    background: #28a745;
}

.badge-locked {
    background: #6c757d;
}

.badge-available {
    background: #007bff;
}

/* Course Content */
.course-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.course-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.course-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Course Tier */
.course-tier {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.tier-label {
    background: #f8f9fa;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    color: #495057;
}

.course-price {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
}

.course-price.free {
    color: #28a745;
}

/* Progress Bar */
.progress-wrapper {
    margin: 15px 0;
}

.progress {
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
}

.progress-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    font-size: 10px;
    line-height: 8px;
    color: transparent;
    transition: width 0.6s ease;
}

/* Completion Date */
.completion-date {
    font-size: 13px;
    color: #28a745;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Lock Message */
.lock-message {
    font-size: 13px;
    color: #dc3545;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Course Action */
.course-action {
    margin-top: auto;
}

.course-action .btn {
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.course-action .btn i {
    margin-right: 4px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

/* Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header h1 {
        font-size: 28px;
    }
    
    .course-card {
        margin-bottom: 20px;
    }
    
    .course-image-wrapper {
        height: 150px;
    }
}
</style>

<?php get_footer(); ?>