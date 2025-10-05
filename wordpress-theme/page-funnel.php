<?php
/**
 * Template Name: Course Funnel Page
 * 
 * Displays course funnel content with sales pitch and enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get course slug from query var or global
global $clarity_course_slug;
$course_slug = get_query_var('course_slug') ?: $clarity_course_slug;

// Fallback: parse from URL if not available via rewrite
if (empty($course_slug)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path_parts = explode('/', trim($request_uri, '/'));
    if (count($path_parts) >= 2 && $path_parts[0] === 'funnel') {
        $course_slug = sanitize_title($path_parts[1]);
    }
}

$course_slug = sanitize_title($course_slug);

// If no slug found, redirect to home
if (empty($course_slug)) {
    wp_redirect(home_url());
    exit;
}

// Get course data
global $wpdb;
$courses_table = $wpdb->prefix . 'clarity_courses';
$course = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$courses_table} WHERE course_slug = %s AND course_status = 'published'",
    $course_slug
));

// Initialize course routing
$course_routing = new Clarity_AWS_GHL_Course_Routing();

// If course not found, show 404
if (!$course) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit;
}

// Check if user is enrolled
$is_enrolled = false;
$enrollment_status = '';
if (is_user_logged_in()) {
    $enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
    $enrollment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$enrollments_table} WHERE user_id = %d AND course_id = %d",
        get_current_user_id(),
        $course->id
    ));
    
    if ($enrollment) {
        $is_enrolled = true;
        $enrollment_status = $enrollment->enrollment_status;
    }
}

get_header();
?>

<div class="funnel-container">
    <div class="container">
        <?php 
        // Show welcome message for email leads
        if (isset($_GET['ref']) && ($_GET['ref'] === 'email-lead' || $_GET['ref'] === 'contact')): 
        ?>
        <div class="welcome-message">
            <div class="alert alert-success">
                <h4><i class="bi bi-gift"></i> Welcome! Here's Your Free Course</h4>
                <p>As promised, here's FREE access to our Real Estate Foundations course. Simply create your account below to get started!</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <div class="funnel-hero">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="funnel-hero-content">
                        <div class="course-tier-badge">
                            <span class="tier-badge tier-<?php echo esc_attr($course->course_tier); ?>">
                                <i class="bi <?php echo esc_attr($course->course_icon ?: 'bi-mortarboard'); ?>"></i>
                                Tier <?php echo esc_html($course->course_tier); ?>
                            </span>
                        </div>
                        
                        <h1 class="funnel-title"><?php echo esc_html($course->course_title); ?></h1>
                        
                        <div class="course-meta">
                            <div class="price-display">
                                <?php if ($course->course_price == 0): ?>
                                    <span class="price-free">FREE</span>
                                <?php else: ?>
                                    <span class="price-amount">$<?php echo number_format($course->course_price, 0); ?></span>
                                    <span class="price-label">One-time payment</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Enrollment Status or CTA -->
                        <div class="funnel-cta">
                            <?php 
                            // Get smart CTA configuration
                            $cta_config = $course_routing->get_funnel_cta_config($course);
                            
                            if ($cta_config['type'] == 'enrolled'): ?>
                                <div class="enrolled-status">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i>
                                        You're enrolled in this course!
                                    </div>
                                    <a href="<?php echo esc_url($cta_config['action_url']); ?>" class="btn btn-primary btn-lg">
                                        <i class="bi <?php echo esc_attr($cta_config['button_icon']); ?>"></i>
                                        <?php echo esc_html($cta_config['button_text']); ?>
                                    </a>
                                    <?php if (!empty($cta_config['secondary_button'])): ?>
                                    <a href="<?php echo esc_url($cta_config['secondary_button']['url']); ?>" class="btn btn-outline-secondary">
                                        <i class="bi <?php echo esc_attr($cta_config['secondary_button']['icon']); ?>"></i>
                                        <?php echo esc_html($cta_config['secondary_button']['text']); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($cta_config['type'] == 'free_enroll'): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="enrollment-form">
                                    <?php wp_nonce_field('enroll_course', 'enrollment_nonce'); ?>
                                    <input type="hidden" name="action" value="enroll_in_course">
                                    <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id); ?>">
                                    <button type="submit" class="btn btn-primary btn-lg btn-enroll">
                                        <i class="bi <?php echo esc_attr($cta_config['button_icon']); ?>"></i>
                                        <?php echo esc_html($cta_config['button_text']); ?>
                                    </button>
                                </form>
                            <?php elseif ($cta_config['type'] == 'paid_checkout'): ?>
                                <a href="<?php echo esc_url($cta_config['action_url']); ?>" class="btn btn-primary btn-lg btn-enroll">
                                    <i class="bi <?php echo esc_attr($cta_config['button_icon']); ?>"></i>
                                    <?php echo esc_html($cta_config['button_text']); ?>
                                </a>
                            <?php elseif (in_array($cta_config['type'], ['free_register', 'paid_register'])): ?>
                                <div class="login-prompt">
                                    <p class="lead">Ready to start your real estate journey?</p>
                                    <?php 
                                    // Build registration URL with proper redirect parameters
                                    $register_url = $cta_config['action_url'];
                                    if (!empty($cta_config['checkout_course'])) {
                                        $register_url = add_query_arg('checkout_course', $cta_config['checkout_course'], $register_url);
                                    }
                                    $register_url = add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), $register_url);
                                    ?>
                                    <a href="<?php echo esc_url($register_url); ?>" class="btn btn-primary btn-lg">
                                        <i class="bi <?php echo esc_attr($cta_config['button_icon']); ?>"></i>
                                        <?php echo esc_html($cta_config['button_text']); ?>
                                    </a>
                                    <a href="<?php echo home_url('/login?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-person"></i>
                                        Already have an account? Login
                                    </a>
                                </div>
                                <?php if (!empty($cta_config['after_register'])): ?>
                                <!-- Store registration flow info for handling after registration -->
                                <script>
                                    sessionStorage.setItem('after_register', '<?php echo $cta_config['after_register']; ?>');
                                    <?php if (!empty($cta_config['checkout_course'])): ?>
                                    sessionStorage.setItem('checkout_course', '<?php echo $cta_config['checkout_course']; ?>');
                                    <?php endif; ?>
                                </script>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <?php if (!empty($course->featured_image)): ?>
                        <div class="funnel-hero-image">
                            <img src="<?php echo esc_attr($course->featured_image); ?>" 
                                 alt="<?php echo esc_attr($course->course_title); ?>" 
                                 class="img-fluid rounded">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Funnel Content (Sales Pitch) -->
        <?php if (!empty($course->funnel_content)): ?>
        <div class="funnel-content">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="sales-content">
                        <?php 
                        // Decode HTML entities and render content properly
                        $funnel_content = wp_unslash($course->funnel_content);
                        $funnel_content = html_entity_decode($funnel_content, ENT_QUOTES, 'UTF-8');
                        $funnel_content = wp_kses_post($funnel_content);
                        echo $funnel_content;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Final CTA Section -->
        <div class="funnel-final-cta">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="cta-card">
                        <h3>Ready to Get Started?</h3>
                        <p>Join thousands of successful real estate professionals</p>
                        
                        <?php 
                        // Reuse the same CTA configuration for final CTA
                        if ($cta_config['type'] == 'enrolled'): ?>
                            <a href="<?php echo esc_url($cta_config['action_url']); ?>" class="btn btn-success btn-lg">
                                <i class="bi bi-play-circle"></i>
                                Continue Your Course
                            </a>
                        <?php elseif ($cta_config['type'] == 'free_enroll'): ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="enrollment-form">
                                <?php wp_nonce_field('enroll_course', 'enrollment_nonce'); ?>
                                <input type="hidden" name="action" value="enroll_in_course">
                                <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id); ?>">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-unlock"></i>
                                    Start Free Course
                                </button>
                            </form>
                        <?php elseif ($cta_config['type'] == 'paid_checkout'): ?>
                            <a href="<?php echo esc_url($cta_config['action_url']); ?>" class="btn btn-primary btn-lg">
                                <i class="bi <?php echo esc_attr($cta_config['button_icon']); ?>"></i>
                                <?php echo esc_html($cta_config['button_text']); ?>
                            </a>
                        <?php elseif (in_array($cta_config['type'], ['free_register', 'paid_register'])): ?>
                            <?php 
                            // Build registration URL with proper redirect parameters for final CTA
                            $final_register_url = $cta_config['action_url'];
                            if (!empty($cta_config['checkout_course'])) {
                                $final_register_url = add_query_arg('checkout_course', $cta_config['checkout_course'], $final_register_url);
                            }
                            $final_register_url = add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), $final_register_url);
                            ?>
                            <a href="<?php echo esc_url($final_register_url); ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-rocket"></i>
                                Get Started Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.funnel-container {
    padding: 40px 0 80px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.welcome-message {
    margin-bottom: 30px;
    animation: slideDown 0.5s ease-out;
}

.welcome-message .alert {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
}

.welcome-message h4 {
    color: #155724;
    margin-bottom: 15px;
    font-weight: 600;
}

.welcome-message p {
    color: #155724;
    font-size: 16px;
    margin: 0;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.funnel-hero {
    background: white;
    border-radius: 20px;
    padding: 60px 40px;
    margin-bottom: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.tier-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 20px;
}

.tier-badge.tier-1 {
    background: #d4edda;
    color: #155724;
}

.tier-badge.tier-2 {
    background: #cce5ff;
    color: #004085;
}

.tier-badge.tier-3 {
    background: #f8d7da;
    color: #721c24;
}

.funnel-title {
    font-size: 3rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    line-height: 1.2;
}

.price-display {
    margin: 20px 0;
}

.price-free {
    background: #28a745;
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: 700;
    font-size: 18px;
}

.price-amount {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
}

.price-label {
    display: block;
    color: #6c757d;
    font-size: 14px;
    margin-top: 5px;
}

.funnel-cta {
    margin-top: 30px;
}

.btn-enroll {
    padding: 15px 30px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 30px;
    margin-right: 15px;
    margin-bottom: 10px;
}

.funnel-hero-image img {
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.funnel-description,
.funnel-content {
    background: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.description-content h3 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 20px;
}

.sales-content {
    font-size: 16px;
    line-height: 1.8;
    color: #495057;
}

.sales-content h1,
.sales-content h2,
.sales-content h3 {
    color: #2c3e50;
    margin-top: 30px;
    margin-bottom: 15px;
}

.funnel-final-cta {
    margin-top: 50px;
}

.cta-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
}

.cta-card h3 {
    font-size: 2rem;
    margin-bottom: 15px;
}

.cta-card p {
    font-size: 18px;
    margin-bottom: 25px;
    opacity: 0.9;
}

.enrolled-status .alert {
    font-size: 16px;
    padding: 15px 20px;
    margin-bottom: 20px;
}

.login-prompt .lead {
    font-size: 18px;
    margin-bottom: 25px;
    color: #495057;
}

@media (max-width: 768px) {
    .funnel-hero {
        padding: 40px 20px;
    }
    
    .funnel-title {
        font-size: 2rem;
    }
    
    .price-amount {
        font-size: 2rem;
    }
    
    .btn-enroll {
        width: 100%;
        margin-bottom: 15px;
    }
    
    .funnel-description,
    .funnel-content {
        padding: 30px 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find any button in the funnel content that says "Start Building Your Empire"
    const funnelContent = document.querySelector('.funnel-content');
    if (funnelContent) {
        const buttons = funnelContent.querySelectorAll('a, button');
        buttons.forEach(function(button) {
            const buttonText = button.textContent.trim();
            if (buttonText === 'Start Building Your Empire' || 
                buttonText === 'Get Started' || 
                buttonText === 'Enroll Now' ||
                buttonText.includes('Start Your Journey')) {
                
                // Prevent default action
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get the same URL as the final CTA button uses
                    const finalCTA = document.querySelector('.funnel-final-cta .btn-primary');
                    if (finalCTA) {
                        // Use the same URL as the final CTA
                        window.location.href = finalCTA.getAttribute('href');
                    } else {
                        // Fallback: Find if there's a form to submit
                        const ctaForm = document.querySelector('.funnel-final-cta form');
                        if (ctaForm) {
                            ctaForm.submit();
                        }
                    }
                });
                
                // Add styling to make it look like a CTA button if it's not already styled
                if (button.tagName === 'A' && !button.classList.contains('btn')) {
                    button.classList.add('btn', 'btn-primary', 'btn-lg');
                    button.style.display = 'inline-block';
                    button.style.marginTop = '20px';
                }
            }
        });
    }
});
</script>

<?php get_footer(); ?>