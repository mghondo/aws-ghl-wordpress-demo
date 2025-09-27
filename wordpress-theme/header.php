<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  
  <!-- Favicons -->
  <link href="<?php echo get_template_directory_uri(); ?>/assets/img/favicon.png" rel="icon">
  <link href="<?php echo get_template_directory_uri(); ?>/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="<?php echo esc_url(home_url('/')); ?>" class="logo d-flex align-items-center me-auto me-xl-0">
        <?php if (has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <h1 class="sitename"><?php bloginfo('name'); ?></h1>
        <?php endif; ?>
      </a>

      <nav id="navmenu" class="navmenu">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => '',
          'fallback_cb'    => false,
          'walker'         => new Clarity_Bootstrap_Walker(),
          'depth'          => 3,
        ));
        ?>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted" href="#about"><?php esc_html_e('Get Started', 'clarity-aws-ghl'); ?></a>

    </div>
  </header>

  <main class="main">