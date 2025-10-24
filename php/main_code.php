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
			'post_type'  		=> 'prayer',
			'post_status'  => 'publish',
			'meta_key'					=> 'date',
			'meta_value'   => $date,
			'numberposts'		=> -1,
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
	
	$message = '';
	$users = [];
	foreach($posts as $post){
		$message .= $post->post_title.'<br>';
		$message .= $post->post_content.'<br>';
		
		$users[] = $post->post_author;
	}
	
	if($plainText){
		$message = stripTags($message);
	}
	
	$params	= [
		'message'	=> $message,
		'urls'		=> [],
		'pictures'	=> [],
		'users'		=> $users
	];

	$params	= apply_filters('sim-prayer-params', $params, $date, $post, $plainText);

	return $params;
}

add_filter('sim-prayer-request-to-update', __NAMESPACE__.'\prayerToBeUpdated', 10, 3);
function prayerToBeUpdated($prayer, $replaceDate, $receivedMessage){
	// Make sure we send back the raw html message to be replaced with a new one
	if($receivedMessage == 'update prayer correct'){
		$exploded			= explode(' - ', $prayer['html']);
		
		$prayer['message']	= $exploded[1];
	}else{
		$exploded	= explode('<i>SIM Nigeria</i></div>', $prayer['message']);

		if(isset($exploded[1])){
			$exploded2	= explode('</b><br/>', $exploded[1]);
			if(isset($exploded2[1])){
				$prayer['message']	= $exploded2[1];
			}else{
				$prayer['message']	= $exploded[1];
			}
		}
	}

	return $prayer;
}