<?php

/**
 * Plugin Name: Enlightened Images Pro
 * Description: Adds bonus functionality to the free Enlightened Images plugin, including background alt text generation on upload.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: enlightenedimages
 * License: not public domain
 */

function elim_check_free_version()
{
  if (!class_exists('EnlightenedImages_Plugin')) {
    add_action('admin_notices', 'elim_activate_free_notice');
  } else {
    require_once dirname(__FILE__) . '/src/class-elim-pro-plugin.php';
  }
}

add_action('plugins_loaded', 'elim_check_free_version');


function elim_activate_free_notice()
{
?>
  <div class="notice notice-error is-dismissible">
    <p><?php echo __('You need to install and activate the free version of the EnlightenedImages plugin for the Pro version to work.', 'enlightenedimages'); ?></p>

  </div>
<?php
}
