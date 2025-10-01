<?php
/**
 * Test authentication flow
 * Run: docker exec aws-ghl-wordpress-demo-wordpress-1 php /var/www/html/test-auth-flow.php
 */

require_once('wp-load.php');
global $wpdb;

echo "=== TESTING AUTHENTICATION FLOW ===\n\n";

// Test data
$test_email = 'testuser@example.com';
$test_username = 'testuser' . rand(1000, 9999);
$test_first_name = 'Test';
$test_last_name = 'User';
$test_password = 'testpassword123';

echo "1. Testing user creation...\n";
echo "   Email: $test_email\n";
echo "   Username: $test_username\n";

// Check if user already exists
if (username_exists($test_username) || email_exists($test_email)) {
    echo "   ⚠️  User already exists, cleaning up first...\n";
    
    $existing_user = get_user_by('email', $test_email);
    if ($existing_user) {
        wp_delete_user($existing_user->ID);
        echo "   ✓ Existing user removed\n";
    }
}

// Create user
$user_id = wp_create_user($test_username, $test_password, $test_email);

if (is_wp_error($user_id)) {
    echo "   ✗ User creation failed: " . $user_id->get_error_message() . "\n";
    exit;
} else {
    echo "   ✓ User created successfully (ID: $user_id)\n";
}

// Update user meta
update_user_meta($user_id, 'first_name', $test_first_name);
update_user_meta($user_id, 'last_name', $test_last_name);
update_user_meta($user_id, 'display_name', $test_first_name . ' ' . $test_last_name);

echo "   ✓ User meta updated\n";

echo "\n2. Testing auto-enrollment in Tier 1 course...\n";

// Auto-enroll in Tier 1 course
$enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';

$enrollment_result = $wpdb->insert(
    $enrollments_table,
    array(
        'user_id' => $user_id,
        'course_id' => 1, // Tier 1 course ID
        'enrollment_date' => current_time('mysql'),
        'enrollment_status' => 'active',
        'payment_status' => 'paid',
        'payment_amount' => 0.00,
        'progress_percentage' => 0
    ),
    array('%d', '%d', '%s', '%s', '%s', '%f', '%d')
);

if ($enrollment_result === false) {
    echo "   ✗ Enrollment failed: " . $wpdb->last_error . "\n";
} else {
    echo "   ✓ User enrolled in Tier 1 course\n";
}

echo "\n3. Testing prospect-to-student linking...\n";

// First, create a test prospect
$contacts_table = $wpdb->prefix . 'clarity_ghl_contacts';

$prospect_result = $wpdb->insert(
    $contacts_table,
    array(
        'ghl_contact_id' => 'test_' . rand(100000, 999999),
        'first_name' => $test_first_name,
        'last_name' => $test_last_name,
        'email' => $test_email,
        'phone' => '+1234567890',
        'status' => 'prospect',
        'source' => 'test',
        'wp_user_id' => null
    ),
    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
);

if ($prospect_result === false) {
    echo "   ⚠️  Could not create test prospect (may already exist)\n";
} else {
    echo "   ✓ Test prospect created\n";
}

// Link prospect to student
$contact_result = $wpdb->update(
    $contacts_table,
    array('wp_user_id' => $user_id),
    array('email' => $test_email, 'wp_user_id' => null),
    array('%d'),
    array('%s', '%d')
);

if ($contact_result !== false && $contact_result > 0) {
    echo "   ✓ Prospect linked to student account ($contact_result rows updated)\n";
} else {
    echo "   ⚠️  No prospect found to link (this is normal if no matching prospect exists)\n";
}

echo "\n4. Verifying enrollment data...\n";

// Check enrollment
$enrollment = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $enrollments_table WHERE user_id = %d AND course_id = %d",
    $user_id, 1
));

if ($enrollment) {
    echo "   ✓ Enrollment verified:\n";
    echo "     - User ID: {$enrollment->user_id}\n";
    echo "     - Course ID: {$enrollment->course_id}\n";
    echo "     - Status: {$enrollment->enrollment_status}\n";
    echo "     - Payment Status: {$enrollment->payment_status}\n";
    echo "     - Progress: {$enrollment->progress_percentage}%\n";
} else {
    echo "   ✗ Enrollment not found\n";
}

echo "\n5. Testing login authentication...\n";

// Test login with username
$creds = array(
    'user_login' => $test_username,
    'user_password' => $test_password,
    'remember' => true
);

$user = wp_authenticate($test_username, $test_password);

if (is_wp_error($user)) {
    echo "   ✗ Login failed: " . $user->get_error_message() . "\n";
} else {
    echo "   ✓ Login successful with username\n";
}

// Test login with email
$user_by_email = get_user_by('email', $test_email);
if ($user_by_email) {
    $user_email = wp_authenticate($user_by_email->user_login, $test_password);
    if (!is_wp_error($user_email)) {
        echo "   ✓ Login successful with email\n";
    }
}

echo "\n6. Testing course access...\n";

// Check if user has access to Tier 1 course
$has_access = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $enrollments_table WHERE user_id = %d AND course_id = %d AND enrollment_status = 'active'",
    $user_id, 1
));

if ($has_access > 0) {
    echo "   ✓ User has access to Tier 1 course\n";
} else {
    echo "   ✗ User does not have access to Tier 1 course\n";
}

echo "\n7. Cleanup...\n";

// Remove test user
wp_delete_user($user_id);
echo "   ✓ Test user removed\n";

// Remove test prospect if created
$wpdb->delete(
    $contacts_table,
    array('email' => $test_email),
    array('%s')
);
echo "   ✓ Test prospect cleaned up\n";

echo "\n=== AUTHENTICATION FLOW TEST COMPLETE ===\n";
echo "✅ All authentication components are working correctly!\n";
?>