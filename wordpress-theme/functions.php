<?php
/**
 * Clarity AWS GHL Integration Theme Functions
 *
 * @package Clarity_AWS_GHL
 */

// Define theme constants
define('CLARITY_THEME_VERSION', '1.0.0');
define('CLARITY_THEME_DIR', get_template_directory());
define('CLARITY_THEME_URI', get_template_directory_uri());

/**
 * Theme Setup
 */
function clarity_theme_setup() {
    // Add theme support features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Add HTML5 support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'clarity-aws-ghl'),
        'mobile'  => __('Mobile Menu', 'clarity-aws-ghl'),
    ));
    
    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');
}
add_action('after_setup_theme', 'clarity_theme_setup');

/**
 * Add custom page templates to the dropdown
 */
function clarity_add_page_templates($templates) {
    $templates['page-mainpage.php'] = 'MainPage';
    return $templates;
}
add_filter('theme_page_templates', 'clarity_add_page_templates');

/**
 * Enqueue styles and scripts
 */
function clarity_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'clarity-google-fonts',
        'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Quicksand:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );
    
    // Vendor CSS
    wp_enqueue_style('bootstrap', CLARITY_THEME_URI . '/assets/vendor/bootstrap/css/bootstrap.min.css', array(), '5.3.8');
    wp_enqueue_style('bootstrap-icons', CLARITY_THEME_URI . '/assets/vendor/bootstrap-icons/bootstrap-icons.css', array(), CLARITY_THEME_VERSION);
    wp_enqueue_style('aos', CLARITY_THEME_URI . '/assets/vendor/aos/aos.css', array(), CLARITY_THEME_VERSION);
    wp_enqueue_style('swiper', CLARITY_THEME_URI . '/assets/vendor/swiper/swiper-bundle.min.css', array(), CLARITY_THEME_VERSION);
    wp_enqueue_style('glightbox', CLARITY_THEME_URI . '/assets/vendor/glightbox/css/glightbox.min.css', array(), CLARITY_THEME_VERSION);
    
    // Main theme CSS
    wp_enqueue_style('clarity-main', CLARITY_THEME_URI . '/assets/css/main.css', array(), CLARITY_THEME_VERSION);
    
    // Theme style.css (required for WordPress)
    wp_enqueue_style('clarity-style', get_stylesheet_uri(), array(), CLARITY_THEME_VERSION);
    
    // Vendor JS
    wp_enqueue_script('bootstrap', CLARITY_THEME_URI . '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js', array(), '5.3.8', true);
    wp_enqueue_script('aos', CLARITY_THEME_URI . '/assets/vendor/aos/aos.js', array(), CLARITY_THEME_VERSION, true);
    wp_enqueue_script('purecounter', CLARITY_THEME_URI . '/assets/vendor/purecounter/purecounter_vanilla.js', array(), CLARITY_THEME_VERSION, true);
    wp_enqueue_script('swiper', CLARITY_THEME_URI . '/assets/vendor/swiper/swiper-bundle.min.js', array(), CLARITY_THEME_VERSION, true);
    wp_enqueue_script('glightbox', CLARITY_THEME_URI . '/assets/vendor/glightbox/js/glightbox.min.js', array(), CLARITY_THEME_VERSION, true);
    wp_enqueue_script('imagesloaded', CLARITY_THEME_URI . '/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js', array(), CLARITY_THEME_VERSION, true);
    wp_enqueue_script('isotope', CLARITY_THEME_URI . '/assets/vendor/isotope-layout/isotope.pkgd.min.js', array('imagesloaded'), CLARITY_THEME_VERSION, true);
    
    // Main theme JS
    wp_enqueue_script('clarity-main', CLARITY_THEME_URI . '/assets/js/main.js', array('bootstrap'), CLARITY_THEME_VERSION, true);
    
    // Localize script for AJAX if needed
    wp_localize_script('clarity-main', 'clarity_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('clarity_ajax_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'clarity_enqueue_assets');

/**
 * Register widget areas
 */
function clarity_widgets_init() {
    register_sidebar(array(
        'name'          => __('Footer Widget Area 1', 'clarity-aws-ghl'),
        'id'            => 'footer-1',
        'description'   => __('Add widgets here for footer column 1.', 'clarity-aws-ghl'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget Area 2', 'clarity-aws-ghl'),
        'id'            => 'footer-2',
        'description'   => __('Add widgets here for footer column 2.', 'clarity-aws-ghl'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget Area 3', 'clarity-aws-ghl'),
        'id'            => 'footer-3',
        'description'   => __('Add widgets here for footer column 3.', 'clarity-aws-ghl'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget Area 4', 'clarity-aws-ghl'),
        'id'            => 'footer-4',
        'description'   => __('Add widgets here for footer column 4.', 'clarity-aws-ghl'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'clarity_widgets_init');

/**
 * Custom Walker for Bootstrap Navigation
 */
class Clarity_Bootstrap_Walker extends Walker_Nav_Menu {
    function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"dropdown-menu\">\n";
    }
    
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // Check if item has children
        $has_children = in_array('menu-item-has-children', $classes);
        
        if ($has_children) {
            $classes[] = 'dropdown';
        }
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= $indent . '<li' . $id . $class_names .'>';
        
        $attributes  = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
        $attributes .= ! empty($item->target)     ? ' target="' . esc_attr($item->target     ) .'"' : '';
        $attributes .= ! empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn        ) .'"' : '';
        $attributes .= ! empty($item->url)        ? ' href="'   . esc_attr($item->url        ) .'"' : '';
        
        if ($has_children && $depth === 0) {
            $attributes .= ' class="dropdown-toggle" data-bs-toggle="dropdown"';
        }
        
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        
        if ($has_children) {
            $item_output .= ' <i class="bi bi-chevron-down toggle-dropdown"></i>';
        }
        
        $item_output .= '</a>';
        $item_output .= $args->after;
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}

/**
 * AWS and GoHighLevel Integration Functions Placeholder
 * These will be expanded as we build out the integration
 */

// AWS S3 Integration placeholder
function clarity_aws_s3_init() {
    // AWS S3 configuration will go here
}

// GoHighLevel Webhook handler placeholder
function clarity_ghl_webhook_handler() {
    // GHL webhook processing will go here
}

// Add AJAX handlers for future integrations
add_action('wp_ajax_clarity_ghl_sync', 'clarity_ghl_sync_handler');
add_action('wp_ajax_nopriv_clarity_ghl_sync', 'clarity_ghl_sync_handler');

function clarity_ghl_sync_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'clarity_ajax_nonce')) {
        wp_die('Security check failed');
    }
    
    // Integration logic will go here
    
    wp_die();
}

/**
 * Include Theme Customizer
 */
require get_template_directory() . '/customizer.php';

/**
 * Include AWS S3 Integration
 */
require get_template_directory() . '/includes/class-aws-s3-integration.php';
require get_template_directory() . '/includes/aws-s3-functions.php';

/**
 * Include GoHighLevel Webhook Integration
 */
require get_template_directory() . '/includes/class-ghl-webhook.php';