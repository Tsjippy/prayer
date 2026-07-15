<?php

namespace TSJIPPY\DAILYMESSAGE;

/**
 * Plugin Name:          Tsjippy Daily Message
 * Description:          This plugin adds the daily-message post type. Use it to display daily messages on frontend or send thrue e-mail or signal.
 * Version:              10.5.9
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         7.0
 * Plugin URI:           https://github.com/Tsjippy/daily-message
 * Tested:               7.0
 * TextDomain:           tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_' . PLUGINSLUG . '_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    // Load shared code
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }
    
    $roleSet = get_role('contributor')->capabilities;

    // Only add the new role if it does not exist
    if (!wp_roles()->is_role('message-coordinator')) {
        add_role(
            'message-coordinator',
            'Message coordinator',
            $roleSet
        );
    }

    // Create db
    $messageSchedule    = new MessageSchedule();
    $messageSchedule->createDbTables();

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});

