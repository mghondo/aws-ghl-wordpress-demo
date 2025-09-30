<?php
/**
 * Contact Form Template
 * 
 * Styled to match the Clarity template form components with GHL integration
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts
wp_enqueue_script('jquery');
?>

<div class="clarity-contact-section">
    <div class="contact-container">
        <div class="contact-header">
            <h3>Let's Start a Conversation</h3>
            <p>Ready to transform your business with our comprehensive course platform? Get in touch with us today.</p>
        </div>

        <?php if (isset($_GET['contact_success'])): ?>
            <div class="sent-message">
                Your message has been sent successfully! We'll get back to you soon.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['contact_error'])): ?>
            <div class="error-message">
                <?php echo esc_html(urldecode($_GET['contact_error'])); ?>
            </div>
        <?php endif; ?>

        <form id="clarity-contact-form" class="php-email-form" method="post">
            <?php wp_nonce_field('clarity_contact_form', 'clarity_contact_nonce'); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Your Name" required="">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required="">
                </div>
            </div>

            <div class="mb-3">
                <input type="text" class="form-control" name="subject" placeholder="What's this about?" required="">
            </div>

            <div class="mb-3">
                <select class="form-control" name="interest" required="">
                    <option value="">Select your interest...</option>
                    <option value="free_course">Free Course Access</option>
                    <option value="core_product">Core Product ($497)</option>
                    <option value="premium_access">Premium Access ($1997)</option>
                    <option value="enterprise">Enterprise Solutions</option>
                    <option value="partnership">Partnership Opportunities</option>
                    <option value="support">Technical Support</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <textarea class="form-control" name="message" rows="4" placeholder="Tell us more about your needs..." required=""></textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="subscribe_newsletter" name="subscribe_newsletter" value="1">
                    <label class="form-check-label" for="subscribe_newsletter">
                        I'd like to receive updates about new courses and features
                    </label>
                </div>
            </div>

            <div class="my-3">
                <div class="loading" style="display: none;">Sending your message...</div>
                <div class="error-message" style="display: none;"></div>
                <div class="sent-message" style="display: none;">Your message has been sent. Thank you!</div>
            </div>

            <button type="submit" class="submit-btn">
                <span>Send Message</span>
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<style>
.clarity-contact-section {
    background: #f8f9fa;
    padding: 4rem 2rem;
    margin: 2rem 0;
}

.contact-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.contact-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 3rem 2rem;
    text-align: center;
}

.contact-header h3 {
    margin: 0 0 1rem 0;
    font-size: 2rem;
    font-weight: 600;
}

.contact-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
    line-height: 1.6;
}

.php-email-form {
    padding: 3rem 2rem;
}

.row {
    display: flex;
    gap: 1rem;
    margin-bottom: 0;
}

.col-md-6 {
    flex: 1;
    min-width: 0;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    background: #fff;
}

.form-control:focus {
    outline: 0;
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

select.form-control {
    cursor: pointer;
}

.form-check {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-top: 1rem;
}

.form-check-input {
    margin-top: 0.125rem;
}

.form-check-label {
    font-size: 0.9rem;
    color: #6c757d;
    line-height: 1.4;
    cursor: pointer;
}

.submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.submit-btn:disabled {
    opacity: 0.6;
    transform: none;
    cursor: not-allowed;
}

.loading {
    color: #667eea;
    font-weight: 600;
    text-align: center;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}

.sent-message {
    background: #d4edda;
    color: #155724;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .clarity-contact-section {
        padding: 2rem 1rem;
    }
    
    .contact-header {
        padding: 2rem 1.5rem;
    }
    
    .contact-header h3 {
        font-size: 1.5rem;
    }
    
    .php-email-form {
        padding: 2rem 1.5rem;
    }
    
    .row {
        flex-direction: column;
        gap: 0;
    }
    
    .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#clarity-contact-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('.submit-btn');
        var $loading = $form.find('.loading');
        var $error = $form.find('.error-message');
        var $success = $form.find('.sent-message');
        
        // Basic validation
        var isValid = true;
        $form.find('input[required], textarea[required], select[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('form-field-error');
                isValid = false;
            } else {
                $(this).removeClass('form-field-error');
            }
        });
        
        if (!isValid) {
            $error.text('Please fill in all required fields.').show();
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $loading.show();
        $error.hide();
        $success.hide();
        
        // Submit via AJAX (or form post)
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_contact_form',
                nonce: clarityAjax.contact_nonce,
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val(),
                subject: $form.find('input[name="subject"]').val(),
                interest: $form.find('select[name="interest"]').val(),
                message: $form.find('textarea[name="message"]').val(),
                subscribe_newsletter: $form.find('input[name="subscribe_newsletter"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $success.text(response.data.message || 'Thank you! Your message has been sent.').show();
                    $form[0].reset();
                } else {
                    $error.text(response.data || 'There was an error sending your message.').show();
                }
                $loading.hide();
                $button.prop('disabled', false);
            },
            error: function() {
                $error.text('There was an error sending your message. Please try again.').show();
                $loading.hide();
                $button.prop('disabled', false);
            }
        });
    });
    
    // Remove error styling on input
    $(document).on('input', '.form-field-error', function() {
        $(this).removeClass('form-field-error');
    });
});
</script>