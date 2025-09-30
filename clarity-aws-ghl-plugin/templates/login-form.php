<?php
/**
 * Student Login Form Template
 * 
 * Styled to match the Clarity template form components
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts
wp_enqueue_script('jquery');
?>

<div class="clarity-login-container">
    <div class="clarity-login-card">
        <div class="clarity-card-header">
            <h3>Welcome Back</h3>
            <p>Sign in to access your courses and continue learning</p>
        </div>

        <?php if (isset($_GET['login_error'])): ?>
            <div class="error-message">
                <?php echo esc_html(urldecode($_GET['login_error'])); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout_success'])): ?>
            <div class="sent-message">
                You have been logged out successfully.
            </div>
        <?php endif; ?>

        <form id="clarity-login-form" class="php-email-form" method="post">
            <?php wp_nonce_field('clarity_student_login', 'clarity_login_nonce'); ?>
            
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required="">
            </div>

            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required="">
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">
                        Remember me
                    </label>
                </div>
            </div>

            <div class="my-3">
                <div class="loading" style="display: none;">Signing you in...</div>
                <div class="error-message" style="display: none;"></div>
                <div class="sent-message" style="display: none;">Login successful! Redirecting...</div>
            </div>

            <button type="submit" class="submit-btn">
                <span>Sign In</span>
                <i class="bi bi-box-arrow-in-right"></i>
            </button>
        </form>

        <div class="clarity-card-footer">
            <p>Don't have an account? <a href="#" id="show-registration-form">Create one here</a></p>
            <p><a href="#" id="forgot-password">Forgot your password?</a></p>
        </div>
    </div>
</div>

<style>
.clarity-login-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.clarity-login-card {
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
    align-items: center;
    gap: 0.5rem;
}

.form-check-input {
    margin: 0;
}

.form-check-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
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
    margin: 0 0 0.5rem 0;
    color: #6c757d;
}

.clarity-card-footer p:last-child {
    margin-bottom: 0;
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
    .clarity-login-container {
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
    // Handle login form submission
    $('#clarity-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('.submit-btn');
        var $loading = $form.find('.loading');
        var $error = $form.find('.error-message');
        var $success = $form.find('.sent-message');
        
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
                action: 'clarity_login_student',
                nonce: clarityAjax.nonce,
                email: $form.find('input[name="email"]').val(),
                password: $form.find('input[name="password"]').val(),
                remember: $form.find('input[name="remember"]').is(':checked')
            },
            success: function(response) {
                if (response.success) {
                    $success.text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
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
    
    // Show registration form link
    $('#show-registration-form').on('click', function(e) {
        e.preventDefault();
        window.location.href = '/student-registration/';
    });
    
    // Handle forgot password
    $('#forgot-password').on('click', function(e) {
        e.preventDefault();
        alert('Please contact support for password reset assistance.');
    });
});
</script>