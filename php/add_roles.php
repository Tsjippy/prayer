<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-user-management-role-description', __NAMESPACE__ . '\roleDescription', 10, 2);

/**
 * Filters the role description
 * 
 * @param string $description  The description of a user role
 * @param string $role         The role slug
 */
function roleDescription($description, $role)
{
    if ($role == 'message-coordinator') {
        return 'Ability to publish messages';
    }

    return $description;
}
