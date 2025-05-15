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
    // perform the replacement
    if($message == 'update prayer correct'){
        // mark as updated for affected users
        foreach($users as $user){
            delete_user_meta($user->ID, 'pending-prayer-update');

            $replacementData    = get_user_meta($user->ID, 'pending-prayer-update-data', true);
            
            delete_user_meta($user->ID, 'pending-prayer-update-data');

            if(empty($replacementData)){
                continue;
            }
        }

        if(empty($replacementData)){
            return 'Something went wrong';
        }

        $post               = get_post($replacementData['post_id']);

        if(empty($post)){
            return "Post with id '{$replacementData->post_id}' not found";
        }

        $post->post_content = str_replace($replacementData['original'], $replacementData['replacement'], $post->post_content, $count);
        
        // do the actual replacement
        wp_update_post(
            $post,
            false,
            false
        );

        return "Replaced:\n'{$replacementData['message']}'\n\nwith:\n'{$replacementData['replacement']}'";
    }


    foreach($users as $user){
        clean_user_cache($user);

        $timeStamp      = get_user_meta($user->ID, 'pending-prayer-update', true);

        if(!$timeStamp || !is_numeric($timeStamp)){
            continue;
        }

        break;
    }

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
    $prayer         = apply_filters('sim-prayer-request-to-update', $prayer, $replaceDate, $message);

    if(!$prayer){
        return "Could not find prayer request to update for $replaceDate";
    }

    $prayerMessage  = trim($prayer['message']);

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update prayer', '', $message));

    foreach($users as $user){
        update_user_meta(
            $user->ID, 
            'pending-prayer-update-data', 
            [
                'original'      => $prayer['html'],
                'message'       => $prayerMessage,
                'replacement'   => $replacetext,
                'post_id'       => $prayer['post']
            ]
        );
    }

	return "I am going to replace:\n'$prayerMessage'\n\nwith\n'$replacetext'\n\nReply with 'update prayer correct' if I should continue";
}