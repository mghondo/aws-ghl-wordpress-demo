<?php
/**
 * AWS S3 Integration Test Script
 *
 * Test script to validate S3 bucket setup and WordPress integration
 * Run this script after setting up AWS credentials
 *
 * @package Clarity_AWS_GHL
 */

// Simulate WordPress environment for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../wordpress-theme/');
}

// Test configuration
$test_config = array(
    'bucket_name' => 'clarity-aws-ghl-demo-storage',
    'region' => 'us-east-1',
    'test_file' => __DIR__ . '/test-upload.txt',
    'test_content' => 'This is a test file for S3 integration validation.',
);

echo "=== AWS S3 Integration Test ===\n\n";

// Create test file
file_put_contents($test_config['test_file'], $test_config['test_content']);
echo "✓ Test file created: " . $test_config['test_file'] . "\n";

// Test 1: Check if AWS SDK is available
echo "\n1. Checking AWS SDK availability...\n";
if (class_exists('Aws\S3\S3Client')) {
    echo "✓ AWS SDK is available\n";
} else {
    echo "✗ AWS SDK not found. Install via: composer require aws/aws-sdk-php\n";
}

// Test 2: Validate AWS credentials
echo "\n2. Validating AWS credentials...\n";
$access_key = getenv('AWS_S3_ACCESS_KEY_ID') ?: 'not_set';
$secret_key = getenv('AWS_S3_SECRET_ACCESS_KEY') ?: 'not_set';

if ($access_key !== 'not_set' && $secret_key !== 'not_set') {
    echo "✓ AWS credentials found in environment\n";
} else {
    echo "✗ AWS credentials not found. Set AWS_S3_ACCESS_KEY_ID and AWS_S3_SECRET_ACCESS_KEY\n";
}

// Test 3: Test S3 connection (if AWS SDK available)
if (class_exists('Aws\S3\S3Client') && $access_key !== 'not_set') {
    echo "\n3. Testing S3 connection...\n";
    
    try {
        $s3Client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $test_config['region'],
            'credentials' => [
                'key' => $access_key,
                'secret' => $secret_key,
            ],
        ]);
        
        // Test bucket access
        $result = $s3Client->headBucket([
            'Bucket' => $test_config['bucket_name'],
        ]);
        
        echo "✓ S3 bucket connection successful\n";
        
        // Test 4: Upload test file
        echo "\n4. Testing file upload...\n";
        
        $upload_result = $s3Client->putObject([
            'Bucket' => $test_config['bucket_name'],
            'Key' => 'tests/integration-test-' . time() . '.txt',
            'SourceFile' => $test_config['test_file'],
            'ACL' => 'private',
            'Metadata' => [
                'test' => 'integration',
                'timestamp' => time(),
            ]
        ]);
        
        echo "✓ File upload successful\n";
        echo "  Upload URL: " . $upload_result['ObjectURL'] . "\n";
        
        // Test 5: Generate presigned URL
        echo "\n5. Testing presigned URL generation...\n";
        
        $command = $s3Client->getCommand('GetObject', [
            'Bucket' => $test_config['bucket_name'],
            'Key' => $upload_result['Key'],
        ]);
        
        $request = $s3Client->createPresignedRequest($command, '+1 hour');
        $presigned_url = (string) $request->getUri();
        
        echo "✓ Presigned URL generated successfully\n";
        echo "  URL: " . substr($presigned_url, 0, 100) . "...\n";
        
        // Test 6: List bucket contents
        echo "\n6. Testing bucket listing...\n";
        
        $list_result = $s3Client->listObjects([
            'Bucket' => $test_config['bucket_name'],
            'Prefix' => 'tests/',
            'MaxKeys' => 5,
        ]);
        
        if (isset($list_result['Contents'])) {
            echo "✓ Bucket listing successful\n";
            echo "  Found " . count($list_result['Contents']) . " test files\n";
        } else {
            echo "✓ Bucket listing successful (no files found)\n";
        }
        
    } catch (Exception $e) {
        echo "✗ S3 operation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n3-6. Skipping S3 tests (AWS SDK or credentials not available)\n";
}

// Test 7: Validate WordPress integration files
echo "\n7. Validating WordPress integration files...\n";

$required_files = array(
    '../wordpress-theme/includes/class-aws-s3-integration.php',
    '../wordpress-theme/includes/aws-s3-functions.php',
    '../aws-config/s3-bucket-setup.json',
    '../aws-config/aws-cli-commands.sh',
    '../aws-config/s3-iam-policy.json',
);

$missing_files = array();
foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ " . basename($file) . " exists\n";
    } else {
        echo "✗ " . basename($file) . " missing\n";
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "✓ All WordPress integration files present\n";
} else {
    echo "✗ Missing " . count($missing_files) . " required files\n";
}

// Test 8: Validate configuration
echo "\n8. Validating configuration...\n";

if (file_exists(__DIR__ . '/../.env.example')) {
    echo "✓ Environment configuration template exists\n";
} else {
    echo "✗ .env.example template missing\n";
}

if (file_exists(__DIR__ . '/../aws-config/s3-bucket-setup.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../aws-config/s3-bucket-setup.json'), true);
    if ($config && isset($config['bucketName'])) {
        echo "✓ S3 configuration file valid\n";
        echo "  Bucket: " . $config['bucketName'] . "\n";
        echo "  Region: " . $config['region'] . "\n";
    } else {
        echo "✗ S3 configuration file invalid\n";
    }
}

// Cleanup
unlink($test_config['test_file']);
echo "\n✓ Test file cleaned up\n";

echo "\n=== Test Summary ===\n";
echo "Integration test completed.\n";
echo "Next steps:\n";
echo "1. Install AWS SDK: composer require aws/aws-sdk-php\n";
echo "2. Set up AWS credentials in .env file\n";
echo "3. Run aws-cli-commands.sh to create S3 bucket\n";
echo "4. Configure WordPress theme settings\n";
echo "\n";
?>