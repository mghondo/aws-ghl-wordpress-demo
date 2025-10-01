<?php
/**
 * Create Register page in WordPress
 * Run: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/create-register-page.php
 */

require_once('wp-load.php');

echo "=== CREATING REGISTER PAGE ===\n\n";

// Check if Register page already exists
$existing_page = get_page_by_path('register');

if ($existing_page) {
    echo "⚠️  Register page already exists (ID: {$existing_page->ID})\n";
    echo "   Updating existing page...\n";
    
    $page_data = array(
        'ID' => $existing_page->ID,
        'post_title' => 'Register',
        'post_name' => 'register',
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => 'page-register.php'
    );
    
    $page_id = wp_update_post($page_data);
} else {
    echo "Creating new Register page...\n";
    
    $page_data = array(
        'post_title' => 'Register',
        'post_name' => 'register',
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => 'page-register.php'
    );
    
    $page_id = wp_insert_post($page_data);
}

if (is_wp_error($page_id)) {
    echo "✗ Failed to create page: " . $page_id->get_error_message() . "\n";
    exit;
}

// Set the page template
update_post_meta($page_id, '_wp_page_template', 'page-register.php');

echo "✓ Register page created/updated successfully!\n";
echo "   Page ID: $page_id\n";
echo "   URL: " . get_permalink($page_id) . "\n";
echo "   Template: page-register.php\n";

// Verify the page
$created_page = get_post($page_id);
if ($created_page) {
    echo "\n📋 Page Details:\n";
    echo "   Title: {$created_page->post_title}\n";
    echo "   Slug: {$created_page->post_name}\n";
    echo "   Status: {$created_page->post_status}\n";
    echo "   Template: " . get_post_meta($page_id, '_wp_page_template', true) . "\n";
}

// Check if Login page exists for comparison
$login_page = get_page_by_path('login');
if ($login_page) {
    echo "\n✓ Login page exists for comparison:\n";
    echo "   Login URL: " . get_permalink($login_page->ID) . "\n";
    echo "   Login Template: " . get_post_meta($login_page->ID, '_wp_page_template', true) . "\n";
}

echo "\n=== REGISTER PAGE CREATION COMPLETE ===\n";
echo "🎉 You can now access the registration form at: /register\n";
?>