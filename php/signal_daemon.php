<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-signal-daemon-response', __NAMESPACE__ . '\addMessageResponse', 10, 6);
/**
 * Add pdaily message to the signal daemon response
 *
 * @param array  $response The signal daemon response
 * @param string $message  The message received
 * @param string $source   The source of the message
 * @param array  $users    The users associated with the message
 * @param string $name     The name of the user
 * @param object $signal   The signal object
 *
 * @return array The modified response
 */
function addMessageResponse($response, $message, $source, $users, $name, $signal)
{
    if ($response['message'] != 'I have no clue, do you know?') {
        return $response;
    }

    $lowerMessage = strtolower($message);

    if (str_starts_with($message, 'update message correct')) {
        $response['message']    = updateMessage($message, $users, $signal);
    } elseif (str_starts_with($lowerMessage, 'update message')) {
        $response['message']    = checkMessageToUpdate($message, $users, $signal);
    } elseif (str_contains($lowerMessage, 'message') && $name) {
        $dailyMessage  = getDailyMessage(true, true);
        $response['message']    = "This is the message for today:\n\n{$dailyMessage['message']}";
        $response['pictures']   = $dailyMessage['pictures'];
    }

    return $response;
}

/**
 * Update a daily message request
 *
 * @param string $message The message received
 * @param array $users The users associated with the message
 * @param object $signal The signal object
 *
 * @return string The response message
 */
function updateMessage($message, $users, $signal)
{
    // mark as updated for affected users
    foreach ($users as $user) {
        $replacementData    = get_user_meta($user->ID, 'tsjippy_pending-message-update-data', true);

        delete_user_meta($user->ID, 'tsjippy_pending-message-update-data');

        if (empty($replacementData)) {
            continue;
        }
    }

    if (empty($replacementData)) {
        return 'Something went wrong';
    }

    $post               = get_post($replacementData['post-id']);

    if (empty($post)) {
        return "Post with id '{$replacementData['post-id']}' not found";
    }

    $post->post_content = $replacementData['replacement'];

    // do the actual replacement
    wp_update_post(
        [
            'ID'            => $post->ID,
            'post_content'  => $replacementData['replacement']
        ],
        false,
        false
    );

    $date   = gmdate(TSJIPPY\DATEFORMAT, strtotime($replacementData['date']));
    return "Updated your message for $date\n\nto:\n'{$replacementData['replacement']}'";
}

/**
 * Check a message to update
 *
 * @param string $message The message received
 * @param array $users The users associated with the message
 * @param object $signal The signal object
 *
 * @return string The response message
 */
function checkMessageToUpdate($message, $users, $signal)
{
    $dailyMessages    = false;

    foreach ($users as $user) {
        // get the message to be replaced
        $dailyMessages        = get_posts(
            [
                'post_type'     => 'daily-message',
                'meta_key'      => 'user-id',
                'meta_value'    => $user->ID
            ]
        );

        if ($dailyMessages) {
            break;
        }
    }

    if (!$dailyMessages) {
        return "Could not find any daily message to update for you, sorry";
    }

    $dailyMessage  = trim($dailyMessages[0]->post_content);

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update message', '', $message));

    if (empty($replacetext)) {
        return "You did not supply me with the new message ";
    }

    if ($replacetext == $dailyMessage) {
        return "The message is already just as you want";
    }

    foreach ($users as $user) {
        update_user_meta(
            $user->ID,
            'pending-message-update-data',
            [
                'replacement'   => $replacetext,
                'post-id'       => $dailyMessages[0]->ID,
                'date'          => get_post_meta($dailyMessages[0]->ID, 'tsjippy_date', true)
            ]
        );
    }

    return "I am going to replace:\n'$dailyMessage'\n\nwith\n'$replacetext'\n\nReply with 'update message correct' if I should continue";
}
