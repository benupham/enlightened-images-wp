<?php

/**
 * Plugin Name: EnlightenedImages Pro
 * Description: Adds bonus functionality to the free EnlightenedImages plugin, including background alt text generation on upload.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: enlightenedimages
 * License: not public domain
 */

function sisa_check_free_version()
{
  if (!class_exists('Sisa')) {
    add_action('admin_notices', 'sisa_activate_free_notice');
  } else {
    require_once dirname(__FILE__) . '/src/class-sisa-pro-plugin.php';
  }
}

add_action('plugins_loaded', 'sisa_check_free_version');


function sisa_activate_free_notice()
{
?>
  <div class="notice notice-error is-dismissible">
    <p><?php echo __('You need to activate the free version of the EnlightenedImages plugin for the Pro version to work. %splugins page%s', 'enlightenedimages'); ?></p>

  </div>
<?php
}
