<?php
/**
 * Student Registration Form Template
 * 
 * Styled to match the Clarity template form components
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts
wp_enqueue_script('jquery');
?>

<div class="clarity-registration-container">
    <div class="clarity-registration-card">
        <div class="clarity-card-header">
            <h3>Join Our Course Platform</h3>
            <p>Create your account to access our comprehensive learning system</p>
        </div>
        <div class="clarity-card-footer">
            <p>Already have an account? <a href="#" id="show-login-form">Sign in here</a></p>
        </div>
        <?php if (isset($_GET['registration_error'])): ?>
            <div class="error-message">
                <?php echo esc_html(urldecode($_GET['registration_error'])); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registration_success'])): ?>
            <div class="sent-message">
                Registration successful! Welcome to our course platform.
            </div>
        <?php endif; ?>

        <form id="clarity-registration-form" class="php-email-form" method="post">
            <?php wp_nonce_field('clarity_student_registration', 'clarity_register_nonce'); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" name="first_name" class="form-control" placeholder="First Name" required="">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name" required="">
                </div>
            </div>

            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required="">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password (min 6 characters)" required="" minlength="6">
                </div>
                <div class="col-md-6 mb-3">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required="">
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="agree_terms" required="">
                    <label class="form-check-label" for="agree_terms">
                        I agree to the <a href="/terms/" target="_blank">Terms of Service</a> and <a href="/privacy/" target="_blank">Privacy Policy</a>
                    </label>
                </div>
            </div>

            <div class="my-3">
                <div class="loading" style="display: none;">Creating your account...</div>
                <div class="error-message" style="display: none;"></div>
                <div class="sent-message" style="display: none;">Registration successful! Redirecting...</div>
            </div>

            <button type="submit" class="submit-btn">
                <span>Create Account</span>
                <i class="bi bi-person-plus-fill"></i>
            </button>
        </form>

        <div class="clarity-card-footer">
            <p>Already have an account? <a href="#" id="show-login-form">Sign in here</a></p>
        </div>
    </div>
</div>

<style>
.clarity-registration-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.clarity-registration-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.clarity-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 2rem;
    text-align: center;
}

.clarity-card-header h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.clarity-card-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.php-email-form {
    padding: 2rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    outline: 0;
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-check {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.form-check-input {
    margin-top: 0.125rem;
}

.form-check-label {
    font-size: 0.9rem;
    color: #6c757d;
    line-height: 1.4;
}

.form-check-label a {
    color: #667eea;
    text-decoration: none;
}

.form-check-label a:hover {
    text-decoration: underline;
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

.clarity-card-footer {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid #e9ecef;
}

.clarity-card-footer p {
    margin: 0;
    color: #6c757d;
}

.clarity-card-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.clarity-card-footer a:hover {
    text-decoration: underline;
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
    .clarity-registration-container {
        padding: 1rem 0.5rem;
    }
    
    .clarity-card-header {
        padding: 1.5rem;
    }
    
    .php-email-form {
        padding: 1.5rem;
    }
    
    .clarity-card-footer {
        padding: 1rem 1.5rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle registration form submission
    $('#clarity-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('.submit-btn');
        var $loading = $form.find('.loading');
        var $error = $form.find('.error-message');
        var $success = $form.find('.sent-message');
        
        // Validate passwords match
        var password = $form.find('input[name="password"]').val();
        var confirmPassword = $form.find('input[name="confirm_password"]').val();
        
        if (password !== confirmPassword) {
            $error.text('Passwords do not match.').show();
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $loading.show();
        $error.hide();
        $success.hide();
        
        // Submit via AJAX
        $.ajax({
            url: clarityAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_register_student',
                nonce: clarityAjax.nonce,
                first_name: $form.find('input[name="first_name"]').val(),
                last_name: $form.find('input[name="last_name"]').val(),
                email: $form.find('input[name="email"]').val(),
                password: password
            },
            success: function(response) {
                if (response.success) {
                    $success.text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    $error.text(response.data).show();
                    $button.prop('disabled', false);
                }
                $loading.hide();
            },
            error: function() {
                $error.text('An error occurred. Please try again.').show();
                $loading.hide();
                $button.prop('disabled', false);
            }
        });
    });
    
    // Show login form link (if you have a login form on the same page)
    $('#show-login-form').on('click', function(e) {
        e.preventDefault();
        // Redirect to login page or show login modal
        window.location.href = '/student-login/';
    });
});
</script>