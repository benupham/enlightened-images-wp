<?php

/**
 * PLUGIN_NAME
 * Description: Use the power of machine learning to add alt text and index the Media Library by content, emotions, in-image-text and more.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: smartimagesearch
 * License: GPLv2 or later
 */

require_once dirname(__FILE__) . '/src/class-sisa-wp-base.php';
require_once dirname(__FILE__) . '/src/class-sisa-pro-plugin.php';
require_once dirname(__FILE__) . '/src/class-gcv-client.php';
require_once dirname(__FILE__) . '/src/class-pro-client.php';

function sisa_deactivate_free_notice()
{
?>
  <div class="notice notice-error is-dismissible">
    <p><?php echo __('You need to deactivate the free version of the EnlightenedImages plugin on the plugins page', 'smartimagesearch'); ?></p>
  </div>
<?php
}

function sisa_pro_activation()
{
  if (class_exists('Sisa')) {
    add_action('admin_notices', 'sisa_deactivate_free_notice');
    return;
  }
  $sis_plugin = new SisaPro();
}
add_action('plugins_loaded', 'sisa_pro_activation');
