<?php
/**
 * Template Name: Student Registration
 * 
 * Custom registration page with auto-enrollment in Tier 1 course
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    wp_redirect(home_url('/course/real-estate-foundations'));
    exit;
}

// Handle registration form submission
$registration_errors = array();
$registration_success = false;

if (isset($_POST['register_student'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['register_nonce'], 'student_registration')) {
        $registration_errors[] = 'Security check failed. Please try again.';
    } else {
        // Sanitize inputs
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms_accepted = isset($_POST['terms_accepted']);

        // Validation
        if (empty($first_name)) {
            $registration_errors[] = 'First name is required.';
        }
        if (empty($last_name)) {
            $registration_errors[] = 'Last name is required.';
        }
        if (empty($email) || !is_email($email)) {
            $registration_errors[] = 'Please enter a valid email address.';
        }
        if (empty($username)) {
            $registration_errors[] = 'Username is required.';
        }
        if (username_exists($username)) {
            $registration_errors[] = 'Username already exists. Please choose another.';
        }
        if (email_exists($email)) {
            $registration_errors[] = 'An account with this email already exists.';
        }
        if (empty($password) || strlen($password) < 8) {
            $registration_errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirm_password) {
            $registration_errors[] = 'Passwords do not match.';
        }
        if (!$terms_accepted) {
            $registration_errors[] = 'You must accept the Terms & Conditions.';
        }

        // If no errors, create the user
        if (empty($registration_errors)) {
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $registration_errors[] = 'Registration failed: ' . $user_id->get_error_message();
            } else {
                // Update user meta with first and last name
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'display_name', $first_name . ' ' . $last_name);
                
                // Auto-enroll in Tier 1 course
                global $wpdb;
                $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
                
                // Suppress database errors temporarily to prevent output before redirect
                $wpdb->suppress_errors(true);
                
                // Check if user is already enrolled
                $existing_enrollment = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $enrollments_table WHERE user_id = %d AND course_id = %d",
                    $user_id, 1
                ));
                
                if (!$existing_enrollment) {
                    $enrollment_result = $wpdb->insert(
                        $enrollments_table,
                        array(
                            'user_id' => $user_id,
                            'course_id' => 1, // Tier 1 course ID (Real Estate Foundations)
                            'enrollment_date' => current_time('mysql'),
                            'enrollment_status' => 'active',
                            'payment_status' => 'paid',
                            'payment_amount' => 0.00,
                            'progress_percentage' => 0
                        ),
                        array('%d', '%d', '%s', '%s', '%s', '%f', '%d')
                    );

                    if ($enrollment_result === false) {
                        error_log('Failed to create enrollment record for user: ' . $user_id . ' - Error: ' . $wpdb->last_error);
                    }
                } else {
                    error_log('User ' . $user_id . ' is already enrolled in course 1');
                }
                
                // Re-enable database errors
                $wpdb->suppress_errors(false);

                // Check if this email exists in prospects table and link them
                $contacts_table = $wpdb->prefix . 'clarity_ghl_contacts';
                $contact_result = $wpdb->update(
                    $contacts_table,
                    array('wp_user_id' => $user_id),
                    array('email' => $email, 'wp_user_id' => null),
                    array('%d'),
                    array('%s', '%d')
                );

                // Auto-login the new user
                $creds = array(
                    'user_login' => $username,
                    'user_password' => $password,
                    'remember' => true
                );
                
                $user = wp_signon($creds, false);
                
                if (!is_wp_error($user)) {
                    // Successful registration and login - redirect BEFORE any output
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    wp_redirect(home_url('/course/real-estate-foundations?welcome=1'));
                    exit;
                } else {
                    // Account created but auto-login failed - still redirect to prevent re-submission
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    wp_redirect(home_url('/login?registered=1'));
                    exit;
                }
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
                        <h2 class="auth-title">Start Your Real Estate Journey</h2>
                        <p class="auth-subtitle">Create your account and get instant access to our foundation course</p>
                        <hr>
                        <p class="auth-subtitle">Already have an account? <a href="<?php echo home_url('/login'); ?>">Sign In</a></p>
                    </div>
                    <!-- <div class="auth-footer">
                        <p>Already have an account? <a href="<?php echo home_url('/login'); ?>">Sign In</a></p>
                    </div> -->
                    <?php if (!empty($registration_errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($registration_errors as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="auth-form" id="registration-form">
                        <?php wp_nonce_field('student_registration', 'register_nonce'); ?>
                        
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value=""
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value=""
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value=""
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value=""
                                   required>
                            <small class="form-text text-muted">Choose a unique username for your account</small>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <small class="form-text text-muted">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="terms_accepted" 
                                   name="terms_accepted" 
                                   required>
                            <label class="form-check-label" for="terms_accepted">
                                I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" name="register_student" class="btn btn-primary btn-auth">
                            Create Account & Start Learning
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="<?php echo home_url('/login'); ?>">Sign In</a></p>
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

.form-check-label a {
    color: #667eea;
    text-decoration: none;
}

.form-check-label a:hover {
    text-decoration: underline;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registration-form');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    // Real-time password validation
    function validatePasswords() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (confirmPassword && password !== confirmPassword) {
            confirmPasswordField.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordField.setCustomValidity('');
        }
    }
    
    passwordField.addEventListener('input', validatePasswords);
    confirmPasswordField.addEventListener('input', validatePasswords);
});
</script>

<?php
get_footer();
?>