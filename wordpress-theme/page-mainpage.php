<?php
/**
 * Template Name: MainPage
 * 
 * Full hardcoded MainPage template using Clarity template HTML/CSS
 * with dynamic background options configured via AWS GHL > Hero Background
 */

// Helper functions for stats (define before use)
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

// Get hero settings from admin
$bg_type = get_option('clarity_hero_bg_type', 'default');
$custom_image = get_option('clarity_hero_custom_image', '');
$image_position = get_option('clarity_hero_image_position', 'center center');
$hero_title = get_option('clarity_hero_title', 'Transform Your Skills with Our Course Platform');
$hero_description = get_option('clarity_hero_description', 'Join thousands of students who are mastering new skills through our comprehensive three-tier learning system. From free introductory content to premium mentorship programs.');
$hero_darkness = get_option('clarity_hero_darkness', 80);

// Get about settings from admin
$about_title = get_option('clarity_about_title', 'Innovative Learning for a Skills-First World');
$about_description = get_option('clarity_about_description', 'Our comprehensive three-tier learning system is designed to take you from beginner to expert. Whether you\'re just starting out or looking to advance your skills, we have the perfect program for you.');
$about_feature_1 = get_option('clarity_about_feature_1', 'Free introductory courses to get you started');
$about_feature_2 = get_option('clarity_about_feature_2', 'Core product with comprehensive training materials');
$about_feature_3 = get_option('clarity_about_feature_3', 'Premium access with personal mentorship');
$about_feature_4 = get_option('clarity_about_feature_4', 'Progress tracking and certificates');
$about_image = get_option('clarity_about_image', '');

// Get courses from course manager
$course_manager = new Clarity_AWS_GHL_Course_Manager();
$courses = $course_manager->get_all_courses(array('status' => 'published'));

// DEBUG: Removed forced slideshow setting - using admin panel setting

// DEBUG: Output background type for debugging
error_log('MainPage Debug - Background type: ' . $bg_type);

// Enqueue all required Clarity template assets directly
$theme_uri = get_template_directory_uri();

// Vendor CSS
wp_enqueue_style('bootstrap', $theme_uri . '/assets/vendor/bootstrap/css/bootstrap.min.css', array(), '5.3.8');
wp_enqueue_style('bootstrap-icons', $theme_uri . '/assets/vendor/bootstrap-icons/bootstrap-icons.css', array(), '1.0.0');
wp_enqueue_style('aos', $theme_uri . '/assets/vendor/aos/aos.css', array(), '3.0.0');
wp_enqueue_style('swiper', $theme_uri . '/assets/vendor/swiper/swiper-bundle.min.css', array(), '10.0.0');
wp_enqueue_style('glightbox', $theme_uri . '/assets/vendor/glightbox/css/glightbox.min.css', array(), '3.2.0');

// Main Clarity CSS
wp_enqueue_style('clarity-main', $theme_uri . '/assets/css/main.css', array('bootstrap'), '1.0.0');

// Dynamic background CSS
wp_enqueue_style('clarity-main-page-dynamic', $theme_uri . '/assets/css/main-page-dynamic.css', array('clarity-main'), '1.0.0');

// Vendor JS
wp_enqueue_script('jquery');
wp_enqueue_script('bootstrap', $theme_uri . '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js', array('jquery'), '5.3.8', true);
wp_enqueue_script('aos', $theme_uri . '/assets/vendor/aos/aos.js', array('jquery'), '3.0.0', true);
wp_enqueue_script('swiper', $theme_uri . '/assets/vendor/swiper/swiper-bundle.min.js', array('jquery'), '10.0.0', true);
wp_enqueue_script('glightbox', $theme_uri . '/assets/vendor/glightbox/js/glightbox.min.js', array('jquery'), '3.2.0', true);
wp_enqueue_script('purecounter', $theme_uri . '/assets/vendor/purecounter/purecounter_vanilla.js', array('jquery'), '1.0.0', true);

// Main Clarity JS
wp_enqueue_script('clarity-main', $theme_uri . '/assets/js/main.js', array('jquery', 'bootstrap'), '1.0.0', true);

// MainPage specific JS
wp_enqueue_script('clarity-main-page', $theme_uri . '/assets/js/main-page.js', array('jquery'), '1.0.0', true);

// Localize script for slideshow functionality
wp_localize_script('clarity-main-page', 'clarityMainPage', array(
    'netlifyUrl' => 'https://fractional-real-estate.netlify.app',
    'bgType' => $bg_type,
    'customImage' => $custom_image,
    'imagePosition' => $image_position,
    'slideInterval' => 5000,
    'themeUri' => get_template_directory_uri(),
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'heroDarkness' => $hero_darkness,
));

// Add index-page class to body for this template
add_filter('body_class', function($classes) {
    $classes[] = 'index-page';
    return $classes;
});


