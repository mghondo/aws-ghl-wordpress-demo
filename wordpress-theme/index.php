<?php
/**
 * The main template file
 *
 * @package Clarity_AWS_GHL
 */

get_header(); ?>

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
            <div class="hero-content">
              <h1><?php echo esc_html(get_theme_mod('hero_title', 'Transform Your Digital Presence')); ?></h1>
              <p><?php echo esc_html(get_theme_mod('hero_description', 'We create innovative digital solutions that drive growth and elevate your brand. From web development to digital marketing, we\'re your partners in digital transformation.')); ?></p>
              <div class="hero-buttons">
                <a href="<?php echo esc_url(get_theme_mod('hero_button_primary_url', '#')); ?>" class="btn btn-primary"><?php echo esc_html(get_theme_mod('hero_button_primary_text', 'Get Started')); ?></a>
                <a href="<?php echo esc_url(get_theme_mod('hero_button_secondary_url', '#')); ?>" class="btn btn-outline"><?php echo esc_html(get_theme_mod('hero_button_secondary_text', 'Our Work')); ?></a>
              </div>
              <div class="hero-stats">
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="<?php echo esc_attr(get_theme_mod('hero_stat_projects', '150')); ?>" data-purecounter-duration="1"></span>
                  <span class="stat-label"><?php esc_html_e('Projects Completed', 'clarity-aws-ghl'); ?></span>
                </div>
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="<?php echo esc_attr(get_theme_mod('hero_stat_satisfaction', '95')); ?>" data-purecounter-duration="1"></span>
                  <span class="stat-label"><?php esc_html_e('Client Satisfaction', 'clarity-aws-ghl'); ?></span>
                </div>
                <div class="stat-item">
                  <span class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="<?php echo esc_attr(get_theme_mod('hero_stat_team', '24')); ?>" data-purecounter-duration="1"></span>
                  <span class="stat-label"><?php esc_html_e('Team Members', 'clarity-aws-ghl'); ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
            <div class="hero-visual">
              <div class="hero-image">
                <?php 
                $hero_image = get_theme_mod('hero_image', get_template_directory_uri() . '/assets/img/misc/misc-16.webp');
                ?>
                <img src="<?php echo esc_url($hero_image); ?>" alt="<?php esc_attr_e('Digital Agency Hero', 'clarity-aws-ghl'); ?>" class="img-fluid">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-bg-elements">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
        <div class="bg-particles"></div>
      </div>

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5 align-items-center">

          <div class="col-lg-5" data-aos="fade-right" data-aos-delay="200">
            <div class="content">
              <h6 class="subtitle"><?php echo esc_html(get_theme_mod('about_subtitle', 'Discover Our Story')); ?></h6>
              <h2><?php echo esc_html(get_theme_mod('about_title', 'Innovative Solutions for a Digital-First World')); ?></h2>
              <p><?php echo wp_kses_post(get_theme_mod('about_description', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.')); ?></p>

              <?php
              $about_features = get_theme_mod('about_features');
              if (!empty($about_features)) :
                $features = explode("\n", $about_features);
              ?>
                <ul class="features-list">
                  <?php foreach ($features as $feature) : ?>
                    <li><i class="bi bi-check-circle-fill"></i><span><?php echo esc_html($feature); ?></span></li>
                  <?php endforeach; ?>
                </ul>
              <?php else : ?>
                <ul class="features-list">
                  <li><i class="bi bi-check-circle-fill"></i><span>Excepteur sint occaecat cupidatat non proident.</span></li>
                  <li><i class="bi bi-check-circle-fill"></i><span>Nemo enim ipsam voluptatem quia voluptas sit.</span></li>
                  <li><i class="bi bi-check-circle-fill"></i><span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                </ul>
              <?php endif; ?>

              <a href="<?php echo esc_url(get_theme_mod('about_button_url', '#')); ?>" class="btn btn-primary"><?php echo esc_html(get_theme_mod('about_button_text', 'Discover More')); ?></a>
            </div>
          </div>

          <div class="col-lg-7" data-aos="fade-left" data-aos-delay="300">
            <div class="image-composition">
              <div class="image-main">
                <?php 
                $about_image_main = get_theme_mod('about_image_main', get_template_directory_uri() . '/assets/img/about/about-9.webp');
                ?>
                <img src="<?php echo esc_url($about_image_main); ?>" alt="<?php esc_attr_e('Modern office with a team working', 'clarity-aws-ghl'); ?>" class="img-fluid" loading="lazy">
              </div>
              <div class="image-secondary">
                <?php 
                $about_image_secondary = get_theme_mod('about_image_secondary', get_template_directory_uri() . '/assets/img/about/about-square-8.webp');
                ?>
                <img src="<?php echo esc_url($about_image_secondary); ?>" alt="<?php esc_attr_e('Collaborative discussion', 'clarity-aws-ghl'); ?>" class="img-fluid" loading="lazy">
              </div>
              <div class="stats-card">
                <div class="stats-item">
                  <h3><?php echo esc_html(get_theme_mod('about_stat_years', '20+')); ?></h3>
                  <p><?php esc_html_e('Years of Expertise', 'clarity-aws-ghl'); ?></p>
                </div>
                <div class="stats-item">
                  <h3><?php echo esc_html(get_theme_mod('about_stat_clients', '500+')); ?></h3>
                  <p><?php esc_html_e('Happy Clients', 'clarity-aws-ghl'); ?></p>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2><?php echo esc_html(get_theme_mod('services_section_title', 'Services')); ?></h2>
        <p><?php echo esc_html(get_theme_mod('services_section_subtitle', 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit')); ?></p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">
          
          <?php
          // Query for services (can be custom post type or theme customizer)
          $services = array(
            array(
              'icon' => 'bi-palette',
              'title' => 'Brand Identity Design',
              'description' => 'Donec vel sapien augue integer urna vel turpis cursus porta aliquam ligula eget ultricies.',
              'badge' => 'Most Popular'
            ),
            array(
              'icon' => 'bi-layout-text-window-reverse',
              'title' => 'UI/UX Design',
              'description' => 'Mauris blandit aliquet elit eget tincidunt nibh pulvinar rutrum tellus pellentesque eu.',
              'badge' => ''
            ),
            array(
              'icon' => 'bi-code-slash',
              'title' => 'Web Development',
              'description' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.',
              'badge' => ''
            ),
            array(
              'icon' => 'bi-phone',
              'title' => 'Mobile App Design',
              'description' => 'Nulla facilisi morbi tempus iaculis urna id volutpat lacus laoreet non curabitur gravida.',
              'badge' => ''
            ),
            array(
              'icon' => 'bi-megaphone',
              'title' => 'Digital Marketing',
              'description' => 'Sed porttitor lectus nibh donec sollicitudin molestie malesuada proin eget tortor risus.',
              'badge' => ''
            ),
            array(
              'icon' => 'bi-search',
              'title' => 'SEO Optimization',
              'description' => 'Curabitur arcu erat accumsan id imperdiet et porttitor at sem pellentesque habitant morbi.',
              'badge' => ''
            )
          );
          
          $delay = 200;
          foreach ($services as $service) : ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
              <div class="service-card">
                <div class="service-icon">
                  <i class="bi <?php echo esc_attr($service['icon']); ?>"></i>
                </div>
                <h4><a href="#"><?php echo esc_html($service['title']); ?></a></h4>
                <p><?php echo esc_html($service['description']); ?></p>
                <?php if (!empty($service['badge'])) : ?>
                  <div class="service-badge"><?php echo esc_html($service['badge']); ?></div>
                <?php endif; ?>
                <a href="#" class="service-link">
                  <span><?php esc_html_e('Learn More', 'clarity-aws-ghl'); ?></span>
                  <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          <?php 
          $delay += 100;
          endforeach; ?>

        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- Portfolio Section (Placeholder for now) -->
    <section id="portfolio" class="portfolio section">
      <div class="container section-title" data-aos="fade-up">
        <h2><?php esc_html_e('Portfolio', 'clarity-aws-ghl'); ?></h2>
        <p><?php esc_html_e('Check our Portfolio', 'clarity-aws-ghl'); ?></p>
      </div>
      <!-- Portfolio items will be added later -->
    </section><!-- /Portfolio Section -->

    <!-- Contact Section (Basic for now) -->
    <section id="contact" class="contact section">
      <div class="container section-title" data-aos="fade-up">
        <h2><?php esc_html_e('Contact', 'clarity-aws-ghl'); ?></h2>
        <p><?php esc_html_e('Get in touch with us', 'clarity-aws-ghl'); ?></p>
      </div>
      <!-- Contact form will be added later -->
    </section><!-- /Contact Section -->

<?php get_footer(); ?>