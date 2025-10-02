<?php
/**
 * Create Dashboard page in WordPress
 * Run: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/create-dashboard-page.php
 */

require_once('wp-load.php');

echo "=== CREATING DASHBOARD PAGE ===\n\n";

// Check if Dashboard page already exists
$existing_page = get_page_by_path('dashboard');

if ($existing_page) {
    echo "⚠️  Dashboard page already exists (ID: {$existing_page->ID})\n";
    echo "   Updating existing page...\n";
    
    $page_data = array(
        'ID' => $existing_page->ID,
        'post_title' => 'Dashboard',
        'post_name' => 'dashboard',
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => 'page-dashboard.php'
    );
    
    $page_id = wp_update_post($page_data);
} else {
    echo "Creating new Dashboard page...\n";
    
    $page_data = array(
        'post_title' => 'Dashboard',
        'post_name' => 'dashboard',
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => 'page-dashboard.php'
    );
    
    $page_id = wp_insert_post($page_data);
}

if (is_wp_error($page_id)) {
    echo "✗ Failed to create page: " . $page_id->get_error_message() . "\n";
    exit;
}

// Set the page template
update_post_meta($page_id, '_wp_page_template', 'page-dashboard.php');

echo "✓ Dashboard page created/updated successfully!\n";
echo "   Page ID: $page_id\n";
echo "   URL: " . get_permalink($page_id) . "\n";
echo "   Template: page-dashboard.php\n";

// Verify the page
$created_page = get_post($page_id);
if ($created_page) {
    echo "\n📋 Page Details:\n";
    echo "   Title: {$created_page->post_title}\n";
    echo "   Slug: {$created_page->post_name}\n";
    echo "   Status: {$created_page->post_status}\n";
    echo "   Template: " . get_post_meta($page_id, '_wp_page_template', true) . "\n";
}

echo "\n=== DASHBOARD PAGE CREATION COMPLETE ===\n";
echo "🎉 Students can now access their dashboard at: /dashboard\n";
?>