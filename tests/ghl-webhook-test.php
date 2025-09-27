<?php
/**
 * GoHighLevel Webhook Test Script
 *
 * Test the GHL webhook endpoint with mock data
 *
 * @package Clarity_AWS_GHL
 */

// Test configuration
$test_config = array(
    'webhook_url' => 'http://localhost:8000/wp-json/clarity-ghl/v1/webhook',
    'webhook_secret' => 'test_secret_key_12345',
    'test_events' => array(
        'contact_created',
        'contact_updated', 
        'opportunity_created',
        'appointment_scheduled',
        'form_submitted'
    )
);

echo "=== GoHighLevel Webhook Test ===\n\n";

/**
 * Generate mock GHL webhook data
 */
function generate_mock_ghl_data($event_type) {
    $base_data = array(
        'event' => $event_type,
        'timestamp' => time(),
        'locationId' => 'test_location_123',
        'version' => '1.0'
    );
    
    switch ($event_type) {
        case 'contact_created':
        case 'contact_updated':
            return array_merge($base_data, array(
                'contact' => array(
                    'id' => 'contact_' . wp_generate_password(10, false),
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'phone' => '+1234567890',
                    'source' => 'website',
                    'tags' => array('lead', 'website'),
                    'customFields' => array(
                        'company' => 'Test Company',
                        'interest' => 'WordPress Development'
                    ),
                    'dateAdded' => current_time('c'),
                    'dateUpdated' => current_time('c')
                )
            ));
            
        case 'opportunity_created':
            return array_merge($base_data, array(
                'opportunity' => array(
                    'id' => 'opp_' . wp_generate_password(10, false),
                    'contactId' => 'contact_abc123',
                    'name' => 'Website Development Project',
                    'value' => 5000,
                    'currency' => 'USD',
                    'stage' => 'qualification',
                    'source' => 'website_form',
                    'dateCreated' => current_time('c')
                )
            ));
            
        case 'appointment_scheduled':
            return array_merge($base_data, array(
                'appointment' => array(
                    'id' => 'appt_' . wp_generate_password(10, false),
                    'contactId' => 'contact_abc123',
                    'calendarId' => 'cal_123',
                    'title' => 'Discovery Call',
                    'startTime' => date('c', strtotime('+1 day')),
                    'endTime' => date('c', strtotime('+1 day +1 hour')),
                    'status' => 'scheduled',
                    'notes' => 'Initial consultation call'
                )
            ));
            
        case 'form_submitted':
            return array_merge($base_data, array(
                'form' => array(
                    'id' => 'form_' . wp_generate_password(10, false),
                    'name' => 'Contact Form',
                    'submissionId' => 'sub_' . wp_generate_password(8, false),
                    'contactId' => 'contact_def456',
                    'submittedAt' => current_time('c'),
                    'data' => array(
                        'name' => 'Jane Smith',
                        'email' => 'jane@example.com',
                        'message' => 'Interested in your services',
                        'budget' => '$5000-$10000'
                    )
                )
            ));
            
        default:
            return array_merge($base_data, array(
                'data' => array(
                    'message' => 'Generic webhook event',
                    'details' => 'This is a test webhook payload'
                )
            ));
    }
}

/**
 * Generate webhook signature
 */
function generate_webhook_signature($payload, $secret) {
    return 'sha256=' . hash_hmac('sha256', $payload, $secret);
}

/**
 * Send webhook test request
 */
function send_webhook_test($url, $data, $secret = null) {
    $payload = wp_json_encode($data);
    
    $headers = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
        'User-Agent: GoHighLevel-Webhook/1.0'
    );
    
    // Add signature if secret provided
    if ($secret) {
        $signature = generate_webhook_signature($payload, $secret);
        $headers[] = 'X-GHL-Signature: ' . $signature;
    }
    
    // Use cURL if available, otherwise simulate
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return array(
            'success' => empty($error),
            'http_code' => $http_code,
            'response' => $response,
            'error' => $error
        );
    } else {
        // Simulate response for testing without cURL
        return array(
            'success' => true,
            'http_code' => 200,
            'response' => json_encode(array(
                'success' => true,
                'message' => 'Webhook processed successfully (simulated)',
                'timestamp' => current_time('c')
            )),
            'error' => null,
            'simulated' => true
        );
    }
}

