<?php

/**
 * Plugin Name: Smart Image Search AI
 * Description: Use the power of machine learning to index the images in the Media Library by content, emotions, in-image-text and more.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: smartimagesearch
 * License: GPLv2 or later
 */

require dirname(__FILE__) . '/src/class-sisa-wp-base.php';
require dirname(__FILE__) . '/src/class-sisa-image.php';
require dirname(__FILE__) . '/src/class-sisa-settings.php';
require dirname(__FILE__) . '/src/class-sisa-plugin.php';
require dirname(__FILE__) . '/src/class-gcv-client.php';
require dirname(__FILE__) . '/src/class-azure-client.php';

$sis_plugin = new SmartImageSearch();
// $sis_plugin->delete_all_sisa_meta();
