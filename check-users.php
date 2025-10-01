<?php
/**
 * Check existing users in WordPress
 */

require_once('wp-load.php');

echo "=== WordPress Users ===\n\n";

$users = get_users(array(
    'orderby' => 'ID',
    'order' => 'ASC'
));

if (empty($users)) {
    echo "No users found.\n";
} else {
    foreach ($users as $user) {
        echo "ID: {$user->ID}\n";
        echo "Username: {$user->user_login}\n";
        echo "Email: {$user->user_email}\n";
        echo "Display Name: {$user->display_name}\n";
        echo "Roles: " . implode(', ', $user->roles) . "\n";
        echo "-------------------\n";
    }
}

echo "\nTotal users: " . count($users) . "\n";

// Check if there are any mock/test users in our custom tables
global $wpdb;

echo "\n=== Course Enrollments ===\n";
$enrollments = $wpdb->get_results("SELECT DISTINCT user_id FROM wp_clarity_course_enrollments");
if ($enrollments) {
    echo "User IDs with enrollments: ";
    foreach ($enrollments as $enrollment) {
        echo $enrollment->user_id . " ";
    }
    echo "\n";
} else {
    echo "No enrollments found.\n";
}

echo "\n=== User Progress Records ===\n";
$progress = $wpdb->get_results("SELECT DISTINCT user_id FROM wp_clarity_user_progress");
if ($progress) {
    echo "User IDs with progress: ";
    foreach ($progress as $p) {
        echo $p->user_id . " ";
    }
    echo "\n";
} else {
    echo "No progress records found.\n";
}
?>