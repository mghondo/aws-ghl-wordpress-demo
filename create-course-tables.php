<?php
/**
 * One-time script to create course tables and populate with default data
 * Run this by visiting: http://localhost:8080/create-course-tables.php
 */

// WordPress environment
require_once('wp-config.php');
require_once('wp-includes/wp-db.php');

// Include our course classes
require_once('wp-content/plugins/clarity-aws-ghl-plugin/includes/class-database-courses.php');
require_once('wp-content/plugins/clarity-aws-ghl-plugin/includes/class-lesson-handler.php');

echo "<h1>Creating Course Tables and Default Content</h1>";

try {
    // Create the course database tables
    $db_courses = new Clarity_AWS_GHL_Database_Courses();
    $db_courses->create_course_tables();
    
    echo "<p>✅ Course tables created successfully!</p>";
    
    // Create default lessons for Tier 1 course
    global $wpdb;
    $tables = $db_courses->get_table_names();
    
    // Get the Tier 1 course ID
    $tier1_course = $wpdb->get_row("SELECT * FROM {$tables['courses']} WHERE course_tier = 1 LIMIT 1");
    
    if ($tier1_course) {
        $lesson_handler = new Clarity_AWS_GHL_Lesson_Handler();
        $lesson_handler->populate_default_lessons($tier1_course->id, 1);
        
        echo "<p>✅ Default Alex Hormozi videos added to Tier 1 course!</p>";
    }
    
    echo "<h2>Course System Ready!</h2>";
    echo "<p><a href='/wp-admin/admin.php?page=clarity-aws-ghl-courses'>Go to Course Management</a></p>";
    
    // Show what was created
    echo "<h3>Courses Created:</h3>";
    $courses = $wpdb->get_results("SELECT * FROM {$tables['courses']} ORDER BY course_tier");
    foreach ($courses as $course) {
        echo "<p><strong>Tier {$course->course_tier}:</strong> {$course->course_title} (\${$course->course_price})</p>";
    }
    
    echo "<h3>Lessons Created:</h3>";
    $lessons = $wpdb->get_results("
        SELECT l.*, c.course_title 
        FROM {$tables['lessons']} l 
        JOIN {$tables['courses']} c ON l.course_id = c.id 
        ORDER BY c.course_tier, l.lesson_order
    ");
    
    foreach ($lessons as $lesson) {
        echo "<p><strong>{$lesson->course_title}</strong> - Lesson {$lesson->lesson_order}: {$lesson->lesson_title}</p>";
        if ($lesson->video_url) {
            echo "<p style='margin-left: 20px; color: #666;'>Video: {$lesson->video_url}</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><em>You can delete this file after running it once.</em></p>";
?>