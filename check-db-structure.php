<?php
/**
 * Check actual database structure and user data
 */

require_once('wp-load.php');
global $wpdb;

echo "=== DATABASE STRUCTURE & USER DATA ===\n\n";

// 1. Show all tables with 'clarity' or 'user' in name
echo "1. Relevant Database Tables:\n";
$tables = $wpdb->get_results("SHOW TABLES LIKE '%clarity%'");
foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    echo "   - $table_name\n";
}

$user_tables = $wpdb->get_results("SHOW TABLES LIKE '%user%'");
foreach ($user_tables as $table) {
    $table_name = array_values((array)$table)[0];
    if (strpos($table_name, 'clarity') === false) {
        echo "   - $table_name\n";
    }
}
echo "\n";

// 2. Show structure of each clarity table
$clarity_tables = ['wp_clarity_courses', 'wp_clarity_lessons', 'wp_clarity_course_enrollments', 'wp_clarity_user_progress'];

foreach ($clarity_tables as $table) {
    echo "2. Structure of $table:\n";
    $structure = $wpdb->get_results("DESCRIBE $table");
    foreach ($structure as $column) {
        echo "   - {$column->Field}: {$column->Type} " . 
             ($column->Null == 'YES' ? '(nullable)' : '(required)') . 
             ($column->Default ? " default: {$column->Default}" : '') . "\n";
    }
    echo "\n";
}

// 3. Show actual data for user ID 1
$user_id = 1;
echo "3. ACTUAL DATA for User ID $user_id:\n\n";

// WordPress user data
echo "WordPress User (wp_users):\n";
$user_data = $wpdb->get_row("SELECT * FROM wp_users WHERE ID = $user_id");
if ($user_data) {
    foreach ($user_data as $key => $value) {
        echo "   - $key: " . ($value ?: 'NULL') . "\n";
    }
} else {
    echo "   - No user found\n";
}
echo "\n";

// User meta data
echo "WordPress User Meta (wp_usermeta):\n";
$user_meta = $wpdb->get_results("SELECT meta_key, meta_value FROM wp_usermeta WHERE user_id = $user_id");
if ($user_meta) {
    foreach ($user_meta as $meta) {
        echo "   - {$meta->meta_key}: {$meta->meta_value}\n";
    }
} else {
    echo "   - No user meta found\n";
}
echo "\n";

// Course enrollments
echo "Course Enrollments (wp_clarity_course_enrollments):\n";
$enrollments = $wpdb->get_results("SELECT * FROM wp_clarity_course_enrollments WHERE user_id = $user_id");
if ($enrollments) {
    foreach ($enrollments as $enrollment) {
        echo "   Enrollment ID {$enrollment->id}:\n";
        foreach ($enrollment as $key => $value) {
            echo "     - $key: " . ($value ?: 'NULL') . "\n";
        }
        echo "\n";
    }
} else {
    echo "   - No enrollments found\n";
}
echo "\n";

// User progress
echo "User Progress (wp_clarity_user_progress):\n";
$progress_records = $wpdb->get_results("SELECT * FROM wp_clarity_user_progress WHERE user_id = $user_id");
if ($progress_records) {
    foreach ($progress_records as $progress) {
        echo "   Progress ID {$progress->id}:\n";
        foreach ($progress as $key => $value) {
            echo "     - $key: " . ($value ?: 'NULL') . "\n";
        }
        echo "\n";
    }
} else {
    echo "   - No progress records found\n";
}

// 4. Show what variables are available when course page loads
echo "4. VARIABLES AVAILABLE ON COURSE PAGE:\n";
echo "Based on current code, these variables are set:\n";
echo "   - \$user_id: $user_id (from get_current_user_id())\n";
echo "   - \$is_enrolled: boolean (from get_user_enrollment())\n";
echo "   - \$enrollment: object|null (enrollment record)\n";
echo "   - \$user_progress: array (from get_user_course_progress())\n";
echo "   - \$completed_lessons: integer (count from progress)\n";
echo "   - \$lessons: array (from get_course_lessons())\n";
echo "   - \$total_lessons: integer (count of lessons)\n";
echo "   - \$progress_percentage: integer (0-100)\n";
echo "   - \$is_admin: boolean (from current_user_can('manage_options'))\n";
echo "\n";

echo "=== END DATABASE ANALYSIS ===\n";
?>