<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
  die;
}

// delete_option('sisa_api_key');
// delete_option('sisa_pro_api_key');
// delete_option('sisa_pro_api_key');

global $wpdb;

$wpdb->query(
  "
  DELETE FROM $wpdb->options
  WHERE option_name LIKE 'sisa_%'
"
);
