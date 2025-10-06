<?php
/**
 * Quick database fix script
 * Add this temporarily to fix the missing certificate_number column
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    $wp_path = dirname(__FILE__);
    require_once($wp_path . '/wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo "<h2>Database Migration Tool</h2>";

// Check if column exists
$enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$enrollments_table} LIKE 'certificate_number'");

if (empty($column_exists)) {
    echo "<p>Adding missing certificate_number column...</p>";
    
    $result = $wpdb->query("ALTER TABLE {$enrollments_table} ADD COLUMN certificate_number varchar(50) DEFAULT NULL AFTER certificate_url");
    
    if ($result !== false) {
        echo "<p style='color: green;'>✅ Successfully added certificate_number column!</p>";
        
        // Also add index
        $wpdb->query("ALTER TABLE {$enrollments_table} ADD INDEX certificate_number (certificate_number)");
        echo "<p style='color: green;'>✅ Added index for certificate_number!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding column: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Certificate_number column already exists.</p>";
}

// Show current table structure
echo "<h3>Current Table Structure:</h3>";
$columns = $wpdb->get_results("DESCRIBE {$enrollments_table}");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Current Enrollments:</h3>";
$enrollments = $wpdb->get_results("SELECT * FROM {$enrollments_table}");
if ($enrollments) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Course ID</th><th>Progress %</th><th>Certificate URL</th><th>Certificate Number</th></tr>";
    foreach ($enrollments as $enrollment) {
        echo "<tr>";
        echo "<td>{$enrollment->id}</td>";
        echo "<td>{$enrollment->user_id}</td>";
        echo "<td>{$enrollment->course_id}</td>";
        echo "<td>{$enrollment->progress_percentage}</td>";
        echo "<td>" . substr($enrollment->certificate_url, 0, 50) . "...</td>";
        echo "<td>{$enrollment->certificate_number}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No enrollments found.</p>";
}

echo "<p><a href='/wp-admin/'>← Back to WordPress Admin</a></p>";
?>