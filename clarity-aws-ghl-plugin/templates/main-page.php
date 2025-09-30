<?php
/**
 * MainPage Template - Landing Page with Dynamic Backgrounds
 * 
 * Based on Clarity template with dynamic hero backgrounds
 * This template is designed to be used within WordPress shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get background settings
$bg_type = get_option('clarity_hero_bg_type', 'default');
$custom_image = get_option('clarity_hero_custom_image', '');
$image_position = get_option('clarity_hero_image_position', 'center center');

// Enqueue necessary scripts and styles
wp_enqueue_script('jquery');
wp_enqueue_script('clarity-main-page', CLARITY_AWS_GHL_PLUGIN_URL . 'assets/js/main-page.js', array('jquery'), CLARITY_AWS_GHL_VERSION, true);
wp_enqueue_style('clarity-main-page', CLARITY_AWS_GHL_PLUGIN_URL . 'assets/css/main-page.css', array(), CLARITY_AWS_GHL_VERSION);

// Localize script for image inventory
wp_localize_script('clarity-main-page', 'clarityMainPage', array(
    'netlifyUrl' => 'https://fractional-real-estate.netlify.app',
    'bgType' => $bg_type,
    'customImage' => $custom_image,
    'imagePosition' => $image_position,
    'slideInterval' => 5000, // 5 seconds
));

// Background settings are managed through WordPress Admin > AWS GHL > Hero Background

// Start output buffering to capture template content
ob_start();
?>

<!-- Header -->
<header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

        <a href="<?php echo home_url(); ?>" class="logo d-flex align-items-center me-auto me-xl-0">
            <!-- Uncomment the line below if you also wish to use an image logo -->
            <!-- <img src="assets/img/logo.webp" alt=""> -->
            <h1 class="sitename"><?php echo get_bloginfo('name', 'display'); ?></h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="#hero" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#courses">Courses</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li class="dropdown">
                    <a href="#"><span>Student Portal</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                    <ul>
                        <li><a href="/student-registration/">Register</a></li>
                        <li><a href="/student-login/">Login</a></li>
                        <li><a href="/student-dashboard/">Dashboard</a></li>
                        <li class="dropdown">
                            <a href="#"><span>Course Tiers</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            <ul>
                                <li><a href="#tier-free">Free Course</a></li>
                                <li><a href="#tier-core">Core Product ($497)</a></li>
                                <li><a href="#tier-premium">Premium Access ($1997)</a></li>
                            </ul>
                        </li>
                        <li><a href="/support/">Support</a></li>
                    </ul>
                </li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="/student-registration/">Get Started</a>

    </div>
</header>

<!-- Hero Section with Dynamic Background -->
<section id="hero" class="hero section" data-bg-type="<?php echo esc_attr($bg_type); ?>">
    
    <!-- Background Container -->
    <div class="hero-background-container">
        <!-- Default Background Elements -->
        <div class="hero-bg-elements <?php echo $bg_type === 'default' ? 'active' : ''; ?>">
            <div class="bg-shape shape-1"></div>
            <div class="bg-shape shape-2"></div>
            <div class="bg-particles"></div>
        </div>
        
        <!-- Slideshow Background -->
        <div class="hero-slideshow-container <?php echo $bg_type === 'slideshow' ? 'active' : ''; ?>">
            <div class="slideshow-images"></div>
            <div class="slideshow-overlay"></div>
        </div>
        
        <!-- Custom Image Background -->
        <div class="hero-custom-bg <?php echo $bg_type === 'custom' ? 'active' : ''; ?>"
             <?php if ($bg_type === 'custom' && $custom_image): ?>
                style="background-image: url('<?php echo esc_url($custom_image); ?>'); 
                       background-position: <?php echo esc_attr($image_position); ?>;"
             <?php endif; ?>>
            <div class="custom-bg-overlay"></div>
        </div>
    </div>

    <!-- Hero Content -->
    <div class="container hero-content-wrapper">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
                <div class="hero-content">
                    <h1>Transform Your Skills with Our Course Platform</h1>
                    <p>Join thousands of students who are mastering new skills through our comprehensive three-tier learning system. From free introductory content to premium mentorship programs.</p>
                    <div class="hero-buttons">
                        <a href="/student-registration/" class="btn btn-primary">Start Learning Free</a>
                        <a href="#courses" class="btn btn-outline">Explore Courses</a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number purecounter" data-purecounter-start="0" 
                                  data-purecounter-end="<?php echo get_student_count(); ?>" 
                                  data-purecounter-duration="0"><?php echo get_student_count(); ?></span>
                            <span class="stat-label">Active Students</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number purecounter" data-purecounter-start="0" 
                                  data-purecounter-end="<?php echo get_completion_rate(); ?>" 
                                  data-purecounter-duration="0"><?php echo get_completion_rate(); ?></span>
                            <span class="stat-label">Completion Rate</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number purecounter" data-purecounter-start="0" 
                                  data-purecounter-end="3" data-purecounter-duration="0">3</span>
                            <span class="stat-label">Course Tiers</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                <div class="hero-visual">
                    <div class="hero-image">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/misc/misc-16.webp" 
                             alt="Course Platform Hero" class="img-fluid">
                        <div class="floating-elements">
                            <div class="floating-card course-card-1">
                                <div class="card-icon">🎓</div>
                                <div class="card-text">Free Course</div>
                            </div>
                            <div class="floating-card course-card-2">
                                <div class="card-icon">💼</div>
                                <div class="card-text">Core Product</div>
                            </div>
                            <div class="floating-card course-card-3">
                                <div class="card-icon">⭐</div>
                                <div class="card-text">Premium</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- Background controls are now available in WordPress Admin under AWS GHL > Hero Background -->

<!-- Additional sections will go here -->
<section id="about" class="section">
    <div class="container">
        <h2>More sections coming soon...</h2>
        <p>We'll add more sections to this page as we build it out.</p>
    </div>
</section>

<?php
// Helper methods for stats
if (!function_exists('get_student_count')) {
    function get_student_count() {
        $students = get_users(array('role' => 'clarity_student'));
        return count($students);
    }
}

if (!function_exists('get_completion_rate')) {
    function get_completion_rate() {
        // Calculate average completion rate
        return 87; // Placeholder - implement actual calculation
    }
}

// Get the buffered content and clean the buffer
$template_content = ob_get_clean();

// Return the content for shortcode use
return $template_content;
?>