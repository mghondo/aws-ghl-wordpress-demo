"""
Simplified Lambda Function for Certificate Generation
This version generates HTML certificates and uploads to S3 for immediate testing.
"""

import json
import logging
import os
import boto3
from datetime import datetime
from botocore.exceptions import ClientError, NoCredentialsError

# Configure logging
logger = logging.getLogger()
logger.setLevel(logging.INFO)

def lambda_handler(event, context):
    """
    Simplified Lambda handler that generates HTML certificates.
    """
    try:
        logger.info(f"Certificate generation request received: {json.dumps(event)}")
        
        # Parse the request body
        if 'body' in event:
            if isinstance(event['body'], str):
                body = json.loads(event['body'])
            else:
                body = event['body']
        else:
            body = event
            
        # Validate required fields
        required_fields = ['recipient_name', 'course_title', 'tier_level', 'completion_date', 'user_id', 'course_id']
        missing_fields = [field for field in required_fields if field not in body or not body[field]]
        
        if missing_fields:
            logger.error(f"Missing required fields: {missing_fields}")
            return {
                'statusCode': 400,
                'headers': {'Content-Type': 'application/json'},
                'body': json.dumps({
                    'success': False,
                    'error': f'Missing required fields: {", ".join(missing_fields)}'
                })
            }
        
        # Generate certificate number
        certificate_number = generate_certificate_number()
        
        # Map tier level to tier name
        tier_names = {1: "Foundation Program", 2: "Mastery Program", 3: "Elite Program"}
        tier_name = tier_names.get(body['tier_level'], "Unknown Program")
        
        # Format completion date
        try:
            completion_date_obj = datetime.strptime(body['completion_date'], '%Y-%m-%d')
            formatted_date = completion_date_obj.strftime('%B %d, %Y')
        except ValueError:
            logger.error(f"Invalid date format: {body['completion_date']}")
            return {
                'statusCode': 400,
                'headers': {'Content-Type': 'application/json'},
                'body': json.dumps({
                    'success': False,
                    'error': 'completion_date must be in YYYY-MM-DD format'
                })
            }
        
        # Prepare certificate data
        certificate_data = {
            'recipient_name': body['recipient_name'].strip(),
            'course_title': body['course_title'].strip(),
            'tier_level': body['tier_level'],
            'tier_name': tier_name,
            'completion_date': formatted_date,
            'certificate_number': certificate_number,
            'user_id': body['user_id'],
            'course_id': body['course_id'],
            'accent_color': get_tier_color(body['tier_level']),
            'current_year': datetime.now().year
        }
        
        # Generate HTML certificate
        html_content = generate_html_certificate(certificate_data)
        
        # Upload to S3
        s3_key = f"certificates/{body['user_id']}/{body['course_id']}/cert-{certificate_number}.html"
        certificate_url = upload_to_s3(html_content, s3_key, 'text/html')
        
        logger.info(f"Certificate generated successfully: {certificate_number}")
        
        return {
            'statusCode': 200,
            'headers': {'Content-Type': 'application/json'},
            'body': json.dumps({
                'success': True,
                'certificate_url': certificate_url,
                'certificate_number': certificate_number,
                'message': f'Certificate generated successfully for {body["recipient_name"]}',
                'note': 'This is an HTML version - PDF generation requires additional Lambda configuration'
            })
        }
        
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}", exc_info=True)
        return {
            'statusCode': 500,
            'headers': {'Content-Type': 'application/json'},
            'body': json.dumps({
                'success': False,
                'error': 'Internal server error occurred while generating certificate'
            })
        }

def generate_certificate_number():
    """Generate a unique certificate number."""
    import random
    current_year = datetime.now().year
    random_number = random.randint(1, 9999)
    return f"CERT-{current_year}-{random_number:04d}"

def get_tier_color(tier_level):
    """Get color scheme for tier level."""
    colors = {1: '#4A90E2', 2: '#95A5A6', 3: '#F39C12'}
    return colors.get(tier_level, '#4A90E2')

