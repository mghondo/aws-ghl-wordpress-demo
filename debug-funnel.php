<?php
/**
 * Debug funnel content display
 */
require_once('wp-load.php');

echo "=== DEBUGGING FUNNEL CONTENT ===\n\n";

global $wpdb;
$courses_table = $wpdb->prefix . 'clarity_courses';

// Get course data
$course = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$courses_table} WHERE id = %d",
    1 // Real Estate Foundations
));

if (!$course) {
    echo "❌ Course not found\n";
    exit;
}

echo "✅ Course found: {$course->course_title}\n";
echo "📝 Course slug: {$course->course_slug}\n";

// Check funnel content
echo "\n=== FUNNEL CONTENT DEBUG ===\n";
echo "Funnel content exists: " . (!empty($course->funnel_content) ? 'YES' : 'NO') . "\n";

if (!empty($course->funnel_content)) {
    echo "Content length: " . strlen($course->funnel_content) . " characters\n";
    echo "First 200 characters:\n";
    echo substr($course->funnel_content, 0, 200) . "...\n";
} else {
    echo "❌ Funnel content is EMPTY\n";
}

// Test the exact condition used in template
echo "\n=== TEMPLATE CONDITION TEST ===\n";
echo "empty(\$course->funnel_content): " . (empty($course->funnel_content) ? 'TRUE' : 'FALSE') . "\n";
echo "!empty(\$course->funnel_content): " . (!empty($course->funnel_content) ? 'TRUE' : 'FALSE') . "\n";

// Check what wp_kses_post would output
if (!empty($course->funnel_content)) {
    echo "\n=== WP_KSES_POST OUTPUT ===\n";
    $sanitized = wp_kses_post($course->funnel_content);
    echo "Sanitized length: " . strlen($sanitized) . " characters\n";
    echo "Sanitized content preview:\n";
    echo substr($sanitized, 0, 300) . "...\n";
}

// Test the funnel URL
echo "\n=== FUNNEL URL TEST ===\n";
echo "Funnel URL: " . home_url('/funnel/' . $course->course_slug) . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
?>