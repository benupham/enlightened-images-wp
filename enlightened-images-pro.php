<?php

/**
 * Plugin Name: EnlightenedImages Pro
 * Description: Use the power of machine learning to add alt text and index the Media Library by content, emotions, in-image-text and more.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: smartimagesearch
 * License: GPLv2 or later
 */

function sisa_check_free_version()
{
  if (class_exists('Sisa')) {
    add_action('admin_notices', 'sisa_deactivate_free_notice');
  } else {
    require_once dirname(__FILE__) . '/src/class-sisa-wp-base.php';
    require_once dirname(__FILE__) . '/src/class-sisa-pro-plugin.php';
    require_once dirname(__FILE__) . '/src/class-gcv-client.php';
    require_once dirname(__FILE__) . '/src/class-pro-client.php';
    $sisa_plugin = new SisaPro();
  }
}

add_action('plugins_loaded', 'sisa_check_free_version');


function sisa_deactivate_free_notice()
{
?>
  <div class="notice notice-error is-dismissible">
    <p><?php echo sprintf(__('You need to deactivate the free version of the EnlightenedImages plugin on the plugins page for the Pro version to work. %splugins page%s', 'smartimagesearch'), '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;plugin=enlightened-images%2Fenlightened-images.php&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_enlightened-images/enlightened-images.php') . '">', '</a>'); ?></p>

  </div>
<?php
}
