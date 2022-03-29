<?php

/**
 * Plugin Name: EnlightenedImages
 * Description: Generate image alt text automatically with machine learning. 
 * Version: 1.0
 * Author: Ben Upham
 * Text Domain: enlightenedimages
 * License: GPLv2 or later
 */

require_once dirname(__FILE__) . '/src/class-sisa-plugin.php';
require_once dirname(__FILE__) . '/src/class-azure-client.php';
require_once dirname(__FILE__) . '/src/class-pro-client.php';

global $sisa_plugin;

$sisa_plugin = Sisa::getInstance();
