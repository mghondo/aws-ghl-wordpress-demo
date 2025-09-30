<?php
/**
 * Course viewer redirect
 * 
 * This file handles /course/ URLs and loads the appropriate WordPress template
 */

// Load WordPress
require_once('wp-load.php');

// Get the course slug from URL
$request_uri = $_SERVER['REQUEST_URI'];
$course_slug = '';

// Extract course slug from URL patterns like /course/slug/
if (preg_match('/\/course\/([^\/\?]+)/', $request_uri, $matches)) {
    $course_slug = $matches[1];
}

// Set up query vars for the template
$_GET['course'] = $course_slug;

// Set up WordPress query
global $wp_query;
$wp_query = new WP_Query(array(
    'pagename' => 'course',
    'post_type' => 'page'
));

// Load the course template directly
$template = get_template_directory() . '/page-course.php';

if (file_exists($template)) {
    // Set up the global post object
    $page = get_page_by_path('course');
    if (!$page) {
        // Create a virtual page object if the page doesn't exist
        $page = new stdClass();
        $page->ID = -1;
        $page->post_title = 'Course';
        $page->post_name = 'course';
        $page->post_type = 'page';
        $page->post_status = 'publish';
        $page->comment_status = 'closed';
        $page->ping_status = 'closed';
        $page->post_content = '';
    }
    
    $GLOBALS['post'] = $page;
    setup_postdata($page);
    
    // Include the template
    include($template);
} else {
    // Fallback error
    wp_die('Course template not found. Please ensure page-course.php exists in your theme directory.');
}
?>