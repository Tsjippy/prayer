<?php
namespace SIM\PRAYER;
use SIM;

add_action('init', function(){
	SIM\registerPostTypeAndTax('prayer-request', 'prayer-requests');
});

//give prayer coordinator acces to prayer items
add_filter('sim_frontend_content_edit_rights', __NAMESPACE__.'\editRights', 10, 2);
function editRights($editRight, $postCategory){
	
	if(
		!$editRight														&&	// If we currently have no edit right
		in_array('prayercoordinator', wp_get_current_user()->roles)		&& 	// If we have the prayer coordinator role and the post or page has the prayer category
		(
			in_array(get_cat_ID('Prayer'), $postCategory) 				||
			in_array('prayer', $postCategory)
		)
	){
		$editRight = true;
	}

	return $editRight;
}

/**
 *
 * Get the prayer request of today
 *
 * @param   string     	$plainText      Whether we shuld return the prayer request in html or plain text
 * @param	bool		$verified		If we trust the request, default false
 * @param	string|int	$date			The date or time string for which to get the request, default empty for today
 *
 * @return   array|false     			An array containing the prayer request and pictures or false if no prayer request found
 *
**/
function prayerRequest($plainText = false, $verified=false, $date='') {
	if (!is_user_logged_in() && !$verified){
		return false;
	}

	if(empty($date)){
		$date = date("Y-m-d");
	}else{
		// epoch
		if(is_numeric($date)){
			$datetime	= $date;
		}else{
			// date string given
			$datetime 	= strtotime($date);
		}

		$date			= date("Y-m-d", $datetime);
	}

	//Get all the prayer posts for this date
	$posts = get_posts(
		array(
			'post_type'		=> 'prayer-request',
			'post_status'  	=> 'publish',
			'meta_key'		=> 'date',
			'meta_value'   	=> $date,
			'numberposts'	=> -1,
		)
	);
	
	if(empty($posts)){
		if($plainText){
			
			return [
				'message'	=> 'Sorry I could not find any prayer request for today', 
				'pictures'	=> []
			];
		}
		return false;
	}
	
	$message	= '';
	$users 		= [];
	$pictures	= [];
	$urls		= [];

	foreach($posts as $post){
		$cats		 = wp_get_post_terms($post->ID, 'prayer-requests');
		foreach($cats as $cat){
			$message	.= "<i>$cat->name</i><br>";
		}

		$message	.= trim(explode(':', $post->post_title)[1]).'<br>';
		$message	.= $post->post_content.'<br>';
		
		$users		 = array_merge(get_post_meta($post->ID, 'user-id'), $users);
	}

	foreach($users as $userId ){
		// family picture
		$family			= get_user_meta($userId, 'family', true);

		if(!empty($family['picture'])){
			if(is_array($family['picture'])){
				$attachmentId	= $family['picture'][0];
			}elseif(is_numeric($family['picture'])){
				$attachmentId	= $family['picture'];
			}								
		}else{
			$attachmentId	= get_user_meta($userId, 'profile_picture', true);
			if(is_array($attachmentId)){
				if (isset($attachmentId[0])){
					$attachmentId	= $attachmentId[0];
				}else{
					$attachmentId	= 0;						}
			}
		}

		if(is_numeric($attachmentId)){
			$picture 	= get_attached_file($attachmentId);
		}else{
			$picture 	= SIM\urlToPath($attachmentId);
		}

		if(!in_array($picture, $pictures)){
			$pictures[]	= $picture;
		}

		// user page url
		$url		= SIM\maybeGetUserPageUrl($userId);
		if($url && !in_array($url, $urls)){
			$urls[]	= $url;
		}
	}
	
	if($plainText){
		$message	= str_replace(['<br>', '</br>', '</ br>', '<br />'], "\n", $message);
		$message 	= stripTags($message);
	}
	
	$params	= [
		'message'	=> $message,
		'pictures'	=> $pictures,
		'urls'		=> $urls,
		'users'		=> $users
	];

	// skip filter if we are not returning it for a signal message for today
	if($plainText && $date == date("Y-m-d")){
		$params	= apply_filters('sim_after_bot_payer', $params);

		//prevent duplicate urls
		$params['urls']		= array_unique($params['urls']);

		$params['message']	= $params['message']."\n\n".implode("\n", $params['urls']);
	}

	return $params;
}