<?php
namespace SIM\PRAYER;
use SIM;

function dateRegex(){
    /* $year = [
        'Y' => "20(?:0[1-9]|[12]\d)",
        'y' => "(?:0[1-9]|[12]\d)"
    ];
    $years = "(?:".implode('|', $year).")"; */
    $years = "(?:20)?(?:0[1-9]|[12]\d)";
    
    $month = [
        'F' => "(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|June?|July?|Aug(?:ust)?|Sept?(?:ember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)",
        //'M' => "(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sept|Oct|Nov|Dec)",
        'm' => "(?:0?[1-9]|1[0-2])",
        //'n' => "(?:[1-9]|1[0-2])"
    ];
    $months = "(?:".implode('|', $month).")";
    
    $day = [
        'd' => "(?:0?[1-9]|[12]\d|3[01])(?:nd|th)?",
        //'j' => "(?:[1-9]|[12]\d|3[0-1]])(?:nd|th)?",
        //'D' => "(?:Sun|Mon|Tues|Tue|Tu|Wed|Thurs|Thu|Th|Fri|Sat)",
        'l' => "(?:Sun(?:day)?|Mon(?:day)?|Tue?s?(?:day)?|Wed(?:nesday)?|Thu?r?s?(?:day)?|Fri(?:day)?|Sat(?:urday)?)"
    ];
    $days = "\b(?:".implode('|', $day).")\b";
    
    $seperators = "(?:\/|\.|-|\s|,\s)";
    
    $regex  = "$days$seperators$months$seperators$days$seperators?$years?|";
    $regex .= "$years$seperators$months$seperators$days|";
    $regex .= "$years$seperators$days$seperators$months|";
    $regex .= "$months$seperators$days$seperators?$years?|";
    $regex .= "$days$seperators$months$seperators?$years?";

    return $regex;
}

/**
 * Parse Prayers Post
 */
function parsePostContent($post){
	$text		= preg_replace("/(*UTF8)(\x{002D}|\x{058A}|\x{05BE}|\x{2010}|\x{2011}|\x{2012}|\x{2013}|\x{2014}|\x{2015}|\x{2E3A}|\x{2E3B}|\x{FE58}|\x{FE63}|\x{FF0D})/mus", "-", $post->post_content);
	
	/**
     * build the regex
     **/

    // the date pattern itself
	$dateRegex      = dateRegex(); 

    // makes sure the date is not part of a bigger word and is on its own line
    $charsAfterDate = "(?=(?:\R|<|\s)).{0,10}?(?:<br>|<br \/>|<br\/>)"; 

    // This captures the first line, the date
    $dateLine       = "(?P<date>$dateRegex)$charsAfterDate";

    // Captures the heading of the prayer request
    $heading        = "(?P<heading>.+?)(?:<br>|<br \/>|<br\/>)"; 

    // The actual message
    $message        = "(?P<message>.+?)";

    // the line of the next prayer request or the end of the document
    $end            = "(?=(?:(?:$dateRegex)$charsAfterDate|$))";
    
    // All combined
    $re			    = "/(*UTF8)$dateLine$heading$message$end/s";
	preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
	
    if(count($matches) < 28){
        return false; // Less than 28 prayer requests found
    }
    
    $prayerRequests = [];

    foreach($matches as $match){
    	$html		= $match['message'];
    
    	$heading	= stripTags($match['heading']);
    	if(!str_contains($heading, '<b>') && !str_contains($heading, '<strong>')){
    		$heading	= "<b>$heading</b>";
    	}

        $userPageLinks  = new SIM\UserPageLinks($heading, false);
    	
        $prayerRequests[$match['date']] = [
            'heading'   => $heading,
        
        	'prayer'	=> cleanMessage($html),
        
        	'userIds'	=> $userPageLinks->foundUsers,
        ];
    }
    
    return $prayerRequests;
}

function stripTags($content){
	// Content of page with all prayer requests of this month
	return trim(strip_tags($content, ['strong', 'b', 'em', 'i', 'details', 's', 'br']));
}

/**
 * Removes and balances html tags
 */
function cleanMessage($msg){
	// < SOME TAG > one or more spaces followed by the same tag closing </ (\g1) >
	$re		= "/<([^>]*)>(?:\s|&nbsp;)*<\/\g1>/";

	//Remove empty tags
	$msg	= trim(preg_replace($re, '', $msg));

	// Balance
	$msg	= force_balance_tags($msg);

	//Remove empty tags again after balancing
	$msg	= trim(preg_replace($re, '', $msg));

	// Remove starting and ending line breaks
	$msg	= preg_replace("/(^(<br\s*\/?>)|(<br\s*\/?>\s*)+$)/", "", $msg	);

	return $msg;
}

function createPrayerPosts( $postId, $post, $update ) {
    // Check if it's an autosave or a revision
    if ( 
        $post->post_type != 'prayer-request' || // We should only process prayer-request posts
        $post->post_status != 'publish' ||      // Only process if published
        wp_is_post_autosave( $postId ) || 
        wp_is_post_revision( $postId ) || 
        !empty($post->post_parent)              // only process if this is not a child itself
    ) {
        return;
    }

    $prayerRequests = parsePostContent($post);

    if(!$prayerRequests){
        return;
    }

    // remove any children of this post
    $posts = get_posts(
		array(
			'post_type'     => 'prayer-request',
			'posts_per_page'=> -1,
			'post_parent'	=> $post->ID
		)
	);
    foreach($posts as $prevPost){
        wp_delete_post($prevPost->ID, true);
    }

    // Get the categrories from post
    if(!empty($_POST['prayer-requests-ids'])){
        $cats   = $_POST['prayer-requests-ids'];

        $cats   = array_map( 'intval', $cats );
    }else{
        $cats   = wp_get_post_categories($post->ID);
    }
    
    foreach($prayerRequests as $date => $prayerRequest){
        $date       = date(DATEFORMAT, strtotime($date));
        $postData   = array(
            'post_title'    => "Prayer Request for $date: {$prayerRequest['heading']}",
            'post_content'  => $prayerRequest['prayer'],
            'post_status'   => 'publish',
            'post_type'     => 'prayer-request',
            'post_author'   => isset($prayerRequest['userIds'][0]) ? $prayerRequest['userIds'][0] : $post->post_author, 
            'post_parent'   => $post->ID
        );
        
        // Insert the post into the database
        $postId = wp_insert_post( $postData, false, false );
        
        if ( is_wp_error( $postId ) ) {
            SIM\printArray('Error inserting post: ' . $postId->get_error_message());
        } 
        
        add_post_meta( $postId, 'date', date('Y-m-d', strtotime($date)), true );

        foreach($prayerRequest['userIds'] as $userId){
            add_post_meta( $postId, 'user-id', $userId, false );
        }

        // Store the cat
        wp_set_post_terms($postId, $cats, 'prayer-requests');
    }
}
add_action( 'save_post_prayer-request', __NAMESPACE__.'\createPrayerPosts', 10, 3 );