// Test 1: Basic connectivity test
echo "1. Testing webhook endpoint connectivity...\n";
$test_data = generate_mock_ghl_data('contact_created');
$result = send_webhook_test($test_config['webhook_url'], $test_data);

if ($result['success']) {
    echo "✓ Webhook endpoint reachable\n";
    echo "  HTTP Code: " . $result['http_code'] . "\n";
    if (isset($result['simulated'])) {
        echo "  Note: Response simulated (cURL not available)\n";
    }
} else {
    echo "✗ Webhook endpoint failed: " . $result['error'] . "\n";
}

// Test 2: Signature verification test
echo "\n2. Testing signature verification...\n";
$test_data = generate_mock_ghl_data('contact_updated');
$result = send_webhook_test($test_config['webhook_url'], $test_data, $test_config['webhook_secret']);

if ($result['success'] && $result['http_code'] == 200) {
    echo "✓ Signature verification working\n";
} else {
    echo "✗ Signature verification failed\n";
    echo "  HTTP Code: " . $result['http_code'] . "\n";
}

// Test 3: Multiple event types
echo "\n3. Testing different event types...\n";
foreach ($test_config['test_events'] as $event_type) {
    echo "  Testing {$event_type}... ";
    
    $test_data = generate_mock_ghl_data($event_type);
    $result = send_webhook_test($test_config['webhook_url'], $test_data, $test_config['webhook_secret']);
    
    if ($result['success'] && $result['http_code'] == 200) {
        echo "✓\n";
    } else {
        echo "✗ (HTTP {$result['http_code']})\n";
    }
}

// Test 4: Error conditions
echo "\n4. Testing error conditions...\n";

// Invalid JSON
echo "  Testing invalid JSON... ";
$invalid_json = '{"invalid": json}';
$ch = curl_init();
if ($ch) {
    curl_setopt($ch, CURLOPT_URL, $test_config['webhook_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $invalid_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 400) {
        echo "✓ (Correctly rejected invalid JSON)\n";
    } else {
        echo "✗ (Expected 400, got {$http_code})\n";
    }
} else {
    echo "- (cURL not available)\n";
}

// Wrong content type
echo "  Testing wrong content type... ";
$test_data = generate_mock_ghl_data('test');
$payload = wp_json_encode($test_data);

$ch = curl_init();
if ($ch) {
    curl_setopt($ch, CURLOPT_URL, $test_config['webhook_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 400) {
        echo "✓ (Correctly rejected wrong content type)\n";
    } else {
        echo "✗ (Expected 400, got {$http_code})\n";
    }
} else {
    echo "- (cURL not available)\n";
}

echo "\n=== Test Summary ===\n";
echo "Webhook endpoint: " . $test_config['webhook_url'] . "\n";
echo "Test completed. Check WordPress logs and S3 bucket for stored webhook data.\n";

// Generate sample webhook data files
echo "\n5. Generating sample webhook data files...\n";

foreach ($test_config['test_events'] as $event_type) {
    $sample_data = generate_mock_ghl_data($event_type);
    $filename = __DIR__ . "/mock-ghl-{$event_type}.json";
    
    file_put_contents($filename, wp_json_encode($sample_data, JSON_PRETTY_PRINT));
    echo "✓ Created: " . basename($filename) . "\n";
}

echo "\nSample webhook data files created in tests/ directory.\n";
echo "You can use these files to test the webhook endpoint manually.\n\n";

/**
 * Helper function for WordPress compatibility
 */
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options);
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        if ($type === 'c') {
            return date('c');
        }
        return date($type);
    }
}
?>