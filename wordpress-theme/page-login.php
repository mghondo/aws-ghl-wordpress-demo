<?php
/**
 * Template Name: Student Login
 * 
 * Custom login page with WordPress authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Handle login form submission
$login_errors = array();

if (isset($_POST['login_student'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['login_nonce'], 'student_login')) {
        $login_errors[] = 'Security check failed. Please try again.';
    } else {
        // Sanitize inputs
        $username_or_email = sanitize_text_field($_POST['username_or_email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        // Validation
        if (empty($username_or_email)) {
            $login_errors[] = 'Username or email is required.';
        }
        if (empty($password)) {
            $login_errors[] = 'Password is required.';
        }

        // If no errors, attempt login
        if (empty($login_errors)) {
            // Determine if input is email or username
            $user_login = $username_or_email;
            if (is_email($username_or_email)) {
                $user = get_user_by('email', $username_or_email);
                if ($user) {
                    $user_login = $user->user_login;
                }
            }

            $creds = array(
                'user_login' => $user_login,
                'user_password' => $password,
                'remember' => $remember
            );

            $user = wp_signon($creds, false);

            if (!is_wp_error($user)) {
                // Successful login - redirect to dashboard
                wp_redirect(home_url('/dashboard'));
                exit;
            } else {
                $login_errors[] = 'Invalid username/email or password.';
            }
        }
    }
}

get_header();
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="auth-title">Welcome Back</h2>
                        <p class="auth-subtitle">Sign in to continue your real estate education</p>
                    </div>

                    <?php if (!empty($login_errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($login_errors as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="auth-form" id="login-form">
                        <?php wp_nonce_field('student_login', 'login_nonce'); ?>
                        
                        <div class="form-group">
                            <label for="username_or_email">Username or Email</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username_or_email" 
                                   name="username_or_email" 
                                   value="<?php echo isset($_POST['username_or_email']) ? esc_attr($_POST['username_or_email']) : ''; ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember" 
                                   <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" name="login_student" class="btn btn-primary btn-auth">
                            Sign In
                        </button>
                    </form>

                    <div class="auth-links">
                        <p><a href="<?php echo wp_lostpassword_url(); ?>">Forgot your password?</a></p>
                    </div>

                    <div class="auth-footer">
                        <p>Don't have an account? <a href="<?php echo home_url('/register'); ?>">Create Account</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 0;
}

.auth-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    animation: fadeInUp 0.6s ease-out;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-title {
    color: #333;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 10px;
}

.auth-subtitle {
    color: #6c757d;
    font-size: 16px;
    margin-bottom: 0;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.auth-form label {
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
}

.auth-form .form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.auth-form .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-check {
    margin: 25px 0;
}

.form-check-label {
    font-size: 14px;
    color: #6c757d;
}

.btn-auth {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    color: white;
    transition: transform 0.2s ease;
}

.btn-auth:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

.auth-links {
    text-align: center;
    margin: 20px 0;
}

.auth-links a {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
}

.auth-links a:hover {
    text-decoration: underline;
}

.auth-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.auth-footer p {
    color: #6c757d;
    margin: 0;
}

.auth-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert {
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert ul {
    list-style: none;
    padding-left: 0;
}

.alert li {
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .auth-card {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .auth-title {
        font-size: 24px;
    }
}
</style>

<?php get_footer(); ?>