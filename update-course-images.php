<?php
/**
 * Update courses with featured images
 * Run: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/update-course-images.php
 */

require_once('wp-load.php');
global $wpdb;

echo "=== UPDATING COURSE FEATURED IMAGES ===\n\n";

$courses_table = $wpdb->prefix . 'clarity_courses';

// Sample image URLs - you can replace these with actual images
$course_images = array(
    'real-estate-foundations' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1920&h=400&fit=crop',
    'real-estate-mastery' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=400&fit=crop',  
    'elite-empire-builder' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1920&h=400&fit=crop'
);

foreach ($course_images as $slug => $image_url) {
    $result = $wpdb->update(
        $courses_table,
        array('featured_image' => $image_url),
        array('course_slug' => $slug),
        array('%s'),
        array('%s')
    );
    
    if ($result !== false) {
        echo "✓ Updated image for: $slug\n";
        echo "  Image: $image_url\n\n";
    } else {
        echo "✗ Failed to update: $slug\n\n";
    }
}

// Show current course data
echo "Current course featured images:\n";
$courses = $wpdb->get_results("SELECT course_title, course_slug, featured_image FROM $courses_table");
foreach ($courses as $course) {
    echo "- {$course->course_title}:\n";
    echo "  Slug: {$course->course_slug}\n";
    echo "  Image: " . ($course->featured_image ?: 'None') . "\n\n";
}

echo "=== DONE ===\n";
?>