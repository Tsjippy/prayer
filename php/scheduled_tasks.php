<?php

namespace TSJIPPY\PRAYER;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\init');
function init()
{
    //add action for use in scheduled task
    add_action('send_prayer_action', __NAMESPACE__ . '\sendPrayerRequests');

    //add action for use in scheduled task
    add_action('check_prayer_action', __NAMESPACE__ . '\checkPrayerRequests');
}

function scheduleTasks()
{
    TSJIPPY\scheduleTask('send_prayer_action', 'quarterly');

    TSJIPPY\scheduleTask('check_prayer_action', 'daily');
}

/**
 * We will send the prayer request based on the times as given by people
 * As we are not sure about the timeliness of the cron schedule we keep
 * a seperate schedule for each day to be sure everyone gets what they requested
 */
function sendPrayerRequests()
{
    $prayerRequest    = prayerRequest(true, true);

    $message         = "The prayer request of today is:\n";
    $message         .= $prayerRequest['message'];

    // Get the schedule for today
    $prayerSchedule = new PrayerSchedule();
    $schedule        = $prayerSchedule->getTodaySchedule();

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
                'tsjippy-prayer-send-message',
                "Good $dayPart$userName,\n\n$message",
                $recipient,
                $prayerRequest['pictures']
            );
        }
    }

    $date            = \gmdate('y-m-d');
    if (empty($schedule)) {
        delete_option("prayer_schedule_$date");
    } else {
        update_option("prayer_schedule_$date", $schedule);
    }
}

/**
 * Check if a prayer request needs an update
 */
function checkPrayerRequests()
{
    // Get the amount of days between this check and the actual publishing
    $days            = SETTINGS['prayercheck'] ?? [];
    if (empty($days)) {
        return;
    }

    // Get the actual prayer request this warning is for
    $dateTime        = strtotime("+$days day", time());
    $dateString        = gmdate(TSJIPPY\DATEFORMAT, $dateTime);

    $prayerRequests = get_posts(
        array(
            'post_type'        => 'prayer-request',
            'post_status'      => 'publish',
            'numberposts'    => -1,
            'meta_query'    => array(
                'relation' => 'AND',
                array(
                    'key'     => 'date',
                    'value'   => gmdate('Y-m-d', $dateTime)
                ),
                array(
                    'key'     => 'user-id',
                    'compare' => 'EXISTS',
                ),
            )
        )
    );

    // loop over all found prayer requests for the date with users attached to it.
    foreach ($prayerRequests as $prayerRequest) {
        $message         = strip_tags($prayerRequest->post_content);

        if (empty($message)) {
            continue;
        }

        $signalMessage    = "Good day %name%, $days days from now your prayer request will be sent out.\n\nPlease reply to me with an updated request if needed.\n\nThis is the request I have now:\n\n$message\n\nIt will be sent on $dateString\n\nStart your reply with 'update prayer'";

        foreach (get_post_meta($prayerRequest->ID, 'tsjippy_user-id') as $userId) {
            $user        = get_userdata($userId);
            $msg        = str_replace('%name%', $user->first_name, $signalMessage);

            // make this available through an action to be used by the signal plugin, potentially others
            do_action(
                'tsjippy-prayer-send-message',
                $msg,
                $user
            );
        }
    }
}
