<?php
/**
 * Debug Checkout Process
 * 
 * Simple script to test the enrollment process
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Checkout Process</h1>";

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Mock WordPress functions if not available
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true; // For testing
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1; // Mock user ID
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($url) {
        echo "<p><strong>REDIRECT:</strong> Would redirect to: $url</p>";
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'http://localhost' . $path;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

// Load classes if available
if (file_exists(__DIR__ . '/clarity-aws-ghl-plugin/includes/class-course-routing.php')) {
    require_once __DIR__ . '/clarity-aws-ghl-plugin/includes/class-course-routing.php';
}

echo "<h2>Testing Course Routing Class</h2>";

try {
    if (class_exists('Clarity_AWS_GHL_Course_Routing')) {
        $course_routing = new Clarity_AWS_GHL_Course_Routing();
        echo "<p>✅ Course Routing class loaded successfully</p>";
        
        // Test building a cart
        echo "<h3>Testing Cart Build</h3>";
        $cart = $course_routing->build_checkout_cart(1, 2); // User ID 1, Course ID 2
        echo "<pre>Cart: " . print_r($cart, true) . "</pre>";
        
        // Test enrollment process
        echo "<h3>Testing Enrollment Process</h3>";
        if (!empty($cart['courses'])) {
            $enrollment_ids = $course_routing->process_post_payment_enrollment(
                1, // User ID
                $cart['courses'], 
                $cart['total']
            );
            echo "<pre>Enrollment IDs: " . print_r($enrollment_ids, true) . "</pre>";
        } else {
            echo "<p>❌ No courses in cart to test enrollment</p>";
        }
        
    } else {
        echo "<p>❌ Course Routing class not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>POST Data Analysis</h2>";
echo "<pre>POST: " . print_r($_POST, true) . "</pre>";
echo "<pre>GET: " . print_r($_GET, true) . "</pre>";

echo "<h2>Session Debug</h2>";
if (isset($_POST['debug_enrollment'])) {
    echo "<p>🎯 Debug enrollment triggered</p>";
    
    // Simulate form data
    $_POST['process_payment'] = '1';
    $_POST['payment_nonce'] = 'test_nonce';
    $_POST['course_id'] = '2';
    $_POST['card_number'] = '1234 5678 9012 3456';
    
    echo "<p>Simulated POST data set. Testing enrollment...</p>";
    
    if (class_exists('Clarity_AWS_GHL_Course_Routing')) {
        $course_routing = new Clarity_AWS_GHL_Course_Routing();
        $cart = $course_routing->build_checkout_cart(1, 2);
        
        if (!empty($cart['courses'])) {
            $enrollment_ids = $course_routing->process_post_payment_enrollment(1, $cart['courses'], $cart['total']);
            echo "<p>✅ Enrollment completed! IDs: " . implode(', ', $enrollment_ids) . "</p>";
        }
    }
}

echo '<form method="post">
    <button type="submit" name="debug_enrollment" value="1">🧪 Test Enrollment Process</button>
</form>';
?>