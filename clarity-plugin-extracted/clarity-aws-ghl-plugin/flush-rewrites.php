<?php
/**
 * Flush WordPress rewrite rules
 */

// Load WordPress from the correct path
$wp_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../wordpress/wp-load.php'
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
    echo "Could not find wp-load.php. Trying to flush via database...\n";
    
    // Alternative: Force WordPress to regenerate rewrite rules by deleting them
    $config = [
        'host' => 'db',
        'user' => 'wordpress', 
        'password' => 'wordpress',
        'database' => 'wordpress'
    ];
    
    try {
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']}", 
                       $config['user'], $config['password']);
        
        // Delete rewrite rules to force regeneration
        $stmt = $pdo->prepare("DELETE FROM wp_options WHERE option_name = 'rewrite_rules'");
        $stmt->execute();
        
        echo "Rewrite rules cleared. WordPress will regenerate them on next request.\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    // Flush rewrite rules
    flush_rewrite_rules();
    echo "WordPress rewrite rules flushed successfully!\n";
    
    // Also try to get the permalink for MainPage
    $page = get_page_by_path('mainpage');
    if ($page) {
        echo "MainPage permalink: " . get_permalink($page->ID) . "\n";
    }
}
?>