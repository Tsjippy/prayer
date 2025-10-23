<?php

function dateRegex(){
    $year = [
        'Y' => "d{4}",
        'y' => "d{2}"
    ];
    $years = "(?:".implode('|', $year).")";
    
    $month = [
        'F' => "(:?January|February|March|April|May|June|July|August|September|October|November|December)",
        'M' => "(:?Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sept|Oct|Nov|Dec)",
        'm' => "d{2}",
        'n' => "d{1,2}"
    ];
    $months = "(?:".implode('|', $month).")";
    
    $day = [
      'd' => "d{2}(?:nd|th)?",
        'j' => "d{1,2}(?:nd|th)?",
        'D' => "(:?Sun|Mon|Tues|Tue|Tu|Wed|Thurs|Thu|Th|Fri|Sat)",
        'l' => "(:?Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)"
    ];
    $days = "(?:".implode('|', $day).")";
    
    $seperators = "(?:/|.|-|\s|, )";
    
    $regex = "($years$seperators$months$seperators$days|$years$seperators$days$seperators$months|$months$seperators$days$seperators$years?|$days$seperators$months$seperators$years?)";
    
    return $regex;
}

/**
 * Parse Prayers Post
 */
function parsePostContent($post){
	$text		= preg_replace("/(*UTF8)(\x{002D}|\x{058A}|\x{05BE}|\x{2010}|\x{2011}|\x{2012}|\x{2013}|\x{2014}|\x{2015}|\x{2E3A}|\x{2E3B}|\x{FE58}|\x{FE63}|\x{FF0D})/mus", "-", $post->post_content);
	
	// build the regex
	$dateRegex = dateRegex();
    $re			= "/(*UTF8)$dateRegex.*?(?:<br>|<br \/>|<br\/>)(.+?)(?:<br>|<br \/>|<br\/>)(.+?)($dateRegex|$)/s";
	preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
	
    if(count($matches) < 28){
        rerurn new WP_Error('prayer', ' Less than 28 prayer requests found!');
    }
    
	// prayer request not found
	if (!isset($matches[0][2]) || empty($matches[0][2])){
		return false;
	}
    
    $prayerRequests = [];

    foreach($matches as $match){
    	$html		= $match[3];
    
    	$heading	= stripTags($match[2]);
    	if(!str_contains($heading, '<b>') && !str_contains($heading, '<strong>')){
    		$heading	= "<b>$heading</b>";
    	}
    	
        $prayerRequests[$match[1]] = [
            'heading'   => $heading,
            
        	'html'	    => stripTags($html),
        
        	'prayer'	=> cleanMessage($html),
        
        	'userIds'	=> SIM\findUsers($heading, false),
        ];
    }
    
    return $prayerRequests;
}

function createPrayerPosts( $postId, $post, $update ) {
    // Check if it's an autosave or a revision
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    // remove old prayer posts with this post id
    $posts = get_posts();
    foreach($posts as $prevPost){
        wp_delete_post($prevPost->ID);
    }

    $prayerRequests = parsePostContent($post);
    
    foreach($prayerRequests as $date => $prayerRequest){
        $postData = array(
            'post_title'    => "Prayer Request for $date",
            'post_content'  => $prayer['message'],
            'post_status'   => 'publish', // or 'draft', 'pending', 'private'
            'post_type'     => 'prayer',    // or 'page', 'custom_post_type'
            'post_author'   => isset($prayer['userid']) ? $prayer['userid'] : $post->post_author,         // ID of the author
            'post_parent'.   => $post->ID
        );
        
        // Insert the post into the database
        $postId = wp_insert_post( $postData );
        
        if ( is_wp_error( $postId ) ) {
            echo 'Error inserting post: ' . $postId->get_error_message();
        } else {
            echo 'Post inserted successfully with ID: ' . $postId;
        }
        
        add_post_meta( $postId, 'date', date('Y-m-d', strtotime($date)), true );
    }
}
add_action( 'save_post', __NAMESPACE__.'\createPrayerPosts', 10, 3 );
