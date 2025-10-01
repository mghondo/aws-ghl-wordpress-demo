<?php
/**
 * Verify both Login and Register pages exist
 * Run: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/verify-pages.php
 */

require_once('wp-load.php');

echo "=== VERIFYING AUTHENTICATION PAGES ===\n\n";

// Get all pages
$pages = get_pages(array(
    'post_status' => 'publish',
    'sort_column' => 'post_title'
));

echo "📋 All Published Pages:\n";
foreach ($pages as $page) {
    $template = get_post_meta($page->ID, '_wp_page_template', true);
    $template_display = $template ? $template : 'default';
    
    echo "   • {$page->post_title} (/{$page->post_name}/) - Template: {$template_display}\n";
}

echo "\n🔐 Authentication Pages Status:\n";

// Check Login page
$login_page = get_page_by_path('login');
if ($login_page) {
    echo "   ✅ Login Page:\n";
    echo "      - ID: {$login_page->ID}\n";
    echo "      - URL: " . get_permalink($login_page->ID) . "\n";
    echo "      - Status: {$login_page->post_status}\n";
    echo "      - Template: " . get_post_meta($login_page->ID, '_wp_page_template', true) . "\n";
} else {
    echo "   ❌ Login page not found\n";
}

echo "\n";

// Check Register page
$register_page = get_page_by_path('register');
if ($register_page) {
    echo "   ✅ Register Page:\n";
    echo "      - ID: {$register_page->ID}\n";
    echo "      - URL: " . get_permalink($register_page->ID) . "\n";
    echo "      - Status: {$register_page->post_status}\n";
    echo "      - Template: " . get_post_meta($register_page->ID, '_wp_page_template', true) . "\n";
} else {
    echo "   ❌ Register page not found\n";
}

echo "\n🎯 Ready for Testing:\n";
echo "   • Registration: http://localhost:8080/register/\n";
echo "   • Login: http://localhost:8080/login/\n";
echo "   • Course Access: http://localhost:8080/course/real-estate-foundations/\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
?>