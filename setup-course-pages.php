<?php
/**
 * Setup Course Pages in WordPress
 * 
 * Run this script to automatically create the course page and set up routing
 * Usage: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/setup-course-pages.php
 */

// Load WordPress
require_once('wp-load.php');

echo "=== Setting up Course Pages ===\n\n";

// 1. Create the main Course page
echo "1. Creating Course page...\n";

$course_page = get_page_by_path('course');

if (!$course_page) {
    $page_data = array(
        'post_title'    => 'Course',
        'post_name'     => 'course',
        'post_content'  => '<!-- Course content is handled by the template -->',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => 1,
        'comment_status' => 'closed',
        'ping_status'   => 'closed',
        'page_template' => 'page-course.php'
    );
    
    $page_id = wp_insert_post($page_data);
    
    if (!is_wp_error($page_id)) {
        // Set the page template
        update_post_meta($page_id, '_wp_page_template', 'page-course.php');
        echo "✓ Course page created with ID: $page_id\n";
    } else {
        echo "✗ Error creating page: " . $page_id->get_error_message() . "\n";
    }
} else {
    echo "✓ Course page already exists with ID: " . $course_page->ID . "\n";
    // Ensure template is set
    update_post_meta($course_page->ID, '_wp_page_template', 'page-course.php');
}

// 2. Set up permalinks
echo "\n2. Setting up permalinks...\n";

// Update permalink structure to /%postname%/
update_option('permalink_structure', '/%postname%/');
echo "✓ Permalink structure set to /%postname%/\n";

// 3. Add rewrite rules for course URLs
echo "\n3. Adding course rewrite rules...\n";

// Add custom rewrite rules
add_rewrite_rule(
    '^course/([^/]+)/?$',
    'index.php?pagename=course&course_slug=$matches[1]',
    'top'
);

// Add course_slug as a query var
global $wp;
$wp->add_query_var('course_slug');

// Flush rewrite rules
flush_rewrite_rules();
echo "✓ Rewrite rules added and flushed\n";

// 4. Create individual course pages (optional but helpful)
echo "\n4. Creating individual course shortcut pages...\n";

$courses = array(
    array(
        'title' => 'Real Estate Foundations',
        'slug' => 'real-estate-foundations',
        'parent_id' => $course_page ? $course_page->ID : 0
    ),
    array(
        'title' => 'Real Estate Mastery',
        'slug' => 'real-estate-mastery',
        'parent_id' => $course_page ? $course_page->ID : 0
    ),
    array(
        'title' => 'Elite Empire Builder',
        'slug' => 'elite-empire-builder',
        'parent_id' => $course_page ? $course_page->ID : 0
    )
);

foreach ($courses as $course) {
    $existing = get_page_by_path('course/' . $course['slug'], OBJECT, 'page');
    
    if (!$existing) {
        $page_data = array(
            'post_title'    => $course['title'],
            'post_name'     => $course['slug'],
            'post_content'  => '<!-- This page redirects to the course viewer -->',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'post_parent'   => $course['parent_id'],
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
            'page_template' => 'page-course.php'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-course.php');
            echo "✓ Created page for: " . $course['title'] . "\n";
        } else {
            echo "✗ Error creating page for " . $course['title'] . ": " . $page_id->get_error_message() . "\n";
        }
    } else {
        echo "✓ Page already exists for: " . $course['title'] . "\n";
    }
}

// 5. Display available URLs
echo "\n=== Setup Complete! ===\n\n";
echo "You can now access courses at these URLs:\n\n";

$site_url = get_site_url();
echo "Main course page:\n";
echo "  $site_url/course/\n\n";

echo "Individual courses:\n";
echo "  $site_url/course/?course=real-estate-foundations\n";
echo "  $site_url/course/?course=real-estate-mastery\n";
echo "  $site_url/course/?course=elite-empire-builder\n\n";

// If individual pages were created
if (get_option('permalink_structure')) {
    echo "Or with pretty permalinks:\n";
    echo "  $site_url/course/real-estate-foundations/\n";
    echo "  $site_url/course/real-estate-mastery/\n";
    echo "  $site_url/course/elite-empire-builder/\n\n";
}

echo "=== Done! ===\n";
?>