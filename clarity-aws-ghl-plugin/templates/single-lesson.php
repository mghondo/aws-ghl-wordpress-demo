<?php
/**
 * Single Lesson Template
 *
 * Template for displaying individual lesson pages
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $clarity_lesson;

if (!$clarity_lesson) {
    get_template_part('404');
    get_footer();
    return;
}

// Get lesson data
$course_manager = new Clarity_AWS_GHL_Course_Manager();
$lesson_handler = new Clarity_AWS_GHL_Lesson_Handler();
$progress_tracker = new Clarity_AWS_GHL_Progress_Tracker();

$course = $course_manager->get_course($clarity_lesson->course_id);
$user_id = get_current_user_id();

// Check access
if ($user_id && !$clarity_lesson->is_preview && !$course_manager->can_access_lesson($user_id, $clarity_lesson->id)) {
    ?>
    <div class="container">
        <div class="lesson-access-denied">
            <h1><?php _e('Access Denied', 'clarity-aws-ghl'); ?></h1>
            <p><?php _e('You need to complete previous lessons to access this content.', 'clarity-aws-ghl'); ?></p>
            <a href="<?php echo home_url('/course/' . $course->course_slug); ?>" class="button button-primary">
                <?php _e('Back to Course', 'clarity-aws-ghl'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Get progress data
$progress_data = null;
$is_completed = false;
if ($user_id) {
    $progress_data = $course_manager->get_user_course_progress($user_id, $course->id);
    if ($progress_data) {
        foreach ($progress_data['lessons'] as $lp) {
            if ($lp['lesson']->id == $clarity_lesson->id) {
                $is_completed = $lp['completed'];
                break;
            }
        }
    }
}

// Get lesson navigation
$navigation_data = null;
if ($user_id) {
    global $wpdb;
    $db_courses = new Clarity_AWS_GHL_Database_Courses();
    $tables = $db_courses->get_table_names();
    
    // Get previous lesson
    $previous_lesson = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$tables['lessons']} 
        WHERE course_id = %d AND lesson_order < %d 
        ORDER BY lesson_order DESC LIMIT 1
    ", $clarity_lesson->course_id, $clarity_lesson->lesson_order));
    
    // Get next lesson
    $next_lesson = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$tables['lessons']} 
        WHERE course_id = %d AND lesson_order > %d 
        ORDER BY lesson_order ASC LIMIT 1
    ", $clarity_lesson->course_id, $clarity_lesson->lesson_order));
    
    $navigation_data = array(
        'previous' => $previous_lesson,
        'next' => $next_lesson,
        'can_access_next' => $next_lesson ? $course_manager->can_access_lesson($user_id, $next_lesson->id) : false
    );
}
?>

<div class="clarity-lesson-template">
    <div class="lesson-header">
        <div class="container">
            <div class="breadcrumbs">
                <a href="<?php echo home_url('/courses'); ?>"><?php _e('Courses', 'clarity-aws-ghl'); ?></a>
                <span class="separator">/</span>
                <a href="<?php echo home_url('/course/' . $course->course_slug); ?>"><?php echo esc_html($course->course_title); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo esc_html($clarity_lesson->lesson_title); ?></span>
            </div>
            
            <div class="lesson-info">
                <h1 class="lesson-title"><?php echo esc_html($clarity_lesson->lesson_title); ?></h1>
                
                <div class="lesson-meta">
                    <span class="lesson-position">
                        <?php printf(__('Lesson %d', 'clarity-aws-ghl'), $clarity_lesson->lesson_order); ?>
                    </span>
                    
                    <?php if ($clarity_lesson->duration_minutes): ?>
                        <span class="lesson-duration">
                            <i class="dashicons dashicons-clock"></i>
                            <?php echo esc_html($clarity_lesson->duration_minutes); ?> min
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($clarity_lesson->is_preview): ?>
                        <span class="preview-badge"><?php _e('Preview', 'clarity-aws-ghl'); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($is_completed): ?>
                        <span class="completed-badge">
                            <i class="dashicons dashicons-yes-alt"></i>
                            <?php _e('Completed', 'clarity-aws-ghl'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($progress_data): ?>
                    <div class="course-progress-indicator">
                        <span class="progress-label"><?php _e('Course Progress:', 'clarity-aws-ghl'); ?></span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($progress_data['progress_percentage']); ?>%"></div>
                        </div>
                        <span class="progress-percentage"><?php echo esc_html($progress_data['progress_percentage']); ?>%</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="lesson-content">
        <div class="container">
            <div class="lesson-main">
                <div class="lesson-video-section">
                    <?php if (!empty($clarity_lesson->video_url)): ?>
                        <div class="lesson-video">
                            <?php echo $lesson_handler->generate_video_embed($clarity_lesson->video_url, $clarity_lesson->video_type); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($clarity_lesson->lesson_content)): ?>
                        <div class="lesson-text-content">
                            <?php echo wp_kses_post(wpautop($clarity_lesson->lesson_content)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($clarity_lesson->resources)): ?>
                        <div class="lesson-resources">
                            <h3><?php _e('Resources', 'clarity-aws-ghl'); ?></h3>
                            <div class="resources-content">
                                <?php
                                $resources = json_decode($clarity_lesson->resources, true);
                                if (is_array($resources)) {
                                    echo '<ul class="resources-list">';
                                    foreach ($resources as $resource) {
                                        if (isset($resource['title']) && isset($resource['url'])) {
                                            echo '<li><a href="' . esc_url($resource['url']) . '" target="_blank">' . esc_html($resource['title']) . '</a></li>';
                                        }
                                    }
                                    echo '</ul>';
                                } else {
                                    echo wp_kses_post(wpautop($clarity_lesson->resources));
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="lesson-sidebar">
                    <?php if ($user_id && !$clarity_lesson->is_preview): ?>
                        <div class="lesson-actions-card">
                            <h3><?php _e('Lesson Actions', 'clarity-aws-ghl'); ?></h3>
                            
                            <?php if (!$is_completed): ?>
                                <button class="button button-primary complete-lesson-btn" data-lesson-id="<?php echo esc_attr($clarity_lesson->id); ?>">
                                    <i class="dashicons dashicons-yes-alt"></i>
                                    <?php _e('Mark as Complete', 'clarity-aws-ghl'); ?>
                                </button>
                            <?php else: ?>
                                <div class="completion-status">
                                    <i class="dashicons dashicons-yes-alt"></i>
                                    <?php _e('Lesson Completed', 'clarity-aws-ghl'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-notes-card">
                            <h3><?php _e('My Notes', 'clarity-aws-ghl'); ?></h3>
                            <div class="notes-section">
                                <?php
                                global $wpdb;
                                $tables = $db_courses->get_table_names();
                                $existing_notes = $wpdb->get_var($wpdb->prepare("
                                    SELECT notes FROM {$tables['user_progress']} 
                                    WHERE user_id = %d AND lesson_id = %d
                                ", $user_id, $clarity_lesson->id));
                                ?>
                                
                                <textarea 
                                    class="lesson-notes" 
                                    data-lesson-id="<?php echo esc_attr($clarity_lesson->id); ?>" 
                                    placeholder="<?php _e('Add your notes here...', 'clarity-aws-ghl'); ?>"
                                    rows="6"
                                ><?php echo esc_textarea($existing_notes); ?></textarea>
                                
                                <button class="button save-notes-btn" data-lesson-id="<?php echo esc_attr($clarity_lesson->id); ?>">
                                    <?php _e('Save Notes', 'clarity-aws-ghl'); ?>
                                </button>
                                
                                <p class="notes-auto-save"><?php _e('Notes are auto-saved as you type', 'clarity-aws-ghl'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="course-outline-card">
                        <h3><?php _e('Course Outline', 'clarity-aws-ghl'); ?></h3>
                        
                        <?php
                        $all_lessons = $course_manager->get_course_lessons($course->id, false);
                        ?>
                        
                        <div class="course-lessons-list">
                            <?php foreach ($all_lessons as $index => $lesson): ?>
                                <?php
                                $lesson_progress = null;
                                $can_access = false;
                                $completed = false;
                                
                                if ($progress_data) {
                                    foreach ($progress_data['lessons'] as $lp) {
                                        if ($lp['lesson']->id == $lesson->id) {
                                            $lesson_progress = $lp;
                                            $can_access = $lp['can_access'];
                                            $completed = $lp['completed'];
                                            break;
                                        }
                                    }
                                }
                                
                                $is_current = $lesson->id == $clarity_lesson->id;
                                ?>
                                
                                <div class="outline-lesson-item <?php echo $is_current ? 'current' : ''; ?> <?php echo $completed ? 'completed' : ''; ?> <?php echo $can_access || $lesson->is_preview ? 'accessible' : 'locked'; ?>">
                                    <div class="lesson-status">
                                        <?php if ($completed): ?>
                                            <i class="dashicons dashicons-yes-alt"></i>
                                        <?php elseif ($is_current): ?>
                                            <i class="dashicons dashicons-controls-play"></i>
                                        <?php elseif ($can_access || $lesson->is_preview): ?>
                                            <span class="lesson-number"><?php echo $index + 1; ?></span>
                                        <?php else: ?>
                                            <i class="dashicons dashicons-lock"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="lesson-info">
                                        <?php if ($can_access || $lesson->is_preview || $is_current): ?>
                                            <a href="<?php echo home_url('/lesson/' . $lesson->lesson_slug); ?>" class="lesson-link">
                                                <?php echo esc_html($lesson->lesson_title); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="lesson-title"><?php echo esc_html($lesson->lesson_title); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($lesson->duration_minutes): ?>
                                            <span class="lesson-duration"><?php echo esc_html($lesson->duration_minutes); ?>m</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($navigation_data): ?>
        <div class="lesson-navigation">
            <div class="container">
                <div class="nav-controls">
                    <div class="nav-previous">
                        <?php if ($navigation_data['previous']): ?>
                            <a href="<?php echo home_url('/lesson/' . $navigation_data['previous']->lesson_slug); ?>" class="nav-button prev">
                                <i class="dashicons dashicons-arrow-left-alt2"></i>
                                <div class="nav-content">
                                    <span class="nav-label"><?php _e('Previous Lesson', 'clarity-aws-ghl'); ?></span>
                                    <span class="nav-title"><?php echo esc_html($navigation_data['previous']->lesson_title); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nav-center">
                        <a href="<?php echo home_url('/course/' . $course->course_slug); ?>" class="back-to-course">
                            <i class="dashicons dashicons-list-view"></i>
                            <?php _e('Course Overview', 'clarity-aws-ghl'); ?>
                        </a>
                    </div>
                    
                    <div class="nav-next">
                        <?php if ($navigation_data['next']): ?>
                            <a href="<?php echo home_url('/lesson/' . $navigation_data['next']->lesson_slug); ?>" 
                               class="nav-button next <?php echo $navigation_data['can_access_next'] ? '' : 'disabled'; ?>">
                                <div class="nav-content">
                                    <span class="nav-label"><?php _e('Next Lesson', 'clarity-aws-ghl'); ?></span>
                                    <span class="nav-title"><?php echo esc_html($navigation_data['next']->lesson_title); ?></span>
                                </div>
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </a>
                        <?php else: ?>
                            <div class="course-complete-indicator">
                                <i class="dashicons dashicons-awards"></i>
                                <span><?php _e('Course Complete!', 'clarity-aws-ghl'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.clarity-lesson-template {
    min-height: 100vh;
    background: #f8f9fa;
}

.lesson-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    padding: 20px 0;
}

.breadcrumbs {
    margin-bottom: 15px;
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

.lesson-title {
    font-size: 28px;
    margin-bottom: 15px;
    color: #333;
}

.lesson-meta {
    display: flex;
    gap: 20px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.lesson-meta > span {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #666;
}

.preview-badge,
.completed-badge {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.completed-badge {
    background: #d4edda;
    color: #155724;
}

.course-progress-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    width: 200px;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.6s ease;
}

.lesson-content {
    padding: 40px 0;
}

.lesson-main {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

.lesson-video {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 8px;
    margin-bottom: 30px;
}

.lesson-video iframe,
.lesson-video video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.lesson-text-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.lesson-resources {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.lesson-actions-card,
.lesson-notes-card,
.course-outline-card {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.lesson-actions-card h3,
.lesson-notes-card h3,
.course-outline-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 18px;
}

.complete-lesson-btn {
    width: 100%;
    padding: 12px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.complete-lesson-btn:hover {
    background: #218838;
}

.completion-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #28a745;
    font-weight: 600;
    padding: 12px;
    background: #d4edda;
    border-radius: 6px;
}

.lesson-notes {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    margin-bottom: 10px;
}

.save-notes-btn {
    background: #007cba;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.notes-auto-save {
    font-size: 12px;
    color: #666;
    margin: 10px 0 0 0;
}

.course-lessons-list {
    max-height: 400px;
    overflow-y: auto;
}

.outline-lesson-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 5px;
    transition: background 0.2s ease;
}

.outline-lesson-item:hover {
    background: #f8f9fa;
}

.outline-lesson-item.current {
    background: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.outline-lesson-item.completed .lesson-status {
    color: #28a745;
}

.outline-lesson-item.locked {
    opacity: 0.6;
}

.lesson-status {
    width: 30px;
    text-align: center;
    margin-right: 10px;
}

.lesson-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lesson-link {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.lesson-link:hover {
    color: #0073aa;
}

.lesson-navigation {
    background: #fff;
    border-top: 1px solid #e9ecef;
    padding: 30px 0;
}

.nav-controls {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 30px;
    align-items: center;
}

.nav-button {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.nav-button:hover:not(.disabled) {
    background: #e9ecef;
    border-color: #007cba;
}

.nav-button.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.nav-content {
    display: flex;
    flex-direction: column;
}

.nav-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.nav-title {
    font-weight: 500;
    margin-top: 2px;
}

.nav-next {
    text-align: right;
}

.back-to-course {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    padding: 10px 20px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.back-to-course:hover {
    color: #007cba;
    border-color: #007cba;
}

.course-complete-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ffc107;
    font-weight: 600;
    padding: 20px;
    background: #fff3cd;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .lesson-main {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .lesson-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .course-progress-indicator {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .nav-controls {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .nav-previous,
    .nav-next {
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Complete lesson
    $('.complete-lesson-btn').on('click', function() {
        var $btn = $(this);
        var lessonId = $btn.data('lesson-id');
        
        $btn.prop('disabled', true).text('Marking Complete...');
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_mark_lesson_complete',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error marking lesson complete');
                    $btn.prop('disabled', false).text('Mark as Complete');
                }
            },
            error: function() {
                alert('Error marking lesson complete');
                $btn.prop('disabled', false).text('Mark as Complete');
            }
        });
    });
    
    // Auto-save notes
    var saveTimeout;
    $('.lesson-notes').on('input', function() {
        var $textarea = $(this);
        var lessonId = $textarea.data('lesson-id');
        var notes = $textarea.val();
        
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            saveNotes(lessonId, notes);
        }, 2000);
    });
    
    // Manual save notes
    $('.save-notes-btn').on('click', function() {
        var $btn = $(this);
        var lessonId = $btn.data('lesson-id');
        var notes = $('.lesson-notes[data-lesson-id="' + lessonId + '"]').val();
        
        saveNotes(lessonId, notes, $btn);
    });
    
    function saveNotes(lessonId, notes, $btn) {
        if ($btn) {
            $btn.text('Saving...');
        }
        
        $.ajax({
            url: clarityProgress.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_save_lesson_notes',
                nonce: clarityProgress.nonce,
                lesson_id: lessonId,
                notes: notes
            },
            success: function(response) {
                if ($btn) {
                    $btn.text('Saved!');
                    setTimeout(function() {
                        $btn.text('Save Notes');
                    }, 2000);
                }
            },
            error: function() {
                if ($btn) {
                    $btn.text('Error');
                    setTimeout(function() {
                        $btn.text('Save Notes');
                    }, 2000);
                }
            }
        });
    }
});
</script>

<?php
get_footer();
?>