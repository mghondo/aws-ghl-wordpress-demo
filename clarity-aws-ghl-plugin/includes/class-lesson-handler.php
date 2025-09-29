<?php
/**
 * Lesson Handler Class
 *
 * Manages individual lessons, video embeds, and progress tracking
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Lesson_Handler {
    
    /**
     * Database instance
     */
    private $db_courses;
    private $tables;
    
    /**
     * Default Alex Hormozi videos for Tier 1
     */
    private $default_tier1_videos = array(
        array(
            'title' => 'Real Estate Investing Fundamentals',
            'youtube_id' => 'QKJVcXErvIo', // Placeholder - replace with actual video ID
            'duration' => 15,
            'description' => 'Learn the fundamental principles of real estate investing from scratch.'
        ),
        array(
            'title' => 'Finding Your First Deal',
            'youtube_id' => 'YOUTUBE_VIDEO_ID_2',
            'duration' => 20,
            'description' => 'Discover proven strategies for finding and evaluating your first real estate deal.'
        ),
        array(
            'title' => 'Financing Strategies for Beginners',
            'youtube_id' => 'YOUTUBE_VIDEO_ID_3',
            'duration' => 18,
            'description' => 'Understanding different financing options and how to secure funding.'
        ),
        array(
            'title' => 'Property Analysis Masterclass',
            'youtube_id' => 'YOUTUBE_VIDEO_ID_4',
            'duration' => 25,
            'description' => 'Learn how to analyze properties like a pro and avoid costly mistakes.'
        ),
        array(
            'title' => 'Your First 90 Days Action Plan',
            'youtube_id' => 'YOUTUBE_VIDEO_ID_5',
            'duration' => 30,
            'description' => 'Step-by-step action plan for your first 90 days in real estate investing.'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_courses = new Clarity_AWS_GHL_Database_Courses();
        $this->tables = $this->db_courses->get_table_names();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Video tracking
        add_action('wp_ajax_update_video_progress', array($this, 'ajax_update_video_progress'));
        add_action('wp_ajax_save_lesson_notes', array($this, 'ajax_save_lesson_notes'));
        
        // Admin lesson management
        add_action('wp_ajax_create_lesson', array($this, 'ajax_create_lesson'));
        add_action('wp_ajax_update_lesson', array($this, 'ajax_update_lesson'));
        add_action('wp_ajax_delete_lesson', array($this, 'ajax_delete_lesson'));
        add_action('wp_ajax_reorder_lessons', array($this, 'ajax_reorder_lessons'));
        
        // Shortcodes for display
        add_shortcode('clarity_lesson', array($this, 'render_lesson_shortcode'));
        add_shortcode('clarity_course_player', array($this, 'render_course_player_shortcode'));
    }
    
    /**
     * Insert default lessons for courses
     */
    public function insert_default_lessons() {
        global $wpdb;
        
        // Get Tier 1 course
        $tier1_course = $wpdb->get_row(
            "SELECT * FROM {$this->tables['courses']} WHERE course_tier = 1 LIMIT 1"
        );
        
        if (!$tier1_course) {
            return;
        }
        
        // Check if lessons already exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['lessons']} WHERE course_id = %d",
            $tier1_course->id
        ));
        
        if ($existing > 0) {
            return;
        }
        
        // Insert Tier 1 lessons
        foreach ($this->default_tier1_videos as $index => $video) {
            $this->create_lesson(array(
                'course_id' => $tier1_course->id,
                'lesson_title' => $video['title'],
                'lesson_slug' => sanitize_title($video['title']),
                'lesson_description' => $video['description'],
                'video_url' => 'https://www.youtube.com/watch?v=' . $video['youtube_id'],
                'video_type' => 'youtube',
                'lesson_order' => $index + 1,
                'duration_minutes' => $video['duration'],
                'is_preview' => ($index === 0) ? 1 : 0 // First lesson is preview
            ));
        }
        
        // Insert placeholder lessons for Tier 2
        $tier2_course = $wpdb->get_row(
            "SELECT * FROM {$this->tables['courses']} WHERE course_tier = 2 LIMIT 1"
        );
        
        if ($tier2_course) {
            $tier2_lessons = array(
                'Advanced Market Analysis Techniques',
                'Creative Financing Strategies',
                'Building Your Real Estate Team',
                'Scaling Your Portfolio',
                'Tax Optimization for Investors'
            );
            
            foreach ($tier2_lessons as $index => $title) {
                $this->create_lesson(array(
                    'course_id' => $tier2_course->id,
                    'lesson_title' => $title,
                    'lesson_slug' => sanitize_title($title),
                    'lesson_description' => 'Advanced strategies for experienced investors.',
                    'lesson_order' => $index + 1,
                    'duration_minutes' => 45
                ));
            }
        }
        
        // Insert placeholder lessons for Tier 3
        $tier3_course = $wpdb->get_row(
            "SELECT * FROM {$this->tables['courses']} WHERE course_tier = 3 LIMIT 1"
        );
        
        if ($tier3_course) {
            $tier3_lessons = array(
                'Building a Real Estate Empire',
                'Syndication and Partnership Structures',
                'Commercial Real Estate Mastery',
                'International Investment Strategies',
                'Legacy and Wealth Preservation'
            );
            
            foreach ($tier3_lessons as $index => $title) {
                $this->create_lesson(array(
                    'course_id' => $tier3_course->id,
                    'lesson_title' => $title,
                    'lesson_slug' => sanitize_title($title),
                    'lesson_description' => 'Elite strategies for building a real estate empire.',
                    'lesson_order' => $index + 1,
                    'duration_minutes' => 60
                ));
            }
        }
    }
    
    /**
     * Create a lesson
     */
    public function create_lesson($data) {
        global $wpdb;
        
        $defaults = array(
            'course_id' => 0,
            'lesson_title' => '',
            'lesson_slug' => '',
            'lesson_description' => '',
            'lesson_content' => '',
            'video_url' => '',
            'video_type' => 'youtube',
            'lesson_order' => 0,
            'duration_minutes' => 0,
            'is_preview' => 0,
            'resources' => '',
            'meta_data' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate slug if not provided
        if (empty($data['lesson_slug'])) {
            $data['lesson_slug'] = sanitize_title($data['lesson_title']);
        }
        
        // Get next order if not specified
        if ($data['lesson_order'] == 0) {
            $max_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(lesson_order) FROM {$this->tables['lessons']} WHERE course_id = %d",
                $data['course_id']
            ));
            $data['lesson_order'] = ($max_order ? $max_order + 1 : 1);
        }
        
        $result = $wpdb->insert($this->tables['lessons'], $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update lesson
     */
    public function update_lesson($lesson_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->tables['lessons'],
            $data,
            array('id' => $lesson_id)
        );
    }
    
    /**
     * Delete lesson
     */
    public function delete_lesson($lesson_id) {
        global $wpdb;
        
        // Delete progress records
        $wpdb->delete(
            $this->tables['user_progress'],
            array('lesson_id' => $lesson_id)
        );
        
        // Delete lesson
        return $wpdb->delete(
            $this->tables['lessons'],
            array('id' => $lesson_id)
        );
    }
    
    /**
     * Get lesson by ID
     */
    public function get_lesson($lesson_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} WHERE id = %d",
            $lesson_id
        ));
    }
    
    /**
     * Generate video embed code
     */
    public function generate_video_embed($video_url, $video_type = 'youtube') {
        $embed_html = '';
        
        switch ($video_type) {
            case 'youtube':
                $video_id = $this->extract_youtube_id($video_url);
                if ($video_id) {
                    $embed_html = sprintf(
                        '<div class="clarity-video-wrapper">
                            <iframe 
                                id="clarity-video-player"
                                src="https://www.youtube.com/embed/%s?enablejsapi=1&rel=0&modestbranding=1" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>',
                        esc_attr($video_id)
                    );
                }
                break;
                
            case 'vimeo':
                $video_id = $this->extract_vimeo_id($video_url);
                if ($video_id) {
                    $embed_html = sprintf(
                        '<div class="clarity-video-wrapper">
                            <iframe 
                                src="https://player.vimeo.com/video/%s" 
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>',
                        esc_attr($video_id)
                    );
                }
                break;
                
            case 'custom':
                $embed_html = sprintf(
                    '<div class="clarity-video-wrapper">
                        <video id="clarity-video-player" controls>
                            <source src="%s" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>',
                    esc_url($video_url)
                );
                break;
        }
        
        return $embed_html;
    }
    
    /**
     * Extract YouTube video ID
     */
    private function extract_youtube_id($url) {
        $patterns = array(
            '/youtube\.com\/watch\?v=([^&]+)/',
            '/youtube\.com\/embed\/([^?]+)/',
            '/youtu\.be\/([^?]+)/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    /**
     * Extract Vimeo video ID
     */
    private function extract_vimeo_id($url) {
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return false;
    }
    
    /**
     * Render lesson shortcode
     */
    public function render_lesson_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_navigation' => 'yes',
            'show_progress' => 'yes'
        ), $atts);
        
        if (!$atts['id']) {
            return '<p>No lesson specified.</p>';
        }
        
        $lesson = $this->get_lesson($atts['id']);
        if (!$lesson) {
            return '<p>Lesson not found.</p>';
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please login to view this lesson.</p>';
        }
        
        $course_manager = new Clarity_AWS_GHL_Course_Manager();
        if (!$course_manager->can_access_lesson($user_id, $lesson->id)) {
            return '<p>You do not have access to this lesson. Please complete previous lessons first.</p>';
        }
        
        ob_start();
        $this->render_lesson_player($lesson, $user_id, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render lesson player
     */
    private function render_lesson_player($lesson, $user_id, $options = array()) {
        global $wpdb;
        
        // Get user progress
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['user_progress']} 
            WHERE user_id = %d AND lesson_id = %d",
            $user_id, $lesson->id
        ));
        
        ?>
        <div class="clarity-lesson-player" data-lesson-id="<?php echo $lesson->id; ?>">
            <div class="clarity-lesson-header">
                <h2><?php echo esc_html($lesson->lesson_title); ?></h2>
                <?php if ($lesson->duration_minutes): ?>
                    <span class="clarity-lesson-duration">
                        <i class="dashicons dashicons-clock"></i>
                        <?php echo $lesson->duration_minutes; ?> minutes
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($lesson->lesson_description): ?>
                <div class="clarity-lesson-description">
                    <?php echo wp_kses_post($lesson->lesson_description); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($lesson->video_url): ?>
                <div class="clarity-video-container">
                    <?php echo $this->generate_video_embed($lesson->video_url, $lesson->video_type); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($lesson->lesson_content): ?>
                <div class="clarity-lesson-content">
                    <?php echo wp_kses_post($lesson->lesson_content); ?>
                </div>
            <?php endif; ?>
            
            <div class="clarity-lesson-actions">
                <?php if (!$progress || !$progress->is_completed): ?>
                    <button class="clarity-complete-lesson button button-primary" 
                            data-lesson-id="<?php echo $lesson->id; ?>">
                        <i class="dashicons dashicons-yes-alt"></i>
                        Mark as Complete
                    </button>
                <?php else: ?>
                    <div class="clarity-lesson-completed">
                        <i class="dashicons dashicons-yes"></i>
                        Completed on <?php echo date('F j, Y', strtotime($progress->completion_date)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($options['show_navigation'] === 'yes'): ?>
                <?php $this->render_lesson_navigation($lesson, $user_id); ?>
            <?php endif; ?>
            
            <div class="clarity-lesson-notes">
                <h3>Your Notes</h3>
                <textarea 
                    id="clarity-lesson-notes" 
                    class="clarity-notes-textarea"
                    placeholder="Take notes about this lesson..."
                    data-lesson-id="<?php echo $lesson->id; ?>"><?php 
                    echo $progress ? esc_textarea($progress->notes) : ''; 
                ?></textarea>
                <button class="clarity-save-notes button">Save Notes</button>
            </div>
        </div>
        
        <style>
        .clarity-lesson-player {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .clarity-video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .clarity-video-wrapper iframe,
        .clarity-video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .clarity-lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .clarity-lesson-duration {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
        }
        
        .clarity-lesson-actions {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            text-align: center;
        }
        
        .clarity-complete-lesson {
            font-size: 16px;
            padding: 12px 30px !important;
        }
        
        .clarity-lesson-completed {
            color: #46b450;
            font-size: 16px;
            font-weight: 600;
        }
        
        .clarity-lesson-notes {
            margin-top: 30px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .clarity-notes-textarea {
            width: 100%;
            min-height: 150px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
        }
        
        .clarity-lesson-navigation {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .clarity-nav-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            transition: background 0.3s;
        }
        
        .clarity-nav-button:hover {
            background: #005a87;
            color: white;
        }
        
        .clarity-nav-button.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Mark lesson complete
            $('.clarity-complete-lesson').on('click', function() {
                var button = $(this);
                var lessonId = button.data('lesson-id');
                
                button.prop('disabled', true).text('Marking complete...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mark_lesson_complete',
                        lesson_id: lessonId,
                        nonce: '<?php echo wp_create_nonce('clarity_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            button.replaceWith(
                                '<div class="clarity-lesson-completed">' +
                                '<i class="dashicons dashicons-yes"></i> ' +
                                'Completed just now</div>'
                            );
                            
                            // Refresh navigation if next lesson available
                            if (response.data.next_lesson) {
                                location.reload();
                            }
                        } else {
                            alert('Error: ' + response.data);
                            button.prop('disabled', false).text('Mark as Complete');
                        }
                    }
                });
            });
            
            // Save notes
            $('.clarity-save-notes').on('click', function() {
                var button = $(this);
                var notes = $('#clarity-lesson-notes').val();
                var lessonId = $('#clarity-lesson-notes').data('lesson-id');
                
                button.prop('disabled', true).text('Saving...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'save_lesson_notes',
                        lesson_id: lessonId,
                        notes: notes,
                        nonce: '<?php echo wp_create_nonce('clarity_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            button.text('Saved!');
                            setTimeout(function() {
                                button.prop('disabled', false).text('Save Notes');
                            }, 2000);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render lesson navigation
     */
    private function render_lesson_navigation($current_lesson, $user_id) {
        global $wpdb;
        
        // Get previous lesson
        $prev_lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} 
            WHERE course_id = %d AND lesson_order < %d 
            ORDER BY lesson_order DESC LIMIT 1",
            $current_lesson->course_id, $current_lesson->lesson_order
        ));
        
        // Get next lesson
        $next_lesson = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['lessons']} 
            WHERE course_id = %d AND lesson_order > %d 
            ORDER BY lesson_order ASC LIMIT 1",
            $current_lesson->course_id, $current_lesson->lesson_order
        ));
        
        $course_manager = new Clarity_AWS_GHL_Course_Manager();
        
        ?>
        <div class="clarity-lesson-navigation">
            <?php if ($prev_lesson): ?>
                <a href="?lesson_id=<?php echo $prev_lesson->id; ?>" 
                   class="clarity-nav-button clarity-prev-lesson">
                    <i class="dashicons dashicons-arrow-left-alt"></i>
                    Previous: <?php echo esc_html($prev_lesson->lesson_title); ?>
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            
            <?php if ($next_lesson): ?>
                <?php 
                $can_access_next = $course_manager->can_access_lesson($user_id, $next_lesson->id);
                ?>
                <a href="?lesson_id=<?php echo $next_lesson->id; ?>" 
                   class="clarity-nav-button clarity-next-lesson <?php echo !$can_access_next ? 'disabled' : ''; ?>">
                    Next: <?php echo esc_html($next_lesson->lesson_title); ?>
                    <i class="dashicons dashicons-arrow-right-alt"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX: Save lesson notes
     */
    public function ajax_save_lesson_notes() {
        check_ajax_referer('clarity_ajax_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = intval($_POST['lesson_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (!$user_id) {
            wp_send_json_error('Please login to save notes');
        }
        
        global $wpdb;
        
        // Get lesson for course_id
        $lesson = $this->get_lesson($lesson_id);
        
        // Check if progress record exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['user_progress']} 
            WHERE user_id = %d AND lesson_id = %d",
            $user_id, $lesson_id
        ));
        
        if ($existing) {
            $wpdb->update(
                $this->tables['user_progress'],
                array('notes' => $notes),
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
                    'notes' => $notes
                )
            );
        }
        
        wp_send_json_success('Notes saved');
    }
    
    /**
     * Populate default lessons for a course
     */
    public function populate_default_lessons($course_id, $tier) {
        global $wpdb;
        
        if ($tier == 1) {
            // Clear any existing lessons for this course
            $wpdb->delete($this->tables['lessons'], array('course_id' => $course_id));
            
            // Add default Tier 1 lessons
            foreach ($this->default_tier1_videos as $index => $video) {
                $wpdb->insert($this->tables['lessons'], array(
                    'course_id' => $course_id,
                    'lesson_title' => $video['title'],
                    'lesson_slug' => sanitize_title($video['title']),
                    'lesson_description' => $video['description'],
                    'video_url' => 'https://youtube.com/watch?v=' . $video['youtube_id'],
                    'video_type' => 'youtube',
                    'lesson_order' => $index + 1,
                    'duration_minutes' => $video['duration'],
                    'is_preview' => ($index == 0) ? 1 : 0, // First lesson is preview
                    'created_at' => current_time('mysql')
                ));
            }
            
            // Update course total lessons count
            $wpdb->update($this->tables['courses'], 
                array('total_lessons' => count($this->default_tier1_videos)),
                array('id' => $course_id)
            );
        }
    }
}