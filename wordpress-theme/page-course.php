<?php
/**
 * Template Name: Course Viewer
 * Description: Clean course viewing page with sequential lesson unlocking
 * 
 * @package Clarity_AWS_GHL
 */

get_header();


// if (is_user_logged_in()) {
//     $current_user = wp_get_current_user();
//     echo '<div style="background: blue; padding: 10px; margin: 10px;">
//         Logged in as: ' . $current_user->user_login . ' (User ID: ' . $current_user->ID . ')
//     </div>';
// } else {
//     echo '<div style="background: red; padding: 10px; margin: 10px; color: white;">
//         NOT LOGGED IN - Go to <a href="/wp-login.php" style="color: white; text-decoration: underline;">Login Page</a>
//     </div>';
// }


// Get course slug from URL, query var, or query parameter
$course_slug = '';

// Check WordPress query var first (from rewrite rule)
$course_slug = get_query_var('course_slug', '');

// Check URL path if query var is empty
if (empty($course_slug)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/course\/([^\/]+)/', $request_uri, $matches)) {
        $course_slug = $matches[1];
    }
}

// Check query parameter as fallback
if (empty($course_slug) && isset($_GET['course'])) {
    $course_slug = sanitize_text_field($_GET['course']);
}

// Check for course_id parameter as well
if (empty($course_slug) && isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    global $wpdb;
    $courses_table = $wpdb->prefix . 'clarity_courses';
    $course_data = $wpdb->get_row($wpdb->prepare(
        "SELECT course_slug FROM {$courses_table} WHERE id = %d", 
        $course_id
    ));
    if ($course_data) {
        $course_slug = $course_data->course_slug;
    }
}

// Initialize course manager
if (class_exists('Clarity_AWS_GHL_Course_Manager')) {
    $course_manager = new Clarity_AWS_GHL_Course_Manager();
    $course = null;
    
    // If no slug provided, default to the free course (Tier 1)
    if (empty($course_slug)) {
        global $wpdb;
        $tables = $course_manager->get_table_names();
        $course = $wpdb->get_row(
            "SELECT * FROM {$tables['courses']} 
            WHERE course_tier = 1 AND course_status = 'published' 
            ORDER BY course_order ASC
            LIMIT 1"
        );
    } else {
        $course = $course_manager->get_course_by_slug($course_slug);
    }
    
    if (!$course) {
        // Redirect to courses page if course not found
        wp_redirect(home_url('/courses'));
        exit;
    }
    
    // Get user data
    $user_id = get_current_user_id();
    $is_enrolled = false;
    $enrollment = null;
    $user_progress = array();
    $completed_lessons = 0;
    
    if ($user_id) {
        $enrollment = $course_manager->get_user_enrollment($user_id, $course->id);
        $is_enrolled = !empty($enrollment);
        
        // Get progress records directly from database (not dependent on enrollment)
        global $wpdb;
        $progress_table = $wpdb->prefix . 'clarity_user_progress';
        $user_progress = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$progress_table} 
            WHERE user_id = %d AND course_id = %d",
            $user_id, $course->id
        ));
        
        // Count completed lessons
        foreach ($user_progress as $progress) {
            if ($progress->is_completed) {
                $completed_lessons++;
            }
        }
    }
    
    // Get all lessons for this course
    $lessons = $course_manager->get_course_lessons($course->id);
    $total_lessons = count($lessons);
    $progress_percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
    
    // Check if user is admin for testing controls
    $is_admin = current_user_can('manage_options');
    
} else {
    wp_redirect(home_url());
    exit;
}
?>

