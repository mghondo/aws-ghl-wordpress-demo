# AWS Lambda Certificate Generator

A production-ready serverless certificate generation system built with Python, WeasyPrint, and AWS Lambda. This function generates professional PDF certificates for course completions and integrates seamlessly with WordPress LMS systems.

## 🎯 Overview

This Lambda function:
- Receives certificate requests from WordPress via API Gateway
- Generates beautiful, professional PDF certificates using HTML/CSS templates
- Uploads certificates to S3 with signed URLs
- Returns certificate URLs and tracking numbers to WordPress
- Supports tier-based designs with custom color schemes
- Handles error scenarios gracefully with detailed logging

## 📋 Features

### Certificate Generation
- **Professional Design**: University-style certificates with elegant typography
- **Tier-Based Styling**: Different colors for Foundation (Blue), Mastery (Silver), Elite (Gold)
- **Dynamic Content**: Recipient name, course title, completion date, certificate numbers
- **Security Features**: Unique certificate numbers (CERT-YYYY-####), verification codes
- **Responsive Layout**: 8.5x11 inch format, optimized for print and digital viewing

### AWS Integration
- **S3 Storage**: Secure certificate storage with organized folder structure
- **Signed URLs**: 7-day expiration for secure access
- **Error Handling**: Comprehensive error handling with CloudWatch logging
- **Performance**: Optimized for Lambda with 1024MB memory allocation

### Security & Compliance
- **Input Validation**: Comprehensive validation of all input parameters
- **Access Control**: Proper IAM roles and S3 bucket policies
- **Encryption**: Server-side encryption for stored certificates
- **Audit Trail**: Complete logging of all certificate generation events

## 🚀 Quick Deployment

### Prerequisites
- AWS CLI configured with appropriate permissions
- Python 3.11+ for local testing
- S3 bucket created for certificate storage

### 1. Package the Lambda Function

```bash
# Clone or download the project
cd lambda/

# Create deployment package
mkdir package
pip install -r requirements.txt -t package/

# Copy source files
cp *.py package/
cp -r templates/ package/

# Create deployment zip
cd package
zip -r ../certificate-generator.zip .
cd ..
```

### 2. Create IAM Role

Create an IAM role for the Lambda function with this policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogGroup",
                "logs:CreateLogStream",
                "logs:PutLogEvents"
            ],
            "Resource": "arn:aws:logs:*:*:*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::clarity-aws-ghl-demo-storage",
                "arn:aws:s3:::clarity-aws-ghl-demo-storage/*"
            ]
        }
    ]
}
```

### 3. Deploy Lambda Function

```bash
# Create the Lambda function
aws lambda create-function \
  --function-name certificate-generator \
  --runtime python3.11 \
  --role arn:aws:iam::YOUR-ACCOUNT:role/lambda-certificate-role \
  --handler handler.lambda_handler \
  --zip-file fileb://certificate-generator.zip \
  --memory-size 1024 \
  --timeout 30 \
  --environment Variables='{
    "S3_BUCKET":"clarity-aws-ghl-demo-storage",
    "S3_REGION":"us-east-1"
  }'
```

### 4. Create API Gateway

```bash
# Create API Gateway (REST API)
aws apigateway create-rest-api --name certificate-api

# Configure POST method and integrate with Lambda
# (Full API Gateway setup commands available in deployment scripts)
```

## 🔧 Local Development

### Setup Development Environment

```bash
# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Set environment variables
export S3_BUCKET=clarity-aws-ghl-demo-storage
export S3_REGION=us-east-1
export AWS_ACCESS_KEY_ID=your-access-key
export AWS_SECRET_ACCESS_KEY=your-secret-key
```

### Local Testing

```python
# test_local.py - Local testing script
import json
from handler import lambda_handler

# Test certificate generation
test_event = {
    "body": json.dumps({
        "recipient_name": "John Doe",
        "course_title": "Real Estate Foundations",
        "tier_level": 1,
        "completion_date": "2024-10-05",
        "user_id": 123,
        "course_id": 456
    })
}

response = lambda_handler(test_event, None)
print(json.dumps(response, indent=2))
```

```bash
# Run local test
python test_local.py
```

## 📡 API Usage

### Endpoint
```
POST https://your-api-gateway-url/certificates
```

### Request Format
```json
{
  "recipient_name": "John Doe",
  "course_title": "Real Estate Foundations",
  "tier_level": 1,
  "completion_date": "2024-10-05",
  "user_id": 123,
  "course_id": 456
}
```

### Response Format
```json
{
  "statusCode": 200,
  "body": {
    "success": true,
    "certificate_url": "https://s3.amazonaws.com/bucket/certificates/123/456/cert-CERT-2024-0847.pdf?X-Amz-...",
    "certificate_number": "CERT-2024-0847",
    "message": "Certificate generated successfully for John Doe"
  }
}
```

### Error Responses
```json
{
  "statusCode": 400,
  "body": {
    "success": false,
    "error": "Missing required fields: recipient_name"
  }
}
```

## 🔧 WordPress Integration

### Certificate Manager Class

Add this to your WordPress plugin:

```php
// includes/class-certificate-manager.php
class Clarity_AWS_GHL_Certificate_Manager {
    private $api_endpoint = 'https://your-api-gateway-url/certificates';
    