def generate_html_certificate(certificate_data):
    """Generate HTML certificate content."""
    template_content = """
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion - {{ certificate_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Crimson+Text:wght@400;600&display=swap');
        
        body {
            font-family: 'Crimson Text', serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .certificate {
            width: 800px;
            background: white;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
            padding: 60px;
            text-align: center;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 3px solid {{ accent_color }};
            border-radius: 4px;
            z-index: 1;
        }
        
        .content {
            position: relative;
            z-index: 2;
        }
        
        .organization {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: {{ accent_color }};
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        
        .subtitle {
            font-size: 18px;
            color: #7f8c8d;
            font-style: italic;
            margin-bottom: 40px;
        }
        
        .this-certifies {
            font-size: 20px;
            color: #34495e;
            margin-bottom: 30px;
            font-style: italic;
        }
        
        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 40px;
            line-height: 1.2;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .recipient-name::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 3px;
            background: linear-gradient(90deg, transparent, {{ accent_color }}, transparent);
        }
        
        .achievement-text {
            font-size: 22px;
            color: #34495e;
            margin-bottom: 20px;
        }
        
        .course-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: {{ accent_color }};
            margin-bottom: 30px;
            line-height: 1.3;
        }
        
        .tier-badge {
            display: inline-block;
            background: linear-gradient(135deg, {{ accent_color }}, {{ accent_color }}cc);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .completion-date {
            font-size: 20px;
            color: #34495e;
            margin-bottom: 50px;
            font-style: italic;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
        }
        
        .signature-section {
            text-align: left;
        }
        
        .signature-line {
            width: 200px;
            height: 2px;
            background: #bdc3c7;
            margin-bottom: 10px;
        }
        
        .signature-title {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .certificate-details {
            text-align: right;
        }
        
        .certificate-number {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-family: 'Courier New', monospace;
        }
        
        .verification-text {
            font-size: 12px;
            color: #95a5a6;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: {{ accent_color }}08;
            font-weight: 900;
            z-index: 1;
            user-select: none;
            pointer-events: none;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .certificate {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="watermark">MORGO</div>
        <div class="content">
            <div class="organization">Morgo LLC</div>
            <h1 class="title">Certificate of Completion</h1>
            <div class="subtitle">Excellence in Professional Development</div>
            
            <div class="this-certifies">This certifies that</div>
            <div class="recipient-name">{{ recipient_name }}</div>
            <div class="achievement-text">has successfully completed the</div>
            <div class="course-title">{{ course_title }}</div>
            <div class="tier-badge">{{ tier_name }}</div>
            
            <div class="completion-date">Completed on {{ completion_date }}</div>
            
            <div class="footer">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-title">Course Instructor</div>
                </div>
                <div class="certificate-details">
                    <div class="certificate-number">Certificate No: {{ certificate_number }}</div>
                    <div class="verification-text">Verify at morgo.com/verify</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
    """
    
    # Simple template rendering without Jinja2
    html = template_content
    for key, value in certificate_data.items():
        html = html.replace('{{ ' + key + ' }}', str(value))
    
    return html

def upload_to_s3(content, s3_key, content_type='text/html'):
    """Upload content to S3 and return signed URL."""
    try:
        s3_bucket = os.getenv('S3_BUCKET', 'clarity-aws-ghl-demo-storage')
        s3_client = boto3.client('s3')
        
        # Upload to S3
        s3_client.put_object(
            Bucket=s3_bucket,
            Key=s3_key,
            Body=content.encode('utf-8'),
            ContentType=content_type,
            ServerSideEncryption='AES256',
            Metadata={
                'generated_at': datetime.now().isoformat(),
                'generator': 'clarity-aws-ghl-lambda-simple'
            }
        )
        
        # Generate signed URL with 7-day expiration
        signed_url = s3_client.generate_presigned_url(
            'get_object',
            Params={'Bucket': s3_bucket, 'Key': s3_key},
            ExpiresIn=7 * 24 * 3600  # 7 days in seconds
        )
        
        logger.info(f"Certificate uploaded successfully: {signed_url}")
        return signed_url
        
    except Exception as e:
        logger.error(f"S3 upload failed: {str(e)}")
        raise Exception(f"Failed to upload certificate to S3: {str(e)}")