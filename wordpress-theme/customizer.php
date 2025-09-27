<?php
/**
 * Theme Customizer
 *
 * @package Clarity_AWS_GHL
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 */
function clarity_customize_register($wp_customize) {
    
    // Hero Section
    $wp_customize->add_section('clarity_hero_section', array(
        'title'    => __('Hero Section', 'clarity-aws-ghl'),
        'priority' => 30,
    ));
    
    // Hero Title
    $wp_customize->add_setting('hero_title', array(
        'default'           => 'Transform Your Digital Presence',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('hero_title', array(
        'label'   => __('Hero Title', 'clarity-aws-ghl'),
        'section' => 'clarity_hero_section',
        'type'    => 'text',
    ));
    
    // Hero Description
    $wp_customize->add_setting('hero_description', array(
        'default'           => 'We create innovative digital solutions that drive growth and elevate your brand.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('hero_description', array(
        'label'   => __('Hero Description', 'clarity-aws-ghl'),
        'section' => 'clarity_hero_section',
        'type'    => 'textarea',
    ));
    
    // Hero Image
    $wp_customize->add_setting('hero_image', array(
        'default'           => get_template_directory_uri() . '/assets/img/misc/misc-16.webp',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'hero_image', array(
        'label'   => __('Hero Image', 'clarity-aws-ghl'),
        'section' => 'clarity_hero_section',
    )));
    
    // About Section
    $wp_customize->add_section('clarity_about_section', array(
        'title'    => __('About Section', 'clarity-aws-ghl'),
        'priority' => 35,
    ));
    
    // About Subtitle
    $wp_customize->add_setting('about_subtitle', array(
        'default'           => 'Discover Our Story',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('about_subtitle', array(
        'label'   => __('About Subtitle', 'clarity-aws-ghl'),
        'section' => 'clarity_about_section',
        'type'    => 'text',
    ));
    
    // About Title
    $wp_customize->add_setting('about_title', array(
        'default'           => 'Innovative Solutions for a Digital-First World',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('about_title', array(
        'label'   => __('About Title', 'clarity-aws-ghl'),
        'section' => 'clarity_about_section',
        'type'    => 'text',
    ));
    
    // Contact Information
    $wp_customize->add_section('clarity_contact_section', array(
        'title'    => __('Contact Information', 'clarity-aws-ghl'),
        'priority' => 40,
    ));
    
    // Address
    $wp_customize->add_setting('clarity_address', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('clarity_address', array(
        'label'   => __('Address', 'clarity-aws-ghl'),
        'section' => 'clarity_contact_section',
        'type'    => 'text',
    ));
    
    // Phone
    $wp_customize->add_setting('clarity_phone', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('clarity_phone', array(
        'label'   => __('Phone', 'clarity-aws-ghl'),
        'section' => 'clarity_contact_section',
        'type'    => 'text',
    ));
    
    // Email
    $wp_customize->add_setting('clarity_email', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('clarity_email', array(
        'label'   => __('Email', 'clarity-aws-ghl'),
        'section' => 'clarity_contact_section',
        'type'    => 'email',
    ));
    
    // Social Media
    $wp_customize->add_section('clarity_social_section', array(
        'title'    => __('Social Media', 'clarity-aws-ghl'),
        'priority' => 45,
    ));
    
    // Twitter
    $wp_customize->add_setting('clarity_twitter', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('clarity_twitter', array(
        'label'   => __('Twitter URL', 'clarity-aws-ghl'),
        'section' => 'clarity_social_section',
        'type'    => 'url',
    ));
    
    // Facebook
    $wp_customize->add_setting('clarity_facebook', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('clarity_facebook', array(
        'label'   => __('Facebook URL', 'clarity-aws-ghl'),
        'section' => 'clarity_social_section',
        'type'    => 'url',
    ));
    
    // Instagram
    $wp_customize->add_setting('clarity_instagram', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('clarity_instagram', array(
        'label'   => __('Instagram URL', 'clarity-aws-ghl'),
        'section' => 'clarity_social_section',
        'type'    => 'url',
    ));
    
    // LinkedIn
    $wp_customize->add_setting('clarity_linkedin', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('clarity_linkedin', array(
        'label'   => __('LinkedIn URL', 'clarity-aws-ghl'),
        'section' => 'clarity_social_section',
        'type'    => 'url',
    ));
}
add_action('customize_register', 'clarity_customize_register');