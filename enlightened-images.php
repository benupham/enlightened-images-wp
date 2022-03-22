<?php

/**
 * EnlightenedImages
 * Description: Use the power of machine learning to add alt text and index the Media Library by content, emotions, in-image-text and more.
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: smartimagesearch
 * License: GPLv2 or later
 */

require dirname(__FILE__) . '/src/class-sisa-wp-base.php';
require dirname(__FILE__) . '/src/class-sisa-plugin.php';
require dirname(__FILE__) . '/src/class-gcv-client.php';
require dirname(__FILE__) . '/src/class-pro-client.php';

$sis_plugin = new Sisa();