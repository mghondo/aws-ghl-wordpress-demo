"""
Certificate Generator Module
Handles PDF generation using WeasyPrint and S3 upload operations.
"""

import os
import io
import logging
import boto3
from datetime import datetime, timedelta
from jinja2 import Environment, FileSystemLoader
from weasyprint import HTML, CSS
from botocore.exceptions import ClientError, NoCredentialsError

logger = logging.getLogger(__name__)

class CertificateGenerator:
    """
    Handles certificate PDF generation and S3 upload operations.
    """
    
    def __init__(self):
        """Initialize the certificate generator with S3 client and template environment."""
        self.s3_bucket = os.getenv('S3_BUCKET', 'clarity-aws-ghl-demo-storage')
        self.s3_region = os.getenv('S3_REGION', 'us-east-1')
        
        # Initialize S3 client
        try:
            self.s3_client = boto3.client('s3', region_name=self.s3_region)
        except Exception as e:
            logger.error(f"Failed to initialize S3 client: {str(e)}")
            raise
        
        # Initialize Jinja2 template environment
        template_dir = os.path.join(os.path.dirname(__file__), 'templates')
        self.template_env = Environment(loader=FileSystemLoader(template_dir))
        
        # Color schemes by tier
        self.tier_colors = {
            1: '#4A90E2',  # Blue for Foundation
            2: '#95A5A6',  # Silver/Gray for Mastery  
            3: '#F39C12'   # Gold for Elite
        }
    
    def generate_certificate(self, certificate_data):
        """
        Generate a certificate PDF and upload to S3.
        
        Args:
            certificate_data (dict): Certificate information including recipient name, course, etc.
            
        Returns:
            dict: Result containing success status, certificate URL, or error message
        """
        try:
            logger.info(f"Generating certificate for {certificate_data['recipient_name']}")
            
            # Add color scheme based on tier
            certificate_data['accent_color'] = self.tier_colors.get(certificate_data['tier_level'], '#4A90E2')
            
            # Generate HTML from template
            html_content = self._render_template(certificate_data)
            
            # Convert HTML to PDF
            pdf_content = self._html_to_pdf(html_content)
            
            # Upload to S3
            s3_key = self._generate_s3_key(certificate_data)
            certificate_url = self._upload_to_s3(pdf_content, s3_key)
            
            logger.info(f"Certificate generated successfully: {certificate_url}")
            
            return {
                'success': True,
                'certificate_url': certificate_url,
                's3_key': s3_key
            }
            
        except Exception as e:
            logger.error(f"Certificate generation failed: {str(e)}", exc_info=True)
            return {
                'success': False,
                'error': str(e)
            }
    
    def _render_template(self, certificate_data):
        """
        Render the certificate HTML template with the provided data.
        
        Args:
            certificate_data (dict): Certificate data for template rendering
            
        Returns:
            str: Rendered HTML content
        """
        try:
            template = self.template_env.get_template('certificate_template.html')
            
            # Add current year for copyright
            certificate_data['current_year'] = datetime.now().year
            
            html_content = template.render(**certificate_data)
            logger.info("Template rendered successfully")
            return html_content
            
        except Exception as e:
            logger.error(f"Template rendering failed: {str(e)}")
            raise Exception(f"Failed to render certificate template: {str(e)}")
    
    def _html_to_pdf(self, html_content):
        """
        Convert HTML content to PDF using WeasyPrint.
        
        Args:
            html_content (str): HTML content to convert
            
        Returns:
            bytes: PDF content as bytes
        """
        try:
            logger.info("Converting HTML to PDF")
            
            # Create CSS for better styling
            css_content = CSS(string=self._get_pdf_css())
            
            # Generate PDF
            html_doc = HTML(string=html_content)
            pdf_bytes = html_doc.write_pdf(stylesheets=[css_content])
            
            logger.info(f"PDF generated successfully, size: {len(pdf_bytes)} bytes")
            return pdf_bytes
            
        except Exception as e:
            logger.error(f"PDF generation failed: {str(e)}")
            raise Exception(f"Failed to generate PDF: {str(e)}")
    
    def _get_pdf_css(self):
        """
        Return additional CSS for PDF generation optimization.
        
        Returns:
            str: CSS content for PDF styling
        """
        return """
        @page {
            size: Letter;
            margin: 0.5in;
        }
        
        body {
            font-family: 'Times New Roman', 'Georgia', serif;
            margin: 0;
            padding: 0;
        }
        
        .certificate {
            width: 100%;
            height: 100%;
            page-break-inside: avoid;
        }
        
        @media print {
            .certificate {
                page-break-inside: avoid;
            }
        }
        """
    
    def _generate_s3_key(self, certificate_data):
        """
        Generate S3 key path for the certificate.
        
        Args:
            certificate_data (dict): Certificate data containing user_id, course_id, etc.
            
        Returns:
            str: S3 key path
        """
        user_id = certificate_data['user_id']
        course_id = certificate_data['course_id']
        cert_number = certificate_data['certificate_number']
        
        return f"certificates/{user_id}/{course_id}/cert-{cert_number}.pdf"
    
    def _upload_to_s3(self, pdf_content, s3_key):
        """
        Upload PDF content to S3 and return signed URL.
        
        Args:
            pdf_content (bytes): PDF content to upload
            s3_key (str): S3 key for the file
            
        Returns:
            str: Signed URL for the uploaded certificate
        """
        try:
            logger.info(f"Uploading certificate to S3: {s3_key}")
            
            # Upload to S3
            self.s3_client.put_object(
                Bucket=self.s3_bucket,
                Key=s3_key,
                Body=pdf_content,
                ContentType='application/pdf',
                ServerSideEncryption='AES256',
                Metadata={
                    'generated_at': datetime.now().isoformat(),
                    'generator': 'clarity-aws-ghl-lambda'
                }
            )
            
            # Generate signed URL with 7-day expiration
            signed_url = self.s3_client.generate_presigned_url(
                'get_object',
                Params={'Bucket': self.s3_bucket, 'Key': s3_key},
                ExpiresIn=7 * 24 * 3600  # 7 days in seconds
            )
            
            logger.info(f"Certificate uploaded successfully: {signed_url}")
            return signed_url
            
        except NoCredentialsError:
            logger.error("AWS credentials not found")
            raise Exception("AWS credentials not configured")
            
        except ClientError as e:
            error_code = e.response['Error']['Code']
            logger.error(f"S3 upload failed with error {error_code}: {str(e)}")
            
            if error_code == 'NoSuchBucket':
                raise Exception(f"S3 bucket '{self.s3_bucket}' does not exist")
            elif error_code == 'AccessDenied':
                raise Exception("Access denied to S3 bucket - check IAM permissions")
            else:
                raise Exception(f"S3 upload failed: {str(e)}")
                
        except Exception as e:
            logger.error(f"Unexpected error during S3 upload: {str(e)}")
            raise Exception(f"Failed to upload certificate to S3: {str(e)}")
    
    def test_s3_connection(self):
        """
        Test S3 connection and permissions.
        
        Returns:
            dict: Test result with success status and details
        """
        try:
            # Test bucket access
            self.s3_client.head_bucket(Bucket=self.s3_bucket)
            
            # Test write permissions with a small test file
            test_key = 'test/connection-test.txt'
            test_content = f"Connection test at {datetime.now().isoformat()}"
            
            self.s3_client.put_object(
                Bucket=self.s3_bucket,
                Key=test_key,
                Body=test_content.encode(),
                ContentType='text/plain'
            )
            
            # Clean up test file
            self.s3_client.delete_object(Bucket=self.s3_bucket, Key=test_key)
            
            return {
                'success': True,
                'message': f'S3 connection successful to bucket: {self.s3_bucket}'
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def get_certificate_stats(self):
        """
        Get statistics about generated certificates in S3.
        
        Returns:
            dict: Statistics about certificates
        """
        try:
            response = self.s3_client.list_objects_v2(
                Bucket=self.s3_bucket,
                Prefix='certificates/'
            )
            
            if 'Contents' not in response:
                return {
                    'total_certificates': 0,
                    'total_size_mb': 0
                }
            
            total_certificates = len(response['Contents'])
            total_size = sum(obj['Size'] for obj in response['Contents'])
            total_size_mb = round(total_size / (1024 * 1024), 2)
            
            return {
                'total_certificates': total_certificates,
                'total_size_mb': total_size_mb,
                'bucket': self.s3_bucket
            }
            
        except Exception as e:
            logger.error(f"Failed to get certificate stats: {str(e)}")
            return {
                'error': str(e)
            }