  </main>

  <footer id="footer" class="footer position-relative dark-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="logo d-flex align-items-center">
            <span class="sitename"><?php bloginfo('name'); ?></span>
          </a>
          <div class="footer-contact pt-3">
            <?php if (get_theme_mod('clarity_address')) : ?>
              <p><?php echo esc_html(get_theme_mod('clarity_address')); ?></p>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_city_state')) : ?>
              <p><?php echo esc_html(get_theme_mod('clarity_city_state')); ?></p>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_phone')) : ?>
              <p class="mt-3"><strong><?php esc_html_e('Phone:', 'clarity-aws-ghl'); ?></strong> <span><?php echo esc_html(get_theme_mod('clarity_phone')); ?></span></p>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_email')) : ?>
              <p><strong><?php esc_html_e('Email:', 'clarity-aws-ghl'); ?></strong> <span><?php echo esc_html(get_theme_mod('clarity_email')); ?></span></p>
            <?php endif; ?>
          </div>
          <div class="social-links d-flex mt-4">
            <?php if (get_theme_mod('clarity_twitter')) : ?>
              <a href="<?php echo esc_url(get_theme_mod('clarity_twitter')); ?>"><i class="bi bi-twitter-x"></i></a>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_facebook')) : ?>
              <a href="<?php echo esc_url(get_theme_mod('clarity_facebook')); ?>"><i class="bi bi-facebook"></i></a>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_instagram')) : ?>
              <a href="<?php echo esc_url(get_theme_mod('clarity_instagram')); ?>"><i class="bi bi-instagram"></i></a>
            <?php endif; ?>
            <?php if (get_theme_mod('clarity_linkedin')) : ?>
              <a href="<?php echo esc_url(get_theme_mod('clarity_linkedin')); ?>"><i class="bi bi-linkedin"></i></a>
            <?php endif; ?>
          </div>
        </div>

        <?php if (is_active_sidebar('footer-1')) : ?>
          <div class="col-lg-2 col-md-3 footer-links">
            <?php dynamic_sidebar('footer-1'); ?>
          </div>
        <?php endif; ?>

        <?php if (is_active_sidebar('footer-2')) : ?>
          <div class="col-lg-2 col-md-3 footer-links">
            <?php dynamic_sidebar('footer-2'); ?>
          </div>
        <?php endif; ?>

        <?php if (is_active_sidebar('footer-3')) : ?>
          <div class="col-lg-2 col-md-3 footer-links">
            <?php dynamic_sidebar('footer-3'); ?>
          </div>
        <?php endif; ?>

        <?php if (is_active_sidebar('footer-4')) : ?>
          <div class="col-lg-2 col-md-3 footer-links">
            <?php dynamic_sidebar('footer-4'); ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span><?php esc_html_e('Copyright', 'clarity-aws-ghl'); ?></span> <?php echo date('Y'); ?> <strong class="px-1 sitename"><?php bloginfo('name'); ?></strong> <span>All Rights Reserved Morgo LLC.</span></p>
      <p class="credits">
        <?php
        printf(
          esc_html__('Powered by %1$s | Theme by %2$s', 'clarity-aws-ghl'),
          '<a href="https://wordpress.org/">WordPress</a>',
          '<a href="' . esc_url(home_url('/')) . '">' . get_bloginfo('name') . '</a>'
        );
        ?>
      </p>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <?php wp_footer(); ?>

</body>

</html>