<?php
namespace SIM\PRAYER;
use SIM;

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
		$s			= date("F Y");

		//Current date
		$datetime 	= time();
	}else{
		// epoch
		if(is_numeric($date)){
			$datetime	= $date;
		}else{
			// date string given
			$datetime 	= strtotime($date);
		}

		$s			= date("F Y", $datetime);
	}

	//Get all the post belonging to the prayer category
	$posts = get_posts(
		array(
			'category'  		=> get_cat_ID('Prayer'),
			's'					=> $s,
			'numberposts'		=> -1,
			'search_columns'	=> ['post_title'],
			//'sentence'			=> true
		)
	);
	
	$params	= [
		'message'	=> '',
		'urls'		=> [],
		'pictures'	=> [],
		'users'		=> [],
		'post'		=> -1,
		'html'		=> ''
	];

	//Loop over them to find the post(s) for this month
	foreach($posts as $post){
		// double check if the current month and year is in the title as the s parameter searches everywhere
		if(!str_contains($post->post_title, date("F")) && !str_contains($post->post_title, date("Y"))){
			continue;
		}
		
		$params	= apply_filters('sim-prayer-params', $params, $datetime, $post, $plainText);
	}

	if(empty($params)){
		if($plainText){
			
			return [
				'message'	=> 'Sorry I could not find any prayer request for today', 
				'pictures'	=> []
			];
		}
		return false;
	}

	return $params;
}