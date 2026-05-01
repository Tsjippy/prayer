<?php
namespace TSJIPPY\PRAYER;

/**
 * Plugin Name:  		Tsjippy Prayer
 * Description:  		This plugin adds 1 post category: 'Prayer' You should add a new post with the prayer category each month. This post should have a prayer request for each day on seperate lines. The lines should have this format: '1(T) – ' So an example will look like this: <code> 1(M) – Prayer for day 1 2(T) – Prayer for day 2 </code> If such a post is available the daily prayerrequest will be displayed on the homepage and will be available via the rest-api.
 * Version:      		10.0.0
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.3
 * Requires PHP: 		8.3
 * Tested up to: 		6.9
 * Plugin URI:			https://github.com/Tsjippy/prayer
 * Tested:				6.9
 * TextDomain:			tsjippy
 * Requires Plugins:	tsjippy-shared-functionality, tsjippy-signal
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pluginData = get_plugin_data(__FILE__, false, false);

// Define constants
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGINPATH', __DIR__.'/');
define(__NAMESPACE__ .'\PLUGINVERSION', $pluginData['Version']);
define(__NAMESPACE__ .'\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ .'\SETTINGS', get_option('tsjippy_'.PLUGINSLUG.'_settings', []));

