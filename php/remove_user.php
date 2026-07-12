<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('delete_user', __NAMESPACE__ . '\onUserDelete');
/**
 * Function to handle user deletion and update daily message schedule accordingly
 *
 * @param int $userId The ID of the user being deleted
 */
function onUserDelete($userId)
{
    $messageSchedule    = new MessageSchedule();

    $messageSchedule->delete($userId);
}
