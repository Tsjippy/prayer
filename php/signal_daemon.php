<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addPrayerResponse', 10, 6);
function addPrayerResponse($response, $message, $source, $users, $name, $signal){
    if(str_starts_with($message, 'update prayer')){
        $response['message']    = updatePrayerRequest($message, $users, $signal);
    }elseif(str_contains($message, 'prayer') && $name){
        $prayerRequest  = prayerRequest(true, true);
        $response['message']    = "This is the prayer for today:\n\n{$prayerRequest['message']}";
        $response['pictures']   = $prayerRequest['pictures'];
    }

    return $response;
}

function updatePrayerRequest($message, $users, $signal){
    $timeStamp      = get_user_meta($users[0]->ID, 'pending-prayer-update', true);
    if(!$timeStamp || !is_numeric($timeStamp)){
        return "You do not have a pending prayer request";
    }

    $sendMessage    = $signal->getSendMessageByTimestamp($timeStamp);

    if(!preg_match_all("/[\d]{2}-[\d]{2}-[\d]{4}/m", $sendMessage, $matches, PREG_SET_ORDER, 0)){
        return "Not sure which prayer request is pending for you";
    }

    $replaceDate	= $matches[0][0];

    // get the prayer request to be replaced
    $prayer         = prayerRequest(false, false, $replaceDate);
    $prayer         = apply_filters('sim-prayer-request-to-update', $prayer, $replaceDate);

    if(!$prayer){
        return "Could not find prayer request to update for $replaceDate";
    }

    $prayerMessage = trim($prayer['message']);

    // perform the replacement
    if($message == 'update prayer correct'){
        foreach($users as $user){
            delete_user_meta($user->ID, 'pending-prayer-update');
        }

        $replacetext    = get_user_meta($user->ID, 'pending-prayer-update-text', true);
        delete_user_meta($user->ID, 'pending-prayer-update-text');

        if(empty($replacetext)){
            return 'Something went wrong';
        }

        $post               = get_post($prayer['post']);

        if(empty($post)){
            return 'no post found to replace in'.implode(';', $prayer);
        }

        $post->post_content = str_replace($prayerMessage, $replacetext, $post->post_content);
        // do the actual replacement
        wp_update_post(
            $post,
            false,
            false
        );

        return "Replaced:\n'$prayerMessage'\n\nwith:\n'$replacetext'";
    }

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update prayer', '', $message));

    foreach($users as $user){
        update_user_meta($user->ID, 'pending-prayer-update-text', $replacetext);
    }

	return "I am going to replace:\n'$prayerMessage'\n\nwith\n'$replacetext'\n\nReply with 'update prayer correct' if I should continue";
}