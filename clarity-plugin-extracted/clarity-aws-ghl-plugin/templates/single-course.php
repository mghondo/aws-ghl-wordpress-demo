<?php
/**
 * Single Course Template
 *
 * Template for displaying individual course pages
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $clarity_course;

if (!$clarity_course) {
    get_template_part('404');
    get_footer();
    return;
}

// Get course data
$course_manager = new Clarity_AWS_GHL_Course_Manager();
$user_id = get_current_user_id();
$is_enrolled = false;
$progress_data = null;

if ($user_id) {
    $progress_data = $course_manager->get_user_course_progress($user_id, $clarity_course->id);
    $is_enrolled = !is_null($progress_data);
}

$lessons = $course_manager->get_course_lessons($clarity_course->id, false);
?>

<div class="clarity-course-template">
    <div class="container">
        <div class="course-header-section">
            <div class="breadcrumbs">
                <a href="<?php echo home_url('/courses'); ?>"><?php _e('Courses', 'clarity-aws-ghl'); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo esc_html($clarity_course->course_title); ?></span>
            </div>
            
            <div class="course-hero">
                <div class="course-info">
                    <div class="course-tier">
                        <span class="tier-badge tier-<?php echo esc_attr($clarity_course->course_tier); ?>">
                            <?php printf(__('Tier %d', 'clarity-aws-ghl'), $clarity_course->course_tier); ?>
                        </span>
                    </div>
                    
                    <h1 class="course-title"><?php echo esc_html($clarity_course->course_title); ?></h1>
                    
                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="dashicons dashicons-video-alt3"></i>
                            <span><?php printf(_n('%d lesson', '%d lessons', $clarity_course->total_lessons, 'clarity-aws-ghl'), $clarity_course->total_lessons); ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="dashicons dashicons-clock"></i>
                            <span>
                                <?php
                                $total_duration = array_sum(array_column($lessons, 'duration_minutes'));
                                if ($total_duration > 60) {
                                    printf(__('%dh %dm total', 'clarity-aws-ghl'), floor($total_duration / 60), $total_duration % 60);
                                } else {
                                    printf(__('%d minutes total', 'clarity-aws-ghl'), $total_duration);
                                }
                                ?>
                            </span>
                        </div>
                        
                        <?php if ($clarity_course->course_price > 0): ?>
                            <div class="meta-item price">
                                <i class="dashicons dashicons-money-alt"></i>
                                <span>$<?php echo esc_html(number_format($clarity_course->course_price, 2)); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="meta-item price free">
                                <i class="dashicons dashicons-heart"></i>
                                <span><?php _e('Free', 'clarity-aws-ghl'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($clarity_course->course_description)): ?>
                        <div class="course-description">
                            <?php echo wp_kses_post(wpautop($clarity_course->course_description)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="course-enrollment">
                    <?php if ($is_enrolled && $progress_data): ?>
                        <div class="enrollment-card enrolled">
                            <h3><?php _e('Your Progress', 'clarity-aws-ghl'); ?></h3>
                            
                            <div class="progress-display">
                                <div class="progress-circle">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <path class="circle" stroke-dasharray="<?php echo esc_attr($progress_data['progress_percentage']); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <text x="18" y="20.35" class="percentage"><?php echo esc_html($progress_data['progress_percentage']); ?>%</text>
                                    </svg>
                                </div>
                                
                                <div class="progress-stats">
                                    <p class="completion-percentage"><?php echo esc_html($progress_data['progress_percentage']); ?>% Complete</p>
                                    <p class="lesson-progress">
                                        <?php
                                        $completed_count = count(array_filter($progress_data['lessons'], function($l) { return $l['completed']; }));
                                        printf(__('%d of %d lessons completed', 'clarity-aws-ghl'), $completed_count, count($progress_data['lessons']));
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="enrollment-actions">
                                <?php
                                // Find next available lesson
                                $next_lesson = null;
                                foreach ($progress_data['lessons'] as $lesson_data) {
                                    if ($lesson_data['can_access'] && !$lesson_data['completed']) {
                                        $next_lesson = $lesson_data['lesson'];
                                        break;
                                    }
                                }
                                
                                if ($next_lesson):
                                ?>
                                    <a href="<?php echo home_url('/lesson/' . $next_lesson->lesson_slug); ?>" class="button button-primary button-large">
                                        <?php _e('Continue Learning', 'clarity-aws-ghl'); ?>
                                    </a>
                                <?php elseif ($progress_data['progress_percentage'] >= 100): ?>
                                    <div class="course-completed">
                                        <p class="completion-message">
                                            <i class="dashicons dashicons-awards"></i>
                                            <?php _e('Congratulations! You completed this course.', 'clarity-aws-ghl'); ?>
                                        </p>
                                        <?php if ($progress_data['enrollment']->certificate_url): ?>
                                            <a href="<?php echo esc_url($progress_data['enrollment']->certificate_url); ?>" class="button certificate-button">
                                                <i class="dashicons dashicons-download"></i>
                                                <?php _e('Download Certificate', 'clarity-aws-ghl'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <a href="#course-lessons" class="button button-primary button-large">
                                        <?php _e('Start Course', 'clarity-aws-ghl'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="enrollment-card not-enrolled">
                            <?php
                            $frontend_templates = new Clarity_AWS_GHL_Frontend_Templates();
                            echo $frontend_templates->render_course_enrollment(array('course_id' => $clarity_course->id));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="course-content-section">
            <div class="course-lessons" id="course-lessons">
                <h2><?php _e('Course Content', 'clarity-aws-ghl'); ?></h2>
                
                <div class="lessons-container">
                    <?php foreach ($lessons as $index => $lesson): ?>
                        <?php
                        $lesson_progress = null;
                        if ($is_enrolled && $progress_data) {
                            foreach ($progress_data['lessons'] as $lp) {
                                if ($lp['lesson']->id == $lesson->id) {
                                    $lesson_progress = $lp;
                                    break;
                                }
                            }
                        }
                        
                        $can_access = $is_enrolled && $lesson_progress && $lesson_progress['can_access'];
                        $is_completed = $lesson_progress && $lesson_progress['completed'];
                        $is_preview = $lesson->is_preview;
                        ?>
                        
                        <div class="lesson-card <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $can_access || $is_preview ? 'accessible' : 'locked'; ?>">
                            <div class="lesson-status-indicator">
                                <?php if ($is_completed): ?>
                                    <i class="dashicons dashicons-yes-alt completed"></i>
                                <?php elseif ($can_access || $is_preview): ?>
                                    <span class="lesson-number"><?php echo $index + 1; ?></span>
                                <?php else: ?>
                                    <i class="dashicons dashicons-lock locked"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="lesson-content">
                                <div class="lesson-header">
                                    <h3 class="lesson-title"><?php echo esc_html($lesson->lesson_title); ?></h3>
                                    <div class="lesson-badges">
                                        <?php if ($is_preview): ?>
                                            <span class="preview-badge"><?php _e('Preview', 'clarity-aws-ghl'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($lesson->duration_minutes): ?>
                                            <span class="duration-badge">
                                                <i class="dashicons dashicons-clock"></i>
                                                <?php echo esc_html($lesson->duration_minutes); ?> min
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($lesson->lesson_description)): ?>
                                    <p class="lesson-description"><?php echo esc_html($lesson->lesson_description); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($is_completed && $lesson_progress['completion_date']): ?>
                                    <p class="completion-info">
                                        <i class="dashicons dashicons-calendar-alt"></i>
                                        <?php printf(__('Completed on %s', 'clarity-aws-ghl'), 
                                            date_i18n(get_option('date_format'), strtotime($lesson_progress['completion_date']))); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="lesson-actions">
                                <?php if ($can_access || $is_preview): ?>
                                    <a href="<?php echo home_url('/lesson/' . $lesson->lesson_slug); ?>" class="button lesson-button">
                                        <?php if ($is_preview): ?>
                                            <i class="dashicons dashicons-visibility"></i>
                                            <?php _e('Preview', 'clarity-aws-ghl'); ?>
                                        <?php elseif ($is_completed): ?>
                                            <i class="dashicons dashicons-controls-repeat"></i>
                                            <?php _e('Review', 'clarity-aws-ghl'); ?>
                                        <?php else: ?>
                                            <i class="dashicons dashicons-controls-play"></i>
                                            <?php _e('Start Lesson', 'clarity-aws-ghl'); ?>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <button class="button lesson-button locked" disabled>
                                        <i class="dashicons dashicons-lock"></i>
                                        <?php _e('Locked', 'clarity-aws-ghl'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!$is_enrolled): ?>
                <div class="enrollment-cta">
                    <h3><?php _e('Ready to Start Learning?', 'clarity-aws-ghl'); ?></h3>
                    <p><?php _e('Join thousands of students learning with our expert-led courses.', 'clarity-aws-ghl'); ?></p>
                    
                    <?php
                    echo $frontend_templates->render_course_enrollment(array(
                        'course_id' => $clarity_course->id,
                        'button_text' => $clarity_course->course_price > 0 ? 
                            sprintf(__('Enroll Now - $%s', 'clarity-aws-ghl'), number_format($clarity_course->course_price, 2)) :
                            __('Start Learning for Free', 'clarity-aws-ghl')
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.clarity-course-template {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.breadcrumbs {
    margin-bottom: 20px;
    font-size: 14px;
}

.breadcrumbs a {
    color: #0073aa;
    text-decoration: none;
}

.separator {
    margin: 0 8px;
    color: #999;
}

.course-hero {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-bottom: 50px;
}

.tier-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 15px;
}

.tier-1 { background: #e3f2fd; color: #1976d2; }
.tier-2 { background: #fff3e0; color: #f57c00; }
.tier-3 { background: #fce4ec; color: #c2185b; }

.course-title {
    font-size: 32px;
    margin-bottom: 20px;
    color: #333;
}

.course-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
}

.meta-item.price {
    font-weight: bold;
    color: #333;
}

.meta-item.free {
    color: #28a745;
}

.enrollment-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: sticky;
    top: 20px;
}

.progress-display {
    display: flex;
    align-items: center;
    gap: 20px;
    margin: 20px 0;
}

.progress-circle {
    width: 80px;
    height: 80px;
}

.circular-chart {
    display: block;
    max-width: 100%;
    max-height: 100%;
}

.circle-bg {
    fill: none;
    stroke: #e9ecef;
    stroke-width: 2.8;
}

.circle {
    fill: none;
    stroke: #28a745;
    stroke-width: 2.8;
    stroke-linecap: round;
}

.percentage {
    fill: #333;
    font-family: sans-serif;
    font-size: 0.5em;
    text-anchor: middle;
    font-weight: bold;
}

.lessons-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.lesson-card {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.lesson-card:hover:not(.locked) {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.lesson-card.completed {
    background: #f8fff8;
    border-color: #28a745;
}

.lesson-card.locked {
    opacity: 0.6;
    background: #f8f9fa;
}

.lesson-status-indicator {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
    background: #e9ecef;
    color: #6c757d;
    font-weight: bold;
}

.lesson-card.completed .lesson-status-indicator {
    background: #28a745;
    color: white;
}

.lesson-card.accessible .lesson-status-indicator {
    background: #007cba;
    color: white;
}

.lesson-content {
    flex: 1;
}

.lesson-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.lesson-title {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.lesson-badges {
    display: flex;
    gap: 8px;
}

.preview-badge,
.duration-badge {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 12px;
    background: #f1f3f4;
    color: #5f6368;
}

.preview-badge {
    background: #fff3cd;
    color: #856404;
}

.lesson-actions {
    margin-left: 20px;
}

.button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.button-primary {
    background: #007cba;
    color: white;
}

.button-primary:hover {
    background: #005a87;
}

.button.locked {
    background: #6c757d;
    color: white;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .course-hero {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .course-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .lesson-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .lesson-actions {
        margin-left: 0;
        width: 100%;
    }
}
</style>

<?php
get_footer();
?>