    public function request_certificate($user_id, $course_id) {
        // Get user and course data
        $user = get_userdata($user_id);
        $course = $this->get_course_data($course_id);
        
        // Prepare certificate request
        $request_data = [
            'recipient_name' => $user->display_name,
            'course_title' => $course->course_title,
            'tier_level' => $course->course_tier,
            'completion_date' => date('Y-m-d'),
            'user_id' => $user_id,
            'course_id' => $course_id
        ];
        
        // Call Lambda function
        $response = wp_remote_post($this->api_endpoint, [
            'body' => json_encode($request_data),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 45
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['success']) {
            // Update database with certificate URL
            $this->save_certificate_url($user_id, $course_id, 
                $body['certificate_url'], $body['certificate_number']);
        }
        
        return $body;
    }
}
```

### AJAX Handler

```php
// Handle certificate requests
add_action('wp_ajax_request_certificate', 'handle_certificate_request');

function handle_certificate_request() {
    $user_id = get_current_user_id();
    $course_id = intval($_POST['course_id']);
    
    // Verify user completed course
    if (!user_completed_course($user_id, $course_id)) {
        wp_send_json_error('Course not completed');
    }
    
    $cert_manager = new Clarity_AWS_GHL_Certificate_Manager();
    $result = $cert_manager->request_certificate($user_id, $course_id);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['error']);
    }
}
```

## 📊 Monitoring & Logging

### CloudWatch Metrics
- Function duration and memory usage
- Error rates and success rates  
- S3 upload performance
- Certificate generation volume

### Log Analysis
```bash
# View recent logs
aws logs describe-log-groups --log-group-name-prefix /aws/lambda/certificate-generator

# Stream logs in real-time
aws logs tail /aws/lambda/certificate-generator --follow
```

### Performance Optimization
- **Memory**: 1024MB (optimal for WeasyPrint)
- **Timeout**: 30 seconds (usually completes in 10-15s)
- **Concurrency**: Set reserved concurrency to prevent cost overruns
- **Dead Letter Queue**: Configure for failed executions

## 🔒 Security Considerations

### Access Control
- Lambda execution role with minimal necessary permissions
- S3 bucket policy restricting access to certificate paths
- API Gateway authentication (recommend API keys or AWS IAM)

### Data Protection
- Server-side encryption enabled on S3
- Signed URLs with 7-day expiration
- Input validation and sanitization
- No sensitive data in CloudWatch logs

### Best Practices
```python
# Environment variable validation
def validate_environment():
    required_vars = ['S3_BUCKET', 'S3_REGION']
    missing = [var for var in required_vars if not os.getenv(var)]
    if missing:
        raise Exception(f"Missing environment variables: {missing}")
```

## 💰 Cost Optimization

### Estimated Costs (per 1000 certificates)
- **Lambda**: ~$0.50 (1024MB, 15s average execution)
- **S3 Storage**: ~$0.023 (100KB average PDF size)
- **API Gateway**: ~$3.50 (REST API requests)
- **Total**: ~$4.00 per 1000 certificates

### Optimization Strategies
```python
# Implement caching for repeated requests
# Use S3 Intelligent Tiering for long-term storage
# Set up lifecycle policies for old certificates
```

## 🚦 Deployment Checklist

- [ ] S3 bucket created with proper permissions
- [ ] IAM role created with certificate-lambda-policy
- [ ] Lambda function deployed with correct environment variables
- [ ] API Gateway configured and deployed
- [ ] CloudWatch alarms configured for monitoring
- [ ] WordPress integration tested
- [ ] Error handling verified
- [ ] Performance benchmarks established

## 🐛 Troubleshooting

### Common Issues

**1. WeasyPrint Import Error**
```bash
# Solution: Ensure all dependencies are included in deployment package
pip install -r requirements.txt -t package/ --no-deps
```

**2. S3 Permission Denied**
```bash
# Solution: Check IAM role and bucket policy
aws iam get-role-policy --role-name lambda-certificate-role --policy-name certificate-policy
```

**3. PDF Generation Timeout**
```bash
# Solution: Increase Lambda timeout and memory
aws lambda update-function-configuration --function-name certificate-generator --timeout 45 --memory-size 1536
```

**4. Font Rendering Issues**
```python
# Solution: Include custom fonts in deployment package
# Add to templates/ directory and reference in CSS
```

### Debug Mode
```python
# Enable debug logging
import logging
logging.basicConfig(level=logging.DEBUG)
```

## 📚 Additional Resources

- [WeasyPrint Documentation](https://weasyprint.readthedocs.io/)
- [AWS Lambda Python Guide](https://docs.aws.amazon.com/lambda/latest/dg/python-programming-model.html)
- [Jinja2 Template Engine](https://jinja.palletsprojects.com/)
- [S3 Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/optimizing-performance.html)

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**🚀 Ready for Production Deployment**

This certificate generator is production-ready and optimized for performance, security, and cost-effectiveness. Perfect for portfolio demonstrations and enterprise WordPress LMS integrations.