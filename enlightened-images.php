<?php

/**
 * Plugin Name: Enlightened Images
 * Description: Generate image alt text automatically with machine learning. 
 * Version: 1.1
 * Author: Ben Upham
 * Text Domain: enlightenedimages
 * License: GPLv2 or later
 */

require_once dirname(__FILE__) . '/src/class-elim-plugin.php';
require_once dirname(__FILE__) . '/src/class-azure-client.php';
require_once dirname(__FILE__) . '/src/class-pro-client.php';

global $elim_plugin;

$elim_plugin = EnlightenedImages_Plugin::getInstance();
