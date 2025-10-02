<?php
/**
 * Header template - starts output buffering to prevent redirect issues
 */
if (!ob_get_level()) {
    ob_start();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  
  <!-- Favicons -->
  <link href="<?php echo get_template_directory_uri(); ?>/assets/img/favicon.png" rel="icon">
  <link href="<?php echo get_template_directory_uri(); ?>/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <?php wp_head(); ?>
  
  <style>
  /* User Menu Styling */
  .user-menu {
    position: relative;
  }
  
  .btn-user {
    background: transparent;
    border: 2px solid var(--accent-color, #667eea);
    border-radius: 25px;
    padding: 8px 16px;
    color: var(--accent-color, #667eea);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
  }
  
  .btn-user:hover {
    background: var(--accent-color, #667eea);
    color: white;
  }
  
  .btn-user i {
    font-size: 18px;
  }
  
  .btn-getstarted {
    background: transparent;
    border: 2px solid var(--accent-color, #667eea);
    border-radius: 25px;
    padding: 8px 16px;
    color: var(--accent-color, #667eea);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
  }
  
  .btn-getstarted:hover {
    background: var(--accent-color, #667eea);
    color: white;
    text-decoration: none;
  }
  
  .btn-getstarted i {
    font-size: 16px;
  }
  
  .dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 8px 0;
    margin-top: 8px;
  }
  
  .dropdown-item {
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    transition: background-color 0.2s ease;
  }
  
  .dropdown-item:hover {
    background-color: #f8f9fa;
    color: var(--accent-color, #667eea);
  }
  
  .dropdown-item i {
    font-size: 16px;
    width: 20px;
  }
  
  .dropdown-divider {
    margin: 8px 0;
  }
  
  @media (max-width: 768px) {
    .btn-user,
    .btn-getstarted {
      padding: 6px 12px;
      font-size: 14px;
    }
    
    .btn-user span,
    .btn-getstarted span {
      display: none;
    }
  }
  </style>
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

      <?php if (is_user_logged_in()) : ?>
        <?php $current_user = wp_get_current_user(); ?>
        <div class="user-menu dropdown">
          <button class="btn-user dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
            <?php echo esc_html($current_user->display_name); ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="<?php echo home_url('/dashboard'); ?>">
              <i class="bi bi-speedometer2"></i> My Dashboard
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo wp_logout_url(home_url()); ?>">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
          </ul>
        </div>
      <?php else : ?>
        <a class="btn-getstarted" href="<?php echo home_url('/login'); ?>">
          <i class="bi bi-person"></i> Login
        </a>
      <?php endif; ?>

    </div>
  </header>

  <main class="main">