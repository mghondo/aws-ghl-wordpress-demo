<?php
/**
 * Setup funnel page and flush rewrite rules
 */
require_once('wp-load.php');

echo "Setting up funnel page and rewrite rules...\n";

// Create funnel page if it doesn't exist
$page_slug = 'funnel';
$page = get_page_by_path($page_slug);

if (!$page) {
    $page_data = array(
        'post_title' => 'Course Funnel',
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => $page_slug,
        'post_author' => 1
    );
    
    $page_id = wp_insert_post($page_data);
    
    if ($page_id) {
        echo "✅ Funnel page created successfully (ID: $page_id)\n";
    } else {
        echo "❌ Failed to create funnel page\n";
    }
} else {
    echo "✅ Funnel page already exists (ID: {$page->ID})\n";
}

// Add rewrite rules
add_rewrite_rule(
    '^funnel/([^/]+)/?$',
    'index.php?pagename=funnel&course_slug=$matches[1]',
    'top'
);

// Flush rewrite rules
flush_rewrite_rules();
echo "✅ Rewrite rules flushed\n";

// Test the rewrite rules
$test_urls = array(
    '/funnel/real-estate-foundations',
    '/funnel/real-estate-mastery',
    '/funnel/elite-empire-builder'
);

echo "\n📋 Test URLs generated:\n";
foreach ($test_urls as $url) {
    echo "   - " . home_url() . $url . "\n";
}

echo "\n🎯 You can now test funnel pages!\n";
echo "💡 Example: Visit " . home_url('/funnel/real-estate-foundations') . "\n";
?>