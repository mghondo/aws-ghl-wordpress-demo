<?php
// Flush rewrite rules
require_once('wp-config.php');
flush_rewrite_rules();
delete_option('clarity_rewrite_rules_flushed');
echo 'Rewrite rules flushed successfully!';
?>