get_header(); ?>

    <!-- Hero Section with Dynamic Background -->
    <!-- DEBUG: Background type is: <?php echo $bg_type; ?> -->
    <section id="hero" class="hero section" data-bg-type="<?php echo esc_attr($bg_type); ?>"
             <?php if ($bg_type === 'custom' && $custom_image): ?>
                style="background-image: url('<?php echo esc_url($custom_image); ?>'); 
                       background-size: cover; background-position: <?php echo esc_attr($image_position); ?>;"
             <?php endif; ?>>

      <!-- Overlay for better text readability -->
      <?php if ($bg_type === 'slideshow' || ($bg_type === 'custom' && $custom_image)): ?>
      <div class="hero-overlay"></div>
      <?php endif; ?>

      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
            <div class="hero-content">
              <h1><?php echo esc_html($hero_title); ?></h1>
              <p><?php echo esc_html($hero_description); ?></p>
              <div class="hero-buttons">
                <a href="/register/" class="btn btn-primary">Start Learning Free</a>
                <a href="#courses" class="btn btn-outline">Explore Courses</a>
              </div>
              <div class="hero-stats">
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" 
                        data-purecounter-end="<?php echo get_student_count(); ?>" 
                        data-purecounter-duration="1"><?php echo get_student_count(); ?></span>
                  <span class="stat-label">Active Students</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" 
                        data-purecounter-end="<?php echo get_completion_rate(); ?>" 
                        data-purecounter-duration="1"><?php echo get_completion_rate(); ?></span>
                  <span class="stat-label">Completion Rate</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" 
                        data-purecounter-end="3" data-purecounter-duration="1">3</span>
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

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5 align-items-center">

          <div class="col-lg-5" data-aos="fade-right" data-aos-delay="200">
            <div class="content">
              <h6 class="subtitle">Discover Our Platform</h6>
              <h2><?php echo esc_html($about_title); ?></h2>
              <p>
                <?php echo esc_html($about_description); ?>
              </p>

              <ul class="features-list">
                <li><i class="bi bi-check-circle-fill"></i><span><?php echo esc_html($about_feature_1); ?></span></li>
                <li><i class="bi bi-check-circle-fill"></i><span><?php echo esc_html($about_feature_2); ?></span></li>
                <li><i class="bi bi-check-circle-fill"></i><span><?php echo esc_html($about_feature_3); ?></span></li>
                <li><i class="bi bi-check-circle-fill"></i><span><?php echo esc_html($about_feature_4); ?></span></li>
              </ul>

              <div class="buttons">
                <a href="/register/" class="btn btn-primary">Start Your Journey</a>
                <a href="#courses" class="btn btn-outline">Learn More</a>
              </div>
            </div>
          </div>

          <div class="col-lg-7" data-aos="fade-left" data-aos-delay="200">
            <div class="about-image">
              <img id="about-us-image" src="<?php echo $about_image ? esc_url($about_image) : get_template_directory_uri() . '/assets/img/misc/misc-6.webp'; ?>" alt="About Us" class="img-fluid">
              <div class="about-overlay">
                <div class="video-btn">
                  <a href="https://www.youtube.com/watch?v=Y7f98aduVJ8" class="glightbox play-btn"></a>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Courses Section -->
    <section id="courses" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Our Course Tiers</h2>
        <p>Choose the learning path that fits your goals and budget</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <?php if (!empty($courses)): ?>
            <?php 
            $delay = 100;
            foreach ($courses as $course): 
              $price_display = $course->course_price > 0 ? '$' . number_format($course->course_price, 0) : 'Free';
              $course_url = home_url('/course/' . $course->course_slug);
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
              <div class="service-item position-relative">
                <div class="icon">
                  <i class="bi <?php echo esc_attr($course->course_icon ?: 'bi-mortarboard'); ?>"></i>
                </div>
                <a href="<?php echo esc_url($course_url); ?>" class="stretched-link">
                  <h3><?php echo esc_html($course->course_title); ?></h3>
                </a>
                <p><?php echo esc_html($course->course_description); ?></p>
                <div class="price"><?php echo esc_html($price_display); ?></div>
              </div>
            </div><!-- End Service Item -->
            <?php 
            $delay += 100; 
            endforeach; ?>
          <?php else: ?>
            <!-- Fallback if no courses found -->
            <div class="col-12">
              <p class="text-center">No courses available at this time.</p>
            </div>
          <?php endif; ?>

        </div>

      </div>

    </section><!-- /Courses Section -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Contact</h2>
        <p>Get in touch with our team</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-6">
            <div class="info-wrap">
              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="200">
                <i class="bi bi-geo-alt flex-shrink-0"></i>
                <div>
                  <h3>Address</h3>
                  <p>A108 Adam Street, New York, NY 535022</p>
                </div>
              </div><!-- End Info Item -->

              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
                <i class="bi bi-telephone flex-shrink-0"></i>
                <div>
                  <h3>Call Us</h3>
                  <p>+1 5589 55488 55</p>
                </div>
              </div><!-- End Info Item -->

              <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-envelope flex-shrink-0"></i>
                <div>
                  <h3>Email Us</h3>
                  <p>info@example.com</p>
                </div>
              </div><!-- End Info Item -->

            </div>
          </div>

          <div class="col-lg-6">
            <form action="" method="post" class="php-email-form" id="contact-form" data-aos="fade-up" data-aos-delay="200">
              <div class="row gy-4">

                <div class="col-md-6">
                  <label for="name-field" class="pb-2">Your Name</label>
                  <input type="text" name="name" id="name-field" class="form-control" required="">
                </div>

                <div class="col-md-6">
                  <label for="email-field" class="pb-2">Your Email</label>
                  <input type="email" class="form-control" name="email" id="email-field" required="">
                </div>

                <div class="col-md-12">
                  <label for="subject-field" class="pb-2">Subject</label>
                  <input type="text" class="form-control" name="subject" id="subject-field" required="">
                </div>

                <div class="col-md-12">
                  <label for="message-field" class="pb-2">Message</label>
                  <textarea class="form-control" name="message" rows="10" id="message-field" required=""></textarea>
                </div>

                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>

                  <div class="buttons">
                    <a href="#" onclick="document.getElementById('contact-form').submit(); return false;" class="btn btn-primary" style="background: var(--accent-color); color: var(--contrast-color); border: 2px solid var(--accent-color);">Send Message</a>
                  </div>

                </div>

              </div>
            </form>
          </div><!-- End Contact Form -->

        </div>

      </div>

    </section><!-- /Contact Section -->

<?php get_footer(); ?>