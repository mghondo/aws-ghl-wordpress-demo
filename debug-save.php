<?php
/**
 * Debug funnel content save process
 */
require_once('wp-load.php');

echo "=== DEBUGGING SAVE PROCESS ===\n\n";

global $wpdb;
$courses_table = $wpdb->prefix . 'clarity_courses';

// Get current content
$course = $wpdb->get_row($wpdb->prepare(
    "SELECT funnel_content FROM {$courses_table} WHERE id = %d",
    1
));

echo "Current raw content in database:\n";
echo "Length: " . strlen($course->funnel_content) . " characters\n";
echo "Raw content:\n";
echo $course->funnel_content . "\n\n";

echo "After wp_unslash:\n";
$unslashed = wp_unslash($course->funnel_content);
echo $unslashed . "\n\n";

echo "After stripslashes:\n";
$stripped = stripslashes($course->funnel_content);
echo $stripped . "\n\n";

// Test different methods
echo "=== TESTING FIXES ===\n";
echo "1. html_entity_decode:\n";
echo html_entity_decode($course->funnel_content) . "\n\n";

echo "2. htmlspecialchars_decode:\n";
echo htmlspecialchars_decode($course->funnel_content) . "\n\n";

echo "3. Multiple stripslashes:\n";
echo stripslashes(stripslashes($course->funnel_content)) . "\n\n";
?>