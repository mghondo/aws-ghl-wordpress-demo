<?php
/**
 * Contact Form Handler Class
 *
 * Handles contact form submissions and lead generation
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Contact_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers for lead capture
        add_action('wp_ajax_capture_lead_email', array($this, 'ajax_capture_lead_email'));
        add_action('wp_ajax_nopriv_capture_lead_email', array($this, 'ajax_capture_lead_email'));
        
        // Legacy contact form handler (keeping for backwards compatibility)
        add_action('wp_ajax_submit_contact_form', array($this, 'ajax_handle_contact_form'));
        add_action('wp_ajax_nopriv_submit_contact_form', array($this, 'ajax_handle_contact_form'));
    }
    
    /**
     * Handle AJAX email-only lead capture
     */
    public function ajax_capture_lead_email() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lead_capture_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Sanitize email
        $email = sanitize_email($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email) || !is_email($email)) {
            wp_send_json_error('Please enter a valid email address');
        }
        
        // Save lead to database
        $lead_id = $this->save_email_lead($email);
        
        if (!$lead_id) {
            wp_send_json_error('Failed to process your request. Please try again.');
        }
        
        // Send to GoHighLevel webhook
        $this->send_email_lead_to_ghl($email);
        
        // Send notification email to admin (optional)
        $this->send_admin_lead_notification($email);
        
        // Get the free course (Tier 1) slug
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $free_course = $wpdb->get_row(
            "SELECT course_slug FROM {$courses_table} 
            WHERE course_tier = 1 AND course_status = 'published' 
            LIMIT 1"
        );
        
        $redirect_url = home_url('/funnel/real-estate-foundations');
        if ($free_course && !empty($free_course->course_slug)) {
            $redirect_url = home_url('/funnel/' . $free_course->course_slug);
        }
        
        // Add referral parameter to track email leads
        $redirect_url = add_query_arg('ref', 'email-lead', $redirect_url);
        
        wp_send_json_success(array(
            'message' => 'Success! Taking you to your free course...',
            'redirect' => $redirect_url
        ));
    }
    
    /**
     * Handle AJAX contact form submission (legacy)
     */
    public function ajax_handle_contact_form() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Sanitize form data
        $form_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'subject' => sanitize_text_field($_POST['subject'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? '')
        );
        
        // Validate required fields
        if (empty($form_data['name']) || empty($form_data['email']) || empty($form_data['message'])) {
            wp_send_json_error('Please fill in all required fields');
        }
        
        if (!is_email($form_data['email'])) {
            wp_send_json_error('Please enter a valid email address');
        }
        
        // Save lead to database
        $lead_id = $this->save_lead($form_data);
        
        if (!$lead_id) {
            wp_send_json_error('Failed to process your request. Please try again.');
        }
        
        // Send notification email to admin
        $this->send_admin_notification($form_data);
        
        // Send welcome email to lead
        $this->send_welcome_email($form_data);
        
        // Get the free course (Tier 1) slug
        global $wpdb;
        $courses_table = $wpdb->prefix . 'clarity_courses';
        $free_course = $wpdb->get_row(
            "SELECT course_slug FROM {$courses_table} 
            WHERE course_tier = 1 AND course_status = 'published' 
            LIMIT 1"
        );
        
        $redirect_url = home_url('/funnel/real-estate-foundations');
        if ($free_course && !empty($free_course->course_slug)) {
            $redirect_url = home_url('/funnel/' . $free_course->course_slug);
        }
        
        // Add referral parameter to track contact form leads
        $redirect_url = add_query_arg('ref', 'contact', $redirect_url);
        
        wp_send_json_success(array(
            'message' => 'Thank you for contacting us! We have a free course for you...',
            'redirect' => $redirect_url
        ));
    }
    
    /**
     * Handle non-AJAX form submission (fallback)
     */
    public function handle_contact_form_submission() {
        if (!isset($_POST['contact_form_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['contact_form_nonce']) || !wp_verify_nonce($_POST['contact_form_nonce'], 'contact_form_nonce')) {
            return;
        }
        
        // Sanitize form data
        $form_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'subject' => sanitize_text_field($_POST['subject'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? '')
        );
        
        // Save lead
        $lead_id = $this->save_lead($form_data);
        
        if ($lead_id) {
            // Send emails
            $this->send_admin_notification($form_data);
            $this->send_welcome_email($form_data);
            
            // Get free course slug
            global $wpdb;
            $courses_table = $wpdb->prefix . 'clarity_courses';
            $free_course = $wpdb->get_row(
                "SELECT course_slug FROM {$courses_table} 
                WHERE course_tier = 1 AND course_status = 'published' 
                LIMIT 1"
            );
            
            $redirect_url = home_url('/funnel/real-estate-foundations');
            if ($free_course && !empty($free_course->course_slug)) {
                $redirect_url = home_url('/funnel/' . $free_course->course_slug);
            }
            
            // Add referral parameter
            $redirect_url = add_query_arg('ref', 'contact', $redirect_url);
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Save email-only lead to database
     */
    private function save_email_lead($email) {
        global $wpdb;
        $contacts_table = $wpdb->prefix . 'clarity_ghl_contacts';
        
        // Check if this email already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$contacts_table} WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            // Update existing record with new lead activity
            $custom_fields = json_decode($existing->custom_fields, true) ?: array();
            $custom_fields['last_lead_capture'] = current_time('mysql');
            $custom_fields['lead_capture_count'] = ($custom_fields['lead_capture_count'] ?? 0) + 1;
            
            $result = $wpdb->update(
                $contacts_table,
                array(
                    'tags' => 'email-lead,interested',
                    'custom_fields' => json_encode($custom_fields),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
            
            return $existing->id;
        } else {
            // Create new lead record
            $result = $wpdb->insert(
                $contacts_table,
                array(
                    'ghl_contact_id' => 'lead_' . uniqid(), // Prefix for email leads
                    'email' => $email,
                    'source' => 'email_capture',
                    'tags' => 'email-lead,interested',
                    'custom_fields' => json_encode(array(
                        'lead_capture_date' => current_time('mysql'),
                        'lead_type' => 'email_only'
                    )),
                    'created_at' => current_time('mysql')
                )
            );
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Send notification email to admin for new lead
     */
    private function send_admin_lead_notification($email) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] New Email Lead Captured";
        
        $message = "A new lead has been captured:\n\n";
        $message .= "Email: {$email}\n";
        $message .= "Date: " . current_time('mysql') . "\n\n";
        $message .= "---\n";
        $message .= "This lead has been redirected to the free course offer.\n";
        $app_name = clarity_get_app_name();
        $message .= "View all leads in WordPress Admin > {$app_name} > Contacts";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>'
        );
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send email lead to GoHighLevel webhook
     */
    private function send_email_lead_to_ghl($email) {
        error_log("Clarity: send_email_lead_to_ghl called for email {$email}");
        
        $webhook_url = 'https://services.leadconnectorhq.com/hooks/dx7Ru0l4s4q30jYQBuAz/webhook-trigger/a68645d7-e669-4e10-8de0-6299da1d20b0';
        
        $data = array(
            'email' => $email
        );
        
        error_log("Clarity: Email lead webhook data - " . json_encode($data));
        
        // Send to webhook
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log("Clarity: Email lead webhook error - " . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            error_log("Clarity: Email lead webhook response - Code: {$response_code}, Body: {$response_body}");
        }
    }
    
    /**
     * Save full contact form lead to database (legacy)
     */
    private function save_lead($form_data) {
        global $wpdb;
        $contacts_table = $wpdb->prefix . 'clarity_ghl_contacts';
        
        // Split name into first and last
        $name_parts = explode(' ', $form_data['name'], 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Check if this email already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$contacts_table} WHERE email = %s",
            $form_data['email']
        ));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $contacts_table,
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'custom_fields' => json_encode(array(
                        'last_subject' => $form_data['subject'],
                        'last_message' => $form_data['message'],
                        'last_contact' => current_time('mysql')
                    )),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id)
            );
            
            return $existing->id;
        } else {
            // Create new lead record
            $result = $wpdb->insert(
                $contacts_table,
                array(
                    'ghl_contact_id' => 'web_' . uniqid(), // Prefix for web-generated leads
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $form_data['email'],
                    'source' => 'website_contact_form',
                    'tags' => 'contact-form,lead',
                    'custom_fields' => json_encode(array(
                        'initial_subject' => $form_data['subject'],
                        'initial_message' => $form_data['message'],
                        'contact_date' => current_time('mysql')
                    )),
                    'created_at' => current_time('mysql')
                )
            );
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Send notification email to admin
     */
    private function send_admin_notification($form_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] New Contact Form Submission";
        
        $message = "You have received a new contact form submission:\n\n";
        $message .= "Name: {$form_data['name']}\n";
        $message .= "Email: {$form_data['email']}\n";
        $message .= "Subject: {$form_data['subject']}\n";
        $message .= "Message:\n{$form_data['message']}\n\n";
        $message .= "---\n";
        $message .= "This lead has been saved to the database and redirected to the free course offer.\n";
        $app_name = clarity_get_app_name();
        $message .= "View all leads in WordPress Admin > {$app_name} > Contacts";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
            'Reply-To: ' . $form_data['name'] . ' <' . $form_data['email'] . '>'
        );
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send welcome email to the lead
     */
    private function send_welcome_email($form_data) {
        $site_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');
        
        $subject = "Welcome! Here's Your Free Real Estate Course";
        
        $message = "Hi {$form_data['name']},\n\n";
        $message .= "Thank you for contacting us! We're excited to help you start your real estate journey.\n\n";
        $message .= "As a thank you for reaching out, we're giving you FREE access to our Real Estate Foundations course.\n\n";
        $message .= "This comprehensive course covers:\n";
        $message .= "• Real estate fundamentals\n";
        $message .= "• Market analysis basics\n";
        $message .= "• Investment strategies\n";
        $message .= "• And much more!\n\n";
        $message .= "Click the link below to get started:\n";
        $message .= home_url('/funnel/real-estate-foundations?ref=email') . "\n\n";
        $message .= "If you have any questions about your original inquiry:\n";
        $message .= "\"{$form_data['subject']}\"\n\n";
        $message .= "We'll be in touch shortly with a personal response.\n\n";
        $message .= "Best regards,\n";
        $message .= "The {$site_name} Team\n\n";
        $message .= "P.S. This free course is the first step in our three-tier learning system. Once you complete it, you'll unlock access to even more advanced training!";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>'
        );
        
        wp_mail($form_data['email'], $subject, $message, $headers);
    }
}

// Initialize the handler
new Clarity_AWS_GHL_Contact_Form_Handler();