<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addPrayerResponse', 10, 6);
function addPrayerResponse($response, $message, $source, $users, $name, $signal){
    if($response['message'] != 'I have no clue, do you know?'){
        return $response;
    }

    $lowerMessage = strtolower($message);

    if(str_starts_with($message, 'update prayer correct')){
        $response['message']    = updatePrayerRequest($message, $users, $signal);
    }
    
    elseif(str_starts_with($lowerMessage, 'update prayer')){
        $response['message']    = checkPrayerRequestToUpdate($message, $users, $signal);
    }elseif(str_contains($lowerMessage, 'prayer') && $name){
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
        [
            'ID'            => $post->ID,
            'post_content'  => $replacementData['replacement']
        ],
        false,
        false
    );

    $date   = date(DATEFORMAT, strtotime($replacementData['date']));
    return "Updated your prayer request for $date\n\nto:\n'{$replacementData['replacement']}'";
}

function checkPrayerRequestToUpdate($message, $users, $signal){
    foreach($users as $user){
        // get the prayer request to be replaced
        $prayerRequests        = get_posts(
            [
                'post_type'     => 'prayer-request',
                'meta_key'      => 'user-id',
                'meta_value'    => $user->ID
            ]
        );
        
        if($prayerRequests){
            break;
        }
    }

    if(!$prayerRequests){
        return "Could not find prayer request to update for you, sorry";
    }

    $prayerMessage  = trim($prayerRequests[0]->post_content);

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update prayer', '', $message));

    if(empty($replacetext)){
        return "You did not supply me with the new prayer request.";
    }

    if($replacetext == $prayerMessage){
        return "The prayer message is already just as you want";
    }

    foreach($users as $user){
        update_user_meta(
            $user->ID, 
            'pending-prayer-update-data', 
            [
                'replacement'   => $replacetext,
                'post-id'       => $prayerRequests[0]->ID,
                'date'          => get_post_meta($prayerRequests[0]->ID, 'date', true)
            ]
        );
    }

	return "I am going to replace:\n'$prayerMessage'\n\nwith\n'$replacetext'\n\nReply with 'update prayer correct' if I should continue";
}