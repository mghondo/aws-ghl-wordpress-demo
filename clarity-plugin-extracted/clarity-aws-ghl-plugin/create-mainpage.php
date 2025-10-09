<?php
/**
 * Force create MainPage
 * Run this file to immediately create the MainPage
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if page already exists
$existing_page = get_page_by_path('mainpage');

if ($existing_page) {
    echo "MainPage already exists with ID: " . $existing_page->ID . "\n";
    echo "View it at: " . get_permalink($existing_page->ID) . "\n";
} else {
    // Create the page
    $page_data = array(
        'post_title'    => 'MainPage',
        'post_name'     => 'mainpage',
        'post_content'  => '[clarity_main_page]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => 1,
        'comment_status' => 'closed',
        'ping_status'   => 'closed'
    );
    
    $page_id = wp_insert_post($page_data);
    
    if (!is_wp_error($page_id)) {
        echo "SUCCESS: MainPage created with ID: " . $page_id . "\n";
        echo "View it at: " . get_permalink($page_id) . "\n";
    } else {
        echo "ERROR: Failed to create MainPage - " . $page_id->get_error_message() . "\n";
    }
}