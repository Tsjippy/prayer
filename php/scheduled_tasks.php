<?php

namespace TSJIPPY\DAILYMESSAGE;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\scheduleTasks');
function scheduleTasks()
{
    TSJIPPY\scheduleTask('tsjippy-send-daily-message', 'quarterly', __NAMESPACE__, 'sendDailyMessage');

    TSJIPPY\scheduleTask('tsjippy-check-daily-message', 'daily', __NAMESPACE__, 'checkDailyMessage');
}

/**
 * We will send the daily message based on the times as given by people
 * As we are not sure about the timeliness of the cron schedule we keep
 * a seperate schedule for each day to be sure everyone gets what they requested
 */
function sendDailyMessage()
{
    $dailyMessage    = getDailyMessage(true, true);

    $message         = (SETTINGS['message-prepend'] ?? '') . "\n";
    $message         .= $dailyMessage['message'];

    // Get the schedule for today
    $messageSchedule  = new MessageSchedule();
    $schedule        = $messageSchedule->getTodaySchedule();

    $time    = current_time('H:i');
    foreach ($schedule as $t => $recipients) {
        if (
            !is_array($recipients) ||    // Recipients should always be an array
            $t > $time                    // Do not continue for times in the future
        ) {
            continue;
        }

        // Remove the curent entry from todays schedule to indicate we have processed it
        unset($schedule[$t]);

        $dayPart    = "morning";
        $hour        = current_time('H');
        if ($hour > 11 && $hour < 18) {
            $dayPart    = 'afternoon';
        } elseif ($hour > 17) {
            $dayPart    = 'evening';
        } elseif ($hour < 4) {
            $dayPart    = 'night';
        }

        foreach ($recipients as $recipient) {
            $userName    = '';
            if (is_numeric($recipient)) {
                $userdata    = get_userdata($recipient);

                if (!$userdata) {
                    continue;
                }

                $userName    = ' ' . $userdata->first_name;
            }

            // make this available through an action to be used by the signal plugin, potentially others
            do_action(
                'tsjippy-daily-message-send',
                "Good $dayPart$userName,\n\n$message",
                $recipient,
                $dailyMessage['pictures']
            );
        }
    }

    $date            = \gmdate('y-m-d');
    if (empty($schedule)) {
        delete_option("daily_message_schedule_$date");
    } else {
        update_option("daily_message_schedule_$date", $schedule);
    }
}

/**
 * Check if a message needs an update
 */
function checkDailyMessage()
{
    // Get the amount of days between this check and the actual publishing
    $days            = SETTINGS['messagecheck'] ?? [];
    if (empty($days)) {
        return;
    }

    // Get the actual message this warning is for
    $dateTime   = strtotime("+$days day", time());
    $dateString = gmdate(TSJIPPY\DATEFORMAT, $dateTime);

    $dailyMessages = get_posts(
        array(
            'post_type'   => 'daily-message',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'     => 'tsjippy_date',
                    'value'   => gmdate('Y-m-d', $dateTime)
                ),
                array(
                    'key'     => 'tsjippy_user-id',
                    'compare' => 'EXISTS',
                ),
            )
        )
    );

    // loop over all found mesages for the date with users attached to it.
    foreach ($dailyMessages as $dailyMessage) {
        $message         = strip_tags($dailyMessage->post_content);

        if (empty($message)) {
            continue;
        }

        $signalMessage    = "Good day %name%, $days days from now your message will be sent out.\n\nPlease reply to me with an updated version if needed.\n\nThis is the request I have now:\n\n$message\n\nIt will be sent on $dateString\n\nStart your reply with 'update message'";

        foreach (get_post_meta($dailyMessage->ID, 'tsjippy_user-id') as $userId) {
            $user = get_userdata($userId);
            $msg  = str_replace('%name%', $user->first_name, $signalMessage);

            // make this available through an action to be used by the signal plugin, potentially others
            do_action(
                'tsjippy-daily-message-send',
                $msg,
                $user
            );
        }
    }
}