<div class="course-viewer-container">
    <!-- Course Header -->
    <div class="course-header" <?php if (!empty($course->featured_image)): ?>style="background-image: url('<?php 
        // Handle both URLs and base64 data
        if (strpos($course->featured_image, 'data:image/') === 0) {
            // Base64 data - output directly
            echo $course->featured_image; 
        } else {
            // Regular URL - escape it
            echo esc_url($course->featured_image); 
        }
    ?>');"<?php endif; ?>>
        <div class="course-header-overlay">
        <div class="container">
            <div class="course-header-content">
                <h1 class="course-title">
                    <i class="bi <?php echo esc_attr($course->course_icon ?: 'bi-mortarboard'); ?>"></i>
                    <?php echo esc_html($course->course_title); ?>
                </h1>
                
                <div class="course-meta">
                    <span class="tier-badge">Tier <?php echo esc_html($course->course_tier); ?></span>
                    <span class="lessons-count"><?php echo $total_lessons; ?> Lessons</span>
                    <span class="price-badge">
                        <?php echo $course->course_price > 0 ? '$' . number_format($course->course_price, 2) : 'Free Course'; ?>
                    </span>
                </div>
                
                <div class="progress-section">
                    <div class="progress-text">
                        <strong><?php echo $completed_lessons; ?> of <?php echo $total_lessons; ?> lessons completed</strong>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <!-- Course Description Section -->
    <div class="course-description-section">
        <div class="container">
            <p class="course-description"><?php echo esc_html($course->course_description); ?></p>
        </div>
    </div>
    
    <!-- Course Lessons Section -->
    <div class="course-lessons-section">
        <div class="container">
            <h2 class="section-title">Lessons</h2>
            
            <?php if ($is_admin): ?>
                <!-- Admin Testing Controls -->
                <div class="admin-controls">
                    <div class="admin-controls-header">
                        <i class="bi bi-gear"></i>
                        <span>Admin Testing Controls</span>
                    </div>
                    <div class="admin-controls-buttons">
                        <button class="admin-btn admin-enroll-btn" 
                                data-course-id="<?php echo $course->id; ?>"
                                data-enrolled="<?php echo $is_enrolled ? '1' : '0'; ?>">
                            <?php echo $is_enrolled ? 'Remove Enrollment' : 'Quick Enroll'; ?>
                        </button>
                        <button class="admin-btn admin-reset-btn" 
                                data-course-id="<?php echo $course->id; ?>">
                            Reset Progress
                        </button>
                        <button class="admin-btn admin-complete-all-btn" 
                                data-course-id="<?php echo $course->id; ?>">
                            Complete All Lessons
                        </button>
                        <button class="admin-btn admin-test-modal-btn">
                            Test Modal
                        </button>
                    </div>
                    <div class="admin-controls-note">
                        These controls are only visible to administrators for testing purposes.
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Lesson Cards -->
            <div class="lessons-container">
                <?php 
                $lesson_number = 1;
                foreach ($lessons as $lesson):
                    // Determine lesson state
                    $is_completed = false;
                    $is_available = false;
                    $is_locked = true;
                    
                    // For testing without user - show all lessons as available if admin
                    if ($is_admin && !$user_id) {
                        $is_available = true;
                        $is_locked = false;
                    } elseif ($user_id) { // Allow any logged-in user to see progress (not just enrolled)
                        // Check if this lesson is completed
                        foreach ($user_progress as $progress) {
                            if ($progress->lesson_id == $lesson->id && $progress->is_completed) {
                                $is_completed = true;
                                break;
                            }
                        }
                        
                        // First lesson is always unlocked
                        if ($lesson_number === 1) {
                            $is_locked = false;
                            // Available if not completed, but still accessible if completed
                            $is_available = !$is_completed;
                        } else {
                            // Check if previous lesson is completed
                            $prev_lesson_completed = false;
                            if (isset($lessons[$lesson_number - 2])) {
                                $prev_lesson = $lessons[$lesson_number - 2];
                                foreach ($user_progress as $progress) {
                                    if ($progress->lesson_id == $prev_lesson->id && $progress->is_completed) {
                                        $prev_lesson_completed = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Unlock if previous lesson is completed OR if this lesson is already completed
                            if ($prev_lesson_completed || $is_completed) {
                                $is_locked = false;
                                // Available for first time viewing if not completed
                                $is_available = !$is_completed;
                            }
                        }
                    }
                    
                    // Determine visual state
                    $card_class = 'lesson-card';
                    if ($is_completed) {
                        $card_class .= ' completed';
                    } elseif ($is_available) {
                        $card_class .= ' available';
                    } else {
                        $card_class .= ' locked';
                    }
                ?>
                    <div class="<?php echo $card_class; ?>" data-lesson-id="<?php echo $lesson->id; ?>">
                        <div class="lesson-icon">
                            <?php if ($is_completed): ?>
                                <i class="bi bi-check-circle-fill completed-icon"></i>
                            <?php elseif ($is_available): ?>
                                <i class="bi bi-play-circle-fill available-icon" 
                                   onclick="openLessonModal(this.closest('.lesson-card').querySelector('.start-lesson-btn'))"></i>
                            <?php else: ?>
                                <i class="bi bi-lock-fill locked-icon"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-content">
                            <div class="lesson-header">
                                <h3 class="lesson-title">
                                    Lesson <?php echo $lesson_number; ?>- <?php echo esc_html($lesson->lesson_title); ?>
                                </h3>
                                <span class="lesson-number">Lesson <?php echo $lesson_number; ?></span>
                            </div>
                            
                            <?php if ($lesson->lesson_description): ?>
                                <p class="lesson-description"><?php echo esc_html($lesson->lesson_description); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($lesson->duration_minutes): ?>
                                <div class="lesson-duration">
                                    <i class="bi bi-clock"></i>
                                    <?php echo $lesson->duration_minutes; ?> minutes
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-actions">
                            <?php if ($is_completed): ?>
                                <button class="lesson-btn completed-btn start-lesson-btn"
                                        data-lesson-id="<?php echo $lesson->id; ?>"
                                        data-lesson-title="<?php echo esc_attr($lesson->lesson_title); ?>"
                                        data-lesson-url="<?php echo esc_attr($lesson->video_url); ?>"
                                        data-lesson-type="<?php echo esc_attr($lesson->video_type ?: 'youtube'); ?>"
                                        data-course-id="<?php echo $course->id; ?>"
                                        data-is-completed="1"
                                        onclick="openLessonModal(this)">
                                    <i class="bi bi-play-circle"></i>
                                    Rewatch
                                </button>
                            <?php elseif ($is_available): ?>
                                <button class="lesson-btn start-btn start-lesson-btn"
                                        data-lesson-id="<?php echo $lesson->id; ?>"
                                        data-lesson-title="<?php echo esc_attr($lesson->lesson_title); ?>"
                                        data-lesson-url="<?php echo esc_attr($lesson->video_url); ?>"
                                        data-lesson-type="<?php echo esc_attr($lesson->video_type ?: 'youtube'); ?>"
                                        data-course-id="<?php echo $course->id; ?>"
                                        data-is-completed="0"
                                        onclick="openLessonModal(this)">
                                    Start Lesson
                                </button>
                            <?php else: ?>
                                <button class="lesson-btn locked-btn" disabled>
                                    Locked
                                </button>
                            <?php endif; ?>
                            
                            <!-- Admin Controls per Lesson -->
                            <?php if ($is_admin): ?>
                                <div class="admin-lesson-controls">
                                    <?php if (!$is_completed): ?>
                                        <button class="admin-lesson-btn mark-complete-btn" 
                                                data-lesson-id="<?php echo $lesson->id; ?>"
                                                data-course-id="<?php echo $course->id; ?>"
                                                onclick="adminCompleteLesson(<?php echo $lesson->id; ?>, <?php echo $course->id; ?>)">
                                            Mark Complete
                                        </button>
                                    <?php else: ?>
                                        <button class="admin-lesson-btn mark-incomplete-btn" 
                                                data-lesson-id="<?php echo $lesson->id; ?>"
                                                data-course-id="<?php echo $course->id; ?>"
                                                onclick="adminIncompleteLesson(<?php echo $lesson->id; ?>, <?php echo $course->id; ?>)">
                                            Mark Incomplete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    $lesson_number++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Video Modal (keeping existing modal structure) -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">Lesson Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="video-container">
                    <div id="video-player-container" class="ratio ratio-16x9">
                        <!-- Video player will be injected here -->
                    </div>
                </div>
                
                <!-- Video Controls -->
                <div class="video-controls p-3 bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="lesson-info">
                                <h6 class="mb-1" id="modal-lesson-title">Lesson Title</h6>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="lesson-actions">
                                <button class="btn btn-success" id="mark-complete-btn" style="display: none;">
                                    <i class="bi bi-check-circle me-1"></i> Mark Complete
                                </button>
                                <button class="btn btn-outline-secondary" id="close-video-btn" onclick="closeVideoModal()">
                                    <i class="bi bi-x-circle me-1"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Course Viewer Styles */
.course-viewer-container {
    min-height: 100vh;
    background: #f8f9fa;
}

/* Course Header */
.course-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

/* Overlay for better text readability when image is present */
.course-header-overlay {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
    padding: 60px 0;
    position: relative;
    z-index: 1;
}

/* When no image, don't show overlay gradient */
.course-header:not([style*="background-image"]) .course-header-overlay {
    background: transparent;
    padding: 60px 0;
}

.course-header-content {
    text-align: center;
    position: relative;
    z-index: 2;
}

.course-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.course-title i {
    margin-right: 0.5rem;
}

/* Course Description Section (below header) */
.course-description-section {
    padding: 2rem 0 1rem;
    background: white;
    border-bottom: 1px solid #e9ecef;
}

.course-description-section .course-description {
    font-size: 1.2rem;
    line-height: 1.8;
    color: #555;
    margin: 0;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.course-meta {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.tier-badge, .lessons-count, .price-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.progress-section {
    max-width: 400px;
    margin: 0 auto;
}

.progress-text {
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.progress-bar-container {
    background: rgba(255, 255, 255, 0.3);
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    background: #28a745;
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Course Lessons Section */
.course-lessons-section {
    padding: 3rem 0;
}

.section-title {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
    font-weight: 600;
    color: #333;
}

/* Admin Controls */
.admin-controls {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.admin-controls-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #856404;
}

.admin-controls-buttons {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.admin-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.admin-btn:hover {
    background: #0056b3;
}

.admin-controls-note {
    font-size: 0.85rem;
    color: #856404;
    font-style: italic;
}

/* Lessons Container */
.lessons-container {
    display: grid;
    gap: 1rem;
    max-width: 800px;
    margin: 0 auto;
}

/* Lesson Cards */
.lesson-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.lesson-card.available:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.lesson-card.locked {
    opacity: 0.6;
    background: #f8f9fa;
}

.lesson-card.completed {
    background: #f8fff9;
    border-left: 4px solid #28a745;
}

/* Lesson Icon */
.lesson-icon {
    flex-shrink: 0;
    font-size: 3rem;
}

.available-icon {
    color: #007bff;
    cursor: pointer;
    transition: color 0.2s;
}

.available-icon:hover {
    color: #0056b3;
}

.completed-icon {
    color: #28a745;
}

.locked-icon {
    color: #6c757d;
}

/* Lesson Content */
.lesson-content {
    flex: 1;
}

.lesson-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.lesson-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.lesson-number {
    background: #e9ecef;
    color: #6c757d;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.lesson-description {
    color: #666;
    margin: 0.5rem 0;
    font-size: 0.95rem;
}

.lesson-duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Lesson Actions */
.lesson-actions {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.lesson-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 120px;
}

.start-btn {
    background: #007bff;
    color: white;
}

.start-btn:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.completed-btn {
    background: #28a745;
    color: white;
}

.completed-btn:hover {
    background: #1e7e34;
}

.locked-btn {
    background: #6c757d;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Admin Lesson Controls */
.admin-lesson-controls {
    margin-top: 0.5rem;
}

.admin-lesson-btn {
    background: #ffc107;
    color: #212529;
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: background 0.2s;
}

.admin-lesson-btn:hover {
    background: #e0a800;
}

.mark-incomplete-btn {
    background: #dc3545;
    color: white;
}

.mark-incomplete-btn:hover {
    background: #c82333;
}

/* Modal Styles */
#videoModal .modal-content {
    border: none;
    border-radius: 10px;
    overflow: hidden;
}

#videoModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

#videoModal .modal-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.video-container {
    background: #000;
    min-height: 400px;
}

.video-controls {
    border-top: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .course-title {
        font-size: 2rem;
    }
    
    .lesson-card {
        flex-direction: column;
        text-align: center;
    }
    
    .lesson-header {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .admin-controls-buttons {
        justify-content: center;
    }
}
</style>

<script>
// Global variables for modal functionality
let currentLessonId = null;
let currentCourseId = null;

// Function to open lesson modal
function openLessonModal(buttonElement) {
    console.log('Opening lesson modal');
    
    try {
        // Get lesson data from button
        const lessonId = buttonElement.getAttribute('data-lesson-id');
        const lessonTitle = buttonElement.getAttribute('data-lesson-title') || 'Lesson';
        const lessonUrl = buttonElement.getAttribute('data-lesson-url') || '';
        const lessonType = buttonElement.getAttribute('data-lesson-type') || 'youtube';
        const courseId = buttonElement.getAttribute('data-course-id');
        const isCompleted = buttonElement.getAttribute('data-is-completed') === '1';
        
        // Store current lesson data
        currentLessonId = lessonId;
        currentCourseId = courseId;
        
        // Update modal content
        const modalElement = document.getElementById('videoModal');
        const modalTitle = modalElement.querySelector('#videoModalLabel');
        const modalLessonTitle = modalElement.querySelector('#modal-lesson-title');
        
        if (modalTitle) modalTitle.textContent = lessonTitle;
        if (modalLessonTitle) modalLessonTitle.textContent = lessonTitle;
        
        // Show/hide mark complete button
        const markCompleteBtn = document.getElementById('mark-complete-btn');
        if (markCompleteBtn) {
            if (!isCompleted) {
                markCompleteBtn.style.display = 'inline-block';
            } else {
                markCompleteBtn.style.display = 'none';
            }
        }
        
        // Load video content
        loadVideoContent(lessonUrl, lessonType);
        
        // Show modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            // Fallback for manual modal
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
        }
        
    } catch (error) {
        console.error('Error opening modal:', error);
        alert('Error opening lesson: ' + error.message);
    }
}

// Function to load video content
function loadVideoContent(videoUrl, videoType) {
    console.log('Loading video:', videoUrl, videoType);
    
    const container = document.getElementById('video-player-container');
    if (!container) {
        console.error('Video container not found');
        return;
    }
    
    // Clear previous content
    container.innerHTML = '';
    
    if (!videoUrl) {
        container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 bg-dark text-white"><h4>No video URL provided</h4></div>';
        return;
    }
    
    if (videoType === 'youtube') {
        // Extract YouTube video ID
        let videoId = '';
        const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        const match = videoUrl.match(regex);
        
        if (match && match[1]) {
            videoId = match[1];
            const embedUrl = `https://www.youtube.com/embed/${videoId}?enablejsapi=1&rel=0&modestbranding=1`;
            const iframe = `<iframe src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            container.innerHTML = iframe;
        } else {
            container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 bg-dark text-white"><h4>Invalid YouTube URL</h4></div>';
        }
    } else {
        // Direct video or other types
        const video = `
            <video controls class="w-100 h-100">
                <source src="${videoUrl}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        `;
        container.innerHTML = video;
    }
}

// Function to close video modal
function closeVideoModal() {
    console.log('Closing video modal');
    
    const modalElement = document.getElementById('videoModal');
    if (!modalElement) return;
    
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        } else {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
        
        // Clear video content
        const container = document.getElementById('video-player-container');
        if (container) {
            container.innerHTML = '';
        }
    } catch (error) {
        console.error('Error closing modal:', error);
    }
}

// Admin function to complete lesson
function adminCompleteLesson(lessonId, courseId) {
    console.log('Admin completing lesson:', lessonId, courseId);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'admin_complete_lesson',
            lesson_id: lessonId,
            course_id: courseId,
            user_id: <?php echo $user_id ?: 0; ?>,
            nonce: '<?php echo wp_create_nonce('admin_course_actions'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Admin complete response:', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to complete lesson: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error completing lesson:', error);
        alert('Error completing lesson');
    });
}

// Admin function to mark lesson incomplete
function adminIncompleteLesson(lessonId, courseId) {
    console.log('Admin marking lesson incomplete:', lessonId, courseId);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'admin_uncomplete_lesson',
            lesson_id: lessonId,
            course_id: courseId,
            user_id: <?php echo $user_id ?: 0; ?>,
            nonce: '<?php echo wp_create_nonce('admin_course_actions'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Admin incomplete response:', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark lesson incomplete: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error marking lesson incomplete:', error);
        alert('Error marking lesson incomplete');
    });
}

// Document ready functions
document.addEventListener('DOMContentLoaded', function() {
    
    // Admin: Quick Enroll/Remove button
    const enrollBtn = document.querySelector('.admin-enroll-btn');
    if (enrollBtn) {
        enrollBtn.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            const isEnrolled = this.getAttribute('data-enrolled') === '1';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'admin_toggle_enrollment',
                    course_id: courseId,
                    user_id: <?php echo $user_id ?: 0; ?>,
                    enroll: !isEnrolled,
                    nonce: '<?php echo wp_create_nonce('admin_course_actions'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to toggle enrollment');
                }
            });
        });
    }
    
    // Admin: Reset Progress button
    const resetBtn = document.querySelector('.admin-reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (!confirm('Reset all progress for this course?')) return;
            
            const courseId = this.getAttribute('data-course-id');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'admin_reset_progress',
                    course_id: courseId,
                    user_id: <?php echo $user_id ?: 0; ?>,
                    nonce: '<?php echo wp_create_nonce('admin_course_actions'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to reset progress');
                }
            });
        });
    }
    
    // Admin: Complete All Lessons button
    const completeAllBtn = document.querySelector('.admin-complete-all-btn');
    if (completeAllBtn) {
        completeAllBtn.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'admin_complete_all_lessons',
                    course_id: courseId,
                    user_id: <?php echo $user_id ?: 0; ?>,
                    nonce: '<?php echo wp_create_nonce('admin_course_actions'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to complete all lessons');
                }
            });
        });
    }
    
    // Admin: Test Modal button
    const testModalBtn = document.querySelector('.admin-test-modal-btn');
    if (testModalBtn) {
        testModalBtn.addEventListener('click', function() {
            console.log('Test modal button clicked');
            
            // Test modal with dummy data
            const modalElement = document.getElementById('videoModal');
            const modalTitle = modalElement.querySelector('#videoModalLabel');
            const modalLessonTitle = modalElement.querySelector('#modal-lesson-title');
            
            if (modalTitle) modalTitle.textContent = 'Test Lesson';
            if (modalLessonTitle) modalLessonTitle.textContent = 'Test Lesson';
            
            // Load test video
            loadVideoContent('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube');
            
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        });
    }
    
    // Mark Complete button in modal
    const markCompleteBtn = document.getElementById('mark-complete-btn');
    if (markCompleteBtn) {
        markCompleteBtn.addEventListener('click', function() {
            if (!currentLessonId || !currentCourseId) return;
            
            this.disabled = true;
            this.textContent = 'Marking Complete...';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mark_lesson_complete',
                    lesson_id: currentLessonId,
                    course_id: currentCourseId,
                    user_id: <?php echo $user_id ?: 0; ?>,
                    nonce: '<?php echo wp_create_nonce('clarity_ajax_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Lesson Complete!';
                    setTimeout(() => {
                        closeVideoModal();
                        location.reload();
                    }, 2000);
                } else {
                    this.disabled = false;
                    this.textContent = 'Mark Complete';
                    alert('Failed to mark lesson complete: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                this.disabled = false;
                this.textContent = 'Mark Complete';
                alert('Error marking lesson complete');
            });
        });
    }
});
</script>

<?php get_footer(); ?>