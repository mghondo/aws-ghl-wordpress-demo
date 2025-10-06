<?php
/**
 * Debug certificate database issues
 */

// Load WordPress
require_once(dirname(__FILE__) . '/wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo "<h2>Certificate Database Debug</h2>";

$enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
$user_id = 1; // Your user ID
$course_id = 1; // Course ID

echo "<h3>Current Enrollments for User $user_id:</h3>";
$enrollments = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$enrollments_table} WHERE user_id = %d
", $user_id));

if ($enrollments) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Course ID</th><th>Progress %</th><th>Certificate URL</th><th>Certificate Number</th><th>Status</th></tr>";
    foreach ($enrollments as $enrollment) {
        echo "<tr>";
        echo "<td>{$enrollment->id}</td>";
        echo "<td>{$enrollment->user_id}</td>";
        echo "<td>{$enrollment->course_id}</td>";
        echo "<td>{$enrollment->progress_percentage}</td>";
        echo "<td>" . substr($enrollment->certificate_url ?? 'NULL', 0, 50) . "...</td>";
        echo "<td>{$enrollment->certificate_number}</td>";
        echo "<td>{$enrollment->enrollment_status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No enrollments found for user $user_id</p>";
}

echo "<h3>Test Database Insert:</h3>";

// Test inserting a dummy certificate
$test_result = $wpdb->insert(
    $enrollments_table,
    array(
        'user_id' => $user_id,
        'course_id' => 999, // Use a dummy course ID to avoid conflicts
        'enrollment_date' => current_time('mysql'),
        'completion_date' => current_time('mysql'),
        'progress_percentage' => 100,
        'certificate_issued' => 1,
        'certificate_url' => 'https://test-certificate-url.com',
        'certificate_number' => 'TEST-CERT-123',
        'enrollment_status' => 'active',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ),
    array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
);

if ($test_result === false) {
    echo "<p style='color: red;'>❌ Test insert failed: " . $wpdb->last_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Test insert successful! Insert ID: " . $wpdb->insert_id . "</p>";
    
    // Clean up test record
    $wpdb->delete($enrollments_table, array('id' => $wpdb->insert_id));
    echo "<p>Test record cleaned up.</p>";
}

echo "<h3>Test Database Update for User $user_id, Course $course_id:</h3>";

// Check if enrollment exists
$existing = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$enrollments_table} WHERE user_id = %d AND course_id = %d
", $user_id, $course_id));

if ($existing) {
    echo "<p>Found existing enrollment with ID: {$existing->id}</p>";
    
    // Test update
    $update_result = $wpdb->update(
        $enrollments_table,
        array(
            'certificate_issued' => 1,
            'certificate_url' => 'https://test-update-certificate-url.com',
            'certificate_number' => 'TEST-UPDATE-123',
            'updated_at' => current_time('mysql')
        ),
        array(
            'user_id' => $user_id,
            'course_id' => $course_id
        ),
        array('%d', '%s', '%s', '%s'),
        array('%d', '%d')
    );
    
    if ($update_result === false) {
        echo "<p style='color: red;'>❌ Test update failed: " . $wpdb->last_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Test update successful! Rows affected: $update_result</p>";
    }
} else {
    echo "<p style='color: orange;'>No existing enrollment found for user $user_id, course $course_id</p>";
    echo "<p>This might be why the certificate save is failing.</p>";
}

echo "<p><a href='/wp-admin/'>← Back to WordPress Admin</a></p>";
?>