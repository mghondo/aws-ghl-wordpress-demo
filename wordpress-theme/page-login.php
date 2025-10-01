<?php
/**
 * Template Name: Login Page
 * Description: Custom styled login page matching the app design
 * 
 * @package Clarity_AWS_GHL
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/course/'));
    exit;
}

// Handle login form submission
if (isset($_POST['login_submit'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        $login_error = $user->get_error_message();
    } else {
        // Successful login - redirect to course page
        wp_redirect(home_url('/course/'));
        exit;
    }
}

get_header();
?>

<main class="main">
    <!-- Login Section -->
    <section class="login-section section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="login-form-card">
                        <!-- Form Header -->
                        <div class="form-header">
                            <div class="header-icon">
                                <i class="bi bi-person-lock"></i>
                            </div>
                            <h3>Welcome Back</h3>
                            <p>Sign in to access your courses and track your progress</p>
                        </div>
                        
                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo esc_html($login_error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="post" class="php-email-form">
                            <div class="mb-4">
                                <label for="username" class="form-label">Email or Username</label>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control" 
                                       placeholder="Enter your email or username"
                                       value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>"
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="form-control" 
                                       placeholder="Enter your password"
                                       required>
                            </div>
                            
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="remember" 
                                           id="remember" 
                                           class="form-check-input"
                                           <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                    <label for="remember" class="form-check-label">
                                        Remember me
                                    </label>
                                </div>
                                <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password-link">
                                    Forgot password?
                                </a>
                            </div>
                            
                            <button type="submit" name="login_submit" class="submit-btn">
                                <span>Sign In</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                        
                        <!-- Demo Mode Option -->
                        <div class="demo-mode-section">
                            <div class="divider">
                                <span>OR</span>
                            </div>
                            <a href="<?php echo home_url('/course/?demo=1'); ?>" class="demo-mode-btn">
                                <i class="bi bi-eye"></i>
                                <span>Continue as Demo User</span>
                            </a>
                        </div>
                        
                        <!-- Footer Links -->
                        <div class="login-footer">
                            <p>Don't have an account? <a href="#" class="register-link">Sign up here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
/* Login Section Styles - Based on contact form styling */
.login-section {
    background: linear-gradient(135deg, var(--background-color), color-mix(in srgb, var(--accent-color), transparent 97%));
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 60px 0;
}

.login-form-card {
    background: var(--surface-color);
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 20px 60px color-mix(in srgb, var(--default-color), transparent 92%);
    border: 1px solid color-mix(in srgb, var(--accent-color), transparent 90%);
    position: relative;
    overflow: hidden;
    max-width: 500px;
    margin: 0 auto;
}

.login-form-card:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-color), color-mix(in srgb, var(--accent-color), #6366f1 30%));
}

/* Form Header */
.form-header {
    text-align: center;
    margin-bottom: 35px;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--accent-color), color-mix(in srgb, var(--accent-color), #6366f1 30%));
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.header-icon i {
    font-size: 28px;
    color: var(--contrast-color);
}

.form-header h3 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--heading-color);
}

.form-header p {
    font-size: 15px;
    line-height: 1.6;
    color: color-mix(in srgb, var(--default-color), transparent 20%);
    margin-bottom: 0;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--heading-color);
    font-size: 14px;
}

.form-control {
    height: 52px;
    padding: 16px 20px;
    border-radius: 16px;
    border: 2px solid color-mix(in srgb, var(--default-color), transparent 88%);
    background-color: color-mix(in srgb, var(--surface-color), var(--background-color) 30%);
    color: var(--default-color);
    font-size: 15px;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent-color), transparent 90%);
    background-color: var(--surface-color);
    outline: none;
}

.form-control::placeholder {
    color: color-mix(in srgb, var(--default-color), transparent 50%);
    font-weight: 400;
}

/* Checkbox and Links */
.form-check-input {
    border-radius: 4px;
    border: 2px solid color-mix(in srgb, var(--default-color), transparent 80%);
}

.form-check-input:checked {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.form-check-label {
    font-size: 14px;
    color: var(--default-color);
}

.forgot-password-link {
    color: var(--accent-color);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.forgot-password-link:hover {
    color: color-mix(in srgb, var(--accent-color), #6366f1 30%);
}

/* Submit Button */
.submit-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--accent-color), color-mix(in srgb, var(--accent-color), #6366f1 30%));
    color: var(--contrast-color);
    border: none;
    padding: 16px 30px;
    border-radius: 16px;
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.submit-btn:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--contrast-color), transparent 85%), transparent);
    transition: left 0.6s ease;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px color-mix(in srgb, var(--accent-color), transparent 75%);
}

.submit-btn:hover:before {
    left: 100%;
}

.submit-btn:hover i {
    transform: translateX(3px);
}

.submit-btn span,
.submit-btn i {
    position: relative;
    z-index: 1;
}

.submit-btn i {
    font-size: 16px;
    transition: transform 0.3s ease;
}

/* Demo Mode Section */
.demo-mode-section {
    margin: 30px 0;
}

.divider {
    text-align: center;
    position: relative;
    margin: 25px 0;
}

.divider:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: color-mix(in srgb, var(--default-color), transparent 85%);
}

.divider span {
    background: var(--surface-color);
    padding: 0 15px;
    color: color-mix(in srgb, var(--default-color), transparent 30%);
    font-size: 14px;
    font-weight: 500;
}

.demo-mode-btn {
    width: 100%;
    padding: 12px 20px;
    border: 2px solid color-mix(in srgb, var(--accent-color), transparent 80%);
    border-radius: 12px;
    background: transparent;
    color: var(--accent-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.demo-mode-btn:hover {
    background: color-mix(in srgb, var(--accent-color), transparent 95%);
    border-color: var(--accent-color);
    color: var(--accent-color);
    text-decoration: none;
}

/* Footer */
.login-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
}

.login-footer p {
    margin: 0;
    font-size: 14px;
    color: color-mix(in srgb, var(--default-color), transparent 20%);
}

.register-link {
    color: var(--accent-color);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.register-link:hover {
    color: color-mix(in srgb, var(--accent-color), #6366f1 30%);
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 20px;
    border: 1px solid;
}

.alert-danger {
    background: color-mix(in srgb, #dc3545, transparent 90%);
    border-color: color-mix(in srgb, #dc3545, transparent 70%);
    color: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
    .login-form-card {
        padding: 30px 25px;
        margin: 20px;
    }
    
    .form-header h3 {
        font-size: 24px;
    }
    
    .login-section {
        padding: 40px 0;
    }
}

/* CSS Variables (matching the course page design) */
:root {
    --background-color: #f8f9fa;
    --surface-color: #ffffff;
    --accent-color: #667eea;
    --contrast-color: #ffffff;
    --heading-color: #333333;
    --default-color: #666666;
}
</style>

<?php get_footer(); ?>