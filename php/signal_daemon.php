<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addPrayerResponse', 10, 6);
function addPrayerResponse($response, $message, $source, $users, $name, $signal){
    if(str_starts_with($message, 'update prayer correct')){
        $response['message']    = updatePrayerRequest($message, $users, $signal);
    }
    
    elseif(str_starts_with($message, 'update prayer')){
        $response['message']    = checkPrayerRequestToUpdate($message, $users, $signal);
    }elseif(str_contains($message, 'prayer') && $name){
        $prayerRequest  = prayerRequest(true, true);
        $response['message']    = "This is the prayer for today:\n\n{$prayerRequest['message']}";
        $response['pictures']   = $prayerRequest['pictures'];
    }

    return $response;
}

function updatePrayerRequest($message, $users, $signal){
    // mark as updated for affected users
    foreach($users as $user){
        $replacementData    = get_user_meta($user->ID, 'pending-prayer-update-data', true);
        
        delete_user_meta($user->ID, 'pending-prayer-update-data');

        if(empty($replacementData)){
            continue;
        }
    }

    if(empty($replacementData)){
        return 'Something went wrong';
    }

    $post               = get_post($replacementData['post-id']);

    if(empty($post)){
        return "Post with id '{$replacementData['post-id']}' not found";
    }

    $post->post_content = $replacementData['replacement'];
    
    // do the actual replacement
    wp_update_post(
        $post,
        false,
        false
    );

    return "Updated your prayer request for '{$replacementData['date']}'\n\nto:\n'{$replacementData['replacement']}'";
}

function checkPrayerRequestToUpdate($message, $users, $signal){
    foreach($users as $user){
        // get the prayer request to be replaced
        $prayer         = get_post(
            [
                'post_type' => 'prayer',
                'post_author' => $user->ID
            ]
        );
        
        if($prayer){
            break;
        }
    }
    $prayer         = apply_filters('sim-prayer-request-to-update', $prayer, $replaceDate, $message);

    if(!$prayer){
        return "Could not find prayer request to update for $replaceDate";
    }

    $prayerMessage  = trim($prayer->post_content);

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update prayer', '', $message));

    foreach($users as $user){
        update_user_meta(
            $user->ID, 
            'pending-prayer-update-data', 
            [
                'replacement'   => $replacetext,
                'post-id'       => $prayer['post']
            ]
        );
    }

	return "I am going to replace:\n'$prayerMessage'\n\nwith\n'$replacetext'\n\nReply with 'update prayer correct' if I should continue";
}