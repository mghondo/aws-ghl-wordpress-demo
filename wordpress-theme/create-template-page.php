<?php
/**
 * Create a test page with the MainPage template
 */

// Load WordPress
$wp_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Could not find wp-load.php\n");
}

// Delete the old page if it exists
$existing_page = get_page_by_path('mainpage-template-test');
if ($existing_page) {
    wp_delete_post($existing_page->ID, true);
    echo "Deleted existing test page\n";
}

// Create new page with MainPage template
$page_data = array(
    'post_title'    => 'MainPage Template Test',
    'post_name'     => 'mainpage-template-test',
    'post_content'  => 'This page uses the MainPage template. The content here is ignored - the template provides all the HTML.',
    'post_status'   => 'publish',
    'post_type'     => 'page',
    'post_author'   => 1,
    'comment_status' => 'closed',
    'ping_status'   => 'closed'
);

$page_id = wp_insert_post($page_data);

if (!is_wp_error($page_id)) {
    // Set the custom template
    update_post_meta($page_id, '_wp_page_template', 'page-mainpage.php');
    
    echo "SUCCESS: MainPage template test page created with ID: " . $page_id . "\n";
    echo "View it at: " . get_permalink($page_id) . "\n";
    echo "Template assigned: page-mainpage.php\n";
} else {
    echo "ERROR: Failed to create page - " . $page_id->get_error_message() . "\n";
}
?>